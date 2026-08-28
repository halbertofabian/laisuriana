import type { AuthUser } from '../types';
import { getSecureValue, removeSecureValue, setSecureValue } from './secureStorage';

const TOKEN_KEY = 'auth_token';
const USER_KEY = 'auth_user';

export async function setAuthToken(token: string): Promise<void> {
  await setSecureValue(TOKEN_KEY, token);
}

export async function getAuthToken(): Promise<string | null> {
  return getSecureValue(TOKEN_KEY);
}

export async function clearAuthToken(): Promise<void> {
  await removeSecureValue(TOKEN_KEY);
}

export async function setCachedAuthUser(user: AuthUser): Promise<void> {
  await setSecureValue(USER_KEY, JSON.stringify(user));
}

export async function getCachedAuthUser(): Promise<AuthUser | null> {
  const stored = await getSecureValue(USER_KEY);
  if (!stored) return null;

  try {
    const user = JSON.parse(stored) as AuthUser;
    return Number.isInteger(user.id) && Array.isArray(user.sucursales) ? user : null;
  } catch {
    await removeSecureValue(USER_KEY);
    return null;
  }
}

export async function clearCachedAuthUser(): Promise<void> {
  await removeSecureValue(USER_KEY);
}
