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

// Función para calcular los puntos
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



// Verificar que se haya pasado el ID de la jornada
if (isset($_GET['jornada_id']) && isset($_GET['participante_id'])) {
    $jornada_id = (int) $_GET['jornada_id'];
    $participante_id = (int) $_GET['participante_id'];

    // Obtener los partidos de la jornada seleccionada
    $stmt = $pdo->prepare(
        'SELECT p.id AS partido_id, p.equipo1, p.equipo2, s.resultado_seleccionado, p.resultado
         FROM partidos p
         LEFT JOIN selecciones s ON p.id = s.partido_id AND s.participante_id = ? 
         WHERE p.jornada_id = ?'
    );
    $stmt->execute([$participante_id, $jornada_id]);
    $selecciones = $stmt->fetchAll();

    // Mostrar los partidos de la jornada seleccionada
    if ($selecciones) {
        echo "<div class='container'>
                <div class='row'>";

        // Agregar el bloque de estilos dentro del archivo PHP
        echo "<style>
            .bg-light-red {
                 background-color: #ffcccc;
                 color: black;
                 border: 1px solid #343a40;
            }

            .bg-light-green {
                background-color: #d7ffcc; /* Verde claro */
                color: black;
                border: 1px solid #343a40;
            }

            .bg-light-green2 {
                background-color: #ffe8cc; /* Verde claro */
                color: black;
                border: 1px solid #343a40;
            }
            .card-title{
                background-color: #343a40;
                color: white;
                font-weight: bold;
                padding: 5px;
                border-radius: 3px 3px 3px 3px;
                text-align: center; /* Centra el texto horizontalmente */
                display: flex;      /* Usa flexbox para centrar */
                justify-content: center; /* Centra horizontalmente */
                align-items: center;     /* Centra verticalmente */
            }
            .card-body{
                margin-top: 0px;
            }
            .mb-3{
                text-align: center;
                justify-content: center; /* Centra horizontalmente */
                align-items: center;     /* Centra verticalmente */
            }

            .bg-secondary {
                background-color: #6c757d;
                color: white;
            }
            </style>";

        foreach ($selecciones as $seleccion) {
            $puntos = 0;
            $class = 'bg-light-red'; // Rojo claro por defecto si el resultado es incorrecto

            // Verificar si el jugador ha seleccionado un resultado
            $resultado_seleccionado = $seleccion['resultado_seleccionado'] ? htmlspecialchars($seleccion['resultado_seleccionado']) : 'No seleccionado';
            
            // Verificar si el resultado real existe
            $resultado_real = isset($seleccion['resultado']) && $seleccion['resultado'] !== null ? htmlspecialchars($seleccion['resultado']) : 'Sin Resultado';

            // Calcular puntos solo si hay un resultado seleccionado
            if ($seleccion['resultado_seleccionado']) {
                $puntos = calcularPuntos($seleccion['resultado_seleccionado'], $seleccion['resultado']);
                if ($puntos == 3) {
                    $class = 'bg-light-green'; // Verde claro si acertó resultado exacto
                } elseif ($puntos == 1) {
                    $class = 'bg-light-green2'; // Amarillo claro si acertó el ganador
                }
            }

            // Obtener el goleador seleccionado por el usuario
            $stmt_goleador = $pdo->prepare('
                SELECT j.nombre 
                FROM pichichi_seleccion ps 
                JOIN jugadores j ON ps.jugador_id = j.id
                WHERE ps.partido_id = ? AND ps.usuario_id = ?'
            );
            $stmt_goleador->execute([$seleccion['partido_id'], $participante_id]);
            $goleador_seleccionado = $stmt_goleador->fetchColumn();  // Devuelve el nombre del goleador seleccionado

            // Obtener el goleador real
            $stmt_goleador_real = $pdo->prepare('
                SELECT j.nombre
                FROM goleadores g
                JOIN jugadores j ON g.jugador_id = j.id
                WHERE g.partido_id = ? 
                ORDER BY g.cantidad_goles DESC LIMIT 1'
            );
            $stmt_goleador_real->execute([$seleccion['partido_id']]);
            $goleador_real = $stmt_goleador_real->fetchColumn(); // Devuelve el nombre del goleador real

            // Verificar si el goleador seleccionado coincide con el real
            $goleador_class = 'bg-secondary text-white'; // Por defecto, fondo gris
            if ($goleador_seleccionado && $goleador_seleccionado === $goleador_real) {
                $goleador_class = 'bg-success text-white'; // Fondo verde si es correcto
            }

            // Mostrar partido en formato de tarjeta con secciones claras
            echo "<div class='col-12 col-md-6 col-lg-4 mb-4'>
                    <div class='card $class'>
                        <div class='card-body'>
                            <h5 class='card-title text-black'>" . htmlspecialchars($seleccion['equipo1']) . " vs " . htmlspecialchars($seleccion['equipo2']) . "</h5>
                            
                            <div class='mb-3'>
                                <h6 class='card-subtitle mb-2 text-dark'>Resultado Seleccionado:</h6>
                                <p class='font-weight-bold'>$resultado_seleccionado</p>
                            </div>

                            <!-- Línea blanca después de Resultado Seleccionado -->
                            <div style='border-top: 1px solid white; margin: 20px 0;'></div>

                            <div class='mb-3'>
                                <h6 class='card-subtitle mb-2 text-dark'>Resultado Real:</h6>
                                <p class='font-weight-bold'>$resultado_real</p>
                            </div>

                            <!-- Línea blanca después de Resultado Real -->
                            <div style='border-top: 1px solid white; margin: 20px 0;'></div>

                            <div class='mb-3'>
                                <h6 class='card-subtitle mb-2 text-dark'>Goleador Seleccionado:</h6>
                                <p class='$goleador_class' style='padding: 5px; border-radius: 5px;'>" . ($goleador_seleccionado ? htmlspecialchars($goleador_seleccionado) : 'No seleccionado') . "</p>
                            </div>
                        </div>
                    </div>
                </div>";

        }

        echo "</div></div>";
    } else {
        echo "<p class='text-muted'>No hay selecciones para esta jornada.</p>";
    }
}
?>
