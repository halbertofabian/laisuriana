@extends('layouts.desktop')

@section('title', 'Impresoras')

@push('desktop-styles')
    <style>
        .desktop-printer-shell {
            display: grid;
            grid-template-columns: 400px minmax(0, 1fr);
            min-height: 640px;
        }
        .desktop-printer-config {
            border-right: 1px solid var(--divider);
            background: var(--surface);
            min-width: 0;
        }
        .desktop-printer-config__head,
        .desktop-printer-panel__head {
            padding: 14px 16px;
            border-bottom: 1px solid var(--divider);
            background: var(--surface-alt);
        }
        .desktop-printer-config__title,
        .desktop-printer-panel__title {
            font-size: .88rem;
            font-weight: 600;
            margin: 0;
        }
        .desktop-printer-config__sub,
        .desktop-printer-panel__sub {
            margin: 3px 0 0;
            color: var(--text-2);
            font-size: .75rem;
        }
        .desktop-printer-config__body {
            padding: 16px;
        }
        .desktop-printer-panel {
            min-width: 0;
            background:
                radial-gradient(circle at top left, rgba(15,108,189,.08), transparent 28%),
                linear-gradient(180deg, #f7f9fc 0%, #eef2f7 100%);
        }
        .desktop-printer-panel__body {
            padding: 18px;
            display: grid;
            gap: 16px;
        }
        .desktop-printer-card {
            background: rgba(255, 255, 255, .9);
            border: 1px solid rgba(148, 163, 184, .18);
            border-radius: var(--r-lg);
            box-shadow: 0 12px 24px rgba(15, 23, 42, .05);
            overflow: hidden;
        }
        .desktop-printer-card__body {
            padding: 16px;
        }
        .desktop-printer-kv {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }
        .desktop-printer-kv__item {
            border: 1px solid var(--stroke);
            border-radius: var(--r-md);
            background: #fff;
            padding: 12px;
        }
        .desktop-printer-kv__label {
            display: block;
            margin-bottom: 4px;
            color: var(--text-2);
            font-size: .72rem;
        }
        .desktop-printer-kv__value {
            display: block;
            font-weight: 600;
            font-size: .84rem;
            color: var(--text);
            word-break: break-word;
        }
        .desktop-printer-note {
            margin: 0;
            color: var(--text-2);
            font-size: .75rem;
            line-height: 1.55;
        }
        .desktop-printer-help {
            margin-top: 16px;
            border: 1px solid var(--stroke);
            border-radius: var(--r-md);
            background: var(--surface-alt);
            padding: 14px;
        }
        .desktop-printer-help__title {
            margin: 0 0 8px;
            font-size: .8rem;
            font-weight: 700;
            color: var(--text);
        }
        .desktop-printer-help__list {
            margin: 0;
            padding-left: 18px;
            color: var(--text-2);
            font-size: .75rem;
            line-height: 1.55;
        }
        .desktop-printer-help__list li + li {
            margin-top: 6px;
        }
        .desktop-printer-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: .74rem;
            font-weight: 600;
            background: rgba(15, 108, 189, .1);
            color: #0f6cbd;
        }
        @media (max-width: 980px) {
            .desktop-printer-shell {
                grid-template-columns: 1fr;
            }
            .desktop-printer-config {
                border-right: 0;
                border-bottom: 1px solid var(--divider);
            }
            .desktop-printer-kv {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        @php($activeSubmenu = 'impresoras')
        @include('desktop.operacion.gestion_configuraciones._subnav')
        @if($permisosUI['impresora_editar'])
            <span class="desktop-toolbar__divider"></span>
            <button type="submit" form="desktop-printer-form" class="desktop-btn desktop-btn--primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8"/><path d="M7 3v5h8"/></svg>
                Guardar cambios
            </button>
        @endif
    </div>
@endsection

@section('content')
    <section class="desktop-pane">
        <div class="desktop-printer-shell">
            <div class="desktop-printer-config">
                <div class="desktop-printer-config__head">
                    <h2 class="desktop-printer-config__title">Configuracion de impresora</h2>
                    <p class="desktop-printer-config__sub">Esta configuracion aplica al dispositivo actual y no depende del usuario, caja o sucursal.</p>
                </div>

                <form id="desktop-printer-form" action="{{ route('desktop.operacion.gestion_configuraciones.impresoras.store') }}" method="POST" data-ls-autocomplete="admin">
                    @csrf
                    <div class="desktop-printer-config__body">
                        @if(session('success'))
                            <div class="alert alert-success mb-3">{{ session('success') }}</div>
                        @endif

                        @if($errors->any())
                            <div class="alert alert-danger mb-3">
                                <strong>No fue posible guardar la configuracion.</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="desktop-field">
                            <label>Nombre del dispositivo</label>
                            <input type="text" name="dip_nombre_dispositivo" maxlength="120" value="{{ $impresoraDispositivo['nombre_dispositivo'] }}" placeholder="Ej. MOSTRADOR 1" required>
                        </div>
                        <div class="desktop-field">
                            <label>Tipo de conexion</label>
                            <select name="dip_tipo_conexion" id="dip_tipo_conexion" required>
                                <option value="usb" @selected($impresoraDispositivo['tipo_conexion'] === 'usb')>USB con agente local</option>
                                <option value="red" @selected($impresoraDispositivo['tipo_conexion'] === 'red')>Impresora por red</option>
                            </select>
                        </div>
                        <div class="desktop-field">
                            <label>Nombre de la impresora</label>
                            <input type="text" name="dip_nombre_impresora" maxlength="160" value="{{ $impresoraDispositivo['nombre_impresora'] }}" placeholder="Ej. POS-80 / EPSON TM-T20 / BROTHER QL-800" required>
                        </div>

                        <div class="desktop-field desktop-impresora-red">
                            <label>IP o hostname</label>
                            <input type="text" name="dip_host" maxlength="190" value="{{ $impresoraDispositivo['host'] }}" placeholder="Ej. 192.168.1.80" data-ls-uppercase="off">
                        </div>
                        <div class="desktop-field desktop-impresora-red">
                            <label>Puerto</label>
                            <input type="number" name="dip_puerto" min="1" max="65535" value="{{ $impresoraDispositivo['puerto'] }}" placeholder="9100">
                        </div>
                        <div class="desktop-field desktop-impresora-red">
                            <label>Modelo / comando</label>
                            <input type="text" name="dip_controlador" maxlength="80" value="{{ $impresoraDispositivo['controlador'] }}" placeholder="Ej. ESC/POS">
                        </div>

                        <div class="desktop-field desktop-impresora-usb">
                            <label>URL local del agente</label>
                            <input type="url" name="dip_agent_url" maxlength="255" value="{{ $impresoraDispositivo['agent_url'] }}" placeholder="http://127.0.0.1:17890" data-ls-uppercase="off">
                        </div>

                        <div class="desktop-form-actions mt-3">
                            <a href="{{ $descargaAgenteUrl }}" class="desktop-btn desktop-btn--ghost">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
                                Descargar instalador para Windows
                            </a>
                        </div>

                        <div class="desktop-printer-help">
                            <h3 class="desktop-printer-help__title">Que necesita tener esta computadora</h3>
                            <ul class="desktop-printer-help__list">
                                <li><strong>Conexion a internet o red interna:</strong> esta computadora debe poder abrir el sistema web desde su navegador.</li>
                                <li><strong>Si la impresora es USB:</strong> instala <strong>LAISURIANAPRINT-SOFTMOR</strong> en esta misma computadora usando el boton de descarga de arriba.</li>
                                <li><strong>Si la impresora es por red:</strong> esta computadora debe estar conectada a la misma red de la impresora y debes conocer su IP y puerto.</li>
                                <li><strong>Impresora disponible en Windows:</strong> la impresora debe estar correctamente instalada y lista para usarse en esta computadora cuando aplique.</li>
                                <li><strong>Configuracion por equipo:</strong> cada computadora que vaya a imprimir debe configurarse por separado.</li>
                            </ul>
                        </div>
                    </div>
                </form>
            </div>

            <div class="desktop-printer-panel">
                <div class="desktop-printer-panel__head">
                    <h2 class="desktop-printer-panel__title">Resumen del dispositivo</h2>
                    <p class="desktop-printer-panel__sub">Referencia rapida para validar como quedara configurada la impresion en este equipo.</p>
                </div>

                <div class="desktop-printer-panel__body">
                    <div class="desktop-printer-card">
                        <div class="desktop-printer-card__body">
                            <span class="desktop-printer-chip" id="desktop-printer-type-chip">
                                {{ $impresoraDispositivo['tipo_conexion'] === 'usb' ? 'USB con agente local' : 'Impresora por red' }}
                            </span>
                            <div class="desktop-printer-kv mt-3">
                                <div class="desktop-printer-kv__item">
                                    <span class="desktop-printer-kv__label">Identificador del dispositivo</span>
                                    <span class="desktop-printer-kv__value">{{ $impresoraDispositivo['device_id'] }}</span>
                                </div>
                                <div class="desktop-printer-kv__item">
                                    <span class="desktop-printer-kv__label">Nombre del dispositivo</span>
                                    <span class="desktop-printer-kv__value" id="desktop-printer-preview-device">{{ $impresoraDispositivo['nombre_dispositivo'] }}</span>
                                </div>
                                <div class="desktop-printer-kv__item">
                                    <span class="desktop-printer-kv__label">Impresora</span>
                                    <span class="desktop-printer-kv__value" id="desktop-printer-preview-name">{{ $impresoraDispositivo['nombre_impresora'] ?: 'SIN DEFINIR' }}</span>
                                </div>
                                <div class="desktop-printer-kv__item desktop-impresora-red-preview">
                                    <span class="desktop-printer-kv__label">Host / puerto</span>
                                    <span class="desktop-printer-kv__value" id="desktop-printer-preview-network">{{ ($impresoraDispositivo['host'] ?: 'SIN DEFINIR') . ':' . ($impresoraDispositivo['puerto'] ?: '9100') }}</span>
                                </div>
                                <div class="desktop-printer-kv__item desktop-impresora-usb-preview">
                                    <span class="desktop-printer-kv__label">URL del agente local</span>
                                    <span class="desktop-printer-kv__value" id="desktop-printer-preview-agent">{{ $impresoraDispositivo['agent_url'] ?: 'SIN DEFINIR' }}</span>
                                </div>
                                <div class="desktop-printer-kv__item">
                                    <span class="desktop-printer-kv__label">Controlador / comando</span>
                                    <span class="desktop-printer-kv__value" id="desktop-printer-preview-driver">{{ $impresoraDispositivo['controlador'] ?: 'SIN DEFINIR' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="desktop-printer-card">
                        <div class="desktop-printer-card__body">
                            <p class="desktop-printer-note mb-2"><strong>USB:</strong> requiere que <strong>LAISURIANAPRINT-SOFTMOR</strong> quede instalado, residente y configurado para iniciar automaticamente con Windows.</p>
                            <p class="desktop-printer-note mb-2"><strong>Red:</strong> el sistema guarda IP/hostname, puerto y comando base para enviar trabajos al dispositivo configurado en esta computadora.</p>
                            <p class="desktop-printer-note mb-0"><strong>HTTPS:</strong> cuando la app corra publicada en HTTPS, el agente local idealmente debe responder en <strong>https://127.0.0.1</strong> o <strong>https://localhost</strong> para evitar bloqueos del navegador.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('desktop-scripts')
    <script>
        (function () {
            const $type = $('#dip_tipo_conexion');
            const $form = $('#desktop-printer-form');

            function syncPrinterTypeFields() {
                const type = $type.val();
                const isUsb = type === 'usb';

                $('.desktop-impresora-usb').toggle(isUsb);
                $('.desktop-impresora-red').toggle(!isUsb);
                $('.desktop-impresora-usb-preview').toggle(isUsb);
                $('.desktop-impresora-red-preview').toggle(!isUsb);

                $form.find('[name="dip_agent_url"]').prop('required', isUsb);
                $form.find('[name="dip_host"]').prop('required', !isUsb);
                $form.find('[name="dip_puerto"]').prop('required', !isUsb);

                $('#desktop-printer-type-chip').text(isUsb ? 'USB con agente local' : 'Impresora por red');
            }

            function syncPreview() {
                const host = String($form.find('[name="dip_host"]').val() || '').trim();
                const puerto = String($form.find('[name="dip_puerto"]').val() || '').trim();

                $('#desktop-printer-preview-device').text($form.find('[name="dip_nombre_dispositivo"]').val() || 'SIN DEFINIR');
                $('#desktop-printer-preview-name').text($form.find('[name="dip_nombre_impresora"]').val() || 'SIN DEFINIR');
                $('#desktop-printer-preview-driver').text($form.find('[name="dip_controlador"]').val() || 'SIN DEFINIR');
                $('#desktop-printer-preview-agent').text($form.find('[name="dip_agent_url"]').val() || 'SIN DEFINIR');
                $('#desktop-printer-preview-network').text((host || 'SIN DEFINIR') + ':' + (puerto || '9100'));
            }

            syncPrinterTypeFields();
            syncPreview();

            $type.on('change', function () {
                syncPrinterTypeFields();
                syncPreview();
            });

            $form.on('input change', 'input, select', syncPreview);
        })();
    </script>
@endpush
