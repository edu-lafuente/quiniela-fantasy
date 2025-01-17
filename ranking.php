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

session_start(); // Asegúrate de iniciar sesión para verificar si el usuario está logueado

if (!isset($_SESSION['usuario_id'])) {
    // Si no está logueado, redirigir al login
    header("Location: login.php");
    exit();
}

// Obtener el nombre de usuario del usuario logueado
$usuario_id = $_SESSION['usuario_id'];
$stmt = $pdo->prepare("SELECT nombre_usuario FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch();
$nombre_usuario = $usuario ? $usuario['nombre_usuario'] : 'Usuario desconocido';

// Función para calcular los puntos de los partidos
function calcularPuntos($seleccion, $resultado) {
    if ($seleccion === $resultado) {
        return 3; // Acierta resultado exacto
    }
    $selec_ganador = obtenerGanador($seleccion);
    $real_ganador = obtenerGanador($resultado);
    return $selec_ganador === $real_ganador ? 1 : 0; // Acierta el ganador
}

function obtenerGanador($resultado) {
    // Implementa la lógica para determinar el ganador del partido
    // Ejemplo: si el resultado es 2-1, el ganador es el equipo 1.
}

// Función para calcular los puntos de los goleadores
function calcularPuntosGoleadores($goleador_seleccionado, $goleador_real) {
    return $goleador_seleccionado === $goleador_real ? 1 : 0; // 1 punto por acertar el goleador
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking de Participantes</title>
    <!-- Usar Bootstrap desde el CDN -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f9;
        }
        .container {
            margin-top: 40px;
        }
        h1, h2 {
            font-family: 'Helvetica', sans-serif;
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .table-dark {
            background-color: #343a40;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }
        .table-success {
            background-color: #28a745 !important;
            color: white;
        }
        .table-warning {
            background-color: #ffc107 !important;
            color: black;
        }
        .table-danger {
            background-color: #f8d7da !important;
            color: black;
        }
        .card {
            background-color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .card-body {
            padding: 20px;
        }
        .text-primary {
            color: #007bff;
        }
        .text-success {
            color: #28a745;
        }
        .text-muted {
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Mostrar nombre de usuario logueado -->
        <div class="alert alert-info" role="alert">
            Bienvenido, <?php echo htmlspecialchars($nombre_usuario); ?>
        </div>
        
        <!-- Botón para cerrar sesión -->
        <a href="logout.php" class="btn btn-danger mb-3">Cerrar sesión</a>
        <a href='quiniela.php' class='btn btn-secondary mb-3 ml-2'>Quiniela</a>
        <a href='quiniela_pichichi.php' class='btn btn-secondary mb-3 ml-2'>Goleadores</a>

        <!-- Mostrar ranking de partidos -->
        <div class="card">
            <div class="card-header text-primary">
                <h1>Ranking Quiniela</h1>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr><th>Nombre</th><th>Puntos</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            // Obtener el ranking de los participantes
                            $stmt = $pdo->query(
                                'SELECT p.id, p.nombre FROM participantes p'
                            );

                            $ranking_partidos = [];
                            while ($row = $stmt->fetch()) {
                                $puntos = 0;
                                
                                // Verificar si el participante tiene selecciones de partidos
                                $stmtSelecciones = $pdo->prepare(
                                    'SELECT s.resultado_seleccionado, pt.resultado FROM selecciones s 
                                    JOIN partidos pt ON s.partido_id = pt.id 
                                    WHERE s.participante_id = ?'
                                );
                                $stmtSelecciones->execute([$row['id']]);
                                
                                $selecciones = $stmtSelecciones->fetchAll();
                                if ($selecciones) {
                                    foreach ($selecciones as $seleccion) {
                                        if (isset($seleccion['resultado_seleccionado']) && isset($seleccion['resultado'])) {
                                            $puntos += calcularPuntos($seleccion['resultado_seleccionado'], $seleccion['resultado']);
                                        }
                                    }
                                }

                                $ranking_partidos[] = ['nombre' => $row['nombre'], 'id' => $row['id'], 'puntos' => $puntos];
                            }

                            // Ordenar el ranking por puntos de mayor a menor
                            usort($ranking_partidos, function ($a, $b) {
                                return $b['puntos'] - $a['puntos'];
                            });

                            // Mostrar el ranking de partidos
                            foreach ($ranking_partidos as $participante) {
                                echo "<tr>
                                        <td><a class='text-decoration-none' href='detalles_participante.php?participante_id=" . $participante['id'] . "'>" . htmlspecialchars($participante['nombre']) . "</a></td>
                                        <td>" . $participante['puntos'] . "</td>
                                      </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Mostrar ranking de goleadores -->
        <div class="card mt-4">
            <div class="card-header text-primary">
                <h1>Ranking Goleadores</h1>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr><th>Nombre</th><th>Puntos</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            // Obtener el ranking de los goleadores
                            $stmt = $pdo->query(
                                'SELECT p.id, p.nombre FROM participantes p'
                            );

                            $ranking_goleadores = [];
                            while ($row = $stmt->fetch()) {
                                $puntos = 0;
                                
                                // Verificar si el participante tiene selecciones de goleadores
                                $stmtGoleadores = $pdo->prepare(
                                    'SELECT ps.jugador_id AS goleador_seleccionado, g.jugador_id AS goleador_real 
                                     FROM pichichi_seleccion ps 
                                     JOIN goleadores g ON ps.partido_id = g.partido_id 
                                     WHERE ps.usuario_id = ?'
                                );
                                $stmtGoleadores->execute([$row['id']]);
                                
                                $goleadores = $stmtGoleadores->fetchAll();
                                if ($goleadores) {
                                    foreach ($goleadores as $goleador) {
                                        if (isset($goleador['goleador_seleccionado']) && isset($goleador['goleador_real'])) {
                                            $puntos += calcularPuntosGoleadores($goleador['goleador_seleccionado'], $goleador['goleador_real']);
                                        }
                                    }
                                }

                                $ranking_goleadores[] = ['nombre' => $row['nombre'], 'id' => $row['id'], 'puntos' => $puntos];
                            }

                            // Ordenar el ranking de goleadores por puntos
                            usort($ranking_goleadores, function ($a, $b) {
                                return $b['puntos'] - $a['puntos'];
                            });

                            // Mostrar el ranking de goleadores
                            foreach ($ranking_goleadores as $participante) {
                                echo "<tr>
                                        <td><a class='text-decoration-none' href='detalles_participante.php?participante_id=" . $participante['id'] . "'>" . htmlspecialchars($participante['nombre']) . "</a></td>
                                        <td>" . $participante['puntos'] . "</td>
                                      </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
