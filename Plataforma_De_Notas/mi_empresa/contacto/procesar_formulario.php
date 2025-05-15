<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "empresa_contactos";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $database);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = $_POST["nombre"] ?? '';
    $correo = $_POST["correo"] ?? '';
    $asunto = $_POST["asunto"] ?? '';
    $mensaje = $_POST["mensaje"] ?? '';

    if ($nombre && $correo && $asunto && $mensaje) {
        $stmt = $conn->prepare("INSERT INTO mensajes (nombre, correo, asunto, mensaje) VALUES (?, ?, ?, ?)");

        if ($stmt === false) {
            die("Error al preparar la consulta: " . $conn->error);
        }

        $stmt->bind_param("ssss", $nombre, $correo, $asunto, $mensaje);

        if ($stmt->execute()) {
            echo "<h2>Mensaje enviado correctamente</h2>";
        } else {
            echo "Error al guardar el mensaje: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Todos los campos son obligatorios.";
    }
}

$conn->close();
?>
