<?php
// Iniciar sesión
session_start();

// Conexión a la base de datos
$servername = "localhost";
$username = "root"; // Cambia según tu configuración
$password = ""; // Cambia según tu configuración
$dbname = "acema_db"; // Nombre de tu base de datos

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Comprobar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Verificar si los datos del formulario fueron enviados
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $accion = $_POST['accion'];
    $proyecto_id = $_POST['proyecto_id'];
    $hora_actual = date('Y-m-d H:i:s');
    
    // Determinar la acción (ingreso o salida)
    if ($accion == 'ingreso') {
        $sql = "INSERT INTO work_register (user_id, project_id, entry_or_exit, timestamp) 
                VALUES ('$user_id', '$proyecto_id', 1, '$hora_actual')";
    } elseif ($accion == 'salida') {
        $sql = "INSERT INTO work_register (user_id, project_id, entry_or_exit, timestamp) 
                VALUES ('$user_id', '$proyecto_id', 0, '$hora_actual')";
    }

    // Ejecutar la consulta
    if ($conn->query($sql) === TRUE) {
        $message = 'Hora registrada exitosamente.';
        $message_type = 'success';
    } else {
        $message = 'Error: ' . $conn->error;
        $message_type = 'error';
    }

    // Redirigir con los parámetros
    header("Location: ../views/entry_exit_register.php?message=" . urlencode($message) . "&message_type=" . urlencode($message_type));
    exit;
}

// Cerrar conexión
$conn->close();
?>
