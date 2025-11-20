// scripts/api/live-socket.js
// Cargar socket.io (ESM) desde CDN
import io from 'https://cdn.socket.io/4.7.5/socket.io.esm.min.js';
window.showEvalPrompt   = window.showEvalPrompt   || (() => {});
window.showCanceledPrompt = window.showCanceledPrompt || (() => {});
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

// --- CHAT UI helpers ---
let chatListEl = null;
let chatBoxEl  = null;
let chatInput  = null;
let chatSendBtn = null;
let chatReportId = null;

// Flag global por defecto (sí seguir)
window.__followTec = true;

function updateFollowButtonsUI(){
    const bUnf = document.getElementById('btnUnfollow');
    const bFol = document.getElementById('btnFollow');
    const bHome = document.getElementById('btnHome'); // Mi domicilio

    if (bUnf && bFol) {
        if (window.__followTec) {
            bUnf.style.display = '';
            bFol.style.display = 'none';
        } else {
            bUnf.style.display = 'none';
            bFol.style.display = '';
        }
    }
    if (bHome) {
        bHome.disabled = !!window.__followTec; // deshabilitado si seguimos al técnico
    }
}


document.addEventListener('DOMContentLoaded', () => {
    const bUnf = document.getElementById('btnUnfollow');
    const bFol = document.getElementById('btnFollow');

    if (bUnf) bUnf.addEventListener('click', () => {
        window.__followTec = false;
        // intenta enfocar el destino cuando deje de seguir
        if (typeof window.focusDestination === 'function') {
            window.focusDestination(/*preferBounds=*/true);
        }
        updateFollowButtonsUI();
    });

    if (bFol) bFol.addEventListener('click', () => {
        window.__followTec = true;
        // centra inmediatamente al técnico si tenemos última posición
        if (typeof window.focusTechnician === 'function') {
            window.focusTechnician();
        }
        updateFollowButtonsUI();
    });

    updateFollowButtonsUI();
});

// Helpers globales para botones
window.focusTechnician = function(){
    try {
        const map = getMap();                // ✅ obtener mapa aquí
        const live = window._liveTarget;
        if (!map || !live) return;
        const { lat, lng } = live;
        map.setView([lat, lng], Math.max(18, map.getZoom() || 18), { animate: true });
    } catch(_) {}
};



window.focusDestination = async function(preferBounds = true){
    try {
        const map  = getMap();               // ✅ obtener mapa aquí
        if (!map) return;

        // si aún no ha llegado el destino, espera un momento
        const dest = window.__destLive || await waitForDestination();
        const live = window._liveTarget;

        if (!dest) {
            // feedback opcional
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                icon: 'info',
                title: 'Sin destino aún',
                text: 'Todavía no tenemos el pin de tu domicilio. Intenta de nuevo en unos segundos.',
                confirmButtonText: 'OK'
                });
            }
        return;
        }

        if (preferBounds && live) {
            const bounds = L.latLngBounds([live.lat, live.lng], [dest.lat, dest.lng]).pad(0.15);
            map.fitBounds(bounds, { animate: true });
        } else {
            map.setView([dest.lat, dest.lng], Math.max(17, map.getZoom() || 17), { animate: true });
        }

        // halo visual opcional para “Mi domicilio”
        try {
        const halo = L.circle([dest.lat, dest.lng], {
            radius: 30, color: '#3388ff', weight: 2, opacity: 0.8, fillOpacity: 0.15
        }).addTo(map);
        setTimeout(() => { try { map.removeLayer(halo); } catch(_){} }, 1200);
        } catch(_) {}
    } catch(_) {}
};

function formatTime(ts){
    try {
        const d = new Date(Number(ts) || Date.now());
        const hh = String(d.getHours()).padStart(2,'0');
        const mm = String(d.getMinutes()).padStart(2,'0');
        return `${hh}:${mm}`;
    } catch { return ''; }
}

function appendChatBubble({ text, from, ts }, myRole){
    if (!chatListEl) return;
    const isMe = (from === myRole);
    const div = document.createElement('div');
    div.className = `chat-msg ${isMe ? 'me' : 'other'}`;
    div.innerHTML = `${escapeHtml(text)}<span class="chat-time">${formatTime(ts)}</span>`;
    chatListEl.appendChild(div);
    // autoscroll
    if (chatBoxEl) chatBoxEl.scrollTop = chatBoxEl.scrollHeight;
}

function clearChat(){
    if (chatListEl) chatListEl.innerHTML = '';
}

function escapeHtml(s){
    return String(s || '')
    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

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

function clamp(n, min, max){ return Math.max(min, Math.min(max, n)); }
function haversineKm(lat1,lon1,lat2,lon2){
    const R=6371;
    const dLat=(lat2-lat1)*Math.PI/180;
    const dLon=(lon2-lon1)*Math.PI/180;
    const a=Math.sin(dLat/2)**2 +
            Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180) *
            Math.sin(dLon/2)**2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}

