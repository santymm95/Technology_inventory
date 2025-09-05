<?php
session_start();

// Database connection
$host = 'localhost';
$dbname = 'acema_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES 'utf8'");
} catch (PDOException $e) {
    die("Database connection error: " . $e->getMessage());
}

// Check if documento is provided
if (isset($_POST['documento'])) {
    $documento = $_POST['documento'];

    // Query to search user by documento
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE documento = :documento LIMIT 1");
    $stmt->bindParam(':documento', $documento);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Return user data if found
        echo json_encode(['success' => true, 'data' => $user]);
    } else {
        // No user found
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Número de documento no proporcionado.']);
}
?>
