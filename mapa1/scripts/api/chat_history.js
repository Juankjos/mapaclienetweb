(function(){
    const chatModal = document.getElementById('chatModal');
    const chatContent = document.getElementById('chatContent');
    const chatLoader  = document.getElementById('chatLoader');

    chatModal.addEventListener('show.bs.modal', function (ev) {
        const btn = ev.relatedTarget;
        const reportId = btn?.getAttribute('data-report-id');
        const contrato = btn?.getAttribute('data-contrato') || '';

        // Título del modal
        const title = chatModal.querySelector('#chatModalLabel');
        title.textContent = `Chat del reporte #${reportId}` + (contrato ? ` · Contrato ${contrato}` : '');

        // Limpia contenido y muestra loader
        chatContent.innerHTML = '';
        chatLoader.classList.remove('d-none');

        // Construye URL sobre la actual (mismo endpoint)
        const url = new URL(window.location.href);
        url.searchParams.set('ajax_chat', '1');
        url.searchParams.set('report_id', reportId);
        // Conserva tec y filtros actuales, no tocamos otros params

        fetch(url.toString(), {
            headers: { 'X-Requested-With': 'fetch' }
        })
        .then(r => r.text())
        .then(html => {
            chatContent.innerHTML = html;
            chatContent.scrollTop = chatContent.scrollHeight;
        })
        .catch(() => {
            chatContent.innerHTML = '<div class="text-danger">No se pudo cargar el historial de chat.</div>';
        })
        .finally(() => {
            chatLoader.classList.add('d-none');
        });
    });

    // Opcional: limpiar al cerrar
    chatModal.addEventListener('hidden.bs.modal', function () {
        chatContent.innerHTML = '';
    });
})();