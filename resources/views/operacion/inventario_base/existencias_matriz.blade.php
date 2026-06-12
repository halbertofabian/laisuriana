@extends('layouts.app')

@section('title', 'Existencias Matriz')

@push('vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/select2/select2.css') }}" />
    <style>
        .em-filter-bar {
            background: var(--ls-surface-2);
            border: 1px solid var(--ls-border);
            border-radius: var(--ls-radius);
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .em-filter-bar .form-label {
            font-size: .78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--ls-text-muted);
            margin-bottom: .35rem;
        }

        .em-filter-actions {
            display: flex;
            gap: .5rem;
            align-items: end;
            height: 100%;
        }

        .em-table td,
        .em-table th {
            vertical-align: middle;
        }

        .em-meta {
            display: flex;
            flex-direction: column;
            gap: .15rem;
        }

        .em-meta__title {
            font-weight: 700;
            color: var(--ls-text-primary);
            line-height: 1.2;
        }

        .em-meta__sub {
            font-size: .78rem;
            color: var(--ls-text-secondary);
            line-height: 1.3;
        }

        .em-size-strip {
            display: flex;
            flex-wrap: wrap;
            gap: .25rem .3rem;
            align-items: center;
            min-width: 220px;
        }

        .em-size-pill {
            display: inline-flex;
            align-items: baseline;
            gap: .18rem;
            border: 1px solid var(--ls-border);
            border-radius: .3rem;
            padding: .08rem .38rem;
            font-size: .74rem;
            line-height: 1.6;
            white-space: nowrap;
            background: var(--ls-surface-2);
        }

        .em-size-pill__name {
            font-weight: 700;
            color: var(--ls-text-secondary);
            font-size: .7rem;
            letter-spacing: .03em;
        }

        .em-size-pill__sep {
            color: var(--ls-text-muted);
            font-size: .68rem;
        }

        .em-size-pill__val {
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            color: var(--ls-text-primary);
        }

        .em-size-pill[data-psk-id] {
            cursor: context-menu;
        }

        .em-size-pill--ok {
            border-color: rgba(26, 158, 109, .3);
            background: var(--ls-success-bg);
        }
        .em-size-pill--ok .em-size-pill__val { color: var(--ls-success); }

        .em-size-pill--zero {
            border-color: rgba(233, 155, 62, .3);
            background: var(--ls-warning-bg);
        }
        .em-size-pill--zero .em-size-pill__val { color: var(--ls-warning); }

        .em-size-pill--new {
            border-color: rgba(37, 99, 235, .28);
            background: rgba(37, 99, 235, .08);
        }
        .em-size-pill--new .em-size-pill__val { color: #2563eb; }

        .em-size-pill--na {
            border-style: dashed;
            opacity: .6;
        }
        .em-size-pill--na .em-size-pill__val { color: var(--ls-text-muted); }

        .em-legend {
            display: flex;
            flex-wrap: wrap;
            gap: .6rem;
            margin-top: .75rem;
        }

        .em-legend__item {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            font-size: .78rem;
            color: var(--ls-text-secondary);
        }

        .em-legend__dot {
            width: 11px;
            height: 11px;
            border-radius: 999px;
            display: inline-block;
            border: 1px solid transparent;
        }

        .em-legend__dot--ok { background: var(--ls-success); }
        .em-legend__dot--zero { background: var(--ls-warning); }
        .em-legend__dot--na {
            background: var(--ls-surface-3);
            border-color: var(--ls-border);
        }

        .em-context-menu {
            position: fixed;
            z-index: 1085;
            min-width: 220px;
            background: #fff;
            border: 1px solid var(--ls-border);
            border-radius: .7rem;
            box-shadow: 0 16px 42px rgba(15, 23, 42, .16);
            padding: .35rem;
        }

        .em-context-menu.d-none {
            display: none !important;
        }

        .em-context-menu__button {
            width: 100%;
            border: 0;
            background: transparent;
            text-align: left;
            border-radius: .5rem;
            padding: .65rem .75rem;
            font-size: .86rem;
            color: var(--ls-text-primary);
        }

        .em-context-menu__button:hover,
        .em-context-menu__button:focus {
            background: var(--ls-surface-2);
            outline: none;
        }

        .em-context-menu__button:disabled {
            opacity: .55;
            cursor: not-allowed;
        }

        .em-table tbody tr.em-row-selected td {
            background-color: var(--ls-primary-bg, rgba(105, 108, 255, .09));
            box-shadow: inset 3px 0 0 var(--bs-primary, #696cff);
        }

        .em-table tbody tr.em-row-selected:hover td {
            background-color: var(--ls-primary-bg, rgba(105, 108, 255, .13));
        }

        .em-num {
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .datatable-toolbar .dataTables_filter label,
        .datatable-toolbar .dataTables_length label {
            font-size: .82rem;
            font-weight: 500;
            color: var(--ls-text-muted);
        }

        .datatable-toolbar .dataTables_filter input,
        .datatable-toolbar .dataTables_length select {
            min-height: 2.1rem;
            border-radius: var(--ls-radius);
            border: 1px solid var(--ls-border);
            font-size: .84rem;
        }

        .select2-container--default .select2-selection--single,
        .select2-container--default .select2-selection--multiple {
            border-color: var(--ls-border);
            min-height: 38px;
        }

        @media (max-width: 991.98px) {
            .em-filter-actions {
                align-items: stretch;
            }

            .em-filter-actions .btn {
                flex: 1;
            }

            .em-size-strip {
                min-width: 180px;
            }
        }
    </style>
@endpush

@section('content')
<x-section-header
    eyebrow="Operación"
    title="Existencias Matriz"
    subtitle="Consulta agrupada por producto y color, con lectura de existencias por talla en una sola fila."
/>

<div class="card app-tabs-shell mb-4">
    <div class="app-tabs-shell__body">
        <div class="em-filter-bar">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label" for="flt-scl">Sucursal</label>
                    <select id="flt-scl" class="form-select js-select2-basic">
                        @foreach($opciones['sucursales'] as $sucursal)
                            <option value="{{ $sucursal->scl_id }}" @selected((int) $sucursal->scl_id === (int) ($defaultSucursalId ?? 0))>{{ $sucursal->scl_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="flt-alm">Almacén</label>
                    <select id="flt-alm" class="form-select js-select2-basic">
                        <option value="">Todos</option>
                        @foreach($opciones['almacenes'] as $almacen)
                            <option value="{{ $almacen->alm_id }}" data-scl="{{ $almacen->alm_scl_id }}">{{ $almacen->alm_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="flt-mrc">Marca</label>
                    <select id="flt-mrc" class="form-select js-select2-basic">
                        <option value="">Todas</option>
                        @foreach($opciones['marcas'] as $marca)
                            <option value="{{ $marca->mrc_id }}">{{ $marca->mrc_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="flt-mdl">Modelo</label>
                    <select id="flt-mdl" class="form-select js-select2-basic">
                        <option value="">Todos</option>
                        @foreach($opciones['modelos'] as $modelo)
                            <option value="{{ $modelo->mdl_id }}">{{ $modelo->mdl_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="flt-lna">Línea</label>
                    <select id="flt-lna" class="form-select js-select2-basic">
                        <option value="">Todas</option>
                        @foreach($opciones['lineas'] as $linea)
                            <option value="{{ $linea->lna_id }}">{{ $linea->lna_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="flt-ctg">Concepto</label>
                    <select id="flt-ctg" class="form-select js-select2-basic">
                        <option value="">Todos</option>
                        @foreach($opciones['categorias'] as $categoria)
                            <option value="{{ $categoria->ctg_id }}" data-lna="{{ $categoria->ctg_lna_id }}">{{ $categoria->ctg_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="flt-prd">Producto</label>
                    <select id="flt-prd" class="form-select"></select>
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="flt-buscar">Buscar</label>
                    <input id="flt-buscar" type="text" class="form-control" placeholder="Código, producto, marca, línea, concepto o color">
                </div>
                <div class="col-md-4">
                    <div class="em-filter-actions">
                        <button type="button" class="btn btn-primary" id="btn-filtrar">
                            <i class="ti tabler-filter me-1"></i>Aplicar filtros
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="btn-limpiar">
                            <i class="ti tabler-eraser me-1"></i>Limpiar
                        </button>
                        <div class="dropdown">
                            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Exportar">
                                <i class="ti tabler-download"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" id="btn-exportar-excel" href="#">
                                        <i class="ti tabler-file-spreadsheet me-2 text-success"></i>Exportar a Excel (CSV)
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" id="btn-exportar-pdf" href="#">
                                        <i class="ti tabler-file-type-pdf me-2 text-danger"></i>Exportar a PDF
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="em-legend">
                <span class="em-legend__item"><span class="em-size-pill em-size-pill--ok" style="pointer-events:none"><span class="em-size-pill__name">TL</span><span class="em-size-pill__sep">:</span><span class="em-size-pill__val">5</span></span>&nbsp;Existencia positiva</span>
                <span class="em-legend__item"><span class="em-size-pill em-size-pill--zero" style="pointer-events:none"><span class="em-size-pill__name">TL</span><span class="em-size-pill__sep">:</span><span class="em-size-pill__val">0</span></span>&nbsp;Existencia en cero</span>
                <span class="em-legend__item"><span class="em-size-pill em-size-pill--new" style="pointer-events:none"><span class="em-size-pill__name">TL</span><span class="em-size-pill__sep">:</span><span class="em-size-pill__val">0</span></span>&nbsp;SKU generado sin historial en almacenes</span>
                <span class="em-legend__item"><span class="em-size-pill em-size-pill--na" style="pointer-events:none"><span class="em-size-pill__name">TL</span><span class="em-size-pill__sep">:</span><span class="em-size-pill__val">N/D</span></span>&nbsp;Sin SKU generado</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table em-table" id="tbl-existencias-matriz">
                <thead>
                    <tr>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Línea</th>
                        <th>Concepto</th>
                        <th>Color</th>
                        <th style="min-width: 260px;">Tallas</th>
                        <th class="text-end">Total art.</th>
                        <th class="text-end">Precio</th>
                        <th class="text-end">Costo</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div id="em-context-menu" class="em-context-menu d-none" role="menu" aria-hidden="true">
    <button type="button" class="em-context-menu__button" id="em-action-kardex" role="menuitem">
        <i class="ti tabler-list-details me-2 text-primary"></i>Ver kardex completo
    </button>
</div>
@endsection

@push('vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('vendor-template/assets/vendor/libs/select2/select2.js') }}"></script>
    <script>
        (() => {
            const rutas = {
                data:           @json(route('operacion.inventario_base.existencias_matriz.data')),
                productos:      @json(route('operacion.inventario_base.productos.buscar')),
                exportarExcel:  @json(route('operacion.inventario_base.existencias_matriz.exportar.excel')),
                exportarPdf:    @json(route('operacion.inventario_base.existencias_matriz.exportar.pdf')),
                kardexDetalle:  @json(url('/operacion/inventario-base/kardex/__SKU__/detalle')),
            };

            let tabla = null;
            let selectedRowIdx = null;
            let pendingSelect = null;
            let contextTarget = null;
            function getDataRows() {
                return $('#tbl-existencias-matriz tbody tr').not('.dataTables_empty');
            }

            function selectRow(idx) {
                const rows = getDataRows();
                if (!rows.length) return;
                idx = Math.max(0, Math.min(idx, rows.length - 1));
                selectedRowIdx = idx;
                rows.removeClass('em-row-selected');
                $(rows[idx]).addClass('em-row-selected');
                rows[idx].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }

            function clearSelection() {
                getDataRows().removeClass('em-row-selected');
                selectedRowIdx = null;
            }

            function escapeHtml(text) {
                return $('<div>').text(text ?? '').html();
            }

            function renderMeta(title, subtitle = '') {
                return `
                    <div class="em-meta">
                        <span class="em-meta__title">${escapeHtml(title || '-')}</span>
                        ${subtitle ? `<span class="em-meta__sub">${escapeHtml(subtitle)}</span>` : ''}
                    </div>
                `;
            }

            function renderConcepto(_, __, row) {
                const producto = `${row.producto_codigo || '-'} · ${row.producto_nombre || '-'}`;
                return `
                    <div class="em-meta">
                        <span class="em-meta__title">${escapeHtml(row.concepto_nombre || '-')}</span>
                        <span class="em-meta__sub">${escapeHtml(producto)}</span>
                    </div>
                `;
            }

            function renderTallas(tallas) {
                if (!Array.isArray(tallas) || !tallas.length) {
                    return '<span class="text-body-secondary">Sin tallas configuradas.</span>';
                }

                return '<div class="em-size-strip">' + tallas.map((item) => {
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

                    return `<span class="em-size-pill em-size-pill--${mod}" title="${escapeHtml(title)}" data-psk-id="${escapeHtml(pskId)}" data-talla="${escapeHtml(item.talla || 'Base')}" data-talla-key="${escapeHtml(tallaKey)}" data-color-vat-id="${escapeHtml(colorVatId)}"><span class="em-size-pill__name">${escapeHtml(item.talla || 'Base')}</span><span class="em-size-pill__sep">:</span><span class="em-size-pill__val">${escapeHtml(valor)}</span></span>`;
                }).join('') + '</div>';
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

                if (actual && $concepto.find(`option[value="${actual}"]:not([hidden])`).length === 0) {
                    $concepto.val('');
                }

                $concepto.trigger('change.select2');
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

                if (actual && $almacen.find(`option[value="${actual}"]:not([hidden])`).length === 0) {
                    $almacen.val('');
                }

                $almacen.trigger('change.select2');
            }

            function initSelect2() {
                $('.js-select2-basic').select2({
                    width: '100%',
                    allowClear: false,
                });

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

            function buildTabla() {
                tabla = $('#tbl-existencias-matriz').DataTable({
                    processing: true,
                    serverSide: true,
                    pageLength: 10,
                    searching: false,
                    lengthMenu: [10, 25, 50],
                    order: [[0, 'asc'], [1, 'asc'], [4, 'asc']],
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
                        { data: 'marca_nombre', render: (v) => renderMeta(v || '-') },
                        { data: 'modelo_nombre', render: (v) => renderMeta(v || '-') },
                        { data: 'linea_nombre', render: (v) => renderMeta(v || '-') },
                        { data: null, render: renderConcepto },
                        { data: 'color_nombre', render: (v, _, row) => renderMeta(v || 'Sin color', `${row.sku_total || 0} SKU(s)`) },
                        { data: 'tallas', orderable: false, searchable: false, render: renderTallas },
                        { data: 'total_articulos', className: 'em-num', render: (v) => formatMoney(v) },
                        { data: 'precio_unitario', className: 'em-num', render: (v) => '$ ' + formatMoney(v) },
                        { data: 'costo_unitario', className: 'em-num', render: (v) => '$ ' + formatMoney(v) },
                        { data: 'total_importe_precio', className: 'em-num', render: (v) => '$ ' + formatMoney(v) },
                    ]
                });
            }

            function recargarTabla(reset = false) {
                if (!tabla) {
                    buildTabla();
                    return;
                }

                tabla.ajax.reload(null, reset);
            }

            function limpiarFiltros() {
                $('#flt-mrc').val('').trigger('change');
                $('#flt-mdl').val('').trigger('change');
                $('#flt-lna').val('').trigger('change');
                $('#flt-ctg').val('').trigger('change');
                $('#flt-prd').val(null).trigger('change');
                $('#flt-buscar').val('');
                $('#flt-scl').val(@json((string) ($defaultSucursalId ?? ''))).trigger('change');
                $('#flt-alm').val('').trigger('change');
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
                return qs ? `${base}?${qs}` : base;
            }

            function hideContextMenu() {
                contextTarget = null;
                $('#em-context-menu').addClass('d-none').attr('aria-hidden', 'true');
            }

            function showContextMenu(event, target) {
                const pskId = Number(target?.dataset?.pskId || 0);
                contextTarget = {
                    pskId,
                    talla: String(target.dataset.talla || 'Base'),
                };

                const $menu = $('#em-context-menu');
                $('#em-action-kardex').prop('disabled', !pskId);
                $menu.removeClass('d-none').attr('aria-hidden', 'false');

                const menuWidth = $menu.outerWidth() || 220;
                const menuHeight = $menu.outerHeight() || 48;
                const left = Math.min(event.clientX, window.innerWidth - menuWidth - 12);
                const top = Math.min(event.clientY, window.innerHeight - menuHeight - 12);
                $menu.css({ left: `${Math.max(8, left)}px`, top: `${Math.max(8, top)}px` });
            }

            function aplicarFiltrosDesdeQuery() {
                const params = new URLSearchParams(window.location.search);
                const setIfPresent = function (id, key) {
                    const value = params.get(key);
                    if (value) $(id).val(value).trigger('change');
                };

                setIfPresent('#flt-mrc', 'prd_mrc_id');
                setIfPresent('#flt-mdl', 'prd_mdl_id');
                setIfPresent('#flt-lna', 'prd_lna_id');
                setIfPresent('#flt-ctg', 'prd_ctg_id');
                setIfPresent('#flt-scl', 'min_scl_id');
                setIfPresent('#flt-alm', 'min_alm_id');

                const buscar = params.get('buscar');
                if (buscar) {
                    $('#flt-buscar').val(buscar);
                }

                const prdId = params.get('prd_id');
                const prdText = params.get('prd_text');
                if (prdId) {
                    const option = new Option(prdText || (`Producto #${prdId}`), prdId, true, true);
                    $('#flt-prd').append(option).trigger('change');
                }
            }

            initSelect2();
            aplicarFiltroConceptosPorLinea();
            syncAlmacenesPorSucursal();
            aplicarFiltrosDesdeQuery();
            buildTabla();

            // Selección por click
            $('#tbl-existencias-matriz tbody').on('click', 'tr', function () {
                const idx = getDataRows().index(this);
                if (idx >= 0) selectRow(idx);
            });

            // Re-selección tras paginación / limpieza tras filtro o sort
            $('#tbl-existencias-matriz').on('draw.dt', function () {
                if (pendingSelect === 'first') {
                    pendingSelect = null;
                    selectRow(0);
                } else if (pendingSelect === 'last') {
                    pendingSelect = null;
                    selectRow(getDataRows().length - 1);
                } else {
                    clearSelection();
                }
            });

            // Navegación por teclado
            $(document).on('keydown.em-nav', function (e) {
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

            $('#flt-scl').on('change', function () {
                syncAlmacenesPorSucursal();
                $('#flt-prd').val(null).trigger('change');
            });

            $('#flt-lna').on('change', function () {
                aplicarFiltroConceptosPorLinea();
                $('#flt-prd').val(null).trigger('change');
            });

            $('#flt-alm, #flt-mrc, #flt-mdl, #flt-ctg').on('change', function () {
                $('#flt-prd').val(null).trigger('change');
            });

            $('#btn-filtrar').on('click', function () {
                recargarTabla(true);
            });

            $('#btn-limpiar').on('click', function () {
                limpiarFiltros();
            });

            function buildExportUrl(base) {
                const params = new URLSearchParams();
                const mrc = $('#flt-mrc').val();
                const mdl = $('#flt-mdl').val();
                const lna = $('#flt-lna').val();
                const ctg = $('#flt-ctg').val();
                const scl = $('#flt-scl').val();
                const alm = $('#flt-alm').val();
                const prd = $('#flt-prd').val();
                const buscar = $('#flt-buscar').val().trim();
                if (scl)    params.set('min_scl_id', scl);
                if (alm)    params.set('min_alm_id', alm);
                if (mrc)    params.set('prd_mrc_id', mrc);
                if (mdl)    params.set('prd_mdl_id', mdl);
                if (lna)    params.set('prd_lna_id', lna);
                if (ctg)    params.set('prd_ctg_id', ctg);
                if (prd)    params.set('prd_id', prd);
                if (buscar) params.set('buscar', buscar);
                const qs = params.toString();
                return qs ? base + '?' + qs : base;
            }

            $('#btn-exportar-excel').on('click', function (e) {
                e.preventDefault();
                window.location.href = buildExportUrl(rutas.exportarExcel);
            });

            $('#btn-exportar-pdf').on('click', function (e) {
                e.preventDefault();
                window.open(buildExportUrl(rutas.exportarPdf), '_blank');
            });

            $('#tbl-existencias-matriz tbody').on('contextmenu', '.em-size-pill[data-psk-id]', function (event) {
                event.preventDefault();
                event.stopPropagation();
                showContextMenu(event, this);
            });

            $('#em-action-kardex').on('click', function () {
                if (!contextTarget?.pskId) return;
                const pskId = contextTarget.pskId;
                hideContextMenu();
                window.location.href = buildKardexDetalleUrl(pskId);
            });

            $(document).on('click', function (event) {
                if ($(event.target).closest('#em-context-menu,.em-size-pill[data-psk-id]').length) return;
                hideContextMenu();
            });

            $(document).on('keydown', function (event) {
                if (event.key === 'Escape') {
                    hideContextMenu();
                }
            });

            $('#flt-buscar').on('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    recargarTabla(true);
                }
            });
        })();
    </script>
@endpush
