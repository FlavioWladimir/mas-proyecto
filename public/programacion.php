<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../config/database.php';

$userName = $_SESSION['nombre_completo'] ?? '';
$userRole = $_SESSION['rol'] ?? '';
$fecha = $_GET['fecha'] ?? date('Y-m-d');

// Obtener salas
$salas = $pdo->query("SELECT * FROM salas WHERE estado = 'disponible' ORDER BY codigo_sala")->fetchAll();

// Obtener bloques
$bloques = $pdo->query("SELECT id_bloque, numero_bloque FROM bloques_horarios ORDER BY numero_bloque")->fetchAll();

// Obtener ocupaciones para la fecha
$stmt = $pdo->prepare("
    SELECT 
        s.id_sala,
        sol.id_bloque_inicio, 
        sol.id_bloque_fin,
        sol.nombre_profesor,
        sol.carrera_sigla,
        sol.paralelo,
        sol.tipo_actividad
    FROM asignaciones a
    JOIN solicitudes sol ON a.id_solicitud = sol.id_solicitud
    JOIN salas s ON a.id_sala = s.id_sala
    WHERE a.fecha_reserva = ? AND a.estado = 'activa'
");
$stmt->execute([$fecha]);
$ocupaciones = $stmt->fetchAll();

// Crear array de ocupación
$ocupado = [];
foreach ($ocupaciones as $o) {
    $detalles = [
        'profesor' => $o['nombre_profesor'] ?? '',
        'carrera' => $o['carrera_sigla'] ?? '',
        'paralelo' => $o['paralelo'] ?? '',
        'actividad' => $o['tipo_actividad'] ?? ''
    ];
    for ($i = $o['id_bloque_inicio']; $i <= $o['id_bloque_fin']; $i++) {
        $ocupado[$o['id_sala']][$i] = $detalles;
    }
}

// Función para obtener el icono de actividad (para uso interno, no se muestra)
function getActividadIcon($tipo) {
    $iconos = [
        'catedra' => '📚',
        'control' => '📝',
        'examen' => '📄',
        'examen_titulo' => '🎓',
        'charla' => '🎤',
        'taller' => '🔧',
        'reunion' => '🤝',
        'otro' => ''
    ];
    return $iconos[$tipo] ?? '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programación - MAS</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .programacion-excel {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 10px;
            border-collapse: collapse;
            width: 100%;
            background: #fff;
        }
        .programacion-excel th {
            background: #1a3a5c;
            color: #fff;
            font-weight: bold;
            padding: 4px 6px;
            border: 1px solid #1a3a5c;
            text-align: center;
            font-size: 9px;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .programacion-excel td {
            border: 1px solid #ddd;
            padding: 2px 4px;
            text-align: center;
            vertical-align: middle;
            font-size: 9px;
            min-width: 60px;
            max-width: 120px;
        }
        .programacion-excel tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .programacion-excel tr:hover {
            background-color: #e8f0fe;
        }
        .programacion-excel .sala-col {
            background-color: #f0f4f8;
            font-weight: bold;
            color: #333;
            min-width: 60px;
            position: sticky;
            left: 0;
            z-index: 5;
        }
        .programacion-excel .celda-ocupada {
            background-color: #fce8e6 !important;
        }
        .programacion-excel .celda-disponible {
            background-color: #e6f4ea !important;
            color: #1e7e34;
        }
        .programacion-excel .profesor-nombre {
            font-weight: bold;
            font-size: 9px;
            color: #1a3a5c;
        }
        .programacion-excel .profesor-carrera {
            font-size: 8px;
            color: #555;
        }
        .programacion-excel .profesor-paralelo {
            font-size: 8px;
            color: #666;
            background: #f0f0f0;
            padding: 0 3px;
            border-radius: 2px;
        }
        .programacion-excel .profesor-actividad {
            font-size: 8px;
            font-weight: bold;
            color: #d93025;
            display: block;
            margin-top: 1px;
        }
        .table-container {
            overflow-x: auto;
            max-height: 600px;
            overflow-y: auto;
        }
        .table-container table {
            min-width: 100%;
        }
        .bloque-header {
            font-size: 7px;
            font-weight: normal;
            display: block;
            color: #aad0f5;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo"><h1>MAS</h1><span>Modelo de Asignación de Salas</span></div>
            <ul>
                <li><a href="index.php">Inicio</a></li>
                <li><a href="programacion.php" class="active">Programación</a></li>
                <li><a href="solicitar.php">Solicitar</a></li>
                <li><a href="solicitudes.php">Mis Solicitudes</a></li>
                <?php if ($userRole === 'administrador'): ?>
                <li><a href="admin/salas.php">Administración</a></li>
                <?php endif; ?>
                <li><span>👤 <?php echo htmlspecialchars($userName); ?></span></li>
                <li><a href="logout.php">Cerrar sesión</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <h2>📊 Programación de Salas</h2>
        <p>Visualiza la disponibilidad de salas para el día <strong><?php echo date('d/m/Y', strtotime($fecha)); ?></strong></p>

        <div class="filters">
            <div class="form-group">
                <label for="fecha">Fecha</label>
                <input type="date" id="fecha" name="fecha" value="<?php echo $fecha; ?>">
            </div>
            <button class="btn btn-primary" onclick="filtrarProgramacion()">Filtrar</button>
            <button class="btn btn-success" onclick="descargarPDF()">📄 Descargar PDF</button>
        </div>

        <div class="table-container">
            <table class="programacion-excel">
                <thead>
                    <tr>
                        <th style="min-width:60px;position:sticky;left:0;z-index:15;background:#1a3a5c;">Sala</th>
                        <?php foreach ($bloques as $b): ?>
                        <th style="min-width:60px;">
                            B<?php echo $b['numero_bloque']; ?>
                            <span class="bloque-header">Bloque <?php echo $b['numero_bloque']; ?></span>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($salas as $sala): ?>
                    <tr>
                        <td class="sala-col"><?php echo htmlspecialchars($sala['codigo_sala']); ?></td>
                        <?php foreach ($bloques as $b): ?>
                        <?php 
                            $ocupado_actual = isset($ocupado[$sala['id_sala']][$b['id_bloque']]);
                            $info = $ocupado_actual ? $ocupado[$sala['id_sala']][$b['id_bloque']] : null;
                        ?>
                        <td class="<?php echo $ocupado_actual ? 'celda-ocupada' : 'celda-disponible'; ?>">
                            <?php if ($ocupado_actual): ?>
                                <div>
                                    <?php if (!empty($info['profesor'])): ?>
                                        <div class="profesor-nombre"><?php echo htmlspecialchars(substr($info['profesor'], 0, 12)); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($info['carrera'])): ?>
                                        <div class="profesor-carrera"><?php echo htmlspecialchars($info['carrera']); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($info['paralelo'])): ?>
                                        <div class="profesor-paralelo">P<?php echo htmlspecialchars($info['paralelo']); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($info['actividad'])): ?>
                                        <div class="profesor-actividad"><?php echo htmlspecialchars($info['actividad']); ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span style="color:#1e7e34;font-weight:bold;">✓</span>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top:1rem;display:flex;flex-wrap:wrap;gap:1.5rem;background:white;padding:1rem;border-radius:12px;box-shadow:0 4px 10px rgba(0,0,0,0.06);">
            <div><strong>Leyenda:</strong></div>
            <div><span style="background:#e6f4ea;padding:2px 10px;border-radius:4px;color:#1e7e34;">✓</span> = Disponible</div>
            <div><span style="background:#fce8e6;padding:2px 10px;border-radius:4px;color:#d93025;">🔴</span> = Ocupado</div>
            <div style="color:#999;font-size:10px;">Pasa el mouse sobre las celdas para ver detalles</div>
        </div>
    </main>
    <footer><p>&copy; 2026 MAS - Proyecto de Título</p></footer>
    <script>
        function filtrarProgramacion() {
            const fecha = document.getElementById('fecha').value;
            if (fecha) {
                window.location.href = 'programacion.php?fecha=' + fecha;
            }
        }
        function descargarPDF() {
            const fecha = document.getElementById('fecha').value;
            if (!fecha) {
                alert('Selecciona una fecha para descargar el PDF.');
                return;
            }
            window.location.href = 'api/descargar_pdf.php?fecha=' + fecha;
        }
    </script>
</body>
</html>