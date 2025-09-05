<?php
require_once('../includes/db.php');

// Obtener roles
try {
    $roles = $pdo->query("SELECT id, name FROM roles");
} catch (PDOException $e) {
    die("Error al obtener los roles: " . $e->getMessage());
}

// Obtener proyectos
try {
    $projects = $pdo->query("SELECT id, name FROM projects");
} catch (PDOException $e) {
    die("Error al obtener los proyectos: " . $e->getMessage());
}
?>

<?php include 'layout.php'; ?>
<link rel="stylesheet" href="../assets/css/styles_register.css">

<div class="register-container">
  <h2>Registro de Usuario</h2>
  <form id="registerForm" action="../controllers/registerController.php" method="POST" enctype="multipart/form-data" class="register-form">

    <div class="input-group">
      <i class="fas fa-user"></i>
      <input type="text" name="first_name" placeholder="Nombre" required>
    </div>

    <div class="input-group">
      <i class="fas fa-user"></i>
      <input type="text" name="last_name" placeholder="Apellido" required>
    </div>

    <div class="input-group">
      <i class="fas fa-id-card"></i>
      <input type="text" name="document_number" id="document_number" placeholder="Número de documento" required>
    </div>

    <div class="input-group">
      <i class="fas fa-envelope"></i>
      <input type="email" name="email" placeholder="Correo electrónico" required>
    </div>

    <div class="input-group">
      <i class="fas fa-lock"></i>
      <input type="password" name="password" placeholder="Contraseña" required>
    </div>

    <div class="input-group">
      <i class="fas fa-user-tag"></i>
      <select name="role_id" required>
        <option value="">Selecciona un rol</option>
        <?php while ($row = $roles->fetch(PDO::FETCH_ASSOC)): ?>
          <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>

    <div class="input-group">
      <i class="fas fa-project-diagram"></i>
      <select name="project_id" required>
        <option value="">Selecciona un proyecto</option>
        <?php while ($proj = $projects->fetch(PDO::FETCH_ASSOC)): ?>
          <option value="<?= $proj['id'] ?>"><?= htmlspecialchars($proj['name']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>

    <!-- Cámara -->
    <div class="input-group">
      <label for="camera">Tomar Foto:</label><br>
      <video id="video" width="300" autoplay></video><br>
      <canvas id="canvas" style="display:none;"></canvas>
      <input type="hidden" name="photo_data" id="photo_data" required>
      <button type="button" onclick="capturePhoto()">Capturar Foto</button>
    </div>

    <div class="button-group">
      <button class="button" type="submit">Registrar</button>
    </div>
  </form>
</div>

<script>
  const video = document.getElementById('video');
  const canvas = document.getElementById('canvas');
  const photoData = document.getElementById('photo_data');

  // Solicitar acceso a la cámara
  navigator.mediaDevices.getUserMedia({ video: true })
    .then((stream) => {
      video.srcObject = stream;
    })
    .catch((err) => {
      console.error("Error al acceder a la cámara: " + err);
      alert("No se pudo acceder a la cámara.");
    });

  function capturePhoto() {
    const context = canvas.getContext('2d');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    context.drawImage(video, 0, 0);
    const dataURL = canvas.toDataURL('image/jpeg');
    photoData.value = dataURL;
    alert("Foto capturada correctamente. Ya puedes enviar el formulario.");
  }
</script>
