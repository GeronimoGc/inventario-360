<?php
session_start();
define('PROTECT_CONFIG', true);
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}

require_once '../../assets/config/info.php';
$permisoRequerido = $op_search_products_movimientos;
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
    $query = $_GET['q'] ?? '';

    if (empty($query) || strlen($query) < 2) {
        echo json_encode(['success' => true, 'products' => []]);
        exit();
    }

    $searchTerm = '%' . $query . '%';

    $stmt = $pdo->prepare("SELECT idproducto, numero_producto, nombre, precio FROM inventario360_producto WHERE nombre LIKE ? OR numero_producto LIKE ? LIMIT 10");
    $stmt->execute([$searchTerm, $searchTerm]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'products' => $products]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error al buscar productos: ' . $e->getMessage()]);
    exit();
}
?>