import { BadgePercent, ChevronRight, MapPin, MessageSquareText, ReceiptText, Trash2, UserRound } from 'lucide-react';
import { useMemo, useState } from 'react';
import { AppHeader } from '../components/AppHeader';
import { BottomSheet } from '../components/BottomSheet';
import { Button } from '../components/Button';
import { InlineNotice } from '../components/Feedback';
import { QuantityStepper } from '../components/QuantityStepper';
import { ApiError } from '../services/api';
import type { CartLine, Customer } from '../types';

const money = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

export function CartScreen({
  cart,
  customer,
  editingFolio,
  notes,
  requestId,
  online,
  onNotes,
  onBack,
  onQuantity,
  onDiscount,
  onSubmit,
}: {
  cart: CartLine[];
  customer: Customer;
  editingFolio?: string;
  notes: string;
  requestId: string;
  online: boolean;
  onNotes: (notes: string) => void;
  onBack: () => void;
  onQuantity: (line: CartLine, quantity: number) => void;
  onDiscount: (line: CartLine, type: CartLine['discountType'], value: number, quantity: number) => void;
  onSubmit: (notes: string, requestId: string) => Promise<void>;
}) {
  const [confirmOpen, setConfirmOpen] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState<string | null>(null);
  const [discountLine, setDiscountLine] = useState<CartLine | null>(null);
  const [discountType, setDiscountType] = useState<CartLine['discountType']>('percentage');
  const [discountValue, setDiscountValue] = useState(0);
  const [discountQuantity, setDiscountQuantity] = useState(1);
  const [discountError, setDiscountError] = useState<string | null>(null);
  const roundMoney = (value: number) => Math.round(value * 100) / 100;
  const lineSubtotal = (line: CartLine) => roundMoney(line.price * line.quantity);
  const lineDiscount = (line: CartLine) => {
    if (line.discountType === 'percentage') return roundMoney(lineSubtotal(line) * Math.min(100, line.discountValue) / 100);
    if (line.discountType === 'amount') return Math.min(lineSubtotal(line), roundMoney(line.discountValue));
    return 0;
  };
  const lineTotal = (line: CartLine) => roundMoney(lineSubtotal(line) - lineDiscount(line));
  const subtotal = cart.reduce((sum, line) => sum + lineSubtotal(line), 0);
  const totalDiscount = cart.reduce((sum, line) => sum + lineDiscount(line), 0);
  const total = roundMoney(subtotal - totalDiscount);
  const warehouseGroups = useMemo(() => Object.values(cart.reduce<Record<number, { id: number; name: string; lines: CartLine[] }>>((groups, line) => {
    groups[line.warehouseId] ??= { id: line.warehouseId, name: line.warehouseName, lines: [] };
    groups[line.warehouseId].lines.push(line);
    return groups;
  }, {})), [cart]);

  const submit = async () => {
    if (!online) {
      setSubmitError('Tu pedido está guardado. Podrás enviarlo cuando vuelva la conexión.');
      return;
    }
    setSubmitting(true);
    setSubmitError(null);
    try {
      await onSubmit(notes, requestId);
    } catch (error) {
      if (error instanceof ApiError) {
        const firstError = Object.values(error.errors)[0]?.[0];
        setSubmitError(firstError ?? error.message);
      } else {
        setSubmitError('No pudimos generar el pedido. Intenta nuevamente.');
      }
    } finally {
      setSubmitting(false);
    }
  };

  const openDiscount = (line: CartLine) => {
    setDiscountLine(line);
    setDiscountType(line.discountType === 'none' ? 'percentage' : line.discountType);
    setDiscountValue(line.discountType === 'none' ? 0 : line.discountValue);
    setDiscountQuantity(line.discountType === 'none' ? line.quantity : (line.discountQuantity || line.quantity));
    setDiscountError(null);
  };

  const applyDiscount = () => {
    if (!discountLine) return;
    const minimum = discountLine.allowsDecimal ? 0.01 : 1;
    if (discountQuantity < minimum || discountQuantity > discountLine.quantity) {
      setDiscountError('La cantidad con descuento debe estar dentro de la cantidad de la partida.');
      return;
    }
    if (discountValue <= 0) {
      setDiscountError('Captura un descuento mayor a cero.');
      return;
    }
    if (discountType === 'percentage' && discountValue > 100) {
      setDiscountError('El porcentaje no puede ser mayor a 100%.');
      return;
    }
    if (discountType === 'amount' && discountValue > roundMoney(discountLine.price * discountQuantity)) {
      setDiscountError('El descuento no puede superar el subtotal de la cantidad seleccionada.');
      return;
    }

    onDiscount(discountLine, discountType, roundMoney(discountValue), discountQuantity);
    setDiscountLine(null);
  };

  return (
    <main className="screen screen--with-action screen-enter">
      <AppHeader eyebrow={editingFolio ? `EDITANDO ${editingFolio}` : 'NUEVO PEDIDO'} title={editingFolio ? 'Revisar cambios' : 'Revisar pedido'} onBack={onBack} />
      <section className="screen-content cart-content">
        <div className="customer-summary">
          <div className="customer-summary__avatar"><UserRound size={20} /></div>
          <div><span>Cliente</span><strong>{customer.name}</strong></div>
          <ChevronRight size={18} />
        </div>

        <div className="section-heading"><h2>Productos</h2><span>{cart.length} partidas</span></div>
        {warehouseGroups.map((group) => (
          <section className="warehouse-group" key={group.id}>
            <div className="warehouse-group__title"><MapPin size={15} /><span>{group.name}</span><small>{group.lines.length}</small></div>
            <div className="cart-lines">
              {group.lines.map((line) => (
                <article className="cart-line" key={line.cartKey}>
                  <div className="cart-line__heading">
                    <div><span>{line.sku}</span><h3>{line.name}</h3></div>
                    <button onClick={() => onQuantity(line, 0)} aria-label={`Eliminar ${line.name}`}><Trash2 size={18} /></button>
                  </div>
                  <div className="cart-line__footer">
                    <QuantityStepper value={line.quantity} step={line.allowsDecimal ? 0.01 : 1} onChange={(value) => onQuantity(line, value)} />
                    <div><span>{money.format(line.price)} c/u</span><strong>{money.format(lineTotal(line))}</strong></div>
                  </div>
                  <button className={`discount-action ${line.discountType !== 'none' ? 'discount-action--active' : ''}`} onClick={() => openDiscount(line)}>
                    <BadgePercent size={16} />
                    {line.discountType === 'none'
                      ? 'Agregar descuento'
                      : `${line.discountType === 'percentage' ? `${line.discountValue}%` : money.format(line.discountValue)} · −${money.format(lineDiscount(line))}`}
                  </button>
                </article>
              ))}
            </div>
          </section>
        ))}

        <label className="notes-field">
          <MessageSquareText size={19} />
          <input value={notes} onChange={(event) => onNotes(event.target.value)} placeholder="Agregar una nota (opcional)" />
        </label>

        {!online && <InlineNotice type="offline">Tu pedido está guardado en este teléfono. Podrás enviarlo cuando vuelva la conexión.</InlineNotice>}

        <div className="totals">
          <div><span>Subtotal</span><span>{money.format(subtotal)}</span></div>
          <div><span>Descuento</span><span>{totalDiscount > 0 ? `−${money.format(totalDiscount)}` : '—'}</span></div>
          <div className="totals__grand"><strong>Total</strong><strong>{money.format(total)}</strong></div>
        </div>
      </section>

      <div className="sticky-action sticky-action--summary">
        <div><span>Total</span><strong>{money.format(total)}</strong></div>
        <Button disabled={cart.length === 0 || !online} onClick={() => setConfirmOpen(true)} icon={<ReceiptText size={20} />}>{editingFolio ? 'Guardar' : 'Generar'}</Button>
      </div>

      <BottomSheet open={confirmOpen} onClose={() => setConfirmOpen(false)} title={editingFolio ? '¿Guardar los cambios?' : '¿Generar este pedido?'} description={editingFolio ? `Se actualizará ${editingFolio} sin cambiar su folio.` : 'Se creará un folio para que el cliente pague en caja.'}>
        <div className="confirm-summary">
          <div><span>Cliente</span><strong>{customer.name}</strong></div>
          <div><span>{cart.length} partidas</span><strong>{money.format(total)}</strong></div>
          {warehouseGroups.length > 1 && <div><span>Tickets a generar</span><strong>{warehouseGroups.length}</strong></div>}
        </div>
        <div className="sheet-actions">
          {submitError && <InlineNotice type="warning">{submitError}</InlineNotice>}
          {!online && <InlineNotice type="offline">Sin conexión. Conservaremos este pedido hasta que puedas reintentarlo.</InlineNotice>}
          <Button full loading={submitting} disabled={!online} onClick={() => void submit()}>{editingFolio ? 'Sí, guardar cambios' : 'Sí, generar pedido'}</Button>
          <Button full variant="quiet" onClick={() => setConfirmOpen(false)}>Seguir revisando</Button>
        </div>
      </BottomSheet>

      <BottomSheet open={discountLine !== null} onClose={() => setDiscountLine(null)} title="Aplicar descuento" description={discountLine?.name ?? ''}>
        <div className="discount-types" role="tablist" aria-label="Tipo de descuento">
          <button className={discountType === 'percentage' ? 'active' : ''} onClick={() => { setDiscountType('percentage'); setDiscountError(null); }}>Porcentaje</button>
          <button className={discountType === 'amount' ? 'active' : ''} onClick={() => { setDiscountType('amount'); setDiscountError(null); }}>Importe</button>
        </div>
        <div className="discount-form">
          <label>
            <span>{discountType === 'percentage' ? 'Porcentaje' : 'Importe'}</span>
            <div className="discount-input"><i>{discountType === 'percentage' ? '%' : '$'}</i><input type="number" inputMode="decimal" min="0" step="0.01" value={discountValue} onFocus={(event) => event.currentTarget.select()} onChange={(event) => { setDiscountValue(Number(event.target.value)); setDiscountError(null); }} /></div>
          </label>
          <label>
            <span>Cantidad a la que aplica</span>
            <div className="discount-input"><input type="number" inputMode={discountLine?.allowsDecimal ? 'decimal' : 'numeric'} min={discountLine?.allowsDecimal ? 0.01 : 1} max={discountLine?.quantity} step={discountLine?.allowsDecimal ? 0.01 : 1} value={discountQuantity} onFocus={(event) => event.currentTarget.select()} onChange={(event) => { setDiscountQuantity(Number(event.target.value)); setDiscountError(null); }} /><i>de {discountLine?.quantity ?? 0}</i></div>
          </label>
        </div>
        {discountLine && discountValue > 0 && discountQuantity > 0 && (
          <div className="discount-preview"><span>Descuento estimado</span><strong>−{money.format(discountType === 'percentage' ? discountLine.price * discountQuantity * Math.min(100, discountValue) / 100 : Math.min(discountLine.price * discountQuantity, discountValue))}</strong></div>
        )}
        {discountError && <InlineNotice type="warning">{discountError}</InlineNotice>}
        <div className="sheet-actions">
          <Button full onClick={applyDiscount}>Aplicar descuento</Button>
          {discountLine?.discountType !== 'none' && <Button full variant="quiet" onClick={() => { if (!discountLine) return; onDiscount(discountLine, 'none', 0, 0); setDiscountLine(null); }}>Quitar descuento</Button>}
        </div>
      </BottomSheet>
    </main>
  );
}
