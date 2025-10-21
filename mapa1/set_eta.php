<?php
require_once '../db.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$reporteId = (int)($input['reporteId'] ?? 0);
$eta_iso   = $input['eta_iso'] ?? null;

if ($reporteId <= 0 || !$eta_iso) {
    http_response_code(400);
    echo json_encode(['ok'=>false, 'error'=>'bad request']);
    exit;
}

$eta = date('Y-m-d H:i:s', strtotime($eta_iso));

// Solo fija si Status='En camino' y ETA aún está vacío
$sql = "UPDATE reportes
        SET ETA=?
        WHERE IDReporte=? AND Status='En camino'
            AND (ETA IS NULL OR ETA='0000-00-00 00:00:00')";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('si', $eta, $reporteId);
$stmt->execute();

echo json_encode(['ok' => $stmt->affected_rows > 0]);
$stmt->close();
