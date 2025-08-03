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
$usuario = $_SESSION['usuario_data'];


require_once '../../assets/config/info.php';
$permisoRequerido = $op_crear_categoria;
if (isset($_SESSION['usuario_permisos']) && in_array($permisoRequerido, $_SESSION['usuario_permisos'])) {
    $esAccesoPermitido = true;
} else {
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => "Acceso denegado. No cuentas con el permiso " . $permisoRequerido . ". Por favor Contacta con tu departamento de IT o administrador de ayudas"
    ];
    header("Location: ../categoria/");
    exit();
}


require_once '../../assets/config/db.php';
 // Verificación de rol admin

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Saneamiento y validación de las entradas del usuario
    // FILTER_SANITIZE_STRING es obsoleto en PHP 8.1+. Para versiones más recientes, considera:
    // filter_var($string, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    // Para simplificar y mantener compatibilidad en sistemas heredados, lo mantendré como estaba,
    // pero sé consciente de esto. Para PHP 8.1+, simplemente castear a string es una opción,
    // o usar htmlspecialchars para la salida.
    $nombre_categoria = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
    $descripcion_categoria = filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_STRING);

    // 2. Verificación de datos obligatorios
    if (empty($nombre_categoria)) {
        $_SESSION['mensaje_error'] = "El nombre de la categoría es obligatorio.";
        header("Location: index.php");
        exit();
    }

    try {
        // 3. Verificar si la categoría ya existe para evitar duplicados por nombre
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM inventario360_categoria WHERE nombre = :nombre");
        $stmt_check->bindParam(':nombre', $nombre_categoria);
        $stmt_check->execute();
        if ($stmt_check->fetchColumn() > 0) {
            $_SESSION['mensaje_error'] = "Error: La categoría con el nombre '<strong>" . htmlspecialchars($nombre_categoria) . "</strong>' ya existe.";
            header("Location: index.php");
            exit();
        }

        // 4. Preparar la consulta SQL para la inserción
        // Usamos sentencias preparadas para prevenir inyecciones SQL.
        $stmt = $pdo->prepare("INSERT INTO inventario360_categoria (nombre, descripcion) VALUES (:nombre, :descripcion)");

        // 5. Vincular los parámetros de forma segura
        $stmt->bindParam(':nombre', $nombre_categoria);
        $stmt->bindParam(':descripcion', $descripcion_categoria);

        // 6. Ejecutar la consulta
        if ($stmt->execute()) {
            $_SESSION['mensaje_exito'] = "Categoría " . htmlspecialchars($nombre_categoria) . " añadida exitosamente.";
        } else {
            $_SESSION['mensaje_error'] = "Error al añadir la categoría. Por favor, inténtalo de nuevo.";
        }
    } catch (PDOException $e) {
        // 7. Manejo de errores de la base de datos
        // El código '23000' es un error de integridad (por ejemplo, clave única duplicada si se implementara una).
        // Aunque ya lo comprobamos antes, es una buena práctica tener un fallback.
        if ($e->getCode() == '23000') { 
            $_SESSION['mensaje_error'] = "Error de duplicidad: La categoría '<strong>" . htmlspecialchars($nombre_categoria) . "</strong>' ya está registrada.";
        } else {
            $_SESSION['mensaje_error'] = "Error de base de datos al añadir la categoría: " . $e->getMessage();
        }
    }
} else {
    // Si la solicitud no es POST (ej. alguien intenta acceder directamente vía URL)
    $_SESSION['mensaje_error'] = "Acceso no autorizado al procesamiento de categorías.";
}

// 8. Redirigir de vuelta a la página de categorías después de la operación
header("Location: index.php");
exit();
?>