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
    <title>Hoja de Vida Sistemas CCTV</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .cctv-form-container {
            max-width: 700px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.09);
            padding: 2.5rem 2rem;
        }
        .cctv-form-container h2 {
            color: #215ba0;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .cctv-form-container label {
            font-weight: 500;
            margin-bottom: 0.3em;
            display: block;
        }
        .cctv-form-container input,
        .cctv-form-container select,
        .cctv-form-container textarea {
            width: 100%;
            padding: 0.5em;
            border-radius: 6px;
            border: 1px solid #c7d0db;
            margin-bottom: 1em;
        }
        .cctv-form-container button {
            background: #2176ae;
            color: #fff;
            padding: 0.7em 2em;
            border: none;
            border-radius: 7px;
            font-size: 1.08em;
            cursor: pointer;
            box-shadow: 0 1px 4px rgba(33,118,174,0.10);
        }
       
        
           .btn-back {
            display: inline-block;
            padding: 5px 10px;
            background-color: #215ba0;
            color: white;
            text-decoration: none;
            border: none;
            margin-bottom: 5px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s ease, transform 0.2s ease;
            cursor: pointer;
        }

        .btn-back:hover {
            background-color: #2980b9;
            transform: scale(1.05);
        }

        label {
            font-weight: 100;
            color: #215ba0;
        }
    </style>
</head>
<body>
    <?php include 'layout.php'; ?>
    
    <div class="cctv-form-container">
        
        
        <form method="post" action="../controllers/register_cctv_controller.php" enctype="multipart/form-data" id="cctvForm">
            
        <h2 style="color:#2176ae;">Formulario de registro cámara de seguridad</h2>
            
        <label>N° Interno</label>
            <input type="text" name="internal_number" required>
            <label>Modelo</label>
            <input type="text" name="model" required>
            <label>Marca</label>
            <input type="text" name="brand" required>
            <label>Serial</label>
            <input type="text" name="serial" required>
            <label>Fecha de compra</label>
            <input type="date" name="purchase_date" required>
            <label>Proveedor</label>
            <input type="text" name="provider" required>
            <label>Fotografía</label>
            <input type="file" name="photo" accept="image/*">
            <h3 style="color:#2176ae;">Características Técnicas</h3>
            <label>Tipo</label>
            <input type="text" name="type">
            <label>Resolución</label>
            <input type="text" name="resolucion">
            <label>Píxeles</label>
            <input type="text" name="pixeles">
            <label>Conectividad</label>
            <input type="text" name="conectividad">
            <label>Sensor de movimiento</label>
            <select name="sensor_movimiento">
                <option value="">Seleccione</option>
                <option value="Sí">Sí</option>
                <option value="No">No</option>
            </select>
            <label>Ubicación</label>
            <input type="text" name="ubicacion">
            <div style="text-align:center;margin-top:1.5em;">
                <button type="submit">Guardar CCTV</button>
            </div>
        </form>
    </div>
    <script>
    document.getElementById('cctvForm').addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var formData = new FormData(form);
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(function(data) {
            alert('Registro exitoso');
            window.location.href = 'views_divices.php';
        })
        .catch(function() {
            alert('Ocurrió un error al registrar.');
        });
    });
    </script>
</body>
</html>
