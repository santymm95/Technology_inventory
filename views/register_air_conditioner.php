<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
include_once __DIR__ . '/../includes/conection.php';

$success = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $location = $_POST['location'] ?? '';
    $internal_number = $_POST['internal_number'] ?? '';
    $model = $_POST['model'] ?? '';
    $brand = $_POST['brand'] ?? '';
    $serial = $_POST['serial'] ?? '';
    $purchase_date = $_POST['purchase_date'] ?? '';
    $provider = $_POST['provider'] ?? '';
    $refrigerant = $_POST['refrigerant'] ?? '';
    $capacity = $_POST['capacity'] ?? '';
    $voltage = $_POST['voltage'] ?? '';

    // Crear carpeta para el aire
    $upload_dir = __DIR__ . '/../uploads/' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $internal_number);
    if (!is_dir($upload_dir) && $internal_number) {
        mkdir($upload_dir, 0777, true);
    }

    // Guardar fotografía principal (archivo)
    $photo_filename = '';
    if (isset($_FILES['photo_file']) && $_FILES['photo_file']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['photo_file']['name'], PATHINFO_EXTENSION);
        $photo_filename = $upload_dir . '/foto_principal.' . $ext;
        move_uploaded_file($_FILES['photo_file']['tmp_name'], $photo_filename);
    }

    if ($name && $location && $internal_number && $model && $brand && $serial && $purchase_date && $provider && $refrigerant && $capacity && $voltage) {
        $stmt = $conn->prepare("INSERT INTO air (name, location, internal_number, model, brand, serial, purchase_date, provider, refrigerant, capacity, voltage) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssss", $name, $location, $internal_number, $model, $brand, $serial, $purchase_date, $provider, $refrigerant, $capacity, $voltage);
        if ($stmt->execute()) {
            $success = true;
        } else {
            $error = "Error al registrar el aire acondicionado: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error = "Todos los campos son obligatorios.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Aire Acondicionado</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .form-container { max-width: 700px; margin: 2em auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); padding: 2em; }
        .form-group { margin-bottom: 1.2em; }
        label { display: block; margin-bottom: 0.4em; color: #215ba0; }
        input, select, textarea { width: 100%; padding: 0.5em; border: 1px solid #dbe4f3; border-radius: 5px; }
        button { background: #215ba0; color: #fff; border: none; padding: 0.7em 2em; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <?php include 'layout.php'; ?>
    <div class="form-container">
        <h2>Registrar Aire Acondicionado</h2>
        <?php if ($success): ?>
            <div style="color:green;margin-bottom:1em;">Aire acondicionado registrado correctamente.</div>
        <?php elseif ($error): ?>
            <div style="color:red;margin-bottom:1em;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" action="" enctype="multipart/form-data" autocomplete="off">
            <div class="form-group">
                <label for="name">Nombre del equipo</label>
                <input type="text" name="name" id="name" required>
            </div>
            <div class="form-group">
                <label for="location">Ubicación</label>
                <input type="text" name="location" id="location" required>
            </div>
            <div class="form-group">
                <label for="internal_number">N° Interno</label>
                <input type="text" name="internal_number" id="internal_number" required>
            </div>
            <div class="form-group">
                <label for="model">Modelo</label>
                <input type="text" name="model" id="model" required>
            </div>
            <div class="form-group">
                <label for="brand">Marca</label>
                <input type="text" name="brand" id="brand" required>
            </div>
            <div class="form-group">
                <label for="serial">Serial</label>
                <input type="text" name="serial" id="serial" required>
            </div>
            <div class="form-group">
                <label for="purchase_date">Fecha de compra</label>
                <input type="date" name="purchase_date" id="purchase_date" required>
            </div>
            <div class="form-group">
                <label for="provider">Proveedor</label>
                <input type="text" name="provider" id="provider" required>
            </div>
            <div class="form-group">
                <label for="refrigerant">Refrigerante</label>
                <input type="text" name="refrigerant" id="refrigerant" required>
            </div>
            <div class="form-group">
                <label for="capacity">Capacidad</label>
                <input type="text" name="capacity" id="capacity" required>
            </div>
            <div class="form-group">
                <label for="voltage">Voltaje</label>
                <input type="text" name="voltage" id="voltage" required>
            </div>
            <div class="form-group">
                <label for="photo_file">Fotografía</label>
                <input type="file" name="photo_file" id="photo_file" accept="image/*">
            </div>
            <button type="submit">Registrar aire acondicionado</button>
        </form>
    </div>
</body>
</html>
