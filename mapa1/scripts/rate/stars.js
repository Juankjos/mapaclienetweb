(function () {
    const btn = document.getElementById('btnGuardarComentario');
    const overlay = document.getElementById('overlayListo');
    const canvas = document.getElementById('confettiCanvas');

    if (!btn || !overlay || !canvas) return;

    // Crea una instancia ligada al canvas del overlay
    let confettiOverlay = null;
    function getConfettiInstance(){
    if (!confettiOverlay && window.confetti) {
        confettiOverlay = confetti.create(canvas, {
        resize: true,      // se ajusta al tamaño del overlay
        useWorker: true
        });
    }
    return confettiOverlay;
    }

    btn.addEventListener('click', () => {
    overlay.classList.add('show');
    overlay.setAttribute('aria-hidden', 'false');

    // Lanza el confeti cuando el overlay ya empezó a mostrarse
    requestAnimationFrame(() => {
        setTimeout(() => lanzarConfeti(), 300);
    });
    });

    function lanzarConfeti() {
    const c = getConfettiInstance();
    if (!c) return;

    const duration = 400;                 // 2s
    const end = Date.now() + duration;

    // ráfagas continuas durante "duration"
    (function frame() {
            c({
            particleCount: 10,
            startVelocity: 45,
            spread: 360,
            ticks: 80,
            scalar: 1,
            origin: { x: Math.random(), y: Math.random() * 0.6 } // más visible en el overlay
            });
            if (Date.now() < end) requestAnimationFrame(frame);
        })();
        }
})();

(function () {
const fieldset = document.querySelector('.rating-stars');
if (!fieldset) return;

    // === Splash de estrellitas ===
    function starSplashAt(x, y, count = 10, color = '#fcd93a', spread = 40, duration = 600) {
        const layer = document.createElement('div');
        layer.className = 'star-splash';
        document.body.appendChild(layer);

        for (let i = 0; i < count; i++) {
        const span = document.createElement('span');
        span.className = 'p';
        span.textContent = '⭐';
        span.style.color = color;

        // Movimiento aleatorio, más amplio con spread alto
        const angle = Math.random() * Math.PI * 2;
        const radius = spread * (0.4 + Math.random() * 0.8);
        const dx = Math.cos(angle) * radius;
        const dy = Math.sin(angle) * radius - spread * 0.4;

        span.style.left = x + 'px';
        span.style.top = y + 'px';
        span.style.setProperty('--dx', dx + 'px');
        span.style.setProperty('--dy', dy + 'px');
        span.style.setProperty('--rot', (Math.random() * 180 - 90) + 'deg');
        span.style.fontSize = (12 + Math.random() * 10) + 'px';
        span.style.animationDuration = (duration + Math.random() * 300) + 'ms';

        layer.appendChild(span);
        span.addEventListener('animationend', () => {
            span.remove();
            if (!layer.childElementCount) layer.remove();
        });
        }
    }

    // === Detectar clic en estrellas ===
    fieldset.addEventListener('click', (e) => {
        const label = e.target.closest('label');
        if (!label) return;
        const input = label.previousElementSibling;
        if (!input) return;

        const rating = parseInt(input.value, 10) || 1;
        const rect = label.getBoundingClientRect();
        const cx = rect.left + rect.width / 2;
        const cy = rect.top + rect.height / 2;

        // Configuración según calificación
        const splashMap = {
        1: { count: 8,  color: '#ff3b3b', spread: 40, duration: 500 },
        2: { count: 8, color: '#ffb347', spread: 60, duration: 600 },
        3: { count: 12, color: '#ffe047', spread: 80, duration: 700 },
        4: { count: 32, color: '#fcd93a', spread: 110, duration: 850 },
        5: { count: 45, color: '#ffd700', spread: 140, duration: 1000 },
        };

        const { count, color, spread, duration } = splashMap[rating] || splashMap[3];
        starSplashAt(cx, cy, count, color, spread, duration);
    });
})();