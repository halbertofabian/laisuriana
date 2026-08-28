# Despliegue del API para Suriana Vendedor

La aplicación Android de producción consume:

```text
https://laisuriana.softmor.com/api/v1/mobile
```

## Cambios que deben publicarse juntos

El despliegue debe incluir el API completo, no solamente `routes/api.php`:

- controladores en `app/Http/Controllers/Api/V1/Mobile`;
- servicios y modelos modificados para pedidos de piso;
- configuración de rutas, CORS y Sanctum;
- migraciones de tokens personales y `pdp_mobile_request_id`;
- `composer.json` y `composer.lock`.

Las migraciones son necesarias para autenticar la app y evitar pedidos duplicados cuando Android reintenta una solicitud.

## Publicación

1. Crear un respaldo verificable de la base de datos.
2. Publicar el mismo commit que haya aprobado la suite de pruebas.
3. Desde la carpeta de Laravel, ejecutar:

```bash
php artisan down --retry=30
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan up
```

Si un comando falla, no ejecutar `artisan up` hasta corregir la causa. No imprimir ni crear pedidos como parte del despliegue.

Antes de probar desde Android, ejecutar la verificación interna:

```bash
php artisan mobile:verificar-api
```

El comando valida migraciones, rutas, middleware Sanctum, CORS y HTTPS sin crear pedidos ni solicitar credenciales.

## Verificación sin datos reales

El diagnóstico no consulta la base de datos ni requiere credenciales:

```bash
curl --fail-with-body \
  -H 'Accept: application/json' \
  https://laisuriana.softmor.com/api/v1/mobile/health
```

Respuesta esperada:

```json
{"data":{"status":"ok","service":"suriana-mobile-api","version":"v1"}}
```

Verificar también el preflight que utiliza Capacitor:

```bash
curl --fail-with-body -i -X OPTIONS \
  -H 'Origin: http://localhost' \
  -H 'Access-Control-Request-Method: GET' \
  -H 'Access-Control-Request-Headers: authorization,content-type' \
  https://laisuriana.softmor.com/api/v1/mobile/health
```

La respuesta debe ser `204` e incluir `Access-Control-Allow-Origin: http://localhost`.

## Puerta de salida para el APK

No distribuir el APK hasta confirmar todos estos puntos:

- `health` responde `200` por HTTPS;
- el preflight responde `204` para `http://localhost`;
- la suite de pruebas móvil termina sin fallos;
- el APK contiene la URL productiva y no contiene `127.0.0.1`;
- el APK está firmado con la llave oficial y `apksigner verify` es exitoso;
- una prueba controlada de inicio de sesión fue autorizada por el responsable.

La creación, edición, cancelación o impresión de un pedido real requiere autorización separada.
