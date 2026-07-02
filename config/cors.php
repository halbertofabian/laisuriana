<?php

return [
    'paths' => [
        'mobile/*',
        'pos/*',
        'operacion/pedidos-piso/*',
        'operacion/pedidos-piso',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:8081',
        'http://127.0.0.1:8081',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
