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

if (isset($_GET['participante_id'])) {
    $participante_id = (int) $_GET['participante_id'];
} else {
    $participante_id = null;
}

function calcularPuntos($seleccion, $resultado) {
    if ($seleccion === $resultado) {
        return 3; // Acierta resultado exacto
    }
    $selec_ganador = obtenerGanador($seleccion);
    $real_ganador = obtenerGanador($resultado);
    return $selec_ganador === $real_ganador ? 1 : 0; // Acierta el ganador
}

function obtenerGanador($resultado) {
    $goles = explode('-', $resultado);
    if (count($goles) !== 2 || !is_numeric($goles[0]) || !is_numeric($goles[1])) {
        return 'indefinido'; // Evita errores si el formato es incorrecto
    }
    list($goles1, $goles2) = $goles;
    if ($goles1 > $goles2) {
        return 'equipo1';
    } elseif ($goles2 > $goles1) {
        return 'equipo2';
    }
    return 'empate';
}


function obtenerPuntosTotales($pdo, $participante_id) {
    $puntos = 0;
    $stmtSelecciones = $pdo->prepare(
        'SELECT s.resultado_seleccionado, pt.resultado FROM selecciones s 
        JOIN partidos pt ON s.partido_id = pt.id 
        WHERE s.participante_id = ?'
    );
    $stmtSelecciones->execute([$participante_id]);
    
    foreach ($stmtSelecciones->fetchAll() as $seleccion) {
        $puntos += calcularPuntos($seleccion['resultado_seleccionado'], $seleccion['resultado']);
    }
    
    return $puntos;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking y Selecciones</title>
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
            font-size: 1.5rem;
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .btn-secondary span {
            position: absolute;
            left: 125%;
            display: flex;
            align-items: center;

        }
        .btn-outline-warning{
          color: black !important;
          font-weight: bold !important;
        }
        .btn-secondary span img {
            width: 30px;
            height: 30px;
        }
        .card-header {
            background-color: #343a40;
            font-weight: bold;
        }
         /* Ajustar botones para pantallas pequeñas */
        .btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .btn-group .btn {
            width: auto;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    
<script>
$(document).ready(function() {
    <?php if (isset($participante_id) && $_SESSION['usuario_id'] == $participante_id): ?>
        $('#seleccionesTitulo').addClass('text-success');
        $('#botonQuiniela').show();
    <?php endif; ?>
});
</script>

<?php
if (isset($_GET['participante_id'])) {
    $stmt = $pdo->prepare('SELECT nombre, dinero, comodin_x2, comodin_x3 FROM participantes WHERE id = ?');
    $stmt->execute([$participante_id]);
    $participante = $stmt->fetch();

    if ($participante) {
        $dinero = obtenerPuntosTotales($pdo, $participante_id);
        $cantidadX2 = $participante['comodin_x2'];
        $cantidadX3 = $participante['comodin_x3'];

        // Mostrar el botón solo si el participante es el mismo que el usuario actual
        if ($_SESSION['usuario_id'] == $participante_id) {
            echo "<div class='container d-flex justify-content-between align-items-center flex-column flex-sm-row'>
                    <div class='btn-group'>
                        <a href='ranking.php' class='btn btn-primary mb-3'>Volver al Ranking</a>
                        <a href='quiniela.php' class='btn btn-secondary mb-3 ml-2'>Quiniela</a>
                        <a href='quiniela_pichichi.php' class='btn btn-secondary mb-3 ml-2'>Goleadores</a>
                    </div>
                </div>";
            } else {
                echo "<div class='container d-flex justify-content-between align-items-center'>
                        <div>
                            <a href='ranking.php' class='btn btn-primary mb-3'>Volver al Ranking</a>
                        </div>
                    </div>";
            }

        echo "<div class='container'>
                <div class='card'>
                    <div class='card-header text-primary'>
                        <h1 id='seleccionesTitulo'>Selecciones de " . htmlspecialchars($participante['nombre']) . "</h1>
                    </div>
                    <div class='card-body'>
                        <div class='form-group'>
                            <label for='selectJornada'>Seleccionar Jornada</label>
                            <select class='form-control' id='selectJornada'>
                                <option value=''>Selecciona una jornada</option>";
        $jornadas = $pdo->query('SELECT id, numero FROM jornadas ORDER BY id')->fetchAll();
        foreach ($jornadas as $jornada) {
            echo "<option value='" . $jornada['id'] . "'>" . htmlspecialchars($jornada['numero']) . "</option>";
        }
        echo "</select>
                        </div>
                        <div id='partidosJornada'></div>
                    </div>
                </div>
              </div>";
    } else {
        echo "<div class='alert alert-warning'>Participante no encontrado.</div>";
    }
} else {
    $stmt = $pdo->query('SELECT p.id, p.nombre FROM participantes p');

    $ranking = [];
    while ($row = $stmt->fetch()) {
        $puntos = 0;
        $stmtSelecciones = $pdo->prepare(
            'SELECT s.resultado_seleccionado, pt.resultado FROM selecciones s 
            JOIN partidos pt ON s.partido_id = pt.id 
            WHERE s.participante_id = ?'
        );
        $stmtSelecciones->execute([$row['id']]);
        foreach ($stmtSelecciones->fetchAll() as $seleccion) {
            $puntos += calcularPuntos($seleccion['resultado_seleccionado'], $seleccion['resultado']);
        }
        $ranking[] = ['nombre' => $row['nombre'], 'id' => $row['id'], 'puntos' => $puntos];
    }

    usort($ranking, function ($a, $b) {
        return $b['puntos'] - $a['puntos'];
    });

    echo "<div class='container'>
            <div class='card'>
                <div class='card-header text-primary'>
                    <h1>Ranking de Participantes</h1>
                </div>
                <div class='card-body'>
                    <div class='table-responsive'>
                        <table class='table table-bordered table-striped'>
                            <thead class='table-dark'>
                                <tr><th>Nombre</th><th>Puntos</th></tr>
                            </thead>
                            <tbody>";
    foreach ($ranking as $participante) {
        echo "<tr>
                <td><a class='text-decoration-none' href='?participante_id=" . $participante['id'] . "'>" . htmlspecialchars($participante['nombre']) . "</a></td>
                <td>" . $participante['puntos'] . "</td>
              </tr>";
    }
    echo "</tbody></table></div></div></div></div>";
}
?>

<script>
    $('#selectJornada').change(function() {
        var jornada_id = $(this).val();
        if (jornada_id) {
            $.ajax({
                url: 'get_partidos.php',
                type: 'GET',
                data: { jornada_id: jornada_id, participante_id: <?php echo $participante_id; ?> },
                success: function(data) {
                    $('#partidosJornada').html(data);
                }
            });
        } else {
            $('#partidosJornada').html('');
        }
    });
</script>

</body>
</html>
