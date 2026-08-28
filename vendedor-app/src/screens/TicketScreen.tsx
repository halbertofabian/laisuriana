import { Bluetooth, Check, ChevronRight, CircleX, Copy, Pencil, Printer, ReceiptText, Share2, Trash2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { AppHeader } from '../components/AppHeader';
import { BottomSheet } from '../components/BottomSheet';
import { Button } from '../components/Button';
import { Code128Barcode } from '../components/Code128Barcode';
import { InlineNotice } from '../components/Feedback';
import { ApiError, floorOrderApi } from '../services/api';
import { bluetoothPrinter, printerErrorMessage, printerProfileNames } from '../services/bluetoothPrinter';
import type { OrderDetail, PrinterConfig } from '../types';

const money = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

function orderDate(order: OrderDetail): string {
  if (!order.createdAt) return `Hoy, ${order.time}`;
  const date = new Date(order.createdAt);
  if (Number.isNaN(date.getTime()) || date.toDateString() === new Date().toDateString()) return `Hoy, ${order.time}`;
  return `${date.toLocaleDateString('es-MX', { day: 'numeric', month: 'short', year: 'numeric' }).replace('.', '')}, ${order.time}`;
}

export function TicketScreen({
  orders,
  mode,
  canEdit,
  canCancel,
  printerConfig,
  onCancel,
  onEdit,
  onConfigurePrinter,
  onDone,
}: {
  orders: OrderDetail[];
  mode: 'generated' | 'detail' | 'updated';
  canEdit: boolean;
  canCancel: boolean;
  printerConfig: PrinterConfig | null;
  onCancel: (orderId: number) => Promise<void>;
  onEdit: (order: OrderDetail) => void;
  onConfigurePrinter: () => void;
  onDone: () => void;
}) {
  const [activeIndex, setActiveIndex] = useState(0);
  const [printerSheet, setPrinterSheet] = useState(false);
  const [printedIds, setPrintedIds] = useState<number[]>([]);
  const [printing, setPrinting] = useState(false);
  const [printError, setPrintError] = useState<string | null>(null);
  const [shared, setShared] = useState(false);
  const [cancelSheet, setCancelSheet] = useState(false);
  const [cancelling, setCancelling] = useState(false);
  const [cancelError, setCancelError] = useState<string | null>(null);
  const [actionsSheet, setActionsSheet] = useState(false);
  const automaticPrintStarted = useRef(false);
  const order = orders[Math.min(activeIndex, orders.length - 1)];
  const pending = order.status === 'pending';
  const printed = printedIds.includes(order.id);
  const itemLabel = `${order.itemCount} ${order.itemCount === 1 ? 'artículo' : 'artículos'}`;

  const printOrders = async (ordersToPrint: OrderDetail[], closeSheetAfter = true, verifyStatus = true) => {
    if (!printerConfig) return;
    setPrinting(true);
    setPrintError(null);
    try {
      const printableOrders = verifyStatus
        ? await Promise.all(ordersToPrint.map((ticketOrder) => floorOrderApi.show(ticketOrder.id)))
        : ordersToPrint;
      if (printableOrders.some((ticketOrder) => ticketOrder.status !== 'pending')) {
        throw new ApiError(422, 'El pedido ya no está pendiente y no puede imprimirse para cobro.');
      }

      for (const ticketOrder of printableOrders) {
        await bluetoothPrinter.printTicket(printerConfig, { order: ticketOrder });
        setPrintedIds((current) => current.includes(ticketOrder.id) ? current : [...current, ticketOrder.id]);
      }
      if (closeSheetAfter) window.setTimeout(() => setPrinterSheet(false), 850);
    } catch (error) {
      setPrintError(error instanceof ApiError ? error.message : printerErrorMessage(error));
    } finally {
      setPrinting(false);
    }
  };

  useEffect(() => {
    if (mode !== 'generated' || !printerConfig || automaticPrintStarted.current) return;
    automaticPrintStarted.current = true;
    void printOrders(orders, false, false);
  }, [mode, printerConfig, orders]);

  const share = async () => {
    const text = `Pedido ${order.folio} · ${money.format(order.total)} · Presentar en caja.`;
    try {
      if (navigator.share) {
        await navigator.share({ title: `Pedido ${order.folio}`, text });
      } else {
        await navigator.clipboard.writeText(text);
      }
      setShared(true);
      window.setTimeout(() => setShared(false), 1800);
    } catch {
      // El usuario puede cerrar el diálogo de compartir sin que sea un error.
    }
  };

  const cancel = async () => {
    setCancelling(true);
    setCancelError(null);
    try {
      await onCancel(order.id);
    } catch (error) {
      if (error instanceof ApiError) {
        const firstError = Object.values(error.errors)[0]?.[0];
        setCancelError(firstError ?? error.message);
      } else {
        setCancelError('No pudimos cancelar el pedido.');
      }
    } finally {
      setCancelling(false);
    }
  };

  const shareFromActions = async () => {
    setActionsSheet(false);
    await share();
  };

  const headerTitle = order.status === 'paid'
    ? 'Pedido cobrado'
    : order.status === 'cancelled'
      ? 'Pedido cancelado'
      : mode === 'detail'
        ? 'Detalle del pedido'
        : mode === 'updated' ? 'Pedido actualizado' : (orders.length > 1 ? 'Pedidos generados' : 'Pedido generado');
  const mainTitle = order.status === 'paid'
    ? 'Pago confirmado'
    : order.status === 'cancelled'
      ? 'Pedido cancelado'
      : mode === 'updated' ? 'Cambios guardados' : 'Listo para pagar';
  const mainDescription = order.status === 'paid'
    ? 'Este pedido ya fue cobrado en caja.'
    : order.status === 'cancelled'
      ? 'Este folio ya no está disponible para cobro.'
      : mode === 'updated'
        ? 'El folio se conserva y ya contiene la información corregida.'
        : orders.length > 1
          ? `Se generaron ${orders.length} tickets, uno por almacén.`
          : 'Entrega este ticket al cliente para que pase a caja.';

  return (
    <main className="screen screen--with-action screen-enter">
      <AppHeader title={headerTitle} onMore={pending ? () => setActionsSheet(true) : undefined} />
      <section className="screen-content ticket-content">
        <div className={order.status === 'cancelled' ? 'cancelled-mark' : order.status === 'paid' || mode !== 'detail' ? 'success-mark' : 'detail-mark'}>{order.status === 'cancelled' ? <CircleX size={31} /> : order.status === 'paid' || mode !== 'detail' ? <Check size={31} strokeWidth={2.6} /> : <ReceiptText size={29} />}</div>
        <h2>{mainTitle}</h2>
        <p>{mainDescription}</p>

        {orders.length > 1 && (
          <div className="ticket-switcher" aria-label="Tickets generados">
            {orders.map((item, index) => (
              <button key={item.id} className={index === activeIndex ? 'active' : ''} onClick={() => { setActiveIndex(index); setPrinterSheet(false); setActionsSheet(false); }}>
                <span>{index + 1}</span>
                <div><strong>{item.folio}</strong><small>{item.warehouse}</small></div>
                {printedIds.includes(item.id) ? <Check size={17} /> : <ChevronRight size={17} />}
              </button>
            ))}
          </div>
        )}

        <article className={`ticket-card${pending ? '' : ' ticket-card--inactive'}`}>
          <div className="ticket-card__top">
            <span>FOLIO DE PEDIDO</span>
            <strong>{order.folio}</strong>
            <small>{orderDate(order)}</small>
            {!pending && <em className={`ticket-status ticket-status--${order.status}`}>{order.status === 'paid' ? 'PAGADO' : 'CANCELADO'}</em>}
          </div>
          <Code128Barcode value={order.folio} />
          <div className="ticket-card__details">
            <div><span>Cliente</span><strong>{order.customer}</strong></div>
            <div><span>Almacén</span><strong>{order.warehouse}</strong></div>
            <div><span>Productos</span><strong>{itemLabel}</strong></div>
          </div>
          <div className="ticket-card__total"><span>Total</span><strong>{money.format(order.total)}</strong></div>
        </article>

        {pending && printedIds.length > 0 && <div className="print-success"><Check size={17} /> {printedIds.length === orders.length && orders.length > 1 ? `${orders.length} tickets enviados a la impresora` : 'Ticket enviado a la impresora'}</div>}
        {printError && <InlineNotice type="warning">{printError} El pedido sigue guardado y puedes reintentar.</InlineNotice>}
        {pending && shared && <div className="print-success"><Copy size={17} /> Folio compartido</div>}
        {pending ? (
          <div className="ticket-actions">
            <Button full loading={printing} onClick={() => setPrinterSheet(true)} icon={<Printer size={20} />}>{orders.length > 1 ? `Imprimir ${orders.length} tickets` : printed ? 'Reimprimir ticket' : 'Imprimir ticket'}</Button>
            {canEdit && orders.length === 1 && <Button full variant="secondary" onClick={() => onEdit(order)} icon={<Pencil size={18} />}>Editar pedido</Button>}
          </div>
        ) : (
          <InlineNotice type={order.status === 'paid' ? 'success' : 'warning'}>{order.status === 'paid' ? 'No es necesario volver a presentar este folio en caja.' : 'La impresión y el uso de este folio están deshabilitados.'}</InlineNotice>
        )}
      </section>
      <div className="sticky-action sticky-action--plain">
        <Button full variant="quiet" onClick={onDone}>Volver a pedidos</Button>
      </div>

      <BottomSheet open={printerSheet} onClose={() => setPrinterSheet(false)} title={orders.length > 1 ? 'Imprimir tickets' : 'Imprimir ticket'} description={orders.length > 1 ? `Enviar ${orders.length} tickets a la impresora.` : `Enviar ${order.folio} a la impresora.`}>
        {printerConfig ? (
          <button className="printer-option" onClick={onConfigurePrinter}>
            <span className="printer-option__icon"><Printer size={22} /></span>
            <span><strong>{printerConfig.name}</strong><small><Bluetooth size={14} /> {printerProfileNames[printerConfig.language]} · {printerConfig.paperWidth} mm</small></span>
            <span className="radio-selected"><i /></span>
          </button>
        ) : (
          <div className="printer-sheet-empty"><span><Bluetooth size={22} /></span><strong>Sin impresora configurada</strong><p>Elige una impresora antes de imprimir.</p></div>
        )}
        {printError && <div className="inline-notice inline-notice--error" role="alert">{printError}</div>}
        <div className="sheet-actions">
          {printerConfig && <Button full onClick={() => void printOrders(orders.length > 1 ? orders : [order])} loading={printing}>{orders.length > 1 ? 'Imprimir todos' : 'Imprimir ahora'}</Button>}
          <Button full variant="secondary" onClick={onConfigurePrinter}>{printerConfig ? 'Cambiar impresora' : 'Configurar impresora'}</Button>
        </div>
      </BottomSheet>

      <BottomSheet open={actionsSheet} onClose={() => setActionsSheet(false)} title="Más acciones" description={order.folio}>
        <div className="sheet-actions">
          {pending && <Button full variant="secondary" onClick={() => void shareFromActions()} icon={<Share2 size={19} />}>Compartir folio</Button>}
          {canCancel && order.status === 'pending' && <Button full variant="danger" onClick={() => { setActionsSheet(false); setCancelSheet(true); }} icon={<Trash2 size={18} />}>Cancelar pedido</Button>}
        </div>
      </BottomSheet>

      <BottomSheet open={cancelSheet} onClose={() => setCancelSheet(false)} title="¿Cancelar este pedido?" description={`El folio ${order.folio} dejará de estar disponible para cobro.`}>
        <InlineNotice type="warning">Esta acción solo está disponible mientras el pedido siga pendiente.</InlineNotice>
        {cancelError && <InlineNotice type="warning">{cancelError}</InlineNotice>}
        <div className="sheet-actions">
          <Button full variant="danger" loading={cancelling} onClick={() => void cancel()}>Sí, cancelar pedido</Button>
          <Button full variant="quiet" onClick={() => setCancelSheet(false)}>Conservar pedido</Button>
        </div>
      </BottomSheet>
    </main>
  );
}
