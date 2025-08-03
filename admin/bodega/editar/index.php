<?php
// Configuración de reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
$permisoRequerido = $bodega_editar;
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

// Cargar configuración de la base de datos
require_once __DIR__ . '/../../../assets/config/db.php';

// Configuración de la aplicación
$usuario = $_SESSION['usuario_data'];
$esAdmin = ($usuario['rol_id'] == 1);
$error = '';
$success = '';
$bodega = null;

// Validar y obtener ID de la bodega
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?? 0;

if ($id <= 0) {
    header("Location: ../listar/");
    exit();
}

try {
    // Verificar conexión a la base de datos
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception("Error de conexión a la base de datos");
    }

    // Obtener datos de la bodega
    $stmt = $pdo->prepare("SELECT * FROM inventario360_bodega WHERE idbodega = ?");
    $stmt->execute([$id]);
    $bodega = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bodega) {
        header("Location: ../listar/");
        exit();
    }

    // Procesar formulario de actualización
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Sanitizar y validar entradas
        $nombre = htmlspecialchars(trim($_POST['nombre'] ?? ''), ENT_QUOTES, 'UTF-8');
        $direccion = htmlspecialchars(trim($_POST['direccion'] ?? ''), ENT_QUOTES, 'UTF-8');
        $capacidad_maxima = filter_input(INPUT_POST, 'capacidad_maxima', FILTER_VALIDATE_INT);
        $capacidad_actual = filter_input(INPUT_POST, 'capacidad_actual', FILTER_VALIDATE_INT);
        
        // Validaciones
        if (empty($nombre)) {
            throw new Exception("El nombre de la bodega es requerido");
        }
        
        if ($capacidad_maxima === false || $capacidad_maxima <= 0) {
            throw new Exception("La capacidad máxima debe ser un número positivo");
        }
        
        if ($capacidad_actual === false || $capacidad_actual < 0) {
            throw new Exception("La capacidad actual debe ser un número no negativo");
        }
        
        if ($capacidad_actual > $capacidad_maxima) {
            throw new Exception("La capacidad actual no puede ser mayor que la capacidad máxima");
        }

        // Actualizar bodega en la base de datos
        $sql = "UPDATE inventario360_bodega 
                SET nombre = ?, 
                    direccion = ?, 
                    capacidad_maxima = ?, 
                    capacidad_actual = ? 
                WHERE idbodega = ?";
        
        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute([
            $nombre,
            $direccion,
            $capacidad_maxima,
            $capacidad_actual,
            $id
        ]);

        if ($resultado) {
            $success = "Bodega actualizada exitosamente!";
            // Actualizar datos para mostrar
            $bodega['nombre'] = $nombre;
            $bodega['direccion'] = $direccion;
            $bodega['capacidad_maxima'] = $capacidad_maxima;
            $bodega['capacidad_actual'] = $capacidad_actual;
        } else {
            throw new Exception("Error al actualizar la bodega en la base de datos");
        }
    }
} catch(PDOException $e) {
    error_log("Error de base de datos: " . $e->getMessage());
    $error = "Error en la base de datos. Por favor intenta nuevamente.";
} catch(Exception $e) {
    error_log("Error general: " . $e->getMessage());
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Bodega - <?php echo $name_corp; ?></title>
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
                <span class="ml-4 text-sm sm:text-lg text-gray-600">/ Gestión de Bodegas / Editar Bodega</span>
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
                <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                <i class="fas fa-tags text-indigo-600 mr-3"></i> Editar Bodega
            </h2>
            <a href="../"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-arrow-left mr-2"></i> Volver al Inicio
            </a>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-6">Información de la Bodega</h2>
            
            <?php if($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>
            
            <form action="" method="POST">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Nombre -->
                    <div>
                        <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre de la Bodega *</label>
                        <input type="text" id="nombre" name="nombre" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               value="<?php echo htmlspecialchars($bodega['nombre'] ?? ''); ?>">
                    </div>
                    
                    <!-- Dirección -->
                    <div>
                        <label for="direccion" class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                        <input type="text" id="direccion" name="direccion" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               value="<?php echo htmlspecialchars($bodega['direccion'] ?? ''); ?>">
                    </div>
                    
                    <!-- Capacidad Máxima -->
                    <div>
                        <label for="capacidad_maxima" class="block text-sm font-medium text-gray-700 mb-1">Capacidad Máxima *</label>
                        <input type="number" id="capacidad_maxima" name="capacidad_maxima" required min="1"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               value="<?php echo htmlspecialchars($bodega['capacidad_maxima'] ?? 0); ?>">
                    </div>
                    
                    <!-- Capacidad Actual -->
                    <div>
                        <label for="capacidad_actual" class="block text-sm font-medium text-gray-700 mb-1">Capacidad Actual *</label>
                        <input type="number" id="capacidad_actual" name="capacidad_actual" required min="0"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               value="<?php echo htmlspecialchars($bodega['capacidad_actual'] ?? 0); ?>">
                    </div>
                </div>
                
                <div class="mt-8 flex justify-end space-x-4">
                    <a href="../listar/" class="bg-gray-200 text-gray-800 px-6 py-2 rounded-md font-medium hover:bg-gray-300 transition">
                        Cancelar
                    </a>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md font-medium hover:bg-blue-700 transition">
                        <i class="fas fa-save mr-2"></i>Actualizar Bodega
                    </button>
                </div>
            </form>
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