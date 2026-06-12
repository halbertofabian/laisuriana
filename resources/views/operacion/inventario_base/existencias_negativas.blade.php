@extends('layouts.app')

@section('title', 'Existencias Negativas')

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

        .em-size-pill--na {
            border-style: dashed;
            opacity: .6;
        }
        .em-size-pill--na .em-size-pill__val { color: var(--ls-text-muted); }

        .em-size-pill--neg {
            border-color: rgba(220, 53, 69, .35);
            background: rgba(220, 53, 69, .07);
        }
        .em-size-pill--neg .em-size-pill__name { color: #b02a37; }
        .em-size-pill--neg .em-size-pill__val { color: #dc3545; font-weight: 900; }

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
    title="Existencias Negativas"
    subtitle="Productos con al menos una talla en existencia menor a cero. Las tallas negativas se resaltan en rojo."
/>

<div class="card app-tabs-shell mb-4">
    <div class="app-tabs-shell__body">
        <div class="em-filter-bar">
            <div class="row g-3">
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
                    </div>
                </div>
            </div>

            <div class="em-legend">
                <span class="em-legend__item"><span class="em-size-pill em-size-pill--neg" style="pointer-events:none"><span class="em-size-pill__name">TL</span><span class="em-size-pill__sep">:</span><span class="em-size-pill__val">-2</span></span>&nbsp;Existencia negativa</span>
                <span class="em-legend__item"><span class="em-size-pill em-size-pill--ok" style="pointer-events:none"><span class="em-size-pill__name">TL</span><span class="em-size-pill__sep">:</span><span class="em-size-pill__val">5</span></span>&nbsp;Existencia positiva</span>
                <span class="em-legend__item"><span class="em-size-pill em-size-pill--zero" style="pointer-events:none"><span class="em-size-pill__name">TL</span><span class="em-size-pill__sep">:</span><span class="em-size-pill__val">0</span></span>&nbsp;Existencia en cero</span>
                <span class="em-legend__item"><span class="em-size-pill em-size-pill--na" style="pointer-events:none"><span class="em-size-pill__name">TL</span><span class="em-size-pill__sep">:</span><span class="em-size-pill__val">N/D</span></span>&nbsp;Sin SKU generado</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table em-table" id="tbl-existencias-negativas">
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
@endsection

@push('vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('vendor-template/assets/vendor/libs/select2/select2.js') }}"></script>
    <script>
        (() => {
            const rutas = {
                data: @json(route('operacion.inventario_base.existencias_negativas.data')),
                productos: @json(route('operacion.inventario_base.productos.buscar')),
            };

            let tabla = null;
            let selectedRowIdx = null;
            let pendingSelect = null;

            function getDataRows() {
                return $('#tbl-existencias-negativas tbody tr').not('.dataTables_empty');
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
                    const mod = estado === 'negativo'
                        ? 'neg'
                        : (estado === 'con_existencia' ? 'ok' : (estado === 'cero' ? 'zero' : 'na'));
                    const valor = item.existencia === null ? 'N/D' : Number(item.existencia).toFixed(2).replace(/\.00$/, '');

                    return `<span class="em-size-pill em-size-pill--${mod}"><span class="em-size-pill__name">${escapeHtml(item.talla || 'Base')}</span><span class="em-size-pill__sep">:</span><span class="em-size-pill__val">${escapeHtml(valor)}</span></span>`;
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
                tabla = $('#tbl-existencias-negativas').DataTable({
                    processing: true,
                    serverSide: true,
                    pageLength: 10,
                    searching: false,
                    lengthMenu: [10, 25, 50],
                    order: [[0, 'asc'], [1, 'asc'], [4, 'asc']],
                    ajax: {
                        url: rutas.data,
                        data: function (d) {
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
                aplicarFiltroConceptosPorLinea();
                recargarTabla(true);
            }

            initSelect2();
            aplicarFiltroConceptosPorLinea();
            buildTabla();

            // Selección por click
            $('#tbl-existencias-negativas tbody').on('click', 'tr', function () {
                const idx = getDataRows().index(this);
                if (idx >= 0) selectRow(idx);
            });

            // Re-selección tras paginación / limpieza tras filtro o sort
            $('#tbl-existencias-negativas').on('draw.dt', function () {
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

            $('#flt-lna').on('change', function () {
                aplicarFiltroConceptosPorLinea();
                $('#flt-prd').val(null).trigger('change');
            });

            $('#flt-mrc, #flt-mdl, #flt-ctg').on('change', function () {
                $('#flt-prd').val(null).trigger('change');
            });

            $('#btn-filtrar').on('click', function () {
                recargarTabla(true);
            });

            $('#btn-limpiar').on('click', function () {
                limpiarFiltros();
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
