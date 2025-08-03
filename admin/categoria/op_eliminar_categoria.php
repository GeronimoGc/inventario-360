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
$usuario = $_SESSION['usuario_data'];
require_once '../../assets/config/info.php';
$permisoRequerido = $op_eliminar_categoria;
if (isset($_SESSION['usuario_permisos']) && in_array($permisoRequerido, $_SESSION['usuario_permisos'])) {
    $esAccesoPermitido = true;
} else {
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => "Acceso denegado. No cuentas con el permiso " . $permisoRequerido . ". Por favor Contacta con tu departamento de IT o administrador de ayudas"
    ];
    header("Location: ../categoria/");
    exit();
}

// A partir de aquí, el usuario está autenticado y es un administrador.
// Puedes usar `$usuario_actual['nombre']`, `$usuario_actual['correo']`, etc.,
// para mostrar información personalizada en el encabezado o en el contenido de la página.

// Ejemplo de uso en el HTML (dentro del <body>):
/*
<header class="bg-white shadow-sm">
    <div class="flex items-center">
        <i class="fas fa-user-circle"></i>
        <span><?php echo htmlspecialchars($usuario_actual['nombre']); ?></span>
        <span><?php echo htmlspecialchars($usuario_actual['rol_nombre']); ?></span>
        <a href="../../op_logout.php">Cerrar sesión</a>
    </div>
</header>
*/


require_once '../../assets/config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idcategoria = $_POST['idcategoria'];

    if (empty($idcategoria)) {
        $_SESSION['mensaje_error'] = "ID de categoría no proporcionado para eliminar.";
        header("Location: ../categoria/");
        exit();
    }

    try {
        // Opcional: Obtener el nombre de la categoría antes de eliminar para el mensaje de éxito
        $stmt_nombre = $pdo->prepare("SELECT nombre FROM inventario360_categoria WHERE idcategoria = :idcategoria");
        $stmt_nombre->bindParam(':idcategoria', $idcategoria, PDO::PARAM_INT);
        $stmt_nombre->execute();
        $categoria_nombre = $stmt_nombre->fetchColumn();

        $stmt = $pdo->prepare("DELETE FROM inventario360_categoria WHERE idcategoria = :idcategoria");
        $stmt->bindParam(':idcategoria', $idcategoria, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $_SESSION['mensaje_exito'] = "Categoría '" . htmlspecialchars($categoria_nombre) . "' eliminada exitosamente.";
        } else {
            $_SESSION['mensaje_error'] = "No se encontró la categoría con ID " . htmlspecialchars($idcategoria) . ".";
        }
    } catch (PDOException $e) {
        // Considera si hay restricciones de clave foránea que impidan la eliminación
        if ($e->getCode() == 23000) { // Integridad referencial
            $_SESSION['mensaje_error'] = "No se puede eliminar la categoría porque tiene productos asociados. Primero elimina o reasigna los productos.";
        } else {
            $_SESSION['mensaje_error'] = "Error al eliminar la categoría: " . $e->getMessage();
        }
    }
} else {
    $_SESSION['mensaje_error'] = "Acceso no autorizado para eliminar categoría.";
}

header("Location: ../categoria/");
exit();
?>