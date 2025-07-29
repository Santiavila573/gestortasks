<?php
/**
 * Clase para manejar operaciones relacionadas con el calendario de tareas
 */
class TaskConstants
{
    // Task States
    public const STATE_TODO = 'Por hacer';
    public const STATE_IN_PROGRESS = 'En progreso';
    public const STATE_BLOCKED = 'Bloqueada';
    public const STATE_DONE = 'Hecha';

    // Task Priorities
    public const PRIORITY_HIGH = 'Alta';
    public const PRIORITY_MEDIUM = 'Media';
    public const PRIORITY_LOW = 'Baja';

    // Colors for FullCalendar based on task state
    public const STATE_COLORS = [
        self::STATE_DONE => '#27ae60',        // Green
        self::STATE_IN_PROGRESS => '#f1c40f', // Yellow
        self::STATE_BLOCKED => '#e67e22',     // Orange
        self::STATE_TODO => '#e74c3c',        // Red
    ];

    public static function isValidState(string $state): bool
    {
        return in_array($state, [
            self::STATE_TODO,
            self::STATE_IN_PROGRESS,
            self::STATE_BLOCKED,
            self::STATE_DONE,
        ]);
    }

    public static function isValidPriority(string $priority): bool
    {
        return in_array($priority, [
            self::PRIORITY_HIGH,
            self::PRIORITY_MEDIUM,
            self::PRIORITY_LOW,
        ]);
    }
}





/**
 * Clase para manejar la conexión a la base de datos usando PDO.
 */
class Database
{
    private string $host;
    private string $db;
    private string $user;
    private string $pass;
    private string $charset;
    private PDO $pdo;

    public function __construct(string $host, string $db, string $user, string $pass, string $charset = 'utf8mb4')
    {
        $this->host = $host;
        $this->db = $db;
        $this->user = $user;
        $this->pass = $pass;
        $this->charset = $charset;

        $this->connect();
    }

    private function connect(): void
    {
        $dsn = "mysql:host={$this->host};dbname={$this->db};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // Deshabilitar emulación de prepares para mayor seguridad
        ];

        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            // En un entorno de producción, se debería loggear el error y mostrar un mensaje genérico.
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public function getConnection(): PDO
    {
        return $this->pdo;
    }
}





/**
 * Clase para manejar operaciones relacionadas con el calendario de tareas.
 */
