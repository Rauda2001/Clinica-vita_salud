<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Security {
    public static function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCSRFToken($token) {
        return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function sanitize($data) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    public static function checkRole($allowedRoles) {
        $role = $_SESSION['rol'] ?? $_SESSION['usuario_rol'] ?? null;
        $roleNames = [
            1 => 'Administrador',
            2 => 'Enfermera',
            3 => 'Medico',
        ];
        if (is_numeric($role)) {
            $role = $roleNames[(int)$role] ?? null;
        }
        if (is_string($role)) {
            $role = str_replace('é', 'e', strtolower($role));
            $allowedRoles = array_map(function ($allowedRole) {
                return str_replace('é', 'e', strtolower((string)$allowedRole));
            }, $allowedRoles);
        }
        if ($role === null || !in_array(str_replace('é', 'e', strtolower((string)$role)), $allowedRoles, true)) {
            header("Location: index.php?action=login&error=unauthorized");
            exit();
        }
    }

    public static function isLocalRequest() {
        $remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        return in_array($remoteAddress, ['127.0.0.1', '::1'], true);
    }
}