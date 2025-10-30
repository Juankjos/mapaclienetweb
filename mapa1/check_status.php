<?php
// api/check_status.php
session_start();
require_once '../db.php';

if (empty($_SESSION['contrato'])) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'error'=>'unauthorized']); exit;
}

$reporte = isset($_GET['reporte']) ? (int)$_GET['reporte'] : 0;
$contrato = $_GET['contrato'] ?? '';

if ($reporte <= 0 || $contrato === '') {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'bad_request']); exit;
}

$sql = "
    SELECT p.Status, COALESCE(p.Rate,0) AS Rate
    FROM produccion p
    WHERE p.IDReporte = ? AND p.IDContrato = ?
    LIMIT 1
";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('is', $reporte, $contrato);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$res) { echo json_encode(['ok'=>true,'status'=>null,'rate'=>null]); exit; }

echo json_encode(['ok'=>true,'status'=>$res['Status'],'rate'=>(int)$res['Rate']]);
