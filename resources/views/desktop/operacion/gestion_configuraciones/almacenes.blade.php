@extends('layouts.desktop')

@section('title', 'Almacenes')

@push('desktop-vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        @php($activeSubmenu = 'almacenes')
        @include('desktop.operacion.gestion_configuraciones._subnav')
        @if($permisosUI['almacen_crear'])
            <span class="desktop-toolbar__divider"></span>
            <button type="button" class="desktop-btn desktop-btn--primary" id="btn-nuevo-almacen">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5v14"/></svg>
                Nuevo almacén
            </button>
        @endif
        <button type="button" class="desktop-btn desktop-btn--ghost" id="btn-recargar-almacenes">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
            Actualizar
        </button>
    </div>
    <div class="desktop-toolbar__group">
        <select class="desktop-toolbar__select" id="almacenes-sucursal">
            <option value="">Todas las sucursales</option>
            @foreach($opciones['sucursales'] as $sucursal)
                <option value="{{ $sucursal->scl_id }}">{{ $sucursal->scl_nombre }}</option>
            @endforeach
        </select>
        <select class="desktop-toolbar__select" id="almacenes-tipo">
            <option value="">Todos los tipos</option>
            @foreach($opciones['tipos_almacen'] as $tipo)
                <option value="{{ $tipo->tal_id }}">{{ $tipo->tal_nombre }}</option>
            @endforeach
        </select>
        <select class="desktop-toolbar__select" id="almacenes-estatus">
            <option value="">Todos los estatus</option>
            <option value="activo">Activos</option>
            <option value="inactivo">Inactivos</option>
        </select>
        <select class="desktop-toolbar__select" id="almacenes-length">
            <option value="10">10 por página</option>
            <option value="25">25 por página</option>
            <option value="50">50 por página</option>
        </select>
        <input type="search" id="almacenes-search" class="desktop-toolbar__search" placeholder="Buscar almacén">
    </div>
@endsection

@section('content')
    <section class="desktop-pane">
        <div class="desktop-list-wrap">
            <table id="desktop-almacenes-table" class="desktop-list">
                <thead>
                    <tr>
                        <th>Almacén</th>
                        <th>Sucursal</th>
                        <th>Clave</th>
                        <th>Tipo</th>
                        <th style="width:104px;">Estatus</th>
                        <th style="width:56px; text-align:right;">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
        <div class="desktop-list-foot">
            <div id="desktop-almacenes-info"></div>
            <div id="desktop-almacenes-pagination" class="desktop-pager"></div>
        </div>
    </section>

    <div class="desktop-modal" id="desktop-almacen-modal" aria-hidden="true">
        <div class="desktop-modal__dialog">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="desktop-almacen-modal-title">Nuevo almacén</div>
                <button type="button" class="desktop-modal__close" data-close-almacen-modal aria-label="Cerrar">&times;</button>
            </div>

            <form id="desktop-almacen-form">
                <div class="desktop-modal__body">
                    <input type="hidden" name="alm_id" id="alm_id">
                    <div class="desktop-form-grid">
                        <div class="desktop-field">
                            <label>Sucursal</label>
                            <select name="alm_scl_id" id="alm_scl_id" required>
                                <option value="">Selecciona</option>
                                @foreach($opciones['sucursales'] as $sucursal)
                                    <option value="{{ $sucursal->scl_id }}">{{ $sucursal->scl_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="desktop-field">
                            <label>Tipo de almacén</label>
                            <select name="alm_tal_id" id="alm_tal_id" required>
                                <option value="">Selecciona</option>
                                @foreach($opciones['tipos_almacen'] as $tipo)
                                    <option value="{{ $tipo->tal_id }}">{{ $tipo->tal_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="desktop-field">
                            <label>Nombre del almacén</label>
                            <input type="text" name="alm_nombre" id="alm_nombre" maxlength="120" placeholder="Ej. Almacén principal" required>
                        </div>
                        <div class="desktop-field">
                            <label>Estatus</label>
                            <select name="alm_estatus" id="alm_estatus" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="desktop-field desktop-field--full">
                            <label>Clave</label>
                            <input type="text" id="alm_clave_preview" value="Se generará automáticamente al guardar." readonly>
                        </div>
                    </div>
                </div>

                <div class="desktop-modal__foot">
                    <button type="button" class="desktop-btn desktop-btn--default" data-close-almacen-modal>Cancelar</button>
                    <button type="submit" class="desktop-btn desktop-btn--primary" id="btn-guardar-almacen">Guardar almacén</button>
                </div>
            </form>
        </div>
    </div>

    <div class="desktop-feedback" id="desktop-almacenes-feedback"></div>
@endsection

@push('desktop-vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endpush

@push('desktop-scripts')
    <script>
        (function () {
            const $table = $('#desktop-almacenes-table');
            const $modal = $('#desktop-almacen-modal');
            const $form = $('#desktop-almacen-form');
            const $feedback = $('#desktop-almacenes-feedback');
            let almacenesTable = null;

            $.ajaxSetup({
                cache: false,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const permisosUI = @json($permisosUI);
            const rutas = {
                data: '{{ route('desktop.operacion.gestion_configuraciones.almacenes.data') }}',
                show: function (id) { return '{{ url('/desktop/operacion/gestion-configuraciones/almacenes') }}/' + id; },
                store: '{{ route('desktop.operacion.gestion_configuraciones.almacenes.store') }}',
                update: function (id) { return '{{ url('/desktop/operacion/gestion-configuraciones/almacenes') }}/' + id; },
                estatus: function (id) { return '{{ url('/desktop/operacion/gestion-configuraciones/almacenes') }}/' + id + '/estatus'; }
            };

            const ICONS = {
                edit: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>',
                toggle: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.2-8.5"/><path d="m9 11 3 3L22 4"/></svg>',
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
                return String(value || '?').trim().split(/\s+/).slice(0, 2).map(function (part) {
                    return part.charAt(0);
                }).join('').toUpperCase() || '?';
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

            function renderStatus(value) {
                const className = value === 'activo' ? 'desktop-status--active' : 'desktop-status--inactive';
                const label = value === 'activo' ? 'Activo' : 'Inactivo';
                return '<span class="desktop-status ' + className + '">' + label + '</span>';
            }

            function renderActions(row) {
                const toggleTo = row.alm_estatus === 'activo' ? 'inactivo' : 'activo';
                const toggleText = row.alm_estatus === 'activo' ? 'Inactivar' : 'Activar';

                return '' +
                    '<div class="desktop-rowmenu">' +
                        '<button type="button" class="desktop-overflow" data-overflow aria-haspopup="true" aria-expanded="false" aria-label="Más acciones">' + ICONS.dots + '</button>' +
                        '<div class="desktop-menu">' +
                            (permisosUI.almacen_editar
                                ? '<button type="button" class="desktop-menu__item btn-editar-almacen" data-id="' + row.alm_id + '">' + ICONS.edit + 'Editar</button>'
                                : '') +
                            (permisosUI.almacen_inactivar
                                ? '<div class="desktop-menu__divider"></div><button type="button" class="desktop-menu__item btn-toggle-almacen" data-id="' + row.alm_id + '" data-estatus="' + toggleTo + '">' + ICONS.toggle + toggleText + '</button>'
                                : '') +
                        '</div>' +
                    '</div>';
            }

            function renderCustomFooter() {
                if (!almacenesTable) return;

                const info = almacenesTable.page.info();
                const total = info.recordsDisplay;

                if (!total) {
                    $('#desktop-almacenes-info').text('Mostrando 0 almacenes');
                    $('#desktop-almacenes-pagination').empty();
                    return;
                }

                $('#desktop-almacenes-info').text(
                    'Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + total + ' almacenes'
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

                $('#desktop-almacenes-pagination').html(html);
            }

            function limpiarFormulario() {
                $form.get(0).reset();
                $('#alm_id').val('');
                $('#alm_estatus').val('activo');
                $('#alm_clave_preview').val('Se generará automáticamente al guardar.');
                $('#desktop-almacen-modal-title').text('Nuevo almacén');
                $('#btn-guardar-almacen').text('Guardar almacén');
            }

            function initTable() {
                almacenesTable = $table.DataTable({
                    ajax: {
                        url: rutas.data,
                        data: function () {
                            return {
                                buscar: $('#almacenes-search').val(),
                                estatus: $('#almacenes-estatus').val(),
                                alm_scl_id: $('#almacenes-sucursal').val(),
                                alm_tal_id: $('#almacenes-tipo').val()
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
                    order: [[1, 'asc'], [0, 'asc']],
                    dom: 'rt',
                    language: {
                        processing: 'Cargando...',
                        emptyTable: 'No hay almacenes registrados',
                        zeroRecords: 'No se encontraron almacenes'
                    },
                    columns: [
                        {
                            data: 'alm_nombre',
                            render: function (value, type, row) {
                                return '' +
                                    '<div class="desktop-cell-primary">' +
                                        '<span class="desktop-avatar-sm">' + escapeHtml(initials(value)) + '</span>' +
                                        '<span>' +
                                            '<span class="desktop-list__name">' + escapeHtml(value) + '</span>' +
                                            '<span class="desktop-list__meta">ID ' + escapeHtml(row.alm_id) + '</span>' +
                                        '</span>' +
                                    '</div>';
                            }
                        },
                        {
                            data: 'sucursal',
                            render: function (value) {
                                return value ? '<span style="font-weight:600;">' + escapeHtml(value) + '</span>' : '<span class="desktop-list__meta">Sin sucursal</span>';
                            }
                        },
                        {
                            data: 'alm_clave',
                            render: function (value) {
                                return '<span style="font-weight:600;">' + escapeHtml(value) + '</span>';
                            }
                        },
                        {
                            data: 'tipo',
                            render: function (value) {
                                return value ? escapeHtml(value) : '<span class="desktop-list__meta">Sin tipo</span>';
                            }
                        },
                        { data: 'alm_estatus', render: renderStatus },
                        { data: null, orderable: false, searchable: false, render: renderActions }
                    ],
                    initComplete: renderCustomFooter,
                    drawCallback: renderCustomFooter
                });
            }

            function reloadTable(resetPaging) {
                if (!almacenesTable) return;
                almacenesTable.ajax.reload(null, !resetPaging);
            }

            function cargarAlmacen(id) {
                $.getJSON(rutas.show(id))
                    .done(function (response) {
                        const data = response.data || {};
                        limpiarFormulario();
                        $('#desktop-almacen-modal-title').text('Editar almacén');
                        $('#btn-guardar-almacen').text('Guardar cambios');
                        $('#alm_id').val(data.alm_id || '');
                        $('#alm_scl_id').val(data.alm_scl_id || '');
                        $('#alm_tal_id').val(data.alm_tal_id || '');
                        $('#alm_nombre').val(data.alm_nombre || '');
                        $('#alm_estatus').val(data.alm_estatus || 'activo');
                        $('#alm_clave_preview').val(data.alm_clave || 'Se generará automáticamente al guardar.');
                        openModal();
                    })
                    .fail(function (xhr) {
                        showFeedback('error', parseError(xhr));
                    });
            }

            function cambiarEstatus(id, estatus) {
                $.ajax({
                    url: rutas.estatus(id),
                    method: 'PATCH',
                    data: { alm_estatus: estatus }
                }).done(function (response) {
                    showFeedback('success', response.message || 'Estatus actualizado correctamente.');
                    reloadTable(false);
                }).fail(function (xhr) {
                    showFeedback('error', parseError(xhr));
                });
            }

            initTable();

            $('#btn-nuevo-almacen').on('click', function () {
                limpiarFormulario();
                openModal();
            });

            $('#btn-recargar-almacenes').on('click', function () {
                reloadTable(true);
            });

            $('#almacenes-sucursal, #almacenes-tipo, #almacenes-estatus').on('change', function () {
                reloadTable(true);
            });

            $('#almacenes-length').on('change', function () {
                if (!almacenesTable) return;
                almacenesTable.page.len(Number(this.value)).draw();
            });

            $('#almacenes-search').on('input', function () {
                reloadTable(true);
            });

            $('#desktop-almacenes-pagination').on('click', '.desktop-pager__btn', function () {
                if ($(this).is(':disabled') || !almacenesTable) return;
                almacenesTable.page($(this).data('page')).draw('page');
            });

            $(document).on('click', '[data-close-almacen-modal]', function () {
                closeModal();
            });

            $modal.on('click', function (event) {
                if (event.target === this) closeModal();
            });

            $table.on('click', '.btn-editar-almacen', function () {
                cargarAlmacen($(this).data('id'));
            });

            $table.on('click', '.btn-toggle-almacen', function () {
                const id = $(this).data('id');
                const estatus = $(this).data('estatus');
                const promptText = estatus === 'activo'
                    ? '¿Deseas activar este almacén?'
                    : '¿Deseas inactivar este almacén?';

                if (!window.confirm(promptText)) return;
                cambiarEstatus(id, estatus);
            });

            $form.on('submit', function (event) {
                event.preventDefault();

                const id = $('#alm_id').val();
                const url = id ? rutas.update(id) : rutas.store;

                $.ajax({
                    url: url,
                    method: id ? 'PUT' : 'POST',
                    data: $form.serialize()
                }).done(function (response) {
                    closeModal();
                    showFeedback('success', response.message || 'Almacén guardado correctamente.');
                    reloadTable(false);
                }).fail(function (xhr) {
                    showFeedback('error', parseError(xhr));
                });
            });
        })();
    </script>
@endpush
