import { BadgePercent, Clock3, MapPin, MessageSquareText, Plus, ReceiptText, Trash2, UserRound } from 'lucide-react';
import { useCallback, useLayoutEffect, useMemo, useRef, useState } from 'react';
import { AppHeader } from '../components/AppHeader';
import { BottomSheet } from '../components/BottomSheet';
import { Button } from '../components/Button';
import { CustomerSheet } from '../components/CustomerSheet';
import { EmptyState, InlineNotice } from '../components/Feedback';
import { MeterLineEditor } from '../components/MeterLineEditor';
import { QuantityStepper } from '../components/QuantityStepper';
import { ApiError } from '../services/api';
import { firstIncompleteLine, formatMeters, formatQuantity, isMeterProduct, meterLineState, parseQuantityInput, roundMoney } from '../services/quantity';
import type { CartLine, Customer } from '../types';

const money = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

export function CartScreen({
  cart,
  customer,
  editingFolio,
  notes,
  requestId,
  online,
  focusRequest,
  onFocusHandled,
  onNotes,
  onBack,
  onCustomer,
  onQuantity,
  onMeterInput,
  onConfirmMeters,
  onDiscount,
  onSubmit,
}: {
  cart: CartLine[];
  customer: Customer;
  editingFolio?: string;
  notes: string;
  requestId: string;
  online: boolean;
  /** Línea por metro que debe mostrarse y enfocarse al llegar desde el catálogo. */
  focusRequest: { cartKey: string; id: number } | null;
  onFocusHandled: () => void;
  onNotes: (notes: string) => void;
  onBack: () => void;
  onCustomer: (customer: Customer) => void;
  onQuantity: (line: CartLine, quantity: number) => void;
  onMeterInput: (line: CartLine, text: string | undefined) => void;
  onConfirmMeters: (line: CartLine, quantity: number) => void;
  onDiscount: (line: CartLine, type: CartLine['discountType'], value: number, quantity: number) => void;
  onSubmit: (notes: string, requestId: string) => Promise<void>;
}) {
  const [clientSheet, setClientSheet] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState<string | null>(null);
  const [discountLine, setDiscountLine] = useState<CartLine | null>(null);
  const [discountType, setDiscountType] = useState<CartLine['discountType']>('percentage');
  const [discountValueText, setDiscountValueText] = useState('');
  const [discountQuantityText, setDiscountQuantityText] = useState('1');
  const [discountError, setDiscountError] = useState<string | null>(null);
  const [highlightKey, setHighlightKey] = useState<string | null>(null);
  const [blocked, setBlocked] = useState<{ cartKey: string; message: string } | null>(null);
  const lineElements = useRef(new Map<string, HTMLElement>());
  const meterInputs = useRef(new Map<string, HTMLInputElement>());
  const highlightTimer = useRef<number | undefined>(undefined);
  const incompleteLine = firstIncompleteLine(cart);
  const incompleteCount = cart.filter((line) => {
    const state = meterLineState(line);
    return state === 'pending' || state === 'unconfirmed';
  }).length;
  const parsedDiscountQuantity = parseQuantityInput(discountQuantityText);
  const discountQuantity = parsedDiscountQuantity.status === 'valid' ? parsedDiscountQuantity.value : 0;
  const discountValue = Number(discountValueText.trim().replace(',', '.')) || 0;
  const formatLineQuantity = (line: CartLine, quantity: number) => isMeterProduct(line) ? `${formatMeters(quantity)} m` : formatQuantity(quantity);
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

  /** Lleva la vista a la línea, la resalta y deja el cursor en su campo de metros. */
  const focusLine = useCallback((cartKey: string) => {
    const element = lineElements.current.get(cartKey);
    const input = meterInputs.current.get(cartKey);
    if (!element) return;
    input?.focus({ preventScroll: true });
    if (input?.value) input.select();
    element.scrollIntoView({ block: 'center' });
    // El teclado reduce la vista después de enfocar; se vuelve a centrar el campo.
    window.setTimeout(() => (input ?? element).scrollIntoView({ block: 'center', behavior: 'smooth' }), 350);
    setHighlightKey(cartKey);
    window.clearTimeout(highlightTimer.current);
    highlightTimer.current = window.setTimeout(() => setHighlightKey(null), 1800);
  }, []);

  // Layout effect: el enfoque ocurre en el mismo toque que abrió el carrito, así Android muestra el teclado.
  useLayoutEffect(() => {
    if (!focusRequest) return;
    focusLine(focusRequest.cartKey);
    onFocusHandled();
  }, [focusLine, focusRequest, onFocusHandled]);

  // Sin una línea que mostrar, la revisión siempre empieza arriba.
  useLayoutEffect(() => {
    if (!focusRequest) window.scrollTo(0, 0);
  }, []);

  const requestSubmit = () => {
    if (incompleteLine) {
      const action = meterLineState(incompleteLine) === 'pending' ? 'Captura y confirma' : 'Confirma';
      setBlocked({ cartKey: incompleteLine.cartKey, message: `${action} los metros para poder ${editingFolio ? 'guardar' : 'generar'} el pedido.` });
      focusLine(incompleteLine.cartKey);
      return;
    }
    setBlocked(null);
    void submit();
  };

  const submit = async () => {
    if (submitting) return;
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
    setDiscountValueText(line.discountType === 'none' ? '' : String(line.discountValue));
    const quantity = line.discountType === 'none' ? line.quantity : (line.discountQuantity || line.quantity);
    setDiscountQuantityText(isMeterProduct(line) ? formatMeters(quantity) : formatQuantity(quantity));
    setDiscountError(null);
  };

  const applyDiscount = () => {
    if (!discountLine) return;
    const minimum = discountLine.allowsDecimal ? 0.01 : 1;
    if (parsedDiscountQuantity.status === 'invalid') {
      setDiscountError(parsedDiscountQuantity.message);
      return;
    }
    if (discountQuantity < minimum || discountQuantity > discountLine.quantity) {
      setDiscountError('La cantidad con descuento debe estar dentro de la cantidad de la partida.');
      return;
    }
    if (!/^\d*([.,]\d{0,2})?$/.test(discountValueText.trim()) || discountValue <= 0) {
      setDiscountError('Escribe un descuento mayor a cero, por ejemplo 10.');
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

  const discountButton = (line: CartLine) => (
    <button className={`discount-action ${line.discountType !== 'none' ? 'discount-action--active' : ''}`} onClick={() => openDiscount(line)} aria-label={`${line.discountType === 'none' ? 'Agregar descuento' : 'Cambiar descuento'} a ${line.name}`}>
      <BadgePercent size={16} aria-hidden="true" />
      {line.discountType === 'none'
        ? 'Descuento'
        : `Descuento ${line.discountType === 'percentage' ? `${line.discountValue}%` : money.format(line.discountValue)} · −${money.format(lineDiscount(line))}`}
    </button>
  );

  return (
    <main className="screen screen--with-action screen-enter">
      <AppHeader eyebrow={editingFolio ? `Editando ${editingFolio}` : 'Nuevo pedido'} title={editingFolio ? 'Revisar cambios' : 'Revisar pedido'} onBack={onBack} />
      <section className="screen-content cart-content">
        <button className="customer-row" onClick={() => setClientSheet(true)} aria-label={`Cliente: ${customer.name}. Cambiar cliente`}>
          <UserRound size={18} aria-hidden="true" />
          <span>Cliente</span>
          <strong>{customer.name}</strong>
          <em>Cambiar</em>
        </button>

        {cart.length === 0 && (
          <EmptyState
            title="El pedido está vacío"
            message="Busca productos en el catálogo para agregarlos."
            action={<Button icon={<Plus size={19} />} onClick={onBack}>Agregar productos</Button>}
          />
        )}

        {warehouseGroups.map((group) => (
          <section className="warehouse-group" key={group.id} aria-label={`Almacén ${group.name}`}>
            <h2 className="warehouse-group__title"><MapPin size={14} aria-hidden="true" />{group.name}</h2>
            <div className="cart-lines">
              {group.lines.map((line) => {
                const meter = isMeterProduct(line);
                return (
                  <article
                    className={`cart-line ${highlightKey === line.cartKey ? 'cart-line--highlight' : ''}`}
                    key={line.cartKey}
                    ref={(element) => {
                      if (element) lineElements.current.set(line.cartKey, element);
                      else lineElements.current.delete(line.cartKey);
                    }}
                  >
                    <div className="cart-line__heading">
                      <div>
                        <h3>{line.name}</h3>
                        <span>{line.sku} · {money.format(line.price)} {meter ? 'por metro' : 'c/u'}</span>
                      </div>
                      <button onClick={() => onQuantity(line, 0)} aria-label={`Quitar ${line.name} del pedido`}><Trash2 size={18} /></button>
                    </div>
                    {meter ? (
                      <>
                        <MeterLineEditor
                          line={line}
                          blockedMessage={blocked?.cartKey === line.cartKey ? blocked.message : undefined}
                          inputRef={(element) => {
                            if (element) meterInputs.current.set(line.cartKey, element);
                            else meterInputs.current.delete(line.cartKey);
                          }}
                          onInput={(text) => onMeterInput(line, text)}
                          onConfirm={(quantity) => {
                            onConfirmMeters(line, quantity);
                            if (blocked?.cartKey === line.cartKey) setBlocked(null);
                          }}
                        />
                        {/* El descuento solo aplica sobre una cantidad confirmada. */}
                        {meterLineState(line) === 'confirmed' && (
                          <div className="cart-line__extra">
                            {discountButton(line)}
                            {line.discountType !== 'none' && <strong>{money.format(lineTotal(line))}</strong>}
                          </div>
                        )}
                      </>
                    ) : (
                      <>
                        <div className="cart-line__footer">
                          <QuantityStepper value={line.quantity} step={line.allowsDecimal ? 0.01 : 1} onChange={(value) => onQuantity(line, value)} />
                          <strong>{money.format(lineTotal(line))}</strong>
                        </div>
                        <div className="cart-line__extra">{discountButton(line)}</div>
                      </>
                    )}
                  </article>
                );
              })}
            </div>
          </section>
        ))}

        {cart.length > 0 && (
          <Button full variant="secondary" className="keep-adding" icon={<Plus size={19} />} onClick={onBack}>Seguir agregando productos</Button>
        )}

        {cart.length > 0 && (
          <label className="notes-field">
            <MessageSquareText size={19} aria-hidden="true" />
            <input value={notes} onChange={(event) => onNotes(event.target.value)} placeholder="Nota para caja (opcional)" aria-label="Nota para caja" />
          </label>
        )}

        {!online && <InlineNotice type="offline">Sin conexión. El pedido queda guardado en este teléfono y podrás enviarlo al reconectarte.</InlineNotice>}
        {incompleteCount > 0 && (
          <InlineNotice type="warning">
            {incompleteCount === 1 ? 'Falta confirmar los metros de 1 producto.' : `Falta confirmar los metros de ${incompleteCount} productos.`} No se incluyen en el total.
          </InlineNotice>
        )}

        {totalDiscount > 0 && (
          <div className="totals">
            <div><span>Subtotal</span><span>{money.format(subtotal)}</span></div>
            <div><span>Descuentos</span><span>−{money.format(totalDiscount)}</span></div>
          </div>
        )}
      </section>

      <div className="sticky-action sticky-action--summary">
        {submitError && <InlineNotice type="warning">{submitError}</InlineNotice>}
        <div className="sticky-action__row">
          <div className="sticky-action__total">
            <span>{warehouseGroups.length > 1 ? `Total · ${warehouseGroups.length} tickets` : 'Total'}</span>
            <strong>{money.format(total)}</strong>
            {incompleteCount > 0 && <small className="sticky-action__pending"><Clock3 size={12} aria-hidden="true" /> {incompleteCount === 1 ? '1 sin metros' : `${incompleteCount} sin metros`}</small>}
          </div>
          <Button loading={submitting} disabled={cart.length === 0 || !online} onClick={requestSubmit} icon={<ReceiptText size={20} />}>
            {editingFolio ? 'Guardar cambios' : 'Generar pedido'}
          </Button>
        </div>
      </div>

      <CustomerSheet open={clientSheet} customer={customer} online={online} onClose={() => setClientSheet(false)} onSelect={onCustomer} />

      <BottomSheet open={discountLine !== null} onClose={() => setDiscountLine(null)} title="Aplicar descuento" description={discountLine?.name ?? ''}>
        <div className="discount-types" role="tablist" aria-label="Tipo de descuento">
          <button className={discountType === 'percentage' ? 'active' : ''} onClick={() => { setDiscountType('percentage'); setDiscountError(null); }}>Porcentaje</button>
          <button className={discountType === 'amount' ? 'active' : ''} onClick={() => { setDiscountType('amount'); setDiscountError(null); }}>Importe</button>
        </div>
        <div className="discount-form">
          <label>
            <span>{discountType === 'percentage' ? 'Porcentaje' : 'Importe'}</span>
            <div className="discount-input"><i>{discountType === 'percentage' ? '%' : '$'}</i><input type="text" inputMode="decimal" autoComplete="off" placeholder="0" value={discountValueText} onFocus={(event) => event.currentTarget.select()} onChange={(event) => { setDiscountValueText(event.target.value); setDiscountError(null); }} aria-label={discountType === 'percentage' ? 'Porcentaje de descuento' : 'Importe de descuento'} /></div>
          </label>
          <label>
            <span>Cantidad a la que aplica</span>
            <div className="discount-input"><input type="text" inputMode={discountLine?.allowsDecimal ? 'decimal' : 'numeric'} autoComplete="off" value={discountQuantityText} onFocus={(event) => event.currentTarget.select()} onChange={(event) => { setDiscountQuantityText(event.target.value); setDiscountError(null); }} aria-label="Cantidad a la que aplica" /><i>de {discountLine ? formatLineQuantity(discountLine, discountLine.quantity) : 0}</i></div>
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
