import { API_BASE_URL } from "../config/env";
import type { PartidaPedido, PedidoRow, ProductoSugerencia } from "../types/pedidos";

const JSON_HEADERS = {
  Accept: "application/json",
  "X-Requested-With": "XMLHttpRequest"
} as const;

export async function buscarProductosPedido(q: string): Promise<ProductoSugerencia[]> {
  const query = q.trim();
  if (query.length < 2) return [];

  const response = await fetch(
    `${API_BASE_URL}/mobile/pedidos-piso/productos/buscar?q=${encodeURIComponent(query)}`,
    {
      method: "GET",
      headers: JSON_HEADERS,
      credentials: "include"
    }
  );

  if (!response.ok) return [];
  const data = await response.json().catch(() => ({}));
  return Array.isArray(data?.data) ? data.data : [];
}

export async function listarPedidosPiso(params?: {
  buscar?: string;
  pdp_estatus?: string;
  pdp_scl_id?: number;
}): Promise<PedidoRow[]> {
  const buscar = params?.buscar || "";
  const estatus = params?.pdp_estatus || "";
  const scl = params?.pdp_scl_id ? String(params.pdp_scl_id) : "";
  const response = await fetch(
    `${API_BASE_URL}/mobile/pedidos-piso/data?buscar=${encodeURIComponent(buscar)}&pdp_estatus=${encodeURIComponent(estatus)}&pdp_scl_id=${encodeURIComponent(scl)}`,
    {
      method: "GET",
      headers: JSON_HEADERS,
      credentials: "include"
    }
  );

  if (!response.ok) {
    throw new Error(`No se pudieron cargar pedidos (${response.status}).`);
  }
  const data = await response.json().catch(() => ({}));
  return Array.isArray(data?.data) ? data.data : [];
}

export async function guardarPedidoPiso(payload: {
  pdp_scl_id: number;
  pdp_alm_id: number;
  pdp_observaciones?: string;
  partidas: PartidaPedido[];
}): Promise<{ pdp_id: number; pdp_folio: string }> {
  const response = await fetch(`${API_BASE_URL}/mobile/pedidos-piso`, {
    method: "POST",
    headers: {
      ...JSON_HEADERS,
      "Content-Type": "application/json"
    },
    credentials: "include",
    body: JSON.stringify({
      pdp_scl_id: payload.pdp_scl_id,
      pdp_alm_id: payload.pdp_alm_id,
      pdp_observaciones: payload.pdp_observaciones || "",
      partidas: payload.partidas.map((p) => ({
        ppd_psk_id: p.ppd_psk_id,
        ppd_cantidad: p.cantidad
      }))
    })
  });

  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    const errorsObj = (data as { errors?: Record<string, string[]> })?.errors;
    const first = errorsObj ? Object.values(errorsObj)?.[0] : null;
    const message =
      typeof first?.[0] === "string"
        ? first[0]
        : data?.message || `No se pudo guardar pedido (${response.status}).`;
    throw new Error(message);
  }

  return {
    pdp_id: Number(data?.data?.pdp_id),
    pdp_folio: String(data?.data?.pdp_folio || "")
  };
}
