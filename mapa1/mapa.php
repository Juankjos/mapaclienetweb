<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Rastreo de  Técnico</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <link rel="stylesheet" href="styles/root.css" />
    <style></style>
</head>
<body>
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
        <!-- NUEVO: overlay de carga -->
        <div id="loadingMask" class="loading-mask">
            <div class="loading-msg">Cargando…</div>
        </div>
        <div class="toast" id="toast" style="display:none"></div>
        </div>
    </div>

    <!-- Modal: datos del cliente -->
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

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="scripts/app.js"></script>
    <script type="importmap">
    {
        "imports": {
        "three": "https://unpkg.com/three@0.159.0/build/three.module.js"
        }
    }
    </script>
    <script type="module" src="scripts/car-overlay.js"></script>
</body>
</html>