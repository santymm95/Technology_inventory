<?php
// AJAX endpoint para buscar el responsable actual de un equipo por número interno
include_once __DIR__ . '/../includes/conection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['numero_interno'])) {
    $numero = trim($_POST['numero_interno']);
    $stmt = $conn->prepare("SELECT u.first_name, u.last_name 
        FROM devices d 
        JOIN actas a ON a.device_id = d.id 
        JOIN users u ON a.user_id = u.id 
        WHERE d.internal_number = ? 
        ORDER BY a.fecha_entrega DESC LIMIT 1");
    $stmt->bind_param("s", $numero);
    $stmt->execute();
    $stmt->bind_result($first_name, $last_name);
    if ($stmt->fetch()) {
        echo $first_name . ' ' . $last_name;
    } else {
        echo '';
    }
    $stmt->close();
}
