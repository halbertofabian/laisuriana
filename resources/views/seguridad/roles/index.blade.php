@extends('layouts.app')

@section('title', 'Roles')

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

        .table-row-actions .dropdown-menu { min-width:12rem;border-radius:var(--ls-radius-lg);border:1px solid var(--ls-border);box-shadow:var(--ls-shadow-lg);padding:.35rem; }
        .table-row-actions .dropdown-toggle { font-size:.78rem;font-weight:600;padding:.28rem .65rem;border-radius:var(--ls-radius-sm); }
        .table-row-actions .dropdown-item { display:flex;align-items:center;gap:.45rem;font-size:.82rem;font-weight:500;border-radius:var(--ls-radius-sm);padding:.45rem .65rem; }
        .table-row-actions .dropdown-item:hover { background:var(--ls-surface-2); }
        .tag-chip { display:inline-flex;align-items:center;border-radius:.35rem;padding:.18rem .55rem;font-size:.73rem;font-weight:600;line-height:1.4;border:1px solid transparent; }
        .tag-chip.permission { background:var(--ls-surface-3);color:var(--ls-text-muted);border-color:var(--ls-border); }
        .tag-chip.more { background:rgba(233,155,62,.1);color:#c97b1a;border-color:rgba(233,155,62,.25); }
        .tag-list { display:flex;flex-wrap:wrap;gap:.3rem; }

        .perm-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:.75rem; }
        .perm-group { min-width:0; }
        .perm-group-head { padding-bottom:.35rem;border-bottom:1px solid var(--ls-border);font-size:.72rem;letter-spacing:.05em;font-weight:700;text-transform:uppercase;color:var(--ls-text-muted); }
        .perm-group-body { padding-top:.5rem;max-height:200px;overflow:auto; }
        .perm-group .form-check { margin-bottom:.45rem; }
        .perm-group .form-check:last-child { margin-bottom:0; }
        .perm-group .form-check-input:checked { background-color:var(--ls-accent);border-color:var(--ls-accent); }
        .perm-group .form-check-label { font-size:.82rem;color:var(--ls-text-secondary); }
    </style>
@endpush

@section('content')
<x-section-header
    eyebrow="Seguridad"
    icon="tabler-shield-check"
    title="Roles"
    subtitle="Define los roles y sus permisos de acceso al sistema."
>
    <x-slot:actions>
        @if(auth()->user()?->tienePermiso('rol.crear'))
            <button class="btn btn-primary btn-sm" id="btn-nuevo-rol">
                <i class="ti tabler-plus me-1" style="font-size:.85rem;"></i>Nuevo rol
            </button>
        @endif
    </x-slot:actions>
</x-section-header>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap" id="roles-toolbar-wrapper">
        <span></span>
    </div>
    <div class="table-responsive">
        <table id="roles-table" class="table mb-0">
            <thead>
            <tr>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Permisos asignados</th>
                <th>Estatus</th>
                <th style="width:1%;">Acciones</th>
            </tr>
            </thead>
        </table>
    </div>
</div>

