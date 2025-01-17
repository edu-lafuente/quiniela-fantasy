<?php
include('conexion.php'); // Incluir la conexión a la base de datos

// Validar que se recibieron los datos
if (isset($_POST['partido_id']) && isset($_POST['resultado'])) {
    $partido_id = $_POST['partido_id'];
    $resultado = $_POST['resultado'];

    // Suponiendo que ya tienes el ID del participante en sesión
    $participante_id = $_SESSION['participante_id']; // Ajusta esto a tu estructura

    // Verificar que no se haya enviado un resultado previamente para este partido
    $sql = "SELECT COUNT(*) FROM selecciones WHERE partido_id = :partido_id AND participante_id = :participante_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':partido_id' => $partido_id, ':participante_id' => $participante_id]);
    $existe = $stmt->fetchColumn();

    if ($
