<?php
// =========================================================================
// VITASALUD - CONTROLADOR DE AUTENTICACIÓN (controllers/AuthController.php)
// =========================================================================

class AuthController {

    private function getDB() {
        if (method_exists('Database', 'conectar')) {
            return Database::conectar();
        } elseif (method_exists('Database', 'getConnection')) {
            return Database::getConnection();
        } elseif (method_exists('Database', 'getConexion')) {
            return Database::getConexion();
        } else {
            $db = new Database();
            return method_exists($db, 'conectar') ? $db->conectar() : $db->getConnection();
        }
    }

    public function login() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['usuario_id'])) {
            header("Location: index.php?action=dashboard");
            exit();
        }

        // PROCESAR FORMULARIO POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::validateCSRFToken($_POST['csrf_token'] ?? null)) {
                http_response_code(400);
                throw new RuntimeException('Solicitud no válida o token de seguridad vencido.');
            }
            // Se leen inputs capturando variaciones de nombres (username/usuario)
            $username = trim($_POST['username'] ?? $_POST['usuario'] ?? '');
            $password = $_POST['password'] ?? '';

            if (!empty($username) && !empty($password)) {
                try {
                    $db = $this->getDB();
                    
                    // Buscar usuario ignorando mayúsculas/minúsculas
                    $stmt = $db->prepare("SELECT * FROM usuarios WHERE LOWER(username) = LOWER(:username) AND activo = 1 LIMIT 1");
                    $stmt->execute([':username' => $username]);
                    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($usuario && password_verify($password, $usuario['password'])) {
                        session_regenerate_id(true);
                        $_SESSION['usuario_id'] = (int)$usuario['id'];
                        $_SESSION['usuario_nombre'] = $usuario['nombre'] . ' ' . $usuario['apellido'];
                        $_SESSION['usuario_rol'] = (int)$usuario['rol_id'];
                        $_SESSION['username'] = $usuario['username'];

                        header("Location: index.php?action=dashboard");
                        exit();
                    }
                } catch (PDOException $e) {
                    error_log("VitaSalud login database failure: " . $e->getMessage());
                    http_response_code(500);
                    echo "No fue posible verificar las credenciales. Revise la conexión a la base de datos.";
                    return;
                }
            }

            header("Location: index.php?action=login&error=1");
            exit();
        }

        $puedeCrearAdministrador = false;
        $requiereActualizarEsquema = false;
        if (Security::isLocalRequest()) {
            try {
                $puedeCrearAdministrador = $this->puedeCrearAdministradorInicial();
            } catch (PDOException $e) {
                error_log('VitaSalud recovery status failure: ' . $e->getMessage());
                $requiereActualizarEsquema = true;
            }
        }
        $csrfToken = Security::generateCSRFToken();
        require_once __DIR__ . '/../views/auth/login.php';
    }

    public function crearAdministradorInicial() {
        if (!Security::isLocalRequest()) {
            http_response_code(403);
            throw new RuntimeException('La recuperación inicial solo está disponible desde este equipo.');
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
            !Security::validateCSRFToken($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            throw new RuntimeException('Solicitud no válida o token de seguridad vencido.');
        }

        $username = trim($_POST['username'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $correo = trim($_POST['correo'] ?? '');

        if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $username)) {
            throw new InvalidArgumentException('El usuario debe tener entre 3 y 50 caracteres: letras, números, punto, guion o guion bajo.');
        }
        if ($nombre === '' || strlen($nombre) > 100 || $apellido === '' || strlen($apellido) > 100) {
            throw new InvalidArgumentException('Nombre y apellido son obligatorios.');
        }
        if (strlen($password) < 12 || $password !== $passwordConfirm) {
            throw new InvalidArgumentException('La contraseña debe tener al menos 12 caracteres y ambas entradas deben coincidir.');
        }
        if ($correo !== '' && (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) > 100)) {
            throw new InvalidArgumentException('El correo electrónico no es válido.');
        }

        $db = $this->getDB();
        $db->beginTransaction();
        try {
            $stmt = $db->query(
                "SELECT valor FROM configuracion_sistema
                 WHERE clave = 'administrador_inicial_creado' FOR UPDATE"
            );
            $estado = $stmt->fetchColumn();
            if ($estado === false || $estado !== '0') {
                throw new RuntimeException('La creación inicial de administrador ya fue utilizada o falta actualizar el esquema de vitasalud_db.');
            }

            $stmt = $db->prepare(
                "INSERT INTO usuarios (username, password, nombre, apellido, correo, rol_id, activo)
                 VALUES (?, ?, ?, ?, ?, 1, 1)"
            );
            $stmtExiste = $db->prepare("SELECT id FROM usuarios WHERE LOWER(username) = LOWER(?)");
            $stmtExiste->execute([$username]);
            if ($stmtExiste->fetch()) {
                throw new InvalidArgumentException('Ese nombre de usuario ya está registrado. Elige otro.');
            }
            $stmt->execute([
                $username,
                password_hash($password, PASSWORD_DEFAULT),
                $nombre,
                $apellido,
                $correo !== '' ? $correo : null,
            ]);

            $stmt = $db->prepare(
                "UPDATE configuracion_sistema
                 SET valor = '1' WHERE clave = 'administrador_inicial_creado' AND valor = '0'"
            );
            $stmt->execute();
            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('La creación inicial ya fue completada en otra solicitud.');
            }
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }

        header('Location: index.php?action=login&admin_created=1');
        exit();
    }

    public function mostrarCreacionAdministrador() {
        if (!Security::isLocalRequest()) {
            http_response_code(403);
            throw new RuntimeException('La recuperación inicial solo está disponible desde este equipo.');
        }
        if (!$this->puedeCrearAdministradorInicial()) {
            http_response_code(403);
            throw new RuntimeException('La creación inicial de administrador ya fue utilizada.');
        }

        $csrfToken = Security::generateCSRFToken();
        require __DIR__ . '/../views/auth/crear_administrador.php';
    }

    private function puedeCrearAdministradorInicial() {
        if (!Security::isLocalRequest()) {
            return false;
        }
        $stmt = $this->getDB()->query(
            "SELECT valor FROM configuracion_sistema
             WHERE clave = 'administrador_inicial_creado'"
        );
        return $stmt->fetchColumn() === '0';
    }

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = array();

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
        header("Location: index.php?action=login");
        exit();
    }
}