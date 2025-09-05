<?php
include_once __DIR__ . '/../includes/conection.php';
include_once __DIR__ . '/layout.php';

// Obtener todos los registros ordenados por fecha descendente
$res = $conn->query("SELECT * FROM vehicle_preoperation ORDER BY date DESC, time DESC");
$registros = [];
while ($row = $res->fetch_assoc()) {
    $registros[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Preoperacionales de Vehículo</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f7f9fb;
        }

        .acordeon-list {
            max-width: 900px;
            margin: 2em auto;
        }

        .pdf-header-table {
            width: 100%;
            margin-bottom: 2em;
        }

        .pdf-header-logo {
            width: 120px;
        }

        .pdf-header-title {
            text-align: center;
            font-size: 1.2em;
            font-weight: bold;
        }

        .pdf-header-info {
            text-align: right;
        }

        .pdf-header-info-table {
            font-size: 0.95em;
        }

        .acordeon-item {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px #0001;
            margin-bottom: 1em;
            overflow: hidden;
            border: 1px solid #eaeaea;
        }

        .acordeon-header {
  cursor: pointer;
  width: 100%;
  padding: 1.2em 1.8em;
  background: #eaf2fb; /* o usa un solo color */
  color: #215ba0;
  font-weight: 600;
  font-size: 1.1em;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border: none;
  transition: background 0.25s ease;
  border-radius: 12px; /* para coincidir con el card */
  box-sizing: border-box;
}


        .acordeon-header:hover {
            background: #dbeafe;
        }

        .acordeon-header .arrow {
            font-size: 1.2em;
            transition: transform 0.2s;
        }

        .acordeon-header.active .arrow {
            transform: rotate(90deg);
        }

        .acordeon-content {
            display: none;
            padding: 1.5em 2em 1.5em 2em;
            background: #fff;
            border-top: 1px solid #eaeaea;
            animation: fadeIn 0.3s;
        }

        .acordeon-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5em;
        }

        .summary-table th,
        .summary-table td {
            text-align: left;
            padding: 0.5em 1em;
            border-bottom: 1px solid #eaeaea;
        }

        .summary-table th {
            background: #f7f9fb;
            color: #215ba0;
        }

        .firma-img {
            height: 60px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: #fafbfc;
        }

        .obs-box {
            background: #f8fafc;
            border-radius: 6px;
            padding: 1em;
            margin-top: 1em;
            color: #215ba0;
        }

        .sn-color {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="acordeon-list">

        <h2 style="text-align:center;margin-top:3.5em;">Preoperacionales de Vehículo</h2>

        <?php if (count($registros) === 0): ?>
            <div style="color:#b00;text-align:center;">No hay registros.</div>
        <?php else: ?>
            <?php foreach ($registros as $i => $r): ?>
                <div class="acordeon-item">
                    <button class="acordeon-header" type="button">
                        <span>
                            <?= htmlspecialchars(date('d/m/Y', strtotime($r['date']))) ?> -
                            <?= htmlspecialchars($r['driver_name']) ?> -
                            <?= htmlspecialchars($r['plate']) ?>
                        </span>
                        <span class="arrow">&#9654;</span>
                    </button>
                    <div class="acordeon-content">
                        <table class="pdf-header-table" style="width:100%;margin-bottom:2em;">
                            <tr>
                                <td class="pdf-header-logo" style="width:120px;">
                                    <img src="../assets/images/logo.png" class="pdf-header-logo-img" style="max-width:100px;">
                                </td>
                                <td class="pdf-header-title" style="text-align:center;font-size:1.2em;font-weight:bold;">
                                    FORMULARIO DE PRECHEQUEO OPERACIONAL <br>
                                    AUTOMÓVIL O PICK-UP
                                </td>
                                <td class="pdf-header-info" style="text-align:right;">
                                    <table class="pdf-header-info-table" style="font-size:0.95em;">
                                        <tr>
                                            <td><strong>Código:</strong> ACM-ADM-TI-FO-004</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Versión:</strong> 002</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Fecha:</strong> 06-06-2025</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                        <table class="summary-table">
                            <tr>
                                <th>Conductor</th>
                                <td><?= htmlspecialchars($r['driver_name']) ?></td>
                            </tr>
                            <tr>
                                <th>Documento</th>
                                <td><?= htmlspecialchars($r['driver_document']) ?></td>
                            </tr>
                            <tr>
                                <th>Placa</th>
                                <td><?= htmlspecialchars($r['plate']) ?></td>
                            </tr>
                            <tr>
                                <th>Kilometraje</th>
                                <td><?= htmlspecialchars($r['mileage']) ?></td>
                            </tr>
                            <tr>
                                <th>Fecha</th>
                                <td><?= htmlspecialchars($r['date']) ?></td>
                            </tr>
                            <tr>
                                <th>Hora</th>
                                <td><?= htmlspecialchars($r['time']) ?></td>
                            </tr>
                            <tr>
                                <th>Estado de salud</th>
                                <td class="sn-color"><?= htmlspecialchars($r['health_condition']) ?></td>
                            </tr>
                            <tr>
                                <th>Llantas</th>
                                <td class="sn-color"><?= htmlspecialchars($r['tires']) ?></td>
                            </tr>
                            <tr>
                                <th>Luces</th>
                                <td class="sn-color"><?= htmlspecialchars($r['lights']) ?></td>
                            </tr>
                            <tr>
                                <th>Bocina</th>
                                <td class="sn-color"><?= htmlspecialchars($r['horn']) ?></td>
                            </tr>
                            <tr>
                                <th>Espejos</th>
                                <td class="sn-color"><?= htmlspecialchars($r['mirrors']) ?></td>
                            </tr>
                            <tr>
                                <th>Líquidos</th>
                                <td class="sn-color"><?= htmlspecialchars($r['fluids']) ?></td>
                            </tr>
                            <tr>
                                <th>Fugas</th>
                                <td class="sn-color"><?= htmlspecialchars($r['leaks']) ?></td>
                            </tr>
                            <tr>
                                <th>Frenos</th>
                                <td class="sn-color"><?= htmlspecialchars($r['brakes']) ?></td>
                            </tr>
                            <tr>
                                <th>Parabrisas</th>
                                <td class="sn-color"><?= htmlspecialchars($r['windshield']) ?></td>
                            </tr>
                            <tr>
                                <th>Retención</th>
                                <td class="sn-color"><?= htmlspecialchars($r['retention']) ?></td>
                            </tr>
                            <tr>
                                <th>Documentos</th>
                                <td class="sn-color"><?= htmlspecialchars($r['documents']) ?></td>
                            </tr>
                            <tr>
                                <th>Prevención</th>
                                <td class="sn-color"><?= htmlspecialchars($r['prevention']) ?></td>
                            </tr>
                            <tr>
                                <th>Luces tablero</th>
                                <td class="sn-color"><?= htmlspecialchars($r['dashboard_lights']) ?></td>
                            </tr>
                            <tr>
                                <th>Observaciones Generales</th>
                                <td>
                                    <?php if (!empty($r['general_observations'])): ?>
                                        <div class="obs-box"><?= nl2br(htmlspecialchars($r['general_observations'])) ?></div>
                                    <?php else: ?>
                                        <span style="color:#aaa;">No hay observaciones</span>
                                    <?php endif; ?>
                                </td>
                            <tr>
                                <th>Firma Usuario</th>
                                <td>
                                    <?php
                                    if (!empty($r['signature_user_path']) && file_exists(__DIR__ . '/../uploads/' . $r['signature_user_path'])) {
                                        echo '<img class="firma-img" src="../uploads/' . htmlspecialchars($r['signature_user_path']) . '" alt="Firma Usuario">';
                                    } else {
                                        echo '<span style="color:#aaa;">No registrada</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        </table>

                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <script>
        // Acordeón JS: alterna el contenido al hacer clic
        document.querySelectorAll('.acordeon-header').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const isActive = btn.classList.contains('active');
                // Cerrar todos
                document.querySelectorAll('.acordeon-header').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.acordeon-content').forEach(tc => tc.classList.remove('active'));
                // Si no estaba activo, abrir; si estaba activo, dejar todo cerrado
                if (!isActive) {
                    btn.classList.add('active');
                    btn.nextElementSibling.classList.add('active');
                    window.scrollTo(0, btn.getBoundingClientRect().top + window.scrollY - 60);
                }
            });
        });
        // Colorea solo el texto "Sí" de verde y "No" de rojo en las celdas .sn-color
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.sn-color').forEach(function (td) {
                if (td.textContent.trim() === 'No') {
                    td.style.color = '#e74c3c';
                } else if (td.textContent.trim() === 'Sí') {
                    td.style.color = '#27ae60';
                } else {
                    td.style.color = '';
                }
            });
        });
    </script>
</body>

</html>