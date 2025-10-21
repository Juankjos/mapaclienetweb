// scripts/api/live-socket.js
// Cargar socket.io (ESM) desde CDN
import io from 'https://cdn.socket.io/4.7.5/socket.io.esm.min.js';

const getMap = () => window._leafletMap;

// Marcadores/capas
let carMarker = null;     // marcador del técnico
let destMarker = null;    // marcador del destino
let snappedLine = null;   // polyline del recorrido pegado a calle (OSRM)
let routeLine = null;     // polyline de ruta técnico -> destino (OSRM)

// Estado de tracking
let last = null;          // { lat, lng, yaw } suavizado para render
let target = null;        // { lat, lng, yaw } crudo recibido
let firstFixDone = false;
let panAccumulator = 0;

// Snap por calles (OSRM)
let prevRaw = null;                 // último punto crudo (sin snap)
const osrmQueue = [];               // cola de segmentos [A,B] a snapear
let osrmBusy = false;               // evita llamadas concurrentes
const OSRM_BASE = 'https://router.project-osrm.org';

// Destino
window.__destLive = null;           // { lat, lng }
window.__fitDoneOnce = false;       // encuadre tec+destino (una sola vez)

// Control de recálculo de ruta técnico→destino (debounce + distancia)
let lastRouteReqAt = 0;             // timestamp ms
let lastRouteFrom = null;           // {lat,lng} del último origen usado
const ROUTE_MIN_SECS = 3;           // no recalcular más frecuente que cada 3 s
const ROUTE_MIN_METERS = 30;        // ni por desplazamientos < 30 m

// --- Utilidades varias ---
function toRad(d){ return d * Math.PI / 180; }
function toDeg(r){ return r * 180 / Math.PI; }
function bearingBetween(a,b){
    const lat1=toRad(a.lat), lon1=toRad(a.lng);
    const lat2=toRad(b.lat), lon2=toRad(b.lng);
    const dLon = lon2 - lon1;
    let brng = Math.atan2(
        Math.sin(dLon) * Math.cos(lat2),
        Math.cos(lat1) * Math.sin(lat2) - Math.sin(lat1) * Math.cos(lat2) * Math.cos(dLon)
    );
    return (toDeg(brng) + 360) % 360;
}

function getMapOrThrow(){
    const m = getMap();
    if (!m) throw new Error('Leaflet map not ready');
    return m;
}

function distMeters(a, b){
    const m = getMap();
    if (!m) return Infinity;
    return m.distance([a.lat, a.lng], [b.lat, b.lng]);
}

// --- Cola OSRM: “snap” de A->B por la red vial ---
function enqueueSnap(a, b){
    const map = getMap();
    const d = map ? map.distance([a.lat,a.lng], [b.lat,b.lng]) : 0;
    // Evita saturar OSRM con micro-movimientos:
    if (!isFinite(d) || d < 10) return;
    osrmQueue.push([a, b]);
    processSnapQueue();
}

async function processSnapQueue(){
    if (osrmBusy || osrmQueue.length === 0) return;
    osrmBusy = true;

    const [a, b] = osrmQueue.shift();
    const url = `${OSRM_BASE}/route/v1/driving/${a.lng},${a.lat};${b.lng},${b.lat}?overview=full&geometries=geojson`;

    try {
        const res = await fetch(url, { cache: 'no-store' });
        if (!res.ok) throw new Error(`OSRM status ${res.status}`);
        const data = await res.json();
        const coords = data?.routes?.[0]?.geometry?.coordinates || [];

        if (coords.length){
        const pts = coords.map(([x,y]) => L.latLng(y, x));
        if (!snappedLine){
            snappedLine = L.polyline(pts, { weight: 4, opacity: 0.75, color: '#0B8FFF' })
            .addTo(window._layerCar || getMapOrThrow());
            snappedLine.bringToFront?.();
        } else {
            // Concatena evitando duplicar el primer punto del segmento
            const cur = snappedLine.getLatLngs();
            const toAppend = (cur.length && pts.length && cur[cur.length-1].equals(pts[0]))
            ? pts.slice(1) : pts;
            snappedLine.setLatLngs(cur.concat(toAppend));
            snappedLine.bringToFront?.();
        }
        } else {
        console.warn('[live] OSRM sin geometría (snap), fallback recto');
        fallbackStraight(a, b);
        }
    } catch (e){
        console.warn('[live] OSRM error (snap):', e);
        fallbackStraight(a, b);
    } finally {
        osrmBusy = false;
        if (osrmQueue.length) setTimeout(processSnapQueue, 400); // pausa entre llamadas
    }
}

