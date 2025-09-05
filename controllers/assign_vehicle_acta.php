<?php
session_start();
include_once __DIR__ . '/../includes/conection.php';

// Recibe datos del formulario
$vehicle_id = isset($_POST['vehicle_id']) ? intval($_POST['vehicle_id']) : 0;
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$elementos = isset($_POST['elementos']) ? $_POST['elementos'] : [];
$firma_entrega = isset($_POST['firma_entrega']) ? $_POST['firma_entrega'] : null;

// Validación básica
if ($vehicle_id <= 0 || $user_id <= 0 || !$firma_entrega) {
    die("Datos incompletos.");
}

// Serializar elementos entregados
$delivered_items = json_encode($elementos);

// Insertar acta en la tabla vehicle_record
$stmt = $conn->prepare("INSERT INTO vehicle_record (vehicle_id, user_id, delivered_items, created_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("iis", $vehicle_id, $user_id, $delivered_items);
$stmt->execute();
$acta_id = $stmt->insert_id;
$stmt->close();

// Obtener documento del usuario para la carpeta
$stmt = $conn->prepare("SELECT document FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($documento_usuario);
$stmt->fetch();
$stmt->close();

// Guardar la firma en la carpeta con nombre corto: vehicle_{documento}
if ($firma_entrega && strpos($firma_entrega, 'data:image/png;base64,') === 0 && $documento_usuario) {
    $carpeta_firma = __DIR__ . '/../uploads/documents/vehicle_' . $documento_usuario;
    if (!is_dir($carpeta_firma)) {
        mkdir($carpeta_firma, 0777, true);
    }
    $firma_filename = 'firma_entrega.png';
    $firma_data = base64_decode(str_replace('data:image/png;base64,', '', $firma_entrega));
    file_put_contents($carpeta_firma . '/' . $firma_filename, $firma_data);
}

// Redirigir a la vista del acta creada
header("Location: ../views/view_vehicle_acta.php?id=$vehicle_id&acta_id=$acta_id");
exit;
