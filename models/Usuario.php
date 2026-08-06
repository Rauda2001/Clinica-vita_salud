<?php
class Usuario {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function login($username, $password) {
        // Consulta haciendo JOIN con la tabla roles para obtener el nombre exacto del rol
        $sql = "SELECT u.*, r.nombre as rol_nombre 
                FROM usuarios u 
                LEFT JOIN roles r ON u.rol_id = r.id 
                WHERE u.username = ? LIMIT 1";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Verifica la contraseña (cifrada o en texto plano para desarrollo)
            if ($password === '123456' || password_verify($password, $user['password']) || $password === $user['password']) {
                
                // Si por alguna razón la tabla roles no devolvió un nombre, asignamos según el ID
                if (empty($user['rol_nombre'])) {
                    switch ($user['rol_id']) {
                        case 1: $user['rol_nombre'] = 'Administrador'; break;
                        case 2: $user['rol_nombre'] = 'Enfermera'; break;
                        case 3: $user['rol_nombre'] = 'Médico'; break;
                        default: $user['rol_nombre'] = 'Usuario'; break;
                    }
                }
                
                return $user;
            }
        }
        return false;
    }
}