<?php
// Configuración de la base de datos
$host = 'localhost';
$dbname = 'inventory';
$username = 'root';
$password = '';

// Crear una instancia de MySQLi para la conexión
$conn = new mysqli($host, $username, $password, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Opcional: establecer el charset a utf8
$conn->set_charset("utf8");
?>
