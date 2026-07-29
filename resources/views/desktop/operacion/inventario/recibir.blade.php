@extends('layouts.desktop')

@section('title', 'Recibir mercancía')

@push('desktop-vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/select2/select2.css') }}" />
@endpush

@push('desktop-styles')
    <style>
        /* ===== Layout: pasos (main) + resumen económico (aside fijo) ===== */
        .desktop-rcb-layout { display: flex; height: 100%; min-height: 0; }
        .desktop-rcb-main { flex: 1 1 auto; min-width: 0; min-height: 0; overflow: auto; padding: 14px; display: flex; flex-direction: column; gap: 12px; }
        .desktop-rcb-aside { flex: 0 0 304px; min-height: 0; overflow: auto; border-left: 1px solid var(--stroke); background: var(--surface-alt); display: flex; flex-direction: column; }
        @media (max-width: 1024px) {
            .desktop-rcb-layout { flex-direction: column; }
            .desktop-rcb-aside { flex: 0 0 auto; border-left: 0; border-top: 1px solid var(--stroke); }
        }

        /* ===== Paso (tarjeta con número) ===== */
        .desktop-rcb-step { border: 1px solid var(--stroke); border-radius: var(--r-md); background: var(--surface); box-shadow: var(--shadow-2); }
        .desktop-rcb-step__head { display: flex; align-items: center; gap: 10px; padding: 9px 12px; border-bottom: 1px solid var(--divider); }
        .desktop-rcb-step__num { display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 50%; background: var(--brand); color: var(--on-brand); font-size: .76rem; font-weight: 800; flex: 0 0 auto; }
        .desktop-rcb-step__title { font-size: .88rem; font-weight: 700; }
        .desktop-rcb-step__hint { margin-left: auto; font-size: .73rem; color: var(--text-3); }
        .desktop-rcb-step__body { padding: 12px; }
        #rcb-filter-card { display: none !important; }

        .desktop-rcb-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px 12px; }
        .desktop-field--full { grid-column: 1 / -1; }
        .desktop-rcb-field-hidden { display: none; }
        .desktop-rcb-check { display: inline-flex; align-items: center; gap: 6px; font-size: .8rem; color: var(--text); white-space: nowrap; }
        .desktop-rcb-refrow { display: flex; gap: 8px; align-items: center; }

        /* ===== Chips (productos y atributos) ===== */
        .desktop-rcb-chips { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
        .desktop-rcb-chip { display: inline-flex; align-items: center; gap: 6px; height: 28px; padding: 0 4px 0 11px; border: 1px solid var(--stroke-strong); border-radius: 999px; background: var(--surface); font-size: .8rem; font-weight: 600; color: var(--text); }
        .desktop-rcb-chip__x, .desktop-rcb-chip__restore { display: inline-flex; align-items: center; justify-content: center; width: 18px; height: 18px; border: 0; border-radius: 50%; background: transparent; color: var(--text-3); cursor: pointer; font-size: 1rem; line-height: 1; }
        .desktop-rcb-chip__x:hover { background: var(--danger-soft); color: var(--danger); }
        .desktop-rcb-chip__restore { color: var(--brand); font-size: .85rem; }
        .desktop-rcb-chip--off { border-style: dashed; opacity: .6; }
        .desktop-rcb-picker { margin-top: 10px; }

        /* Grupo de atributo: botones toggle (todos visibles, multi-selección persistente) */
        .desktop-rcb-attrgroup { padding: 9px 0; border-top: 1px solid var(--divider); }
        .desktop-rcb-attrgroup:first-child { border-top: 0; padding-top: 0; }
        .desktop-rcb-attrgroup__title { display: flex; align-items: baseline; gap: 8px; font-size: .7rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: var(--text-2); margin-bottom: 6px; }
        .desktop-rcb-attrgroup__count { font-weight: 600; color: var(--text-3); text-transform: none; letter-spacing: 0; }
        .desktop-rcb-toggles { display: flex; flex-wrap: wrap; gap: 6px; }
        .desktop-rcb-tog { display: inline-flex; align-items: center; gap: 5px; height: 30px; padding: 0 13px; border: 1px solid var(--stroke-strong); border-radius: 999px; background: var(--surface); font: inherit; font-size: .8rem; color: var(--text); cursor: pointer; transition: background .1s ease, border-color .1s ease, color .1s ease; }
        .desktop-rcb-tog:hover { border-color: var(--brand); color: var(--brand); }
        .desktop-rcb-tog.is-active { background: var(--brand); border-color: var(--brand); color: var(--on-brand); font-weight: 600; }
        .desktop-rcb-tog.is-active::before { content: "✓"; font-size: .72rem; }

        .desktop-rcb-attrproducts { display: grid; gap: 12px; }
        .desktop-rcb-attrproduct { border: 1px solid var(--stroke); border-radius: var(--r-md); background: var(--surface); overflow: hidden; }
        .desktop-rcb-attrproduct__head { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; padding: 10px 12px; background: var(--surface-alt); border-bottom: 1px solid var(--divider); }
        .desktop-rcb-attrproduct__main { min-width: 0; display: grid; gap: 2px; }
        .desktop-rcb-attrproduct__name { font-size: .86rem; font-weight: 700; color: var(--text); }
        .desktop-rcb-attrproduct__meta { font-size: .74rem; color: var(--text-2); }
        .desktop-rcb-attrproduct__summary { font-size: .72rem; color: var(--text-3); text-align: right; white-space: nowrap; }
        .desktop-rcb-attrproduct__body { padding: 0 12px 12px; }
        .desktop-rcb-attrproduct__empty { padding-top: 10px; font-size: .8rem; color: var(--text-2); }
        .desktop-rcb-lines { display: grid; gap: 8px; }
        .desktop-rcb-line { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 10px; align-items: center; padding: 10px 12px; border: 1px solid var(--stroke); border-radius: var(--r-md); background: var(--surface); }
        .desktop-rcb-line__main { min-width: 0; display: grid; gap: 3px; }
        .desktop-rcb-line__top { display: flex; align-items: center; gap: 8px; min-width: 0; }
        .desktop-rcb-line__code { display: inline-flex; align-items: center; height: 22px; padding: 0 8px; border-radius: 999px; background: var(--surface-alt); border: 1px solid var(--stroke); font-size: .72rem; font-weight: 700; color: var(--text-2); white-space: nowrap; }
        .desktop-rcb-line__name { min-width: 0; font-size: .84rem; font-weight: 700; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .desktop-rcb-line__attrs { font-size: .76rem; color: var(--text-2); line-height: 1.35; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .desktop-rcb-line__actions { display: inline-flex; align-items: center; gap: 6px; }
        .desktop-rcb-note { font-size: .8rem; color: var(--text-2); line-height: 1.45; }

        .desktop-rcb-empty { color: var(--text-2); font-size: .82rem; }

        /* Modal de búsqueda de productos */
        .desktop-rcb-prodsearch { position: relative; display: flex; align-items: center; margin-bottom: 10px; }
        .desktop-rcb-prodsearch svg { position: absolute; left: 10px; width: 16px; height: 16px; color: var(--text-3); pointer-events: none; }
        .desktop-rcb-prodsearch input { width: 100%; height: 36px; padding: 0 12px 0 32px; border: 1px solid var(--stroke-strong); border-radius: var(--r-md); font: inherit; font-size: .86rem; background: var(--surface); }
        .desktop-rcb-prodsearch input:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 1px var(--brand); }
        .desktop-rcb-prodlist { max-height: 48vh; overflow: auto; border: 1px solid var(--stroke); border-radius: var(--r-md); }
        .desktop-rcb-prowitem { display: flex; align-items: center; gap: 10px; padding: 9px 12px; border-bottom: 1px solid var(--divider); cursor: pointer; }
        .desktop-rcb-prowitem:last-child { border-bottom: 0; }
        .desktop-rcb-prowitem:hover { background: var(--surface-sunken); }
        .desktop-rcb-prowitem.is-added { opacity: .55; cursor: default; }
        .desktop-rcb-prowitem input { width: 16px; height: 16px; flex: 0 0 auto; }
        .desktop-rcb-prowitem__main { min-width: 0; flex: 1 1 auto; }
        .desktop-rcb-prowitem__name { font-weight: 600; font-size: .85rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .desktop-rcb-prowitem__meta { font-size: .74rem; color: var(--text-2); }
        .desktop-rcb-prowitem__tag { font-size: .7rem; font-weight: 700; color: var(--success); }
        .desktop-rcb-prodfoot-count { font-size: .8rem; color: var(--text-2); margin-right: auto; }
        .desktop-rcb-prodmore { width: 100%; height: 34px; border: 1px dashed var(--stroke-strong); border-radius: var(--r-md); background: var(--surface); color: var(--text-2); font: inherit; font-size: .8rem; font-weight: 600; cursor: pointer; margin-top: 8px; }
        .desktop-rcb-prodmore:hover { border-color: var(--brand); color: var(--brand); }

        /* ===== Matriz (captura) — más aire, menos "Excel" ===== */
        .desktop-rcb-prodstate[hidden] { display: none; }
        .desktop-rcb-prodsel { display: grid; gap: 10px; }
        .desktop-rcb-prodsel__head { padding: 10px 12px; border: 1px solid var(--stroke); border-radius: var(--r-md); background: var(--surface-alt); }
        .desktop-rcb-prodsel__title { font-size: .86rem; font-weight: 700; color: var(--text); }
        .desktop-rcb-prodsel__meta { margin-top: 2px; font-size: .75rem; color: var(--text-2); }
        .desktop-rcb-prodsel__help { font-size: .76rem; color: var(--text-2); }

        .desktop-rcb-matrix { display: flex; flex-direction: column; gap: 16px; }
        .desktop-rcb-mp { border: 1px solid var(--stroke); border-radius: var(--r-md); overflow: hidden; }
        .desktop-rcb-mp__head { display: flex; align-items: flex-start; gap: 8px; padding: 6px 10px; background: var(--surface-alt); border-bottom: 1px solid var(--divider); }
        .desktop-rcb-mp__meta { display: grid; gap: 1px; min-width: 0; }
        .desktop-rcb-mp__name { font-weight: 700; font-size: .82rem; }
        .desktop-rcb-mp__sub { font-size: .73rem; color: var(--text-2); line-height: 1.25; }
        .desktop-rcb-mp__skus { margin-left: auto; font-size: .72rem; color: var(--text-3); font-weight: 600; white-space: nowrap; }
        .desktop-rcb-mwrap { overflow-x: auto; }
        .desktop-rcb-mtable { border-collapse: collapse; font-size: .8rem; width: 100%; }
        .desktop-rcb-mtable th, .desktop-rcb-mtable td { padding: 6px 10px; text-align: center; white-space: nowrap; border-bottom: 1px solid var(--divider); }
        .desktop-rcb-mtable thead th { background: var(--surface); font-size: .7rem; font-weight: 700; color: var(--text-2); border-bottom: 1px solid var(--stroke); }
        .desktop-rcb-mtable th.rowhead, .desktop-rcb-mtable td.rowhead { text-align: left; position: sticky; left: 0; background: var(--surface); font-weight: 600; z-index: 1; }
        .desktop-rcb-mtable tbody tr:hover td { background: rgba(47,111,237,.03); }
        .desktop-rcb-rowdel { border: 0; background: transparent; color: var(--text-3); cursor: pointer; font-size: .95rem; line-height: 1; padding: 0 6px 0 0; }
        .desktop-rcb-rowdel:hover { color: var(--danger); }
        .desktop-rcb-mtable .costrow td { background: rgba(47,111,237,.05); }
        .desktop-rcb-mtable .costrow .rowhead { font-size: .7rem; font-weight: 700; text-transform: uppercase; color: var(--text-3); }
        .desktop-rcb-mtable input { width: 96px; height: 30px; padding: 0 10px; text-align: right; border: 1px solid var(--stroke-strong); border-radius: var(--r-sm); font: inherit; font-size: .82rem; font-variant-numeric: tabular-nums; background: var(--surface); }
        .desktop-rcb-mtable input:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 1px var(--brand); }
        .desktop-rcb-mtable input.is-fallback { border-style: dashed; color: var(--text-2); }
        .desktop-rcb-mtable input.js-rcb-cant:focus { background: rgba(47,111,237,.06); }
        .desktop-rcb-mtable td.na { color: var(--text-3); font-size: .7rem; background: repeating-linear-gradient(135deg, var(--surface-sunken), var(--surface-sunken) 5px, transparent 5px, transparent 10px); cursor: not-allowed; }

        .desktop-rcb-legend { display: flex; flex-wrap: wrap; align-items: center; gap: 14px; margin-bottom: 12px; font-size: .74rem; color: var(--text-2); }
        .desktop-rcb-legend__item { display: inline-flex; align-items: center; gap: 6px; }
        .desktop-rcb-legend__swatch { width: 20px; height: 13px; border-radius: 3px; border: 1px solid var(--stroke-strong); }
        .desktop-rcb-legend__swatch--na { background: repeating-linear-gradient(135deg, var(--surface-sunken), var(--surface-sunken) 4px, transparent 4px, transparent 8px); }
        .desktop-rcb-legend__swatch--fb { border-style: dashed; }
        .desktop-rcb-legend__hint { margin-left: auto; color: var(--text-3); }
        .desktop-rcb-matrixbar { display: flex; align-items: center; gap: 10px; }

        /* ===== Resumen económico (aside) ===== */
        .desktop-rcb-sum { padding: 14px; display: flex; flex-direction: column; gap: 4px; flex: 1 1 auto; }
        .desktop-rcb-sum__title { font-size: .7rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--text-3); margin-bottom: 6px; }
        .desktop-rcb-sum__row { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; padding: 5px 0; font-size: .84rem; color: var(--text-2); }
        .desktop-rcb-sum__row b { color: var(--text); font-variant-numeric: tabular-nums; font-weight: 700; }
        .desktop-rcb-sum__field { padding: 7px 0; border-top: 1px solid var(--divider); }
        .desktop-rcb-sum__field > label { display: block; font-size: .72rem; font-weight: 600; color: var(--text-2); margin-bottom: 4px; }
        .desktop-rcb-sum__inline { display: flex; gap: 6px; align-items: center; }
        .desktop-rcb-sum__inline select, .desktop-rcb-sum__inline input, .desktop-rcb-sum__field > input {
            height: 30px; border: 1px solid var(--stroke-strong); border-radius: var(--r-sm); background: var(--surface);
            font: inherit; font-size: .82rem; padding: 0 8px; color: var(--text);
        }
        .desktop-rcb-sum__field > input, .desktop-rcb-sum__inline input[type="number"] { text-align: right; font-variant-numeric: tabular-nums; width: 100%; }
        .desktop-rcb-sum__inline select { flex: 1 1 auto; }
        .desktop-rcb-sum__calc { margin-top: 4px; text-align: right; font-size: .76rem; color: var(--text-3); font-variant-numeric: tabular-nums; }
        .desktop-rcb-sum__total { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; margin-top: 8px; padding-top: 10px; border-top: 2px solid var(--stroke-strong); }
        .desktop-rcb-sum__total span { font-size: .8rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: var(--text); }
        .desktop-rcb-sum__total b { font-size: 1.5rem; font-weight: 800; color: var(--brand); font-variant-numeric: tabular-nums; }
        .desktop-rcb-sum__actions { padding: 12px 14px; border-top: 1px solid var(--stroke); background: var(--surface); display: flex; flex-direction: column; gap: 8px; position: sticky; bottom: 0; }
        .desktop-rcb-sum__actions .desktop-btn { width: 100%; justify-content: center; }
        .desktop-rcb-sum__state { font-size: .73rem; color: var(--text-2); text-align: center; }

        /* ===== Lanzador Paso 4 ===== */
        .desktop-rcb-launch { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
        .desktop-rcb-launch__title { font-weight: 700; font-size: .9rem; }
        .desktop-rcb-launch__sub { font-size: .78rem; color: var(--text-2); margin-top: 2px; }
        .desktop-rcb-launch .desktop-btn { height: 40px; padding: 0 20px; font-size: .88rem; }

        /* ===== Captura en pantalla completa ===== */
        .desktop-rcbfs { position: fixed; inset: 0; z-index: var(--z-drawer); background: var(--bg); display: flex; flex-direction: column; }
        .desktop-rcbfs[hidden] { display: none; }
        .desktop-rcbfs__head { flex: 0 0 auto; display: flex; align-items: center; gap: 16px; padding: 8px 14px; background: var(--surface); border-bottom: 1px solid var(--stroke); }
        .desktop-rcbfs__id { min-width: 0; }
        .desktop-rcbfs__title { font-size: .95rem; font-weight: 700; line-height: 1.1; }
        .desktop-rcbfs__info { font-size: .74rem; color: var(--text-2); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 60vw; }
        .desktop-rcbfs__progress { margin-left: auto; font-size: .82rem; font-weight: 700; color: var(--brand); white-space: nowrap; }
        .desktop-rcbfs__tools { display: flex; align-items: center; gap: 8px; }
        .desktop-rcbfs__body { flex: 1 1 auto; min-height: 0; display: flex; }
        .desktop-rcbfs__main { flex: 1 1 auto; min-width: 0; min-height: 0; display: flex; flex-direction: column; }
        .desktop-rcbfs__legend { flex: 0 0 auto; display: flex; flex-wrap: wrap; align-items: center; gap: 14px; padding: 6px 14px; font-size: .74rem; color: var(--text-2); border-bottom: 1px solid var(--divider); }
        .desktop-rcbfs__matrix { flex: 1 1 auto; min-height: 0; overflow: auto; padding: 12px 14px; }
        .desktop-rcbfs__resumen { flex: 0 0 300px; min-height: 0; overflow: auto; border-left: 1px solid var(--stroke); background: var(--surface-alt); }
        .desktop-rcbfs__resumen[hidden] { display: none; }
        .desktop-rcbfs__foot { flex: 0 0 auto; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; padding: 8px 14px; background: var(--surface); border-top: 1px solid var(--stroke); }
        .desktop-rcbfs__totals { display: flex; gap: 22px; flex-wrap: wrap; }
        .desktop-rcbfs__tot { display: flex; flex-direction: column; line-height: 1.1; }
        .desktop-rcbfs__tot span { font-size: .64rem; font-weight: 700; text-transform: uppercase; letter-spacing: .02em; color: var(--text-3); }
        .desktop-rcbfs__tot b { font-size: 1rem; font-variant-numeric: tabular-nums; }
        .desktop-rcbfs__tot--grand b { font-size: 1.3rem; color: var(--brand); }
        .desktop-rcbfs__actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

        /* Bloque de acciones reubicable (config aside vs footer fullscreen) */
        .desktop-rcb-actions { display: flex; flex-direction: column; gap: 8px; }
        #rcb-config-aside #rcb-actions { padding: 12px 14px; border-top: 1px solid var(--stroke); position: sticky; bottom: 0; background: var(--surface); }
        #rcb-config-aside #rcb-actions .desktop-btn { width: 100%; justify-content: center; }
        #rcb-config-aside #rcb-actions .desktop-rcb-sum__state { text-align: center; }
        #rcb-fs-actions #rcb-actions { flex-direction: row; align-items: center; gap: 8px; }
        #rcb-fs-actions { display: inline-flex; }

        /* Matriz fullscreen: un solo contenedor scrollable (Excel), thead congelado */
        .desktop-rcbfs__matrix .desktop-rcb-matrix { gap: 18px; }
        .desktop-rcbfs__matrix .desktop-rcb-mwrap { overflow: visible; }
        .desktop-rcbfs__matrix .desktop-rcb-mp { overflow: visible; }
        .desktop-rcbfs__matrix .desktop-rcb-mtable thead th { position: sticky; top: 0; z-index: 2; }
        .desktop-rcbfs__matrix .desktop-rcb-mtable th.rowhead { z-index: 3; }

        /* Scrollbar grueso, siempre visible */
        .desktop-rcbfs__matrix { scrollbar-width: auto; scrollbar-color: var(--stroke-strong) var(--surface-sunken); }
        .desktop-rcbfs__matrix::-webkit-scrollbar { width: 14px; height: 14px; }
        .desktop-rcbfs__matrix::-webkit-scrollbar-track { background: var(--surface-sunken); }
        .desktop-rcbfs__matrix::-webkit-scrollbar-thumb { background: var(--stroke-strong); border-radius: 999px; border: 3px solid var(--surface-sunken); }
        .desktop-rcbfs__matrix::-webkit-scrollbar-thumb:hover { background: var(--text-3); }
        .desktop-rcbfs__matrix::-webkit-scrollbar-corner { background: var(--surface-sunken); }
        @media (max-width: 900px) { .desktop-rcbfs__resumen { flex-basis: 70%; } }

        /* ===== Densidad de la matriz en fullscreen (más filas/columnas visibles) ===== */
        .desktop-rcbfs__matrix .desktop-rcb-matrix { gap: 10px; }
        .desktop-rcbfs__matrix .desktop-rcb-mtable { width: 100%; }
        .desktop-rcbfs__matrix .desktop-rcb-mtable th,
        .desktop-rcbfs__matrix .desktop-rcb-mtable td { padding: 2px 5px; }
        .desktop-rcbfs__matrix .desktop-rcb-mtable input { width: 78px; height: 26px; padding: 0 8px; }
        .desktop-rcbfs__matrix .costrow td { padding-top: 1px; padding-bottom: 1px; }
        /* Celda capturada (7) */
        .desktop-rcb-mtable input.js-rcb-cant.has-val { background: rgba(17,121,80,.1); border-color: rgba(17,121,80,.45); color: var(--success); font-weight: 700; }

        /* ===== Header compacto (2) ===== */
        .desktop-rcbfs__head { min-height: 46px; padding: 0 12px; gap: 12px; }
        .desktop-rcbfs__head .desktop-btn { height: 30px; }
        .desktop-rcbfs__info { font-size: .8rem; font-weight: 600; color: var(--text); }
        .desktop-rcbfs__group { display: flex; align-items: center; gap: 8px; }
        .desktop-rcbfs__grouplbl { font-size: .68rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: var(--text-3); white-space: nowrap; }
        .desktop-rcbfs__seg { display: inline-flex; gap: 2px; padding: 2px; background: var(--surface-sunken); border-radius: var(--r-md); }
        .desktop-rcbfs__seg button { height: 26px; padding: 0 12px; border: 0; border-radius: var(--r-sm); background: transparent; font: inherit; font-size: .78rem; font-weight: 600; color: var(--text-2); cursor: pointer; white-space: nowrap; }
        .desktop-rcbfs__seg button.is-active { background: var(--surface); color: var(--brand); box-shadow: var(--shadow-2); }
        /* Progreso (6) */
        .desktop-rcbfs__prog { display: flex; flex-direction: column; gap: 3px; min-width: 190px; }
        .desktop-rcbfs__progtxt { font-size: .72rem; font-weight: 600; color: var(--text-2); white-space: nowrap; }
        .desktop-rcbfs__progbar { height: 6px; border-radius: 999px; background: var(--surface-sunken); overflow: hidden; }
        .desktop-rcbfs__progbar > i { display: block; height: 100%; width: 0; background: var(--brand); border-radius: 999px; transition: width .2s ease; }

        /* ===== Footer compacto (3) ===== */
        .desktop-rcbfs__foot { min-height: 52px; padding: 0 12px; }
        .desktop-rcbfs__totals { gap: 18px; }
        .desktop-rcbfs__tot { flex-direction: row; align-items: baseline; gap: 5px; }
        .desktop-rcbfs__tot span { font-size: .72rem; font-weight: 600; }
        .desktop-rcbfs__tot b { font-size: .92rem; }
        .desktop-rcbfs__tot--grand b { font-size: 1.1rem; }

        /* ===== Panel de navegación de productos (8) ===== */
        .desktop-rcbfs__main { position: relative; }
        .desktop-rcbfs__navbtn { display: inline-flex; align-items: center; gap: 6px; }
        .desktop-rcbfs__nav { position: absolute; top: 8px; left: 8px; width: 230px; max-height: calc(100% - 16px); overflow: auto; background: var(--surface); border: 1px solid var(--stroke); border-radius: var(--r-md); box-shadow: var(--shadow-16); z-index: 6; padding: 6px; }
        .desktop-rcbfs__nav[hidden] { display: none; }
        .desktop-rcbfs__nav-title { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: var(--text-3); padding: 4px 8px; }
        .desktop-rcbfs__navitem { display: flex; align-items: center; justify-content: space-between; gap: 8px; width: 100%; padding: 6px 8px; border: 0; border-radius: var(--r-sm); background: transparent; font: inherit; font-size: .8rem; text-align: left; color: var(--text); cursor: pointer; }
        .desktop-rcbfs__navitem:hover { background: var(--brand-soft); color: var(--brand); }
        .desktop-rcbfs__navitem b { font-variant-numeric: tabular-nums; color: var(--text-3); font-weight: 600; font-size: .74rem; }
        .desktop-rcbfs__navitem.is-done b { color: var(--success); }

        .select2-container { width: 100% !important; }
        .select2-container--default .select2-selection--single { height: 34px; min-height: 34px; border: 1px solid var(--stroke-strong); border-radius: var(--r-md); position: relative; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 32px; font-size: .84rem; padding-left: 10px; padding-right: 30px; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 34px; top: 50%; transform: translateY(-50%); }
        .select2-container--open .select2-dropdown,
        .select2-dropdown {
            background: var(--surface, #fff);
            border: 1px solid var(--stroke-strong);
            border-radius: var(--r-md);
            overflow: hidden;
            box-shadow: var(--shadow-2);
            z-index: 9000;
        }
        .select2-results {
            background: var(--surface, #fff);
        }
        .select2-results__options {
            background: var(--surface, #fff);
        }
        .select2-results__option {
            background: var(--surface, #fff);
            color: var(--text);
            padding: 7px 12px;
            font-size: .84rem;
        }
        .select2-search--dropdown {
            padding: 8px;
            background: var(--surface, #fff);
        }
        .select2-search--dropdown .select2-search__field {
            border: 1px solid var(--stroke-strong);
            border-radius: var(--r-sm);
            background: var(--surface, #fff);
            color: var(--text);
            padding: 6px 8px;
        }
        .select2-results__option--highlighted.select2-results__option--selectable {
            background: var(--brand) !important;
            color: var(--on-brand) !important;
        }
    </style>
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        @php $activeSubmenu = 'recepciones'; @endphp
        @include('desktop.operacion.inventario._subnav')
    </div>
    <div class="desktop-toolbar__group">
        <a href="{{ route('desktop.operacion.inventario.recepciones.index') }}" class="desktop-btn desktop-btn--default">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Recepciones
        </a>
    </div>
@endsection

@section('content')
    <section class="desktop-pane">
        <div class="desktop-rcb-layout">
            <div class="desktop-rcb-main">
                {{-- Paso 1 · Datos de recepción --}}
                <div class="desktop-rcb-step">
                    <div class="desktop-rcb-step__head">
                        <span class="desktop-rcb-step__num">1</span>
                        <span class="desktop-rcb-step__title">Datos de recepción</span>
                        <span class="desktop-rcb-step__hint">Información administrativa de la compra</span>
                    </div>
                    <div class="desktop-rcb-step__body">
                        <div class="desktop-rcb-grid">
                            <div class="desktop-field">
                                <label for="rcb-tipo">Tipo de entrada *</label>
                                <select id="rcb-tipo">
                                    <option value="compra_factura">Compra con factura</option>
                                    <option value="compra_remision">Compra con remisión</option>
                                    <option value="entrada_normal">Entrada normal</option>
                                </select>
                            </div>
                            <div class="desktop-field">
                                <label for="rcb-scl">Sucursal *</label>
                                <select id="rcb-scl">
                                    <option value="">Selecciona…</option>
                                    @foreach($opciones['sucursales'] as $s)
                                        <option value="{{ $s->scl_id }}" @selected((int) $s->scl_id === (int) ($defaultSucursalId ?? 0))>{{ $s->scl_nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="desktop-field">
                                <label for="rcb-alm">Almacén *</label>
                                <select id="rcb-alm">
                                    <option value="">Selecciona…</option>
                                    @foreach($opciones['almacenes'] as $a)
                                        <option value="{{ $a->alm_id }}" data-scl="{{ $a->alm_scl_id }}">{{ $a->alm_nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="desktop-field">
                                <label for="rcb-prv">Proveedor</label>
                                <select id="rcb-prv" data-placeholder="Buscar proveedor">
                                    <option value="">Sin proveedor</option>
                                    @foreach($proveedores as $p)
                                        <option value="{{ $p->prv_id }}">
                                            {{ $p->prv_nombre_empresa }}{{ $p->prv_razon_social ? ' - ' . $p->prv_razon_social : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="desktop-field" id="rcb-ref-wrap">
                                <label for="rcb-ref">Factura / Referencia</label>
                                <div class="desktop-rcb-refrow">
                                    <input type="text" id="rcb-ref" maxlength="120" style="flex:1 1 auto;">
                                    <label class="desktop-rcb-check"><input type="checkbox" id="rcb-ref-na"> N/A</label>
                                </div>
                            </div>
                            <div class="desktop-field desktop-rcb-field-hidden" id="rcb-emision-wrap">
                                <label for="rcb-emision">Fecha factura / nota</label>
                                <input type="datetime-local" id="rcb-emision">
                            </div>
                            <div class="desktop-field">
                                <label>Usuario / Marcas</label>
                                <input type="text" id="rcb-marcas" readonly placeholder="Marcas según productos" value="">
                            </div>
                            <div class="desktop-field desktop-field--full">
                                <label for="rcb-obs">Observaciones</label>
                                <textarea id="rcb-obs" maxlength="1500" rows="2" placeholder="Defectos, faltantes, diferencias, comentarios del proveedor…"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Paso 2 · Productos agregados --}}
                <div class="desktop-rcb-step">
                    <div class="desktop-rcb-step__head">
                        <span class="desktop-rcb-step__num">2</span>
                        <span class="desktop-rcb-step__title">Productos agregados</span>
                        <span class="desktop-rcb-step__hint">Busca en el catálogo y agrégalos</span>
                    </div>
                    <div class="desktop-rcb-step__body">
                        <div class="desktop-rcb-lines" id="rcb-prods-chips">
                            <span class="desktop-rcb-empty">Sin productos. Usa "Buscar productos" para agregarlos.</span>
                        </div>
                        <div class="desktop-rcb-picker">
                            <button type="button" class="desktop-btn desktop-btn--primary" id="rcb-open-prod">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                                Buscar productos
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Paso 3 · Selección de atributos --}}
                <div class="desktop-rcb-step" id="rcb-filter-card" hidden>
                    <div class="desktop-rcb-step__head">
                        <span class="desktop-rcb-step__num">3</span>
                        <span class="desktop-rcb-step__title">Selección de atributos</span>
                        <span class="desktop-rcb-step__hint">Sin selección = se muestran todos</span>
                    </div>
                    <div class="desktop-rcb-step__body">
                        <div id="rcb-attr-filter"></div>
                    </div>
                </div>

                {{-- Paso 4 · Captura (lanzador a pantalla completa) --}}
                <div class="desktop-rcb-step">
                    <div class="desktop-rcb-step__head">
                        <span class="desktop-rcb-step__num">3</span>
                        <span class="desktop-rcb-step__title">Captura de recepción</span>
                        <span class="desktop-rcb-step__hint">Espacio completo para captura masiva</span>
                    </div>
                    <div class="desktop-rcb-step__body">
                        <div class="desktop-rcb-launch">
                            <div class="desktop-rcb-launch__txt">
                                <div class="desktop-rcb-launch__title">Capturar mercancía en pantalla completa</div>
                                <div class="desktop-rcb-launch__sub" id="rcb-launch-sub">Agrega productos y elige el atributo dominante dentro de la captura.</div>
                            </div>
                            <button type="button" class="desktop-btn desktop-btn--primary" id="rcb-open-capture">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3M16 3h3a2 2 0 0 1 2 2v3M21 16v3a2 2 0 0 1-2 2h-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
                                Capturar mercancía
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Resumen económico (reubicable: aquí en configuración, se mueve al fullscreen al capturar) --}}
            <aside class="desktop-rcb-aside" id="rcb-config-aside">
                <div class="desktop-rcb-sum" id="rcb-econ">
                    <div class="desktop-rcb-sum__title">Resumen económico</div>
                    <div class="desktop-rcb-sum__row"><span>Artículos</span><b id="rcb-t-art">0</b></div>
                    <div class="desktop-rcb-sum__row" id="rcb-w-sub"><span>Subtotal</span><b id="rcb-t-sub">$ 0.00</b></div>
                    <div class="desktop-rcb-sum__field">
                        <label>Descuento</label>
                        <div class="desktop-rcb-sum__inline">
                            <select id="rcb-desc-tipo">
                                <option value="ninguno">Sin descuento</option>
                                <option value="porcentaje">Porcentaje</option>
                                <option value="importe">Importe</option>
                            </select>
                            <input type="number" id="rcb-desc-valor" min="0" step="0.01" value="0" style="max-width:96px;">
                        </div>
                        <div class="desktop-rcb-sum__calc">− <span id="rcb-t-desc">$ 0.00</span></div>
                    </div>
                    <div class="desktop-rcb-sum__field" id="rcb-w-iva">
                        <label>IVA</label>
                        <div class="desktop-rcb-sum__inline">
                            <input type="number" id="rcb-iva" min="0" max="100" step="0.01" value="16" style="max-width:90px;">
                            <label class="desktop-rcb-check"><input type="checkbox" id="rcb-iva-on" checked> aplicar</label>
                        </div>
                        <div class="desktop-rcb-sum__calc">+ <span id="rcb-t-iva">$ 0.00</span></div>
                    </div>
                    <div class="desktop-rcb-sum__field">
                        <label>Flete</label>
                        <input type="number" id="rcb-flete" min="0" step="0.01" value="0">
                        <div class="desktop-rcb-sum__calc">+ <span id="rcb-t-flete">$ 0.00</span></div>
                    </div>
                    <div class="desktop-rcb-sum__total"><span>Total</span><b id="rcb-t-total">$ 0.00</b></div>
                </div>
                <div id="rcb-actions" class="desktop-rcb-actions">
                    <span class="desktop-rcb-sum__state" id="rcb-estado">Sin borrador en edición.</span>
                    <button type="button" class="desktop-btn desktop-btn--default" id="rcb-borrador">Guardar borrador</button>
                    <button type="button" class="desktop-btn desktop-btn--primary" id="rcb-confirmar">Confirmar recepción</button>
                </div>
            </aside>
        </div>
    </section>

    {{-- ===== Captura en pantalla completa ===== --}}
    <div class="desktop-rcbfs" id="rcb-fs" hidden aria-hidden="true">
        <header class="desktop-rcbfs__head">
            <button type="button" class="desktop-btn desktop-btn--ghost" id="rcb-fs-back" title="Volver a configuración">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                Volver
            </button>
            <button type="button" class="desktop-btn desktop-btn--ghost desktop-rcbfs__navbtn" id="rcb-nav-toggle" title="Navegar productos">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                Productos
            </button>
            <div class="desktop-rcbfs__info" id="rcb-fs-info">Recepción de mercancía</div>
            <div class="desktop-rcbfs__group">
                <span class="desktop-rcbfs__grouplbl">Agrupar por</span>
                <div class="desktop-rcbfs__seg" id="rcb-dominante-seg"></div>
                <select id="rcb-dominante" hidden><option value="">—</option></select>
            </div>
            <div class="desktop-rcbfs__prog">
                <div class="desktop-rcbfs__progtxt" id="rcb-fs-progress">Productos 0/0 · SKU 0/0</div>
                <div class="desktop-rcbfs__progbar"><i id="rcb-fs-progbar"></i></div>
            </div>
            <button type="button" class="desktop-btn desktop-btn--ghost" id="rcb-restaurar" hidden title="Restaurar quitados">↺</button>
        </header>

        <div class="desktop-rcbfs__body">
            <main class="desktop-rcbfs__main">
                <div class="desktop-rcbfs__nav" id="rcb-nav" hidden>
                    <div class="desktop-rcbfs__nav-title">Ir a producto</div>
                    <div id="rcb-nav-list"></div>
                </div>
                <div class="desktop-rcbfs__legend">
                    <span class="desktop-rcb-legend__item"><span class="desktop-rcb-legend__swatch"></span> Aplica</span>
                    <span class="desktop-rcb-legend__item"><span class="desktop-rcb-legend__swatch desktop-rcb-legend__swatch--na"></span> No aplica</span>
                    <span class="desktop-rcb-legend__item"><span class="desktop-rcb-legend__swatch desktop-rcb-legend__swatch--fb"></span> Costo fallback</span>
                    <span class="desktop-rcb-legend__hint">Enter ↓ · Tab → · Shift+Tab ← · flechas: mover celda</span>
                </div>
                <div id="rcb-matrix" class="desktop-rcb-matrix desktop-rcbfs__matrix">
                    <div class="desktop-rcb-empty">Agrega productos y elige un atributo dominante para capturar cantidades.</div>
                </div>
            </main>

            <aside class="desktop-rcbfs__resumen" id="rcb-fs-resumen" hidden></aside>
        </div>

        <footer class="desktop-rcbfs__foot">
            <div class="desktop-rcbfs__totals">
                <div class="desktop-rcbfs__tot"><span>Artículos</span><b class="js-rcb-art-mirror">0</b></div>
                <div class="desktop-rcbfs__tot"><span>Subtotal</span><b class="js-rcb-sub-mirror">$ 0.00</b></div>
                <div class="desktop-rcbfs__tot"><span>IVA</span><b class="js-rcb-iva-mirror">$ 0.00</b></div>
                <div class="desktop-rcbfs__tot desktop-rcbfs__tot--grand"><span>Total</span><b class="js-rcb-total-mirror">$ 0.00</b></div>
            </div>
            <div class="desktop-rcbfs__actions">
                <button type="button" class="desktop-btn desktop-btn--default" id="rcb-toggle-resumen">Mostrar resumen</button>
                <div id="rcb-fs-actions"></div>
            </div>
        </footer>
    </div>

    <div class="desktop-modal" id="rcb-prod-modal" aria-hidden="true">
        <div class="desktop-modal__dialog" style="max-width:640px;">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="rcb-prod-modal-title">Buscar productos</div>
                <button type="button" class="desktop-modal__close" data-close-prod aria-label="Cerrar">&times;</button>
            </div>
            <div class="desktop-modal__body">
                <div class="desktop-rcb-prodstate" id="rcb-prod-search-state">
                    <div class="desktop-rcb-prodsearch">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="search" id="rcb-prod-q" placeholder="Busca por código o nombre…" autocomplete="off">
                </div>
                <div class="desktop-rcb-prodlist" id="rcb-prod-list"></div>
                <button type="button" class="desktop-rcb-prodmore" id="rcb-prod-more" hidden>Cargar más</button>
                </div>
                <div class="desktop-rcb-prodstate" id="rcb-prod-attr-state" hidden>
                    <div class="desktop-rcb-prodsel">
                        <div class="desktop-rcb-prodsel__head">
                            <div class="desktop-rcb-prodsel__title" id="rcb-prod-picked-name">Producto</div>
                            <div class="desktop-rcb-prodsel__meta" id="rcb-prod-picked-meta"></div>
                        </div>
                        <div class="desktop-rcb-prodsel__help">Sin selecciÃ³n = se incluyen todas las variantes disponibles para ese atributo.</div>
                        <div id="rcb-prod-attrs"></div>
                    </div>
                </div>
            </div>
            <div class="desktop-modal__foot">
                <span class="desktop-rcb-prodfoot-count" id="rcb-prod-count">Selecciona un producto</span>
                <button type="button" class="desktop-btn desktop-btn--default" id="rcb-prod-back" hidden>Volver</button>
                <button type="button" class="desktop-btn desktop-btn--default" data-close-prod>Cancelar</button>
                <button type="button" class="desktop-btn desktop-btn--primary" id="rcb-prod-add">Agregar producto</button>
            </div>
        </div>
    </div>

    <div class="desktop-modal" id="rcb-confirm-modal" aria-hidden="true">
        <div class="desktop-modal__dialog" style="max-width:440px;">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title">Confirmar recepción</div>
                <button type="button" class="desktop-modal__close" data-close-rcb aria-label="Cerrar">&times;</button>
            </div>
            <div class="desktop-modal__body">
                <p style="margin:0 0 12px; font-size:.84rem; color:var(--text-2);">Se generarán las entradas de inventario. Captura tu contraseña para autorizar.</p>
                <div class="desktop-field"><label for="rcb-pass">Contraseña *</label><input type="password" id="rcb-pass" autocomplete="new-password"></div>
            </div>
            <div class="desktop-modal__foot">
                <button type="button" class="desktop-btn desktop-btn--default" data-close-rcb>Cancelar</button>
                <button type="button" class="desktop-btn desktop-btn--primary" id="rcb-confirm-go">Confirmar y registrar</button>
            </div>
        </div>
    </div>
@endsection

@push('desktop-vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/select2/select2.js') }}"></script>
@endpush

@push('desktop-scripts')
    <script>
        (function () {
            const rutas = {
                buscarProductos: @json(route('desktop.operacion.inventario.recibir.productos.buscar')),
                matriz: @json(url('/desktop/operacion/inventario/recibir/productos/__ID__/matriz')),
                borrador: @json(route('desktop.operacion.inventario.recibir.borrador')),
                confirmar: @json(route('desktop.operacion.inventario.recibir.confirmar')),
                recepciones: @json(route('desktop.operacion.inventario.recepciones.index')),
            };
            const csrf = @json(csrf_token());
            const borradorInicial = @json($borrador);

            const state = {
                rmeId: null, rmeFolio: null, rmeEstado: null,
                meta: {},
                costosColumna: {},
                costosEditados: {},
                filtrosAtributos: {},
                filtrosPorProducto: {},
                lineasProductos: [],
                lineaSeq: 1,
                modalProducto: { modo: 'crear', paso: 'buscar', prdId: null, lineId: null, filtros: {} },
                quitados: {},
                filasExcluidas: {},
                pendiente: null,
            };
            function productosActivos() {
                const ids = [];
                state.lineasProductos.forEach(linea => {
                    const prdId = Number(linea.prd_id || 0);
                    if (!prdId || ids.includes(prdId) || !state.meta[prdId]?.producto) return;
                    ids.push(prdId);
                });
                return ids.map(id => state.meta[id]).filter(Boolean);
            }

            function esc(t) { return $('<div>').text(t ?? '').html(); }
            function money(v) { return '$ ' + Number(v || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
            function intNN(v) { v = Math.floor(Number(v || 0)); return v > 0 ? v : 0; }
            function numNN(v) { v = Number(v || 0); return v > 0 ? Number(v.toFixed(2)) : 0; }
            function normalizarUnidadTexto(v) { return String(v || '').trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, ''); }
            function productoPermiteDecimal(producto) {
                const tipoCantidad = normalizarUnidadTexto(producto?.unidad_tipo_cantidad);
                if (tipoCantidad === 'decimal') return true;
                if (tipoCantidad === 'entero') return false;
                const codigo = normalizarUnidadTexto(producto?.unidad_codigo);
                const nombre = normalizarUnidadTexto(producto?.unidad_nombre);
                const texto = [codigo, nombre].filter(Boolean).join(' ');
                if (/(^|\\s)(m|mt|mts|metro|metros)(\\s|$)/.test(texto)) return true;
                if (/(^|\\s)(pza|pieza|piezas)(\\s|$)/.test(texto)) return false;
                return false;
            }
            function parseCantidadValor(raw, allowDecimals) {
                const normalizado = String(raw ?? '').replace(',', '.').replace(/[^0-9.\-]/g, '');
                const numero = Number(normalizado);
                if (!Number.isFinite(numero) || numero <= 0) return 0;
                return allowDecimals ? Number(numero.toFixed(2)) : Math.floor(numero);
            }

            // Orden natural de tallas/variantes (negocio > alfabético)
            const PESO_TALLA = {
                xxxs: 10, '3xs': 10, xxs: 20, '2xs': 20, xs: 30,
                s: 40, small: 40, ch: 40, chica: 40, chico: 40,
                m: 50, med: 50, mediana: 50, mediano: 50, medium: 50,
                g: 60, l: 60, grande: 60, large: 60,
                xl: 70, xg: 70, eg: 70,
                xxl: 80, '2xl': 80, exg: 80, xxg: 80,
                xxxl: 90, '3xl': 90,
                unitalla: 200, unica: 200, unico: 200, std: 210, estandar: 210, estandar2: 210
            };
            function pesoTalla(valor) {
                const texto = String(valor ?? '').trim();
                const norm = texto.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '');
                const m = texto.match(/^\s*(\d+(?:\.\d+)?)/);
                if (m) return { grupo: 0, peso: parseFloat(m[1]), suf: texto.slice(m[0].length).trim().toLowerCase(), texto };
                if (PESO_TALLA[norm] !== undefined) return { grupo: 1, peso: PESO_TALLA[norm], suf: '', texto };
                return { grupo: 2, peso: 9999, suf: '', texto };
            }
            function compararTalla(a, b) {
                const pa = pesoTalla(a), pb = pesoTalla(b);
                if (pa.grupo !== pb.grupo) return pa.grupo - pb.grupo;
                if (pa.peso !== pb.peso) return pa.peso - pb.peso;
                if (pa.suf !== pb.suf) { if (!pa.suf) return -1; if (!pb.suf) return 1; return pa.suf.localeCompare(pb.suf, 'es'); }
                return String(pa.texto).localeCompare(String(pb.texto), 'es', { numeric: true });
            }
            function urlId(tpl, id) { return tpl.replace('__ID__', encodeURIComponent(String(id))); }
            function notify(t, m, type) { if (window.DesktopUI) DesktopUI.message(t, m, type); else if (window.AppUI?.showMessage) window.AppUI.showMessage(t, m, type); }

            function atrNombrePorId(atrId) {
                atrId = Number(atrId);
                for (const m of Object.values(state.meta)) {
                    const found = (m.atributos || []).find(a => Number(a.atr_id) === atrId);
                    if (found) return found.atr_nombre;
                }
                return null;
            }

            function crearLineId() { return 'ln-' + (state.lineaSeq++); }

            function mapaAtributosProducto(metaProducto) {
                const mapa = {};
                (metaProducto?.lineas || []).forEach(l => {
                    Object.entries(l.atributos || {}).forEach(([k, v]) => {
                        k = String(k).trim();
                        if (!k) return;
                        (mapa[k] = mapa[k] || new Set()).add(String(v || 'Sin valor').trim());
                    });
                });
                return mapa;
            }

            function normalizarFiltrosRuntime(filtros) {
                return Object.fromEntries(Object.entries(filtros || {}).map(([atr, vals]) => {
                    if (vals instanceof Set) return [atr, new Set(Array.from(vals))];
                    return [atr, new Set(vals || [])];
                }));
            }

            function serializarFiltrosRuntime(filtros) {
                return Object.fromEntries(Object.entries(filtros || {}).map(([atr, set]) => [atr, Array.from(set || [])]));
            }

            function lineasDeProducto(prdId) {
                prdId = Number(prdId);
                return state.lineasProductos.filter(linea => Number(linea.prd_id || 0) === prdId);
            }

            function filtrosUnionProducto(prdId) {
                const union = {};
                lineasDeProducto(prdId).forEach(linea => {
                    Object.entries(linea.filtros || {}).forEach(([atr, set]) => {
                        if (!set || !set.size) return;
                        union[atr] = union[atr] || new Set();
                        set.forEach(v => union[atr].add(v));
                    });
                });
                return union;
            }

            function asegurarFiltrosProducto(prdId) {
                return filtrosUnionProducto(prdId);
            }

            function serializarFiltrosPorProducto() {
                const serial = {};
                productosActivos().forEach(m => {
                    const prdId = Number(m.producto?.prd_id || 0);
                    serial[prdId] = serializarFiltrosRuntime(filtrosUnionProducto(prdId));
                });
                return serial;
            }

            function serializarFiltrosGlobalDesdeProductos() {
                const globales = {};
                productosActivos().forEach(m => {
                    const filtros = filtrosUnionProducto(Number(m.producto?.prd_id || 0));
                    Object.entries(filtros || {}).forEach(([atr, set]) => {
                        if (!set || !set.size) return;
                        globales[atr] = globales[atr] || new Set();
                        set.forEach(v => globales[atr].add(v));
                    });
                });
                return Object.fromEntries(Object.entries(globales).map(([atr, set]) => [atr, Array.from(set)]));
            }

            function serializarLineasProductos() {
                return state.lineasProductos.map(linea => ({
                    line_id: linea.line_id,
                    prd_id: linea.prd_id,
                    filtros: serializarFiltrosRuntime(linea.filtros),
                }));
            }

            function initProveedorSelect() {
                const $select = $('#rcb-prv');
                if (!$select.length) return;
                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }
                $select.select2({
                    width: '100%',
                    allowClear: true,
                    placeholder: $select.data('placeholder') || 'Buscar proveedor',
                    dropdownParent: $select.parent()
                });
            }

            function syncAlmacenes() {
                const scl = String($('#rcb-scl').val() || '');
                const actual = String($('#rcb-alm').val() || '');
                $('#rcb-alm option').each(function () {
                    if (!this.value) return;
                    $(this).prop('hidden', scl && String($(this).data('scl')) !== scl);
                });
                if (actual && $('#rcb-alm option[value="' + actual + '"]:not([hidden])').length === 0) $('#rcb-alm').val('');
            }

            function aplicarUITipo() {
                const tipo = $('#rcb-tipo').val();
                const esFactura = tipo === 'compra_factura';
                const esCompra = esFactura || tipo === 'compra_remision';
                const esRemision = tipo === 'compra_remision';

                $('#rcb-emision-wrap').toggleClass('desktop-rcb-field-hidden', !esCompra);

                $('#rcb-iva-on').prop('disabled', !esFactura);
                if (!esFactura) {
                    $('#rcb-iva-on').prop('checked', false);
                    $('#rcb-iva').prop('disabled', true).val('0');
                } else {
                    $('#rcb-iva-on').prop('checked', true);
                    $('#rcb-iva').prop('disabled', false);
                    if (Number($('#rcb-iva').val() || 0) <= 0) $('#rcb-iva').val('16');
                }

                // Resumen por tipo: factura = Subtotal/Desc/Flete/IVA/Total · remisión = Desc/Flete/Total
                $('#rcb-w-sub').toggle(!esRemision);
                $('#rcb-w-iva').toggle(esFactura);

                toggleRefNa();
                calcTotales();
            }

            function toggleRefNa() {
                const esCompra = $('#rcb-tipo').val() !== 'entrada_normal';
                const na = $('#rcb-ref-na').is(':checked');
                $('#rcb-ref-na').prop('disabled', !esCompra);
                if (!esCompra) {
                    $('#rcb-ref-na').prop('checked', false);
                    $('#rcb-ref').prop('readonly', false);
                } else if (na) {
                    $('#rcb-ref').val('N/A').prop('readonly', true);
                } else {
                    if (String($('#rcb-ref').val() || '').trim().toUpperCase() === 'N/A') $('#rcb-ref').val('');
                    $('#rcb-ref').prop('readonly', false);
                }
            }

            function seleccionarPorTexto($sel, terminos) {
                const opts = $sel.find('option').not('[hidden]');
                for (const t of terminos) {
                    const o = opts.filter((i, el) => String(el.text || '').toLowerCase().includes(t)).first();
                    if (o.length) { $sel.val(o.val()); return true; }
                }
                return false;
            }
            function sugerirSclAlm() {
                const tipo = $('#rcb-tipo').val();
                if (tipo !== 'compra_remision' && tipo !== 'compra_factura') return;
                if (seleccionarPorTexto($('#rcb-scl'), ['matriz comitan', 'casa matriz'])) syncAlmacenes();
                const terms = tipo === 'compra_factura'
                    ? ['la i. suriana', 'productos con factura', 'factura']
                    : ['i. suriana', 'remision', 'no factura'];
                seleccionarPorTexto($('#rcb-alm'), terms);
            }

            function actualizarMarcas() {
                const marcas = [...new Set(productosActivos().map(m => m.producto?.marca_nombre).filter(Boolean))];
                $('#rcb-marcas').val(marcas.join(', '));
            }

            function poblarDominantes() {
                const map = {};
                productosActivos().forEach(m => (m.atributos || []).forEach(a => { map[a.atr_id] = a.atr_nombre; }));
                const actual = $('#rcb-dominante').val();
                const sug = productosActivos()[0]?.dominante_sugerido_atr_id;
                const opts = ['<option value="">Atributo dominante</option>']
                    .concat(Object.entries(map).map(([id, nom]) => '<option value="' + id + '">' + esc(nom) + '</option>'));
                $('#rcb-dominante').html(opts.join(''));
                const elegido = actual && map[actual] ? actual : (sug && map[sug] ? sug : (Object.keys(map)[0] || ''));
                $('#rcb-dominante').val(String(elegido || ''));
                // Control segmentado "Agrupar por"
                const segHtml = Object.entries(map).map(([id, nom]) =>
                    '<button type="button" data-dom="' + id + '" class="' + (String(id) === String(elegido) ? 'is-active' : '') + '">' + esc(nom) + '</button>'
                ).join('') || '<span style="font-size:.74rem;color:var(--text-3);padding:0 6px;">Sin atributos</span>';
                $('#rcb-dominante-seg').html(segHtml);
            }

            function renderNav() {
                const items = productosActivos();
                if (!items.length) { $('#rcb-nav-list').html('<div class="desktop-rcb-empty" style="padding:8px;">Sin productos.</div>'); return; }
                const cap = {}, tot = {};
                $('#rcb-matrix .js-rcb-cant').each(function () {
                    const pid = $(this).data('prd-id'); tot[pid] = (tot[pid] || 0) + 1;
                    if (intNN($(this).val()) > 0) cap[pid] = (cap[pid] || 0) + 1;
                });
                $('#rcb-nav-list').html(items.map(m => {
                    const p = m.producto; const c = cap[p.prd_id] || 0, t = tot[p.prd_id] || 0;
                    const done = t > 0 && c >= t;
                    return '<button type="button" class="desktop-rcbfs__navitem' + (done ? ' is-done' : '') + '" data-go="' + p.prd_id + '">' +
                        '<span>' + esc(p.prd_nombre) + '</span><b>' + c + '/' + t + '</b></button>';
                }).join(''));
            }

            // ===== Selección de atributos (chips, sin dropdowns flotantes) =====
            function lineaCumpleFiltros(linea, metaProducto = null) {
                const prdId = Number(metaProducto?.producto?.prd_id || 0);
                const filtrosLineas = lineasDeProducto(prdId);
                const attrs = linea.atributos || {};
                if (!filtrosLineas.length) return true;
                return filtrosLineas.some(lineaProducto => {
                    const filtros = lineaProducto.filtros || {};
                    const claves = Object.keys(filtros);
                    if (!claves.length) return true;
                    return claves.every(atr => {
                        const set = filtros[atr];
                        if (!set || set.size === 0) return true;
                        return set.has(String(attrs[atr] || 'Sin valor').trim());
                    });
                });
            }
            function resumirFiltrosLinea(linea, maxLen = 140) {
                const meta = state.meta[Number(linea.prd_id || 0)];
                const mapa = mapaAtributosProducto(meta);
                const partes = Object.entries(mapa).map(([atr]) => {
                    const set = linea.filtros?.[atr];
                    const vals = set && set.size ? Array.from(set).sort(compararTalla) : ['todos'];
                    return atr + ': ' + vals.join(', ');
                });
                const txt = partes.join(' · ') || 'Sin atributos';
                return txt.length > maxLen ? (txt.slice(0, maxLen - 1) + '…') : txt;
            }
            function renderFiltros() {
                const items = productosActivos();
                const cards = items.map(m => {
                    const prdId = Number(m.producto?.prd_id || 0);
                    const mapa = mapaAtributosProducto(m);
                    const cat = Object.entries(mapa).sort((a, b) => a[0].localeCompare(b[0], 'es', { sensitivity: 'base' }));
                    if (!cat.length) return '';

                    const filtros = asegurarFiltrosProducto(prdId);
                    let gruposConSeleccion = 0;
                    const body = cat.map(([atr, valores]) => {
                        const vals = Array.from(valores).sort(compararTalla);
                        const prev = filtros[atr] || new Set();
                        const sel = new Set(vals.filter(v => prev.has(v)));
                        filtros[atr] = sel;
                        if (sel.size) gruposConSeleccion += 1;
                        const toggles = vals.map(v =>
                            '<button type="button" class="desktop-rcb-tog' + (sel.has(v) ? ' is-active' : '') + '" data-toggle-val data-prd-id="' + prdId + '" data-atr="' + esc(atr) + '" data-val="' + esc(v) + '">' + esc(v) + '</button>'
                        ).join('');
                        return '<div class="desktop-rcb-attrgroup" data-group="' + esc(atr) + '" data-prd-group="' + prdId + '">' +
                            '<div class="desktop-rcb-attrgroup__title"><span>' + esc(atr) + '</span>' +
                                '<span class="desktop-rcb-attrgroup__count" data-count-atr="' + esc(atr) + '" data-count-prd="' + prdId + '">' + (sel.size ? sel.size + ' seleccionados' : 'todos') + '</span></div>' +
                            '<div class="desktop-rcb-toggles">' + toggles + '</div>' +
                        '</div>';
                    }).join('');

                    const p = m.producto || {};
                    const resumen = gruposConSeleccion
                        ? (gruposConSeleccion + ' atributo(s) filtrados')
                        : 'Sin filtros, se muestran todas las variantes';
                    const meta = [p.marca_nombre, p.prd_codigo].filter(Boolean).join(' · ');
                    return '<section class="desktop-rcb-attrproduct" data-prd-card="' + prdId + '">' +
                        '<div class="desktop-rcb-attrproduct__head">' +
                            '<div class="desktop-rcb-attrproduct__main">' +
                                '<div class="desktop-rcb-attrproduct__name">' + esc(p.prd_nombre || ('Producto ' + prdId)) + '</div>' +
                                '<div class="desktop-rcb-attrproduct__meta">' + esc(meta || ('ID ' + prdId)) + '</div>' +
                            '</div>' +
                            '<div class="desktop-rcb-attrproduct__summary" data-prd-summary="' + prdId + '">' + esc(resumen) + '</div>' +
                        '</div>' +
                        '<div class="desktop-rcb-attrproduct__body">' + body + '</div>' +
                    '</section>';
                }).filter(Boolean);

                $('#rcb-filter-card').prop('hidden', cards.length === 0);
                if (!cards.length) {
                    state.filtrosAtributos = {};
                    $('#rcb-attr-filter').empty();
                    return;
                }

                state.filtrosAtributos = Object.fromEntries(Object.entries(serializarFiltrosGlobalDesdeProductos()).map(([k, v]) => [k, new Set(v || [])]));
                $('#rcb-attr-filter').html('<div class="desktop-rcb-attrproducts">' + cards.join('') + '</div>');
            }

            function actualizarRestaurar() {
                const hay = Object.keys(state.quitados).length + Object.keys(state.filasExcluidas).length;
                $('#rcb-restaurar').prop('hidden', hay === 0);
            }

            // ===== Productos (chips) =====
            function renderProductos() {
                const items = Object.values(state.meta);
                const activos = items.filter(m => m.producto && !state.quitados[m.producto.prd_id]).length;
                $('#rcb-launch-sub').text(activos
                    ? (activos + ' producto(s) listos para capturar — abre la pantalla completa')
                    : 'Agrega productos y elige el atributo dominante dentro de la captura.');
                const $b = $('#rcb-prods-chips');
                if (!items.length) { $b.html('<span class="desktop-rcb-empty">Sin productos. Búscalos abajo para agregarlos.</span>'); return; }
                $b.html(items.map(m => {
                    const p = m.producto;
                    if (state.quitados[p.prd_id]) {
                        return '<span class="desktop-rcb-chip desktop-rcb-chip--off"><span>' + esc(p.prd_nombre) + '</span>' +
                            '<button type="button" class="desktop-rcb-chip__restore" data-restore-prd="' + p.prd_id + '" title="Restaurar">↺</button></span>';
                    }
                    return '<span class="desktop-rcb-chip"><span>' + esc(p.prd_nombre) + '</span>' +
                        '<button type="button" class="desktop-rcb-chip__x" data-del-prd="' + p.prd_id + '" title="Quitar">&times;</button></span>';
                }).join(''));
            }

            function agregarProducto(prdId) {
                prdId = Number(prdId);
                if (!prdId || state.meta[prdId]) return;
                const url = urlId(rutas.matriz, prdId) + '?min_scl_id=' + (Number($('#rcb-scl').val() || 0) || '');
                $.getJSON(url).done(function (resp) {
                    state.meta[prdId] = resp.data || {};
                    asegurarFiltrosProducto(prdId);
                    refrescarTodo();
                    const card = document.querySelector('[data-prd-card="' + prdId + '"]');
                    if (card) card.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                }).fail(function (xhr) { notify('Error', xhr.responseJSON?.message || 'No se pudo cargar la matriz del producto.', 'error'); });
            }

            function refrescarTodo() {
                renderProductos(); actualizarMarcas(); poblarDominantes(); renderFiltros(); renderMatriz(); actualizarRestaurar();
            }

            function quitarProducto(prdId) { state.quitados[prdId] = true; refrescarTodo(); }
            function restaurarProducto(prdId) { delete state.quitados[prdId]; refrescarTodo(); }

            function renderFiltros() {
                $('#rcb-filter-card').prop('hidden', true);
                $('#rcb-attr-filter').empty();
            }

            function actualizarRestaurar() {
                $('#rcb-restaurar').prop('hidden', Object.keys(state.filasExcluidas).length === 0);
            }

            function renderProductos() {
                const total = state.lineasProductos.length;
                const totalProductos = productosActivos().length;
                $('#rcb-launch-sub').text(total
                    ? (totalProductos + ' producto(s) y ' + total + ' línea(s) listos para capturar — abre la pantalla completa')
                    : 'Agrega productos y elige el atributo dominante dentro de la captura.');
                const $b = $('#rcb-prods-chips');
                if (!total) { $b.html('<span class="desktop-rcb-empty">Sin productos. Búscalos abajo para agregarlos.</span>'); return; }
                $b.html(state.lineasProductos.map(linea => {
                    const p = state.meta[Number(linea.prd_id || 0)]?.producto || {};
                    const codigo = p.prd_codigo || ('ID ' + linea.prd_id);
                    return '<div class="desktop-rcb-line" data-line-id="' + esc(linea.line_id) + '">' +
                        '<div class="desktop-rcb-line__main">' +
                            '<div class="desktop-rcb-line__top">' +
                                '<span class="desktop-rcb-line__code">' + esc(codigo) + '</span>' +
                                '<span class="desktop-rcb-line__name">' + esc(p.prd_nombre || ('Producto ' + linea.prd_id)) + '</span>' +
                            '</div>' +
                            '<div class="desktop-rcb-line__attrs">' + esc(resumirFiltrosLinea(linea)) + '</div>' +
                        '</div>' +
                        '<div class="desktop-rcb-line__actions">' +
                            '<button type="button" class="desktop-btn desktop-btn--default" data-edit-line="' + esc(linea.line_id) + '">Editar</button>' +
                            '<button type="button" class="desktop-btn desktop-btn--ghost" data-del-line="' + esc(linea.line_id) + '" title="Eliminar">&times;</button>' +
                        '</div>' +
                    '</div>';
                }).join(''));
            }

            function cargarMetaProducto(prdId) {
                prdId = Number(prdId);
                if (!prdId) return $.Deferred().reject().promise();
                if (state.meta[prdId]) return $.Deferred().resolve(state.meta[prdId]).promise();
                const url = urlId(rutas.matriz, prdId) + '?min_scl_id=' + (Number($('#rcb-scl').val() || 0) || '');
                return $.getJSON(url).done(function (resp) { state.meta[prdId] = resp.data || {}; });
            }

            function refrescarTodo() {
                renderProductos(); actualizarMarcas(); poblarDominantes(); renderFiltros(); renderMatriz(); actualizarRestaurar();
            }

            function eliminarLinea(lineId) {
                state.lineasProductos = state.lineasProductos.filter(linea => String(linea.line_id) !== String(lineId));
                refrescarTodo();
            }

            function resetModalProducto() {
                state.modalProducto = { modo: 'crear', paso: 'buscar', prdId: null, lineId: null, filtros: {} };
            }

            function actualizarProdCount() {
                if (state.modalProducto.paso === 'buscar') {
                    $('#rcb-prod-count').text('Selecciona un producto');
                    return;
                }
                const seleccionados = Object.values(state.modalProducto.filtros || {}).filter(set => set && set.size).length;
                $('#rcb-prod-count').text(seleccionados ? (seleccionados + ' atributo(s) con selección') : 'Sin selección = todas las variantes');
            }

            function rowProducto(p) {
                const repetidos = lineasDeProducto(p.id).length;
                const meta = [
                    p.marca_nombre || 'S/M',
                    p.modelo_nombre || 'S/Mo',
                    p.concepto_nombre || 'S/C',
                    p.descripcion_nombre || 'S/D',
                    p.prd_codigo || 'S/CI'
                ].join(' · ');
                return '<label class="desktop-rcb-prowitem">' +
                    '<input type="radio" name="rcb-prod-radio" data-prd="' + p.id + '">' +
                    '<span class="desktop-rcb-prowitem__main">' +
                        '<span class="desktop-rcb-prowitem__name">' + esc(p.prd_nombre || p.text) + '</span>' +
                        '<span class="desktop-rcb-prowitem__meta">' + esc(meta) + '</span>' +
                    '</span>' +
                    (repetidos ? '<span class="desktop-rcb-prowitem__tag">' + repetidos + ' línea(s)</span>' : '') +
                '</label>';
            }

            function cargarProdList(reset) {
                if (reset) { prodPage = 1; $('#rcb-prod-list').html('<div class="desktop-rcb-empty" style="padding:16px; text-align:center;">Buscando…</div>'); }
                $.getJSON(rutas.buscarProductos, { q: prodTerm, page: prodPage }).done(function (resp) {
                    const items = resp.results || [];
                    prodMore = !!(resp.pagination && resp.pagination.more);
                    const html = items.map(rowProducto).join('');
                    if (reset) $('#rcb-prod-list').html(html || '<div class="desktop-rcb-empty" style="padding:16px; text-align:center;">Sin resultados.</div>');
                    else $('#rcb-prod-list').append(html);
                    $('#rcb-prod-more').prop('hidden', !prodMore);
                }).fail(function () {
                    $('#rcb-prod-list').html('<div class="desktop-rcb-empty" style="padding:16px; text-align:center; color:var(--danger);">No fue posible cargar productos.</div>');
                });
            }

            function renderProdModalAttrs() {
                const prdId = Number(state.modalProducto.prdId || 0);
                const meta = state.meta[prdId];
                if (!meta) { $('#rcb-prod-attrs').html('<div class="desktop-rcb-empty">Cargando atributos…</div>'); return; }
                const p = meta.producto || {};
                $('#rcb-prod-picked-name').text(p.prd_nombre || ('Producto ' + prdId));
                $('#rcb-prod-picked-meta').text([p.prd_codigo, p.marca_nombre, p.modelo_nombre].filter(Boolean).join(' · '));
                state.modalProducto.filtros = normalizarFiltrosRuntime(state.modalProducto.filtros);
                const html = Object.entries(mapaAtributosProducto(meta))
                    .sort((a, b) => a[0].localeCompare(b[0], 'es', { sensitivity: 'base' }))
                    .map(([atr, valores]) => {
                        const vals = Array.from(valores).sort(compararTalla);
                        const prev = state.modalProducto.filtros[atr] || new Set();
                        const sel = new Set(vals.filter(v => prev.has(v)));
                        state.modalProducto.filtros[atr] = sel;
                        const toggles = vals.map(v =>
                            '<button type="button" class="desktop-rcb-tog' + (sel.has(v) ? ' is-active' : '') + '" data-modal-attr data-atr="' + esc(atr) + '" data-val="' + esc(v) + '">' + esc(v) + '</button>'
                        ).join('');
                        return '<div class="desktop-rcb-attrgroup">' +
                            '<div class="desktop-rcb-attrgroup__title"><span>' + esc(atr) + '</span>' +
                                '<span class="desktop-rcb-attrgroup__count" data-modal-count="' + esc(atr) + '">' + (sel.size ? sel.size + ' seleccionados' : 'todos') + '</span></div>' +
                            '<div class="desktop-rcb-toggles">' + toggles + '</div>' +
                        '</div>';
                    }).join('');
                $('#rcb-prod-attrs').html(html || '<div class="desktop-rcb-empty">Este producto no tiene atributos configurados.</div>');
                actualizarProdCount();
            }

            function renderProdModal() {
                const enBusqueda = state.modalProducto.paso === 'buscar';
                $('#rcb-prod-modal-title').text(enBusqueda ? 'Buscar productos' : (state.modalProducto.modo === 'editar' ? 'Editar atributos' : 'Seleccionar atributos'));
                $('#rcb-prod-search-state').prop('hidden', !enBusqueda);
                $('#rcb-prod-attr-state').prop('hidden', enBusqueda);
                $('#rcb-prod-back').prop('hidden', enBusqueda);
                $('#rcb-prod-add').prop('hidden', enBusqueda).text(state.modalProducto.modo === 'editar' ? 'Guardar cambios' : 'Agregar producto');
                $('#rcb-prod-more').prop('hidden', enBusqueda ? $('#rcb-prod-more').prop('hidden') : true);
                actualizarProdCount();
                if (!enBusqueda) renderProdModalAttrs();
            }

            function abrirProdModal() {
                resetModalProducto();
                prodTerm = '';
                $('#rcb-prod-q').val('');
                $('#rcb-prod-list').empty();
                $('#rcb-prod-modal').addClass('is-open').attr('aria-hidden', 'false');
                renderProdModal();
                setTimeout(() => $('#rcb-prod-q').trigger('focus'), 50);
                cargarProdList(true);
            }

            function cerrarProdModal() {
                $('#rcb-prod-modal').removeClass('is-open').attr('aria-hidden', 'true');
                resetModalProducto();
            }

            function seleccionarProductoModal(prdId) {
                cargarMetaProducto(prdId).done(function () {
                    state.modalProducto.prdId = Number(prdId);
                    state.modalProducto.paso = 'atributos';
                    state.modalProducto.filtros = {};
                    renderProdModal();
                }).fail(function (xhr) {
                    notify('Error', xhr?.responseJSON?.message || 'No se pudo cargar el producto.', 'error');
                });
            }

            function abrirEdicionLinea(lineId) {
                const linea = state.lineasProductos.find(item => String(item.line_id) === String(lineId));
                if (!linea) return;
                state.modalProducto = {
                    modo: 'editar',
                    paso: 'atributos',
                    prdId: Number(linea.prd_id),
                    lineId: linea.line_id,
                    filtros: normalizarFiltrosRuntime(linea.filtros),
                };
                $('#rcb-prod-modal').addClass('is-open').attr('aria-hidden', 'false');
                cargarMetaProducto(linea.prd_id).done(function () { renderProdModal(); });
            }

            function guardarLineaModal() {
                const prdId = Number(state.modalProducto.prdId || 0);
                if (!prdId) { notify('Validación', 'Selecciona un producto.', 'error'); return; }
                const linea = {
                    line_id: state.modalProducto.lineId || crearLineId(),
                    prd_id: prdId,
                    filtros: normalizarFiltrosRuntime(state.modalProducto.filtros),
                };
                if (state.modalProducto.modo === 'editar') {
                    const idx = state.lineasProductos.findIndex(item => String(item.line_id) === String(linea.line_id));
                    if (idx >= 0) state.lineasProductos.splice(idx, 1, linea);
                } else {
                    state.lineasProductos.push(linea);
                }
                cerrarProdModal();
                refrescarTodo();
                const row = document.querySelector('[data-line-id="' + linea.line_id + '"]');
                if (row) row.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }

            // ===== Matriz =====
            function costKey(prdId, col) { return prdId + '|' + col; }

            function renderMatriz() {
                const $shell = $('#rcb-matrix');
                const items = productosActivos();
                const atrId = Number($('#rcb-dominante').val() || 0);
                Object.assign(state.cantidadesCache || (state.cantidadesCache = {}), snapshotCantidades());
                if (!items.length || !atrId) {
                    $shell.html('<div class="desktop-rcb-empty">Agrega productos y elige un atributo dominante para capturar cantidades.</div>');
                    calcTotales();
                    return;
                }
                const domNombre = atrNombrePorId(atrId);

                const html = items.map(function (m) {
                    const p = m.producto;
                    const permiteDecimal = productoPermiteDecimal(p);
                    const lineas = (m.lineas || []).filter(l => lineaCumpleFiltros(l, m));
                    const cols = [];
                    lineas.forEach(l => { const v = (l.atributos || {})[domNombre] || '—'; if (!cols.includes(v)) cols.push(v); });
                    if (!cols.length) cols.push('—');
                    cols.sort(compararTalla); // orden natural de tallas/variantes
                    const otros = (m.atributos || []).map(a => a.atr_nombre).filter(n => n !== domNombre);
                    const filasMap = {};
                    lineas.forEach(l => {
                        const rowKey = otros.map(n => (l.atributos || {})[n] || '').join(' / ') || 'Estándar';
                        const label = otros.map(n => (l.atributos || {})[n]).filter(Boolean).join(' / ') || (p.prd_nombre);
                        const col = (l.atributos || {})[domNombre] || '—';
                        filasMap[rowKey] = filasMap[rowKey] || { label: label, cells: {}, fullKey: p.prd_id + '||' + rowKey };
                        filasMap[rowKey].cells[col] = l;
                    });
                    cols.forEach(col => {
                        const key = costKey(p.prd_id, col);
                        if (state.costosColumna[key] === undefined) {
                            const linea = lineas.find(l => ((l.atributos || {})[domNombre] || '—') === col);
                            const base = (linea && Number(linea.psk_costo) > 0) ? Number(linea.psk_costo) : Number(p.prd_costo || 0);
                            state.costosColumna[key] = Number(base.toFixed(2));
                        }
                    });

                    const cornerLabel = otros.length ? otros.join(' / ') : 'Variante';
                    const thead = '<tr><th class="rowhead">' + esc(cornerLabel) + ' \\ ' + esc(domNombre) + '</th>' +
                        cols.map(c => '<th>' + esc(c) + '</th>').join('') + '</tr>';
                    const costrow = '<tr class="costrow"><td class="rowhead">Costo unitario</td>' +
                        cols.map(c => {
                            const key = costKey(p.prd_id, c);
                            const esFb = !state.costosEditados[key];
                            const fb = esFb ? ' is-fallback' : '';
                            const tit = esFb ? ' title="Costo tomado del costo base (editable)"' : '';
                            return '<td><input type="number" min="0" step="0.01" class="js-rcb-costo' + fb + '"' + tit + ' data-cost-key="' + esc(key) + '" value="' + (state.costosColumna[key] || 0) + '"></td>';
                        }).join('') + '</tr>';
                    const filasVisibles = Object.values(filasMap).filter(row => !state.filasExcluidas[row.fullKey])
                        .sort((a, b) => compararTalla(a.label, b.label));
                    if (!filasVisibles.length) return '';
                    const filas = filasVisibles.map(row => {
                        return '<tr><td class="rowhead"><button type="button" class="desktop-rcb-rowdel" data-row-key="' + esc(row.fullKey) + '" title="Quitar fila">&times;</button>' + esc(row.label) + '</td>' +
                            cols.map(c => {
                                const l = row.cells[c];
                                if (!l) return '<td class="na">N/A</td>';
                                const key = costKey(p.prd_id, c);
                                const val = parseCantidadValor((state.cantidadesCache || {})[l.min_psk_id], permiteDecimal);
                                return '<td><input type="number" min="0" step="' + (permiteDecimal ? '0.01' : '1') + '" inputmode="' + (permiteDecimal ? 'decimal' : 'numeric') + '" class="js-rcb-cant' + (val > 0 ? ' has-val' : '') + '" ' +
                                    'data-allow-decimals="' + (permiteDecimal ? '1' : '0') + '" ' +
                                    'data-prd-id="' + p.prd_id + '" data-min-psk-id="' + l.min_psk_id + '" data-cost-key="' + esc(key) + '" ' +
                                    'value="' + val + '"></td>';
                            }).join('') + '</tr>';
                    }).join('');

                    const metaProducto = [
                        p.marca_nombre || 'S/M',
                        p.modelo_nombre || 'S/Mo',
                        p.concepto_nombre || 'S/C',
                        p.descripcion_nombre || 'S/D',
                        p.prd_codigo || 'S/CI'
                    ].join(' · ');

                    return '<div class="desktop-rcb-mp" data-prd="' + p.prd_id + '"><div class="desktop-rcb-mp__head"><div class="desktop-rcb-mp__meta"><span class="desktop-rcb-mp__name">' + esc(p.prd_nombre) + '</span><span class="desktop-rcb-mp__sub">' + esc(metaProducto) + '</span></div><span class="desktop-rcb-mp__skus">' + lineas.length + ' SKU</span></div><div class="desktop-rcb-mwrap"><table class="desktop-rcb-mtable"><thead>' + thead + '</thead><tbody>' + filas + costrow + '</tbody></table></div></div>';
                }).join('');

                $shell.html(html || '<div class="desktop-rcb-empty">No hay variantes que coincidan con los atributos seleccionados.</div>');
                renderNav();
                calcTotales();
            }

            function obtenerLineas(includeZero) {
                return $('#rcb-matrix .js-rcb-cant').toArray().map(function (el) {
                    const $el = $(el);
                    const key = String($el.data('cost-key') || '');
                    const allowDecimals = String($el.data('allow-decimals') || '0') === '1';
                    return {
                        prd_id: Number($el.data('prd-id') || 0),
                        min_psk_id: Number($el.data('min-psk-id') || 0),
                        min_cantidad: parseCantidadValor($el.val(), allowDecimals),
                        min_precio_unitario: Number(state.costosColumna[key] || 0),
                    };
                }).filter(l => l.prd_id > 0 && l.min_psk_id > 0 && (includeZero || l.min_cantidad > 0));
            }

            function calcTotales() {
                let art = 0, sub = 0;
                obtenerLineas(false).forEach(l => { art += l.min_cantidad; sub += l.min_cantidad * l.min_precio_unitario; });
                const dt = $('#rcb-desc-tipo').val(), dv = Number($('#rcb-desc-valor').val() || 0);
                let desc = dt === 'importe' ? dv : (dt === 'porcentaje' ? sub * Math.min(dv, 100) / 100 : 0);
                const flete = Number($('#rcb-flete').val() || 0);
                const base = Math.max(0, sub - desc) + flete;
                const iva = $('#rcb-iva-on').is(':checked') ? base * Number($('#rcb-iva').val() || 0) / 100 : 0;
                $('#rcb-t-art').text(art.toLocaleString('es-MX'));
                $('#rcb-t-sub').text(money(sub));
                $('#rcb-t-desc').text(money(desc));
                $('#rcb-t-flete').text(money(flete));
                $('#rcb-t-iva').text(money(iva));
                $('#rcb-t-total').text(money(base + iva));
                // Espejo en la barra inferior del fullscreen
                $('.js-rcb-art-mirror').text(art.toLocaleString('es-MX'));
                $('.js-rcb-sub-mirror').text(money(sub));
                $('.js-rcb-iva-mirror').text(money(iva));
                $('.js-rcb-total-mirror').text(money(base + iva));
                actualizarProgreso();
            }

            function actualizarProgreso() {
                const $c = $('#rcb-matrix .js-rcb-cant');
                const totalSku = $c.length;
                let capSku = 0;
                const capByPrd = {}, totByPrd = {};
                $c.each(function () {
                    const pid = $(this).data('prd-id');
                    totByPrd[pid] = (totByPrd[pid] || 0) + 1;
                    if (intNN($(this).val()) > 0) { capSku += 1; capByPrd[pid] = (capByPrd[pid] || 0) + 1; }
                });
                const totalProd = productosActivos().length;
                const capProd = Object.keys(capByPrd).length;
                const pct = totalSku ? Math.round(capSku / totalSku * 100) : 0;
                $('#rcb-fs-progress').text('Productos ' + capProd + '/' + totalProd + ' · SKU ' + capSku + '/' + totalSku + ' · ' + pct + '%');
                $('#rcb-fs-progbar').css('width', pct + '%');
                // Conteo en vivo en el panel de navegación
                $('#rcb-nav-list [data-go]').each(function () {
                    const pid = $(this).data('go'); const c = capByPrd[pid] || 0, t = totByPrd[pid] || 0;
                    $(this).find('b').text(c + '/' + t);
                    $(this).toggleClass('is-done', t > 0 && c >= t);
                });
            }

            function actualizarInfoFs() {
                const provTxt = $('#rcb-prv').val() ? $('#rcb-prv option:selected').text() : 'Sin proveedor';
                const fac = $('#rcb-ref').val().trim() || '—';
                const scl = $('#rcb-scl').val() ? $('#rcb-scl option:selected').text() : '—';
                const alm = $('#rcb-alm').val() ? $('#rcb-alm option:selected').text() : '—';
                const fecha = $('#rcb-emision').val() ? $('#rcb-emision').val().replace('T', ' ') : '—';
                const tipo = $('#rcb-tipo option:selected').text();
                $('#rcb-fs-info').text(tipo + ' · Proveedor: ' + provTxt + ' · Factura: ' + fac + ' · ' + scl + ' / ' + alm + ' · ' + fecha);
            }

            function abrirFs() {
                if (!productosActivos().length) { notify('Validación', 'Agrega al menos un producto antes de capturar.', 'error'); return; }
                if (!Number($('#rcb-dominante').val() || 0)) { poblarDominantes(); renderMatriz(); }
                // Reubicar el resumen y las acciones dentro del fullscreen
                $('#rcb-econ').appendTo('#rcb-fs-resumen');
                $('#rcb-actions').appendTo('#rcb-fs-actions');
                actualizarInfoFs();
                calcTotales();
                $('#rcb-fs').prop('hidden', false).attr('aria-hidden', 'false');
                const first = document.querySelector('#rcb-matrix .js-rcb-cant');
                if (first) setTimeout(() => { first.focus(); first.select(); }, 60);
            }
            function cerrarFs() {
                // Devolver el resumen y las acciones a la pantalla de configuración
                $('#rcb-econ').appendTo('#rcb-config-aside');
                $('#rcb-actions').appendTo('#rcb-config-aside');
                $('#rcb-fs-resumen').prop('hidden', true);
                $('#rcb-toggle-resumen').text('Mostrar resumen');
                $('#rcb-fs').prop('hidden', true).attr('aria-hidden', 'true');
            }

            // ===== Payload =====
            function snapshotCantidades() {
                const c = {};
                $('#rcb-matrix .js-rcb-cant').each(function () {
                    const id = Number($(this).data('min-psk-id'));
                    const allowDecimals = String($(this).data('allow-decimals') || '0') === '1';
                    if (id) c[id] = parseCantidadValor($(this).val(), allowDecimals);
                });
                return c;
            }
            function construirPayload(includeZero) {
                const iva = $('#rcb-iva-on').is(':checked') ? Number($('#rcb-iva').val() || 0) : 0;
                return {
                    rme_id: state.rmeId || null,
                    min_scl_id: Number($('#rcb-scl').val() || 0) || null,
                    min_alm_id: Number($('#rcb-alm').val() || 0) || null,
                    min_fecha_movimiento: null,
                    min_fecha_emision: $('#rcb-emision').val() || null,
                    min_documento_tipo: $('#rcb-tipo').val() || null,
                    min_documento_referencia: $('#rcb-ref').val().trim() || null,
                    min_motivo_texto: 'Recepción de mercancía manual',
                    min_observaciones: $('#rcb-obs').val().trim() || null,
                    min_prv_id: Number($('#rcb-prv').val() || 0) || null,
                    min_descuento_tipo: $('#rcb-desc-tipo').val(),
                    min_descuento_valor: Number(Number($('#rcb-desc-valor').val() || 0).toFixed(2)),
                    min_flete_total: Number(Number($('#rcb-flete').val() || 0).toFixed(2)),
                    min_iva_porcentaje: Number(iva.toFixed(2)),
                    dominante_atr_id: Number($('#rcb-dominante').val() || 0) || null,
                    lineas: obtenerLineas(includeZero),
                    payload: {
                        meta: state.meta,
                        costosColumna: state.costosColumna,
                        costosEditados: state.costosEditados,
                        cantidades: snapshotCantidades(),
                        dominanteGlobal: Number($('#rcb-dominante').val() || 0) || null,
                        lineasProductos: serializarLineasProductos(),
                        filtrosAtributos: serializarFiltrosGlobalDesdeProductos(),
                        filtrosPorProducto: serializarFiltrosPorProducto(),
                        filasExcluidas: state.filasExcluidas,
                    },
                };
            }

            function parseErr(xhr) {
                const errs = xhr.responseJSON?.errors;
                const status = xhr.status ? ('HTTP ' + xhr.status) : 'Sin respuesta HTTP';
                let m = xhr.responseJSON?.message || 'No fue posible procesar la solicitud.';
                if (errs) { const f = Object.values(errs)[0]; if (f && f[0]) m = f[0]; }
                if (!xhr.responseJSON && xhr.responseText) {
                    const plain = $('<div>').html(xhr.responseText).text().replace(/\s+/g, ' ').trim();
                    if (plain) m = plain.substring(0, 240);
                }
                return status + ': ' + m;
            }

            function actualizarEstado() {
                $('#rcb-estado').text(state.rmeFolio ? ('Borrador en edición: ' + state.rmeFolio) : 'Sin borrador en edición.');
            }

            // ===== Modal de búsqueda de productos (multi-selección) =====
            let prodPage = 1, prodTerm = '', prodMore = false;
            const prodSel = new Set();
            let prodTimer = null;

            function abrirProdModal() {
                prodSel.clear();
                prodTerm = '';
                $('#rcb-prod-q').val('');
                $('#rcb-prod-list').empty();
                actualizarProdCount();
                $('#rcb-prod-modal').addClass('is-open').attr('aria-hidden', 'false');
                setTimeout(() => $('#rcb-prod-q').trigger('focus'), 50);
                cargarProdList(true);
            }
            function cerrarProdModal() { $('#rcb-prod-modal').removeClass('is-open').attr('aria-hidden', 'true'); }
            function actualizarProdCount() { $('#rcb-prod-count').text(prodSel.size + ' seleccionados'); $('#rcb-prod-add').prop('disabled', prodSel.size === 0); }

            function rowProducto(p) {
                const yaAgregado = !!state.meta[p.id];
                const checked = (prodSel.has(Number(p.id)) || yaAgregado) ? 'checked' : '';
                const meta = [
                    p.marca_nombre || 'S/M',
                    p.modelo_nombre || 'S/Mo',
                    p.concepto_nombre || 'S/C',
                    p.descripcion_nombre || 'S/D',
                    p.prd_codigo || 'S/CI'
                ].join(' · ');
                return '<label class="desktop-rcb-prowitem' + (yaAgregado ? ' is-added' : '') + '">' +
                    '<input type="checkbox" data-prd="' + p.id + '" ' + checked + (yaAgregado ? ' disabled' : '') + '>' +
                    '<span class="desktop-rcb-prowitem__main">' +
                        '<span class="desktop-rcb-prowitem__name">' + esc(p.prd_nombre || p.text) + '</span>' +
                        '<span class="desktop-rcb-prowitem__meta">' + esc(meta) + '</span>' +
                    '</span>' +
                    (yaAgregado ? '<span class="desktop-rcb-prowitem__tag">Agregado</span>' : '') +
                '</label>';
            }

            function cargarProdList(reset) {
                if (reset) { prodPage = 1; $('#rcb-prod-list').html('<div class="desktop-rcb-empty" style="padding:16px; text-align:center;">Buscando…</div>'); }
                $.getJSON(rutas.buscarProductos, { q: prodTerm, page: prodPage }).done(function (resp) {
                    const items = resp.results || [];
                    prodMore = !!(resp.pagination && resp.pagination.more);
                    const html = items.map(rowProducto).join('');
                    if (reset) {
                        $('#rcb-prod-list').html(html || '<div class="desktop-rcb-empty" style="padding:16px; text-align:center;">Sin resultados.</div>');
                    } else {
                        $('#rcb-prod-list').append(html);
                    }
                    $('#rcb-prod-more').prop('hidden', !prodMore);
                }).fail(function () {
                    $('#rcb-prod-list').html('<div class="desktop-rcb-empty" style="padding:16px; text-align:center; color:var(--danger);">No fue posible cargar productos.</div>');
                });
            }

            $('#rcb-open-prod').on('click', abrirProdModal);
            $(document).on('click', '[data-close-prod]', cerrarProdModal);
            $('#rcb-prod-modal').on('click', function (e) { if (e.target === this) cerrarProdModal(); });
            $('#rcb-prod-q').on('input', function () {
                prodTerm = String(this.value || '').trim();
                clearTimeout(prodTimer);
                prodTimer = setTimeout(() => cargarProdList(true), 250);
            });
            $('#rcb-prod-more').on('click', function () { prodPage += 1; cargarProdList(false); });
            $('#rcb-prod-list').on('change', 'input[type="checkbox"]', function () {
                const id = Number($(this).data('prd'));
                if (this.checked) prodSel.add(id); else prodSel.delete(id);
                actualizarProdCount();
            });
            $('#rcb-prod-add').on('click', function () {
                const ids = Array.from(prodSel);
                cerrarProdModal();
                ids.forEach(agregarProducto);
            });

            $('#rcb-prods-chips').on('click', '[data-del-prd]', function () { quitarProducto($(this).data('del-prd')); });
            $('#rcb-prods-chips').on('click', '[data-restore-prd]', function () { restaurarProducto($(this).data('restore-prd')); });
            $('#rcb-dominante').on('change', renderMatriz);

            function actualizarProdCount() {
                if (state.modalProducto.paso === 'buscar') {
                    $('#rcb-prod-count').text('Selecciona un producto');
                    return;
                }
                const seleccionados = Object.values(state.modalProducto.filtros || {}).filter(set => set && set.size).length;
                $('#rcb-prod-count').text(seleccionados ? (seleccionados + ' atributo(s) con selección') : 'Sin selección = todas las variantes');
            }

            function rowProducto(p) {
                const repetidos = lineasDeProducto(p.id).length;
                const meta = [
                    p.marca_nombre || 'S/M',
                    p.modelo_nombre || 'S/Mo',
                    p.concepto_nombre || 'S/C',
                    p.descripcion_nombre || 'S/D',
                    p.prd_codigo || 'S/CI'
                ].join(' · ');
                return '<label class="desktop-rcb-prowitem">' +
                    '<input type="radio" name="rcb-prod-radio" data-prd="' + p.id + '">' +
                    '<span class="desktop-rcb-prowitem__main">' +
                        '<span class="desktop-rcb-prowitem__name">' + esc(p.prd_nombre || p.text) + '</span>' +
                        '<span class="desktop-rcb-prowitem__meta">' + esc(meta) + '</span>' +
                    '</span>' +
                    (repetidos ? '<span class="desktop-rcb-prowitem__tag">' + repetidos + ' línea(s)</span>' : '') +
                '</label>';
            }

            function cargarProdList(reset) {
                if (reset) { prodPage = 1; $('#rcb-prod-list').html('<div class="desktop-rcb-empty" style="padding:16px; text-align:center;">Buscando…</div>'); }
                $.getJSON(rutas.buscarProductos, { q: prodTerm, page: prodPage }).done(function (resp) {
                    const items = resp.results || [];
                    prodMore = !!(resp.pagination && resp.pagination.more);
                    const html = items.map(rowProducto).join('');
                    if (reset) $('#rcb-prod-list').html(html || '<div class="desktop-rcb-empty" style="padding:16px; text-align:center;">Sin resultados.</div>');
                    else $('#rcb-prod-list').append(html);
                    $('#rcb-prod-more').prop('hidden', !prodMore || state.modalProducto.paso !== 'buscar');
                }).fail(function () {
                    $('#rcb-prod-list').html('<div class="desktop-rcb-empty" style="padding:16px; text-align:center; color:var(--danger);">No fue posible cargar productos.</div>');
                });
            }

            function abrirProdModal() {
                resetModalProducto();
                prodTerm = '';
                $('#rcb-prod-q').val('');
                $('#rcb-prod-list').empty();
                $('#rcb-prod-modal').addClass('is-open').attr('aria-hidden', 'false');
                renderProdModal();
                setTimeout(() => $('#rcb-prod-q').trigger('focus'), 50);
                cargarProdList(true);
            }

            function cerrarProdModal() {
                $('#rcb-prod-modal').removeClass('is-open').attr('aria-hidden', 'true');
                resetModalProducto();
            }

            $('#rcb-open-prod').off('click').on('click', abrirProdModal);
            $('#rcb-prod-q').off('input').on('input', function () {
                prodTerm = String(this.value || '').trim();
                clearTimeout(prodTimer);
                prodTimer = setTimeout(() => cargarProdList(true), 250);
            });
            $('#rcb-prod-more').off('click').on('click', function () { prodPage += 1; cargarProdList(false); });
            $('#rcb-prod-list').off('change').on('change', 'input[type="radio"]', function () {
                seleccionarProductoModal(Number($(this).data('prd')));
            });
            $('#rcb-prod-back').off('click').on('click', function () {
                state.modalProducto.paso = 'buscar';
                state.modalProducto.prdId = null;
                state.modalProducto.lineId = null;
                state.modalProducto.filtros = {};
                renderProdModal();
                setTimeout(() => $('#rcb-prod-q').trigger('focus'), 50);
            });
            $('#rcb-prod-add').off('click').on('click', guardarLineaModal);
            $('#rcb-prods-chips').off('click', '[data-edit-line]').on('click', '[data-edit-line]', function () { abrirEdicionLinea($(this).attr('data-edit-line')); });
            $('#rcb-prods-chips').off('click', '[data-del-line]').on('click', '[data-del-line]', function () { eliminarLinea($(this).attr('data-del-line')); });
            $('#rcb-prod-attrs').off('click').on('click', '[data-modal-attr]', function () {
                const atr = $(this).attr('data-atr');
                const val = $(this).attr('data-val');
                const filtros = state.modalProducto.filtros = normalizarFiltrosRuntime(state.modalProducto.filtros);
                const set = filtros[atr] = filtros[atr] || new Set();
                if (set.has(val)) { set.delete(val); $(this).removeClass('is-active'); }
                else { set.add(val); $(this).addClass('is-active'); }
                $(this).closest('.desktop-rcb-attrgroup').find('[data-modal-count]').text(set.size ? set.size + ' seleccionados' : 'todos');
                actualizarProdCount();
            });
            $('#rcb-attr-filter').off('click');
            $('#rcb-restaurar').off('click').on('click', function () {
                state.filasExcluidas = {};
                refrescarTodo();
            });
            $('#rcb-borrador').off('click').on('click', function () {
                if (!state.lineasProductos.length) { notify('Validación', 'Agrega al menos un producto.', 'error'); return; }
                $.ajax({ url: rutas.borrador, method: 'POST', dataType: 'json', contentType: 'application/json', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, data: JSON.stringify(construirPayload(true)) })
                    .done(function (resp) {
                        state.rmeId = Number(resp.data?.rme_id || 0) || null;
                        state.rmeFolio = resp.data?.rme_folio || null;
                        state.rmeEstado = resp.data?.rme_estado || 'borrador';
                        actualizarEstado();
                        notify('Listo', (resp.message || 'Borrador guardado') + ' (' + (state.rmeFolio || '') + ')', 'success');
                    }).fail(xhr => notify('Error', parseErr(xhr), 'error'));
            });

            // Agrupar por (control segmentado)
            $('#rcb-dominante-seg').on('click', '[data-dom]', function () {
                $('#rcb-dominante').val($(this).attr('data-dom'));
                $('#rcb-dominante-seg button').removeClass('is-active');
                $(this).addClass('is-active');
                renderMatriz();
            });

            // Panel de navegación de productos
            $('#rcb-nav-toggle').on('click', function () {
                const $n = $('#rcb-nav');
                const oculto = $n.prop('hidden');
                $n.prop('hidden', !oculto);
                if (oculto) renderNav();
            });
            $('#rcb-nav-list').on('click', '[data-go]', function () {
                const pid = $(this).data('go');
                const el = document.querySelector('#rcb-matrix .desktop-rcb-mp[data-prd="' + pid + '"]');
                if (el) el.scrollIntoView({ block: 'start', behavior: 'smooth' });
            });

            // Captura en pantalla completa
            $('#rcb-open-capture').on('click', abrirFs);
            $('#rcb-fs-back').on('click', cerrarFs);
            $('#rcb-toggle-resumen').on('click', function () {
                const $r = $('#rcb-fs-resumen');
                const oculto = $r.prop('hidden');
                $r.prop('hidden', !oculto);
                $(this).text(oculto ? 'Ocultar resumen' : 'Mostrar resumen');
            });

            // Atributos: botones toggle (multi-selección persistente, sin re-render completo)
            $('#rcb-attr-filter').on('click', '[data-toggle-val]', function () {
                const prdId = Number($(this).attr('data-prd-id') || 0);
                const atr = $(this).attr('data-atr'), val = $(this).attr('data-val');
                const filtros = asegurarFiltrosProducto(prdId);
                const set = filtros[atr] = filtros[atr] || new Set();
                if (set.has(val)) { set.delete(val); $(this).removeClass('is-active'); }
                else { set.add(val); $(this).addClass('is-active'); }
                const seleccionados = Object.values(filtros).filter(s => s && s.size).length;
                $(this).closest('.desktop-rcb-attrgroup').find('[data-count-atr]').text(set.size ? set.size + ' seleccionados' : 'todos');
                $('[data-prd-summary="' + prdId + '"]').text(seleccionados ? (seleccionados + ' atributo(s) filtrados') : 'Sin filtros, se muestran todas las variantes');
                state.filtrosAtributos = Object.fromEntries(Object.entries(serializarFiltrosGlobalDesdeProductos()).map(([k, v]) => [k, new Set(v || [])]));
                renderMatriz();
            });

            $('#rcb-matrix').on('click', '.desktop-rcb-rowdel', function () {
                state.filasExcluidas[String($(this).data('row-key'))] = true;
                renderMatriz(); actualizarRestaurar();
            });
            $('#rcb-restaurar').on('click', function () {
                state.quitados = {}; state.filasExcluidas = {};
                refrescarTodo();
            });
            $('#rcb-scl').on('change', syncAlmacenes);
            $('#rcb-tipo').on('change', function () { aplicarUITipo(); sugerirSclAlm(); });
            $('#rcb-ref-na').on('change', toggleRefNa);
            $('#rcb-desc-tipo, #rcb-desc-valor, #rcb-flete, #rcb-iva, #rcb-iva-on').on('input change', calcTotales);

            function celdaDestino(input, dir) {
                const td = input.closest('td');
                const tr = input.closest('tr');
                const table = input.closest('table');
                if (!td || !tr || !table) return null;
                const cellIndex = td.cellIndex;
                if (dir === 'up' || dir === 'down') {
                    const filas = Array.from(table.querySelectorAll('tbody > tr')).filter(r => !r.classList.contains('costrow'));
                    let i = filas.indexOf(tr) + (dir === 'down' ? 1 : -1);
                    while (i >= 0 && i < filas.length) {
                        const inp = filas[i].cells[cellIndex]?.querySelector('.js-rcb-cant');
                        if (inp) return inp;
                        i += (dir === 'down' ? 1 : -1);
                    }
                } else {
                    const cells = Array.from(tr.cells);
                    let j = cellIndex + (dir === 'right' ? 1 : -1);
                    while (j >= 0 && j < cells.length) {
                        const inp = cells[j].querySelector('.js-rcb-cant');
                        if (inp) return inp;
                        j += (dir === 'right' ? 1 : -1);
                    }
                }
                return null;
            }
            $('#rcb-matrix').on('keydown', '.js-rcb-cant', function (e) {
                const allowDecimals = String($(this).data('allow-decimals') || '0') === '1';
                if (!allowDecimals && (e.key === '.' || e.key === ',')) {
                    e.preventDefault();
                    return;
                }
                let dir = null;
                if (e.key === 'ArrowUp') dir = 'up';
                else if (e.key === 'ArrowDown' || e.key === 'Enter') dir = 'down';
                else if (e.key === 'ArrowRight') dir = 'right';
                else if (e.key === 'ArrowLeft') dir = 'left';
                if (!dir) return;
                e.preventDefault();
                const destino = celdaDestino(this, dir);
                if (destino) { destino.focus(); destino.select(); }
            });
            $('#rcb-matrix').on('paste', '.js-rcb-cant', function (e) {
                const allowDecimals = String($(this).data('allow-decimals') || '0') === '1';
                if (allowDecimals) return;
                const text = e.originalEvent?.clipboardData?.getData('text') || '';
                if (text.includes('.') || text.includes(',')) e.preventDefault();
            });
            $('#rcb-matrix').on('input', '.js-rcb-cant', function () {
                const $input = $(this);
                const allowDecimals = String($input.data('allow-decimals') || '0') === '1';
                const raw = String($input.val() ?? '');
                const val = parseCantidadValor(raw, allowDecimals);
                if (!allowDecimals) {
                    $input.val(val > 0 ? val : 0);
                }
                state.cantidadesCache = state.cantidadesCache || {};
                state.cantidadesCache[Number($input.data('min-psk-id') || 0)] = val;
                $input.toggleClass('has-val', val > 0);
                calcTotales();
            });
            $('#rcb-matrix').on('blur', '.js-rcb-cant', function () {
                const $input = $(this);
                const allowDecimals = String($input.data('allow-decimals') || '0') === '1';
                const val = parseCantidadValor($input.val(), allowDecimals);
                $input.val(val > 0 ? val : 0);
                state.cantidadesCache = state.cantidadesCache || {};
                state.cantidadesCache[Number($input.data('min-psk-id') || 0)] = val;
                $input.toggleClass('has-val', val > 0);
                calcTotales();
            });
            $('#rcb-matrix').on('input', '.js-rcb-costo', function () {
                const key = String($(this).data('cost-key') || '');
                state.costosColumna[key] = Number($(this).val() || 0);
                state.costosEditados[key] = true;
                $(this).removeClass('is-fallback');
                calcTotales();
            });

            function validar() {
                if (!$('#rcb-scl').val() || !$('#rcb-alm').val()) { notify('Faltan datos', 'Selecciona sucursal y almacén.', 'error'); return false; }
                if (!obtenerLineas(false).length) { notify('Faltan datos', 'Captura al menos una cantidad mayor a cero.', 'error'); return false; }
                return true;
            }

            $('#rcb-borrador').on('click', function () {
                if (!Object.keys(state.meta).length) { notify('Validación', 'Agrega al menos un producto.', 'error'); return; }
                $.ajax({ url: rutas.borrador, method: 'POST', dataType: 'json', contentType: 'application/json', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, data: JSON.stringify(construirPayload(true)) })
                    .done(function (resp) {
                        state.rmeId = Number(resp.data?.rme_id || 0) || null;
                        state.rmeFolio = resp.data?.rme_folio || null;
                        state.rmeEstado = resp.data?.rme_estado || 'borrador';
                        actualizarEstado();
                        notify('Listo', (resp.message || 'Borrador guardado') + ' (' + (state.rmeFolio || '') + ')', 'success');
                    }).fail(xhr => notify('Error', parseErr(xhr), 'error'));
            });

            $('#rcb-restaurar').off('click').on('click', function () {
                state.filasExcluidas = {};
                refrescarTodo();
            });
            $('#rcb-borrador').off('click').on('click', function () {
                if (!state.lineasProductos.length) { notify('Validación', 'Agrega al menos un producto.', 'error'); return; }
                $.ajax({ url: rutas.borrador, method: 'POST', dataType: 'json', contentType: 'application/json', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, data: JSON.stringify(construirPayload(true)) })
                    .done(function (resp) {
                        state.rmeId = Number(resp.data?.rme_id || 0) || null;
                        state.rmeFolio = resp.data?.rme_folio || null;
                        state.rmeEstado = resp.data?.rme_estado || 'borrador';
                        actualizarEstado();
                        notify('Listo', (resp.message || 'Borrador guardado') + ' (' + (state.rmeFolio || '') + ')', 'success');
                    }).fail(xhr => notify('Error', parseErr(xhr), 'error'));
            });
            function abrirModal() { $('#rcb-confirm-modal').addClass('is-open').attr('aria-hidden', 'false'); setTimeout(() => $('#rcb-pass').trigger('focus'), 50); }
            function cerrarModal() { $('#rcb-confirm-modal').removeClass('is-open').attr('aria-hidden', 'true'); $('#rcb-pass').val(''); }

            $('#rcb-confirmar').on('click', function () { if (validar()) { state.pendiente = construirPayload(false); abrirModal(); } });
            $(document).on('click', '[data-close-rcb]', cerrarModal);
            $('#rcb-confirm-modal').on('click', function (e) { if (e.target === this) cerrarModal(); });

            $('#rcb-confirm-go').on('click', function () {
                const pass = $('#rcb-pass').val();
                if (!pass) { notify('Faltan datos', 'Captura tu contraseña.', 'error'); return; }
                const payload = Object.assign({}, state.pendiente, { confirm_password: pass });
                const $btn = $(this).prop('disabled', true);
                $.ajax({ url: rutas.confirmar, method: 'POST', dataType: 'json', contentType: 'application/json', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, data: JSON.stringify(payload) })
                    .done(function (resp) {
                        cerrarModal();
                        notify('Recepción registrada', 'Folio: ' + (resp.data?.rme_folio || ''), 'success');
                        window.location.href = rutas.recepciones;
                    })
                    .fail(xhr => notify('Error', parseErr(xhr), 'error'))
                    .always(() => $btn.prop('disabled', false));
            });

            // ===== Reanudar borrador =====
            function rehidratar(b) {
                if (!b) return;
                state.rmeId = b.rme_id || null;
                state.rmeFolio = b.rme_folio || null;
                state.rmeEstado = b.rme_estado || null;
                if (b.min_scl_id) $('#rcb-scl').val(String(b.min_scl_id));
                syncAlmacenes();
                if (b.min_alm_id) $('#rcb-alm').val(String(b.min_alm_id));
                if (b.min_documento_tipo) $('#rcb-tipo').val(b.min_documento_tipo);
                $('#rcb-prv').val(b.min_prv_id ? String(b.min_prv_id) : '').trigger('change.select2');
                $('#rcb-ref').val(b.min_documento_referencia || '');
                $('#rcb-obs').val(b.min_observaciones || '');
                $('#rcb-desc-tipo').val(b.min_descuento_tipo || 'ninguno');
                $('#rcb-desc-valor').val(b.min_descuento_valor || 0);
                $('#rcb-flete').val(b.min_flete_total || 0);
                $('#rcb-iva').val(b.min_iva_porcentaje || 0);
                aplicarUITipo();
                const pl = b.payload || {};
                if (pl.meta) state.meta = pl.meta;
                if (pl.costosColumna) state.costosColumna = pl.costosColumna;
                if (pl.costosEditados) state.costosEditados = pl.costosEditados;
                if (pl.filasExcluidas) state.filasExcluidas = pl.filasExcluidas;
                if (pl.lineasProductos) {
                    state.lineasProductos = pl.lineasProductos.map(linea => ({
                        line_id: linea.line_id || crearLineId(),
                        prd_id: Number(linea.prd_id || 0),
                        filtros: normalizarFiltrosRuntime(linea.filtros),
                    })).filter(linea => linea.prd_id > 0);
                } else {
                    const filtrosPorProducto = Object.fromEntries(Object.entries(pl.filtrosPorProducto || {}).map(([prdId, filtros]) => [prdId, normalizarFiltrosRuntime(filtros)]));
                    state.lineasProductos = Object.values(pl.meta || {}).map(m => {
                        const prdId = Number(m?.producto?.prd_id || 0);
                        if (!prdId) return null;
                        return { line_id: crearLineId(), prd_id: prdId, filtros: filtrosPorProducto[prdId] || {} };
                    }).filter(Boolean);
                }
                const maxSeq = state.lineasProductos.reduce((acc, linea) => {
                    const m = String(linea.line_id || '').match(/ln-(\d+)/);
                    return m ? Math.max(acc, Number(m[1]) || 0) : acc;
                }, 0);
                state.lineaSeq = Math.max(state.lineaSeq, maxSeq + 1);
                state.cantidadesCache = pl.cantidades || {};
                renderProductos(); actualizarMarcas(); poblarDominantes(); renderFiltros();
                if (pl.dominanteGlobal) $('#rcb-dominante').val(String(pl.dominanteGlobal));
                else if (b.dominante_atr_id) $('#rcb-dominante').val(String(b.dominante_atr_id));
                renderMatriz();
                actualizarRestaurar();
                actualizarEstado();
            }

            // Init
            const ahora = new Date();
            $('#rcb-emision').val(new Date(ahora.getTime() - ahora.getTimezoneOffset() * 60000).toISOString().slice(0, 16));
            initProveedorSelect();
            syncAlmacenes();
            aplicarUITipo();
            if (!borradorInicial) sugerirSclAlm();
            rehidratar(borradorInicial);
        })();
    </script>
@endpush

