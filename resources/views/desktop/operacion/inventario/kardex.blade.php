@extends('layouts.desktop')

@section('title', 'Kardex')

@push('desktop-vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
@endpush

@push('desktop-styles')
    <style>
        .desktop-kar-bar { display: flex; align-items: center; gap: 8px; padding: 6px 12px; border-bottom: 1px solid var(--stroke); background: var(--surface-alt); flex-wrap: wrap; }
        .desktop-kar-bar__search { position: relative; flex: 1 1 300px; max-width: 400px; display: flex; align-items: center; }
        .desktop-kar-bar__search svg { position: absolute; left: 9px; width: 15px; height: 15px; color: var(--text-3); pointer-events: none; }
        .desktop-kar-bar__search input { width: 100%; height: 32px; padding: 0 10px 0 30px; border: 1px solid var(--stroke-strong); border-radius: var(--r-md); background: var(--surface); color: var(--text); font: inherit; font-size: .82rem; }
        .desktop-kar-bar__search input:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 1px var(--brand); }
        .desktop-kar-bar__field { display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; }
        .desktop-kar-bar__cap { font-size: .68rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: var(--text-3); }
        .desktop-kar-bar__field .desktop-toolbar__select, .desktop-kar-bar__field input { height: 32px; max-width: 160px; }
        .desktop-kar-bar__spacer { flex: 1 1 auto; }
        .desktop-kar-bar__divider { width: 1px; height: 22px; background: var(--stroke); }

        .desktop-kar-table tbody td { padding-top: 4px !important; padding-bottom: 4px !important; vertical-align: middle; }
        .desktop-kar-table tbody tr.is-selected td { background: #eaf1fd; }
        .desktop-kar-meta { display: flex; flex-direction: column; gap: 1px; line-height: 1.2; min-width: 0; }
        .desktop-kar-meta__title { font-weight: 600; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .desktop-kar-meta__sub { font-size: .73rem; color: var(--text-2); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .desktop-kar-num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; font-weight: 600; }
        .desktop-kar-qty--in { color: var(--success); font-weight: 700; }
        .desktop-kar-qty--out { color: var(--danger); font-weight: 700; }

        .desktop-kar-mov { display: inline-flex; align-items: center; gap: 6px; font-size: .8rem; font-weight: 600; white-space: nowrap; }
        .desktop-kar-mov::before { content: ""; width: 8px; height: 8px; border-radius: 50%; background: var(--mv, var(--text-3)); flex: 0 0 auto; }
        .desktop-kar-mov--entrada { --mv: var(--success); }
        .desktop-kar-mov--salida { --mv: var(--danger); }
        .desktop-kar-mov--ajuste { --mv: var(--brand); }
        .desktop-kar-mov--traspaso { --mv: #7c3aed; }

        .desktop-kar-state { display: inline-flex; align-items: center; height: 20px; padding: 0 8px; border-radius: 999px; font-size: .72rem; font-weight: 700; background: var(--surface-sunken); color: var(--text-2); white-space: nowrap; }
        .desktop-kar-state--activo { background: rgba(17,121,80,.12); color: var(--success); }
        .desktop-kar-state--cancelado { background: rgba(193,42,46,.12); color: var(--danger); }

        @media (max-width: 1100px) {
            .desktop-kar-bar { flex-wrap: wrap; }
            .desktop-kar-bar__search { flex: 1 1 100%; max-width: none; order: -1; }
            .desktop-kar-bar__spacer { display: none; }
        }
    </style>
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        @php $activeSubmenu = 'kardex'; @endphp
        @include('desktop.operacion.inventario._subnav')
        <span class="desktop-toolbar__divider"></span>
        <button type="button" class="desktop-btn desktop-btn--ghost" id="btn-recargar-kardex">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
            Actualizar
        </button>
    </div>
@endsection

@section('content')
    <section class="desktop-pane">
        <div class="desktop-kar-bar">
            <div class="desktop-kar-bar__search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <input id="flt-buscar" type="search" placeholder="Folio, referencia, SKU o producto">
            </div>
            <div class="desktop-kar-bar__field">
                <span class="desktop-kar-bar__cap">Sucursal</span>
                <select class="desktop-toolbar__select" id="flt-scl">
                    <option value="">Todas</option>
                    @foreach($opciones['sucursales'] as $s)
                        <option value="{{ $s->scl_id }}">{{ $s->scl_nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="desktop-kar-bar__field">
                <span class="desktop-kar-bar__cap">Almacén</span>
                <select class="desktop-toolbar__select" id="flt-alm">
                    <option value="">Todos</option>
                    @foreach($opciones['almacenes'] as $a)
                        <option value="{{ $a->alm_id }}" data-scl="{{ $a->alm_scl_id }}">{{ $a->alm_nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="desktop-kar-bar__field">
                <span class="desktop-kar-bar__cap">Desde</span>
                <input type="date" id="flt-desde" class="desktop-toolbar__select">
            </div>
            <div class="desktop-kar-bar__field">
                <span class="desktop-kar-bar__cap">Hasta</span>
                <input type="date" id="flt-hasta" class="desktop-toolbar__select">
            </div>
            <div class="desktop-kar-bar__field">
                <span class="desktop-kar-bar__cap">Registros</span>
                <select class="desktop-toolbar__select" id="flt-length">
                    <option value="10">10 por página</option>
                    <option value="25">25 por página</option>
                    <option value="50" selected>50 por página</option>
                    <option value="100">100 por página</option>
                    <option value="250">250 por página</option>
                </select>
            </div>
            <span class="desktop-kar-bar__spacer"></span>
            <button type="button" class="desktop-btn desktop-btn--default" id="btn-limpiar">Limpiar</button>
            <span class="desktop-kar-bar__divider"></span>
            <button type="button" class="desktop-btn desktop-btn--primary" id="btn-filtrar">Aplicar</button>
        </div>

        <div class="desktop-list-wrap">
            <table class="desktop-list desktop-kar-table" id="tbl-kardex">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Folio</th>
                        <th>Código / SKU</th>
                        <th>Producto / Variante</th>
                        <th>Sucursal</th>
                        <th>Almacén</th>
                        <th>Movimiento</th>
                        <th style="text-align:right;">Cantidad</th>
                        <th style="text-align:right;">Antes</th>
                        <th style="text-align:right;">Después</th>
                        <th>Usuario</th>
                        <th>Estatus</th>
                        <th style="width:48px; text-align:right;">Kardex</th>
                    </tr>
                </thead>
            </table>
        </div>

        <div class="desktop-list-foot">
            <div id="desktop-kar-info"></div>
            <div id="desktop-kar-pagination" class="desktop-pager"></div>
        </div>
    </section>
@endsection

@push('desktop-vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endpush

@push('desktop-scripts')
    <script>
        (function () {
            const rutas = {
                data: @json(route('desktop.operacion.inventario.kardex.data')),
                detalle: @json(url('/desktop/operacion/inventario/kardex/__SKU__/detalle')),
            };
            const claseLabels = { entrada: 'Entrada', salida: 'Salida', ajuste: 'Ajuste', traspaso: 'Traspaso' };
            const $table = $('#tbl-kardex');
            let tabla = null;

            function esc(t) { return $('<div>').text(t ?? '').html(); }
            function num(v) { return Number(v || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
            function fecha(v) { return v ? String(v).replace('T', ' ').slice(0, 16) : '—'; }
            function urlId(tpl, id) { return tpl.replace('__SKU__', encodeURIComponent(String(id))); }

            function renderMeta(title, sub) {
                return '<div class="desktop-kar-meta"><span class="desktop-kar-meta__title">' + esc(title || '—') + '</span>' +
                    (sub ? '<span class="desktop-kar-meta__sub">' + esc(sub) + '</span>' : '') + '</div>';
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
                if (!total) { $('#desktop-kar-info').text('Mostrando 0 movimientos'); $('#desktop-kar-pagination').empty(); return; }
                $('#desktop-kar-info').text('Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + total + ' movimientos');
                const buttons = [];
                const current = info.page, totalPages = info.pages;
                buttons.push({ label: '‹', page: 'previous', disabled: current === 0 });
                for (let i = 0; i < totalPages; i += 1) buttons.push({ label: String(i + 1), page: i, active: i === current });
                buttons.push({ label: '›', page: 'next', disabled: current >= totalPages - 1 });
                $('#desktop-kar-pagination').html(buttons.map(function (b) {
                    const cls = ['desktop-pager__btn', b.active ? 'is-active' : '', b.disabled ? 'is-disabled' : ''].filter(Boolean).join(' ');
                    return '<button type="button" class="' + cls + '" data-page="' + b.page + '"' + (b.disabled ? ' disabled' : '') + '>' + b.label + '</button>';
                }).join(''));
            }

            function buildTabla() {
                tabla = $table.DataTable({
                    processing: true,
                    serverSide: true,
                    pageLength: Number($('#flt-length').val() || 50),
                    lengthChange: false,
                    searching: false,
                    ordering: true,
                    info: false,
                    autoWidth: false,
                    responsive: false,
                    order: [[0, 'desc']],
                    dom: 'rt',
                    language: { processing: 'Cargando...', emptyTable: 'No hay movimientos de inventario', zeroRecords: 'No se encontraron coincidencias' },
                    ajax: {
                        url: rutas.data,
                        data: function (d) {
                            d.min_scl_id = $('#flt-scl').val();
                            d.min_alm_id = $('#flt-alm').val();
                            d.fecha_desde = $('#flt-desde').val();
                            d.fecha_hasta = $('#flt-hasta').val();
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
                        { data: null, render: function (_, __, row) {
                            const clase = row.tmi_clase || ((Number(row.min_signo) >= 0) ? 'entrada' : 'salida');
                            const label = row.tmi_nombre || claseLabels[clase] || 'Movimiento';
                            return '<span class="desktop-kar-mov desktop-kar-mov--' + esc(clase) + '">' + esc(label) + '</span>';
                        }},
                        { data: 'min_cantidad', className: 'desktop-kar-num', render: function (v, _, row) {
                            const signo = Number(row.min_signo) >= 0 ? '+' : '-';
                            const cls = Number(row.min_signo) >= 0 ? 'desktop-kar-qty--in' : 'desktop-kar-qty--out';
                            return '<span class="' + cls + '">' + signo + num(v) + '</span>';
                        }},
                        { data: 'min_existencia_antes', orderable: false, className: 'desktop-kar-num', render: num },
                        { data: 'min_existencia_despues', className: 'desktop-kar-num', render: num },
                        { data: 'usuario_nombre', render: function (v) { return esc(v || '—'); } },
                        { data: 'min_estatus', render: function (v) {
                            const e = String(v || '').toLowerCase();
                            return '<span class="desktop-kar-state desktop-kar-state--' + esc(e) + '">' + esc(e ? e.charAt(0).toUpperCase() + e.slice(1) : '—') + '</span>';
                        }},
                        { data: null, orderable: false, className: 'dt-actions', render: function (_, __, row) {
                            return '<a class="desktop-btn desktop-btn--ghost" style="height:26px;padding:0 8px;" href="' + urlId(rutas.detalle, row.min_psk_id) + '" title="Ver kardex completo del SKU">' +
                                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01"/><path d="M3 12h.01"/><path d="M3 18h.01"/></svg></a>';
                        }},
                    ],
                    initComplete: renderFooter,
                    drawCallback: renderFooter
                });
            }

            function recargar(reset) {
                if (!tabla) { buildTabla(); return; }
                tabla.ajax.reload(null, !!reset);
            }

            $('#flt-scl').on('change', syncAlmacenes);
            $('#btn-filtrar, #btn-recargar-kardex').on('click', function () { recargar(true); });
            $('#flt-buscar').on('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); recargar(true); } });
            $('#flt-length').on('change', function () { if (tabla) tabla.page.len(Number(this.value || 50)).draw(false); });
            $('#btn-limpiar').on('click', function () {
                $('#flt-scl').val(''); syncAlmacenes(); $('#flt-alm').val('');
                $('#flt-desde').val(''); $('#flt-hasta').val(''); $('#flt-buscar').val('');
                recargar(true);
            });
            $('#desktop-kar-pagination').on('click', '.desktop-pager__btn', function () {
                if (!tabla || this.disabled) return;
                const page = $(this).data('page');
                tabla.page(page === 'previous' || page === 'next' ? page : Number(page)).draw('page');
            });

            syncAlmacenes();
            buildTabla();
        })();
    </script>
@endpush
