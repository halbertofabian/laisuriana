import type { FloorOrderGroup, FloorOrderLine } from "@/features/floor-order/types";

export function sanitizeQuantity(value: number, allowsDecimals: boolean) {
  if (!Number.isFinite(value) || value <= 0) return allowsDecimals ? 0.01 : 1;
  return allowsDecimals ? Number(value.toFixed(2)) : Math.max(1, Math.round(value));
}

export function groupLinesByWarehouse(lines: FloorOrderLine[]): FloorOrderGroup[] {
  const map = new Map<number, FloorOrderGroup>();

  lines.forEach((line) => {
    const current = map.get(line.pdp_alm_id) || {
      pdp_alm_id: line.pdp_alm_id,
      almacen: line.almacen,
      items: [],
    };
    current.items.push(line);
    map.set(line.pdp_alm_id, current);
  });

  return Array.from(map.values());
}

export function calculateLineSubtotal(line: FloorOrderLine) {
  const subtotal = line.cantidad * line.precio;
  if (line.descuento_tipo === "porcentaje") {
    return subtotal - subtotal * (line.descuento_valor / 100);
  }
  if (line.descuento_tipo === "importe") {
    return Math.max(0, subtotal - line.descuento_valor);
  }
  return subtotal;
}

export function calculateDraftTotal(lines: FloorOrderLine[]) {
  return lines.reduce((total, line) => total + calculateLineSubtotal(line), 0);
}
