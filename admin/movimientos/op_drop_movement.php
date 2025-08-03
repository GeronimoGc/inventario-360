<?php
session_start();
define('PROTECT_CONFIG', true);
header('Content-Type: application/json'); // Esto es útil si en el futuro se cambia a AJAX, pero la redirección lo maneja por ahora.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Método no permitido.';
    header("Location: ../movimientos/");
    exit();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['usuario_data'])) {
    $_SESSION['error'] = 'Acceso denegado. Por favor, inicie sesión.';
    header("Location: ../../");
    exit();
}

require_once '../../assets/config/info.php';
$permisoRequerido = $op_drop_movement;
if (isset($_SESSION['usuario_permisos']) && in_array($permisoRequerido, $_SESSION['usuario_permisos'])) {
    $esAccesoPermitido = true;
} else {
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => "Acceso denegado. No cuentas con el permiso " . $permisoRequerido . ". Por favor Contacta con tu departamento de IT o administrador de ayudas"
    ];
    header("Location: ../movimientos/");
    exit();
    }

require_once '../../assets/config/db.php';

if (!isset($pdo) || !$pdo instanceof PDO) {
    $_SESSION['error'] = 'Error de conexión a la base de datos.';
    header("Location: ../movimientos/");
    exit();
}

try {
    $idmovimiento = $_POST['id_movimiento_eliminar'] ?? null;

    if (empty($idmovimiento)) {
        $_SESSION['error'] = 'ID de movimiento no proporcionado para la eliminación.';
        header("Location: ../movimientos/");
        exit();
    }

    // Iniciar una transacción para asegurar que ambas eliminaciones (productos y movimiento) sean atómicas.
    $pdo->beginTransaction();

    // 1. Eliminar los productos asociados a este movimiento primero
    $stmt_delete_products = $pdo->prepare("DELETE FROM inventario360_movimientos_productos WHERE movimiento_id = ?");
    $stmt_delete_products->execute([$idmovimiento]);

    // 2. Ahora eliminar el movimiento principal
    $stmt_delete_movement = $pdo->prepare("DELETE FROM inventario360_movimientos WHERE idmovimiento = ?");
    $stmt_delete_movement->execute([$idmovimiento]);

    if ($stmt_delete_movement->rowCount() > 0) {
        $_SESSION['mensaje_exito'] = 'Movimiento y sus productos asociados eliminados exitosamente.';
    } else {
        $_SESSION['mensaje_aviso'] = 'El movimiento no fue encontrado o ya ha sido eliminado previamente.';
    }

    // Confirmar la transacción
    $pdo->commit();
    header("Location: ../movimientos/");
    exit();

} catch (PDOException $e) {
    // Revertir la transacción en caso de cualquier error
    $pdo->rollBack();
    $_SESSION['error'] = 'Error al eliminar el movimiento: ' . $e->getMessage();
    header("Location: ../movimientos/");
    exit();
}
?>