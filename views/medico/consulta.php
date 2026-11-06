<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php $csrfToken = Security::generateCSRFToken(); ?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fw-bold text-primary mb-0"><i class="bi bi-file-earmark-medical me-2"></i>Consulta médica</h3>
        <a href="index.php?action=dashboard" class="btn btn-outline-secondary btn-sm">Regresar a la cola</a>
    </div>

    <div class="card border-info mb-4 shadow-sm">
        <div class="card-header bg-info text-white fw-bold d-flex justify-content-between align-items-center">
            <span><i class="bi bi-activity me-2"></i>Paciente y signos vitales</span>
            <a class="btn btn-sm btn-light" href="index.php?action=historial&paciente_id=<?= (int)$paciente['id'] ?>">
                <i class="bi bi-journal-medical me-1"></i>Historial completo
            </a>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6"><strong>Nombre:</strong> <?= htmlspecialchars($paciente['nombres'] . ' ' . $paciente['apellidos'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="col-md-6"><strong>Expediente:</strong> <?= htmlspecialchars($paciente['expediente'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <?php if (!$paciente['fecha_signos']): ?><div class="alert alert-warning py-2">Aún no se registran signos vitales para esta cita.</div><?php endif; ?>
            <div class="row text-center g-2">
                <div class="col"><small class="text-muted d-block">Presión</small><strong><?= htmlspecialchars($paciente['presion_arterial'] ?: 'N/D', ENT_QUOTES, 'UTF-8') ?></strong></div>
                <div class="col"><small class="text-muted d-block">Temperatura</small><strong><?= htmlspecialchars($paciente['temperatura_c'] ?: 'N/D', ENT_QUOTES, 'UTF-8') ?><?= $paciente['temperatura_c'] ? ' °C' : '' ?></strong></div>
                <div class="col"><small class="text-muted d-block">Peso</small><strong><?= htmlspecialchars($paciente['peso_kg'] ?: 'N/D', ENT_QUOTES, 'UTF-8') ?><?= $paciente['peso_kg'] ? ' kg' : '' ?></strong></div>
                <div class="col"><small class="text-muted d-block">Estatura</small><strong><?= htmlspecialchars($paciente['estatura_cm'] ?: 'N/D', ENT_QUOTES, 'UTF-8') ?><?= $paciente['estatura_cm'] ? ' cm' : '' ?></strong></div>
                <div class="col"><small class="text-muted d-block">FC</small><strong><?= htmlspecialchars($paciente['frecuencia_cardiaca'] ?: 'N/D', ENT_QUOTES, 'UTF-8') ?></strong></div>
                <div class="col"><small class="text-muted d-block">FR</small><strong><?= htmlspecialchars($paciente['frecuencia_respiratoria'] ?: 'N/D', ENT_QUOTES, 'UTF-8') ?></strong></div>
            </div>
            <?php if ($paciente['observaciones_vitales']): ?><div class="mt-3"><strong>Observaciones del triaje:</strong> <?= nl2br(htmlspecialchars($paciente['observaciones_vitales'], ENT_QUOTES, 'UTF-8')) ?></div><?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form action="index.php?action=guardar_consulta" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="cita_id" value="<?= (int)$paciente['cita_id'] ?>">
                <div class="mb-3">
                    <label class="form-label fw-bold">Motivo de consulta *</label>
                    <textarea name="motivo_consulta" class="form-control" rows="3" maxlength="4000" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Diagnóstico *</label>
                    <textarea name="diagnostico" class="form-control" rows="3" maxlength="4000" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Tratamiento y plan</label>
                    <textarea name="tratamiento" class="form-control" rows="3" maxlength="4000"></textarea>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">Receta externa / indicaciones farmacológicas</label>
                    <textarea name="receta" class="form-control" rows="2" maxlength="4000" placeholder="Medicamentos indicados que no se despachan del inventario de la clínica"></textarea>
                </div>

                <div class="card bg-light mb-4">
                    <div class="card-header fw-bold">Medicamentos, sueros o insumos para aplicar en clínica</div>
                    <div class="card-body">
                        <?php if ($inventario): ?>
                            <label for="buscar_producto_clinica" class="form-label">Buscar medicamento o insumo</label>
                            <input type="search" id="buscar_producto_clinica" class="form-control mb-3"
                                   placeholder="Escribe el nombre o la categoría..." autocomplete="off">
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-3">
                                    <thead><tr><th>Asignar</th><th>Producto</th><th>Disponible</th><th>Cantidad</th></tr></thead>
                                    <tbody id="productos_clinica">
                                    <?php foreach ($inventario as $producto): ?>
                                        <tr class="producto-clinica" data-busqueda="<?= htmlspecialchars($producto['nombre'] . ' ' . $producto['categoria'], ENT_QUOTES, 'UTF-8') ?>">
                                            <td><input class="form-check-input" type="checkbox" name="producto_id[]" value="<?= (int)$producto['id'] ?>" aria-label="Asignar <?= htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8') ?>"></td>
                                            <td><?= htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8') ?> <small class="text-muted">(<?= htmlspecialchars($producto['categoria'], ENT_QUOTES, 'UTF-8') ?>)</small></td>
                                            <td><?= htmlspecialchars($producto['stock'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($producto['unidad_medida'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><input class="form-control form-control-sm" style="max-width: 120px" type="number" name="cantidad[<?= (int)$producto['id'] ?>]" min="0.01" step="0.01" value="1"></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr id="sin_productos_coincidentes" hidden><td colspan="4" class="text-center text-muted">No hay medicamentos o insumos que coincidan con la búsqueda.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <label class="form-label">Indicaciones de aplicación</label>
                            <input type="text" name="indicaciones_med" class="form-control" maxlength="500" placeholder="Ej.: aplicar por vía indicada y registrar respuesta">
                            <small class="text-muted">El stock se descuenta cuando enfermería registra el despacho o aplicación.</small>
                        <?php else: ?>
                            <p class="text-muted mb-0">No hay productos activos con existencia disponible.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">Próxima cita (opcional)</label>
                    <input type="date" name="proxima_cita" class="form-control" min="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>">
                    <small class="text-muted">Enfermería asignará la hora y aparecerá como pendiente en su panel.</small>
                </div>
                <button type="submit" class="btn btn-success btn-lg w-100 fw-bold">
                    <i class="bi bi-check-circle-fill me-2"></i>Finalizar consulta e imprimir resumen
                </button>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const buscador = document.getElementById('buscar_producto_clinica');
    const filas = document.querySelectorAll('.producto-clinica');
    const filaSinCoincidencias = document.getElementById('sin_productos_coincidentes');

    if (!buscador || !filaSinCoincidencias) return;

    buscador.addEventListener('input', function() {
        const termino = this.value.trim().toLocaleLowerCase();
        let coincidencias = 0;

        filas.forEach(function(fila) {
            const texto = fila.dataset.busqueda.toLocaleLowerCase();
            const coincide = texto.includes(termino);
            fila.hidden = !coincide;
            if (coincide) coincidencias++;
        });

        filaSinCoincidencias.hidden = coincidencias > 0;
    });
});
</script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
