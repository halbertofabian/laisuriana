# QA rápido - Recibir mercancía (Inventario Base)

## Preparación
1. Inicia sesión con usuario que tenga permisos:
- `inventario_base.ver`
- `inventario_base.entrada`
- (opcional) `inventario_base.inicial`
2. Entra a `Operación > Inventario > Recibir mercancía`.

## Caso 1 - Búsqueda de productos (modal)
1. Clic en `Buscar artículos`.
2. Probar filtros: texto, marca, modelo, línea, categoría.
3. Elegir 2-3 productos y `Agregar seleccionados`.

Esperado:
- Carga productos en matriz.
- Filtro línea limita categorías.
- No se congela el selector.

## Caso 2 - Dominante global + matriz horizontal
1. Selecciona dominante global (ej. `Talla` o `Color`).
2. Verifica que la tabla sea única (todos los productos juntos).
3. Verifica columnas no aplicables en readonly con `N/A`.

Esperado:
- Una sola tabla.
- Dominante único aplicado a todos.
- Celdas aplicables editables, no aplicables bloqueadas.

## Caso 3 - Costo unitario por fila
1. Selecciona `GUES-006 - Playera GUESS`.
2. Verifica costo unitario por default.

Esperado:
- Debe autollenarse con `125.00` (costo base producto).

## Caso 4 - Quitar/restaurar
1. Quitar una fila con botón rojo.
2. Quitar un producto con botón gris.
3. Clic en `Restaurar quitados`.

Esperado:
- Se ocultan filas/producto.
- `Restaurar quitados` habilita cuando hay elementos removidos.
- Al restaurar, reaparecen todos.

## Caso 5 - Tipo de entrada y presets
1. Cambiar tipo a `Compra con remisión`.
2. Cambiar tipo a `Compra con factura`.

Esperado:
- Preset automático de sucursal/almacén (editable).
- Para factura: proveedor obligatorio.
- Para compra: referencia y fecha emisión obligatorias.
- Opción `N/A` en referencia funciona.

## Caso 6 - Totales monetarios
1. Captura cantidades y costos.
2. Aplica descuento por porcentaje.
3. Cambia a descuento por importe.
4. Captura flete.
5. Ajusta IVA (ej. 8%, 16%).

Esperado:
- Recalcula en vivo: Subtotal, Descuento, Flete, IVA, Total.

## Caso 7 - Registro + PDF
1. Clic `Guardar entrada`.
2. Validar mensaje de éxito.
3. Ir a pestaña `Reportes entradas`.
4. Abrir `Ver PDF` y `Descargar PDF`.

Esperado:
- Genera movimientos y folios.
- Existe registro en listado de reportes.
- PDF abre/descarga con datos de totales y detalle por matriz.

## Caso 8 - Kardex y existencias
1. Ir a `Kardex` y filtrar por fecha de la captura.
2. Ir a `Existencias` y revisar SKU afectados.

Esperado:
- Kardex muestra movimientos con trazabilidad.
- Columna producto incluye variante cuando aplica.
- Existencias reflejan el incremento correcto.

## Caso 9 - Permisos
1. Probar con usuario que tenga `inventario_base.entrada` pero no `inventario_base.inicial`.
2. Registrar desde `Recibir mercancía`.

Esperado:
- Debe permitir guardar (usa ruta `entradas/masivo`).
- No debe exigir permiso de inventario inicial para este flujo.

## Criterio de salida
- Todos los casos en verde sin errores JS/SQL.
- PDF y reporte visibles.
- Kardex y existencias consistentes.
