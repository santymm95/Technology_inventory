<?php
session_start();
include_once __DIR__ . '/../includes/conection.php';

// Permitir ver actas por equipo (id)
$device_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Si no hay equipo, error
if ($device_id <= 0) {
    echo "Equipo no especificado.";
    exit;
}

// Obtener todas las actas del equipo
$stmt = $conn->prepare("SELECT a.id, a.fecha_entrega, a.fecha_devolucion, u.first_name, u.last_name 
    FROM actas a 
    JOIN users u ON a.user_id = u.id
    WHERE a.device_id = ?
    ORDER BY a.fecha_entrega DESC");
$stmt->bind_param("i", $device_id);
$stmt->execute();
$res = $stmt->get_result();
$actas_list = [];
while ($row = $res->fetch_assoc()) {
    $actas_list[] = $row;
}
$stmt->close();

// Determinar la acta seleccionada
$acta_id = isset($_GET['acta_id']) ? intval($_GET['acta_id']) : ($actas_list[0]['id'] ?? 0);
if ($acta_id <= 0) {
    echo "No hay actas registradas para este equipo.";
    exit;
}

// Obtener la información de la acta seleccionada
$stmt = $conn->prepare("SELECT a.*, 
    d.internal_number, d.serial, d.purchase_date, 
    rams.name AS ram, 
    operating_systems.name AS sistema_operativo, 
    storages.name AS almacenamiento, 
    graphics_cards.name AS grafica, 
    models.name AS modelo,
    u.first_name, u.last_name, u.document, u.position, u.department, 
    b.name AS marca
    FROM actas a 
    JOIN devices d ON a.device_id = d.id
    JOIN models ON d.model_id = models.id 
    JOIN users u ON a.user_id = u.id
    LEFT JOIN brands b ON d.brand_id = b.id
    LEFT JOIN rams ON d.ram_id = rams.id
    LEFT JOIN storages ON d.storage_id = storages.id
    LEFT JOIN operating_systems ON d.os_id = operating_systems.id
    LEFT JOIN graphics_cards ON d.graphics_card_id = graphics_cards.id
    WHERE a.id = ?");
$stmt->bind_param("i", $acta_id);
$stmt->execute();
$res = $stmt->get_result();
$acta = $res->fetch_assoc();
$stmt->close();

if (!$acta) {
    echo "No hay información de la acta seleccionada.";
    exit;
}

// Procesar formulario de devolución y guardar en la tabla actas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fecha_devolucion'])) {
    $fecha_devolucion = $_POST['fecha_devolucion'] ?? null;
    $estado_devolucion = $_POST['estado_devolucion'] ?? null;
    $observacion_devolucion = $_POST['observacion_devolucion'] ?? null;
    $firma_devuelve = $_POST['firma_devuelve'] ?? null;

    // Guardar la firma de quien devuelve si existe
    if ($firma_devuelve && strpos($firma_devuelve, 'data:image/png;base64,') === 0) {
        $firmaData = base64_decode(str_replace('data:image/png;base64,', '', $firma_devuelve));
        $firmaDir = realpath(__DIR__ . '/../documents') . DIRECTORY_SEPARATOR . $acta['document'];
        if (!is_dir($firmaDir)) {
            mkdir($firmaDir, 0777, true);
        }
        $firmaDevolucionFile = $firmaDir . DIRECTORY_SEPARATOR . 'firma-devolucion-' . $acta['document'] . '.png';
        file_put_contents($firmaDevolucionFile, $firmaData);
    }

    $stmt = $conn->prepare("UPDATE actas SET fecha_devolucion = ?, estado_devolucion = ?, observacion_devolucion = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("sssi", $fecha_devolucion, $estado_devolucion, $observacion_devolucion, $acta['id']);
    $stmt->execute();
    $stmt->close();

    // Recargar los datos del acta actualizada
    $stmt = $conn->prepare("SELECT a.*, 
        d.internal_number, d.serial, d.purchase_date, 
        rams.name AS ram, 
        operating_systems.name AS sistema_operativo, 
        storages.name AS almacenamiento, 
        graphics_cards.name AS grafica, 
        u.first_name, u.last_name, u.document, u.position, u.department, 
        b.name AS marca
        FROM actas a 
        JOIN devices d ON a.device_id = d.id 
        JOIN users u ON a.user_id = u.id
        LEFT JOIN brands b ON d.brand_id = b.id
        LEFT JOIN rams ON d.ram_id = rams.id
        LEFT JOIN storages ON d.storage_id = storages.id
        LEFT JOIN operating_systems ON d.os_id = operating_systems.id
        LEFT JOIN graphics_cards ON d.graphics_card_id = graphics_cards.id
        WHERE a.id = ?");
    $stmt->bind_param("i", $acta['id']);
    $stmt->execute();
    $res = $stmt->get_result();
    $acta = $res->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Actas del equipo</title>
 
    <link rel="stylesheet" href="../assets/css/acta_view.css">
    
</head>

<body>
     <?php include 'layout.php'; ?>
<div class="acta-container">
    <h2 style="text-align:center;margin-bottom:1.5em;">Actas registradas del equipo</h2>
    <div class="acta-cards-container">
        <?php foreach ($actas_list as $item): ?>
            <div class="acta-card<?= ($item['id'] == $acta_id ? ' selected' : '') ?>"
                onclick="mostrarActaFormulario(<?= $item['id'] ?>)">
                <div class="acta-card-title">
                    <?= htmlspecialchars($item['first_name'] . ' ' . $item['last_name']) ?>
                </div>
                <div class="acta-card-info">
                    <strong>Fecha entrega:</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($item['fecha_entrega']))) ?><br>
                    <strong>Fecha devolución:</strong>
                    <?= $item['fecha_devolucion'] ? htmlspecialchars(date('d/m/Y', strtotime($item['fecha_devolucion']))) : 'No registrada' ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <!-- Mostrar el formulario solo si hay una acta seleccionada -->
    <?php if ($acta): ?>
    <div id="acta-formulario" style="display:none;">
         
       
        <table class="pdf-header-table">
            <tr>
                <td class="pdf-header-logo">
                    <img src="../assets/images/logo.png" class="pdf-header-logo-img">
                </td>
                <td class="pdf-header-title">
                    ACTA DE ENTREGA Y DEVOLUCIÓN<br>
                    DE EQUIPOS DE CÓMPUTO
                </td>
                <td class="pdf-header-info">
                    <table class="pdf-header-info-table">
                        <tr>
                            <td><strong>Código:</strong> ACM-TI-FORMS-001</td>
                        </tr>
                        <tr>
                            <td><strong>Versión:</strong> 002</td>
                        </tr>
                        <tr>
                           <td><strong>Fecha:</strong> 09-06-2025</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
         <div style="text-align:justify;margin-bottom:2em;color:black;">
                Hoy, <?= date('d/m/Y') ?> el departamento de Tecnología e Información (TI), mediante el siguiente documento realiza la entrega formal de los equipos e insumos tecnológicos asignados para el cumplimiento de las actividades laborales al colaborador:
            </div>
        <h3>Datos del Usuario Responsable</h3>
        <table class="acta-table">
            <tr><th>Nombre(s)</th><td><?= htmlspecialchars($acta['first_name']) ?></td></tr>
            <tr><th>Apellido(s)</th><td><?= htmlspecialchars($acta['last_name']) ?></td></tr>
            <tr><th>Documento</th><td><?= htmlspecialchars($acta['document']) ?></td></tr>
            <tr><th>Cargo</th><td><?= htmlspecialchars($acta['position']) ?></td></tr>
            <tr><th>Departamento</th><td><?= htmlspecialchars($acta['department']) ?></td></tr>
        </table>
        <p style="text-align:justify;margin-bottom:2em;">
                    Quien declara recepción de estos en buen estado y se compromete a cuidar de los recursos y hacer uso de ellos para los fines establecidos.<br>
                    Ambas partes, reconociéndose mutuamente capacidad legal para contratar y obligarse en los términos del presente documento, acuerdan y establecen las siguientes cláusulas:
                </p>
                <p style="text-align:justify;margin-bottom:2em;">
                    <strong>CLAUSULA PRIMERA: OBJETO</strong><br>
                    ACEMA INGENIERÍA hace entrega a "El Recibidor" del siguiente equipo y accesorios.
                </p>
        <h3>Datos del Equipo</h3>
        <table class="acta-table">
            <tr>
                <th>Marca</th>
                <td><?= htmlspecialchars($acta['marca']) ?></td>
            </tr>
            <tr>
                <th>Modelo</th>
                <td><?= htmlspecialchars($acta['modelo']) ?></td>
            </tr>
            <tr>
                <th>Número Interno</th>
                <td><?= htmlspecialchars($acta['internal_number']) ?></td>
            </tr>
            <tr>
                <th>Serial</th>
                <td><?= htmlspecialchars($acta['serial']) ?></td>
            </tr>
            <!-- <tr>
                <th>Fecha de Compra</th>
                <td><?= htmlspecialchars($acta['purchase_date']) ?></td>
            </tr> -->
            <tr>
                <th>RAM</th>
                <td><?= htmlspecialchars($acta['ram'] ?? '') ?></td>
            </tr>
           
            <tr>
                <th>Almacenamiento</th>
                <td><?= htmlspecialchars($acta['almacenamiento'] ?? '') ?></td>
            </tr>
            <tr>
                <th>Gráfica</th>
                <td><?= htmlspecialchars($acta['grafica'] ?? '') ?></td>
            </tr>
             <tr>
                <th>Sistema Operativo</th>
                <td><?= htmlspecialchars($acta['sistema_operativo'] ?? '') ?></td>
            </tr>
            <tr>
                <th>Accesorios</th>
                <td><?= htmlspecialchars($acta['accesorios']) ?></td>
            </tr>
            <tr>
                <th>Observaciones</th>
                <td><?= nl2br(htmlspecialchars($acta['observacion'])) ?></td>
                
            </tr>
            <tr>
                <th>Foto del equipo</th>
                <td>
                    <?php
                    $foto_path = '../uploads/' . $acta['internal_number'] . '/' . $acta['internal_number'] . '.jpg';
                    if (file_exists(__DIR__ . '/../uploads/' . $acta['internal_number'] . '/' . $acta['internal_number'] . '.jpg')): ?>
                        <img src="<?= htmlspecialchars($foto_path) ?>" alt="Foto del equipo" style="max-width:180px;max-height:120px;border-radius:8px;box-shadow:0 2px 8px rgba(33,91,160,0.10);">
                    <?php else: ?>
                        <span style="color:#aaa;">Sin foto</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
          <div class="politicas" style="margin-bottom:2em;">
                   
                    <ul style="list-style:none;padding-left:0;">
                        <li>
                            <strong>CLAUSULA SEGUNDA: RESPONSABILIDADES DEL RECIBIDOR</strong>
                            <ul>
                                <li style="text-align: justify;">a) "El Recibidor" se compromete a recibir los equipos tecnológicos en las condiciones establecidas, a entregarlo de igual forma y a hacer uso del mismo de manera adecuada y responsable dentro de las instalaciones de ACEMA INGENIERÍA.</li>
                                <li>b) "El Recibidor" se compromete a informar a ACEMA INGENIERÍA de cualquier inconveniente o necesidad de mantenimiento que surja durante el uso de los equipos tecnológicos dentro de las instalaciones.</li>
                            </ul>
                        </li>
                        <li>
                            <strong>CLAUSULA TERCERA: RESPONSABILIDADES DE LA EMPRESA</strong>
                            <ul>
                                <li style="text-align: justify;">a) ACEMA INGENIERÍA se compromete a entregar los equipos tecnológicos en condiciones óptimas de funcionamiento.</li>
                                <li style="text-align: justify;">b) ACEMA INGENIERÍA proporcionará el soporte técnico necesario para el mantenimiento de los equipos tecnológicos, de acuerdo con los plazos establecidos y las necesidades dentro de las instalaciones.</li>
                                <li style="text-align: justify;">c) Llevará un registro detallado de las fechas de mantenimiento de los equipos tecnológicos, incluyendo cualquier reparación o servicio realizado dentro de las instalaciones de ACEMA INGENIERÍA.</li>
                                <li style="text-align: justify;">d) ACEMA INGENIERÍA proporcionará a "El Recibidor" la información necesaria para realizar el seguimiento y mantenimiento adecuado de los equipos tecnológicos dentro de las instalaciones.</li>
                            </ul>
                        </li>
                        <li>
                            <strong>CLAUSULA CUARTA: EXTRAVIO O MAL USO</strong>
                            <ul>
                                <li style="text-align: justify;">a) En caso de presentarse hurto o pérdida de algún equipo se deberá instaurar de inmediato la denuncia ante la policía nacional e informar por correo electrónico a willinton.posso@acemaingenieria.com del área de tecnología de la información (TI), adjuntando el denuncio.</li>
                                <li style="text-align: justify;">b) Si se evidencia que al equipo de cómputo o elementos de tecnología asignados se le ha dado un mal uso, se iniciará un proceso disciplinario, y si se evidencia un daño por tal motivo, se aplicará un descuento por nómina por el mantenimiento de este.</li>
                            </ul>
                        </li>
                        <li>
                            <strong>CLAUSULA QUINTA: SOFTWARE / HARDWARE</strong>
                            <ul>
                                <li style="text-align: justify;">a) NO se borrará, deshabilitará o sobrescribirá el software instalado en el equipo de cómputo asignado, esto incluye: office, sistema operativo, antivirus, cortafuegos o servicios de actualización automática.</li>
                                <li style="text-align: justify;">b) NO se descargará de internet, ni instalará ningún software que no se encuentre debidamente autorizado para su uso en el equipo de cómputo asignado. De requerir software se solicitará a la oficina de tecnologías de la información para su autorización e instalación.</li>
                            </ul>
                        </li>
                        <li>
                            <strong>CLAUSULA SEXTA: VIGENCIA</strong>
                            <ul>
                                <li style="text-align: justify;">La presente acta de entrega y seguimiento de equipos electrónicos tiene vigencia a partir de la fecha de entrega y se mantendrá en vigor hasta que ACEMA INGENIERÍA considere que se ha cumplido con las condiciones establecidas o hasta que se acuerde la devolución o retiro de los equipos tecnológicos.</li>
                            </ul>
                        </li>
                    </ul>
                    <div style="margin-top:1em;">
                        En constancia de lo cual, ambas partes firman el presente documento en dos ejemplares, en el lugar y fecha arriba indicados.
                    </div>
                </div>
                <!-- Cuadro de observación -->
        <div class="firma-section">
            <div class="firma-box">
                <?php
                // Mostrar firma de cuando recibe (firma de entrega)
                $firma_dir = realpath(__DIR__ . '/../documents');
                $firma_file = '';
                if ($firma_dir && isset($acta['document'])) {
                    $user_dir = $firma_dir . DIRECTORY_SEPARATOR . $acta['document'];
                    if (is_dir($user_dir)) {
                        $files = glob($user_dir . '/firma_' . $acta['document'] . '_*.png');
                        if ($files && count($files) > 0) {
                            // Mostrar la última firma de entrega registrada
                            natsort($files);
                            $firma_file = '../documents/' . $acta['document'] . '/' . basename(array_pop($files));
                        }
                    }
                }
                if ($firma_file):
                ?>
                    <img src="<?= htmlspecialchars($firma_file) ?>" alt="Firma Usuario" style="height:60px;display:block;margin:0 auto 0.5em auto;">
                <?php endif; ?>
                <div style="border-bottom:1px solid #888;margin-bottom:0.5em;"></div>
                <span>Firma del colaborador</span>
            </div>
            <div class="firma-box">
                <img src="../assets/images/responsable.jpg" alt="Firma Área de TI" style="height:60px;display:block;margin:0 auto 0.5em auto;">
                <div style="border-bottom:1px solid #888;margin-bottom:0.5em;"></div>
                <span>Firma Área de TI</span>
            </div>
        </div>
        <!-- Mostrar info de devolución debajo del formulario solo si hay info registrada -->
        <?php if (
            ($acta['fecha_devolucion'] && trim($acta['fecha_devolucion']) !== '') ||
            ($acta['estado_devolucion'] && trim($acta['estado_devolucion']) !== '') ||
            ($acta['observacion_devolucion'] && trim($acta['observacion_devolucion']) !== '')
        ): ?>
            <div style="margin-top:2.5em;">
                <h3 style="color:#215ba0;">Información de Devolución Registrada</h3>
                <table class="acta-table">
                    <tr>
                        <th>Fecha de devolución</th>
                        <td><?= $acta['fecha_devolucion'] ? htmlspecialchars($acta['fecha_devolucion']) : 'No registrada' ?></td>
                    </tr>
                    <tr>
                        <th>Estado del equipo</th>
                        <td><?= $acta['estado_devolucion'] ? htmlspecialchars($acta['estado_devolucion']) : 'No registrado' ?></td>
                    </tr>
                    <tr>
                        <th>Observaciones de devolución</th>
                        <td><?= nl2br(htmlspecialchars($acta['observacion_devolucion'])) ?></td>
                    </tr>
                </table>
                <div class="firma-section">
                    <div class="firma-box">
                        <?php
                        // Mostrar firma de quien devuelve: ../documents/{document}/firma-devolucion-{document}.png
                        $firma_devolucion_file = '';
                        if (!empty($acta['document'])) {
                            $firma_devolucion_abs = __DIR__ . '/../documents/' . $acta['document'] . '/firma-devolucion-' . $acta['document'] . '.png';
                            $firma_devolucion_rel = '../documents/' . $acta['document'] . '/firma-devolucion-' . $acta['document'] . '.png';
                            if (file_exists($firma_devolucion_abs)) {
                                $firma_devolucion_file = $firma_devolucion_rel;
                            }
                        }
                        if ($firma_devolucion_file):
                        ?>
                            <img src="<?= htmlspecialchars($firma_devolucion_file) ?>" alt="Firma Devolución" style="height:60px;display:block;margin:0 auto 0.5em auto;">
                        <?php else: ?>
                            <div style="height:60px;margin:0 auto 0.5em auto;border:1px dashed #bbb;border-radius:4px;background:#fafbfc;"></div>
                        <?php endif; ?>
                        <div style="border-bottom:1px solid #888;margin-bottom:0.5em;"></div>
                        <span>Firma de quien devuelve</span>
                    </div>
                    <div class="firma-box">
                        <img src="../assets/images/responsable.jpg" alt="Firma Área de TI" style="height:60px;display:block;margin:0 auto 0.5em auto;">
                        <div style="border-bottom:1px solid #888;margin-bottom:0.5em;"></div>
                        <span>Firma Área de TI</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Checkbox para mostrar el formulario de devolución -->
        <?php
        $devolucion_registrada =
            ($acta['fecha_devolucion'] && trim($acta['fecha_devolucion']) !== '') ||
            ($acta['estado_devolucion'] && trim($acta['estado_devolucion']) !== '') ||
            ($acta['observacion_devolucion'] && trim($acta['observacion_devolucion']) !== '');
        ?>
        <?php if (!$devolucion_registrada): ?>
        <div style="margin-top:2em;">
            <label>
                <input type="checkbox" id="activar_devolucion" onchange="toggleDevolucionForm()">
                Registrar devolución del equipo
            </label>
        </div>
        <?php endif; ?>
        <!-- Formulario de devolución solo visible si el check está activo -->
        <form id="form_devolucion" method="post" action="" onsubmit="guardarFirmaDevolucion()" autocomplete="off" style="margin-top:2em; display:none;">
            <h3 style="color:#215ba0;">Registrar devolución del equipo</h3>
            <div style="margin-bottom:1em;">
                <label>Nombre:</label>
                <input type="text" readonly value="<?= htmlspecialchars($acta['first_name']) ?>" style="width:100%;padding:0.4em;border-radius:6px;border:1px solid #c7d0db;background:#f7f7f7;">
            </div>
            <div style="margin-bottom:1em;">
                <label>Apellido:</label>
                <input type="text" readonly value="<?= htmlspecialchars($acta['last_name']) ?>" style="width:100%;padding:0.4em;border-radius:6px;border:1px solid #c7d0db;background:#f7f7f7;">
            </div>
            <div style="margin-bottom:1em;">
                <label>Fecha de devolución:</label>
                <input type="date" name="fecha_devolucion" style="width:100%;padding:0.4em;border-radius:6px;border:1px solid #c7d0db;">
            </div>
            <div style="margin-bottom:1em;">
                <label>Estado del equipo:</label>
                <select name="estado_devolucion" style="width:100%;padding:0.4em;border-radius:6px;border:1px solid #c7d0db;">
                    <option value="">Seleccione</option>
                    <option value="Bueno">Bueno</option>
                    <option value="Regular">Regular</option>
                    <option value="Malo">Malo</option>
                </select>
            </div>
            <div style="margin-bottom:1em;">
                <label>Observaciones:</label>
                <textarea name="observacion_devolucion" rows="2" style="width:100%;padding:0.4em;border-radius:6px;border:1px solid #c7d0db;resize:vertical;"></textarea>
            </div>
            <div class="firma-section">
                <div class="firma-box">
                    <span style="font-weight:500;">Firma de quien devuelve:</span>
                    <div class="firma-box-canvas">
                        <canvas id="firmaDevuelve" width="250" height="60" style="border:1px solid #888;background:#fff;touch-action: none;"></canvas>
                        <button type="button" onclick="clearFirmaDevuelve()" style="margin-top:0.5em;background:#e0e0e0;color:#215ba0;padding:0.3em 1em;border:none;border-radius:5px;cursor:pointer;">Limpiar firma</button>
                        <input type="hidden" name="firma_devuelve" id="firma_devuelve_input">
                    </div>
                </div>
                <div class="firma-box">
                    <span style="font-weight:500;">Firma Área de TI:</span>
                    <div class="firma-box-canvas">
                        <img src="../assets/images/responsable.jpg" alt="Firma Área de TI" style="height:60px;display:block;margin:0 auto 0.5em auto;">
                    </div>
                </div>
            </div>
            
            <div style="margin-top:2em;text-align:center;">
                <button type="submit" style="background:#2176ae;color:#fff;padding:0.7em 2em;border:none;border-radius:7px;font-size:1.08em;cursor:pointer;box-shadow:0 1px 4px rgba(33,118,174,0.10);">
                    Guardar devolución
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>
<script>
    // Firma devolución
    let canvasDevuelve = document.getElementById('firmaDevuelve');
    let ctxDevuelve = canvasDevuelve ? canvasDevuelve.getContext('2d') : null;
    let drawingDevuelve = false;
    if (canvasDevuelve) {
        canvasDevuelve.addEventListener('mousedown', function(e) {
            drawingDevuelve = true;
            ctxDevuelve.beginPath();
            ctxDevuelve.moveTo(e.offsetX, e.offsetY);
        });
        canvasDevuelve.addEventListener('mousemove', function(e) {
            if (drawingDevuelve) {
                ctxDevuelve.lineTo(e.offsetX, e.offsetY);
                ctxDevuelve.strokeStyle = "#222";
                ctxDevuelve.lineWidth = 2;
                ctxDevuelve.stroke();
            }
        });
        canvasDevuelve.addEventListener('mouseup', function() { drawingDevuelve = false; });
        canvasDevuelve.addEventListener('mouseleave', function() { drawingDevuelve = false; });
        canvasDevuelve.addEventListener('touchstart', function(e) {
            if (e.targetTouches.length == 1) {
                let rect = canvasDevuelve.getBoundingClientRect();
                let touch = e.targetTouches[0];
                drawingDevuelve = true;
                ctxDevuelve.beginPath();
                ctxDevuelve.moveTo(touch.clientX - rect.left, touch.clientY - rect.top);
                e.preventDefault();
            }
        });
        canvasDevuelve.addEventListener('touchmove', function(e) {
            if (drawingDevuelve && e.targetTouches.length == 1) {
                let rect = canvasDevuelve.getBoundingClientRect();
                let touch = e.targetTouches[0];
                ctxDevuelve.lineTo(touch.clientX - rect.left, touch.clientY - rect.top);
                ctxDevuelve.strokeStyle = "#222";
                ctxDevuelve.lineWidth = 2;
                ctxDevuelve.stroke();
                e.preventDefault();
            }
        });
        canvasDevuelve.addEventListener('touchend', function(e) { drawingDevuelve = false; e.preventDefault(); });
    }
    function clearFirmaDevuelve() {
        if (ctxDevuelve) ctxDevuelve.clearRect(0, 0, canvasDevuelve.width, canvasDevuelve.height);
    }
    function guardarFirmaDevolucion() {
        if (ctxDevuelve) {
            document.getElementById('firma_devuelve_input').value = canvasDevuelve.toDataURL("image/png");
        }
    }
    function toggleDevolucionForm() {
        var check = document.getElementById('activar_devolucion');
        var form = document.getElementById('form_devolucion');
        form.style.display = check.checked ? 'block' : 'none';
    }

    // Mostrar/ocultar el formulario de acta al hacer clic en la card
    function mostrarActaFormulario(id) {
        // Siempre recarga la página con el acta seleccionada (un solo clic)
        window.location.href = '?id=<?= $device_id ?>&acta_id=' + id;
    }

    // Mostrar el formulario si hay acta seleccionada (cuando hay acta_id en la URL)
    window.addEventListener('DOMContentLoaded', function() {
        var formDiv = document.getElementById('acta-formulario');
        // Siempre inicia oculto, solo se muestra si hay acta_id en la URL
        if (formDiv) formDiv.style.display = 'none';
        <?php if (isset($_GET['acta_id'])): ?>
        if (formDiv && '<?= $_GET['acta_id'] ?>' !== '') formDiv.style.display = 'block';
        <?php endif; ?>
    });
</script>
</body>
</html>
