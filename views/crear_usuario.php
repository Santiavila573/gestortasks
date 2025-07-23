<?php
/**
 * Archivo de registro de usuarios (admin)
 */

// Requerir el archivo de configuración de la base de datos
require_once('C:\xampp\htdocs\gestortasks\app\config\db.php');

// Inicializar el mensaje de error o éxito
$mensaje = "";

// Verificar si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener los datos del formulario
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $contrasena = $_POST['contrasena'];
    $rol = $_POST['rol'];

    // Verificar si los campos están vacíos
    if (!empty($nombre) && !empty($correo) && !empty($contrasena) && !empty($rol)) {
        // Encriptar la contraseña
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);

        // Preparar la consulta para insertar en la base de datos
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, correo, contrasena, rol, estado) VALUES (?, ?, ?, ?, 1)");

        try {
            // Ejecutar la consulta
            $stmt->execute([$nombre, $correo, $hash, $rol]);

            // Mostrar mensaje de éxito
            $mensaje = "✅ Usuario registrado con éxito.";
        } catch (PDOException $e) {
            // Mostrar mensaje de error
            $mensaje = "❌ Error: " . $e->getMessage();
        }
    } else {
        // Mostrar mensaje de error si los campos están vacíos
        $mensaje = "⚠️ Todos los campos son obligatorios.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Usuario (Admin)</title>
<style>
    /* Estilos generales */
    body {
        font-family: Arial, sans-serif;
        background: #ecf0f1; /* Fondo suave */
        padding: 40px;
        margin: 0;
    }

    /* Estilos para el formulario */
    .formulario {
        background: white;
        max-width: 500px;
        margin: auto;
        padding: 30px; /* Aumenté el padding para mayor espacio */
        border-radius: 15px; /* Bordes más redondeados */
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    /* Estilos para inputs y selects */
    input, select {
        width: 100%;
        padding: 12px; /* Mayor padding */
        margin: 10px 0;
        border: 1px solid #3498db; /* Borde azul celeste */
        border-radius: 5px;
        font-size: 16px;
        transition: border-color 0.3s;
    }

    input:focus, select:focus {
        border-color: #2980b9; /* Azul más oscuro al enfocar */
        outline: none;
    }

    /* Estilos para el botón */
    button {
        padding: 12px; /* Mayor padding */
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

    /* Estilos para mensajes de éxito y error */
    .mensaje {
        margin: 10px 0;
        padding: 10px;
        border-radius: 5px;
        font-size: 14px;
    }

    .success {
        background-color: #dff0d8; /* Fondo verde claro */
        color: #3c763d; /* Texto verde */
    }

    .error {
        background-color: #f2dede; /* Fondo rojo claro */
        color: #a94442; /* Texto rojo */
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

<div class="formulario">
    <h2>Registrar Usuario (Admin)</h2>

    <?php if ($mensaje): ?>
        <div class="mensaje <?= strpos($mensaje, '✅') !== false ? 'success' : 'error' ?>">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <label>Nombre:</label>
        <input type="text" name="nombre" required>

        <label>Correo electrónico:</label>
        <input type="email" name="correo" required>

        <label>Contraseña:</label>
        <input type="password" name="contrasena" required>

        <label>Rol:</label>
        <select name="rol" required>
            <option value="">Seleccionar...</option>
            <option value="Scrum Master">Scrum Master</option>
            <option value="Product Owner">Product Owner</option>
            <option value="Developer">Developer</option>
        </select>

        <button type="submit">Crear usuario</button>
    </form>
</div>

</body>
</html>

