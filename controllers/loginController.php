<?php
session_start();

// Conexión a la base de datos
$host = 'localhost';
$dbname = 'acema_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES 'utf8'");
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Procesar el inicio de sesión
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validación simple del correo
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Por favor, ingresa todos los campos.";
        header('Location: ../index.php'); // Redirigir al formulario de login
        exit;
    }

    // Consultar si el usuario existe
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch();

    // Verificar la contraseña
    if ($user && password_verify($password, $user['password'])) {
        // Iniciar sesión y guardar los datos del usuario
        $_SESSION['user'] = [
            'id' => $user['id'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'email' => $user['email'],
            'role' => $user['role']
        ];
        header('Location: ./dashboard.php'); // Redirigir a la página principal (dashboard)
        exit;
    } else {
        // Si el usuario o la contraseña son incorrectos
        $_SESSION['error'] = "Correo o contraseña incorrectos🤨"; // Mensaje de error
        header('Location: ../index.php'); // Redirigir a index.php (formulario de login)
        exit;
    }
}
?>
