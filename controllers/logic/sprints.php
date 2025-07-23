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
    header("Location: " . ROOT_PATH . "/app/controllers/logic/sprints.php"); // Redirige a sí mismo o a otra página
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
            $response .= "<table border='1'>";
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
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        h1 {
            font-size: 2.5rem;
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            background: linear-gradient(90deg, #3498db, #2980b9);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .sprint-list {
            list-style: none;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .sprint-item {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 5px solid #3498db;
        }

        .sprint-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(13, 50, 172, 0.2);
        }

        .sprint-item a {
            text-decoration: none;
            font-size: 1.3rem;
            color: #3498db;
            font-weight: 600;
            display: block;
            margin-bottom: 10px;
            transition: color 0.3s ease;
        }

        .sprint-item a:hover {
            color: #2980b9;
        }

        .sprint-item p {
            font-size: 0.95rem;
            color: #7f8c8d;
            margin: 5px 0;
        }

        .sprint-item p strong {
            color: #2c3e50;
        }

        .crear-sprint {
            background: #f9fbfd;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            border-left: 5px solid #2980b9;
        }

        .crear-sprint form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .crear-sprint input {
            flex: 1 1 200px;
            padding: 12px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .crear-sprint input:focus {
            border-color: #3498db;
            outline: none;
        }

        .crear-sprint button {
            flex: 0 0 auto;
            padding: 12px 25px;
            background: linear-gradient(90deg, #2980b9, #2980b9);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.3s ease, background 0.3s ease;
        }

        .crear-sprint button:hover {
            transform: translateY(-2px);
            background: linear-gradient(90deg, #3498db, #3498db);
        }

        .acciones {
            text-align: center;
        }

        .acciones a {
            display: inline-block;
            padding: 12px 25px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 1rem;
            transition: background 0.3s ease, transform 0.3s ease;
        }

        .acciones a:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        p {
            text-align: center;
            color: #7f8c8d;
            font-size: 1.1rem;
            padding: 20px;
        }

        @media (max-width: 768px) {
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
        <h1>Lista de Sprints</h1>

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