<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
include_once __DIR__ . '/../includes/db.php';

$placa = isset($_GET['placa']) ? trim($_GET['placa']) : null;

if ($placa) {
    // Mostrar solo la HV del vehículo seleccionado
    $stmt = $conn->prepare("SELECT * FROM vehicle WHERE placa = ?");
    $stmt->bind_param("s", $placa);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Mostrar todos los vehículos (vista general)
    $result = $conn->query("SELECT * FROM vehicle ORDER BY id DESC");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Vehículos</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 2rem auto 2.5rem auto;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.09);
            padding: 2.5rem 2rem;
        }
        .profile-header {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .profile-photo {
            width: 200px;
            height: 140px;
            object-fit: contain;
            border-radius: 10px;
            background: #f2f6fa;
            border: 1px solid #e0e0e0;
        }
        .profile-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
        }
        .profile-table th,
        .profile-table td {
            text-align: left;
            padding: 0.6rem 1rem;
            border-bottom: 1px solid #eaeaea;
        }
        .profile-table th {
            color: #215ba0;
            width: 220px;
            background: #f7f9fb;
        }
        .profile-table tr:last-child td {
            border-bottom: none;
        }
        .action-link {
            color: #2176ae;
            text-decoration: underline;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include 'layout.php'; ?>
    <div class="main-content">
        <?php
            // Mostrar errores si la consulta falla
            if (!$result) {
                echo "<div style='color:red;'>Error en la consulta: " . $conn->error . "</div>";
            }
        ?>
        <?php if (!$result || $result->num_rows === 0): ?>
            <div style="text-align:center; margin:2em; color:#e74c3c; font-size:1.2em;">
                Vehículo no encontrado.
            </div>
        <?php else: ?>
            <?php while ($vehicle = $result->fetch_assoc()): ?>
                <!-- Debug: mostrar ID y placa -->
                <!-- <div style="color: #888; font-size: 0.95em; margin-bottom: 0.5em;">
                    ID: <?= htmlspecialchars($vehicle['id']) ?> | Placa: <?= htmlspecialchars($vehicle['placa']) ?>
                </div> -->
                <?php
                    $photoDir = "../uploads/" . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $vehicle['placa']) . "/";
                    $photoPattern = $photoDir . "foto_vehiculo.*";
                    $photoFiles = glob($photoPattern);
                    $photoUrl = (count($photoFiles) > 0) ? $photoFiles[0] : "../assets/img/no-image.png";
                ?>
                <div
                    style="max-width:800px;margin:1.5rem auto 0 auto;text-align:center;display:flex;justify-content:center;gap:1em;">
                    <a href="edit_vehicle.php?id=<?= urlencode($vehicle['id']) ?>"
                        style="display:inline-block;background:#215ba0;color:#fff;padding:0.7em 2em;border-radius:7px;font-size:1.08em;text-decoration:none;box-shadow:0 1px 4px rgba(33,118,174,0.10);font-weight:500;min-width:120px;min-height:48px;line-height:32px;text-align:center;">
                        Editar
                    </a>
                    <a href="vehicle_documents.php?id=<?= urlencode($vehicle['id']) ?>"
                        style="display:inline-block;background:#f5c518;color:#215ba0;padding:0.7em 2em;border-radius:7px;font-size:1.08em;text-decoration:none;box-shadow:0 1px 4px rgba(245,197,24,0.10);font-weight:500;min-width:150px;min-height:48px;line-height:32px;text-align:center;">
                        Documentos
                    </a>
                    <a href="assign_responsible_vehicle.php?id=<?= urlencode($vehicle['id']) ?>"
                        style="display:inline-block;background:#27ae60;color:#fff;padding:0.7em 2em;border-radius:7px;font-size:1.08em;text-decoration:none;box-shadow:0 1px 4px rgba(39,174,96,0.10);font-weight:500;min-width:150px;min-height:48px;line-height:32px;text-align:center;">
                        Asignar responsable
                    </a>
                    <form method="post" action="../controllers/delete_device.php"
                        onsubmit="return confirm('¿Estás seguro de que deseas eliminar este equipo? Esta acción no se puede deshacer.');"
                        style="display:inline;">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($vehicle['id']) ?>">
                        <button type="submit"
                            style="background:#e74c3c;color:#fff;padding:0.7em 2em;border:none;border-radius:7px;font-size:1.08em;cursor:pointer;box-shadow:0 1px 4px rgba(231,76,60,0.10);font-weight:500;min-width:150px;min-height:48px;line-height:32px;text-align:center;">
                            Eliminar
                        </button>
                    </form>
                </div>
                <div class="profile-container">
                
                <table style="width:100%;margin-bottom:2rem;">
                    <tr>
                        <td style="width:120px;vertical-align:middle;">
                            <img src="../assets/images/logo-acema.webp" alt="Logo" style="height:60px;max-width:120px;">
                        </td>
                        <td style="text-align:center;vertical-align:middle;">
                            <div style="font-size:1.1rem; font-weight:bold; letter-spacing:1px;">
                                HOJA DE VIDA VEHÍCULOS		
                            </div>
                        </td>
                        <td style="text-align:center;vertical-align:middle;font-size:1rem;">
                            <div><strong>Código:</strong> ACM-SGI-FORMS-007</div>
                            <div><strong>Versión:</strong> 002</div>
                            <div><strong>Fecha:</strong> 09-06-2025</div>
                        </td>
                    </tr>
                </table>
                <hr style="margin: 0 0 2rem 0; border: none; border-top: 2px solid #e0e0e0;">
                <div class="profile-header">
                    <img class="profile-photo" src="<?= htmlspecialchars($photoUrl) ?>" alt="Foto vehículo">
                    <div>
                        
                        <div><strong>Placa:</strong> <?= htmlspecialchars($vehicle['placa']) ?></div>
                        <div><strong>Marca:</strong> <?= htmlspecialchars($vehicle['marca']) ?></div>
                        <div><strong>Modelo:</strong> <?= htmlspecialchars($vehicle['modelo']) ?></div>
                        <div><strong>Tipo de Vehículo:</strong> <?= htmlspecialchars($vehicle['tipo_vehiculo']) ?></div>
                        <div><strong>Servicio:</strong> <?= htmlspecialchars($vehicle['servicio']) ?></div>
                        <div><strong>Línea:</strong> <?= htmlspecialchars($vehicle['linea']) ?></div>
                        <div><strong>Color:</strong> <?= htmlspecialchars($vehicle['color']) ?></div>
                        <!-- <div style="margin-top:1em;">
                            <a class="action-link" href="vehicle_profile.php?id=<?= urlencode($vehicle['id']) ?>">Ver hoja de vida</a>
                        </div> -->
                    </div>
                </div>
                <hr style="margin: 2rem 0; border: none; border-top: 2px solid #e0e0e0;">
                <table class="profile-table">
                    <p><strong>ESPECIFICACIONES</strong></p>
                    <tr><th>Carrocería</th><td><?= !empty($vehicle['carroceria']) ? htmlspecialchars($vehicle['carroceria']) : 'Sin dato' ?></td></tr>
                    <tr><th>Capacidad</th><td><?= !empty($vehicle['capacidad']) ? htmlspecialchars($vehicle['capacidad']) : 'Sin dato' ?></td></tr>
                    <tr><th>Declaración de Importación</th><td><?= !empty($vehicle['declaracion_importacion']) ? htmlspecialchars($vehicle['declaracion_importacion']) : 'Sin dato' ?></td></tr>
                    <tr><th>Cilindraje</th><td><?= !empty($vehicle['cilindraje']) ? htmlspecialchars($vehicle['cilindraje']) : 'Sin dato' ?></td></tr>
                    <tr><th>Registro VIN</th><td><?= !empty($vehicle['registro_vin']) ? htmlspecialchars($vehicle['registro_vin']) : 'Sin dato' ?></td></tr>
                    <tr><th>Potencia HP</th><td><?= !empty($vehicle['potencia_hp']) ? htmlspecialchars($vehicle['potencia_hp']) : 'Sin dato' ?></td></tr>
                    <tr><th>Motor</th><td><?= !empty($vehicle['motor']) ? htmlspecialchars($vehicle['motor']) : 'Sin dato' ?></td></tr>
                    <tr><th>Número de Chasis</th><td><?= !empty($vehicle['numero_chasis']) ? htmlspecialchars($vehicle['numero_chasis']) : 'Sin dato' ?></td></tr>
                    <tr><th>No Matrícula</th><td><?= !empty($vehicle['no_matricula']) ? htmlspecialchars($vehicle['no_matricula']) : 'Sin dato' ?></td></tr>
                    <tr><th>Seguro Obligatorio</th><td><?= !empty($vehicle['seguro_obligatorio']) ? htmlspecialchars($vehicle['seguro_obligatorio']) : 'Sin dato' ?></td></tr>
                    <tr><th>Póliza de Seguro</th><td><?= !empty($vehicle['poliza_seguro']) ? htmlspecialchars($vehicle['poliza_seguro']) : 'Sin dato' ?></td></tr>
                    <tr><th>Técnico Mecánica</th><td><?= !empty($vehicle['tecnico_mecanica']) ? htmlspecialchars($vehicle['tecnico_mecanica']) : 'Sin dato' ?></td></tr>
                    <tr><th>Tarjeta de Operación</th><td><?= !empty($vehicle['tarjeta_operacion']) ? htmlspecialchars($vehicle['tarjeta_operacion']) : 'Sin dato' ?></td></tr>
                    <tr><th>Proveedor - Nombre/Razón Social</th><td><?= !empty($vehicle['proveedor_nombre']) ? htmlspecialchars($vehicle['proveedor_nombre']) : 'Sin dato' ?></td></tr>
                    <tr><th>Proveedor - NIT</th><td><?= !empty($vehicle['proveedor_nit']) ? htmlspecialchars($vehicle['proveedor_nit']) : 'Sin dato' ?></td></tr>
                    <tr><th>Proveedor - Representante Legal</th><td><?= !empty($vehicle['proveedor_representante']) ? htmlspecialchars($vehicle['proveedor_representante']) : 'Sin dato' ?></td></tr>
                    <tr><th>Proveedor - Dirección</th><td><?= !empty($vehicle['proveedor_direccion']) ? htmlspecialchars($vehicle['proveedor_direccion']) : 'Sin dato' ?></td></tr>
                    <tr><th>Proveedor - Teléfono</th><td><?= !empty($vehicle['proveedor_telefono']) ? htmlspecialchars($vehicle['proveedor_telefono']) : 'Sin dato' ?></td></tr>
                    <tr><th>Proveedor - E-mail</th><td><?= !empty($vehicle['proveedor_email']) ? htmlspecialchars($vehicle['proveedor_email']) : 'Sin dato' ?></td></tr>
                    <tr><th>Fecha SOAT</th>
    <td>
        <?php
        if (!empty($vehicle['fecha_soat']) && $vehicle['fecha_soat'] !== '0000-00-00') {
            echo htmlspecialchars($vehicle['fecha_soat']);
            $soat_vencimiento = date('Y-m-d', strtotime($vehicle['fecha_soat'] . ' +1 year'));
            $dias_restantes_soat = (strtotime($soat_vencimiento) - strtotime(date('Y-m-d'))) / 86400;
            echo "<br><span style='color:#2176ae;font-size:0.97em;'>Vence: $soat_vencimiento</span>";
            if ($dias_restantes_soat <= 30 && $dias_restantes_soat >= 0) {
                echo "<br><span style='color:#e67e22;font-weight:bold;'>¡SOAT por vencer en $dias_restantes_soat días!</span>";
            } elseif ($dias_restantes_soat < 0) {
                echo "<br><span style='color:#e74c3c;font-weight:bold;'>¡SOAT vencido!</span>";
            }
        } else {
            echo 'Sin dato';
        }
        ?>
    </td>
