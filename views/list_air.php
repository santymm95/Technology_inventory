<?php
// Conexión a la base de datos (ajusta los datos de conexión según tu entorno)
$host = 'localhost';
$db = 'inventory';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
    exit;
}

$internal_number = isset($_GET['internal_number']) ? trim($_GET['internal_number']) : null;
$airs = [];

if ($internal_number) {
    $stmt = $pdo->prepare("SELECT * FROM air WHERE internal_number = ?");
    $stmt->execute([$internal_number]);
    $air = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($air) {
        $airs[] = $air;
    }
} else {
    // Opcional: puedes mostrar un mensaje o dejar vacío
    $airs = [];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Hojas de Vida de Aires Acondicionados</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background: #f4f6f9;
        }

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
            width: 180px;
            height: 140px;
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
            width: 220px;
            background: #f7f9fb;
        }

        .profile-table tr:last-child td {
            border-bottom: none;
        }

        .institution-header {
            width: 100%;
            margin-bottom: 2rem;
        }

        .institution-header td {
            vertical-align: middle;
        }
    </style>
</head>

<body>
    <?php if (count($airs) > 0): ?>
        <?php foreach ($airs as $air): ?>
            <?php include 'layout.php'; ?>
            <div class="profile-container">
                <!-- Encabezado institucional -->
                <table class="institution-header">
                    <tr>
                        <td style="width:120px;">
                            <img src="../assets/images/logo-acema.webp" alt="Logo" style="height:60px;max-width:120px;">
                        </td>
                        <td style="text-align:center;">
                            <div style="font-size:1.1rem; font-weight:bold; letter-spacing:1px;">
                                HOJA DE VIDA EQUIPOS DE AIRE ACONDICIONADO
                            </div>
                        </td>
                        <td style="text-align:center;font-size:1rem;">
                            <div><strong>Código:</strong> ACM-TI-FORMS-003</div>
                            <div><strong>Versión:</strong> 002</div>
                            <div><strong>Fecha:</strong> 09-06-2025</div>
                        </td>
                    </tr>
                </table>
                <hr style="margin: 0 0 2rem 0; border: none; border-top: 2px solid #e0e0e0;">
                <!-- Datos principales -->
                <div class="profile-header">
                    <?php
                    // Buscar imagen dinámica en la carpeta del número interno (pdf, jpg, jpeg, png, webp)
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
                    // Si es PDF, mostrar un ícono o enlace, si es imagen mostrar la imagen
                    $is_pdf = strtolower(pathinfo($img_path, PATHINFO_EXTENSION)) === 'pdf';
                    ?>
                    <?php if ($is_pdf): ?>
                        <a href="<?= htmlspecialchars($img_path) ?>" target="_blank" style="display:inline-block;">
                            <img class="profile-photo" src="../assets/images/pdf-icon.png" alt="Ver PDF"
                                style="object-fit:contain;">
                            <div>Ver documento</div>
                        </a>
                    <?php else: ?>
                        <img class="profile-photo" src="<?= htmlspecialchars($img_path) ?>" alt="Foto aire">
                    <?php endif; ?>
                    <div>
                         
                        <div><strong>Marca:</strong> <?= htmlspecialchars($air['brand']) ?></div>
                        <div><strong>Número Interno:</strong> <?= htmlspecialchars($air['internal_number']) ?></div>
                        <div><strong>Serial:</strong> <?= htmlspecialchars($air['serial']) ?></div>
                        <div><strong>Modelo:</strong> <?= htmlspecialchars($air['model']) ?></div>
                        <div><strong>Ubicación:</strong> <?= htmlspecialchars($air['location']) ?></div>
                        <div><strong>Fecha de Compra:</strong> <?= htmlspecialchars($air['purchase_date']) ?></div>
                        <div><strong>Proveedor:</strong> <?= htmlspecialchars($air['provider']) ?></div>
                    </div>
                </div>
                <hr style="margin: 2rem 0; border: none; border-top: 2px solid #e0e0e0;">
                <!-- Especificaciones -->
                <p><strong>ESPECIFICACIONES</strong></p>
                <table class="profile-table">
                    <tr>
                        <th>Capacidad</th>
                        <td><?= htmlspecialchars($air['capacity']) ?></td>
                    </tr>
                    <tr>
                        <th>Voltaje</th>
                        <td><?= htmlspecialchars($air['voltage']) ?></td>
                    </tr>
                    <tr>
                        <th>Refrigerante</th>
                        <td><?= htmlspecialchars($air['refrigerant']) ?></td>
                    </tr>
                    <!-- <tr>
                    <th>Especificaciones adicionales</th>
                    <td><?= htmlspecialchars($air['specs']) ?></td>
                </tr> -->
                </table>
                <hr style="margin: 2rem 0; border: none; border-top: 2px solid #e0e0e0;">
                <!-- Formulario de mantenimiento único por aire -->
                <div style="margin:2.5rem 0 1.5rem 0;">
                    <label>
                        <input type="checkbox" id="mantenimiento-checkbox-<?= $air['id'] ?>">
                        Registrar nuevo mantenimiento
                    </label>
                </div>
                <form id="mantenimiento-form-<?= $air['id'] ?>" method="post"
                    action="../controllers/register_air_maintenance.php" enctype="multipart/form-data"
                    style="display:none;max-width:600px;margin:0 auto 2rem auto;padding:1.5rem 1rem;background:#f8fafc;border-radius:10px;border:1px solid #eaeaea;">
                    <input type="hidden" name="id" value="<?= intval($air['id']) ?>">
                    <input type="hidden" name="internal_number" value="<?= htmlspecialchars($air['internal_number']) ?>">
                    <div style="margin-bottom:1em;">
                        <label>Fecha:</label>
                        <input type="date" name="date" required
                            style="width:100%;padding:0.4em;border-radius:6px;border:1px solid #c7d0db;">
                    </div>
                    <div style="margin-bottom:1em;">
                        <label>Tipo de mantenimiento:</label>
                        <select name="type" required
                            style="width:100%;padding:0.4em;border-radius:6px;border:1px solid #c7d0db;">
                            <option value="">Seleccione</option>
                            <option value="Correctivo">Correctivo</option>
                            <option value="Preventivo">Preventivo</option>
                            <option value="Otros">Otros</option>
                        </select>
                    </div>
                    <div style="margin-bottom:1em;">
                        <label>Descripción:</label>
                        <textarea name="description" rows="2" required
                            style="width:100%;padding:0.4em;border-radius:6px;border:1px solid #c7d0db;resize:vertical;"></textarea>
                    </div>
                    <div style="margin-bottom:1em;">
                        <label>
                            <input type="checkbox" id="externo-checkbox-<?= $air['id'] ?>" name="externo" value="1"
                                onchange="toggleResponsableFields<?= $air['id'] ?>()">
                            Mantenimiento realizado por proveedor externo
                        </label>
                    </div>
                    <div id="responsable-ti-<?= $air['id'] ?>" style="margin-bottom:1em;">
                        <label>Responsable:</label>
                        <input type="hidden" name="responsible" value="Área de TI">
                        <img src="../assets/images/responsable.jpg" alt="Firma Área de TI"
                            style="height:60px;display:block;margin:0 auto 0.5em auto;">
                        <div style="border-bottom:1px solid #888;margin-bottom:0.5em;"></div>
                    </div>
                    <div id="responsable-externo-<?= $air['id'] ?>" style="margin-bottom:1em;display:none;">
                        <label>Nombre del responsable:</label>
                        <input type="text" name="proveedor_numero"
                            style="width:100%;padding:0.4em;border-radius:6px;border:1px solid #c7d0db;">
                    </div>
                    <div style="margin-bottom:1em;">
                        <label>Foto del mantenimiento (opcional):</label>
                        <input type="file" name="foto_mantenimiento" accept="image/*">
                    </div>
                    <div style="text-align:center;margin-top:1.5em;">
                        <button type="submit"
                            style="background:#2176ae;color:#fff;padding:0.7em 2em;border:none;border-radius:7px;font-size:1.08em;cursor:pointer;box-shadow:0 1px 4px rgba(33,118,174,0.10);">
                            Guardar mantenimiento
                        </button>
                    </div>
                </form>
                <script>
                    // Mostrar/ocultar formulario de mantenimiento para este aire
                    document.getElementById('mantenimiento-checkbox-<?= $air['id'] ?>').addEventListener('change', function () {
                        document.getElementById('mantenimiento-form-<?= $air['id'] ?>').style.display = this.checked ? 'block' : 'none';
                    });
                    function toggleResponsableFields<?= $air['id'] ?>() {
                        var externo = document.getElementById('externo-checkbox-<?= $air['id'] ?>').checked;
                        document.getElementById('responsable-ti-<?= $air['id'] ?>').style.display = externo ? 'none' : 'block';
                        document.getElementById('responsable-externo-<?= $air['id'] ?>').style.display = externo ? 'block' : 'none';
                    }
                </script>
                <!-- Historial de mantenimientos -->
                <?php
                // Obtener historial de mantenimientos para este aire (incluyendo la ruta de imagen)
                $stmtMnt = $pdo->prepare("SELECT date, type, description, responsible, external FROM air_maintenance WHERE air_id = ? ORDER BY date DESC");
                $stmtMnt->execute([$air['id']]);
                $mantenimientos = $stmtMnt->fetchAll(PDO::FETCH_ASSOC);

                // Ruta base para imágenes de mantenimiento
                $mnt_img_base = "../uploads/mantenimiento/" . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $air['internal_number']) . "/";
                ?>
                <?php if (!empty($mantenimientos)): ?>
                    <div
                        style="margin:2.5rem 0 0 0;padding:2rem;background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(33,118,174,0.07);">
                        <h3 style="color:#2176ae;margin-bottom:1.2em;">Historial de Mantenimientos</h3>
                        <table style="width:100%;border-collapse:collapse;">
                            <thead>
                                <tr style="background:#f7f9fb;">
                                    <th style="padding:0.7em 0.5em;color:#215ba0;text-align:center;">Fecha</th>
                                    <th style="padding:0.7em 0.5em;color:#215ba0;text-align:center;">Tipo</th>
                                    <th style="padding:0.7em 0.5em;color:#215ba0;text-align:center;">Descripción</th>
                                    <th style="padding:0.7em 0.5em;color:#215ba0;text-align:center;">Responsable</th>
                                    <th style="padding:0.7em 0.5em;color:#215ba0;text-align:center;">Imagen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mantenimientos as $mnt): ?>
                                    <tr>
                                        <td style="padding:0.6em 0.5em; text-align: center; vertical-align:middle;">
                                            <?= htmlspecialchars($mnt['date']) ?></td>
                                        <td style="padding:0.6em 0.5em; text-align: center; vertical-align:middle;">
                                            <?= htmlspecialchars($mnt['type']) ?></td>
                                        <td style="padding:0.6em 0.5em; text-align: left; vertical-align:middle;">
                                            <?= nl2br(htmlspecialchars($mnt['description'])) ?></td>
                                        <td style="padding:0.6em 0.5em; text-align: center; vertical-align:middle;">
                                            <?php
                                            if (!empty($mnt['external'])) {
                                                echo '<span style="color:#2176ae;font-weight:bold;">' . htmlspecialchars($mnt['external']) . '</span>';
                                            } elseif (!empty($mnt['responsible'])) {
                                                echo htmlspecialchars($mnt['responsible']);
                                            } else {
                                                echo '<span style="color:#bbb;">-</span>';
                                            }
                                            ?>
                                        </td>
                                        <td style="padding:0.6em 0.5em; text-align: center; vertical-align:middle;">
                                            <?php
                                            // Buscar imagen de mantenimiento por fecha y extensiones comunes
                                            $img_found = false;
                                            $img_exts = ['jpg', 'jpeg', 'png', 'webp'];
                                            foreach ($img_exts as $ext) {
                                                $img_path = $mnt_img_base . $mnt['date'] . "." . $ext;
                                                if (file_exists($img_path)) {
                                                    echo "<a href='" . htmlspecialchars($img_path) . "' target='_blank'>Ver</a>";
                                                    $img_found = true;
                                                    break;
                                                }
                                            }
                                            if (!$img_found) {
                                                echo "<span style='color:#bbb;'>-</span>";
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
        <?php endforeach; ?>
    <?php else: ?>
        <div class="container">
            <div class="alert alert-warning mt-5">No se encontró la hoja de vida del aire solicitado.</div>
        </div>
    <?php endif; ?>
</body>

</html>