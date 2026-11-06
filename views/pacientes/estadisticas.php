<?php
require_once __DIR__ . '/../layouts/header.php';
$chartFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;
?>

<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-people text-primary me-2"></i>Pacientes y estadísticas</h2>
            <p class="text-muted mb-0">Expedientes registrados y resumen de pacientes atendidos.</p>
        </div>
        <a href="index.php?action=dashboard" class="btn btn-outline-secondary">Volver al panel</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm h-100"><div class="card-body">
                <div class="text-muted">Pacientes inscritos</div>
                <div class="display-6 fw-bold text-primary"><?= $totalPacientes ?></div>
            </div></div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm h-100"><div class="card-body">
                <div class="text-muted">Pacientes atendidos hoy</div>
                <div class="display-6 fw-bold text-success"><?= $atendidosHoy ?></div>
            </div></div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm h-100"><div class="card-body">
                <div class="text-muted">Pacientes atendidos este mes</div>
                <div class="display-6 fw-bold text-info"><?= $atendidosMes ?></div>
            </div></div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm h-100"><div class="card-body">
                <div class="text-muted">Más visitas este mes</div>
                <?php if ($pacienteMasFrecuente): ?>
                    <div class="fw-bold mt-2"><?= htmlspecialchars($pacienteMasFrecuente['nombres'] . ' ' . $pacienteMasFrecuente['apellidos'], ENT_QUOTES, 'UTF-8') ?></div>
                    <small class="text-muted"><?= htmlspecialchars($pacienteMasFrecuente['expediente'], ENT_QUOTES, 'UTF-8') ?> · <?= (int)$pacienteMasFrecuente['visitas'] ?> visitas atendidas</small>
                <?php else: ?>
                    <div class="text-muted mt-2">Aún no hay atenciones este mes.</div>
                <?php endif; ?>
            </div></div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-bold">Pacientes atendidos por día · <?= htmlspecialchars($inicioMes->format('m/Y'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="card-body"><div style="height: 300px"><canvas id="graficaDiaria" aria-label="Pacientes atendidos por día este mes"></canvas></div></div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-bold">Tendencia mensual · últimos 12 meses</div>
                <div class="card-body"><div style="height: 300px"><canvas id="graficaMensual" aria-label="Tendencia mensual de pacientes atendidos"></canvas></div></div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <strong>Todos los pacientes (<?= $totalCoincidencias ?>)</strong>
            <form method="GET" class="d-flex gap-2">
                <input type="hidden" name="action" value="pacientes_estadisticas">
                <input type="search" name="q" value="<?= htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8') ?>" class="form-control form-control-sm" maxlength="100" placeholder="Buscar nombre, DUI o expediente">
                <button class="btn btn-sm btn-primary" type="submit">Buscar</button>
                <?php if ($busqueda !== ''): ?><a class="btn btn-sm btn-outline-secondary" href="index.php?action=pacientes_estadisticas">Limpiar</a><?php endif; ?>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Expediente</th><th>Paciente</th><th>DUI</th><th>Teléfono</th><th>Atenciones</th><th>Última atención</th></tr>
                </thead>
                <tbody>
                <?php foreach ($pacientes as $paciente): ?>
                    <tr>
                        <td><?= htmlspecialchars($paciente['expediente'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($paciente['nombres'] . ' ' . $paciente['apellidos'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($paciente['dui'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($paciente['telefono'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int)$paciente['total_atenciones'] ?></td>
                        <td><?= $paciente['ultima_atencion'] ? htmlspecialchars(date('d/m/Y', strtotime($paciente['ultima_atencion'])), ENT_QUOTES, 'UTF-8') : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$pacientes): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No se encontraron pacientes.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPaginas > 1): ?>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <small class="text-muted">Página <?= $pagina ?> de <?= $totalPaginas ?></small>
                <div class="btn-group">
                    <?php if ($pagina > 1): ?>
                        <a class="btn btn-sm btn-outline-primary" href="index.php?action=pacientes_estadisticas&amp;pagina=<?= $pagina - 1 ?>&amp;q=<?= urlencode($busqueda) ?>">Anterior</a>
                    <?php endif; ?>
                    <?php if ($pagina < $totalPaginas): ?>
                        <a class="btn btn-sm btn-outline-primary" href="index.php?action=pacientes_estadisticas&amp;pagina=<?= $pagina + 1 ?>&amp;q=<?= urlencode($busqueda) ?>">Siguiente</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <p class="small text-muted mt-3 mb-0">Las gráficas cuentan pacientes únicos con cita en estado “Atendida”. El listado se puede buscar y recorrer por páginas.</p>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const etiquetasDias = <?= json_encode($etiquetasDias, $chartFlags) ?>;
const datosDias = <?= json_encode($datosDias, $chartFlags) ?>;
const etiquetasMeses = <?= json_encode($etiquetasMeses, $chartFlags) ?>;
const datosMeses = <?= json_encode($datosMeses, $chartFlags) ?>;

new Chart(document.getElementById('graficaDiaria'), {
    type: 'bar',
    data: {
        labels: etiquetasDias,
        datasets: [{
            label: 'Pacientes atendidos',
            data: datosDias,
            backgroundColor: 'rgba(13, 110, 253, 0.65)',
            borderColor: '#0d6efd',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});

new Chart(document.getElementById('graficaMensual'), {
    type: 'line',
    data: {
        labels: etiquetasMeses,
        datasets: [{
            label: 'Pacientes atendidos',
            data: datosMeses,
            borderColor: '#198754',
            backgroundColor: 'rgba(25, 135, 84, 0.15)',
            fill: true,
            tension: 0.25
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
