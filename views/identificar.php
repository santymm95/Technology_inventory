<?php
$resultado_identificacion = "";

if (isset($_POST['imagen']) && !empty($_POST['imagen'])) {
    $api_key = 'eSTTGLhRR_vkGWE6-EZjvFOMjZQsaoPy';
    $api_secret = 'VkSDCJ3lJgyso9FCPiebEhekyfZZSaDr';
    $image_base64 = str_replace('data:image/jpeg;base64,', '', $_POST['imagen']);
    $image_data = base64_decode($image_base64);
    $file_path = 'temp.jpg';

    file_put_contents($file_path, $image_data);

    // Detectar rostro
    $url_detect = 'https://api-us.faceplusplus.com/facepp/v3/detect';
    $data_detect = [
        'api_key' => $api_key,
        'api_secret' => $api_secret,
        'image_file' => new CURLFile($file_path)
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_detect);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_detect);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response_detect = curl_exec($ch);
    curl_close($ch);

    unlink($file_path);

    $resp_detect = json_decode($response_detect, true);

    if (isset($resp_detect['faces'][0]['face_token'])) {
        $face_token_new = $resp_detect['faces'][0]['face_token'];

        $conn = new mysqli("localhost", "root", "", "acema_db");
        if ($conn->connect_error) {
            die("Error en la conexión: " . $conn->connect_error);
        }

        $result = $conn->query("SELECT id, face_token, first_name, last_name, email, project_id, enabled FROM users WHERE face_token IS NOT NULL");

        $mejor_puntaje = 0;
        $usuario_encontrado = null;

        while ($fila = $result->fetch_assoc()) {
            $face_token_guardado = $fila['face_token'];

            // Comparar rostros
            $url_compare = 'https://api-us.faceplusplus.com/facepp/v3/compare';
            $data_compare = [
                'api_key' => $api_key,
                'api_secret' => $api_secret,
                'face_token1' => $face_token_new,
                'face_token2' => $face_token_guardado
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url_compare);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_compare);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response_compare = curl_exec($ch);
            curl_close($ch);

            $resp_compare = json_decode($response_compare, true);

            if (isset($resp_compare['confidence'])) {
                $confianza = $resp_compare['confidence'];
                if ($confianza > $mejor_puntaje) {
                    $mejor_puntaje = $confianza;
                    $usuario_encontrado = $fila;
                }
            }
        }

        if ($mejor_puntaje > 80 && $usuario_encontrado !== null) {
            // Verificar si el usuario está habilitado
            if (isset($usuario_encontrado['enabled']) && !$usuario_encontrado['enabled']) {
                $resultado_identificacion = "❌ Usuario inhabilitado. No puede registrar asistencia.";
            } else {
                // Buscar nombre del proyecto
                $project_id = (int)$usuario_encontrado['project_id'];
                $nombre_proyecto = "No asignado";

                if ($project_id > 0) {
                    $sql_proyecto = "SELECT name FROM projects WHERE id = $project_id LIMIT 1";
                    $res_proyecto = $conn->query($sql_proyecto);
                    if ($res_proyecto && $res_proyecto->num_rows > 0) {
                        $fila_proyecto = $res_proyecto->fetch_assoc();
                        $nombre_proyecto = $fila_proyecto['name'];
                    }
                }

                // Obtener hora y fecha del servidor
                date_default_timezone_set('America/Bogota'); // Cambiar según zona
                $hora_actual = date("H:i:s");
                $fecha_actual = date("Y-m-d");

                $user_id = (int)$usuario_encontrado['id'];

                // Revisar si ya hay un registro de asistencia para hoy sin salida
                $sql_check = "SELECT * FROM attendance_records WHERE user_id = ? AND date = ? ORDER BY id DESC LIMIT 1";
                $stmt_check = $conn->prepare($sql_check);
                $stmt_check->bind_param("is", $user_id, $fecha_actual);
                $stmt_check->execute();
                $resultado_check = $stmt_check->get_result();

                if ($resultado_check->num_rows === 0) {
                    // No existe registro para hoy, inserta entrada (time_in)
                    $stmt_insert = $conn->prepare("INSERT INTO attendance_records (user_id, date, time_in) VALUES (?, ?, ?)");
                    $stmt_insert->bind_param("iss", $user_id, $fecha_actual, $hora_actual);
                    $stmt_insert->execute();
                    $stmt_insert->close();

                    $resultado_identificacion = "
                        <h3>Usuario identificado:</h3>
                        Nombre: " . htmlspecialchars($usuario_encontrado['first_name']) . "<br>
                        Apellido: " . htmlspecialchars($usuario_encontrado['last_name']) . "<br>
                        Proyecto asignado: " . htmlspecialchars($nombre_proyecto) . "<br>
                        Confianza de registro: " . round($mejor_puntaje, 2) . "%<br>
                        ✅ Registro de entrada guardado a las <strong>$hora_actual</strong>.
                    ";
                } else {
                    // Ya hay registro(s) para hoy, revisamos el último
                    $registro_actual = $resultado_check->fetch_assoc();

                    if (is_null($registro_actual['time_out'])) {
                        // Si no tiene salida, actualizar la salida con hora actual
                        $time_in = $registro_actual['time_in'];
                        $time_out = $hora_actual;

                        // Calcular horas trabajadas en decimal (horas + minutos/60)
                        $diff = (strtotime($time_out) - strtotime($time_in)) / 3600;
                        $total_hours = round($diff, 2);

                        // Calcular horas extras fuera de la franja 07:00 a 17:00
                        function calcularHorasExtras($time_in, $time_out) {
                            if (!$time_in || !$time_out) return 0.0;
                            $start = DateTime::createFromFormat('H:i:s', $time_in);
                            $end = DateTime::createFromFormat('H:i:s', $time_out);
                            if (!$start || !$end) return 0.0;

                            $franja_inicio = DateTime::createFromFormat('H:i:s', '07:00:00');
                            $franja_fin = DateTime::createFromFormat('H:i:s', '17:00:00');

                            $horas_extras = 0.0;

                            // Antes de la franja
                            if ($start < $franja_inicio) {
                                $intervalo = $franja_inicio->diff($start);
                                $horas_extras += ($intervalo->h + $intervalo->i/60 + $intervalo->s/3600);
                            }
                            // Después de la franja
                            if ($end > $franja_fin) {
                                $intervalo = $end->diff($franja_fin);
                                $horas_extras += ($intervalo->h + $intervalo->i/60 + $intervalo->s/3600);
                            }
                            // Si la entrada es después de la franja de fin, todo el tiempo es extra
                            if ($start > $franja_fin) {
                                $intervalo = $end->diff($start);
                                $horas_extras = ($intervalo->h + $intervalo->i/60 + $intervalo->s/3600);
                            }
                            // Si la salida es antes de la franja de inicio, todo el tiempo es extra
                            if ($end < $franja_inicio) {
                                $intervalo = $start->diff($end);
                                $horas_extras = ($intervalo->h + $intervalo->i/60 + $intervalo->s/3600);
                            }

                            return round(abs($horas_extras), 2);
                        }

                        $extra_hours = calcularHorasExtras($time_in, $time_out);

                        // Actualizar registro con time_out, total_hours y extra_hours
                        $stmt_update = $conn->prepare("UPDATE attendance_records SET time_out = ?, total_hours = ?, extra_hours = ? WHERE id = ?");
                        $stmt_update->bind_param("sdsi", $time_out, $total_hours, $extra_hours, $registro_actual['id']);
                        $stmt_update->execute();
                        $stmt_update->close();

                        $resultado_identificacion = "
                            <h3>Usuario identificado:</h3>
                            Nombre: " . htmlspecialchars($usuario_encontrado['first_name']) . "<br>
                            Apellido: " . htmlspecialchars($usuario_encontrado['last_name']) . "<br>
                            Proyecto asignado: " . htmlspecialchars($nombre_proyecto) . "<br>
                            Confianza de registro: " . round($mejor_puntaje, 2) . "%<br>
                            ✅ Registro de salida guardado a las <strong>$hora_actual</strong>.<br>
                            🕒 Total horas trabajadas: <strong>$total_hours horas</strong>.<br>
                            ⏰ Horas extras: <strong>$extra_hours horas</strong>.
                        ";
                    } else {
                        // Ya tiene entrada y salida, no se registra más hoy
                        $resultado_identificacion = "
                            <h3>Usuario identificado:</h3>
                            Nombre: " . htmlspecialchars($usuario_encontrado['first_name']) . "<br>
                            Apellido: " . htmlspecialchars($usuario_encontrado['last_name']) . "<br>
                            Proyecto asignado: " . htmlspecialchars($nombre_proyecto) . "<br>
                            Confianza de registro: " . round($mejor_puntaje, 2) . "%<br>
                            ⚠️ Ya registraste tu entrada y salida para el día de hoy.
                        ";
                    }
                }

                $stmt_check->close();
            }
        } else {
            $resultado_identificacion = "❌ No se encontró un usuario coincidente con suficiente confianza.";
        }

        $conn->close();
    } else {
        $resultado_identificacion = "❌ No se detectó ningún rostro en la imagen.";
    }
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="../assets/css/identificator.css" />
  <title>Identificación Facial</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f0f0f0;
      margin: 0;
      padding: 0;
    }
    .container {
      max-width: 380px;
      margin: 40px auto;
      background: white;
      padding: 20px 25px 40px;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0,0,0,0.2);
      text-align: center;
    }
    #video {
      border-radius: 8px;
      width: 320px;
      height: 240px;
      background: #000;
      margin-bottom: 15px;
    }
    .btn {
      background-color: #007bff;
      color: white;
      border: none;
      padding: 10px 18px;
      font-size: 16px;
      border-radius: 6px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }
    .btn:hover:not(:disabled) {
      background-color: #0056b3;
    }
    .btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }
    #resultado {
      margin-top: 25px;
      font-size: 16px;
      color: #333;
    }
    #reloj {
      font-size: 20px;
      font-weight: bold;
      margin-bottom: 20px;
      color: #444;
    }
  </style>
