<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "acema_db";

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$project_id = $_GET['project_id'] ?? '';

// Obtener lista de proyectos para el filtro
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $proyectos = $pdo->query("SELECT id, name FROM projects ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

    // Horas extras y totales por usuario
    $sql = "SELECT 
                CONCAT(u.first_name, ' ', u.last_name) AS full_name, 
                SUM(ar.extra_hours) AS total_extras,
                SUM(ar.total_hours) AS total_horas,
                COUNT(ar.id) AS sesiones
            FROM attendance_records ar
            LEFT JOIN users u ON ar.user_id = u.id";
    $conditions = [];
    $params = [];
    if (!empty($start_date)) {
        $conditions[] = "ar.date >= :start_date";
        $params[':start_date'] = $start_date;
    }
    if (!empty($end_date)) {
        $conditions[] = "ar.date <= :end_date";
        $params[':end_date'] = $end_date;
    }
    if (!empty($project_id)) {
        $conditions[] = "u.project_id = :project_id";
        $params[':project_id'] = $project_id;
    }
    if ($conditions) $sql .= " WHERE " . implode(" AND ", $conditions);
    $sql .= " GROUP BY ar.user_id";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = [];
    $extras = [];
    $totales = [];
    $sesiones = [];
    foreach ($data as $row) {
        $labels[] = $row['full_name'];
        $extras[] = floatval($row['total_extras']);
        $totales[] = floatval($row['total_horas']);
        $sesiones[] = intval($row['sesiones']);
    }

    // Promedio de horas extras por usuario
    $promedio_extras = [];
    foreach ($data as $i => $row) {
        $ses = $sesiones[$i] ?: 1;
        $promedio_extras[] = round($extras[$i] / $ses, 2);
    }

    // Llegadas tarde por usuario
    $sql_tarde = "SELECT 
                    CONCAT(u.first_name, ' ', u.last_name) AS full_name, 
                    COUNT(ar.id) AS llegadas_tarde
                FROM attendance_records ar
                LEFT JOIN users u ON ar.user_id = u.id
                WHERE ar.time_in > '07:00:00'";
    if (!empty($start_date)) $sql_tarde .= " AND ar.date >= :start_date";
    if (!empty($end_date)) $sql_tarde .= " AND ar.date <= :end_date";
    if (!empty($project_id)) $sql_tarde .= " AND u.project_id = :project_id";
    $sql_tarde .= " GROUP BY ar.user_id";
    $stmt_tarde = $pdo->prepare($sql_tarde);
    if (!empty($start_date)) $stmt_tarde->bindValue(':start_date', $start_date);
    if (!empty($end_date)) $stmt_tarde->bindValue(':end_date', $end_date);
    if (!empty($project_id)) $stmt_tarde->bindValue(':project_id', $project_id);
    $stmt_tarde->execute();
    $data_tarde = $stmt_tarde->fetchAll(PDO::FETCH_ASSOC);

    $labels_tarde = [];
    $llegadas_tarde = [];
    foreach ($data_tarde as $row) {
        $labels_tarde[] = $row['full_name'];
        $llegadas_tarde[] = intval($row['llegadas_tarde']);
    }

    // Distribución de registros por día
    $sql_dias = "SELECT ar.date, COUNT(ar.id) as registros
                 FROM attendance_records ar
                 LEFT JOIN users u ON ar.user_id = u.id";
    $conditions_dias = [];
    $params_dias = [];
    if (!empty($start_date)) {
        $conditions_dias[] = "ar.date >= :start_date";
        $params_dias[':start_date'] = $start_date;
    }
    if (!empty($end_date)) {
        $conditions_dias[] = "ar.date <= :end_date";
        $params_dias[':end_date'] = $end_date;
    }
    if (!empty($project_id)) {
        $conditions_dias[] = "u.project_id = :project_id";
        $params_dias[':project_id'] = $project_id;
    }
    if ($conditions_dias) $sql_dias .= " WHERE " . implode(" AND ", $conditions_dias);
    $sql_dias .= " GROUP BY ar.date ORDER BY ar.date";
    $stmt_dias = $pdo->prepare($sql_dias);
    foreach ($params_dias as $k => $v) {
        $stmt_dias->bindValue($k, $v);
    }
    $stmt_dias->execute();
    $data_dias = $stmt_dias->fetchAll(PDO::FETCH_ASSOC);

    $labels_dias = [];
    $registros_dias = [];
    foreach ($data_dias as $row) {
        $labels_dias[] = $row['date'];
        $registros_dias[] = intval($row['registros']);
    }

    // Porcentaje de llegadas tarde por proyecto
    $sql_tarde_proy = "SELECT pr.name AS proyecto, 
                              COUNT(ar.id) AS total_registros,
                              SUM(CASE WHEN ar.time_in > '07:00:00' THEN 1 ELSE 0 END) AS llegadas_tarde
                       FROM attendance_records ar
                       LEFT JOIN users u ON ar.user_id = u.id
                       LEFT JOIN projects pr ON u.project_id = pr.id";
    $conditions_tarde_proy = [];
    $params_tarde_proy = [];
    if (!empty($start_date)) {
        $conditions_tarde_proy[] = "ar.date >= :start_date";
        $params_tarde_proy[':start_date'] = $start_date;
    }
    if (!empty($end_date)) {
        $conditions_tarde_proy[] = "ar.date <= :end_date";
        $params_tarde_proy[':end_date'] = $end_date;
    }
    if ($conditions_tarde_proy) $sql_tarde_proy .= " WHERE " . implode(" AND ", $conditions_tarde_proy);
    $sql_tarde_proy .= " GROUP BY pr.id";
    $stmt_tarde_proy = $pdo->prepare($sql_tarde_proy);
    foreach ($params_tarde_proy as $k => $v) {
        $stmt_tarde_proy->bindValue($k, $v);
    }
    $stmt_tarde_proy->execute();
    $data_tarde_proy = $stmt_tarde_proy->fetchAll(PDO::FETCH_ASSOC);

    $labels_proy = [];
    $porc_llegadas_tarde = [];
    foreach ($data_tarde_proy as $row) {
        $labels_proy[] = $row['proyecto'] ?: 'Sin Proyecto';
        $total = intval($row['total_registros']);
        $tarde = intval($row['llegadas_tarde']);
        $porc = $total > 0 ? round(($tarde / $total) * 100, 2) : 0;
        $porc_llegadas_tarde[] = $porc;
    }

    // Total de horas por proyecto
    $sql_horas_proy = "SELECT pr.name AS proyecto, SUM(ar.total_hours) AS total_horas
                       FROM attendance_records ar
                       LEFT JOIN users u ON ar.user_id = u.id
                       LEFT JOIN projects pr ON u.project_id = pr.id";
    $conditions_horas_proy = [];
    $params_horas_proy = [];
    if (!empty($start_date)) {
        $conditions_horas_proy[] = "ar.date >= :start_date";
        $params_horas_proy[':start_date'] = $start_date;
    }
    if (!empty($end_date)) {
        $conditions_horas_proy[] = "ar.date <= :end_date";
        $params_horas_proy[':end_date'] = $end_date;
    }
    if ($conditions_horas_proy) $sql_horas_proy .= " WHERE " . implode(" AND ", $conditions_horas_proy);
    $sql_horas_proy .= " GROUP BY pr.id";
    $stmt_horas_proy = $pdo->prepare($sql_horas_proy);
    foreach ($params_horas_proy as $k => $v) {
        $stmt_horas_proy->bindValue($k, $v);
    }
    $stmt_horas_proy->execute();
    $data_horas_proy = $stmt_horas_proy->fetchAll(PDO::FETCH_ASSOC);

    $total_horas_proy = [];
    foreach ($data_horas_proy as $row) {
        $total_horas_proy[] = floatval($row['total_horas']);
    }

    // Total de horas extra por proyecto
    $sql_extras_proy = "SELECT pr.name AS proyecto, SUM(ar.extra_hours) AS total_extras
                        FROM attendance_records ar
                        LEFT JOIN users u ON ar.user_id = u.id
                        LEFT JOIN projects pr ON u.project_id = pr.id";
    $conditions_extras_proy = [];
    $params_extras_proy = [];
    if (!empty($start_date)) {
        $conditions_extras_proy[] = "ar.date >= :start_date";
        $params_extras_proy[':start_date'] = $start_date;
    }
    if (!empty($end_date)) {
        $conditions_extras_proy[] = "ar.date <= :end_date";
        $params_extras_proy[':end_date'] = $end_date;
    }
    if ($conditions_extras_proy) $sql_extras_proy .= " WHERE " . implode(" AND ", $conditions_extras_proy);
    $sql_extras_proy .= " GROUP BY pr.id";
    $stmt_extras_proy = $pdo->prepare($sql_extras_proy);
    foreach ($params_extras_proy as $k => $v) {
        $stmt_extras_proy->bindValue($k, $v);
    }
    $stmt_extras_proy->execute();
    $data_extras_proy = $stmt_extras_proy->fetchAll(PDO::FETCH_ASSOC);

    $total_extras_proy = [];
    foreach ($data_extras_proy as $row) {
        $total_extras_proy[] = floatval($row['total_extras']);
    }
} catch (PDOException $e) {
    die("Error de conexión o consulta: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gráficas de Asistencia</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
        .container { max-width: 900px; margin: 40px auto; background: #fff; border-radius: 10px; box-shadow: 0 0 15px rgba(0,0,0,0.1); padding: 30px; }
        h1 { text-align: center; }
        .volver { display: block; margin: 20px auto 0; text-align: center; }
        .volver a { background: #215ba0; color: #fff; padding: 10px 20px; border-radius: 5px; text-decoration: none; }
        .volver a:hover { background: #18417a; }
        .grafica { margin-bottom: 40px; }
    </style>
</head>
<?php include 'layout.php'; ?>
<body>
    <div class="container">
        <h1>Gráficas de Asistencia</h1>
        <form method="get" style="margin-bottom:30px; text-align:center;">
            <label>Desde: <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>"></label>
            <label>Hasta: <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>"></label>
            <label>Proyecto:
                <select name="project_id">
                    <option value="">Todos</option>
                    <?php foreach ($proyectos as $proy): ?>
                        <option value="<?= $proy['id'] ?>" <?= $project_id == $proy['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($proy['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit">Filtrar</button>
        </form>
        <div class="grafica">
            <h3>Horas Extras por Usuario</h3>
            <canvas id="graficaHorasExtras" height="100"></canvas>
        </div>
        <div class="grafica">
            <h3>Promedio de Horas Extras por Usuario</h3>
            <canvas id="graficaPromedioExtras" height="100"></canvas>
        </div>
        <div class="grafica">
            <h3>Horas Totales por Usuario</h3>
            <canvas id="graficaHorasTotales" height="100"></canvas>
        </div>
        <div class="grafica">
            <h3>Llegadas Tarde por Usuario</h3>
            <canvas id="graficaLlegadasTarde" height="100"></canvas>
        </div>
        <div class="grafica">
            <h3>Sesiones (Registros) por Usuario</h3>
            <canvas id="graficaSesiones" height="100"></canvas>
        </div>
        <div class="grafica">
            <h3>Registros de Asistencia por Día</h3>
            <canvas id="graficaDias" height="100"></canvas>
        </div>
        <div class="grafica">
            <h3>% Llegadas Tarde por Proyecto</h3>
            <canvas id="graficaPorcTardeProy" height="100"></canvas>
        </div>
        <div class="grafica">
            <h3>Total de Horas por Proyecto</h3>
            <canvas id="graficaHorasProy" height="100"></canvas>
        </div>
        <div class="grafica">
            <h3>Total de Horas Extras por Proyecto</h3>
            <canvas id="graficaExtrasProy" height="100"></canvas>
        </div>
        <div class="volver">
            <a href="review_history.php?start_date=<?=urlencode($start_date)?>&end_date=<?=urlencode($end_date)?>">Volver</a>
        </div>
    </div>
    <script>
        // Horas Extras
        new Chart(document.getElementById('graficaHorasExtras').getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Horas Extras',
                    data: <?= json_encode($extras) ?>,
                    backgroundColor: 'rgba(33, 91, 160, 0.7)'
                }]
            },
            options: { responsive: true, plugins: { legend: {display: false} } }
        });

        // Promedio de Horas Extras
        new Chart(document.getElementById('graficaPromedioExtras').getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Promedio Horas Extras',
                    data: <?= json_encode($promedio_extras) ?>,
                    backgroundColor: 'rgba(0, 123, 255, 0.7)'
                }]
            },
            options: { responsive: true, plugins: { legend: {display: false} } }
        });

        // Horas Totales
        new Chart(document.getElementById('graficaHorasTotales').getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Horas Totales',
                    data: <?= json_encode($totales) ?>,
                    backgroundColor: 'rgba(40, 167, 69, 0.7)'
                }]
            },
            options: { responsive: true, plugins: { legend: {display: false} } }
        });

        // Llegadas Tarde
        new Chart(document.getElementById('graficaLlegadasTarde').getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels_tarde) ?>,
                datasets: [{
                    label: 'Llegadas Tarde',
                    data: <?= json_encode($llegadas_tarde) ?>,
                    backgroundColor: 'rgba(220, 53, 69, 0.7)'
                }]
            },
            options: { responsive: true, plugins: { legend: {display: false} } }
        });

        // Sesiones
        new Chart(document.getElementById('graficaSesiones').getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Sesiones',
                    data: <?= json_encode($sesiones) ?>,
                    backgroundColor: 'rgba(255, 193, 7, 0.7)'
                }]
            },
            options: { responsive: true, plugins: { legend: {display: false} } }
        });

        // Registros por Día
        new Chart(document.getElementById('graficaDias').getContext('2d'), {
            type: 'line',
            data: {
                labels: <?= json_encode($labels_dias) ?>,
                datasets: [{
                    label: 'Registros por Día',
                    data: <?= json_encode($registros_dias) ?>,
                    backgroundColor: 'rgba(40, 167, 69, 0.2)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    fill: true,
                    tension: 0.2
                }]
            },
            options: { responsive: true, plugins: { legend: {display: false} } }
        });

        // % Llegadas Tarde por Proyecto
        new Chart(document.getElementById('graficaPorcTardeProy').getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels_proy) ?>,
                datasets: [{
                    label: '% Llegadas Tarde',
                    data: <?= json_encode($porc_llegadas_tarde) ?>,
                    backgroundColor: 'rgba(220, 53, 69, 0.7)'
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: {display: false} },
                scales: { y: { beginAtZero: true, max: 100 } }
            }
        });

        // Total de Horas por Proyecto
        new Chart(document.getElementById('graficaHorasProy').getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels_proy) ?>,
                datasets: [{
                    label: 'Total Horas',
                    data: <?= json_encode($total_horas_proy) ?>,
                    backgroundColor: 'rgba(40, 167, 69, 0.7)'
                }]
            },
            options: { responsive: true, plugins: { legend: {display: false} } }
        });

        // Total de Horas Extras por Proyecto
        new Chart(document.getElementById('graficaExtrasProy').getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels_proy) ?>,
                datasets: [{
                    label: 'Total Horas Extras',
                    data: <?= json_encode($total_extras_proy) ?>,
                    backgroundColor: 'rgba(33, 91, 160, 0.7)'
                }]
            },
            options: { responsive: true, plugins: { legend: {display: false} } }
        });
    </script>
</body>
</html>
