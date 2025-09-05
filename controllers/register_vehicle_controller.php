<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../views/index.php');
    exit;
}
include_once __DIR__ . '/../includes/db.php';

// Obtener datos del formulario (agrega todos los campos que necesitas)
$placa = $_POST['placa'] ?? null;
$carroceria = $_POST['carroceria'] ?? null;
$marca = $_POST['marca'] ?? null;
$capacidad = $_POST['capacidad'] ?? null;
$tipo_vehiculo = $_POST['tipo_vehiculo'] ?? null;
$servicio = $_POST['servicio'] ?? null;
$linea = $_POST['linea'] ?? null;
$declaracion_importacion = $_POST['declaracion_importacion'] ?? null;
$modelo = $_POST['modelo'] ?? null;
$cilindraje = $_POST['cilindraje'] ?? null;
$registro_vin = $_POST['registro_vin'] ?? null;
$potencia_hp = $_POST['potencia_hp'] ?? null;
$motor = $_POST['motor'] ?? null;
$numero_chasis = $_POST['numero_chasis'] ?? null;
$color = $_POST['color'] ?? null;
$no_matricula = $_POST['no_matricula'] ?? null;
$seguro_obligatorio = $_POST['seguro_obligatorio'] ?? null;
$poliza_seguro = $_POST['poliza_seguro'] ?? null;
$tecnico_mecanica = $_POST['tecnico_mecanica'] ?? null;
$tarjeta_operacion = $_POST['tarjeta_operacion'] ?? null;
$proveedor_nombre = $_POST['proveedor_nombre'] ?? null;
$proveedor_nit = $_POST['proveedor_nit'] ?? null;
$proveedor_representante = $_POST['proveedor_representante'] ?? null;
$proveedor_direccion = $_POST['proveedor_direccion'] ?? null;
$proveedor_telefono = $_POST['proveedor_telefono'] ?? null;
$proveedor_email = $_POST['proveedor_email'] ?? null;
$fecha_soat = $_POST['fecha_soat'] ?? null;
$fecha_tecnomecanica = $_POST['fecha_tecnomecanica'] ?? null;
$foto_vehiculo = null;

// Validar datos mínimos
if (!$placa || !$marca || !$modelo || !$tipo_vehiculo) {
    $_SESSION['error'] = "Faltan datos obligatorios.";
    header("Location: ../views/vehicle_form.php");
    exit;
}

// Crear carpeta para la foto si no existe
$dir = __DIR__ . "/../uploads/" . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $placa);
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

// Guardar la foto del vehículo si se subió
if (isset($_FILES['foto_vehiculo']) && $_FILES['foto_vehiculo']['error'] === UPLOAD_ERR_OK) {
    $tmp_name = $_FILES['foto_vehiculo']['tmp_name'];
    $ext = pathinfo($_FILES['foto_vehiculo']['name'], PATHINFO_EXTENSION);
    $filename = "foto_vehiculo." . $ext;
    $destino = $dir . "/" . $filename;
    if (move_uploaded_file($tmp_name, $destino)) {
        $foto_vehiculo = "uploads/" . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $placa) . "/" . $filename;
    }
}

// Insertar el vehículo en la base de datos (todos los campos)
$stmt = $conn->prepare("INSERT INTO vehicle (
    placa, carroceria, marca, capacidad, tipo_vehiculo, servicio, linea, declaracion_importacion, modelo, cilindraje, registro_vin, potencia_hp, motor, numero_chasis, color, no_matricula, seguro_obligatorio, poliza_seguro, tecnico_mecanica, tarjeta_operacion, proveedor_nombre, proveedor_nit, proveedor_representante, proveedor_direccion, proveedor_telefono, proveedor_email, fecha_soat, fecha_tecnomecanica, foto_vehiculo
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

// 29 signos de pregunta, 29 variables, 29 letras en el string de tipos
$stmt->bind_param(
    "sssssssssssssssssssssssssssss",
    $placa, $carroceria, $marca, $capacidad, $tipo_vehiculo, $servicio, $linea, $declaracion_importacion, $modelo, $cilindraje, $registro_vin, $potencia_hp, $motor, $numero_chasis, $color, $no_matricula, $seguro_obligatorio, $poliza_seguro, $tecnico_mecanica, $tarjeta_operacion, $proveedor_nombre, $proveedor_nit, $proveedor_representante, $proveedor_direccion, $proveedor_telefono, $proveedor_email, $fecha_soat, $fecha_tecnomecanica, $foto_vehiculo
);
$stmt->execute();
$stmt->close();

$_SESSION['success'] = "Vehículo registrado correctamente.";
header("Location: ../views/vehicle_list.php");
exit;
