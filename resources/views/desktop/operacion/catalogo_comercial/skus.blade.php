@extends('layouts.desktop')

@section('title', 'SKU / Variantes')

@push('desktop-vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
@endpush

@push('desktop-styles')
    <style>
        /* ===== Command bar (data-first): pivot + buscador + filtros ===== */
        .desktop-sku-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 12px;
            border-bottom: 1px solid var(--stroke);
            background: var(--surface-alt);
        }
        .desktop-sku-bar__search {
            position: relative;
            flex: 1 1 auto;
            max-width: 460px;
            display: flex;
            align-items: center;
        }
        .desktop-sku-bar__search svg {
            position: absolute;
            left: 9px;
            width: 15px;
            height: 15px;
            color: var(--text-3);
            pointer-events: none;
        }
        .desktop-sku-bar__search input {
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
        .desktop-sku-bar__search input::placeholder { color: var(--text-3); }
        .desktop-sku-bar__search input:hover { border-color: var(--text-3); }
        .desktop-sku-bar__search input:focus {
            outline: none;
            border-color: var(--brand);
            box-shadow: 0 0 0 1px var(--brand);
        }
        #skus-length {
            min-width: 148px;
        }
        .desktop-sku-bar__spacer { flex: 1 1 auto; }
        .desktop-sku-filterbtn {
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
        .desktop-sku-filterbtn svg { width: 15px; height: 15px; }
        .desktop-sku-filterbtn:hover { background: var(--surface-sunken); }
        .desktop-sku-filterbtn.is-active { border-color: var(--brand); color: var(--brand); }
        .desktop-sku-filterbtn__badge {
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
        .desktop-sku-filterbtn__badge.is-visible { display: inline-flex; }
        .desktop-sku-clear {
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
        .desktop-sku-clear:hover { text-decoration: underline; }
        .desktop-sku-clear[hidden] { display: none; }

        /* ===== Drawer de filtros (Azure Portal) ===== */
        .desktop-drawer { position: fixed; inset: 0; z-index: var(--z-drawer); display: none; }
        .desktop-drawer.is-open { display: block; }
        .desktop-drawer__scrim {
            position: absolute; inset: 0;
            background: rgba(16, 24, 40, .28);
            animation: dxfade .14s ease;
        }
        .desktop-drawer__panel {
            position: absolute; top: 0; right: 0;
            height: 100%;
            width: min(380px, 100%);
            display: flex; flex-direction: column;
            background: var(--surface);
            border-left: 1px solid var(--stroke);
            box-shadow: var(--shadow-16);
            animation: dxdrawer .18s ease;
        }
        @keyframes dxdrawer { from { transform: translateX(20px); opacity: .5; } to { transform: none; opacity: 1; } }
        .desktop-drawer__head {
            display: flex; align-items: center; justify-content: space-between;
            gap: 8px;
            padding: 14px 16px;
            border-bottom: 1px solid var(--stroke);
        }
        .desktop-drawer__title { font-size: .95rem; font-weight: 600; letter-spacing: -.01em; }
        .desktop-drawer__close {
            display: inline-flex; align-items: center; justify-content: center;
            width: 28px; height: 28px;
            border: 0; border-radius: var(--r-md);
            background: transparent; color: var(--text-2);
            font-size: 1.2rem; line-height: 1; cursor: pointer;
        }
        .desktop-drawer__close:hover { background: var(--surface-sunken); color: var(--text); }
        .desktop-drawer__body { flex: 1 1 auto; overflow: auto; padding: 14px 16px; }
        .desktop-drawer__group { display: grid; gap: 12px; }
        .desktop-drawer__group[hidden] { display: none; }
        .desktop-drawer__section-title {
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--text-3);
            margin: 6px 0 -2px;
        }
        .desktop-drawer__foot {
            display: flex; align-items: center; justify-content: space-between;
            gap: 8px;
            padding: 12px 16px;
            border-top: 1px solid var(--stroke);
        }
        .desktop-sku-attrgrid { display: grid; gap: 12px; }
        .desktop-sku-attrgrid[hidden] { display: none; }
        .desktop-drawer__body .desktop-field input,
        .desktop-drawer__body .desktop-field select { min-height: 34px; }

        .desktop-sku-shell {
            display: flex;
            flex-direction: column;
            min-height: 0;
            flex: 1 1 auto;
        }
        .desktop-sku-shell[hidden] {
            display: none;
        }
        .desktop-sku-matrix-wrap {
            padding: 0;
            overflow: auto;
            flex: 1 1 auto;
        }
        .desktop-sku-matrix-empty {
            padding: 18px 12px;
            text-align: center;
            color: var(--text-2);
        }
        .desktop-sku-group-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        /* ===== Matriz densa: una fila = una línea ===== */
        .desktop-sku-group-table th,
        .desktop-sku-group-table td {
            padding: 3px 14px;
            border-bottom: 1px solid var(--divider);
            vertical-align: middle;
            text-align: left;
            font-size: .8rem;
            white-space: nowrap;
        }
        .desktop-sku-group-table tbody tr:hover { background: var(--surface-alt); }
        .desktop-sku-meta { display: grid; gap: 2px; }
        .desktop-sku-meta__title { font-weight: 600; color: var(--text); }
        .desktop-sku-meta__sub { color: var(--text-2); font-size: .75rem; }
        .desktop-sku-group-table thead th {
            position: sticky;
            top: 0;
            background: var(--surface);
            z-index: 1;
            padding-top: 5px;
            padding-bottom: 5px;
            font-size: .66rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--text-3);
            border-bottom: 1px solid var(--stroke);
        }
        /* Columna Producto: nombre arriba y meta debajo para no truncar descripción */
        .desktop-sku-group-table td.is-clip {
            min-width: 340px;
            max-width: 560px;
            overflow: hidden;
            white-space: normal;
        }
        .desktop-sku-line {
            display: grid;
            gap: 2px;
            line-height: 1.3;
        }
        .desktop-sku-line__name { font-weight: 600; color: var(--text); }
        .desktop-sku-line__meta { color: var(--text-2); word-break: break-word; }
        .desktop-sku-line__code { color: var(--text-3); font-variant-numeric: tabular-nums; }
        .desktop-sku-line__sep { color: var(--text-3); margin: 0 6px; }
        /* Color en una sola línea */
        .desktop-sku-color {
            display: block;
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            font-weight: 600;
            color: var(--text);
        }
        /* Variantes en línea: CH · M · G · XL */
        .desktop-sku-strip {
            display: flex;
            flex-wrap: nowrap;
            gap: 4px;
            overflow: hidden;
        }
        .desktop-sku-pillbtn {
            display: inline-flex;
            align-items: center;
            height: 20px;
            padding: 0 7px;
            border: 1px solid var(--stroke);
            border-radius: var(--r-sm);
            background: var(--surface-alt);
            color: var(--text);
            font-size: .69rem;
            font-weight: 600;
            line-height: 1;
            cursor: pointer;
            transition: border-color .14s ease, background .14s ease, color .14s ease;
        }
        .desktop-sku-pillbtn:hover {
            border-color: var(--brand);
            color: var(--brand);
            background: var(--surface);
        }
        .desktop-sku-pillbtn.is-inactive {
            opacity: .55;
        }
        /* Resaltado temporal de la variante recién modificada */
        @keyframes dxflash-ring {
            0%   { box-shadow: 0 0 0 2px var(--brand); }
            70%  { box-shadow: 0 0 0 2px var(--brand); }
            100% { box-shadow: 0 0 0 2px rgba(15, 108, 189, 0); }
        }
        @keyframes dxflash-bg {
            0%   { background-color: var(--brand-soft); }
            70%  { background-color: var(--brand-soft); }
            100% { background-color: rgba(15, 108, 189, 0); }
        }
        .desktop-sku-pillbtn.is-flash {
            animation: dxflash-ring 2.4s ease;
            border-color: var(--brand);
            color: var(--brand);
        }
        table.desktop-list tbody tr.is-flash td {
            animation: dxflash-bg 2.4s ease;
        }
        .desktop-sku-detail {
            margin: 8px 12px 0;
            border: 1px solid var(--stroke);
            border-radius: var(--r-lg);
            background: var(--surface);
            overflow: hidden;
        }
        .desktop-sku-detail[hidden] {
            display: none;
        }
        .desktop-sku-detail__head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            padding: 10px 12px;
            border-bottom: 1px solid var(--divider);
            background: var(--surface-alt);
        }
        .desktop-sku-detail__title {
            margin: 0;
            font-size: .86rem;
            font-weight: 700;
        }
        .desktop-sku-detail__sub {
            margin-top: 2px;
            font-size: .72rem;
            color: var(--text-2);
        }
        .desktop-sku-detail__chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .desktop-sku-detail__chip {
            padding: 4px 8px;
            border: 1px solid var(--stroke);
            border-radius: 999px;
            background: var(--surface);
            font-size: .68rem;
            color: var(--text-2);
        }
        .desktop-sku-detail__body {
            padding: 0;
            overflow: auto;
        }
        .desktop-sku-detail-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .desktop-sku-detail-table th,
        .desktop-sku-detail-table td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--divider);
            border-right: 1px solid var(--divider);
            vertical-align: top;
            min-width: 116px;
        }
        .desktop-sku-detail-table thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: var(--surface);
            font-size: .78rem;
            color: var(--text-2);
            text-align: left;
        }
        .desktop-sku-detail-table thead th:first-child,
        .desktop-sku-detail-table tbody th {
            position: sticky;
            left: 0;
            z-index: 2;
            background: var(--surface);
        }
        .desktop-sku-detail-table tbody th {
            min-width: 150px;
            font-size: .76rem;
            text-align: left;
        }
        .desktop-sku-colhead {
            display: grid;
            gap: 2px;
        }
        .desktop-sku-colhead strong {
            font-size: .78rem;
            color: var(--text);
        }
        .desktop-sku-colhead span {
            font-size: .72rem;
            color: var(--text-2);
        }
        .desktop-sku-cell {
            display: grid;
            gap: 4px;
        }
        .desktop-sku-cell__btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 30px;
            padding: 0 8px;
            border: 1px solid var(--stroke);
            border-radius: var(--r-md);
            background: var(--surface-alt);
            color: var(--text);
            font-size: .71rem;
            font-weight: 700;
            cursor: pointer;
        }
        .desktop-sku-cell__btn.is-inactive {
            opacity: .65;
        }
        .desktop-sku-cell__meta {
            font-size: .68rem;
            color: var(--text-2);
            line-height: 1.35;
        }
        .desktop-sku-cell--empty {
            color: var(--text-2);
            font-size: .72rem;
        }
        .desktop-sku-combo-list {
            display: grid;
            gap: 6px;
        }
        .desktop-sku-combo-item {
            padding: 8px 10px;
            border: 1px solid var(--stroke);
            border-radius: var(--r-md);
            background: var(--surface-alt);
            color: var(--text-2);
            font-size: .76rem;
        }
        @media (max-width: 760px) {
            .desktop-sku-bar { flex-wrap: wrap; }
            .desktop-sku-bar__search { max-width: none; order: 3; flex-basis: 100%; }
            .desktop-drawer__panel { width: 100%; }
        }
    </style>
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        @php($activeSubmenu = 'skus')
        @include('desktop.operacion.catalogo_comercial._subnav')
        <button type="button" class="desktop-btn desktop-btn--ghost" id="btn-recargar-skus">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
            Actualizar
        </button>
    </div>
    <div class="desktop-toolbar__group">
        <select class="desktop-toolbar__select" id="skus-length">
            <option value="25">25 por página</option>
            <option value="50">50 por página</option>
            <option value="100" selected>100 por página</option>
        </select>
    </div>
