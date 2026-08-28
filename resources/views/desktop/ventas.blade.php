@extends('layouts.desktop')

@section('title', 'Ventas')

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        <div class="page-head">
            <div class="page-head__title">Ventas</div>
            <div class="page-head__sub">Listado de ventas cobradas en caja.</div>
        </div>
    </div>

    <div class="desktop-toolbar__group">
        <input type="search" class="desktop-toolbar__search" id="ventas-filtro-buscar" placeholder="Folio, vendedor o cliente">
        <select class="desktop-toolbar__select" id="ventas-filtro-caja">
            <option value="">Todas las cajas</option>
            @foreach(($opciones['cajas'] ?? []) as $caja)
                <option value="{{ $caja->caj_id }}">{{ $caja->caj_nombre }}</option>
            @endforeach
        </select>
        <select class="desktop-toolbar__select" id="ventas-filtro-almacen">
            <option value="">Todos los almacenes</option>
            @foreach(($opciones['almacenes'] ?? []) as $almacen)
                <option value="{{ $almacen->alm_id }}">{{ $almacen->alm_nombre }}</option>
            @endforeach
        </select>
        <input type="date" class="desktop-toolbar__search" id="ventas-filtro-desde" style="width: 142px;">
        <input type="date" class="desktop-toolbar__search" id="ventas-filtro-hasta" style="width: 142px;">
        <button type="button" class="desktop-btn desktop-btn--primary" id="ventas-btn-filtrar">Filtrar</button>
    </div>
@endsection

@section('content')
    <section class="desktop-pane">
        <div class="desktop-list-wrap">
            <table class="desktop-list" id="desktop-ventas-table">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Fecha</th>
                        <th>Caja</th>
                        <th>Almacenes involucrados</th>
                        <th>Vendedor</th>
                        <th>Cliente</th>
                        <th>Método</th>
                        <th style="text-align:right;">Total</th>
                        <th style="width:1%;">Acción</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div class="desktop-list-foot">
            <span id="desktop-ventas-info">Cargando ventas...</span>
        </div>
    </section>
@endsection

@push('desktop-scripts')
<script>
    (function () {
        const tbody = document.querySelector('#desktop-ventas-table tbody');
        const info = document.getElementById('desktop-ventas-info');
        const rutaData = @json(route('desktop.ventas.data'));
        const rutaTicketBase = @json(url('/pos/ventas'));

        function esc(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;');
        }

        function money(value) {
            return '$' + Number(value || 0).toLocaleString('es-MX', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function fecha(value) {
            if (!value) return '—';
            const dt = new Date(value);
            if (Number.isNaN(dt.getTime())) return esc(value);
            return dt.toLocaleString('es-MX', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function render(rows) {
            if (!rows.length) {
                tbody.innerHTML = '<tr><td colspan="9" class="desktop-list__empty">No hay ventas para los filtros seleccionados.</td></tr>';
                info.textContent = 'Sin resultados';
                return;
            }

            tbody.innerHTML = rows.map((row) => `
                <tr>
                    <td><strong>${esc(row.psv_folio)}</strong></td>
                    <td>${fecha(row.psv_fecha_cobro)}</td>
                    <td>${esc(row.caj_nombre || '—')}</td>
                    <td>${esc(row.almacenes_involucrados || row.alm_nombre || '—')}</td>
                    <td>${esc(row.vendedor || '—')}</td>
                    <td>${esc((row.cliente || '').trim() || 'Público general')}</td>
                    <td>${esc((row.psv_metodo_pago || '').toUpperCase() || '—')}</td>
                    <td style="text-align:right; font-weight:600;">${money(row.psv_total)}</td>
                    <td>
                        <button type="button" class="desktop-btn desktop-btn--default" data-ticket="${row.psv_id}">Ticket</button>
                    </td>
                </tr>
            `).join('');
            info.textContent = rows.length + ' venta(s)';
        }

        async function cargar() {
            info.textContent = 'Consultando ventas...';

            const params = new URLSearchParams({
                buscar: document.getElementById('ventas-filtro-buscar').value || '',
                caja_id: document.getElementById('ventas-filtro-caja').value || '',
                almacen_id: document.getElementById('ventas-filtro-almacen').value || '',
                fecha_desde: document.getElementById('ventas-filtro-desde').value || '',
                fecha_hasta: document.getElementById('ventas-filtro-hasta').value || '',
            });

            try {
                const response = await fetch(`${rutaData}?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const payload = await response.json();
                render(Array.isArray(payload.data) ? payload.data : []);
            } catch (error) {
                tbody.innerHTML = '<tr><td colspan="9" class="desktop-list__empty">No fue posible cargar las ventas.</td></tr>';
                info.textContent = 'Error al consultar';
            }
        }

        document.getElementById('ventas-btn-filtrar').addEventListener('click', cargar);
        document.getElementById('ventas-filtro-buscar').addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                cargar();
            }
        });

        tbody.addEventListener('click', function (event) {
            const button = event.target.closest('[data-ticket]');
            if (!button) return;
            const ventaId = button.getAttribute('data-ticket');
            if (!ventaId) return;
            window.open(`${rutaTicketBase}/${ventaId}/ticket`, '_blank');
        });

        cargar();
    })();
</script>
@endpush
