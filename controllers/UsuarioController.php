<?php

class UsuarioController {
    public function index() {
        Security::checkRole(['Administrador']);
        $db = Database::getConnection();
        $usuarios = $db->query(
            "SELECT u.id, u.username, u.nombre, u.apellido, u.correo, u.activo,
                    r.nombre AS rol
             FROM usuarios u
             JOIN roles r ON r.id = u.rol_id
             ORDER BY r.id, u.nombre, u.apellido"
        )->fetchAll();
        $csrfToken = Security::generateCSRFToken();
        require __DIR__ . '/../views/admin/usuarios.php';
    }

    public function cambiarPassword() {
        Security::checkRole(['Administrador']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
            !Security::validateCSRFToken($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            throw new RuntimeException('Solicitud no válida o token de seguridad vencido.');
        }

        $usuarioId = filter_var($_POST['usuario_id'] ?? null, FILTER_VALIDATE_INT);
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        if (!$usuarioId) {
            throw new InvalidArgumentException('El usuario seleccionado no es válido.');
        }
        if (strlen($password) < 12 || $password !== $passwordConfirm) {
            throw new InvalidArgumentException('La contraseña debe tener al menos 12 caracteres y ambas entradas deben coincidir.');
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $usuarioId]);
        if ($stmt->rowCount() !== 1) {
            $stmt = $db->prepare("SELECT id FROM usuarios WHERE id = ?");
            $stmt->execute([$usuarioId]);
            if (!$stmt->fetch()) {
                throw new RuntimeException('No se encontró el usuario que se desea actualizar.');
            }
        }

        header('Location: index.php?action=usuarios&password_updated=1');
        exit();
    }
}
