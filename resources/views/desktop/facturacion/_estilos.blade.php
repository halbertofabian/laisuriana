@push('desktop-styles')
<style>
    /* Solo lo propio de Facturación: superficies, tablas, campos, botones y pills vienen del layout desktop. */
    /* La barra de comandos no debe comprimirse cuando sus acciones pasan a otra línea (pantallas pequeñas). */
    .app__main > .desktop-toolbar { flex-shrink:0; }
    .fac-alert { display:flex; align-items:flex-start; gap:8px; padding:9px 12px; border:0 solid var(--warning-stroke); border-bottom-width:1px; background:var(--warning-soft); color:var(--warning); font-size:.8rem; line-height:1.45; }
    .fac-alert svg { width:16px; height:16px; flex:none; margin-top:1px; }
    .fac-alert a { color:inherit; font-weight:700; }
    .fac-alert strong { font-weight:700; }
    .fac-alert--danger { background:var(--danger-soft); color:var(--danger); border-color:var(--danger-stroke); }
    .fac-alert--success { background:var(--success-soft); color:var(--success); border-color:var(--success-stroke); }
    .fac-alert--info { background:var(--brand-soft); color:var(--brand-pressed); border-color:var(--brand-soft-2); }
    .fac-alert--box { border-width:1px; border-radius:var(--r-md); margin-bottom:10px; }
    .fac-alert__body { flex:1 1 auto; min-width:0; }
    .fac-alert__actions { display:flex; gap:6px; flex:none; align-items:center; flex-wrap:wrap; }
    .fac-alert[hidden] { display:none; }

    .fac-bar { flex:0 0 auto; display:flex; align-items:center; justify-content:space-between; gap:8px 12px; flex-wrap:wrap; padding:7px 12px; border-bottom:1px solid var(--stroke); background:var(--surface); }
    .fac-bar .desktop-pivot { flex-wrap:wrap; }
    .fac-bar__hint { font-size:.74rem; color:var(--text-2); }
    .fac-count { display:inline-flex; min-width:18px; height:18px; padding:0 5px; align-items:center; justify-content:center; border-radius:9px; background:var(--surface); color:var(--text-2); font-size:.7rem; font-variant-numeric:tabular-nums; }
    .fac-count:empty { display:none; }
    .desktop-btn--active .fac-count { background:var(--brand-soft); color:var(--brand); }

    .desktop-pill.fac-pill--warning { background:var(--warning-soft); color:var(--warning); }
    .desktop-pill.fac-pill--success { background:var(--success-soft); color:var(--success); }
    .desktop-pill.fac-pill--danger { background:var(--danger-soft); color:var(--danger); }
    .desktop-pill.fac-pill--lg { height:24px; padding:0 10px; font-size:.76rem; }

    .fac-num { text-align:right !important; font-variant-numeric:tabular-nums; white-space:nowrap; }
    .fac-note { display:block; margin-top:3px; font-size:.74rem; line-height:1.35; color:var(--text-2); }
    .fac-note--warning { color:var(--warning); }
    .fac-note--danger, .desktop-field small.fac-note--danger { color:var(--danger); font-weight:600; }
    .fac-note--success { color:var(--success); }
    .fac-actions { display:flex; align-items:center; gap:4px; flex-wrap:nowrap; }
    .fac-list { font-size:.86rem !important; }
    .fac-list tbody td { padding:9px 12px !important; }
    .fac-list .desktop-pill-list { max-width:240px; }
    /* En pantallas angostas se priorizan venta, total, situación y acciones. */
    @media (max-width:700px) {
        .desktop-pane:has(.fac-list) { --list-min-w:500px; }
        .fac-list th:nth-child(2), .fac-list td:nth-child(2), .fac-list th:nth-child(3), .fac-list td:nth-child(3),
        .fac-list th:nth-child(4), .fac-list td:nth-child(4) { display:none; }
        .fac-bar__hint { display:none; }
    }
</style>
@endpush
