<?php
session_start();
define('PROTECT_CONFIG', true);
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($input['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de producto de movimiento no proporcionado.']);
    exit();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['usuario_data'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Por favor, inicie sesión.']);
    exit();
}

require_once '../../assets/config/info.php';
$permisoRequerido = $op_delete_movement_product;
// CAMBIO AQUÍ: En lugar de redirigir, devuelve un JSON de error de permiso
if (!isset($_SESSION['usuario_permisos']) || !in_array($permisoRequerido, $_SESSION['usuario_permisos'])) {
    echo json_encode(['success' => false, 'message' => "Acceso denegado. No cuentas con el permiso " . $permisoRequerido . ". Por favor Contacta con tu departamento de IT o administrador de ayudas."]);
    exit();
}
require_once '../../assets/config/db.php';

if (!isset($pdo) || !$pdo instanceof PDO) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
    exit();
}

try {
    $id_movimiento_producto = $input['id'];

    $stmt = $pdo->prepare("DELETE FROM inventario360_movimientos_productos WHERE id = ?");
    $stmt->execute([$id_movimiento_producto]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Producto eliminado del movimiento.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Producto del movimiento no encontrado.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error al eliminar el producto del movimiento: ' . $e->getMessage()]);
    exit();
}
?>