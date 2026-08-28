# Entregables Android

## `Suriana-Vendedor-0.1.0-produccion-prueba.apk`

- API incluida: `https://laisuriana.softmor.com/api/v1/mobile`
- Canal visible: `Prueba de producción`
- Instalación directa en Android: sí
- Firma: Android Debug, solamente para preentrega
- SHA-256: `2c7e2a07213ce7825076fedffbf40b8833af7dbecbcd18214fa74a834e26beb5`

Esta APK comenzará a funcionar contra producción cuando `/api/v1/mobile/health` esté publicado. No debe presentarse como Release oficial ni subirse a Google Play.

La versión final debe compilarse con `npm run android:release`, firmarse con la llave permanente y verificarse con `apksigner verify`.
