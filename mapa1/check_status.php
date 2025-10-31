<?php
// check_status.php
session_start();
require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$reporteId = isset($_GET['reporte']) ? (int)$_GET['reporte'] : 0;
$contrato  = isset($_GET['contrato']) ? $_GET['contrato'] : '';

if ($reporteId <= 0 || $contrato === '') {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Parámetros inválidos']); exit;
}

$sql = "
    SELECT p.Status, COALESCE(p.Rate,0) AS Rate, 
        UNIX_TIMESTAMP(GREATEST(COALESCE(p.FechaFin, '1970-01-01'), COALESCE(p.FechaInicio, '1970-01-01'))) AS updated_ts
    FROM produccion p
    WHERE p.IDReporte = ? AND p.IDContrato = ?
    LIMIT 1
";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('is', $reporteId, $contrato);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) {
    http_response_code(404);
    echo json_encode(['ok'=>false,'error'=>'No encontrado']); exit;
}

echo json_encode([
    'ok'     => true,
    'status' => $row['Status'],         // 'En camino' | 'Completado' | 'Cancelado' | ...
    'rate'   => (int)$row['Rate'],       // 0..5
    'ts'     => (int)$row['updated_ts']  // para depurar / invalidar caches
], JSON_UNESCAPED_UNICODE);
