<?php
// Iniciar sesión
session_start();

// Si ya se ha iniciado sesión, redirigir a la página principal
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión - GestorTasks</title>
<style>
    /* Estilos generales */
    body {
        font-family: Arial, sans-serif;
        background: #ecf0f1;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }

    /* Estilos para la caja de login */
    .login-box {
        background: #ffffff;
        padding: 40px;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        width: 350px;
        text-align: center;
    }

    /* Títulos */
    h2 {
        color: #3498db; /* Azul celeste */
        margin-bottom: 20px;
        font-size: 24px;
    }

    /* Estilos para inputs */
    input {
        width: 100%;
        padding: 12px;
        margin: 10px 0;
        border: 1px solid #3498db; /* Borde azul celeste */
        border-radius: 5px;
        font-size: 16px;
        transition: border-color 0.3s;
    }

    input:focus {
        border-color: #2980b9; /* Azul más oscuro al enfocar */
        outline: none;
    }

    /* Estilos para el botón */
    button {
        width: 100%;
        padding: 12px;
        background: #3498db; /* Azul celeste */
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        transition: background 0.3s;
    }

    button:hover {
        background: #2980b9; /* Azul más oscuro al pasar el mouse */
    }

    /* Estilos para mostrar errores */
    .error {
        color: red;
        text-align: center;
        margin-top: 10px;
        font-size: 14px;
    }

    /* Estilo para enlaces */
    a {
        color: #3498db; /* Azul celeste */
        text-decoration: none;
        font-size: 14px;
    }

    a:hover {
        text-decoration: underline;
    }
</style>
</head>
<body>
<div class="login-box">
    <h2>GestorTasks - Login</h2>

    <?php
    // Mostrar mensaje de error si se intentó acceder con credenciales incorrectas
    if (isset($_GET['error'])):
    ?>
        <p class="error">Credenciales incorrectas</p>
    <?php endif; ?>

    <!-- Formulario para iniciar sesión -->
    <form method="POST" action="autenticar.php">
        <!-- Email -->
        <input type="email" name="correo" placeholder="Correo electrónico" required>
        <!-- Contraseña -->
        <input type="password" name="contrasena" placeholder="Contraseña" required>
        <!-- Botón para enviar el formulario -->
        <button type="submit">Iniciar sesión</button>
    </form>

    <!-- Enlace para registrarse -->
    <p>¿No tienes una cuenta? <a href="http://localhost:3000/app/views/crear_usuario.php">Crear cuenta</a></p>
</div>
</body>
</html>

