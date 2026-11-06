<?php

class ReporteController {
    public function pacientesEstadisticas() {
        Security::checkRole(['Administrador', 'Enfermera', 'Recepcion']);
        $db = Database::getConnection();

        $hoy = new DateTimeImmutable('today');
        $inicioMes = $hoy->modify('first day of this month');
        $inicioMesSiguiente = $inicioMes->modify('first day of next month');
        $inicioGraficaMeses = $inicioMes->modify('-11 months');

        $totalPacientes = (int)$db->query("SELECT COUNT(*) FROM pacientes")->fetchColumn();

        $stmt = $db->prepare(
            "SELECT COUNT(DISTINCT paciente_id)
             FROM citas
             WHERE estado = 'Atendida' AND fecha = ?"
        );
        $stmt->execute([$hoy->format('Y-m-d')]);
        $atendidosHoy = (int)$stmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT COUNT(DISTINCT paciente_id)
             FROM citas
             WHERE estado = 'Atendida' AND fecha >= ? AND fecha < ?"
        );
        $stmt->execute([$inicioMes->format('Y-m-d'), $inicioMesSiguiente->format('Y-m-d')]);
        $atendidosMes = (int)$stmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT p.id, p.expediente, p.nombres, p.apellidos, COUNT(c.id) AS visitas
             FROM citas c
             INNER JOIN pacientes p ON p.id = c.paciente_id
             WHERE c.estado = 'Atendida' AND c.fecha >= ? AND c.fecha < ?
             GROUP BY p.id, p.expediente, p.nombres, p.apellidos
             ORDER BY visitas DESC, p.apellidos, p.nombres
             LIMIT 1"
        );
        $stmt->execute([$inicioMes->format('Y-m-d'), $inicioMesSiguiente->format('Y-m-d')]);
        $pacienteMasFrecuente = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $stmt = $db->prepare(
            "SELECT DAY(fecha) AS dia, COUNT(DISTINCT paciente_id) AS total
             FROM citas
             WHERE estado = 'Atendida' AND fecha >= ? AND fecha < ?
             GROUP BY DAY(fecha)"
        );
        $stmt->execute([$inicioMes->format('Y-m-d'), $inicioMesSiguiente->format('Y-m-d')]);
        $atencionesPorDia = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $atencionesPorDia[(int)$fila['dia']] = (int)$fila['total'];
        }

        $diasMes = (int)$inicioMes->format('t');
        $etiquetasDias = range(1, $diasMes);
        $datosDias = [];
        foreach ($etiquetasDias as $dia) {
            $datosDias[] = $atencionesPorDia[$dia] ?? 0;
        }

        $stmt = $db->prepare(
            "SELECT YEAR(fecha) AS anio, MONTH(fecha) AS mes,
                    COUNT(DISTINCT paciente_id) AS total
             FROM citas
             WHERE estado = 'Atendida' AND fecha >= ? AND fecha < ?
             GROUP BY YEAR(fecha), MONTH(fecha)"
        );
        $stmt->execute([$inicioGraficaMeses->format('Y-m-d'), $inicioMesSiguiente->format('Y-m-d')]);
        $atencionesMensuales = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $atencionesMensuales[$fila['anio'] . '-' . $fila['mes']] = (int)$fila['total'];
        }

        $etiquetasMeses = [];
        $datosMeses = [];
        for ($mes = $inicioGraficaMeses; $mes < $inicioMesSiguiente; $mes = $mes->modify('first day of next month')) {
            $clave = $mes->format('Y-n');
            $etiquetasMeses[] = $mes->format('m/Y');
            $datosMeses[] = $atencionesMensuales[$clave] ?? 0;
        }

        $busquedaInput = $_GET['q'] ?? '';
        if (!is_string($busquedaInput)) {
            throw new InvalidArgumentException('El término de búsqueda no es válido.');
        }
        $busqueda = trim($busquedaInput);
        if (strlen($busqueda) > 100) {
            throw new InvalidArgumentException('La búsqueda no debe superar 100 caracteres.');
        }
        $paginaInput = $_GET['pagina'] ?? 1;
        $pagina = is_string($paginaInput) || is_int($paginaInput)
            ? filter_var($paginaInput, FILTER_VALIDATE_INT)
            : false;
        $pagina = $pagina && $pagina > 0 ? $pagina : 1;
        $porPagina = 25;

        $where = '';
        $parametros = [];
        if ($busqueda !== '') {
            $where = " WHERE p.expediente LIKE :expediente
                       OR p.nombres LIKE :nombres
                       OR p.apellidos LIKE :apellidos
                       OR CONCAT(p.nombres, ' ', p.apellidos) LIKE :nombre_completo
                       OR p.dui LIKE :dui";
            $termino = '%' . $busqueda . '%';
            $parametros = [
                ':expediente' => $termino,
                ':nombres' => $termino,
                ':apellidos' => $termino,
                ':nombre_completo' => $termino,
                ':dui' => $termino,
            ];
        }

        $stmtTotal = $db->prepare('SELECT COUNT(*) FROM pacientes p' . $where);
        $stmtTotal->execute($parametros);
        $totalCoincidencias = (int)$stmtTotal->fetchColumn();
        $totalPaginas = max(1, (int)ceil($totalCoincidencias / $porPagina));
        $pagina = min($pagina, $totalPaginas);
        $offset = ($pagina - 1) * $porPagina;

        $stmt = $db->prepare(
            "SELECT p.id, p.expediente, p.nombres, p.apellidos, p.dui, p.telefono, p.created_at,
                    COALESCE(a.total_atenciones, 0) AS total_atenciones,
                    a.ultima_atencion
             FROM pacientes p
             LEFT JOIN (
                 SELECT paciente_id, COUNT(*) AS total_atenciones, MAX(fecha) AS ultima_atencion
                 FROM citas
                 WHERE estado = 'Atendida'
                 GROUP BY paciente_id
             ) a ON a.paciente_id = p.id
             $where
             ORDER BY p.apellidos, p.nombres, p.id
             LIMIT $porPagina OFFSET $offset"
        );
        $stmt->execute($parametros);
        $pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/pacientes/estadisticas.php';
    }

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
