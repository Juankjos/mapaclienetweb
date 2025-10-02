import { initMap } from './map/initMap.js';
import { setupBaseSwitcher } from './map/baseSwitcher.js';
import { fetchReports } from './api/client.js';
import { makeMarker, resetMarkers } from './layers/markers.js';
import { showClientModal, wireModalClose } from './ui/modal.js';
import { toast, showLoading } from './ui/toast.js';

const { map, bases, groups } = initMap();
window._leafletMap = map;
setupBaseSwitcher({ map, bases });

const elInicio = document.getElementById('fechaInicio');
const elFin    = document.getElementById('fechaFin');
const elFiltro = document.getElementById('filtroEstado');
const elBtn    = document.getElementById('btnCargar');
const elHud    = document.getElementById('hud');

wireModalClose();

// últimos 2 días (zona MX)
(function setLast2Days(){
    const tz = { timeZone: 'America/Mexico_City' };
    const fin = new Date(new Date().toLocaleString('en-US', tz));
    const inicio = new Date(fin); inicio.setDate(inicio.getDate()-1);
    const ymdMX = d => d.toLocaleDateString('en-CA', tz); // YYYY-MM-DD
    elInicio.value = ymdMX(inicio);
    elFin.value    = ymdMX(fin);
})();

const markers = []; // {marker, latlng, estado, data}

async function cargar(fitAfterLoad=false){
    const inicio = elInicio.value;
    const fin    = elFin.value;
    const filtro = elFiltro.value;

    elHud.textContent = 'Cargando…';
    showLoading(true);
    resetMarkers(groups.layerAll, markers);

    try{
        const arr = await fetchReports({ inicio, fin, filter:filtro });
        if (!arr.length){
        toast('Sin datos para el rango/filtrado');
        elHud.textContent = `0 fallas · filtro=${filtro}`;
        return;
        }

        arr.forEach(r => {
        const m = makeMarker(r, groups.layerAll, showClientModal);
        markers.push({ marker:m, latlng:[r.latitude, r.longitude], estado:r.estado, data:r });
        });

        if (fitAfterLoad && markers.length){
        const bounds = L.latLngBounds(markers.map(x=>x.latlng));
        map.fitBounds(bounds.pad(0.1));
        }
        elHud.textContent = `${markers.length} fallas (mostrando ${filtro})`;

    }catch(e){
        console.error(e);
        toast('Error al cargar');
        elHud.textContent = 'Error.';
    }finally{
        showLoading(false);
    }
}

elBtn.addEventListener('click', ()=> cargar(true));
cargar(false); // carga inicial
