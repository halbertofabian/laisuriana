@extends('layouts.app')

@section('title', 'Negativos por Sesion de Caja')

@push('vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
@endpush

@section('content')
<x-section-header
    eyebrow="Operación"
    icon="tabler-alert-triangle"
    title="Stock Negativo por Sesión"
    subtitle="Audita existencias negativas generadas por ventas POS en cada sesión de caja."
/>

<div class="card mb-4">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label">Sucursal</label>
                <select class="form-select" id="flt-scl">
                    <option value="">Todas</option>
                    @foreach($opciones['sucursales'] as $sucursal)
                        <option value="{{ $sucursal->scl_id }}">{{ $sucursal->scl_nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Almacén</label>
                <select class="form-select" id="flt-alm">
                    <option value="">Todos</option>
                    @foreach($opciones['almacenes'] as $almacen)
                        <option value="{{ $almacen->alm_id }}" data-scl="{{ $almacen->alm_scl_id }}">{{ $almacen->alm_nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Sesión caja (ID)</label>
                <select class="form-select" id="flt-cse">
                    <option value="">Todas</option>
                    @foreach($sesionesCaja as $sesion)
                        <option value="{{ $sesion->cse_id }}">
                            #{{ $sesion->cse_id }} | {{ $sesion->caj_nombre }} | {{ $sesion->scl_nombre }} | {{ $sesion->cse_abierta_at ? \Illuminate\Support\Carbon::parse($sesion->cse_abierta_at)->format('Y-m-d H:i') : '—' }}
                        </option>
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
            <div class="col-md-2">
                <label class="form-label">Buscar</label>
                <input type="text" class="form-control" id="flt-buscar" placeholder="Folio/SKU/Caja">
            </div>
            <div class="col-md-12 d-flex gap-2 justify-content-end mt-2">
                <button class="btn btn-outline-secondary btn-sm" id="btn-limpiar"><i class="ti tabler-x me-1"></i>Limpiar</button>
                <button class="btn btn-primary btn-sm" id="btn-filtrar"><i class="ti tabler-filter me-1"></i>Aplicar</button>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle" id="tbl-negativos-sesion">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Sesión</th>
                        <th>Caja</th>
                        <th>Sucursal</th>
                        <th>Almacén</th>
                        <th>Venta</th>
                        <th>SKU</th>
                        <th>Producto</th>
                        <th class="text-end">Cantidad</th>
                        <th class="text-end">Antes</th>
                        <th class="text-end">Después</th>
                        <th>Usuario venta</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
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
    const rutaData = '{{ route('operacion.inventario_base.negativos_sesion.data') }}';
    const almacenesBase = @json($opciones['almacenes']->map(fn($a) => ['alm_id' => $a->alm_id, 'alm_scl_id' => $a->alm_scl_id, 'alm_nombre' => $a->alm_nombre])->values());

    function esc(v) {
        return String(v ?? '').replace(/[&<>"']/g, function (m) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m];
        });
    }

    function syncAlmacenes() {
        const sclId = String($('#flt-scl').val() || '');
        const current = String($('#flt-alm').val() || '');
        const $alm = $('#flt-alm');
        $alm.empty().append('<option value="">Todos</option>');
        almacenesBase.forEach(function (a) {
            if (sclId !== '' && String(a.alm_scl_id) !== sclId) return;
            const selected = current !== '' && current === String(a.alm_id) ? ' selected' : '';
            $alm.append('<option value="' + esc(a.alm_id) + '"' + selected + '>' + esc(a.alm_nombre) + '</option>');
        });
    }

    function cargar() {
        AppUI.showLoader();
        $.getJSON(rutaData, {
            cse_id: $('#flt-cse').val(),
            min_scl_id: $('#flt-scl').val(),
            min_alm_id: $('#flt-alm').val(),
            fecha_desde: $('#flt-desde').val(),
            fecha_hasta: $('#flt-hasta').val(),
            buscar: $('#flt-buscar').val()
        }).done(function (resp) {
            const data = resp.data || [];
            if ($.fn.DataTable.isDataTable('#tbl-negativos-sesion')) {
                $('#tbl-negativos-sesion').DataTable().clear().destroy();
            }

            $('#tbl-negativos-sesion').DataTable({
                data,
                order: [[0, 'desc']],
                columns: [
                    { data: 'min_fecha_movimiento', render: (v) => v ? String(v).replace('T', ' ').slice(0, 16) : '-' },
                    { data: 'cse_id', render: (v) => '<span class="badge bg-label-primary">#' + esc(v) + '</span>' },
                    { data: 'caj_nombre', defaultContent: '-' },
                    { data: 'scl_nombre', defaultContent: '-' },
                    { data: 'alm_nombre', defaultContent: '-' },
                    { data: 'psv_folio', render: (v) => '<span class="badge bg-label-info">' + esc(v || '-') + '</span>' },
                    { data: 'psk_codigo', render: (v) => '<span class="badge bg-label-secondary">' + esc(v || '-') + '</span>' },
                    { data: 'psk_nombre', defaultContent: '-' },
                    { data: 'min_cantidad', className: 'text-end', render: (v) => '-' + Number(v || 0).toFixed(2) },
                    { data: 'min_existencia_antes', className: 'text-end', render: (v) => Number(v || 0).toFixed(2) },
                    { data: 'min_existencia_despues', className: 'text-end text-danger fw-bold', render: (v) => Number(v || 0).toFixed(2) },
                    { data: 'usuario_venta', defaultContent: '<span class="text-body-secondary">—</span>' }
                ]
            });
        }).fail(function (xhr) {
            const msg = xhr.responseJSON?.message || 'No fue posible cargar el reporte.';
            AppUI.showMessage('Error', msg, 'error');
        }).always(function () {
            AppUI.hideLoader();
        });
    }

    $('#flt-scl').on('change', syncAlmacenes);
    $('#btn-filtrar').on('click', cargar);
    $('#btn-limpiar').on('click', function () {
        $('#flt-cse').val('');
        $('#flt-scl').val('');
        syncAlmacenes();
        $('#flt-desde').val('');
        $('#flt-hasta').val('');
        $('#flt-buscar').val('');
        cargar();
    });

    syncAlmacenes();
    cargar();
})();
</script>
@endpush
