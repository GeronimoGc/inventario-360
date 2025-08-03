<?php
if (!defined('PROTECT_CONFIG')) {
    die('Acceso directo no permitido');
}

$servidor = "localhost";
$usuario_bd = "root";
$contrasena_bd = "";  // Asegúrate que coincida con tu configuración real
$nombre_bd = "inventario360";

$charset = 'utf8mb4';

$dsn = "mysql:host=$servidor;dbname=$nombre_bd;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $usuario_bd, $contrasena_bd, $options);
} catch (\PDOException $e) {
    // En un entorno de producción, no mostrarías $e->getMessage() directamente al usuario.
    // Lo registrarías y mostrarías un mensaje genérico.
    // Para depuración, puedes dejarlo así temporalmente.
    // throw new \PDOException($e->getMessage(), (int)$e->getCode());
    $dbError = "Error de conexión a la base de datos: " . $e->getMessage(); // Puedes usar esta variable para mostrar un error
    // Para evitar que el script se rompa si $pdo no se crea:
    // $pdo = null; // O manejar el error de forma más robusta
}

?>