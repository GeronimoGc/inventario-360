<?php
define('PROTECT_CONFIG', true);
session_start();

header('Content-Type: application/json');

// Verifica si el usuario tiene sesión activa y permisos
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['usuario_data'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Por favor, inicia sesión.']);
    exit();
}

require_once '../../assets/config/info.php';
$permisoRequerido = $op_crear_multiples_productos;
// CAMBIO AQUÍ: En lugar de redirigir, devuelve un JSON de error de permiso
if (!isset($_SESSION['usuario_permisos']) || !in_array($permisoRequerido, $_SESSION['usuario_permisos'])) {
    echo json_encode(['success' => false, 'message' => "Acceso denegado. No cuentas con el permiso " . $permisoRequerido . ". Por favor Contacta con tu departamento de IT o administrador de ayudas."]);
    exit();
}

// Incluir el archivo de conexión a la base de datos
require_once '../../assets/config/db.php'; // Ajusta la ruta si es necesario

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Error al decodificar JSON: ' . json_last_error_msg()]);
    exit();
}

if (!isset($data['products']) || !is_array($data['products'])) {
    echo json_encode(['success' => false, 'message' => 'Datos de productos inválidos.']);
    exit();
}

$productsToInsert = $data['products'];
$insertedCount = 0;
$errors = [];
$usuario_id = $_SESSION['usuario_data']['idusuario']; // Asegúrate de que esto esté disponible en tu sesión

if (empty($productsToInsert)) {
    echo json_encode(['success' => false, 'message' => 'No hay productos para ingresar.']);
    exit();
}

try {
    $pdo->beginTransaction();

    // Preparar la consulta para insertar productos en inventario360_producto
    // ELIMINADO 'idusuario_registro' de aquí
    $stmt_producto = $pdo->prepare("INSERT INTO inventario360_producto (numero_producto, nombre, estado, precio, idcategoria, idbodega, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, NOW())");

    // Preparar la consulta para insertar en inventario360_registro_actividad
    $stmt_registro_actividad = $pdo->prepare("INSERT INTO inventario360_registro_actividad (accion, descripcion, usuario_idusuario, producto_idproducto) VALUES (?, ?, ?, ?)");

    foreach ($productsToInsert as $product) {
        // Validaciones básicas de datos (puedes añadir más)
        if (empty($product['numero_producto']) || empty($product['nombre']) || !isset($product['estado']) || !is_numeric($product['precio']) || empty($product['idcategoria']) || empty($product['idbodega'])) {
            $errors[] = "Datos incompletos para el producto: " . ($product['numero_producto'] ?? 'N/A');
            continue; // Saltar este producto y continuar con el siguiente
        }

        try {
            // Ejecutar la inserción del producto
            $stmt_producto->execute([
                $product['numero_producto'],
                $product['nombre'],
                $product['estado'],
                $product['precio'],
                $product['idcategoria'],
                $product['idbodega']
                // 'idusuario_registro' y 'fecha_registro' ya no están aquí
                // 'fecha_registro' se maneja con NOW() en la consulta SQL
            ]);

            // Obtener el ID del producto recién insertado
            $lastProductId = $pdo->lastInsertId();

            // Insertar el registro de actividad
            $descripcion_actividad = "Producto '" . htmlspecialchars($product['nombre']) . "' (Número: " . htmlspecialchars($product['numero_producto']) . ") creado.";

            // Asegurarse de que $usuario_id esté disponible antes de usarlo
            if ($usuario_id) {
                $stmt_registro_actividad->execute([
                    'INSERT',
                    $descripcion_actividad,
                    $usuario_id, // ID del usuario de la sesión
                    $lastProductId // ID del producto recién insertado
                ]);
            } else {
                $errors[] = "Error: ID de usuario no encontrado en la sesión para registrar actividad del producto '" . htmlspecialchars($product['numero_producto']) . "'.";
                // Considera si esto debería detener el proceso o solo loggear un error.
                // En este caso, simplemente lo añade a los errores pero el producto ya se insertó.
            }

            $insertedCount++;

        } catch (PDOException $e) {
            // Captura duplicados u otros errores específicos de la DB para la inserción del producto
            if ($e->getCode() == '23000') { // Código SQLSTATE para violación de integridad (ej. UNIQUE constraint)
                $errors[] = "Error: El número de producto '" . htmlspecialchars($product['numero_producto']) . "' ya existe.";
            } else {
                $errors[] = "Error al insertar producto '" . htmlspecialchars($product['numero_producto']) . "': " . $e->getMessage();
            }
            // Si hay un error en la inserción del producto, no incrementamos $insertedCount
            // y no intentamos insertar en el registro de actividad para este producto.
        }
    }

    if (empty($errors)) {
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => "Se han ingresado {$insertedCount} producto(s) exitosamente."]);
    } else {
        $pdo->rollBack();
        // Si hay errores, incluso si algunos se insertaron, es mejor hacer rollback para asegurar consistencia
        echo json_encode(['success' => false, 'message' => "Se encontraron errores durante el ingreso de productos: " . implode(", ", $errors) . ". Ningún producto fue ingresado."]);
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error general de base de datos: ' . $e->getMessage()]);
}
?>