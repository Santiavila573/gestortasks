<?php
// Host de la base de datos
$host = 'localhost';

// Nombre de la base de datos
$db = 'gestortasks_db';

// Usuario y contraseña de la base de datos
$user = 'root';
$pass = '';

try {
    // Intentar conectar a la base de datos con los datos proporcionados
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);

    // Establecer el atributo para que se muestren los errores de la base de datos
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Mostrar mensaje de éxito al conectar a la base de datos
    // echo "Conexión exitosa";
} catch (PDOException $e) {
    // Mostrar el mensaje de error si no se puede conectar a la base de datos
    die("Error en la conexión: " . $e->getMessage());
}

?>
