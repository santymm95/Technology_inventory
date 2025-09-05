<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}
include_once __DIR__ . '/../includes/conection.php';

// Recoger datos del formulario
$nvr_id = isset($_POST['device_id']) ? intval($_POST['device_id']) : 0;
$internal_number = isset($_POST['internal_number']) ? $_POST['internal_number'] : '';
$date = $_POST['date'] ?? '';
$type = $_POST['type'] ?? '';
$description = $_POST['description'] ?? '';
$responsible = $_POST['responsible'] ?? '';
$external = isset($_POST['externo']) && !empty($_POST['proveedor_numero']) ? $_POST['proveedor_numero'] : null;

// Manejo de la foto
$photo_path = null;
if (isset($_FILES['foto_mantenimiento']) && $_FILES['foto_mantenimiento']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['foto_mantenimiento']['name'], PATHINFO_EXTENSION);
    $upload_dir = "../images/mantenimiento/" . $internal_number . "/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $filename = $date . "." . $ext;
    $dest_path = $upload_dir . $filename;
    if (move_uploaded_file($_FILES['foto_mantenimiento']['tmp_name'], $dest_path)) {
        $photo_path = $dest_path;
    }
}

// Validar datos mínimos
$stmt = $conn->prepare("SELECT id FROM nvr_devices WHERE id = ?");
$stmt->bind_param("i", $nvr_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    $stmt->close();
    die("Error: El NVR seleccionado no existe.");
}
$stmt->close();

// Insertar mantenimiento
$insert = $conn->prepare("INSERT INTO nvr_maintenance (nvr_id, date, type, description, responsible, external, photo) VALUES (?, ?, ?, ?, ?, ?, ?)");
$insert->bind_param(
    "issssss",
    $nvr_id,
    $date,
    $type,
    $description,
    $responsible,
    $external,
    $photo_path
);
if ($insert->execute()) {
    header("Location: ../views/nvr_profile.php?id=" . urlencode($nvr_id) . "&success=1");
    exit;
} else {
    die("Error al registrar el mantenimiento.");
}
