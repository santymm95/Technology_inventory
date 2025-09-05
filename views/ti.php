<?php
session_start();
if (!isset($_SESSION['user'])) {
  header('Location: index.php');
  exit;
}

// Obtener el número real de solicitudes pendientes (ajusta según tu lógica real)
// Ejemplo: $pendientes = obtenerPendientes(); 
// Aquí simulado:
$pendientes = 0; // Cambia esto por tu consulta real a la base de datos

// Verificar cantidad de equipos con mantenimiento atrasado SIN realizar
include_once __DIR__ . '/../includes/conection.php';
$atrasadosCount = 0;
$anios = [2025, 2026];
$equipos = [];
$res = $conn->query("SELECT id, purchase_date FROM devices");
while ($row = $res->fetch_assoc()) {
  $equipos[$row['id']] = $row['purchase_date'];
}
$mantenimientos = [];
$res = $conn->query("SELECT device_id, date FROM maintenance");
while ($row = $res->fetch_assoc()) {
  $mantenimientos[$row['device_id']][] = $row['date'];
}
foreach ($equipos as $id => $purchase_date) {
  if ($purchase_date && $purchase_date !== '0000-00-00') {
    $mes_compra = (int)date('n', strtotime($purchase_date));
    $anio_compra = (int)date('Y', strtotime($purchase_date));
    $mant_fechas = [];
    if (!empty($mantenimientos[$id])) {
      foreach ($mantenimientos[$id] as $fecha) {
        $mant_fechas[] = strtotime($fecha);
      }
    }
    foreach ($anios as $anio_eval) {
      if ($mes_compra > 0 && $anio_compra <= $anio_eval) {
        $mant_en_tiempo = false;
        foreach ($mant_fechas as $f) {
          $mant_anio = (int)date('Y', $f);
          $mant_mes = (int)date('n', $f);
          if ($mant_anio == $anio_eval && $mant_mes == $mes_compra) {
            $mant_en_tiempo = true;
            break;
          }
        }
        // Solo contar si NO se ha realizado el mantenimiento en el mes esperado y YA pasó ese mes/año
        if (
          !$mant_en_tiempo &&
          (
            ($anio_eval < date('Y')) ||
            ($anio_eval == date('Y') && date('n') > $mes_compra)
          )
        ) {
          // Verificar si existe algún mantenimiento para ese equipo en ese año o después del mes esperado
          $mant_realizado = false;
          foreach ($mant_fechas as $f) {
            $mant_anio = (int)date('Y', $f);
            $mant_mes = (int)date('n', $f);
            if (
              ($mant_anio > $anio_eval) ||
              ($mant_anio == $anio_eval && $mant_mes > $mes_compra)
            ) {
              $mant_realizado = true;
              break;
            }
          }
          // Solo contar si NO hay ningún mantenimiento realizado después (o sea, sigue pendiente)
          if (!$mant_realizado) {
            $atrasadosCount++;
            break; // Solo contar una vez por equipo
          }
        }
      }
    }
  }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Panel de Control</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    crossorigin="anonymous" referrerpolicy="no-referrer" />

  <link rel="stylesheet" href="../assets/css/dashboard.css">
  <style>
    /* Notificación burbuja para la card */
    .notification-bubble {
      position: absolute;
      top: 8px;
      right: 12px;
      background: #e74c3c;
      color: #fff;
      border-radius: 50%;
      padding: 2px 7px;
      font-size: 12px;
      font-weight: bold;
      z-index: 2;
      box-shadow: 0 1px 4px rgba(0, 0, 0, 0.15);
      min-width: 20px;
      text-align: center;
      line-height: 18px;
      display: inline-block;
    }

    .shortcut-card {
      position: relative;
    }
  </style>
</head>

<body>

  <?php include 'layout.php'; ?>


  <div id="asistente-popup"
    style="display:none;position:fixed;bottom:100px;right:32px;z-index:1000;background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,0.12);padding:1.5rem;max-width:340px;">
    <div style="display:flex;align-items:center;justify-content:space-between;">
      <strong>¿Necesitas ayuda?</strong>
      <button id="cerrar-asistente" style="background:none;border:none;font-size:1.2rem;cursor:pointer;">×</button>
    </div>
    <div style="margin-top:1rem;">
      <p>Asistente ACEMA ×<br>Hola Administrador 👋<br>¿En qué puedo ayudarte hoy?</p>
      <ul style="padding-left:1.2em;">
        <li>📁 ¿Cómo crear un nuevo proyecto?</li>
        <li>🕒 ¿Dónde registro mis horas?</li>
        <li>📊 ¿Cómo ver mis reportes?</li>
      </ul>
    </div>
    <div style="margin-top:1rem;text-align:right;">
      <span style="font-size:0.9em;color:#888;">Logo<br>A<br>Administrador TI<br>🌙</span>
    </div>
    <div style="margin-top:1rem;">
      <span style="font-size:0.9em;color:#888;">Inicio · Asistencias · Proyectos · Usuarios · Reportes · Configuración ·
        Salir</span>
    </div>
  </div>
  <script>
    // Mostrar el popup del asistente
    document.getElementById('asistente-boton').addEventListener('click', function () {
      document.getElementById('asistente-popup').style.display = 'block';
      this.style.display = 'none';
    });
    document.getElementById('cerrar-asistente').addEventListener('click', function () {
      document.getElementById('asistente-popup').style.display = 'none';
      document.getElementById('asistente-boton').style.display = 'block';
    });
  </script>

  <div class="main-content">
    <div class="welcome-card">
      <h1>Bienvenido, <?php echo $_SESSION['user']['first_name']; ?></h1>
      <p><strong>Dashboard principal.</strong> Explora el menú lateral para realizar acciones o crea tarjetas con
        accesos directos.</p>
    </div>

    <div class="shortcuts-container">
      <div class="shortcut-card" data-link="register_device.php">
        <i class="fas fa-laptop"></i>
        <span>Registrar computador</span>
      </div>

      <div class="shortcut-card" data-link="register_other_device.php">
        <i class="fas fa-camera"></i>
        <span>Registar otros equipos</span>
      </div>
      
      <div class="shortcut-card" data-link="views_divices.php">
        <i class="fas fa-eye"></i>
        <span>Ver equipos</span>
      </div>
      <div class="shortcut-card" data-link="maintenance_calendar.php" style="position:relative;">
        <i class="fas fa-calendar"></i>
        <span>Mantenimientos</span>
        <?php if ($atrasadosCount > 0): ?>
          <span class="notification-bubble" title="Equipos con mantenimiento atrasado"><?= $atrasadosCount ?></span>
        <?php endif; ?>
      </div>
      <div class="shortcut-card" data-link="user_form.php">
        <i class="fas fa-user"></i>
        <span>Registrar usuarios</span>
      </div>
      <!-- Nueva card para vehicle_timeline.php -->
      <div class="shortcut-card" data-link="vehicle_timeline.php">
        <i class="fas fa-car"></i>
        <span>Historial vehículos</span>
      </div>
      <!-- Nueva card para matriz general -->
      <div class="shortcut-card" data-link="matriz_general.php">
        <i class="fas fa-table"></i>
        <span>Matriz General</span>
      </div>
      </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
  <script>
    document.querySelectorAll('.shortcut-card').forEach(card => {
      card.addEventListener('click', () => {
        const link = card.getAttribute('data-link');
        if (link) {
          window.location.href = link;
        }
      });
    });

    window.addEventListener('load', () => {
      // Mostrar el botón del asistente 3 segundos después
      setTimeout(() => {
        document.getElementById('asistente-boton').classList.add('mostrar');
      }, 2000);
    });

    const asistenteBoton = document.getElementById('asistente-boton');
    const asistentePopup = document.getElementById('asistente-popup');
    const cerrarAsistente = document.getElementById('cerrar-asistente');

    // Mostrar popup y ocultar botón
    asistenteBoton.addEventListener('click', () => {
      asistentePopup.classList.add('mostrar');
      asistenteBoton.style.display = 'none';
    });

    // Cerrar popup y mostrar botón otra vez
    cerrarAsistente.addEventListener('click', () => {
      asistentePopup.classList.remove('mostrar');
      asistenteBoton.style.display = 'flex';
    });
    new Sortable(document.querySelector('.shortcuts-container'), {
      animation: 150,
      ghostClass: 'dragging-card'
    });

    document.querySelectorAll('.shortcut-card').forEach(card => {
      card.addEventListener('click', () => {
        const destino = card.getAttribute('data-link');
        if (destino) window.location.href = destino;
      });
    });

    // Actualización periódica de pendientes y notificación de escritorio
    let lastPendientes = <?php echo (int) $pendientes; ?>;
    let lastNotified = lastPendientes;
    function checkPendientes() {
      fetch('../get_pendientes.php?_=' + new Date().getTime())
        .then(response => response.json())
        .then(data => {
          console.log('Respuesta get_pendientes.php:', data); // depuración
          const pendientes = parseInt(data.pendientes, 10);
          const numero = document.getElementById('pendientes-numero');
          if (numero) {
            numero.textContent = pendientes;
          }
          // Notifica si el valor cambió y es mayor que 0
          if (pendientes !== lastNotified && pendientes > 0) {
            showDesktopNotification(pendientes);
            lastNotified = pendientes;
          }
          lastPendientes = pendientes;
        })
        .catch(err => {
          console.error('Error al consultar get_pendientes.php:', err);
        });
    }

    function showDesktopNotification(pendientes) {
      if ("Notification" in window) {
        var options = {
          body: "Haz clic para ver las solicitudes pendientes.",
          icon: "https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.0/svgs/solid/project-diagram.svg",
          tag: "solicitudes-pendientes"
        };
        if (Notification.permission === "granted") {
          var notification = new Notification("Tienes " + pendientes + " solicitudes pendientes por aprobar.", options);
          notification.onclick = function () {
            window.focus();
            window.location.href = "enviados_revision.php";
          };
        } else if (Notification.permission !== "denied") {
          Notification.requestPermission().then(function (permission) {
            if (permission === "granted") {
              var notification = new Notification("Tienes " + pendientes + " solicitudes pendientes por aprobar.", options);
              notification.onclick = function () {
                window.focus();
                window.location.href = "enviados_revision.php";
              };
            }
          });
        }
      }
    }

    // Primera comprobación al cargar
    document.addEventListener('DOMContentLoaded', function () {
      checkPendientes();
      setInterval(checkPendientes, 30000); // cada 30 segundos
    });
  </script>

</body>

</html>