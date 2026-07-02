@php($templateAssetBase = asset('vendor-template/assets'))
@php($desktopUser = auth()->user())
@php($desktopInitials = collect(explode(' ', trim($desktopUser?->usr_nombre ?? 'La Suriana')))->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode(''))
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'Desktop') | {{ config('app.name', 'La Suriana Retail') }}</title>

    @stack('desktop-vendor-styles')

    <style>
        /* =====================================================================
           LA SURIANA · DESKTOP — MICROSOFT FLUENT DESIGN SYSTEM
           Tokens, layout shell y componentes reutilizables. Un único lenguaje
           visual compartido por todo /desktop/*.
           ===================================================================== */
        :root {
            /* Superficies y neutros */
            --bg: #f5f7fa;
            --surface: #ffffff;
            --surface-alt: #fafbfc;
            --surface-sunken: #f1f4f8;
            --header-bg: #ffffff;
            --nav-bg: #fafbfc;

            /* Trazos y divisores (mínimos, suaves) */
            --stroke: #e6e9ed;
            --stroke-strong: #d7dbe0;
            --divider: #eef1f4;

            /* Texto (jerarquía) */
            --text: #1b1f23;
            --text-2: #5a6472;
            --text-3: #8b93a1;

            /* Marca — Microsoft Blue (Fluent 2) */
            --brand: #0f6cbd;
            --brand-hover: #115ea3;
            --brand-pressed: #0c3b5e;
            --brand-soft: #eff6fc;
            --brand-soft-2: #dcecfa;
            --on-brand: #ffffff;

            /* Estados */
            --success: #0e700e;
            --success-soft: #eef8ee;
            --success-stroke: #cfe9cf;
            --danger: #b3261e;
            --danger-soft: #fdf3f2;
            --danger-stroke: #f3cecb;
            --warning: #8a5a00;
            --warning-soft: #fdf6e9;
            --warning-stroke: #f1e1bf;

            /* Radios — bordes mínimos, no cuadrados */
            --r-sm: 4px;
            --r-md: 6px;
            --r-lg: 8px;
            --r-xl: 12px;

            /* Profundidad sutil (Fluent depth) */
            --shadow-2: 0 1px 2px rgba(16, 24, 40, .07), 0 0 1px rgba(16, 24, 40, .10);
            --shadow-8: 0 2px 8px rgba(16, 24, 40, .10), 0 1px 3px rgba(16, 24, 40, .06);
            --shadow-16: 0 8px 28px rgba(16, 24, 40, .16), 0 2px 8px rgba(16, 24, 40, .08);

            --rail-w: 212px;
            --header-h: 44px;

            /* ===== Jerarquía global de z-index (stacking order único) =====
               Contenido base < chrome (sticky/toolbar/header) < flotantes
               (dropdown/menu) < drawer < backdrop < modal < toast. */
            --z-base: 1;
            --z-rail: 50;
            --z-sticky: 100;
            --z-toolbar: 200;
            --z-header: 300;
            --z-dropdown: 500;
            --z-menu: 700;
            --z-drawer: 1000;
            --z-backdrop: 1050;
            --z-modal: 1100;
            --z-toast: 1200;

            --font: 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, 'Helvetica Neue', Arial, sans-serif;
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            min-height: 100%;
            font-family: var(--font);
            font-size: 14px;
            color: var(--text);
            background: var(--bg);
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }

        a { color: inherit; text-decoration: none; }
        .d-none { display: none !important; }

        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-thumb { background: #cdd3da; border-radius: 999px; border: 2px solid transparent; background-clip: padding-box; }
        ::-webkit-scrollbar-thumb:hover { background: #b6bdc6; background-clip: padding-box; }
        ::-webkit-scrollbar-track { background: transparent; }

        /* ====================== SHELL ====================== */
        .app {
            height: 100vh;
            display: grid;
            grid-template-columns: var(--rail-w) 1fr;
            grid-template-rows: var(--header-h) 1fr;
            background: var(--bg);
            transition: grid-template-columns .18s ease;
        }
        .app.is-rail { --rail-w: 48px; }

        /* Header */
        .app__header {
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 10px 0 6px;
            background: var(--header-bg);
            border-bottom: 1px solid var(--stroke);
            z-index: var(--z-header);
        }
        .hburger {
            width: 32px; height: 32px;
            display: inline-flex; align-items: center; justify-content: center;
            border: 0; border-radius: var(--r-md);
            background: transparent; color: var(--text-2);
            cursor: pointer; flex: none;
        }
        .hburger:hover { background: var(--surface-sunken); color: var(--text); }

        .brand { display: flex; align-items: center; gap: 10px; min-width: 0; }
        .brand__logo {
            width: 24px; height: 24px; flex: none;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: var(--r-sm);
            color: #fff; font-size: .68rem; font-weight: 700; letter-spacing: .02em;
            background: linear-gradient(135deg, #0f6cbd, #1452a3);
        }
        .brand__name { font-size: .86rem; font-weight: 600; letter-spacing: -.01em; white-space: nowrap; }

        .header__spacer { flex: 1 1 auto; }

        .header__search {
            display: flex; align-items: center; gap: 8px;
            width: min(300px, 26vw); height: 28px;
            padding: 0 10px;
            background: var(--surface-sunken);
            border: 1px solid transparent;
            border-radius: var(--r-md);
            color: var(--text-3);
        }
        .header__search:focus-within { background: #fff; border-color: var(--brand); color: var(--text-2); }
        .header__search input {
            border: 0; outline: 0; background: transparent;
            font: inherit; font-size: .82rem; color: var(--text); width: 100%;
        }
        .header__icons { display: flex; align-items: center; gap: 1px; }
        .iconbtn {
            width: 32px; height: 32px;
            display: inline-flex; align-items: center; justify-content: center;
            border: 0; border-radius: var(--r-md);
            background: transparent; color: var(--text-2); cursor: pointer;
        }
        .iconbtn svg { width: 18px; height: 18px; }
        .iconbtn:hover { background: var(--surface-sunken); color: var(--text); }
        .avatar {
            width: 28px; height: 28px; margin-left: 4px; flex: none;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 50%;
            font-size: .66rem; font-weight: 700; color: #fff;
            background: linear-gradient(135deg, #2b7de0, #1452a3);
            cursor: pointer;
        }

        /* Nav rail */
        .app__nav {
            grid-row: 2; grid-column: 1;
            background: var(--nav-bg);
            border-right: 1px solid var(--stroke);
            overflow-x: hidden; overflow-y: auto;
            padding: 6px 6px 12px;
            display: flex; flex-direction: column; gap: 1px;
        }
        .nav-group { margin-top: 7px; }
        .nav-group:first-child { margin-top: 1px; }
        .nav-group__label {
            padding: 4px 10px 3px;
            font-size: .64rem; font-weight: 700; letter-spacing: .05em;
            text-transform: uppercase; color: var(--text-3);
            white-space: nowrap;
        }
        .app.is-rail .nav-group__label { opacity: 0; height: 6px; padding: 0; }

        .nav-item {
            position: relative;
            display: flex; align-items: center; gap: 10px;
            height: 32px; padding: 0 9px;
            border-radius: var(--r-md);
            color: var(--text-2); font-size: .82rem; font-weight: 500;
            white-space: nowrap; cursor: pointer;
            transition: background .12s ease, color .12s ease;
        }
        .nav-item__icon { flex: none; width: 18px; height: 18px; display: inline-flex; align-items: center; justify-content: center; }
        .nav-item__icon svg { width: 18px; height: 18px; }
        .nav-item__label { overflow: hidden; text-overflow: ellipsis; }
        .app.is-rail .nav-item__label { opacity: 0; }
        .nav-item:hover { background: rgba(15, 108, 189, .07); color: var(--text); }
        .nav-item.is-active { background: var(--brand-soft); color: var(--brand); font-weight: 600; }
        .nav-item.is-active::before {
            content: ""; position: absolute; left: 0; top: 7px; bottom: 7px;
            width: 3px; border-radius: 0 3px 3px 0; background: var(--brand);
        }
        .nav-item.is-disabled { color: var(--text-3); cursor: default; pointer-events: none; }

        /* Main */
        .app__main {
            grid-row: 2; grid-column: 2;
            min-width: 0; min-height: 0;
            display: flex; flex-direction: column;
            background: var(--bg);
        }

        /* Command bar / toolbar */
        .desktop-toolbar {
            position: relative;
            z-index: var(--z-toolbar);
            display: flex; align-items: center; justify-content: space-between;
            gap: 10px; flex-wrap: wrap;
            min-height: 42px; padding: 5px 12px;
            background: var(--surface);
            border-bottom: 1px solid var(--stroke);
        }
        .desktop-toolbar__group { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; }

        /* Page heading (optional) */
        .page-head { display: flex; flex-direction: column; gap: 0; }
        .page-head__title { font-size: .95rem; font-weight: 600; letter-spacing: -.01em; }
        .page-head__sub { font-size: .74rem; color: var(--text-2); }

        /* Content area
           IMPORTANTE: no debe crear stacking context propio. Un z-index numerico
           aqui atraparia modales/drawers/menus (renderizados dentro del yield de
           contenido) por debajo del header y la toolbar. Se deja z-index:auto para
           que los overlays compitan en el contexto raiz segun la jerarquia global. */
        .desktop-content {
            position: relative;
            z-index: auto;
            flex: 1 1 auto; min-height: 0;
            padding: 10px 12px;
            overflow: auto;
        }
        .desktop-pane {
            height: 100%;
            background: var(--surface);
            border: 1px solid var(--stroke);
            border-radius: var(--r-md);
            box-shadow: var(--shadow-2);
            overflow: hidden;
            display: flex; flex-direction: column;
        }

        /* ====================== BUTTONS ====================== */
        .desktop-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            height: 30px; padding: 0 11px;
            border: 1px solid transparent; border-radius: var(--r-md);
            background: transparent; color: var(--text);
            font: inherit; font-size: .8rem; font-weight: 600;
            cursor: pointer; white-space: nowrap;
            transition: background .12s ease, border-color .12s ease, color .12s ease;
        }
        .desktop-btn svg { width: 15px; height: 15px; }
        .desktop-btn:hover { background: var(--surface-sunken); }
        .desktop-btn:focus-visible { outline: 2px solid var(--brand); outline-offset: 1px; }

        .desktop-btn--primary {
            background: var(--brand); color: var(--on-brand); border-color: var(--brand);
            box-shadow: var(--shadow-2);
        }
        .desktop-btn--primary:hover { background: var(--brand-hover); border-color: var(--brand-hover); color: #fff; }

        .desktop-btn--default { border-color: var(--stroke-strong); background: var(--surface); }
        .desktop-btn--default:hover { background: var(--surface-sunken); border-color: var(--stroke-strong); }

        .desktop-btn--ghost { color: var(--text-2); }
        .desktop-btn--ghost:hover { color: var(--text); background: var(--surface-sunken); }

        /* Acción destructiva (outline danger): misma forma que --default, color de riesgo.
           Secundaria por diseño: no compite con el primario sólido. */
        .desktop-btn--danger { border-color: var(--stroke-strong); background: var(--surface); color: var(--danger); }
        .desktop-btn--danger:hover { background: var(--danger-soft); border-color: var(--danger); color: var(--danger); }
        .desktop-btn--danger:focus-visible { outline-color: var(--danger); }

        /* Segmented pivot (sub-navegación) */
        .desktop-pivot { display: inline-flex; align-items: center; gap: 2px; padding: 2px; background: var(--surface-sunken); border-radius: var(--r-md); }
        .desktop-pivot .desktop-btn { height: 26px; border-radius: var(--r-sm); color: var(--text-2); font-weight: 600; }
        .desktop-pivot .desktop-btn:hover { background: rgba(255,255,255,.7); }
        .desktop-btn--active { background: var(--surface); color: var(--brand); box-shadow: var(--shadow-2); }
        .desktop-btn--active:hover { background: var(--surface); }

        /* ====================== COMMAND-BAR INPUTS ====================== */
        .desktop-toolbar__search {
            display: inline-flex; align-items: center;
            width: 258px; height: 30px; padding: 0 11px;
            border: 1px solid var(--stroke-strong); border-radius: var(--r-md);
            background: var(--surface);
            font: inherit; font-size: .8rem; color: var(--text); outline: none;
            transition: border-color .12s ease, box-shadow .12s ease;
        }
        .desktop-toolbar__search::placeholder { color: var(--text-3); }
        .desktop-toolbar__search:focus { border-color: var(--brand); box-shadow: 0 0 0 1px var(--brand); }

        .desktop-toolbar__select {
            height: 30px; padding: 0 32px 0 11px;
            border: 1px solid var(--stroke-strong); border-radius: var(--r-md);
            background:
                linear-gradient(45deg, transparent 50%, var(--text-3) 50%),
                linear-gradient(135deg, var(--text-3) 50%, transparent 50%),
                var(--surface);
            background-position: calc(100% - 15px) 13px, calc(100% - 10px) 13px, 0 0;
            background-size: 5px 5px, 5px 5px, 100% 100%;
            background-repeat: no-repeat;
            font: inherit; font-size: .8rem; color: var(--text);
            appearance: none; outline: none; cursor: pointer;
        }
        .desktop-toolbar__select:focus { border-color: var(--brand); box-shadow: 0 0 0 1px var(--brand); }

        .desktop-toolbar__divider { width: 1px; height: 20px; background: var(--stroke); margin: 0 3px; }

        /* ====================== DETAILS LIST (tabla) ====================== */
        .desktop-list-wrap { flex: 1 1 auto; min-height: 0; overflow: auto; }

        table.desktop-list {
            width: 100% !important;
            border-collapse: collapse !important;
            font-size: .82rem;
            table-layout: auto;
        }
        table.desktop-list thead th {
            position: sticky; top: 0; z-index: var(--z-sticky);
            padding: 7px 12px !important;
            text-align: left;
            border: 0 !important;
            border-bottom: 1px solid var(--stroke) !important;
            color: var(--text-2) !important;
            font-size: .7rem !important; font-weight: 600 !important;
            letter-spacing: .01em; text-transform: none;
            background: var(--surface) !important;
            white-space: nowrap;
        }
        table.desktop-list tbody td {
            padding: 7px 12px !important;
            border: 0 !important;
            border-bottom: 1px solid var(--divider) !important;
            vertical-align: middle;
            color: var(--text); background: transparent;
        }
        table.desktop-list tbody tr { transition: background .1s ease; }
        table.desktop-list tbody tr:hover td { background: var(--brand-soft); }
        table.desktop-list tbody tr:last-child td { border-bottom: 0 !important; }

        .desktop-cell-primary { display: flex; align-items: center; gap: 9px; }
        .desktop-avatar-sm {
            width: 26px; height: 26px; flex: none;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 50%; font-size: .68rem; font-weight: 700; color: #fff;
            background: linear-gradient(135deg, #5b8def, #2f6fd0);
        }
        .desktop-list__name { display: block; font-weight: 600; font-size: .82rem; line-height: 1.25; color: var(--text); }
        .desktop-list__meta { display: block; color: var(--text-3); font-size: .72rem; }

        /* ====================== BADGES / PILLS ====================== */
        .desktop-pill-list { display: flex; flex-wrap: wrap; gap: 4px; }
        .desktop-pill {
            display: inline-flex; align-items: center; gap: 5px;
            height: 20px; padding: 0 8px;
            border-radius: var(--r-sm);
            font-size: .72rem; font-weight: 600; white-space: nowrap;
            background: var(--surface-sunken); color: var(--text-2);
        }
        .desktop-pill--brand { background: var(--brand-soft); color: var(--brand); }
        .desktop-pill--neutral { background: var(--surface-sunken); color: var(--text-2); }
        .desktop-pill--more { background: var(--surface-sunken); color: var(--text-3); }

        .desktop-status {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: .78rem; font-weight: 600; color: var(--text);
        }
        .desktop-status::before { content: ""; width: 7px; height: 7px; border-radius: 50%; background: var(--text-3); }
        .desktop-status--active { color: var(--success); }
        .desktop-status--active::before { background: var(--success); }
        .desktop-status--inactive { color: var(--text-2); }
        .desktop-status--inactive::before { background: #c0c6cf; }

        /* ====================== OVERFLOW / CONTEXT MENU ====================== */
        .desktop-rowmenu { position: relative; display: inline-flex; justify-content: flex-end; width: 100%; }
        .desktop-overflow {
            width: 28px; height: 28px;
            display: inline-flex; align-items: center; justify-content: center;
            border: 1px solid transparent; border-radius: var(--r-md);
            background: transparent; color: var(--text-3); cursor: pointer;
            opacity: .5;
            transition: opacity .12s ease, background .12s ease, color .12s ease, border-color .12s ease;
        }
        .desktop-overflow svg { width: 18px; height: 18px; }
        table.desktop-list tbody tr:hover .desktop-overflow { opacity: 1; color: var(--text-2); }
        .desktop-overflow:hover { background: #fff; border-color: var(--stroke-strong); color: var(--brand); box-shadow: var(--shadow-2); }
        .desktop-overflow[aria-expanded="true"] { opacity: 1; background: var(--brand-soft); border-color: var(--brand-soft-2); color: var(--brand); }

        .desktop-menu {
            position: fixed; z-index: var(--z-menu); min-width: 176px;
            display: none; flex-direction: column; padding: 4px;
            background: var(--surface);
            border: 1px solid var(--stroke);
            border-radius: var(--r-md);
            box-shadow: var(--shadow-16);
        }
        .desktop-menu.is-open { display: flex; animation: dxmenu .12s ease; }
        @keyframes dxmenu { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: none; } }
        .desktop-menu__item {
            display: flex; align-items: center; gap: 10px;
            height: 32px; padding: 0 10px;
            border: 0; border-radius: var(--r-sm);
            background: transparent; color: var(--text);
            font: inherit; font-size: .82rem; font-weight: 500;
            text-align: left; cursor: pointer;
        }
        .desktop-menu__item svg { width: 16px; height: 16px; color: var(--text-3); }
        .desktop-menu__item:hover { background: var(--brand-soft); color: var(--brand); }
        .desktop-menu__item:hover svg { color: var(--brand); }
        .desktop-menu__item--danger { color: var(--danger); }
        .desktop-menu__item--danger svg { color: var(--danger); }
        .desktop-menu__item--danger:hover { background: var(--danger-soft); color: var(--danger); }
        .desktop-menu__item--danger:hover svg { color: var(--danger); }
        .desktop-menu__divider { height: 1px; margin: 4px 6px; background: var(--divider); }

        /* ====================== PAGINATION ====================== */
        .desktop-list-foot {
            display: flex; align-items: center; justify-content: space-between;
            gap: 16px; flex-wrap: wrap;
            padding: 6px 12px;
            border-top: 1px solid var(--stroke);
            background: var(--surface-alt);
            font-size: .76rem; color: var(--text-2);
        }
        .desktop-pager { display: flex; align-items: center; gap: 3px; }
        .desktop-pager__btn {
            min-width: 28px; height: 28px; padding: 0 7px;
            display: inline-flex; align-items: center; justify-content: center;
            border: 1px solid transparent; border-radius: var(--r-md);
            background: transparent; color: var(--text-2);
            font: inherit; font-size: .78rem; font-weight: 600; line-height: 1; cursor: pointer;
            transition: background .1s ease;
        }
        .desktop-pager__btn:hover { background: var(--surface-sunken); color: var(--text); }
        .desktop-pager__btn.is-active { background: var(--brand); color: #fff; }
        .desktop-pager__btn.is-active:hover { background: var(--brand-hover); }
        .desktop-pager__btn.is-disabled { opacity: .4; cursor: default; pointer-events: none; }

        .desktop-list__empty { padding: 56px 16px; text-align: center; color: var(--text-3); font-size: .85rem; }

        /* ====================== DIALOG / MODAL ====================== */
        .desktop-modal {
            position: fixed; inset: 0; z-index: var(--z-modal);
            display: none; align-items: flex-start; justify-content: center;
            padding: 56px 20px 20px;
            background: rgba(23, 30, 38, .42);
            backdrop-filter: blur(1px);
        }
        .desktop-modal.is-open { display: flex; animation: dxfade .14s ease; }
        @keyframes dxfade { from { opacity: 0; } to { opacity: 1; } }

        .desktop-modal__dialog {
            width: min(920px, 100%);
            max-height: calc(100vh - 76px);
            display: flex; flex-direction: column;
            background: var(--surface);
            border-radius: var(--r-xl);
            box-shadow: var(--shadow-16);
            overflow: hidden;
            animation: dxpop .16s ease;
        }
        @keyframes dxpop { from { transform: translateY(8px) scale(.99); opacity: .6; } to { transform: none; opacity: 1; } }

        .desktop-modal__head {
            display: flex; align-items: center; justify-content: space-between; gap: 16px;
            padding: 14px 18px 10px;
            flex: none;
        }
        .desktop-modal__title { font-size: 1rem; font-weight: 600; letter-spacing: -.01em; }
        .desktop-modal__close {
            width: 32px; height: 32px; flex: none;
            display: inline-flex; align-items: center; justify-content: center;
            border: 0; border-radius: var(--r-md);
            background: transparent; color: var(--text-2);
            font-size: 1.1rem; cursor: pointer;
        }
        .desktop-modal__close:hover { background: var(--surface-sunken); color: var(--text); }

        .desktop-modal__body { padding: 2px 18px 6px; overflow: auto; }
        /* Footer estándar de modales. Por defecto alinea a la derecha (compatible
           con modales que ponen los botones directos). Para el patrón de dos grupos
           —riesgo a la izquierda, confirmación a la derecha— envolver así:
           <div class="desktop-modal__foot">
             <div class="desktop-modal__foot-group desktop-modal__foot-group--start">…riesgo…</div>
             <div class="desktop-modal__foot-group">…confirmar…</div>
           </div>
           El --start usa margin-right:auto para empujar el grupo de riesgo a la izq. */
        .desktop-modal__foot {
            display: flex; align-items: center; justify-content: flex-end; gap: 8px;
            flex-wrap: wrap;
            padding: 12px 18px 16px;
            flex: none;
        }
        .desktop-modal__foot-group { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .desktop-modal__foot-group--start { margin-right: auto; }

        /* ====================== FORMS (Windows 11 Settings) ====================== */
        .desktop-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px 16px; }
        .desktop-field { display: flex; flex-direction: column; gap: 5px; }
        .desktop-field[hidden] { display: none !important; }
        .desktop-field--full { grid-column: 1 / -1; }
        .desktop-field label {
            font-size: .79rem; font-weight: 600; color: var(--text);
            letter-spacing: 0; text-transform: none;
        }
        .desktop-field input,
        .desktop-field select,
        .desktop-field textarea {
            min-height: 34px;
            border: 1px solid var(--stroke-strong); border-radius: var(--r-md);
            padding: 0 12px;
            font: inherit; font-size: .85rem; color: var(--text);
            background: var(--surface); outline: none;
            transition: border-color .12s ease, box-shadow .12s ease;
        }
        .desktop-field select {
            padding-right: 34px; appearance: none; cursor: pointer;
            background:
                linear-gradient(45deg, transparent 50%, var(--text-3) 50%),
                linear-gradient(135deg, var(--text-3) 50%, transparent 50%),
                var(--surface);
            background-position: calc(100% - 15px) 14px, calc(100% - 10px) 14px, 0 0;
            background-size: 5px 5px, 5px 5px, 100% 100%;
            background-repeat: no-repeat;
        }
        .desktop-field input:hover,
        .desktop-field select:hover { border-color: var(--text-3); }
        .desktop-field input:focus,
        .desktop-field select:focus,
        .desktop-field textarea:focus { border-color: var(--brand); box-shadow: 0 0 0 1px var(--brand); }
        .desktop-field input::placeholder { color: var(--text-3); }
        .desktop-field small { color: var(--text-2); font-size: .74rem; }
        .desktop-field-section {
            margin-top: 16px; padding-top: 12px;
            border-top: 1px solid var(--divider);
        }
        .desktop-field-section__title { font-size: .92rem; font-weight: 600; }
        .desktop-field-section__hint { font-size: .78rem; color: var(--text-2); margin-top: 2px; }

        /* Tag picker (multi-select) */
        .desktop-tag-picker {
            position: relative;
            min-height: 34px; padding: 4px 5px;
            border: 1px solid var(--stroke-strong); border-radius: var(--r-md);
            background: var(--surface);
            transition: border-color .12s ease, box-shadow .12s ease;
        }
        .desktop-tag-picker.is-focused { border-color: var(--brand); box-shadow: 0 0 0 1px var(--brand); }
        .desktop-tag-picker__tags { display: flex; flex-wrap: wrap; gap: 6px; }
        .desktop-tag-picker__chip {
            display: inline-flex; align-items: center; gap: 6px;
            height: 24px; padding: 0 6px 0 10px;
            border-radius: 999px;
            background: var(--brand-soft); color: var(--brand);
            font-size: .76rem; font-weight: 600;
        }
        .desktop-tag-picker__chip button {
            border: 0; background: transparent; color: inherit; cursor: pointer;
            width: 16px; height: 16px; display: inline-flex; align-items: center; justify-content: center;
            border-radius: 50%; font-size: .85rem; line-height: 1; padding: 0;
        }
        .desktop-tag-picker__chip button:hover { background: rgba(15,108,189,.18); }
        .desktop-tag-picker__input {
            flex: 1; min-width: 120px;
            border: 0; outline: none; background: transparent;
            padding: 4px 6px; font: inherit; font-size: .82rem; color: var(--text);
        }
        .desktop-tag-picker__dropdown {
            position: absolute; left: -1px; right: -1px; top: calc(100% + 4px);
            display: none; flex-direction: column; padding: 5px;
            max-height: 220px; overflow: auto;
            background: var(--surface);
            border: 1px solid var(--stroke);
            border-radius: var(--r-lg);
            box-shadow: var(--shadow-16);
            z-index: 6;
        }
        .desktop-tag-picker__option {
            padding: 8px 10px; border-radius: var(--r-sm);
            font-size: .83rem; cursor: pointer;
        }
        .desktop-tag-picker__option:hover { background: var(--surface-sunken); }

        /* Checkbox (Windows 11) */
        .desktop-check { display: flex; align-items: flex-start; gap: 10px; padding: 6px 4px; border-radius: var(--r-sm); cursor: pointer; }
        .desktop-check:hover { background: var(--surface-sunken); }
        .desktop-check input { position: absolute; opacity: 0; width: 0; height: 0; }
        .desktop-check__box {
            flex: none; width: 18px; height: 18px; margin-top: 1px;
            border: 1.5px solid var(--text-3); border-radius: var(--r-sm);
            display: inline-flex; align-items: center; justify-content: center;
            color: #fff; background: var(--surface);
            transition: background .1s ease, border-color .1s ease;
        }
        .desktop-check__box svg { width: 12px; height: 12px; opacity: 0; }
        .desktop-check input:checked + .desktop-check__box { background: var(--brand); border-color: var(--brand); }
        .desktop-check input:checked + .desktop-check__box svg { opacity: 1; }
        .desktop-check input:focus-visible + .desktop-check__box { outline: 2px solid var(--brand); outline-offset: 2px; }
        .desktop-check__label { font-size: .84rem; line-height: 1.45; color: var(--text); }

        /* ====================== TOAST ====================== */
        .desktop-feedback {
            position: fixed; right: 18px; bottom: 18px; z-index: var(--z-toast);
            min-width: 280px; max-width: 400px;
            display: none; align-items: flex-start; gap: 12px;
            padding: 14px 16px;
            background: var(--surface);
            border: 1px solid var(--stroke);
            border-left: 3px solid var(--text-3);
            border-radius: var(--r-lg);
            box-shadow: var(--shadow-16);
            font-size: .84rem; line-height: 1.45; color: var(--text);
        }
        .desktop-feedback.is-visible { display: flex; animation: dxslide .18s ease; }
        @keyframes dxslide { from { transform: translateY(10px); opacity: 0; } to { transform: none; opacity: 1; } }
        .desktop-feedback.is-success { border-left-color: var(--success); }
        .desktop-feedback.is-error { border-left-color: var(--danger); }
        /* Contenedor para toasts dinámicos (apilados) */
        #dx-toast-wrap { position: fixed; right: 18px; bottom: 18px; z-index: var(--z-toast); display: flex; flex-direction: column-reverse; gap: 10px; pointer-events: none; }
        #dx-toast-wrap .desktop-feedback { position: static; pointer-events: auto; }

        /* ====================== RESPONSIVE ====================== */
        .nav-scrim { display: none; }
        @media (max-width: 860px) {
            .app { grid-template-columns: 1fr; }
            .app__nav {
                position: fixed; top: var(--header-h); bottom: 0; left: 0;
                width: 280px; --rail-w: 280px;
                transform: translateX(-100%); transition: transform .2s ease;
                box-shadow: var(--shadow-16); z-index: var(--z-drawer);
            }
            .app.is-nav-open .app__nav { transform: none; }
            .app.is-nav-open .nav-scrim { display: block; position: fixed; inset: var(--header-h) 0 0 0; background: rgba(0,0,0,.32); z-index: calc(var(--z-drawer) - 1); }
            .app__main { grid-column: 1; grid-row: 2; }
            .header__search { display: none; }
            .desktop-content { padding: 12px; }
            .desktop-form-grid { grid-template-columns: 1fr; }
            .desktop-toolbar { padding: 7px 12px; }
        }
        @media (max-width: 560px) {
            .desktop-toolbar__search { width: 100%; }
        }
    </style>
    @stack('desktop-styles')
</head>
<body>
    <div class="app" id="app-shell">
        <script>
            /* Restaura el estado del menú antes de pintar el rail (sin parpadeo).
               Por defecto inicia colapsado, como Outlook / Microsoft 365. */
            (function () {
                try {
                    var s = document.getElementById('app-shell');
                    var pref = localStorage.getItem('dx-nav-collapsed');
                    var collapsed = pref === null ? true : pref === '1';
                    if (collapsed && !window.matchMedia('(max-width: 860px)').matches) {
                        s.classList.add('is-rail');
                    }
                } catch (e) {}
            })();
        </script>
        <div class="nav-scrim" data-nav-close></div>

        <header class="app__header">
            <button type="button" class="hburger" id="nav-toggle" aria-label="Alternar navegación">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div class="brand">
                <span class="brand__logo">LS</span>
                <span class="brand__name">La I. Suriana</span>
            </div>

            <div class="header__spacer"></div>

            <label class="header__search">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                <input type="search" placeholder="Buscar en La Suriana" aria-label="Buscar">
            </label>

            <div class="header__icons">
                <button type="button" class="iconbtn" aria-label="Notificaciones" title="Notificaciones">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
                </button>
                <button type="button" class="iconbtn" aria-label="Configuración" title="Configuración">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6h.09A1.65 1.65 0 0 0 11 3.09V3a2 2 0 0 1 4 0v.09A1.65 1.65 0 0 0 16 4.6a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9v.09a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                </button>
                <span class="avatar" title="{{ $desktopUser?->usr_nombre }}">{{ strtoupper($desktopInitials) }}</span>
            </div>
        </header>

        <nav class="app__nav" aria-label="Navegación principal">
            <div class="nav-group">
                <div class="nav-group__label">General</div>
                <a href="{{ route('desktop.dashboard') }}" class="nav-item {{ request()->routeIs('desktop.dashboard') ? 'is-active' : '' }}">
                    <span class="nav-item__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg></span>
                    <span class="nav-item__label">Dashboard</span>
                </a>
            </div>

            <div class="nav-group">
                <div class="nav-group__label">Seguridad</div>
                <a href="{{ route('desktop.usuarios') }}" class="nav-item {{ request()->routeIs('desktop.usuarios') || request()->routeIs('desktop.roles') || request()->routeIs('desktop.permisos') || request()->routeIs('desktop.bitacora') ? 'is-active' : '' }}">
                    <span class="nav-item__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
                    <span class="nav-item__label">Usuarios y roles</span>
                </a>
            </div>

            <div class="nav-group">
                <div class="nav-group__label">Operación</div>
                <a href="{{ route('desktop.operacion.gestion_configuraciones.index') }}" class="nav-item {{ request()->routeIs('desktop.operacion.gestion_configuraciones.*') ? 'is-active' : '' }}">
                    <span class="nav-item__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></span>
                    <span class="nav-item__label">Gestión y configuraciones</span>
                </a>
                <a href="{{ route('desktop.operacion.catalogo_comercial.index') }}" class="nav-item {{ request()->routeIs('desktop.operacion.catalogo_comercial.*') ? 'is-active' : '' }}">
                    <span class="nav-item__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/></svg></span>
                    <span class="nav-item__label">Catálogo comercial</span>
                </a>
                <a href="{{ route('desktop.operacion.inventario.index') }}" class="nav-item {{ request()->routeIs('desktop.operacion.inventario.*') ? 'is-active' : '' }}">
                    <span class="nav-item__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2.97 12.92A2 2 0 0 0 2 14.63v3.24a2 2 0 0 0 .97 1.71l3 1.8a2 2 0 0 0 2.06 0L12 19v-5.5l-5-3-4.03 2.42Z"/><path d="m7 16.5-4.74-2.85M7 16.5l5-3M7 16.5v5.17"/><path d="M12 13.5V19l3.97 2.38a2 2 0 0 0 2.06 0l3-1.8a2 2 0 0 0 .97-1.71v-3.24a2 2 0 0 0-.97-1.71L17 10.5l-5 3Z"/><path d="m17 16.5-5-3M17 16.5l4.74-2.85M17 16.5v5.17"/><path d="M7.97 4.42A2 2 0 0 0 7 6.13v4.37l5 3 5-3V6.13a2 2 0 0 0-.97-1.71l-3-1.8a2 2 0 0 0-2.06 0l-3 1.8Z"/><path d="M12 8 7.26 5.15M12 8l4.74-2.85M12 13.5V8"/></svg></span>
                    <span class="nav-item__label">Inventario</span>
                </a>
                <a href="{{ route('desktop.operacion.pedido_piso.index') }}" class="nav-item {{ request()->routeIs('desktop.operacion.pedido_piso.*') ? 'is-active' : '' }}">
                    <span class="nav-item__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg></span>
                    <span class="nav-item__label">Pedido de piso</span>
                </a>
                <a href="{{ route('pos.index') }}" class="nav-item {{ request()->routeIs('pos.*') ? 'is-active' : '' }}">
                    <span class="nav-item__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 7h8"/><path d="M8 11h8"/><path d="M8 15h2"/><path d="M14 15h2"/></svg></span>
                    <span class="nav-item__label">Punto de venta</span>
                </a>
                <span class="nav-item is-disabled">
                    <span class="nav-item__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m7 14 3-3 3 3 5-6"/></svg></span>
                    <span class="nav-item__label">Ventas</span>
                </span>
                <span class="nav-item is-disabled">
                    <span class="nav-item__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h6"/></svg></span>
                    <span class="nav-item__label">Reportes</span>
                </span>
            </div>
        </nav>

        <section class="app__main">
            <div class="desktop-toolbar">
                @yield('desktop-toolbar')
            </div>
            <main class="desktop-content">
                @yield('content')
            </main>
        </section>
    </div>

    @if(session('success'))
        <div class="desktop-feedback is-success is-visible" id="desktop-feedback-success" role="status" aria-live="polite">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--success); flex: none; margin-top: 1px;"><path d="M20 6 9 17l-5-5"/></svg>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="desktop-feedback is-error is-visible" id="desktop-feedback-error" role="alert" aria-live="assertive">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--danger); flex: none; margin-top: 1px;"><circle cx="12" cy="12" r="10"/><path d="M15 9 9 15"/><path d="m9 9 6 6"/></svg>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <div id="dx-toast-wrap"></div>

    <script src="{{ $templateAssetBase }}/vendor/libs/jquery/jquery.js"></script>
    <script>
        (function () {
            var shell = document.getElementById('app-shell');
            var toggle = document.getElementById('nav-toggle');
            var mql = window.matchMedia('(max-width: 860px)');

            toggle.addEventListener('click', function () {
                if (mql.matches) {
                    shell.classList.toggle('is-nav-open');
                } else {
                    shell.classList.toggle('is-rail');
                    try { localStorage.setItem('dx-nav-collapsed', shell.classList.contains('is-rail') ? '1' : '0'); } catch (e) {}
                }
            });
            /* Al cruzar el breakpoint, reaplica la preferencia guardada en escritorio. */
            mql.addEventListener('change', function (e) {
                if (e.matches) {
                    shell.classList.remove('is-rail');
                } else {
                    shell.classList.remove('is-nav-open');
                    var pref = null;
                    try { pref = localStorage.getItem('dx-nav-collapsed'); } catch (e2) {}
                    shell.classList.toggle('is-rail', pref === null ? true : pref === '1');
                }
            });
            document.querySelectorAll('[data-nav-close]').forEach(function (el) {
                el.addEventListener('click', function () { shell.classList.remove('is-nav-open'); });
            });

            /* ---- Menús contextuales reutilizables (botón overflow "...") ---- */
            function closeMenus() {
                document.querySelectorAll('.desktop-menu.is-open').forEach(function (m) {
                    m.classList.remove('is-open');
                    var t = m.previousElementSibling;
                    if (t && t.hasAttribute('data-overflow')) t.setAttribute('aria-expanded', 'false');
                });
            }
            function placeMenu(trigger, menu) {
                var r = trigger.getBoundingClientRect();
                menu.style.visibility = 'hidden';
                menu.classList.add('is-open');
                var mw = menu.offsetWidth, mh = menu.offsetHeight;
                var left = Math.min(r.right - mw, window.innerWidth - mw - 8);
                var top = r.bottom + 4;
                if (top + mh > window.innerHeight - 8) top = Math.max(8, r.top - mh - 4);
                menu.style.left = Math.max(8, left) + 'px';
                menu.style.top = top + 'px';
                menu.style.visibility = '';
            }
            document.addEventListener('click', function (e) {
                var trigger = e.target.closest('[data-overflow]');
                var insideMenu = e.target.closest('.desktop-menu');
                if (!trigger && !insideMenu) { closeMenus(); return; }
                if (trigger) {
                    e.preventDefault();
                    var menu = trigger.parentElement.querySelector('.desktop-menu');
                    if (!menu) return;
                    var wasOpen = menu.classList.contains('is-open');
                    closeMenus();
                    if (!wasOpen) {
                        placeMenu(trigger, menu);
                        trigger.setAttribute('aria-expanded', 'true');
                    }
                    return;
                }
                if (e.target.closest('.desktop-menu__item')) {
                    window.setTimeout(closeMenus, 0);
                }
            });
            window.addEventListener('scroll', closeMenus, true);
            window.addEventListener('resize', closeMenus);
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeMenus(); });

            ['desktop-feedback-success', 'desktop-feedback-error'].forEach(function (id) {
                var feedback = document.getElementById(id);
                if (!feedback) return;

                window.setTimeout(function () {
                    feedback.classList.remove('is-visible');
                    window.setTimeout(function () {
                        if (feedback.parentNode) feedback.parentNode.removeChild(feedback);
                    }, 180);
                }, 3200);
            });
        })();
    </script>

    <script>
        /* DesktopUI: toasts + confirm/prompt Fluent (reemplaza alert/confirm/prompt nativos) */
        (function () {
            function el(html) { const d = document.createElement('div'); d.innerHTML = html.trim(); return d.firstChild; }
            const wrap = document.getElementById('dx-toast-wrap');

            function toast(message, type, title) {
                type = type || 'info';
                const icon = type === 'success'
                    ? '<path d="M20 6 9 17l-5-5"/>'
                    : type === 'error'
                        ? '<circle cx="12" cy="12" r="10"/><path d="M15 9 9 15"/><path d="m9 9 6 6"/>'
                        : '<circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/>';
                const color = type === 'success' ? 'var(--success)' : type === 'error' ? 'var(--danger)' : 'var(--brand)';
                const cls = type === 'success' ? 'is-success' : type === 'error' ? 'is-error' : '';
                const t = el('<div class="desktop-feedback is-visible ' + cls + '" role="status" aria-live="polite">' +
                    '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:' + color + ';flex:none;margin-top:1px;">' + icon + '</svg>' +
                    '<div></div></div>');
                t.querySelector('div').textContent = (title ? title + ': ' : '') + (message || '');
                wrap.appendChild(t);
                setTimeout(function () { t.classList.remove('is-visible'); setTimeout(function () { t.remove(); }, 200); }, 3600);
            }

            let dlg, titleEl, msgEl, fieldEl, inputEl, okBtn, resolver, mode;
            function ensure() {
                if (dlg) return;
                dlg = el('<div class="desktop-modal" id="dx-dialog" aria-hidden="true">' +
                    '<div class="desktop-modal__dialog" style="max-width:460px;">' +
                    '<div class="desktop-modal__head"><div class="desktop-modal__title"></div>' +
                    '<button type="button" class="desktop-modal__close" data-dx-cancel aria-label="Cerrar">&times;</button></div>' +
                    '<div class="desktop-modal__body"><p style="margin:0 0 12px;font-size:.86rem;color:var(--text-2);" data-dx-msg></p>' +
                    '<div class="desktop-field" data-dx-field hidden><textarea data-dx-input rows="2"></textarea></div></div>' +
                    '<div class="desktop-modal__foot"><div class="desktop-modal__foot-group">' +
                    '<button type="button" class="desktop-btn desktop-btn--default" data-dx-cancel>Cancelar</button>' +
                    '<button type="button" class="desktop-btn desktop-btn--primary" data-dx-ok>Aceptar</button></div></div>' +
                    '</div></div>');
                document.body.appendChild(dlg);
                titleEl = dlg.querySelector('.desktop-modal__title');
                msgEl = dlg.querySelector('[data-dx-msg]');
                fieldEl = dlg.querySelector('[data-dx-field]');
                inputEl = dlg.querySelector('[data-dx-input]');
                okBtn = dlg.querySelector('[data-dx-ok]');
                dlg.addEventListener('click', function (e) {
                    if (e.target === dlg || e.target.closest('[data-dx-cancel]')) finish(mode === 'prompt' ? null : false);
                });
                okBtn.addEventListener('click', function () { finish(mode === 'prompt' ? (inputEl.value || '') : true); });
                document.addEventListener('keydown', function (e) {
                    if (!dlg.classList.contains('is-open')) return;
                    if (e.key === 'Escape') finish(mode === 'prompt' ? null : false);
                    else if (e.key === 'Enter' && mode === 'confirm') finish(true);
                });
            }
            function finish(val) {
                dlg.classList.remove('is-open'); dlg.setAttribute('aria-hidden', 'true');
                const r = resolver; resolver = null; if (r) r(val);
            }
            function open(opts, m) {
                ensure(); mode = m;
                opts = (typeof opts === 'string') ? { message: opts } : (opts || {});
                titleEl.textContent = opts.title || (m === 'confirm' ? 'Confirmar' : 'Capturar dato');
                msgEl.textContent = opts.message || '';
                msgEl.style.display = opts.message ? '' : 'none';
                fieldEl.hidden = (m !== 'prompt');
                inputEl.value = m === 'prompt' ? (opts.value || '') : '';
                inputEl.setAttribute('placeholder', m === 'prompt' ? (opts.placeholder || '') : '');
                okBtn.textContent = opts.okText || (m === 'confirm' ? 'Confirmar' : 'Aceptar');
                okBtn.classList.toggle('desktop-btn--danger', !!opts.danger);
                okBtn.classList.toggle('desktop-btn--primary', !opts.danger);
                dlg.classList.add('is-open'); dlg.setAttribute('aria-hidden', 'false');
                setTimeout(function () { (m === 'prompt' ? inputEl : okBtn).focus(); }, 50);
                return new Promise(function (res) { resolver = res; });
            }

            window.DesktopUI = {
                toast: toast,
                message: function (title, message, type) { toast(message, type, title); },
                confirm: function (opts) { return open(opts, 'confirm'); },
                prompt: function (opts) { return open(opts, 'prompt'); },
            };
        })();
    </script>
    @stack('desktop-vendor-scripts')
    @stack('desktop-scripts')
</body>
</html>
