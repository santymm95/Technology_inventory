<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../views/index.php');
    exit;
}
include_once __DIR__ . '/../includes/db.php';

// Recoger datos del formulario
$id = $_POST['id'] ?? null;
$placa = $_POST['placa'] ?? null;
$marca = $_POST['marca'] ?? null;
$modelo = $_POST['modelo'] ?? null;
$tipo_vehiculo = $_POST['tipo_vehiculo'] ?? null;
$carroceria = $_POST['carroceria'] ?? null;
$capacidad = $_POST['capacidad'] ?? null;
$servicio = $_POST['servicio'] ?? null;
$linea = $_POST['linea'] ?? null;
$declaracion_importacion = $_POST['declaracion_importacion'] ?? null;
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

// Actualizar datos en la base de datos (sin actualizar la foto)
$stmt = $conn->prepare("UPDATE vehicle SET 
    placa=?, carroceria=?, marca=?, capacidad=?, tipo_vehiculo=?, servicio=?, linea=?, declaracion_importacion=?, modelo=?, cilindraje=?, registro_vin=?, potencia_hp=?, motor=?, numero_chasis=?, color=?, no_matricula=?, seguro_obligatorio=?, poliza_seguro=?, tecnico_mecanica=?, tarjeta_operacion=?, proveedor_nombre=?, proveedor_nit=?, proveedor_representante=?, proveedor_direccion=?, proveedor_telefono=?, proveedor_email=?, fecha_soat=?, fecha_tecnomecanica=?
    WHERE id=?");
$stmt->bind_param(
    "ssssssssssssssssssssssssssssi",
    $placa, $carroceria, $marca, $capacidad, $tipo_vehiculo, $servicio, $linea, $declaracion_importacion, $modelo, $cilindraje, $registro_vin, $potencia_hp, $motor, $numero_chasis, $color, $no_matricula, $seguro_obligatorio, $poliza_seguro, $tecnico_mecanica, $tarjeta_operacion, $proveedor_nombre, $proveedor_nit, $proveedor_representante, $proveedor_direccion, $proveedor_telefono, $proveedor_email, $fecha_soat, $fecha_tecnomecanica, $id
);
$stmt->execute();
$stmt->close();

$_SESSION['success'] = "Vehículo actualizado correctamente.";
header("Location: ../views/vehicle_list.php?placa=" . urlencode($placa));
exit;
