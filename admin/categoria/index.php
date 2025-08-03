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
$permisoRequerido = $categoria_index;
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
 // Verificación de rol admin

$categorias = [];
try {
    // Obtener todas las categorías de la base de datos
    $stmt = $pdo->query("SELECT idcategoria, nombre, descripcion FROM inventario360_categoria ORDER BY nombre ASC");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['mensaje_error'] = "Error al cargar las categorías: " . $e->getMessage();
    header("Location: ../"); // Redirige al dashboard si hay un error crítico
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Categorías - <?php echo $name_corp; ?></title>
    <link rel="shortcut icon" href="<?php echo $logo; ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .modal {
            display: none; /* Oculto por defecto */
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
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
                <span class="ml-4 text-sm sm:text-lg text-gray-600">/ Gestión de Categorias </span>
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
                <i class="fas fa-tags text-indigo-600 mr-3"></i> Gestión de Categorías
            </h2>
            <a href="../" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-arrow-left mr-2"></i> Volver al Inicio
            </a>
        </div>

        <?php if (isset($_SESSION['mensaje_exito'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                <p class="font-bold">¡Éxito!</p>
                <p><?php echo htmlspecialchars($_SESSION['mensaje_exito']); ?></p>
            </div>
            <?php unset($_SESSION['mensaje_exito']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['mensaje_error'])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                <p class="font-bold">¡Error!</p>
                <p><?php echo htmlspecialchars($_SESSION['mensaje_error']); ?></p>
            </div>
            <?php unset($_SESSION['mensaje_error']); ?>
        <?php endif; ?>

        <div class="mb-6 text-right">
            <button id="openAddCategoryModal" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                <i class="fas fa-plus-circle mr-2"></i> Añadir Nueva Categoría
            </button>
        </div>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Listado de Categorías
                </h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                    Aquí puedes ver todas las categorías de productos registradas.
                </p>
            </div>
            <div class="border-t border-gray-200">
                <?php if (count($categorias) > 0): ?>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ID
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Nombre
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Descripción
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($categorias as $categoria): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($categoria['idcategoria']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($categoria['descripcion']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button type="button" class="text-indigo-600 hover:text-indigo-900 mr-4 edit-button" 
                                            data-id="<?php echo htmlspecialchars($categoria['idcategoria']); ?>" 
                                            data-nombre="<?php echo htmlspecialchars($categoria['nombre']); ?>" 
                                            data-descripcion="<?php echo htmlspecialchars($categoria['descripcion']); ?>"
                                            title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="text-red-600 hover:text-red-900 delete-button" 
                                            data-id="<?php echo htmlspecialchars($categoria['idcategoria']); ?>" 
                                            data-nombre="<?php echo htmlspecialchars($categoria['nombre']); ?>"
                                            title="Eliminar">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="p-6 text-gray-500">No hay categorías registradas aún.</p>
                <?php endif; ?>
            </div>
        </div>

    </main>

    <footer class="bg-white border-t mt-10 py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-gray-500 text-sm">
            <p><?php echo $name_corp; ?> v2.0 &copy; <?php echo date('Y'); ?> - Todos los derechos reservados</p>
            <p class="mt-1">Sistema desarrollado para gestión integral de inventarios</p>
        </div>
    </footer>

    <div id="addCategoryModal" class="modal">
        <div class="modal-content">
            <span class="close-button" id="closeAddCategoryModal">&times;</span>
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Añadir Nueva Categoría</h2>
            <form action="op_crear_categoria.php" method="POST" class="space-y-4">
                <div>
                    <label for="add_nombre_categoria" class="block text-sm font-medium text-gray-700">Nombre de la Categoría:</label>
                    <input type="text" id="add_nombre_categoria" name="nombre" required 
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="add_descripcion_categoria" class="block text-sm font-medium text-gray-700">Descripción:</label>
                    <textarea id="add_descripcion_categoria" name="descripcion" rows="3" 
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                </div>
                <div class="flex justify-end">
                    <button type="button" id="cancelAddCategoryModal" class="mr-3 px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Guardar Categoría
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="editCategoryModal" class="modal">
        <div class="modal-content">
            <span class="close-button" id="closeEditCategoryModal">&times;</span>
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Editar Categoría</h2>
            <form action="op_editar_categoria.php" method="POST" class="space-y-4">
                <input type="hidden" id="edit_id_categoria" name="idcategoria">
                <div>
                    <label for="edit_nombre_categoria" class="block text-sm font-medium text-gray-700">Nombre de la Categoría:</label>
                    <input type="text" id="edit_nombre_categoria" name="nombre" required 
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="edit_descripcion_categoria" class="block text-sm font-medium text-gray-700">Descripción:</label>
                    <textarea id="edit_descripcion_categoria" name="descripcion" rows="3" 
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                </div>
                <div class="flex justify-end">
                    <button type="button" id="cancelEditCategoryModal" class="mr-3 px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Actualizar Categoría
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteConfirmModal" class="modal">
        <div class="modal-content max-w-sm">
            <span class="close-button" id="closeDeleteConfirmModal">&times;</span>
            <h2 class="text-xl font-bold text-gray-800 mb-4 text-center">Confirmar Eliminación</h2>
            <p class="text-gray-700 mb-6 text-center">¿Estás seguro de que deseas eliminar la categoría "<strong id="categoryToDeleteName"></strong>"?</p>
            <form action="op_eliminar_categoria.php" method="POST" class="flex justify-center space-x-4">
                <input type="hidden" id="delete_id_categoria" name="idcategoria">
                <button type="button" id="cancelDeleteConfirmModal" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Eliminar
                </button>
            </form>
        </div>
    </div>

    <script>
        // Lógica para el modal de Añadir Categoría
        const addModal = document.getElementById("addCategoryModal");
        const openAddBtn = document.getElementById("openAddCategoryModal");
        const closeAddSpan = document.getElementById("closeAddCategoryModal");
        const cancelAddBtn = document.getElementById("cancelAddCategoryModal");

        openAddBtn.onclick = function() {
            addModal.style.display = "flex"; 
        }
        closeAddSpan.onclick = function() {
            addModal.style.display = "none";
        }
        cancelAddBtn.onclick = function() {
            addModal.style.display = "none";
        }

        // Lógica para el modal de Editar Categoría
        const editModal = document.getElementById("editCategoryModal");
        const closeEditSpan = document.getElementById("closeEditCategoryModal");
        const cancelEditBtn = document.getElementById("cancelEditCategoryModal");
        const editButtons = document.querySelectorAll(".edit-button");

        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const nombre = this.getAttribute('data-nombre');
                const descripcion = this.getAttribute('data-descripcion');

                document.getElementById('edit_id_categoria').value = id;
                document.getElementById('edit_nombre_categoria').value = nombre;
                document.getElementById('edit_descripcion_categoria').value = descripcion;
                editModal.style.display = "flex";
            });
        });

        closeEditSpan.onclick = function() {
            editModal.style.display = "none";
        }
        cancelEditBtn.onclick = function() {
            editModal.style.display = "none";
        }

        // Lógica para el modal de Confirmación de Eliminación
        const deleteConfirmModal = document.getElementById("deleteConfirmModal");
        const closeDeleteSpan = document.getElementById("closeDeleteConfirmModal");
        const cancelDeleteBtn = document.getElementById("cancelDeleteConfirmModal");
        const deleteButtons = document.querySelectorAll(".delete-button");
        const categoryToDeleteName = document.getElementById("categoryToDeleteName");
        const deleteIdInput = document.getElementById("delete_id_categoria");

        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const nombre = this.getAttribute('data-nombre');
                
                categoryToDeleteName.textContent = nombre;
                deleteIdInput.value = id;
                deleteConfirmModal.style.display = "flex";
            });
        });

        closeDeleteSpan.onclick = function() {
            deleteConfirmModal.style.display = "none";
        }
        cancelDeleteBtn.onclick = function() {
            deleteConfirmModal.style.display = "none";
        }

        // Cierra cualquier modal si se hace clic fuera de su contenido
        window.onclick = function(event) {
            if (event.target == addModal) {
                addModal.style.display = "none";
            }
            if (event.target == editModal) {
                editModal.style.display = "none";
            }
            if (event.target == deleteConfirmModal) {
                deleteConfirmModal.style.display = "none";
            }
        }
    </script>
</body>
</html>