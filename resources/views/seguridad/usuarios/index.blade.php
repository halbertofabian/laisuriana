@extends('layouts.app')

@section('title', 'Usuarios')

@push('vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <style>
        .datatable-toolbar .dataTables_filter label,
        .datatable-toolbar .dataTables_length label { font-size:.82rem;font-weight:500;color:var(--ls-text-muted); }
        .datatable-toolbar .dataTables_filter input,
        .datatable-toolbar .dataTables_length select { min-height:2.1rem;border-radius:var(--ls-radius);border:1px solid var(--ls-border);font-size:.84rem; }
        .dataTables_paginate .paginate_button { border-radius:var(--ls-radius-sm)!important;font-size:.8rem!important; }
        .dataTables_paginate .paginate_button.current,
        .dataTables_paginate .paginate_button.current:hover { background:var(--ls-accent)!important;color:#fff!important;border-color:var(--ls-accent)!important; }

        .tag-chip { display:inline-flex;align-items:center;border-radius:.35rem;padding:.18rem .55rem;font-size:.73rem;font-weight:600;line-height:1.4;border:1px solid transparent; }
        .tag-chip.role   { background:rgba(99,91,255,.08);color:#4f46e5;border-color:rgba(99,91,255,.2); }
        .tag-chip.branch { background:rgba(26,158,109,.08);color:#1a9e6d;border-color:rgba(26,158,109,.2); }
        .tag-chip.more   { background:rgba(233,155,62,.1);color:#c97b1a;border-color:rgba(233,155,62,.25); }
        .tag-list { display:flex;flex-wrap:wrap;gap:.3rem; }

        .table-row-actions .dropdown-menu { min-width:12rem;border-radius:var(--ls-radius-lg);border:1px solid var(--ls-border);box-shadow:var(--ls-shadow-lg);padding:.35rem; }
        .table-row-actions .dropdown-toggle { font-size:.78rem;font-weight:600;padding:.28rem .65rem;border-radius:var(--ls-radius-sm); }
        .table-row-actions .dropdown-item { display:flex;align-items:center;gap:.45rem;font-size:.82rem;font-weight:500;border-radius:var(--ls-radius-sm);padding:.45rem .65rem; }
        .table-row-actions .dropdown-item:hover { background:var(--ls-surface-2); }

        .tag-picker { border:1px solid var(--ls-border);border-radius:var(--ls-radius);min-height:42px;padding:.35rem;background:var(--ls-surface);position:relative;box-shadow:0 1px 2px rgba(10,37,64,.04);transition:border-color .15s,box-shadow .15s; }
        .tag-picker.is-focused { border-color:var(--ls-accent);box-shadow:0 0 0 3px rgba(99,91,255,.12); }
        .tag-picker-tags { display:flex;flex-wrap:wrap;gap:.3rem;margin-bottom:.3rem; }
        .tag-picker-chip { display:inline-flex;align-items:center;gap:.3rem;border-radius:var(--ls-radius-sm);background:var(--ls-accent);color:#fff;padding:.22rem .5rem;font-weight:600;font-size:.8rem;line-height:1.3; }
        .tag-picker-chip button { border:0;background:rgba(0,0,0,.2);color:#fff;border-radius:999px;width:.95rem;height:.95rem;padding:0;line-height:.95rem;font-size:.68rem;cursor:pointer; }
        .tag-picker-input { width:100%;border:0;outline:none;padding:.12rem .2rem;font-size:.875rem;background:transparent;color:var(--ls-text-primary); }
        .tag-picker-dropdown { position:absolute;left:0;right:0;top:calc(100% + .2rem);border:1px solid var(--ls-border);border-radius:var(--ls-radius-lg);background:var(--ls-surface);box-shadow:var(--ls-shadow-lg);z-index:1080;max-height:180px;overflow:auto;display:none; }
        .tag-picker-option { padding:.48rem .75rem;cursor:pointer;font-size:.875rem;color:var(--ls-text-secondary); }
        .tag-picker-option:hover { background:var(--ls-surface-2);color:var(--ls-text-primary); }
    </style>
@endpush

@section('content')
<x-section-header
    eyebrow="Seguridad"
    icon="tabler-users-group"
    title="Usuarios"
    subtitle="Gestiona los usuarios, sus roles y sucursales asignadas."
>
    <x-slot:actions>
        @if(auth()->user()?->tienePermiso('usuario.crear'))
            <button class="btn btn-primary btn-sm" id="btn-nuevo-usuario">
                <i class="ti tabler-plus me-1" style="font-size:.85rem;"></i>Nuevo usuario
            </button>
        @endif
    </x-slot:actions>
</x-section-header>

<div class="card">
    <div class="card-header d-flex align-items-center gap-3 flex-wrap" id="usuarios-toolbar-wrapper">
        <span></span>
    </div>
    <div class="table-responsive">
        <table id="usuarios-table" class="table mb-0">
            <thead>
            <tr>
                <th>Nombre</th>
                <th>Usuario</th>
                <th>Correo</th>
                <th>Roles</th>
                <th>Sucursales</th>
                <th>Estatus</th>
                <th style="width:1%;">Acciones</th>
            </tr>
            </thead>
        </table>
    </div>
</div>

{{-- MODAL: CREAR / EDITAR USUARIO --}}
<div class="modal fade" id="usuarioModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="usuario-form" data-ls-autocomplete="admin">
                <div class="modal-header">
                    <h5 class="modal-title" id="usuarioModalTitle">Nuevo usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="usr_id" id="usr_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre completo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="usr_nombre" placeholder="Ej. Ana García" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Usuario <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="usr_usuario" placeholder="Ej. ana.garcia" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Correo electrónico</label>
                            <input type="email" class="form-control" name="usr_email" placeholder="correo@empresa.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contraseña <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="usr_password" id="usr_password" placeholder="••••••••">
                            <p style="font-size:.76rem;color:var(--ls-text-muted);margin:0;">En edición, dejar vacío para conservar la actual.</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estatus <span class="text-danger">*</span></label>
                            <select class="form-select" name="usr_estatus" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sucursal predeterminada</label>
                            <select class="form-select" name="usc_scl_predeterminada" id="usc_scl_predeterminada"></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Roles <span class="text-danger">*</span></label>
                            <div id="roles-picker" class="tag-picker"></div>
                            <select class="form-select d-none" name="roles[]" id="roles" multiple>
                                @foreach($opciones['roles'] as $rol)
                                    <option value="{{ $rol->rol_id }}">{{ $rol->rol_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sucursales <span class="text-danger">*</span></label>
                            <div id="sucursales-picker" class="tag-picker"></div>
                            <select class="form-select d-none" name="sucursales[]" id="sucursales" multiple>
                                @foreach($opciones['sucursales'] as $sucursal)
                                    <option value="{{ $sucursal->scl_id }}">{{ $sucursal->scl_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endpush

@push('page-scripts')
<script>
(function () {
    const usuarioModal = new bootstrap.Modal(document.getElementById('usuarioModal'));
    const datatableLanguage = {
        lengthMenu: '_MENU_ registros por página',
        zeroRecords: 'No se encontraron registros',
        info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
        infoEmpty: 'Mostrando 0 a 0 de 0 registros',
        infoFiltered: '(filtrado de _MAX_ registros totales)',
        search: 'Buscar:', paginate: { first: '«', last: '»', next: '›', previous: '‹' }
    };
    const tagPickers = {};

    function escapeHtml(v) {
        return String(v || '').replaceAll('&','&amp;').replaceAll('<','&lt;')
            .replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#39;');
    }

    function renderTags(list, type, maxItems) {
        const values = list || [];
        const visibles = values.slice(0, maxItems || values.length);
        const chips = visibles.map(function (v) {
            return '<span class="tag-chip ' + type + '">' + escapeHtml(v) + '</span>';
        });
        if (values.length > visibles.length) {
            chips.push('<span class="tag-chip more">+' + (values.length - visibles.length) + ' más</span>');
        }
        return '<div class="tag-list">' + chips.join('') + '</div>';
    }

    function renderActionsDropdown(items) {
        const options = items.map(function (item) {
            const extra = Object.entries(item.data || {}).map(function (e) {
                return ' data-' + e[0] + '="' + escapeHtml(e[1]) + '"';
            }).join('');
            return '<li><button type="button" class="dropdown-item ' + item.className + '"' + extra + '>' +
                '<i class="icon-base ti ' + item.icon + '"></i>' + escapeHtml(item.label) + '</button></li>';
        }).join('');
        return '<div class="dropdown table-row-actions">' +
            '<button class="btn btn-sm btn-label-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">' +
            '<i class="icon-base ti tabler-dots-vertical me-1"></i>Acciones</button>' +
            '<ul class="dropdown-menu dropdown-menu-end">' + options + '</ul></div>';
    }

    function initTagPicker(config) {
        const $select    = $(config.selectId);
        const $container = $(config.containerId);
        const options    = $select.find('option').map(function () {
            return { value: String($(this).val()), label: $(this).text() };
        }).get();
        let selected = new Set(($select.val() || []).map(String));

        function syncSelect() { $select.val(Array.from(selected)); $select.trigger('change'); }

        function availableOptions(search) {
            const term = (search || '').trim().toLowerCase();
            return options.filter(function (item) {
                if (selected.has(item.value)) return false;
                return term === '' || item.label.toLowerCase().includes(term);
            });
        }

        function render(searchTerm, keepFocus) {
            const tagsHtml = Array.from(selected).map(function (value) {
                const option = options.find(function (i) { return i.value === value; });
                if (!option) return '';
                return '<span class="tag-picker-chip" data-value="' + escapeHtml(value) + '">' +
                    escapeHtml(option.label) +
                    '<button type="button" aria-label="Quitar">×</button></span>';
            }).join('');
            const dropdownOptions = availableOptions(searchTerm).slice(0, 20);
            const dropdownHtml = dropdownOptions.length
                ? dropdownOptions.map(function (item) {
                    return '<div class="tag-picker-option" data-value="' + escapeHtml(item.value) + '">' + escapeHtml(item.label) + '</div>';
                }).join('')
                : '<div class="tag-picker-option text-body-secondary">Sin coincidencias</div>';
            $container.html(
                '<div class="tag-picker-tags">' + tagsHtml + '</div>' +
                '<input type="text" class="tag-picker-input" placeholder="' + escapeHtml(config.placeholder || 'Escribe para buscar') + '">' +
                '<div class="tag-picker-dropdown">' + dropdownHtml + '</div>'
            );
            const $input = $container.find('.tag-picker-input');
            $input.val(searchTerm || '');
            if (keepFocus) {
                const inputEl = $input.get(0);
                if (inputEl) { inputEl.focus(); const pos = (searchTerm || '').length; inputEl.setSelectionRange(pos, pos); }
            }
        }

        function setValues(values) { selected = new Set((values || []).map(String)); render('', false); syncSelect(); }

        render('', false); syncSelect();
        $container.on('click', function () { $container.find('.tag-picker-input').trigger('focus'); });
        $container.on('focus', '.tag-picker-input', function () { $container.addClass('is-focused'); $container.find('.tag-picker-dropdown').show(); });
        $container.on('blur', '.tag-picker-input', function () {
            window.setTimeout(function () { $container.removeClass('is-focused'); $container.find('.tag-picker-dropdown').hide(); }, 150);
        });
        $container.on('input', '.tag-picker-input', function () { render($(this).val(), true); $container.find('.tag-picker-dropdown').show(); });
        $container.on('keydown', '.tag-picker-input', function (e) {
            if (e.key !== 'Enter') return;
            e.preventDefault();
            const first = $container.find('.tag-picker-option[data-value]').first();
            if (first.length) first.trigger('click');
        });
        $container.on('click', '.tag-picker-option[data-value]', function () {
            selected.add(String($(this).data('value'))); render('', true); syncSelect();
        });
        $container.on('click', '.tag-picker-chip button', function (e) {
            e.stopPropagation();
            selected.delete(String($(this).closest('.tag-picker-chip').data('value'))); render('', true); syncSelect();
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
        $('#usuario-form')[0].reset();
        $('#usr_id').val('');
        if (tagPickers.roles) tagPickers.roles.setValues([]);
        if (tagPickers.sucursales) tagPickers.sucursales.setValues([]);
        $('#usc_scl_predeterminada').empty();
        $('#usuarioModalTitle').text('Nuevo usuario');
        $('#usr_password').attr('required', 'required');
    }

    function cargarTabla() {
        AppUI.showLoader();
        $.getJSON('{{ route('seguridad.usuarios.data') }}').done(function (response) {
            if ($.fn.DataTable.isDataTable('#usuarios-table')) {
                $('#usuarios-table').DataTable().clear().destroy();
            }
            $('#usuarios-table').DataTable({
                data: response.data || [],
                order: [[0, 'asc']],
                responsive: true, autoWidth: false, pageLength: 10, lengthMenu: [10, 25, 50],
                language: datatableLanguage,
                initComplete: function () {
                    const wrapper = $('#usuarios-table_wrapper');
                    wrapper.addClass('datatable-toolbar');
                    const filter = wrapper.find('.dataTables_filter').detach();
                    const length = wrapper.find('.dataTables_length').detach();
                    const lengthHtml = length.length ? length[0].outerHTML : '';
                    const filterHtml = filter.length ? filter[0].outerHTML : '';
                    $('#usuarios-toolbar-wrapper').html(
                        '<div class="d-flex align-items-center gap-3 w-100 flex-wrap">' +
                        lengthHtml + filterHtml + '</div>'
                    );
                },
                columns: [
                    { data: 'usr_nombre', render: function (v) { return '<div class="fw-semibold">' + escapeHtml(v) + '</div>'; } },
                    { data: 'usr_usuario', render: function (v) { return '<span class="fw-semibold text-primary">' + escapeHtml(v) + '</span>'; } },
                    { data: 'usr_email', defaultContent: '-', render: function (v) { return escapeHtml(v || '-'); } },
                    { data: 'roles', render: function (v) { return renderTags(v, 'role', 2); } },
                    { data: 'sucursales', render: function (v) { return renderTags(v, 'branch', 2); } },
                    { data: 'usr_estatus', render: function (v) {
                        return v === 'activo'
                            ? '<span class="ls-badge ls-badge-success">Activo</span>'
                            : '<span class="ls-badge ls-badge-danger">Inactivo</span>';
                    }},
                    { data: null, orderable: false, searchable: false, render: function (row) {
                        const toggleTo   = row.usr_estatus === 'activo' ? 'inactivo' : 'activo';
                        const toggleText = row.usr_estatus === 'activo' ? 'Inactivar' : 'Activar';
                        const toggleIcon = toggleTo === 'activo' ? 'tabler-user-check' : 'tabler-user-off';
                        return renderActionsDropdown([
                            { className: 'btn-editar-usuario', label: 'Editar', icon: 'tabler-edit', data: { id: row.usr_id } },
                            { className: 'btn-toggle-usuario', label: toggleText, icon: toggleIcon, data: { id: row.usr_id, estatus: toggleTo } }
                        ]);
                    }}
                ]
            });
        }).fail(function () { AppUI.showMessage('Error', 'No fue posible cargar los usuarios.'); })
        .always(function () { AppUI.hideLoader(); });
    }

    $('#btn-nuevo-usuario').on('click', function () { limpiarFormularioUsuario(); usuarioModal.show(); });
    $('#sucursales').on('change', actualizarOpcionesPredeterminada);

    initTagPicker({ name: 'roles',     selectId: '#roles',     containerId: '#roles-picker',     placeholder: 'Agregar rol' });
    initTagPicker({ name: 'sucursales', selectId: '#sucursales', containerId: '#sucursales-picker', placeholder: 'Agregar sucursal' });

    $('#usuarios-table').on('click', '.btn-editar-usuario', function () {
        const id = $(this).data('id');
        AppUI.showLoader();
        $.getJSON('{{ url('/seguridad/usuarios') }}/' + id).done(function (response) {
            const d = response.data;
            limpiarFormularioUsuario();
            $('#usuarioModalTitle').text('Editar usuario');
            $('#usr_id').val(d.usr_id);
            $('[name="usr_nombre"]').val(d.usr_nombre);
            $('[name="usr_usuario"]').val(d.usr_usuario);
            $('[name="usr_email"]').val(d.usr_email);
            $('[name="usr_estatus"]').val(d.usr_estatus);
            if (tagPickers.roles)     tagPickers.roles.setValues((d.roles || []).map(String));
            if (tagPickers.sucursales) tagPickers.sucursales.setValues((d.sucursales || []).map(String));
            actualizarOpcionesPredeterminada();
            if (d.usc_scl_predeterminada) {
                $('#usc_scl_predeterminada').val(String(d.usc_scl_predeterminada));
            } else if (d.sucursales.length > 0) {
                $('#usc_scl_predeterminada').val(String(d.sucursales[0]));
            }
            $('#usr_password').removeAttr('required').val('');
            usuarioModal.show();
        }).fail(function () { AppUI.showMessage('Error', 'No fue posible cargar la información del usuario.'); })
        .always(function () { AppUI.hideLoader(); });
    });

    $('#usuarios-table').on('click', '.btn-toggle-usuario', function () {
        const id     = $(this).data('id');
        const estatus = $(this).data('estatus');
        AppUI.showLoader();
        $.ajax({ url: '{{ url('/seguridad/usuarios') }}/' + id + '/estatus', method: 'PATCH', data: { usr_estatus: estatus } })
            .done(function (r) { AppUI.showMessage('Éxito', r.message); cargarTabla(); })
            .fail(function (xhr) { AppUI.showMessage('Error', xhr.responseJSON?.message || 'No se pudo cambiar el estatus.'); })
            .always(function () { AppUI.hideLoader(); });
    });

    $('#usuario-form').on('submit', function (event) {
        event.preventDefault();
        const rolesSeleccionados      = $('#roles').val() || [];
        const sucursalesSeleccionadas = $('#sucursales').val() || [];
        if (rolesSeleccionados.length === 0) { AppUI.showMessage('Validación', 'Debes seleccionar al menos un rol.'); return; }
        if (sucursalesSeleccionadas.length === 0) { AppUI.showMessage('Validación', 'Debes seleccionar al menos una sucursal.'); return; }
        const id     = $('#usr_id').val();
        const url    = id ? '{{ url('/seguridad/usuarios') }}/' + id : '{{ route('seguridad.usuarios.store') }}';
        AppUI.showLoader();
        $.ajax({ url, method: id ? 'PUT' : 'POST', data: $(this).serialize() })
            .done(function (r) { usuarioModal.hide(); AppUI.showMessage('Éxito', r.message); cargarTabla(); })
            .fail(function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    AppUI.showMessage('Validación', Object.values(xhr.responseJSON.errors)[0][0]); return;
                }
                AppUI.showMessage('Error', xhr.responseJSON?.message || 'No fue posible guardar el usuario.');
            }).always(function () { AppUI.hideLoader(); });
    });

    cargarTabla();
})();
</script>
@endpush
