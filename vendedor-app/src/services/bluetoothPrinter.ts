import { Capacitor, registerPlugin } from '@capacitor/core';
import type {
  BluetoothPrinterDevice,
  OrderDetail,
  PrinterConfig,
  PrinterLanguage,
} from '../types';

interface PairedDevicesResult {
  supported: boolean;
  enabled: boolean;
  permissionGranted: boolean;
  devices: BluetoothPrinterDevice[];
}

interface BluetoothPrinterPlugin {
  requestAccess(): Promise<{ granted: boolean; enabled: boolean }>;
  getPairedDevices(): Promise<PairedDevicesResult>;
  openBluetoothSettings(): Promise<void>;
  send(options: { address: string; payload: string; charset: string }): Promise<{ success: boolean }>;
}

export interface TicketPrintData {
  order: OrderDetail;
}

const nativePrinter = registerPlugin<BluetoothPrinterPlugin>('BluetoothPrinter');
const ESC = '\u001b';
const GS = '\u001d';

export const printerProfileNames: Record<PrinterLanguage, string> = {
  escpos: 'Térmica ESC/POS',
  zpl: 'Zebra ZPL',
  cpcl: 'Zebra CPCL',
};

function clean(value: string): string {
  return value.replace(/[\^~]/g, ' ').replace(/\s+/g, ' ').trim();
}

