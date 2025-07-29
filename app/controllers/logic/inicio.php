<?php
// Iniciar sesión y verificar si el usuario ha iniciado sesión

session_start();
if (!isset($_SESSION['usuario_id'])) {
    // Si no ha iniciado sesión, redirigir a la página de inicio de sesión
    define('ROOT_PATH', 'http://localhost/gestortasks/app/views/login.php'); // Ajusta la ruta real
    header('Location: ' . ROOT_PATH);
    exit;

}
?>
<!-- Mostrar el nombre del usuario y su rol -->
<h2>Hola, <?= htmlspecialchars($_SESSION['nombre']) ?> (<?= $_SESSION['rol'] ?>)</h2>
<!-- Enlace para cerrar la sesión -->
<a href="logout.php">Cerrar sesión</a>

