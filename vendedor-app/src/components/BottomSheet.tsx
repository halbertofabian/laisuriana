import type { ReactNode } from 'react';
import { X } from 'lucide-react';

interface BottomSheetProps {
  open: boolean;
  title: string;
  description?: string;
  children: ReactNode;
  onClose: () => void;
}

export function BottomSheet({ open, title, description, children, onClose }: BottomSheetProps) {
  if (!open) return null;

  return (
    <div className="sheet-layer" role="presentation" onMouseDown={onClose}>
      <section
        className="bottom-sheet"
        role="dialog"
        aria-modal="true"
        aria-labelledby="sheet-title"
        onMouseDown={(event) => event.stopPropagation()}
      >
        <div className="bottom-sheet__handle" />
        <div className="bottom-sheet__heading">
          <div>
            <h2 id="sheet-title">{title}</h2>
            {description && <p>{description}</p>}
          </div>
          <button className="icon-button icon-button--soft" onClick={onClose} aria-label="Cerrar">
            <X size={20} />
          </button>
        </div>
        {children}
      </section>
    </div>
  );
}
