<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-stethoscope text-primary me-2"></i>Panel de Consulta Médica</h2>
        <span class="badge bg-primary fs-6">Dr(a). <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Médico') ?></span>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white fw-bold">
            <i class="bi bi-people-fill me-2"></i> Pacientes Asignados en Cola de Espera
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Expediente</th>
                            <th>Paciente</th>
                            <th>Estado</th>
                            <th class="text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pacientesEnEspera)): ?>
                            <?php foreach ($pacientesEnEspera as $p): ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($p['expediente']) ?></span></td>
                                    <td><strong><?= htmlspecialchars($p['nombres'] . ' ' . $p['apellidos']) ?></strong></td>
                                    <td><span class="badge bg-warning text-dark"><?= htmlspecialchars($p['estado']) ?></span></td>
                                    <td class="text-center">
                                        <a href="index.php?action=atender&cita_id=<?= $p['id'] ?>" class="btn btn-success btn-sm fw-bold">
                                            <i class="bi bi-box-arrow-in-right me-1"></i> Llamar / Atender Paciente
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <i class="bi bi-check-circle fs-3 text-success d-block mb-2"></i>
                                    No tienes pacientes asignados pendientes en la cola de espera.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>