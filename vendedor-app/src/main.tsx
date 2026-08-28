import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { Capacitor } from '@capacitor/core';
import '@fontsource-variable/manrope';
import './styles/tokens.css';
import './styles/global.css';
import App from './App';

async function configureServiceWorker() {
  if (Capacitor.isNativePlatform()) {
    if ('serviceWorker' in navigator) {
      const registrations = await navigator.serviceWorker.getRegistrations();
      await Promise.all(registrations.map((registration) => registration.unregister()));
    }

    if ('caches' in window) {
      const cacheNames = await caches.keys();
      await Promise.all(cacheNames.map((cacheName) => caches.delete(cacheName)));
    }

    return;
  }

  const { registerSW } = await import('virtual:pwa-register');
  registerSW({ immediate: true });
}

void configureServiceWorker();

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <App />
  </StrictMode>,
);
