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
    $stmt = $pdo->prepare("SELECT * FROM tipos_actividad WHERE id_tipo = ?");
    $stmt->execute([$id]);
    $editar = $stmt->fetch();
}

// Agregar tipo de actividad
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'agregar') {
    $nombre = trim($_POST['nombre']);
    $icono = trim($_POST['icono']);
    
    if (empty($nombre)) {
        $error = 'El nombre es obligatorio.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO tipos_actividad (nombre, icono) VALUES (?, ?)");
            $stmt->execute([$nombre, $icono]);
            $mensaje = '✅ Tipo de actividad agregado correctamente.';
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Actualizar tipo de actividad
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar') {
    $id = intval($_POST['id_tipo']);
    $nombre = trim($_POST['nombre']);
    $icono = trim($_POST['icono']);
    
    if (empty($nombre)) {
        $error = 'El nombre es obligatorio.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE tipos_actividad SET nombre = ?, icono = ? WHERE id_tipo = ?");
            $stmt->execute([$nombre, $icono, $id]);
            $mensaje = '✅ Tipo de actividad actualizado correctamente.';
            $editar = null;
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Eliminar tipo de actividad
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    try {
        $stmt = $pdo->prepare("DELETE FROM tipos_actividad WHERE id_tipo = ?");
        $stmt->execute([$id]);
        $mensaje = '✅ Tipo de actividad eliminado correctamente.';
    } catch (PDOException $e) {
        $error = 'Error al eliminar: ' . $e->getMessage();
    }
}

// Obtener tipos de actividad
$tipos = $pdo->query("SELECT * FROM tipos_actividad ORDER BY nombre")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Tipos de Actividad - MAS</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo"><h1>MAS</h1><span>Administración</span></div>
            <ul>
                <li><a href="../index.php">Inicio</a></li>
                <li><a href="salas.php">Salas</a></li>
                <li><a href="usuarios.php">Usuarios</a></li>
                <li><a href="bloques.php">Bloques</a></li>
                <li><a href="requerimientos.php">Requerimientos</a></li>
                <li><a href="tipos_actividad.php" class="active">Actividades</a></li>
                <li><span>👤 <?php echo htmlspecialchars($_SESSION['nombre_completo']); ?></span></li>
                <li><a href="../logout.php">Cerrar sesión</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <h2>📋 Gestión de Tipos de Actividad</h2>
        
        <?php if ($mensaje): ?>
        <div class="alert success"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="form-container">
            <h3><?php echo $editar ? '✏️ Editar Tipo de Actividad' : '📝 Agregar Tipo de Actividad'; ?></h3>
            <form method="POST">
                <input type="hidden" name="accion" value="<?php echo $editar ? 'actualizar' : 'agregar'; ?>">
                <?php if ($editar): ?>
                <input type="hidden" name="id_tipo" value="<?php echo $editar['id_tipo']; ?>">
                <?php endif; ?>
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre">Nombre</label>
                        <input type="text" id="nombre" name="nombre" 
                               value="<?php echo $editar ? htmlspecialchars($editar['nombre']) : ''; ?>" 
                               placeholder="Ej: Cátedra" required>
                    </div>
                    <div class="form-group">
                        <label for="icono">Icono</label>
                        <input type="text" id="icono" name="icono" 
                               value="<?php echo $editar ? htmlspecialchars($editar['icono']) : '📌'; ?>" 
                               placeholder="Ej: 📚">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <?php echo $editar ? 'Actualizar' : 'Agregar'; ?>
                </button>
                <?php if ($editar): ?>
                <a href="tipos_actividad.php" class="btn btn-warning" style="margin-left:10px;">Cancelar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <h3>Listado de Tipos de Actividad</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Icono</th>
                        <th>Nombre</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tipos as $tipo): ?>
                    <tr>
                        <td><?php echo $tipo['id_tipo']; ?></td>
                        <td><?php echo htmlspecialchars($tipo['icono']); ?></td>
                        <td><?php echo htmlspecialchars($tipo['nombre']); ?></td>
                        <td>
                            <a href="tipos_actividad.php?editar=<?php echo $tipo['id_tipo']; ?>" class="btn btn-warning btn-sm">✏️ Editar</a>
                            <a href="tipos_actividad.php?eliminar=<?php echo $tipo['id_tipo']; ?>" 
                               class="btn btn-danger btn-sm" 
                               onclick="return confirm('¿Estás seguro de eliminar este tipo de actividad?')">
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