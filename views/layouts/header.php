<?php
// =========================================================================
// VITASALUD - LAYOUT HEADER (views/layouts/header.php)
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es" class="notranslate" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google" content="notranslate">
    <title>VitaSalud Metalío</title>
    
    <!-- Bootstrap 5 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }
        .sidebar {
            width: 240px;
            height: 100vh;
            position: sticky;
            top: 0;
            background-color: #0b3c5d;
            color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            border-radius: 6px;
            margin-bottom: 2px;
            font-weight: 500;
            padding: 8px 12px;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.15);
        }
        .main-content {
            flex: 1;
            min-width: 0;
            overflow-y: auto;
        }
    </style>
</head>
<body class="notranslate" translate="no">

<div class="d-flex">
    <!-- BARRA LATERAL / SIDEBAR -->
    <div class="sidebar p-3 flex-shrink-0">
        <div>
            <a href="index.php?action=dashboard" class="d-flex align-items-center mb-2 text-white text-decoration-none">
                <i class="bi bi-heart-pulse-fill fs-3 text-success me-2"></i>
                <div>
                    <span class="fs-4 fw-bold d-block lh-1">VitaSalud</span>
                    <small class="text-white-50" style="font-size: 0.75rem;">Metalío</small>
                </div>
            </a>
            
            <hr class="text-secondary my-2">
            
            <?php
            $rolActual = $_SESSION['usuario_rol'] ?? $_SESSION['rol'] ?? null;
            $esAdmin = (string)$rolActual === '1' || strtolower((string)$rolActual) === 'administrador';
            $esEnfermeria = (string)$rolActual === '2' || in_array(strtolower((string)$rolActual), ['enfermera', 'recepcion'], true);
            $etiquetasRol = [1 => 'Administrador', 2 => 'Enfermería', 3 => 'Médico'];
            $rolEtiqueta = is_numeric($rolActual) ? ($etiquetasRol[(int)$rolActual] ?? 'Usuario') : ($rolActual ?? 'Usuario');
            ?>
            <small class="text-uppercase text-white-50 fw-bold mb-1 d-block" style="font-size: 0.65rem;">Módulos</small>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item">
                    <a href="index.php?action=dashboard" class="nav-link <?= ($_GET['action'] ?? 'dashboard') === 'dashboard' ? 'active' : '' ?>">
                        <i class="bi bi-speedometer2 me-2"></i> Panel Principal
                    </a>
                </li>
                <?php if ($esEnfermeria || $esAdmin): ?>
                <li><a href="index.php?action=nuevo_expediente" class="nav-link <?= ($_GET['action'] ?? '') === 'nuevo_expediente' ? 'active' : '' ?>"><i class="bi bi-person-plus me-2"></i> Crear expediente</a></li>
                <li><a href="index.php?action=triaje_cola" class="nav-link <?= ($_GET['action'] ?? '') === 'triaje_cola' ? 'active' : '' ?>"><i class="bi bi-activity me-2"></i> Triaje y cola de espera</a></li>
                <li><a href="index.php?action=pendientes_enfermeria" class="nav-link <?= ($_GET['action'] ?? '') === 'pendientes_enfermeria' ? 'active' : '' ?>"><i class="bi bi-calendar2-check me-2"></i> Citas pendientes y aplicaciones</a></li>
                <li><a href="index.php?action=pacientes_estadisticas" class="nav-link <?= ($_GET['action'] ?? '') === 'pacientes_estadisticas' ? 'active' : '' ?>"><i class="bi bi-people me-2"></i> Pacientes y estadísticas</a></li>
                <li><a href="index.php?action=inventario" class="nav-link"><i class="bi bi-capsule me-2"></i> Inventario y farmacia</a></li>
                <?php endif; ?>
                <?php if ($esAdmin): ?>
                <?php if (!$esEnfermeria): ?>
                <li><a href="index.php?action=pacientes_estadisticas" class="nav-link <?= ($_GET['action'] ?? '') === 'pacientes_estadisticas' ? 'active' : '' ?>"><i class="bi bi-people me-2"></i> Pacientes y estadísticas</a></li>
                <?php endif; ?>
                <li><a href="index.php?action=reportes" class="nav-link"><i class="bi bi-graph-up-arrow me-2"></i> Reportes administrativos</a></li>
                <li><a href="index.php?action=usuarios" class="nav-link"><i class="bi bi-people-gear me-2"></i> Usuarios y contraseñas</a></li>
                <?php endif; ?>
            </ul>
        </div>
        
        <!-- PERFIL Y BOTÓN CERRAR SESIÓN -->
        <div class="pt-2 border-top border-secondary">
            <div class="d-flex align-items-center mb-2">
                <i class="bi bi-person-circle fs-4 me-2 text-info"></i>
                <div class="overflow-hidden">
                    <strong class="d-block text-truncate small">
                        <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Soraya Gómez') ?>
                    </strong>
                    <span class="badge bg-secondary" style="font-size: 0.65rem;">
                        <?= htmlspecialchars($rolEtiqueta, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>
            </div>
            
            <!-- Redirección directa por JS para evitar cualquier bloqueo -->
            <button type="button" 
                    onclick="window.location.href='index.php?action=logout';" 
                    class="btn btn-danger w-100 fw-bold btn-sm shadow-sm">
                <i class="bi bi-box-arrow-right me-1"></i> Cerrar Sesión
            </button>
        </div>
    </div>

    <!-- CONTENIDO PRINCIPAL -->
    <div class="main-content">