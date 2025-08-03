<?php

date_default_timezone_set('America/Bogota');
define('PROTECT_CONFIG', true);

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['usuario_data'])) {
    // No redirigir directamente, ya que esta página se abrirá en una nueva ventana.
    // Simplemente mostrar un mensaje de error o una página vacía.
    die('Acceso denegado. Por favor, inicie sesión.');
}

require_once '../../assets/config/db.php';

// Asegúrate de que solo los administradores puedan acceder a este reporte
if (!isset($_SESSION['usuario_data']['rol_nombre']) || strtolower($_SESSION['usuario_data']['rol_nombre']) !== 'administrador') {
    die('Acceso denegado. No tienes permisos para ver este informe.');
}

$fecha_filtro = isset($_GET['fecha']) ? $_GET['fecha'] : '';
$accion_filtro = isset($_GET['accion']) ? $_GET['accion'] : '';
$usuario_filtro = isset($_GET['usuario']) ? $_GET['usuario'] : '';

$registros = [];
try {
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
    die("Error al cargar los datos para el reporte: " . htmlspecialchars($e->getMessage()));
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Registro de Actividad</title>
    <link rel="shortcut icon" href="<?php echo $logo; ?>">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20mm; /* Margen para simular un documento impreso */
            font-size: 10pt;
            color: #333;
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 18pt;
        }
        .report-header {
            text-align: right;
            font-size: 9pt;
            margin-bottom: 15px;
        }
        .filters-info {
            background-color: #f8f8f8;
            border: 1px solid #e0e0e0;
            padding: 10px;
            margin-bottom: 20px;
            font-size: 9pt;
        }
        .filters-info p {
            margin: 3px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            color: #555;
            font-weight: bold;
            font-size: 9pt;
        }
        td {
            font-size: 8.5pt;
        }
        .action-tag {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 7.5pt;
            font-weight: bold;
            color: #fff;
            background-color: #999; /* Default */
        }
        .action-tag.insert { background-color: #28a745; } /* Green */
        .action-tag.update { background-color: #007bff; } /* Blue */
        .action-tag.delete { background-color: #dc3545; } /* Red */
        .action-tag.login { background-color: #6f42c1; } /* Purple */
        .action-tag.logout { background-color: #fd7e14; } /* Orange */

        .footer {
            text-align: center;
            margin-top: 40px;
            font-size: 8pt;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }

        /* Ocultar elementos no deseados al imprimir */
        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    
    <div class="no-print" style="text-align: right; margin-bottom: 10px;">
        <a href="../"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-arrow-left mr-2"></i> Volver al Inicio
            </a>
        <button onclick="window.print()" style="padding: 8px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
            <i class="fas fa-print"></i> Imprimir / Guardar como PDF
        </button>
    </div>

    <h1>Reporte de Registro de Actividad</h1>
    <div class="report-header">
        <p>Generado por: <?php echo htmlspecialchars($usuario['nombre']); ?></p>
        <p>Fecha de Generación: <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>

    <div class="filters-info">
        <p><strong>Filtros aplicados:</strong></p>
        <p>Fecha: <?php echo !empty($fecha_filtro) ? htmlspecialchars($fecha_filtro) : 'Todas'; ?></p>
        <p>Acción: <?php echo !empty($accion_filtro) ? htmlspecialchars($accion_filtro) : 'Todas'; ?></p>
        <p>Usuario: <?php echo !empty($usuario_filtro) ? htmlspecialchars($usuario_filtro) : 'Todos'; ?></p>
    </div>

    <?php if (empty($registros)): ?>
        <p style="text-align: center; margin-top: 30px; font-style: italic; color: #777;">No se encontraron registros de actividad para los filtros aplicados.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha y Hora</th>
                    <th>Acción</th>
                    <th>Descripción</th>
                    <th>Usuario</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registros as $registro): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($registro['idreac']); ?></td>
                        <td><?php echo htmlspecialchars(date('d/m/Y H:i:s', strtotime($registro['fecha']))); ?></td>
                        <td>
                            <span class="action-tag <?php 
                                switch ($registro['accion']) {
                                    case 'INSERT': echo 'insert'; break;
                                    case 'UPDATE': echo 'update'; break;
                                    case 'DELETE': echo 'delete'; break;
                                    case 'LOGIN': echo 'login'; break;
                                    case 'LOGOUT': echo 'logout'; break;
                                    default: echo ''; break;
                                }
                            ?>">
                                <?php echo htmlspecialchars($registro['accion']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($registro['descripcion']); ?></td>
                        <td><?php echo htmlspecialchars($registro['usuario_nombre']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="footer">
        <p><?php echo $name_corp; ?> &copy; <?php echo date('Y'); ?></p>
        <p>Reporte generado el <?php echo date('d/m/Y') . ' a las ' . date('H:i:s'); ?></p>
    </div>

    <script>
        // Imprimir automáticamente la página cuando se carga si no es un evento de recarga
        // Descomenta la siguiente línea si quieres que se abra el diálogo de impresión automáticamente
        // window.onload = function() { window.print(); };
    </script>
</body>
</html>