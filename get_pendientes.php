<?php
session_start();
require_once 'includes/db.php'; // Usar la conexión PDO

try {
    $sql = "SELECT estado, COUNT(*) as total FROM attendance_records GROUP BY estado";
    $stmt = $pdo->query($sql);
    $estados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $pendientes = 0;
    foreach ($estados as $row) {
        if (strtolower(trim($row['estado'])) === 'enviado') {
            $pendientes = (int)$row['total'];
            break;
        }
    }

    header('Content-Type: application/json');
    echo json_encode([
        'pendientes' => $pendientes,
        'debug' => $estados
    ]);
} catch (Exception $e) {
    header('Content-Type: application/json', true, 500);
    echo json_encode(['pendientes' => 0, 'error' => $e->getMessage()]);
}
