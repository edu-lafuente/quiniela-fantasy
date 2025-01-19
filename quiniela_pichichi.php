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

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

function obtenerPartidosPorJornada($pdo) {
    // Obtener todas las jornadas
    $jornadas = $pdo->query('SELECT id, numero FROM jornadas ORDER BY id')->fetchAll();
    
    // Inicializar el array de partidos
    $partidosPorJornada = [];

    foreach ($jornadas as $jornada) {
        // Preparar la consulta para obtener los partidos por jornada
        $stmt = $pdo->prepare('SELECT p.id, p.equipo1, p.equipo2, p.fecha FROM partidos p WHERE p.jornada_id = ?');
        $stmt->execute([$jornada['id']]);
        $partidos = $stmt->fetchAll();

        if (!empty($partidos)) {
            // Solo agregamos a la variable si existen partidos en la jornada
            $partidosPorJornada[] = ['jornada' => $jornada, 'partidos' => $partidos];
        }
    }

    return $partidosPorJornada;  // Retornamos el array de partidos
}

function obtenerJugadoresPorEquipo($pdo, $equipo1, $equipo2) {
    $stmt = $pdo->prepare('SELECT id, nombre, equipo FROM jugadores WHERE equipo IN (?, ?) ORDER BY nombre');
    $stmt->execute([$equipo1, $equipo2]);
    return $stmt->fetchAll();
}

function obtenerSeleccionJugador($pdo, $partido_id, $usuario_id) {
    $stmt = $pdo->prepare(
        'SELECT j.nombre 
        FROM pichichi_seleccion ps
        JOIN jugadores j ON ps.jugador_id = j.id
        WHERE ps.partido_id = ? AND ps.usuario_id = ?'
    );
    $stmt->execute([$partido_id, $usuario_id]);
    return $stmt->fetchColumn();
}

function obtenerGoleadoresPorPartido($pdo, $partido_id) {
    $stmt = $pdo->prepare(
        'SELECT j.nombre, g.cantidad_goles 
        FROM goleadores g
        JOIN jugadores j ON g.jugador_id = j.id
        WHERE g.partido_id = ?'
    );
    $stmt->execute([$partido_id]);
    return $stmt->fetchAll();
}

