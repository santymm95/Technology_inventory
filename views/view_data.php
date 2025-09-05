<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "acema_db";

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$order_by = $_GET['order_by'] ?? 'ar.date';
$order_dir = $_GET['order_dir'] ?? 'DESC';

// Filtrar columnas válidas para ordenar
$allowed_columns = ['full_name', 'project_name', 'ar.date', 'ar.time_in', 'ar.time_out', 'ar.total_hours', 'ar.extra_hours'];
$allowed_directions = ['ASC', 'DESC'];

if (!in_array($order_by, $allowed_columns)) $order_by = 'ar.date';
if (!in_array($order_dir, $allowed_directions)) $order_dir = 'DESC';

$alerta = '';

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Enviar data a revisión
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_revision'])) {
        // Solo permite enviar si ambos filtros de fecha están presentes
        if (!empty($start_date) && !empty($end_date)) {
            $update_sql = "UPDATE attendance_records SET estado = 'enviado' WHERE 1=1";
            $params = [];
            $update_sql .= " AND date >= :start_date";
            $params[':start_date'] = $start_date;
            $update_sql .= " AND date <= :end_date";
            $params[':end_date'] = $end_date;
            $stmt_update = $pdo->prepare($update_sql);
            $stmt_update->execute($params);
            echo "<script>alert('Datos enviados a revisión correctamente.');</script>";
        } else {
            echo "<script>alert('Debe filtrar por fechas antes de enviar a revisión.');</script>";
        }
    }

    // Consultar los registros
    $sql = "SELECT 
                ar.id,
                CONCAT(u.first_name, ' ', u.last_name) AS full_name, 
                pr.name AS project_name,
                ar.date, ar.time_in, ar.time_out, ar.total_hours, ar.extra_hours, ar.estado
            FROM attendance_records ar
            LEFT JOIN users u ON ar.user_id = u.id
            LEFT JOIN projects pr ON u.project_id = pr.id";

    $conditions = [];
    if (!empty($start_date)) $conditions[] = "ar.date >= :start_date";
    if (!empty($end_date))   $conditions[] = "ar.date <= :end_date";
    if ($conditions) $sql .= " WHERE " . implode(" AND ", $conditions);

    $sql .= " ORDER BY $order_by $order_dir";

    $stmt = $pdo->prepare($sql);
    if (!empty($start_date)) $stmt->bindParam(':start_date', $start_date);
    if (!empty($end_date)) $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Actualizar las horas extras calculadas en la base de datos
    foreach ($records as $row) {
        $calculated_extras = calcularHorasExtras($row['time_in'], $row['time_out']);
        // Solo actualiza si el valor calculado es diferente al almacenado
        if ($row['extra_hours'] != $calculated_extras) {
            $update = $pdo->prepare("UPDATE attendance_records SET extra_hours = :extra_hours WHERE id = :id");
            $update->execute([
                ':extra_hours' => $calculated_extras,
                ':id' => $row['id']
            ]);
        }
    }

    // Vuelve a consultar los registros para mostrar los valores actualizados
    $stmt = $pdo->prepare($sql);
    if (!empty($start_date)) $stmt->bindParam(':start_date', $start_date);
    if (!empty($end_date)) $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (isset($_GET['export']) && $_GET['export'] === 'excel') {
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=asistencia_" . date('Ymd_His') . ".xls");
        header("Pragma: no-cache");
        header("Expires: 0");

        echo "<table border='1'>";
        echo "<tr>
            <th>Nombre Completo</th>
            <th>Proyecto</th>
            <th>Fecha</th>
            <th>Hora Entrada</th>
            <th>Hora Salida</th>
            <th>Horas Extras</th>
            <th>Total Horas</th>
            <th>Estado</th>
        </tr>";
        foreach ($records as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['project_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['date']) . "</td>";
            echo "<td>" . htmlspecialchars($row['time_in']) . "</td>";
            echo "<td>" . htmlspecialchars($row['time_out']) . "</td>";
            echo "<td>" . htmlspecialchars($row['extra_hours']) . "</td>";
            echo "<td>" . htmlspecialchars($row['total_hours']) . "</td>";
            echo "<td>" . htmlspecialchars($row['estado'] ?? 'pendiente') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        exit;
    }

} catch (PDOException $e) {
    die("Error de conexión o consulta: " . $e->getMessage());
}

function sortLink($column, $label, $current_order_by, $current_order_dir, $start_date, $end_date) {
    $dir = ($current_order_by === $column && $current_order_dir === 'ASC') ? 'DESC' : 'ASC';
    return "<a href=\"?order_by=$column&order_dir=$dir&start_date=$start_date&end_date=$end_date\">$label</a>";
}

// Función para calcular horas extras según la franja 7:00 a 17:00
function calcularHorasExtras($time_in, $time_out) {
    if (!$time_in || !$time_out) return 0.0;
    $start = DateTime::createFromFormat('H:i:s', $time_in);
    $end = DateTime::createFromFormat('H:i:s', $time_out);
    if (!$start || !$end) return 0.0;

    $franja_inicio = DateTime::createFromFormat('H:i:s', '07:00:00');
    $franja_fin = DateTime::createFromFormat('H:i:s', '17:00:00');

    $horas_extras = 0.0;

    // Si la entrada es antes de la franja, sumar ese tiempo como extra
    if ($start < $franja_inicio) {
        $intervalo = $franja_inicio->diff($start);
        $horas_extras += ($intervalo->h + $intervalo->i/60 + $intervalo->s/3600);
    }

    // Si la salida es después de la franja, sumar ese tiempo como extra
    if ($end > $franja_fin) {
        $intervalo = $end->diff($franja_fin);
        $horas_extras += ($intervalo->h + $intervalo->i/60 + $intervalo->s/3600);
    }

    // Si la entrada es después de la franja de fin, todo el tiempo es extra
    if ($start > $franja_fin) {
        $intervalo = $end->diff($start);
        $horas_extras = ($intervalo->h + $intervalo->i/60 + $intervalo->s/3600);
    }

    // Si la salida es antes de la franja de inicio, todo el tiempo es extra
    if ($end < $franja_inicio) {
        $intervalo = $start->diff($end);
        $horas_extras = ($intervalo->h + $intervalo->i/60 + $intervalo->s/3600);
    }

    return round(abs($horas_extras), 2);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Asistencia con Proyecto</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        h1 {
            text-align: center;
            padding: 20px;
        }

        .container{
            
        }
        form {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
            padding-bottom: 20px;
        }
        input[type="date"], button {
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            background-color: #215ba0;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #18417a;
        }
        table {
            border-collapse: collapse;
            width: 70%;
            margin: 0 auto 50px;
            background-color: white;
        }
        th, td {
            padding: 5px;
            border: 1px solid #ddd;
            text-align: center;
        }
        th {
            background-color: #215ba0;
            color: white;
        }
        th a {
            color: white;
            text-decoration: none;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .acciones {
            text-align: center;
            margin-top: 10px;
        }
        .alerta-exito {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 10px 20px;
            margin: 20px auto;
            width: 70%;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }
        @media (max-width: 768px) {
            form {
                flex-direction: column;
                align-items: center;
            }
            input[type="date"], button {
                width: 80%;
                max-width: 300px;
            }
            
        }
    </style>
</head>
  <?php include 'layout.php'; ?>
<body>
    <div class="container">
        <?php if ($alerta): ?>
            <div class="alerta-exito"><?= htmlspecialchars($alerta) ?></div>
        <?php endif; ?>
        <h1>Registro de Asistencia con Proyecto</h1>
        
        <form method="get">
            <label>Desde: <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" required></label>
            <label>Hasta: <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" required></label>
            <button type="submit">Filtrar</button>
            <button type="submit" name="export" value="excel">Exportar a Excel</button>
        </form>
        
        <form method="post" class="acciones">
            <input type="hidden" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
            <input type="hidden" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
            <button type="submit" name="enviar_revision">Enviar a revisión</button>
        </form>

        <form method="get" action="graficas.php" style="text-align:center; margin-bottom:20px;">
            <input type="hidden" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
            <input type="hidden" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
            <button type="submit" class="btn" style="background:#28a745;">Mostrar gráfica</button>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th><?= sortLink('full_name', 'Nombre Completo', $order_by, $order_dir, $start_date, $end_date) ?></th>
                <th><?= sortLink('project_name', 'Proyecto', $order_by, $order_dir, $start_date, $end_date) ?></th>
                <th><?= sortLink('ar.date', 'Fecha', $order_by, $order_dir, $start_date, $end_date) ?></th>
                <th><?= sortLink('ar.time_in', 'Hora Entrada', $order_by, $order_dir, $start_date, $end_date) ?></th>
                <th><?= sortLink('ar.time_out', 'Hora Salida', $order_by, $order_dir, $start_date, $end_date) ?></th>
                <th><?= sortLink('ar.extra_hours', 'Horas Extras', $order_by, $order_dir, $start_date, $end_date) ?></th>
                <th><?= sortLink('ar.total_hours', 'Total Horas', $order_by, $order_dir, $start_date, $end_date) ?></th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($records): ?>
                <?php foreach ($records as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['full_name']) ?></td>
                        <td><?= htmlspecialchars($row['project_name']) ?></td>
                        <td><?= htmlspecialchars($row['date']) ?></td>
                        <td>
                            <?php
                                $hora_limite = DateTime::createFromFormat('H:i:s', '07:00:00');
                                $hora_entrada = DateTime::createFromFormat('H:i:s', $row['time_in']);
                                if ($hora_entrada && $hora_entrada > $hora_limite) {
                                    // Llegada tarde
                                    echo '<span style="color:red;font-weight:bold;">' . htmlspecialchars($row['time_in']) . '</span>';
                                } else {
                                    echo htmlspecialchars($row['time_in']);
                                }
                            ?>
                        </td>
                        <td><?= htmlspecialchars($row['time_out']) ?></td>
                        <td>
                            <?php
                                if (floatval($row['extra_hours']) > 0) {
                                    echo '<span style="color:green;font-weight:bold;">' . htmlspecialchars($row['extra_hours']) . '</span>';
                                } else {
                                    echo htmlspecialchars($row['extra_hours']);
                                }
                            ?>
                        </td>
                        <td><?= htmlspecialchars($row['total_hours']) ?></td>
                        <td>
                            <?php
                                $estado = $row['estado'] ?? 'pendiente';
                                if ($estado === 'aprobado') {
                                    echo '<span style="color:green;font-weight:bold;">' . htmlspecialchars($estado) . '</span>';
                                } elseif ($estado === 'rechazado') {
                                    echo '<span style="color:red;font-weight:bold;">' . htmlspecialchars($estado) . '</span>';
                                } else {
                                    echo htmlspecialchars($estado);
                                }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8">No se encontraron registros.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>
