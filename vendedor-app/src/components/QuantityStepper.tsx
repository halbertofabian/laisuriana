import { Minus, Plus } from 'lucide-react';

interface QuantityStepperProps {
  value: number;
  onChange: (value: number) => void;
  compact?: boolean;
  step?: number;
}

export function QuantityStepper({ value, onChange, compact, step = 1 }: QuantityStepperProps) {
  const normalize = (next: number) => Math.max(0, Math.round(next * 100) / 100);

  return (
    <div className={`stepper ${compact ? 'stepper--compact' : ''}`} aria-label={`Cantidad: ${value}`}>
      <button type="button" onClick={() => onChange(normalize(value - step))} aria-label="Restar cantidad">
        <Minus size={compact ? 16 : 18} />
      </button>
      <input
        type="number"
        inputMode={step < 1 ? 'decimal' : 'numeric'}
        min="0"
        step={step}
        value={value}
        onChange={(event) => onChange(normalize(Number(event.target.value)))}
        aria-label="Cantidad"
      />
      <button type="button" onClick={() => onChange(normalize(value + step))} aria-label="Agregar cantidad">
        <Plus size={compact ? 16 : 18} />
      </button>
    </div>
  );
}
