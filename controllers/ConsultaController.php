<?php

class ConsultaController {
    private function db() {
        return Database::getConnection();
    }

    public function atender() {
        Security::checkRole(['Medico']);
        $citaId = filter_input(INPUT_GET, 'cita_id', FILTER_VALIDATE_INT);
        if (!$citaId) {
            throw new InvalidArgumentException('La cita indicada no es válida.');
        }

        $db = $this->db();
        $stmt = $db->prepare(
            "SELECT c.id AS cita_id, c.fecha, c.hora, c.estado,
                    p.*, sv.peso_kg, sv.estatura_cm, sv.temperatura_c,
                    sv.presion_arterial, sv.frecuencia_cardiaca,
                    sv.frecuencia_respiratoria, sv.observaciones AS observaciones_vitales,
                    sv.fecha_registro AS fecha_signos
             FROM citas c
             JOIN medicos m ON m.id = c.medico_id
             JOIN pacientes p ON p.id = c.paciente_id
             LEFT JOIN signos_vitales sv ON sv.cita_id = c.id
             WHERE c.id = ? AND m.usuario_id = ?"
        );
        $stmt->execute([$citaId, $_SESSION['usuario_id'] ?? $_SESSION['user_id']]);
        $paciente = $stmt->fetch();
        if (!$paciente || !in_array($paciente['estado'], ['En espera', 'En consulta'], true)) {
            http_response_code(404);
            throw new RuntimeException('La cita no existe, no está en su agenda o ya fue atendida.');
        }

        $stmt = $db->prepare("SELECT id, nombre, categoria, unidad_medida, stock FROM inventario WHERE activo = 1 AND stock > 0 ORDER BY nombre");
        $stmt->execute();
        $inventario = $stmt->fetchAll();

        require __DIR__ . '/../views/medico/consulta.php';
    }

