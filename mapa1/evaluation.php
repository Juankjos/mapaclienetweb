<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Evaluación del servicio</title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="styles/map/navbar.css" />
    <link rel="stylesheet" href="styles/map/root.css" />
    <link rel="stylesheet" href="styles/map/offcanvas.css" />
    <link rel="stylesheet" href="styles/evaluation/starrate.css" />
    <link rel="stylesheet" href="styles/evaluation/overlay.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    </head>
    <body>
        <div id="overlayListo" class="overlay-listo" aria-hidden="true">
            <canvas id="confettiCanvas" class="overlay-canvas" aria-hidden="true"></canvas>
            <div class="overlay-content d-flex flex-column align-items-center justify-content-center text-center">
                <h1 class="text-white display-3 fw-bold mb-3">¡Listo!</h1>
                <p class="text-white fs-5 mb-1">Tus comentarios han sido enviados.</p>
                <p class="text-white fs-2">¡Fue un placer atenderte!</p>
            </div>
        </div>
    <!-- Navbar -->
    <nav class="navbar navbar-light bg-white shadow-sm app-navbar fixed-top" aria-label="Barra de navegación">
        <div class="container-fluid">
        <div class="d-flex align-items-center gap-2">
            <!-- Botón hamburguesa que abre el menú lateral -->
            <button class="navbar-toggler" type="button"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#menuLateral"
                    aria-controls="menuLateral"
                    aria-label="Abrir menú">
            <span class="navbar-toggler-icon"></span>
            </button>
            <img src="icon/icono.png" class="nav-tech-icon" alt="Logo" />
            <img src="icon/iconopride.png" id="iconopride" alt="Variación de logo" />
        </div>
        </div>
    </nav>

    <div class="offcanvas offcanvas-start" tabindex="-1" id="menuLateral" aria-labelledby="menuLateralLabel">
        <div class="offcanvas-body">
        <!-- PERFIL -->
        <div class="d-flex align-items-center gap-3 mb-3">
            <div class="rounded-circle bg-light border" style="width:48px;height:48px; display:flex;align-items:center;justify-content:center;">
            <i class="bi bi-person fs-4 text-secondary"></i>
            </div>
            <div>
            <div class="fw-semibold">Juan Carlos G. Medina</div>
            <a href="#" id="admin-cuenta" class="text-decoration-none small">Administrar cuenta</a>
            </div>
        </div>

        <!-- NUEVO MENÚ SIMPLE -->
        <div class="menu-simple d-flex flex-column gap-2 mt-3">
            <button type="button" class="menu-btn">
                <i class="bi bi-file-earmark-bar-graph-fill me-2"></i>
                Órdenes de Servicio
            </button>
            <button type="button" class="menu-btn">
                <i class="bi bi-person-raised-hand me-2"></i>
                Contacto a Soporte
            </button>
        </div>

        </div>
        <div class="offcanvas-footer mt-auto">
        <button type="button" class="btn-logout">
            <i class="bi bi-box-arrow-right me-2"></i>
            Cerrar Sesión
        </button>
    </div>
    </div>

    <main class="container py-3">
        <!-- ===== EVALUACIÓN ===== -->
        <section id="eval" aria-labelledby="eval-title">
        <h6 id="eval-title" class="section-title visually-hidden">Evaluación</h6>

        <div class="card shadow-sm mb-3" style="max-width: 600px;">
            <div class="card-body">
            <!-- FOTO DE PERFIL -->
            <div class="mb-3 d-flex justify-content-center">
                <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center" style="width:96px;height:96px;">
                <i class="bi bi-person fs-1 text-secondary"></i>
                </div>
            </div>

            <h5 class="mb-3 fw-bold text-dark">¿Cómo fue tu experiencia con Servando?</h5>

            <!-- Rating: 5 estrellas -->
            <fieldset class="rating-stars mb-3" aria-labelledby="rating-label" role="radiogroup">
                <legend id="rating-label" class="visually-hidden">Calificación</legend>
                <input type="radio" name="svc-rating" id="svc-rating-5" value="5" aria-label="5 estrellas" />
                <label for="svc-rating-5" title="Excelente" tabindex="0"></label>
                <input type="radio" name="svc-rating" id="svc-rating-4" value="4" aria-label="4 estrellas" />
                <label for="svc-rating-4" title="Muy bueno" tabindex="0"></label>
                <input type="radio" name="svc-rating" id="svc-rating-3" value="3" aria-label="3 estrellas" />
                <label for="svc-rating-3" title="Bueno" tabindex="0"></label>
                <input type="radio" name="svc-rating" id="svc-rating-2" value="2" aria-label="2 estrellas" />
                <label for="svc-rating-2" title="Regular" tabindex="0"></label>
                <input type="radio" name="svc-rating" id="svc-rating-1" value="1" aria-label="1 estrella" />
                <label for="svc-rating-1" title="Malo" tabindex="0"></label>
            </fieldset>

            <input type="hidden" id="svc-rating-value" value="0" />

            <div class="mb-3 w-100">
                <label for="svc-comentarios" class="form-label fw-semibold">Deja tus comentarios positivos si tuviste una buena experiencia</label>
                <textarea class="form-control text-left" id="svc-comentarios" rows="5" maxlength="300" placeholder="Danos tu mejor opinión..."></textarea>
            </div>

            <div class="mt-3 w-100">
                <button class="btn btn-primary w-100" id="btnGuardarComentario" type="button">Enviar</button>
            </div>
            </div>
        </div>
        <div id="status" class="alert d-none" role="alert"></div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
    <script>
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
    </script>
    <script>
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
    </script>
</body>
</html>
