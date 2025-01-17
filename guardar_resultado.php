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
    die("<div class='alert alert-danger' role='alert'>Error de conexión: " . $e->getMessage() . "</div>");
}

session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

// Verificar si los datos han sido enviados correctamente
if (isset($_POST['partido_id']) && isset($_POST['goles_equipo1']) && isset($_POST['goles_equipo2'])) {
    $partido_id = $_POST['partido_id'];
    $goles_equipo1 = $_POST['goles_equipo1'];
    $goles_equipo2 = $_POST['goles_equipo2'];

    // Comprobar si ya se ha introducido un resultado
    $stmt = $pdo->prepare('SELECT resultado FROM partidos WHERE id = ?');
    $stmt->execute([$partido_id]);
    $resultado = $stmt->fetchColumn();

    if ($resultado) {
        // Si ya existe un resultado, no se puede guardar de nuevo
        echo json_encode(['error' => 'Ya se ha introducido un resultado para este partido']);
        exit();
    }

    // Actualizar el resultado en la tabla 'selecciones'
    $stmt = $pdo->prepare('
        INSERT INTO selecciones (partido_id, participante_id, resultado_seleccionado)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE resultado_seleccionado = ?'
    );
    $stmt->execute([$partido_id, $usuario_id, $goles_equipo1 . '-' . $goles_equipo2, $goles_equipo1 . '-' . $goles_equipo2]);

    // Devolver una respuesta de éxito
    echo json_encode(['success' => 'Resultado guardado correctamente']);
} else {
    // Si los datos no están disponibles
    echo json_encode(['error' => 'Faltan datos para guardar el resultado']);
}
?>
