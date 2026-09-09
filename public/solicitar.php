<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../config/database.php';

$mensaje = '';
$error = '';

if ($_SESSION['rol'] !== 'docente' && $_SESSION['rol'] !== 'secretaria') {
    $error = 'Solo docentes y secretarias pueden solicitar salas.';
}

$salas = $pdo->query("SELECT * FROM salas WHERE estado = 'disponible' ORDER BY codigo_sala")->fetchAll();
$requerimientos = $pdo->query("SELECT * FROM requerimientos WHERE activo = 1 ORDER BY nombre")->fetchAll();
$tipos_actividad = $pdo->query("SELECT * FROM tipos_actividad WHERE activo = 1 ORDER BY nombre")->fetchAll();
$bloques = $pdo->query("SELECT id_bloque, numero_bloque FROM bloques_horarios ORDER BY numero_bloque")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_sala = $_POST['id_sala'] ?? '';
    $fecha_reserva = $_POST['fecha_reserva'] ?? '';
    $id_bloque_inicio = $_POST['id_bloque_inicio'] ?? '';
    $id_bloque_fin = $_POST['id_bloque_fin'] ?? '';
    $nombre_profesor = trim($_POST['nombre_profesor'] ?? '');
    $carrera_sigla = trim($_POST['carrera_sigla'] ?? '');
    $paralelo = trim($_POST['paralelo'] ?? '');
    $tipo_actividad = $_POST['tipo_actividad'] ?? '';
    $requerimientos_seleccionados = $_POST['requerimientos'] ?? [];

    if (empty($id_sala) || empty($fecha_reserva) || empty($id_bloque_inicio) || empty($id_bloque_fin) || empty($nombre_profesor)) {
        $error = 'Por favor, completa todos los campos obligatorios.';
    } elseif ($id_bloque_inicio > $id_bloque_fin) {
        $error = 'El bloque de inicio debe ser menor o igual al bloque de fin.';
    } else {
        try {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM solicitudes 
                WHERE id_sala = ? AND fecha_reserva = ? 
                AND ((id_bloque_inicio <= ? AND id_bloque_fin >= ?) 
                OR (id_bloque_inicio <= ? AND id_bloque_fin >= ?))
                AND estado != 'rechazada'
            ");
            $stmt->execute([$id_sala, $fecha_reserva, $id_bloque_inicio, $id_bloque_fin, $id_bloque_fin, $id_bloque_inicio]);
            $conflictos = $stmt->fetchColumn();

            if ($conflictos > 0) {
                $error = '❌ La sala no está disponible en el horario seleccionado.';
            } else {
                $motivo = implode(', ', $requerimientos_seleccionados);
                
                $stmt = $pdo->prepare("
                    INSERT INTO solicitudes (id_usuario_solicitante, id_sala, id_bloque_inicio, id_bloque_fin, fecha_reserva, motivo, nombre_profesor, carrera_sigla, paralelo, tipo_actividad) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_SESSION['usuario_id'], 
                    $id_sala, 
                    $id_bloque_inicio, 
                    $id_bloque_fin, 
                    $fecha_reserva, 
                    $motivo,
                    $nombre_profesor,
                    $carrera_sigla,
                    $paralelo,
                    $tipo_actividad
                ]);
                $mensaje = '✅ Solicitud creada exitosamente. Espera la aprobación del administrador.';
            }
        } catch (PDOException $e) {
            $error = 'Error al crear la solicitud: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Reserva - MAS</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 8px;
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .checkbox-group label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: normal;
            font-size: 13px;
            cursor: pointer;
        }
        .checkbox-group input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
        .form-group label .requerido {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo"><h1>MAS</h1><span>Modelo de Asignación de Salas</span></div>
            <ul>
                <li><a href="index.php">Inicio</a></li>
                <li><a href="programacion.php">Programación</a></li>
                <li><a href="solicitar.php" class="active">Solicitar</a></li>
                <li><a href="solicitudes.php">Mis Solicitudes</a></li>
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
            <h2>📝 Nueva Solicitud de Reserva</h2>
            
            <?php if ($mensaje): ?>
            <div class="alert success"><?php echo $mensaje; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="id_sala">Sala <span class="requerido">*</span></label>
                        <select id="id_sala" name="id_sala" required>
                            <option value="">Seleccione una sala</option>
                            <?php foreach ($salas as $sala): ?>
                            <option value="<?php echo $sala['id_sala']; ?>">
                                <?php echo htmlspecialchars($sala['codigo_sala'] . ' - ' . $sala['nombre_sala'] . ' (Cap. ' . $sala['capacidad'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_reserva">Fecha <span class="requerido">*</span></label>
                        <input type="date" id="fecha_reserva" name="fecha_reserva" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="id_bloque_inicio">Bloque Inicio <span class="requerido">*</span></label>
                        <select id="id_bloque_inicio" name="id_bloque_inicio" required>
                            <option value="">Seleccione</option>
                            <?php foreach ($bloques as $b): ?>
                            <option value="<?php echo $b['id_bloque']; ?>">Bloque <?php echo $b['numero_bloque']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="id_bloque_fin">Bloque Fin <span class="requerido">*</span></label>
                        <select id="id_bloque_fin" name="id_bloque_fin" required>
                            <option value="">Seleccione</option>
                            <?php foreach ($bloques as $b): ?>
                            <option value="<?php echo $b['id_bloque']; ?>">Bloque <?php echo $b['numero_bloque']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre_profesor">Nombre del Profesor <span class="requerido">*</span></label>
                        <input type="text" id="nombre_profesor" name="nombre_profesor" placeholder="Ej: Juan Pérez" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="carrera_sigla">Carrera/Sigla</label>
                        <input type="text" id="carrera_sigla" name="carrera_sigla" placeholder="Ej: ING-INFO">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="paralelo">Paralelo</label>
                        <input type="text" id="paralelo" name="paralelo" placeholder="Ej: 200, 201, A, V, etc.">
                    </div>
                    
                    <div class="form-group">
                        <label for="tipo_actividad">Tipo de Actividad</label>
                        <select id="tipo_actividad" name="tipo_actividad">
                            <option value="">Seleccione</option>
                            <?php foreach ($tipos_actividad as $ta): ?>
                            <option value="<?php echo htmlspecialchars($ta['nombre']); ?>">
                                <?php echo htmlspecialchars($ta['icono'] . ' ' . $ta['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Requerimientos</label>
                    <div class="checkbox-group">
                        <?php foreach ($requerimientos as $req): ?>
                        <label>
                            <input type="checkbox" name="requerimientos[]" value="<?php echo htmlspecialchars($req['nombre']); ?>">
                            <?php echo htmlspecialchars($req['icono'] . ' ' . $req['nombre']); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <small style="color:#666;display:block;margin-top:5px;">Selecciona los requerimientos necesarios para tu actividad.</small>
                </div>
                
                <button type="submit" class="btn btn-primary">Enviar Solicitud</button>
                <a href="index.php" class="btn btn-warning" style="margin-top:0.5rem;text-align:center;display:block;">Cancelar</a>
            </form>
        </div>
    </main>
    <footer><p>&copy; 2026 MAS - Proyecto de Título</p></footer>
</body>
</html>