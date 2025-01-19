<?php
// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "quiniela_fantasy_edu";  // Nombre de la base de datos

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Verificar si el formulario ha sido enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $nombre_usuario = $_POST['nombre_usuario'];

    // Validar si el email ya existe
    $sql = "SELECT * FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<div class='alert alert-danger' role='alert'>Este email ya está registrado.</div>";
    } else {
        // Hashear la contraseña
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Iniciar una transacción para asegurarnos de que ambas operaciones se realicen correctamente
        $conn->begin_transaction();
        
        try {
            // Insertar el nuevo usuario
            $sql = "INSERT INTO usuarios (nombre, email, password_hash, nombre_usuario, created_at) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $nombre, $email, $password_hash, $nombre_usuario);

            if (!$stmt->execute()) {
                throw new Exception("Error al registrar el usuario: " . $stmt->error);
            }

            // Obtener el id del nuevo usuario
            $usuario_id = $stmt->insert_id;

            // Insertar el participante
            $sql_participante = "INSERT INTO participantes (nombre, usuario_id) VALUES (?, ?)";
            $stmt_participante = $conn->prepare($sql_participante);
            $stmt_participante->bind_param("si", $nombre_usuario, $usuario_id);
            
            if (!$stmt_participante->execute()) {
                throw new Exception("Error al registrar el participante: " . $stmt_participante->error);
            }

            // Commit de la transacción
            $conn->commit();

            // Iniciar sesión automáticamente después del registro
            session_start();
            $_SESSION['usuario_id'] = $usuario_id;
            $_SESSION['participante_id'] = $stmt_participante->insert_id;

            echo "<div class='alert alert-success' role='alert'>Registro exitoso. Ahora puedes iniciar sesión.</div>";
            header("Location: ranking.php");
            exit();
        } catch (Exception $e) {
            // Si hay un error, hacer rollback
            $conn->rollback();
            echo "<div class='alert alert-danger' role='alert'>" . $e->getMessage() . "</div>";
        }
    }

    // Cerrar la conexión
    $stmt->close();
    $stmt_participante->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
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
                        <h4>Registro de Usuario</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="nombre">Nombre Completo</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ingresa tu nombre completo" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Correo Electrónico</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Ingresa tu correo electrónico" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Ingresa tu contraseña" required>
                            </div>
                            <div class="form-group">
                                <label for="nombre_usuario">Nombre de Usuario (para participar)</label>
                                <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario" placeholder="Elige un nombre de usuario" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Registrar</button>
                        </form>
                        <div class="text-center mt-3">
                            <a href="login.php">¿Ya tienes cuenta? Inicia sesión</a>
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
