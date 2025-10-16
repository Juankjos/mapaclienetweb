<?php
session_start();
require_once 'db.php';

// Requiere login
if (empty($_SESSION['contrato'])) {
    header('Location: login.php'); exit;
}
?>
<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Registro de técnico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/map/navbar.css" />
    <link rel="stylesheet" href="styles/map/root.css" />
    <link rel="stylesheet" href="styles/map/offcanvas.css" />
    <link rel="stylesheet" href="styles/rate/rate_tec.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .panel { max-width: 760px; margin-inline: auto; }
        .fade-slide { transition: all .25s ease; overflow: hidden; }
        .fade-slide.collapsed { max-height: 0; opacity: 0; margin-top: 0 !important; }
        .fade-slide.expanded { max-height: 400px; opacity: 1; }
        .tech-chip { font-weight: 600; }
    </style>
    </head>
    <body>
    <!-- Navbar -->
    <nav class="navbar navbar-light bg-white shadow-sm app-navbar fixed-top">
        <div class="container-fluid">
        <div class="d-flex align-items-center gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="rate_tec.php">
            <i class="bi bi-arrow-left"></i> Volver
            </a>
            <img src="icon/icono.png" class="nav-tech-icon" alt="Logo" />
            <img src="icon/iconopride.png" id="iconopride" alt="Variación de logo" />
        </div>
        </div>
    </nav>

    <main class="container panel" style="margin-top:84px;">
        <h3 class="mb-3">Registro de técnico</h3>

        <!-- Selección de técnico -->
        <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
            <div class="col-12 col-md-8">
                <label class="form-label">Selecciona un técnico</label>
                <select id="selTec" class="form-select">
                <option value="">— Elegir —</option>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <button id="btnSelect" type="button" class="btn btn-primary w-100">
                Seleccionar técnico
                </button>
            </div>
            </div>

            <!-- Técnico seleccionado -->
            <div id="selInfo" class="alert alert-info d-none mt-3 mb-0">
            Técnico seleccionado: <span id="lblTec" class="tech-chip"></span>
            <button id="btnCambiar" type="button" class="btn btn-link btn-sm">Cambiar</button>
            </div>
        </div>
        </div>

        <!-- Formulario de contraseña (crear o cambiar) -->
        <div id="frmWrap" class="card shadow-sm mb-5 d-none">
        <div class="card-body">
            <div class="mb-2">
            <span id="modeBadge" class="badge text-bg-secondary">Modo</span>
            </div>

            <div class="row g-2">
            <div class="col-12 col-md-6">
                <label id="lblPass1" class="form-label">Crear contraseña</label>
                <input id="pass1" type="password" class="form-control" placeholder="Min. 8 caracteres" minlength="8">
            </div>
            <div class="col-12 col-md-6">
                <label id="lblPass2" class="form-label">Confirmar contraseña</label>
                <input id="pass2" type="password" class="form-control" placeholder="Repite la contraseña" minlength="8">
            </div>
            </div>

            <div class="d-flex gap-2 mt-3">
            <button id="btnGuardar" type="button" class="btn btn-primary">Guardar</button>
            <button id="btnCancelar" type="button" class="btn btn-outline-secondary">Cancelar</button>
            </div>

            <div id="msg" class="alert d-none mt-3" role="alert"></div>
        </div>
        </div>
    </main>

