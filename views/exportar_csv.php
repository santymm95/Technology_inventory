<?php
// Incluir el autoload de Composer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "acema_db";

// Conexión a la base de datos
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener fechas de los parámetros
$startDate = $_POST['startDate'];
$endDate = $_POST['endDate'];

// Consulta SQL para obtener los datos filtrados por fecha
$sql = "SELECT 
            u.first_name, 
            u.last_name, 
            p.name AS project_name,
            DATE(wr.timestamp) AS fecha,
            MAX(CASE WHEN wr.entry_or_exit = 1 THEN wr.timestamp END) AS entry_time,
            MAX(CASE WHEN wr.entry_or_exit = 0 THEN wr.timestamp END) AS exit_time
        FROM work_register wr
        JOIN users u ON wr.user_id = u.id
        JOIN projects p ON wr.project_id = p.id
        WHERE (DATE(wr.timestamp) BETWEEN '$startDate' AND '$endDate' OR ('$startDate' = '' AND '$endDate' = ''))
        GROUP BY u.id, p.id, fecha";

$result = $conn->query($sql);

// Crear el archivo CSV
$filename = "registro_horas_" . $startDate . "_a_" . $endDate . ".csv";
$file = fopen('php://temp', 'w');

// Escribir encabezados
$headers = ['Usuario', 'Proyecto', 'Fecha', 'Entrada', 'Salida', 'Franja', 'Extras', 'Total'];
fputcsv($file, $headers);

// Escribir los datos
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $entryTime = strtotime($row['entry_time']);
        $exitTime = !empty($row['exit_time']) ? strtotime($row['exit_time']) : null;

        $entryTimeFormatted = date('H:i', $entryTime);
        $exitTimeFormatted = !empty($exitTime) ? date('H:i', $exitTime) : "En proceso";

        $workedHoursTotal = 0;
        if ($exitTime) {
            $workedDuration = $exitTime - $entryTime;
            $workedHoursTotal = gmdate("H:i", max(0, $workedDuration));
        }

        // Franja horaria 07:00 a 17:00
        $fechaBase = date('Y-m-d', $entryTime);
        $workStart = strtotime($fechaBase . ' 07:00:00');
        $workEnd = strtotime($fechaBase . ' 17:00:00');

        // Horas dentro de la franja
        $workedHoursInFranja = 0;
        if ($exitTime) {
            $startWithinFranja = max($entryTime, $workStart);
            $endWithinFranja = min($exitTime, $workEnd);
            if ($endWithinFranja > $startWithinFranja) {
                $workedHoursInFranja = $endWithinFranja - $startWithinFranja;
            }
        }
        $workedHoursInFranjaFormatted = gmdate("H:i", max(0, $workedHoursInFranja));

        // Horas extra antes de 07:00
        $earlyOvertime = ($entryTime < $workStart) ? ($workStart - $entryTime) : 0;

        // Horas extra después de 17:00
        $lateOvertime = ($exitTime > $workEnd) ? ($exitTime - $workEnd) : 0;

        $overtime = $earlyOvertime + $lateOvertime;
        $overtimeFormatted = gmdate("H:i", max(0, $overtime));

        // Escribir una fila de datos
        fputcsv($file, [
            $row['first_name'] . ' ' . $row['last_name'],
            $row['project_name'],
            date('d/m/Y', strtotime($row['fecha'])),
            $entryTimeFormatted,
            $exitTimeFormatted,
            $workedHoursInFranjaFormatted,
            $overtimeFormatted,
            $workedHoursTotal
        ]);
    }
} else {
    fputcsv($file, ['No se encontraron registros.']);
}

// Resetear puntero de archivo
rewind($file);

// Crear archivo CSV en memoria
$csvData = stream_get_contents($file);
fclose($file);

// Configuración de PHPMailer
$mail = new PHPMailer(true);
try {
    // Servidor SMTP
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com'; // Usar el servidor SMTP que prefieras
    $mail->SMTPAuth = true;
    $mail->Username = 'santymm95@gmail.com'; // Tu dirección de correo electrónico
    $mail->Password = 'xqjq pcgc crdd zeue'; // Tu contraseña de correo
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Remitente y destinatarios
    $mail->setFrom('no-reply@acema.com', 'ACEMA');
    $mail->addAddress('santymm95@gmail.com'); // Cambiar por el correo de destino

    // Asunto y cuerpo del correo
    $mail->Subject = 'Registro de Horas - Exportación CSV';
    $mail->Body    = 'Adjunto encontrarás el archivo CSV con los registros de horas solicitados.';

    // Adjuntar el archivo CSV
    $mail->addStringAttachment($csvData, $filename, 'base64', 'text/csv');

    // Enviar el correo
    $mail->send();
    echo 'CSV exportado y enviado por correo exitosamente.';
} catch (Exception $e) {
    echo "Hubo un error al enviar el correo. Error: {$mail->ErrorInfo}";
}

$conn->close();
?>
