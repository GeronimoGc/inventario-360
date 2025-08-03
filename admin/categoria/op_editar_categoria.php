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
$permisoRequerido = $op_editar_categoria;
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

require_once '../../assets/config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idcategoria = $_POST['idcategoria'];
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);

    if (empty($nombre) || empty($idcategoria)) {
        $_SESSION['mensaje_error'] = "ID de categoría o nombre no pueden estar vacíos.";
        header("Location: ../categoria/");
        exit();
    }

    try {
        $stmt = $pdo->prepare("UPDATE inventario360_categoria SET nombre = :nombre, descripcion = :descripcion WHERE idcategoria = :idcategoria");
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':idcategoria', $idcategoria, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $_SESSION['mensaje_exito'] = "Categoría '" . htmlspecialchars($nombre) . "' actualizada exitosamente.";
        } else {
            $_SESSION['mensaje_error'] = "No se encontró la categoría o no hubo cambios para actualizar.";
        }
    } catch (PDOException $e) {
        // Error de duplicado para el nombre
        if ($e->getCode() == 23000) { 
            $_SESSION['mensaje_error'] = "Ya existe otra categoría con el nombre '" . htmlspecialchars($nombre) . "'.";
        } else {
            $_SESSION['mensaje_error'] = "Error al actualizar la categoría: " . $e->getMessage();
        }
    }
} else {
    $_SESSION['mensaje_error'] = "Acceso no autorizado para editar categoría.";
}

header("Location: ../categoria/");
exit();
?>