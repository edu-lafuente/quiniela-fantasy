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
    list($goles1, $goles2) = explode('-', $resultado);
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
        echo "<div class='table-responsive'>
                <table class='table table-bordered table-striped'>";

        echo "<thead class='table-dark'>
                <tr>
                    <th>En casa</th>
                    <th>Fuera</th>
                    <th>Resultado Seleccionado</th>
                    <th>Resultado Real</th>
                    <th>Goleador</th> <!-- Nueva columna -->
                </tr>
              </thead>
              <tbody>";

        foreach ($selecciones as $seleccion) {
            $puntos = 0;
            $class = 'table-danger'; // Por defecto, si no se acierta, será en rojo claro

            // Verificar si el jugador ha seleccionado un resultado
            $resultado_seleccionado = $seleccion['resultado_seleccionado'] ? htmlspecialchars($seleccion['resultado_seleccionado']) : 'No seleccionado';
            
            // Verificar si el resultado real existe
            $resultado_real = isset($seleccion['resultado']) && $seleccion['resultado'] !== null ? htmlspecialchars($seleccion['resultado']) : 'Sin Resultado';

            // Calcular puntos solo si hay un resultado seleccionado
            if ($seleccion['resultado_seleccionado']) {
                $puntos = calcularPuntos($seleccion['resultado_seleccionado'], $seleccion['resultado']);
                if ($puntos == 3) {
                    $class = 'table-success'; // Resultado exacto
                } elseif ($puntos == 1) {
                    $class = 'table-warning'; // Acertó el ganador
                }
            }

            // Obtener el goleador seleccionado por el usuario
            $stmt_goleador = $pdo->prepare('
                SELECT j.nombre 
                FROM pichichi_seleccion ps 
                JOIN jugadores j ON ps.jugador_id = j.id
                WHERE ps.partido_id = ? AND ps.usuario_id = ?
            ');
            $stmt_goleador->execute([$seleccion['partido_id'], $participante_id]);
            $goleador_seleccionado = $stmt_goleador->fetchColumn();  // Devuelve el nombre del goleador seleccionado

            // Obtener el goleador real
            $stmt_goleador_real = $pdo->prepare('
                SELECT j.nombre
                FROM goleadores g
                JOIN jugadores j ON g.jugador_id = j.id
                WHERE g.partido_id = ? 
                ORDER BY g.cantidad_goles DESC LIMIT 1
            ');
            $stmt_goleador_real->execute([$seleccion['partido_id']]);
            $goleador_real = $stmt_goleador_real->fetchColumn(); // Devuelve el nombre del goleador real

            // Verificar si el goleador seleccionado coincide con el real
            $goleador_class = 'bg-secondary text-white'; // Por defecto, fondo gris
            if ($goleador_seleccionado && $goleador_seleccionado === $goleador_real) {
                $goleador_class = 'bg-success text-white'; // Fondo verde si es correcto
            }

            // Mostrar fila de partido con los resultados correspondientes
            echo "<tr class='$class'>
                    <td>" . htmlspecialchars($seleccion['equipo1']) . "</td>
                    <td>" . htmlspecialchars($seleccion['equipo2']) . "</td>
                    <td>$resultado_seleccionado</td>
                    <td>$resultado_real</td>
                    <td class='$goleador_class'>" . ($goleador_seleccionado ? htmlspecialchars($goleador_seleccionado) : 'No seleccionado') . "</td> <!-- Goleador -->
                  </tr>";
        }

        echo "</tbody></table></div>";
    } else {
        echo "<p class='text-muted'>No hay selecciones para esta jornada.</p>";
    }
}
?>