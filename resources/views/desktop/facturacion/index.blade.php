@extends('layouts.desktop')
@section('title', 'Facturación')
@include('desktop.facturacion._estilos')
@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        <div class="page-head">
            <div class="page-head__title">Facturación</div>
            <div class="page-head__sub">Prepara los tickets y genera la factura simulada.</div>
        </div>
    </div>
    <form class="desktop-toolbar__group" id="facturas-filtros" role="search">
        <input type="search" class="desktop-toolbar__search" name="buscar" placeholder="Folio o cliente" aria-label="Buscar folio o cliente" autofocus>
        <input type="hidden" name="estado">
        <input type="date" class="desktop-toolbar__search" name="desde" aria-label="Desde" title="Desde" style="width:142px;">
        <input type="date" class="desktop-toolbar__search" name="hasta" aria-label="Hasta" title="Hasta" style="width:142px;">
        <button class="desktop-btn desktop-btn--primary">Filtrar</button>
    </form>
@endsection
@section('content')
    <section class="desktop-pane">
        @if($sinAlmacenHabilitado)
            <div class="fac-alert fac-alert--warning" role="status">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2 21h20L12 3Z"/><path d="M12 9v5M12 18h.01"/></svg>
                <div>
                    <strong>Aún no hay un almacén habilitado para facturar.</strong>
                    Las ventas de un solo almacén se pueden facturar; las de dos almacenes necesitan uno habilitado para ajustar el ticket.
                    Configúralo en Administración → Almacenes: edita el almacén y elige “Sí” en “Habilitado para ajustar tickets de facturación”.
                    @if($puedeConfigurarAlmacenes)
                        <a href="{{ route('desktop.operacion.gestion_configuraciones.almacenes.index') }}">Ir a Almacenes</a>
                    @endif
                </div>
            </div>
        @endif
        <div class="fac-bar">
            <div class="desktop-pivot" id="facturas-situaciones" role="tablist" aria-label="Situación de la venta">
                <button type="button" class="desktop-btn desktop-btn--active" role="tab" aria-selected="true" data-situacion="">Todas</button>
                <button type="button" class="desktop-btn" role="tab" aria-selected="false" data-situacion="requiere_ajuste">Requieren ajuste <span class="fac-count" data-count="requiere_ajuste"></span></button>
                <button type="button" class="desktop-btn" role="tab" aria-selected="false" data-situacion="lista">Listas para facturar <span class="fac-count" data-count="lista"></span></button>
                <button type="button" class="desktop-btn" role="tab" aria-selected="false" data-situacion="simulada">Factura simulada <span class="fac-count" data-count="simulada"></span></button>
                <button type="button" class="desktop-btn" role="tab" aria-selected="false" data-situacion="revision">No facturables <span class="fac-count" data-count="revision"></span></button>
            </div>
            <span class="fac-bar__hint">Emisión simulada, sin valor fiscal. No mueve inventario.</span>
        </div>
        <div class="desktop-list-wrap">
            <table class="desktop-list fac-list" id="facturas-tabla">
                <thead><tr><th>Venta</th><th>Cliente</th><th>Almacenes</th><th class="fac-num">Total original</th><th class="fac-num">Total a facturar</th><th>Situación</th><th>Acciones</th></tr></thead>
                <tbody><tr><td colspan="7" class="desktop-list__empty">Cargando ventas...</td></tr></tbody>
            </table>
        </div>
        <div class="desktop-list-foot"><span id="facturas-info" role="status"></span><div id="facturas-paginacion" class="desktop-pager"></div></div>
    </section>
@endsection
@push('desktop-scripts')
    <script src="{{ asset('js/facturacion.js') }}?v={{ filemtime(public_path('js/facturacion.js')) }}"></script>
    <script>
        Facturacion.listado({
            base: @json(url('/desktop/facturacion')),
            emitir: @json(auth()->user()->tienePermiso('facturacion.emitir')),
            ajustar: @json(auth()->user()->tienePermiso('facturacion.ajustar'))
        });
    </script>
@endpush
