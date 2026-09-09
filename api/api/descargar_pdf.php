<?php
// ============================================
// MAS - Modelo de Asignación de Salas
// DESCARGAR PROGRAMACIÓN EN PDF
// ============================================

session_start();

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo "No autorizado";
    exit;
}

// ============================================
// INCLUIR TCPDF Y CONFIGURACIÓN
// ============================================
require_once '../../libs/tcpdf/tcpdf.php';
require_once '../../config/database.php';

// Obtener la fecha
$fecha = $_GET['fecha'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    $fecha = date('Y-m-d');
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            s.codigo_sala,
            s.nombre_sala,
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
        ORDER BY s.codigo_sala, sol.id_bloque_inicio
    ");
    $stmt->execute([$fecha]);
    $ocupaciones = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error al consultar la base de datos: " . $e->getMessage());
}

// ============================================
// CREAR PDF CON TCPDF
// ============================================

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('MAS');
$pdf->SetAuthor('Flavio Hernandez');
$pdf->SetTitle('Programación de Salas');
$pdf->SetMargins(10, 10, 10);
$pdf->AddPage();

// Título
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'MAS - MODELO DE ASIGNACIÓN DE SALAS', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 8, 'Programación de Salas - ' . date('d/m/Y', strtotime($fecha)), 0, 1, 'C');
$pdf->Ln(5);

if (count($ocupaciones) > 0) {
    // Encabezados
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(26, 58, 92);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(20, 7, 'Sala', 1, 0, 'C', 1);
    $pdf->Cell(40, 7, 'Profesor', 1, 0, 'C', 1);
    $pdf->Cell(25, 7, 'Carrera', 1, 0, 'C', 1);
    $pdf->Cell(20, 7, 'Paralelo', 1, 0, 'C', 1);
    $pdf->Cell(25, 7, 'Actividad', 1, 0, 'C', 1);
    $pdf->Cell(25, 7, 'Bloques', 1, 1, 'C', 1);
    
    // Datos
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(0, 0, 0);
    $fill = 0;
    
    foreach ($ocupaciones as $o) {
        $pdf->Cell(20, 6, substr($o['codigo_sala'] ?? '', 0, 8), 1, 0, 'C', $fill);
        $pdf->Cell(40, 6, substr($o['nombre_profesor'] ?? 'No asignado', 0, 20), 1, 0, 'L', $fill);
        $pdf->Cell(25, 6, substr($o['carrera_sigla'] ?? 'N/E', 0, 12), 1, 0, 'C', $fill);
        $pdf->Cell(20, 6, substr($o['paralelo'] ?? 'N/E', 0, 8), 1, 0, 'C', $fill);
        $pdf->Cell(25, 6, substr($o['tipo_actividad'] ?? 'N/E', 0, 12), 1, 0, 'C', $fill);
        $pdf->Cell(25, 6, $o['id_bloque_inicio'] . '→' . $o['id_bloque_fin'], 1, 1, 'C', $fill);
        $fill = !$fill;
    }
    
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(0, 6, 'Total de ocupaciones: ' . count($ocupaciones), 0, 1, 'L');
} else {
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 10, 'No hay ocupaciones registradas para esta fecha.', 0, 1, 'C');
}

$pdf->Ln(10);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 5, 'Generado por MAS - ' . date('d/m/Y H:i:s'), 0, 1, 'C');

// Generar PDF
if (ob_get_level()) ob_end_clean();
$pdf->Output('programacion_' . $fecha . '.pdf', 'D');
exit;
?>