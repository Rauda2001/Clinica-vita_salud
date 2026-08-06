<?php
require_once __DIR__ . '/../models/Paciente.php';
require_once __DIR__ . '/../models/Cita.php';

class MedicoController {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function atender() {
        Security::checkRole(['Medico']);
        $cita_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        
        // Cambiar estado a 'En consulta' inmediatamente al abrir expediente
        $this->db->prepare("UPDATE citas SET estado = 'En consulta' WHERE id = ?")->execute([$cita_id]);

        // Obtener detalles de la cita y paciente
        $stmt = $this->db->prepare("SELECT c.*, p.id as pac_id FROM citas c JOIN pacientes p ON c.paciente_id = p.id WHERE c.id = ?");
        $stmt->execute([$cita_id]);
        $cita_actual = $stmt->fetch();

        $pacienteModel = new Paciente();
        $paciente = $pacienteModel->findById($cita_actual['pac_id']);

        // Obtener Triaje de enfermería
        $stmtTriaje = $this->db->prepare("SELECT * FROM signos_vitales WHERE cita_id = ?");
        $stmtTriaje->execute([$cita_id]);
        $triaje = $stmtTriaje->fetch();

        require_once __DIR__ . '/../views/medico/consulta.php';
    }

    public function guardarAtencionCompleta() {
        Security::checkRole(['Medico']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
                die("CSRF Token Error.");
            }

            $cita_id = $_POST['cita_id'];
            $paciente_id = $_POST['paciente_id'];
            $diagnostico = Security::sanitize($_POST['diagnostico']);
            $tratamiento = Security::sanitize($_POST['tratamiento']);
            $motivo = Security::sanitize($_POST['motivo_consulta']);
            $sintomas = Security::sanitize($_POST['sintomas']);
            $receta = Security::sanitize($_POST['receta_medicamentos']);
            $monto = $_POST['monto'];
            $metodo = $_POST['metodo_pago'];

            try {
                $this->db->beginTransaction();

                // 1. Guardar Historial
                $obs = "Motivo: " . $motivo . " | Sintomas: " . $sintomas;
                $stmtH = $this->db->prepare("INSERT INTO historial_clinico (paciente_id, medico_id, cita_id, diagnostico, tratamiento, observaciones) VALUES (?, ?, ?, ?, ?, ?)");
                $stmtH->execute([$paciente_id, $_SESSION['user_id'], $cita_id, $diagnostico, $tratamiento, $obs]);
                $historial_id = $this->db->lastInsertId();

                // 2. Guardar Receta si existe
                if (!empty($receta)) {
                    $this->db->prepare("INSERT INTO recetas (historial_id, medicamento) VALUES (?, ?)")->execute([$historial_id, $receta]);
                }

                // 3. Registrar Transacción Financiera Directa
                $this->db->prepare("INSERT INTO ingresos (cita_id, monto, metodo_pago, fecha_pago) VALUES (?, ?, ?, CURDATE())")->execute([$cita_id, $monto, $metodo]);

                // 4. Cerrar Cita Actual
                $this->db->prepare("UPDATE citas SET estado = 'Atendida' WHERE id = ?")->execute([$cita_id]);

                // 5. Gestión Automática de Citados para Retorno
                if (isset($_POST['requiere_regreso'])) {
                    $stmtC = $this->db->prepare("INSERT INTO citas (paciente_id, medico_id, fecha, hora, estado, tipo_ingreso, motivo) VALUES (?, (SELECT id FROM medicos WHERE usuario_id = ?), ?, ?, 'Pendiente', 'Citado', 'Control Médico Obligatorio')");
                    $stmtC->execute([$paciente_id, $_SESSION['user_id'], $_POST['fecha_proxima'], $_POST['hora_proxima']]);
                }

                $this->db->commit();
                header("Location: index.php?action=dashboard&success=atencion_exitosa");
            } catch (Exception $e) {
                $this->db->rollBack();
                die("Error crítico al procesar la atención médica: " . $e->getMessage());
            }
        }
    }
}