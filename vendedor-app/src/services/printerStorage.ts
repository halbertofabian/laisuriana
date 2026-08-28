import type { PrinterConfig } from '../types';

const PRINTER_KEY = 'suriana_printer_config';

export function getPrinterConfig(): PrinterConfig | null {
  try {
    const stored = window.localStorage.getItem(PRINTER_KEY);
    return stored ? JSON.parse(stored) as PrinterConfig : null;
  } catch {
    return null;
  }
}

export function setPrinterConfig(config: PrinterConfig): void {
  window.localStorage.setItem(PRINTER_KEY, JSON.stringify(config));
}

export function clearPrinterConfig(): void {
  window.localStorage.removeItem(PRINTER_KEY);
}
