<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Ticket {{ $venta->psv_folio }}</title>
    <style>
        body{font-family:Arial,sans-serif;font-size:12px;color:#111;margin:16px}
        .t{max-width:340px;margin:0 auto}
        .c{text-align:center}.r{text-align:right}.m{margin:8px 0}.b{font-weight:700}
        table{width:100%;border-collapse:collapse}
        th,td{padding:4px 0;border-bottom:1px dashed #ccc}
        .tot td{border:none}
    </style>
</head>
<body onload="window.print()">
<div class="t">
    <div class="c b">LA SURIANA</div>
    <div class="c m">Ticket {{ $venta->psv_folio }}</div>
    <div>Fecha: {{ optional($venta->psv_fecha_cobro)->format('d/m/Y H:i') }}</div>
    <div>Método: {{ strtoupper((string) $venta->psv_metodo_pago) }}</div>
    <div class="m"></div>
    <table>
        <thead><tr><th>Producto</th><th class="r">Cant</th><th class="r">Imp</th></tr></thead>
        <tbody>
        @foreach($venta->detalle as $d)
            <tr>
                <td>{{ $d->sku?->psk_nombre ?: ('SKU '.$d->pvd_psk_id) }}</td>
                <td class="r">{{ number_format((float)$d->pvd_cantidad,2) }}</td>
                <td class="r">${{ number_format((float)$d->pvd_importe,2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <table class="tot m">
        <tr><td>Subtotal</td><td class="r">${{ number_format((float)$venta->psv_subtotal,2) }}</td></tr>
        <tr><td>Descuento</td><td class="r">${{ number_format((float)$venta->psv_descuento,2) }}</td></tr>
        <tr><td class="b">Total</td><td class="r b">${{ number_format((float)$venta->psv_total,2) }}</td></tr>
        <tr><td>Pagado</td><td class="r">${{ number_format((float)$venta->psv_pagado,2) }}</td></tr>
        <tr><td>Cambio</td><td class="r">${{ number_format((float)$venta->psv_cambio,2) }}</td></tr>
    </table>
</div>
</body>
</html>
