<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "acema_db";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Mostrar nombre de quien aprobó/rechazó si existe el campo updated_by
    $sql = "SELECT 
                ar.id,
                CONCAT(u.first_name, ' ', u.last_name) AS employee,
                pr.name AS project,
                ar.date,
                ar.time_in,
                ar.time_out,
                ar.total_hours,
                ar.extra_hours,
                ar.estado,
                ar.updated_at,
                CONCAT(admin.first_name, ' ', admin.last_name) AS reviewed_by
            FROM attendance_records ar
            LEFT JOIN users u ON ar.user_id = u.id
            LEFT JOIN projects pr ON u.project_id = pr.id
            LEFT JOIN users admin ON ar.updated_by = admin.id
            ORDER BY ar.date DESC, ar.updated_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Connection or query error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Registros</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
        h1 { margin-top: 50px; text-align: center; padding: 20px; }
        table { border-collapse: collapse; width: 90%; margin: 0 auto 50px; background-color: white; }
        th, td { padding: 5px; border: 1px solid #ddd; text-align: center; }
        th { background-color: #215ba0; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
    </style>
    <?php include 'layout.php'; ?>
</head>
<body>
    <h1>Historial de Registros de Asistencia</h1>
    <div style="width:90%;margin:0 auto 20px;text-align:right;">
        <a href="graficas.php" style="display:inline-block;padding:10px 18px;background:#28a745;color:#fff;border-radius:5px;text-decoration:none;font-weight:bold;margin-right:10px;">Ver Gráficas</a>
        <form action="send_history_mail.php" method="post" style="display:inline;">
            <input type="hidden" name="send_csv" value="1">
            <button type="submit" style="padding:10px 18px;background:#215ba0;color:#fff;border-radius:5px;border:none;font-weight:bold;cursor:pointer;">Enviar Correo</button>
        </form>
    </div>
    <table>
        <thead>
            <tr>
                <th>Revisado por</th>
                <th>Fecha de Revisión</th>
                <th>Empleado</th>
                <th>Proyecto</th>
                <th>Fecha</th>
                <th>Hora Entrada</th>
                <th>Hora Salida</th>
                <th>Horas Extras</th>
                <th>Total Horas</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($records): ?>
                <?php foreach ($records as $row): ?>
                    <tr>
                        <td>
                            <?php
                            if (!empty($row['reviewed_by'])) {
                                echo htmlspecialchars($row['reviewed_by']);
                            } elseif (isset($row['updated_by'])) {
                                echo htmlspecialchars($row['updated_by']);
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td><?= htmlspecialchars($row['updated_at']) ?></td>
                        <td><?= htmlspecialchars($row['employee']) ?></td>
                        <td><?= htmlspecialchars($row['project']) ?></td>
                        <td><?= htmlspecialchars($row['date']) ?></td>
                        <td><?= htmlspecialchars($row['time_in']) ?></td>
                        <td><?= htmlspecialchars($row['time_out']) ?></td>
                        <td><?= htmlspecialchars($row['extra_hours']) ?></td>
                        <td><?= htmlspecialchars($row['total_hours']) ?></td>
                        <td><?= htmlspecialchars($row['estado']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10">No se encontraron registros.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
