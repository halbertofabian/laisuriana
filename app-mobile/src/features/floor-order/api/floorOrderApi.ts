import { apiRequest, buildApiUrl } from "@/shared/api/http";
import type {
  FloorOrderDraft,
  FloorOrderLine,
  PedidoRow,
  ProductSuggestion,
  WarehouseResolution,
} from "@/features/floor-order/types";

type CreateOrderPayload = {
  pdp_scl_id: number;
  pdp_alm_id: number;
  pdp_cli_id?: number | null;
  pdp_observaciones?: string;
  partidas: FloorOrderLine[];
};

function serializeLine(line: FloorOrderLine) {
  return {
    ppd_psk_id: line.ppd_psk_id,
    ppd_cantidad: line.cantidad,
    ppd_descuento_tipo: line.descuento_tipo,
    ppd_descuento_valor: line.descuento_valor,
    ppd_descuento_cantidad:
      line.descuento_tipo === "ninguno" ? undefined : line.descuento_cantidad ?? line.cantidad,
    ppd_usr_id: line.ppd_usr_id,
  };
}

export async function searchProducts(query: string): Promise<ProductSuggestion[]> {
  const q = query.trim();
  if (q.length < 2) return [];

  const response = await apiRequest<{ data?: ProductSuggestion[] }>(
    `/mobile/pedidos-piso/productos/buscar?q=${encodeURIComponent(q)}`,
  );

  return Array.isArray(response.data) ? response.data : [];
}

export async function resolveProductWarehouse(
  skuId: number,
  sucursalId: number,
): Promise<WarehouseResolution> {
  const response = await apiRequest<{ data: WarehouseResolution }>(
    `/operacion/pedidos-piso/productos/resolver?psk_id=${skuId}&pdp_scl_id=${sucursalId}`,
  );

  return response.data;
}

export async function listPendingOrders(params?: {
  buscar?: string;
  pdp_scl_id?: number;
}): Promise<PedidoRow[]> {
  const buscar = params?.buscar || "";
  const sclId = params?.pdp_scl_id ? String(params.pdp_scl_id) : "";
  const response = await apiRequest<{ data?: PedidoRow[] }>(
    `/mobile/pedidos-piso/data?buscar=${encodeURIComponent(buscar)}&pdp_scl_id=${encodeURIComponent(sclId)}`,
  );

  return Array.isArray(response.data) ? response.data : [];
}

export async function createFloorOrder(
  payload: CreateOrderPayload,
): Promise<{ pdp_id: number; pdp_folio: string }> {
  const response = await apiRequest<{ data: { pdp_id: number; pdp_folio: string } }>(
    "/mobile/pedidos-piso",
    {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        pdp_scl_id: payload.pdp_scl_id,
        pdp_alm_id: payload.pdp_alm_id,
        pdp_cli_id: payload.pdp_cli_id ?? null,
        pdp_observaciones: payload.pdp_observaciones || "",
        partidas: payload.partidas.map(serializeLine),
      }),
    },
  );

  return response.data;
}

export async function createOrdersFromDraft(
  sucursalId: number,
  draft: FloorOrderDraft,
): Promise<{ orderIds: number[]; folios: string[] }> {
  const grouped = new Map<number, FloorOrderLine[]>();

  draft.lines.forEach((line) => {
    const current = grouped.get(line.pdp_alm_id) || [];
    current.push(line);
    grouped.set(line.pdp_alm_id, current);
  });

  const orderIds: number[] = [];
  const folios: string[] = [];

  for (const [almacenId, lines] of grouped.entries()) {
    const result = await createFloorOrder({
      pdp_scl_id: sucursalId,
      pdp_alm_id: almacenId,
      pdp_cli_id: draft.client?.cli_id ?? null,
      pdp_observaciones: draft.observaciones,
      partidas: lines,
    });

    orderIds.push(result.pdp_id);
    folios.push(result.pdp_folio);
  }

  return { orderIds, folios };
}

export function getTicketUrl(orderId: number) {
  return buildApiUrl(`/operacion/pedidos-piso/${orderId}/ticket`);
}
