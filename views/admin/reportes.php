<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="bi bi-bar-chart-line-fill text-primary me-2"></i>Panel administrativo</h2>
        <a href="index.php?action=dashboard" class="btn btn-outline-secondary">Panel principal</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100"><div class="card-body">
                <div class="text-muted">Pacientes atendidos hoy</div>
                <div class="display-5 fw-bold text-primary"><?= (int)$atendidosTotal ?></div>
            </div></div>
        </div>
        <div class="col-md-8">
            <div class="card shadow-sm border-0 h-100"><div class="card-header fw-bold">Consultas por médico · hoy</div>
                <div class="card-body p-0"><div class="table-responsive">
                    <table class="table table-striped mb-0"><thead><tr><th>Médico</th><th class="text-end">Consultas</th></tr></thead><tbody>
                    <?php foreach ($atendidosHoy as $fila): ?><tr><td><?= htmlspecialchars($fila['medico'], ENT_QUOTES, 'UTF-8') ?></td><td class="text-end"><?= (int)$fila['total'] ?></td></tr><?php endforeach; ?>
                    <?php if (!$atendidosHoy): ?><tr><td colspan="2" class="text-center text-muted">No hay médicos activos.</td></tr><?php endif; ?>
                    </tbody></table>
                </div></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header fw-bold text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Productos con stock bajo (<?= count($productosBajos) ?>)</div>
                <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Producto</th><th>Stock</th><th>Mínimo</th></tr></thead><tbody>
                    <?php foreach ($productosBajos as $producto): ?>
                        <tr><td><?= htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8') ?><small class="d-block text-muted"><?= htmlspecialchars($producto['categoria'], ENT_QUOTES, 'UTF-8') ?></small></td>
                            <td class="text-danger fw-bold"><?= htmlspecialchars($producto['stock'] . ' ' . $producto['unidad_medida'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($producto['stock_minimo'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$productosBajos): ?><tr><td colspan="3" class="text-center text-muted py-3">No hay alertas de stock.</td></tr><?php endif; ?>
                    </tbody></table></div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header fw-bold">Expedientes recientes · historial clínico</div>
                <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Expediente</th><th>Paciente</th><th></th></tr></thead><tbody>
                    <?php foreach ($pacientesRecientes as $paciente): ?>
                        <tr><td><?= htmlspecialchars($paciente['expediente'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($paciente['nombres'] . ' ' . $paciente['apellidos'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="index.php?action=historial&paciente_id=<?= (int)$paciente['id'] ?>">Ver historial</a></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$pacientesRecientes): ?><tr><td colspan="3" class="text-center text-muted">No hay pacientes registrados.</td></tr><?php endif; ?>
                    </tbody></table></div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
