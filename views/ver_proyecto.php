<?php
session_start();
require_once('C:\xampp\htdocs\gestortasks\app\config\db.php');

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: http://localhost:3000/app/views/login.php');
    exit;
}

// Verificar que se proporcione el ID del sprint
if (!isset($_GET['sprint_id'])) {
    header('Location: http://localhost:3000/app/controllers/logic/sprints.php');
    exit;
}

$sprint_id = $_GET['sprint_id'];

// Preparar la consulta para obtener las tareas del sprint
$stmt = $pdo->prepare("SELECT * FROM tareas WHERE sprint_id = :sprint_id");
$stmt->bindParam(':sprint_id', $sprint_id);
$stmt->execute();
$tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener detalles del sprint
$stmt = $pdo->prepare("SELECT nombre, fecha_inicio, fecha_fin FROM sprints WHERE id = :sprint_id LIMIT 1");
$stmt->bindParam(':sprint_id', $sprint_id);
$stmt->execute();
$sprint = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sprint) {
    header('Location: http://localhost:3000/app/controllers/logic/sprints.php');
    exit;
}

// Procesar cambio de estado de tareas y calcular métricas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tarea_id']) && isset($_POST['estado']) && $_SESSION['rol'] === 'Scrum Master') {
    $tarea_id = $_POST['tarea_id'];
    $estado = $_POST['estado'];
    if (in_array($estado, ['Por hacer', 'En progreso', 'Bloqueada', 'Hecha'])) {
        $stmt = $pdo->prepare("UPDATE tareas SET estado = ? WHERE id = ?");
        $stmt->execute([$estado, $tarea_id]);

        // Calcular y guardar métricas (simulación básica)
        $tareas_completadas = $pdo->query("SELECT COUNT(*) FROM tareas WHERE sprint_id = $sprint_id AND estado = 'Hecha'")->fetchColumn();
        $total_tareas = $pdo->query("SELECT COUNT(*) FROM tareas WHERE sprint_id = $sprint_id")->fetchColumn();
        $velocidad_equipo = $tareas_completadas; // Simulación; idealmente usa puntos
        $cumplimiento_objetivos = ($tareas_completadas == $total_tareas) ? 1 : 0; // Simulación
        $tiempo_promedio_tarea = 0; // Requiere campo de tiempo en tareas

        // Verificar si ya existe una métrica para este sprint
        $stmt = $pdo->prepare("SELECT id FROM metricas WHERE sprint_id = ?");
        $stmt->execute([$sprint_id]);
        $metrica_existente = $stmt->fetch();

        if ($metrica_existente) {
            $stmt = $pdo->prepare("UPDATE metricas SET velocidad_equipo = ?, cumplimiento_objetivos = ?, tiempo_promedio_tarea = ?, fecha_registro = NOW() WHERE sprint_id = ?");
            $stmt->execute([$velocidad_equipo, $cumplimiento_objetivos, $tiempo_promedio_tarea, $sprint_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO metricas (sprint_id, velocidad_equipo, cumplimiento_objetivos, tiempo_promedio_tarea, fecha_registro) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$sprint_id, $velocidad_equipo, $cumplimiento_objetivos, $tiempo_promedio_tarea]);
        }

        header('Location: ver_tareas_sprint.php?sprint_id=' . $sprint_id);
        exit;
    }
}

// Procesar creación de tarea
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_tarea'])) {
    $titulo = $_POST['titulo'] ?? 'Nueva tarea ' . time();
    $estado = $_POST['estado'] ?? 'Por hacer';
    $stmt = $pdo->prepare("INSERT INTO tareas (titulo, estado, sprint_id) VALUES (?, ?, ?)");
    $stmt->execute([$titulo, $estado, $sprint_id]);
    header('Location: ver_tareas_sprint.php?sprint_id=' . $sprint_id);
    exit;
}

