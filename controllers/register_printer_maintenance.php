<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../views/index.php');
    exit;
}

include_once __DIR__ . '/../includes/conection.php';

$printer_id = $_POST['device_id']; // ahora es printer_id
$date = $_POST['date'];
$type = $_POST['type'];
$description = $_POST['description'];
$responsible = isset($_POST['responsible']) ? $_POST['responsible'] : null;
$externo = isset($_POST['externo']) && $_POST['externo'] == '1';
$external = $externo ? trim($_POST['proveedor_numero']) : null;

// Obtener el número interno de la impresora y validar existencia SOLO en printer
$stmt = $conn->prepare("SELECT internal_number FROM printer WHERE id = ?");
$stmt->bind_param("i", $printer_id);
$stmt->execute();
$stmt->bind_result($internal_number);
$printer_exists = $stmt->fetch();
$stmt->close();

if (!$printer_exists) {
    die("Error: La impresora seleccionada no existe en la tabla printer. No se puede registrar el mantenimiento.");
}

// Crear carpeta si no existe
$dir = __DIR__ . "/../images/mantenimiento/" . $internal_number;
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

// Guardar la imagen si se subió
$photo_path = null;
if (isset($_FILES['foto_mantenimiento']) && $_FILES['foto_mantenimiento']['error'] === UPLOAD_ERR_OK) {
    $tmp_name = $_FILES['foto_mantenimiento']['tmp_name'];
    $ext = pathinfo($_FILES['foto_mantenimiento']['name'], PATHINFO_EXTENSION);
    $photo_path = "images/mantenimiento/" . $internal_number . "/" . $date . "." . $ext;
    $destino = __DIR__ . "/../" . $photo_path;
    move_uploaded_file($tmp_name, $destino);
}

// Guardar el mantenimiento en la base de datos (en printer_maintenance)
if ($externo) {
    $stmt = $conn->prepare("INSERT INTO printer_maintenance (printer_id, date, type, description, photo, responsible, external) VALUES (?, ?, ?, ?, ?, NULL, ?)");
    $stmt->bind_param("isssss", $printer_id, $date, $type, $description, $photo_path, $external);
} else {
    $stmt = $conn->prepare("INSERT INTO printer_maintenance (printer_id, date, type, description, photo, responsible, external) VALUES (?, ?, ?, ?, ?, ?, NULL)");
    $stmt->bind_param("isssss", $printer_id, $date, $type, $description, $photo_path, $responsible);
}
$stmt->execute();
$stmt->close();

header("Location: ../views/printer_list.php");
exit;
