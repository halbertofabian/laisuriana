@extends('layouts.desktop')

@section('title', 'Negativos por sesión')

@push('desktop-vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
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
        .desktop-neg-bar__field .desktop-toolbar__select,
        .desktop-neg-bar__field input {
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
        .desktop-neg-table tbody td {
            padding-top: 6px !important;
            padding-bottom: 6px !important;
            vertical-align: middle;
        }
        .desktop-neg-table tbody tr.is-selected td {
            background: #eaf1fd;
        }
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
        .desktop-neg-empty {
            color: var(--text-2);
            font-size: .78rem;
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
                <span class="desktop-neg-bar__cap">Sucursal</span>
                <select class="desktop-toolbar__select" id="flt-scl">
                    <option value="">Todas</option>
                    @foreach($opciones['sucursales'] as $sucursal)
                        <option value="{{ $sucursal->scl_id }}" @selected((int) $sucursal->scl_id === (int) ($defaultSucursalId ?? 0))>{{ $sucursal->scl_nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="desktop-neg-bar__field">
                <span class="desktop-neg-bar__cap">Almacén</span>
                <select class="desktop-toolbar__select" id="flt-alm">
                    <option value="">Todos</option>
                    @foreach($opciones['almacenes'] as $almacen)
                        <option value="{{ $almacen->alm_id }}" data-scl="{{ $almacen->alm_scl_id }}">{{ $almacen->alm_nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="desktop-neg-bar__field">
                <span class="desktop-neg-bar__cap">Registros</span>
                <select class="desktop-toolbar__select" id="flt-length">
                    <option value="10">10 por página</option>
                    <option value="25">25 por página</option>
                    <option value="50" selected>50 por página</option>
                    <option value="100">100 por página</option>
                    <option value="250">250 por página</option>
                </select>
            </div>

            <span class="desktop-neg-bar__spacer"></span>

            <button type="button" class="desktop-neg-clear" id="btn-limpiar">Limpiar</button>
            <button type="button" class="desktop-neg-filterbtn" id="btn-neg-filtros" aria-haspopup="dialog" aria-expanded="false">
                Más filtros
                <span class="desktop-neg-filterbtn__badge" id="neg-filtros-badge"></span>
            </button>
            <span class="desktop-neg-bar__divider"></span>
            <button type="button" class="desktop-btn desktop-btn--primary" id="btn-filtrar">Aplicar</button>
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
                    <label for="flt-desde">Desde</label>
                    <input type="date" id="flt-desde">
                </div>
                <div class="desktop-field">
                    <label for="flt-hasta">Hasta</label>
                    <input type="date" id="flt-hasta">
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
@endpush

@push('desktop-scripts')
    <script>
        (function () {
            const rutas = {
                data: @json(route('desktop.operacion.inventario.negativos_sesion.data')),
            };

            const almacenesBase = @json($opciones['almacenes']->map(fn($a) => ['alm_id' => $a->alm_id, 'alm_scl_id' => $a->alm_scl_id, 'alm_nombre' => $a->alm_nombre])->values());
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

            function syncAlmacenes() {
                const sclId = String($('#flt-scl').val() || '');
                const current = String($('#flt-alm').val() || '');
                const $alm = $('#flt-alm');
                $alm.empty().append('<option value="">Todos</option>');
                almacenesBase.forEach(function (a) {
                    if (sclId !== '' && String(a.alm_scl_id) !== sclId) return;
                    const selected = current !== '' && current === String(a.alm_id) ? ' selected' : '';
                    $alm.append('<option value="' + escapeHtml(a.alm_id) + '"' + selected + '>' + escapeHtml(a.alm_nombre) + '</option>');
                });
            }

            function filtrosActivosCount() {
                let count = 0;
                ['#flt-cse', '#flt-desde', '#flt-hasta'].forEach(function (selector) {
                    if ($(selector).val()) count += 1;
                });
                return count;
            }

            function syncBadge() {
                const count = filtrosActivosCount();
                const badge = document.getElementById('neg-filtros-badge');
                badge.textContent = count ? String(count) : '';
                badge.classList.toggle('is-visible', count > 0);
                $('#btn-neg-filtros').toggleClass('is-active', count > 0);
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
                    language: {
                        processing: 'Cargando...',
                        emptyTable: 'No se encontraron movimientos con stock negativo',
                        zeroRecords: 'No se encontraron coincidencias'
                    },
                    ajax: {
                        url: rutas.data,
                        data: function (d) {
                            d.cse_id = $('#flt-cse').val();
                            d.min_scl_id = $('#flt-scl').val();
                            d.min_alm_id = $('#flt-alm').val();
                            d.fecha_desde = $('#flt-desde').val();
                            d.fecha_hasta = $('#flt-hasta').val();
                            d.buscar = $('#flt-buscar').val().trim();
                        },
                        error: function (xhr) {
                            const msg = xhr.responseJSON?.message || 'No fue posible cargar el reporte.';
                            if (window.DesktopUI) DesktopUI.message('Error', msg, 'error');
                            else if (window.AppUI?.showMessage) window.AppUI.showMessage('Error', msg, 'error');
                        }
                    },
                    columns: [
                        { data: 'min_fecha_movimiento', render: function (v) {
                            return renderMeta(v ? String(v).replace('T', ' ').slice(0, 16) : '—', v ? 'Movimiento inventario' : '');
                        }},
                        { data: 'cse_id', render: function (v, _, row) {
                            return renderMeta('#' + (v || '—'), row.cse_estatus || '—');
                        }},
                        { data: 'caj_nombre', render: function (v) { return renderMeta(v || '—'); }},
                        { data: 'scl_nombre', render: function (v) { return renderMeta(v || '—'); }},
                        { data: 'alm_nombre', render: function (v) { return renderMeta(v || '—'); }},
                        { data: 'psv_folio', render: function (v) {
                            return '<span class="desktop-neg-badge">' + escapeHtml(v || '—') + '</span>';
                        }},
                        { data: 'psk_codigo', render: function (v, _, row) {
                            return renderMeta(v || '—', row.min_folio || '');
                        }},
                        { data: 'psk_nombre', render: function (v, _, row) {
                            return renderMeta(v || row.prd_nombre || '—', row.prd_nombre || '');
                        }},
                        { data: 'min_cantidad', className: 'desktop-neg-num', render: function (v) { return '-' + Number(v || 0).toFixed(2); }},
                        { data: 'min_existencia_antes', className: 'desktop-neg-num', render: function (v) { return Number(v || 0).toFixed(2); }},
                        { data: 'min_existencia_despues', className: 'desktop-neg-num desktop-neg-num--danger', render: function (v) { return Number(v || 0).toFixed(2); }},
                        { data: 'usuario_venta', render: function (v, _, row) {
                            return renderMeta(v || '—', row.usuario_apertura ? 'Apertura: ' + row.usuario_apertura : '');
                        }},
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
                tabla.ajax.reload(null, !resetPage ? false : true);
            }

            function limpiarFiltros() {
                $('#flt-scl').val(@json((string) ($defaultSucursalId ?? '')));
                syncAlmacenes();
                $('#flt-alm').val('');
                $('#flt-cse').val('');
                $('#flt-desde').val('');
                $('#flt-hasta').val('');
                $('#flt-buscar').val('');
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

            $('#flt-scl').on('change', function () {
                syncAlmacenes();
            });

            $('#flt-length').on('change', function () {
                if (tabla) {
                    tabla.page.len(Number(this.value || 50)).draw(false);
                }
            });

            $('#btn-filtrar, #btn-recargar-negativos').on('click', function () {
                syncBadge();
                recargar(true);
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

            syncAlmacenes();
            syncBadge();
            recargar(true);
        })();
    </script>
@endpush
