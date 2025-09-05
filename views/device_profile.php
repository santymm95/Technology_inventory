<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
include_once __DIR__ . '/../includes/conection.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo "Equipo no encontrado.";
    exit;
}

// Consulta toda la información relevante del equipo
$sql = "SELECT d.internal_number, d.serial, b.name AS marca, m.name AS modelo, d.purchase_date, 
               pt.name AS tipo_proveedor, p.name AS procesador, r.name AS ram, 
               s.name AS almacenamiento, os.name AS sistema_operativo, 
               gc.name AS tarjeta_grafica, st.name AS estado
        FROM devices d
        LEFT JOIN brands b ON d.brand_id = b.id
        LEFT JOIN models m ON d.model_id = m.id
        LEFT JOIN provider_types pt ON d.provider_type_id = pt.id
        LEFT JOIN processors p ON d.processor_id = p.id
        LEFT JOIN rams r ON d.ram_id = r.id
        LEFT JOIN storages s ON d.storage_id = s.id
        LEFT JOIN operating_systems os ON d.os_id = os.id
        LEFT JOIN graphics_cards gc ON d.graphics_card_id = gc.id
        LEFT JOIN statuses st ON d.status_id = st.id
        WHERE d.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$device = $res->fetch_assoc();
$stmt->close();

if (!$device) {
    echo "Equipo no encontrado.";
    exit;
}

// Buscar la foto en la nueva ubicación: images/mantenimiento/{numero_interno}/{numero_interno}.*
$photoDir = "../images/mantenimiento/" . $device['internal_number'] . "/";
$photoPattern = $photoDir . $device['internal_number'] . ".*";
$photoFiles = glob($photoPattern);

if (count($photoFiles) > 0) {
    $photoUrl = $photoFiles[0];
} else {
    // Fallback a la lógica anterior (uploads o imagen por defecto)
    $photoPath = "../uploads/" . $device['internal_number'] . "/" . $device['internal_number'] . ".*";
    $photoFilesOld = glob($photoPath);
    $photoUrl = (count($photoFilesOld) > 0) ? $photoFilesOld[0] : "../assets/img/no-image.png";
}

// Obtener accesorios del equipo
$acc_sql = "SELECT a.name FROM device_accessories da 
            JOIN accessories a ON da.accessory_id = a.id 
            WHERE da.device_id = (SELECT id FROM devices WHERE internal_number = ? LIMIT 1)";
