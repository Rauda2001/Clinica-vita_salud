<?php
// =========================================================================
// VITASALUD - VISTA DASHBOARD (views/enfermera/dashboard.php)
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$formData   = $_SESSION['form_data'] ?? [];
$medicos    = $medicos ?? [];
$colaEspera = $colaEspera ?? [];

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid py-4 notranslate" translate="no">

    <!-- ALERTAS -->
    <?php if (isset($_GET['error']) && $_GET['error'] === 'dui_duplicado'): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><strong>¡Atención!</strong> El DUI ingresado ya pertenece a otro expediente.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['success']) && $_GET['success'] === 'paciente_registrado'): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><strong>¡Éxito!</strong> Expediente creado correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['success']) && $_GET['success'] === 'triaje_guardado'): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><strong>¡Éxito!</strong> Paciente ingresado a la cola de espera correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        
        <!-- COLUMNA IZQUIERDA: BUSCADOR Y REGISTRO -->
        <div class="col-lg-5">

            <!-- BUSCADOR AJAX -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="fw-bold text-primary mb-3">
                        <i class="bi bi-search"></i> Admisión de Paciente
                    </h5>
                    
                    <div class="position-relative">
                        <input type="text" id="buscar_paciente" class="form-control form-control-lg" placeholder="Buscar por Nombre, DUI o Expediente..." autocomplete="off">
                        
                        <div id="resultados_busqueda" class="list-group position-absolute w-100 shadow-lg mt-1" style="z-index: 1050; display: none; max-height: 280px; overflow-y: auto;"></div>
                    </div>
                    
                    <small class="text-muted mt-2 d-block">Escribe para realizar la búsqueda en tiempo real...</small>
                </div>
            </div>

            <!-- REGISTRO EXPEDIENTE CON CAMPOS EXACTOS DE LA TABLA PACIENTES -->
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white fw-bold">
                    <i class="bi bi-person-plus-fill me-1"></i> Crear Nuevo Expediente Clínico
                </div>
                <div class="card-body">
                    <form action="index.php?action=registrar_paciente" method="POST">
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Nombres *</label>
                                <input type="text" name="nombres" class="form-control" value="<?= htmlspecialchars($formData['nombres'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Apellidos *</label>
                                <input type="text" name="apellidos" class="form-control" value="<?= htmlspecialchars($formData['apellidos'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Fecha Nacimiento *</label>
                                <input type="date" name="fecha_nacimiento" class="form-control" value="<?= htmlspecialchars($formData['fecha_nacimiento'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Sexo *</label>
                                <select name="sexo" class="form-select">
                                    <option value="Masculino">Masculino</option>
                                    <option value="Femenino">Femenino</option>
                                    <option value="Otros">Otros</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">DUI</label>
                                <input type="text" name="dui" class="form-control" placeholder="00000000-0" value="<?= htmlspecialchars($formData['dui'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Teléfono *</label>
                                <input type="text" name="telefono" class="form-control" placeholder="7000-0000" value="<?= htmlspecialchars($formData['telefono'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-bold">Dirección</label>
                            <textarea name="direccion" class="form-control" rows="2"><?= htmlspecialchars($formData['direccion'] ?? '') ?></textarea>
                        </div>

                        <hr class="my-3">
                        <span class="d-block small fw-bold text-secondary mb-2"><i class="bi bi-telephone-plus me-1"></i> Contacto de Emergencia</span>

                        <div class="row g-2 mb-3">
                            <div class="col-md-7">
                                <label class="form-label small">Nombre del Contacto</label>
                                <input type="text" name="contacto_emergencia_nombre" class="form-control" value="<?= htmlspecialchars($formData['contacto_emergencia_nombre'] ?? '') ?>">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small">Teléfono Contacto</label>
                                <input type="text" name="contacto_emergencia_telefono" class="form-control" value="<?= htmlspecialchars($formData['contacto_emergencia_telefono'] ?? '') ?>">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success w-100 fw-bold">Guardar Expediente</button>
                    </form>
                </div>
            </div>

        </div>

        <!-- COLUMNA DERECHA: COLA DE ESPERA Y TRIAJE ACTIVO -->
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><i class="bi bi-people-fill me-2"></i> Cola de Espera y Triaje Activo</span>
                    <span class="badge bg-danger">En Vivo</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Paciente / Expediente</th>
                                    <th>Médico Asignado</th>
                                    <th>Hora Entrada</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($colaEspera)): ?>
                                    <?php foreach ($colaEspera as $item): ?>
                                        <tr>
                                            <td>
                                                <strong class="d-block text-dark"><?= htmlspecialchars($item['paciente_nombre']) ?></strong>
                                                <small class="text-muted"><?= htmlspecialchars($item['expediente']) ?></small>
                                            </td>
                                            <td>Dr(a). <?= htmlspecialchars($item['medico_nombre']) ?></td>
                                            <td><span class="fw-bold text-secondary"><?= $item['hora_entrada'] ?></span></td>
                                            <td>
                                                <span class="badge bg-warning text-dark"><?= htmlspecialchars($item['estado']) ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                                            No hay pacientes esperando atención en este momento.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- MODAL DE TRIAJE Y SIGNOS VITALES -->
