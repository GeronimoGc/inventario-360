<?php

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

// Incluir el archivo de conexión a la base de datos
require_once '../../assets/config/db.php';

$proveedores = [];

try {
    // Obtener proveedores
    $stmt_proveedores = $pdo->query("SELECT idproveedor, nombre, contacto FROM inventario360_proveedor ORDER BY nombre");
    $proveedores = $stmt_proveedores->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error al cargar datos de proveedores: " . htmlspecialchars($e->getMessage());
    // En producción, considera redirigir o mostrar un mensaje de error amigable al usuario
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
    <title>Gestión de Proveedores - <?php echo $name_corp; ?></title>
    <link rel="shortcut icon" href="<?php echo $logo; ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .section-divider {
            border-color: rgba(156, 163, 175, 0.2);
        }

        /* Estilos para el modal flotante */
        .modal {
            display: none;
            /* Oculto por defecto */
            position: fixed;
            z-index: 100;
            /* Alto z-index para que esté sobre todo */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            /* Habilita scroll si el contenido es muy largo */
            background-color: rgba(0, 0, 0, 0.5);
            /* Fondo semitransparente */
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(3px);
            /* Efecto de desenfoque */
        }

        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            /* Ancho máximo para el formulario */
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            position: relative;
            animation: fadeIn 0.3s ease-out;
            /* Animación de entrada */
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

        /* Estilos específicos para el modal de confirmación */
        .confirm-modal .modal-content {
            max-width: 400px;
            text-align: center;
        }

        .confirm-modal .modal-content h2 {
            color: #ef4444;
            /* Rojo para el título de confirmación */
            font-size: 1.5rem;
        }

        .confirm-modal .modal-content .confirm-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        /* Animación de entrada para el modal */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
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
                <i class="fas fa-tags text-indigo-600 mr-3"></i> Gestión de Proveedores
            </h2>
            <a href="../"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-arrow-left mr-2"></i> Volver al Inicio
            </a>
        </div>
        <?php
        // Mostrar mensajes de sesión (éxito/error)
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
                    <h2 class="text-xl sm:text-2xl font-bold mb-2">Gestión de Proveedores</h2>
                    <p class="opacity-90">Administra la información de tus proveedores.</p>
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
                <i class="fas fa-truck text-teal-500 mr-2"></i> Listado de Proveedores
            </h2>

            <button id="openAddProveedorModal"
                class="mb-6 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                <i class="fas fa-plus-circle mr-2"></i> Añadir Nuevo Proveedor
            </button>

            <?php if (empty($proveedores)): ?>
                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4" role="alert">
                    <p class="font-bold">No hay proveedores registrados.</p>
                    <p>Usa el botón "Añadir Nuevo Proveedor" para comenzar.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($proveedores as $proveedor): ?>
                        <div class="bg-white rounded-lg shadow-md overflow-hidden card-hover">
                            <div class="p-6">
                                <h3 class="font-semibold text-gray-900 text-lg mb-2 flex items-center">
                                    <i class="fas fa-building text-gray-500 mr-3"></i>
                                    <?php echo htmlspecialchars($proveedor['nombre']); ?>
                                </h3>
                                <p class="text-sm text-gray-600 mb-2">
                                    <i class="fas fa-envelope mr-2"></i>Contacto:
                                    <?php echo htmlspecialchars($proveedor['contacto']); ?>
                                </p>
                                <div class="mt-4 flex space-x-2">
                                    <button
                                        class="px-3 py-1 bg-yellow-500 text-white rounded-md hover:bg-yellow-600 transition text-sm edit-proveedor-btn"
                                        data-id="<?php echo $proveedor['idproveedor']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($proveedor['nombre']); ?>"
                                        data-contacto="<?php echo htmlspecialchars($proveedor['contacto']); ?>">
                                        <i class="fas fa-edit mr-1"></i> Editar
                                    </button>
                                    <button
                                        class="px-3 py-1 bg-red-500 text-white rounded-md hover:bg-red-600 transition text-sm delete-proveedor-btn"
                                        data-id="<?php echo $proveedor['idproveedor']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($proveedor['nombre']); ?>">
                                        <i class="fas fa-trash-alt mr-1"></i> Eliminar
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="bg-white border-t mt-10 py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-gray-500 text-sm">
            <p><?php echo $name_corp; ?> v2.0 &copy; <?php echo date('Y'); ?> - Todos los derechos reservados</p>
            <p class="mt-1">Sistema desarrollado para gestión integral de inventarios</p>
        </div>
    </footer>

    <div id="addProveedorModal" class="modal">
        <div class="modal-content">
            <span class="close-button" id="closeAddProveedorModal">&times;</span>
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Añadir Nuevo Proveedor</h2>
            <form action="op_crear_proveedor.php" method="POST" class="space-y-4">
                <div>
                    <label for="add_nombre_proveedor" class="block text-sm font-medium text-gray-700">Nombre del
                        Proveedor:</label>
                    <input type="text" id="add_nombre_proveedor" name="nombre" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div>
                    <label for="add_contacto_proveedor" class="block text-sm font-medium text-gray-700">Información de
                        Contacto:</label>
                    <input type="text" id="add_contacto_proveedor" name="contacto"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="Ej: correo@ejemplo.com o +123456789">
                </div>
                <div class="flex justify-end">
                    <button type="button" id="cancelAddProveedorModal"
                        class="mr-3 px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Guardar Proveedor
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="editProveedorModal" class="modal">
        <div class="modal-content">
            <span class="close-button" id="closeEditProveedorModal">&times;</span>
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Editar Proveedor</h2>
            <form action="op_editar_proveedor.php" method="POST" class="space-y-4">
                <input type="hidden" id="edit_id_proveedor" name="idproveedor">
                <div>
                    <label for="edit_nombre_proveedor" class="block text-sm font-medium text-gray-700">Nombre del
                        Proveedor:</label>
                    <input type="text" id="edit_nombre_proveedor" name="nombre" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div>
                    <label for="edit_contacto_proveedor" class="block text-sm font-medium text-gray-700">Información de
                        Contacto:</label>
                    <input type="text" id="edit_contacto_proveedor" name="contacto"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="Ej: correo@ejemplo.com o +123456789">
                </div>
                <div class="flex justify-end">
                    <button type="button" id="cancelEditProveedorModal"
                        class="mr-3 px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Actualizar Proveedor
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="confirmDeleteModal1" class="modal confirm-modal">
        <div class="modal-content">
            <span class="close-button" id="closeConfirmDeleteModal1">&times;</span>
            <h2 class="font-bold mb-4 flex items-center justify-center text-red-600">
                <i class="fas fa-exclamation-triangle mr-2 text-3xl"></i> Confirmar Eliminación
            </h2>
            <p class="text-gray-700 mb-6">¿Estás seguro de que quieres eliminar al proveedor "<strong
                    id="deleteProveedorName1"></strong>"?</p>
            <p class="text-sm text-gray-500 italic">Esta acción es irreversible y eliminará todos los registros
                asociados a este proveedor.</p>
            <div class="confirm-buttons">
                <button type="button" id="cancelDeleteModal1"
                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cancelar
                </button>
                <button type="button" id="proceedToDeleteModal2"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Sí, Estoy Seguro
                </button>
            </div>
            <input type="hidden" id="confirmDeleteId1">
        </div>
    </div>

    <div id="confirmDeleteModal2" class="modal confirm-modal">
        <div class="modal-content">
            <span class="close-button" id="closeConfirmDeleteModal2">&times;</span>
            <h2 class="font-bold mb-4 flex items-center justify-center text-red-600">
                <i class="fas fa-skull-crossbones mr-2 text-3xl"></i> ¡Confirmación Final Requerida!
            </h2>
            <p class="text-gray-700 mb-6">Esta es la **última advertencia**. La eliminación del proveedor "<strong
                    id="deleteProveedorName2"></strong>" es **PERMANENTE** y no se puede deshacer.</p>
            <p class="text-sm text-gray-500 italic">Por favor, escribe "ELIMINAR" en el campo de texto para confirmar.
            </p>
            <input type="text" id="deleteConfirmInput"
                class="mt-4 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm"
                placeholder="Escribe ELIMINAR">
            <div class="confirm-buttons">
                <button type="button" id="cancelDeleteModal2"
                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cancelar
                </button>
                <button type="button" id="finalDeleteBtn"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    disabled>
                    <i class="fas fa-trash-alt mr-1"></i> Eliminar Ahora
                </button>
            </div>
            <input type="hidden" id="confirmDeleteId2">
        </div>
    </div>

    <script>
        // Funciones auxiliares para abrir/cerrar modales
        function openModal(modalId) {
            document.getElementById(modalId).style.display = "flex";
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
        }

        // ------------------- Modal "Añadir Nuevo Proveedor" -------------------
        const addProveedorModal = document.getElementById("addProveedorModal");
        const openAddProveedorBtn = document.getElementById("openAddProveedorModal");
        const closeAddProveedorBtn = document.getElementById("closeAddProveedorModal");
        const cancelAddProveedorBtn = document.getElementById("cancelAddProveedorModal");

        openAddProveedorBtn.onclick = () => openModal("addProveedorModal");
        closeAddProveedorBtn.onclick = () => closeModal("addProveedorModal");
        cancelAddProveedorBtn.onclick = () => closeModal("addProveedorModal");

        // ------------------- Modal "Editar Proveedor" -------------------
        const editProveedorModal = document.getElementById("editProveedorModal");
        const closeEditProveedorBtn = document.getElementById("closeEditProveedorModal");
        const cancelEditProveedorBtn = document.getElementById("cancelEditProveedorModal");
        const editProveedorBtns = document.querySelectorAll(".edit-proveedor-btn");

        closeEditProveedorBtn.onclick = () => closeModal("editProveedorModal");
        cancelEditProveedorBtn.onclick = () => closeModal("editProveedorModal");

        editProveedorBtns.forEach(button => {
            button.addEventListener("click", function () {
                const id = this.dataset.id;
                const nombre = this.dataset.nombre;
                const contacto = this.dataset.contacto;

                document.getElementById("edit_id_proveedor").value = id;
                document.getElementById("edit_nombre_proveedor").value = nombre;
                document.getElementById("edit_contacto_proveedor").value = contacto;
                openModal("editProveedorModal");
            });
        });

        // ------------------- Modales de Confirmación de Eliminación -------------------
        const confirmDeleteModal1 = document.getElementById("confirmDeleteModal1");
        const closeConfirmDeleteModal1 = document.getElementById("closeConfirmDeleteModal1");
        const cancelDeleteModal1 = document.getElementById("cancelDeleteModal1");
        const proceedToDeleteModal2 = document.getElementById("proceedToDeleteModal2");
        const deleteProveedorBtns = document.querySelectorAll(".delete-proveedor-btn");

        const confirmDeleteModal2 = document.getElementById("confirmDeleteModal2");
        const closeConfirmDeleteModal2 = document.getElementById("closeConfirmDeleteModal2");
        const cancelDeleteModal2 = document.getElementById("cancelDeleteModal2");
        const deleteConfirmInput = document.getElementById("deleteConfirmInput");
        const finalDeleteBtn = document.getElementById("finalDeleteBtn");

        let proveedorIdToDelete = null; // Variable para almacenar el ID del proveedor a eliminar

        closeConfirmDeleteModal1.onclick = () => closeModal("confirmDeleteModal1");
        cancelDeleteModal1.onclick = () => closeModal("confirmDeleteModal1");
        closeConfirmDeleteModal2.onclick = () => closeModal("confirmDeleteModal2");
        cancelDeleteModal2.onclick = () => {
            closeModal("confirmDeleteModal2");
            deleteConfirmInput.value = ''; // Limpiar el input
            finalDeleteBtn.disabled = true; // Deshabilitar botón
        };

        deleteProveedorBtns.forEach(button => {
            button.addEventListener("click", function () {
                proveedorIdToDelete = this.dataset.id;
                const nombreProveedor = this.dataset.nombre;
                document.getElementById("deleteProveedorName1").textContent = nombreProveedor;
                document.getElementById("confirmDeleteId1").value = proveedorIdToDelete;
                openModal("confirmDeleteModal1");
            });
        });

        proceedToDeleteModal2.onclick = function () {
            closeModal("confirmDeleteModal1");
            const nombreProveedor = document.getElementById("deleteProveedorName1").textContent; // Obtener el nombre del primer modal
            document.getElementById("deleteProveedorName2").textContent = nombreProveedor;
            document.getElementById("confirmDeleteId2").value = proveedorIdToDelete; // Pasa el ID al segundo modal
            deleteConfirmInput.value = ''; // Limpiar el input para la segunda confirmación
            finalDeleteBtn.disabled = true; // Deshabilitar botón al abrir el segundo modal
            openModal("confirmDeleteModal2");
        };

        deleteConfirmInput.addEventListener("keyup", function () {
            finalDeleteBtn.disabled = this.value.toUpperCase() !== "ELIMINAR";
        });

        finalDeleteBtn.onclick = function () {
            if (deleteConfirmInput.value.toUpperCase() === "ELIMINAR") {
                // Redirigir para eliminar el proveedor
                window.location.href = "op_eliminar_proveedor.php?id=" + proveedorIdToDelete;
            }
        };

        // Cerrar modales si se hace clic fuera de ellos
        window.onclick = function (event) {
            if (event.target == addProveedorModal) {
                closeModal("addProveedorModal");
            }
            if (event.target == editProveedorModal) {
                closeModal("editProveedorModal");
            }
            if (event.target == confirmDeleteModal1) {
                closeModal("confirmDeleteModal1");
            }
            if (event.target == confirmDeleteModal2) {
                closeModal("confirmDeleteModal2");
            }
        }
    </script>
</body>

</html>