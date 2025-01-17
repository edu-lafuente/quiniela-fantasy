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

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario_valido']) || $_SESSION['usuario_valido'] !== true) {
    header('Location: controlPanelLogin.php');
    exit();
}

// Función para obtener todas las jornadas disponibles
function obtenerJornadas($pdo) {
    $stmt = $pdo->query("SELECT * FROM jornadas ORDER BY id");
    return $stmt->fetchAll();
}

// Si el formulario es enviado, insertar o actualizar el partido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jornada_id = $_POST['jornada_id'];
    $equipo1 = $_POST['equipo1'];
    $equipo2 = $_POST['equipo2'];
    $fecha = $_POST['fecha'];

    if (isset($_POST['id_partido'])) {
        // Si existe el campo id_partido, estamos actualizando un partido
        $id_partido = $_POST['id_partido'];
        $stmt = $pdo->prepare("UPDATE partidos SET jornada_id = ?, equipo1 = ?, equipo2 = ?, fecha = ?, resultado = ? WHERE id = ?");
        $stmt->execute([$jornada_id, $equipo1, $equipo2, $fecha, $_POST['resultado'], $id_partido]);
    } else {
        // Si no existe id_partido, estamos insertando un nuevo partido
        $stmt = $pdo->prepare("INSERT INTO partidos (jornada_id, equipo1, equipo2, fecha) VALUES (?, ?, ?, ?)");
        $stmt->execute([$jornada_id, $equipo1, $equipo2, $fecha]);
    }

    // Redirigir para evitar resubmit de formulario
    header('Location: controlPanel.php');
    exit();
}

// Obtener las jornadas disponibles
$jornadas = obtenerJornadas($pdo);

// Verificar si un partido está siendo editado
$partido_a_editar = null;
if (isset($_GET['editar_partido'])) {
    $stmt = $pdo->prepare("SELECT * FROM partidos WHERE id = ?");
    $stmt->execute([$_GET['editar_partido']]);
    $partido_a_editar = $stmt->fetch();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Control - Gestión de Partidos</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Panel de Control</h1>
        <a href="controlPanelLogin.php?logout=true" class="btn btn-danger mb-3">Cerrar sesión</a>

        <h2><?php echo $partido_a_editar ? 'Editar Partido' : 'Nuevo Partido'; ?></h2>

        <form method="POST">
            <?php if ($partido_a_editar): ?>
                <input type="hidden" name="id_partido" value="<?php echo $partido_a_editar['id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="jornada_id">Jornada</label>
                <select class="form-control" name="jornada_id" id="jornada_id" required>
                    <?php foreach ($jornadas as $jornada): ?>
                        <option value="<?php echo $jornada['id']; ?>" <?php echo ($partido_a_editar && $partido_a_editar['jornada_id'] == $jornada['id']) ? 'selected' : ''; ?>>
                            Jornada <?php echo $jornada['numero']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="equipo1">Equipo 1</label>
                <input type="text" class="form-control" name="equipo1" id="equipo1" value="<?php echo $partido_a_editar ? htmlspecialchars($partido_a_editar['equipo1']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="equipo2">Equipo 2</label>
                <input type="text" class="form-control" name="equipo2" id="equipo2" value="<?php echo $partido_a_editar ? htmlspecialchars($partido_a_editar['equipo2']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="fecha">Fecha</label>
                <input type="date" class="form-control" name="fecha" id="fecha" value="<?php echo $partido_a_editar ? $partido_a_editar['fecha'] : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="resultado">Resultado (Equipo 1 - Equipo 2)</label>
                <input type="text" class="form-control" name="resultado" id="resultado" value="<?php echo $partido_a_editar ? htmlspecialchars($partido_a_editar['resultado']) : ''; ?>">
            </div>

            <button type="submit" class="btn btn-primary"><?php echo $partido_a_editar ? 'Actualizar Partido' : 'Agregar Partido'; ?></button>
        </form>

        <hr>

        <h2>Partidos Registrados</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Jornada</th>
                    <th>Equipo 1</th>
                    <th>Equipo 2</th>
                    <th>Fecha</th>
                    <th>Resultado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Obtener los partidos registrados
                $stmt = $pdo->query("SELECT p.id, p.equipo1, p.equipo2, p.fecha, p.resultado, j.numero AS jornada_numero FROM partidos p JOIN jornadas j ON p.jornada_id = j.id ORDER BY p.fecha");
                $partidos = $stmt->fetchAll();
                foreach ($partidos as $partido):
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($partido['jornada_numero']); ?></td>
                        <td><?php echo htmlspecialchars($partido['equipo1']); ?></td>
                        <td><?php echo htmlspecialchars($partido['equipo2']); ?></td>
                        <td><?php echo htmlspecialchars($partido['fecha']); ?></td>
                        <td><?php echo htmlspecialchars($partido['resultado'] ?? '') ?: 'Sin Resultado'; ?></td>

                        <td>
                            <a href="?editar_partido=<?php echo $partido['id']; ?>" class="btn btn-warning">Editar</a>
                            <a href="eliminar_partido.php?id=<?php echo $partido['id']; ?>" class="btn btn-danger" onclick="return confirm('¿Estás seguro de que deseas eliminar este partido?')">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
