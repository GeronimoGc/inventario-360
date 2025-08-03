<?php
define('PROTECT_CONFIG', true);
session_start();
require_once '../../assets/config/db.php';

    require_once '../../assets/config/info.php';
// Verificar si el usuario tiene el permiso 'acceso_admin'.
// 'usuario_permisos' debería estar disponible en $_SESSION gracias a op_validar.php.
if (isset($_SESSION['usuario_permisos']) && in_array($op_editar_rol, $_SESSION['usuario_permisos'])) {
    $esAdmin = true; // El usuario tiene el permiso de administrador
} else {
    // Si no tiene el permiso 'acceso_view_gestor_usuario', redirigir al login y mostrar el aviso.
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => "Acceso denegado. No cuentas con el permiso " . $op_editar_rol . ". Por favor Contacta con tu departamente de IT o administrador de ayudas"
    ];
    header("Location: ../rol_permisos/"); // Redirige al login o a una página de acceso denegado específica.
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idrol = $_POST['idrol'] ?? 0;
    $nombre = $_POST['nombre'] ?? '';
    $estado = $_POST['estado'] ?? 'activo';
    $permisos_seleccionados = $_POST['permisos'] ?? []; // Array de IDs de permisos

    if (empty($idrol) || empty($nombre)) {
        $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Datos incompletos para actualizar el rol.'];
        header("Location: index.php");
        exit();
    }

    try {
        $pdo->beginTransaction();

        // 1. Actualizar la información del rol
        $stmt_rol = $pdo->prepare("UPDATE inventario360_rol SET nombre = ?, estado = ? WHERE idrol = ?");
        $stmt_rol->execute([$nombre, $estado, $idrol]);

        // 2. Sincronizar permisos del rol
        // Primero, eliminar todos los permisos existentes para este rol
        $stmt_delete_permisos = $pdo->prepare("DELETE FROM inventario360_rol_permiso WHERE rol_id = ?");
        $stmt_delete_permisos->execute([$idrol]);

        // Luego, insertar los permisos seleccionados
        if (!empty($permisos_seleccionados)) {
            $sql_insert_permiso = "INSERT INTO inventario360_rol_permiso (rol_id, permiso_id) VALUES (?, ?)";
            $stmt_insert_permiso = $pdo->prepare($sql_insert_permiso);
            foreach ($permisos_seleccionados as $permiso_id) {
                $stmt_insert_permiso->execute([$idrol, $permiso_id]);
            }
        }

        $pdo->commit();
        $_SESSION['mensaje'] = ['tipo' => 'exito', 'texto' => 'Rol "' . htmlspecialchars($nombre) . '" actualizado exitosamente.'];

    } catch (PDOException $e) {
        $pdo->rollBack();
        if ($e->getCode() == 23000) { // Código de error para duplicado (SQLSTATE)
            $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Error: El nombre de rol "' . htmlspecialchars($nombre) . '" ya existe.'];
        } else {
            $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Error de base de datos al actualizar el rol: ' . $e->getMessage()];
        }
    }
} else {
    $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Acceso no válido.'];
}

header("Location: index.php");
exit();
?>