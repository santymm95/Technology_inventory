<?php
// delete_user.php
if (isset($_GET['id'])) {
    $userId = intval($_GET['id']);

    $conn = new mysqli("localhost", "root", "", "acema_db");
    $conn->set_charset("utf8mb4");

    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        echo "<script>alert('Usuario eliminado correctamente.'); window.location.href='../views/create_user.php';</script>";
    } else {
        echo "Error al eliminar usuario: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "ID de usuario no proporcionado.";
}
