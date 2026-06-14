@extends('layouts.desktop')

@section('title', 'Permisos')

@push('desktop-vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        <div class="desktop-pivot" role="tablist">
            <a href="{{ route('desktop.usuarios') }}" class="desktop-btn">Usuarios</a>
            <a href="{{ route('desktop.roles') }}" class="desktop-btn">Roles</a>
            <a href="{{ route('desktop.permisos') }}" class="desktop-btn desktop-btn--active" aria-current="page">Permisos</a>
            <a href="{{ route('desktop.bitacora') }}" class="desktop-btn">Bitácora</a>
        </div>
        <span class="desktop-toolbar__divider"></span>
        <button type="button" class="desktop-btn desktop-btn--ghost" id="btn-recargar-permisos">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
            Actualizar
        </button>
    </div>
    <div class="desktop-toolbar__group">
        <select class="desktop-toolbar__select" id="permisos-length">
            <option value="10">10 por página</option>
            <option value="25">25 por página</option>
            <option value="50">50 por página</option>
        </select>
        <input type="search" id="permisos-search" class="desktop-toolbar__search" placeholder="Buscar permiso, clave o módulo">
    </div>
@endsection

@section('content')
    <section class="desktop-pane">
        <div class="desktop-list-wrap">
            <table id="desktop-permisos-table" class="desktop-list">
                <thead>
                    <tr>
                        <th>Permiso</th>
                        <th>Clave</th>
                        <th>Módulo</th>
                        <th>Descripción</th>
                        <th style="width:104px;">Estatus</th>
                        <th style="width:56px; text-align:right;">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
        <div class="desktop-list-foot">
            <div id="desktop-permisos-info"></div>
            <div id="desktop-permisos-pagination" class="desktop-pager"></div>
        </div>
    </section>

    <div class="desktop-modal" id="desktop-permiso-modal" aria-hidden="true">
        <div class="desktop-modal__dialog">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title">Detalle del permiso</div>
                <button type="button" class="desktop-modal__close" data-close-permiso-modal aria-label="Cerrar">&times;</button>
            </div>

            <div class="desktop-modal__body">
                <div class="desktop-form-grid">
                    <div class="desktop-field">
                        <label>Nombre</label>
                        <input type="text" id="permiso_detalle_nombre" readonly>
                    </div>
                    <div class="desktop-field">
                        <label>Clave</label>
                        <input type="text" id="permiso_detalle_clave" readonly>
                    </div>
                    <div class="desktop-field">
                        <label>Módulo</label>
                        <input type="text" id="permiso_detalle_modulo" readonly>
                    </div>
                    <div class="desktop-field">
                        <label>Estatus</label>
                        <input type="text" id="permiso_detalle_estatus" readonly>
                    </div>
                    <div class="desktop-field desktop-field--full">
                        <label>Descripción</label>
                        <textarea id="permiso_detalle_descripcion" rows="4" readonly></textarea>
                    </div>
                </div>
            </div>

            <div class="desktop-modal__foot">
                <button type="button" class="desktop-btn desktop-btn--default" data-close-permiso-modal>Cerrar</button>
            </div>
        </div>
    </div>

    <div class="desktop-feedback" id="desktop-permisos-feedback"></div>
@endsection

@push('desktop-vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endpush

@push('desktop-scripts')
    <script>
        (function () {
            const $table = $('#desktop-permisos-table');
            const $modal = $('#desktop-permiso-modal');
            const $feedback = $('#desktop-permisos-feedback');
            const rowStore = new Map();
            let permisosTable = null;

            const ICONS = {
                view: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z"/><circle cx="12" cy="12" r="3"/></svg>',
                copy: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>',
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

            function initials(value) {
                return String(value || '?')
                    .replaceAll('_', ' ')
                    .trim()
                    .split(/\s+/)
                    .slice(0, 2)
                    .map(function (part) { return part.charAt(0); })
                    .join('')
                    .toUpperCase() || '?';
            }

            function formatModule(value) {
                return String(value || 'general')
                    .replaceAll('_', ' ')
                    .replace(/\b\w/g, function (char) { return char.toUpperCase(); });
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

            function renderActions(row) {
                return '' +
                    '<div class="desktop-rowmenu">' +
                        '<button type="button" class="desktop-overflow" data-overflow aria-haspopup="true" aria-expanded="false" aria-label="Más acciones">' + ICONS.dots + '</button>' +
                        '<div class="desktop-menu">' +
                            '<button type="button" class="desktop-menu__item btn-ver-permiso" data-id="' + row.prm_id + '">' + ICONS.view + 'Ver detalle</button>' +
                            '<button type="button" class="desktop-menu__item btn-copiar-clave" data-id="' + row.prm_id + '">' + ICONS.copy + 'Copiar clave</button>' +
                            '<div class="desktop-menu__divider"></div>' +
                            '<button type="button" class="desktop-menu__item btn-filtrar-modulo" data-id="' + row.prm_id + '">' + ICONS.filter + 'Filtrar módulo</button>' +
                        '</div>' +
                    '</div>';
            }

            function renderCustomFooter() {
                if (!permisosTable) return;

                const info = permisosTable.page.info();
                const total = info.recordsDisplay;

                if (!total) {
                    $('#desktop-permisos-info').text('Mostrando 0 permisos');
                    $('#desktop-permisos-pagination').empty();
                    return;
                }

                $('#desktop-permisos-info').text(
                    'Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + total + ' permisos'
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

                $('#desktop-permisos-pagination').html(html);
            }

            function fillModal(row) {
                $('#permiso_detalle_nombre').val(row.prm_descripcion || '');
                $('#permiso_detalle_clave').val(row.prm_clave || '');
                $('#permiso_detalle_modulo').val(formatModule(row.prm_modulo));
                $('#permiso_detalle_estatus').val(row.prm_estatus === 'activo' ? 'Activo' : 'Inactivo');
                $('#permiso_detalle_descripcion').val(row.prm_descripcion || 'Sin descripción');
                openModal();
            }

            function copyText(value) {
                if (!navigator.clipboard || !navigator.clipboard.writeText) {
                    showFeedback('error', 'Tu navegador no permitió copiar al portapapeles.');
                    return;
                }

                navigator.clipboard.writeText(value)
                    .then(function () {
                        showFeedback('success', 'Clave copiada: ' + value);
                    })
                    .catch(function () {
                        showFeedback('error', 'No fue posible copiar la clave.');
                    });
            }

            function initTable() {
                permisosTable = $table.DataTable({
                    ajax: {
                        url: '{{ route('seguridad.permisos.data') }}',
                        dataSrc: function (json) {
                            rowStore.clear();
                            (json.data || []).forEach(function (row) {
                                rowStore.set(String(row.prm_id), row);
                            });
                            return json.data || [];
                        }
                    },
                    processing: true,
                    deferRender: true,
                    responsive: false,
                    autoWidth: false,
                    pageLength: 10,
                    lengthChange: false,
                    searching: true,
                    order: [[2, 'asc'], [0, 'asc']],
                    dom: 'rt',
                    language: {
                        info: 'Mostrando _START_ a _END_ de _TOTAL_ permisos',
                        infoEmpty: 'Mostrando 0 a 0 de 0 permisos',
                        infoFiltered: '(filtrado de _MAX_ permisos)',
                        paginate: { first: '«', last: '»', next: '›', previous: '‹' },
                        processing: 'Cargando...',
                        emptyTable: 'No hay permisos disponibles',
                        zeroRecords: 'No se encontraron permisos'
                    },
                    columns: [
                        {
                            data: 'prm_descripcion',
                            render: function (value, type, row) {
                                return '' +
                                    '<div class="desktop-cell-primary">' +
                                        '<span class="desktop-avatar-sm">' + escapeHtml(initials(row.prm_modulo)) + '</span>' +
                                        '<span>' +
                                            '<span class="desktop-list__name">' + escapeHtml(value || 'Sin nombre') + '</span>' +
                                            '<span class="desktop-list__meta">ID ' + escapeHtml(row.prm_id) + '</span>' +
                                        '</span>' +
                                    '</div>';
                            }
                        },
                        {
                            data: 'prm_clave',
                            render: function (value) {
                                return '<span style="font-weight:600;">' + escapeHtml(value) + '</span>';
                            }
                        },
                        {
                            data: 'prm_modulo',
                            render: function (value) {
                                return '<span class="desktop-pill desktop-pill--neutral">' + escapeHtml(formatModule(value)) + '</span>';
                            }
                        },
                        {
                            data: 'prm_descripcion',
                            render: function (value) {
                                return value ? escapeHtml(value) : '<span class="desktop-list__meta">Sin descripción</span>';
                            }
                        },
                        {
                            data: 'prm_estatus',
                            render: function (value) {
                                const className = value === 'activo' ? 'desktop-status--active' : 'desktop-status--inactive';
                                const label = value === 'activo' ? 'Activo' : 'Inactivo';
                                return '<span class="desktop-status ' + className + '">' + label + '</span>';
                            }
                        },
                        { data: null, orderable: false, searchable: false, render: renderActions }
                    ],
                    initComplete: renderCustomFooter,
                    drawCallback: renderCustomFooter
                });

                $('#permisos-search').on('input', function () {
                    permisosTable.search(this.value).draw();
                });

                $('#permisos-length').on('change', function () {
                    permisosTable.page.len(Number(this.value)).draw();
                });
            }

            initTable();

            $('#btn-recargar-permisos').on('click', function () {
                permisosTable.ajax.reload();
            });

            $('#desktop-permisos-pagination').on('click', '.desktop-pager__btn', function () {
                const page = $(this).data('page');
                if ($(this).is(':disabled')) return;
                permisosTable.page(page).draw('page');
            });

            $(document).on('click', '[data-close-permiso-modal]', function () {
                closeModal();
            });

            $modal.on('click', function (event) {
                if (event.target === this) closeModal();
            });

            $table.on('click', '.btn-ver-permiso', function () {
                const row = rowStore.get(String($(this).data('id')));
                if (!row) return;
                fillModal(row);
            });

            $table.on('click', '.btn-copiar-clave', function () {
                const row = rowStore.get(String($(this).data('id')));
                if (!row) return;
                copyText(row.prm_clave || '');
            });

            $table.on('click', '.btn-filtrar-modulo', function () {
                const row = rowStore.get(String($(this).data('id')));
                if (!row) return;
                const moduleName = formatModule(row.prm_modulo);
                $('#permisos-search').val(moduleName);
                permisosTable.search(moduleName).draw();
                showFeedback('success', 'Filtro aplicado al módulo ' + moduleName + '.');
            });
        })();
    </script>
@endpush