class Calendario
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene las tareas de un proyecto para mostrar en el calendario.
     * @param int $proyectoId Identificador del proyecto.
     * @return array Arreglo con las tareas del proyecto.
     * @throws InvalidArgumentException Si el ID del proyecto no es válido.
     * @throws PDOException Si ocurre un error en la base de datos.
     */
    public function getTareasPorProyecto(int $proyectoId): array
    {
        if ($proyectoId <= 0) {
            throw new InvalidArgumentException("ID de proyecto no válido.");
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT id, titulo as title, fecha_estimada as start, estado, prioridad, descripcion
                FROM tareas
                WHERE proyecto_id = :proyecto_id
            ");
            $stmt->bindParam(":proyecto_id", $proyectoId, PDO::PARAM_INT);
            $stmt->execute();
            $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($tareas as &$tarea) {
                $tarea["color"] = TaskConstants::STATE_COLORS[$tarea["estado"]] ?? TaskConstants::STATE_COLORS[TaskConstants::STATE_TODO];
            }
            return $tareas;
        } catch (PDOException $e) {
            // Log the error: error_log("Database error in getTareasPorProyecto: " . $e->getMessage());
            throw new PDOException("Error al obtener tareas por proyecto: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Obtiene las tareas flotantes (sin fecha) de un proyecto.
     * @param int $proyectoId Identificador del proyecto.
     * @return array Arreglo con las tareas flotantes del proyecto.
     * @throws InvalidArgumentException Si el ID del proyecto no es válido.
     * @throws PDOException Si ocurre un error en la base de datos.
     */
    public function getTareasFlotantes(int $proyectoId): array
    {
        if ($proyectoId <= 0) {
            throw new InvalidArgumentException("ID de proyecto no válido.");
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT id, titulo as title, 'Sin fecha' as start, estado, prioridad, descripcion
                FROM tareas
                WHERE proyecto_id = :proyecto_id AND fecha_estimada IS NULL
            ");
            $stmt->bindParam(":proyecto_id", $proyectoId, PDO::PARAM_INT);
            $stmt->execute();
            $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($tareas as &$tarea) {
                $tarea["color"] = TaskConstants::STATE_COLORS[$tarea["estado"]] ?? TaskConstants::STATE_COLORS[TaskConstants::STATE_TODO];
            }
            return $tareas;
        } catch (PDOException $e) {
            // Log the error: error_log("Database error in getTareasFlotantes: " . $e->getMessage());
            throw new PDOException("Error al obtener tareas flotantes: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Renderiza el calendario con las tareas del proyecto.
     * @param int $proyectoId Identificador del proyecto.
     * @param string $viewType Tipo de vista (all, pendientes, completadas, flotantes).
     * @return string JSON con las tareas del proyecto.
     * @throws InvalidArgumentException Si el ID del proyecto o el tipo de vista no son válidos.
     * @throws PDOException Si ocurre un error en la base de datos.
     */
    public function renderCalendar(int $proyectoId, string $viewType = 'all'): string
    {
        if ($proyectoId <= 0) {
            throw new InvalidArgumentException("ID de proyecto no válido.");
        }

        $events = [];
        switch ($viewType) {
            case 'flotantes':
                $events = $this->getTareasFlotantes($proyectoId);
                break;
            case 'pendientes':
                $allTareas = $this->getTareasPorProyecto($proyectoId);
                $events = array_filter($allTareas, fn($t) => $t['estado'] !== TaskConstants::STATE_DONE);
                break;
            case 'completadas':
                $allTareas = $this->getTareasPorProyecto($proyectoId);
                $events = array_filter($allTareas, fn($t) => $t['estado'] === TaskConstants::STATE_DONE);
                break;
            case 'all':
                $events = $this->getTareasPorProyecto($proyectoId);
                break;
            default:
                throw new InvalidArgumentException("Tipo de vista no válido: {$viewType}");
        }
        return json_encode($events);
    }
}





/**
 * Clase para manejar operaciones de edición de tareas.
 */
class Editar
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene una tarea por su identificador.
     * @param int $tareaId Identificador de la tarea.
     * @return array|false Arreglo con la tarea o false si no se encuentra.
     * @throws InvalidArgumentException Si el ID de la tarea no es válido.
     * @throws PDOException Si ocurre un error en la base de datos.
     */
    public function obtenerTarea(int $tareaId): array|false
    {
        if ($tareaId <= 0) {
            throw new InvalidArgumentException("ID de tarea no válido.");
        }

        try {
            $stmt = $this->pdo->prepare("SELECT * FROM tareas WHERE id = :tarea_id");
            $stmt->bindParam(":tarea_id", $tareaId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Log the error: error_log("Database error in obtenerTarea: " . $e->getMessage());
            throw new PDOException("Error al obtener tarea: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Actualiza una tarea.
     * @param int $tareaId Identificador de la tarea.
     * @param array $data Datos de la tarea actualizados.
     * @param int $usuarioId Identificador del usuario que realiza la edición.
     * @return bool Verdadero si la edición fue exitosa, falso de lo contrario.
     * @throws InvalidArgumentException Si los datos de entrada no son válidos.
     * @throws PDOException Si ocurre un error en la base de datos.
     */
    public function actualizarTarea(int $tareaId, array $data, int $usuarioId): bool
    {
        if ($tareaId <= 0 || $usuarioId <= 0) {
            throw new InvalidArgumentException("ID de tarea o usuario no válido.");
        }

        $tarea = $this->obtenerTarea($tareaId);
        if (!$tarea) {
            return false; // Tarea no encontrada
        }

        // Permisos: Solo el creador o el asignado pueden editar
        if ($tarea["creador_id"] !== $usuarioId && $tarea["asignado_id"] !== $usuarioId) {
            return false; // Sin permisos
        }

        // Validar y sanear datos de entrada
        $titulo = filter_var($data["titulo"] ?? $tarea["titulo"], FILTER_SANITIZE_STRING);
        $descripcion = filter_var($data["descripcion"] ?? $tarea["descripcion"], FILTER_SANITIZE_STRING);
        $prioridad = $data["prioridad"] ?? $tarea["prioridad"];
        $estado = $data["estado"] ?? $tarea["estado"];
        $fecha_estimada = $data["fecha_estimada"] ?? $tarea["fecha_estimada"];

        // Validación de valores permitidos usando constantes
        if (!TaskConstants::isValidPriority($prioridad) || !TaskConstants::isValidState($estado)) {
            throw new InvalidArgumentException("Prioridad o estado no válidos.");
        }

        // Construir la consulta UPDATE dinámicamente
        $setClauses = [];
        $params = [];

        if (isset($data["titulo"])) {
            $setClauses[] = "titulo = :titulo";
            $params[":titulo"] = $titulo;
        }
        if (isset($data["descripcion"])) {
            $setClauses[] = "descripcion = :descripcion";
            $params[":descripcion"] = $descripcion;
        }
        if (isset($data["prioridad"])) {
            $setClauses[] = "prioridad = :prioridad";
            $params[":prioridad"] = $prioridad;
        }
        if (isset($data["estado"])) {
            $setClauses[] = "estado = :estado";
            $params[":estado"] = $estado;
        }
        if (isset($data["fecha_estimada"])) {
            $setClauses[] = "fecha_estimada = :fecha_estimada";
            $params[":fecha_estimada"] = $fecha_estimada;
        }

        if (empty($setClauses)) {
            return false; // No hay datos para actualizar
        }

        $query = "UPDATE tareas SET " . implode(", ", $setClauses) . " WHERE id = :tarea_id";
        $params[":tarea_id"] = $tareaId;

        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return true;
        } catch (PDOException $e) {
            // Log the error: error_log("Database error in actualizarTarea: " . $e->getMessage());
            throw new PDOException("Error al actualizar tarea: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Actualiza el estado de una tarea desde el calendario.
     * @param int $tareaId Identificador de la tarea.
     * @param string $nuevoEstado Nuevo estado de la tarea.
     * @param int $usuarioId Identificador del usuario que realiza la edición.
     * @return bool Verdadero si la edición fue exitosa, falso de lo contrario.
     * @throws InvalidArgumentException Si los datos de entrada no son válidos.
     * @throws PDOException Si ocurre un error en la base de datos.
     */
    public function actualizarEstadoDesdeCalendario(int $tareaId, string $nuevoEstado, int $usuarioId): bool
    {
        if ($tareaId <= 0 || $usuarioId <= 0) {
            throw new InvalidArgumentException("ID de tarea o usuario no válido.");
        }

        $tarea = $this->obtenerTarea($tareaId);
        if (!$tarea) {
            return false; // Tarea no encontrada
        }

        // Permisos: Solo el creador o el asignado pueden editar
        if ($tarea["creador_id"] !== $usuarioId && $tarea["asignado_id"] !== $usuarioId) {
            return false; // Sin permisos
        }

        // Validación de estado usando constantes
        if (!TaskConstants::isValidState($nuevoEstado)) {
            throw new InvalidArgumentException("Nuevo estado no válido.");
        }

        try {
            $stmt = $this->pdo->prepare("UPDATE tareas SET estado = :nuevo_estado WHERE id = :tarea_id");
            $stmt->bindParam(":nuevo_estado", $nuevoEstado, PDO::PARAM_STR);
            $stmt->bindParam(":tarea_id", $tareaId, PDO::PARAM_INT);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            // Log the error: error_log("Database error in actualizarEstadoDesdeCalendario: " . $e->getMessage());
            throw new PDOException("Error al actualizar estado desde calendario: " . $e->getMessage(), (int)$e->getCode());
        }
    }
}

?>
