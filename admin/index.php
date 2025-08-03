<?php
// ... (tu código PHP existente)
define('PROTECT_CONFIG', true);

session_start();

// Redirige al login si no hay sesión activa
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['usuario_data'])) {
    $_SESSION['error'] = 'Por favor, inicia sesión para acceder.';
    header("Location: ../"); // Ajusta esta ruta si tu página de login no está en la raíz superior
    exit();
}

// Obtener toda la información del usuario desde la sesión
$usuario = $_SESSION['usuario_data'];
$esAdmin = false; // Inicializar por defecto

require_once '../assets/config/info.php';
// Verificar si el usuario tiene el permiso 'acceso_admin'.
// 'usuario_permisos' debería estar disponible en $_SESSION gracias a op_validar.php.
if (isset($_SESSION['usuario_permisos']) && in_array($admin, $_SESSION['usuario_permisos'])) {
    $esAdmin = true; // El usuario tiene el permiso de administrador
} else {
    // Si no tiene el permiso 'acceso_admin', redirigir al login.
    $_SESSION['error'] = 'Acceso denegado. No cuentas con el permiso ' . $admin . '. Por favor Contacta con tu departamento de IT o administrador de ayudas.';
    header("Location: ../"); // Redirige al login o a una página de acceso denegado específica.
    exit();
}

// Incluir el archivo de conexión a la base de datos
// Asegúrate de que esta ruta sea correcta.
require_once '../assets/config/db.php';

$categorias = [];
$bodegas = [];

