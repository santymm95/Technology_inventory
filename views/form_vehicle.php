<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Vehículo</title>
    <link rel="stylesheet" href="../assets/css/acta_view.css">
    <style>
        .form-container { max-width: 700px; margin: 2em auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); padding: 2em; }
        .form-group { margin-bottom: 1.2em; }
        label { display: block; margin-bottom: 0.4em; color: #215ba0; }
        input, select, textarea { width: 100%; padding: 0.5em; border: 1px solid #dbe4f3; border-radius: 5px; }
        button { background: #215ba0; color: #fff; border: none; padding: 0.7em 2em; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <?php include 'layout.php'; ?>
    <div class="form-container">
        <h2>Formulario de registro de vehículo</h2>
        <form method="post" action="../controllers/register_vehicle_controller.php" enctype="multipart/form-data" autocomplete="off">
            <div class="form-group">
                <label for="placa">Placa</label>
                <input type="text" name="placa" id="placa" required>
            </div>
            <div class="form-group">
                <label for="carroceria">Carrocería</label>
                <input type="text" name="carroceria" id="carroceria">
            </div>
            <div class="form-group">
                <label for="marca">Marca</label>
                <input type="text" name="marca" id="marca" required>
            </div>
            <div class="form-group">
                <label for="capacidad">Capacidad</label>
                <input type="text" name="capacidad" id="capacidad">
            </div>
            <div class="form-group">
                <label for="tipo_vehiculo">Tipo de Vehículo</label>
                <input type="text" name="tipo_vehiculo" id="tipo_vehiculo" required>
            </div>
            <div class="form-group">
                <label for="servicio">Servicio</label>
                <input type="text" name="servicio" id="servicio">
            </div>
            <div class="form-group">
                <label for="linea">Línea</label>
                <input type="text" name="linea" id="linea">
            </div>
            <div class="form-group">
                <label for="declaracion_importacion">Declaración de Importación</label>
                <input type="text" name="declaracion_importacion" id="declaracion_importacion">
            </div>
            <div class="form-group">
                <label for="modelo">Modelo</label>
                <input type="text" name="modelo" id="modelo" required>
            </div>
            <div class="form-group">
                <label for="cilindraje">Cilindraje</label>
                <input type="text" name="cilindraje" id="cilindraje">
            </div>
            <div class="form-group">
                <label for="registro_vin">Registro VIN</label>
                <input type="text" name="registro_vin" id="registro_vin">
            </div>
            <div class="form-group">
                <label for="potencia_hp">Potencia HP</label>
                <input type="text" name="potencia_hp" id="potencia_hp">
            </div>
            <div class="form-group">
                <label for="motor">Motor</label>
                <input type="text" name="motor" id="motor">
            </div>
            <div class="form-group">
                <label for="numero_chasis">Número de Chasis</label>
                <input type="text" name="numero_chasis" id="numero_chasis">
            </div>
            <div class="form-group">
                <label for="color">Color</label>
                <input type="text" name="color" id="color">
            </div>
            <div class="form-group">
                <label for="no_matricula">No Matrícula</label>
                <input type="text" name="no_matricula" id="no_matricula">
            </div>
            <div class="form-group">
                <label for="seguro_obligatorio">Seguro Obligatorio</label>
                <input type="text" name="seguro_obligatorio" id="seguro_obligatorio">
            </div>
            <div class="form-group">
                <label for="poliza_seguro">Póliza de Seguro</label>
                <input type="text" name="poliza_seguro" id="poliza_seguro">
            </div>
            <div class="form-group">
                <label for="tecnico_mecanica">Técnico Mecánica</label>
                <input type="text" name="tecnico_mecanica" id="tecnico_mecanica">
            </div>
            <div class="form-group">
                <label for="tarjeta_operacion">Tarjeta de Operación</label>
                <input type="text" name="tarjeta_operacion" id="tarjeta_operacion">
            </div>
            <div class="form-group">
                <label for="fecha_soat">Fecha SOAT</label>
                <input type="date" name="fecha_soat" id="fecha_soat">
            </div>
            <div class="form-group">
                <label for="fecha_tecnomecanica">Fecha Tecnomecánica</label>
                <input type="date" name="fecha_tecnomecanica" id="fecha_tecnomecanica">
            </div>
            <hr>
            <p>DATOS PROVEEDOR - SI EL VEHÍCULO ES ALQUILADO					</p>
            <div class="form-group">
                <label for="proveedor_nombre">Nombre/Razón Social</label>
                <input type="text" name="proveedor_nombre" id="proveedor_nombre">
            </div>
            <div class="form-group">
                <label for="proveedor_nit">NIT</label>
                <input type="text" name="proveedor_nit" id="proveedor_nit">
            </div>
            <div class="form-group">
                <label for="proveedor_representante">Representante Legal</label>
                <input type="text" name="proveedor_representante" id="proveedor_representante">
            </div>
            <div class="form-group">
                <label for="proveedor_direccion">Dirección</label>
                <input type="text" name="proveedor_direccion" id="proveedor_direccion">
            </div>
            <div class="form-group">
                <label for="proveedor_telefono">Teléfono</label>
                <input type="text" name="proveedor_telefono" id="proveedor_telefono">
            </div>
            <div class="form-group">
                <label for="proveedor_email">E-mail</label>
                <input type="email" name="proveedor_email" id="proveedor_email">
            </div>
            <div class="form-group">
                <label for="foto_vehiculo">Foto del vehículo</label>
                <input type="file" name="foto_vehiculo" id="foto_vehiculo" accept="image/*" required>
                <p style="color:#888;font-size:0.95em;margin-top:0.5em;">
                    La foto se subirá al servidor en una carpeta creada automáticamente con el número de la placa.
                </p>
            </div>
            <button type="submit">Registrar vehículo</button>
        </form>
    </div>
</body>
</html>
