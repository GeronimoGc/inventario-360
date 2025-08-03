<?php
define('PROTECT_CONFIG', true);
session_start();

require_once '../../assets/config/db.php';

require_once '../../assets/config/info.php';
$permisoRequerido = $op_eliminar_proveedor;
if (isset($_SESSION['usuario_permisos']) && in_array($permisoRequerido, $_SESSION['usuario_permisos'])) {
    $esAccesoPermitido = true;
} else {
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => "Acceso denegado. No cuentas con el permiso " . $permisoRequerido . ". Por favor Contacta con tu departamento de IT o administrador de ayudas"
    ];
    header("Location: ../proveedores/");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $idproveedor = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

    if (empty($idproveedor)) {
        $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'ID de proveedor no proporcionado para la eliminación.'];
        header("Location: ./");
        exit();
    }

    try {
        // Primero, obtener el nombre del proveedor para el mensaje de éxito/error
        $stmt_nombre = $pdo->prepare("SELECT nombre FROM inventario360_proveedor WHERE idproveedor = ?");
        $stmt_nombre->execute([$idproveedor]);
        $proveedor = $stmt_nombre->fetch(PDO::FETCH_ASSOC);
        $nombre_proveedor = $proveedor ? $proveedor['nombre'] : 'Proveedor Desconocido';

        // Intentar eliminar
        $stmt = $pdo->prepare("DELETE FROM inventario360_proveedor WHERE idproveedor = ?");
        $stmt->execute([$idproveedor]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['mensaje'] = ['tipo' => 'exito', 'texto' => 'Proveedor "' . htmlspecialchars($nombre_proveedor) . '" eliminado exitosamente.'];
        } else {
            $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'El proveedor "' . htmlspecialchars($nombre_proveedor) . '" no fue encontrado o no se pudo eliminar.'];
        }
        header("Location: ./");
        exit();

    } catch (PDOException $e) {
        // error_log("Error al eliminar proveedor: " . $e->getMessage());
        // Puedes verificar el código de error para errores específicos (ej. FK constraint)
        if ($e->getCode() == '23000') { // Código para violación de integridad referencial
            $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'No se puede eliminar el proveedor porque tiene productos asociados. Primero elimina los productos relacionados.'];
        } else {
            $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Error al eliminar el proveedor: ' . htmlspecialchars($e->getMessage())];
        }
        header("Location: ./");
        exit();
    }
} else {
    $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Acceso no autorizado.'];
    header("Location: ./");
    exit();
}
?>