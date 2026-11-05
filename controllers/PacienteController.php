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

        // Enfermería / recepción: cola y médicos disponibles para asignación.
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

        // Lista de médicos activos
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

        // Últimos pacientes creados
        $queryPacientes = "SELECT * FROM pacientes ORDER BY id DESC LIMIT 50";
        $stmtP = $db->prepare($queryPacientes);
        $stmtP->execute();
        $pacientes = $stmtP->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../views/enfermera/dashboard.php';
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
        $this->verificarSesion();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db = $this->getDB();

            $nombres = trim($_POST['nombres'] ?? '');
            $apellidos = trim($_POST['apellidos'] ?? '');
            $fecha_nac = $_POST['fecha_nacimiento'] ?? null;
            $sexo = $_POST['sexo'] ?? '';
            $dui = trim($_POST['dui'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $direccion = trim($_POST['direccion'] ?? '');
            $expediente = 'VS-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            $stmt = $db->prepare("INSERT INTO pacientes (expediente, nombres, apellidos, fecha_nacimiento, sexo, dui, telefono, direccion)
                                  VALUES (:expediente, :nombres, :apellidos, :fecha_nac, :sexo, :dui, :telefono, :direccion)");

            $stmt->execute([
                ':expediente' => $expediente,
                ':nombres' => $nombres,
                ':apellidos' => $apellidos,
                ':fecha_nac' => $fecha_nac,
                ':sexo' => $sexo,
                ':dui' => $dui,
                ':telefono' => $telefono,
                ':direccion' => $direccion
            ]);

            header("Location: index.php?action=dashboard&success=1");
            exit();
        }
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