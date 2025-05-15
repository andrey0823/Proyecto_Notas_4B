<?php
// php/login_process.php
session_start(); // Iniciar sesión para manejar estado de login y mensajes
require_once 'db_config.php'; // Incluir config BD

// Verificar si el método es POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Recoger datos del formulario
    $email = trim($_POST['correo_electronico'] ?? '');
    $password = trim($_POST['contrasena'] ?? '');

    // 2. Validación básica
    if (empty($email) || empty($password)) {
        $_SESSION['message'] = "Correo electrónico y contraseña son obligatorios.";
        $_SESSION['message_type'] = 'error';
        header("Location: ../index.html");
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
         $_SESSION['message'] = "Formato de correo inválido.";
         $_SESSION['message_type'] = 'error';
         header("Location: ../index.html");
         exit;
    }


    // 3. Conectar a la BD
    $conn = connectDB();

    // 4. Buscar al usuario por correo electrónico (Usando Sentencias Preparadas)
    $sql = "SELECT id_usuario, nombres, id_rol_fk, contrasena, estado FROM USUARIOS WHERE correo_electronico = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt); // Obtener el resultado

        if ($result && mysqli_num_rows($result) == 1) {
            // Usuario encontrado, obtener sus datos
            $user = mysqli_fetch_assoc($result);

            // 5. Verificar el estado de la cuenta
            if ($user['estado'] !== 'activo') {
                $_SESSION['message'] = "Tu cuenta está inactiva. Contacta al administrador.";
                $_SESSION['message_type'] = 'error';
                mysqli_stmt_close($stmt);
                mysqli_close($conn);
                header("Location: ../index.html");
                exit;
            }

            // 6. Verificar la contraseña
            if (password_verify($password, $user['contrasena'])) {
                // ¡Contraseña correcta! Iniciar sesión.
                session_regenerate_id(true); // Regenerar ID de sesión por seguridad

                // Guardar información del usuario en la sesión
                $_SESSION['user_id'] = $user['id_usuario'];
                $_SESSION['user_nombre'] = $user['nombres'];
                $_SESSION['user_rol_id'] = $user['id_rol_fk'];
                // Podrías guardar más datos si los necesitas (ej: rol_nombre, email)

                mysqli_stmt_close($stmt);
                mysqli_close($conn);

                // Redirigir al dashboard o página principal post-login
                header("Location: ../dashboard.php");
                exit;

            } else {
                // Contraseña incorrecta
                $_SESSION['message'] = "Correo electrónico o contraseña incorrectos.";
                $_SESSION['message_type'] = 'error';
            }
        } else {
            // Usuario no encontrado
            $_SESSION['message'] = "Correo electrónico o contraseña incorrectos.";
            $_SESSION['message_type'] = 'error';
        }
        mysqli_stmt_close($stmt); // Cerrar statement
    } else {
        // Error al preparar la consulta
         error_log("Error al preparar select login: " . mysqli_error($conn));
        $_SESSION['message'] = "Error interno del servidor.";
        $_SESSION['message_type'] = 'error';
    }

    mysqli_close($conn); // Cerrar conexión
    header("Location: ../index.html"); // Redirigir de vuelta a login si hubo error
    exit;

} else {
    // Si no es POST, redirigir
    header("Location: ../index.html");
    exit;
}
?>