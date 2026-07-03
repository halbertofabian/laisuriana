@extends('layouts.desktop')

@section('title', 'Roles')

@push('desktop-vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
@endpush

@push('desktop-styles')
    <style>
        .desktop-perm-head { margin-top: 22px; padding-top: 16px; border-top: 1px solid var(--divider); }
        .desktop-perm-head strong { font-size: .92rem; font-weight: 600; }
        .desktop-perm-head span { display: block; font-size: .78rem; color: var(--text-2); margin-top: 2px; }
        #desktop-rol-modal { padding: 32px 20px; overflow-y: auto; }
        #desktop-rol-modal .desktop-modal__dialog { max-height: calc(100vh - 64px); }
        #desktop-rol-form {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            min-height: 0;
        }
        #desktop-rol-modal .desktop-modal__body {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            padding-bottom: 16px;
        }
        .desktop-perm-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 14px; margin-top: 14px;
        }
        .desktop-perm-group {
            border: 1px solid var(--stroke); border-radius: var(--r-lg);
            background: var(--surface); overflow: hidden;
        }
        .desktop-perm-group__head {
            padding: 10px 14px; border-bottom: 1px solid var(--divider);
            font-size: .8rem; font-weight: 600; color: var(--text);
            background: var(--surface-alt);
        }
        .desktop-perm-group__body { max-height: 230px; overflow: auto; padding: 6px 8px; }
    </style>
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        <div class="desktop-pivot" role="tablist">
            <a href="{{ route('desktop.usuarios') }}" class="desktop-btn">Usuarios</a>
            <button type="button" class="desktop-btn desktop-btn--active" aria-current="page">Roles</button>
            <a href="{{ route('desktop.permisos') }}" class="desktop-btn">Permisos</a>
            <a href="{{ route('desktop.bitacora') }}" class="desktop-btn">Bitácora</a>
        </div>
        @if(auth()->user()?->tienePermiso('rol.crear'))
            <span class="desktop-toolbar__divider"></span>
            <button type="button" class="desktop-btn desktop-btn--primary" id="btn-nuevo-rol">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5v14"/></svg>
                Nuevo rol
            </button>
        @endif
        <button type="button" class="desktop-btn desktop-btn--ghost" id="btn-recargar-roles">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
            Actualizar
        </button>
    </div>
    <div class="desktop-toolbar__group">
        <select class="desktop-toolbar__select" id="roles-length">
            <option value="10">10 por página</option>
            <option value="25">25 por página</option>
            <option value="50">50 por página</option>
        </select>
        <input type="search" id="roles-search" class="desktop-toolbar__search" placeholder="Buscar rol o descripción">
    </div>
@endsection

