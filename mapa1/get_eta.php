<?php
require_once '../db.php';
header('Content-Type: application/json');

$reporteId = (int)($_GET['reporte'] ?? 0);
$out = ['eta_iso' => null];

if ($reporteId > 0) {
    $stmt = $mysqli->prepare("SELECT ETA FROM reportes WHERE IDReporte=? LIMIT 1");
    $stmt->bind_param('i', $reporteId);
    $stmt->execute();
    $stmt->bind_result($eta);
    if ($stmt->fetch() && $eta) {
        $out['eta_iso'] = date('c', strtotime($eta));
    }
    $stmt->close();
}
echo json_encode($out);
