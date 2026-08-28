import { defineConfig, loadEnv } from 'vite';
import react from '@vitejs/plugin-react';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), 'VITE_');
  const apiBaseUrl = env.VITE_API_BASE_URL?.trim();

  if (mode === 'production' || mode === 'production-test') {
    if (!apiBaseUrl) {
      throw new Error('VITE_API_BASE_URL es obligatorio para compilar una aplicación conectada a producción.');
    }

    let parsedUrl: URL;
    try {
      parsedUrl = new URL(apiBaseUrl);
    } catch {
      throw new Error('VITE_API_BASE_URL debe ser una URL absoluta válida.');
    }

    if (parsedUrl.protocol !== 'https:') {
      throw new Error('Una aplicación conectada a producción solo permite un VITE_API_BASE_URL con HTTPS.');
    }
  }

  return {
    plugins: [
      react(),
      VitePWA({
        injectRegister: null,
        registerType: 'autoUpdate',
        includeAssets: ['app-icon.svg'],
        manifest: {
          name: 'Suriana Vendedor',
          short_name: 'Suriana',
          description: 'Pedidos de piso para vendedores de La Suriana',
          theme_color: '#1064AE',
          background_color: '#F6F8FB',
          display: 'standalone',
          orientation: 'portrait',
          icons: [
            {
              src: 'app-icon.svg',
              sizes: 'any',
              type: 'image/svg+xml',
              purpose: 'any maskable',
            },
          ],
        },
      }),
    ],
  };
});
