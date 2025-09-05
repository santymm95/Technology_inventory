<?php
// Conexión a la base de datos
$conn = new mysqli("localhost", "root", "", "acema_db");
$conn->set_charset("utf8mb4");

$usuarios = $conn->query("SELECT users.id, CONCAT(users.first_name, ' ', users.last_name) AS nombre_completo, email, roles.name AS rol 
FROM users 
JOIN roles ON users.role_id = roles.id");

$usuarios_data = [];
while ($row = $usuarios->fetch_assoc()) {
  $usuarios_data[] = $row;
}
$conn->close();

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['new_password'])) {
    $user_id = $_POST['user_id'];
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    $conn = new mysqli("localhost", "root", "", "acema_db");
    $conn->set_charset("utf8mb4");
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $new_password, $user_id);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    echo "<p style='color: green; text-align: center;'>Contraseña actualizada correctamente para el usuario.</p>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestión de Usuarios</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f4f9;
      color: #333;
      margin: 0;
      padding: 20px;
    }

    h2 {
      text-align: center;
      margin-bottom: 20px;
    }

    .container {
      max-width: 1200px;
      margin: auto;
    }

    #user-dropdown {
      padding: 10px;
      font-size: 1rem;
      margin: 0 auto 20px auto;
      display: block;
    }

    .user-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 30px;
    }

    .user-table th, .user-table td {
      padding: 12px;
      border: 1px solid #ccc;
      text-align: left;
    }

    .user-table th {
      background-color: #215ba0;
      color: white;
    }

    .user-table tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    .action-btn {
      padding: 8px 12px;
      font-size: 14px;
      border: none;
      border-radius: 5px;
      color: white;
      cursor: pointer;
      margin-right: 5px;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    .action-btn:hover {
      opacity: 0.9;
    }

    .edit-btn { background-color: #007bff; }
    .reset-btn { background-color: #ffc107; color: #000; }
    .delete-btn { background-color: #dc3545; }

    #reset-password-form {
      display: none;
      background-color: #fff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      max-width: 500px;
      margin: auto;
    }

    #reset-password-form input,
    #reset-password-form button {
      width: 100%;
      padding: 10px;
      margin-top: 10px;
      font-size: 16px;
      border-radius: 5px;
      border: 1px solid #ccc;
    }

    .button-container a{
 
      margin-bottom: 20px;
      text-decoration: none;
      color: white;
      background-color: #28a745;
      font-weight: bold;
 
    }

    .button-container a:hover {
      background-color: #218838;
    }

    #reset-password-form button {
      background-color: #40a335;
      color: white;
      border: none;
      cursor: pointer;
      
    }

    #reset-password-form button:hover {
      background-color: #218838;
    }

    @media (max-width: 768px) {
      .user-table, .user-table th, .user-table td {
        font-size: 14px;
      }

      .action-btn {
        font-size: 12px;
        padding: 6px 8px;
      }
    }
  </style>
</head>
<body>
     <div class="button-container">
      <a href="form_register_user.php" class="button">+</a>
    </div>
  <div class="container">
  
 
 

    <table class="user-table">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Email</th>
          <th>Rol</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($usuarios_data as $usuario): ?>
          <tr>
            <td><?php echo $usuario['nombre_completo']; ?></td>
            <td><?php echo $usuario['email']; ?></td>
            <td><?php echo $usuario['rol']; ?></td>
            <td>
              <button class="action-btn edit-btn" onclick="editUser(<?php echo $usuario['id']; ?>)">
                <i class="fas fa-edit"></i> Editar
              </button>
              <button class="action-btn reset-btn" onclick="resetPasswordForm(<?php echo $usuario['id']; ?>)">
                <i class="fas fa-key"></i> Restablecer
              </button>
              <button class="action-btn delete-btn" onclick="deleteUser(<?php echo $usuario['id']; ?>)">
                <i class="fas fa-trash-alt"></i> Eliminar
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div id="reset-password-form">
      <h3>Restablecer Contraseña</h3>
      <form method="POST">
        <input type="hidden" name="user_id" id="reset-user-id">
        <label for="new_password">Nueva Contraseña:</label>
        <input type="password" name="new_password" id="new_password" required>
        <button type="submit">Actualizar Contraseña</button>
      </form>
    </div>
  </div>
  <h2>Total de Usuarios</h2>
    <p style="text-align:center; font-size: 1.5em;"><strong><?php echo count($usuarios_data); ?></strong></p>
 
 
  <script>
    function resetPasswordForm(userId) {
      document.getElementById("reset-password-form").style.display = "block";
      document.getElementById("reset-user-id").value = userId;
      window.scrollTo({ top: document.getElementById("reset-password-form").offsetTop, behavior: 'smooth' });
    }

    function editUser(userId) {
      window.location.href = `edit_user.php?id=${userId}`;
    }

    function deleteUser(userId) {
      if (confirm("¿Estás seguro de que deseas eliminar este usuario?")) {
        window.location.href = `../controllers/delete_user.php?id=${userId}`;
      }
    }
  </script>
</body>
</html>