// Contar tareas por estado para métricas
$estados = ['Por hacer', 'En progreso', 'Bloqueada', 'Hecha'];
$conteo_estados = array_fill_keys($estados, 0);
foreach ($tareas as $tarea) {
    if (isset($conteo_estados[$tarea['estado']])) {
        $conteo_estados[$tarea['estado']]++;
    }
}

// Obtener métricas actuales
$stmt = $pdo->prepare("SELECT velocidad_equipo, cumplimiento_objetivos, tiempo_promedio_tarea, fecha_registro FROM metricas WHERE sprint_id = ?");
$stmt->execute([$sprint_id]);
$metricas = $stmt->fetch(PDO::FETCH_ASSOC);

// Manejar solicitud POST del chatbot
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
    $user_input = $input['user_input'] ?? '';

    if (strtolower($user_input) === 'sprints o tareas') {
        // Usar el sprint_id de la URL actual
        $tareas_stmt = $pdo->prepare("
            SELECT t.id, t.titulo, t.descripcion, t.prioridad, t.estado, t.fecha_estimada, 
                   u.nombre AS asignado_nombre
            FROM tareas t
            LEFT JOIN usuarios u ON t.asignado_id = u.id
            WHERE t.sprint_id = :sprint_id
            LIMIT 50
        ");
        $tareas_stmt->bindParam(':sprint_id', $sprint_id, PDO::PARAM_INT);
        $tareas_stmt->execute();
        $tareas = $tareas_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Obtener nombre del sprint
        $sprint_nombre = $sprint['nombre'] ?? 'Sprint Desconocido';

        // Generar tabla HTML
        ob_start();
        ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tarea</th>
                    <th>Descripción</th>
                    <th>Prioridad</th>
                    <th>Estado</th>
                    <th>Asignado</th>
                    <th>Fecha Estimada</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tareas)): ?>
                    <tr><td colspan="7">No hay tareas para el sprint: <?= htmlspecialchars($sprint_nombre) ?></td></tr>
                <?php else: ?>
                    <?php foreach ($tareas as $tarea): ?>
                        <tr>
                            <td><?= htmlspecialchars($tarea['id']) ?></td>
                            <td><?= htmlspecialchars($tarea['titulo']) ?></td>
                            <td><?= htmlspecialchars($tarea['descripcion'] ?: 'Sin descripción') ?></td>
                            <td><?= htmlspecialchars($tarea['prioridad']) ?></td>
                            <td><?= htmlspecialchars($tarea['estado']) ?></td>
                            <td><?= htmlspecialchars($tarea['asignado_nombre'] ?: 'No asignado') ?></td>
                            <td><?= htmlspecialchars($tarea['fecha_estimada'] ?: 'No definida') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
        $output = ob_get_clean();
        header('Content-Type: text/html; charset=utf-8');
        echo $output;
        exit;
    } else {
        echo "Por favor, escribe 'Sprints o Tareas' para ver las tareas.";
        exit;
    }
}

