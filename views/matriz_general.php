<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
include_once __DIR__ . '/../includes/conection.php';

// Consulta para la matriz general
$sql = "SELECT 
    d.id,
    d.internal_number,
    d.serial,
    m.name AS modelo,
    b.name AS marca,
    p.name AS procesador,
    r.name AS ram,
    s.name AS almacenamiento,
    pt.name AS tipo,
    gc.name AS grafica,
    st.name AS estado,
    d.device_value,
    (
        SELECT GROUP_CONCAT(a.name SEPARATOR ', ')
        FROM device_accessories da
        JOIN accessories a ON da.accessory_id = a.id
        WHERE da.device_id = d.id
    ) AS accesorios,
    (
        SELECT CONCAT_WS(' ', u.first_name, u.last_name)
        FROM actas a
        LEFT JOIN users u ON a.user_id = u.id
        WHERE a.device_id = d.id
        ORDER BY a.fecha_entrega DESC
        LIMIT 1
    ) AS responsable
FROM devices d
LEFT JOIN models m ON d.model_id = m.id
LEFT JOIN brands b ON d.brand_id = b.id
LEFT JOIN processors p ON d.processor_id = p.id
LEFT JOIN rams r ON d.ram_id = r.id
LEFT JOIN storages s ON d.storage_id = s.id
LEFT JOIN provider_types pt ON d.provider_type_id = pt.id
LEFT JOIN graphics_cards gc ON d.graphics_card_id = gc.id
LEFT JOIN statuses st ON d.status_id = st.id
ORDER BY d.internal_number ASC";
$res = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Matriz General</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .matriz-container {
            width: 80%;
            margin: auto;
            padding-top: 80px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.09);
            min-height: 300px;
            overflow-x: auto;
        }
        h2 {
            color: #215ba0;
            text-align: center;
            margin-bottom: 1.5em;
        }
        
        tr td a{
            color:rgb(190, 11, 190);
            text-decoration: underline;
        }

        .matriz-table {
            width: 90%;
            margin: auto;
            border-collapse: collapse;
            margin-top: 2em;
            font-size: 0.60em;
            min-width: 900px;
        }
        .matriz-table th, .matriz-table td {
            border: 1px solid #e0e0e0;
            padding: 0.1em 0.7em;
            text-align: left;
        }
        .matriz-table th {
            background: #f4f8fb;
            color: #215ba0;
            font-weight: bold;
            cursor: pointer;
            user-select: none;
        }
        .matriz-table th.sort-asc:after {
            content: " ▲";
            font-size: 0.9em;
        }
        .matriz-table th.sort-desc:after {
            content: " ▼";
            font-size: 0.9em;
        }
        .matriz-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        .matriz-table tr:hover {
            background: #eaf4ff;
        }
        @media (max-width: 900px) {
            .matriz-container {
                padding: 1rem 0.2rem;
            }
            .matriz-table {
                font-size: 0.93em;
                min-width: 700px;
            }
        }
        @media (max-width: 600px) {
            .matriz-container {
                padding: 0.5rem 0;
            }
            .matriz-table {
                font-size: 0.89em;
                min-width: 600px;
            }
        }
        /* Fuerza scroll horizontal en móviles */
        @media (max-width: 900px) {
            .matriz-container {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
<?php include 'layout.php'; ?>
<div class="matriz-container">
    <table class="institution-header" style="width:100%;margin-bottom:2em;">
        <tr>
            <td style="width:120px;">
                <img src="../assets/images/logo-acema.webp" alt="Logo" style="height:60px;max-width:120px;">
            </td>
            <td style="text-align:center;">
                <div style="font-size:1.1rem; font-weight:bold; letter-spacing:1px;">
                    BASE DE DATOS Y SEGUIMIENTO DE EQUIPOS DE CÓMPUTO
                </div>
            </td>
            <td style="text-align:center;font-size:1rem;">
                <div><strong>Código:</strong> ACM-TI-FORMS-008</div>
                <div><strong>Versión:</strong> 002</div>
                <div><strong>Fecha:</strong> 09-06-2025</div>
            </td>
        </tr>
    </table>
    
    <table class="matriz-table" id="matrizTable">
        <!-- El encabezado institucional ya está arriba, se elimina de aquí -->
        <thead>
            <tr>
                <th>Número Interno</th>
                <th>Asignado a</th>
                <th>Marca</th>
                <th>Serial</th>
                <th>Modelo</th>
                <th>Procesador</th>
                <th>RAM</th>
                <th>Almacenamiento</th>
                <th>Tipo</th>
                <th>Gráfica</th>
                <th>Accesorios</th>
                <th>Estado</th>
                <th>Valor</th>
                <th>Activo</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $res->fetch_assoc()): ?>
            <tr>
                <td>
                    <a href="device_profile.php?id=<?= $row['id'] ?>" style="color:#215ba0;text-decoration:none; font-weight: bold;">
                        <?= htmlspecialchars($row['internal_number']) ?>
                    </a>
                </td>
                <td><?= htmlspecialchars($row['responsable']) ?></td>
                
                <td><?= htmlspecialchars($row['marca']) ?></td>
                <td><?= htmlspecialchars($row['serial']) ?></td>
                <td><?= htmlspecialchars($row['modelo']) ?></td>
                <td><?= htmlspecialchars($row['procesador']) ?></td>
                <td><?= htmlspecialchars($row['ram']) ?></td>
                <td><?= htmlspecialchars($row['almacenamiento']) ?></td>
                <td><?= htmlspecialchars($row['tipo']) ?></td>
                <td><?= htmlspecialchars($row['grafica']) ?></td>
                <td><?= htmlspecialchars($row['accesorios']) ?></td>
                <td><?= htmlspecialchars($row['estado']) ?></td>
                <td>
                    <?php
                        if (is_numeric($row['device_value'])) {
                            echo '$' . number_format($row['device_value'], 0, ',', '.');
                        } else {
                            echo '';
                        }
                    ?>
                </td>
                <td>
                    <?php
                        // Si tiene responsable está Activo, si no Disponible
                        echo trim($row['responsable']) ? 'Activo' : 'Disponible';
                    ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<script>
// Simple sort para tabla HTML
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('matrizTable');
    const headers = table.querySelectorAll('th');
    let sortCol = -1;
    let sortDir = 1; // 1 asc, -1 desc

    headers.forEach((th, idx) => {
        th.addEventListener('click', function() {
            // Quitar clase de todos
            headers.forEach(h => h.classList.remove('sort-asc', 'sort-desc'));
            // Cambiar dirección si es la misma columna
            if (sortCol === idx) {
                sortDir = -sortDir;
            } else {
                sortCol = idx;
                sortDir = 1;
            }
            th.classList.add(sortDir === 1 ? 'sort-asc' : 'sort-desc');
            sortTable(table, idx, sortDir);
        });
    });

    function sortTable(table, col, dir) {
        const tbody = table.tBodies[0];
        const rows = Array.from(tbody.querySelectorAll('tr'));
        rows.sort((a, b) => {
            let aText = a.children[col].textContent.trim();
            let bText = b.children[col].textContent.trim();
            // Si es valor, quitar $ y puntos
            if (col === 12) {
                aText = aText.replace(/[^\d]/g, '');
                bText = bText.replace(/[^\d]/g, '');
                aText = parseInt(aText) || 0;
                bText = parseInt(bText) || 0;
            }
            if (!isNaN(aText) && !isNaN(bText) && col !== 10 && col !== 11) {
                return (aText - bText) * dir;
            }
            return aText.localeCompare(bText, undefined, {numeric: true}) * dir;
        });
        rows.forEach(row => tbody.appendChild(row));
    }
});
</script>
</body>
</html>
