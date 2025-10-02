export function setupBaseSwitcher({ map, bases }) {
    let currentBase = bases.baseLight;

    function setBase(name){
        const target = ({ sat:bases.esriImagery, light:bases.baseLight, dark:bases.baseDark })[name] || bases.esriImagery;
        if (currentBase !== target){
        if (currentBase) currentBase.remove();
        target.addTo(map);
        currentBase = target;
        }
        document.querySelectorAll('.basemap-card')
        .forEach(c => c.classList.toggle('active', c.dataset.base === name));
    }

    // listeners del widget
    document.querySelectorAll('.basemap-card').forEach(card=>{
        card.addEventListener('click', ()=> setBase(card.dataset.base));
    });

    return { setBase };
}
