<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php'); exit;
}

$contrato = trim($_POST['contrato'] ?? '');
$passRaw  = $_POST['password'] ?? '';

// Validación mínima
if ($contrato === '' || $passRaw === '') {
    echo "<script>alert('Contrato y contraseña son obligatorios.'); window.location.href='login.php';</script>";
    exit;
}

// Traer al usuario por contrato
$sql = "SELECT IDContrato, Correo, Nombre, Pass FROM usuarios WHERE IDContrato = ? LIMIT 1";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('s', $contrato);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

if (!$user) {
    echo "<script>alert('Contrato o contraseña incorrectos.'); window.location.href='login.php';</script>";
    exit;
}

$dbPass = $user['Pass'];
$autenticado = false;

// ¿Parece hash de password? (bcrypt empieza con $2y$ / $2a$ / $2b$)
if (preg_match('/^\$2[aby]\$/', $dbPass)) {
    // Hash: verificar
    $autenticado = password_verify($passRaw, $dbPass);
} else {
    // Posible texto plano legado
    if (hash_equals($dbPass, $passRaw)) {
        $autenticado = true;
        // Migrar a hash seguro
        $nuevoHash = password_hash($passRaw, PASSWORD_DEFAULT);
        $up = $mysqli->prepare("UPDATE usuarios SET Pass = ? WHERE IDContrato = ? LIMIT 1");
        $up->bind_param('ss', $nuevoHash, $contrato);
        $up->execute();
        $up->close();
    }
}

if (!$autenticado) {
    echo "<script>alert('Contrato o contraseña incorrectos.'); window.location.href='login.php';</script>";
    exit;
}

// Éxito: session hardening
session_regenerate_id(true);
$_SESSION['contrato'] = $user['IDContrato'];
$_SESSION['correo']   = $user['Correo'] ?? '';
$_SESSION['nombre']   = $user['Nombre'] ?? '';

// Redirigir al mapa
header('Location: http://127.0.0.1/mapaclienteweb/mapa1/ordenes_servicio.php');
exit;
