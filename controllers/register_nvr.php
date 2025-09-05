<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}
include_once __DIR__ . '/../includes/conection.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $internal_number = $_POST['n_interno'] ?? '';
    $model = $_POST['modelo'] ?? '';
    $brand = $_POST['marca'] ?? '';
    $serial = $_POST['serial'] ?? '';
    $purchase_date = $_POST['fecha_compra'] ?? null;
    $supplier = $_POST['proveedor'] ?? '';
    $type = $_POST['tipo'] ?? '';
    $decoding = $_POST['decodificacion'] ?? '';
    $inputs = $_POST['entradas'] ?? '';
    $connectivity = $_POST['conectividad'] ?? '';
    $storage = $_POST['almacenamiento'] ?? '';
    $transmission = $_POST['transmision'] ?? '';
    $usage = $_POST['uso'] ?? '';
    $parts_description = $_POST['partes_equipo'] ?? '';

    // Folder for images
    $folder_name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $internal_number);
    $upload_dir = __DIR__ . '/../uploads/nvr/' . $folder_name;
    if (!is_dir($upload_dir) && $internal_number) {
        mkdir($upload_dir, 0777, true);
    }

    // Save photo
    if (isset($_FILES['fotografia']) && $_FILES['fotografia']['error'] === UPLOAD_ERR_OK) {
        move_uploaded_file($_FILES['fotografia']['tmp_name'], $upload_dir . '/photo.jpg');
    }
    // Save parts image
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        move_uploaded_file($_FILES['imagen']['tmp_name'], $upload_dir . '/parts.jpg');
    }

    // Insert into DB (no image paths, only folder)
    if ($internal_number && $model && $brand && $serial) {
        $stmt = $conn->prepare("INSERT INTO nvr_devices 
            (internal_number, model, brand, serial, purchase_date, supplier, type, decoding, inputs, connectivity, storage, transmission, `usage`, parts_description, image_folder)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $image_folder = 'uploads/nvr/' . $folder_name;
        $stmt->bind_param(
            "sssssssssssssss",
            $internal_number, $model, $brand, $serial, $purchase_date, $supplier, $type, $decoding, $inputs, $connectivity, $storage, $transmission, $usage, $parts_description, $image_folder
        );
        if ($stmt->execute()) {
            header('Location: ../views/registrar_nvr.php?success=1');
            exit;
        } else {
            $error = "Error saving NVR: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error = "Required fields are missing.";
    }
    if ($error) {
        header('Location: ../views/registrar_nvr.php?error=' . urlencode($error));
        exit;
    }
}
?>
