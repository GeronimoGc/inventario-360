<?php
define('PROTECT_CONFIG', true); // Protección para incluir la configuración
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '../../../../assets/config/db.php'; // Asegúrate de que la ruta sea correcta

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Acceso no autorizado para cambiar contraseña.'];
    header("Location: ../../../"); // Redirige a la página de login o principal
    exit();
}

$id_usuario = $_POST['id_usuario'] ?? '';
$correo = $_POST['correo'] ?? '';
$nueva_contrasenia = $_POST['nueva_contrasenia'] ?? '';
$confirmar_contrasenia = $_POST['confirmar_contrasenia'] ?? '';

// Validaciones básicas de campos
if (empty($id_usuario) || empty($correo) || empty($nueva_contrasenia) || empty($confirmar_contrasenia)) {
    $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Todos los campos son requeridos.'];
    header("Location: ../../../"); // Redirige a la página de login o al formulario correspondiente
    exit();
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'El formato del correo electrónico es inválido.'];
    header("Location: ../../../");
    exit();
}

if ($nueva_contrasenia !== $confirmar_contrasenia) {
    $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'La nueva contraseña y su confirmación no coinciden.'];
    header("Location: ../../../");
    exit();
}

// Validación de longitud de la nueva contraseña (ajusta el mínimo si es necesario)
if (strlen($nueva_contrasenia) < 6) {
    $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'La nueva contraseña debe tener al menos 6 caracteres.'];
    header("Location: ../../../");
    exit();
}

try {
    // Conexión a la base de datos
    $pdo = new PDO("mysql:host=$servidor;dbname=$nombre_bd", $usuario_bd, $contrasena_bd, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // 1. Verificar si el ID de usuario y el correo coinciden (sin contraseña antigua)
    $stmt = $pdo->prepare("SELECT idusuario FROM inventario360_usuario WHERE idusuario = :id_usuario AND correo = :correo");
    $stmt->execute([':id_usuario' => $id_usuario, ':correo' => $correo]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'ID de usuario o correo electrónico incorrectos.'];
        header("Location: ../../../");
        exit();
    }

    // 2. Hashear la nueva contraseña
    $nueva_contrasenia_hasheada = password_hash($nueva_contrasenia, PASSWORD_DEFAULT);

    // 3. Actualizar la contraseña en la base de datos
    $stmt_update = $pdo->prepare("UPDATE inventario360_usuario SET contrasenia = :nueva_contrasenia WHERE idusuario = :id_usuario");
    $stmt_update->execute([':nueva_contrasenia' => $nueva_contrasenia_hasheada, ':id_usuario' => $id_usuario]);

    if ($stmt_update->rowCount() > 0) {
        $_SESSION['mensaje'] = ['tipo' => 'exito', 'texto' => 'La contraseña ha sido restablecida exitosamente.'];
    } else {
        // Esto podría pasar si el usuario existe pero la nueva contraseña es idéntica a la actual hasheada
        $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'No se pudo restablecer la contraseña. Es posible que la nueva contraseña sea igual a la actual o que el usuario no exista.'];
    }

    header("Location: ../../../"); // Redirige a la página de login después de la operación
    exit();

} catch (PDOException $e) {
    error_log("Error en op_cambiar_contrasenia_sin_antigua.php: " . $e->getMessage());
    $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Error en el sistema al intentar restablecer la contraseña. Por favor, intente más tarde.'];
    header("Location: ../../../");
    exit();
}
?>