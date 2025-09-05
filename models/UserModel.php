<?php
class UserModel {
    private $conn;

    public function __construct() {
        $this->conn = new mysqli("localhost", "root", "", "acema_db");
        $this->conn->set_charset("utf8mb4");
    }

    // Editar Usuario
    public function editUser($user_id, $first_name, $last_name, $email, $role_id) {
        $stmt = $this->conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, role_id = ? WHERE id = ?");
        $stmt->bind_param("sssii", $first_name, $last_name, $email, $role_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    // Eliminar Usuario
    public function deleteUser($user_id) {
        $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }

    // Restablecer Contraseña
    public function resetPassword($user_id) {
        $new_password = password_hash("123456", PASSWORD_DEFAULT);  // Contraseña predeterminada
        $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $new_password, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    public function __destruct() {
        $this->conn->close();
    }
}
?>
