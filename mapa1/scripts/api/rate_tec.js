const filtrar = document.getElementById('filtrar');
const filas = Array.from(document.querySelectorAll('#tabla-tecs tbody tr'));
const normalize = s => (s || '')
    .normalize('NFD')                         // separa acentos
    .replace(/\p{Diacritic}/gu, '')           // quita acentos
    .toLowerCase()
    .trim();
const digits = s => (s || '').replace(/\D+/g, ''); // solo números

function matchRow(tr, qText, qDigits) {
    const nombre = normalize(tr.dataset.nombre);
    const id     = String(tr.dataset.id || '');
    const num    = digits(tr.dataset.num || '');
    // Soporta múltiples palabras en texto (todas deben coincidir en el nombre)
    const tokens = qText.split(/\s+/).filter(Boolean);
    const okText = tokens.length ? tokens.every(tok => nombre.includes(tok)) : false;
    // Coincidencia numérica: si escriben números, comparamos con ID o teléfono/NumTec
    const okNum = qDigits ? (id.includes(qDigits) || (num && num.includes(qDigits))) : false;
    return okText || okNum;
}

filtrar?.addEventListener('input', () => {
const raw = filtrar.value || '';
    if (!raw.trim()) {
        filas.forEach(tr => tr.style.display = '');
        return;
    }
    const qText   = normalize(raw);
    const qDigits = digits(raw);
    filas.forEach(tr => {
        tr.style.display = matchRow(tr, qText, qDigits) ? '' : 'none';
    });
});

document.addEventListener('DOMContentLoaded', () => {
    // ====== SWEET ALERT LOGOUT ======
    const form = document.getElementById('logoutForm');
    const btn  = document.getElementById('btnLogout');

    if (form && btn) {
        btn.addEventListener('click', (e) => {
            e.preventDefault();

        if (typeof Swal === 'undefined') {
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
        cancelButtonColor: '#6c757d',
        allowOutsideClick: false
    }).then((result) => {
        if (result.isConfirmed) form.submit();
    });
    });
    } else {
        console.warn('[logout] No se encontró #logoutForm o #btnLogout');
    }
});