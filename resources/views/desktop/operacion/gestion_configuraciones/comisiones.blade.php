@extends('layouts.desktop')
@section('title', 'Metas y comisiones')

@push('desktop-styles')
<style>
    .commission-v2{display:flex;flex-direction:column;gap:16px;max-width:1180px;margin:0 auto}.commission-hero{padding:22px;border-radius:18px;color:#fff;background:linear-gradient(135deg,#0f6cbd,#17478f);box-shadow:var(--shadow-4)}
    .commission-hero__top,.commission-row{display:flex;align-items:center;justify-content:space-between;gap:14px}.commission-hero h1{margin:2px 0 0;font-size:1.42rem}.commission-hero h1::first-letter{text-transform:uppercase}.commission-hero p{margin:6px 0 0;opacity:.88;font-size:.86rem;line-height:1.5}.commission-hero__eyebrow{font-size:.72rem;font-weight:700;opacity:.8}.commission-status{display:inline-flex;flex:none;padding:7px 12px;border-radius:999px;font-size:.76rem;font-weight:800;text-transform:uppercase;background:rgba(255,255,255,.16)}
    .commission-toolbar,.commission-wizard{background:var(--surface);border:1px solid var(--stroke);border-radius:var(--r-lg);box-shadow:var(--shadow-2)}.commission-toolbar{padding:14px 16px;display:flex;align-items:end;gap:12px;flex-wrap:wrap}.commission-toolbar .desktop-field{margin:0;min-width:190px}.commission-toolbar__aside{margin-left:auto;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
    .commission-advanced{position:relative}.commission-advanced>summary{list-style:none;cursor:pointer}.commission-advanced>summary::-webkit-details-marker{display:none}.commission-advanced__panel{position:absolute;right:0;top:calc(100% + 8px);z-index:var(--z-dropdown);width:min(430px,90vw);padding:14px;border:1px solid var(--stroke);border-radius:14px;background:var(--surface);box-shadow:var(--shadow-8)}.commission-create{display:flex;gap:8px;align-items:end;padding-bottom:12px}.commission-create .desktop-field{margin:0;flex:1}.commission-manage__row{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:9px 4px;border-top:1px solid var(--divider)}
    .commission-wizard{overflow:hidden}.commission-stepper{display:grid;grid-template-columns:repeat(4,1fr);padding:18px 20px;border-bottom:1px solid var(--divider);background:var(--surface-sunken)}.commission-stepper__item{position:relative;display:flex;align-items:center;gap:9px;border:0;background:transparent;color:var(--text-2);text-align:left;padding:0 8px;cursor:pointer}.commission-stepper__item:not(:last-child)::after{content:"";position:absolute;left:calc(100% - 12px);right:-12px;top:17px;height:2px;background:var(--stroke)}.commission-stepper__number{position:relative;z-index:1;width:34px;height:34px;display:grid;place-items:center;flex:none;border-radius:50%;border:2px solid var(--stroke-strong);background:var(--surface);font-weight:800}.commission-stepper__text strong,.commission-stepper__text small{display:block}.commission-stepper__text strong{font-size:.83rem}.commission-stepper__text small{margin-top:2px;font-size:.68rem;color:var(--text-3)}.commission-stepper__item.is-active{color:var(--brand)}.commission-stepper__item.is-active .commission-stepper__number,.commission-stepper__item.is-done .commission-stepper__number{border-color:var(--brand);background:var(--brand);color:#fff}.commission-stepper__item.is-done:not(:last-child)::after{background:var(--brand)}.commission-stepper__item:focus-visible{outline:2px solid var(--brand);outline-offset:2px;border-radius:8px}
    .commission-step-panel[hidden]{display:none!important}.commission-step-panel{animation:commission-step-in .18s ease}.commission-step-panel__head{display:flex;gap:14px;align-items:flex-start;padding:24px 26px 17px;border-bottom:1px solid var(--divider)}.commission-step-panel__icon{width:42px;height:42px;display:grid;place-items:center;flex:none;border-radius:12px;background:var(--brand-soft);color:var(--brand);font-size:1.12rem}.commission-step-panel__head h2{margin:0;font-size:1.18rem}.commission-step-panel__head p{margin:5px 0 0;color:var(--text-2);font-size:.84rem;line-height:1.5}.commission-step-panel__body{padding:22px 26px;display:grid;gap:22px}.commission-step-panel__body>*{min-width:0}.commission-step-panel__footer{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 26px;border-top:1px solid var(--divider);background:var(--surface-sunken)}
    .commission-section__title{display:flex;align-items:baseline;gap:8px;flex-wrap:wrap;margin:0 0 10px;font-size:.92rem}.commission-section__title small{font-weight:400;color:var(--text-2);font-size:.76rem}
    .commission-choice-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.commission-choice{display:flex;align-items:center;gap:12px;padding:15px;border:1px solid var(--stroke);border-radius:12px;background:var(--surface);cursor:pointer;transition:.15s}.commission-choice:hover{border-color:var(--brand)}.commission-choice input{width:18px;height:18px}.commission-choice:has(input:checked){border-color:var(--brand);background:var(--brand-soft)}.commission-choice strong,.commission-choice small{display:block}.commission-choice small{margin-top:3px;color:var(--text-2);font-size:.74rem}
    .commission-departments{display:flex;flex-direction:column;gap:13px}.commission-department{border:1px solid var(--stroke);border-radius:14px;overflow:hidden}.commission-department[hidden]{display:none}.commission-department__top{display:flex;gap:12px;align-items:center;flex-wrap:wrap;padding:13px 15px;background:var(--surface-sunken)}.commission-department__top>strong,.commission-department__toggle{flex:1}.commission-department__toggle{display:flex;align-items:center;gap:10px;cursor:pointer;font-weight:700}.commission-department__toggle input{width:17px;height:17px}.commission-department__body{padding:16px;display:grid;gap:14px}.commission-department__body>*,.commission-fields>*{min-width:0}.commission-departments{min-width:0}.commission-department.is-disabled .commission-department__body{opacity:.5}.commission-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;align-items:start}.commission-fields[hidden]{display:none}
    .commission-lines{display:flex;flex-wrap:wrap;gap:7px}.commission-line{position:relative}.commission-line input{position:absolute;opacity:0;pointer-events:none}.commission-line span{display:block;padding:7px 10px;border:1px solid var(--stroke);border-radius:999px;color:var(--text-2);font-size:.76rem;cursor:pointer}.commission-line input:checked+span{border-color:var(--brand);background:var(--brand-soft);color:var(--brand);font-weight:700}.commission-line input:focus-visible+span{outline:2px solid var(--brand);outline-offset:2px}.commission-line input:disabled+span{cursor:not-allowed;opacity:.5;text-decoration:line-through}
    .commission-reference{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px}.commission-reference div,.commission-summary-card{padding:12px;border-radius:11px;background:var(--surface-sunken)}.commission-reference span,.commission-summary-card span{display:block;color:var(--text-2);font-size:.7rem}.commission-reference strong,.commission-summary-card strong{display:block;margin-top:4px;font-size:.9rem;font-variant-numeric:tabular-nums}.commission-reference .is-base{background:var(--brand-soft)}.commission-reference .is-base strong{color:var(--brand)}
    .commission-help{padding:12px 14px;border-radius:10px;background:#eef6ff;color:#154f86;font-size:.79rem;line-height:1.5}.commission-note{color:var(--text-2);font-size:.77rem;line-height:1.45}.commission-danger-note{padding:12px;border-radius:10px;background:#fff4e5;color:#7a4a00;font-size:.8rem;line-height:1.5}.commission-error-note{padding:12px;border-radius:10px;background:var(--danger-soft);color:var(--danger);font-size:.8rem;line-height:1.5}.commission-help[hidden],.commission-danger-note[hidden],.commission-error-note[hidden],.commission-note[hidden]{display:none}.commission-help a,.commission-danger-note a,.commission-error-note a{color:inherit;font-weight:700}
    .desktop-pill.commission-pill--success{background:var(--success-soft);color:var(--success)}.desktop-pill.commission-pill--warning{background:var(--warning-soft);color:var(--warning)}.desktop-pill.commission-pill--danger{background:var(--danger-soft);color:var(--danger)}
    .commission-mode{display:flex;align-items:center;gap:12px;flex-wrap:wrap}.commission-mode__label{font-size:.8rem;font-weight:700}.commission-mode .desktop-pivot{flex-wrap:wrap;height:auto}.commission-mode .desktop-pivot label{cursor:pointer}.commission-mode .desktop-pivot input{position:absolute;opacity:0;pointer-events:none}.commission-mode .desktop-pivot label:focus-within{outline:2px solid var(--brand);outline-offset:1px}
    .commission-formula{padding:12px 14px;border-radius:11px;border:1px dashed var(--stroke-strong);font-size:.82rem;line-height:1.6;font-variant-numeric:tabular-nums}.commission-formula strong{color:var(--brand)}.commission-formula .desktop-btn{margin-top:6px}
    .commission-tip>summary{cursor:pointer;color:var(--brand);font-size:.8rem;font-weight:700}.commission-tip>summary:focus-visible{outline:2px solid var(--brand);outline-offset:2px}.commission-tip[open]>summary{margin-bottom:10px}.commission-tip dl{margin:0;display:grid;gap:8px;font-size:.8rem;line-height:1.5}.commission-tip dt{font-weight:700}.commission-tip dd{margin:0;color:var(--text-2)}
    .commission-seller-tools{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px}.commission-seller-tools__end{display:flex;align-items:center;gap:8px;flex-wrap:wrap}.commission-seller-search{width:min(300px,100%);min-height:34px;padding:6px 10px;border:1px solid var(--stroke);border-radius:9px;background:var(--surface);color:var(--text)}.commission-sellers{overflow:auto;border:1px solid var(--stroke);border-radius:12px}.commission-table{width:100%;border-collapse:collapse;min-width:980px}.commission-table--compact{min-width:560px}.commission-table th{padding:10px;text-align:left;font-size:.7rem;color:var(--text-2);background:var(--surface-sunken);border-bottom:1px solid var(--stroke)}.commission-table td{padding:10px;border-bottom:1px solid var(--divider);vertical-align:top;font-size:.82rem}.commission-table tr:last-child td{border-bottom:0}.commission-table .is-number{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}.commission-table input,.commission-table select{width:100%;min-height:34px;padding:6px 8px;border:1px solid var(--stroke);border-radius:8px;background:var(--surface);color:var(--text)}.commission-table input.is-required{border-color:var(--warning)}.commission-table .seller-toggle{width:18px;height:18px;min-height:0}.commission-table small{display:block;margin-top:4px;font-size:.72rem;line-height:1.4;color:var(--text-2)}.commission-table .is-number small{white-space:normal;min-width:170px}.commission-table small.is-warning{color:var(--warning)}.commission-table small.is-danger,.commission-field-error{color:var(--danger)!important}.commission-table tr.is-off td{opacity:.5}.commission-table tr.is-off td:first-child{opacity:1}.commission-table tr.is-missing td{background:var(--warning-soft)}
    .commission-estimate__percent{min-width:120px}.commission-progress{height:7px;overflow:hidden;border-radius:999px;background:var(--surface-sunken);margin-top:4px}.commission-progress span{display:block;height:100%;border-radius:inherit;background:linear-gradient(90deg,#0f6cbd,#16a36a)}.commission-empty{padding:24px;text-align:center;color:var(--text-2);font-size:.84rem;line-height:1.6}.commission-empty[hidden]{display:none}
    .commission-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-bottom:12px}.commission-review-section h3{margin:0 0 10px;font-size:.92rem}.commission-review-section .commission-row{margin-bottom:8px}.commission-review-section .commission-row h3{margin:0}
    .commission-checklist{list-style:none;margin:0;padding:0;display:grid;gap:6px}.commission-checklist li{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:9px 12px;border-radius:10px;font-size:.8rem;background:var(--warning-soft);color:var(--warning)}.commission-checklist li.is-blocking{background:var(--danger-soft);color:var(--danger)}.commission-checklist li.is-ok{background:var(--success-soft);color:var(--success)}.commission-checklist__group{margin:10px 0 6px;font-size:.74rem;font-weight:700;color:var(--text-2)}.commission-checklist__group:first-child{margin-top:0}
    .commission-effects{margin:0;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:8px}.commission-effects div{padding:10px 12px;border-radius:10px;background:var(--surface-sunken);font-size:.76rem;line-height:1.45}.commission-effects dt{font-weight:700;margin-bottom:2px}.commission-effects dd{margin:0;color:var(--text-2)}
    .commission-final-actions{display:flex;align-items:center;justify-content:flex-end;gap:8px;flex-wrap:wrap}.commission-error{display:none;margin:0 26px 18px;padding:10px 12px;border-radius:9px;background:#fff0f0;color:#a32b2b;font-size:.8rem}.commission-error.is-visible{display:block}
    @keyframes commission-step-in{from{opacity:.6}to{opacity:1}}
    @media(prefers-reduced-motion:reduce){.commission-step-panel{animation:none}}
    @media(max-width:900px){.commission-stepper__text small{display:none}.commission-stepper__item{justify-content:center}.commission-summary{grid-template-columns:repeat(2,1fr)}.commission-reference{grid-template-columns:1fr}.commission-fields{grid-template-columns:1fr}}
    @media(max-width:620px){.commission-hero__top{flex-direction:column;align-items:flex-start}.commission-choice-grid{grid-template-columns:1fr}.commission-stepper{padding:14px 5px}.commission-stepper__item{padding:0 2px;flex-direction:column;text-align:center;gap:5px}.commission-stepper__text strong{font-size:.68rem}.commission-stepper__item:not(:last-child)::after{top:17px;left:calc(50% + 18px);right:calc(-50% + 18px)}.commission-step-panel__head,.commission-step-panel__body{padding-left:16px;padding-right:16px}.commission-step-panel__footer{padding:14px 16px;flex-wrap:wrap}.commission-summary{grid-template-columns:1fr 1fr}.commission-toolbar__aside{margin-left:0;width:100%;justify-content:flex-start}.commission-create{flex-wrap:wrap}.commission-final-actions{justify-content:flex-start}}
</style>
@endpush

@section('desktop-toolbar')
<div class="page-head"><span class="page-head__title">Metas y comisiones</span><span class="page-head__sub">Asistente mensual paso a paso</span></div>
@endsection

@section('content')
@php
    $estado = $periodo?->cmp_estatus ?? 'sin configurar';
    $bloqueado = $estado === 'cerrado';
    $estadoTexto = [
        'sin configurar' => 'Aún no se ha guardado. Los vendedores no ven nada.',
        'borrador' => 'Borrador guardado. Los vendedores todavía no ven su avance.',
        'aprobado' => 'Publicado. Los vendedores ven su avance; cada cambio requiere motivo.',
        'cerrado' => 'Cerrado. Resultados definitivos; ya no admite cambios.',
    ][$estado] ?? '';
    $primerError = (string) collect($errors->keys())->first();
    $pasoError = match (true) {
        $primerError === '' => null,
        str_starts_with($primerError, 'almacen') || str_ends_with($primerError, 'linea_ids') || $primerError === 'departamentos' => 1,
        str_starts_with($primerError, 'departamentos') => 2,
        str_starts_with($primerError, 'vendedores') => 3,
        default => 4,
    };
    $pasoInicial = $pasoError ?? ($periodo ? 4 : 1);
    $historicoUrl = route('desktop.operacion.gestion_configuraciones.comisiones.historico.index', ['mes' => $referenciaPeriodo, 'volver' => $periodoTexto]);
    $tasas = [0, 0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9, 1];
    $datosCliente = [
        'locked' => $bloqueado,
        'estado' => $estado,
        'previewUrl' => route('desktop.operacion.gestion_configuraciones.comisiones.vista_previa'),
        'historicoUrl' => $historicoUrl,
        'periodo' => $periodoTexto,
        'referenciaTexto' => $referenciaTexto,
        'guardada' => $vistaGuardada,
    ];
@endphp
<div class="commission-v2" data-commission-wizard data-initial-step="{{ $pasoInicial }}" data-has-errors="{{ $errors->any() ? '1' : '0' }}">
    <script type="application/json" id="commission-data">@json($datosCliente)</script>

    <section class="commission-hero" aria-labelledby="commission-title">
        <div class="commission-hero__top">
            <div>
                <span class="commission-hero__eyebrow">Mes que configuras</span>
                <h1 id="commission-title">{{ $mesTexto }}</h1>
                <p>Referencia histórica: <strong>{{ $referenciaTexto }}</strong>, el mismo mes del año anterior.<br>{{ $estadoTexto }}</p>
            </div>
            <span class="commission-status">{{ str($estado)->headline() }}</span>
        </div>
    </section>
    @if($errors->any())<div class="alert alert-danger" role="alert"><strong>No se guardaron los cambios.</strong> {{ $errors->first() }} Revisa el paso marcado.</div>@endif
    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif

    <section class="commission-toolbar">
        <form method="GET" class="commission-row" data-leave-guard><div class="desktop-field"><label for="commission-period">Mes que vas a configurar</label><input id="commission-period" type="month" name="periodo" value="{{ $periodoTexto }}"></div><button class="desktop-btn desktop-btn--primary" type="submit">Abrir mes</button></form>
        <div class="commission-toolbar__aside">
            <a class="desktop-btn desktop-btn--ghost" href="{{ $historicoUrl }}" data-leave-guard><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 8v4l2 2"/><path d="M3.05 11a9 9 0 1 1 .5 4M3 4v5h5"/></svg> Capturar ventas históricas</a>
            @if($legacyCount>0)<a class="desktop-btn desktop-btn--ghost" href="{{ route('reportes.show',['reporte'=>'ventas-comisiones']) }}" data-leave-guard>Histórico anterior</a>@endif
            <details class="commission-advanced"><summary class="desktop-btn desktop-btn--ghost">Opciones avanzadas</summary><div class="commission-advanced__panel">
                <form method="POST" action="{{ route('desktop.operacion.gestion_configuraciones.comisiones.departamentos.store') }}" class="commission-create">@csrf<div class="desktop-field"><label for="new-department">Crear departamento</label><input id="new-department" name="nombre" maxlength="120" placeholder="Ej. Hogar" required></div><button class="desktop-btn desktop-btn--ghost" type="submit">Crear</button></form>
                @foreach($departamentosCatalogo as $item)<div class="commission-manage__row"><span><strong>{{ $item->cmd_nombre }}</strong><br><small>{{ str($item->cmd_estatus)->headline() }}</small></span><form method="POST" action="{{ route('desktop.operacion.gestion_configuraciones.comisiones.departamentos.estatus',$item) }}">@csrf @method('PATCH')<input type="hidden" name="estatus" value="{{ $item->cmd_estatus==='activo'?'inactivo':'activo' }}"><button class="desktop-btn desktop-btn--ghost" type="submit">{{ $item->cmd_estatus==='activo'?'Retirar':'Activar' }}</button></form></div>@endforeach
            </div></details>
        </div>
    </section>

    @if($periodo&&$estado==='borrador')<form id="commission-approve" method="POST" action="{{ route('reportes.comisiones.calcular') }}" data-confirm="approve">@csrf<input type="hidden" name="periodo" value="{{ $periodoTexto }}"></form>@endif
    @if($periodo&&$estado==='aprobado')<form id="commission-close" method="POST" action="{{ route('reportes.comisiones.cerrar') }}" data-confirm="close">@csrf<input type="hidden" name="periodo" value="{{ $periodoTexto }}"></form>@endif

    <form id="commission-config" class="commission-wizard" method="POST" action="{{ route('desktop.operacion.gestion_configuraciones.comisiones.update') }}" data-ls-autocomplete="admin" novalidate>
        @csrf @method('PUT')<input type="hidden" name="periodo" value="{{ $periodoTexto }}">
        <nav class="commission-stepper" aria-label="Pasos de configuración">
            @foreach([[1,'Periodo y alcance','Almacenes y líneas'],[2,'Histórico y metas','Referencia y meta'],[3,'Equipo','Vendedores y tasas'],[4,'Revisión','Guardar y publicar']] as [$numero,$titulo,$subtitulo])
                <button class="commission-stepper__item" type="button" data-step-target="{{ $numero }}"><span class="commission-stepper__number">{{ $numero }}</span><span class="commission-stepper__text"><strong>{{ $titulo }}</strong><small>{{ $subtitulo }}</small></span></button>
            @endforeach
        </nav>

        {{-- Paso 1: qué ventas cuentan --}}
        <section class="commission-step-panel" data-step-panel="1" aria-labelledby="step-1-title">
            <header class="commission-step-panel__head"><span class="commission-step-panel__icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18M4 21V10M20 21V10M3 7l2-4h14l2 4M3 7a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0M9 21v-6h6v6"/></svg></span><div><h2 id="step-1-title">¿Qué ventas cuentan este mes?</h2><p>Elige almacenes y las líneas de cada departamento. Se usan para las ventas de {{ $mesTexto }} y para buscar la referencia de {{ $referenciaTexto }}.</p></div></header>
            <div class="commission-step-panel__body">
                <div>
                    <h3 class="commission-section__title">Almacenes <small>Se suman las ventas de todos los seleccionados.</small></h3>
                    <div class="commission-choice-grid">@forelse($almacenes as $almacen)<label class="commission-choice"><input type="checkbox" name="almacen_ids[]" value="{{ $almacen->alm_id }}" @checked(in_array((string)$almacen->alm_id,array_map('strval',old('almacen_ids',$almacenesSeleccionados)),true)) @disabled($bloqueado)><span><strong>{{ $almacen->alm_nombre }}</strong><small>Incluir ventas de este almacén</small></span></label>@empty<div class="commission-empty">No hay almacenes activos en esta sucursal.</div>@endforelse</div>
                    @error('almacen_ids')<p class="commission-field-error commission-note">{{ $message }}</p>@enderror
                </div>
                <div>
                    <h3 class="commission-section__title">Departamentos y líneas <small>Cada línea pertenece a un solo departamento durante el periodo.</small></h3>
                    <div class="commission-departments">
                        @forelse($departamentos as $departamento)
                            @php
                                $config = $configDepartamentos->get($departamento->cmd_id);
                                $habilitado = (bool) old("departamentos.{$departamento->cmd_id}.habilitado", $periodo ? (bool) $config : $departamento->lineas->isNotEmpty());
                                $seleccionadas = $periodo ? collect($lineasPeriodo->get($departamento->cmd_id, []))->pluck('cml_lna_id')->all() : $departamento->lineas->pluck('lna_id')->all();
                            @endphp
                            <article class="commission-department" data-department="{{ $departamento->cmd_id }}" data-name="{{ $departamento->cmd_nombre }}">
                                <div class="commission-department__top"><input type="hidden" name="departamentos[{{ $departamento->cmd_id }}][habilitado]" value="0"><label class="commission-department__toggle"><input class="department-toggle" type="checkbox" name="departamentos[{{ $departamento->cmd_id }}][habilitado]" value="1" @checked($habilitado) @disabled($bloqueado)>{{ $departamento->cmd_nombre }}</label><span class="commission-note" data-line-count></span></div>
                                <div class="commission-department__body">
                                    <div class="commission-lines" role="group" aria-label="Líneas de {{ $departamento->cmd_nombre }}">@foreach($lineas as $linea)<label class="commission-line"><input type="checkbox" name="departamentos[{{ $departamento->cmd_id }}][linea_ids][]" value="{{ $linea->lna_id }}" data-line-name="{{ $linea->lna_nombre }}" @checked(in_array((string)$linea->lna_id,array_map('strval',old("departamentos.{$departamento->cmd_id}.linea_ids",$seleccionadas)),true)) @disabled($bloqueado)><span>{{ $linea->lna_nombre }}</span></label>@endforeach</div>
                                    @error("departamentos.{$departamento->cmd_id}.linea_ids")<p class="commission-field-error commission-note">{{ $message }}</p>@enderror
                                </div>
                            </article>
                        @empty
                            <div class="commission-empty">No hay departamentos activos. Créalos en <strong>Opciones avanzadas</strong>.</div>
                        @endforelse
                    </div>
                    @error('departamentos')<p class="commission-field-error commission-note">{{ $message }}</p>@enderror
                </div>
            </div>
            <p class="commission-error" data-step-error="1" role="alert"></p>
            <footer class="commission-step-panel__footer"><span class="commission-note">Paso 1 de 4</span><button class="desktop-btn desktop-btn--primary" type="button" data-next-step="2">Continuar</button></footer>
        </section>

        {{-- Paso 2: referencia histórica y forma de definir la meta --}}
        <section class="commission-step-panel" data-step-panel="2" aria-labelledby="step-2-title" hidden>
            <header class="commission-step-panel__head"><span class="commission-step-panel__icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19h16M4 15l4-6 4 3 4-6 4 4"/></svg></span><div><h2 id="step-2-title">Histórico y metas</h2><p>Elige cómo se define la meta de cada departamento: el sistema la calcula con {{ $referenciaTexto }} o tú escribes una meta manual, que tiene prioridad.</p></div></header>
            <div class="commission-step-panel__body">
                <div class="commission-departments">
                    @foreach($departamentos as $departamento)
                        @php
                            $config = $configDepartamentos->get($departamento->cmd_id);
                            $modo = old("departamentos.{$departamento->cmd_id}.modo_meta", $modosGuardados->get($departamento->cmd_id, 'historica'));
                            $metaManual = old("departamentos.{$departamento->cmd_id}.meta_comun", $modosGuardados->get($departamento->cmd_id) === 'manual' ? $config?->cpd_meta_comun : null);
                        @endphp
                        <article class="commission-department" data-goal="{{ $departamento->cmd_id }}" data-name="{{ $departamento->cmd_nombre }}" hidden>
                            <div class="commission-department__top"><strong>{{ $departamento->cmd_nombre }}</strong><span class="desktop-pill desktop-pill--neutral" data-ref-status role="status">Pendiente de consultar</span></div>
                            <div class="commission-department__body">
                                <div class="commission-reference" aria-live="polite">
                                    <div><span>Ventas netas de {{ $referenciaTexto }}</span><strong data-ref-ventas>—</strong></div>
                                    <div><span>Menos autoservicio</span><strong data-ref-auto>—</strong></div>
                                    <div class="is-base"><span>Base histórica</span><strong data-ref-base>—</strong></div>
                                </div>
                                <p class="commission-note" data-ref-source hidden></p>
                                <div class="commission-danger-note" data-ref-warning hidden></div>
                                <details class="commission-tip" data-ref-detail hidden><summary>Ver detalle por almacén y línea</summary><div class="commission-sellers"><table class="commission-table commission-table--compact"><thead><tr><th>Almacén</th><th>Línea</th><th>Fuente</th><th class="is-number">Ventas netas</th><th class="is-number">Autoservicio</th><th class="is-number">Base</th><th><span class="visually-hidden">Acción</span></th></tr></thead><tbody data-ref-rows></tbody></table></div></details>

                                <div class="commission-mode">
                                    <span class="commission-mode__label" id="mode-label-{{ $departamento->cmd_id }}">¿Cómo se define la meta?</span>
                                    <div class="desktop-pivot" role="radiogroup" aria-labelledby="mode-label-{{ $departamento->cmd_id }}">
                                        <label class="desktop-btn"><input type="radio" name="departamentos[{{ $departamento->cmd_id }}][modo_meta]" value="historica" @checked($modo !== 'manual') @disabled($bloqueado)>Calcular con histórico</label>
                                        <label class="desktop-btn"><input type="radio" name="departamentos[{{ $departamento->cmd_id }}][modo_meta]" value="manual" @checked($modo === 'manual') @disabled($bloqueado)>Definir meta manual</label>
                                    </div>
                                </div>
                                <div class="commission-fields" data-mode-panel="historica">
                                    <div class="desktop-field"><label for="increment-{{ $departamento->cmd_id }}">Incremento sobre la base (%)</label><input id="increment-{{ $departamento->cmd_id }}" type="number" min="0" max="100" step="0.01" inputmode="decimal" name="departamentos[{{ $departamento->cmd_id }}][incremento_meta]" value="{{ old("departamentos.{$departamento->cmd_id}.incremento_meta",$config?->cpd_incremento_meta??0) }}" @disabled($bloqueado)><small class="commission-note">Aumenta la meta. Usa 0% para mantener el promedio. No es la tasa de comisión.</small>@error("departamentos.{$departamento->cmd_id}.incremento_meta")<small class="commission-field-error">{{ $message }}</small>@enderror</div>
                                    <div class="commission-formula" data-formula aria-live="polite"></div>
                                </div>
                                <div class="commission-fields" data-mode-panel="manual" hidden>
                                    <div class="desktop-field"><label for="manual-{{ $departamento->cmd_id }}">Meta por vendedor ($)</label><input id="manual-{{ $departamento->cmd_id }}" type="number" min="0" step="0.01" inputmode="decimal" name="departamentos[{{ $departamento->cmd_id }}][meta_comun]" value="{{ $metaManual }}" placeholder="Ej. 150000" @disabled($bloqueado)><small class="commission-note">Se aplica a todo el equipo del departamento y tiene prioridad sobre la meta sugerida.</small>@error("departamentos.{$departamento->cmd_id}.meta_comun")<small class="commission-field-error">{{ $message }}</small>@enderror</div>
                                    <div class="commission-formula" data-manual-compare aria-live="polite"></div>
                                </div>
                                <p class="commission-help" data-effect hidden></p>
                            </div>
                        </article>
                    @endforeach
                    <div class="commission-empty" data-goals-empty hidden>Activa al menos un departamento con líneas en el paso 1.</div>
                </div>
                <details class="commission-tip"><summary>¿Qué diferencia hay entre incremento, factor comisionable y tasa?</summary><dl>
                    <div><dt>Incremento (este paso)</dt><dd>Aumenta la meta sobre el promedio histórico por vendedor. No cambia cuánto se paga.</dd></div>
                    <div><dt>Factor comisionable (fijo, 33%)</dt><dd>Parte de las ventas netas sobre la que se calcula la comisión. Aplica a toda la venta neta, no solo al excedente.</dd></div>
                    <div><dt>Tasa (paso Equipo)</dt><dd>Porcentaje que se paga sobre esa base cuando el vendedor alcanza su meta. La ordinaria es 0.9%.</dd></div>
                </dl></details>
            </div>
            <p class="commission-error" data-step-error="2" role="alert"></p>
            <footer class="commission-step-panel__footer"><button class="desktop-btn desktop-btn--ghost" type="button" data-previous-step="1">Regresar</button><button class="desktop-btn desktop-btn--primary" type="button" data-next-step="3">Continuar</button></footer>
        </section>

        {{-- Paso 3: equipo, ajustes individuales y tasas --}}
        <section class="commission-step-panel" data-step-panel="3" aria-labelledby="step-3-title" hidden>
            <header class="commission-step-panel__head"><span class="commission-step-panel__icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2M16 3.1a4 4 0 0 1 0 7.8M21 21v-2a4 4 0 0 0-3-3.9"/></svg></span><div><h2 id="step-3-title">Selecciona el equipo del mes</h2><p>Activa a cada vendedor y asígnale departamento. Su meta sigue la meta común; escribe una meta solo para un ajuste individual. El incremento aumenta la meta; la tasa determina la comisión.</p></div></header>
            <div class="commission-step-panel__body">
                <div>
                    <div class="commission-seller-tools"><span class="commission-note" aria-live="polite"><strong data-seller-count>0</strong> vendedores seleccionados</span><div class="commission-seller-tools__end"><div class="desktop-pivot" role="group" aria-label="Filtrar vendedores"><button type="button" class="desktop-btn desktop-btn--active" data-seller-filter="todos" aria-pressed="true">Todos</button><button type="button" class="desktop-btn" data-seller-filter="participantes" aria-pressed="false">Participantes</button></div><input id="commission-seller-search" class="commission-seller-search" type="search" placeholder="Buscar por nombre o usuario" aria-label="Buscar vendedor"></div></div>
                    @error('vendedores')<p class="commission-error-note">{{ $message }}</p>@enderror
                    <div class="commission-sellers"><table class="commission-table"><thead><tr><th scope="col">Participa</th><th scope="col">Vendedor</th><th scope="col">Número</th><th scope="col">Departamento</th><th scope="col">Meta del mes</th><th scope="col">Tasa de comisión</th><th scope="col">Motivo del ajuste</th></tr></thead><tbody>
                        @forelse($usuarios as $usuario)@php $p=$participantes->get($usuario->usr_id);$activo=(bool)old("vendedores.{$usuario->usr_id}.habilitado",(bool)$p);$uid=$usuario->usr_id; @endphp
                        <tr data-seller-row="{{ $uid }}" data-name="{{ $usuario->usr_nombre }}">
                            <td><input class="seller-toggle" type="checkbox" name="vendedores[{{ $uid }}][habilitado]" value="1" aria-label="Participa {{ $usuario->usr_nombre }}" @checked($activo) @disabled($bloqueado)></td>
                            <td><strong>{{ $usuario->usr_nombre }}</strong><small>{{ $usuario->usr_usuario }}</small></td>
                            <td><input name="vendedores[{{ $uid }}][numero]" value="{{ old("vendedores.{$uid}.numero",$p?->cpt_numero_vendedor) }}" maxlength="40" placeholder="Ej. 5" aria-label="Número de {{ $usuario->usr_nombre }}" @disabled($bloqueado)>@error("vendedores.{$uid}.numero")<small class="is-danger">{{ $message }}</small>@enderror</td>
                            <td><select name="vendedores[{{ $uid }}][departamento_id]" aria-label="Departamento de {{ $usuario->usr_nombre }}" @disabled($bloqueado)><option value="">Selecciona</option>@foreach($departamentos as $departamento)<option value="{{ $departamento->cmd_id }}" @selected((string)old("vendedores.{$uid}.departamento_id",$p?->departamentoPeriodo?->cpd_cmd_id)===(string)$departamento->cmd_id)>{{ $departamento->cmd_nombre }}</option>@endforeach</select>@error("vendedores.{$uid}.departamento_id")<small class="is-danger">{{ $message }}</small>@enderror</td>
                            <td><input type="number" min="0" step="0.01" inputmode="decimal" name="vendedores[{{ $uid }}][meta]" value="{{ old("vendedores.{$uid}.meta",$metasIndividuales->get($uid)) }}" placeholder="Meta común" aria-label="Ajuste de meta de {{ $usuario->usr_nombre }}" @disabled($bloqueado)><small data-seller-meta></small></td>
                            <td><select name="vendedores[{{ $uid }}][tasa]" aria-label="Tasa de {{ $usuario->usr_nombre }}" @disabled($bloqueado)>@foreach($tasas as $tasa)<option value="{{ $tasa }}" @selected((float)old("vendedores.{$uid}.tasa",$p?->cpt_tasa_comision??0.9)===(float)$tasa)>{{ number_format($tasa,1) }}%{{ $tasa === 0.9 ? ' · ordinaria' : '' }}</option>@endforeach</select></td>
                            <td><input name="vendedores[{{ $uid }}][motivo]" value="{{ old("vendedores.{$uid}.motivo",$p?->cpt_motivo_ajuste) }}" maxlength="500" placeholder="Solo si ajustas meta o tasa" aria-label="Motivo del ajuste de {{ $usuario->usr_nombre }}" @disabled($bloqueado)><small data-motivo-hint></small>@error("vendedores.{$uid}.motivo")<small class="is-danger">{{ $message }}</small>@enderror</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="commission-empty">No hay usuarios activos asignados a esta sucursal.</td></tr>
                        @endforelse
                    </tbody></table></div>
                    <div class="commission-empty" data-seller-empty hidden>Ningún vendedor coincide con la búsqueda.</div>
                </div>
            </div>
            <p class="commission-error" data-step-error="3" role="alert"></p>
            <footer class="commission-step-panel__footer"><button class="desktop-btn desktop-btn--ghost" type="button" data-previous-step="2">Regresar</button><button class="desktop-btn desktop-btn--primary" type="button" data-next-step="4">Revisar configuración</button></footer>
        </section>

        {{-- Paso 4: revisión, resultados y acciones --}}
        <section class="commission-step-panel" data-step-panel="4" aria-labelledby="step-4-title" hidden>
            <header class="commission-step-panel__head"><span class="commission-step-panel__icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="m9 14 2 2 4-4"/></svg></span><div><h2 id="step-4-title">{{ $bloqueado ? 'Resultados definitivos' : 'Revisa antes de guardar y publicar' }}</h2><p>{{ $bloqueado ? 'El periodo está cerrado. Puedes consultar la configuración y los resultados, pero no modificarlos.' : 'Confirma qué se guardará, resuelve lo pendiente y consulta el avance de cada vendedor.' }}</p></div></header>
            <div class="commission-step-panel__body">
                @unless($bloqueado)
                <div class="commission-review-section"><h3>Pendientes</h3><div data-checklist aria-live="polite"></div></div>
                @endunless
                <div class="commission-review-section">
                    <h3>{{ $bloqueado ? 'Configuración del periodo' : 'Lo que se guardará' }}</h3>
                    <div class="commission-summary"><div class="commission-summary-card"><span>Periodo</span><strong>{{ $mesTexto }}</strong></div><div class="commission-summary-card"><span>Almacenes</span><strong data-summary-warehouses>0</strong></div><div class="commission-summary-card"><span>Departamentos</span><strong data-summary-departments>0</strong></div><div class="commission-summary-card"><span>Vendedores</span><strong data-summary-sellers>0</strong></div></div>
                    <div class="commission-sellers"><table class="commission-table commission-table--compact"><thead><tr><th>Departamento</th><th>Líneas</th><th class="is-number">Vendedores</th><th class="is-number">Meta común</th><th>Cómo se definió</th><th class="is-number">Ajustes individuales</th></tr></thead><tbody data-review-rows></tbody></table></div>
                </div>
                <div class="commission-review-section">
                    <div class="commission-row"><h3>{{ $bloqueado ? 'Resultado definitivo' : 'Avance y comisión estimada' }}</h3>@if($estimaciones->isNotEmpty())<span class="desktop-pill {{ $bloqueado ? 'commission-pill--success' : 'desktop-pill--brand' }}">{{ $bloqueado ? 'Definitivo' : 'Estimado' }}</span>@endif</div>
                    @if($estimaciones->isNotEmpty())
                        <p class="commission-note">{{ $bloqueado ? 'Calculado al cerrar el periodo'.($periodo->cmp_cerrado_at ? ' el '.$periodo->cmp_cerrado_at->format('d/m/Y H:i') : '').'. No cambia aunque se registren ventas o históricos nuevos.' : 'Se calcula con la configuración guardada y las ventas registradas hasta ahora. Cambiará con nuevas ventas hasta que cierres el periodo.' }} Comisión = 33% de la venta neta × tasa, solo si la venta neta alcanza la meta.</p>
                        <p class="commission-danger-note" data-dirty-note hidden><strong>Tienes cambios sin guardar.</strong> Estos resultados aún usan la configuración guardada.</p>
                        <div class="commission-sellers"><table class="commission-table"><thead><tr><th>Vendedor</th><th>Departamento</th><th class="is-number">Venta neta</th><th class="is-number">Meta</th><th>Avance</th><th class="is-number">Base comisionable</th><th class="is-number">Tasa</th><th class="is-number">{{ $bloqueado ? 'Comisión final' : 'Comisión estimada' }}</th></tr></thead><tbody>@foreach($estimaciones as $estimacion)<tr><td><strong>{{ $estimacion->numero }}</strong> · {{ $estimacion->nombre }}</td><td>{{ $estimacion->departamento }}</td><td class="is-number">${{ number_format($estimacion->ventas,2) }}</td><td class="is-number">${{ number_format($estimacion->meta,2) }}</td><td class="commission-estimate__percent"><strong>{{ number_format($estimacion->cumplimiento,2) }}%</strong><div class="commission-progress" role="progressbar" aria-label="Avance de {{ $estimacion->nombre }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ min(100,max(0,$estimacion->cumplimiento)) }}"><span style="width:{{ min(100,max(0,$estimacion->cumplimiento)) }}%"></span></div></td><td class="is-number">${{ number_format($estimacion->base_comisionable,2) }}</td><td class="is-number">{{ number_format($estimacion->tasa,1) }}%</td><td class="is-number"><strong>${{ number_format($estimacion->comision,2) }}</strong><small class="{{ $estimacion->motivo === 'comisiona' ? '' : 'is-warning' }}">{{ $estimacion->explicacion }}</small></td></tr>@endforeach</tbody><tfoot><tr><td colspan="2"><strong>Total</strong></td><td class="is-number"><strong>${{ number_format($estimaciones->sum('ventas'),2) }}</strong></td><td colspan="4"></td><td class="is-number"><strong>${{ number_format($estimaciones->sum('comision'),2) }}</strong></td></tr></tfoot></table></div>
                    @elseif(!$periodo)
                        <div class="commission-empty">Aún no hay resultados porque el periodo no se ha guardado. Guarda el borrador para ver el avance estimado de cada vendedor.</div>
                    @elseif(!$puedeEstimar)
                        <div class="commission-empty">Tu usuario no tiene permiso para consultar estimaciones de comisiones.</div>
                    @else
                        <div class="commission-empty">El periodo guardado no tiene vendedores participantes. Agrégalos en el paso Equipo y guarda.</div>
                    @endif
                </div>
                @if($estado==='aprobado')<div class="desktop-field"><label for="change-reason">Motivo de la corrección <span class="commission-note">(obligatorio para guardar cambios en un periodo publicado)</span></label><textarea id="change-reason" name="motivo_cambio" rows="2" maxlength="1000" placeholder="Ej. Se corrigió la asignación del vendedor...">{{ old('motivo_cambio') }}</textarea>@error('motivo_cambio')<small class="commission-field-error">{{ $message }}</small>@enderror</div>@endif
                <p class="commission-help"><strong>Privacidad:</strong> en su consulta personal, cada vendedor ve solo su porcentaje de avance, su estado y un mensaje. Nunca ve ventas, metas en pesos, tasas ni comisiones.</p>
                @unless($bloqueado)
                <dl class="commission-effects" aria-label="Qué hace cada acción">
                    @if($estado==='aprobado')
                        <div><dt>Guardar corrección</dt><dd>Actualiza metas y equipo ya publicados. Exige motivo y queda en bitácora con el antes y el después.</dd></div>
                        @if($puedeCerrar)<div><dt>Cerrar periodo</dt><dd>Calcula los resultados definitivos con las ventas del mes y los congela. Después no se puede modificar.</dd></div>@endif
                    @else
                        <div><dt>Guardar borrador</dt><dd>Guarda la configuración y calcula las metas. Los vendedores todavía no ven nada.</dd></div>
                        @if($puedeAprobar)<div><dt>Aprobar y publicar avance</dt><dd>Requiere metas positivas para todo el equipo. Los vendedores verán su avance; después, cada cambio pedirá motivo.</dd></div>@endif
                    @endif
                </dl>
                @endunless
            </div>
            <p class="commission-error" data-step-error="4" role="alert"></p>
            <footer class="commission-step-panel__footer"><button class="desktop-btn desktop-btn--ghost" type="button" data-previous-step="3">Regresar</button><div class="commission-final-actions">
                <span class="commission-note" data-action-hint hidden>Guarda los cambios antes de {{ $estado==='aprobado' ? 'cerrar' : 'aprobar' }}.</span>
                @unless($bloqueado)<button type="submit" class="desktop-btn {{ $estado==='aprobado' || ($periodo && $puedeAprobar) ? 'desktop-btn--default' : 'desktop-btn--primary' }}" data-save-button>{{ $estado==='aprobado' ? 'Guardar corrección' : ($periodo ? 'Guardar cambios' : 'Guardar borrador') }}</button>@endunless
                @if($periodo&&$estado==='borrador'&&$puedeAprobar)<button type="submit" form="commission-approve" class="desktop-btn desktop-btn--primary" data-requires-saved>Aprobar y publicar avance</button>@endif
                @if($periodo&&$estado==='aprobado'&&$puedeCerrar)<button type="submit" form="commission-close" class="desktop-btn desktop-btn--danger" data-requires-saved>Cerrar periodo</button>@endif
            </div></footer>
        </section>
    </form>
</div>
@endsection

@push('desktop-scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const wizard = document.querySelector('[data-commission-wizard]');
    if (!wizard) return;
    const cfg = JSON.parse(document.getElementById('commission-data').textContent);
    const locked = cfg.locked;
    const form = document.getElementById('commission-config');
    const panels = Array.from(wizard.querySelectorAll('[data-step-panel]'));
    const targets = Array.from(wizard.querySelectorAll('[data-step-target]'));
    const departments = Array.from(wizard.querySelectorAll('[data-department]'));
    const goals = Array.from(wizard.querySelectorAll('[data-goal]'));
    const rows = Array.from(wizard.querySelectorAll('[data-seller-row]'));
    const money = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });
    const fmt = value => value === null || value === undefined ? '—' : money.format(value);
    const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const plural = (n, one, many) => n + ' ' + (n === 1 ? one : many);
    let current = Number(wizard.dataset.initialStep || 1);
    let preview = cfg.guardada;
    let previewState = locked ? 'ready' : 'loading';
    let previewError = '';
    let dirty = false;
    let submitting = false;
    let timer = null;
    let request = null;
    let sellerFilter = 'todos';

    // ---------- Navegación entre pasos (los datos viven en un solo formulario) ----------
    function showStep(step, scroll = true) {
        current = Math.max(1, Math.min(4, Number(step)));
        panels.forEach(panel => panel.hidden = Number(panel.dataset.stepPanel) !== current);
        targets.forEach(button => {
            const n = Number(button.dataset.stepTarget);
            button.classList.toggle('is-active', n === current);
            button.classList.toggle('is-done', n < current);
            if (n === current) button.setAttribute('aria-current', 'step'); else button.removeAttribute('aria-current');
        });
        try { history.replaceState(null, '', '#paso-' + current); } catch (e) {}
        renderAll();
        if (scroll) form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    function showError(step, message) { const el = wizard.querySelector('[data-step-error="' + step + '"]'); if (el) { el.textContent = message; el.classList.add('is-visible'); } }
    function clearError(step) { wizard.querySelector('[data-step-error="' + step + '"]')?.classList.remove('is-visible'); }
    function validate(step) {
        clearError(step);
        if (locked) return true;
        if (step === 1) {
            if (!wizard.querySelector('input[name="almacen_ids[]"]:checked')) { showError(1, 'Selecciona al menos un almacén para continuar.'); return false; }
            const active = activeDepartments();
            if (!active.length) { showError(1, 'Activa al menos un departamento.'); return false; }
            const sinLineas = active.filter(card => !lineInputs(card).some(i => i.checked));
            if (sinLineas.length) { showError(1, sinLineas.map(c => c.dataset.name).join(', ') + ': selecciona al menos una línea.'); return false; }
        }
        if (step === 3 && !rows.some(isParticipant)) { showError(3, 'Selecciona al menos un vendedor para continuar.'); return false; }
        return true;
    }

    // ---------- Lectura del formulario ----------
    const lineInputs = card => Array.from(card.querySelectorAll('input[name*="[linea_ids]"]'));
    const activeDepartments = () => departments.filter(card => card.querySelector('.department-toggle')?.checked);
    const isParticipant = row => Boolean(row.querySelector('.seller-toggle')?.checked);
    const field = (row, name) => row.querySelector('[name$="[' + name + ']"]');
    const deptName = id => departments.find(card => card.dataset.department === String(id))?.dataset.name || '';
    const modeOf = goal => goal.querySelector('input[type="radio"]:checked')?.value || 'historica';
    const pending = () => previewState !== 'ready' || !preview;

    // ---------- Paso 1 ----------
    function refreshScope() {
        const owners = {};
        departments.forEach(card => {
            if (!card.querySelector('.department-toggle')?.checked) return;
            lineInputs(card).forEach(input => { if (input.checked) owners[input.value] = card.dataset.name; });
        });
        departments.forEach(card => {
            const enabled = Boolean(card.querySelector('.department-toggle')?.checked);
            card.classList.toggle('is-disabled', !enabled);
            lineInputs(card).forEach(input => {
                const owner = owners[input.value];
                const takenByOther = Boolean(owner) && owner !== card.dataset.name;
                input.disabled = locked || !enabled || (takenByOther && !input.checked);
                input.closest('label').title = takenByOther && !input.checked ? 'Asignada a ' + owner : '';
            });
            const count = lineInputs(card).filter(i => i.checked).length;
            const label = card.querySelector('[data-line-count]');
            if (label) label.textContent = enabled ? plural(count, 'línea seleccionada', 'líneas seleccionadas') : 'No participa';
        });
    }

    // ---------- Vista previa (cálculo en el servidor con las mismas reglas que al guardar) ----------
    function schedulePreview() {
        if (locked) return;
        previewState = 'loading';
        renderGoals();
        clearTimeout(timer);
        timer = setTimeout(fetchPreview, 350);
    }
    async function fetchPreview() {
        request?.abort();
        request = new AbortController();
        const data = new FormData(form);
        data.delete('_method');
        try {
            const response = await fetch(cfg.previewUrl, { method: 'POST', body: data, signal: request.signal, headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            const json = await response.json().catch(() => ({}));
            if (!response.ok) {
                const first = json.errors ? Object.values(json.errors)[0]?.[0] : null;
                throw new Error(first || json.message || 'No se pudo consultar el histórico.');
            }
            preview = json;
            previewState = 'ready';
            previewError = '';
        } catch (error) {
            if (error.name === 'AbortError') return;
            previewState = 'error';
            previewError = error.message || 'No se pudo consultar el histórico.';
        }
        renderAll();
    }

    // ---------- Paso 2 ----------
    const statusInfo = {
        completa: ['Histórico disponible', 'commission-pill--success'],
        parcial: ['Histórico parcial', 'commission-pill--warning'],
        sin_base: ['Base insuficiente', 'commission-pill--danger'],
        sin_alcance: ['Falta seleccionar alcance', 'desktop-pill--neutral'],
        guardada: ['Referencia guardada', 'desktop-pill--brand'],
    };
    const sourceText = {
        sistema: 'Fuente: ventas registradas en el sistema.',
        manual: 'Fuente: capturas manuales de ventas históricas.',
        mixto: 'Fuente: capturas manuales y ventas del sistema. Cada captura sustituye al sistema solo en su almacén y línea.',
    };
    const fuenteText = { manual: 'Captura manual', sistema: 'Ventas del sistema', sin_datos: 'Sin datos' };
    function captureUrl(params) {
        const url = new URL(cfg.historicoUrl, window.location.origin);
        Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v));
        return url.toString();
    }
    function renderGoals() {
        const activeIds = activeDepartments().map(card => card.dataset.department);
        let visible = 0;
        goals.forEach(goal => {
            const id = goal.dataset.goal;
            goal.hidden = !activeIds.includes(id);
            if (goal.hidden) return;
            visible++;
            const mode = modeOf(goal);
            goal.querySelectorAll('.desktop-pivot label').forEach(label => label.classList.toggle('desktop-btn--active', label.querySelector('input').checked));
            goal.querySelectorAll('[data-mode-panel]').forEach(panel => panel.hidden = panel.dataset.modePanel !== mode);
            const d = !pending() ? preview.departamentos?.[id] : null;
            const status = goal.querySelector('[data-ref-status]');
            const [text, cls] = previewState === 'loading' ? ['Consultando histórico…', 'desktop-pill--neutral']
                : previewState === 'error' ? ['No se pudo consultar', 'commission-pill--danger']
                : d ? statusInfo[d.estado] : ['Pendiente de consultar', 'desktop-pill--neutral'];
            status.textContent = text;
            status.className = 'desktop-pill ' + cls;
            goal.querySelector('[data-ref-ventas]').textContent = d ? fmt(d.ventas) : '—';
            goal.querySelector('[data-ref-auto]').textContent = d ? '− ' + fmt(d.autoservicio) : '—';
            goal.querySelector('[data-ref-base]').textContent = d ? fmt(d.base) : '—';
            const source = goal.querySelector('[data-ref-source]');
            source.textContent = d ? (d.estado === 'guardada' ? 'Valores guardados al configurar el periodo.' : (sourceText[d.origen] || '')) : '';
            source.hidden = !source.textContent;
            renderReferenceWarning(goal, d);
            renderReferenceDetail(goal, d);
            renderFormula(goal, d, mode);
            renderEffect(goal, d);
        });
        wizard.querySelector('[data-goals-empty]').hidden = visible > 0;
    }
    function renderReferenceWarning(goal, d) {
        const box = goal.querySelector('[data-ref-warning]');
        let html = '';
        const capture = (text = 'Capturar ventas históricas') => '<a href="' + esc(cfg.historicoUrl) + '" data-leave-guard>' + text + '</a>';
        if (previewState === 'error') html = esc(previewError) + ' <button type="button" class="desktop-btn desktop-btn--ghost" data-retry-preview>Reintentar</button>';
        else if (d?.estado === 'sin_alcance') html = 'Selecciona almacenes y líneas en el paso 1 para consultar la referencia.';
        else if (d?.estado === 'sin_base') html = d.ventas > 0
            ? 'Todo el importe de ' + esc(cfg.referenciaTexto) + ' corresponde a autoservicio: no queda base para repartir. ' + capture('Captura las ventas históricas') + ' o define una meta manual.'
            : 'No hay ventas del sistema ni capturas de ' + esc(cfg.referenciaTexto) + ' para estas líneas y almacenes. ' + capture('Captura las ventas históricas') + ' o define una meta manual.';
        else if (d?.estado === 'parcial') {
            const total = d.combinaciones.length;
            html = '<strong>Referencia incompleta:</strong> faltan datos de ' + plural(d.faltantes, 'combinación', 'combinaciones') + ' de almacén y línea (de ' + total + '). La base solo suma las que tienen datos. Si alguna no tuvo ventas, captúrala en $0 para confirmarlo. ' + capture() + '.';
        }
        box.innerHTML = html;
        box.hidden = !html;
        box.className = d?.estado === 'sin_base' || previewState === 'error' ? 'commission-error-note' : 'commission-danger-note';
    }
    function renderReferenceDetail(goal, d) {
        const details = goal.querySelector('[data-ref-detail]');
        const combos = d?.combinaciones || [];
        details.hidden = combos.length === 0;
        if (!combos.length) return;
        if (d.faltantes > 0 && !details.dataset.touched) details.open = true;
        goal.querySelector('[data-ref-rows]').innerHTML = combos.map(c => {
            const action = c.fuente === 'manual'
                ? '<a href="' + esc(captureUrl({ editar: c.captura_id })) + '" data-leave-guard>Corregir</a>'
                : '<a href="' + esc(captureUrl({ almacen: c.almacen_id, linea: c.linea_id })) + '" data-leave-guard>Capturar</a>';
            const note = c.fuente === 'manual'
                ? (c.documento ? '<small>' + esc(c.documento) + '</small>' : '') + (c.sistema_sustituido !== null ? '<small>Sustituye ' + fmt(c.sistema_sustituido) + ' del sistema</small>' : '')
                : '';
            const missing = c.fuente === 'sin_datos';
            return '<tr class="' + (missing ? 'is-missing' : '') + '"><td>' + esc(c.almacen) + '</td><td>' + esc(c.linea) + '</td><td>' + fuenteText[c.fuente] + note + '</td>'
                + '<td class="is-number">' + (missing ? '—' : fmt(c.ventas)) + '</td><td class="is-number">' + (missing ? '—' : fmt(c.autoservicio)) + '</td><td class="is-number">' + (missing ? '—' : fmt(c.base)) + '</td><td>' + action + '</td></tr>';
        }).join('');
    }
    function renderFormula(goal, d, mode) {
        const name = goal.dataset.name;
        const formula = goal.querySelector('[data-formula]');
        const compare = goal.querySelector('[data-manual-compare]');
        let html = '';
        if (previewState === 'loading') html = 'Calculando la meta sugerida…';
        else if (!d) html = 'Sin datos para calcular.';
        else if (d.falta === 'alcance') html = 'Primero selecciona almacenes y líneas en el paso 1.';
        else if (d.base <= 0) html = 'Sin base histórica no se puede sugerir una meta. Captura el histórico o elige <strong>Definir meta manual</strong>.';
        else if (d.vendedores === 0) html = 'La base de ' + fmt(d.base) + ' está lista. Falta asignar vendedores a ' + esc(name) + ' para dividirla.<br><button type="button" class="desktop-btn desktop-btn--ghost" data-go-step="3">Ir a Equipo</button>';
        else html = fmt(d.base) + ' ÷ ' + plural(d.vendedores, 'vendedor', 'vendedores') + ' = ' + fmt(d.promedio) + ' por vendedor<br>'
            + fmt(d.promedio) + ' + ' + Number(d.incremento).toLocaleString('es-MX') + '% de incremento = <strong>' + fmt(d.sugerida) + '</strong> meta sugerida por vendedor';
        formula.innerHTML = html;
        let compareHtml = '';
        if (previewState === 'loading') compareHtml = 'Consultando la meta sugerida…';
        else if (d && d.meta_manual === null) compareHtml = '<strong>Escribe la meta</strong> para poder aprobar el periodo.' + (d.sugerida !== null ? ' Como referencia, la sugerida es ' + fmt(d.sugerida) + '.' : '');
        else if (d && d.sugerida !== null) compareHtml = 'Se usará <strong>' + fmt(d.meta_manual) + '</strong> en lugar de la sugerida (' + fmt(d.sugerida) + ').';
        else if (d) compareHtml = 'Se usará <strong>' + fmt(d.meta_manual) + '</strong>. No hay meta sugerida para comparar.';
        compare.innerHTML = compareHtml;
    }
    function renderEffect(goal, d) {
        const box = goal.querySelector('[data-effect]');
        let text = '';
        const saved = d?.guardado;
        if (d && saved && !locked && previewState === 'ready') {
            const before = saved.meta_comun, after = d.meta_comun;
            const differs = before !== null && after !== null && Math.abs(before - after) > 0.009;
            const kept = d.ajustes > 0 ? ' ' + plural(d.ajustes, 'vendedor con ajuste individual conserva su meta.', 'vendedores con ajuste individual conservan su meta.') : '';
            if (differs) text = 'Meta común guardada: ' + fmt(before) + '. Al guardar cambiará a ' + fmt(after) + ' para el equipo sin ajuste individual.' + kept;
            else if (before !== null && after === null) text = 'Meta común guardada: ' + fmt(before) + '. Con esta selección no habría meta; resuelve lo que falta antes de guardar.';
            if (text && cfg.estado === 'aprobado') text += ' El periodo está publicado: guardar requiere motivo.';
        }
        box.textContent = text;
        box.hidden = !text;
    }
    function selectMode(goal, mode) {
        const manual = goal.querySelector('[data-mode-panel="manual"] input');
        const d = preview?.departamentos?.[goal.dataset.goal];
        // Al pasar a manual se parte de la última sugerida conocida para no capturar desde cero.
        if (mode === 'manual' && manual && !manual.value && d?.sugerida) manual.value = d.sugerida.toFixed(2);
    }

    // ---------- Paso 3 ----------
    function renderSellers() {
        let count = 0, shown = 0;
        const term = (document.getElementById('commission-seller-search')?.value || '').trim().toLocaleLowerCase('es');
        rows.forEach(row => {
            const active = isParticipant(row);
            if (active) count++;
            row.classList.toggle('is-off', !active);
            row.querySelectorAll('input:not(.seller-toggle),select').forEach(el => el.disabled = locked || !active);
            row.hidden = (sellerFilter === 'participantes' && !active) || (term !== '' && !row.dataset.name.toLocaleLowerCase('es').includes(term) && !row.innerText.toLocaleLowerCase('es').includes(term));
            if (!row.hidden) shown++;
            const hint = row.querySelector('[data-seller-meta]');
            const motivoHint = row.querySelector('[data-motivo-hint]');
            const motivo = field(row, 'motivo');
            const metaInput = field(row, 'meta');
            const s = !pending() ? preview.vendedores?.[row.dataset.sellerRow] : null;
            let text = '', cls = '';
            if (!active) text = '';
            else if (!field(row, 'departamento_id')?.value) { text = 'Elige un departamento.'; cls = 'is-warning'; }
            else if (previewState === 'loading') text = 'Calculando…';
            else if (!s) text = '';
            else if (s.origen === 'comun') text = 'Usa la meta común: ' + fmt(s.meta);
            else if (s.origen === 'individual' && s.meta_comun !== null && Math.abs(s.meta - s.meta_comun) > 0.009) { text = 'Ajuste individual. Común: ' + fmt(s.meta_comun); cls = 'is-warning'; }
            else if (s.origen === 'individual') text = 'Meta individual';
            else { text = 'Sin meta: defínela en el paso 2.'; cls = 'is-danger'; }
            hint.textContent = text;
            hint.className = cls;
            if (metaInput) metaInput.placeholder = s?.meta_comun ? 'Común: ' + fmt(s.meta_comun) : 'Meta común';
            const required = active && Boolean(s?.requiere_motivo);
            const missing = required && !motivo.value.trim();
            motivo.classList.toggle('is-required', missing);
            motivo.setAttribute('aria-invalid', missing ? 'true' : 'false');
            motivoHint.textContent = required ? (missing ? 'Obligatorio: explica el ajuste de meta o tasa.' : 'Ajuste justificado.') : '';
            motivoHint.className = missing ? 'is-warning' : '';
        });
        wizard.querySelectorAll('[data-seller-count]').forEach(el => el.textContent = count);
        const empty = wizard.querySelector('[data-seller-empty]');
        if (empty) empty.hidden = shown > 0 || rows.length === 0;
    }

    // ---------- Paso 4 ----------
    function issues() {
        const save = [], approve = [];
        if (locked) return { save, approve };
        if (!wizard.querySelector('input[name="almacen_ids[]"]:checked')) save.push(['Selecciona al menos un almacén.', 1]);
        const active = activeDepartments();
        if (!active.length) save.push(['Activa al menos un departamento.', 1]);
        active.forEach(card => { if (!lineInputs(card).some(i => i.checked)) save.push([card.dataset.name + ': selecciona al menos una línea.', 1]); });
        (preview?.lineas_repetidas || []).forEach(line => save.push(['La línea ' + line + ' está en dos departamentos.', 1]));
        const numbers = {};
        rows.filter(isParticipant).forEach(row => {
            const name = row.dataset.name;
            const numero = field(row, 'numero').value.trim();
            const dep = field(row, 'departamento_id').value;
            const sel = '[data-seller-row="' + row.dataset.sellerRow + '"] ';
            if (!numero) save.push([name + ': falta el número de vendedor.', 3, sel + '[name$="[numero]"]']);
            else if (numbers[numero]) save.push([name + ' y ' + numbers[numero] + ' tienen el mismo número.', 3]);
            else numbers[numero] = name;
            if (!dep || !active.some(card => card.dataset.department === dep)) save.push([name + ': elige un departamento activo.', 3]);
            const s = !pending() ? preview.vendedores?.[row.dataset.sellerRow] : null;
            if (s?.requiere_motivo && !field(row, 'motivo').value.trim()) save.push([name + ': explica el ajuste de meta o tasa.', 3, sel + '[name$="[motivo]"]']);
        });
        if (cfg.estado === 'aprobado' && dirty && !document.getElementById('change-reason')?.value.trim()) save.push(['Escribe el motivo de la corrección.', 4, '#change-reason']);
        active.forEach(card => {
            const d = !pending() ? preview.departamentos?.[card.dataset.department] : null;
            const team = rows.filter(row => isParticipant(row) && field(row, 'departamento_id').value === card.dataset.department).length;
            if (!team) approve.push([card.dataset.name + ': asigna al menos un vendedor.', 3]);
            else if (d?.falta === 'meta_manual') approve.push([card.dataset.name + ': escribe la meta manual.', 2, '#manual-' + card.dataset.department]);
            else if (d?.falta === 'base') approve.push([card.dataset.name + ': no hay base histórica; captúrala o define una meta manual.', 2]);
        });
        return { save, approve };
    }
    function renderReview() {
        const warehouses = wizard.querySelectorAll('input[name="almacen_ids[]"]:checked').length;
        const active = activeDepartments();
        const sellers = rows.filter(isParticipant);
        wizard.querySelector('[data-summary-warehouses]').textContent = warehouses;
        wizard.querySelector('[data-summary-departments]').textContent = active.length;
        wizard.querySelector('[data-summary-sellers]').textContent = sellers.length;
        const body = wizard.querySelector('[data-review-rows]');
        body.innerHTML = active.length ? active.map(card => {
            const id = card.dataset.department;
            const d = !pending() ? preview.departamentos?.[id] : null;
            const lines = lineInputs(card).filter(i => i.checked).map(i => i.dataset.lineName);
            const team = sellers.filter(row => field(row, 'departamento_id').value === id).length;
            const goal = goals.find(g => g.dataset.goal === id);
            const how = modeOf(goal) === 'manual' ? 'Meta manual' : 'Calculada con histórico' + (d && d.estado === 'parcial' ? ' (referencia parcial)' : '');
            const meta = previewState === 'loading' ? 'Calculando…' : (d?.meta_comun !== null && d?.meta_comun !== undefined ? fmt(d.meta_comun) : '<span class="commission-field-error">Sin meta</span>');
            return '<tr><td><strong>' + esc(card.dataset.name) + '</strong></td><td>' + (lines.length ? esc(lines.join(', ')) : '—') + '</td><td class="is-number">' + team + '</td><td class="is-number">' + meta + '</td><td>' + how + '</td><td class="is-number">' + (d ? d.ajustes : '—') + '</td></tr>';
        }).join('') : '<tr><td colspan="6" class="commission-empty">Ningún departamento activo.</td></tr>';

        const list = wizard.querySelector('[data-checklist]');
        if (list) {
            const { save, approve } = issues();
            const item = ([text, step, focus], blocking) => '<li class="' + (blocking ? 'is-blocking' : '') + '"><span>' + esc(text) + '</span><button type="button" class="desktop-btn desktop-btn--ghost" data-go-step="' + step + '"' + (focus ? ' data-focus="' + esc(focus) + '"' : '') + '>' + (step === current ? 'Resolver' : 'Ir al paso ' + step) + '</button></li>';
            let html = '';
            if (previewState === 'loading') html += '<p class="commission-note">Revisando la configuración…</p>';
            if (save.length) html += '<p class="commission-checklist__group">Para guardar</p><ul class="commission-checklist">' + save.map(i => item(i, true)).join('') + '</ul>';
            if (approve.length) html += '<p class="commission-checklist__group">Para aprobar (puedes guardar el borrador antes)</p><ul class="commission-checklist">' + approve.map(i => item(i, false)).join('') + '</ul>';
            if (!save.length && !approve.length && previewState === 'ready') html += '<ul class="commission-checklist"><li class="is-ok"><span>' + (cfg.estado === 'aprobado' && !dirty ? 'Sin cambios pendientes. El periodo está publicado.' : 'Todo listo. Puedes guardar' + (cfg.estado === 'borrador' && !dirty ? ' o aprobar' : '') + '.') + '</span></li></ul>';
            list.innerHTML = html;
        }
        wizard.querySelector('[data-dirty-note]')?.toggleAttribute('hidden', !dirty);
        wizard.querySelectorAll('[data-requires-saved]').forEach(button => button.disabled = dirty);
        const hint = wizard.querySelector('[data-action-hint]');
        if (hint) hint.hidden = !(dirty && wizard.querySelector('[data-requires-saved]'));
    }

    function renderAll() { refreshScope(); renderGoals(); renderSellers(); renderReview(); }

    // ---------- Eventos ----------
    targets.forEach(button => button.addEventListener('click', () => showStep(button.dataset.stepTarget)));
    wizard.querySelectorAll('[data-next-step]').forEach(button => button.addEventListener('click', () => { if (validate(current)) showStep(button.dataset.nextStep); }));
    wizard.querySelectorAll('[data-previous-step]').forEach(button => button.addEventListener('click', () => showStep(button.dataset.previousStep)));
    wizard.addEventListener('click', event => {
        const go = event.target.closest('[data-go-step]');
        if (go) {
            event.preventDefault();
            const focus = go.dataset.focus;
            showStep(go.dataset.goStep, !focus);
            if (focus) { const target = wizard.querySelector(focus); target?.scrollIntoView({ behavior: 'smooth', block: 'center' }); target?.focus({ preventScroll: true }); }
            return;
        }
        if (event.target.closest('[data-retry-preview]')) { event.preventDefault(); schedulePreview(); }
        const summary = event.target.closest('[data-ref-detail] > summary');
        if (summary) summary.parentElement.dataset.touched = '1';
    });
    wizard.querySelectorAll('[data-seller-filter]').forEach(button => button.addEventListener('click', () => {
        sellerFilter = button.dataset.sellerFilter;
        wizard.querySelectorAll('[data-seller-filter]').forEach(b => { const on = b === button; b.classList.toggle('desktop-btn--active', on); b.setAttribute('aria-pressed', on ? 'true' : 'false'); });
        renderSellers();
    }));
    document.getElementById('commission-seller-search')?.addEventListener('input', renderSellers);
    form.addEventListener('change', event => {
        const target = event.target;
        if (!target.name) return;
        dirty = true;
        if (target.type === 'radio' && target.name.endsWith('[modo_meta]')) selectMode(target.closest('[data-goal]'), target.value);
        renderAll();
        schedulePreview();
    });
    form.addEventListener('input', event => {
        const target = event.target;
        if (!target.name || target.type === 'checkbox' || target.type === 'radio' || target.tagName === 'SELECT') return;
        dirty = true;
        if (/\[(incremento_meta|meta_comun|meta)\]$/.test(target.name)) schedulePreview();
        else { renderSellers(); renderReview(); }
    });
    form.addEventListener('submit', () => { submitting = true; });

    // Las acciones que publican o cierran se confirman y no pierden de vista los cambios sin guardar.
    document.querySelectorAll('form[data-confirm]').forEach(target => target.addEventListener('submit', async event => {
        if (target.dataset.confirmed === '1') { submitting = true; return; }
        event.preventDefault();
        if (dirty) { DesktopUI.toast('Guarda los cambios antes de continuar.', 'error'); return; }
        const close = target.dataset.confirm === 'close';
        const ok = await DesktopUI.confirm(close
            ? { title: '¿Cerrar el periodo?', message: 'Se calcularán los resultados definitivos con las ventas registradas y quedarán congelados. Ya no podrás modificar metas, equipo ni tasas.', okText: 'Cerrar periodo', danger: true }
            : { title: '¿Aprobar y publicar el avance?', message: 'Los vendedores verán su porcentaje de avance, estado y mensaje, sin importes. Después de aprobar, cada cambio requerirá motivo y quedará en bitácora.', okText: 'Aprobar y publicar' });
        if (ok) { target.dataset.confirmed = '1'; target.requestSubmit(); }
    }));
    // Salir con cambios sin guardar pide confirmación (cambiar de mes, ir al histórico).
    document.addEventListener('click', async event => {
        const link = event.target.closest('a[data-leave-guard]');
        if (!link || !dirty || event.ctrlKey || event.metaKey) return;
        event.preventDefault();
        if (await leaveConfirmed()) { submitting = true; window.location.href = link.href; }
    });
    document.querySelectorAll('form[data-leave-guard]').forEach(target => target.addEventListener('submit', async event => {
        if (!dirty || target.dataset.confirmed === '1') { submitting = true; return; }
        event.preventDefault();
        if (await leaveConfirmed()) { target.dataset.confirmed = '1'; submitting = true; target.requestSubmit(); }
    }));
    function leaveConfirmed() {
        return DesktopUI.confirm({ title: 'Tienes cambios sin guardar', message: 'Si sales ahora se perderán los cambios de esta configuración.', okText: 'Salir sin guardar', danger: true });
    }
    window.addEventListener('beforeunload', event => { if (dirty && !submitting) { event.preventDefault(); event.returnValue = ''; } });

    // Con un equipo ya elegido se muestran primero sus integrantes; "Todos" permite agregar más.
    if (rows.some(isParticipant)) wizard.querySelector('[data-seller-filter="participantes"]')?.click();
    const hashStep = Number((window.location.hash.match(/^#paso-([1-4])$/) || [])[1]);
    if (hashStep && wizard.dataset.hasErrors !== '1') current = hashStep;
    showStep(current, false);
    if (!locked) fetchPreview();
});
</script>
@endpush
