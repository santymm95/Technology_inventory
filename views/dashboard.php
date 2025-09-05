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

  <!-- Puedes agregar aquí un asistente flotante o popup si lo deseas -->
  <div id="asistente-boton" style="position:fixed;bottom:32px;right:32px;z-index:999;">
    <!-- <button
      style="background:#2176ae;color:#fff;border:none;border-radius:50%;width:56px;height:56px;box-shadow:0 2px 8px rgba(33,118,174,0.15);font-size:2rem;cursor:pointer;">
      <i class="fas fa-question"></i>
    </button> -->
  </div>
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
    <div class="card">
      <h1>Bienvenido, <?php echo $_SESSION['user']['first_name']; ?></h1>
      <p><strong>Dashboard principal.</strong> Explora el menú lateral para realizar acciones o crea tarjetas con
        accesos directos.</p>
    </div>

    <div class="shortcuts-container">
      <!-- <div class="shortcut-card" data-link="identificar.php">
        <i class="fas fa-clock"></i>
        <span>Asistencias</span>
      </div>
      <div class="shortcut-card" data-link="view_data.php">
        <i class="fas fa-chart-line"></i>
        <span>Reportes</span>
      </div>
      <div class="shortcut-card" data-link="enviados_revision.php" style="position: relative;">
        <i class="fas fa-project-diagram"></i>
        <span>Solicitudes</span>
        <span id="pendientes-burbuja" style="
          position: absolute;
          top: 8px;
          right: 12px;
          background: #e74c3c;
          color: #fff;
          border-radius: 50%;
          width: 32px;
          height: 32px;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 1em;
          font-weight: bold;
          box-shadow: 0 1px 4px rgba(0,0,0,0.15);
          z-index: 10;
        ">
          <i class="fas fa-bell" style="margin-right:4px;font-size:1em;"></i>
          <span id="pendientes-numero"><?php echo $pendientes > 0 ? $pendientes : '0'; ?></span>
        </span>
      </div>
      <div class="shortcut-card" data-link="create_project.php">
        <i class="fas fa-user-check"></i>
        <span>Proyectos</span>
      </div> -->
      <div class="shortcut-card" data-link="ti.php">
        <i class="fas fa-laptop"></i>
        <span>Inventario</span>
      </div>
      <div class="shortcut-card" onclick="window.location.href='preoperation_vehicle.php'">
        <i class="fas fa-clipboard-check"></i>
        <span>Preoperacional Vehículo</span>
      </div>


      <!-- <div class="shortcut-card" data-link="configuration.php">
        <i class="fas fa-cog"></i>
        <span>Admin</span>
      </div> -->




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