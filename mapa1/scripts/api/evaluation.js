// ======== Stars simple (si ya tienes stars.js, puedes omitir esto) ========
(function starPicker(){
    const radios = [...document.querySelectorAll('input[name="svc-rating"]')];
    const hidden = document.getElementById('svc-rating-value');
    radios.forEach(r => r.addEventListener('change', () => hidden.value = r.value));
})();

// ======== Enviar evaluación ========
(function setupSubmit(){
    const btn  = document.getElementById('btnGuardarComentario');
    const out  = document.getElementById('status');
    const data = window.__EVAL__ || {};

    btn.addEventListener('click', async () => {
        const val = parseInt(document.getElementById('svc-rating-value').value || '0', 10);
        const com = (document.getElementById('Comentario').value || '').trim();

        // Validaciones
        if (!(val >= 1 && val <= 5)) {
        out.className = 'alert alert-warning';
        out.textContent = 'Selecciona una calificación entre 1 y 5 estrellas.';
        out.classList.remove('d-none');
        return;
        }
        if (com.length > 300) {
        out.className = 'alert alert-warning';
        out.textContent = 'El comentario no debe exceder 300 caracteres.';
        out.classList.remove('d-none');
        return;
        }

        btn.disabled = true;
        out.className = 'alert alert-info';
        out.textContent = 'Enviando…';
        out.classList.remove('d-none');

        try{
        const resp = await fetch('procesar_evaluacion.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
            reporte: data.IDReporte,
            rate: val,
            comentario: com
            })
        });
        const j = await resp.json();

        if (!resp.ok || !j.ok) throw new Error(j.message || 'Error al guardar');

        // Éxito: overlay + confetti + redirect
        const overlay = document.getElementById('overlayListo');
        overlay.setAttribute('aria-hidden', 'false');

        // Éxito: SweetAlert + confetti + redirect
        try {
            const duration = 1200;
            const end = Date.now() + duration;
            const frame = () => {
                confetti({ particleCount: 3, spread: 70, origin: { y: 0.6 } });
                if (Date.now() < end) requestAnimationFrame(frame);
            };
            frame();
        } catch {}

        Swal.fire({
            title: '¡Gracias por tu evaluación!',
            text: 'Serás redirigido en 5 segundos…',
            icon: 'success',
            confirmButtonText: 'OK',
            confirmButtonColor: '#10451cff', // Verde
            draggable: true,
            allowOutsideClick: false, 
            timer: 5000,
            timerProgressBar: true
        }).then(() => {
            window.location.href = 'ordenes_servicio.php';
        });

        // por si el usuario no espera el timer y quieres forzar el redirect:
        setTimeout(() => { window.location.href = 'ordenes_servicio.php'; }, 5000);

        }catch(e){
            out.className = 'alert alert-danger';
            out.textContent = 'No se pudo guardar la evaluación. Intenta de nuevo.';
            btn.disabled = false;
        }
    });
})();

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
});