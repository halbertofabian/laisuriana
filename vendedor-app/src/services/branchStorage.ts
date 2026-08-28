import type { AuthUser } from '../types';

function storageKey(userId: number): string {
  return `suriana.active-branch.${userId}`;
}

export function resolveActiveBranchId(user: AuthUser): number | null {
  let storedId = 0;
  try {
    storedId = Number(localStorage.getItem(storageKey(user.id)) ?? 0);
  } catch {
    storedId = 0;
  }

  if (user.sucursales.some((branch) => branch.id === storedId)) return storedId;
  return user.sucursales.find((branch) => branch.id === user.sucursal_predeterminada_id)?.id
    ?? user.sucursales[0]?.id
    ?? null;
}

export function persistActiveBranchId(userId: number, branchId: number): void {
  try {
    localStorage.setItem(storageKey(userId), String(branchId));
  } catch {
    // La selección sigue activa durante la sesión aunque el almacenamiento no esté disponible.
  }
}
