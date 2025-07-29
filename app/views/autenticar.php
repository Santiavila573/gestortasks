<?php
/**
 * Autenticación de usuarios
 *
 * Verifica si el usuario y contraseña coinciden con los registros en la base de datos.
 * Si coincide, crea una sesión con los datos del usuario y redirige a la página principal.
 * Si no coincide, redirige a la página de inicio de sesión con un parámetro de error.
 */

session_start();
require_once('C:\xampp\htdocs\gestortasks\app\config\db.php');

// Verificar si se ha enviado el formulario de inicio de sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Obtener los datos del formulario
    $correo = trim($_POST['correo']);
    $contrasena = $_POST['contrasena'];

    // Verificar si los campos están vacíos
    if (!empty($correo) && !empty($contrasena)) {

        // Preparar la consulta para buscar al usuario en la base de datos
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = :correo LIMIT 1");
        $stmt->bindParam(':correo', $correo);
        $stmt->execute();

        // Obtener los resultados de la consulta
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verificar si el usuario existe y la contraseña coincide
        if ($usuario && password_verify($contrasena, $usuario['contrasena'])) {

            // Crear una sesión con los datos del usuario
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nombre'] = $usuario['nombre'];
            $_SESSION['rol'] = $usuario['rol'];

            // Redirigir a la página principal
            header("Location: index.php");
            exit;

        } else {

            // Redirigir a la página de inicio de sesión con un parámetro de error
            header("Location: login.php?error=1");
            exit;
        }

    } else {

        // Redirigir a la página de inicio de sesión con un parámetro de error
        header("Location: login.php?error=1");
        exit;
    }

} else {

    // Redirigir a la página de inicio de sesión
    header("Location: login.php");
    exit;
}

