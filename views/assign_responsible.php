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

// Obtener datos del equipo
$sql = "SELECT d.internal_number, d.serial, m.name AS modelo, d.purchase_date, pt.name AS tipo_proveedor, b.name AS marca,
               gc.name AS tarjeta_grafica, os.name AS sistema_operativo, s.name AS almacenamiento, r.name AS ram
        FROM devices d
        LEFT JOIN models m ON d.model_id = m.id
        LEFT JOIN provider_types pt ON d.provider_type_id = pt.id
        LEFT JOIN brands b ON d.brand_id = b.id
        LEFT JOIN graphics_cards gc ON d.graphics_card_id = gc.id
        LEFT JOIN operating_systems os ON d.os_id = os.id
        LEFT JOIN storages s ON d.storage_id = s.id
        LEFT JOIN rams r ON d.ram_id = r.id
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
    <title>Acta de Entrega de Equipo</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .acta-container {
            max-width: 900px;
            margin: 2.5rem auto;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.09);
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
        .acta-table th, .acta-table td {
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

        .acta-table {
    width: 100%;
    margin: auto;
    border-collapse: collapse;
    margin-bottom: 2em;
    font-size: 0.95em;
    
}

.acta-table th, .acta-table td {
    border: 1px solid #dbe4f3;
    padding: 10px;
    text-align: left;
}

.acta-table th {
    background-color: #ecf2fc;
    color: #215ba0;
    width: 200px;
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

        function buscarResponsableEquipo() {
            var numeroInterno = document.getElementById('buscar_numero_interno').value.trim();
            var inputNombre = document.getElementById('nombre_responsable_devolucion');
            if (!numeroInterno) {
                inputNombre.value = '';
                return;
            }
            // AJAX para buscar responsable por número interno
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '../controllers/ajax_get_responsable.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    inputNombre.value = xhr.responseText || 'No asignado';
                } else {
                    inputNombre.value = 'Error al buscar';
                }
            };
            xhr.send('numero_interno=' + encodeURIComponent(numeroInterno));
        }
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
                    DE EQUIPOS DE CÓMPUTO
                </td>
                <td class="pdf-header-info">
                    <table class="pdf-header-info-table">
                        <tr>
                            <td><strong>Código:</strong> ACM-ADM-TI-FO-001</td>
                        </tr>
                        <tr>
                            <td><strong>Versión:</strong> 003</td>
                        </tr>
                        <tr>
                           <td><strong>Fecha:</strong>17-05-2024</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
            <div style="text-align:justify;margin-bottom:2em;color:black;">
                Hoy, <?= date('d/m/Y') ?> el departamento de Tecnología e Información (TI), mediante el siguiente documento realiza la entrega formal de los equipos e insumos tecnológicos asignados para el cumplimiento de las actividades laborales al colaborador:
            </div>
            <form method="post" action="../controllers/assign_responsible.php" target="_blank" enctype="multipart/form-data">
                <input type="hidden" name="device_id" value="<?= htmlspecialchars($id) ?>">
                <div style="margin-bottom:2em;">
                    <label for="user_id" style="font-weight:500;color:#215ba0;">Usuario responsable:</label>
                    <select name="user_id" id="user_id" required onchange="fillUserData()" style="width:100%;padding:0.5em;border-radius:6px;border:1px solid #c7d0db;">
                        <option value="">Selecciona un usuario</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name'] . ' (' . $u['document'] . ')') ?></option>
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
                    Quien declara recepción de estos en buen estado y se compromete a cuidar de los recursos y hacer uso de ellos para los fines establecidos.<br>
                    Ambas partes, reconociéndose mutuamente capacidad legal para contratar y obligarse en los términos del presente documento, acuerdan y establecen las siguientes cláusulas:
                </p>
                <p style="text-align:justify;margin-bottom:2em;">
                    <strong>CLAUSULA PRIMERA: OBJETO</strong><br>
                    ACEMA INGENIERÍA hace entrega a "El Recibidor" del siguiente equipo y accesorios.
                </p>
                <h3 style="color:#2176ae;margin-top:2em;">Datos del Equipo</h3>
                <table class="acta-table">
                    <tr>
                        <th>Marca</th>
                        <td><?= htmlspecialchars($device['marca']) ?></td>
                    </tr>
                    <tr>
                        <th>Número Interno</th>
                        <td><?= htmlspecialchars($device['internal_number']) ?></td>
                    </tr>
                    <tr>
                        <th>Serial</th>
                        <td><?= htmlspecialchars($device['serial']) ?></td>
                    </tr>
                    <tr>
                        <th>Modelo</th>
                        <td><?= htmlspecialchars($device['modelo']) ?></td>
                    </tr>
                    <tr>
                        <th>RAM</th>
                        <td><?= htmlspecialchars($device['ram']) ?></td>
                    </tr>
                    <tr>
                        <th>Almacenamiento</th>
                        <td><?= htmlspecialchars($device['almacenamiento']) ?></td>
                    </tr>
                    <!-- <tr>
                        <th>Fecha de Compra</th>
                        <td><?= htmlspecialchars($device['purchase_date']) ?></td>
                    </tr> -->
                    <tr>
                        <th>Tipo de equipo</th>
                        <td><?= htmlspecialchars($device['tipo_proveedor']) ?></td>
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
                        <th>Foto del equipo</th>
                        <td>
                            <?php
                            $photoDir = realpath(__DIR__ . '/../uploads') . DIRECTORY_SEPARATOR . $device['internal_number'];
                            $photo = '';
                            if (is_dir($photoDir)) {
                                $files = glob($photoDir . DIRECTORY_SEPARATOR . $device['internal_number'] . '.*');
                                if ($files && count($files) > 0) {
                                    $photo = '../uploads/' . $device['internal_number'] . '/' . basename($files[0]);
                                }
                            }
                            if ($photo):
                            ?>
                                <img src="<?= htmlspecialchars($photo) ?>" alt="Foto del equipo" style=" ;max-height:200px;border-radius:6px;border:1px solid #ccc;">
                            <?php else: ?>
                                <span style="color:#888;">No disponible</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                <!-- Opción de accesorios -->
                <div style="margin-bottom:2em;">
                    <label style="font-weight:500;color:#215ba0;">Accesorios entregados:</label>
                    <div style="padding:0.5em 1em; display: flex;   gap: 0.5em; background: #f8fafc; border-radius: 6px; border: 1px solid #c7d0db; ">
                        <label><input type="checkbox" name="accesorios[]" value="Cargador"> Cargador</label><br>
                        <label><input type="checkbox" name="accesorios[]" value="Mouse"> Mouse</label><br>
                        <label><input type="checkbox" name="accesorios[]" value="Bolso/Funda"> Bolso/Funda</label><br>
                        <label><input type="checkbox" name="accesorios[]" value="Base"> Base</label><br>
                        <label><input type="checkbox" name="accesorios[]" value="Teclado"> Teclado</label><br>
                        <label><input type="checkbox" name="accesorios[]" value="Cable HDMI"> Cable HDMI</label><br>
                        <label><input type="checkbox" name="accesorios[]" value="Otro"> Otro</label>
                    </div>
                </div>
                <div style="margin-bottom:2em;">
                    <label for="observacion" style="font-weight:500;color:#215ba0;">Observaciones:</label>
                    <textarea name="observacion" id="observacion" rows="2" style="width:100%;padding:0.4em;border-radius:6px;border:1px solid #c7d0db;resize:vertical;font-size:0.95em;" placeholder="Ingrese aquí cualquier observación relevante..."></textarea>
                </div>
                <div class="politicas" style="margin-bottom:2em;">
                   
                    <ul style="list-style:none;padding-left:0;">
                        <li>
                            <strong>CLAUSULA SEGUNDA: RESPONSABILIDADES DEL RECIBIDOR</strong>
                            <ul style="text-align: justify;">
                                <li>a) "El Recibidor" se compromete a recibir los equipos tecnológicos en las condiciones establecidas, a entregarlo de igual forma y a hacer uso del mismo de manera adecuada y responsable dentro de las instalaciones de ACEMA INGENIERÍA.</li>
                                <li>b) "El Recibidor" se compromete a informar a ACEMA INGENIERÍA de cualquier inconveniente o necesidad de mantenimiento que surja durante el uso de los equipos tecnológicos dentro de las instalaciones.</li>
                            </ul>
                        </li>
                        <li>
                            <strong>CLAUSULA TERCERA: RESPONSABILIDADES DE LA EMPRESA</strong>
                            <ul style="text-align: justify;">
                                <li>a) ACEMA INGENIERÍA se compromete a entregar los equipos tecnológicos en condiciones óptimas de funcionamiento.</li>
                                <li>b) ACEMA INGENIERÍA proporcionará el soporte técnico necesario para el mantenimiento de los equipos tecnológicos, de acuerdo con los plazos establecidos y las necesidades dentro de las instalaciones.</li>
                                <li>c) Llevará un registro detallado de las fechas de mantenimiento de los equipos tecnológicos, incluyendo cualquier reparación o servicio realizado dentro de las instalaciones de ACEMA INGENIERÍA.</li>
                                <li>d) ACEMA INGENIERÍA proporcionará a "El Recibidor" la información necesaria para realizar el seguimiento y mantenimiento adecuado de los equipos tecnológicos dentro de las instalaciones.</li>
                            </ul>
                        </li>
                        <li>
                            <strong>CLAUSULA CUARTA: EXTRAVIO O MAL USO</strong>
                            <ul style="text-align: justify;">
                                <li>a) En caso de presentarse hurto o pérdida de algún equipo se deberá instaurar de inmediato la denuncia ante la policía nacional y relatar los hechos e informar por correo electrónico a Santiago.montoya@acemaingenieria.com del área de tecnología de la información (TI), adjuntando el denuncio.</li>
                                <li>b) Si se evidencia que al equipo de cómputo o elementos de tecnología asignados se le ha dado un mal uso, se iniciará un proceso disciplinario, y si se evidencia un daño por tal motivo, se aplicará un descuento por nómina por el mantenimiento de este.</li>
                            </ul>
                        </li>
                        <li>
                            <strong>CLAUSULA QUINTA: SOFTWARE / HARDWARE</strong>
                            <ul style="text-align: justify;">
                                <li>a) NO se borrará, deshabilitará o sobrescribirá el software instalado en el equipo de cómputo asignado, esto incluye: office, sistema operativo, antivirus, cortafuegos o servicios de actualización automática.</li>
                                <li>b) NO se descargará de internet, ni instalará ningún software que no se encuentre debidamente autorizado para su uso en el equipo de cómputo asignado. De requerir software se solicitará a la oficina de tecnologías de la información para su autorización e instalación.</li>
                            </ul>
                        </li>
                        <li>
                            <strong>CLAUSULA SEXTA: VIGENCIA</strong>
                            <ul style="text-align: justify;">
                                <li>La presente acta de entrega y seguimiento de equipos electrónicos tiene vigencia a partir de la fecha de entrega y se mantendrá en vigor hasta que ACEMA INGENIERÍA considere que se ha cumplido con las condiciones establecidas o hasta que se acuerde la devolución o retiro de los equipos tecnológicos.</li>
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
                        <canvas id="firmaUsuario" width="250" height="60" style="border:1px solid #888;background:#fff;touch-action: none;"></canvas>
                        <div style="border-bottom:1px solid #888;margin-bottom:0.5em;"></div>
                        <span>Firma Usuario Responsable</span>
                        <button type="button" onclick="clearFirma()" style="margin-top:0.5em;background:#e0e0e0;color:#215ba0;padding:0.3em 1em;border:none;border-radius:5px;cursor:pointer;">Limpiar firma</button>
                        <input type="hidden" name="firma_usuario" id="firma_usuario_input">
                        <input type="hidden" name="user_document" id="user_document_input">
                    </div>
                    <div class="firma-box">
                        <img src="../assets/images/responsable.jpg" alt="Firma Área de TI" style="height:60px;display:block;margin:0 auto 0.5em auto;">
                        <div style="border-bottom:1px solid #888;margin-bottom:0.5em;"></div>
                        <span>Firma Área de TI</span>
                    </div>
                </div>
                <div style="margin-top:2em;text-align:center;">
                    <button type="submit" onclick="guardarFirma()" style="background:#2176ae;color:#fff;padding:0.7em 2em;border:none;border-radius:7px;font-size:1.08em;cursor:pointer;box-shadow:0 1px 4px rgba(33,118,174,0.10);">
                        Guardar
                    </button>
                </div>
                <!-- Formulario de devolución -->
            </form>
        </div>
    </div>
    <script>
        // Inicializar datos de usuario si ya hay uno seleccionado (por recarga)
        document.addEventListener('DOMContentLoaded', function() {
            fillUserData();
        });
        let canvas = document.getElementById('firmaUsuario');
        let ctx = canvas.getContext('2d');
        let drawing = false, lastX = 0, lastY = 0;

        // Mouse events
        canvas.addEventListener('mousedown', function(e) {
            drawing = true;
            ctx.beginPath();
            ctx.moveTo(e.offsetX, e.offsetY);
        });
        canvas.addEventListener('mousemove', function(e) {
            if (drawing) {
                ctx.lineTo(e.offsetX, e.offsetY);
                ctx.strokeStyle = "#222";
                ctx.lineWidth = 2;
                ctx.stroke();
            }
        });
        canvas.addEventListener('mouseup', function() {
            drawing = false;
        });
        canvas.addEventListener('mouseleave', function() {
            drawing = false;
        });

        // Touch events for tablet/mobile
        canvas.addEventListener('touchstart', function(e) {
            if (e.targetTouches.length == 1) {
                let rect = canvas.getBoundingClientRect();
                let touch = e.targetTouches[0];
                drawing = true;
                ctx.beginPath();
                ctx.moveTo(touch.clientX - rect.left, touch.clientY - rect.top);
                e.preventDefault();
            }
        });
        canvas.addEventListener('touchmove', function(e) {
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
        canvas.addEventListener('touchend', function(e) {
            drawing = false;
            e.preventDefault();
        });

        function clearFirma() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        }

        function guardarFirma() {
            document.getElementById('firma_usuario_input').value = canvas.toDataURL("image/png");
            // Guardar el documento del usuario seleccionado para el backend
            var users = <?php echo json_encode($users); ?>;
            var select = document.getElementById('user_id');
            var selectedId = select.value;
            var user = users.find(u => u.id == selectedId);
            if (user) {
                document.getElementById('user_document_input').value = user.document;
            } else {
                document.getElementById('user_document_input').value = '';
            }
        }

        // Devolución toggle
        function toggleDevolucionForm() {
            var check = document.getElementById('devolucion_check');
            var form = document.getElementById('devolucion_form');
            form.style.display = check.checked ? 'block' : 'none';
            if (check.checked) {
                // Poner el nombre del responsable seleccionado
                var nombre = document.getElementById('user_first_name').textContent + ' ' + document.getElementById('user_last_name').textContent;
                document.getElementById('nombre_responsable_devolucion').value = nombre.trim();
            }
        }

        // Firma recibidor devolución
        let canvasRecibidor = document.getElementById('firmaRecibidor');
        let ctxRecibidor = canvasRecibidor ? canvasRecibidor.getContext('2d') : null;
        let drawingRecibidor = false;
        if (canvasRecibidor) {
            canvasRecibidor.addEventListener('mousedown', function(e) {
                drawingRecibidor = true;
                ctxRecibidor.beginPath();
                ctxRecibidor.moveTo(e.offsetX, e.offsetY);
            });
            canvasRecibidor.addEventListener('mousemove', function(e) {
                if (drawingRecibidor) {
                    ctxRecibidor.lineTo(e.offsetX, e.offsetY);
                    ctxRecibidor.strokeStyle = "#222";
                    ctxRecibidor.lineWidth = 2;
                    ctxRecibidor.stroke();
                }
            });
            canvasRecibidor.addEventListener('mouseup', function() { drawingRecibidor = false; });
            canvasRecibidor.addEventListener('mouseleave', function() { drawingRecibidor = false; });
            canvasRecibidor.addEventListener('touchstart', function(e) {
                if (e.targetTouches.length == 1) {
                    let rect = canvasRecibidor.getBoundingClientRect();
                    let touch = e.targetTouches[0];
                    drawingRecibidor = true;
                    ctxRecibidor.beginPath();
                    ctxRecibidor.moveTo(touch.clientX - rect.left, touch.clientY - rect.top);
                    e.preventDefault();
                }
            });
            canvasRecibidor.addEventListener('touchmove', function(e) {
                if (drawingRecibidor && e.targetTouches.length == 1) {
                    let rect = canvasRecibidor.getBoundingClientRect();
                    let touch = e.targetTouches[0];
                    ctxRecibidor.lineTo(touch.clientX - rect.left, touch.clientY - rect.top);
                    ctxRecibidor.strokeStyle = "#222";
                    ctxRecibidor.lineWidth = 2;
                    ctxRecibidor.stroke();
                    e.preventDefault();
                }
            });
            canvasRecibidor.addEventListener('touchend', function(e) { drawingRecibidor = false; e.preventDefault(); });
        }
        function clearFirmaRecibidor() {
            if (ctxRecibidor) ctxRecibidor.clearRect(0, 0, canvasRecibidor.width, canvasRecibidor.height);
        }

        // Firma entrega devolución
        let canvasEntrega = document.getElementById('firmaEntrega');
        let ctxEntrega = canvasEntrega ? canvasEntrega.getContext('2d') : null;
        let drawingEntrega = false;
        if (canvasEntrega) {
            canvasEntrega.addEventListener('mousedown', function(e) {
                drawingEntrega = true;
                ctxEntrega.beginPath();
                ctxEntrega.moveTo(e.offsetX, e.offsetY);
            });
            canvasEntrega.addEventListener('mousemove', function(e) {
                if (drawingEntrega) {
                    ctxEntrega.lineTo(e.offsetX, e.offsetY);
                    ctxEntrega.strokeStyle = "#222";
                    ctxEntrega.lineWidth = 2;
                    ctxEntrega.stroke();
                }
            });
            canvasEntrega.addEventListener('mouseup', function() { drawingEntrega = false; });
            canvasEntrega.addEventListener('mouseleave', function() { drawingEntrega = false; });
            canvasEntrega.addEventListener('touchstart', function(e) {
                if (e.targetTouches.length == 1) {
                    let rect = canvasEntrega.getBoundingClientRect();
                    let touch = e.targetTouches[0];
                    drawingEntrega = true;
                    ctxEntrega.beginPath();
                    ctxEntrega.moveTo(touch.clientX - rect.left, touch.clientY - rect.top);
                    e.preventDefault();
                }
            });
            canvasEntrega.addEventListener('touchmove', function(e) {
                if (drawingEntrega && e.targetTouches.length == 1) {
                    let rect = canvasEntrega.getBoundingClientRect();
                    let touch = e.targetTouches[0];
                    ctxEntrega.lineTo(touch.clientX - rect.left, touch.clientY - rect.top);
                    ctxEntrega.strokeStyle = "#222";
                    ctxEntrega.lineWidth = 2;
                    ctxEntrega.stroke();
                    e.preventDefault();
                }
            });
            canvasEntrega.addEventListener('touchend', function(e) { drawingEntrega = false; e.preventDefault(); });
        }
        function clearFirmaEntrega() {
            if (ctxEntrega) ctxEntrega.clearRect(0, 0, canvasEntrega.width, canvasEntrega.height);
        }

        // Guardar firmas de devolución al enviar
        document.querySelector('form').addEventListener('submit', function() {
            if (ctxRecibidor) {
                document.getElementById('firma_recibidor_input').value = canvasRecibidor.toDataURL("image/png");
            }
            if (ctxEntrega) {
                document.getElementById('firma_entrega_input').value = canvasEntrega.toDataURL("image/png");
            }
        });

        function setNombreResponsableDevolucion() {
            var userFirstName = document.getElementById('user_first_name');
            var userLastName = document.getElementById('user_last_name');
            var nombre = '';
            if (userFirstName && userLastName) {
                nombre = userFirstName.textContent + ' ' + userLastName.textContent;
            }
            var inputNombre = document.getElementById('nombre_responsable_devolucion');
            if (inputNombre) {
                inputNombre.value = nombre.trim();
            }
        }

        // Llama a esta función cuando se seleccione usuario o se active el check de devolución
        document.getElementById('user_id').addEventListener('change', setNombreResponsableDevolucion);
        document.getElementById('devolucion_check').addEventListener('change', setNombreResponsableDevolucion);
    </script>
</body>
</html>
