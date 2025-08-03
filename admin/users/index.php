<?php
session_start();
define('PROTECT_CONFIG', true);

// 1. Verificar si la sesión 'logged_in' existe y es verdadera.
// Esta es la bandera principal de que el usuario ha iniciado sesión.
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirigir al login si no hay sesión activa
    header("Location: ../../");
    exit();
}

// 2. Usar $_SESSION['usuario_data'] que es donde se guarda la información del usuario
// según tu script op_validar.php.
if (!isset($_SESSION['usuario_data']) || !is_array($_SESSION['usuario_data'])) {
    // Si por alguna razón usuario_data no está configurado (aunque logged_in sí),
    // también redirigir para evitar errores.
    $_SESSION['error'] = 'Error de sesión. Por favor, inicie sesión de nuevo.';
    header("Location: ../../");
    exit();
}

$usuario_actual = $_SESSION['usuario_data']; // ¡Cambiado de $_SESSION['usuario'] a $_SESSION['usuario_data']!

    require_once '../../assets/config/info.php';
// Verificar si el usuario tiene el permiso 'acceso_admin'.
// 'usuario_permisos' debería estar disponible en $_SESSION gracias a op_validar.php.
if (isset($_SESSION['usuario_permisos']) && in_array($op_view_gestor_usuario, $_SESSION['usuario_permisos'])) {
    $esAdmin = true; // El usuario tiene el permiso de administrador
} else {
    // Si no tiene el permiso 'acceso_view_gestor_usuario', redirigir al login y mostrar el aviso.
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => "Acceso denegado. No cuentas con el permiso " . $op_view_gestor_usuario . ". Por favor Contacta con tu departamente de IT o administrador de ayudas"
    ];
    header("Location: ../"); // Redirige al login o a una página de acceso denegado específica.
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
$filterRolId = $_GET['rol_filter'] ?? ''; // Nuevo: para el filtro por rol
$dbError = null;
$usuarios_sistema = [];
$roles_para_formulario = []; // Para el select de los modales

