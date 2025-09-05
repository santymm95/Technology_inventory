<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
include_once __DIR__ . '/../includes/db.php';

$result = $conn->query("SELECT * FROM air ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Aires Acondicionados</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 2rem auto 2.5rem auto;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.09);
            padding: 2.5rem 2rem;
        }
        .profile-header {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .profile-photo {
            width: 200px;
            height: 140px;
            object-fit: contain;
            border-radius: 10px;
            background: #f2f6fa;
            border: 1px solid #e0e0e0;
        }
        .profile-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
        }
        .profile-table th,
        .profile-table td {
            text-align: left;
            padding: 0.6rem 1rem;
            border-bottom: 1px solid #eaeaea;
        }
        .profile-table th {
            color: #215ba0;
            width: 220px;
            background: #f7f9fb;
        }
        .profile-table tr:last-child td {
            border-bottom: none;
        }
        .action-link {
            color: #2176ae;
            text-decoration: underline;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include 'layout.php'; ?>
    <div class="main-content">
        <h2>Lista de Aires Acondicionados</h2>
        <?php while ($air = $result->fetch_assoc()): ?>
            <?php
                $photoDir = "../uploads/" . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $air['internal_number']) . "/";
                $photoPattern = $photoDir . "foto_principal.*";
                $photoFiles = glob($photoPattern);
                $photoUrl = (count($photoFiles) > 0) ? $photoFiles[0] : "../assets/img/no-image.png";
            ?>
            <div class="profile-container">
                <table style="width:100%;margin-bottom:2rem;">
                    <tr>
                        <td style="width:120px;vertical-align:middle;">
                            <img src="../assets/images/logo-acema.webp" alt="Logo" style="height:60px;max-width:120px;">
                        </td>
                        <td style="text-align:center;vertical-align:middle;">
                            <div style="font-size:1.1rem; font-weight:bold; letter-spacing:1px;">
                                HOJA DE VIDA EQUIPOS DE AIRE ACONDICIONADO
                            </div>
                        </td>
                        <td style="text-align:center;vertical-align:middle;font-size:1rem;">
                            <div><strong>Código:</strong> ACM-ADM-TI-FO-005</div>
                            <div><strong>Versión:</strong> 001</div>
                            <div><strong>Fecha:</strong> 21-05-2024</div>
                        </td>
                    </tr>
                </table>
                <hr style="margin: 0 0 2rem 0; border: none; border-top: 2px solid #e0e0e0;">
                <div class="profile-header">
                    <img class="profile-photo" src="<?= htmlspecialchars($photoUrl) ?>" alt="Foto aire">
                    <div>
                        <div><strong>Nombre del equipo:</strong> <?= htmlspecialchars($air['name']) ?></div>
                        <div><strong>Ubicación:</strong> <?= htmlspecialchars($air['location']) ?></div>
                        <div><strong>N° Interno:</strong> <?= htmlspecialchars($air['internal_number']) ?></div>
                        <div><strong>Modelo:</strong> <?= htmlspecialchars($air['model']) ?></div>
                        <div><strong>Marca:</strong> <?= htmlspecialchars($air['brand']) ?></div>
                        <div><strong>Serial:</strong> <?= htmlspecialchars($air['serial']) ?></div>
                        <div><strong>Fecha de compra:</strong> <?= htmlspecialchars($air['purchase_date']) ?></div>
                        <div><strong>Proveedor:</strong> <?= htmlspecialchars($air['provider']) ?></div>
                    </div>
                </div>
                <hr style="margin: 2rem 0; border: none; border-top: 2px solid #e0e0e0;">
                <table class="profile-table">
                     <p><strong>ESPECIFICACIONES</strong></p>
                    <tr><th>Refrigerante</th><td><?= htmlspecialchars($air['refrigerant']) ?></td></tr>
                    <tr><th>Capacidad</th><td><?= htmlspecialchars($air['capacity']) ?></td></tr>
                    <tr><th>Voltaje</th><td><?= htmlspecialchars($air['voltage']) ?></td></tr>
                </table>
            </div>
        <?php endwhile; ?>
    </div>
</body>
</html>
