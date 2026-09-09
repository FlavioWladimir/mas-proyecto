<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ../login.php');
    exit;
}
require_once '../../config/database.php';

$mensaje = '';
$error = '';
$editar = null;

// Obtener datos para editar
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $id = intval($_GET['editar']);
    $stmt = $pdo->prepare("SELECT * FROM salas WHERE id_sala = ?");
    $stmt->execute([$id]);
    $editar = $stmt->fetch();
}

// Obtener tipos de sala
$tipos = $pdo->query("SELECT * FROM tipos_sala")->fetchAll();

// Agregar sala
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'agregar') {
    $codigo = trim($_POST['codigo_sala']);
    $nombre = trim($_POST['nombre_sala']);
    $capacidad = intval($_POST['capacidad']);
    $id_tipo = intval($_POST['id_tipo_sala']);
    $ubicacion = trim($_POST['ubicacion']);
    $estado = $_POST['estado'] ?? 'disponible';
    
    try {
        $stmt = $pdo->prepare("INSERT INTO salas (codigo_sala, nombre_sala, capacidad, id_tipo_sala, ubicacion, estado) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$codigo, $nombre, $capacidad, $id_tipo, $ubicacion, $estado]);
        $mensaje = '✅ Sala agregada correctamente.';
    } catch (PDOException $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Actualizar sala
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar') {
    $id = intval($_POST['id_sala']);
    $codigo = trim($_POST['codigo_sala']);
    $nombre = trim($_POST['nombre_sala']);
    $capacidad = intval($_POST['capacidad']);
    $id_tipo = intval($_POST['id_tipo_sala']);
    $ubicacion = trim($_POST['ubicacion']);
    $estado = $_POST['estado'] ?? 'disponible';
    
    try {
        $stmt = $pdo->prepare("UPDATE salas SET codigo_sala = ?, nombre_sala = ?, capacidad = ?, id_tipo_sala = ?, ubicacion = ?, estado = ? WHERE id_sala = ?");
        $stmt->execute([$codigo, $nombre, $capacidad, $id_tipo, $ubicacion, $estado, $id]);
        $mensaje = '✅ Sala actualizada correctamente.';
        $editar = null;
    } catch (PDOException $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Eliminar sala
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    try {
        $stmt = $pdo->prepare("DELETE FROM salas WHERE id_sala = ?");
        $stmt->execute([$id]);
        $mensaje = '✅ Sala eliminada correctamente.';
    } catch (PDOException $e) {
        $error = 'Error al eliminar: ' . $e->getMessage();
    }
}

// Cambiar estado de sala (bloquear/desbloquear)
if (isset($_GET['cambiar_estado']) && is_numeric($_GET['cambiar_estado'])) {
    $id = intval($_GET['cambiar_estado']);
    try {
        $stmt = $pdo->prepare("UPDATE salas SET estado = IF(estado = 'disponible', 'mantencion', 'disponible') WHERE id_sala = ?");
        $stmt->execute([$id]);
        $mensaje = '✅ Estado de sala actualizado.';
    } catch (PDOException $e) {
        $error = 'Error al cambiar estado: ' . $e->getMessage();
    }
}

// Obtener salas
$salas = $pdo->query("SELECT s.*, t.nombre_tipo FROM salas s LEFT JOIN tipos_sala t ON s.id_tipo_sala = t.id_tipo_sala ORDER BY s.codigo_sala")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Salas - MAS</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo"><h1>MAS</h1><span>Administración</span></div>
            <ul>
                <li><a href="../index.php">Inicio</a></li>
                <li><a href="salas.php" class="active">Salas</a></li>
                <li><a href="usuarios.php">Usuarios</a></li>
                <li><a href="bloques.php">Bloques</a></li>
                <li><a href="requerimientos.php">Requerimientos</a></li>
                <li><a href="tipos_actividad.php">Actividades</a></li>
                <li><span>👤 <?php echo htmlspecialchars($_SESSION['nombre_completo']); ?></span></li>
                <li><a href="../logout.php">Cerrar sesión</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <h2>🏫 Gestión de Salas</h2>
        
        <?php if ($mensaje): ?>
        <div class="alert success"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="form-container">
            <h3><?php echo $editar ? '✏️ Editar Sala' : '📝 Agregar Sala'; ?></h3>
            <form method="POST">
                <input type="hidden" name="accion" value="<?php echo $editar ? 'actualizar' : 'agregar'; ?>">
                <?php if ($editar): ?>
                <input type="hidden" name="id_sala" value="<?php echo $editar['id_sala']; ?>">
                <?php endif; ?>
                <div class="form-row">
                    <div class="form-group">
                        <label for="codigo_sala">Código</label>
                        <input type="text" id="codigo_sala" name="codigo_sala" 
                               value="<?php echo $editar ? htmlspecialchars($editar['codigo_sala']) : ''; ?>" 
                               placeholder="Ej: A101" required>
                    </div>
                    <div class="form-group">
                        <label for="nombre_sala">Nombre</label>
                        <input type="text" id="nombre_sala" name="nombre_sala" 
                               value="<?php echo $editar ? htmlspecialchars($editar['nombre_sala']) : ''; ?>" 
                               placeholder="Ej: Aula 101" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="capacidad">Capacidad</label>
                        <input type="number" id="capacidad" name="capacidad" 
                               value="<?php echo $editar ? $editar['capacidad'] : ''; ?>" 
                               placeholder="Ej: 40" required>
                    </div>
                    <div class="form-group">
                        <label for="id_tipo_sala">Tipo</label>
                        <select id="id_tipo_sala" name="id_tipo_sala" required>
                            <option value="">Seleccione un tipo</option>
                            <?php foreach ($tipos as $tipo): ?>
                            <option value="<?php echo $tipo['id_tipo_sala']; ?>" 
                                <?php echo $editar && $editar['id_tipo_sala'] == $tipo['id_tipo_sala'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tipo['nombre_tipo']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="ubicacion">Ubicación</label>
                        <input type="text" id="ubicacion" name="ubicacion" 
                               value="<?php echo $editar ? htmlspecialchars($editar['ubicacion']) : ''; ?>" 
                               placeholder="Ej: Edificio A, Piso 1">
                    </div>
                    <div class="form-group">
                        <label for="estado">Estado</label>
                        <select id="estado" name="estado" required>
                            <option value="disponible" <?php echo $editar && $editar['estado'] == 'disponible' ? 'selected' : ''; ?>>🟢 Disponible</option>
                            <option value="mantencion" <?php echo $editar && $editar['estado'] == 'mantencion' ? 'selected' : ''; ?>>🔴 Mantención</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <?php echo $editar ? 'Actualizar Sala' : 'Guardar Sala'; ?>
                </button>
                <?php if ($editar): ?>
                <a href="salas.php" class="btn btn-warning" style="margin-left:10px;">Cancelar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <h3>Listado de Salas</h3>
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Capacidad</th>
                        <th>Tipo</th>
                        <th>Ubicación</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($salas as $sala): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($sala['codigo_sala']); ?></td>
                        <td><?php echo htmlspecialchars($sala['nombre_sala']); ?></td>
                        <td><?php echo $sala['capacidad']; ?></td>
                        <td><?php echo htmlspecialchars($sala['nombre_tipo'] ?? 'Sin tipo'); ?></td>
                        <td><?php echo htmlspecialchars($sala['ubicacion']); ?></td>
                        <td><?php echo $sala['estado'] === 'disponible' ? '🟢 Disponible' : '🔴 Mantención'; ?></td>
                        <td>
                            <a href="salas.php?editar=<?php echo $sala['id_sala']; ?>" class="btn btn-warning btn-sm">✏️ Editar</a>
                            <a href="salas.php?cambiar_estado=<?php echo $sala['id_sala']; ?>" 
                               class="btn btn-info btn-sm"
                               onclick="return confirm('¿Cambiar estado de la sala?')">
                               <?php echo $sala['estado'] === 'disponible' ? '🔒 Bloquear' : '🔓 Desbloquear'; ?>
                            </a>
                            <a href="salas.php?eliminar=<?php echo $sala['id_sala']; ?>" 
                               class="btn btn-danger btn-sm" 
                               onclick="return confirm('¿Estás seguro de eliminar esta sala?')">
                               🗑 Eliminar
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
    <footer><p>&copy; 2026 MAS - Proyecto de Título</p></footer>
</body>
</html>