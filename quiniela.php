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

function obtenerPartidosPorJornada($pdo, $participante_id) {
    // Obtener las jornadas
    $jornadas = $pdo->query('SELECT id, numero FROM jornadas ORDER BY id')->fetchAll();

    $partidosPorJornada = [];

    foreach ($jornadas as $jornada) {
        // Obtener los partidos de cada jornada
        $stmt = $pdo->prepare(
            'SELECT p.id, p.equipo1, p.equipo2, p.resultado, p.fecha, s.resultado_seleccionado 
            FROM partidos p
            LEFT JOIN selecciones s ON p.id = s.partido_id AND s.participante_id = ? 
            WHERE p.jornada_id = ?'
        );
        $stmt->execute([$participante_id, $jornada['id']]);
        $partidos = $stmt->fetchAll();

        // Solo añadir la jornada si hay partidos registrados
        if (!empty($partidos)) {
            $partidosPorJornada[] = [
                'jornada' => $jornada,
                'partidos' => $partidos
            ];
        }
    }

    return $partidosPorJornada;
}

// Obtener los comodines del usuario
$stmt = $pdo->prepare("SELECT comodin_x2, comodin_x3 FROM participantes WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$comodines = $stmt->fetch();

// Los valores de los comodines
$comodin_x2 = $comodines['comodin_x2'];
$comodin_x3 = $comodines['comodin_x3'];

$partidosPorJornada = obtenerPartidosPorJornada($pdo, $usuario_id);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Quiniela</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #f4f4f9; }
        .container { margin-top: 40px; }
        .btn-primary:hover, .btn-success:hover { filter: brightness(0.9); }
        .table th, .table td { vertical-align: middle; }
        .card { box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }

        /* Estilo del botón flotante centrado arriba */
        #comodinesBtn {
            position: fixed;
            left: 50%;
            top: 20px;  /* Ajuste para que esté cerca de la parte superior */
            transform: translateX(-50%);
            background-color: lightblue;
            color: black;
            padding: 15px;
            border-radius: 7px;
            font-size: 18px;
            z-index: 9999;
            opacity: 0.9;
        }
        #comodinesBtn:hover {
            background-color: lightseagreen;
            opacity: 0.9;
        }

        /* Estilo del contenedor de comodines centrado arriba */
        #comodinesContainer {
            position: fixed;
            left: 50%;
            top: 80px; /* Justo debajo del botón */
            transform: translateX(-50%);
            background-color: white;
            padding: 10px;
            border-radius: 7px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            display: none;
            z-index: 9998;
            opacity: 0.9;
        }

        #comodinesContainer h4 {
            margin-bottom: 15px;

        }

        .comodin-item {
            margin-bottom: 10px;

        }

    </style>
</head>
<body>
<div class="container">
    <a href="ranking.php" class="btn btn-primary mb-3">Volver al Ranking</a>
    <h1 style="text-align: center;">Mi Quiniela</h1>
    <?php if (empty($partidosPorJornada)): ?>
        <div class="alert alert-warning" role="alert">
            No hay partidos registrados para las jornadas actuales.
        </div>
    <?php endif; ?>
    <?php foreach ($partidosPorJornada as $jornada): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h2><?php echo htmlspecialchars($jornada['jornada']['numero']); ?></h2>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr style="background-color: #B0E0E6;">
                            <th>Partido</th>
                            <th>Fecha</th>
                            <th>Resultado Seleccionado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jornada['partidos'] as $partido): ?>
                            <?php 
                                // Obtener la fecha del partido
                                $fecha_partido = $partido['fecha'];
                                // Calcular el día anterior a la fecha del partido
                                $fecha_maxima = date('Y-m-d', strtotime($fecha_partido . ' -1 day'));
                                // Verificar si la fecha actual es antes de la fecha máxima permitida
                                $permitir_resultado = (date('Y-m-d') <= $fecha_maxima);
                                // Formatear la fecha del partido
                                $fecha_formateada = date('d/m/Y', strtotime($fecha_partido));
                            ?>

