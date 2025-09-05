<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
include_once __DIR__ . '/../includes/conection.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo "Vehículo no encontrado.";
    exit;
}

// Obtener datos del vehículo
$sql = "SELECT v.placa, v.marca, v.modelo, v.id
        FROM vehicle v
        WHERE v.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$vehicle = $res->fetch_assoc();
$stmt->close();

if (!$vehicle) {
    echo "Vehículo no encontrado.";
    exit;
}

// Obtener usuarios activos
$users = [];
$user_sql = "SELECT * FROM users WHERE active = 1 ORDER BY first_name, last_name";
$user_res = $conn->query($user_sql);
while ($row = $user_res->fetch_assoc()) {
    $users[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Acta de Entrega de Vehículo</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .acta-container {
            max-width: 900px;
            margin: 2.5rem auto;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.09);
            padding: 2.5rem 2rem;
        }

        .acta-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .acta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }

        .acta-table th,
        .acta-table td {
            text-align: left;
            padding: 0.6rem 1rem;
            border-bottom: 1px solid #eaeaea;
        }

        .acta-table th {
            color: #215ba0;
            background: #f7f9fb;
            width: 200px;
        }

        .politicas {
            background: #f8fafc;
            border-radius: 8px;
            padding: 1.2em 1em;
            margin-bottom: 2em;
            font-size: 1em;
        }

        .firma-section {
            margin-top: 2em;
            display: flex;
            gap: 2em;
            justify-content: space-between;
        }

        .firma-box {
            flex: 1;
            text-align: center;
            padding-top: 2em;
        }

        .pdf-header-table {
            width: 100%;
            border: none;
            text-align: center;
            margin-bottom: 1em;
            border: 1px solid #dbe4f3;
            margin-top: 0px;
        }

        .pdf-header-logo-img {
            max-width: 130px;
        }

        .pdf-header-title {
            text-align: center;
            font-size: 1.2em;
            font-weight: bold;
            color: #215ba0;
        }

        .pdf-header-info-table td {
            padding: 2px 0;
            font-size: 0.85em;
        }
    </style>
    <script>
        function fillUserData() {
            var users = <?php echo json_encode($users); ?>;
            var select = document.getElementById('user_id');
            var selectedId = select.value;
            var user = users.find(u => u.id == selectedId);
            if (user) {
                document.getElementById('user_first_name').textContent = user.first_name;
                document.getElementById('user_last_name').textContent = user.last_name;
                document.getElementById('user_document').textContent = user.document;
                document.getElementById('user_position').textContent = user.position;
                document.getElementById('user_department').textContent = user.department;
            } else {
                document.getElementById('user_first_name').textContent = '';
                document.getElementById('user_last_name').textContent = '';
                document.getElementById('user_document').textContent = '';
                document.getElementById('user_position').textContent = '';
                document.getElementById('user_department').textContent = '';
            }
        }
        document.addEventListener('DOMContentLoaded', function () {
            fillUserData();
        });
    </script>
</head>

