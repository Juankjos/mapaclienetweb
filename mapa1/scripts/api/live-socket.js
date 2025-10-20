// scripts/api/live-socket.js
// Cargar socket.io (ESM) desde CDN
import io from 'https://cdn.socket.io/4.7.5/socket.io.esm.min.js';

const getMap = () => window._leafletMap;
let carMarker = null;
let breadcrumb = null; // Polyline opcional

// Estado de suavizado
let last = null;   // { lat, lng, yaw }
let target = null; // { lat, lng, yaw }
let firstFixDone = false;
let panAccumulator = 0;

// Utilidades
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

// Render loop (suaviza y pinta)
function render(){
    requestAnimationFrame(render);
    const map = getMap();
    if (!map || !target) return;

    // init last
    if (!last) last = { ...target };

    // Suavizado de posición
    const alpha = 0.15;
    last.lat += (target.lat - last.lat) * alpha;
    last.lng += (target.lng - last.lng) * alpha;

    // Yaw (si server no manda, lo calculamos con delta)
    const calcYaw = bearingBetween({ lat:last.lat, lng:last.lng }, target);
    last.yaw = 0.8 * (last.yaw ?? calcYaw) + 0.2 * (target.yaw ?? calcYaw);

    // Pintar marcador 2D del técnico
    if (!carMarker) {
        carMarker = L.circleMarker([last.lat, last.lng], {
        radius: 6, weight: 2, color: '#0B8FFF', fillColor: '#0B8FFF', fillOpacity: 0.9
        }).addTo(window._layerCar || map);
    } else {
        carMarker.setLatLng([last.lat, last.lng]);
    }

    // (Opcional) trazo del recorrido
    if (!breadcrumb) {
        breadcrumb = L.polyline([[last.lat, last.lng]], { weight: 3, opacity: 0.5 }).addTo(window._layerCar || map);
    } else {
        const pts = breadcrumb.getLatLngs();
        const prev = pts[pts.length - 1];
        // agrega punto si te moviste ~>=5m
        const dist = map.distance(prev, L.latLng(last.lat, last.lng));
        if (dist >= 5) {
        pts.push(L.latLng(last.lat, last.lng));
        breadcrumb.setLatLngs(pts);
        }
    }

    // Publica para el overlay 3D (car-overlay.js)
    window._liveTarget = { ...last }; // {lat,lng,yaw}

    // Evento DOM para otras UIs (paneles, etc.)
    window.dispatchEvent(new CustomEvent('live:position', { detail: { ...last } }));

    // Centrados y pan-to
    if (!firstFixDone) {
        firstFixDone = true;
        try { map.setView([last.lat, last.lng], Math.max(18, map.getZoom() || 18), { animate: false }); } catch (_) {}
    } else {
        panAccumulator += 1/60; // aprox (render a ~60fps)
        if (panAccumulator > 0.3) {          // ≈ cada 0.3s
        panAccumulator = 0;
        try { map.panTo([target.lat, target.lng], { animate: true, duration: 0.3 }); } catch (_) {}
        }
    }
}
requestAnimationFrame(render);

// Conecta y suscríbete con el IDReporte que llega desde PHP
export function startLiveSocket() {
    const track = window.__TRACK__; // viene de mapa.php
    if (!track) return;

    const reportId = Number(track.IDReporte);
    const tecId    = track.NumTec ? Number(track.NumTec) : null;

    // Limpia estado previo si recargas
    target = null; last = null; firstFixDone = false; panAccumulator = 0;

    const socket = io('http://localhost:3001', {
        transports: ['websocket'],
        query: { reportId, tecId, role: 'client' },
        reconnection: true,
        reconnectionAttempts: Infinity,
        reconnectionDelay: 800,
        reconnectionDelayMax: 6000,
    });

    socket.on('connect', ()=> console.log('[live] connected, id=', socket.id));
    socket.on('connect_error', (err) => console.error('[live] connect_error:', err.message, err));
    socket.on('error', (err) => console.error('[live] error:', err));

    socket.on('location:live', (msg) => {
        console.log('[live] msg', msg); // 👈 logea primeras veces
        const lat = Number(msg.lat), lng = Number(msg.lng);
        const yaw = isFinite(Number(msg.bearing)) ? Number(msg.bearing) : undefined;
        if (!isFinite(lat) || !isFinite(lng)) return;
        target = { lat, lng, yaw };
        try { getMap().panTo([lat, lng], { animate: true, duration: 0.3 }); } catch(_) {}
    });

    socket.on('disconnect', ()=> console.log('[live] disconnected'));

    // Expón por si quieres depurar desde consola
    window._liveSocket = socket;
}
