<?php
define('PROTECT_CONFIG', true);
session_start();

// En producción, es recomendable desactivar la visualización de errores y usar un registro de errores.
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Incluye el archivo de configuración de la base de datos.
require_once __DIR__ . '/assets/config/db.php';

// --- Validación inicial de la solicitud ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Acceso no autorizado.';
    header("Location: /");
    exit();
}

if (empty($_POST['correo']) || empty($_POST['clave'])) {
    $_SESSION['error'] = 'Todos los campos son requeridos.';
    header("Location: /");
    exit();
}

// --- Conexión a la base de datos y validación de credenciales ---
try {
    $conexion = new PDO(
        "mysql:host=$servidor;dbname=$nombre_bd;charset=utf8mb4",
        $usuario_bd,
        $contrasena_bd,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );

    // Consulta para obtener el usuario y la información de su rol.
    // Importante: No se filtra por estado del rol aquí, porque el estado del rol
    // se verifica explícitamente después de la verificación de contraseña.
    $stmt = $conexion->prepare("
        SELECT
            u.*,
            r.idrol,
            r.nombre as rol_nombre,
            r.estado as rol_estado
        FROM
            inventario360_usuario u
        JOIN
            inventario360_rol r ON u.rol_id = r.idrol
        WHERE
            u.correo = ?
    ");
    $stmt->execute([$_POST['correo']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // --- Verificación de usuario, contraseña y estado del rol ---
    if ($usuario && password_verify($_POST['clave'], $usuario['contrasenia'])) {
        // Verificar si el rol del usuario está activo.
        // Si el rol está inactivo, se considera como una denegación de acceso.
        if (isset($usuario['rol_estado']) && $usuario['rol_estado'] === 'inactivo') {
            $_SESSION['error'] = 'Su rol de usuario está inactivo. Contacte al administrador.';
            header("Location: /");
            exit();
        }

        // Autenticación exitosa (credenciales correctas y rol activo)
        session_regenerate_id(true);

        unset($usuario['contrasenia']);
        $_SESSION['usuario_data'] = $usuario;
        $_SESSION['logged_in'] = true;

        // --- Obtener permisos del rol del usuario (SOLO PERMISOS ACTIVOS) ---
        $stmt_permisos = $conexion->prepare("
            SELECT
                p.nombre
            FROM
                inventario360_rol_permiso rp
            JOIN
                inventario360_permiso p ON rp.permiso_id = p.idpermiso
            WHERE
                rp.rol_id = ? AND p.estado = 'activo'
        ");
        $stmt_permisos->execute([$usuario['idrol']]);
        $permisos = $stmt_permisos->fetchAll(PDO::FETCH_COLUMN);

        $_SESSION['usuario_permisos'] = $permisos;

        // --- Manejo de la cookie "Recordarme" ---
        if (isset($_POST['remember'])) {
            setcookie(
                'remember_email',
                $_POST['correo'],
                time() + (30 * 24 * 60 * 60), // 30 días
                "/",
                "",
                isset($_SERVER['HTTPS']),
                true
            );
        } else {
            if (isset($_COOKIE['remember_email'])) {
                setcookie('remember_email', '', time() - 3600, "/"); // Eliminar la cookie
            }
        }

        // --- Permisos necesarios ---
        $permiso_login = 'acceso_login';        // Permiso básico para poder iniciar sesión
        $permiso_admin = 'acceso_admin';        // Permiso para el panel de administración
        $permiso_dashboard = 'acceso_dashboard'; // Permiso para el dashboard general (si existe en tu BD)


        // *** Lógica de Redirección Basada en Permisos ***

        // PRIMERA VERIFICACIÓN CRÍTICA: ¿Tiene el permiso básico para iniciar sesión?
        // Si el usuario no tiene el permiso 'acceso_login' activo, no debe poder entrar.
        if (!in_array($permiso_login, $_SESSION['usuario_permisos'])) {
            $_SESSION['error'] = 'Su cuenta no tiene los permisos necesarios para iniciar sesión o ha sido deshabilitada.';
            session_destroy(); // Destruir la sesión por seguridad
            header("Location: /"); // Redirige al login (index.php)
            exit();
        }

        // Si tiene permiso de login, entonces evaluamos a dónde redirigir
        if (in_array($permiso_admin, $_SESSION['usuario_permisos'])) {
            header("Location: /admin/"); // Redirige al panel de administrador
            exit();
        } elseif (in_array($permiso_dashboard, $_SESSION['usuario_permisos'])) {
            header("Location: /dashboard/"); // Redirige al dashboard general
            exit();
        } else {
            // Este caso significa que tiene 'acceso_login' pero ningún permiso para un panel específico.
            // Es una situación inesperada, así que se le informa y se le devuelve al login.
            $_SESSION['error'] = 'Su cuenta tiene acceso de inicio de sesión, pero no tiene permisos para ningún panel específico.';
            session_destroy(); // También se destruye la sesión aquí, ya que no hay destino válido.
            header("Location: /"); // Redirige al login (index.php)
            exit();
        }

    } else {
        // Credenciales incorrectas
        $_SESSION['error'] = 'Correo o contraseña incorrectos.';
        header("Location: /"); // Redirige al login (index.php)
        exit();
    }
} catch (PDOException $e) {
    // Registra el error para depuración en el servidor (no mostrar al usuario final)
    error_log('Error en op_validar.php: ' . $e->getMessage());
    $_SESSION['error'] = 'Ha ocurrido un error en el sistema. Por favor, inténtelo más tarde.';
    header("Location: /"); // Redirige al login (index.php)
    exit();
}
?>