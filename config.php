<?php
// Configuración de la base de datos
$host = '127.0.0.1';  // Dirección del servidor de la base de datos
$dbname = 'quiniela_fantasy_edu';  // Nombre de la base de datos
$username = 'root';  // Nombre de usuario para la base de datos
$password = '';  // Contraseña para la base de datos

try {
    // Crear una conexión a la base de datos usando PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Establecer el modo de error para PDO a excepción
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Manejo de errores de conexión
    die("Error al conectar con la base de datos: " . $e->getMessage());
}
?>
