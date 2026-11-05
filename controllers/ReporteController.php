<?php

class ReporteController {
    public function verDashboardReportes() {
        Security::checkRole(['Administrador']);
        $db = Database::getConnection();

        $atendidosTotal = (int)$db->query(
            "SELECT COUNT(DISTINCT paciente_id) FROM citas
             WHERE estado = 'Atendida' AND fecha = CURDATE()"
        )->fetchColumn();
        $atendidosHoy = $db->query(
            "SELECT CONCAT('Dr(a). ', u.nombre, ' ', u.apellido) AS medico,
                    COUNT(c.id) AS total
             FROM medicos m
             JOIN usuarios u ON u.id = m.usuario_id
             LEFT JOIN citas c ON c.medico_id = m.id
                AND c.estado = 'Atendida' AND c.fecha = CURDATE()
             WHERE u.activo = 1
             GROUP BY m.id, u.nombre, u.apellido
             ORDER BY total DESC, medico"
        )->fetchAll();
        $productosBajos = $db->query(
            "SELECT id, nombre, categoria, stock, stock_minimo, unidad_medida
             FROM inventario
             WHERE activo = 1 AND stock <= stock_minimo
             ORDER BY stock ASC, nombre"
        )->fetchAll();
        $pacientesRecientes = $db->query(
            "SELECT id, expediente, nombres, apellidos
             FROM pacientes ORDER BY id DESC LIMIT 50"
        )->fetchAll();

        require __DIR__ . '/../views/admin/reportes.php';
    }
}
