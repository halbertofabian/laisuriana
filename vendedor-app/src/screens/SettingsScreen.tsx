import { Bluetooth, Building2, CheckCircle2, ChevronRight, Cloud, LogOut, Printer, RefreshCw, TriangleAlert, UserRound } from 'lucide-react';
import { AppHeader } from '../components/AppHeader';
import { BottomSheet } from '../components/BottomSheet';
import { Button } from '../components/Button';
import { systemApi } from '../services/api';
import { printerProfileNames } from '../services/bluetoothPrinter';
import type { AuthUser, PrinterConfig } from '../types';
import { useCallback, useEffect, useState } from 'react';

type ServerStatus = 'checking' | 'online' | 'offline';

export function SettingsScreen({
  user,
  activeBranchId,
  branchName,
  printerConfig,
  onPrinter,
  onBranch,
  onBack,
  onLogout,
}: {
  user: AuthUser;
  activeBranchId: number;
  branchName: string;
  printerConfig: PrinterConfig | null;
  onPrinter: () => void;
  onBranch: (branchId: number) => void;
  onBack: () => void;
  onLogout: () => Promise<void>;
}) {
  const [loggingOut, setLoggingOut] = useState(false);
  const [branchSheet, setBranchSheet] = useState(false);
  const [serverStatus, setServerStatus] = useState<ServerStatus>('checking');

  const checkServer = useCallback(async () => {
    setServerStatus('checking');
    try {
      await systemApi.health();
      setServerStatus('online');
    } catch {
      setServerStatus('offline');
    }
  }, []);

  useEffect(() => {
    void checkServer();
  }, [checkServer]);

  const logout = async () => {
    setLoggingOut(true);
    await onLogout();
  };

  return (
    <main className="screen screen-enter">
      <AppHeader title="Cuenta" onBack={onBack} />
      <section className="screen-content settings-content">
        <div className="profile-card">
          <div className="profile-card__avatar"><UserRound size={28} /></div>
          <div><h2>{user.nombre}</h2><p>@{user.usuario} · Vendedor de piso</p></div>
        </div>
        <p className="settings-label">JORNADA</p>
        <div className="settings-group">
          <button onClick={() => setBranchSheet(true)} disabled={user.sucursales.length <= 1}><span className="settings-icon"><Building2 size={20} /></span><span><strong>Sucursal</strong><small>{branchName}</small></span>{user.sucursales.length > 1 && <ChevronRight size={18} />}</button>
          <button onClick={onPrinter}><span className="settings-icon"><Printer size={20} /></span><span><strong>Impresora</strong><small className={printerConfig ? 'connected' : ''}><Bluetooth size={13} /> {printerConfig ? `${printerConfig.name} · ${printerProfileNames[printerConfig.language]}` : 'Sin configurar'}</small></span><ChevronRight size={18} /></button>
          <button onClick={() => void checkServer()} disabled={serverStatus === 'checking'}><span className="settings-icon"><Cloud size={20} /></span><span><strong>Servidor</strong><small className={serverStatus === 'online' ? 'connected' : serverStatus === 'offline' ? 'unavailable' : ''}>{serverStatus === 'online' ? <CheckCircle2 size={13} /> : serverStatus === 'offline' ? <TriangleAlert size={13} /> : <RefreshCw className="spin" size={13} />} {serverStatus === 'online' ? 'Conectado' : serverStatus === 'offline' ? 'No disponible · toca para reintentar' : 'Comprobando'}</small></span><RefreshCw className={serverStatus === 'checking' ? 'spin' : ''} size={18} /></button>
        </div>
        <Button variant="danger" full icon={<LogOut size={19} />} onClick={logout} loading={loggingOut}>Cerrar sesión</Button>
        <p className="app-version">Suriana Vendedor · 0.1.0{import.meta.env.VITE_BUILD_CHANNEL ? ` · ${import.meta.env.VITE_BUILD_CHANNEL}` : ''}</p>
      </section>

      <BottomSheet open={branchSheet} onClose={() => setBranchSheet(false)} title="Elegir sucursal" description="El catálogo y los pedidos se mostrarán para esta sucursal.">
        <div className="selection-list">
          {user.sucursales.map((branch) => (
            <button key={branch.id} className={branch.id === activeBranchId ? 'selected' : ''} onClick={() => { onBranch(branch.id); setBranchSheet(false); }}>
              <span className="initials">{branch.clave.slice(0, 2).toUpperCase()}</span>
              <span><strong>{branch.nombre}</strong><small>{branch.clave}</small></span>
              <i />
            </button>
          ))}
        </div>
      </BottomSheet>
    </main>
  );
}
