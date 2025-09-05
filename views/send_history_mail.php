<?php
// Cambia la ruta del autoload de Composer para que apunte correctamente a la raíz del proyecto
require_once __DIR__ . '/../vendor/autoload.php'; // Ajusta el path si tu vendor está en la raíz de acema
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Configuración de la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "acema_db";

// Configuración del correo
$to = "santymm95@gmail.com"; // Corrige: agrega .com al correo real
$subject = "Historial de Registros de Asistencia";
$body = "Adjunto el historial de registros de asistencia en formato CSV.";

// Generar el CSV
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT 
                CONCAT(admin.first_name, ' ', admin.last_name) AS revisado_por,
                ar.updated_at,
                CONCAT(u.first_name, ' ', u.last_name) AS empleado,
                pr.name AS proyecto,
                ar.date,
                ar.time_in,
                ar.time_out,
                ar.extra_hours,
                ar.total_hours,
                ar.estado
            FROM attendance_records ar
            LEFT JOIN users u ON ar.user_id = u.id
            LEFT JOIN projects pr ON u.project_id = pr.id
            LEFT JOIN users admin ON ar.updated_by = admin.id
            ORDER BY ar.date DESC, ar.updated_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Crear el archivo CSV en memoria con BOM UTF-8 para soportar tildes y caracteres especiales
    $csv = fopen('php://temp', 'w+');
    // Escribir BOM UTF-8
    fwrite($csv, "\xEF\xBB\xBF");
    fputcsv($csv, ['Revisado por', 'Fecha de Revisión', 'Empleado', 'Proyecto', 'Fecha', 'Hora Entrada', 'Hora Salida', 'Horas Extras', 'Total Horas', 'Estado']);
    foreach ($records as $row) {
        fputcsv($csv, [
            $row['revisado_por'],
            $row['updated_at'],
            $row['empleado'],
            $row['proyecto'],
            $row['date'],
            $row['time_in'],
            $row['time_out'],
            $row['extra_hours'],
            $row['total_hours'],
            $row['estado']
        ]);
    }
    rewind($csv);
    $csv_content = stream_get_contents($csv);
    fclose($csv);

    // Enviar correo con PHPMailer
    $mail = new PHPMailer(true);
    try {
        // Configuración SMTP (ajusta según tu servidor)
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // <-- Cambia esto por el host real de tu proveedor de correo
        $mail->SMTPAuth = true;
        $mail->Username = 'santymm95@gmail.com'; // <-- Cambia esto por tu usuario real
        $mail->Password = 'urik lzlu uobv aaus'; // <-- Cambia esto por tu contraseña real
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('santymm95@gmail.com', 'ACEMA ERP'); // <-- Cambia esto por tu correo real
        $mail->addAddress($to);

        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->addStringAttachment($csv_content, 'historial_asistencia.csv', 'base64', 'text/csv');

        $mail->send();
        echo "<script>alert('Correo enviado correctamente');window.location.href='review_history.php';</script>";
    } catch (Exception $e) {
        echo "<script>alert('Error al enviar el correo: {$mail->ErrorInfo}');window.location.href='review_history.php';</script>";
    }
} catch (PDOException $e) {
    echo "<script>alert('Error de base de datos: " . addslashes($e->getMessage()) . "');window.location.href='review_history.php';</script>";
}
