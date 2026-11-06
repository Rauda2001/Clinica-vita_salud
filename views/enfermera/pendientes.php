<?php
require_once __DIR__ . '/../layouts/header.php';
$csrfToken = Security::generateCSRFToken();
$ordenesPorPaciente = [];
foreach ($ordenesPendientes as $orden) {
    $ordenesPorPaciente[$orden['paciente_id']][] = $orden;
}
?>

<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-calendar2-check text-primary me-2"></i>Citas y aplicaciones pendientes</h2>
            <p class="text-muted mb-0">Asigna hora a las citas de seguimiento y registra las aplicaciones indicadas por el médico.</p>
        </div>
        <a href="index.php?action=dashboard" class="btn btn-outline-secondary">Volver al panel</a>
    </div>

    <?php if (($_GET['success'] ?? '') === 'hora_cita_guardada'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Hora de la cita actualizada correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif (($_GET['success'] ?? '') === 'despacho_realizado'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Aplicación registrada y existencia actualizada.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-info-subtle">
                    <strong><i class="bi bi-calendar2-week me-2"></i>Citas pendientes de atención (<?= count($citasPendientes) ?>)</strong>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Paciente</th><th>Fecha</th><th>Hora asignada</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($citasPendientes as $cita): ?>
                            <?php $horaCita = substr($cita['hora'], 0, 5); ?>
                            <tr>
                                <td>
                                    <strong class="d-block"><?= htmlspecialchars($cita['paciente_nombre'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small class="text-muted"><?= htmlspecialchars($cita['expediente'], ENT_QUOTES, 'UTF-8') ?> · Dr(a). <?= htmlspecialchars($cita['medico_nombre'], ENT_QUOTES, 'UTF-8') ?></small>
                                    <?php if ($cita['motivo']): ?><small class="d-block text-muted"><?= htmlspecialchars($cita['motivo'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($cita['fecha'])), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <form action="index.php?action=programar_hora_cita" method="POST" class="d-flex gap-2">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="cita_id" value="<?= (int)$cita['id'] ?>">
                                        <input type="hidden" name="return_to" value="pendientes_enfermeria">
                                        <input type="time" name="hora" class="form-control form-control-sm" value="<?= $horaCita !== '00:00' ? htmlspecialchars($horaCita, ENT_QUOTES, 'UTF-8') : '' ?>" required>
                                        <button type="submit" class="btn btn-sm btn-primary">Guardar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$citasPendientes): ?>
                            <tr><td colspan="3" class="text-center text-muted py-4">No hay citas pendientes de atención.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-warning-subtle d-flex justify-content-between align-items-center">
                    <strong><i class="bi bi-clipboard2-pulse me-2"></i>Medicamentos e insumos por aplicar (<?= count($ordenesPendientes) ?>)</strong>
                    <a href="index.php?action=inventario" class="btn btn-sm btn-outline-primary">Ver inventario</a>
                </div>
                <div class="accordion accordion-flush">
                    <?php foreach ($ordenesPorPaciente as $pacienteId => $ordenesPaciente): ?>
                        <?php
                        $primeraOrden = $ordenesPaciente[0];
                        $encabezadoId = 'paciente-aplicaciones-' . (int)$pacienteId;
                        $contenidoId = 'detalle-aplicaciones-' . (int)$pacienteId;
                        ?>
                        <div class="accordion-item">
                            <h3 class="accordion-header" id="<?= $encabezadoId ?>">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#<?= $contenidoId ?>" aria-expanded="false"
                                        aria-controls="<?= $contenidoId ?>">
                                    <span>
                                        <strong><?= htmlspecialchars($primeraOrden['nombres'] . ' ' . $primeraOrden['apellidos'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        <small class="d-block text-muted">
                                            <?= htmlspecialchars($primeraOrden['expediente'], ENT_QUOTES, 'UTF-8') ?>
                                            · <?= count($ordenesPaciente) ?> <?= count($ordenesPaciente) === 1 ? 'aplicación pendiente' : 'aplicaciones pendientes' ?>
                                        </small>
                                    </span>
                                </button>
                            </h3>
                            <div id="<?= $contenidoId ?>" class="accordion-collapse collapse"
                                 aria-labelledby="<?= $encabezadoId ?>">
                                <div class="accordion-body p-0">
                                    <div class="d-flex justify-content-end p-3 border-bottom">
                                        <a class="btn btn-sm btn-outline-primary"
                                           href="index.php?action=imprimir_aplicaciones_pendientes&amp;paciente_id=<?= (int)$pacienteId ?>"
                                           target="_blank" rel="noopener">
                                            <i class="bi bi-printer me-1"></i>Imprimir hoja de aplicación
                                        </a>
                                    </div>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($ordenesPaciente as $orden): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                                    <div>
                                                        <strong><?= htmlspecialchars($orden['producto'], ENT_QUOTES, 'UTF-8') ?></strong>
                                                        <small class="d-block text-muted">Dr(a). <?= htmlspecialchars($orden['medico'], ENT_QUOTES, 'UTF-8') ?></small>
                                                        <div class="small">Cantidad: <?= htmlspecialchars($orden['cantidad'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($orden['unidad_medida'], ENT_QUOTES, 'UTF-8') ?></div>
                                                        <?php if ($orden['indicaciones']): ?><div class="small">Indicaciones: <?= htmlspecialchars($orden['indicaciones'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                                                    </div>
                                                    <form action="index.php?action=despachar_producto" method="POST">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                        <input type="hidden" name="orden_id" value="<?= (int)$orden['id'] ?>">
                                                        <input type="hidden" name="return_to" value="pendientes_enfermeria">
                                                        <button type="submit" class="btn btn-sm btn-success">Registrar aplicación</button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$ordenesPendientes): ?>
                        <div class="p-4 text-center text-muted">No hay pacientes con medicamentos o insumos pendientes de aplicación.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
