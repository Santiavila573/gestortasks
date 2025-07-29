<?php
session_start();
require_once('C:\xampp\htdocs\gestortasks\app\config\db.php');

// Verificar que el usuario esté autenticado
if (!isset($_SESSION["usuario_id"])) {
    define("ROOT_PATH", "http://localhost/gestortasks/app/views/login.php"); // Ajusta la ruta real
    header("Location: " . ROOT_PATH);
    exit;
}

$usuario_id = $_SESSION["usuario_id"];

// Procesar creación de sprint (simulación básica)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["crear_sprint"])) {
    $nombre = $_POST["nombre"] ?? "Sprint " . time();
    $fecha_inicio = $_POST["fecha_inicio"] ?? date("Y-m-d");
    $fecha_fin = $_POST["fecha_fin"] ?? date("Y-m-d", strtotime("+1 week"));
    $proyecto_id = 1; // Ajusta según tu lógica para obtener proyecto_id

    $stmt = $pdo->prepare("INSERT INTO sprints (nombre, fecha_inicio, fecha_fin, proyecto_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nombre, $fecha_inicio, $fecha_fin, $proyecto_id]);
    define("ROOT_PATH", "http://localhost/gestortasks/app/controllers/logic/sprints.php"); // Ajusta la ruta real
    header("Location: " . ROOT_PATH);
    exit;
}

