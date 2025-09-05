<?php
// Corrige la ruta del archivo de conexión
include_once __DIR__ . '/../includes/conection.php';

$baseUploadDir = realpath(__DIR__ . '/../../../uploads') . DIRECTORY_SEPARATOR;

$message = "";

// Función para obtener lista de opciones desde tabla (id, nombre)
function getOptions($conn, $table)
{
    $sql = "SELECT id, name FROM $table ORDER BY name ASC";
    $result = $conn->query($sql);
    $options = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $options[] = $row;
        }
    }
    return $options;
}

// Obtener datos para los selects
$modelOptions = getOptions($conn, 'models');
$providerOptions = getOptions($conn, 'provider_types');
$processorOptions = getOptions($conn, 'processors');
$ramOptions = getOptions($conn, 'rams');
$storageOptions = getOptions($conn, 'storages');
$osOptions = getOptions($conn, 'operating_systems');
$statusOptions = getOptions($conn, 'statuses');
$graphicsCardOptions = getOptions($conn, 'graphics_cards');
// Obtener marcas
$brandOptions = getOptions($conn, 'brands');

// Accesorios fijos
$accessoriesList = [
    ['id' => 1, 'name' => 'Teclado'],
    ['id' => 2, 'name' => 'Mouse'],
    ['id' => 3, 'name' => 'Cargador'],
    ['id' => 4, 'name' => 'Pantalla'],
];

// Obtener el último número interno registrado (solo el número)
$lastNumber = 0;
$result = $conn->query("SELECT internal_number FROM devices WHERE internal_number LIKE 'PCAI-%' ORDER BY id DESC LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    // Extraer el número después de 'PCAI-'
    if (preg_match('/PCAI-(\d+)/', $row['internal_number'], $matches)) {
        $lastNumber = intval($matches[1]);
    }
}
$nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $internal_number = trim($_POST['internal_number']);
    $serial = trim($_POST['serial']);
    $brand_id = intval($_POST['brand_id']);
    $model_id = intval($_POST['model_id']);
    $purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
    $provider_type_id = intval($_POST['provider_type_id']);
    $processor_id = intval($_POST['processor_id']);
    $ram_id = intval($_POST['ram_id']);
    $storage_id = intval($_POST['storage_id']);
    $os_id = intval($_POST['os_id']);
    $status_id = intval($_POST['status_id']);
    $graphics_card_id = intval($_POST['graphics_card_id']);
    $selected_accessories = isset($_POST['accessories']) ? $_POST['accessories'] : [];
    $device_value = floatval(str_replace(',', '', $_POST['device_value'])); // Nuevo campo

    if (empty($internal_number)) {
        $message = "El número interno es obligatorio.";
    } elseif (empty($purchase_date)) {
        $message = "La fecha de compra es obligatoria.";
    } elseif (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        $message = "Debe subir una foto válida.";
    } else {
        // Validar duplicados ANTES de cualquier guardado
        $dup_stmt = $conn->prepare("SELECT COUNT(*) as total FROM devices WHERE internal_number = ? OR serial = ?");
        $dup_stmt->bind_param("ss", $internal_number, $serial);
        $dup_stmt->execute();
        $dup_result = $dup_stmt->get_result();
        $dup_row = $dup_result->fetch_assoc();
        $dup_stmt->close();

        if ($dup_row['total'] > 0) {
            echo "<script>alert('El número interno o el serial ya están registrados.'); window.history.back();</script>";
            exit;
        }

        // Crear carpeta en uploads con el número interno
        $uploadsBase = realpath(__DIR__ . '/../uploads');
        $uploadDir = $uploadsBase . DIRECTORY_SEPARATOR . $internal_number;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Guardar la imagen con el nombre del número interno y su extensión
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $filename = $internal_number . '.' . $ext;
        $filepath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $filepath)) {
            $message = "✅ Equipo registrado correctamente. Foto almacenada en la carpeta correspondiente.";

            // Guardar solo los datos del equipo (sin la foto) en la base de datos:
            $stmt = $conn->prepare("INSERT INTO devices 
                (internal_number, serial, model_id, purchase_date, provider_type_id, processor_id, ram_id, storage_id, os_id, status_id, graphics_card_id, brand_id, device_value) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                "ssisiiiiiiiii",
                $internal_number,
                $serial,
                $model_id,
                $purchase_date,
                $provider_type_id,
                $processor_id,
                $ram_id,
                $storage_id,
                $os_id,
                $status_id,
                $graphics_card_id,
                $brand_id,
                $device_value // Nuevo campo
            );
            // Si no hay fecha, pasar null explícitamente
            if ($purchase_date === null) {
                $stmt->bind_param(
                    "ssisiiiiiiiii",
                    $internal_number,
                    $serial,
                    $model_id,
                    $purchase_date, // null
                    $provider_type_id,
                    $processor_id,
                    $ram_id,
                    $storage_id,
                    $os_id,
                    $status_id,
                    $graphics_card_id,
                    $brand_id,
                    $device_value // Nuevo campo
                );
            }

            if ($stmt->execute()) {
                $device_id = $conn->insert_id;
                // Guardar accesorios seleccionados (si hay)
                if (!empty($selected_accessories)) {
                    $acc_stmt = $conn->prepare("INSERT INTO device_accessories (device_id, accessory_id) VALUES (?, ?)");
                    foreach ($selected_accessories as $acc_id) {
                        $acc_stmt->bind_param("ii", $device_id, $acc_id);
                        $acc_stmt->execute();
                    }
                    $acc_stmt->close();
                }
                // Mostrar alerta y redirigir con JS
                echo "<script>alert('✅ Equipo registrado correctamente.');window.location.href='http://localhost/acema2/views/views_divices.php';</script>";
                exit;
            } else {
                $message = "❌ Error al registrar equipo: " . $stmt->error;
            }

            $stmt->close();
        } else {
            $message = "Error al guardar la foto en el servidor.";
        }
    }
}

