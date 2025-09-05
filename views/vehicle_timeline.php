<?php
session_start();
include_once __DIR__ . '/../includes/conection.php';

$vehicle_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($vehicle_id <= 0) {
    // Mostrar lista de vehículos
    $vehicles = [];
    $res = $conn->query("SELECT id, placa, marca, modelo, foto_vehiculo FROM vehicle ORDER BY id DESC");
    while ($row = $res->fetch_assoc()) {
        $vehicles[] = $row;
    }
    include 'layout.php';
    echo '<div style="max-width:600px;margin:3em auto;padding:2em;background:#fff;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.08);">';
    echo '<h2 style="color:#215ba0;margin-bottom:1em;text-align:center;">Selecciona un vehículo</h2>';
    if (count($vehicles) === 0) {
        echo '<div style="color:#888;text-align:center;">No hay vehículos registrados.</div>';
    } else {
        echo '<ul style="list-style:none;padding:0;">';
        foreach ($vehicles as $v) {
            $desc = htmlspecialchars(($v['placa'] ? $v['placa'] : 'Sin placa') . ' - ' . $v['marca'] . ' ' . $v['modelo']);
            echo '<li style="margin-bottom:1.5em;text-align:center;">';
            if (!empty($v['foto_vehiculo']) && file_exists(__DIR__ . '/../' . $v['foto_vehiculo'])) {
                $foto_url = '../' . $v['foto_vehiculo'];
                echo '<div style="margin-bottom:0.5em;"><img src="' . $foto_url . '" alt="Foto vehículo" style="max-width:120px;max-height:80px;border-radius:6px;box-shadow:0 1px 6px rgba(0,0,0,0.10);"></div>';
            }
            echo '<a href="?id=' . $v['id'] . '" style="color:#2176ae;font-size:1.1em;text-decoration:underline;">' . $desc . '</a>';
            echo '</li>';
        }
        echo '</ul>';
    }
    echo '</div>';
    exit;
}

// Obtener todas las actas del vehículo ordenadas por fecha de entrega
$stmt = $conn->prepare("SELECT vr.id, vr.created_at, vr.return_date, 
    COALESCE(u.first_name, vr.return_first_name) AS first_name, 
    COALESCE(u.last_name, vr.return_last_name) AS last_name, 
    u.document
    FROM vehicle_record vr
    LEFT JOIN users u ON vr.user_id = u.id
    WHERE vr.vehicle_id = ?
    ORDER BY vr.created_at ASC");
$stmt->bind_param("i", $vehicle_id);
$stmt->execute();
$res = $stmt->get_result();
$timeline = [];
while ($row = $res->fetch_assoc()) {
    $timeline[] = $row;
}
$stmt->close();

// Obtener el estado actual del vehículo (quién lo tiene)
$current_holder = null;
foreach (array_reverse($timeline) as $acta) {
    if (empty($acta['return_date'])) {
        $current_holder = $acta;
        break;
    }
}

// Guardar novedad si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['novedad']) && $vehicle_id > 0) {
    $note = trim($_POST['novedad']);
    if ($note !== '') {
        $user = $_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name'];
        $stmt = $conn->prepare("INSERT INTO vehicle_note (vehicle_id, note, user, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iss", $vehicle_id, $note, $user);
        $stmt->execute();
        $stmt->close();
        header("Location: vehicle_timeline.php?id=$vehicle_id");
        exit;
    }
}

