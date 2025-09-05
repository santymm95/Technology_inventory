<?php
session_start();
// Corregir la ruta del include para que apunte al archivo correcto
include_once __DIR__ . '/../includes/conection.php';

// Cabeceras para descarga CSV con BOM para Excel y UTF-8
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="mantenimientos.csv"');

// Escribir BOM UTF-8 para evitar caracteres raros en Excel
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// Encabezados
fputcsv($output, [
    'ID Equipo',
    'Número Interno',
    'Fecha de Compra',
    'ID Mantenimiento',
    'Fecha Mantenimiento',
    'Descripción',
    'Tipo',
    'Responsable',
    'Externo',
    'Año',
    'Mes esperado',
    '¿Atraso?'
]);

// Consulta todos los equipos y mantenimientos
$sql = "SELECT 
            d.id AS device_id,
            d.internal_number,
            d.purchase_date,
            m.id AS maintenance_id,
            m.date AS maintenance_date,
            m.description,
            m.type,
            m.responsible,
            m.external
        FROM devices d
        LEFT JOIN maintenance m ON m.device_id = d.id
        ORDER BY d.internal_number ASC, m.date ASC";
$res = $conn->query($sql);

$equipos = [];
// Obtener datos de compra para cada equipo
$res_equipos = $conn->query("SELECT id, purchase_date FROM devices");
while ($row = $res_equipos->fetch_assoc()) {
    $equipos[$row['id']] = $row['purchase_date'];
}

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $anio = '';
        $mes_esperado = '';
        $atraso = '';
        $device_id = $row['device_id'];
        $fecha_mant = $row['maintenance_date'];
        $fecha_compra = $equipos[$device_id] ?? null;

        if ($fecha_compra && $fecha_compra !== '0000-00-00') {
            $anio_compra = (int)date('Y', strtotime($fecha_compra));
            $mes_compra = (int)date('n', strtotime($fecha_compra));
            if ($fecha_mant) {
                $anio_mant = (int)date('Y', strtotime($fecha_mant));
                $mes_mant = (int)date('n', strtotime($fecha_mant));
                $anio = $anio_mant;
                $mes_esperado = $mes_compra;
                // Atraso: si el mantenimiento no fue en el mes esperado de ese año
                $atraso = ($mes_mant != $mes_compra) ? 'Sí' : 'No';
            }
        }

        fputcsv($output, [
            $row['device_id'],
            $row['internal_number'],
            $row['purchase_date'],
            $row['maintenance_id'],
            $row['maintenance_date'],
            $row['description'],
            $row['type'],
            $row['responsible'],
            $row['external'],
            $anio,
            $mes_esperado,
            $atraso
        ]);
    }
}

fclose($output);
exit;
