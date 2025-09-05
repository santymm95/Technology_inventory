<?php
session_start();
include_once __DIR__ . '/../includes/conection.php';

// Allow viewing vehicle actas by vehicle id
$vehicle_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($vehicle_id <= 0) {
    echo "Vehículo no especificado.";
    exit;
}

// Get all actas for the vehicle
$stmt = $conn->prepare("SELECT vr.id, vr.created_at, u.first_name, u.last_name 
    FROM vehicle_record vr
    JOIN users u ON vr.user_id = u.id
    WHERE vr.vehicle_id = ?
    ORDER BY vr.created_at DESC");
$stmt->bind_param("i", $vehicle_id);
$stmt->execute();
$res = $stmt->get_result();
$actas_list = [];
while ($row = $res->fetch_assoc()) {
    $actas_list[] = $row;
}
$stmt->close();

// Determine selected acta
$acta_id = isset($_GET['acta_id']) ? intval($_GET['acta_id']) : ($actas_list[0]['id'] ?? 0);
if ($acta_id <= 0) {
    echo "No hay actas registradas para este vehículo.";
    exit;
}

// Get selected acta info
$stmt = $conn->prepare("SELECT vr.*, v.placa, v.marca, v.modelo, u.first_name, u.last_name, u.document, u.position, u.department
    FROM vehicle_record vr
    JOIN vehicle v ON vr.vehicle_id = v.id
    JOIN users u ON vr.user_id = u.id
    WHERE vr.id = ?");
$stmt->bind_param("i", $acta_id);
$stmt->execute();
$res = $stmt->get_result();
$acta = $res->fetch_assoc();
$stmt->close();

if (!$acta) {
    echo "No hay información de la acta seleccionada.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Actas del vehículo</title>
    <link rel="stylesheet" href="../assets/css/acta_view.css">
</head>
<body>
<?php include 'layout.php'; ?>
<div class="acta-container">
    <h2 style="text-align:center;margin-bottom:1.5em;">Actas registradas del vehículo</h2>
    <div class="acta-cards-container">
        <?php foreach ($actas_list as $item): ?>
            <?php
            // Obtener datos de devolución para cada acta (solo para la card)
            $return_date = '';
            $stmt = $conn->prepare("SELECT return_date FROM vehicle_record WHERE id = ?");
            $stmt->bind_param("i", $item['id']);
            $stmt->execute();
            $stmt->bind_result($return_date);
            $stmt->fetch();
            $stmt->close();
            ?>
            <div class="acta-card<?= ($item['id'] == $acta_id ? ' selected' : '') ?>"
                onclick="mostrarActaFormulario(<?= $item['id'] ?>)">
                <div class="acta-card-title">
                    <?= htmlspecialchars($item['first_name'] . ' ' . $item['last_name']) ?>
                </div>
                <div class="acta-card-info">
                    <strong>Fecha de entrega:</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($item['created_at']))) ?><br>
                    <?php if (!empty($return_date)): ?>
                        <strong>Fecha de devolución:</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($return_date))) ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if ($acta): ?>
    <div id="acta-formulario" style="display:none;">
        <table class="pdf-header-table">
            <tr>
                <td class="pdf-header-logo">
                    <img src="../assets/images/logo.png" class="pdf-header-logo-img">
                </td>
                <td class="pdf-header-title">
                    ACTA DE ENTREGA Y DEVOLUCIÓN<br>
                    DE VEHÍCULO
                </td>
                <td class="pdf-header-info">
                    <table class="pdf-header-info-table">
                        <tr>
                            <td><strong>Código:</strong> ACM-ADM-TI-FO-004</td>
                        </tr>
                        <tr>
                            <td><strong>Versión:</strong> 002</td>
                        </tr>
                        <tr>
                            <td><strong>Fecha:</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($acta['created_at']))) ?></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <div style="text-align:justify;margin-bottom:2em;color:black;">
            Hoy, <?= date('d/m/Y', strtotime($acta['created_at'])) ?> se hace entrega del vehículo propiedad de la empresa ACEMA INGENIERÍA SAS, quedando como a cargo total del cuidado y supervisión del vehículo que aquí se entrega, los días en que se le esté cediendo la movilidad fuera del horario laboral, y ante cualquier daño o desperfecto será el único responsable de cubrir los daños causados.
        </div>
        <h3>Datos del Usuario Responsable</h3>
        <table class="acta-table">
            <tr><th>Nombre(s)</th><td><?= htmlspecialchars($acta['first_name']) ?></td></tr>
            <tr><th>Apellido(s)</th><td><?= htmlspecialchars($acta['last_name']) ?></td></tr>
            <tr><th>Documento</th><td><?= htmlspecialchars($acta['document']) ?></td></tr>
            <tr><th>Cargo</th><td><?= htmlspecialchars($acta['position']) ?></td></tr>
            <tr><th>Departamento</th><td><?= htmlspecialchars($acta['department']) ?></td></tr>
        </table>
        <h3>Datos del Vehículo</h3>
        <table class="acta-table">
            <tr><th>Placa</th><td><?= htmlspecialchars($acta['placa']) ?></td></tr>
            <tr><th>Marca</th><td><?= htmlspecialchars($acta['marca']) ?></td></tr>
            <tr><th>Modelo</th><td><?= htmlspecialchars($acta['modelo']) ?></td></tr>
            <tr>
                <th>Foto del vehículo</th>
                <td>
                    <?php
                    // Usar el mismo método que vehicle_list.php para buscar la imagen
                    $placaFolder = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $acta['placa']);
                    $photoDir = "../uploads/" . $placaFolder . "/";
                    $photoPattern = $photoDir . "foto_vehiculo.*";
                    $photoFiles = glob($photoPattern);
                    $photo = (count($photoFiles) > 0) ? $photoFiles[0] : "../assets/img/no-image.png";
                    $photoExt = strtolower(pathinfo($photo, PATHINFO_EXTENSION));
                    // Mensaje de depuración opcional
                    // echo '<div style="color:#090;font-size:12px;">[DEBUG] Ruta web usada: ' . htmlspecialchars($photo) . '</div>';
                    if ($photoExt === 'pdf'):
                    ?>
                        <a href="<?= htmlspecialchars($photo) ?>" target="_blank" style="color:#2176ae;text-decoration:underline;">Ver PDF</a>
                    <?php elseif (in_array($photoExt, ['jpg', 'jpeg', 'png', 'webp'])): ?>
                        <img src="<?= htmlspecialchars($photo) ?>" alt="Foto del vehículo" style="max-width:200px;max-height:140px;border-radius:8px;box-shadow:0 2px 8px rgba(33,91,160,0.10);">
                    <?php else: ?>
                        <span style="color:#aaa;">Formato no soportado</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        <h3>Elementos Entregados</h3>
        <ul style="display:flex;flex-wrap:wrap;gap:1.5em;list-style:none;padding-left:0;">
            <?php
            $items = json_decode($acta['delivered_items'], true);
            if ($items && is_array($items)) {
                foreach ($items as $k => $v) {
                    echo '<li style="background:#ecf2fc;padding:0.5em 1.2em;border-radius:6px;color:#215ba0;font-weight:500;border:1px solid #dbe4f3;">' . htmlspecialchars(ucwords(str_replace('_', ' ', $k))) . '</li>';
                }
            } else {
                echo '<li>No registrado</li>';
            }
            ?>
        </ul>
        <br>
        <hr>
        <div class="politicas" style="margin-bottom:2em;">
            <ul style="list-style:none;padding-left:0;">
                <li>
                    <strong>CLAUSULA SEGUNDA: RESPONSABILIDADES DEL RECIBIDOR</strong>
                    <ul>
                        <li style="text-align: justify;"><strong>A)</strong> "El Recibidor" se compromete a recibir el vehículo en las condiciones establecidas a entregarlo de igual forma y a hacer uso del mismo de manera adecuada y responsable dentro de las ciudades asignadas por ACEMA INGENIERÍA SAS.</li>
                        <li style="text-align: justify;"><strong>B)</strong> "El Recibidor" se compromete a Inspeccionar el vehículo antes de iniciar las labores diarias: Niveles, luces, llantas, posibles fugas, tablero instrumentos etc., y plasmar la información en el formato ACM-SGI-FO-012 PREOPERACIONAL DE CAMIONETA.</li>
                        <li style="text-align: justify;"><strong>C)</strong> "El Recibidor" se compromete a informar a ACEMA INGENIERÍA SAS de cualquier inconveniente o necesidad de mantenimiento que surja durante el uso del vehículo.</li>
                        <li style="text-align: justify;"><strong>D)</strong> "El Recibidor" se compromete a respetar los límites de velocidad establecido por la legislación colombiana, teniendo presente que:<br>
                            En vías urbanas y municipales, la velocidad máxima permitida es de 50 kph.<br>
                            En zonas escolares y residenciales, se debe mantener una velocidad de hasta 30kph.<br>
                            En vías rurales, el límite de velocidad autorizado es de 80 kph.<br>
                            En carreteras nacionales y departamentales, se establece una velocidad máxima de 90 kph.
                        </li>
                        <li style="text-align: justify;"><strong>E)</strong> "El Recibidor” se compromete a Informar de forma veraz su estado de salud.</li>
                        <li style="text-align: justify;"><strong>F)</strong> "El Recibidor” se compromete a respetar las normas de tránsito.</li>
                        <li style="text-align: justify;"><strong>G)</strong> "El Recibidor” se compromete Informar al dueño del vehículo, jefe inmediato y/o auxiliar de SST cualquier anomalía, incidente o accidente de tránsito que presente el vehículo por insignificante que parezca.</li>
                        <li style="text-align: justify;"><strong>H)</strong> "El Recibidor” se compromete a Portar los documentos tanto del vehículo como los personales ordenados por la autoridad competente.</li>
                        <li style="text-align: justify;"><strong>I)</strong> "El Recibidor” se compromete a mantener el vehículo en óptimas condiciones de funcionamiento y limpieza.</li>
                        <li style="text-align: justify;"><strong>J)</strong> "El Recibidor” se compromete a cuidar y preservar el estado general del vehículo.</li>
                        <li style="text-align: justify;"><strong>K)</strong> "El Recibidor” se compromete a usar el cinturón de seguridad y exigir su uso a los ocupantes del vehículo que conduzco.</li>
                        <li style="text-align: justify;"><strong>L)</strong> "El Recibidor” se compromete a usar los dispositivos de comunicaciones solo cuando me encuentre detenido en lugar seguro y habilitado para estacionamiento evitando que puedan causarme distracción y por con siguiente probables accidentes de tránsito.</li>
                        <li style="text-align: justify;"><strong>M)</strong> "El Recibidor” se compromete a usar los elementos de protección personal durante la conducción de vehículos en cumplimiento de la misión de la empresa.</li>
                        <li style="text-align: justify;"><strong>N)</strong> "El Recibidor” se compromete a asistir a las capacitaciones, charlas y eventos citados por la organización.</li>
                        <li style="text-align: justify;"><strong>O)</strong> "El Recibidor” se compromete a estar a paz y salvo con el pago de comparendos por infracciones de tránsito.</li>
                        <li style="text-align: justify;"><strong>P)</strong> "El Recibidor” se compromete a guardar el vehículo en un lugar seguro.</li>
                    </ul>
                </li>
                <li>
                    <strong>CLAUSULA TERCERA: RESPONSABILIDADES DE LA EMPRESA</strong>
                    <ul>
                        <li style="text-align: justify;">ACEMA INGENIERÍA SAS se compromete a entregar el vehículo en óptimas condiciones de funcionamiento.</li>
                        <li style="text-align: justify;">ACEMA INGENIERÍA SAS proporcionará el soporte técnico necesario para el mantenimiento preventivo o correctivo del vehículo, de acuerdo con los plazos establecidos y las necesidades que se presenten.</li>
                        <li style="text-align: justify;">ACEMA INGENIERÍA SAS llevará un registro detallado de las fechas de mantenimiento del vehículo, incluyendo cualquier reparación o servicio realizado.</li>
                        <li style="text-align: justify;">ACEMA INGENIERÍA SAS proporcionará a "El Recibidor" la información necesaria para realizar el seguimiento y mantenimiento adecuado del vehículo.</li>
                    </ul>
                </li>
                <li>
                    <strong>CLAUSULA CUARTA: HURTO, ACCIDENTE O DAÑOS</strong>
                    <ul>
                        <li style="text-align: justify;">En caso de presentarse hurto del vehículo se deberá instaurar de inmediato la denuncia ante la policía nacional y relatar los hechos e informar por correo electrónico a mariana.agudelo@acemaingenieria.com Gerente Administrativa, y gerencia@acemaingenieria.com  Gerente General, adjuntando el denuncio.</li>
                        <li style="text-align: justify;">En caso de accidente o daño al vehículo, el empleado debe informar a la empresa y a las autoridades competentes de inmediato. El conductor deberá presentar un informe detallado del incidente y enviarlo por correo electrónico a, mariana.agudelo@acemaingenieria.com Gerente Administrativa, y gerencia@acemaingenieria.com  Gerente General. Si el daño es producto de negligencia o mal uso del vehículo, los costos asociados son deducidos de nómina.</li>
                    </ul>
                </li>
                <li>
                    <strong>CLAUSULA QUINTA: MULTAS</strong>
                    <ul>
                        <li style="text-align: justify;">El trabajador se compromete a asumir la responsabilidad por cualquier infracción de tránsito que cometa durante el período de arrendamiento del vehículo o cuando utilice vehículos de la empresa, incluyendo, multas por exceso de velocidad, estacionamiento indebido, uso incorrecto de los documentos del vehículo, foto multas etc. En caso de que el colaborador reciba una multa, deberá abonar dicha multa de forma inmediata, así como los gastos administrativos generados por el proceso igualmente.</li>
                        <li style="text-align: justify;">En caso de que la empresa pague una multa en nombre del trabajador, este se compromete a reembolsar el monto total de la multa a partir de la notificación formal de la misma. además de los posibles gastos administrativos generados. En caso de que el trabajador no cumpla con el reembolso dentro del plazo establecido, la empresa podrá imponer sanciones adicionales, que podrán incluir la retención de salarios o la terminación del contrato de trabajo, dependiendo de la gravedad de la infracción cometida.</li>
                    </ul>
                </li>
                <li>
                    <strong>CLAUSULA SÉPTIMA: CAMBIOS</strong>
                    <ul>
                        <li style="text-align: justify;">NO realizar cambios de carrocería, color, rines, cámara, u cualquier otro elemento sin previa autorización del jefe inmediato.</li>
                    </ul>
                </li>
                <li>
                    <strong>CLAUSULA OCTAVA: PROHIBICIONES</strong>
                    <ul>
                        <li style="text-align: justify;">NO TRANSPORTAR personal ajeno a la compañía, a menos de que este sea autorizado por su jefe inmediato.</li>
                        <li style="text-align: justify;">NO PRESTAR el vehículo a ninguna persona, cuando el directo responsable es Usted.</li>
                        <li style="text-align: justify;">NO consumir, portar o distribuir bebidas alcohólicas o sustancias psicoactivas en los sitios de trabajo, instalaciones, vehículos o áreas relacionadas con las operaciones de la empresa.</li>
                        <li style="text-align: justify;">NO USAR, teléfonos móviles ni dispositivos electrónicos mientras se conduce, salvo cuando se utilicen sistemas de manos libres y sin distraer la atención del conductor.</li>
                        <li style="text-align: justify;">Queda expresamente prohibido el uso del vehículo para el transporte de bienes no relacionados con las actividades de la empresa, así como para fines personales, recreativos o cualquier otro propósito ajeno a las necesidades laborales autorizadas.</li>
                    </ul>
                </li>
                <li>
                    <strong>CLAUSULA NOVENA: VIGENCIA</strong>
                    <ul>
                        <li style="text-align: justify;">La presente acta de entrega tiene vigencia a partir de la fecha de entrega y se mantendrá en vigor hasta que ACEMA INGENIERÍA SAS considere que se ha cumplido con las condiciones establecidas o hasta que se acuerde la devolución o retiro del mismo.</li>
                    </ul>
                </li>
            </ul>
            <div style="margin-top:1em;">
                En constancia de lo cual, ambas partes firman el presente documento en dos ejemplares, en el lugar y fecha arriba indicados.
            </div>
        </div>
        <div class="firma-section">
            <div class="firma-box">
                <?php
                // Mostrar la firma de entrega guardada como firma_entrega.png en la carpeta vehicle_{document}
                $firma_file = '';
                if (!empty($acta['document'])) {
                    $firma_file = __DIR__ . '/../uploads/documents/vehicle_' . $acta['document'] . '/firma_entrega.png';
                }
                if ($firma_file && file_exists($firma_file) && filesize($firma_file) > 100) {
                    $firma_file_rel = str_replace(realpath(__DIR__ . '/..'), '..', $firma_file);
                    echo '<img src="' . htmlspecialchars($firma_file_rel) . '" alt="Firma Usuario" style="height:60px;display:block;margin:0 auto 0.5em auto;">';
                } else {
                    echo '<div style="height:60px;margin:0 auto 0.5em auto;border:1px dashed #bbb;border-radius:4px;background:#fafbfc;"></div>';
                }
                ?>
                <div style="border-bottom:1px solid #888;margin-bottom:0.5em;"></div>
                <span>Firma Usuario Responsable</span>
            </div>
            <div class="firma-box">
                <img src="../assets/images/responsable.jpg" alt="Firma Área de TI" style="height:60px;display:block;margin:0 auto 0.5em auto;">
                <div style="border-bottom:1px solid #888;margin-bottom:0.5em;"></div>
                <span>Firma Supervisor(a)</span>
            </div>
        </div>
        <?php if ($acta['is_returned']) { ?>
        <div style="margin-top:2.5em;">
            <h3 style="color:#215ba0;">Información de Devolución Registrada</h3>
            <table class="acta-table">
                <tr>
                    <th>Nombre</th>
                    <td><?= htmlspecialchars($acta['return_first_name'] ?? '') ?></td>
                </tr>
                <tr>
                    <th>Apellido</th>
                    <td><?= htmlspecialchars($acta['return_last_name'] ?? '') ?></td>
                </tr>
                <tr>
                    <th>Fecha de devolución</th>
                    <td><?= $acta['return_date'] ? htmlspecialchars($acta['return_date']) : 'No registrada' ?></td>
                </tr>
                <tr>
                    <th>Estado del equipo</th>
                    <td><?= $acta['return_condition'] ? htmlspecialchars($acta['return_condition']) : 'No registrado' ?></td>
                </tr>
                <tr>
                    <th>Observaciones</th>
                    <td><?= nl2br(htmlspecialchars($acta['return_observations'])) ?></td>
                </tr>
            </table>
            <div class="firma-section">
                <div class="firma-box">
                    <?php
                    // Mostrar la firma de devolución guardada como firma_devolucion.png en la carpeta vehicle_{document}
                    $firma_return = '';
                    if (!empty($acta['document'])) {
                        $firma_return = __DIR__ . '/../uploads/documents/vehicle_' . $acta['document'] . '/firma_devolucion.png';
                    }
                    if ($firma_return && file_exists($firma_return) && filesize($firma_return) > 100) {
                        $firma_return_rel = str_replace(realpath(__DIR__ . '/..'), '..', $firma_return);
                        echo '<img src="' . htmlspecialchars($firma_return_rel) . '" alt="Firma Devolución" style="height:60px;display:block;margin:0 auto 0.5em auto;">';
                    } else {
                        echo '<div style="height:60px;margin:0 auto 0.5em auto;border:1px dashed #bbb;border-radius:4px;background:#fafbfc;"></div>';
                    }
                    ?>
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
        <?php } ?>
        <!-- Formulario de devolución igual al de los computadores, al final del acta -->
        <div style="margin:2em 0;<?= $acta['is_returned'] ? 'display:none;' : '' ?>">
            <label>
                <input type="checkbox" id="toggle-devolucion" onchange="toggleDevolucionForm()" style="transform:scale(1.2);margin-right:0.5em;">
                Registrar devolución del equipo
            </label>
            <form id="devolucion-form" method="post" action="../controllers/register_vehicle_return.php" enctype="multipart/form-data" style="display:none;margin-top:1.5em;background:#f8fafc;padding:1.5em 1em;border-radius:10px;border:1px solid #eaeaea;max-width:600px;">
                <input type="hidden" name="acta_id" value="<?= htmlspecialchars($acta['id']) ?>">
                <div style="margin-bottom:1em;">
                    <label>Nombre:</label>
                    <input type="text" name="return_first_name" value="<?= htmlspecialchars($acta['first_name']) ?>" readonly style="width:100%;padding:0.4em;border-radius:6px;border:1px solid #c7d0db;background:#f3f6fa;">
                </div>
                <div style="margin-bottom:1em;">
                    <label>Apellido:</label>
                    <input type="text" name="return_last_name" value="<?= htmlspecialchars($acta['last_name']) ?>" readonly style="width:100%;padding:0.4em;border-radius:6px;border:1px solid #c7d0db;background:#f3f6fa;">
                </div>
                <div style="margin-bottom:1em;">
                    <label>Fecha de devolución:</label>
                    <input type="date" name="return_date" required style="width:100%;padding:0.4em;border-radius:6px;border:1px solid #c7d0db;">
                </div>
                <div style="margin-bottom:1em;">
                    <label>Estado del equipo:</label>
                    <select name="return_condition" required style="width:100%;padding:0.4em;border-radius:6px;border:1px solid #c7d0db;">
                        <option value="">Seleccione</option>
                        <option value="Bueno">Bueno</option>
                        <option value="Regular">Regular</option>
                        <option value="Malo">Malo</option>
                    </select>
                </div>
                <div style="margin-bottom:1em;">
                    <label>Observaciones:</label>
                    <textarea name="return_observations" rows="2" style="width:100%;padding:0.4em;border-radius:6px;border:1px solid #c7d0db;resize:vertical;"></textarea>
                </div>
                <div class="firma-section" style="margin-bottom:1.5em;">
                    <div class="firma-box">
                        <canvas id="firmaDevolucion" width="250" height="60" style="border:1px solid #888;background:#fff;touch-action: none;"></canvas>
                        <div style="border-bottom:1px solid #888;margin-bottom:0.5em;"></div>
                        <span>Firma de quien devuelve</span>
                        <button type="button" onclick="clearFirmaDevolucion()" style="margin-top:0.5em;background:#e0e0e0;color:#215ba0;padding:0.3em 1em;border:none;border-radius:5px;cursor:pointer;">Limpiar firma</button>
                        <input type="hidden" name="firma_devolucion" id="firma_devolucion_input">
                    </div>
                    <div class="firma-box">
                        <img src="../assets/images/responsable.jpg" alt="Firma Área de TI" style="height:60px;display:block;margin:0 auto 0.5em auto;">
                        <div style="border-bottom:1px solid #888;margin-bottom:0.5em;"></div>
                        <span>Firma Área de TI</span>
                    </div>
                </div>
                <div style="text-align:center;">
                    <button type="submit" onclick="guardarFirmaDevolucion()" style="background:#2176ae;color:#fff;padding:0.7em 2em;border:none;border-radius:7px;font-size:1.08em;cursor:pointer;box-shadow:0 1px 4px rgba(33,118,174,0.10);">
                        Guardar devolución
                    </button>
                </div>
            </form>
        </div>
        <!-- Fin formulario devolución -->
    </div>
    <?php endif; ?>
