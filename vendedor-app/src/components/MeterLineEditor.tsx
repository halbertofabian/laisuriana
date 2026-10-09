import { Check, CircleAlert, CircleCheck, Clock3 } from 'lucide-react';
import { useId, useRef, useState } from 'react';
import { formatMeters, meterLineState, parseQuantityInput, roundMoney } from '../services/quantity';
import type { CartLine } from '../types';

const money = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

interface MeterLineEditorProps {
  line: CartLine;
  /** Mensaje cuando se intentó finalizar el pedido con esta línea incompleta. */
  blockedMessage?: string;
  inputRef: (element: HTMLInputElement | null) => void;
  onInput: (text: string | undefined) => void;
  onConfirm: (quantity: number) => void;
}

export function MeterLineEditor({ line, blockedMessage, inputRef, onInput, onConfirm }: MeterLineEditorProps) {
  const [error, setError] = useState<string | null>(null);
  const [justSaved, setJustSaved] = useState(false);
  const selectOnMouseUp = useRef(false);
  const inputId = useId();
  const messageId = useId();
  const state = meterLineState(line) ?? 'pending';
  // Se muestra exactamente lo que se escribió; solo una cantidad confirmada se formatea.
  const text = line.meterInput ?? (line.quantity > 0 ? formatMeters(line.quantity) : '');
  const parsed = parseQuantityInput(text);
  const shownQuantity = parsed.status === 'valid' ? parsed.value : null;
  const message = error ?? (state !== 'confirmed' ? blockedMessage : undefined);

  const confirm = (input: HTMLInputElement | null) => {
    if (parsed.status === 'valid') {
      onConfirm(parsed.value);
      setError(null);
      setJustSaved(true);
      window.setTimeout(() => setJustSaved(false), 1800);
      input?.blur();
      return;
    }
    setError(parsed.status === 'invalid'
      ? parsed.message
      : text.trim() === '' ? 'Escribe la cantidad en metros, por ejemplo 2.75.' : 'La cantidad debe ser mayor a 0.');
    input?.focus();
  };

  return (
    <div className={`meter-entry meter-entry--${state}`}>
      <div className={`meter-entry__status ${justSaved ? 'meter-entry__status--saved' : ''}`} role="status">
        {state === 'confirmed' ? <CircleCheck size={15} /> : <Clock3 size={15} />}
        {state === 'pending' && 'Pendiente de capturar metros'}
        {state === 'unconfirmed' && 'Cambios sin confirmar'}
        {state === 'confirmed' && 'Cantidad guardada'}
      </div>

      <label className="meter-entry__label" htmlFor={inputId}>Cantidad en metros</label>
      <div className={`meter-entry__field ${message ? 'meter-entry__field--error' : ''}`}>
        <input
          id={inputId}
          ref={inputRef}
          type="text"
          inputMode="decimal"
          enterKeyHint="done"
          autoComplete="off"
          maxLength={9}
          placeholder="Ej. 2.75"
          value={text}
          onChange={(event) => { setError(null); onInput(event.target.value); }}
          onFocus={(event) => {
            if (!event.currentTarget.value) return;
            selectOnMouseUp.current = true;
            event.currentTarget.select();
          }}
          onMouseUp={(event) => {
            // El mouseup del mismo toque colocaría el cursor y desharía la selección.
            if (!selectOnMouseUp.current) return;
            selectOnMouseUp.current = false;
            event.preventDefault();
            event.currentTarget.select();
          }}
          onBlur={() => { selectOnMouseUp.current = false; }}
          onKeyDown={(event) => {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            confirm(event.currentTarget);
          }}
          aria-invalid={message ? true : undefined}
          aria-describedby={message ? messageId : undefined}
        />
        <span aria-hidden="true">m</span>
      </div>
      {message && <p className="meter-entry__error" id={messageId} role="alert"><CircleAlert size={14} />{message}</p>}

      {/* El precio por metro ya está en el encabezado de la línea; aquí solo el subtotal. */}
      {shownQuantity !== null && (
        <div className="meter-entry__price">
          <span>
            {state !== 'confirmed' && <em>Vista previa</em>}
            {formatMeters(shownQuantity)} m × {money.format(line.price)}/m
          </span>
          <strong>= {money.format(roundMoney(shownQuantity * line.price))}</strong>
        </div>
      )}

      {state !== 'confirmed' && (
        <div className="meter-entry__actions">
          <button
            type="button"
            className="meter-entry__confirm"
            // Evita que el teclado se cierre antes del clic y desplace el botón.
            onPointerDown={(event) => event.preventDefault()}
            onClick={(event) => confirm(event.currentTarget.closest('.meter-entry')?.querySelector('input') ?? null)}
          >
            <Check size={18} /> Confirmar metros
          </button>
          {state === 'unconfirmed' && (
            <button type="button" className="meter-entry__discard" onClick={() => { setError(null); onInput(undefined); }}>
              Descartar cambios
            </button>
          )}
        </div>
      )}
    </div>
  );
}
