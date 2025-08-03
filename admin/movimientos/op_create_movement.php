<?php
session_start();
define('PROTECT_CONFIG', true);
header('Content-Type: application/json'); // Asegura que la respuesta es JSON

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Método no permitido.';
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    header("Location: ../movimientos/");
    exit();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['usuario_data'])) {
    $_SESSION['error'] = 'Acceso denegado. Por favor, inicie sesión.';
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    header("Location: ../../");
    exit();
}

require_once '../../assets/config/info.php';
$permisoRequerido = $op_create_movement;
if (isset($_SESSION['usuario_permisos']) && in_array($permisoRequerido, $_SESSION['usuario_permisos'])) {
    $esAccesoPermitido = true;
} else {
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => "Acceso denegado. No cuentas con el permiso " . $permisoRequerido . ". Por favor Contacta con tu departamento de IT o administrador de ayudas"
    ];
    header("Location: ../movimientos/");
    exit();
}

require_once '../../assets/config/db.php';

if (!isset($pdo) || !$pdo instanceof PDO) {
    $_SESSION['error'] = 'Error de conexión a la base de datos.';
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
    header("Location: ../movimientos/");
    exit();
}

try {
    $tipo_movimiento_id = $_POST['tipo_movimiento_id'] ?? null;
    $descripcion = $_POST['descripcion'] ?? '';
    $documento_referencia = $_POST['documento_referencia'] ?? null;
    $bodega_origen_id = $_POST['bodega_origen_id'] ?? null;
    $bodega_destino_id = $_POST['bodega_destino_id'] ?? null;
    $estado_movimiento = $_POST['estado_movimiento'] ?? 'abierto';
    $usuario_id = $_POST['usuario_id'] ?? null; // ID del usuario logueado

    // Validaciones básicas
    if (empty($tipo_movimiento_id) || empty($usuario_id)) {
        $_SESSION['error'] = 'Tipo de movimiento y usuario son obligatorios.';
        header("Location: ../movimientos/");
        exit();
    }

    // Asegurarse de que los IDs de bodega sean null si están vacíos, no '0' o cadena vacía.
    $bodega_origen_id = !empty($bodega_origen_id) ? (int)$bodega_origen_id : null;
    $bodega_destino_id = !empty($bodega_destino_id) ? (int)$bodega_destino_id : null;


    $stmt = $pdo->prepare("INSERT INTO inventario360_movimientos (tipo_movimiento_id, fecha_movimiento, usuario_id, bodega_origen_id, bodega_destino_id, descripcion, documento_referencia, estado_movimiento) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $tipo_movimiento_id,
        $usuario_id,
        $bodega_origen_id,
        $bodega_destino_id,
        $descripcion,
        $documento_referencia,
        $estado_movimiento
    ]);

    $_SESSION['mensaje_exito'] = 'Movimiento creado exitosamente.';
    header("Location: ../movimientos/");
    exit();

} catch (PDOException $e) {
    $_SESSION['error'] = 'Error al crear el movimiento: ' . $e->getMessage();
    header("Location: ../movimientos/");
    exit();
}
?>