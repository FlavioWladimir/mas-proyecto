<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../config/database.php';

$userName = $_SESSION['nombre_completo'] ?? '';
$userRole = $_SESSION['rol'] ?? '';
$usuario_id = $_SESSION['usuario_id'];

$mensaje = $_GET['mensaje'] ?? '';
$error = $_GET['error'] ?? '';

if ($userRole === 'administrador') {
    $stmt = $pdo->prepare("
        SELECT s.*, sal.codigo_sala, sal.nombre_sala, u.nombre_completo as solicitante
        FROM solicitudes s
        JOIN salas sal ON s.id_sala = sal.id_sala
        JOIN usuarios u ON s.id_usuario_solicitante = u.id_usuario
        ORDER BY s.fecha_solicitud DESC
    ");
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("
        SELECT s.*, sal.codigo_sala, sal.nombre_sala
        FROM solicitudes s
        JOIN salas sal ON s.id_sala = sal.id_sala
        WHERE s.id_usuario_solicitante = ?
        ORDER BY s.fecha_solicitud DESC
    ");
    $stmt->execute([$usuario_id]);
}
$solicitudes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Solicitudes - MAS</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        table th { background: #1a3a5c; color: white; padding: 8px; text-align: left; }
        table td { padding: 8px; border-bottom: 1px solid #eee; }
        table tr:hover { background: #f8f9fa; }
        .btn-sm { padding: 4px 10px; font-size: 11px; border-radius: 4px; text-decoration: none; display: inline-block; margin: 2px; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-warning { background: #ffc107; color: #333; }
        .btn-warning:hover { background: #e0a800; }
        .estado-pendiente { color: #f39c12; font-weight: bold; }
        .estado-aprobada { color: #28a745; font-weight: bold; }
        .estado-rechazada { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo"><h1>MAS</h1><span>Modelo de Asignación de Salas</span></div>
            <ul>
                <li><a href="index.php">Inicio</a></li>
                <li><a href="programacion.php">Programación</a></li>
                <?php if ($userRole === 'docente' || $userRole === 'secretaria'): ?>
                <li><a href="solicitar.php">Solicitar</a></li>
                <li><a href="solicitudes.php" class="active">Mis Solicitudes</a></li>
                <?php endif; ?>
                <?php if ($userRole === 'administrador'): ?>
                <li><a href="solicitar.php">Solicitar</a></li>
                <li><a href="solicitudes.php" class="active">Solicitudes</a></li>
                <li><a href="admin/salas.php">Administración</a></li>
                <?php endif; ?>
                <li><span>👤 <?php echo htmlspecialchars($userName); ?></span></li>
                <li><a href="logout.php">Cerrar sesión</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <h2>📋 <?php echo $userRole === 'administrador' ? 'Todas las Solicitudes' : 'Mis Solicitudes'; ?></h2>
        <p>Consulta el estado de tus solicitudes de reserva.</p>

        <?php if ($mensaje): ?>
        <div class="alert success"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Sala</th>
                        <th>Fecha</th>
                        <th>Bloques</th>
                        <th>Profesor</th>
                        <th>Carrera</th>
                        <th>Paralelo</th>
                        <th>Actividad</th>
                        <th>Requerimientos</th>
                        <th>Estado</th>
                        <?php if ($userRole === 'administrador'): ?>
                        <th>Solicitante</th>
                        <th>Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($solicitudes) === 0): ?>
                    <tr>
                        <td colspan="<?php echo $userRole === 'administrador' ? '12' : '10'; ?>" style="text-align:center;color:#999;padding:2rem;">
                            No hay solicitudes aún.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($solicitudes as $s): ?>
                    <tr>
                        <td><?php echo $s['id_solicitud']; ?></td>
                        <td><?php echo htmlspecialchars($s['codigo_sala'] . ' - ' . $s['nombre_sala']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($s['fecha_reserva'])); ?></td>
                        <td><?php echo $s['id_bloque_inicio'] . ' → ' . $s['id_bloque_fin']; ?></td>
                        <td><?php echo htmlspecialchars($s['nombre_profesor'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($s['carrera_sigla'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($s['paralelo'] ?? '-'); ?></td>
                        <td>
                            <?php 
                            $actividad = $s['tipo_actividad'] ?? '-';
                            $iconos = [
                                'catedra' => '📚',
                                'control' => '📝',
                                'examen' => '📄',
                                'examen_titulo' => '🎓',
                                'charla' => '🎤',
                                'taller' => '🔧',
                                'reunion' => '🤝',
                                'otro' => '📌'
                            ];
                            $icono = $iconos[$actividad] ?? '';
                            echo $icono . ' ' . ucfirst(str_replace('_', ' ', $actividad));
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars(substr($s['motivo'] ?? '', 0, 20)) . (strlen($s['motivo'] ?? '') > 20 ? '...' : ''); ?></td>
                        <td>
                            <?php
                            switch ($s['estado']) {
                                case 'pendiente':
                                    echo '<span class="estado-pendiente">🟡 Pendiente</span>';
                                    break;
                                case 'aprobada':
                                    echo '<span class="estado-aprobada">🟢 Aprobada</span>';
                                    break;
                                case 'rechazada':
                                    echo '<span class="estado-rechazada">🔴 Rechazada</span>';
                                    break;
                            }
                            ?>
                        </td>
                        <?php if ($userRole === 'administrador'): ?>
                        <td><?php echo htmlspecialchars($s['solicitante'] ?? ''); ?></td>
                        <td>
                            <?php if ($s['estado'] === 'pendiente'): ?>
                            <a href="aprobar_solicitud.php?id=<?php echo $s['id_solicitud']; ?>&accion=aprobar" class="btn btn-success btn-sm">✅ Aprobar</a>
                            <a href="aprobar_solicitud.php?id=<?php echo $s['id_solicitud']; ?>&accion=rechazar" class="btn btn-danger btn-sm">❌ Rechazar</a>
                            <a href="editar_solicitud.php?id=<?php echo $s['id_solicitud']; ?>" class="btn btn-warning btn-sm">✏️ Editar</a>
                            <?php else: ?>
                            <span style="color:#999;">-</span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
    <footer><p>&copy; 2026 MAS - Proyecto de Título</p></footer>
</body>
</html>