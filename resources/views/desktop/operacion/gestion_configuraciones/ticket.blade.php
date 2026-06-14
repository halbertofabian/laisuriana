@extends('layouts.desktop')

@section('title', 'Personalizar ticket')

@push('desktop-styles')
    <style>
        .desktop-ticket-shell {
            display: grid;
            grid-template-columns: 360px minmax(0, 1fr);
            min-height: 640px;
        }
        .desktop-ticket-config {
            border-right: 1px solid var(--divider);
            background: var(--surface);
            min-width: 0;
        }
        .desktop-ticket-config__head,
        .desktop-ticket-preview__head {
            padding: 14px 16px;
            border-bottom: 1px solid var(--divider);
            background: var(--surface-alt);
        }
        .desktop-ticket-config__title,
        .desktop-ticket-preview__title {
            font-size: .88rem;
            font-weight: 600;
            margin: 0;
        }
        .desktop-ticket-config__sub,
        .desktop-ticket-preview__sub {
            margin: 3px 0 0;
            color: var(--text-2);
            font-size: .75rem;
        }
        .desktop-ticket-config__body {
            padding: 16px;
        }
        .desktop-ticket-preview {
            display: flex;
            flex-direction: column;
            min-width: 0;
            background:
                radial-gradient(circle at top left, rgba(15,108,189,.08), transparent 28%),
                linear-gradient(180deg, #f7f9fc 0%, #eef2f7 100%);
        }
        .desktop-ticket-preview__body {
            flex: 1 1 auto;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 28px 18px;
            overflow: auto;
        }
        .desktop-ticket-upload {
            position: relative;
            border: 1px dashed var(--stroke-strong);
            border-radius: var(--r-md);
            background: var(--surface-alt);
            min-height: 112px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            text-align: center;
        }
        .desktop-ticket-upload input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }
        .desktop-ticket-upload__content {
            pointer-events: none;
        }
        .desktop-ticket-upload__icon {
            width: 36px;
            height: 36px;
            margin: 0 auto 8px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--surface-sunken);
            color: var(--text-2);
        }
        .desktop-ticket-upload__title {
            display: block;
            font-size: .79rem;
            font-weight: 600;
        }
        .desktop-ticket-upload__hint {
            display: block;
            margin-top: 4px;
            color: var(--text-2);
            font-size: .73rem;
        }
        .desktop-ticket-logo-card {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            pointer-events: none;
        }
        .desktop-ticket-logo-card[hidden] {
            display: none;
        }
        .desktop-ticket-logo-card img {
            width: 54px;
            height: 54px;
            object-fit: contain;
            border-radius: 10px;
            border: 1px solid var(--stroke);
            background: #fff;
        }
        .desktop-ticket-upload__content[hidden] {
            display: none;
        }
        .desktop-ticket-upload__actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 8px;
        }
        .desktop-ticket-upload__actions[hidden] {
            display: none;
        }
        .desktop-ticket-paper-wrap {
            width: 268px;
            max-width: 100%;
        }
        .desktop-ticket-paper {
            background: #fffef9;
            border-radius: 6px 6px 0 0;
            padding: 16px 14px 14px;
            font-family: Menlo, Consolas, monospace;
            color: #16202b;
            box-shadow: 0 10px 28px rgba(15, 23, 42, .12);
        }
        .desktop-ticket-preview-logo[hidden] {
            display: none !important;
        }
        .desktop-ticket-edge {
            height: 18px;
            background-image:
                linear-gradient(135deg, #fffef9 33.33%, transparent 33.33%),
                linear-gradient(-135deg, #fffef9 33.33%, transparent 33.33%);
            background-size: 9px 18px;
            background-repeat: repeat-x;
            background-position: 0 0, 4.5px 0;
            box-shadow: 0 10px 28px rgba(15, 23, 42, .08);
        }
        .desktop-ticket-brand {
            text-align: center;
            font-size: .86rem;
            font-weight: 800;
        }
        .desktop-ticket-copy {
            white-space: pre-line;
            text-align: center;
            line-height: 1.55;
            color: #475569;
            font-size: .62rem;
        }
        .desktop-ticket-date {
            text-align: center;
            font-size: .56rem;
            color: #94a3b8;
            margin-top: 8px;
        }
        .desktop-ticket-rule {
            border: 0;
            border-top: 1px dashed #cbd5e1;
            margin: 10px 0;
        }
        .desktop-ticket-table,
        .desktop-ticket-totals {
            width: 100%;
            font-size: .6rem;
        }
        .desktop-ticket-table th,
        .desktop-ticket-table td,
        .desktop-ticket-totals td {
            padding: 2px 0;
            vertical-align: top;
        }
        .desktop-ticket-table th {
            color: #94a3b8;
            font-size: .54rem;
            text-transform: uppercase;
        }
        .desktop-ticket-muted {
            color: #64748b;
        }
        .desktop-ticket-right {
            text-align: right;
            font-weight: 600;
        }
        .desktop-ticket-total td {
            font-size: .68rem;
            font-weight: 800;
            color: #0f172a;
        }
        .desktop-ticket-barcode {
            height: 36px;
            margin-top: 10px;
            border-radius: 4px;
            background: repeating-linear-gradient(
                90deg,
                #111827 0 2px, #fffef9 2px 4px,
                #111827 4px 5px, #fffef9 5px 8px,
                #111827 8px 10px, #fffef9 10px 13px,
                #111827 13px 14px, #fffef9 14px 17px
            );
        }
        .desktop-ticket-folio {
            text-align: center;
            margin-top: 6px;
            font-size: .58rem;
            color: #94a3b8;
            letter-spacing: .08em;
        }
        @media (max-width: 980px) {
            .desktop-ticket-shell {
                grid-template-columns: 1fr;
            }
            .desktop-ticket-config {
                border-right: 0;
                border-bottom: 1px solid var(--divider);
            }
        }
    </style>
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        @php($activeSubmenu = 'ticket')
        @include('desktop.operacion.gestion_configuraciones._subnav')
        @if($permisosUI['ticket_editar'])
            <span class="desktop-toolbar__divider"></span>
            <button type="submit" form="desktop-ticket-form" class="desktop-btn desktop-btn--primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8"/><path d="M7 3v5h8"/></svg>
                Guardar cambios
            </button>
        @endif
    </div>
@endsection

@section('content')
    <section class="desktop-pane">
        <div class="desktop-ticket-shell">
            <div class="desktop-ticket-config">
                <div class="desktop-ticket-config__head">
                    <h2 class="desktop-ticket-config__title">Configuración del ticket</h2>
                    <p class="desktop-ticket-config__sub">Edita logo, encabezado y pie del ticket.</p>
                </div>

                <form id="desktop-ticket-form" action="{{ route('desktop.operacion.gestion_configuraciones.ticket.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="desktop-ticket-config__body">
                        <div class="desktop-field">
                            <label>Logo</label>
                            <input type="hidden" name="eliminar_logo" id="ticket-remove-logo" value="0">
                            <div class="desktop-ticket-upload">
                                <input type="file" name="logo" id="ticket-logo-input" accept=".jpg,.jpeg,.png,.webp">
                                <div class="desktop-ticket-logo-card" id="ticket-logo-card" @if(!$logoUrl) hidden @endif>
                                    <img src="{{ $logoUrl ?? '' }}" alt="Logo actual" id="ticket-logo-current">
                                    <div>
                                        <strong id="ticket-logo-label" style="display:block;font-size:.79rem;">{{ $logoUrl ? 'Logo actual' : 'Logo seleccionado' }}</strong>
                                        <span class="desktop-ticket-upload__hint" id="ticket-logo-hint">Selecciona una nueva imagen para reemplazarlo.</span>
                                    </div>
                                </div>
                                <div class="desktop-ticket-upload__content" id="ticket-logo-empty" @if($logoUrl) hidden @endif>
                                    <span class="desktop-ticket-upload__icon">
                                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5"/><path d="M12 3v12"/></svg>
                                    </span>
                                    <span class="desktop-ticket-upload__title">Subir logo</span>
                                    <span class="desktop-ticket-upload__hint">JPG, PNG o WEBP hasta 3 MB.</span>
                                </div>
                            </div>
                            <div class="desktop-ticket-upload__actions" id="ticket-logo-actions" @if(!$logoUrl) hidden @endif>
                                <button type="button" class="desktop-btn desktop-btn--default" id="ticket-remove-logo-button">
                                    Quitar logo
                                </button>
                            </div>
                        </div>

                        <div class="desktop-field" style="margin-top:16px;">
                            <label>Texto de encabezado</label>
                            <textarea name="texto_encabezado" id="texto_encabezado" rows="8">{{ old('texto_encabezado', $config->ptc_texto_encabezado) }}</textarea>
                        </div>

                        <div class="desktop-field" style="margin-top:16px;">
                            <label>Texto de pie</label>
                            <textarea name="texto_pie" id="texto_pie" rows="8">{{ old('texto_pie', $config->ptc_texto_pie) }}</textarea>
                        </div>
                    </div>
                </form>
            </div>

            <div class="desktop-ticket-preview">
                <div class="desktop-ticket-preview__head">
                    <h2 class="desktop-ticket-preview__title">Vista previa</h2>
                    <p class="desktop-ticket-preview__sub">Previsualización del ticket con datos de ejemplo.</p>
                </div>

                <div class="desktop-ticket-preview__body">
                    <div class="desktop-ticket-paper-wrap">
                        <div class="desktop-ticket-paper">
                            <img
                                @if($logoUrl) src="{{ $logoUrl }}" @endif
                                alt="Logo"
                                class="desktop-ticket-preview-logo"
                                id="ticket-preview-logo"
                                @if(!$logoUrl) hidden @endif
                                style="width:60px;height:60px;object-fit:contain;border:1px solid #e2e8f0;border-radius:10px;background:#fff;display:block;margin:0 auto 8px;"
                            >
                            <div class="desktop-ticket-brand">{{ $preview['empresa'] }}</div>
                            <div class="desktop-ticket-copy" id="ticket-preview-header">{{ $config->ptc_texto_encabezado }}</div>
                            <div class="desktop-ticket-date">{{ $preview['fecha'] }}</div>
                            <hr class="desktop-ticket-rule">

                            <table class="desktop-ticket-totals">
                                <tr><td class="desktop-ticket-muted">Almacen</td><td class="desktop-ticket-right">{{ $preview['almacen'] }}</td></tr>
                                <tr><td class="desktop-ticket-muted">Cliente</td><td class="desktop-ticket-right">{{ $preview['cliente'] }}</td></tr>
                                <tr><td class="desktop-ticket-muted">Articulos</td><td class="desktop-ticket-right">{{ $preview['articulos'] }}</td></tr>
                                <tr><td class="desktop-ticket-muted">Vendedores</td><td class="desktop-ticket-right">{{ $preview['vendedores'] }}</td></tr>
                            </table>

                            <hr class="desktop-ticket-rule">

                            <table class="desktop-ticket-table">
                                <thead>
                                    <tr>
                                        <th style="text-align:left;">Articulo</th>
                                        <th style="text-align:right;">Imp.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($preview['items'] as $item)
                                        <tr>
                                            <td>
                                                <div>{{ $item['nombre'] }}</div>
                                                <div class="desktop-ticket-muted">{{ $item['vendedor'] }} · {{ $item['cantidad'] }}</div>
                                            </td>
                                            <td class="desktop-ticket-right">{{ $item['importe'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <hr class="desktop-ticket-rule">

                            <table class="desktop-ticket-totals">
                                <tr><td class="desktop-ticket-muted">Subtotal</td><td class="desktop-ticket-right">$1,500.00</td></tr>
                                <tr><td class="desktop-ticket-muted">Descuento</td><td class="desktop-ticket-right">$0.00</td></tr>
                                <tr class="desktop-ticket-total"><td>Total</td><td class="desktop-ticket-right">$1,500.00</td></tr>
                            </table>

                            <hr class="desktop-ticket-rule">
                            <div class="desktop-ticket-copy" id="ticket-preview-footer">{{ $config->ptc_texto_pie }}</div>
                            <div class="desktop-ticket-barcode"></div>
                            <div class="desktop-ticket-folio">{{ $preview['folio'] }}</div>
                        </div>
                        <div class="desktop-ticket-edge"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('desktop-scripts')
    <script>
        (() => {
            const logoInput = document.getElementById('ticket-logo-input');
            const logoCard = document.getElementById('ticket-logo-card');
            const logoCurrent = document.getElementById('ticket-logo-current');
            const logoEmpty = document.getElementById('ticket-logo-empty');
            const logoLabel = document.getElementById('ticket-logo-label');
            const logoHint = document.getElementById('ticket-logo-hint');
            const logoActions = document.getElementById('ticket-logo-actions');
            const removeLogoButton = document.getElementById('ticket-remove-logo-button');
            const removeLogoInput = document.getElementById('ticket-remove-logo');
            const previewLogo = document.getElementById('ticket-preview-logo');
            const headerInput = document.getElementById('texto_encabezado');
            const footerInput = document.getElementById('texto_pie');
            const previewHeader = document.getElementById('ticket-preview-header');
            const previewFooter = document.getElementById('ticket-preview-footer');

            if (!logoInput || !headerInput || !footerInput || !previewHeader || !previewFooter) {
                return;
            }

            let previewObjectUrl = null;

            const setPreviewCopy = (source, target, fallback = '') => {
                const value = source.value.trim();
                target.textContent = value || fallback;
            };

            const showLogoState = (url, label, hint = 'Selecciona una nueva imagen para reemplazarlo.') => {
                if (!logoCard || !logoCurrent || !previewLogo) {
                    return;
                }

                logoCard.hidden = false;
                logoCurrent.src = url;
                logoLabel.textContent = label;
                logoHint.textContent = hint;

                if (logoEmpty) {
                    logoEmpty.hidden = true;
                }

                if (logoActions) {
                    logoActions.hidden = false;
                }

                if (removeLogoInput) {
                    removeLogoInput.value = '0';
                }

                previewLogo.src = url;
                previewLogo.hidden = false;
            };

            const clearLogoState = () => {
                if (previewObjectUrl) {
                    URL.revokeObjectURL(previewObjectUrl);
                    previewObjectUrl = null;
                }

                if (logoCard) {
                    logoCard.hidden = true;
                }

                if (logoEmpty) {
                    logoEmpty.hidden = false;
                }

                if (logoActions) {
                    logoActions.hidden = true;
                }

                if (logoCurrent) {
                    logoCurrent.src = '';
                }

                if (previewLogo) {
                    previewLogo.src = '';
                    previewLogo.hidden = true;
                }

                if (logoInput) {
                    logoInput.value = '';
                }

                if (removeLogoInput) {
                    removeLogoInput.value = '1';
                }
            };

            headerInput.addEventListener('input', () => setPreviewCopy(headerInput, previewHeader));
            footerInput.addEventListener('input', () => setPreviewCopy(footerInput, previewFooter, 'Gracias por su compra'));

            logoInput.addEventListener('change', (event) => {
                const [file] = event.target.files || [];
                if (!file || !file.type.startsWith('image/')) {
                    return;
                }

                if (previewObjectUrl) {
                    URL.revokeObjectURL(previewObjectUrl);
                }

                previewObjectUrl = URL.createObjectURL(file);
                showLogoState(previewObjectUrl, file.name);
            });

            removeLogoButton?.addEventListener('click', () => {
                clearLogoState();
            });

            setPreviewCopy(headerInput, previewHeader);
            setPreviewCopy(footerInput, previewFooter, 'Gracias por su compra');
        })();
    </script>
@endpush
