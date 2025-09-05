<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../views/index.php');
    exit;
}
include_once __DIR__ . '/../includes/conection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $document = trim($_POST['document'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $active = isset($_POST['active']) ? intval($_POST['active']) : 0;

    if ($first_name && $last_name && $document) {
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, document, position, department, active) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $first_name, $last_name, $document, $position, $department, $active);
        if ($stmt->execute()) {
            // Crear carpeta en documents con el nombre del documento del usuario
            $docBase = realpath(__DIR__ . '/../documents');
            $userDir = $docBase . DIRECTORY_SEPARATOR . $document;
            if (!is_dir($userDir)) {
                mkdir($userDir, 0777, true);
            }
            $msg = "Usuario registrado correctamente.";
        } else {
            $msg = "Error al registrar usuario: " . $stmt->error;
        }
        $stmt->close();
        header("Location: ../views/user_form.php?msg=" . urlencode($msg));
        exit;
    } else {
        header("Location: ../views/user_form.php?msg=" . urlencode("Todos los campos obligatorios deben ser completados."));
        exit;
    }
} else {
    header('Location: ../views/user_form.php');
    exit;
}
