# Impresion por dispositivo

## Identificacion del dispositivo

- El sistema identifica cada computadora o navegador con la cookie propia `laisuriana_device_id`.
- Esa cookie no depende del usuario autenticado, caja ni sucursal.
- Si un usuario cierra sesion y entra otro en la misma computadora y mismo navegador, la configuracion sigue siendo la misma.
- Si el navegador borra cookies o se usa otro perfil, el sistema lo tratara como un dispositivo nuevo.

## Persistencia de impresora

- La configuracion se guarda en `tbl_dispositivo_impresoras_dip`.
- Cada registro se relaciona por `dip_device_uid`.
- Se soportan dos tipos de conexion:
  - `red`: guarda nombre descriptivo, impresora, host/IP, puerto y controlador/comando.
  - `usb`: guarda nombre descriptivo, impresora local y URL del agente local.

## Agente local esperado

- El agente instalable se llama `LAISURIANAPRINT-SOFTMOR`.
- Debe instalarse en Windows, quedar residente y arrancar automaticamente al encender el equipo.
- Debe exponer un endpoint local en loopback, por ejemplo `http://127.0.0.1:17890` o `https://127.0.0.1:17890`, para recibir trabajos de impresion del sistema.
- El agente debe validar origenes permitidos y no aceptar peticiones arbitrarias desde cualquier sitio.

## Consideraciones para HTTPS

- En desarrollo local puede usarse `http://127.0.0.1`.
- Cuando el sistema se publique en HTTPS, el punto ideal es que el agente local ofrezca `https://127.0.0.1` o `https://localhost` con el certificado local instalado por el mismo instalador.
- Si el agente solo expone HTTP y la app corre en HTTPS, algunos navegadores pueden bloquear la comunicacion por mixed content o reglas de Private Network Access.
- Por eso la configuracion deja guardada la URL del agente por dispositivo y documenta la necesidad de un canal local compatible con HTTPS.
