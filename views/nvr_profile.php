<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}
include_once __DIR__ . '/../includes/conection.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo "NVR not found.";
    exit;
}

// Get NVR info
$sql = "SELECT * FROM nvr_devices WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$nvr = $res->fetch_assoc();
$stmt->close();

if (!$nvr) {
    echo "NVR not found.";
    exit;
}

// Find photo in uploads/nvr/{internal_number}/photo.jpg
$photoDir = "../uploads/nvr/" . $nvr['internal_number'] . "/";
$photoPath = $photoDir . "photo.jpg";
if (!file_exists($photoPath)) {
    $photoPath = "../assets/img/no-image.png";
}
$partsImgPath = $photoDir . "parts.jpg";
if (!file_exists($partsImgPath)) {
    $partsImgPath = "";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>NVR Profile</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 2rem auto;
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
            height: 160px;
            object-fit: contain;
            border-radius: 10px;
            background: #f2f6fa;
            border: 1px solid #e0e0e0;
        }
        .profile-title {
            font-size: 2rem;
            color: #2176ae;
            margin-bottom: 0.5rem;
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
    </style>
</head>
<body>
<?php include 'layout.php'; ?>
<div class="main-content">
    <div class="profile-container">
        <table style="width:100%;margin-bottom:2rem;">
            <tr>
                <td style="width:120px;vertical-align:middle;">
                    <img src="../assets/images/logo-acema.webp" alt="Logo" style="height:60px;max-width:120px;">
                </td>
                <td style="text-align:center;vertical-align:middle;">
                    <div style="font-size:1.1rem; font-weight:bold; letter-spacing:1px;">
                        HOJA DE VIDA NVR
                    </div>
                </td>
                <td style="text-align:center;vertical-align:middle;font-size:1rem;">
                    <div><strong>Código:</strong> ACM-ADM-TI-FO-008</div>
                    <div><strong>Versión:</strong> 001</div>
                    <div><strong>Fecha:</strong> 16-09-2024</div>
                </td>
            </tr>
        </table>
        <hr style="margin: 0 0 2rem 0; border: none; border-top: 2px solid #e0e0e0;">
        <div class="profile-header">
            <img class="profile-photo" src="<?= htmlspecialchars($photoPath) ?>" alt="NVR Photo">
            <div>
                <div><strong>Brand:</strong> <?= htmlspecialchars($nvr['brand']) ?></div>
                <div><strong>Internal Number:</strong> <?= htmlspecialchars($nvr['internal_number']) ?></div>
                <div><strong>Serial:</strong> <?= htmlspecialchars($nvr['serial']) ?></div>
                <div><strong>Model:</strong> <?= htmlspecialchars($nvr['model']) ?></div>
                <div><strong>Purchase Date:</strong> <?= htmlspecialchars($nvr['purchase_date']) ?></div>
                <div><strong>Supplier:</strong> <?= htmlspecialchars($nvr['supplier']) ?></div>
            </div>
        </div>
        <hr style="margin: 2rem 0; border: none; border-top: 2px solid #e0e0e0;">
        <table class="profile-table">
            <p><strong>TECHNICAL SPECIFICATIONS</strong></p>
            <tr>
                <th>Type</th>
                <td><?= htmlspecialchars($nvr['type']) ?></td>
            </tr>
            <tr>
                <th>Decoding</th>
                <td><?= htmlspecialchars($nvr['decoding']) ?></td>
            </tr>
            <tr>
                <th>Inputs</th>
                <td><?= htmlspecialchars($nvr['inputs']) ?></td>
            </tr>
            <tr>
                <th>Connectivity</th>
                <td><?= htmlspecialchars($nvr['connectivity']) ?></td>
            </tr>
            <tr>
                <th>Storage</th>
                <td><?= htmlspecialchars($nvr['storage']) ?></td>
            </tr>
            <tr>
                <th>Transmission</th>
                <td><?= htmlspecialchars($nvr['transmission']) ?></td>
            </tr>
            <tr>
                <th>Usage</th>
                <td><?= htmlspecialchars($nvr['usage']) ?></td>
            </tr>
        </table>
        <?php if (!empty($nvr['parts_description'])): ?>
            <p style="padding-top:1rem;"><strong>PARTS DESCRIPTION:</strong></p>
            <div style="margin-top:0.7em;"><?= nl2br(htmlspecialchars($nvr['parts_description'])) ?></div>
        <?php endif; ?>
        <?php if ($partsImgPath): ?>
            <div style="margin-top:2em;">
                <strong>Parts Image:</strong><br>
                <img src="<?= htmlspecialchars($partsImgPath) ?>" alt="Parts Image" style="max-width:220px;max-height:180px;border-radius:8px;border:1px solid #e0e0e0;background:#fafbfc;">
            </div>
        <?php endif; ?>

        <!-- INICIO: Check y formulario de mantenimiento dentro de la hoja de vida -->
        <div style="margin:2.5rem 0 1.5rem 0;">
            <label>
                <input type="checkbox" id="mantenimiento-checkbox-final">
                Registrar nuevo mantenimiento
            </label>
        </div>
        <form id="mantenimiento-form" method="post" action="../controllers/nvr_maintenance_controller.php" enctype="multipart/form-data" style="display:none;max-width:600px;margin:0 auto 2rem auto;padding:1.5rem 1rem;background:#f8fafc;border-radius:10px;border:1px solid #eaeaea;">
            <input type="hidden" name="device_id" value="<?= htmlspecialchars($id) ?>">
            <input type="hidden" name="internal_number" value="<?= htmlspecialchars($nvr['internal_number']) ?>">
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
                    <input type="checkbox" id="externo-checkbox" name="externo" value="1" onchange="toggleResponsableFields()">
                    Mantenimiento realizado por proveedor externo
                </label>
            </div>
            <div id="responsable-ti" style="margin-bottom:1em;">
                <label>Responsable:</label>
                <input type="hidden" name="responsible" value="Área de TI">
                <img src="../assets/images/responsable.jpg" alt="Firma Área de TI" style="height:60px;display:block;margin:0 auto 0.5em auto;">
                <div style="border-bottom:1px solid #888;margin-bottom:0.5em;"></div>
            </div>
            <div id="responsable-externo" style="margin-bottom:1em;display:none;">
                <label>Nombre del responsable:</label>
                <input type="text" name="proveedor_numero" style="width:100%;padding:0.4em;border-radius:6px;border:1px solid #c7d0db;">
            </div>
            <div style="margin-bottom:1em;">
                <label>Foto del mantenimiento (opcional):</label>
                <input type="file" name="foto_mantenimiento" accept="image/*">
                <div style="font-size:0.92em;color:#888;margin-top:0.3em;">
                    La foto se guardará en el servidor.
                </div>
            </div>
            <div style="text-align:center;margin-top:1.5em;">
                <button type="submit" style="background:#2176ae;color:#fff;padding:0.7em 2em;border:none;border-radius:7px;font-size:1.08em;cursor:pointer;box-shadow:0 1px 4px rgba(33,118,174,0.10);">
                    Guardar mantenimiento
                </button>
            </div>
        </form>
        <script>
            document.getElementById('mantenimiento-checkbox-final').addEventListener('change', function () {
                document.getElementById('mantenimiento-form').style.display = this.checked ? 'block' : 'none';
            });
            function toggleResponsableFields() {
                var externo = document.getElementById('externo-checkbox').checked;
                document.getElementById('responsable-ti').style.display = externo ? 'none' : 'block';
                document.getElementById('responsable-externo').style.display = externo ? 'block' : 'none';
            }
        </script>
        <!-- FIN: Check y formulario de mantenimiento dentro de la hoja de vida -->

    </div> <!-- cierre de .profile-container -->

    <?php
    // Obtener historial de mantenimientos del NVR
    $mnt_sql = "SELECT date, type, description, responsible, external, photo FROM nvr_maintenance WHERE nvr_id = ? ORDER BY date DESC";
    $mnt_stmt = $conn->prepare($mnt_sql);
    $mnt_stmt->bind_param("i", $id);
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
                        <th style="padding:0.7em 0.5em;color:#215ba0;text-align:center;">Realizado por</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mantenimientos as $mnt): ?>
                    <tr>
                        <td style="padding:0.6em 0.5em; text-align:center; vertical-align:middle;"><?= htmlspecialchars($mnt['date']) ?></td>
                        <td style="padding:0.6em 0.5em; text-align:center; vertical-align:middle;"><?= htmlspecialchars($mnt['type']) ?></td>
                        <td style="padding:0.6em 0.5em; text-align:left; vertical-align:middle;"><?= nl2br(htmlspecialchars($mnt['description'])) ?></td>
                        <td style="padding:0.6em 0.5em; text-align:center; vertical-align:middle;">
                            <?php if (!empty($mnt['photo']) && file_exists($mnt['photo'])): ?>
                                <a href="<?= htmlspecialchars($mnt['photo']) ?>" target="_blank" style="display:inline-block;">
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
                        <td style="padding:0.6em 0.5em; text-align:center; vertical-align:middle;">
                            <?php
                                if (!empty($mnt['external'])) {
                                    echo '<span style="color:#e67e22;font-weight:bold;">Externo</span>';
                                } else {
                                    echo '<span style="color:#27ae60;font-weight:bold;">Interno</span>';
                                }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</body>
</html>