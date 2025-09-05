<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../views/index.php');
    exit;
}
include_once __DIR__ . '/../includes/conection.php';

// Verifica que TCPDF esté instalado
$tcpdfPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($tcpdfPath)) {
    die("Error: No se encuentra la librería TCPDF. Instala TCPDF con Composer: <br><code>composer require tecnickcom/tcpdf</code>");
}
require_once($tcpdfPath);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger datos del formulario
    $device_id = intval($_POST['device_id']);
    $user_id = intval($_POST['user_id']);
    $user_document = $_POST['user_document'] ?? '';
    $firma_usuario = $_POST['firma_usuario'] ?? null;
    $accesorios = isset($_POST['accesorios']) ? $_POST['accesorios'] : [];

    // Obtener datos del equipo
    $stmt = $conn->prepare("SELECT internal_number, serial, purchase_date FROM devices WHERE id = ?");
    $stmt->bind_param("i", $device_id);
    $stmt->execute();
    $stmt->bind_result($internal_number, $serial, $purchase_date);
    $stmt->fetch();
    $stmt->close();

    // Obtener datos del usuario
    $stmt = $conn->prepare("SELECT first_name, last_name, document, position, department FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($first_name, $last_name, $document, $position, $department);
    $stmt->fetch();
    $stmt->close();

    // Obtener más datos del equipo para el PDF (sin usar brand_id si no existe)
    $stmt = $conn->prepare("SELECT 
        d.internal_number, d.serial, d.purchase_date, 
        m.name AS modelo, 
        pt.name AS tipo_proveedor,
        p.name AS procesador, r.name AS ram, 
        s.name AS almacenamiento, gc.name AS tarjeta_grafica, 
        os.name AS sistema_operativo
        FROM devices d
        LEFT JOIN models m ON d.model_id = m.id
        LEFT JOIN provider_types pt ON d.provider_type_id = pt.id
        LEFT JOIN processors p ON d.processor_id = p.id
        LEFT JOIN rams r ON d.ram_id = r.id
        LEFT JOIN storages s ON d.storage_id = s.id
        LEFT JOIN graphics_cards gc ON d.graphics_card_id = gc.id
        LEFT JOIN operating_systems os ON d.os_id = os.id
        WHERE d.id = ?");
    $stmt->bind_param("i", $device_id);
    $stmt->execute();
    $stmt->bind_result(
        $internal_number,
        $serial,
        $purchase_date,
        $modelo,
        $tipo_proveedor,
        $procesador,
        $ram,
        $almacenamiento,
        $tarjeta_grafica,
        $sistema_operativo
    );
    $stmt->fetch();
    $stmt->close();

    // Marca: si NO existe la columna brand_id en devices, intenta obtener la marca desde modelo o deja vacío
    $marca = '';
    // Si la tabla models tiene una relación con brands, puedes intentar:
    $marca_stmt = $conn->prepare("SELECT b.name FROM models m LEFT JOIN brands b ON m.brand_id = b.id WHERE m.name = ?");
    if ($marca_stmt) {
        $marca_stmt->bind_param("s", $modelo);
        $marca_stmt->execute();
        $marca_stmt->bind_result($marca);
        $marca_stmt->fetch();
        $marca_stmt->close();
    }
    // Si tampoco existe esa relación, simplemente deja $marca = '';

    // Foto del equipo
    $photoPath = glob(realpath(__DIR__ . '/../uploads') . "/$internal_number/$internal_number.*");
    $photoUrl = (count($photoPath) > 0) ? $photoPath[0] : realpath(__DIR__ . '/../assets/img/no-image.png');

    // Crear carpeta documents/{document}
    $docBase = realpath(__DIR__ . '/../documents');
    $userDocDir = $docBase . DIRECTORY_SEPARATOR . $document;
    if (!is_dir($userDocDir)) {
        mkdir($userDocDir, 0777, true);
    }

    // Guardar firma del usuario como imagen PNG (si existe)
    $firmaFile = '';
    if ($firma_usuario && strpos($firma_usuario, 'data:image/png;base64,') === 0) {
        $firmaData = base64_decode(str_replace('data:image/png;base64,', '', $firma_usuario));
        $firmaFile = $userDocDir . DIRECTORY_SEPARATOR . 'firma_' . $document . '_' . date('Ymd_His') . '.png';
        file_put_contents($firmaFile, $firmaData);
    }

    // Crear la carpeta y guardar la firma del responsable
    $firma_usuario_path = null;
    if ($firma_usuario && $user_document) {
        $base_dir = __DIR__ . '/../documents';
        if (!is_dir($base_dir)) {
            mkdir($base_dir, 0777, true);
        }
        $user_dir = $base_dir . DIRECTORY_SEPARATOR . $user_document;
        if (!is_dir($user_dir)) {
            mkdir($user_dir, 0777, true);
        }
        $firma_usuario_path = $user_dir . DIRECTORY_SEPARATOR . 'firma_' . $user_document . '_' . date('Ymd_His') . '.png';
        $data = explode(',', $firma_usuario);
        if (isset($data[1])) {
            file_put_contents($firma_usuario_path, base64_decode($data[1]));
            @chmod($firma_usuario_path, 0666);
        }
    }

    // Construir HTML de accesorios para el PDF
    $accesorios_html = '';
    if (!empty($accesorios)) {
        $accesorios_html = '<h3>Accesorios Entregados</h3><ul>';
        foreach ($accesorios as $acc) {
            $accesorios_html .= '<li>' . htmlspecialchars($acc) . '</li>';
        }
        $accesorios_html .= '</ul>';
    } else {
        $accesorios_html = '<h3>Accesorios Entregados</h3><em>No se entregaron accesorios.</em>';
    }

    // Lee el CSS desde un archivo externo para el PDF
    $pdfCss = '';
    $cssFile = __DIR__ . '/../assets/css/pdf_acta.css';
    if (file_exists($cssFile)) {
        $pdfCss = '<style>' . file_get_contents($cssFile) . '</style>';
    }

    // Encabezado PDF igual al de la imagen adjunta (solo en el PDF)
    $headerHtml = '
    <table class="pdf-header-table">
        <tr>
            <td class="pdf-header-logo">
                <img src="' . realpath(__DIR__ . '/../assets/images/logo.png') . '" class="pdf-header-logo-img">
                <span class="pdf-header-ingenieria"></span>
            </td>
            <td class="pdf-header-title">
                ACTA DE ENTREGA Y DEVOLUCIÓN<br>
                DE EQUIPOS DE CÓMPUTO<br>
                
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
                        <td><strong>Fecha:</strong> 17-05-2024</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    ';

    $fecha_actual = date('d/m/Y');
    $intro = "Hoy, $fecha_actual el departamento de Tecnología e Información (TI), mediante el siguiente documento realiza la entrega formal de los equipos e insumos tecnológicos asignados para el cumplimiento de las actividades laborales al colaborador:";

    // Obtener la observación del formulario
    $observacion = isset($_POST['observacion']) ? trim($_POST['observacion']) : '';

    // Datos de devolución (pueden venir vacíos)
    $fecha_devolucion = isset($_POST['fecha_devolucion']) ? $_POST['fecha_devolucion'] : null;
    $estado_devolucion = isset($_POST['estado_devolucion']) ? $_POST['estado_devolucion'] : null;
    $observacion_devolucion = isset($_POST['observacion_devolucion']) ? trim($_POST['observacion_devolucion']) : null;

    // Nombre de quien entrega y recibe (puedes ajustar según tu lógica)
    $entregado_por = isset($_SESSION['user']['name']) ? $_SESSION['user']['name'] : '';
    $recibido_por = $first_name . ' ' . $last_name;

    // Guardar accesorios como texto separado por coma
    $accesorios_str = !empty($accesorios) ? implode(', ', $accesorios) : '';

    // Guardar en la tabla actas
    // Deben ser 9 parámetros: 2 int, 7 string (fecha_entrega es NOW())
    $stmt = $conn->prepare("INSERT INTO actas 
        (device_id, user_id, fecha_entrega, accesorios, observacion, fecha_devolucion, estado_devolucion, observacion_devolucion, entregado_por, recibido_por) 
        VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?)");
    // 2 int, 7 string
    $stmt->bind_param(
        "issssssss",
        $device_id,
        $user_id,
        $accesorios_str,
        $observacion,
        $fecha_devolucion,
        $estado_devolucion,
        $observacion_devolucion,
        $entregado_por,
        $recibido_por
    );
    $stmt->execute();
    $stmt->close();

    // Alerta y redirección con JS
    echo '<script>
        alert("✅ Registro exitoso. El acta de entrega ha sido registrada correctamente.");
        window.location.href = "http://localhost/acema2/views/views_divices.php";
    </script>';
    exit;
} else {
    header('Location: ../views/ti.php');
    exit;
}
