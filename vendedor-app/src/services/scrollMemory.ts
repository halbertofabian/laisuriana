import { useEffect, useLayoutEffect, useRef } from 'react';

const positions = new Map<string, number>();

/**
 * Al entrar a una pantalla restaura su desplazamiento anterior (o la deja arriba) y lo
 * registra mientras se usa. `ready` permite esperar a que el contenido ya esté pintado.
 * Con `remember` en false la pantalla siempre empieza arriba.
 */
export function useScrollMemory(key: string, { ready = true, remember = true } = {}) {
  const restored = useRef(false);

  useLayoutEffect(() => {
    if (restored.current || !ready) return;
    restored.current = true;
    window.scrollTo(0, remember ? positions.get(key) ?? 0 : 0);
  }, [key, ready, remember]);

  useEffect(() => {
    if (!remember) return;
    // Antes de restaurar, un scroll lo provoca el cambio de pantalla y no debe guardarse.
    const save = () => { if (restored.current) positions.set(key, window.scrollY); };
    window.addEventListener('scroll', save, { passive: true });
    return () => window.removeEventListener('scroll', save);
  }, [key, remember]);
}