@section('content')
    <section class="desktop-pane">
        <div class="desktop-list-wrap">
            <table id="desktop-roles-table" class="desktop-list">
                <thead>
                    <tr>
                        <th style="width:200px;">Rol</th>
                        <th style="width:240px;">Descripción</th>
                        <th>Permisos</th>
                        <th style="width:104px;">Estatus</th>
                        <th style="width:56px; text-align:right;">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
        <div class="desktop-list-foot">
            <div id="desktop-roles-info"></div>
            <div id="desktop-roles-pagination" class="desktop-pager"></div>
        </div>
    </section>

    <div class="desktop-modal" id="desktop-rol-modal" aria-hidden="true">
        <div class="desktop-modal__dialog" style="width:min(1040px,100%);">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="desktop-rol-modal-title">Nuevo rol</div>
                <button type="button" class="desktop-modal__close" data-close-rol-modal aria-label="Cerrar">&times;</button>
            </div>

            <form id="desktop-rol-form">
                <div class="desktop-modal__body">
                    <input type="hidden" name="rol_id" id="rol_id">

                    <div class="desktop-form-grid">
                        <div class="desktop-field">
                            <label>Nombre</label>
                            <input type="text" name="rol_nombre" placeholder="Ej. Administrador" required>
                        </div>
                        <div class="desktop-field">
                            <label>Estatus</label>
                            <select name="rol_estatus" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="desktop-field desktop-field--full">
                            <label>Descripción</label>
                            <input type="text" name="rol_descripcion" placeholder="Breve descripción del rol">
                        </div>
                    </div>

                    <div class="desktop-perm-head">
                        <strong>Permisos</strong>
                        <span>Selecciona las acciones permitidas por módulo</span>
                    </div>

                    @php($permisosAgrupados = $permisos->groupBy('prm_modulo'))
                    <div class="desktop-perm-grid">
                        @foreach($permisosAgrupados as $modulo => $permisosDelModulo)
                            <div class="desktop-perm-group">
                                <div class="desktop-perm-group__head">{{ ucfirst(str_replace('_', ' ', $modulo)) }}</div>
                                <div class="desktop-perm-group__body">
                                    @foreach($permisosDelModulo as $permiso)
                                        <label class="desktop-check" title="{{ $permiso->prm_clave }}">
                                            <input type="checkbox" name="permisos[]" id="permiso_{{ $permiso->prm_id }}" value="{{ $permiso->prm_id }}">
                                            <span class="desktop-check__box">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                            </span>
                                            <span class="desktop-check__label">{{ $permiso->prm_descripcion }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="desktop-modal__foot">
                    <button type="button" class="desktop-btn desktop-btn--default" data-close-rol-modal>Cancelar</button>
                    <button type="submit" class="desktop-btn desktop-btn--primary" id="btn-guardar-rol">Guardar rol</button>
                </div>
            </form>
        </div>
    </div>

    <div class="desktop-feedback" id="desktop-roles-feedback"></div>
@endsection

@push('desktop-vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endpush

@push('desktop-scripts')
    <script>
        (function () {
            const $table = $('#desktop-roles-table');
            const $modal = $('#desktop-rol-modal');
            const $form = $('#desktop-rol-form');
            const $feedback = $('#desktop-roles-feedback');
            let rolesTable = null;

            $.ajaxSetup({
                cache: false,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

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

            function openModal() {
                $modal.addClass('is-open').attr('aria-hidden', 'false');
            }

            function closeModal() {
                $modal.removeClass('is-open').attr('aria-hidden', 'true');
            }

            function renderPermisos(permisos) {
                const items = permisos || [];
                if (!items.length) {
                    return '<span class="desktop-list__meta">Sin permisos</span>';
                }

                const visibles = items.slice(0, 6).map(function (permiso) {
                    return '<span class="desktop-pill desktop-pill--brand">' + escapeHtml(permiso.descripcion || '') + '</span>';
                });

                if (items.length > 6) {
                    visibles.push('<span class="desktop-pill desktop-pill--more">+' + (items.length - 6) + '</span>');
                }

                return '<div class="desktop-pill-list">' + visibles.join('') + '</div>';
            }

            function renderActions(row) {
                const toggleTo = row.rol_estatus === 'activo' ? 'inactivo' : 'activo';
                const toggleText = row.rol_estatus === 'activo' ? 'Inactivar' : 'Activar';

                return '' +
                    '<div class="desktop-rowmenu">' +
                        '<button type="button" class="desktop-overflow" data-overflow aria-haspopup="true" aria-expanded="false" aria-label="Más acciones">' + ICONS.dots + '</button>' +
                        '<div class="desktop-menu">' +
                            '<button type="button" class="desktop-menu__item btn-editar-rol" data-id="' + row.rol_id + '">' + ICONS.edit + 'Editar</button>' +
                            '<div class="desktop-menu__divider"></div>' +
                            '<button type="button" class="desktop-menu__item btn-toggle-rol" data-id="' + row.rol_id + '" data-estatus="' + toggleTo + '">' + ICONS.toggle + toggleText + '</button>' +
                        '</div>' +
                    '</div>';
            }

            function limpiarFormulario() {
                $form.get(0).reset();
                $('#rol_id').val('');
                $('input[name="permisos[]"]').prop('checked', false);
                $('#desktop-rol-modal-title').text('Nuevo rol');
                $('#btn-guardar-rol').text('Guardar rol');
            }

            function renderCustomFooter() {
                if (!rolesTable) return;

                const info = rolesTable.page.info();
                const total = info.recordsDisplay;

                if (!total) {
                    $('#desktop-roles-info').text('Mostrando 0 roles');
                    $('#desktop-roles-pagination').empty();
                    return;
                }

                $('#desktop-roles-info').text(
                    'Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + total + ' roles'
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

                $('#desktop-roles-pagination').html(html);
            }

            function initTable() {
                rolesTable = $table.DataTable({
                    ajax: {
                        url: '{{ route('seguridad.roles.data') }}',
                        dataSrc: 'data'
                    },
                    processing: true,
                    deferRender: true,
                    responsive: false,
                    autoWidth: false,
                    pageLength: 10,
                    lengthChange: false,
                    searching: true,
                    dom: 'rt',
                    language: {
                        info: 'Mostrando _START_ a _END_ de _TOTAL_ roles',
                        infoEmpty: 'Mostrando 0 a 0 de 0 roles',
                        infoFiltered: '(filtrado de _MAX_ roles)',
                        paginate: { first: '«', last: '»', next: '›', previous: '‹' },
                        processing: 'Cargando...',
                        emptyTable: 'No hay roles disponibles',
                        zeroRecords: 'No se encontraron roles'
                    },
                    columns: [
                        {
                            data: 'rol_nombre',
                            render: function (value, type, row) {
                                return '' +
                                    '<span class="desktop-list__name">' + escapeHtml(value) + '</span>' +
                                    '<span class="desktop-list__meta">ID ' + escapeHtml(row.rol_id) + '</span>';
                            }
                        },
                        {
                            data: 'rol_descripcion',
                            render: function (value) {
                                return value ? escapeHtml(value) : '<span class="desktop-list__meta">Sin descripción</span>';
                            }
                        },
                        { data: 'permisos', render: renderPermisos },
                        {
                            data: 'rol_estatus',
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

                $('#roles-search').on('input', function () {
                    rolesTable.search(this.value).draw();
                });

                $('#roles-length').on('change', function () {
                    rolesTable.page.len(Number(this.value)).draw();
                });
            }

            function cargarRol(id) {
                $.getJSON('{{ url('/seguridad/roles') }}/' + id)
                    .done(function (response) {
                        const data = response.data;
                        limpiarFormulario();
                        $('#desktop-rol-modal-title').text('Editar rol');
                        $('#btn-guardar-rol').text('Guardar cambios');
                        $('#rol_id').val(data.rol_id);
                        $('[name="rol_nombre"]').val(data.rol_nombre);
                        $('[name="rol_descripcion"]').val(data.rol_descripcion);
                        $('[name="rol_estatus"]').val(data.rol_estatus);
                        (data.permisos || []).forEach(function (permisoId) {
                            $('#permiso_' + permisoId).prop('checked', true);
                        });
                        openModal();
                    })
                    .fail(function () {
                        showFeedback('error', 'No fue posible cargar el rol.');
                    });
            }

            function cambiarEstatus(id, estatus) {
                $.ajax({
                    url: '{{ url('/seguridad/roles') }}/' + id + '/estatus',
                    method: 'PATCH',
                    data: { rol_estatus: estatus }
                }).done(function (response) {
                    showFeedback('success', response.message || 'Estatus de rol actualizado correctamente.');
                    rolesTable.ajax.reload(null, false);
                }).fail(function (xhr) {
                    showFeedback('error', xhr.responseJSON?.message || 'No se pudo cambiar el estatus del rol.');
                });
            }

            initTable();

            $('#btn-nuevo-rol').on('click', function () {
                limpiarFormulario();
                openModal();
            });

            $('#btn-recargar-roles').on('click', function () {
                rolesTable.ajax.reload();
            });

            $('#desktop-roles-pagination').on('click', '.desktop-pager__btn', function () {
                const page = $(this).data('page');
                if ($(this).is(':disabled')) return;
                rolesTable.page(page).draw('page');
            });

            $(document).on('click', '[data-close-rol-modal]', function () {
                closeModal();
            });

            $modal.on('click', function (event) {
                if (event.target === this) closeModal();
            });

            $table.on('click', '.btn-editar-rol', function () {
                cargarRol($(this).data('id'));
            });

            $table.on('click', '.btn-toggle-rol', function () {
                const id = $(this).data('id');
                const estatus = $(this).data('estatus');
                DesktopUI.confirm({ title: 'Cambiar estatus', message: '¿Deseas cambiar el estatus de este rol?' }).then(function (ok) {
                    if (!ok) return;
                    cambiarEstatus(id, estatus);
                });
            });

            $form.on('submit', function (event) {
                event.preventDefault();
                const id = $('#rol_id').val();
                const url = id ? '{{ url('/seguridad/roles') }}/' + id : '{{ route('seguridad.roles.store') }}';

                $.ajax({
                    url: url,
                    method: id ? 'PUT' : 'POST',
                    data: $form.serialize()
                }).done(function (response) {
                    closeModal();
                    showFeedback('success', response.message || 'Rol guardado correctamente.');
                    rolesTable.ajax.reload(null, false);
                }).fail(function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        const firstError = Object.values(xhr.responseJSON.errors)[0][0];
                        showFeedback('error', firstError);
                        return;
                    }
                    showFeedback('error', xhr.responseJSON?.message || 'No fue posible guardar el rol.');
                });
            });
        })();
    </script>
@endpush
