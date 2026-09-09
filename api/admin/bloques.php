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
    $stmt = $pdo->prepare("SELECT * FROM bloques_horarios WHERE id_bloque = ?");
    $stmt->execute([$id]);
    $editar = $stmt->fetch();
}

// Agregar bloque
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'agregar') {
    $numero = intval($_POST['numero_bloque']);
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = $_POST['hora_fin'];
    $dia = $_POST['dia_semana'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO bloques_horarios (numero_bloque, hora_inicio, hora_fin, dia_semana) VALUES (?, ?, ?, ?)");
        $stmt->execute([$numero, $hora_inicio, $hora_fin, $dia]);
        $mensaje = '✅ Bloque agregado correctamente.';
    } catch (PDOException $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Actualizar bloque
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar') {
    $id = intval($_POST['id_bloque']);
    $numero = intval($_POST['numero_bloque']);
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = $_POST['hora_fin'];
    $dia = $_POST['dia_semana'];
    
    try {
        $stmt = $pdo->prepare("UPDATE bloques_horarios SET numero_bloque = ?, hora_inicio = ?, hora_fin = ?, dia_semana = ? WHERE id_bloque = ?");
        $stmt->execute([$numero, $hora_inicio, $hora_fin, $dia, $id]);
        $mensaje = '✅ Bloque actualizado correctamente.';
        $editar = null;
    } catch (PDOException $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Eliminar bloque
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    try {
        $stmt = $pdo->prepare("DELETE FROM bloques_horarios WHERE id_bloque = ?");
        $stmt->execute([$id]);
        $mensaje = '✅ Bloque eliminado correctamente.';
    } catch (PDOException $e) {
        $error = 'Error al eliminar: ' . $e->getMessage();
    }
}

// Obtener bloques
$bloques = $pdo->query("SELECT * FROM bloques_horarios ORDER BY dia_semana, numero_bloque")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Bloques - MAS</title>
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
                <li><a href="bloques.php" class="active">Bloques</a></li>
                <li><a href="requerimientos.php">Requerimientos</a></li>
                <li><a href="tipos_actividad.php">Actividades</a></li>
                <li><span>👤 <?php echo htmlspecialchars($_SESSION['nombre_completo']); ?></span></li>
                <li><a href="../logout.php">Cerrar sesión</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <h2>🕐 Gestión de Bloques Horarios</h2>
        
        <?php if ($mensaje): ?>
        <div class="alert success"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="form-container">
            <h3><?php echo $editar ? '✏️ Editar Bloque' : '📝 Agregar Bloque'; ?></h3>
            <form method="POST">
                <input type="hidden" name="accion" value="<?php echo $editar ? 'actualizar' : 'agregar'; ?>">
                <?php if ($editar): ?>
                <input type="hidden" name="id_bloque" value="<?php echo $editar['id_bloque']; ?>">
                <?php endif; ?>
                <div class="form-row">
                    <div class="form-group">
                        <label for="numero_bloque">Número de Bloque</label>
                        <input type="number" id="numero_bloque" name="numero_bloque" 
                               value="<?php echo $editar ? $editar['numero_bloque'] : ''; ?>" 
                               placeholder="Ej: 1" required>
                    </div>
                    <div class="form-group">
                        <label for="hora_inicio">Hora Inicio</label>
                        <input type="time" id="hora_inicio" name="hora_inicio" 
                               value="<?php echo $editar ? $editar['hora_inicio'] : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="hora_fin">Hora Fin</label>
                        <input type="time" id="hora_fin" name="hora_fin" 
                               value="<?php echo $editar ? $editar['hora_fin'] : ''; ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="dia_semana">Día</label>
                    <select id="dia_semana" name="dia_semana" required>
                        <option value="lunes" <?php echo $editar && $editar['dia_semana'] == 'lunes' ? 'selected' : ''; ?>>Lunes</option>
                        <option value="martes" <?php echo $editar && $editar['dia_semana'] == 'martes' ? 'selected' : ''; ?>>Martes</option>
                        <option value="miercoles" <?php echo $editar && $editar['dia_semana'] == 'miercoles' ? 'selected' : ''; ?>>Miércoles</option>
                        <option value="jueves" <?php echo $editar && $editar['dia_semana'] == 'jueves' ? 'selected' : ''; ?>>Jueves</option>
                        <option value="viernes" <?php echo $editar && $editar['dia_semana'] == 'viernes' ? 'selected' : ''; ?>>Viernes</option>
                        <option value="sabado" <?php echo $editar && $editar['dia_semana'] == 'sabado' ? 'selected' : ''; ?>>Sábado</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">
                    <?php echo $editar ? 'Actualizar Bloque' : 'Guardar Bloque'; ?>
                </button>
                <?php if ($editar): ?>
                <a href="bloques.php" class="btn btn-warning" style="margin-left:10px;">Cancelar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <h3>Listado de Bloques</h3>
            <table>
                <thead>
                    <tr>
                        <th>Bloque</th>
                        <th>Hora Inicio</th>
                        <th>Hora Fin</th>
                        <th>Día</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bloques as $bloque): ?>
                    <tr>
                        <td><?php echo $bloque['numero_bloque']; ?></td>
                        <td><?php echo $bloque['hora_inicio']; ?></td>
                        <td><?php echo $bloque['hora_fin']; ?></td>
                        <td><?php echo ucfirst($bloque['dia_semana']); ?></td>
                        <td>
                            <a href="bloques.php?editar=<?php echo $bloque['id_bloque']; ?>" class="btn btn-warning btn-sm">✏️ Editar</a>
                            <a href="bloques.php?eliminar=<?php echo $bloque['id_bloque']; ?>" 
                               class="btn btn-danger btn-sm" 
                               onclick="return confirm('¿Estás seguro de eliminar este bloque?')">
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