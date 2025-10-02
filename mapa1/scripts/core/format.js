// Utilidades de formato
export const fmt = {
    ymd: d => d.toISOString().slice(0,10),
    ymd_slash: d => fmt.ymd(d).replaceAll('-', '/'),
    toMX: (s) => {
        if (!s) return '—';
        const parts = s.replace('T',' ').split(' ');
        const d = parts[0]?.split('-') || [];
        const time = parts[1] || '';
        if (d.length === 3) return `${d[2]}/${d[1]}/${d[0]} ${time}`.trim();
        const s2 = s.replace(/\//g,'-');
        const d2 = s2.split(' ')[0]?.split('-');
        if (d2 && d2.length===3) return `${d2[2]}/${d2[1]}/${d2[0]} ${s2.split(' ')[1]||''}`.trim();
        return s;
    }
};
