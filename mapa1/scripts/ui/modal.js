import { fmt } from '../core/format.js';

let _modalHoldTimer = null;
let _modalHoldResolver = null;

export function openModal(){ const m = document.getElementById('clientModal'); m.classList.add('open'); m.setAttribute('aria-hidden','false'); }
export function closeModal(){
    const m = document.getElementById('clientModal'); m.classList.remove('open'); m.setAttribute('aria-hidden','true');
    if (_modalHoldTimer){ clearTimeout(_modalHoldTimer); _modalHoldTimer = null; }
    if (_modalHoldResolver){ const r = _modalHoldResolver; _modalHoldResolver = null; r(); }
}

export function wireModalClose(){
    document.getElementById('cm-close')?.addEventListener('click', closeModal);
    document.getElementById('clientModal')?.addEventListener('click', (e)=>{ if (e.target.id==='clientModal') closeModal(); });
    document.addEventListener('keydown', (e)=>{ if (e.key==='Escape') closeModal(); });
}

export function showClientModal(r){
    const esEjec = (r.solucion && r.fecha_ejecucion);
    const badge = esEjec ? '<span class="badge-state ejec">Ejecutada</span>' : '<span class="badge-state pend">Pendiente</span>';
    const dir = (r.direccion?.calle || '').trim();
    const col = (r.direccion?.colonia || '').trim();
    const dirTxt = [dir, col && `Col. ${col}`].filter(Boolean).join(' · ');

    document.getElementById('cm-title').innerHTML = `Contrato ${r.contrato || '—'} · Reporte ${r.reporte || '—'} ${badge}`;
    document.getElementById('cm-sub').textContent = `${(r.nombre||'').trim()}${dirTxt ? ' · '+dirTxt : ''}`;

    document.getElementById('cm-sep-addr')?.remove();
    document.getElementById('cm-sub').insertAdjacentHTML('afterend','<hr id="cm-sep-addr" style="border:0;border-top:1px solid #123054;margin:10px 0" />');

    const problema = r.reporte_cliente || r.clasificacion || '—';
    const sol = esEjec ? (r.solucion || '—') : null;
    document.getElementById('cm-problema').innerHTML =
        esEjec ? `<div><b>Problema:</b><br>${problema}</div><div style="margin-top:10px"><b>Solución:</b> ${sol}</div>`
            : `<div><b>Problema:</b><br>${problema}</div>`;

    document.getElementById('cm-creacion').innerHTML =
        `<hr style="border:0;border-top:1px solid #123054;margin:10px 0" />
        <b>Fecha de creación:</b> ${fmt.toMX(r.fecha_solicitud)}`;

    document.getElementById('cm-ejecucion').innerHTML =
        esEjec ? `<b>Fecha de ejecución:</b> ${fmt.toMX(r.fecha_ejecucion)}` : '';

    document.getElementById('cm-solucion').innerHTML = '';
    openModal();
}
