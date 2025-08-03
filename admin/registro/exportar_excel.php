<?php

date_default_timezone_set('America/Bogota');

define('PROTECT_CONFIG', true);

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['usuario_data'])) {
    header("Location: ../../");
    exit();
}

require_once '../../assets/config/db.php';

// Asegúrate de que solo los administradores puedan descargar el registro
if (!isset($_SESSION['usuario_data']['rol_nombre']) || strtolower($_SESSION['usuario_data']['rol_nombre']) !== 'administrador') {
    $_SESSION['error'] = 'Acceso denegado. No tienes permisos para descargar este informe.';
    header("Location: ../../dashboard/");
    exit();
}

$fecha_filtro = isset($_GET['fecha']) ? $_GET['fecha'] : '';
$accion_filtro = isset($_GET['accion']) ? $_GET['accion'] : '';
$usuario_filtro = isset($_GET['usuario']) ? $_GET['usuario'] : '';

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

    // Configurar cabeceras para descarga de archivo Excel (CSV)
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="registro_actividad_' . date('Ymd_His') . '.csv"');

    // Abrir el buffer de salida
    $output = fopen('php://output', 'w');

    // Escribir la cabecera del CSV
    fputcsv($output, ['ID', 'Fecha y Hora', 'Accion', 'Descripcion', 'Usuario']);

    // Escribir los datos
    foreach ($registros as $registro) {
        fputcsv($output, [
            $registro['idreac'],
            date('d/m/Y H:i:s', strtotime($registro['fecha'])),
            $registro['accion'],
            $registro['descripcion'],
            $registro['usuario_nombre']
        ]);
    }

    fclose($output);
    exit();

} catch (PDOException $e) {
    $_SESSION['error'] = 'Error al generar el reporte Excel: ' . $e->getMessage();
    header("Location: index.php");
    exit();
}

?>