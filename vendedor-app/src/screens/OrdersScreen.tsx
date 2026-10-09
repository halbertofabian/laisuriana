import { ChevronRight, CirclePlus, FilePenLine, LoaderCircle, RefreshCw, Search, Trash2, X } from 'lucide-react';
import { useMemo, useState } from 'react';
import { AppHeader } from '../components/AppHeader';
import { BottomSheet } from '../components/BottomSheet';
import { Button } from '../components/Button';
import { EmptyState, InlineNotice, SkeletonList } from '../components/Feedback';
import { SearchField } from '../components/SearchField';
import type { OrderDraft } from '../services/orderDraftStorage';
import { countItems, meterLineState } from '../services/quantity';
import { useScrollMemory } from '../services/scrollMemory';
import type { CommissionProgress, Order, OrderStatus } from '../types';

const money = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });
const statusLabel: Record<OrderStatus, string> = { pending: 'Pendiente', paid: 'Pagado', cancelled: 'Cancelado' };

/** Filtro y búsqueda de la lista; viven en App para conservarlos al abrir un pedido y volver. */
export interface OrdersView {
  filter: 'pending' | 'all';
  query: string;
  searchOpen: boolean;
}

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
  commissionProgress,
  draft,
  connectionState,
  view,
  onView,
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
  commissionProgress: CommissionProgress | null;
  draft: OrderDraft | null;
  connectionState: 'synced' | 'syncing' | 'offline' | 'server-unavailable';
  view: OrdersView;
  onView: (view: OrdersView) => void;
  onNewOrder: () => void;
  onResumeDraft: () => void;
  onDiscardDraft: () => void;
  onProfile: () => void;
  onOpenOrder: (order: Order) => void;
  onRetry: () => void;
}) {
  const [discardOpen, setDiscardOpen] = useState(false);
  const { filter, query, searchOpen } = view;
  useScrollMemory('orders', { ready: !loading });

  const filtered = useMemo(() => orders.filter((order) => {
    const matchesFilter = filter === 'all' || order.status === 'pending';
    const normalized = query.trim().toLowerCase();
    return matchesFilter && (`${order.folio} ${order.customer}`.toLowerCase().includes(normalized));
  }), [filter, orders, query]);
  const todayKey = new Date().toDateString();
  const todayCount = orders.filter((order) => order.createdAt && new Date(order.createdAt).toDateString() === todayKey).length;
  const draftItemCount = countItems(draft?.cart ?? []);
  const draftPendingMeters = draft?.cart.filter((line) => {
    const state = meterLineState(line);
    return state === 'pending' || state === 'unconfirmed';
  }).length ?? 0;
  const draftUpdatedAt = draft ? new Date(draft.updatedAt) : null;
  const draftTime = draftUpdatedAt && !Number.isNaN(draftUpdatedAt.getTime())
    ? draftUpdatedAt.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', hour12: false })
    : '';
  const connectionLabel = connectionState === 'offline'
    ? 'Sin conexión'
    : connectionState === 'server-unavailable'
      ? 'Servidor no disponible'
      : connectionState === 'syncing' ? 'Actualizando…' : 'Actualizado';
  const progress = commissionProgress?.porcentaje ?? null;
  const searching = query.trim() !== '';

  const toggleSearch = () => onView(searchOpen ? { ...view, searchOpen: false, query: '' } : { ...view, searchOpen: true });

  return (
    <main className="screen screen--with-action screen-enter">
      <AppHeader eyebrow={branchName} title="Pedidos" onProfile={onProfile} />
      <section className="screen-content orders-content">
        <section className="day-summary" aria-label="Resumen del día">
          <div className="day-summary__row">
            <span><strong>{todayCount}</strong> {todayCount === 1 ? 'pedido hoy' : 'pedidos hoy'}</span>
            <button className={`sync-state sync-state--${connectionState}`} onClick={onRetry} disabled={connectionState === 'syncing'} aria-label={`${connectionLabel}. Actualizar pedidos`}>
              <i aria-hidden="true" />{connectionLabel}<RefreshCw size={14} className={connectionState === 'syncing' ? 'spin' : ''} aria-hidden="true" />
            </button>
          </div>
          <div className="day-summary__goal">
            <span>Meta del mes</span>
            <div className="day-summary__track" role="progressbar" aria-valuenow={progress ?? undefined} aria-valuemin={0} aria-valuemax={100} aria-label="Avance de la meta del mes">
              <span style={{ width: `${Math.min(100, progress ?? 0)}%` }} />
            </div>
            <strong>{progress == null ? '—' : `${progress.toFixed(1)}%`}</strong>
          </div>
          {commissionProgress?.mensaje && <p>{commissionProgress.mensaje}</p>}
        </section>

        {draft && (
          <div className="draft-card">
            <button className="draft-card__main" onClick={onResumeDraft}>
              <span className="draft-card__icon"><FilePenLine size={19} /></span>
              <span className="draft-card__body">
                <strong>{draft.editingOrder ? `Cambios sin guardar en ${draft.editingOrder.folio}` : 'Pedido sin terminar'}</strong>
                <small>
                  {draftItemCount} {draftItemCount === 1 ? 'artículo' : 'artículos'}
                  {draftPendingMeters > 0 ? ` · ${draftPendingMeters} sin metros` : ''}
                  {draftTime ? ` · ${draftTime}` : ''}
                </small>
              </span>
              <ChevronRight size={18} aria-hidden="true" />
            </button>
            <button className="draft-card__discard" onClick={() => setDiscardOpen(true)} aria-label="Descartar pedido sin terminar"><Trash2 size={17} /></button>
          </div>
        )}

        {connectionState === 'offline' && <InlineNotice type="offline">Sin conexión. Lo que captures se guarda en este teléfono.</InlineNotice>}

        <div className="section-toolbar">
          <div className="segmented-control" role="group" aria-label="Filtrar pedidos">
            <button className={filter === 'pending' ? 'active' : ''} aria-pressed={filter === 'pending'} onClick={() => onView({ ...view, filter: 'pending' })}>Pendientes</button>
            <button className={filter === 'all' ? 'active' : ''} aria-pressed={filter === 'all'} onClick={() => onView({ ...view, filter: 'all' })}>Todos</button>
          </div>
          <button className="icon-button icon-button--soft" onClick={toggleSearch} aria-label={searchOpen ? 'Cerrar búsqueda' : 'Buscar por folio o cliente'} aria-expanded={searchOpen}>
            {searchOpen ? <X size={19} /> : <Search size={19} />}
          </button>
        </div>

        {searchOpen && (
          <div className="collapsible-field"><SearchField value={query} onChange={(value) => onView({ ...view, query: value })} placeholder="Folio o cliente" autoFocus={!query} /></div>
        )}

        {error && (
          <div className="notice-with-action">
            <InlineNotice type="warning">{error}</InlineNotice>
            <Button variant="secondary" icon={<RefreshCw size={17} />} onClick={onRetry}>Reintentar</Button>
          </div>
        )}
        {loading && orders.length === 0 && <SkeletonList />}
        {!(loading && orders.length === 0) && filtered.length > 0 && (
          <div className="order-list">
            {filtered.map((order) => (
              <button className="order-row" key={order.id} disabled={openingOrderId !== null} onClick={() => onOpenOrder(order)}>
                <div className="order-row__body">
                  <div className="order-row__line">
                    <strong>{order.folio}</strong>
                    <strong>{money.format(order.total)}</strong>
                  </div>
                  <div className="order-row__line order-row__line--meta">
                    <span className="order-row__customer">{order.customer}</span>
                    {filter === 'all' && <span className={`status-pill status-pill--${order.status}`}>{statusLabel[order.status]}</span>}
                  </div>
                  <div className="order-row__meta">{orderDate(order)} · {order.warehouse}</div>
                </div>
                {openingOrderId === order.id
                  ? <LoaderCircle size={18} className="spin" aria-label="Abriendo" />
                  : <ChevronRight size={18} className="order-row__chevron" aria-hidden="true" />}
              </button>
            ))}
          </div>
        )}

        {!loading && !error && filtered.length === 0 && (
          searching ? (
            <EmptyState title="Sin resultados" message={`Ningún pedido coincide con «${query.trim()}». Revisa el folio o el nombre del cliente.`} />
          ) : filter === 'pending' && orders.length > 0 ? (
            <EmptyState
              title="Sin pedidos pendientes"
              message="Todos tus pedidos ya se cobraron o cancelaron."
              action={<Button variant="secondary" onClick={() => onView({ ...view, filter: 'all' })}>Ver todos</Button>}
            />
          ) : (
            <EmptyState title="Aún no hay pedidos" message="Los pedidos que generes aparecerán aquí." />
          )
        )}
      </section>
      <div className="sticky-action">
        <Button full onClick={onNewOrder} icon={draft ? <FilePenLine size={20} /> : <CirclePlus size={21} />}>{draft ? 'Continuar pedido' : 'Nuevo pedido'}</Button>
      </div>

      <BottomSheet open={discardOpen} onClose={() => setDiscardOpen(false)} title="¿Descartar el pedido sin terminar?" description="Se borrarán los productos, el cliente y la nota guardados en este teléfono.">
        <div className="sheet-actions">
          <Button full variant="danger" onClick={() => { setDiscardOpen(false); onDiscardDraft(); }}>Descartar</Button>
          <Button full variant="quiet" onClick={() => setDiscardOpen(false)}>Conservar</Button>
        </div>
      </BottomSheet>
    </main>
  );
}