<div class="modal fade notranslate" translate="no" id="modalTriaje" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-activity me-2"></i> Toma de Signos Vitales y Triaje</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="bg-light p-3 rounded mb-3 border">
                    <h5 class="fw-bold text-primary mb-1" id="triaje_paciente_nombre">Cargando...</h5>
                    <small class="text-muted" id="triaje_paciente_expediente">Expediente: -</small>
                </div>

                <form id="formTriaje" action="index.php?action=guardar_triaje" method="POST">
                    <input type="hidden" name="paciente_id" id="triaje_paciente_id">

                    <h6 class="fw-bold text-secondary mb-2"><i class="bi bi-heart-pulse me-1"></i> Signos Vitales</h6>
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small">Presión Arterial</label>
                            <input type="text" name="presion_arterial" class="form-control" placeholder="120/80">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Temp. (°C)</label>
                            <input type="number" step="0.1" name="temperatura" class="form-control" placeholder="36.5">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">FC (bpm)</label>
                            <input type="number" name="frecuencia_cardiaca" class="form-control" placeholder="80">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">FR (rpm)</label>
                            <input type="number" name="frecuencia_respiratoria" class="form-control" placeholder="18">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small">Peso (kg)</label>
                            <input type="number" step="0.01" name="peso" class="form-control" placeholder="70.50">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Talla / Estatura (cm)</label>
                            <input type="number" name="talla" class="form-control" placeholder="170">
                        </div>
                    </div>

                    <h6 class="fw-bold text-secondary mb-2"><i class="bi bi-person-badge me-1"></i> Asignación de Consulta</h6>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Asignar a Médico *</label>
                        <select name="medico_id" class="form-select" required>
                            <option value="">-- Seleccionar Médico Disponible --</option>
                            <?php if (!empty($medicos)): ?>
                                <?php foreach ($medicos as $medico): ?>
                                    <option value="<?= $medico['medico_id'] ?>">
                                        Dr(a). <?= htmlspecialchars($medico['nombre_completo']) ?> (<?= htmlspecialchars($medico['especialidad']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="text-end mt-4">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success fw-bold">Ingresar a Cola de Espera</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputBuscar = document.getElementById('buscar_paciente');
    const contenedorResultados = document.getElementById('resultados_busqueda');

    if (inputBuscar && contenedorResultados) {
        inputBuscar.addEventListener('input', function() {
            const query = this.value.trim();

            if (query.length === 0) {
                contenedorResultados.style.display = 'none';
                contenedorResultados.innerHTML = '';
                return;
            }

            fetch('index.php?action=buscar_paciente_ajax&q=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    contenedorResultados.innerHTML = '';

                    if (Array.isArray(data) && data.length > 0) {
                        data.forEach(paciente => {
                            const item = document.createElement('a');
                            item.href = '#';
                            item.className = 'list-group-item list-group-item-action py-2';
                            item.innerHTML = `
                                <div class="d-flex w-100 justify-content-between">
                                    <strong class="text-primary">${paciente.nombres} ${paciente.apellidos}</strong>
                                    <small class="badge bg-secondary">${paciente.expediente}</small>
                                </div>
                                <small class="text-muted">DUI: ${paciente.dui ? paciente.dui : 'N/A'} | Tel: ${paciente.telefono ? paciente.telefono : 'N/A'}</small>
                            `;

                            item.addEventListener('click', function(e) {
                                e.preventDefault();
                                contenedorResultados.style.display = 'none';
                                inputBuscar.value = '';

                                document.getElementById('triaje_paciente_id').value = paciente.id;
                                document.getElementById('triaje_paciente_nombre').innerText = paciente.nombres + ' ' + paciente.apellidos;
                                document.getElementById('triaje_paciente_expediente').innerText = 'Expediente: ' + paciente.expediente + (paciente.dui ? ' | DUI: ' + paciente.dui : '');

                                const modal = new bootstrap.Modal(document.getElementById('modalTriaje'));
                                modal.show();
                            });

                            contenedorResultados.appendChild(item);
                        });
                        contenedorResultados.style.display = 'block';
                    } else {
                        contenedorResultados.innerHTML = '<div class="list-group-item text-muted p-2">No se encontraron pacientes</div>';
                        contenedorResultados.style.display = 'block';
                    }
                })
                .catch(err => console.error("Error al buscar:", err));
        });

        document.addEventListener('click', function(e) {
            if (!inputBuscar.contains(e.target) && !contenedorResultados.contains(e.target)) {
                contenedorResultados.style.display = 'none';
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>