$fecha_actual = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['jugador_id'], $_POST['partido_id'])) {
    $jugador_id = $_POST['jugador_id'];
    $partido_id = $_POST['partido_id'];
    
    // Validación del servidor: Verificar si se ha seleccionado un jugador
    if (empty($jugador_id)) {
        echo "<script>alert('Por favor, selecciona un jugador.'); window.history.back();</script>";
        exit();
    }

    $stmt = $pdo->prepare('SELECT id FROM pichichi_seleccion WHERE partido_id = ? AND usuario_id = ?');
    $stmt->execute([$partido_id, $usuario_id]);
    $seleccion = $stmt->fetch();
    if ($seleccion) {
        $stmt = $pdo->prepare('UPDATE pichichi_seleccion SET jugador_id = ? WHERE id = ?');
        $stmt->execute([$jugador_id, $seleccion['id']]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO pichichi_seleccion (partido_id, usuario_id, jugador_id) VALUES (?, ?, ?)');
        $stmt->execute([$partido_id, $usuario_id, $jugador_id]);
    }

    header('Location: quiniela_pichichi.php');
    exit();
}

$partidosPorJornada = obtenerPartidosPorJornada($pdo);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Goleadores</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #f4f4f9; }
        .container { margin-top: 40px; }
        .btn-primary:hover, .btn-success:hover { filter: brightness(0.9); }
        .card { box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); border: 0; border-radius: 8px; }
        .card-header { background-color: #007bff; color: white; font-weight: bold; }
        .form-control { border-radius: 4px; }
        select:disabled, button:disabled { background-color: #f5f5f5; color: #aaa; }

        /* Flexbox Layout */
        .jornada-card {
            display: flex;
            flex-direction: column;
            margin-bottom: 20px;

        }

        .jornada-header {
            background-color: #b30000;
            color: white;
            font-weight: bold;
            padding: 10px;
            border-radius: 8px 8px 8px 8px;
            margin-bottom: 5px;
        }

        .partido {
            display: flex;
            flex-direction: column;
            margin-bottom: 20px;
            padding: 15px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.4);
        }

        .partido-header {
            background-color: #67737e;
            color: white;
            font-weight: bold;
            padding: 10px;
            border-radius: 8px 8px 8px 8px;
            margin-bottom: 5px;
            text-align: center; /* Centra el texto horizontalmente */
            display: flex;      /* Usa flexbox para centrar */
            justify-content: center; /* Centra horizontalmente */
            align-items: center;     /* Centra verticalmente */
        }

        .list-unstyled{
            background-color: #ccffcc;
            color: black;
            font-weight: bold;
            padding: 10px;
            border-radius: 8px 8px 8px 8px;
            text-align: center; /* Centra el texto horizontalmente */
            display: flex;      /* Usa flexbox para centrar */
            justify-content: center; /* Centra horizontalmente */
            align-items: center;     /* Centra verticalmente */
        }

        .partido-content {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 15px;
        }

        .partido-content > div {
            flex: 1;
        }

          .btn-primary, .btn-success {
                width: 100%; /* Botones al 100% de ancho en pantallas pequeñas */
                margin-top: 10px;
                margin-bottom: 10px;
            }

            .form-inline {
                flex-direction: column;
            }


            .form-inline select, .form-inline button {
                width: 100%;
            }

        /* Estilo responsivo */
        @media (max-width: 576px) {
            .btn-primary, .btn-success {
                width: 100%; /* Botones al 100% de ancho en pantallas pequeñas */
                margin-bottom: 10px;
            }

            .form-inline {
                flex-direction: column;
            }

            .form-inline select, .form-inline button {
                width: 100%;
            }
        }
    </style>
    <script>
        // Validación de JavaScript para verificar si se ha seleccionado un jugador
        $(document).ready(function () {
            $('form').on('submit', function (e) {
                var jugadorSeleccionado = $(this).find('select[name="jugador_id"]').val();
                if (!jugadorSeleccionado) {
                    alert('Por favor, selecciona un jugador antes de enviar el formulario.');
                    e.preventDefault(); // Evitar que se envíe el formulario
                }
            });
        });
    </script>
</head>
<body>
<div class="container">
    <a href="ranking.php" class="btn btn-primary mb-3">Volver al Ranking</a>
    <h1 class="text-center mb-4"></h1>
    <?php if (!empty($partidosPorJornada)): ?>
        <?php foreach ($partidosPorJornada as $jornada): ?>
            <div class="jornada-card">
                <div class="jornada-header">
                    <?php echo htmlspecialchars($jornada['jornada']['numero']); ?>
                </div>
                <?php foreach ($jornada['partidos'] as $partido): ?>
                    <?php 
                        $fecha_partido = date('Y-m-d', strtotime($partido['fecha']));
                        $disabled = ($fecha_partido <= $fecha_actual) ? 'disabled' : '';
                        $jugadores = obtenerJugadoresPorEquipo($pdo, $partido['equipo1'], $partido['equipo2']);
                        $jugador_seleccionado = obtenerSeleccionJugador($pdo, $partido['id'], $usuario_id);
                    ?>
                    <div class="partido">
                        <div class="partido-header">
                            <?php echo htmlspecialchars($partido['equipo1']) . ' vs ' . htmlspecialchars($partido['equipo2']); ?>
                            <br>
                            <?php echo date('d/m/Y', strtotime($partido['fecha'])); ?>
                        </div>
                        <div class="partido-content">
                            <div>
                                
                                <?php if ($jugador_seleccionado): ?>
                                    <div class="alert alert-info mt-2">
                                        Has elegido a <strong><?php echo htmlspecialchars($jugador_seleccionado); ?></strong> para este partido.
                                    </div>
                                <?php endif; ?>

                                <?php
                                $goleadores = obtenerGoleadoresPorPartido($pdo, $partido['id']);
                                if ($goleadores): ?>
                                    <ul class="list-unstyled mb-0">
                                        <?php foreach ($goleadores as $goleador): ?>
                                            <li><strong><?php echo htmlspecialchars($goleador['nombre']); ?></strong>: <?php echo $goleador['cantidad_goles']; ?> goles</li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <br>
                                    <ul class="list-unstyled mb-0">
                                    <li><strong>No hay goleadores aún</strong></li>
                                    </ul>
                                <?php endif; ?>
<br>
                                <form method="POST" class="form-inline">
                                    <select name="jugador_id" class="form-control mr-2" <?php echo $disabled; ?>>
                                        <option value="">Selecciona un jugador</option>
                                        <?php foreach ($jugadores as $jugador): ?>
                                            <option value="<?php echo $jugador['id']; ?>" <?php echo $jugador['id'] == $jugador_seleccionado ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($jugador['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <div class="form-group">
                                        <input type="hidden" name="partido_id" value="<?php echo $partido['id']; ?>">
                                        <button type="submit" class="btn btn-primary" <?php echo $disabled; ?>>Guardar</button>
                                    </div>

                                </form>

                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No hay partidos disponibles para mostrar.</p>
    <?php endif; ?>
</div>
</body>
</html>
