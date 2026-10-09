<div x-cloak x-show="mostrarDatosFacturacion" class="variant-modal variant-modal--front">
    <section class="variant-modal__card datos-facturacion" x-ref="datosFacturacionModal" role="dialog" aria-modal="true" aria-labelledby="datos-facturacion-titulo">
        <div class="variant-modal__head" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;">
            <span id="datos-facturacion-titulo"><i class="ti tabler-file-invoice"></i> Datos de facturación</span>
            <button type="button" class="pos-btn pos-btn--ghost" @click="cerrarDatosFacturacion()" :disabled="guardandoDatosFacturacion" aria-label="Cerrar datos de facturación">✕</button>
        </div>
        <form x-ref="datosFacturacionForm" @submit.prevent="guardarDatosFacturacion()" style="padding:1rem;">
            <p style="margin:0 0 1rem;color:var(--ls-text-muted);font-size:.82rem;">Alta rápida de cliente. Los campos con * son obligatorios.</p>
            <div x-show="errorDatosFacturacion" x-text="errorDatosFacturacion" role="alert" class="datos-facturacion__error" style="margin-bottom:.75rem;"></div>
            <fieldset :disabled="guardandoDatosFacturacion" class="datos-facturacion__grid">
                @foreach ([
                    'cli_razon_social' => ['Razón social', 'text', 180, 'Nombre completo o razón social', true],
                    'cli_rfc' => ['RFC', 'text', 13, 'RFC de 12 o 13 caracteres', true],
                ] as $campo => [$etiqueta, $tipo, $maximo, $ejemplo, $obligatorio])
                    <div class="datos-facturacion__wide">
                        <label class="pos-field__label" for="facturacion-{{ $campo }}">{{ $etiqueta }} *</label>
                        <input id="facturacion-{{ $campo }}" name="{{ $campo }}" type="{{ $tipo }}" class="pos-input" maxlength="{{ $maximo }}" placeholder="{{ $ejemplo }}" required :aria-invalid="!!erroresDatosFacturacion.{{ $campo }}" aria-describedby="error-{{ $campo }}">
                        <small id="error-{{ $campo }}" class="datos-facturacion__error" x-text="erroresDatosFacturacion.{{ $campo }}?.[0]"></small>
                    </div>
                @endforeach
                <div class="datos-facturacion__wide pos-vendedor-select-wrap">
                    <label class="pos-field__label" for="facturacion-regimen">Régimen fiscal *</label>
                    <select id="facturacion-regimen" name="cli_regimen_fiscal" x-ref="regimenFiscalSelect" class="pos-input" aria-describedby="error-cli_regimen_fiscal" :aria-invalid="!!erroresDatosFacturacion.cli_regimen_fiscal">
                        <option value="">Selecciona un régimen fiscal</option>
                        @foreach (config('datos_facturacion.regimenes') as $clave => $nombre)
                            <option value="{{ $clave }}">{{ $clave }} · {{ $nombre }}</option>
                        @endforeach
                    </select>
                    <small id="error-cli_regimen_fiscal" class="datos-facturacion__error" x-text="erroresDatosFacturacion.cli_regimen_fiscal?.[0]"></small>
                </div>
                @foreach ([
                    'cli_uso_cfdi' => ['Uso de CFDI', 'text', 150, 'Ej. G03 · Gastos en general', true, 'facturacion-usos'],
                    'cli_cp' => ['Código postal fiscal', 'text', 5, '5 dígitos', true, null],
                    'cli_forma_pago' => ['Forma de pago', 'text', 100, 'Selecciona o escribe', true, 'facturacion-pagos'],
                    'cli_telefono' => ['Teléfono', 'tel', 25, 'Opcional', false, null],
                    'cli_email' => ['Correo', 'email', 140, 'correo@ejemplo.com (opcional)', false, null],
                ] as $campo => [$etiqueta, $tipo, $maximo, $ejemplo, $obligatorio, $lista])
                    <div @class(['datos-facturacion__wide' => $campo === 'cli_email'])>
                        <label class="pos-field__label" for="facturacion-{{ $campo }}">{{ $etiqueta }}{{ $obligatorio ? ' *' : '' }}</label>
                        <input id="facturacion-{{ $campo }}" name="{{ $campo }}" type="{{ $tipo }}" class="pos-input" maxlength="{{ $maximo }}" placeholder="{{ $ejemplo }}" @required($obligatorio) @if($lista) list="{{ $lista }}" @endif @if($campo === 'cli_cp') inputmode="numeric" pattern="[0-9]{5}" @endif :aria-invalid="!!erroresDatosFacturacion.{{ $campo }}" aria-describedby="error-{{ $campo }}">
                        <small id="error-{{ $campo }}" class="datos-facturacion__error" x-text="erroresDatosFacturacion.{{ $campo }}?.[0]"></small>
                    </div>
                @endforeach
            </fieldset>
            <datalist id="facturacion-usos">
                <option value="G01 · Adquisición de mercancías"></option>
                <option value="G03 · Gastos en general"></option>
            </datalist>
            <datalist id="facturacion-pagos">
                <option value="Efectivo"></option>
                <option value="Transferencia"></option>
                <option value="Tarjeta de crédito"></option>
                <option value="Tarjeta de débito"></option>
                <option value="Crédito"></option>
            </datalist>
            <div style="display:flex;justify-content:flex-end;gap:.5rem;margin-top:1rem;">
                <button type="button" class="pos-btn pos-btn--ghost" @click="cerrarDatosFacturacion()" :disabled="guardandoDatosFacturacion">Cancelar</button>
                <button type="submit" class="pos-btn pos-btn--success" :disabled="guardandoDatosFacturacion" x-text="guardandoDatosFacturacion ? 'Guardando…' : 'Guardar y seleccionar cliente'"></button>
            </div>
        </form>
    </section>
</div>
