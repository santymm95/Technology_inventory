<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../views/index.php');
    exit;
}

require_once '../includes/db.php'; // Usar el archivo correcto de conexión

// Crear tabla si no existe
$createTableSQL = "
CREATE TABLE IF NOT EXISTS cctv (
    id INT AUTO_INCREMENT PRIMARY KEY,
    internal_number VARCHAR(100) NOT NULL,
    model VARCHAR(100) NOT NULL,
    brand VARCHAR(100) NOT NULL,
    serial VARCHAR(100) NOT NULL,
    purchase_date DATE NOT NULL,
    provider VARCHAR(100) NOT NULL,
    photo VARCHAR(255),
    type VARCHAR(100),
    resolucion VARCHAR(100),
    pixeles VARCHAR(100),
    conectividad VARCHAR(100),
    sensor_movimiento VARCHAR(10),
    ubicacion VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
$conn->query($createTableSQL);

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $internal_number = $_POST['internal_number'];
    $model = $_POST['model'];
    $brand = $_POST['brand'];
    $serial = $_POST['serial'];
    $purchase_date = $_POST['purchase_date'];
    $provider = $_POST['provider'];
    $type = $_POST['type'];
    $resolucion = $_POST['resolucion'];
    $pixeles = $_POST['pixeles'];
    $conectividad = $_POST['conectividad'];
    $sensor_movimiento = $_POST['sensor_movimiento'];
    $ubicacion = $_POST['ubicacion'];

    // Manejo de la foto
    $photo_path = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $filename = uniqid() . '_' . basename($_FILES['photo']['name']);
        $targetFile = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFile)) {
            $photo_path = $filename;
        }
    }

    $stmt = $conn->prepare("INSERT INTO cctv (internal_number, model, brand, serial, purchase_date, provider, photo, type, resolucion, pixeles, conectividad, sensor_movimiento, ubicacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "sssssssssssss",
        $internal_number,
        $model,
        $brand,
        $serial,
        $purchase_date,
        $provider,
        $photo_path,
        $type,
        $resolucion,
        $pixeles,
        $conectividad,
        $sensor_movimiento,
        $ubicacion
    );
    $stmt->execute();
    $stmt->close();

    header('Location: ../views/cctv_form.php?success=1');
    exit;
}
?>
