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

// Conexión adicional a la base de datos acema_db
$acema_dbname = 'acema_db';
$conn_acema = new mysqli($host, $username, $password, $acema_dbname);

if ($conn_acema->connect_error) {
    die("Conexión a acema_db fallida: " . $conn_acema->connect_error);
}
$conn_acema->set_charset("utf8");
?>