</tr>
<tr>
    <th>Fecha Tecnomecánica</th>
    <td>
        <?php
        if (!empty($vehicle['fecha_tecnomecanica']) && $vehicle['fecha_tecnomecanica'] !== '0000-00-00') {
            echo htmlspecialchars($vehicle['fecha_tecnomecanica']);
            $tecno_vencimiento = date('Y-m-d', strtotime($vehicle['fecha_tecnomecanica'] . ' +1 year'));
            $dias_restantes_tecno = (strtotime($tecno_vencimiento) - strtotime(date('Y-m-d'))) / 86400;
            echo "<br><span style='color:#2176ae;font-size:0.97em;'>Vence: $tecno_vencimiento</span>";
            if ($dias_restantes_tecno <= 30 && $dias_restantes_tecno >= 0) {
                echo "<br><span style='color:#e67e22;font-weight:bold;'>¡Tecnomecánica por vencer en $dias_restantes_tecno días!</span>";
            } elseif ($dias_restantes_tecno < 0) {
                echo "<br><span style='color:#e74c3c;font-weight:bold;'>¡Tecnomecánica vencida!</span>";
            }
        } else {
            echo 'Sin dato';
        }
        ?>
    </td>
</tr>
                </table>
                <!-- Formulario de mantenimiento -->
                <div style="margin:2.5rem 0 1.5rem 0;">
                    <label>
                        <input type="checkbox" class="mantenimiento-checkbox">
                        Registrar nuevo mantenimiento
                    </label>
                </div>
                <form class="mantenimiento-form" method="post" action="../controllers/register_maintenance.php" enctype="multipart/form-data" style="display:none;max-width:600px;margin:0 auto 2rem auto;padding:1.5rem 1rem;background:#f8fafc;border-radius:10px;border:1px solid #eaeaea;">
                    <input type="hidden" name="device_id" value="<?= htmlspecialchars($vehicle['id']) ?>">
                    <input type="hidden" name="tipo_equipo" value="vehicle">
                    <div style="margin-bottom:1em;">
                        <label>Fecha:</label>
                        <input type="date" name="date" required style="width:100%;padding:0.4em;border-radius:6px;border:1px solid #c7d0db;">
                    </div>
                    <div style="margin-bottom:1em;">
                        <label>Tipo de mantenimiento:</label>
                        <select name="type" required style="width:100%;padding:0.4em;border-radius:6px;border:1px solid #c7d0db;">
                            <option value="">Seleccione</option>
                            <option value="Correctivo">Correctivo</option>
                            <option value="Preventivo">Preventivo</option>
                            <option value="Otros">Otros</option>
                        </select>
                    </div>
                    <div style="margin-bottom:1em;">
                        <label>Descripción:</label>
                        <textarea name="description" rows="2" required style="width:100%;padding:0.4em;border-radius:6px;border:1px solid #c7d0db;resize:vertical;"></textarea>
                    </div>
                    <div style="margin-bottom:1em;">
                        <label>
                            <input type="checkbox" class="externo-checkbox" name="externo" value="1" onchange="toggleResponsableFields(this)">
                            Mantenimiento realizado por proveedor externo
                        </label>
                    </div>
                    <div class="responsable-ti" style="margin-bottom:1em;">
                        <label>Responsable:</label>
                        <input type="text" name="responsible" value="Área de TI" style="width:100%;padding:0.4em;border-radius:6px;border:1px solid #c7d0db;">
                        <img src="../assets/images/responsable.jpg" alt="Firma Área de TI" style="height:60px;display:block;margin:0 auto 0.5em auto;">
                        <div style="border-bottom:1px solid #888;margin-bottom:0.5em;"></div>
                    </div>
                    <div class="responsable-externo" style="margin-bottom:1em;display:none;">
                        <label>Nombre del responsable:</label>
                        <input type="text" name="proveedor_numero" style="width:100%;padding:0.4em;border-radius:6px;border:1px solid #c7d0db;">
                    </div>
                    <div style="margin-bottom:1em;">
                        <label>Foto del mantenimiento (opcional):</label>
                        <input type="file" name="foto_mantenimiento" accept="image/*">
                    </div>
                    <div style="text-align:center;margin-top:1.5em;">
                        <button type="submit" style="background:#2176ae;color:#fff;padding:0.7em 2em;border:none;border-radius:7px;font-size:1.08em;cursor:pointer;box-shadow:0 1px 4px rgba(33,118,174,0.10);">
                            Guardar mantenimiento
                        </button>
                    </div>
                </form>
                <!-- Historial de mantenimientos del vehículo -->
                <?php
                // Obtener mantenimientos de la tabla vehicle_maintenance
                $mnt_sql = "SELECT date, type, responsible, description, external, photo FROM vehicle_maintenance WHERE vehicle_id = ? ORDER BY date DESC";
                $mnt_stmt = $conn->prepare($mnt_sql);
                $mnt_stmt->bind_param("i", $vehicle['id']);
                $mnt_stmt->execute();
                $mnt_result = $mnt_stmt->get_result();
                $mantenimientos = [];
                while ($mnt = $mnt_result->fetch_assoc()) {
                    $mantenimientos[] = $mnt;
                }
                $mnt_stmt->close();
                ?>
                <?php if (!empty($mantenimientos)): ?>
                    <div style="max-width:800px;margin:2.5rem auto 0 auto;padding:2rem;background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(33,118,174,0.07);">
                        <h3 style="color:#2176ae;margin-bottom:1.2em;">Historial de Mantenimientos</h3>
                        <table style="width:100%;border-collapse:collapse;">
                            <thead>
                                <tr style="background:#f7f9fb;">
                                    <th style="padding:0.7em 0.5em;color:#215ba0;text-align:center;">Fecha</th>
                                    <th style="padding:0.7em 0.5em;color:#215ba0;text-align:center;">Tipo</th>
                                    <th style="padding:0.7em 0.5em;color:#215ba0;text-align:center;">Descripción</th>
                                    <th style="padding:0.7em 0.5em;color:#215ba0;text-align:center;">Foto/documento</th>
                                    <th style="padding:0.7em 0.5em;color:#215ba0;text-align:center;">Responsable</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mantenimientos as $mnt): ?>
                                <tr>
                                    <td style="padding:0.6em 0.5em; text-align: center; vertical-align:middle;"><?= htmlspecialchars($mnt['date']) ?></td>
                                    <td style="padding:0.6em 0.5em; text-align: center; vertical-align:middle;"><?= htmlspecialchars($mnt['type']) ?></td>
                                    <td style="padding:0.6em 0.5em; text-align: left; vertical-align:middle;"><?= nl2br(htmlspecialchars($mnt['description'])) ?></td>
                                    <td style="padding:0.6em 0.5em; text-align: center; vertical-align:middle;">
                                        <?php if (!empty($mnt['photo'])): ?>
                                            <a href="../<?= htmlspecialchars($mnt['photo']) ?>" target="_blank" style="display:inline-block;">
                                                <div style="font-size:0.85em;margin-top:0.3em;">Ver</div>
                                            </a>
                                        <?php else: ?>
                                            <span style="color:#bbb;font-size:0.95em;">Sin foto</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:0.6em 0.5em; text-align:center; vertical-align:middle;">
                                        <?php
                                            if (!empty($mnt['external'])) {
                                                echo '<span style="color:#2176ae;font-weight:bold;">' . htmlspecialchars($mnt['external']) . '</span>';
                                            } elseif (trim($mnt['responsible']) === 'Área de TI') {
                                                echo '<img src="../assets/images/responsable.jpg" alt="Firma Área de TI" style="height:40px;display:inline-block;vertical-align:middle;">';
                                            } else {
                                                echo htmlspecialchars($mnt['responsible']);
                                            }
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        document.querySelectorAll('.profile-container').forEach(function (container) {
                            var checkbox = container.querySelector('.mantenimiento-checkbox');
                            var form = container.querySelector('.mantenimiento-form');
                            if (checkbox && form) {
                                checkbox.addEventListener('change', function () {
                                    form.style.display = checkbox.checked ? 'block' : 'none';
                                });
                            }
                        });
                    });
                    function toggleResponsableFields(checkbox) {
                        var form = checkbox.closest('form');
                        var externo = checkbox.checked;
                        form.querySelector('.responsable-ti').style.display = externo ? 'none' : 'block';
                        form.querySelector('.responsable-externo').style.display = externo ? 'block' : 'none';
                    }
                </script>
            </div>
        <?php endwhile; ?>
        <?php endif; ?>
    </div>
</body>
</html>
