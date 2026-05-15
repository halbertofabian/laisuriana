# LaSuriana App Mobile

Base de app Android (React Native + Expo) para pedidos de piso.

## Login implementado

- Captura `usuario` y `contrasena`.
- Consume `POST /login`.
- Guarda sesion local con `AsyncStorage`.
- Permite cerrar sesion.

## Ejecutar

1. Instala dependencias:

```bash
npm install
```

2. Crea `.env` desde `.env.example` y ajusta `EXPO_PUBLIC_API_BASE_URL`.

3. Inicia Expo:

```bash
npm run start
```

4. Para Android:

```bash
npm run android
```
