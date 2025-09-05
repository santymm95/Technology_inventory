<?php
require 'db.php';

$api_key = 'TU_API_KEY';
$api_secret = 'TU_API_SECRET';
$outer_id = 'acema_faceset';

if (isset($_POST['imagen'])) {
    $image_base64 = str_replace('data:image/jpeg;base64,', '', $_POST['imagen']);
    $image_data = base64_decode($image_base64);
    $file_path = 'temp.jpg';
    file_put_contents($file_path, $image_data);

    // Buscar rostro
    $url_search = 'https://api-us.faceplusplus.com/facepp/v3/search';
    $data = [
        'api_key' => $api_key,
        'api_secret' => $api_secret,
        'image_file' => new CURLFile($file_path),
        'outer_id' => $outer_id
    ];
    $ch = curl_init($url_search);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    unlink($file_path);

    $result = json_decode($response, true);

    if (!empty($result['results'][0]['face_token']) && $result['results'][0]['confidence'] > 75) {
        $face_token = $result['results'][0]['face_token'];

        $stmt = $conn->prepare("SELECT nombre, apellido FROM rostros WHERE face_token = ?");
        $stmt->bind_param("s", $face_token);
        $stmt->execute();
        $stmt->bind_result($nombre, $apellido);
        if ($stmt->fetch()) {
            echo "¡Rostro reconocido! Bienvenido, $nombre $apellido.";
        } else {
            echo "Rostro no encontrado en la base de datos.";
        }
        $stmt->close();
    } else {
        echo "No se detectó rostro o la coincidencia es baja.";
    }
}
?>
