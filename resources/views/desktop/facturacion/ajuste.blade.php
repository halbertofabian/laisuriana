@extends('layouts.desktop')
@section('title', 'Ticket para facturación')
@include('desktop.facturacion._estilos')
@php
    $factura = $venta->facturacion;
    $emitida = $factura?->fac_estado === 'simulada';
    $editable = $mixta && !$emitida && !$bloqueo && auth()->user()->tienePermiso('facturacion.ajustar');
    $puedeEmitir = auth()->user()->tienePermiso('facturacion.emitir') && !$emitida;
    $sinAlmacen = $mixta && !$emitida && $almacenes->isEmpty();
    $almacenFacturacion = $factura?->fac_alm_id;
    // Con un único almacén habilitado se preselecciona; elegir entre los ya habilitados no habilita ninguno nuevo.
    if ($editable && !$almacenFacturacion && $almacenes->count() === 1) {
        $almacenFacturacion = $almacenes->first()->alm_id;
    }
    $estado = $emitida ? ['Factura simulada', 'success'] : ($factura ? ['Ajuste guardado', 'brand'] : ($mixta ? ['Sin ajuste', 'warning'] : ['Lista para facturar', 'brand']));
@endphp
@push('desktop-styles')
<style>
    .fac-toolbar-hint { max-width:260px; font-size:.74rem; line-height:1.3; color:var(--text-2); }
    .fac-toolbar-hint:empty { display:none; }

    .fac-resumen { position:sticky; top:-10px; z-index:var(--z-sticky); display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:1px; margin:-10px 0 10px; padding-top:10px; background:var(--bg); }
    .fac-resumen__item { padding:8px 14px; background:var(--surface); border:1px solid var(--stroke); }
    .fac-resumen__item:first-child { border-radius:var(--r-md) 0 0 var(--r-md); }
    .fac-resumen__item:last-child { border-radius:0 var(--r-md) var(--r-md) 0; }
    .fac-resumen__label { display:block; font-size:.74rem; color:var(--text-2); }
    .fac-resumen__value { display:block; font-size:1.2rem; font-weight:700; font-variant-numeric:tabular-nums; line-height:1.3; }
    .fac-resumen__item.is-warning { background:var(--warning-soft); border-color:var(--warning-stroke); }
    .fac-resumen__item.is-warning .fac-resumen__value, .fac-resumen__item.is-warning .fac-resumen__label { color:var(--warning); }
    .fac-resumen__item.is-success .fac-resumen__value { color:var(--success); }

    .fac-comparacion { display:grid; grid-template-columns:minmax(0, 5fr) minmax(0, 7fr); gap:10px; align-items:start; }
    .fac-comparacion .desktop-pane { height:auto; --list-min-w:0px; }
    .fac-comparacion .desktop-list-wrap { flex:none; }
    .fac-comparacion table.desktop-list { font-size:.86rem; }
    .fac-comparacion table.desktop-list th, .fac-comparacion table.desktop-list td { padding:8px 10px !important; }
    .fac-pane-head { display:flex; align-items:flex-start; justify-content:space-between; gap:10px; padding:10px 12px; border-bottom:1px solid var(--stroke); }
    .fac-original { position:sticky; top:72px; }
    .fac-original .desktop-list-wrap { max-height:calc(100vh - 330px); min-height:120px; overflow:auto; }
    .fac-original .is-otro td { color:var(--text-2); }
    .fac-total { font-size:1.02rem; color:var(--text); font-variant-numeric:tabular-nums; }
    .fac-cuerpo { padding:10px 12px; }
    .fac-cuerpo + .fac-cuerpo { padding-top:0; }
    .fac-campos { display:grid; grid-template-columns:minmax(180px, 2fr) minmax(0, 5fr); gap:10px 12px; padding:10px 12px; border-bottom:1px solid var(--divider); }
    .fac-campos .desktop-field input, .fac-campos .desktop-field select { min-height:38px; font-size:.9rem; }

    .fac-buscador { position:relative; }
    .fac-resultados { position:absolute; left:0; right:0; top:calc(100% + 4px); z-index:var(--z-dropdown); max-height:320px; overflow:auto; background:var(--surface); border:1px solid var(--stroke-strong); border-radius:var(--r-md); box-shadow:var(--shadow-16); }
    .fac-resultados[hidden] { display:none; }
    .fac-resultado { display:grid; grid-template-columns:minmax(0, 1fr) auto auto; gap:12px; align-items:center; padding:8px 12px; border-bottom:1px solid var(--divider); cursor:pointer; font-size:.86rem; }
    .fac-resultado:last-child { border-bottom:0; }
    .fac-resultado.is-active, .fac-resultado:hover { background:var(--brand-soft); }
    .fac-resultado__precio { font-weight:700; font-variant-numeric:tabular-nums; }
    .fac-resultado__exist { min-width:86px; text-align:right; font-size:.76rem; color:var(--text-2); font-variant-numeric:tabular-nums; }
    .fac-resultado__exist.is-low { color:var(--warning); font-weight:600; }
    .fac-resultados__vacio { padding:14px 12px; font-size:.82rem; color:var(--text-2); }

    .fac-partidas input[type="number"] { width:96px; min-height:36px; padding:0 8px; font-size:.9rem; text-align:right; font-variant-numeric:tabular-nums; }
    .fac-partidas .desktop-field { gap:2px; }
    .fac-partidas input[aria-invalid="true"] { border-color:var(--danger); box-shadow:0 0 0 1px var(--danger); }
    .fac-partidas tr.is-nueva td { animation:fac-nueva 1.4s ease; }
    .fac-partidas tr.is-error td { background:var(--danger-soft); }
    @keyframes fac-nueva { from { background:var(--success-soft); } to { background:transparent; } }
    .fac-quitar { width:32px; padding:0 !important; color:var(--text-2); }
    .fac-quitar:hover { color:var(--danger); background:var(--danger-soft) !important; }
    .fac-atajos { font-size:.72rem; color:var(--text-3); }
    .fac-atajos kbd { padding:0 4px; border:1px solid var(--stroke-strong); border-radius:3px; background:var(--surface-alt); font:inherit; font-size:.7rem; color:var(--text-2); }
    .fac-error-general { color:var(--danger); font-size:.82rem; font-weight:600; }
    .fac-error-general:empty { display:none; }
    .fac-deshacer[hidden] { display:none; }
    .fac-nota-inventario { margin-top:10px; font-size:.76rem; color:var(--text-2); }

    @media (max-width:1100px) {
        .fac-comparacion { grid-template-columns:1fr; }
        .fac-original { position:static; }
        .fac-original .desktop-list-wrap { max-height:220px; }
    }
    @media (max-width:640px) {
        .fac-atajos { display:none; }
        .fac-resumen__item { padding:6px 10px; }
        .fac-resumen__value { font-size:1rem; }
        .fac-campos { grid-template-columns:1fr; }
        .fac-resultado { grid-template-columns:minmax(0, 1fr) auto; }
        .fac-resultado__exist { grid-column:1 / -1; text-align:left; }
        .fac-comparacion .desktop-list-wrap { overflow-x:auto; }
        .fac-partidas table.desktop-list { min-width:520px; }
    }
