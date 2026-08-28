@extends('layouts.app')

@section('title', 'Ventas')

@section('content')
<x-section-header
    eyebrow="Punto de venta"
    icon="tabler-receipt-2"
    title="Ventas"
    subtitle="Listado de ventas cobradas en caja con filtros de consulta."
/>

<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label">Buscar</label>
                <input type="text" class="form-control" id="flt-buscar" placeholder="Folio, vendedor o cliente">
            </div>
            <div class="col-md-2">
                <label class="form-label">Caja</label>
                <select class="form-select" id="flt-caja">
                    <option value="">Todas</option>
                    @foreach($cajas as $caja)
                        <option value="{{ $caja->caj_id }}">{{ $caja->caj_nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Almacén involucrado</label>
                <select class="form-select" id="flt-almacen">
                    <option value="">Todos</option>
                    @foreach($almacenes as $almacen)
                        <option value="{{ $almacen->alm_id }}">{{ $almacen->alm_nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Desde</label>
                <input type="date" class="form-control" id="flt-desde">
            </div>
            <div class="col-md-2">
                <label class="form-label">Hasta</label>
                <input type="date" class="form-control" id="flt-hasta">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100" id="btn-filtrar">Filtrar</button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table" id="tbl-ventas">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Fecha</th>
                        <th>Caja</th>
                        <th>Almacenes involucrados</th>
                        <th>Vendedor</th>
                        <th>Cliente</th>
                        <th>Método</th>
                        <th class="text-end">Total</th>
                        <th style="width:1%">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('page-scripts')
<script>
(function () {
    const tbody = document.querySelector('#tbl-ventas tbody');
    const rutaData = '{{ route('operacion.ventas.data') }}';
    const rutaTicketBase = '{{ url('/pos/ventas') }}';

    function esc(s) {
        return String(s ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;');
    }

    function money(v) {
        return '$' + Number(v || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    async function cargar() {
        const p = new URLSearchParams({
            buscar: document.getElementById('flt-buscar').value || '',
            caja_id: document.getElementById('flt-caja').value || '',
            almacen_id: document.getElementById('flt-almacen').value || '',
            fecha_desde: document.getElementById('flt-desde').value || '',
            fecha_hasta: document.getElementById('flt-hasta').value || '',
        });
        const res = await fetch(`${rutaData}?${p.toString()}`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        const rows = (await res.json()).data || [];

        tbody.innerHTML = rows.map((r) => `
            <tr>
                <td class="fw-semibold">${esc(r.psv_folio)}</td>
                <td>${esc(r.psv_fecha_cobro || '')}</td>
                <td>${esc(r.caj_nombre || '—')}</td>
                <td>${esc(r.almacenes_involucrados || r.alm_nombre || '—')}</td>
                <td>${esc(r.vendedor || '—')}</td>
                <td>${esc((r.cliente || '').trim() || 'Público general')}</td>
                <td>${esc((r.psv_metodo_pago || '').toUpperCase())}</td>
                <td class="text-end fw-semibold">${money(r.psv_total)}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-secondary" data-ticket="${r.psv_id}">
                        <i class="ti tabler-printer me-1"></i>Ticket
                    </button>
                </td>
            </tr>
        `).join('');
    }

    document.getElementById('btn-filtrar').addEventListener('click', cargar);
    document.getElementById('flt-buscar').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') cargar();
    });
    tbody.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-ticket]');
        if (!btn) return;
        const id = btn.getAttribute('data-ticket');
        if (!id) return;
        window.open(`${rutaTicketBase}/${id}/ticket`, '_blank');
    });

    cargar();
})();
</script>
@endpush
