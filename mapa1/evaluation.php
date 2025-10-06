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
    <div id="overlayListo" class="overlay-listo d-flex align-items-center justify-content-center">
        <h1 class="text-white display-3 fw-bold">¡Listo!</h1>
    </div>
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
    <script>
        document.getElementById('btnGuardarComentario').addEventListener('click', () => {
            const overlay = document.getElementById('overlayListo');
            overlay.classList.add('show');
            setTimeout(() => {
            overlay.style.opacity = 1;
            }, 50);
        });
    </script>
</body>
</html>
