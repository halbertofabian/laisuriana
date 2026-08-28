import { Search, X } from 'lucide-react';

interface SearchFieldProps {
  value: string;
  onChange: (value: string) => void;
  placeholder?: string;
  autoFocus?: boolean;
}

export function SearchField({ value, onChange, placeholder = 'Buscar', autoFocus }: SearchFieldProps) {
  return (
    <label className="search-field">
      <Search size={20} aria-hidden="true" />
      <input
        value={value}
        onChange={(event) => onChange(event.target.value)}
        placeholder={placeholder}
        autoFocus={autoFocus}
        aria-label={placeholder}
      />
      {value && (
        <button onClick={() => onChange('')} aria-label="Limpiar búsqueda" type="button">
          <X size={18} />
        </button>
      )}
    </label>
  );
}
