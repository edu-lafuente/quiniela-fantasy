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
    die("<div class='alert alert-danger' role='alert'>No estás logueado.</div>");
}

$usuario_id = $_SESSION['usuario_id'];

// Obtener datos del usuario
$stmt = $pdo->prepare('SELECT comodin_x2, comodin_x3, compras_comodin_x2, compras_comodin_x3 FROM participantes WHERE id = ?');
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    die("<div class='alert alert-danger' role='alert'>Usuario no encontrado.</div>");
}

function calcularPuntos($resultadoSeleccionado, $resultadoReal) {
    if ($resultadoSeleccionado === $resultadoReal) {
        return 3; // Por ejemplo, 3 puntos si el resultado es correcto.
    } elseif (strpos($resultadoSeleccionado, '-') !== false && strpos($resultadoReal, '-') !== false) {
        $seleccionado = explode('-', $resultadoSeleccionado);
        $real = explode('-', $resultadoReal);
        if ($seleccionado[0] == $real[0] && $seleccionado[1] == $real[1]) {
            return 1; // Por ejemplo, 1 punto si aciertas solo el empate o victoria.
        }
    }
    return 0; // Sin puntos si no coincide.
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

$puntos = obtenerPuntosTotales($pdo, $usuario_id);
$comodin_x2 = $usuario['comodin_x2'];
$comodin_x3 = $usuario['comodin_x3'];
$compras_x2 = $usuario['compras_comodin_x2'];
$compras_x3 = $usuario['compras_comodin_x3'];

// Definir precios dinámicos
$precio_x2 = 3 * pow(2, $compras_x2);
$precio_x3 = 10 * pow(2, $compras_x3);

// Comprobar si se ha realizado una compra
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_comodin = $_POST['comodin'];
    $precio = $tipo_comodin === 'x2' ? $precio_x2 : $precio_x3;

    if ($puntos >= $precio) {
        if ($tipo_comodin === 'x2') {
            $stmt = $pdo->prepare('UPDATE participantes SET comodin_x2 = ?, compras_comodin_x2 = ? WHERE id = ?');
            $stmt->execute([$comodin_x2 + 1, $compras_x2 + 1, $usuario_id]);
        } elseif ($tipo_comodin === 'x3') {
            $stmt = $pdo->prepare('UPDATE participantes SET comodin_x3 = ?, compras_comodin_x3 = ? WHERE id = ?');
            $stmt->execute([$comodin_x3 + 1, $compras_x3 + 1, $usuario_id]);
        }
        echo json_encode(['success' => true, 'puntos' => $puntos, 'comodin_x2' => $comodin_x2 + 1, 'comodin_x3' => $comodin_x3 + 1]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No tienes suficientes puntos para esta compra.']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mercado - Comprar Comodines</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 40px;
        }
        .btn-custom {
            background-color: #007bff;
            color: white;
        }
        .modal-content {
            background-color: #ffffff;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <a href="ranking.php" class="btn btn-secondary mb-4">Volver al Ranking</a>
    <h3>Mercado</h3>
    <div class="card mb-4">
        <div class="card-header">
            <h4 class="m-0">Inventario</h4>
        </div>
        <div class="card-body">
            <p><strong><img src="iconos/dinero.webp" width="30" height="30" style="margin-right: 5px;"></strong> <?php echo $puntos; ?></p>
            <p><strong><img src="iconos/x2.webp" width="35" height="35" style="margin-right: 5px;"></strong> <?php echo $comodin_x2; ?></p>
            <p><strong><img src="iconos/x3.webp" width="35" height="35" style="margin-right: 5px;"></strong> <?php echo $comodin_x3; ?></p>
        </div>
    </div>

    <h4 class="mb-3">Comodines</h4>
    <div class="list-group mb-4">
        <button type="button" class="list-group-item list-group-item-action list-group-item-info" id="comprarX2" data-precio="<?php echo $precio_x2; ?>" data-comodin="x2">
            Comprar Comodín X2 - <?php echo $precio_x2; ?> puntos
        </button>
        <button type="button" class="list-group-item list-group-item-action list-group-item-info" id="comprarX3" data-precio="<?php echo $precio_x3; ?>" data-comodin="x3">
            Comprar Comodín X3 - <?php echo $precio_x3; ?> puntos
        </button>
    </div>
    
    <div id="mensaje"></div>
</div>

<!-- Modal de Confirmación de Compra -->
<div class="modal" id="modalConfirmacionCompra" tabindex="-1" role="dialog" aria-labelledby="modalConfirmacionCompraLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalConfirmacionCompraLabel">Confirmación de Compra</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                ¿Estás seguro de que deseas comprar este comodín?
                <div id="modalComodinDetalle"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmarCompra">Confirmar Compra</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    var comodinSeleccionado;

    // Al hacer click en comprar Comodín X2 o X3
    $('#comprarX2, #comprarX3').click(function() {
        var precio = $(this).data('precio');
        var comodin = $(this).data('comodin');

        comodinSeleccionado = comodin;  // Guardamos el comodín seleccionado

        // Mostrar detalles en el modal
        var comodinNombre = comodin === 'x2' ? 'Comodín X2' : 'Comodín X3';
        $('#modalComodinDetalle').html(`${comodinNombre} - Precio: ${precio} puntos`);

        // Mostrar modal
        $('#modalConfirmacionCompra').modal('show');
    });

    // Confirmar compra
    $('#confirmarCompra').click(function() {
        $.ajax({
            url: 'mercado.php',
            type: 'POST',
            data: { comodin: comodinSeleccionado },
            success: function(response) {
                var data = JSON.parse(response);

                if (data.success) {
                    $('#mensaje').html('<div class="alert alert-success">Compra exitosa. Nuevo saldo de comodines.</div>');
                    location.reload();
                } else {
                    $('#mensaje').html('<div class="alert alert-danger">' + data.message + '</div>');
                }
                $('#modalConfirmacionCompra').modal('hide');
            }
        });
    });
});
</script>

</body>
</html>
