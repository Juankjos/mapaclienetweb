<?php
session_start();
require_once 'db.php';
require_once 'auth.php';
gate_mesa_only_these();

// Proteger ruta
if (empty($_SESSION['contrato'])) {
    header('Location: login.php');
    exit;
}
$contrato = $_SESSION['contrato'];

// Traer TODAS las órdenes del contrato con joins
$sql = "
SELECT 
    r.IDReporte,
    r.IDContrato,
    r.Problema,
    r.FechaAgendado,
    p.IDTec,
    p.Status,
    p.FechaInicio,
    p.FechaFin,
    p.Rate,
    p.Comentario,
    t.NombreTec,
    u.Direccion,
    u.Nombre AS NombreCliente
FROM reportes r
INNER JOIN usuarios   u ON u.IDContrato = r.IDContrato
LEFT  JOIN produccion p ON p.IDReporte  = r.IDReporte AND p.IDContrato = r.IDContrato
LEFT  JOIN tecnicos   t ON t.IdTec      = p.IDTec
WHERE r.IDContrato = ?
    AND p.Status IN ('En camino','Completado','Cancelado')
ORDER BY COALESCE(r.FechaAgendado, '1000-01-01') DESC, r.IDReporte DESC
";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('s', $contrato);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($row = $res->fetch_assoc()) $rows[] = $row;
$stmt->close();

$nombreSesion = !empty($_SESSION['nombre']) ? $_SESSION['nombre'] : ($rows[0]['NombreCliente'] ?? 'Cliente');
?>

<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Órdenes de Servicio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/map/navbar.css" />
    <link rel="stylesheet" href="styles/map/root.css" />
    <link rel="stylesheet" href="styles/map/offcanvas.css" />
    <link rel="stylesheet" href="styles/ordenes/ordenes.css" />
    <style>
        .order-card { border: 1px solid #eee; border-radius: 12px; background: #fff; }
        .image-thumb { width: 96px; height: 96px; object-fit: cover; }
        .info-block { margin-right: 2rem; margin-bottom: .5rem; }
        .pager button { min-width: 40px; }
    </style>
    </head>
    <body>

    <nav class="navbar navbar-light bg-white shadow-sm app-navbar fixed-top" aria-label="Barra de navegación">
        <div class="container-fluid">
        <div class="d-flex align-items-center gap-2">
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas"
                    data-bs-target="#menuLateral" aria-controls="menuLateral" aria-label="Abrir menú">
            <span class="navbar-toggler-icon"></span>
            </button>
            <img src="icon/icono.png" class="nav-tech-icon" alt="Logo" />
            <img src="icon/iconopride.png" id="iconopride" alt="Variación de logo" />
        </div>
        </div>
    </nav>

    <div class="offcanvas offcanvas-start" tabindex="-1" id="menuLateral" aria-labelledby="menuLateralLabel">
        <div class="offcanvas-body">
        <!-- PERFIL -->
        <div class="d-flex align-items-center gap-3 mb-3">
            <div class="rounded-circle bg-light border" style="width:48px;height:48px; display:flex;align-items:center;justify-content:center;">
            <i class="bi bi-person fs-4 text-secondary"></i>
            </div>
            <div>
            <div id="Nombre" class="fw-semibold">...</div>
            <a href="administrar_cuenta.php" id="admin-cuenta" class="text-decoration-none small">Administrar cuenta</a>
            </div>
        </div>

        <!-- Menú -->
        <div class="menu-simple d-flex flex-column gap-2 mt-3">
            <button type="button" class="menu-btn" onclick="window.location.href='ordenes_servicio.php'">
            <i class="bi bi-file-earmark-bar-graph-fill me-2"></i>
            Órdenes de Servicio
            </button>
            <button type="button" class="menu-btn">
            <i class="bi bi-person-raised-hand me-2"></i>
            Contacto a Soporte
            </button>
        </div>

        <!-- NUEVO: Botón Seguir técnico -->
        <div class="mt-4">
            <button id="btnFollowTech" type="button" class="btn btn-secondary w-100" disabled>
            <i class="bi bi-geo-alt-fill me-2"></i>
            Sigue a tu técnico
            </button>
            <div id="followHelp" class="form-text">
            Disponible cuando tu orden esté <strong>En camino</strong>.
            </div>
        </div>

        </div>
        <div class="offcanvas-footer mt-auto">
            <form id="logoutForm" action="logout.php" method="post">
                <button id="btnLogout" type="submit" class="btn-logout">
                <i class="bi bi-box-arrow-right me-2"></i>
                Cerrar Sesión
                </button>
            </form>
        </div>
    </div>

    <main class="container" style="margin-top: 5rem;">
    <!-- TÍTULO -->
        <h4 class="orders-title mb-2">Órdenes de Servicio</h4>

        <!-- FILTROS DE BÚSQUEDA (debajo del título) -->
        <div class="mb-3">
            <div class="row g-2">
            <div class="col-12 col-md-6 col-lg-5">
                <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input id="q" type="search" class="form-control" placeholder="Buscar por problema, técnico o dirección...">
                </div>
            </div>

            <div class="col-6 col-md-3 col-lg-3">
                <div class="input-group">
                <span class="input-group-text"><i class="bi bi-funnel"></i></span>
                <select id="fStatus" class="form-select">
                    <option value="">Todos los estados</option>
                    <option value="En camino">En camino</option>
                    <option value="Completado">Completado</option>
                    <option value="Cancelado">Cancelado</option>
                </select>
                </div>
            </div>

            <div class="col-6 col-md-3 col-lg-2">
                <div class="input-group">
                <span class="input-group-text"><i class="bi bi-list-ol"></i></span>
                <select id="pageSize" class="form-select">
                    <option value="5">5 por página</option>
                    <option value="10" selected>10 por página</option>
                    <option value="20">20 por página</option>
                </select>
                </div>
            </div>
            </div>
        </div>

        <!-- LISTA DE TARJETAS -->
        <div id="cards" class="vstack gap-3"></div>

        <!-- PAGINADOR -->
        <div class="d-flex align-items-center justify-content-between mt-3">
            <div id="resultsInfo" class="small text-muted"></div>
            <div class="pager btn-group">
            <button id="prevBtn" class="btn btn-outline-secondary" type="button">&laquo;</button>
            <button id="pageInfo" class="btn btn-outline-secondary disabled" type="button">1 / 1</button>
            <button id="nextBtn" class="btn btn-outline-secondary" type="button">&raquo;</button>
            </div>
        </div>
    </main>

    <!-- Datos embebidos -->
    <script>
        window.__ORDENES__ = <?php
        $payload = [
            'Contrato' => $contrato,
            'Nombre'   => $nombreSesion,
            'items'    => array_map(function($r){
            return [
                'IDReporte'     => (int)$r['IDReporte'],
                'Problema'      => $r['Problema'],
                'FechaAgendado' => $r['FechaAgendado'],
                'IDTec'         => isset($r['IDTec']) ? (int)$r['IDTec'] : null,
                'NombreTec'     => $r['NombreTec'],
                'Direccion'     => $r['Direccion'],
                'Status'        => $r['Status'] ?? null,
                'Rate'          => isset($r['Rate']) ? (int)$r['Rate'] : null,
                'Comentario'    => $r['Comentario'] ?? null
            ];
            }, $rows)
        ];
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        ?>;
    </script>
    <script>const FOLLOW_TECH_URL_BASE = '/mapaclienteweb/mapa1/mapa.php';</script>
    <script type="module" src="scripts/api/ordenes.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    </body>
</html>
