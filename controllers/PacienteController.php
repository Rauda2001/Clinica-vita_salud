<?php
// =========================================================================
// VITASALUD - CONTROLADOR DE PACIENTES (controllers/PacienteController.php)
// =========================================================================

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

    public function index() {
        try {
            $db = $this->getDB();

            // 1. Obtener lista de Médicos ligada a 'black_especialidades'
            $sqlMedicos = "SELECT 
                            m.id AS medico_id, 
                            CONCAT(u.nombre, ' ', u.apellido) AS nombre_completo, 
                            IFNULL(e.nombre, 'Medicina General') AS especialidad 
                        FROM medicos m
                        INNER JOIN usuarios u ON m.usuario_id = u.id
                        LEFT JOIN black_especialidades e ON m.especialidad_id = e.id
                        ORDER BY m.id ASC";
            
            $stmtMedicos = $db->prepare($sqlMedicos);
            $stmtMedicos->execute();
            $medicos = $stmtMedicos->fetchAll(PDO::FETCH_ASSOC);

            // 2. Obtener la Cola de Espera desde la tabla 'citas'
            $sqlCola = "SELECT 
                            c.id AS cita_id,
                            CONCAT(p.nombres, ' ', p.apellidos) AS paciente_nombre,
                            p.expediente,
                            CONCAT(u.nombre, ' ', u.apellido) AS medico_nombre,
                            c.estado,
                            c.tipo_ingreso,
                            DATE_FORMAT(c.created_at, '%h:%i %p') AS hora_entrada
                        FROM citas c
                        INNER JOIN pacientes p ON c.paciente_id = p.id
                        INNER JOIN medicos m ON c.medico_id = m.id
                        INNER JOIN usuarios u ON m.usuario_id = u.id
                        WHERE c.estado = 'En espera'
                        ORDER BY c.created_at ASC";

            $stmtCola = $db->prepare($sqlCola);
            $stmtCola->execute();
            $colaEspera = $stmtCola->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            $medicos = [];
            $colaEspera = [];
        }

        require_once __DIR__ . '/../views/enfermera/dashboard.php';
    }

    public function registrarPaciente() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $db = $this->getDB();

                $nombres                        = trim($_POST['nombres'] ?? '');
                $apellidos                      = trim($_POST['apellidos'] ?? '');
                $fecha_nacimiento               = $_POST['fecha_nacimiento'] ?? '';
                $sexo                           = $_POST['sexo'] ?? 'Masculino';
                $dui                            = !empty($_POST['dui']) ? trim($_POST['dui']) : null;
                $telefono                       = trim($_POST['telefono'] ?? '');
                $direccion                      = !empty($_POST['direccion']) ? trim($_POST['direccion']) : null;
                $contacto_emergencia_nombre     = !empty($_POST['contacto_emergencia_nombre']) ? trim($_POST['contacto_emergencia_nombre']) : null;
                $contacto_emergencia_telefono   = !empty($_POST['contacto_emergencia_telefono']) ? trim($_POST['contacto_emergencia_telefono']) : null;

                $expediente = 'VS-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

                $sql = "INSERT INTO pacientes (
                            expediente, nombres, apellidos, fecha_nacimiento, sexo, 
                            dui, direccion, telefono, contacto_emergencia_nombre, 
                            contacto_emergencia_telefono, created_at
                        ) VALUES (
                            :expediente, :nombres, :apellidos, :fecha_nacimiento, :sexo, 
                            :dui, :direccion, :telefono, :contacto_emergencia_nombre, 
                            :contacto_emergencia_telefono, NOW()
                        )";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    ':expediente'                   => $expediente,
                    ':nombres'                      => $nombres,
                    ':apellidos'                    => $apellidos,
                    ':fecha_nacimiento'             => $fecha_nacimiento,
                    ':sexo'                         => $sexo,
                    ':dui'                          => $dui,
                    ':direccion'                    => $direccion,
                    ':telefono'                     => $telefono,
                    ':contacto_emergencia_nombre'   => $contacto_emergencia_nombre,
                    ':contacto_emergencia_telefono' => $contacto_emergencia_telefono
                ]);

                unset($_SESSION['form_data']);
                header("Location: index.php?action=dashboard&success=paciente_registrado");
                exit();

            } catch (Exception $e) {
                header("Location: index.php?action=dashboard&error=error_registro");
                exit();
            }
        }
    }

    public function buscarPacienteAjax() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        $q = trim($_GET['q'] ?? '');

        if ($q === '') {
            echo json_encode([]);
            exit();
        }

        try {
            $db = $this->getDB();
            $param = '%' . $q . '%';

            $sql = "SELECT id, expediente, nombres, apellidos, IFNULL(dui, '') AS dui, telefono 
                    FROM pacientes 
                    WHERE nombres LIKE :q1 
                       OR apellidos LIKE :q2 
                       OR expediente LIKE :q3 
                       OR dui LIKE :q4
                    LIMIT 10";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':q1' => $param,
                ':q2' => $param,
                ':q3' => $param,
                ':q4' => $param
            ]);

            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
            exit();

        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
            exit();
        }
    }

    public function guardarTriaje() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $db = $this->getDB();

                $paciente_id             = !empty($_POST['paciente_id']) ? $_POST['paciente_id'] : null;
                $medico_id               = !empty($_POST['medico_id']) ? $_POST['medico_id'] : null;
                $presion_arterial        = !empty($_POST['presion_arterial']) ? trim($_POST['presion_arterial']) : null;
                $temperatura             = !empty($_POST['temperatura']) ? $_POST['temperatura'] : null;
                $frecuencia_cardiaca     = !empty($_POST['frecuencia_cardiaca']) ? $_POST['frecuencia_cardiaca'] : null;
                $frecuencia_respiratoria = !empty($_POST['frecuencia_respiratoria']) ? $_POST['frecuencia_respiratoria'] : null;
                $peso                    = !empty($_POST['peso']) ? $_POST['peso'] : null;
                $talla                   = !empty($_POST['talla']) ? $_POST['talla'] : null;

                if (!$paciente_id || !$medico_id) {
                    header("Location: index.php?action=dashboard&error=datos_incompletos");
                    exit();
                }

                $db->beginTransaction();

                // 1. Crear Cita con estado 'En espera'
                $sqlCita = "INSERT INTO citas (paciente_id, medico_id, fecha, hora, estado, tipo_ingreso, created_at) 
                            VALUES (:paciente_id, :medico_id, CURDATE(), CURTIME(), 'En espera', 'Orden de Llegada', NOW())";
                
                $stmtCita = $db->prepare($sqlCita);
                $stmtCita->execute([
                    ':paciente_id' => $paciente_id,
                    ':medico_id'   => $medico_id
                ]);

                $cita_id = $db->lastInsertId();

                // 2. Registrar Signos Vitales asociados a la Cita
                $sqlSignos = "INSERT INTO signos_vitales (
                                cita_id, peso_kg, estatura_cm, temperatura_c, 
                                presion_arterial, frecuencia_cardiaca, frecuencia_respiratoria, fecha_registro
                            ) VALUES (
                                :cita_id, :peso_kg, :estatura_cm, :temperatura_c, 
                                :presion_arterial, :frecuencia_cardiaca, :frecuencia_respiratoria, NOW()
                            )";

                $stmtSignos = $db->prepare($sqlSignos);
                $stmtSignos->execute([
                    ':cita_id'                => $cita_id,
                    ':peso_kg'                => $peso,
                    ':estatura_cm'            => $talla,
                    ':temperatura_c'          => $temperatura,
                    ':presion_arterial'       => $presion_arterial,
                    ':frecuencia_cardiaca'    => $frecuencia_cardiaca,
                    ':frecuencia_respiratoria'=> $frecuencia_respiratoria
                ]);

                $db->commit();

                header("Location: index.php?action=dashboard&success=triaje_guardado");
                exit();

            } catch (Exception $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                header("Location: index.php?action=dashboard&error=error_triaje");
                exit();
            }
        }
    }
}