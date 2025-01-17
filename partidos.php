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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partidos</title>

    <!-- Usar Bootstrap desde el CDN -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Estilos adicionales para mejorar el diseño -->
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f9;
        }
        .container {
            margin-top: 40px;
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .table-dark {
            background-color: #343a40;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="ranking.php" class="btn btn-primary mb-3">Volver al Ranking</a>
        <h1>Partidos Divididos por Jornadas</h1>

        <?php
        // Obtener todas las jornadas
        $jornadas = $pdo->query('SELECT id, numero FROM jornadas ORDER BY id')->fetchAll();

        // Mostrar partidos por jornada
        foreach ($jornadas as $jornada) {
            echo "<h2 class='mt-4'>Jornada " . htmlspecialchars($jornada['numero']) . "</h2>";

            // Obtener los partidos de cada jornada
            $stmt = $pdo->prepare(
                'SELECT p.equipo1, p.equipo2, p.resultado FROM partidos p WHERE p.jornada_id = ?'
            );
            $stmt->execute([$jornada['id']]);
            $partidos = $stmt->fetchAll();

            // Si hay partidos en la jornada, mostrar en una tabla
            if ($partidos) {
                echo "<div class='table-responsive'>
                        <table class='table table-bordered table-striped'>";
                echo "<thead class='table-dark'>
                        <tr>
                            <th>Equipo 1</th>
                            <th>Equipo 2</th>
                            <th>Resultado</th>
                        </tr>
                      </thead>
                      <tbody>";
                foreach ($partidos as $partido) {
                    $resultado = $partido['resultado'] ? htmlspecialchars($partido['resultado']) : '0-0';
                    echo "<tr>
                            <td>" . htmlspecialchars($partido['equipo1']) . "</td>
                            <td>" . htmlspecialchars($partido['equipo2']) . "</td>
                            <td>$resultado</td>
                          </tr>";
                }
                echo "</tbody></table></div>";
            } else {
                echo "<p class='text-muted'>No hay partidos para esta jornada.</p>";
            }
        }
        ?>
    </div>
</body>
</html>
