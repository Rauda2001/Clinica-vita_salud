<!DOCTYPE html>
<html lang="es" class="notranslate" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Sistema - VitaSalud Metalío</title>
    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #0b3c5d 0%, #1d2731 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            border: none;
            border-radius: 16px;
            background: #ffffff;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }
        .brand-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            padding: 30px 20px 20px 20px;
        }
        .brand-icon {
            width: 60px;
            height: 60px;
            background-color: #0b3c5d;
            color: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px auto;
            box-shadow: 0 4px 10px rgba(11, 60, 93, 0.3);
        }
        .form-control:focus {
            border-color: #0b3c5d;
            box-shadow: 0 0 0 0.25rem rgba(11, 60, 93, 0.15);
        }
        .btn-primary-custom {
            background-color: #0b3c5d;
            border-color: #0b3c5d;
            color: #ffffff;
            transition: all 0.3s ease;
        }
        .btn-primary-custom:hover {
            background-color: #072a42;
            border-color: #072a42;
        }
    </style>
</head>
<body class="notranslate" translate="no">

<div class="login-card">
    <!-- CABECERA INSTITUCIONAL -->
    <div class="brand-header text-center">
        <div class="brand-icon">
            <i class="bi bi-heart-pulse-fill fs-2"></i>
        </div>
        <h4 class="fw-bold text-dark mb-1">VitaSalud</h4>
        <p class="text-muted small mb-0">Sistema de Gestión Médica - Metalío</p>
    </div>

    <!-- CUERPO DEL FORMULARIO -->
    <div class="p-4">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger d-flex align-items-center py-2 px-3 small rounded-3 mb-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-6"></i>
                <div>Usuario o contraseña incorrectos.</div>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['admin_created'])): ?>
            <div class="alert alert-success small">Administrador creado. Inicia sesión con el nuevo usuario y contraseña.</div>
        <?php endif; ?>
        <?php if (!empty($requiereActualizarEsquema)): ?>
            <div class="alert alert-warning small">
                Para habilitar la recuperación, aplica en <code>vitasalud_db</code> el bloque de <code>configuracion_sistema</code> al final de <code>vitasalud_db.sql</code>.
            </div>
        <?php endif; ?>

        <form action="index.php?action=login" method="POST" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <!-- CAMPO USERNAME -->
            <div class="mb-3">
                <label for="username" class="form-label fw-semibold small text-secondary">Nombre de Usuario</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-secondary border-end-0"><i class="bi bi-person-fill"></i></span>
                    <input type="text" name="username" id="username" class="form-control border-start-0 bg-light" placeholder="Ej: admin, enfermera, medico1" required autofocus>
                </div>
            </div>

            <!-- CAMPO CONTRASEÑA -->
            <div class="mb-4">
                <label for="password" class="form-label fw-semibold small text-secondary">Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-secondary border-end-0"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" name="password" id="password" class="form-control border-start-0 bg-light" placeholder="••••••••" required>
                </div>
            </div>

            <!-- BOTÓN INGRESAR -->
            <button type="submit" class="btn btn-primary-custom w-100 fw-bold py-2 rounded-3 shadow-sm">
                <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar Sesión
            </button>
        </form>
        <?php if (!empty($puedeCrearAdministrador)): ?>
            <div class="border-top mt-4 pt-3 text-center">
                <a class="btn btn-outline-secondary btn-sm" href="index.php?action=crear_administrador_inicial">
                    Crear administrador de recuperación (uso único)
                </a>
                <small class="d-block text-muted mt-2">Disponible únicamente desde este equipo y hasta que se complete una vez.</small>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- PIE DE PÁGINA -->
    <div class="bg-light py-2 text-center border-top">
        <small class="text-muted" style="font-size: 0.75rem;">&copy; <?= date('Y') ?> VitaSalud Metalío - Todos los derechos reservados</small>
    </div>
</div>

</body>
</html>