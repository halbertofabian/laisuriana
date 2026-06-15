@extends('layouts.desktop')

@section('title', 'Etiquetado')

@push('desktop-vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
@endpush

@push('desktop-styles')
    <style>
        .desktop-etq-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            padding: 8px 12px;
            border-bottom: 1px solid var(--stroke);
            background: var(--surface-alt);
        }
        .desktop-etq-toolbar__group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .desktop-etq-number {
            width: 124px;
            height: 38px;
            padding: 0 12px;
            border: 1px solid var(--stroke);
            border-radius: var(--r-md);
            background: var(--surface);
            color: var(--text);
            font: inherit;
            font-size: .84rem;
        }
        .desktop-etq-number:focus {
            outline: none;
            border-color: var(--brand);
            box-shadow: 0 0 0 1px var(--brand);
        }
        .desktop-etq-panel {
            display: none;
            padding: 12px;
            border-bottom: 1px solid var(--stroke);
            background: rgba(255,255,255,.72);
        }
        .desktop-etq-panel.is-open {
            display: block;
        }
        .desktop-etq-panel__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        .desktop-etq-panel__title {
            margin: 0;
            font-size: .84rem;
            font-weight: 600;
        }
        .desktop-etq-panel__copy {
            margin: 4px 0 0;
            color: var(--text-2);
            font-size: .75rem;
            line-height: 1.5;
        }
        .desktop-etq-actions {
            display: inline-flex;
            justify-content: flex-end;
            width: 100%;
        }
        .desktop-etq-generate {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 112px;
            height: 30px;
            padding: 0 10px;
            border: 1px solid var(--brand-soft-2);
            border-radius: var(--r-md);
            background: var(--brand-soft);
            color: var(--brand);
            font: inherit;
            font-size: .79rem;
            font-weight: 600;
            cursor: pointer;
            transition: background .12s ease, border-color .12s ease, color .12s ease;
        }
        .desktop-etq-generate:hover {
            background: #fff;
            border-color: var(--brand);
        }
        @media (max-width: 860px) {
            .desktop-etq-toolbar__group {
                width: 100%;
            }
            .desktop-etq-number {
                width: 100%;
            }
        }
    </style>
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        @php($activeSubmenu = 'etiquetado')
        @include('desktop.operacion.catalogo_comercial._subnav')
        <span class="desktop-toolbar__divider"></span>
        <button type="button" class="desktop-btn desktop-btn--ghost" id="btn-recargar-etiquetado">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
            Actualizar
        </button>
    </div>
    <div class="desktop-toolbar__group">
        <select class="desktop-toolbar__select" id="etiquetado-length">
            <option value="10">10 por página</option>
            <option value="25">25 por página</option>
            <option value="50">50 por página</option>
        </select>
        <input type="search" id="etiquetado-search" class="desktop-toolbar__search" placeholder="Buscar SKU, producto o nombre etiqueta">
    </div>
@endsection

