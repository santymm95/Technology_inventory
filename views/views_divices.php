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
            ) AS ultima_fecha_devolucion
        FROM devices d
        LEFT JOIN models m ON d.model_id = m.id
        $where
        ORDER BY d.internal_number ASC
        LIMIT $por_pagina OFFSET $offset";
$res = $conn->query($sql);
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
     
        .csv-download-btn:hover {
            background: #2176ae;
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

        h2 {
             color: #215ba0;
        }

        .header {
   
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1em;
        }

        .device-card-link {
    text-decoration: none;
    color: inherit;
    display: block;
}
.device-card {
    cursor: pointer;
}
.device-card-link .device-link {
    display: none;
}
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
        <div class="header">
              <a href="ti.php" class="btn-back">Volver</a>
 

          
        </div>
        <h2>Lista de Equipos</h2>
    
        <!-- Se elimina la barra de búsqueda -->
        <!-- Filtros por tipo de equipo -->
        <div style="display:flex;flex-direction:column;align-items:center;gap:0;margin-bottom:1em;">
            <?php
            // Obtener filtros activos
            $filter_types = isset($_GET['type']) ? (array)$_GET['type'] : [];
            function checked($type, $filter_types) { return in_array($type, $filter_types) ? 'checked' : ''; }
            ?>
            <form id="filterForm" style="display:flex;gap:1em;align-items:center;justify-content:center;">
                <label><input type="checkbox" name="type[]" value="pc" <?= checked('pc', $filter_types) ?>> PC</label>
                <label><input type="checkbox" name="type[]" value="printer" <?= checked('printer', $filter_types) ?>> Impresora</label>
                <label><input type="checkbox" name="type[]" value="vehicle" <?= checked('vehicle', $filter_types) ?>> Vehículo</label>
                <label><input type="checkbox" name="type[]" value="air" <?= checked('air', $filter_types) ?>> Aire</label>
                <label><input type="checkbox" name="type[]" value="cctv" <?= checked('cctv', $filter_types) ?>> CCTV</label>
            </form>
            <!-- Select dinámico relacionado -->
            <div id="relatedSelectContainer" style="margin-top:1em;min-width:220px;">
            <?php
            // Mostrar el select solo si hay un solo filtro seleccionado
            if (count($filter_types) === 1) {
                $type = $filter_types[0];
                if ($type === 'pc') {
                    $q = $conn->query("SELECT id, internal_number FROM devices ORDER BY internal_number ASC");
                    echo '<select id="relatedSelect" style="padding:0.5em 1em;border-radius:6px;border:1px solid #e0e0e0;font-size:1em;width:220px;">';
                    echo '<option value="">Seleccione un PC...</option>';
                    while ($r = $q->fetch_assoc()) {
                        // Buscar responsable
                        $res_acta = $conn->query("SELECT CONCAT_WS(' ', u.first_name, u.last_name) AS responsable, a.fecha_devolucion
                            FROM actas a
                            LEFT JOIN users u ON a.user_id = u.id
                            WHERE a.device_id = " . intval($r['id']) . "
                            ORDER BY a.fecha_entrega DESC
                            LIMIT 1");
                        $acta = $res_acta->fetch_assoc();
                        $responsable = '';
                        if ($acta && !empty($acta['responsable']) && empty($acta['fecha_devolucion'])) {
                            $responsable = htmlspecialchars($acta['responsable']);
                        } else {
                            // Solo el cuadro verde si no tiene responsable
                            $responsable = '<span style="font-size:1.2em;">🟢</span>';
                        }
                        echo '<option value="' . htmlspecialchars($r['id']) . '">' . htmlspecialchars($r['internal_number']) . ' - ' . $responsable . '</option>';
                    }
                    echo '</select>';
                } elseif ($type === 'printer') {
                    $q = $conn->query("SELECT id, internal_number FROM printer ORDER BY internal_number ASC");
                    echo '<select id="relatedSelect" style="padding:0.5em 1em;border-radius:6px;border:1px solid #e0e0e0;font-size:1em;width:220px;">';
                    echo '<option value=\"\">Seleccione una impresora...</option>';
                    while ($r = $q->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($r['id']) . '">' . htmlspecialchars($r['internal_number']) . '</option>';
                    }
                    echo '</select>';
                } elseif ($type === 'vehicle') {
                    $q = $conn->query("SELECT id, placa FROM vehicle ORDER BY placa ASC");
                    echo '<select id="relatedSelect" style="padding:0.5em 1em;border-radius:6px;border:1px solid #e0e0e0;font-size:1em;width:220px;">';
                    echo '<option value=\"\">Seleccione un vehículo...</option>';
                    while ($r = $q->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($r['id']) . '">' . htmlspecialchars($r['placa']) . '</option>';
                    }
                    echo '</select>';
                } elseif ($type === 'air') {
                    $q = $conn->query("SELECT id, internal_number FROM air ORDER BY internal_number ASC");
                    echo '<select id="relatedSelect" style="padding:0.5em 1em;border-radius:6px;border:1px solid #e0e0e0;font-size:1em;width:220px;">';
                    echo '<option value=\"\">Seleccione un aire acondicionado...</option>';
                    while ($r = $q->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($r['id']) . '">' . htmlspecialchars($r['internal_number']) . '</option>';
                    }
                    echo '</select>';
                } elseif ($type === 'cctv') {
                    $q = $conn->query("SELECT id, internal_number FROM cctv ORDER BY internal_number ASC");
                    echo '<select id="relatedSelect" style="padding:0.5em 1em;border-radius:6px;border:1px solid #e0e0e0;font-size:1em;width:220px;">';
                    echo '<option value=\"\">Seleccione una cámara CCTV...</option>';
                    while ($r = $q->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($r['id']) . '">' . htmlspecialchars($r['internal_number']) . '</option>';
                    }
                    echo '</select>';
                }
                // NVR filter
                elseif ($type === 'nvr') {
                    $q = $conn->query("SELECT id, internal_number FROM nvr_devices ORDER BY internal_number ASC");
                    echo '<select id="relatedSelect" style="padding:0.5em 1em;border-radius:6px;border:1px solid #e0e0e0;font-size:1em;width:220px;">';
                    echo '<option value=\"">Select an NVR...</option>';
                    while ($r = $q->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($r['id']) . '">' . htmlspecialchars($r['internal_number']) . '</option>';
                    }
                    echo '</select>';
                }
            }
            ?>
            </div>
            <script>
            // Enviar el filtro automáticamente al cambiar
            document.getElementById('filterForm').addEventListener('change', function() {
                let params = [];
                document.querySelectorAll('#filterForm input[type=checkbox]:checked').forEach(function(cb){
                    params.push('type[]=' + encodeURIComponent(cb.value));
                });
                window.location = 'views_divices.php' + (params.length ? '?' + params.join('&') : '');
            });

            // Redirección al seleccionar un elemento del select relacionado
            document.addEventListener('DOMContentLoaded', function() {
                var select = document.getElementById('relatedSelect');
                if (select) {
                    select.addEventListener('change', function() {
                        var type = <?= json_encode($filter_types[0] ?? '') ?>;
                        var val = this.value;
                        if (!val) return;
                        if (type === 'pc') {
                            window.location = 'device_profile.php?id=' + encodeURIComponent(val);
                        } else if (type === 'printer') {
                            window.location = 'printer_list.php?id=' + encodeURIComponent(val);
                        } else if (type === 'vehicle') {
                            window.location = 'vehicle_list.php?id=' + encodeURIComponent(val);
                        } else if (type === 'air') {
                            window.location = 'list_air.php?id=' + encodeURIComponent(val);
                        } else if (type === 'cctv') {
                            window.location = 'list_cctv.php?id=' + encodeURIComponent(val);
                        } else if (type === 'nvr') {
                            window.location = 'nvr_profile.php?id=' + encodeURIComponent(val);
                        }
                    });
                }
            });
            </script>
        </div>
        <div class="devices-grid" id="devicesGrid">
        <?php
        // Filtrado por tipo
        $show_pc = empty($filter_types) || in_array('pc', $filter_types);
        $show_printer = empty($filter_types) || in_array('printer', $filter_types);
        $show_vehicle = empty($filter_types) || in_array('vehicle', $filter_types);
        $show_air = empty($filter_types) || in_array('air', $filter_types);
        $show_cctv = empty($filter_types) || in_array('cctv', $filter_types);
        $show_nvr = empty($filter_types) || in_array('nvr', $filter_types);

        // Mostrar PCs
        if ($show_pc):
            while ($row = $res->fetch_assoc()):
                // Buscar la foto igual que en device_profile.php
                $photoPath = "../uploads/" . $row['internal_number'] . "/" . $row['internal_number'] . ".*";
                $photoFiles = glob($photoPath);
                $photoUrl = (count($photoFiles) > 0) ? $photoFiles[0] : "../assets/img/no-image.png";
        ?>
            <a class="device-card-link" href="device_profile.php?id=<?= $row['id'] ?>" style="text-decoration:none;color:inherit;">
                <div class="device-card" style="cursor:pointer;">
                    <img class="device-photo" src="<?= htmlspecialchars($photoUrl) ?>" alt="Foto equipo">
                    <div class="device-internal"><?= htmlspecialchars($row['internal_number']) ?></div>
                    <?php if (!empty($row['ultimo_usuario_acta']) && empty($row['ultima_fecha_devolucion'])): ?>
                        <div style="font-size:0.98em;color:#555;margin-bottom:0.5em;">
                            Asignado a: <br> <strong><?= htmlspecialchars($row['ultimo_usuario_acta']) ?></strong> 
                        </div>
                    <?php elseif (!empty($row['ultima_fecha_devolucion'])): ?>
                        <div style="font-size:0.98em;color:#bbb;margin-bottom:0.5em;">
                            Sin asignar
                        </div>
                    <?php else: ?>
                        <div style="font-size:0.98em;color:#bbb;margin-bottom:0.5em;">
                            Sin acta registrada
                        </div>
                    <?php endif; ?>
                    <span class="device-link" style="display:none;"></span>
                </div>
            </a>
        <?php endwhile; endif; ?>

        <?php
        // Mostrar impresoras
        if ($show_printer):
        $printers = $conn->query("SELECT * FROM printer ORDER BY id DESC");
        while ($printer = $printers->fetch_assoc()):
            $printerPhoto = !empty($printer['photo']) ? "../" . $printer['photo'] : "../assets/img/no-image.png";
        ?>
            <div class="device-card">
                <img class="device-photo" src="<?= htmlspecialchars($printerPhoto) ?>" alt="Foto impresora">
                <div class="device-internal"><?= htmlspecialchars($printer['internal_number']) ?></div>
                <a class="device-link" href="printer_list.php">Ver Impresora</a>
            </div>
        <?php endwhile; endif; ?>

        <?php
        // Mostrar vehículos
        if ($show_vehicle):
        $vehicles = $conn->query("SELECT * FROM vehicle ORDER BY id DESC");
        while ($vehicle = $vehicles->fetch_assoc()):
            $vehiclePhotoDir = "../uploads/" . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $vehicle['placa']) . "/";
            $vehiclePhotoPattern = $vehiclePhotoDir . "foto_vehiculo.*";
            $vehiclePhotoFiles = glob($vehiclePhotoPattern);
            $vehiclePhotoUrl = (count($vehiclePhotoFiles) > 0) ? $vehiclePhotoFiles[0] : "../assets/img/no-image.png";
        ?>
            <div class="device-card">
                <img class="device-photo" src="<?= htmlspecialchars($vehiclePhotoUrl) ?>" alt="Foto vehículo">
                <div class="device-internal"><?= htmlspecialchars($vehicle['placa']) ?></div>
                <a class="device-link" href="vehicle_list.php?placa=<?= urlencode($vehicle['placa']) ?>">Ver Vehículo</a>
            </div>
        <?php endwhile; endif; ?>

        <?php
        // Mostrar aires acondicionados
        if ($show_air):
        $airs = $conn->query("SELECT * FROM air ORDER BY id DESC");
        while ($air = $airs->fetch_assoc()):
            $internal_number = trim($air['internal_number']);
            $img_dir = "../uploads/" . $internal_number . "/";
            $img_path = "";
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
            foreach ($allowed_exts as $ext) {
                $files = glob($img_dir . "*." . $ext);
                if (count($files) > 0) {
                    $img_path = $files[0];
                    break;
                }
            }
            if (!$img_path || !file_exists($img_path)) {
                $img_path = "../assets/images/aire-acondicionado.png";
            }
            $is_pdf = strtolower(pathinfo($img_path, PATHINFO_EXTENSION)) === 'pdf';
        ?>
            <div class="device-card">
                <?php if ($is_pdf): ?>
                    <a href="<?= htmlspecialchars($img_path) ?>" target="_blank" style="display:inline-block;">
                        <img class="device-photo" src="../assets/images/pdf-icon.png" alt="Ver PDF" style="object-fit:contain;">
                        <div>Ver documento</div>
                    </a>
                <?php else: ?>
                    <img class="device-photo" src="<?= htmlspecialchars($img_path) ?>" alt="Foto aire">
                <?php endif; ?>
                <div class="device-internal"><?= htmlspecialchars($air['internal_number']) ?></div>
                <a class="device-link" href="list_air.php?internal_number=<?= urlencode($air['internal_number']) ?>">Ver Hoja de Vida</a>
            </div>
        <?php endwhile; endif; ?>

        <?php
        // Mostrar cámaras CCTV
        if ($show_cctv):
        $cctvs = $conn->query("SELECT * FROM cctv ORDER BY id DESC");
        while ($cctv = $cctvs->fetch_assoc()):
            $cctvPhoto = !empty($cctv['photo']) ? "../uploads/" . $cctv['photo'] : "../assets/img/no-image.png";
        ?>
            <div class="device-card">
                <img class="device-photo" src="<?= htmlspecialchars($cctvPhoto) ?>" alt="Foto CCTV">
                <div class="device-internal"><?= htmlspecialchars($cctv['internal_number']) ?></div>
                <a class="device-link" href="list_cctv.php?internal_number=<?= urlencode($cctv['internal_number']) ?>">Ver Hoja de Vida</a>
            </div>
        <?php endwhile; endif; ?>

        <?php
        // Mostrar NVRs
        if (empty($filter_types) || in_array('nvr', $filter_types)):
        $nvr_q = $conn->query("SELECT * FROM nvr_devices ORDER BY id DESC");
        while ($nvr = $nvr_q->fetch_assoc()):
            $nvrPhoto = "../" . $nvr['image_folder'] . "/photo.jpg";
            if (!file_exists($nvrPhoto)) $nvrPhoto = "../assets/img/no-image.png";
        ?>
            <div class="device-card">
                <img class="device-photo" src="<?= htmlspecialchars($nvrPhoto) ?>" alt="NVR photo">
                <div class="device-internal"><?= htmlspecialchars($nvr['internal_number']) ?></div>
                <a class="device-link" href="nvr_profile.php?id=<?= $nvr['id'] ?>">View NVR</a>
            </div>
        <?php endwhile; endif; ?>
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
