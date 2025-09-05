<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

// Incluir la conexión a la base de datos
require_once '../includes/db.php'; // Asegúrate de que el archivo db.php está en la carpeta 'includes'

// Manejar envío del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $lider_id = $_POST['lider_id']; // Obtener el ID del líder seleccionado

    if ($nombre !== '' && $lider_id !== '') {
        // Usando PDO para preparar y ejecutar la consulta
        $sql = "INSERT INTO projects (name, leader_id) VALUES (:nombre, :lider_id)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':lider_id', $lider_id, PDO::PARAM_INT);

        try {
            $stmt->execute();
            $mensaje = "Proyecto creado exitosamente.";
        } catch (PDOException $e) {
            $error = "Error al crear el proyecto: " . $e->getMessage();
        }
    } else {
        $error = "El nombre del proyecto y el líder son obligatorios.";
    }
}

// Obtener los usuarios con el rol de "líder" (rol_id = 2)
$result = $pdo->query("SELECT id, first_name, last_name FROM users WHERE role_id = 2");
$usuarios_lideres = $result->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Proyecto</title>
    <link rel="stylesheet" href="../assets/css/create_project.css"> <!-- Asegúrate de que la ruta sea correcta -->
</head>
<body>
    <?php include './layout.php'; ?>

    <div class="main-content">
        <div class="card">
            <h2>Crear Nuevo Proyecto</h2>
            <?php if (isset($mensaje)) echo "<p class='success'>$mensaje</p>"; ?>
            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>

            <form method="post" class="form">
                <label for="nombre">Nombre del Proyecto:</label>
                <input type="text" name="nombre" id="nombre" required>

                <label for="lider_id">Seleccionar Líder:</label>
                <select name="lider_id" id="lider_id" required>
                    <option value="">Seleccione un líder</option>
                    <?php foreach ($usuarios_lideres as $usuario): ?>
                        <option value="<?= $usuario['id']; ?>">
                            <?= $usuario['first_name'] . ' ' . $usuario['last_name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn">Crear Proyecto</button>
            </form>
        </div>
       
    </div>
</body>
</html>
