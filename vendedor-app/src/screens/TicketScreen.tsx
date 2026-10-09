import { Bluetooth, Check, CircleX, Copy, Pencil, Printer, ReceiptText, Share2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { AppHeader } from '../components/AppHeader';
import { BottomSheet } from '../components/BottomSheet';
import { Button } from '../components/Button';
import { Code128Barcode } from '../components/Code128Barcode';
import { InlineNotice } from '../components/Feedback';
import { ApiError, floorOrderApi } from '../services/api';
import { bluetoothPrinter, printerErrorMessage, printerProfileNames } from '../services/bluetoothPrinter';
import { formatMeters, formatQuantity, isMeterProduct } from '../services/quantity';
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
  const [printedIds, setPrintedIds] = useState<number[]>([]);
  const [printing, setPrinting] = useState(false);
  const [printError, setPrintError] = useState<string | null>(null);
  const [shared, setShared] = useState(false);
  const [cancelSheet, setCancelSheet] = useState(false);
  const [cancelling, setCancelling] = useState(false);
  const [cancelError, setCancelError] = useState<string | null>(null);
  const automaticPrintStarted = useRef(false);
  const order = orders[Math.min(activeIndex, orders.length - 1)];
  const pending = order.status === 'pending';
  const printed = printedIds.includes(order.id);

  const printOrders = async (ordersToPrint: OrderDetail[], verifyStatus = true) => {
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
    } catch (error) {
      setPrintError(error instanceof ApiError ? error.message : printerErrorMessage(error));
    } finally {
      setPrinting(false);
    }
  };

  useEffect(() => {
    if (mode !== 'generated' || !printerConfig || automaticPrintStarted.current) return;
    automaticPrintStarted.current = true;
    void printOrders(orders, false);
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

  const printLabel = orders.length > 1 ? `Imprimir ${orders.length} tickets` : printed ? 'Reimprimir ticket' : 'Imprimir ticket';
  const headerTitle = mode === 'generated'
    ? (orders.length > 1 ? 'Pedidos generados' : 'Pedido generado')
    : mode === 'updated' ? 'Pedido actualizado' : 'Detalle del pedido';
  const status = order.status === 'paid'
    ? { tone: 'success', title: 'Pagado', text: 'Ya se cobró en caja. No es necesario volver a presentarlo.' }
    : order.status === 'cancelled'
      ? { tone: 'danger', title: 'Cancelado', text: 'Este folio ya no se puede cobrar ni imprimir.' }
      : mode === 'updated'
        ? { tone: 'success', title: 'Cambios guardados', text: 'Se conserva el mismo folio. Imprime de nuevo si el cliente tiene el ticket anterior.' }
        : mode === 'generated'
          ? { tone: 'success', title: 'Listo para cobrar', text: orders.length > 1 ? `Se generó un ticket por almacén (${orders.length}). Entrega todos al cliente.` : 'Entrega el ticket al cliente para que pague en caja.' }
          : { tone: 'pending', title: 'Pendiente de pago', text: 'El cliente debe presentar este folio en caja.' };

  return (
    <main className="screen screen-enter">
      <AppHeader title={headerTitle} onBack={onDone} />
      <section className="screen-content ticket-content">
        <div className={`ticket-banner ticket-banner--${status.tone}`} role="status">
          {order.status === 'cancelled' ? <CircleX size={20} aria-hidden="true" /> : status.tone === 'pending' ? <ReceiptText size={20} aria-hidden="true" /> : <Check size={20} aria-hidden="true" />}
          <div><strong>{status.title}</strong><span>{status.text}</span></div>
        </div>

        {orders.length > 1 && (
          <div className="ticket-switcher" role="group" aria-label="Tickets generados">
            {orders.map((item, index) => (
              <button key={item.id} className={index === activeIndex ? 'active' : ''} aria-pressed={index === activeIndex} onClick={() => setActiveIndex(index)}>
                <strong>{item.folio}</strong>
                <small>{item.warehouse}{printedIds.includes(item.id) ? ' · impreso' : ''}</small>
              </button>
            ))}
          </div>
        )}

        <article className={`ticket-card${pending ? '' : ' ticket-card--inactive'}`}>
          <div className="ticket-card__top">
            <strong>{order.folio}</strong>
            <small>{orderDate(order)} · {order.warehouse}</small>
          </div>
          <Code128Barcode value={order.folio} />
          <div className="ticket-card__details">
            <div><span>Cliente</span><strong>{order.customer}</strong></div>
          </div>
          {order.lines.length > 0 && (
            <ul className="ticket-lines" aria-label="Productos">
              {order.lines.map((line) => (
                <li key={line.id}>
                  <span>
                    <strong>{line.name}</strong>
                    <small>
                      {isMeterProduct(line) ? `${formatMeters(line.quantity)} m` : formatQuantity(line.quantity)} × {money.format(line.price)}
                      {line.discount > 0 ? ` · descuento −${money.format(line.discount)}` : ''}
                    </small>
                  </span>
                  <b>{money.format(line.total)}</b>
                </li>
              ))}
            </ul>
          )}
          <div className="ticket-card__total"><span>Total</span><strong>{money.format(order.total)}</strong></div>
        </article>

        {pending && (
          <div className="ticket-actions">
            {pending && printedIds.length > 0 && !printing && !printError && (
              <p className="print-success" role="status"><Check size={16} aria-hidden="true" /> {printedIds.length > 1 ? `${printedIds.length} tickets enviados a la impresora` : 'Ticket enviado a la impresora'}</p>
            )}
            {printError && <InlineNotice type="warning">{printError} El pedido sigue guardado; puedes reintentar.</InlineNotice>}
            {printerConfig ? (
              <>
                <Button full loading={printing} onClick={() => void printOrders(orders.length > 1 ? orders : [order])} icon={<Printer size={20} />}>{printLabel}</Button>
                <button className="printer-line" onClick={onConfigurePrinter}>
                  <Bluetooth size={14} aria-hidden="true" />{printerConfig.name} · {printerProfileNames[printerConfig.language]} · {printerConfig.paperWidth} mm<span>Cambiar</span>
                </button>
              </>
            ) : (
              <Button full onClick={onConfigurePrinter} icon={<Printer size={20} />}>Configurar impresora</Button>
            )}
            <div className="ticket-actions__row">
              {canEdit && orders.length === 1 && <Button variant="secondary" onClick={() => onEdit(order)} icon={<Pencil size={18} />}>Editar</Button>}
              <Button variant="secondary" onClick={() => void share()} icon={shared ? <Copy size={18} /> : <Share2 size={18} />}>{shared ? 'Compartido' : 'Compartir'}</Button>
            </div>
            {canCancel && <button className="text-danger-button" onClick={() => setCancelSheet(true)}>Cancelar pedido</button>}
          </div>
        )}
        <Button full variant="quiet" className="ticket-done" onClick={onDone}>Volver a pedidos</Button>
      </section>

      <BottomSheet open={cancelSheet} onClose={() => setCancelSheet(false)} title="¿Cancelar este pedido?" description={`El folio ${order.folio} dejará de estar disponible para cobro.`}>
        {cancelError && <InlineNotice type="warning">{cancelError}</InlineNotice>}
        <div className="sheet-actions">
          <Button full variant="danger" loading={cancelling} onClick={() => void cancel()}>Cancelar pedido</Button>
          <Button full variant="quiet" onClick={() => setCancelSheet(false)}>Conservar pedido</Button>
        </div>
      </BottomSheet>
    </main>
  );
}
