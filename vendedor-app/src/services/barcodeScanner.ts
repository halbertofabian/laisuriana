import { Capacitor, registerPlugin } from '@capacitor/core';

interface BarcodeScanResult {
  cancelled: boolean;
  value?: string;
  format?: number;
}

interface BarcodeScannerPlugin {
  scan(): Promise<BarcodeScanResult>;
}

const nativeScanner = registerPlugin<BarcodeScannerPlugin>('BarcodeScanner');

export const barcodeScanner = {
  isAvailable: Capacitor.isNativePlatform(),

  async scan(): Promise<BarcodeScanResult> {
    if (!Capacitor.isNativePlatform()) {
      throw new Error('El lector de cámara está disponible en la aplicación Android.');
    }

    return nativeScanner.scan();
  },
};
