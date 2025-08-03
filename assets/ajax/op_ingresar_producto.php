<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Acceso no autorizado.']);
    exit();
}

if (!defined('PROTECT_CONFIG')) {
    define('PROTECT_CONFIG', true);
}
require_once __DIR__ . '/../config/db.php';

if (!isset($pdo) || $pdo === null) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Error al conectar con la base de datos.', 'details' => $dbError ?? 'No Pdo object']);
    exit();
}

header('Content-Type: application/json');
$response = ['success' => false, 'message' => '', 'data' => null];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['accion']) && $_GET['accion'] === 'cargar_datos_formulario') {
    try {
        // Cargar categorías
        $stmt_cat = $pdo->query("SELECT idcategoria, nombre FROM inventario360_categoria ORDER BY nombre ASC");
        $categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

        // Cargar bodegas
        $stmt_bod = $pdo->query("SELECT idbodega, nombre FROM inventario360_bodega ORDER BY nombre ASC");
        $bodegas = $stmt_bod->fetchAll(PDO::FETCH_ASSOC);

        $response['success'] = true;
        $response['data'] = [
            'categorias' => $categorias,
            'bodegas' => $bodegas
        ];
    } catch (PDOException $e) {
        $response['message'] = 'Error al cargar datos para el formulario: ' . $e->getMessage();
    }
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recibir y sanitizar datos (ejemplo básico)
    $numero_producto = trim($_POST['numero_producto'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $estado = $_POST['estado'] ?? '';
    $precio = filter_var($_POST['precio'] ?? '', FILTER_VALIDATE_FLOAT);
    $idcategoria = filter_var($_POST['idcategoria'] ?? '', FILTER_VALIDATE_INT);
    $idbodega = filter_var($_POST['idbodega'] ?? '', FILTER_VALIDATE_INT);
    $fecha_registro = date('Y-m-d'); // Fecha actual

    // Validaciones básicas
    if (empty($numero_producto)) {
        $response['message'] = 'El número de producto es obligatorio.';
    } elseif (empty($nombre)) {
        $response['message'] = 'El nombre del producto es obligatorio.';
    } elseif (!in_array($estado, ['activo', 'inactivo', 'averiado'])) {
        $response['message'] = 'Estado no válido.';
    } elseif ($precio === false || $precio <= 0) {
        $response['message'] = 'Precio no válido.';
    } elseif ($idcategoria === false || $idcategoria <= 0) {
        $response['message'] = 'Categoría no válida.';
    } elseif ($idbodega === false || $idbodega <= 0) {
        $response['message'] = 'Bodega no válida.';
    } else {
        try {
            // Verificar si el numero_producto ya existe
            $stmt_check = $pdo->prepare("SELECT idproducto FROM inventario360_producto WHERE numero_producto = :numero_producto");
            $stmt_check->bindParam(':numero_producto', $numero_producto, PDO::PARAM_STR);
            $stmt_check->execute();
            if ($stmt_check->fetch()) {
                $response['message'] = 'El número de producto ya existe.';
            } else {
                $sql = "INSERT INTO inventario360_producto 
                            (numero_producto, nombre, estado, fecha_registro, precio, idcategoria, idbodega) 
                        VALUES 
                            (:numero_producto, :nombre, :estado, :fecha_registro, :precio, :idcategoria, :idbodega)";
                $stmt = $pdo->prepare($sql);

                $stmt->bindParam(':numero_producto', $numero_producto, PDO::PARAM_STR);
                $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
                $stmt->bindParam(':estado', $estado, PDO::PARAM_STR);
                $stmt->bindParam(':fecha_registro', $fecha_registro, PDO::PARAM_STR);
                $stmt->bindParam(':precio', $precio, PDO::PARAM_STR); // PDO maneja decimales como strings
                $stmt->bindParam(':idcategoria', $idcategoria, PDO::PARAM_INT);
                $stmt->bindParam(':idbodega', $idbodega, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Producto ingresado correctamente.';
                    $response['producto_id'] = $pdo->lastInsertId();
                } else {
                    $response['message'] = 'Error al ingresar el producto.';
                }
            }
        } catch (PDOException $e) {
            // Código de error para duplicados (puede variar según el motor de BD)
            if ($e->getCode() == 23000) { // Generalmente error de integridad (UNIQUE constraint)
                $response['message'] = 'Error: El número de producto ya existe o hay un conflicto de datos.';
            } else {
                $response['message'] = 'Error en la base de datos: ' . $e->getMessage();
            }
        }
    }
} else {
    $response['message'] = 'Método no permitido o acción no especificada.';
}

echo json_encode($response);
?>