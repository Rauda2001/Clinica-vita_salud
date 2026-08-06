<?php
// =========================================================================
// VITASALUD - ENRUTADOR PRINCIPAL (public/index.php)
// =========================================================================

// Activar reporte de errores para evitar pantallas en blanco
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Carga de la conexión a Base de Datos
if (file_exists(__DIR__ . '/../config/database.php')) {
    require_once __DIR__ . '/../config/database.php';
} elseif (file_exists(__DIR__ . '/../models/Database.php')) {
    require_once __DIR__ . '/../models/Database.php';
} elseif (file_exists(__DIR__ . '/../config/Database.php')) {
    require_once __DIR__ . '/../config/Database.php';
}

// Carga de Controladores
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/PacienteController.php';

// Obtener la acción enviada por URL
$action = $_GET['action'] ?? (isset($_SESSION['usuario_id']) ? 'dashboard' : 'login');

switch ($action) {

    // --- AUTENTICACIÓN ---
    case 'login':
        $auth = new AuthController();
        $auth->login();
        break;

    case 'logout':
        $auth = new AuthController();
        $auth->logout();
        break;

    // --- PANEL DE PACIENTES / ENFERMERA ---
    case 'dashboard':
        $paciente = new PacienteController();
        $paciente->index();
        break;

    case 'registrar_paciente':
        $paciente = new PacienteController();
        $paciente->registrarPaciente();
        break;

    case 'buscar_paciente_ajax':
        $paciente = new PacienteController();
        $paciente->buscarPacienteAjax();
        break;

    case 'guardar_triaje':
        $paciente = new PacienteController();
        $paciente->guardarTriaje();
        break;

    // --- RUTA POR DEFECTO ---
    default:
        if (isset($_SESSION['usuario_id'])) {
            header("Location: index.php?action=dashboard");
        } else {
            header("Location: index.php?action=login");
        }
        exit();
}