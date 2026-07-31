@extends('layouts.desktop')

@section('title', 'Configuración de etiquetas')

@push('desktop-styles')
<style>
    .etq-shell{display:flex;flex-direction:column;min-height:calc(100vh - 118px);background:var(--surface)}
    .etq-head{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;padding:18px 22px 14px;border-bottom:1px solid var(--stroke)}
    .etq-head h1{margin:0;font-size:1.08rem;font-weight:650;color:var(--text)}
    .etq-head p{margin:5px 0 0;font-size:.78rem;color:var(--text-2)}
    .etq-steps{display:flex;align-items:center;gap:0;padding:10px 22px;background:var(--surface-alt);border-bottom:1px solid var(--stroke);overflow:auto}
    .etq-step{display:flex;align-items:center;gap:8px;white-space:nowrap;font-size:.76rem;color:var(--text-2)}
    .etq-step:not(:last-child)::after{content:"";width:34px;height:1px;margin:0 12px;background:var(--stroke)}
    .etq-step__num{display:grid;place-items:center;width:22px;height:22px;border-radius:50%;border:1px solid var(--stroke);background:var(--surface);font-weight:650}
    .etq-step.is-done{color:var(--success)} .etq-step.is-done .etq-step__num{background:#e8f7ef;border-color:#a8dfbe}
    .etq-tabs{display:flex;gap:4px;padding:10px 22px 0;border-bottom:1px solid var(--stroke);background:var(--surface)}
    .etq-tab{position:relative;border:0;background:transparent;padding:9px 12px 11px;color:var(--text-2);font:inherit;font-size:.8rem;font-weight:580;cursor:pointer}
    .etq-tab.is-active{color:var(--brand)} .etq-tab.is-active::after{content:"";position:absolute;left:8px;right:8px;bottom:-1px;height:2px;background:var(--brand)}
    .etq-badge{display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:18px;margin-left:5px;padding:0 6px;border-radius:9px;background:var(--surface-alt);font-size:.68rem;color:var(--text-2)}
    .etq-tab.is-active .etq-badge{background:var(--brand-soft);color:var(--brand)}
    .etq-panel{padding:16px 22px 24px}.etq-panel[hidden]{display:none!important}
    .etq-panelbar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}
    .etq-panelbar__copy strong{display:block;font-size:.86rem}.etq-panelbar__copy span{font-size:.74rem;color:var(--text-2)}
    .etq-table{width:100%;border-collapse:collapse;font-size:.78rem}.etq-table th{padding:9px 11px;background:var(--surface-alt);border:1px solid var(--stroke);text-align:left;font-size:.7rem;text-transform:uppercase;letter-spacing:.035em;color:var(--text-2)}
    .etq-table td{padding:10px 11px;border:1px solid var(--stroke);vertical-align:middle}.etq-table tbody tr:hover{background:#fafcff}
    .etq-name{font-weight:630;color:var(--text)}.etq-meta{display:block;margin-top:2px;font-size:.7rem;color:var(--text-2)}
    .etq-status{display:inline-flex;padding:3px 8px;border-radius:10px;font-size:.68rem;font-weight:600}.etq-status--active{background:#e8f7ef;color:#18794e}.etq-status--inactive{background:#f1f3f5;color:#68707a}
    .etq-actions{display:flex;justify-content:flex-end;gap:5px}.etq-iconbtn{display:grid;place-items:center;width:30px;height:28px;border:1px solid var(--stroke);border-radius:5px;background:var(--surface);color:var(--text-2);cursor:pointer}.etq-iconbtn:hover{border-color:var(--brand);color:var(--brand)}.etq-iconbtn svg{width:15px;height:15px}
    .etq-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:310px;padding:32px;border:1px dashed var(--stroke);background:var(--surface-alt);text-align:center}
    .etq-empty__icon{display:grid;place-items:center;width:50px;height:50px;margin-bottom:12px;border-radius:50%;background:var(--brand-soft);color:var(--brand)}.etq-empty__icon svg{width:24px;height:24px}.etq-empty h3{margin:0;font-size:.92rem}.etq-empty p{max-width:430px;margin:7px 0 16px;font-size:.76rem;color:var(--text-2);line-height:1.5}
    .etq-warning{display:flex;align-items:center;gap:9px;padding:9px 11px;margin-bottom:12px;border:1px solid #f0d79a;background:#fffaf0;color:#7a5514;font-size:.75rem}.etq-warning svg{width:17px;height:17px;flex:none}
    .etq-modal{position:fixed;inset:0;z-index:1080;display:none;align-items:center;justify-content:center;padding:18px}.etq-modal.is-open{display:flex}.etq-modal__scrim{position:absolute;inset:0;background:rgba(22,28,36,.42)}
    .etq-modal__dialog{position:relative;width:min(920px,96vw);max-height:92vh;display:flex;flex-direction:column;background:var(--surface);border:1px solid var(--stroke);border-radius:8px;box-shadow:0 22px 60px rgba(0,0,0,.2)}
    .etq-modal__head{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;border-bottom:1px solid var(--stroke)}.etq-modal__title{font-size:.94rem;font-weight:650}.etq-modal__close{border:0;background:transparent;font-size:1.4rem;color:var(--text-2);cursor:pointer}
    .etq-modal__body{display:grid;grid-template-columns:minmax(0,1fr) 280px;overflow:auto}.etq-form{padding:18px}.etq-preview{padding:18px;border-left:1px solid var(--stroke);background:var(--surface-alt)}
    .etq-preview__title{margin-bottom:12px;font-size:.73rem;font-weight:620;color:var(--text-2)}.etq-preview__stage{display:grid;place-items:center;min-height:280px;padding:18px;background:#e9edf2;border:1px solid var(--stroke)}
    .etq-label{position:relative;display:block;overflow:hidden;padding:0;background:white;border:1px solid #87909b;box-shadow:0 3px 10px rgba(0,0,0,.12);color:#17191c}.etq-label__bars{height:34%;margin:8px 9px 0;background:repeating-linear-gradient(90deg,#111 0 2px,#fff 2px 4px,#111 4px 5px,#fff 5px 8px)}.etq-label__meta{margin:2px 9px 0;text-align:center;font-size:8px;font-weight:400}.etq-label__name{margin:8px 9px 0;font-size:10px;font-weight:700}.etq-label__price{position:absolute;right:9px;bottom:7px;text-align:right;font-size:15px;font-weight:800}
    .etq-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.etq-grid .is-full{grid-column:1/-1}.etq-field label{display:block;margin-bottom:5px;font-size:.72rem;font-weight:600;color:var(--text-2)}.etq-field input,.etq-field select,.etq-field textarea{width:100%;height:36px;padding:0 9px;border:1px solid var(--stroke);border-radius:5px;background:var(--surface);color:var(--text);font:inherit;font-size:.78rem}.etq-field textarea{height:64px;padding-top:8px;resize:vertical}.etq-field input:focus,.etq-field select:focus,.etq-field textarea:focus{outline:0;border-color:var(--brand);box-shadow:0 0 0 2px var(--brand-soft)}
    .etq-sectiontitle{grid-column:1/-1;margin-top:4px;padding-top:12px;border-top:1px solid var(--stroke);font-size:.73rem;font-weight:650;color:var(--text)}
    .etq-modal__foot{display:flex;justify-content:flex-end;gap:8px;padding:12px 18px;border-top:1px solid var(--stroke)}
    .etq-checks{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:7px}.etq-check{display:flex;align-items:center;gap:7px;padding:8px;border:1px solid var(--stroke);border-radius:5px;font-size:.73rem;cursor:pointer}.etq-check:has(input:checked){border-color:var(--brand);background:var(--brand-soft);color:var(--brand)}
    .etq-rulecopy{font-size:.7rem;color:var(--text-2)}
    .etq-feedback{display:none;margin:0 22px;padding:9px 12px;border-radius:5px;font-size:.76rem}.etq-feedback.is-visible{display:block}.etq-feedback--error{background:#fff0f0;color:#a32929;border:1px solid #efb8b8}.etq-feedback--success{background:#edf9f2;color:#18794e;border:1px solid #b7e2c7}
    @media(max-width:850px){.etq-modal__body{grid-template-columns:1fr}.etq-preview{border-left:0;border-top:1px solid var(--stroke)}.etq-checks{grid-template-columns:repeat(2,1fr)}}
</style>
@endpush

@section('desktop-toolbar')
<div class="desktop-toolbar__group">
    <a href="{{ route('desktop.operacion.catalogo_comercial.etiquetado.index') }}" class="desktop-btn desktop-btn--ghost">‹ Etiquetado por SKU</a>
</div>
<div class="desktop-toolbar__group">
    <button type="button" class="desktop-btn desktop-btn--primary" data-new-format>Nuevo formato</button>
</div>
@endsection

@section('content')
@php
    $formatosActivos = $formatos->where('etf_estatus', 'activo');
    $plantillasActivas = $plantillas->where('etp_estatus', 'activo');
    $lineasAsignadas = $configuraciones->where('elc_estatus', 'activo')->pluck('elc_lna_id')->unique();
    $lineasSinAsignar = $lineas->whereNotIn('lna_id', $lineasAsignadas);
    $unidadesSinRegla = $unidades->filter(fn($u) => !isset($reglas[$u->umd_id]));
@endphp
<section class="desktop-pane etq-shell">
    <div class="etq-head">
        <div><h1>Configuración de etiquetas</h1><p>Define una vez las reglas; durante la recepción el sistema elegirá el tamaño automáticamente.</p></div>
    </div>
    <div class="etq-steps">
        <div class="etq-step {{ $formatos->isNotEmpty() ? 'is-done' : '' }}"><span class="etq-step__num">1</span>Crear formato</div>
        <div class="etq-step {{ $plantillas->isNotEmpty() ? 'is-done' : '' }}"><span class="etq-step__num">2</span>Definir contenido</div>
        <div class="etq-step {{ $lineasAsignadas->isNotEmpty() ? 'is-done' : '' }}"><span class="etq-step__num">3</span>Asignar líneas</div>
        <div class="etq-step {{ $unidadesSinRegla->isEmpty() ? 'is-done' : '' }}"><span class="etq-step__num">4</span>Reglas de cantidad</div>
    </div>
    <div id="etq-feedback" class="etq-feedback"></div>
    <nav class="etq-tabs" aria-label="Secciones de etiquetas">
        <button type="button" class="etq-tab is-active" data-tab="formatos">Formatos <span class="etq-badge">{{ $formatos->count() }}</span></button>
        <button type="button" class="etq-tab" data-tab="plantillas">Plantillas <span class="etq-badge">{{ $plantillas->count() }}</span></button>
        <button type="button" class="etq-tab" data-tab="asignaciones">Asignación por línea <span class="etq-badge">{{ $lineasSinAsignar->count() }} pendientes</span></button>
        <button type="button" class="etq-tab" data-tab="unidades">Reglas por unidad <span class="etq-badge">{{ $unidadesSinRegla->count() }} pendientes</span></button>
        <button type="button" class="etq-tab" data-tab="historial">Historial</button>
    </nav>

    <div class="etq-panel" data-panel="formatos">
        <div class="etq-panelbar"><div class="etq-panelbar__copy"><strong>Formatos físicos</strong><span>Medidas reales de papel o etiqueta térmica.</span></div><button type="button" class="desktop-btn desktop-btn--primary" data-new-format>Nuevo formato</button></div>
        @if($formatos->isEmpty())
        <div class="etq-empty"><div class="etq-empty__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16v16H4z"/><path d="M8 4v4H4M16 20v-4h4"/></svg></div><h3>Aún no hay formatos</h3><p>Crea el tamaño físico de tu primera etiqueta. Puedes empezar con 50 × 30 mm para una impresora térmica.</p><button type="button" class="desktop-btn desktop-btn--primary" data-new-format>Crear primer formato</button></div>
        @else
        <table class="etq-table"><thead><tr><th>Formato</th><th>Dimensiones</th><th>Salida</th><th>Líneas asignadas</th><th>Estado</th><th style="width:110px;text-align:right">Acciones</th></tr></thead><tbody>
        @foreach($formatos as $f)<tr><td><span class="etq-name">{{ $f->etf_nombre }}</span><span class="etq-meta">{{ $f->etf_descripcion ?: 'Sin descripción' }}</span></td><td>{{ number_format((float)$f->etf_ancho_mm, 1) }} × {{ number_format((float)$f->etf_alto_mm, 1) }} mm<span class="etq-meta">{{ ucfirst($f->etf_orientacion) }}</span></td><td>{{ $f->etf_tipo_salida === 'hoja' ? 'Hoja / '.($f->etf_columnas * $f->etf_filas).' por página' : 'Térmica / individual' }}</td><td>{{ $f->configuraciones_linea_count }}</td><td><span class="etq-status etq-status--{{ $f->etf_estatus === 'activo' ? 'active' : 'inactive' }}">{{ ucfirst($f->etf_estatus) }}</span></td><td><div class="etq-actions"><button type="button" class="etq-iconbtn" title="Editar" data-edit-format='@json($f)'><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m4 20 4.2-1 10.7-10.7-3.2-3.2L5 15.8 4 20Z"/><path d="m14.5 6.5 3 3"/></svg></button><button type="button" class="etq-iconbtn" title="Duplicar" data-duplicate-format='@json($f)'><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/></svg></button></div></td></tr>@endforeach
        </tbody></table>
        @endif
    </div>

    <div class="etq-panel" data-panel="plantillas" hidden>
        <div class="etq-panelbar"><div class="etq-panelbar__copy"><strong>Plantillas de contenido</strong><span>Elige la información que aparecerá en cada tipo de etiqueta.</span></div><button type="button" class="desktop-btn desktop-btn--primary" data-new-template>Nueva plantilla</button></div>
        @if($plantillas->isEmpty())
        <div class="etq-empty"><div class="etq-empty__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 3h12v18H6z"/><path d="M9 8h6M9 12h6M9 16h4"/></svg></div><h3>Define qué lleva la etiqueta</h3><p>Selecciona visualmente nombre, código, precio, talla y demás campos. No necesitas usar combinaciones de teclado.</p><button type="button" class="desktop-btn desktop-btn--primary" data-new-template>Crear primera plantilla</button></div>
        @else
        <table class="etq-table"><thead><tr><th>Plantilla</th><th>Campos incluidos</th><th>Estado</th><th style="width:70px;text-align:right">Acciones</th></tr></thead><tbody>@foreach($plantillas as $p)<tr><td><span class="etq-name">{{ $p->etp_nombre }}</span><span class="etq-meta">{{ $p->etp_descripcion ?: 'Sin descripción' }}</span></td><td>{{ collect($p->etp_campos)->map(fn($c)=>ucfirst(str_replace('_',' ',$c)))->join(', ') }}</td><td><span class="etq-status etq-status--{{ $p->etp_estatus === 'activo' ? 'active' : 'inactive' }}">{{ ucfirst($p->etp_estatus) }}</span></td><td><div class="etq-actions"><button type="button" class="etq-iconbtn" title="Editar plantilla" data-edit-template='@json($p)'><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m4 20 4.2-1 10.7-10.7-3.2-3.2L5 15.8 4 20Z"/><path d="m14.5 6.5 3 3"/></svg></button></div></td></tr>@endforeach</tbody></table>
        @endif
    </div>

    <div class="etq-panel" data-panel="asignaciones" hidden>
        <div class="etq-panelbar"><div class="etq-panelbar__copy"><strong>Asignación automática por línea</strong><span>Cada línea debe tener exactamente un formato y una plantilla activos.</span></div><button type="button" class="desktop-btn desktop-btn--primary" data-new-assignment {{ $formatosActivos->isEmpty() || $plantillasActivas->isEmpty() ? 'disabled' : '' }}>Asignar línea</button></div>
        @if($formatosActivos->isEmpty() || $plantillasActivas->isEmpty())<div class="etq-warning"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3 2 21h20L12 3Z"/><path d="M12 9v5M12 18h.01"/></svg>Antes de asignar líneas necesitas al menos un formato y una plantilla activos.</div>@endif
        @if($lineasSinAsignar->isNotEmpty())<div class="etq-warning"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>{{ $lineasSinAsignar->count() }} líneas todavía no podrán imprimir etiquetas.</div>@endif
        <table class="etq-table"><thead><tr><th>Línea</th><th>Formato físico</th><th>Plantilla</th><th>Estado</th><th style="width:70px"></th></tr></thead><tbody>@forelse($lineas as $l)@php($c=$configuraciones->firstWhere('elc_lna_id',$l->lna_id))<tr><td><span class="etq-name">{{ $l->lna_nombre }}</span><span class="etq-meta">{{ $l->lna_clave }}</span></td><td>{{ $c?->formato?->etf_nombre ?: 'Sin configurar' }}</td><td>{{ $c?->plantilla?->etp_nombre ?: 'Sin configurar' }}</td><td>@if($c)<span class="etq-status etq-status--{{ $c->elc_estatus === 'activo' ? 'active':'inactive' }}">{{ ucfirst($c->elc_estatus) }}</span>@else<span class="etq-status etq-status--inactive">Pendiente</span>@endif</td><td><button type="button" class="etq-iconbtn" title="{{ $c ? 'Cambiar asignación':'Configurar' }}" data-assign-line="{{ $l->lna_id }}" data-format-id="{{ $c?->elc_etf_id }}" data-template-id="{{ $c?->elc_etp_id }}" data-assignment-status="{{ $c?->elc_estatus ?: 'activo' }}" {{ $formatosActivos->isEmpty() || $plantillasActivas->isEmpty() ? 'disabled' : '' }}><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m4 20 4.2-1 10.7-10.7-3.2-3.2L5 15.8 4 20Z"/></svg></button></td></tr>@empty<tr><td colspan="5">No hay líneas activas.</td></tr>@endforelse</tbody></table>
    </div>

    <div class="etq-panel" data-panel="unidades" hidden>
        <div class="etq-panelbar"><div class="etq-panelbar__copy"><strong>Reglas de cantidad</strong><span>Indica cómo convertir la cantidad recibida en número de etiquetas.</span></div></div>
        @if($unidadesSinRegla->isNotEmpty())<div class="etq-warning"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>{{ $unidadesSinRegla->count() }} unidades necesitan una regla explícita. El sistema no adivinará su comportamiento.</div>@endif
        <table class="etq-table"><thead><tr><th>Unidad de venta</th><th>Comportamiento</th><th>Ejemplo</th><th style="width:92px"></th></tr></thead><tbody>
        @foreach($unidades as $u)
            @php($reglaUnidad = $reglas[$u->umd_id]->eur_regla ?? '')
            <tr><td><span class="etq-name">{{ $u->umd_nombre }}</span><span class="etq-meta">{{ $u->umd_codigo }}</span></td><td><select class="desktop-toolbar__select" data-rule-select="{{ $u->umd_id }}"><option value="">Seleccionar regla…</option><option value="por_unidad_recibida" @selected($reglaUnidad==='por_unidad_recibida')>Una etiqueta por unidad recibida</option><option value="por_detalle_recepcion" @selected($reglaUnidad==='por_detalle_recepcion')>Una etiqueta por producto/detalle</option></select></td><td class="etq-rulecopy" data-rule-example="{{ $u->umd_id }}">{{ $reglaUnidad === 'por_unidad_recibida' ? '10 recibidas → 10 etiquetas' : ($reglaUnidad === 'por_detalle_recepcion' ? '50 recibidos → 1 etiqueta' : 'Sin configurar') }}</td><td><button type="button" class="desktop-btn desktop-btn--default" data-save-rule="{{ $u->umd_id }}">Guardar</button></td></tr>
        @endforeach
        </tbody></table>
    </div>

    <div class="etq-panel" data-panel="historial" hidden>
        <div class="etq-panelbar"><div class="etq-panelbar__copy"><strong>Historial de generación</strong><span>Archivos generados desde recepciones de mercancía.</span></div></div>
        @if($historial->isEmpty())
            <div class="etq-empty"><div class="etq-empty__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l3 2"/></svg></div><h3>Aún no hay impresiones</h3><p>Cuando generes etiquetas desde una recepción, aquí aparecerán sus archivos y cantidades.</p></div>
        @else
            <table class="etq-table">
                <thead><tr><th>Recepción</th><th>Modo</th><th>Etiquetas</th><th>Estado</th><th>Fecha</th><th>Archivos</th></tr></thead>
                <tbody>
                @foreach($historial as $h)
                    <tr>
                        <td class="etq-name">{{ $h->rme_folio }}</td>
                        <td>{{ $h->eim_modo === 'separado' ? 'Por formato' : 'PDF único' }}</td>
                        <td>{{ $h->eim_total_etiquetas }}</td>
                        <td><span class="etq-status etq-status--active">{{ ucfirst($h->eim_estatus) }}</span></td>
                        <td>{{ $h->eim_generado_at }}</td>
                        <td><div class="etq-actions">
                            @foreach($h->archivos as $archivo)
                                <a class="desktop-btn desktop-btn--primary" target="_blank" rel="noopener" href="{{ route('desktop.operacion.etiquetas.archivos.ver', $archivo->eia_id) }}" title="{{ $archivo->eia_nombre }}">Ver PDF</a>
                            @endforeach
                            @if($h->archivos->count() > 1)
                                <a class="desktop-btn desktop-btn--default" href="{{ route('desktop.operacion.etiquetas.impresiones.zip', $h->eim_id) }}">ZIP</a>
                            @endif
                        </div></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
</section>

<div class="etq-modal" id="format-modal" aria-hidden="true"><div class="etq-modal__scrim" data-close-modal></div><form class="etq-modal__dialog" id="format-form"><div class="etq-modal__head"><div class="etq-modal__title" id="format-modal-title">Nuevo formato</div><button type="button" class="etq-modal__close" data-close-modal>×</button></div><div class="etq-modal__body"><div class="etq-form"><input type="hidden" name="etf_id"><div class="etq-grid"><div class="etq-field is-full"><label>Nombre del formato *</label><input name="etf_nombre" maxlength="120" placeholder="Ej. Etiqueta ropa estándar" required></div><div class="etq-field is-full"><label>Descripción</label><textarea name="etf_descripcion" placeholder="Cuándo y para qué mercancía se utiliza"></textarea></div><div class="etq-field"><label>Ancho (mm) *</label><input type="number" name="etf_ancho_mm" min="10" max="500" step=".1" value="50" required></div><div class="etq-field"><label>Alto (mm) *</label><input type="number" name="etf_alto_mm" min="10" max="500" step=".1" value="30" required></div><div class="etq-field"><label>Orientación</label><select name="etf_orientacion"><option value="auto">Automática</option><option value="horizontal">Horizontal</option><option value="vertical">Vertical</option></select></div><div class="etq-field"><label>Tipo de salida</label><select name="etf_tipo_salida" id="format-output"><option value="termica">Térmica / individual</option><option value="hoja">Hoja con varias etiquetas</option></select></div><div class="etq-sectiontitle">Márgenes internos</div>@foreach(['izq'=>'Izquierdo','der'=>'Derecho','sup'=>'Superior','inf'=>'Inferior'] as $key=>$label)<div class="etq-field"><label>{{ $label }} (mm)</label><input type="number" name="etf_margen_{{ $key }}_mm" min="0" max="50" step=".1" value="2"></div>@endforeach<div class="etq-sectiontitle" data-sheet-field hidden>Distribución en hoja</div><div class="etq-field" data-sheet-field hidden><label>Columnas</label><input type="number" name="etf_columnas" min="1" max="20" value="1"></div><div class="etq-field" data-sheet-field hidden><label>Filas</label><input type="number" name="etf_filas" min="1" max="20" value="1"></div><div class="etq-field" data-sheet-field hidden><label>Separación horizontal (mm)</label><input type="number" name="etf_separacion_h_mm" min="0" max="50" step=".1" value="0"></div><div class="etq-field" data-sheet-field hidden><label>Separación vertical (mm)</label><input type="number" name="etf_separacion_v_mm" min="0" max="50" step=".1" value="0"></div><div class="etq-field is-full"><label>Compatibilidad / impresora</label><input name="etf_compatibilidad_impresora" placeholder="Ej. Zebra ZD220, cualquier impresora PDF"></div><div class="etq-field"><label>Estado</label><select name="etf_estatus"><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select></div></div></div><aside class="etq-preview"><div class="etq-preview__title">Vista previa proporcional</div><div class="etq-preview__stage"><div class="etq-label" id="format-preview"><div class="etq-label__bars"></div><div class="etq-label__meta">SKU-001-AZUL-CH</div><div class="etq-label__name">Playera deportiva / Azul / CH</div><div class="etq-label__price">$299.00</div></div></div><span class="etq-meta" id="format-preview-size" style="text-align:center;margin-top:9px">50 × 30 mm</span></aside></div><div class="etq-modal__foot"><button type="button" class="desktop-btn desktop-btn--default" data-close-modal>Cancelar</button><button type="submit" class="desktop-btn desktop-btn--primary">Guardar formato</button></div></form></div>

<div class="etq-modal" id="template-modal" aria-hidden="true"><div class="etq-modal__scrim" data-close-modal></div><form class="etq-modal__dialog" id="template-form" style="width:min(760px,96vw)"><div class="etq-modal__head"><div class="etq-modal__title" id="template-modal-title">Nueva plantilla</div><button type="button" class="etq-modal__close" data-close-modal>×</button></div><div class="etq-form"><input type="hidden" name="etp_id"><div class="etq-grid"><div class="etq-field"><label>Nombre *</label><input name="etp_nombre" placeholder="Ej. Contenido ropa" required></div><div class="etq-field"><label>Estado</label><select name="etp_estatus"><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select></div><div class="etq-field is-full"><label>Descripción</label><textarea name="etp_descripcion" placeholder="Contenido para etiquetas de ropa con talla y color"></textarea></div><div class="etq-sectiontitle">Campos que aparecerán</div><div class="etq-checks is-full">@foreach(['nombre_producto'=>'Producto','sku'=>'SKU','codigo_barras'=>'Código de barras','precio'=>'Precio','linea'=>'Línea','talla'=>'Talla','color'=>'Color','unidad'=>'Unidad','cantidad'=>'Cantidad/metraje','sucursal'=>'Sucursal','fecha_recepcion'=>'Fecha recepción','folio_recepcion'=>'Folio entrada'] as $key=>$label)<label class="etq-check"><input type="checkbox" name="etp_campos[]" value="{{ $key }}">{{ $label }}</label>@endforeach</div></div></div><div class="etq-modal__foot"><button type="button" class="desktop-btn desktop-btn--default" data-close-modal>Cancelar</button><button type="submit" class="desktop-btn desktop-btn--primary">Guardar plantilla</button></div></form></div>

<div class="etq-modal" id="assignment-modal" aria-hidden="true"><div class="etq-modal__scrim" data-close-modal></div><form class="etq-modal__dialog" id="assignment-form" style="width:min(560px,96vw)"><div class="etq-modal__head"><div class="etq-modal__title">Asignar línea</div><button type="button" class="etq-modal__close" data-close-modal>×</button></div><div class="etq-form"><div class="etq-grid"><div class="etq-field is-full"><label>Línea de producto *</label><select name="elc_lna_id" required>@foreach($lineas as $l)<option value="{{ $l->lna_id }}">{{ $l->lna_nombre }}</option>@endforeach</select></div><div class="etq-field is-full"><label>Formato físico *</label><select name="elc_etf_id" required>@foreach($formatosActivos as $f)<option value="{{ $f->etf_id }}">{{ $f->etf_nombre }} — {{ $f->etf_ancho_mm }} × {{ $f->etf_alto_mm }} mm</option>@endforeach</select></div><div class="etq-field is-full"><label>Plantilla de contenido *</label><select name="elc_etp_id" required>@foreach($plantillasActivas as $p)<option value="{{ $p->etp_id }}">{{ $p->etp_nombre }}</option>@endforeach</select></div><div class="etq-field is-full"><label>Estado de la asignación</label><select name="elc_estatus"><option value="activo">Activa</option><option value="inactivo">Inactiva</option></select></div></div></div><div class="etq-modal__foot"><button type="button" class="desktop-btn desktop-btn--default" data-close-modal>Cancelar</button><button type="submit" class="desktop-btn desktop-btn--primary">Guardar asignación</button></div></form></div>
@endsection

@push('desktop-scripts')
<script>
(function(){
    const csrf=@json(csrf_token()), formatStore=@json(route('desktop.operacion.etiquetas.formatos.store')), formatBase=@json(url('/desktop/operacion/etiquetas/formatos')), templateStore=@json(route('desktop.operacion.etiquetas.plantillas.store')), templateBase=@json(url('/desktop/operacion/etiquetas/plantillas')), assignmentStore=@json(route('desktop.operacion.etiquetas.asignaciones.store')), ruleStore=@json(route('desktop.operacion.etiquetas.reglas.store'));
    const $feedback=$('#etq-feedback');
    function showFeedback(type,message){$feedback.removeClass('etq-feedback--error etq-feedback--success').addClass('is-visible etq-feedback--'+type).text(message);window.scrollTo({top:0,behavior:'smooth'});}
    function errors(xhr){const bag=xhr.responseJSON?.errors||{};return Object.values(bag).flat().join(' ')||xhr.responseJSON?.message||'No fue posible completar la operación.';}
    function request(url,method,data){return $.ajax({url,method,data,headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'}});}
    function openModal(id){$(id).addClass('is-open').attr('aria-hidden','false');$('body').css('overflow','hidden');}
    function closeModals(){$('.etq-modal').removeClass('is-open').attr('aria-hidden','true');$('body').css('overflow','');}
    $('[data-close-modal]').on('click',closeModals);$(document).on('keydown',e=>{if(e.key==='Escape')closeModals();});
    $('.etq-tab').on('click',function(){$('.etq-tab').removeClass('is-active');$(this).addClass('is-active');$('[data-panel]').prop('hidden',true);$('[data-panel="'+this.dataset.tab+'"]').prop('hidden',false);});
    function resetFormat(data={}){const f=document.getElementById('format-form');f.reset();f.etf_id.value=data.etf_id||'';f.etf_nombre.value=data.etf_nombre||'';f.etf_descripcion.value=data.etf_descripcion||'';['ancho','alto'].forEach((k,i)=>f['etf_'+k+'_mm'].value=data['etf_'+k+'_mm']||(i?30:50));f.etf_orientacion.value=data.etf_orientacion||'auto';f.etf_tipo_salida.value=data.etf_tipo_salida||'termica';['izq','der','sup','inf'].forEach(k=>f['etf_margen_'+k+'_mm'].value=data['etf_margen_'+k+'_mm']??2);f.etf_columnas.value=data.etf_columnas||1;f.etf_filas.value=data.etf_filas||1;f.etf_separacion_h_mm.value=data.etf_separacion_h_mm||0;f.etf_separacion_v_mm.value=data.etf_separacion_v_mm||0;f.etf_compatibilidad_impresora.value=data.etf_compatibilidad_impresora||'';f.etf_estatus.value=data.etf_estatus||'activo';$('#format-modal-title').text(data.etf_id?'Editar formato':'Nuevo formato');syncFormat();}
    function syncFormat(){const f=document.getElementById('format-form'),sheet=f.etf_tipo_salida.value==='hoja';$('[data-sheet-field]').prop('hidden',!sheet);let w=Math.max(10,Number(f.etf_ancho_mm.value)||50),h=Math.max(10,Number(f.etf_alto_mm.value)||30);if(f.etf_orientacion.value==='horizontal'&&h>w)[w,h]=[h,w];if(f.etf_orientacion.value==='vertical'&&w>h)[w,h]=[h,w];const scale=Math.min(220/w,150/h);$('#format-preview').css({width:(w*scale)+'px',height:(h*scale)+'px'});$('#format-preview-size').text(w+' × '+h+' mm');}
    $('[data-new-format]').on('click',()=>{resetFormat();openModal('#format-modal');});$('[data-edit-format]').on('click',function(){resetFormat($(this).data('edit-format'));openModal('#format-modal');});$('[data-duplicate-format]').on('click',function(){const d={...$(this).data('duplicate-format'),etf_id:null};d.etf_nombre=(d.etf_nombre||'')+' (copia)';resetFormat(d);openModal('#format-modal');});$('#format-form').on('input change',syncFormat).on('submit',function(e){e.preventDefault();const id=this.etf_id.value,url=id?formatBase+'/'+id:formatStore,method=id?'PUT':'POST';request(url,method,$(this).serialize()).done(r=>{showFeedback('success',r.message);setTimeout(()=>location.reload(),500);}).fail(x=>showFeedback('error',errors(x)));});
    function resetTemplate(data={}){const f=document.getElementById('template-form');f.reset();f.etp_id.value=data.etp_id||'';f.etp_nombre.value=data.etp_nombre||'';f.etp_descripcion.value=data.etp_descripcion||'';f.etp_estatus.value=data.etp_estatus||'activo';const selected=data.etp_campos||['nombre_producto','codigo_barras','precio'];$(f).find('[name="etp_campos[]"]').each(function(){this.checked=selected.includes(this.value);});$('#template-modal-title').text(data.etp_id?'Editar plantilla':'Nueva plantilla');}
    $('[data-new-template]').on('click',()=>{resetTemplate();openModal('#template-modal');});$('[data-edit-template]').on('click',function(){resetTemplate($(this).data('edit-template'));openModal('#template-modal');});$('#template-form').on('submit',function(e){e.preventDefault();const id=this.etp_id.value,url=id?templateBase+'/'+id:templateStore,method=id?'PUT':'POST';request(url,method,$(this).serialize()).done(r=>{showFeedback('success',r.message);setTimeout(()=>location.reload(),500);}).fail(x=>showFeedback('error',errors(x)));});
    function openAssignment(data={}){const f=document.getElementById('assignment-form');f.reset();if(data.lineId)f.elc_lna_id.value=String(data.lineId);if(data.formatId)f.elc_etf_id.value=String(data.formatId);if(data.templateId)f.elc_etp_id.value=String(data.templateId);f.elc_estatus.value=data.status||'activo';openModal('#assignment-modal');}$('[data-new-assignment]').on('click',()=>openAssignment());$('[data-assign-line]').on('click',function(){openAssignment({lineId:this.dataset.assignLine,formatId:this.dataset.formatId,templateId:this.dataset.templateId,status:this.dataset.assignmentStatus});});$('#assignment-form').on('submit',function(e){e.preventDefault();request(assignmentStore,'POST',$(this).serialize()).done(r=>{showFeedback('success',r.message);setTimeout(()=>location.reload(),500);}).fail(x=>showFeedback('error',errors(x)));});
    $('[data-rule-select]').on('change',function(){const t=this.value==='por_unidad_recibida'?'10 recibidas → 10 etiquetas':this.value==='por_detalle_recepcion'?'50 recibidos → 1 etiqueta':'Sin configurar';$('[data-rule-example="'+this.dataset.ruleSelect+'"]').text(t);});$('[data-save-rule]').on('click',function(){const id=this.dataset.saveRule,regla=$('[data-rule-select="'+id+'"]').val();if(!regla){showFeedback('error','Selecciona una regla antes de guardar.');return;}request(ruleStore,'POST',{eur_umd_id:id,eur_regla:regla}).done(r=>showFeedback('success',r.message)).fail(x=>showFeedback('error',errors(x)));});
    syncFormat();
})();
</script>
@endpush
