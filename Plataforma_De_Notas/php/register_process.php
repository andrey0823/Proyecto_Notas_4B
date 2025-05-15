<?php
// php/register_process.php
session_start(); // Iniciar la sesión para manejar mensajes
require_once 'db_config.php'; // Incluir configuración de BD

// Verificar si el método es POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Recoger datos del formulario (y limpiar espacios)
    $nombres = trim($_POST['nombres'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $identificacion = trim($_POST['identificacion'] ?? '');
    $rol_nombre = trim($_POST['rol'] ?? ''); // 'estudiante' o 'profesor'
    $programa = trim($_POST['programa'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $terms = isset($_POST['terms']); // Verifica si el checkbox está marcado

    // 2. Validación básica (Añadir más validaciones según necesidad)
    $errors = [];
    if (empty($nombres)) $errors[] = "El nombre es obligatorio.";
    if (empty($apellidos)) $errors[] = "Los apellidos son obligatorios.";
    if (empty($identificacion)) $errors[] = "El número de identificación es obligatorio.";
    if (empty($rol_nombre)) $errors[] = "Debe seleccionar un rol.";
    if (empty($programa)) $errors[] = "Debe seleccionar un programa.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "El formato del correo electrónico no es válido.";
    if (empty($direccion)) $errors[] = "La dirección es obligatoria.";
    if (empty($telefono)) $errors[] = "El teléfono es obligatorio.";
    if (strlen($password) < 8) $errors[] = "La contraseña debe tener al menos 8 caracteres.";
    if ($password !== $confirm_password) $errors[] = "Las contraseñas no coinciden.";
    if (!$terms) $errors[] = "Debe aceptar los términos y condiciones.";

    // Si hay errores de validación, redirigir de vuelta al formulario
    if (!empty($errors)) {
        $_SESSION['message'] = implode("<br>", $errors);
        $_SESSION['message_type'] = 'error';
        header("Location: ../registro.html");
        exit;
    }

    // 3. Conectar a la Base de Datos
    $conn = connectDB();

    // 4. Verificar si el correo o identificación ya existen (Usando Sentencias Preparadas)
    $sql_check = "SELECT id_usuario FROM USUARIOS WHERE correo_electronico = ? OR numero_identificacion = ?";
    $stmt_check = mysqli_prepare($conn, $sql_check);

    if ($stmt_check) {
        mysqli_stmt_bind_param($stmt_check, "ss", $email, $identificacion);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check); // Necesario para mysqli_stmt_num_rows

        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            $errors[] = "El correo electrónico o número de identificación ya están registrados.";
            mysqli_stmt_close($stmt_check); // Cerrar statement
            mysqli_close($conn); // Cerrar conexión
            $_SESSION['message'] = implode("<br>", $errors);
            $_SESSION['message_type'] = 'error';
            header("Location: ../registro.html");
            exit;
        }
        mysqli_stmt_close($stmt_check); // Cerrar si no hubo error
    } else {
        // Error en la preparación de la consulta de verificación
        error_log("Error al preparar consulta de verificación: " . mysqli_error($conn));
        mysqli_close($conn);
        $_SESSION['message'] = "Error interno del servidor al verificar usuario.";
        $_SESSION['message_type'] = 'error';
        header("Location: ../registro.html");
        exit;
    }


    // 5. Obtener el ID del Rol (Mapeo simple basado en inserción inicial)
    // Asume que 'Estudiante' es id_rol=1 y 'Profesor' es id_rol=2
    // Una forma más robusta sería consultar la tabla ROLES
    $id_rol_fk = ($rol_nombre === 'estudiante') ? 1 : (($rol_nombre === 'profesor') ? 2 : null);

    if ($id_rol_fk === null) {
         $errors[] = "Rol inválido seleccionado.";
         // ... (manejo de error similar al anterior) ...
         mysqli_close($conn);
         $_SESSION['message'] = "Rol inválido seleccionado.";
         $_SESSION['message_type'] = 'error';
         header("Location: ../registro.html");
         exit;
    }

    // 5.1. Mapear el código corto del programa al valor completo para la BD ENUM
    $programa_db_value = ''; // Valor por defecto o para manejar error
    switch ($programa) {
        case 'sistemas':
            $programa_db_value = 'Ingeniería de Sistemas';
            break;
        case 'mecatronica':
            $programa_db_value = 'Ingeniería Mecatrónica';
            break;
        case 'admin':
            $programa_db_value = 'Administración de Empresas';
            break;
        case 'contaduria':
            $programa_db_value = 'Contaduría Pública';
            break;
        case 'diseno':
            $programa_db_value = 'Diseño Digital Publicitario';
            break;
        case 'marketing':
            $programa_db_value = 'Marketing y Negocios Digitales';
            break;
        default:
        // Si el valor no es ninguno de los esperados, es un error
            $errors[] = "El programa seleccionado no es válido.";
        // (Podrías añadir aquí el manejo de error y redirección si es necesario)
        // No continuamos si el programa no es válido.
            $_SESSION['message'] = "Programa inválido seleccionado.";
            $_SESSION['message_type'] = 'error';
            mysqli_close($conn); // Asegúrate de cerrar la conexión si sales aquí
            header("Location: ../registro.html");
            exit;
    }

    // 6. Hashear la contraseña (¡MUY IMPORTANTE!)
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    if ($hashed_password === false) {
        error_log("Error al hashear la contraseña.");
        mysqli_close($conn);
        $_SESSION['message'] = "Error interno del servidor al procesar la contraseña.";
        $_SESSION['message_type'] = 'error';
        header("Location: ../registro.html");
        exit;
    }

    // 7. Preparar la sentencia INSERT
    $sql_insert = "INSERT INTO USUARIOS (nombres, apellidos, numero_identificacion, id_rol_fk, programa, correo_electronico, direccion, telefono, contrasena)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_insert = mysqli_prepare($conn, $sql_insert);

    if ($stmt_insert) {
        // Vincular parámetros (s = string, i = integer)
        mysqli_stmt_bind_param($stmt_insert, "sssisssss",
            $nombres,
            $apellidos,
            $identificacion,
            $id_rol_fk,
            $programa_db_value,
            $email,
            $direccion,
            $telefono,
            $hashed_password // Guardar la contraseña hasheada
        );

        // Ejecutar la sentencia
        if (mysqli_stmt_execute($stmt_insert)) {
            // Éxito en el registro
            $_SESSION['message'] = "¡Registro exitoso! Ahora puedes iniciar sesión.";
            $_SESSION['message_type'] = 'success';
            mysqli_stmt_close($stmt_insert);
            mysqli_close($conn);
            header("Location: ../index.html"); // Redirigir a la página de login
            exit;
        } else {
            // Error al ejecutar el insert
             error_log("Error al ejecutar insert: " . mysqli_stmt_error($stmt_insert));
            $_SESSION['message'] = "Error al registrar el usuario. Inténtalo de nuevo.";
            $_SESSION['message_type'] = 'error';
        }
        mysqli_stmt_close($stmt_insert); // Cerrar statement de insert
    } else {
         // Error al preparar el insert
         error_log("Error al preparar insert: " . mysqli_error($conn));
        $_SESSION['message'] = "Error interno del servidor al preparar registro.";
        $_SESSION['message_type'] = 'error';
    }

    mysqli_close($conn); // Cerrar la conexión
    header("Location: ../registro.html"); // Redirigir de vuelta si hubo error en insert
    exit;

} else {
    // Si no es método POST, redirigir a la página de registro
    header("Location: ../registro.html");
    exit;
}
?>