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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
            integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>

    </style>
    </head>
    <body>
    <div class="wrap">
        <div class="map">

        <button class="btn btn-primary offcanvas-toggle-btn"
                id="offcanvas-tecnico"
                type="button"
                data-bs-toggle="offcanvas"
                data-bs-target="#panelServicio"
                aria-controls="panelServicio">
                <i class="bi bi-tools"></i>
            Tú Técnico
        </button>

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

    <!-- OFFCANVAS DERECHA con pestañas -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="panelServicio" aria-labelledby="panelServicioLabel">
        <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="panelServicioLabel">Detalles de Técnico</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
        </div>

        <div class="offcanvas-body p-0 d-flex flex-column">

        <!-- Tabs -->
        <ul class="nav nav-tabs px-3" id="tabsServicio" role="tablist">
            <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-servicio" data-bs-toggle="tab"
                    data-bs-target="#pane-servicio" type="button" role="tab"
                    aria-controls="pane-servicio" aria-selected="true">
                Servicio
            </button>
            </li>
            <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-comentarios" data-bs-toggle="tab"
                    data-bs-target="#pane-comentarios" type="button" role="tab"
                    aria-controls="pane-comentarios" aria-selected="false">
                Evaluación
            </button>
            </li>
        </ul>

        <!-- Contenido de pestañas -->
        <div class="tab-content flex-grow-1 p-3" id="tabsServicioContent">
            <!-- PESTAÑA: Servicio -->
            <div class="tab-pane fade show active" id="pane-servicio" role="tabpanel" aria-labelledby="tab-servicio">
                <div class="servicio-card">
                    <div class="servicio-item">
                        <div class="servicio-label">Contrato</div>
                        <div class="servicio-value">123123-3</div>
                    </div>
                    <div class="servicio-item">
                        <div class="servicio-label">Orden de Servicio</div>
                        <div class="servicio-value">Cambio de Tecnología</div>
                    </div>
                    <div class="servicio-item">
                        <div class="servicio-label">Técnico</div>
                        <div class="servicio-value">Salvador Enríque Bustamante</div>
                    </div>
                    <div class="servicio-item">
                        <div class="servicio-label">Dirigiéndose a</div>
                        <div class="servicio-value">C. González Hermosillo 191, San Antonio El Alto, 47640 Tepatitlán de Morelos, Jal.</div>
                    </div>
                </div>
            </div>

            <!-- PESTAÑA: Comentarios -->
            <div class="tab-pane fade" id="pane-comentarios" role="tabpanel" aria-labelledby="tab-comentarios">
            <div class="mb-3">
                <label for="svc-comentarios" class="form-label">Agregar comentario</label>
                <textarea class="form-control" id="svc-comentarios" rows="5" placeholder="Escribe aquí…"></textarea>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary" id="btnLimpiarComentario">Limpiar</button>
                <button class="btn btn-primary" id="btnGuardarComentario">Guardar</button>
            </div>
            <hr>
            <div id="listaComentarios" class="vstack gap-2">
                <!-- Aquí puedes renderizar comentarios previos -->
            </div>
            </div>
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
    <script>
        // Rellenar campos de ejemplo cuando abras desde algún evento
        function abrirPanelConDatos(datos = {}) {
        document.getElementById('svc-folio').value     = datos.folio ?? '—';
        document.getElementById('svc-cliente').value   = datos.cliente ?? '—';
        document.getElementById('svc-direccion').value = datos.direccion ?? '—';
        document.getElementById('svc-creacion').value  = datos.creacion ?? '—';
        document.getElementById('svc-ejecucion').value = datos.ejecucion ?? '—';

        const offcanvasEl = document.getElementById('panelServicio');
        const panel = bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl);
        panel.show();

        // Si quieres forzar abrir en la pestaña "Servicio"
        const tabTrigger = document.querySelector('#tab-servicio');
        bootstrap.Tab.getOrCreateInstance(tabTrigger).show();
        }

        // Botones simples para comentarios
        document.getElementById('btnLimpiarComentario')?.addEventListener('click', () => {
        document.getElementById('svc-comentarios').value = '';
        });
        document.getElementById('btnGuardarComentario')?.addEventListener('click', () => {
        const txt = document.getElementById('svc-comentarios').value.trim();
        if (!txt) return;
        const item = document.createElement('div');
        item.className = 'border rounded p-2';
        item.textContent = txt;
        document.getElementById('listaComentarios').prepend(item);
        document.getElementById('svc-comentarios').value = '';
        });
    </script>
    </body>
</html>