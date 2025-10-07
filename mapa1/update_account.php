<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

if (empty($_SESSION['contrato'])) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'message'=>'No autenticado']); exit;
}
$contrato = $_SESSION['contrato'];

$raw = file_get_contents('php://input');
$in  = json_decode($raw, true);
$action = $in['action'] ?? '';

try {
    if ($action === 'update_name') {
        $nombre = trim((string)($in['nombre'] ?? ''));
        if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 150) {
        http_response_code(400); echo json_encode(['ok'=>false,'message'=>'Nombre inválido']); exit;
        }

        $sql = "UPDATE usuarios SET Nombre = ? WHERE IDContrato = ? LIMIT 1";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ss', $nombre, $contrato);
        $stmt->execute(); $stmt->close();

        $_SESSION['nombre'] = $nombre;
        echo json_encode(['ok'=>true]); exit;
    }

    if ($action === 'update_email') {
        $correo = trim((string)($in['correo'] ?? ''));
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo) > 190) {
        http_response_code(400); echo json_encode(['ok'=>false,'message'=>'Correo inválido']); exit;
        }

        // Unicidad de correo
        $sql = "SELECT 1 FROM usuarios WHERE Correo = ? AND IDContrato <> ? LIMIT 1";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ss', $correo, $contrato);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($exists) {
        http_response_code(409); echo json_encode(['ok'=>false,'message'=>'Ese correo ya está en uso']); exit;
        }

        $sql = "UPDATE usuarios SET Correo = ? WHERE IDContrato = ? LIMIT 1";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ss', $correo, $contrato);
        $stmt->execute(); $stmt->close();

        $_SESSION['correo'] = $correo;
        echo json_encode(['ok'=>true]); exit;
    }

    if ($action === 'update_password') {
        $current = (string)($in['current'] ?? '');
        $newpass = (string)($in['newpass'] ?? '');
        if (strlen($newpass) < 8) {
        http_response_code(400); echo json_encode(['ok'=>false,'message'=>'La nueva contraseña es muy corta']); exit;
        }

        // Trae hash actual
        $sql = "SELECT Pass FROM usuarios WHERE IDContrato = ? LIMIT 1";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('s', $contrato);
        $stmt->execute();
        $hash = ($stmt->get_result()->fetch_assoc()['Pass'] ?? null);
        $stmt->close();

        if (!$hash || !password_verify($current, $hash)) {
        http_response_code(403); echo json_encode(['ok'=>false,'message'=>'La contraseña actual no es correcta']); exit;
        }

        $newHash = password_hash($newpass, PASSWORD_DEFAULT);
        $sql = "UPDATE usuarios SET Pass = ? WHERE IDContrato = ? LIMIT 1";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ss', $newHash, $contrato);
        $stmt->execute(); $stmt->close();

        echo json_encode(['ok'=>true]); exit;
    }

    http_response_code(400);
    echo json_encode(['ok'=>false,'message'=>'Acción no soportada']);
} catch (mysqli_sql_exception $e) {
    $code = $e->getCode();
    $msg = 'Error al guardar';
    if ($code === 1062) $msg = 'Valor duplicado';
    http_response_code(500);
    echo json_encode(['ok'=>false,'message'=>$msg]);
}
