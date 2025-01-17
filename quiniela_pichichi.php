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
    $jornadas = $pdo->query('SELECT id, numero FROM jornadas ORDER BY id')->fetchAll();
    $partidosPorJornada = [];
    foreach ($jornadas as $jornada) {
        $stmt = $pdo->prepare('SELECT p.id, p.equipo1, p.equipo2, p.fecha FROM partidos p WHERE p.jornada_id = ?');
        $stmt->execute([$jornada['id']]);
        $partidos = $stmt->fetchAll();
        if (!empty($partidos)) {
            $partidosPorJornada[] = ['jornada' => $jornada, 'partidos' => $partidos];
        }
    }
    return $partidosPorJornada;
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
    <title>Mi Quiniela de Goleadores</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #f4f4f9; }
        .container { margin-top: 40px; }
        .btn-primary:hover, .btn-success:hover { filter: brightness(0.9); }
        .table th, .table td { vertical-align: middle; text-align: center; }
        .card { box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); border: 0; border-radius: 8px; }
        .card-header { background-color: #007bff; color: white; font-weight: bold; }
        .form-control { border-radius: 4px; }
        select:disabled, button:disabled { background-color: #f5f5f5; color: #aaa; }
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
    <h1 class="text-center mb-4">Selecciona Goleadores</h1>
    <?php foreach ($partidosPorJornada as $jornada): ?>
        <div class="card mb-4">
            <div class="card-header">
                <?php echo htmlspecialchars($jornada['jornada']['numero']); ?>
            </div>
            <div class="card-body">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Partido</th>
                            <th>Fecha</th>
                            <th>Jugador</th>
                            <th>Goleadores</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jornada['partidos'] as $partido): ?>
                            <?php 
                                $fecha_partido = date('Y-m-d', strtotime($partido['fecha']));
                                $disabled = ($fecha_partido <= $fecha_actual) ? 'disabled' : '';
                                $jugadores = obtenerJugadoresPorEquipo($pdo, $partido['equipo1'], $partido['equipo2']);
                                $jugador_seleccionado = obtenerSeleccionJugador($pdo, $partido['id'], $usuario_id);
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($partido['equipo1']) . ' vs ' . htmlspecialchars($partido['equipo2']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($partido['fecha'])); ?></td>
                                <td>
                                    <?php if ($jugador_seleccionado): ?>
                                        <form method="POST" class="form-inline">
                                            <select name="jugador_id" class="form-control mr-2" <?php echo $disabled; ?>>
                                                <option value="">Selecciona un jugador</option>
                                                <?php foreach ($jugadores as $jugador): ?>
                                                    <option value="<?php echo $jugador['id']; ?>" <?php echo $jugador['id'] == $jugador_seleccionado ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($jugador['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="partido_id" value="<?php echo $partido['id']; ?>">
                                            <button type="submit" class="btn btn-primary" <?php echo $disabled; ?>>Guardar</button>
                                        </form>
                                        <div class="alert alert-info mt-2">
                                            Has elegido a <strong><?php echo htmlspecialchars($jugador_seleccionado); ?></strong> para este partido.
                                        </div>
                                    <?php else: ?>
                                        <form method="POST" class="form-inline">
                                            <select name="jugador_id" class="form-control mr-2" <?php echo $disabled; ?>>
                                                <option value="">Selecciona un jugador</option>
                                                <?php foreach ($jugadores as $jugador): ?>
                                                    <option value="<?php echo $jugador['id']; ?>">
                                                        <?php echo htmlspecialchars($jugador['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="partido_id" value="<?php echo $partido['id']; ?>">
                                            <button type="submit" class="btn btn-primary" <?php echo $disabled; ?>>Guardar</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                               <td>
                                <?php
                                $goleadores = obtenerGoleadoresPorPartido($pdo, $partido['id']);
                                if ($goleadores): ?>
                                    <ul class="list-unstyled mb-0">
                                        <?php foreach ($goleadores as $goleador): ?>
                                            <li><strong><?php echo htmlspecialchars($goleador['nombre']); ?></strong>: <?php echo $goleador['cantidad_goles']; ?> goles</li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p>No hay goleadores aún.</p>
                                <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>