<script>
    // ===== JSON de técnicos (pegado tal cual nos lo diste) =====
    const RAW = {"GetMuestra_Tecnicos_Almacen_AgendaResult":[{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":10146,"tecnico":"Albany Ruiz Molina"},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":20199,"tecnico":"Alfonso Enriquez Juarez"},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":120,"tecnico":"Alfonso Plascencia Gutiérrez "},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":20191,"tecnico":"Alonso Heriberto González Cisneros"},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":20197,"tecnico":"Anthony Enrique Silvestre Lopez"},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":104,"tecnico":"Basilio Barajas Moya "},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":118,"tecnico":"Carlos Hugo González Hernández "},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":20196,"tecnico":"Cristofer Alexis Hernández León"},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":20171,"tecnico":"Dan Velazquez"},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":20189,"tecnico":"Diego Iván Orozco González"},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":20193,"tecnico":"Edgar Alejandro Pérez Contreras"},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":76,"tecnico":"Eduardo Martin Vazquez "},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":77,"tecnico":"Edwin Borrayo Cruz "},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":97,"tecnico":"Emmanuel Reyes Sandoval "},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":92,"tecnico":"Evodio Alvarez Cordero "},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":20174,"tecnico":"Fabián de Jesús Rodríguez Córdova  "},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":20190,"tecnico":"Fernando de Jesus Garcia Rodriguez"},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":20177,"tecnico":"Fernando González Gutiérrez "},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":20204,"tecnico":"Fernando Martinez Zarate"},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":114,"tecnico":"Gerardo Arcos Prieto "},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":10142,"tecnico":"Geronimo Martinez Lopez "},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":95,"tecnico":"Guadalupe Antonio Perez Mercado "},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":10151,"tecnico":"Guillermo Ruan Valenzuela"},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":79,"tecnico":"Gustavo Alcantar Gutierrez "},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":20172,"tecnico":"Gustavo Ponce Armas "},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":10152,"tecnico":"Informática"},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":83,"tecnico":"Javier Esquivias Gomez "},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":20156,"tecnico":"Jesús Márquez García "},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":20170,"tecnico":"Joel Ernesto López Rivera "},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":20192,"tecnico":"Jordi Orozco Gonzalez"},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":86,"tecnico":"Jose Alberto Ochoa Huerta "},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":20195,"tecnico":"José Antonio Vargas Pérez"},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":91,"tecnico":"Jose Antonio Yepez Delgadillo "},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":20203,"tecnico":"José Carlos Gómez Rivera "},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":20160,"tecnico":"JOSE DE JESUS DE ALBA VERA "},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":94,"tecnico":"Jose Gabriel Franco Velazquez "},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":85,"tecnico":"Jose Guadalupe Gallardo Mendez "},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":10148,"tecnico":"Jose Javier Perez Mercado"},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":20185,"tecnico":"Josue Abraham Martinez Alvarez"},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":90,"tecnico":"Juan Carlos Martin Marquez "},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":10154,"tecnico":"Juan Gilberto De Loza Pérez"},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":20200,"tecnico":"Juan Leonardo Romero Sanchez"},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":78,"tecnico":"Juan Pablo Hernandez Magana "},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":20159,"tecnico":"Luis Angel Gomez Muñoz "},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":20194,"tecnico":"Luis Jesús Esteban Ramírez"},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":20163,"tecnico":"Martín Alejandro López Vazquez"},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":20202,"tecnico":"Mayem Israel Ramírez Romo"},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":81,"tecnico":"Miguel Lopez Cruz "},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":10150,"tecnico":"Oribel Ibarra Vivanco"},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":20154,"tecnico":"Osvaldo Orozco Navarro "},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":20201,"tecnico":"Ricardo Gutiérrez González"},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":82,"tecnico":"Salvador Venegas Plascencia "},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":20182,"tecnico":"Servando Pérez Martin Del Campo"},{"BaseIdUser":0,"BaseRemoteIp":null,"clv_tecnico":106,"tecnico":"Victor Manuel Martinez Lozano "}]};

    const sel = document.getElementById('selTec');
    const btnSelect = document.getElementById('btnSelect');
    const selInfo = document.getElementById('selInfo');
    const lblTec = document.getElementById('lblTec');
    const btnCambiar = document.getElementById('btnCambiar');

    const frmWrap = document.getElementById('frmWrap');
    const modeBadge = document.getElementById('modeBadge');
    const lblPass1 = document.getElementById('lblPass1');
    const lblPass2 = document.getElementById('lblPass2');
    const pass1 = document.getElementById('pass1');
    const pass2 = document.getElementById('pass2');
    const btnGuardar = document.getElementById('btnGuardar');
    const btnCancelar = document.getElementById('btnCancelar');
    const msg = document.getElementById('msg');

    // Poblamos select
    const list = (RAW?.GetMuestra_Tecnicos_Almacen_AgendaResult || []).slice().sort((a,b)=>String(a.tecnico).localeCompare(String(b.tecnico),'es'));
    for (const it of list) {
        const opt = document.createElement('option');
        opt.value = it.clv_tecnico;
        opt.textContent = `${it.tecnico} — ${it.clv_tecnico}`;
        opt.dataset.nombre = it.tecnico;
        sel.appendChild(opt);
    }

    // Estado local
    let current = null; // { id, nombre, hasPassword }

    function uiSelectMode(on){
        sel.disabled = on;
        btnSelect.classList.toggle('d-none', on);
        selInfo.classList.toggle('d-none', !on);
        frmWrap.classList.toggle('d-none', !on);
        if (on) frmWrap.classList.add('fade-slide','expanded');
    }

    function setFormMode(hasPass){
        modeBadge.textContent = hasPass ? 'Cambiar contraseña' : 'Crear contraseña';
        modeBadge.className = hasPass ? 'badge text-bg-warning' : 'badge text-bg-success';
        lblPass1.textContent = hasPass ? 'Nueva contraseña' : 'Crear contraseña';
        lblPass2.textContent = hasPass ? 'Confirmar nueva contraseña' : 'Confirmar contraseña';
        pass1.value = '';
        pass2.value = '';
        msg.className = 'alert d-none';
        msg.textContent = '';
    }

    async function postJSON(url, body){
        const resp = await fetch(url, {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify(body)
        });
        const j = await resp.json().catch(()=>({ok:false,message:'Respuesta inválida'}));
        if(!resp.ok || !j.ok) throw new Error(j.message || 'Operación fallida');
        return j;
    }

    // Seleccionar técnico
    btnSelect.addEventListener('click', async ()=>{
        const id = parseInt(sel.value,10);
        if (!id) { alert('Elige un técnico del listado.'); return; }
        const nombre = sel.options[sel.selectedIndex].dataset.nombre || '';

        try{
            // En el servidor: asegura existencia en "tecnicos" y regresa si ya tiene PasswordHash
            const j = await postJSON('tech_actions.php', { action:'select_tec', idtec:id, nombre:nombre });
            current = { id, nombre, hasPassword: !!j.hasPassword };
            lblTec.textContent = `${nombre} — ${id}`;
            setFormMode(current.hasPassword);
            uiSelectMode(true);
        }catch(e){
            msg.className = 'alert alert-danger';
            msg.textContent = e.message;
        }
    });

    // Cambiar de técnico
    btnCambiar.addEventListener('click', ()=>{
        current = null;
        uiSelectMode(false);
        frmWrap.classList.remove('expanded');
        msg.className = 'alert d-none'; msg.textContent='';
        sel.value = '';
    });

    // Guardar contraseña
    btnGuardar.addEventListener('click', async ()=>{
        if (!current?.id) return;

        const p1 = pass1.value.trim();
        const p2 = pass2.value.trim();
        if (p1.length < 8) { msg.className='alert alert-warning'; msg.textContent='La contraseña debe tener al menos 8 caracteres.'; return; }
        if (p1 !== p2) { msg.className='alert alert-warning'; msg.textContent='Las contraseñas no coinciden.'; return; }

        btnGuardar.disabled = true;
        msg.className='alert alert-info'; msg.textContent='Guardando…';

        try{
            await postJSON('tech_actions.php', { action:'save_password', idtec: current.id, password:p1 });
            msg.className='alert alert-success'; msg.textContent='Contraseña guardada correctamente.';
        }catch(e){
            msg.className='alert alert-danger'; msg.textContent= e.message || 'No se pudo guardar.';
        }finally{
            btnGuardar.disabled = false;
        }
    });

    // Cancelar vuelve a estado inicial
    btnCancelar.addEventListener('click', ()=>{
        current = null;
        sel.value = '';
        uiSelectMode(false);
        msg.className = 'alert d-none'; msg.textContent='';
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
