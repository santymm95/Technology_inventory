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

// Obtener el número interno del equipo
$stmt = $conn->prepare("SELECT internal_number FROM devices WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($internal_number);
$stmt->fetch();
$stmt->close();

if (!$internal_number) {
    echo "Equipo no encontrado.";
    exit;
}

// Ruta de la carpeta de documentos
$docDir = realpath(__DIR__ . '/../documents');
$deviceDocDir = $docDir . DIRECTORY_SEPARATOR . $internal_number;
if (!is_dir($deviceDocDir)) {
    mkdir($deviceDocDir, 0777, true);
}

// Subida de archivo
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['pdf'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext === 'pdf') {
        $safeName = 'garantia_' . date('Ymd_His') . '_' . uniqid() . '.pdf';
        $dest = $deviceDocDir . DIRECTORY_SEPARATOR . $safeName;
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $msg = "Documento subido correctamente.";
        } else {
            $msg = "Error al subir el documento.";
        }
    } else {
        $msg = "Solo se permiten archivos PDF.";
    }
}

// Listar documentos
$docs = [];
foreach (glob($deviceDocDir . '/*.pdf') as $file) {
    $docs[] = basename($file);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Documentos del Equipo</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .doc-container {
            max-width: 700px;
            margin: 2.5rem auto;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.09);
            padding: 2.5rem 2rem;
        }
        .doc-list {
            margin-top: 2em;
        }
        .doc-list li {
            margin-bottom: 1em;
            font-size: 1.05em;
        }
        .doc-list a {
            color: #215ba0;
            text-decoration: underline;
        }
        .msg {
            margin-bottom: 1em;
            color: #2176ae;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <?php include 'layout.php'; ?>
    <div class="main-content">
        <div class="doc-container">
            <h2>Documentos Relacionados - Equipo <?= htmlspecialchars($internal_number) ?></h2>
            <?php if ($msg): ?>
                <div class="msg"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data" style="margin-bottom:2em;">
                <label>
                    Subir documentos PDF:
                    <input type="file" name="pdf" accept="application/pdf" required style="margin-top:0.5em;">
                </label>
                <button type="submit" style="margin-left:1em;background:#2176ae;color:#fff;padding:0.5em 1.5em;border:none;border-radius:6px;cursor:pointer;">Subir</button>
            </form>
            <h3 style="margin-top:2em;">Documentos guardados</h3>
            <?php if (empty($docs)): ?>
                <p>No hay documentos cargados para este equipo.</p>
            <?php else: ?>
                <ul class="doc-list">
                    <?php foreach ($docs as $doc): ?>
                        <li>
                            <a href="../documents/<?= rawurlencode($internal_number) ?>/<?= rawurlencode($doc) ?>" target="_blank">
                                <?= htmlspecialchars($doc) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <div style="margin-top:1.5em;">
                <a href="view_acta.php?id=<?= urlencode($id) ?>" style="background:#215ba0;color:#fff;padding:0.7em 2em;border:none;border-radius:7px;font-size:1.08em;cursor:pointer;text-decoration:none;display:inline-block;">
                    Ver Acta de Entrega
                </a>
            </div>
            <div style="margin-top:2em;">
                <a href="device_profile.php?id=<?= urlencode($id) ?>" style="color:#215ba0;text-decoration:underline;">&larr; Volver al perfil del equipo</a>
            </div>
        </div>
    </div>
</body>
</html>