try {
    // Las variables de conexión ($pdo) se definen en db.php.
    // Asegúrate de que tu db.php establezca una variable $pdo de tipo PDO.
    // Por ejemplo: $pdo = new PDO(...);

    // Obtener categorías
    $stmt_categorias = $pdo->query("SELECT idcategoria, nombre FROM inventario360_categoria ORDER BY nombre");
    $categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

    // Obtener bodegas
    $stmt_bodegas = $pdo->query("SELECT idbodega, nombre FROM inventario360_bodega ORDER BY nombre");
    $bodegas = $stmt_bodegas->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // En desarrollo, puedes mostrar el error directamente para depurar
    echo "Error al cargar datos de la base de datos: " . htmlspecialchars($e->getMessage());
    // En producción, considera redirigir o mostrar un mensaje de error amigable al usuario
    // Por ejemplo:
    // $_SESSION['error'] = 'Error al cargar datos del sistema. Por favor, inténtelo más tarde.';
    // header("Location: /");
    // exit();
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard -<?php echo $name_corp; ?></title>
    <link rel="shortcut icon" href="<?php echo $logo; ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Estilos adicionales para los modales */
        .modal {
            display: none;
            /* Hidden by default */
            position: fixed;
            /* Stay in place */
            z-index: 1000;
            /* Sit on top */
            left: 0;
            top: 0;
            width: 100%;
            /* Full width */
            height: 100%;
            /* Full height */
            overflow: auto;
            /* Enable scroll if needed */
            background-color: rgba(0, 0, 0, 0.4);
            /* Black w/ opacity */
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 600px;
            /* Ancho máximo para los modales */
            position: relative;
            animation-name: animatetop;
            animation-duration: 0.4s;
        }

        @keyframes animatetop {
            from {
                top: -300px;
                opacity: 0
            }

            to {
                top: 0;
                opacity: 1
            }
        }

        .close-button {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            right: 15px;
            top: 10px;
        }

        .close-button:hover,
        .close-button:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 2s linear infinite;
            display: none;
            margin: 20px auto;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .grid-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .grid-form>div {
            display: flex;
            flex-direction: column;
        }

        .grid-form label {
            font-weight: 500;
            color: #4a5568;
            margin-bottom: 4px;
            font-size: 0.875rem;
        }

        .form-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #cbd5e0;
            border-radius: 0.375rem;
            background-color: #f7fafc;
            font-size: 0.875rem;
            color: #2d3748;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .form-input[readonly] {
            background-color: #e2e8f0;
            cursor: not-allowed;
        }

        .history-list {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            padding: 10px;
            background-color: #fff;
        }

        .history-item {
            background-color: #f7fafc;
            border: 1px solid #ebf4ff;
            border-radius: 0.375rem;
            padding: 10px;
            margin-bottom: 10px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            font-size: 0.875rem;
            color: #4a5568;
            position: relative;
        }

        .history-item label {
            font-weight: 600;
            color: #2d3748;
        }

        .history-item span {
            display: block;
            word-wrap: break-word;
        }

        .history-item .full-width-field {
            grid-column: span 2;
            /* Hace que este div ocupe todo el ancho en un diseño de dos columnas */
        }

        .history-item textarea {
            width: 100%;
            min-height: 50px;
            resize: vertical;
            font-size: 0.875rem;
            padding: 5px;
            border: 1px solid #cbd5e0;
            border-radius: 0.25rem;
            background-color: #edf2f7;
        }

        /* Estilos para el botón de eliminar producto de la lista */
        .remove-product-btn {
            background-color: #ef4444;
            /* red-500 */
            color: white;
            padding: 0.25rem 0.5rem;
            /* px-2 py-1 */
            border-radius: 0.25rem;
            /* rounded */
            font-size: 0.75rem;
            /* text-xs */
            cursor: pointer;
            border: none;
            transition: background-color 0.2s;
        }

        .remove-product-btn:hover {
            background-color: #dc2626;
            /* red-600 */
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">
    <header class="bg-white shadow-sm sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row justify-between items-center">
            <div class="flex items-center mb-4 sm:mb-0">
                <i class="fas fa-boxes text-blue-600 text-2xl mr-3"></i>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900"><?php echo $name_corp; ?></h1>
            </div>

            <div class="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-4">
                <div class="flex items-center text-sm sm:text-base">
                    <i class="fas fa-user-circle text-gray-500 mr-2"></i>
                    <span class="text-gray-700"><?php echo htmlspecialchars($usuario['nombre']); ?></span>
                    <span class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                        <?php echo htmlspecialchars($usuario['rol_nombre']); ?>
                    </span>
                </div>
                <a href="../op_logout.php"
                    class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition flex items-center text-sm sm:text-base">
                    <i class="fas fa-sign-out-alt mr-1"></i> Cerrar sesión
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
        <?php
        // Mostrar mensajes de sesión (éxito/error)
        // ESTA SECCIÓN AHORA SÓLO MOSTRARÁ MENSAJES QUE NO SE ORIGINEN EN LOS MODALES DE CREACIÓN/MODIFICACIÓN.
        // Los mensajes de los modales se mostrarán directamente en el modal.
        if (isset($_SESSION['mensaje'])) {
            $tipo = $_SESSION['mensaje']['tipo'];
            $texto = $_SESSION['mensaje']['texto'];
            $clase_alerta = '';
            if ($tipo == 'exito') {
                $clase_alerta = 'bg-green-100 border-green-400 text-green-700';
            } elseif ($tipo == 'error') {
                $clase_alerta = 'bg-red-100 border-red-400 text-red-700';
            }
            echo '<div class="mb-4 p-4 border rounded-md ' . $clase_alerta . '" role="alert">';
            echo '<p>' . htmlspecialchars($texto) . '</p>';
            echo '</div>';
            unset($_SESSION['mensaje']); // Limpiar el mensaje de la sesión
        }
        ?>

        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow-md p-6 mb-8 text-white">
            <div class="flex flex-col md:flex-row items-center justify-between">
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold mb-2">Bienvenido,
                        <?php echo htmlspecialchars($usuario['nombre']); ?>!
                    </h2>
                    <p class="opacity-90">Sistema de gestión de inventario completo</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <span class="inline-block bg-white bg-opacity-20 px-4 py-2 rounded-full">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        <?php echo date('d/m/Y'); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="mb-10">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-bolt text-yellow-500 mr-2"></i> Acciones rápidas
            </h2>

            <!-- Contenedor principal desplazable -->
            <div class="relative">
                <div class="overflow-x-auto pb-4"> <!-- pb-4 para espacio de scroll -->
                    <div class="flex space-x-4 w-max"> <!-- w-max para que ocupe el espacio necesario -->
                        <!-- Consultar Producto -->
                        <a href="#" id="openConsultProductModal"
                            class="block bg-white rounded-lg shadow-md overflow-hidden transition duration-300 card-hover text-left"
                            style="min-width: 220px;">
                            <div class="p-5 flex items-start">
                                <div class="bg-blue-100 p-3 rounded-lg mr-4">
                                    <i class="fas fa-search text-blue-600 text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-800">Consultar Producto</h3>
                                    <p class="text-sm text-gray-500 mt-1">Buscar información de productos</p>
                                </div>
                            </div>
                        </a>

                        <!-- Ingresar Producto -->
                        <a href="#" id="openProductModal"
                            class="block bg-white rounded-lg shadow-md overflow-hidden transition duration-300 card-hover text-left"
                            style="min-width: 220px;">
                            <div class="p-5 flex items-start">
                                <div class="bg-green-100 p-3 rounded-lg mr-4">
                                    <i class="fas fa-plus-circle text-green-600 text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-800">Ingresar Producto</h3>
                                    <p class="text-sm text-gray-500 mt-1">Agregar nuevos productos</p>
                                </div>
                            </div>
                        </a>

                        <!-- Modificar Producto -->
                        <a href="#" id="openModifyProductModal"
                            class="block bg-white rounded-lg shadow-md overflow-hidden transition duration-300 card-hover text-left"
                            style="min-width: 220px;">
                            <div class="p-5 flex items-start">
                                <div class="bg-yellow-100 p-3 rounded-lg mr-4">
                                    <i class="fas fa-edit text-yellow-600 text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-800">Modificar Producto</h3>
                                    <p class="text-sm text-gray-500 mt-1">Actualizar información</p>
                                </div>
                            </div>
                        </a>

                        <!-- Movimientos -->
                        <a href="movimientos/"
                            class="block bg-white rounded-lg shadow-md overflow-hidden transition duration-300 card-hover text-left"
                            style="min-width: 220px;">
                            <div class="p-5 flex items-start">
                                <div class="bg-purple-100 p-3 rounded-lg mr-4">
                                    <i class="fas fa-exchange-alt text-purple-600 text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-800">Movimientos</h3>
                                    <p class="text-sm text-gray-500 mt-1">Registro de transacciones</p>
                                </div>
                            </div>
                        </a>

                        <!-- Estadísticas -->
                        <a href="estadisticas/"
                            class="block bg-white rounded-lg shadow-md overflow-hidden transition duration-300 card-hover text-left"
                            style="min-width: 220px;">
                            <div class="p-5 flex items-start">
                                <div class="bg-red-100 p-3 rounded-lg mr-4">
                                    <i class="fas fa-chart-bar text-red-600 text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-800">Estadísticas</h3>
                                    <p class="text-sm text-gray-500 mt-1">Métricas y gráficos</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-10">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-boxes text-blue-500 mr-2"></i> Gestión de Productos
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <a href="bodega/"
                    class="bg-white rounded-lg shadow-md overflow-hidden transition duration-300 card-hover">
                    <div class="p-6 flex flex-col items-center text-center">
                        <div class="bg-orange-100 w-16 h-16 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-warehouse text-orange-600 text-2xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-800">Gestión de Bodegas</h3>
                        <p class="text-sm text-gray-500 mt-2">Administra tus espacios de almacenamiento</p>
                    </div>
                </a>

                <a href="categoria/"
                    class="bg-white rounded-lg shadow-md overflow-hidden transition duration-300 card-hover">
                    <div class="p-6 flex flex-col items-center text-center">
                        <div class="bg-indigo-100 w-16 h-16 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-tags text-indigo-600 text-2xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-800">Categorías</h3>
                        <p class="text-sm text-gray-500 mt-2">Organiza tus productos</p>
                    </div>
                </a>

                <a href="proveedores/"
                    class="bg-white rounded-lg shadow-md overflow-hidden transition duration-300 card-hover">
                    <div class="p-6 flex flex-col items-center text-center">
                        <div class="bg-teal-100 w-16 h-16 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-truck text-teal-600 text-2xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-800">Proveedores</h3>
                        <p class="text-sm text-gray-500 mt-2">Administra tus suministradores</p>
                    </div>
                </a>
            </div>
        </div>

        <?php if ($esAdmin): // Solo muestra esta sección si $esAdmin es true ?>
            <div class="pt-8 border-t section-divider">
                <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                    <i class="fas fa-user-shield text-red-500 mr-2"></i> Panel de Administración
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <a href="users/"
                        class="bg-white rounded-lg shadow-md overflow-hidden transition duration-300 card-hover">
                        <div class="p-6 flex flex-col items-center text-center">
                            <div class="bg-gray-100 w-16 h-16 rounded-full flex items-center justify-center mb-4">
                                <i class="fas fa-users-cog text-gray-600 text-2xl"></i>
                            </div>
                            <h3 class="font-semibold text-gray-800">Gestión de Usuarios</h3>
                            <p class="text-sm text-gray-500 mt-2">Administra los usuarios del sistema</p>
                        </div>
                    </a>

                    <a href="rol_permisos/"
                        class="bg-white rounded-lg shadow-md overflow-hidden transition duration-300 card-hover">
                        <div class="p-6 flex flex-col items-center text-center">
                            <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mb-4">
                                <i class="fas fa-key text-blue-600 text-2xl"></i>
                            </div>
                            <h3 class="font-semibold text-gray-800">Roles y Permisos</h3>
                            <p class="text-sm text-gray-500 mt-2">Configura los niveles de acceso</p>
                        </div>
                    </a>

                    <a href="registro/"
                        class="bg-white rounded-lg shadow-md overflow-hidden transition duration-300 card-hover">
                        <div class="p-6 flex flex-col items-center text-center">
                            <div class="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mb-4">
                                <i class="fas fa-history text-purple-600 text-2xl"></i>
                            </div>
                            <h3 class="font-semibold text-gray-800">Registro de Actividad</h3>
                            <p class="text-sm text-gray-500 mt-2">Revisa las acciones del sistema</p>
                        </div>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <footer class="bg-white border-t mt-10 py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-gray-500 text-sm">
            <p><?php echo $name_corp; ?> v2.0 &copy; <?php echo date('Y'); ?> - Todos los derechos reservados</p>
            <p class="mt-1">Sistema desarrollado para gestión integral de inventarios</p>
        </div>
    </footer>

    <div id="productModal" class="modal">
        <div class="modal-content">
            <span class="close-button" id="closeProductModal">&times;</span>
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Ingresar Nuevo Producto</h2>
            <div id="createProductMessageContainer" class="mb-4 hidden"></div>

            <form id="addProductToListForm" class="space-y-4 mb-6 p-4 border border-gray-200 rounded-md bg-gray-50">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Detalles del Producto a Añadir</h3>
                <div>
                    <label for="numero_producto" class="block text-sm font-medium text-gray-700">Número de
                        Producto:</label>
                    <input type="text" id="numero_producto" name="numero_producto" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div>
                    <label for="nombre_producto" class="block text-sm font-medium text-gray-700">Nombre del
                        Producto:</label>
                    <input type="text" id="nombre_producto" name="nombre" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div>
                    <label for="estado" class="block text-sm font-medium text-gray-700">Estado:</label>
                    <select id="estado" name="estado" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                        <option value="averiado">Averiado</option>
                    </select>
                </div>
                <div>
                    <label for="precio" class="block text-sm font-medium text-gray-700">Precio:</label>
                    <input type="number" id="precio" name="precio" step="0.01" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div>
                    <label for="categoria" class="block text-sm font-medium text-gray-700">Categoría:</label>
                    <select id="categoria" name="idcategoria" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Selecciona una categoría</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['idcategoria']); ?>">
                                <?php echo htmlspecialchars($cat['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="bodega" class="block text-sm font-medium text-gray-700">Bodega:</label>
                    <select id="bodega" name="idbodega" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Selecciona una bodega</option>
                        <?php foreach ($bodegas as $bod): ?>
                            <option value="<?php echo htmlspecialchars($bod['idbodega']); ?>">
                                <?php echo htmlspecialchars($bod['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex justify-end">
                    <button type="submit" id="addProductButton"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-plus mr-1"></i> Añadir a la lista
                    </button>
                </div>
            </form>

            <div class="mb-6 p-4 border border-gray-200 rounded-md bg-gray-50">
                <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                    <i class="fas fa-list-alt mr-2 text-green-600"></i> Productos a Ingresar
                    (<span id="productCount">0</span>)
                </h3>
                <div id="productsToEnterList" class="space-y-3 max-h-60 overflow-y-auto">
                    <p id="noProductsAdded" class="text-gray-500 text-sm text-center">No hay productos añadidos aún.</p>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="button" id="cancelProductModal"
                    class="mr-3 px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cancelar
                </button>
                <button type="button" id="confirmBulkProductEntry"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    disabled>
                    <i class="fas fa-save mr-1"></i> Confirmar Ingreso (<span id="confirmProductCount">0</span>)
                </button>
            </div>
        </div>
    </div>


    <div id="consultProductModal" class="modal">
        <div class="modal-content">
            <span class="close-button" id="closeConsultProductModal">&times;</span>
            <h2 class="text-xl font-bold text-gray-800 mb-4 text-center">Consultar Producto</h2>
            <div class="flex items-center mb-4 px-3 py-2 bg-blue-100 rounded-md border border-blue-200"> <i
                    class="fas fa-search text-blue-600 text-lg mr-2"></i> <input type="text"
                    id="consult_numero_producto" placeholder="Ingresa el Número de Producto"
                    class="flex-grow px-2 py-1 border border-blue-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm bg-white">
                <button type="button" id="searchProductButton"
                    class="ml-3 px-3 py-1 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Buscar
                </button>
            </div>

            <div id="productInfo" class="mt-4 p-3 border border-gray-200 rounded-md bg-gray-50">
                <div class="loader" id="productLoader"></div>
                <p id="productMessage" class="text-gray-600 text-center text-sm"></p>
                <div id="productDetails" class="hidden">
                    <div class="grid-form mb-4">
                        <div>
                            <label for="info_numero_producto_display">Número Producto:</label>
                            <input type="text" id="info_numero_producto_display" class="form-input" readonly>
                        </div>
                        <div>
                            <label for="info_nombre_display">Producto:</label>
                            <input type="text" id="info_nombre_display" class="form-input" readonly>
                        </div>
                        <div>
                            <label for="info_origen_display">Origen:</label>
                            <input type="text" id="info_origen_display" class="form-input" value="N/A" readonly>
                        </div>
                        <div>
                            <label for="info_destino_display">Destino:</label>
                            <input type="text" id="info_destino_display" class="form-input" value="N/A" readonly>
                        </div>
                        <div>
                            <label for="info_proveedor_display">Proveedor:</label>
                            <input type="text" id="info_proveedor_display" class="form-input" value="N/A" readonly>
                        </div>
                        <div>
                            <label for="info_categoria_display">Categoría:</label>
                            <input type="text" id="info_categoria_display" class="form-input" readonly>
                        </div>
                        <div>
                            <label for="info_valor_display">Valor:</label>
                            <input type="text" id="info_valor_display" class="form-input" readonly>
                        </div>
                        <div>
                            <label for="info_descripcion_display">Descripción:</label>
                            <input type="text" id="info_descripcion_display" class="form-input" value="N/A" readonly>
                        </div>
                        <div>
                            <label for="info_usuario_display">Usuario:</label>
                            <input type="text" id="info_usuario_display" class="form-input" readonly>
                        </div>
                        <div>
                            <label for="info_fecha_registro_display">Fecha Creación:</label>
                            <input type="text" id="info_fecha_registro_display" class="form-input" readonly>
                        </div>
                    </div>

                    <div class="text-center mt-3 mb-3"> <a href="#" id="verMasButton"
                            class="text-blue-600 hover:underline text-xs flex items-center justify-center"> Ver más <i
                                class="fas fa-chevron-down ml-1"></i>
                        </a>
                    </div>

                    <h3 class="text-lg font-bold text-gray-700 mb-3 mt-4">Historial de Movimientos</h3>
                    <div class="history-list" id="movementsTimeline">
                    </div>
                    <p id="noMovementsMessage" class="text-gray-600 text-center hidden text-sm">No se encontraron
                        movimientos para este producto.</p>
                </div>
            </div>
        </div>
    </div>

    <div id="modifyProductModal" class="modal">
        <div class="modal-content">
            <span class="close-button" id="closeModifyProductModal">&times;</span>
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Modificar Producto Existente</h2>
            <div class="flex items-center mb-4 px-3 py-2 bg-yellow-100 rounded-md border border-yellow-200">
                <i class="fas fa-search text-yellow-600 text-lg mr-2"></i>
                <input type="text" id="modify_numero_producto_search"
                    placeholder="Ingresa el Número de Producto a Modificar"
                    class="flex-grow px-2 py-1 border border-yellow-300 rounded-md shadow-sm focus:outline-none focus:ring-yellow-500 focus:border-yellow-500 text-sm bg-white">
                <button type="button" id="searchModifyProductButton"
                    class="ml-3 px-3 py-1 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                    Buscar
                </button>
            </div>

            <div id="modifyProductFormContainer" class="mt-4 p-3 border border-gray-200 rounded-md bg-gray-50">
                <div class="loader" id="modifyProductLoader"></div>
                <div id="modifyProductMessageContainer" class="mb-4 hidden"></div>
                <p id="modifyProductMessage" class="text-gray-600 text-center text-sm"></p>

                <form action="producto/op_modificar_producto.php" method="POST" class="space-y-4 hidden"
                    id="actualModifyProductForm">
                    <input type="hidden" id="modify_idproducto" name="idproducto">
                    <div>
                        <label for="modify_numero_producto" class="block text-sm font-medium text-gray-700">Número de
                            Producto:</label>
                        <input type="text" id="modify_numero_producto" name="numero_producto" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="modify_nombre_producto" class="block text-sm font-medium text-gray-700">Nombre del
                            Producto:</label>
                        <input type="text" id="modify_nombre_producto" name="nombre" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="modify_estado" class="block text-sm font-medium text-gray-700">Estado:</label>
                        <select id="modify_estado" name="estado" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                            <option value="averiado">Averiado</option>
                        </select>
                    </div>
                    <div>
                        <label for="modify_precio" class="block text-sm font-medium text-gray-700">Precio:</label>
                        <input type="number" id="modify_precio" name="precio" step="0.01" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="modify_categoria" class="block text-sm font-medium text-gray-700">Categoría:</label>
                        <select id="modify_categoria" name="idcategoria" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">Selecciona una categoría</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['idcategoria']); ?>">
                                    <?php echo htmlspecialchars($cat['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="modify_bodega" class="block text-sm font-medium text-gray-700">Bodega:</label>
                        <select id="modify_bodega" name="idbodega" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">Selecciona una bodega</option>
                            <?php foreach ($bodegas as $bod): ?>
                                <option value="<?php echo htmlspecialchars($bod['idbodega']); ?>">
                                    <?php echo htmlspecialchars($bod['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex justify-end">
                        <button type="button" id="cancelModifyProductButton"
                            class="mr-3 px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                            Actualizar Producto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script>
        // Función auxiliar para mostrar mensajes
        function showMessage(element, message, type) {
            element.textContent = message;
            element.classList.remove('hidden', 'bg-green-100', 'border-green-400', 'text-green-700', 'bg-red-100', 'border-red-400', 'text-red-700');
            if (type === 'success') {
                element.classList.add('bg-green-100', 'border-green-400', 'text-green-700', 'p-3', 'rounded-md', 'border');
            } else if (type === 'error') {
                element.classList.add('bg-red-100', 'border-red-400', 'text-red-700', 'p-3', 'rounded-md', 'border');
            }
            element.style.display = 'block'; // Asegurarse de que sea visible
        }

        // Función auxiliar para ocultar mensajes
        function hideMessage(element) {
            element.classList.add('hidden');
            element.textContent = '';
            element.classList.remove('bg-green-100', 'border-green-400', 'text-green-700', 'bg-red-100', 'border-red-400', 'text-red-700', 'p-3', 'rounded-md', 'border');
        }

        // Modal para Ingresar Producto (MODIFICADO para lista de productos)
        var productModal = document.getElementById("productModal");
        var openProductBtn = document.getElementById("openProductModal");
        var closeProductSpan = document.getElementById("closeProductModal");
        var cancelProductBtn = document.getElementById("cancelProductModal");
        var addProductToListForm = document.getElementById("addProductToListForm");
        var productsToEnterListDiv = document.getElementById("productsToEnterList");
        var productCountSpan = document.getElementById("productCount");
        var confirmBulkProductEntryBtn = document.getElementById("confirmBulkProductEntry");
        var confirmProductCountSpan = document.getElementById("confirmProductCount");
        var noProductsAddedMessage = document.getElementById("noProductsAdded");
        var createProductMessageContainer = document.getElementById("createProductMessageContainer");

        let productsToEnter = []; // Array para almacenar temporalmente los productos

        // Función para renderizar la lista de productos a ingresar
        function renderProductsToEnterList() {
            productsToEnterListDiv.innerHTML = '';
            if (productsToEnter.length === 0) {
                noProductsAddedMessage.classList.remove('hidden');
                confirmBulkProductEntryBtn.disabled = true;
            } else {
                noProductsAddedMessage.classList.add('hidden');
                confirmBulkProductEntryBtn.disabled = false;
                productsToEnter.forEach((product, index) => {
                    const productItem = document.createElement('div');
                    productItem.classList.add('p-3', 'bg-white', 'rounded-md', 'shadow-sm', 'border', 'border-gray-100', 'flex', 'justify-between', 'items-center', 'flex-wrap');
                    productItem.innerHTML = `
                        <div class="flex-1 min-w-0 pr-4">
                            <p class="font-semibold text-gray-800 text-sm">${htmlspecialchars(product.nombre)} <span class="text-gray-500 text-xs">(${htmlspecialchars(product.numero_producto)})</span></p>
                            <p class="text-xs text-gray-600">Cat: ${htmlspecialchars(product.categoria_nombre)} | Bod: ${htmlspecialchars(product.bodega_nombre)} | Precio: $${parseFloat(product.precio).toFixed(2)} | Estado: ${htmlspecialchars(product.estado)}</p>
                        </div>
                        <button type="button" class="remove-product-btn" data-index="${index}">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    productsToEnterListDiv.appendChild(productItem);
                });
            }
            productCountSpan.textContent = productsToEnter.length;
            confirmProductCountSpan.textContent = productsToEnter.length;
        }

        // Función para escapar caracteres HTML
        function htmlspecialchars(str) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return str.replace(/[&<>"']/g, function (m) { return map[m]; });
        }

        openProductBtn.onclick = function () {
            productModal.style.display = "flex";
            productsToEnter = []; // Reiniciar la lista al abrir el modal
            renderProductsToEnterList(); // Renderizar lista vacía
            addProductToListForm.reset(); // Limpiar el formulario de añadir
            hideMessage(createProductMessageContainer); // Ocultar mensajes anteriores
        }

        closeProductSpan.onclick = function () {
            productModal.style.display = "none";
            productsToEnter = []; // Limpiar la lista al cerrar
            renderProductsToEnterList(); // Asegurarse de que la lista esté vacía
            addProductToListForm.reset();
            hideMessage(createProductMessageContainer);
        }

        cancelProductBtn.onclick = function () {
            productModal.style.display = "none";
            productsToEnter = []; // Limpiar la lista al cancelar
            renderProductsToEnterList();
            addProductToListForm.reset();
            hideMessage(createProductMessageContainer);
        }

        // Evento para eliminar un producto de la lista temporal
        productsToEnterListDiv.addEventListener('click', function (event) {
            if (event.target.classList.contains('remove-product-btn') || event.target.closest('.remove-product-btn')) {
                const button = event.target.classList.contains('remove-product-btn') ? event.target : event.target.closest('.remove-product-btn');
                const indexToRemove = button.dataset.index;
                productsToEnter.splice(indexToRemove, 1); // Eliminar el producto del array
                renderProductsToEnterList(); // Volver a renderizar la lista
            }
        });

        // Evento para añadir un producto a la lista temporal
        addProductToListForm.addEventListener('submit', function (event) {
            event.preventDefault();

            hideMessage(createProductMessageContainer); // Ocultar cualquier mensaje anterior

            const newProduct = {
                numero_producto: document.getElementById('numero_producto').value,
                nombre: document.getElementById('nombre_producto').value,
                estado: document.getElementById('estado').value,
                precio: parseFloat(document.getElementById('precio').value).toFixed(2),
                idcategoria: document.getElementById('categoria').value,
                idbodega: document.getElementById('bodega').value,
                // Obtener los nombres para mostrar en la lista
                categoria_nombre: document.getElementById('categoria').options[document.getElementById('categoria').selectedIndex].text,
                bodega_nombre: document.getElementById('bodega').options[document.getElementById('bodega').selectedIndex].text
            };

            // Validación básica para evitar duplicados en la lista temporal por numero_producto
            const isDuplicate = productsToEnter.some(p => p.numero_producto === newProduct.numero_producto);
            if (isDuplicate) {
                showMessage(createProductMessageContainer, 'Ya existe un producto con el mismo Número de Producto en la lista.', 'error');
                return;
            }

            productsToEnter.push(newProduct);
            renderProductsToEnterList();
            addProductToListForm.reset(); // Limpiar el formulario después de añadir
            showMessage(createProductMessageContainer, 'Producto añadido a la lista. Puedes añadir más o confirmar el ingreso.', 'success');
        });

        // Evento para confirmar el ingreso masivo de productos
        confirmBulkProductEntryBtn.addEventListener('click', function () {
            if (productsToEnter.length === 0) {
                showMessage(createProductMessageContainer, 'No hay productos en la lista para ingresar.', 'error');
                return;
            }

            // Deshabilitar el botón para evitar múltiples envíos
            confirmBulkProductEntryBtn.disabled = true;
            confirmBulkProductEntryBtn.textContent = 'Guardando...';

            fetch('producto/op_crear_multiples_productos.php', { // Necesitarás crear este nuevo archivo PHP
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ products: productsToEnter })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage(createProductMessageContainer, data.message, 'success');
                        productsToEnter = []; // Vaciar la lista después del éxito
                        renderProductsToEnterList();
                        // Opcional: Cerrar el modal o dejarlo abierto para más operaciones
                        // productModal.style.display = "none";
                        // location.reload(); // Si quieres recargar la página principal
                    } else {
                        // Si hay errores parciales o totales, mostrar el mensaje de error
                        showMessage(createProductMessageContainer, data.message || 'Error al guardar algunos productos.', 'error');
                        // Opcional: Si el backend devuelve qué productos fallaron, podrías actualizar la lista productsToEnter
                    }
                })
                .catch(error => {
                    showMessage(createProductMessageContainer, 'Error de conexión al intentar guardar productos: ' + error.message, 'error');
                    console.error('Error en el envío masivo de productos:', error);
                })
                .finally(() => {
                    // Habilitar el botón nuevamente (podrías cambiar el texto también)
                    confirmBulkProductEntryBtn.disabled = false;
                    confirmBulkProductEntryBtn.innerHTML = '<i class="fas fa-save mr-1"></i> Confirmar Ingreso (<span id="confirmProductCount">' + productsToEnter.length + '</span>)';
                });
        });


        // Modal para Consultar Producto (SIN CAMBIOS)
        var consultProductModal = document.getElementById("consultProductModal");
        var openConsultProductBtn = document.getElementById("openConsultProductModal");
        var closeConsultProductSpan = document.getElementById("closeConsultProductModal");
        var searchProductButton = document.getElementById("searchProductButton");
        var consultNumeroProductoInput = document.getElementById("consult_numero_producto");
        var productInfoDiv = document.getElementById("productInfo");
        var productLoader = document.getElementById("productLoader");
        var productMessage = document.getElementById("productMessage");
        var productDetailsDiv = document.getElementById("productDetails");
        var movementsTimelineDiv = document.getElementById("movementsTimeline");
        var noMovementsMessage = document.getElementById("noMovementsMessage");

        // Nuevos elementos para los campos de solo lectura
        const infoNumeroProductoDisplay = document.getElementById('info_numero_producto_display');
        const infoNombreDisplay = document.getElementById('info_nombre_display');
        const infoOrigenDisplay = document.getElementById('info_origen_display'); // Nuevo
        const infoDestinoDisplay = document.getElementById('info_destino_display'); // Nuevo
        const infoProveedorDisplay = document.getElementById('info_proveedor_display'); // Nuevo
        const infoCategoriaDisplay = document.getElementById('info_categoria_display');
        const infoValorDisplay = document.getElementById('info_valor_display'); // Asumo que es el precio
        const infoDescripcionDisplay = document.getElementById('info_descripcion_display'); // Nuevo
        const infoUsuarioDisplay = document.getElementById('info_usuario_display'); // Nuevo (creador del producto)
        const infoFechaRegistroDisplay = document.getElementById('info_fecha_registro_display');

        openConsultProductBtn.onclick = function () {
            consultProductModal.style.display = "flex";
            // Limpiar campos y mensajes al abrir el modal de consulta
            consultNumeroProductoInput.value = '';
            productDetailsDiv.classList.add('hidden');
            productMessage.textContent = '';
            productLoader.style.display = 'none';
            movementsTimelineDiv.innerHTML = ''; // Limpiar el historial de movimientos
            noMovementsMessage.classList.add('hidden'); // Ocultar mensaje de no movimientos
            // Limpiar también los campos de display
            infoNumeroProductoDisplay.value = '';
            infoNombreDisplay.value = '';
            infoOrigenDisplay.value = 'N/A'; // Valor por defecto
            infoDestinoDisplay.value = 'N/A'; // Valor por defecto
            infoProveedorDisplay.value = 'N/A'; // Valor por defecto
            infoCategoriaDisplay.value = '';
            infoValorDisplay.value = '';
            infoDescripcionDisplay.value = 'N/A'; // Valor por defecto
            infoUsuarioDisplay.value = 'N/A'; // Valor por defecto
            infoFechaRegistroDisplay.value = '';
        }

        closeConsultProductSpan.onclick = function () {
            consultProductModal.style.display = "none";
        }

        searchProductButton.onclick = function () {
            const numeroProducto = consultNumeroProductoInput.value.trim();
            if (!numeroProducto) {
                productMessage.textContent = 'Por favor, introduce el número de producto.';
                productDetailsDiv.classList.add('hidden');
                movementsTimelineDiv.innerHTML = ''; // Limpiar el historial de movimientos
                noMovementsMessage.classList.add('hidden'); // Ocultar mensaje de no movimientos
                return;
            }

            productDetailsDiv.classList.add('hidden'); // Ocultar detalles anteriores
            productMessage.textContent = ''; // Limpiar mensaje anterior
            movementsTimelineDiv.innerHTML = ''; // Limpiar historial de movimientos
            noMovementsMessage.classList.add('hidden'); // Ocultar mensaje de no movimientos
            productLoader.style.display = 'block'; // Mostrar spinner de carga

            // Realizar la solicitud AJAX
            fetch('producto/op_consultar_producto.php?numero_producto=' + encodeURIComponent(numeroProducto))
                .then(response => {
                    productLoader.style.display = 'none'; // Ocultar spinner
                    if (!response.ok) {
                        throw new Error('Error en la solicitud HTTP: ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const product = data.product;
                        const movements = data.movements;

                        // Rellenar información general del producto en los nuevos campos de input
                        infoNumeroProductoDisplay.value = product.numero_producto;
                        infoNombreDisplay.value = product.nombre;
                        infoCategoriaDisplay.value = product.nombre_categoria;
                        infoValorDisplay.value = parseFloat(product.precio).toFixed(2); // Formatear precio
                        infoFechaRegistroDisplay.value = product.fecha_registro;
                        // Para los campos que no vienen directamente de la tabla producto, se mantiene N/A
                        // o se llenan si op_consultar_producto.php los devuelve (necesitarías modificarlo)
                        // infoOrigenDisplay.value = product.origen || 'N/A';
                        // infoDestinoDisplay.value = product.destino || 'N/A';
                        // infoProveedorDisplay.value = product.proveedor || 'N/A';
                        // infoDescripcionDisplay.value = product.descripcion || 'N/A'; // Esto es diferente a la descripción de movimiento
                        // infoUsuarioDisplay.value = product.usuario_registro || 'N/A'; // Usuario que lo registró

                        productDetailsDiv.classList.remove('hidden');

                        // Mostrar historial de movimientos
                        if (movements.length > 0) {
                            movements.forEach(move => {
                                const item = document.createElement('div');
                                item.classList.add('history-item'); // Usar la nueva clase
                                item.innerHTML = `
                                    <div><label>Proceso:</label> <span>${move.tipo_movimiento_nombre}</span></div>
                                    <div><label>Usuario:</label> <span>${move.usuario_nombre}</span></div>
                                    <div><label>Fecha:</label> <span>${move.fecha_movimiento.split(' ')[0]}</span></div>
                                    <div class="full-width-field">
                                        <label>Descripción:</label>
                                        <textarea class="form-input" readonly>${move.movimiento_descripcion || 'N/A'}</textarea>
                                    </div>
                                `;
                                movementsTimelineDiv.appendChild(item);
                            });
                            noMovementsMessage.classList.add('hidden');
                        } else {
                            noMovementsMessage.classList.remove('hidden');
                        }

                    } else {
                        productMessage.textContent = data.message || 'Producto no encontrado.';
                        productDetailsDiv.classList.add('hidden');
                        movementsTimelineDiv.innerHTML = '';
                        noMovementsMessage.classList.remove('hidden'); // Mostrar mensaje si no hay producto o movimientos
                    }
                })
                .catch(error => {
                    productLoader.style.display = 'none'; // Ocultar spinner en caso de error
                    productMessage.textContent = 'Error al buscar el producto: ' + error.message;
                    console.error('Fetch error:', error);
                    productDetailsDiv.classList.add('hidden');
                    movementsTimelineDiv.innerHTML = '';
                    noMovementsMessage.classList.remove('hidden'); // Mostrar mensaje de error general
                });
        }

        // Nuevo: Modal para Modificar Producto (SIN CAMBIOS)
        var modifyProductModal = document.getElementById("modifyProductModal");
        var openModifyProductBtn = document.getElementById("openModifyProductModal");
        var closeModifyProductSpan = document.getElementById("closeModifyProductModal");
        var cancelModifyProductBtn = document.getElementById("cancelModifyProductButton");
        var searchModifyProductButton = document.getElementById("searchModifyProductButton");
        var modifyNumeroProductoSearchInput = document.getElementById("modify_numero_producto_search");
        var modifyProductLoader = document.getElementById("modifyProductLoader");
        var modifyProductMessage = document.getElementById("modifyProductMessage"); // Este es para el mensaje de búsqueda del producto
        var modifyProductMessageContainer = document.getElementById("modifyProductMessageContainer"); // Contenedor de mensajes de modificación (éxito/error)
        var actualModifyProductForm = document.getElementById("actualModifyProductForm");

        // Campos del formulario de modificación
        const modifyIdProducto = document.getElementById('modify_idproducto');
        const modifyNumeroProducto = document.getElementById('modify_numero_producto');
        const modifyNombreProducto = document.getElementById('modify_nombre_producto');
        const modifyEstado = document.getElementById('modify_estado');
        const modifyPrecio = document.getElementById('modify_precio');
        const modifyCategoria = document.getElementById('modify_categoria');
        const modifyBodega = document.getElementById('modify_bodega');

        openModifyProductBtn.onclick = function () {
            modifyProductModal.style.display = "flex";
            // Limpiar campos y mensajes al abrir el modal de modificación
            modifyNumeroProductoSearchInput.value = '';
            modifyProductMessage.textContent = ''; // Mensaje de búsqueda
            modifyProductLoader.style.display = 'none';
            actualModifyProductForm.classList.add('hidden');
            hideMessage(modifyProductMessageContainer); // Ocultar mensaje de operación
            // Limpiar los campos del formulario de modificación
            modifyIdProducto.value = '';
            modifyNumeroProducto.value = '';
            modifyNombreProducto.value = '';
            modifyEstado.value = 'activo'; // Valor por defecto
            modifyPrecio.value = '';
            modifyCategoria.value = '';
            modifyBodega.value = '';
        }

        closeModifyProductSpan.onclick = function () {
            modifyProductModal.style.display = "none";
        }

        cancelModifyProductBtn.onclick = function () {
            modifyProductModal.style.display = "none";
        }

        searchModifyProductButton.onclick = function () {
            const numeroProducto = modifyNumeroProductoSearchInput.value.trim();
            if (!numeroProducto) {
                modifyProductMessage.textContent = 'Por favor, introduce el número de producto a modificar.';
                actualModifyProductForm.classList.add('hidden');
                hideMessage(modifyProductMessageContainer); // Ocultar mensaje de operación
                return;
            }

            modifyProductMessage.textContent = ''; // Limpiar mensaje de búsqueda
            actualModifyProductForm.classList.add('hidden');
            hideMessage(modifyProductMessageContainer); // Ocultar mensaje de operación
            modifyProductLoader.style.display = 'block';

            fetch('producto/op_obtener_producto.php?numero_producto=' + encodeURIComponent(numeroProducto))
                .then(response => {
                    modifyProductLoader.style.display = 'none';
                    if (!response.ok) {
                        throw new Error('Error en la solicitud HTTP: ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.product) {
                        const product = data.product;

                        // Rellenar el formulario de modificación con los datos del producto
                        modifyIdProducto.value = product.idproducto;
                        modifyNumeroProducto.value = product.numero_producto;
                        modifyNombreProducto.value = product.nombre;
                        modifyEstado.value = product.estado;
                        modifyPrecio.value = parseFloat(product.precio).toFixed(2);
                        modifyCategoria.value = product.idcategoria;
                        modifyBodega.value = product.idbodega;

                        actualModifyProductForm.classList.remove('hidden');
                    } else {
                        modifyProductMessage.textContent = data.message || 'Producto no encontrado para modificar.';
                        actualModifyProductForm.classList.add('hidden');
                    }
                })
                .catch(error => {
                    modifyProductLoader.style.display = 'none';
                    modifyProductMessage.textContent = 'Error al buscar el producto: ' + error.message;
                    console.error('Fetch error:', error);
                    actualModifyProductForm.classList.add('hidden');
                });
        }

        // Interceptar el envío del formulario de modificación para usar AJAX
        actualModifyProductForm.addEventListener('submit', function (event) {
            event.preventDefault(); // Evitar el envío normal del formulario

            hideMessage(modifyProductMessageContainer); // Ocultar cualquier mensaje anterior

            const formData = new FormData(this); // Obtener datos del formulario

            fetch(this.action, {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    return response.json(); // Siempre intenta parsear el JSON
                })
                .then(data => {
                    if (data.success) {
                        showMessage(modifyProductMessageContainer, data.message, 'success');
                        // Opcional: podrías recargar la página si los cambios necesitan ser reflejados en el dashboard principal
                        // location.reload();
                    } else {
                        showMessage(modifyProductMessageContainer, data.message, 'error');
                    }
                })
                .catch(error => {
                    showMessage(modifyProductMessageContainer, 'Hubo un problema al procesar la solicitud: ' + error.message, 'error');
                    console.error('Error al modificar producto:', error);
                });
        });

        // Cierre de modales al hacer clic fuera de ellos (NO CIERRE PARA INGRESAR Y MODIFICAR)
        window.onclick = function (event) {
            // El modal de Ingresar Producto NO se cerrará al hacer clic fuera
            if (event.target == productModal) {
                // No hacer nada para evitar que se cierre
            }
            // El modal de Consultar Producto SÍ se cerrará al hacer clic fuera
            if (event.target == consultProductModal) {
                consultProductModal.style.display = "none";
            }
            // El modal de Modificar Producto NO se cerrará al hacer clic fuera
            if (event.target == modifyProductModal) {
                // No hacer nada para evitar que se cierre
            }
        }
    </script>
</body>

</html>