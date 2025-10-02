export function initMap() {
    const map = L.map('map', {
        zoomControl: false, zoomSnap: 0.25, zoomDelta: 0.5,
        wheelDebounceTime: 25, wheelPxPerZoomLevel: 100,
        preferCanvas: true, maxZoom: 20, minZoom: 10
    });
    map.setView([20.814, -102.76], 12);
    L.control.zoom({ position:'bottomright' }).addTo(map);

    // Bases
    const esriImagery = L.tileLayer(
        'https://services.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        { maxZoom:20, maxNativeZoom:18, attribution:'&copy; Esri', crossOrigin:true,
        errorTileUrl:'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==' }
    );

    const baseLight = L.tileLayer(
        'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
        { attribution:'&copy; OpenStreetMap, &copy; CARTO', subdomains:'abcd',
        detectRetina:true, updateWhenZooming:true, maxNativeZoom:19, maxZoom:22,
        noWrap:true, crossOrigin:true, keepBuffer:4 }
    ).addTo(map);

    const baseDark = L.tileLayer(
        'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
        { attribution:'&copy; OpenStreetMap, &copy; CARTO', subdomains:'abcd',
        maxNativeZoom:19, maxZoom:22, noWrap:true, crossOrigin:true, keepBuffer:4 }
    );

    // Overlays
    const roadsOverlay = L.tileLayer(
        'https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Transportation/MapServer/tile/{z}/{y}/{x}',
        { opacity:0.45, maxNativeZoom:19, maxZoom:22, crossOrigin:true }
    ).addTo(map);

    const layerAll = L.layerGroup().addTo(map);
    const layerCar = L.layerGroup().addTo(map);

    // expone referencias necesarias
    return {
        map,
        bases: { esriImagery, baseLight, baseDark },
        overlays: { roadsOverlay },
        groups: { layerAll, layerCar }
    };
}
