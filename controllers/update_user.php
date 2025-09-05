<?php
if (isset($_POST['update_user'])) {
    // Obtener datos del formulario
    $userId = $_POST['id'];
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $documentNumber = $_POST['document_number'];
    $project = $_POST['project'];
    $enabled = isset($_POST['enabled']) ? 1 : 0;

    // Conexión a la base de datos
    $conn = new mysqli("localhost", "root", "", "acema_db");
    $conn->set_charset("utf8mb4");

    // Validar correo duplicado (excluyendo el usuario actual)
    $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $checkEmail->bind_param("si", $email, $userId);
    $checkEmail->execute();
    $result = $checkEmail->get_result();

    if ($result->num_rows > 0) {
        // Correo duplicado
        echo "<script>alert('El correo electrónico ya está en uso por otro usuario.'); window.location.href = '../views/edit_user.php?id=$userId';</script>";
    } else {
        // Actualizar datos del usuario
        $sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, document_number = ?, role_id = ?, project_id = ?, enabled = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssiisi", $firstName, $lastName, $email, $documentNumber, $role, $project, $enabled, $userId);

        if ($stmt->execute()) {
            echo "<script>alert('Usuario actualizado correctamente.'); window.location.href = '../views/create_user.php';</script>";
        } else {
            echo "<script>alert('Hubo un error al actualizar el usuario.'); window.location.href = '../views/edit_user.php?id=$userId';</script>";
        }

        $stmt->close();
    }

    $conn->close();
} else {
    // Acceso indebido
    echo "<script>window.location.href = '../views/usuarios.php';</script>";
    exit();
}
?>
