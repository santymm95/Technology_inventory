<?php
session_start();

// Conexión a la base de datos
$host = 'localhost';
$dbname = 'acema_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Procesar el inicio de sesión
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Por favor, ingresa todos los campos.";
    } else {
        // Consulta con JOIN para obtener nombre del rol
        $stmt = $pdo->prepare("
            SELECT u.*, r.name AS role_name 
            FROM users u 
            INNER JOIN roles r ON u.role_id = r.id 
            WHERE u.email = :email 
            LIMIT 1
        ");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'email' => $user['email'],
                'role' => $user['role_name']  // Guardamos el nombre del rol
            ];
            header("Location: ./views/loading_dashboard.php");
            exit;
        } else {
            $_SESSION['error'] = "Correo o contraseña incorrectos 🤨";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login - ACEMA ERP</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0f2f5;
            margin: 0; padding: 0;
            display: flex;
            height: 100vh;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            width: 350px;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h2 {
            margin-bottom: 20px;
            color: #333;
            text-align: center;
        }
        .alert {
            background: #f8d7da;
            color: #842029;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            border: 1px solid #f5c2c7;
            font-size: 14px;
        }
        .input-group {
            position: relative;
            margin-bottom: 20px;
        }
        .input-group input {
            width: 100%;
            padding: 12px 40px 12px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }
        .input-group i {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #0078D7;
            border: none;
            color: white;
            font-weight: bold;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        button:hover {
            background: #005fa3;
        }
        .register-option {
            margin-top: 15px;
            text-align: center;
            font-size: 14px;
        }
        .register-option a {
            color: #0078D7;
            text-decoration: none;
            font-weight: 600;
        }
        .register-option a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Iniciar sesión</h2>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert">
                <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" autocomplete="off">
            <div class="input-group">
                <input type="email" name="email" placeholder="Correo electrónico" required autofocus>
                <i class="fas fa-user"></i>
            </div>
            <div class="input-group">
                <input type="password" id="password" name="password" placeholder="Contraseña" required>
                <i class="fas fa-eye" id="toggle-password"></i>
            </div>
            <button type="submit">Iniciar sesión</button>
        </form>

        <div class="register-option">
            <p>¿No tienes cuenta? <a href="./views/register.php">¡Regístrate aquí!</a></p>
        </div>
    </div>

    <!-- Font Awesome CDN -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

    <script>
        // Mostrar/ocultar contraseña
        const togglePassword = document.getElementById('toggle-password');
        const passwordField = document.getElementById('password');

        togglePassword.addEventListener('click', () => {
            const type = passwordField.type === 'password' ? 'text' : 'password';
            passwordField.type = type;
            togglePassword.classList.toggle('fa-eye');
            togglePassword.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>
