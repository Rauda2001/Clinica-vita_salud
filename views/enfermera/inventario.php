<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php $csrfToken = Security::generateCSRFToken(); ?>

<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-capsule text-primary me-2"></i>Inventario y farmacia</h2>
            <p class="text-muted mb-0">Control de existencias y órdenes médicas pendientes.</p>
        </div>
        <a href="index.php?action=dashboard" class="btn btn-outline-secondary">Volver al panel</a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <?= $_GET['success'] === 'despacho_realizado' ? 'Despacho registrado y stock actualizado.' : 'Producto guardado correctamente.' ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Stock de medicamentos e insumos</strong>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#productoModal"
                            onclick="prepararProducto()"><i class="bi bi-plus-lg me-1"></i>Nuevo producto</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Producto</th><th>Categoría</th><th>Existencia</th><th>Mínimo</th><th></th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($productos as $producto): ?>
                            <?php $bajo = (float)$producto['stock'] <= (float)$producto['stock_minimo']; ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <?php if ($producto['descripcion']): ?><small class="d-block text-muted"><?= htmlspecialchars($producto['descripcion'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($producto['categoria'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge <?= $bajo ? 'bg-danger' : 'bg-success' ?>"><?= htmlspecialchars($producto['stock'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($producto['unidad_medida'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td><?= htmlspecialchars($producto['stock_minimo'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#productoModal"
                                            data-producto="<?= htmlspecialchars(json_encode($producto, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>"
                                            onclick="prepararProducto(this)">Editar</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$productos): ?><tr><td colspan="5" class="text-center text-muted py-4">Aún no hay productos en el inventario.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card shadow-sm">
                <div class="card-header bg-warning-subtle"><strong><i class="bi bi-clipboard2-pulse me-2"></i>Pendientes de despacho / aplicación (<?= count($pendientes) ?>)</strong></div>
                <div class="list-group list-group-flush">
                    <?php foreach ($pendientes as $orden): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between gap-2">
                                <div>
                                    <strong><?= htmlspecialchars($orden['producto'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <div><?= htmlspecialchars($orden['nombres'] . ' ' . $orden['apellidos'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($orden['expediente'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($orden['medico'], ENT_QUOTES, 'UTF-8') ?>
                                    </small>
                                    <div class="small mt-1">Cantidad: <?= htmlspecialchars($orden['cantidad'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($orden['unidad_medida'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php if ($orden['indicaciones']): ?><div class="small"><?= htmlspecialchars($orden['indicaciones'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                                </div>
                                <form action="index.php?action=despachar_producto" method="POST" class="align-self-center">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="orden_id" value="<?= (int)$orden['id'] ?>">
                                    <button class="btn btn-sm btn-success" type="submit">Despachar / aplicar</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$pendientes): ?><div class="p-4 text-center text-muted">No hay órdenes pendientes.</div><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="productoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" action="index.php?action=guardar_producto" method="POST">
            <div class="modal-header"><h5 class="modal-title">Producto de inventario</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id" id="producto_id">
                <label class="form-label">Nombre</label><input class="form-control mb-3" name="nombre" id="producto_nombre" maxlength="150" required>
                <label class="form-label">Descripción</label><textarea class="form-control mb-3" name="descripcion" id="producto_descripcion" rows="2"></textarea>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Categoría</label>
                        <select class="form-select" name="categoria" id="producto_categoria" required>
                            <option>Medicamento</option><option>Suero</option><option>Insumo</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3"><label class="form-label">Unidad</label><input class="form-control" name="unidad_medida" id="producto_unidad" value="unidad" maxlength="40" required></div>
                </div>
                <div class="row">
                    <div class="col-6"><label class="form-label">Stock actual</label><input class="form-control" type="number" name="stock" id="producto_stock" min="0" step="0.01" required></div>
                    <div class="col-6"><label class="form-label">Aviso de stock bajo</label><input class="form-control" type="number" name="stock_minimo" id="producto_stock_minimo" min="0" step="0.01" value="5" required></div>
                </div>
                <small class="text-muted d-block mt-2">El ajuste de stock es manual; los despachos descuentan stock automáticamente.</small>
            </div>
            <div class="modal-footer"><button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit">Guardar</button></div>
        </form>
    </div>
</div>
<script>
function prepararProducto(button) {
    const form = document.querySelector('#productoModal form');
    form.reset();
    document.getElementById('producto_id').value = '';
    document.getElementById('producto_unidad').value = 'unidad';
    document.getElementById('producto_stock_minimo').value = '5';
    if (!button || !button.dataset.producto) return;
    const producto = JSON.parse(button.dataset.producto);
    document.getElementById('producto_id').value = producto.id;
    document.getElementById('producto_nombre').value = producto.nombre;
    document.getElementById('producto_descripcion').value = producto.descripcion || '';
    document.getElementById('producto_categoria').value = producto.categoria;
    document.getElementById('producto_unidad').value = producto.unidad_medida;
    document.getElementById('producto_stock').value = producto.stock;
    document.getElementById('producto_stock_minimo').value = producto.stock_minimo;
}
</script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
