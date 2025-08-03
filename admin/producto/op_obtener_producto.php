<?php
// producto/op_obtener_producto.php
define('PROTECT_CONFIG', true);
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['usuario_data'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}

require_once '../../assets/config/info.php';
$permisoRequerido = $op_obtener_producto;
if (isset($_SESSION['usuario_permisos']) && in_array($permisoRequerido, $_SESSION['usuario_permisos'])) {
    $esAccesoPermitido = true;
} else {
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => "Acceso denegado. No cuentas con el permiso " . $permisoRequerido . ". Por favor Contacta con tu departamento de IT o administrador de ayudas"
    ];
    header("Location: ../");
    exit();
}

require_once '../../assets/config/db.php'; // Ajusta la ruta

$numero_producto = $_GET['numero_producto'] ?? '';

if (empty($numero_producto)) {
    echo json_encode(['success' => false, 'message' => 'Número de producto no proporcionado.']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT
                            p.idproducto,
                            p.numero_producto,
                            p.nombre,
                            p.estado,
                            p.precio,
                            p.idcategoria,
                            c.nombre AS nombre_categoria,
                            p.idbodega,
                            b.nombre AS nombre_bodega
                            FROM inventario360_producto p
                            LEFT JOIN inventario360_categoria c ON p.idcategoria = c.idcategoria
                            LEFT JOIN inventario360_bodega b ON p.idbodega = b.idbodega
                            WHERE p.numero_producto = ?");
    $stmt->execute([$numero_producto]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        echo json_encode(['success' => true, 'product' => $product]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>