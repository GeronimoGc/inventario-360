<?php
define('PROTECT_CONFIG', true);
session_start();
require_once '../../assets/config/db.php'; // Asegúrate de que la ruta a db.php sea correcta


// Verificar si el usuario tiene el permiso 'acceso_admin'.
// 'usuario_permisos' debería estar disponible en $_SESSION gracias a op_validar.php.
if (isset($_SESSION['usuario_permisos']) && in_array('acceso_admin', $_SESSION['usuario_permisos'])) {
    $esAdmin = true; // El usuario tiene el permiso de administrador
} else {
    // Si no tiene el permiso 'acceso_admin', redirigir al login.
    $_SESSION['error'] = 'Acceso denegado. No tienes permisos de administrador.';
    header("Location: ../rol_permisos/"); // Redirige al login o a una página de acceso denegado específica.
    exit();
}


header('Content-Type: application/json'); // Indicar que la respuesta es JSON

$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? ''; // CAMBIADO a email
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) { // CAMBIADO a email
        $response['message'] = 'Por favor, ingresa correo electrónico y contraseña.';
        echo json_encode($response);
        exit();
    }

    try {
        // Buscar el usuario por correo electrónico
        // ASEGÚRATE de que tu tabla inventario360_usuario tenga una columna 'correo_electronico'
        // Si no la tiene, tendrás que agregarla a tu base de datos (Inventario360_RBACv3.sql)
        $stmt = $pdo->prepare("SELECT u.idusuario, u.correo, u.contrasenia, r.nombre AS rol_nombre 
                               FROM inventario360_usuario u
                               JOIN inventario360_rol r ON u.rol_id = r.idrol
                               WHERE u.correo = ?"); // CAMBIADO a correo_electronico
        $stmt->execute([$email]); // CAMBIADO a email
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            // Verificar la contraseña
            if (password_verify($password, $usuario['contrasenia'])) {
                // Verificar si el rol es 'administrador'
                if (strtolower($usuario['rol_nombre']) === 'administrador') {
                    $response['success'] = true;
                    $response['message'] = 'Credenciales confirmadas exitosamente.';
                } else {
                    $response['message'] = 'Las credenciales son válidas, pero el usuario no tiene permisos de administrador.';
                }
            } else {
                $response['message'] = 'Contraseña incorrecta.';
            }
        } else {
            $response['message'] = 'Correo electrónico no encontrado o usuario inactivo.'; // Mensaje actualizado
        }

    } catch (PDOException $e) {
        $response['message'] = 'Error de base de datos al verificar credenciales: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Acceso no válido.';
}

echo json_encode($response);
exit();
?>