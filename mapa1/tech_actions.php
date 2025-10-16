<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

if (empty($_SESSION['contrato'])) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'message'=>'No autenticado']); exit;
}

$raw = file_get_contents('php://input');
$in  = json_decode($raw, true);
$action = $in['action'] ?? '';

function jerr($msg, $code=400){ http_response_code($code); echo json_encode(['ok'=>false,'message'=>$msg]); exit; }

if ($action === 'select_tec') {
  $id = isset($in['idtec']) ? (int)$in['idtec'] : 0;
  $nombre = trim($in['nombre'] ?? '');
  if ($id <= 0 || $nombre === '') jerr('Parámetros inválidos');

  // Asegura existencia del técnico (si ya existe, no lo modifica)
  // Ajusta según tus índices (si IdTec es PK, puedes usar INSERT IGNORE)
  $ins = $mysqli->prepare("INSERT IGNORE INTO tecnicos (IdTec, NombreTec, NumTec, Planta) VALUES (?, ?, '', 'N/A')");
  $ins->bind_param('is', $id, $nombre);
  $ins->execute();
  $ins->close();

  // Consulta si ya tiene PasswordHash
  $q = $mysqli->prepare("SELECT PasswordHash IS NOT NULL AS hasPwd FROM tecnicos WHERE IdTec = ? LIMIT 1");
  $q->bind_param('i', $id);
  $q->execute();
  $res = $q->get_result()->fetch_assoc();
  $q->close();

  $has = $res ? (bool)$res['hasPwd'] : false;
  echo json_encode(['ok'=>true, 'hasPassword'=>$has]);
  exit;
}

if ($action === 'save_password') {
  $id = isset($in['idtec']) ? (int)$in['idtec'] : 0;
  $pwd = (string)($in['password'] ?? '');
  if ($id <= 0 || strlen($pwd) < 8) jerr('Parámetros inválidos');

  // Debe existir
  $q = $mysqli->prepare("SELECT IdTec FROM tecnicos WHERE IdTec = ? LIMIT 1");
  $q->bind_param('i', $id);
  $q->execute();
  $exists = $q->get_result()->fetch_assoc();
  $q->close();
  if (!$exists) jerr('Técnico no encontrado', 404);

  $hash = password_hash($pwd, PASSWORD_BCRYPT);
  $u = $mysqli->prepare("UPDATE tecnicos SET PasswordHash = ? WHERE IdTec = ? LIMIT 1");
  $u->bind_param('si', $hash, $id);
  $ok = $u->execute();
  $u->close();

  if (!$ok) jerr('No se pudo guardar', 500);

  echo json_encode(['ok'=>true]);
  exit;
}

jerr('Acción no soportada', 400);
