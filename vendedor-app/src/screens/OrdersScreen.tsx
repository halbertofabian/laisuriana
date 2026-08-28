import { ChevronRight, CirclePlus, Clock3, FilePenLine, LoaderCircle, RefreshCw, Search, SlidersHorizontal, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import { AppHeader } from '../components/AppHeader';
import { BottomSheet } from '../components/BottomSheet';
import { Button } from '../components/Button';
import { EmptyState, InlineNotice, SkeletonList } from '../components/Feedback';
import { SearchField } from '../components/SearchField';
import type { OrderDraft } from '../services/orderDraftStorage';
import type { Order } from '../types';

const money = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

function orderDate(order: Order): string {
  if (!order.createdAt) return order.time;
  const date = new Date(order.createdAt);
  if (Number.isNaN(date.getTime())) return order.time;
  if (date.toDateString() === new Date().toDateString()) return order.time;
  return date.toLocaleDateString('es-MX', { day: 'numeric', month: 'short' }).replace('.', '');
}

export function OrdersScreen({
  orders,
  loading,
  error,
  openingOrderId,
  branchName,
  draft,
  connectionState,
  onNewOrder,
  onResumeDraft,
  onDiscardDraft,
  onProfile,
  onOpenOrder,
  onRetry,
}: {
  orders: Order[];
  loading: boolean;
  error: string | null;
  openingOrderId: number | null;
  branchName: string;
  draft: OrderDraft | null;
  connectionState: 'synced' | 'syncing' | 'offline' | 'server-unavailable';
  onNewOrder: () => void;
  onResumeDraft: () => void;
  onDiscardDraft: () => void;
  onProfile: () => void;
  onOpenOrder: (order: Order) => void;
  onRetry: () => void;
}) {
  const [query, setQuery] = useState('');
  const [filter, setFilter] = useState<'pending' | 'all'>('pending');
  const [searchOpen, setSearchOpen] = useState(false);
  const [discardOpen, setDiscardOpen] = useState(false);

  const filtered = useMemo(() => orders.filter((order) => {
    const matchesFilter = filter === 'all' || order.status === 'pending';
    const normalized = query.toLowerCase();
    return matchesFilter && (`${order.folio} ${order.customer}`.toLowerCase().includes(normalized));
  }), [filter, orders, query]);
  const todayKey = new Date().toDateString();
  const todayCount = orders.filter((order) => order.createdAt && new Date(order.createdAt).toDateString() === todayKey).length;
  const draftItemCount = draft?.cart.reduce((sum, line) => sum + line.quantity, 0) ?? 0;
  const draftUpdatedAt = draft ? new Date(draft.updatedAt) : null;
  const draftTime = draftUpdatedAt && !Number.isNaN(draftUpdatedAt.getTime())
    ? draftUpdatedAt.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', hour12: false })
    : '';
  const connectionLabel = connectionState === 'offline'
    ? 'Sin conexión'
    : connectionState === 'server-unavailable'
      ? 'Servidor no disponible'
      : connectionState === 'syncing' ? 'Sincronizando' : 'Sincronizado';

  return (
    <main className="screen screen--with-action screen-enter">
      <AppHeader eyebrow={branchName} title="Pedidos" onProfile={onProfile} />
      <section className="screen-content orders-content">
        <div className="day-summary">
          <div>
            <span>Hoy</span>
            <strong>{todayCount} pedidos</strong>
          </div>
          <button className={`sync-state sync-state--${connectionState}`} onClick={onRetry} disabled={connectionState === 'syncing'} aria-label="Actualizar pedidos"><i /> {connectionLabel}</button>
        </div>

        {draft && (
          <div className="draft-card">
            <button className="draft-card__main" onClick={onResumeDraft}>
              <span className="draft-card__icon"><FilePenLine size={20} /></span>
              <span className="draft-card__body">
                <strong>{draft.editingOrder ? `Cambios en ${draft.editingOrder.folio}` : 'Pedido sin terminar'}</strong>
                <small>{draftItemCount} {draftItemCount === 1 ? 'artículo' : 'artículos'}{draftTime ? ` · Guardado ${draftTime}` : ''}</small>
              </span>
              <ChevronRight size={18} />
            </button>
            <button className="draft-card__discard" onClick={() => setDiscardOpen(true)} aria-label="Descartar borrador"><Trash2 size={17} /></button>
          </div>
        )}

        {connectionState === 'offline' && <InlineNotice type="offline">Trabajas sin conexión. Tu borrador permanece guardado en este teléfono.</InlineNotice>}

        <div className="section-toolbar">
          <div className="segmented-control" role="tablist" aria-label="Filtrar pedidos">
            <button className={filter === 'pending' ? 'active' : ''} onClick={() => setFilter('pending')}>Pendientes</button>
            <button className={filter === 'all' ? 'active' : ''} onClick={() => setFilter('all')}>Todos</button>
          </div>
          <button className="icon-button icon-button--soft" onClick={() => setSearchOpen(!searchOpen)} aria-label="Buscar pedidos">
            {searchOpen ? <SlidersHorizontal size={19} /> : <Search size={19} />}
          </button>
        </div>

        {searchOpen && (
          <div className="collapsible-field"><SearchField value={query} onChange={setQuery} placeholder="Folio o cliente" autoFocus /></div>
        )}

        {error && <InlineNotice type="warning">{error}</InlineNotice>}
        {loading && <SkeletonList />}
        {!loading && <div className="order-list">
          {filtered.map((order) => (
            <button className="order-row" key={order.id} disabled={openingOrderId !== null} onClick={() => onOpenOrder(order)}>
              <div className={`order-row__marker order-row__marker--${order.status}`}>
                <Clock3 size={19} />
              </div>
              <div className="order-row__body">
                <div className="order-row__line">
                  <strong>{order.folio}</strong>
                  <strong>{money.format(order.total)}</strong>
                </div>
                <p>{order.customer}</p>
                <div className="order-row__meta">
                  <span>{order.itemCount} {order.itemCount === 1 ? 'artículo' : 'artículos'}</span>
                  <i />
                  <span>{orderDate(order)}</span>
                  {order.status === 'paid' && <span className="status-text status-text--paid">Pagado</span>}
                  {order.status === 'cancelled' && <span className="status-text status-text--cancelled">Cancelado</span>}
                </div>
              </div>
              {openingOrderId === order.id ? <LoaderCircle size={18} className="ui-button__spinner" /> : <ChevronRight size={18} className="order-row__chevron" />}
            </button>
          ))}
        </div>}

        {!loading && filtered.length === 0 && (
          <EmptyState
            title="No encontramos pedidos"
            message="Prueba con otro folio o crea un pedido nuevo."
            action={error ? <Button variant="secondary" icon={<RefreshCw size={18} />} onClick={onRetry}>Reintentar</Button> : undefined}
          />
        )}
      </section>
      <div className="sticky-action">
        <Button full onClick={onNewOrder} icon={draft ? <FilePenLine size={20} /> : <CirclePlus size={21} />}>{draft ? 'Continuar pedido' : 'Nuevo pedido'}</Button>
      </div>


      <BottomSheet open={discardOpen} onClose={() => setDiscardOpen(false)} title="¿Descartar este borrador?" description="Se eliminarán los productos, el cliente y las notas guardadas en este teléfono.">
        <div className="sheet-actions">
          <Button full variant="danger" onClick={() => { setDiscardOpen(false); onDiscardDraft(); }}>Sí, descartar</Button>
          <Button full variant="quiet" onClick={() => setDiscardOpen(false)}>Conservar borrador</Button>
        </div>
      </BottomSheet>
    </main>
  );
}
