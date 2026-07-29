# Agente de impresion Windows (.NET)

Este agente esta pensado para operar por computadora.

## Como funciona por computadora

Cada computadora Windows corre su propia instancia del agente escuchando en:

```text
http://127.0.0.1:17890
```

Eso hace que:

- la computadora A solo envie tickets a `127.0.0.1` de la computadora A
- la computadora B solo envie tickets a `127.0.0.1` de la computadora B
- una computadora no pueda imprimir automaticamente en otra, salvo que alguien cambie la configuracion a mano

En otras palabras, el navegador siempre habla con el agente local de esa misma PC, no con un servidor central de impresion.

El agente acepta solicitudes del POS desde origenes locales como:

- `http://127.0.0.1:8000`
- `http://localhost:8000`
- cualquier otro puerto local en `127.0.0.1` o `localhost`

## Dependencias

Este primer corte usa:

- .NET 8 SDK para compilar el paquete
- SumatraPDF para imprimir PDF de forma silenciosa

Ruta esperada por defecto:

```text
C:\Program Files\SumatraPDF\SumatraPDF.exe
```

## Configuracion local

Editar `appsettings.json` en cada computadora:

- `PrinterAgent:PrinterName`
- `PrinterAgent:SumatraPdfPath`
- `PrinterAgent:BindUrl`

Ejemplo:

```json
{
  "PrinterAgent": {
    "BindUrl": "http://127.0.0.1:17890",
    "PrinterName": "EPSON TM-T20III",
    "SumatraPdfPath": "C:\\Program Files\\SumatraPDF\\SumatraPDF.exe",
    "TempDirectory": "C:\\Temp\\LaisurianaPrintAgent",
    "AllowedSources": [ "laisuriana-pos" ],
    "PrintTimeoutSeconds": 30
  }
}
```

## API

### Estado

```http
GET /api/status
```

### Imprimir

```http
POST /api/print-jobs
Content-Type: application/json
```

```json
{
  "source": "laisuriana-pos",
  "content_type": "application/pdf",
  "document_name": "ticket-venta-15.pdf",
  "document_base64": "JVBERi0xLjcKJc..."
}
```

## Publicacion tecnica

En una computadora Windows con .NET 8 SDK:

```powershell
dotnet publish .\Laisuriana.PrintAgent.csproj -c Release -r win-x64 --self-contained true
```

## Modo servicio

El agente ya esta preparado para correr como servicio de Windows y arrancar automaticamente al prender la computadora.

## Instalacion para usuario final

El usuario final no deberia abrir PowerShell ni correr comandos.

La entrega correcta es:

1. compilar el agente
2. generar un instalador
3. el cliente ejecuta un solo `Setup.exe`
4. el instalador copia archivos, registra el servicio y lo deja en inicio automatico

Base del instalador:

- script de Inno Setup: `installer\Laisuriana.PrintAgent.iss`
- servicio de Windows: `LaisurianaPrintAgent`

## Ejecucion manual de soporte

```powershell
dotnet .\Laisuriana.PrintAgent.dll
```

## Siguiente paso recomendado

Cambiar la impresion del POS a ESC/POS RAW nativo, para no depender de PDF cuando se trate de tickets termicos.
