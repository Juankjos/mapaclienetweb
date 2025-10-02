const COLOR = { pendiente: '#F59E0B', ejecutada: '#0B8FFF' };

export function makeMarker(report, group, onClick){
    const estado = report.estado;
    const color  = estado === 'pendientes' ? COLOR.pendiente : COLOR.ejecutada;
    const m = L.circleMarker([report.latitude, report.longitude], {
        radius:6, weight:1.2, color:'#001014', fillColor:color, fillOpacity:0.95
    });
    m.on('click', () => onClick(report));
    m.addTo(group);
    return m;
}

export function resetMarkers(group, markersArr){
    group.clearLayers();
    markersArr.length = 0;
}