<body>
    <?php include 'layout.php'; ?>
    <div class="main-content">
        <div class="acta-container">
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
                                <td><strong>Fecha:</strong> 06-06-2025</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <div style="text-align:justify;margin-bottom:2em;color:black;">
                Hoy, <?= date('d/m/Y') ?> Se hace entrega del vehículo propiedad de la empresa ACEMA INGENIERA SAS,
                quedando como a cargo total del cuidado y supervisión del vehículo que aquí se entrega, los días en que
                se le esté cediendo la movilidad fuera del horario laboral, y ante cualquier daño o desperfecto será el
                único responsable de cubrir los daños causados.

            </div>

            <form method="post" action="../controllers/assign_vehicle_acta.php" enctype="multipart/form-data">
                <input type="hidden" name="vehicle_id" value="<?= htmlspecialchars($id) ?>">
                <div style="margin-bottom:2em;">
                    <label for="user_id" style="font-weight:500;color:#215ba0;">Usuario responsable:</label>
                    <select name="user_id" id="user_id" required onchange="fillUserData()"
                        style="width:100%;padding:0.5em;border-radius:6px;border:1px solid #c7d0db;">
                        <option value="">Selecciona un usuario</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>">
                                <?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name'] . ' (' . $u['document'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <table class="acta-table">
                    <tr>
                        <th>Nombre(s)</th>
                        <td id="user_first_name"></td>
                    </tr>
                    <tr>
                        <th>Apellido(s)</th>
                        <td id="user_last_name"></td>
                    </tr>
                    <tr>
                        <th>Documento</th>
                        <td id="user_document"></td>
                    </tr>
                    <tr>
                        <th>Cargo</th>
                        <td id="user_position"></td>
                    </tr>
                    <tr>
                        <th>Departamento</th>
                        <td id="user_department"></td>
                    </tr>
                </table>
                <p style="text-align:justify;margin-bottom:2em;">
                    Quien declara recepción de este en buen estado y se compromete a cuidar del recurso y hacer uso de
                    él para los fines establecidos.<br>
                    Ambas partes, reconociéndose mutuamente capacidad legal para contratar y obligarse en los términos
                    del presente documento, acuerdan y establecen las siguientes cláusulas:
                </p>
                <p style="text-align:justify;margin-bottom:2em;">
                    <strong>CLAUSULA PRIMERA: OBJETO</strong><br>
                    ACEMA INGENIERÍA hace entrega a "El Recibidor" del siguiente vehículo.
                </p>
                <h3 style="color:#2176ae;margin-top:2em;">Datos del Vehículo</h3>
                <table class="acta-table">
                    <tr>
                        <th>Placa</th>
                        <td><?= htmlspecialchars($vehicle['placa']) ?></td>
                    </tr>
                    <tr>
                        <th>Marca</th>
                        <td><?= htmlspecialchars($vehicle['marca']) ?></td>
                    </tr>
                    <tr>
                        <th>Modelo</th>
                        <td><?= htmlspecialchars($vehicle['modelo']) ?></td>
                    </tr>
                    <tr>
                        <th>Foto del vehículo</th>
                        <td>
                            <?php
                            // Usa el mismo método que vehicle_list.php para buscar la imagen
                            $placaFolder = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $vehicle['placa']);
                            $photoDir = "../uploads/" . $placaFolder . "/";
                            $photoPattern = $photoDir . "foto_vehiculo.*";
                            $photoFiles = glob($photoPattern);
                            $photo = (count($photoFiles) > 0) ? $photoFiles[0] : "../assets/img/no-image.png";
                            $photoExt = strtolower(pathinfo($photo, PATHINFO_EXTENSION));
                            if ($photoExt === 'pdf'):
                            ?>
                                <a href="<?= htmlspecialchars($photo) ?>" target="_blank" style="color:#2176ae;text-decoration:underline;">Ver PDF</a>
                            <?php elseif (in_array($photoExt, ['jpg', 'jpeg', 'png', 'webp'])): ?>
                                <img src="<?= htmlspecialchars($photo) ?>" alt="Foto del vehículo" style="max-height:200px;border-radius:6px;border:1px solid #ccc;">
                            <?php else: ?>
                                <span style="color:#888;">No disponible</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                <!-- INFORMACIÓN Y ELEMENTOS ENTREGADO -->
                <div style="margin-bottom:2em;">
                    <label style="font-weight:500;color:#215ba0;">INFORMACIÓN Y ELEMENTOS ENTREGADO:</label>
                    <div
                        style="padding:0.5em 1em; display: flex;   gap: 0.5em; background: #f8fafc; border-radius: 6px; border: 1px solid #c7d0db;">
                        <label><input type="checkbox" name="elementos[matricula]" value="1"> Matrícula</label><br>
                        <label><input type="checkbox" name="elementos[soat]" value="1"> SOAT</label><br>
                        <label><input type="checkbox" name="elementos[runt]" value="1"> RUNT</label><br>
                        <label><input type="checkbox" name="elementos[llaves]" value="1"> Llaves</label><br>
                        <label><input type="checkbox" name="elementos[botiquin]" value="1"> Botiquín</label><br>
                        <label><input type="checkbox" name="elementos[extintor]" value="1"> Extintor</label><br>
                        <label><input type="checkbox" name="elementos[kit_carretera]" value="1"> Kit
                            Carretera</label><br>
                        <label><input type="checkbox" name="elementos[camaras]" value="1"> Cámaras</label><br>
                        <label><input type="checkbox" name="elementos[gps]" value="1"> GPS</label>
                    </div>
                </div>
                <div class="politicas" style="margin-bottom:2em;">
                    <div style="margin-bottom:1.5em;">
                        <span style="font-weight:bold;color:#215ba0;font-size:1.1em;">CLAUSULA SEGUNDA: RESPONSABILIDADES DEL RECIBIDOR</span>
                        <ul style="text-align: justify; margin-top:0.7em;">
                            <li><b>a)</b> <b>"El Recibidor"</b> se compromete a recibir el vehículo en las condiciones establecidas a entregarlo de igual forma y a hacer uso del mismo de manera adecuada y responsable dentro de las ciudades asignadas por ACEMA INGENIERÍA SAS.</li>
                            <li><b>b)</b> <b>"El Recibidor"</b> se compromete a Inspeccionar el vehículo antes de iniciar las labores diarias: Niveles, luces, llantas, posibles fugas, tablero instrumentos etc., y plasmar la información en el formato ACM-SGI-FO-012 PREOPERACIONAL DE CAMIONETA.</li>
                            <li><b>c)</b> <b>"El Recibidor"</b> se compromete a informar a ACEMA INGENIERÍA SAS de cualquier inconveniente o necesidad de mantenimiento que surja durante el uso del vehículo.</li>
                            <li><b>d)</b> <b>"El Recibidor"</b> se compromete a respetar los límites de velocidad establecidos por la legislación colombiana, teniendo presente que:
                                <ul>
                                    <li><b>i.</b> En vías urbanas y municipales, la velocidad máxima permitida es de 50 kph.</li>
                                    <li><b>ii.</b> En zonas escolares y residenciales, se debe mantener una velocidad de hasta 30 kph.</li>
                                    <li><b>iii.</b> En vías rurales, el límite de velocidad autorizado es de 80 kph.</li>
                                    <li><b>iv.</b> En carreteras nacionales y departamentales, se establece una velocidad máxima de 90 kph.</li>
                                </ul>
                            </li>
                            <li><b>e)</b> <b>"El Recibidor”</b> se compromete a informar de forma veraz su estado de salud.</li>
                            <li><b>f)</b> <b>"El Recibidor”</b> se compromete a respetar las normas de tránsito.</li>
                            <li><b>g)</b> <b>"El Recibidor”</b> se compromete a informar al dueño del vehículo, jefe inmediato y/o auxiliar de SST cualquier anomalía, incidente o accidente de tránsito que presente el vehículo por insignificante que parezca.</li>
                            <li><b>h)</b> <b>"El Recibidor”</b> se compromete a portar los documentos tanto del vehículo como los personales ordenados por la autoridad competente.</li>
                            <li><b>i)</b> <b>"El Recibidor”</b> se compromete a mantener el vehículo en óptimas condiciones de funcionamiento y limpieza.</li>
                            <li><b>j)</b> <b>"El Recibidor”</b> se compromete a cuidar y preservar el estado general del vehículo.</li>
                            <li><b>k)</b> <b>"El Recibidor”</b> se compromete a usar el cinturón de seguridad y exigir su uso a los ocupantes del vehículo que conduzca.</li>
                            <li><b>l)</b> <b>"El Recibidor”</b> se compromete a usar los dispositivos de comunicaciones solo cuando se encuentre detenido en lugar seguro y habilitado para estacionamiento, evitando que puedan causarle distracción y por consiguiente probables accidentes de tránsito.</li>
                            <li><b>m)</b> <b>"El Recibidor”</b> se compromete a usar los elementos de protección personal durante la conducción de vehículos en cumplimiento de la misión de la empresa.</li>
                            <li><b>n)</b> <b>"El Recibidor”</b> se compromete a asistir a las capacitaciones, charlas y eventos citados por la organización.</li>
                            <li><b>o)</b> <b>"El Recibidor”</b> se compromete a estar a paz y salvo con el pago de comparendos por infracciones de tránsito.</li>
                            <li><b>p)</b> <b>"El Recibidor”</b> se compromete a guardar el vehículo en un lugar seguro.</li>
                        </ul>
                    </div>
                    <div style="margin-bottom:1.5em;">
                        <span style="font-weight:bold;color:#215ba0;font-size:1.1em;">CLAUSULA TERCERA: RESPONSABILIDADES DE LA EMPRESA</span>
                        <ul style="text-align: justify; margin-top:0.7em;">
                            <li><b>a)</b> ACEMA INGENIERÍA SAS se compromete a entregar el vehículo en óptimas condiciones de funcionamiento.</li>
                            <li><b>b)</b> ACEMA INGENIERÍA SAS proporcionará el soporte técnico necesario para el mantenimiento preventivo o correctivo del vehículo, de acuerdo con los plazos establecidos y las necesidades que se presenten.</li>
                            <li><b>c)</b> ACEMA INGENIERÍA SAS llevará un registro detallado de las fechas de mantenimiento del vehículo, incluyendo cualquier reparación o servicio realizado.</li>
                            <li><b>d)</b> ACEMA INGENIERÍA SAS proporcionará a "El Recibidor" la información necesaria para realizar el seguimiento y mantenimiento adecuado del vehículo.</li>
                        </ul>
                    </div>
                    <div style="margin-bottom:1.5em;">
                        <span style="font-weight:bold;color:#215ba0;font-size:1.1em;">CLAUSULA CUARTA: HURTO, ACCIDENTE O DAÑOS</span>
                        <ul style="text-align: justify; margin-top:0.7em;">
                            <li><b>a)</b> En caso de presentarse hurto del vehículo se deberá instaurar de inmediato la denuncia ante la policía nacional y relatar los hechos e informar por correo electrónico a mariana.agudelo@acemaingenieria.com Gerente Administrativa, y gerencia@acemaingenieria.com Gerente General, adjuntando el denuncio.</li>
                            <li><b>b)</b> En caso de accidente o daño al vehículo, el empleado debe informar a la empresa y a las autoridades competentes de inmediato. El conductor deberá presentar un informe detallado del incidente y enviarlo por correo electrónico a mariana.agudelo@acemaingenieria.com Gerente Administrativa, y gerencia@acemaingenieria.com Gerente General. Si el daño es producto de negligencia o mal uso del vehículo, los costos asociados son deducidos de nómina.</li>
                        </ul>
                    </div>
                    <div style="margin-bottom:1.5em;">
                        <span style="font-weight:bold;color:#215ba0;font-size:1.1em;">CLAUSULA QUINTA: MULTAS</span>
                        <ul style="text-align: justify; margin-top:0.7em;">
                            <li><b>a)</b> El trabajador se compromete a asumir la responsabilidad por cualquier infracción de tránsito que cometa durante el período de arrendamiento del vehículo o cuando utilice vehículos de la empresa, incluyendo multas por exceso de velocidad, estacionamiento indebido, uso incorrecto de los documentos del vehículo, foto multas, etc. En caso de que el colaborador reciba una multa, deberá abonar dicha multa de forma inmediata, así como los gastos administrativos generados por el proceso igualmente.</li>
                            <li><b>b)</b> En caso de que la empresa pague una multa en nombre del trabajador, este se compromete a reembolsar el monto total de la multa a partir de la notificación formal de la misma, además de los posibles gastos administrativos generados. En caso de que el trabajador no cumpla con el reembolso dentro del plazo establecido, la empresa podrá imponer sanciones adicionales, que podrán incluir la retención de salarios o la terminación del contrato de trabajo, dependiendo de la gravedad de la infracción cometida.</li>
                        </ul>
                    </div>
                    <div style="margin-bottom:1.5em;">
                        <span style="font-weight:bold;color:#215ba0;font-size:1.1em;">CLAUSULA SÉPTIMA: CAMBIOS</span>
                        <ul style="text-align: justify; margin-top:0.7em;">
                            <li><b>a)</b> NO realizar cambios de carrocería, color, rines, cámara, u cualquier otro elemento sin previa autorización del jefe inmediato.</li>
                        </ul>
                    </div>
                    <div style="margin-bottom:1.5em;">
                        <span style="font-weight:bold;color:#215ba0;font-size:1.1em;">CLAUSULA OCTAVA: PROHIBICIONES</span>
                        <ul style="text-align: justify; margin-top:0.7em;">
                            <li><b>a)</b> NO TRANSPORTAR personal ajeno a la compañía, a menos de que este sea autorizado por su jefe inmediato.</li>
                            <li><b>b)</b> NO PRESTAR el vehículo a ninguna persona, cuando el directo responsable es Usted.</li>
                            <li><b>c)</b> NO consumir, portar o distribuir bebidas alcohólicas o sustancias psicoactivas en los sitios de trabajo, instalaciones, vehículos o áreas relacionadas con las operaciones de la empresa.</li>
                            <li><b>d)</b> NO USAR teléfonos móviles ni dispositivos electrónicos mientras se conduce, salvo cuando se utilicen sistemas de manos libres y sin distraer la atención del conductor.</li>
                            <li><b>e)</b> Queda expresamente prohibido el uso del vehículo para el transporte de bienes no relacionados con las actividades de la empresa, así como para fines personales, recreativos o cualquier otro propósito ajeno a las necesidades laborales autorizadas.</li>
                        </ul>
                    </div>
                    <div style="margin-bottom:1.5em;">
                        <span style="font-weight:bold;color:#215ba0;font-size:1.1em;">CLAUSULA NOVENA: VIGENCIA</span>
                        <ul style="text-align: justify; margin-top:0.7em;">
                            <li><b>a)</b> La presente acta de entrega tiene vigencia a partir de la fecha de entrega y se mantendrá en vigor hasta que ACEMA INGENIERÍA SAS considere que se ha cumplido con las condiciones establecidas o hasta que se acuerde la devolución o retiro del mismo.</li>
                        </ul>
                    </div>
                    <div style="margin-top:1em;">
                        En constancia de lo cual, ambas partes firman el presente documento en dos ejemplares, en el lugar y fecha arriba indicados.
                    </div>
                </div>
                <div class="firma-section">
                    <div class="firma-box">
                        <canvas id="firmaUsuario" width="250" height="60"
                            style="border:1px solid #888;background:#fff;touch-action: none;"></canvas>
                        <div style="border-bottom:1px solid #888;margin-bottom:0.5em;"></div>
                        <span>Firma Usuario Responsable</span>
                        <button type="button" onclick="clearFirma()"
                            style="margin-top:0.5em;background:#e0e0e0;color:#215ba0;padding:0.3em 1em;border:none;border-radius:5px;cursor:pointer;">Limpiar
                            firma</button>
                        <!-- Cambia el nombre del input para que el backend lo guarde como 'firma_entrega' -->
                        <input type="hidden" name="firma_entrega" id="firma_usuario_input">
                    </div>
                    <div class="firma-box">
                        <img src="../assets/images/responsable.jpg" alt="Firma Área de TI"
                            style="height:60px;display:block;margin:0 auto 0.5em auto;">
                        <div style="border-bottom:1px solid #888;margin-bottom:0.5em;"></div>
                        <span>Firma Área de TI</span>
                    </div>
                </div>
                <div style="margin-top:2em;text-align:center;">
                    <button type="submit" onclick="guardarFirma()"
                        style="background:#2176ae;color:#fff;padding:0.7em 2em;border:none;border-radius:7px;font-size:1.08em;cursor:pointer;box-shadow:0 1px 4px rgba(33,118,174,0.10);">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
        // Inicializar datos de usuario si ya hay uno seleccionado (por recarga)
        document.addEventListener('DOMContentLoaded', function () {
            fillUserData();
        });
        let canvas = document.getElementById('firmaUsuario');
        let ctx = canvas.getContext('2d');
        let drawing = false;

        // Mouse events
        canvas.addEventListener('mousedown', function (e) {
            drawing = true;
            ctx.beginPath();
            ctx.moveTo(e.offsetX, e.offsetY);
        });
        canvas.addEventListener('mousemove', function (e) {
            if (drawing) {
                ctx.lineTo(e.offsetX, e.offsetY);
                ctx.strokeStyle = "#222";
                ctx.lineWidth = 2;
                ctx.stroke();
            }
        });
        canvas.addEventListener('mouseup', function () {
            drawing = false;
        });
        canvas.addEventListener('mouseleave', function () {
            drawing = false;
        });

        // Touch events for tablet/mobile
        canvas.addEventListener('touchstart', function (e) {
            if (e.targetTouches.length == 1) {
                let rect = canvas.getBoundingClientRect();
                let touch = e.targetTouches[0];
                drawing = true;
                ctx.beginPath();
                ctx.moveTo(touch.clientX - rect.left, touch.clientY - rect.top);
                e.preventDefault();
            }
        });
        canvas.addEventListener('touchmove', function (e) {
            if (drawing && e.targetTouches.length == 1) {
                let rect = canvas.getBoundingClientRect();
                let touch = e.targetTouches[0];
                ctx.lineTo(touch.clientX - rect.left, touch.clientY - rect.top);
                ctx.strokeStyle = "#222";
                ctx.lineWidth = 2;
                ctx.stroke();
                e.preventDefault();
            }
        });
        canvas.addEventListener('touchend', function (e) {
            drawing = false;
            e.preventDefault();
        });

        function clearFirma() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        }

        function guardarFirma() {
            document.getElementById('firma_usuario_input').value = canvas.toDataURL("image/png");
        }
    </script>
</body>
</html>