const API_BASE = './index.php';

export async function fetchReports({ inicio, fin, filter }) {
    const qs = new URLSearchParams({ action:'fetch_reports', inicio, fin, filter });
    const url = `${API_BASE}?${qs.toString()}`;
    const resp = await fetch(url, { headers: { 'Accept':'application/json' }});
    if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
    const data = await resp.json();
    if (!data.ok) throw new Error(data.error || 'Respuesta inválida');
    return data.data || [];
}
