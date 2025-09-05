<?php
session_start();
include_once __DIR__ . '/../includes/conection.php';

// Recibe los datos del formulario de devolución
$acta_id = isset($_POST['acta_id']) ? intval($_POST['acta_id']) : 0;
$return_first_name = isset($_POST['return_first_name']) ? trim($_POST['return_first_name']) : '';
$return_last_name = isset($_POST['return_last_name']) ? trim($_POST['return_last_name']) : '';
$return_date = isset($_POST['return_date']) ? $_POST['return_date'] : null;
$return_condition = isset($_POST['return_condition']) ? trim($_POST['return_condition']) : '';
$return_observations = isset($_POST['return_observations']) ? trim($_POST['return_observations']) : '';
$firma_devolucion = isset($_POST['firma_devolucion']) ? $_POST['firma_devolucion'] : null;

// Validación básica
if ($acta_id <= 0 || !$return_date || !$return_condition) {
    exit('Datos incompletos');
}

// Obtener el documento y vehicle_id del acta para la carpeta y redirección
$stmt = $conn->prepare("SELECT vr.vehicle_id, u.document FROM vehicle_record vr JOIN users u ON vr.user_id = u.id WHERE vr.id = ?");
$stmt->bind_param("i", $acta_id);
$stmt->execute();
$stmt->bind_result($vehicle_id, $documento_usuario);
$stmt->fetch();
$stmt->close();

// Guardar la firma de devolución en la carpeta correspondiente
if ($firma_devolucion && strpos($firma_devolucion, 'data:image/png;base64,') === 0 && $documento_usuario) {
    $carpeta_firma = __DIR__ . '/../uploads/documents/vehicle_' . $documento_usuario;
    if (!is_dir($carpeta_firma)) {
        mkdir($carpeta_firma, 0777, true);
    }
    $firma_filename = 'firma_devolucion.png';
    $firma_data = base64_decode(str_replace('data:image/png;base64,', '', $firma_devolucion));
    file_put_contents($carpeta_firma . '/' . $firma_filename, $firma_data);
}

// Actualiza la tabla vehicle_record con los datos de devolución
$stmt = $conn->prepare("UPDATE vehicle_record SET 
    is_returned = 1,
    return_first_name = ?,
    return_last_name = ?,
    return_date = ?,
    return_condition = ?,
    return_observations = ?
    WHERE id = ?");
$stmt->bind_param(
    "sssssi",
    $return_first_name,
    $return_last_name,
    $return_date,
    $return_condition,
    $return_observations,
    $acta_id
);
$stmt->execute();
$stmt->close();

// Redirige de vuelta a la vista del acta con el vehicle_id correcto
header("Location: ../views/view_vehicle_acta.php?id=" . intval($vehicle_id) . "&acta_id=" . $acta_id);
exit;
