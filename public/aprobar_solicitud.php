<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: login.php');
    exit;
}
require_once '../config/database.php';

$id_solicitud = $_GET['id'] ?? 0;
$accion = $_GET['accion'] ?? '';

if ($id_solicitud && $accion) {
    $estado = $accion === 'aprobar' ? 'aprobada' : 'rechazada';
    
    try {
        $pdo->beginTransaction();
        
        // Obtener datos de la solicitud
        $stmt = $pdo->prepare("
            SELECT id_sala, id_bloque_inicio, id_bloque_fin, fecha_reserva, id_usuario_solicitante
            FROM solicitudes WHERE id_solicitud = ?
        ");
        $stmt->execute([$id_solicitud]);
        $solicitud = $stmt->fetch();
        
        if (!$solicitud) {
            throw new Exception('Solicitud no encontrada');
        }
        
        // Actualizar estado de la solicitud
        $stmt = $pdo->prepare("UPDATE solicitudes SET estado = ? WHERE id_solicitud = ?");
        $stmt->execute([$estado, $id_solicitud]);
        
        // Si es aprobada, crear asignación
        if ($estado === 'aprobada') {
            $stmt = $pdo->prepare("
                INSERT INTO asignaciones (
                    id_solicitud, 
                    id_usuario_aprobador, 
                    id_sala, 
                    id_bloque_inicio, 
                    id_bloque_fin, 
                    fecha_reserva, 
                    estado
                ) VALUES (?, ?, ?, ?, ?, ?, 'activa')
            ");
            $stmt->execute([
                $id_solicitud,
                $_SESSION['usuario_id'],
                $solicitud['id_sala'],
                $solicitud['id_bloque_inicio'],
                $solicitud['id_bloque_fin'],
                $solicitud['fecha_reserva']
            ]);
        }
        
        $pdo->commit();
        
        $mensaje = $estado === 'aprobada' ? '✅ Solicitud aprobada y asignación creada' : '❌ Solicitud rechazada';
        header('Location: solicitudes.php?mensaje=' . urlencode($mensaje));
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Error: ' . $e->getMessage();
        header('Location: solicitudes.php?error=' . urlencode($error));
        exit;
    }
} else {
    header('Location: solicitudes.php');
    exit;
}
?>