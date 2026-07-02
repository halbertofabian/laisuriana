import { apiRequest } from "@/shared/api/http";
import type { Client } from "@/shared/types/clients";

export async function searchClients(query: string): Promise<Client[]> {
  const q = query.trim();
  if (q.length < 2) return [];

  const response = await apiRequest<{ data?: Client[] }>(
    `/pos/clientes/buscar?q=${encodeURIComponent(q)}`,
  );

  return Array.isArray(response.data) ? response.data : [];
}