    public function guardar() {
        Security::checkRole(['Medico']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
            !Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            http_response_code(400);
            throw new RuntimeException('Solicitud no válida o token de seguridad vencido.');
        }

        $citaId = filter_var($_POST['cita_id'] ?? null, FILTER_VALIDATE_INT);
        $motivo = trim($_POST['motivo_consulta'] ?? '');
        $diagnostico = trim($_POST['diagnostico'] ?? '');
        $tratamiento = trim($_POST['tratamiento'] ?? '');
        $recetaTexto = trim($_POST['receta'] ?? '');
        $indicaciones = trim($_POST['indicaciones_med'] ?? '');
        $proximaCita = trim($_POST['proxima_cita'] ?? '');
        $userId = (int)($_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? 0);

        if (!$citaId || $motivo === '' || $diagnostico === '') {
            throw new InvalidArgumentException('La cita, el motivo y el diagnóstico son obligatorios.');
        }
        if ($proximaCita !== '') {
            $fecha = DateTime::createFromFormat('Y-m-d', $proximaCita);
            if (!$fecha || $fecha->format('Y-m-d') !== $proximaCita || $proximaCita < date('Y-m-d')) {
                throw new InvalidArgumentException('La fecha de próxima cita debe ser válida y no anterior a hoy.');
            }
        }

        $cantidades = $_POST['cantidad'] ?? [];
        $productoIds = $_POST['producto_id'] ?? [];
        if (!is_array($cantidades) || !is_array($productoIds)) {
            throw new InvalidArgumentException('La selección de productos no es válida.');
        }
        $ordenes = [];
        foreach ($productoIds as $productoId) {
            $productoId = filter_var($productoId, FILTER_VALIDATE_INT);
            $cantidad = filter_var($cantidades[$productoId] ?? null, FILTER_VALIDATE_FLOAT);
            if (!$productoId || $cantidad === false || $cantidad <= 0) {
                throw new InvalidArgumentException('Cada producto seleccionado debe tener una cantidad mayor que cero.');
            }
            $ordenes[$productoId] = $cantidad;
        }

        $db = $this->db();
        $db->beginTransaction();
        try {
            $stmtCita = $db->prepare(
                "SELECT c.paciente_id, c.estado, m.usuario_id
                 FROM citas c JOIN medicos m ON m.id = c.medico_id
                 WHERE c.id = ? FOR UPDATE"
            );
            $stmtCita->execute([$citaId]);
            $cita = $stmtCita->fetch();
            if (!$cita || (int)$cita['usuario_id'] !== $userId ||
                !in_array($cita['estado'], ['En espera', 'En consulta'], true)) {
                throw new RuntimeException('La cita no está disponible para este médico.');
            }

            $historial = $db->prepare(
                "INSERT INTO historial_clinico
                    (paciente_id, medico_id, cita_id, diagnostico, tratamiento, observaciones)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $observaciones = 'Motivo de consulta: ' . $motivo;
            $historial->execute([
                $cita['paciente_id'], $userId, $citaId, $diagnostico,
                $tratamiento, $observaciones
            ]);
            $historialId = (int)$db->lastInsertId();

            if ($recetaTexto !== '') {
                $stmtReceta = $db->prepare("INSERT INTO recetas (historial_id, medicamento) VALUES (?, ?)");
                $stmtReceta->execute([$historialId, $recetaTexto]);
            }

            if ($ordenes) {
                $validarProducto = $db->prepare(
                    "SELECT id, nombre, stock FROM inventario WHERE id = ? AND activo = 1 FOR UPDATE"
                );
                $pendienteProducto = $db->prepare(
                    "SELECT COALESCE(SUM(cantidad), 0) FROM ordenes_aplicacion
                     WHERE inventario_id = ? AND estado = 'Pendiente'"
                );
                $stmtOrden = $db->prepare(
                    "INSERT INTO ordenes_aplicacion (historial_id, inventario_id, cantidad, indicaciones)
                     VALUES (?, ?, ?, ?)"
                );
                $stmtReceta = $db->prepare("INSERT INTO recetas (historial_id, medicamento) VALUES (?, ?)");
                foreach ($ordenes as $productoId => $cantidad) {
                    $validarProducto->execute([$productoId]);
                    $producto = $validarProducto->fetch();
                    if (!$producto) {
                        throw new RuntimeException('Uno de los productos seleccionados ya no está disponible.');
                    }
                    $pendienteProducto->execute([$productoId]);
                    $reservado = (float)$pendienteProducto->fetchColumn();
                    if ((float)$producto['stock'] - $reservado < (float)$cantidad) {
                        throw new RuntimeException('No hay existencias disponibles suficientes para ' . $producto['nombre'] . '.');
                    }
                    $stmtOrden->execute([$historialId, $productoId, $cantidad, $indicaciones ?: null]);
                    $detalleReceta = $producto['nombre'] . ' — ' . $cantidad . ' unidad(es)';
                    if ($indicaciones !== '') {
                        $detalleReceta .= '. Indicaciones: ' . $indicaciones;
                    }
                    $stmtReceta->execute([$historialId, $detalleReceta]);
                }
            }

            $cerrarCita = $db->prepare("UPDATE citas SET estado = 'Atendida' WHERE id = ?");
            $cerrarCita->execute([$citaId]);

            if ($proximaCita !== '') {
                $crearCita = $db->prepare(
                    "INSERT INTO citas (paciente_id, medico_id, fecha, hora, estado, tipo_ingreso, motivo)
                     SELECT ?, id, ?, '09:00:00', 'Pendiente', 'Citado', 'Cita de seguimiento'
                     FROM medicos WHERE usuario_id = ?"
                );
                $crearCita->execute([$cita['paciente_id'], $proximaCita, $userId]);
                if ($crearCita->rowCount() !== 1) {
                    throw new RuntimeException('No fue posible programar la próxima cita.');
                }
            }

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }

        header('Location: index.php?action=imprimir_consulta&cita_id=' . $citaId . '&saved=1');
        exit();
    }

    public function historial() {
        Security::checkRole(['Medico', 'Administrador']);
        $patientId = filter_input(INPUT_GET, 'paciente_id', FILTER_VALIDATE_INT);
        if (!$patientId) {
            throw new InvalidArgumentException('El expediente indicado no es válido.');
        }

        $db = $this->db();
        $stmt = $db->prepare("SELECT * FROM pacientes WHERE id = ?");
        $stmt->execute([$patientId]);
        $paciente = $stmt->fetch();
        if (!$paciente) {
            http_response_code(404);
            throw new RuntimeException('No se encontró el expediente del paciente.');
        }

        $stmt = $db->prepare(
            "SELECT hc.id, hc.cita_id, hc.diagnostico, hc.tratamiento,
                    hc.observaciones, hc.fecha_consulta,
                    CONCAT(u.nombre, ' ', u.apellido) AS medico,
                    sv.peso_kg, sv.estatura_cm, sv.temperatura_c,
                    sv.presion_arterial, sv.frecuencia_cardiaca,
                    sv.frecuencia_respiratoria, sv.fecha_registro AS fecha_signos
             FROM historial_clinico hc
             JOIN usuarios u ON u.id = hc.medico_id
             LEFT JOIN signos_vitales sv ON sv.cita_id = hc.cita_id
             WHERE hc.paciente_id = ?
             ORDER BY hc.fecha_consulta DESC, hc.id DESC"
        );
        $stmt->execute([$patientId]);
        $consultas = $stmt->fetchAll();

        $recetas = [];
        $ordenes = [];
        if ($consultas) {
            $ids = array_column($consultas, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("SELECT historial_id, medicamento FROM recetas WHERE historial_id IN ($placeholders) ORDER BY id");
            $stmt->execute($ids);
            foreach ($stmt->fetchAll() as $receta) {
                $recetas[$receta['historial_id']][] = $receta['medicamento'];
            }
            $stmt = $db->prepare(
                "SELECT oa.historial_id, i.nombre, oa.cantidad, i.unidad_medida,
                        oa.indicaciones, oa.estado
                 FROM ordenes_aplicacion oa
                 JOIN inventario i ON i.id = oa.inventario_id
                 WHERE oa.historial_id IN ($placeholders) ORDER BY oa.id"
            );
            $stmt->execute($ids);
            foreach ($stmt->fetchAll() as $orden) {
                $ordenes[$orden['historial_id']][] = $orden;
            }
        }

        require __DIR__ . '/../views/medico/historial.php';
    }

    public function imprimir() {
        Security::checkRole(['Medico', 'Administrador']);
        $citaId = filter_input(INPUT_GET, 'cita_id', FILTER_VALIDATE_INT);
        if (!$citaId) {
            throw new InvalidArgumentException('La consulta indicada no es válida.');
        }
        $db = $this->db();
        $stmt = $db->prepare(
            "SELECT hc.*, c.fecha, c.hora, p.id AS paciente_id, p.expediente,
                    p.nombres, p.apellidos, p.fecha_nacimiento, p.sexo,
                    u.nombre AS medico_nombre, u.apellido AS medico_apellido
             FROM historial_clinico hc
             JOIN citas c ON c.id = hc.cita_id
             JOIN pacientes p ON p.id = hc.paciente_id
             JOIN usuarios u ON u.id = hc.medico_id
             WHERE hc.cita_id = ?"
        );
        $stmt->execute([$citaId]);
        $consulta = $stmt->fetch();
        if (!$consulta) {
            http_response_code(404);
            throw new RuntimeException('No se encontró el resumen de la consulta.');
        }
        if (in_array('Medico', $this->rolesActuales(), true)) {
            $stmt = $db->prepare("SELECT 1 FROM citas c JOIN medicos m ON m.id = c.medico_id WHERE c.id = ? AND m.usuario_id = ?");
            $stmt->execute([$citaId, $_SESSION['usuario_id'] ?? $_SESSION['user_id']]);
            if (!$stmt->fetch()) {
                http_response_code(403);
                throw new RuntimeException('No tiene permiso para imprimir esta consulta.');
            }
        }

        $stmt = $db->prepare("SELECT * FROM signos_vitales WHERE cita_id = ?");
        $stmt->execute([$citaId]);
        $signos = $stmt->fetch();
        $stmt = $db->prepare("SELECT medicamento FROM recetas WHERE historial_id = ? ORDER BY id");
        $stmt->execute([$consulta['id']]);
        $recetas = $stmt->fetchAll();
        $stmt = $db->prepare(
            "SELECT i.nombre, i.unidad_medida, oa.cantidad, oa.indicaciones, oa.estado
             FROM ordenes_aplicacion oa JOIN inventario i ON i.id = oa.inventario_id
             WHERE oa.historial_id = ? ORDER BY oa.id"
        );
        $stmt->execute([$consulta['id']]);
        $ordenes = $stmt->fetchAll();
        require __DIR__ . '/../views/admin/consulta_imprimible.php';
    }

    private function rolesActuales() {
        $role = $_SESSION['rol'] ?? $_SESSION['usuario_rol'] ?? null;
        $roles = [1 => 'Administrador', 2 => 'Enfermera', 3 => 'Medico'];
        return [is_numeric($role) ? ($roles[(int)$role] ?? '') : str_replace('é', 'e', (string)$role)];
    }
}
