<?php
define('PROTECT_CONFIG', true);
session_start();
require_once '../../assets/config/db.php';

    require_once '../../assets/config/info.php';
// Verificar si el usuario tiene el permiso 'acceso_admin'.
// 'usuario_permisos' debería estar disponible en $_SESSION gracias a op_validar.php.
if (isset($_SESSION['usuario_permisos']) && in_array($op_crear_permiso, $_SESSION['usuario_permisos'])) {
    $esAdmin = true; // El usuario tiene el permiso de administrador
} else {
    // Si no tiene el permiso 'acceso_view_gestor_usuario', redirigir al login y mostrar el aviso.
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => "Acceso denegado. No cuentas con el permiso " . $$op_crear_permiso . ". Por favor Contacta con tu departamente de IT o administrador de ayudas"
    ];
    header("Location: ../rol_permisos/"); // Redirige al login o a una página de acceso denegado específica.
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $estado = $_POST['estado'] ?? 'activo';

    if (empty($nombre) || empty($descripcion)) {
        $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'El nombre y la descripción del permiso no pueden estar vacíos.'];
        header("Location: index.php");
        exit();
    }

    try {
        // Verificar si el nombre del permiso ya existe
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM inventario360_permiso WHERE nombre = ?");
        $stmt_check->execute([$nombre]);
        if ($stmt_check->fetchColumn() > 0) {
            $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'El nombre de permiso "' . htmlspecialchars($nombre) . '" ya existe.'];
            header("Location: index.php");
            exit();
        }

        $stmt = $pdo->prepare("INSERT INTO inventario360_permiso (nombre, descripcion, estado) VALUES (?, ?, ?)");
        if ($stmt->execute([$nombre, $descripcion, $estado])) {
            $_SESSION['mensaje'] = ['tipo' => 'exito', 'texto' => 'Permiso "' . htmlspecialchars($nombre) . '" creado exitosamente.'];
        } else {
            $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Error al crear el permiso.'];
        }
    } catch (PDOException $e) {
        $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Error de base de datos al crear el permiso: ' . $e->getMessage()];
    }
} else {
    $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Acceso no válido.'];
}

header("Location: index.php");
exit();
?>