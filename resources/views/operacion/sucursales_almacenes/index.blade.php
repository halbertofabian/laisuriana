@extends('layouts.app')

@section('title', 'Sucursales y Almacenes')

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
        .tag-chip.branch { background:rgba(26,158,109,.08);color:#1a9e6d;border-color:rgba(26,158,109,.2); }
        .tag-chip.permission { background:var(--ls-surface-3);color:var(--ls-text-muted);border-color:var(--ls-border); }
        .tag-chip.more { background:rgba(233,155,62,.1);color:#c97b1a;border-color:rgba(233,155,62,.25); }
        .table-row-actions .dropdown-menu { min-width:12rem;border-radius:var(--ls-radius-lg);border:1px solid var(--ls-border);box-shadow:var(--ls-shadow-lg);padding:.35rem; }
        .table-row-actions .dropdown-toggle { font-size:.78rem;font-weight:600;padding:.28rem .65rem;border-radius:var(--ls-radius-sm); }
        .table-row-actions .dropdown-item { display:flex;align-items:center;gap:.45rem;font-size:.82rem;font-weight:500;border-radius:var(--ls-radius-sm);padding:.45rem .65rem; }
        .table-row-actions .dropdown-item:hover { background:var(--ls-surface-2); }
    </style>
@endpush

@section('content')
<x-section-header
    eyebrow="Operación"
    icon="tabler-building-warehouse"
    title="Sucursales y Almacenes"
    subtitle="Administra la estructura operativa base para inventario por almacén."
/>

<div class="card app-tabs-shell mb-4">
<div class="app-tabs-shell__header">
<ul class="nav nav-tabs app-tabs-shell__tabs" id="operacionTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#panel-sucursales" type="button" role="tab" aria-selected="true">Sucursales</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#panel-almacenes" type="button" role="tab" aria-selected="false">Almacenes</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#panel-tipos" type="button" role="tab" aria-selected="false">Tipos de almacén</button>
    </li>
</ul>
</div>

<div class="app-tabs-shell__body">
<div class="tab-content">
    <div class="tab-pane fade show active" id="panel-sucursales" role="tabpanel" tabindex="0">
        <div class="d-flex justify-content-end mb-3">
            @if($permisosUI['sucursal_crear'])
                <button class="btn btn-primary btn-sm" id="btn-nueva-sucursal">
                    <i class="ti tabler-plus me-1" style="font-size:.85rem;"></i>Nueva sucursal
                </button>
            @endif
        </div>
        <div class="table-responsive">
            <table id="sucursales-table" class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Clave</th>
                        <th>Estatus</th>
                        <th>Almacenes</th>
                        <th style="width:1%;">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="tab-pane fade" id="panel-almacenes" role="tabpanel" tabindex="0">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label">Filtro por sucursal</label>
                <select class="form-select" id="flt-almacen-sucursal">
                    <option value="">Todas</option>
                    @foreach($opciones['sucursales'] as $sucursal)
                        <option value="{{ $sucursal->scl_id }}">{{ $sucursal->scl_nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Filtro por tipo de almacén</label>
                <select class="form-select" id="flt-almacen-tipo">
                    <option value="">Todos</option>
                    @foreach($opciones['tipos_almacen'] as $tipo)
                        <option value="{{ $tipo->tal_id }}">{{ $tipo->tal_nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end gap-2">
                <button class="btn btn-primary btn-sm" id="btn-filtrar-almacenes">Aplicar</button>
                <button class="btn btn-outline-secondary btn-sm" id="btn-limpiar-filtros-almacenes">Limpiar</button>
            </div>
        </div>

        <div class="d-flex justify-content-end mb-3">
            @if($permisosUI['almacen_crear'])
                <button class="btn btn-primary btn-sm" id="btn-nuevo-almacen">
                    <i class="ti tabler-plus me-1" style="font-size:.85rem;"></i>Nuevo almacén
                </button>
            @endif
        </div>
        <div class="table-responsive">
            <table id="almacenes-table" class="table">
                <thead>
                    <tr>
                        <th>Sucursal</th>
                        <th>Nombre</th>
                        <th>Clave</th>
                        <th>Tipo</th>
                        <th>Estatus</th>
                        <th style="width:1%;">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="tab-pane fade" id="panel-tipos" role="tabpanel" tabindex="0">
        <div class="d-flex justify-content-end mb-3">
            @if($permisosUI['tipo_crear'])
                <button class="btn btn-primary btn-sm" id="btn-nuevo-tipo">
                    <i class="ti tabler-plus me-1" style="font-size:.85rem;"></i>Nuevo tipo
                </button>
            @endif
        </div>
        <div class="table-responsive">
            <table id="tipos-table" class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Clave</th>
                        <th>Descripción</th>
                        <th>Estatus</th>
                        <th>Uso</th>
                        <th style="width:1%;">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
</div>
</div>

<div class="modal fade" id="modal-sucursal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="form-sucursal">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-sucursal-title">Nueva sucursal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="scl_id" name="scl_id" />
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="scl_nombre" id="scl_nombre" maxlength="120" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estatus <span class="text-danger">*</span></label>
                            <select class="form-select" name="scl_estatus" id="scl_estatus" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
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

<div class="modal fade" id="modal-almacen" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="form-almacen">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-almacen-title">Nuevo almacén</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="alm_id" name="alm_id" />
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Sucursal <span class="text-danger">*</span></label>
                            <select class="form-select" name="alm_scl_id" id="alm_scl_id" required>
                                <option value="">Selecciona</option>
                                @foreach($opciones['sucursales'] as $sucursal)
                                    <option value="{{ $sucursal->scl_id }}">{{ $sucursal->scl_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipo de almacén <span class="text-danger">*</span></label>
                            <select class="form-select" name="alm_tal_id" id="alm_tal_id" required>
                                <option value="">Selecciona</option>
                                @foreach($opciones['tipos_almacen'] as $tipo)
                                    <option value="{{ $tipo->tal_id }}">{{ $tipo->tal_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="alm_nombre" id="alm_nombre" maxlength="120" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estatus <span class="text-danger">*</span></label>
                            <select class="form-select" name="alm_estatus" id="alm_estatus" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
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

<div class="modal fade" id="modal-tipo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="form-tipo">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-tipo-title">Nuevo tipo de almacén</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="tal_id" name="tal_id" />
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="tal_nombre" id="tal_nombre" maxlength="80" required />
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="tal_descripcion" id="tal_descripcion" rows="2" maxlength="220"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estatus <span class="text-danger">*</span></label>
                            <select class="form-select" name="tal_estatus" id="tal_estatus" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
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

<div class="modal fade" id="modal-confirmar-eliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="confirmar-eliminar-mensaje">¿Deseas eliminar este registro?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btn-confirmar-eliminar">Eliminar</button>
            </div>
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
    const permisosUI = @json($permisosUI);

    const rutas = {
        sucursalesData: '{{ route('operacion.sucursales_almacenes.sucursales.data') }}',
        sucursalShow: (id) => '{{ url('/operacion/sucursales-almacenes/sucursales') }}/' + id,
        sucursalStore: '{{ route('operacion.sucursales_almacenes.sucursales.store') }}',
        sucursalUpdate: (id) => '{{ url('/operacion/sucursales-almacenes/sucursales') }}/' + id,
        sucursalEstatus: (id) => '{{ url('/operacion/sucursales-almacenes/sucursales') }}/' + id + '/estatus',
        sucursalDelete: (id) => '{{ url('/operacion/sucursales-almacenes/sucursales') }}/' + id,

        almacenesData: '{{ route('operacion.sucursales_almacenes.almacenes.data') }}',
        almacenShow: (id) => '{{ url('/operacion/sucursales-almacenes/almacenes') }}/' + id,
        almacenStore: '{{ route('operacion.sucursales_almacenes.almacenes.store') }}',
        almacenUpdate: (id) => '{{ url('/operacion/sucursales-almacenes/almacenes') }}/' + id,
        almacenEstatus: (id) => '{{ url('/operacion/sucursales-almacenes/almacenes') }}/' + id + '/estatus',
        almacenDelete: (id) => '{{ url('/operacion/sucursales-almacenes/almacenes') }}/' + id,

        tiposData: '{{ route('operacion.sucursales_almacenes.tipos.data') }}',
        tipoShow: (id) => '{{ url('/operacion/sucursales-almacenes/tipos-almacen') }}/' + id,
        tipoStore: '{{ route('operacion.sucursales_almacenes.tipos.store') }}',
        tipoUpdate: (id) => '{{ url('/operacion/sucursales-almacenes/tipos-almacen') }}/' + id,
        tipoEstatus: (id) => '{{ url('/operacion/sucursales-almacenes/tipos-almacen') }}/' + id + '/estatus',
        tipoDelete: (id) => '{{ url('/operacion/sucursales-almacenes/tipos-almacen') }}/' + id
    };

    const modalSucursal = new bootstrap.Modal(document.getElementById('modal-sucursal'));
    const modalAlmacen = new bootstrap.Modal(document.getElementById('modal-almacen'));
    const modalTipo = new bootstrap.Modal(document.getElementById('modal-tipo'));
    const modalConfirmarEliminar = new bootstrap.Modal(document.getElementById('modal-confirmar-eliminar'));
    let deleteAction = null;

    function estatusBadge(estatus) {
        return estatus === 'activo'
            ? '<span class="ls-badge ls-badge-success">Activo</span>'
            : '<span class="ls-badge ls-badge-danger">Inactivo</span>';
    }

    function buildActions(canEdit, canToggle, canDelete, isActive, id, type, name) {
        if (!canEdit && !canToggle && !canDelete) {
            return '<span class="text-body-secondary">Sin acciones</span>';
        }

        let html = '<div class="dropdown op-actions">';
        html += '<button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">Acciones</button>';
        html += '<ul class="dropdown-menu dropdown-menu-end">';

        if (canEdit) {
            html += '<li><button type="button" class="dropdown-item" data-action="edit-' + type + '" data-id="' + id + '"><i class="icon-base ti tabler-edit"></i>Editar</button></li>';
        }

        if (canToggle) {
            const next = isActive ? 'inactivo' : 'activo';
            const label = isActive ? 'Inactivar' : 'Activar';
            html += '<li><button type="button" class="dropdown-item" data-action="toggle-' + type + '" data-id="' + id + '" data-next="' + next + '"><i class="icon-base ti tabler-refresh"></i>' + label + '</button></li>';
        }

        if (canDelete) {
            html += '<li><hr class="dropdown-divider"></li>';
            html += '<li><button type="button" class="dropdown-item text-danger" data-action="delete-' + type + '" data-id="' + id + '" data-name="' + (name || '') + '"><i class="icon-base ti tabler-trash"></i>Eliminar</button></li>';
        }

        html += '</ul></div>';
        return html;
    }

    function parseErrorMessage(xhr) {
        if (xhr.responseJSON) {
            if (xhr.responseJSON.message) {
                return xhr.responseJSON.message;
            }

            if (xhr.responseJSON.errors) {
                const parts = [];
                Object.values(xhr.responseJSON.errors).forEach(function (messages) {
                    (messages || []).forEach(function (m) {
                        parts.push(m);
                    });
                });
                if (parts.length > 0) {
                    return parts.join('\n');
                }
            }
        }

        return 'No fue posible completar la operación.';
    }

    function recargarSucursales() {
        AppUI.showLoader();
        $.getJSON(rutas.sucursalesData).done(function (response) {
            if ($.fn.DataTable.isDataTable('#sucursales-table')) {
                $('#sucursales-table').DataTable().clear().destroy();
            }

            $('#sucursales-table').DataTable({
                data: response.data || [],
                order: [[0, 'asc']],
                columns: [
                    { data: 'scl_nombre' },
                    { data: 'scl_clave' },
                    { data: 'scl_estatus', render: (v) => estatusBadge(v) },
                    {
                        data: null,
                        render: function (row) {
                            return '<span class="fw-semibold">' + row.almacenes_activos + '</span> / ' + row.almacenes_total;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function (row) {
                            return buildActions(permisosUI.sucursal_editar, permisosUI.sucursal_inactivar, permisosUI.sucursal_eliminar, row.scl_estatus === 'activo', row.scl_id, 'sucursal', row.scl_nombre);
                        }
                    }
                ]
            });
        }).fail(function (xhr) {
            AppUI.showMessage('Error', parseErrorMessage(xhr), 'error');
        }).always(function () {
            AppUI.hideLoader();
        });
    }

    function recargarAlmacenes() {
        AppUI.showLoader();

        $.getJSON(rutas.almacenesData, {
            alm_scl_id: $('#flt-almacen-sucursal').val(),
            alm_tal_id: $('#flt-almacen-tipo').val()
        }).done(function (response) {
            if ($.fn.DataTable.isDataTable('#almacenes-table')) {
                $('#almacenes-table').DataTable().clear().destroy();
            }

            $('#almacenes-table').DataTable({
                data: response.data || [],
                order: [[0, 'asc'], [1, 'asc']],
                columns: [
                    { data: 'sucursal', defaultContent: '-' },
                    { data: 'alm_nombre' },
                    { data: 'alm_clave' },
                    { data: 'tipo', defaultContent: '-' },
                    { data: 'alm_estatus', render: (v) => estatusBadge(v) },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function (row) {
                            return buildActions(permisosUI.almacen_editar, permisosUI.almacen_inactivar, permisosUI.almacen_eliminar, row.alm_estatus === 'activo', row.alm_id, 'almacen', row.alm_nombre);
                        }
                    }
                ]
            });
        }).fail(function (xhr) {
            AppUI.showMessage('Error', parseErrorMessage(xhr), 'error');
        }).always(function () {
            AppUI.hideLoader();
        });
    }

    function recargarTipos() {
        AppUI.showLoader();
        $.getJSON(rutas.tiposData).done(function (response) {
            if ($.fn.DataTable.isDataTable('#tipos-table')) {
                $('#tipos-table').DataTable().clear().destroy();
            }

            $('#tipos-table').DataTable({
                data: response.data || [],
                order: [[0, 'asc']],
                columns: [
                    { data: 'tal_nombre' },
                    { data: 'tal_clave' },
                    { data: 'tal_descripcion', defaultContent: '-' },
                    { data: 'tal_estatus', render: (v) => estatusBadge(v) },
                    {
                        data: null,
                        render: function (row) {
                            return '<span class="fw-semibold">' + row.almacenes_activos + '</span> / ' + row.almacenes_total;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function (row) {
                            return buildActions(permisosUI.tipo_editar, permisosUI.tipo_inactivar, permisosUI.tipo_eliminar, row.tal_estatus === 'activo', row.tal_id, 'tipo', row.tal_nombre);
                        }
                    }
                ]
            });
        }).fail(function (xhr) {
            AppUI.showMessage('Error', parseErrorMessage(xhr), 'error');
        }).always(function () {
            AppUI.hideLoader();
        });
    }

    function recargarOpcionesActivas() {
        $.getJSON(rutas.sucursalesData).done(function (response) {
            const opciones = (response.data || []).filter((x) => x.scl_estatus === 'activo');
            const html = ['<option value="">Selecciona</option>'];
            const htmlFiltro = ['<option value="">Todas</option>'];

            opciones.forEach(function (item) {
                html.push('<option value="' + item.scl_id + '">' + item.scl_nombre + '</option>');
                htmlFiltro.push('<option value="' + item.scl_id + '">' + item.scl_nombre + '</option>');
            });

            $('#alm_scl_id').html(html.join(''));
            $('#flt-almacen-sucursal').html(htmlFiltro.join(''));
        });

        $.getJSON(rutas.tiposData).done(function (response) {
            const opciones = (response.data || []).filter((x) => x.tal_estatus === 'activo');
            const html = ['<option value="">Selecciona</option>'];
            const htmlFiltro = ['<option value="">Todos</option>'];

            opciones.forEach(function (item) {
                html.push('<option value="' + item.tal_id + '">' + item.tal_nombre + '</option>');
                htmlFiltro.push('<option value="' + item.tal_id + '">' + item.tal_nombre + '</option>');
            });

            $('#alm_tal_id').html(html.join(''));
            $('#flt-almacen-tipo').html(htmlFiltro.join(''));
        });
    }

    function limpiarFormSucursal() {
        $('#form-sucursal')[0].reset();
        $('#scl_id').val('');
        $('#scl_estatus').val('activo');
    }

    function limpiarFormAlmacen() {
        $('#form-almacen')[0].reset();
        $('#alm_id').val('');
        $('#alm_estatus').val('activo');
    }

    function limpiarFormTipo() {
        $('#form-tipo')[0].reset();
        $('#tal_id').val('');
        $('#tal_estatus').val('activo');
    }

    $('#btn-nueva-sucursal').on('click', function () {
        limpiarFormSucursal();
        $('#modal-sucursal-title').text('Nueva sucursal');
        modalSucursal.show();
    });

    $('#btn-nuevo-almacen').on('click', function () {
        limpiarFormAlmacen();
        $('#modal-almacen-title').text('Nuevo almacén');
        modalAlmacen.show();
    });

    $('#btn-nuevo-tipo').on('click', function () {
        limpiarFormTipo();
        $('#modal-tipo-title').text('Nuevo tipo de almacén');
        modalTipo.show();
    });

    $('#btn-filtrar-almacenes').on('click', function () {
        recargarAlmacenes();
    });

    $('#btn-limpiar-filtros-almacenes').on('click', function () {
        $('#flt-almacen-sucursal').val('');
        $('#flt-almacen-tipo').val('');
        recargarAlmacenes();
    });

    $('#form-sucursal').on('submit', function (e) {
        e.preventDefault();
        AppUI.showLoader();

        const id = $('#scl_id').val();
        const url = id ? rutas.sucursalUpdate(id) : rutas.sucursalStore;
        const method = id ? 'PUT' : 'POST';

        $.ajax({
            url,
            method,
            data: $(this).serialize(),
            dataType: 'json'
        }).done(function (response) {
            modalSucursal.hide();
            recargarSucursales();
            recargarOpcionesActivas();
            AppUI.showMessage('Éxito', response.message || 'Operación realizada correctamente.', 'success');
        }).fail(function (xhr) {
            AppUI.showMessage('Error', parseErrorMessage(xhr), 'error');
        }).always(function () {
            AppUI.hideLoader();
        });
    });

    $('#form-almacen').on('submit', function (e) {
        e.preventDefault();
        AppUI.showLoader();

        const id = $('#alm_id').val();
        const url = id ? rutas.almacenUpdate(id) : rutas.almacenStore;
        const method = id ? 'PUT' : 'POST';

        $.ajax({
            url,
            method,
            data: $(this).serialize(),
            dataType: 'json'
        }).done(function (response) {
            modalAlmacen.hide();
            recargarAlmacenes();
            recargarSucursales();
            recargarTipos();
            AppUI.showMessage('Éxito', response.message || 'Operación realizada correctamente.', 'success');
        }).fail(function (xhr) {
            AppUI.showMessage('Error', parseErrorMessage(xhr), 'error');
        }).always(function () {
            AppUI.hideLoader();
        });
    });

    $('#form-tipo').on('submit', function (e) {
        e.preventDefault();
        AppUI.showLoader();

        const id = $('#tal_id').val();
        const url = id ? rutas.tipoUpdate(id) : rutas.tipoStore;
        const method = id ? 'PUT' : 'POST';

        $.ajax({
            url,
            method,
            data: $(this).serialize(),
            dataType: 'json'
        }).done(function (response) {
            modalTipo.hide();
            recargarTipos();
            recargarOpcionesActivas();
            AppUI.showMessage('Éxito', response.message || 'Operación realizada correctamente.', 'success');
        }).fail(function (xhr) {
            AppUI.showMessage('Error', parseErrorMessage(xhr), 'error');
        }).always(function () {
            AppUI.hideLoader();
        });
    });

    $(document).on('click', 'button[data-action="edit-sucursal"]', function () {
        const id = $(this).data('id');
        AppUI.showLoader();

        $.getJSON(rutas.sucursalShow(id)).done(function (response) {
            const d = response.data || {};
            limpiarFormSucursal();
            $('#modal-sucursal-title').text('Editar sucursal');
            $('#scl_id').val(d.scl_id || '');
            $('#scl_nombre').val(d.scl_nombre || '');
            $('#scl_estatus').val(d.scl_estatus || 'activo');
            modalSucursal.show();
        }).fail(function (xhr) {
            AppUI.showMessage('Error', parseErrorMessage(xhr), 'error');
        }).always(function () {
            AppUI.hideLoader();
        });
    });

    $(document).on('click', 'button[data-action="edit-almacen"]', function () {
        const id = $(this).data('id');
        AppUI.showLoader();

        $.getJSON(rutas.almacenShow(id)).done(function (response) {
            const d = response.data || {};
            limpiarFormAlmacen();
            $('#modal-almacen-title').text('Editar almacén');
            $('#alm_id').val(d.alm_id || '');
            $('#alm_scl_id').val(String(d.alm_scl_id || ''));
            $('#alm_tal_id').val(String(d.alm_tal_id || ''));
            $('#alm_nombre').val(d.alm_nombre || '');
            $('#alm_estatus').val(d.alm_estatus || 'activo');
            modalAlmacen.show();
        }).fail(function (xhr) {
            AppUI.showMessage('Error', parseErrorMessage(xhr), 'error');
        }).always(function () {
            AppUI.hideLoader();
        });
    });

    $(document).on('click', 'button[data-action="edit-tipo"]', function () {
        const id = $(this).data('id');
        AppUI.showLoader();

        $.getJSON(rutas.tipoShow(id)).done(function (response) {
            const d = response.data || {};
            limpiarFormTipo();
            $('#modal-tipo-title').text('Editar tipo de almacén');
            $('#tal_id').val(d.tal_id || '');
            $('#tal_nombre').val(d.tal_nombre || '');
            $('#tal_descripcion').val(d.tal_descripcion || '');
            $('#tal_estatus').val(d.tal_estatus || 'activo');
            modalTipo.show();
        }).fail(function (xhr) {
            AppUI.showMessage('Error', parseErrorMessage(xhr), 'error');
        }).always(function () {
            AppUI.hideLoader();
        });
    });

    $(document).on('click', 'button[data-action="toggle-sucursal"]', function () {
        const id = $(this).data('id');
        const next = $(this).data('next');

        AppUI.showLoader();
        $.ajax({
            url: rutas.sucursalEstatus(id),
            method: 'PATCH',
            data: { scl_estatus: next },
            dataType: 'json'
        }).done(function (response) {
            recargarSucursales();
            recargarOpcionesActivas();
            AppUI.showMessage('Éxito', response.message || 'Estatus actualizado correctamente.', 'success');
        }).fail(function (xhr) {
            AppUI.showMessage('Error', parseErrorMessage(xhr), 'error');
        }).always(function () {
            AppUI.hideLoader();
        });
    });

    $(document).on('click', 'button[data-action="toggle-almacen"]', function () {
        const id = $(this).data('id');
        const next = $(this).data('next');

        AppUI.showLoader();
        $.ajax({
            url: rutas.almacenEstatus(id),
            method: 'PATCH',
            data: { alm_estatus: next },
            dataType: 'json'
        }).done(function (response) {
            recargarAlmacenes();
            recargarSucursales();
            recargarTipos();
            AppUI.showMessage('Éxito', response.message || 'Estatus actualizado correctamente.', 'success');
        }).fail(function (xhr) {
            AppUI.showMessage('Error', parseErrorMessage(xhr), 'error');
        }).always(function () {
            AppUI.hideLoader();
        });
    });

    $(document).on('click', 'button[data-action="toggle-tipo"]', function () {
        const id = $(this).data('id');
        const next = $(this).data('next');

        AppUI.showLoader();
        $.ajax({
            url: rutas.tipoEstatus(id),
            method: 'PATCH',
            data: { tal_estatus: next },
            dataType: 'json'
        }).done(function (response) {
            recargarTipos();
            recargarOpcionesActivas();
            AppUI.showMessage('Éxito', response.message || 'Estatus actualizado correctamente.', 'success');
        }).fail(function (xhr) {
            AppUI.showMessage('Error', parseErrorMessage(xhr), 'error');
        }).always(function () {
            AppUI.hideLoader();
        });
    });

    function abrirConfirmacionEliminar(message, onConfirm) {
        deleteAction = onConfirm;
        $('#confirmar-eliminar-mensaje').text(message);
        modalConfirmarEliminar.show();
    }

    $('#btn-confirmar-eliminar').on('click', function () {
        if (typeof deleteAction === 'function') {
            modalConfirmarEliminar.hide();
            deleteAction();
        }
    });

    $(document).on('click', 'button[data-action="delete-sucursal"]', function () {
        const id = $(this).data('id');
        const name = $(this).data('name') || 'la sucursal';
        abrirConfirmacionEliminar('¿Deseas eliminar la sucursal "' + name + '"? Esta acción es lógica y no se puede deshacer desde esta pantalla.', function () {
            AppUI.showLoader();
            $.ajax({
                url: rutas.sucursalDelete(id),
                method: 'DELETE',
                dataType: 'json'
            }).done(function (response) {
                recargarSucursales();
                recargarOpcionesActivas();
                AppUI.showMessage('Éxito', response.message || 'Sucursal eliminada correctamente.', 'success');
            }).fail(function (xhr) {
                AppUI.showMessage('Error', parseErrorMessage(xhr), 'error');
            }).always(function () {
                AppUI.hideLoader();
            });
        });
    });

    $(document).on('click', 'button[data-action="delete-almacen"]', function () {
        const id = $(this).data('id');
        const name = $(this).data('name') || 'el almacén';
        abrirConfirmacionEliminar('¿Deseas eliminar el almacén "' + name + '"? Esta acción es lógica y no se puede deshacer desde esta pantalla.', function () {
            AppUI.showLoader();
            $.ajax({
                url: rutas.almacenDelete(id),
                method: 'DELETE',
                dataType: 'json'
            }).done(function (response) {
                recargarAlmacenes();
                recargarSucursales();
                recargarTipos();
                AppUI.showMessage('Éxito', response.message || 'Almacén eliminado correctamente.', 'success');
            }).fail(function (xhr) {
                AppUI.showMessage('Error', parseErrorMessage(xhr), 'error');
            }).always(function () {
                AppUI.hideLoader();
            });
        });
    });

    $(document).on('click', 'button[data-action="delete-tipo"]', function () {
        const id = $(this).data('id');
        const name = $(this).data('name') || 'el tipo de almacén';
        abrirConfirmacionEliminar('¿Deseas eliminar el tipo de almacén "' + name + '"? Esta acción es lógica y no se puede deshacer desde esta pantalla.', function () {
            AppUI.showLoader();
            $.ajax({
                url: rutas.tipoDelete(id),
                method: 'DELETE',
                dataType: 'json'
            }).done(function (response) {
                recargarTipos();
                recargarOpcionesActivas();
                AppUI.showMessage('Éxito', response.message || 'Tipo de almacén eliminado correctamente.', 'success');
            }).fail(function (xhr) {
                AppUI.showMessage('Error', parseErrorMessage(xhr), 'error');
            }).always(function () {
                AppUI.hideLoader();
            });
        });
    });

    recargarSucursales();
    recargarAlmacenes();
    recargarTipos();
})();
</script>
@endpush
