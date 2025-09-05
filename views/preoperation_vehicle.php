<?php
// ...puedes agregar aquí session_start() si es necesario...
include_once __DIR__ . '/../includes/conection.php';
// Obtener usuarios para el select
$users = [];
$res = $conn->query("SELECT id, first_name, last_name, document FROM users ORDER BY first_name, last_name");
while ($row = $res->fetch_assoc()) {
    $users[] = $row;
}
// Obtener placas de vehículos registrados
$placas = [];
$res2 = $conn->query("SELECT id, placa, marca, modelo FROM vehicle ORDER BY placa");
while ($row = $res2->fetch_assoc()) {
    $placas[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Formulario Prechequeo Vehículo</title>
  <link rel="stylesheet" href="../assets/css/dashboard.css">
  <style>
       /* === Container principal === */
.acta-container {
  max-width: 960px;
  margin: 2rem auto;
  padding: 2.5rem;
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 5px 24px rgba(0, 0, 0, 0.07);
}

/* === Etiquetas === */
label {
  font-weight: 600;
  color: #215ba0;
  display: block;
  margin-bottom: 0.5em;
}

/* === Inputs más grandes y cuadraditos === */
input[type="text"],
input[type="number"],
input[type="date"],
input[type="time"],
select,
textarea {
  width: 100%;
  padding: 0.2em 1em;
  font-size: 1.1em;
  border: 2px solid #cdd6e1;
  border-radius: 6px;
  background-color: #f5f7fa;
  margin-bottom: 1.5em;
  transition: border-color 0.3s, background 0.3s;
}
input:focus,
select:focus,
textarea:focus {
  outline: none;
  border-color: #215ba0;
  background: #ffffff;
  box-shadow: 0 0 6px rgba(33, 91, 160, 0.25);
}

/* === Radios y checks grandes === */
input[type="radio"],
input[type="checkbox"] {
  accent-color: #215ba0;
  transform: scale(1.6);
  margin-right: 0.4em;
  vertical-align: middle;
}

/* === Botones modernos === */
button[type="submit"],
.btn-primary {
  background:#215ba0;
  color: #fff;
  font-weight: 600;
  font-size: 1.1em;
  border: none;
  border-radius: 6px;
  padding: 1em 2.2em;
  margin-top: 1.5em;
  cursor: pointer;
  box-shadow: 0 3px 10px rgba(33, 91, 160, 0.15);
  transition: all 0.25s ease;
  margin-bottom: 1.5em;
}
button[type="submit"]:hover,
.btn-primary:hover {
  background:  #195f8d;
 
}

/* === Firma === */
canvas {
  border: 2px dashed #ccc;
  border-radius: 6px;
  background: #fff;
  width: 100%;
  max-width: 350px;
  height: 90px;
  margin-bottom: 0.5em;
}

/* === Responsive === */
@media (max-width: 768px) {
  .acta-container {
    padding: 1.2rem;
  }
  .firma-section {
    flex-direction: column;
    align-items: center;
  }
}

    </style>
</head>
<body>
  <?php include 'layout.php'; ?>
  <div class="acta-container">
    <table class="pdf-header-table" style="width:100%;margin-bottom:2em;">
      <tr>
          <td class="pdf-header-logo" style="width:120px;">
              <img src="../assets/images/logo.png" class="pdf-header-logo-img" style="max-width:100px;">
          </td>
          <td class="pdf-header-title" style="text-align:center;font-size:1.2em;font-weight:bold;">
              FORMULARIO DE PRECHEQUEO OPERACIONAL <br>
              AUTOMÓVIL O PICK-UP
          </td>
          <td class="pdf-header-info" style="text-align:right;">
              <table class="pdf-header-info-table" style="font-size:0.95em;">
                  <tr>
                      <td><strong>Código:</strong> ACM-ADM-TI-FO-004</td>
                  </tr>
                  <tr>
                      <td><strong>Versión:</strong> 002</td>
                  </tr>
                  <tr>
                      <td><strong>Fecha:</strong> 06-06-2025</td>
                  </tr>
              </table>
          </td>
      </tr>
    </table>
  
    <form action="../controllers/register_preoperation_vehicle.php" method="post" style="max-width:600px;margin:0 auto;" id="preop-form" target="_self" autocomplete="off">
      <div style="font-size:0.98em;color:#215ba0;margin-bottom:1.5em; text-align:justify; line-height:1.5em;">
          <strong>Instrucciones:</strong><br>
          Este formulario debe ser completado antes de usar algún vehículo de la empresa. 
          <br><br>
          <strong>Objetivo:</strong> Verificar las condiciones del vehículo y del usuario para garantizar un uso seguro.
          <br><br>
          <strong>Importante:</strong>
        Este formato permite revisar el vehículo antes de usarlo, para prevenir fallas o accidentes. Verifica tu estado de salud y las condiciones del vehículo. Si algún ítem no cumple, informa al responsable inmediato para definir si el vehículo puede ser utilizado.
      </div>
      <div style="margin-bottom:2em;">
          <label for="user_id" style="font-weight:500;color:#215ba0;">Usuario responsable:</label>
          <select name="user_id" id="user_id" required onchange="fillUserData()"
              style="width:100%;padding:0.5em;border-radius:6px;border:1px solid #c7d0db;">
              <option value="">Selecciona un usuario</option>
              <?php foreach ($users as $u): ?>
                  <option value="<?= $u['id'] ?>">
                      <?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name'] . ' (' . $u['document'] . ')') ?>
                  </option>
              <?php endforeach; ?>
          </select>
      </div>
      <h3>1. Información General del Vehículo</h3>
      <label>Nombre del conductor:<br>
        <input type="text" name="driver_name" id="nombre_conductor" required class="input-text">
      </label><br>
      <label>Documento de identidad:<br>
        <input type="text" name="driver_document" id="documento" required class="input-text">
      </label><br>
      <label>Fecha:<br>
        <input type="date" name="date" id="fecha" required class="input-text">
      </label><br>
      <label>Hora:<br>
        <input type="time" name="time" id="hora" required class="input-text">
      </label><br>
      <label>Placa:<br>
        <select name="plate" id="placa" required class="input-text" onchange="mostrarDatosVehiculo()">
          <option value="">Selecciona una placa</option>
          <?php foreach ($placas as $v): ?>
            <option value="<?= htmlspecialchars($v['placa']) ?>"
              data-marca="<?= htmlspecialchars($v['marca']) ?>"
              data-modelo="<?= htmlspecialchars($v['modelo']) ?>">
              <?= htmlspecialchars($v['placa']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label><br>
      <div id="info-vehiculo" style="display:none; margin-bottom:1.5em; background:#f7f9fb; border-radius:8px; padding:1em 1em 1em 1em; box-shadow:0 1px 6px rgba(33,91,160,0.07);">
        <div style="display:flex;align-items:center;gap:1.5em;">
          <div>
            <img id="foto-vehiculo" src="../assets/img/no-image.png" alt="Foto vehículo" style="width:90px;height:60px;object-fit:cover;border-radius:6px;border:1px solid #eaeaea;">
          </div>
          <div>
            <div><strong>Placa:</strong> <span id="info-placa"></span></div>
            <div><strong>Marca:</strong> <span id="info-marca"></span></div>
            <div><strong>Modelo:</strong> <span id="info-modelo"></span></div>
          </div>
        </div>
      </div>
      <label>Kilometraje:<br>
        <input type="number" name="mileage" required class="input-text" placeholder="Sin punto ni comas">
      </label><br><br>

      <h3>2. Condiciones del Usuario</h3>
      <p>¿Estás en condiciones de salud y descanso para usar el vehículo?</p>
      <label><input type="radio" name="health_condition" value="Sí" required> Sí</label>
      <label><input type="radio" name="health_condition" value="No"> No</label><br><br>

      <h3>3. Condiciones del Vehículo</h3>

      <p><strong>Estado de las llantas:</strong></p>
      <label><input type="radio" name="tires" value="Sí" required> Sí</label>
      <label><input type="radio" name="tires" value="No"> No</label><br><br>

      <p><strong>Luces:</strong></p>
      <label><input type="radio" name="lights" value="Sí" required> Sí</label>
      <label><input type="radio" name="lights" value="No"> No</label><br><br>

      <p><strong>Bocina:</strong></p>
      <label><input type="radio" name="horn" value="Sí" required> Sí</label>
      <label><input type="radio" name="horn" value="No"> No</label><br><br>

      <p><strong>Espejos:</strong></p>
      <label><input type="radio" name="mirrors" value="Sí" required> Sí</label>
      <label><input type="radio" name="mirrors" value="No"> No</label><br><br>

      <p><strong>Niveles de líquidos:</strong></p>
      <label><input type="radio" name="fluids" value="Sí" required> Sí</label>
      <label><input type="radio" name="fluids" value="No"> No</label><br><br>

      <p><strong>Fugas de fluidos:</strong></p>
      <label><input type="radio" name="leaks" value="Sí" required> Sí</label>
      <label><input type="radio" name="leaks" value="No"> No</label><br><br>

      <p><strong>Estado de frenos:</strong></p>
      <label><input type="radio" name="brakes" value="Sí" required> Sí</label>
      <label><input type="radio" name="brakes" value="No"> No</label><br><br>

      <p><strong>Parabrisas:</strong></p>
      <label><input type="radio" name="windshield" value="Sí" required> Sí</label>
      <label><input type="radio" name="windshield" value="No"> No</label><br><br>

      <p><strong>Elementos de retención:</strong></p>
      <label><input type="radio" name="retention" value="Sí" required> Sí</label>
      <label><input type="radio" name="retention" value="No"> No</label><br><br>

      <p><strong>Documentos:</strong></p>
      <label><input type="radio" name="documents" value="Sí" required> Sí</label>
      <label><input type="radio" name="documents" value="No"> No</label><br><br>

      <p><strong>Equipos de prevención:</strong></p>
      <label><input type="radio" name="prevention" value="Sí" required> Sí</label>
      <label><input type="radio" name="prevention" value="No"> No</label><br><br>

      <p><strong>Luces de tablero y testigos:</strong></p>
      <label><input type="radio" name="dashboard_lights" value="Sí" required> Sí</label>
      <label><input type="radio" name="dashboard_lights" value="No"> No</label><br><br>

      <label><strong>Observaciones generales:</strong><br>
        <textarea name="general_observations" class="input-text" rows="3" style="resize:vertical;"></textarea>
      </label><br><br>

      <div class="firma-section" style="margin-top:2.5em; display: flex; flex-direction: row; gap: 2em; justify-content: center;">
        <div class="firma-box" style="flex:1; min-width: 260px;">
            <canvas id="firmaUsuario" width="250" height="60"
                style="border:1.5px solid #888;background:#fff;touch-action: none;border-radius:6px;width:100%;max-width:250px;"></canvas>
            <div style="border-bottom:1px solid #888;margin-bottom:0.5em;"></div>
            <span>Firma Usuario Responsable</span>
            <br>
            <button type="button" onclick="clearFirma()"
                style="margin-top:0.5em;background:#e0e0e0;color:#215ba0;padding:0.3em 1em;border:none;border-radius:5px;cursor:pointer;">Limpiar
                firma</button>
            <input type="hidden" name="firma_entrega" id="firma_usuario_input">
        </div>
      </div>
      <div style="text-align:center;">
        <button type="submit" class="btn-primary" onclick="return beforeSubmit();">Enviar</button>
      </div>
    </form>
  </div>
  
  <script>
    // Opcional: autocompletar nombre/documento al seleccionar usuario
    const users = <?php echo json_encode($users); ?>;
    function fillUserData() {
      const sel = document.getElementById('user_id');
      const nombre = document.getElementById('nombre_conductor');
      const doc = document.getElementById('documento');
      const user = users.find(u => u.id == sel.value);
      if (user) {
        nombre.value = user.first_name + ' ' + user.last_name;
        doc.value = user.document;
      } else {
        nombre.value = '';
        doc.value = '';
      }
    }

    // Establecer fecha y hora actuales automáticamente
    window.addEventListener('DOMContentLoaded', function() {
      const fechaInput = document.getElementById('fecha');
      const horaInput = document.getElementById('hora');
      const now = new Date();
      if (fechaInput) {
        fechaInput.value = now.toISOString().slice(0, 10);
      }
      if (horaInput) {
        let h = now.getHours().toString().padStart(2, '0');
        let m = now.getMinutes().toString().padStart(2, '0');
        horaInput.value = h + ':' + m;
      }
    });

    // Prepara un array JS con las fotos de los vehículos
    const vehiculosFotos = {};
    <?php foreach ($placas as $v): 
      $placaFolder = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $v['placa']);
      $photoDir = "../uploads/" . $placaFolder . "/";
      $photoPattern = $photoDir . "foto_vehiculo.*";
      $photoFiles = glob(__DIR__ . "/../uploads/" . $placaFolder . "/foto_vehiculo.*");
      $photoWeb = "../assets/img/no-image.png";
      if (count($photoFiles) > 0) {
        $basename = basename($photoFiles[0]);
        $photoWeb = $photoDir . $basename;
      }
    ?>
      vehiculosFotos["<?= addslashes($v['placa']) ?>"] = "<?= addslashes($photoWeb) ?>";
    <?php endforeach; ?>

    function mostrarDatosVehiculo() {
      var select = document.getElementById('placa');
      var infoDiv = document.getElementById('info-vehiculo');
      var marca = '';
      var modelo = '';
      var placa = '';
      var foto = '../assets/img/no-image.png';
      if (select.value) {
        var selected = select.options[select.selectedIndex];
        marca = selected.getAttribute('data-marca') || '';
        modelo = selected.getAttribute('data-modelo') || '';
        placa = select.value;
        if (vehiculosFotos[placa]) {
          foto = vehiculosFotos[placa];
        }
        document.getElementById('info-placa').textContent = placa;
        document.getElementById('info-marca').textContent = marca;
        document.getElementById('info-modelo').textContent = modelo;
        document.getElementById('foto-vehiculo').src = foto;
        infoDiv.style.display = 'block';
      } else {
        infoDiv.style.display = 'none';
      }
    }

    // Firma usuario responsable
    let canvas = document.getElementById('firmaUsuario');
    let ctx = canvas ? canvas.getContext('2d') : null;
    let drawing = false;
    if (canvas) {
      // Mouse events
      canvas.addEventListener('mousedown', function (e) {
        drawing = true;
        ctx.beginPath();
        ctx.moveTo(e.offsetX, e.offsetY);
      });
      canvas.addEventListener('mousemove', function (e) {
        if (drawing) {
          ctx.lineTo(e.offsetX, e.offsetY);
          ctx.strokeStyle = "#222";
          ctx.lineWidth = 2;
          ctx.stroke();
        }
      });
      canvas.addEventListener('mouseup', function () {
        drawing = false;
      });
      canvas.addEventListener('mouseleave', function () {
        drawing = false;
      });
      // Touch events
      canvas.addEventListener('touchstart', function (e) {
        if (e.targetTouches.length == 1) {
          let rect = canvas.getBoundingClientRect();
          let touch = e.targetTouches[0];
          drawing = true;
          ctx.beginPath();
          ctx.moveTo(touch.clientX - rect.left, touch.clientY - rect.top);
          e.preventDefault();
        }
      });
      canvas.addEventListener('touchmove', function (e) {
        if (drawing && e.targetTouches.length == 1) {
          let rect = canvas.getBoundingClientRect();
          let touch = e.targetTouches[0];
          ctx.lineTo(touch.clientX - rect.left, touch.clientY - rect.top);
          ctx.strokeStyle = "#222";
          ctx.lineWidth = 2;
          ctx.stroke();
          e.preventDefault();
        }
      });
      canvas.addEventListener('touchend', function (e) {
        drawing = false;
        e.preventDefault();
      });
    }
    function clearFirma() {
      if (ctx && canvas) ctx.clearRect(0, 0, canvas.width, canvas.height);
    }

    // Guardar firmas antes de enviar el formulario
    function beforeSubmit() {
      if (canvas) {
        document.getElementById('firma_usuario_input').value = canvas.toDataURL("image/png");
      }
      // Permitir el envío normal del formulario
      return true;
    }

    // Si quieres forzar el scroll arriba para ver la alerta después del submit:
    window.addEventListener('pageshow', function() {
      window.scrollTo(0, 0);
    });
  </script>
</body>
</html>
  
</body>
</html>
      
</body>
</html>
  
</body>
</html>
