<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-journal-medical text-primary me-2"></i>Historial clínico</h2>
            <div class="text-muted">
                <?= htmlspecialchars($paciente['nombres'] . ' ' . $paciente['apellidos'], ENT_QUOTES, 'UTF-8') ?>
                · Expediente <?= htmlspecialchars($paciente['expediente'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>
        <button class="btn btn-outline-secondary" onclick="history.back()">Regresar</button>
    </div>
    <?php foreach ($consultas as $consulta): ?>
        <article class="card shadow-sm mb-3">
            <div class="card-header d-flex flex-wrap justify-content-between gap-2">
                <strong>Consulta <?= htmlspecialchars(date('d/m/Y H:i', strtotime($consulta['fecha_consulta'])), ENT_QUOTES, 'UTF-8') ?></strong>
                <span class="text-muted">Médico: <?= htmlspecialchars($consulta['medico'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="card-body">
                <?php if ($consulta['observaciones']): ?><p><strong>Motivo / observaciones:</strong> <?= nl2br(htmlspecialchars($consulta['observaciones'], ENT_QUOTES, 'UTF-8')) ?></p><?php endif; ?>
                <p><strong>Diagnóstico:</strong><br><?= nl2br(htmlspecialchars($consulta['diagnostico'], ENT_QUOTES, 'UTF-8')) ?></p>
                <p><strong>Tratamiento:</strong><br><?= nl2br(htmlspecialchars($consulta['tratamiento'], ENT_QUOTES, 'UTF-8')) ?></p>
                <h6 class="fw-bold">Signos vitales</h6>
                <div class="row g-2 small mb-3">
                    <div class="col-md-3">Presión: <?= htmlspecialchars($consulta['presion_arterial'] ?: 'N/D', ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="col-md-3">Temperatura: <?= htmlspecialchars($consulta['temperatura_c'] ?: 'N/D', ENT_QUOTES, 'UTF-8') ?><?= $consulta['temperatura_c'] ? ' °C' : '' ?></div>
                    <div class="col-md-3">Peso: <?= htmlspecialchars($consulta['peso_kg'] ?: 'N/D', ENT_QUOTES, 'UTF-8') ?><?= $consulta['peso_kg'] ? ' kg' : '' ?></div>
                    <div class="col-md-3">Estatura: <?= htmlspecialchars($consulta['estatura_cm'] ?: 'N/D', ENT_QUOTES, 'UTF-8') ?><?= $consulta['estatura_cm'] ? ' cm' : '' ?></div>
                    <div class="col-md-3">Pulso: <?= htmlspecialchars($consulta['frecuencia_cardiaca'] ?: 'N/D', ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="col-md-3">Respiración: <?= htmlspecialchars($consulta['frecuencia_respiratoria'] ?: 'N/D', ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <h6 class="fw-bold">Recetas</h6>
                <?php if (!empty($recetas[$consulta['id']])): ?>
                    <ul><?php foreach ($recetas[$consulta['id']] as $receta): ?><li><?= nl2br(htmlspecialchars($receta, ENT_QUOTES, 'UTF-8')) ?></li><?php endforeach; ?></ul>
                <?php else: ?><p class="text-muted">Sin recetas registradas.</p><?php endif; ?>
                <?php if (!empty($ordenes[$consulta['id']])): ?>
                    <h6 class="fw-bold">Aplicaciones / productos asignados</h6>
                    <ul><?php foreach ($ordenes[$consulta['id']] as $orden): ?>
                        <li><?= htmlspecialchars($orden['nombre'] . ' — ' . $orden['cantidad'] . ' ' . $orden['unidad_medida'] . ' (' . $orden['estado'] . ')', ENT_QUOTES, 'UTF-8') ?>
                            <?= $orden['indicaciones'] ? ' · ' . htmlspecialchars($orden['indicaciones'], ENT_QUOTES, 'UTF-8') : '' ?></li>
                    <?php endforeach; ?></ul>
                <?php endif; ?>
                <a class="btn btn-sm btn-outline-primary" href="index.php?action=imprimir_consulta&cita_id=<?= (int)$consulta['cita_id'] ?>" target="_blank" rel="noopener">Imprimir resumen</a>
            </div>
        </article>
    <?php endforeach; ?>
    <?php if (!$consultas): ?><div class="alert alert-info">Este paciente todavía no tiene consultas registradas.</div><?php endif; ?>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
