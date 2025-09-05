<?php
session_start(); // Iniciar la sesión

// Eliminar todas las variables de sesión
session_unset();

// Destruir la sesión
session_destroy();

// Redirigir después de 2 segundos
header("Refresh: 2; URL=/acema2/index.php");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - ACEMA</title>
    <link rel="stylesheet" href="./assets/css/styles.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f4f4f4;
            margin: 0;
        }

        .logout-message {
            text-align: center;
            font-family: Arial, sans-serif;
        }

        .logout-message h2 {
            color: #333;
        }

        .logout-message p {
            color: #666;
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
    <div class="logout-message">
        <h2>Has cerrado sesión con éxito</h2>
        <p>Redirigiendo a la página de inicio...</p>
        <div class="loader"></div>
    </div>
</body>

</html>