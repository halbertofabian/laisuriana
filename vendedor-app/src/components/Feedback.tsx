import type { ReactNode } from 'react';
import { AlertTriangle, CheckCircle2, PackageSearch, WifiOff } from 'lucide-react';

export function EmptyState({
  title,
  message,
  action,
}: {
  title: string;
  message: string;
  action?: ReactNode;
}) {
  return (
    <div className="empty-state">
      <div className="empty-state__icon"><PackageSearch size={26} /></div>
      <h3>{title}</h3>
      <p>{message}</p>
      {action}
    </div>
  );
}

export function InlineNotice({ type, children }: { type: 'success' | 'warning' | 'offline'; children: ReactNode }) {
  const Icon = type === 'success' ? CheckCircle2 : type === 'offline' ? WifiOff : AlertTriangle;
  return (
    <div className={`inline-notice inline-notice--${type}`} role="status">
      <Icon size={18} />
      <span>{children}</span>
    </div>
  );
}

export function SkeletonList() {
  return (
    <div className="skeleton-list" aria-label="Cargando">
      {[1, 2, 3].map((item) => (
        <div className="skeleton-row" key={item}>
          <div className="skeleton skeleton--square" />
          <div className="skeleton-row__copy">
            <div className="skeleton skeleton--title" />
            <div className="skeleton skeleton--text" />
          </div>
        </div>
      ))}
    </div>
  );
}
