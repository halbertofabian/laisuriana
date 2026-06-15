@extends('layouts.desktop')

@section('title', 'Recepciones capturadas')

@push('desktop-vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
@endpush

@push('desktop-styles')
    <style>
        .desktop-rme-bar {
            display: flex; align-items: center; gap: 8px;
            padding: 6px 12px;
            border-bottom: 1px solid var(--stroke);
            background: var(--surface-alt);
        }
        .desktop-rme-bar__search { position: relative; flex: 1 1 320px; max-width: 420px; display: flex; align-items: center; }
        .desktop-rme-bar__search svg { position: absolute; left: 9px; width: 15px; height: 15px; color: var(--text-3); pointer-events: none; }
        .desktop-rme-bar__search input {
            width: 100%; height: 32px; padding: 0 10px 0 30px;
            border: 1px solid var(--stroke-strong); border-radius: var(--r-md);
            background: var(--surface); color: var(--text); font: inherit; font-size: .82rem;
        }
        .desktop-rme-bar__search input:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 1px var(--brand); }
        .desktop-rme-bar__field { display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; }
        .desktop-rme-bar__cap { font-size: .68rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: var(--text-3); }
        .desktop-rme-bar__field .desktop-toolbar__select,
        .desktop-rme-bar__field input { height: 32px; max-width: 170px; }
        .desktop-rme-bar__spacer { flex: 1 1 auto; }
        .desktop-rme-bar__divider { width: 1px; height: 22px; background: var(--stroke); }

        .desktop-rme-table tbody td { padding-top: 5px !important; padding-bottom: 5px !important; vertical-align: middle; }
        .desktop-rme-table tbody tr.is-selected td { background: #eaf1fd; }
        .desktop-rme-meta { display: flex; flex-direction: column; gap: 1px; line-height: 1.2; min-width: 0; }
        .desktop-rme-meta__title { font-weight: 600; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .desktop-rme-meta__sub { font-size: .74rem; color: var(--text-2); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .desktop-rme-num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; font-weight: 600; }

        .desktop-rme-state {
            display: inline-flex; align-items: center; gap: 6px;
            height: 22px; padding: 0 10px; border-radius: 999px;
            font-size: .74rem; font-weight: 700; white-space: nowrap;
        }
        .desktop-rme-state::before { content: ""; width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
        .desktop-rme-state--finalizado { background: rgba(17,121,80,.12); color: var(--success); }
        .desktop-rme-state--borrador { background: rgba(181,124,0,.14); color: #8a5a00; }
        .desktop-rme-state--cancelado { background: rgba(193,42,46,.12); color: var(--danger); }

        /* Detalle (modal) */
        .desktop-rme-detail__grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-bottom: 14px; }
        .desktop-rme-detail__cell label { display: block; font-size: .67rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: var(--text-3); margin-bottom: 2px; }
        .desktop-rme-detail__cell div { font-size: .84rem; font-weight: 600; color: var(--text); }
        .desktop-rme-lines { width: 100%; border-collapse: collapse; font-size: .82rem; }
        .desktop-rme-lines th { text-align: left; padding: 6px 8px; border-bottom: 1px solid var(--stroke); font-size: .7rem; font-weight: 700; text-transform: uppercase; color: var(--text-3); }
        .desktop-rme-lines td { padding: 6px 8px; border-bottom: 1px solid var(--divider); }
        .desktop-rme-lines td.num { text-align: right; font-variant-numeric: tabular-nums; }
        .desktop-rme-detail__obs { margin-bottom: 14px; }
        .desktop-rme-detail__obs label { display: block; font-size: .67rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: var(--text-3); margin-bottom: 3px; }
        .desktop-rme-detail__obs div { font-size: .82rem; color: var(--text); background: var(--surface-alt); border: 1px solid var(--stroke); border-radius: var(--r-md); padding: 8px 10px; white-space: pre-wrap; }
        .desktop-rme-detail__foot { display: flex; justify-content: space-between; gap: 24px; flex-wrap: wrap; margin-top: 12px; padding-top: 10px; border-top: 1px solid var(--stroke); }
        .desktop-rme-detail__totals { display: flex; gap: 20px; flex-wrap: wrap; align-content: flex-start; }
        .desktop-rme-detail__total b { font-size: 1rem; font-variant-numeric: tabular-nums; }
        .desktop-rme-detail__total span { display: block; font-size: .68rem; font-weight: 700; text-transform: uppercase; color: var(--text-3); }
        .desktop-rme-money { min-width: 220px; margin-left: auto; }
        .desktop-rme-money__row { display: flex; align-items: baseline; justify-content: space-between; gap: 24px; padding: 3px 0; font-size: .82rem; color: var(--text-2); }
        .desktop-rme-money__row b { color: var(--text); font-variant-numeric: tabular-nums; font-weight: 600; }
        .desktop-rme-money__row.is-grand { border-top: 1px solid var(--stroke); margin-top: 4px; padding-top: 6px; font-size: .92rem; }
        .desktop-rme-money__row.is-grand span { font-weight: 700; color: var(--text); }
        .desktop-rme-money__row.is-grand b { color: var(--brand); font-weight: 800; font-size: 1.05rem; }

        @media (max-width: 1100px) {
            .desktop-rme-bar { flex-wrap: wrap; }
            .desktop-rme-bar__search { flex: 1 1 100%; max-width: none; order: -1; }
            .desktop-rme-bar__spacer { display: none; }
        }
    </style>
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        @php $activeSubmenu = 'recepciones'; @endphp
        @include('desktop.operacion.inventario._subnav')
        <span class="desktop-toolbar__divider"></span>
        <a href="{{ route('desktop.operacion.inventario.recibir.index') }}" class="desktop-btn desktop-btn--primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            Recibir mercancía
        </a>
        <button type="button" class="desktop-btn desktop-btn--ghost" id="btn-recargar-rme">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
            Actualizar
        </button>
    </div>
@endsection

@section('content')
    <section class="desktop-pane">
        <div class="desktop-rme-bar">
            <div class="desktop-rme-bar__search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <input id="flt-buscar" type="search" placeholder="Buscar folio, referencia o proveedor">
            </div>
            <div class="desktop-rme-bar__field">
                <span class="desktop-rme-bar__cap">Estado</span>
                <select class="desktop-toolbar__select" id="flt-estado">
                    <option value="">Todos</option>
                    <option value="finalizado">Finalizado</option>
                    <option value="borrador">Borrador</option>
                    <option value="cancelado">Cancelado</option>
                </select>
            </div>
            <div class="desktop-rme-bar__field">
                <span class="desktop-rme-bar__cap">Desde</span>
                <input type="date" id="flt-desde" class="desktop-toolbar__select">
            </div>
            <div class="desktop-rme-bar__field">
                <span class="desktop-rme-bar__cap">Hasta</span>
                <input type="date" id="flt-hasta" class="desktop-toolbar__select">
            </div>
            <div class="desktop-rme-bar__field">
                <span class="desktop-rme-bar__cap">Registros</span>
                <select class="desktop-toolbar__select" id="flt-length">
                    <option value="10">10 por página</option>
                    <option value="25">25 por página</option>
                    <option value="50" selected>50 por página</option>
                    <option value="100">100 por página</option>
                    <option value="250">250 por página</option>
                </select>
            </div>

            <span class="desktop-rme-bar__spacer"></span>
            <button type="button" class="desktop-btn desktop-btn--primary" id="btn-filtrar">Aplicar</button>
        </div>

        <div class="desktop-list-wrap">
            <table class="desktop-list desktop-rme-table" id="tbl-recepciones">
                <thead>
                    <tr>
                        <th>Fecha captura</th>
                        <th>Folio</th>
                        <th>Estado</th>
                        <th>Sucursal / Almacén</th>
                        <th>Proveedor</th>
                        <th style="text-align:right;">Líneas</th>
                        <th style="text-align:right;">Artículos</th>
                        <th style="text-align:right;">Importe</th>
                        <th>Capturó</th>
                        <th style="width:56px; text-align:right;">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>

        <div class="desktop-list-foot">
            <div id="desktop-rme-info"></div>
            <div id="desktop-rme-pagination" class="desktop-pager"></div>
        </div>
    </section>

    <div class="desktop-modal" id="rme-modal" aria-hidden="true">
        <div class="desktop-modal__dialog" style="max-width:720px;">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="rme-modal-title">Detalle de recepción</div>
                <button type="button" class="desktop-modal__close" data-close-rme aria-label="Cerrar">&times;</button>
            </div>
            <div class="desktop-modal__body" id="rme-modal-body">
                <div style="padding:24px; text-align:center; color:var(--text-2);">Cargando…</div>
            </div>
            <div class="desktop-modal__foot">
                <button type="button" class="desktop-btn desktop-btn--default" data-close-rme>Cerrar</button>
                <a href="#" target="_blank" class="desktop-btn desktop-btn--primary" id="rme-modal-pdf">Ver PDF</a>
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
            const rutas = {
                data: @json(route('desktop.operacion.inventario.recepciones.data')),
                show: @json(url('/desktop/operacion/inventario/recepciones/__ID__')),
                pdf: @json(url('/desktop/operacion/inventario/recepciones/__ID__/reporte-pdf')),
                cancelar: @json(url('/desktop/operacion/inventario/recepciones/__ID__/cancelar')),
                recibir: @json(route('desktop.operacion.inventario.recibir.index')),
            };
            const csrf = @json(csrf_token());
            const ICONS = { dots: '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>' };

            const $table = $('#tbl-recepciones');
            let tabla = null;

            function escapeHtml(text) { return $('<div>').text(text ?? '').html(); }
            function money(v) { return Number(v || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
            function num(v) { return Number(v || 0).toLocaleString('es-MX'); }
            function fecha(v) { return v ? String(v).replace('T', ' ').slice(0, 16) : '—'; }
            function urlFor(tpl, id) { return tpl.replace('__ID__', encodeURIComponent(String(id))); }

            function renderMeta(title, subtitle) {
                return '<div class="desktop-rme-meta"><span class="desktop-rme-meta__title">' + escapeHtml(title || '—') + '</span>' +
                    (subtitle ? '<span class="desktop-rme-meta__sub">' + escapeHtml(subtitle) + '</span>' : '') + '</div>';
            }
            function renderEstado(v) {
                const estado = String(v || '').toLowerCase();
                const label = estado ? estado.charAt(0).toUpperCase() + estado.slice(1) : '—';
                return '<span class="desktop-rme-state desktop-rme-state--' + escapeHtml(estado) + '">' + escapeHtml(label) + '</span>';
            }
            function renderAcciones(row) {
                const id = row.rme_id;
                const estado = String(row.rme_estado || '').toLowerCase();
                let items;
                if (estado === 'borrador') {
                    // Un borrador se retoma o se descarta (aún no tiene movimientos)
                    items = '<button type="button" class="desktop-menu__item" data-act="continuar" data-id="' + id + '">Continuar captura</button>' +
                        '<div class="desktop-menu__divider"></div>' +
                        '<button type="button" class="desktop-menu__item desktop-menu__item--danger" data-act="cancelar" data-id="' + id + '">Descartar borrador</button>';
                } else {
                    items = '<button type="button" class="desktop-menu__item" data-act="ver" data-id="' + id + '">Ver detalle</button>' +
                        '<a class="desktop-menu__item" href="' + urlFor(rutas.pdf, id) + '" target="_blank">Ver PDF</a>';
                }
                return '<div class="desktop-rowmenu">' +
                    '<button type="button" class="desktop-overflow" data-overflow aria-haspopup="true" aria-expanded="false" aria-label="Más acciones">' + ICONS.dots + '</button>' +
                    '<div class="desktop-menu" role="menu">' + items + '</div>' +
                '</div>';
            }

            function renderFooter() {
                if (!tabla) return;
                const info = tabla.page.info();
                const total = info.recordsDisplay;
                if (!total) {
                    $('#desktop-rme-info').text('Mostrando 0 registros');
                    $('#desktop-rme-pagination').empty();
                    return;
                }
                $('#desktop-rme-info').text('Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + total + ' registros');
                const buttons = [];
                const current = info.page, totalPages = info.pages;
                buttons.push({ label: '‹', page: 'previous', disabled: current === 0 });
                for (let i = 0; i < totalPages; i += 1) buttons.push({ label: String(i + 1), page: i, active: i === current });
                buttons.push({ label: '›', page: 'next', disabled: current >= totalPages - 1 });
                $('#desktop-rme-pagination').html(buttons.map(function (b) {
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
                    language: { processing: 'Cargando...', emptyTable: 'No hay recepciones capturadas', zeroRecords: 'No se encontraron coincidencias' },
                    ajax: {
                        url: rutas.data,
                        data: function (d) {
                            d.estado = $('#flt-estado').val();
                            d.fecha_desde = $('#flt-desde').val();
                            d.fecha_hasta = $('#flt-hasta').val();
                            d.buscar = $('#flt-buscar').val().trim();
                        }
                    },
                    columns: [
                        { data: 'rme_fecha_captura', render: function (v, _, row) { return renderMeta(fecha(v), row.rme_documento_referencia || ''); } },
                        { data: 'rme_folio', render: function (v) { return renderMeta(v || '—'); } },
                        { data: 'rme_estado', render: renderEstado },
                        { data: null, orderable: false, render: function (_, __, row) { return renderMeta(row.sucursal_nombre || '—', row.almacen_nombre || ''); } },
                        { data: 'proveedor_nombre', orderable: false, render: function (v) { return renderMeta(v || '—'); } },
                        { data: 'total_lineas', orderable: false, className: 'desktop-rme-num', render: num },
                        { data: 'total_articulos', orderable: false, className: 'desktop-rme-num', render: function (v) { return money(v); } },
                        { data: 'total_importe', orderable: false, className: 'desktop-rme-num', render: function (v) { return '$ ' + money(v); } },
                        { data: 'usuario_creo', orderable: false, render: function (v) { return renderMeta(v || '—'); } },
                        { data: null, orderable: false, className: 'dt-actions', render: function (_, __, row) { return renderAcciones(row); } },
                    ],
                    initComplete: renderFooter,
                    drawCallback: renderFooter
                });
            }

            function recargar(reset) {
                if (!tabla) { buildTabla(); return; }
                tabla.ajax.reload(null, !!reset);
            }

            // ===== Modal de detalle =====
            function abrirModal() { $('#rme-modal').addClass('is-open').attr('aria-hidden', 'false'); }
            function cerrarModal() { $('#rme-modal').removeClass('is-open').attr('aria-hidden', 'true'); }

            function verDetalle(id) {
                abrirModal();
                $('#rme-modal-pdf').attr('href', urlFor(rutas.pdf, id));
                $('#rme-modal-body').html('<div style="padding:24px; text-align:center; color:var(--text-2);">Cargando…</div>');
                $.getJSON(urlFor(rutas.show, id)).done(function (resp) {
                    const d = resp.data || {};
                    const r = d.resumen || {};
                    $('#rme-modal-title').text('Recepción ' + (d.rme_folio || ''));
                    const tipo = String(d.min_documento_tipo || '');
                    const esFactura = tipo === 'compra_factura';
                    const esRemision = tipo === 'compra_remision';
                    const obs = String(d.min_observaciones || '').trim();
                    const cab = '<div class="desktop-rme-detail__grid">' +
                        '<div class="desktop-rme-detail__cell"><label>Estado</label><div>' + renderEstado(d.rme_estado) + '</div></div>' +
                        '<div class="desktop-rme-detail__cell"><label>Sucursal</label><div>' + escapeHtml(r.sucursal_nombre || '—') + '</div></div>' +
                        '<div class="desktop-rme-detail__cell"><label>Almacén</label><div>' + escapeHtml(r.almacen_nombre || '—') + '</div></div>' +
                        '<div class="desktop-rme-detail__cell"><label>Proveedor</label><div>' + escapeHtml(r.proveedor_nombre || '—') + '</div></div>' +
                        '<div class="desktop-rme-detail__cell"><label>Referencia</label><div>' + escapeHtml(d.min_documento_referencia || '—') + '</div></div>' +
                        '<div class="desktop-rme-detail__cell"><label>Fecha</label><div>' + escapeHtml(fecha(d.min_fecha_movimiento)) + '</div></div>' +
                    '</div>' +
                    (obs ? '<div class="desktop-rme-detail__obs"><label>Observaciones</label><div>' + escapeHtml(obs) + '</div></div>' : '');
                    const filas = (d.lineas || []).map(function (l) {
                        return '<tr><td>' + escapeHtml(l.sku_codigo || '—') + '</td>' +
                            '<td>' + escapeHtml(l.producto_nombre || l.sku_nombre || '—') + '</td>' +
                            '<td class="num">' + money(l.min_cantidad) + '</td>' +
                            '<td class="num">$ ' + money(l.min_precio_unitario) + '</td>' +
                            '<td class="num">$ ' + money((l.min_cantidad || 0) * (l.min_precio_unitario || 0)) + '</td></tr>';
                    }).join('') || '<tr><td colspan="5" style="text-align:center; color:var(--text-2); padding:16px;">Sin líneas</td></tr>';
                    const tabla = '<table class="desktop-rme-lines"><thead><tr><th>SKU</th><th>Producto</th><th style="text-align:right;">Cant.</th><th style="text-align:right;">Precio</th><th style="text-align:right;">Importe</th></tr></thead><tbody>' + filas + '</tbody></table>';

                    // Desglose monetario por tipo
                    const subtotal = Number(r.total_importe || 0);
                    const dt = d.min_descuento_tipo || 'ninguno', dv = Number(d.min_descuento_valor || 0);
                    const desc = dt === 'importe' ? dv : (dt === 'porcentaje' ? subtotal * Math.min(dv, 100) / 100 : 0);
                    const flete = Number(d.min_flete_total || 0);
                    const base = Math.max(0, subtotal - desc) + flete;
                    const ivaPct = Number(d.min_iva_porcentaje || 0);
                    const iva = esFactura ? base * ivaPct / 100 : 0;
                    const total = base + iva;
                    const linea = (lbl, val, grand) => '<div class="desktop-rme-money__row' + (grand ? ' is-grand' : '') + '"><span>' + lbl + '</span><b>$ ' + money(val) + '</b></div>';
                    let money_rows = '';
                    if (esRemision) {
                        money_rows = linea('Descuento', desc) + linea('Flete', flete) + linea('Total', total, true);
                    } else {
                        money_rows = linea('Subtotal', subtotal) + linea('Descuento', desc) + linea('Flete', flete) +
                            (esFactura ? linea('IVA (' + ivaPct + '%)', iva) : '') + linea('Total', total, true);
                    }
                    const totales = '<div class="desktop-rme-detail__foot">' +
                        '<div class="desktop-rme-detail__totals">' +
                            '<div class="desktop-rme-detail__total"><span>Líneas</span><b>' + num(r.total_lineas) + '</b></div>' +
                            '<div class="desktop-rme-detail__total"><span>Artículos</span><b>' + money(r.total_articulos) + '</b></div>' +
                        '</div>' +
                        '<div class="desktop-rme-money">' + money_rows + '</div>' +
                    '</div>';
                    $('#rme-modal-body').html(cab + tabla + totales);
                }).fail(function () {
                    $('#rme-modal-body').html('<div style="padding:24px; text-align:center; color:var(--danger);">No fue posible cargar el detalle.</div>');
                });
            }

            function notify(title, msg, type) {
                if (window.DesktopUI) DesktopUI.message(title, msg, type);
                else if (window.AppUI?.showMessage) window.AppUI.showMessage(title, msg, type);
            }

            function continuar(id) {
                window.location.href = rutas.recibir + '?rme_id=' + encodeURIComponent(String(id));
            }

            async function cancelar(id) {
                const ok = await DesktopUI.confirm({
                    title: 'Descartar borrador',
                    message: 'Se descartará el borrador seleccionado. Esta acción no se puede deshacer.',
                    okText: 'Descartar borrador',
                    danger: true,
                });
                if (!ok) return;
                $.ajax({
                    url: urlFor(rutas.cancelar, id),
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf },
                    data: { motivo: '' }
                }).done(function (resp) {
                    notify('Listo', resp.message || 'Borrador descartado.', 'success');
                    recargar(false);
                }).fail(function (xhr) {
                    notify('Error', xhr.responseJSON?.message || 'No fue posible descartar el borrador.', 'error');
                });
            }

            $('#btn-filtrar, #btn-recargar-rme').on('click', function () { recargar(true); });
            $('#flt-buscar').on('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); recargar(true); } });
            $('#flt-length').on('change', function () { if (tabla) tabla.page.len(Number(this.value || 50)).draw(false); });

            $('#desktop-rme-pagination').on('click', '.desktop-pager__btn', function () {
                if (!tabla || this.disabled) return;
                const page = $(this).data('page');
                tabla.page(page === 'previous' || page === 'next' ? page : Number(page)).draw('page');
            });

            $table.on('click', '[data-act="ver"]', function () { verDetalle($(this).data('id')); });
            $table.on('click', '[data-act="continuar"]', function () { continuar($(this).data('id')); });
            $table.on('click', '[data-act="cancelar"]', function () { cancelar($(this).data('id')); });
            $(document).on('click', '[data-close-rme]', cerrarModal);
            $('#rme-modal').on('click', function (e) { if (e.target === this) cerrarModal(); });
            $(document).on('keydown', function (e) { if (e.key === 'Escape') cerrarModal(); });

            buildTabla();
        })();
    </script>
@endpush
