<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
global $conn;

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $internal_number = $_POST['internal_number'] ?? '';
    $brand = $_POST['brand'] ?? '';
    $model = $_POST['model'] ?? '';
    $serial = $_POST['serial'] ?? '';
    $purchase_date = $_POST['purchase_date'] ?? '';
    $provider = $_POST['provider'] ?? '';
    $host_name = $_POST['host_name'] ?? '';
    $fax_speed = $_POST['fax_speed'] ?? '';
    $duplex = $_POST['duplex'] ?? '';
    $connectivity = $_POST['connectivity'] ?? '';
    $front_panel = $_POST['front_panel'] ?? '';
    $filter_type = $_POST['filter_type'] ?? '';
    $print_speed = $_POST['print_speed'] ?? '';
    $ip_url = $_POST['ip_url'] ?? '';
    $voltage = $_POST['voltage'] ?? '';
    $ink_cartridges = $_POST['ink_cartridges'] ?? '';
    $parts = $_POST['parts'] ?? '';
    $parts_desc = $_POST['parts_desc'] ?? '';

    // Crear carpeta para el equipo
    $upload_dir = __DIR__ . '/../uploads/' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $internal_number);
    if (!is_dir($upload_dir) && $internal_number) {
        mkdir($upload_dir, 0777, true);
    }

    // Guardar fotografía principal
    $photo_filename = '';
    if (isset($_FILES['photo_file']) && $_FILES['photo_file']['error'] === UPLOAD_ERR_OK) {
        $photo_filename = $upload_dir . '/foto_principal.jpg';
        move_uploaded_file($_FILES['photo_file']['tmp_name'], $photo_filename);
    }
    $photo_db = $photo_filename ? str_replace(__DIR__ . '/../', '', $photo_filename) : '';

    // Guardar imagen de partes
    $parts_image_filename = '';
    if (isset($_FILES['parts_file']) && $_FILES['parts_file']['error'] === UPLOAD_ERR_OK) {
        $parts_image_filename = $upload_dir . '/imagen_partes.jpg';
        move_uploaded_file($_FILES['parts_file']['tmp_name'], $parts_image_filename);
    }
    $parts_image_db = $parts_image_filename ? str_replace(__DIR__ . '/../', '', $parts_image_filename) : '';

    // Validar campos requeridos
    $required = [
        $internal_number, $brand, $model, $serial, $purchase_date, $provider, $host_name, $fax_speed, $duplex, $connectivity, $front_panel, $filter_type, $print_speed, $ip_url, $voltage, $ink_cartridges, $parts, $parts_desc
    ];
    $allFieldsFilled = true;
    foreach ($required as $val) {
        if (trim($val) === '' && $val !== "0") {
            $allFieldsFilled = false;
            break;
        }
    }

    // Validar archivos subidos
    $photoFileOk = isset($_FILES['photo_file']) && $_FILES['photo_file']['error'] === UPLOAD_ERR_OK;
    $partsFileOk = isset($_FILES['parts_file']) && $_FILES['parts_file']['error'] === UPLOAD_ERR_OK;

    if (!$photoFileOk) {
        $error = "Debe subir una fotografía principal de la impresora.";
    } elseif (!$partsFileOk) {
        $error = "Debe subir una imagen de las partes del equipo.";
    } elseif ($allFieldsFilled && $photoFileOk && $partsFileOk) {
        $stmt = $conn->prepare("INSERT INTO printer 
            (internal_number, brand, model, serial, purchase_date, device_type, provider, host_name, fax_speed, duplex, connectivity, front_panel, filter_type, print_speed, ip_url, voltage, ink_cartridges, parts, parts_desc, photo, parts_image) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $tipo = "Impresora";
        $stmt->bind_param(
            "sssssssssssssssssssss",
            $internal_number, $brand, $model, $serial, $purchase_date, $tipo, $provider,
            $host_name, $fax_speed, $duplex, $connectivity, $front_panel, $filter_type,
            $print_speed, $ip_url, $voltage, $ink_cartridges, $parts, $parts_desc, $photo_db, $parts_image_db
        );
        if ($stmt->execute()) {
            $success = true;
        } else {
            $error = "Error al registrar la impresora: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error = "Todos los campos son obligatorios para impresoras.";
    }

    if ($success) {
        header("Location: ../views/form_printer.php?success=1");
        exit;
    } else {
        header("Location: ../views/form_printer.php?error=" . urlencode($error));
        exit;
    }
}
