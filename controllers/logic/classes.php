<?php
class Calendario {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getTareasPorProyecto($proyecto_id) {
        $stmt = $this->pdo->prepare("
            SELECT id, titulo as title, fecha_estimada as start, estado, prioridad, descripcion
            FROM tareas
            WHERE proyecto_id = ?
        ");
        $stmt->execute([$proyecto_id]);
        $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Añadir color según estado para FullCalendar
        foreach ($tareas as &$tarea) {
            $tarea['color'] = $this->getColorPorEstado($tarea['estado']);
        }
        return $tareas;
    }

    private function getColorPorEstado($estado) {
        switch ($estado) {
            case 'Hecha':
                return '#27ae60'; // Verde
            case 'En progreso':
                return '#f1c40f'; // Amarillo
            case 'Bloqueada':
                return '#e67e22'; // Naranja
            case 'Por hacer':
            default:
                return '#e74c3c'; // Rojo
        }
    }

    public function getTareasFlotantes($proyecto_id) {
        $stmt = $this->pdo->prepare("
            SELECT id, titulo as title, 'Sin fecha' as start, estado, prioridad, descripcion
            FROM tareas
            WHERE proyecto_id = ? AND fecha_estimada IS NULL
        ");
        $stmt->execute([$proyecto_id]);
        $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($tareas as &$tarea) {
            $tarea['color'] = $this->getColorPorEstado($tarea['estado']);
        }
        return $tareas;
    }

    public function renderCalendar($proyecto_id, $viewType = 'all') {
        $events = [];
        switch ($viewType) {
            case 'flotantes':
                $events = $this->getTareasFlotantes($proyecto_id);
                break;
            case 'pendientes':
                $events = array_filter($this->getTareasPorProyecto($proyecto_id), fn($t) => $t['estado'] !== 'Hecha');
                break;
            case 'completadas':
                $events = array_filter($this->getTareasPorProyecto($proyecto_id), fn($t) => $t['estado'] === 'Hecha');
                break;
            default:
                $events = $this->getTareasPorProyecto($proyecto_id);
                break;
        }
        return json_encode($events);
    }
}

class Editar {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function obtenerTarea($tarea_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM tareas WHERE id = ?");
        $stmt->execute([$tarea_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function actualizarTarea($tarea_id, $data, $usuario_id) {
        $tarea = $this->obtenerTarea($tarea_id);
        if (!$tarea || ($tarea['creador_id'] !== $usuario_id && $tarea['asignado_id'] !== $usuario_id)) {
            return false; // Sin permisos
        }

        $titulo = $data['titulo'] ?? $tarea['titulo'];
        $descripcion = $data['descripcion'] ?? $tarea['descripcion'];
        $prioridad = $data['prioridad'] ?? $tarea['prioridad'];
        $estado = $data['estado'] ?? $tarea['estado'];
        $fecha_estimada = $data['fecha_estimada'] ?? $tarea['fecha_estimada'];

        if (!in_array($prioridad, ['Alta', 'Media', 'Baja']) || !in_array($estado, ['Por hacer', 'En progreso', 'Bloqueada', 'Hecha'])) {
            return false; // Validación de datos
        }

        try {
            $stmt = $this->pdo->prepare("
                UPDATE tareas
                SET titulo = ?, descripcion = ?, prioridad = ?, estado = ?, fecha_estimada = ?
                WHERE id = ?
            ");
            $stmt->execute([$titulo, $descripcion, $prioridad, $estado, $fecha_estimada, $tarea_id]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Método para manejar edición desde el calendario
    public function actualizarEstadoDesdeCalendario($tarea_id, $nuevo_estado, $usuario_id) {
        $tarea = $this->obtenerTarea($tarea_id);
        if (!$tarea || ($tarea['creador_id'] !== $usuario_id && $tarea['asignado_id'] !== $usuario_id)) {
            return false; // Sin permisos
        }

        if (!in_array($nuevo_estado, ['Por hacer', 'En progreso', 'Bloqueada', 'Hecha'])) {
            return false; // Validación de estado
        }

        try {
            $stmt = $this->pdo->prepare("UPDATE tareas SET estado = ? WHERE id = ?");
            $stmt->execute([$nuevo_estado, $tarea_id]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>