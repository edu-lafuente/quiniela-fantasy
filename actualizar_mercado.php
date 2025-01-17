<?php
// Conexión a la base de datos
$host = '127.0.0.1';
$db = 'quiniela_fantasy_edu';
$user = 'root';  
$pass = '';     
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Obtener los jugadores aleatorios
$stmt = $pdo->prepare('SELECT id FROM jugadores ORDER BY RAND() LIMIT 3');
$stmt->execute();
$jugadores = $stmt->fetchAll();

// Limpiar el mercado actual (si es necesario)
$stmt = $pdo->prepare('DELETE FROM mercado');
$stmt->execute();

// Insertar jugadores aleatorios en el mercado
foreach ($jugadores as $jugador) {
    $stmt = $pdo->prepare('INSERT INTO mercado (jugador_id) VALUES (?)');
    $stmt->execute([$jugador['id']]);
}

echo "Mercado actualizado con jugadores aleatorios.";
?>