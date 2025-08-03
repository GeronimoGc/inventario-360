<?php
define('PROTECT_CONFIG', true);
session_start();
require_once '../../assets/config/db.php';

    require_once '../../assets/config/info.php';
// Verificar si el usuario tiene el permiso 'acceso_admin'.
// 'usuario_permisos' debería estar disponible en $_SESSION gracias a op_validar.php.
if (isset($_SESSION['usuario_permisos']) && in_array($op_eliminar_rol, $_SESSION['usuario_permisos'])) {
    $esAdmin = true; // El usuario tiene el permiso de administrador
} else {
    // Si no tiene el permiso 'acceso_view_gestor_usuario', redirigir al login y mostrar el aviso.
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => "Acceso denegado. No cuentas con el permiso " . $op_eliminar_rol . ". Por favor Contacta con tu departamente de IT o administrador de ayudas"
    ];
    header("Location: ../rol_permisos/"); // Redirige al login o a una página de acceso denegado específica.
    exit();
}

header('Content-Type: application/json'); // Indicar que la respuesta es JSON

$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idrol = $_POST['idrol'] ?? 0;

    if (empty($idrol)) {
        $response['message'] = 'ID de rol no proporcionado.';
        echo json_encode($response);
        exit();
    }

    try {
        $pdo->beginTransaction();

        // Obtener el nombre del rol antes de eliminar para el mensaje
        $stmt_get_rol_name = $pdo->prepare("SELECT nombre FROM inventario360_rol WHERE idrol = ?");
        $stmt_get_rol_name->execute([$idrol]);
        $rol_name = $stmt_get_rol_name->fetchColumn();

        // Prevenir la eliminación del rol "Administrador" si su ID es conocido (o por nombre)
        if (strtolower($rol_name) === 'administrador') {
            $response['message'] = 'No se puede eliminar el rol "Administrador".';
            echo json_encode($response);
            exit();
        }

        // Tu esquema SQL usa ON DELETE CASCADE para fk_usuario_rol y fk_rol_permiso_rol.
        // Esto significa que los usuarios y las asignaciones de permisos a este rol se eliminarán automáticamente.
        // Si no fuera así, deberías manejar aquí la reasignación o eliminación de usuarios.

        $stmt = $pdo->prepare("DELETE FROM inventario360_rol WHERE idrol = ?");
        if ($stmt->execute([$idrol])) {
            $pdo->commit();
            $response['success'] = true;
            $response['message'] = 'Rol "' . htmlspecialchars($rol_name) . '" eliminado exitosamente.';
        } else {
            $pdo->rollBack();
            $response['message'] = 'Error al eliminar el rol.';
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        // SQLSTATE 23000 es para violaciones de integridad referencial (ej. si hay usuarios asociados y no hay CASCADE)
        if ($e->getCode() == 23000) {
            $response['message'] = 'No se puede eliminar el rol porque tiene usuarios asociados. Reasigne los usuarios primero.';
        } else {
            $response['message'] = 'Error de base de datos al eliminar el rol: ' . $e->getMessage();
        }
    }
} else {
    $response['message'] = 'Acceso no válido.';
}

echo json_encode($response);
exit();
?>