{{-- MODAL: CREAR / EDITAR ROL --}}
<div class="modal fade" id="rolModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form id="rol-form">
                <div class="modal-header">
                    <h5 class="modal-title" id="rolModalTitle">Nuevo rol</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="rol_id" id="rol_id">
                    <div class="row g-3 mb-4">
                        <div class="col-md-5">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="rol_nombre" placeholder="Ej. Supervisor de ventas" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Descripción</label>
                            <input type="text" class="form-control" name="rol_descripcion" placeholder="Ej. Puede ver reportes y autorizar cancelaciones">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Estatus <span class="text-danger">*</span></label>
                            <select class="form-select" name="rol_estatus" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                        <span style="font-size:.78rem;font-weight:700;color:var(--ls-text-primary);text-transform:uppercase;letter-spacing:.05em;">Permisos</span>
                        <span style="font-size:.76rem;color:var(--ls-text-muted);">Selecciona las acciones permitidas por módulo</span>
                    </div>

                    @php($permisosAgrupados = $permisos->groupBy('prm_modulo'))
                    <div class="perm-grid">
                        @foreach($permisosAgrupados as $modulo => $permisosDelModulo)
                            <div class="perm-group">
                                <div class="perm-group-head">{{ ucfirst(str_replace('_', ' ', $modulo)) }}</div>
                                <div class="perm-group-body">
                                    @foreach($permisosDelModulo as $permiso)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="permisos[]"
                                                id="permiso_{{ $permiso->prm_id }}" value="{{ $permiso->prm_id }}">
                                            <label class="form-check-label" for="permiso_{{ $permiso->prm_id }}"
                                                title="{{ $permiso->prm_clave }}">
                                                {{ $permiso->prm_descripcion }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btn-guardar-rol">Guardar rol</button>
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
    const rolModal = new bootstrap.Modal(document.getElementById('rolModal'));
    const datatableLanguage = {
        lengthMenu: '_MENU_ registros por página',
        zeroRecords: 'No se encontraron registros',
        info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
        infoEmpty: 'Mostrando 0 a 0 de 0 registros',
        infoFiltered: '(filtrado de _MAX_ registros totales)',
        search: 'Buscar:', paginate: { first: '«', last: '»', next: '›', previous: '‹' }
    };

    function escapeHtml(v) {
        return String(v || '').replaceAll('&','&amp;').replaceAll('<','&lt;')
            .replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#39;');
    }

    function renderPermisosCompactos(permisos) {
        const items = permisos || [];
        if (items.length === 0) return '<span class="text-body-secondary" style="text-transform:none;">Sin permisos</span>';
        const visibles = items.slice(0, 4).map(function (p) {
            const d = escapeHtml(p.descripcion || '');
            return '<span class="tag-chip permission" title="' + d + '">' + d + '</span>';
        });
        if (items.length > 4) visibles.push('<span class="tag-chip more">+' + (items.length - 4) + ' más</span>');
        return '<div class="tag-list">' + visibles.join('') + '</div>';
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

    function limpiarFormulario() {
        $('#rol-form')[0].reset();
        $('#rol_id').val('');
        $('input[name="permisos[]"]').prop('checked', false);
        $('#rolModalTitle').text('Nuevo rol');
        $('#btn-guardar-rol').text('Guardar rol');
    }

    function cargarTabla() {
        AppUI.showLoader();
        $.getJSON('{{ route('seguridad.roles.data') }}').done(function (response) {
            if ($.fn.DataTable.isDataTable('#roles-table')) {
                $('#roles-table').DataTable().clear().destroy();
            }
            $('#roles-table').DataTable({
                data: response.data || [],
                order: [[0, 'asc']],
                responsive: true, autoWidth: false, pageLength: 10, lengthMenu: [10, 25, 50],
                language: datatableLanguage,
                initComplete: function () {
                    const wrapper = $('#roles-table_wrapper');
                    wrapper.addClass('datatable-toolbar');
                    const filter = wrapper.find('.dataTables_filter').detach();
                    const length = wrapper.find('.dataTables_length').detach();
                    $('#roles-toolbar-wrapper').html(
                        '<div class="d-flex align-items-center gap-3 w-100 flex-wrap">' +
                        (length.length ? length[0].outerHTML : '') + (filter.length ? filter[0].outerHTML : '') + '</div>'
                    );
                },
                columns: [
                    { data: 'rol_nombre', render: function (v) { return '<div class="fw-semibold">' + escapeHtml(v) + '</div>'; } },
                    { data: 'rol_descripcion', defaultContent: '-', render: function (v) { return escapeHtml(v || '-'); } },
                    { data: 'permisos', render: function (v) { return renderPermisosCompactos(v); } },
                    { data: 'rol_estatus', render: function (v) {
                        return v === 'activo'
                            ? '<span class="ls-badge ls-badge-success">Activo</span>'
                            : '<span class="ls-badge ls-badge-danger">Inactivo</span>';
                    }},
                    { data: null, orderable: false, searchable: false, render: function (row) {
                        const toggleTo   = row.rol_estatus === 'activo' ? 'inactivo' : 'activo';
                        const toggleText = row.rol_estatus === 'activo' ? 'Inactivar' : 'Activar';
                        const toggleIcon = toggleTo === 'activo' ? 'tabler-lock-open' : 'tabler-lock-off';
                        return renderActionsDropdown([
                            { className: 'btn-editar', label: 'Editar', icon: 'tabler-edit', data: { id: row.rol_id } },
                            { className: 'btn-toggle', label: toggleText, icon: toggleIcon, data: { id: row.rol_id, estatus: toggleTo } }
                        ]);
                    }}
                ]
            });
        }).fail(function () {
            AppUI.showMessage('Error', 'No fue posible cargar los roles.');
        }).always(function () { AppUI.hideLoader(); });
    }

    $('#btn-nuevo-rol').on('click', function () { limpiarFormulario(); rolModal.show(); });

    $('#roles-table').on('click', '.btn-editar', function () {
        const id = $(this).data('id');
        AppUI.showLoader();
        $.getJSON('{{ url('/seguridad/roles') }}/' + id).done(function (response) {
            const d = response.data;
            limpiarFormulario();
            $('#rolModalTitle').text('Editar rol');
            $('#btn-guardar-rol').text('Guardar cambios');
            $('#rol_id').val(d.rol_id);
            $('[name="rol_nombre"]').val(d.rol_nombre);
            $('[name="rol_descripcion"]').val(d.rol_descripcion);
            $('[name="rol_estatus"]').val(d.rol_estatus);
            (d.permisos || []).forEach(function (idPerm) { $('#permiso_' + idPerm).prop('checked', true); });
            rolModal.show();
        }).fail(function () { AppUI.showMessage('Error', 'No fue posible cargar el rol.'); })
        .always(function () { AppUI.hideLoader(); });
    });

    $('#roles-table').on('click', '.btn-toggle', function () {
        const id = $(this).data('id');
        const estatus = $(this).data('estatus');
        AppUI.showLoader();
        $.ajax({ url: '{{ url('/seguridad/roles') }}/' + id + '/estatus', method: 'PATCH', data: { rol_estatus: estatus } })
            .done(function (r) { AppUI.showMessage('Éxito', r.message); cargarTabla(); })
            .fail(function (xhr) { AppUI.showMessage('Error', xhr.responseJSON?.message || 'No se pudo cambiar el estatus.'); })
            .always(function () { AppUI.hideLoader(); });
    });

    $('#rol-form').on('submit', function (event) {
        event.preventDefault();
        const id  = $('#rol_id').val();
        const url = id ? '{{ url('/seguridad/roles') }}/' + id : '{{ route('seguridad.roles.store') }}';
        AppUI.showLoader();
        $.ajax({ url, method: id ? 'PUT' : 'POST', data: $(this).serialize() })
            .done(function (r) { rolModal.hide(); AppUI.showMessage('Éxito', r.message); cargarTabla(); })
            .fail(function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    AppUI.showMessage('Validación', Object.values(xhr.responseJSON.errors)[0][0]); return;
                }
                AppUI.showMessage('Error', xhr.responseJSON?.message || 'No fue posible guardar el rol.');
            }).always(function () { AppUI.hideLoader(); });
    });

    cargarTabla();
})();
</script>
@endpush
