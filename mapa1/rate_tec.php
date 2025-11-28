<?php
session_start();
require_once 'db.php';
require_once 'auth.php';
gate_only_mesa_for_these();
gate_mesa_only_these();

// Requiere login
if (empty($_SESSION['contrato'])) {
    header('Location: login.php'); exit;
}

$sql = "
    SELECT
        t.IdTec,
        t.NombreTec,
        t.NumTec,

        /* Reportes Totales: solo Completado o Cancelado */
        (SELECT COUNT(*)
            FROM produccion p_all
            WHERE p_all.IDTec = t.IdTec
            AND p_all.Status IN ('Completado','Cancelado')
        ) AS total_reportes,

        /* Conteo de ratings válidos (1–5) SOLO Completado */
        (SELECT COUNT(*)
            FROM produccion p_cnt
            WHERE p_cnt.IDTec = t.IdTec
            AND p_cnt.Status = 'Completado'
            AND p_cnt.Rate BETWEEN 1 AND 5
        ) AS total_ratings,

        /* Promedio de calificación SOLO Completado */
        (SELECT AVG(NULLIF(p_avg.Rate, 0))
            FROM produccion p_avg
            WHERE p_avg.IDTec = t.IdTec
            AND p_avg.Status = 'Completado'
            AND p_avg.Rate BETWEEN 1 AND 5
        ) AS avg_rate

    FROM tecnicos t
    ORDER BY
        ( (SELECT AVG(NULLIF(p_avg2.Rate, 0))
            FROM produccion p_avg2
            WHERE p_avg2.IDTec = t.IdTec
            AND p_avg2.Status = 'Completado'
            AND p_avg2.Rate BETWEEN 1 AND 5
        ) IS NULL ),
        (SELECT AVG(NULLIF(p_avg3.Rate, 0))
            FROM produccion p_avg3
            WHERE p_avg3.IDTec = t.IdTec
            AND p_avg3.Status = 'Completado'
            AND p_avg3.Rate BETWEEN 1 AND 5
        ) DESC,
        t.NombreTec ASC
";

