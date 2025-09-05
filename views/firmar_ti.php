<?php
include_once __DIR__ . '/../includes/conection.php';

$token = $_GET['token'] ?? '';
$doc = $_GET['doc'] ?? '';

if (!$token || !$doc) {
    die('Enlace inválido.');
}

// Verificar existencia del preoperacional
$stmt = $conn->prepare("SELECT * FROM vehicle_preoperation WHERE driver_document = ? LIMIT 1");
$stmt->bind_param("s", $doc);
$stmt->execute();
$result = $stmt->get_result();
$formulario = $result->fetch_assoc();

if (!$formulario) {
    die("Formulario no encontrado.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Firma Área TI</title>
    <style>
        body {
            font-family: sans-serif;
            background: #f4f7f9;
            padding: 2rem;
        }
        .container {
            max-width: 600px;
            margin: auto;
            background: #fff;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        canvas {
            border: 1px solid #ccc;
            width: 100%;
            height: 100px;
            border-radius: 6px;
        }
        button {
            margin-top: 1em;
            padding: 0.6em 1.2em;
            background: #2176ae;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1em;
            cursor: pointer;
        }
        button:hover {
            background: #195f8d;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Firma Área de TI</h2>
        <p>Conductor: <strong><?= htmlspecialchars($formulario['driver_name']) ?></strong></p>
        <p>Placa: <strong><?= htmlspecialchars($formulario['plate']) ?></strong></p>
        <for
