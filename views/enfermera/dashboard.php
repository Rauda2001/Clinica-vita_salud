<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid py-4">
    <div class="mb-4">
        <h2 class="mb-1">Panel de enfermería</h2>
        <p class="text-muted mb-0">Selecciona la tarea que deseas realizar.</p>
    </div>

    <div class="row g-4">
        <div class="col-md-6 col-xl-4">
            <a href="index.php?action=nuevo_expediente" class="card h-100 shadow-sm text-decoration-none">
                <div class="card-body">
                    <i class="bi bi-person-plus fs-2 text-primary"></i>
                    <h5 class="mt-3 text-dark">Crear expediente</h5>
                    <p class="text-muted mb-0">Registrar a un paciente nuevo en la clínica.</p>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-xl-4">
            <a href="index.php?action=triaje_cola" class="card h-100 shadow-sm text-decoration-none">
                <div class="card-body">
                    <i class="bi bi-activity fs-2 text-success"></i>
                    <h5 class="mt-3 text-dark">Triaje y cola de espera</h5>
                    <p class="text-muted mb-0">Buscar un paciente, registrar sus signos vitales y asignarlo a un médico.</p>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-xl-4">
            <a href="index.php?action=pendientes_enfermeria" class="card h-100 shadow-sm text-decoration-none">
                <div class="card-body">
                    <i class="bi bi-calendar2-check fs-2 text-warning"></i>
                    <h5 class="mt-3 text-dark">Citas y aplicaciones</h5>
                    <p class="text-muted mb-0">Asignar hora a seguimientos y revisar tratamientos por aplicar.</p>
                </div>
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
