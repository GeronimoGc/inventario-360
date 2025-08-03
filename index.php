<?php
session_start(); // ¡Siempre la primera línea!

// --- LÓGICA DE REDIRECCIÓN PARA USUARIOS YA AUTENTICADOS ---
// Si el usuario ya está logueado, redirigir a su panel correspondiente.
// Esta parte evita que un usuario ya logueado vea el formulario de login.
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    require_once './assets/config/info.php';
$permisoRequerido = $op_validar;
    if (isset($_SESSION['usuario_permisos']) && in_array($permisoRequerido, $_SESSION['usuario_permisos'])) {
        header("Location: admin/");
        exit();
    } else {
        // Si no tiene el permiso 'acceso_view_gestor_usuario', redirigir al login y mostrar el aviso.
        $_SESSION['error'] = "Acceso denegado. No cuentas con el permiso " . $permisoRequerido . ". Por favor Contacta con tu departamente de IT o administrador de ayudas";
        header("Location: /");
        exit();
    }
}

// Si el usuario NO está logueado, el código HTML de la página de login se mostrará a continuación.
// El bloque PHP dentro del <body> se encargará de mostrar los mensajes de error/éxito
// establecidos por op_validar.php.
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo $name_corp; ?></title>
    <link rel="shortcut icon" href="<?php echo $logo; ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        },
                        secondary: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                        },
                        accent: {
                            50: '#fff7ed',
                            100: '#ffedd5',
                            200: '#fed7aa',
                            300: '#fdba74',
                            400: '#fb923c',
                            500: '#f97316',
                            600: '#ea580c',
                            700: '#c2410c',
                            800: '#9a3412',
                            900: '#7c2d12',
                        }
                    },
                    fontFamily: {
                        'sans': ['Poppins', 'ui-sans-serif', 'system-ui'],
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    },
                    keyframes: {
                        float: {
                            '0%, 100': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-10px)' },
                        }
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Estilos para la animación de despliegue */
        .slide-enter-active,
        .slide-leave-active {
            transition: all 0.5s ease-out;
            max-height: 500px;
            /* Suficientemente grande para el contenido */
            opacity: 1;
            overflow: hidden;
            transform: translateY(0);
        }

        .slide-enter-from {
            max-height: 0;
            opacity: 0;
            padding-top: 0;
            padding-bottom: 0;
            margin-top: 0;
            margin-bottom: 0;
            transform: translateY(20px);
            /* Para un efecto de entrada desde abajo */
        }

        .slide-leave-to {
            max-height: 0;
            opacity: 0;
            padding-top: 0;
            padding-bottom: 0;
            margin-top: 0;
            margin-bottom: 0;
            transform: translateY(-20px);
            /* Para un efecto de salida hacia arriba */
        }

        .slide-hidden {
            display: none;
        }

        /* Asegurarse de que los formularios no tengan overflow visible por defecto */
        #loginForm,
        #passwordResetContainer {
            box-sizing: border-box;
            /* Incluir padding y border en el tamaño */
        }
    </style>
</head>

