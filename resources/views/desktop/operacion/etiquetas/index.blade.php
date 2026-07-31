@extends('layouts.desktop')

@section('title', 'Configuración de etiquetas')

@push('desktop-styles')
    <style>
        /* Tira de avance: misma altura y lenguaje que las filas de indicadores. */
        .desktop-etq-steps {
            flex: 0 0 auto;
            display: flex; align-items: stretch;
            border-bottom: 1px solid var(--stroke);
            background: var(--surface-alt);
            overflow-x: auto;
        }
        .desktop-etq-step {
            display: flex; align-items: center; gap: 8px;
            padding: 8px 14px;
            border-right: 1px solid var(--divider);
            font-size: .76rem; font-weight: 600; color: var(--text-2);
            white-space: nowrap;
        }
        .desktop-etq-step:last-child { border-right: 0; }
        .desktop-etq-step__num {
            display: grid; place-items: center;
            width: 20px; height: 20px; flex: none;
            border: 1px solid var(--stroke-strong); border-radius: 50%;
            background: var(--surface);
            font-size: .68rem; font-weight: 700; color: var(--text-2);
        }
        .desktop-etq-step.is-done { color: var(--success); }
        .desktop-etq-step.is-done .desktop-etq-step__num { background: var(--success); border-color: var(--success); color: #fff; }

        /* Paneles de sección */
        .desktop-etq-panel { flex: 1 1 auto; min-height: 0; display: flex; flex-direction: column; }
        .desktop-etq-panel[hidden] { display: none; }

        /* Las acciones contextuales de la command bar se ocultan con [hidden];
           .desktop-btn declara display:inline-flex y gana al estilo del navegador. */
        [data-tab-only][hidden] { display: none !important; }

        .desktop-etq-bar {
            flex: 0 0 auto;
            display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
            padding: 7px 12px;
            border-bottom: 1px solid var(--stroke);
            background: var(--surface);
        }
        .desktop-etq-bar__copy { font-size: .78rem; color: var(--text-2); }
        .desktop-etq-bar__copy strong { color: var(--text); font-weight: 600; }
        .desktop-etq-bar .desktop-btn { height: 30px; }

        .desktop-etq-alert {
            flex: 0 0 auto;
            display: flex; align-items: center; gap: 8px;
            padding: 7px 12px;
            border-bottom: 1px solid var(--warning-stroke);
            background: var(--warning-soft);
            color: var(--warning);
            font-size: .76rem;
        }
        .desktop-etq-alert svg { width: 16px; height: 16px; flex: none; }

        .desktop-etq-num { text-align: right; font-variant-numeric: tabular-nums; }
        .desktop-etq-dim { font-variant-numeric: tabular-nums; white-space: nowrap; }
        .desktop-etq-select { min-width: 260px; max-width: 320px; }
        .desktop-etq-pills { display: flex; flex-wrap: wrap; gap: 4px; }
        .desktop-etq-hint { margin: 0 0 12px; font-size: .74rem; color: var(--text-2); }

        /* Estado vacío con acción, dentro del área de lista. */
        .desktop-etq-empty {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 4px; padding: 48px 24px; text-align: center;
        }
        .desktop-etq-empty__icon {
            display: grid; place-items: center;
            width: 44px; height: 44px; margin-bottom: 8px;
            border-radius: 50%; background: var(--brand-soft); color: var(--brand);
        }
        .desktop-etq-empty__icon svg { width: 22px; height: 22px; }
        .desktop-etq-empty h3 { margin: 0; font-size: .92rem; font-weight: 600; color: var(--text); }
        .desktop-etq-empty p { max-width: 430px; margin: 0 0 12px; font-size: .78rem; line-height: 1.5; color: var(--text-2); }

        /* Modal de formato: formulario + vista previa proporcional. */
        .desktop-etq-split { display: grid; grid-template-columns: minmax(0, 1fr) 260px; gap: 16px; }
        .desktop-etq-preview {
            padding: 12px; border: 1px solid var(--stroke); border-radius: var(--r-md);
            background: var(--surface-alt);
            display: flex; flex-direction: column; align-items: center;
        }
        .desktop-etq-preview__title { align-self: flex-start; margin-bottom: 10px; font-size: .72rem; font-weight: 600; color: var(--text-2); }
        .desktop-etq-preview__stage {
            display: grid; place-items: center;
            width: 100%; min-height: 240px; padding: 16px;
            background: #e9edf2; border: 1px solid var(--stroke); border-radius: var(--r-sm);
        }
        .desktop-etq-preview__size { margin-top: 9px; font-size: .72rem; color: var(--text-2); font-variant-numeric: tabular-nums; }
        .desktop-etq-label {
            position: relative; display: block; overflow: hidden;
            background: #fff; border: 1px solid #87909b; color: #17191c;
            box-shadow: 0 3px 10px rgba(0,0,0,.12);
        }
        .desktop-etq-label__bars { height: 34%; margin: 8px 9px 0; background: repeating-linear-gradient(90deg,#111 0 2px,#fff 2px 4px,#111 4px 5px,#fff 5px 8px); }
        .desktop-etq-label__meta { margin: 2px 9px 0; text-align: center; font-size: 8px; }
        .desktop-etq-label__name { margin: 8px 9px 0; font-size: 10px; font-weight: 700; }
        .desktop-etq-label__price { position: absolute; right: 9px; bottom: 7px; font-size: 15px; font-weight: 800; }

        .desktop-etq-checks { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 2px 12px; }
        .desktop-etq-section { grid-column: 1 / -1; margin-top: 4px; padding-top: 10px; border-top: 1px solid var(--divider); font-size: .78rem; font-weight: 600; color: var(--text); }

        @media (max-width: 900px) {
            .desktop-etq-split { grid-template-columns: minmax(0, 1fr); }
            .desktop-etq-checks { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .desktop-etq-select { min-width: 180px; }
        }
    </style>
@endpush

@section('desktop-toolbar')
    @php
        $formatosActivos = $formatos->where('etf_estatus', 'activo');
        $plantillasActivas = $plantillas->where('etp_estatus', 'activo');
        $lineasAsignadas = $configuraciones->where('elc_estatus', 'activo')->pluck('elc_lna_id')->unique();
        $lineasSinAsignar = $lineas->whereNotIn('lna_id', $lineasAsignadas);
        $unidadesSinRegla = $unidades->filter(fn ($u) => !isset($reglas[$u->umd_id]));
        $puedeAsignar = $formatosActivos->isNotEmpty() && $plantillasActivas->isNotEmpty();
    @endphp

    <div class="desktop-toolbar__group">
        <div class="desktop-pivot" role="tablist" aria-label="Secciones de etiquetas">
            <button type="button" class="desktop-btn desktop-btn--active" data-tab="formatos" aria-current="page">Formatos</button>
            <button type="button" class="desktop-btn" data-tab="plantillas">Plantillas</button>
            <button type="button" class="desktop-btn" data-tab="asignaciones">Asignación por línea</button>
            <button type="button" class="desktop-btn" data-tab="unidades">Reglas por unidad</button>
            <button type="button" class="desktop-btn" data-tab="historial">Historial</button>
        </div>

        <span class="desktop-toolbar__divider"></span>

        <button type="button" class="desktop-btn desktop-btn--primary" data-new-format data-tab-only="formatos">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5v14"/></svg>
            Nuevo formato
        </button>
        <button type="button" class="desktop-btn desktop-btn--primary" data-new-template data-tab-only="plantillas" hidden>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5v14"/></svg>
            Nueva plantilla
        </button>
        <button type="button" class="desktop-btn desktop-btn--primary" data-new-assignment data-tab-only="asignaciones" hidden @disabled(!$puedeAsignar)>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5v14"/></svg>
            Asignar línea
        </button>

        <button type="button" class="desktop-btn desktop-btn--ghost" id="btn-recargar-etiquetas">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
            Actualizar
        </button>
    </div>

    <div class="desktop-toolbar__group">
        <input type="search" id="etq-buscar" class="desktop-toolbar__search" placeholder="Buscar en la sección">
        <span class="desktop-toolbar__divider"></span>
        <a href="{{ route('desktop.operacion.catalogo_comercial.etiquetado.index') }}" class="desktop-btn desktop-btn--ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Etiquetado por SKU
        </a>
    </div>
@endsection

@section('content')
    <section class="desktop-pane">
        {{-- Avance de configuración --}}
        <div class="desktop-etq-steps" aria-label="Avance de la configuración">
            <span class="desktop-etq-step {{ $formatos->isNotEmpty() ? 'is-done' : '' }}"><span class="desktop-etq-step__num">1</span> Crear formato</span>
            <span class="desktop-etq-step {{ $plantillas->isNotEmpty() ? 'is-done' : '' }}"><span class="desktop-etq-step__num">2</span> Definir contenido</span>
            <span class="desktop-etq-step {{ $lineasAsignadas->isNotEmpty() ? 'is-done' : '' }}"><span class="desktop-etq-step__num">3</span> Asignar líneas</span>
            <span class="desktop-etq-step {{ $unidadesSinRegla->isEmpty() ? 'is-done' : '' }}"><span class="desktop-etq-step__num">4</span> Reglas de cantidad</span>
        </div>

        {{-- ── Formatos ─────────────────────────────────────────── --}}
        <div class="desktop-etq-panel" data-panel="formatos">
            <div class="desktop-etq-bar">
                <span class="desktop-etq-bar__copy">
                    <strong>Formatos físicos.</strong>
                    Medidas reales del papel o de la etiqueta térmica.
                </span>
            </div>

            <div class="desktop-list-wrap">
                @if($formatos->isEmpty())
                    <div class="desktop-etq-empty">
                        <span class="desktop-etq-empty__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v16H4z"/><path d="M8 4v4H4M16 20v-4h4"/></svg>
                        </span>
                        <h3>Aún no hay formatos</h3>
                        <p>Crea el tamaño físico de tu primera etiqueta. Puedes empezar con 50 × 30 mm para una impresora térmica.</p>
                        <button type="button" class="desktop-btn desktop-btn--primary" data-new-format>Crear primer formato</button>
                    </div>
                @else
                    <table class="desktop-list">
                        <thead>
                            <tr>
                                <th>Formato</th>
                                <th style="width:170px;">Dimensiones</th>
                                <th style="width:210px;">Salida</th>
                                <th style="width:130px; text-align:right;">Líneas asignadas</th>
                                <th style="width:120px;">Estatus</th>
                                <th style="width:56px; text-align:right;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($formatos as $formato)
                                <tr>
                                    <td>
                                        <span class="desktop-list__name">{{ $formato->etf_nombre }}</span>
                                        <span class="desktop-list__meta">{{ $formato->etf_descripcion ?: 'Sin descripción' }}</span>
                                    </td>
                                    <td class="desktop-etq-dim">
                                        {{ number_format((float) $formato->etf_ancho_mm, 1) }} × {{ number_format((float) $formato->etf_alto_mm, 1) }} mm
                                        <span class="desktop-list__meta">{{ ucfirst($formato->etf_orientacion) }}</span>
                                    </td>
                                    <td>
                                        <span class="desktop-pill desktop-pill--neutral">
                                            {{ $formato->etf_tipo_salida === 'hoja' ? 'Hoja · '.($formato->etf_columnas * $formato->etf_filas).' por página' : 'Térmica · individual' }}
                                        </span>
                                    </td>
                                    <td class="desktop-etq-num">{{ $formato->configuraciones_linea_count }}</td>
                                    <td>
                                        <span class="desktop-status {{ $formato->etf_estatus === 'activo' ? 'desktop-status--active' : 'desktop-status--inactive' }}">
                                            {{ ucfirst($formato->etf_estatus) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="desktop-rowmenu">
                                            <button type="button" class="desktop-overflow" data-overflow aria-haspopup="true" aria-expanded="false" aria-label="Acciones del formato">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                                            </button>
                                            <div class="desktop-menu">
                                                <button type="button" class="desktop-menu__item" data-edit-format='@json($formato)'>
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m4 20 4.2-1 10.7-10.7-3.2-3.2L5 15.8 4 20Z"/><path d="m14.5 6.5 3 3"/></svg>
                                                    Editar formato
                                                </button>
                                                <button type="button" class="desktop-menu__item" data-duplicate-format='@json($formato)'>
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/></svg>
                                                    Duplicar
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div class="desktop-list-foot">
                <div>{{ $formatos->count() }} {{ $formatos->count() === 1 ? 'formato' : 'formatos' }}</div>
            </div>
        </div>

        {{-- ── Plantillas ───────────────────────────────────────── --}}
        <div class="desktop-etq-panel" data-panel="plantillas" hidden>
            <div class="desktop-etq-bar">
                <span class="desktop-etq-bar__copy">
                    <strong>Plantillas de contenido.</strong>
                    La información que aparecerá impresa en cada tipo de etiqueta.
                </span>
            </div>

            <div class="desktop-list-wrap">
                @if($plantillas->isEmpty())
                    <div class="desktop-etq-empty">
                        <span class="desktop-etq-empty__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h12v18H6z"/><path d="M9 8h6M9 12h6M9 16h4"/></svg>
                        </span>
                        <h3>Define qué lleva la etiqueta</h3>
                        <p>Selecciona nombre, código, precio, talla y demás campos que deben imprimirse.</p>
                        <button type="button" class="desktop-btn desktop-btn--primary" data-new-template>Crear primera plantilla</button>
                    </div>
                @else
                    <table class="desktop-list">
                        <thead>
                            <tr>
                                <th style="width:260px;">Plantilla</th>
                                <th>Campos incluidos</th>
                                <th style="width:120px;">Estatus</th>
                                <th style="width:56px; text-align:right;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($plantillas as $plantilla)
                                @php $camposPlantilla = is_array($plantilla->etp_campos) ? $plantilla->etp_campos : (json_decode((string) $plantilla->etp_campos, true) ?: []); @endphp
                                <tr>
                                    <td>
                                        <span class="desktop-list__name">{{ $plantilla->etp_nombre }}</span>
                                        <span class="desktop-list__meta">{{ $plantilla->etp_descripcion ?: 'Sin descripción' }}</span>
                                    </td>
                                    <td>
                                        <div class="desktop-etq-pills">
                                            @forelse($camposPlantilla as $campo)
                                                <span class="desktop-pill desktop-pill--brand">{{ ucfirst(str_replace('_', ' ', $campo)) }}</span>
                                            @empty
                                                <span class="desktop-list__meta">Sin campos</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td>
                                        <span class="desktop-status {{ $plantilla->etp_estatus === 'activo' ? 'desktop-status--active' : 'desktop-status--inactive' }}">
                                            {{ ucfirst($plantilla->etp_estatus) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="desktop-rowmenu">
                                            <button type="button" class="desktop-overflow" data-overflow aria-haspopup="true" aria-expanded="false" aria-label="Acciones de la plantilla">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                                            </button>
                                            <div class="desktop-menu">
                                                <button type="button" class="desktop-menu__item" data-edit-template='@json($plantilla)'>
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m4 20 4.2-1 10.7-10.7-3.2-3.2L5 15.8 4 20Z"/><path d="m14.5 6.5 3 3"/></svg>
                                                    Editar plantilla
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div class="desktop-list-foot">
                <div>{{ $plantillas->count() }} {{ $plantillas->count() === 1 ? 'plantilla' : 'plantillas' }}</div>
            </div>
        </div>

        {{-- ── Asignación por línea ─────────────────────────────── --}}
        <div class="desktop-etq-panel" data-panel="asignaciones" hidden>
            <div class="desktop-etq-bar">
                <span class="desktop-etq-bar__copy">
                    <strong>Asignación automática por línea.</strong>
                    Cada línea necesita un formato y una plantilla activos para poder imprimir.
                </span>
            </div>

            @unless($puedeAsignar)
                <div class="desktop-etq-alert">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2 21h20L12 3Z"/><path d="M12 9v5M12 18h.01"/></svg>
                    Antes de asignar líneas necesitas al menos un formato y una plantilla activos.
                </div>
            @endunless

            @if($lineasSinAsignar->isNotEmpty())
                <div class="desktop-etq-alert">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
                    {{ $lineasSinAsignar->count() }} {{ $lineasSinAsignar->count() === 1 ? 'línea todavía no podrá imprimir etiquetas' : 'líneas todavía no podrán imprimir etiquetas' }}.
                </div>
            @endif

            <div class="desktop-list-wrap">
                <table class="desktop-list">
                    <thead>
                        <tr>
                            <th>Línea</th>
                            <th>Formato físico</th>
                            <th>Plantilla</th>
                            <th style="width:130px;">Estatus</th>
                            <th style="width:56px; text-align:right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lineas as $linea)
                            @php $configuracion = $configuraciones->firstWhere('elc_lna_id', $linea->lna_id); @endphp
                            <tr>
                                <td>
                                    <span class="desktop-list__name">{{ $linea->lna_nombre }}</span>
                                    <span class="desktop-list__meta">{{ $linea->lna_clave }}</span>
                                </td>
                                <td>{{ $configuracion?->formato?->etf_nombre ?: '—' }}</td>
                                <td>{{ $configuracion?->plantilla?->etp_nombre ?: '—' }}</td>
                                <td>
                                    @if($configuracion)
                                        <span class="desktop-status {{ $configuracion->elc_estatus === 'activo' ? 'desktop-status--active' : 'desktop-status--inactive' }}">
                                            {{ ucfirst($configuracion->elc_estatus) }}
                                        </span>
                                    @else
                                        <span class="desktop-status desktop-status--inactive">Pendiente</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="desktop-rowmenu">
                                        <button type="button" class="desktop-overflow" data-overflow aria-haspopup="true" aria-expanded="false" aria-label="Acciones de la línea" @disabled(!$puedeAsignar)>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                                        </button>
                                        <div class="desktop-menu">
                                            <button
                                                type="button"
                                                class="desktop-menu__item"
                                                data-assign-line="{{ $linea->lna_id }}"
                                                data-format-id="{{ $configuracion?->elc_etf_id }}"
                                                data-template-id="{{ $configuracion?->elc_etp_id }}"
                                                data-assignment-status="{{ $configuracion?->elc_estatus ?: 'activo' }}"
                                            >
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m4 20 4.2-1 10.7-10.7-3.2-3.2L5 15.8 4 20Z"/><path d="m14.5 6.5 3 3"/></svg>
                                                {{ $configuracion ? 'Cambiar asignación' : 'Configurar línea' }}
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr data-empty>
                                <td colspan="5" class="desktop-list__empty">No hay líneas activas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="desktop-list-foot">
                <div>{{ $lineas->count() }} {{ $lineas->count() === 1 ? 'línea' : 'líneas' }} · {{ $lineasSinAsignar->count() }} sin asignar</div>
            </div>
        </div>

        {{-- ── Reglas por unidad ────────────────────────────────── --}}
        <div class="desktop-etq-panel" data-panel="unidades" hidden>
            <div class="desktop-etq-bar">
                <span class="desktop-etq-bar__copy">
                    <strong>Reglas de cantidad.</strong>
                    Cómo se convierte la cantidad recibida en número de etiquetas.
                </span>
            </div>

            @if($unidadesSinRegla->isNotEmpty())
                <div class="desktop-etq-alert">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
                    {{ $unidadesSinRegla->count() }} {{ $unidadesSinRegla->count() === 1 ? 'unidad necesita' : 'unidades necesitan' }} una regla explícita. El sistema no asume ningún comportamiento.
                </div>
            @endif

            <div class="desktop-list-wrap">
                <table class="desktop-list">
                    <thead>
                        <tr>
                            <th>Unidad de venta</th>
                            <th style="width:340px;">Comportamiento</th>
                            <th style="width:220px;">Ejemplo</th>
                            <th style="width:110px; text-align:right;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($unidades as $unidad)
                            @php $reglaUnidad = $reglas[$unidad->umd_id]->eur_regla ?? ''; @endphp
                            <tr>
                                <td>
                                    <span class="desktop-list__name">{{ $unidad->umd_nombre }}</span>
                                    <span class="desktop-list__meta">{{ $unidad->umd_codigo }}</span>
                                </td>
                                <td>
                                    <select class="desktop-toolbar__select desktop-etq-select" data-rule-select="{{ $unidad->umd_id }}">
                                        <option value="">Seleccionar regla…</option>
                                        <option value="por_unidad_recibida" @selected($reglaUnidad === 'por_unidad_recibida')>Una etiqueta por unidad recibida</option>
                                        <option value="por_detalle_recepcion" @selected($reglaUnidad === 'por_detalle_recepcion')>Una etiqueta por producto/detalle</option>
                                    </select>
                                </td>
                                <td class="desktop-list__meta" data-rule-example="{{ $unidad->umd_id }}">
                                    {{ $reglaUnidad === 'por_unidad_recibida' ? '10 recibidas → 10 etiquetas' : ($reglaUnidad === 'por_detalle_recepcion' ? '50 recibidos → 1 etiqueta' : 'Sin configurar') }}
                                </td>
                                <td style="text-align:right;">
                                    <button type="button" class="desktop-btn desktop-btn--default" data-save-rule="{{ $unidad->umd_id }}">Guardar</button>
                                </td>
                            </tr>
                        @empty
                            <tr data-empty>
                                <td colspan="4" class="desktop-list__empty">No hay unidades de medida activas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="desktop-list-foot">
                <div>{{ $unidades->count() }} {{ $unidades->count() === 1 ? 'unidad' : 'unidades' }} · {{ $unidadesSinRegla->count() }} sin regla</div>
            </div>
        </div>

        {{-- ── Historial ────────────────────────────────────────── --}}
        <div class="desktop-etq-panel" data-panel="historial" hidden>
            <div class="desktop-etq-bar">
                <span class="desktop-etq-bar__copy">
                    <strong>Historial de generación.</strong>
                    Archivos generados desde recepciones de mercancía.
                </span>
            </div>

            <div class="desktop-list-wrap">
                @if($historial->isEmpty())
                    <div class="desktop-etq-empty">
                        <span class="desktop-etq-empty__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l3 2"/></svg>
                        </span>
                        <h3>Aún no hay impresiones</h3>
                        <p>Cuando generes etiquetas desde una recepción, aquí aparecerán sus archivos y cantidades.</p>
                    </div>
                @else
                    <table class="desktop-list">
                        <thead>
                            <tr>
                                <th style="width:190px;">Recepción</th>
                                <th style="width:150px;">Modo</th>
                                <th style="width:110px; text-align:right;">Etiquetas</th>
                                <th style="width:130px;">Estatus</th>
                                <th>Generado</th>
                                <th style="width:56px; text-align:right;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($historial as $impresion)
                                <tr>
                                    <td><span class="desktop-list__name">{{ $impresion->rme_folio }}</span></td>
                                    <td><span class="desktop-pill desktop-pill--neutral">{{ $impresion->eim_modo === 'separado' ? 'Por formato' : 'PDF único' }}</span></td>
                                    <td class="desktop-etq-num">{{ number_format((int) $impresion->eim_total_etiquetas) }}</td>
                                    <td><span class="desktop-status desktop-status--active">{{ ucfirst($impresion->eim_estatus) }}</span></td>
                                    <td>{{ $impresion->eim_generado_at }}</td>
                                    <td>
                                        <div class="desktop-rowmenu">
                                            <button type="button" class="desktop-overflow" data-overflow aria-haspopup="true" aria-expanded="false" aria-label="Archivos de la impresión">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                                            </button>
                                            <div class="desktop-menu">
                                                @foreach($impresion->archivos as $indice => $archivo)
                                                    <a class="desktop-menu__item" target="_blank" rel="noopener" href="{{ route('desktop.operacion.etiquetas.archivos.ver', $archivo->eia_id) }}" title="{{ $archivo->eia_nombre }}">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/></svg>
                                                        Ver PDF{{ $impresion->archivos->count() > 1 ? ' '.($indice + 1) : '' }}
                                                    </a>
                                                @endforeach
                                                @if($impresion->archivos->count() > 1)
                                                    <div class="desktop-menu__divider"></div>
                                                    <a class="desktop-menu__item" href="{{ route('desktop.operacion.etiquetas.impresiones.zip', $impresion->eim_id) }}">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
                                                        Descargar ZIP
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div class="desktop-list-foot">
                <div>{{ $historial->count() }} {{ $historial->count() === 1 ? 'impresión' : 'impresiones' }}</div>
            </div>
        </div>
    </section>

    {{-- ── Modal: formato ───────────────────────────────────────── --}}
    <div class="desktop-modal" id="format-modal" aria-hidden="true">
        <div class="desktop-modal__dialog">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="format-modal-title">Nuevo formato</div>
                <button type="button" class="desktop-modal__close" data-close-modal aria-label="Cerrar">&times;</button>
            </div>
            <form id="format-form">
                <div class="desktop-modal__body">
                    <div class="desktop-etq-split">
                        <div>
                            <input type="hidden" name="etf_id">
                            <div class="desktop-form-grid">
                                <div class="desktop-field desktop-field--full">
                                    <label for="etf_nombre">Nombre del formato</label>
                                    <input type="text" id="etf_nombre" name="etf_nombre" maxlength="120" placeholder="Ej. Etiqueta ropa estándar" required>
                                </div>
                                <div class="desktop-field desktop-field--full">
                                    <label for="etf_descripcion">Descripción</label>
                                    <textarea id="etf_descripcion" name="etf_descripcion" rows="2" placeholder="Cuándo y para qué mercancía se utiliza"></textarea>
                                </div>
                                <div class="desktop-field">
                                    <label for="etf_ancho_mm">Ancho (mm)</label>
                                    <input type="number" id="etf_ancho_mm" name="etf_ancho_mm" min="10" max="500" step="0.1" value="50" required>
                                </div>
                                <div class="desktop-field">
                                    <label for="etf_alto_mm">Alto (mm)</label>
                                    <input type="number" id="etf_alto_mm" name="etf_alto_mm" min="10" max="500" step="0.1" value="30" required>
                                </div>
                                <div class="desktop-field">
                                    <label for="etf_orientacion">Orientación</label>
                                    <select id="etf_orientacion" name="etf_orientacion">
                                        <option value="auto">Automática</option>
                                        <option value="horizontal">Horizontal</option>
                                        <option value="vertical">Vertical</option>
                                    </select>
                                </div>
                                <div class="desktop-field">
                                    <label for="etf_tipo_salida">Tipo de salida</label>
                                    <select id="etf_tipo_salida" name="etf_tipo_salida">
                                        <option value="termica">Térmica / individual</option>
                                        <option value="hoja">Hoja con varias etiquetas</option>
                                    </select>
                                </div>

                                <div class="desktop-etq-section">Márgenes internos</div>
                                @foreach(['izq' => 'Izquierdo', 'der' => 'Derecho', 'sup' => 'Superior', 'inf' => 'Inferior'] as $clave => $etiqueta)
                                    <div class="desktop-field">
                                        <label for="etf_margen_{{ $clave }}_mm">{{ $etiqueta }} (mm)</label>
                                        <input type="number" id="etf_margen_{{ $clave }}_mm" name="etf_margen_{{ $clave }}_mm" min="0" max="50" step="0.1" value="2">
                                    </div>
                                @endforeach

                                <div class="desktop-etq-section" data-sheet-field hidden>Distribución en hoja</div>
                                <div class="desktop-field" data-sheet-field hidden>
                                    <label for="etf_columnas">Columnas</label>
                                    <input type="number" id="etf_columnas" name="etf_columnas" min="1" max="20" value="1">
                                </div>
                                <div class="desktop-field" data-sheet-field hidden>
                                    <label for="etf_filas">Filas</label>
                                    <input type="number" id="etf_filas" name="etf_filas" min="1" max="20" value="1">
                                </div>
                                <div class="desktop-field" data-sheet-field hidden>
                                    <label for="etf_separacion_h_mm">Separación horizontal (mm)</label>
                                    <input type="number" id="etf_separacion_h_mm" name="etf_separacion_h_mm" min="0" max="50" step="0.1" value="0">
                                </div>
                                <div class="desktop-field" data-sheet-field hidden>
                                    <label for="etf_separacion_v_mm">Separación vertical (mm)</label>
                                    <input type="number" id="etf_separacion_v_mm" name="etf_separacion_v_mm" min="0" max="50" step="0.1" value="0">
                                </div>

                                <div class="desktop-field desktop-field--full">
                                    <label for="etf_compatibilidad_impresora">Compatibilidad / impresora</label>
                                    <input type="text" id="etf_compatibilidad_impresora" name="etf_compatibilidad_impresora" placeholder="Ej. Zebra ZD220, cualquier impresora PDF">
                                </div>
                                <div class="desktop-field">
                                    <label for="etf_estatus">Estatus</label>
                                    <select id="etf_estatus" name="etf_estatus">
                                        <option value="activo">Activo</option>
                                        <option value="inactivo">Inactivo</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <aside class="desktop-etq-preview">
                            <div class="desktop-etq-preview__title">Vista previa proporcional</div>
                            <div class="desktop-etq-preview__stage">
                                <div class="desktop-etq-label" id="format-preview">
                                    <div class="desktop-etq-label__bars"></div>
                                    <div class="desktop-etq-label__meta">SKU-001-AZUL-CH</div>
                                    <div class="desktop-etq-label__name">Playera deportiva / Azul / CH</div>
                                    <div class="desktop-etq-label__price">$299.00</div>
                                </div>
                            </div>
                            <span class="desktop-etq-preview__size" id="format-preview-size">50 × 30 mm</span>
                        </aside>
                    </div>
                </div>
                <div class="desktop-modal__foot">
                    <div class="desktop-modal__foot-group">
                        <button type="button" class="desktop-btn desktop-btn--default" data-close-modal>Cancelar</button>
                        <button type="submit" class="desktop-btn desktop-btn--primary">Guardar formato</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Modal: plantilla ─────────────────────────────────────── --}}
    <div class="desktop-modal" id="template-modal" aria-hidden="true">
        <div class="desktop-modal__dialog" style="max-width:720px;">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="template-modal-title">Nueva plantilla</div>
                <button type="button" class="desktop-modal__close" data-close-modal aria-label="Cerrar">&times;</button>
            </div>
            <form id="template-form">
                <div class="desktop-modal__body">
                    <input type="hidden" name="etp_id">
                    <div class="desktop-form-grid">
                        <div class="desktop-field">
                            <label for="etp_nombre">Nombre</label>
                            <input type="text" id="etp_nombre" name="etp_nombre" maxlength="120" placeholder="Ej. Contenido ropa" required>
                        </div>
                        <div class="desktop-field">
                            <label for="etp_estatus">Estatus</label>
                            <select id="etp_estatus" name="etp_estatus">
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="desktop-field desktop-field--full">
                            <label for="etp_descripcion">Descripción</label>
                            <textarea id="etp_descripcion" name="etp_descripcion" rows="2" placeholder="Contenido para etiquetas de ropa con talla y color"></textarea>
                        </div>

                        <div class="desktop-etq-section">Campos que aparecerán</div>
                        <div class="desktop-field desktop-field--full">
                            <div class="desktop-etq-checks">
                                @foreach(['nombre_producto' => 'Producto', 'sku' => 'SKU', 'codigo_barras' => 'Código de barras', 'precio' => 'Precio', 'linea' => 'Línea', 'talla' => 'Talla', 'color' => 'Color', 'unidad' => 'Unidad', 'cantidad' => 'Cantidad/metraje', 'sucursal' => 'Sucursal', 'fecha_recepcion' => 'Fecha recepción', 'folio_recepcion' => 'Folio entrada'] as $clave => $etiqueta)
                                    <label class="desktop-check">
                                        <input type="checkbox" name="etp_campos[]" value="{{ $clave }}">
                                        <span class="desktop-check__box">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                        </span>
                                        <span class="desktop-check__label">{{ $etiqueta }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="desktop-modal__foot">
                    <div class="desktop-modal__foot-group">
                        <button type="button" class="desktop-btn desktop-btn--default" data-close-modal>Cancelar</button>
                        <button type="submit" class="desktop-btn desktop-btn--primary">Guardar plantilla</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Modal: asignación ────────────────────────────────────── --}}
    <div class="desktop-modal" id="assignment-modal" aria-hidden="true">
        <div class="desktop-modal__dialog" style="max-width:560px;">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title">Asignar línea</div>
                <button type="button" class="desktop-modal__close" data-close-modal aria-label="Cerrar">&times;</button>
            </div>
            <form id="assignment-form">
                <div class="desktop-modal__body">
                    <p class="desktop-etq-hint">Si la línea ya tenía una asignación, se reemplaza por esta.</p>
                    <div class="desktop-form-grid">
                        <div class="desktop-field desktop-field--full">
                            <label for="elc_lna_id">Línea de producto</label>
                            <select id="elc_lna_id" name="elc_lna_id" required>
                                @foreach($lineas as $linea)
                                    <option value="{{ $linea->lna_id }}">{{ $linea->lna_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="desktop-field desktop-field--full">
                            <label for="elc_etf_id">Formato físico</label>
                            <select id="elc_etf_id" name="elc_etf_id" required>
                                @foreach($formatosActivos as $formato)
                                    <option value="{{ $formato->etf_id }}">{{ $formato->etf_nombre }} — {{ number_format((float) $formato->etf_ancho_mm, 1) }} × {{ number_format((float) $formato->etf_alto_mm, 1) }} mm</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="desktop-field desktop-field--full">
                            <label for="elc_etp_id">Plantilla de contenido</label>
                            <select id="elc_etp_id" name="elc_etp_id" required>
                                @foreach($plantillasActivas as $plantilla)
                                    <option value="{{ $plantilla->etp_id }}">{{ $plantilla->etp_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="desktop-field desktop-field--full">
                            <label for="elc_estatus">Estatus de la asignación</label>
                            <select id="elc_estatus" name="elc_estatus">
                                <option value="activo">Activa</option>
                                <option value="inactivo">Inactiva</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="desktop-modal__foot">
                    <div class="desktop-modal__foot-group">
                        <button type="button" class="desktop-btn desktop-btn--default" data-close-modal>Cancelar</button>
                        <button type="submit" class="desktop-btn desktop-btn--primary">Guardar asignación</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('desktop-scripts')
    <script>
        (function () {
            const rutas = {
                formatoStore: @json(route('desktop.operacion.etiquetas.formatos.store')),
                formatoBase: @json(url('/desktop/operacion/etiquetas/formatos')),
                plantillaStore: @json(route('desktop.operacion.etiquetas.plantillas.store')),
                plantillaBase: @json(url('/desktop/operacion/etiquetas/plantillas')),
                asignacionStore: @json(route('desktop.operacion.etiquetas.asignaciones.store')),
                reglaStore: @json(route('desktop.operacion.etiquetas.reglas.store')),
            };

            const $buscar = $('#etq-buscar');

            function errores(xhr) {
                const bag = xhr.responseJSON?.errors || {};
                return Object.values(bag).flat().join(' ') || xhr.responseJSON?.message || 'No fue posible completar la operación.';
            }

            function request(url, method, data) {
                return $.ajax({ url: url, method: method, data: data, headers: { Accept: 'application/json' } });
            }

            function guardado(mensaje) {
                DesktopUI.toast(mensaje, 'success');
                window.setTimeout(function () { window.location.reload(); }, 700);
            }

            /* ── Navegación por secciones ─────────────────────────── */
            function activarTab(tab) {
                $('[data-tab]').each(function () {
                    const activo = $(this).data('tab') === tab;
                    $(this).toggleClass('desktop-btn--active', activo);
                    if (activo) $(this).attr('aria-current', 'page'); else $(this).removeAttr('aria-current');
                });
                $('[data-panel]').each(function () { this.hidden = $(this).data('panel') !== tab; });
                $('[data-tab-only]').each(function () { this.hidden = $(this).data('tab-only') !== tab; });
                $buscar.val('');
                filtrar('');
            }

            function filtrar(termino) {
                const texto = String(termino || '').trim().toLowerCase();
                const $panel = $('[data-panel]').filter(function () { return !this.hidden; });
                const $tbody = $panel.find('tbody');
                let visibles = 0;

                $tbody.find('tr').each(function () {
                    if (this.hasAttribute('data-empty') || this.hasAttribute('data-filtro-vacio')) return;
                    const coincide = !texto || $(this).text().toLowerCase().indexOf(texto) !== -1;
                    this.hidden = !coincide;
                    if (coincide) visibles += 1;
                });

                $tbody.find('tr[data-filtro-vacio]').remove();
                if (texto && visibles === 0 && $tbody.find('tr[data-empty]').length === 0 && $tbody.length) {
                    const columnas = $panel.find('thead th').length;
                    $tbody.append('<tr data-filtro-vacio><td colspan="' + columnas + '" class="desktop-list__empty">Sin resultados para la búsqueda.</td></tr>');
                }
            }

            $('[data-tab]').on('click', function () { activarTab($(this).data('tab')); });
            $buscar.on('input', function () { filtrar(this.value); });
            $('#btn-recargar-etiquetas').on('click', function () { window.location.reload(); });

            /* ── Modales ──────────────────────────────────────────── */
            function abrirModal(selector) {
                $(selector).addClass('is-open').attr('aria-hidden', 'false');
                $(selector).find('input, select, textarea').filter(':visible').first().trigger('focus');
            }

            function cerrarModales() {
                $('.desktop-modal').removeClass('is-open').attr('aria-hidden', 'true');
            }

            $('[data-close-modal]').on('click', cerrarModales);
            $('.desktop-modal').on('click', function (evento) { if (evento.target === this) cerrarModales(); });
            $(document).on('keydown', function (evento) { if (evento.key === 'Escape') cerrarModales(); });

            /* ── Formatos ─────────────────────────────────────────── */
            function cargarFormato(datos) {
                datos = datos || {};
                const form = document.getElementById('format-form');
                form.reset();
                form.etf_id.value = datos.etf_id || '';
                form.etf_nombre.value = datos.etf_nombre || '';
                form.etf_descripcion.value = datos.etf_descripcion || '';
                form.etf_ancho_mm.value = datos.etf_ancho_mm || 50;
                form.etf_alto_mm.value = datos.etf_alto_mm || 30;
                form.etf_orientacion.value = datos.etf_orientacion || 'auto';
                form.etf_tipo_salida.value = datos.etf_tipo_salida || 'termica';
                ['izq', 'der', 'sup', 'inf'].forEach(function (lado) {
                    form['etf_margen_' + lado + '_mm'].value = datos['etf_margen_' + lado + '_mm'] ?? 2;
                });
                form.etf_columnas.value = datos.etf_columnas || 1;
                form.etf_filas.value = datos.etf_filas || 1;
                form.etf_separacion_h_mm.value = datos.etf_separacion_h_mm || 0;
                form.etf_separacion_v_mm.value = datos.etf_separacion_v_mm || 0;
                form.etf_compatibilidad_impresora.value = datos.etf_compatibilidad_impresora || '';
                form.etf_estatus.value = datos.etf_estatus || 'activo';
                $('#format-modal-title').text(datos.etf_id ? 'Editar formato' : 'Nuevo formato');
                sincronizarFormato();
            }

            function sincronizarFormato() {
                const form = document.getElementById('format-form');
                if (!form) return;
                const enHoja = form.etf_tipo_salida.value === 'hoja';
                $('[data-sheet-field]').prop('hidden', !enHoja);

                let ancho = Math.max(10, Number(form.etf_ancho_mm.value) || 50);
                let alto = Math.max(10, Number(form.etf_alto_mm.value) || 30);
                if (form.etf_orientacion.value === 'horizontal' && alto > ancho) { const t = ancho; ancho = alto; alto = t; }
                if (form.etf_orientacion.value === 'vertical' && ancho > alto) { const t = ancho; ancho = alto; alto = t; }

                const escala = Math.min(210 / ancho, 150 / alto);
                $('#format-preview').css({ width: (ancho * escala) + 'px', height: (alto * escala) + 'px' });
                $('#format-preview-size').text(ancho + ' × ' + alto + ' mm');
            }

            $('[data-new-format]').on('click', function () { cargarFormato(); abrirModal('#format-modal'); });
            $('[data-edit-format]').on('click', function () { cargarFormato($(this).data('edit-format')); abrirModal('#format-modal'); });
            $('[data-duplicate-format]').on('click', function () {
                const datos = Object.assign({}, $(this).data('duplicate-format'));
                datos.etf_id = null;
                datos.etf_nombre = (datos.etf_nombre || '') + ' (copia)';
                cargarFormato(datos);
                abrirModal('#format-modal');
            });

            $('#format-form').on('input change', sincronizarFormato).on('submit', function (evento) {
                evento.preventDefault();
                const id = this.etf_id.value;
                request(id ? rutas.formatoBase + '/' + id : rutas.formatoStore, id ? 'PUT' : 'POST', $(this).serialize())
                    .done(function (respuesta) { guardado(respuesta.message || 'Formato guardado.'); })
                    .fail(function (xhr) { DesktopUI.toast(errores(xhr), 'error'); });
            });

            /* ── Plantillas ───────────────────────────────────────── */
            function cargarPlantilla(datos) {
                datos = datos || {};
                const form = document.getElementById('template-form');
                form.reset();
                form.etp_id.value = datos.etp_id || '';
                form.etp_nombre.value = datos.etp_nombre || '';
                form.etp_descripcion.value = datos.etp_descripcion || '';
                form.etp_estatus.value = datos.etp_estatus || 'activo';

                const seleccionados = datos.etp_campos || ['nombre_producto', 'codigo_barras', 'precio'];
                $(form).find('[name="etp_campos[]"]').each(function () {
                    this.checked = seleccionados.indexOf(this.value) !== -1;
                });
                $('#template-modal-title').text(datos.etp_id ? 'Editar plantilla' : 'Nueva plantilla');
            }

            $('[data-new-template]').on('click', function () { cargarPlantilla(); abrirModal('#template-modal'); });
            $('[data-edit-template]').on('click', function () { cargarPlantilla($(this).data('edit-template')); abrirModal('#template-modal'); });

            $('#template-form').on('submit', function (evento) {
                evento.preventDefault();
                if ($(this).find('[name="etp_campos[]"]:checked').length === 0) {
                    DesktopUI.toast('Selecciona al menos un campo para la plantilla.', 'error');
                    return;
                }
                const id = this.etp_id.value;
                request(id ? rutas.plantillaBase + '/' + id : rutas.plantillaStore, id ? 'PUT' : 'POST', $(this).serialize())
                    .done(function (respuesta) { guardado(respuesta.message || 'Plantilla guardada.'); })
                    .fail(function (xhr) { DesktopUI.toast(errores(xhr), 'error'); });
            });

            /* ── Asignación por línea ─────────────────────────────── */
            function abrirAsignacion(datos) {
                datos = datos || {};
                const form = document.getElementById('assignment-form');
                form.reset();
                if (datos.lineId) form.elc_lna_id.value = String(datos.lineId);
                if (datos.formatId) form.elc_etf_id.value = String(datos.formatId);
                if (datos.templateId) form.elc_etp_id.value = String(datos.templateId);
                form.elc_estatus.value = datos.status || 'activo';
                abrirModal('#assignment-modal');
            }

            $('[data-new-assignment]').on('click', function () { abrirAsignacion(); });
            $('[data-assign-line]').on('click', function () {
                abrirAsignacion({
                    lineId: this.dataset.assignLine,
                    formatId: this.dataset.formatId,
                    templateId: this.dataset.templateId,
                    status: this.dataset.assignmentStatus,
                });
            });

            $('#assignment-form').on('submit', function (evento) {
                evento.preventDefault();
                request(rutas.asignacionStore, 'POST', $(this).serialize())
                    .done(function (respuesta) { guardado(respuesta.message || 'Asignación guardada.'); })
                    .fail(function (xhr) { DesktopUI.toast(errores(xhr), 'error'); });
            });

            /* ── Reglas por unidad ────────────────────────────────── */
            $('[data-rule-select]').on('change', function () {
                const texto = this.value === 'por_unidad_recibida'
                    ? '10 recibidas → 10 etiquetas'
                    : (this.value === 'por_detalle_recepcion' ? '50 recibidos → 1 etiqueta' : 'Sin configurar');
                $('[data-rule-example="' + this.dataset.ruleSelect + '"]').text(texto);
            });

            $('[data-save-rule]').on('click', function () {
                const unidad = this.dataset.saveRule;
                const regla = $('[data-rule-select="' + unidad + '"]').val();
                if (!regla) {
                    DesktopUI.toast('Selecciona una regla antes de guardar.', 'error');
                    return;
                }
                const $boton = $(this);
                $boton.prop('disabled', true);
                request(rutas.reglaStore, 'POST', { eur_umd_id: unidad, eur_regla: regla })
                    .done(function (respuesta) {
                        $boton.prop('disabled', false);
                        DesktopUI.toast(respuesta.message || 'Regla guardada.', 'success');
                    })
                    .fail(function (xhr) {
                        $boton.prop('disabled', false);
                        DesktopUI.toast(errores(xhr), 'error');
                    });
            });

            activarTab('formatos');
        })();
    </script>
@endpush
