<?php
define('PROTECT_CONFIG', true);
session_start();


require_once '../../assets/config/info.php';
$permisoRequerido = $estadisticas_index;
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


// Redirige al login si no hay sesión activa
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['usuario_data'])) {
    $_SESSION['error'] = 'Por favor, inicia sesión para acceder.';
    header("Location: ../");
    exit();
}

// Obtener información del usuario desde la sesión
$usuario = $_SESSION['usuario_data'];

// Incluir el archivo de conexión a la base de datos
require_once '../../assets/config/db.php';

// Obtener estadísticas generales
$estadisticas = [];
$productosPorCategoria = [];
$productosPorBodega = [];
$movimientosRecientes = [];
$estadoProductos = [];
$ventasPorMes = [];

try {
    // Estadísticas generales
    $stmt = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM inventario360_producto) as total_productos,
            (SELECT COUNT(*) FROM inventario360_bodega) as total_bodegas,
            (SELECT COUNT(*) FROM inventario360_categoria) as total_categorias,
            (SELECT COUNT(*) FROM inventario360_movimientos) as total_movimientos,
            (SELECT COUNT(*) FROM inventario360_proveedor) as total_proveedores
    ");
    $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);

    // Productos por categoría
    $stmt = $pdo->query("
        SELECT c.nombre as categoria, COUNT(p.idproducto) as cantidad
        FROM inventario360_categoria c
        LEFT JOIN inventario360_producto p ON c.idcategoria = p.idcategoria
        GROUP BY c.idcategoria
        ORDER BY cantidad DESC
    ");
    $productosPorCategoria = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Productos por bodega
    $stmt = $pdo->query("
        SELECT b.nombre as bodega, COUNT(p.idproducto) as cantidad
        FROM inventario360_bodega b
        LEFT JOIN inventario360_producto p ON b.idbodega = p.idbodega
        GROUP BY b.idbodega
        ORDER BY cantidad DESC
    ");
    $productosPorBodega = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Estado de los productos
    $stmt = $pdo->query("
        SELECT estado, COUNT(*) as cantidad
        FROM inventario360_producto
        GROUP BY estado
    ");
    $estadoProductos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Movimientos recientes
    $stmt = $pdo->query("
        SELECT m.idmovimiento, tm.nombre as tipo_movimiento, m.fecha_movimiento, 
               u.nombre as usuario, COUNT(mp.producto_id) as productos
        FROM inventario360_movimientos m
        JOIN inventario360_tipo_movimiento tm ON m.tipo_movimiento_id = tm.idtipo_movimiento
        JOIN inventario360_usuario u ON m.usuario_id = u.idusuario
        LEFT JOIN inventario360_movimientos_productos mp ON m.idmovimiento = mp.movimiento_id
        GROUP BY m.idmovimiento
        ORDER BY m.fecha_movimiento DESC
        LIMIT 5
    ");
    $movimientosRecientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ventas por mes (asumiendo que hay un tipo de movimiento para ventas)
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(m.fecha_movimiento, '%Y-%m') as mes,
            SUM(mp.cantidad * mp.precio_unitario) as total_ventas,
            COUNT(DISTINCT m.idmovimiento) as transacciones
        FROM inventario360_movimientos m
        JOIN inventario360_movimientos_productos mp ON m.idmovimiento = mp.movimiento_id
        JOIN inventario360_tipo_movimiento tm ON m.tipo_movimiento_id = tm.idtipo_movimiento
        WHERE tm.nombre = 'Venta' OR tm.nombre LIKE '%venta%'
        GROUP BY mes
        ORDER BY mes DESC
        LIMIT 12
    ");
    $ventasPorMes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['error'] = 'Error al cargar estadísticas: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas - <?php echo $name_corp; ?></title>
    <link rel="shortcut icon" href="<?php echo $logo; ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .card-hover:hover {
            transform: translateY(-5px);
            transition: transform 0.3s ease;
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">
    <header class="bg-white shadow-sm sticky top-0 z-20">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row justify-between items-center">
            <div class="flex items-center mb-4 sm:mb-0">
                <a href="../" class="flex items-center">
                    <i class="fas fa-boxes text-blue-600 text-2xl mr-3"></i>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900"><?php echo $name_corp; ?></h1>
                </a>
                <span class="ml-4 text-sm sm:text-lg text-gray-600">/ Estadisticas</span>
            </div>

            <div class="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-4">
                <div class="flex items-center text-sm sm:text-base">
                    <i class="fas fa-user-circle text-gray-500 mr-2"></i>
                    <span class="text-gray-700"><?php echo htmlspecialchars($usuario['nombre']); ?></span>
                    <span class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                        <?php echo htmlspecialchars($usuario['rol_nombre']); ?>
                    </span>
                </div>
                <a href="../../op_logout.php"
                    class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition flex items-center text-sm sm:text-base">
                    <i class="fas fa-sign-out-alt mr-1"></i> Cerrar sesión
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">

        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-4 p-4 border rounded-md bg-red-100 border-red-400 text-red-700" role="alert">
                <p><?php echo htmlspecialchars($_SESSION['error']);
                unset($_SESSION['error']); ?></p>
            </div>
        <?php endif; ?>

        <div class="mb-8">


                    <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-tachometer-alt text-blue-500 mr-2"></i> Resumen General
            </h2>
            <a href="../"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-arrow-left mr-2"></i> Volver al Inicio
            </a>
        </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                            <i class="fas fa-boxes text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Productos</p>
                            <h3 class="text-2xl font-bold"><?php echo $estadisticas['total_productos'] ?? 0; ?></h3>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                            <i class="fas fa-warehouse text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Bodegas</p>
                            <h3 class="text-2xl font-bold"><?php echo $estadisticas['total_bodegas'] ?? 0; ?></h3>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                            <i class="fas fa-tags text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Categorías</p>
                            <h3 class="text-2xl font-bold"><?php echo $estadisticas['total_categorias'] ?? 0; ?></h3>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                            <i class="fas fa-exchange-alt text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Movimientos</p>
                            <h3 class="text-2xl font-bold"><?php echo $estadisticas['total_movimientos'] ?? 0; ?></h3>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-100 text-red-600 mr-4">
                            <i class="fas fa-truck text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Proveedores</p>
                            <h3 class="text-2xl font-bold"><?php echo $estadisticas['total_proveedores'] ?? 0; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-chart-pie text-blue-500 mr-2"></i> Productos por Categoría
                </h3>
                <div class="chart-container">
                    <canvas id="categoriaChart"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-chart-bar text-green-500 mr-2"></i> Productos por Bodega
                </h3>
                <div class="chart-container">
                    <canvas id="bodegaChart"></canvas>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-chart-pie text-purple-500 mr-2"></i> Estado de Productos
                </h3>
                <div class="chart-container">
                    <canvas id="estadoChart"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-chart-line text-yellow-500 mr-2"></i> Ventas por Mes
                </h3>
                <div class="chart-container">
                    <canvas id="ventasChart"></canvas>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 card-hover mb-8">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-exchange-alt text-red-500 mr-2"></i> Últimos Movimientos
            </h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tipo</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Fecha</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Usuario</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Productos</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($movimientosRecientes as $movimiento): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $movimiento['idmovimiento']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo $movimiento['tipo_movimiento']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('d/m/Y H:i', strtotime($movimiento['fecha_movimiento'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $movimiento['usuario']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $movimiento['productos']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <footer class="bg-white border-t mt-10 py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-gray-500 text-sm">
            <p><?php echo $name_corp; ?> v2.0 &copy; <?php echo date('Y'); ?> - Todos los derechos reservados</p>
            <p class="mt-1">Sistema desarrollado para gestión integral de inventarios</p>
        </div>
    </footer>

    <script>
        // Gráfico de productos por categoría
        const categoriaCtx = document.getElementById('categoriaChart').getContext('2d');
        const categoriaChart = new Chart(categoriaCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($productosPorCategoria, 'categoria')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($productosPorCategoria, 'cantidad')); ?>,
                    backgroundColor: [
                        '#3B82F6', '#10B981', '#F59E0B', '#6366F1', '#EC4899',
                        '#14B8A6', '#F97316', '#8B5CF6', '#EF4444', '#06B6D4'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.raw + ' productos';
                                return label;
                            }
                        }
                    }
                }
            }
        });

        // Gráfico de productos por bodega
        const bodegaCtx = document.getElementById('bodegaChart').getContext('2d');
        const bodegaChart = new Chart(bodegaCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($productosPorBodega, 'bodega')); ?>,
                datasets: [{
                    label: 'Productos',
                    data: <?php echo json_encode(array_column($productosPorBodega, 'cantidad')); ?>,
                    backgroundColor: '#10B981',
                    borderColor: '#047857',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                if (Number.isInteger(value)) {
                                    return value;
                                }
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.raw + ' productos';
                            }
                        }
                    }
                }
            }
        });

        // Gráfico de estado de productos
        const estadoCtx = document.getElementById('estadoChart').getContext('2d');
        const estadoChart = new Chart(estadoCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($estadoProductos, 'estado')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($estadoProductos, 'cantidad')); ?>,
                    backgroundColor: [
                        '#10B981', // Activo
                        '#F59E0B', // Inactivo
                        '#EF4444'  // Averiado
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.raw + ' productos';
                            }
                        }
                    }
                }
            }
        });

        // Gráfico de ventas por mes
        const ventasCtx = document.getElementById('ventasChart').getContext('2d');
        const ventasChart = new Chart(ventasCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($ventasPorMes, 'mes')); ?>,
                datasets: [{
                    label: 'Ventas ($)',
                    data: <?php echo json_encode(array_column($ventasPorMes, 'total_ventas')); ?>,
                    backgroundColor: 'rgba(234, 179, 8, 0.2)',
                    borderColor: 'rgba(234, 179, 8, 1)',
                    borderWidth: 2,
                    tension: 0.1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return '$' + context.raw.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>