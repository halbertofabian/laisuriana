# Flutter venta de piso

## Recomendación

Sí conviene cambiar a Flutter para esta etapa.

Motivos puntuales para este proyecto:

- Tu meta inmediata es UX móvil, no backend.
- Ya detectaste inestabilidad en tooling basado en Node para levantar y compilar.
- Flutter te deja una app separada del Laravel con menos piezas moviéndose en desarrollo diario.
- El flujo de venta de piso requiere UI muy controlada, rápida y consistente en móviles.

## Estructura sugerida

La nueva app quedó en `app_flutter/` y está separada del proyecto Laravel.

Estructura inicial:

- `lib/app/`
- `lib/features/auth/`
- `lib/features/home/`
- `lib/features/orders/`
- `lib/features/inventory/`

## Correspondencia con Laravel

La referencia visual fuerte para `Pedido` viene de:

- `routes/web.php`
- `app/Http/Controllers/Operacion/PedidoPisoController.php`
- `resources/views/operacion/pedidos_piso/index.blade.php`

Elementos trasladados a Flutter:

- encabezado de pedidos de piso
- pill de sucursal
- buscador hero para escanear o buscar producto
- resumen inline de productos, almacenes y total
- grupos por almacén
- CTA fijo para generar pedidos

## Regla funcional clave

El flujo UX debe reforzar esto:

- un cliente puede elegir productos de varios almacenes
- cada almacén genera su propio pedido
- si hay 2 almacenes, se generan 2 pedidos

Por eso la pantalla `Pedido` ya está maquetada por grupos de almacén y no como un carrito plano.

## Siguiente paso sugerido

Primero consolidar UX y navegación en Flutter.

Después conectar por fases:

1. login mock -> login real
2. sucursales y almacenes
3. búsqueda de productos
4. crear pedido por almacén
