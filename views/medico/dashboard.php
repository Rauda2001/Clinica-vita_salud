<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="card shadow-sm border-0 mb-4 p-3 bg-vitasalud-blue text-white">
    <h4 class="mb-0 fw-bold">Estación de Consulta Médica</h4>
    <p class="mb-0 opacity-75 small">A continuación se despliegan los pacientes en sala de espera asignados a su persona por orden estricto de llegada.</p>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white fw-bold text-dark"><i class="bi bi-play-circle-fill text-success"></i> Pacientes Listos Para Pasar</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center fs-7">
                <thead class="table-light">
                    <tr>
                        <th>Expediente</th>
                        <th>Nombre del Paciente</th>
                        <th>Hora de Registro</th>
                        <th>Prioridad</th>
                        <th>Acción Clinica</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($agenda)): ?>
                        <tr><td colspan="5" class="py-4 text-muted">Excelente médico, no tiene pacientes pendientes en su cola el día de hoy.</td></tr>
                    <?php else: ?>
                        <?php foreach($agenda as $a): ?>
                        <tr>
                            <td><span class="badge bg-secondary"><?= $a['expediente']; ?></span></td>
                            <td class="text-start fw-bold"><?= $a['paciente']; ?></td>
                            <td><?= date("g:i A", strtotime($a['hora'])); ?></td>
                            <td>
                                <span class="badge <?= $a['tipo_ingreso'] === 'Citado' ? 'bg-info' : 'bg-warning text-dark'; ?>">
                                    <?= $a['tipo_ingreso']; ?>
                                </span>
                            </td>
                            <td>
                                <a href="index.php?action=atender_paciente&id=<?= $a['id']; ?>" class="btn btn-sm btn-success bg-vitasalud-green border-0 px-3 fw-bold shadow-sm"><i class="bi bi-file-earmark-medical"></i> Llamar y Atender</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>