@section('content')
    <section class="desktop-pane">
        <div class="desktop-etq-toolbar">
            <div class="desktop-etq-toolbar__group">
                <select class="desktop-toolbar__select" id="flt-etq-producto">
                    <option value="">Todos los productos</option>
                    @foreach($opciones['productos'] as $producto)
                        <option value="{{ $producto->prd_id }}">{{ $producto->prd_codigo }} - {{ $producto->prd_nombre }}</option>
                    @endforeach
                </select>
                <input type="number" class="desktop-etq-number" id="etq-copias" min="1" max="50" value="1" placeholder="Copias">
                <button type="button" class="desktop-btn desktop-btn--default" id="btn-toggle-etq-settings">
                    Ajustes Zebra
                </button>
                <button type="button" class="desktop-btn desktop-btn--ghost" id="btn-restaurar-zebra">
                    Restaurar valores
                </button>
            </div>
            <div class="desktop-etq-toolbar__group" style="font-size:.75rem; color:var(--text-2);">
                Genera etiquetas PDF Zebra con código de barras, nombre y precio.
            </div>
        </div>

        <div class="desktop-etq-panel" id="desktop-etq-panel">
            <div class="desktop-etq-panel__head">
                <div>
                    <h3 class="desktop-etq-panel__title">Configuración Zebra (preparación sin impresora)</h3>
                    <p class="desktop-etq-panel__copy">Imprime al 100% de escala y sin ajustar a página. Activa el ajuste manual solo si necesitas calibrar medidas físicas o barcode.</p>
                </div>
                <label class="desktop-check">
                    <input type="checkbox" id="etq-usar-config-manual" value="1">
                    <span class="desktop-check__box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    </span>
                    <span class="desktop-check__label">Ajustar manualmente</span>
                </label>
            </div>

            <div class="desktop-form-grid" id="etq-config-manual-wrap" hidden>
                <div class="desktop-field">
                    <label>Ancho (mm)</label>
                    <input type="number" step="0.1" min="20" max="120" id="etq-width-mm">
                </div>
                <div class="desktop-field">
                    <label>Alto (mm)</label>
                    <input type="number" step="0.1" min="10" max="120" id="etq-height-mm">
                </div>
                <div class="desktop-field">
                    <label>Margen izq (mm)</label>
                    <input type="number" step="0.1" min="0" max="10" id="etq-margin-left-mm">
                </div>
                <div class="desktop-field">
                    <label>Margen der (mm)</label>
                    <input type="number" step="0.1" min="0" max="10" id="etq-margin-right-mm">
                </div>
                <div class="desktop-field">
                    <label>Margen sup (mm)</label>
                    <input type="number" step="0.1" min="0" max="10" id="etq-margin-top-mm">
                </div>
                <div class="desktop-field">
                    <label>Margen inf (mm)</label>
                    <input type="number" step="0.1" min="0" max="10" id="etq-margin-bottom-mm">
                </div>
                <div class="desktop-field">
                    <label>Alto barcode (mm)</label>
                    <input type="number" step="0.1" min="4" max="25" id="etq-barcode-height-mm">
                </div>
                <div class="desktop-field">
                    <label>Grosor barra (xres)</label>
                    <input type="number" step="0.01" min="0.2" max="0.8" id="etq-barcode-xres">
                </div>
            </div>
        </div>

        <div class="desktop-list-wrap">
            <table id="desktop-etiquetado-table" class="desktop-list">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>SKU</th>
                        <th>Nombre etiqueta</th>
                        <th>Precio</th>
                        <th style="width:140px; text-align:right;">Acción</th>
                    </tr>
                </thead>
            </table>
        </div>

        <div class="desktop-list-foot">
            <div id="desktop-etiquetado-info"></div>
            <div id="desktop-etiquetado-pagination" class="desktop-pager"></div>
        </div>
    </section>
@endsection

