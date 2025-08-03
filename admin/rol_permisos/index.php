<?php
define('PROTECT_CONFIG', true);
session_start();

// Redirige al login si no hay sesión activa o si el usuario no es admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['usuario_data'])) {
    $_SESSION['error'] = 'Por favor, inicia sesión para acceder.';
    header("Location: ../");
    exit();
}

$usuario = $_SESSION['usuario_data'];
$esAdmin = false;

    require_once '../../assets/config/info.php';
// Verificar si el usuario tiene el permiso 'acceso_admin'.
// 'usuario_permisos' debería estar disponible en $_SESSION gracias a op_validar.php.
if (isset($_SESSION['usuario_permisos']) && in_array($op_view_gestor_rol_permisos, $_SESSION['usuario_permisos'])) {
    $esAdmin = true; // El usuario tiene el permiso de administrador
} else {
    // Si no tiene el permiso 'acceso_view_gestor_usuario', redirigir al login y mostrar el aviso.
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => "Acceso denegado. No cuentas con el permiso " . $op_view_gestor_rol_permisos . ". Por favor Contacta con tu departamente de IT o administrador de ayudas"
    ];
    header("Location: ../"); // Redirige al login o a una página de acceso denegado específica.
    exit();
}
require_once '../../assets/config/db.php';

$roles = [];
$permisos_disponibles = []; // Todos los permisos para asignación a roles
$todos_los_permisos = []; // Todos los permisos para la tabla de gestión de permisos

