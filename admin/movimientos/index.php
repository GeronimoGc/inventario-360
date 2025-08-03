<?php
session_start();
define('PROTECT_CONFIG', true);

// 1. Verificar si la sesión 'logged_in' existe y es verdadera.
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../../");
    exit();
}

// 2. Usar $_SESSION['usuario_data']
if (!isset($_SESSION['usuario_data']) || !is_array($_SESSION['usuario_data'])) {
    $_SESSION['error'] = 'Error de sesión. Por favor, inicie sesión de nuevo.';
    header("Location: ../../");
    exit();
}

$usuario_actual = $_SESSION['usuario_data'];

require_once '../../assets/config/info.php';
$permisoRequerido = $op_view_gestor_movimientos;
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

// Inicializar variables para mensajes
$mensaje_exito = null;
$mensaje_error = null;
$mensaje_aviso = null;

// Recuperar mensajes de la sesión y luego limpiarlos
if (isset($_SESSION['mensaje_exito'])) {
    $mensaje_exito = $_SESSION['mensaje_exito'];
    unset($_SESSION['mensaje_exito']);
}
if (isset($_SESSION['mensaje_error'])) {
    $mensaje_error = $_SESSION['mensaje_error'];
    unset($_SESSION['mensaje_error']);
}
if (isset($_SESSION['mensaje_aviso'])) {
    $mensaje_aviso = $_SESSION['mensaje_aviso'];
    unset($_SESSION['mensaje_aviso']);
}

$searchTerm = $_GET['search'] ?? '';
$filterTipoMovimiento = $_GET['tipo_movimiento_filter'] ?? '';
$dbError = null;
$movimientos_sistema = [];
$tipos_movimiento_para_formulario = [];
$usuarios_para_formulario = [];
$bodegas_para_formulario = [];

