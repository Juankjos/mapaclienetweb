<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Ordenes de Servicio</title>

    <!-- Bootstrap 5.3 CSS + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/map/navbar.css" />
    <link rel="stylesheet" href="styles/map/root.css" />
    <link rel="stylesheet" href="styles/map/offcanvas.css" />
    <link rel="stylesheet" href="styles/ordenes/ordenes.css" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

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

    <main class="container my-4">
        <h4 class="orders-title mb-3">Ordenes anteriores</h4>

        <article class="order-card p-3">
        <div class="row g-3">
            <div class="col-12 col-sm-auto">
            <img src="images/tecnico.png"
                class="image-thumb rounded" alt="Imagen referencia">
            </div>

            <div class="col">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
                <div>
                <h2 class="h5 mb-1 d-block text-decoration-none text-dark fw-semibold">Cambio de Tecnología.</h2>
                <div class="rating">
                    <i class="bi bi-star-fill" aria-hidden="true"></i>
                    <i class="bi bi-star-fill" aria-hidden="true"></i>
                    <i class="bi bi-star-fill" aria-hidden="true"></i>
                    <i class="bi bi-star-fill" aria-hidden="true"></i>
                    <i class="bi bi-star-fill" aria-hidden="true"></i>
                </div>
                </div>
            </div>

            <div class="item-row">
                <div class="d-flex align-items-start flex-wrap info-group">
                    <div class="info-block">
                        <div class="small text-muted">Lo atendió:</div>
                        <div class="fw-semibold">Salvador Venegas Plascencia</div>
                    </div>
                    <div class="info-block">
                        <div class="small text-muted">En el domicilio:</div>
                        <div class="fw-semibold">
                            C. González Hermosillo 191, San Antonio El Alto, 47640 Tepatitlán de Morelos, Jal.
                        </div>
                    </div>
                    <div class="info-block">
                        <div class="small text-muted">El día:</div>
                        <div class="fw-semibold">25 de Octubre del 2025</div>
                    </div>
                </div>
            </div>
            
        </div>
        </article>

    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
