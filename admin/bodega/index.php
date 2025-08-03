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
$permisoRequerido = $bodega_index;
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


require_once '../../assets/config/db.php';

// Obtener bodegas recientes (modificado para usar campos correctos)
try {
    $conexion = new PDO("mysql:host=$servidor;dbname=$nombre_bd", $usuario_bd, $contrasena_bd);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT * FROM inventario360_bodega ORDER BY idbodega DESC LIMIT 5";
    $stmt = $conexion->prepare($sql);
    $stmt->execute();
    $bodegas_recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_recientes = "Error al obtener bodegas recientes: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Bodegas - <?php echo $name_corp; ?></title>
    <link rel="shortcut icon" href="<?php echo $logo; ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
    </style>
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
                <span class="ml-4 text-sm sm:text-lg text-gray-600">/ Gestión de Bodegas</span>
            </div>

            <div class="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-4">
                <div class="flex items-center text-sm sm:text-base">
                    <i class="fas fa-user-circle text-gray-500 mr-2"></i>
                    <span class="text-gray-700"><?php echo htmlspecialchars($usuario_actual['nombre']); ?></span>
                    <span class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                        <?php echo htmlspecialchars($usuario_actual['rol_nombre']); ?>
                    </span>
                </div>
                <a href="../../op_logout.php"
                    class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition flex items-center text-sm sm:text-base">
                    <i class="fas fa-sign-out-alt mr-1"></i> Cerrar sesión
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
        <!-- Welcome Banner -->
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow-md p-6 mb-8 text-white">
            <div class="flex flex-col md:flex-row items-center justify-between">
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold mb-2">Gestión de Bodegas</h2>
                    <p class="opacity-90">Administra los espacios de almacenamiento de tu inventario</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <a href="../"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-arrow-left mr-2"></i> Volver al Inicio
                    </a>
                    <a href="crear/"
                        class="inline-flex items-center bg-white text-blue-600 px-4 py-2 rounded-lg font-medium hover:bg-blue-50 transition">
                        <i class="fas fa-plus mr-2"></i>Nueva Bodega
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mb-10">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Listar Bodegas -->
                <a href="listar/"
                    class="bg-white rounded-lg shadow-md overflow-hidden transition duration-300 card-hover">
                    <div class="p-6 flex flex-col items-center text-center">
                        <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-list text-blue-600 text-2xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-800">Listar Bodegas</h3>
                        <p class="text-sm text-gray-500 mt-2">Ver todas las bodegas registradas</p>
                    </div>
                </a>

                <!-- Crear Bodega -->
                <a href="crear/"
                    class="bg-white rounded-lg shadow-md overflow-hidden transition duration-300 card-hover">
                    <div class="p-6 flex flex-col items-center text-center">
                        <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-plus-circle text-green-600 text-2xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-800">Crear Bodega</h3>
                        <p class="text-sm text-gray-500 mt-2">Agregar nueva bodega al sistema</p>
                    </div>
                </a>

                <!-- Reportes -->
                <a href="reportes/"
                    class="bg-white rounded-lg shadow-md overflow-hidden transition duration-300 card-hover">
                    <div class="p-6 flex flex-col items-center text-center">
                        <div class="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-chart-bar text-purple-600 text-2xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-800">Reportes</h3>
                        <p class="text-sm text-gray-500 mt-2">Generar reportes de bodegas</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-history text-blue-500 mr-2"></i> Bodegas Recientes
            </h2>

            <?php if (isset($error_recientes)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline"><?php echo $error_recientes; ?></span>
                </div>
            <?php endif; ?>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nombre</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Dirección</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Capacidad</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Ocupación</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($bodegas_recientes)): ?>
                            <?php foreach ($bodegas_recientes as $bodega): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($bodega['nombre']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($bodega['direccion']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo number_format($bodega['capacidad_maxima']); ?> unidades
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $porcentaje = $bodega['capacidad_maxima'] > 0 ?
                                            round(($bodega['capacidad_actual'] / $bodega['capacidad_maxima']) * 100) : 0;
                                        $color = $porcentaje >= 90 ? 'bg-red-500' : ($porcentaje >= 70 ? 'bg-yellow-500' : 'bg-green-500');
                                        ?>
                                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                                            <div class="h-2.5 rounded-full <?php echo $color; ?>"
                                                style="width: <?php echo $porcentaje; ?>%"></div>
                                        </div>
                                        <span class="text-xs text-gray-500 mt-1">
                                            <?php echo $bodega['capacidad_actual']; ?>/<?php echo $bodega['capacidad_maxima']; ?>
                                            (<?php echo $porcentaje; ?>%)
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="editar/?id=<?php echo $bodega['idbodega']; ?>"
                                            class="text-blue-600 hover:text-blue-900 mr-3">Editar</a>
                                        <?php if ($esAdmin): ?>
                                            <a href="eliminar/?id=<?php echo $bodega['idbodega']; ?>"
                                                onclick="return confirm('¿Estás seguro de eliminar esta bodega?')"
                                                class="text-red-600 hover:text-red-900">Eliminar</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No hay bodegas registradas
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex justify-end">
                <a href="listar/" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    Ver todas las bodegas <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t mt-10 py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-gray-500 text-sm">
            <p><?php echo $name_corp; ?> v2.0 &copy; <?php echo date('Y'); ?> - Todos los derechos reservados</p>
        </div>
    </footer>

    <script>
        // Confirmación antes de eliminar
        document.querySelectorAll('a[href*="eliminar"]').forEach(link => {
            link.addEventListener('click', function (e) {
                if (!confirm('¿Estás seguro de que deseas eliminar esta bodega?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>

</html>