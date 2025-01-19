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
    <title>Quiniela</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #f4f4f9; }
        .container { margin-top: 20px; padding: 15px; }
        .btn { font-size: 1rem; padding: 10px 20px; }
        .table-responsive { margin-bottom: 20px; }
        .card { box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3); }
        .card-header{
            background-color: #343a40;
            color: white;
        }
        #comodinesBtn {
            position: fixed; left: 50%; top: 20px;
            transform: translateX(-50%);
            background-color: lightblue; color: black;
            padding: 10px 15px; border-radius: 7px;
            z-index: 9999; font-size: 1rem; opacity: 0.9;
        }
        #comodinesContainer {
            position: fixed; left: 50%; top: 60px;
            transform: translateX(-50%);
            background-color: white; padding: 10px;
            border-radius: 7px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            display: none; z-index: 9998; opacity: 0.9;
        }
        table th, table td { font-size: 0.9rem; }
        
        /* Responsividad para pantallas pequeñas */
        @media (max-width: 768px) {
            .btn { font-size: 0.85rem; padding: 8px 15px; }
            .card-header h2 { font-size: 1rem; }
            .table th, .table td { font-size: 0.8rem; }
            .table-responsive { overflow-x: auto; }
            .card-body { padding: 10px; }
            .modal-content { width: 100%; }
            #comodinesBtn { font-size: 0.9rem; }
        }
    </style>
</head>
<body>
<div class="container">
    <a href="ranking.php" class="btn btn-primary mb-3">Volver al Ranking</a>
    <h1 style="text-align: center;"></h1>
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
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr style="background-color: #B0E0E6;">
                                <th>Partido</th>
                                <th>Pronóstico</th>
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

                                    <td 
                                    style="
                                    text-align: center; 
                                    font-weight: bold; 
                                    color: #333333;">

                                            <?php echo $fecha_formateada; ?>
    
                                        <div>
                                            <?php 
                                            $equipo1 = htmlspecialchars($partido['equipo1']);
                                            $equipo2 = htmlspecialchars($partido['equipo2']);
                                            // Array asociativo que mapea nombres de equipos a sus rutas de icono
                                            $iconosEquipos = [
                                                'Alavés' => 'alaves.png',
                                                'Athletic' => 'athletic.png',
                                                'Atlético' => 'atletico.png',
                                                'Barcelona' => 'barcelona.png',
                                                'Betis' => 'betis.png',
                                                'Celta' => 'celta.png',
                                                'R. Sociedad' => 'erreala.png',
                                                'Espanyol' => 'espanyol.png',
                                                'Getafe' => 'getafe.png',
                                                'Girona' => 'girona.png',
                                                'Las Palmas' => 'laspalmas.png',
                                                'Leganés' => 'leganes.png',
                                                'Real Madrid' => 'madrid.png',
                                                'Mallorca' => 'mallorca.png',
                                                'Osasuna' => 'osasuna.png',
                                                'Rayo' => 'rayo.png',
                                                'Sevilla' => 'sevilla.png',
                                                'Valencia' => 'valencia.png',
                                                'Valladolid' => 'valladolid.png',
                                                'Villareal' => 'villareal.png'
                                            ];

                                            // Mostrar el icono correspondiente si el equipo existe en el array
                                            if (array_key_exists($equipo1, $iconosEquipos)) {
                                                echo '<img src="iconos/' . $iconosEquipos[$equipo1] . '" alt="' . $equipo1 . '" width="30" height="30" style="margin-right: 0px; margin-top: 5px;">';
                                            }
                                            ?>
                                            <br>
                                            <strong><?php echo $equipo1; ?></strong>

                                        </div>
                                            <?php echo '-' ?>
                                        <div>

                                            <strong><?php echo $equipo2; ?></strong>
                                            <br>
                                              <!-- Mostrar icono del segundo equipo a la derecha -->
                                            <?php 
                                            // Mostrar el icono correspondiente si el equipo existe en el array
                                            if (array_key_exists($equipo2, $iconosEquipos)) {
                                                echo '<img src="iconos/' . $iconosEquipos[$equipo2] . '" alt="' . $equipo2 . '" width="30" height="30" style="margin-left: 0px;">';
                                            }
                                            ?>

                                        </div>  
                                    </td>

                                    <td style="
                                    text-align: center; 
                                    vertical-align: middle; 
                                    font-weight: bold;
                                    font-size: 1.5em;">

                                    <!-- RESULTADO SELECCIONADO-->
                                        <?php echo $partido['resultado_seleccionado'] ? htmlspecialchars($partido['resultado_seleccionado']) : ''; ?>
                                    </td>

                                    <td style="text-align: center; vertical-align: middle;">
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
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal -->
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

</body>
</html>
