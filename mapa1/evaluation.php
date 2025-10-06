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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    </head>
    <body>
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

        <div class="card shadow-sm mb-3">
            <div class="card-body">
            <h5 class="mb-3 fw-bold text-dark">
                ¿Cómo fue tu experiencia con Servando?
            </h5>

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

            <!-- Valor seleccionado -->
            <input type="hidden" id="svc-rating-value" value="0" />
            <!-- <p class="mb-3" aria-live="polite">Calificación seleccionada: <strong id="svc-selected">0</strong>/5</p> -->

            <!-- Comentario -->
            <div class="mb-3">
                <label for="svc-comentarios" class="form-label fw-semibold">
                    Deja tus comentarios positivos si tuviste una buena experiencia
                </label>
                <textarea class="form-control" id="svc-comentarios" rows="5" maxlength="300"
                        placeholder="Dinos tu mejor opinión..."></textarea>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-primary" id="btnGuardarComentario" type="button">Guardar</button>
                <button class="btn btn-outline-secondary" id="btnLimpiar" type="button">Limpiar</button>
            </div>
            </div>
        </div>

        <!-- Alerta de estado -->
        <div id="status" class="alert d-none" role="alert"></div>
        </section>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <script>
        (function(){
        const fieldset = document.querySelector('.rating-stars');
        const hiddenValue = document.getElementById('svc-rating-value');
        const selectedText = document.getElementById('svc-selected');
        const radios = Array.from(document.querySelectorAll('input[name="svc-rating"]'));
        const labels = Array.from(fieldset.querySelectorAll('label'));
        const txt = document.getElementById('svc-comentarios');
        const count = document.getElementById('svc-count');
        const btnSave = document.getElementById('btnGuardarComentario');
        const btnClear = document.getElementById('btnLimpiar');
        const status = document.getElementById('status');

        // Actualiza contador caracteres
        const updateCount = () => { count.textContent = String(txt.value.length); };
        txt.addEventListener('input', updateCount);
        updateCount();

        // Cuando cambia el radio
        radios.forEach(r => r.addEventListener('change', () => {
            hiddenValue.value = r.value;
            selectedText.textContent = r.value;
        }));

        // Permitir teclado en labels (Enter / Espacio / Flechas)
        labels.forEach((label, idx) => {
            label.addEventListener('keydown', (e) => {
            const key = e.key;
            const currentIdx = idx; // por row-reverse, 0 = 5 estrellas
            if(key === 'Enter' || key === ' '){
                e.preventDefault();
                const forId = label.getAttribute('for');
                const input = document.getElementById(forId);
                input.checked = true; input.dispatchEvent(new Event('change', { bubbles:true }));
            } else if(key === 'ArrowLeft' || key === 'ArrowDown'){
                e.preventDefault();
                const next = labels[Math.min(labels.length-1, currentIdx+1)];
                next?.focus();
            } else if(key === 'ArrowRight' || key === 'ArrowUp'){
                e.preventDefault();
                const prev = labels[Math.max(0, currentIdx-1)];
                prev?.focus();
            }
            });
        });

        // Guardar (demo)
        function showStatus(type, msg){
            status.className = 'alert alert-' + type;
            status.textContent = msg;
            status.classList.remove('d-none');
            // ocultar después de 4s
            setTimeout(() => status.classList.add('d-none'), 4000);
        }

        btnSave.addEventListener('click', () => {
            const rating = Number(hiddenValue.value);
            const comment = txt.value.trim();
            if(!rating){
            showStatus('warning', 'Por favor selecciona una calificación.');
            return;
            }
            // Aquí podrías enviar a tu backend con fetch()
            console.log('Datos a enviar', { rating, comment });
            showStatus('success', '¡Gracias! Tu retroalimentación se ha guardado.');
        });

        btnClear.addEventListener('click', () => {
            radios.forEach(r => r.checked = false);
            hiddenValue.value = '0';
            selectedText.textContent = '0';
            txt.value = '';
            updateCount();
            showStatus('secondary', 'Se limpió el formulario.');
        });
        })();
    </script>
</body>
</html>
