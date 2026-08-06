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
}