</style>
@endpush
@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        <a class="desktop-btn desktop-btn--default" href="{{ $volver }}" id="ajuste-volver">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>Volver
        </a>
        <div class="page-head">
            <div class="page-head__title">{{ $emitida ? 'Factura simulada · '.$factura->fac_folio : ($mixta ? 'Ajustar ticket' : 'Facturar venta') }}</div>
            <div class="page-head__sub">{{ $venta->psv_folio }} · {{ $cliente }} · {{ $venta->psv_fecha_cobro?->format('d/m/Y H:i') }}</div>
        </div>
    </div>
    <div class="desktop-toolbar__group">
        @if($emitida)
            <a class="desktop-btn desktop-btn--default" href="{{ route('desktop.facturacion.pdf', $venta->psv_id) }}" target="_blank" rel="noopener" aria-label="Ver PDF (se abre en otra pestaña)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h6"/></svg>Ver PDF
            </a>
            <a class="desktop-btn desktop-btn--primary" href="{{ route('desktop.facturacion.pdf', [$venta->psv_id, 'descargar' => 1]) }}" download>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg>Descargar PDF
            </a>
        @endif
        <span id="ajuste-estado" class="desktop-pill fac-pill--lg {{ $estado[1] === 'brand' ? 'desktop-pill--brand' : 'fac-pill--'.$estado[1] }}" role="status" aria-live="polite">{{ $estado[0] }}</span>
        @if($puedeEmitir)
            <span class="fac-toolbar-hint" id="ajuste-facturar-motivo"></span>
        @endif
        @if($editable)
            <button type="button" class="desktop-btn desktop-btn--default" id="ajuste-guardar" title="Guardar ajuste (Ctrl+S)">Guardar ajuste</button>
        @endif
        @if($puedeEmitir)
            <button type="button" class="desktop-btn desktop-btn--primary" id="ajuste-facturar" @disabled($bloqueo || ($mixta && !$factura))>Facturar</button>
        @endif
    </div>