try {
    // Asegúrate de que este path sea correcto desde 'admin/users/' a 'assets/config/db.php'
    // ../../ indica subir dos directorios para llegar a la raíz del proyecto.
    require_once '../../assets/config/db.php';

    // Aquí, la variable $pdo debería estar disponible si db.php establece la conexión globalmente
    // o si devuelve el objeto PDO y lo asignamos.
    // Asumo que db.php crea una variable $pdo global o similar.
    // Si tu db.php no crea una variable $pdo automáticamente, podrías necesitar esto:
    // global $pdo;
    // if (!isset($pdo)) {
    //     // Esto es una medida de seguridad, db.php debería manejar su propia conexión
    //     throw new Exception("La conexión PDO no está disponible después de incluir db.php");
    // }

    // Validación para asegurar que $pdo está disponible
    if (!isset($pdo) || !$pdo instanceof PDO) {
        throw new PDOException("La conexión PDO no está disponible. Asegúrate de que db.php la inicializa correctamente.");
    }

    // Obtener todos los roles para el filtro y los modales
    $stmt_roles = $pdo->query("SELECT idrol, nombre FROM inventario360_rol ORDER BY nombre ASC");
    $roles_para_formulario = $stmt_roles->fetchAll(PDO::FETCH_ASSOC);

    // Construir la consulta SQL para buscar y filtrar usuarios
    $sql = "SELECT u.idusuario, u.nombre, u.correo, u.rol_id, r.nombre as rol_nombre
            FROM inventario360_usuario u
            LEFT JOIN inventario360_rol r ON u.rol_id = r.idrol";

    $whereClauses = [];
    $params = [];

    // Lógica de búsqueda por texto (nombre, correo, ID de usuario, nombre de rol)
    if (!empty($searchTerm)) {
        $whereClauses[] = "(u.nombre LIKE :searchTermNombre OR u.correo LIKE :searchTermCorreo OR u.idusuario = :searchTermIdUsuario OR r.nombre LIKE :searchTermRolNombre)";
        $params[':searchTermNombre'] = '%' . $searchTerm . '%';
        $params[':searchTermCorreo'] = '%' . $searchTerm . '%';
        // Convertir searchTerm a entero para comparación de ID, si es numérico.
        // Esto evita errores si searchTerm no es un número.
        $params[':searchTermIdUsuario'] = (int) $searchTerm;
        $params[':searchTermRolNombre'] = '%' . $searchTerm . '%';
    }

    // Lógica de filtro por rol
    if (!empty($filterRolId) && $filterRolId !== 'all') { // 'all' para mostrar todos
        $whereClauses[] = "u.rol_id = :filterRolId";
        $params[':filterRolId'] = $filterRolId;
    }

    // Combinar las cláusulas WHERE si existen
    if (!empty($whereClauses)) {
        $sql .= " WHERE " . implode(" AND ", $whereClauses);
    }

    $sql .= " ORDER BY u.idusuario ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $usuarios_sistema = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $dbError = "Error al conectar o consultar la base de datos. Mostrando datos de ejemplo. Detalles: " . $e->getMessage();
    // Fallback a datos de ejemplo si la BD falla o $usuarios_sistema está vacío
    // Esto es útil para depuración, pero en producción podrías querer un mensaje más genérico
    $usuarios_ejemplo = [
        ['idusuario' => 101, 'nombre' => 'Usuario Demo 1', 'correo' => 'demo1@example.com', 'rol_id' => 1, 'rol_nombre' => 'Administrador (Demo)'],
        ['idusuario' => 102, 'nombre' => 'Usuario Demo 2', 'correo' => 'demo2@example.com', 'rol_id' => 2, 'rol_nombre' => 'Supervisor (Demo)'],
        ['idusuario' => 103, 'nombre' => 'Usuario Demo 3', 'correo' => 'demo3@example.com', 'rol_id' => 3, 'rol_nombre' => 'Empleado (Demo)'],
    ];

    $filtered_usuarios_ejemplo = [];
    foreach ($usuarios_ejemplo as $u) {
        $matchesSearch = true;
        $matchesFilter = true;

        if (!empty($searchTerm)) {
            $searchLower = strtolower($searchTerm);
            if (
                !(stripos($u['nombre'], $searchLower) !== false ||
                    stripos($u['correo'], $searchLower) !== false ||
                    (string) $u['idusuario'] === $searchTerm ||
                    stripos($u['rol_nombre'], $searchLower) !== false)
            ) {
                $matchesSearch = false;
            }
        }

        if (!empty($filterRolId) && $filterRolId !== 'all') {
            if ($u['rol_id'] != $filterRolId) {
                $matchesFilter = false;
            }
        }

        if ($matchesSearch && $matchesFilter) {
            $filtered_usuarios_ejemplo[] = $u;
        }
    }
    $usuarios_sistema = $filtered_usuarios_ejemplo;

    $roles_para_formulario = [
        ['idrol' => 1, 'nombre' => 'Administrador (Demo)'],
        ['idrol' => 2, 'nombre' => 'Supervisor (Demo)'],
        ['idrol' => 3, 'nombre' => 'Empleado (Demo)'],
    ];

}

