import { apiRequest } from "@/shared/api/http";
import type { Almacen, Sucursal } from "@/shared/types/catalogs";

export async function fetchSucursales(): Promise<Sucursal[]> {
  const response = await apiRequest<{ data?: Sucursal[] }>("/mobile/sucursales");
  return Array.isArray(response.data) ? response.data : [];
}

export async function fetchAlmacenes(sucursalId: number): Promise<Almacen[]> {
  const response = await apiRequest<{ data?: Almacen[] }>(
    `/mobile/almacenes?scl_id=${encodeURIComponent(String(sucursalId))}`,
  );
  return Array.isArray(response.data) ? response.data : [];
}
