// Actualiza varios elementos aunque el ID esté duplicado en el HTML
window.__followTec = true;

function updateFollowButtonsUI(){
    const bUnf = document.getElementById('btnUnfollow');
    const bFol = document.getElementById('btnFollow');
    const bHome = document.getElementById('btnHome');

    if (bUnf && bFol) {
        if (window.__followTec) {
            bUnf.style.display = '';
            bFol.style.display = 'none';
        } else {
            bUnf.style.display = 'none';
            bFol.style.display = '';
        }
    }

    // "Mi domicilio" solo habilitado cuando NO estamos siguiendo
    if (bHome) {
        bHome.disabled = !!window.__followTec;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const bUnf = document.getElementById('btnUnfollow');
    const bFol = document.getElementById('btnFollow');
    const bHome = document.getElementById('btnHome');

    if (bUnf) bUnf.addEventListener('click', () => {
        window.__followTec = false;
        // al dejar de seguir, opcional: encuadrar técnico+destino
        if (typeof window.focusDestination === 'function') {
            window.focusDestination(/*preferBounds=*/true);
        }
        updateFollowButtonsUI();
    });

    if (bFol) bFol.addEventListener('click', () => {
        window.__followTec = true;
        if (typeof window.focusTechnician === 'function') {
            window.focusTechnician();
        }
        updateFollowButtonsUI();
    });

    if (bHome) bHome.addEventListener('click', () => {
        // Solo centra en el domicilio (destino); NO cambia el follow
        if (typeof window.focusDestination === 'function') {
            // preferBounds = false para ir directo al destino
            const hadDest = !!window.__destLive;
            window.focusDestination(false);
            if (!hadDest) {
                // Feedback si aún no tenemos destino
                if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'info',
                    title: 'Sin destino aún',
                    text: 'Todavía no tenemos el pin de tu domicilio. Intenta de nuevo en unos segundos.',
                    confirmButtonText: 'OK'
                });
                } else {
                alert('Todavía no tenemos el pin de tu domicilio. Intenta de nuevo en unos segundos.');
                }
            }
            }
    });
    updateFollowButtonsUI();
});

function setAllById(id, text) {
    document.querySelectorAll('[id="'+id+'"]').forEach(el => {
        el.textContent = text ?? '';
    });
}

function secureRedirect(url) {
    try {
        history.pushState(null, '', location.href);
        history.replaceState(null, '', location.href);
        window.addEventListener('popstate', () => {
        history.pushState(null, '', location.href);
        });
    } catch {}

    setTimeout(() => {
        window.location.replace(url); // <- reemplaza la entrada del historial
    }, 50);
}

(function hydratePanel(){
    const d = window.__TRACK__ || {};
    // Offcanvas: Nombre del cliente
    setAllById('Nombre', d.Nombre || 'Cliente');

    if (d.Status !== 'En camino') {
        console.warn('No hay orden En camino para este contrato.');
        return;
    }

    function formatPhone(num) {
        if (!num) return '';
        const digits = String(num).replace(/\D+/g, ''); // elimina cualquier carácter no numérico
        const m = digits.match(/^(\d{3})(\d{3})(\d+)$/);
        if (!m) return num; // si no cumple el patrón, deja igual
        return `${m[1]} ${m[2]} ${m[3]}`;
    }

    const telFormatted = d.NumTec ? formatPhone(d.NumTec) : null;
    // Rellenar campos solicitados
    setAllById('NombreTec', d.NombreTec || 'Técnico sin asignar');
    setAllById('NumTec', telFormatted ? `Contáctate al número ${telFormatted}` : 'Número no disponible');
    setAllById('IDContrato', d.IDContrato || '');
    setAllById('Problema',   d.Problema || 'Sin descripción del problema');
    setAllById('Direccion',  formatDireccion(d.Direccion));
})();

//Redirigir a Ordenes de Servicio
document.querySelectorAll('.menu-btn').forEach(btn => {
    if (btn.textContent.includes('Órdenes de Servicio')) {
        btn.addEventListener('click', () => {
            window.location.href = 'ordenes_servicio.php';
        });
    }
});

