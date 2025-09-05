<?php
session_start();
include_once __DIR__ . '/../includes/conection.php';

// Obtener todos los equipos con fecha de compra
$equipos = [];
$res = $conn->query("SELECT id, internal_number, purchase_date FROM devices ORDER BY internal_number ASC");
while ($row = $res->fetch_assoc()) {
    $equipos[$row['id']] = [
        'internal_number' => $row['internal_number'],
        'purchase_date' => $row['purchase_date']
    ];
}

// Obtener fechas de mantenimiento por equipo
$mantenimientos = [];
$res = $conn->query("SELECT device_id, date FROM maintenance ORDER BY date ASC");
while ($row = $res->fetch_assoc()) {
    $mantenimientos[$row['device_id']][] = $row['date'];
}

// Año actual
$anio = date('Y');
// Mostrar años 2025 y 2026
$anios = [2025, 2026];
$meses = [
    1 => 'Ene',
    2 => 'Feb',
    3 => 'Mar',
    4 => 'Abr',
    5 => 'May',
    6 => 'Jun',
    7 => 'Jul',
    8 => 'Ago',
    9 => 'Sep',
    10 => 'Oct',
    11 => 'Nov',
    12 => 'Dic'
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Calendario de Mantenimientos</title>
    <link rel="stylesheet" href="../assets/css/acta_view.css">
    <style>
        .calendar-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2em;
        }

        .calendar-table th,
        .calendar-table td {
            border: 1px solid #dbe4f3;
            padding: 8px;
            text-align: center;
        }

        .calendar-table th {
            background: #ecf2fc;
            color: #215ba0;
        }

        .mant-done {
            background: #b6e6b6 !important;
            color: #215ba0;
            font-weight: bold;
            border-radius: 4px;
        }

        .mant-none {
            background: #f7f7f7;
            color: #bbb;
        }

        .mant-late {
            background: #ffd6d6 !important;
            color: #b30000;
            font-weight: bold;
            border-radius: 4px;
        }

        .legend {
            margin-bottom: 1em;
            padding: 1em;
            background: #f8fafc;
            border-radius: 10px;
            border: 1px solid #dbe4f3;
        }

        .legend span {
            margin-right: 1.5em;
        }

        .mant-done,
        .mant-late,
        .mant-recommended {
            cursor: pointer;
        }

        .chart-container {
            max-width: 900px;
            margin: 2em auto 1em auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            padding: 2em;
        }

        .btn-back {
            display: inline-block;
            padding: 5px 10px;
            background-color: #215ba0;
            color: white;
            text-decoration: none;
            border: none;
            margin-bottom: 5px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s ease, transform 0.2s ease;
            cursor: pointer;
        }

        .btn-back:hover {
            background-color: #2980b9;
            transform: scale(1.05);
        }

        .header {
            margin-top: 50px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1em;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function goToHojaDeVida(deviceId) {
            if (deviceId) {
                window.location.href = 'device_profile.php?id=' + deviceId;
            }
        }
    </script>
</head>

<body>
    <?php include 'layout.php'; ?>

    <div class="acta-container">
        <div class="header">
              <a href="ti.php" class="btn-back">Volver</a>
            <h2>Calendario de Mantenimientos de Equipos</h2>

          
        </div>


        <div class="legend">

            <span><span style="color:green;font-weight:bold;">● Verde</span>: Ejecutado</span>
            <span><span style="color:#b30000;font-weight:bold;">● Rojo</span>: Atrasado</span>
            <!-- <span><span style="color:#bbb;">- Gris</span>: Sin mantenimiento</span> -->
            <span><span style="color:#215ba0;font-weight:bold;">■ Azul</span>:Próximo mantenimiento</span>
        </div>
        <?php
        // Calcular datos para la gráfica
        $grafica_data = [];
        $grafica_equipos = [];
        $grafica_tipos = [];
        foreach ($anios as $anio_graf) {
            $grafica_data[$anio_graf] = array_fill(1, 12, 0);
            $grafica_equipos[$anio_graf] = array_fill(1, 12, []);
            $grafica_tipos[$anio_graf] = array_fill(1, 12, ['ejecutado' => 0, 'atrasado' => 0, 'recomendado' => 0]);
        }
        foreach ($equipos as $id => $eq) {
            $mes_compra = 0;
            $anio_compra = 0;
            if ($eq['purchase_date'] && $eq['purchase_date'] !== '0000-00-00') {
                $mes_compra = (int) date('n', strtotime($eq['purchase_date']));
                $anio_compra = (int) date('Y', strtotime($eq['purchase_date']));
            }
            foreach ($anios as $anio_graf) {
                // Mantenimientos realizados en el año mostrado
                $mant_meses = [];
                if (!empty($mantenimientos[$id])) {
                    foreach ($mantenimientos[$id] as $fecha) {
                        $f = strtotime($fecha);
                        if (date('Y', $f) == $anio_graf) {
                            $mant_meses[(int) date('n', $f)] = true;
                        }
                    }
                }
                foreach ($meses as $mes_num => $nombre) {
                    // Ejecutado
                    if (isset($mant_meses[$mes_num])) {
                        $grafica_data[$anio_graf][$mes_num]++;
                        $grafica_equipos[$anio_graf][$mes_num][] = $eq['internal_number'];
                        $grafica_tipos[$anio_graf][$mes_num]['ejecutado']++;
                    }
                    // Atrasado
                    elseif (
                        $mes_compra === $mes_num &&
                        $anio_graf <= date('Y') &&
                        (($anio_graf < date('Y')) || ($anio_graf == date('Y') && date('n') > $mes_num)) &&
                        empty($mant_meses[$mes_num]) &&
                        $eq['purchase_date'] !== '0000-00-00'
                    ) {
                        $grafica_tipos[$anio_graf][$mes_num]['atrasado']++;
                    }
                    // Recomendado
                    elseif ($mes_compra === $mes_num && $eq['purchase_date'] !== '0000-00-00') {
                        $grafica_tipos[$anio_graf][$mes_num]['recomendado']++;
                    }
                }
            }
        }
        ?>
        <script>
            // Evitar múltiples inicializaciones de Chart.js
            let chartMantenimientosInstance = null;
            document.addEventListener('DOMContentLoaded', function () {
                var ctx = document.getElementById('chartMantenimientos').getContext('2d');
                if (chartMantenimientosInstance) {
                    chartMantenimientosInstance.destroy();
                }
                chartMantenimientosInstance = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: [<?= implode(',', array_map(fn($m) => "'$m'", $meses)) ?>],
                        datasets: [
                            <?php foreach ($anios as $i => $anio_graf): ?>
                                    {
                                    label: '<?= $anio_graf ?> - Ejecutado',
                                    data: [<?= implode(',', $grafica_data[$anio_graf]) ?>],
                                    backgroundColor: '<?= $i === 0 ? "#215ba0" : "#b6e6b6" ?>',
                                    borderColor: '<?= $i === 0 ? "#215ba0" : "#217a3c" ?>',
                                    borderWidth: 1,
                                    stack: 'ejecutado'
                                },
                                {
                                    label: '<?= $anio_graf ?> - Atrasado',
                                    data: [<?= implode(',', array_map(fn($m) => $m['atrasado'], $grafica_tipos[$anio_graf])) ?>],
                                    backgroundColor: '#ffd6d6',
                                    borderColor: '#b30000',
                                    borderWidth: 1,
                                    stack: 'atrasado'
                                },
                                {
                                    label: '<?= $anio_graf ?> - Recomendado',
                                    data: [<?= implode(',', array_map(fn($m) => $m['recomendado'], $grafica_tipos[$anio_graf])) ?>],
                                    backgroundColor: '#215ba0',
                                    borderColor: '#215ba0',
                                    borderWidth: 1,
                                    stack: 'recomendado'
                                }
                                    <?= $i < count($anios) - 1 ? ',' : '' ?>
                            <?php endforeach; ?>
                        ]
                    },
                    options: {
                        responsive: false,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'top' },
                            title: { display: true, text: 'Mantenimientos por mes y tipo (ejecutado, atrasado, recomendado)' },
                            tooltip: {
                                callbacks: {
                                    afterBody: function (context) {
                                        // Mostrar equipos en tooltip solo para ejecutado
                                        var idx = context[0].dataIndex;
                                        var dsLabel = context[0].dataset.label;
                                        <?php foreach ($anios as $i => $anio_graf): ?>
                                            if (dsLabel === '<?= $anio_graf ?> - Ejecutado') {
                                                var equipos = <?= json_encode($grafica_equipos[$anio_graf]) ?>;
                                                var mes = idx + 1;
                                                if (equipos[mes] && equipos[mes].length > 0) {
                                                    return 'Equipos: ' + equipos[mes].join(', ');
                                                }
                                            }
                                        <?php endforeach; ?>
                                        return '';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: { beginAtZero: true, stepSize: 1 }
                        }
                    }
                });
            });
        </script>
        <?php foreach ($anios as $anio): ?>
            <h3 style="text-align:center;margin-top:2em;">Año <?= $anio ?></h3>
            <table class="calendar-table">
                <thead>
                    <tr>
                        <th>Número Interno</th>
                        <?php foreach ($meses as $num => $nombre): ?>
                            <th><?= $nombre ?></th>
                        <?php endforeach; ?>
                        <th>Tipo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipos as $id => $eq): ?>
                        <tr>
                            <td>
                                <a href="device_profile.php?id=<?= $id ?>"
                                    style="color:#215ba0;text-decoration:underline;cursor:pointer;"
                                    title="Ver hoja de vida del equipo">
                                    <?= htmlspecialchars($eq['internal_number']) ?>
                                </a>
                            </td>
                            <?php
                            $mes_compra = 0;
                            $anio_compra = 0;
                            if ($eq['purchase_date'] && $eq['purchase_date'] !== '0000-00-00') {
                                $mes_compra = (int) date('n', strtotime($eq['purchase_date']));
                                $anio_compra = (int) date('Y', strtotime($eq['purchase_date']));
                            }
                            // Mantenimientos realizados en el año mostrado
                            $mant_meses = [];
                            $mant_fechas = [];
                            // Determinar tipos de mantenimiento en el año
                            $tipos_mant = [];
                            if (!empty($mantenimientos[$id])) {
                                // Buscar tipos de mantenimiento para el año mostrado
                                $res_tipo = $conn->query("SELECT external FROM maintenance WHERE device_id = $id AND YEAR(date) = $anio");
                                while ($row_tipo = $res_tipo ? $res_tipo->fetch_assoc() : []) {
                                    if ($row_tipo) {
                                        $tipo = ($row_tipo['external'] && trim($row_tipo['external']) !== '') ? 'Externo' : 'Interno';
                                        $tipos_mant[$tipo] = true;
                                    }
                                }
                                foreach ($mantenimientos[$id] as $fecha) {
                                    $f = strtotime($fecha);
                                    if (date('Y', $f) == $anio) {
                                        $mant_meses[(int) date('n', $f)] = true;
                                    }
                                    $mant_fechas[] = $f;
                                }
                            }
                            // Determinar texto para la columna tipo
                            if (count($tipos_mant) > 1) {
                                $tipo_mant = 'Ambos';
                            } elseif (isset($tipos_mant['Externo'])) {
                                $tipo_mant = 'Externo';
                            } elseif (isset($tipos_mant['Interno'])) {
                                $tipo_mant = 'Interno';
                            } else {
                                $tipo_mant = '-';
                            }
                            foreach ($meses as $mes_num => $nombre) {
                                $clase = 'mant-none';
                                $contenido = '-';
                                $onclick = '';

                                $es_mes_compra = ($mes_compra === $mes_num && $eq['purchase_date'] !== '0000-00-00');
                                $mantenimiento_esperado = $es_mes_compra && $anio >= $anio_compra && $anio <= date('Y');

                                // Buscar si hay mantenimiento exactamente en el mes/año esperado
                                $mantenimiento_en_tiempo = isset($mant_meses[$mes_num]);
                                // Buscar si hay mantenimiento atrasado (en año posterior o en el mismo año pero después del mes esperado)
                                $mantenimiento_atrasado = false;
                                if ($mantenimiento_esperado && !$mantenimiento_en_tiempo && !empty($mant_fechas)) {
                                    foreach ($mant_fechas as $f) {
                                        $mant_anio = (int) date('Y', $f);
                                        $mant_mes = (int) date('n', $f);
                                        if (
                                            ($mant_anio > $anio) ||
                                            ($mant_anio == $anio && $mant_mes > $mes_num)
                                        ) {
                                            $mantenimiento_atrasado = true;
                                            break;
                                        }
                                    }
                                }

                                if ($mantenimiento_en_tiempo) {
                                    // Solo verde si se hizo exactamente en el mes/año esperado
                                    $clase = 'mant-done';
                                    $contenido = '<span style="color:green;font-size:1.2em;">●</span>';
                                    $onclick = "onclick=\"goToHojaDeVida($id)\" title='Ver hoja de vida del equipo'";
                                } elseif ($mantenimiento_esperado && $mantenimiento_atrasado) {
                                    // Rojo si se hizo después del mes/año esperado
                                    $clase = 'mant-late';
                                    $contenido = '<span style="color:#b30000;font-size:1.2em;">●</span>';
                                    $onclick = '';
                                } elseif (
                                    $mantenimiento_esperado &&
                                    !$mantenimiento_en_tiempo &&
                                    !$mantenimiento_atrasado &&
                                    (
                                        ($anio < date('Y')) ||
                                        ($anio == date('Y') && date('n') > $mes_num)
                                    )
                                ) {
                                    // Rojo si ya pasó el mes/año y no se hizo mantenimiento ni atrasado
                                    $clase = 'mant-late';
                                    $contenido = '<span style="color:#b30000;font-size:1.2em;">●</span>';
                                    $onclick = '';
                                } elseif ($es_mes_compra) {
                                    // Azul si es el mes recomendado y no hay mantenimiento ni atraso
                                    $clase = '';
                                    $contenido = '<span style="color:#215ba0;font-weight:bold;">■</span>';
                                    $onclick = '';
                                }
                                echo '<td class="' . $clase . '" ' . $onclick . '>' . $contenido . '</td>';
                            }
                            ?>
                            <td><?= $tipo_mant ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endforeach; ?>

        <!-- Nueva gráfica: Equipos con y sin mantenimiento -->
        <?php
        // Calcular equipos con y sin mantenimiento para los años seleccionados
        $equipos_con_mant = 0;
        $equipos_sin_mant = 0;
        $equipos_con_mant_2025 = 0;
        $equipos_sin_mant_2025 = 0;
        $equipos_con_mant_2026 = 0;
        $equipos_sin_mant_2026 = 0;

        // Calcular atrasos por año
        $atrasos_por_anio = [2025 => 0, 2026 => 0];

        // Calcular mantenimientos internos y externos por año
        $mant_interno = [2025 => 0, 2026 => 0];
        $mant_externo = [2025 => 0, 2026 => 0];

        // Calcular cantidad de equipos total y con/sin mantenimiento por año
        $total_equipos = count($equipos);
        $equipos_con_mant_anio = [2025 => 0, 2026 => 0];
        $equipos_sin_mant_anio = [2025 => 0, 2026 => 0];

        foreach ($equipos as $id => $eq) {
            $tiene_mant_2025 = false;
            $tiene_mant_2026 = false;
            // Para atrasos
            $tiene_atraso_2025 = false;
            $tiene_atraso_2026 = false;

            // Obtener datos de compra
            $mes_compra = 0;
            $anio_compra = 0;
            if ($eq['purchase_date'] && $eq['purchase_date'] !== '0000-00-00') {
                $mes_compra = (int) date('n', strtotime($eq['purchase_date']));
                $anio_compra = (int) date('Y', strtotime($eq['purchase_date']));
            }

            // Obtener todas las fechas de mantenimiento y tipo
            $mant_fechas = [];
            if (!empty($mantenimientos[$id])) {
                // Obtener tipo de mantenimiento por cada registro
                $res_tipo = $conn->query("SELECT date, external FROM maintenance WHERE device_id = $id");
                $tipos_por_fecha = [];
                while ($row_tipo = $res_tipo->fetch_assoc()) {
                    $tipos_por_fecha[$row_tipo['date']] = $row_tipo['external'];
                }

                foreach ($mantenimientos[$id] as $fecha) {
                    $f = strtotime($fecha);
                    if (date('Y', $f) == 2025)
                        $tiene_mant_2025 = true;
                    if (date('Y', $f) == 2026)
                        $tiene_mant_2026 = true;
                    $mant_fechas[] = $f;

                    // Contar internos/externos por año
                    $anio_mant = (int) date('Y', $f);
                    $tipo_ext = isset($tipos_por_fecha[$fecha]) ? $tipos_por_fecha[$fecha] : null;
                    if ($anio_mant == 2025 || $anio_mant == 2026) {
                        if ($tipo_ext && trim($tipo_ext) !== '') {
                            $mant_externo[$anio_mant]++;
                        } else {
                            $mant_interno[$anio_mant]++;
                        }
                    }
                }
            }

            // Analizar atrasos para cada año
            foreach ([2025, 2026] as $anio_eval) {
                if ($mes_compra > 0 && $anio_compra <= $anio_eval) {
                    // Buscar si hay mantenimiento en el mes esperado
                    $mant_en_tiempo = false;
                    $mant_atrasado = false;
                    foreach ($mant_fechas as $f) {
                        $mant_anio = (int) date('Y', $f);
                        $mant_mes = (int) date('n', $f);
                        if ($mant_anio == $anio_eval && $mant_mes == $mes_compra) {
                            $mant_en_tiempo = true;
                            break;
                        }
                    }
                    if (!$mant_en_tiempo) {
                        // Buscar si hay mantenimiento atrasado (en año posterior o en el mismo año pero después del mes esperado)
                        foreach ($mant_fechas as $f) {
                            $mant_anio = (int) date('Y', $f);
                            $mant_mes = (int) date('n', $f);
                            if (
                                ($mant_anio > $anio_eval) ||
                                ($mant_anio == $anio_eval && $mant_mes > $mes_compra)
                            ) {
                                $mant_atrasado = true;
                                break;
                            }
                        }
                    }
                    if (
                        !$mant_en_tiempo && ($mant_atrasado ||
                            ($anio_eval < date('Y') || ($anio_eval == date('Y') && date('n') > $mes_compra))
                        )
                    ) {
                        if ($anio_eval == 2025)
                            $tiene_atraso_2025 = true;
                        if ($anio_eval == 2026)
                            $tiene_atraso_2026 = true;
                    }
                }
            }

            if ($tiene_mant_2025)
                $equipos_con_mant_2025++;
            else
                $equipos_sin_mant_2025++;
            if ($tiene_mant_2026)
                $equipos_con_mant_2026++;
            else
                $equipos_sin_mant_2026++;
            if ($tiene_atraso_2025)
                $atrasos_por_anio[2025]++;
            if ($tiene_atraso_2026)
                $atrasos_por_anio[2026]++;
            // Al final de cada foreach equipo:
            // Equipos con/sin mantenimiento por año
            $equipos_con_mant_anio[2025] += $tiene_mant_2025 ? 1 : 0;
            $equipos_sin_mant_anio[2025] += !$tiene_mant_2025 ? 1 : 0;
            $equipos_con_mant_anio[2026] += $tiene_mant_2026 ? 1 : 0;
            $equipos_sin_mant_anio[2026] += !$tiene_mant_2026 ? 1 : 0;
        }
        ?>
        <div class="chart-container" style="max-width:700px;">
            <canvas id="chartMantenimientos" width="600" height="220"></canvas>
        </div>
        <div class="chart-container" style="max-width:500px;">
            <canvas id="chartEquiposMant" width="400" height="220"></canvas>
        </div>
        <div class="chart-container" style="max-width:400px;">
            <canvas id="chartAtrasos" width="350" height="220"></canvas>
        </div>
        <div class="chart-container" style="max-width:400px;">
            <canvas id="chartTipoMant" width="350" height="220"></canvas>
        </div>
        <div class="chart-container" style="max-width:400px;">
            <canvas id="chartEquiposResumen" width="350" height="220"></canvas>
        </div>

        <!-- Botón para descargar toda la data -->
        <!-- <div style="text-align:right; margin: 1em 0;">
            <form method="post" action="download_maintenance_data.php">
                <button type="submit" style="padding:0.7em 2em;font-size:1em;background:#215ba0;color:#fff;border:none;border-radius:5px;cursor:pointer;">
                    Descargar toda la data
                </button>
            </form>
        </div> -->

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // ...existing Chart.js code for chartMantenimientos...

                // Gráfica de equipos con/sin mantenimiento
                var ctx2 = document.getElementById('chartEquiposMant').getContext('2d');
                new Chart(ctx2, {
                    type: 'bar',
                    data: {
                        labels: ['2025', '2026'],
                        datasets: [
                            {
                                label: 'Con mantenimiento',
                                data: [<?= $equipos_con_mant_2025 ?>, <?= $equipos_con_mant_2026 ?>],
                                backgroundColor: '#215ba0'
                            },
                            {
                                label: 'Sin mantenimiento',
                                data: [<?= $equipos_sin_mant_2025 ?>, <?= $equipos_sin_mant_2026 ?>],
                                backgroundColor: '#ffd6d6'
                            }
                        ]
                    },
                    options: {
                        responsive: false,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'top' },
                            title: { display: true, text: 'Equipos con y sin mantenimiento por año' },
                            tooltip: {
                                callbacks: {
                                    afterBody: function (context) {
                                        var idx = context[0].dataIndex;
                                        var dsLabel = context[0].dataset.label;
                                        // Relacionar números internos
                                        <?php
                                        // Prepara arrays de números internos para tooltip
                                        $equipos_con_mant_nums = [2025 => [], 2026 => []];
                                        $equipos_sin_mant_nums = [2025 => [], 2026 => []];
                                        foreach ($equipos as $id => $eq) {
                                            $has2025 = isset($mantenimientos[$id]) && array_filter($mantenimientos[$id], function ($fecha) {
                                                return date('Y', strtotime($fecha)) == 2025;
                                            });
                                            $has2026 = isset($mantenimientos[$id]) && array_filter($mantenimientos[$id], function ($fecha) {
                                                return date('Y', strtotime($fecha)) == 2026;
                                            });
                                            if ($has2025)
                                                $equipos_con_mant_nums[2025][] = $eq['internal_number'];
                                            else
                                                $equipos_sin_mant_nums[2025][] = $eq['internal_number'];
                                            if ($has2026)
                                                $equipos_con_mant_nums[2026][] = $eq['internal_number'];
                                            else
                                                $equipos_sin_mant_nums[2026][] = $eq['internal_number'];
                                        }
                                        ?>
                                        var equiposConMant = <?= json_encode([$equipos_con_mant_nums[2025], $equipos_con_mant_nums[2026]]) ?>;
                                        var equiposSinMant = <?= json_encode([$equipos_sin_mant_nums[2025], $equipos_sin_mant_nums[2026]]) ?>;
                                        if (dsLabel === 'Con mantenimiento') {
                                            if (equiposConMant[idx] && equiposConMant[idx].length > 0) {
                                                return 'Números internos: ' + equiposConMant[idx].join(', ');
                                            }
                                        }
                                        if (dsLabel === 'Sin mantenimiento') {
                                            if (equiposSinMant[idx] && equiposSinMant[idx].length > 0) {
                                                return 'Números internos: ' + equiposSinMant[idx].join(', ');
                                            }
                                        }
                                        return '';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: { beginAtZero: true, stepSize: 1 }
                        }
                    }
                });

                // Gráfica de cantidad de atrasos por año
                var ctx3 = document.getElementById('chartAtrasos').getContext('2d');
                new Chart(ctx3, {
                    type: 'bar',
                    data: {
                        labels: ['2025', '2026'],
                        datasets: [{
                            label: 'Cantidad de atrasos',
                            data: [<?= $atrasos_por_anio[2025] ?>, <?= $atrasos_por_anio[2026] ?>],
                            backgroundColor: '#b30000'
                        }]
                    },
                    options: {
                        responsive: false,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            title: { display: true, text: 'Cantidad de atrasos por año' }
                        },
                        scales: {
                            y: { beginAtZero: true, stepSize: 1 }
                        }
                    }
                });

                // Nueva gráfica: cantidad de mantenimientos internos y externos por año
                var ctx4 = document.getElementById('chartTipoMant').getContext('2d');
                new Chart(ctx4, {
                    type: 'bar',
                    data: {
                        labels: ['2025', '2026'],
                        datasets: [
                            {
                                label: 'Interno',
                                data: [<?= $mant_interno[2025] ?>, <?= $mant_interno[2026] ?>],
                                backgroundColor: '#215ba0'
                            },
                            {
                                label: 'Externo',
                                data: [<?= $mant_externo[2025] ?>, <?= $mant_externo[2026] ?>],
                                backgroundColor: '#f7b731'
                            }
                        ]
                    },
                    options: {
                        responsive: false,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'top' },
                            title: { display: true, text: 'Cantidad de mantenimientos internos y externos por año' }
                        },
                        scales: {
                            y: { beginAtZero: true, stepSize: 1 }
                        }
                    }
                });

                // Nueva gráfica: cantidad de equipos, con y sin mantenimiento por año
                var ctx5 = document.getElementById('chartEquiposResumen').getContext('2d');
                new Chart(ctx5, {
                    type: 'bar',
                    data: {
                        labels: ['2025', '2026'],
                        datasets: [
                            {
                                label: 'Total equipos',
                                data: [<?= $total_equipos ?>, <?= $total_equipos ?>],
                                backgroundColor: '#b6e6b6'
                            },
                            {
                                label: 'Con mantenimiento',
                                data: [<?= $equipos_con_mant_anio[2025] ?>, <?= $equipos_con_mant_anio[2026] ?>],
                                backgroundColor: '#215ba0'
                            },
                            {
                                label: 'Sin mantenimiento',
                                data: [<?= $equipos_sin_mant_anio[2025] ?>, <?= $equipos_sin_mant_anio[2026] ?>],
                                backgroundColor: '#ffd6d6'
                            }
                        ]
                    },
                    options: {
                        responsive: false,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'top' },
                            title: { display: true, text: 'Cantidad de equipos y su estado de mantenimiento por año' }
                        },
                        scales: {
                            y: { beginAtZero: true, stepSize: 1 }
                        }
                    }
                });
            });
        </script>
    </div>
</body>

</html>