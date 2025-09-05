<?php
include_once __DIR__ . '/../includes/conection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger datos
    $user_id = $_POST['user_id'] ?? '';
    $driver_name = $_POST['driver_name'] ?? '';
    $driver_document = $_POST['driver_document'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $plate = $_POST['plate'] ?? '';
    $mileage = $_POST['mileage'] ?? 0;
    $health_condition = $_POST['health_condition'] ?? '';
    $tires = $_POST['tires'] ?? '';
    $lights = $_POST['lights'] ?? '';
    $horn = $_POST['horn'] ?? '';
    $mirrors = $_POST['mirrors'] ?? '';
    $fluids = $_POST['fluids'] ?? '';
    $leaks = $_POST['leaks'] ?? '';
    $brakes = $_POST['brakes'] ?? '';
    $windshield = $_POST['windshield'] ?? '';
    $retention = $_POST['retention'] ?? '';
    $documents = $_POST['documents'] ?? '';
    $prevention = $_POST['prevention'] ?? '';
    $dashboard_lights = $_POST['dashboard_lights'] ?? '';
    $general_observations = $_POST['general_observations'] ?? '';
    $firma_usuario = $_POST['firma_entrega'] ?? '';
    $firma_ti = $_POST['firma_ti'] ?? '';
    $status = 'pending';

    // Crear carpeta para guardar firmas
    $folder_name = 'preoperacion_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $driver_document);
    $folder_path = __DIR__ . '/../uploads/' . $folder_name;
    if (!is_dir($folder_path)) {
        mkdir($folder_path, 0775, true);
    }

    // Guardar firma del usuario
    $signature_user_path = '';
    if (!empty($firma_usuario) && strpos($firma_usuario, 'data:image') === 0) {
        $firma_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $firma_usuario));
        $signature_user_path = $folder_name . '/signature_user.png';
        file_put_contents(__DIR__ . '/../uploads/' . $signature_user_path, $firma_data);
    }

    // Guardar firma del área TI
    $signature_ti_path = '';
    if (!empty($firma_ti) && strpos($firma_ti, 'data:image') === 0) {
        $firma_ti_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $firma_ti));
        $signature_ti_path = $folder_name . '/signature_ti.png';
        file_put_contents(__DIR__ . '/../uploads/' . $signature_ti_path, $firma_ti_data);
    }

    // Insertar en la base de datos
    $sql = "INSERT INTO vehicle_preoperation (
        user_id, driver_name, driver_document, date, time, plate, mileage,
        health_condition, tires, lights, horn, mirrors, fluids, leaks,
        brakes, windshield, retention, documents, prevention, dashboard_lights,
        general_observations, signature_user_path, signature_ti_path, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error al preparar la consulta: " . $conn->error);
    }

    // El string de tipos debe tener 24 caracteres: 1 int, 23 string
    $stmt->bind_param(
        "isssssisssssssssssssssss",
        $user_id, $driver_name, $driver_document, $date, $time, $plate, $mileage,
        $health_condition, $tires, $lights, $horn, $mirrors, $fluids, $leaks,
        $brakes, $windshield, $retention, $documents, $prevention, $dashboard_lights,
        $general_observations, $signature_user_path, $signature_ti_path, $status
    );

    if ($stmt->execute()) {
        echo "<script>alert('Formulario enviado correctamente.');window.location.href='../views/preoperation_vehicle.php?success=1';</script>";
        exit;
    } else {
        echo "<script>alert('Error al guardar el formulario: " . $stmt->error . "');window.location.href='../views/preoperation_vehicle.php?error=1';</script>";
        exit;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Acceso no autorizado.";
}
?>
