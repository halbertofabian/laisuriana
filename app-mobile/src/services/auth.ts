import { API_BASE_URL } from "../config/env";
import type {
  AlmacenOption,
  LoginRequest,
  Session,
  SucursalOption,
  UsuarioSugerido
} from "../types/auth";

const JSON_HEADERS = {
  Accept: "application/json",
  "X-Requested-With": "XMLHttpRequest"
} as const;

export async function login(payload: LoginRequest): Promise<Session> {
  const response = await fetch(`${API_BASE_URL}/mobile/login`, {
    method: "POST",
    headers: {
      ...JSON_HEADERS,
      "Content-Type": "application/json"
    },
    credentials: "include",
    body: JSON.stringify({
      usuario: payload.usuario,
      password: payload.password
    })
  });

  const data = await response.json().catch(() => ({}));

  if (!response.ok) {
    throw new Error(
      typeof data?.message === "string"
        ? data.message
        : `Error de autenticación (${response.status}).`
    );
  }

  if (!data?.ok) {
    throw new Error(data?.message || "No fue posible iniciar sesión.");
  }

  return {
    usuario: payload.usuario
  };
}

export async function buscarUsuarios(q: string): Promise<UsuarioSugerido[]> {
  const query = q.trim();
  if (query.length < 2) return [];

  const url = `${API_BASE_URL}/mobile/login/buscar-usuarios?q=${encodeURIComponent(query)}`;
  const response = await fetch(url, {
    method: "GET",
    headers: JSON_HEADERS
  });

  if (!response.ok) {
    throw new Error(`No se pudo consultar usuarios (${response.status}).`);
  }

  const data = await response.json().catch(() => ({}));
  return Array.isArray(data?.data) ? data.data : [];
}

export async function cargarSucursales(): Promise<SucursalOption[]> {
  const response = await fetch(`${API_BASE_URL}/mobile/sucursales`, {
    method: "GET",
    headers: JSON_HEADERS,
    credentials: "include"
  });

  if (!response.ok) {
    throw new Error(`No se pudieron cargar sucursales (${response.status}).`);
  }

  const data = await response.json().catch(() => ({}));
  return Array.isArray(data?.data) ? data.data : [];
}

export async function cargarAlmacenes(sucursalId: number): Promise<AlmacenOption[]> {
  const response = await fetch(
    `${API_BASE_URL}/mobile/almacenes?scl_id=${encodeURIComponent(String(sucursalId))}`,
    {
      method: "GET",
      headers: JSON_HEADERS,
      credentials: "include"
    }
  );

  if (!response.ok) {
    throw new Error(`No se pudieron cargar almacenes (${response.status}).`);
  }

  const data = await response.json().catch(() => ({}));
  return Array.isArray(data?.data) ? data.data : [];
}
