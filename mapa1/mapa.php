<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Rastreo de Técnico</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <link rel="stylesheet" href="styles/root.css" />
    <link rel="stylesheet" href="styles/ui.css" />
    <link rel="stylesheet" href="styles/modal.css" />
    <link rel="stylesheet" href="styles/mapa.css" />
    <link rel="stylesheet" href="styles/starrate.css" />
    <link rel="stylesheet" href="styles/offcanvas.css" />
    <link rel="stylesheet" href="styles/servicio.css" />
    <link rel="stylesheet" href="styles/leafpopup.css" />
    <link rel="stylesheet" href="styles/navbar.css" />
    <link rel="stylesheet" href="styles/tecnico.css" />
    <link rel="stylesheet" href="styles/tecnicopanel.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
    <body>
    <!-- NAVBAR SUPERIOR -->
    <nav class="navbar navbar-light bg-white shadow-sm app-navbar fixed-top">
        <div class="container-fluid">
        <div class="d-flex align-items-center">
            <!-- Botón hamburguesa que abre el menú lateral -->
            <button class="navbar-toggler me-2" type="button"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#menuLateral"
                    aria-controls="menuLateral"
                    aria-label="Abrir menú">
            <span class="navbar-toggler-icon"></span>
            </button>
            <img src="icon/icono.png" class="nav-tech-icon">
            <img src="icon/iconopride.png" id="iconopride">
        </div>
        </div>
    </nav>

    <!-- OFFCANVAS: MENÚ LATERAL -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="menuLateral" aria-labelledby="menuLateralLabel">
        <div class="offcanvas-body">
        <!-- PERFIL -->
        <div class="d-flex align-items-center gap-3 mb-3">
            <div class="rounded-circle bg-light border" style="width:48px;height:48px; display:flex;align-items:center;justify-content:center;">
            <i class="bi bi-person fs-4 text-secondary"></i>
            </div>
            <div>
            <div class="fw-semibold">Juan Carlos G. Medina</div>
            <a href="#" id="admin-cuenta" class="text-decoration-none small">Administrar cuenta</a>
            </div>
        </div>

        <!-- NUEVO MENÚ SIMPLE -->
        <div class="menu-simple d-flex flex-column gap-2 mt-3">
            <button type="button" class="menu-btn">
                <i class="bi bi-file-earmark-bar-graph-fill me-2"></i>
                Órdenes de Servicio
            </button>
            <button type="button" class="menu-btn">
                <i class="bi bi-person-raised-hand me-2"></i>
                Contacto a Soporte
            </button>
        </div>

        </div>
        <div class="offcanvas-footer mt-auto">
        <button type="button" class="btn-logout">
            <i class="bi bi-box-arrow-right me-2"></i>
            Cerrar Sesión
        </button>
    </div>
    </div>

    <div class="wrap">
        <div class="map">
        <!-- Basemap switcher (compact) -->
        <div class="ui-card below" id="grpBaseMaps">
            <div class="basemap-grid">
            <div class="basemap-card" data-base="sat">
                <div class="bm-thumb"
                style="background-image:url('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/3/2/2');"></div>
                <div class="bm-title">Satélite</div>
            </div>
            <div class="basemap-card active" data-base="light">
                <div class="bm-thumb"
                style="background-image:url('https://a.basemaps.cartocdn.com/rastertiles/voyager/12/1138/1657.png');"></div>
                <div class="bm-title">Claro HD</div>
            </div>
            <div class="basemap-card" data-base="dark">
                <div class="bm-thumb"
                style="background-image:url('https://a.basemaps.cartocdn.com/dark_all/3/2/2.png');"></div>
                <div class="bm-title">Oscuro</div>
            </div>
            </div>
        </div>

        <div id="map"></div>
        <div id="loadingMask" class="loading-mask">
            <div class="loading-msg">Cargando…</div>
        </div>
        <div class="toast" id="toast" style="display:none"></div>

        <!-- SIDEBAR TÉCNICO FIJO (ya no offcanvas) -->
        <aside id="panelTecnico" class="tecnico-panel">
        <div class="tecnico-header d-flex align-items-center justify-content-between">
            <h5 class="mb-0">Detalles de tu servicio</h5>
        </div>
        <div class="tecnico-content">

        <section aria-labelledby="arrival-title" class="arrival-info">
        <header class="section-sticky">
            <h3 id="arrival-title" class="section-title mb-1">Hora de llegada</h3>
        </header>

        <div class="arrival-card">
            <div class="arrival-estimada mb-2">
            <strong>Llegada estimada:</strong> 2:20 PM
            </div>

            <!-- Barra de progreso -->
            <div class="progress mb-2" style="height: 8px;">
            <div class="progress-bar bg-success" role="progressbar"
                style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
            </div>
            </div>

            <div class="arrival-limite text-muted">
            Llegada a más tardar <strong>2:40 PM</strong>
            </div>
        </div>
        </section>

        <section aria-labelledby="tec-title" class="tec-info">
            <header class="section-sticky">
                <h3 id="tec-title" class="section-title mb-1">Técnico</h3>
            </header>

            <div class="tec-card d-flex align-items-center justify-content-between gap-3">
                <div class="flex-grow-1">
                <!-- Solo nombre y solo número (opcional: elimina la frase) -->
                <div id="tec-name" class="tec-name">Salvador Enríque Bustamante</div>
                <div id="tec-num" class="tec-number">Contáctate al número 378 711 4606</div>
                </div>

                <div class="rounded-circle bg-light border profile-avatar"
                    style="width:48px;height:48px; display:flex; align-items:center; justify-content:center;">
                <i class="bi bi-person fs-4 text-secondary"></i>
                </div>
            </div>

            <div class="mt-3 d-flex align-items-center gap-2" id="input-div">
                <input type="text" class="input-mensaje" placeholder="Envía un mensaje al Técnico">
                <button type="button" class="btn-enviar">
                <i class="bi bi-send-fill"></i>
                </button>
            </div>
        </section>

        <!-- Servicio (sticky) -->
        <section aria-labelledby="svc-title">
            <header class="section-sticky">
            <h3 id="svc-title" class="section-title mb-1">Servicio</h3>
            </header>

            <div class="servicio-card">
            <!-- Contrato -->
            <div class="servicio-item d-flex align-items-start gap-3">
                <i class="bi bi-clipboard2-data servicio-icon"></i>
                <div>
                <div class="servicio-label">Contrato</div>
                <div class="servicio-value">123123-3</div>
                </div>
            </div>

            <!-- Orden de Servicio -->
            <div class="servicio-item d-flex align-items-start gap-3">
                <i class="bi bi-file-earmark-bar-graph-fill servicio-icon"></i>
                <div>
                <div class="servicio-label">Orden de Servicio</div>
                <div class="servicio-value">Cambio de Tecnología</div>
                </div>
            </div>

            <!-- Técnico -->
            <div class="servicio-item d-flex align-items-start gap-3">
                <i class="bi bi-tools servicio-icon"></i>
                <div>
                <div class="servicio-label">Técnico</div>
                <div class="servicio-value">Salvador Enríque Bustamante</div>
                </div>
            </div>

            <!-- Dirigiéndose a -->
            <div class="servicio-item d-flex align-items-start gap-3">
                <i class="bi bi-geo-alt servicio-icon"></i>
                <div>
                <div class="servicio-label">Dirigiéndose a</div>
                <div class="servicio-value">
                    C. González Hermosillo 191, San Antonio El Alto, 47640 Tepatitlán de Morelos, Jal.
                </div>
                </div>
            </div>
            </div>
        </section>
        </div>
        </aside>

        </div>
    </div>

    <!-- Modal: datos del cliente (se mantiene por si lo usas en el mapa) -->
    <div id="clientModal" class="modal" aria-hidden="true">
        <div class="modal-card">
        <h4 id="cm-title">Datos del cliente</h4>
        <div class="modal-row" id="cm-sub">—</div>
        <div class="modal-row" id="cm-problema"></div>
        <div class="modal-row" id="cm-creacion"></div>
        <div class="modal-row" id="cm-ejecucion"></div>
        <div class="modal-row" id="cm-solucion"></div>
        <div class="modal-actions">
            <button id="cm-close">Cerrar</button>
        </div>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous"></script>
    <script type="module" src="scripts/main.js"></script>
    <script type="importmap">
        {
        "imports": {
            "three": "https://unpkg.com/three@0.159.0/build/three.module.js"
        }
        }
    </script>
    <script type="module" src="scripts/ui/car-overlay.js"></script>
    </body>
</html>