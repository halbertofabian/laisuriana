@extends('layouts.desktop')

@section('title', 'Sucursales')

@push('desktop-vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        @php($activeSubmenu = 'sucursales')
        @include('desktop.operacion.gestion_configuraciones._subnav')
        @if($permisosUI['sucursal_crear'])
            <span class="desktop-toolbar__divider"></span>
            <button type="button" class="desktop-btn desktop-btn--primary" id="btn-nueva-sucursal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5v14"/></svg>
                Nueva sucursal
            </button>
        @endif
        <button type="button" class="desktop-btn desktop-btn--ghost" id="btn-recargar-sucursales">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
            Actualizar
        </button>
    </div>
    <div class="desktop-toolbar__group">
        <select class="desktop-toolbar__select" id="sucursales-estatus">
            <option value="">Todos los estatus</option>
            <option value="activo">Activas</option>
            <option value="inactivo">Inactivas</option>
        </select>
        <select class="desktop-toolbar__select" id="sucursales-length">
            <option value="10">10 por página</option>
            <option value="25">25 por página</option>
            <option value="50">50 por página</option>
        </select>
        <input type="search" id="sucursales-search" class="desktop-toolbar__search" placeholder="Buscar sucursal">
    </div>
@endsection

@section('content')
    <section class="desktop-pane">
        <div class="desktop-list-wrap">
            <table id="desktop-sucursales-table" class="desktop-list">
                <thead>
                    <tr>
                        <th>Sucursal</th>
                        <th>Clave</th>
                        <th>Almacenes</th>
                        <th style="width:104px;">Estatus</th>
                        <th style="width:56px; text-align:right;">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
        <div class="desktop-list-foot">
            <div id="desktop-sucursales-info"></div>
            <div id="desktop-sucursales-pagination" class="desktop-pager"></div>
        </div>
    </section>

    <div class="desktop-modal" id="desktop-sucursal-modal" aria-hidden="true">
        <div class="desktop-modal__dialog">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="desktop-sucursal-modal-title">Nueva sucursal</div>
                <button type="button" class="desktop-modal__close" data-close-sucursal-modal aria-label="Cerrar">&times;</button>
            </div>

            <form id="desktop-sucursal-form">
                <div class="desktop-modal__body">
                    <input type="hidden" name="scl_id" id="scl_id">

                    <div class="desktop-form-grid">
                        <div class="desktop-field desktop-field--full">
                            <label>Nombre de la sucursal</label>
                            <input type="text" name="scl_nombre" id="scl_nombre" maxlength="120" placeholder="Ej. Sucursal Centro" required>
                        </div>
                        <div class="desktop-field">
                            <label>Clave</label>
                            <input type="text" id="scl_clave_preview" value="Se generará automáticamente al guardar." readonly>
                        </div>
                        <div class="desktop-field">
                            <label>Estatus</label>
                            <select name="scl_estatus" id="scl_estatus" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="desktop-modal__foot">
                    <button type="button" class="desktop-btn desktop-btn--default" data-close-sucursal-modal>Cancelar</button>
                    <button type="submit" class="desktop-btn desktop-btn--primary" id="btn-guardar-sucursal">Guardar sucursal</button>
                </div>
            </form>
        </div>
    </div>

    <div class="desktop-modal" id="desktop-sucursal-detail-modal" aria-hidden="true">
        <div class="desktop-modal__dialog">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title">Detalle de sucursal</div>
                <button type="button" class="desktop-modal__close" data-close-detail-modal aria-label="Cerrar">&times;</button>
            </div>
            <div class="desktop-modal__body">
                <div class="desktop-form-grid">
                    <div class="desktop-field desktop-field--full">
                        <label>Sucursal</label>
                        <input type="text" id="detail_scl_nombre" readonly>
                    </div>
                    <div class="desktop-field">
                        <label>Clave</label>
                        <input type="text" id="detail_scl_clave" readonly>
                    </div>
                    <div class="desktop-field">
                        <label>Estatus</label>
                        <input type="text" id="detail_scl_estatus" readonly>
                    </div>
                    <div class="desktop-field">
                        <label>Almacenes activos</label>
                        <input type="text" id="detail_almacenes_activos" readonly>
                    </div>
                    <div class="desktop-field">
                        <label>Total de almacenes</label>
                        <input type="text" id="detail_almacenes_total" readonly>
                    </div>
                </div>
            </div>
            <div class="desktop-modal__foot">
                <button type="button" class="desktop-btn desktop-btn--default" data-close-detail-modal>Cerrar</button>
            </div>
        </div>
    </div>

    <div class="desktop-feedback" id="desktop-sucursales-feedback"></div>
@endsection

@push('desktop-vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endpush

@push('desktop-scripts')
    <script>
        (function () {
            const $table = $('#desktop-sucursales-table');
            const $modal = $('#desktop-sucursal-modal');
            const $detailModal = $('#desktop-sucursal-detail-modal');
            const $form = $('#desktop-sucursal-form');
            const $feedback = $('#desktop-sucursales-feedback');
            let sucursalesTable = null;

            $.ajaxSetup({
                cache: false,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const permisosUI = @json($permisosUI);
            const rutas = {
                data: '{{ route('desktop.operacion.gestion_configuraciones.sucursales.data') }}',
                show: function (id) { return '{{ url('/desktop/operacion/gestion-configuraciones/sucursales') }}/' + id; },
                store: '{{ route('desktop.operacion.gestion_configuraciones.sucursales.store') }}',
                update: function (id) { return '{{ url('/desktop/operacion/gestion-configuraciones/sucursales') }}/' + id; },
                estatus: function (id) { return '{{ url('/desktop/operacion/gestion-configuraciones/sucursales') }}/' + id + '/estatus'; },
                almacenes: '{{ route('desktop.operacion.gestion_configuraciones.almacenes.index') }}'
            };

            const ICONS = {
                edit: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>',
                view: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z"/><circle cx="12" cy="12" r="3"/></svg>',
                warehouse: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 4l9 6.5"/><path d="M5 9.8V20h14V9.8"/><path d="M9 20v-6h6v6"/></svg>',
                toggle: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.2-8.5"/><path d="m9 11 3 3L22 4"/></svg>',
                dots: '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="19" cy="12" r="1.8"/></svg>'
            };

            function escapeHtml(value) {
                return String(value || '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#39;');
            }

            function initials(value) {
                return String(value || '?')
                    .trim()
                    .split(/\s+/)
                    .slice(0, 2)
                    .map(function (part) { return part.charAt(0); })
                    .join('')
                    .toUpperCase() || '?';
            }

            function showFeedback(type, message) {
                $feedback.removeClass('is-error is-success is-visible')
                    .addClass(type === 'error' ? 'is-error' : 'is-success')
                    .text(message)
                    .addClass('is-visible');

                window.clearTimeout(showFeedback._timer);
                showFeedback._timer = window.setTimeout(function () {
                    $feedback.removeClass('is-visible');
                }, 3600);
            }

            function parseError(xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    const firstGroup = Object.values(xhr.responseJSON.errors)[0];
                    if (Array.isArray(firstGroup) && firstGroup[0]) return firstGroup[0];
                }

                return xhr.responseJSON?.message || 'No fue posible completar la operación.';
            }

            function openModal() {
                $modal.addClass('is-open').attr('aria-hidden', 'false');
            }

            function closeModal() {
                $modal.removeClass('is-open').attr('aria-hidden', 'true');
            }

            function openDetailModal() {
                $detailModal.addClass('is-open').attr('aria-hidden', 'false');
            }

            function closeDetailModal() {
                $detailModal.removeClass('is-open').attr('aria-hidden', 'true');
            }

            function renderStatus(value) {
                const className = value === 'activo' ? 'desktop-status--active' : 'desktop-status--inactive';
                const label = value === 'activo' ? 'Activo' : 'Inactivo';
                return '<span class="desktop-status ' + className + '">' + label + '</span>';
            }

            function renderActions(row) {
                const items = [];

                if (permisosUI.sucursal_editar) {
                    items.push('<button type="button" class="desktop-menu__item btn-editar-sucursal" data-id="' + row.scl_id + '">' + ICONS.edit + 'Editar sucursal</button>');
                }

                items.push('<button type="button" class="desktop-menu__item btn-ver-sucursal" data-id="' + row.scl_id + '">' + ICONS.view + 'Ver detalle</button>');
                if (permisosUI.almacen_ver) {
                    items.push('<button type="button" class="desktop-menu__item btn-ver-almacenes" data-id="' + row.scl_id + '">' + ICONS.warehouse + 'Ver almacenes</button>');
                }

                if (permisosUI.sucursal_inactivar) {
                    const next = row.scl_estatus === 'activo' ? 'inactivo' : 'activo';
                    const label = row.scl_estatus === 'activo' ? 'Inactivar' : 'Activar';
                    items.push('<div class="desktop-menu__divider"></div>');
                    items.push('<button type="button" class="desktop-menu__item btn-toggle-sucursal" data-id="' + row.scl_id + '" data-estatus="' + next + '">' + ICONS.toggle + label + '</button>');
                }

                return '' +
                    '<div class="desktop-rowmenu">' +
                        '<button type="button" class="desktop-overflow" data-overflow aria-haspopup="true" aria-expanded="false" aria-label="Más acciones">' + ICONS.dots + '</button>' +
                        '<div class="desktop-menu">' + items.join('') + '</div>' +
                    '</div>';
            }

            function renderCustomFooter() {
                if (!sucursalesTable) return;

                const info = sucursalesTable.page.info();
                const total = info.recordsDisplay;

                if (!total) {
                    $('#desktop-sucursales-info').text('Mostrando 0 sucursales');
                    $('#desktop-sucursales-pagination').empty();
                    return;
                }

                $('#desktop-sucursales-info').text(
                    'Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + total + ' sucursales'
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

                $('#desktop-sucursales-pagination').html(html);
            }

            function limpiarFormulario() {
                $form.get(0).reset();
                $('#scl_id').val('');
                $('#scl_estatus').val('activo');
                $('#scl_clave_preview').val('Se generará automáticamente al guardar.');
                $('#desktop-sucursal-modal-title').text('Nueva sucursal');
                $('#btn-guardar-sucursal').text('Guardar sucursal');
            }

            function initTable() {
                sucursalesTable = $table.DataTable({
                    ajax: {
                        url: rutas.data,
                        data: function () {
                            return {
                                buscar: $('#sucursales-search').val(),
                                estatus: $('#sucursales-estatus').val()
                            };
                        },
                        dataSrc: 'data'
                    },
                    processing: true,
                    deferRender: true,
                    responsive: false,
                    autoWidth: false,
                    pageLength: 10,
                    lengthChange: false,
                    searching: false,
                    order: [[0, 'asc']],
                    dom: 'rt',
                    language: {
                        processing: 'Cargando...',
                        emptyTable: 'No hay sucursales registradas',
                        zeroRecords: 'No se encontraron sucursales'
                    },
                    columns: [
                        {
                            data: 'scl_nombre',
                            render: function (value, type, row) {
                                return '' +
                                    '<div class="desktop-cell-primary">' +
                                        '<span class="desktop-avatar-sm">' + escapeHtml(initials(value)) + '</span>' +
                                        '<span>' +
                                            '<span class="desktop-list__name">' + escapeHtml(value) + '</span>' +
                                            '<span class="desktop-list__meta">ID ' + escapeHtml(row.scl_id) + '</span>' +
                                        '</span>' +
                                    '</div>';
                            }
                        },
                        {
                            data: 'scl_clave',
                            render: function (value) {
                                return '<span style="font-weight:600;">' + escapeHtml(value) + '</span>';
                            }
                        },
                        {
                            data: null,
                            render: function (row) {
                                return '<span class="desktop-list__name">' + escapeHtml(row.almacenes_activos) + ' activos</span>' +
                                    '<span class="desktop-list__meta">' + escapeHtml(row.almacenes_total) + ' registrados</span>';
                            }
                        },
                        { data: 'scl_estatus', render: renderStatus },
                        { data: null, orderable: false, searchable: false, render: renderActions }
                    ],
                    initComplete: renderCustomFooter,
                    drawCallback: renderCustomFooter
                });
            }

            function reloadTable(resetPaging) {
                if (!sucursalesTable) return;
                sucursalesTable.ajax.reload(null, !resetPaging);
            }

            function cargarSucursal(id) {
                $.getJSON(rutas.show(id))
                    .done(function (response) {
                        const data = response.data || {};
                        limpiarFormulario();
                        $('#desktop-sucursal-modal-title').text('Editar sucursal');
                        $('#btn-guardar-sucursal').text('Guardar cambios');
                        $('#scl_id').val(data.scl_id || '');
                        $('#scl_nombre').val(data.scl_nombre || '');
                        $('#scl_estatus').val(data.scl_estatus || 'activo');
                        $('#scl_clave_preview').val(data.scl_clave || 'Se generará automáticamente al guardar.');
                        openModal();
                    })
                    .fail(function (xhr) {
                        showFeedback('error', parseError(xhr));
                    });
            }

            function verSucursal(id) {
                $.getJSON(rutas.show(id))
                    .done(function (response) {
                        const data = response.data || {};
                        $('#detail_scl_nombre').val(data.scl_nombre || '');
                        $('#detail_scl_clave').val(data.scl_clave || '');
                        $('#detail_scl_estatus').val(data.scl_estatus === 'activo' ? 'Activo' : 'Inactivo');
                        $('#detail_almacenes_activos').val(String(data.almacenes_activos || 0));
                        $('#detail_almacenes_total').val(String(data.almacenes_total || 0));
                        openDetailModal();
                    })
                    .fail(function (xhr) {
                        showFeedback('error', parseError(xhr));
                    });
            }

            function cambiarEstatus(id, estatus) {
                $.ajax({
                    url: rutas.estatus(id),
                    method: 'PATCH',
                    data: { scl_estatus: estatus }
                }).done(function (response) {
                    showFeedback('success', response.message || 'Estatus actualizado correctamente.');
                    reloadTable(false);
                }).fail(function (xhr) {
                    showFeedback('error', parseError(xhr));
                });
            }

            initTable();

            $('#btn-nueva-sucursal').on('click', function () {
                limpiarFormulario();
                openModal();
            });

            $('#btn-recargar-sucursales').on('click', function () {
                reloadTable(true);
            });

            $('#sucursales-estatus').on('change', function () {
                reloadTable(true);
            });

            $('#sucursales-length').on('change', function () {
                if (!sucursalesTable) return;
                sucursalesTable.page.len(Number(this.value)).draw();
            });

            $('#sucursales-search').on('input', function () {
                reloadTable(true);
            });

            $('#desktop-sucursales-pagination').on('click', '.desktop-pager__btn', function () {
                if ($(this).is(':disabled') || !sucursalesTable) return;
                sucursalesTable.page($(this).data('page')).draw('page');
            });

            $(document).on('click', '[data-close-sucursal-modal]', function () {
                closeModal();
            });

            $(document).on('click', '[data-close-detail-modal]', function () {
                closeDetailModal();
            });

            $modal.on('click', function (event) {
                if (event.target === this) closeModal();
            });

            $detailModal.on('click', function (event) {
                if (event.target === this) closeDetailModal();
            });

            $table.on('click', '.btn-editar-sucursal', function () {
                cargarSucursal($(this).data('id'));
            });

            $table.on('click', '.btn-ver-sucursal', function () {
                verSucursal($(this).data('id'));
            });

            $table.on('click', '.btn-ver-almacenes', function () {
                const id = $(this).data('id');
                window.location.href = rutas.almacenes + '?sucursal=' + encodeURIComponent(id);
            });

            $table.on('click', '.btn-toggle-sucursal', function () {
                const id = $(this).data('id');
                const estatus = $(this).data('estatus');
                const promptText = estatus === 'activo'
                    ? '¿Deseas activar esta sucursal?'
                    : '¿Deseas inactivar esta sucursal?';

                DesktopUI.confirm({ title: 'Confirmar', message: promptText }).then(function (ok) {
                    if (!ok) return;
                    cambiarEstatus(id, estatus);
                });
            });

            $form.on('submit', function (event) {
                event.preventDefault();

                const id = $('#scl_id').val();
                const url = id ? rutas.update(id) : rutas.store;

                $.ajax({
                    url: url,
                    method: id ? 'PUT' : 'POST',
                    data: $form.serialize()
                }).done(function (response) {
                    closeModal();
                    showFeedback('success', response.message || 'Sucursal guardada correctamente.');
                    reloadTable(false);
                }).fail(function (xhr) {
                    showFeedback('error', parseError(xhr));
                });
            });
        })();
    </script>
@endpush
