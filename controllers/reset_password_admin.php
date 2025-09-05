<?php
session_start();

// Verificar que el usuario está autenticado y tiene el rol 'admin'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Verificar que el ID del usuario está presente
if (!isset($_GET['id'])) {
    die("ID de usuario no especificado.");
}

$user_id = intval($_GET['id']);

// Procesar el formulario para actualizar la contraseña
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_once('../includes/db.php');
    
    // Validar y encriptar la nueva contraseña
    if (!empty($_POST['new_password'])) {
        $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

        // Actualizar la contraseña en la base de datos
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$new_password, $user_id]);

        // Mensaje de éxito
        echo "<p>Contraseña actualizada correctamente.</p>";
        echo "<a href='../admin/usuarios.php'>Volver</a>";
        exit;
    } else {
        echo "<p>La contraseña no puede estar vacía.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer contraseña</title>
    <link rel="stylesheet" href="../assets/css/form_admin.css">
</head>
<body>
    <div class="container">
        <h2>Restablecer contraseña</h2>
        <form method="POST">
            <label>Nueva contraseña:</label>
            <input type="password" name="new_password" required>
            <button type="submit">Actualizar</button>
        </form>
    </div>
</body>
</html>
