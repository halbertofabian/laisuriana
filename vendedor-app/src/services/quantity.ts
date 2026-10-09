import type { CartLine, Product } from '../types';

/** Decimales que admite el backend para `ppd_cantidad` (decimal 14,2). */
export const QUANTITY_DECIMALS = 2;
const SCALE = 10 ** QUANTITY_DECIMALS;
export const MIN_DECIMAL_QUANTITY = 1 / SCALE;
/** Límite de captura para evitar desbordes accidentales (99,999.99 m). */
export const MAX_QUANTITY = 99_999.99;

/** El catálogo identifica el metro por el código de la unidad de medida, igual que Laravel. */
export function isMeterProduct(product: Pick<Product, 'unitCode'>): boolean {
  return product.unitCode.trim().toUpperCase() === 'M';
}

/**
 * Artículos para contadores: las piezas suman su cantidad y cada partida por metro con cantidad
 * confirmada cuenta como 1. Las partidas pendientes de capturar metros no se cuentan.
 */
export function countItems(lines: Array<Pick<Product, 'unitCode'> & { quantity: number }>): number {
  return roundQuantity(lines.reduce((sum, line) => sum + (isMeterProduct(line) ? (line.quantity > 0 ? 1 : 0) : line.quantity), 0));
}

export type MeterLineState = 'pending' | 'unconfirmed' | 'confirmed';

/** Estado de captura de una línea por metro; null para cualquier otra unidad. */
export function meterLineState(line: Pick<CartLine, 'unitCode' | 'quantity' | 'meterInput'>): MeterLineState | null {
  if (!isMeterProduct(line)) return null;
  if (!(line.quantity > 0)) return 'pending';
  if (line.meterInput === undefined) return 'confirmed';
  const parsed = parseQuantityInput(line.meterInput);
  return parsed.status === 'valid' && parsed.value === line.quantity ? 'confirmed' : 'unconfirmed';
}

/** Primera línea que impide finalizar: metros sin capturar o con cambios sin confirmar. */
export function firstIncompleteLine<T extends Pick<CartLine, 'unitCode' | 'quantity' | 'meterInput'>>(lines: T[]): T | undefined {
  return lines.find((line) => {
    const state = meterLineState(line);
    return state === 'pending' || state === 'unconfirmed';
  });
}

export function roundQuantity(value: number): number {
  return Math.round((value + Number.EPSILON) * SCALE) / SCALE;
}

/** Suma en centésimas enteras para evitar errores de coma flotante (0.1 + 0.2). */
export function addQuantity(value: number, delta: number): number {
  return (Math.round(value * SCALE) + Math.round(delta * SCALE)) / SCALE;
}

export function formatMeters(value: number): string {
  return roundQuantity(value).toFixed(QUANTITY_DECIMALS);
}

export function formatQuantity(value: number): string {
  return String(roundQuantity(value));
}

/** Entradas que se permiten mientras se escribe: '', '2', '2.', '2,7', '.5'. */
const PARTIAL_INPUT = /^\d*(?:[.,]\d*)?$/;
const COMPLETE_INPUT = /^(?:\d+(?:[.,]\d*)?|[.,]\d+)$/;

export type QuantityParseResult =
  | { status: 'incomplete' }
  | { status: 'valid'; value: number }
  | { status: 'invalid'; message: string };

export function parseQuantityInput(text: string): QuantityParseResult {
  const trimmed = text.trim();
  if (trimmed === '' || trimmed === '.' || trimmed === ',') return { status: 'incomplete' };
  if (trimmed.startsWith('-')) return { status: 'invalid', message: 'La cantidad no puede ser negativa' };
  if (!PARTIAL_INPUT.test(trimmed) || !COMPLETE_INPUT.test(trimmed)) {
    return { status: 'invalid', message: 'Escribe solo números, por ejemplo 2.75' };
  }

  const [, decimals = ''] = trimmed.replace(',', '.').split('.');
  if (decimals.length > QUANTITY_DECIMALS) {
    return { status: 'invalid', message: `Usa máximo ${QUANTITY_DECIMALS} decimales` };
  }

  const value = Number(trimmed.replace(',', '.'));
  if (!Number.isFinite(value)) return { status: 'invalid', message: 'Cantidad no válida' };
  // '0', '0.' o '0.2' pueden convertirse en 0.25: no se marcan como error mientras se escribe.
  if (value === 0 && decimals.length < QUANTITY_DECIMALS) return { status: 'incomplete' };
  if (value <= 0) return { status: 'invalid', message: `La cantidad mínima es ${formatMeters(MIN_DECIMAL_QUANTITY)}` };
  if (value > MAX_QUANTITY) return { status: 'invalid', message: 'La cantidad es demasiado grande' };

  return { status: 'valid', value: roundQuantity(value) };
}

/** Redondeo de importes que ya usa el pedido (subtotal = precio × cantidad a 2 decimales). */
export function roundMoney(value: number): number {
  return Math.round(value * 100) / 100;
}
