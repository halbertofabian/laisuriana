# Arquitectura de producto

## Propósito

Permitir que un vendedor arme un pedido con el cliente, genere un folio y entregue un ticket para pago en caja con la menor cantidad de decisiones posible.

## Módulos necesarios

### 1. Acceso y jornada

- Inicio de sesión.
- Sucursal y almacén activos.
- Estado de sincronización.

### 2. Pedidos

- Pedidos pendientes de cobro como vista inicial.
- Historial inmediato del vendedor.
- Búsqueda por folio o cliente.
- Consulta y reimpresión de un pedido.

### 3. Nuevo pedido

- Selección de cliente.
- Búsqueda de producto por texto, SKU o código de barras.
- Precio, unidad y disponibilidad.
- Cantidad y carrito.
- Nota opcional.
- Revisión y confirmación.

### 4. Ticket

- Folio legible y código para caja.
- Total y cliente.
- Impresión Zebra.
- Reimpresión y compartir folio.

### 5. Cuenta y dispositivo

- Identidad del vendedor.
- Sucursal activa.
- Impresora emparejada y estado.
- Soporte y cierre de sesión.

No se incluyen inventarios administrativos, reportes, configuración comercial ni caja: pertenecen al sistema Laravel de escritorio y distraerían del trabajo del vendedor.

## Navegación

La app usa una raíz y navegación jerárquica:

```text
Acceso
  └── Pedidos
       ├── Nuevo pedido
       │    ├── Catálogo y cliente
       │    ├── Revisión
       │    └── Ticket generado
       ├── Detalle / reimpresión
       └── Cuenta
            ├── Sucursal
            └── Impresora
```

No existe navegación inferior en la primera versión. Sólo hay un módulo principal; “Nuevo pedido” es una acción, no una sección. Se presenta como acción persistente en la raíz y como flujo apilado en pantallas internas.

## Acción principal por pantalla

| Pantalla | Acción principal | Acciones discretas |
|---|---|---|
| Pedidos | Nuevo pedido | Buscar, filtrar, abrir cuenta |
| Catálogo | Ver pedido cuando hay productos | Elegir cliente, ajustar cantidad |
| Revisión | Generar | Nota, eliminar o cambiar cantidad |
| Ticket | Imprimir | Compartir, volver |
| Cuenta | Ninguna | Sucursal, impresora, soporte, salir |

## Estados esenciales

- Cargando: skeletons que conservan la estructura del contenido.
- Vacío: una explicación breve y una sola salida útil.
- Sin conexión: banda no bloqueante; el borrador local permanece disponible.
- Error recuperable: mensaje integrado y acción “Reintentar”.
- Error de validación: debajo del campo relacionado.
- Pedido confirmado: pantalla de éxito, nunca alerta del navegador.
- Impresora ausente: bottom sheet con conexión y ayuda contextual.

## Estrategia técnica

- React + TypeScript para UI y lógica de presentación.
- Vite PWA para instalación, caché del shell y actualización controlada.
- Capacitor para producir la aplicación Android y alojar integraciones nativas.
- Cliente HTTP desacoplado para consumir sólo endpoints del backend.
- Cola local de borradores para tolerar cortes breves de red.
- Plugin Android específico para Zebra mediante el SDK oficial o Bluetooth Classic, según el modelo confirmado.

El backend actual expone rutas móviles bajo sesión web. Para que la app sea realmente API-first, la etapa funcional debe definir autenticación con token, CORS, versión de contrato y endpoints JSON sin dependencia de cookies o HTML.
