<?php
// Asumimos que este archivo se encuentra en '../controllers/controlcontroller.php'
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../includes/db.php'; // Asegúrate de que esta ruta sea correcta

// Verifica si se recibió una acción y el proyecto
if (isset($_POST['accion']) && isset($_POST['proyecto_id'])) {
    $usuario_id = $_POST['usuario_id'];
    $proyecto_id = $_POST['proyecto_id'];
    $accion = $_POST['accion'];

    // Realiza la acción en la base de datos (por ejemplo, iniciar o finalizar jornada)
    if ($accion == 'inicio') {
        $query = "INSERT INTO jornadas (usuario_id, proyecto_id, hora_inicio) VALUES ('$usuario_id', '$proyecto_id', NOW())";
    } elseif ($accion == 'fin') {
        $query = "UPDATE jornadas SET hora_fin = NOW() WHERE usuario_id = '$usuario_id' AND proyecto_id = '$proyecto_id' AND hora_fin IS NULL";
    }

    if ($conn->query($query)) {
        echo "Jornada " . ($accion == 'inicio' ? "iniciada" : "finalizada") . " correctamente.";
    } else {
        echo "Error al registrar la jornada.";
    }
}
?>

    <!DOCTYPE html>
    <html lang="es">
    <head>
    <meta charset="UTF-8">
    <title>Registro de Asistencia</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    </head>
    <body>

    <h2>Registro de Asistencia</h2>
    <form action="../controllers/controlcontroller.php" method="post"> <!-- Cambié la acción aquí -->
        <label for="proyecto">Proyecto:</label>
        <select name="proyecto_id" id="proyecto" required>
        <option value="">Seleccione un proyecto</option>
        <?php while($proy = $proyectos->fetch_assoc()): ?>
            <option value="<?= $proy['id'] ?>"><?= $proy['nombre'] ?></option>
        <?php endwhile; ?>
        </select>

        <input type="hidden" name="usuario_id" value="<?= $_SESSION['user']['id'] ?>">

        <button type="submit" name="accion" value="inicio">Iniciar Jornada</button>
        <button type="submit" name="accion" value="fin">Finalizar Jornada</button>
    </form>

    </body>
    </html>