function updateProgressBar(){
    try{
        const bar = document.getElementById('routeProgressBar');
        if (!bar || !window.__destLive || !target) return;

        // totalKm: distancia total por calles (OSRM) si la tenemos;
        // si no, inicialízala como la distancia en línea recta del primer cálculo.
        if (typeof window.__totalKm !== 'number') {
        window.__totalKm = haversineKm(target.lat, target.lng, window.__destLive.lat, window.__destLive.lng);
        }

        const remainKm = haversineKm(target.lat, target.lng, window.__destLive.lat, window.__destLive.lng);
        const totalKm  = window.__totalKm || remainKm || 1e-6;

        let p = (totalKm - remainKm) / totalKm;
        p = clamp(p, 0, 1);
        const pct = Math.round(p * 100);

        bar.style.width = pct + '%';
        bar.setAttribute('aria-valuenow', String(pct));

        bar.classList.remove('bg-danger','bg-warning','bg-info','bg-success');
        if (p < 0.25) bar.classList.add('bg-danger');
        else if (p < 0.50) bar.classList.add('bg-info');
        else if (p < 0.75) bar.classList.add('bg-success');
        else bar.classList.add('bg-success');
    }catch(e){ /* no-op */ }
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
        const distMeters = data?.routes?.[0]?.distance;
        if (typeof distMeters === 'number' && isFinite(distMeters) && distMeters > 1) {
            const km = distMeters / 1000;
            // Solo setear si no existía, para mantener coherencia del “total”
            if (typeof window.__totalKm !== 'number' || window.__totalKm < 0.2) {
                window.__totalKm = km;
            }
        }

        const pts = coords.map(([x,y]) => L.latLng(y,x));
        // if (!routeLine) {
        // routeLine = L.polyline(pts, { weight: 4, color: '#14452F', opacity: 0.9 })
        //     .addTo(window._layerCar || m);
        // } else {
        // routeLine.setLatLngs(pts);
        // }
        updateProgressBar();
        routeLine.bringToFront?.();
    } catch (e) {
        console.warn('[live] OSRM error (route to dest), fallback recto:', e);
        drawStraightRoute(from, to);
        updateProgressBar();
    }
}

function drawStraightRoute(from, to){
    const m = getMap();
    if (!m || !from || !to) return;
    const pts = [L.latLng(from.lat, from.lng), L.latLng(to.lat, to.lng)];
    // if (!routeLine) {
    //     routeLine = L.polyline(pts, { weight: 4, color: '#14452F', opacity: 0.7 })
    //     .addTo(window._layerCar || m);
    // } else {
    //     routeLine.setLatLngs(pts);
    // }
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
} else if (window.__followTec) {
  panAccumulator += 1/60;
  if (panAccumulator > 0.3) {
    panAccumulator = 0;
    try { map.panTo([target.lat, target.lng], { animate: true, duration: 0.3 }); } catch (_) {}
  }
}
}
requestAnimationFrame(render);

