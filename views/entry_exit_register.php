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

$user_id = 31; // ID líder proyectos

// Manejo del parámetro de búsqueda con consulta preparada
$search = isset($_GET['search']) ? $_GET['search'] : '';
$order_column = isset($_GET['order_by']) ? $_GET['order_by'] : 'id';
$order_direction = (isset($_GET['order_dir']) && $_GET['order_dir'] == 'desc') ? 'DESC' : 'ASC';

// Sanitizar columna y dirección para evitar inyección (solo columnas permitidas)
$allowed_columns = ['id', 'first_name', 'last_name', 'email', 'registration_date', 'role_id', 'document_number'];
if (!in_array($order_column, $allowed_columns)) {
    $order_column = 'id';
}

// Consulta usuarios con filtro seguro
$stmt_users = $conn->prepare("
    SELECT id, first_name, last_name, email, registration_date, role_id, document_number, photo 
    FROM users 
    WHERE first_name LIKE CONCAT('%', ?, '%') OR last_name LIKE CONCAT('%', ?, '%') OR document_number LIKE CONCAT('%', ?, '%') 
    ORDER BY $order_column $order_direction
");
$stmt_users->bind_param("sss", $search, $search, $search);
$stmt_users->execute();
$result = $stmt_users->get_result();

// Consulta proyectos del líder
$stmt_projects = $conn->prepare("SELECT id, name FROM projects WHERE leader_id = ?");
$stmt_projects->bind_param("i", $user_id);
$stmt_projects->execute();
$result_proyectos = $stmt_projects->get_result();
$proyectos = [];
while ($row = $result_proyectos->fetch_assoc()) {
    $proyectos[] = $row;
}

$current_date = date('Y-m-d');
$hora_actual = date('Y-m-d H:i:s');

// Manejo del POST para registro ingreso/salida
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'];
    $post_user_id = $_POST['user_id'];
    $proyecto_id = $_POST['proyecto_id'];

    // Validar acción y datos
    if (($accion === 'ingreso' || $accion === 'salida') && is_numeric($post_user_id) && is_numeric($proyecto_id)) {
        $entry_or_exit = ($accion === 'ingreso') ? 1 : 0;

        $stmt_insert = $conn->prepare("INSERT INTO work_register (user_id, entry_or_exit, timestamp, project_id) VALUES (?, ?, ?, ?)");
        $stmt_insert->bind_param("iisi", $post_user_id, $entry_or_exit, $hora_actual, $proyecto_id);

        if ($stmt_insert->execute()) {
            $message = ($accion === 'ingreso') ? 'Ingreso registrado correctamente.' : 'Salida registrada correctamente.';
            header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message) . "&message_type=success");
            exit();
        } else {
            echo "Error al registrar el ingreso/salida: " . $stmt_insert->error;
        }
    } else {
        echo "Datos inválidos para registrar ingreso o salida.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php include 'layout.php'; ?>
    <meta charset="UTF-8">
    <title>Registro de Entrada/Salida</title>
    <style>
        /* (Tu CSS existente aquí) */
    </style>
</head>

<body>
    <div class="container">
        <h2>Registro de Ingreso y Salida</h2>
        <form method="GET" action="" id="searchForm">
            <input type="text" name="search" placeholder="Buscar por nombre, apellido o documento" id="searchInput"
                value="<?php echo htmlspecialchars($search); ?>" />
        </form>

        <div id="reloj" style="font-size: 24px; text-align: center; margin-top: 20px; font-weight: bold;"></div>

        <div class="cards-container" id="resultsContainer">
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Consultar último registro del día
                    $stmt_check = $conn->prepare("SELECT entry_or_exit FROM work_register WHERE user_id = ? AND DATE(timestamp) = ? ORDER BY timestamp DESC LIMIT 1");
                    $stmt_check->bind_param("is", $row['id'], $current_date);
                    $stmt_check->execute();
                    $result_check = $stmt_check->get_result();
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
            $stmt_users->close();
            $stmt_projects->close();
            $conn->close();
            ?>
        </div>

        <script>
            function actualizarReloj() {
                var now = new Date();
                var horas = now.getHours().toString().padStart(2, '0');
                var minutos = now.getMinutes().toString().padStart(2, '0');
                var segundos = now.getSeconds().toString().padStart(2, '0');
                var tiempo = horas + ":" + minutos + ":" + segundos;

                document.getElementById('reloj').innerText = tiempo;
            }

            setInterval(actualizarReloj, 1000);
            actualizarReloj();

            // Buscador con AJAX (opcional, según archivo search.php)
            document.getElementById('searchInput').addEventListener('keyup', function () {
                var searchValue = this.value;
                var xhr = new XMLHttpRequest();
                xhr.open('GET', 'search.php?search=' + encodeURIComponent(searchValue), true);
                xhr.onreadystatechange = function () {
                    if (xhr.readyState == 4 && xhr.status == 200) {
                        document.getElementById('resultsContainer').innerHTML = xhr.responseText;
                    }
                };
                xhr.send();
            });
        </script>
    </div>
</body>

</html>
