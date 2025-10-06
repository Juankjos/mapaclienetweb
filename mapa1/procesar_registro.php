<?php
session_start();
require_once 'db.php';

/** ===== DEBUG: activar mientras pruebas ===== */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
ini_set('display_errors', 1);
error_reporting(E_ALL);
/** ========================================== */

function flash_and_back($msg) {
    $_SESSION['flash_error'] = $msg;
    header('Location: registro.php');
    exit;
}

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: registro.php'); exit;
}

// Recibir POST
$contrato = trim($_POST['contrato'] ?? '');
$correo   = trim($_POST['correo'] ?? '');
$passRaw  = $_POST['password'] ?? '';

// Validaciones
$errores = [];
if ($contrato === '') $errores[] = 'Contrato es obligatorio.';
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) $errores[] = 'Correo inválido.';
if (strlen($passRaw) < 8) $errores[] = 'La contraseña debe tener al menos 8 caracteres.';

if ($errores) {
    flash_and_back(implode(' ', $errores));
}

try {
    // Charset por si no lo seteaste en db.php
    if (method_exists($mysqli, 'set_charset')) {
        $mysqli->set_charset('utf8mb4');
    }

    // === 1) ¿Existe ya el contrato o correo? (método sin get_result) ===
    $sql = "SELECT COUNT(*) AS n FROM usuarios WHERE IDContrato = ? OR Correo = ? LIMIT 1";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ss', $contrato, $correo);
    $stmt->execute();
    $stmt->bind_result($n);
    $stmt->fetch();
    $stmt->close();

    if ((int)$n > 0) {
        flash_and_back('El contrato o el correo ya están registrados.');
    }

    // === 2) Insertar con hash ===
    $hash = password_hash($passRaw, PASSWORD_DEFAULT);

    $sql = "INSERT INTO usuarios (IDContrato, Correo, Pass) VALUES (?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('sss', $contrato, $correo, $hash);
    $stmt->execute();
    $stmt->close();

    // Guardar mensaje de éxito y redirigir al login
    $_SESSION['flash_success'] = '✅ Registro exitoso, ahora inicia sesión.';
    header('Location: login.php');
    exit;

} catch (mysqli_sql_exception $e) {
    // Errores típicos:
    // - 1062: entrada duplicada (por índices únicos)
    // - 1364/1048: columna no tiene valor por defecto / columna no puede ser NULL
    // - 1406: data too long for column
    $errno = $e->getCode();
    if ($errno === 1062) {
        flash_and_back('Ya existe un usuario con ese Contrato o Correo.');
    } elseif ($errno === 1048 || $errno === 1364) {
        flash_and_back('Faltan campos obligatorios en la tabla (revisa que Nombre/Direccion permitan NULL si no los envías).');
    } elseif ($errno === 1406) {
        flash_and_back('Algún dato excede el tamaño permitido por la columna (revisa longitudes).');
    } else {
        // En DEBUG mostramos el mensaje real:
        flash_and_back('Error al registrar: [' . $errno . '] ' . $e->getMessage());
    }
}
