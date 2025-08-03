<?php

define('PROTECT_CONFIG', true); // Asegúrate de que tu db.php respete esto

session_start();

header('Content-Type: application/json'); // Asegurarse de que la respuesta sea JSON
$response = ['success' => false, 'message' => ''];

// Redirige si no hay sesión activa o si no es administrador (doble verificación de seguridad)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['usuario_data'])) {
    $response['message'] = 'Acceso denegado. Por favor, inicia sesión.';
    echo json_encode($response);
    exit();
}

$usuario = $_SESSION['usuario_data'];


require_once '../../assets/config/info.php';
$permisoRequerido = $op_crear_producto;
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

// Incluir el archivo de conexión a la base de datos
// Asegúrate de que esta ruta sea correcta para acceder a db.php
require_once '../../assets/config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y limpiar los datos de entrada
    $numero_producto = filter_input(INPUT_POST, 'numero_producto', FILTER_SANITIZE_STRING);
    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
    $estado = filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_STRING);
    $precio = filter_input(INPUT_POST, 'precio', FILTER_VALIDATE_FLOAT);
    $idcategoria = filter_input(INPUT_POST, 'idcategoria', FILTER_VALIDATE_INT);
    $idbodega = filter_input(INPUT_POST, 'idbodega', FILTER_VALIDATE_INT);

    // Obtener el ID del usuario de la sesión para el registro de actividad
    // Asegúrate de que 'idusuario' esté disponible en $_SESSION['usuario_data']
    $usuario_id = isset($usuario['idusuario']) ? $usuario['idusuario'] : null;

    // Validar que los campos obligatorios no estén vacíos/inválidos
    if (!$numero_producto || !$nombre || !$estado || $precio === false || $idcategoria === false || $idbodega === false || $usuario_id === null) {
        $response['message'] = 'Todos los campos obligatorios deben ser válidos y el ID de usuario debe estar disponible.';
        echo json_encode($response);
        exit();
    }

    try {
        $pdo->beginTransaction(); // Iniciar transacción

        // Insertar el nuevo producto
        // La sentencia INSERT para producto está correcta, no incluye fecha_registro porque tiene un DEFAULT
        $stmt = $pdo->prepare("INSERT INTO inventario360_producto (numero_producto, nombre, estado, precio, idcategoria, idbodega) VALUES (:numero_producto, :nombre, :estado, :precio, :idcategoria, :idbodega)");

        $stmt->bindParam(':numero_producto', $numero_producto);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':estado', $estado);
        $stmt->bindParam(':precio', $precio);
        $stmt->bindParam(':idcategoria', $idcategoria);
        $stmt->bindParam(':idbodega', $idbodega);

        if ($stmt->execute()) {
            $producto_id_insertado = $pdo->lastInsertId();

            // --- CORRECCIÓN AQUÍ ---
            // La tabla inventario360_registro_actividad tiene una columna 'fecha' con DEFAULT CURRENT_TIMESTAMP.
            // Si la incluyes en la lista de columnas, debes pasarle un valor (CURRENT_TIMESTAMP para usar el valor por defecto).
            // Si no la incluyes en la lista de columnas, la BD la llenará automáticamente.
            // La forma más sencilla y correcta es NO incluir 'fecha' en la lista de columnas si quieres que use el valor por defecto.
            $descripcion_registro = "Producto creado: Número: '{$numero_producto}', Nombre: '{$nombre}', Estado: '{$estado}', Precio: '{$precio}', Categoría ID: '{$idcategoria}', Bodega ID: '{$idbodega}'";

            $stmt_log = $pdo->prepare("INSERT INTO inventario360_registro_actividad (accion, descripcion, usuario_idusuario, producto_idproducto) VALUES ('INSERT', :descripcion, :usuario_id, :producto_id)");
            $stmt_log->bindParam(':descripcion', $descripcion_registro);
            $stmt_log->bindParam(':usuario_id', $usuario_id);
            $stmt_log->bindParam(':producto_id', $producto_id_insertado);
            $stmt_log->execute();

            $pdo->commit(); // Confirmar transacción
            $response['success'] = true;
            $response['message'] = 'Producto ingresado exitosamente.';
        } else {
            $pdo->rollBack(); // Revertir transacción si la inserción falló
            $response['message'] = 'Error desconocido al ingresar el producto.';
        }

    } catch (PDOException $e) {
        $pdo->rollBack(); // Revertir transacción en caso de excepción
        // Captura específica para el error de duplicado (código SQLSTATE 23000)
        if ($e->getCode() == '23000') { // Código de error para entrada duplicada (ej: numero_producto único)
            // Extraer el valor duplicado del mensaje de error para hacerlo más específico
            preg_match("/Duplicate entry '(.*?)' for key '(.*?)'/i", $e->getMessage(), $matches);
            $duplicate_value = isset($matches[1]) ? $matches[1] : 'un valor';
            $key_name = isset($matches[2]) ? $matches[2] : 'una clave';
            
            if ($key_name == 'numero_producto_UNIQUE') { // Usa el nombre exacto de la clave única
                $response['message'] = "El número de producto '{$duplicate_value}' ya existe. Por favor, elige otro.";
            } else {
                $response['message'] = "Entrada duplicada para '{$key_name}' con valor '{$duplicate_value}'.";
            }
        } else if ($e->getCode() == '22003') { // Código de error para 'Numeric value out of range'
            $response['message'] = 'Error en el precio: el valor es demasiado grande o inválido para la columna. Verifica que el precio no exceda $99,999,999.99.';
        }
        else {
            $response['message'] = 'Error en la base de datos al ingresar el producto: ' . $e->getMessage();
        }
        error_log("Error al crear producto: " . $e->getMessage()); // Para depuración en el log del servidor
    }
} else {
    $response['message'] = 'Acceso no permitido para la creación del producto.';
}

echo json_encode($response);
exit();
?>