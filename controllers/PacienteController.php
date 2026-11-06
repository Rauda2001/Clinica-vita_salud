<?php
// =========================================================================
// VITASALUD - CONTROLADOR DE PACIENTES Y ROLES (controllers/PacienteController.php)
// =========================================================================

require_once __DIR__ . '/../models/Paciente.php';

class PacienteController {

    private function getDB() {
        if (method_exists('Database', 'conectar')) {
            return Database::conectar();
        } elseif (method_exists('Database', 'getConnection')) {
            return Database::getConnection();
        } elseif (method_exists('Database', 'getConexion')) {
            return Database::getConexion();
        } else {
            $db = new Database();
            return method_exists($db, 'conectar') ? $db->conectar() : $db->getConnection();
        }
    }

    private function verificarSesion() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['usuario_id'])) {
            header("Location: index.php?action=login");
            exit();
        }
    }

    // ---------------------------------------------------------------------
    // VISTA PRINCIPAL (DASHBOARD SEGÚN ROL)
    // ---------------------------------------------------------------------
    public function index() {
        $this->verificarSesion();
        $db = $this->getDB();

        $rol = $_SESSION['usuario_rol'] ?? $_SESSION['rol'] ?? 2;
        $usuario_id = $_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? 0;

        // ROL 3: MÉDICO (Ver solo sus pacientes asignados)
        if ($rol == 3 || strtolower((string)$rol) === 'medico' || strtolower((string)$rol) === 'médico') {
            $query = "SELECT c.*, p.nombres, p.apellidos, p.expediente, p.fecha_nacimiento
                      FROM citas c
                      INNER JOIN medicos m ON m.id = c.medico_id
                      INNER JOIN pacientes p ON c.paciente_id = p.id
                      WHERE m.usuario_id = :medico_id AND c.estado = 'En espera'
                      ORDER BY c.created_at ASC, c.id ASC";

            $stmt = $db->prepare($query);
            $stmt->execute([':medico_id' => $usuario_id]);
            $pacientesEnEspera = $stmt->fetchAll(PDO::FETCH_ASSOC);

            require_once __DIR__ . '/../views/medico/dashboard.php';
            exit();
        }

        require __DIR__ . '/../views/enfermera/dashboard.php';
    }

    public function nuevoExpediente() {
        Security::checkRole(['Administrador', 'Enfermera', 'Recepcion']);
        require __DIR__ . '/../views/enfermera/nuevo_expediente.php';
    }

    public function triajeCola() {
        Security::checkRole(['Administrador', 'Enfermera', 'Recepcion']);
        $db = $this->getDB();

        $queryCitas = "SELECT c.id, p.expediente,
                              CONCAT(p.nombres, ' ', p.apellidos) AS paciente_nombre,
                              CONCAT(u.nombre, ' ', u.apellido) AS medico_nombre,
                              c.hora AS hora_entrada, c.estado
                       FROM citas c
                       INNER JOIN pacientes p ON c.paciente_id = p.id
                       INNER JOIN medicos m ON m.id = c.medico_id
                       INNER JOIN usuarios u ON u.id = m.usuario_id
                       WHERE c.fecha = CURDATE() AND c.estado IN ('En espera', 'En consulta')
                       ORDER BY c.created_at ASC, c.id ASC";

        $stmtC = $db->prepare($queryCitas);
        $stmtC->execute();
        $colaEspera = $stmtC->fetchAll(PDO::FETCH_ASSOC);

        $queryMedicos = "SELECT m.id AS medico_id,
                                CONCAT(u.nombre, ' ', u.apellido) AS nombre_completo,
                                e.nombre AS especialidad
                         FROM medicos m
                         INNER JOIN usuarios u ON u.id = m.usuario_id
                         INNER JOIN black_especialidades e ON e.id = m.especialidad_id
                         WHERE u.rol_id = 3 AND u.activo = 1
                         ORDER BY u.nombre, u.apellido";
        $stmtM = $db->prepare($queryMedicos);
        $stmtM->execute();
        $medicos = $stmtM->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/enfermera/triaje.php';
    }

    public function pendientesEnfermeria() {
        Security::checkRole(['Administrador', 'Enfermera', 'Recepcion']);
        $db = $this->getDB();

        $citasPendientes = $db->query(
            "SELECT c.id, c.fecha, c.hora, c.motivo,
                    p.expediente,
                    CONCAT(p.nombres, ' ', p.apellidos) AS paciente_nombre,
                    CONCAT(u.nombre, ' ', u.apellido) AS medico_nombre
             FROM citas c
             INNER JOIN pacientes p ON p.id = c.paciente_id
             INNER JOIN medicos m ON m.id = c.medico_id
             INNER JOIN usuarios u ON u.id = m.usuario_id
             WHERE c.estado = 'Pendiente' AND c.fecha >= CURDATE()
             ORDER BY c.fecha, c.hora, c.id"
        )->fetchAll(PDO::FETCH_ASSOC);

        $ordenesPendientes = $db->query(
            "SELECT oa.id, oa.cantidad, oa.indicaciones, oa.creado_en,
                    i.nombre AS producto, i.unidad_medida,
                    p.id AS paciente_id, p.nombres, p.apellidos, p.expediente,
                    CONCAT(u.nombre, ' ', u.apellido) AS medico
             FROM ordenes_aplicacion oa
             INNER JOIN inventario i ON i.id = oa.inventario_id
             INNER JOIN historial_clinico hc ON hc.id = oa.historial_id
             INNER JOIN pacientes p ON p.id = hc.paciente_id
             INNER JOIN usuarios u ON u.id = hc.medico_id
             WHERE oa.estado = 'Pendiente'
             ORDER BY oa.creado_en, oa.id"
        )->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/enfermera/pendientes.php';
    }

    public function buscarAjax() {
        Security::checkRole(['Administrador', 'Enfermera', 'Recepcion']);
        header('Content-Type: application/json; charset=utf-8');

        $query = trim($_GET['q'] ?? '');
        if (strlen($query) < 2) {
            echo json_encode([], JSON_UNESCAPED_UNICODE);
            return;
        }
        if (strlen($query) > 100) {
            http_response_code(400);
            echo json_encode(['error' => 'La búsqueda no debe superar 100 caracteres.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $pacientes = (new Paciente())->buscar($query);
            echo json_encode($pacientes, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (PDOException $e) {
            error_log('VitaSalud patient search failure: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'No fue posible buscar pacientes. Intente de nuevo.'], JSON_UNESCAPED_UNICODE);
        }
    }

    // ---------------------------------------------------------------------
    // CREAR NUEVO EXPEDIENTE
    // ---------------------------------------------------------------------
    public function guardar() {
        Security::checkRole(['Administrador', 'Enfermera', 'Recepcion']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
            !Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            http_response_code(400);
            throw new RuntimeException('Solicitud no válida o token de seguridad vencido.');
        }

        $readString = static function ($field) {
            $value = $_POST[$field] ?? '';
            if (!is_string($value)) {
                throw new InvalidArgumentException('Los datos del expediente no son válidos.');
            }
            return trim($value);
        };

        $nombres = $readString('nombres');
        $apellidos = $readString('apellidos');
        $fechaNac = $readString('fecha_nacimiento');
        $sexo = $readString('sexo');
        $dui = $readString('dui');
        $telefono = $readString('telefono');
        $direccion = $readString('direccion');
        $contactoNombre = $readString('contacto_emergencia_nombre');
        $contactoTelefono = $readString('contacto_emergencia_telefono');
        $fechaValida = DateTime::createFromFormat('Y-m-d', $fechaNac);

        if ($nombres === '' || strlen($nombres) > 100 ||
            $apellidos === '' || strlen($apellidos) > 100 ||
            !$fechaValida || $fechaValida->format('Y-m-d') !== $fechaNac ||
            !in_array($sexo, ['Masculino', 'Femenino', 'Otros'], true) ||
            $telefono === '' || strlen($telefono) > 20 ||
            strlen($dui) > 10 || strlen($contactoNombre) > 150 ||
            strlen($contactoTelefono) > 20) {
            throw new InvalidArgumentException('Revise los campos obligatorios y sus formatos.');
        }

        $expediente = 'VS-' . date('Y') . '-' . str_pad((string)random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        $stmt = $this->getDB()->prepare(
            'INSERT INTO pacientes
                (expediente, nombres, apellidos, fecha_nacimiento, sexo, dui, telefono, direccion,
                 contacto_emergencia_nombre, contacto_emergencia_telefono)
             VALUES
                (:expediente, :nombres, :apellidos, :fecha_nac, :sexo, :dui, :telefono, :direccion,
                 :contacto_nombre, :contacto_telefono)'
        );
        $stmt->execute([
            ':expediente' => $expediente,
            ':nombres' => $nombres,
            ':apellidos' => $apellidos,
            ':fecha_nac' => $fechaNac,
            ':sexo' => $sexo,
            ':dui' => $dui !== '' ? $dui : null,
            ':telefono' => $telefono,
            ':direccion' => $direccion !== '' ? $direccion : null,
            ':contacto_nombre' => $contactoNombre !== '' ? $contactoNombre : null,
            ':contacto_telefono' => $contactoTelefono !== '' ? $contactoTelefono : null,
        ]);

        header('Location: index.php?action=nuevo_expediente&success=paciente_registrado');
        exit();
    }

    // ---------------------------------------------------------------------
    // ASIGNAR PACIENTE A MÉDICO
    // ---------------------------------------------------------------------
    public function asignarMedico() {
        $this->verificarSesion();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db = $this->getDB();
            $paciente_id = $_POST['paciente_id'] ?? null;
            $medico_id = $_POST['medico_id'] ?? null;

            if ($paciente_id && $medico_id) {
                $stmt = $db->prepare("INSERT INTO citas (paciente_id, medico_id, estado)
                                      VALUES (:paciente_id, :medico_id, 'En espera')");
                $stmt->execute([
                    ':paciente_id' => $paciente_id,
                    ':medico_id' => $medico_id
                ]);
            }

            header("Location: index.php?action=dashboard");
            exit();
        }
    }
}