<?php
// =========================================================================
// VITASALUD - MODELO DE PACIENTE (models/Paciente.php)
// =========================================================================

require_once __DIR__ . '/../config/database.php';

class Paciente {
    private $db;

    public function __construct() {
        // Adaptación automática a cualquier estructura de Database.php
        if (class_exists('Database')) {
            if (method_exists('Database', 'getInstance')) {
                $this->db = Database::getInstance()->getConnection();
            } elseif (method_exists('Database', 'getConnection')) {
                $this->db = Database::getConnection();
            } else {
                $dbObj = new Database();
                $this->db = method_exists($dbObj, 'getConnection') ? $dbObj->getConnection() : $dbObj->connect();
            }
        } elseif (isset($pdo)) {
            global $pdo;
            $this->db = $pdo;
        } elseif (isset($conn)) {
            global $conn;
            $this->db = $conn;
        }
    }

    /**
     * Búsqueda en tiempo real por Nombre, Apellido, DUI o Expediente
     */
    public function buscar($query) {
        $term = "%" . trim($query) . "%";

        $sql = "SELECT id, expediente, nombres, apellidos, dui, telefono 
                FROM pacientes 
                WHERE nombres LIKE :term1 
                   OR apellidos LIKE :term2 
                   OR CONCAT(nombres, ' ', apellidos) LIKE :term3
                   OR expediente LIKE :term4 
                   OR dui LIKE :term5 
                ORDER BY nombres ASC 
                LIMIT 10";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':term1' => $term,
            ':term2' => $term,
            ':term3' => $term,
            ':term4' => $term,
            ':term5' => $term
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Genera un número de expediente autoincrementable por año (VS-AAAA-0001)
     */
    public function generarExpediente() {
        $anioActual = date('Y');
        $prefix = "VS-" . $anioActual . "-";

        $sql = "SELECT expediente FROM pacientes WHERE expediente LIKE :prefix ORDER BY id DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':prefix' => $prefix . '%']);
        $ultimo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($ultimo) {
            $partes = explode('-', $ultimo['expediente']);
            $correlativo = (int)end($partes) + 1;
        } else {
            $correlativo = 1;
        }

        return $prefix . str_pad($correlativo, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Registra un nuevo paciente en la base de datos
     */
    public function registrar($datos) {
        $sql = "INSERT INTO pacientes (expediente, nombres, apellidos, fecha_nacimiento, sexo, dui, direccion, telefono, contacto_emergencia) 
                VALUES (:expediente, :nombres, :apellidos, :fecha_nacimiento, :sexo, :dui, :direccion, :telefono, :contacto_emergencia)";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':expediente' => $datos['expediente'],
            ':nombres' => $datos['nombres'],
            ':apellidos' => $datos['apellidos'],
            ':fecha_nacimiento' => $datos['fecha_nacimiento'],
            ':sexo' => $datos['sexo'],
            ':dui' => $datos['dui'] ?: null,
            ':direccion' => $datos['direccion'] ?: null,
            ':telefono' => $datos['telefono'],
            ':contacto_emergencia' => $datos['contacto_emergencia'] ?: null
        ]);
    }
}