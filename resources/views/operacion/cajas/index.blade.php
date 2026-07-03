@extends('layouts.app')

@section('title', 'Cajas')

@push('vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/select2/select2.css') }}" />
@endpush

@section('content')
<x-section-header
    eyebrow="Operación"
    icon="tabler-cash"
    title="Cajas"
    subtitle="Da de alta cajas por sucursal y asigna uno o varios usuarios cajeros."
/>

<style>
    #tbl-cajas td.actions-cell {
        min-width: 120px;
        text-align: right;
    }
    #tbl-cajas .actions-dropdown .dropdown-toggle::after {
        margin-left: .4rem;
    }
    #tbl-cajas .actions-dropdown .dropdown-menu {
        min-width: 180px;
    }
</style>

<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label">Buscar</label>
                <input type="text" class="form-control" id="flt-buscar" placeholder="Nombre o clave" />
            </div>
            <div class="col-md-3">
                <label class="form-label">Sucursal</label>
                <select class="form-select" id="flt-sucursal">
                    <option value="">Todas</option>
                    @foreach($opciones['sucursales'] as $sucursal)
                        <option value="{{ $sucursal->scl_id }}">{{ $sucursal->scl_nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Estatus</label>
                <select class="form-select" id="flt-estatus">
                    <option value="">Todos</option>
                    <option value="activo">Activo</option>
                    <option value="inactivo">Inactivo</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100" id="btn-filtrar">Filtrar</button>
            </div>
        </div>

        <div class="d-flex justify-content-end mb-3">
            @if($permisosUI['caja_crear'])
                <button class="btn btn-primary btn-sm" id="btn-nueva-caja">
                    <i class="ti tabler-plus me-1"></i>Nueva caja
                </button>
            @endif
        </div>

        <div class="table-responsive">
            <table class="table" id="tbl-cajas">
                <thead>
                    <tr>
                        <th>Sucursal</th>
                        <th>Almacén</th>
                        <th>Nombre</th>
                        <th>Clave</th>
                        <th>Usuarios</th>
                        <th>Estatus</th>
                        <th style="width:1%">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-caja" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="form-caja">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-caja-title">Nueva caja</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="caj_id" name="caj_id" />
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Sucursal <span class="text-danger">*</span></label>
                            <select class="form-select" name="caj_scl_id" id="caj_scl_id" required>
                                <option value="">Selecciona</option>
                                @foreach($opciones['sucursales'] as $sucursal)
                                    <option value="{{ $sucursal->scl_id }}">{{ $sucursal->scl_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estatus <span class="text-danger">*</span></label>
                            <select class="form-select" name="caj_estatus" id="caj_estatus" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Almacén asociado (opcional)</label>
                            <select class="form-select" name="caj_alm_id" id="caj_alm_id">
                                <option value="">Sin almacén asociado</option>
                            </select>
                            <small class="text-body-secondary">Si se define, POS tomará este almacén automáticamente para la venta.</small>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Nombre de caja <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="caj_nombre" name="caj_nombre" maxlength="120" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Umbral de retiro</label>
                            <input type="number" class="form-control" id="caj_retiro_umbral" name="caj_retiro_umbral" min="0" step="0.01" placeholder="0.00" />
                            <small class="text-body-secondary">Cuando el efectivo estimado llegue a este monto, POS recomendará un retiro.</small>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Usuarios asignados <span class="text-danger">*</span></label>
                            <select class="form-select caja-usuarios-select" id="usuarios" name="usuarios[]" multiple required>
                                @foreach($opciones['usuarios'] as $usuario)
                                    <option value="{{ $usuario->usr_id }}">{{ $usuario->usr_nombre }} ({{ $usuario->usr_usuario }})</option>
                                @endforeach
                            </select>
                            <small class="text-body-secondary">Selecciona uno o varios usuarios.</small>
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
    <script src="{{ asset('vendor-template/assets/vendor/libs/select2/select2.js') }}"></script>
@endpush

@push('page-scripts')
<script>
(function () {
    const permisos = @json($permisosUI);
    const modal = new bootstrap.Modal(document.getElementById('modal-caja'));

    const rutas = {
        data: '{{ route('operacion.cajas.data') }}',
        show: (id) => '{{ url('/operacion/cajas') }}/' + id,
        store: '{{ route('operacion.cajas.store') }}',
        update: (id) => '{{ url('/operacion/cajas') }}/' + id,
        estatus: (id) => '{{ url('/operacion/cajas') }}/' + id + '/estatus',
        destroy: (id) => '{{ url('/operacion/cajas') }}/' + id,
    };

    const tbody = document.querySelector('#tbl-cajas tbody');
    const form = document.getElementById('form-caja');
    const $usuarios = $('#usuarios');
    const almacenesBase = @json($opciones['almacenes_js'] ?? []);

    function initUsuariosSelect() {
        if (!$usuarios.length || typeof $usuarios.select2 !== 'function') return;
        if ($usuarios.hasClass('select2-hidden-accessible')) {
            $usuarios.select2('destroy');
        }

        $usuarios.wrap('<div class="position-relative"></div>').select2({
            placeholder: 'Selecciona usuarios',
            width: '100%',
            closeOnSelect: false,
            dropdownParent: $('#modal-caja'),
        });
    }

    function estatusBadge(estatus) {
        return estatus === 'activo'
            ? '<span class="ls-badge ls-badge-success">Activo</span>'
            : '<span class="ls-badge ls-badge-danger">Inactivo</span>';
    }

    function usuariosHtml(usuarios) {
        if (!usuarios || !usuarios.length) return '<span class="text-body-secondary">Sin usuarios</span>';
        return usuarios.map((u) => `<span class="ls-badge ls-badge-primary me-1 mb-1">${u.usr_nombre}</span>`).join('');
    }

    function accionesHtml(row) {
        let items = '';

        if (permisos.caja_editar) {
            items += `<button class="dropdown-item" data-action="editar" data-id="${row.caj_id}">Editar</button>`;
        }

        if (permisos.caja_inactivar) {
            const next = row.caj_estatus === 'activo' ? 'inactivo' : 'activo';
            const label = row.caj_estatus === 'activo' ? 'Inactivar' : 'Activar';
            items += `<button class="dropdown-item" data-action="estatus" data-id="${row.caj_id}" data-estatus="${next}">${label}</button>`;
        }

        if (permisos.caja_eliminar) {
            if (items) items += `<div class="dropdown-divider"></div>`;
            items += `<button class="dropdown-item text-danger" data-action="eliminar" data-id="${row.caj_id}">Eliminar</button>`;
        }

        if (!items) return '<span class="text-body-secondary">Sin acciones</span>';
        return `
            <div class="dropdown actions-dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Acciones
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    ${items}
                </div>
            </div>
        `;
    }

    async function cargarTabla() {
        const params = new URLSearchParams({
            buscar: document.getElementById('flt-buscar').value || '',
            estatus: document.getElementById('flt-estatus').value || '',
            caj_scl_id: document.getElementById('flt-sucursal').value || '',
        });

        const res = await fetch(`${rutas.data}?${params.toString()}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });

        const json = await res.json();
        const rows = json.data || [];

        tbody.innerHTML = rows.map((row) => `
            <tr>
                <td>${row.sucursal ?? ''}</td>
                <td>${row.almacen ?? '<span class="text-body-secondary">Sin definir</span>'}</td>
                <td>${row.caj_nombre}</td>
                <td>${row.caj_clave}</td>
                <td>${usuariosHtml(row.usuarios)}</td>
                <td>${estatusBadge(row.caj_estatus)}</td>
                <td class="actions-cell">${accionesHtml(row)}</td>
            </tr>
        `).join('');
    }

    function limpiarForm() {
        form.reset();
        document.getElementById('caj_id').value = '';
        document.getElementById('caj_retiro_umbral').value = '';
        llenarAlmacenesPorSucursal('', '');
        $usuarios.val([]).trigger('change');
    }

    function llenarAlmacenesPorSucursal(sucursalId, seleccionado = '') {
        const select = document.getElementById('caj_alm_id');
        const sid = Number(sucursalId || 0);
        const opciones = (almacenesBase || []).filter((a) => Number(a.alm_scl_id) === sid);
        select.innerHTML = '<option value="">Sin almacén asociado</option>' + opciones.map((a) =>
            `<option value="${a.alm_id}">${a.alm_nombre}</option>`
        ).join('');
        select.value = seleccionado ? String(seleccionado) : '';
    }

    async function abrirEditar(id) {
        limpiarForm();
        const res = await fetch(rutas.show(id), { headers: { 'Accept': 'application/json' } });
        const json = await res.json();
        const row = json.data;

        document.getElementById('modal-caja-title').textContent = 'Editar caja';
        document.getElementById('caj_id').value = row.caj_id;
        document.getElementById('caj_scl_id').value = row.caj_scl_id;
        llenarAlmacenesPorSucursal(row.caj_scl_id, row.caj_alm_id || '');
        document.getElementById('caj_nombre').value = row.caj_nombre;
        document.getElementById('caj_retiro_umbral').value = row.caj_retiro_umbral || '';
        document.getElementById('caj_estatus').value = row.caj_estatus;

        const usuarios = (row.usuarios || []).map((u) => String(u));
        $usuarios.val(usuarios).trigger('change');

        modal.show();
    }

    function formDataToJson(formData) {
        const usuarios = formData.getAll('usuarios[]');

        return {
            caj_scl_id: formData.get('caj_scl_id'),
            caj_alm_id: formData.get('caj_alm_id') || null,
            caj_nombre: formData.get('caj_nombre'),
            caj_retiro_umbral: formData.get('caj_retiro_umbral') || 0,
            caj_estatus: formData.get('caj_estatus'),
            usuarios,
        };
    }

    async function guardarCaja(event) {
        event.preventDefault();
        const id = document.getElementById('caj_id').value;
        const data = formDataToJson(new FormData(form));

        const res = await fetch(id ? rutas.update(id) : rutas.store, {
            method: id ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(data),
        });

        if (!res.ok) {
            const error = await res.json();
            const first = Object.values(error.errors || {})[0];
            alert(first ? first[0] : 'No se pudo guardar la caja.');
            return;
        }

        modal.hide();
        await cargarTabla();
    }

    async function cambiarEstatus(id, estatus) {
        const res = await fetch(rutas.estatus(id), {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ caj_estatus: estatus }),
        });

        if (!res.ok) {
            alert('No se pudo actualizar el estatus.');
            return;
        }

        await cargarTabla();
    }

    async function eliminarCaja(id) {
        if (!confirm('¿Deseas eliminar esta caja?')) {
            return;
        }

        const res = await fetch(rutas.destroy(id), {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!res.ok) {
            alert('No se pudo eliminar la caja.');
            return;
        }

        await cargarTabla();
    }

    document.getElementById('btn-filtrar').addEventListener('click', cargarTabla);

    const btnNueva = document.getElementById('btn-nueva-caja');
    if (btnNueva) {
        btnNueva.addEventListener('click', function () {
            limpiarForm();
            document.getElementById('modal-caja-title').textContent = 'Nueva caja';
            modal.show();
        });
    }

    document.getElementById('caj_scl_id').addEventListener('change', function (event) {
        llenarAlmacenesPorSucursal(event.target.value || '', '');
    });

    form.addEventListener('submit', guardarCaja);

    tbody.addEventListener('click', async function (event) {
        const btn = event.target.closest('button[data-action]');
        if (!btn) return;

        const action = btn.dataset.action;
        const id = btn.dataset.id;

        if (action === 'editar') {
            await abrirEditar(id);
            return;
        }

        if (action === 'estatus') {
            await cambiarEstatus(id, btn.dataset.estatus);
            return;
        }

        if (action === 'eliminar') {
            await eliminarCaja(id);
        }
    });

    initUsuariosSelect();
    cargarTabla();
})();
</script>
@endpush
