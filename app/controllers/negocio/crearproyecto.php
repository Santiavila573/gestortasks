<?php
session_start();

// Verificar si el usuario está autenticado y es un Scrum Master
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'Scrum Master') {
    header('Location: http://localhost/gestortasks/app/views/login.php');
    exit;
}

require_once('C:\xampp\htdocs\gestortasks\app\config\db.php');

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Location: http://localhost/gestortasks/app/views/login.php');
    exit;
}

// Verificar si el usuario es un Scrum Master
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Scrum Master') {
    header('Location: http://localhost/gestortasks/app/views/login.php');
    exit;
}

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Location: http://localhost/gestortasks/app/views/login.php');
    exit;
}

// Verificar si el usuario es un Scrum Master
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Scrum Master') {
    header('Location: http://localhost/gestortasks/app/views/login.php');
    exit;
}

// Obtener el ID del Scrum Master actual
$scrum_master_id = $_SESSION['usuario_id'];

// Verificar si el usuario es un Scrum Master
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Scrum Master') {
    header('Location: http://localhost/gestortasks/app/views/login.php');
    exit;
}

// Obtener los proyectos del Scrum Master
$stmt = $pdo->prepare("SELECT proyectos.* FROM proyectos INNER JOIN proyecto_usuarios ON proyectos.id = proyecto_usuarios.proyecto_id AND proyecto_usuarios.usuario_id = :scrum_master_id");
$stmt->execute(['scrum_master_id' => $scrum_master_id]);
$proyectos = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Verificar si el formulario se ha enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener y sanitizar los datos del formulario
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];
    $propietario_id = $_SESSION['usuario_id'];

    // Verificar que los campos obligatorios no estén vacíos
    if ($nombre && $fecha_inicio) {
        // Insertar el nuevo proyecto en la base de datos
        $stmt = $pdo->prepare("INSERT INTO proyectos (nombre, descripcion, fecha_inicio, fecha_fin, propietario_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $descripcion, $fecha_inicio, $fecha_fin, $propietario_id]);
        $proyecto_id = $pdo->lastInsertId();

        // Asignar el proyecto al Scrum Master
        $stmt2 = $pdo->prepare("INSERT INTO proyecto_usuarios (proyecto_id, usuario_id) VALUES (?, ?)");
        $stmt2->execute([$proyecto_id, $propietario_id]);

        // Redirigir a la lista de proyectos
        header("Location: proyectos.php");
        exit;
    } else {
        $mensaje = "El nombre y la fecha de inicio son obligatorios.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Proyecto - GestorTasks</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f0f4f8, #e0e7f0);
            color: #2c3e50;
            line-height: 1.6;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .formulario {
            background: white;
            padding: 30px;
            max-width: 600px;
            width: 100%;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border-left: 6px solid #3498db;
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            font-size: 2rem;
            color: #2c3e50;
            text-align: center;
            margin-bottom: 25px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            background: linear-gradient(90deg, #3498db, #2980b9);
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .error {
            color: #e74c3c;
            font-size: 0.95rem;
            text-align: center;
            margin-bottom: 15px;
            padding: 10px;
            background: #fdecea;
            border-radius: 5px;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        label {
            font-size: 1rem;
            font-weight: 600;
            color: black;
            margin-bottom: 5px;
        }

        input, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        input:focus, textarea:focus {
            border-color: #3498db;
            box-shadow: 0 0 5px rgba(8, 95, 153, 0.5);
            outline: none;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        button {
            padding: 12px 25px;
            background: linear-gradient(90deg, #3498db, #3498db);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.3s ease, background 0.3s ease;
            align-self: flex-end;
        }

        button:hover {
            transform: translateY(-2px);
            background: linear-gradient(90deg, #3498db, #3498db);
        }

        @media (max-width: 768px) {
            .formulario {
                padding: 20px;
                margin: 10px;
            }

            h2 {
                font-size: 1.8rem;
            }

            button {
                width: 100%;
                align-self: center;
            }
        }
    </style>
</head>
<body>
<div class="formulario">
    <h2>Crear Nuevo Proyecto</h2>

    <?php if (isset($mensaje)): ?>
        <p class="error"><?= $mensaje ?></p>
    <?php endif; ?>

    <!-- Formulario para crear un nuevo proyecto -->
    <form method="POST">
        <label>Nombre del proyecto:</label>
        <input type="text" name="nombre" required>

        <label>Descripción:</label>
        <textarea name="descripcion" rows="4"></textarea>

        <label>Fecha de inicio:</label>
        <input type="date" name="fecha_inicio" required>

        <label>Fecha estimada de finalización:</label>
        <input type="date" name="fecha_fin">

        <button type="submit">Crear proyecto</button>
    </form>
</div>
</body>
</html>
