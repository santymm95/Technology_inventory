<?php
session_start(); // Inicia una sesión o reanuda una existente

// Función para iniciar sesión
function login($email, $password) {
    global $pdo; // Accede a la conexión PDO global

    // Consulta SQL para verificar si el usuario existe
    $stmt = $pdo->prepare("SELECT id, nombre, email, password FROM usuarios WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    // Verifica si el usuario existe
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Verifica si la contraseña es correcta (comparar hash)
        if (password_verify($password, $user['password'])) {
            // Si la autenticación es exitosa, guarda los datos del usuario en la sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nombre'];
            $_SESSION['user_email'] = $user['email'];
            return true;
        } else {
            return false; // Contraseña incorrecta
        }
    }
    return false; // Usuario no encontrado
}

// Función para cerrar sesión
function logout() {
    // Elimina todas las variables de sesión
    session_unset();
    // Destruye la sesión
    session_destroy();
}
?>
