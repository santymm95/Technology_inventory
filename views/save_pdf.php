<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cedula'], $_POST['pdf'])) {
    $cedula = preg_replace('/[^\w\-]/', '', $_POST['cedula']);
    $pdfData = base64_decode($_POST['pdf']);
    $dir = realpath(__DIR__ . '/../documents') . DIRECTORY_SEPARATOR . $cedula;
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $file = $dir . DIRECTORY_SEPARATOR . 'acta_de_devolucion.pdf';
    file_put_contents($file, $pdfData);
    echo 'OK';
} else {
    http_response_code(400);
    echo 'ERROR';
}
?>
