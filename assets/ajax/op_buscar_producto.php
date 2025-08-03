<?php
session_start();

// Verificar si el usuario está logueado y tiene permiso (opcional, pero recomendado)
if (!isset($_SESSION['usuario'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Acceso no autorizado.']);
    exit();
}

// Define PROTECT_CONFIG para permitir la inclusión de db.php
if (!defined('PROTECT_CONFIG')) {
    define('PROTECT_CONFIG', true);
}
require_once __DIR__ . '../config/db.php'; // Ruta a tu archivo db.php

// Verificar si la conexión PDO se estableció correctamente
if (!isset($pdo) || $pdo === null) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Error al conectar con la base de datos.', 'details' => $dbError ?? 'No Pdo object']);
    exit();
}

header('Content-Type: application/json');

$response = ['success' => false, 'producto' => null, 'message' => ''];

if (isset($_GET['numero_producto'])) {
    $numero_producto = trim($_GET['numero_producto']);

    if (!empty($numero_producto)) {
        try {
            $sql = "SELECT p.idproducto, p.numero_producto, p.nombre, p.estado, p.fecha_registro, p.precio, 
                           c.nombre as categoria_nombre, b.nombre as bodega_nombre 
                    FROM inventario360_producto p
                    LEFT JOIN inventario360_categoria c ON p.idcategoria = c.idcategoria
                    LEFT JOIN inventario360_bodega b ON p.idbodega = b.idbodega
                    WHERE p.numero_producto = :numero_producto";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':numero_producto', $numero_producto, PDO::PARAM_STR);
            $stmt->execute();
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($producto) {
                $response['success'] = true;
                $response['producto'] = $producto;
            } else {
                $response['message'] = 'Producto no encontrado.';
            }
        } catch (PDOException $e) {
            $response['message'] = 'Error en la consulta: ' . $e->getMessage();
            // Considera loggear $e->getMessage() en un entorno de producción en lugar de mostrarlo.
        }
    } else {
        $response['message'] = 'Por favor, ingrese un número de producto.';
    }
} else {
    $response['message'] = 'Número de producto no proporcionado.';
}

// PDO cierra la conexión automáticamente cuando el script termina o el objeto $pdo es destruido.
echo json_encode($response);
?>