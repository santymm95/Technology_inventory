<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}
include_once __DIR__ . '/../includes/conection.php';

// Recoger datos del formulario
$vehicle_id = intval($_POST['vehicle_id']);
$user_id = intval($_POST['user_id']);
$elementos = isset($_POST['elementos']) ? json_encode($_POST['elementos']) : null;
$firma_usuario = $_POST['firma_usuario'] ?? null;

// Obtener el documento del usuario responsable
$user_document = '';
$stmt = $conn->prepare("SELECT document FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($user_document);
$stmt->fetch();
$stmt->close();

// Crear carpeta uploads/documents/vehicle_{document}
$uploadsBase = realpath(__DIR__ . '/../uploads');
if (!$uploadsBase) {
    $uploadsBase = __DIR__ . '/../uploads';
    if (!is_dir($uploadsBase)) {
        mkdir($uploadsBase, 0777, true);
    }
}
$documentsBase = $uploadsBase . DIRECTORY_SEPARATOR . 'documents';
if (!is_dir($documentsBase)) {
    mkdir($documentsBase, 0777, true);
}
$vehicleDocDir = $documentsBase . DIRECTORY_SEPARATOR . 'vehicle_' . $user_document;
if (!is_dir($vehicleDocDir)) {
    mkdir($vehicleDocDir, 0777, true);
}

// Guardar firma de entrega del vehículo
$firma_entrega_path = null;
if ($firma_usuario) {
    $firma_entrega_path = $vehicleDocDir . DIRECTORY_SEPARATOR . 'firma_entrega_vehiculo_' . date('Ymd_His') . '.png';
    $data = explode(',', $firma_usuario);
    if (isset($data[1])) {
        file_put_contents($firma_entrega_path, base64_decode($data[1]));
        @chmod($firma_entrega_path, 0666);
    }
}

// Firma de devolución (si aplica)
$is_returned = isset($_POST['devolucion_check']) ? 1 : 0;
$return_first_name = $is_returned ? ($_POST['nombre_responsable_devolucion'] ?? '') : null;
$return_last_name = $is_returned ? ($_POST['apellido_responsable_devolucion'] ?? '') : null;
$return_date = $is_returned ? ($_POST['fecha_devolucion'] ?? null) : null;
$return_condition = $is_returned ? ($_POST['estado_devolucion'] ?? null) : null;
$return_observations = $is_returned ? ($_POST['observaciones_devolucion'] ?? null) : null;
$return_signature_path = null;
if ($is_returned && !empty($_POST['firma_recibidor'])) {
    $return_signature_path = $vehicleDocDir . DIRECTORY_SEPARATOR . 'firma_devolucion_vehiculo_' . date('Ymd_His') . '.png';
    $data = explode(',', $_POST['firma_recibidor']);
    if (isset($data[1])) {
        file_put_contents($return_signature_path, base64_decode($data[1]));
        @chmod($return_signature_path, 0666);
    }
}

// Insert into vehicle_record table
$stmt = $conn->prepare("INSERT INTO vehicle_record 
    (vehicle_id, user_id, delivered_items, responsible_signature, is_returned, return_first_name, return_last_name, return_date, return_condition, return_observations, return_signature, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
$stmt->bind_param(
    "iississssss",
    $vehicle_id,
    $user_id,
    $elementos,
    $firma_entrega_path,
    $is_returned,
    $return_first_name,
    $return_last_name,
    $return_date,
    $return_condition,
    $return_observations,
    $return_signature_path
);
$stmt->execute();
$stmt->close();

// Obtener la placa del vehículo para redirigir a la hoja de vida
$placa = '';
if ($vehicle_id) {
    $stmt = $conn->prepare("SELECT placa FROM vehicle WHERE id = ?");
    $stmt->bind_param("i", $vehicle_id);
    $stmt->execute();
    $stmt->bind_result($placa);
    $stmt->fetch();
    $stmt->close();
}

// Después de guardar en la base de datos
echo '<script>
    alert("✅ Registro exitoso. El acta de entrega ha sido registrada correctamente.");
    window.location.href = "../views/view_divices.php";
</script>';
exit;