<body class="bg-gradient-to-br from-primary-50 to-primary-100 min-h-screen flex items-center justify-center p-4">
    <div
        class="w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden transition-all duration-300 hover:shadow-2xl">
        <div
            class="bg-gradient-to-r from-primary-600 to-primary-800 p-6 flex justify-between items-center relative overflow-hidden">
            <div class="absolute -right-10 -top-10 w-32 h-32 rounded-full bg-white/10"></div>
            <div class="absolute -left-5 -bottom-5 w-20 h-20 rounded-full bg-white/10"></div>

            <div class="flex items-center space-x-3 z-10">
                <div class="bg-white/20 p-2 rounded-lg">
                    <i class="fas fa-boxes text-white text-2xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-white">INVENTARIO <span class="text-accent-300">360°</span></h1>
            </div>
            <div
                class="bg-white/20 text-white font-bold rounded-full w-12 h-12 flex items-center justify-center z-10 animate-float">
                <i class="fas fa-lock-open"></i>
            </div>
        </div>

        <div class="p-8">
            <?php
            // --- BLOQUE PARA MOSTRAR MENSAJES DE SESIÓN (ÉXITO/ERROR) ---
            // Este bloque verifica si hay mensajes en la sesión (establecidos por op_validar.php u otro archivo)
            // y los muestra al usuario.
            if (isset($_SESSION['error']) || isset($_SESSION['success']) || isset($_SESSION['mensaje'])) {
                $tipo = 'error'; // Default
                $texto = '';
                if (isset($_SESSION['error'])) {
                    $texto = $_SESSION['error'];
                    unset($_SESSION['error']); // ¡IMPORTANTE! Eliminar el mensaje de la sesión después de mostrarlo.
                } elseif (isset($_SESSION['success'])) {
                    $tipo = 'exito';
                    $texto = $_SESSION['success'];
                    unset($_SESSION['success']);
                } elseif (isset($_SESSION['mensaje'])) {
                    $tipo = $_SESSION['mensaje']['tipo'];
                    $texto = $_SESSION['mensaje']['texto'];
                    unset($_SESSION['mensaje']);
                }

                $clase_alerta = '';
                $icono_alerta = '';
                if ($tipo == 'exito') {
                    $clase_alerta = 'bg-green-100 border-green-400 text-green-700';
                    $icono_alerta = 'fas fa-check-circle';
                } elseif ($tipo == 'error') {
                    $clase_alerta = 'bg-red-100 border-red-400 text-red-700';
                    $icono_alerta = 'fas fa-exclamation-circle';
                }

                echo '<div class="mb-6 p-4 border rounded-md ' . $clase_alerta . ' flex items-start animate-pulse-slow" role="alert">';
                echo '<i class="' . $icono_alerta . ' mr-3 mt-1 ' . ($tipo == 'exito' ? 'text-green-500' : 'text-red-500') . '"></i>';
                echo '<div>' . htmlspecialchars($texto) . '</div>'; // htmlspecialchars() es crucial para seguridad
                echo '</div>';
            }

            $saved_email = '';
            $remember_checked = '';
            if (isset($_COOKIE['remember_email'])) {
                $saved_email = htmlspecialchars($_COOKIE['remember_email']);
                $remember_checked = 'checked';
            }
            ?>

            <form id="loginForm" action="op_validar.php" method="post" class="space-y-6">
                <div class="space-y-2">
                    <label for="correo" class="block text-sm font-medium text-gray-700 flex items-center">
                        <i class="fas fa-envelope mr-2 text-primary-600"></i> Correo electrónico
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-at text-gray-400"></i>
                        </div>
                        <input type="email" id="correo" name="correo" value="<?= $saved_email ?>"
                            class="pl-10 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition duration-200"
                            placeholder="tu@email.com" required>
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="clave" class="block text-sm font-medium text-gray-700 flex items-center">
                        <i class="fas fa-key mr-2 text-primary-600"></i> Contraseña
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <i class="fas fa-eye-slash text-gray-400 cursor-pointer hover:text-primary-600"
                                id="togglePassword"></i>
                        </div>
                        <input type="password" id="clave" name="clave"
                            class="pl-10 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition duration-200"
                            placeholder="••••••••" required>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input type="checkbox" id="remember" name="remember" <?= $remember_checked ?>
                            class="h-5 w-5 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                        <label for="remember" class="ml-2 block text-sm text-gray-700">Recordar mi cuenta</label>
                    </div>
                    <a href="#" id="showResetFormLink"
                        class="text-sm text-primary-600 hover:text-primary-800 font-medium transition duration-200">
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>

                <button type="submit"
                    class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-white bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 font-medium transition-all duration-300 transform hover:-translate-y-1">
                    <i class="fas fa-sign-in-alt mr-2"></i> INGRESAR AL SISTEMA
                </button>

                <div class="relative mt-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500">O ingresa con</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 mt-4">
                    <a href="#" id="loginGoogle"
                        class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-lg shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition duration-200">
                        <i class="fab fa-google text-red-500 mr-2"></i> Google
                    </a>
                    <a href="#" id="loginMicrosoft"
                        class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-lg shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition duration-200">
                        <i class="fab fa-microsoft text-blue-500 mr-2"></i> Microsoft
                    </a>
                </div>
            </form>

            <div id="passwordResetContainer" class="slide-hidden mt-8 pt-6 border-t border-gray-200">
                <h2 class="text-xl font-bold text-gray-800 mb-4 text-center">Restablecer Contraseña</h2>
                <form action="users/update/recovery_password/op_recovery.php" method="POST" class="space-y-4">
                    <p class="text-sm text-gray-600 text-center mb-4">Ingresa tu ID de usuario y correo para restablecer
                        tu contraseña.</p>
                    <div>
                        <label for="reset_id_usuario" class="block text-sm font-medium text-gray-700">ID de
                            Usuario:</label>
                        <input type="number" id="reset_id_usuario" name="id_usuario" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="reset_correo" class="block text-sm font-medium text-gray-700">Correo
                            Electrónico:</label>
                        <input type="email" id="reset_correo" name="correo" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <hr class="my-2">
                    <div>
                        <label for="reset_nueva_contrasenia" class="block text-sm font-medium text-gray-700">Nueva
                            Contraseña:</label>
                        <input type="password" id="reset_nueva_contrasenia" name="nueva_contrasenia" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="reset_confirmar_contrasenia"
                            class="block text-sm font-medium text-gray-700">Confirmar Nueva Contraseña:</label>
                        <input type="password" id="reset_confirmar_contrasenia" name="confirmar_contrasenia" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" id="showLoginFormLink"
                            class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Volver al Login
                        </button>
                        <button type="submit"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Restablecer Contraseña
                        </button>
                    </div>
                </form>
            </div>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    ¿No tienes una cuenta?
                    <a href="#" id="registerLink"
                        class="font-medium text-primary-600 hover:text-primary-800 transition duration-200">
                        Regístrate aquí
                    </a>
                </p>
            </div>
        </div>
    </div>

    <div id="outOfServiceModal"
        class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center hidden z-50">
        <div class="relative p-8 bg-white w-full max-w-md m-auto flex-col flex rounded-lg shadow-lg">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-gray-700 flex items-center">
                    <i class="fas fa-exclamation-triangle text-yellow-500 mr-3"></i>Aviso Importante
                </h3>
                <button id="closeModalButton" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <p class="text-gray-600 mb-6">Esta función se encuentra temporalmente fuera de servicio. Agradecemos tu
                comprensión.</p>
            <button id="okModalButton"
                class="w-full bg-primary-600 hover:bg-primary-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">Entendido</button>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordInput = document.getElementById('clave');
            const icon = this;

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        });

        // Lógica para alternar entre formularios con animación
        const showResetFormLink = document.getElementById('showResetFormLink'); // Link para ir a restablecer
        const showLoginFormLink = document.getElementById('showLoginFormLink'); // Botón para volver al login

        const loginForm = document.getElementById('loginForm');
        const passwordResetContainer = document.getElementById('passwordResetContainer');

        function animateToggle(showElement, hideElement) {
            // Oculta el elemento actual
            hideElement.classList.add('slide-leave-active');
            hideElement.classList.add('slide-leave-to');

            setTimeout(() => {
                hideElement.classList.add('slide-hidden');
                hideElement.classList.remove('slide-leave-active');
                hideElement.classList.remove('slide-leave-to');

                // Muestra el nuevo elemento
                showElement.classList.remove('slide-hidden');
                showElement.classList.add('slide-enter-active');
                showElement.classList.add('slide-enter-from');

                // Pequeño retraso para que la transición de 'enter-from' sea visible
                setTimeout(() => {
                    showElement.classList.remove('slide-enter-from');
                }, 10);

            }, 500); // Duración de la animación de ocultar
        }

        showResetFormLink.addEventListener('click', function (e) {
            e.preventDefault();
            animateToggle(passwordResetContainer, loginForm);
        });

        showLoginFormLink.addEventListener('click', function () {
            animateToggle(loginForm, passwordResetContainer);
        });


        // Modal para funciones no disponibles (existente)
        const outOfServiceModal = document.getElementById('outOfServiceModal');
        const closeModalButton = document.getElementById('closeModalButton');
        const okModalButton = document.getElementById('okModalButton');

        function showAlertOutOfService(event) {
            event.preventDefault(); // Prevenir la acción por defecto del enlace
            if (outOfServiceModal) {
                outOfServiceModal.classList.remove('hidden');
            }
        }

        function hideModal() {
            if (outOfServiceModal) {
                outOfServiceModal.classList.add('hidden');
            }
        }

        const loginGoogleButton = document.getElementById('loginGoogle');
        const loginMicrosoftButton = document.getElementById('loginMicrosoft');
        const registerLink = document.getElementById('registerLink');

        if (closeModalButton) closeModalButton.addEventListener('click', hideModal);
        if (okModalButton) okModalButton.addEventListener('click', hideModal);

        if (loginGoogleButton) loginGoogleButton.addEventListener('click', showAlertOutOfService);
        if (loginMicrosoftButton) loginMicrosoftButton.addEventListener('click', showAlertOutOfService);
        if (registerLink) registerLink.addEventListener('click', showAlertOutOfService);

    </script>
</body>

</html>