<?php
session_start();
date_default_timezone_set('America/Bogota');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "acema_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$search = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT id, first_name, last_name, email, registration_date, role_id, document_number, photo 
        FROM users 
        WHERE first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR document_number LIKE '%$search%' 
        ORDER BY first_name";
$result = $conn->query($sql);

$sql_proyectos = "SELECT id, name FROM projects WHERE leader_id = 31";  // Ajusta el user_id según sea necesario
$result_proyectos = $conn->query($sql_proyectos);
$proyectos = [];
while ($row = $result_proyectos->fetch_assoc()) {
    $proyectos[] = $row;
}

$current_date = date('Y-m-d');

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $sql_check = "SELECT * FROM work_register 
                      WHERE user_id = '{$row['id']}' AND DATE(timestamp) = '$current_date' 
                      ORDER BY timestamp DESC LIMIT 1";
        $result_check = $conn->query($sql_check);
        $last_entry = $result_check->fetch_assoc();

        $estado = "Sin registrar";
        $ingreso_class = "btn-ingreso";
        $salida_class = "btn-salida";
        $ingreso_disabled = "";
        $salida_disabled = "";

        if ($last_entry) {
            if ($last_entry['entry_or_exit'] == 1) {
                $estado = "Ingresado";
                $ingreso_class = "btn-ingreso-registrado";
                $ingreso_disabled = "disabled";
            } elseif ($last_entry['entry_or_exit'] == 0) {
                $estado = "Salido";
                $salida_class = "btn-salida-registrada";
                $salida_disabled = "disabled";
            }
        }

        echo "<div class='card'>
            <h3>{$row['first_name']} {$row['last_name']}</h3>
            <p><strong>Documento:</strong> {$row['document_number']}</p>
            <p><strong>Estado:</strong> $estado</p>
            <form method='POST' action=''>
                <input type='hidden' name='user_id' value='{$row['id']}' />
                <select name='proyecto_id' required>
                    <option value=''>Selecciona un Proyecto</option>";
        foreach ($proyectos as $proyecto) {
            echo "<option value='{$proyecto['id']}'>{$proyecto['name']}</option>";
        }
        echo "</select>
            <button type='submit' name='accion' value='ingreso' class='$ingreso_class' $ingreso_disabled>Ingreso</button>
            <button type='submit' name='accion' value='salida' class='$salida_class' $salida_disabled>Salida</button>
            </form>
        </div>";
    }
} else {
    echo "<p style='text-align:center;'>No hay usuarios registrados.</p>";
}

$conn->close();
?>
