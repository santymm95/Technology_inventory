<?php
require_once('../includes/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar datos obligatorios
    if (
        !empty($_POST['first_name']) && !empty($_POST['last_name']) &&
        !empty($_POST['email']) && !empty($_POST['password']) &&
        !empty($_POST['role_id']) && !empty($_POST['project_id']) &&
        !empty($_POST['document_number']) && !empty($_POST['photo_data'])
    ) {
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role_id = (int)$_POST['role_id'];
        $project_id = (int)$_POST['project_id'];
        $document_number = $_POST['document_number'];
        $photo_data = $_POST['photo_data'];

        // Procesar imagen base64
        $image_base64 = str_replace('data:image/jpeg;base64,', '', $photo_data);
        $image_data = base64_decode($image_base64);

        // Crear carpeta uploads/document_number si no existe
        $upload_dir = __DIR__ . '/../uploads/' . $document_number;
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Guardar imagen en carpeta creada
        $image_filename = $upload_dir . '/rostro.jpg';
        file_put_contents($image_filename, $image_data);

        // Ruta relativa para guardar en la base de datos
        $image_path = 'uploads/' . $document_number . '/rostro.jpg';

        // Guardar imagen temporal para Face++ API (requerido)
        $temp_dir = __DIR__ . '/temp';
        if (!is_dir($temp_dir)) mkdir($temp_dir, 0777, true);
        $temp_file = $temp_dir . '/temp.jpg';
        file_put_contents($temp_file, $image_data);

        // Configuración Face++ API
        $api_key = 'eSTTGLhRR_vkGWE6-EZjvFOMjZQsaoPy';
        $api_secret = 'VkSDCJ3lJgyso9FCPiebEhekyfZZSaDr';
        $url = 'https://api-us.faceplusplus.com/facepp/v3/detect';

        $data = [
            'api_key' => $api_key,
            'api_secret' => $api_secret,
            'image_file' => new CURLFile($temp_file),
            'return_attributes' => 'gender,age'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $curl_errno = curl_errno($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        unlink($temp_file);

        if ($curl_errno) {
            die("Error en cURL: $curl_error");
        }

        $resp = json_decode($response, true);

        if (isset($resp['faces'][0]['face_token'])) {
            $face_token = $resp['faces'][0]['face_token'];

            // Insertar en users con image_path y face_token
            $stmtUser = $pdo->prepare("INSERT INTO users 
                (first_name, last_name, email, password, role_id, project_id, document_number, photo, face_token, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmtUser->execute([
                $first_name, $last_name, $email, $password,
                $role_id, $project_id, $document_number,
                $image_path, $face_token
            ]);

            echo "<script>alert('Usuario registrado exitosamente con reconocimiento facial.'); window.location='../views/create_user.php';</script>";
        } else {
            echo "<script>alert('❌ No se detectó ningún rostro en la foto. Intenta nuevamente.'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Faltan datos obligatorios.'); window.history.back();</script>";
    }
} else {
    echo "Método no permitido.";
}
?>
