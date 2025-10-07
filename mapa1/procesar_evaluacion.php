<?php
// Guarda Rate y Comentario si:
// - Sesión válida
// - El reporte pertenece al contrato
// - Status = 'Completado'
// - Rate actual = 0 (o NULL)

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

if (empty($_SESSION['contrato'])) {
  http_response_code(401);
  echo json_encode(['ok'=>false, 'message'=>'No autenticado']); exit;
}

$raw = file_get_contents('php://input');
$in  = json_decode($raw, true);
$reporte   = isset($in['reporte']) ? (int)$in['reporte'] : 0;
$rate      = isset($in['rate']) ? (int)$in['rate'] : 0;
$comentario= isset($in['comentario']) ? trim((string)$in['comentario']) : '';

if ($reporte <= 0 || $rate < 1 || $rate > 5) {
  http_response_code(400);
  echo json_encode(['ok'=>false, 'message'=>'Parámetros inválidos']); exit;
}

$contrato = $_SESSION['contrato'];

// Verifica elegibilidad
$sql = "
  SELECT p.IDReporte
  FROM produccion p
  WHERE p.IDReporte = ? AND p.IDContrato = ?
    AND p.Status IN ('Completado','Cancelado')
    AND COALESCE(p.Rate,0) = 0
  LIMIT 1
";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('is', $reporte, $contrato);
$stmt->execute();
$exists = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$exists) {
  http_response_code(403);
  echo json_encode(['ok'=>false, 'message'=>'No elegible para evaluar']); exit;
}

// Sanitiza comentario a longitud de columna (varchar(300))
if (mb_strlen($comentario) > 300) {
  $comentario = mb_substr($comentario, 0, 300);
}

// Actualiza Rate y Comentario
$upd = "UPDATE produccion SET Rate = ?, Comentario = ? WHERE IDReporte = ? AND IDContrato = ? LIMIT 1";
$stmt = $mysqli->prepare($upd);
$stmt->bind_param('isis', $rate, $comentario, $reporte, $contrato);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
  http_response_code(500);
  echo json_encode(['ok'=>false, 'message'=>'No se pudo guardar']); exit;
}

echo json_encode(['ok'=>true]);
