<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    exit;
}
require_once '../../config/database.php';

$fecha = $_GET['fecha'] ?? date('Y-m-d');
$sala_id = isset($_GET['sala']) && $_GET['sala'] !== '' ? intval($_GET['sala']) : null;
$bloque = isset($_GET['bloque']) && $_GET['bloque'] !== '' ? intval($_GET['bloque']) : null;

// Obtener salas
$sql = "SELECT * FROM salas WHERE estado = 'disponible'";
if ($sala_id) {
    $sql .= " AND id_sala = $sala_id";
}
$salas = $pdo->query($sql)->fetchAll();

// Obtener ocupaciones
$sql_ocup = "SELECT s.id_sala, sol.id_bloque_inicio, sol.id_bloque_fin
              FROM asignaciones a
              JOIN solicitudes sol ON a.id_solicitud = sol.id_solicitud
              JOIN salas s ON a.id_sala = s.id_sala
              WHERE a.fecha_reserva = ? AND a.estado = 'activa'";
$stmt = $pdo->prepare($sql_ocup);
$stmt->execute([$fecha]);
$ocupaciones = $stmt->fetchAll();

$ocupado = [];
foreach ($ocupaciones as $o) {
    for ($i = $o['id_bloque_inicio']; $i <= $o['id_bloque_fin']; $i++) {
        $ocupado[$o['id_sala']][$i] = true;
    }
}

// Mostrar tabla
?>
<table>
    <thead>
        <tr>
            <th>Hora</th>
            <?php foreach ($salas as $sala): ?>
            <th><?php echo htmlspecialchars($sala['codigo_sala']); ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php for ($b = 1; $b <= 10; $b++): ?>
        <?php if ($bloque && $b != $bloque) continue; ?>
        <tr>
            <td><strong>Bloque <?php echo $b; ?></strong></td>
            <?php foreach ($salas as $sala): ?>
            <td>
                <?php if (isset($ocupado[$sala['id_sala']][$b])): ?>
                    <span class="ocupado">🔴 OC</span>
                <?php else: ?>
                    <span class="disponible">🟢 LIB</span>
                <?php endif; ?>
            </td>
            <?php endforeach; ?>
        </tr>
        <?php endfor; ?>
    </tbody>
</table>