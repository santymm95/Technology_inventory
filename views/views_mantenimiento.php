<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
include_once __DIR__ . '/../includes/conection.php';

// --- CSV Download Handler ---
if (isset($_GET['download']) && $_GET['download'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="equipos.csv"');
    $output = fopen('php://output', 'w');
    // Consulta todos los equipos con JOINs para obtener nombres descriptivos (ajustado a nombres reales de tablas)
    $sql_csv = "SELECT 
        d.id,
        d.internal_number,
        d.serial,
        m.name AS modelo,
        d.purchase_date,
        pt.name AS proveedor,
        p.name AS procesador,
        r.name AS ram,
        s.name AS almacenamiento,
        os.name AS sistema_operativo,
        st.name AS estado,
        gc.name AS grafica,
        b.name AS marca
    FROM devices d
    LEFT JOIN models m ON d.model_id = m.id
    LEFT JOIN provider_types pt ON d.provider_type_id = pt.id
    LEFT JOIN processors p ON d.processor_id = p.id
    LEFT JOIN rams r ON d.ram_id = r.id
    LEFT JOIN storages s ON d.storage_id = s.id
    LEFT JOIN operating_systems os ON d.os_id = os.id
    LEFT JOIN statuses st ON d.status_id = st.id
    LEFT JOIN graphics_cards gc ON d.graphics_card_id = gc.id
    LEFT JOIN brands b ON d.brand_id = b.id
    ORDER BY d.internal_number ASC";
    $res_csv = $conn->query($sql_csv);
    // Escribir BOM UTF-8 para que Excel reconozca acentos
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    // Encabezados claros
    fputcsv($output, [
        'ID',
        'Número Interno',
        'Serial',
        'Modelo',
        'Fecha de Compra',
        'Proveedor',
        'Procesador',
        'RAM',
        'Almacenamiento',
        'Sistema Operativo',
        'Estado',
        'Gráfica',
        'Marca'
    ]);
    while ($row = $res_csv->fetch_assoc()) {
        // Formatear fecha si es necesario
        if (!empty($row['purchase_date'])) {
            $row['purchase_date'] = date('Y-m-d', strtotime($row['purchase_date']));
        }
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Barra de búsqueda
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Paginación
$por_pagina = 8; // 4 columnas x 2 filas
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina - 1) * $por_pagina;

// Total de equipos (con búsqueda)
$where = '';
if ($search !== '') {
    $search_esc = $conn->real_escape_string($search);
    $where = "WHERE 
        d.internal_number LIKE '%$search_esc%' 
        OR d.serial LIKE '%$search_esc%' 
        OR m.name LIKE '%$search_esc%'
        OR EXISTS (
            SELECT 1 FROM actas a
            LEFT JOIN users u ON a.user_id = u.id
            WHERE a.device_id = d.id
            AND (
                CONCAT_WS(' ', u.first_name, u.last_name) LIKE '%$search_esc%'
                OR u.first_name LIKE '%$search_esc%'
                OR u.last_name LIKE '%$search_esc%'
            )
        )";
}

$total_res = $conn->query("SELECT COUNT(*) AS total FROM devices d LEFT JOIN models m ON d.model_id = m.id $where");
$total_row = $total_res->fetch_assoc();
$total_equipos = $total_row['total'];
$total_paginas = ceil($total_equipos / $por_pagina);

// Obtener lista de dispositivos paginados (con búsqueda)
// Traer el nombre del usuario del último acta (por fecha_entrega más reciente)
// Traer el tipo de mantenimiento más reciente (por fecha)
$sql = "SELECT 
            d.id, 
            d.internal_number, 
            d.serial, 
            m.name AS modelo,
            (
                SELECT CONCAT_WS(' ', u.first_name, u.last_name)
                FROM actas a
                LEFT JOIN users u ON a.user_id = u.id
                WHERE a.device_id = d.id
                ORDER BY a.fecha_entrega DESC
                LIMIT 1
            ) AS ultimo_usuario_acta,
            (
                SELECT a.fecha_devolucion
                FROM actas a
                WHERE a.device_id = d.id
                ORDER BY a.fecha_entrega DESC
                LIMIT 1
            ) AS ultima_fecha_devolucion,
            (
                SELECT mt.type
                FROM maintenances mt
                WHERE mt.device_id = d.id
                ORDER BY mt.fecha DESC
                LIMIT 1
            ) AS tipo_mantenimiento
        FROM devices d
        LEFT JOIN models m ON d.model_id = m.id
        $where
        ORDER BY d.internal_number ASC
        LIMIT $por_pagina OFFSET $offset";
$res = $conn->query($sql);

// Obtener mantenimientos por equipo y mes para los equipos de la página actual
$device_ids = [];
$res_ids = $conn->query("SELECT d.id, d.internal_number FROM devices d LEFT JOIN models m ON d.model_id = m.id $where ORDER BY d.internal_number ASC LIMIT $por_pagina OFFSET $offset");
while ($row = $res_ids->fetch_assoc()) {
    $device_ids[$row['id']] = $row['internal_number'];
}
$mantenimientos_por_mes = [];
if (count($device_ids) > 0) {
    $ids_str = implode(',', array_map('intval', array_keys($device_ids)));
    $year = date('Y'); // Puedes cambiar el año si lo necesitas
    // Cambia la consulta para traer responsible y external
    $sql_mant = "SELECT device_id, MONTH(date) as mes, responsible, external FROM maintenance WHERE device_id IN ($ids_str) AND YEAR(date) = $year";
    $res_mant = $conn->query($sql_mant);
    while ($row = $res_mant->fetch_assoc()) {
        $dev = $row['device_id'];
        $mes = intval($row['mes']);
        if (!isset($mantenimientos_por_mes[$dev])) $mantenimientos_por_mes[$dev] = [];
        if (!isset($mantenimientos_por_mes[$dev][$mes])) $mantenimientos_por_mes[$dev][$mes] = [];
        if (!empty($row['responsible'])) {
            $mantenimientos_por_mes[$dev][$mes]['interno'] = true;
        }
        if (!empty($row['external'])) {
            $mantenimientos_por_mes[$dev][$mes]['externo'] = true;
        }
    }
}

// Depuración temporal: mostrar el array de mantenimientos por mes
echo '<pre style="background:#fffbe6;border:1px solid #ffe58f;padding:10px;margin:10px 0;color:#ad8b00;">';
echo "DEBUG \$mantenimientos_por_mes:\n";
print_r($mantenimientos_por_mes);
echo '</pre>';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Equipos</title>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .devices-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            margin-top: 2rem;
        }
  .device-card {
    background: linear-gradient(135deg, #ffffff, #f9f9f9);
    border-radius: 16px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
    padding: 2rem 1.5rem;
    width: 100%;
    max-width: 360px;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    transition: all 0.3s ease;
    border: 1px solid #eaeaea;
}

.device-card:hover {
    box-shadow: 0 8px 32px rgba(33, 118, 174, 0.18);
    transform: translateY(-4px);
    background: linear-gradient(135deg, #ffffff, #f0faff);
    border-color: #cce4f5;
}

    .main-content {
            text-align: center;
        }

        .main-content a{
            text-decoration: none;
            color: #40a335;
        }

        
        
        .device-photo {
            width: 120px;
            height: 90px;
            object-fit: contain;
            border-radius: 8px;
            background: #f2f6fa;
            border: 1px solid #e0e0e0;
            margin-bottom: 1rem;
        }
        .device-internal {
            font-size: 1.1rem;
            font-weight: bold;
            color: #2176ae;
            margin-bottom: 0.5rem;
        }
        .device-link {
            margin-top: 0.7rem;
            display: inline-block;
            color: #215ba0;
            text-decoration: none;
            font-size: 0.98em;
        }
        
        .device-link:hover {
           color: #40a335;
        }
        .pagination {
            margin: 2em auto 0 auto;
            display: flex;
            justify-content: center;
            gap: 0.5em;
        }
        .pagination a, .pagination span {
            display: inline-block;
            padding: 0.5em 1em;
            border-radius: 6px;
            background: #f2f6fa;
            color: #215ba0;
            text-decoration: none;
            font-weight: 500;
            border: 1px solid #e0e0e0;
            transition: background 0.15s;
        }
        .pagination a:hover {
            background: #215ba0;
            color: #fff;
        }
        .pagination .active {
            background: #215ba0;
            color: #fff;
            pointer-events: none;
        }
        .search-bar-container {
            display: flex;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }
        .search-bar {
            display: flex;
            gap: 0.5rem;
        }
        .search-bar input[type="text"] {
            padding: 0.5em 1em;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
            font-size: 1em;
            width: 220px;
        }
        .search-bar button {
            padding: 0.5em 1.2em;
            border-radius: 6px;
            background: #215ba0;
            color: #fff;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.15s;
        }
        .search-bar button:hover {
            background: #2176ae;
        }
        @media (max-width: 1100px) {
            .devices-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 700px) {
            .devices-grid { grid-template-columns: 1fr; }
        }
        /* Botón CSV */
        .csv-download-btn {
            padding: 0.5em 1.2em;
            border-radius: 6px;
            background: #40a335;
            color: #fff;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.15s;
            margin-bottom: 1.2em;
            margin-right: 1em;
            text-decoration: none;
            display: inline-block;
        }
        .csv-download-btn:hover {
            background: #2176ae;
        }
        /* Estilos para la tabla de mantenimientos */
        .mant-interno { background: #d4f8e8; color: #217a3b; font-weight: bold; }
        .mant-externo { background: #d4e6fa; color: #215ba0; font-weight: bold; }
        .mant-ambos   { background: #e8d4fa; color: #6c2eb6; font-weight: bold; }
        .mant-ninguno { color: #bbb; }
        .mant-leyenda { margin-bottom: 0.7em; text-align: left; font-size: 0.98em; }
        .mant-leyenda span { display: inline-block; min-width: 2em; text-align: center; border-radius: 4px; margin-right: 0.7em; padding: 0.2em 0.7em; }
        .mant-leyenda .mant-interno { background: #d4f8e8; color: #217a3b; }
        .mant-leyenda .mant-externo { background: #d4e6fa; color: #215ba0; }
        .mant-leyenda .mant-ambos   { background: #e8d4fa; color: #6c2eb6; }
    </style>
    <script>
    // Automatiza la búsqueda al escribir (después de un pequeño retardo)
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('.search-bar input[name="search"]');
        const searchForm = document.querySelector('.search-bar');
        let timeout = null;
        if (searchInput && searchForm) {
            searchInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    // Solo enviar si el valor cambió respecto al anterior submit
                    searchForm.submit();
                }, 600); // Espera 600ms tras dejar de escribir
            });
        }
    });
    </script>
</head>
<body>
    <?php include 'layout.php'; ?>
    <div class="main-content">
        <h2>Lista de Equipos</h2>

        <!-- Leyenda de la gráfica -->
        <div class="mant-leyenda">
            <span class="mant-interno">1 = Interno</span>
            <span class="mant-externo">2 = Externo</span>
            <span class="mant-ambos">● = Ambos</span>
            <span class="mant-ninguno">- = Sin mantenimiento</span>
        </div>

        <!-- Tabla de mantenimientos por mes -->
        <div style="overflow-x:auto;margin-bottom:2em;">
        <table border="1" cellpadding="6" cellspacing="0" style="margin:auto;border-collapse:collapse;background:#fff;">
            <thead>
                <tr style="background:#f2f6fa;">
                    <th>Número Interno</th>
                    <th>Ene</th>
                    <th>Feb</th>
                    <th>Mar</th>
                    <th>Abr</th>
                    <th>May</th>
                    <th>Jun</th>
                    <th>Jul</th>
                    <th>Ago</th>
                    <th>Sep</th>
                    <th>Oct</th>
                    <th>Nov</th>
                    <th>Dic</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($device_ids as $dev_id => $internal_number): ?>
                <tr>
                    <td style="font-weight:bold;color:#2176ae;"><?= htmlspecialchars($internal_number) ?></td>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <?php
                            $cellClass = 'mant-ninguno';
                            $cellVal = '-';
                            if (isset($mantenimientos_por_mes[$dev_id][$m])) {
                                $tipos = $mantenimientos_por_mes[$dev_id][$m];
                                if (isset($tipos['interno']) && isset($tipos['externo'])) {
                                    $cellClass = 'mant-ambos';
                                    $cellVal = '●';
                                } elseif (isset($tipos['interno'])) {
                                    $cellClass = 'mant-interno';
                                    $cellVal = '1';
                                } elseif (isset($tipos['externo'])) {
                                    $cellClass = 'mant-externo';
                                    $cellVal = '2';
                                }
                            }
                        ?>
                        <td class="<?= $cellClass ?>" style="text-align:center;"><?= $cellVal ?></td>
                    <?php endfor; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <div style="display:flex;justify-content:center;margin-bottom:1.2em;">
            
            <form class="search-bar" method="get" action="">
                <input type="text" name="search" placeholder="Buscar equipo..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit">Buscar</button>
                <?php if ($search !== ''): ?>
                    <a href="views_divices.php" style="margin-left:0.5em;color:#40a335;text-decoration:none;font-size:0.98em;">Limpiar</a>
                <?php endif; ?>
                <?php if ($pagina > 1): ?>
                    <input type="hidden" name="pagina" value="<?= $pagina ?>">
                <?php endif; ?>
                <br>
                    <a href="?download=csv" class="text-decoration-none">Descargar CSV</a>
            </form>
            
        </div>
        
        <div class="devices-grid">
        <?php while ($row = $res->fetch_assoc()): ?>
            <?php
                // Buscar la foto igual que en device_profile.php
                $photoPath = "../uploads/" . $row['internal_number'] . "/" . $row['internal_number'] . ".*";
                $photoFiles = glob($photoPath);
                $photoUrl = (count($photoFiles) > 0) ? $photoFiles[0] : "../assets/img/no-image.png";
            ?>
            <div class="device-card">
                <img class="device-photo" src="<?= htmlspecialchars($photoUrl) ?>" alt="Foto equipo">
                <div class="device-internal"><?= htmlspecialchars($row['internal_number']) ?></div>
                <?php if (!empty($row['tipo_mantenimiento'])): ?>
                    <div style="font-size:0.97em;color:#2176ae;margin-bottom:0.5em;">
                        Mantenimiento: 
                        <strong>
                            <?php
                                if (strtolower($row['tipo_mantenimiento']) === 'interno') {
                                    echo '1';
                                } elseif (strtolower($row['tipo_mantenimiento']) === 'externo') {
                                    echo '2';
                                } else {
                                    echo htmlspecialchars($row['tipo_mantenimiento']);
                                }
                            ?>
                        </strong>
                    </div>
                <?php else: ?>
                    <div style="font-size:0.97em;color:#bbb;margin-bottom:0.5em;">
                        Sin mantenimiento registrado
                    </div>
                <?php endif; ?>
                <?php
                    // Si hay acta y la última acta tiene fecha_devolucion vacía, mostrar usuario asignado
                    if (!empty($row['ultimo_usuario_acta']) && empty($row['ultima_fecha_devolucion'])): ?>
                    <div style="font-size:0.98em;color:#555;margin-bottom:0.5em;">
                        
                        Asignado a: <br> <strong><?= htmlspecialchars($row['ultimo_usuario_acta']) ?></strong> 
                    </div>
                <?php
                    // Si hay acta y la última acta tiene fecha_devolucion NO vacía, mostrar "Sin asignar"
                    elseif (!empty($row['ultima_fecha_devolucion'])): ?>
                    <div style="font-size:0.98em;color:#bbb;margin-bottom:0.5em;">
                        Sin asignar
                    </div>
                <?php
                    // Si no hay acta, mostrar "Sin acta registrada"
                    else: ?>
                    <div style="font-size:0.98em;color:#bbb;margin-bottom:0.5em;">
                        Sin acta registrada
                    </div>
                <?php endif; ?>
                <a class="device-link" href="device_profile.php?id=<?= $row['id'] ?>">Ver Equipo</a>
            </div>
        <?php endwhile; ?>
        </div>
        <?php if ($total_paginas > 1): ?>
        <div class="pagination">
            <?php if ($pagina > 1): ?>
                <a href="?pagina=1<?= $search !== '' ? '&search=' . urlencode($search) : '' ?>">&laquo; Primero</a>
                <a href="?pagina=<?= $pagina - 1 ?><?= $search !== '' ? '&search=' . urlencode($search) : '' ?>">&lt; Anterior</a>
            <?php endif; ?>
            <?php
            // Mostrar máximo 5 páginas alrededor de la actual
            $start = max(1, $pagina - 2);
            $end = min($total_paginas, $pagina + 2);
            for ($i = $start; $i <= $end; $i++): ?>
                <?php if ($i == $pagina): ?>
                    <span class="active"><?= $i ?></span>
                <?php else: ?>
                    <a href="?pagina=<?= $i ?><?= $search !== '' ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                <?php endif; ?>
                
            <?php endfor; ?>
            <?php if ($pagina < $total_paginas): ?>
                <a href="?pagina=<?= $pagina + 1 ?><?= $search !== '' ? '&search=' . urlencode($search) : '' ?>">Siguiente &gt;</a>
                <a href="?pagina=<?= $total_paginas ?><?= $search !== '' ? '&search=' . urlencode($search) : '' ?>">Último &raquo;</a>
            <?php endif; ?>
            
        </div>
        
        <?php endif; ?>
        
    </div>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
</body>
</html>