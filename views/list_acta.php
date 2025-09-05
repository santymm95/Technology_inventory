<?php
// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "inventory");
if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

// Consulta con JOIN
$sql = "
SELECT 
    actas.id,
    actas.fecha_entrega,
    actas.accesorios,
    actas.observacion,
    actas.fecha_devolucion,
    actas.estado_devolucion,
    actas.observacion_devolucion,
    actas.entregado_por,
    actas.recibido_por,
    users.first_name,
    users.last_name,
    users.document,
    users.department,
    users.position,
    devices.internal_number,
    devices.serial,
    devices.purchase_date
FROM actas
LEFT JOIN users ON actas.user_id = users.id
LEFT JOIN devices ON actas.device_id = devices.id
ORDER BY actas.fecha_entrega DESC
";

$resultado = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Actas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 2em;
            background: #f4f4f4;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        th, td {
            border: 1px solid #ccc;
            padding: 0.7em;
            text-align: center;
        }
        th {
            background: #333;
            color: white;
        }
        h2 {
            margin-bottom: 1em;
            text-align: center;
        }
        .btn-ver {
            background: #215ba0;
            color: #fff;
            padding: 0.5em 1em;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.95em;
        }
        .btn-ver:hover {
            background: #184880;
        }
    </style>
</head>
<body>

<h2>Listado de Actas de Entrega</h2>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre Usuario</th>
            <th>Documento</th>
            <th>Cargo</th>
            <th>Departamento</th>
            <th>Equipo</th>
            <th>Serial</th>
            <th>Fecha Compra</th>
            <th>Fecha Entrega</th>
            <th>Accesorios</th>
            <th>Observación</th>
            <th>Fecha Devolución</th>
            <th>Estado Devolución</th>
            <th>Obs. Devolución</th>
            <th>Entregado Por</th>
            <th>Recibido Por</th>
            <th>Ver Documento</th>
        </tr>
    </thead>
    <tbody>
        <?php while($fila = $resultado->fetch_assoc()): ?>
            <tr>
                <td><?= $fila['id'] ?></td>
                <td><?= htmlspecialchars($fila['first_name'] . ' ' . $fila['last_name']) ?></td>
                <td><?= htmlspecialchars($fila['document']) ?></td>
                <td><?= htmlspecialchars($fila['position']) ?></td>
                <td><?= htmlspecialchars($fila['department']) ?></td>
                <td><?= htmlspecialchars($fila['internal_number']) ?></td>
                <td><?= htmlspecialchars($fila['serial']) ?></td>
                <td><?= htmlspecialchars($fila['purchase_date']) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($fila['fecha_entrega'])) ?></td>
                <td><?= htmlspecialchars($fila['accesorios']) ?></td>
                <td><?= htmlspecialchars($fila['observacion']) ?></td>
                <td><?= $fila['fecha_devolucion'] ? date('d/m/Y', strtotime($fila['fecha_devolucion'])) : '-' ?></td>
                <td><?= htmlspecialchars($fila['estado_devolucion']) ?></td>
                <td><?= htmlspecialchars($fila['observacion_devolucion']) ?></td>
                <td><?= htmlspecialchars($fila['entregado_por']) ?></td>
                <td><?= htmlspecialchars($fila['recibido_por']) ?></td>
                <td>
                    <a class="btn-ver" href="ver_acta.php?id=<?= urlencode($fila['id']) ?>" target="_blank">Ver Documento</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

</body>
</html>

<?php
$conexion->close();
?>
