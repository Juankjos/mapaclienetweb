<?php
session_start();
require_once 'db.php';
require_once 'auth.php';
gate_mesa_only_these();
gate_only_mesa_for_these();

if (empty($_SESSION['contrato'])) {
    header('Location: login.php'); exit;
}

$tecId = isset($_GET['tec']) ? (int)$_GET['tec'] : 0;
if ($tecId <= 0) {
    header('Location: rate_tec.php'); exit;
}

// ====== Parámetros de filtro ======
$fecha_desde = isset($_GET['desde']) ? trim($_GET['desde']) : '';
$fecha_hasta = isset($_GET['hasta']) ? trim($_GET['hasta']) : '';
$contrato_q  = isset($_GET['contrato']) ? trim($_GET['contrato']) : '';
$reporte_q   = isset($_GET['reporte']) ? (int)$_GET['reporte'] : 0;
$status_q    = isset($_GET['status']) ? trim($_GET['status']) : ''; // '', 'Completado', 'Cancelado'

// Normaliza fechas (espera YYYY-MM-DD)
$fecha_desde_ok = preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_desde) ? $fecha_desde.' 00:00:00' : '';
$fecha_hasta_ok = preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_hasta) ? $fecha_hasta.' 23:59:59' : '';

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

// ====== Query de comentarios con filtros ======
// Fecha base: COALESCE(FechaFin, FechaAgendado, FechaInicio)
$wheres = [];
$params = [];
$types  = 'i'; // siempre iniciamos con el Id del técnico
$params[] = $tecId;

// Estado (opcional)
if ($status_q === 'Completado' || $status_q === 'Cancelado') {
    $wheres[] = "p.Status = ?";
    $types .= 's';
    $params[] = $status_q;
} else {
    // Por defecto mostramos Completado + Cancelado
    $wheres[] = "p.Status IN ('Completado','Cancelado')";
}

// Rango de fechas (opcional)
if ($fecha_desde_ok !== '') {
    $wheres[] = "COALESCE(p.FechaFin, r.FechaAgendado, p.FechaInicio) >= ?";
    $types .= 's';
    $params[] = $fecha_desde_ok;
}
if ($fecha_hasta_ok !== '') {
    $wheres[] = "COALESCE(p.FechaFin, r.FechaAgendado, p.FechaInicio) <= ?";
    $types .= 's';
    $params[] = $fecha_hasta_ok;
}

// Contrato (opcional, LIKE)
if ($contrato_q !== '') {
    $wheres[] = "p.IDContrato LIKE ?";
    $types .= 's';
    $params[] = '%'.$contrato_q.'%';
}

// Nº Reporte (opcional, exacto)
if ($reporte_q > 0) {
    $wheres[] = "p.IDReporte = ?";
    $types .= 'i';
    $params[] = $reporte_q;
}

$whereSql = $wheres ? (' AND '.implode(' AND ', $wheres)) : '';

// IMPORTANTE: Traemos Motivo (cancelados.Motivo) por LEFT JOIN a produccion.IDProd
$sql = "
    SELECT
        COALESCE(p.FechaFin, r.FechaAgendado, p.FechaInicio) AS Fecha,
        p.IDProd,
        p.IDReporte,
        p.IDContrato,
        p.Status,
        p.Comentario,      -- comentario del cliente (evaluación)
        p.Rate,
        c.Motivo AS MotivoTecnico
    FROM produccion p
    INNER JOIN reportes r
        ON r.IDReporte = p.IDReporte
        AND r.IDContrato = p.IDContrato
    LEFT JOIN cancelados c
        ON c.IDProd = p.IDProd
    WHERE p.IDTec = ?
    $whereSql
    ORDER BY COALESCE(p.FechaFin, r.FechaAgendado, p.FechaInicio, '1000-01-01') DESC,
            p.IDReporte DESC
    LIMIT 500
";
$st = $mysqli->prepare($sql);
$st->bind_param($types, ...$params);
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

