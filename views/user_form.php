<?php
// Formulario para crear o editar usuarios de la tabla `users`
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Formulario Usuario</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .user-form-container {
            max-width: 500px;
            margin: 2.5rem auto;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.09);
            padding: 2.5rem 2rem;
        }
        .user-form label {
            font-weight: 500;
            color: #215ba0;
            margin-bottom: 0.3em;
            display: block;
        }
        .user-form input, .user-form select {
            width: 100%;
            padding: 0.5em;
            margin-bottom: 1.2em;
            border-radius: 6px;
            border: 1px solid #c7d0db;
            font-size: 1em;
        }
        .user-form button {
            background: #2176ae;
            color: #fff;
            padding: 0.7em 2em;
            border: none;
            border-radius: 7px;
            font-size: 1.08em;
            cursor: pointer;
            box-shadow: 0 1px 4px rgba(33,118,174,0.10);
        }
    </style>
</head>
<body>
    <?php include 'layout.php'; ?>
    <div class="main-content">
        <div class="user-form-container">
            <h2>Registrar Usuario</h2>
            <?php if ($msg): ?>
                <script>
                    alert("<?= htmlspecialchars($msg) ?>");
                </script>
            <?php endif; ?>
            <form class="user-form" method="post" action="../controllers/register_user.php">
                <label>Nombre(s):
                    <input type="text" name="first_name" required maxlength="100">
                </label>
                <label>Apellido(s):
                    <input type="text" name="last_name" required maxlength="100">
                </label>
                <label>Documento:
                    <input type="text" name="document" required maxlength="50">
                </label>
                <label>Cargo:
                    <input type="text" name="position" maxlength="100">
                </label>
                <label>Departamento:
                    <input type="text" name="department" maxlength="100">
                </label>
                <label>Activo:
                    <select name="active" required>
                        <option value="1">Sí</option>
                        <option value="0">No</option>
                    </select>
                </label>
                <button type="submit">Guardar usuario</button>
            </form>
        </div>
    </div>
</body>
</html>
