@extends('layouts.desktop')

@section('title', 'Existencias')

@push('desktop-vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/select2/select2.css') }}" />
@endpush

@push('desktop-styles')
    <style>
        /* ===== Command bar densa (M365 / Azure): buscar + filtros frecuentes + acciones ===== */
        .desktop-inv-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-bottom: 1px solid var(--stroke);
            background: var(--surface-alt);
        }
        .desktop-inv-bar__search {
            position: relative;
            flex: 1 1 300px;
            max-width: 420px;
            display: flex;
            align-items: center;
        }
        .desktop-inv-bar__search svg {
            position: absolute;
            left: 9px;
            width: 15px;
            height: 15px;
            color: var(--text-3);
            pointer-events: none;
        }
        .desktop-inv-bar__search input {
            width: 100%;
            height: 32px;
            padding: 0 10px 0 30px;
            border: 1px solid var(--stroke-strong);
            border-radius: var(--r-md);
            background: var(--surface);
            color: var(--text);
            font: inherit;
            font-size: .82rem;
        }
        .desktop-inv-bar__search input::placeholder { color: var(--text-3); }
        .desktop-inv-bar__search input:hover { border-color: var(--text-3); }
        .desktop-inv-bar__search input:focus {
            outline: none;
            border-color: var(--brand);
            box-shadow: 0 0 0 1px var(--brand);
        }
        .desktop-inv-bar__field {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }
        .desktop-inv-bar__cap {
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .03em;
            text-transform: uppercase;
            color: var(--text-3);
        }
        .desktop-inv-bar__field .desktop-toolbar__select {
            height: 32px;
            max-width: 180px;
        }
        .desktop-inv-bar__spacer { flex: 1 1 auto; }
        .desktop-inv-bar__divider { width: 1px; height: 22px; background: var(--stroke); }
        .desktop-inv-bar .desktop-btn { height: 32px; }
        /* Botón "Filtros avanzados" (Azure Portal) */
        .desktop-inv-filterbtn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 32px;
            padding: 0 11px;
            border: 1px solid var(--stroke-strong);
            border-radius: var(--r-md);
            background: var(--surface);
            color: var(--text);
            font: inherit;
            font-size: .8rem;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
        }
        .desktop-inv-filterbtn svg { width: 15px; height: 15px; }
        .desktop-inv-filterbtn:hover { background: var(--surface-sunken); }
        .desktop-inv-filterbtn.is-active { border-color: var(--brand); color: var(--brand); }
        .desktop-inv-filterbtn__badge {
            display: none;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 999px;
            background: var(--brand);
            color: var(--on-brand);
            font-size: .68rem;
            font-weight: 700;
            line-height: 1;
        }
        .desktop-inv-filterbtn__badge.is-visible { display: inline-flex; }
        .desktop-inv-clear {
            border: 0;
            background: transparent;
            color: var(--brand);
            font: inherit;
            font-size: .78rem;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            padding: 0 4px;
        }
        .desktop-inv-clear:hover { text-decoration: underline; }
        .desktop-inv-clear[hidden] { display: none; }

        /* Dropdown Exportar (usa el menú contextual compartido .desktop-menu) */
        .desktop-inv-export { position: relative; display: inline-flex; }
        .desktop-inv-export .desktop-btn svg { width: 13px; height: 13px; }

        /* Tallas: SIEMPRE una sola línea horizontal (el scroll lo da la tabla
           entre las columnas congeladas, estilo paneles de Excel) */
        .desktop-inv-strip {
            display: flex;
            flex-wrap: nowrap;
            gap: 4px;
        }
        .desktop-inv-strip > .desktop-inv-pill { flex: 0 0 auto; }
        .desktop-inv-pill {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0;
            min-width: 38px;
            min-height: 32px;
            padding: 2px 7px;
            border: 1px solid var(--stroke-strong);
            border-radius: var(--r-md);
            background: var(--surface);
            line-height: 1.05;
            white-space: nowrap;
        }
        .desktop-inv-pill__name {
            font-size: .62rem;
            font-weight: 600;
            color: var(--text-3);
            letter-spacing: .02em;
            text-transform: uppercase;
        }
        .desktop-inv-pill__val {
            font-size: .85rem;
            font-weight: 700;
            color: var(--text);
        }
        .desktop-inv-pill[data-psk-id] {
            cursor: context-menu;
        }
        .desktop-inv-pill--ok {
            border-color: rgba(17, 121, 80, .2);
            background: rgba(17, 121, 80, .08);
        }
        .desktop-inv-pill--ok .desktop-inv-pill__val { color: var(--success); }
        .desktop-inv-pill--zero {
            border-color: rgba(181, 124, 0, .2);
            background: rgba(181, 124, 0, .08);
        }
        .desktop-inv-pill--zero .desktop-inv-pill__val { color: #8a5a00; }
        .desktop-inv-pill--new {
            border-color: rgba(47, 111, 237, .2);
            background: rgba(47, 111, 237, .08);
        }
        .desktop-inv-pill--new .desktop-inv-pill__val { color: var(--brand); }
        .desktop-inv-pill--na {
            border-style: dashed;
            opacity: .68;
        }
        .desktop-inv-pill--na .desktop-inv-pill__val { color: var(--text-2); }
        /* ===== Celda Producto: título + detalle en 2 líneas compactas ===== */
        .desktop-inv-prod-cell { white-space: nowrap; }
        .desktop-inv-prod {
            display: flex;
            flex-direction: column;
            gap: 0;
            max-width: 360px;
            line-height: 1.22;
        }
        .desktop-inv-prod__title {
            font-weight: 600; color: var(--text);
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .desktop-inv-prod__sub {
            font-size: .74rem; color: var(--text-2);
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }

        /* ===== Celda Color en UNA sola línea ===== */
        .desktop-inv-colorcell { white-space: nowrap; }
        .desktop-inv-colorcell__name { font-weight: 600; color: var(--text); }
        .desktop-inv-colorcell__sku { font-size: .76rem; color: var(--text-2); }

        .desktop-inv-num {
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
            font-weight: 600;
        }
        /* Totales: emphasis ligero (negrita + separador de columna), sin bloques */
        .desktop-inv-table thead th.desktop-inv-th-metric { color: var(--text) !important; font-weight: 700 !important; }
        .desktop-inv-table tbody td.desktop-inv-metric { font-size: .86rem; }
        .desktop-inv-table thead th.desktop-inv-metric--start,
        .desktop-inv-table tbody td.desktop-inv-metric--start { border-left: 1px solid var(--stroke) !important; }
        .desktop-inv-num--strong { color: var(--text); font-weight: 700; }
        .desktop-inv-num--total { color: var(--brand); font-weight: 800; font-size: .92rem; }
        .desktop-inv-empty {
            color: var(--text-2);
            font-size: .78rem;
        }
        /* Densidad alta: filas compactas (estilo ERP/hoja de trabajo) */
        .desktop-inv-table tbody td { padding-top: 4px !important; padding-bottom: 4px !important; vertical-align: middle; }
        .desktop-inv-table tbody tr.is-selected td { background: #eaf1fd; }

        /* ===== Área de tabla con scroll horizontal SIEMPRE visible ===== */
        .desktop-inv-tablearea {
            position: relative;
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }
        /* Vertical nativo visible; horizontal lo controla el scrollbar propio */
        .desktop-inv-wrap { flex: 1 1 auto; min-height: 0; overflow-x: hidden; overflow-y: auto; }

        /* Scrollbar horizontal PROPIO (track + thumb), siempre visible.
           No depende del scrollbar del SO (overlay en macOS lo ocultaría). */
        .desktop-inv-hbar {
            position: relative;
            flex: 0 0 auto;
            height: 14px;
            background: var(--surface-sunken);
            border-bottom: 1px solid var(--stroke);
            cursor: pointer;
            user-select: none;
            touch-action: none;
        }
        .desktop-inv-hbar[hidden] { display: none; }
        .desktop-inv-hbar__thumb {
            position: absolute;
            top: 3px;
            height: 8px;
            min-width: 36px;
            border-radius: 999px;
            background: var(--stroke-strong);
            cursor: grab;
            transition: background .12s ease;
        }
        .desktop-inv-hbar__thumb:hover { background: var(--text-3); }
        .desktop-inv-hbar.is-dragging { cursor: grabbing; }
        .desktop-inv-hbar.is-dragging .desktop-inv-hbar__thumb { background: var(--brand); cursor: grabbing; }

        /* Indicadores de borde (sombra/degradado) cuando hay más contenido lateral */
        .desktop-inv-edge {
            position: absolute;
            top: 15px; bottom: 0;
            width: 26px;
            pointer-events: none;
            opacity: 0;
            transition: opacity .15s ease;
            z-index: 4;
        }
        .desktop-inv-edge--left { left: 0; background: linear-gradient(90deg, rgba(15,23,42,.10), transparent); }
        .desktop-inv-edge--right { right: 0; background: linear-gradient(270deg, rgba(15,23,42,.10), transparent); }
        .desktop-inv-tablearea.show-left .desktop-inv-edge--left { opacity: 1; }
        .desktop-inv-tablearea.show-right .desktop-inv-edge--right { opacity: 1; }

        .desktop-inv-context {
            position: fixed;
            z-index: var(--z-menu);
            min-width: 210px;
            padding: 4px;
            border: 1px solid var(--stroke);
            border-radius: var(--r-md);
            background: var(--surface);
            box-shadow: var(--shadow-16);
        }
        .desktop-inv-context[hidden] {
            display: none;
        }
        .desktop-inv-context__item {
            width: 100%;
            height: 34px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 10px;
            border: 0;
            border-radius: var(--r-sm);
            background: transparent;
            color: var(--text);
            font: inherit;
            font-size: .81rem;
            text-align: left;
            cursor: pointer;
            white-space: nowrap;
        }
        .desktop-inv-context__item svg {
            width: 16px;
            height: 16px;
            flex: 0 0 auto;
            color: var(--text-3);
        }
        .desktop-inv-context__item:hover svg { color: var(--brand); }
        .desktop-inv-context__item:hover {
            background: var(--brand-soft);
            color: var(--brand);
        }
        .desktop-inv-context__item:disabled {
            opacity: .52;
            cursor: not-allowed;
        }
        /* ===== Drawer de filtros avanzados (Azure Portal) ===== */
        .desktop-inv-drawer { position: fixed; inset: 0; z-index: var(--z-drawer); display: none; }
        .desktop-inv-drawer.is-open { display: block; }
        .desktop-inv-drawer__scrim {
            position: absolute; inset: 0;
            background: rgba(16, 24, 40, .28);
            animation: dxfade .14s ease;
        }
        .desktop-inv-drawer__panel {
            position: absolute; top: 0; right: 0;
            height: 100%;
            width: min(380px, 100%);
            display: flex; flex-direction: column;
            background: var(--surface);
            border-left: 1px solid var(--stroke);
            box-shadow: var(--shadow-16);
            animation: dxinvdrawer .18s ease;
        }
        @keyframes dxinvdrawer { from { transform: translateX(20px); opacity: .5; } to { transform: none; opacity: 1; } }
        .desktop-inv-drawer__head {
            display: flex; align-items: center; justify-content: space-between;
            gap: 8px;
            padding: 14px 16px;
            border-bottom: 1px solid var(--stroke);
        }
        .desktop-inv-drawer__title { font-size: .95rem; font-weight: 600; letter-spacing: -.01em; }
        .desktop-inv-drawer__close {
            display: inline-flex; align-items: center; justify-content: center;
            width: 28px; height: 28px;
            border: 0; border-radius: var(--r-md);
            background: transparent; color: var(--text-2);
            font-size: 1.2rem; line-height: 1; cursor: pointer;
        }
        .desktop-inv-drawer__close:hover { background: var(--surface-sunken); color: var(--text); }
        .desktop-inv-drawer__body { flex: 1 1 auto; overflow: auto; padding: 14px 16px; display: grid; gap: 12px; align-content: start; }
        .desktop-inv-drawer__body .desktop-field input,
        .desktop-inv-drawer__body .desktop-field select { min-height: 34px; }
        .desktop-inv-drawer__foot {
            display: flex; align-items: center; justify-content: space-between;
            gap: 8px;
            padding: 12px 16px;
            border-top: 1px solid var(--stroke);
        }

        .select2-container--default .select2-selection--single,
        .select2-container--default .select2-selection--multiple {
            min-height: 38px;
            border: 1px solid var(--stroke);
            border-radius: var(--r-md);
            background: var(--surface);
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
            color: var(--text);
            font-size: .84rem;
            padding-left: 12px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
            right: 8px;
        }
        .select2-dropdown {
            border-color: var(--stroke);
            border-radius: var(--r-md);
            overflow: hidden;
            box-shadow: var(--shadow-16);
        }
        @media (max-width: 1100px) {
            .desktop-inv-bar { flex-wrap: wrap; }
            .desktop-inv-bar__search { flex: 1 1 100%; max-width: none; order: -1; }
            .desktop-inv-bar__spacer { display: none; }
        }
        @media (max-width: 860px) {
            .desktop-inv-drawer__panel { width: 100%; }
        }
    </style>
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        @php($activeSubmenu = 'existencias')
        @include('desktop.operacion.inventario._subnav')
        <span class="desktop-toolbar__divider"></span>
        <button type="button" class="desktop-btn desktop-btn--ghost" id="btn-recargar-existencias">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
            Actualizar
        </button>
    </div>
@endsection

@section('content')
    <section class="desktop-pane">
        {{-- Command bar compacta: filtros frecuentes + acciones --}}
        <div class="desktop-inv-bar">
            <div class="desktop-inv-bar__search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <input id="flt-buscar" type="search" placeholder="Código, producto, marca, línea, concepto o color">
            </div>

            <div class="desktop-inv-bar__field">
                <span class="desktop-inv-bar__cap">Sucursal</span>
                <select class="desktop-toolbar__select" id="flt-scl">
                    @foreach($opciones['sucursales'] as $sucursal)
                        <option value="{{ $sucursal->scl_id }}" @selected((int) $sucursal->scl_id === (int) ($defaultSucursalId ?? 0))>{{ $sucursal->scl_nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="desktop-inv-bar__field">
                <span class="desktop-inv-bar__cap">Almacén</span>
                <select class="desktop-toolbar__select" id="flt-alm">
                    <option value="">Todos</option>
                    @foreach($opciones['almacenes'] as $almacen)
                        <option value="{{ $almacen->alm_id }}" data-scl="{{ $almacen->alm_scl_id }}">{{ $almacen->alm_nombre }}</option>
                    @endforeach
                </select>
            </div>

            <span class="desktop-inv-bar__spacer"></span>

            <button type="button" class="desktop-inv-clear" id="btn-limpiar" hidden>Limpiar</button>
            <button type="button" class="desktop-inv-filterbtn" id="btn-inv-filtros" aria-haspopup="dialog" aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                Filtros
                <span class="desktop-inv-filterbtn__badge" id="inv-filtros-badge"></span>
            </button>

            <span class="desktop-inv-bar__divider"></span>

            <button type="button" class="desktop-btn desktop-btn--primary" id="btn-filtrar">Aplicar</button>
            <div class="desktop-inv-export">
                <button type="button" class="desktop-btn desktop-btn--default" id="btn-exportar" data-overflow aria-haspopup="true" aria-expanded="false">
                    Exportar
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div class="desktop-menu" role="menu" aria-label="Exportar">
                    <button type="button" class="desktop-menu__item" id="btn-exportar-excel">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="m9 13 6 6"/><path d="m15 13-6 6"/></svg>
                        Exportar Excel
                    </button>
                    <button type="button" class="desktop-menu__item" id="btn-exportar-pdf">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 15h6"/><path d="M9 18h6"/></svg>
                        Exportar PDF
                    </button>
                </div>
            </div>
        </div>

        <div class="desktop-inv-tablearea" id="inv-tablearea">
            {{-- Scrollbar propio SIEMPRE visible (no depende del scrollbar del SO) --}}
            <div class="desktop-inv-hbar" id="inv-hbar" hidden>
                <div class="desktop-inv-hbar__thumb" id="inv-hbar-thumb"></div>
            </div>
            <div class="desktop-list-wrap desktop-inv-wrap" id="inv-wrap">
            <table class="desktop-list desktop-inv-table" id="tbl-existencias">
                <thead>
                    <tr>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Línea</th>
                        <th>Producto</th>
                        <th>Color</th>
                        <th>Tallas</th>
                        <th class="desktop-inv-th-metric desktop-inv-metric--start" style="text-align:right;">Total art.</th>
                        <th class="desktop-inv-th-metric" style="text-align:right;">Precio</th>
                        <th class="desktop-inv-th-metric" style="text-align:right;">Costo</th>
                        <th class="desktop-inv-th-metric" style="text-align:right;">Total</th>
                    </tr>
                </thead>
            </table>
            </div>
            <span class="desktop-inv-edge desktop-inv-edge--left" aria-hidden="true"></span>
            <span class="desktop-inv-edge desktop-inv-edge--right" aria-hidden="true"></span>
        </div>

        <div class="desktop-list-foot">
            <div id="desktop-existencias-info"></div>
            <div id="desktop-existencias-pagination" class="desktop-pager"></div>
        </div>
    </section>

    {{-- Drawer de filtros avanzados (Marca, Modelo, Línea, Concepto, Producto) --}}
    <aside class="desktop-inv-drawer" id="desktop-inv-drawer" aria-hidden="true" role="dialog" aria-label="Filtros avanzados">
        <div class="desktop-inv-drawer__scrim" data-close-inv-drawer></div>
        <div class="desktop-inv-drawer__panel">
            <div class="desktop-inv-drawer__head">
                <div class="desktop-inv-drawer__title">Filtros avanzados</div>
                <button type="button" class="desktop-inv-drawer__close" data-close-inv-drawer aria-label="Cerrar">&times;</button>
            </div>
            <div class="desktop-inv-drawer__body">
                <div class="desktop-field">
                    <label for="flt-mrc">Marca</label>
                    <select id="flt-mrc">
                        <option value="">Todas</option>
                        @foreach($opciones['marcas'] as $marca)
                            <option value="{{ $marca->mrc_id }}">{{ $marca->mrc_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="desktop-field">
                    <label for="flt-mdl">Modelo</label>
                    <select id="flt-mdl">
                        <option value="">Todos</option>
                        @foreach($opciones['modelos'] as $modelo)
                            <option value="{{ $modelo->mdl_id }}">{{ $modelo->mdl_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="desktop-field">
                    <label for="flt-lna">Línea</label>
                    <select id="flt-lna">
                        <option value="">Todas</option>
                        @foreach($opciones['lineas'] as $linea)
                            <option value="{{ $linea->lna_id }}">{{ $linea->lna_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="desktop-field">
                    <label for="flt-ctg">Concepto</label>
                    <select id="flt-ctg">
                        <option value="">Todos</option>
                        @foreach($opciones['categorias'] as $categoria)
                            <option value="{{ $categoria->ctg_id }}" data-lna="{{ $categoria->ctg_lna_id }}">{{ $categoria->ctg_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="desktop-field">
                    <label for="flt-prd">Producto</label>
                    <select id="flt-prd"></select>
                </div>
            </div>
            <div class="desktop-inv-drawer__foot">
                <button type="button" class="desktop-btn desktop-btn--default" id="btn-limpiar-drawer">Limpiar</button>
                <button type="button" class="desktop-btn desktop-btn--primary" id="btn-aplicar-drawer">Aplicar filtros</button>
            </div>
        </div>
    </aside>

    <div id="desktop-inv-context" class="desktop-inv-context" hidden>
        <button type="button" class="desktop-inv-context__item" id="desktop-inv-action-kardex">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01"/><path d="M3 12h.01"/><path d="M3 18h.01"/></svg>
            Ver kardex completo
        </button>
    </div>
@endsection

@push('desktop-vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('vendor-template/assets/vendor/libs/select2/select2.js') }}"></script>
@endpush

@push('desktop-scripts')
    <script>
        (function () {
            const rutas = {
                data: @json(route('desktop.operacion.inventario.existencias.data')),
                productos: @json(route('desktop.operacion.inventario.productos.buscar')),
                exportarExcel: @json(route('desktop.operacion.inventario.existencias.exportar.excel')),
                exportarPdf: @json(route('desktop.operacion.inventario.existencias.exportar.pdf')),
                kardexDetalle: @json(url('/desktop/operacion/inventario/kardex/__SKU__/detalle')),
            };

            const $table = $('#tbl-existencias');
            let tabla = null;
            let selectedRowIdx = null;
            let pendingSelect = null;
            let contextTarget = null;

            function getDataRows() {
                return $table.find('tbody tr').not('.dataTables_empty');
            }

            function escapeHtml(text) {
                return $('<div>').text(text ?? '').html();
            }

            // Celda Producto: máximo 2 líneas (título + marca·código·línea·concepto).
            // Solo presentación; las columnas originales siguen existiendo ocultas
            // para no alterar el mapeo de orden server-side por índice de columna.
            function renderProducto(_, __, row) {
                const title = row.producto_nombre || row.concepto_nombre || '-';
                const sub = [row.marca_nombre, row.linea_nombre, row.concepto_nombre, row.producto_codigo]
                    .filter(Boolean).join(' · ');
                const tip = title + (sub ? ' · ' + sub : '');
                return '<div class="desktop-inv-prod" title="' + escapeHtml(tip) + '">' +
                    '<span class="desktop-inv-prod__title">' + escapeHtml(title) + '</span>' +
                    (sub ? '<span class="desktop-inv-prod__sub">' + escapeHtml(sub) + '</span>' : '') +
                '</div>';
            }

            function renderTallas(tallas) {
                if (!Array.isArray(tallas) || !tallas.length) {
                    return '<span class="desktop-inv-empty">Sin tallas configuradas.</span>';
                }

                return '<div class="desktop-inv-strip">' + tallas.map(function (item) {
                    const estado = item.estado || 'sin_sku';
                    const mod = estado === 'con_existencia'
                        ? 'ok'
                        : (estado === 'cero'
                            ? 'zero'
                            : (estado === 'sin_historial' ? 'new' : 'na'));
                    const valor = item.existencia === null ? 'N/D' : Number(item.existencia).toFixed(2).replace(/\.00$/, '');
                    const pskId = item.psk_id ? String(item.psk_id) : '';
                    const colorVatId = item.color_vat_id ? String(item.color_vat_id) : '';
                    const tallaKey = item.talla_key ? String(item.talla_key) : '';
                    const title = estado === 'sin_historial'
                        ? 'SKU generado sin historial de existencias en almacenes'
                        : (estado === 'sin_sku' ? 'Sin SKU generado' : '');

                    return '<span class="desktop-inv-pill desktop-inv-pill--' + mod + '" title="' + escapeHtml(title) + '" data-psk-id="' + escapeHtml(pskId) + '" data-talla="' + escapeHtml(item.talla || 'Base') + '" data-talla-key="' + escapeHtml(tallaKey) + '" data-color-vat-id="' + escapeHtml(colorVatId) + '">' +
                        '<span class="desktop-inv-pill__name">' + escapeHtml(item.talla || 'Base') + '</span>' +
                        '<span class="desktop-inv-pill__val">' + escapeHtml(valor) + '</span>' +
                    '</span>';
                }).join('') + '</div>';
            }

            // Celda Color en UNA sola línea: "Sin color · 11 SKU(s)"
            function renderColor(_, __, row) {
                const color = row.color_nombre || 'Sin color';
                const skus = (row.sku_total || 0) + ' SKU(s)';
                return '<div class="desktop-inv-colorcell" title="' + escapeHtml(color + ' · ' + skus) + '">' +
                    '<span class="desktop-inv-colorcell__name">' + escapeHtml(color) + '</span>' +
                    ' · <span class="desktop-inv-colorcell__sku">' + escapeHtml(skus) + '</span>' +
                '</div>';
            }

            function formatMoney(value) {
                return Number(value || 0).toLocaleString('es-MX', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function aplicarFiltroConceptosPorLinea() {
                const lineaId = $('#flt-lna').val();
                const $concepto = $('#flt-ctg');
                const actual = $concepto.val();

                $concepto.find('option').each(function () {
                    const valor = $(this).val();
                    const linea = $(this).data('lna');
                    const mostrar = !valor || !lineaId || String(linea || '') === String(lineaId);
                    $(this).prop('hidden', !mostrar);
                });

                if (actual && $concepto.find('option[value="' + actual + '"]:not([hidden])').length === 0) {
                    $concepto.val('');
                }
            }

            function syncAlmacenesPorSucursal() {
                const sucursalId = String($('#flt-scl').val() || '');
                const $almacen = $('#flt-alm');
                const actual = String($almacen.val() || '');

                $almacen.find('option').each(function () {
                    const valor = String($(this).val() || '');
                    if (!valor) {
                        $(this).prop('hidden', false);
                        return;
                    }

                    const scl = String($(this).data('scl') || '');
                    $(this).prop('hidden', sucursalId !== '' && scl !== sucursalId);
                });

                if (actual && $almacen.find('option[value="' + actual + '"]:not([hidden])').length === 0) {
                    $almacen.val('');
                }
            }

            function initSelect2() {
                $('#flt-prd').select2({
                    width: '100%',
                    placeholder: 'Todos los productos',
                    allowClear: true,
                    ajax: {
                        url: rutas.productos,
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                q: params.term || '',
                                page: params.page || 1,
                                min_scl_id: $('#flt-scl').val(),
                                min_alm_id: $('#flt-alm').val(),
                                prd_mrc_id: $('#flt-mrc').val(),
                                prd_mdl_id: $('#flt-mdl').val(),
                                prd_lna_id: $('#flt-lna').val(),
                                prd_ctg_id: $('#flt-ctg').val(),
                            };
                        },
                        processResults: function (data) {
                            return data;
                        }
                    }
                });
            }

            function renderFooter() {
                if (!tabla) return;
                const info = tabla.page.info();
                const total = info.recordsDisplay;

                if (!total) {
                    $('#desktop-existencias-info').text('Mostrando 0 registros');
                    $('#desktop-existencias-pagination').empty();
                    return;
                }

                $('#desktop-existencias-info').text('Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + total + ' registros');

                const buttons = [];
                const current = info.page;
                const totalPages = info.pages;
                buttons.push({ label: '‹', page: 'previous', disabled: current === 0 });
                for (let i = 0; i < totalPages; i += 1) {
                    buttons.push({ label: String(i + 1), page: i, active: i === current });
                }
                buttons.push({ label: '›', page: 'next', disabled: current >= totalPages - 1 });

                const html = buttons.map(function (button) {
                    const classes = ['desktop-pager__btn', button.active ? 'is-active' : '', button.disabled ? 'is-disabled' : ''].filter(Boolean).join(' ');
                    return '<button type="button" class="' + classes + '" data-page="' + button.page + '"' + (button.disabled ? ' disabled' : '') + '>' + button.label + '</button>';
                }).join('');

                $('#desktop-existencias-pagination').html(html);
            }

            function buildTabla() {
                tabla = $table.DataTable({
                    processing: true,
                    serverSide: true,
                    pageLength: 50,
                    searching: false,
                    lengthChange: false,
                    autoWidth: false,
                    responsive: false,
                    order: [[0, 'asc'], [1, 'asc'], [4, 'asc']],
                    dom: 'rt',
                    language: {
                        processing: 'Cargando...',
                        emptyTable: 'No hay registros disponibles',
                        zeroRecords: 'No se encontraron coincidencias'
                    },
                    ajax: {
                        url: rutas.data,
                        data: function (d) {
                            d.min_scl_id = $('#flt-scl').val();
                            d.min_alm_id = $('#flt-alm').val();
                            d.prd_mrc_id = $('#flt-mrc').val();
                            d.prd_mdl_id = $('#flt-mdl').val();
                            d.prd_lna_id = $('#flt-lna').val();
                            d.prd_ctg_id = $('#flt-ctg').val();
                            d.prd_id = $('#flt-prd').val();
                            d.buscar = $('#flt-buscar').val();
                        }
                    },
                    columns: [
                        // Columnas 0-2 ocultas: su contenido se fusiona en "Producto" (col 3).
                        // Se conservan en el array para preservar los índices de orden
                        // server-side (0=marca, 1=modelo, 2=linea, ...).
                        { data: 'marca_nombre', visible: false },
                        { data: 'modelo_nombre', visible: false },
                        { data: 'linea_nombre', visible: false },
                        { data: null, className: 'desktop-inv-prod-cell', render: renderProducto },
                        { data: 'color_nombre', render: renderColor },
                        { data: 'tallas', orderable: false, searchable: false, render: renderTallas },
                        { data: 'total_articulos', className: 'desktop-inv-num desktop-inv-metric desktop-inv-metric--start desktop-inv-num--strong', render: function (v) { return formatMoney(v); } },
                        { data: 'precio_unitario', className: 'desktop-inv-num desktop-inv-metric', render: function (v) { return '$ ' + formatMoney(v); } },
                        { data: 'costo_unitario', className: 'desktop-inv-num desktop-inv-metric', render: function (v) { return '$ ' + formatMoney(v); } },
                        { data: 'total_importe_precio', className: 'desktop-inv-num desktop-inv-metric desktop-inv-num--total', render: function (v) { return '$ ' + formatMoney(v); } },
                    ],
                    initComplete: renderFooter,
                    drawCallback: function () {
                        renderFooter();
                        syncInvHScroll();
                        if (pendingSelect === 'first') {
                            pendingSelect = null;
                            selectRow(0);
                        } else if (pendingSelect === 'last') {
                            pendingSelect = null;
                            selectRow(getDataRows().length - 1);
                        } else {
                            clearSelection();
                        }
                    }
                });
            }

            function recargarTabla(reset) {
                if (!tabla) {
                    buildTabla();
                    return;
                }
                tabla.ajax.reload(null, !!reset);
            }

            function limpiarFiltros() {
                $('#flt-mrc').val('');
                $('#flt-mdl').val('');
                $('#flt-lna').val('');
                $('#flt-ctg').val('');
                $('#flt-prd').val(null).trigger('change');
                $('#flt-buscar').val('');
                $('#flt-scl').val(@json((string) ($defaultSucursalId ?? '')));
                $('#flt-alm').val('');
                aplicarFiltroConceptosPorLinea();
                syncAlmacenesPorSucursal();
                recargarTabla(true);
            }

            function filtroActualComoParams() {
                const params = new URLSearchParams();
                const mrc = $('#flt-mrc').val();
                const mdl = $('#flt-mdl').val();
                const lna = $('#flt-lna').val();
                const ctg = $('#flt-ctg').val();
                const scl = $('#flt-scl').val();
                const alm = $('#flt-alm').val();
                const prd = $('#flt-prd').val();
                const prdText = $('#flt-prd').find('option:selected').text().trim();
                const buscar = $('#flt-buscar').val().trim();

                if (scl) params.set('back_min_scl_id', scl);
                if (alm) params.set('back_min_alm_id', alm);
                if (mrc) params.set('back_prd_mrc_id', mrc);
                if (mdl) params.set('back_prd_mdl_id', mdl);
                if (lna) params.set('back_prd_lna_id', lna);
                if (ctg) params.set('back_prd_ctg_id', ctg);
                if (prd) params.set('back_prd_id', prd);
                if (prd && prdText) params.set('back_prd_text', prdText);
                if (buscar) params.set('back_buscar', buscar);

                return params;
            }

            function buildKardexDetalleUrl(pskId) {
                const base = rutas.kardexDetalle.replace('__SKU__', encodeURIComponent(String(pskId)));
                const params = filtroActualComoParams();
                const qs = params.toString();
                return qs ? base + '?' + qs : base;
            }

            function hideContextMenu() {
                contextTarget = null;
                $('#desktop-inv-context').attr('hidden', true);
            }

            function showContextMenu(event, target) {
                const pskId = Number(target?.dataset?.pskId || 0);
                contextTarget = { pskId: pskId, talla: String(target.dataset.talla || 'Base') };

                const $menu = $('#desktop-inv-context');
                $('#desktop-inv-action-kardex').prop('disabled', !pskId);
                $menu.attr('hidden', false);

                const menuWidth = $menu.outerWidth() || 220;
                const menuHeight = $menu.outerHeight() || 48;
                const left = Math.min(event.clientX, window.innerWidth - menuWidth - 12);
                const top = Math.min(event.clientY, window.innerHeight - menuHeight - 12);
                $menu.css({ left: Math.max(8, left) + 'px', top: Math.max(8, top) + 'px' });
            }

            function aplicarFiltrosDesdeQuery() {
                const params = new URLSearchParams(window.location.search);
                function setIfPresent(id, key) {
                    const value = params.get(key);
                    if (value) $(id).val(value);
                }

                setIfPresent('#flt-mrc', 'prd_mrc_id');
                setIfPresent('#flt-mdl', 'prd_mdl_id');
                setIfPresent('#flt-lna', 'prd_lna_id');
                setIfPresent('#flt-ctg', 'prd_ctg_id');
                setIfPresent('#flt-scl', 'min_scl_id');
                setIfPresent('#flt-alm', 'min_alm_id');

                const buscar = params.get('buscar');
                if (buscar) $('#flt-buscar').val(buscar);

                const prdId = params.get('prd_id');
                const prdText = params.get('prd_text');
                if (prdId) {
                    const option = new Option(prdText || ('Producto #' + prdId), prdId, true, true);
                    $('#flt-prd').append(option).trigger('change');
                }
            }

            function selectRow(idx) {
                const rows = getDataRows();
                if (!rows.length) return;
                idx = Math.max(0, Math.min(idx, rows.length - 1));
                selectedRowIdx = idx;
                rows.removeClass('is-selected');
                $(rows[idx]).addClass('is-selected');
                rows[idx].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }

            function clearSelection() {
                getDataRows().removeClass('is-selected');
                selectedRowIdx = null;
            }

            function buildExportUrl(base) {
                const params = new URLSearchParams();
                const map = {
                    min_scl_id: $('#flt-scl').val(),
                    min_alm_id: $('#flt-alm').val(),
                    prd_mrc_id: $('#flt-mrc').val(),
                    prd_mdl_id: $('#flt-mdl').val(),
                    prd_lna_id: $('#flt-lna').val(),
                    prd_ctg_id: $('#flt-ctg').val(),
                    prd_id: $('#flt-prd').val(),
                    buscar: $('#flt-buscar').val().trim()
                };

                Object.keys(map).forEach(function (key) {
                    if (map[key]) params.set(key, map[key]);
                });

                const qs = params.toString();
                return qs ? base + '?' + qs : base;
            }

            // ===== Scrollbar horizontal propio (track + thumb), siempre visible =====
            const invWrapEl = document.getElementById('inv-wrap');
            const invAreaEl = document.getElementById('inv-tablearea');
            const invHbarEl = document.getElementById('inv-hbar');
            const invThumbEl = document.getElementById('inv-hbar-thumb');

            function updateInvEdges() {
                if (!invWrapEl || !invAreaEl) return;
                const max = invWrapEl.scrollWidth - invWrapEl.clientWidth;
                const x = invWrapEl.scrollLeft;
                invAreaEl.classList.toggle('show-left', x > 1);
                invAreaEl.classList.toggle('show-right', x < max - 1);
            }
            // Renombrada: actualiza el thumb del scrollbar propio + indicadores de borde
            function syncInvHScroll() {
                if (!invWrapEl || !invHbarEl) return;
                const sw = invWrapEl.scrollWidth;
                const cw = invWrapEl.clientWidth;
                const overflow = sw > cw + 1;
                invHbarEl.hidden = !overflow;
                if (overflow) {
                    const trackW = invHbarEl.clientWidth;
                    const thumbW = Math.max(36, Math.round(trackW * cw / sw));
                    const maxScroll = sw - cw;
                    const maxThumb = trackW - thumbW;
                    const left = maxScroll > 0 ? Math.round((invWrapEl.scrollLeft / maxScroll) * maxThumb) : 0;
                    invThumbEl.style.width = thumbW + 'px';
                    invThumbEl.style.left = left + 'px';
                }
                updateInvEdges();
            }

            if (invWrapEl && invHbarEl) {
                // La tabla se desplaza -> reposicionar el thumb
                invWrapEl.addEventListener('scroll', syncInvHScroll);
                window.addEventListener('resize', syncInvHScroll);

                // Rueda / trackpad horizontal (overflow-x está oculto en el wrap)
                invWrapEl.addEventListener('wheel', function (e) {
                    const horiz = e.shiftKey ? e.deltaY : e.deltaX;
                    if (!horiz) return;
                    const max = invWrapEl.scrollWidth - invWrapEl.clientWidth;
                    if (max <= 0) return;
                    const before = invWrapEl.scrollLeft;
                    invWrapEl.scrollLeft = Math.max(0, Math.min(max, before + horiz));
                    if (invWrapEl.scrollLeft !== before) e.preventDefault();
                }, { passive: false });

                // Arrastre del thumb
                let hbDrag = false, hbStartX = 0, hbStartScroll = 0;
                invThumbEl.addEventListener('pointerdown', function (e) {
                    hbDrag = true;
                    hbStartX = e.clientX;
                    hbStartScroll = invWrapEl.scrollLeft;
                    invHbarEl.classList.add('is-dragging');
                    invThumbEl.setPointerCapture(e.pointerId);
                    e.preventDefault();
                });
                invThumbEl.addEventListener('pointermove', function (e) {
                    if (!hbDrag) return;
                    const sw = invWrapEl.scrollWidth, cw = invWrapEl.clientWidth;
                    const trackW = invHbarEl.clientWidth, thumbW = invThumbEl.offsetWidth;
                    const maxThumb = trackW - thumbW, maxScroll = sw - cw;
                    const dx = e.clientX - hbStartX;
                    const delta = maxThumb > 0 ? (dx * maxScroll / maxThumb) : 0;
                    invWrapEl.scrollLeft = Math.max(0, Math.min(maxScroll, hbStartScroll + delta));
                });
                function hbEnd() { hbDrag = false; invHbarEl.classList.remove('is-dragging'); }
                invThumbEl.addEventListener('pointerup', hbEnd);
                invThumbEl.addEventListener('pointercancel', hbEnd);

                // Click en el track (fuera del thumb) -> saltar a esa posición
                invHbarEl.addEventListener('pointerdown', function (e) {
                    if (e.target === invThumbEl) return;
                    const rect = invHbarEl.getBoundingClientRect();
                    const sw = invWrapEl.scrollWidth, cw = invWrapEl.clientWidth;
                    const trackW = invHbarEl.clientWidth, thumbW = invThumbEl.offsetWidth;
                    const maxThumb = trackW - thumbW, maxScroll = sw - cw;
                    const target = e.clientX - rect.left - thumbW / 2;
                    invWrapEl.scrollLeft = maxThumb > 0 ? Math.max(0, Math.min(maxScroll, (target / maxThumb) * maxScroll)) : 0;
                });
            }

            // ===== Drawer de filtros avanzados + indicadores de estado =====
            const $drawer = $('#desktop-inv-drawer');
            const defaultScl = @json((string) ($defaultSucursalId ?? ''));

            function contarFiltrosAvanzados() {
                let n = 0;
                ['#flt-mrc', '#flt-mdl', '#flt-lna', '#flt-ctg'].forEach(function (sel) {
                    if ($(sel).val()) n += 1;
                });
                if ($('#flt-prd').val()) n += 1;
                return n;
            }

            function hayFiltroActivo() {
                return contarFiltrosAvanzados() > 0
                    || !!$('#flt-buscar').val().trim()
                    || !!$('#flt-alm').val()
                    || String($('#flt-scl').val() || '') !== String(defaultScl);
            }

            function actualizarIndicadores() {
                const avanzados = contarFiltrosAvanzados();
                $('#inv-filtros-badge').text(avanzados ? avanzados : '').toggleClass('is-visible', avanzados > 0);
                $('#btn-inv-filtros').toggleClass('is-active', avanzados > 0);
                $('#btn-limpiar').prop('hidden', !hayFiltroActivo());
            }

            function abrirDrawer() {
                $drawer.addClass('is-open').attr('aria-hidden', 'false');
                $('#btn-inv-filtros').attr('aria-expanded', 'true');
            }

            function cerrarDrawer() {
                $drawer.removeClass('is-open').attr('aria-hidden', 'true');
                $('#btn-inv-filtros').attr('aria-expanded', 'false');
            }

            initSelect2();
            aplicarFiltroConceptosPorLinea();
            syncAlmacenesPorSucursal();
            aplicarFiltrosDesdeQuery();
            actualizarIndicadores();
            buildTabla();
            syncInvHScroll();

            $('#btn-recargar-existencias').on('click', function () {
                recargarTabla(false);
            });
            $('#flt-scl').on('change', function () {
                syncAlmacenesPorSucursal();
                $('#flt-prd').val(null).trigger('change');
                actualizarIndicadores();
            });
            $('#flt-lna').on('change', function () {
                aplicarFiltroConceptosPorLinea();
                $('#flt-prd').val(null).trigger('change');
                actualizarIndicadores();
            });
            $('#flt-alm, #flt-mrc, #flt-mdl, #flt-ctg').on('change', function () {
                $('#flt-prd').val(null).trigger('change');
                actualizarIndicadores();
            });
            $('#flt-prd').on('change', actualizarIndicadores);
            $('#flt-buscar').on('input', actualizarIndicadores);
            $('#btn-filtrar').on('click', function () {
                recargarTabla(true);
                actualizarIndicadores();
            });
            $('#btn-limpiar').on('click', function () {
                limpiarFiltros();
                actualizarIndicadores();
            });
            $('#btn-inv-filtros').on('click', abrirDrawer);
            $('[data-close-inv-drawer]').on('click', cerrarDrawer);
            $('#btn-aplicar-drawer').on('click', function () {
                recargarTabla(true);
                actualizarIndicadores();
                cerrarDrawer();
            });
            $('#btn-limpiar-drawer').on('click', function () {
                limpiarFiltros();
                actualizarIndicadores();
            });
            $('#btn-exportar-excel').on('click', function () {
                window.location.href = buildExportUrl(rutas.exportarExcel);
            });
            $('#btn-exportar-pdf').on('click', function () {
                window.open(buildExportUrl(rutas.exportarPdf), '_blank');
            });
            $('#flt-buscar').on('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    recargarTabla(true);
                    actualizarIndicadores();
                }
            });
            $('#desktop-existencias-pagination').on('click', '[data-page]', function () {
                if (!tabla) return;
                const page = $(this).data('page');
                if (page === 'previous') {
                    tabla.page('previous').draw('page');
                    return;
                }
                if (page === 'next') {
                    tabla.page('next').draw('page');
                    return;
                }
                tabla.page(Number(page)).draw('page');
            });

            $table.find('tbody').on('click', 'tr', function () {
                const idx = getDataRows().index(this);
                if (idx >= 0) selectRow(idx);
            });

            $(document).on('keydown.desktop-existencias-nav', function (e) {
                if (!tabla || !['ArrowDown', 'ArrowUp'].includes(e.key)) return;
                const tag = (document.activeElement?.tagName || '').toLowerCase();
                if (['input', 'select', 'textarea'].includes(tag)) return;
                e.preventDefault();

                const info = tabla.page.info();
                const rows = getDataRows();
                const count = rows.length;

                if (e.key === 'ArrowDown') {
                    if (selectedRowIdx === null) {
                        selectRow(0);
                    } else if (selectedRowIdx < count - 1) {
                        selectRow(selectedRowIdx + 1);
                    } else if (info.page < info.pages - 1) {
                        pendingSelect = 'first';
                        tabla.page('next').draw('page');
                    }
                } else {
                    if (selectedRowIdx === null) {
                        selectRow(count - 1);
                    } else if (selectedRowIdx > 0) {
                        selectRow(selectedRowIdx - 1);
                    } else if (info.page > 0) {
                        pendingSelect = 'last';
                        tabla.page('previous').draw('page');
                    }
                }
            });

            $table.find('tbody').on('contextmenu', '.desktop-inv-pill[data-psk-id]', function (event) {
                event.preventDefault();
                event.stopPropagation();
                showContextMenu(event, this);
            });

            $('#desktop-inv-action-kardex').on('click', function () {
                if (!contextTarget?.pskId) return;
                const pskId = contextTarget.pskId;
                hideContextMenu();
                window.location.href = buildKardexDetalleUrl(pskId);
            });

            $(document).on('click', function (event) {
                if ($(event.target).closest('#desktop-inv-context,.desktop-inv-pill[data-psk-id]').length) return;
                hideContextMenu();
            });
            $(document).on('keydown', function (event) {
                if (event.key === 'Escape') {
                    hideContextMenu();
                    cerrarDrawer();
                }
            });
        })();
    </script>
@endpush
