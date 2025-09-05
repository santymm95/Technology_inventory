<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
include_once __DIR__ . '/../includes/db.php';

$result = $conn->query("SELECT * FROM printer ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Impresoras</title>
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
            width: 160px;
            height: 120px;
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
            width: 200px;
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
        
        <?php while ($printer = $result->fetch_assoc()): ?>
            <div class="profile-container">
                <table style="width:100%;margin-bottom:2rem;">
                    <tr>
                        <td style="width:120px;vertical-align:middle;">
                            <img src="../assets/images/logo-acema.webp" alt="Logo" style="height:60px;max-width:120px;">
                        </td>
                        <td style="text-align:center;vertical-align:middle;">
                            <div style="font-size:1.1rem; font-weight:bold; letter-spacing:1px;">
                                HOJA DE VIDA - IMPRESORA MULTIUSOS
                            </div>
                        </td>
                        <td style="text-align:center;vertical-align:middle;font-size:1rem;">
                            <div><strong>Código:</strong> ACM-TI-FORMS-004</div>
                            <div><strong>Versión:</strong> 002</div>
                            <div><strong>Fecha:</strong> 09-06-2025</div>
                        </td>
                    </tr>
                </table>
                <hr style="margin: 0 0 2rem 0; border: none; border-top: 2px solid #e0e0e0;">
                <div class="profile-header">
                    <img class="profile-photo" src="../<?= htmlspecialchars($printer['photo']) ?>" alt="Foto impresora">
                    <div>
                        <div><strong>Marca:</strong> <?= htmlspecialchars($printer['brand']) ?></div>
                        <div><strong>Número Interno:</strong> <?= htmlspecialchars($printer['internal_number']) ?></div>
                        <div><strong>Serial:</strong> <?= htmlspecialchars($printer['serial']) ?></div>
                        <div><strong>Modelo:</strong> <?= htmlspecialchars($printer['model']) ?></div>
                        <div><strong>Fecha de Compra:</strong> <?= htmlspecialchars($printer['purchase_date']) ?></div>
                        <div><strong>Tipo de equipo:</strong> <?= htmlspecialchars($printer['device_type']) ?></div>
                       
                    </div>
                </div>
                <table class="profile-table">
                    <tr><th>Proveedor</th><td><?= htmlspecialchars($printer['provider']) ?></td></tr>
                    <tr><th>Nombre del host</th><td><?= htmlspecialchars($printer['host_name']) ?></td></tr>
                    <tr><th>Duplex</th><td><?= htmlspecialchars($printer['duplex']) ?></td></tr>
                    <tr><th>Conectividad</th><td><?= htmlspecialchars($printer['connectivity']) ?></td></tr>
                    <tr><th>Panel frontal</th><td><?= htmlspecialchars($printer['front_panel']) ?></td></tr>
                    <tr><th>Tipo de filtrado</th><td><?= htmlspecialchars($printer['filter_type']) ?></td></tr>
                    <tr><th>Velocidad de impresión</th><td><?= htmlspecialchars($printer['print_speed']) ?></td></tr>
                    <tr><th>URL IP</th><td><?= htmlspecialchars($printer['ip_url']) ?></td></tr>
                    <tr><th>Voltaje eléctrico</th><td><?= htmlspecialchars($printer['voltage']) ?></td></tr>
                    <tr><th>Cartuchos de tinta</th><td><?= htmlspecialchars($printer['ink_cartridges']) ?></td></tr>
                    <tr><th>Partes del equipo</th><td><?= htmlspecialchars($printer['parts']) ?></td></tr>
                    <tr><th>Descripción de las partes</th><td><?= nl2br(htmlspecialchars($printer['parts_desc'])) ?></td></tr>
                </table>
                <!-- Formulario de mantenimiento (igual a device_profile.php) -->
                <div style="margin:2.5rem 0 1.5rem 0;">
                    <label>
                        <input type="checkbox" class="mantenimiento-checkbox">
                        Registrar nuevo mantenimiento
                    </label>
                </div>
                <form class="mantenimiento-form" method="post" action="../controllers/register_printer_maintenance.php" enctype="multipart/form-data" style="display:none;max-width:600px;margin:0 auto 2rem auto;padding:1.5rem 1rem;background:#f8fafc;border-radius:10px;border:1px solid #eaeaea;">
                    <input type="hidden" name="device_id" value="<?= htmlspecialchars($printer['id']) ?>">
                    <input type="hidden" name="tipo_equipo" value="printer">
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
                        <input type="hidden" name="responsible" value="Área de TI">
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
                <!-- Historial de mantenimientos de la impresora -->
                <?php
                // Obtener mantenimientos de la impresora desde printer_maintenance
                $mnt_sql = "SELECT date, type, responsible, description, external, photo FROM printer_maintenance WHERE printer_id = ? ORDER BY date DESC";
                $mnt_stmt = $conn->prepare($mnt_sql);
                $mnt_stmt->bind_param("i", $printer['id']);
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
                    // Mostrar/ocultar formulario de mantenimiento para cada impresora
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

                    // Mostrar/ocultar responsable externo/interno para cada formulario
                    function toggleResponsableFields(checkbox) {
                        var form = checkbox.closest('form');
                        var externo = checkbox.checked;
                        form.querySelector('.responsable-ti').style.display = externo ? 'none' : 'block';
                        form.querySelector('.responsable-externo').style.display = externo ? 'block' : 'none';
                    }
                </script>
            </div>
        <?php endwhile; ?>
    </div>
</body>
</html>
