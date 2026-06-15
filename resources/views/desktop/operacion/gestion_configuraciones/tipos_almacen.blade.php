@extends('layouts.desktop')

@section('title', 'Tipos de almacén')

@push('desktop-vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        @php($activeSubmenu = 'tipos_almacen')
        @include('desktop.operacion.gestion_configuraciones._subnav')
        @if($permisosUI['tipo_crear'])
            <span class="desktop-toolbar__divider"></span>
            <button type="button" class="desktop-btn desktop-btn--primary" id="btn-nuevo-tipo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5v14"/></svg>
                Nuevo tipo
            </button>
        @endif
        <button type="button" class="desktop-btn desktop-btn--ghost" id="btn-recargar-tipos">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
            Actualizar
        </button>
    </div>
    <div class="desktop-toolbar__group">
        <select class="desktop-toolbar__select" id="tipos-estatus">
            <option value="">Todos los estatus</option>
            <option value="activo">Activos</option>
            <option value="inactivo">Inactivos</option>
        </select>
        <select class="desktop-toolbar__select" id="tipos-length">
            <option value="10">10 por página</option>
            <option value="25">25 por página</option>
            <option value="50">50 por página</option>
        </select>
        <input type="search" id="tipos-search" class="desktop-toolbar__search" placeholder="Buscar tipo de almacén">
    </div>
@endsection

@section('content')
    <section class="desktop-pane">
        <div class="desktop-list-wrap">
            <table id="desktop-tipos-table" class="desktop-list">
                <thead>
                    <tr>
                        <th>Tipo de almacén</th>
                        <th>Clave</th>
                        <th>Descripción</th>
                        <th>Uso</th>
                        <th style="width:104px;">Estatus</th>
                        <th style="width:56px; text-align:right;">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
        <div class="desktop-list-foot">
            <div id="desktop-tipos-info"></div>
            <div id="desktop-tipos-pagination" class="desktop-pager"></div>
        </div>
    </section>

    <div class="desktop-modal" id="desktop-tipo-modal" aria-hidden="true">
        <div class="desktop-modal__dialog">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="desktop-tipo-modal-title">Nuevo tipo de almacén</div>
                <button type="button" class="desktop-modal__close" data-close-tipo-modal aria-label="Cerrar">&times;</button>
            </div>

            <form id="desktop-tipo-form">
                <div class="desktop-modal__body">
                    <input type="hidden" name="tal_id" id="tal_id">
                    <div class="desktop-form-grid">
                        <div class="desktop-field">
                            <label>Nombre</label>
                            <input type="text" name="tal_nombre" id="tal_nombre" maxlength="80" placeholder="Ej. Venta piso" required>
                        </div>
                        <div class="desktop-field">
                            <label>Estatus</label>
                            <select name="tal_estatus" id="tal_estatus" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="desktop-field desktop-field--full">
                            <label>Descripción</label>
                            <textarea name="tal_descripcion" id="tal_descripcion" rows="4" maxlength="220" placeholder="Describe el uso del tipo de almacén"></textarea>
                        </div>
                        <div class="desktop-field desktop-field--full">
                            <label>Clave</label>
                            <input type="text" id="tal_clave_preview" value="Se generará automáticamente al guardar." readonly>
                        </div>
                    </div>
                </div>

                <div class="desktop-modal__foot">
                    <button type="button" class="desktop-btn desktop-btn--default" data-close-tipo-modal>Cancelar</button>
                    <button type="submit" class="desktop-btn desktop-btn--primary" id="btn-guardar-tipo">Guardar tipo</button>
                </div>
            </form>
        </div>
    </div>

    <div class="desktop-feedback" id="desktop-tipos-feedback"></div>
@endsection

@push('desktop-vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endpush

@push('desktop-scripts')
    <script>
        (function () {
            const $table = $('#desktop-tipos-table');
            const $modal = $('#desktop-tipo-modal');
            const $form = $('#desktop-tipo-form');
            const $feedback = $('#desktop-tipos-feedback');
            let tiposTable = null;

            $.ajaxSetup({
                cache: false,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const permisosUI = @json($permisosUI);
            const rutas = {
                data: '{{ route('desktop.operacion.gestion_configuraciones.tipos_almacen.data') }}',
                show: function (id) { return '{{ url('/desktop/operacion/gestion-configuraciones/tipos-almacen') }}/' + id; },
                store: '{{ route('desktop.operacion.gestion_configuraciones.tipos_almacen.store') }}',
                update: function (id) { return '{{ url('/desktop/operacion/gestion-configuraciones/tipos-almacen') }}/' + id; },
                estatus: function (id) { return '{{ url('/desktop/operacion/gestion-configuraciones/tipos-almacen') }}/' + id + '/estatus'; }
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
                const toggleTo = row.tal_estatus === 'activo' ? 'inactivo' : 'activo';
                const toggleText = row.tal_estatus === 'activo' ? 'Inactivar' : 'Activar';

                return '' +
                    '<div class="desktop-rowmenu">' +
                        '<button type="button" class="desktop-overflow" data-overflow aria-haspopup="true" aria-expanded="false" aria-label="Más acciones">' + ICONS.dots + '</button>' +
                        '<div class="desktop-menu">' +
                            (permisosUI.tipo_editar
                                ? '<button type="button" class="desktop-menu__item btn-editar-tipo" data-id="' + row.tal_id + '">' + ICONS.edit + 'Editar</button>'
                                : '') +
                            (permisosUI.tipo_inactivar
                                ? '<div class="desktop-menu__divider"></div><button type="button" class="desktop-menu__item btn-toggle-tipo" data-id="' + row.tal_id + '" data-estatus="' + toggleTo + '">' + ICONS.toggle + toggleText + '</button>'
                                : '') +
                        '</div>' +
                    '</div>';
            }

            function renderCustomFooter() {
                if (!tiposTable) return;

                const info = tiposTable.page.info();
                const total = info.recordsDisplay;

                if (!total) {
                    $('#desktop-tipos-info').text('Mostrando 0 tipos de almacén');
                    $('#desktop-tipos-pagination').empty();
                    return;
                }

                $('#desktop-tipos-info').text(
                    'Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + total + ' tipos de almacén'
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

                $('#desktop-tipos-pagination').html(html);
            }

            function limpiarFormulario() {
                $form.get(0).reset();
                $('#tal_id').val('');
                $('#tal_estatus').val('activo');
                $('#tal_clave_preview').val('Se generará automáticamente al guardar.');
                $('#desktop-tipo-modal-title').text('Nuevo tipo de almacén');
                $('#btn-guardar-tipo').text('Guardar tipo');
            }

            function initTable() {
                tiposTable = $table.DataTable({
                    ajax: {
                        url: rutas.data,
                        data: function () {
                            return {
                                buscar: $('#tipos-search').val(),
                                estatus: $('#tipos-estatus').val()
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
                        emptyTable: 'No hay tipos de almacén registrados',
                        zeroRecords: 'No se encontraron tipos de almacén'
                    },
                    columns: [
                        {
                            data: 'tal_nombre',
                            render: function (value, type, row) {
                                return '' +
                                    '<div class="desktop-cell-primary">' +
                                        '<span class="desktop-avatar-sm">' + escapeHtml(initials(value)) + '</span>' +
                                        '<span>' +
                                            '<span class="desktop-list__name">' + escapeHtml(value) + '</span>' +
                                            '<span class="desktop-list__meta">ID ' + escapeHtml(row.tal_id) + '</span>' +
                                        '</span>' +
                                    '</div>';
                            }
                        },
                        {
                            data: 'tal_clave',
                            render: function (value) {
                                return '<span style="font-weight:600;">' + escapeHtml(value) + '</span>';
                            }
                        },
                        {
                            data: 'tal_descripcion',
                            render: function (value) {
                                return value ? escapeHtml(value) : '<span class="desktop-list__meta">Sin descripción</span>';
                            }
                        },
                        {
                            data: null,
                            render: function (row) {
                                return '<span class="desktop-list__name">' + escapeHtml(row.almacenes_activos) + ' activos</span>' +
                                    '<span class="desktop-list__meta">' + escapeHtml(row.almacenes_total) + ' registrados</span>';
                            }
                        },
                        { data: 'tal_estatus', render: renderStatus },
                        { data: null, orderable: false, searchable: false, render: renderActions }
                    ],
                    initComplete: renderCustomFooter,
                    drawCallback: renderCustomFooter
                });
            }

            function reloadTable(resetPaging) {
                if (!tiposTable) return;
                tiposTable.ajax.reload(null, !resetPaging);
            }

            function cargarTipo(id) {
                $.getJSON(rutas.show(id))
                    .done(function (response) {
                        const data = response.data || {};
                        limpiarFormulario();
                        $('#desktop-tipo-modal-title').text('Editar tipo de almacén');
                        $('#btn-guardar-tipo').text('Guardar cambios');
                        $('#tal_id').val(data.tal_id || '');
                        $('#tal_nombre').val(data.tal_nombre || '');
                        $('#tal_descripcion').val(data.tal_descripcion || '');
                        $('#tal_estatus').val(data.tal_estatus || 'activo');
                        $('#tal_clave_preview').val(data.tal_clave || 'Se generará automáticamente al guardar.');
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
                    data: { tal_estatus: estatus }
                }).done(function (response) {
                    showFeedback('success', response.message || 'Estatus actualizado correctamente.');
                    reloadTable(false);
                }).fail(function (xhr) {
                    showFeedback('error', parseError(xhr));
                });
            }

            initTable();

            $('#btn-nuevo-tipo').on('click', function () {
                limpiarFormulario();
                openModal();
            });

            $('#btn-recargar-tipos').on('click', function () {
                reloadTable(true);
            });

            $('#tipos-estatus').on('change', function () {
                reloadTable(true);
            });

            $('#tipos-length').on('change', function () {
                if (!tiposTable) return;
                tiposTable.page.len(Number(this.value)).draw();
            });

            $('#tipos-search').on('input', function () {
                reloadTable(true);
            });

            $('#desktop-tipos-pagination').on('click', '.desktop-pager__btn', function () {
                if ($(this).is(':disabled') || !tiposTable) return;
                tiposTable.page($(this).data('page')).draw('page');
            });

            $(document).on('click', '[data-close-tipo-modal]', function () {
                closeModal();
            });

            $modal.on('click', function (event) {
                if (event.target === this) closeModal();
            });

            $table.on('click', '.btn-editar-tipo', function () {
                cargarTipo($(this).data('id'));
            });

            $table.on('click', '.btn-toggle-tipo', function () {
                const id = $(this).data('id');
                const estatus = $(this).data('estatus');
                const promptText = estatus === 'activo'
                    ? '¿Deseas activar este tipo de almacén?'
                    : '¿Deseas inactivar este tipo de almacén?';

                DesktopUI.confirm({ title: 'Confirmar', message: promptText }).then(function (ok) {
                    if (!ok) return;
                    cambiarEstatus(id, estatus);
                });
            });

            $form.on('submit', function (event) {
                event.preventDefault();

                const id = $('#tal_id').val();
                const url = id ? rutas.update(id) : rutas.store;

                $.ajax({
                    url: url,
                    method: id ? 'PUT' : 'POST',
                    data: $form.serialize()
                }).done(function (response) {
                    closeModal();
                    showFeedback('success', response.message || 'Tipo de almacén guardado correctamente.');
                    reloadTable(false);
                }).fail(function (xhr) {
                    showFeedback('error', parseError(xhr));
                });
            });
        })();
    </script>
@endpush
