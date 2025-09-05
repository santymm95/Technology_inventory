<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "acema2";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>
