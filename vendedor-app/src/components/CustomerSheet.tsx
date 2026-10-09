import { Check } from 'lucide-react';
import { useEffect, useState } from 'react';
import { ApiError, orderCatalogApi } from '../services/api';
import type { Customer } from '../types';
import { BottomSheet } from './BottomSheet';
import { InlineNotice, SkeletonList } from './Feedback';
import { SearchField } from './SearchField';

export const PUBLIC_CUSTOMER: Customer = {
  id: null,
  name: 'Público general',
  detail: 'Sin cliente registrado',
  initials: 'PG',
};

/** Selector de cliente compartido por el catálogo y la revisión del pedido. */
export function CustomerSheet({
  open,
  customer,
  online,
  onClose,
  onSelect,
}: {
  open: boolean;
  customer: Customer;
  online: boolean;
  onClose: () => void;
  onSelect: (customer: Customer) => void;
}) {
  const [query, setQuery] = useState('');
  const [clients, setClients] = useState<Customer[]>([]);
  const [searching, setSearching] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!online || !open || query.trim().length < 2) {
      setClients([]);
      setSearching(false);
      setError(null);
      return;
    }

    const controller = new AbortController();
    const timeout = window.setTimeout(async () => {
      setSearching(true);
      try {
        setClients(await orderCatalogApi.searchClients(query.trim(), controller.signal));
        setError(null);
      } catch (requestError) {
        if (!controller.signal.aborted) {
          setClients([]);
          setError(requestError instanceof ApiError ? requestError.message : 'No pudimos buscar clientes.');
        }
      } finally {
        if (!controller.signal.aborted) setSearching(false);
      }
    }, 260);

    return () => {
      window.clearTimeout(timeout);
      controller.abort();
    };
  }, [online, open, query]);

  const choose = (selected: Customer) => {
    onSelect(selected);
    setQuery('');
    onClose();
  };

  const option = (item: Customer) => {
    const selected = item.id === customer.id;
    return (
      <button key={item.id ?? 'public'} className={selected ? 'selected' : ''} onClick={() => choose(item)} aria-pressed={selected}>
        <span className="initials">{item.initials}</span>
        <span><strong>{item.name}</strong><small>{item.detail}</small></span>
        {selected ? <Check size={18} aria-label="Seleccionado" /> : <span />}
      </button>
    );
  };

  return (
    <BottomSheet open={open} onClose={onClose} title="Cliente" description="Opcional. Si no eliges uno, el pedido queda a Público general.">
      <SearchField value={query} onChange={setQuery} placeholder="Nombre, teléfono o RFC" autoFocus={online} />
      {!online && <InlineNotice type="offline">Sin conexión: la búsqueda de clientes está pausada.</InlineNotice>}
      {error && <InlineNotice type="warning">{error}</InlineNotice>}
      <div className="selection-list selection-list--scroll">
        {option(PUBLIC_CUSTOMER)}
        {searching && <SkeletonList />}
        {!searching && clients.map(option)}
      </div>
      {query.trim().length >= 2 && !searching && !error && clients.length === 0 && (
        <p className="sheet-empty-copy">Sin clientes para «{query.trim()}». Revisa el nombre o busca por teléfono.</p>
      )}
    </BottomSheet>
  );
}