function showCanceledPrompt() {
    const laterUrl = 'ordenes_servicio.php';

    if (typeof Swal === 'undefined') {
        alert('Su orden fue Cancelada. Ante cualquier duda o aclaración contacte a soporte.');
        secureRedirect(laterUrl);
        return;
    }

    Swal.fire({
        icon: 'info',
        title: 'Su orden fue Cancelada',
        html: 'Ante cualquier duda o aclaración contacte a soporte.',
        confirmButtonText: 'OK',
        allowOutsideClick: false,
        allowEscapeKey: false
    }).then(() => {
        secureRedirect(laterUrl);
    });
}

function showEvalPrompt(d) {

    const evalUrl = `evaluation.php?reporte=${encodeURIComponent(d.IDReporte)}`;
    const laterUrl = 'ordenes_servicio.php';

    // Fallback si SweetAlert2 no cargó
    if (typeof Swal === 'undefined') {
        setTimeout(() => { secureRedirect(evalUrl); }, 5000);
        if (confirm('Tu orden ha concluido. ¿Deseas calificar ahora?')) {
            secureRedirect(evalUrl);
        } else {
            secureRedirect(laterUrl);
        }
        return;
    }

    let timerInterval;
    Swal.fire({
        title: '¡Tu orden ha concluido!',
        html: 'Califica a nuestro técnico y cuéntanos tu experiencia.<br><br>' +
            'Redirigiendo en <b id="swal-timer">5</b>s…',
        icon: 'success',
        showCancelButton: true,
        confirmButtonText: 'Calificar ahora',
        cancelButtonText: 'Más tarde',
        allowOutsideClick: false,
        allowEscapeKey: false,
        reverseButtons: true,
        timer: 5000,
        timerProgressBar: true,
        didOpen: () => {
            const $cnt = Swal.getHtmlContainer();
            const $b = $cnt ? $cnt.querySelector('#swal-timer') : null;
            let s = 5;
            timerInterval = setInterval(() => {
                s = Math.max(0, s - 1);
                if ($b) $b.textContent = String(s);
            }, 1000);
        },
        willClose: () => clearInterval(timerInterval)
    }).then((res) => {
        // ✅ Cualquier salida bloquea el regreso a mapa
        if (res.isConfirmed) {
            secureRedirect(evalUrl);
        } else if (res.dismiss === Swal.DismissReason.cancel) {
            secureRedirect(laterUrl);
        } else if (res.dismiss === Swal.DismissReason.timer) {
            secureRedirect(evalUrl);
        } else {
            // fallback por cualquier otro cierre
            secureRedirect(evalUrl);
        }
    });
}

// Normaliza dirección ocultando "Colonia" y "Ciudad".
// Si colonia y ciudad son iguales, solo muestra una vez la ciudad (sin palabra "Ciudad").
function formatDireccion(raw) {
    if (!raw) return 'Sin dirección registrada';
    const s = String(raw).trim();

    const lower = s.toLowerCase();
    const idxCol = lower.indexOf('colonia ');
    const idxCiu = lower.indexOf('ciudad ');

    // Street/base (parte antes de la primera etiqueta)
    const firstIdx = [idxCol, idxCiu].filter(i => i >= 0).sort((a,b)=>a-b)[0];
    const street = (firstIdx >= 0 ? s.slice(0, firstIdx) : s).trim();

    // Extraer valores (hasta el siguiente marcador o fin)
    let coloniaVal = null, ciudadVal = null;

    if (idxCol >= 0) {
        const start = idxCol + 'colonia '.length;
        const end = (idxCiu > idxCol) ? idxCiu : s.length;
        coloniaVal = s.slice(start, end).trim();
    }
    if (idxCiu >= 0) {
        const start = idxCiu + 'ciudad '.length;
        ciudadVal = s.slice(start).trim();
    }

    // Si no encontramos etiquetas, como fallback quitamos las palabras si vinieran sueltas
    if (idxCol < 0 && idxCiu < 0) {
        return s.replace(/\b(?:Colonia|Ciudad)\s+/gi, '').replace(/\s{2,}/g,' ').trim();
    }

    // Comparar colonia vs ciudad (normalizando espacios/caso)
    const norm = v => (v||'').toLowerCase().replace(/\s+/g,' ').trim();
    const same = coloniaVal && ciudadVal && norm(coloniaVal) === norm(ciudadVal);

    const parts = [];
    if (street) parts.push(street);
    if (coloniaVal && !same) parts.push(coloniaVal);
    if (ciudadVal) parts.push(ciudadVal);

    // Unir con coma si hay varias partes, o con espacio simple
    return parts.join(', ').replace(/\s{2,}/g,' ').trim();
}


