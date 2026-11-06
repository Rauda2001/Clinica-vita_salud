<?php
require_once __DIR__ . '/../layouts/header.php';
$csrfToken = Security::generateCSRFToken();
$medicos = $medicos ?? [];
$colaEspera = $colaEspera ?? [];
?>

<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-activity text-primary me-2"></i>Triaje y cola de espera</h2>
            <p class="text-muted mb-0">Busca al paciente, registra sus signos vitales y asígnalo a un médico.</p>
        </div>
        <a href="index.php?action=dashboard" class="btn btn-outline-secondary">Volver al panel</a>
    </div>

    <?php if (($_GET['success'] ?? '') === 'triaje_guardado'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Paciente ingresado a la cola de espera correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-xl-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="fw-bold text-primary mb-3"><i class="bi bi-search me-1"></i>Buscar paciente</h5>
                    <div class="position-relative">
                        <input type="text" id="buscar_paciente" class="form-control form-control-lg" placeholder="Nombre, DUI o expediente..." autocomplete="off">
                        <div id="resultados_busqueda" class="list-group position-absolute w-100 shadow-lg mt-1" style="z-index: 1050; display: none; max-height: 280px; overflow-y: auto;"></div>
                    </div>
                    <small class="text-muted mt-2 d-block">Selecciona un paciente para completar el triaje y agregarlo a la cola.</small>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <strong><i class="bi bi-people-fill me-2"></i>Cola de espera activa</strong>
                    <span class="badge bg-danger">En vivo</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Paciente / Expediente</th><th>Médico</th><th>Hora</th><th>Estado</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($colaEspera as $item): ?>
                            <tr>
                                <td><strong class="d-block"><?= htmlspecialchars($item['paciente_nombre'], ENT_QUOTES, 'UTF-8') ?></strong><small class="text-muted"><?= htmlspecialchars($item['expediente'], ENT_QUOTES, 'UTF-8') ?></small></td>
                                <td><?= htmlspecialchars($item['medico_nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($item['hora_entrada'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge bg-warning text-dark"><?= htmlspecialchars($item['estado'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$colaEspera): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4"><i class="bi bi-inbox fs-3 d-block mb-1"></i>No hay pacientes en la cola en este momento.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTriaje" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-activity me-2"></i>Toma de signos vitales y triaje</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="bg-light p-3 rounded mb-3 border">
                    <h5 class="fw-bold text-primary mb-1" id="triaje_paciente_nombre">Paciente</h5>
                    <small class="text-muted" id="triaje_paciente_expediente">Expediente: -</small>
                </div>

                <form action="index.php?action=guardar_triaje" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="paciente_id" id="triaje_paciente_id">
                    <h6 class="fw-bold text-secondary mb-2">Signos vitales</h6>
                    <div class="row g-2 mb-3">
                        <div class="col-md-3"><label class="form-label small">Presión arterial</label><input type="text" name="presion_arterial" class="form-control" placeholder="120/80"></div>
                        <div class="col-md-3"><label class="form-label small">Temperatura (°C)</label><input type="number" step="0.1" name="temperatura" class="form-control" placeholder="36.5"></div>
                        <div class="col-md-3"><label class="form-label small">FC (bpm)</label><input type="number" name="frecuencia_cardiaca" class="form-control" placeholder="80"></div>
                        <div class="col-md-3"><label class="form-label small">FR (rpm)</label><input type="number" name="frecuencia_respiratoria" class="form-control" placeholder="18"></div>
                        <div class="col-md-6"><label class="form-label small">Peso (kg)</label><input type="number" step="0.01" name="peso" class="form-control" placeholder="70.50"></div>
                        <div class="col-md-6"><label class="form-label small">Estatura (cm)</label><input type="number" name="talla" class="form-control" placeholder="170"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Asignar a médico *</label>
                        <select name="medico_id" class="form-select" required>
                            <option value="">-- Seleccionar médico --</option>
                            <?php foreach ($medicos as $medico): ?>
                                <option value="<?= (int)$medico['medico_id'] ?>">Dr(a). <?= htmlspecialchars($medico['nombre_completo'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($medico['especialidad'], ENT_QUOTES, 'UTF-8') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success fw-bold">Ingresar a cola de espera</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputBuscar = document.getElementById('buscar_paciente');
    const resultados = document.getElementById('resultados_busqueda');
    let temporizador;
    let solicitud;

    inputBuscar.addEventListener('input', function() {
        const query = this.value.trim();
        window.clearTimeout(temporizador);
        if (solicitud) solicitud.abort();
        if (query.length < 2) {
            resultados.innerHTML = query ? '<div class="list-group-item text-muted">Escribe al menos 2 caracteres.</div>' : '';
            resultados.style.display = query ? 'block' : 'none';
            return;
        }

        temporizador = window.setTimeout(function() {
            solicitud = new AbortController();
            fetch('index.php?action=buscar_paciente_ajax&q=' + encodeURIComponent(query), {
                headers: { 'Accept': 'application/json' },
                signal: solicitud.signal
            })
            .then(function(response) {
                if (!response.ok) throw new Error('No fue posible completar la búsqueda.');
                return response.json();
            })
            .then(function(pacientes) {
                resultados.innerHTML = '';
                if (!Array.isArray(pacientes) || pacientes.length === 0) {
                    resultados.innerHTML = '<div class="list-group-item text-muted">No se encontraron pacientes.</div>';
                    resultados.style.display = 'block';
                    return;
                }
                pacientes.forEach(function(paciente) {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'list-group-item list-group-item-action';
                    item.textContent = paciente.nombres + ' ' + paciente.apellidos + ' · ' + paciente.expediente;
                    item.addEventListener('click', function() {
                        document.getElementById('triaje_paciente_id').value = paciente.id;
                        document.getElementById('triaje_paciente_nombre').textContent = paciente.nombres + ' ' + paciente.apellidos;
                        document.getElementById('triaje_paciente_expediente').textContent = 'Expediente: ' + paciente.expediente + (paciente.dui ? ' | DUI: ' + paciente.dui : '');
                        resultados.style.display = 'none';
                        inputBuscar.value = '';
                        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalTriaje')).show();
                    });
                    resultados.appendChild(item);
                });
                resultados.style.display = 'block';
            })
            .catch(function(error) {
                if (error.name !== 'AbortError') {
                    resultados.innerHTML = '<div class="list-group-item text-danger">Error al buscar pacientes. Intenta de nuevo.</div>';
                    resultados.style.display = 'block';
                    console.error('Error al buscar pacientes:', error);
                }
            });
        }, 250);
    });

    document.addEventListener('click', function(event) {
        if (!inputBuscar.contains(event.target) && !resultados.contains(event.target)) {
            resultados.style.display = 'none';
        }
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
