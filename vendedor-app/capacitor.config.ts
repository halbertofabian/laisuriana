import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'mx.lasuriana.vendedor',
  appName: 'Suriana Vendedor',
  webDir: 'dist',
  backgroundColor: '#F6F8FB',
  plugins: {
    CapacitorHttp: {
      enabled: true,
    },
  },
  android: {
    backgroundColor: '#F6F8FB',
  },
};

export default config;
