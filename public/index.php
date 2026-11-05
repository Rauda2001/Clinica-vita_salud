<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/PacienteController.php';
require_once __DIR__ . '/../controllers/ConsultaController.php';
require_once __DIR__ . '/../controllers/InventarioController.php';
require_once __DIR__ . '/../controllers/ReporteController.php';
require_once __DIR__ . '/../controllers/UsuarioController.php';

$action = $_GET['action'] ?? 'dashboard';

try {
    switch ($action) {
        case 'login':
            (new AuthController())->login();
            break;
        case 'crear_administrador_inicial':
            (new AuthController())->mostrarCreacionAdministrador();
            break;
        case 'guardar_administrador_inicial':
            (new AuthController())->crearAdministradorInicial();
            break;
        case 'logout':
            (new AuthController())->logout();
            break;
        case 'dashboard':
            $role = $_SESSION['usuario_rol'] ?? $_SESSION['rol'] ?? null;
            if ((string)$role === '1' || strtolower((string)$role) === 'administrador') {
                (new ReporteController())->verDashboardReportes();
            } else {
                (new PacienteController())->index();
            }
            break;
        case 'guardar_paciente':
        case 'registrar_paciente':
            (new PacienteController())->guardar();
            break;
        case 'buscar_paciente_ajax':
            (new PacienteController())->buscarAjax();
            break;
        case 'asignar_medico':
            (new PacienteController())->asignarMedico();
            break;
        case 'atender':
            (new ConsultaController())->atender();
            break;
        case 'guardar_consulta':
            (new ConsultaController())->guardar();
            break;
        case 'historial':
            (new ConsultaController())->historial();
            break;
        case 'imprimir_consulta':
            (new ConsultaController())->imprimir();
            break;
        case 'inventario':
            (new InventarioController())->index();
            break;
        case 'guardar_producto':
            (new InventarioController())->guardarProducto();
            break;
        case 'despachar_producto':
            (new InventarioController())->despachar();
            break;
        case 'reportes':
            (new ReporteController())->verDashboardReportes();
            break;
        case 'usuarios':
            (new UsuarioController())->index();
            break;
        case 'cambiar_password_usuario':
            (new UsuarioController())->cambiarPassword();
            break;
        default:
            http_response_code(404);
            echo 'La acción solicitada no existe.';
    }
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
} catch (RuntimeException $e) {
    http_response_code(http_response_code() >= 400 ? http_response_code() : 400);
    echo htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
} catch (Throwable $e) {
    error_log('VitaSalud request failure: ' . $e->getMessage());
    http_response_code(500);
    echo 'Ocurrió un error al procesar la solicitud. Revise el registro del servidor.';
}