function plainMoney(value: number): string {
  return `$${value.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function fit(value: string, length: number): string {
  return value.length > length ? `${value.slice(0, Math.max(0, length - 1))}…` : value;
}

function columns(left: string, right: string, width: number): string {
  const safeRight = fit(right, Math.floor(width * .45));
  const safeLeft = fit(left, Math.max(1, width - safeRight.length - 1));
  return `${safeLeft}${' '.repeat(Math.max(1, width - safeLeft.length - safeRight.length))}${safeRight}`;
}

function escposTest(config: PrinterConfig): string {
  const width = config.paperWidth === '80' ? 48 : 32;
  return `${ESC}@${ESC}a\u0001${ESC}E\u0001iSURIANA\n${ESC}E\u0000PRUEBA DE IMPRESION\n\n${ESC}a\u0000${'-'.repeat(width)}\n${config.name}\nPerfil: ESC/POS · ${config.paperWidth} mm\n${'-'.repeat(width)}\nConexion correcta\n\n\n`;
}

function escposTicket(config: PrinterConfig, data: TicketPrintData): string {
  const width = config.paperWidth === '80' ? 48 : 32;
  const lines = data.order.lines.length > 0
    ? data.order.lines.flatMap((item) => [fit(item.name, width), columns(`${item.quantity} x ${plainMoney(item.price)}`, plainMoney(item.total), width)])
    : [`${data.order.itemCount} articulos`];

  return [
    `${ESC}@${ESC}a\u0001${ESC}E\u0001iSURIANA`,
    `${ESC}E\u0000PEDIDO PARA CAJA`,
    data.order.branch,
    data.order.warehouse,
    '',
    `${GS}!\u0011${data.order.folio}${GS}!\u0000`,
    `Hoy ${data.order.time}`,
    '',
    `${GS}k\u0004${data.order.folio}\u0000`,
    '',
    `${ESC}a\u0000${'-'.repeat(width)}`,
    `Cliente: ${fit(data.order.customer, width - 9)}`,
    `Vendedor: ${fit(data.order.seller, width - 10)}`,
    '-'.repeat(width),
    ...lines,
    '-'.repeat(width),
    `${ESC}E\u0001${columns('TOTAL', plainMoney(data.order.total), width)}${ESC}E\u0000`,
    '',
    `${ESC}a\u0001Presenta este ticket en caja`,
    '',
    '',
    '',
  ].join('\n');
}

function zplPayload(config: PrinterConfig, data?: TicketPrintData): string {
  const width = config.paperWidth === '80' ? 576 : 384;
  const center = Math.floor(width / 2);
  if (!data) {
    return `^XA^CI28^PW${width}^LL430^FO0,35^A0N,42,42^FB${width},1,0,C^FDiSURIANA^FS^FO0,95^A0N,28,28^FB${width},1,0,C^FDPRUEBA DE IMPRESION^FS^FO20,155^GB${width - 40},2,2^FS^FO0,185^A0N,24,24^FB${width},1,0,C^FD${clean(config.name)}^FS^FO0,225^A0N,22,22^FB${width},1,0,C^FDPerfil ZPL - ${config.paperWidth} mm^FS^FO0,285^A0N,26,26^FB${width},1,0,C^FDConexion correcta^FS^XZ`;
  }
  return `^XA^CI28^PW${width}^LL650^FO0,25^A0N,40,40^FB${width},1,0,C^FDiSURIANA^FS^FO0,75^A0N,24,24^FB${width},1,0,C^FDPEDIDO PARA CAJA^FS^FO0,120^A0N,42,42^FB${width},1,0,C^FD${clean(data.order.folio)}^FS^FO${Math.max(10, center - 140)},180^BY2^BCN,90,Y,N,N^FD${clean(data.order.folio)}^FS^FO20,310^GB${width - 40},2,2^FS^FO20,335^A0N,23,23^FDCliente: ${clean(data.order.customer)}^FS^FO20,375^A0N,23,23^FDArticulos: ${data.order.itemCount}^FS^FO20,430^GB${width - 40},2,2^FS^FO20,460^A0N,34,34^FDTotal: ${plainMoney(data.order.total)}^FS^FO0,540^A0N,22,22^FB${width},2,0,C^FDPresenta este ticket en caja^FS^XZ`;
}

function cpclPayload(config: PrinterConfig, data?: TicketPrintData): string {
  const width = config.paperWidth === '80' ? 576 : 384;
  if (!data) {
    return `! 0 200 200 430 1\nPW ${width}\nCENTER\nTEXT 4 0 0 35 iSURIANA\nTEXT 7 0 0 95 PRUEBA DE IMPRESION\nLINE 20 150 ${width - 20} 150 2\nTEXT 7 0 0 185 ${clean(config.name)}\nTEXT 7 0 0 225 Perfil CPCL - ${config.paperWidth} mm\nTEXT 7 0 0 285 Conexion correcta\nFORM\nPRINT\n`;
  }
  return `! 0 200 200 650 1\nPW ${width}\nCENTER\nTEXT 4 0 0 25 iSURIANA\nTEXT 7 0 0 75 PEDIDO PARA CAJA\nTEXT 4 0 0 120 ${clean(data.order.folio)}\nBARCODE 128 1 1 90 45 180 ${clean(data.order.folio)}\nLEFT\nLINE 20 310 ${width - 20} 310 2\nTEXT 7 0 20 335 Cliente: ${clean(data.order.customer)}\nTEXT 7 0 20 375 Articulos: ${data.order.itemCount}\nLINE 20 430 ${width - 20} 430 2\nTEXT 4 0 20 460 Total: ${plainMoney(data.order.total)}\nCENTER\nTEXT 7 0 0 550 Presenta este ticket en caja\nFORM\nPRINT\n`;
}

function buildPayload(config: PrinterConfig, data?: TicketPrintData): { payload: string; charset: string } {
  if (config.language === 'zpl') return { payload: zplPayload(config, data), charset: 'UTF-8' };
  if (config.language === 'cpcl') return { payload: cpclPayload(config, data), charset: 'UTF-8' };
  return { payload: data ? escposTicket(config, data) : escposTest(config), charset: 'CP850' };
}

export function printerErrorMessage(error: unknown): string {
  const code = typeof error === 'object' && error !== null && 'code' in error ? String(error.code) : '';
  if (code === 'BLUETOOTH_DISABLED') return 'Activa Bluetooth para continuar.';
  if (code === 'BLUETOOTH_PERMISSION') return 'Permite el acceso a dispositivos cercanos.';
  if (code === 'CONNECTION_FAILED') return 'No pudimos conectar. Revisa que la impresora esté encendida y cerca.';
  if (code === 'DEVICE_NOT_FOUND') return 'La impresora ya no está emparejada.';
  return 'No pudimos comunicarnos con la impresora.';
}

export const bluetoothPrinter = {
  isNative: Capacitor.isNativePlatform(),
  requestAccess: () => nativePrinter.requestAccess(),
  getPairedDevices: (): Promise<PairedDevicesResult> => nativePrinter.getPairedDevices(),
  openBluetoothSettings: () => nativePrinter.openBluetoothSettings(),
  async printTest(config: PrinterConfig): Promise<void> {
    const job = buildPayload(config);
    await nativePrinter.send({ address: config.address, ...job });
  },
  async printTicket(config: PrinterConfig, data: TicketPrintData): Promise<void> {
    const job = buildPayload(config, data);
    await nativePrinter.send({ address: config.address, ...job });
  },
};
