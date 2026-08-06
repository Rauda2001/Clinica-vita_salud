<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="card bg-vitasalud-blue text-white border-0 shadow-sm mb-4">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
            <div>
                <span class="badge bg-white text-primary fw-bold mb-1">EXPEDIENTE DIGITAL: <?= $paciente['expediente']; ?></span>
                <h3 class="mb-0 fw-bold"><?= $paciente['nombres'] . " " . $paciente['apellidos']; ?></h3>
                <small class="opacity-75">Sexo: <?= $paciente['sexo']; ?> | Fecha de Nacimiento: <?= date('d/m/Y', strtotime($paciente['fecha_nacimiento'])); ?></small>
            </div>
            <div>
                <span class="badge bg-success fs-6 px-3 py-2 border border-white border-opacity-25 shadow-sm"><i class="bi bi-shield-check"></i> Modo Consulta Médica Activa</span>
            </div>
        </div>
    </div>

    <form action="index.php?action=finalizar_consulta" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="cita_id" value="<?= $cita_actual['id']; ?>">
        <input type="hidden" name="paciente_id" value="<?= $paciente['id']; ?>">

        <div class="row g-4">
            <div class="col-md-7">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-light fw-bold text-vitasalud-blue py-2"><i class="bi bi-pencil-square"></i> Registro de Sintomatología e Historial</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Razón de la Visita (Motivo principal)</label>
                            <textarea name="motivo_consulta" class="form-control" rows="2" required placeholder="Fiebre alta, dolor muscular, tos persistente..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Síntomas y Observaciones Adicionales</label>
                            <textarea name="sintomas" class="form-control" rows="3" required placeholder="Detalles observados durante la anamnesis..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Diagnóstico Clínico</label>
                            <textarea name="diagnostico" class="form-control" rows="2" required placeholder="Diagnóstico conclusivo de la evaluación..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Tratamiento y Plan de Cuidados</label>
                            <textarea name="tratamiento" class="form-control" rows="2" required placeholder="Indicaciones terapéuticas..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 border-top border-success border-3">
                    <div class="card-header bg-white text-success fw-bold py-2"><i class="bi bi-capsule"></i> Prescripción Médica (Receta)</script></div>
                    <div class="card-body">
                        <textarea name="receta_medicamentos" class="form-control" rows="4" placeholder="Medicamento, Dosis, Frecuencia y Duración..."></textarea>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card shadow-sm border-0 border-start border-success border-4 mb-4">
                    <div class="card-body shadow-sm">
                        <h5 class="text-success fw-bold mb-3"><i class="bi bi-currency-dollar"></i> Cobro de la Consulta</h5>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Costo de Consulta ($)</label>
                            <input type="number" name="monto" class="form-control form-control-lg fw-bold text-success" step="0.01" min="0.00" placeholder="0.00" required>
                        </div>
                        <div class="mb-1">
                            <label class="form-label small fw-bold">Forma en que canceló el paciente</label>
                            <select name="metodo_pago" class="form-select" required>
                                <option value="Efectivo">💵 Efectivo</option>
                                <option value="Tarjeta">💳 Tarjeta</option>
                                <option value="Transferencia">📱 Transferencia Directa</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 border-start border-info border-4 mb-4">
                    <div class="card-body">
                        <h5 class="text-info fw-bold mb-1"><i class="bi bi-calendar-event"></i> Planificar Retorno</h5>
                        <div class="form-check form-switch my-3">
                            <input class="form-check-input" type="checkbox" id="requiere_regreso" name="requiere_regreso" value="1">
                            <label class="form-check-label small fw-bold" for="requiere_regreso">Citar de nuevo a este paciente</label>
                        </div>
                        <div id="campos_cita" class="d-none">
                            <div class="mb-2">
                                <label class="form-label small">Fecha Control</label>
                                <input type="date" name="fecha_proxima" class="form-control form-control-sm">
                            </div>
                            <div class="mb-1">
                                <label class="form-label small">Hora Asignada</label>
                                <input type="time" name="hora_proxima" class="form-control form-control-sm">
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-success bg-vitasalud-green w-100 border-0 btn-lg shadow fw-bold"><i class="bi bi-cloud-arrow-up-fill"></i> Concluir Consulta y Cobro</button>
            </div>
        </div>
    </form>
</div>

<script>
document.getElementById('requiere_regreso').addEventListener('change', function() {
    document.getElementById('campos_cita').classList.toggle('d-none', !this.checked);
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>