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
$contrato  = $_SESSION['contrato'];
$reporteId = isset($_GET['reporte']) ? (int)$_GET['reporte'] : 0;

/**
 * Busca la orden elegible a evaluación:
 * - Status = 'Completado'
 * - Rate = 0 (o NULL)
 * - Del contrato de la sesión
 * - Si llega ?reporte= filtra por ese ID
 */
if ($reporteId > 0) {
    $sql = "
        SELECT r.IDReporte, r.IDContrato, r.Problema, r.FechaAgendado,
            p.IDTec, p.Status, COALESCE(p.Rate,0) AS Rate, p.Comentario,
            t.NombreTec, t.NumTec,
            u.Nombre AS NombreCliente
        FROM reportes r
        INNER JOIN usuarios   u ON u.IDContrato = r.IDContrato
        LEFT  JOIN produccion p ON p.IDReporte  = r.IDReporte AND p.IDContrato = r.IDContrato
        LEFT  JOIN tecnicos   t ON t.IdTec      = p.IDTec
        WHERE r.IDReporte = ? AND r.IDContrato = ?
            AND p.Status IN ('Completado','Cancelado')
            AND COALESCE(p.Rate,0) = 0
        LIMIT 1
    ";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('is', $reporteId, $contrato);
    } else {
    // Toma la más reciente "Completado" sin calificar del contrato
    $sql = "
        SELECT r.IDReporte, r.IDContrato, r.Problema, r.FechaAgendado,
            p.IDTec, p.Status, COALESCE(p.Rate,0) AS Rate, p.Comentario,
            t.NombreTec, t.NumTec,
            u.Nombre AS NombreCliente
        FROM reportes r
        INNER JOIN usuarios   u ON u.IDContrato = r.IDContrato
        LEFT  JOIN produccion p ON p.IDReporte  = r.IDReporte AND p.IDContrato = r.IDContrato
        LEFT  JOIN tecnicos   t ON t.IdTec      = p.IDTec
        WHERE r.IDContrato = ?
            AND p.Status IN ('Completado','Cancelado')
            AND COALESCE(p.Rate,0) = 0
        ORDER BY COALESCE(r.FechaAgendado,'1000-01-01') DESC, r.IDReporte DESC
        LIMIT 1
    ";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $contrato);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc() ?: null;
    $stmt->close();

    if (!$row) {
    // No hay nada para evaluar (o ya evaluado) -> vuelve a órdenes
    $_SESSION['flash_error'] = 'No tienes órdenes pendientes de evaluación.';
    header('Location: ordenes_servicio.php'); exit;
    }

    $payload = [
        'IDReporte'  => (int)$row['IDReporte'],
        'IDContrato' => $row['IDContrato'],
        'Problema'   => $row['Problema'] ?? '',
        'IDTec'      => isset($row['IDTec']) ? (int)$row['IDTec'] : null,
        'NombreTec'  => $row['NombreTec'] ?? 'Técnico',
        'NumTec'     => $row['NumTec'] ?? null,
        'Status'     => $row['Status'],
        'Rate'       => (int)$row['Rate'],
        'Nombre'     => $_SESSION['nombre'] ?? ($row['NombreCliente'] ?? 'Cliente')
    ];
?>
<!doctype html>
<html lang="es" data-bs-theme="light">
    <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Evaluación del servicio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="icon/Tvclogo.png">
    <link rel="stylesheet" href="styles/map/navbar.css" />
    <link rel="stylesheet" href="styles/map/root.css" />
    <link rel="stylesheet" href="styles/map/offcanvas.css" />
    <link rel="stylesheet" href="styles/evaluation/starrate.css" />
    <link rel="stylesheet" href="styles/evaluation/overlay.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* Ajustes mínimos de la página de evaluación */
        .rating-stars input { display:none; }
        .rating-stars label { cursor:pointer; }
        .overlay-listo[aria-hidden="false"] { display:block; }
        .overlay-listo[aria-hidden="true"]  { display:none; }
    </style>
    </head>
    <body>
    <!-- Overlay de éxito -->
    <div id="overlayListo" class="overlay-listo" aria-hidden="true">
        <canvas id="confettiCanvas" class="overlay-canvas" aria-hidden="true"></canvas>
        <div class="overlay-content d-flex flex-column align-items-center justify-content-center text-center">
        <h1 class="text-white display-3 fw-bold mb-3">¡Listo!</h1>
        <p class="text-white fs-5 mb-1">Tus comentarios han sido enviados.</p>
        <p class="text-white fs-6 mb-4">Serás redirigido en unos segundos…</p>
        <a class="btn btn-light" href="ordenes_servicio.php">OK</a>
        </div>
    </div>

    <!-- Navbar -->
    <nav class="navbar navbar-light bg-white shadow-sm app-navbar fixed-top">
        <div class="container-fluid">
        <div class="d-flex align-items-center gap-2">
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuLateral" aria-controls="menuLateral" aria-label="Abrir menú">
            <span class="navbar-toggler-icon"></span>
            </button>
            <img src="icon/icono.png" class="nav-tech-icon" alt="Logo" />
            <img src="icon/iconopride.png" id="iconopride" alt="Variación de logo" />
        </div>
        </div>
    </nav>

    <!-- Offcanvas -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="menuLateral" aria-labelledby="menuLateralLabel">
        <div class="offcanvas-body">
        <div class="d-flex align-items-center gap-3 mb-3">
            <div class="rounded-circle bg-light border" style="width:48px;height:48px; display:flex;align-items:center;justify-content:center;">
            <i class="bi bi-person fs-4 text-secondary"></i>
            </div>
            <div>
            <div class="fw-semibold" id="Nombre"><?= htmlspecialchars($payload['Nombre'], ENT_QUOTES, 'UTF-8') ?></div>
            <a href="administrar_cuenta.php" id="admin-cuenta" class="text-decoration-none small">Administrar cuenta</a>
            </div>
        </div>

        <div class="menu-simple d-flex flex-column gap-2 mt-3">
            <button type="button" class="menu-btn" onclick="window.location.href='ordenes_servicio.php'">
            <i class="bi bi-file-earmark-bar-graph-fill me-2"></i>
            Órdenes de Servicio
            </button>
            <button type="button" class="menu-btn" onclick="window.location.href='contacto_soporte.php'">
            <i class="bi bi-person-raised-hand me-2"></i>
            Contacto a Soporte
            </button>
        </div>
        </div>
        <div class="offcanvas-footer mt-auto">
            <form id="logoutForm" action="logout.php" method="post">
                <button id="btnLogout" type="button" class="btn-logout">
                <i class="bi bi-box-arrow-right me-2"></i>
                Cerrar Sesión
                </button>
            </form>
        </div>
    </div>

    <main class="container py-3" style="margin-top:72px;">
        <section id="eval" aria-labelledby="eval-title">
        <h6 id="eval-title" class="section-title visually-hidden">Evaluación</h6>

        <div class="card shadow-sm mb-3 mx-auto" style="max-width: 600px;">
            <div class="card-body">
            <div class="mb-3 d-flex justify-content-center">
                <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center" style="width:96px;height:96px;">
                <i class="bi bi-person fs-1 text-secondary"></i>
                </div>
            </div>

            <h5 class="mb-1 fw-bold text-dark text-center">¿Cómo fue tu experiencia con</h5>
            <h5 class="mb-3 fw-bold text-primary text-center"><?= htmlspecialchars($payload['NombreTec'], ENT_QUOTES, 'UTF-8') ?>?</h5>

            <!-- Stars -->
            <fieldset class="rating-stars mb-3 text-center" aria-labelledby="rating-label" role="radiogroup">
                <legend id="rating-label" class="visually-hidden">Calificación</legend>
                <input type="radio" name="svc-rating" id="svc-rating-5" value="5" aria-label="5 estrellas" />
                <label for="svc-rating-5" title="Excelente" tabindex="0"></label>
                <input type="radio" name="svc-rating" id="svc-rating-4" value="4" aria-label="4 estrellas" />
                <label for="svc-rating-4" title="Muy bueno" tabindex="0"></label>
                <input type="radio" name="svc-rating" id="svc-rating-3" value="3" aria-label="3 estrellas" />
                <label for="svc-rating-3" title="Bueno" tabindex="0"></label>
                <input type="radio" name="svc-rating" id="svc-rating-2" value="2" aria-label="2 estrellas" />
                <label for="svc-rating-2" title="Regular" tabindex="0"></label>
                <input type="radio" name="svc-rating" id="svc-rating-1" value="1" aria-label="1 estrella" />
                <label for="svc-rating-1" title="Malo" tabindex="0"></label>
            </fieldset>

            <input type="hidden" id="svc-rating-value" value="0" />

            <div class="mb-3 w-100">
                <label for="Comentario" class="form-label fw-semibold">Deja tus comentarios positivos si tuviste una buena experiencia.</label>
                <textarea id="Comentario" class="form-control" rows="5" maxlength="300" placeholder="Danos tu mejor opinión..."></textarea>
            </div>

            <div class="mt-3 w-100">
                <button class="btn btn-primary w-100" id="btnGuardarComentario" type="button">Enviar</button>
            </div>
            <div id="status" class="alert d-none mt-3" role="alert"></div>
            </div>
        </div>
        </section>
    </main>

    <script>
        // Inyecta datos para JS
        window.__EVAL__ = <?= json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="module" src="scripts/api/evaluation.js"></script>
    </body>
</html>
