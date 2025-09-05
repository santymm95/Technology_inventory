<?php
// Formulario para registrar impresora
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registrar Impresora</title>
  <link rel="stylesheet" href="../assets/css/acta_view.css">
  <style>
    .form-container { max-width: 700px; margin: 2em auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); padding: 2em; }
    .form-group { margin-bottom: 1.2em; }
    label { display: block; margin-bottom: 0.4em; color: #215ba0; }
    input, select, textarea { width: 100%; padding: 0.5em; border: 1px solid #dbe4f3; border-radius: 5px; }
    button { background: #215ba0; color: #fff; border: none; padding: 0.7em 2em; border-radius: 5px; cursor: pointer; }
    .success { color: green; margin-bottom: 1em; }
    .error { color: #b30000; margin-bottom: 1em; }
  </style>
</head>
<body>
  <?php include 'layout.php'; ?>
  <div class="form-container">
    <h2>Formulario de registro de impresora</h2>
    <?php if (isset($_GET['success'])): ?>
      <div class="success">Impresora registrada correctamente.</div>
    <?php elseif (isset($_GET['error'])): ?>
      <div class="error">
        <?php if ($_GET['error'] === 'required'): ?>
          Todos los campos son obligatorios para impresoras. Por favor, verifica que todos los campos estén completos y vuelve a intentarlo.
        <?php else: ?>
          <?= htmlspecialchars($_GET['error']) ?>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    <form method="post" action="../controllers/register_printer_controller.php" enctype="multipart/form-data" autocomplete="off">
      <div class="form-group">
        <label for="internal_number">Número interno</label>
        <input type="text" name="internal_number" id="internal_number" required>
      </div>
      <div class="form-group">
        <label for="brand">Marca</label>
        <input type="text" name="brand" id="brand" required>
      </div>
      <div class="form-group">
        <label for="model">Modelo</label>
        <input type="text" name="model" id="model" required>
      </div>
      <div class="form-group">
        <label for="serial">Serial</label>
        <input type="text" name="serial" id="serial" required>
      </div>
      <div class="form-group">
        <label for="purchase_date">Fecha de compra</label>
        <input type="date" name="purchase_date" id="purchase_date" required>
      </div>
      <div class="form-group">
        <label for="provider">Proveedor</label>
        <input type="text" name="provider" id="provider" required>
      </div>
     
      <div class="form-group">
        <label for="photo_file">Fotografía (subir archivo)</label>
        <input type="file" name="photo_file" id="photo_file" accept="image/*" required>
      </div>
      <div class="form-group">
        <label>Especificaciones técnicas</label>
      </div>
      <div class="form-group">
        <label for="host_name">Nombre del host</label>
        <input type="text" name="host_name" id="host_name" required>
      </div>
      <div class="form-group">
        <label for="fax_speed">Velocidad de fax</label>
        <input type="text" name="fax_speed" id="fax_speed" required>
      </div>
      <div class="form-group">
        <label for="duplex">Duplex</label>
        <input type="text" name="duplex" id="duplex" required>
      </div>
      <div class="form-group">
        <label for="connectivity">Conectividad</label>
        <input type="text" name="connectivity" id="connectivity" required>
      </div>
      <div class="form-group">
        <label for="front_panel">Panel frontal</label>
        <input type="text" name="front_panel" id="front_panel" required>
      </div>
      <div class="form-group">
        <label>Características</label>
      </div>
      <div class="form-group">
        <label for="filter_type">Tipo de filtrado</label>
        <input type="text" name="filter_type" id="filter_type" required>
      </div>
      <div class="form-group">
        <label for="print_speed">Velocidad de impresión</label>
        <input type="text" name="print_speed" id="print_speed" required>
      </div>
      <div class="form-group">
        <label for="ip_url">URL IP</label>
        <input type="text" name="ip_url" id="ip_url" required>
      </div>
      <div class="form-group">
        <label for="voltage">Voltaje eléctrico</label>
        <input type="text" name="voltage" id="voltage" required>
      </div>
      <div class="form-group">
        <label for="ink_cartridges">Cartuchos de tinta</label>
        <input type="text" name="ink_cartridges" id="ink_cartridges" required>
      </div>
      <div class="form-group">
        <label for="parts">Partes del equipo</label>
        <input type="text" name="parts" id="parts" required>
      </div>
      <div class="form-group">
        <label for="parts_file">Imagen de las partes (subir archivo)</label>
        <input type="file" name="parts_file" id="parts_file" accept="image/*" required>
      </div>
      <div class="form-group">
        <label for="parts_desc">Descripción de las partes</label>
        <textarea name="parts_desc" id="parts_desc" required></textarea>
      </div>
      <input type="hidden" name="tipo" value="Impresora">
      <button type="submit">Registrar impresora</button>
    </form>
  </div>
</body>
</html>
