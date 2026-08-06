<?php
class Cita {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM citas WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getMedicosDisponibles() {
        return $this->db->query("SELECT m.id, CONCAT('Dr(a). ', u.nombre, ' ', u.apellido) as nombre, e.nombre as especialidad 
                                 FROM medicos m 
                                 JOIN usuarios u ON m.usuario_id = u.id
                                 JOIN Black_especialidades e ON m.especialidad_id = e.id WHERE u.activo = 1")->fetchAll();
    }

    public function insertarCitaDirecta($paciente_id, $medico_id, $tipo) {
        $sql = "INSERT INTO citas (paciente_id, medico_id, fecha, hora, estado, tipo_ingreso, motivo) 
                VALUES (?, ?, CURDATE(), CURTIME(), 'En espera', ?, 'Atención del día registrada por recepción')";
        return $this->db->prepare($sql)->execute([$paciente_id, $medico_id, $tipo]);
    }

    public function moverACola($cita_id) {
        $stmt = $this->db->prepare("UPDATE citas SET estado = 'En espera' WHERE id = ?");
        return $stmt->execute([$cita_id]);
    }

    public function getColaDeEspera() {
        $query = "SELECT c.id, p.expediente, CONCAT(p.nombres, ' ', p.apellidos) as paciente, p.dui,
                         CONCAT('Dr. ', u.nombre, ' ', u.apellido) as medico, c.hora, c.estado, c.tipo_ingreso,
                         (SELECT COUNT(*) FROM signos_vitales s WHERE s.cita_id = c.id) as tiene_triaje
                  FROM citas c
                  JOIN pacientes p ON c.paciente_id = p.id
                  JOIN medicos m ON c.medico_id = m.id
                  JOIN usuarios u ON m.usuario_id = u.id
                  WHERE c.fecha = CURDATE() AND c.estado IN ('En espera', 'En consulta')
                  ORDER BY c.estado DESC, c.hora ASC"; // Prioriza los que están en consulta, luego por orden de llegada
        return $this->db->query($query)->fetchAll();
    }

    public function getAgendaMedico($usuario_id) {
        $query = "SELECT c.id, p.expediente, CONCAT(p.nombres, ' ', p.apellidos) as paciente, c.hora, c.estado, c.tipo_ingreso
                  FROM citas c
                  JOIN pacientes p ON c.paciente_id = p.id
                  JOIN medicos m ON c.medico_id = m.id
                  WHERE c.fecha = CURDATE() AND m.usuario_id = ? AND c.estado = 'En espera'
                  ORDER BY c.hora ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll();
    }
}