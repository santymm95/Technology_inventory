<?php
session_start();

// Verifica si hay sesión activa
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$nombre = $_SESSION['user']['first_name'];
$apellido = $_SESSION['user']['last_name'];

// Redirige al dashboard después de 3 segundos
header("Refresh: 3; URL=dashboard.php");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Bienvenido - ACEMA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            background-color: #f4f4f4;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            flex-direction: column;
        }

        h2 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        p {
            color: #7f8c8d;
            margin-bottom: 30px;
        }

        .loader {
            margin: 20px auto;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: conic-gradient(#215ba0 0deg, #40a335 120deg, #215ba0 240deg, #40a335 360deg);
            animation: spin 1s linear infinite;
            mask:
                radial-gradient(farthest-side, transparent calc(100% - 8px), black calc(100% - 8px));
            -webkit-mask:
                radial-gradient(farthest-side, transparent calc(100% - 8px), black calc(100% - 8px));
        }


        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <h2>Bienvenido, <?php echo htmlspecialchars($nombre . ' ' . $apellido); ?></h2>
    <p>Redirigiendo a tu panel de principal...</p>
    <div class="loader"></div>
</body>

</html>