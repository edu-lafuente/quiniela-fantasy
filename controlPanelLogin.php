<?php
session_start();

// Verificar si el usuario ya está autenticado, en ese caso redirigir al panel de control
if (isset($_SESSION['usuario_valido']) && $_SESSION['usuario_valido'] == true) {
    header('Location: controlPanel.php');
    exit();
}

// Si se envió el formulario, verificar la contraseña
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $passwordIntroducida = $_POST['password'];

    // La contraseña fija que usaremos para la validación
    $passwordCorrecta = '9460';

    if ($passwordIntroducida === $passwordCorrecta) {
        // Si la contraseña es correcta, iniciar sesión
        $_SESSION['usuario_valido'] = true;
        header('Location: controlPanel.php'); // Redirigir al panel de control
        exit();
    } else {
        $error_message = "Contraseña incorrecta.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de sesión - Panel de Control</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2 class="mt-5">Iniciar sesión en el Panel de Control</h2>
        <form method="POST" class="mt-3">
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">Iniciar sesión</button>
        </form>
    </div>
</body>
</html>
