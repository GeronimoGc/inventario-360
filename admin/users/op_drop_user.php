<?php
// admin/users/index.php (o cualquier página protegida)

session_start(); // Inicia la sesión PHP

define('PROTECT_CONFIG', true); // Bandera para proteger la inclusión de archivos de configuración

// --- 1. Verificación de Autenticación General ---
// Comprueba si la bandera 'logged_in' de la sesión está activa.
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Si no hay sesión activa, redirige al usuario a la página de login (normalmente la raíz).
    header("Location: ../../"); // Ajusta la ruta relativa según la ubicación del archivo
    exit(); // Detiene la ejecución del script
}

// --- 2. Carga de Datos del Usuario Actual ---
// Accede a los datos completos del usuario guardados en $_SESSION['usuario_data']
// durante el proceso de login en op_validar.php.
if (!isset($_SESSION['usuario_data']) || !is_array($_SESSION['usuario_data'])) {
    // Si los datos del usuario no están disponibles (situación inusual pero posible),
    // se considera un error de sesión y se redirige.
    $_SESSION['error'] = 'Error de sesión. Por favor, inicie sesión de nuevo.';
    header("Location: ../../"); // Redirige al login
    exit();
}

$usuario_actual = $_SESSION['usuario_data']; // Aquí se obtienen todos los datos del usuario actual


require_once '../../assets/config/info.php';
$permisoRequerido = $op_eliminar_usuario; // Esto asume que $bodega_crear ya está definido en info.php
if (isset($_SESSION['usuario_permisos']) && in_array($permisoRequerido, $_SESSION['usuario_permisos'])) {
    $esAccesoPermitido = true;
} else {
    $_SESSION['mensaje_error'] = "Acceso denegado. No cuentas con el permiso " . $permisoRequerido . ". Por favor Contacta con tu departamento de IT o administrador de ayudas";

    header("Location: ../users/");
    exit();
}

require_once '../../assets/config/db.php'; // Asegúrate de que esta ruta sea correcta

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validar y sanear la entrada del ID de usuario
    $id_usuario_eliminar = (int) ($_POST['id_usuario_eliminar'] ?? 0);

    // Validación básica
    if ($id_usuario_eliminar === 0) {
        $_SESSION['mensaje_error'] = "ID de usuario inválido para eliminar.";
        header("Location: ../../../admin/users/");
        exit();
    }

    // Opcional: Evitar que un administrador se elimine a sí mismo (si es crítico para el sistema)
    // Puedes verificar si $id_usuario_eliminar es igual al idusuario de $_SESSION['usuario']
    if ($id_usuario_eliminar == $_SESSION['usuario']['idusuario']) {
        $_SESSION['mensaje_error'] = "No puedes eliminar tu propia cuenta.";
        header("Location: ../../admin/users/");
        exit();
    }

    try {
        // Eliminar el usuario
        $sql = "DELETE FROM inventario360_usuario WHERE idusuario = :idusuario";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idusuario' => $id_usuario_eliminar]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['mensaje_exito'] = "Usuario eliminado exitosamente.";
        } else {
            $_SESSION['mensaje_error'] = "No se encontró el usuario o no se pudo eliminar.";
        }
    } catch (PDOException $e) {
        // En caso de que el usuario tenga registros relacionados debido a FOREIGN KEYs
        // con ON DELETE NO ACTION o ON DELETE RESTRICT, se producirá un error.
        // La estructura de tu BD tiene ON DELETE CASCADE para fk_usuario_rol, pero
        // si hay otras tablas que referencian al usuario y no tienen CASCADE, podría fallar.
        if ($e->getCode() == '23000') { // Código de error SQLSTATE para violación de integridad
            $_SESSION['mensaje_error'] = "Error al eliminar el usuario. Es posible que existan registros relacionados (actividad, etc.) que impidan la eliminación directa. Contacta al soporte si esto persiste.";
        } else {
            $_SESSION['mensaje_error'] = "Error de base de datos al eliminar el usuario: " . $e->getMessage();
        }
    }
} else {
    // Si la solicitud no es POST, redirigir o mostrar un error
    $_SESSION['mensaje_error'] = "Acceso no autorizado.";
}

// Redirigir de vuelta a la página de gestión de usuarios
header("Location: ../../admin/users/");
exit();
?>