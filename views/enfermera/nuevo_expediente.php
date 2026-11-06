<?php
require_once __DIR__ . '/../layouts/header.php';
$csrfToken = Security::generateCSRFToken();
$formData = $_SESSION['form_data'] ?? [];
?>

<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-person-plus text-primary me-2"></i>Crear expediente nuevo</h2>
            <p class="text-muted mb-0">Registra los datos del paciente para crear su expediente clínico.</p>
        </div>
        <a href="index.php?action=dashboard" class="btn btn-outline-secondary">Volver al panel</a>
    </div>

    <?php if (($_GET['success'] ?? '') === 'paciente_registrado'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Expediente creado correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-xl-8 col-xxl-7">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white fw-bold">Datos del paciente</div>
                <div class="card-body">
                    <form action="index.php?action=registrar_paciente" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nombres *</label>
                                <input type="text" name="nombres" class="form-control" maxlength="100" value="<?= htmlspecialchars($formData['nombres'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Apellidos *</label>
                                <input type="text" name="apellidos" class="form-control" maxlength="100" value="<?= htmlspecialchars($formData['apellidos'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Fecha de nacimiento *</label>
                                <input type="date" name="fecha_nacimiento" class="form-control" value="<?= htmlspecialchars($formData['fecha_nacimiento'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Sexo *</label>
                                <select name="sexo" class="form-select" required>
                                    <option value="Masculino">Masculino</option>
                                    <option value="Femenino">Femenino</option>
                                    <option value="Otros">Otros</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">DUI</label>
                                <input type="text" name="dui" class="form-control" maxlength="10" placeholder="00000000-0" value="<?= htmlspecialchars($formData['dui'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Teléfono *</label>
                                <input type="tel" name="telefono" class="form-control" maxlength="20" placeholder="7000-0000" value="<?= htmlspecialchars($formData['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Dirección</label>
                                <textarea name="direccion" class="form-control" rows="2"><?= htmlspecialchars($formData['direccion'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                        </div>

                        <hr>
                        <h6 class="fw-bold text-secondary mb-3"><i class="bi bi-telephone-plus me-1"></i>Contacto de emergencia</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-7">
                                <label class="form-label">Nombre del contacto</label>
                                <input type="text" name="contacto_emergencia_nombre" class="form-control" maxlength="150" value="<?= htmlspecialchars($formData['contacto_emergencia_nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Teléfono del contacto</label>
                                <input type="tel" name="contacto_emergencia_telefono" class="form-control" maxlength="20" value="<?= htmlspecialchars($formData['contacto_emergencia_telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success w-100 fw-bold">Guardar expediente</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php unset($_SESSION['form_data']); ?>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
