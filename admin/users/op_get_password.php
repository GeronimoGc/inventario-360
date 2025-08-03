<?php
session_start();
define('PROTECT_CONFIG', true);

header('Content-Type: application/json');

// SOLO PARA DESARROLLO: Muestra errores de PHP directamente
// En producción, es recomendable desactivar esto y usar un registro de errores.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluye el archivo de configuración de la base de datos.
// Se asume que este script está en la misma carpeta que index.php,
// por lo que '../assets/config/db.php' es la ruta correcta.
require_once '../../assets/config/db.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método de solicitud no permitido.';
    echo json_encode($response);
    exit();
}

// Obtener los datos del cuerpo de la solicitud JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$adminEmail = $data['admin_email'] ?? '';
$adminPassword = $data['admin_password'] ?? '';
$userIdToView = $data['user_id'] ?? 0;

if (empty($adminEmail) || empty($adminPassword) || empty($userIdToView)) {
    $response['message'] = 'Datos incompletos para la validación.';
    echo json_encode($response);
    exit();
}

try {
    // Verificar que la conexión PDO esté disponible
    if (!isset($pdo) || !$pdo instanceof PDO) {
        throw new PDOException("La conexión PDO no está disponible.");
    }

    // 1. Validar las credenciales del administrador (correo y contraseña)
    $stmt_admin = $pdo->prepare("
        SELECT
            u.idusuario, u.nombre, u.correo, u.contrasenia, r.nombre as rol_nombre
        FROM
            inventario360_usuario u
        JOIN
            inventario360_rol r ON u.rol_id = r.idrol
        WHERE
            u.correo = ? AND r.nombre = 'Administrador'
    ");
    $stmt_admin->execute([$adminEmail]);
    $admin_user = $stmt_admin->fetch(PDO::FETCH_ASSOC);

    if (!$admin_user || !password_verify($adminPassword, $admin_user['contrasenia'])) {
        $response['message'] = 'Credenciales de administrador incorrectas o el usuario no es un administrador.';
        echo json_encode($response);
        exit();
    }

    // 2. Si el administrador es válido, obtener la contraseña del usuario solicitado
    // ADVERTENCIA DE SEGURIDAD: Aquí se está obteniendo la contraseña hasheada.
    // Si realmente quieres la contraseña en texto plano, significa que no está hasheada
    // en la base de datos, lo cual es una VULNERACIÓN DE SEGURIDAD CRÍTICA.
    // Asumo que tu intención es mostrar el hash almacenado o, si no está hasheado,
    // es un sistema de desarrollo.
    $stmt_user_password = $pdo->prepare("
        SELECT
            contrasenia
        FROM
            inventario360_usuario
        WHERE
            idusuario = ?
    ");
    $stmt_user_password->execute([$userIdToView]);
    $user_password_data = $stmt_user_password->fetch(PDO::FETCH_ASSOC);

    if ($user_password_data) {
        $response['success'] = true;
        // Aquí se devuelve la contraseña tal como está almacenada en la DB.
        // Si la contrasenia está hasheada con password_hash(), lo que se verá es el hash.
        // Si no está hasheada (mal), se verá el texto plano.
        $response['password'] = $user_password_data['contrasenia'];
    } else {
        $response['message'] = 'Usuario no encontrado.';
    }

} catch (PDOException $e) {
    error_log('Error en op_get_password.php: ' . $e->getMessage());
    $response['message'] = 'Error interno del servidor. Inténtelo más tarde.';
    // Para depuración, puedes descomentar:
    // $response['message'] .= ' (DEBUG: ' . $e->getMessage() . ')';
}

echo json_encode($response);
exit();
?>