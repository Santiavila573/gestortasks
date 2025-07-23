<?php

require_once('C:\xampp\htdocs\gestortasks\app\config\db.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


class Tareas {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getTareasPorMiembro($usuario_id, $proyecto_id) {
        $stmt = $this->pdo->prepare("
            SELECT t.id, t.titulo, t.estado, t.prioridad, t.fecha_estimada, t.descripcion
            FROM tareas t
            JOIN proyectos p ON t.proyecto_id = p.id
            WHERE t.asignado_id = ? AND t.proyecto_id = ?
        ");
        $stmt->execute([$usuario_id, $proyecto_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function agregarTarea($data, $usuario_id) {
        $titulo = $data['titulo'] ?? '';
        $proyecto_id = $data['proyecto_id'] ?? 0;
        $fecha_estimada = $data['fecha_estimada'] ?? null;
        $descripcion = $data['descripcion'] ?? '';

        if ($fecha_estimada) {
            $stmt = $this->pdo->prepare("SELECT fecha_fin FROM proyectos WHERE id = ?");
            $stmt->execute([$proyecto_id]);
            $proyecto = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($proyecto && new DateTime($fecha_estimada) > new DateTime($proyecto['fecha_fin'])) {
                return false;
            }
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO tareas (titulo, asignado_id, proyecto_id, fecha_estimada, descripcion, prioridad, estado)
            VALUES (?, ?, ?, ?, ?, ?, 'Por hacer')
        ");
        return $stmt->execute([$titulo, $usuario_id, $proyecto_id, $fecha_estimada, $descripcion, 'Media']);
    }

    public function actualizarTarea($tarea_id, $data, $usuario_id) {
        $tarea = $this->obtenerTarea($tarea_id);
        if (!$tarea || $tarea['asignado_id'] !== $usuario_id) {
            return false;
        }

        $titulo = $data['titulo'] ?? $tarea['titulo'];
        $estado = $data['estado'] ?? $tarea['estado'];
        $prioridad = $data['prioridad'] ?? $tarea['prioridad'];
        $fecha_estimada = $data['fecha_estimada'] ?? $tarea['fecha_estimada'];
        $descripcion = $data['descripcion'] ?? $tarea['descripcion'];

        if (!in_array($estado, ['Por hacer', 'En progreso', 'Bloqueada', 'Hecha'])) {
            return false;
        }

        if ($fecha_estimada) {
            $stmt = $this->pdo->prepare("SELECT fecha_fin FROM proyectos WHERE id = ?");
            $stmt->execute([$tarea['proyecto_id']]);
            $proyecto = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($proyecto && new DateTime($fecha_estimada) > new DateTime($proyecto['fecha_fin'])) {
                return false;
            }
        }

        try {
            $stmt = $this->pdo->prepare("
                UPDATE tareas
                SET titulo = ?, estado = ?, prioridad = ?, fecha_estimada = ?, descripcion = ?
                WHERE id = ?
            ");
            $stmt->execute([$titulo, $estado, $prioridad, $fecha_estimada, $descripcion, $tarea_id]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function obtenerTarea($tarea_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM tareas WHERE id = ?");
        $stmt->execute([$tarea_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function sincronizarConCalendario($tarea_id) {
        $tarea = $this->obtenerTarea($tarea_id);
        if ($tarea) {
            $tarea['color'] = $this->getColorPorEstado($tarea['estado']);
            return $tarea;
        }
        return null;
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

    public function generarFormularioTarea($proyecto_id, $team_members, $tarea_id = null) {
        $tarea = $tarea_id ? $this->obtenerTarea($tarea_id) : null;
        $titulo = $tarea ? htmlspecialchars($tarea['titulo']) : '';
        $fecha_estimada = $tarea ? $tarea['fecha_estimada'] : '';
        $prioridad = $tarea ? $tarea['prioridad'] : 'Media';
        $descripcion = $tarea ? htmlspecialchars($tarea['descripcion']) : '';

        ob_start();
?>
        <div id="tarea-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2><?php echo $tarea_id ? 'Editar Tarea' : 'Nueva Tarea'; ?></h2>
                    <span class="close-modal" onclick="document.getElementById('tarea-modal').style.display='none'">&times;</span>
                </div>
                <form id="tarea-form" method="POST" action="">
                    <input type="hidden" name="proyecto_id" value="<?php echo $proyecto_id; ?>">
                    <?php if ($tarea_id): ?>
                        <input type="hidden" name="tarea_id" value="<?php echo $tarea_id; ?>">
                        <input type="hidden" name="edit_task" value="1">
                    <?php else: ?>
                        <input type="hidden" name="add_task" value="1">
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="titulo">Título:</label>
                        <input type="text" id="titulo" name="titulo" value="<?php echo $titulo; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="fecha_estimada">Fecha estimada:</label>
                        <input type="date" id="fecha_estimada" name="fecha_estimada" value="<?php echo $fecha_estimada; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="prioridad">Prioridad:</label>
                        <select id="prioridad" name="prioridad" required>
                            <option value="Baja" <?php echo $prioridad === 'Baja' ? 'selected' : ''; ?>>Baja</option>
                            <option value="Media" <?php echo $prioridad === 'Media' ? 'selected' : ''; ?>>Media</option>
                            <option value="Alta" <?php echo $prioridad === 'Alta' ? 'selected' : ''; ?>>Alta</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="asignado_id">Asignado a:</label>
                        <select id="asignado_id" name="asignado_id" required>
                            <?php foreach ($team_members as $member): ?>
                                <option value="<?php echo $member['id']; ?>" <?php echo ($tarea && $tarea['asignado_id'] == $member['id']) || (!$tarea && $member['id'] == $_SESSION['usuario_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($member['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="descripcion">Descripción:</label>
                        <textarea id="descripcion" name="descripcion"><?php echo $descripcion; ?></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="save-btn"><?php echo $tarea_id ? 'Guardar' : 'Crear'; ?></button>
                        <button type="button" class="cancel-btn" onclick="document.getElementById('tarea-modal').style.display='none'">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
        <style>
            .modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1000;
                justify-content: center;
                align-items: center;
            }

            .modal-content {
                background: white;
                padding: 20px;
                border-radius: 10px;
                width: 90%;
                max-width: 500px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
                animation: slideIn 0.3s ease-out;
            }

            @keyframes slideIn {
                from { transform: translateY(-50px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }

            .modal-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-bottom: 1px solid #eee;
                margin-bottom: 15px;
                padding-bottom: 10px;
            }

            .modal-header h2 {
                margin: 0;
                font-size: 1.5rem;
                color: #2c3e50;
            }

            .close-modal {
                font-size: 1.5rem;
                cursor: pointer;
                color: #7f8c8d;
                transition: color 0.3s;
            }

            .close-modal:hover {
                color: #e74c3c;
            }

            .form-group {
                margin-bottom: 15px;
            }

            .form-group label {
                display: block;
                margin-bottom: 5px;
                font-weight: 500;
                color: #34495e;
            }

            .form-group input,
            .form-group select,
            .form-group textarea {
                width: 100%;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 5px;
                font-size: 1rem;
                transition: border-color 0.3s;
            }

            .form-group input:focus,
            .form-group select:focus,
            .form-group textarea:focus {
                border-color: #3498db;
                outline: none;
            }

            .form-group textarea {
                height: 80px;
                resize: vertical;
            }

            .modal-footer {
                display: flex;
                justify-content: flex-end;
                gap: 10px;
                margin-top: 20px;
            }

            .save-btn, .cancel-btn {
                padding: 10px 20px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-size: 1rem;
                transition: transform 0.2s, background 0.2s;
            }

            .save-btn {
                background: #27ae60;
                color: white;
            }

            .save-btn:hover {
                transform: translateY(-2px);
                background: #219653;
            }

            .cancel-btn {
                background: #e74c3c;
                color: white;
            }

            .cancel-btn:hover {
                transform: translateY(-2px);
                background: #c0392b;
            }
        </style>
        <script>
            function openTareaModal(proyecto_id, tarea_id = null) {
                const modal = document.getElementById('tarea-modal');
                modal.style.display = 'flex';
                const form = document.getElementById('tarea-form');
                form.action = `?proyecto_id=${proyecto_id}${tarea_id ? '&tarea_id=' + tarea_id : ''}`;
            }
        </script>
<?php
        return ob_get_clean();
    }
}
?>