<?php
session_start();
date_default_timezone_set('America/Bogota');

// Verificar si el usuario está logueado
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

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
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// Inicializar variables para mostrar nombre, foto y id del usuario buscado
$searchedName = "";
$searchedPhoto = "";
$searchedUserId = null;

// Buscar usuario por últimos dígitos del documento
if (isset($_POST['search_user'])) {
    $last_digits = trim($_POST['last_digits']);
    if (!empty($last_digits)) {
        try {
            $stmt = $pdo->prepare("SELECT id, first_name, last_name, document_number FROM users WHERE RIGHT(document_number, :length) = :last_digits");
            $length = strlen($last_digits);
            $stmt->bindParam(':length', $length, PDO::PARAM_INT);
            $stmt->bindParam(':last_digits', $last_digits, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $searchedUserId = $user['id'];
                $searchedName = $user['first_name'] . ' ' . $user['last_name'];
                // Asumimos que el documento es único y que la foto tiene el mismo nombre que el documento
                $searchedPhoto = 'uploads/' . $user['document_number'] . '.jpg'; // Ruta a la foto
            } else {
                $searchedName = "No se encontró un usuario con estos últimos dígitos.";
            }
        } catch (PDOException $e) {
            $searchedName = "Error: " . $e->getMessage();
        }
    } else {
        $searchedName = "Por favor ingresa algunos dígitos.";
    }
}

// Registrar entrada o salida
if (isset($_POST['register_entry_exit'])) {
    $project_id = $_POST['project_id'];
    $entry_or_exit = $_POST['entry_or_exit'];
    $searchedUserId = $_POST['searched_user_id']; // Recuperar el ID del usuario buscado del formulario

    // Usar el ID del usuario buscado
    if (!empty($project_id) && !empty($entry_or_exit) && !empty($searchedUserId)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO work_register (user_id, project_id, entry_or_exit, timestamp) VALUES (:user_id, :project_id, :entry_or_exit, NOW())");
            $stmt->bindParam(':user_id', $searchedUserId, PDO::PARAM_INT); // ID del usuario buscado
            $stmt->bindParam(':project_id', $project_id, PDO::PARAM_INT);
            $stmt->bindParam(':entry_or_exit', $entry_or_exit, PDO::PARAM_INT);
            $stmt->execute();

            $_SESSION['success'] = "Registro de entrada/salida exitoso para el usuario buscado.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error al registrar la entrada/salida: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Por favor selecciona el proyecto, el tipo de registro y busca un usuario válido.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Entrada/Salida</title>
    <link rel="stylesheet" href="./assets/css/styles.css">
    <style>
        .searched-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 15px;
        }

        .searched-info img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #ccc;
        }

        .alert {
            margin-top: 10px;
            padding: 10px;
            border-radius: 4px;
        }

        .alert.success { background-color: #d4edda; color: #155724; }
        .alert.error { background-color: #f8d7da; color: #721c24; }
    </style>
    <script>
        function updateTime() {
            var currentTime = new Date();
            var hours = currentTime.getHours().toString().padStart(2, '0');
            var minutes = currentTime.getMinutes().toString().padStart(2, '0');
            var seconds = currentTime.getSeconds().toString().padStart(2, '0');
            var timeString = hours + ':' + minutes + ':' + seconds;
            document.getElementById('real-time').innerText = timeString;
        }
        setInterval(updateTime, 1000);
    </script>
</head>
<body onload="updateTime()">

    <div class="container">
        <h1>Registro de Entrada/Salida</h1>

        <div class="current-time">
            <h2>Hora Actual: <span id="real-time"></span></h2>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert success">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <!-- Formulario para buscar usuario -->
        <div class="input-group">
            <label for="last_digits">Buscar Usuario por Últimos Dígitos del Documento</label>
            <form action="entry_exit_register.php" method="POST">
                <input type="text" name="last_digits" id="last_digits" placeholder="Ingrese los últimos dígitos" required>
                <button type="submit" name="search_user">Buscar</button>
            </form>
        </div>

        <?php if (!empty($searchedName)): ?>
            <div class="searched-info">
                <?php 
                // Verificar si la foto existe
                if (!empty($searchedPhoto) && file_exists($searchedPhoto)) {
                    echo "<img src='" . htmlspecialchars($searchedPhoto) . "' alt='Foto del usuario'>";
                } else {
                    echo "<img src='uploads/default.jpg' alt='Foto por defecto'>";
                }
                ?>
                <div><?= htmlspecialchars($searchedName) ?></div>
            </div>
        <?php endif; ?>

        <!-- Formulario para registrar entrada/salida -->
        <?php if ($searchedUserId): ?>
            <form action="entry_exit_register.php" method="POST">
                <input type="hidden" name="searched_user_id" value="<?= htmlspecialchars($searchedUserId) ?>">
                <div class="input-group">
                    <label for="project_id">Proyecto</label>
                    <select name="project_id" id="project_id" required>
                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM projects");
                        $stmt->execute();
                        foreach ($stmt->fetchAll() as $project) {
                            echo "<option value='{$project['id']}'>{$project['name']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="input-group">
                    <label for="entry_or_exit">Entrada o Salida</label>
                    <select name="entry_or_exit" id="entry_or_exit" required>
                        <option value="1">Entrada</option>
                        <option value="2">Salida</option>
                    </select>
                </div>

                <div class="button-group">
                    <button type="submit" name="register_entry_exit">Registrar</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
