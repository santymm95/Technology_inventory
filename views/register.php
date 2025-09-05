<?php
session_start();
$conn = new mysqli("localhost", "root", "", "acema_db");

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$roles = $conn->query("SELECT id, name FROM roles");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario - ACEMA</title>
    <link rel="stylesheet" href="../assets/css/styles_register.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="login-box">
            <h2>Registro de Usuario</h2>
            <form action="../controllers/registerController.php" method="POST">
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="first_name" id="first_name" placeholder="Nombre" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="last_name" id="last_name" placeholder="Apellido" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" id="email" placeholder="Correo electrónico" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="password" placeholder="Contraseña" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-user-tag"></i>
                    <select name="role_id" id="role_id" required>
                        <option value="">Selecciona un rol</option>
                        <?php while ($row = $roles->fetch_assoc()): ?>
                            <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="button-group">
                    <button type="submit">Registrar</button>
                </div>
            </form>
            <div class="register-option">
                <p>¿Ya tienes cuenta? <a href="/acema/index.php">Inicia sesión aquí</a></p>
            </div>
        </div>
    </div>

    <?php
    if (isset($_SESSION['error'])) {
        echo "<script>alert('" . $_SESSION['error'] . "');</script>";
        unset($_SESSION['error']);
    }

    if (isset($_SESSION['success'])) {
        echo "<script>alert('" . $_SESSION['success'] . "');</script>";
        unset($_SESSION['success']);
    }
    ?>
</body>
</html>