</div>
<script>
    function mostrarActaFormulario(id) {
        window.location.href = '?id=<?= $vehicle_id ?>&acta_id=' + id;
    }
    window.addEventListener('DOMContentLoaded', function() {
        var formDiv = document.getElementById('acta-formulario');
        if (formDiv) formDiv.style.display = 'none';
        <?php if (isset($_GET['acta_id'])): ?>
        if (formDiv && '<?= $_GET['acta_id'] ?>' !== '') formDiv.style.display = 'block';
        <?php endif; ?>
    });
    // Devolución: mostrar/ocultar formulario
    function toggleDevolucionForm() {
        var check = document.getElementById('toggle-devolucion');
        var form = document.getElementById('devolucion-form');
        if (form) form.style.display = check.checked ? 'block' : 'none';
    }

    // Firma devolución
    let canvasDev = document.getElementById('firmaDevolucion');
    let ctxDev = canvasDev ? canvasDev.getContext('2d') : null;
    let drawingDev = false;
    if (canvasDev) {
        // Mouse events
        canvasDev.addEventListener('mousedown', function (e) {
            drawingDev = true;
            ctxDev.beginPath();
            ctxDev.moveTo(e.offsetX, e.offsetY);
        });
        canvasDev.addEventListener('mousemove', function (e) {
            if (drawingDev) {
                ctxDev.lineTo(e.offsetX, e.offsetY);
                ctxDev.strokeStyle = "#222";
                ctxDev.lineWidth = 2;
                ctxDev.stroke();
            }
        });
        canvasDev.addEventListener('mouseup', function () {
            drawingDev = false;
        });
        canvasDev.addEventListener('mouseleave', function () {
            drawingDev = false;
        });
        // Touch events
        canvasDev.addEventListener('touchstart', function (e) {
            if (e.targetTouches.length == 1) {
                let rect = canvasDev.getBoundingClientRect();
                let touch = e.targetTouches[0];
                drawingDev = true;
                ctxDev.beginPath();
                ctxDev.moveTo(touch.clientX - rect.left, touch.clientY - rect.top);
                e.preventDefault();
            }
        });
        canvasDev.addEventListener('touchmove', function (e) {
            if (drawingDev && e.targetTouches.length == 1) {
                let rect = canvasDev.getBoundingClientRect();
                let touch = e.targetTouches[0];
                ctxDev.lineTo(touch.clientX - rect.left, touch.clientY - rect.top);
                ctxDev.strokeStyle = "#222";
                ctxDev.lineWidth = 2;
                ctxDev.stroke();
                e.preventDefault();
            }
        });
        canvasDev.addEventListener('touchend', function (e) {
            drawingDev = false;
            e.preventDefault();
        });
    }
    function clearFirmaDevolucion() {
        if (ctxDev && canvasDev) ctxDev.clearRect(0, 0, canvasDev.width, canvasDev.height);
    }
    function guardarFirmaDevolucion() {
        if (canvasDev) document.getElementById('firma_devolucion_input').value = canvasDev.toDataURL("image/png");
    }
</script>
</body>
</html>
