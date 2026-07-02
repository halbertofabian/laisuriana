# Base técnica inicial Android para pedido de piso

## Decisión técnica

Se toma `app-mobile/` como base oficial para siguientes fases.

- Ya consume endpoints reales del backend Laravel.
- Está enfocada a Android/Expo y permite iterar rápido con personal de piso.
- `app_flutter/` existe, pero hoy se comporta como prototipo visual sin acoplamiento funcional al módulo actual.

## Arquitectura propuesta

La app queda organizada en capas ligeras, listas para crecer:

- `app/`: bootstrap, providers, navegación y tema.
- `features/`: módulos por dominio operativo.
- `shared/`: infraestructura común, tipos transversales, UI reusable y storage.

Principios aplicados:

- UI separada de servicios HTTP.
- Estado aislado por contexto de autenticación, contexto operativo y borrador de pedido.
- Lógica de agrupación por almacén fuera de pantallas.
- Reglas no confirmadas marcadas como pendiente, no inventadas.

## Estructura principal

```text
app-mobile/
  App.tsx
  docs/pedido-piso-mobile-base.md
  src/
    app/
      navigation/
      providers/
      theme/
    features/
      auth/
      clients/
      floor-order/
      history/
      home/
      operational-context/
      tickets/
    shared/
      api/
      config/
      storage/
      types/
      ui/
```

## Flujo cubierto en esta base

- Login móvil.
- Selección de sucursal/contexto.
- Búsqueda de producto por texto.
- Resolución de almacén por producto usando backend actual.
- Carrito agrupado por almacén.
- Cliente opcional.
- Notas de pedido.
- Descuento por línea modelado en el draft.
- Confirmación que genera uno o varios pedidos por almacén.
- Pantalla de ticket con apertura del PDF Laravel.
- Historial de pedidos pendientes de cobro.

## Endpoints backend reutilizables

Autenticación y contexto:

- `POST /mobile/login`
- `GET /mobile/login/buscar-usuarios`
- `GET /mobile/sucursales`
- `GET /mobile/almacenes?scl_id={id}`

Pedido de piso:

- `GET /mobile/pedidos-piso/data`
- `GET /mobile/pedidos-piso/productos/buscar?q=...`
- `POST /mobile/pedidos-piso`
- `GET /operacion/pedidos-piso/productos/resolver?psk_id=...&pdp_scl_id=...`
- `GET /operacion/pedidos-piso/productos/validar?psk_id=...&pdp_scl_id=...&pdp_alm_id=...`
- `GET /operacion/pedidos-piso/{pedido}`
- `GET /operacion/pedidos-piso/{pedido}/ticket`
- `GET /operacion/pedidos-piso/buscar-por-folio?folio=...`
- `PUT /operacion/pedidos-piso/{pedido}`
- `DELETE /operacion/pedidos-piso/{pedido}`

Clientes:

- `GET /pos/clientes/buscar?q=...`

## Endpoints faltantes o pendientes

- Endpoint móvil dedicado para selección explícita de almacén por producto.
  Nota: hoy se reutiliza el resolver web y la app toma la primera opción si hay varias. Pendiente UX final.
- Endpoint móvil dedicado para historial con paginación.
  Nota: hoy se reutiliza `/mobile/pedidos-piso/data`.
- Endpoint móvil para detalle de ticket en JSON.
  Nota: hoy se abre el PDF Laravel.
- Endpoint para alta rápida de cliente desde app.
  Pendiente de confirmar si se permite en operación de piso.
- Endpoint para reintento/reimpresión optimizado para móvil.
- Endpoint o estrategia para logout móvil explícito.
- Estrategia backend para expiración de sesión móvil y renovación.

## Reglas confirmadas reutilizadas

- Un borrador puede terminar en múltiples pedidos por almacén.
- La sucursal activa condiciona la resolución de almacenes.
- Algunos productos requieren selección de almacén.
- Unidad `M` permite decimales.
- Cliente opcional.
- Descuento por línea.
- Estatus inicial `pendiente_cobro`.
- Cobro e impacto inventario ocurren después en POS/caja.
- Folio y ticket son parte crítica del flujo.

## Pendiente de confirmar

- Si crear pedido reserva existencia o solo registra intención de cobro.
- Si la app móvil podrá editar o cancelar pedidos ya creados en producción.
- Si el personal de piso puede crear clientes desde la app.
- Si el flujo móvil debe imprimir localmente o solo abrir PDF/ticket.
- Si la selección de múltiples almacenes debe ser manual siempre o puede sugerirse una preferencia.

## Riesgos técnicos detectados

- El grupo `/mobile` todavía no expone todos los endpoints del dominio; hoy se mezclan rutas móviles y rutas web autenticadas.
- La sesión móvil actual depende de cookies Laravel; para dispositivos reales habrá que validar dominio, HTTPS y persistencia.
- La UX de selección de almacén múltiple todavía es básica en la app y necesita una pantalla/modal dedicada.
- La pantalla de descuentos solo deja lista la estructura; falta una UX más completa para `importe` y cantidad con descuento.
