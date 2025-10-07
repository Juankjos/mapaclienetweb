// Helpers
function fmtFechaLargaEsMX(dtStr) {
    if (!dtStr) return 'Sin fecha';
        const m = /^(\d{4})-(\d{2})-(\d{2})(?:\s+(\d{2}):(\d{2})(?::(\d{2}))?)?$/.exec(dtStr);
    if (!m) return dtStr;
        const d = new Date(+m[1], +m[2]-1, +m[3], +(m[4]||0), +(m[5]||0), +(m[6]||0));
    return new Intl.DateTimeFormat('es-MX', { dateStyle: 'long' }).format(d);
}
function esc(s){ return (s ?? '').toString(); }

function starRow(rate){
    const r = Math.max(0, Math.min(5, Number(rate)||0));
    let out=''; for(let i=0;i<5;i++) out += `<i class="bi ${i<r?'bi-star-fill':'bi-star'}"></i>`;
    return out;
}

function formatDireccion(raw) {
    if (!raw) return 'Sin dirección registrada';
    const s = String(raw).trim();

    const lower = s.toLowerCase();
    const idxCol = lower.indexOf('colonia ');
    const idxCiu = lower.indexOf('ciudad ');

    // Parte de calle/base antes de la primera etiqueta
    const firstIdx = [idxCol, idxCiu].filter(i => i >= 0).sort((a,b)=>a-b)[0];
    const street = (firstIdx >= 0 ? s.slice(0, firstIdx) : s).trim();

    // Extraer valores (hasta siguiente etiqueta o fin)
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

    // Si no hay etiquetas, solo elimina palabras y normaliza espacios
    if (idxCol < 0 && idxCiu < 0) {
        return s.replace(/\b(?:Colonia|Ciudad)\s+/gi, '').replace(/\s{2,}/g,' ').trim();
    }

    // Comparar igualdad colonia vs ciudad
    const norm = v => (v||'').toLowerCase().replace(/\s+/g,' ').trim();
    const same = coloniaVal && ciudadVal && norm(coloniaVal) === norm(ciudadVal);

    const parts = [];
    if (street) parts.push(street);
    if (coloniaVal && !same) parts.push(coloniaVal);
    if (ciudadVal) parts.push(ciudadVal);

    return parts.join(', ').replace(/\s{2,}/g,' ').trim();
}