// CERRAR SESIÓN SWEETALERT
document.addEventListener('DOMContentLoaded', () => {
    // ====== SWEET ALERT LOGOUT ======
    const form = document.getElementById('logoutForm');
    const btn  = document.getElementById('btnLogout');

    if (form && btn) {
        btn.addEventListener('click', (e) => {
        e.preventDefault();

        // ¿SweetAlert2 cargado?
        if (typeof Swal === 'undefined') {
          // Fallback simple
            if (confirm('¿Estás seguro que deseas cerrar sesión?')) {
                form.submit();
            }
            return;
        }

        Swal.fire({
            title: '¿Estás seguro que deseas cerrar sesión?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, cerrar sesión',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#d33',
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) form.submit();
        });
        });
    } else {
        // Ayuda para depurar si cambiaste IDs en el HTML
        console.warn('[logout] No se encontró #logoutForm o #btnLogout');
    }

    // ====== Redirigir a Órdenes de Servicio ======
    document.querySelectorAll('.menu-btn').forEach(btn => {
        if (btn.textContent && btn.textContent.includes('Órdenes de Servicio')) {
        btn.addEventListener('click', () => {
            window.location.href = 'ordenes_servicio.php';
        });
        }
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const d = window.__TRACK__ || {};
    const mustAsk = (d.Status === 'Completado') && (Number(d.Rate || 0) === 0);
    if (mustAsk) showEvalPrompt(d);
});

(function statusWatcher(){
    const d = window.__TRACK__ || {};
    if (!d.IDReporte || !d.IDContrato) return;

    let stopped = false;
    let currentStatus = d.Status;
    let currentRate   = Number(d.Rate || 0);
    let timerId = null;
    let controller = null;
    let shown = false; // evita doble prompt por socket + polling

    const FAST_MS = 3000;
    const SLOW_MS = 10000;

    function scheduleNext(ms){
        if (stopped || shown) return;
        clearTimeout(timerId);
        timerId = setTimeout(runOnce, ms);
    }
    function nextInterval(){ return document.hidden ? SLOW_MS : FAST_MS; }

    async function runOnce(){
        if (stopped || shown) return;
        try { controller?.abort(); } catch {}
        controller = new AbortController();

        try {
        const url = `check_status.php?reporte=${encodeURIComponent(d.IDReporte)}&contrato=${encodeURIComponent(d.IDContrato)}`;
        const resp = await fetch(url, { cache:'no-store', signal: controller.signal });
        if (!resp.ok) { scheduleNext(SLOW_MS); return; }
        const j = await resp.json();
        if (!j || !j.ok) { scheduleNext(SLOW_MS); return; }

        const newStatus = j.status;
        const newRate   = Number(j.rate || 0);

        if (newStatus !== currentStatus || newRate !== currentRate) {
            console.log(`[poll] status: ${currentStatus} -> ${newStatus} | rate: ${currentRate} -> ${newRate}`);
        }

        currentStatus = newStatus;
        currentRate   = newRate;

        // 🔴 Cancelado: prioridad máxima
        if (!shown && newStatus === 'Cancelado') {
            shown = true; stopped = true;
            clearTimeout(timerId); try{controller.abort();}catch{}
            showCanceledPrompt();
            return;
        }

        // ⭐ Completado + sin calificación
        if (!shown && newStatus === 'Completado' && newRate === 0) {
            shown = true; stopped = true;
            clearTimeout(timerId); try{controller.abort();}catch{}
            showEvalPrompt({ ...d, Status:newStatus, Rate:newRate });
            return;
        }

        scheduleNext(nextInterval());

        } catch (err) {
        if (err?.name !== 'AbortError') console.warn('[poll] error', err);
        scheduleNext(SLOW_MS);
        }
    }

    // si ya llegó Completado+rate=0 desde el server al cargar, dispara
    const mustAsk = (d.Status === 'Completado') && (Number(d.Rate || 0) === 0);
    if (mustAsk) { shown = true; showEvalPrompt(d); return; }

    runOnce();
    document.addEventListener('visibilitychange', ()=>{ if (!stopped && !shown) scheduleNext(200); });
    window.addEventListener('beforeunload', ()=>{ stopped = true; clearTimeout(timerId); try{controller?.abort();}catch{} });
})();