<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-people-gear text-primary me-2"></i>Usuarios y contraseñas</h2>
            <p class="text-muted mb-0">Establece una contraseña nueva para una cuenta. Las contraseñas actuales no se muestran.</p>
        </div>
        <a class="btn btn-outline-secondary" href="index.php?action=dashboard">Volver al panel</a>
    </div>
    <?php if (isset($_GET['password_updated'])): ?>
        <div class="alert alert-success">La contraseña se actualizó correctamente.</div>
    <?php endif; ?>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Usuario</th><th>Nombre</th><th>Rol</th><th>Estado</th><th>Nueva contraseña</th></tr></thead>
                <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($usuario['username'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td><?= htmlspecialchars(trim($usuario['nombre'] . ' ' . $usuario['apellido']), ENT_QUOTES, 'UTF-8') ?><br>
                            <small class="text-muted"><?= htmlspecialchars($usuario['correo'] ?? '', ENT_QUOTES, 'UTF-8') ?></small></td>
                        <td><?= htmlspecialchars($usuario['rol'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="badge <?= $usuario['activo'] ? 'bg-success' : 'bg-secondary' ?>"><?= $usuario['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
                        <td>
                            <form action="index.php?action=cambiar_password_usuario" method="POST" class="row g-2">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="usuario_id" value="<?= (int)$usuario['id'] ?>">
                                <div class="col-lg-5"><input class="form-control form-control-sm" type="password" name="password" minlength="12" placeholder="12 caracteres mínimo" autocomplete="new-password" required></div>
                                <div class="col-lg-5"><input class="form-control form-control-sm" type="password" name="password_confirm" minlength="12" placeholder="Confirmar contraseña" autocomplete="new-password" required></div>
                                <div class="col-lg-2"><button class="btn btn-sm btn-primary w-100" type="submit">Actualizar</button></div>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$usuarios): ?><tr><td colspan="5" class="text-center text-muted py-4">No hay usuarios registrados.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
