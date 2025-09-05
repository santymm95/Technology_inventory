<?php
require_once '../db/db_connection.php';

// Función para crear un proyecto
function createProject($name, $leader_id) {
    global $conn;

    $query = "INSERT INTO projects (name, leader_id) VALUES ('$name', '$leader_id')";
    if (mysqli_query($conn, $query)) {
        return true;
    } else {
        return false;
    }
}

// Función para asignar un líder a un proyecto
function assignLeaderToProject($project_id, $leader_id) {
    global $conn;

    $query = "UPDATE projects SET leader_id = '$leader_id' WHERE id = '$project_id'";
    if (mysqli_query($conn, $query)) {
        return true;
    } else {
        return false;
    }
}
?>
