@extends('layouts.desktop')

@section('title', 'Salidas')

@push('desktop-vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
@endpush

@push('desktop-styles')
    <style>
        .desktop-sal-bar { display: flex; align-items: center; gap: 8px; padding: 6px 12px; border-bottom: 1px solid var(--stroke); background: var(--surface-alt); flex-wrap: wrap; }
        .desktop-sal-bar__search { position: relative; flex: 1 1 280px; max-width: 380px; display: flex; align-items: center; }
        .desktop-sal-bar__search svg { position: absolute; left: 9px; width: 15px; height: 15px; color: var(--text-3); pointer-events: none; }
        .desktop-sal-bar__search input { width: 100%; height: 32px; padding: 0 10px 0 30px; border: 1px solid var(--stroke-strong); border-radius: var(--r-md); background: var(--surface); color: var(--text); font: inherit; font-size: .82rem; }
        .desktop-sal-bar__search input:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 1px var(--brand); }
        .desktop-sal-bar__field { display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; }
        .desktop-sal-bar__cap { font-size: .68rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: var(--text-3); }
        .desktop-sal-bar__field .desktop-toolbar__select { height: 32px; max-width: 150px; }
        .desktop-sal-bar__spacer { flex: 1 1 auto; }
        .desktop-sal-bar__divider { width: 1px; height: 22px; background: var(--stroke); }
        .desktop-sal-bar .desktop-btn { height: 32px; }
        .desktop-sal-clear {
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
        .desktop-sal-clear:hover { text-decoration: underline; }
        .desktop-sal-clear[hidden] { display: none; }
        .desktop-sal-filterbtn {
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
        .desktop-sal-filterbtn svg { width: 15px; height: 15px; }
        .desktop-sal-filterbtn:hover { background: var(--surface-sunken); }
        .desktop-sal-filterbtn.is-active { border-color: var(--brand); color: var(--brand); }
        .desktop-sal-filterbtn__badge {
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
        .desktop-sal-filterbtn__badge.is-visible { display: inline-flex; }
        .desktop-sal-drawer { position: fixed; inset: 0; z-index: var(--z-drawer); display: none; }
        .desktop-sal-drawer.is-open { display: block; }
        .desktop-sal-drawer__scrim { position: absolute; inset: 0; background: rgba(15, 23, 42, .16); backdrop-filter: blur(2px); }
        .desktop-sal-drawer__panel {
            position: absolute; top: 0; right: 0; height: 100%; width: min(420px, 100%);
            display: flex; flex-direction: column; background: var(--surface); border-left: 1px solid var(--stroke);
            box-shadow: var(--shadow-16); animation: desktopsaldrawer .18s ease;
        }
        @keyframes desktopsaldrawer { from { transform: translateX(20px); opacity: .5; } to { transform: none; opacity: 1; } }
        .desktop-sal-drawer__head {
            display: flex; align-items: center; justify-content: space-between; gap: 8px;
            padding: 14px 16px; border-bottom: 1px solid var(--stroke);
        }
        .desktop-sal-drawer__title { font-size: .95rem; font-weight: 600; letter-spacing: -.01em; }
        .desktop-sal-drawer__close {
            display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px;
            border: 0; border-radius: var(--r-md); background: transparent; color: var(--text-2);
            font-size: 1.2rem; line-height: 1; cursor: pointer;
        }
        .desktop-sal-drawer__close:hover { background: var(--surface-sunken); color: var(--text); }
        .desktop-sal-drawer__body {
            flex: 1 1 auto; overflow: auto; padding: 14px 16px; display: grid; gap: 12px; align-content: start;
        }
        .desktop-sal-drawer__body .desktop-field input,
        .desktop-sal-drawer__body .desktop-field select { min-height: 34px; }
        .desktop-sal-drawer__foot {
            display: flex; align-items: center; justify-content: space-between; gap: 8px;
            padding: 12px 16px; border-top: 1px solid var(--stroke);
        }
        .desktop-sal-period-range[hidden] { display: none; }
        .desktop-sal-period-range { display: grid; gap: 12px; }

        .desktop-sal-table tbody td { padding-top: 4px !important; padding-bottom: 4px !important; vertical-align: middle; }
        .desktop-sal-table tbody tr.is-selected td { background: #eaf1fd; }
        .desktop-sal-meta { display: flex; flex-direction: column; gap: 1px; line-height: 1.2; min-width: 0; }
        .desktop-sal-meta__title { font-weight: 600; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .desktop-sal-meta__sub { font-size: .73rem; color: var(--text-2); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .desktop-sal-num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; font-weight: 600; }
        .desktop-sal-qty { color: var(--danger); font-weight: 800; }
        .desktop-sal-tipo { display: inline-flex; align-items: center; gap: 6px; font-size: .8rem; font-weight: 600; white-space: nowrap; }
        .desktop-sal-tipo::before { content: ""; width: 8px; height: 8px; border-radius: 50%; background: var(--tp, var(--text-3)); }
        .desktop-sal-tipo--ajuste { --tp: var(--brand); }
        .desktop-sal-tipo--merma { --tp: var(--danger); }
        .desktop-sal-state { display: inline-flex; align-items: center; height: 20px; padding: 0 8px; border-radius: 999px; font-size: .72rem; font-weight: 700; background: var(--surface-sunken); color: var(--text-2); white-space: nowrap; }
        .desktop-sal-state--activo { background: rgba(17,121,80,.12); color: var(--success); }
        .desktop-sal-state--cancelado { background: rgba(193,42,46,.12); color: var(--danger); }

        .desktop-sal-detail__grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; }
        .desktop-sal-detail__cell label { display: block; font-size: .67rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: var(--text-3); margin-bottom: 2px; }
        .desktop-sal-detail__cell div { font-size: .84rem; font-weight: 600; color: var(--text); }
        .desktop-sal-detail__obs { margin-top: 12px; }
        .desktop-sal-detail__obs label { display: block; font-size: .67rem; font-weight: 700; text-transform: uppercase; color: var(--text-3); margin-bottom: 3px; }
        .desktop-sal-detail__obs div { font-size: .84rem; background: var(--surface-alt); border: 1px solid var(--stroke); border-radius: var(--r-md); padding: 8px 10px; }

        @media (max-width: 1100px) {
            .desktop-sal-bar { flex-wrap: wrap; }
            .desktop-sal-bar__search { flex: 1 1 100%; max-width: none; order: -1; }
            .desktop-sal-bar__spacer { display: none; }
        }
        @media (max-width: 860px) {
            .desktop-sal-drawer__panel { width: 100%; }
        }
    </style>
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        @php $activeSubmenu = 'salidas'; @endphp
        @include('desktop.operacion.inventario._subnav')
        <span class="desktop-toolbar__divider"></span>
        <a href="{{ route('desktop.operacion.inventario.salidas.registrar') }}" class="desktop-btn desktop-btn--primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            Agregar salida
        </a>
        <button type="button" class="desktop-btn desktop-btn--ghost" id="btn-recargar-sal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
            Actualizar
        </button>
    </div>
@endsection

@section('content')
    <section class="desktop-pane">
        <div class="desktop-sal-bar">
            <div class="desktop-sal-bar__search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <input id="flt-buscar" type="search" placeholder="Folio, referencia, SKU o producto">
            </div>
            <div class="desktop-sal-bar__field">
                <select class="desktop-toolbar__select" id="flt-length">
                    <option value="25">25 por página</option>
                    <option value="50">50 por página</option>
                    <option value="100" selected>100 por página</option>
                    <option value="250">250 por página</option>
                </select>
            </div>
            <span class="desktop-sal-bar__spacer"></span>
            <button type="button" class="desktop-sal-clear" id="btn-limpiar" hidden>Limpiar</button>
            <button type="button" class="desktop-sal-filterbtn" id="btn-sal-filtros" aria-haspopup="dialog" aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                Filtros
                <span class="desktop-sal-filterbtn__badge" id="sal-filtros-badge"></span>
            </button>
            <span class="desktop-sal-bar__divider"></span>
            <button type="button" class="desktop-btn desktop-btn--primary" id="btn-filtrar">Aplicar</button>
        </div>

        <div class="desktop-list-wrap">
            <table class="desktop-list desktop-sal-table" id="tbl-salidas">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Folio</th>
                        <th>Código / SKU</th>
                        <th>Producto</th>
                        <th>Sucursal</th>
                        <th>Almacén</th>
                        <th>Tipo</th>
                        <th style="text-align:right;">Cantidad</th>
                        <th style="text-align:right;">Después</th>
                        <th>Usuario</th>
                        <th>Estatus</th>
                        <th style="width:48px; text-align:right;">Detalle</th>
                    </tr>
                </thead>
            </table>
        </div>

        <div class="desktop-list-foot">
            <div id="desktop-sal-info"></div>
            <div id="desktop-sal-pagination" class="desktop-pager"></div>
        </div>
    </section>

    <aside class="desktop-sal-drawer" id="desktop-sal-drawer" aria-hidden="true" role="dialog" aria-label="Filtros avanzados">
        <div class="desktop-sal-drawer__scrim" data-close-sal-drawer></div>
        <div class="desktop-sal-drawer__panel">
            <div class="desktop-sal-drawer__head">
                <div class="desktop-sal-drawer__title">Filtros avanzados</div>
                <button type="button" class="desktop-sal-drawer__close" data-close-sal-drawer aria-label="Cerrar">&times;</button>
            </div>
            <div class="desktop-sal-drawer__body">
                <div class="desktop-field">
                    <label for="flt-periodo">Periodo</label>
                    <select id="flt-periodo">
                        <option value="hoy" selected>Hoy</option>
                        <option value="ayer">Ayer</option>
                        <option value="antier">Antier</option>
                        <option value="semana_en_curso">Semana en curso</option>
                        <option value="mes_en_curso">Mes en curso</option>
                        <option value="mes_anterior">Mes anterior</option>
                        <option value="ultimos_3_meses">Últimos 3 meses</option>
                        <option value="este_ano">Este año</option>
                        <option value="rango_personalizado">Rango personalizado</option>
                    </select>
                </div>
                <div class="desktop-sal-period-range" id="flt-periodo-rango" hidden>
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
                    <label for="flt-tipo">Tipo</label>
                    <select id="flt-tipo">
                        <option value="">Todos</option>
                        <option value="ajuste_manual">Ajuste manual</option>
                        <option value="merma">Merma</option>
                    </select>
                </div>
                <div class="desktop-field">
                    <label for="flt-scl">Sucursal</label>
                    <select id="flt-scl">
                        <option value="">Todas</option>
                        @foreach($opciones['sucursales'] as $s)
                            <option value="{{ $s->scl_id }}">{{ $s->scl_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="desktop-field">
                    <label for="flt-alm">Almacén</label>
                    <select id="flt-alm">
                        <option value="">Todos</option>
                        @foreach($opciones['almacenes'] as $a)
                            <option value="{{ $a->alm_id }}" data-scl="{{ $a->alm_scl_id }}">{{ $a->alm_nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="desktop-sal-drawer__foot">
                <button type="button" class="desktop-btn desktop-btn--default" id="btn-limpiar-drawer">Limpiar</button>
                <button type="button" class="desktop-btn desktop-btn--primary" id="btn-aplicar-drawer">Aplicar filtros</button>
            </div>
        </div>
    </aside>

    <div class="desktop-modal" id="sal-modal" aria-hidden="true">
        <div class="desktop-modal__dialog" style="max-width:560px;">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="sal-modal-title">Detalle de salida</div>
                <button type="button" class="desktop-modal__close" data-close-sal aria-label="Cerrar">&times;</button>
            </div>
            <div class="desktop-modal__body" id="sal-modal-body"></div>
            <div class="desktop-modal__foot">
                <button type="button" class="desktop-btn desktop-btn--default" data-close-sal>Cerrar</button>
            </div>
        </div>
    </div>
@endsection

@push('desktop-vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endpush

@push('desktop-scripts')
    <script>
        (function () {
            const rutas = { data: @json(route('desktop.operacion.inventario.salidas.data')) };
            const tipoLabels = { ajuste_manual: 'Ajuste manual', merma: 'Merma' };
            const $table = $('#tbl-salidas');
            let tabla = null;

            function esc(t) { return $('<div>').text(t ?? '').html(); }
            function num(v) { return Number(v || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
            function fecha(v) { return v ? String(v).replace('T', ' ').slice(0, 16) : '—'; }
            function tipoLabel(t) { return tipoLabels[t] || t || '—'; }
            function renderMeta(title, sub) {
                return '<div class="desktop-sal-meta"><span class="desktop-sal-meta__title">' + esc(title || '—') + '</span>' +
                    (sub ? '<span class="desktop-sal-meta__sub">' + esc(sub) + '</span>' : '') + '</div>';
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
                        const diff = day === 0 ? -6 : 1 - day;
                        const desde = addDays(today, diff);
                        return { desde: formatDateValue(desde), hasta: formatDateValue(addDays(desde, 6)) };
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
                    case 'este_ano':
                        return { desde: formatDateValue(new Date(today.getFullYear(), 0, 1)), hasta: formatDateValue(today) };
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
            function contarFiltrosAvanzados() {
                let count = 0;
                if ($('#flt-tipo').val()) count += 1;
                if ($('#flt-scl').val()) count += 1;
                if ($('#flt-alm').val()) count += 1;
                if ($('#flt-periodo').val() !== 'hoy') count += 1;
                if ($('#flt-periodo').val() === 'rango_personalizado' && ($('#flt-desde').val() || $('#flt-hasta').val())) count += 1;
                return count;
            }

            function hayFiltroActivo() {
                return contarFiltrosAvanzados() > 0 || !!$('#flt-buscar').val().trim();
            }

            function syncBadge() {
                const count = contarFiltrosAvanzados();
                $('#sal-filtros-badge').text(count ? String(count) : '').toggleClass('is-visible', count > 0);
                $('#btn-sal-filtros').toggleClass('is-active', count > 0);
                $('#btn-limpiar').prop('hidden', !hayFiltroActivo());
            }

            function syncAlmacenes() {
                const scl = String($('#flt-scl').val() || '');
                const actual = String($('#flt-alm').val() || '');
                $('#flt-alm option').each(function () {
                    if (!this.value) return;
                    $(this).prop('hidden', scl && String($(this).data('scl')) !== scl);
                });
                if (actual && $('#flt-alm option[value="' + actual + '"]:not([hidden])').length === 0) $('#flt-alm').val('');
            }

            function renderFooter() {
                if (!tabla) return;
                const info = tabla.page.info();
                const total = info.recordsDisplay;
                if (!total) { $('#desktop-sal-info').text('Sin salidas registradas'); $('#desktop-sal-pagination').empty(); return; }
                $('#desktop-sal-info').text('Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + total + ' salidas');
                const buttons = [];
                const current = info.page, totalPages = info.pages;
                buttons.push({ label: '‹', page: 'previous', disabled: current === 0 });
                for (let i = 0; i < totalPages; i += 1) buttons.push({ label: String(i + 1), page: i, active: i === current });
                buttons.push({ label: '›', page: 'next', disabled: current >= totalPages - 1 });
                $('#desktop-sal-pagination').html(buttons.map(function (b) {
                    const cls = ['desktop-pager__btn', b.active ? 'is-active' : '', b.disabled ? 'is-disabled' : ''].filter(Boolean).join(' ');
                    return '<button type="button" class="' + cls + '" data-page="' + b.page + '"' + (b.disabled ? ' disabled' : '') + '>' + b.label + '</button>';
                }).join(''));
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
                    language: { processing: 'Cargando...', emptyTable: 'No hay salidas registradas', zeroRecords: 'No se encontraron coincidencias' },
                    ajax: {
                        url: rutas.data,
                        data: function (d) {
                            const fechas = getFechasFiltro();
                            d.min_scl_id = $('#flt-scl').val();
                            d.min_alm_id = $('#flt-alm').val();
                            d.tipo = $('#flt-tipo').val();
                            d.fecha_desde = fechas.desde;
                            d.fecha_hasta = fechas.hasta;
                            d.buscar = $('#flt-buscar').val().trim();
                        }
                    },
                    columns: [
                        { data: 'min_fecha_movimiento', render: fecha },
                        { data: 'min_folio', render: function (v) { return esc(v || '—'); } },
                        { data: 'psk_codigo', render: function (v) { return esc(v || '—'); } },
                        { data: null, orderable: false, render: function (_, __, row) { return renderMeta(row.prd_nombre, row.psk_nombre); } },
                        { data: 'scl_nombre', render: function (v) { return esc(v || '—'); } },
                        { data: 'alm_nombre', render: function (v) { return esc(v || '—'); } },
                        { data: 'min_documento_tipo', render: function (v) {
                            const cls = v === 'merma' ? 'merma' : 'ajuste';
                            return '<span class="desktop-sal-tipo desktop-sal-tipo--' + cls + '">' + esc(tipoLabel(v)) + '</span>';
                        }},
                        { data: 'min_cantidad', className: 'desktop-sal-num', render: function (v) { return '<span class="desktop-sal-qty">-' + num(v) + '</span>'; } },
                        { data: 'min_existencia_despues', className: 'desktop-sal-num', render: num },
                        { data: 'usuario_nombre', render: function (v) { return esc(v || '—'); } },
                        { data: 'min_estatus', render: function (v) {
                            const e = String(v || '').toLowerCase();
                            return '<span class="desktop-sal-state desktop-sal-state--' + esc(e) + '">' + esc(e ? e.charAt(0).toUpperCase() + e.slice(1) : '—') + '</span>';
                        }},
                        { data: null, orderable: false, className: 'dt-actions', render: function () {
                            return '<button type="button" class="desktop-btn desktop-btn--ghost" style="height:26px;padding:0 8px;" data-ver title="Ver detalle">' +
                                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg></button>';
                        }},
                    ],
                    initComplete: renderFooter,
                    drawCallback: renderFooter
                });
            }

            function recargar(reset) { if (!tabla) { buildTabla(); return; } tabla.ajax.reload(null, !!reset); }

            function limpiarFiltros() {
                $('#flt-scl').val('');
                syncAlmacenes();
                $('#flt-alm').val('');
                $('#flt-tipo').val('');
                $('#flt-periodo').val('hoy');
                $('#flt-desde').val('');
                $('#flt-hasta').val('');
                syncPeriodoUI();
                $('#flt-buscar').val('');
                syncBadge();
                recargar(true);
            }

            function abrirDrawer() {
                $('#desktop-sal-drawer').addClass('is-open').attr('aria-hidden', 'false');
                $('#btn-sal-filtros').attr('aria-expanded', 'true');
            }

            function cerrarDrawer() {
                $('#desktop-sal-drawer').removeClass('is-open').attr('aria-hidden', 'true');
                $('#btn-sal-filtros').attr('aria-expanded', 'false');
            }

            function verDetalle(row) {
                $('#sal-modal-title').text('Salida ' + (row.min_folio || ''));
                const cell = (lbl, val) => '<div class="desktop-sal-detail__cell"><label>' + lbl + '</label><div>' + val + '</div></div>';
                const cab = '<div class="desktop-sal-detail__grid">' +
                    cell('Fecha', esc(fecha(row.min_fecha_movimiento))) +
                    cell('Tipo', esc(tipoLabel(row.min_documento_tipo))) +
                    cell('SKU', esc(row.psk_codigo || '—')) +
                    cell('Producto', esc(row.prd_nombre || '—') + (row.psk_nombre ? ' · ' + esc(row.psk_nombre) : '')) +
                    cell('Sucursal', esc(row.scl_nombre || '—')) +
                    cell('Almacén', esc(row.alm_nombre || '—')) +
                    cell('Cantidad', '<span class="desktop-sal-qty">-' + num(row.min_cantidad) + '</span>') +
                    cell('Antes → Después', num(row.min_existencia_antes) + ' → ' + num(row.min_existencia_despues)) +
                    cell('Referencia', esc(row.min_documento_referencia || '—')) +
                    cell('Usuario', esc(row.usuario_nombre || '—')) +
                '</div>' +
                (row.min_motivo_texto ? '<div class="desktop-sal-detail__obs"><label>Motivo</label><div>' + esc(row.min_motivo_texto) + '</div></div>' : '');
                $('#sal-modal-body').html(cab);
                $('#sal-modal').addClass('is-open').attr('aria-hidden', 'false');
            }
            function cerrarModal() { $('#sal-modal').removeClass('is-open').attr('aria-hidden', 'true'); }

            $('#flt-scl').on('change', function () {
                syncAlmacenes();
                syncBadge();
            });
            $('#flt-periodo').on('change', function () {
                syncPeriodoUI();
                syncBadge();
            });
            $('#flt-tipo, #flt-alm, #flt-desde, #flt-hasta').on('change', syncBadge);
            $('#flt-buscar').on('input', syncBadge);
            $('#btn-filtrar, #btn-recargar-sal').on('click', function () { recargar(true); });
            $('#flt-buscar').on('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); recargar(true); } });
            $('#flt-length').on('change', function () { if (tabla) tabla.page.len(Number(this.value || 100)).draw(false); });
            $('#btn-sal-filtros').on('click', abrirDrawer);
            $(document).on('click', '[data-close-sal-drawer]', cerrarDrawer);
            $('#btn-limpiar, #btn-limpiar-drawer').on('click', function () {
                limpiarFiltros();
            });
            $('#btn-aplicar-drawer').on('click', function () {
                syncBadge();
                cerrarDrawer();
                recargar(true);
            });
            $('#desktop-sal-pagination').on('click', '.desktop-pager__btn', function () {
                if (!tabla || this.disabled) return;
                const page = $(this).data('page');
                tabla.page(page === 'previous' || page === 'next' ? page : Number(page)).draw('page');
            });

            $table.on('click', '[data-ver]', function () {
                const row = tabla.row($(this).closest('tr')).data();
                if (row) verDetalle(row);
            });
            $(document).on('click', '[data-close-sal]', cerrarModal);
            $('#sal-modal').on('click', function (e) { if (e.target === this) cerrarModal(); });
            $(document).on('keydown', function (e) { if (e.key === 'Escape') cerrarModal(); });

            syncAlmacenes();
            syncPeriodoUI();
            syncBadge();
            buildTabla();
        })();
    </script>
@endpush