// Preparar la consulta para obtener los sprints asociados al usuario
$stmt = $pdo->prepare("
    SELECT s.* 
    FROM sprints s
    JOIN proyecto_usuarios pu ON pu.proyecto_id = s.proyecto_id
    WHERE pu.usuario_id = :uid
    ORDER BY s.fecha_inicio DESC
");
$stmt->bindParam(":uid", $usuario_id);
$stmt->execute();
$sprints = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lógica para el chatbot
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_SERVER["HTTP_CONTENT_TYPE"]) && $_SERVER["HTTP_CONTENT_TYPE"] === "application/json") {
    $input = json_decode(file_get_contents("php://input"), true);
    if (isset($input["user_input"]) && strtolower($input["user_input"]) === "sprints") {
        $response = "";
        if (count($sprints) > 0) {
            $response .= "<table border='1' class='sprint-table'>";
            $response .= "<tr><th>Nombre</th><th>Inicio</th><th>Fin</th><th>Estado</th></tr>";
            $today = new DateTime();
            foreach ($sprints as $sprint) {
                $fecha_inicio = new DateTime($sprint['fecha_inicio']);
                $fecha_fin = new DateTime($sprint['fecha_fin']);
                $estado = '';

                if ($today < $fecha_inicio) {
                    $estado = 'Pendiente';
                } elseif ($today >= $fecha_inicio && $today <= $fecha_fin) {
                    $estado = 'En Curso';
                } else {
                    $estado = 'Finalizado';
                }

                $response .= "<tr>";
                $response .= "<td>" . htmlspecialchars($sprint['nombre']) . "</td>";
                $response .= "<td>" . htmlspecialchars($sprint['fecha_inicio']) . "</td>";
                $response .= "<td>" . htmlspecialchars($sprint['fecha_fin']) . "</td>";
                $response .= "<td>" . $estado . "</td>";
                $response .= "</tr>";
            }
            $response .= "</table>";
        } else {
            $response .= "No hay sprints disponibles.";
        }
        echo $response;
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Sprints - GestorTasks</title>
    <style>
        /* Estilos generales */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            /* Fuente y color del texto */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #2c3e50;
            line-height: 1.6;
            /* Fondo y padding */
            background: linear-gradient(135deg, #f0f4f8, #e0e7f0);
            padding: 20px;
            min-height: 100vh;
            /* Animación de fondo */
            background-size: 200% 200%;
            animation: gradient 3s ease infinite;
        }

        @keyframes gradient {
            0% {
                background-position: 0% 0%;
            }
            50% {
                background-position: 100% 0%;
            }
            100% {
                background-position: 0% 0%;
            }
        }

        /* Contenedor principal */
        .container {
            /* Tamaño y posición */
            max-width: 1200px;
            margin: 0 auto;
            /* Fondo y sombra */
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            /* Animación de entrada */
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Título principal */
        h1 {
            /* Tamaño y estilo de fuente */
            font-size: 2.5rem;
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            /* Fondo gradiente */
            background: linear-gradient(90deg, #3498db, #2980b9);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            /* Animación de texto */
            animation: textGradient 3s ease infinite;
        }

        @keyframes textGradient {
            0% {
                background-position: 0% 0%;
            }
            50% {
                background-position: 100% 0%;
            }
            100% {
                background-position: 0% 0%;
            }
        }

        /* Lista de sprints */
        .sprint-list {
            /* Estilos de la lista */
            list-style: none;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
            /* Animación de entrada */
            animation: fadeIn 0.5s ease-in-out;
        }

        /* Item de la lista de sprints */
        .sprint-item {
            /* Fondo y sombra */
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            /* Borde izquierdo */
            border-left: 5px solid #3498db;
            /* Animación de entrada */
            animation: fadeIn 0.5s ease-in-out;
        }

        .sprint-item:hover {
            /* Sombra y transformación */
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(13, 50, 172, 0.2);
        }

        .sprint-item a {
            /* Enlace */
            text-decoration: none;
            font-size: 1.3rem;
            color: #3498db;
            font-weight: 600;
            display: block;
            margin-bottom: 10px;
            transition: color 0.3s ease;
            /* Animación de texto */
            animation: textGradient 3s ease infinite;
        }

        .sprint-item a:hover {
            /* Color del enlace al pasar por encima */
            color: #2980b9;
        }

        .sprint-item p {
            /* Texto */
            font-size: 0.95rem;
            color: #7f8c8d;
            margin: 5px 0;
            /* Animación de texto */
            animation: textGradient 3s ease infinite;
        }

        .sprint-item p strong {
            /* Texto importante */
            color: #2c3e50;
        }

        /* Formulario para crear un nuevo sprint */
        .crear-sprint {
            /* Fondo y sombra */
            background: #f9fbfd;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            /* Borde izquierdo */
            border-left: 5px solid #2980b9;
            /* Animación de entrada */
            animation: fadeIn 0.5s ease-in-out;
        }

        .crear-sprint form {
            /* Flexbox para los campos del formulario */
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            /* Animación de entrada */
            animation: fadeIn 0.5s ease-in-out;
        }

        .crear-sprint input {
            /* Campos de texto */
            flex: 1 1 200px;
            padding: 12px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            /* Animación de texto */
            animation: textGradient 3s ease infinite;
        }

        .crear-sprint input:focus {
            /* Foco en los campos de texto */
            border-color: #3498db;
            outline: none;
        }

        .crear-sprint button {
            /* Botón de envío */
            flex: 0 0 auto;
            padding: 12px 25px;
            background: linear-gradient(90deg, #2980b9, #2980b9);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.3s ease, background 0.3s ease;
            /* Animación de texto */
            animation: textGradient 3s ease infinite;
        }

        .crear-sprint button:hover {
            /* Efecto hover en el botón de envío */
            transform: translateY(-2px);
            background: linear-gradient(90deg, #3498db, #3498db);
        }

        .acciones {
            /* Enlaces para crear un nuevo sprint */
            text-align: center;
        }

        .acciones a {
            /* Enlace para crear un nuevo sprint */
            display: inline-block;
            padding: 12px 25px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 1rem;
            transition: background 0.3s ease, transform 0.3s ease;
            /* Animación de texto */
            animation: textGradient 3s ease infinite;
        }

        .acciones a:hover {
            /* Efecto hover en el enlace para crear un nuevo sprint */
            background: #2980b9;
            transform: translateY(-2px);
        }

        p {
            /* Texto en el pie de página */
            text-align: center;
            color: #7f8c8d;
            font-size: 1.1rem;
            padding: 20px;
            /* Animación de texto */
            animation: textGradient 3s ease infinite;
        }

        /* Estilo para la tabla de sprints generada por el chatbot */
        .sprint-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .sprint-table th,
        .sprint-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .sprint-table th {
            background-color: #f2f2f2;
            color: #333;
        }

        .sprint-table td {
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .sprint-table td:hover {
            background-color: #f1f1f1;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0, 0, 0);
            background-color: rgba(0, 0, 0, 0.4);
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 700px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            /* Estilos para pantallas pequeñas */
            .container {
                padding: 15px;
            }

            h1 {
                font-size: 2rem;
            }

            .sprint-list {
                grid-template-columns: 1fr;
            }

            .crear-sprint form {
                flex-direction: column;
            }

            .crear-sprint input,
            .crear-sprint button {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <?php if (count($sprints) > 0): ?>
            <ul class="sprint-list">
                <?php foreach ($sprints as $sprint): ?>
                    <li class="sprint-item">
                        <a href="http://localhost:3000/app/views/ver_tareas_sprint.php?sprint_id=<?= $sprint['id'] ?>">
                            <?= htmlspecialchars($sprint['nombre']) ?>
                        </a>
                        <p><strong>Inicio:</strong> <?= htmlspecialchars($sprint['fecha_inicio'] ?: 'No definida') ?></p>
                        <p><strong>Fin:</strong> <?= htmlspecialchars($sprint['fecha_fin'] ?: 'No definida') ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No hay sprints disponibles.</p>
        <?php endif; ?>

        <?php if ($_SESSION['rol'] === 'Scrum Master'): ?>
            <div class="crear-sprint">
                <form method="POST">
                    <input type="text" name="nombre" placeholder="Nombre del sprint" required>
                    <input type="date" name="fecha_inicio" required>
                    <input type="date" name="fecha_fin" required>
                    <button type="submit" name="crear_sprint">Crear Sprint</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="acciones">
            <a href="http://localhost:3000/app/controllers/negocio/proyectos.php">Volver atrás</a>
        </div>
    </div>
</body>

</html>