<tr>
    <td style="text-align: center;">
        <!-- Mostrar icono del primer equipo a la izquierda -->
        <?php 
            $equipo1 = htmlspecialchars($partido['equipo1']);
            if ($equipo1 == 'Alavés') {
                echo '<img src="iconos/alaves.png" alt="Alavés" width="30" height="30" style="margin-right: 5px;">';
            } elseif ($equipo1 == 'Athletic') {
                echo '<img src="iconos/athletic.png" alt="Athletic" width="30" height="30" style="margin-right: 5px;">';
            } elseif ($equipo1 == 'Atlético') {
                echo '<img src="iconos/atletico.png" alt="Atlético" width="30" height="30" style="margin-right: 5px;">';
            } elseif ($equipo1 == 'Barcelona') {
                echo '<img src="iconos/barcelona.png" alt="Barcelona" width="30" height="30" style="margin-right: 5px;">';
            } elseif ($equipo1 == 'Betis') {
                echo '<img src="iconos/betis.png" alt="Betis" width="30" height="30" style="margin-right: 5px;">';
            } elseif ($equipo1 == 'Celta') {
                echo '<img src="iconos/celta.png" alt="Celta" width="30" height="30" style="margin-right: 5px;">';
            } elseif ($equipo1 == 'R. Sociedad') {
                echo '<img src="iconos/erreala.png" alt="R. Sociedad" width="30" height="30" style="margin-right: 5px;">';
            } elseif ($equipo1 == 'Espanyol') {
                echo '<img src="iconos/espanyol.png" alt="Espanyol" width="30" height="30" style="margin-right: 5px;">';
            } elseif ($equipo1 == 'Getafe') {
                echo '<img src="iconos/getafe.png" alt="Getafe" width="30" height="30" style="margin-right: 5px;">';
            } elseif ($equipo1 == 'Girona') {
                echo '<img src="iconos/girona.png" alt="Girona" width="30" height="30" style="margin-right: 5px;">';
            } elseif ($equipo1 == 'Las Palmas') {
                echo '<img src="iconos/laspalmas.png" alt="Las Palmas" width="30" height="30" style="margin-right: 5px;">';
            } elseif ($equipo1 == 'Leganés') {
                echo '<img src="iconos/leganes.png" alt="Leganés" width="30" height="30" style="margin-right: 5px;">';
            } elseif ($equipo1 == 'Real Madrid') {
                echo '<img src="iconos/madrid.png" alt="Real Madrid" width="30" height="30" style="margin-right: 5px;">';
            } elseif ($equipo1 == 'Mallorca') {
                echo '<img src="iconos/mallorca.png" alt="Mallorca" width="30" height="30" style="margin-right: 5px;">';
            } elseif ($equipo1 == 'Osasuna') {
                echo '<img src="iconos/osasuna.png" alt="Osasuna" width="30" height="30" style="margin-right: 5px;">';
            } elseif ($equipo1 == 'Rayo') {
                echo '<img src="iconos/rayo.png" alt="Rayo" width="30" height="30" style="margin-right: 5px;">';
            } elseif ($equipo1 == 'Sevilla') {
                echo '<img src="iconos/sevilla.png" alt="Sevilla" width="30" height="30" style="margin-right: 5px;">';
            } elseif ($equipo1 == 'Valencia') {
                echo '<img src="iconos/valencia.png" alt="Valencia" width="30" height="30" style="margin-right: 5px;">';
            } elseif ($equipo1 == 'Valladolid') {
                echo '<img src="iconos/valladolid.png" alt="Valladolid" width="30" height="30" style="margin-right: 5px;">';
            }elseif ($equipo1 == 'Villareal') {
                echo '<img src="iconos/villareal.png" alt="Valladolid" width="30" height="30" style="margin-right: 5px;">';
            }
        ?>
        
        <strong><?php echo $equipo1; ?></strong>

        <!-- Icono y nombre del segundo equipo -->
        <strong style="color: #483D8B;"> <?php echo '-'; ?> </strong>
        <strong>
            <?php 
                $equipo2 = htmlspecialchars($partido['equipo2']);
                echo $equipo2;
            ?>
        </strong>
        <!-- Mostrar icono del segundo equipo a la derecha -->
        <?php 
            if ($equipo2 == 'Alavés') {
                echo '<img src="iconos/alaves.png" alt="Alavés" width="30" height="30" style="margin-left: 5px;">';
            } elseif ($equipo2 == 'Athletic') {
                echo '<img src="iconos/athletic.png" alt="Athletic" width="30" height="30" style="margin-left: 5px;">';
            } elseif ($equipo2 == 'Atlético') {
                echo '<img src="iconos/atletico.png" alt="Atlético" width="30" height="30" style="margin-left: 5px;">';
            } elseif ($equipo2 == 'Barcelona') {
                echo '<img src="iconos/barcelona.png" alt="Barcelona" width="30" height="30" style="margin-left: 5px;">';
            } elseif ($equipo2 == 'Betis') {
                echo '<img src="iconos/betis.png" alt="Betis" width="30" height="30" style="margin-left: 5px;">';
            } elseif ($equipo2 == 'Celta') {
                echo '<img src="iconos/celta.png" alt="Celta" width="30" height="30" style="margin-left: 5px;">';
            } elseif ($equipo2 == 'R. Sociedad') {
                echo '<img src="iconos/erreala.png" alt="R. Sociedad" width="30" height="30" style="margin-left: 5px;">';
            } elseif ($equipo2 == 'Espanyol') {
                echo '<img src="iconos/espanyol.png" alt="Espanyol" width="30" height="30" style="margin-left: 5px;">';
            } elseif ($equipo2 == 'Getafe') {
                echo '<img src="iconos/getafe.png" alt="Getafe" width="30" height="30" style="margin-left: 5px;">';
            } elseif ($equipo2 == 'Girona') {
                echo '<img src="iconos/girona.png" alt="Girona" width="30" height="30" style="margin-left: 5px;">';
            } elseif ($equipo2 == 'Las Palmas') {
                echo '<img src="iconos/laspalmas.png" alt="Las Palmas" width="30" height="30" style="margin-left: 5px;">';
            } elseif ($equipo2 == 'Leganés') {
                echo '<img src="iconos/leganes.png" alt="Leganés" width="30" height="30" style="margin-left: 5px;">';
            } elseif ($equipo2 == 'Real Madrid') {
                echo '<img src="iconos/madrid.png" alt="Real Madrid" width="30" height="30" style="margin-left: 5px;">';
            } elseif ($equipo2 == 'Mallorca') {
                echo '<img src="iconos/mallorca.png" alt="Mallorca" width="30" height="30" style="margin-left: 5px;">';
            } elseif ($equipo2 == 'Osasuna') {
                echo '<img src="iconos/osasuna.png" alt="Osasuna" width="30" height="30" style="margin-left: 5px;">';
            } elseif ($equipo2 == 'Rayo') {
                echo '<img src="iconos/rayo.png" alt="Rayo" width="30" height="30" style="margin-left: 5px;">';
            } elseif ($equipo2 == 'Sevilla') {
                echo '<img src="iconos/sevilla.png" alt="Sevilla" width="30" height="30" style="margin-left: 5px;">';
            } elseif ($equipo2 == 'Valencia') {
                echo '<img src="iconos/valencia.png" alt="Valencia" width="30" height="30" style="margin-left: 5px;">';
            } elseif ($equipo2 == 'Valladolid') {
                echo '<img src="iconos/valladolid.png" alt="Valladolid" width="30" height="30" style="margin-left: 5px;">';
            } elseif ($equipo2 == 'Villareal') {
                echo '<img src="iconos/villareal.png" alt="Valladolid" width="30" height="30" style="margin-left: 5px;">';
            }
        ?>
    </td>
    <td style="text-align: center;"><?php echo $fecha_formateada; ?></td>
    <td>
        <?php echo $partido['resultado_seleccionado'] ? htmlspecialchars($partido['resultado_seleccionado']) : 'No introducido'; ?>
    </td>
    <td>
        <?php if ($partido['resultado']): ?>
            <!-- Si existe un resultado real, deshabilitar el botón -->
            <button class="btn btn-secondary" disabled>Resultado Final: <?php echo htmlspecialchars($partido['resultado']); ?></button>
        <?php elseif ($permitir_resultado && $partido['resultado_seleccionado'] == null): ?>
            <!-- Si no hay resultado real ni seleccionado y la fecha permite introducirlo, habilitar el botón -->
            <button class="btn btn-success" data-toggle="modal" data-target="#resultadoModal" data-partido-id="<?php echo $partido['id']; ?>" data-equipo1="<?php echo htmlspecialchars($partido['equipo1']); ?>" data-equipo2="<?php echo htmlspecialchars($partido['equipo2']); ?>">Introducir Resultado</button>
        <?php elseif (!$permitir_resultado): ?>
            <!-- Si no se puede introducir el resultado porque la fecha es posterior -->
            <button class="btn btn-secondary" disabled>Fuera de plazo</button>
        <?php else: ?>
            <!-- Si hay un resultado seleccionado, deshabilitar el botón -->
            <button class="btn btn-secondary" disabled>Resultado Seleccionado: <?php echo htmlspecialchars($partido['resultado_seleccionado']); ?></button>
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

