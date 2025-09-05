<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
include_once __DIR__ . '/../includes/conection.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo "Equipo no encontrado.";
    exit;
}

// Obtener datos actuales del equipo
$sql = "SELECT d.internal_number, d.serial, d.purchase_date, d.model_id, d.provider_type_id, d.processor_id, d.ram_id, d.storage_id, d.os_id, d.graphics_card_id, d.status_id
        FROM devices d WHERE d.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$device = $res->fetch_assoc();
$stmt->close();

if (!$device) {
    echo "Equipo no encontrado.";
    exit;
}

// Obtener accesorios actuales del equipo
$acc_sql = "SELECT accessory_id FROM device_accessories WHERE device_id = ?";
$acc_stmt = $conn->prepare($acc_sql);
$acc_stmt->bind_param("i", $id);
$acc_stmt->execute();
$acc_result = $acc_stmt->get_result();
$current_accessories = [];
while ($row = $acc_result->fetch_assoc()) {
    $current_accessories[] = $row['accessory_id'];
}
$acc_stmt->close();

// Cargar opciones para selects
function getOptions($conn, $table, $selectedId) {
    $sql = "SELECT id, name FROM $table ORDER BY name";
    $result = $conn->query($sql);
    $options = "";
    while ($row = $result->fetch_assoc()) {
        $sel = ($row['id'] == $selectedId) ? "selected" : "";
        $options .= "<option value=\"{$row['id']}\" $sel>" . htmlspecialchars($row['name']) . "</option>";
    }
    return $options;
}

// Obtener todos los accesorios disponibles
$all_acc_sql = "SELECT id, name FROM accessories ORDER BY name";
$all_acc_result = $conn->query($all_acc_sql);
$all_accessories = [];
while ($row = $all_acc_result->fetch_assoc()) {
    $all_accessories[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Equipo</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .edit-container {
            max-width: 650px;
            margin: 2.5rem auto;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.09);
            padding: 2.5rem 2rem;
        }
        .edit-container h2 {
            color: #2176ae;
            margin-bottom: 1.5rem;
        }
        .edit-form label {
            font-weight: 500;
            color: #215ba0;
            margin-bottom: 0.3em;
            display: block;
        }
        .edit-form input, .edit-form select {
            width: 100%;
            padding: 0.5em;
            margin-bottom: 1.2em;
            border-radius: 6px;
            border: 1px solid #c7d0db;
            font-size: 1em;
        }
        .edit-form button {
            background: #2176ae;
            color: #fff;
            padding: 0.7em 2em;
            border: none;
            border-radius: 7px;
            font-size: 1.08em;
            cursor: pointer;
            box-shadow: 0 1px 4px rgba(33,118,174,0.10);
        }
    </style>
</head>
<body>
    <?php include 'layout.php'; ?>
    <div class="main-content">
        <div class="edit-container">
            <h2>Editar información del equipo</h2>
            <form class="edit-form" method="post" action="../controllers/update_device.php">
                <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
                <label>Número Interno:
                    <input type="text" name="internal_number" value="<?= htmlspecialchars($device['internal_number']) ?>" required>
                </label>
                <label>Serial:
                    <input type="text" name="serial" value="<?= htmlspecialchars($device['serial']) ?>" required>
                </label>
                <label>Fecha de Compra:
                    <input type="date" name="purchase_date" value="<?= htmlspecialchars($device['purchase_date']) ?>" required>
                </label>
                <label>Modelo:
                    <select name="model_id" required>
                        <?= getOptions($conn, 'models', $device['model_id']) ?>
                    </select>
                </label>
                <label>Tipo de equipo:
                    <select name="provider_type_id" required>
                        <?= getOptions($conn, 'provider_types', $device['provider_type_id']) ?>
                    </select>
                </label>
                <label>Procesador:
                    <select name="processor_id" required>
                        <?= getOptions($conn, 'processors', $device['processor_id']) ?>
                    </select>
                </label>
                <label>RAM:
                    <select name="ram_id" required>
                        <?= getOptions($conn, 'rams', $device['ram_id']) ?>
                    </select>
                </label>
                <label>Almacenamiento:
                    <select name="storage_id" required>
                        <?= getOptions($conn, 'storages', $device['storage_id']) ?>
                    </select>
                </label>
                <label>Sistema Operativo:
                    <select name="os_id" required>
                        <?= getOptions($conn, 'operating_systems', $device['os_id']) ?>
                    </select>
                </label>
                <label>Tarjeta Gráfica:
                    <select name="graphics_card_id" required>
                        <?= getOptions($conn, 'graphics_cards', $device['graphics_card_id']) ?>
                    </select>
                </label>
                <label>Estado:
                    <select name="status_id" required>
                        <?= getOptions($conn, 'statuses', $device['status_id']) ?>
                    </select>
                </label>
                <label>Accesorios:</label>
                <div style="display:flex;flex-wrap:wrap;gap:1em 2em;margin-bottom:1.2em;">
                    <?php foreach ($all_accessories as $acc): ?>
                        <label style="font-weight:400;color:#215ba0;">
                            <input type="checkbox" name="accessories[]" value="<?= $acc['id'] ?>"
                                <?= in_array($acc['id'], $current_accessories) ? 'checked' : '' ?>>
                            <?= htmlspecialchars($acc['name']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <button type="submit">Guardar cambios</button>
            </form>
        </div>
    </div>
</body>
</html>
