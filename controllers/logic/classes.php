<?php
/**
 * Clase para manejar operaciones relacionadas con el calendario de tareas
 */
class Calendario {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene las tareas de un proyecto para mostrar en el calendario
     * @param int $proyecto_id Identificador del proyecto
     * @return array Arreglo con las tareas del proyecto
     */
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

    /**
     * Obtiene el color según el estado de la tarea
     * @param string $estado Estado de la tarea
     * @return string Color en formato HEX
     */
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

    /**
     * Obtiene las tareas flotantes (sin fecha) de un proyecto
     * @param int $proyecto_id Identificador del proyecto
     * @return array Arreglo con las tareas flotantes del proyecto
     */
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

    /**
     * Renderiza el calendario con las tareas del proyecto
     * @param int $proyecto_id Identificador del proyecto
     * @param string $viewType Tipo de vista (all, pendientes, completadas, flotantes)
     * @return string JSON con las tareas del proyecto
     */
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

/**
 * Clase para manejar operaciones de edición de tareas
 */
class Editar {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene una tarea por su identificador
     * @param int $tarea_id Identificador de la tarea
     * @return array Arreglo con la tarea
     */
    public function obtenerTarea($tarea_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM tareas WHERE id = ?");
        $stmt->execute([$tarea_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Actualiza una tarea
     * @param int $tarea_id Identificador de la tarea
     * @param array $data Datos de la tarea actualizados
     * @param int $usuario_id Identificador del usuario que realiza la edición
     * @return bool Verdadero si la edición fue exitosa, falso de lo contrario
     */
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

    /**
     * Actualiza el estado de una tarea desde el calendario
     * @param int $tarea_id Identificador de la tarea
     * @param string $nuevo_estado Nuevo estado de la tarea
     * @param int $usuario_id Identificador del usuario que realiza la edición
     * @return bool Verdadero si la edición fue exitosa, falso de lo contrario
     */
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
