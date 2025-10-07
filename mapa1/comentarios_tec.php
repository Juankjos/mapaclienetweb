<?php
session_start();
require_once 'db.php';

if (empty($_SESSION['contrato'])) {
    header('Location: login.php'); exit;
}

$tecId = isset($_GET['tec']) ? (int)$_GET['tec'] : 0;
if ($tecId <= 0) {
    header('Location: rate_tec.php'); exit;
}

// Datos del técnico
$sqlTec = "SELECT IdTec, NombreTec, NumTec FROM tecnicos WHERE IdTec = ? LIMIT 1";
$st = $mysqli->prepare($sqlTec);
$st->bind_param('i', $tecId);
$st->execute();
$tec = $st->get_result()->fetch_assoc();
$st->close();

if (!$tec) {
    $_SESSION['flash_error'] = 'Técnico no encontrado.';
    header('Location: rate_tec.php'); exit;
}

// Comentarios: status Completado/Cancelado, comentario no vacío ni 'Sin comentarios'
$sql = "
    SELECT
        COALESCE(p.FechaFin, r.FechaAgendado, p.FechaInicio) AS Fecha,
        p.IDReporte,
        p.IDContrato,
        p.Comentario,
        p.Rate
    FROM produccion p
    INNER JOIN reportes r
        ON r.IDReporte = p.IDReporte
        AND r.IDContrato = p.IDContrato
    WHERE p.IDTec = ?
        AND p.Status IN ('Completado','Cancelado')
        AND TRIM(p.Comentario) <> ''
        AND p.Comentario NOT IN ('Sin comentarios','Sin comentrarios')
    ORDER BY COALESCE(p.FechaFin, r.FechaAgendado, p.FechaInicio, '1000-01-01') DESC,
            p.IDReporte DESC
    LIMIT 100
";
$st = $mysqli->prepare($sql);
$st->bind_param('i', $tecId);
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

function esc($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Comentarios de Técnico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/map/navbar.css" />
    <link rel="stylesheet" href="styles/map/root.css" />
    <link rel="stylesheet" href="styles/map/offcanvas.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .avatar { width:48px;height:48px; object-fit:cover; border-radius:50%; border:1px solid #e5e5e5; }
        .sticky-header { position: sticky; top: 72px; z-index: 1020; background: var(--bs-body-bg); }
        .comment-cell { max-width: 560px; }
        .nowrap { white-space: nowrap; }
    </style>
</head>
<body>

    <!-- Navbar simplificada -->
    <nav class="navbar navbar-light bg-white shadow-sm fixed-top">
        <div class="container-fluid d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
            <a href="rate_tec.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
            <img src="icon/icono.png" class="nav-tech-icon" alt="Logo" />
            <span class="fw-bold">Comentarios recibidos a <?= esc($tec['NombreTec'] ?: 'Técnico') ?></span>
            </div>
        </div>
    </nav>

    <main class="container py-4" style="margin-top:72px;">
        <section aria-labelledby="c-title">
            <div class="d-flex align-items-center gap-3 mb-3">
                    <h5 id="c-title" class="mb-0"><?= esc($tec['NombreTec'] ?: 'Técnico') ?></h5>
                    <div class="text-body-secondary small">ID: <?= (int)$tec['IdTec'] ?><?= $tec['NumTec'] ? ' · #'.esc($tec['NumTec']) : '' ?></div>
                </div>
            </div>

            <div class="table-responsive table-scroll"> <!-- table-scroll es opcional -->
                <table class="table table-hover table-sticky">
                    <thead class="table-light">
                        <tr>
                            <th class="nowrap" style="width:140px;">Fecha</th>
                            <th class="nowrap" style="width:130px;">Nº Reporte</th>
                            <th class="nowrap" style="width:130px;">Contrato</th>
                            <th>Comentario</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($rows): ?>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td class="nowrap">
                                    <?= esc($r['Fecha'] ? date('Y-m-d H:i', strtotime($r['Fecha'])) : '-') ?>
                                </td>
                                <td class="nowrap"><?= (int)$r['IDReporte'] ?></td>
                                <td class="nowrap"><?= esc($r['IDContrato']) ?></td>
                                <td class="comment-cell">
                                    <div class="fw-normal"><?= nl2br(esc($r['Comentario'])) ?></div>
                                    <?php if ((int)$r['Rate'] >= 1): ?>
                                        <div class="text-body-secondary small mt-1">Calificación: <?= (int)$r['Rate'] ?>/5</div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center text-body-secondary py-4">Sin comentarios disponibles.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