@endsection
@section('content')
    @if($emitida)
        <div class="fac-alert fac-alert--success fac-alert--box" role="status">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>
            <div class="fac-alert__body"><strong>Factura simulada {{ $factura->fac_folio }}</strong> emitida el {{ $factura->fac_emitida_at?->format('d/m/Y H:i') }}. Sin valor fiscal; el ticket ya no se puede editar.
                @if($uuid = data_get($factura->fac_documento, 'cfdi.timbre.uuid'))<br>Folio fiscal (UUID) simulado: <strong>{{ $uuid }}</strong>@endif</div>
        </div>
    @endif
    @if($bloqueo)
        <div class="fac-alert fac-alert--danger fac-alert--box" role="alert">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
            <div class="fac-alert__body"><strong>{{ $emitida ? 'Aviso:' : 'No se puede facturar:' }}</strong> {{ $bloqueo }} La consulta del ticket permanece disponible.</div>
        </div>
    @endif
    @if($sinAlmacen && !$bloqueo)
        <div class="fac-alert fac-alert--warning fac-alert--box" role="alert">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2 21h20L12 3Z"/><path d="M12 9v5M12 18h.01"/></svg>
            <div class="fac-alert__body">
                <strong>Falta habilitar un almacén para facturar en esta sucursal.</strong>
                En Administración → Almacenes, edita el almacén que se usará y elige “Sí” en “Habilitado para ajustar tickets de facturación”. Después vuelve a esta pantalla.
            </div>
            @if($puedeConfigurarAlmacenes)
                <div class="fac-alert__actions"><a class="desktop-btn desktop-btn--default" href="{{ route('desktop.operacion.gestion_configuraciones.almacenes.index') }}">Ir a Almacenes</a></div>
            @endif
        </div>
    @endif
    <div class="fac-alert fac-alert--warning fac-alert--box" id="ajuste-borrador" role="status" hidden>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4l3 2"/><circle cx="12" cy="12" r="9"/></svg>
        <div class="fac-alert__body" data-texto></div>
        <div class="fac-alert__actions">
            <button type="button" class="desktop-btn desktop-btn--primary" data-recuperar>Recuperar cambios</button>
            <button type="button" class="desktop-btn desktop-btn--default" data-descartar>Descartar</button>
        </div>
    </div>
    <div class="fac-alert fac-alert--danger fac-alert--box" id="ajuste-conflicto" role="alert" hidden>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
        <div class="fac-alert__body"><strong>Otra sesión modificó este ticket.</strong> Recarga para ver la versión actual. Tus cambios quedan guardados en este equipo para recuperarlos después de recargar.</div>
        <div class="fac-alert__actions"><button type="button" class="desktop-btn desktop-btn--primary" data-recargar>Recargar</button></div>
    </div>

    <div class="fac-resumen" aria-label="Comparación de totales">
        <div class="fac-resumen__item">
            <span class="fac-resumen__label">Ticket original {{ $venta->psv_folio }}</span>
            <span class="fac-resumen__value">${{ number_format($venta->psv_total, 2) }}</span>
        </div>
        <div class="fac-resumen__item">
            <span class="fac-resumen__label">{{ $mixta ? 'Total ajustado' : 'Total a facturar' }}</span>
            <span class="fac-resumen__value" id="ajuste-total-resumen"></span>
        </div>
        <div class="fac-resumen__item" id="ajuste-diferencia-caja">
            <span class="fac-resumen__label" id="ajuste-diferencia-texto">Diferencia</span>
            <span class="fac-resumen__value" id="ajuste-diferencia" aria-live="polite"></span>
        </div>
    </div>

    <form id="ajuste-form" novalidate>
        <div class="fac-comparacion">
            <section class="desktop-pane fac-original" aria-label="Ticket original">
                <div class="fac-pane-head">
                    <div class="page-head"><div class="page-head__title">Ticket original</div><div class="page-head__sub">{{ $venta->psv_folio }} · {{ count($original) }} {{ count($original) === 1 ? 'partida' : 'partidas' }}</div></div>
                    <div class="desktop-toolbar__group">
                        @if($editable)
                            <button type="button" class="desktop-btn desktop-btn--default" id="ajuste-copiar" title="Agrega al ajustado las partidas del original que existen en el almacén habilitado">Copiar al ajustado</button>
                        @endif
                        <span class="desktop-pill desktop-pill--neutral">Solo lectura</span>
                    </div>
                </div>
                <div class="desktop-list-wrap">
                    <table class="desktop-list">
                        <thead><tr><th>Producto</th><th class="fac-num">Cant.</th><th class="fac-num">Precio</th><th class="fac-num">Importe</th></tr></thead>
                        <tbody>
                        @foreach($original as $linea)
                            <tr data-almacen="{{ $linea['almacen_id'] }}">
                                <td>
                                    <span class="desktop-list__name">{{ $linea['nombre'] }}</span>
                                    <span class="desktop-list__meta">{{ $linea['codigo'] }}</span>
                                    @if($mixta)<span class="desktop-pill desktop-pill--neutral" data-almacen-pill="{{ $linea['almacen_id'] }}">{{ $linea['almacen'] }}</span>@endif
                                    @if(($linea['descuento'] ?? 0) > 0)<span class="fac-note">Descuento: ${{ number_format($linea['descuento'], 2) }}</span>@endif
                                </td>
                                <td class="fac-num">{{ (float) $linea['cantidad'] }}</td>
                                <td class="fac-num">${{ number_format($linea['precio'], 2) }}</td>
                                <td class="fac-num">${{ number_format($linea['importe'], 2) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="desktop-list-foot"><span>Total original</span><strong class="fac-total">${{ number_format($venta->psv_total, 2) }}</strong></div>
                @if((float)$venta->psv_descuento > 0 || (float)$venta->psv_credito_cambio > 0)
                    <div class="fac-cuerpo fac-note">Descuento registrado: ${{ number_format($venta->psv_descuento, 2) }} · Crédito aplicado: ${{ number_format($venta->psv_credito_cambio, 2) }}</div>
                @endif
            </section>

            <section class="desktop-pane fac-partidas" aria-label="{{ $mixta ? 'Ticket ajustado' : 'Ticket a facturar' }}">
                <div class="fac-pane-head">
                    <div class="page-head">
                        <div class="page-head__title">{{ $mixta ? 'Ticket ajustado' : 'Ticket a facturar' }}</div>
                        <div class="page-head__sub">{{ $emitida ? 'Partidas facturadas en simulación.' : ($mixta ? 'Solo productos del almacén habilitado para facturar.' : 'Venta de un solo almacén: se factura igual que el original.') }}</div>
                    </div>
                    @if($editable)
                        <span class="fac-atajos"><kbd>F2</kbd> buscar · <kbd>Enter</kbd> agregar · <kbd>Ctrl</kbd>+<kbd>S</kbd> guardar</span>
                    @endif
                </div>
                @if($mixta)
                    <div class="fac-campos">
                        <div class="desktop-field">
                            <label for="ajuste-almacen">Almacén para facturar</label>
                            <select id="ajuste-almacen" @disabled(!$editable || $almacenes->isEmpty()) aria-describedby="ajuste-almacen-error">
                                <option value="">{{ $almacenes->isEmpty() ? 'Ninguno habilitado' : 'Selecciona un almacén' }}</option>
                                @foreach($almacenes as $almacen)<option value="{{ $almacen->alm_id }}" @selected((int)$almacenFacturacion === (int)$almacen->alm_id)>{{ $almacen->alm_nombre }}</option>@endforeach
                                @if($factura && !$almacenes->contains('alm_id', $factura->fac_alm_id))<option value="{{ $factura->fac_alm_id }}" selected>Almacén #{{ $factura->fac_alm_id }} (ya no habilitado)</option>@endif
                            </select>
                            <small class="fac-note--danger" id="ajuste-almacen-error" role="alert"></small>
                        </div>
                        @if($editable)
                            <div class="desktop-field fac-buscador">
                                <label for="ajuste-buscar">Agregar producto</label>
                                <input type="search" id="ajuste-buscar" placeholder="Nombre, SKU o código de barras" autocomplete="off" disabled
                                       role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="ajuste-resultados" data-ls-autocomplete="allow">
                                <div class="fac-resultados" id="ajuste-resultados" role="listbox" aria-label="Productos del almacén" hidden></div>
                                <small id="ajuste-productos-info" role="status">Selecciona el almacén para buscar productos.</small>
                            </div>
                        @endif
                    </div>
                @endif
                <div class="desktop-list-wrap">
                    <table class="desktop-list">
                        <thead><tr><th>Producto</th><th class="fac-num">Cantidad</th><th class="fac-num">Precio</th><th class="fac-num">Importe</th>@if($editable)<th aria-label="Quitar"></th>@endif</tr></thead>
                        <tbody id="ajuste-partidas"></tbody>
                    </table>
                </div>
                <div class="desktop-list-foot">
                    <span>Total {{ $mixta ? 'ajustado' : 'a facturar' }} <span id="ajuste-num-partidas"></span></span>
                    <strong class="fac-total" id="ajuste-total"></strong>
                </div>
                <div class="fac-cuerpo">
                    <div class="fac-error-general" id="ajuste-error" role="alert"></div>
                    <div class="fac-note fac-deshacer" id="ajuste-deshacer" hidden><span data-texto></span> <button type="button" class="desktop-btn desktop-btn--ghost" data-deshacer>Deshacer</button></div>
                </div>
                @if($mixta)
                    <div class="fac-cuerpo desktop-field">
                        <label for="ajuste-notas">Observaciones <span class="desktop-list__meta" style="display:inline;">(opcional)</span></label>
                        <textarea id="ajuste-notas" rows="2" maxlength="2000" @disabled(!$editable) aria-describedby="ajuste-notas-error">{{ $factura?->fac_notas }}</textarea>
                        <small class="fac-note--danger" id="ajuste-notas-error" role="alert"></small>
                    </div>
                @endif
            </section>
        </div>
        <p class="fac-nota-inventario">
            {{ $mixta ? 'Se puede guardar aunque el total ajustado sea diferente.' : '' }}
            La afectación de inventario está pendiente de definición: no se realizan movimientos, reservas ni cambios de existencias, y la venta original no se modifica. La factura es una simulación sin valor fiscal.
        </p>
    </form>
@endsection
@push('desktop-scripts')
    <script src="{{ asset('js/facturacion.js') }}?v={{ filemtime(public_path('js/facturacion.js')) }}"></script>
    <script>
        Facturacion.ajuste({
            base: @json(url('/desktop/facturacion/'.$venta->psv_id)),
            venta: @json($venta->psv_id), folio: @json($venta->psv_folio),
            original: @json((float)$venta->psv_total),
            lineasOriginales: @json(collect($original)->map(fn ($l) => ['psk_id' => $l['psk_id'], 'cantidad' => $l['cantidad'], 'precio' => $l['precio']])->values()),
            partidas: @json($partidas),
            notas: @json($factura?->fac_notas ?? ''),
            almacen: @json($almacenFacturacion ? (string) $almacenFacturacion : ''),
            almacenGuardado: @json($factura?->fac_alm_id ? (string) $factura->fac_alm_id : ''),
            editable: @json($editable), mixta: @json($mixta), emitida: @json($emitida), bloqueo: @json((bool)$bloqueo),
            guardado: @json((bool)$factura), version: @json($factura?->fac_version ?? 0)
        });
    </script>
@endpush