@endsection

@section('content')
    <section class="desktop-pane">
        <div class="desktop-sku-bar">
            <div class="desktop-pivot" role="tablist" aria-label="Vista de SKU">
                <button type="button" class="desktop-btn desktop-btn--active" data-sku-view="matriz" aria-current="page">Matriz</button>
                <button type="button" class="desktop-btn" data-sku-view="tabla">Listado</button>
            </div>
            <div class="desktop-sku-bar__search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="search" id="skus-search" placeholder="Buscar SKU, código, nombre o variante...">
            </div>
            <div class="desktop-sku-bar__spacer"></div>
            <button type="button" class="desktop-sku-clear" id="btn-sku-clear-all" hidden>Limpiar</button>
            <button type="button" class="desktop-sku-filterbtn" id="btn-sku-filtros" aria-haspopup="dialog" aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                Filtros
                <span class="desktop-sku-filterbtn__badge" id="sku-filtros-badge"></span>
            </button>
        </div>

        <div id="desktop-sku-matrix-shell" class="desktop-sku-shell">
            <div id="desktop-sku-product-detail" class="desktop-sku-detail" hidden>
                <div class="desktop-sku-detail__head">
                    <div>
                        <h3 class="desktop-sku-detail__title" id="desktop-sku-detail-title">Matriz del producto</h3>
                        <div class="desktop-sku-detail__sub" id="desktop-sku-detail-sub">Selecciona un producto para ver su matriz completa.</div>
                    </div>
                    <div class="desktop-sku-detail__chips" id="desktop-sku-detail-chips"></div>
                </div>
                <div class="desktop-sku-detail__body" id="desktop-sku-detail-body"></div>
            </div>

            <div class="desktop-sku-matrix-wrap" id="desktop-sku-group-wrap">
                <div class="desktop-sku-matrix-empty">Cargando variantes...</div>
            </div>
            <div class="desktop-list-foot">
                <div id="desktop-matrix-info"></div>
                <div id="desktop-matrix-pagination" class="desktop-pager"></div>
            </div>
        </div>

        <div id="desktop-sku-table-shell" class="desktop-sku-shell" hidden>
            <div class="desktop-list-wrap">
                <table id="desktop-skus-table" class="desktop-list">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>SKU</th>
                            <th>Combinación</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th style="width:104px;">Estatus</th>
                            <th style="width:56px; text-align:right;">Acciones</th>
                        </tr>
                    </thead>
                </table>
            </div>
            <div class="desktop-list-foot">
                <div id="desktop-skus-info"></div>
                <div id="desktop-skus-pagination" class="desktop-pager"></div>
            </div>
        </div>
    </section>

    <aside class="desktop-drawer" id="desktop-sku-drawer" aria-hidden="true" role="dialog" aria-label="Filtros">
        <div class="desktop-drawer__scrim" data-close-sku-drawer></div>
        <div class="desktop-drawer__panel">
            <div class="desktop-drawer__head">
                <div class="desktop-drawer__title">Filtros</div>
                <button type="button" class="desktop-drawer__close" data-close-sku-drawer aria-label="Cerrar">&times;</button>
            </div>
            <div class="desktop-drawer__body">
                <div class="desktop-drawer__group" id="sku-drawer-matriz">
                    <div class="desktop-field">
                        <label>Producto</label>
                        <select id="mtz-flt-producto">
                            <option value="">Todos los productos</option>
                            @foreach($opciones['productos'] as $producto)
                                <option value="{{ $producto->prd_id }}">{{ $producto->prd_codigo }} - {{ $producto->prd_nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="desktop-field">
                        <label>Marca</label>
                        <select id="mtz-flt-mrc">
                            <option value="">Todas</option>
                            @foreach($opciones['marcas'] as $marca)
                                <option value="{{ $marca->mrc_id }}">{{ $marca->mrc_nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="desktop-field">
                        <label>Modelo</label>
                        <select id="mtz-flt-mdl">
                            <option value="">Todos</option>
                            @foreach($opciones['modelos'] as $modelo)
                                <option value="{{ $modelo->mdl_id }}">{{ $modelo->mdl_nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="desktop-field">
                        <label>Línea</label>
                        <select id="mtz-flt-lna">
                            <option value="">Todas</option>
                            @foreach($opciones['lineas'] as $linea)
                                <option value="{{ $linea->lna_id }}">{{ $linea->lna_nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="desktop-field">
                        <label>Concepto</label>
                        <select id="mtz-flt-ctg">
                            <option value="">Todos</option>
                            @foreach($opciones['categorias'] as $categoria)
                                <option value="{{ $categoria->ctg_id }}">{{ $categoria->ctg_nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="mtz-atributos-wrap" class="desktop-sku-attrgrid" hidden></div>
                </div>

                <div class="desktop-drawer__group" id="sku-drawer-list" hidden>
                    <div class="desktop-field">
                        <label>Producto</label>
                        <select id="flt-sku-producto">
                            <option value="">Todos los productos</option>
                            @foreach($opciones['productos'] as $producto)
                                <option value="{{ $producto->prd_id }}">{{ $producto->prd_codigo }} - {{ $producto->prd_nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="sku-filtros-dinamicos" class="desktop-sku-attrgrid" hidden></div>
                </div>
            </div>
            <div class="desktop-drawer__foot">
                <button type="button" class="desktop-sku-clear" id="btn-sku-drawer-clear">Limpiar todo</button>
                <button type="button" class="desktop-btn desktop-btn--primary" data-close-sku-drawer>Listo</button>
            </div>
        </div>
    </aside>

    <div class="desktop-modal" id="desktop-sku-modal" aria-hidden="true">
        <div class="desktop-modal__dialog">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="desktop-sku-modal-title">Editar SKU / Variante</div>
                <button type="button" class="desktop-modal__close" data-close-sku-modal aria-label="Cerrar">&times;</button>
            </div>
            <form id="desktop-sku-form">
                <div class="desktop-modal__body">
                    <input type="hidden" id="psk_id" name="psk_id">
                    <input type="hidden" id="psk_prd_id" name="psk_prd_id">

                    <div class="desktop-form-grid">
                        <div class="desktop-field">
                            <label>Código SKU</label>
                            <input type="text" id="psk_codigo" name="psk_codigo" maxlength="60" required>
                        </div>
                        <div class="desktop-field">
                            <label>Código de barras SKU</label>
                            <input type="text" id="psk_codigo_barras" name="psk_codigo_barras" maxlength="80">
                        </div>
                        <div class="desktop-field desktop-field--full">
                            <label>Nombre SKU</label>
                            <input type="text" id="psk_nombre" name="psk_nombre" maxlength="180">
                        </div>
                        <div class="desktop-field">
                            <label>Costo</label>
                            <input type="number" id="psk_costo" name="psk_costo" min="0" step="0.01">
                        </div>
                        <div class="desktop-field">
                            <label>Precio venta</label>
                            <input type="number" id="psk_precio" name="psk_precio" min="0" step="0.01">
                        </div>
                        <div class="desktop-field">
                            <label>Stock mínimo</label>
                            <input type="number" id="psk_stock_minimo" name="psk_stock_minimo" min="0" step="1">
                        </div>
                        <div class="desktop-field">
                            <label>Stock máximo</label>
                            <input type="number" id="psk_stock_maximo" name="psk_stock_maximo" min="0" step="1">
                        </div>
                        <div class="desktop-field">
                            <label>Estatus</label>
                            <select id="psk_estatus" name="psk_estatus" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="desktop-field desktop-field--full">
                            <label>Combinación</label>
                            <div class="desktop-sku-combo-list" id="desktop-sku-combinacion"></div>
                        </div>
                    </div>
                </div>
                <div class="desktop-modal__foot">
                    @if($permisosUI['inactivar'] || $permisosUI['eliminar'])
                        <div class="desktop-modal__foot-group desktop-modal__foot-group--start">
                            @if($permisosUI['inactivar'])
                                <button type="button" class="desktop-btn desktop-btn--default" id="btn-sku-toggle-status">Cambiar estatus</button>
                            @endif
                            @if($permisosUI['eliminar'])
                                <button type="button" class="desktop-btn desktop-btn--danger" id="btn-sku-delete">Eliminar</button>
                            @endif
                        </div>
                    @endif
                    <div class="desktop-modal__foot-group">
                        <button type="button" class="desktop-btn desktop-btn--default" data-close-sku-modal>Cancelar</button>
                        <button type="submit" class="desktop-btn desktop-btn--primary">Guardar cambios</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="desktop-modal" id="desktop-sku-confirm-modal" aria-hidden="true">
        <div class="desktop-modal__dialog" style="width:min(440px, 100%);">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title">Confirmar acción</div>
                <button type="button" class="desktop-modal__close" data-close-sku-confirm-modal aria-label="Cerrar">&times;</button>
            </div>
            <div class="desktop-modal__body">
                <p id="desktop-sku-confirm-copy" style="margin:0; color:var(--text-2); line-height:1.55;"></p>
            </div>
            <div class="desktop-modal__foot">
                <button type="button" class="desktop-btn desktop-btn--default" data-close-sku-confirm-modal>Cancelar</button>
                <button type="button" class="desktop-btn desktop-btn--primary" id="desktop-sku-confirm-accept">Continuar</button>
            </div>
        </div>
    </div>

    <div class="desktop-feedback" id="desktop-skus-feedback"></div>
@endsection

@push('desktop-vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endpush

@push('desktop-scripts')
    <script>
        (function () {
            const $table = $('#desktop-skus-table');
            const $feedback = $('#desktop-skus-feedback');
            const $skuModal = $('#desktop-sku-modal');
            const $confirmModal = $('#desktop-sku-confirm-modal');
            const $skuForm = $('#desktop-sku-form');
            const $listAttrs = $('#sku-filtros-dinamicos');
            const $matrixAttrs = $('#mtz-atributos-wrap');
            const $groupWrap = $('#desktop-sku-group-wrap');
            const $detailShell = $('#desktop-sku-product-detail');
            const $drawer = $('#desktop-sku-drawer');
            const permisosUI = @json($permisosUI);
            let skusTable = null;
            let currentView = 'matriz';
            let confirmAction = null;
            let groupedRows = [];
            let matrixPage = 0;
            let pendingFlashId = null;

            $.ajaxSetup({
                cache: false,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const rutas = {
                data: '{{ route('desktop.operacion.catalogo_comercial.skus.data') }}',
                grouped: '{{ route('desktop.operacion.catalogo_comercial.skus.agrupados') }}',
                filters: '{{ route('desktop.operacion.catalogo_comercial.skus.filtros') }}',
                matrix: '{{ route('desktop.operacion.catalogo_comercial.skus.matriz') }}',
                show: function (id) { return '{{ url('/desktop/operacion/catalogo-comercial/skus') }}/' + id; },
                update: function (id) { return '{{ url('/desktop/operacion/catalogo-comercial/skus') }}/' + id; },
                estatus: function (id) { return '{{ url('/desktop/operacion/catalogo-comercial/skus') }}/' + id + '/estatus'; },
                destroy: function (id) { return '{{ url('/desktop/operacion/catalogo-comercial/skus') }}/' + id; }
            };

            const ICONS = {
                edit: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>',
                view: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z"/><circle cx="12" cy="12" r="3"/></svg>',
                toggle: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.2-8.5"/><path d="m9 11 3 3L22 4"/></svg>',
                remove: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>',
                dots: '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="19" cy="12" r="1.8"/></svg>'
            };

            function escapeHtml(value) {
                return String(value || '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#39;');
            }

            function initials(value) {
                return String(value || '?').trim().split(/\s+/).slice(0, 2).map(function (part) {
                    return part.charAt(0);
                }).join('').toUpperCase() || '?';
            }

            function parseError(xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    const firstGroup = Object.values(xhr.responseJSON.errors)[0];
                    if (Array.isArray(firstGroup) && firstGroup[0]) return firstGroup[0];
                }
                return xhr.responseJSON?.message || 'No fue posible completar la operación.';
            }

            function showFeedback(type, message) {
                $feedback.removeClass('is-error is-success is-visible')
                    .addClass(type === 'error' ? 'is-error' : 'is-success')
                    .text(message)
                    .addClass('is-visible');

                window.clearTimeout(showFeedback._timer);
                showFeedback._timer = window.setTimeout(function () {
                    $feedback.removeClass('is-visible');
                }, 3600);
            }

            function openModal($target) {
                $target.addClass('is-open').attr('aria-hidden', 'false');
            }

            function closeModal($target) {
                $target.removeClass('is-open').attr('aria-hidden', 'true');
            }

            function renderStatus(value) {
                const active = value === 'activo';
                return '<span class="desktop-status ' + (active ? 'desktop-status--active' : 'desktop-status--inactive') + '">' + (active ? 'Activo' : 'Inactivo') + '</span>';
            }

            function renderActions(row) {
                const items = [];

                if (permisosUI.editar) {
                    items.push('<button type="button" class="desktop-menu__item btn-editar-sku" data-id="' + row.psk_id + '">' + ICONS.edit + 'Editar</button>');
                }

                items.push('<button type="button" class="desktop-menu__item btn-ver-sku" data-id="' + row.psk_id + '">' + ICONS.view + 'Ver detalle</button>');

                if (permisosUI.inactivar) {
                    items.push('<div class="desktop-menu__divider"></div>');
                    items.push('<button type="button" class="desktop-menu__item btn-toggle-sku" data-id="' + row.psk_id + '" data-estatus="' + (row.psk_estatus === 'activo' ? 'inactivo' : 'activo') + '">' + ICONS.toggle + (row.psk_estatus === 'activo' ? 'Inactivar' : 'Activar') + '</button>');
                }

                if (permisosUI.eliminar) {
                    items.push('<button type="button" class="desktop-menu__item desktop-menu__item--danger btn-eliminar-sku" data-id="' + row.psk_id + '" data-name="' + escapeHtml(row.psk_codigo || row.psk_nombre || 'SKU') + '">' + ICONS.remove + 'Eliminar</button>');
                }

                return '<div class="desktop-rowmenu">' +
                    '<button type="button" class="desktop-overflow" data-overflow aria-haspopup="true" aria-expanded="false" aria-label="Más acciones">' + ICONS.dots + '</button>' +
                    '<div class="desktop-menu">' + items.join('') + '</div>' +
                    '</div>';
            }

            function renderFooter() {
                if (!skusTable) return;
                const info = skusTable.page.info();
                const total = info.recordsDisplay;

                if (!total) {
                    $('#desktop-skus-info').text('Mostrando 0 variantes');
                    $('#desktop-skus-pagination').empty();
                    return;
                }

                $('#desktop-skus-info').text('Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + total + ' variantes');

                const buttons = [];
                buttons.push({ label: '‹', page: 'previous', disabled: info.page === 0 });
                for (let i = 0; i < info.pages; i += 1) {
                    buttons.push({ label: String(i + 1), page: i, active: i === info.page });
                }
                buttons.push({ label: '›', page: 'next', disabled: info.page >= info.pages - 1 });

                $('#desktop-skus-pagination').html(buttons.map(function (button) {
                    const classes = ['desktop-pager__btn', button.active ? 'is-active' : '', button.disabled ? 'is-disabled' : ''].filter(Boolean).join(' ');
                    return '<button type="button" class="' + classes + '" data-page="' + button.page + '"' + (button.disabled ? ' disabled' : '') + '>' + button.label + '</button>';
                }).join(''));

                applyPendingFlash();
            }

            // Resalta por unos segundos la variante recién modificada (si está en la página visible)
            function applyPendingFlash() {
                if (pendingFlashId == null) return;
                const id = String(pendingFlashId);
                pendingFlashId = null;
                let $els;
                if (currentView === 'matriz') {
                    $els = $('#desktop-sku-group-wrap').find('.desktop-sku-pillbtn[data-id="' + id + '"]');
                } else {
                    $els = $('#desktop-skus-table').find('[data-id="' + id + '"]').first().closest('tr');
                }
                if (!$els || !$els.length) return;
                $els.addClass('is-flash');
                window.setTimeout(function () { $els.removeClass('is-flash'); }, 2400);
            }

            // ===== Paginación de la Matriz (mismo patrón/markup que el Listado, client-side) =====
            function matrixPageLen() {
                return Number($('#skus-length').val()) || 10;
            }

            function renderMatrixFooter() {
                const total = groupedRows.length;

                if (!total) {
                    $('#desktop-matrix-info').text('Mostrando 0 grupos');
                    $('#desktop-matrix-pagination').empty();
                    return;
                }

                const len = matrixPageLen();
                const pages = Math.ceil(total / len);
                if (matrixPage > pages - 1) matrixPage = pages - 1;
                if (matrixPage < 0) matrixPage = 0;
                const start = matrixPage * len;
                const end = Math.min(start + len, total);

                $('#desktop-matrix-info').text('Mostrando ' + (start + 1) + ' a ' + end + ' de ' + total + ' grupos');

                const buttons = [];
                buttons.push({ label: '‹', page: 'previous', disabled: matrixPage === 0 });
                for (let i = 0; i < pages; i += 1) {
                    buttons.push({ label: String(i + 1), page: i, active: i === matrixPage });
                }
                buttons.push({ label: '›', page: 'next', disabled: matrixPage >= pages - 1 });

                $('#desktop-matrix-pagination').html(buttons.map(function (button) {
                    const classes = ['desktop-pager__btn', button.active ? 'is-active' : '', button.disabled ? 'is-disabled' : ''].filter(Boolean).join(' ');
                    return '<button type="button" class="' + classes + '" data-page="' + button.page + '"' + (button.disabled ? ' disabled' : '') + '>' + button.label + '</button>';
                }).join(''));
            }

            function renderGroupedPage() {
                const total = groupedRows.length;
                if (!total) {
                    renderGroupedEmpty('No se encontraron registros con los filtros aplicados.');
                    renderMatrixFooter();
                    return;
                }

                const len = matrixPageLen();
                const pages = Math.ceil(total / len);
                if (matrixPage > pages - 1) matrixPage = pages - 1;
                if (matrixPage < 0) matrixPage = 0;
                const start = matrixPage * len;

                renderGroupedRows(groupedRows.slice(start, start + len));
                renderMatrixFooter();
                applyPendingFlash();
            }

            function renderCombinacion(items) {
                const values = Array.isArray(items) ? items : [];
                if (!values.length) return '<span class="desktop-list__meta">Sin combinación</span>';
                const chips = values.slice(0, 2).map(function (value) {
                    return '<span class="desktop-pill desktop-pill--brand">' + escapeHtml(value) + '</span>';
                });
                if (values.length > 2) chips.push('<span class="desktop-pill desktop-pill--more">+' + (values.length - 2) + '</span>');
                return '<div class="desktop-pill-list">' + chips.join('') + '</div>';
            }

            function tableAttrFilters() {
                const filters = {};
                $listAttrs.find('[data-sku-atributo-id]').each(function () {
                    const atrId = String($(this).data('skuAtributoId') || '').trim();
                    const value = String($(this).val() || '').trim();
                    if (atrId && value) filters[atrId] = value;
                });
                return filters;
            }

            function activeFilterCount() {
                let count = 0;
                if (currentView === 'matriz') {
                    ['#mtz-flt-producto', '#mtz-flt-mrc', '#mtz-flt-mdl', '#mtz-flt-lna', '#mtz-flt-ctg'].forEach(function (sel) {
                        if ($(sel).val()) count += 1;
                    });
                    count += Object.keys(matrixAttrFilters()).length;
                } else {
                    if ($('#flt-sku-producto').val()) count += 1;
                    count += Object.keys(tableAttrFilters()).length;
                }
                return count;
            }

            function refreshFilterActions() {
                const count = activeFilterCount();
                $('#sku-filtros-badge').text(count ? count : '').toggleClass('is-visible', count > 0);
                $('#btn-sku-filtros').toggleClass('is-active', count > 0);
                const anyActive = count > 0 || Boolean($('#skus-search').val());
                $('#btn-sku-clear-all').prop('hidden', !anyActive);
            }

            function clearAllFilters() {
                $('#skus-search').val('');
                if (currentView === 'matriz') {
                    $('#mtz-flt-producto, #mtz-flt-mrc, #mtz-flt-mdl, #mtz-flt-lna, #mtz-flt-ctg').val('');
                    $matrixAttrs.prop('hidden', true).html('');
                    $detailShell.prop('hidden', true);
                    refreshFilterActions();
                    reloadMatrix();
                } else {
                    $('#flt-sku-producto').val('');
                    $listAttrs.prop('hidden', true).html('');
                    refreshFilterActions();
                    reloadTable(true);
                }
            }

            function openDrawer() {
                $drawer.addClass('is-open').attr('aria-hidden', 'false');
                $('#btn-sku-filtros').attr('aria-expanded', 'true');
            }

            function closeDrawer() {
                $drawer.removeClass('is-open').attr('aria-hidden', 'true');
                $('#btn-sku-filtros').attr('aria-expanded', 'false');
            }

            function matrixAttrFilters() {
                const filters = {};
                $matrixAttrs.find('.js-mtx-attr').each(function () {
                    const atrId = String($(this).data('atrId') || '').trim();
                    const value = String($(this).val() || '').trim();
                    if (atrId && value) filters[atrId] = value;
                });
                return filters;
            }

            function initTable() {
                skusTable = $table.DataTable({
                    ajax: {
                        url: rutas.data,
                        data: function () {
                            return {
                                psk_prd_id: $('#flt-sku-producto').val() || '',
                                buscar: $('#skus-search').val() || '',
                                atributo_filtros: tableAttrFilters()
                            };
                        },
                        dataSrc: 'data'
                    },
                    processing: true,
                    deferRender: true,
                    responsive: false,
                    autoWidth: false,
                    pageLength: 100,
                    lengthChange: false,
                    searching: false,
                    order: [[1, 'asc']],
                    dom: 'rt',
                    language: {
                        processing: 'Cargando...',
                        emptyTable: 'No hay variantes registradas',
                        zeroRecords: 'No se encontraron variantes'
                    },
                    columns: [
                        {
                            data: null,
                            render: function (row) {
                                const metaProducto = [
                                    row.marca_nombre || 'S/M',
                                    row.modelo_nombre || 'S/Mo',
                                    row.concepto_nombre || 'S/C',
                                    row.descripcion_catalogo || 'S/D',
                                    row.producto_codigo || 'S/CI'
                                ].join(' · ');

                                return '<div class="desktop-cell-primary">' +
                                    '<span class="desktop-avatar-sm">' + escapeHtml(initials(row.producto || row.producto_codigo)) + '</span>' +
                                    '<span><span class="desktop-list__name">' + escapeHtml(row.producto || 'Sin producto') + '</span>' +
                                    '<span class="desktop-list__meta">' + escapeHtml(metaProducto) + '</span></span></div>';
                            }
                        },
                        { data: 'psk_codigo', render: function (v) { return '<span class="desktop-list__name">' + escapeHtml(v || '-') + '</span>'; } },
                        { data: 'combinacion', orderable: false, searchable: false, render: renderCombinacion },
                        { data: 'psk_precio', render: function (v) { return '<span class="desktop-list__name">$' + escapeHtml(Number(v || 0).toFixed(2)) + '</span>'; } },
                        { data: null, render: function (row) { return '<span class="desktop-list__name">Min ' + escapeHtml(row.psk_stock_minimo ?? 0) + '</span><span class="desktop-list__meta">Max ' + escapeHtml(row.psk_stock_maximo ?? 0) + '</span>'; } },
                        { data: 'psk_estatus', render: renderStatus },
                        { data: null, orderable: false, searchable: false, render: renderActions }
                    ],
                    initComplete: renderFooter,
                    drawCallback: renderFooter
                });
            }

            function reloadTable(resetPaging) {
                if (!skusTable) return;
                skusTable.ajax.reload(null, !resetPaging);
            }

            function renderListAttrFilters(meta, selected) {
                const attrs = meta?.atributos || [];
                if (!attrs.length) {
                    $listAttrs.prop('hidden', true).html('');
                    refreshFilterActions();
                    return;
                }

                const html = attrs.map(function (attr) {
                    const options = ['<option value="">Todos</option>'].concat((attr.valores || []).map(function (value) {
                        const isSelected = String((selected || {})[attr.atr_id] || '') === String(value.vat_id) ? ' selected' : '';
                        return '<option value="' + escapeHtml(value.vat_id) + '"' + isSelected + '>' + escapeHtml(value.vat_valor || '') + '</option>';
                    }));
                    return '<div class="desktop-field">' +
                        '<label>' + escapeHtml(attr.atr_nombre || 'Atributo') + '</label>' +
                        '<select class="js-sku-filtro-atributo" data-sku-atributo-id="' + escapeHtml(attr.atr_id) + '">' + options.join('') + '</select>' +
                    '</div>';
                }).join('');

                $listAttrs.html(html).prop('hidden', false);
                refreshFilterActions();
            }

            function loadListFilters(productId, recargar) {
                if (!productId) {
                    renderListAttrFilters(null, {});
                    if (recargar) reloadTable(true);
                    return;
                }

                $.getJSON(rutas.filters, { psk_prd_id: productId })
                    .done(function (response) {
                        renderListAttrFilters(response.data || {}, {});
                        if (recargar) reloadTable(true);
                    })
                    .fail(function () {
                        renderListAttrFilters(null, {});
                        if (recargar) reloadTable(true);
                    });
            }

            function renderMatrixAttrFilters(meta) {
                const attrs = meta?.atributos || [];
                if (!attrs.length) {
                    $matrixAttrs.prop('hidden', true).html('');
                    refreshFilterActions();
                    return;
                }

                const html = attrs.map(function (attr) {
                    const options = ['<option value="">Todos</option>'].concat((attr.valores || []).map(function (value) {
                        return '<option value="' + escapeHtml(value.vat_id) + '">' + escapeHtml(value.vat_valor || '') + '</option>';
                    }));
                    return '<div class="desktop-field">' +
                        '<label>' + escapeHtml(attr.atr_nombre || 'Atributo') + '</label>' +
                        '<select class="js-mtx-attr" data-atr-id="' + escapeHtml(attr.atr_id) + '">' + options.join('') + '</select>' +
                    '</div>';
                }).join('');

                $matrixAttrs.html(html).prop('hidden', false);
                refreshFilterActions();
            }

            function renderGroupedEmpty(message) {
                $groupWrap.html('<div class="desktop-sku-matrix-empty">' + escapeHtml(message || 'No hay datos para mostrar.') + '</div>');
            }

            function renderGroupedRows(rows) {
                if (!Array.isArray(rows) || !rows.length) {
                    renderGroupedEmpty('No se encontraron registros con los filtros aplicados.');
                    return;
                }

                const html = '<table class="desktop-sku-group-table">' +
                    '<thead><tr><th>Producto</th><th style="width:190px;">Color</th><th style="width:1%;">Variantes</th></tr></thead>' +
                    '<tbody>' + rows.map(function (row) {
                        const variantes = row.variantes || [];
                        const pills = variantes.map(function (variant) {
                            return '<button type="button" class="desktop-sku-pillbtn' + (variant.psk_estatus === 'inactivo' ? ' is-inactive' : '') + '" data-action="edit-sku" data-id="' + escapeHtml(variant.psk_id) + '" title="' + escapeHtml(variant.psk_codigo || '') + '">' +
                                escapeHtml(variant.label || variant.psk_codigo || '-') +
                            '</button>';
                        }).join('');

                        const title = row.producto_nombre || row.concepto_nombre || row.marca_nombre || 'Sin producto';
                        const metaProducto = [
                            row.marca_nombre || 'S/M',
                            row.modelo_nombre || 'S/Mo',
                            row.concepto_nombre || 'S/C',
                            row.descripcion_catalogo || 'S/D',
                            row.producto_codigo || 'S/CI'
                        ];
                        const lineParts = '<div class="desktop-sku-line">' +
                            '<span class="desktop-sku-line__name">' + escapeHtml(title) + '</span>' +
                            '<span class="desktop-sku-line__meta">' + escapeHtml(metaProducto.join(' · ')) + '</span>' +
                        '</div>';
                        const lineText = [title].concat(metaProducto).join(' · ');

                        return '<tr>' +
                            '<td class="is-clip" title="' + escapeHtml(lineText) + '">' + lineParts + '</td>' +
                            '<td><span class="desktop-sku-color" title="' + escapeHtml(row.color_nombre || '') + '">' + escapeHtml(row.color_nombre || '—') + '</span></td>' +
                            '<td><div class="desktop-sku-strip">' + (pills || '<span class="desktop-list__meta">Sin variantes</span>') + '</div></td>' +
                        '</tr>';
                    }).join('') + '</tbody></table>';

                $groupWrap.html(html);
            }

            function renderProductMatrixEmpty(message) {
                $detailShell.prop('hidden', false);
                $('#desktop-sku-detail-title').text('Matriz del producto');
                $('#desktop-sku-detail-sub').text(message || 'No fue posible construir la matriz.');
                $('#desktop-sku-detail-chips').html('');
                $('#desktop-sku-detail-body').html('<div class="desktop-sku-matrix-empty">' + escapeHtml(message || 'No hay matriz disponible.') + '</div>');
            }

            function renderProductMatrix(data) {
                if (!data || !data.disponible) {
                    renderProductMatrixEmpty(data?.motivo || 'El producto no tiene una matriz disponible.');
                    return;
                }

                $detailShell.prop('hidden', false);
                $('#desktop-sku-detail-title').text((data.producto?.prd_codigo || '') + ' · ' + (data.producto?.prd_nombre || 'Matriz del producto'));
                $('#desktop-sku-detail-sub').text('Matriz principal por ' + (data.row_attribute?.atr_nombre || 'atributo') + '.');
                $('#desktop-sku-detail-chips').html([
                    '<span class="desktop-sku-detail__chip">Filas: ' + escapeHtml(data.row_attribute?.atr_nombre || 'Atributo') + '</span>',
                    '<span class="desktop-sku-detail__chip">Columnas: ' + escapeHtml((data.column_attributes || []).map(function (item) { return item.atr_nombre; }).join(', ') || 'Sin atributos secundarios') + '</span>'
                ].join(''));

                const head = '<thead><tr><th>' + escapeHtml(data.row_attribute?.atr_nombre || 'Valor') + '</th>' +
                    (data.columnas || []).map(function (columna) {
                        const detail = (columna.detalle || []).join(' · ');
                        return '<th><div class="desktop-sku-colhead"><strong>' + escapeHtml(columna.label || '-') + '</strong>' +
                            (detail ? '<span>' + escapeHtml(detail) + '</span>' : '') +
                        '</div></th>';
                    }).join('') +
                '</tr></thead>';

                const body = '<tbody>' + (data.filas || []).map(function (fila) {
                    const cells = (data.columnas || []).map(function (columna) {
                        const cell = (fila.cells || {})[columna.key] || null;
                        if (!cell) {
                            return '<td><span class="desktop-sku-cell--empty">—</span></td>';
                        }
                        return '<td><div class="desktop-sku-cell">' +
                            '<button type="button" class="desktop-sku-cell__btn' + (cell.psk_estatus === 'inactivo' ? ' is-inactive' : '') + '" data-action="edit-sku" data-id="' + escapeHtml(cell.psk_id) + '">' + escapeHtml(cell.psk_codigo || '-') + '</button>' +
                            '<span class="desktop-sku-cell__meta">$' + escapeHtml(Number(cell.psk_precio || 0).toFixed(2)) + '</span>' +
                        '</div></td>';
                    }).join('');

                    return '<tr><th><div class="desktop-sku-meta"><span class="desktop-sku-meta__title">' + escapeHtml(fila.valor || '-') + '</span></div></th>' + cells + '</tr>';
                }).join('') + '</tbody>';

                $('#desktop-sku-detail-body').html('<table class="desktop-sku-detail-table">' + head + body + '</table>');
            }

            function reloadMatrix(keepPage) {
                $groupWrap.html('<div class="desktop-sku-matrix-empty">Cargando variantes...</div>');

                const groupedParams = {
                    psk_prd_id: $('#mtz-flt-producto').val() || '',
                    prd_mrc_id: $('#mtz-flt-mrc').val() || '',
                    prd_mdl_id: $('#mtz-flt-mdl').val() || '',
                    prd_lna_id: $('#mtz-flt-lna').val() || '',
                    prd_ctg_id: $('#mtz-flt-ctg').val() || '',
                    buscar: $('#skus-search').val() || '',
                    atributo_filtros: matrixAttrFilters()
                };

                // El panel "matriz del producto" (Filas/Columnas) queda desactivado:
                // la vista Matriz solo muestra los registros agrupados normales.
                $detailShell.prop('hidden', true);

                $.getJSON(rutas.grouped, groupedParams)
                    .done(function (response) {
                        groupedRows = response.data || [];
                        if (!keepPage) matrixPage = 0;
                        renderGroupedPage();
                    })
                    .fail(function (xhr) {
                        groupedRows = [];
                        renderGroupedEmpty(parseError(xhr));
                        renderMatrixFooter();
                    });
            }

            function loadMatrixFilters(productId) {
                $matrixAttrs.prop('hidden', true).html('');
                if (!productId) {
                    $detailShell.prop('hidden', true);
                    reloadMatrix();
                    return;
                }

                $.getJSON(rutas.filters, { psk_prd_id: productId })
                    .done(function (response) {
                        renderMatrixAttrFilters(response.data || {});
                        reloadMatrix();
                    })
                    .fail(function () {
                        renderMatrixAttrFilters(null);
                        reloadMatrix();
                    });
            }

            function applyView(view) {
                currentView = view === 'tabla' ? 'tabla' : 'matriz';
                $('[data-sku-view]').each(function () {
                    const active = $(this).data('skuView') === currentView;
                    $(this).toggleClass('desktop-btn--active', active).attr('aria-current', active ? 'page' : null);
                });
                $('#desktop-sku-matrix-shell').prop('hidden', currentView !== 'matriz');
                $('#desktop-sku-table-shell').prop('hidden', currentView !== 'tabla');
                $('#sku-drawer-matriz').prop('hidden', currentView !== 'matriz');
                $('#sku-drawer-list').prop('hidden', currentView !== 'tabla');
                refreshFilterActions();

                if (currentView === 'matriz') {
                    reloadMatrix();
                    return;
                }

                $('#skus-length').val('100');
                if (skusTable) skusTable.page.len(100);
                reloadTable(true);
            }

            function resetSkuForm() {
                $skuForm.get(0).reset();
                $('#psk_id, #psk_prd_id').val('');
                $('#desktop-sku-combinacion').html('');
            }

            function fillSkuForm(data) {
                $('#psk_id').val(data.psk_id || '');
                $('#psk_prd_id').val(String(data.psk_prd_id || ''));
                $('#psk_codigo').val(data.psk_codigo || '');
                $('#psk_codigo_barras').val(data.psk_codigo_barras || '');
                $('#psk_nombre').val(data.psk_nombre || '');
                $('#psk_costo').val(Number(data.psk_costo ?? 0).toFixed(2));
                $('#psk_precio').val(Number(data.psk_precio ?? 0).toFixed(2));
                $('#psk_stock_minimo').val(data.psk_stock_minimo ?? 0);
                $('#psk_stock_maximo').val(data.psk_stock_maximo ?? 0);
                $('#psk_estatus').val(data.psk_estatus || 'activo');
                $('#desktop-sku-combinacion').html((data.combinacion || []).map(function (item) {
                    return '<div class="desktop-sku-combo-item">' + escapeHtml(item) + '</div>';
                }).join('') || '<div class="desktop-sku-combo-item">Sin combinación registrada</div>');
                $('#btn-sku-toggle-status').text(String(data.psk_estatus || '') === 'activo' ? 'Marcar inactivo' : 'Marcar activo');
            }

            function loadSku(id) {
                $.getJSON(rutas.show(id))
                    .done(function (response) {
                        fillSkuForm(response.data || {});
                        openModal($skuModal);
                    })
                    .fail(function (xhr) {
                        showFeedback('error', parseError(xhr));
                    });
            }

            function openConfirm(message, callback) {
                confirmAction = callback;
                $('#desktop-sku-confirm-copy').text(message || '');
                openModal($confirmModal);
            }

            $('[data-close-sku-modal]').on('click', function () { closeModal($skuModal); });
            $('[data-close-sku-confirm-modal]').on('click', function () { closeModal($confirmModal); });

            $('#desktop-sku-confirm-accept').on('click', function () {
                if (typeof confirmAction === 'function') confirmAction();
                confirmAction = null;
                closeModal($confirmModal);
            });

            $skuForm.on('submit', function (event) {
                event.preventDefault();
                const id = $('#psk_id').val();
                if (!id) return;

                $.ajax({
                    url: rutas.update(id),
                    method: 'PUT',
                    data: $skuForm.serialize(),
                    dataType: 'json'
                }).done(function (response) {
                    closeModal($skuModal);
                    pendingFlashId = id;
                    if (currentView === 'matriz') {
                        reloadMatrix(true);
                    } else {
                        reloadTable(true);
                    }
                    showFeedback('success', response.message || 'SKU actualizado correctamente.');
                }).fail(function (xhr) {
                    showFeedback('error', parseError(xhr));
                });
            });

            $('#btn-sku-toggle-status').on('click', function () {
                const skuId = Number($('#psk_id').val() || 0);
                const current = String($('#psk_estatus').val() || 'activo');
                if (!skuId) return;
                const next = current === 'activo' ? 'inactivo' : 'activo';

                openConfirm(next === 'inactivo' ? '¿Deseas marcar este SKU como inactivo?' : '¿Deseas reactivar este SKU?', function () {
                    $.ajax({
                        url: rutas.estatus(skuId),
                        method: 'PATCH',
                        data: { psk_estatus: next },
                        dataType: 'json'
                    }).done(function (response) {
                        closeModal($skuModal);
                        pendingFlashId = skuId;
                        if (currentView === 'matriz') {
                            reloadMatrix(true);
                        } else {
                            reloadTable(true);
                        }
                        showFeedback('success', response.message || 'Estatus actualizado correctamente.');
                    }).fail(function (xhr) {
                        showFeedback('error', parseError(xhr));
                    });
                });
            });

            $('#btn-sku-delete').on('click', function () {
                const skuId = Number($('#psk_id').val() || 0);
                if (!skuId) return;
                openConfirm('¿Deseas eliminar este SKU? Esta acción aplicará borrado lógico.', function () {
                    $.ajax({
                        url: rutas.destroy(skuId),
                        method: 'DELETE',
                        dataType: 'json'
                    }).done(function (response) {
                        closeModal($skuModal);
                        if (currentView === 'matriz') {
                            reloadMatrix(true);
                        } else {
                            reloadTable(true);
                        }
                        showFeedback('success', response.message || 'SKU eliminado correctamente.');
                    }).fail(function (xhr) {
                        showFeedback('error', parseError(xhr));
                    });
                });
            });

            $('#btn-recargar-skus').on('click', function () {
                if (currentView === 'matriz') {
                    reloadMatrix();
                    return;
                }
                reloadTable(false);
            });

            $('#skus-search').on('input', function () {
                refreshFilterActions();
                if (currentView === 'matriz') {
                    reloadMatrix();
                    return;
                }
                reloadTable(true);
            });

            $('#skus-length').on('change', function () {
                if (skusTable) skusTable.page.len(Number(this.value)).draw();
                matrixPage = 0;
                if (currentView === 'matriz') renderGroupedPage();
            });

            $('#flt-sku-producto').on('change', function () {
                loadListFilters($(this).val(), true);
            });

            $listAttrs.on('change', '.js-sku-filtro-atributo', function () {
                refreshFilterActions();
                reloadTable(true);
            });

            $('#mtz-flt-producto').on('change', function () {
                loadMatrixFilters($(this).val());
                refreshFilterActions();
            });

            $('#mtz-flt-mrc, #mtz-flt-mdl, #mtz-flt-lna, #mtz-flt-ctg').on('change', function () {
                refreshFilterActions();
                reloadMatrix();
            });

            $matrixAttrs.on('change', '.js-mtx-attr', function () {
                refreshFilterActions();
                reloadMatrix();
            });

            $('#btn-sku-filtros').on('click', openDrawer);
            $('[data-close-sku-drawer]').on('click', closeDrawer);
            $('#btn-sku-clear-all, #btn-sku-drawer-clear').on('click', clearAllFilters);

            $(document).on('click', '[data-sku-view]', function () {
                applyView($(this).data('skuView'));
            });

            $table.on('click', '.btn-editar-sku, .btn-ver-sku', function () {
                loadSku($(this).data('id'));
            });

            $(document).on('click', '[data-action="edit-sku"]', function () {
                loadSku($(this).data('id'));
            });

            $table.on('click', '.btn-toggle-sku', function () {
                const id = $(this).data('id');
                const next = $(this).data('estatus');
                openConfirm(next === 'inactivo' ? '¿Deseas inactivar esta variante?' : '¿Deseas activar esta variante?', function () {
                    $.ajax({
                        url: rutas.estatus(id),
                        method: 'PATCH',
                        data: { psk_estatus: next },
                        dataType: 'json'
                    }).done(function (response) {
                        pendingFlashId = id;
                        reloadTable(true);
                        showFeedback('success', response.message || 'Estatus actualizado correctamente.');
                    }).fail(function (xhr) {
                        showFeedback('error', parseError(xhr));
                    });
                });
            });

            $table.on('click', '.btn-eliminar-sku', function () {
                const id = $(this).data('id');
                const name = $(this).data('name');
                openConfirm('¿Deseas eliminar la variante ' + name + '? Esta acción aplicará borrado lógico.', function () {
                    $.ajax({
                        url: rutas.destroy(id),
                        method: 'DELETE',
                        dataType: 'json'
                    }).done(function (response) {
                        reloadTable(true);
                        showFeedback('success', response.message || 'SKU eliminado correctamente.');
                    }).fail(function (xhr) {
                        showFeedback('error', parseError(xhr));
                    });
                });
            });

            $('#desktop-skus-pagination').on('click', '.desktop-pager__btn', function () {
                if (!skusTable || this.disabled) return;
                const page = $(this).data('page');
                if (page === 'previous') {
                    skusTable.page('previous').draw('page');
                    return;
                }
                if (page === 'next') {
                    skusTable.page('next').draw('page');
                    return;
                }
                skusTable.page(page).draw('page');
            });

            $('#desktop-matrix-pagination').on('click', '.desktop-pager__btn', function () {
                if (this.disabled) return;
                const page = $(this).data('page');
                const pages = Math.ceil(groupedRows.length / matrixPageLen());
                if (page === 'previous') {
                    if (matrixPage > 0) matrixPage -= 1;
                } else if (page === 'next') {
                    if (matrixPage < pages - 1) matrixPage += 1;
                } else {
                    matrixPage = page;
                }
                renderGroupedPage();
            });

            $('#desktop-sku-modal, #desktop-sku-confirm-modal').on('click', function (event) {
                if (event.target === this) closeModal($(this));
            });

            $(document).on('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeModal($skuModal);
                    closeModal($confirmModal);
                    closeDrawer();
                }
            });

            resetSkuForm();
            initTable();
            applyView('matriz');
        })();
    </script>
@endpush
