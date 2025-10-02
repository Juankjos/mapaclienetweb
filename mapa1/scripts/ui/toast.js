const elToast = document.getElementById('toast');
const loadingMask = document.getElementById('loadingMask');

export function toast(msg, ms=1800){
    elToast.innerText = msg; elToast.style.display='block';
    clearTimeout(toast._t); toast._t = setTimeout(()=> elToast.style.display='none', ms);
}
export function showLoading(on){ if(loadingMask) loadingMask.classList.toggle('show', !!on); }
