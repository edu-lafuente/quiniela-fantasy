<?php
// Iniciar la sesión
session_start();

// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "quiniela_fantasy_edu"; // Nombre de la base de datos

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Verificar si el formulario ha sido enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Verificar si el email existe
    $sql = "SELECT id, password_hash FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Verificar la contraseña
        if (password_verify($password, $row['password_hash'])) {
            // Iniciar sesión y guardar el ID del usuario
            $_SESSION['usuario_id'] = $row['id'];
            
            // Obtener el participante_id correspondiente a este usuario
            $sqlParticipante = "SELECT id FROM participantes WHERE usuario_id = ?";
            $stmtParticipante = $conn->prepare($sqlParticipante);
            $stmtParticipante->bind_param("i", $row['id']);
            $stmtParticipante->execute();
            $resultParticipante = $stmtParticipante->get_result();

            if ($resultParticipante->num_rows > 0) {
                $participanteRow = $resultParticipante->fetch_assoc();
                $_SESSION['participante_id'] = $participanteRow['id']; // Guardar el participante_id en la sesión
            }

            // Redirigir al ranking.php
            header("Location: ranking.php");
            exit(); // Asegurarse de que no se ejecute el código posterior
        } else {
            echo "<div class='alert alert-danger' role='alert'>Contraseña incorrecta.</div>";
        }
    } else {
        echo "<div class='alert alert-danger' role='alert'>No existe un usuario con ese correo electrónico.</div>";
    }

    // Cerrar la conexión
    $stmt->close();
    $conn->close();
}
?>

<!-- Formulario de login -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <!-- Agregar Bootstrap -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card mt-5">
                    <div class="card-header bg-primary text-white text-center">
                        <h4>Iniciar Sesión</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="email">Correo Electrónico</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Iniciar Sesión</button>
                        </form>
                        <div class="text-center mt-3">
                            <a href="registro.php">¿No tienes cuenta? Regístrate</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Agregar Scripts de Bootstrap -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
