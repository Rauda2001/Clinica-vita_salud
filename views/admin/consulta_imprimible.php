<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Resumen clínico - <?= htmlspecialchars($consulta['expediente'], ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { max-width: 900px; margin: 2rem auto; color: #212529; }
        @media print { .no-print { display: none !important; } body { margin: 0; max-width: none; } }
    </style>
</head>
<body>
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h1 class="h4 mb-0">Resumen de consulta</h1>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="index.php?action=dashboard">Volver a cola</a>
        <button class="btn btn-primary" onclick="window.print()">Imprimir / Guardar PDF</button>
    </div>
</div>
<header class="border-bottom pb-3 mb-4">
    <h1 class="h3">VitaSalud · Resumen de consulta médica</h1>
    <div>Fecha: <?= htmlspecialchars(date('d/m/Y', strtotime($consulta['fecha'])), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars(substr($consulta['hora'], 0, 5), ENT_QUOTES, 'UTF-8') ?></div>
    <div>Expediente: <?= htmlspecialchars($consulta['expediente'], ENT_QUOTES, 'UTF-8') ?></div>
    <div>Paciente: <?= htmlspecialchars($consulta['nombres'] . ' ' . $consulta['apellidos'], ENT_QUOTES, 'UTF-8') ?></div>
    <div>Médico: <?= htmlspecialchars($consulta['medico_nombre'] . ' ' . $consulta['medico_apellido'], ENT_QUOTES, 'UTF-8') ?></div>
</header>
<section class="mb-4">
    <h2 class="h5">Motivo y observaciones</h2>
    <p><?= nl2br(htmlspecialchars($consulta['observaciones'] ?: 'No registrado', ENT_QUOTES, 'UTF-8')) ?></p>
    <h2 class="h5">Diagnóstico</h2>
    <p><?= nl2br(htmlspecialchars($consulta['diagnostico'], ENT_QUOTES, 'UTF-8')) ?></p>
    <h2 class="h5">Tratamiento</h2>
    <p><?= nl2br(htmlspecialchars($consulta['tratamiento'] ?: 'No registrado', ENT_QUOTES, 'UTF-8')) ?></p>
</section>
<section class="mb-4">
    <h2 class="h5">Signos vitales</h2>
    <table class="table table-bordered table-sm">
        <tbody>
        <tr><th>Presión arterial</th><td><?= htmlspecialchars($signos['presion_arterial'] ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></td><th>Temperatura</th><td><?= htmlspecialchars($signos['temperatura_c'] ?? 'N/D', ENT_QUOTES, 'UTF-8') ?><?= isset($signos['temperatura_c']) ? ' °C' : '' ?></td></tr>
        <tr><th>Peso</th><td><?= htmlspecialchars($signos['peso_kg'] ?? 'N/D', ENT_QUOTES, 'UTF-8') ?><?= isset($signos['peso_kg']) ? ' kg' : '' ?></td><th>Estatura</th><td><?= htmlspecialchars($signos['estatura_cm'] ?? 'N/D', ENT_QUOTES, 'UTF-8') ?><?= isset($signos['estatura_cm']) ? ' cm' : '' ?></td></tr>
        <tr><th>Frecuencia cardiaca</th><td><?= htmlspecialchars($signos['frecuencia_cardiaca'] ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></td><th>Frecuencia respiratoria</th><td><?= htmlspecialchars($signos['frecuencia_respiratoria'] ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></td></tr>
        </tbody>
    </table>
</section>
<section class="mb-5">
    <h2 class="h5">Receta</h2>
    <?php if ($recetas): ?><ul><?php foreach ($recetas as $receta): ?><li><?= nl2br(htmlspecialchars($receta['medicamento'], ENT_QUOTES, 'UTF-8')) ?></li><?php endforeach; ?></ul><?php else: ?><p>No hay medicamentos indicados.</p><?php endif; ?>
    <?php if ($ordenes): ?><h3 class="h6 mt-3">Productos para aplicación en clínica</h3><ul><?php foreach ($ordenes as $orden): ?><li><?= htmlspecialchars($orden['nombre'] . ' — ' . $orden['cantidad'] . ' ' . $orden['unidad_medida'] . ' — ' . $orden['estado'], ENT_QUOTES, 'UTF-8') ?><?= $orden['indicaciones'] ? ' · ' . htmlspecialchars($orden['indicaciones'], ENT_QUOTES, 'UTF-8') : '' ?></li><?php endforeach; ?></ul><?php endif; ?>
</section>
<footer class="border-top pt-3 small text-muted">Documento generado desde VitaSalud. La firma y validación clínica corresponden al médico tratante.</footer>
</body>
</html>
