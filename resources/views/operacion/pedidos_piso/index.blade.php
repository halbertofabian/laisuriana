@extends('layouts.app')

@section('title', 'Pedidos de Piso')

@push('vendor-styles')
<style>
    .pp-layout .card { border-radius: 0.8rem; }
    .pp-form-card .card-body { padding: 1rem 1rem 1.1rem; }

    .pp-section-label {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: var(--ls-text-muted, #9090aa);
        margin-bottom: 0.35rem;
    }

    .pp-ubicacion-block {
        padding: 0.1rem 0 0;
        margin-top: 0.4rem;
    }

    .pp-search-wrap {
        background: #fff;
        border: 1px solid #d8deea;
        border-radius: 0.7rem;
        padding: 0.75rem 0.85rem;
    }

    .pp-search-wrap .form-control {
        font-size: 1rem;
        height: 2.7rem;
        border-radius: 0.6rem;
    }

    #sugerencias .list-group-item {
        padding: 0.65rem 0.85rem;
        border-left: 0;
        border-right: 0;
        text-transform: none;
        letter-spacing: 0;
    }
    #sugerencias .list-group-item:hover { background: rgba(115,103,240,0.05); }
    #sugerencias .list-group-item.active {
        background: rgba(115,103,240,0.12);
        border-color: rgba(115,103,240,0.15);
        color: inherit;
    }

    #sugerencias .list-group-item:first-child { border-top: 0; }
    #sugerencias .list-group-item:last-child  { border-bottom: 0; }

    .pp-items-table {
        margin-bottom: 0;
    }

    .pp-items-table thead th {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--ls-text-muted, #9090aa);
        border-bottom-width: 1px;
        padding: 0.5rem 0.6rem;
        background: transparent;
    }

    .pp-items-table tbody td {
        padding: 0.75rem 0.6rem;
        vertical-align: middle;
        text-transform: none;
        letter-spacing: 0;
    }

    .pp-items-table tbody tr {
        border-bottom: 1px solid #f0f2fa;
    }

    .pp-items-table tbody tr:last-child {
        border-bottom: 0;
    }

    .pp-item-name {
        font-weight: 600;
        font-size: 0.88rem;
        line-height: 1.3;
        color: #2f2b47;
        text-transform: none;
    }

    .pp-item-sku {
        font-size: 0.75rem;
        color: var(--ls-text-muted, #9090aa);
        margin-top: 0.15rem;
        text-transform: none;
        letter-spacing: 0;
    }

    .pp-item-price {
        font-weight: 700;
        font-size: 1rem;
        color: #2f2b47;
        white-space: nowrap;
    }

    .pp-del-btn {
        font-size: 0.9rem;
        line-height: 1;
        color: #dc3545;
    }

    .pp-empty-state {
        text-align: center;
        padding: 2rem 1rem;
        color: var(--ls-text-muted, #aaa);
        font-size: 0.88rem;
        text-transform: none;
        letter-spacing: 0;
        font-weight: 400;
    }

    .pp-empty-state i {
        font-size: 2.2rem;
        opacity: 0.25;
        display: block;
        margin-bottom: 0.5rem;
    }

    .pp-qty-ctrl {
        display: inline-flex;
        align-items: center;
        border: 1px solid #cfd7e6;
        border-radius: 0.65rem;
        overflow: hidden;
        width: 136px;
        height: 40px;
        background: #fff;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.65);
    }

    .pp-qty-btn {
        width: 36px;
        height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        font-weight: 800;
        border: 0;
        background: #f4f7fc;
        color: #4a5570;
        flex-shrink: 0;
        cursor: pointer;
        transition: background .12s, color .12s;
        line-height: 1;
    }

    .pp-qty-btn:hover {
        background: #e7edf7;
        color: #2f3a52;
    }

    .pp-qty-ctrl input {
        border: 0;
        border-left: 1px solid #d9e1ef;
        border-right: 1px solid #d9e1ef;
        border-radius: 0;
        text-align: center;
        width: 64px;
        height: 40px;
        font-size: 1rem;
        font-weight: 700;
        color: #2f2b47;
        padding: 0;
        background: #fff;
        box-shadow: none !important;
    }

    .pp-total-bar {
        background: linear-gradient(135deg, #322f47 0%, #44408a 100%);
        border-radius: 0.85rem;
        padding: 0.85rem 1.1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: #fff;
    }

    .pp-total-bar .pp-total-label {
        font-size: 0.82rem;
        font-weight: 600;
        opacity: 0.75;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .pp-total-bar .pp-total-amount {
        font-size: 1.5rem;
        font-weight: 800;
        letter-spacing: -0.01em;
    }

    .pp-btn-guardar {
        height: 2.85rem;
        font-size: 1rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        border-radius: 0.7rem;
    }

    .pp-folio {
        font-family: monospace;
        font-weight: 700;
        font-size: 0.92rem;
        color: var(--ls-accent, #7367f0);
        white-space: nowrap;
    }

    .pp-pedidos-card .card-header {
        padding: 0.9rem 1.25rem;
    }

    .pp-search-folio {
        max-width: 210px;
        border-radius: 999px;
        font-size: 0.88rem;
    }

    .pp-pedidos-table thead th {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--ls-text-muted, #9090aa);
        border-bottom-width: 1px;
        padding: 0.55rem 0.75rem;
    }

    .pp-pedidos-table tbody td {
        padding: 0.65rem 0.75rem;
        vertical-align: middle;
    }

    .pp-pedidos-table tbody tr:hover { background: rgba(115,103,240,0.03); }

    .pp-card-section {
        border: 1px solid #e7ecf5;
        border-radius: 0.65rem;
        padding: 0.65rem 0.75rem;
        background: #fff;
        margin-inline: 0.25rem;
    }

    .pp-table-shell {
        border: 1px solid #edf0f7;
        border-radius: 0.65rem;
        overflow: hidden;
    }

    .pp-pedidos-table td,
    .pp-pedidos-table th {
        text-transform: none;
        letter-spacing: 0;
    }

    .pp-pedidos-table td:nth-child(1) { width: 20%; }
    .pp-pedidos-table td:nth-child(2) { width: 28%; }
    .pp-pedidos-table td:nth-child(3) { width: 18%; }
    .pp-pedidos-table td:nth-child(4) { width: 20%; }
    .pp-pedidos-table td:nth-child(5) { width: 14%; }

    .pp-badge-pendiente { background: rgba(255,159,67,0.15); color: #e07c00; font-weight: 600; border-radius: 999px; padding: 0.3rem 0.7rem; font-size: 0.78rem; white-space: nowrap; display: inline-flex; }
    .pp-badge-cobrado   { background: rgba(40,199,111,0.15); color: #1a8e47; font-weight: 600; border-radius: 999px; padding: 0.3rem 0.7rem; font-size: 0.78rem; white-space: nowrap; display: inline-flex; }
    .pp-badge-cancelado { background: rgba(234,84,85,0.15);  color: #b71c1c; font-weight: 600; border-radius: 999px; padding: 0.3rem 0.7rem; font-size: 0.78rem; white-space: nowrap; display: inline-flex; }
    .pp-badge-default   { background: rgba(130,134,139,0.15); color: #555; font-weight: 600; border-radius: 999px; padding: 0.3rem 0.7rem; font-size: 0.78rem; white-space: nowrap; display: inline-flex; }

    .pp-del-btn {
        width: 2rem;
        height: 2rem;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.45rem;
        font-size: 0.95rem;
    }

    @media (max-width: 1199.98px) {
        .pp-layout .col-lg-5,
        .pp-layout .col-lg-7 {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
<x-section-header
    eyebrow="Punto de venta"
    icon="tabler-notes"
    title="Pedidos de Piso"
    subtitle="Captura pedidos por almacén para cobro posterior en caja."
/>

<div class="row g-4 pp-layout">

    {{-- ── FORMULARIO NUEVO PEDIDO ─────────────────────────── --}}
    <div class="col-xl-5 col-lg-6">
        <div class="card pp-form-card">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="tabler-clipboard-plus text-primary fs-5"></i>
                <h5 class="mb-0">Nuevo pedido</h5>
            </div>
            <div class="card-body d-flex flex-column gap-3">
                <form id="form-pedido">

                    {{-- Ubicación --}}
                    <div class="pp-ubicacion-block mb-3 pp-card-section">
                        <div class="pp-section-label mb-3">
                            <i class="tabler-map-pin me-1"></i>Ubicación
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label">Sucursal</label>
                                <select class="form-select" id="pdp_scl_id" name="pdp_scl_id" required>
                                    <option value="">Selecciona</option>
                                    @foreach($opciones['sucursales'] as $s)
                                        <option value="{{ $s->scl_id }}">{{ $s->scl_nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Almacén</label>
                                <select class="form-select" id="pdp_alm_id" name="pdp_alm_id" required>
                                    <option value="">Selecciona</option>
                                    @foreach($opciones['almacenes'] as $a)
                                        <option value="{{ $a->alm_id }}" data-scl="{{ $a->alm_scl_id }}">{{ $a->alm_nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Buscador de productos --}}
                    <div class="pp-search-wrap mb-3 position-relative">
                        <div class="pp-section-label mb-2"><i class="tabler-scan me-1"></i>Agregar producto</div>
                        <input
                            class="form-control"
                            id="buscar_producto"
                            placeholder="Escanea o escribe nombre, código o barras…"
                            autocomplete="off"
                        />
                        <div id="sugerencias" class="list-group d-none mt-1 border rounded shadow-sm" style="position:absolute;left:0;right:0;z-index:20;max-height:240px;overflow:auto;background:#fff;"></div>
                    </div>

                    {{-- Partidas --}}
                    <div class="mb-1 pp-section-label">Productos en el pedido</div>
                    <div class="table-responsive mb-3 pp-table-shell" style="min-height:60px;">
                        <table class="table pp-items-table" id="tbl-partidas">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th style="width:120px">Cantidad</th>
                                    <th style="width:100px">Precio</th>
                                    <th style="width:44px"></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    {{-- Notas --}}
                    <div class="mb-3">
                        <label class="form-label pp-section-label">Notas del pedido <span class="text-body-secondary fw-normal">(opcional)</span></label>
                        <textarea class="form-control" id="pdp_observaciones" name="pdp_observaciones" rows="2" placeholder="Instrucciones especiales, referencias…"></textarea>
                    </div>

                    {{-- Total --}}
                    <div class="pp-total-bar mb-3">
                        <span class="pp-total-label">Total del pedido</span>
                        <span class="pp-total-amount" id="total-pedido">$0.00</span>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 pp-btn-guardar" @if(!$permisosUI['crear']) disabled @endif>
                        <i class="tabler-device-floppy me-1"></i>Generar pedido
                    </button>

                </form>
            </div>
        </div>
    </div>

    {{-- ── LISTA DE PEDIDOS ─────────────────────────────────── --}}
    <div class="col-xl-7 col-lg-6">
        <div class="card pp-pedidos-card">
            <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <div class="d-flex align-items-center gap-2">
                    <i class="tabler-list-details text-primary fs-5"></i>
                    <h5 class="mb-0">Pedidos generados</h5>
                </div>
                <input
                    class="form-control pp-search-folio"
                    id="flt-buscar"
                    placeholder="Buscar por folio…"
                />
            </div>
            <div class="table-responsive">
                <table class="table pp-pedidos-table mb-0" id="tbl-pedidos">
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Sucursal / Almacén</th>
                            <th>Vendedor</th>
                            <th>Estado</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

@push('page-scripts')
<script>
(() => {
    const rutas = {
        data: '{{ route('operacion.pedidos_piso.data') }}',
        store: '{{ route('operacion.pedidos_piso.store') }}',
        buscarProductos: '{{ route('operacion.pedidos_piso.productos.buscar') }}',
    };

    const form = document.getElementById('form-pedido');
    const tbodyPartidas = document.querySelector('#tbl-partidas tbody');
    const tbodyPedidos = document.querySelector('#tbl-pedidos tbody');
    const inpBuscar = document.getElementById('buscar_producto');
    const boxSugerencias = document.getElementById('sugerencias');
    const inpFiltro = document.getElementById('flt-buscar');
    const selectSucursal = document.getElementById('pdp_scl_id');
    const selectAlmacen = document.getElementById('pdp_alm_id');

    let timer = null;
    let partidas = [];
    let sugerenciasActuales = [];
    let sugerenciaActiva = -1;

    function money(v){ return '$' + Number(v || 0).toLocaleString('es-MX', { minimumFractionDigits:2, maximumFractionDigits:2 }); }

    function estatusBadge(estatus) {
        const e = (estatus || '').toLowerCase().replace(/_/g, ' ');
        const label = e.replace(/\b\w/g, c => c.toUpperCase());
        if (e.includes('pendiente')) return `<span class="pp-badge-pendiente">${label}</span>`;
        if (e.includes('cobrado'))   return `<span class="pp-badge-cobrado">${label}</span>`;
        if (e.includes('cancelado')) return `<span class="pp-badge-cancelado">${label}</span>`;
        return `<span class="pp-badge-default">${label}</span>`;
    }

    function renderPartidas() {
        if (!partidas.length) {
            tbodyPartidas.innerHTML = `
                <tr>
                    <td colspan="4" style="text-transform:none;letter-spacing:0;font-weight:400;">
                        <div class="pp-empty-state">
                            <i class="tabler-shopping-cart-off"></i>
                            <span>Busca un producto arriba para agregarlo al pedido</span>
                        </div>
                    </td>
                </tr>`;
            document.getElementById('total-pedido').textContent = '$0.00';
            return;
        }

        tbodyPartidas.innerHTML = partidas.map((p, idx) => `
            <tr>
                <td>
                    <div class="pp-item-name">${p.nombre}</div>
                    <div class="pp-item-sku">${p.sku}</div>
                </td>
                <td>
                    <div class="pp-qty-ctrl">
                        <button type="button" class="pp-qty-btn" data-k="dec" data-i="${idx}">−</button>
                        <input
                            type="number"
                            min="0.01"
                            step="0.01"
                            class="form-control form-control-sm"
                            data-k="cant"
                            data-i="${idx}"
                            value="${p.cantidad}"
                        >
                        <button type="button" class="pp-qty-btn" data-k="inc" data-i="${idx}">+</button>
                    </div>
                </td>
                <td><span class="pp-item-price">${money(p.precio)}</span></td>
                <td>
                    <button class="btn btn-sm btn-outline-danger pp-del-btn" data-k="del" data-i="${idx}" title="Quitar">✕</button>
                </td>
            </tr>
        `).join('');

        const total = partidas.reduce((s, p) => s + (Number(p.cantidad) * Number(p.precio)), 0);
        document.getElementById('total-pedido').textContent = money(total);
    }

    function addPartida(item) {
        const idx = partidas.findIndex(p => Number(p.ppd_psk_id) === Number(item.psk_id));
        if (idx >= 0) {
            partidas[idx].cantidad = Number(partidas[idx].cantidad) + 1;
            renderPartidas();
            return;
        }
        partidas.push({
            ppd_psk_id: item.psk_id,
            sku: item.psk_codigo,
            nombre: item.psk_nombre || item.producto?.prd_nombre || item.psk_codigo,
            cantidad: 1,
            precio: Number(item.psk_precio || 0),
        });
        renderPartidas();
    }

    async function cargarPedidos() {
        const q = inpFiltro.value.trim();
        const res = await fetch(`${rutas.data}?buscar=${encodeURIComponent(q)}`, { headers: { 'Accept': 'application/json' } });
        const json = await res.json();
        const rows = json.data || [];

        tbodyPedidos.innerHTML = rows.length ? rows.map(r => `
            <tr>
                <td class="pp-folio">${r.pdp_folio}</td>
                <td>
                    <div class="fw-semibold">${r.sucursal || '—'}</div>
                    <small class="text-body-secondary">${r.almacen || ''}</small>
                </td>
                <td>${r.vendedor || '—'}</td>
                <td>${estatusBadge(r.pdp_estatus)}</td>
                <td class="text-end fw-semibold">${money(r.pdp_total)}</td>
            </tr>
        `).join('') : `<tr><td colspan="5" class="text-center text-body-secondary py-4">No hay pedidos registrados</td></tr>`;
    }

    async function buscarProductos(q) {
        const res = await fetch(`${rutas.buscarProductos}?q=${encodeURIComponent(q)}`, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) {
            sugerenciasActuales = [];
            sugerenciaActiva = -1;
            boxSugerencias.classList.add('d-none');
            boxSugerencias.innerHTML = '';
            return;
        }

        const json = await res.json();
        const data = json.data || [];
        if (!data.length) {
            sugerenciasActuales = [];
            sugerenciaActiva = -1;
            boxSugerencias.classList.add('d-none');
            boxSugerencias.innerHTML = '';
            return;
        }

        sugerenciasActuales = data;
        sugerenciaActiva = 0;
        boxSugerencias.innerHTML = data.map((d) => `
            <button type="button" class="list-group-item list-group-item-action" data-idx="${data.indexOf(d)}" data-psk='${JSON.stringify(d).replace(/'/g, '&#39;')}'>
                <div class="fw-semibold">${d.psk_nombre || d.producto?.prd_nombre || d.psk_codigo}</div>
                <small class="text-body-secondary">${d.psk_codigo} · ${d.psk_codigo_barras || 'Sin barras'} · ${money(d.psk_precio || 0)}</small>
            </button>
        `).join('');
        marcarSugerenciaActiva();
        boxSugerencias.classList.remove('d-none');
    }

    function marcarSugerenciaActiva() {
        const items = [...boxSugerencias.querySelectorAll('[data-idx]')];
        items.forEach((el, i) => {
            if (i === sugerenciaActiva) {
                el.classList.add('active');
                el.scrollIntoView({ block: 'nearest' });
            } else {
                el.classList.remove('active');
            }
        });
    }

    function cerrarSugerencias() {
        boxSugerencias.classList.add('d-none');
        sugerenciasActuales = [];
        sugerenciaActiva = -1;
    }

    function seleccionarSugerenciaActiva() {
        if (!sugerenciasActuales.length) return;
        const item = sugerenciasActuales[sugerenciaActiva >= 0 ? sugerenciaActiva : 0];
        if (!item) return;
        addPartida(item);
        inpBuscar.value = '';
        boxSugerencias.innerHTML = '';
        cerrarSugerencias();
        inpBuscar.focus();
    }

    inpBuscar.addEventListener('input', () => {
        clearTimeout(timer);
        const q = inpBuscar.value.trim();
        if (q.length < 2) {
            cerrarSugerencias();
            boxSugerencias.innerHTML = '';
            return;
        }
        timer = setTimeout(() => buscarProductos(q), 200);
    });

    inpBuscar.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            e.preventDefault();
            cerrarSugerencias();
            return;
        }

        if (e.key === 'ArrowDown' && sugerenciasActuales.length) {
            e.preventDefault();
            sugerenciaActiva = Math.min(sugerenciasActuales.length - 1, (sugerenciaActiva < 0 ? 0 : sugerenciaActiva + 1));
            marcarSugerenciaActiva();
            return;
        }

        if (e.key === 'ArrowUp' && sugerenciasActuales.length) {
            e.preventDefault();
            sugerenciaActiva = Math.max(0, (sugerenciaActiva < 0 ? 0 : sugerenciaActiva - 1));
            marcarSugerenciaActiva();
            return;
        }

        if (e.key === 'Enter') {
            if (sugerenciasActuales.length) {
                e.preventDefault();
                seleccionarSugerenciaActiva();
            }
        }
    });

    document.addEventListener('click', (e) => {
        const dentroInput = e.target.closest('#buscar_producto');
        const dentroLista = e.target.closest('#sugerencias');
        if (!dentroInput && !dentroLista) {
            cerrarSugerencias();
        }
    });

    boxSugerencias.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-psk]');
        if (!btn) return;
        const item = JSON.parse(btn.getAttribute('data-psk'));
        addPartida(item);
        inpBuscar.value = '';
        boxSugerencias.innerHTML = '';
        cerrarSugerencias();
        inpBuscar.focus();
    });

    tbodyPartidas.addEventListener('input', (e) => {
        const el = e.target;
        const idx = Number(el.dataset.i);
        if (Number.isNaN(idx) || !partidas[idx]) return;

        if (el.dataset.k === 'cant') partidas[idx].cantidad = Math.max(0.01, Number(el.value || 0));
        renderPartidas();
    });

    tbodyPartidas.addEventListener('click', (e) => {
        const el = e.target.closest('[data-k]');
        if (!el) return;
        const accion = el.dataset.k;
        const idx = Number(el.dataset.i);
        if (Number.isNaN(idx) || !partidas[idx]) return;

        if (accion === 'inc') {
            partidas[idx].cantidad = Number(partidas[idx].cantidad) + 1;
            renderPartidas();
            return;
        }

        if (accion === 'dec') {
            partidas[idx].cantidad = Math.max(0.01, Number(partidas[idx].cantidad) - 1);
            renderPartidas();
            return;
        }

        if (accion !== 'del') return;
        partidas.splice(idx, 1);
        renderPartidas();
    });

    selectSucursal.addEventListener('change', () => {
        const scl = selectSucursal.value;
        [...selectAlmacen.options].forEach((opt) => {
            if (!opt.value) return;
            opt.hidden = scl !== '' && opt.dataset.scl !== scl;
        });
        if (selectAlmacen.selectedOptions[0]?.hidden) {
            selectAlmacen.value = '';
        }
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!partidas.length) {
            alert('Agrega al menos una partida al pedido.');
            return;
        }

        const payload = {
            pdp_scl_id: selectSucursal.value,
            pdp_alm_id: selectAlmacen.value,
            pdp_observaciones: document.getElementById('pdp_observaciones').value,
            partidas: partidas.map((p) => ({
                ppd_psk_id: p.ppd_psk_id,
                ppd_cantidad: p.cantidad,
            })),
        };

        const res = await fetch(rutas.store, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify(payload),
        });

        if (!res.ok) {
            const err = await res.json();
            const first = Object.values(err.errors || {})[0];
            alert(first ? first[0] : 'No se pudo guardar el pedido.');
            return;
        }

        const json = await res.json();
        alert(`Pedido generado: ${json.data.pdp_folio}`);

        form.reset();
        partidas = [];
        renderPartidas();
        await cargarPedidos();
    });

    inpFiltro.addEventListener('input', () => cargarPedidos());

    renderPartidas();
    cargarPedidos();
})();
</script>
@endpush
