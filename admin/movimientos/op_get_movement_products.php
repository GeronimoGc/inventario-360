<?php
session_start();
define('PROTECT_CONFIG', true);
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['usuario_data'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}

require_once '../../assets/config/info.php';
$permisoRequerido = $op_get_movement_products;
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
    $movement_id = $_GET['movement_id'] ?? null;

    if (empty($movement_id)) {
        echo json_encode(['success' => false, 'message' => 'ID de movimiento no proporcionado.']);
        exit();
    }

    $stmt = $pdo->prepare("
        SELECT
            mp.id,
            mp.producto_id,
            p.nombre AS nombre_producto,
            mp.cantidad,
            mp.precio_unitario
        FROM inventario360_movimientos_productos mp
        JOIN inventario360_producto p ON mp.producto_id = p.idproducto
        WHERE mp.movimiento_id = ?
    ");
    $stmt->execute([$movement_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'products' => $products]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error al obtener productos del movimiento: ' . $e->getMessage()]);
    exit();
}
?>