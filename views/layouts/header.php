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
            
            <small class="text-uppercase text-white-50 fw-bold mb-1 d-block" style="font-size: 0.65rem;">Módulos</small>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item">
                    <a href="index.php?action=dashboard" class="nav-link active">
                        <i class="bi bi-speedometer2 me-2"></i> Panel Principal
                    </a>
                </li>
                <li>
                    <a href="index.php?action=caja" class="nav-link">
                        <i class="bi bi-graph-up-arrow me-2"></i> Caja y Reportes
                    </a>
                </li>
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
                        <?= htmlspecialchars($_SESSION['usuario_rol'] ?? 'Enfermera') ?>
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