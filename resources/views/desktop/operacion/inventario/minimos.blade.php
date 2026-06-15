@extends('layouts.desktop')

@section('title', 'Bajo mínimo')

@push('desktop-vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
@endpush

@push('desktop-styles')
    <style>
        .desktop-min-bar { display: flex; align-items: center; gap: 8px; padding: 6px 12px; border-bottom: 1px solid var(--stroke); background: var(--surface-alt); flex-wrap: wrap; }
        .desktop-min-bar__search { position: relative; flex: 1 1 300px; max-width: 420px; display: flex; align-items: center; }
        .desktop-min-bar__search svg { position: absolute; left: 9px; width: 15px; height: 15px; color: var(--text-3); pointer-events: none; }
        .desktop-min-bar__search input { width: 100%; height: 32px; padding: 0 10px 0 30px; border: 1px solid var(--stroke-strong); border-radius: var(--r-md); background: var(--surface); color: var(--text); font: inherit; font-size: .82rem; }
        .desktop-min-bar__search input:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 1px var(--brand); }
        .desktop-min-bar__field { display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; }
        .desktop-min-bar__cap { font-size: .68rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: var(--text-3); }
        .desktop-min-bar__field .desktop-toolbar__select { height: 32px; max-width: 170px; }
        .desktop-min-bar__spacer { flex: 1 1 auto; }
        .desktop-min-bar__divider { width: 1px; height: 22px; background: var(--stroke); }

        .desktop-min-table tbody td { padding-top: 5px !important; padding-bottom: 5px !important; vertical-align: middle; }
        .desktop-min-table tbody tr.is-selected td { background: #eaf1fd; }
        .desktop-min-meta { display: flex; flex-direction: column; gap: 1px; line-height: 1.2; min-width: 0; }
        .desktop-min-meta__title { font-weight: 600; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .desktop-min-meta__sub { font-size: .73rem; color: var(--text-2); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .desktop-min-num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; font-weight: 600; }
        .desktop-min-exist { color: var(--danger); font-weight: 800; }
        .desktop-min-falta { display: inline-flex; align-items: center; min-height: 22px; padding: 0 9px; border-radius: 999px; background: rgba(193,42,46,.1); color: var(--danger); font-weight: 800; font-variant-numeric: tabular-nums; }

        @media (max-width: 1100px) {
            .desktop-min-bar { flex-wrap: wrap; }
            .desktop-min-bar__search { flex: 1 1 100%; max-width: none; order: -1; }
            .desktop-min-bar__spacer { display: none; }
        }
    </style>
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        @php $activeSubmenu = 'minimos'; @endphp
        @include('desktop.operacion.inventario._subnav')
        <span class="desktop-toolbar__divider"></span>
        <button type="button" class="desktop-btn desktop-btn--ghost" id="btn-recargar-min">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
            Actualizar
        </button>
    </div>
@endsection

@section('content')
    <section class="desktop-pane">
        <div class="desktop-min-bar">
            <div class="desktop-min-bar__search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <input id="flt-buscar" type="search" placeholder="Código, SKU o producto">
            </div>
            <div class="desktop-min-bar__field">
                <span class="desktop-min-bar__cap">Sucursal</span>
                <select class="desktop-toolbar__select" id="flt-scl">
                    <option value="">Todas</option>
                    @foreach($opciones['sucursales'] as $s)
                        <option value="{{ $s->scl_id }}">{{ $s->scl_nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="desktop-min-bar__field">
                <span class="desktop-min-bar__cap">Almacén</span>
                <select class="desktop-toolbar__select" id="flt-alm">
                    <option value="">Todos</option>
                    @foreach($opciones['almacenes'] as $a)
                        <option value="{{ $a->alm_id }}" data-scl="{{ $a->alm_scl_id }}">{{ $a->alm_nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="desktop-min-bar__field">
                <span class="desktop-min-bar__cap">Registros</span>
                <select class="desktop-toolbar__select" id="flt-length">
                    <option value="10">10 por página</option>
                    <option value="25">25 por página</option>
                    <option value="50" selected>50 por página</option>
                    <option value="100">100 por página</option>
                    <option value="250">250 por página</option>
                </select>
            </div>
            <span class="desktop-min-bar__spacer"></span>
            <button type="button" class="desktop-btn desktop-btn--default" id="btn-limpiar">Limpiar</button>
            <span class="desktop-min-bar__divider"></span>
            <button type="button" class="desktop-btn desktop-btn--primary" id="btn-filtrar">Aplicar</button>
        </div>

        <div class="desktop-list-wrap">
            <table class="desktop-list desktop-min-table" id="tbl-minimos">
                <thead>
                    <tr>
                        <th>Código / SKU</th>
                        <th>Producto</th>
                        <th>Sucursal</th>
                        <th>Almacén</th>
                        <th style="text-align:right;">Existencia</th>
                        <th style="text-align:right;">Mínimo</th>
                        <th style="text-align:right;">Faltante</th>
                    </tr>
                </thead>
            </table>
        </div>

        <div class="desktop-list-foot">
            <div id="desktop-min-info"></div>
            <div id="desktop-min-pagination" class="desktop-pager"></div>
        </div>
    </section>
@endsection

@push('desktop-vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endpush

@push('desktop-scripts')
    <script>
        (function () {
            const rutas = { data: @json(route('desktop.operacion.inventario.minimos.bajo.data')) };
            const $table = $('#tbl-minimos');
            let tabla = null;

            function esc(t) { return $('<div>').text(t ?? '').html(); }
            function num(v) { return Number(v || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
            function renderMeta(title, sub) {
                return '<div class="desktop-min-meta"><span class="desktop-min-meta__title">' + esc(title || '—') + '</span>' +
                    (sub ? '<span class="desktop-min-meta__sub">' + esc(sub) + '</span>' : '') + '</div>';
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
                if (!total) { $('#desktop-min-info').text('Sin productos bajo mínimo'); $('#desktop-min-pagination').empty(); return; }
                $('#desktop-min-info').text('Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + total + ' productos bajo mínimo');
                const buttons = [];
                const current = info.page, totalPages = info.pages;
                buttons.push({ label: '‹', page: 'previous', disabled: current === 0 });
                for (let i = 0; i < totalPages; i += 1) buttons.push({ label: String(i + 1), page: i, active: i === current });
                buttons.push({ label: '›', page: 'next', disabled: current >= totalPages - 1 });
                $('#desktop-min-pagination').html(buttons.map(function (b) {
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
                    order: [[0, 'asc']],
                    dom: 'rt',
                    language: { processing: 'Cargando...', emptyTable: 'No hay productos por debajo del mínimo', zeroRecords: 'No se encontraron coincidencias' },
                    ajax: {
                        url: rutas.data,
                        data: function (d) {
                            d.mni_scl_id = $('#flt-scl').val();
                            d.mni_alm_id = $('#flt-alm').val();
                            d.buscar = $('#flt-buscar').val().trim();
                        }
                    },
                    columns: [
                        { data: 'psk_codigo', render: function (v) { return esc(v || '—'); } },
                        { data: null, orderable: false, render: function (_, __, row) { return renderMeta(row.prd_nombre, row.psk_nombre); } },
                        { data: 'scl_nombre', render: function (v) { return esc(v || '—'); } },
                        { data: 'alm_nombre', render: function (v) { return esc(v || '—'); } },
                        { data: 'exa_existencia', className: 'desktop-min-num', render: function (v) { return '<span class="desktop-min-exist">' + num(v) + '</span>'; } },
                        { data: 'mni_minimo', className: 'desktop-min-num', render: num },
                        { data: null, orderable: false, className: 'desktop-min-num', render: function (_, __, row) {
                            const falta = Number(row.mni_minimo || 0) - Number(row.exa_existencia || 0);
                            return '<span class="desktop-min-falta">' + num(falta > 0 ? falta : 0) + '</span>';
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
            $('#btn-filtrar, #btn-recargar-min').on('click', function () { recargar(true); });
            $('#flt-buscar').on('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); recargar(true); } });
            $('#flt-length').on('change', function () { if (tabla) tabla.page.len(Number(this.value || 50)).draw(false); });
            $('#btn-limpiar').on('click', function () {
                $('#flt-scl').val(''); syncAlmacenes(); $('#flt-alm').val(''); $('#flt-buscar').val('');
                recargar(true);
            });
            $('#desktop-min-pagination').on('click', '.desktop-pager__btn', function () {
                if (!tabla || this.disabled) return;
                const page = $(this).data('page');
                tabla.page(page === 'previous' || page === 'next' ? page : Number(page)).draw('page');
            });

            syncAlmacenes();
            buildTabla();
        })();
    </script>
@endpush
