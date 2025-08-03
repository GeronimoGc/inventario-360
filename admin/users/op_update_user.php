<?php
// admin/users/index.php (o cualquier página protegida)

session_start(); // Inicia la sesión PHP

define('PROTECT_CONFIG', true); // Bandera para proteger la inclusión de archivos de configuración

// --- 1. Verificación de Autenticación General ---
// Comprueba si la bandera 'logged_in' de la sesión está activa.
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Si no hay sesión activa, redirige al usuario a la página de login (normalmente la raíz).
    header("Location: ../../"); // Ajusta la ruta relativa según la ubicación del archivo
    exit(); // Detiene la ejecución del script
}

// --- 2. Carga de Datos del Usuario Actual ---
// Accede a los datos completos del usuario guardados en $_SESSION['usuario_data']
// durante el proceso de login en op_validar.php.
if (!isset($_SESSION['usuario_data']) || !is_array($_SESSION['usuario_data'])) {
    // Si los datos del usuario no están disponibles (situación inusual pero posible),
    // se considera un error de sesión y se redirige.
    $_SESSION['error'] = 'Error de sesión. Por favor, inicie sesión de nuevo.';
    header("Location: ../../"); // Redirige al login
    exit();
}

$usuario_actual = $_SESSION['usuario_data']; // Aquí se obtienen todos los datos del usuario actual

    require_once '../../assets/config/info.php';
// Verificar si el usuario tiene el permiso 'acceso_admin'.
// 'usuario_permisos' debería estar disponible en $_SESSION gracias a op_validar.php.
if (isset($_SESSION['usuario_permisos']) && in_array($op_update_user, $_SESSION['usuario_permisos'])) {
    $esAdmin = true; // El usuario tiene el permiso de administrador
} else {
    // Si no tiene el permiso 'acceso_view_gestor_usuario', redirigir al login y mostrar el aviso.
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => "Acceso denegado. No cuentas con el permiso " . $op_update_user . ". Por favor Contacta con tu departamente de IT o administrador de ayudas"
    ];
    header("Location: ../users/"); // Redirige al login o a una página de acceso denegado específica.
    exit();
}

require_once '../../assets/config/db.php'; // Asegúrate de que esta ruta sea correcta

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validar y sanear las entradas
    $id_usuario = (int)($_POST['id_usuario'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $rol_id = (int)($_POST['rol_id'] ?? 0);
    $contrasena_plana = $_POST['contrasena'] ?? ''; // La contraseña en texto plano, puede estar vacía

    // Validación básica de campos
    if ($id_usuario === 0 || empty($nombre) || empty($correo) || $rol_id === 0) {
        $_SESSION['mensaje_error'] = "Todos los campos obligatorios (ID, Nombre, Correo, Rol) deben estar completos.";
        header("Location: ../../admin/users/");
        exit();
    }

    // Validación de formato de correo electrónico
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['mensaje_error'] = "El formato del correo electrónico es inválido.";
        header("Location: ../../admin/users/");
        exit();
    }

    try {
        // 1. Verificar si el correo electrónico ya existe para OTRO usuario
        // Esto es importante para evitar que un usuario cambie su correo a uno que ya está en uso por otro.
        $stmt_check_email = $pdo->prepare("SELECT COUNT(*) FROM inventario360_usuario WHERE correo = :correo AND idusuario != :idusuario");
        $stmt_check_email->execute([':correo' => $correo, ':idusuario' => $id_usuario]);
        if ($stmt_check_email->fetchColumn() > 0) {
            $_SESSION['mensaje_error'] = "El correo electrónico ya está registrado para otro usuario.";
            header("Location: ../../admin/users/");
            exit();
        }

        // Construir la consulta SQL dinámicamente
        $sql = "UPDATE inventario360_usuario SET nombre = :nombre, correo = :correo, rol_id = :rol_id";
        $params = [
            ':nombre' => $nombre,
            ':correo' => $correo,
            ':rol_id' => $rol_id,
            ':idusuario' => $id_usuario
        ];

        // Si se proporcionó una nueva contraseña, hashearla y añadirla a la consulta
        if (!empty($contrasena_plana)) {
            $contrasena_hasheada = password_hash($contrasena_plana, PASSWORD_DEFAULT);
            if ($contrasena_hasheada === false) {
                $_SESSION['mensaje_error'] = "Error al hashear la nueva contraseña. Inténtalo de nuevo.";
                header("Location: ../../admin/users/");
                exit();
            }
            $sql .= ", contrasenia = :contrasena";
            $params[':contrasena'] = $contrasena_hasheada;
        }

        $sql .= " WHERE idusuario = :idusuario";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() > 0) {
            $_SESSION['mensaje_exito'] = "Usuario '{$nombre}' actualizado exitosamente.";
        } else {
            // No se actualizaron filas. Podría ser que no se cambió nada o que el usuario no existe.
            // Considera si quieres mostrar un mensaje diferente si no se cambió nada.
            $_SESSION['mensaje_aviso'] = "No se realizaron cambios en el usuario o el usuario no existe.";
        }
    } catch (PDOException $e) {
        $_SESSION['mensaje_error'] = "Error de base de datos al actualizar el usuario: " . $e->getMessage();
    }
} else {
    // Si la solicitud no es POST, redirigir o mostrar un error
    $_SESSION['mensaje_error'] = "Acceso no autorizado.";
}

// Redirigir de vuelta a la página de gestión de usuarios
header("Location: ../../admin/users/");
exit();
?>