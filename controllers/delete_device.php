<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../views/index.php');
    exit;
}
include_once __DIR__ . '/../includes/conection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($id > 0) {
        // Obtener el número interno para eliminar la carpeta de imágenes
        $sql = "SELECT internal_number FROM devices WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($internal_number);
        $stmt->fetch();
        $stmt->close();

        // Eliminar registros relacionados en tablas hijas (device_accessories, maintenance, etc.)
        $conn->query("DELETE FROM device_accessories WHERE device_id = $id");
        $conn->query("DELETE FROM maintenance WHERE device_id = $id");
        // Agrega aquí más tablas relacionadas si es necesario

        // Eliminar el registro del equipo
        $del_sql = "DELETE FROM devices WHERE id = ?";
        $del_stmt = $conn->prepare($del_sql);
        $del_stmt->bind_param("i", $id);
        $del_stmt->execute();
        $del_stmt->close();

        // Eliminar la carpeta de imágenes asociada
        if (!empty($internal_number)) {
            $dir = realpath(__DIR__ . '/../uploads/' . $internal_number);
            if ($dir && is_dir($dir)) {
                $files = glob($dir . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                rmdir($dir);
            }
        }

        header("Location: ../views/views_divices.php");
        exit;
    } else {
        header("Location: ../views/ti.php");
        exit;
    }
} else {
    header("Location: ../views/ti.php");
    exit;
}