// Manejar solicitud GET para el chatbot (mantener compatibilidad)
if (isset($_GET['action']) && $_GET['action'] === 'get_chat_data') {
    // Validación de autenticación
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['token']) || $_SERVER['HTTP_AUTHORIZATION'] !== 'Bearer ' . $_SESSION['token']) {
        header('Location: http://localhost:3000/app/views/login.php');
        exit;
    }

    $tareas_stmt = $pdo->prepare("
        SELECT t.id, t.titulo, t.descripcion, t.prioridad, t.estado, t.fecha_estimada, 
               u.nombre AS asignado_nombre
        FROM tareas t
        LEFT JOIN usuarios u ON t.asignado_id = u.id
        WHERE t.sprint_id = :sprint_id OR :sprint_id IS NULL
        LIMIT 50
    ");
    $tareas_stmt->bindParam(':sprint_id', $sprint_id, PDO::PARAM_INT);
    $tareas_stmt->execute();
    $tareas = $tareas_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener detalles del sprint para el título
    $sprint_nombre = 'Todas las Tareas';
    if ($sprint_id) {
        $sprint_stmt = $pdo->prepare("SELECT nombre FROM sprints WHERE id = :sprint_id LIMIT 1");
        $sprint_stmt->bindParam(':sprint_id', $sprint_id);
        $sprint_stmt->execute();
        $sprint_data = $sprint_stmt->fetch(PDO::FETCH_ASSOC);
        if ($sprint_data) {
            $sprint_nombre = $sprint_data['nombre'];
        }
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tareas del Sprint - GestorTasks</title>
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');
    body {
        font-family: 'Roboto', sans-serif;
        background: linear-gradient(135deg, #f0f4f8, #e0e7f0);
        margin: 0;
        padding: 20px;
        color: #2c3e50;
        line-height: 1.6;
    }
    .container {
        max-width: 1100px;
        margin: 0 auto;
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }
    .container:hover {
        transform: translateY(-2px);
    }
    h1 {
        font-size: 2.2rem;
        color: #34495e;
        margin-bottom: 25px;
        text-align: center;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        background: #f9fbfd;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    th, td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ecf0f1;
    }
    th {
        background: #34495e;
        color: white;
        font-weight: 500;
        text-transform: uppercase;
        font-size: 0.95rem;
    }
    tr:hover {
        background: #ecf0f1;
    }
    .estado.Porhacer { color: #e74c3c; }
    .estado.Enprogreso { color: #f1c40f; }
    .estado.Bloqueada { color: #e67e22; }
    .estado.Hecha { color: #27ae60; }
    .mensaje-vacio {
        text-align: center;
        color: #7f8c8d;
        margin-top: 20px;
        font-style: italic;
        font-size: 1.1rem;
    }
    .acciones {
        margin-top: 20px;
        text-align: center;
    }
    .acciones a {
        color: #3498db;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.3s ease;
    }
    .acciones a:hover {
        color: #2980b9;
    }
    @media (max-width: 600px) {
        .container {
            padding: 15px;
        }
        table {
            font-size: 0.9rem;
        }
        th, td {
            padding: 8px;
        }
    }
    </style>
</head>
<body>
    <div class="container">
        <h1>Tareas del Sprint: <?= htmlspecialchars($sprint_nombre) ?></h1>
        <?php if (empty($tareas)): ?>
            <div class="mensaje-vacio">No hay tareas para este sprint.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tarea</th>
                        <th>Descripción</th>
                        <th>Prioridad</th>
                        <th>Estado</th>
                        <th>Asignado</th>
                        <th>Fecha Estimada</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tareas as $terea): ?>
                        <tr>
                            <td><?= htmlspecialchars($tarea['id']) ?></td>
                            <td><?= htmlspecialchars($tarea['titulo']) ?></td>
                            <td><?= htmlspecialchars($tarea['descripcion'] ?: 'Sin descripción') ?></td>
                            <td><?= htmlspecialchars($tarea['prioridad']) ?></td>
                            <td class="estado <?= htmlspecialchars(str_replace(' ', '', $tarea['estado'])) ?>">
                                <?= htmlspecialchars($tarea['estado']) ?>
                            </td>
                            <td><?= htmlspecialchars($tarea['asignado_nombre'] ?: 'No asignado') ?></td>
                            <td><?= htmlspecialchars($tarea['fecha_estimada'] ?: 'No definida') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <div class="acciones">
            <a href="http://localhost:3000/app/views/sprints.php">Volver a Sprints</a>
        </div>
    </div>
</body>
</html>
<?php
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Tareas del Sprint - GestorTasks</title>
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');
    body {
        font-family: 'Roboto', sans-serif;
        background: linear-gradient(135deg, #f0f4f8, #e0e7f0);
        margin: 0;
        padding: 20px;
        color: #2c3e50;
        line-height: 1.6;
    }
    .container {
        max-width: 1100px;
        margin: 0 auto;
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }
    .container:hover {
        transform: translateY(-2px);
    }
    h1 {
        font-size: 2.2rem;
        color: #34495e;
        margin-bottom: 25px;
        text-align: center;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .sprint-header {
        padding: 20px;
        background: linear-gradient(90deg, #ecf0f1, #dfe6ea);
        border-radius: 10px;
        margin-bottom: 25px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    .sprint-header h2 {
        margin: 0;
        font-size: 1.7rem;
        color: #2c3e50;
        font-weight: 500;
    }
    .sprint-header p {
        margin: 8px 0;
        color: #7f8c8d;
        font-size: 0.95rem;
    }
    .metricas {
        margin-top: 25px;
        display: flex;
        gap: 25px;
        flex-wrap: wrap;
        justify-content: center;
    }
    .metrica {
        background: #ecf0f1;
        padding: 15px;
        border-radius: 10px;
        flex: 1;
        min-width: 180px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        transition: transform 0.3s ease;
    }
    .metrica:hover {
        transform: scale(1.02);
    }
    .tareas-tablero {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 25px;
        margin-top: 25px;
    }
    .columna {
        background: #f9fbfd;
        border-radius: 10px;
        padding: 20px;
        min-height: 250px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        transition: box-shadow 0.3s ease;
    }
    .columna:hover {
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    .columna h3 {
        font-size: 1.3rem;
        color: #34495e;
        margin-bottom: 15px;
        border-bottom: 2px solid #ecf0f1;
        padding-bottom: 8px;
        font-weight: 500;
    }
    .tarea {
        background: white;
        padding: 12px;
        margin-bottom: 15px;
        border-left: 5px solid;
        border-radius: 6px;
        transition: all 0.3s ease;
        box-shadow: 0 1px 5px rgba(0, 0, 0, 0.05);
    }
    .tarea:hover {
        transform: translateX(8px) scale(1.02);
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    }
    .tarea.Porhacer { border-color: #e74c3c; }
    .tarea.Enprogreso { border-color: #f1c40f; }
    .tarea.Bloqueada { border-color: #e67e22; }
    .tarea.Hecha { border-color: #27ae60; }
    .tarea p {
        margin: 0;
        color: #7f8c8d;
        font-size: 0.95rem;
    }
    .estado-form {
        margin-top: 8px;
    }
    .estado-form select {
        padding: 6px 10px;
        border: 1px solid #ecf0f1;
        border-radius: 6px;
        font-size: 0.9rem;
        background: #fff;
        transition: border-color 0.3s ease;
    }
    .estado-form select:focus {
        border-color: #3498db;
        outline: none;
    }
    .crear-tarea {
        margin-top: 25px;
        background: #f9fbfd;
        padding: 15px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    .crear-tarea form {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        align-items: center;
    }
    .crear-tarea input, .crear-tarea select, .crear-tarea button {
        padding: 10px;
        border: 1px solid #ecf0f1;
        border-radius: 6px;
        font-size: 0.95rem;
    }
    .crear-tarea input {
        flex: 1;
        min-width: 200px;
    }
    .crear-tarea button {
        background: #2980b9;
        color: white;
        border: none;
        cursor: pointer;
        transition: background 0.3s ease;
    }
    .crear-tarea button:hover {
        background: #3498db;
    }
    .mensaje-vacio {
        text-align: center;
        color: #7f8c8d;
        margin-top: 20px;
        font-style: italic;
    }
    .acciones {
        margin-top: 20px;
        text-align: center;
    }
    .acciones a {
        color: #3498db;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.3s ease;
    }
    .acciones a:hover {
        color: #2980b9;
    }
    @media (max-width: 600px) {
        .container {
            padding: 15px;
        }
        .tareas-tablero {
            grid-template-columns: 1fr;
        }
        .crear-tarea form {
            flex-direction: column;
        }
        .crear-tarea input, .crear-tarea select, .crear-tarea button {
            width: 100%;
        }
        .sprint-header h2 {
            font-size: 1.4rem;
        }
        .columna h3 {
            font-size: 1.1rem;
        }
    }
    </style>
</head>
<body>
    <div class="container">
        <h1>Ver Tareas del Sprint</h1>

        <div class="sprint-header">
            <h2><?= htmlspecialchars($sprint['nombre']) ?></h2>
            <p><strong>Fecha de inicio:</strong> <?= htmlspecialchars($sprint['fecha_inicio'] ?: 'No definida') ?></p>
            <p><strong>Fecha de finalización:</strong> <?= htmlspecialchars($sprint['fecha_fin'] ?: 'No definida') ?></p>
        </div>

        <div class="metricas">
            <?php foreach ($estados as $estado): ?>
                <div class="metrica">
                    <p><strong><?= htmlspecialchars($estado) ?>:</strong> <?= $conteo_estados[$estado] ?> tareas</p>
                </div>
            <?php endforeach; ?>
            <?php if ($metricas): ?>
                <div class="metrica">
                    <p><strong>Velocidad equipo:</strong> <?= $metricas['velocidad_equipo'] ?></p>
                </div>
                <div class="metrica">
                    <p><strong>Cumplimiento objetivos:</strong> <?= $metricas['cumplimiento_objetivos'] ? 'Sí' : 'No' ?></p>
                </div>
                <div class="metrica">
                    <p><strong>Tiempo promedio tarea:</strong> <?= $metricas['tiempo_promedio_tarea'] ?> días</p>
                </div>
                <div class="metrica">
                    <p><strong>Última actualización:</strong> <?= htmlspecialchars($metricas['fecha_registro']) ?></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="tareas-tablero">
            <?php $hay_tareas = false; ?>
            <?php foreach ($estados as $estado): ?>
                <div class="columna">
                    <h3><?= htmlspecialchars($estado) ?></h3>
                    <?php $tareas_en_estado = array_filter($tareas, fn($t) => $t['estado'] === $estado); ?>
                    <?php if (empty($tareas_en_estado)): ?>
                        <div class="mensaje-vacio">No hay tareas en esta columna.</div>
                    <?php else: ?>
                        <?php $hay_tareas = true; ?>
                        <?php foreach ($tareas_en_estado as $tarea): ?>
                            <div class="tarea <?= htmlspecialchars(str_replace(' ', '', $tarea['estado'])) ?>">
                                <p><?= htmlspecialchars($tarea['titulo']) ?></p>
                                <?php if ($_SESSION['rol'] === 'Scrum Master'): ?>
                                    <form method="POST" class="estado-form">
                                        <input type="hidden" name="tarea_id" value="<?= $tarea['id'] ?>">
                                        <select name="estado" onchange="this.form.submit()">
                                            <option value="Por hacer" <?= $tarea['estado'] === 'Por hacer' ? 'selected' : '' ?>>Por hacer</option>
                                            <option value="En progreso" <?= $tarea['estado'] === 'En progreso' ? 'selected' : '' ?>>En progreso</option>
                                            <option value="Bloqueada" <?= $tarea['estado'] === 'Bloqueada' ? 'selected' : '' ?>>Bloqueada</option>
                                            <option value="Hecha" <?= $tarea['estado'] === 'Hecha' ? 'selected' : '' ?>>Hecha</option>
                                        </select>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?php if (!$hay_tareas): ?>
                <div class="mensaje-vacio">No hay tareas en este sprint. ¡Crea una nueva!</div>
            <?php endif; ?>
        </div>

        <?php if ($_SESSION['rol'] === 'Scrum Master'): ?>
            <div class="crear-tarea">
                <form method="POST">
                    <input type="text" name="titulo" placeholder="Título de la tarea" required>
                    <select name="estado">
                        <option value="Por hacer">Por hacer</option>
                        <option value="En progreso">En progreso</option>
                        <option value="Bloqueada">Bloqueada</option>
                        <option value="Hecha">Hecha</option>
                    </select>
                    <button type="submit" name="crear_tarea">Crear Tarea</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="acciones">
            <a href="http://localhost:3000/app/controllers/logic/sprints.php">Volver a Sprints</a>
        </div>
    </div>
</body>
</html>