@extends('layouts.desktop')

@section('title', 'Reportes')

@push('desktop-vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
@endpush

@push('desktop-styles')
    <style>
        .desktop-reportes-pane.is-hidden { display: none; }
        .desktop-reportes-subnav.is-hidden { display: none; }
        .desktop-reportes-range.is-hidden { display: none; }
        .desktop-reportes-toolbar-panel.is-hidden { display: none; }
        .desktop-reportes-toolbar-secondary {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .desktop-reportes-board {
            display: flex;
            flex-direction: column;
            gap: 14px;
            padding: 16px;
            min-height: 100%;
            background: linear-gradient(180deg, #fbfcfe 0%, #f5f7fa 100%);
        }
        .desktop-reportes-hero {
            display: grid;
            grid-template-columns: minmax(0, 1.3fr) minmax(280px, .7fr);
            gap: 14px;
        }
        .desktop-reportes-card {
            border: 1px solid var(--stroke);
            border-radius: var(--r-lg);
            background: var(--surface);
            box-shadow: var(--shadow-2);
        }
        .desktop-reportes-card__body { padding: 16px; }
        .desktop-reportes-kicker {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }
        .desktop-reportes-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 8px;
            border-radius: 999px;
            background: var(--brand-soft);
            color: var(--brand);
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .02em;
        }
        .desktop-reportes-title {
            margin: 0;
            font-size: 1.22rem;
            font-weight: 700;
            letter-spacing: -.02em;
        }
        .desktop-reportes-copy {
            margin: 8px 0 0;
            max-width: 640px;
            color: var(--text-2);
            font-size: .84rem;
            line-height: 1.55;
        }
        .desktop-reportes-kpis {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-top: 14px;
        }
        .desktop-reportes-kpi {
            padding: 12px;
            border: 1px solid var(--divider);
            border-radius: var(--r-md);
            background: var(--surface-alt);
        }
        .desktop-reportes-kpi__label {
            color: var(--text-3);
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .desktop-reportes-kpi__value {
            margin-top: 6px;
            font-size: 1rem;
            font-weight: 700;
            color: var(--text);
        }
        .desktop-reportes-kpi__meta {
            margin-top: 4px;
            color: var(--text-2);
            font-size: .75rem;
        }
        .desktop-reportes-rail {
            display: flex;
            flex-direction: column;
            gap: 10px;
            height: 100%;
        }
        .desktop-reportes-rail__item {
            padding: 12px 14px;
            border: 1px solid var(--divider);
            border-radius: var(--r-md);
            background: var(--surface-alt);
        }
        .desktop-reportes-rail__item strong {
            display: block;
            margin-bottom: 4px;
            font-size: .8rem;
        }
        .desktop-reportes-rail__item span {
            color: var(--text-2);
            font-size: .76rem;
            line-height: 1.45;
        }
        .desktop-reportes-block {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 14px;
        }
        .desktop-reportes-section__head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            border-bottom: 1px solid var(--divider);
        }
        .desktop-reportes-section__title {
            margin: 0;
            font-size: .95rem;
            font-weight: 700;
        }
        .desktop-reportes-section__sub {
            margin-top: 4px;
            color: var(--text-2);
            font-size: .77rem;
        }
        .desktop-reportes-table {
            width: 100%;
            border-collapse: collapse;
        }
        .desktop-reportes-table th,
        .desktop-reportes-table td {
            padding: 11px 16px;
            border-bottom: 1px solid var(--divider);
            text-align: left;
        }
        .desktop-reportes-table th {
            color: var(--text-3);
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .desktop-reportes-table td {
            font-size: .82rem;
            color: var(--text);
        }
        .desktop-reportes-table tr:last-child td { border-bottom: 0; }
        .desktop-reportes-muted {
            color: var(--text-2);
            font-size: .76rem;
        }
        .desktop-reportes-aside {
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .desktop-reportes-note {
            padding: 12px 13px;
            border-radius: var(--r-md);
            background: var(--surface-alt);
            border: 1px solid var(--divider);
        }
        .desktop-reportes-note strong {
            display: block;
            margin-bottom: 4px;
            font-size: .8rem;
        }
        .desktop-reportes-note p {
            margin: 0;
            color: var(--text-2);
            font-size: .76rem;
            line-height: 1.5;
        }
        .desktop-reportes-kicker__meta {
            color: var(--text-2);
            font-size: .76rem;
        }
        .desktop-reportes-kicker__meta strong {
            color: var(--text);
        }
        .desktop-reportes-search {
            width: 210px;
        }
        .desktop-reportes-cell--num {
            display: block;
            text-align: right;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }
        .desktop-reportes-cell--money {
            display: block;
            text-align: right;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
            font-weight: 700;
        }
        .desktop-reportes-cell--date {
            display: block;
            text-align: right;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }
        .desktop-reportes-cell--meta-right {
            display: block;
            text-align: right;
        }
        #desktop-reportes-vendedores-table thead th:nth-child(11) {
            text-align: right !important;
        }
        @media (max-width: 980px) {
            .desktop-reportes-hero,
            .desktop-reportes-block {
                grid-template-columns: 1fr;
            }
            .desktop-reportes-kpis {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        <div class="desktop-pivot" role="tablist" aria-label="Módulos de reportes">
            <button type="button" class="desktop-btn desktop-btn--active" data-reportes-module="ventas" aria-current="page">Ventas</button>
            <button type="button" class="desktop-btn" data-reportes-module="inventario">Inventario</button>
        </div>

        <span class="desktop-toolbar__divider"></span>

        <div class="desktop-toolbar__group desktop-reportes-subnav" data-reportes-subnav="ventas">
            <button type="button" class="desktop-btn desktop-btn--primary" data-reportes-view="ventas.vendedores">Vendedores</button>
            <button type="button" class="desktop-btn desktop-btn--ghost" data-reportes-view="ventas.sesiones">Sesiones</button>
            <button type="button" class="desktop-btn desktop-btn--ghost" data-reportes-view="ventas.fecha">Fecha</button>
        </div>

        <div class="desktop-toolbar__group desktop-reportes-subnav is-hidden" data-reportes-subnav="inventario">
            <button type="button" class="desktop-btn desktop-btn--primary" data-reportes-view="inventario.existencias">Existencias</button>
            <button type="button" class="desktop-btn desktop-btn--ghost" data-reportes-view="inventario.movimientos">Movimientos</button>
            <button type="button" class="desktop-btn desktop-btn--ghost" data-reportes-view="inventario.kardex">Kardex</button>
        </div>
    </div>
    <div class="desktop-toolbar__group desktop-reportes-toolbar-panel" data-reportes-toolbar-view="ventas.vendedores">
        <div class="desktop-reportes-toolbar-secondary">
            <select class="desktop-toolbar__select" id="reportes-tiempo" style="width: 176px;">
                <option value="hoy">Hoy</option>
                <option value="ayer">Ayer</option>
                <option value="antier">Antier</option>
                <option value="semana" selected>Semana en curso</option>
                <option value="mes">Mes en curso</option>
                <option value="rango">Rango</option>
            </select>
            <select class="desktop-toolbar__select" id="reportes-tipo" style="width: 172px;">
                <option value="todos" selected>Todos los tipos</option>
                <option value="venta">Venta</option>
                <option value="cambio_devolucion">Cambio devolución</option>
                <option value="cambio_nuevo">Cambio nuevo</option>
            </select>
            <div class="desktop-reportes-range is-hidden" id="reportes-range-wrap">
                <input type="date" id="reportes-desde" class="desktop-toolbar__search" style="width: 145px;">
                <input type="date" id="reportes-hasta" class="desktop-toolbar__search" style="width: 145px;">
            </div>
            <select class="desktop-toolbar__select" id="reportes-vendedor" style="width: 190px;">
                <option value="">Todos los vendedores</option>
                @foreach($opciones['vendedores'] as $vendedor)
                    <option value="{{ $vendedor->usr_id }}">{{ $vendedor->usr_nombre }}</option>
                @endforeach
            </select>
            <select class="desktop-toolbar__select" id="reportes-linea" style="width: 190px;">
                <option value="">Todas las líneas</option>
                @foreach($opciones['lineas'] as $linea)
                    <option value="{{ $linea->lna_id }}">{{ $linea->lna_nombre }}</option>
                @endforeach
            </select>
            <button type="button" class="desktop-btn desktop-btn--ghost" id="btn-recargar-reporte-vendedores">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
                Actualizar
            </button>
            <div class="desktop-neg-export">
                <button type="button" class="desktop-btn desktop-btn--primary" id="btn-exportar-reporte-menu" data-overflow aria-haspopup="true" aria-expanded="false">
                    Exportar
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div class="desktop-menu" role="menu" aria-label="Exportar reporte">
                    <button type="button" class="desktop-menu__item" id="btn-exportar-reporte-vendedores">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="m9 13 6 6"/><path d="m15 13-6 6"/></svg>
                        Exportar Excel
                    </button>
                    <button type="button" class="desktop-menu__item" id="btn-exportar-pdf-reporte-vendedores">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 15h6"/><path d="M9 18h6"/></svg>
                        Exportar PDF
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="desktop-toolbar__group desktop-reportes-toolbar-panel" data-reportes-toolbar-view="ventas.vendedores">
        <select class="desktop-toolbar__select" id="reportes-vendedores-length">
            <option value="10">10 por página</option>
            <option value="25" selected>25 por página</option>
            <option value="50">50 por página</option>
            <option value="100">100 por página</option>
        </select>
        <input type="search" id="reportes-vendedores-search" class="desktop-toolbar__search desktop-reportes-search" placeholder="Buscar folio, vendedor, producto, código o línea">
    </div>
@endsection

@section('content')
    <section class="desktop-pane desktop-reportes-pane" data-reportes-pane="ventas.vendedores">
        <div class="desktop-list-wrap">
            <div class="desktop-reportes-card__body" style="padding-bottom:0;">
                <div class="desktop-reportes-kicker">
                    <div>
                        <span class="desktop-reportes-eyebrow">Ventas · Vendedores</span>
                        <h1 class="desktop-reportes-title">Productos vendidos por vendedor</h1>
                        <p class="desktop-reportes-copy" style="margin-top:6px; max-width:none;">
                            Consulta agregada por vendedor y producto dentro del periodo seleccionado.
                        </p>
                    </div>
                    <div class="desktop-reportes-kicker__meta">
                        Rango aplicado:
                        <strong id="reportes-rango-texto">Semana en curso</strong>
                    </div>
                </div>
            </div>
            <table id="desktop-reportes-vendedores-table" class="desktop-list">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Folio / Ref.</th>
                        <th>Vendedor</th>
                        <th>Línea</th>
                        <th>Producto</th>
                        <th>Código</th>
                        <th style="text-align:right;">Piezas</th>
                        <th style="text-align:right;">Importe</th>
                        <th style="text-align:right;">Descuento</th>
                        <th style="text-align:right;">Precio unit.</th>
                        <th>Última venta</th>
                    </tr>
                </thead>
            </table>
        </div>
        <div class="desktop-list-foot">
            <div id="desktop-reportes-vendedores-info"></div>
            <div id="desktop-reportes-vendedores-pagination" class="desktop-pager"></div>
        </div>
    </section>

    <section class="desktop-pane desktop-reportes-pane is-hidden" data-reportes-pane="ventas.sesiones">
        <div class="desktop-reportes-board">
            <article class="desktop-reportes-card">
                <div class="desktop-reportes-card__body">
                    <span class="desktop-reportes-eyebrow">Ventas · Sesiones</span>
                    <h2 class="desktop-reportes-title">Vista preparada para sesiones de caja</h2>
                    <p class="desktop-reportes-copy">
                        Aquí puede entrar el análisis por apertura y cierre de sesión, cortes, diferencias, tickets emitidos
                        y productividad por turno.
                    </p>
                </div>
            </article>
        </div>
    </section>

    <section class="desktop-pane desktop-reportes-pane is-hidden" data-reportes-pane="ventas.fecha">
        <div class="desktop-reportes-board">
            <article class="desktop-reportes-card">
                <div class="desktop-reportes-card__body">
                    <span class="desktop-reportes-eyebrow">Ventas · Fecha</span>
                    <h2 class="desktop-reportes-title">Vista preparada para cortes por rango de fechas</h2>
                    <p class="desktop-reportes-copy">
                        Esta vista puede concentrar comparativos diarios, semanales o mensuales, con acumulados,
                        tendencias y comportamiento por sucursal.
                    </p>
                </div>
            </article>
        </div>
    </section>

    <section class="desktop-pane desktop-reportes-pane is-hidden" data-reportes-pane="inventario.existencias">
        <div class="desktop-reportes-board">
            <article class="desktop-reportes-card">
                <div class="desktop-reportes-card__body">
                    <span class="desktop-reportes-eyebrow">Inventario · Existencias</span>
                    <h2 class="desktop-reportes-title">Vista preparada para existencias por sucursal o almacén</h2>
                    <p class="desktop-reportes-copy">
                        Este espacio puede concentrar stock actual, mínimos, máximos, quiebres y cobertura por almacén.
                    </p>
                </div>
            </article>
        </div>
    </section>

    <section class="desktop-pane desktop-reportes-pane is-hidden" data-reportes-pane="inventario.movimientos">
        <div class="desktop-reportes-board">
            <article class="desktop-reportes-card">
                <div class="desktop-reportes-card__body">
                    <span class="desktop-reportes-eyebrow">Inventario · Movimientos</span>
                    <h2 class="desktop-reportes-title">Vista preparada para entradas, salidas y ajustes</h2>
                    <p class="desktop-reportes-copy">
                        Aquí puede entrar el histórico de movimientos por fecha, tipo, usuario y documento origen.
                    </p>
                </div>
            </article>
        </div>
    </section>

    <section class="desktop-pane desktop-reportes-pane is-hidden" data-reportes-pane="inventario.kardex">
        <div class="desktop-reportes-board">
            <article class="desktop-reportes-card">
                <div class="desktop-reportes-card__body">
                    <span class="desktop-reportes-eyebrow">Inventario · Kardex</span>
                    <h2 class="desktop-reportes-title">Vista preparada para consulta detallada por producto</h2>
                    <p class="desktop-reportes-copy">
                        Esta vista puede mostrar existencias antes y después, costo, documento y trazabilidad por SKU.
                    </p>
                </div>
            </article>
        </div>
    </section>
@endsection

@push('desktop-vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endpush

@push('desktop-scripts')
    <script>
        (function () {
            const moduleButtons = Array.from(document.querySelectorAll('[data-reportes-module]'));
            const viewButtons = Array.from(document.querySelectorAll('[data-reportes-view]'));
            const subnavs = Array.from(document.querySelectorAll('[data-reportes-subnav]'));
            const panes = Array.from(document.querySelectorAll('[data-reportes-pane]'));
            const toolbarPanels = Array.from(document.querySelectorAll('[data-reportes-toolbar-view]'));
            const defaultViews = {
                ventas: 'ventas.vendedores',
                inventario: 'inventario.existencias'
            };

            let currentModule = 'ventas';

            function setView(view) {
                viewButtons.forEach(function (button) {
                    const active = button.getAttribute('data-reportes-view') === view;
                    button.classList.toggle('desktop-btn--primary', active);
                    button.classList.toggle('desktop-btn--ghost', !active);
                    button.setAttribute('aria-current', active ? 'page' : 'false');
                });

                panes.forEach(function (pane) {
                    pane.classList.toggle('is-hidden', pane.getAttribute('data-reportes-pane') !== view);
                });

                toolbarPanels.forEach(function (panel) {
                    panel.classList.toggle('is-hidden', panel.getAttribute('data-reportes-toolbar-view') !== view);
                });
            }

            function setModule(module) {
                currentModule = module;

                moduleButtons.forEach(function (button) {
                    const active = button.getAttribute('data-reportes-module') === module;
                    button.classList.toggle('desktop-btn--active', active);
                    button.setAttribute('aria-current', active ? 'page' : 'false');
                });

                subnavs.forEach(function (subnav) {
                    subnav.classList.toggle('is-hidden', subnav.getAttribute('data-reportes-subnav') !== module);
                });

                setView(defaultViews[module]);
            }

            moduleButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    setModule(button.getAttribute('data-reportes-module'));
                });
            });

            viewButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    const view = button.getAttribute('data-reportes-view');
                    if (!view.startsWith(currentModule + '.')) return;
                    setView(view);
                });
            });

            setModule(currentModule);
        })();

        (function () {
            const $table = $('#desktop-reportes-vendedores-table');
            if (!$table.length) return;

            const rutas = {
                exportarExcel: @json(route('desktop.reportes.ventas.vendedores.exportar.excel')),
                exportarPdf: @json(route('desktop.reportes.ventas.vendedores.exportar.pdf')),
            };
            const $tiempo = $('#reportes-tiempo');
            const $tipo = $('#reportes-tipo');
            const $desde = $('#reportes-desde');
            const $hasta = $('#reportes-hasta');
            const $rangeWrap = $('#reportes-range-wrap');
            const $vendedor = $('#reportes-vendedor');
            const $linea = $('#reportes-linea');
            const $search = $('#reportes-vendedores-search');
            const $length = $('#reportes-vendedores-length');
            const $rangoTexto = $('#reportes-rango-texto');
            let tabla = null;

            function fmtMoney(value) {
                return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(value || 0));
            }

            function fmtNumber(value) {
                return new Intl.NumberFormat('es-MX', { minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(Number(value || 0));
            }

            function escapeHtml(value) {
                return String(value || '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#39;');
            }

            function toggleRangeInputs() {
                $rangeWrap.toggleClass('is-hidden', $tiempo.val() !== 'rango');
            }

            function rangoLabel(meta) {
                const selected = $tiempo.find('option:selected').text();
                if ($tiempo.val() !== 'rango' || !meta?.desde || !meta?.hasta) {
                    return selected;
                }
                return meta.desde + ' a ' + meta.hasta;
            }

            function renderFooter() {
                if (!tabla) return;
                const info = tabla.page.info();
                const total = info.recordsDisplay;

                if (!total) {
                    $('#desktop-reportes-vendedores-info').text('Sin ventas para el filtro seleccionado');
                    $('#desktop-reportes-vendedores-pagination').empty();
                    return;
                }

                $('#desktop-reportes-vendedores-info').text(
                    'Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + total + ' registros'
                );

                const buttons = [];
                const current = info.page;
                const totalPages = info.pages;

                buttons.push({ label: '‹', page: 'previous', disabled: current === 0 });
                for (let i = 0; i < totalPages; i += 1) {
                    buttons.push({ label: String(i + 1), page: i, active: i === current });
                }
                buttons.push({ label: '›', page: 'next', disabled: current >= totalPages - 1 });

                $('#desktop-reportes-vendedores-pagination').html(buttons.map(function (button) {
                    const classes = [
                        'desktop-pager__btn',
                        button.active ? 'is-active' : '',
                        button.disabled ? 'is-disabled' : ''
                    ].filter(Boolean).join(' ');

                    return '<button type="button" class="' + classes + '" data-page="' + button.page + '"' +
                        (button.disabled ? ' disabled' : '') + '>' + button.label + '</button>';
                }).join(''));
            }

            function ajaxParams(d) {
                d.tiempo = $tiempo.val();
                d.tipo = $tipo.val();
                d.desde = $desde.val();
                d.hasta = $hasta.val();
                d.vendedor_id = $vendedor.val();
                d.linea_id = $linea.val();
            }

            function currentFilters() {
                return {
                    tiempo: $tiempo.val(),
                    tipo: $tipo.val(),
                    desde: $desde.val(),
                    hasta: $hasta.val(),
                    vendedor_id: $vendedor.val(),
                    linea_id: $linea.val(),
                    q: $search.val()
                };
            }

            function buildExportUrl(baseUrl) {
                const params = new URLSearchParams();
                const filters = currentFilters();

                Object.entries(filters).forEach(function ([key, value]) {
                    if (value !== null && value !== undefined && String(value).trim() !== '') {
                        params.append(key, value);
                    }
                });

                const query = params.toString();
                return query ? baseUrl + '?' + query : baseUrl;
            }

            tabla = $table.DataTable({
                ajax: {
                    url: '{{ route('desktop.reportes.ventas.vendedores.data') }}',
                    data: ajaxParams,
                    dataSrc: function (json) {
                        $rangoTexto.text(rangoLabel(json.meta || null));
                        return json.data || [];
                    }
                },
                processing: true,
                deferRender: true,
                responsive: false,
                autoWidth: false,
                pageLength: 25,
                lengthChange: false,
                searching: true,
                dom: 'rt',
                order: [[10, 'desc']],
                language: {
                    info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                    infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                    infoFiltered: '(filtrado de _MAX_ registros)',
                    paginate: { first: '«', last: '»', next: '›', previous: '‹' },
                    processing: 'Cargando...',
                    emptyTable: 'No hay ventas en el periodo seleccionado',
                    zeroRecords: 'No se encontraron coincidencias'
                },
                columns: [
                    {
                        data: 'tipo',
                        render: function (value) {
                            const isCambio = String(value || '').toLowerCase().includes('cambio');
                            const variant = isCambio ? 'desktop-pill--brand' : 'desktop-pill--neutral';
                            return '<span class="desktop-pill ' + variant + '">' + escapeHtml(value || 'Venta') + '</span>';
                        }
                    },
                    {
                        data: 'folio',
                        render: function (value, type, row) {
                            return '<span class="desktop-list__name">' + escapeHtml(value || 'Sin folio') + '</span>' +
                                (row.referencia
                                    ? '<span class="desktop-list__meta">Ref. ' + escapeHtml(row.referencia) + '</span>'
                                    : '<span class="desktop-list__meta">Sin referencia</span>');
                        }
                    },
                    {
                        data: 'vendedor',
                        render: function (value, type, row) {
                            return '<span class="desktop-list__name">' + escapeHtml(value) + '</span>' +
                                '<span class="desktop-list__meta">' + escapeHtml(row.codigo || 'Sin código') + '</span>';
                        }
                    },
                    { data: 'linea', render: function (value) { return '<span class="desktop-pill desktop-pill--neutral">' + escapeHtml(value) + '</span>'; } },
                    {
                        data: 'producto',
                        width: '28%',
                        render: function (value) {
                            return '<span class="desktop-list__name">' + escapeHtml(value) + '</span>';
                        }
                    },
                    { data: 'codigo', render: function (value) { return value ? '<span style="font-weight:600;">' + escapeHtml(value) + '</span>' : '<span class="desktop-list__meta">Sin código</span>'; } },
                    { data: 'piezas_vendidas', render: function (value) { return '<span class="desktop-reportes-cell--num">' + fmtNumber(value) + '</span>'; } },
                    { data: 'importe_total', render: function (value) { return '<span class="desktop-reportes-cell--money">' + fmtMoney(value) + '</span>'; } },
                    {
                        data: 'descuento_total',
                        render: function (value, type, row) {
                            if (!row.tiene_descuento) {
                                return '<span class="desktop-list__meta desktop-reportes-cell--meta-right">Sin descuento</span>';
                            }

                            const porcentaje = Number(row.descuento_porcentaje_max || 0);
                            return '<span class="desktop-reportes-cell--money" style="color:var(--warning);">' + fmtMoney(value) + '</span>' +
                                (porcentaje > 0
                                    ? '<span class="desktop-list__meta desktop-reportes-cell--meta-right">' + fmtNumber(porcentaje) + '% max.</span>'
                                    : '');
                        }
                    },
                    { data: 'precio_unitario', render: function (value) { return '<span class="desktop-reportes-cell--num">' + fmtMoney(value) + '</span>'; } },
                    { data: 'ultima_venta', render: function (value) { return value ? '<span class="desktop-reportes-cell--date">' + escapeHtml(value) + '</span>' : '<span class="desktop-list__meta desktop-reportes-cell--meta-right">Sin fecha</span>'; } }
                ],
                initComplete: renderFooter,
                drawCallback: renderFooter
            });

            $tiempo.on('change', function () {
                toggleRangeInputs();
                tabla.ajax.reload();
            });

            $tipo.on('change', function () { tabla.ajax.reload(); });
            $desde.on('change', function () { if ($tiempo.val() === 'rango') tabla.ajax.reload(); });
            $hasta.on('change', function () { if ($tiempo.val() === 'rango') tabla.ajax.reload(); });
            $vendedor.on('change', function () { tabla.ajax.reload(); });
            $linea.on('change', function () { tabla.ajax.reload(); });
            $search.on('input', function () { tabla.search(this.value).draw(); });
            $length.on('change', function () { tabla.page.len(Number(this.value)).draw(); });
            $('#btn-recargar-reporte-vendedores').on('click', function () { tabla.ajax.reload(); });
            $('#btn-exportar-reporte-vendedores').on('click', function () {
                window.location.href = buildExportUrl(rutas.exportarExcel);
            });
            $('#btn-exportar-pdf-reporte-vendedores').on('click', function () {
                window.open(buildExportUrl(rutas.exportarPdf), '_blank');
            });
            $('#desktop-reportes-vendedores-pagination').on('click', '.desktop-pager__btn', function () {
                const page = $(this).data('page');
                if ($(this).is(':disabled')) return;
                tabla.page(page).draw('page');
            });

            toggleRangeInputs();
        })();
    </script>
@endpush
