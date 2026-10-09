# Entregables Android

## `Suriana-Vendedor-0.2.0.apk` — 9 de octubre de 2026

- Descarga: https://laisuriana.softmor.com/downloads/Suriana-Vendedor-0.2.0.apk
- Versión: `0.2.0`; código Android: `2`; paquete: `mx.lasuriana.vendedor`.
- API incluida: `https://laisuriana.softmor.com/api/v1/mobile`.
- Compilación Release con tráfico HTTP bloqueado, firmada con la misma llave Android Debug de la entrega 0.1.0, por solicitud del responsable para permitir su actualización.
- Esta firma de pruebas no es una llave oficial de producción ni una entrega para Google Play.
- Certificado SHA-256: `4aaa5c9cc51d68b9ec13a3c0b1a0c0781f37074d3d8bdf2ec58fc2bee92f24d0`.
- APK SHA-256: `8daba9816669a8d96e404e79ec0164f224d98ef360ad7e3080aa068c92f84987`.
- Verificado con `apksigner verify`, `aapt dump badging` y revisión del endpoint empaquetado.
- Cambios: catálogo, carrito, cliente, cantidades por metro, pedidos, tickets y ajustes de impresión.
- La APK heredada `lasuriana-app-release.apk` pertenece al paquete distinto `com.lasuriana.pedidos`; se conserva y no se reemplaza.

## `Suriana-Vendedor-0.1.0-produccion-prueba.apk`

- API incluida: `https://laisuriana.softmor.com/api/v1/mobile`
- Canal visible: `Prueba de producción`
- Instalación directa en Android: sí
- Firma: Android Debug, solamente para preentrega
- SHA-256: `2c7e2a07213ce7825076fedffbf40b8833af7dbecbcd18214fa74a834e26beb5`

Esta APK comenzará a funcionar contra producción cuando `/api/v1/mobile/health` esté publicado. No debe presentarse como Release oficial ni subirse a Google Play.

La versión final debe compilarse con `npm run android:release`, firmarse con la llave permanente y verificarse con `apksigner verify`.
