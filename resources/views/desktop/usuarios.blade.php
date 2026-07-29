@extends('layouts.desktop')

@section('title', 'Usuarios')

@push('desktop-vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        <div class="desktop-pivot" role="tablist">
            <a href="{{ route('desktop.usuarios') }}" class="desktop-btn desktop-btn--active" aria-current="page">Usuarios</a>
            <a href="{{ route('desktop.roles') }}" class="desktop-btn">Roles</a>
            <a href="{{ route('desktop.permisos') }}" class="desktop-btn">Permisos</a>
            <a href="{{ route('desktop.bitacora') }}" class="desktop-btn">Bitácora</a>
        </div>
        @if(auth()->user()?->tienePermiso('usuario.crear'))
            <span class="desktop-toolbar__divider"></span>
            <button type="button" class="desktop-btn desktop-btn--primary" id="btn-nuevo-usuario">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5v14"/></svg>
                Nuevo usuario
            </button>
        @endif
        <button type="button" class="desktop-btn desktop-btn--ghost" id="btn-recargar-usuarios">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
            Actualizar
        </button>
    </div>
    <div class="desktop-toolbar__group">
        <select class="desktop-toolbar__select" id="usuarios-length">
            <option value="10">10 por página</option>
            <option value="25">25 por página</option>
            <option value="50">50 por página</option>
        </select>
        <input type="search" id="usuarios-search" class="desktop-toolbar__search" placeholder="Buscar usuario, cuenta o correo">
    </div>
@endsection

@section('content')
    <section class="desktop-pane">
        <div class="desktop-list-wrap">
            <table id="desktop-usuarios-table" class="desktop-list">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Cuenta</th>
                        <th>Correo</th>
                        <th>Roles</th>
                        <th>Sucursales</th>
                        <th style="width:104px;">Estatus</th>
                        <th style="width:56px; text-align:right;">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
        <div class="desktop-list-foot">
            <div id="desktop-usuarios-info"></div>
            <div id="desktop-usuarios-pagination" class="desktop-pager"></div>
        </div>
    </section>

    <div class="desktop-modal" id="desktop-usuario-modal" aria-hidden="true">
        <div class="desktop-modal__dialog">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="desktop-usuario-modal-title">Nuevo usuario</div>
                <button type="button" class="desktop-modal__close" data-close-modal aria-label="Cerrar">&times;</button>
            </div>

            <form id="desktop-usuario-form" data-ls-autocomplete="admin">
                <div class="desktop-modal__body">
                    <input type="hidden" name="usr_id" id="usr_id">
                    <div class="desktop-form-grid">
                        <div class="desktop-field">
                            <label>Nombre completo</label>
                            <input type="text" name="usr_nombre" placeholder="Ej. Ana López" required>
                        </div>
                        <div class="desktop-field">
                            <label>Usuario</label>
                            <input type="text" name="usr_usuario" placeholder="Nombre de cuenta" required>
                        </div>
                        <div class="desktop-field">
                            <label>Correo electrónico</label>
                            <input type="email" name="usr_email" placeholder="correo@ejemplo.com">
                        </div>
                        <div class="desktop-field">
                            <label>Contraseña</label>
                            <input type="password" name="usr_password" id="usr_password" placeholder="••••••••">
                            <small>En edición, dejar vacío para conservar la actual.</small>
                        </div>
                    </div>

                    <div class="desktop-field-section">
                        <div class="desktop-field-section__title">Accesos y asignación</div>
                        <div class="desktop-field-section__hint">Define el estatus, los roles y las sucursales del usuario.</div>
                    </div>

                    <div class="desktop-form-grid" style="margin-top:14px;">
                        <div class="desktop-field">
                            <label>Estatus</label>
                            <select name="usr_estatus" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="desktop-field">
                            <label>Sucursal predeterminada</label>
                            <select name="usc_scl_predeterminada" id="usc_scl_predeterminada"></select>
                        </div>
                        <div class="desktop-field">
                            <label>Roles</label>
                            <div id="roles-picker" class="desktop-tag-picker"></div>
                            <select class="d-none" name="roles[]" id="roles" multiple>
                                @foreach($opciones['roles'] as $rol)
                                    <option value="{{ $rol->rol_id }}">{{ $rol->rol_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="desktop-field">
                            <label>Sucursales</label>
                            <div id="sucursales-picker" class="desktop-tag-picker"></div>
                            <select class="d-none" name="sucursales[]" id="sucursales" multiple>
                                @foreach($opciones['sucursales'] as $sucursal)
                                    <option value="{{ $sucursal->scl_id }}">{{ $sucursal->scl_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="desktop-modal__foot">
                    <button type="button" class="desktop-btn desktop-btn--default" data-close-modal>Cancelar</button>
                    <button type="submit" class="desktop-btn desktop-btn--primary">Guardar usuario</button>
                </div>
            </form>
        </div>
    </div>

    <div class="desktop-feedback" id="desktop-feedback"></div>
@endsection

@push('desktop-vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endpush

@push('desktop-scripts')
    <script>
        (function () {
            const $table = $('#desktop-usuarios-table');
            const $modal = $('#desktop-usuario-modal');
            const $form = $('#desktop-usuario-form');
            const $feedback = $('#desktop-feedback');
            const tagPickers = {};
            let usuariosTable = null;

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

            function initials(name) {
                return String(name || '?').trim().split(/\s+/).slice(0, 2).map(function (p) { return p.charAt(0); }).join('').toUpperCase() || '?';
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

            function renderPills(values, variant, maxItems) {
                const items = values || [];
                const visible = items.slice(0, maxItems || items.length);

                if (!visible.length) {
                    return '<span class="desktop-list__meta">Sin datos</span>';
                }

                const chips = visible.map(function (value) {
                    return '<span class="desktop-pill desktop-pill--' + variant + '">' + escapeHtml(value) + '</span>';
                });

                if (items.length > visible.length) {
                    chips.push('<span class="desktop-pill desktop-pill--more">+' + (items.length - visible.length) + '</span>');
                }

                return '<div class="desktop-pill-list">' + chips.join('') + '</div>';
            }

            function renderActions(row) {
                const toggleTo = row.usr_estatus === 'activo' ? 'inactivo' : 'activo';
                const toggleText = row.usr_estatus === 'activo' ? 'Inactivar' : 'Activar';

                return '' +
                    '<div class="desktop-rowmenu">' +
                        '<button type="button" class="desktop-overflow" data-overflow aria-haspopup="true" aria-expanded="false" aria-label="Más acciones">' + ICONS.dots + '</button>' +
                        '<div class="desktop-menu">' +
                            '<button type="button" class="desktop-menu__item btn-editar-usuario" data-id="' + row.usr_id + '">' + ICONS.edit + 'Editar</button>' +
                            '<div class="desktop-menu__divider"></div>' +
                            '<button type="button" class="desktop-menu__item btn-toggle-usuario" data-id="' + row.usr_id + '" data-estatus="' + toggleTo + '">' + ICONS.toggle + toggleText + '</button>' +
                        '</div>' +
                    '</div>';
            }

            function initTagPicker(config) {
                const $select = $(config.selectId);
                const $container = $(config.containerId);
                const options = $select.find('option').map(function () {
                    return { value: String($(this).val()), label: $(this).text() };
                }).get();
                let selected = new Set(($select.val() || []).map(String));

                function syncSelect() {
                    $select.val(Array.from(selected));
                    $select.trigger('change');
                }

                function availableOptions(search) {
                    const term = (search || '').trim().toLowerCase();

                    return options.filter(function (item) {
                        if (selected.has(item.value)) return false;
                        return term === '' || item.label.toLowerCase().includes(term);
                    });
                }

                function render(searchTerm, keepFocus) {
                    const tagsHtml = Array.from(selected).map(function (value) {
                        const option = options.find(function (item) { return item.value === value; });
                        if (!option) return '';

                        return '' +
                            '<span class="desktop-tag-picker__chip" data-value="' + escapeHtml(value) + '">' +
                                escapeHtml(option.label) +
                                '<button type="button" aria-label="Quitar">&times;</button>' +
                            '</span>';
                    }).join('');

                    const dropdownOptions = availableOptions(searchTerm).slice(0, 20);
                    const dropdownHtml = dropdownOptions.length
                        ? dropdownOptions.map(function (item) {
                            return '<div class="desktop-tag-picker__option" data-value="' + escapeHtml(item.value) + '">' + escapeHtml(item.label) + '</div>';
                        }).join('')
                        : '<div class="desktop-tag-picker__option" style="color:var(--text-3);cursor:default;">Sin coincidencias</div>';

                    $container.html(
                        '<div class="desktop-tag-picker__tags">' + tagsHtml +
                            '<input type="text" class="desktop-tag-picker__input" placeholder="' + escapeHtml(config.placeholder || 'Escribe para buscar') + '">' +
                        '</div>' +
                        '<div class="desktop-tag-picker__dropdown">' + dropdownHtml + '</div>'
                    );

                    const $input = $container.find('.desktop-tag-picker__input');
                    $input.val(searchTerm || '');

                    if (keepFocus) {
                        const inputEl = $input.get(0);
                        if (inputEl) {
                            inputEl.focus();
                            const pos = (searchTerm || '').length;
                            inputEl.setSelectionRange(pos, pos);
                        }
                    }
                }

                function setValues(values) {
                    selected = new Set((values || []).map(String));
                    render('', false);
                    syncSelect();
                }

                render('', false);
                syncSelect();

                $container.on('click', function () {
                    $container.find('.desktop-tag-picker__input').trigger('focus');
                });

                $container.on('focus', '.desktop-tag-picker__input', function () {
                    $container.addClass('is-focused');
                    $container.find('.desktop-tag-picker__dropdown').show();
                });

                $container.on('blur', '.desktop-tag-picker__input', function () {
                    window.setTimeout(function () {
                        $container.removeClass('is-focused');
                        $container.find('.desktop-tag-picker__dropdown').hide();
                    }, 150);
                });

                $container.on('input', '.desktop-tag-picker__input', function () {
                    render($(this).val(), true);
                    $container.find('.desktop-tag-picker__dropdown').show();
                });

                $container.on('keydown', '.desktop-tag-picker__input', function (e) {
                    if (e.key !== 'Enter') return;
                    e.preventDefault();

                    const first = $container.find('.desktop-tag-picker__option[data-value]').first();
                    if (first.length) first.trigger('click');
                });

                $container.on('click', '.desktop-tag-picker__option[data-value]', function () {
                    selected.add(String($(this).data('value')));
                    render('', true);
                    syncSelect();
                });

                $container.on('click', '.desktop-tag-picker__chip button', function (e) {
                    e.stopPropagation();
                    selected.delete(String($(this).closest('.desktop-tag-picker__chip').data('value')));
                    render('', true);
                    syncSelect();
                });

                tagPickers[config.name] = { setValues };
            }

            function actualizarOpcionesPredeterminada() {
                const selected = $('#sucursales').val() || [];
                const $pred = $('#usc_scl_predeterminada');
                $pred.empty();

                selected.forEach(function (value) {
                    const text = $('#sucursales option[value="' + value + '"]').text();
                    $pred.append('<option value="' + value + '">' + text + '</option>');
                });
            }

            function limpiarFormularioUsuario() {
                $form.get(0).reset();
                $('#usr_id').val('');
                $('#usr_password').attr('required', 'required').val('');
                if (tagPickers.roles) tagPickers.roles.setValues([]);
                if (tagPickers.sucursales) tagPickers.sucursales.setValues([]);
                $('#usc_scl_predeterminada').empty();
                $('#desktop-usuario-modal-title').text('Nuevo usuario');
            }

            function initTable() {
                usuariosTable = $table.DataTable({
                    ajax: {
                        url: '{{ route('seguridad.usuarios.data') }}',
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
                        info: 'Mostrando _START_ a _END_ de _TOTAL_ usuarios',
                        infoEmpty: 'Mostrando 0 a 0 de 0 usuarios',
                        infoFiltered: '(filtrado de _MAX_ usuarios)',
                        paginate: { first: '«', last: '»', next: '›', previous: '‹' },
                        processing: 'Cargando...',
                        emptyTable: 'No hay usuarios disponibles',
                        zeroRecords: 'No se encontraron usuarios'
                    },
                    columns: [
                        {
                            data: 'usr_nombre',
                            render: function (value, type, row) {
                                return '' +
                                    '<div class="desktop-cell-primary">' +
                                        '<span class="desktop-avatar-sm">' + escapeHtml(initials(value)) + '</span>' +
                                        '<span>' +
                                            '<span class="desktop-list__name">' + escapeHtml(value) + '</span>' +
                                            '<span class="desktop-list__meta">ID ' + escapeHtml(row.usr_id) + '</span>' +
                                        '</span>' +
                                    '</div>';
                            }
                        },
                        { data: 'usr_usuario', render: function (value) { return '<span style="font-weight:600;">' + escapeHtml(value) + '</span>'; } },
                        { data: 'usr_email', render: function (value) { return value ? escapeHtml(value) : '<span class="desktop-list__meta">Sin correo</span>'; } },
                        { data: 'roles', render: function (value) { return renderPills(value, 'brand', 3); } },
                        { data: 'sucursales', render: function (value) { return renderPills(value, 'neutral', 3); } },
                        {
                            data: 'usr_estatus',
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

                $('#usuarios-search').on('input', function () {
                    usuariosTable.search(this.value).draw();
                });

                $('#usuarios-length').on('change', function () {
                    usuariosTable.page.len(Number(this.value)).draw();
                });
            }

            function renderCustomFooter() {
                if (!usuariosTable) return;

                const info = usuariosTable.page.info();
                const total = info.recordsDisplay;

                if (!total) {
                    $('#desktop-usuarios-info').text('Mostrando 0 usuarios');
                    $('#desktop-usuarios-pagination').empty();
                    return;
                }

                $('#desktop-usuarios-info').text(
                    'Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + total + ' usuarios'
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

                $('#desktop-usuarios-pagination').html(html);
            }

            function cargarUsuario(id) {
                $.getJSON('{{ url('/seguridad/usuarios') }}/' + id)
                    .done(function (response) {
                        const data = response.data;
                        limpiarFormularioUsuario();
                        $('#desktop-usuario-modal-title').text('Editar usuario');
                        $('#usr_id').val(data.usr_id);
                        $('[name="usr_nombre"]').val(data.usr_nombre);
                        $('[name="usr_usuario"]').val(data.usr_usuario);
                        $('[name="usr_email"]').val(data.usr_email);
                        $('[name="usr_estatus"]').val(data.usr_estatus);
                        $('#usr_password').removeAttr('required').val('');
                        if (tagPickers.roles) tagPickers.roles.setValues((data.roles || []).map(String));
                        if (tagPickers.sucursales) tagPickers.sucursales.setValues((data.sucursales || []).map(String));
                        actualizarOpcionesPredeterminada();

                        if (data.usc_scl_predeterminada) {
                            $('#usc_scl_predeterminada').val(String(data.usc_scl_predeterminada));
                        } else if ((data.sucursales || []).length > 0) {
                            $('#usc_scl_predeterminada').val(String(data.sucursales[0]));
                        }

                        openModal();
                    })
                    .fail(function () {
                        showFeedback('error', 'No fue posible cargar la información del usuario.');
                    });
            }

            function cambiarEstatus(id, estatus) {
                $.ajax({
                    url: '{{ url('/seguridad/usuarios') }}/' + id + '/estatus',
                    method: 'PATCH',
                    data: { usr_estatus: estatus }
                }).done(function (response) {
                    showFeedback('success', response.message || 'Estatus actualizado correctamente.');
                    usuariosTable.ajax.reload(null, false);
                }).fail(function (xhr) {
                    showFeedback('error', xhr.responseJSON?.message || 'No se pudo cambiar el estatus.');
                });
            }

            initTagPicker({ name: 'roles', selectId: '#roles', containerId: '#roles-picker', placeholder: 'Agregar rol' });
            initTagPicker({ name: 'sucursales', selectId: '#sucursales', containerId: '#sucursales-picker', placeholder: 'Agregar sucursal' });
            initTable();

            $('#sucursales').on('change', actualizarOpcionesPredeterminada);

            $('#btn-nuevo-usuario').on('click', function () {
                limpiarFormularioUsuario();
                openModal();
            });

            $('#btn-recargar-usuarios').on('click', function () {
                usuariosTable.ajax.reload();
            });

            $('#desktop-usuarios-pagination').on('click', '.desktop-pager__btn', function () {
                const page = $(this).data('page');
                if ($(this).is(':disabled')) return;
                usuariosTable.page(page).draw('page');
            });

            $(document).on('click', '[data-close-modal]', function () {
                closeModal();
            });

            $modal.on('click', function (event) {
                if (event.target === this) closeModal();
            });

            $table.on('click', '.btn-editar-usuario', function () {
                cargarUsuario($(this).data('id'));
            });

            $table.on('click', '.btn-toggle-usuario', function () {
                const id = $(this).data('id');
                const estatus = $(this).data('estatus');

                DesktopUI.confirm({ title: 'Cambiar estatus', message: '¿Deseas cambiar el estatus de este usuario?' }).then(function (ok) {
                    if (!ok) return;
                    cambiarEstatus(id, estatus);
                });
            });

            $form.on('submit', function (event) {
                event.preventDefault();

                const rolesSeleccionados = $('#roles').val() || [];
                const sucursalesSeleccionadas = $('#sucursales').val() || [];

                if (rolesSeleccionados.length === 0) {
                    showFeedback('error', 'Debes seleccionar al menos un rol.');
                    return;
                }

                if (sucursalesSeleccionadas.length === 0) {
                    showFeedback('error', 'Debes seleccionar al menos una sucursal.');
                    return;
                }

                const id = $('#usr_id').val();
                const url = id ? '{{ url('/seguridad/usuarios') }}/' + id : '{{ route('seguridad.usuarios.store') }}';

                $.ajax({
                    url: url,
                    method: id ? 'PUT' : 'POST',
                    data: $form.serialize()
                }).done(function (response) {
                    closeModal();
                    showFeedback('success', response.message || 'Usuario guardado correctamente.');
                    usuariosTable.ajax.reload(null, false);
                }).fail(function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        const firstError = Object.values(xhr.responseJSON.errors)[0][0];
                        showFeedback('error', firstError);
                        return;
                    }

                    showFeedback('error', xhr.responseJSON?.message || 'No fue posible guardar el usuario.');
                });
            });
        })();
    </script>
@endpush
