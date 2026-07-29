# Impresion automatica por agente local

## Objetivo

Permitir que el navegador del POS envie tickets a un agente de impresion instalado en una computadora Windows, sin depender del dialogo manual de impresion del navegador.

## Flujo implementado en POS

1. El POS obtiene la URL autenticada del ticket PDF.
2. El navegador descarga ese PDF usando la sesion actual del usuario.
3. Si el agente local esta habilitado en esa computadora, el navegador convierte el PDF a Base64.
4. El navegador envia el documento al agente local en `POST {AGENTE}/api/print-jobs`.
5. Si el agente no responde o rechaza el trabajo, el POS hace fallback y abre el PDF en una pestaña nueva.

## Configuracion por computadora

La configuracion se guarda en `localStorage`, asi que es especifica del navegador y de la computadora:

- `laisuriana.pos.agente_impresion.habilitado`
- `laisuriana.pos.agente_impresion.url`

En la cabecera del POS se agrego el boton `Agente` para capturar la URL local. Valor sugerido inicial:

```text
http://127.0.0.1:17890
```

## Contrato esperado del agente Windows

Endpoint:

```http
POST /api/print-jobs
Content-Type: application/json
Accept: application/json
```

Cuerpo:

```json
{
  "source": "laisuriana-pos",
  "content_type": "application/pdf",
  "document_name": "ticket-venta-123.pdf",
  "document_base64": "JVBERi0xLjcKJc..."
}
```

Respuesta exitosa sugerida:

```json
{
  "ok": true,
  "message": "Ticket enviado a la impresora"
}
```

Respuesta de error sugerida:

```json
{
  "ok": false,
  "message": "No se encontro la impresora configurada"
}
```

## Recomendacion para el agente

La primera version del agente en Windows puede ser una app local que:

- escuche solo en `127.0.0.1`
- acepte PDF en Base64
- lo guarde temporalmente
- lo mande a la impresora termica configurada para esa caja
- devuelva un JSON corto con exito o error

## Alcance actual

Esta base ya quedo integrada en el POS para:

- tickets de venta
- tickets de creditos por cambio
- tickets de movimientos de caja
- tickets de corte de caja

No sustituye aun el servicio Windows directo que ya existe en inventario para recepciones horizontales. Ese flujo sigue independiente.

## Variante .NET por computadora

Se agrego una base de agente en:

[`tools/windows-print-agent`](C:/xampp-8/htdocs/laisuriana/tools/windows-print-agent)

La idea es que cada computadora tenga:

- su propia instancia del agente escuchando en `127.0.0.1:17890`
- su propia impresora configurada localmente
- su propio `appsettings.json`

Con eso, la impresion automatica queda amarrada a la computadora donde el usuario esta trabajando. La PC A imprime solo sus tickets; la PC B imprime solo los suyos.

Nota tecnica:

- el agente .NET ya quedo preparado para aceptar CORS desde `localhost` y `127.0.0.1`
- eso evita el bloqueo del navegador cuando el POS corre, por ejemplo, en `http://127.0.0.1:8000`

## Instalacion final esperada

El flujo de entrega no debe pedirle al cliente abrir terminales.

La forma correcta de despliegue para cada computadora es:

1. generar un `Setup.exe`
2. el usuario final ejecuta ese instalable
3. el instalador registra `LaisurianaPrintAgent` como servicio de Windows
4. el servicio queda en arranque automatico para que imprima siempre al prender la PC
