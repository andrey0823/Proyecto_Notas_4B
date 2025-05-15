<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Contáctanos</title>
  <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
  <section class="contacto">
    <div class="container">
      <h2>Contáctanos</h2>
      <form action="procesar_formulario.php" method="POST">
        <label for="nombre">Nombre:</label>
        <input type="text" id="nombre" name="nombre" required>
        <label for="correo">Correo electrónico:</label>
        <input type="email" id="correo" name="correo" required>
        <label for="asunto">Asunto:</label>
        <input type="text" id="asunto" name="asunto" required>
        <label for="mensaje">Mensaje:</label>
        <textarea id="mensaje" name="mensaje" rows="5" required></textarea>
        <button type="submit">Enviar mensaje</button>
      </form>
    </div>
  </section>
</body>
</html>
