import { ArrowLeft, MoreHorizontal, UserRound } from 'lucide-react';

interface AppHeaderProps {
  eyebrow?: string;
  title: string;
  onBack?: () => void;
  onProfile?: () => void;
  onMore?: () => void;
}

export function AppHeader({ eyebrow, title, onBack, onProfile, onMore }: AppHeaderProps) {
  return (
    <header className="app-header">
      <div className="app-header__side">
        {onBack ? (
          <button className="icon-button" onClick={onBack} aria-label="Volver">
            <ArrowLeft size={22} />
          </button>
        ) : (
          <div className="brand-mark" aria-hidden="true">S</div>
        )}
      </div>
      <div className="app-header__title">
        {eyebrow && <span>{eyebrow}</span>}
        <h1>{title}</h1>
      </div>
      <div className="app-header__side app-header__side--end">
        {onProfile && (
          <button className="avatar-button" onClick={onProfile} aria-label="Abrir cuenta">
            <UserRound size={19} />
          </button>
        )}
        {onMore && (
          <button className="icon-button" onClick={onMore} aria-label="Más opciones">
            <MoreHorizontal size={23} />
          </button>
        )}
      </div>
    </header>
  );
}
