<?php
session_start();
require_once 'db.php';
require_once 'auth.php';
gate_mesa_only_these();
gate_only_mesa_for_these();

// Requiere login
if (empty($_SESSION['contrato'])) {
    header('Location: login.php'); exit;
}
?>
<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Registro de técnico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/map/navbar.css" />
    <link rel="stylesheet" href="styles/map/root.css" />
    <link rel="stylesheet" href="styles/map/offcanvas.css" />
    <link rel="stylesheet" href="styles/rate/rate_tec.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .panel { max-width: 760px; margin-inline: auto; }
        .fade-slide { transition: all .25s ease; overflow: hidden; }
        .fade-slide.collapsed { max-height: 0; opacity: 0; margin-top: 0 !important; }
        .fade-slide.expanded { max-height: 400px; opacity: 1; }
        .tech-chip { font-weight: 600; }
    </style>
    </head>
    <body>
    <!-- Navbar -->
    <nav class="navbar navbar-light bg-white shadow-sm app-navbar fixed-top">
        <div class="container-fluid">
        <div class="d-flex align-items-center gap-2">
            <a class="btn btn-outline-primary btn-sm" href="rate_tec.php">
            <i class="bi bi-arrow-left"></i> Volver
            </a>
            <img src="icon/icono.png" class="nav-tech-icon" alt="Logo" />
            <img src="icon/iconopride.png" id="iconopride" alt="Variación de logo" />
        </div>
        </div>
    </nav>

    <main class="container panel" style="margin-top:84px;">
        <h3 class="mb-3">Registro de técnico</h3>

        <!-- Selección de técnico -->
        <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
            <div class="col-12 col-md-8">
                <label class="form-label">Selecciona un técnico</label>
                <select id="selTec" class="form-select">
                <option value="">— Elegir —</option>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <button id="btnSelect" type="button" class="btn btn-primary w-100">
                Seleccionar técnico
                </button>
            </div>
            </div>

            <!-- Técnico seleccionado -->
            <div id="selInfo" class="alert alert-info d-none mt-3 mb-0">
            Técnico seleccionado: <span id="lblTec" class="tech-chip"></span>
            <button id="btnCambiar" type="button" class="btn btn-link btn-sm">Cambiar</button>
            </div>
        </div>
        </div>

        <!-- ====== BLOQUE PLANTA ====== -->
        <div id="plantaWrap" class="card shadow-sm mb-3 d-none">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="fw-semibold">Planta</div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="tgPlanta">
                    <label class="form-check-label" for="tgPlanta">Editar</label>
                </div>
            </div>

            <input id="inpPlanta" type="text" class="form-control" placeholder="Ej. Tepatitlán, Jalisco." disabled maxlength="100">

            <div class="d-flex gap-2 mt-3">
                <button id="btnSavePlanta" type="button" class="btn btn-primary" disabled>Guardar</button>
                <button id="btnCancelPlanta" type="button" class="btn btn-outline-secondary" disabled>Cancelar</button>
            </div>

            <div id="msgPlanta" class="alert d-none mt-3" role="alert"></div>
        </div>
        </div>

        <!-- Formulario de contraseña (crear o cambiar) -->
        <div id="frmWrap" class="card shadow-sm mb-5 d-none">
        <div class="card-body">
            <div class="mb-2">
            <span id="modeBadge" class="badge text-bg-secondary">Modo</span>
            </div>

            <div class="row g-2">
            <div class="col-12 col-md-6">
                <label id="lblPass1" class="form-label">Crear contraseña</label>
                <input id="pass1" type="password" class="form-control" placeholder="Min. 8 caracteres" minlength="8">
            </div>
            <div class="col-12 col-md-6">
                <label id="lblPass2" class="form-label">Confirmar contraseña</label>
                <input id="pass2" type="password" class="form-control" placeholder="Repite la contraseña" minlength="8">
            </div>
            </div>

            <div class="d-flex gap-2 mt-3">
            <button id="btnGuardar" type="button" class="btn btn-primary">Guardar</button>
            <button id="btnCancelar" type="button" class="btn btn-outline-secondary">Cancelar</button>
            </div>

            <div id="msg" class="alert d-none mt-3" role="alert"></div>
        </div>
        </div>
    </main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="module" src="scripts/ui/registro_tec.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