try {
    // Obtener todos los roles
    $stmt_roles = $pdo->query("SELECT idrol, nombre, estado FROM inventario360_rol ORDER BY nombre");
    $roles = $stmt_roles->fetchAll(PDO::FETCH_ASSOC);

    // Obtener todos los permisos disponibles para asignación a roles y para la tabla de permisos
    $stmt_permisos = $pdo->query("SELECT idpermiso, nombre, descripcion, estado FROM inventario360_permiso ORDER BY nombre");
    $permisos_disponibles = $stmt_permisos->fetchAll(PDO::FETCH_ASSOC);
    $todos_los_permisos = $permisos_disponibles; // Misma lista para ambos propósitos por ahora

} catch (PDOException $e) {
    echo "Error al cargar datos de la base de datos: " . htmlspecialchars($e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Roles y Permisos - <?php echo $name_corp; ?></title>
    <link rel="shortcut icon" href="<?php echo $logo; ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Estilos generales para modales */
        .modal {
            display: none;
            /* Hidden by default */
            position: fixed;
            /* Stay in place */
            z-index: 100;
            /* Sit on top */
            left: 0;
            top: 0;
            width: 100%;
            /* Full width */
            height: 100%;
            /* Full height */
            overflow: auto;
            /* Enable scroll if needed */
            background-color: rgba(0, 0, 0, 0.5);
            /* Black w/ opacity */
            justify-content: center;
            /* Center horizontally */
            align-items: center;
            /* Center vertically */
        }

        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            /* Adjust max-width as needed for content */
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .close-button {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close-button:hover,
        .close-button:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        /* Estilos específicos para el modal de confirmación y credenciales */
        #confirmModal .modal-content,
        #credentialsModal .modal-content {
            max-width: 400px;
            /* Más pequeño para confirmación */
            text-align: center;
        }

        #confirmModal .modal-content h2,
        #credentialsModal .modal-content h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        #confirmModal .modal-content p,
        #credentialsModal .modal-content p {
            margin-bottom: 1.5rem;
        }

        #confirmModal .modal-content .flex button,
        #credentialsModal .modal-content .flex button {
            width: 100%;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">
    <header class="bg-white shadow-sm sticky top-0 z-20">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row justify-between items-center">
            <div class="flex items-center mb-4 sm:mb-0">
                <a href="../index.php" class="flex items-center">
                    <i class="fas fa-boxes text-blue-600 text-2xl mr-3"></i>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900"><?php echo $name_corp; ?></h1>
                </a>
                <span class="ml-4 text-sm sm:text-lg text-gray-600">/ Gestión de Roles y Permisos</span>
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

        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                <i class="fas fa-tags text-indigo-600 mr-3"></i> Gestión de Roles y permisos
            </h2>
            <a href="../"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-arrow-left mr-2"></i> Volver al Inicio
            </a>
        </div>

        <?php
        if (isset($_SESSION['mensaje'])) {
            $tipo = $_SESSION['mensaje']['tipo'];
            $texto = $_SESSION['mensaje']['texto'];
            $clase_alerta = ($tipo == 'exito') ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700';
            echo '<div class="mb-4 p-4 border rounded-md ' . $clase_alerta . '" role="alert">';
            echo '<p>' . htmlspecialchars($texto) . '</p>';
            echo '</div>';
            unset($_SESSION['mensaje']);
        }
        ?>

        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-key text-blue-500 mr-3"></i> Gestión de Roles
            </h2>

            <button id="openCreateRolModal"
                class="mb-6 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition flex items-center">
                <i class="fas fa-plus mr-2"></i> Crear Nuevo Rol
            </button>

            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200 rounded-lg shadow-sm">
                    <thead>
                        <tr class="bg-gray-100 border-b border-gray-200 text-gray-600 uppercase text-sm leading-normal">
                            <th class="py-3 px-6 text-left">ID</th>
                            <th class="py-3 px-6 text-left">Nombre del Rol</th>
                            <th class="py-3 px-6 text-left">Estado</th>
                            <th class="py-3 px-6 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 text-sm font-light">
                        <?php if (empty($roles)): ?>
                            <tr>
                                <td colspan="4" class="py-3 px-6 text-center">No hay roles registrados.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($roles as $rol): ?>
                                <tr class="border-b border-gray-200 hover:bg-gray-50">
                                    <td class="py-3 px-6 text-left whitespace-nowrap">
                                        <?php echo htmlspecialchars($rol['idrol']); ?></td>
                                    <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($rol['nombre']); ?></td>
                                    <td class="py-3 px-6 text-left">
                                        <span
                                            class="px-2 py-1 font-semibold leading-tight rounded-full <?php echo ($rol['estado'] == 'activo') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo htmlspecialchars(ucfirst($rol['estado'])); ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <div class="flex item-center justify-center">
                                            <button class="w-4 mr-2 transform hover:text-blue-500 hover:scale-110 edit-rol-btn"
                                                data-id="<?php echo htmlspecialchars($rol['idrol']); ?>"
                                                data-nombre="<?php echo htmlspecialchars($rol['nombre']); ?>"
                                                data-estado="<?php echo htmlspecialchars($rol['estado']); ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if (strtolower($rol['nombre']) !== 'administrador'): // No permitir eliminar el rol "Administrador" ?>
                                                <button class="w-4 mr-2 transform hover:text-red-500 hover:scale-110 delete-button"
                                                    data-type="rol" data-id="<?php echo htmlspecialchars($rol['idrol']); ?>"
                                                    data-name="<?php echo htmlspecialchars($rol['nombre']); ?>">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-user-lock text-purple-500 mr-3"></i> Gestión de Permisos
            </h2>

            <button id="openCreatePermisoModal"
                class="mb-6 px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition flex items-center">
                <i class="fas fa-plus mr-2"></i> Crear Nuevo Permiso
            </button>

            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200 rounded-lg shadow-sm">
                    <thead>
                        <tr class="bg-gray-100 border-b border-gray-200 text-gray-600 uppercase text-sm leading-normal">
                            <th class="py-3 px-6 text-left">ID</th>
                            <th class="py-3 px-6 text-left">Nombre del Permiso</th>
                            <th class="py-3 px-6 text-left">Descripción</th>
                            <th class="py-3 px-6 text-left">Estado</th>
                            <th class="py-3 px-6 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 text-sm font-light">
                        <?php if (empty($todos_los_permisos)): ?>
                            <tr>
                                <td colspan="5" class="py-3 px-6 text-center">No hay permisos registrados.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($todos_los_permisos as $permiso): ?>
                                <tr class="border-b border-gray-200 hover:bg-gray-50">
                                    <td class="py-3 px-6 text-left whitespace-nowrap">
                                        <?php echo htmlspecialchars($permiso['idpermiso']); ?></td>
                                    <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($permiso['nombre']); ?></td>
                                    <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($permiso['descripcion']); ?>
                                    </td>
                                    <td class="py-3 px-6 text-left">
                                        <span
                                            class="px-2 py-1 font-semibold leading-tight rounded-full <?php echo ($permiso['estado'] == 'activo') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo htmlspecialchars(ucfirst($permiso['estado'])); ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <div class="flex item-center justify-center">
                                            <button
                                                class="w-4 mr-2 transform hover:text-blue-500 hover:scale-110 edit-permiso-btn"
                                                data-id="<?php echo htmlspecialchars($permiso['idpermiso']); ?>"
                                                data-nombre="<?php echo htmlspecialchars($permiso['nombre']); ?>"
                                                data-descripcion="<?php echo htmlspecialchars($permiso['descripcion']); ?>"
                                                data-estado="<?php echo htmlspecialchars($permiso['estado']); ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="w-4 mr-2 transform hover:text-red-500 hover:scale-110 delete-button"
                                                data-type="permiso"
                                                data-id="<?php echo htmlspecialchars($permiso['idpermiso']); ?>"
                                                data-name="<?php echo htmlspecialchars($permiso['nombre']); ?>">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
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

    <div id="createRolModal" class="modal">
        <div class="modal-content">
            <span class="close-button" id="closeCreateRolModal">&times;</span>
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Crear Nuevo Rol</h2>
            <form action="op_crear_rol.php" method="POST" class="space-y-4">
                <div>
                    <label for="new_rol_nombre" class="block text-sm font-medium text-gray-700">Nombre del Rol:</label>
                    <input type="text" id="new_rol_nombre" name="nombre" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div>
                    <label for="new_rol_estado" class="block text-sm font-medium text-gray-700">Estado:</label>
                    <select id="new_rol_estado" name="estado" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>
                <div class="flex justify-end">
                    <button type="button" id="cancelCreateRolModal"
                        class="mr-3 px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Guardar Rol
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="editRolModal" class="modal">
        <div class="modal-content">
            <span class="close-button" id="closeEditRolModal">&times;</span>
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Editar Rol</h2>
            <form action="op_editar_rol.php" method="POST" class="space-y-4">
                <input type="hidden" id="edit_rol_id" name="idrol">
                <div>
                    <label for="edit_rol_nombre" class="block text-sm font-medium text-gray-700">Nombre del Rol:</label>
                    <input type="text" id="edit_rol_nombre" name="nombre" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div>
                    <label for="edit_rol_estado" class="block text-sm font-medium text-gray-700">Estado:</label>
                    <select id="edit_rol_estado" name="estado" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>

                <div class="mt-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Permisos del Rol:</h3>
                    <div id="permisos_checkboxes" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <?php foreach ($permisos_disponibles as $permiso): ?>
                            <div class="flex items-center">
                                <input type="checkbox"
                                    id="permiso_rol_<?php echo htmlspecialchars($permiso['idpermiso']); ?>"
                                    name="permisos[]" value="<?php echo htmlspecialchars($permiso['idpermiso']); ?>"
                                    class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <label for="permiso_rol_<?php echo htmlspecialchars($permiso['idpermiso']); ?>"
                                    class="ml-2 block text-sm text-gray-900">
                                    <?php echo htmlspecialchars($permiso['nombre']); ?>
                                    <span class="text-gray-500 text-xs italic">
                                        (<?php echo htmlspecialchars($permiso['descripcion']); ?>)</span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="flex justify-end mt-6">
                    <button type="button" id="cancelEditRolModal"
                        class="mr-3 px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Actualizar Rol
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="createPermisoModal" class="modal">
        <div class="modal-content">
            <span class="close-button" id="closeCreatePermisoModal">&times;</span>
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Crear Nuevo Permiso</h2>
            <form action="op_crear_permiso.php" method="POST" class="space-y-4">
                <div>
                    <label for="new_permiso_nombre" class="block text-sm font-medium text-gray-700">Nombre del
                        Permiso:</label>
                    <input type="text" id="new_permiso_nombre" name="nombre" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm">
                </div>
                <div>
                    <label for="new_permiso_descripcion"
                        class="block text-sm font-medium text-gray-700">Descripción:</label>
                    <textarea id="new_permiso_descripcion" name="descripcion" rows="3" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm"></textarea>
                </div>
                <div>
                    <label for="new_permiso_estado" class="block text-sm font-medium text-gray-700">Estado:</label>
                    <select id="new_permiso_estado" name="estado" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>
                <div class="flex justify-end">
                    <button type="button" id="cancelCreatePermisoModal"
                        class="mr-3 px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Guardar Permiso
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="editPermisoModal" class="modal">
        <div class="modal-content">
            <span class="close-button" id="closeEditPermisoModal">&times;</span>
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Editar Permiso</h2>
            <form action="op_editar_permiso.php" method="POST" class="space-y-4">
                <input type="hidden" id="edit_permiso_id" name="idpermiso">
                <div>
                    <label for="edit_permiso_nombre" class="block text-sm font-medium text-gray-700">Nombre del
                        Permiso:</label>
                    <input type="text" id="edit_permiso_nombre" name="nombre" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm">
                </div>
                <div>
                    <label for="edit_permiso_descripcion"
                        class="block text-sm font-medium text-gray-700">Descripción:</label>
                    <textarea id="edit_permiso_descripcion" name="descripcion" rows="3" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm"></textarea>
                </div>
                <div>
                    <label for="edit_permiso_estado" class="block text-sm font-medium text-gray-700">Estado:</label>
                    <select id="edit_permiso_estado" name="estado" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>
                <div class="flex justify-end">
                    <button type="button" id="cancelEditPermisoModal"
                        class="mr-3 px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Actualizar Permiso
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <span class="close-button" id="closeConfirmModal">&times;</span>
            <h2 class="text-xl font-bold text-gray-800 mb-4">Confirmar Eliminación</h2>
            <p id="confirmMessage" class="text-gray-700 mb-6"></p>
            <div class="flex justify-end space-x-4">
                <button id="cancelConfirmBtn"
                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cancelar
                </button>
                <button id="proceedToCredentialsBtn"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Continuar y Confirmar Credenciales
                </button>
            </div>
        </div>
    </div>

    <div id="credentialsModal" class="modal">
        <div class="modal-content">
            <span class="close-button" id="closeCredentialsModal">&times;</span>
            <h2 class="text-xl font-bold text-gray-800 mb-4">Confirmar Acceso</h2>
            <p class="text-gray-700 mb-4">Por favor, ingresa El Correo y contraseña de administrador para confirmar esta
                acción.</p>
            <div class="space-y-4">
                <div>
                    <label for="admin_email" class="block text-sm font-medium text-gray-700 text-left">Correo
                        Electrónico:</label>
                    <input type="email" id="admin_email" name="admin_email" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div>
                    <label for="admin_password"
                        class="block text-sm font-medium text-gray-700 text-left">Contraseña:</label>
                    <input type="password" id="admin_password" name="admin_password" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div id="credentialsError" class="text-red-600 text-sm mt-2 text-center" style="display:none;"></div>
            </div>
            <div class="flex justify-end space-x-4 mt-6">
                <button id="cancelCredentialsBtn"
                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cancelar
                </button>
                <button id="confirmAdminCredentialsBtn"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Confirmar
                </button>
            </div>
        </div>
    </div>


    <script>
        // --- Modales para Roles ---
        const createRolModal = document.getElementById("createRolModal");
        const openCreateRolBtn = document.getElementById("openCreateRolModal");
        const closeCreateRolBtn = document.getElementById("closeCreateRolModal");
        const cancelCreateRolBtn = document.getElementById("cancelCreateRolModal");

        const editRolModal = document.getElementById("editRolModal");
        const closeEditRolBtn = document.getElementById("closeEditRolModal");
        const cancelEditRolBtn = document.getElementById("cancelEditRolModal");
        const editRolButtons = document.querySelectorAll(".edit-rol-btn");

        // --- Modales para Permisos ---
        const createPermisoModal = document.getElementById("createPermisoModal");
        const openCreatePermisoBtn = document.getElementById("openCreatePermisoModal");
        const closeCreatePermisoBtn = document.getElementById("closeCreatePermisoModal");
        const cancelCreatePermisoBtn = document.getElementById("cancelCreatePermisoModal");

        const editPermisoModal = document.getElementById("editPermisoModal");
        const closeEditPermisoBtn = document.getElementById("closeEditPermisoModal");
        const cancelEditPermisoBtn = document.getElementById("cancelEditPermisoModal");
        const editPermisoButtons = document.querySelectorAll(".edit-permiso-btn");

        // --- Modal de Confirmación Genérico (Primera Etapa) ---
        const confirmModal = document.getElementById("confirmModal");
        const closeConfirmModalBtn = document.getElementById("closeConfirmModal");
        const cancelConfirmBtn = document.getElementById("cancelConfirmBtn");
        const proceedToCredentialsBtn = document.getElementById("proceedToCredentialsBtn"); // Nuevo botón
        const confirmMessage = document.getElementById("confirmMessage");

        // --- Modal de Credenciales (Segunda Etapa) ---
        const credentialsModal = document.getElementById("credentialsModal");
        const closeCredentialsModalBtn = document.getElementById("closeCredentialsModal");
        const cancelCredentialsBtn = document.getElementById("cancelCredentialsBtn");
        const confirmAdminCredentialsBtn = document.getElementById("confirmAdminCredentialsBtn");
        const adminEmailInput = document.getElementById("admin_email"); // CAMBIADO a admin_email
        const adminPasswordInput = document.getElementById("admin_password");
        const credentialsErrorDiv = document.getElementById("credentialsError");


        let currentDeleteItem = { type: null, id: null, name: null }; // Almacena el elemento a eliminar


        // --- Funciones para abrir y cerrar modales (Roles) ---
        openCreateRolBtn.onclick = function () {
            createRolModal.style.display = "flex";
        }
        closeCreateRolBtn.onclick = function () { createRolModal.style.display = "none"; }
        cancelCreateRolBtn.onclick = function () { createRolModal.style.display = "none"; }
        closeEditRolBtn.onclick = function () { editRolModal.style.display = "none"; }
        cancelEditRolBtn.onclick = function () { editRolModal.style.display = "none"; }

        // --- Funciones para abrir y cerrar modales (Permisos) ---
        openCreatePermisoBtn.onclick = function () {
            createPermisoModal.style.display = "flex";
        }
        closeCreatePermisoBtn.onclick = function () { createPermisoModal.style.display = "none"; }
        cancelCreatePermisoBtn.onclick = function () { createPermisoModal.style.display = "none"; }
        closeEditPermisoBtn.onclick = function () { editPermisoModal.style.display = "none"; }
        cancelEditPermisoBtn.onclick = function () { editPermisoModal.style.display = "none"; }

        // --- Funciones para abrir y cerrar modal de confirmación ---
        closeConfirmModalBtn.onclick = function () { confirmModal.style.display = "none"; }
        cancelConfirmBtn.onclick = function () { confirmModal.style.display = "none"; }

        // --- Funciones para abrir y cerrar modal de credenciales ---
        closeCredentialsModalBtn.onclick = function () { credentialsModal.style.display = "none"; credentialsErrorDiv.style.display = "none"; adminEmailInput.value = ''; adminPasswordInput.value = ''; }
        cancelCredentialsBtn.onclick = function () { credentialsModal.style.display = "none"; credentialsErrorDiv.style.display = "none"; adminEmailInput.value = ''; adminPasswordInput.value = ''; }


        // Cerrar modales si se hace clic fuera
        window.onclick = function (event) {
            if (event.target == createRolModal) {
                createRolModal.style.display = "none";
            }
            if (event.target == editRolModal) {
                editRolModal.style.display = "none";
            }
            if (event.target == createPermisoModal) {
                createPermisoModal.style.display = "none";
            }
            if (event.target == editPermisoModal) {
                editPermisoModal.style.display = "none";
            }
            if (event.target == confirmModal) {
                confirmModal.style.display = "none";
            }
            if (event.target == credentialsModal) {
                credentialsModal.style.display = "none";
                credentialsErrorDiv.style.display = "none"; // Ocultar errores al cerrar
                adminEmailInput.value = ''; // Limpiar campos
                adminPasswordInput.value = '';
            }
        }

        // --- Lógica de Edición de Rol ---
        editRolButtons.forEach(button => {
            button.addEventListener('click', function () {
                const idrol = this.dataset.id;
                const nombre = this.dataset.nombre;
                const estado = this.dataset.estado;

                document.getElementById('edit_rol_id').value = idrol;
                document.getElementById('edit_rol_nombre').value = nombre;
                document.getElementById('edit_rol_estado').value = estado;

                // Cargar permisos del rol dinámicamente
                fetch('op_get_permisos_rol.php?idrol=' + idrol)
                    .then(response => response.json())
                    .then(data => {
                        // Desmarcar todos los checkboxes primero
                        document.querySelectorAll('#permisos_checkboxes input[type="checkbox"]').forEach(checkbox => {
                            checkbox.checked = false;
                        });

                        // Marcar los checkboxes que corresponden a los permisos del rol
                        data.forEach(permisoId => {
                            const checkbox = document.getElementById('permiso_rol_' + permisoId); // Usar el ID correcto
                            if (checkbox) {
                                checkbox.checked = true;
                            }
                        });
                        editRolModal.style.display = "flex";
                    })
                    .catch(error => console.error('Error al obtener permisos:', error));
            });
        });

        // --- Lógica de Edición de Permiso ---
        editPermisoButtons.forEach(button => {
            button.addEventListener('click', function () {
                const idpermiso = this.dataset.id;
                const nombre = this.dataset.nombre;
                const descripcion = this.dataset.descripcion;
                const estado = this.dataset.estado;

                document.getElementById('edit_permiso_id').value = idpermiso;
                document.getElementById('edit_permiso_nombre').value = nombre;
                document.getElementById('edit_permiso_descripcion').value = descripcion;
                document.getElementById('edit_permiso_estado').value = estado;
                editPermisoModal.style.display = "flex";
            });
        });

        // --- Lógica para el botón genérico de eliminación (Primera Confirmación) ---
        document.querySelectorAll('.delete-button').forEach(button => {
            button.addEventListener('click', function () {
                const type = this.dataset.type; // 'rol' o 'permiso'
                const id = this.dataset.id;
                const name = this.dataset.name;

                currentDeleteItem = { type, id, name }; // Almacenar para usar en la confirmación de credenciales

                let message = '';
                if (type === 'rol') {
                    message = `¿Estás seguro de que deseas eliminar el rol "${name}"? Esto también desasociará a los usuarios y permisos de este rol.`;
                } else if (type === 'permiso') {
                    message = `¿Estás seguro de que deseas eliminar el permiso "${name}"? Esto lo desasociará de todos los roles.`;
                }

                confirmMessage.textContent = message;
                confirmModal.style.display = "flex";
            });
        });

        // --- Lógica al proceder a la confirmación de credenciales (desde el primer modal) ---
        proceedToCredentialsBtn.addEventListener('click', function () {
            confirmModal.style.display = "none"; // Ocultar el primer modal de confirmación
            credentialsErrorDiv.style.display = "none"; // Ocultar cualquier error previo
            adminEmailInput.value = ''; // Limpiar campos por si acaso
            adminPasswordInput.value = '';
            credentialsModal.style.display = "flex"; // Mostrar el modal de credenciales
            adminEmailInput.focus(); // Poner foco en el campo de email
        });


        // --- Lógica al confirmar las credenciales (desde el segundo modal) ---
        confirmAdminCredentialsBtn.addEventListener('click', function () {
            const email = adminEmailInput.value; // CAMBIADO a email
            const password = adminPasswordInput.value;

            if (email === '' || password === '') {
                credentialsErrorDiv.textContent = 'Por favor, ingresa correo electrónico y contraseña.';
                credentialsErrorDiv.style.display = 'block';
                return;
            }

            // Realizar una llamada AJAX para verificar las credenciales
            fetch('op_verificar_credenciales.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}` // CAMBIADO a email
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        credentialsModal.style.display = "none"; // Cerrar el modal de credenciales
                        credentialsErrorDiv.style.display = "none"; // Ocultar errores

                        // Si las credenciales son correctas y tiene permisos, proceder con la eliminación
                        executeDeletion();
                    } else {
                        credentialsErrorDiv.textContent = data.message || 'Credenciales incorrectas o no tienes permisos.';
                        credentialsErrorDiv.style.display = 'block';
                        adminPasswordInput.value = ''; // Limpiar solo la contraseña por seguridad
                    }
                })
                .catch(error => {
                    console.error('Error al verificar credenciales:', error);
                    credentialsErrorDiv.textContent = 'Error de comunicación con el servidor.';
                    credentialsErrorDiv.style.display = 'block';
                });
        });

        // Función para ejecutar la eliminación después de las confirmaciones
        function executeDeletion() {
            const { type, id } = currentDeleteItem;
            let url = '';
            let body = '';

            if (type === 'rol') {
                url = 'op_eliminar_rol.php';
                body = 'idrol=' + id;
            } else if (type === 'permiso') {
                url = 'op_eliminar_permiso.php';
                body = 'idpermiso=' + id;
            }

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: body
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload(); // Recargar la página para ver los cambios
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error en la eliminación:', error);
                    alert('Ocurrió un error al intentar eliminar. Por favor, revisa la consola para más detalles.');
                });
        }

    </script>
</body>

</html>