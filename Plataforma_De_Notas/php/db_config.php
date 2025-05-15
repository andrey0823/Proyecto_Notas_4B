<?php
// php/db_config.php

// --- Configuración de la Base de Datos ---
define('DB_HOST', 'localhost');     // O tu host de BD (ej: 127.0.0.1)
define('DB_USER', 'root'); // Reemplaza con tu usuario de MySQL
define('DB_PASS', ''); // Reemplaza con tu contraseña de MySQL
define('DB_NAME', 'gestion_notas'); // El nombre de tu base de datos

// --- Función para Conectar a la BD ---
function connectDB() {
    // Crear conexión usando mysqli
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Verificar conexión
    if (!$conn) {
        // En un entorno de producción, loggear el error en lugar de mostrarlo
        error_log("Error de conexión a la base de datos: " . mysqli_connect_error());
        die("Error de conexión. Por favor, inténtelo más tarde."); // Mensaje genérico al usuario
    }

    // Establecer el charset a utf8mb4 (recomendado)
    if (!mysqli_set_charset($conn, "utf8mb4")) {
        error_log("Error al establecer el charset UTF-8: " . mysqli_error($conn));
        // No es necesario 'die' aquí, pero sí loggear el error.
    }

    return $conn;
}

// --- (Opcional) Configuración de la Sesión ---
// Establecer parámetros de cookie de sesión seguros (descomentar y ajustar si usas HTTPS)
/*
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
ini_set('session.cookie_samesite', 'Lax'); // O 'Strict'
*/

?>