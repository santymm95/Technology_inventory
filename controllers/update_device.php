<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../views/index.php');
    exit;
}
include_once __DIR__ . '/../includes/conection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $internal_number = isset($_POST['internal_number']) ? trim($_POST['internal_number']) : '';
    $serial = isset($_POST['serial']) ? trim($_POST['serial']) : '';
    $purchase_date = isset($_POST['purchase_date']) ? $_POST['purchase_date'] : '';
    $model_id = isset($_POST['model_id']) ? intval($_POST['model_id']) : null;
    $provider_type_id = isset($_POST['provider_type_id']) ? intval($_POST['provider_type_id']) : null;
    $processor_id = isset($_POST['processor_id']) ? intval($_POST['processor_id']) : null;
    $ram_id = isset($_POST['ram_id']) ? intval($_POST['ram_id']) : null;
    $storage_id = isset($_POST['storage_id']) ? intval($_POST['storage_id']) : null;
    $os_id = isset($_POST['os_id']) ? intval($_POST['os_id']) : null;
    $graphics_card_id = isset($_POST['graphics_card_id']) ? intval($_POST['graphics_card_id']) : null;
    $status_id = isset($_POST['status_id']) ? intval($_POST['status_id']) : null;
    $accessories = isset($_POST['accessories']) && is_array($_POST['accessories']) ? $_POST['accessories'] : [];

    if (
        $id > 0 && $internal_number && $serial && $purchase_date &&
        $model_id && $provider_type_id && $processor_id && $ram_id &&
        $storage_id && $os_id && $graphics_card_id && $status_id
    ) {
        $sql = "UPDATE devices SET 
            internal_number = ?, 
            serial = ?, 
            purchase_date = ?, 
            model_id = ?, 
            provider_type_id = ?, 
            processor_id = ?, 
            ram_id = ?, 
            storage_id = ?, 
            os_id = ?, 
            graphics_card_id = ?, 
            status_id = ?
            WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sssiiiiiiiii",
            $internal_number,
            $serial,
            $purchase_date,
            $model_id,
            $provider_type_id,
            $processor_id,
            $ram_id,
            $storage_id,
            $os_id,
            $graphics_card_id,
            $status_id,
            $id
        );
        $stmt->execute();
        $stmt->close();

        // Actualizar accesorios
        // 1. Eliminar todos los accesorios actuales
        $conn->query("DELETE FROM device_accessories WHERE device_id = $id");
        // 2. Insertar los nuevos accesorios seleccionados
        if (!empty($accessories)) {
            $insert_stmt = $conn->prepare("INSERT INTO device_accessories (device_id, accessory_id) VALUES (?, ?)");
            foreach ($accessories as $acc_id) {
                $acc_id = intval($acc_id);
                $insert_stmt->bind_param("ii", $id, $acc_id);
                $insert_stmt->execute();
            }
            $insert_stmt->close();
        }

        header("Location: ../views/device_profile.php?id=" . $id);
        exit;
    } else {
        header("Location: ../views/edit_device.php?id=" . $id . "&error=1");
        exit;
    }
} else {
    header('Location: ../views/ti.php');
    exit;
}
