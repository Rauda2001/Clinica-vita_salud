<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crear administrador de recuperación - VitaSalud</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-5">
    <div class="card shadow-sm mx-auto" style="max-width: 620px">
        <div class="card-header bg-primary text-white">
            <h1 class="h4 mb-0">Crear administrador de recuperación</h1>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                Este formulario funciona una sola vez y solo desde localhost. Crea un usuario administrador nuevo; no revela ni cambia las contraseñas actuales.
            </div>
            <form action="index.php?action=guardar_administrador_inicial" method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="nombre">Nombre</label>
                        <input class="form-control" id="nombre" name="nombre" maxlength="100" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="apellido">Apellido</label>
                        <input class="form-control" id="apellido" name="apellido" maxlength="100" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="username">Nombre de usuario</label>
                    <input class="form-control" id="username" name="username" minlength="3" maxlength="50" pattern="[A-Za-z0-9._-]+" required>
                    <div class="form-text">De 3 a 50 caracteres: letras, números, punto, guion o guion bajo.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="correo">Correo (opcional)</label>
                    <input class="form-control" type="email" id="correo" name="correo" maxlength="100">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Contraseña nueva</label>
                    <input class="form-control" type="password" id="password" name="password" minlength="12" autocomplete="new-password" required>
                    <div class="form-text">Usa al menos 12 caracteres.</div>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password_confirm">Confirmar contraseña</label>
                    <input class="form-control" type="password" id="password_confirm" name="password_confirm" minlength="12" autocomplete="new-password" required>
                </div>
                <div class="d-flex justify-content-between">
                    <a class="btn btn-outline-secondary" href="index.php?action=login">Cancelar</a>
                    <button class="btn btn-primary" type="submit">Crear administrador (una vez)</button>
                </div>
            </form>
        </div>
    </div>
</main>
</body>
</html>
