<?php
// dashboard.php
session_start(); // Continuar/Iniciar la sesión

// 1. Verificar si el usuario está logueado, si no, redirigir a login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit;
}

// 2. Recuperar información del usuario de la sesión
$user_id = $_SESSION['user_id'];
$user_nombre = $_SESSION['user_nombre'];
$user_rol_id = $_SESSION['user_rol_id']; // 1: Estudiante, 2: Profesor, 3: Admin

// 3. Obtener el nombre del rol desde la BD (para mostrarlo)
require_once 'php/db_config.php'; // Asegúrate que la ruta sea correcta
$conn = connectDB();
$nombre_rol = 'Desconocido';
if ($conn) {
    $stmt_role = mysqli_prepare($conn, "SELECT nombre_rol FROM ROLES WHERE id_rol = ?");
    if ($stmt_role) {
        mysqli_stmt_bind_param($stmt_role, "i", $user_rol_id);
        mysqli_stmt_execute($stmt_role);
        $result_role = mysqli_stmt_get_result($stmt_role);
        if ($row_role = mysqli_fetch_assoc($result_role)) {
            $nombre_rol = $row_role['nombre_rol'];
        }
        mysqli_stmt_close($stmt_role);
    } else {
        error_log("Error al preparar la consulta de rol: " . mysqli_error($conn));
    }
    // No cerramos la conexión aún, los paneles podrían necesitarla. Se cerrará al final.
} else {
    // Error de conexión ya manejado en connectDB(), pero puedes añadir lógica aquí si es necesario.
    error_log("Dashboard: No se pudo conectar a la BD.");
}

// 4. Determinar qué panel de contenido incluir según el rol
$panel_path = '';
switch ($user_rol_id) {
    case 1: // Estudiante
        $panel_path = 'php/paneles/estudiante_panel_contenido.php';
        break;
    case 2: // Profesor
        $panel_path = 'php/paneles/profesor_panel_contenido.php';
        break;
    case 3: // Administrador
        $panel_path = 'php/paneles/admin_panel_contenido.php';
        break;
    default:
        // Rol no reconocido o no manejado
        $panel_path = 'php/paneles/error_panel_contenido.php'; // Un panel de error genérico
}

// ----- INICIO DE LA SALIDA HTML -----
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard: <?php echo htmlspecialchars($nombre_rol); ?> - Plataforma de Notas</title>
    <link rel="stylesheet" href="style.css"> <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;600&family=IBM+Plex+Serif:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* Estilos específicos para el layout del dashboard y paneles */
        body {
            background-color: #f0f2f5; /* Un fondo más neutro para el dashboard */
            background-image: none; /* Quitar imagen de fondo si la tenías en body general */
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            color: #333;
        }
        .dashboard-main-header { /* Hereda .main-header pero podemos ajustar */
            background-color: #2c3e50; /* Un color de cabecera para el dashboard */
        }
        .dashboard-main-header nav a {
            color: #ecf0f1; /* Texto claro para cabecera oscura */
        }
        .dashboard-content-wrapper {
            width: 90%;
            max-width: 1200px;
            margin: 30px auto;
            padding: 25px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            flex-grow: 1;
        }
        .dashboard-content-wrapper h1, .dashboard-content-wrapper h2, .dashboard-content-wrapper h3 {
            font-family: 'IBM Plex Serif', serif;
            color: #1e50e3; /* Azul principal para títulos */
        }
        .dashboard-content-wrapper h1 { font-size: 28px; margin-bottom: 20px; }
        .dashboard-content-wrapper h2 { font-size: 24px; margin-bottom: 15px; margin-top: 30px; }
        .dashboard-content-wrapper h3 { font-size: 20px; margin-bottom: 10px; margin-top: 25px; }

        .module-menu ul {
            list-style: none;
            padding: 0;
        }
        .module-menu li {
            margin-bottom: 8px;
        }
        .module-menu li a {
            display: block;
            padding: 12px 18px;
            background-color: #e9ecef;
            color: #343a40;
            text-decoration: none;
            border-radius: 5px;
            font-family: 'IBM Plex Sans', sans-serif;
            font-weight: 600;
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        .module-menu li a:hover {
            background-color: #1e50e3;
            color: #ffffff;
        }
        .main-footer {
            margin-top: auto; /* Para empujar el footer hacia abajo */
             background-color: #343a40; /* Footer oscuro para consistencia */
             color: #f8f9fa;
        }
    </style>
</head>
<body>

    <header class="main-header dashboard-main-header">
        <nav>
            <ul>
                <li><a href="dashboard.php">Inicio Panel (<?php echo htmlspecialchars($nombre_rol); ?>)</a></li>
                <li><a href="#">Mi Perfil</a></li> <li><a href="php/logout.php">Cerrar Sesión (<?php echo htmlspecialchars($user_nombre); ?>)</a></li>
            </ul>
        </nav>
    </header>

    <main class="dashboard-content-wrapper">
        <?php
        if (file_exists($panel_path)) {
            // La conexión $conn está disponible para los archivos incluidos si la necesitan
            include $panel_path;
        } else {
            echo "<h1>Error del Sistema</h1>";
            echo "<p>No se pudo cargar el módulo correspondiente a su rol. Por favor, contacte al administrador.</p>";
            error_log("Error en Dashboard: Panel no encontrado - " . $panel_path . " para rol ID: " . $user_rol_id);
        }
        ?>
    </main>

    <footer class="main-footer">
        <p>&copy; <?php echo date("Y"); ?> Plataforma de Notas. Todos los izquierdos reservados.</p>
    </footer>

<?php
// Cerrar la conexión a la BD al final de la página
if ($conn) {
    mysqli_close($conn);
}
?>
</body>
</html>