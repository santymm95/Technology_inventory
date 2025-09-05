<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "acema_db";

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT 
                ar.id,
                CONCAT(u.first_name, ' ', u.last_name) AS full_name, 
                pr.name AS project_name,
                ar.date, ar.time_in, ar.time_out, ar.total_hours, ar.extra_hours, ar.estado
            FROM attendance_records ar
            LEFT JOIN users u ON ar.user_id = u.id
            LEFT JOIN projects pr ON u.project_id = pr.id
            WHERE ar.estado = 'enviado'";

    $params = [];
    if (!empty($start_date)) {
        $sql .= " AND ar.date >= :start_date";
        $params[':start_date'] = $start_date;
    }
    if (!empty($end_date)) {
        $sql .= " AND ar.date <= :end_date";
        $params[':end_date'] = $end_date;
    }

    $sql .= " ORDER BY ar.date DESC";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar acción de aprobar o rechazar
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        $accion = $_POST['accion'] === 'aprobar' ? 'aprobado' : 'rechazado';
        $update = $pdo->prepare("UPDATE attendance_records SET estado = :estado WHERE id = :id");
        $update->execute([':estado' => $accion, ':id' => $id]);
        // Redirigir para evitar reenvío de formulario
        header("Location: " . $_SERVER['PHP_SELF'] . "?start_date=" . urlencode($start_date) . "&end_date=" . urlencode($end_date));
        exit;
    }

} catch (PDOException $e) {
    die("Error de conexión o consulta: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Registros Enviados a Revisión</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
        }

        h1 {
            margin-top: 50px;
            text-align: center;
            padding: 20px;
        }

        table {
            
            border-collapse: collapse;
            width: 65%;
            margin: 0 auto 50px;
            background-color: white;
        }

        th,
        td {
            padding: 5px;
            border: 1px solid #ddd;
            text-align: center;
        }

        th {
            background-color: #215ba0;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        form {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
            padding-bottom: 20px;
        }

        input[type="date"],
        button {
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

        .btn-icon {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 18px;
            margin: 0 2px;
        }

        .btn-aprobar {
            color: #28a745;
        }

        .btn-rechazar {
            color: #dc3545;
        }
    </style>
    <?php include 'layout.php'; ?>
</head>

<body>
    <h1>Registros Enviados a Revisión</h1>
   
    <form method="get">
        <label>Desde: <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>"></label>
        <label>Hasta: <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>"></label>
        <button type="submit">Filtrar</button>
    </form>
    <table>
        <thead>
            <tr>
                <th><input type="checkbox" id="checkAll"></th>
                <th>Nombre Completo</th>
                <th>Proyecto</th>
                <th>Fecha</th>
                <th>Hora Entrada</th>
                <th>Hora Salida</th>
                <th>Horas Extras</th>
                <th>Total Horas</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <form method="post" id="bulkForm">
            <?php if ($records): ?>
                <?php foreach ($records as $row): ?>
                    <tr>
                        <td>
                            <input type="checkbox" name="ids[]" value="<?= $row['id'] ?>">
                        </td>
                        <td><?= htmlspecialchars($row['full_name']) ?></td>
                        <td><?= htmlspecialchars($row['project_name']) ?></td>
                        <td><?= htmlspecialchars($row['date']) ?></td>
                        <td><?= htmlspecialchars($row['time_in']) ?></td>
                        <td><?= htmlspecialchars($row['time_out']) ?></td>
                        <td><?= htmlspecialchars($row['extra_hours']) ?></td>
                        <td><?= htmlspecialchars($row['total_hours']) ?></td>
                        <td><?= htmlspecialchars($row['estado']) ?></td>
                        <td>
                            <button type="submit" name="accion_ind" value="aprobar_<?= $row['id'] ?>" class="btn-icon btn-aprobar" title="Aprobar">&#10003;</button>
                            <button type="submit" name="accion_ind" value="rechazar_<?= $row['id'] ?>" class="btn-icon btn-rechazar" title="Rechazar">&#10007;</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10">No se encontraron registros enviados a revisión.</td>
                </tr>
            <?php endif; ?>
            </form>
        </tbody>
    </table>
    <div style="width:65%;margin:0 auto 20px;text-align:right;">
        <a href="review_history.php" style="display:inline-block;padding:10px 18px;background:#215ba0;color:#fff;border-radius:5px;text-decoration:none;font-weight:bold;"
           onclick="window.location.href='review_history.php';return false;">Ver Histórico</a>
    </div>
    <?php if ($records): ?>
    <div style="width:65%;margin:10px auto 30px;text-align:right;">
        <form method="post" id="bulkActionForm">
            <input type="hidden" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
            <input type="hidden" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
            <button type="submit" name="accion_masiva" value="aprobar" class="btn-icon btn-aprobar" style="font-size:16px;padding:8px 18px;margin-right:10px;">Aprobar seleccionados</button>
            <button type="submit" name="accion_masiva" value="rechazar" class="btn-icon btn-rechazar" style="font-size:16px;padding:8px 18px;">Rechazar seleccionados</button>
        </form>
    </div>
    <?php endif; ?>

    <script>
    // Seleccionar todos los checkboxes
    document.getElementById('checkAll').addEventListener('change', function() {
        var checks = document.querySelectorAll('input[name="ids[]"]');
        for (var i = 0; i < checks.length; i++) {
            checks[i].checked = this.checked;
        }
    });

    // Enviar ids seleccionados al formulario masivo
    document.getElementById('bulkActionForm').addEventListener('submit', function(e) {
        var checks = document.querySelectorAll('input[name="ids[]"]:checked');
        if (checks.length === 0) {
            alert('Seleccione al menos un registro.');
            e.preventDefault();
            return false;
        }
        // Agregar los ids seleccionados al formulario masivo
        var form = document.getElementById('bulkActionForm');
        // Elimina ids previos
        var prev = form.querySelectorAll('input[name="ids[]"]');
        prev.forEach(function(el){el.remove();});
        // Agrega los nuevos
        checks.forEach(function(chk){
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'ids[]';
            hidden.value = chk.value;
            form.appendChild(hidden);
        });
    });
    </script>
<?php
$alerta = '';
// Inicia la sesión al principio del archivo para asegurar que $_SESSION['user']['id'] esté disponible
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Procesar acción de aprobar o rechazar individual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_ind'])) {
    // Cambia a $_SESSION['user']['id'] según tu login
    if (!isset($_SESSION['user']['id']) || !is_numeric($_SESSION['user']['id'])) {
        $alerta = "No se puede registrar la acción: usuario no autenticado.";
    } else {
        $aprobador_id = intval($_SESSION['user']['id']);
        $fecha_revision = date('Y-m-d H:i:s');

        if (strpos($_POST['accion_ind'], 'aprobar_') === 0) {
            $id = intval(substr($_POST['accion_ind'], 8));
            $update = $pdo->prepare("UPDATE attendance_records SET estado = 'aprobado', updated_by = :updated_by, updated_at = :updated_at WHERE id = :id");
            $update->execute([
                ':updated_by' => $aprobador_id,
                ':updated_at' => $fecha_revision,
                ':id' => $id
            ]);
            $alerta = "Registro aprobado correctamente.";
        } elseif (strpos($_POST['accion_ind'], 'rechazar_') === 0) {
            $id = intval(substr($_POST['accion_ind'], 9));
            $update = $pdo->prepare("UPDATE attendance_records SET estado = 'rechazado', updated_by = :updated_by, updated_at = :updated_at WHERE id = :id");
            $update->execute([
                ':updated_by' => $aprobador_id,
                ':updated_at' => $fecha_revision,
                ':id' => $id
            ]);
            $alerta = "Registro rechazado correctamente.";
        }
    }
    // No redirigir, solo mostrar alerta
}

// Procesar acción masiva
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_masiva']) && isset($_POST['ids'])) {
    if (!isset($_SESSION['user']['id']) || !is_numeric($_SESSION['user']['id'])) {
        $alerta = "No se puede registrar la acción: usuario no autenticado.";
    } else {
        $aprobador_id = intval($_SESSION['user']['id']);
        $fecha_revision = date('Y-m-d H:i:s');
        $accion = $_POST['accion_masiva'] === 'aprobar' ? 'aprobado' : 'rechazado';
        $ids = array_map('intval', $_POST['ids']);
        if ($ids) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $sql = "UPDATE attendance_records SET estado = ?, updated_by = ?, updated_at = ? WHERE id IN ($in)";
            $params = array_merge([$accion, $aprobador_id, $fecha_revision], $ids);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $alerta = $accion === 'aprobado' ? "Registros aprobados correctamente." : "Registros rechazados correctamente.";
        }
    }
    // No redirigir, solo mostrar alerta
}
?>
    <?php if ($alerta): ?>
        <script>
            alert("<?= addslashes($alerta) ?>");
            window.location.href = window.location.pathname + window.location.search;
        </script>
    <?php endif; ?>
    </body>
</html>