</head>
<?php include 'layout.php'; ?>
<body>

  <div class="container">
    <div id="reloj">Cargando hora...</div>
    <h2>Identificación Facial</h2>

    <video id="video" autoplay muted></video>
    <canvas id="canvas" width="320" height="240" style="display:none;"></canvas>

    <form method="post" action="" id="form">
      <input type="hidden" name="imagen" id="imagen" />
      <button class="btn" type="submit" id="identificar">📸 Identificar</button>
    </form>

    <div id="resultado">
      <?php echo $resultado_identificacion; ?>
    </div>
  </div>

  <script>
    // Reloj en vivo
    function actualizarReloj() {
      const ahora = new Date();
      const horas = String(ahora.getHours()).padStart(2, '0');
      const minutos = String(ahora.getMinutes()).padStart(2, '0');
      const segundos = String(ahora.getSeconds()).padStart(2, '0');
      const horaFormateada = `${horas}:${minutos}:${segundos}`;
      document.getElementById('reloj').textContent = "Hora actual: " + horaFormateada;
    }
    setInterval(actualizarReloj, 1000);
    actualizarReloj();

    // Video y captura de imagen
    const video = document.getElementById("video");
    const canvas = document.getElementById("canvas");
    const imagen = document.getElementById("imagen");
    const form = document.getElementById("form");
    const boton = document.getElementById("identificar");

    navigator.mediaDevices
      .getUserMedia({ video: true })
      .then((stream) => (video.srcObject = stream))
      .catch((err) => alert("Error al acceder a la cámara: " + err));

    form.addEventListener("submit", function (e) {
      e.preventDefault();

      boton.classList.add("identificando");
      boton.textContent = "🔍 Identificando...";
      boton.disabled = true;

      const context = canvas.getContext("2d");
      context.drawImage(video, 0, 0, canvas.width, canvas.height);
      imagen.value = canvas.toDataURL("image/jpeg");

      setTimeout(() => {
        form.submit();
      }, 100);
    });
  </script>
</body>
</html>
