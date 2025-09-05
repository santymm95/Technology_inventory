<?php
// Corrección segura para evitar errores si ya se inició la sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validar que el usuario haya iniciado sesión (opcional según seguridad deseada)
if (!isset($_SESSION['user'])) {
    echo "Acceso no autorizado.";
    exit;
}

$first_name = $_SESSION['user']['first_name'] ?? '';
$last_name = $_SESSION['user']['last_name'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control - ACEMA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../assets/css/navbar.css"> <!-- Aquí se debe colocar el CSS externo correctamente -->
  
</head>
<body>
    <style>
     
    </style>
    <!-- Botón flotante del asistente -->
    <div id="asistente-boton" class="asistente-boton">
        <i class="fas fa-robot"></i>
        <span class="mensaje-ayuda">¿Necesitas ayuda?</span>
    </div>

    <!-- Popup del asistente -->
    <div id="asistente-popup" class="asistente-popup">
        <div class="asistente-header">
            <i class="fas fa-robot"></i> Asistente ACEMA
            <button id="cerrar-asistente" title="Cerrar">&times;</button>
        </div>
        <div class="asistente-body">
            <p>Hola <?php echo htmlspecialchars($first_name); ?> 👋<br>¿En qué puedo ayudarte hoy?</p>
            <ul>
                <li>📁 ¿Cómo crear un nuevo proyecto?</li>
                <li>🕒 ¿Dónde registro mis horas?</li>
                <li>📊 ¿Cómo ver mis reportes?</li>
            </ul>
        </div>
    </div>

    <!-- Navbar -->
    <nav class="navbar-modern">
        
        <div class="navbar-logo">
           <a href="dashboard.php"><img src="../assets/images/logo-acema.webp" alt="Logo"></a> 
        </div>
        <div class="navbar-left">
            <div class="user-icon"><?php echo strtoupper(substr($first_name, 0, 1)); ?></div>
            <div class="user-name"><?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></div>
            <button id="theme-toggle" class="theme-toggle" title="Cambiar tema">
  <!-- <span class="icon" id="theme-icon">🌙</span> -->
</button>




        </div>
        
    </nav>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="nav-item"><a href="dashboard.php"><i class="fas fa-home"></i><span> Inicio</span></a></div>
        <div class="nav-item"><a href="identificar.php"><i class="fas fa-clock"></i><span> Asistencias</span></a></div>
        <div class="nav-item"><a href="create_project.php"><i class="fas fa-folder-open"></i><span> Proyectos</span></a></div>
        <div class="nav-item"><a href="create_user.php"><i class="fas fa-users"></i><span> Usuarios</span></a></div>
        <div class="nav-item"><a href="view_data.php"><i class="fas fa-chart-line"></i><span> Reportes</span></a></div>
        <div class="nav-item"><a href="configuration.php"><i class="fas fa-cog"></i><span> Configuración</span></a></div>
        <div class="nav-item"><a href="logout.php"><i class="fas fa-sign-out-alt"></i><span> Salir</span></a></div>
         
        
    </div>
    <script>
     // Obtener el botón de cambio de tema y el icono
const themeToggleButton = document.getElementById('theme-toggle');
const themeIcon = document.getElementById('theme-icon');

// Comprobar si hay un tema guardado en localStorage
if (localStorage.getItem('theme') === 'dark') {
  document.body.classList.add('dark-mode');
  themeIcon.textContent = '☀️'; // Sol
} else {
  document.body.classList.remove('dark-mode');
  themeIcon.textContent = '🌙'; // Luna
}

// Cambiar entre modo oscuro y claro al hacer clic
themeToggleButton.addEventListener('click', () => {
  document.body.classList.toggle('dark-mode');

  // Cambiar el ícono de luna a sol
  if (document.body.classList.contains('dark-mode')) {
    themeIcon.textContent = '☀️'; // Sol
    localStorage.setItem('theme', 'dark'); // Guardar el tema como oscuro
  } else {
    themeIcon.textContent = '🌙'; // Luna
    localStorage.setItem('theme', 'light'); // Guardar el tema como claro
  }
});

</script>

</body>

</html>
