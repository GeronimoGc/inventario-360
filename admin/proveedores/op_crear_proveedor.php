<?php
define('PROTECT_CONFIG', true);
session_start();

require_once '../../assets/config/db.php';

require_once '../../assets/config/info.php';
$permisoRequerido = $op_crear_proveedor;
if (isset($_SESSION['usuario_permisos']) && in_array($permisoRequerido, $_SESSION['usuario_permisos'])) {
    $esAccesoPermitido = true;
} else {
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => "Acceso denegado. No cuentas con el permiso " . $permisoRequerido . ". Por favor Contacta con tu departamento de IT o administrador de ayudas"
    ];
    header("Location: ../proveedores/");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $contacto = trim($_POST['contacto']);

    if (empty($nombre)) {
        $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'El nombre del proveedor no puede estar vacío.'];
        header("Location: ./");
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO inventario360_proveedor (nombre, contacto) VALUES (?, ?)");
        $stmt->execute([$nombre, $contacto]);

        $_SESSION['mensaje'] = ['tipo' => 'exito', 'texto' => 'Proveedor "' . htmlspecialchars($nombre) . '" añadido exitosamente.'];
        header("Location: ./");
        exit();

    } catch (PDOException $e) {
        // En un entorno de producción, solo deberías mostrar un mensaje genérico.
        // error_log("Error al crear proveedor: " . $e->getMessage()); // Registrar el error
        $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Error al añadir el proveedor: ' . htmlspecialchars($e->getMessage())];
        header("Location: ./");
        exit();
    }
} else {
    $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Acceso no autorizado.'];
    header("Location: ./");
    exit();
}
?>