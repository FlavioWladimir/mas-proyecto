<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    exit;
}

$fecha = $_GET['fecha'] ?? date('Y-m-d');

// Simular descarga de PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="programacion_' . $fecha . '.pdf"');

echo "PDF de programación para la fecha: " . $fecha . "\n";
echo "Este es un archivo PDF simulado.\n";
echo "En producción, aquí se generaría el PDF real.\n";