function fallbackStraight(a, b){
    if (!snappedLine){
        snappedLine = L.polyline([ [a.lat,a.lng], [b.lat,b.lng] ], { weight:4, opacity:0.4, color:'#0B8FFF' })
        .addTo(window._layerCar || getMapOrThrow());
    } else {
        const cur = snappedLine.getLatLngs();
        if (!cur.length || !cur[cur.length-1].equals(L.latLng(a.lat, a.lng))) {
        cur.push(L.latLng(a.lat, a.lng));
        }
        cur.push(L.latLng(b.lat, b.lng));
        snappedLine.setLatLngs(cur);
    }
    snappedLine.bringToFront?.();
}

// --- Ruta técnico -> destino por calles (OSRM) ---
async function drawOsrmRouteToDest(from, to) {
    const m = getMap();
    if (!m || !from || !to) return;

    const url = `${OSRM_BASE}/route/v1/driving/${from.lng},${from.lat};${to.lng},${to.lat}?overview=full&geometries=geojson`;
    try {
        const res = await fetch(url, { cache: 'no-store' });
        if (!res.ok) throw new Error(`OSRM status ${res.status}`);
        const data = await res.json();
        const coords = data?.routes?.[0]?.geometry?.coordinates || [];
        if (!coords.length) {
        console.warn('[live] OSRM sin geometría (route to dest), fallback recto');
        return drawStraightRoute(from, to);
        }

        const pts = coords.map(([x,y]) => L.latLng(y,x));
        if (!routeLine) {
        routeLine = L.polyline(pts, { weight: 4, color: '#14452F', opacity: 0.9 })
            .addTo(window._layerCar || m);
        } else {
        routeLine.setLatLngs(pts);
        }
        routeLine.bringToFront?.();
    } catch (e) {
        console.warn('[live] OSRM error (route to dest), fallback recto:', e);
        drawStraightRoute(from, to);
    }
}

function drawStraightRoute(from, to){
    const m = getMap();
    if (!m || !from || !to) return;
    const pts = [L.latLng(from.lat, from.lng), L.latLng(to.lat, to.lng)];
    if (!routeLine) {
        routeLine = L.polyline(pts, { weight: 4, color: '#14452F', opacity: 0.7 })
        .addTo(window._layerCar || m);
    } else {
        routeLine.setLatLngs(pts);
    }
    routeLine.bringToFront?.();
}

// Decide si vale la pena recalcular la ruta técnico→destino
function maybeRecalcRoute(){
    if (!target || !window.__destLive) return;

    const now = Date.now();
    if (now - lastRouteReqAt < ROUTE_MIN_SECS * 1000) return;

    const from = { lat: target.lat, lng: target.lng };
    if (lastRouteFrom && distMeters(lastRouteFrom, from) < ROUTE_MIN_METERS) return;

    lastRouteReqAt = now;
    lastRouteFrom  = from;
    drawOsrmRouteToDest(from, window.__destLive);
}

// --- Render loop: suaviza, pinta marcador y sigue cámara ---
function render(){
    requestAnimationFrame(render);
    const map = getMap();
    if (!map || !target) return;

    // Inicializa last
    if (!last) last = { ...target };

    // Suavizado de posición (lerp)
    const alpha = 0.15;
    last.lat += (target.lat - last.lat) * alpha;
    last.lng += (target.lng - last.lng) * alpha;

    // Yaw (si server no manda, calc. con delta)
    const calcYaw = bearingBetween({ lat:last.lat, lng:last.lng }, target);
    last.yaw = 0.8 * (last.yaw ?? calcYaw) + 0.2 * (target.yaw ?? calcYaw);

    // Marcador técnico
    if (!carMarker) {
        carMarker = L.circleMarker([last.lat, last.lng], {
        radius: 6, weight: 2, color: '#0B8FFF', fillColor: '#0B8FFF', fillOpacity: 0.9
        }).addTo(window._layerCar || map);
    } else {
        carMarker.setLatLng([last.lat, last.lng]);
    }

    // Publica para overlay 3D y otras UIs
    window._liveTarget = { ...last }; // {lat,lng,yaw}
    window.dispatchEvent(new CustomEvent('live:position', { detail: { ...last } }));

    // OJO: ya NO pedimos ruta en cada frame. Lo decide maybeRecalcRoute()
    maybeRecalcRoute();

    // Auto-pan
    if (!firstFixDone) {
        firstFixDone = true;
        try { map.setView([last.lat, last.lng], Math.max(18, map.getZoom() || 18), { animate: false }); } catch (_) {}
    } else {
        panAccumulator += 1/60; // aprox (60fps)
        if (panAccumulator > 0.3) { // ≈ cada 0.3s
        panAccumulator = 0;
        try { map.panTo([target.lat, target.lng], { animate: true, duration: 0.3 }); } catch (_) {}
        }
    }
}
requestAnimationFrame(render);

