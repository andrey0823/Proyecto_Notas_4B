<?php
// php/paneles/admin_panel_contenido.php
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol_id'] != 3) {
    echo "<p>Acceso restringido.</p>";
    return;
}
$action = $_GET['action'] ?? 'inicio_admin';
?>

<h1>Módulo Administrador</h1>
<p>Bienvenido/a, Administrador/a <?php echo htmlspecialchars($_SESSION['user_nombre']); ?>.</p>

<nav class="module-menu">
    <ul>
        <li><a href="dashboard.php?action=gestionar_usuarios">1. Gestionar usuarios</a></li>
        <li><a href="dashboard.php?action=generar_cambiar_contrasenas">2. Generar/cambiar contraseñas</a></li>
        <li><a href="dashboard.php?action=asignar_roles">3. Asignar roles</a></li>
        <li><a href="dashboard.php?action=verificar_modificar_info">4. Verificar/modificar información</a></li>
        <li><a href="dashboard.php?action=eliminar_info">5. Eliminar información</a></li>
    </ul>
</nav>
<hr style="margin: 20px 0;">

<?php
switch ($action) {
    case 'gestionar_usuarios':
        echo "<h2>Gestionar Usuarios</h2><p>Aquí podrás crear, modificar y eliminar usuarios. (Funcionalidad en desarrollo)</p>";
        // Sub-menú o lógica para CRUD de usuarios
        break;
    // Añadir más casos para cada acción del admin...
    case 'inicio_admin':
    default:
        echo "<p>Selecciona una opción del menú para comenzar.</p>";
        break;
}
?>