<?php
// Iniciar la sesión
session_start();

// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "quiniela_fantasy_edu";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT id, password_hash FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password_hash'])) {
            $_SESSION['usuario_id'] = $row['id'];

            $sqlParticipante = "SELECT id FROM participantes WHERE usuario_id = ?";
            $stmtParticipante = $conn->prepare($sqlParticipante);
            $stmtParticipante->bind_param("i", $row['id']);
            $stmtParticipante->execute();
            $resultParticipante = $stmtParticipante->get_result();

            if ($resultParticipante->num_rows > 0) {
                $participanteRow = $resultParticipante->fetch_assoc();
                $_SESSION['participante_id'] = $participanteRow['id'];
            }

            header("Location: ranking.php");
            exit();
        } else {
            echo "<div class='alert alert-danger' role='alert'>Contraseña incorrecta.</div>";
        }
    } else {
        echo "<div class='alert alert-danger' role='alert'>No existe un usuario con ese correo electrónico.</div>";
    }
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, #007bff, #6610f2);
            color: #fff;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .card {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border: none;
            border-radius: 10px;
        }
        .card-header {
            background: #343a40;
            color: #fff;
            font-size: 1.5em;
            border-radius: 10px !important;
        }
        .form-control {
            border-radius: 5px;
        }
        .btn-primary {
            background: #28a745;
            border: none;
            border-radius: 5px;
            font-size: 1.1em;
        }
        .btn-primary:hover {
            background: #218838;
        }
        a {
            color: #007bff;
            text-decoration: none;
        }
        a:hover {
            color: #0056b3;
        }
        .logo {
            display: block;
            margin: 0 auto;
            width: 100px; /* Ajusta el tamaño de la imagen */
            margin-bottom: 20px; /* Espacio entre la imagen y el formulario */
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <img src="imagenes/pelota.png" alt="Logo" class="logo"> <!-- Imagen de la pelota -->
                <div class="card p-4">
                    <div class="card-header text-center">
                        <h4>Iniciar Sesión</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="email">Correo Electrónico</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Ingresa tu correo" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Ingresa tu contraseña" required>
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
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
