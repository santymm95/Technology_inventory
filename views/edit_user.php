<?php
$conn = new mysqli("localhost", "root", "", "acema_db");
$conn->set_charset("utf8mb4");

if (isset($_GET['id'])) {
    $userId = $_GET['id'];

    $sql = "SELECT users.id, users.first_name, users.last_name, users.email, users.document_number, users.project_id,
                   roles.id AS role_id, roles.name AS role_name
            FROM users
            JOIN roles ON users.role_id = roles.id
            WHERE users.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        // Usuario no encontrado, redirigir
        header("Location: usuarios.php");
        exit();
    }
} else {
    header("Location: usuarios.php");
    exit();
}

// Obtener roles
$rolesResult = $conn->query("SELECT id, name FROM roles");
$roles = [];
while ($role = $rolesResult->fetch_assoc()) {
    $roles[] = $role;
}

// Obtener proyectos
$projectsResult = $conn->query("SELECT id, name FROM projects");
$projects = [];
while ($project = $projectsResult->fetch_assoc()) {
    $projects[] = $project;
}

$conn->close();

$document = $user['document_number'];
$photoServerPath = __DIR__ . "/uploads/{$document}/rostro.jpg";
$photoWebPath = "uploads/{$document}/rostro.jpg";

$photoPath = file_exists($photoServerPath) ? $photoWebPath : "https://via.placeholder.com/150";
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Perfil de Usuario</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    * {
      box-sizing: border-box;
      padding: 0;
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      background: linear-gradient(to right, #f0f2f5, #e6ecf0);
      padding: 20px;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      min-height: 100vh;
    }

    .card-container {
      background: #fff;
      max-width: 400px;
      width: 100%;
      margin-top: 80px;
      border-radius: 15px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
      padding: 30px 20px;
      text-align: center;
      transition: all 0.3s ease-in-out;
    }

    .card-container img {
      width: 130px;
      height: 130px;
      object-fit: cover;
      border-radius: 50%;
      margin-bottom: 15px;
      border: 3px solid #007bff;
    }

    h2 {
      margin-bottom: 5px;
    }

    .role {
      font-weight: bold;
      color: #555;
      margin-bottom: 15px;
    }

    .info {
      text-align: left;
      margin-bottom: 15px;
    }

    .info p {
      margin-bottom: 8px;
      font-size: 15px;
    }

    .btn {
      background-color: #007bff;
      color: white;
      padding: 10px;
      width: 100%;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-size: 16px;
      margin-top: 10px;
    }

    .btn:hover {
      background-color: #0056b3;
    }

    .edit-form {
      display: none;
      margin-top: 20px;
      text-align: left;
    }

    .edit-form input, .edit-form select {
      width: 100%;
      padding: 8px;
      margin: 8px 0;
      border-radius: 5px;
      border: 1px solid #ccc;
    }

    .back-link {
      display: block;
      text-align: center;
      margin-top: 15px;
      color: #007bff;
      text-decoration: none;
    }

    .back-link:hover {
      text-decoration: underline;
    }

    @media (max-width: 480px) {
      .card-container {
        margin-top: 80px;
        padding: 50px;
      }

      .info {
        font-size: 14px;
      }

      .btn {
        font-size: 14px;
      }
    }
  </style>
</head>
<body>
<?php include 'layout.php'; ?>
<div class="card-container">
  <img src="<?php echo htmlspecialchars($photoPath); ?>" alt="Foto de <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>">
  <h2><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h2>
  <div class="role"><?php echo htmlspecialchars($user['role_name']); ?></div>

  <div class="info">
    <p><strong>Correo:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
    <p><strong>Documento:</strong> <?php echo htmlspecialchars($user['document_number']); ?></p>
  </div>

  <button class="btn" onclick="document.querySelector('.edit-form').style.display='block'; this.style.display='none'">Editar Información</button>

  <form class="edit-form" action="../controllers/update_user.php" method="POST">
    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">

    <label for="first_name">Nombre</label>
    <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>

    <label for="last_name">Apellido</label>
    <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>

    <label for="email">Correo Electrónico</label>
    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>

    <label for="document_number">Número de Documento</label>
    <input type="text" name="document_number" value="<?php echo htmlspecialchars($user['document_number']); ?>" required>

    <label for="role">Rol</label>
    <select name="role" required>
      <?php foreach ($roles as $role): ?>
        <option value="<?php echo $role['id']; ?>" <?php echo $role['id'] == $user['role_id'] ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($role['name']); ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label for="project">Proyecto Asignado</label>
    <select name="project" required>
      <?php foreach ($projects as $project): ?>
        <option value="<?php echo $project['id']; ?>" <?php echo $project['id'] == $user['project_id'] ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($project['name']); ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label>
      <input type="checkbox" name="enabled" value="1" <?php if (!isset($user['enabled']) || $user['enabled']) echo 'checked'; ?>>
      Usuario habilitado
    </label>
    <button class="btn" type="submit" name="update_user">Guardar Cambios</button>
  </form>
</div>

</body>
</html>
