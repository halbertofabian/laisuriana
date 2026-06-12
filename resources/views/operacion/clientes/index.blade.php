@extends('layouts.app')

@section('title', 'Clientes')

@section('content')
<x-section-header
    eyebrow="Punto de venta"
    icon="tabler-users"
    title="Clientes"
    subtitle="Alta y administración de clientes para ventas, pedidos y facturación."
/>

<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-5">
                <label class="form-label">Buscar</label>
                <input type="text" class="form-control" id="flt-buscar" placeholder="Nombre, RFC, CURP, email o teléfono" />
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
            <div class="col-md-2 d-flex align-items-end justify-content-end">
                @if($permisosUI['cliente_crear'])
                    <button class="btn btn-primary w-100" id="btn-nuevo">
                        <i class="ti tabler-plus me-1"></i>Nuevo
                    </button>
                @endif
            </div>
        </div>

        <div class="table-responsive">
            <table class="table" id="tbl-clientes">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Desc.</th>
                        <th>Contacto</th>
                        <th>Documentos</th>
                        <th>Dirección</th>
                        <th>Estatus</th>
                        <th style="width:1%">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

@include("operacion.clientes.partials.modal_cliente")
@endsection

@push('page-scripts')
<script>
(function () {
    const permisos = @json($permisosUI);
    const modal = new bootstrap.Modal(document.getElementById('modal-cliente'));
    const tbody = document.querySelector('#tbl-clientes tbody');
    const form = document.getElementById('form-cliente');
    let cpRows = [];

    const rutas = {
        data: '{{ route('operacion.clientes.data') }}',
        show: (id) => '{{ url('/operacion/clientes') }}/' + id,
        store: '{{ route('operacion.clientes.store') }}',
        update: (id) => '{{ url('/operacion/clientes') }}/' + id,
        estatus: (id) => '{{ url('/operacion/clientes') }}/' + id + '/estatus',
        destroy: (id) => '{{ url('/operacion/clientes') }}/' + id,
        cpBuscar: '{{ route('operacion.clientes.cp.buscar') }}',
    };

    function esc(s) {
        return String(s ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;');
    }

    function estatusBadge(estatus) {
        return estatus === 'activo'
            ? '<span class="ls-badge ls-badge-success">Activo</span>'
            : '<span class="ls-badge ls-badge-danger">Inactivo</span>';
    }

    function accionesHtml(row) {
        let items = '';
        if (permisos.cliente_editar) items += `<button class="dropdown-item" data-action="editar" data-id="${row.cli_id}">Editar</button>`;
        if (permisos.cliente_inactivar) {
            const next = row.cli_estatus === 'activo' ? 'inactivo' : 'activo';
            const label = row.cli_estatus === 'activo' ? 'Inactivar' : 'Activar';
            items += `<button class="dropdown-item" data-action="estatus" data-id="${row.cli_id}" data-estatus="${next}">${label}</button>`;
        }
        if (permisos.cliente_eliminar) {
            if (items) items += `<div class="dropdown-divider"></div>`;
            items += `<button class="dropdown-item text-danger" data-action="eliminar" data-id="${row.cli_id}">Eliminar</button>`;
        }
        if (!items) return '<span class="text-body-secondary">Sin acciones</span>';
        return `<div class="dropdown"><button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">Acciones</button><div class="dropdown-menu dropdown-menu-end">${items}</div></div>`;
    }

    async function cargarTabla() {
        const params = new URLSearchParams({
            buscar: document.getElementById('flt-buscar').value || '',
            estatus: document.getElementById('flt-estatus').value || '',
        });

        const res = await fetch(`${rutas.data}?${params.toString()}`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        const rows = (await res.json()).data || [];

        tbody.innerHTML = rows.map((row) => `
            <tr>
                <td><div class="fw-semibold">${esc(row.nombre_completo)}</div><small class="text-body-secondary">${esc(row.cli_razon_social || '')}</small></td>
                <td>${row.cli_descuento_default ? esc(row.cli_descuento_default) + '%' : '—'}</td>
                <td><div>${esc(row.cli_telefono || '—')}</div><small class="text-body-secondary">${esc(row.cli_email || row.cli_whatsapp || '')}</small></td>
                <td><div>RFC: ${esc(row.cli_rfc || '—')}</div><small class="text-body-secondary">CURP: ${esc(row.cli_curp || '—')}</small></td>
                <td>${esc(row.direccion || 'Sin dirección')}</td>
                <td>${estatusBadge(row.cli_estatus)}</td>
                <td class="text-end">${accionesHtml(row)}</td>
            </tr>
        `).join('');
    }

    function limpiarForm() {
        form.reset();
        document.getElementById('cli_id').value = '';
        document.getElementById('cliente-preview-nombre').textContent = 'Vista previa del nombre';
        document.getElementById('doc_tipo').value = '';
        document.getElementById('doc_valor').value = '';
        document.getElementById('cli_rfc').value = '';
        document.getElementById('cli_curp').value = '';
        document.getElementById('cli_ine').value = '';
        cpRows = [];
        document.getElementById('cli_colonia').innerHTML = '<option value="">Selecciona</option>';
    }

    function asignarDatos(row) {
        Object.keys(row).forEach((k) => {
            const el = document.getElementById(k);
            if (el && row[k] !== null) el.value = row[k];
        });
        if (row.cli_rfc) {
            document.getElementById('doc_tipo').value = 'rfc';
            document.getElementById('doc_valor').value = row.cli_rfc;
        } else if (row.cli_curp) {
            document.getElementById('doc_tipo').value = 'curp';
            document.getElementById('doc_valor').value = row.cli_curp;
        } else if (row.cli_ine) {
            document.getElementById('doc_tipo').value = 'ine';
            document.getElementById('doc_valor').value = row.cli_ine;
        }
    }

    function sincronizarDocumento() {
        const tipo = (document.getElementById('doc_tipo').value || '').toLowerCase();
        const valor = (document.getElementById('doc_valor').value || '').trim().toUpperCase();
        document.getElementById('cli_rfc').value = '';
        document.getElementById('cli_curp').value = '';
        document.getElementById('cli_ine').value = '';
        if (!tipo || !valor) return;
        if (tipo === 'rfc') document.getElementById('cli_rfc').value = valor;
        if (tipo === 'curp') document.getElementById('cli_curp').value = valor;
        if (tipo === 'ine') document.getElementById('cli_ine').value = valor;
    }

    async function abrirEditar(id) {
        limpiarForm();
        const res = await fetch(rutas.show(id), { headers: { 'Accept': 'application/json' } });
        const row = (await res.json()).data;
        document.getElementById('modal-title').textContent = 'Editar cliente';
        document.getElementById('cli_id').value = row.cli_id;
        asignarDatos(row);
        if (row.cli_cp) {
            await buscarCP(false);
            if (row.cli_colonia) document.getElementById('cli_colonia').value = row.cli_colonia;
        }
        modal.show();
    }

    function payload() {
        sincronizarDocumento();
        const fd = new FormData(form);
        return Object.fromEntries(fd.entries());
    }

    async function guardar(e) {
        e.preventDefault();
        const id = document.getElementById('cli_id').value;
        const res = await fetch(id ? rutas.update(id) : rutas.store, {
            method: id ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload()),
        });
        if (!res.ok) {
            const error = await res.json();
            const first = Object.values(error.errors || {})[0];
            alert(first ? first[0] : 'No se pudo guardar el cliente.');
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
            body: JSON.stringify({ cli_estatus: estatus }),
        });
        if (!res.ok) return alert('No se pudo actualizar el estatus.');
        await cargarTabla();
    }

    async function eliminar(id) {
        if (!confirm('¿Deseas eliminar este cliente?')) return;
        const res = await fetch(rutas.destroy(id), {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!res.ok) return alert('No se pudo eliminar el cliente.');
        await cargarTabla();
    }

    function refrescarDependientesAsentamiento(colonia) {
        const found = cpRows.find((x) => (x.cp_asentamiento || '') === colonia);
        if (!found) return;
        document.getElementById('cli_tipo_asentamiento').value = found.cp_tipo_asentamiento || '';
        document.getElementById('cli_municipio').value = found.cp_municipio || '';
        document.getElementById('cli_estado').value = found.cp_estado || '';
        document.getElementById('cli_ciudad').value = found.cp_ciudad || '';
    }

    async function buscarCP(enfocarColonia = true) {
        const cp = (document.getElementById('cli_cp').value || '').trim();
        if (cp.length < 5) return;
        const res = await fetch(`${rutas.cpBuscar}?codigo_postal=${encodeURIComponent(cp)}`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        if (!res.ok) return;
        cpRows = (await res.json()).data || [];
        const select = document.getElementById('cli_colonia');
        select.innerHTML = '<option value="">Selecciona</option>' + cpRows.map((r) => `<option value="${esc(r.cp_asentamiento)}">${esc(r.cp_asentamiento)} (${esc(r.cp_tipo_asentamiento || 'Asentamiento')})</option>`).join('');
        if (cpRows.length) {
            select.value = cpRows[0].cp_asentamiento || '';
            refrescarDependientesAsentamiento(select.value);
            if (enfocarColonia) select.focus();
        }
    }

    document.getElementById('btn-filtrar').addEventListener('click', cargarTabla);
    document.getElementById('doc_tipo').addEventListener('change', sincronizarDocumento);
    document.getElementById('doc_valor').addEventListener('input', sincronizarDocumento);
    ['cli_nombre', 'cli_apellido_paterno', 'cli_apellido_materno'].forEach((id) => {
        document.getElementById(id).addEventListener('input', () => {
            const nombre = [
                document.getElementById('cli_nombre').value,
                document.getElementById('cli_apellido_paterno').value,
                document.getElementById('cli_apellido_materno').value,
            ].map((x) => (x || '').trim()).filter(Boolean).join(' ');
            document.getElementById('cliente-preview-nombre').textContent = nombre || 'Vista previa del nombre';
        });
    });
    const btnNuevo = document.getElementById('btn-nuevo');
    if (btnNuevo) btnNuevo.addEventListener('click', () => {
        limpiarForm();
        document.getElementById('modal-title').textContent = 'Nuevo cliente';
        modal.show();
    });
    form.addEventListener('submit', guardar);
    document.getElementById('cli_cp').addEventListener('blur', () => buscarCP(true));
    document.getElementById('cli_colonia').addEventListener('change', (e) => refrescarDependientesAsentamiento(e.target.value));

    tbody.addEventListener('click', async (event) => {
        const btn = event.target.closest('button[data-action]');
        if (!btn) return;
        const action = btn.dataset.action;
        const id = btn.dataset.id;
        if (action === 'editar') return abrirEditar(id);
        if (action === 'estatus') return cambiarEstatus(id, btn.dataset.estatus);
        if (action === 'eliminar') return eliminar(id);
    });

    cargarTabla();
})();
</script>
@endpush
