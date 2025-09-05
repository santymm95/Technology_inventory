<?php
session_start();
if (!isset($_SESSION['user'])) {
  header('Location: index.php');
  exit;
}
include_once __DIR__ . '/../includes/conection.php';

$success = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $tipo = $_POST['tipo'] ?? '';
  $internal_number = $_POST['internal_number'] ?? '';
  $brand = $_POST['brand'] ?? '';
  $model = $_POST['model'] ?? '';
  $serial = $_POST['serial'] ?? '';
  $purchase_date = $_POST['purchase_date'] ?? '';
  $provider = $_POST['provider'] ?? '';
  $specs = $_POST['specs'] ?? '';
  $host_name = $_POST['host_name'] ?? '';
  $fax_speed = $_POST['fax_speed'] ?? '';
  $duplex = $_POST['duplex'] ?? '';
  $connectivity = $_POST['connectivity'] ?? '';
  $front_panel = $_POST['front_panel'] ?? '';
  $features = $_POST['features'] ?? '';
  $filter_type = $_POST['filter_type'] ?? '';
  $print_speed = $_POST['print_speed'] ?? '';
  $ip_url = $_POST['ip_url'] ?? '';
  $voltage = $_POST['voltage'] ?? '';
  $ink_cartridges = $_POST['ink_cartridges'] ?? '';
  $parts = $_POST['parts'] ?? '';
  $parts_desc = $_POST['parts_desc'] ?? '';
  // Fotografía principal
  $photo_data = $_POST['photo_data'] ?? '';
  // Imagen de las partes (puede ser base64 o file, aquí base64)
  $parts_image = $_POST['parts_image'] ?? '';

  // Crear carpeta para el equipo
  $upload_dir = __DIR__ . '/../uploads/' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $internal_number);
  if (!is_dir($upload_dir) && $internal_number) {
    mkdir($upload_dir, 0777, true);
  }

  // Guardar fotografía principal (archivo o base64)
  $photo_filename = '';
  if (isset($_FILES['photo_file']) && $_FILES['photo_file']['error'] === UPLOAD_ERR_OK) {
    $photo_filename = $upload_dir . '/foto_principal.jpg';
    move_uploaded_file($_FILES['photo_file']['tmp_name'], $photo_filename);
  } elseif (!empty($_POST['photo_data'])) {
    $photo_data = $_POST['photo_data'];
    if (preg_match('/^data:image\/(\w+);base64,/', $photo_data, $type)) {
      $photo_data = substr($photo_data, strpos($photo_data, ',') + 1);
      $photo_data = base64_decode($photo_data);
      $photo_filename = $upload_dir . '/foto_principal.jpg';
      file_put_contents($photo_filename, $photo_data);
    }
  }

  // Guardar imagen de partes (archivo o base64)
  $parts_image_filename = '';
  if (isset($_FILES['parts_file']) && $_FILES['parts_file']['error'] === UPLOAD_ERR_OK) {
    $parts_image_filename = $upload_dir . '/imagen_partes.jpg';
    move_uploaded_file($_FILES['parts_file']['tmp_name'], $parts_image_filename);
  } elseif (!empty($_POST['parts_image'])) {
    $parts_image = $_POST['parts_image'];
    if (preg_match('/^data:image\/(\w+);base64,/', $parts_image, $type)) {
      $parts_image = substr($parts_image, strpos($parts_image, ',') + 1);
      $parts_image = base64_decode($parts_image);
      $parts_image_filename = $upload_dir . '/imagen_partes.jpg';
      file_put_contents($parts_image_filename, $parts_image);
    }
  }

  // Guardar solo la ruta relativa en la base de datos
  $photo_db = $photo_filename ? str_replace(__DIR__ . '/../', '', $photo_filename) : '';
  $parts_image_db = $parts_image_filename ? str_replace(__DIR__ . '/../', '', $parts_image_filename) : '';

  if ($tipo === 'Impresora') {
    if (
      $internal_number && $brand && $model && $serial && $purchase_date && $provider && $specs &&
      $host_name && $fax_speed && $duplex && $connectivity && $front_panel && $features && $filter_type &&
      $print_speed && $ip_url && $voltage && $ink_cartridges && $parts && $parts_desc && $photo_db && $parts_image_db
    ) {
      $stmt = $conn->prepare("INSERT INTO devices 
        (internal_number, brand, model, serial, purchase_date, provider, specs, device_type, host_name, fax_speed, duplex, connectivity, front_panel, features, filter_type, print_speed, ip_url, voltage, ink_cartridges, parts, parts_desc, photo, parts_image) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param(
        "sssssssssssssssssssssss",
        $internal_number, $brand, $model, $serial, $purchase_date, $provider, $specs, $tipo,
        $host_name, $fax_speed, $duplex, $connectivity, $front_panel, $features, $filter_type,
        $print_speed, $ip_url, $voltage, $ink_cartridges, $parts, $parts_desc, $photo_db, $parts_image_db
      );
      if ($stmt->execute()) {
        $success = true;
      } else {
        $error = "Error al registrar la impresora: " . $conn->error;
      }
      $stmt->close();
    } else {
      $error = "Todos los campos son obligatorios para impresoras.";
    }
  } else {
    // ...registro simple para otros tipos...
    if ($tipo && $internal_number && $brand && $model && $serial && $purchase_date) {
      $stmt = $conn->prepare("INSERT INTO devices (internal_number, brand, model, serial, purchase_date, device_type, photo) VALUES (?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("sssssss", $internal_number, $brand, $model, $serial, $purchase_date, $tipo, $photo_db);
      if ($stmt->execute()) {
        $success = true;
      } else {
        $error = "Error al registrar el equipo: " . $conn->error;
      }
      $stmt->close();
    } else {
      $error = "Todos los campos son obligatorios.";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registrar otro equipo</title>
  <link rel="stylesheet" href="../assets/css/dashboard.css">
  
</head>
<body>
  <?php include 'layout.php'; ?>
  
  <div class="main-content">
    <div class="welcome-card">
      <h1>Bienvenido, <?php echo $_SESSION['user']['first_name']; ?></h1>
      <p><strong>Dashboard principal.</strong> Explora el menú lateral para realizar acciones o crea tarjetas con
        accesos directos.</p>
    </div>

    <div class="shortcuts-container">
      <div class="shortcut-card" onclick="window.location.href='form_printer.php'">
        <i class="fas fa-print"></i>
        <span>Registrar Impresora</span>
      </div>

      <div class="shortcut-card" onclick="window.location.href='form_vehicle.php'">
        <i class="fas fa-car"></i>
        <span>Registrar Vehículo</span>
      </div>

      <div class="shortcut-card" onclick="window.location.href='register_air_conditioner.php'">
        <i class="fas fa-box"></i>
        <span>Registrar AC</span>
      </div>

      <div class="shortcut-card" onclick="window.location.href='cctv_form.php'">
        <i class="fas fa-video"></i>
        <span>Registrar CCTV</span>
      </div>

      <!-- Card para registrar NVR -->
      <div class="shortcut-card" onclick="window.location.href='registrar_nvr.php'">
        <i class="fas fa-server"></i>
        <span>Registrar NVR</span>
      </div>

      <div class="shortcut-card" onclick="window.location.href='views_divices.php'">
        <i class="fas fa-eye"></i>
        <span>Ver equipo</span>
      </div>
       
      <!-- <div class="shortcut-card" data-link="user_form.php">
        <i class="fas fa-user"></i>
        <span>Registrar usuario</span>
      </div> -->
</body>
</html>
