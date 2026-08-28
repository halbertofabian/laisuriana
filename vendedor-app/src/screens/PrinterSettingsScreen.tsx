import { Bluetooth, BluetoothOff, Check, ExternalLink, Printer, RefreshCw } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { AppHeader } from '../components/AppHeader';
import { Button } from '../components/Button';
import { bluetoothPrinter, printerErrorMessage, printerProfileNames } from '../services/bluetoothPrinter';
import type { BluetoothPrinterDevice, PrinterConfig, PrinterLanguage, PrinterPaperWidth } from '../types';

interface PrinterStatus {
  supported: boolean;
  enabled: boolean;
  permissionGranted: boolean;
}

const profiles: Array<{ id: PrinterLanguage; title: string; description: string }> = [
  { id: 'escpos', title: 'ESC/POS', description: 'MP-2 y térmicas genéricas' },
  { id: 'zpl', title: 'ZPL', description: 'Zebra de escritorio o móvil' },
  { id: 'cpcl', title: 'CPCL', description: 'Zebra móvil compatible' },
];

export function PrinterSettingsScreen({
  config,
  onChange,
  onBack,
}: {
  config: PrinterConfig | null;
  onChange: (config: PrinterConfig) => void;
  onBack: () => void;
}) {
  const [devices, setDevices] = useState<BluetoothPrinterDevice[]>([]);
  const [status, setStatus] = useState<PrinterStatus | null>(null);
  const [loading, setLoading] = useState(true);
  const [testing, setTesting] = useState(false);
  const [notice, setNotice] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

  const loadDevices = useCallback(async () => {
    setLoading(true);
    setNotice(null);
    try {
      const result = await bluetoothPrinter.getPairedDevices();
      setStatus(result);
      setDevices(result.devices);
    } catch (error) {
      setNotice({ type: 'error', text: printerErrorMessage(error) });
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (bluetoothPrinter.isNative) void loadDevices();
    else setLoading(false);
  }, [loadDevices]);

  const requestAccess = async () => {
    setLoading(true);
    try {
      await bluetoothPrinter.requestAccess();
      await loadDevices();
    } catch (error) {
      setNotice({ type: 'error', text: printerErrorMessage(error) });
      setLoading(false);
    }
  };

  const chooseDevice = (device: BluetoothPrinterDevice) => {
    const looksLikeZebra = /zebra|zq|zd|zt/i.test(device.name);
    const next: PrinterConfig = {
      ...device,
      language: config?.address === device.address ? config.language : looksLikeZebra ? 'zpl' : 'escpos',
      paperWidth: config?.address === device.address ? config.paperWidth : looksLikeZebra ? '80' : '58',
    };
    onChange(next);
    setNotice(null);
  };

  const updateProfile = (language: PrinterLanguage) => {
    if (config) onChange({ ...config, language });
    setNotice(null);
  };

  const updateWidth = (paperWidth: PrinterPaperWidth) => {
    if (config) onChange({ ...config, paperWidth });
    setNotice(null);
  };

  const printTest = async () => {
    if (!config) return;
    setTesting(true);
    setNotice(null);
    try {
      await bluetoothPrinter.printTest(config);
      setNotice({ type: 'success', text: 'Prueba enviada. Revisa el papel impreso.' });
    } catch (error) {
      setNotice({ type: 'error', text: printerErrorMessage(error) });
    } finally {
      setTesting(false);
    }
  };

  if (!bluetoothPrinter.isNative) {
    return (
      <main className="screen screen-enter">
        <AppHeader title="Impresora" onBack={onBack} />
        <section className="screen-content">
          <div className="empty-state">
            <span className="empty-state__icon"><Bluetooth size={25} /></span>
            <h3>Disponible en Android</h3>
            <p>La conexión Bluetooth se configura desde la aplicación instalada en el teléfono.</p>
          </div>
        </section>
      </main>
    );
  }

  return (
    <main className="screen screen--with-action screen-enter">
      <AppHeader title="Impresora" eyebrow="CONFIGURACIÓN" onBack={onBack} />
      <section className="screen-content printer-settings">
        <div className="printer-intro">
          <span className="printer-intro__icon"><Printer size={27} /></span>
          <div><h2>Impresión Bluetooth</h2><p>Elige una impresora emparejada y su formato.</p></div>
        </div>

        {!status?.permissionGranted && !loading && (
          <div className="printer-permission-card">
            <Bluetooth size={23} />
            <div><strong>Permitir dispositivos cercanos</strong><p>Necesitamos este permiso para ver tus impresoras emparejadas.</p></div>
            <Button full onClick={requestAccess}>Permitir Bluetooth</Button>
          </div>
        )}

        {status?.permissionGranted && !status.enabled && (
          <div className="printer-permission-card">
            <BluetoothOff size={23} />
            <div><strong>Bluetooth está apagado</strong><p>Actívalo para conectar la impresora.</p></div>
            <Button full onClick={() => void bluetoothPrinter.openBluetoothSettings()}>Abrir Bluetooth</Button>
          </div>
        )}

        {status?.permissionGranted && status.enabled && (
          <>
            <div className="printer-section-heading">
              <div><span>IMPRESORAS EMPAREJADAS</span><small>{devices.length} disponibles</small></div>
              <button onClick={() => void loadDevices()} aria-label="Actualizar impresoras"><RefreshCw size={18} className={loading ? 'spin' : ''} /></button>
            </div>
            {devices.length > 0 ? (
              <div className="printer-device-list">
                {devices.map((device) => {
                  const selected = config?.address === device.address;
                  return (
                    <button key={device.address} className={selected ? 'selected' : ''} onClick={() => chooseDevice(device)}>
                      <span className="printer-device-icon"><Printer size={21} /></span>
                      <span><strong>{device.name}</strong><small><Bluetooth size={13} /> Emparejada</small></span>
                      <span className="printer-radio">{selected && <i />}</span>
                    </button>
                  );
                })}
              </div>
            ) : !loading && (
              <div className="printer-empty">
                <p>No hay impresoras emparejadas.</p>
                <button onClick={() => void bluetoothPrinter.openBluetoothSettings()}>Abrir ajustes <ExternalLink size={15} /></button>
              </div>
            )}
          </>
        )}

        {config && (
          <div className="printer-options">
            <p className="settings-label">FORMATO DE IMPRESIÓN</p>
            <div className="printer-profile-list">
              {profiles.map((profile) => (
                <button key={profile.id} className={config.language === profile.id ? 'selected' : ''} onClick={() => updateProfile(profile.id)}>
                  <span><strong>{profile.title}</strong><small>{profile.description}</small></span>
                  {config.language === profile.id && <Check size={18} />}
                </button>
              ))}
            </div>
            <p className="settings-label">ANCHO DEL PAPEL</p>
            <div className="paper-width-picker">
              {(['58', '80'] as PrinterPaperWidth[]).map((width) => (
                <button key={width} className={config.paperWidth === width ? 'selected' : ''} onClick={() => updateWidth(width)}>
                  <strong>{width} mm</strong><small>{width === '58' ? 'Rollo compacto' : 'Ticket amplio'}</small>
                </button>
              ))}
            </div>
            <div className="printer-current-summary">
              <span>Configuración actual</span>
              <strong>{config.name} · {printerProfileNames[config.language]} · {config.paperWidth} mm</strong>
            </div>
          </div>
        )}

        {notice && <div className={`inline-notice ${notice.type === 'success' ? 'inline-notice--success' : 'inline-notice--error'}`} role="status">{notice.type === 'success' && <Check size={18} />}{notice.text}</div>}
      </section>

      <div className="sticky-action sticky-action--plain">
        <Button full onClick={printTest} loading={testing} disabled={!config || !status?.enabled} icon={<Printer size={20} />}>Imprimir prueba</Button>
      </div>
    </main>
  );
}
