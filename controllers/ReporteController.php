<?php
class ReporteController {
    public function verDashboardReportes() {
        Security::checkRole(['Administrador', 'Enfermera']);
        $db = Database::getConnection();

        // Médicos y conteo diario
        $atendidosHoy = $db->query("SELECT CONCAT('Dr(a). ', u.nombre, ' ', u.apellido) as medico, COUNT(c.id) as total 
                                    FROM citas c JOIN medicos m ON c.medico_id = m.id JOIN usuarios u ON m.usuario_id = u.id 
                                    WHERE c.estado = 'Atendida' AND c.fecha = CURDATE() GROUP BY m.id")->fetchAll();

        // Ingresos consolidados mensuales agrupados por tipo
        $ingresosMetodo = $db->query("SELECT metodo_pago, SUM(monto) as total FROM ingresos WHERE MONTH(fecha_pago) = MONTH(NOW()) GROUP BY metodo_pago")->fetchAll();

        require_once __DIR__ . '/../views/admin/reportes.php';
    }
}