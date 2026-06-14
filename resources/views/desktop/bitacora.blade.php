@extends('layouts.desktop')

@section('title', 'Bitácora')

@push('desktop-vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
@endpush

@push('desktop-styles')
    <style>
        .desktop-pane.is-hidden { display: none; }
    </style>
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        <div class="desktop-pivot" role="tablist">
            <a href="{{ route('desktop.usuarios') }}" class="desktop-btn">Usuarios</a>
            <a href="{{ route('desktop.roles') }}" class="desktop-btn">Roles</a>
            <a href="{{ route('desktop.permisos') }}" class="desktop-btn">Permisos</a>
            <a href="{{ route('desktop.bitacora') }}" class="desktop-btn desktop-btn--active" aria-current="page">Bitácora</a>
        </div>
        <span class="desktop-toolbar__divider"></span>
        <div class="desktop-pivot" role="tablist" aria-label="Vista de bitácora">
            <button type="button" class="desktop-btn desktop-btn--active" data-bitacora-view="accesos" aria-current="page">Accesos</button>
            <button type="button" class="desktop-btn" data-bitacora-view="acciones">Acciones</button>
        </div>
        <button type="button" class="desktop-btn desktop-btn--ghost" id="btn-recargar-bitacora">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
            Actualizar
        </button>
    </div>
    <div class="desktop-toolbar__group">
        <input type="date" id="bitacora-desde" class="desktop-toolbar__search" style="width: 145px;">
        <input type="date" id="bitacora-hasta" class="desktop-toolbar__search" style="width: 145px;">
        <select class="desktop-toolbar__select" id="bitacora-usuario" style="width: 170px;">
            <option value="">Todos los usuarios</option>
        </select>
        <select class="desktop-toolbar__select" id="bitacora-resultado" style="width: 168px;">
            <option value="">Todos los resultados</option>
            <option value="exitoso">Acceso permitido</option>
            <option value="fallido">Acceso denegado</option>
        </select>
        <select class="desktop-toolbar__select" id="bitacora-accion" style="width: 170px;">
            <option value="">Todos los eventos</option>
        </select>
        <select class="desktop-toolbar__select" id="bitacora-length">
            <option value="10">10 por página</option>
            <option value="25">25 por página</option>
            <option value="50">50 por página</option>
        </select>
        <input type="search" id="bitacora-search" class="desktop-toolbar__search" placeholder="Buscar evento, usuario o IP">
    </div>
@endsection

@section('content')
    <section class="desktop-pane" id="desktop-bitacora-pane-accesos">
        <div class="desktop-list-wrap">
            <table id="desktop-bitacora-accesos-table" class="desktop-list">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Usuario capturado</th>
                        <th>Usuario identificado</th>
                        <th>Estado</th>
                        <th>Detalle</th>
                        <th>IP</th>
                        <th style="width:56px; text-align:right;">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
        <div class="desktop-list-foot">
            <div id="desktop-bitacora-accesos-info"></div>
            <div id="desktop-bitacora-accesos-pagination" class="desktop-pager"></div>
        </div>
    </section>

    <section class="desktop-pane is-hidden" id="desktop-bitacora-pane-acciones">
        <div class="desktop-list-wrap">
            <table id="desktop-bitacora-acciones-table" class="desktop-list">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Evento</th>
                        <th>¿Qué ocurrió?</th>
                        <th>Usuario</th>
                        <th>Sucursal</th>
                        <th>IP</th>
                        <th>Detalle</th>
                        <th style="width:56px; text-align:right;">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
        <div class="desktop-list-foot">
            <div id="desktop-bitacora-acciones-info"></div>
            <div id="desktop-bitacora-acciones-pagination" class="desktop-pager"></div>
        </div>
    </section>

    <div class="desktop-modal" id="desktop-bitacora-modal" aria-hidden="true">
        <div class="desktop-modal__dialog">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="desktop-bitacora-modal-title">Detalle</div>
                <button type="button" class="desktop-modal__close" data-close-bitacora-modal aria-label="Cerrar">&times;</button>
            </div>
            <div class="desktop-modal__body">
                <div class="desktop-form-grid">
                    <div class="desktop-field">
                        <label id="bitacora-label-1">Campo 1</label>
                        <input type="text" id="bitacora-value-1" readonly>
                    </div>
                    <div class="desktop-field">
                        <label id="bitacora-label-2">Campo 2</label>
                        <input type="text" id="bitacora-value-2" readonly>
                    </div>
                    <div class="desktop-field">
                        <label id="bitacora-label-3">Campo 3</label>
                        <input type="text" id="bitacora-value-3" readonly>
                    </div>
                    <div class="desktop-field">
                        <label id="bitacora-label-4">Campo 4</label>
                        <input type="text" id="bitacora-value-4" readonly>
                    </div>
                    <div class="desktop-field desktop-field--full">
                        <label id="bitacora-label-5">Detalle</label>
                        <textarea id="bitacora-value-5" rows="4" readonly></textarea>
                    </div>
                </div>
            </div>
            <div class="desktop-modal__foot">
                <button type="button" class="desktop-btn desktop-btn--default" data-close-bitacora-modal>Cerrar</button>
            </div>
        </div>
    </div>

    <div class="desktop-feedback" id="desktop-bitacora-feedback"></div>
@endsection

@push('desktop-vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endpush

@push('desktop-scripts')
    <script>
        (function () {
            const $feedback = $('#desktop-bitacora-feedback');
            const $modal = $('#desktop-bitacora-modal');
            const $accesosTable = $('#desktop-bitacora-accesos-table');
            const $accionesTable = $('#desktop-bitacora-acciones-table');
            const accesosStore = new Map();
            const accionesStore = new Map();
            const optionsStore = { usuarios: new Map(), eventos: new Map() };
            let accesosTable = null;
            let accionesTable = null;
            let activeView = 'accesos';

            const ICONS = {
                view: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z"/><circle cx="12" cy="12" r="3"/></svg>',
                user: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>',
                filter: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>',
                dots: '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="19" cy="12" r="1.8"/></svg>'
            };

            function escapeHtml(v) {
                return String(v || '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#39;');
            }

            function showFeedback(type, message) {
                $feedback.removeClass('is-error is-success is-visible')
                    .addClass(type === 'error' ? 'is-error' : 'is-success')
                    .text(message)
                    .addClass('is-visible');

                window.clearTimeout(showFeedback._timer);
                showFeedback._timer = window.setTimeout(function () {
                    $feedback.removeClass('is-visible');
                }, 3200);
            }

            function openModal() {
                $modal.addClass('is-open').attr('aria-hidden', 'false');
            }

            function closeModal() {
                $modal.removeClass('is-open').attr('aria-hidden', 'true');
            }

            function normalizeText(value) {
                return String(value || '').trim();
            }

            function setLastWeek() {
                const today = new Date();
                const start = new Date(today);
                start.setDate(today.getDate() - 6);
                const fmt = function (d) {
                    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
                };
                $('#bitacora-desde').val(fmt(start));
                $('#bitacora-hasta').val(fmt(today));
            }

            function getFilters() {
                return {
                    usuario: $('#bitacora-usuario').val(),
                    fecha_desde: $('#bitacora-desde').val(),
                    fecha_hasta: $('#bitacora-hasta').val(),
                    resultado: $('#bitacora-resultado').val(),
                    accion: $('#bitacora-accion').val()
                };
            }

            function upsertOption(store, value, label) {
                const key = normalizeText(value);
                const text = normalizeText(label) || key;
                if (!key || store.has(key)) return;
                store.set(key, text);
            }

            function rebuildSelect(selector, store, emptyLabel) {
                const $select = $(selector);
                const current = String($select.val() || '');
                const options = Array.from(store.entries())
                    .sort(function (a, b) {
                        return a[1].localeCompare(b[1], 'es', { sensitivity: 'base' });
                    })
                    .map(function (entry) {
                        return '<option value="' + escapeHtml(entry[0]) + '">' + escapeHtml(entry[1]) + '</option>';
                    });

                $select.html('<option value="">' + emptyLabel + '</option>' + options.join(''));
                if (current && store.has(current)) {
                    $select.val(current);
                }
            }

            function updateFooter(table, infoSelector, pagerSelector, entityName) {
                if (!table) return;

                const info = table.page.info();
                const total = info.recordsDisplay;

                if (!total) {
                    $(infoSelector).text('Mostrando 0 ' + entityName);
                    $(pagerSelector).empty();
                    return;
                }

                $(infoSelector).text(
                    'Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + total + ' ' + entityName
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

                $(pagerSelector).html(html);
            }

            function setActiveView(view) {
                activeView = view;
                $('[data-bitacora-view]').each(function () {
                    const isActive = $(this).data('bitacora-view') === view;
                    $(this)
                        .toggleClass('desktop-btn--active', isActive)
                        .attr('aria-current', isActive ? 'page' : null);
                });
                $('#desktop-bitacora-pane-accesos').toggleClass('is-hidden', view !== 'accesos');
                $('#desktop-bitacora-pane-acciones').toggleClass('is-hidden', view !== 'acciones');
            }

            function fillModal(title, fields) {
                $('#desktop-bitacora-modal-title').text(title);
                fields.forEach(function (field, index) {
                    const suffix = String(index + 1);
                    $('#bitacora-label-' + suffix).text(field.label);
                    $('#bitacora-value-' + suffix).val(field.value || '');
                });
                openModal();
            }

            function renderAccessActions(row) {
                return '' +
                    '<div class="desktop-rowmenu">' +
                        '<button type="button" class="desktop-overflow" data-overflow aria-haspopup="true" aria-expanded="false" aria-label="Más acciones">' + ICONS.dots + '</button>' +
                        '<div class="desktop-menu">' +
                            '<button type="button" class="desktop-menu__item btn-ver-acceso" data-id="' + row.bac_id + '">' + ICONS.view + 'Ver detalle</button>' +
                            '<button type="button" class="desktop-menu__item btn-filtrar-acceso-usuario" data-id="' + row.bac_id + '">' + ICONS.user + 'Filtrar usuario</button>' +
                        '</div>' +
                    '</div>';
            }

            function renderActionActions(row) {
                return '' +
                    '<div class="desktop-rowmenu">' +
                        '<button type="button" class="desktop-overflow" data-overflow aria-haspopup="true" aria-expanded="false" aria-label="Más acciones">' + ICONS.dots + '</button>' +
                        '<div class="desktop-menu">' +
                            '<button type="button" class="desktop-menu__item btn-ver-accion" data-id="' + row.bac_id + '">' + ICONS.view + 'Ver detalle</button>' +
                            '<button type="button" class="desktop-menu__item btn-filtrar-evento" data-id="' + row.bac_id + '">' + ICONS.filter + 'Filtrar evento</button>' +
                        '</div>' +
                    '</div>';
            }

            function loadAccesos() {
                return $.getJSON('{{ route('seguridad.bitacora.accesos') }}', getFilters())
                    .done(function (response) {
                        const rows = response.data || [];
                        accesosStore.clear();
                        rows.forEach(function (row) {
                            accesosStore.set(String(row.bac_id), row);
                            upsertOption(optionsStore.usuarios, row.usuario_intentado, row.usuario_intentado);
                            upsertOption(optionsStore.usuarios, row.usuario_registrado, row.nombre_registrado || row.usuario_registrado);
                        });
                        rebuildSelect('#bitacora-usuario', optionsStore.usuarios, 'Todos los usuarios');

                        if ($.fn.DataTable.isDataTable('#desktop-bitacora-accesos-table')) {
                            accesosTable.clear().rows.add(rows).draw();
                            return;
                        }

                        accesosTable = $accesosTable.DataTable({
                            data: rows,
                            processing: true,
                            deferRender: true,
                            responsive: false,
                            autoWidth: false,
                            pageLength: 10,
                            lengthChange: false,
                            searching: true,
                            order: [[0, 'desc']],
                            dom: 'rt',
                            language: {
                                info: 'Mostrando _START_ a _END_ de _TOTAL_ accesos',
                                infoEmpty: 'Mostrando 0 a 0 de 0 accesos',
                                infoFiltered: '(filtrado de _MAX_ accesos)',
                                paginate: { first: '«', last: '»', next: '›', previous: '‹' },
                                processing: 'Cargando...',
                                emptyTable: 'No hay accesos disponibles',
                                zeroRecords: 'No se encontraron accesos'
                            },
                            columns: [
                                { data: 'fecha', render: function (value) { return value ? escapeHtml(value) : '<span class="desktop-list__meta">Sin fecha</span>'; } },
                                { data: 'usuario_intentado', render: function (value) { return '<span style="font-weight:600;">' + escapeHtml(value || '-') + '</span>'; } },
                                { data: null, render: function (row) { return escapeHtml(row.nombre_registrado || row.usuario_registrado || '-'); } },
                                {
                                    data: 'resultado',
                                    render: function (value, type, row) {
                                        const isSuccess = value === 'exitoso';
                                        return '<span class="desktop-status ' + (isSuccess ? 'desktop-status--active' : 'desktop-status--inactive') + '">' + escapeHtml(row.resultado_label || value || '-') + '</span>';
                                    }
                                },
                                { data: 'motivo', render: function (value) { return value ? escapeHtml(value) : '<span class="desktop-list__meta">Sin detalle</span>'; } },
                                { data: 'ip', render: function (value) { return escapeHtml(value || '-'); } },
                                { data: null, orderable: false, searchable: false, render: renderAccessActions }
                            ],
                            initComplete: function () {
                                updateFooter(accesosTable, '#desktop-bitacora-accesos-info', '#desktop-bitacora-accesos-pagination', 'accesos');
                            },
                            drawCallback: function () {
                                updateFooter(accesosTable, '#desktop-bitacora-accesos-info', '#desktop-bitacora-accesos-pagination', 'accesos');
                            }
                        });
                    })
                    .fail(function () {
                        showFeedback('error', 'No fue posible cargar la bitácora de accesos.');
                    });
            }

            function loadAcciones() {
                return $.getJSON('{{ route('seguridad.bitacora.acciones') }}', getFilters())
                    .done(function (response) {
                        const rows = response.data || [];
                        accionesStore.clear();
                        rows.forEach(function (row) {
                            accionesStore.set(String(row.bac_id), row);
                            upsertOption(optionsStore.usuarios, row.usuario_login, row.usuario || row.usuario_login);
                            upsertOption(optionsStore.eventos, row.accion, row.evento || row.accion);
                        });
                        rebuildSelect('#bitacora-usuario', optionsStore.usuarios, 'Todos los usuarios');
                        rebuildSelect('#bitacora-accion', optionsStore.eventos, 'Todos los eventos');

                        if ($.fn.DataTable.isDataTable('#desktop-bitacora-acciones-table')) {
                            accionesTable.clear().rows.add(rows).draw();
                            return;
                        }

                        accionesTable = $accionesTable.DataTable({
                            data: rows,
                            processing: true,
                            deferRender: true,
                            responsive: false,
                            autoWidth: false,
                            pageLength: 10,
                            lengthChange: false,
                            searching: true,
                            order: [[0, 'desc']],
                            dom: 'rt',
                            language: {
                                info: 'Mostrando _START_ a _END_ de _TOTAL_ acciones',
                                infoEmpty: 'Mostrando 0 a 0 de 0 acciones',
                                infoFiltered: '(filtrado de _MAX_ acciones)',
                                paginate: { first: '«', last: '»', next: '›', previous: '‹' },
                                processing: 'Cargando...',
                                emptyTable: 'No hay acciones disponibles',
                                zeroRecords: 'No se encontraron acciones'
                            },
                            columns: [
                                { data: 'fecha', render: function (value) { return value ? escapeHtml(value) : '<span class="desktop-list__meta">Sin fecha</span>'; } },
                                { data: 'evento', render: function (value) { return '<span class="desktop-pill desktop-pill--brand">' + escapeHtml(value || '-') + '</span>'; } },
                                { data: 'detalle', render: function (value) { return '<span style="font-weight:600;">' + escapeHtml(value || '-') + '</span>'; } },
                                { data: 'usuario', render: function (value) { return escapeHtml(value || '-'); } },
                                {
                                    data: 'sucursal',
                                    render: function (value) {
                                        return value ? '<span class="desktop-pill desktop-pill--neutral">' + escapeHtml(value) + '</span>' : '<span class="desktop-list__meta">Sin sucursal</span>';
                                    }
                                },
                                { data: 'ip', render: function (value) { return escapeHtml(value || '-'); } },
                                {
                                    data: 'payload',
                                    render: function (value) {
                                        return value && value !== 'Sin detalle adicional'
                                            ? '<span class="desktop-list__meta">' + escapeHtml(value) + '</span>'
                                            : '<span class="desktop-list__meta">Sin detalle</span>';
                                    }
                                },
                                { data: null, orderable: false, searchable: false, render: renderActionActions }
                            ],
                            initComplete: function () {
                                updateFooter(accionesTable, '#desktop-bitacora-acciones-info', '#desktop-bitacora-acciones-pagination', 'acciones');
                            },
                            drawCallback: function () {
                                updateFooter(accionesTable, '#desktop-bitacora-acciones-info', '#desktop-bitacora-acciones-pagination', 'acciones');
                            }
                        });
                    })
                    .fail(function () {
                        showFeedback('error', 'No fue posible cargar la bitácora de acciones.');
                    });
            }

            function refreshAll() {
                loadAccesos();
                loadAcciones();
            }

            setLastWeek();
            setActiveView('accesos');
            refreshAll();

            $('#btn-recargar-bitacora').on('click', refreshAll);

            $('#bitacora-search').on('input', function () {
                const value = this.value;
                if (accesosTable) accesosTable.search(value).draw();
                if (accionesTable) accionesTable.search(value).draw();
            });

            $('#bitacora-length').on('change', function () {
                const length = Number(this.value);
                if (accesosTable) accesosTable.page.len(length).draw();
                if (accionesTable) accionesTable.page.len(length).draw();
            });

            $('#bitacora-desde, #bitacora-hasta, #bitacora-usuario, #bitacora-resultado, #bitacora-accion').on('change', refreshAll);

            $('[data-bitacora-view]').on('click', function () {
                setActiveView($(this).data('bitacora-view'));
            });

            $('#desktop-bitacora-accesos-pagination').on('click', '.desktop-pager__btn', function () {
                const page = $(this).data('page');
                if ($(this).is(':disabled') || !accesosTable) return;
                accesosTable.page(page).draw('page');
            });

            $('#desktop-bitacora-acciones-pagination').on('click', '.desktop-pager__btn', function () {
                const page = $(this).data('page');
                if ($(this).is(':disabled') || !accionesTable) return;
                accionesTable.page(page).draw('page');
            });

            $(document).on('click', '[data-close-bitacora-modal]', function () {
                closeModal();
            });

            $modal.on('click', function (event) {
                if (event.target === this) closeModal();
            });

            $accesosTable.on('click', '.btn-ver-acceso', function () {
                const row = accesosStore.get(String($(this).data('id')));
                if (!row) return;
                fillModal('Detalle de acceso', [
                    { label: 'Usuario capturado', value: row.usuario_intentado || '-' },
                    { label: 'Usuario identificado', value: row.nombre_registrado || row.usuario_registrado || '-' },
                    { label: 'Resultado', value: row.resultado_label || '-' },
                    { label: 'IP', value: row.ip || '-' },
                    { label: 'Detalle', value: row.motivo || 'Sin detalle adicional' }
                ]);
            });

            $accesosTable.on('click', '.btn-filtrar-acceso-usuario', function () {
                const row = accesosStore.get(String($(this).data('id')));
                if (!row) return;
                $('#bitacora-usuario').val(row.usuario_registrado || row.usuario_intentado || '');
                refreshAll();
            });

            $accionesTable.on('click', '.btn-ver-accion', function () {
                const row = accionesStore.get(String($(this).data('id')));
                if (!row) return;
                fillModal('Detalle de acción', [
                    { label: 'Evento', value: row.evento || '-' },
                    { label: 'Usuario', value: row.usuario || row.usuario_login || '-' },
                    { label: 'Sucursal', value: row.sucursal || '-' },
                    { label: 'IP', value: row.ip || '-' },
                    { label: 'Detalle', value: [row.detalle || '', row.payload || ''].filter(Boolean).join('\n\n') || 'Sin detalle adicional' }
                ]);
            });

            $accionesTable.on('click', '.btn-filtrar-evento', function () {
                const row = accionesStore.get(String($(this).data('id')));
                if (!row) return;
                $('#bitacora-accion').val(row.accion || '');
                setActiveView('acciones');
                refreshAll();
            });
        })();
    </script>
@endpush
