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


require_once '../../../assets/config/info.php';
$permisoRequerido = $bodega_eliminar;
if (isset($_SESSION['usuario_permisos']) && in_array($permisoRequerido, $_SESSION['usuario_permisos'])) {
    $esAccesoPermitido = true;
} else {
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => "Acceso denegado. No cuentas con el permiso " . $permisoRequerido . ". Por favor Contacta con tu departamento de IT o administrador de ayudas"
    ];
    header("Location: ../");
    exit();
}

require_once '../../../assets/config/db.php';
 // Verificación de rol admin


// Obtener ID de la bodega a eliminar
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../listar/");
    exit();
}

$id_bodega = $_GET['id'];
$error = '';
$success = '';

// Verificar si se confirmó la eliminación
if (isset($_GET['confirm']) && $_GET['confirm'] == 'true') {
    try {
        $conexion = new PDO("mysql:host=$servidor;dbname=$nombre_bd", $usuario_bd, $contrasena_bd);
        $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Verificar si la bodega tiene productos asociados
        $sql_check = "SELECT COUNT(*) as total FROM inventario360_producto WHERE idbodega = :id";
        $stmt_check = $conexion->prepare($sql_check);
        $stmt_check->bindParam(':id', $id_bodega);
        $stmt_check->execute();
        $result = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total'] > 0) {
            $error = "No se puede eliminar la bodega porque tiene productos asociados. Primero mueve o elimina los productos.";
        } else {
            // Eliminar la bodega
            $sql = "DELETE FROM inventario360_bodega WHERE idbodega = :id";
            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':id', $id_bodega);
            
            if ($stmt->execute()) {
                $success = "Bodega eliminada exitosamente!";
                // Redirigir después de 2 segundos
                header("Refresh: 2; url=../listar/");
            } else {
                $error = "Error al eliminar la bodega";
            }
        }
    } catch(PDOException $e) {
        $error = "Error en la base de datos: " . $e->getMessage();
    }
}

// Obtener información de la bodega para mostrar
try {
    $conexion = new PDO("mysql:host=$servidor;dbname=$nombre_bd", $usuario_bd, $contrasena_bd);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sql = "SELECT * FROM inventario360_bodega WHERE idbodega = :id";
    $stmt = $conexion->prepare($sql);
    $stmt->bindParam(':id', $id_bodega);
    $stmt->execute();
    $bodega = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bodega) {
        header("Location: ../listar/");
        exit();
    }
} catch(PDOException $e) {
    $error = "Error al obtener información de la bodega: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar Bodega - <?php echo $name_corp; ?></title>
    <link rel="shortcut icon" href="<?php echo $logo; ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-50 font-sans">
    <!-- Header/Navbar -->
    <header class="bg-white shadow-sm sticky top-0 z-20">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row justify-between items-center">
            <div class="flex items-center mb-4 sm:mb-0">
                <a href="../index.php" class="flex items-center">
                    <i class="fas fa-boxes text-blue-600 text-2xl mr-3"></i>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900"><?php echo $name_corp; ?></h1>
                </a>
                <span class="ml-4 text-sm sm:text-lg text-gray-600">/ Gestión de Bodegas / eliminar bodega</span>
            </div>

            <div class="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-4">
                <div class="flex items-center text-sm sm:text-base">
                    <i class="fas fa-user-circle text-gray-500 mr-2"></i>
                    <span class="text-gray-700"><?php echo htmlspecialchars($usuario_actual['nombre']); ?></span>
                    <span class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                        <?php echo htmlspecialchars($usuario_actual['rol_nombre']); ?>
                    </span>
                </div>
                <a href="../../../op_logout.php"
                    class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition flex items-center text-sm sm:text-base">
                    <i class="fas fa-sign-out-alt mr-1"></i> Cerrar sesión
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-md p-6 max-w-3xl mx-auto">
                    <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                <i class="fas fa-tags text-indigo-600 mr-3"></i> Eliminar Bodega
            </h2>
            <a href="../"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-arrow-left mr-2"></i> Volver al Inicio
            </a>
        </div>
            <h2 class="text-xl font-bold text-gray-800 mb-6">Confirmar Eliminación de Bodega</h2>
            
            <?php if($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline"><?php echo $success; ?></span>
                    <p class="mt-2">Redirigiendo a la lista de bodegas...</p>
                </div>
            <?php else: ?>
                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6">
                    <p class="font-bold">Advertencia</p>
                    <p>¿Estás seguro de que deseas eliminar permanentemente esta bodega? Esta acción no se puede deshacer.</p>
                </div>
                
                <div class="bg-gray-100 p-4 rounded-lg mb-6">
                    <h3 class="font-medium text-gray-800 mb-2">Información de la Bodega:</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Nombre:</p>
                            <p class="font-medium"><?php echo htmlspecialchars($bodega['nombre']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Ubicación:</p>
                            <p class="font-medium"><?php echo htmlspecialchars($bodega['ubicacion']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Capacidad:</p>
                            <p class="font-medium"><?php echo htmlspecialchars($bodega['capacidad']); ?> m²</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Estado:</p>
                            <p class="font-medium">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $bodega['estado'] == 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo $bodega['estado'] == 1 ? 'Activa' : 'Inactiva'; ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-4">
                    <a href="../listar/" class="bg-gray-200 text-gray-800 px-6 py-2 rounded-md font-medium hover:bg-gray-300 transition">
                        Cancelar
                    </a>
                    <a href="?id=<?php echo $id_bodega; ?>&confirm=true" class="bg-red-600 text-white px-6 py-2 rounded-md font-medium hover:bg-red-700 transition">
                        <i class="fas fa-trash-alt mr-2"></i>Confirmar Eliminación
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t mt-10 py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-gray-500 text-sm">
            <p><?php echo $name_corp; ?> v2.0 &copy; <?php echo date('Y'); ?> - Todos los derechos reservados</p>
        </div>
    </footer>
</body>
</html>