$acc_stmt = $conn->prepare($acc_sql);
$acc_stmt->bind_param("s", $device['internal_number']);
$acc_stmt->execute();
$acc_result = $acc_stmt->get_result();
$accessories = [];
while ($acc = $acc_result->fetch_assoc()) {
    $accessories[] = $acc['name'];
}
$acc_stmt->close();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Hoja de Vida del Equipo</title>
    <link rel="stylesheet" href="path/to/font-awesome/css/font-awesome.min.css">
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

        /* Modal styles */
        .modal-img-bg {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0; top: 0;
            width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.7);
            justify-content: center;
            align-items: center;
        }
        .modal-img-bg.active {
            display: flex;
        }
        .modal-img-content {
            position: relative;
            background: transparent;
            padding: 0;
            border-radius: 10px;
            max-width: 90vw;
            max-height: 90vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-img-content img {
            max-width: 90vw;
            max-height: 80vh;
            border-radius: 10px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.25);
            background: #fff;
        }
        .modal-img-close {
            position: absolute;
            top: 10px; right: 18px;
            font-size: 2.2em;
            color: #fff;
            background: rgba(0,0,0,0.3);
            border: none;
            cursor: pointer;
            border-radius: 50%;
            width: 40px; height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-img-close:hover {
            background: #215ba0;
        }
    </style>
</head>

<body>
    <?php include 'layout.php'; ?>

    <div class="main-content">
<div
        style="max-width:800px;margin:1.5rem auto 0 auto;text-align:center;display:flex;justify-content:center;gap:1em;">
        <a href="edit_device.php?id=<?= urlencode($id) ?>"
            style="display:inline-block;background:#215ba0;color:#fff;padding:0.7em 2em;border-radius:7px;font-size:1.08em;text-decoration:none;box-shadow:0 1px 4px rgba(33,118,174,0.10);font-weight:500;min-width:120px;min-height:48px;line-height:32px;text-align:center;">
            Editar
        </a>
        <a href="device_documents.php?id=<?= urlencode($id) ?>"
            style="display:inline-block;background:#f5c518;color:#215ba0;padding:0.7em 2em;border-radius:7px;font-size:1.08em;text-decoration:none;box-shadow:0 1px 4px rgba(245,197,24,0.10);font-weight:500;min-width:150px;min-height:48px;line-height:32px;text-align:center;">
            Documentos
        </a>
        <a href="assign_responsible.php?id=<?= urlencode($id) ?>"
            style="display:inline-block;background:#27ae60;color:#fff;padding:0.7em 2em;border-radius:7px;font-size:1.08em;text-decoration:none;box-shadow:0 1px 4px rgba(39,174,96,0.10);font-weight:500;min-width:150px;min-height:48px;line-height:32px;text-align:center;">
            Asignar responsable
        </a>
        <form method="post" action="../controllers/delete_device.php"
            onsubmit="return confirm('¿Estás seguro de que deseas eliminar este equipo? Esta acción no se puede deshacer.');"
            style="display:inline;">
            <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
            <button type="submit"
                style="background:#e74c3c;color:#fff;padding:0.7em 2em;border:none;border-radius:7px;font-size:1.08em;cursor:pointer;box-shadow:0 1px 4px rgba(231,76,60,0.10);font-weight:500;min-width:150px;min-height:48px;line-height:32px;text-align:center;">
                Eliminar
            </button>
        </form>
    </div>
        <div class="profile-container">
            <!-- Encabezado -->
            <table style="width:100%;margin-bottom:2rem;">

                <tr>
                    <td style="width:120px;vertical-align:middle;">
                        <img src="../assets/images/logo-acema.webp" alt="Logo" style="height:60px;max-width:120px;">
                    </td>
                    <td style="text-align:center;vertical-align:middle;">
                        <div style="font-size:1.1rem; font-weight:bold; letter-spacing:1px;">
                            HOJA DE VIDA EQUIPO DE COMPUTO
                        </div>
                    </td>
                    <td style="text-align:center;vertical-align:middle;font-size:1rem;">
                        <div><strong>Código:</strong> ACM-TI-FORMS-002</div>
                        <div><strong>Versión:</strong> 002</div>
                        <div><strong>Fecha:</strong> 09-06-2025</div>
                    </td>
                </tr>
            </table>
            <hr style="margin: 0 0 2rem 0; border: none; border-top: 2px solid #e0e0e0;">
            <!-- Datos del equipo -->
            <div class="profile-header">
                <a href="#" id="openModalImg">
                    <img class="profile-photo" src="<?= htmlspecialchars($photoUrl) ?>" alt="Foto equipo">
                </a>
                <div>
                    <!-- <div class="profile-title">Equipo: <?= htmlspecialchars($device['internal_number']) ?></div> -->
                    <div><strong>Marca:</strong> <?= htmlspecialchars($device['marca']) ?></div>
                    <div><strong>Número Interno:</strong> <?= htmlspecialchars($device['internal_number']) ?></div>
                    <div><strong>Serial:</strong> <?= htmlspecialchars($device['serial']) ?></div>
            
                    <div><strong>Modelo:</strong> <?= htmlspecialchars($device['modelo']) ?></div>
                    <div><strong>Fecha de Compra:</strong> <?= htmlspecialchars($device['purchase_date']) ?></div>
                    <div><strong>Tipo de equipo:</strong> <?= htmlspecialchars($device['tipo_proveedor']) ?></div>

                </div>
            </div>
            <!-- Botón para documentos relacionados -->
            
            <hr style="margin: 2rem 0; border: none; border-top: 2px solid #e0e0e0;">
            <!-- Especificaciones -->
            <table class="profile-table">
                <p><strong>ESPECIFICACIONES</strong></p>
                <tr>
                    <th>Procesador</th>
                    <td><?= htmlspecialchars($device['procesador']) ?></td>
                </tr>
                <tr>
                    <th>RAM</th>
                    <td><?= htmlspecialchars($device['ram']) ?></td>
                </tr>
                <tr>
                    <th>Almacenamiento</th>
                    <td><?= htmlspecialchars($device['almacenamiento']) ?></td>
                </tr>
                <tr>
                    <th>Tarjeta Gráfica</th>
                    <td><?= htmlspecialchars($device['tarjeta_grafica']) ?></td>
                </tr>
                <tr>
                    <th>Sistema Operativo</th>
                    <td><?= htmlspecialchars($device['sistema_operativo']) ?></td>
                </tr>
                <tr>
                    <th>Estado</th>
                    <td><?= htmlspecialchars($device['estado']) ?></td>
                </tr>

            </table>
            <?php if (!empty($accessories)): ?>
                <p style="padding-top:1rem;"><strong>ACCESORIOS:</strong></p>
                <div style="margin-top:0.7em; display: flex; gap: 1em; flex-wrap: wrap; justify-content:space-around;">
                    <?php foreach ($accessories as $accName): ?>
                <div style="display: flex; flex-direction: column; align-items: center;">
                    <span style="font-size:0.95em;color:#444;"><?= htmlspecialchars($accName) ?></span>
                    <span title="<?= htmlspecialchars($accName) ?>" style="font-size:1.4em;">
                        <i class="fa fa-check-circle" style="color:#40a335;"></i>
                        
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <!-- Botón para modificar y eliminar la info del equipo -->
    

        <!-- Registro de mantenimiento debajo de la hoja de vida -->
       
        <!-- Mostrar mantenimientos relacionados -->
        <?php
        // Obtener mantenimientos del equipo (traer también el campo external)
        $mnt_sql = "SELECT date, type, responsible, description, external FROM maintenance WHERE device_id = ?";
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
            <div
                style="max-width:800px;margin:2.5rem auto 0 auto;padding:2rem;background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(33,118,174,0.07);">
                <h3 style="color:#2176ae;margin-bottom:1.2em;">Historial de Mantenimientos</h3>
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f7f9fb;">
        <th style="padding:0.7em 0.5em;color:#215ba0;text-align:center;">Fecha</th>
        <th style="padding:0.7em 0.5em;color:#215ba0;text-align:center;">Tipo</th>
        <th style="padding:0.7em 0.5em;color:#215ba0;text-align:center;">Descripción</th>
        <th style="padding:0.7em 0.5em;color:#215ba0;text-align:center;">Foto/documento</th>
                <th style="padding:0.7em 0.5em;color:#215ba0;text-align:center;">Realizado por</th>
        <th style="padding:0.7em 0.5em;color:#215ba0;text-align:center;">Responsable</th>

    </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mantenimientos as $mnt): ?>
    <tr>
        <td style="padding:0.6em 0.5em; text-align: center; vertical-align:middle;"><?= htmlspecialchars($mnt['date']) ?></td>
        <td style="padding:0.6em 0.5em; text-align: center; vertical-align:middle;"><?= htmlspecialchars($mnt['type']) ?></td>
        <td style="padding:0.6em 0.5em; text-align: left; vertical-align:middle;">
            <?= nl2br(htmlspecialchars($mnt['description'])) ?>
        </td>
        <td style="padding:0.6em 0.5em; text-align: center; vertical-align:middle;">
            <?php
                $mntImgDir = "../images/mantenimiento/" . $device['internal_number'] . "/";
                $mntImgPattern = $mntImgDir . $mnt['date'] . ".*";
                $mntImgFiles = glob($mntImgPattern);
                if (count($mntImgFiles) > 0):
                    $mntImgUrl = $mntImgFiles[0];
            ?>
                <a href="<?= htmlspecialchars($mntImgUrl) ?>" target="_blank" style="display:inline-block;">
                    <div style="font-size:0.85em;margin-top:0.3em;">Ver</div>
                </a>
            <?php else: ?>
                <span style="color:#bbb;font-size:0.95em;">Sin foto</span>
            <?php endif; ?>
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

        <!-- Checkbox para mostrar el formulario de mantenimiento -->
        <div style="margin:2.5rem 0 1.5rem 0;">
            <label>
                <input type="checkbox" id="mantenimiento-checkbox-final">
                Registrar nuevo mantenimiento
            </label>
        </div>
        <!-- Formulario de mantenimiento oculto inicialmente -->
        <form id="mantenimiento-form" method="post" action="../controllers/register_maintenance.php" enctype="multipart/form-data" style="display:none;max-width:600px;margin:0 auto 2rem auto;padding:1.5rem 1rem;background:#f8fafc;border-radius:10px;border:1px solid #eaeaea;">
            <input type="hidden" name="device_id" value="<?= htmlspecialchars($id) ?>">
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
                <!-- Campo oculto para enviar el responsable a la base de datos -->
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
            </div>
            <div style="text-align:center;margin-top:1.5em;">
                <button type="submit" style="background:#2176ae;color:#fff;padding:0.7em 2em;border:none;border-radius:7px;font-size:1.08em;cursor:pointer;box-shadow:0 1px 4px rgba(33,118,174,0.10);">
                    Guardar mantenimiento
                </button>
            </div>
        </form>
        <script>
