# Suriana Vendedor

Aplicación Android híbrida independiente para vendedores de piso. La interfaz está construida en React y Capacitor; Laravel expone únicamente el API móvil.

## Funciones actuales

- Acceso con sugerencias de usuario y token Sanctum cifrado mediante Android Keystore.
- Sucursal activa, catálogo, clientes, almacenes, cantidades decimales y descuentos parciales.
- Un pedido por almacén, creación atómica e idempotente y totales calculados por Laravel.
- Borradores cifrados recuperables después de cerrar la aplicación.
- Historial, detalle, edición y cancelación de pedidos pendientes.
- Actualización automática del estado después del cobro en caja.
- Escáner de códigos con Google Code Scanner.
- Impresión Bluetooth ESC/POS, Zebra ZPL y Zebra CPCL con Code 128.
- Manejo visual de conexión, servidor no disponible, errores y sesión vencida.

## Desarrollo local en Android

El modo local apunta a `http://127.0.0.1:8187/api/v1/mobile`. El tráfico HTTP está permitido exclusivamente en la variante Debug.

```bash
npm install
npm run android:local
cd android
./gradlew assembleDebug
adb reverse tcp:8187 tcp:8187
adb install -r app/build/outputs/apk/debug/app-debug.apk
```

## Compilación web o Android de producción

Production exige un endpoint HTTPS. El proyecto queda configurado para el dominio operativo:

```dotenv
VITE_API_BASE_URL=https://laisuriana.softmor.com/api/v1/mobile
VITE_BUILD_CHANNEL=Producción
```

Antes de distribuir una versión, `GET /api/v1/mobile/auth/usuarios` debe estar disponible en ese servidor. Un `404` indica que las rutas del API móvil aún no se han desplegado.

Después ejecuta:

```bash
npm run android:release
cd android
./gradlew assembleRelease
```

La configuración Android Release bloquea todo tráfico HTTP sin cifrar, aunque por error se empaquetara una URL local.

### Probar el servidor productivo antes de firmar

Este modo genera un APK Debug instalable, pero conectado al API productivo:

```bash
npm run android:production-test
cd android
./gradlew assembleDebug
adb install -r app/build/outputs/apk/debug/app-debug.apk
```

En Cuenta debe mostrarse `Prueba de producción` y `Servidor · Conectado`. No ejecutar esta prueba mientras `https://laisuriana.softmor.com/api/v1/mobile/health` responda `404`.

## Firma de Release

Los archivos `.jks`, `.keystore` y `android/keystore.properties` están excluidos del repositorio. Copia `android/keystore.properties.example` como `android/keystore.properties` y apunta a un almacén de claves guardado fuera del proyecto.

```properties
storeFile=/ruta/segura/suriana-vendedor.jks
storePassword=CONTRASEÑA_SEGURA
keyAlias=suriana-vendedor
keyPassword=CONTRASEÑA_SEGURA
```

En una máquina de compilación también pueden usarse variables de entorno, evitando guardar contraseñas en el proyecto:

```bash
export SURIANA_KEYSTORE_FILE=/ruta/segura/suriana-vendedor.jks
export SURIANA_KEYSTORE_PASSWORD='CONTRASEÑA_SEGURA'
export SURIANA_KEY_ALIAS=suriana-vendedor
export SURIANA_KEY_PASSWORD='CONTRASEÑA_SEGURA'
```

Si solo se proporciona una parte de la configuración, Gradle detiene la compilación para evitar una APK mal firmada.

La versión puede establecerse sin modificar archivos:

```bash
./gradlew assembleRelease -PSURIANA_VERSION_CODE=1 -PSURIANA_VERSION_NAME=0.1.0
```

Sin `keystore.properties`, Gradle puede verificar el código Release pero genera un APK sin firma que no debe distribuirse.

## Verificación

```bash
npm run build:android:local
php artisan test tests/Feature/Api/MobileAuthTest.php \
  tests/Feature/Api/MobileOrderCatalogTest.php \
  tests/Feature/Api/MobileFloorOrderTest.php \
  tests/Feature/Operacion/PosCambiosYCancelacionesTest.php
```

La arquitectura del producto y el sistema visual se documentan en `docs/PRODUCT_ARCHITECTURE.md` y `docs/DESIGN_SYSTEM.md`.
