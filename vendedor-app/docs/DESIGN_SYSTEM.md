# Sistema de diseño

## Dirección

El producto usa el azul oficial de iSuriana `#1064AE` sobre una base neutra fría. El color de marca no se usa para decorar: señala navegación, acción principal y estados seleccionados. Los azules claros aportan foco sin competir con la acción principal.

La interfaz sigue una cuadrícula de 4 px, blancos generosos y superficies principalmente planas. Las sombras se reservan para elementos flotantes o transitorios.

## Tipografía

Familia: Manrope Variable, incluida dentro de la aplicación para funcionar sin red.

| Rol | Tamaño | Peso | Uso |
|---|---:|---:|---|
| Display | 32 px | 750 | Acceso y momentos excepcionales |
| Título de resultado | 26 px | 750 | Confirmación de pedido |
| Título de pantalla | 20 px | 750 | Encabezado principal |
| Título de sección | 17 px | 750 | Agrupar contenido |
| Cuerpo | 15 px | 450–650 | Lectura y controles |
| Secundario | 13 px | 450–700 | Metadatos y ayuda |
| Etiqueta | 11 px | 700–800 | Estado, sobrelínea y SKU |

Las frases usan mayúsculas y minúsculas. Las mayúsculas completas se limitan a etiquetas muy cortas.

## Espaciado

- Unidad base: 4 px.
- Margen lateral móvil: 20 px.
- Separación entre elementos relacionados: 8–12 px.
- Separación entre grupos: 20–32 px.
- Área táctil mínima: 44 × 44 px.
- Botón principal: 52 px de alto.

## Forma y elevación

- Controles pequeños: radio de 10 px.
- Inputs y botones: 14 px.
- Tarjetas y listas: 18 px.
- Bottom sheets: 24 px en esquinas superiores.
- Píldoras sólo para estados o controles segmentados; no para cualquier texto.
- Una sombra ligera para superficies elevadas y una sombra más marcada sólo para el carrito flotante.

## Color

| Token | Valor | Función |
|---|---|---|
| Brand 900 | `#073B68` | Superficie de alto contraste |
| Brand 800 | `#1064AE` | Color oficial y acción principal |
| Accent 500 | `#8CC8FA` | Foco e identidad secundaria |
| Canvas | `#F6F8FB` | Fondo principal |
| Surface | `#FFFFFF` | Contenido agrupado |
| Ink | `#17211F` | Texto principal |
| Ink secondary | `#64706C` | Texto secundario |
| Border | `#DDE2DD` | Separación |
| Success | `#23765F` | Éxito y conexión |
| Warning | `#9A6412` | Pendiente y atención |
| Danger | `#AD3B38` | Acción destructiva |

Todos los pares principales mantienen contraste legible. El estado nunca depende únicamente del color: se acompaña de icono o texto.

## Componentes base

### Botones

- Primario: una sola acción dominante por contexto.
- Secundario: borde neutro para una alternativa válida.
- Discreto: navegación o acción de baja prioridad.
- Peligro: fondo rojo suave; sólo para acciones destructivas.
- Estados: normal, presionado, foco, deshabilitado y cargando.

### Inputs

- Etiqueta siempre visible cuando el dato puede ser ambiguo.
- Placeholder como ejemplo, nunca como única etiqueta.
- Error en línea debajo del campo.
- Búsqueda usa un componente distinto y compacto.

### Tarjetas y listados

- Una tarjeta agrupa una entidad; no se anidan tarjetas.
- Los listados usan divisores y una sola acción de apertura.
- La fila completa es el objetivo táctil.
- El menú contextual contiene acciones infrecuentes.

### Selectores

- Control segmentado sólo para dos o tres vistas locales.
- Bottom sheet para seleccionar cliente, sucursal o impresora.
- Radio visible cuando la selección es única.

### Mensajes

- Confirmación destructiva o irreversible: bottom sheet.
- Resultado importante: pantalla dedicada.
- Aviso no bloqueante: franja integrada.
- Respuesta breve a una acción: toast visual propio.
- Nunca `alert()`, `confirm()` ni cuadros nativos improvisados.

### Estados vacíos y carga

- Vacío: icono pequeño, título humano, una frase y como máximo una acción.
- Carga inicial: skeleton con la geometría real de las filas.
- Carga de botón: conserva el ancho y muestra indicador.

### Iconografía

- Lucide, trazo de 1.8–2 px.
- Tamaño común: 18–22 px.
- Los iconos apoyan una etiqueta; no reemplazan texto en acciones importantes.
- Botones sólo con icono requieren nombre accesible.

## Movimiento

- Interacción rápida: 140 ms.
- Entrada de pantalla o sheet: 240 ms.
- Curva estándar: `cubic-bezier(0.2, 0, 0, 1)`.
- Se respeta `prefers-reduced-motion`.

## Reglas de revisión

Antes de aceptar una pantalla:

1. El título responde dónde está el usuario.
2. La primera zona visible contiene sólo información necesaria.
3. Existe como máximo una acción visualmente dominante.
4. Todos los objetivos táctiles miden al menos 44 px.
5. Usa tokens y componentes existentes; una excepción se documenta.
6. Se revisan carga, vacío, error, sin conexión y éxito.
7. Se prueba a 360 px de ancho y con texto ampliado.
