<?php
require_once __DIR__ . '/../models/Cita.php';

class CitaController {
    public function dashboard() {
        Security::checkRole(['Administrador', 'Enfermera', 'Medico']);
        $citaModel = new Cita();

        // 1. Cargar la barra lateral y cabecera global (Sidebar + HTML inicial)
        require_once __DIR__ . '/../views/layouts/header.php';

        // 2. Cargar el dashboard según el rol del usuario
        if ($_SESSION['rol'] === 'Medico') {
            $agenda = $citaModel->getAgendaMedico($_SESSION['user_id']);
            require_once __DIR__ . '/../views/medico/dashboard.php';
        } else {
            $cola = $citaModel->getColaDeEspera();
            $medicos = $citaModel->getMedicosDisponibles();
            require_once __DIR__ . '/../views/enfermera/dashboard.php';
        }

        // 3. Cargar el pie de página global (cierre de etiquetas HTML)
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    public function procesarCola() {
        Security::checkRole(['Administrador', 'Enfermera']);
        $citaModel = new Cita();
        
        $subAccion = $_POST['sub_accion'] ?? null;
        $cita_id = $_POST['cita_id'] ?? null;

        if ($subAccion === 'mover_a_cola' && $cita_id) {
            $citaModel->moverACola($cita_id);
        } elseif ($subAccion === 'insertar_cola') {
            $paciente_id = $_POST['paciente_id'] ?? null;
            $medico_id = $_POST['medico_id'] ?? null;
            $tipo = $_POST['tipo_ingreso'] ?? null;
            $citaModel->insertarCitaDirecta($paciente_id, $medico_id, $tipo);
        }

        header("Location: index.php?action=dashboard&success=cola_actualizada");
        exit();
    }

    public function guardarTriaje() {
        Security::checkRole(['Administrador', 'Enfermera', 'Recepcion']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
            !Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            http_response_code(400);
            throw new RuntimeException('Solicitud no válida o token de seguridad vencido.');
        }

        $pacienteId = filter_var($_POST['paciente_id'] ?? null, FILTER_VALIDATE_INT);
        $medicoId = filter_var($_POST['medico_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$pacienteId || !$medicoId) {
            throw new InvalidArgumentException('Debe seleccionar un paciente y un médico válidos.');
        }

        $readString = static function ($field) {
            $value = $_POST[$field] ?? '';
            if (!is_string($value)) {
                throw new InvalidArgumentException('Los datos enviados no son válidos.');
            }
            return trim($value);
        };

        $presion = $readString('presion_arterial');
        if (strlen($presion) > 15) {
            throw new InvalidArgumentException('La presión arterial no debe superar 15 caracteres.');
        }

        $decimalFields = [
            'temperatura' => ['max' => 999.9, 'decimals' => 1],
            'peso' => ['max' => 999.99, 'decimals' => 2],
        ];
        $decimals = [];
        foreach ($decimalFields as $field => $rules) {
            $value = $readString($field);
            if ($value === '') {
                $decimals[$field] = null;
                continue;
            }
            $pattern = '/^\d+(?:\.\d{1,' . $rules['decimals'] . '})?$/D';
            if (!preg_match($pattern, $value) || (float)$value > $rules['max']) {
                throw new InvalidArgumentException('El valor de ' . $field . ' no es válido.');
            }
            $decimals[$field] = $value;
        }

        $integerFields = [
            'frecuencia_cardiaca' => 'frecuencia cardíaca',
            'frecuencia_respiratoria' => 'frecuencia respiratoria',
            'talla' => 'estatura',
        ];
        $integers = [];
        foreach ($integerFields as $field => $label) {
            $value = $readString($field);
            if ($value === '') {
                $integers[$field] = null;
                continue;
            }
            if (!ctype_digit($value) || (float)$value > 2147483647) {
                throw new InvalidArgumentException('El valor de ' . $label . ' no es válido.');
            }
            $integers[$field] = (int)$value;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT id FROM pacientes WHERE id = ?');
        $stmt->execute([$pacienteId]);
        if (!$stmt->fetchColumn()) {
            throw new InvalidArgumentException('El paciente seleccionado no existe.');
        }

        $stmt = $db->prepare(
            'SELECT m.id
             FROM medicos m
             INNER JOIN usuarios u ON u.id = m.usuario_id
             WHERE m.id = ? AND u.rol_id = 3 AND u.activo = 1'
        );
        $stmt->execute([$medicoId]);
        if (!$stmt->fetchColumn()) {
            throw new InvalidArgumentException('El médico seleccionado no está disponible.');
        }

        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                "INSERT INTO citas (paciente_id, medico_id, fecha, hora, estado, tipo_ingreso, motivo)
                 VALUES (?, ?, CURDATE(), CURTIME(), 'En espera', 'Orden de Llegada', 'Ingreso desde triaje')"
            );
            $stmt->execute([$pacienteId, $medicoId]);
            $citaId = $db->lastInsertId();

            $stmt = $db->prepare(
                'INSERT INTO signos_vitales
                    (cita_id, peso_kg, estatura_cm, temperatura_c, presion_arterial,
                     frecuencia_cardiaca, frecuencia_respiratoria)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $citaId,
                $decimals['peso'],
                $integers['talla'],
                $decimals['temperatura'],
                $presion !== '' ? $presion : null,
                $integers['frecuencia_cardiaca'],
                $integers['frecuencia_respiratoria'],
            ]);
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }

        header('Location: index.php?action=dashboard&success=triaje_guardado');
        exit();
    }

    public function programarHora() {
        Security::checkRole(['Administrador', 'Enfermera', 'Recepcion']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
            !Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            http_response_code(400);
            throw new RuntimeException('Solicitud no válida o token de seguridad vencido.');
        }

        $citaIdInput = $_POST['cita_id'] ?? null;
        $citaId = is_string($citaIdInput) || is_int($citaIdInput)
            ? filter_var($citaIdInput, FILTER_VALIDATE_INT)
            : false;
        $hora = $_POST['hora'] ?? null;
        if (!$citaId || !is_string($hora) ||
            !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $hora)) {
            throw new InvalidArgumentException('La cita o la hora indicada no son válidas.');
        }

        $db = Database::getConnection();
        $stmt = $db->prepare(
            "UPDATE citas SET hora = ?
             WHERE id = ? AND estado = 'Pendiente' AND fecha >= CURDATE()"
        );
        $horaCompleta = $hora . ':00';
        $stmt->execute([$horaCompleta, $citaId]);
        $actualizada = $stmt->rowCount() === 1;
        if (!$actualizada) {
            $stmt = $db->prepare(
                "SELECT id FROM citas
                 WHERE id = ? AND estado = 'Pendiente' AND fecha >= CURDATE() AND hora = ?"
            );
            $stmt->execute([$citaId, $horaCompleta]);
            $actualizada = (bool)$stmt->fetchColumn();
        }
        if (!$actualizada) {
            throw new RuntimeException('La cita ya no está pendiente o no se pudo actualizar su hora.');
        }

        $destino = ($_POST['return_to'] ?? '') === 'pendientes_enfermeria'
            ? 'pendientes_enfermeria'
            : 'dashboard';
        header('Location: index.php?action=' . $destino . '&success=hora_cita_guardada');
        exit();
    }
}