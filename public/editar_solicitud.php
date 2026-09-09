<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: login.php');
    exit;
}
require_once '../config/database.php';

$mensaje = '';
$error = '';
$solicitud = null;

// Obtener ID de solicitud
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: solicitudes.php');
    exit;
}

// Obtener datos de la solicitud
$stmt = $pdo->prepare("
    SELECT s.*, sal.codigo_sala, sal.nombre_sala 
    FROM solicitudes s
    JOIN salas sal ON s.id_sala = sal.id_sala
    WHERE s.id_solicitud = ? AND s.estado = 'pendiente'
");
$stmt->execute([$id]);
$solicitud = $stmt->fetch();

if (!$solicitud) {
    header('Location: solicitudes.php');
    exit;
}

// Obtener salas disponibles
$salas = $pdo->query("SELECT * FROM salas WHERE estado = 'disponible' ORDER BY codigo_sala")->fetchAll();

// Procesar el cambio de sala
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nueva_sala = intval($_POST['id_sala'] ?? 0);
    $comentario = trim($_POST['comentario'] ?? '');
    
    if ($nueva_sala <= 0) {
        $error = 'Por favor, selecciona una sala.';
    } else {
        try {
            // Verificar disponibilidad de la nueva sala
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM solicitudes 
                WHERE id_sala = ? AND fecha_reserva = ? 
                AND ((id_bloque_inicio <= ? AND id_bloque_fin >= ?) 
                OR (id_bloque_inicio <= ? AND id_bloque_fin >= ?))
                AND estado != 'rechazada'
                AND id_solicitud != ?
            ");
            $stmt->execute([
                $nueva_sala, 
                $solicitud['fecha_reserva'],
                $solicitud['id_bloque_inicio'],
                $solicitud['id_bloque_fin'],
                $solicitud['id_bloque_fin'],
                $solicitud['id_bloque_inicio'],
                $id
            ]);
            $conflictos = $stmt->fetchColumn();

            if ($conflictos > 0) {
                $error = '❌ La sala seleccionada no está disponible en ese horario.';
            } else {
                // Obtener información de la nueva sala
                $stmt = $pdo->prepare("SELECT codigo_sala, nombre_sala FROM salas WHERE id_sala = ?");
                $stmt->execute([$nueva_sala]);
                $nueva_sala_info = $stmt->fetch();
                
                // Actualizar la solicitud con la nueva sala
                $stmt = $pdo->prepare("UPDATE solicitudes SET id_sala = ? WHERE id_solicitud = ?");
                $stmt->execute([$nueva_sala, $id]);
                
                // Guardar comentario (opcional)
                if (!empty($comentario)) {
                    $stmt = $pdo->prepare("UPDATE solicitudes SET comentarios = CONCAT(IFNULL(comentarios, ''), '\n[Admin] ', ?) WHERE id_solicitud = ?");
                    $stmt->execute([$comentario, $id]);
                }
                
                $mensaje = '✅ Sala reasignada exitosamente de ' . $solicitud['codigo_sala'] . ' a ' . $nueva_sala_info['codigo_sala'] . ' - ' . $nueva_sala_info['nombre_sala'];
            }
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Obtener la sala actual
$sala_actual = $pdo->query("SELECT codigo_sala, nombre_sala FROM salas WHERE id_sala = " . $solicitud['id_sala'])->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Solicitud - MAS</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo"><h1>MAS</h1><span>Modelo de Asignación de Salas</span></div>
            <ul>
                <li><a href="index.php">Inicio</a></li>
                <li><a href="programacion.php">Programación</a></li>
                <li><a href="solicitar.php">Solicitar</a></li>
                <li><a href="solicitudes.php" class="active">Solicitudes</a></li>
                <?php if ($_SESSION['rol'] === 'administrador'): ?>
                <li><a href="admin/salas.php">Administración</a></li>
                <?php endif; ?>
                <li><span>👤 <?php echo htmlspecialchars($_SESSION['nombre_completo']); ?></span></li>
                <li><a href="logout.php">Cerrar sesión</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <div class="form-container">
            <h2>✏️ Editar Solicitud #<?php echo $id; ?></h2>
            
            <?php if ($mensaje): ?>
            <div class="alert success"><?php echo $mensaje; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Detalles de la solicitud -->
            <div style="background:#f8f9fa;padding:15px;border-radius:8px;margin-bottom:20px;">
                <h3>📋 Detalles de la Solicitud</h3>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <div><strong>Sala actual:</strong> <?php echo htmlspecialchars($sala_actual['codigo_sala'] . ' - ' . $sala_actual['nombre_sala']); ?></div>
                    <div><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($solicitud['fecha_reserva'])); ?></div>
                    <div><strong>Bloques:</strong> <?php echo $solicitud['id_bloque_inicio'] . ' → ' . $solicitud['id_bloque_fin']; ?></div>
                    <div><strong>Profesor:</strong> <?php echo htmlspecialchars($solicitud['nombre_profesor'] ?? '-'); ?></div>
                    <div><strong>Carrera:</strong> <?php echo htmlspecialchars($solicitud['carrera_sigla'] ?? '-'); ?></div>
                    <div><strong>Paralelo:</strong> <?php echo htmlspecialchars($solicitud['paralelo'] ?? '-'); ?></div>
                    <div style="grid-column:1/3;"><strong>Requerimientos:</strong> <?php echo htmlspecialchars($solicitud['motivo'] ?? '-'); ?></div>
                </div>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label for="id_sala">Seleccionar Nueva Sala *</label>
                    <select id="id_sala" name="id_sala" required>
                        <option value="">Seleccione una sala</option>
                        <?php foreach ($salas as $sala): ?>
                        <option value="<?php echo $sala['id_sala']; ?>" 
                            <?php echo $sala['id_sala'] == $solicitud['id_sala'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sala['codigo_sala'] . ' - ' . $sala['nombre_sala'] . ' (Cap. ' . $sala['capacidad'] . ')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="comentario">Comentario (opcional)</label>
                    <textarea id="comentario" name="comentario" placeholder="Motivo del cambio de sala..."></textarea>
                </div>

                <div style="display:flex;gap:10px;">
                    <button type="submit" class="btn btn-primary">✅ Guardar Cambios</button>
                    <a href="solicitudes.php" class="btn btn-warning">Cancelar</a>
                </div>
            </form>
        </div>
    </main>
    <footer><p>&copy; 2026 MAS - Proyecto de Título</p></footer>
</body>
</html>