(function init(){
    const d = window.__ORDENES__ || { Nombre:'Cliente', items:[] };
    // Offcanvas: nombre
    const elN = document.getElementById('Nombre');
    if (elN) elN.textContent = d.Nombre || 'Cliente';

    // Estado UI
    const state = { all: d.items || [], q:'', status:'', page:1, pageSize:10 };

    const elQ = document.getElementById('q');
    const elS = document.getElementById('fStatus');
    const elPS = document.getElementById('pageSize');
    const elCards = document.getElementById('cards');
    const elInfo = document.getElementById('resultsInfo');
    const btnPrev = document.getElementById('prevBtn');
    const btnNext = document.getElementById('nextBtn');
    const btnPage = document.getElementById('pageInfo');

    const btnFollow = document.getElementById('btnFollowTech');
    const followHelp = document.getElementById('followHelp');

    // Elige la orden "En camino" más reciente
    function pickLatestEnCamino(items){
        const arr = (items || []).filter(x => (x.Status || '') === 'En camino');
        if (!arr.length) return null;
        arr.sort((a,b) => {
            const ta = Date.parse((a.FechaAgendado||'1000-01-01').replace(' ', 'T'));
            const tb = Date.parse((b.FechaAgendado||'1000-01-01').replace(' ', 'T'));
            if (tb !== ta) return tb - ta;
            return (b.IDReporte||0) - (a.IDReporte||0);
        });
        return arr[0];
    }

    // Habilita/Deshabilita el botón "Sigue a tu técnico"
    function updateFollowButton(){
        const current = pickLatestEnCamino(state.all);
        const canFollow = !!current;
        btnFollow.disabled = !canFollow;

        if (canFollow) {
            btnFollow.classList.remove('btn-secondary');
            btnFollow.classList.add('btn-primary');
            btnFollow.title = `Seguir técnico de la orden #${current.IDReporte}`;
            followHelp.innerHTML = `Orden <strong>#${current.IDReporte}</strong> está <strong>En camino</strong>. Puedes seguir a tu técnico.`;
            btnFollow.onclick = () => {
                const url = `${FOLLOW_TECH_URL_BASE}?reporte=${encodeURIComponent(current.IDReporte)}`;
                window.location.href = url;
            };
        } else {
            btnFollow.classList.remove('btn-primary');
            btnFollow.classList.add('btn-secondary');
            btnFollow.title = 'No hay órdenes En camino';
            followHelp.innerHTML = `Disponible cuando tu orden esté <strong>En camino</strong>.`;
            btnFollow.onclick = null;
        }
    }

    function applyFilters(){
        let arr = state.all.slice();
        if (state.status) arr = arr.filter(x => (x.Status||'') === state.status);
        if (state.q) {
        const q = state.q.toLowerCase();
        arr = arr.filter(x => (
            (x.Problema||'').toLowerCase().includes(q) ||
            (x.NombreTec||'').toLowerCase().includes(q) ||
            (x.Direccion||'').toLowerCase().includes(q) ||
            String(x.IDReporte).includes(q)
        ));
        }
        return arr;
    }

    function render(){
        const filtered = applyFilters();
        const total = filtered.length;
        const pages = Math.max(1, Math.ceil(total / state.pageSize));
        if (state.page > pages) state.page = pages;

        const start = (state.page - 1) * state.pageSize;
        const slice = filtered.slice(start, start + state.pageSize);

        elCards.innerHTML = slice.map(item => {
        const titulo = esc(item.Problema) || 'Sin descripción del problema';
        const tecnico = item.NombreTec || (item.IDTec ? `Técnico #${item.IDTec}` : 'Sin asignar');
        const direccion = formatDireccion(item.Direccion); // <-- USO DEL HELPER
        const fecha = fmtFechaLargaEsMX(item.FechaAgendado);
        const status = esc(item.Status) || 'Sin estado';
        const rate = starRow(item.Rate);

        return `
            <article class="order-card p-3">
            <div class="row g-3">
                <div class="col-12 col-sm-auto">
                <img src="images/tecnico.png" class="image-thumb rounded" alt="Técnico">
                </div>
                <div class="col">
                <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
                    <div>
                        <h2 class="h5 mb-1 text-dark fw-semibold">${titulo}</h2>
                        <div class="small text-muted">Reporte #${item.IDReporte} · <span class="badge text-bg-secondary">${status}</span></div>
                        <div class="rating mt-1">${rate}</div>
                    </div>
                    </div>
                    <div class="item-row mt-2">
                    <div class="d-flex align-items-start flex-wrap info-group">
                        <div class="info-block">
                        <div class="small text-muted">Lo atendió:</div>
                        <div class="fw-semibold">${tecnico}</div>
                        </div>
                        <div class="info-block">
                        <div class="small text-muted">En el domicilio:</div>
                        <div class="fw-semibold">${direccion}</div>
                    </div>
                    <div class="info-block">
                        <div class="small text-muted">El día:</div>
                        <div class="fw-semibold">${fecha}</div>
                    </div>
                    </div>
                </div>
                </div>
            </div>
            </article>
        `;
        }).join('');

        elInfo.textContent = `${total} resultado${total===1?'':'s'} · página ${state.page} de ${pages}`;
        btnPage.textContent = `${state.page} / ${pages}`;
        btnPrev.disabled = state.page <= 1;
        btnNext.disabled = state.page >= pages;
    }

    // Eventos
    document.getElementById('prevBtn').addEventListener('click', () => { if (state.page > 1) { state.page--; render(); }});
    document.getElementById('nextBtn').addEventListener('click', () => { state.page++; render(); });
    document.getElementById('pageSize').addEventListener('change', (e) => { state.pageSize = parseInt(e.target.value, 10) || 10; state.page = 1; render(); });
    document.getElementById('fStatus').addEventListener('change', (e) => { state.status = e.target.value; state.page = 1; render(); });
    document.getElementById('q').addEventListener('input', (e) => { state.q = e.target.value.trim(); state.page = 1; render(); });

    // Init
    updateFollowButton();
    render();
})();


// ========== SWEET ALERT ==========
(function setupLogoutConfirm(){
    const form = document.getElementById('logoutForm');
    const btn  = document.getElementById('btnLogout');
    if (!form || !btn) return;

    btn.addEventListener('click', function(e){
        e.preventDefault(); // evita que envíe de inmediato
        Swal.fire({
            title: '¿Estás seguro que deseas cerrar sesión?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, cerrar sesión',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#d33',
        }).then((result) => {
            if (result.isConfirmed) form.submit();
        });
    });
})();

//Redirigir a Ordenes de Servicio
document.querySelectorAll('.menu-btn').forEach(btn => {
    if (btn.textContent.includes('Órdenes de Servicio')) {
        btn.addEventListener('click', () => {
            window.location.href = 'ordenes_servicio.php';
        });
    }
});