@push('desktop-vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endpush

@push('desktop-scripts')
    <script>
        (function () {
            const $table = $('#desktop-etiquetado-table');
            const zebraDefaults = @json($zebraDefaults);
            let etiquetasTable = null;

            function showGlobalFeedback(type, message) {
                const success = document.getElementById('desktop-feedback-success');
                const error = document.getElementById('desktop-feedback-error');
                const target = type === 'error' ? error : success;
                const other = type === 'error' ? success : error;

                if (other) other.classList.remove('is-visible');
                if (!target) return;

                target.textContent = message;
                target.classList.add('is-visible');
                window.clearTimeout(showGlobalFeedback._timer);
                showGlobalFeedback._timer = window.setTimeout(function () {
                    target.classList.remove('is-visible');
                }, 3200);
            }

            function escapeHtml(value) {
                return String(value || '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#39;');
            }

            function toggleSettingsPanel(forceState) {
                const panel = document.getElementById('desktop-etq-panel');
                const shouldOpen = typeof forceState === 'boolean' ? forceState : !panel.classList.contains('is-open');
                panel.classList.toggle('is-open', shouldOpen);
                $('#btn-toggle-etq-settings').toggleClass('desktop-btn--active', shouldOpen);
            }

            function cargarConfiguracionZebraBase() {
                $('#etq-width-mm').val(zebraDefaults.width_mm ?? 50);
                $('#etq-height-mm').val(zebraDefaults.height_mm ?? 30);
                $('#etq-margin-left-mm').val(zebraDefaults.margin_left_mm ?? 2);
                $('#etq-margin-right-mm').val(zebraDefaults.margin_right_mm ?? 2);
                $('#etq-margin-top-mm').val(zebraDefaults.margin_top_mm ?? 1.8);
                $('#etq-margin-bottom-mm').val(zebraDefaults.margin_bottom_mm ?? 1.8);
                $('#etq-barcode-height-mm').val(zebraDefaults.barcode_height_mm ?? 9.5);
                $('#etq-barcode-xres').val(zebraDefaults.barcode_xres ?? 0.33);
            }

            function syncManualConfigVisibility() {
                $('#etq-config-manual-wrap').prop('hidden', !$('#etq-usar-config-manual').is(':checked'));
            }

            function obtenerConfiguracionZebraManual() {
                if (!$('#etq-usar-config-manual').is(':checked')) {
                    return { usar_configuracion_manual: 0 };
                }

                const campos = [
                    { key: 'width_mm', selector: '#etq-width-mm', min: 20, max: 120, label: 'Ancho' },
                    { key: 'height_mm', selector: '#etq-height-mm', min: 10, max: 120, label: 'Alto' },
                    { key: 'margin_left_mm', selector: '#etq-margin-left-mm', min: 0, max: 10, label: 'Margen izquierdo' },
                    { key: 'margin_right_mm', selector: '#etq-margin-right-mm', min: 0, max: 10, label: 'Margen derecho' },
                    { key: 'margin_top_mm', selector: '#etq-margin-top-mm', min: 0, max: 10, label: 'Margen superior' },
                    { key: 'margin_bottom_mm', selector: '#etq-margin-bottom-mm', min: 0, max: 10, label: 'Margen inferior' },
                    { key: 'barcode_height_mm', selector: '#etq-barcode-height-mm', min: 4, max: 25, label: 'Alto de barcode' },
                    { key: 'barcode_xres', selector: '#etq-barcode-xres', min: 0.2, max: 0.8, label: 'Grosor de barra' },
                ];

                const config = { usar_configuracion_manual: 1 };

                for (const campo of campos) {
                    const valor = Number($(campo.selector).val());
                    if (!Number.isFinite(valor) || valor < campo.min || valor > campo.max) {
                        showGlobalFeedback('error', campo.label + ' fuera de rango (' + campo.min + ' - ' + campo.max + ').');
                        return null;
                    }
                    config[campo.key] = valor;
                }

                return config;
            }

            function renderCustomFooter() {
                const tableInstance = etiquetasTable || ($.fn.DataTable.isDataTable($table) ? $table.DataTable() : null);
                if (!tableInstance) return;

                const info = tableInstance.page.info();
                const total = info.recordsDisplay;

                if (!total) {
                    $('#desktop-etiquetado-info').text('Mostrando 0 etiquetas');
                    $('#desktop-etiquetado-pagination').empty();
                    return;
                }

                $('#desktop-etiquetado-info').text(
                    'Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + total + ' etiquetas'
                );

                const buttons = [];
                const current = info.page;
                const totalPages = info.pages;

                buttons.push({ label: '‹', page: 'previous', disabled: current === 0 });
                for (let i = 0; i < totalPages; i += 1) {
                    buttons.push({ label: String(i + 1), page: i, active: i === current });
                }
                buttons.push({ label: '›', page: 'next', disabled: current >= totalPages - 1 });

                const html = buttons.map(function (button) {
                    const classes = [
                        'desktop-pager__btn',
                        button.active ? 'is-active' : '',
                        button.disabled ? 'is-disabled' : ''
                    ].filter(Boolean).join(' ');

                    return '<button type="button" class="' + classes + '" data-page="' + button.page + '"' +
                        (button.disabled ? ' disabled' : '') + '>' + button.label + '</button>';
                }).join('');

                $('#desktop-etiquetado-pagination').html(html);
            }

            function buildTable(rows) {
                if ($.fn.DataTable.isDataTable($table)) {
                    $table.DataTable().clear().destroy();
                }

                etiquetasTable = $table.DataTable({
                    data: rows || [],
                    order: [[1, 'asc']],
                    processing: true,
                    deferRender: true,
                    responsive: false,
                    autoWidth: false,
                    pageLength: Number($('#etiquetado-length').val() || 10),
                    lengthChange: false,
                    searching: true,
                    dom: 'rt',
                    language: {
                        processing: 'Cargando...',
                        emptyTable: 'No hay SKU disponibles para etiquetado.',
                        zeroRecords: 'No se encontraron coincidencias'
                    },
                    columns: [
                        {
                            data: null,
                            render: function (row) {
                                return '<span class="desktop-list__name">' + escapeHtml(row.producto || 'Sin producto') + '</span>' +
                                    '<span class="desktop-list__meta">' + escapeHtml(row.producto_codigo || 'Sin código base') + '</span>';
                            }
                        },
                        {
                            data: 'psk_codigo',
                            render: function (value) {
                                return '<span style="font-weight:600;">' + escapeHtml(value || '-') + '</span>';
                            }
                        },
                        {
                            data: 'psk_nombre',
                            render: function (value) {
                                return value
                                    ? '<span class="desktop-list__name">' + escapeHtml(value) + '</span>'
                                    : '<span class="desktop-list__meta">Usa nombre del producto base</span>';
                            }
                        },
                        {
                            data: 'psk_precio',
                            render: function (value) {
                                return '<span class="desktop-list__name">$' + escapeHtml(Number(value || 0).toFixed(2)) + '</span>';
                            }
                        },
                        {
                            data: null,
                            className: 'text-end',
                            orderable: false,
                            searchable: false,
                            render: function (row) {
                                return '<div class="desktop-etq-actions"><button type="button" class="desktop-etq-generate" data-generate-etiqueta data-id="' + row.psk_id + '">Generar PDF</button></div>';
                            }
                        }
                    ],
                    initComplete: renderCustomFooter,
                    drawCallback: renderCustomFooter
                });
            }

            function recargarEtiquetado() {
                $.getJSON('{{ route('desktop.operacion.catalogo_comercial.skus.data') }}', {
                    psk_prd_id: $('#flt-etq-producto').val()
                }).done(function (resp) {
                    buildTable(resp.data || []);
                }).fail(function (xhr) {
                    buildTable([]);
                    showGlobalFeedback('error', xhr.responseJSON?.message || 'No fue posible cargar el etiquetado.');
                });
            }

            $('#btn-toggle-etq-settings').on('click', function () {
                toggleSettingsPanel();
            });

            $('#btn-restaurar-zebra').on('click', function () {
                cargarConfiguracionZebraBase();
                showGlobalFeedback('success', 'Valores Zebra restaurados.');
            });

            $('#etq-usar-config-manual').on('change', syncManualConfigVisibility);
            $('#flt-etq-producto').on('change', recargarEtiquetado);
            $('#btn-recargar-etiquetado').on('click', recargarEtiquetado);
            $('#etiquetado-search').on('input', function () {
                if (!etiquetasTable) return;
                etiquetasTable.search(this.value).draw();
            });
            $('#etiquetado-length').on('change', function () {
                if (!etiquetasTable) return;
                etiquetasTable.page.len(Number(this.value || 10)).draw();
            });
            $('#desktop-etiquetado-pagination').on('click', '[data-page]', function () {
                if (!etiquetasTable) return;

                const page = $(this).data('page');
                if (page === 'previous') {
                    etiquetasTable.page('previous').draw('page');
                    return;
                }
                if (page === 'next') {
                    etiquetasTable.page('next').draw('page');
                    return;
                }

                etiquetasTable.page(Number(page)).draw('page');
            });

            $table.on('click', '[data-generate-etiqueta]', function () {
                const skuId = $(this).data('id');
                const copias = Number($('#etq-copias').val() || 1);

                if (!Number.isInteger(copias) || copias < 1 || copias > 50) {
                    showGlobalFeedback('error', 'La cantidad de copias debe estar entre 1 y 50.');
                    return;
                }

                const configManual = obtenerConfiguracionZebraManual();
                if (configManual === null) {
                    return;
                }

                const params = new URLSearchParams({
                    formato: 'zebra_50x30',
                    copias: String(copias),
                });

                Object.entries(configManual).forEach(function (entry) {
                    params.set(entry[0], String(entry[1]));
                });

                const url = '{{ url('/desktop/operacion/catalogo-comercial/skus') }}/' + skuId + '/etiqueta-pdf?' + params.toString();
                window.open(url, '_blank', 'noopener');
            });

            cargarConfiguracionZebraBase();
            syncManualConfigVisibility();
            recargarEtiquetado();
        })();
    </script>
@endpush
