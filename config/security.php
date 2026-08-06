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
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function sanitize($data) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    public static function checkRole($allowedRoles) {
        if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], $allowedRoles)) {
            header("Location: index.php?action=login&error=unauthorized");
            exit();
        }
    }
}