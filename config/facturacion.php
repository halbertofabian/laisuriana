<?php

/*
| Datos para la representación de un CFDI 4.0 SIMULADO. No hay integración con el SAT ni con un PAC:
| UUID, sellos y certificados se generan localmente y el documento siempre se marca sin valor fiscal.
| Los datos fiscales del emisor no se inventan: mientras no se configuren, el PDF muestra "Sin configurar".
*/
return [
    'emisor' => [
        'nombre' => env('FACTURACION_EMISOR_NOMBRE'),
        'rfc' => env('FACTURACION_EMISOR_RFC'),
        'regimen' => env('FACTURACION_EMISOR_REGIMEN'),
        'cp' => env('FACTURACION_EMISOR_CP'),
    ],

    // Los precios de venta incluyen IVA; el desglose del PDF lo separa a esta tasa.
    'iva' => (float) env('FACTURACION_IVA_TASA', 0.16),

    'serie' => env('FACTURACION_SERIE', 'SIM'),
];
