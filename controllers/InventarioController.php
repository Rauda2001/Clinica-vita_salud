<?php

class InventarioController {
    private function db() {
        return Database::getConnection();
    }

    public function index() {
        Security::checkRole(['Administrador', 'Enfermera', 'Recepcion']);
        $db = $this->db();
        $productos = $db->query(
            "SELECT id, nombre, descripcion, categoria, unidad_medida, stock, stock_minimo
             FROM inventario WHERE activo = 1 ORDER BY nombre"
        )->fetchAll();
        $pendientes = $db->query(
            "SELECT oa.id, oa.cantidad, oa.indicaciones, oa.creado_en,
                    i.nombre AS producto, i.unidad_medida, p.nombres, p.apellidos,
                    p.expediente, hc.cita_id, hc.fecha_consulta,
                    CONCAT(u.nombre, ' ', u.apellido) AS medico
             FROM ordenes_aplicacion oa
             JOIN inventario i ON i.id = oa.inventario_id
             JOIN historial_clinico hc ON hc.id = oa.historial_id
             JOIN pacientes p ON p.id = hc.paciente_id
             JOIN usuarios u ON u.id = hc.medico_id
             WHERE oa.estado = 'Pendiente'
             ORDER BY oa.creado_en, oa.id"
        )->fetchAll();

        require __DIR__ . '/../views/enfermera/inventario.php';
    }

    public function guardarProducto() {
        Security::checkRole(['Administrador', 'Enfermera', 'Recepcion']);
        $this->validarPost();

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $categoria = trim($_POST['categoria'] ?? 'Medicamento');
        $unidad = trim($_POST['unidad_medida'] ?? 'unidad');
        $stock = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_FLOAT);
        $stockMinimo = filter_var($_POST['stock_minimo'] ?? null, FILTER_VALIDATE_FLOAT);

        if ($nombre === '' || strlen($nombre) > 150 || $stock === false || $stock < 0 ||
            $stockMinimo === false || $stockMinimo < 0 ||
            !in_array($categoria, ['Medicamento', 'Suero', 'Insumo'], true) ||
            $unidad === '' || strlen($unidad) > 40) {
            throw new InvalidArgumentException('Revise nombre, categoría, unidad y cantidades de stock.');
        }

        $db = $this->db();
        if ($id) {
            $db->beginTransaction();
            try {
                $exists = $db->prepare("SELECT id FROM inventario WHERE id = ? AND activo = 1 FOR UPDATE");
                $exists->execute([$id]);
                if (!$exists->fetch()) {
                    throw new RuntimeException('El producto seleccionado no existe o está inactivo.');
                }
                $reserved = $db->prepare(
                    "SELECT COALESCE(SUM(cantidad), 0) FROM ordenes_aplicacion
                     WHERE inventario_id = ? AND estado = 'Pendiente'"
                );
                $reserved->execute([$id]);
                if ((float)$stock < (float)$reserved->fetchColumn()) {
                    throw new RuntimeException('El stock no puede quedar por debajo de las cantidades ya pendientes de despacho.');
                }
                $stmt = $db->prepare(
                    "UPDATE inventario SET nombre = ?, descripcion = ?, categoria = ?,
                     unidad_medida = ?, stock = ?, stock_minimo = ? WHERE id = ? AND activo = 1"
                );
                $stmt->execute([$nombre, $descripcion ?: null, $categoria, $unidad, $stock, $stockMinimo, $id]);
                $db->commit();
            } catch (Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                throw $e;
            }
        } else {
            $stmt = $db->prepare(
                "INSERT INTO inventario (nombre, descripcion, categoria, unidad_medida, stock, stock_minimo)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$nombre, $descripcion ?: null, $categoria, $unidad, $stock, $stockMinimo]);
        }

        header('Location: index.php?action=inventario&success=producto_guardado');
        exit();
    }

    public function despachar() {
        Security::checkRole(['Administrador', 'Enfermera', 'Recepcion']);
        $this->validarPost();
        $ordenId = filter_input(INPUT_POST, 'orden_id', FILTER_VALIDATE_INT);
        if (!$ordenId) {
            throw new InvalidArgumentException('La orden de despacho no es válida.');
        }

        $db = $this->db();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                "SELECT oa.id, oa.inventario_id, oa.cantidad, oa.estado, i.stock, i.nombre
                 FROM ordenes_aplicacion oa
                 JOIN inventario i ON i.id = oa.inventario_id
                 WHERE oa.id = ? FOR UPDATE"
            );
            $stmt->execute([$ordenId]);
            $orden = $stmt->fetch();
            if (!$orden || $orden['estado'] !== 'Pendiente') {
                throw new RuntimeException('La orden no existe o ya fue despachada.');
            }
            if ((float)$orden['stock'] < (float)$orden['cantidad']) {
                throw new RuntimeException('Stock insuficiente para despachar ' . $orden['nombre'] . '.');
            }

            $descuento = $db->prepare(
                "UPDATE inventario SET stock = stock - ?
                 WHERE id = ? AND stock >= ?"
            );
            $descuento->execute([$orden['cantidad'], $orden['inventario_id'], $orden['cantidad']]);
            if ($descuento->rowCount() !== 1) {
                throw new RuntimeException('No se pudo actualizar el stock; vuelva a cargar la página.');
            }

            $actualizar = $db->prepare(
                "UPDATE ordenes_aplicacion
                 SET estado = 'Despachado', despachado_por = ?, despachado_en = NOW()
                 WHERE id = ? AND estado = 'Pendiente'"
            );
            $actualizar->execute([$_SESSION['usuario_id'] ?? $_SESSION['user_id'], $ordenId]);
            if ($actualizar->rowCount() !== 1) {
                throw new RuntimeException('La orden ya fue procesada por otro usuario.');
            }
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }

        header('Location: index.php?action=inventario&success=despacho_realizado');
        exit();
    }

    private function validarPost() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
            !Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            http_response_code(400);
            throw new RuntimeException('Solicitud no válida o token de seguridad vencido.');
        }
    }
}