function esc($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function fmtFecha($dt){
    if (!$dt) return '-';
    $ts = strtotime($dt);
    static $mes = ['ENE','FEB','MAR','ABRI','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'];
    $d  = date('d', $ts);
    $m  = $mes[(int)date('n', $ts)-1];
    $y  = date('Y', $ts);
    $hm = date('H:i', $ts);
    return "$d $m $y · $hm";
}

// Helpers UI: estado activo de botones
function activeBtn($cur, $want){
    return $cur === $want ? 'active' : '';
}
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
    <link rel="stylesheet" href="styles/rate/rate_tec.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

    <!-- Navbar -->
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

        <!-- ====== FILTROS ====== -->
        <form class="card shadow-sm mb-3" method="get" action="comentarios_tec.php">
            <input type="hidden" name="tec" value="<?= (int)$tecId ?>">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label">Desde</label>
                        <input type="date" class="form-control" name="desde" value="<?= esc($fecha_desde) ?>">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Hasta</label>
                        <input type="date" class="form-control" name="hasta" value="<?= esc($fecha_hasta) ?>">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Contrato</label>
                        <input type="text" class="form-control" name="contrato" value="<?= esc($contrato_q) ?>">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">N° Reporte</label>
                        <input type="number" class="form-control" name="reporte" value="<?= $reporte_q ?: '' ?>">
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                    <div class="btn-group" role="group" aria-label="Estado">
                        <a class="btn btn-outline-secondary filter-chip <?= activeBtn($status_q,'') ?>" href="?tec=<?= (int)$tecId ?>&desde=<?= esc($fecha_desde) ?>&hasta=<?= esc($fecha_hasta) ?>&contrato=<?= esc($contrato_q) ?>&reporte=<?= $reporte_q ?>">Todos</a>
                        <a class="btn btn-outline-success filter-chip <?= activeBtn($status_q,'Completado') ?>" href="?tec=<?= (int)$tecId ?>&status=Completado&desde=<?= esc($fecha_desde) ?>&hasta=<?= esc($fecha_hasta) ?>&contrato=<?= esc($contrato_q) ?>&reporte=<?= $reporte_q ?>">Completados</a>
                        <a class="btn btn-outline-danger filter-chip <?= activeBtn($status_q,'Cancelado') ?>" href="?tec=<?= (int)$tecId ?>&status=Cancelado&desde=<?= esc($fecha_desde) ?>&hasta=<?= esc($fecha_hasta) ?>&contrato=<?= esc($contrato_q) ?>&reporte=<?= $reporte_q ?>">Cancelados</a>
                    </div>

                    <button type="submit" class="btn btn-primary ms-auto" id="btn-search">
                        <i class="bi bi-search me-1"></i> Buscar
                    </button>
                    <a class="btn btn-outline-secondary" href="comentarios_tec.php?tec=<?= (int)$tecId ?>">
                        Limpiar
                    </a>
                </div>
            </div>
        </form>

        <!-- Encabezado -->
        <section aria-labelledby="c-title">
            <div class="d-flex align-items-center gap-3 mb-3">
                <h5 id="c-title" class="mb-0"><?= esc($tec['NombreTec'] ?: 'Técnico') ?></h5>
                <div class="text-body-secondary small">
                    ID: <?= (int)$tec['IdTec'] ?><?= $tec['NumTec'] ? ' · Teléfono: '.esc($tec['NumTec']) : '' ?>
                </div>
            </div>

            <div class="table-responsive table-scroll">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="nowrap" style="width:140px;">Fecha</th>
                            <th class="nowrap" style="width:130px;">Nº Reporte</th>
                            <th class="nowrap" style="width:130px;">Contrato</th>
                            <th class="nowrap" style="width:130px;">Estado</th>
                            <th>Comentario Cliente</th>
                            <th>Comentario Técnico</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($rows): ?>
                        <?php foreach ($rows as $r): 
                            $isCancel = ($r['Status'] === 'Cancelado');
                            $trClass  = $isCancel ? 'row-cancelado' : '';
                            $badge    = $isCancel
                                ? '<span class="badge bg-danger-subtle text-danger-emphasis badge-state">Cancelado</span>'
                                : '<span class="badge bg-success-subtle text-success-emphasis badge-state">Completado</span>';
                            $comentTec = trim((string)$r['MotivoTecnico']) !== '' ? $r['MotivoTecnico'] : '';
                        ?>
                            <tr class="<?= $trClass ?>">
                                <td class="nowrap"><?= esc(fmtFecha($r['Fecha'])) ?></td>
                                <td class="nowrap"><?= (int)$r['IDReporte'] ?></td>
                                <td class="nowrap"><?= esc($r['IDContrato']) ?></td>
                                <td class="nowrap"><?= $badge ?></td>
                                <td class="comment-cell">
                                    <div class="fw-normal"><?= nl2br(esc($r['Comentario'])) ?></div>
                                    <?php if ((int)$r['Rate'] >= 1): ?>
                                        <div class="text-body-secondary small mt-1">Calificación: <?= (int)$r['Rate'] ?>/5</div>
                                    <?php endif; ?>
                                </td>
                                <td class="comment-cell"><?= nl2br(esc($comentTec)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center text-body-secondary py-4">Sin resultados para los filtros seleccionados.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
