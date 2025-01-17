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
    $nombre_usuario = $_POST['nombre_usuario']; // Asumimos que se envía un nombre de usuario

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
            $_SESSION['usuario_id'] = $usuario_id; // Guardar el ID del usuario en la sesión
            $_SESSION['participante_id'] = $stmt_participante->insert_id; // Guardar el ID del participante

            echo "<div class='alert alert-success' role='alert'>Registro exitoso. Ahora puedes iniciar sesión.</div>";
            header("Location: ranking.php"); // Redirigir a la página de ranking
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
    <!-- Agregar Bootstrap -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card mt-5">
                    <div class="card-header bg-primary text-white text-center">
                        <h4>Registro de Usuario</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="nombre">Nombre Completo</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Correo Electrónico</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="form-group">
                                <label for="nombre_usuario">Nombre de Usuario (para participar)</label>
                                <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario" required>
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

    <!-- Agregar Scripts de Bootstrap -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
