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
                <span class="desktop-sal-bar__cap">Tipo</span>
                <select class="desktop-toolbar__select" id="flt-tipo">
                    <option value="">Todos</option>
                    <option value="ajuste_manual">Ajuste manual</option>
                    <option value="merma">Merma</option>
                </select>
            </div>
            <div class="desktop-sal-bar__field">
                <span class="desktop-sal-bar__cap">Sucursal</span>
                <select class="desktop-toolbar__select" id="flt-scl">
                    <option value="">Todas</option>
                    @foreach($opciones['sucursales'] as $s)
                        <option value="{{ $s->scl_id }}">{{ $s->scl_nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="desktop-sal-bar__field">
                <span class="desktop-sal-bar__cap">Almacén</span>
                <select class="desktop-toolbar__select" id="flt-alm">
                    <option value="">Todos</option>
                    @foreach($opciones['almacenes'] as $a)
                        <option value="{{ $a->alm_id }}" data-scl="{{ $a->alm_scl_id }}">{{ $a->alm_nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="desktop-sal-bar__field">
                <span class="desktop-sal-bar__cap">Desde</span>
                <input type="date" id="flt-desde" class="desktop-toolbar__select">
            </div>
            <div class="desktop-sal-bar__field">
                <span class="desktop-sal-bar__cap">Hasta</span>
                <input type="date" id="flt-hasta" class="desktop-toolbar__select">
            </div>
            <div class="desktop-sal-bar__field">
                <span class="desktop-sal-bar__cap">Registros</span>
                <select class="desktop-toolbar__select" id="flt-length">
                    <option value="10">10 por página</option>
                    <option value="25">25 por página</option>
                    <option value="50" selected>50 por página</option>
                    <option value="100">100 por página</option>
                    <option value="250">250 por página</option>
                </select>
            </div>
            <span class="desktop-sal-bar__spacer"></span>
            <button type="button" class="desktop-btn desktop-btn--default" id="btn-limpiar">Limpiar</button>
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
                    pageLength: Number($('#flt-length').val() || 50),
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
                            d.min_scl_id = $('#flt-scl').val();
                            d.min_alm_id = $('#flt-alm').val();
                            d.tipo = $('#flt-tipo').val();
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

            $('#flt-scl').on('change', syncAlmacenes);
            $('#btn-filtrar, #btn-recargar-sal').on('click', function () { recargar(true); });
            $('#flt-buscar').on('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); recargar(true); } });
            $('#flt-length').on('change', function () { if (tabla) tabla.page.len(Number(this.value || 50)).draw(false); });
            $('#btn-limpiar').on('click', function () {
                $('#flt-scl').val(''); syncAlmacenes(); $('#flt-alm').val(''); $('#flt-tipo').val('');
                $('#flt-desde').val(''); $('#flt-hasta').val(''); $('#flt-buscar').val('');
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
            buildTabla();
        })();
    </script>
@endpush
