<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../views/index.php');
    exit;
}
include_once __DIR__ . '/../includes/db.php';

$vehicle_id = $_POST['device_id'] ?? null;
$date = $_POST['date'] ?? null;
$type = $_POST['type'] ?? null;
$description = $_POST['description'] ?? null;
$responsible = isset($_POST['responsible']) ? $_POST['responsible'] : null;
$externo = isset($_POST['externo']) && $_POST['externo'] == '1';
$external = $externo ? trim($_POST['proveedor_numero']) : null;

// Validar que el vehicle_id exista en la tabla vehicle
$stmt = $conn->prepare("SELECT placa FROM vehicle WHERE id = ?");
$stmt->bind_param("i", $vehicle_id);
$stmt->execute();
$stmt->bind_result($placa);
$exists = $stmt->fetch();
$stmt->close();

if (!$exists) {
    die("Error: El vehículo seleccionado no existe en la tabla vehicle. No se puede registrar el mantenimiento.");
}

// Crear carpeta si no existe
$dir = __DIR__ . "/../images/mantenimiento/" . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $placa);
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

// Guardar la imagen si se subió
$photo_path = null;
if (isset($_FILES['foto_mantenimiento']) && $_FILES['foto_mantenimiento']['error'] === UPLOAD_ERR_OK) {
    $tmp_name = $_FILES['foto_mantenimiento']['tmp_name'];
    $ext = pathinfo($_FILES['foto_mantenimiento']['name'], PATHINFO_EXTENSION);
    $photo_path = "images/mantenimiento/" . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $placa) . "/" . $date . "." . $ext;
    $destino = __DIR__ . "/../" . $photo_path;
    move_uploaded_file($tmp_name, $destino);
}

// Guardar el mantenimiento en la tabla vehicle_maintenance
if ($externo) {
    $stmt = $conn->prepare("INSERT INTO vehicle_maintenance (vehicle_id, date, type, description, photo, responsible, external) VALUES (?, ?, ?, ?, ?, NULL, ?)");
    $stmt->bind_param("isssss", $vehicle_id, $date, $type, $description, $photo_path, $external);
} else {
    $stmt = $conn->prepare("INSERT INTO vehicle_maintenance (vehicle_id, date, type, description, photo, responsible, external) VALUES (?, ?, ?, ?, ?, ?, NULL)");
    $stmt->bind_param("isssss", $vehicle_id, $date, $type, $description, $photo_path, $responsible);
}
$stmt->execute();
$stmt->close();

header("Location: ../views/vehicle_list.php");
exit;
