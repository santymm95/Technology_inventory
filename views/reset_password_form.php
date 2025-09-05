<?php
if (!isset($_GET['id'])) {
  die("ID de usuario no proporcionado.");
}

$id = intval($_GET['id']);

// Conexión
$conn = new mysqli("localhost", "root", "", "acema_db");
$conn->set_charset("utf8mb4");

$result = $conn->query("SELECT CONCAT(first_name, ' ', last_name) AS nombre FROM users WHERE id = $id");

if ($result->num_rows === 0) {
  die("Usuario no encontrado.");
}

$usuario = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Restablecer Contraseña</title>
  <link rel="stylesheet" href="../assets/css/user_card.css">
</head>
<body>
  <div class="container">
    <div class="card">
      <h2>Restablecer Contraseña</h2>
      <p>Usuario: <strong><?= htmlspecialchars($usuario['nombre']) ?></strong></p>
      <form action="reset_password_process.php" method="POST">
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="input-group">
          <label>Nueva contraseña</label>
          <input type="password" name="new_password" required>
        </div>
        <div class="input-group">
          <label>Confirmar contraseña</label>
          <input type="password" name="confirm_password" required>
        </div>
        <button class="action-btn" type="submit">Actualizar Contraseña</button>
        <a class="action-btn" href="usuarios.php">Cancelar</a>
      </form>
    </div>
  </div>
</body>
</html>
