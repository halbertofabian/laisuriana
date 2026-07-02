import { API_BASE_URL } from "@/shared/config/env";

const JSON_HEADERS = {
  Accept: "application/json",
} as const;

async function parseJson(response: Response) {
  return response.json().catch(() => ({}));
}

export async function apiRequest<T>(
  path: string,
  init?: RequestInit,
): Promise<T> {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    credentials: "include",
    ...init,
    headers: {
      ...JSON_HEADERS,
      ...(init?.headers || {}),
    },
  });

  const json = await parseJson(response);

  if (!response.ok) {
    const errors = (json as { errors?: Record<string, string[]> }).errors;
    const firstError = errors ? Object.values(errors)[0]?.[0] : null;
    throw new Error(
      typeof firstError === "string"
        ? firstError
        : typeof (json as { message?: string }).message === "string"
          ? (json as { message?: string }).message!
          : `Request failed (${response.status}).`,
    );
  }

  return json as T;
}

export function buildApiUrl(path: string) {
  return `${API_BASE_URL}${path}`;
}
