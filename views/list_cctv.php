<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
require_once '../includes/db.php';

$internal_number = isset($_GET['internal_number']) ? trim($_GET['internal_number']) : null;
$cctv = null;

if ($internal_number) {
    $stmt = $conn->prepare("SELECT * FROM cctv WHERE internal_number = ?");
    $stmt->bind_param("s", $internal_number);
    $stmt->execute();
    $result = $stmt->get_result();
    $cctv = $result->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Hoja de Vida CCTV</title>
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

        .btn-back {
            display: inline-block;
            padding: 5px 10px;
            background-color: #215ba0;
            color: white;
            text-decoration: none;
            border: none;
            margin-bottom: 15px;
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

        /* Mantenimiento */
        .mnt-form-container {
            margin: 2.5rem 0 1.5rem 0;
        }

        .mnt-form-box {
            display: none;
            max-width: 600px;
            margin: 0 auto 2rem auto;
            padding: 1.5rem 1rem;
            background: #f8fafc;
            border-radius: 10px;
            border: 1px solid #eaeaea;
        }

        .mnt-table th,
        .mnt-table td {
            padding: 0.6em 0.5em;
            text-align: center;
            vertical-align: middle;
        }

        .mnt-table th {
            background: #f7f9fb;
            color: #215ba0;
        }
    </style>
</head>

<body>
    <?php include 'layout.php'; ?>
    <?php if ($cctv): ?>
        
        <div class="profile-container">
        
            <!-- Encabezado institucional -->
            <table class="institution-header">
                <tr>
                    <td style="width:120px;">
                        <img src="../assets/images/logo-acema.webp" alt="Logo" style="height:60px;max-width:120px;">
                    </td>
                    <td style="text-align:center;">
                        <div style="font-size:1.1rem; font-weight:bold; letter-spacing:1px;">
                            HOJA DE VIDA SISTEMAS CCTV
                        </div>
                    </td>
                    <td style="text-align:center;font-size:1rem;">
                        <div><strong>Código:</strong> ACM-TI-FORMS-007</div>
                        <div><strong>Versión:</strong> 002</div>
                        <div><strong>Fecha:</strong> 09-06-2025</div>
                    </td>
                </tr>
            </table>
            <hr style="margin: 0 0 2rem 0; border: none; border-top: 2px solid #e0e0e0;">
            <!-- Datos principales -->
            <div class="profile-header">
                <?php
                $photo_path = !empty($cctv['photo']) && file_exists("../uploads/" . $cctv['photo'])
                    ? "../uploads/" . $cctv['photo']
                    : "../assets/img/no-image.png";
                ?>
                <img class="profile-photo" src="<?= htmlspecialchars($photo_path) ?>" alt="Foto CCTV">
                <div>
                   
                    <div><strong>Marca:</strong> <?= htmlspecialchars($cctv['brand']) ?></div>
                     <div>   <strong>Número Interno </strong><?= htmlspecialchars($cctv['internal_number']) ?></div>
                    <div><strong>Modelo:</strong> <?= htmlspecialchars($cctv['model']) ?></div>
                    <div><strong>Serial:</strong> <?= htmlspecialchars($cctv['serial']) ?></div>
                    <div><strong>Ubicación:</strong> <?= htmlspecialchars($cctv['ubicacion']) ?></div>
                    <div><strong>Fecha de Compra:</strong> <?= htmlspecialchars($cctv['purchase_date']) ?></div>
                    <div><strong>Proveedor:</strong> <?= htmlspecialchars($cctv['provider']) ?></div>
                </div>
            </div>
            <hr style="margin: 2rem 0; border: none; border-top: 2px solid #e0e0e0;">
            <!-- Especificaciones -->
            <p><strong>ESPECIFICACIONES</strong></p>
            <table class="profile-table">
                <tr>
                    <th>Tipo</th>
                    <td><?= htmlspecialchars($cctv['type']) ?></td>
                </tr>
                <tr>
                    <th>Resolución</th>
                    <td><?= htmlspecialchars($cctv['resolucion']) ?></td>
                </tr>
                <tr>
                    <th>Píxeles</th>
                    <td><?= htmlspecialchars($cctv['pixeles']) ?></td>
                </tr>
                <tr>
                    <th>Conectividad</th>
                    <td><?= htmlspecialchars($cctv['conectividad']) ?></td>
                </tr>
                <tr>
                    <th>Sensor de movimiento</th>
                    <td><?= htmlspecialchars($cctv['sensor_movimiento']) ?></td>
                </tr>
                <tr>
                    <th>Fecha de registro</th>
                    <td><?= htmlspecialchars($cctv['created_at']) ?></td>
                </tr>
            </table>

            <!-- Checkbox y formulario de mantenimiento -->
            <div class="mnt-form-container">
                <label>
                    <input type="checkbox" id="mnt-checkbox">
                    Registrar nuevo mantenimiento
                </label>
            </div>
            <form id="mnt-form" class="mnt-form-box" method="post" action="../controllers/register_cctv_maintenance.php"
                enctype="multipart/form-data">
                <input type="hidden" name="cctv_id" value="<?= intval($cctv['id']) ?>">
                <input type="hidden" name="internal_number" value="<?= htmlspecialchars($cctv['internal_number']) ?>">
                <div style="margin-bottom:1em;">
                    <label>Fecha:</label>
                    <input type="date" name="date" required class="form-control">
                </div>
                <div style="margin-bottom:1em;">
                    <label>Tipo de mantenimiento:</label>
                    <select name="type" required class="form-control">
                        <option value="">Seleccione</option>
                        <option value="Correctivo">Correctivo</option>
                        <option value="Preventivo">Preventivo</option>
                        <option value="Otros">Otros</option>
                    </select>
                </div>
                <div style="margin-bottom:1em;">
                    <label>Descripción:</label>
                    <textarea name="description" rows="2" required class="form-control"></textarea>
                </div>
                <div style="margin-bottom:1em;">
                    <label>
                        <input type="checkbox" class="externo-checkbox" name="externo" value="1"
                            onchange="toggleResponsableFields(this)">
                        Mantenimiento realizado por proveedor externo
                    </label>
                </div>
                <div class="responsable-ti" style="margin-bottom:1em;">
                    <label>Responsable:</label>
                    <input type="hidden" name="responsible" value="Área de TI">
                    <img src="../assets/images/responsable.jpg" alt="Firma Área de TI"
                        style="height:60px;display:block;margin:0 auto 0.5em auto;">
                    <div style="border-bottom:1px solid #888;margin-bottom:0.5em;"></div>
                </div>
                <div class="responsable-externo" style="margin-bottom:1em;display:none;">
                    <label>Nombre del responsable:</label>
                    <input type="text" name="proveedor_numero"
                        style="width:100%;padding:0.4em;border-radius:6px;border:1px solid #c7d0db;">
                </div>
                <div style="margin-bottom:1em;">
                    <label>Foto del mantenimiento (opcional):</label>
                    <input type="file" name="photo" accept="image/*" class="form-control">
                </div>
                <div style="text-align:center;margin-top:1.5em;">
                    <button type="submit" class="btn btn-primary">
                        Guardar mantenimiento
                    </button>
                </div>
            </form>
            <script>
                document.getElementById('mnt-checkbox').addEventListener('change', function () {
                    document.getElementById('mnt-form').style.display = this.checked ? 'block' : 'none';
                });
                function toggleResponsableFields(checkbox) {
                    var form = checkbox.closest('form');
                    var externo = checkbox.checked;
                    form.querySelector('.responsable-ti').style.display = externo ? 'none' : 'block';
                    form.querySelector('.responsable-externo').style.display = externo ? 'block' : 'none';
                }
            </script>

            <!-- Historial de mantenimientos -->
            <?php
            // Obtener historial de mantenimientos para este CCTV
            $stmtMnt = $conn->prepare("SELECT date, type, description, responsible, photo FROM cctv_maintenance WHERE cctv_id = ? ORDER BY date DESC");
            $stmtMnt->bind_param("i", $cctv['id']);
            $stmtMnt->execute();
            $mnt_result = $stmtMnt->get_result();
            $mantenimientos = $mnt_result->fetch_all(MYSQLI_ASSOC);
            $stmtMnt->close();
            ?>
            <?php if (!empty($mantenimientos)): ?>
                <div
                    style="margin:2.5rem 0 0 0;padding:2rem;background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(33,118,174,0.07);">
                    <h3 style="color:#2176ae;margin-bottom:1.2em;">Historial de Mantenimientos</h3>
                    <table class="table mnt-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Descripción</th>
                                <th>Imagen</th>
                                <th>Responsable</th>

                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mantenimientos as $mnt): ?>
                                <tr>
                                    <td><?= htmlspecialchars($mnt['date']) ?></td>
                                    <td><?= htmlspecialchars($mnt['type']) ?></td>
                                    <td style="text-align:left;"><?= nl2br(htmlspecialchars($mnt['description'])) ?></td>
                                    <td>
                                        <?php
                                        $mnt_photo_path = "../uploads/mantenimiento/" . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $cctv['internal_number']) . "/";
                                        if (!empty($mnt['photo']) && file_exists($mnt_photo_path . $mnt['photo'])) {
                                            echo "<a href='" . $mnt_photo_path . htmlspecialchars($mnt['photo']) . "' target='_blank'>Ver</a>";
                                        } else {
                                            echo "<span style='color:#bbb;'>-</span>";
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        if (!empty($mnt['external'])) {
                                            // Proveedor externo
                                            echo '<span style="color:#2176ae;font-weight:bold;">' . htmlspecialchars($mnt['external']) . '</span>';
                                        } elseif (
                                            strtolower(trim($mnt['responsible'])) === 'área de ti' ||
                                            strtolower(trim($mnt['responsible'])) === 'area de ti' ||
                                            strtolower(trim($mnt['responsible'])) === 'interno' // <-- agrega esta condición
                                        ) {
                                            echo htmlspecialchars($mnt['responsible']);
                                            echo '<br><img src="../assets/images/responsable.jpg" alt="Firma Área de TI" style="height:38px;margin-top:2px;">';
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
        </div>
    <?php else: ?>
        <div class="container">
            <div class="alert alert-warning mt-5">No se encontró la hoja de vida de la cámara solicitada.</div>
        </div>
    <?php endif; ?>
</body>

</html>