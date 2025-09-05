<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../views/index.php');
    exit;
}

// Suponiendo que recibes device_id, date, type, description, responsible y foto_mantenimiento

include_once __DIR__ . '/../includes/conection.php';

$device_id = $_POST['device_id'] ?? null;
$tipo_equipo = $_POST['tipo_equipo'] ?? 'devices';
$date = $_POST['date'];
$type = $_POST['type'];
$description = $_POST['description'];
$responsible = isset($_POST['responsible']) ? $_POST['responsible'] : null;
$externo = isset($_POST['externo']) && $_POST['externo'] == '1';
$external = $externo ? trim($_POST['proveedor_numero']) : null;

if ($tipo_equipo === 'vehicle') {
    // Buscar placa y validar existencia en vehicle
    $stmt = $conn->prepare("SELECT placa FROM vehicle WHERE id = ?");
    $stmt->bind_param("i", $device_id);
    $stmt->execute();
    $stmt->bind_result($placa);
    $exists = $stmt->fetch();
    $stmt->close();

    if (!$exists) {
        die("Error: El vehículo seleccionado no existe en la tabla vehicle. No se puede registrar el mantenimiento.");
    }

    // Guardar el mantenimiento en la tabla vehicle_maintenance (NO en maintenance)
    $dir = __DIR__ . "/../images/mantenimiento/" . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $placa);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $photo_path = null;
    if (isset($_FILES['foto_mantenimiento']) && $_FILES['foto_mantenimiento']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['foto_mantenimiento']['tmp_name'];
        $ext = pathinfo($_FILES['foto_mantenimiento']['name'], PATHINFO_EXTENSION);
        $photo_path = "images/mantenimiento/" . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $placa) . "/" . $date . "." . $ext;
        $destino = __DIR__ . "/../" . $photo_path;
        move_uploaded_file($tmp_name, $destino);
    }
    if ($externo) {
        $stmt = $conn->prepare("INSERT INTO vehicle_maintenance (vehicle_id, date, type, description, photo, responsible, external) VALUES (?, ?, ?, ?, ?, NULL, ?)");
        $stmt->bind_param("isssss", $device_id, $date, $type, $description, $photo_path, $external);
    } else {
        $stmt = $conn->prepare("INSERT INTO vehicle_maintenance (vehicle_id, date, type, description, photo, responsible, external) VALUES (?, ?, ?, ?, ?, ?, NULL)");
        $stmt->bind_param("isssss", $device_id, $date, $type, $description, $photo_path, $responsible);
    }
    $stmt->execute();
    $stmt->close();

    // Redirige a la HV específica del vehículo
    header("Location: ../views/vehicle_list.php?placa=" . urlencode($placa));
    exit;
} else {
    // Validar que el device_id exista en la tabla devices antes de insertar el mantenimiento
    $device_exists = false;
    $check_stmt = $conn->prepare("SELECT id FROM devices WHERE id = ?");
    $check_stmt->bind_param("i", $device_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    if ($check_stmt->num_rows > 0) {
        $device_exists = true;
    }
    $check_stmt->close();

    if (!$device_exists) {
        // Mostrar error claro y no intentar insertar
        die("Error: El equipo seleccionado no existe en la tabla devices. No se puede registrar el mantenimiento.");
    }

    // Obtener el número interno del equipo
    $stmt = $conn->prepare("SELECT internal_number FROM devices WHERE id = ?");
    $stmt->bind_param("i", $device_id);
    $stmt->execute();
    $stmt->bind_result($internal_number);
    $device_exists = $stmt->fetch();
    $stmt->close();

    if (!$device_exists) {
        die("Error: El equipo seleccionado no existe en la tabla devices. No se puede registrar el mantenimiento.");
    }
}

// Crear carpeta si no existe
$dir = __DIR__ . "/../images/mantenimiento/" . $internal_number;
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

// Guardar la imagen si se subió
if (isset($_FILES['foto_mantenimiento']) && $_FILES['foto_mantenimiento']['error'] === UPLOAD_ERR_OK) {
    $tmp_name = $_FILES['foto_mantenimiento']['tmp_name'];
    $ext = pathinfo($_FILES['foto_mantenimiento']['name'], PATHINFO_EXTENSION);
    $destino = $dir . "/" . $date . "." . $ext;
    move_uploaded_file($tmp_name, $destino);
}

// Guardar el mantenimiento en la base de datos
if ($externo) {
    $stmt = $conn->prepare("INSERT INTO maintenance (device_id, date, type, description, responsible, external) VALUES (?, ?, ?, ?, NULL, ?)");
    $stmt->bind_param("issss", $device_id, $date, $type, $description, $external);
} else {
    $stmt = $conn->prepare("INSERT INTO maintenance (device_id, date, type, description, responsible, external) VALUES (?, ?, ?, ?, ?, NULL)");
    $stmt->bind_param("issss", $device_id, $date, $type, $description, $responsible);
}
$stmt->execute();
$stmt->close();

// Redirigir de vuelta al perfil del dispositivo
header("Location: ../views/device_profile.php?id=" . urlencode($device_id));
exit;