try {
    require_once '../../assets/config/db.php';

    if (!isset($pdo) || !$pdo instanceof PDO) {
        throw new PDOException("La conexión PDO no está disponible. Asegúrate de que db.php la inicializa correctamente.");
    }

    // Obtener tipos de movimiento
    $stmt_tipos = $pdo->query("SELECT idtipo_movimiento, nombre FROM inventario360_tipo_movimiento ORDER BY nombre ASC");
    $tipos_movimiento_para_formulario = $stmt_tipos->fetchAll(PDO::FETCH_ASSOC);

    // Obtener usuarios (para el creador del movimiento)
    $stmt_usuarios = $pdo->query("SELECT idusuario, nombre FROM inventario360_usuario ORDER BY nombre ASC");
    $usuarios_para_formulario = $stmt_usuarios->fetchAll(PDO::FETCH_ASSOC);

    // Obtener bodegas
    $stmt_bodegas = $pdo->query("SELECT idbodega, nombre FROM inventario360_bodega ORDER BY nombre ASC");
    $bodegas_para_formulario = $stmt_bodegas->fetchAll(PDO::FETCH_ASSOC);


    // Construir la consulta SQL para buscar y filtrar movimientos
    $sql = "SELECT
                m.idmovimiento,
                m.fecha_movimiento,
                m.descripcion,
                m.documento_referencia,
                m.estado_movimiento,
                tm.nombre AS tipo_movimiento_nombre,
                u.nombre AS usuario_nombre,
                bo.nombre AS bodega_origen_nombre,
                bd.nombre AS bodega_destino_nombre,
                m.tipo_movimiento_id,
                m.usuario_id,
                m.bodega_origen_id,
                m.bodega_destino_id
            FROM inventario360_movimientos m
            JOIN inventario360_tipo_movimiento tm ON m.tipo_movimiento_id = tm.idtipo_movimiento
            JOIN inventario360_usuario u ON m.usuario_id = u.idusuario
            LEFT JOIN inventario360_bodega bo ON m.bodega_origen_id = bo.idbodega
            LEFT JOIN inventario360_bodega bd ON m.bodega_destino_id = bd.idbodega";

    $whereClauses = [];
    $params = [];

    if (!empty($searchTerm)) {
        $whereClauses[] = "(m.descripcion LIKE :searchTermDesc OR m.documento_referencia LIKE :searchTermDoc OR tm.nombre LIKE :searchTermTipoMov OR u.nombre LIKE :searchTermUsuario OR bo.nombre LIKE :searchTermBodegaOrigen OR bd.nombre LIKE :searchTermBodegaDestino)";
        $params[':searchTermDesc'] = '%' . $searchTerm . '%';
        $params[':searchTermDoc'] = '%' . $searchTerm . '%';
        $params[':searchTermTipoMov'] = '%' . $searchTerm . '%';
        $params[':searchTermUsuario'] = '%' . $searchTerm . '%';
        $params[':searchTermBodegaOrigen'] = '%' . $searchTerm . '%';
        $params[':searchTermBodegaDestino'] = '%' . $searchTerm . '%';
    }

    if (!empty($filterTipoMovimiento) && $filterTipoMovimiento !== 'all') {
        $whereClauses[] = "m.tipo_movimiento_id = :filterTipoMovimiento";
        $params[':filterTipoMovimiento'] = $filterTipoMovimiento;
    }

    if (!empty($whereClauses)) {
        $sql .= " WHERE " . implode(" AND ", $whereClauses);
    }

    $sql .= " ORDER BY m.fecha_movimiento DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $movimientos_sistema = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $dbError = "Error al conectar o consultar la base de datos. Detalles: " . $e->getMessage();
    // Fallback a datos de ejemplo si la BD falla
    $movimientos_sistema = [
        ['idmovimiento' => 1, 'fecha_movimiento' => '2025-06-10 10:00:00', 'descripcion' => 'Entrada de mercadería A', 'documento_referencia' => 'INV-001', 'estado_movimiento' => 'cerrado', 'tipo_movimiento_nombre' => 'Entrada', 'usuario_nombre' => 'Admin (Demo)', 'bodega_origen_nombre' => 'N/A', 'bodega_destino_nombre' => 'Bodega Central (Demo)', 'tipo_movimiento_id' => 1, 'usuario_id' => 1, 'bodega_origen_id' => null, 'bodega_destino_id' => 1],
        ['idmovimiento' => 2, 'fecha_movimiento' => '2025-06-11 11:30:00', 'descripcion' => 'Salida por venta online', 'documento_referencia' => 'ORD-005', 'estado_movimiento' => 'abierto', 'tipo_movimiento_nombre' => 'Salida', 'usuario_nombre' => 'Supervisor (Demo)', 'bodega_origen_nombre' => 'Bodega Central (Demo)', 'bodega_destino_nombre' => 'N/A', 'tipo_movimiento_id' => 2, 'usuario_id' => 2, 'bodega_origen_id' => 1, 'bodega_destino_id' => null],
    ];
    $tipos_movimiento_para_formulario = [
        ['idtipo_movimiento' => 1, 'nombre' => 'Entrada (Demo)'],
        ['idtipo_movimiento' => 2, 'nombre' => 'Salida (Demo)'],
        ['idtipo_movimiento' => 3, 'nombre' => 'Transferencia (Demo)'],
        ['idtipo_movimiento' => 4, 'nombre' => 'Ajuste (Demo)'],
    ];
    $usuarios_para_formulario = [
        ['idusuario' => 1, 'nombre' => 'Admin (Demo)'],
        ['idusuario' => 2, 'nombre' => 'Supervisor (Demo)'],
    ];
    $bodegas_para_formulario = [
        ['idbodega' => 1, 'nombre' => 'Bodega Central (Demo)'],
        ['idbodega' => 2, 'nombre' => 'Bodega Sucursal (Demo)'],
    ];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Movimientos - <?php echo $name_corp; ?></title>
    <link rel="shortcut icon" href="<?php echo $logo; ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .modal {
            display: none;
        }

        .modal.active {
            display: flex;
        }

        /* Ajuste para que la tabla sea responsiva */
        .table-fixed-layout {
            table-layout: auto;
            width: 100%;
        }

        @media (min-width: 640px) {
            .table-fixed-layout {
                table-layout: fixed;
            }
        }

        /* Estilos para el mensaje de notificación */
        .notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 350px;
        }

        .notification {
            padding: 1rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            opacity: 0;
            transform: translateY(-20px);
            animation: fadeInOut 5s forwards;
        }

        .notification.success {
            background-color: #d1fae5;
            border-left: 4px solid #10b981;
            color: #065f46;
        }

        .notification.error {
            background-color: #fee2e2;
            border-left: 4px solid #ef4444;
            color: #991b1b;
        }

        .notification.warning {
            background-color: #fffbeb;
            border-left: 4px solid #f59e0b;
            color: #92400e;
        }

        .notification i {
            margin-right: 0.75rem;
        }

        @keyframes fadeInOut {
            0% {
                opacity: 0;
                transform: translateY(-20px);
            }

            10% {
                opacity: 1;
                transform: translateY(0);
            }

            90% {
                opacity: 1;
                transform: translateY(0);
            }

            100% {
                opacity: 0;
                transform: translateY(-20px);
            }
        }

        /* Estilos específicos para la tabla en móviles */
        @media (max-width: 639px) {
            .table-fixed-layout thead {
                display: none;
            }

            .table-fixed-layout,
            .table-fixed-layout tbody,
            .table-fixed-layout tr,
            .table-fixed-layout td {
                display: block;
                width: 100%;
            }

            .table-fixed-layout tr {
                margin-bottom: 1rem;
                border: 1px solid #e5e7eb;
                border-radius: 0.5rem;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                background-color: white;
            }

            .table-fixed-layout td {
                text-align: right;
                padding-left: 50%; /* Suficiente espacio para la etiqueta ::before */
                position: relative;
                border: none;
                padding-top: 0.5rem;
                padding-bottom: 0.5rem;
            }

            .table-fixed-layout td::before {
                content: attr(data-label);
                position: absolute;
                left: 0.75rem;
                width: 45%; /* Ancho para las etiquetas */
                padding-right: 0.75rem;
                white-space: nowrap;
                text-align: left;
                font-weight: bold;
                color: #4b5563;
            }

            /* Ajustes específicos para cada columna en móvil */
            .table-fixed-layout td:nth-of-type(1)::before { content: "ID:"; }
            .table-fixed-layout td:nth-of-type(2)::before { content: "Fecha:"; }
            .table-fixed-layout td:nth-of-type(3)::before { content: "Tipo:"; }
            .table-fixed-layout td:nth-of-type(4)::before { content: "Descripción:"; }
            .table-fixed-layout td:nth-of-type(5)::before { content: "Ref. Doc.:"; }
            .table-fixed-layout td:nth-of-type(6)::before { content: "Origen:"; }
            .table-fixed-layout td:nth-of-type(7)::before { content: "Destino:"; }
            .table-fixed-layout td:nth-of-type(8)::before { content: "Estado:"; }
            .table-fixed-layout td:nth-of-type(9)::before { content: "Realizado por:"; }
            .table-fixed-layout td:nth-of-type(10)::before { content: "Acciones:"; }

            .table-fixed-layout td:nth-of-type(10) {
                text-align: center;
                padding-left: 0.75rem;
            }

            .table-fixed-layout td:nth-of-type(10) button {
                margin: 0 0.25rem;
            }

            /* Asegurar que el texto largo se envuelva correctamente */
            .table-fixed-layout td.whitespace-normal {
                white-space: normal;
            }
        }
    </style>
