import { Capacitor, registerPlugin } from '@capacitor/core';

interface SecureStoragePlugin {
  set(options: { key: string; value: string }): Promise<void>;
  get(options: { key: string }): Promise<{ value: string | null }>;
  remove(options: { key: string }): Promise<void>;
}

const nativeStorage = registerPlugin<SecureStoragePlugin>('SecureSession');

export async function setSecureValue(key: string, value: string): Promise<void> {
  if (Capacitor.isNativePlatform()) {
    await nativeStorage.set({ key, value });
    return;
  }

  window.localStorage.setItem(key, value);
}

export async function getSecureValue(key: string): Promise<string | null> {
  if (Capacitor.isNativePlatform()) {
    try {
      const result = await nativeStorage.get({ key });
      return result.value;
    } catch {
      return null;
    }
  }

  return window.localStorage.getItem(key);
}

export async function removeSecureValue(key: string): Promise<void> {
  if (Capacitor.isNativePlatform()) {
    await nativeStorage.remove({ key });
    return;
  }

  window.localStorage.removeItem(key);
}
