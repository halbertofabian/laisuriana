@extends('layouts.app')

@section('title', 'Roles')

@push('vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <style>
        .table-row-actions .dropdown-menu {
            min-width: 13rem;
            border-radius: 0.65rem;
            border-color: #ebeaf2;
            box-shadow: 0 0.4rem 1.1rem rgba(75, 70, 92, 0.14);
        }

        .table-row-actions .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            font-weight: 500;
        }

        .table-row-actions .dropdown-item i {
            font-size: 1rem;
        }
    </style>
@endpush

@section('content')
<x-section-header
    eyebrow="Configuración"
    icon="tabler-lock-cog"
    title="Roles"
    subtitle="Administración de roles y sus permisos."
>
    <x-slot:actions>
        <button class="btn btn-primary btn-sm" id="btn-nuevo-rol">Nuevo rol</button>
    </x-slot:actions>
</x-section-header>

<div class="card">
    <div class="card-datatable table-responsive">
        <table id="roles-table" class="table">
            <thead>
            <tr>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Permisos</th>
                <th>Estatus</th>
                <th>Acciones</th>
            </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="rolModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="rol-form">
                <div class="modal-header">
                    <h5 class="modal-title" id="rolModalTitle">Nuevo rol</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-12">
                        <small class="text-body-secondary">Los campos con <span class="text-danger">*</span> son obligatorios.</small>
                    </div>
                    <input type="hidden" name="rol_id" id="rol_id">
                    <div class="col-md-6">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="rol_nombre" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Estatus <span class="text-danger">*</span></label>
                        <select class="form-select" name="rol_estatus" required>
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descripción (recomendado)</label>
                        <input type="text" class="form-control" name="rol_descripcion">
                        <small class="text-body-secondary">Ejemplo: "Rol para supervisar ventas y autorizar cancelaciones".</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label d-block">Permisos <span class="text-danger">*</span></label>
                        <small class="text-body-secondary d-block mb-2">Selecciona acciones concretas. Cada permiso muestra clave y descripción funcional.</small>
                        <div class="row g-2">
                            @foreach($permisos as $permiso)
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permisos[]" id="permiso_{{ $permiso->prm_id }}" value="{{ $permiso->prm_id }}">
                                        <label class="form-check-label" for="permiso_{{ $permiso->prm_id }}">
                                            <span class="fw-semibold">{{ $permiso->prm_clave }}</span><br>
                                            <small class="text-body-secondary">{{ $permiso->prm_descripcion }}</small>
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
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

    function renderActionsDropdown(items) {
        const options = items.map(function (item) {
            const extraData = Object.entries(item.data || {}).map(function (entry) {
                return ' data-' + entry[0] + '="' + String(entry[1]) + '"';
            }).join('');

            return '<li><button type="button" class="dropdown-item ' + item.className + '"' + extraData + '>' +
                '<i class="icon-base ti ' + item.icon + '"></i>' + item.label +
                '</button></li>';
        }).join('');

        return '<div class="dropdown table-row-actions">' +
            '<button class="btn btn-sm btn-label-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">' +
            '<i class="icon-base ti tabler-dots-vertical me-1"></i>Acciones</button>' +
            '<ul class="dropdown-menu dropdown-menu-end">' + options + '</ul>' +
            '</div>';
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
                columns: [
                    { data: 'rol_nombre' },
                    { data: 'rol_descripcion', defaultContent: '-' },
                    {
                        data: 'permisos',
                        render: function (v) {
                            return (v || []).map(function (permiso) {
                                return '<div><span class="fw-semibold">' + permiso.clave + '</span><br><small class="text-body-secondary">' + permiso.descripcion + '</small></div>';
                            }).join('<hr class="my-2">');
                        }
                    },
                    {
                        data: 'rol_estatus',
                        render: function (v) {
                            return v === 'activo'
                                ? '<span class="ls-badge ls-badge-success">Activo</span>'
                                : '<span class="ls-badge ls-badge-danger">Inactivo</span>';
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function (row) {
                            const toggleTo = row.rol_estatus === 'activo' ? 'inactivo' : 'activo';
                            const toggleText = row.rol_estatus === 'activo' ? 'Inactivar' : 'Activar';
                            const toggleIcon = toggleTo === 'activo' ? 'tabler-lock-open' : 'tabler-lock-off';

                            return renderActionsDropdown([
                                {
                                    className: 'btn-editar',
                                    label: 'Editar',
                                    icon: 'tabler-edit',
                                    data: { id: row.rol_id }
                                },
                                {
                                    className: 'btn-toggle',
                                    label: toggleText,
                                    icon: toggleIcon,
                                    data: { id: row.rol_id, estatus: toggleTo }
                                }
                            ]);
                        }
                    }
                ]
            });
        }).fail(function () {
            AppUI.showMessage('Error', 'No fue posible cargar los roles.');
        }).always(function () {
            AppUI.hideLoader();
        });
    }

    function limpiarFormulario() {
        $('#rol-form')[0].reset();
        $('#rol_id').val('');
        $('input[name="permisos[]"]').prop('checked', false);
        $('#rolModalTitle').text('Nuevo rol');
    }

    $('#btn-nuevo-rol').on('click', function () {
        limpiarFormulario();
        rolModal.show();
    });

    $('#roles-table').on('click', '.btn-editar', function () {
        const id = $(this).data('id');
        AppUI.showLoader();

        $.getJSON('{{ url('/seguridad/roles') }}/' + id).done(function (response) {
            const d = response.data;
            limpiarFormulario();
            $('#rolModalTitle').text('Editar rol');
            $('#rol_id').val(d.rol_id);
            $('[name="rol_nombre"]').val(d.rol_nombre);
            $('[name="rol_descripcion"]').val(d.rol_descripcion);
            $('[name="rol_estatus"]').val(d.rol_estatus);
            (d.permisos || []).forEach(function (idPermiso) {
                $('#permiso_' + idPermiso).prop('checked', true);
            });
            rolModal.show();
        }).fail(function () {
            AppUI.showMessage('Error', 'No fue posible cargar el rol.');
        }).always(function () {
            AppUI.hideLoader();
        });
    });

    $('#roles-table').on('click', '.btn-toggle', function () {
        const id = $(this).data('id');
        const estatus = $(this).data('estatus');

        AppUI.showLoader();
        $.ajax({
            url: '{{ url('/seguridad/roles') }}/' + id + '/estatus',
            method: 'PATCH',
            data: { rol_estatus: estatus }
        }).done(function (response) {
            AppUI.showMessage('Éxito', response.message);
            cargarTabla();
        }).fail(function (xhr) {
            AppUI.showMessage('Error', xhr.responseJSON?.message || 'No fue posible cambiar el estatus.');
        }).always(function () {
            AppUI.hideLoader();
        });
    });

    $('#rol-form').on('submit', function (event) {
        event.preventDefault();

        const id = $('#rol_id').val();
        const url = id ? '{{ url('/seguridad/roles') }}/' + id : '{{ route('seguridad.roles.store') }}';
        const method = id ? 'PUT' : 'POST';

        AppUI.showLoader();
        $.ajax({
            url,
            method,
            data: $(this).serialize()
        }).done(function (response) {
            rolModal.hide();
            AppUI.showMessage('Éxito', response.message);
            cargarTabla();
        }).fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                const firstError = Object.values(xhr.responseJSON.errors)[0][0];
                AppUI.showMessage('Validación', firstError);
                return;
            }

            AppUI.showMessage('Error', xhr.responseJSON?.message || 'No fue posible guardar el rol.');
        }).always(function () {
            AppUI.hideLoader();
        });
    });

    cargarTabla();
})();
</script>
@endpush
