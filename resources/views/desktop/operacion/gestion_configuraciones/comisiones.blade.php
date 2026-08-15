@extends('layouts.desktop')

@section('title', 'Configuración de comisiones')

@php
    $cerrado = ($periodo?->cpe_estatus ?? null) === 'cerrado';
    $bloqueado = $cerrado || $sucursalSoloLectura;
    $sucursalSeleccionada = $sucursalesComision->firstWhere('scl_id', $sucursalSeleccionadaId);
    $almacenesSeleccionados = old('almacen_ids', $periodo?->almacenes?->pluck('alm_id')->map(fn ($id) => (string) $id)->all() ?? []);
    $pasoInicial = $errors->hasAny(['vendedores', 'vendedores.*']) ? 3 : ($errors->hasAny(['grupos', 'grupos.*']) ? 2 : 1);
    $esRecalculo = $periodo?->cpe_estatus === 'calculado';
    $puedeEjecutarCalculo = $esRecalculo ? $puedeRecalcular : $puedeCalcular;
@endphp

@push('desktop-styles')
    <style>
        .commission-shell { height: 100%; overflow: auto; background: var(--surface-alt); }
        .commission-workspace { width: min(100%, 1040px); margin: 0 auto; padding: 20px; }
        .commission-intro { display: flex; align-items: flex-start; justify-content: space-between; gap: 20px; margin-bottom: 18px; }
        .commission-intro h1 { margin: 0; font-size: 1.05rem; font-weight: 650; letter-spacing: -.01em; }
        .commission-intro p { max-width: 620px; margin: 5px 0 0; color: var(--text-2); font-size: .8rem; line-height: 1.5; }
        .commission-context { display: grid; grid-template-columns: minmax(190px, 1fr) minmax(170px, .8fr); gap: 10px; min-width: 390px; }
        .commission-context .desktop-field { min-width: 0; }

        .commission-steps { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); margin-bottom: 12px; padding: 4px; border: 1px solid var(--stroke); border-radius: var(--r-md); background: var(--surface); box-shadow: var(--shadow-2); }
        .commission-step { display: flex; align-items: center; gap: 9px; min-width: 0; padding: 9px 11px; border: 0; border-radius: var(--r-sm); background: transparent; color: var(--text-2); text-align: left; cursor: pointer; }
        .commission-step:hover { background: var(--surface-sunken); color: var(--text); }
        .commission-step.is-active { background: var(--brand-soft); color: var(--brand); }
        .commission-step__number { display: inline-flex; flex: 0 0 24px; align-items: center; justify-content: center; width: 24px; height: 24px; border: 1px solid var(--stroke-strong); border-radius: 50%; background: var(--surface); font-size: .72rem; font-weight: 700; }
        .commission-step.is-active .commission-step__number { border-color: var(--brand); background: var(--brand); color: var(--on-brand); }
        .commission-step__text { min-width: 0; }
        .commission-step__title, .commission-step__meta { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .commission-step__title { font-size: .78rem; font-weight: 650; }
        .commission-step__meta { margin-top: 1px; color: var(--text-3); font-size: .68rem; }

        .commission-panel { border: 1px solid var(--stroke); border-radius: var(--r-md); background: var(--surface); box-shadow: var(--shadow-2); overflow: hidden; }
        .commission-panel[hidden], .commission-group-panel[hidden] { display: none; }
        .commission-panel__head { padding: 16px 18px; border-bottom: 1px solid var(--divider); }
        .commission-panel__head h2 { margin: 0; font-size: .94rem; font-weight: 650; }
        .commission-panel__head p { margin: 4px 0 0; color: var(--text-2); font-size: .76rem; line-height: 1.45; }
        .commission-panel__body { padding: 18px; }
        .commission-panel__foot { display: flex; align-items: center; gap: 10px; padding: 12px 18px; border-top: 1px solid var(--divider); background: var(--surface-alt); }
        .commission-panel__foot-note { margin: 0 auto; color: var(--text-3); font-size: .72rem; }
        .commission-panel__actions { display: flex; gap: 6px; margin-left: auto; }
        .commission-block + .commission-block { margin-top: 22px; padding-top: 20px; border-top: 1px solid var(--divider); }
        .commission-block__head { margin-bottom: 12px; }
        .commission-block__title { margin: 0; font-size: .84rem; font-weight: 650; }
        .commission-block__hint { margin: 3px 0 0; color: var(--text-2); font-size: .73rem; line-height: 1.4; }

        .commission-rule-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
        .commission-rule { padding: 13px; border: 1px solid var(--stroke); border-radius: var(--r-md); background: var(--surface-alt); }
        .commission-rule__number { display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; margin-bottom: 9px; border-radius: 50%; background: var(--surface-sunken); color: var(--text-2); font-size: .7rem; font-weight: 700; }
        .commission-rule .desktop-field label { font-size: .76rem; }
        .commission-rule__explain { min-height: 34px; margin-top: 7px; color: var(--text-2); font-size: .7rem; line-height: 1.35; }
        .commission-checks { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
        .commission-check, .commission-line { display: flex; align-items: flex-start; gap: 8px; padding: 9px 10px; border: 1px solid var(--stroke); border-radius: var(--r-md); background: var(--surface); color: var(--text); font-size: .78rem; cursor: pointer; }
        .commission-check:has(input:checked), .commission-line:has(input:checked) { border-color: var(--brand); background: var(--brand-soft); }
        .commission-check input, .commission-line input { margin-top: 2px; }

        .commission-group-tabs { display: inline-flex; gap: 2px; margin-bottom: 14px; padding: 2px; border-radius: var(--r-md); background: var(--surface-sunken); }
        .commission-group-tab { height: 29px; padding: 0 14px; border: 0; border-radius: var(--r-sm); background: transparent; color: var(--text-2); font: inherit; font-size: .78rem; font-weight: 600; cursor: pointer; }
        .commission-group-tab.is-active { background: var(--surface); color: var(--brand); box-shadow: var(--shadow-2); }
        .commission-group-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 260px)); gap: 14px; margin-bottom: 18px; }
        .commission-lines-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 8px; }
        .commission-lines-head strong { font-size: .78rem; }
        .commission-count { color: var(--text-2); font-size: .72rem; }
        .commission-line-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 7px; }
        .commission-line { min-height: 38px; font-size: .76rem; line-height: 1.3; }
        .commission-help { display: flex; gap: 9px; margin-top: 15px; padding: 10px 11px; border-radius: var(--r-md); background: var(--surface-alt); color: var(--text-2); font-size: .72rem; line-height: 1.4; }
        .commission-help svg { flex: 0 0 16px; width: 16px; height: 16px; margin-top: 1px; }

        .commission-seller-tools { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
        .commission-seller-tools input { width: min(100%, 320px); min-height: 32px; padding: 0 10px; border: 1px solid var(--stroke-strong); border-radius: var(--r-md); font: inherit; font-size: .78rem; color: var(--text); outline: none; }
        .commission-seller-tools input:focus { border-color: var(--brand); box-shadow: 0 0 0 1px var(--brand); }
        .commission-seller-summary { margin-left: auto; color: var(--text-2); font-size: .72rem; }
        .commission-sellers { display: flex; flex-direction: column; border: 1px solid var(--stroke); border-radius: var(--r-md); overflow: hidden; }
        .commission-seller + .commission-seller { border-top: 1px solid var(--divider); }
        .commission-seller.is-disabled .commission-seller__main { background: var(--surface-alt); }
        .commission-seller[hidden] { display: none; }
        .commission-seller__main { display: grid; grid-template-columns: minmax(190px, 1.4fr) minmax(120px, .65fr) minmax(130px, .65fr) auto; align-items: center; gap: 12px; padding: 11px 12px; }
        .commission-seller__identity { display: flex; align-items: center; gap: 10px; min-width: 0; }
        .commission-seller__toggle { position: relative; display: inline-flex; flex: 0 0 34px; width: 34px; height: 20px; }
        .commission-seller__toggle input { position: absolute; opacity: 0; }
        .commission-seller__toggle span { width: 34px; height: 20px; border: 1px solid var(--stroke-strong); border-radius: 10px; background: var(--surface-sunken); transition: .15s ease; }
        .commission-seller__toggle span::after { content: ''; display: block; width: 14px; height: 14px; margin: 2px; border-radius: 50%; background: var(--surface); box-shadow: var(--shadow-2); transition: .15s ease; }
        .commission-seller__toggle input:checked + span { border-color: var(--brand); background: var(--brand); }
        .commission-seller__toggle input:checked + span::after { transform: translateX(14px); }
        .commission-seller__name { min-width: 0; }
        .commission-seller__name strong, .commission-seller__name span { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .commission-seller__name strong { font-size: .8rem; }
        .commission-seller__name span { margin-top: 1px; color: var(--text-3); font-size: .7rem; }
        .commission-seller__field label { display: block; margin-bottom: 4px; color: var(--text-2); font-size: .68rem; font-weight: 600; }
        .commission-seller__field input, .commission-seller__field select { width: 100%; min-height: 32px; padding: 0 9px; border: 1px solid var(--stroke-strong); border-radius: var(--r-md); background: var(--surface); color: var(--text); font: inherit; font-size: .78rem; outline: none; }
        .commission-seller__details-toggle { align-self: end; height: 32px; }
        .commission-seller__details-toggle svg { transition: transform .15s ease; }
        .commission-seller.is-expanded .commission-seller__details-toggle svg { transform: rotate(180deg); }
        .commission-seller__advanced { display: none; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; padding: 12px 14px 14px 56px; border-top: 1px solid var(--divider); background: var(--surface-alt); }
        .commission-seller.is-expanded .commission-seller__advanced { display: grid; }
        .commission-no-results { padding: 26px 14px; color: var(--text-2); font-size: .78rem; text-align: center; }

        @media (max-width: 920px) {
            .commission-line-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .commission-seller__advanced { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 720px) {
            .commission-workspace { padding: 14px; }
            .commission-intro { flex-direction: column; }
            .commission-context { width: 100%; min-width: 0; grid-template-columns: 1fr; }
            .commission-step__meta { display: none; }
            .commission-step { padding: 8px; }
            .commission-rule-grid, .commission-checks, .commission-line-grid, .commission-group-fields { grid-template-columns: 1fr; }
            .commission-seller__main, .commission-seller__advanced { grid-template-columns: 1fr; }
            .commission-seller__advanced { padding-left: 14px; }
            .commission-seller__details-toggle { width: 100%; }
        }
    </style>
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        @include('desktop.operacion.gestion_configuraciones._subnav')
        <span class="desktop-toolbar__divider"></span>
        @unless($bloqueado)
            <button type="submit" class="desktop-btn desktop-btn--primary" form="commission-config-form">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 3h11l3 3v15H5z"/><path d="M8 3v6h8V3M8 21v-7h8v7"/></svg>Guardar
            </button>
        @endunless
        <a class="desktop-btn desktop-btn--ghost" href="{{ route('reportes.show', ['reporte' => 'ventas-comisiones', 'periodo' => $periodoTexto, 'sucursal_id' => $sucursalSeleccionadaId]) }}">Ver reporte</a>
    </div>
    <div class="desktop-toolbar__group">
        @if($periodo && !$bloqueado && $puedeEjecutarCalculo)<button type="submit" class="desktop-btn desktop-btn--ghost" form="commission-calculate-form">{{ $esRecalculo ? 'Recalcular comisión' : 'Calcular comisión' }}</button>@endif
        @if($periodo?->cpe_estatus === 'calculado' && !$sucursalSoloLectura && $puedeCerrar)<button type="submit" class="desktop-btn desktop-btn--ghost" form="commission-close-form" onclick="return confirm('¿Cerrar este periodo? Después no podrá recalcularse.');">Cerrar periodo</button>@endif
        @if($periodo)<span class="desktop-status {{ $cerrado ? 'desktop-status--inactive' : 'desktop-status--active' }}">{{ ucfirst($periodo->cpe_estatus) }}</span>@endif
    </div>
@endsection

@section('content')
    <section class="desktop-pane">
        <div class="commission-shell"><div class="commission-workspace">
            <header class="commission-intro">
                <div><h1>Preparar comisión mensual</h1><p>Completa los tres pasos en orden. La configuración se guarda para el mes seleccionado y después podrás calcular el reporte.</p></div>
                <div class="commission-context">
                    <div class="desktop-field">
                        <label for="commission-branch-picker">Sucursal</label>
                        <select id="commission-branch-picker">
                            @foreach($sucursalesComision as $sucursal)
                                <option value="{{ $sucursal->scl_id }}" @selected((int) $sucursal->scl_id === (int) $sucursalSeleccionadaId)>{{ $sucursal->scl_nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="desktop-field"><label for="commission-period-picker">Mes a configurar</label><input type="month" id="commission-period-picker" value="{{ $periodoTexto }}"></div>
                </div>
            </header>

            @if($errors->any())<div class="alert alert-danger mb-3"><strong>Hay un dato pendiente.</strong> {{ $errors->first() }}</div>@endif
            @if($cerrado)<div class="alert alert-info mb-3">Este periodo está cerrado. Puedes consultar la configuración, pero ya no modificarla.</div>@endif
            @if($sucursalSoloLectura)<div class="alert alert-info mb-3"><strong>Vista de consulta:</strong> estás revisando {{ $sucursalSeleccionada?->scl_nombre }}. Para modificar o calcular sus comisiones, primero actívala como sucursal de trabajo.</div>@endif
            @if($periodo?->cpe_estatus === 'borrador' && !$sucursalSoloLectura)
                <div class="alert alert-warning mb-3">
                    <strong>Pendiente de calcular.</strong> La configuración está guardada, pero el reporte no mostrará comisiones hasta generar el cálculo de este mes.
                    @if($puedeEjecutarCalculo)
                        <button type="submit" class="desktop-btn desktop-btn--primary ms-2" form="commission-calculate-form">Calcular comisión</button>
                    @endif
                </div>
            @endif

            <nav class="commission-steps" aria-label="Pasos de configuración">
                <button type="button" class="commission-step" data-commission-step="1"><span class="commission-step__number">1</span><span class="commission-step__text"><span class="commission-step__title">Reglas generales</span><span class="commission-step__meta">Cómo se calcula</span></span></button>
                <button type="button" class="commission-step" data-commission-step="2"><span class="commission-step__number">2</span><span class="commission-step__text"><span class="commission-step__title">Grupos y líneas</span><span class="commission-step__meta">Qué ventas participan</span></span></button>
                <button type="button" class="commission-step" data-commission-step="3"><span class="commission-step__number">3</span><span class="commission-step__text"><span class="commission-step__title">Vendedores</span><span class="commission-step__meta">Quiénes reciben comisión</span></span></button>
            </nav>

            <form id="commission-config-form" method="POST" action="{{ route('desktop.operacion.gestion_configuraciones.comisiones.update') }}" @if($sucursalSoloLectura) onsubmit="return false;" @endif>
                @csrf @method('PUT')
                <input type="hidden" name="periodo" value="{{ $periodoTexto }}">

                <section class="commission-panel" data-commission-panel="1">
                    <div class="commission-panel__head"><h2>Define las reglas del mes</h2><p>Estos valores se aplicarán a todos los vendedores que actives en el paso 3.</p></div>
                    <div class="commission-panel__body">
                        <div class="commission-block">
                            <div class="commission-block__head"><h3 class="commission-block__title">Cálculo de la comisión</h3><p class="commission-block__hint">La secuencia aparece en el mismo orden en que el sistema realiza el cálculo.</p></div>
                            <div class="commission-rule-grid">
                                <div class="commission-rule"><span class="commission-rule__number">1</span><div class="desktop-field"><label for="factor_comisionable">Parte de la venta que comisiona</label><div class="input-group"><input id="factor_comisionable" type="number" name="factor_comisionable" min="0" max="100" step="0.01" value="{{ old('factor_comisionable', $periodo?->cpe_factor_comisionable ?? 33) }}" required @disabled($bloqueado)><span class="input-group-text">%</span></div></div><div class="commission-rule__explain">De cada $100 vendidos, esta parte se considera para la comisión.</div></div>
                                <div class="commission-rule"><span class="commission-rule__number">2</span><div class="desktop-field"><label for="cumplimiento_minimo">Meta mínima para comisionar</label><div class="input-group"><input id="cumplimiento_minimo" type="number" name="cumplimiento_minimo" min="0" step="0.01" value="{{ old('cumplimiento_minimo', $periodo?->cpe_cumplimiento_minimo ?? 100) }}" required @disabled($bloqueado)><span class="input-group-text">%</span></div></div><div class="commission-rule__explain">El vendedor comisiona al alcanzar este porcentaje de su cuota.</div></div>
                                <div class="commission-rule"><span class="commission-rule__number">3</span><div class="desktop-field"><label for="tasa_general">Tasa de comisión normal</label><div class="input-group"><input id="tasa_general" type="number" name="tasa_general" min="0" max="100" step="0.0001" value="{{ old('tasa_general', $periodo?->cpe_tasa_general ?? 0.9) }}" required @disabled($bloqueado)><span class="input-group-text">%</span></div></div><div class="commission-rule__explain">Se aplica al importe comisionable si el vendedor alcanzó su meta.</div></div>
                            </div>
                        </div>
                        <div class="commission-block">
                            <div class="commission-block__head"><h3 class="commission-block__title">Almacenes que suman ventas</h3><p class="commission-block__hint">Marca los almacenes que forman parte de este reporte mensual.</p></div>
                            <div class="commission-checks">@foreach($almacenes as $almacen)<label class="commission-check"><input type="checkbox" name="almacen_ids[]" value="{{ $almacen->alm_id }}" @checked(in_array((string) $almacen->alm_id, array_map('strval', $almacenesSeleccionados), true)) @disabled($bloqueado)><span>{{ $almacen->alm_nombre }}</span></label>@endforeach</div>
                        </div>
                    </div>
                    <div class="commission-panel__foot"><span class="commission-panel__foot-note">Paso 1 de 3</span><div class="commission-panel__actions"><button type="button" class="desktop-btn desktop-btn--primary" data-commission-next="2">Continuar a grupos</button></div></div>
                </section>

                <section class="commission-panel" data-commission-panel="2" hidden>
                    <div class="commission-panel__head"><h2>Organiza las ventas por grupo</h2><p>Configura un grupo a la vez. Cada línea puede pertenecer únicamente a Ropa o a Telas.</p></div>
                    <div class="commission-panel__body">
                        <div class="commission-group-tabs" role="tablist" aria-label="Grupos de comisión">
                            @foreach($grupos as $grupo)<button type="button" class="commission-group-tab" data-commission-group-tab="{{ $grupo->cgr_id }}">{{ $grupo->cgr_nombre }}</button>@endforeach
                        </div>

                        @foreach($grupos as $grupo)
                            @php
                                $grupoConfig = $configGrupos->get($grupo->cgr_id);
                                $lineasGuardadas = $periodo
                                    ? collect($lineasPeriodo->get($grupo->cgr_id, []))->pluck('cpl_lna_id')
                                    : $grupo->lineas->pluck('lna_id');
                                $seleccionadas = old("grupos.{$grupo->cgr_id}.linea_ids", $lineasGuardadas->map(fn ($id) => (string) $id)->all());
                            @endphp
                            <div class="commission-group-panel" data-commission-group-panel="{{ $grupo->cgr_id }}" hidden>
                                <div class="commission-group-fields">
                                    <div class="desktop-field"><label>Vendedores promedio</label><input type="number" name="grupos[{{ $grupo->cgr_id }}][vendedores_promedio]" min="0.01" step="0.01" value="{{ old("grupos.{$grupo->cgr_id}.vendedores_promedio", $grupoConfig?->cpg_vendedores_promedio ?? 1) }}" required @disabled($bloqueado)><small>Se usa para repartir la meta del grupo.</small></div>
                                    <div class="desktop-field"><label>Incremento de meta</label><div class="input-group"><input type="number" name="grupos[{{ $grupo->cgr_id }}][incremento_meta]" min="{{ $grupo->cgr_incremento_minimo }}" max="{{ $grupo->cgr_incremento_maximo }}" step="0.01" value="{{ old("grupos.{$grupo->cgr_id}.incremento_meta", $grupoConfig?->cpg_incremento_meta ?? $grupo->cgr_incremento_minimo) }}" required @disabled($bloqueado)><span class="input-group-text">%</span></div><small>Permitido: {{ number_format((float) $grupo->cgr_incremento_minimo, 0) }}% a {{ number_format((float) $grupo->cgr_incremento_maximo, 0) }}%.</small></div>
                                </div>
                                <div class="commission-lines-head"><strong>Líneas incluidas en {{ $grupo->cgr_nombre }}</strong><span class="commission-count" data-commission-group-count="{{ $grupo->cgr_id }}">0 seleccionadas</span></div>
                                <div class="commission-line-grid">
                                    @forelse($lineas as $linea)
                                        <label class="commission-line"><input type="checkbox" name="grupos[{{ $grupo->cgr_id }}][linea_ids][]" value="{{ $linea->lna_id }}" @checked(in_array((string) $linea->lna_id, array_map('strval', (array) $seleccionadas), true)) @disabled($bloqueado)><span>{{ $linea->lna_nombre }}</span></label>
                                    @empty
                                        <span class="desktop-list__meta">No hay líneas activas.</span>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach

                        <div class="commission-help"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7.5v.5"/></svg><span>La meta se calcula con las ventas netas de las líneas seleccionadas, menos las ventas sin atención, divididas entre los vendedores promedio y más el incremento indicado.</span></div>
                    </div>
                    <div class="commission-panel__foot"><button type="button" class="desktop-btn desktop-btn--ghost" data-commission-next="1">Anterior</button><span class="commission-panel__foot-note">Paso 2 de 3</span><div class="commission-panel__actions"><button type="button" class="desktop-btn desktop-btn--primary" data-commission-next="3">Continuar a vendedores</button></div></div>
                </section>

                <section class="commission-panel" data-commission-panel="3" hidden>
                    <div class="commission-panel__head"><h2>Selecciona a los vendedores</h2><p>Activa a quienes participan y asigna su número y grupo. Los cambios especiales están ocultos para mantener la lista simple.</p></div>
                    <div class="commission-panel__body">
                        <div class="commission-seller-tools"><input type="search" id="commission-seller-search" placeholder="Buscar por nombre o usuario…" aria-label="Buscar vendedor"><span class="commission-seller-summary"><strong data-commission-active-count>0</strong> vendedores activos</span></div>
                        <div class="commission-sellers">
                            @forelse($vendedores as $usuario)
                                @php
                                    $perfil = $perfiles->get($usuario->usr_id);
                                    $perfilPeriodo = $vendedoresPeriodo->get($usuario->usr_id);
                                    $ajuste = $perfil ? $ajustes->get($perfil->cve_id) : null;
                                    $habilitado = (bool) old("vendedores.{$usuario->usr_id}.habilitado", $periodo ? (bool) $perfilPeriodo : $perfil?->cve_estatus === 'activo');
                                    $tieneAjuste = (float) old("vendedores.{$usuario->usr_id}.ajuste_tasa", $ajuste?->cav_ajuste_tasa ?? 0) !== 0.0
                                        || old("vendedores.{$usuario->usr_id}.tasa_final", $ajuste?->cav_tasa_final) !== null
                                        || (float) old("vendedores.{$usuario->usr_id}.bono", $ajuste?->cav_bono ?? 0) !== 0.0
                                        || filled(old("vendedores.{$usuario->usr_id}.motivo", $ajuste?->cav_motivo));
                                @endphp
                                <article class="commission-seller {{ $habilitado ? '' : 'is-disabled' }} {{ $tieneAjuste ? 'is-expanded' : '' }}" data-seller-row data-seller-search="{{ mb_strtolower($usuario->usr_nombre.' '.$usuario->usr_usuario) }}">
                                    <div class="commission-seller__main">
                                        <div class="commission-seller__identity">
                                            <input type="hidden" name="vendedores[{{ $usuario->usr_id }}][habilitado]" value="0">
                                            <label class="commission-seller__toggle" title="Incluir en comisiones"><input type="checkbox" name="vendedores[{{ $usuario->usr_id }}][habilitado]" value="1" data-seller-enabled @checked($habilitado) @disabled($bloqueado)><span aria-hidden="true"></span></label>
                                            <div class="commission-seller__name"><strong>{{ $usuario->usr_nombre }}</strong><span>{{ $usuario->usr_usuario }}</span></div>
                                        </div>
                                        <div class="commission-seller__field"><label>No. de vendedor</label><input type="text" name="vendedores[{{ $usuario->usr_id }}][numero]" maxlength="40" value="{{ old("vendedores.{$usuario->usr_id}.numero", $perfilPeriodo?->cpv_numero_vendedor ?? $perfil?->cve_numero) }}" placeholder="Ej. 5" data-seller-field @disabled($bloqueado)></div>
                                        <div class="commission-seller__field"><label>Grupo</label><select name="vendedores[{{ $usuario->usr_id }}][grupo_id]" data-seller-field @disabled($bloqueado)><option value="">Selecciona</option>@foreach($grupos as $grupo)<option value="{{ $grupo->cgr_id }}" @selected((string) old("vendedores.{$usuario->usr_id}.grupo_id", $perfilPeriodo?->cpv_cgr_id ?? $perfil?->cve_cgr_id) === (string) $grupo->cgr_id)>{{ $grupo->cgr_nombre }}</option>@endforeach</select></div>
                                        <button type="button" class="desktop-btn desktop-btn--ghost commission-seller__details-toggle" data-seller-details aria-expanded="{{ $tieneAjuste ? 'true' : 'false' }}">Ajustes <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m7 10 5 5 5-5"/></svg></button>
                                    </div>
                                    <div class="commission-seller__advanced">
                                        <div class="commission-seller__field"><label>Ajuste a la tasa (puntos)</label><input type="number" name="vendedores[{{ $usuario->usr_id }}][ajuste_tasa]" step="0.0001" value="{{ old("vendedores.{$usuario->usr_id}.ajuste_tasa", $ajuste?->cav_ajuste_tasa ?? 0) }}" data-seller-field @disabled($bloqueado)></div>
                                        <div class="commission-seller__field"><label>Tasa final opcional (%)</label><input type="number" name="vendedores[{{ $usuario->usr_id }}][tasa_final]" min="0" step="0.0001" value="{{ old("vendedores.{$usuario->usr_id}.tasa_final", $ajuste?->cav_tasa_final) }}" placeholder="Automática" data-seller-field @disabled($bloqueado)></div>
                                        <div class="commission-seller__field"><label>Bono adicional ($)</label><input type="number" name="vendedores[{{ $usuario->usr_id }}][bono]" min="0" step="0.01" value="{{ old("vendedores.{$usuario->usr_id}.bono", $ajuste?->cav_bono ?? 0) }}" data-seller-field @disabled($bloqueado)></div>
                                        <div class="commission-seller__field"><label>Motivo del ajuste</label><input type="text" name="vendedores[{{ $usuario->usr_id }}][motivo]" maxlength="500" value="{{ old("vendedores.{$usuario->usr_id}.motivo", $ajuste?->cav_motivo) }}" placeholder="Ej. buen desempeño" data-seller-field @disabled($bloqueado)></div>
                                    </div>
                                </article>
                            @empty
                                <div class="commission-no-results">No hay usuarios activos asociados a la sucursal.</div>
                            @endforelse
                            <div class="commission-no-results" id="commission-seller-no-results" hidden>No encontramos vendedores con esa búsqueda.</div>
                        </div>
                    </div>
                    <div class="commission-panel__foot"><button type="button" class="desktop-btn desktop-btn--ghost" data-commission-next="2">Anterior</button><span class="commission-panel__foot-note">Paso 3 de 3</span><div class="commission-panel__actions">@unless($bloqueado)<button type="submit" class="desktop-btn desktop-btn--primary">Guardar configuración</button>@endunless</div></div>
                </section>
            </form>
        </div></div>
    </section>

    @if($periodo && !$bloqueado && $puedeEjecutarCalculo)<form id="commission-calculate-form" method="POST" action="{{ route('reportes.comisiones.calcular') }}" hidden>@csrf<input type="hidden" name="periodo" value="{{ $periodoTexto }}"></form>@endif
    @if($periodo?->cpe_estatus === 'calculado' && !$sucursalSoloLectura && $puedeCerrar)<form id="commission-close-form" method="POST" action="{{ route('reportes.comisiones.cerrar') }}" hidden>@csrf<input type="hidden" name="periodo" value="{{ $periodoTexto }}"></form>@endif
@endsection

@push('desktop-scripts')
    <script>
        (function () {
            const initialStep = {{ $pasoInicial }};
            const steps = Array.from(document.querySelectorAll('[data-commission-step]'));
            const panels = Array.from(document.querySelectorAll('[data-commission-panel]'));
            function showStep(number, updateHash) {
                steps.forEach(function (step) { const active = step.dataset.commissionStep === String(number); step.classList.toggle('is-active', active); step.setAttribute('aria-current', active ? 'step' : 'false'); });
                panels.forEach(function (panel) { panel.hidden = panel.dataset.commissionPanel !== String(number); });
                if (updateHash) history.replaceState(null, '', '#paso-' + number);
                document.querySelector('.commission-shell')?.scrollTo({ top: 0, behavior: 'smooth' });
            }
            const requestedStep = Number((window.location.hash.match(/paso-(\d)/) || [])[1]);
            showStep([1, 2, 3].includes(requestedStep) ? requestedStep : initialStep, false);
            steps.forEach(function (step) { step.addEventListener('click', function () { showStep(this.dataset.commissionStep, true); }); });
            document.querySelectorAll('[data-commission-next]').forEach(function (button) { button.addEventListener('click', function () { showStep(this.dataset.commissionNext, true); }); });

            const branch = document.getElementById('commission-branch-picker');
            branch?.addEventListener('change', function () { const url = new URL(window.location.href); url.searchParams.set('sucursal_id', this.value); url.hash = ''; window.location.href = url.toString(); });

            const period = document.getElementById('commission-period-picker');
            period?.addEventListener('change', function () { const url = new URL(window.location.href); url.searchParams.set('periodo', this.value); url.hash = ''; window.location.href = url.toString(); });

            const groupTabs = Array.from(document.querySelectorAll('[data-commission-group-tab]'));
            const groupPanels = Array.from(document.querySelectorAll('[data-commission-group-panel]'));
            function showGroup(id) {
                groupTabs.forEach(function (tab) { const active = tab.dataset.commissionGroupTab === String(id); tab.classList.toggle('is-active', active); tab.setAttribute('aria-selected', active ? 'true' : 'false'); });
                groupPanels.forEach(function (panel) { panel.hidden = panel.dataset.commissionGroupPanel !== String(id); });
            }
            if (groupTabs.length) showGroup(groupTabs[0].dataset.commissionGroupTab);
            groupTabs.forEach(function (tab) { tab.addEventListener('click', function () { showGroup(this.dataset.commissionGroupTab); }); });

            function updateGroupCounts() {
                document.querySelectorAll('[data-commission-group-count]').forEach(function (counter) { const id = counter.dataset.commissionGroupCount; const count = document.querySelectorAll('input[name^="grupos[' + id + ']"][name$="[linea_ids][]"]:checked').length; counter.textContent = count + (count === 1 ? ' seleccionada' : ' seleccionadas'); });
            }
            document.querySelectorAll('input[name^="grupos"][name$="[linea_ids][]"]').forEach(function (checkbox) {
                checkbox.addEventListener('change', function () {
                    if (this.checked) document.querySelectorAll('input[name^="grupos"][name$="[linea_ids][]"][value="' + this.value + '"]').forEach(function (other) { if (other !== checkbox) other.checked = false; });
                    updateGroupCounts();
                });
            });
            updateGroupCounts();

            const sellerRows = Array.from(document.querySelectorAll('[data-seller-row]'));
            function updateActiveCount() { const count = sellerRows.filter(function (row) { return row.querySelector('[data-seller-enabled]')?.checked; }).length; const target = document.querySelector('[data-commission-active-count]'); if (target) target.textContent = count; }
            sellerRows.forEach(function (row) {
                const enabled = row.querySelector('[data-seller-enabled]');
                const fields = row.querySelectorAll('[data-seller-field]');
                const sync = function () { row.classList.toggle('is-disabled', !enabled.checked); fields.forEach(function (field) { field.disabled = !enabled.checked; }); updateActiveCount(); };
                if (enabled && !enabled.disabled) { enabled.addEventListener('change', sync); sync(); }
                row.querySelector('[data-seller-details]')?.addEventListener('click', function () { const expanded = row.classList.toggle('is-expanded'); this.setAttribute('aria-expanded', expanded ? 'true' : 'false'); });
            });
            updateActiveCount();

            document.getElementById('commission-seller-search')?.addEventListener('input', function () {
                const query = this.value.trim().toLocaleLowerCase('es'); let visible = 0;
                sellerRows.forEach(function (row) { const matches = !query || row.dataset.sellerSearch.includes(query); row.hidden = !matches; if (matches) visible++; });
                const empty = document.getElementById('commission-seller-no-results'); if (empty) empty.hidden = visible > 0;
            });
        })();
    </script>
@endpush