document.addEventListener('DOMContentLoaded', function () {
    // Mostrar/ocultar formulario de mantenimiento
    document.getElementById('mantenimiento-checkbox-final').addEventListener('change', function () {
        document.getElementById('mantenimiento-form').style.display = this.checked ? 'block' : 'none';
    });

    function toggleResponsableFields() {
        var externo = document.getElementById('externo-checkbox').checked;
        document.getElementById('responsable-ti').style.display = externo ? 'none' : 'block';
        document.getElementById('responsable-externo').style.display = externo ? 'block' : 'none';
    }
    window.toggleResponsableFields = toggleResponsableFields;

    // Modal logic
    var openModalBtn = document.getElementById('openModalImg');
    var closeModalBtn = document.getElementById('closeModalImg');
    var modalBg = document.getElementById('modalImgBg');

    if (openModalBtn && modalBg) {
        openModalBtn.addEventListener('click', function(e) {
            e.preventDefault();
            modalBg.classList.add('active');
        });
    }
    if (closeModalBtn && modalBg) {
        closeModalBtn.addEventListener('click', function(e) {
            e.preventDefault();
            modalBg.classList.remove('active');
        });
    }
    if (modalBg) {
        modalBg.addEventListener('click', function(e) {
            if (e.target === modalBg) {
                modalBg.classList.remove('active');
            }
        });
    }
});
        </script>
    </div>

    <!-- Modal for image -->
    <div class="modal-img-bg" id="modalImgBg">
        <div class="modal-img-content">
            <button class="modal-img-close" id="closeModalImg" title="Cerrar">&times;</button>
            <img src="<?= htmlspecialchars($photoUrl) ?>" alt="Foto equipo ampliada">
        </div>
    </div>
</body>
</html>