// ... el resto de tu HTML y JavaScript sigue igual
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - <?php echo $name_corp; ?></title>
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

        /* Cambiado a auto para mejor adaptabilidad */
        @media (min-width: 640px) {

            /* sm breakpoint */
            .table-fixed-layout {
                table-layout: fixed;
            }

            /* Vuelve a fixed en pantallas más grandes */
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
            /* Ancho máximo para las notificaciones */
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
            /* Duración de la animación */
        }

        .notification.success {
            background-color: #d1fae5;
            /* green-100 */
            border-left: 4px solid #10b981;
            /* green-500 */
            color: #065f46;
            /* green-800 */
        }

        .notification.error {
            background-color: #fee2e2;
            /* red-100 */
            border-left: 4px solid #ef4444;
            /* red-500 */
            color: #991b1b;
            /* red-800 */
        }

        .notification.warning {
            background-color: #fffbeb;
            /* yellow-100 */
            border-left: 4px solid #f59e0b;
            /* yellow-500 */
            color: #92400e;
            /* yellow-800 */
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

            /* Extra small screens, below sm breakpoint */
            .table-fixed-layout thead {
                display: none;
                /* Oculta el encabezado de la tabla en móviles */
            }

            .table-fixed-layout,
            .table-fixed-layout tbody,
            .table-fixed-layout tr,
            .table-fixed-layout td {
                display: block;
                /* Hace que las filas y celdas se comporten como bloques */
                width: 100%;
            }

            .table-fixed-layout tr {
                margin-bottom: 1rem;
                /* Espacio entre las "tarjetas" de fila */
                border: 1px solid #e5e7eb;
                /* Borde para cada "tarjeta" */
                border-radius: 0.5rem;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                background-color: white;
            }

            .table-fixed-layout td {
                text-align: right;
                /* Alinea el contenido a la derecha */
                padding-left: 50%;
                /* Espacio para la etiqueta */
                position: relative;
                /* Para posicionar la etiqueta */
                border: none;
                /* Quita los bordes de celda individuales */
                padding-top: 0.5rem;
                padding-bottom: 0.5rem;
            }

            .table-fixed-layout td::before {
                content: attr(data-label);
                /* Usa el atributo data-label como etiqueta */
                position: absolute;
                left: 0.75rem;
                /* Posiciona la etiqueta a la izquierda */
                width: 45%;
                /* Ancho de la etiqueta */
                padding-right: 0.75rem;
                white-space: nowrap;
                text-align: left;
                /* Alinea la etiqueta a la izquierda */
                font-weight: bold;
                color: #4b5563;
                /* gray-700 */
            }

            /* Ajustes específicos para cada columna en móvil */
            .table-fixed-layout td:nth-of-type(1)::before {
                content: "ID:";
            }

            .table-fixed-layout td:nth-of-type(2)::before {
                content: "Nombre:";
            }

            .table-fixed-layout td:nth-of-type(3)::before {
                content: "Correo:";
            }

            .table-fixed-layout td:nth-of-type(4)::before {
                content: "Rol:";
            }

            .table-fixed-layout td:nth-of-type(5)::before {
                content: "Contraseña:";
            }

            .table-fixed-layout td:nth-of-type(6)::before {
                content: "Acciones:";
            }

            .table-fixed-layout td:nth-of-type(6) {
                /* Columna de acciones */
                text-align: center;
                /* Centra las acciones en móvil */
                padding-left: 0.75rem;
                /* Ajusta el padding para centrar */
            }

            .table-fixed-layout td:nth-of-type(6) button {
                margin: 0 0.25rem;
                /* Espacio entre botones */
            }
        }
    </style>
</head>

