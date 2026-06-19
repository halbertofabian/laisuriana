<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recepción {{ $reporte['folio'] ?? '' }}</title>
    <style>
        /* ── Papel térmico 80mm ─────────────────────────────────────── */
        @page {
            size: 80mm auto;
            margin: 3mm 4mm;
        }

        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            margin: 0; padding: 0;
            background: #fff; color: #111;
            font-family: 'Courier New', Courier, monospace;
            font-size: 10px;
            line-height: 1.4;
            width: 72mm;
        }

        /* ── Texto ──────────────────────────────────────────────────── */
        .t-center { text-align: center; }
        .t-right  { text-align: right; }
        .bold     { font-weight: 700; }

        /* ── Separadores ────────────────────────────────────────────── */
        .sep {
            border: 0;
            border-top: 1px solid #111;
            margin: 3px 0;
        }
        .sep-dashed {
            border: 0;
            border-top: 1px dashed #888;
            margin: 2px 0;
        }

        /* ── Línea de dos columnas ──────────────────────────────────── */
        .row {
            display: flex;
            justify-content: space-between;
            gap: 4px;
        }
        .row .l { flex: 1 1 auto; overflow: hidden; white-space: nowrap; text-overflow: clip; }
        .row .r { flex: 0 0 auto; white-space: nowrap; }

        /* ── Encabezado ─────────────────────────────────────────────── */
        .tkt-title {
            font-size: 13px;
            font-weight: 700;
            text-align: center;
            letter-spacing: .03em;
            margin: 0 0 1px;
        }
        .tkt-sub {
            font-size: 8.5px;
            text-align: center;
            color: #555;
            margin: 0 0 3px;
        }

        /* ── Metadatos ──────────────────────────────────────────────── */
        .meta-row {
            display: flex;
            gap: 3px;
            margin-bottom: 1px;
            font-size: 9px;
        }
        .meta-row .lbl {
            flex: 0 0 auto;
            color: #555;
            white-space: nowrap;
        }
        .meta-row .val {
            flex: 1 1 auto;
            font-weight: 600;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .meta-folio {
            font-size: 10px;
            font-weight: 700;
        }

        /* ── Grupo de producto ──────────────────────────────────────── */
        .grp {
            margin-top: 3px;
        }
        .grp-head {
            background: #111;
            color: #fff;
            padding: 2px 4px;
            font-size: 9.5px;
            font-weight: 700;
        }
        .grp-head .grp-code {
            font-size: 7.5px;
            font-weight: 400;
            opacity: .75;
            margin-bottom: 1px;
        }
        .grp-head-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 4px;
        }
        .grp-head-row .nombre { flex: 1 1 auto; overflow: hidden; }
        .grp-head-row .total  { flex: 0 0 auto; font-size: 8.5px; white-space: nowrap; }

        /* ── Fila de color ──────────────────────────────────────────── */
        .color-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 4px;
            padding: 1px 4px 0;
            font-size: 9px;
        }
        .color-row .color-name {
            font-weight: 600;
            text-transform: uppercase;
            overflow: hidden;
            flex: 1 1 auto;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .color-row .color-total {
            font-weight: 700;
            white-space: nowrap;
            flex: 0 0 auto;
        }

        /* ── Chips de talla ─────────────────────────────────────────── */
        .tallas {
            padding: 1px 4px 3px 12px;
            font-size: 8.5px;
            color: #333;
            display: flex;
            flex-wrap: wrap;
            gap: 2px 8px;
        }
        .talla-chip {
            white-space: nowrap;
        }
        .talla-chip .talla-lbl {
            color: #666;
        }
        .talla-chip .talla-qty {
            font-weight: 700;
        }

        /* ── Totales finales ────────────────────────────────────────── */
        .totals {
            margin-top: 4px;
        }
        .totals-title {
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            margin-bottom: 2px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            padding: 1px 0;
        }
        .total-row.grand {
            font-size: 11px;
            font-weight: 700;
            padding-top: 3px;
            margin-top: 1px;
        }

        /* ── Pie ────────────────────────────────────────────────────── */
        .tkt-foot {
            margin-top: 5px;
            text-align: center;
            font-size: 7.5px;
            color: #888;
        }

        /* ── Observaciones ──────────────────────────────────────────── */
        .obs {
            font-size: 8.5px;
            border-left: 2px solid #aaa;
            padding: 1px 4px;
            margin: 3px 0;
            color: #444;
        }
        .obs .obs-lbl {
            font-size: 7px;
            text-transform: uppercase;
            font-weight: 700;
            color: #777;
            display: block;
        }

        @media print {
            html, body { width: 72mm; }
            body { margin: 0; padding: 0; }
        }
    </style>
</head>
<body>

{{-- ══ ENCABEZADO ══════════════════════════════════════════════════ --}}
<p class="tkt-title">ENTRADA DE MERCANCÍA</p>
<p class="tkt-sub">Comprobante de recepción</p>
<hr class="sep">

{{-- Folio + fecha en misma línea --}}
<div class="row meta-folio" style="margin-bottom:2px;">
    <span class="l bold">{{ $reporte['folio'] }}</span>
    <span class="r" style="font-size:8.5px;font-weight:400;">{{ $reporte['fecha'] }}</span>
</div>

<div class="meta-row">
    <span class="lbl">Sucursal:</span>
    <span class="val">{{ $reporte['sucursal'] }}</span>
</div>
<div class="meta-row">
    <span class="lbl">Almacén :</span>
    <span class="val">{{ $reporte['almacen'] }}</span>
</div>
<div class="meta-row">
    <span class="lbl">Proveedor:</span>
    <span class="val">{{ $reporte['proveedor'] }}</span>
</div>

@if($reporte['referencia'] !== '')
<div class="meta-row">
    <span class="lbl">Ref     :</span>
    <span class="val">{{ $reporte['referencia'] }}</span>
</div>
@endif

@if($reporte['observaciones'] !== '')
<div class="obs">
    <span class="obs-lbl">Observaciones</span>
    {{ $reporte['observaciones'] }}
</div>
@endif

<hr class="sep">

{{-- ══ GRUPOS DE PRODUCTO ═══════════════════════════════════════════ --}}
@foreach($reporte['grupos'] as $grupo)
<div class="grp">
    {{-- Encabezado del producto --}}
    <div class="grp-head">
        @if($grupo['codigo'] !== '')
            <div class="grp-code">{{ $grupo['codigo'] }}</div>
        @endif
        <div class="grp-head-row">
            <span class="nombre">{{ $grupo['nombre'] }}</span>
            <span class="total">{{ number_format($grupo['total'], 0, '.', ',') }} pz</span>
        </div>
    </div>

    {{-- Colores --}}
    @foreach($grupo['colores'] as $colorData)
        <div class="color-row">
            <span class="color-name">{{ $colorData['nombre'] }}</span>
            <span class="color-total">{{ number_format($colorData['total'], 0, '.', ',') }}</span>
        </div>
        <div class="tallas">
            @foreach($colorData['tallas'] as $talla => $qty)
                @if($qty > 0)
                    <span class="talla-chip">
                        <span class="talla-lbl">{{ $talla }}:</span><span class="talla-qty">{{ number_format($qty, 0) }}</span>
                    </span>
                @endif
            @endforeach
        </div>
    @endforeach

    <hr class="sep-dashed" style="margin-top:2px;">
</div>
@endforeach

{{-- ══ TOTALES FINALES ═════════════════════════════════════════════ --}}
<div class="totals">
    <div class="totals-title">Totales</div>

    <div class="total-row">
        <span>Artículos</span>
        <span class="bold">{{ number_format($reporte['totales']['articulos'], 0, '.', ',') }} pzas</span>
    </div>

    @if($reporte['totales']['subtotal'] > 0)
    <div class="total-row">
        <span>Subtotal</span>
        <span>${{ number_format($reporte['totales']['subtotal'], 2, '.', ',') }}</span>
    </div>
    @endif

    @if($reporte['totales']['descuento'] > 0)
    <div class="total-row">
        <span>Descuento</span>
        <span>-&nbsp;${{ number_format($reporte['totales']['descuento'], 2, '.', ',') }}</span>
    </div>
    @endif

    @if($reporte['totales']['flete'] > 0)
    <div class="total-row">
        <span>Flete</span>
        <span>${{ number_format($reporte['totales']['flete'], 2, '.', ',') }}</span>
    </div>
    @endif

    @if($reporte['totales']['iva'] > 0)
    <div class="total-row">
        <span>IVA</span>
        <span>${{ number_format($reporte['totales']['iva'], 2, '.', ',') }}</span>
    </div>
    @endif

    @if($reporte['totales']['total'] > 0)
    <hr class="sep">
    <div class="total-row grand">
        <span>TOTAL</span>
        <span>${{ number_format($reporte['totales']['total'], 2, '.', ',') }}</span>
    </div>
    @endif
</div>

<hr class="sep-dashed" style="margin-top:6px;">
<div class="tkt-foot">
    {{ $reporte['folio'] }} &bull; {{ $reporte['fecha'] }}<br>
    Documento de uso interno
</div>

@if($autoPrint ?? false)
    <script>
        window.addEventListener('load', function () { window.print(); });
    </script>
@endif
</body>
</html>
