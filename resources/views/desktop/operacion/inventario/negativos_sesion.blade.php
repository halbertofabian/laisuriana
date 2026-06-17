@extends('layouts.desktop')

@section('title', 'Negativos por sesión')

@push('desktop-vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/select2/select2.css') }}" />
@endpush

@push('desktop-styles')
    <style>
        .desktop-neg-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-bottom: 1px solid var(--stroke);
            background: var(--surface-alt);
        }
        .desktop-neg-bar__search {
            position: relative;
            flex: 1 1 360px;
            max-width: 440px;
            display: flex;
            align-items: center;
        }
        .desktop-neg-bar__search svg {
            position: absolute;
            left: 9px;
            width: 15px;
            height: 15px;
            color: var(--text-3);
            pointer-events: none;
        }
        .desktop-neg-bar__search input {
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
        .desktop-neg-bar__search input:focus {
            outline: none;
            border-color: var(--brand);
            box-shadow: 0 0 0 1px var(--brand);
        }
        .desktop-neg-bar__field {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }
        .desktop-neg-bar__cap {
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .03em;
            text-transform: uppercase;
            color: var(--text-3);
        }
        .desktop-neg-bar__field .desktop-toolbar__select {
            height: 32px;
            max-width: 180px;
        }
        .desktop-neg-bar__spacer { flex: 1 1 auto; }
        .desktop-neg-bar__divider { width: 1px; height: 22px; background: var(--stroke); }
        .desktop-neg-clear {
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
        .desktop-neg-clear:hover { text-decoration: underline; }
        .desktop-neg-filterbtn {
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
        .desktop-neg-filterbtn svg { width: 15px; height: 15px; }
        .desktop-neg-filterbtn:hover { background: var(--surface-sunken); }
        .desktop-neg-filterbtn.is-active { border-color: var(--brand); color: var(--brand); }
        .desktop-neg-filterbtn__badge {
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
        .desktop-neg-filterbtn__badge.is-visible { display: inline-flex; }
        .desktop-neg-export { position: relative; display: inline-flex; }
        .desktop-neg-export .desktop-btn svg { width: 13px; height: 13px; }

        .desktop-neg-table tbody td {
            padding-top: 6px !important;
            padding-bottom: 6px !important;
            vertical-align: middle;
        }
        .desktop-neg-table tbody tr.is-selected td { background: #eaf1fd; }
        .desktop-neg-meta {
            display: flex;
            flex-direction: column;
            gap: 1px;
            line-height: 1.22;
            min-width: 0;
        }
        .desktop-neg-meta__title {
            font-weight: 600;
            color: var(--text);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .desktop-neg-meta__sub {
            font-size: .74rem;
            color: var(--text-2);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .desktop-neg-badge {
            display: inline-flex;
            align-items: center;
            height: 24px;
            padding: 0 8px;
            border-radius: 999px;
            background: #eef4ff;
            color: var(--brand);
            font-size: .75rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .desktop-neg-num {
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
            font-weight: 600;
        }
        .desktop-neg-num--danger {
            color: #c62828;
            font-weight: 800;
        }
        .desktop-neg-drawer { position: fixed; inset: 0; z-index: var(--z-drawer); display: none; }
        .desktop-neg-drawer.is-open { display: block; }
        .desktop-neg-drawer__scrim {
            position: absolute; inset: 0;
            background: rgba(16, 24, 40, .28);
        }
        .desktop-neg-drawer__panel {
            position: absolute;
            top: 0; right: 0;
            width: min(380px, 100%);
            height: 100%;
            display: flex;
            flex-direction: column;
            background: var(--surface);
            border-left: 1px solid var(--stroke);
            box-shadow: var(--shadow-16);
        }
        .desktop-neg-drawer__head,
        .desktop-neg-drawer__foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 12px 16px;
        }
        .desktop-neg-drawer__head { border-bottom: 1px solid var(--stroke); }
        .desktop-neg-drawer__foot { border-top: 1px solid var(--stroke); }
        .desktop-neg-drawer__title { font-size: .95rem; font-weight: 600; letter-spacing: -.01em; }
        .desktop-neg-drawer__close {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border: 0;
            border-radius: var(--r-md);
            background: transparent;
            color: var(--text-2);
            font-size: 1.2rem;
            line-height: 1;
            cursor: pointer;
        }
        .desktop-neg-drawer__close:hover { background: var(--surface-sunken); color: var(--text); }
        .desktop-neg-drawer__body {
            flex: 1 1 auto;
            overflow: auto;
            padding: 14px 16px;
            display: grid;
            gap: 12px;
            align-content: start;
        }
        .desktop-neg-drawer__body .desktop-field input,
        .desktop-neg-drawer__body .desktop-field select {
            min-height: 34px;
        }
        .desktop-neg-period-range[hidden] { display: none; }
        .desktop-neg-period-range {
            display: grid;
            gap: 12px;
        }
        .desktop-neg-tablearea {
            position: relative;
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }
        .desktop-neg-table-wrap {
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
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
            background: var(--surface);
            border: 1px solid var(--stroke);
            border-radius: var(--r-md);
            overflow: hidden;
            box-shadow: var(--shadow-16);
        }
        .select2-container--open { z-index: calc(var(--z-drawer) + 20); }
        .desktop-neg-drawer__panel .select2-container { width: 100% !important; }
        .desktop-neg-drawer__panel .select2-container--open .select2-dropdown { z-index: calc(var(--z-drawer) + 20); }
        .select2-results__options {
            max-height: 240px;
            overflow-y: auto;
            background: var(--surface);
        }
        .select2-container--default .select2-results__option--selected { background: var(--surface-sunken); }
        .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
            background: var(--brand-soft);
            color: var(--text);
        }
        @media (max-width: 1180px) {
            .desktop-neg-bar { flex-wrap: wrap; }
            .desktop-neg-bar__search { flex: 1 1 100%; max-width: none; order: -1; }
            .desktop-neg-bar__spacer { display: none; }
        }
        @media (max-width: 860px) {
            .desktop-neg-drawer__panel { width: 100%; }
        }
    </style>
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        @php($activeSubmenu = 'negativos_sesion')
        @include('desktop.operacion.inventario._subnav')
        <span class="desktop-toolbar__divider"></span>
        <button type="button" class="desktop-btn desktop-btn--ghost" id="btn-recargar-negativos">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
            Actualizar
        </button>
    </div>
@endsection

@section('content')
    <section class="desktop-pane">
        <div class="desktop-neg-bar">
            <div class="desktop-neg-bar__search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <input id="flt-buscar" type="search" placeholder="Buscar folio, SKU, producto, caja o usuario">
            </div>

            <div class="desktop-neg-bar__field">
                <select class="desktop-toolbar__select" id="flt-length">
                    <option value="25">25 por página</option>
                    <option value="50">50 por página</option>
                    <option value="100" selected>100 por página</option>
                    <option value="250">250 por página</option>
                </select>
            </div>

            <span class="desktop-neg-bar__spacer"></span>

            <button type="button" class="desktop-neg-clear" id="btn-limpiar" hidden>Limpiar</button>
            <button type="button" class="desktop-neg-filterbtn" id="btn-neg-filtros" aria-haspopup="dialog" aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                Filtros
                <span class="desktop-neg-filterbtn__badge" id="neg-filtros-badge"></span>
            </button>

            <span class="desktop-neg-bar__divider"></span>

            <button type="button" class="desktop-btn desktop-btn--primary" id="btn-filtrar">Aplicar</button>
            <div class="desktop-neg-export">
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

        <div class="desktop-neg-tablearea">
            <div class="desktop-neg-table-wrap desktop-list-wrap">
                <table class="desktop-list desktop-neg-table" id="tbl-negativos-sesion">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Sesión</th>
                            <th>Caja</th>
                            <th>Sucursal</th>
                            <th>Almacén</th>
                            <th>Venta</th>
                            <th>SKU</th>
                            <th>Producto</th>
                            <th style="text-align:right;">Cantidad</th>
                            <th style="text-align:right;">Antes</th>
                            <th style="text-align:right;">Después</th>
                            <th>Usuario venta</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

        <div class="desktop-list-foot">
            <div id="desktop-negativos-info"></div>
            <div id="desktop-negativos-pagination" class="desktop-pager"></div>
        </div>
    </section>

    <aside class="desktop-neg-drawer" id="desktop-neg-drawer" aria-hidden="true" role="dialog" aria-label="Filtros avanzados">
        <div class="desktop-neg-drawer__scrim" data-close-neg-drawer></div>
        <div class="desktop-neg-drawer__panel">
            <div class="desktop-neg-drawer__head">
                <div class="desktop-neg-drawer__title">Filtros avanzados</div>
                <button type="button" class="desktop-neg-drawer__close" data-close-neg-drawer aria-label="Cerrar">&times;</button>
            </div>
            <div class="desktop-neg-drawer__body">
                <div class="desktop-field">
                    <label for="flt-periodo">Periodo</label>
                    <select id="flt-periodo">
                        <option value="hoy">Hoy</option>
                        <option value="ayer">Ayer</option>
                        <option value="antier">Antier</option>
                        <option value="semana_en_curso" selected>Semana en curso</option>
                        <option value="mes_en_curso">Mes en curso</option>
                        <option value="mes_anterior">Mes anterior</option>
                        <option value="ultimos_3_meses">Últimos 3 meses</option>
                        <option value="este_ano">Este año</option>
                        <option value="rango_personalizado">Rango personalizado</option>
                    </select>
                </div>
                <div class="desktop-neg-period-range" id="flt-periodo-rango" hidden>
                    <div class="desktop-field">
                        <label for="flt-desde">Desde</label>
                        <input type="date" id="flt-desde">
                    </div>
                    <div class="desktop-field">
                        <label for="flt-hasta">Hasta</label>
                        <input type="date" id="flt-hasta">
                    </div>
                </div>
                <div class="desktop-field">
                    <label for="flt-cse">Sesión de caja</label>
                    <select id="flt-cse">
                        <option value="">Todas</option>
                        @foreach($sesionesCaja as $sesion)
                            <option value="{{ $sesion->cse_id }}">
                                #{{ $sesion->cse_id }} | {{ $sesion->caj_nombre }} | {{ $sesion->scl_nombre }} | {{ $sesion->cse_abierta_at ? \Illuminate\Support\Carbon::parse($sesion->cse_abierta_at)->format('Y-m-d H:i') : '—' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="desktop-field">
                    <label for="flt-scl">Sucursal</label>
                    <select id="flt-scl">
                        <option value="">Todas</option>
                        @foreach($opciones['sucursales'] as $sucursal)
                            <option value="{{ $sucursal->scl_id }}" @selected((int) $sucursal->scl_id === (int) ($defaultSucursalId ?? 0))>{{ $sucursal->scl_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="desktop-field">
                    <label for="flt-alm">Almacén</label>
                    <select id="flt-alm">
                        <option value="">Todos</option>
                        @foreach($opciones['almacenes'] as $almacen)
                            <option value="{{ $almacen->alm_id }}" data-scl="{{ $almacen->alm_scl_id }}">{{ $almacen->alm_nombre }}</option>
                        @endforeach
                    </select>
                </div>
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
                    <label for="flt-dsc">Descripción</label>
                    <select id="flt-dsc">
                        <option value="">Todas</option>
                        @foreach($opciones['descripciones'] as $descripcion)
                            <option value="{{ $descripcion->dsc_id }}">{{ $descripcion->dsc_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="desktop-field">
                    <label for="flt-prd">Producto</label>
                    <select id="flt-prd"></select>
                </div>
            </div>
            <div class="desktop-neg-drawer__foot">
                <button type="button" class="desktop-btn desktop-btn--default" id="btn-limpiar-drawer">Limpiar</button>
                <button type="button" class="desktop-btn desktop-btn--primary" id="btn-aplicar-drawer">Aplicar filtros</button>
            </div>
        </div>
    </aside>
@endsection

@push('desktop-vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('vendor-template/assets/vendor/libs/select2/select2.js') }}"></script>
@endpush

@push('desktop-scripts')
    <script>
        (function () {
            const rutas = {
                data: @json(route('desktop.operacion.inventario.negativos_sesion.data')),
                productos: @json(route('desktop.operacion.inventario.productos.buscar')),
                exportarExcel: @json(route('desktop.operacion.inventario.negativos_sesion.exportar.excel')),
                exportarPdf: @json(route('desktop.operacion.inventario.negativos_sesion.exportar.pdf')),
            };

            const defaultScl = @json((string) ($defaultSucursalId ?? ''));
            const $table = $('#tbl-negativos-sesion');
            let tabla = null;
            let selectedRowIdx = null;
            let pendingSelect = null;

            function escapeHtml(text) {
                return $('<div>').text(text ?? '').html();
            }

            function renderMeta(title, subtitle = '') {
                return '<div class="desktop-neg-meta">' +
                    '<span class="desktop-neg-meta__title">' + escapeHtml(title || '—') + '</span>' +
                    (subtitle ? '<span class="desktop-neg-meta__sub">' + escapeHtml(subtitle) + '</span>' : '') +
                '</div>';
            }

            function formatDateValue(date) {
                const y = date.getFullYear();
                const m = String(date.getMonth() + 1).padStart(2, '0');
                const d = String(date.getDate()).padStart(2, '0');
                return y + '-' + m + '-' + d;
            }

            function startOfDay(date) {
                const d = new Date(date);
                d.setHours(0, 0, 0, 0);
                return d;
            }

            function addDays(date, days) {
                const d = new Date(date);
                d.setDate(d.getDate() + days);
                return d;
            }

            function addMonths(date, months) {
                const d = new Date(date);
                d.setMonth(d.getMonth() + months);
                return d;
            }

            function resolverPeriodo(periodo) {
                const today = startOfDay(new Date());
                switch (periodo) {
                    case 'hoy':
                        return { desde: formatDateValue(today), hasta: formatDateValue(today) };
                    case 'ayer': {
                        const d = addDays(today, -1);
                        return { desde: formatDateValue(d), hasta: formatDateValue(d) };
                    }
                    case 'antier': {
                        const d = addDays(today, -2);
                        return { desde: formatDateValue(d), hasta: formatDateValue(d) };
                    }
                    case 'semana_en_curso': {
                        const day = today.getDay();
                        const diffToMonday = day === 0 ? -6 : 1 - day;
                        const desde = addDays(today, diffToMonday);
                        const hasta = addDays(desde, 6);
                        return { desde: formatDateValue(desde), hasta: formatDateValue(hasta) };
                    }
                    case 'mes_en_curso': {
                        const desde = new Date(today.getFullYear(), today.getMonth(), 1);
                        const hasta = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                        return { desde: formatDateValue(desde), hasta: formatDateValue(hasta) };
                    }
                    case 'mes_anterior': {
                        const desde = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                        const hasta = new Date(today.getFullYear(), today.getMonth(), 0);
                        return { desde: formatDateValue(desde), hasta: formatDateValue(hasta) };
                    }
                    case 'ultimos_3_meses':
                        return { desde: formatDateValue(addMonths(today, -3)), hasta: formatDateValue(today) };
                    case 'este_ano': {
                        const desde = new Date(today.getFullYear(), 0, 1);
                        return { desde: formatDateValue(desde), hasta: formatDateValue(today) };
                    }
                    default:
                        return { desde: $('#flt-desde').val(), hasta: $('#flt-hasta').val() };
                }
            }

            function syncPeriodoUI() {
                $('#flt-periodo-rango').prop('hidden', $('#flt-periodo').val() !== 'rango_personalizado');
            }

            function getFechasFiltro() {
                if ($('#flt-periodo').val() === 'rango_personalizado') {
                    return { desde: $('#flt-desde').val(), hasta: $('#flt-hasta').val() };
                }
                return resolverPeriodo($('#flt-periodo').val());
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

            function syncAlmacenes() {
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
                    dropdownParent: $('#desktop-neg-drawer .desktop-neg-drawer__panel'),
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
                                prd_dsc_id: $('#flt-dsc').val(),
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
                    $('#desktop-negativos-info').text('Mostrando 0 registros');
                    $('#desktop-negativos-pagination').empty();
                    return;
                }

                $('#desktop-negativos-info').text('Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + total + ' registros');

                const buttons = [];
                const current = info.page;
                const totalPages = info.pages;
                buttons.push({ label: '‹', page: 'previous', disabled: current === 0 });
                for (let i = 0; i < totalPages; i += 1) {
                    buttons.push({ label: String(i + 1), page: i, active: i === current });
                }
                buttons.push({ label: '›', page: 'next', disabled: current >= totalPages - 1 });

                $('#desktop-negativos-pagination').html(buttons.map(function (button) {
                    const classes = ['desktop-pager__btn', button.active ? 'is-active' : '', button.disabled ? 'is-disabled' : ''].filter(Boolean).join(' ');
                    return '<button type="button" class="' + classes + '" data-page="' + button.page + '"' + (button.disabled ? ' disabled' : '') + '>' + button.label + '</button>';
                }).join(''));
            }

            function getDataRows() {
                return $table.find('tbody tr').not('.dataTables_empty');
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

            function contarFiltrosAvanzados() {
                let count = 0;
                if (!!$('#flt-alm').val()) count += 1;
                if (String($('#flt-scl').val() || '') !== String(defaultScl)) count += 1;
                ['#flt-mrc', '#flt-mdl', '#flt-lna', '#flt-ctg', '#flt-dsc', '#flt-prd', '#flt-cse'].forEach(function (selector) {
                    if ($(selector).val()) count += 1;
                });
                if ($('#flt-periodo').val() !== 'semana_en_curso') count += 1;
                if ($('#flt-periodo').val() === 'rango_personalizado' && ($('#flt-desde').val() || $('#flt-hasta').val())) count += 1;
                return count;
            }

            function hayFiltroActivo() {
                return contarFiltrosAvanzados() > 0 || !!$('#flt-buscar').val().trim();
            }

            function syncBadge() {
                const count = contarFiltrosAvanzados();
                $('#neg-filtros-badge').text(count ? String(count) : '').toggleClass('is-visible', count > 0);
                $('#btn-neg-filtros').toggleClass('is-active', count > 0);
                $('#btn-limpiar').prop('hidden', !hayFiltroActivo());
            }

            function buildTabla() {
                tabla = $table.DataTable({
                    processing: true,
                    serverSide: true,
                    pageLength: Number($('#flt-length').val() || 100),
                    lengthChange: false,
                    searching: false,
                    ordering: true,
                    info: false,
                    autoWidth: false,
                    responsive: false,
                    order: [[0, 'desc']],
                    dom: 'rt',
                    language: {
                        processing: 'Cargando...',
                        emptyTable: 'No se encontraron movimientos con stock negativo',
                        zeroRecords: 'No se encontraron coincidencias'
                    },
                    ajax: {
                        url: rutas.data,
                        data: function (d) {
                            const fechas = getFechasFiltro();
                            d.cse_id = $('#flt-cse').val();
                            d.min_scl_id = $('#flt-scl').val();
                            d.min_alm_id = $('#flt-alm').val();
                            d.prd_mrc_id = $('#flt-mrc').val();
                            d.prd_mdl_id = $('#flt-mdl').val();
                            d.prd_lna_id = $('#flt-lna').val();
                            d.prd_ctg_id = $('#flt-ctg').val();
                            d.prd_dsc_id = $('#flt-dsc').val();
                            d.prd_id = $('#flt-prd').val();
                            d.fecha_desde = fechas.desde;
                            d.fecha_hasta = fechas.hasta;
                            d.buscar = $('#flt-buscar').val().trim();
                        },
                        error: function (xhr) {
                            const msg = xhr.responseJSON?.message || 'No fue posible cargar el reporte.';
                            if (window.DesktopUI) DesktopUI.message('Error', msg, 'error');
                            else if (window.AppUI?.showMessage) window.AppUI.showMessage('Error', msg, 'error');
                        }
                    },
                    columns: [
                        { data: 'min_fecha_movimiento', render: function (v) { return renderMeta(v ? String(v).replace('T', ' ').slice(0, 16) : '—', v ? 'Movimiento inventario' : ''); }},
                        { data: 'cse_id', render: function (v, _, row) { return renderMeta('#' + (v || '—'), row.cse_estatus || '—'); }},
                        { data: 'caj_nombre', render: function (v) { return renderMeta(v || '—'); }},
                        { data: 'scl_nombre', render: function (v) { return renderMeta(v || '—'); }},
                        { data: 'alm_nombre', render: function (v) { return renderMeta(v || '—'); }},
                        { data: 'psv_folio', render: function (v) { return '<span class="desktop-neg-badge">' + escapeHtml(v || '—') + '</span>'; }},
                        { data: 'psk_codigo', render: function (v, _, row) { return renderMeta(v || '—', row.min_folio || ''); }},
                        { data: 'psk_nombre', render: function (v, _, row) { return renderMeta(v || row.prd_nombre || '—', row.prd_nombre || ''); }},
                        { data: 'min_cantidad', className: 'desktop-neg-num', render: function (v) { return '-' + Number(v || 0).toFixed(2); }},
                        { data: 'min_existencia_antes', className: 'desktop-neg-num', render: function (v) { return Number(v || 0).toFixed(2); }},
                        { data: 'min_existencia_despues', className: 'desktop-neg-num desktop-neg-num--danger', render: function (v) { return Number(v || 0).toFixed(2); }},
                        { data: 'usuario_venta', render: function (v, _, row) { return renderMeta(v || '—', row.usuario_apertura ? 'Apertura: ' + row.usuario_apertura : ''); }},
                    ],
                    initComplete: renderFooter,
                    drawCallback: function () {
                        renderFooter();
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

            function recargar(resetPage = false) {
                if (!tabla) {
                    buildTabla();
                    return;
                }
                tabla.ajax.reload(null, !!resetPage);
            }

            function limpiarFiltros() {
                $('#flt-scl').val(defaultScl);
                $('#flt-alm').val('');
                $('#flt-mrc').val('');
                $('#flt-mdl').val('');
                $('#flt-lna').val('');
                $('#flt-ctg').val('');
                $('#flt-dsc').val('');
                $('#flt-prd').val(null).trigger('change');
                $('#flt-cse').val('');
                $('#flt-periodo').val('semana_en_curso');
                $('#flt-desde').val('');
                $('#flt-hasta').val('');
                syncPeriodoUI();
                $('#flt-buscar').val('');
                aplicarFiltroConceptosPorLinea();
                syncAlmacenes();
                syncBadge();
                recargar(true);
            }

            function abrirDrawer() {
                $('#desktop-neg-drawer').addClass('is-open').attr('aria-hidden', 'false');
                $('#btn-neg-filtros').attr('aria-expanded', 'true');
            }

            function cerrarDrawer() {
                $('#desktop-neg-drawer').removeClass('is-open').attr('aria-hidden', 'true');
                $('#btn-neg-filtros').attr('aria-expanded', 'false');
            }

            function aplicarFiltrosDesdeQuery() {
                const params = new URLSearchParams(window.location.search);
                function setIfPresent(id, key) {
                    const value = params.get(key);
                    if (value) $(id).val(value);
                }

                setIfPresent('#flt-scl', 'min_scl_id');
                setIfPresent('#flt-alm', 'min_alm_id');
                setIfPresent('#flt-mrc', 'prd_mrc_id');
                setIfPresent('#flt-mdl', 'prd_mdl_id');
                setIfPresent('#flt-lna', 'prd_lna_id');
                setIfPresent('#flt-ctg', 'prd_ctg_id');
                setIfPresent('#flt-dsc', 'prd_dsc_id');
                setIfPresent('#flt-cse', 'cse_id');
                setIfPresent('#flt-periodo', 'periodo');
                setIfPresent('#flt-desde', 'fecha_desde');
                setIfPresent('#flt-hasta', 'fecha_hasta');
                setIfPresent('#flt-length', 'length');
                syncPeriodoUI();

                const buscar = params.get('buscar');
                if (buscar) $('#flt-buscar').val(buscar);

                const prdId = params.get('prd_id');
                const prdText = params.get('prd_text');
                if (prdId) {
                    const option = new Option(prdText || ('Producto #' + prdId), prdId, true, true);
                    $('#flt-prd').append(option).trigger('change');
                }
            }

            function buildExportUrl(base) {
                const params = new URLSearchParams();
                const fechas = getFechasFiltro();
                const map = {
                    cse_id: $('#flt-cse').val(),
                    min_scl_id: $('#flt-scl').val(),
                    min_alm_id: $('#flt-alm').val(),
                    prd_mrc_id: $('#flt-mrc').val(),
                    prd_mdl_id: $('#flt-mdl').val(),
                    prd_lna_id: $('#flt-lna').val(),
                    prd_ctg_id: $('#flt-ctg').val(),
                    prd_dsc_id: $('#flt-dsc').val(),
                    prd_id: $('#flt-prd').val(),
                    prd_text: $('#flt-prd').find('option:selected').text().trim(),
                    periodo: $('#flt-periodo').val(),
                    fecha_desde: fechas.desde,
                    fecha_hasta: fechas.hasta,
                    buscar: $('#flt-buscar').val().trim(),
                    length: $('#flt-length').val(),
                };

                Object.keys(map).forEach(function (key) {
                    if (map[key]) params.set(key, map[key]);
                });

                const qs = params.toString();
                return qs ? base + '?' + qs : base;
            }

            initSelect2();
            aplicarFiltroConceptosPorLinea();
            syncAlmacenes();
            aplicarFiltrosDesdeQuery();
            aplicarFiltroConceptosPorLinea();
            syncAlmacenes();
            if (!$('#flt-periodo').val()) $('#flt-periodo').val('semana_en_curso');
            syncPeriodoUI();
            syncBadge();
            recargar(true);

            $('#btn-recargar-negativos, #btn-filtrar').on('click', function () {
                syncBadge();
                recargar(true);
            });

            $('#flt-scl').on('change', function () {
                syncAlmacenes();
                $('#flt-prd').val(null).trigger('change');
                syncBadge();
            });

            $('#flt-lna').on('change', function () {
                aplicarFiltroConceptosPorLinea();
                $('#flt-prd').val(null).trigger('change');
                syncBadge();
            });

            $('#flt-periodo').on('change', function () {
                syncPeriodoUI();
                syncBadge();
            });

            $('#flt-alm, #flt-mrc, #flt-mdl, #flt-ctg, #flt-dsc, #flt-cse, #flt-desde, #flt-hasta, #flt-prd').on('change', function () {
                if (this.id !== 'flt-prd' && this.id !== 'flt-cse' && this.id !== 'flt-desde' && this.id !== 'flt-hasta') {
                    $('#flt-prd').val(null).trigger('change');
                }
                syncBadge();
            });

            $('#flt-length').on('change', function () {
                if (tabla) {
                    tabla.page.len(Number(this.value || 100)).draw(false);
                }
            });

            $('#btn-limpiar, #btn-limpiar-drawer').on('click', function () {
                limpiarFiltros();
            });

            $('#btn-aplicar-drawer').on('click', function () {
                syncBadge();
                cerrarDrawer();
                recargar(true);
            });

            $('#btn-neg-filtros').on('click', abrirDrawer);
            $(document).on('click', '[data-close-neg-drawer]', cerrarDrawer);

            $('#btn-exportar-excel').on('click', function () {
                window.location.href = buildExportUrl(rutas.exportarExcel);
            });
            $('#btn-exportar-pdf').on('click', function () {
                window.open(buildExportUrl(rutas.exportarPdf), '_blank');
            });

            $('#flt-buscar').on('input', syncBadge);
            $('#flt-buscar').on('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    syncBadge();
                    recargar(true);
                }
            });

            $('#desktop-negativos-pagination').on('click', '.desktop-pager__btn', function () {
                if (!tabla || this.disabled) return;
                const page = $(this).data('page');
                if (page === 'previous' || page === 'next') {
                    tabla.page(page).draw('page');
                } else {
                    tabla.page(Number(page)).draw('page');
                }
            });

            $table.on('click', 'tbody tr', function () {
                const idx = getDataRows().index(this);
                if (idx >= 0) selectRow(idx);
            });

            $(document).on('keydown.neg-nav', function (e) {
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
        })();
    </script>
@endpush
