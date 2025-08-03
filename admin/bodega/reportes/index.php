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
$permisoRequerido = $bodega_reportes_index;
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

// Obtener datos para reportes
try {
    $conexion = new PDO("mysql:host=$servidor;dbname=$nombre_bd", $usuario_bd, $contrasena_bd);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Total de bodegas
    $sql = "SELECT COUNT(*) as total FROM inventario360_bodega";
    $stmt = $conexion->prepare($sql);
    $stmt->execute();
    $total_bodegas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Bodegas con mayor capacidad
    $sql = "SELECT nombre, capacidad_maxima FROM inventario360_bodega ORDER BY capacidad_maxima DESC LIMIT 5";
    $stmt = $conexion->prepare($sql);
    $stmt->execute();
    $bodegas_mayor_capacidad = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Bodegas más llenas
    $sql = "SELECT nombre, capacidad_actual, capacidad_maxima, 
            ROUND((capacidad_actual/capacidad_maxima)*100) as porcentaje 
            FROM inventario360_bodega 
            WHERE capacidad_maxima > 0 
            ORDER BY porcentaje DESC LIMIT 5";
    $stmt = $conexion->prepare($sql);
    $stmt->execute();
    $bodegas_mas_llenas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Bodegas más vacías
    $sql = "SELECT nombre, capacidad_actual, capacidad_maxima, 
            ROUND((capacidad_actual/capacidad_maxima)*100) as porcentaje 
            FROM inventario360_bodega 
            WHERE capacidad_maxima > 0 
            ORDER BY porcentaje ASC LIMIT 5";
    $stmt = $conexion->prepare($sql);
    $stmt->execute();
    $bodegas_mas_vacias = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_reportes = "Error al generar reportes: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes de Bodegas - <?php echo $name_corp; ?></title>
    <link rel="shortcut icon" href="<?php echo $logo; ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <span class="ml-4 text-sm sm:text-lg text-gray-600">/ Gestión de bodegas / Reportes</span>
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
        <!-- Welcome Banner -->
        <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg shadow-md p-6 mb-8 text-white">
            <div class="flex flex-col md:flex-row items-center justify-between">
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold mb-2">Reportes de Bodegas</h2>
                    <p class="opacity-90">Visualiza estadísticas y métricas de tus bodegas</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <a href="../"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-arrow-left mr-2"></i> Volver al Inicio
                    </a>
                    <button onclick="window.print()"
                        class="inline-flex items-center bg-white text-purple-600 px-4 py-2 rounded-lg font-medium hover:bg-purple-50 transition">
                        <i class="fas fa-print mr-2"></i>Imprimir Reporte
                    </button>
                </div>
            </div>
        </div>

        <?php if (isset($error_reportes)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                <span class="block sm:inline"><?php echo $error_reportes; ?></span>
            </div>
        <?php endif; ?>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total Bodegas -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                        <i class="fas fa-warehouse text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total de Bodegas</p>
                        <p class="text-2xl font-semibold text-gray-800"><?php echo $total_bodegas; ?></p>
                    </div>
                </div>
            </div>

            <!-- Capacidad Promedio -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                        <i class="fas fa-boxes text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Capacidad Promedio</p>
                        <?php
                        try {
                            $sql = "SELECT AVG(capacidad_maxima) as promedio FROM inventario360_bodega";
                            $stmt = $conexion->prepare($sql);
                            $stmt->execute();
                            $promedio = $stmt->fetch(PDO::FETCH_ASSOC)['promedio'];
                            echo '<p class="text-2xl font-semibold text-gray-800">' . number_format($promedio, 0) . ' unidades</p>';
                        } catch (PDOException $e) {
                            echo '<p class="text-sm text-gray-500">No disponible</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Ocupación Promedio -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                        <i class="fas fa-percentage text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Ocupación Promedio</p>
                        <?php
                        try {
                            $sql = "SELECT AVG(capacidad_actual/capacidad_maxima)*100 as ocupacion 
                                    FROM inventario360_bodega 
                                    WHERE capacidad_maxima > 0";
                            $stmt = $conexion->prepare($sql);
                            $stmt->execute();
                            $ocupacion = $stmt->fetch(PDO::FETCH_ASSOC)['ocupacion'];
                            echo '<p class="text-2xl font-semibold text-gray-800">' . number_format($ocupacion, 1) . '%</p>';
                        } catch (PDOException $e) {
                            echo '<p class="text-sm text-gray-500">No disponible</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Capacidad de Bodegas -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-chart-pie text-blue-500 mr-2"></i> Distribución de Capacidad
                </h3>
                <canvas id="capacidadChart" height="250"></canvas>
            </div>

            <!-- Ocupación de Bodegas -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-chart-line text-green-500 mr-2"></i> Nivel de Ocupación
                </h3>
                <canvas id="ocupacionChart" height="250"></canvas>
            </div>
        </div>

        <!-- Top Bodegas Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Bodegas con mayor capacidad -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-trophy text-yellow-500 mr-2"></i> Bodegas con Mayor Capacidad
                    </h3>
                    <div class="space-y-4">
                        <?php foreach ($bodegas_mayor_capacidad as $bodega): ?>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="bg-blue-100 text-blue-600 p-2 rounded-lg mr-3">
                                        <i class="fas fa-warehouse"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-800">
                                            <?php echo htmlspecialchars($bodega['nombre']); ?></p>
                                    </div>
                                </div>
                                <span class="text-sm font-semibold bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                    <?php echo number_format($bodega['capacidad_maxima']); ?> unidades
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Bodegas más llenas -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i> Bodegas Más Llenas
                    </h3>
                    <div class="space-y-4">
                        <?php foreach ($bodegas_mas_llenas as $bodega): ?>
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span
                                        class="font-medium text-gray-800"><?php echo htmlspecialchars($bodega['nombre']); ?></span>
                                    <span class="text-sm font-semibold"><?php echo $bodega['porcentaje']; ?>%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-red-500 h-2 rounded-full"
                                        style="width: <?php echo $bodega['porcentaje']; ?>%"></div>
                                </div>
                                <div class="text-xs text-gray-500 mt-1 text-right">
                                    <?php echo $bodega['capacidad_actual']; ?>/<?php echo $bodega['capacidad_maxima']; ?>
                                    unidades
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bodegas más vacías -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-battery-quarter text-green-500 mr-2"></i> Bodegas Más Vacías
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <?php foreach ($bodegas_mas_vacias as $bodega): ?>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex justify-between items-center mb-2">
                                <h4 class="font-medium text-gray-800 truncate">
                                    <?php echo htmlspecialchars($bodega['nombre']); ?></h4>
                                <span class="text-xs font-semibold bg-green-100 text-green-800 px-2 py-1 rounded">
                                    <?php echo $bodega['porcentaje']; ?>%
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2 mb-1">
                                <div class="bg-green-500 h-2 rounded-full"
                                    style="width: <?php echo $bodega['porcentaje']; ?>%"></div>
                            </div>
                            <div class="text-xs text-gray-500">
                                <?php echo $bodega['capacidad_actual']; ?>/<?php echo $bodega['capacidad_maxima']; ?>
                                unidades
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Export Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-file-export text-purple-500 mr-2"></i> Exportar Reportes
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <button
                    class="flex items-center justify-center bg-blue-50 text-blue-600 px-4 py-3 rounded-lg font-medium hover:bg-blue-100 transition">
                    <i class="fas fa-file-excel mr-2"></i> Excel
                </button>
                <button
                    class="flex items-center justify-center bg-green-50 text-green-600 px-4 py-3 rounded-lg font-medium hover:bg-green-100 transition">
                    <i class="fas fa-file-csv mr-2"></i> CSV
                </button>
                <button
                    class="flex items-center justify-center bg-red-50 text-red-600 px-4 py-3 rounded-lg font-medium hover:bg-red-100 transition">
                    <i class="fas fa-file-pdf mr-2"></i> PDF
                </button>
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
        // Chart 1: Distribución de capacidad
        const capacidadCtx = document.getElementById('capacidadChart').getContext('2d');
        const capacidadChart = new Chart(capacidadCtx, {
            type: 'doughnut',
            data: {
                labels: ['Bodegas con >90% capacidad', 'Bodegas 70-90% capacidad', 'Bodegas <70% capacidad'],
                datasets: [{
                    data: [
                        <?php
                        $sql = "SELECT COUNT(*) as count FROM inventario360_bodega WHERE capacidad_maxima > 0 AND (capacidad_actual/capacidad_maxima) >= 0.9";
                        $stmt = $conexion->prepare($sql);
                        $stmt->execute();
                        echo $stmt->fetch(PDO::FETCH_ASSOC)['count'] . ', ';

                        $sql = "SELECT COUNT(*) as count FROM inventario360_bodega WHERE capacidad_maxima > 0 AND (capacidad_actual/capacidad_maxima) >= 0.7 AND (capacidad_actual/capacidad_maxima) < 0.9";
                        $stmt = $conexion->prepare($sql);
                        $stmt->execute();
                        echo $stmt->fetch(PDO::FETCH_ASSOC)['count'] . ', ';

                        $sql = "SELECT COUNT(*) as count FROM inventario360_bodega WHERE capacidad_maxima > 0 AND (capacidad_actual/capacidad_maxima) < 0.7";
                        $stmt = $conexion->prepare($sql);
                        $stmt->execute();
                        echo $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                        ?>
                    ],
                    backgroundColor: [
                        'rgba(239, 68, 68, 0.7)',
                        'rgba(234, 179, 8, 0.7)',
                        'rgba(16, 185, 129, 0.7)'
                    ],
                    borderColor: [
                        'rgba(239, 68, 68, 1)',
                        'rgba(234, 179, 8, 1)',
                        'rgba(16, 185, 129, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const value = context.raw;
                                const percentage = Math.round((value / total) * 100);
                                label += value + ' (' + percentage + '%)';
                                return label;
                            }
                        }
                    }
                }
            }
        });

        // Chart 2: Ocupación de bodegas
        const ocupacionCtx = document.getElementById('ocupacionChart').getContext('2d');
        const ocupacionChart = new Chart(ocupacionCtx, {
            type: 'bar',
            data: {
                labels: [
                    <?php
                    $sql = "SELECT nombre FROM inventario360_bodega ORDER BY (capacidad_actual/capacidad_maxima) DESC LIMIT 5";
                    $stmt = $conexion->prepare($sql);
                    $stmt->execute();
                    $bodegas_chart = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($bodegas_chart as $bodega) {
                        echo "'" . addslashes($bodega['nombre']) . "', ";
                    }
                    ?>
                ],
                datasets: [{
                    label: 'Porcentaje de Ocupación',
                    data: [
                        <?php
                        $sql = "SELECT ROUND((capacidad_actual/capacidad_maxima)*100) as porcentaje 
                                FROM inventario360_bodega 
                                WHERE capacidad_maxima > 0 
                                ORDER BY porcentaje DESC LIMIT 5";
                        $stmt = $conexion->prepare($sql);
                        $stmt->execute();
                        $porcentajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($porcentajes as $porcentaje) {
                            echo $porcentaje['porcentaje'] . ', ';
                        }
                        ?>
                    ],
                    backgroundColor: 'rgba(124, 58, 237, 0.7)',
                    borderColor: 'rgba(124, 58, 237, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Porcentaje (%)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.raw + '% de ocupación';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>