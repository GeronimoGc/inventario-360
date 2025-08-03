<?php
define('PROTECT_CONFIG', true);
session_start();
require_once '../../assets/config/db.php'; // Ajusta la ruta si es necesario

// Solo permitir acceso si hay sesión y rol es administrador (o el necesario para modificar)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['usuario_data'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Por favor, inicia sesión.']);
    exit();
}

require_once '../../assets/config/info.php';
$permisoRequerido = $op_modificar_producto;
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

header('Content-Type: application/json'); // Asegurarse de que la respuesta sea JSON

$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idproducto = filter_input(INPUT_POST, 'idproducto', FILTER_VALIDATE_INT);
    $numero_producto_nuevo = filter_input(INPUT_POST, 'numero_producto', FILTER_SANITIZE_STRING);
    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
    $estado = filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_STRING);
    $precio = filter_input(INPUT_POST, 'precio', FILTER_VALIDATE_FLOAT);
    $idcategoria = filter_input(INPUT_POST, 'idcategoria', FILTER_VALIDATE_INT);
    $idbodega = filter_input(INPUT_POST, 'idbodega', FILTER_VALIDATE_INT);
    $usuario_id = $_SESSION['usuario_data']['idusuario']; // ID del usuario que realiza la acción

    if (!$idproducto || !$numero_producto_nuevo || !$nombre || !$estado || $precio === false || !$idcategoria || !$idbodega) {
        $response['message'] = 'Todos los campos son obligatorios y válidos.';
        echo json_encode($response);
        exit();
    }

    try {
        $pdo->beginTransaction();

        // Obtener el número de producto actual antes de la actualización para el log
        $stmt_old_num = $pdo->prepare("SELECT numero_producto, precio FROM inventario360_producto WHERE idproducto = :idproducto");
        $stmt_old_num->bindParam(':idproducto', $idproducto);
        $stmt_old_num->execute();
        $old_product_data = $stmt_old_num->fetch(PDO::FETCH_ASSOC);

        if (!$old_product_data) {
            $response['message'] = 'Producto no encontrado para modificar.';
            echo json_encode($response);
            exit();
        }

        $old_precio = $old_product_data['precio'];
        $old_numero_producto = $old_product_data['numero_producto'];

        // Actualizar el producto
        $stmt_update = $pdo->prepare("
            UPDATE inventario360_producto
            SET
                numero_producto = :numero_producto_nuevo,
                nombre = :nombre,
                estado = :estado,
                precio = :precio,
                idcategoria = :idcategoria,
                idbodega = :idbodega
            WHERE
                idproducto = :idproducto
        ");

        $stmt_update->bindParam(':numero_producto_nuevo', $numero_producto_nuevo);
        $stmt_update->bindParam(':nombre', $nombre);
        $stmt_update->bindParam(':estado', $estado);
        $stmt_update->bindParam(':precio', $precio);
        $stmt_update->bindParam(':idcategoria', $idcategoria);
        $stmt_update->bindParam(':idbodega', $idbodega);
        $stmt_update->bindParam(':idproducto', $idproducto);
        $stmt_update->execute();

        // Registrar actividad de UPDATE
        $descripcion_registro = "Producto modificado: '{$old_numero_producto}' -> '{$numero_producto_nuevo}', Nombre: '{$nombre}', Estado: '{$estado}', Precio: '{$precio}', Categoría ID: '{$idcategoria}', Bodega ID: '{$idbodega}'";
        
        $stmt_log = $pdo->prepare("
            INSERT INTO inventario360_registro_actividad (accion, descripcion, usuario_idusuario, producto_idproducto)
            VALUES ('UPDATE', :descripcion, :usuario_id, :producto_id)
        ");
        $stmt_log->bindParam(':descripcion', $descripcion_registro);
        $stmt_log->bindParam(':usuario_id', $usuario_id);
        $stmt_log->bindParam(':producto_id', $idproducto);
        $stmt_log->execute();

        // Registrar historial de precios si el precio ha cambiado
        if ($precio != $old_precio) {
            $stmt_price_history = $pdo->prepare("
                INSERT INTO inventario360_historial_precios (producto_id, precio_anterior, precio_actual)
                VALUES (:producto_id, :precio_anterior, :precio_actual)
            ");
            $stmt_price_history->bindParam(':producto_id', $idproducto);
            $stmt_price_history->bindParam(':precio_anterior', $old_precio);
            $stmt_price_history->bindParam(':precio_actual', $precio);
            $stmt_price_history->execute();
        }

        $pdo->commit();
        $response['success'] = true;
        $response['message'] = 'Producto modificado exitosamente.';

    } catch (PDOException $e) {
        $pdo->rollBack();
        // Captura específica para el error de duplicado (código SQLSTATE 23000)
        if ($e->getCode() == '23000' && strpos($e->getMessage(), 'Duplicate entry') !== false) {
            preg_match("/Duplicate entry '(.*?)' for key 'numero_producto'/i", $e->getMessage(), $matches);
            $duplicate_entry = isset($matches[1]) ? $matches[1] : 'un valor';
            $response['message'] = "El número de producto '{$duplicate_entry}' ya existe. Por favor, elige otro.";
        } else {
            $response['message'] = 'Error al modificar el producto: ' . $e->getMessage();
        }
        error_log("Error al modificar producto: " . $e->getMessage());
    }
} else {
    $response['message'] = 'Acceso no permitido para la modificación del producto.';
}

echo json_encode($response);
exit(); // Es importante usar exit() después de echo json_encode
?>