<div class="modal fade" id="resultadoModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Introducir Resultado</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="resultadoForm">
                <div class="modal-body">
                    <label id="equipo1Label"></label>
                    <input type="number" id="golesEquipo1" class="form-control" min="0" required>
                    <label id="equipo2Label"></label>
                    <input type="number" id="golesEquipo2" class="form-control" min="0" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar Resultado</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#resultadoModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var partidoId = button.data('partido-id');
        var equipo1 = button.data('equipo1');
        var equipo2 = button.data('equipo2');

        $('#equipo1Label').text(equipo1);
        $('#equipo2Label').text(equipo2);
        $('#resultadoForm').attr('data-partido-id', partidoId);
    });

    $('#resultadoForm').submit(function(e) {
        e.preventDefault();
        var partidoId = $(this).attr('data-partido-id');
        var golesEquipo1 = $('#golesEquipo1').val();
        var golesEquipo2 = $('#golesEquipo2').val();

        if (golesEquipo1 === '' || golesEquipo2 === '') {
            alert('Introduce los goles para ambos equipos.');
            return;
        }

        $.post('guardar_resultado.php', {
            partido_id: partidoId,
            goles_equipo1: golesEquipo1,
            goles_equipo2: golesEquipo2
        }, function() {
            $('#resultadoModal').modal('hide');
            location.reload();
        }).fail(function() {
            alert('Error al guardar el resultado.');
        });
    });
});
</script>

<!--
 <script>
        function toggleComodinesContainer() {
            var container = document.getElementById("comodinesContainer");
            if (container.style.display === "none" || container.style.display === "") {
                container.style.display = "block";
            } else {
                container.style.display = "none";
            }
        }
</script>

    <!-- Botón flotante de comodines
    <button id="comodinesBtn" onclick="toggleComodinesContainer()">Comodines</button>

    <!-- Contenedor de comodines
    <div id="comodinesContainer">
        <h4>Comodines Disponibles</h4>
        <div class="comodin-item">x2: <?php echo $comodin_x2 > 0 ? $comodin_x2 : 'No disponible'; ?></div>
        <div class="comodin-item">x3: <?php echo $comodin_x3 > 0 ? $comodin_x3 : 'No disponible'; ?></div>
    </div>
-->

</body>
</html>
