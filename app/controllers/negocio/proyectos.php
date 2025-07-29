<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: http://localhost/gestortasks/app/views/login.php');
    exit;
}

require_once('C:\xampp\htdocs\gestortasks\app\config\db.php');

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: http://localhost/gestortasks/app/views/login.php');
    exit;
}

// Verificar el rol del usuario
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Scrum Master') {
    header('Location: http://localhost/gestortasks/app/views/login.php');
    exit;
}



$usuario_id = $_SESSION['usuario_id'];
$rol = $_SESSION['rol'];

// Configuración personalizable del rango de días (por ahora fijo, luego configurable)
$reminder_range_days = 2; // Cambia esto o hazlo dinámico

// Obtener proyectos asociados al usuario con conteo de tareas
$stmt = $pdo->prepare("
    SELECT p.*, COUNT(t.id) as pending_tasks, COUNT(t2.id) as total_tasks
    FROM proyectos p
    JOIN proyecto_usuarios pu ON pu.proyecto_id = p.id
    LEFT JOIN tareas t ON t.proyecto_id = p.id AND t.estado = 'Por hacer'
    LEFT JOIN tareas t2 ON t2.proyecto_id = p.id
    WHERE pu.usuario_id = :uid
    GROUP BY p.id
");
$stmt->bindParam(':uid', $usuario_id);
$stmt->execute();
$proyectos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener tareas pendientes para el usuario y equipo
$stmt = $pdo->prepare("
    SELECT t.id, t.titulo, t.descripcion, t.prioridad, t.estado, t.fecha_estimada, t.nota,
           u.nombre as asignado_nombre, u.id as asignado_id
    FROM tareas t
    JOIN usuarios u ON u.id = t.asignado_id
    JOIN proyecto_usuarios pu ON pu.proyecto_id = t.proyecto_id
    WHERE pu.usuario_id = :uid AND t.estado NOT IN ('Hecha')
");
$stmt->bindParam(':uid', $usuario_id);

$tareas_pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Calcular recordatorios según el rango personalizado
$today = new DateTime('2025-07-11');
$reminders = [];
foreach ($tareas_pendientes as $tarea) {
    if ($tarea['fecha_estimada']) {
        $endDate = new DateTime($tarea['fecha_estimada']);
        $interval = $today->diff($endDate);
        if ($interval->days <= $reminder_range_days && $interval->days >= 0) {
            $reminders[$tarea['asignado_id']][] = $tarea;
        }
    }
}


?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Proyectos - GestorTasks</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="gestortasks/app/assets/js/api/api_integrated_chatbot.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f0f4f8, #e0e7f0);
            padding: 30px;
            margin: 0;
            color: #2c3e50;
            line-height: 1.6;
        }

        .container {
            max-width: 1300px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            height: calc(100vh - 60px);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 15px;
            flex-shrink: 0;
        }

        h1 {
            font-size: 2rem;
            color: #2c3e50;
            margin: 0;
            font-weight: 600;
        }

        .crear {
            padding: 12px 20px;
            background: linear-gradient(90deg, #3498db, #3498db);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-size: 16px;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .crear:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(7, 98, 173, 0.4);
        }

        .search-filter {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            flex-shrink: 0;
        }

        .search-filter input,
        .search-filter select {
            padding: 10px;
            border: 2px solid #ecf0f1;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s;
        }

        .search-filter input:focus,
        .search-filter select:focus {
            border-color: #3498db;
            outline: none;
        }

        .search-filter button {
            padding: 10px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .search-filter button:hover {
            background: #2980b9;
        }

        .content-wrapper {
            display: flex;
            flex-grow: 1;
            gap: 20px;
            overflow: hidden;
        }

        .calendar-panel {
            flex: 1;
            max-width: 500px;
            background: #f9fbfd;
            border-radius: 10px;
            padding: 15px;
            overflow-y: auto;
        }

        .proyectos-lista {
            flex: 1;
            background: #f9fbfd;
            border-radius: 10px;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
            align-items: flex-end;
        }

        .chatbot-panel {
            flex: 0 0 300px;
            background: #f9fbfd;
            border-radius: 10px;
            padding: 15px;
            overflow-y: auto;
            display: none;
            flex-direction: column;
            border: 1px solid #ecf0f1;
        }

        .chatbot-panel.active {
            display: flex;
        }

        .chatbot-messages {
            max-height: 250px;
            overflow-y: auto;
            padding: 15px;
            border-bottom: 1px solid #ecf0f1;
        }

        .chatbot-messages p {
            margin: 8px 0;
            font-size: 0.95rem;
        }

        .chatbot-input {
            width: 100%;
            padding: 10px;
            border: none;
            border-top: 1px solid #ecf0f1;
            font-size: 0.95rem;
            outline: none;
        }

        .proyecto {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
            border-left: 5px solid;
            width: 100%;
            max-width: 350px;
        }

        .proyecto:hover {
            transform: translateY(-3px);
        }

        .proyecto.Activo {
            border-color: #27ae60;
        }

        .proyecto.Enpausa {
            border-color: #f1c40f;
        }

        .proyecto.Finalizado {
            border-color: #e74c3c;
        }

        .proyecto h3 {
            margin: 0 0 8px;
            font-size: 1.2rem;
            color: #2c3e50;
            display: flex;
            align-items: center;
        }

        .proyecto h3 i {
            margin-right: 6px;
        }

        .proyecto p {
            margin: 6px 0;
            font-size: 0.9rem;
            color: #7f8c8d;
        }

        .proyecto .status {
            font-weight: bold;
            color: #2c3e50;
        }

        .estado-form {
            margin-top: 10px;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .estado-form select {
            padding: 6px;
            border: 2px solid #ecf0f1;
            border-radius: 6px;
            font-size: 13px;
            flex-grow: 1;
        }

        .estado-form button {
            padding: 6px 12px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .estado-form button:hover {
            background: #2980b9;
        }

        .acciones {
            margin-top: 10px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .acciones a,
        .acciones button {
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            transition: background 0.3s, color 0.3s;
        }

        .acciones a {
            color: #3498db;
            border: 2px solid #3498db;
            background: none;
        }

        .acciones a:hover {
            background: #3498db;
            color: white;
        }

        .acciones button.delete {
            border: 2px solid #e74c3c;
            color: #e74c3c;
            background: none;
        }

        .acciones button.delete:hover {
            background: #e74c3c;
            color: white;
        }

        .no-proyectos {
            text-align: center;
            color: #7f8c8d;
            font-size: 1.1rem;
            margin-top: 20px;
            padding: 15px;
            background: #f9fbfd;
            border-radius: 8px;
            align-self: center;
        }

        @media (max-width: 800px) {
            .content-wrapper {
                flex-direction: column;
            }

            .calendar-panel,
            .proyectos-lista,
            .chatbot-panel {
                max-width: 100%;
                flex: 1 1 100%;
            }
        }

        @media (max-width: 600px) {
            .header h1 {
                font-size: 1.5rem;
            }

            .search-filter {
                flex-direction: column;
            }

            .search-filter input,
            .search-filter select,
            .search-filter button {
                width: 100%;
            }
        }

        .chatbot-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            font-size: 30px;
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
            will-change: transform, box-shadow;
        }

        .chatbot-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .chatbot-btn:active {
            transform: scale(0.9);
        }

        .chatbot-btn i {
            animation: bounce 1.5s infinite;
            font-size: 36px;
            transform-origin: center;
        }

        .chatbot-btn .reminder-icon {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            animation: pulse 1.5s infinite;
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-5px);
            }
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
            }
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-5px);
            }
        }

        #calendar {
            max-height: calc(100vh - 250px);
        }

        .config-form,
        .task-form {
            margin-top: 20px;
            padding: 15px;
            background: #f9fbfd;
            border-radius: 10px;
            border: 1px solid #ecf0f1;
        }

        .config-form label,
        .task-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .config-form input,
        .task-form input,
        .task-form select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ecf0f1;
            border-radius: 6px;
        }

        .config-form button,
        .task-form button {
            padding: 8px 15px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .config-form button:hover,
        .task-form button:hover {
            background: #2980b9;
        }

        .chatbot-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: linear-gradient(45deg, #3498db, #2980b9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            font-size: 24px;
            transition: transform 0.3s;
            position: relative;
        }

        #robot-icon {
            animation: bounce 1.5s infinite;
            font-size: 20px;
            color: #4a90e2;
            /* Color del ícono */
            margin-right: 10px;
            animation: levitate 2s ease-in-out infinite;
            /* Animación de levitación */
        }

        /* Animación altura de levitación */
        @keyframes levitate {
            0% {
                transform: translateY(0);
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            }

            50% {
                transform: translateY(-5px);
                /* Altura reducida de 10px a 5px */
                box-shadow: 0 10px 20px rgba(0, 0, 0, 0.25);
                /* Sombra ligeramente más pequeña */
            }

            100% {
                transform: translateY(0);
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Hola, <?= htmlspecialchars($_SESSION['nombre']) ?> (<?= htmlspecialchars($rol) ?>)</h1>
            <a href="http://localhost/gestortasks/app/controllers/logic/logout.php" class="crear">Cerrar sesión</a>
        </div>

        <?php if ($rol === 'Scrum Master'): ?>
            <a class="crear" href="http://localhost:3000/app/controllers/negocio/crearproyecto.php">+ Crear nuevo proyecto</a>
        <?php endif; ?>
        </br>
        <div class="search-filter">
            <input type="text" id="search-input" placeholder="Buscar proyectos..." oninput="filterProjects()">
            <select id="status-filter" onchange="filterProjects()">
                <option value="all">Todos los estados</option>
                <option value="Activo">Activo</option>
                <option value="En pausa">En pausa</option>
                <option value="Finalizado">Finalizado</option>
            </select>
            <button onclick="filterProjects()">Filtrar</button>
        </div>

        <div class="content-wrapper">
            <div class="calendar-panel">
                <!-- Calendar container -->
                <div id="calendar"></div>
            </div>
            <div class="proyectos-lista">
                <?php if (empty($proyectos)): ?>
                    <!-- Message when no projects are available -->
                    <p class="no-proyectos">No hay proyectos disponibles. ¡Crea uno nuevo como Scrum Master!</p>
                <?php else: ?>
                    <?php foreach ($proyectos as $p): ?>
                        <!-- Project card with dynamic classes and data attributes -->
                        <div class="proyecto <?= htmlspecialchars($p['estado']) ?>" data-status="<?= htmlspecialchars($p['estado']) ?>" data-name="<?= htmlspecialchars(strtolower($p['nombre'])) ?>">
                            <h3><i class="fas fa-project-diagram"></i> <?= htmlspecialchars($p['nombre']) ?></h3>
                            <p><strong>Descripción:</strong> <?= htmlspecialchars(substr($p['descripcion'] ?: 'Sin descripción', 0, 100)) . (strlen($p['descripcion'] ?? '') > 100 ? '...' : '') ?></p>
                            <p><strong>Inicio:</strong> <?= htmlspecialchars($p['fecha_inicio']) ?></p>
                            <p><strong>Fin:</strong> <?= htmlspecialchars($p['fecha_fin'] ?: 'No definida') ?></p>
                            <p class="status"><strong>Estado:</strong> <?= htmlspecialchars($p['estado']) ?></p>
                            <p><strong>Tareas pendientes:</strong> <?= $p['pending_tasks'] ?></p>
                            <p><strong>Tareas totales:</strong> <?= $p['total_tasks'] ?></p>
                            <?php if ($rol === 'Scrum Master'): ?>
                                <!-- Form to change project status -->
                                <div class="estado-form">
                                    <form method="POST">
                                        <input type="hidden" name="proyecto_id" value="<?= $p['id'] ?>">
                                        <select name="estado" onchange="this.form.submit()">
                                            <option value="Activo" <?= $p['estado'] === 'Activo' ? 'selected' : '' ?>>Activo</option>
                                            <option value="En pausa" <?= $p['estado'] === 'En pausa' ? 'selected' : '' ?>>En pausa</option>
                                            <option value="Finalizado" <?= $p['estado'] === 'Finalizado' ? 'selected' : '' ?>>Finalizado</option>
                                        </select>
                                    </form>
                                </div>
                            <?php endif; ?>
                            <!-- Actions available for the project -->
                            <div class="acciones">
                                <a href="http://localhost:3000/app/controllers/logic/sprints.php?id=<?= $p['id'] ?>">Ver detalles</a>
                                <?php if ($rol === 'Scrum Master'): ?>
                                    <!-- Additional actions for Scrum Master -->
                                <?php elseif ($rol === 'Product Owner'): ?>
                                    <!-- Additional actions for Product Owner -->
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <!-- Chatbot Panel-->
            <div class="chatbot-panel" id="chatbot-panel">
                <div class="chatbot-messages" id="chatbot-messages">
                    <!-- Mensaje inicial del chatbot -->
                    <p><strong>Chatbot:</strong> ¡Hola! Soy tu asistente ScrumBot de GestorTasks. <i class="fas fa-robot" id="robot-icon"></i><br /> Escribe "Sprints" para generar la tabla con tus sprints pendientes.</p>
                    <?php if (count($reminders) > 0): ?>
                        <!-- Mostrar recordatorios si existen -->
                        <?php foreach ($reminders as $user_id => $user_reminders): ?>
                            <p><strong>Chatbot:</strong> Recordatorios para
                                <?= htmlspecialchars($team_members[array_search($user_id, array_column($team_members, 'id'))]['nombre'] ?? 'Desconocido') ?>:<br>
                                <?php foreach ($user_reminders as $r): ?>
                                    - <?= htmlspecialchars($r['nombre']) ?> (Estado: <?= htmlspecialchars($r['estado']) ?>, Inicio: <?= $r['fecha_inicio'] ?>, Fin: <?= $r['fecha_fin'] ?>)<br>
                                    - <?= htmlspecialchars($r['titulo']) ?> (Vence: <?= $r['fecha_estimada'] ?>, Prioridad: <?= $r['prioridad'] ?>, Nota: <?= htmlspecialchars($r['nota'] ?? 'Sin nota') ?>)<br>
                                <?php endforeach; ?>
                            </p>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <!-- Entrada de texto para el usuario -->
                <input type="text" id="chatbot-input" class="chatbot-input" placeholder="Escribe un mensaje..." onkeypress="handleChatbotInput(event)">
            </div>
        </div>

        <script>
            // Función para manejar la entrada de texto del usuario
            function handleChatbotInput(event) {
                if (event.key === 'Enter') {
                    const userInput = document.getElementById('chatbot-input').value;
                    const chatbotMessages = document.getElementById('chatbot-messages');

                    // Logica para manejar la entrada del usuario
                    if (userInput.trim().toLowerCase() === 'sprints') {
                        // Mostrar sprints pendientes
                        const sprints = document.createElement('p');
                        sprints.innerHTML = '<strong>Chatbot:</strong> Sprints pendientes:<br>';
                        const sprintsList = document.createElement('ul');
                        sprints.appendChild(sprintsList);

                        // Mostrar sprints pendientes
                        <?php foreach ($sprints as $sprint): ?>
                            const sprintItem = document.createElement('li');
                            sprintItem.innerHTML = '<?= htmlspecialchars($sprint['nombre']) ?> (Estado: <?= htmlspecialchars($sprint['estado']) ?>, Inicio: <?= $sprint['fecha_inicio'] ?>, Fin: <?= $sprint['fecha_fin'] ?>)';
                            sprintsList.appendChild(sprintItem);
                        <?php endforeach; ?>

                        chatbotMessages.appendChild(sprints);
                    } else {
                        // Mostrar mensaje de error
                        const errorMessage = document.createElement('p');
                        errorMessage.innerHTML = '<strong>Chatbot:</strong> Lo siento, no entiendo el comando. Intenta algo diferente.';
                        chatbotMessages.appendChild(errorMessage);
                    }

                    // Limpiar entrada de texto
                    document.getElementById('chatbot-input').value = '';
                }
            }
        </script>
    </div>

    <script>
        function toggleChatbot() {
            var chatbotPanel = document.getElementById('chatbot-panel');
            if (chatbotPanel.style.display === 'block') {
                chatbotPanel.style.opacity = 1;
                (function fade() {
                    if ((chatbotPanel.style.opacity -= 0.1) < 0) {
                        chatbotPanel.style.display = 'none';
                    } else {
                        requestAnimationFrame(fade);
                    }
                })();
            } else {
                chatbotPanel.style.display = 'block';
                (function fade() {
                    var val = parseFloat(chatbotPanel.style.opacity);
                    if (!((val += 0.1) > 1)) {
                        chatbotPanel.style.opacity = val;
                        requestAnimationFrame(fade);
                    }
                })();
            }
        }
    </script>

    <button class="chatbot-btn" onclick="toggleChatbot()">
        <i class="fas fa-robot"></i>
        <?php if (!empty($reminders)): ?>
            <span class="reminder-icon"><i class="fas fa-comment"></i><?= count($reminders) ?></span>
        <?php endif; ?>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if ('Notification' in window && 'serviceWorker' in navigator) {
                requestNotificationPermission();
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('Service Worker registrado:', reg))
                    .catch(error => console.error('Error al registrar Service Worker:', error));
            }

            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                events: [
                    <?php foreach ($tareas_pendientes as $tarea) {
                        $color = '#e74c3c'; // Rojo por defecto (Por hacer)
                        if ($tarea['estado'] === 'Hecha') $color = '#27ae60'; // Verde
                        elseif (in_array($tarea['estado'], ['En progreso', 'Bloqueada'])) $color = '#f1c40f'; // Amarillo
                        echo json_encode([
                            'title' => $tarea['titulo'],
                            'start' => $tarea['fecha_estimada'],
                            'color' => $color,
                            'extendedProps' => ['id' => $tarea['id'], 'estado' => $tarea['estado'], 'asignado_nombre' => $tarea['asignado_nombre'], 'prioridad' => $tarea['prioridad'], 'type' => 'tarea', 'nota' => $tarea['nota']]
                        ]) . ",";
                    } ?>
                    <?php foreach ($proyectos as $proyecto) {
                        if ($proyecto['fecha_inicio']) {
                            echo json_encode([
                                'title' => $proyecto['nombre'] . ' (Inicio)',
                                'start' => $proyecto['fecha_inicio'],
                                'color' => '#27ae60', // Verde para inicio
                                'extendedProps' => ['id' => $proyecto['id'], 'estado' => $proyecto['estado'], 'type' => 'inicio']
                            ]) . ",";
                        }
                        if ($proyecto['fecha_fin']) {
                            echo json_encode([
                                'title' => $proyecto['nombre'] . ' (Fin)',
                                'start' => $proyecto['fecha_fin'],
                                'color' => '#e74c3c', // Rojo para fin
                                'extendedProps' => ['id' => $proyecto['id'], 'estado' => $proyecto['estado'], 'type' => 'fin']
                            ]) . ",";
                            echo json_encode([
                                'title' => $proyecto['nombre'] . ' (Entrega)',
                                'start' => $proyecto['fecha_fin'],
                                'color' => '#3498db', // Azul para entrega
                                'extendedProps' => ['id' => $proyecto['id'], 'estado' => $proyecto['estado'], 'type' => 'entrega']
                            ]) . ",";
                        }
                    } ?>
                ].filter(event => event !== null),
                eventClick: function(info) {
                    const type = info.event.extendedProps.type;
                    if (type === 'tarea') {
                        alert('Tarea: ' + info.event.title + '\nEstado: ' + info.event.extendedProps.estado + '\nAsignado a: ' + info.event.extendedProps.asignado_nombre + '\nPrioridad: ' + info.event.extendedProps.prioridad + '\nNota: ' + (info.event.extendedProps.nota || 'Sin nota') + '\nID: ' + info.event.extendedProps.id);
                    } else if (type === 'inicio') {
                        alert('Proyecto: ' + info.event.title + '\nEstado: ' + info.event.extendedProps.estado + '\nID: ' + info.event.extendedProps.id + '\nFecha de inicio: ' + info.event.start.toLocaleDateString());
                    } else if (type === 'fin') {
                        alert('Proyecto: ' + info.event.title + '\nEstado: ' + info.event.extendedProps.estado + '\nID: ' + info.event.extendedProps.id + '\nFecha de fin: ' + info.event.start.toLocaleDateString());
                    } else if (type === 'entrega') {
                        alert('Proyecto: ' + info.event.title + '\nEstado: ' + info.event.extendedProps.estado + '\nID: ' + info.event.extendedProps.id + '\nFecha de entrega: ' + info.event.start.toLocaleDateString());
                    }
                }
            });
            calendar.render();
        });


        function filterProjects() {
            const search = document.getElementById('search-input').value.toLowerCase();
            const status = document.getElementById('status-filter').value;
            const projects = document.querySelectorAll('.proyecto');

            projects.forEach(project => {
                const name = project.getAttribute('data-name');
                const projectStatus = project.getAttribute('data-status');
                const matchesSearch = name.includes(search);
                const matchesStatus = status === 'all' || projectStatus === status;

                project.style.display = matchesSearch && matchesStatus ? 'block' : 'none';
            });
        }


        // Funcionalidad del chatbot asistente virtual mejorada

        // Funcionalidad del chatbot asistente virtual mejorada con soporte para tareas

        function handleChatbotInput(event) {
            if (event.keyCode === 13) {
                const userInput = document.getElementById('chatbot-input').value;

                // Agregar mensaje del usuario al chat
                addUserMessage(userInput);

                getChatbotResponse(userInput).then(response => {
                    addChatbotMessage(response);
                    document.getElementById('chatbot-input').value = '';
                });
            }
        }

        function addUserMessage(message) {
            const chatbotMessages = document.getElementById('chatbot-messages');
            const messageElement = document.createElement('p');
            messageElement.innerHTML = `<strong>Usuario:</strong> ${message}`;
            chatbotMessages.appendChild(messageElement);
            chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
        }

        function addChatbotMessage(message) {
            const chatbotMessages = document.getElementById('chatbot-messages');
            const messageElement = document.createElement('p');
            messageElement.innerHTML = `<strong>Chatbot:</strong> ${message}`;
            chatbotMessages.appendChild(messageElement);
            chatbotMessages.scrollTop = chatbotMessages.scrollHeight;

            // Agregar estilos CSS para el mensaje del chatbot
            messageElement.classList.add('chatbot-message');

            // Verificar si el mensaje contiene una tabla de sprints
            if (message.includes('<table') && message.includes('Nombre') && message.includes('Estado') && message.includes('Inicio') && message.includes('Fin')) {
                // Agregar estilos CSS para la tabla
                addTableStyles();

                // Programar la ocultación de la tabla y mostrar retroalimentación
                setTimeout(() => {
                    hideTableAndShowFeedback(messageElement, 'sprints');
                }, 10000); // Ocultar después de 5 segundos
            }
            // Verificar si el mensaje contiene una tabla de tareas
            else if (message.includes('<table') && message.includes('Título') && message.includes('Proyecto') && message.includes('Prioridad') && message.includes('Asignado') && message.includes('Nota') && message.includes('Estado') && message.includes('Inicio') && message.includes('Fin') && message.includes('Entrega') && message.includes('Duración') && message.includes('Avance') && message.includes('Observaciones') && message.includes('Observaciones') && message.includes('Comentarios')) {
                // Agregar estilos CSS para la tabla
                addTableStyles();

                // Programar la ocultación de la tabla y mostrar retroalimentación
                setTimeout(() => {
                    hideTableAndShowFeedback(messageElement, 'tareas');
                }, 10000); // Ocultar después de 5 segundos
            }

            document.getElementById('chatbot-input').focus();
        }

        function addTableStyles() {
            // Check if styles already exist
            if (!document.getElementById('chatbot-table-styles')) {
                const style = document.createElement('style');
                style.id = 'chatbot-table-styles';
                style.textContent = `
            .chatbot-messages table {
                border-collapse: collapse;
                width: 100%;
                margin: 10px 0;
                font-size: 14px;
                cursor: pointer;
            }
            .chatbot-messages table th {
                border: 1px solid #ddd;
                padding: 10px;
                text-align: left;
                color: #ffffff;
                background-color: #2c3e50;
                font-weight: bold;
            }
            .chatbot-messages table td {
                border: 1px solid #ddd;
                padding: 10px;
                text-align: left;
                background-color: #fafafa;
            }
            .chatbot-messages table tr:nth-child(even) td {
                background-color: #f1f1f1;
            }
            .modal {
                display: none;
                position: fixed;
                z-index: 1;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                overflow: auto;
                background-color: rgba(0,0,0,0.4);
            }
            .modal-content {
                background-color: #fefefe;
                margin: 15% auto;
                padding: 20px;
                border: 1px solid #888;
                width: 80%;
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
            .fade-in {
                opacity: 1;
                transition: opacity 1s ease-in;
            }
            .fade-out {
                opacity: 0;
                transition: opacity 1s ease-out;
            }
        `;
                document.head.appendChild(style);
            }
        }

        function showModal(event) {
            // Crear el modal
            const modal = document.createElement('div');
            modal.className = 'modal';
            document.body.appendChild(modal);

            // Crear el contenido del modal
            const modalContent = document.createElement('div');
            modalContent.className = 'modal-content';
            modal.appendChild(modalContent);

            // Agregar el título del modal
            const modalTitle = document.createElement('span');
            modalTitle.className = 'close';
            modalTitle.innerHTML = '&times;';
            modalContent.appendChild(modalTitle);

            // Agregar el contenido del modal
            const table = event.target.closest('tr').querySelector('table');
            modalContent.appendChild(table.cloneNode(true));

            // Agregar el evento para cerrar el modal
            modalTitle.addEventListener('click', () => {
                modal.remove();
            });

            // Mostrar el modal
            modal.style.display = 'block';
        }

        const tableRows = document.querySelectorAll('.expandable');
        tableRows.forEach(row => {
            row.addEventListener('click', showModal);
        });

        function hideTableAndShowFeedback(messageElement, type) {
            // Agregar clase de fade-out a la tabla
            const table = messageElement.querySelector('table');
            if (table) {
                table.classList.add('fade-out');

                // Después de la animación, ocultar completamente y mostrar retroalimentación
                setTimeout(() => {
                    table.style.display = 'none';
                    if (type === 'sprints') {
                        showSprintFeedback();
                    } else if (type === 'tareas') {
                        showTaskFeedback();
                    }
                }, 1000);
            }
        }

        function showSprintFeedback() {
            const feedbackMessages = [
                "He revisado tus sprints, Escribe 'Sprints' para ver el estado de tus sprints nuevamente.",
            ];

            const randomFeedback = feedbackMessages[Math.floor(Math.random() * feedbackMessages.length)];
            setTimeout(() => {
                addChatbotMessage("Escribe 'Sprints' para ver el estado de tus sprints nuevamente.");
            }, 3000);
        }

        function showTaskFeedback() {
            const feedbackMessages = [
                "He revisado tus tareas, Escribe 'Tareas' para ver tus tareas nuevamente o 'Sprints' para ver tus sprints.",
            ];

            const randomFeedback = feedbackMessages[Math.floor(Math.random() * feedbackMessages.length)];
            setTimeout(() => {
                addChatbotMessage("Escribe 'Tareas' para ver tus tareas nuevamente o 'Sprints' para ver tus sprints.");
            }, 3000);
        }

        function getChatbotResponse(userInput) {
            const input = userInput.toLowerCase();

            // Determinar qué endpoint usar basado en la entrada del usuario
            let endpoint = '/app/controllers/logic/sprints.php'; // Por defecto para sprints

            if (input === 'tareas') {
                endpoint = '/app/controllers/logic/tareas_chatbot.php';
            }

            return fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    user_input: userInput
                })
            }).then(response => response.text());
        }

        function checkReminders() {
            const today = new Date();
            const reminders = document.querySelectorAll('.reminder');
            reminders.forEach(reminder => {
                const reminderDate = new Date(reminder.getAttribute('data-reminder-date'));
                if (reminderDate.getDate() === today.getDate() &&
                    reminderDate.getMonth() === today.getMonth() &&
                    reminderDate.getFullYear() === today.getFullYear()) {
                    const reminderText = reminder.getAttribute('data-reminder-text');
                    addChatbotMessage(`Recordatorio: ${reminderText}`);
                }
            });
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }

        function toggleChatbot() {
            const chatbot = document.getElementById('chatbot-panel');
            chatbot.classList.toggle('active');
            if (chatbot.classList.contains('active')) {
                checkReminders();
                fetchAPIRecommendations();
            }
        }

        function requestNotificationPermission() {
            if (Notification.permission === 'default') {
                Notification.requestPermission().then(permission => {
                    if (permission === 'granted') {
                        console.log('Notificaciones permitidas');
                    }
                });
            }
        }

        // Función adicional para manejar diferentes tipos de consultas
        function handleAdvancedChatbotQueries(userInput) {
            const input = userInput.toLowerCase();

            if (input.includes('ayuda') || input.includes('help')) {
                return "Puedo ayudarte con: consultar sprints (escribe 'sprints'), consultar tareas (escribe 'tareas'), revisar proyectos. ¿Qué necesitas?";
            }

            if (input.includes('estado') && input.includes('sprint')) {
                return "Para ver el estado de tus sprints, escribe 'sprints' y te mostraré una tabla detallada.";
            }

            if (input.includes('estado') && input.includes('tarea')) {
                return "Para ver el estado de tus tareas, escribe 'tareas' y te mostraré una tabla detallada.";
            }

            if (input.includes('crear') && input.includes('sprint')) {
                return "Para crear un nuevo sprint, ve a la sección de gestión de sprints en el panel principal.";
            }

            if (input.includes('crear') && input.includes('tarea')) {
                return "Para crear una nueva tarea, ve a la sección de gestión de tareas en el panel principal.";
            }

            return null; // Retorna null si no hay respuesta específica
        }
    </script>
</body>

</html>