// --- Socket ---
export function startLiveSocket() {
    const track = window.__TRACK__; // viene de mapa.php
    if (!track) return;

    const reportId = Number(track.IDReporte);
    const tecId    = track.NumTec ? Number(track.NumTec) : null;

    // Resetea estado por recargas
    last = null;
    target = null;
    firstFixDone = false;
    panAccumulator = 0;
    prevRaw = null;
    window.__destLive = null;
    window.__fitDoneOnce = false;
    lastRouteReqAt = 0;
    lastRouteFrom  = null;

    const socket = io('http://localhost:3001', {
        transports: ['websocket'],
        query: { reportId, tecId, role: 'client' },
        reconnection: true,
        reconnectionAttempts: Infinity,
        reconnectionDelay: 800,
        reconnectionDelayMax: 6000,
    });

    socket.on('connect', ()=> console.log('[live] connected, id=', socket.id));
    socket.on('connect_error', (err) => console.error('[live] connect_error:', err?.message || err, err));
    socket.on('error', (err) => console.error('[live] error:', err));

    // Posición live del técnico
    socket.on('location:live', (msg) => {
        const lat = Number(msg.lat), lng = Number(msg.lng);
        const yaw = isFinite(Number(msg.bearing)) ? Number(msg.bearing) : undefined;
        if (!isFinite(lat) || !isFinite(lng)) return;

        // Puntos crudos de GPS para snap
        const raw = { lat, lng };
        if (prevRaw) enqueueSnap(prevRaw, raw);  // encola segmento A->B
        prevRaw = raw;

        // Target para UI
        target = { lat, lng, yaw };

        // Fit técnico+destino una sola vez si ya hay ambos
        try {
        if (window.__destLive && !window.__fitDoneOnce && getMap()) {
            const bounds = L.latLngBounds(
            [target.lat, target.lng],
            [window.__destLive.lat, window.__destLive.lng]
            ).pad(0.15);
            getMap().fitBounds(bounds, { animate: true });
            window.__fitDoneOnce = true;
        }
        } catch(_) {}

        window._lastLoc = { lat, lng };
        // Intenta (debounced) recalcular la ruta al destino
        maybeRecalcRoute();
    });

    // Destino live enviado por Flutter
    socket.on('destination:live', (msg) => {
        const map = getMap();
        if (!map) return;

        const lat = Number(msg.lat), lng = Number(msg.lng);
        if (!isFinite(lat) || !isFinite(lng)) return;

        window.__destLive = { lat, lng };

        const ll = L.latLng(lat, lng);
        if (!destMarker) {
        destMarker = L.marker(ll, {
            icon: L.divIcon({
            html: '<div style="transform:translate(-50%,-100%);color:#c00;font-size:24px">📍</div>',
            className: 'dest-pin',
            iconSize: [24, 24],
            })
        }).addTo(window._layerCar || map);
        } else {
        destMarker.setLatLng(ll);
        }

        // Recalcular ruta si ya hay target
        if (target) maybeRecalcRoute();

        // Fit técnico+destino al recibir destino por primera vez (si ya hay target)
        try {
        if (target && !window.__fitDoneOnce) {
            const bounds = L.latLngBounds(
            [target.lat, target.lng],
            [window.__destLive.lat, window.__destLive.lng]
            ).pad(0.15);
            map.fitBounds(bounds, { animate: true });
            window.__fitDoneOnce = true;
        }
        } catch(_) {}
    });

    socket.on('disconnect', ()=> console.log('[live] disconnected'));

    // Expón para depuración
    window._liveSocket = socket;
}