function resolveSocketBase() {
    // Si la página viene del propio host (PC o teléfono), intenta mismo host+3001
    const h = window.location.hostname; // '127.0.0.1', 'localhost', '192.168.x.y', dominio, etc.
    // Caso A: pruebas con ADB reverse desde el TELÉFONO
    if (h === '127.0.0.1' || h === 'localhost') return `${window.location.protocol}//${h}:3001`;
    // Caso B: página cargada desde la LAN o dominio
    //   - si tu Node escucha en la misma máquina que sirve PHP: usa ese host:3001
    return `${window.location.protocol}//${h}:3001`;
    // Si tu Socket.IO vive en otro host, expón window.__SOCKET_HOST__ y devuélvelo aquí.
}
// --- Socket ---
export function startLiveSocket() {
    const track = window.__TRACK__; // viene de mapa.php
    if (!track) return;
console.log('[live] TRACK =', track);
console.log('[live] track.IDTec =', track.IDTec);

    const reportId = Number(track.IDReporte);
    const tecId    = track.IDTec ? Number(track.IDTec) : null;

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

    const socket = io(resolveSocketBase(), {
        transports: ['websocket'],
        query: { reportId, tecId, role: 'client' },
        reconnection: true,
        reconnectionAttempts: Infinity,
        reconnectionDelay: 800,
        reconnectionDelayMax: 6000,
    });

    chatListEl  = document.getElementById('chatList');
    chatBoxEl   = document.getElementById('chatBox');
    chatInput   = document.getElementById('chatInput');
    chatSendBtn = document.getElementById('chatSendBtn');
    chatReportId = reportId;

    if (chatInput && chatSendBtn) {
        chatInput.disabled  = true;
        chatSendBtn.disabled = true;
    }

    socket.on('status:live', (msg) => {
    try {
        const d = window.__TRACK__ || {};
        if (Number(msg.reportId) !== Number(d.IDReporte)) return;

        // evita duplicar con el polling
        if (window.__statusPromptShown) return;

        const status = msg.status;
        const rate   = Number(msg.rate || 0);

        if (status === 'Cancelado') {
            window.__statusPromptShown = true;
            if (typeof window.showCanceledPrompt === 'function') {
            window.showCanceledPrompt();
            }
            return;
        }

        if (status === 'Completado' && rate === 0) {
            window.__statusPromptShown = true;
            if (typeof window.showEvalPrompt === 'function') {
            window.showEvalPrompt({ ...d, Status: status, Rate: rate });
            }
            return;
        }
        } catch (e) { console.warn('[ws] status:live error', e); }
    });

    socket.on('connect', () => {
        console.log('[live] connected, id=', socket.id);
        // Habilita input al conectar
        if (chatInput && chatSendBtn) {
            chatInput.disabled  = false;
            chatSendBtn.disabled = false;
            chatInput.placeholder = 'Envía un mensaje al Técnico';
        }
        // Pide historial (últimos 50)
        socket.emit('chat:history:get', { limit: 50 });
    });
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
        if (!map) {
            console.warn('[live] destination:live sin mapa aún', msg);
            return;
        }

        const lat = Number(msg.lat), lng = Number(msg.lng);
        console.log('[live] destination:live coords =', lat, lng, 'raw msg=', msg);

        if (!isFinite(lat) || !isFinite(lng)) {
            console.warn('[live] destination:live coords inválidas', msg);
            return;
        }

        // Guarda destino global
        window.__destLive = { lat, lng };

        const ll = L.latLng(lat, lng);

        if (!destMarker) {
            // 👇 fuerza que el pin vaya directo al mapa
            destMarker = L.marker(ll, {
            icon: L.divIcon({
                html: '<div style="transform:translate(-50%,-100%);color:#c00;font-size:24px">📍</div>',
                className: 'dest-pin',
                iconSize: [24, 24],
            })
            }).addTo(map);
        } else {
            destMarker.setLatLng(ll);
        }

        // Fit técnico + destino una sola vez si ya hay target
        try {
            if (window.__followTec && target && !window.__fitDoneOnce) {
            const bounds = L.latLngBounds(
                [target.lat, target.lng],
                [lat, lng]
            ).pad(0.15);
            map.fitBounds(bounds, { animate: true });
            window.__fitDoneOnce = true;
            }
        } catch (e) {
            console.warn('[live] error en fitBounds destino', e);
        }

        // Opcional: resaltar domicilio con un halo
        try {
            const halo = L.circle([lat, lng], {
            radius: 30,
            color: '#c00',
            weight: 2,
            opacity: 0.9,
            fillOpacity: 0.15,
            }).addTo(map);
            setTimeout(() => { try { map.removeLayer(halo); } catch (_) {} }, 1200);
        } catch (_) {}
    });

    socket.on('disconnect', () => {
        console.log('[live] disconnected');
        if (chatInput && chatSendBtn) {
            chatInput.disabled  = true;
            chatSendBtn.disabled = true;
            chatInput.placeholder = 'Conectando…';
        }
    });

    socket.on('chat:history', (list) => {
        try {
            clearChat();
            const arr = Array.isArray(list) ? list : [];
            arr.forEach(msg => {
                if (Number(msg.reportId) !== chatReportId) return; // sanity
                appendChatBubble({ text: msg.text, from: msg.from, ts: msg.ts }, /*myRole*/ 'client');
            });
        } catch (e) { console.warn('[chat] history error', e); }
    });

    socket.on('chat:message', (msg) => {
        try {
            if (Number(msg.reportId) !== chatReportId) return;
            appendChatBubble({ text: msg.text, from: msg.from, ts: msg.ts }, /*myRole*/ 'client');
        } catch (e) { console.warn('[chat] message parse', e); }
    });

  // Enviar (click o Enter)
    function sendCurrent(){
        if (!chatInput || !chatInput.value) return;
        const text = chatInput.value.trim();
        if (!text) return;
        // emitimos; el servidor re-emitirá y lo veremos en chat:message
        socket.emit('chat:send', {
            reportId,
            from: 'client',
            senderId: null,
            text,
            ts: Date.now()
        });
        chatInput.value = '';
    }

    if (chatSendBtn) chatSendBtn.addEventListener('click', sendCurrent);
    if (chatInput) {
        chatInput.addEventListener('keydown', (ev) => {
            if (ev.key === 'Enter') {
                ev.preventDefault();
                sendCurrent();
            }
        });
    }

    // Expón para depuración
    window._liveSocket = socket;
}