</head>

<body class="bg-gray-100 font-sans">
    <header class="bg-white shadow-sm sticky top-0 z-20">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row justify-between items-center">
            <div class="flex items-center mb-4 sm:mb-0">
                <a href="../index.php" class="flex items-center">
                    <i class="fas fa-boxes text-blue-600 text-2xl mr-3"></i>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900"><?php echo $name_corp; ?></h1>
                </a>
                <span class="ml-4 text-sm sm:text-lg text-gray-600">/ Gestión de Movimientos</span>
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

    <main class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                <i class="fas fa-exchange-alt text-purple-600 mr-3"></i> Gestión de Movimientos
            </h2>
            <a href="../"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-arrow-left mr-2"></i> Volver al Inicio
            </a>
        </div>

        <div class="bg-white shadow-md rounded-lg p-6">
            <div class="flex flex-col sm:flex-row justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4 sm:mb-0">Listado de Movimientos</h2>
                <button onclick="openAddMovementModal()"
                    class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded flex items-center w-full sm:w-auto justify-center">
                    <i class="fas fa-plus-circle mr-2"></i> Agregar Movimiento
                </button>
            </div>

            <?php if ($dbError): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                    <p class="font-bold">Error de Base de Datos</p>
                    <p><?php echo htmlspecialchars($dbError); ?></p>
                </div>
            <?php endif; ?>

            <div id="notification-area" class="notification-container">
                <?php if ($mensaje_exito): ?>
                    <div class="notification success">
                        <i class="fas fa-check-circle"></i>
                        <p><?php echo htmlspecialchars($mensaje_exito); ?></p>
                    </div>
                <?php endif; ?>
                <?php if ($mensaje_error): ?>
                    <div class="notification error">
                        <i class="fas fa-exclamation-circle"></i>
                        <p><?php echo htmlspecialchars($mensaje_error); ?></p>
                    </div>
                <?php endif; ?>
                <?php if ($mensaje_aviso): ?>
                    <div class="notification warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p><?php echo htmlspecialchars($mensaje_aviso); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <form method="GET" action="" class="mb-6 flex flex-col sm:flex-row gap-4">
                <div class="flex flex-grow w-full sm:w-auto">
                    <input type="text" name="search" id="search" placeholder="Buscar por descripción, ref, tipo, usuario, bodega..."
                        value="<?php echo htmlspecialchars($searchTerm); ?>"
                        class="flex-grow px-3 py-2 border border-gray-300 rounded-l-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <button type="submit"
                        class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-r-md flex items-center">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <div class="w-full sm:w-auto">
                    <label for="tipo_movimiento_filter" class="sr-only">Filtrar por Tipo de Movimiento:</label>
                    <select name="tipo_movimiento_filter" id="tipo_movimiento_filter"
                        class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 w-full">
                        <option value="all">Todos los Tipos</option>
                        <?php foreach ($tipos_movimiento_para_formulario as $tipo): ?>
                            <option value="<?php echo $tipo['idtipo_movimiento']; ?>" <?php echo ($filterTipoMovimiento == $tipo['idtipo_movimiento']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tipo['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full bg-white table-fixed-layout">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[5%]">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[10%]">Fecha</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[12%]">Tipo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[20%]">Descripción</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[10%]">Ref. Doc.</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[11%]">Bodega Origen</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[11%]">Bodega Destino</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[9%]">Estado</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[12%]">Realizado por</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[10%]">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($movimientos_sistema)): ?>
                            <tr>
                                <td colspan="10" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center"
                                    data-label="Mensaje:">
                                    No se encontraron movimientos<?php echo !empty($searchTerm) ? ' para "' . htmlspecialchars($searchTerm) . '"' : ''; ?>.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($movimientos_sistema as $mov): ?>
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900" data-label="ID:">
                                        <?php echo htmlspecialchars($mov['idmovimiento']); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900" data-label="Fecha:">
                                        <?php echo date('Y-m-d H:i', strtotime(htmlspecialchars($mov['fecha_movimiento']))); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 font-medium"
                                        data-label="Tipo:"><?php echo htmlspecialchars($mov['tipo_movimiento_nombre']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-500 whitespace-normal" data-label="Descripción:">
                                        <?php echo htmlspecialchars($mov['descripcion']); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 truncate" data-label="Ref. Doc.:">
                                        <?php echo htmlspecialchars($mov['documento_referencia'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 truncate" data-label="Origen:">
                                        <?php echo htmlspecialchars($mov['bodega_origen_nombre'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 truncate" data-label="Destino:">
                                        <?php echo htmlspecialchars($mov['bodega_destino_nombre'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500" data-label="Estado:">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                            <?php echo ($mov['estado_movimiento'] == 'cerrado') ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                            <?php echo htmlspecialchars($mov['estado_movimiento']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 truncate" data-label="Realizado por:">
                                        <?php echo htmlspecialchars($mov['usuario_nombre']); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium" data-label="Acciones:">
                                        <button onclick="openViewProductsModal(<?php echo $mov['idmovimiento']; ?>, '<?php echo htmlspecialchars($mov['descripcion']); ?>')"
                                            class="text-blue-500 hover:text-blue-700 mr-2" title="Ver Productos">
                                            <i class="fas fa-eye"></i></button>
                                        <button onclick="openAddProductsToMovementModal(<?php echo $mov['idmovimiento']; ?>, '<?php echo htmlspecialchars($mov['descripcion']); ?>')"
                                            class="text-purple-500 hover:text-purple-700 mr-2" title="Añadir Productos">
                                            <i class="fas fa-plus-square"></i></button>
                                        <button onclick="openEditMovementModal(<?php echo htmlspecialchars(json_encode($mov)); ?>)"
                                            class="text-yellow-500 hover:text-yellow-700 mr-2" title="Editar">
                                            <i class="fas fa-edit"></i></button>
                                        <button onclick="openDeleteMovementModal(<?php echo $mov['idmovimiento']; ?>, '<?php echo htmlspecialchars($mov['descripcion']); ?>')"
                                            class="text-red-600 hover:text-red-900" title="Eliminar">
                                            <i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="addMovementModal"
        class="modal fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full items-center justify-center z-30 p-4">
        <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-md mx-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-gray-800">Agregar Nuevo Movimiento</h3>
                <button onclick="closeAddMovementModal()"
                    class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>
            <form id="addMovementForm" action="op_create_movement.php" method="POST">
                <div class="mb-4">
                    <label for="add_tipo_movimiento_id" class="block text-sm font-medium text-gray-700">Tipo de Movimiento:</label>
                    <select name="tipo_movimiento_id" id="add_tipo_movimiento_id"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        required>
                        <?php foreach ($tipos_movimiento_para_formulario as $tipo): ?>
                            <option value="<?php echo $tipo['idtipo_movimiento']; ?>"><?php echo htmlspecialchars($tipo['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="add_descripcion" class="block text-sm font-medium text-gray-700">Descripción:</label>
                    <textarea name="descripcion" id="add_descripcion" rows="3"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>
                <div class="mb-4">
                    <label for="add_documento_referencia" class="block text-sm font-medium text-gray-700">Documento de Referencia:</label>
                    <input type="text" name="documento_referencia" id="add_documento_referencia"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="mb-4">
                    <label for="add_bodega_origen_id" class="block text-sm font-medium text-gray-700">Bodega de Origen (opcional):</label>
                    <select name="bodega_origen_id" id="add_bodega_origen_id"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Seleccionar Bodega</option>
                        <?php foreach ($bodegas_para_formulario as $bodega): ?>
                            <option value="<?php echo $bodega['idbodega']; ?>"><?php echo htmlspecialchars($bodega['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="add_bodega_destino_id" class="block text-sm font-medium text-gray-700">Bodega de Destino (opcional):</label>
                    <select name="bodega_destino_id" id="add_bodega_destino_id"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Seleccionar Bodega</option>
                        <?php foreach ($bodegas_para_formulario as $bodega): ?>
                            <option value="<?php echo $bodega['idbodega']; ?>"><?php echo htmlspecialchars($bodega['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="add_estado_movimiento" class="block text-sm font-medium text-gray-700">Estado del Movimiento:</label>
                    <select name="estado_movimiento" id="add_estado_movimiento"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        required>
                        <option value="abierto">Abierto</option>
                        <option value="cerrado">Cerrado</option>
                    </select>
                </div>
                <input type="hidden" name="usuario_id" value="<?php echo htmlspecialchars($usuario_actual['idusuario']); ?>">

                <div class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-3">
                    <button type="button" onclick="closeAddMovementModal()"
                        class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 w-full sm:w-auto">Cancelar</button>
                    <button type="submit"
                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 w-full sm:w-auto">Crear Movimiento</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editMovementModal"
        class="modal fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full items-center justify-center z-30 p-4">
        <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-md mx-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-gray-800">Editar Movimiento</h3>
                <button onclick="closeEditMovementModal()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>
            <form id="editMovementForm" action="op_update_movement.php" method="POST">
                <input type="hidden" name="idmovimiento" id="edit_idmovimiento">
                <div class="mb-4">
                    <label for="edit_tipo_movimiento_id" class="block text-sm font-medium text-gray-700">Tipo de Movimiento:</label>
                    <select name="tipo_movimiento_id" id="edit_tipo_movimiento_id"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        required>
                        <?php foreach ($tipos_movimiento_para_formulario as $tipo): ?>
                            <option value="<?php echo $tipo['idtipo_movimiento']; ?>"><?php echo htmlspecialchars($tipo['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="edit_descripcion" class="block text-sm font-medium text-gray-700">Descripción:</label>
                    <textarea name="descripcion" id="edit_descripcion" rows="3"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>
                <div class="mb-4">
                    <label for="edit_documento_referencia" class="block text-sm font-medium text-gray-700">Documento de Referencia:</label>
                    <input type="text" name="documento_referencia" id="edit_documento_referencia"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="mb-4">
                    <label for="edit_bodega_origen_id" class="block text-sm font-medium text-gray-700">Bodega de Origen (opcional):</label>
                    <select name="bodega_origen_id" id="edit_bodega_origen_id"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Seleccionar Bodega</option>
                        <?php foreach ($bodegas_para_formulario as $bodega): ?>
                            <option value="<?php echo $bodega['idbodega']; ?>"><?php echo htmlspecialchars($bodega['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="edit_bodega_destino_id" class="block text-sm font-medium text-gray-700">Bodega de Destino (opcional):</label>
                    <select name="bodega_destino_id" id="edit_bodega_destino_id"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Seleccionar Bodega</option>
                        <?php foreach ($bodegas_para_formulario as $bodega): ?>
                            <option value="<?php echo $bodega['idbodega']; ?>"><?php echo htmlspecialchars($bodega['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="edit_estado_movimiento" class="block text-sm font-medium text-gray-700">Estado del Movimiento:</label>
                    <select name="estado_movimiento" id="edit_estado_movimiento"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        required>
                        <option value="abierto">Abierto</option>
                        <option value="cerrado">Cerrado</option>
                    </select>
                </div>
                <div class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-3">
                    <button type="button" onclick="closeEditMovementModal()"
                        class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 w-full sm:w-auto">Cancelar</button>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 w-full sm:w-auto">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteMovementModal"
        class="modal fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full items-center justify-center z-30 p-4">
        <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-sm mx-auto">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Confirmar Eliminación de Movimiento</h3>
            <p class="text-gray-600 mb-6">¿Estás seguro de que quieres eliminar el movimiento <strong
                    id="deleteMovementDescription"></strong>? Esta acción no se puede deshacer y eliminará todos los productos asociados.</p>
            <form id="deleteMovementForm" action="op_drop_movement.php" method="POST">
                <input type="hidden" name="id_movimiento_eliminar" id="id_movimiento_eliminar">
                <div class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-3">
                    <button type="button" onclick="closeDeleteMovementModal()"
                        class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 w-full sm:w-auto">Cancelar</button>
                    <button type="submit"
                        class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 w-full sm:w-auto">Eliminar Movimiento</button>
                </div>
            </form>
        </div>
    </div>

    <div id="viewProductsModal"
        class="modal fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full items-center justify-center z-40 p-4">
        <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-2xl mx-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-gray-800">Productos en Movimiento: <span id="movementDescription"></span></h3>
                <button onclick="closeViewProductsModal()"
                    class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>
            <div id="productsList" class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Prod.</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre Prod.</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cantidad</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio Unitario</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="productsTableBody">
                        </tbody>
                </table>
            </div>
            <div id="noProductsMessage" class="text-gray-500 text-center mt-4 hidden">No hay productos asociados a este movimiento.</div>
        </div>
    </div>

    <div id="addProductsToMovementModal"
        class="modal fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full items-center justify-center z-40 p-4">
        <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-gray-800">Gestionar Productos para Movimiento: <span id="currentMovementDescription"></span></h3>
                <button onclick="closeAddProductsToMovementModal()"
                    class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>
            <form id="addProductsForm" onsubmit="event.preventDefault(); addProductRow();">
                <input type="hidden" id="movementIdForProducts" name="movement_id">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                    <div>
                        <label for="product_search" class="block text-sm font-medium text-gray-700">Buscar Producto:</label>
                        <input type="text" id="product_search" placeholder="Nombre o #Producto"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <ul id="product_suggestions" class="absolute z-10 bg-white border border-gray-300 rounded-md shadow-lg max-h-40 overflow-y-auto w-auto hidden"></ul>
                    </div>
                    <div>
                        <label for="product_id" class="block text-sm font-medium text-gray-700">ID Producto:</label>
                        <input type="text" id="product_id" readonly
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-100">
                    </div>
                    <div>
                        <label for="cantidad_producto" class="block text-sm font-medium text-gray-700">Cantidad:</label>
                        <input type="number" id="cantidad_producto" min="1" value="1"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="precio_unitario_producto" class="block text-sm font-medium text-gray-700">Precio Unitario:</label>
                        <input type="number" step="0.01" id="precio_unitario_producto" value="0.00"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                <button type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 flex items-center mb-4">
                    <i class="fas fa-plus mr-2"></i> Añadir Producto a la Lista
                </button>
            </form>

            <div class="overflow-x-auto mb-4">
                <table class="min-w-full bg-white border border-gray-200">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Prod.</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cantidad</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio Unit.</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="productsToAddTableBody">
                        </tbody>
                </table>
            </div>
            <div class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-3">
                <button type="button" onclick="closeAddProductsToMovementModal()"
                    class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 w-full sm:w-auto">Cancelar</button>
                <button type="button" onclick="saveMovementProducts()"
                    class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 w-full sm:w-auto">Guardar Productos</button>
            </div>
        </div>
    </div>


    <footer class="bg-white border-t mt-10 py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-gray-500 text-sm">
            <p><?php echo $name_corp; ?> v2.0 &copy; <?php echo date('Y'); ?> - Todos los derechos reservados</p>
        </div>
    </footer>

    <script>
        const addMovementModal = document.getElementById('addMovementModal');
        const editMovementModal = document.getElementById('editMovementModal');
        const deleteMovementModal = document.getElementById('deleteMovementModal');
        const viewProductsModal = document.getElementById('viewProductsModal');
        const addProductsToMovementModal = document.getElementById('addProductsToMovementModal');
        const productsToAddTableBody = document.getElementById('productsToAddTableBody');
        const productSearchInput = document.getElementById('product_search');
        const productSuggestionsList = document.getElementById('product_suggestions');
        const productIdInput = document.getElementById('product_id');
        const cantidadProductoInput = document.getElementById('cantidad_producto');
        const precioUnitarioProductoInput = document.getElementById('precio_unitario_producto');

        let productsToAdd = []; // Array para almacenar los productos a añadir al movimiento

        // Funciones para los modales de Movimiento
        function openAddMovementModal() {
            document.getElementById('addMovementForm').reset();
            addMovementModal.classList.add('active');
        }

        function closeAddMovementModal() {
            addMovementModal.classList.remove('active');
        }

        function openEditMovementModal(movimiento) {
            document.getElementById('edit_idmovimiento').value = movimiento.idmovimiento;
            document.getElementById('edit_tipo_movimiento_id').value = movimiento.tipo_movimiento_id;
            document.getElementById('edit_descripcion').value = movimiento.descripcion;
            document.getElementById('edit_documento_referencia').value = movimiento.documento_referencia;
            document.getElementById('edit_bodega_origen_id').value = movimiento.bodega_origen_id || ''; // Manejar null
            document.getElementById('edit_bodega_destino_id').value = movimiento.bodega_destino_id || ''; // Manejar null
            document.getElementById('edit_estado_movimiento').value = movimiento.estado_movimiento;
            editMovementModal.classList.add('active');
        }

        function closeEditMovementModal() {
            editMovementModal.classList.remove('active');
        }

        function openDeleteMovementModal(idmovimiento, descripcion) {
            document.getElementById('id_movimiento_eliminar').value = idmovimiento;
            document.getElementById('deleteMovementDescription').textContent = descripcion;
            deleteMovementModal.classList.add('active');
        }

        function closeDeleteMovementModal() {
            deleteMovementModal.classList.remove('active');
        }

        // Funciones para el modal de Ver Productos del Movimiento
        async function openViewProductsModal(idmovimiento, descripcion) {
            document.getElementById('movementDescription').textContent = descripcion;
            document.getElementById('productsTableBody').innerHTML = ''; // Limpiar tabla
            document.getElementById('noProductsMessage').classList.add('hidden'); // Ocultar mensaje de no productos
            viewProductsModal.classList.add('active');

            try {
                const response = await fetch(`op_get_movement_products.php?movement_id=${idmovimiento}`);
                const data = await response.json();

                if (data.success && data.products.length > 0) {
                    let html = '';
                    data.products.forEach(product => {
                        html += `
                            <tr data-product-movement-id="${product.id}">
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">${product.producto_id}</td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">${product.nombre_producto}</td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">${product.cantidad}</td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">$${parseFloat(product.precio_unitario).toFixed(2)}</td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm font-medium">
                                    <button onclick="deleteProductFromMovement(${product.id}, ${idmovimiento})"
                                        class="text-red-600 hover:text-red-900" title="Eliminar Producto">
                                        <i class="fas fa-times-circle"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    document.getElementById('productsTableBody').innerHTML = html;
                } else {
                    document.getElementById('noProductsMessage').classList.remove('hidden');
                }
            } catch (error) {
                console.error('Error al cargar productos del movimiento:', error);
                document.getElementById('noProductsMessage').textContent = 'Error al cargar los productos. Intente de nuevo.';
                document.getElementById('noProductsMessage').classList.remove('hidden');
            }
        }

        function closeViewProductsModal() {
            viewProductsModal.classList.remove('active');
        }

        async function deleteProductFromMovement(movementProductId, movementId) {
            if (confirm('¿Estás seguro de que quieres eliminar este producto del movimiento?')) {
                try {
                    const response = await fetch('op_delete_movement_product.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ id: movementProductId }),
                    });
                    const data = await response.json();
                    if (data.success) {
                        showNotification('success', 'Producto eliminado del movimiento exitosamente.');
                        // Volver a cargar los productos del movimiento para reflejar el cambio
                        openViewProductsModal(movementId, document.getElementById('movementDescription').textContent);
                    } else {
                        showNotification('error', data.message || 'Error al eliminar el producto del movimiento.');
                    }
                } catch (error) {
                    console.error('Error al eliminar producto del movimiento:', error);
                    showNotification('error', 'Error de conexión o del servidor al eliminar producto.');
                }
            }
        }

        // Funciones para el modal de Agregar/Editar Productos a un Movimiento
        function openAddProductsToMovementModal(movementId, description) {
            document.getElementById('currentMovementDescription').textContent = description;
            document.getElementById('movementIdForProducts').value = movementId;
            productsToAdd = []; // Limpiar la lista de productos pendientes
            updateProductsToAddTable(); // Actualizar la tabla vacía
            document.getElementById('addProductsForm').reset(); // Limpiar el formulario de añadir producto
            productSearchInput.value = '';
            productIdInput.value = '';
            addProductsToMovementModal.classList.add('active');
        }

        function closeAddProductsToMovementModal() {
            addProductsToMovementModal.classList.remove('active');
        }

        // Búsqueda de productos en tiempo real para el modal de añadir productos
        let searchTimeout;
        productSearchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            const query = productSearchInput.value.trim();
            if (query.length < 3) { // Mínimo 3 caracteres para buscar
                productSuggestionsList.innerHTML = '';
                productSuggestionsList.classList.add('hidden');
                productIdInput.value = ''; // Limpiar ID si la búsqueda es muy corta
                return;
            }

            searchTimeout = setTimeout(async () => {
                try {
                    const response = await fetch(`op_search_products.php?q=${encodeURIComponent(query)}`);
                    const data = await response.json();
                    productSuggestionsList.innerHTML = '';
                    if (data.success && data.products.length > 0) {
                        data.products.forEach(product => {
                            const li = document.createElement('li');
                            li.classList.add('p-2', 'cursor-pointer', 'hover:bg-gray-200');
                            li.textContent = `${product.nombre} (#${product.numero_producto})`;
                            li.onclick = () => {
                                productSearchInput.value = product.nombre;
                                productIdInput.value = product.idproducto;
                                precioUnitarioProductoInput.value = parseFloat(product.precio).toFixed(2);
                                productSuggestionsList.classList.add('hidden');
                            };
                            productSuggestionsList.appendChild(li);
                        });
                        productSuggestionsList.classList.remove('hidden');
                    } else {
                        productSuggestionsList.classList.add('hidden');
                    }
                } catch (error) {
                    console.error('Error fetching product suggestions:', error);
                    productSuggestionsList.classList.add('hidden');
                }
            }, 300); // Retraso de 300ms
        });

        // Ocultar sugerencias si se hace clic fuera
        document.addEventListener('click', (event) => {
            if (!productSearchInput.contains(event.target) && !productSuggestionsList.contains(event.target)) {
                productSuggestionsList.classList.add('hidden');
            }
        });

        function addProductRow() {
            const productId = productIdInput.value;
            const productName = productSearchInput.value;
            const cantidad = parseInt(cantidadProductoInput.value);
            const precioUnitario = parseFloat(precioUnitarioProductoInput.value);

            if (!productId || !productName || isNaN(cantidad) || cantidad <= 0 || isNaN(precioUnitario)) {
                showNotification('warning', 'Por favor, selecciona un producto y asegúrate de que la cantidad y el precio sean válidos.');
                return;
            }

            // Verificar si el producto ya está en la lista para añadir
            const existingProductIndex = productsToAdd.findIndex(p => p.producto_id === productId);
            if (existingProductIndex > -1) {
                // Actualizar cantidad y precio si ya existe
                productsToAdd[existingProductIndex].cantidad += cantidad;
                productsToAdd[existingProductIndex].precio_unitario = precioUnitario; // Asumimos que el precio se actualiza
                showNotification('aviso', `Cantidad del producto "${productName}" actualizada.`);
            } else {
                productsToAdd.push({
                    producto_id: productId,
                    nombre_producto: productName,
                    cantidad: cantidad,
                    precio_unitario: precioUnitario
                });
            }

            updateProductsToAddTable();
            document.getElementById('addProductsForm').reset(); // Limpiar formulario
            productSearchInput.value = '';
            productIdInput.value = '';
            cantidadProductoInput.value = 1;
            precioUnitarioProductoInput.value = '0.00';
        }

        function removeProductRow(index) {
            productsToAdd.splice(index, 1);
            updateProductsToAddTable();
            showNotification('aviso', 'Producto eliminado de la lista para añadir.');
        }

        function updateProductsToAddTable() {
            productsToAddTableBody.innerHTML = '';
            if (productsToAdd.length === 0) {
                productsToAddTableBody.innerHTML = '<tr><td colspan="5" class="px-4 py-2 text-center text-gray-500">No hay productos en la lista.</td></tr>';
                return;
            }
            productsToAdd.forEach((product, index) => {
                const row = productsToAddTableBody.insertRow();
                row.innerHTML = `
                    <td class="px-4 py-2 text-sm text-gray-900">${product.producto_id}</td>
                    <td class="px-4 py-2 text-sm text-gray-900">${product.nombre_producto}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">${product.cantidad}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">$${product.precio_unitario.toFixed(2)}</td>
                    <td class="px-4 py-2 text-sm font-medium">
                        <button type="button" onclick="removeProductRow(${index})" class="text-red-600 hover:text-red-900" title="Eliminar de la lista">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                `;
            });
        }

        async function saveMovementProducts() {
            const movementId = document.getElementById('movementIdForProducts').value;
            if (productsToAdd.length === 0) {
                showNotification('warning', 'No hay productos para guardar en este movimiento.');
                return;
            }

            try {
                const response = await fetch('op_add_movement_products.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        movement_id: movementId,
                        products: productsToAdd
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    showNotification('success', data.message || 'Productos guardados exitosamente en el movimiento.');
                    closeAddProductsToMovementModal();
                    // Opcional: recargar la página o actualizar la tabla de movimientos si es necesario
                    // window.location.reload();
                } else {
                    showNotification('error', data.message || 'Error al guardar los productos en el movimiento.');
                }
            } catch (error) {
                console.error('Error al guardar productos del movimiento:', error);
                showNotification('error', 'Error de conexión o del servidor al guardar productos.');
            }
        }


        // Función para mostrar notificaciones (reutilizada de index.php)
        function showNotification(type, message) {
            const notificationArea = document.getElementById('notification-area');
            const notification = document.createElement('div');
            notification.classList.add('notification', type);
            notification.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : (type === 'error' ? 'fa-exclamation-circle' : 'fa-exclamation-triangle')}"></i><p>${message}</p>`;
            notificationArea.appendChild(notification);

            // Eliminar la notificación después de 5 segundos
            setTimeout(() => {
                notification.remove();
            }, 5000);
        }


        // Cerrar modal si se hace clic fuera del contenido (se requiere que el modal no tenga display: flex al inicio, y que la clase active lo ponga)
        window.onclick = function (event) {
            if (event.target == addMovementModal) {
                closeAddMovementModal();
            }
            if (event.target == editMovementModal) {
                closeEditMovementModal();
            }
            if (event.target == deleteMovementModal) {
                closeDeleteMovementModal();
            }
            if (event.target == viewProductsModal) {
                closeViewProductsModal();
            }
            if (event.target == addProductsToMovementModal) {
                closeAddProductsToMovementModal();
            }
        }
    </script>

</body>

</html>