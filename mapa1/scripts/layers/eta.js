/**
 * Lógica ETA:
 * - Si Status='En camino':
 *   1) intenta leer ETA guardada (get_eta.php)
 *   2) si no hay, espera a tener lastLoc + destino
 *   3) calcula ETA: FechaInicio + (duración OSRM o fallback por distancia)
 *   4) muestra y fija en BD (set_eta.php) para que no cambie
 */

const OSRM_BASE = 'https://router.project-osrm.org';
const track = window.__TRACK__ || {};

if (track.Status === 'En camino') {
    initEtaOnce();
} else if (track.ETA) {
    // Si ya viene en payload, solo muéstrala
    renderEta(new Date(track.ETA));
}

async function initEtaOnce(){
    // 0) ¿ya existe en BD?
    try {
        const r = await fetch(`api/get_eta.php?reporte=${track.IDReporte}`);
        const j = await r.json();
        if (j?.eta_iso) { renderEta(new Date(j.eta_iso)); return; }
    } catch (_) {}

    // 1) Esperar a lastLoc + destino del WS (máx ~20s)
    let tries = 0;
    const maxTries = 20;
    const iv = setInterval(async () => {
        tries++;
        if (window._lastLoc && window.__destLive) {
        clearInterval(iv);
        const eta = await computeEta(window._lastLoc, window.__destLive, track.FechaInicio);
        if (eta) {
            renderEta(eta);
            // 2) Persistir (best-effort)
            try {
            await fetch('api/set_eta.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ reporteId: track.IDReporte, eta_iso: eta.toISOString() })
            });
            } catch (_) {}
        }
        } else if (tries >= maxTries) {
        clearInterval(iv);
        // si quieres, podrías mostrar un fallback local con now + 30min, etc.
        }
    }, 1000);
}

async function computeEta(loc, dest, fechaInicioStr){
    // Si no hay FechaInicio en payload, usa "ahora" para no fallar:
    const fechaInicio = fechaInicioStr ? new Date(fechaInicioStr) : new Date();

    // Intenta OSRM (duración en segundos)
    try {
        const url = `${OSRM_BASE}/route/v1/driving/${loc.lng},${loc.lat};${dest.lng},${dest.lat}?overview=false`;
        const res = await fetch(url, { cache:'no-store' });
        const json = await res.json();
        const sec = json?.routes?.[0]?.duration;
        if (typeof sec === 'number' && isFinite(sec)) {
        return new Date(fechaInicio.getTime() + sec * 1000);
        }
    } catch (_) {}

    // Fallback: Haversine + velocidad media (30 km/h)
    const km = haversineKm(loc.lat,loc.lng,dest.lat,dest.lng);
    const hours = km / 30; // ajusta a tu realidad
    return new Date(fechaInicio.getTime() + hours * 3600 * 1000);
}

function renderEta(date){
    const fmt = { hour: 'numeric', minute: '2-digit' };
    const etaStr = date.toLocaleTimeString('es-MX', fmt);

    const etaEl = document.getElementById('etaText');
    if (etaEl) etaEl.textContent = `Llegada estimada: ${etaStr}`;

    // “a más tardar”: +20 min (ajusta si quieres)
    const limit = new Date(date.getTime() + 20 * 60000);
    const limitEl = document.getElementById('etaLimitText');
    if (limitEl) limitEl.textContent = limit.toLocaleTimeString('es-MX', fmt);
}

function haversineKm(lat1,lon1,lat2,lon2){
    const R=6371;
    const dLat=(lat2-lat1)*Math.PI/180;
    const dLon=(lon2-lon1)*Math.PI/180;
    const a=Math.sin(dLat/2)**2 +
            Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180) *
            Math.sin(dLon/2)**2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}