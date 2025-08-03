<?php
session_start();
define('PROTECT_CONFIG', true);
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($input['movement_id']) || !isset($input['products'])) {
    echo json_encode(['success' => false, 'message' => 'Datos de solicitud inválidos.']);
    exit();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['usuario_data'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Por favor, inicie sesión.']);
    exit();
}

require_once '../../assets/config/info.php';
$permisoRequerido = $op_add_movement_products;
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
    $pdo->beginTransaction(); // Iniciar transacción

    $movement_id = $input['movement_id'];
    $products = $input['products'];

    if (empty($products)) {
        echo json_encode(['success' => false, 'message' => 'No se proporcionaron productos para añadir.']);
        exit();
    }

    $stmt = $pdo->prepare("INSERT INTO inventario360_movimientos_productos (movimiento_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)
                           ON DUPLICATE KEY UPDATE cantidad = cantidad + VALUES(cantidad), precio_unitario = VALUES(precio_unitario)");
    // El ON DUPLICATE KEY UPDATE es útil si se quisiera sumar cantidades del mismo producto si ya existe en el movimiento.
    // Si la intención es que cada entrada sea única o que se reemplace, se podría ajustar.
    // Para este caso, sumamos la cantidad y actualizamos el precio unitario al último valor.

    foreach ($products as $product) {
        $producto_id = $product['producto_id'] ?? null;
        $cantidad = $product['cantidad'] ?? 0;
        $precio_unitario = $product['precio_unitario'] ?? 0.00;

        if (empty($producto_id) || !is_numeric($cantidad) || $cantidad <= 0 || !is_numeric($precio_unitario)) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Datos de producto inválidos para uno o más elementos.']);
            exit();
        }
        $stmt->execute([$movement_id, $producto_id, $cantidad, $precio_unitario]);
    }

    $pdo->commit(); // Confirmar transacción
    echo json_encode(['success' => true, 'message' => 'Productos añadidos al movimiento exitosamente.']);

} catch (PDOException $e) {
    $pdo->rollBack(); // Revertir transacción en caso de error
    echo json_encode(['success' => false, 'message' => 'Error al añadir productos al movimiento: ' . $e->getMessage()]);
    exit();
}
?>