// Consultar novedades del vehículo
$novedades = [];
if ($vehicle_id > 0) {
    $res_nov = $conn->query("SELECT note, user, created_at FROM vehicle_note WHERE vehicle_id = $vehicle_id ORDER BY created_at DESC");
    while ($row = $res_nov->fetch_assoc()) {
        $novedades[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Línea de tiempo del vehículo</title>
    <link rel="stylesheet" href="../assets/css/acta_view.css">
    <style>
        .timeline-container {
            max-width: 700px;
            margin: 2.5rem auto;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.09);
            padding: 2.5rem 2rem;
        }
        .timeline-event {
            border-left: 4px solid #2176ae;
            margin-left: 1.5em;
            padding-left: 1.5em;
            margin-bottom: 2em;
            position: relative;
        }
        .timeline-event:before {
            content: '';
            position: absolute;
            left: -13px;
            top: 0.5em;
            width: 16px;
            height: 16px;
            background: #fff;
            border: 3px solid #2176ae;
            border-radius: 50%;
            z-index: 1;
        }
        .timeline-event .timeline-title {
            font-weight: bold;
            color: #215ba0;
            margin-bottom: 0.2em;
        }
        .timeline-event .timeline-date {
            color: #888;
            font-size: 0.98em;
            margin-bottom: 0.5em;
        }
        .timeline-event .timeline-user {
            font-size: 1.05em;
            margin-bottom: 0.3em;
        }
        .timeline-event .timeline-period {
            color: #2176ae;
            font-size: 0.97em;
            margin-bottom: 0.2em;
        }
        .timeline-current {
            background: #eaf4ff;
            border-radius: 8px;
            padding: 1em 1.5em;
            margin-bottom: 2em;
            border: 1px solid #b8d6f6;
        }
    </style>
</head>
<body>
<?php include 'layout.php'; ?>
<div class="timeline-container">
    <h2 style="text-align:center;margin-bottom:1.5em;">Línea de tiempo del vehículo</h2>
    <?php if ($current_holder): ?>
        <div class="timeline-current">
            <strong>Actualmente asignado a:</strong>
            <?= htmlspecialchars($current_holder['first_name'] . ' ' . $current_holder['last_name']) ?>
            (<?= htmlspecialchars($current_holder['document']) ?>)
            <br>
            <span style="color:#215ba0;">Desde: <?= date('d/m/Y', strtotime($current_holder['created_at'])) ?></span>
        </div>
    <?php else: ?>
        <div class="timeline-current" style="background:#f8fafc;border:1px solid #eaeaea;">
            <strong>El vehículo no está actualmente asignado.</strong>
        </div>
    <?php endif; ?>

    <?php if (count($timeline) === 0): ?>
        <div style="text-align:center;color:#888;">No hay registros de asignación para este vehículo.</div>
    <?php else: ?>
        <?php foreach ($timeline as $item): ?>
            <div class="timeline-event">
                <div class="timeline-title">
                    <?= htmlspecialchars($item['first_name'] . ' ' . $item['last_name']) ?>
                    <?php if (!empty($item['document'])): ?>
                        (<?= htmlspecialchars($item['document']) ?>)
                    <?php endif; ?>
                </div>
                <div class="timeline-date">
                    Recibió: <?= date('d/m/Y', strtotime($item['created_at'])) ?>
                    <?php if ($item['return_date']): ?>
                        <br>Entregó: <?= date('d/m/Y', strtotime($item['return_date'])) ?>
                    <?php endif; ?>
                </div>
                <?php if ($item['return_date']): ?>
                    <?php
                    $start = new DateTime($item['created_at']);
                    $end = new DateTime($item['return_date']);
                    $interval = $start->diff($end);
                    ?>
                    <div class="timeline-period">
                        Tiempo con el vehículo:
                        <?= $interval->days ?> día<?= $interval->days == 1 ? '' : 's' ?>
                        <?php if ($interval->y > 0 || $interval->m > 0): ?>
                            (<?= $interval->y ? $interval->y . ' año' . ($interval->y > 1 ? 's' : '') . ' ' : '' ?>
                            <?= $interval->m ? $interval->m . ' mes' . ($interval->m > 1 ? 'es' : '') : '' ?>)
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="timeline-period">
                        <span style="color:#e67e22;">Aún tiene el vehículo</span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div style="margin:2em 0 2em 0;">
        <h3 style="color:#215ba0;">Anexar novedad / observación</h3>
        <form method="post" style="margin-bottom:1.5em;">
            <textarea name="novedad" rows="3" style="width:100%;border-radius:6px;border:1px solid #ccc;padding:0.7em;" placeholder="Describe la novedad, inconveniente, chequeo, observación, etc." required></textarea>
            <button type="submit" style="margin-top:0.7em;background:#2176ae;color:#fff;padding:0.5em 1.5em;border:none;border-radius:5px;cursor:pointer;">Guardar novedad</button>
        </form>
        <?php if (count($novedades) > 0): ?>
            <div style="margin-top:1em;">
                <h4 style="color:#2176ae;">Historial de novedades</h4>
                <?php
                // Agrupar novedades por responsable
                $grouped = [];
                foreach ($novedades as $nov) {
                    $responsable = 'No asignado';
                    foreach (array_reverse($timeline) as $item) {
                        $start = strtotime($item['created_at']);
                        $end = $item['return_date'] ? strtotime($item['return_date']) : PHP_INT_MAX;
                        $fecha_nov = strtotime($nov['created_at']);
                        if ($fecha_nov >= $start && $fecha_nov <= $end) {
                            $responsable = htmlspecialchars($item['first_name'] . ' ' . $item['last_name']);
                            break;
                        }
                    }
                    $grouped[$responsable][] = $nov;
                }
                ?>
                <ul style="list-style:none;padding:0;">
                    <?php foreach ($grouped as $responsable => $novs): ?>
                        <li style="margin-bottom:1em;">
                            <div class="responsable-toggle" style="cursor:pointer;font-weight:bold;color:#215ba0;padding:0.5em 0;">
                                <?= $responsable ?>
                                <span style="font-size:1.1em;margin-left:0.5em;">&#9660;</span>
                            </div>
                            <ul class="novedades-list" style="display:none;list-style:none;padding:0;margin:0;">
                                <?php foreach ($novs as $nov): ?>
                                    <li style="background:#f4f8fb;border-radius:6px;padding:0.7em 1em;margin-bottom:0.7em;">
                                        <div style="font-size:0.97em;"><?= htmlspecialchars($nov['note']) ?></div>
                                        <div style="color:#888;font-size:0.93em;margin-top:0.2em;">
                                            <?= htmlspecialchars($nov['user']) ?> - <?= date('d/m/Y H:i', strtotime($nov['created_at'])) ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <script>
                    document.querySelectorAll('.responsable-toggle').forEach(function(toggle) {
                        toggle.addEventListener('click', function() {
                            var list = this.nextElementSibling;
                            if (list.style.display === 'none' || list.style.display === '') {
                                list.style.display = 'block';
                                this.querySelector('span').innerHTML = '&#9650;';
                            } else {
                                list.style.display = 'none';
                                this.querySelector('span').innerHTML = '&#9660;';
                            }
                        });
                    });
                </script>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>