<body class="bg-gray-100 font-sans">
    <header class="bg-white shadow-sm sticky top-0 z-20">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row justify-between items-center">
            <div class="flex items-center mb-4 sm:mb-0">
                <a href="../" class="flex items-center">
                    <i class="fas fa-boxes text-blue-600 text-2xl mr-3"></i>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900"><?php echo $name_corp; ?></h1>
                </a>
                <span class="ml-4 text-sm sm:text-lg text-gray-600">/ Gestión de Usuarios</span>
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
                    <i class="fas fa-tags text-indigo-600 mr-3"></i> Gestión de Usuarios
                </h2>
                <a href="../"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-arrow-left mr-2"></i> Volver al Inicio
                </a>
            </div>

        <div class="bg-white shadow-md rounded-lg p-6">
            <div class="flex flex-col sm:flex-row justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4 sm:mb-0">Listado de Usuarios</h2>
                <button onclick="openAddUserModal()"
                    class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded flex items-center w-full sm:w-auto justify-center">
                    <i class="fas fa-user-plus mr-2"></i> Agregar Usuario
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
                    <input type="text" name="search" id="search" placeholder="Buscar por nombre, correo, ID o rol..."
                        value="<?php echo htmlspecialchars($searchTerm); ?>"
                        class="flex-grow px-3 py-2 border border-gray-300 rounded-l-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <button type="submit"
                        class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-r-md flex items-center">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <div class="w-full sm:w-auto">
                    <label for="rol_filter" class="sr-only">Filtrar por Rol:</label>
                    <select name="rol_filter" id="rol_filter"
                        class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 w-full">
                        <option value="all">Todos los Roles</option>
                        <?php foreach ($roles_para_formulario as $rol): ?>
                            <option value="<?php echo $rol['idrol']; ?>" <?php echo ($filterRolId == $rol['idrol']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($rol['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full bg-white table-fixed-layout">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/12">
                                ID</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-2/12">
                                Nombre</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-3/12">
                                Correo</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-2/12">
                                Rol</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-2/12">
                                Contraseña</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-2/12">
                                Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($usuarios_sistema)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center"
                                    data-label="Mensaje:">
                                    No se encontraron
                                    usuarios<?php echo !empty($searchTerm) ? ' para "' . htmlspecialchars($searchTerm) . '"' : ''; ?>.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($usuarios_sistema as $usr): ?>
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900" data-label="ID:">
                                        <?php echo htmlspecialchars($usr['idusuario']); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 font-medium truncate"
                                        data-label="Nombre:"><?php echo htmlspecialchars($usr['nombre']); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 truncate" data-label="Correo:">
                                        <?php echo htmlspecialchars($usr['correo']); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 truncate" data-label="Rol:">
                                        <?php echo htmlspecialchars($usr['rol_nombre'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500" data-label="Contraseña:">
                                        <span id="password_display_<?php echo $usr['idusuario']; ?>">********</span>
                                        <button
                                            onclick="openViewPasswordModal(<?php echo $usr['idusuario']; ?>, '<?php echo htmlspecialchars($usr['nombre']); ?>')"
                                            class="text-blue-500 hover:text-blue-700 ml-2" title="Ver Contraseña">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium" data-label="Acciones:">
                                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($usr)); ?>)"
                                            class="text-yellow-500 hover:text-yellow-700 mr-2" title="Editar"><i
                                                class="fas fa-edit"></i></button>
                                        <button
                                            onclick="openDeleteModal(<?php echo $usr['idusuario']; ?>, '<?php echo htmlspecialchars($usr['nombre']); ?>')"
                                            class="text-red-600 hover:text-red-900" title="Eliminar"><i
                                                class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="editUserModal"
        class="modal fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full items-center justify-center z-30 p-4">
        <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-md mx-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-gray-800">Editar Usuario</h3>
                <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>
            <form id="editUserForm" action="op_update_user.php" method="POST">
                <input type="hidden" name="id_usuario" id="edit_id_usuario">
                <div class="mb-4">
                    <label for="edit_nombre" class="block text-sm font-medium text-gray-700">Nombre:</label>
                    <input type="text" name="nombre" id="edit_nombre"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        required>
                </div>
                <div class="mb-4">
                    <label for="edit_correo" class="block text-sm font-medium text-gray-700">Correo Electrónico:</label>
                    <input type="email" name="correo" id="edit_correo"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        required>
                </div>
                <div class="mb-4">
                    <label for="edit_rol_id" class="block text-sm font-medium text-gray-700">Rol:</label>
                    <select name="rol_id" id="edit_rol_id"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        required>
                        <?php foreach ($roles_para_formulario as $rol): ?>
                            <option value="<?php echo $rol['idrol']; ?>"><?php echo htmlspecialchars($rol['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="edit_contrasena" class="block text-sm font-medium text-gray-700">Nueva Contraseña (dejar
                        en blanco para no cambiar):</label>
                    <input type="password" name="contrasena" id="edit_contrasena"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-3">
                    <button type="button" onclick="closeEditModal()"
                        class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 w-full sm:w-auto">Cancelar</button>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 w-full sm:w-auto">Guardar
                        Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <div id="addUserModal"
        class="modal fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full items-center justify-center z-30 p-4">
        <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-md mx-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-gray-800">Agregar Nuevo Usuario</h3>
                <button onclick="closeAddUserModal()"
                    class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>
            <form id="addUserForm" action="op_create_user.php" method="POST">
                <div class="mb-4">
                    <label for="add_nombre" class="block text-sm font-medium text-gray-700">Nombre:</label>
                    <input type="text" name="nombre" id="add_nombre"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        required>
                </div>
                <div class="mb-4">
                    <label for="add_correo" class="block text-sm font-medium text-gray-700">Correo Electrónico:</label>
                    <input type="email" name="correo" id="add_correo"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        required>
                </div>
                <div class="mb-4">
                    <label for="add_contrasena" class="block text-sm font-medium text-gray-700">Contraseña:</label>
                    <input type="password" name="contrasena" id="add_contrasena"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        required>
                </div>
                <div class="mb-4">
                    <label for="add_rol_id" class="block text-sm font-medium text-gray-700">Rol:</label>
                    <select name="rol_id" id="add_rol_id"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        required>
                        <?php foreach ($roles_para_formulario as $rol): ?>
                            <option value="<?php echo $rol['idrol']; ?>"><?php echo htmlspecialchars($rol['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-3">
                    <button type="button" onclick="closeAddUserModal()"
                        class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 w-full sm:w-auto">Cancelar</button>
                    <button type="submit"
                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 w-full sm:w-auto">Agregar
                        Usuario</button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteUserModal"
        class="modal fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full items-center justify-center z-30 p-4">
        <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-sm mx-auto">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Confirmar Eliminación de Usuario</h3>
            <p class="text-gray-600 mb-6">¿Estás seguro de que quieres eliminar al usuario <strong
                    id="deleteUserName"></strong>? Esta acción no se puede deshacer.</p>
            <form id="deleteUserForm" action="op_drop_user.php" method="POST">
                <input type="hidden" name="id_usuario_eliminar" id="id_usuario_eliminar">
                <div class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-3">
                    <button type="button" onclick="closeDeleteModal()"
                        class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 w-full sm:w-auto">Cancelar</button>
                    <button type="submit"
                        class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 w-full sm:w-auto">Eliminar
                        Usuario</button>
                </div>
            </form>
        </div>
    </div>

    <div id="viewPasswordModal"
        class="modal fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full items-center justify-center z-40 p-4">
        <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-md mx-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-gray-800">Ver Contraseña de Usuario</h3>
                <button onclick="closeViewPasswordModal()"
                    class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>
            <form id="viewPasswordForm">
                <input type="hidden" name="user_id_to_view" id="user_id_to_view">
                <p class="text-gray-700 mb-4">Para ver la contraseña de <strong id="viewPasswordUserName"></strong>, por
                    favor introduce tus credenciales de administrador.</p>
                <div class="mb-4">
                    <label for="admin_email" class="block text-sm font-medium text-gray-700">Correo de
                        Administrador:</label>
                    <input type="email" name="admin_email" id="admin_email"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        required>
                </div>
                <div class="mb-4">
                    <label for="admin_password" class="block text-sm font-medium text-gray-700">Contraseña de
                        Administrador:</label>
                    <input type="password" name="admin_password" id="admin_password"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        required>
                </div>
                <div id="viewPasswordError" class="text-red-500 text-sm mb-4 hidden"></div>
                <div class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-3">
                    <button type="button" onclick="closeViewPasswordModal()"
                        class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 w-full sm:w-auto">Cancelar</button>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 w-full sm:w-auto">Ver
                        Contraseña</button>
                </div>
            </form>
        </div>
    </div>


    <footer class="bg-white border-t mt-10 py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-gray-500 text-sm">
            <p><?php echo $name_corp; ?> v2.0 &copy; <?php echo date('Y'); ?> - Todos los derechos reservados</p>
        </div>
    </footer>

    <script>
        const editUserModal = document.getElementById('editUserModal');
        const addUserModal = document.getElementById('addUserModal');
        const deleteUserModal = document.getElementById('deleteUserModal');
        const viewPasswordModal = document.getElementById('viewPasswordModal');
        const viewPasswordError = document.getElementById('viewPasswordError');


        function openEditModal(usuario) {
            document.getElementById('edit_id_usuario').value = usuario.idusuario;
            document.getElementById('edit_nombre').value = usuario.nombre;
            document.getElementById('edit_correo').value = usuario.correo;
            document.getElementById('edit_rol_id').value = usuario.rol_id;
            document.getElementById('edit_contrasena').value = ''; // Limpiar campo contraseña
            editUserModal.classList.add('active');
        }

        function closeEditModal() {
            editUserModal.classList.remove('active');
        }

        function openAddUserModal() {
            document.getElementById('addUserForm').reset(); // Limpia el formulario
            addUserModal.classList.add('active');
        }

        function closeAddUserModal() {
            addUserModal.classList.remove('active');
        }


        function openDeleteModal(idusuario, nombre) {
            document.getElementById('id_usuario_eliminar').value = idusuario;
            document.getElementById('deleteUserName').textContent = nombre;
            deleteUserModal.classList.add('active');
        }

        function closeDeleteModal() {
            deleteUserModal.classList.remove('active');
        }

        let currentUserIdToView = null; // Variable para almacenar el ID del usuario cuya contraseña se desea ver

        function openViewPasswordModal(idusuario, nombre) {
            currentUserIdToView = idusuario; // Guardar el ID del usuario
            document.getElementById('user_id_to_view').value = idusuario;
            document.getElementById('viewPasswordUserName').textContent = nombre;
            document.getElementById('admin_email').value = ''; // Limpiar campo
            document.getElementById('admin_password').value = ''; // Limpiar campo
            viewPasswordError.classList.add('hidden');
            viewPasswordModal.classList.add('active');
        }

        function closeViewPasswordModal() {
            viewPasswordModal.classList.remove('active');
            viewPasswordError.classList.add('hidden'); // Ocultar errores al cerrar
        }

        // Manejar el envío del formulario de ver contraseña
        document.getElementById('viewPasswordForm').addEventListener('submit', async function (event) {
            event.preventDefault(); // Evitar el envío normal del formulario

            const adminEmail = document.getElementById('admin_email').value;
            const adminPassword = document.getElementById('admin_password').value;
            const userId = document.getElementById('user_id_to_view').value;

            // Realizar la solicitud AJAX para validar al administrador y obtener la contraseña del usuario
            try {
                const response = await fetch('op_get_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        admin_email: adminEmail,
                        admin_password: adminPassword,
                        user_id: userId
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    // Mostrar la contraseña en la tabla y luego volver a ocultarla después de un tiempo
                    const passwordDisplayElement = document.getElementById(`password_display_${userId}`);
                    passwordDisplayElement.textContent = data.password;
                    viewPasswordError.classList.add('hidden');
                    closeViewPasswordModal();

                    setTimeout(() => {
                        passwordDisplayElement.textContent = '********';
                    }, 10000); // Ocultar después de 10 segundos
                } else {
                    viewPasswordError.textContent = data.message;
                    viewPasswordError.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Error al solicitar la contraseña:', error);
                viewPasswordError.textContent = 'Error de conexión o del servidor.';
                viewPasswordError.classList.remove('hidden');
            }
        });


        // Cerrar modal si se hace clic fuera del contenido
        window.onclick = function (event) {
            if (event.target == editUserModal) {
                closeEditModal();
            }
            if (event.target == addUserModal) {
                closeAddUserModal();
            }
            if (event.target == deleteUserModal) {
                closeDeleteModal();
            }
            if (event.target == viewPasswordModal) {
                closeViewPasswordModal();
            }
        }
    </script>

</body>

</html>