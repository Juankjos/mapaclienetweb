  // Helpers UI
  function setEnabled(el, on){ el.disabled = !on; }
  function showActions(id, show){ document.getElementById(id).classList.toggle('show', !!show); }
  function resetNombre(){
    const sec = document.getElementById('secNombre');
    const orig = sec.dataset.original || '';
    const inp  = document.getElementById('inpNombre');
    inp.value = orig;
    document.getElementById('tgNombre').checked = false;
    setEnabled(inp,false);
    showActions('actNombre', false);
  }
  function resetCorreo(){
    const sec = document.getElementById('secCorreo');
    const orig = sec.dataset.original || '';
    const inp  = document.getElementById('inpCorreo');
    inp.value = orig;
    document.getElementById('tgCorreo').checked = false;
    setEnabled(inp,false);
    showActions('actCorreo', false);
  }
  function resetPass(){
    document.getElementById('tgPass').checked = false;
    ['inpPassActual','inpPassNueva','inpPassConfirm'].forEach(id=>{
      const el = document.getElementById(id);
      el.value = '';
      setEnabled(el,false);
    });
    showActions('actPass', false);
  }

  // Toggles
  document.getElementById('tgNombre').addEventListener('change', (e)=>{
    const on = e.target.checked;
    setEnabled(document.getElementById('inpNombre'), on);
    showActions('actNombre', on);
    if (!on) resetNombre();
  });
  document.getElementById('tgCorreo').addEventListener('change', (e)=>{
    const on = e.target.checked;
    setEnabled(document.getElementById('inpCorreo'), on);
    showActions('actCorreo', on);
    if (!on) resetCorreo();
  });
  document.getElementById('tgPass').addEventListener('change', (e)=>{
    const on = e.target.checked;
    ['inpPassActual','inpPassNueva','inpPassConfirm'].forEach(id=> setEnabled(document.getElementById(id), on));
    showActions('actPass', on);
    if (!on) resetPass();
  });

  // Cancelar -> recarga (como pediste)
  document.querySelector('[data-action="cancel-nombre"]').addEventListener('click', ()=> location.reload());
  document.querySelector('[data-action="cancel-correo"]').addEventListener('click', ()=> location.reload());
  document.querySelector('[data-action="cancel-pass"]').addEventListener('click', ()=> location.reload());

  // Guardar cambios (SweetAlert de confirmación)
  async function confirmSave() {
    const res = await Swal.fire({
      title: '¿Deseas guardar los cambios?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, guardar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#0d6efd',
      allowOutsideClick: false
    });
    return res.isConfirmed;
  }

  // Llamada genérica
  async function postJSON(url, body){
    const resp = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type':'application/json' },
      body: JSON.stringify(body)
    });
    const j = await resp.json().catch(()=>({ok:false,message:'Respuesta inválida'}));
    if(!resp.ok || !j.ok) throw new Error(j.message || 'Error en la operación');
    return j;
  }

  // Guardar Nombre
  document.querySelector('[data-action="save-nombre"]').addEventListener('click', async ()=>{
    const inp = document.getElementById('inpNombre');
    const nuevo = inp.value.trim();
    const orig  = (document.getElementById('secNombre').dataset.original || '').trim();
    if (nuevo.length < 2) {
      Swal.fire('Nombre demasiado corto','Escribe al menos 2 caracteres.','warning'); return;
    }
    if (nuevo === orig) { resetNombre(); return; }
    if (!(await confirmSave())) return;

    try{
      await postJSON('update_account.php', { action:'update_name', nombre:nuevo });
      // Refleja cambios
      document.getElementById('secNombre').dataset.original = nuevo;
      Swal.fire('Listo','Tu nombre ha sido actualizado.','success');
      resetNombre();
    }catch(e){ Swal.fire('Ups', e.message, 'error'); }
  });

  // Guardar Correo
  document.querySelector('[data-action="save-correo"]').addEventListener('click', async ()=>{
    const inp = document.getElementById('inpCorreo');
    const nuevo = inp.value.trim();
    const orig  = (document.getElementById('secCorreo').dataset.original || '').trim();
    const reEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!reEmail.test(nuevo)) { Swal.fire('Correo inválido','Revisa el formato.','warning'); return; }
    if (nuevo === orig) { resetCorreo(); return; }
    if (!(await confirmSave())) return;

    try{
      await postJSON('update_account.php', { action:'update_email', correo:nuevo });
      document.getElementById('secCorreo').dataset.original = nuevo;
      Swal.fire('Listo','Tu correo ha sido actualizado.','success');
      resetCorreo();
    }catch(e){ Swal.fire('Ups', e.message, 'error'); }
  });

  // Guardar Password
  document.querySelector('[data-action="save-pass"]').addEventListener('click', async ()=>{
    const cur = document.getElementById('inpPassActual').value;
    const n1  = document.getElementById('inpPassNueva').value;
    const n2  = document.getElementById('inpPassConfirm').value;

    if (!cur || !n1 || !n2) { Swal.fire('Campos necesarios','Completa todos los campos.','warning'); return; }
    if (n1.length < 8) { Swal.fire('Contraseña corta','Mínimo 8 caracteres.','warning'); return; }
    if (n1 !== n2) { Swal.fire('No coinciden','La confirmación no coincide.','warning'); return; }

    if (!(await confirmSave())) return;

    try{
      await postJSON('update_account.php', { action:'update_password', current:cur, newpass:n1 });
      Swal.fire('Listo','Tu contraseña se actualizó.','success');
      resetPass();
    }catch(e){ Swal.fire('Ups', e.message, 'error'); }
  });
