<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "empresa_contactos";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$sql = "SELECT nombre, correo, asunto, mensaje, fecha FROM mensajes ORDER BY fecha DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Mensajes Recibidos</title>
  <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
  <h1>Mensajes Recibidos</h1>
  <?php
  if ($result->num_rows > 0) {
      echo "<table>";
      echo "<tr><th>Nombre</th><th>Correo</th><th>Asunto</th><th>Mensaje</th><th>Fecha</th></tr>";
      while ($row = $result->fetch_assoc()) {
          echo "<tr>";
          echo "<td>" . htmlspecialchars($row["nombre"]) . "</td>";
          echo "<td>" . htmlspecialchars($row["correo"]) . "</td>";
          echo "<td>" . htmlspecialchars($row["asunto"]) . "</td>";
          echo "<td>" . nl2br(htmlspecialchars($row["mensaje"])) . "</td>";
          echo "<td>" . $row["fecha"] . "</td>";
          echo "</tr>";
      }
      echo "</table>";
  } else {
      echo "<p>No hay mensajes registrados.</p>";
  }
  $conn->close();
  ?>
</body>
</html>
