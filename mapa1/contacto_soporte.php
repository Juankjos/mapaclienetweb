<?php
session_start();
require_once 'db.php';

/* ====== CONFIGURACIÓN MÍNIMA ====== */
// Cambia esto al correo real de soporte:
$SUPPORT_TO = 'soporte@tvcabletepa.mx';
$SUBJECT    = 'AYUDA TÉCNICA PÁGINA DE RASTREO TÉCNICO';

// Requiere login
if (empty($_SESSION['contrato'])) {
    header('Location: login.php'); exit;
}
$contrato = $_SESSION['contrato'];

// Trae datos del usuario
$sql = "SELECT IDContrato, Nombre, Correo FROM usuarios WHERE IDContrato=? LIMIT 1";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('s', $contrato);
$stmt->execute();
$res  = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

if (!$user) {
    $_SESSION['flash_error'] = 'No se encontró tu cuenta.';
    header('Location: ordenes_servicio.php'); exit;
}

$user['Nombre'] = $user['Nombre'] ?? '';
$user['Correo'] = $user['Correo'] ?? '';

/* ====== CSRF TOKEN ====== */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$send_ok    = false;
$send_error = null;

/* ====== MANEJO DE ENVÍO ====== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token_ok = isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
    if (!$token_ok) {
        $send_error = 'Solicitud inválida. Recarga la página e intenta de nuevo.';
    } else {
        $mensaje = trim($_POST['mensaje'] ?? '');
        if ($mensaje === '' || mb_strlen($mensaje) < 5) {
            $send_error = 'Por favor escribe un mensaje (al menos 5 caracteres).';
        } elseif (mb_strlen($mensaje) > 2000) {
            $send_error = 'El mensaje es demasiado largo (máximo 2000 caracteres).';
        } elseif (!filter_var($user['Correo'], FILTER_VALIDATE_EMAIL)) {
            $send_error = 'Tu correo en el sistema no es válido. Contacta a soporte.';
        } else {
            // Prepara cuerpo del correo
            $nombre      = $user['Nombre'] ?: 'Usuario';
            $correoUser  = $user['Correo'];
            $idContrato  = $user['IDContrato'] ?? $contrato;

            $bodyText = "Solicitud de soporte técnico\n"
                    . "----------------------------------------\n"
                    . "Contrato: {$idContrato}\n"
                    . "Nombre:   {$nombre}\n"
                    . "Correo:   {$correoUser}\n"
                    . "Fecha:    " . date('Y-m-d H:i:s') . "\n"
                    . "----------------------------------------\n\n"
                    . "Mensaje del usuario:\n{$mensaje}\n";

            // Intentar con PHPMailer si está disponible
            $sent = false;
            $phpmailer_error = null;

            try {
                if (file_exists(__DIR__ . '/vendor/autoload.php')) {
                    require __DIR__ . '/vendor/autoload.php';
                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

                    // === Config de transporte ===
                    // OPCIÓN 1: SMTP (recomendado). Descomenta y ajusta:
                    /*
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.tuservidor.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'tu_usuario';
                    $mail->Password   = 'tu_password';
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; // o ENCRYPTION_SMTPS
                    $mail->Port       = 587; // o 465
                    */

                    // Si no configuras SMTP, PHPMailer usará mail() del sistema.
                    $mail->CharSet = 'UTF-8';
                    $mail->setFrom($correoUser, $nombre);
                    $mail->addAddress($SUPPORT_TO);
                    $mail->Subject = $SUBJECT;
                    $mail->Body    = $bodyText;
                    $mail->AltBody = $bodyText;

                    $sent = $mail->send();
                }
            } catch (Throwable $e) {
                $phpmailer_error = $e->getMessage();
                $sent = false;
            }

            // Fallback a mail() si no se envió con PHPMailer
            if (!$sent) {
                $headers  = [];
                $headers[] = "From: {$nombre} <{$correoUser}>";
                $headers[] = "Reply-To: {$correoUser}";
                $headers[] = "MIME-Version: 1.0";
                $headers[] = "Content-Type: text/plain; charset=UTF-8";

                $sent = @mail($SUPPORT_TO, $SUBJECT, $bodyText, implode("\r\n", $headers));
            }

            if ($sent) {
                $send_ok = true;
                // Rotamos token para evitar reenvío accidental con back
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } else {
                $send_error = 'No se pudo enviar el correo en este momento. Intenta más tarde.';
                if (!empty($phpmailer_error)) {
                    // Log interno opcional:
                    // error_log('PHPMailer: ' . $phpmailer_error);
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Administración de tu Cuenta</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/map/root.css" />
    <link rel="stylesheet" href="styles/map/navbar.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <nav class="navbar navbar-light bg-white shadow-sm app-navbar fixed-top">
        <div class="container-fluid">
            <div class="d-flex align-items-center gap-2">
                <a class="btn btn-outline-primary btn-sm" href="ordenes_servicio.php">
                    <i class="bi bi-caret-left-fill"></i> Volver
                </a>
                <img src="icon/icono.png" class="nav-tech-icon" alt="Logo">
                <img src="icon/iconopride.png" id="iconopride" alt="Variación de logo">
            </div>
        </div>
    </nav>

    <main class="container page-wrap">
        <h3 class="mb-3" style="margin-top: 15px">Contacto a Soporte</h3>

        <div class="row">
            <div class="col-12 col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <p class="text-muted mb-2">
                            Cuéntanos brevemente tu problema técnico. 
                        </p>
                        <p>
                            Nuestro equipo recibirá tu mensaje por medio del correo que registraste.
                        </p>
                        <form method="post" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <div class="mb-3">
                                <textarea id="mensaje" name="mensaje" class="form-control" rows="6" maxlength="2000" required
                                    placeholder="Describe el problema, cuándo ocurre y cualquier detalle útil."></textarea>
                                <div class="form-text">
                                    Máximo 2000 caracteres. No adjuntes archivos.
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="submit" class="btn btn-outline-primary btn-sm" style="font-size:15.2px">
                                    <i class="bi bi-send"></i> Enviar a Soporte
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    <?php if ($send_ok): ?>
        // Éxito: SweetAlert + redirección en 5s
        Swal.fire({
            icon: 'success',
            title: '¡Mensaje enviado!',
            text: 'Tu solicitud fue enviada correctamente. Serás redirigido en 5 segundos…',
            timer: 5000,
            timerProgressBar: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            customClass: { popup: 'swal2-override-z' },
            didOpen: () => { Swal.showLoading(); }
        }).then(() => {
            window.location.href = 'ordenes_servicio.php';
        });
    <?php elseif ($send_error): ?>
        // Error: SweetAlert (no redirige)
        Swal.fire({
            icon: 'error',
            title: 'No se pudo enviar',
            text: <?php echo json_encode($send_error, JSON_UNESCAPED_UNICODE); ?>,
            customClass: { popup: 'swal2-override-z' }
        });
    <?php endif; ?>
    </script>
</body>
</html>
