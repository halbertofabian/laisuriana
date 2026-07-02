import { apiRequest } from "@/shared/api/http";
import type { LoginRequest, Session, UsuarioSugerido } from "@/shared/types/auth";

export async function loginRequest(payload: LoginRequest): Promise<Session> {
  await apiRequest<{ ok: boolean; usuario: string }>("/mobile/login", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });

  return { usuario: payload.usuario };
}

export async function searchUsers(query: string): Promise<UsuarioSugerido[]> {
  const q = query.trim();
  if (q.length < 2) return [];

  const response = await apiRequest<{ data?: UsuarioSugerido[] }>(
    `/mobile/login/buscar-usuarios?q=${encodeURIComponent(q)}`,
  );

  return Array.isArray(response.data) ? response.data : [];
}
