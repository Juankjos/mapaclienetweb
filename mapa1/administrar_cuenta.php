<?php
session_start();
require_once 'db.php';

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
?>
<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Administración de tu Cuenta</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="icon/Tvclogo.png">
    <link rel="stylesheet" href="styles/map/root.css" />
    <link rel="stylesheet" href="styles/map/navbar.css" />
    <link rel="stylesheet" href="styles/admn_cuenta/administrar_cuenta.css" />
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
        <h3 class="mb-3" style="margin-top: 15px">Administración de tu Cuenta</h3>

        <!-- Contrato (solo lectura) -->
        <section class="section">
        <div class="text-muted mb-1">Contrato</div>
        <div class="readonly-box"><?= htmlspecialchars($user['IDContrato'], ENT_QUOTES, 'UTF-8') ?></div>
        </section>

        <!-- Nombre -->
        <section id="secNombre" class="section" data-original="<?= htmlspecialchars($user['Nombre'], ENT_QUOTES, 'UTF-8') ?>">
        <div class="toggle-line mb-2">
            <div>
            <div class="section-title">Nombre</div>
            <div class="text-body-secondary small">Este es el nombre que veremos en tu cuenta.</div>
            </div>
            <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="tgNombre">
            <label class="form-check-label" for="tgNombre">Editar</label>
            </div>
        </div>
        <input type="text" class="form-control mb-2" id="inpNombre"
                value="<?= htmlspecialchars($user['Nombre'], ENT_QUOTES, 'UTF-8') ?>"
                placeholder="Nombre completo" maxlength="150" disabled>

        <div class="actions field-actions" id="actNombre">
            <button class="btn btn-outline-secondary" type="button" data-action="cancel-nombre" disabled>Cancelar</button>
            <button id="btn-guardar" class="btn btn-primary" type="button" data-action="save-nombre" disabled>Guardar cambios</button>
        </div>
        </section>

        <!-- Correo -->
        <section id="secCorreo" class="section" data-original="<?= htmlspecialchars($user['Correo'], ENT_QUOTES, 'UTF-8') ?>">
        <div class="toggle-line mb-2">
            <div>
                <div class="section-title">Correo</div>
                <div class="text-body-secondary small">Lo usaremos para avisos y recuperación de cuenta.</div>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="tgCorreo">
                <label class="form-check-label" for="tgCorreo">Editar</label>
            </div>
        </div>
        <input type="email" class="form-control mb-2" id="inpCorreo"
                value="<?= htmlspecialchars($user['Correo'], ENT_QUOTES, 'UTF-8') ?>"
                placeholder="ejemplo@correo.com" maxlength="190" inputmode="email" autocomplete="email" disabled>
        <div class="actions field-actions" id="actCorreo">
            <button class="btn btn-outline-secondary" type="button" data-action="cancel-correo" disabled>Cancelar</button>
            <button id="btn-guardar" class="btn btn-primary" type="button" data-action="save-correo" disabled>Guardar cambios</button>
        </div>
        </section>

        <!-- Contraseña -->
        <section id="secPass" class="section">
        <div class="toggle-line mb-2">
            <div>
            <div class="section-title">Cambiar contraseña</div>
            <div class="text-body-secondary small">Mínimo 8 caracteres.</div>
            </div>
            <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="tgPass">
            <label class="form-check-label" for="tgPass">Editar</label>
            </div>
        </div>

        <div class="row g-2">
            <div class="col-12 col-md-4">
            <input type="password" class="form-control" id="inpPassActual" placeholder="Contraseña actual" disabled>
            </div>
            <div class="col-12 col-md-4">
            <input type="password" class="form-control" id="inpPassNueva" placeholder="Contraseña nueva" minlength="8" disabled>
            </div>
            <div class="col-12 col-md-4">
            <input type="password" class="form-control" id="inpPassConfirm" placeholder="Confirmar contraseña" minlength="8" disabled>
            </div>
        </div>

        <div class="actions field-actions mt-2" id="actPass">
            <button class="btn btn-outline-secondary" type="button" data-action="cancel-pass" disabled>Cancelar</button>
            <button id="btn-guardar" class="btn btn-primary" type="button" data-action="save-pass" disabled>Guardar cambios</button>
        </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="module" src="scripts/api/administrar_cuenta.js"></script>
    <script type="module" src="scripts/ui/administrar_cuenta_style.js"></script>

</body>
</html>
