// Actualiza varios elementos aunque el ID esté duplicado en el HTML
function setAllById(id, text) {
    document.querySelectorAll('[id="'+id+'"]').forEach(el => {
        el.textContent = text ?? '';
    });
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

    if (mustAsk && window.bootstrap && bootstrap.Modal) {
        const modalEl = document.getElementById('ratingModal');
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
            // Actualiza el link a evaluation.php con el IDReporte real
            const a = document.getElementById('goEval');
            if (a && d.IDReporte) a.href = `evaluation.php?reporte=${encodeURIComponent(d.IDReporte)}`;
            modal.show();
        }
    }
});

(function statusPolling(){
    const d = window.__TRACK__ || {};
    if (!d.IDReporte || !d.IDContrato) return;

    let shown = false;
    async function check() {
        try {
            const resp = await fetch(`check_status.php?reporte=${encodeURIComponent(d.IDReporte)}&contrato=${encodeURIComponent(d.IDContrato)}`, {cache:'no-store'});
            if (!resp.ok) return;
            const j = await resp.json(); // {status:'En camino'|'Completado'|..., rate:0-5}
            if (!shown && j && j.status === 'Completado' && Number(j.rate||0) === 0) {
                shown = true;
            if (window.bootstrap && bootstrap.Modal) {
                const modalEl = document.getElementById('ratingModal');
                const a = document.getElementById('goEval');
            if (a && d.IDReporte) a.href = `evaluation.php?reporte=${encodeURIComponent(d.IDReporte)}`;
                const modal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
                modal.show();
            } else {
                // Fallback si Bootstrap no está
                if (confirm('Tu orden ha concluido. ¿Deseas calificar ahora?')) {
                    location.href = `evaluation.php?reporte=${encodeURIComponent(d.IDReporte)}`;
                } else {
                    location.href = 'ordenes_servicio.php';
                }
                }
            }
        } catch {}
    }

    // primer chequeo inmediato + cada 5s
    check();
    setInterval(check, 5000);
})();