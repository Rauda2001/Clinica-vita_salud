<?php require_once __DIR__ . '/../views/layouts/header.php'; ?>

<h3 class="text-vitasalud-blue fw-bold mb-4"><i class="bi bi-bar-chart-line-fill"></i> Arqueo de Caja y Control de Atenciones</h3>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-vitasalud-blue text-white py-2 fw-bold">Pacientes Atendidos por Médico (Hoy)</div>
            <div class="card-body">
                <table class="table table-striped align-middle fs-7 text-center">
                    <thead>
                        <tr><th class="text-start">Médico</th><th>Atendidos Totales</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($atendidosHoy as $ah): ?>
                            <tr><td class="text-start fw-bold"><?= $ah['medico']; ?></td><td><span class="badge bg-primary px-3 py-1"><?= $ah['total']; ?></span></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-dark text-white py-2 fw-bold">Ingresos Totales Recaudados (Mes Actual)</div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <?php $totalMes = 0; foreach($ingresosMetodo as $im): $totalMes += $im['total']; ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><?= $im['metodo_pago'] === 'Efectivo' ? '💵 Efectivo' : ($im['metodo_pago'] === 'Tarjeta' ? '💳 Tarjeta' : '📱 Transferencia'); ?></span>
                            <strong class="text-dark">$<?= number_format($im['total'], 2); ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="alert alert-success mt-3 mb-0 d-flex justify-content-between align-items-center py-2">
                    <span class="fw-bold">Cierre Contable Neto:</span>
                    <h4 class="fw-bold text-success mb-0">$<?= number_format($totalMes, 2); ?></h4>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../views/layouts/footer.php'; ?>