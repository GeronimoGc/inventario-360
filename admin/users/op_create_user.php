<?php
session_start();
define('PROTECT_CONFIG', true);


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
if (isset($_SESSION['usuario_permisos']) && in_array($op_create_user, $_SESSION['usuario_permisos'])) {
    $esAdmin = true; // El usuario tiene el permiso de administrador
} else {
    // Si no tiene el permiso 'acceso_view_gestor_usuario', redirigir al login y mostrar el aviso.
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => "Acceso denegado. No cuentas con el permiso " . $op_create_user . ". Por favor Contacta con tu departamente de IT o administrador de ayudas"
    ];
    header("Location: ../users/"); // Redirige al login o a una página de acceso denegado específica.
    exit();
}

// A partir de aquí, el usuario está autenticado y es un administrador.
// Puedes usar `$usuario_actual['nombre']`, `$usuario_actual['correo']`, etc.,
// para mostrar información personalizada en el encabezado o en el contenido de la página.

// Ejemplo de uso en el HTML (dentro del <body>):
/*
<header class="bg-white shadow-sm">
    <div class="flex items-center">
        <i class="fas fa-user-circle"></i>
        <span><?php echo htmlspecialchars($usuario_actual['nombre']); ?></span>
        <span><?php echo htmlspecialchars($usuario_actual['rol_nombre']); ?></span>
        <a href="../../op_logout.php">Cerrar sesión</a>
    </div>
</header>
*/


require_once '../../assets/config/db.php'; // Asegúrate de que esta ruta sea correcta

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validar y sanear las entradas del usuario
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $contrasena_plana = $_POST['contrasena'] ?? ''; // La contraseña en texto plano
    $rol_id = (int)($_POST['rol_id'] ?? 0);

    // Validación básica de campos
    if (empty($nombre) || empty($correo) || empty($contrasena_plana) || $rol_id === 0) {
        // Redirigir con un mensaje de error si faltan campos
        $_SESSION['mensaje_error'] = "Todos los campos son obligatorios.";
        header("Location: ../../admin/users/"); // Asume que el listado de usuarios está en gestion_usuarios.php
        exit();
    }

    // Validación de formato de correo electrónico
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['mensaje_error'] = "El formato del correo electrónico es inválido.";
        header("Location: ../../admin/users/");
        exit();
    }

    // Hashear la contraseña de forma segura con password_hash()
    // PASSWORD_DEFAULT es el algoritmo de hash actual y recomendado (actualmente BCRYPT).
    $contrasena_hasheada = password_hash($contrasena_plana, PASSWORD_DEFAULT);

    // Si por alguna razón password_hash falla (ej. memoria insuficiente), puedes manejarlo.
    if ($contrasena_hasheada === false) {
        $_SESSION['mensaje_error'] = "Error al hashear la contraseña. Inténtalo de nuevo.";
        header("Location: ../../admin/users/");
        exit();
    }

    try {
        // Verificar si el correo electrónico ya existe
        $stmt_check_email = $pdo->prepare("SELECT COUNT(*) FROM inventario360_usuario WHERE correo = :correo");
        $stmt_check_email->execute([':correo' => $correo]);
        if ($stmt_check_email->fetchColumn() > 0) {
            $_SESSION['mensaje_error'] = "El correo electrónico ya está registrado.";
            header("Location: ../../admin/users/");
            exit();
        }

        // Insertar el nuevo usuario
        $sql = "INSERT INTO inventario360_usuario (nombre, correo, contrasenia, rol_id) VALUES (:nombre, :correo, :contrasena, :rol_id)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre' => $nombre,
            ':correo' => $correo,
            ':contrasena' => $contrasena_hasheada,
            ':rol_id' => $rol_id
        ]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['mensaje_exito'] = "Usuario '{$nombre}' creado exitosamente.";
        } else {
            $_SESSION['mensaje_error'] = "No se pudo crear el usuario. Inténtalo de nuevo.";
        }
    } catch (PDOException $e) {
        $_SESSION['mensaje_error'] = "Error de base de datos al crear el usuario: " . $e->getMessage();
    }
} else {
    // Si la solicitud no es POST, redirigir o mostrar un error
    $_SESSION['mensaje_error'] = "Acceso no autorizado.";
}

// Redirigir de vuelta a la página de gestión de usuarios
header("Location: ../../admin/users/");
exit();
?>