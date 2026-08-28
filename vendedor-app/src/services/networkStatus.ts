import { Network, type ConnectionStatus } from '@capacitor/network';

export type AppNetworkStatus = ConnectionStatus;

export function initialNetworkStatus(): AppNetworkStatus {
  return {
    connected: typeof navigator === 'undefined' ? true : navigator.onLine,
    connectionType: 'unknown',
  };
}

export async function observeNetworkStatus(
  listener: (status: AppNetworkStatus) => void,
): Promise<() => Promise<void>> {
  listener(await Network.getStatus());
  const handle = await Network.addListener('networkStatusChange', listener);
  return () => handle.remove();
}
