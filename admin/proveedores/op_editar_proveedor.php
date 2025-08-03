<?php
define('PROTECT_CONFIG', true);
session_start();

require_once '../../assets/config/db.php';


require_once '../../assets/config/info.php';
$permisoRequerido = $op_editar_proveedor;
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
    $idproveedor = filter_input(INPUT_POST, 'idproveedor', FILTER_SANITIZE_NUMBER_INT);
    $nombre = trim($_POST['nombre']);
    $contacto = trim($_POST['contacto']);

    if (empty($idproveedor) || empty($nombre)) {
        $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'ID de proveedor o nombre no pueden estar vacíos para la edición.'];
        header("Location: ./");
        exit();
    }

    try {
        $stmt = $pdo->prepare("UPDATE inventario360_proveedor SET nombre = ?, contacto = ? WHERE idproveedor = ?");
        $stmt->execute([$nombre, $contacto, $idproveedor]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['mensaje'] = ['tipo' => 'exito', 'texto' => 'Proveedor "' . htmlspecialchars($nombre) . '" actualizado exitosamente.'];
        } else {
            $_SESSION['mensaje'] = ['tipo' => 'info', 'texto' => 'No se realizaron cambios en el proveedor "' . htmlspecialchars($nombre) . '".'];
        }
        header("Location: ./");
        exit();

    } catch (PDOException $e) {
        // error_log("Error al editar proveedor: " . $e->getMessage());
        $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Error al actualizar el proveedor: ' . htmlspecialchars($e->getMessage())];
        header("Location: ./");
        exit();
    }
} else {
    $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Acceso no autorizado.'];
    header("Location: ./");
    exit();
}
?>