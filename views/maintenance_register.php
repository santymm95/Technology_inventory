<?php
include_once __DIR__ . '/../includes/conection.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $device_id = intval($_POST['device_id']);
    $internal_number = trim($_POST['internal_number']);
    $date = $_POST['date'] ?? null;
    $type = trim($_POST['type']);
    $responsible = trim($_POST['responsible']);
    $description = trim($_POST['description']);
    $photo_filename = null;

    // Guardar la foto en la carpeta del equipo (uploads/{internal_number}/)
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK && !empty($internal_number)) {
        $uploadsBase = realpath(__DIR__ . '/../uploads');
        $uploadDir = $uploadsBase . DIRECTORY_SEPARATOR . $internal_number;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photo_filename = 'maintenance_' . date('Ymd_His') . '.' . $ext;
        $filepath = $uploadDir . DIRECTORY_SEPARATOR . $photo_filename;
        move_uploaded_file($_FILES['photo']['tmp_name'], $filepath);
    }

    // Insertar el mantenimiento en la base de datos (sin guardar la foto en la BD)
    $stmt = $conn->prepare("INSERT INTO maintenance (device_id, date, description, type, responsible) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $device_id, $date, $description, $type, $responsible);

    if ($stmt->execute()) {
        $message = "✅ Mantenimiento registrado correctamente.";
    } else {
        $message = "❌ Error al registrar mantenimiento: " . $stmt->error;
    }
    $stmt->close();

    // Redirigir de vuelta al perfil del equipo con mensaje
    header("Location: device_profile.php?id=" . $device_id . "&msg=" . urlencode($message));
    exit;
} else {
    header("Location: ../index.php");
    exit;
}
