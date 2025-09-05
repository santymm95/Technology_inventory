<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
include_once __DIR__ . '/../includes/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo "ID de vehículo no válido.";
    exit;
}

$stmt = $conn->prepare("SELECT * FROM vehicle WHERE id = ?");
if (!$stmt) {
    echo "Error en la preparación de la consulta: " . $conn->error;
    exit;
}
$stmt->bind_param("i", $id);
if (!$stmt->execute()) {
    echo "Error al ejecutar la consulta: " . $stmt->error;
    exit;
}
$result = $stmt->get_result();
if (!$result) {
    echo "Error al obtener el resultado: " . $stmt->error;
    exit;
}
$vehicle = $result->fetch_assoc();
$stmt->close();

if (!$vehicle) {
    echo "Vehículo no encontrado.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Vehículo</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .edit-form-container {
            max-width: 600px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.09);
            padding: 2.5rem 2rem;
        }
        .edit-form-container h2 {
            color: #215ba0;
            margin-bottom: 1.5rem;
        }
        .edit-form-container label {
            font-weight: 500;
            margin-bottom: 0.3em;
            display: block;
        }
        .edit-form-container input,
        .edit-form-container select {
            width: 100%;
            padding: 0.5em;
            border-radius: 6px;
            border: 1px solid #c7d0db;
            margin-bottom: 1em;
        }
        .edit-form-container button {
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
    <div class="edit-form-container">
        <h2>Editar Vehículo</h2>
        <form method="post" action="../controllers/update_vehicle_controller.php" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= htmlspecialchars($vehicle['id']) ?>">
            <label>Placa</label>
            <input type="text" name="placa" value="<?= htmlspecialchars($vehicle['placa']) ?>" required>
            <label>Marca</label>
            <input type="text" name="marca" value="<?= htmlspecialchars($vehicle['marca']) ?>" required>
            <label>Modelo</label>
            <input type="text" name="modelo" value="<?= htmlspecialchars($vehicle['modelo']) ?>" required>
            <label>Tipo de Vehículo</label>
            <input type="text" name="tipo_vehiculo" value="<?= htmlspecialchars($vehicle['tipo_vehiculo']) ?>" required>
            <label>Carrocería</label>
            <input type="text" name="carroceria" value="<?= htmlspecialchars($vehicle['carroceria']) ?>">
            <label>Capacidad</label>
            <input type="text" name="capacidad" value="<?= htmlspecialchars($vehicle['capacidad']) ?>">
            <label>Servicio</label>
            <input type="text" name="servicio" value="<?= htmlspecialchars($vehicle['servicio']) ?>">
            <label>Línea</label>
            <input type="text" name="linea" value="<?= htmlspecialchars($vehicle['linea']) ?>">
            <label>Declaración de Importación</label>
            <input type="text" name="declaracion_importacion" value="<?= htmlspecialchars($vehicle['declaracion_importacion']) ?>">
            <label>Cilindraje</label>
            <input type="text" name="cilindraje" value="<?= htmlspecialchars($vehicle['cilindraje']) ?>">
            <label>Registro VIN</label>
            <input type="text" name="registro_vin" value="<?= htmlspecialchars($vehicle['registro_vin']) ?>">
            <label>Potencia HP</label>
            <input type="text" name="potencia_hp" value="<?= htmlspecialchars($vehicle['potencia_hp']) ?>">
            <label>Motor</label>
            <input type="text" name="motor" value="<?= htmlspecialchars($vehicle['motor']) ?>">
            <label>Número de Chasis</label>
            <input type="text" name="numero_chasis" value="<?= htmlspecialchars($vehicle['numero_chasis']) ?>">
            <label>Color</label>
            <input type="text" name="color" value="<?= htmlspecialchars($vehicle['color']) ?>">
            <label>No Matrícula</label>
            <input type="text" name="no_matricula" value="<?= htmlspecialchars($vehicle['no_matricula']) ?>">
            <label>Seguro Obligatorio</label>
            <input type="text" name="seguro_obligatorio" value="<?= htmlspecialchars($vehicle['seguro_obligatorio']) ?>">
            <label>Póliza de Seguro</label>
            <input type="text" name="poliza_seguro" value="<?= htmlspecialchars($vehicle['poliza_seguro']) ?>">
            <label>Técnico Mecánica</label>
            <input type="text" name="tecnico_mecanica" value="<?= htmlspecialchars($vehicle['tecnico_mecanica']) ?>">
            <label>Tarjeta de Operación</label>
            <input type="text" name="tarjeta_operacion" value="<?= htmlspecialchars($vehicle['tarjeta_operacion']) ?>">
            <label>Proveedor - Nombre/Razón Social</label>
            <input type="text" name="proveedor_nombre" value="<?= htmlspecialchars($vehicle['proveedor_nombre']) ?>">
            <label>Proveedor - NIT</label>
            <input type="text" name="proveedor_nit" value="<?= htmlspecialchars($vehicle['proveedor_nit']) ?>">
            <label>Proveedor - Representante Legal</label>
            <input type="text" name="proveedor_representante" value="<?= htmlspecialchars($vehicle['proveedor_representante']) ?>">
            <label>Proveedor - Dirección</label>
            <input type="text" name="proveedor_direccion" value="<?= htmlspecialchars($vehicle['proveedor_direccion']) ?>">
            <label>Proveedor - Teléfono</label>
            <input type="text" name="proveedor_telefono" value="<?= htmlspecialchars($vehicle['proveedor_telefono']) ?>">
            <label>Proveedor - E-mail</label>
            <input type="email" name="proveedor_email" value="<?= htmlspecialchars($vehicle['proveedor_email']) ?>">
            <label>Fecha SOAT</label>
            <input type="date" name="fecha_soat" value="<?= htmlspecialchars($vehicle['fecha_soat']) ?>">
            <label>Fecha Tecnomecánica</label>
            <input type="date" name="fecha_tecnomecanica" value="<?= htmlspecialchars($vehicle['fecha_tecnomecanica']) ?>">
            <label>Foto del vehículo (opcional, reemplaza la actual)</label>
            <input type="file" name="foto_vehiculo" accept="image/*">
            <div style="text-align:center;margin-top:1.5em;">
                <button type="submit">Guardar cambios</button>
            </div>
        </form>
    </div>
</body>
</html>
