<?php
define('PROTECT_CONFIG', true);
session_start();
require_once '../../assets/config/db.php';


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

$response = [];

if (isset($_GET['idrol']) && is_numeric($_GET['idrol'])) {
    $idrol = (int)$_GET['idrol'];

    try {
        // Obtener los IDs de permisos asociados a este rol
        $stmt = $pdo->prepare("SELECT permiso_id FROM inventario360_rol_permiso WHERE rol_id = ?");
        $stmt->execute([$idrol]);
        $permisos_rol = $stmt->fetchAll(PDO::FETCH_COLUMN); // Obtener solo la columna 'permiso_id'

        echo json_encode($permisos_rol);

    } catch (PDOException $e) {
        // En caso de error, devolver un array vacío o un mensaje de error apropiado
        echo json_encode(['error' => 'Error al obtener permisos: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'ID de rol no válido.']);
}
exit();
?>