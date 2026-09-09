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
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$id]);
    $editar = $stmt->fetch();
}

// Agregar usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'agregar') {
    $rut = trim($_POST['rut']);
    $nombre = trim($_POST['nombre_completo']);
    $email = trim($_POST['email']);
    $contrasena = $_POST['contrasena'] ?? '123456';
    $rol = $_POST['rol'];
    $estado = $_POST['estado'];
    
    if (empty($rut) || empty($nombre) || empty($email)) {
        $error = 'Por favor, completa todos los campos obligatorios.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'El correo electrónico ya está registrado.';
            } else {
                $hashed_password = password_hash($contrasena, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (rut, nombre_completo, email, contrasena, rol, estado) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$rut, $nombre, $email, $hashed_password, $rol, $estado]);
                $mensaje = '✅ Usuario agregado correctamente. Contraseña: ' . $contrasena;
            }
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Actualizar usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar') {
    $id = intval($_POST['id_usuario']);
    $rut = trim($_POST['rut']);
    $nombre = trim($_POST['nombre_completo']);
    $email = trim($_POST['email']);
    $rol = $_POST['rol'];
    $estado = $_POST['estado'];
    
    try {
        $stmt = $pdo->prepare("UPDATE usuarios SET rut = ?, nombre_completo = ?, email = ?, rol = ?, estado = ? WHERE id_usuario = ?");
        $stmt->execute([$rut, $nombre, $email, $rol, $estado, $id]);
        $mensaje = '✅ Usuario actualizado correctamente.';
        $editar = null;
    } catch (PDOException $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Eliminar usuario
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    if ($id == $_SESSION['usuario_id']) {
        $error = 'No puedes eliminar tu propia cuenta.';
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
            $stmt->execute([$id]);
            $mensaje = '✅ Usuario eliminado correctamente.';
        } catch (PDOException $e) {
            $error = 'Error al eliminar usuario: ' . $e->getMessage();
        }
    }
}

// Obtener usuarios
$usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY nombre_completo")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Usuarios - MAS</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo"><h1>MAS</h1><span>Administración</span></div>
            <ul>
                <li><a href="../index.php">Inicio</a></li>
                <li><a href="salas.php">Salas</a></li>
                <li><a href="usuarios.php" class="active">Usuarios</a></li>
                <li><a href="bloques.php">Bloques</a></li>
                <li><a href="requerimientos.php">Requerimientos</a></li>
                <li><a href="tipos_actividad.php">Actividades</a></li>
                <li><span>👤 <?php echo htmlspecialchars($_SESSION['nombre_completo']); ?></span></li>
                <li><a href="../logout.php">Cerrar sesión</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <h2>👤 Gestión de Usuarios</h2>
        
        <?php if ($mensaje): ?>
        <div class="alert success"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="form-container">
            <h3><?php echo $editar ? '✏️ Editar Usuario' : '📝 Agregar Usuario'; ?></h3>
            <form method="POST">
                <input type="hidden" name="accion" value="<?php echo $editar ? 'actualizar' : 'agregar'; ?>">
                <?php if ($editar): ?>
                <input type="hidden" name="id_usuario" value="<?php echo $editar['id_usuario']; ?>">
                <?php endif; ?>
                <div class="form-row">
                    <div class="form-group">
                        <label for="rut">RUT</label>
                        <input type="text" id="rut" name="rut" 
                               value="<?php echo $editar ? htmlspecialchars($editar['rut']) : ''; ?>" 
                               placeholder="11.111.111-1" required>
                    </div>
                    <div class="form-group">
                        <label for="nombre_completo">Nombre Completo</label>
                        <input type="text" id="nombre_completo" name="nombre_completo" 
                               value="<?php echo $editar ? htmlspecialchars($editar['nombre_completo']) : ''; ?>" 
                               placeholder="Juan Pérez" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo $editar ? htmlspecialchars($editar['email']) : ''; ?>" 
                               placeholder="correo@ejemplo.cl" required>
                    </div>
                    <div class="form-group">
                        <label for="contrasena">Contraseña</label>
                        <input type="text" id="contrasena" name="contrasena" 
                               placeholder="Dejar en blanco = 123456">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="rol">Rol</label>
                        <select id="rol" name="rol" required>
                            <option value="docente" <?php echo $editar && $editar['rol'] == 'docente' ? 'selected' : ''; ?>>📚 Docente</option>
                            <option value="secretaria" <?php echo $editar && $editar['rol'] == 'secretaria' ? 'selected' : ''; ?>>📋 Secretaria</option>
                            <option value="visualizador" <?php echo $editar && $editar['rol'] == 'visualizador' ? 'selected' : ''; ?>>👁️ Visualizador</option>
                            <option value="administrador" <?php echo $editar && $editar['rol'] == 'administrador' ? 'selected' : ''; ?>>👑 Administrador</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="estado">Estado</label>
                        <select id="estado" name="estado" required>
                            <option value="activo" <?php echo $editar && $editar['estado'] == 'activo' ? 'selected' : ''; ?>>🟢 Activo</option>
                            <option value="inactivo" <?php echo $editar && $editar['estado'] == 'inactivo' ? 'selected' : ''; ?>>🔴 Inactivo</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <?php echo $editar ? 'Actualizar Usuario' : 'Agregar Usuario'; ?>
                </button>
                <?php if ($editar): ?>
                <a href="usuarios.php" class="btn btn-warning" style="margin-left:10px;">Cancelar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <h3>Listado de Usuarios</h3>
            <table>
                <thead>
                    <tr>
                        <th>RUT</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($usuario['rut']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['nombre_completo']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                        <td>
                            <?php 
                            switch ($usuario['rol']) {
                                case 'administrador': echo '👑 Administrador'; break;
                                case 'docente': echo '📚 Docente'; break;
                                case 'secretaria': echo '📋 Secretaria'; break;
                                case 'visualizador': echo '👁️ Visualizador'; break;
                                default: echo $usuario['rol'];
                            }
                            ?>
                        </td>
                        <td><?php echo $usuario['estado'] === 'activo' ? '🟢 Activo' : '🔴 Inactivo'; ?></td>
                        <td>
                            <?php if ($usuario['id_usuario'] != $_SESSION['usuario_id']): ?>
                            <a href="usuarios.php?editar=<?php echo $usuario['id_usuario']; ?>" class="btn btn-warning btn-sm">✏️ Editar</a>
                            <a href="usuarios.php?eliminar=<?php echo $usuario['id_usuario']; ?>" 
                               class="btn btn-danger btn-sm" 
                               onclick="return confirm('¿Estás seguro de eliminar este usuario?')">
                               🗑 Eliminar
                            </a>
                            <?php else: ?>
                            <span style="color:#999;">(Tu cuenta)</span>
                            <?php endif; ?>
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