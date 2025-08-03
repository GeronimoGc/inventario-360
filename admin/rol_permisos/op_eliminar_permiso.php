<?php
define('PROTECT_CONFIG', true);
session_start();
require_once '../../assets/config/db.php';


    require_once '../../assets/config/info.php';
// Verificar si el usuario tiene el permiso 'acceso_admin'.
// 'usuario_permisos' debería estar disponible en $_SESSION gracias a op_validar.php.
if (isset($_SESSION['usuario_permisos']) && in_array($op_eliminar_permiso, $_SESSION['usuario_permisos'])) {
    $esAdmin = true; // El usuario tiene el permiso de administrador
} else {
    // Si no tiene el permiso 'acceso_view_gestor_usuario', redirigir al login y mostrar el aviso.
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => "Acceso denegado. No cuentas con el permiso " . $op_eliminar_permiso . ". Por favor Contacta con tu departamente de IT o administrador de ayudas"
    ];
    header("Location: ../rol_permisos/"); // Redirige al login o a una página de acceso denegado específica.
    exit();
}


header('Content-Type: application/json'); // Indicar que la respuesta es JSON

$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idpermiso = $_POST['idpermiso'] ?? 0;

    if (empty($idpermiso)) {
        $response['message'] = 'ID de permiso no proporcionado.';
        echo json_encode($response);
        exit();
    }

    try {
        $pdo->beginTransaction();

        // Obtener el nombre del permiso antes de eliminar
        $stmt_get_permiso_name = $pdo->prepare("SELECT nombre FROM inventario360_permiso WHERE idpermiso = ?");
        $stmt_get_permiso_name->execute([$idpermiso]);
        $permiso_name = $stmt_get_permiso_name->fetchColumn();

        // Los permisos están relacionados con roles a través de `inventario360_rol_permiso`.
        // Según tu esquema, hay `ON DELETE CASCADE` en `fk_rol_permiso_permiso`,
        // lo que significa que las entradas en `inventario360_rol_permiso` se eliminarán automáticamente.
        // No es necesario eliminar manualmente aquí.

        $stmt = $pdo->prepare("DELETE FROM inventario360_permiso WHERE idpermiso = ?");
        if ($stmt->execute([$idpermiso])) {
            $pdo->commit();
            $response['success'] = true;
            $response['message'] = 'Permiso "' . htmlspecialchars($permiso_name) . '" eliminado exitosamente. Se ha desasociado de todos los roles.';
        } else {
            $pdo->rollBack();
            $response['message'] = 'Error al eliminar el permiso.';
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $response['message'] = 'Error de base de datos al eliminar el permiso: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Acceso no válido.';
}

echo json_encode($response);
exit();
?>