$res = $mysqli->query($sql);
$tecnicos = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
?>
<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Calificaciones de Técnicos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="icon/Tvclogo.png">
    <link rel="stylesheet" href="styles/map/navbar.css" />
    <link rel="stylesheet" href="styles/map/root.css" />
    <link rel="stylesheet" href="styles/map/offcanvas.css" />
    <link rel="stylesheet" href="styles/rate/rate_tec.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
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
                <div class="fw-semibold" id="Nombre">
                <?= htmlspecialchars($payload['Nombre'] ?? ($_SESSION['nombre'] ?? 'Usuario'), ENT_QUOTES, 'UTF-8') ?>
                </div>
                <a href="administrar_cuenta.php" id="admin-cuenta" class="text-decoration-none small">Administrar cuenta</a>
            </div>
            </div>

            <?php $esMesa = is_mesa(); ?>
            <div class="menu-simple d-flex flex-column gap-2 mt-3">
            <?php if ($esMesa): ?>
                <button type="button" class="menu-btn" onclick="window.location.href='rate_tec.php'">
                <i class="bi bi-star-half me-2"></i>
                Calificaciones
                </button>
                <button type="button" class="menu-btn" onclick="window.location.href='registro_tecnico.php'">
                <i class="bi bi-pencil me-2"></i>
                Registro y Modificación de Técnico
                </button>
                <!-- Sin Órdenes de Servicio ni Contacto a Soporte para Mesa -->
            <?php else: ?>
                <button type="button" class="menu-btn" onclick="window.location.href='ordenes_servicio.php'">
                <i class="bi bi-file-earmark-bar-graph-fill me-2"></i>
                Órdenes de Servicio
                </button>
                <button type="button" class="menu-btn" onclick="window.location.href='contacto_soporte.php'">
                <i class="bi bi-person-raised-hand me-2"></i>
                Contacto a Soporte
                </button>
            <?php endif; ?>
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

    <main class="container py-4" style="margin-top:72px;">
    <section aria-labelledby="rate-title">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h4 id="rate-title" class="mb-0">Técnicos</h4>
                <div class="col-12 col-md-8">
                    <input id="filtrar" type="search" class="form-control form-control-sm search-input" placeholder="Buscar por Nombre, ID o teléfono...">
                </div>
            <div class="d-flex gap-2 align-items-center">
                <a id="btn-register-tec" class="btn btn-sm btn-primary" href="registro_tecnico.php">
                    <i class="bi bi-pencil me-1"></i> Registro y Modificación de técnico
                </a>
            </div>
        </div>

        <div class="table-responsive">
        <table id="tabla-tecs" class="table table-hover align-middle">
            <thead class="table-light">
            <tr>
                <th>Nombre</th>
                <th style="width:220px;">Rating</th>
                <th style="width:160px;">Reportes Totales</th>
                <th style="width:140px;">Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($tecnicos as $t): 
                $avg    = is_null($t['avg_rate']) ? null : round((float)$t['avg_rate'], 1);
                $total  = (int)$t['total_reportes'];
                $nombre = $t['NombreTec'] ?: 'Técnico';
                $filled = $avg ? floor($avg) : 0;
                $half   = 0;
                if ($avg) {
                    $frac = $avg - $filled;
                    if ($frac >= 0.75) { $filled++; }
                    elseif ($frac >= 0.25) { $half = 1; }
                }
                $empty = 5 - $filled - $half;

                // Color de fila según promedio
                $rowClass = '';
                if ($avg !== null && $avg > 0) {
                    if ($avg >= 4.0)      $rowClass = 'table-warning';   // amarillo (>= 4.0)
                    elseif ($avg >= 3.0)  $rowClass = 'table-success';   // verde suave (3.0–3.9)
                    elseif ($avg >= 2.0)  $rowClass = 'table-secondary'; // gris (2.0–2.9)
                    else                  $rowClass = 'table-danger';    // rojo (1.0–1.9)
                }
            ?>
            <tr class="<?= $rowClass ?>"
                data-nombre="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>"
                data-id="<?= (int)$t['IdTec'] ?>"
                data-num="<?= htmlspecialchars($t['NumTec'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            >
            <!-- Nombre -->
            <td class="tec-nombre">
                <div class="fw-semibold"><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="text-body-secondary small">
                ID: <?= (int)$t['IdTec'] ?><?= $t['NumTec'] ? ' · #'.htmlspecialchars($t['NumTec'], ENT_QUOTES, 'UTF-8') : '' ?>
                </div>
            </td>

            <!-- Rating -->
            <td>
                <?php if ($avg): ?>
                <div class="d-flex align-items-center gap-2" aria-label="Promedio <?= number_format($avg,1) ?> de 5">
                    <div class="star text-warning">
                    <?php for ($i=0; $i<$filled; $i++): ?><i class="bi bi-star-fill"></i><?php endfor; ?>
                    <?php if ($half): ?><i class="bi bi-star-half"></i><?php endif; ?>
                    <?php for ($i=0; $i<$empty; $i++): ?><i class="bi bi-star"></i><?php endfor; ?>
                    </div>
                    <span class="rate-badge badge text-bg-light"><?= number_format($avg,1) ?></span>
                </div>
                <?php else: ?>
                <span class="text-body-secondary">Sin calificaciones</span>
                <?php endif; ?>
            </td>

            <!-- Reportes Totales -->
            <td class="total-cell">
                <span class="total-number"><?= $total ?></span>
            </td>

            <!-- Acciones -->
            <td>
                <a class="btn btn-sm btn-outline-primary" href="comentarios_tec.php?tec=<?= (int)$t['IdTec'] ?>">
                Comentarios
                </a>
            </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($tecnicos)): ?>
            <tr><td colspan="3" class="text-center text-body-secondary py-4">No hay técnicos.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="module" src="scripts/api/rate_tec.js"></script>

</body>
</html>
