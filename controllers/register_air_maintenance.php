<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../views/index.php');
    exit;
}
include_once __DIR__ . '/../includes/db.php';

// Cambia device_id por id para que coincida con el formulario
$air_id = $_POST['id'] ?? null;
$date = $_POST['date'] ?? null;
$type = $_POST['type'] ?? null;
$description = $_POST['description'] ?? null;
$responsible = isset($_POST['responsible']) ? $_POST['responsible'] : null;
$externo = isset($_POST['externo']) && $_POST['externo'] == '1';
$external = $externo ? trim($_POST['proveedor_numero']) : null;

// Validar que el aire exista y obtener el número interno
$stmt = $conn->prepare("SELECT id, internal_number FROM air WHERE id = ?");
$stmt->bind_param("i", $air_id);
$stmt->execute();
$stmt->bind_result($real_air_id, $internal_number);
$exists = $stmt->fetch();
$stmt->close();

if (!$exists) {
    die("Error: El aire acondicionado seleccionado no existe en la tabla air. No se puede registrar el mantenimiento.");
}

// Crear carpeta si no existe
$dir = __DIR__ . "/../uploads/" . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $internal_number);
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

// Crear carpeta de mantenimiento si no existe
$mnt_dir = __DIR__ . "/../uploads/mantenimiento/" . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $internal_number);
if (!is_dir($mnt_dir)) {
    mkdir($mnt_dir, 0777, true);
}

// Guardar la imagen de mantenimiento en la carpeta mantenimiento/{numero_interno}
$mantenimiento_img = null;
if (isset($_FILES['foto_mantenimiento']) && $_FILES['foto_mantenimiento']['error'] === UPLOAD_ERR_OK) {
    $tmp_name = $_FILES['foto_mantenimiento']['tmp_name'];
    $ext = pathinfo($_FILES['foto_mantenimiento']['name'], PATHINFO_EXTENSION);
    $filename = $date . "." . $ext;
    $destino = $mnt_dir . "/" . $filename;
    if (move_uploaded_file($tmp_name, $destino)) {
        $mantenimiento_img = "uploads/mantenimiento/" . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $internal_number) . "/" . $filename;
    }
}

// Guardar el mantenimiento en la tabla air_maintenance (sin la columna maintenance_img)
if ($externo) {
    $stmt = $conn->prepare("INSERT INTO air_maintenance (air_id, date, type, description, responsible, external) VALUES (?, ?, ?, ?, NULL, ?)");
    $stmt->bind_param("issss", $real_air_id, $date, $type, $description, $external);
} else {
    $stmt = $conn->prepare("INSERT INTO air_maintenance (air_id, date, type, description, responsible, external) VALUES (?, ?, ?, ?, ?, NULL)");
    $stmt->bind_param("issss", $real_air_id, $date, $type, $description, $responsible);
}
$stmt->execute();
$stmt->close();

// Redirige a la hoja de vida del aire correspondiente después de registrar el mantenimiento
header("Location: ../views/list_air.php?internal_number=" . urlencode($internal_number));
exit;
