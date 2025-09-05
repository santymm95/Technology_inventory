<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHMailer\Exception;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

// Cargar el archivo .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$mensaje = "";

// Comprobar si el formulario fue enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $mail = new PHPMailer(true);

    try {
        // Configuración SMTP para Gmail
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['MAIL_USERNAME']; // Correo de Gmail
        $mail->Password = $_ENV['MAIL_PASSWORD']; // Contraseña de aplicación
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Configuración de correo
        $mail->setFrom($_ENV['MAIL_USERNAME'], 'Tu Nombre');
        $mail->addAddress('destinatario@dominio.com', 'Destinatario'); // Dirección del destinatario

        $mail->isHTML(true);
        $mail->Subject = 'Correo de prueba desde Gmail';
        $mail->Body    = 'Este es un correo de prueba usando PHPMailer con Gmail.';
        $mail->AltBody = 'Este es un correo de prueba usando PHPMailer con Gmail.';

        // Enviar el correo
        $mail->send();
        $mensaje = '✅ Correo enviado con éxito.';
    } catch (Exception $e) {
        $mensaje = '❌ Error al enviar: ' . $mail->ErrorInfo;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correo de prueba</title>
</head>
<body>
    <h1>Correo de prueba</h1>
    <p><?= $mensaje ?></p>
    <form method="POST" action="">
        <button type="submit">Enviar Correo</button>
    </form>
</body>
</html>
