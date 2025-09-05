<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../views/index.php');
    exit;
}
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cctv_id = intval($_POST['cctv_id']);
    $internal_number = trim($_POST['internal_number']);
    $date = $_POST['date'];
    $type = $_POST['type'];
    $description = $_POST['description'];

    // Determinar responsable y externo
    $is_externo = isset($_POST['externo']) && $_POST['externo'] == '1';
    if ($is_externo) {
        $responsible = isset($_POST['proveedor_numero']) ? $_POST['proveedor_numero'] : '';
        $external = $responsible;
    } else {
        $responsible = isset($_POST['responsible']) ? $_POST['responsible'] : 'Área de TI';
        $external = null;
    }

    // Manejo de la foto
    $photo_name = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = "../uploads/mantenimiento/" . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $internal_number) . "/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photo_name = $date . "_" . uniqid() . "." . $ext;
        $targetFile = $uploadDir . $photo_name;
        move_uploaded_file($_FILES['photo']['tmp_name'], $targetFile);
    }

    $stmt = $conn->prepare("INSERT INTO cctv_maintenance (cctv_id, internal_number, date, type, description, responsible, external, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "isssssss",
        $cctv_id,
        $internal_number,
        $date,
        $type,
        $description,
        $responsible,
        $external,
        $photo_name
    );
    $stmt->execute();
    $stmt->close();

    header("Location: ../views/list_cctv.php?internal_number=" . urlencode($internal_number));
    exit;
}
?>
