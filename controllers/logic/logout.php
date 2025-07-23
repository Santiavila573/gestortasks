<?php
// Iniciar sesión para eliminar las variables de sesión
session_start();

// Destruir la sesión actual
session_destroy();


// Redirigir a la página de inicio de sesión
define('ROOT_PATH', 'http://localhost/gestortasks/app/views/login.php'); // Ajusta la ruta real
header('Location: ' . ROOT_PATH);
exit;

// Salir del script para evitar que se ejecute código adicional
exit;

