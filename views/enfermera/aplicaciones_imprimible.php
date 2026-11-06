<?php
$nombrePaciente = $paciente['nombres'] . ' ' . $paciente['apellidos'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aplicaciones pendientes - <?= htmlspecialchars($paciente['expediente'], ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        body { max-width: 760px; margin: 24px auto; padding: 0 16px; color: #212529; font: 14px Arial, sans-serif; }
        h1 { margin: 0 0 6px; font-size: 21px; }
        h2 { margin: 24px 0 10px; font-size: 16px; }
        .muted { color: #59636e; }
        .toolbar { display: flex; justify-content: space-between; gap: 12px; margin-bottom: 20px; }
        button, .back { border: 1px solid #6c757d; border-radius: 4px; background: #fff; color: #212529; padding: 8px 12px; text-decoration: none; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #adb5bd; padding: 9px; text-align: left; vertical-align: top; }
        th { background: #f1f3f5; }
        .check { width: 28px; text-align: center; }
        .signature { display: flex; gap: 32px; margin-top: 40px; }
        .signature div { flex: 1; border-top: 1px solid #495057; padding-top: 7px; }
        @media print {
            body { max-width: none; margin: 0; padding: 0; font-size: 12px; }
            .no-print { display: none !important; }
            th, td { padding: 7px; }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <a class="back" href="index.php?action=pendientes_enfermeria">Volver a pendientes</a>
        <button type="button" onclick="window.print()">Imprimir / Guardar PDF</button>
    </div>

    <header>
        <h1>Hoja de aplicación de medicamentos e insumos</h1>
        <div class="muted">VitaSalud · Generada el <?= htmlspecialchars(date('d/m/Y H:i'), ENT_QUOTES, 'UTF-8') ?></div>
        <p><strong>Paciente:</strong> <?= htmlspecialchars($nombrePaciente, ENT_QUOTES, 'UTF-8') ?><br>
        <strong>Expediente:</strong> <?= htmlspecialchars($paciente['expediente'], ENT_QUOTES, 'UTF-8') ?></p>
    </header>

    <h2>Indicaciones pendientes</h2>
    <table>
        <thead>
            <tr>
                <th class="check">Aplicado</th>
                <th>Medicamento / insumo e indicaciones</th>
                <th>Cantidad</th>
                <th>Indicado por</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($ordenes as $orden): ?>
            <tr>
                <td class="check">□</td>
                <td>
                    <strong><?= htmlspecialchars($orden['producto'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <?php if ($orden['indicaciones']): ?>
                        <br><span class="muted"><?= htmlspecialchars($orden['indicaciones'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($orden['cantidad'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($orden['unidad_medida'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($orden['medico'], ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="signature">
        <div>Nombre y firma de enfermería</div>
        <div>Fecha y hora de aplicación</div>
    </div>
    <p class="muted">Esta hoja es una referencia de las indicaciones pendientes. Registrar la aplicación en el sistema una vez realizada.</p>
</body>
</html>
