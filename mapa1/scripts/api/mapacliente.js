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

    // Rellenar campos solicitados
    setAllById('NombreTec', d.NombreTec || 'Técnico sin asignar');
    setAllById('NumTec',    d.NumTec ? `Contáctate al número ${d.NumTec}` : 'Número no disponible');
    setAllById('IDContrato', d.IDContrato || '');
    setAllById('Problema',   d.Problema || 'Sin descripción del problema');
    setAllById('Direccion',  d.Direccion || 'Sin dirección registrada');
})();

//Redirigir a Ordenes de Servicio
document.querySelectorAll('.menu-btn').forEach(btn => {
    if (btn.textContent.includes('Órdenes de Servicio')) {
        btn.addEventListener('click', () => {
            window.location.href = 'ordenes_servicio.php';
        });
    }
});