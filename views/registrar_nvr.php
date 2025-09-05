<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}
// Manejo de mensajes de éxito/error
$success = isset($_GET['success']) && $_GET['success'] == 1;
$error = isset($_GET['error']) ? $_GET['error'] : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar NVR</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .main-content {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            background: #f4f8fb;
        }
        .form-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.09);
            padding: 2.5em 2em 2em 2em;
            margin: 40px 0;
            width: 80%; 
        }
        .form-title {
            font-size: 1.3em;
            color: #215ba0;
            font-weight: bold;
            margin-bottom: 1.5em;
            text-align: center;
        }
        .form-row {
            display: flex;
            gap: 1em;
            margin-bottom: 1em;
        }
        .form-group {
            flex: 1 1 0;
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            font-weight: 500;
            margin-bottom: 0.3em;
        }
        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group textarea {
            width: 100%;
            padding: 0.3em;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .form-group input[type="file"] {
            margin-top: 0.3em;
        }
        .form-actions {
            text-align: right;
            margin-top: 1.5em;
        }
        .form-actions button {
            background: #215ba0;
            color: #fff;
            padding: 0.5em 2em;
            border: none;
            border-radius: 6px;
            font-size: 1em;
        }
        .alert-success {
            background: #e6f9e6;
            color: #217a21;
            border: 1px solid #b2e5b2;
            padding: 1em;
            border-radius: 8px;
            margin-bottom: 1em;
            text-align: center;
        }
        .alert-error {
            background: #ffeaea;
            color: #b30000;
            border: 1px solid #ffb2b2;
            padding: 1em;
            border-radius: 8px;
            margin-bottom: 1em;
            text-align: center;
        }
        @media (max-width: 700px) {
            .form-card { padding: 1em; }
            .form-row { flex-direction: column; gap: 0; }
        }
    </style>
</head>
<body>
<?php include 'layout.php'; ?>
<div class="main-content">
    <div class="form-card">
        <div class="form-title">Registrar NVR</div>
        <?php if ($success): ?>
            <div class="alert-success">¡NVR registrado correctamente!</div>
        <?php elseif ($error): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form action="../controllers/register_nvr.php" method="post" enctype="multipart/form-data" autocomplete="off">
            <div class="form-row">
                <div class="form-group">
                    <label>N° Interno *</label>
                    <input type="text" name="n_interno" required>
                </div>
                <div class="form-group">
                    <label>Modelo *</label>
                    <input type="text" name="modelo" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Marca *</label>
                    <input type="text" name="marca" required>
                </div>
                <div class="form-group">
                    <label>Serial *</label>
                    <input type="text" name="serial" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Fecha de compra</label>
                    <input type="date" name="fecha_compra">
                </div>
                <div class="form-group">
                    <label>Proveedor</label>
                    <input type="text" name="proveedor">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Tipo</label>
                    <input type="text" name="tipo">
                </div>
                <div class="form-group">
                    <label>Decodificación</label>
                    <input type="text" name="decodificacion">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Entradas</label>
                    <input type="text" name="entradas">
                </div>
                <div class="form-group">
                    <label>Conectividad</label>
                    <input type="text" name="conectividad">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Almacenamiento</label>
                    <input type="text" name="almacenamiento">
                </div>
                <div class="form-group">
                    <label>Transmisión</label>
                    <input type="text" name="transmision">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Uso</label>
                    <input type="text" name="uso">
                </div>
                <div class="form-group">
                    <label>Fotografía</label>
                    <input type="file" name="fotografia" accept="image/*">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Descripción de partes</label>
                    <textarea name="partes_equipo" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label>Imagen</label>
                    <input type="file" name="imagen" accept="image/*">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit">Guardar</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
