<?php
define('PROTECT_CONFIG', true);

session_start();

// Validar que el usuario esté logueado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['usuario_data'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit();
}

require_once '../../assets/config/db.php'; // Ajusta la ruta a tu archivo db.php

require_once '../../assets/config/info.php';
$permisoRequerido = $op_consultar_producto;
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

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'product' => null, 'movements' => []];

if (isset($_GET['numero_producto']) && !empty($_GET['numero_producto'])) {
    $numero_producto = $_GET['numero_producto'];

    try {
        // 1. Consultar el producto principal
        $stmt_product = $pdo->prepare("SELECT 
                                p.idproducto, 
                                p.numero_producto, 
                                p.nombre, 
                                p.estado, 
                                p.fecha_registro, 
                                p.precio,
                                c.nombre AS nombre_categoria,
                                b.nombre AS nombre_bodega
                               FROM 
                                inventario360_producto p
                               JOIN 
                                inventario360_categoria c ON p.idcategoria = c.idcategoria
                               JOIN 
                                inventario360_bodega b ON p.idbodega = b.idbodega
                               WHERE 
                                p.numero_producto = :numero_producto");
        $stmt_product->bindParam(':numero_producto', $numero_producto);
        $stmt_product->execute();
        $product = $stmt_product->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            $response['success'] = true;
            $response['product'] = $product;

            // 2. Consultar movimientos asociados a este producto
            $stmt_movements = $pdo->prepare("SELECT 
                                    mp.cantidad,
                                    m.fecha_movimiento,
                                    m.descripcion AS movimiento_descripcion,
                                    m.documento_referencia,
                                    m.estado_movimiento,
                                    tm.nombre AS tipo_movimiento_nombre,
                                    u.nombre AS usuario_nombre,
                                    bo.nombre AS bodega_origen_nombre,
                                    bd.nombre AS bodega_destino_nombre
                                FROM 
                                    inventario360_movimientos_productos mp
                                JOIN 
                                    inventario360_movimientos m ON mp.movimiento_id = m.idmovimiento
                                JOIN 
                                    inventario360_tipo_movimiento tm ON m.tipo_movimiento_id = tm.idtipo_movimiento
                                JOIN 
                                    inventario360_usuario u ON m.usuario_id = u.idusuario
                                LEFT JOIN 
                                    inventario360_bodega bo ON m.bodega_origen_id = bo.idbodega
                                LEFT JOIN 
                                    inventario360_bodega bd ON m.bodega_destino_id = bd.idbodega
                                WHERE 
                                    mp.producto_id = :idproducto
                                ORDER BY 
                                    m.fecha_movimiento DESC");
            $stmt_movements->bindParam(':idproducto', $product['idproducto']);
            $stmt_movements->execute();
            $movements = $stmt_movements->fetchAll(PDO::FETCH_ASSOC);
            $response['movements'] = $movements;

        } else {
            $response['message'] = 'No se encontró ningún producto con ese número.';
        }

    } catch (PDOException $e) {
        $response['message'] = 'Error en la base de datos: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'El número de producto es requerido.';
}

echo json_encode($response);
?>