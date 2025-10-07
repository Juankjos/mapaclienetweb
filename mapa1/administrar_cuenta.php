<?php
session_start();
require_once 'db.php';

// Requiere login
if (empty($_SESSION['contrato'])) {
    header('Location: login.php'); exit;
}
$contrato = $_SESSION['contrato'];

// Trae datos actuales del usuario
$sql = "SELECT IDContrato, Nombre, Correo FROM usuarios WHERE IDContrato = ? LIMIT 1";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('s', $contrato);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

if(!$user){
    $_SESSION['flash_error'] = 'No se encontró tu cuenta.';
    header('Location: ordenes_servicio.php'); exit;
}

// Normaliza
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
    <link rel="stylesheet" href="styles/map/root.css" />
    <link rel="stylesheet" href="styles/map/navbar.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .section-card{ max-width:760px; margin-inline:auto; }
        .form-readonly { background:#f8f9fa; }
        .actions{ gap:.5rem; }
        .toggle-line{ display:flex; align-items:center; justify-content:space-between; }
        .toggle-line .form-check{ margin-bottom:0; }
        .field-actions{ display:none; }
        .field-actions.show{ display:flex; }
    </style>
</head>
<body>
    <nav class="navbar navbar-light bg-white shadow-sm app-navbar fixed-top">
        <div class="container-fluid">
        <div class="d-flex align-items-center gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="ordenes_servicio.php">
            <i class="bi bi-arrow-left"></i> Volver
            </a>
            <img src="icon/icono.png" class="nav-tech-icon" alt="Logo" />
            <img src="icon/iconopride.png" id="iconopride" alt="Variación de logo" />
        </div>
        </div>
    </nav>

    <main class="container" style="margin-top:84px;">
        <h3 class="mb-3">Administración de tu Cuenta</h3>

        <!-- Contrato (solo lectura) -->
        <div class="card shadow-sm section-card mb-4">
        <div class="card-body">
            <div class="mb-2 text-muted">Contrato</div>
            <div class="form-control form-readonly" readonly><?= htmlspecialchars($user['IDContrato'], ENT_QUOTES,'UTF-8') ?></div>
        </div>
        </div>

        <!-- Editar Nombre -->
        <div class="card shadow-sm section-card mb-4" id="secNombre" data-original="<?= htmlspecialchars($user['Nombre'], ENT_QUOTES,'UTF-8') ?>">
        <div class="card-body">
            <div class="toggle-line mb-2">
            <div class="fw-semibold">Nombre</div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="tgNombre">
                <label class="form-check-label" for="tgNombre">Editar</label>
            </div>
            </div>

            <input type="text" class="form-control mb-2" id="inpNombre"
                value="<?= htmlspecialchars($user['Nombre'], ENT_QUOTES,'UTF-8') ?>" disabled
                placeholder="Nombre completo" maxlength="150">

            <div class="d-flex actions field-actions" id="actNombre">
            <button class="btn btn-outline-secondary" type="button" data-action="cancel-nombre">Cancelar</button>
            <button class="btn btn-primary" type="button" data-action="save-nombre">Guardar cambios</button>
            </div>
        </div>
        </div>

        <!-- Editar Correo -->
        <div class="card shadow-sm section-card mb-4" id="secCorreo" data-original="<?= htmlspecialchars($user['Correo'], ENT_QUOTES,'UTF-8') ?>">
        <div class="card-body">
            <div class="toggle-line mb-2">
            <div class="fw-semibold">Correo</div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="tgCorreo">
                <label class="form-check-label" for="tgCorreo">Editar</label>
            </div>
            </div>

            <input type="email" class="form-control mb-2" id="inpCorreo"
                value="<?= htmlspecialchars($user['Correo'], ENT_QUOTES,'UTF-8') ?>" disabled
                placeholder="ejemplo@correo.com" maxlength="190" inputmode="email" autocomplete="email">

            <div class="d-flex actions field-actions" id="actCorreo">
            <button class="btn btn-outline-secondary" type="button" data-action="cancel-correo">Cancelar</button>
            <button class="btn btn-primary" type="button" data-action="save-correo">Guardar cambios</button>
            </div>
        </div>
        </div>

        <!-- Cambiar contraseña -->
        <div class="card shadow-sm section-card mb-5" id="secPass">
        <div class="card-body">
            <div class="toggle-line mb-2">
            <div class="fw-semibold">Cambiar contraseña</div>
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
                <input type="password" class="form-control" id="inpPassNueva" placeholder="Contraseña nueva (min 8)" disabled minlength="8">
            </div>
            <div class="col-12 col-md-4">
                <input type="password" class="form-control" id="inpPassConfirm" placeholder="Confirmar contraseña" disabled minlength="8">
            </div>
            </div>

            <div class="d-flex actions field-actions mt-2" id="actPass">
            <button class="btn btn-outline-secondary" type="button" data-action="cancel-pass">Cancelar</button>
            <button class="btn btn-primary" type="button" data-action="save-pass">Guardar cambios</button>
            </div>
        </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="module" src="scripts/api/administrar_cuenta.js"></script>

</body>
</html>
