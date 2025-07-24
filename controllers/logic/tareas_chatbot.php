<?php
session_start();
require_once('C:\xampp\htdocs\gestortasks\app\config\db.php');

// Definir ROOT_PATH de forma incondicional
define("ROOT_PATH", "http://localhost/gestortasks"); // Ajusta la ruta base de tu proyecto

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . ROOT_PATH . '/app/views/login.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Lógica para el chatbot
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_CONTENT_TYPE']) && $_SERVER['HTTP_CONTENT_TYPE'] === 'application/json') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['user_input']) && strtolower($input['user_input']) === 'tareas') {
        
        // Obtener todas las tareas del usuario
        $stmt = $pdo->prepare("
            SELECT t.id, t.titulo, t.descripcion, t.prioridad, t.estado, t.fecha_estimada, 
                   u.nombre AS asignado_nombre, p.nombre AS proyecto_nombre
            FROM tareas t
            LEFT JOIN usuarios u ON t.asignado_id = u.id
            LEFT JOIN proyectos p ON t.proyecto_id = p.id
            JOIN proyecto_usuarios pu ON pu.proyecto_id = t.proyecto_id
            WHERE pu.usuario_id = :uid
            ORDER BY t.fecha_estimada ASC, t.prioridad DESC
            LIMIT 50
        ");
        $stmt->bindParam(':uid', $usuario_id);
        $stmt->execute();
        $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response = "";
        if (count($tareas) > 0) {
            $response .= "<table border='1'>";
            $response .= "<tr><th>ID</th><th>Título</th><th>Proyecto</th><th>Estado</th><th>Prioridad</th><th>Asignado</th><th>Fecha Estimada</th></tr>";
            
            foreach ($tareas as $tarea) {
                $response .= "<tr>";
                $response .= "<td>" . htmlspecialchars($tarea['id']) . "</td>";
                $response .= "<td>" . htmlspecialchars($tarea['titulo']) . "</td>";
                $response .= "<td>" . htmlspecialchars($tarea['proyecto_nombre'] ?: 'Sin proyecto') . "</td>";
                $response .= "<td>" . htmlspecialchars($tarea['estado']) . "</td>";
                $response .= "<td>" . htmlspecialchars($tarea['prioridad']) . "</td>";
                $response .= "<td>" . htmlspecialchars($tarea['asignado_nombre'] ?: 'No asignado') . "</td>";
                $response .= "<td>" . htmlspecialchars($tarea['fecha_estimada'] ?: 'No definida') . "</td>";
                $response .= "</tr>";
            }
            $response .= "</table>";
        } else {
            $response .= "No hay tareas disponibles.";
        }
        echo $response;
        exit;
    }
}

// Si no es una solicitud del chatbot, redirigir o mostrar error
header('Location: ' . ROOT_PATH . '/app/controllers/negocio/proyectos.php');
exit;
?>