// Endpoint AJAX para obtener modelos por marca
if (isset($_GET['get_models_by_brand']) && isset($_GET['brand_id'])) {
    header('Content-Type: application/json');
    $brand_id = intval($_GET['brand_id']);
    $stmt = $conn->prepare("SELECT id, name FROM models WHERE brand_id = ? ORDER BY name ASC");
    $stmt->bind_param("i", $brand_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $models = [];
    while ($row = $result->fetch_assoc()) {
        $models[] = $row;
    }
    $stmt->close();
    echo json_encode($models);
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../assets/css/dashboard.css" />
    <link rel="stylesheet" href="../assets/css/form.css" />

    <title>Registrar Equipo TI - ACEMA</title>
    <style>
       
    </style>
</head>

<body>
    <?php include 'layout.php'; ?>

<div class="header">
    <a href="ti.php" class="btn-back">Volver</a>
    <h2>Formulario para registrar Equipo</h2>
</div>
<?php if ($message): ?>
    <p><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<form action="register_device.php" method="post" enctype="multipart/form-data">
    <div class="form-column">
            <!-- Nuevo campo de marca -->
            <div class="form-group">
                <label for="brand_id">Marca</label>
                <select name="brand_id" id="brand_id" required>
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($brandOptions as $brand): ?>
                        <option value="<?= $brand['id'] ?>"><?= htmlspecialchars($brand['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="model_id">Modelo</label>
                <select name="model_id" id="model_id" required>
                    <option value="">-- Seleccione --</option>
                    <!-- Opciones iniciales, se actualizarán dinámicamente -->
                </select>
            </div>
            <div class="form-group">
            <label for="internal_number_num">Número Interno</label>
            <div style="display: flex; align-items: center;">
                <span style="font-weight: bold; margin-right: 6px;">PCAI-</span>
                <input 
                    type="text" 
                    name="internal_number_num" 
                    id="internal_number_num" 
                    required 
                    pattern="\d{1,}" 
                    placeholder="001" 
                    style="flex:1;"
                    autocomplete="off"
                    value="<?= htmlspecialchars($nextNumber) ?>"
                >
            </div>
            <small style="color: #215ba0;">Último registrado: PCAI-<?= str_pad($lastNumber, 3, '0', STR_PAD_LEFT) ?> | Siguiente sugerido: PCAI-<?= $nextNumber ?></small>
        </div>
            

            <div class="form-group">
                <label for="serial">Serial</label>
                <input type="text" name="serial" id="serial" required>
            </div>



            <div class="form-group">
                <label for="purchase_date">Fecha de Compra</label>
                <input type="date" name="purchase_date" id="purchase_date" required>
            </div>

            <div class="form-group">
                <label for="photo">Foto del Equipo</label>
                <input type="file" name="photo" id="photo" accept="image/*" required>
            </div>
            <div class="form-group">
                <label for="provider_type_id">Tipo</label>
                <select name="provider_type_id" id="provider_type_id" required>
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($providerOptions as $prov): ?>
                        <option value="<?= $prov['id'] ?>"><?= htmlspecialchars($prov['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Accesorios</label>
                <div style="display: flex; flex-wrap: wrap; gap: 1.2em;">
                    <?php foreach ($accessoriesList as $acc): ?>
                        <label style="font-weight:400; min-width: 120px;">
                            <input type="checkbox" name="accessories[]" value="<?= $acc['id'] ?>">
                            <?= htmlspecialchars($acc['name']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="form-column">


            <div class="form-group">
                <label for="processor_id">Procesador</label>
                <select name="processor_id" id="processor_id" required>
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($processorOptions as $proc): ?>
                        <option value="<?= $proc['id'] ?>"><?= htmlspecialchars($proc['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="ram_id">RAM</label>
                <select name="ram_id" id="ram_id" required>
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($ramOptions as $ram): ?>
                        <option value="<?= $ram['id'] ?>"><?= htmlspecialchars($ram['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="storage_id">Almacenamiento</label>
                <select name="storage_id" id="storage_id" required>
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($storageOptions as $sto): ?>
                        <option value="<?= $sto['id'] ?>"><?= htmlspecialchars($sto['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="graphics_card_id">Tarjeta Gráfica</label>
                <select name="graphics_card_id" id="graphics_card_id" required>
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($graphicsCardOptions as $gc): ?>
                        <option value="<?= $gc['id'] ?>"><?= htmlspecialchars($gc['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="os_id">Sistema Operativo</label>
                <select name="os_id" id="os_id" required>
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($osOptions as $os): ?>
                        <option value="<?= $os['id'] ?>"><?= htmlspecialchars($os['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="status_id">Estado</label>
                <select name="status_id" id="status_id" required>
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($statusOptions as $status): ?>
                        <option value="<?= $status['id'] ?>"><?= htmlspecialchars($status['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="device_value">Valor del equipo (COP)</label>
                <input 
                    type="text"
                    name="device_value"
                    id="device_value"
                    min="0"
                    placeholder="Ej: 2.000.000"
                    required
                    style="padding: 0.5rem 0.75rem; border: 1px solid #ccc; border-radius: 6px; font-size: 0.95rem; background-color: #fafafa; transition: border 0.2s;"
                    inputmode="numeric"
                    autocomplete="off"
                >
            </div>
        </div>

        <button type="submit">Registrar Equipo</button>
    </form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('device_value');
    input.addEventListener('input', function (e) {
        let value = this.value.replace(/\D/g, '');
        if (value) {
            this.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        } else {
            this.value = '';
        }
    });

    // Al enviar el formulario, quitar los puntos para enviar solo el número
    input.form.addEventListener('submit', function () {
        input.value = input.value.replace(/\./g, '');
    });

    // Script para actualizar modelos según la marca seleccionada
    const brandSelect = document.getElementById('brand_id');
    const modelSelect = document.getElementById('model_id');

    brandSelect.addEventListener('change', function () {
        const brandId = this.value;
        modelSelect.innerHTML = '<option value="">Cargando...</option>';
        if (!brandId) {
            modelSelect.innerHTML = '<option value="">-- Seleccione --</option>';
            return;
        }
        fetch('register_device.php?get_models_by_brand=1&brand_id=' + brandId)
            .then(response => response.json())
            .then(data => {
                let options = '<option value="">-- Seleccione --</option>';
                data.forEach(function (model) {
                    options += `<option value="${model.id}">${model.name}</option>`;
                });
                modelSelect.innerHTML = options;
            })
            .catch(() => {
                modelSelect.innerHTML = '<option value="">Error al cargar</option>';
            });
    });

    // Al enviar el formulario, une el prefijo con el número
    const form = document.querySelector('form');
    form.addEventListener('submit', function (e) {
        const numInput = document.getElementById('internal_number_num');
        // Crea un input oculto con el valor completo
        let fullInput = document.createElement('input');
        fullInput.type = 'hidden';
        fullInput.name = 'internal_number';
        fullInput.value = 'PCAI-' + numInput.value.trim();
        form.appendChild(fullInput);
        // Opcional: deshabilita el input original para evitar que se envíe
        numInput.disabled = true;
    });
});
</script>

</body>

</html>