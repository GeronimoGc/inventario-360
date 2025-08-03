<?php
date_default_timezone_set('America/Bogota');

define('PROTECT_CONFIG', true);

session_start();

// Redirige al login si no hay sesión activa
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['usuario_data'])) {
    $_SESSION['error'] = 'Por favor, inicia sesión para acceder.';
    header("Location: ../../"); // Ajusta esta ruta si tu página de login no está en la raíz superior
    exit();
}

// Obtener toda la información del usuario desde la sesión
$usuario = $_SESSION['usuario_data'];
$esAdmin = false; // Inicializar por defecto

require_once '../../assets/config/info.php';
$permisoRequerido = $admin;
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

// Incluir el archivo de conexión a la base de datos
require_once '../../assets/config/db.php';

$registros = [];
$fecha_filtro = isset($_GET['fecha']) ? $_GET['fecha'] : '';
$accion_filtro = isset($_GET['accion']) ? $_GET['accion'] : '';
$usuario_filtro = isset($_GET['usuario']) ? $_GET['usuario'] : '';

try {
    // Construir la consulta SQL base
    $sql = "
        SELECT 
            ra.idreac,
            ra.accion,
            ra.fecha,
            ra.descripcion,
            u.nombre as usuario_nombre
        FROM 
            inventario360_registro_actividad ra
        JOIN 
            inventario360_usuario u ON ra.usuario_idusuario = u.idusuario
        WHERE 1=1 
    ";
    $params = [];

    // Aplicar filtros si existen
    if (!empty($fecha_filtro)) {
        $sql .= " AND DATE(ra.fecha) = :fecha";
        $params[':fecha'] = $fecha_filtro;
    }
    if (!empty($accion_filtro)) {
        $sql .= " AND ra.accion = :accion";
        $params[':accion'] = $accion_filtro;
    }
    if (!empty($usuario_filtro)) {
        $sql .= " AND u.nombre LIKE :usuario";
        $params[':usuario'] = '%' . $usuario_filtro . '%';
    }

    $sql .= " ORDER BY ra.fecha DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error al cargar el registro de actividad: " . htmlspecialchars($e->getMessage());
    // En producción, considera un mensaje más amigable
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Actividad - <?php echo $name_corp; ?></title>
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

        /* Estilos específicos para impresión (opcional, para una vista previa más limpia) */
        @media print {
            body {
                background-color: #fff;
            }

            .no-print {
                display: none !important;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            th,
            td {
                border: 1px solid #ccc;
                padding: 8px;
                text-align: left;
            }

            th {
                background-color: #f0f0f0;
            }
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
                <span class="ml-4 text-sm sm:text-lg text-gray-600">/ Registro de actividad</span>
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


        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow-md p-6 mb-8 text-white no-print">
            <div class="flex flex-col md:flex-row items-center justify-between">
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold mb-2">Registro de Actividad</h2>
                    <p class="opacity-90">Visualiza todas las acciones realizadas en el sistema</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <a href="../"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-arrow-left mr-2"></i> Volver al Inicio
                    </a>
                    <span class="inline-block bg-white bg-opacity-20 px-4 py-2 rounded-full">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        <?php echo date('d/m/Y'); ?>
                    </span>
                </div>
            </div>
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
            echo '<div class="mb-4 p-4 border rounded-md ' . $clase_alerta . ' no-print" role="alert">';
            echo '<p>' . htmlspecialchars($texto) . '</p>';
            echo '</div>';
            unset($_SESSION['mensaje']); // Limpiar el mensaje de la sesión
        }
        ?>

        <div class="bg-white rounded-lg shadow-md p-6 mb-8 no-print">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Filtros de Búsqueda y Descarga</h3>
            <form id="filterForm" method="GET" action="" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="fecha" class="block text-sm font-medium text-gray-700">Fecha:</label>
                    <input type="date" id="fecha" name="fecha" value="<?php echo htmlspecialchars($fecha_filtro); ?>"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div>
                    <label for="accion" class="block text-sm font-medium text-gray-700">Acción:</label>
                    <select id="accion" name="accion"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Todas</option>
                        <option value="INSERT" <?php echo ($accion_filtro == 'INSERT') ? 'selected' : ''; ?>>INSERT
                        </option>
                        <option value="UPDATE" <?php echo ($accion_filtro == 'UPDATE') ? 'selected' : ''; ?>>UPDATE
                        </option>
                        <option value="DELETE" <?php echo ($accion_filtro == 'DELETE') ? 'selected' : ''; ?>>DELETE
                        </option>
                        <option value="LOGIN" <?php echo ($accion_filtro == 'LOGIN') ? 'selected' : ''; ?>>LOGIN</option>
                        <option value="LOGOUT" <?php echo ($accion_filtro == 'LOGOUT') ? 'selected' : ''; ?>>LOGOUT
                        </option>
                    </select>
                </div>
                <div>
                    <label for="usuario_filtro" class="block text-sm font-medium text-gray-700">Usuario:</label>
                    <input type="text" id="usuario_filtro" name="usuario"
                        value="<?php echo htmlspecialchars($usuario_filtro); ?>"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="Nombre de usuario">
                </div>
                <div class="md:col-span-3 flex justify-end space-x-2">
                    <button type="submit"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-filter mr-1"></i> Aplicar Filtros
                    </button>
                    <a href="index.php"
                        class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-times-circle mr-1"></i> Limpiar Filtros
                    </a>

                    <button type="button" onclick="downloadReport('excel')"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <i class="fas fa-file-excel mr-1"></i> Descargar Excel
                    </button>
                    <button type="button" onclick="downloadReport('pdf_html')"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        <i class="fas fa-file-pdf mr-1"></i> Descargar PDF (HTML)
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Fecha y Hora
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Acción
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Descripción
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Usuario
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($registros)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                    No se encontraron registros de actividad.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($registros as $registro): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($registro['idreac']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars(date('d/m/Y H:i:s', strtotime($registro['fecha']))); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php
                                            if ($registro['accion'] == 'INSERT')
                                                echo 'bg-green-100 text-green-800';
                                            else if ($registro['accion'] == 'UPDATE')
                                                echo 'bg-blue-100 text-blue-800';
                                            else if ($registro['accion'] == 'DELETE')
                                                echo 'bg-red-100 text-red-800';
                                            else if ($registro['accion'] == 'LOGIN')
                                                echo 'bg-purple-100 text-purple-800';
                                            else if ($registro['accion'] == 'LOGOUT')
                                                echo 'bg-orange-100 text-orange-800';
                                            else
                                                echo 'bg-gray-100 text-gray-800';
                                            ?>">
                                            <?php echo htmlspecialchars($registro['accion']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                        <?php echo htmlspecialchars($registro['descripcion']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($registro['usuario_nombre']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <footer class="bg-white border-t mt-10 py-6 no-print">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-gray-500 text-sm">
            <p><?php echo $name_corp; ?> v2.0 &copy; <?php echo date('Y'); ?> - Todos los derechos reservados</p>
            <p class="mt-1">Sistema desarrollado para gestión integral de inventarios</p>
        </div>
    </footer>

    <script>
        function downloadReport(format) {
            const form = document.getElementById('filterForm');
            let queryParams = new URLSearchParams();

            // Recopilar los valores de los filtros
            const fecha = form.querySelector('[name="fecha"]').value;
            const accion = form.querySelector('[name="accion"]').value;
            const usuario = form.querySelector('[name="usuario"]').value;

            if (fecha) queryParams.append('fecha', fecha);
            if (accion) queryParams.append('accion', accion);
            if (usuario) queryParams.append('usuario', usuario);

            let downloadUrl = '';
            if (format === 'excel') {
                downloadUrl = 'exportar_excel.php?' + queryParams.toString();
            } else if (format === 'pdf_html') { // Cambiado a 'pdf_html'
                downloadUrl = 'imprimir_pdf_html.php?' + queryParams.toString(); // Nuevo archivo
            }

            if (downloadUrl) {
                window.open(downloadUrl, '_blank');
            }
        }
    </script>
</body>

</html>