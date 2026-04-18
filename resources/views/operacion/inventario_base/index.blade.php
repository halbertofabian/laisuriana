@extends('layouts.app')

@section('title', 'Inventario Base')

@push('vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/select2/select2.css') }}" />
    <style>
        /* ── Datatables toolbar ─────────────────────────────────── */
        .datatable-toolbar .dataTables_filter label,
        .datatable-toolbar .dataTables_length label { font-size:.82rem;font-weight:500;color:var(--ls-text-muted); }
        .datatable-toolbar .dataTables_filter input,
        .datatable-toolbar .dataTables_length select { min-height:2.1rem;border-radius:var(--ls-radius);border:1px solid var(--ls-border);font-size:.84rem; }
        .table-mini { font-size:.86rem; }

        /* ── Stock states ───────────────────────────────────────── */
        .stock-ok { color:var(--ls-success); font-weight:600; }
        .stock-low { color:var(--ls-danger); font-weight:700; }

        /* ── Masive capture grid ────────────────────────────────── */
        .multi-td-ok { background:#e9f8ef; }
        .multi-td-na { background:#f4f5f7; }
        .multi-cell-ok { background:#e9f8ef !important;border-color:#8dd8aa !important; }
        .multi-cell-na { background:#eef1f5 !important;color:#8a93a2 !important;border-color:#d7deea !important; cursor:not-allowed; }
        .multi-grid-wrap {
            max-height: 62vh;
            overflow: auto;
            border: 1px solid var(--ls-border);
            border-radius: var(--ls-radius);
            background: #fff;
        }
        .multi-grid-wrap.multi-density-compact .multi-grid-table th,
        .multi-grid-wrap.multi-density-compact .multi-grid-table td {
            padding: .28rem .42rem;
            font-size: .78rem;
        }
        .multi-grid-wrap.multi-density-compact .multi-cell-ok,
        .multi-grid-wrap.multi-density-compact .multi-cell-na {
            min-height: 1.95rem;
            padding: .18rem .35rem;
            font-size: .78rem;
        }
        .multi-grid-wrap.multi-density-comfortable .multi-grid-table th,
        .multi-grid-wrap.multi-density-comfortable .multi-grid-table td {
            padding: .52rem .58rem;
            font-size: .9rem;
        }
        .multi-grid-wrap.multi-density-comfortable .multi-cell-ok,
        .multi-grid-wrap.multi-density-comfortable .multi-cell-na {
            min-height: 2.35rem;
            padding: .34rem .48rem;
            font-size: .88rem;
        }
        .multi-grid-table thead tr:first-child th {
            position: sticky;
            top: 0;
            z-index: 3;
            box-shadow: inset 0 -1px 0 var(--ls-border);
        }
        .multi-grid-table thead tr:nth-child(2) th {
            position: sticky;
            top: 38px;
            z-index: 2;
            box-shadow: inset 0 -1px 0 var(--ls-border);
        }
        .multi-grid-table tbody tr:nth-child(even) td,
        .multi-grid-table tbody tr:nth-child(even) th[scope="row"] {
            background: #fafbfd;
        }
        .multi-grid-table th[scope="rowgroup"] {
            background: #f5f7fb;
            border-right: 2px solid #d8deea;
        }
        .multi-col-head small {
            display: block;
            font-size: .68rem;
            color: #5f6b7f;
            font-weight: 600;
            margin-top: .1rem;
        }
        .density-switch .btn {
            min-width: 100px;
            font-size: .78rem;
            font-weight: 600;
        }
        .density-state-chip {
            display: inline-flex;
            align-items: center;
            border: 1px solid var(--ls-border);
            border-radius: 999px;
            padding: .14rem .55rem;
            font-size: .74rem;
            font-weight: 700;
            color: var(--ls-text-secondary);
            background: var(--ls-surface);
            letter-spacing: .01em;
        }
        .multi-focus-cell { box-shadow: inset 0 0 0 2px #0d6efd; background: #e9f1ff !important; }
        .multi-focus-col { background: #eef4ff !important; }
        .multi-cell-ok:focus-visible {
            outline: 3px solid #0d6efd;
            outline-offset: 1px;
            box-shadow: 0 0 0 .2rem rgba(13,110,253,.25);
        }
        .multi-grid-table th { font-weight:700; }
        .multi-legend { font-size:.82rem; }
        .multi-dot { width:12px;height:12px;border-radius:999px;display:inline-block; }
        .multi-dot-ok { background:var(--ls-success); }
        .multi-dot-na { background:#8a93a2; }
        .attr-chip {
            display:inline-flex;align-items:center;gap:.35rem;
            border:1px solid var(--ls-border);border-radius:999px;
            padding:.2rem .55rem;font-size:.78rem;background:#fff;
        }
        .attr-chip input { margin-top:0; }
        .multi-col-na { opacity:.55; }
        .multi-col-ok { background:rgba(34,197,94,.09); }

        @media (max-width: 992px) {
            .multi-grid-wrap { max-height: 54vh; }
            .multi-grid-table { font-size: .8rem; }
            .multi-grid-table thead tr:nth-child(2) th { top: 34px; }
            .multi-cell-ok, .multi-cell-na { min-width: 78px; }
            .density-switch .btn { min-width: 86px; }
        }

        /* ── Filter bar ─────────────────────────────────────────── */
        .inv-filter-bar {
            background: var(--ls-surface-2);
            border: 1px solid var(--ls-border);
            border-radius: var(--ls-radius);
            padding: .9rem 1rem;
            margin-bottom: 1rem;
        }
        .inv-filter-bar .form-label { font-size:.78rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:var(--ls-text-muted);margin-bottom:.3rem; }

        /* ── Stats row ──────────────────────────────────────────── */
        .inv-stat-card {
            background: var(--ls-surface);
            border: 1px solid var(--ls-border);
            border-radius: var(--ls-radius);
            padding: .65rem .9rem;
            display: flex;
            align-items: center;
            gap: .65rem;
            box-shadow: var(--ls-shadow-sm);
            min-width: 0;
        }
        .inv-stat-card__icon {
            width: 2rem;height: 2rem;border-radius: var(--ls-radius-sm);
            display:inline-flex;align-items:center;justify-content:center;
            font-size:1rem;flex-shrink:0;
        }
        .inv-stat-card__icon--accent { background:var(--ls-accent-light);color:var(--ls-accent); }
        .inv-stat-card__icon--success { background:var(--ls-success-bg);color:var(--ls-success); }
        .inv-stat-card__icon--danger  { background:var(--ls-danger-bg);color:var(--ls-danger); }
        .inv-stat-card__icon--warning { background:var(--ls-warning-bg);color:var(--ls-warning); }
        .inv-stat-card__val  { font-size:1.1rem;font-weight:700;line-height:1.1;color:var(--ls-text-primary); }
        .inv-stat-card__lbl  { font-size:.72rem;color:var(--ls-text-muted);line-height:1.2; }

        /* ── Quantity pill (Kardex) ──────────────────────────────── */
        .inv-qty-in  { display:inline-flex;align-items:center;gap:.25rem;font-weight:700;color:var(--ls-success);font-size:.82rem; }
        .inv-qty-out { display:inline-flex;align-items:center;gap:.25rem;font-weight:700;color:var(--ls-danger);font-size:.82rem; }
        .inv-qty-in  i,.inv-qty-out i { font-size:.85rem; }

        /* ── SKU chip ────────────────────────────────────────────── */
        .inv-sku-chip {
            display:inline-block;background:var(--ls-surface-3);border:1px solid var(--ls-border);
            border-radius:var(--ls-radius-sm);padding:.05rem .35rem;font-family:monospace;
            font-size:.78rem;color:var(--ls-text-secondary);line-height:1.5;white-space:nowrap;
        }

        /* ── Step wizard ─────────────────────────────────────────── */
        .inv-wizard-steps {
            display:flex;align-items:center;gap:0;margin-bottom:1.1rem;
            border-bottom:1px solid var(--ls-border);padding-bottom:.8rem;
        }
        .inv-wizard-step {
            display:inline-flex;align-items:center;gap:.45rem;
            font-size:.78rem;font-weight:600;color:var(--ls-text-muted);
        }
        .inv-wizard-step.active { color:var(--ls-accent); }
        .inv-wizard-step.done   { color:var(--ls-success); }
        .inv-wizard-step__num {
            width:1.4rem;height:1.4rem;border-radius:999px;border:2px solid currentColor;
            display:inline-flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;
        }
        .inv-wizard-sep { width:2rem;height:2px;background:var(--ls-border);margin:0 .35rem;border-radius:2px;flex-shrink:0; }
        .inv-wizard-step.done .inv-wizard-step__num { background:var(--ls-success);border-color:var(--ls-success);color:#fff; }
        .inv-wizard-step.active .inv-wizard-step__num { background:var(--ls-accent);border-color:var(--ls-accent);color:#fff; }

        /* ── Availability card (Salidas) ─────────────────────────── */
        #inv-availability-card {
            border:1px solid var(--ls-border);border-radius:var(--ls-radius);
            padding:.75rem 1rem;background:var(--ls-surface-2);
            display:flex;align-items:center;gap:.75rem;
            transition:border-color .2s,background .2s;
        }
        #inv-availability-card.av-ok    { border-color:var(--ls-success);background:var(--ls-success-bg); }
        #inv-availability-card.av-low   { border-color:var(--ls-danger);background:var(--ls-danger-bg); }
        #inv-availability-card.av-zero  { border-color:var(--ls-warning);background:var(--ls-warning-bg); }
        #inv-availability-card .av-icon { font-size:1.5rem;flex-shrink:0; }
        #inv-availability-card .av-val  { font-size:1.25rem;font-weight:700;line-height:1;color:var(--ls-text-primary); }
        #inv-availability-card .av-lbl  { font-size:.75rem;color:var(--ls-text-muted); }

        /* ── Section divider label ───────────────────────────────── */
        .inv-section-label {
            font-size:.7rem;font-weight:700;letter-spacing:.07em;text-transform:uppercase;
            color:var(--ls-text-muted);margin-bottom:.6rem;padding-bottom:.4rem;
            border-bottom:1px solid var(--ls-border);
        }

        /* ── Tab icons ───────────────────────────────────────────── */
        .app-tabs-shell__tabs .nav-link i { font-size:.95rem;margin-right:.3rem;vertical-align:-.1rem; }

        /* ── Empty state ─────────────────────────────────────────── */
        .inv-empty-state {
            text-align:center;padding:2.5rem 1rem;color:var(--ls-text-muted);
        }
        .inv-empty-state i { font-size:2.5rem;opacity:.35;display:block;margin-bottom:.6rem; }
        .inv-empty-state p { font-size:.84rem;margin:0; }
    </style>
@endpush

@section('content')
@php($soloEntradas = (bool) ($soloEntradas ?? false))
<x-section-header
    eyebrow="Operación"
    icon="tabler-packages"
    title="Inventario Base"
    subtitle="Control por sucursal y almacén con trazabilidad completa en kardex."
/>

<div class="card app-tabs-shell mb-4">
    <div class="app-tabs-shell__header {{ $soloEntradas ? 'd-none' : '' }}">
        <ul class="nav nav-tabs app-tabs-shell__tabs" role="tablist">
            <li class="nav-item"><button class="nav-link {{ $soloEntradas ? '' : 'active' }}" data-bs-toggle="tab" data-bs-target="#tab-existencias" type="button"><i class="ti tabler-layout-grid"></i>Existencias</button></li>
            <li class="nav-item"><button class="nav-link {{ $soloEntradas ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-inicial" type="button"><i class="ti tabler-package-import"></i>Entradas</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-recibir-mercancia" type="button"><i class="ti tabler-truck-delivery"></i>Recibir mercancía</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-salidas" type="button"><i class="ti tabler-package-export"></i>Salidas</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-correcciones" type="button"><i class="ti tabler-pencil"></i>Corrección/Cancelación</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-kardex" type="button"><i class="ti tabler-list-details"></i>Kardex</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-minimos" type="button"><i class="ti tabler-alert-triangle"></i>Bajo mínimo</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-reportes-entradas" type="button"><i class="ti tabler-file-type-pdf"></i>Reportes PDF</button></li>
        </ul>
    </div>

    <div class="app-tabs-shell__body">
        <div class="tab-content">
            <div class="tab-pane fade {{ $soloEntradas ? '' : 'show active' }}" id="tab-existencias" role="tabpanel">

                {{-- Stats summary --}}
                <div class="row g-2 mb-3" id="inv-stats-existencias">
                    <div class="col-6 col-md-3">
                        <div class="inv-stat-card">
                            <div class="inv-stat-card__icon inv-stat-card__icon--accent"><i class="ti tabler-packages"></i></div>
                            <div><div class="inv-stat-card__val" id="stat-total-skus">—</div><div class="inv-stat-card__lbl">Registros</div></div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="inv-stat-card">
                            <div class="inv-stat-card__icon inv-stat-card__icon--success"><i class="ti tabler-circle-check"></i></div>
                            <div><div class="inv-stat-card__val" id="stat-con-stock">—</div><div class="inv-stat-card__lbl">Con stock</div></div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="inv-stat-card">
                            <div class="inv-stat-card__icon inv-stat-card__icon--danger"><i class="ti tabler-alert-triangle"></i></div>
                            <div><div class="inv-stat-card__val" id="stat-bajo-minimo">—</div><div class="inv-stat-card__lbl">Bajo mínimo</div></div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="inv-stat-card">
                            <div class="inv-stat-card__icon inv-stat-card__icon--warning"><i class="ti tabler-box-off"></i></div>
                            <div><div class="inv-stat-card__val" id="stat-sin-stock">—</div><div class="inv-stat-card__lbl">Sin stock</div></div>
                        </div>
                    </div>
                </div>

                {{-- Filters --}}
                <div class="inv-filter-bar">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Sucursal</label>
                            <select id="flt-exa-scl" class="form-select form-select-sm">
                                <option value="">Todas</option>
                                @foreach($opciones['sucursales'] as $sucursal)
                                    <option value="{{ $sucursal->scl_id }}">{{ $sucursal->scl_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Almacén</label>
                            <select id="flt-exa-alm" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                @foreach($opciones['almacenes'] as $almacen)
                                    <option value="{{ $almacen->alm_id }}" data-scl="{{ $almacen->alm_scl_id }}">{{ $almacen->sucursal?->scl_nombre }} - {{ $almacen->alm_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Buscar</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="ti tabler-search"></i></span>
                                <input id="flt-exa-buscar" class="form-control" placeholder="Código, SKU o producto" />
                            </div>
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button class="btn btn-primary btn-sm w-100" id="btn-filtrar-existencias"><i class="ti tabler-filter me-1"></i>Aplicar</button>
                            <button class="btn btn-outline-secondary btn-sm" id="btn-limpiar-existencias" title="Limpiar filtros"><i class="ti tabler-x"></i></button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="tbl-existencias" class="table table-mini">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>SKU / Variante</th>
                                <th>Producto</th>
                                <th>Sucursal</th>
                                <th>Almacén</th>
                                <th class="text-end">Existencia</th>
                                <th class="text-end">Mínimo</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade {{ $soloEntradas ? 'show active' : '' }}" id="tab-inicial" role="tabpanel">

                {{-- Wizard step indicator --}}
                <div class="inv-wizard-steps">
                    <div class="inv-wizard-step active" id="wiz-step1-indicator">
                        <span class="inv-wizard-step__num">1</span>
                        <span>Seleccionar productos</span>
                    </div>
                    <div class="inv-wizard-sep"></div>
                    <div class="inv-wizard-step" id="wiz-step2-indicator">
                        <span class="inv-wizard-step__num">2</span>
                        <span>Capturar existencias</span>
                    </div>
                    <div class="ms-auto">
                        @if($soloEntradas)
                            <a href="{{ route('operacion.inventario_base.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti tabler-arrow-left me-1"></i>Vista completa</a>
                        @else
                            <a href="{{ route('operacion.inventario_base.entradas_wizard') }}" class="btn btn-outline-primary btn-sm"><i class="ti tabler-external-link me-1"></i>Pantalla completa</a>
                        @endif
                    </div>
                </div>

                <div id="shell-entrada-step1">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary-subtle text-primary fw-semibold" id="lbl-productos-seleccionados" style="font-size:.8rem;padding:.3rem .65rem;">0 seleccionados</span>
                        </div>
                        <div class="d-flex gap-2">
                            <button id="btn-seleccionar-visibles" class="btn btn-outline-secondary btn-sm" type="button"><i class="ti tabler-select me-1"></i>Seleccionar visibles</button>
                            <button id="btn-limpiar-seleccion-productos" class="btn btn-outline-secondary btn-sm" type="button"><i class="ti tabler-x me-1"></i>Limpiar</button>
                            <button id="btn-capturar-seleccionados" class="btn btn-primary btn-sm" type="button" disabled><i class="ti tabler-package-import me-1"></i>Capturar seleccionados</button>
                        </div>
                    </div>
                    <div class="inv-filter-bar">
                    <div class="row g-2 mb-0">
                        <div class="col-md-2">
                            <label class="form-label">Marca</label>
                            <select id="flt-prd-mrc" class="form-select form-select-sm">
                                <option value="">Todas</option>
                                @foreach($opciones['marcas'] as $marca)
                                    <option value="{{ $marca->mrc_id }}">{{ $marca->mrc_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Modelo</label>
                            <select id="flt-prd-mdl" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                @foreach($opciones['modelos'] as $modelo)
                                    <option value="{{ $modelo->mdl_id }}">{{ $modelo->mdl_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Línea</label>
                            <select id="flt-prd-lna" class="form-select form-select-sm">
                                <option value="">Todas</option>
                                @foreach($opciones['lineas'] as $linea)
                                    <option value="{{ $linea->lna_id }}">{{ $linea->lna_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Categoría</label>
                            <select id="flt-prd-ctg" class="form-select form-select-sm">
                                <option value="">Todas</option>
                                @foreach($opciones['categorias'] as $categoria)
                                    <option value="{{ $categoria->ctg_id }}" data-lna="{{ $categoria->ctg_lna_id }}">{{ $categoria->ctg_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Buscar</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="ti tabler-search"></i></span>
                                <input id="flt-prd-buscar" class="form-control" placeholder="Código o producto">
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end gap-2">
                            <button id="btn-filtrar-productos-base" class="btn btn-primary btn-sm w-100"><i class="ti tabler-filter me-1"></i>Aplicar</button>
                            <button id="btn-limpiar-productos-base" class="btn btn-outline-secondary btn-sm" title="Limpiar"><i class="ti tabler-x"></i></button>
                        </div>
                    </div>
                    </div>{{-- /.inv-filter-bar --}}
                    <div class="table-responsive">
                        <table id="tbl-productos-base" class="table table-mini">
                            <thead>
                                <tr>
                                    <th style="width:42px;"><input type="checkbox" id="chk-productos-todos" /></th>
                                    <th>Código</th>
                                    <th>Producto base</th>
                                    <th>Tipo</th>
                                    <th>Marca</th>
                                    <th>Modelo</th>
                                    <th>Línea</th>
                                    <th>Categoría</th>
                                    <th>SKU activos</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

                <div id="shell-entrada-step2" class="d-none">
                    <div id="header-captura-unitaria" class="d-flex justify-content-between align-items-start mb-3 p-3 rounded" style="background:var(--ls-accent-light);border:1px solid var(--ls-accent-mid);">
                        <div>
                            <div class="text-muted" style="font-size:.72rem;font-weight:700;letter-spacing:.07em;text-transform:uppercase;">Producto seleccionado</div>
                            <div class="fw-bold" id="entrada-producto-seleccionado" style="font-size:1rem;color:var(--ls-text-primary);margin:.15rem 0 .1rem;">-</div>
                            <div class="small" id="entrada-producto-indice" style="color:var(--ls-accent);font-weight:600;">Producto 1 de 1</div>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button id="btn-producto-anterior" class="btn btn-outline-secondary btn-sm" type="button"><i class="ti tabler-chevron-left me-1"></i>Anterior</button>
                            <button id="btn-producto-siguiente" class="btn btn-outline-secondary btn-sm" type="button">Siguiente<i class="ti tabler-chevron-right ms-1"></i></button>
                            <button id="btn-cambiar-producto" class="btn btn-outline-primary btn-sm" type="button"><i class="ti tabler-arrow-back-up me-1"></i>Cambiar selección</button>
                        </div>
                    </div>

                    <div id="shell-captura-masiva" class="d-none">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div class="text-body-secondary small">Captura masiva</div>
                                <div class="fw-semibold" id="multi-resumen-productos">0 productos seleccionados</div>
                            </div>
                            <button id="btn-cambiar-producto-multi" class="btn btn-outline-secondary btn-sm" type="button">Cambiar selección</button>
                        </div>
                        <form id="form-inicial-multi" class="row g-3">
                            @csrf
                            <div class="col-md-3">
                                <label class="form-label">Tipo de entrada</label>
                                <select class="form-select" id="multi-tipo-entrada" required>
                                    <option value="inventario_inicial">Entrada normal (inventario inicial)</option>
                                    <option value="entrada_normal">Entrada normal</option>
                                    <option value="compra_remision">Compra con remisión</option>
                                    <option value="compra_factura">Compra con factura</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Sucursal</label>
                                <select class="form-select" id="multi-scl-id" required>
                                    <option value="">Selecciona</option>
                                    @foreach($opciones['sucursales'] as $sucursal)
                                        <option value="{{ $sucursal->scl_id }}">{{ $sucursal->scl_nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Almacén</label>
                                <select class="form-select" id="multi-alm-id" required><option value="">Selecciona</option></select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Fecha captura</label>
                                <input type="datetime-local" class="form-control" id="multi-fecha" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Proveedor</label>
                                <select class="form-select" id="multi-prv-id">
                                    <option value="">Sin proveedor</option>
                                    @foreach($opciones['proveedores'] as $proveedor)
                                        <option value="{{ $proveedor->prv_id }}">{{ $proveedor->prv_nombre_empresa }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Fecha emisión</label>
                                <input type="datetime-local" class="form-control" id="multi-fecha-emision">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" id="lbl-multi-referencia">Referencia (opcional)</label>
                                <input type="text" class="form-control" id="multi-referencia" maxlength="120">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="form-check pb-2">
                                    <input class="form-check-input" type="checkbox" id="multi-referencia-na">
                                    <label class="form-check-label" for="multi-referencia-na">Sin folio (N/A)</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Motivo</label>
                                <input type="text" class="form-control" id="multi-motivo" maxlength="500" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Observaciones</label>
                                <input type="text" class="form-control" id="multi-observaciones" maxlength="1500" placeholder="Defectos, faltantes o detalles importantes">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Descuento</label>
                                <div class="input-group">
                                    <select class="form-select" id="multi-descuento-tipo" style="max-width:150px;">
                                        <option value="ninguno">Sin descuento</option>
                                        <option value="importe">Importe</option>
                                        <option value="porcentaje">Porcentaje</option>
                                    </select>
                                    <input type="number" class="form-control" id="multi-descuento-valor" min="0" step="0.01" value="0">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Flete</label>
                                <input type="number" class="form-control" id="multi-flete-total" min="0" step="0.01" value="0">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">IVA %</label>
                                <input type="number" class="form-control" id="multi-iva-porcentaje" min="0" max="100" step="0.01" value="16">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Dominante global</label>
                                <select class="form-select" id="multi-dominante-global">
                                    <option value="">Selecciona dominante</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check mt-4 pt-2">
                                    <input class="form-check-input" type="checkbox" id="multi-guardar-dominante-global" checked>
                                    <label class="form-check-label" for="multi-guardar-dominante-global">Guardar dominante en productos compatibles</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="card border">
                                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                        <strong>Productos seleccionados</strong>
                                        <small class="text-body-secondary">Define precio unitario por producto y elimina los que no apliquen.</small>
                                    </div>
                                    <div class="card-body p-0">
                                        <div id="multi-productos-shell" class="table-responsive">
                                            <table class="table table-sm table-mini mb-0">
                                                <thead>
                                                    <tr>
                                                        <th style="min-width:360px;">Producto</th>
                                                        <th style="width:220px;">Precio unitario</th>
                                                        <th style="width:120px;">Acción</th>
                                                    </tr>
                                                </thead>
                                                <tbody><tr><td colspan="3" class="text-body-secondary">Selecciona productos para comenzar.</td></tr></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="card border">
                                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                        <strong>Filtro de atributos</strong>
                                        <small class="text-body-secondary">Selecciona solo valores que vas a capturar; puedes quitar o volver a incluir cuando quieras.</small>
                                    </div>
                                    <div class="card-body" id="multi-atributos-shell">
                                        <div class="text-body-secondary small">Selecciona productos para habilitar filtros de atributos.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="multi-legend d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                    <div class="d-flex flex-wrap align-items-center gap-3">
                                        <span class="d-inline-flex align-items-center gap-1">
                                            <span class="multi-dot multi-dot-ok"></span>
                                            <span>Aplica</span>
                                        </span>
                                        <span class="d-inline-flex align-items-center gap-1">
                                            <span class="multi-dot multi-dot-na"></span>
                                            <span>No aplica (solo lectura)</span>
                                        </span>
                                    </div>
                                    <div class="btn-group btn-group-sm density-switch" role="group" aria-label="Densidad de matriz">
                                        <button type="button" class="btn btn-outline-secondary" id="btn-density-auto" data-density="auto">Auto</button>
                                        <button type="button" class="btn btn-outline-secondary active" id="btn-density-compact" data-density="compact">Compacta</button>
                                        <button type="button" class="btn btn-outline-secondary" id="btn-density-comfortable" data-density="comfortable">Amplia</button>
                                    </div>
                                    <span class="density-state-chip" id="density-state-chip" aria-live="polite">Auto -> Compacta</span>
                                </div>
                                <p id="multi-grid-help" class="small text-body-secondary mb-2">
                                    Usa Tab para avanzar. También puedes usar flechas para moverte entre celdas aplicables.
                                </p>
                                <div id="multi-a11y-status" class="visually-hidden" aria-live="polite"></div>
                                <div class="row g-2 mb-2" id="multi-totales-shell">
                                    <div class="col-md-2"><div class="inv-stat-card"><div><div class="inv-stat-card__lbl">Subtotal</div><div class="inv-stat-card__val" id="multi-total-subtotal">$0.00</div></div></div></div>
                                    <div class="col-md-2"><div class="inv-stat-card"><div><div class="inv-stat-card__lbl">Descuento</div><div class="inv-stat-card__val" id="multi-total-descuento">$0.00</div></div></div></div>
                                    <div class="col-md-2"><div class="inv-stat-card"><div><div class="inv-stat-card__lbl">Flete</div><div class="inv-stat-card__val" id="multi-total-flete">$0.00</div></div></div></div>
                                    <div class="col-md-2"><div class="inv-stat-card"><div><div class="inv-stat-card__lbl">IVA</div><div class="inv-stat-card__val" id="multi-total-iva">$0.00</div></div></div></div>
                                    <div class="col-md-2"><div class="inv-stat-card"><div><div class="inv-stat-card__lbl">Piezas</div><div class="inv-stat-card__val" id="multi-total-piezas">0</div></div></div></div>
                                    <div class="col-md-2"><div class="inv-stat-card"><div><div class="inv-stat-card__lbl">Total</div><div class="inv-stat-card__val" id="multi-total-general">$0.00</div></div></div></div>
                                </div>
                                <div id="multi-grid-shell" class="multi-grid-wrap">
                                    <table class="table table-sm table-mini mb-0">
                                        <tbody><tr><td class="text-body-secondary">Selecciona productos para cargar líneas.</td></tr></tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <button class="btn btn-primary" type="submit" {{ ($permisosUI['inicial'] || $permisosUI['entrada']) ? '' : 'disabled' }}>Registrar entradas seleccionadas</button>
                            </div>
                        </form>
                    </div>

                    <form id="form-inicial" class="row g-3">
                        @csrf
                        <input type="hidden" name="prd_id" id="inicial-prd-id">
                        <div class="col-md-4">
                            <label class="form-label">Tipo de entrada</label>
                            <select class="form-select" id="inicial-tipo-entrada" name="min_documento_tipo" required>
                                <option value="inventario_inicial">Entrada normal (inventario inicial)</option>
                                <option value="entrada_normal">Entrada normal</option>
                                <option value="compra_remision">Compra con remisión</option>
                                <option value="compra_factura">Compra con factura</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sucursal</label>
                            <select class="form-select input-sucursal" name="min_scl_id" required>
                                <option value="">Selecciona</option>
                                @foreach($opciones['sucursales'] as $sucursal)
                                    <option value="{{ $sucursal->scl_id }}">{{ $sucursal->scl_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Almacén</label>
                            <select class="form-select input-almacen" name="min_alm_id" required><option value="">Selecciona</option></select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fecha</label>
                            <input type="datetime-local" class="form-control" name="min_fecha_movimiento" required>
                        </div>
                        <div class="col-md-3 d-none" id="inicial-simple-cantidad-shell">
                            <label class="form-label">Cantidad</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="inicial-cantidad-simple" name="min_cantidad_simple">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" id="lbl-inicial-referencia">Referencia (opcional)</label>
                            <input type="text" class="form-control" name="min_documento_referencia" maxlength="120">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Motivo</label>
                            <input type="text" class="form-control" name="min_motivo_texto" maxlength="500" required>
                        </div>
                        <div class="col-md-3 d-none" id="inicial-dominante-shell">
                            <label class="form-label">Variable dominante</label>
                            <select class="form-select" id="inicial-dominante-atr-id" name="dominante_atr_id"></select>
                            <small id="inicial-dominante-hint" class="text-body-secondary d-block mt-1"></small>
                        </div>
                        <div class="col-md-3 d-none" id="inicial-dominante-guardar-shell">
                            <div class="form-check mt-4 pt-2">
                                <input class="form-check-input" type="checkbox" id="inicial-guardar-dominante" name="dominante_guardar_predeterminado" value="1" checked>
                                <label class="form-check-label" for="inicial-guardar-dominante">Guardar como predeterminada</label>
                            </div>
                        </div>
                        <div class="col-12" id="inicial-matriz-card">
                            <div class="card border">
                                <div class="card-header py-2">
                                    <strong>Matriz de variantes</strong>
                                    <small class="text-body-secondary ms-2">Captura existencias y usa Tab para avanzar entre celdas.</small>
                                </div>
                                <div class="card-body p-0">
                                    <div id="matriz-inicial-shell" class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <thead><tr><th>SKU</th><th>Variante</th><th style="width:180px;">Existencia inicial</th></tr></thead>
                                            <tbody><tr><td colspan="3" class="text-body-secondary">Selecciona producto base para continuar.</td></tr></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button class="btn btn-primary" type="submit" {{ ($permisosUI['inicial'] || $permisosUI['entrada']) ? '' : 'disabled' }}>Registrar entrada</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-recibir-mercancia" role="tabpanel">
                <div class="inv-section-label"><i class="ti tabler-truck-delivery me-1"></i>Recibir mercancía (manual)</div>
                <form id="form-recibir-mercancia" class="row g-3">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label">Tipo de entrada</label>
                        <select class="form-select" id="recibir-tipo-entrada" required>
                            <option value="compra_factura">Compra con factura</option>
                            <option value="compra_remision">Compra con remisión</option>
                            <option value="entrada_normal">Entrada normal</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Sucursal</label>
                        <select class="form-select" id="recibir-scl-id" required>
                            <option value="">Selecciona</option>
                            @foreach($opciones['sucursales'] as $sucursal)
                                <option value="{{ $sucursal->scl_id }}">{{ $sucursal->scl_nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Almacén</label>
                        <select class="form-select" id="recibir-alm-id" required><option value="">Selecciona</option></select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Usuario</label>
                        <input type="text" class="form-control" value="{{ auth()->user()->usr_nombre ?? 'N/D' }}" readonly>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Proveedor</label>
                        <select class="form-select" id="recibir-prv-id">
                            <option value="">Sin proveedor</option>
                            @foreach($opciones['proveedores'] as $proveedor)
                                <option value="{{ $proveedor->prv_id }}">{{ $proveedor->prv_nombre_empresa }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Factura / Ref.</label>
                        <input type="text" class="form-control" id="recibir-referencia" maxlength="120">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-check pb-2">
                            <input class="form-check-input" type="checkbox" id="recibir-referencia-na">
                            <label class="form-check-label" for="recibir-referencia-na">N/A</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Fecha factura</label>
                        <input type="datetime-local" class="form-control" id="recibir-fecha-emision">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Fecha captura</label>
                        <input type="datetime-local" class="form-control" id="recibir-fecha-captura" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Comentario</label>
                        <input type="text" class="form-control" id="recibir-observaciones" maxlength="1500" placeholder="Defectos, faltantes o notas de recepción">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Descuento</label>
                        <div class="input-group">
                            <select class="form-select" id="recibir-descuento-tipo" style="max-width:150px;">
                                <option value="ninguno">Sin descuento</option>
                                <option value="porcentaje">Porcentaje</option>
                                <option value="importe">Importe</option>
                            </select>
                            <input type="number" class="form-control" id="recibir-descuento-valor" min="0" step="0.01" value="0">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Flete</label>
                        <input type="number" class="form-control" id="recibir-flete-total" min="0" step="0.01" value="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">IVA %</label>
                        <input type="number" class="form-control" id="recibir-iva-porcentaje" min="0" max="100" step="0.01" value="16">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <div class="form-check pb-2">
                            <input class="form-check-input" type="checkbox" id="recibir-incluir-iva" checked>
                            <label class="form-check-label" for="recibir-incluir-iva">IVA</label>
                        </div>
                    </div>

                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary" id="btn-recibir-buscar-articulos">
                                <i class="ti tabler-search me-1"></i>Buscar artículos
                            </button>
                        </div>
                        <button type="submit" class="btn btn-primary" {{ ($permisosUI['inicial'] || $permisosUI['entrada']) ? '' : 'disabled' }}>
                            <i class="ti tabler-device-floppy me-1"></i>Guardar entrada
                        </button>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Dominante global</label>
                        <select class="form-select" id="recibir-dominante-global">
                            <option value="">Selecciona dominante</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="card border">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <strong>Productos seleccionados</strong>
                                <small class="text-body-secondary">Define costo unitario por producto y elimina los que no apliquen.</small>
                            </div>
                            <div class="card-body p-0">
                                <div id="recibir-productos-shell" class="table-responsive">
                                    <table class="table table-sm table-mini mb-0">
                                        <thead>
                                            <tr>
                                                <th style="min-width:360px;">Producto</th>
                                                <th style="width:120px;">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody><tr><td colspan="2" class="text-body-secondary">Sin productos seleccionados.</td></tr></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card border">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <strong>Filtro de atributos</strong>
                                <small class="text-body-secondary">Selecciona solo atributos que aplican en esta recepción.</small>
                            </div>
                            <div class="card-body" id="recibir-atributos-shell">
                                <div class="text-body-secondary small">Selecciona productos para habilitar filtros.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="multi-legend d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                            <div class="d-flex flex-wrap align-items-center gap-3">
                                <span class="d-inline-flex align-items-center gap-1">
                                    <span class="multi-dot multi-dot-ok"></span>
                                    <span>Aplica</span>
                                </span>
                                <span class="d-inline-flex align-items-center gap-1">
                                    <span class="multi-dot multi-dot-na"></span>
                                    <span>No aplica (solo lectura)</span>
                                </span>
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-recibir-restaurar-filas" disabled>
                                <i class="ti tabler-restore me-1"></i>Restaurar quitados
                            </button>
                        </div>
                        <p id="recibir-grid-help" class="small text-body-secondary mb-2">
                            Captura en matriz horizontal por dominante. Usa Tab o flechas para moverte.
                        </p>
                        <div id="recibir-grid-shell" class="multi-grid-wrap">
                            <table class="table table-sm table-mini mb-0">
                                <tbody><tr><td class="text-body-secondary">Sin productos seleccionados. Usa "Buscar artículos".</td></tr></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="row g-2" id="recibir-totales-shell">
                            <div class="col-md-2"><div class="inv-stat-card"><div><div class="inv-stat-card__lbl">Artículos</div><div class="inv-stat-card__val" id="recibir-total-articulos">0</div></div></div></div>
                            <div class="col-md-2"><div class="inv-stat-card"><div><div class="inv-stat-card__lbl">Subtotal</div><div class="inv-stat-card__val" id="recibir-total-subtotal">$0.00</div></div></div></div>
                            <div class="col-md-2"><div class="inv-stat-card"><div><div class="inv-stat-card__lbl">Descuento</div><div class="inv-stat-card__val" id="recibir-total-descuento">$0.00</div></div></div></div>
                            <div class="col-md-2"><div class="inv-stat-card"><div><div class="inv-stat-card__lbl">Flete</div><div class="inv-stat-card__val" id="recibir-total-flete">$0.00</div></div></div></div>
                            <div class="col-md-2"><div class="inv-stat-card"><div><div class="inv-stat-card__lbl">IVA</div><div class="inv-stat-card__val" id="recibir-total-iva">$0.00</div></div></div></div>
                            <div class="col-md-2"><div class="inv-stat-card"><div><div class="inv-stat-card__lbl">Total</div><div class="inv-stat-card__val" id="recibir-total-general">$0.00</div></div></div></div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="tab-pane fade" id="tab-salidas" role="tabpanel">
                <form id="form-salida" class="row g-3">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">SKU</label>
                        <select
                            class="form-select js-remote-select"
                            id="salida-sku-id"
                            name="min_psk_id"
                            data-url="{{ route('operacion.inventario_base.skus.buscar') }}"
                            data-placeholder="Busca por código, SKU o producto"
                            data-min-input="2"
                            required
                        >
                            <option value="">Selecciona</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Sucursal</label>
                        <select class="form-select input-sucursal" id="salida-scl-id" name="min_scl_id" required>
                            <option value="">Selecciona</option>
                            @foreach($opciones['sucursales'] as $sucursal)
                                <option value="{{ $sucursal->scl_id }}">{{ $sucursal->scl_nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Almacén</label>
                        <select class="form-select input-almacen" id="salida-alm-id" name="min_alm_id" required><option value="">Selecciona</option></select>
                    </div>

                    {{-- Availability indicator --}}
                    <div class="col-12 d-none" id="inv-availability-wrap">
                        <div id="inv-availability-card">
                            <i class="ti tabler-box av-icon"></i>
                            <div>
                                <div class="av-val" id="av-existencia">0</div>
                                <div class="av-lbl">Existencia disponible en almacén</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Cantidad a retirar</label>
                        <input type="number" step="0.01" min="0.01" class="form-control" name="min_cantidad" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tipo salida</label>
                        <select class="form-select" name="min_documento_tipo" required>
                            <option value="ajuste_manual">Ajuste manual</option>
                            <option value="merma">Merma</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Fecha</label>
                        <input type="datetime-local" class="form-control" name="min_fecha_movimiento" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Motivo catálogo</label>
                        <select class="form-select" name="min_mtv_id">
                            <option value="">Selecciona</option>
                            @foreach($opciones['motivos'] as $motivo)
                                <option value="{{ $motivo->mtv_id }}">{{ $motivo->mtv_nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Referencia (opcional)</label>
                        <input type="text" class="form-control" name="min_documento_referencia" maxlength="120">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Motivo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="min_motivo_texto" maxlength="500" placeholder="Describe la razón de la salida" required>
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button class="btn btn-danger" type="submit" {{ ($permisosUI['salida'] || $permisosUI['ajustar']) ? '' : 'disabled' }}>
                            <i class="ti tabler-package-export me-1"></i>Registrar salida
                        </button>
                    </div>
                </form>
            </div>

            <div class="tab-pane fade" id="tab-correcciones" role="tabpanel">
                <div class="d-flex align-items-center gap-2 mb-3 p-3 rounded" style="background:var(--ls-warning-bg);border:1px solid var(--ls-warning);border-left:4px solid var(--ls-warning);">
                    <i class="ti tabler-info-circle" style="font-size:1.15rem;color:var(--ls-warning);flex-shrink:0;"></i>
                    <p class="mb-0 small">Selecciona un movimiento activo del listado para aplicar <strong>cancelación con reversa</strong> o <strong>corrección</strong>. Los movimientos ya cancelados o corregidos no se pueden volver a modificar.</p>
                </div>
                <div class="table-responsive">
                    <table id="tbl-movimientos" class="table table-mini">
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Fecha</th>
                                <th>SKU</th>
                                <th>Sucursal / Almacén</th>
                                <th>Tipo</th>
                                <th class="text-end">Cantidad</th>
                                <th>Estatus</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-kardex" role="tabpanel">
                <div class="inv-filter-bar">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Sucursal</label>
                            <select id="flt-kar-scl" class="form-select form-select-sm">
                                <option value="">Todas</option>
                                @foreach($opciones['sucursales'] as $sucursal)
                                    <option value="{{ $sucursal->scl_id }}">{{ $sucursal->scl_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Almacén</label>
                            <select id="flt-kar-alm" class="form-select form-select-sm"><option value="">Todos</option></select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Desde</label>
                            <input id="flt-kar-desde" type="date" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Hasta</label>
                            <input id="flt-kar-hasta" type="date" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button class="btn btn-primary btn-sm w-100" id="btn-filtrar-kardex"><i class="ti tabler-filter me-1"></i>Aplicar</button>
                            <button class="btn btn-outline-secondary btn-sm" id="btn-limpiar-kardex" title="Limpiar"><i class="ti tabler-x"></i></button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="tbl-kardex" class="table table-mini">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Folio</th>
                                <th>Código</th>
                                <th>Producto / Variante</th>
                                <th>Sucursal</th>
                                <th>Almacén</th>
                                <th>Movimiento</th>
                                <th class="text-end">Cantidad</th>
                                <th class="text-end">Antes</th>
                                <th class="text-end">Después</th>
                                <th>Usuario</th>
                                <th>Estatus</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-minimos" role="tabpanel">
                <div class="inv-section-label"><i class="ti tabler-settings me-1"></i>Configurar stock mínimo</div>
                <form id="form-minimo" class="row g-3 mb-4">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label">SKU</label>
                        <select class="form-select" name="mni_psk_id" required>
                            <option value="">Selecciona</option>
                            @foreach($opciones['skus'] as $sku)
                                <option value="{{ $sku->psk_id }}">{{ $sku->psk_codigo }} - {{ $sku->psk_nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Sucursal</label>
                        <select class="form-select input-sucursal" name="mni_scl_id" required>
                            <option value="">Selecciona</option>
                            @foreach($opciones['sucursales'] as $sucursal)
                                <option value="{{ $sucursal->scl_id }}">{{ $sucursal->scl_nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Almacén</label>
                        <select class="form-select input-almacen" name="mni_alm_id" required><option value="">Selecciona</option></select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Stock mínimo</label>
                        <input type="number" min="0" step="0.01" class="form-control" name="mni_minimo" placeholder="0" required>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button class="btn btn-primary w-100" type="submit" {{ $permisosUI['minimos'] ? '' : 'disabled' }}>
                            <i class="ti tabler-device-floppy"></i>
                        </button>
                    </div>
                </form>

                <div class="inv-section-label"><i class="ti tabler-alert-triangle me-1"></i>Reporte de SKUs bajo mínimo</div>
                <div class="inv-filter-bar mb-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Sucursal</label>
                            <select id="flt-min-scl" class="form-select form-select-sm">
                                <option value="">Todas</option>
                                @foreach($opciones['sucursales'] as $sucursal)
                                    <option value="{{ $sucursal->scl_id }}">{{ $sucursal->scl_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Almacén</label>
                            <select id="flt-min-alm" class="form-select form-select-sm"><option value="">Todos</option></select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button id="btn-recargar-bajo-minimo" class="btn btn-primary btn-sm w-100"><i class="ti tabler-refresh me-1"></i>Actualizar reporte</button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="tbl-bajo-minimo" class="table table-mini">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Sucursal</th>
                                <th>Almacén</th>
                                <th class="text-end">Existencia</th>
                                <th class="text-end">Mínimo</th>
                                <th class="text-end">Faltante</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-reportes-entradas" role="tabpanel">
                <div class="inv-filter-bar mb-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Desde</label>
                            <input id="flt-rep-desde" type="date" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Hasta</label>
                            <input id="flt-rep-hasta" type="date" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button class="btn btn-primary btn-sm w-100" id="btn-filtrar-reportes"><i class="ti tabler-filter me-1"></i>Aplicar</button>
                            <button class="btn btn-outline-secondary btn-sm" id="btn-limpiar-reportes" title="Limpiar"><i class="ti tabler-x"></i></button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="tbl-reportes-entradas" class="table table-mini">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Usuario</th>
                                <th>Sucursal</th>
                                <th>Almacén</th>
                                <th>Tipo entrada</th>
                                <th class="text-end">Folios</th>
                                <th class="text-end">Total</th>
                                <th>Detalle folios</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-recibir-buscar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti tabler-search me-1"></i>Buscar artículos para recibir mercancía</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="inv-filter-bar">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Buscar</label>
                            <input id="recibir-buscar-texto" type="text" class="form-control form-control-sm" placeholder="Código o producto base">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Marca</label>
                            <select id="recibir-buscar-marca" class="form-select form-select-sm">
                                <option value="">Todas</option>
                                @foreach($opciones['marcas'] as $marca)
                                    <option value="{{ $marca->mrc_id }}">{{ $marca->mrc_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Modelo</label>
                            <select id="recibir-buscar-modelo" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                @foreach($opciones['modelos'] as $modelo)
                                    <option value="{{ $modelo->mdl_id }}">{{ $modelo->mdl_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Línea</label>
                            <select id="recibir-buscar-linea" class="form-select form-select-sm">
                                <option value="">Todas</option>
                                @foreach($opciones['lineas'] as $linea)
                                    <option value="{{ $linea->lna_id }}">{{ $linea->lna_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Concepto</label>
                            <select id="recibir-buscar-categoria" class="form-select form-select-sm">
                                <option value="">Todas</option>
                                @foreach($opciones['categorias'] as $categoria)
                                    <option value="{{ $categoria->ctg_id }}" data-lna="{{ $categoria->ctg_lna_id }}">{{ $categoria->ctg_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button class="btn btn-primary btn-sm w-100" id="btn-recibir-filtrar-modal"><i class="ti tabler-filter me-1"></i>Buscar</button>
                            <button class="btn btn-outline-secondary btn-sm" id="btn-recibir-limpiar-modal"><i class="ti tabler-x"></i></button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="tbl-recibir-buscar-productos" class="table table-mini">
                        <thead>
                            <tr>
                                <th style="width:42px;"></th>
                                <th>Código</th>
                                <th>Producto base</th>
                                <th>Tipo</th>
                                <th>Marca</th>
                                <th>Línea</th>
                                <th>SKU activos</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="btn-recibir-agregar-seleccionados">
                    <i class="ti tabler-check me-1"></i>Agregar seleccionados
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-cancelar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="form-cancelar">
                <div class="modal-header" style="border-bottom:1px solid var(--ls-border);">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-inline-flex align-items-center justify-content-center rounded" style="width:2rem;height:2rem;background:var(--ls-danger-bg);color:var(--ls-danger);font-size:1rem;"><i class="ti tabler-ban"></i></span>
                        <h5 class="modal-title mb-0">Cancelar movimiento</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="cancelar-min-id">
                    <div class="d-flex align-items-start gap-2 mb-3 p-2 rounded" style="background:var(--ls-danger-bg);border:1px solid var(--ls-danger);font-size:.83rem;">
                        <i class="ti tabler-alert-triangle" style="color:var(--ls-danger);margin-top:.1rem;flex-shrink:0;"></i>
                        <span>Se creará un movimiento de <strong>reversa automática</strong> para anular este registro. Esta acción no se puede deshacer.</span>
                    </div>
                    <label class="form-label fw-semibold">Motivo de cancelación <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="cancelar-motivo" rows="3" maxlength="500" placeholder="Describe la razón de la cancelación..." required></textarea>
                    <div class="form-text">Máx. 500 caracteres.</div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--ls-border);">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-danger"><i class="ti tabler-ban me-1"></i>Cancelar con reversa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-corregir" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="form-corregir">
                <div class="modal-header" style="border-bottom:1px solid var(--ls-border);">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-inline-flex align-items-center justify-content-center rounded" style="width:2rem;height:2rem;background:var(--ls-accent-light);color:var(--ls-accent);font-size:1rem;"><i class="ti tabler-pencil"></i></span>
                        <h5 class="modal-title mb-0">Corregir movimiento</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="corregir-min-id">
                    <div class="d-flex align-items-start gap-2 mb-3 p-2 rounded" style="background:var(--ls-accent-light);border:1px solid var(--ls-accent-mid);font-size:.83rem;">
                        <i class="ti tabler-info-circle" style="color:var(--ls-accent);margin-top:.1rem;flex-shrink:0;"></i>
                        <span>Se aplicará una <strong>reversa automática</strong> y se creará un nuevo movimiento con los valores corregidos. Ambos quedan registrados en el kardex.</span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Motivo de corrección <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="corregir-motivo" rows="2" maxlength="500" placeholder="¿Por qué se corrige este movimiento?" required></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nueva cantidad <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="corregir-cantidad" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nueva fecha <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="corregir-fecha" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Referencia corregida</label>
                            <input type="text" class="form-control" id="corregir-referencia" maxlength="120" placeholder="Opcional">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Motivo del nuevo movimiento <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="corregir-motivo-nuevo" maxlength="500" placeholder="Descripción del movimiento corregido" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--ls-border);">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary"><i class="ti tabler-pencil me-1"></i>Corregir con reversa</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('vendor-template/assets/vendor/libs/select2/select2.js') }}"></script>
@endpush

@push('page-scripts')
<script>
(function () {
    const soloEntradas = @json($soloEntradas);
    const permisosUI = @json($permisosUI);
    const almacenesBase = @json($opciones['almacenes']->map(fn($a) => ['alm_id' => $a->alm_id, 'alm_scl_id' => $a->alm_scl_id, 'nombre' => ($a->sucursal?->scl_nombre . ' - ' . $a->alm_nombre)])->values());

    const rutas = {
        existencias: '{{ route('operacion.inventario_base.existencias.data') }}',
        productosBaseData: '{{ route('operacion.inventario_base.productos.data') }}',
        skusBuscar: '{{ route('operacion.inventario_base.skus.buscar') }}',
        kardex: '{{ route('operacion.inventario_base.kardex.data') }}',
        bajoMinimo: '{{ route('operacion.inventario_base.minimos.bajo.data') }}',
        reportesEntradasData: '{{ route('operacion.inventario_base.reportes.entradas.data') }}',
        disponibilidad: '{{ route('operacion.inventario_base.disponibilidad') }}',
        matrizProducto: (id) => '{{ url('/operacion/inventario-base/productos') }}/' + id + '/matriz',
        inicial: '{{ route('operacion.inventario_base.inicial.store') }}',
        inicialMasivo: '{{ route('operacion.inventario_base.inicial.masivo.store') }}',
        entradaMasiva: '{{ route('operacion.inventario_base.entradas.masivo.store') }}',
        entrada: '{{ route('operacion.inventario_base.entradas.store') }}',
        salida: '{{ route('operacion.inventario_base.salidas.store') }}',
        minimo: '{{ route('operacion.inventario_base.minimos.store') }}',
        reporteEntradasPdf: '{{ route('operacion.inventario_base.reportes.entradas_seleccionadas.pdf') }}',
        verReporteEntradasPdf: (id) => '{{ url('/operacion/inventario-base/reportes/entradas') }}/' + id + '/pdf',
        cancelar: (id) => '{{ url('/operacion/inventario-base/movimientos') }}/' + id + '/cancelar',
        corregir: (id) => '{{ url('/operacion/inventario-base/movimientos') }}/' + id + '/corregir'
    };

    const modalCancelar = new bootstrap.Modal(document.getElementById('modal-cancelar'));
    const modalCorregir = new bootstrap.Modal(document.getElementById('modal-corregir'));
    const modalRecibirBuscar = new bootstrap.Modal(document.getElementById('modal-recibir-buscar'));
    const storageKeyDensity = 'inventario.multiGridDensity';
    let multiGridDensity = 'auto';
    const estadoInicial = {
        productoId: null,
        productoTipo: null,
        simpleSkuId: null,
        matrizData: null,
        colaProductos: [],
        colaIndex: 0,
        modoMasivo: false,
        multiMeta: {},
        multiFiltrosAtributos: {}
    };
    const seleccionProductos = {};
    const recibirState = {
        modalSeleccion: {},
        tablaModalInicializada: false,
        productos: {},
        meta: {},
        filtrosAtributos: {},
        cantidades: {},
        costosFila: {},
        costosFilaEditados: {},
        filasExcluidas: {},
        productosQuitados: {},
    };

    function parseError(xhr) {
        if (xhr.responseJSON?.errors) {
            const mensajes = [];
            Object.values(xhr.responseJSON.errors).forEach((arr) => (arr || []).forEach((m) => mensajes.push(m)));
            if (mensajes.length > 0) {
                return mensajes.join('\n');
            }
        }

        return xhr.responseJSON?.message || 'No fue posible completar la operación.';
    }

    function estatusBadge(estatus) {
        if (estatus === 'activo') return '<span class="ls-badge ls-badge-success">Activo</span>';
        if (estatus === 'cancelado') return '<span class="ls-badge ls-badge-danger">Cancelado</span>';
        if (estatus === 'corregido') return '<span class="ls-badge ls-badge-warning">Corregido</span>';
        return '<span class="ls-badge">' + (estatus || '-') + '</span>';
    }

    function normalizeDensity(valor) {
        if (valor === 'comfortable') return 'comfortable';
        if (valor === 'compact') return 'compact';
        return 'auto';
    }

    function resolveEffectiveDensity() {
        if (multiGridDensity === 'auto') {
            return window.matchMedia('(max-width: 992px)').matches ? 'compact' : 'comfortable';
        }

        return multiGridDensity;
    }

    function applyGridDensity(density, persist = false) {
        multiGridDensity = normalizeDensity(density);
        const effective = resolveEffectiveDensity();
        const $shell = $('#multi-grid-shell');
        $shell.removeClass('multi-density-compact multi-density-comfortable')
            .addClass(effective === 'comfortable' ? 'multi-density-comfortable' : 'multi-density-compact');

        $('#btn-density-auto').toggleClass('active', multiGridDensity === 'auto');
        $('#btn-density-compact').toggleClass('active', multiGridDensity === 'compact');
        $('#btn-density-comfortable').toggleClass('active', multiGridDensity === 'comfortable');
        const modoSeleccionado = multiGridDensity === 'auto'
            ? 'Auto'
            : (multiGridDensity === 'comfortable' ? 'Amplia' : 'Compacta');
        const modoEfectivo = effective === 'comfortable' ? 'Amplia' : 'Compacta';
        const textoEstado = multiGridDensity === 'auto'
            ? ('Auto -> ' + modoEfectivo)
            : modoSeleccionado;
        $('#density-state-chip').text(textoEstado);

        if (persist) {
            try {
                localStorage.setItem(storageKeyDensity, multiGridDensity);
            } catch (_) {}
        }
    }

    function escapeHtml(texto) {
        return String(texto ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function toMoney(valor) {
        const n = Number(valor || 0);
        return '$' + n.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function normalizarTexto(valor) {
        return String(valor || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-zA-Z0-9\s]/g, ' ')
            .toLowerCase()
            .replace(/\s+/g, ' ')
            .trim();
    }

    function costoBaseProducto(producto) {
        const costo = Number(producto?.prd_costo ?? 0);
        if (costo > 0) return costo;
        const precioBase = Number(producto?.prd_precio_base ?? 0);
        return precioBase > 0 ? precioBase : 0;
    }

    function renderProductoKardex(row) {
        const nombreProducto = escapeHtml(row?.prd_nombre || '-');
        if (row?.prd_tipo !== 'variable') {
            return '<div class="fw-semibold">' + nombreProducto + '</div>';
        }

        const variante = escapeHtml(row?.psk_nombre || '');
        if (!variante) {
            return '<div class="fw-semibold">' + nombreProducto + '</div>';
        }

        return '' +
            '<div class="fw-semibold">' + nombreProducto + '</div>' +
            '<div class="text-body-secondary small">' + variante + '</div>';
    }

    function initRemoteSelect($select, options = {}) {
        if (!$select || $select.length === 0 || typeof $select.select2 !== 'function') return;

        const url = options.url || $select.data('url');
        if (!url) return;

        const minInput = Number(options.minInput || $select.data('min-input') || 2);
        const placeholder = options.placeholder || $select.data('placeholder') || 'Buscar...';

        if (!$select.parent().hasClass('position-relative')) {
            $select.wrap('<div class="position-relative"></div>');
        }

        $select.select2({
            width: '100%',
            placeholder,
            allowClear: true,
            minimumInputLength: minInput,
            ajax: {
                url,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term || '',
                        page: params.page || 1
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.results || [],
                        pagination: data.pagination || { more: false }
                    };
                }
            },
            templateResult: function (item) {
                return item.loading ? item.text : (item.text || '');
            },
            templateSelection: function (item) {
                return item.text || item.id || '';
            }
        });
    }

    function llenarAlmacenesPorSucursal(select, sucursalId, includeAll = false) {
        const opts = [];
        if (includeAll) opts.push('<option value="">Todos</option>');
        else opts.push('<option value="">Selecciona</option>');

        (almacenesBase || []).forEach(function (alm) {
            if (!sucursalId || String(alm.alm_scl_id) === String(sucursalId)) {
                opts.push('<option value="' + alm.alm_id + '">' + alm.nombre + '</option>');
            }
        });

        $(select).html(opts.join(''));
    }

    function formToPayload($form) {
        return $form.serialize();
    }

    function postForm(url, $form, successMessage, cb) {
        AppUI.showLoader();
        $.ajax({
            url,
            method: 'POST',
            data: formToPayload($form),
            dataType: 'json'
        }).done(function (resp) {
            AppUI.showMessage('Éxito', resp.message || successMessage, 'success');
            if (typeof cb === 'function') cb(resp);
        }).fail(function (xhr) {
            AppUI.showMessage('Error', parseError(xhr), 'error');
        }).always(function () {
            AppUI.hideLoader();
        });
    }

    function descargarReporteEntradasSeleccionadas(payload) {
        const csrfToken = $('meta[name="csrf-token"]').attr('content') || '';
        if (!csrfToken || !Array.isArray(payload?.folios) || payload.folios.length === 0) return;

        fetch(rutas.reporteEntradasPdf, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/pdf',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        })
            .then(async (resp) => {
                if (!resp.ok) {
                    throw new Error('No se pudo generar el PDF del reporte.');
                }

                const blob = await resp.blob();
                const dispo = resp.headers.get('Content-Disposition') || '';
                const match = dispo.match(/filename=\"?([^\";]+)\"?/i);
                const fileName = match?.[1] || ('reporte-entradas-' + Date.now() + '.pdf');

                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = fileName;
                document.body.appendChild(a);
                a.click();
                a.remove();
                setTimeout(() => URL.revokeObjectURL(url), 1000);
            })
            .catch(() => {
                AppUI.showMessage('Aviso', 'Las entradas se registraron, pero no se pudo descargar el PDF en este intento.', 'warning');
            });
    }

    function htmlMatrizPlaceholder(msg) {
        return '<table class="table table-sm mb-0"><tbody><tr><td class="text-body-secondary">' + msg + '</td></tr></tbody></table>';
    }

    function resetEstadoInicial() {
        estadoInicial.productoId = null;
        estadoInicial.productoTipo = null;
        estadoInicial.simpleSkuId = null;
        estadoInicial.matrizData = null;
        estadoInicial.modoMasivo = false;
        $('#inicial-prd-id').val('');
        $('#entrada-producto-seleccionado').text('-');
        $('#inicial-simple-cantidad-shell').addClass('d-none');
        $('#inicial-cantidad-simple').val('').prop('required', false);
        $('#inicial-dominante-shell').addClass('d-none');
        $('#inicial-dominante-guardar-shell').addClass('d-none');
        $('#inicial-dominante-atr-id').html('');
        $('#inicial-dominante-hint').text('');
        $('#inicial-guardar-dominante').prop('checked', true);
        $('#inicial-matriz-card').removeClass('d-none');
        $('#matriz-inicial-shell').html(htmlMatrizPlaceholder('Selecciona producto base para continuar.'));
        $('#form-inicial .js-dyn-inicial').remove();
        estadoInicial.multiMeta = {};
        estadoInicial.multiFiltrosAtributos = {};
        $('#multi-dominante-global').html('<option value="">Selecciona dominante</option>');
        $('#multi-guardar-dominante-global').prop('checked', true);
        $('#multi-productos-shell').html('<table class="table table-sm table-mini mb-0"><tbody><tr><td colspan="3" class="text-body-secondary">Selecciona productos para comenzar.</td></tr></tbody></table>');
        $('#multi-atributos-shell').html('<div class="text-body-secondary small">Selecciona productos para habilitar filtros de atributos.</div>');
        $('#multi-grid-shell').html('<table class="table table-sm table-mini mb-0"><tbody><tr><td class="text-body-secondary">Selecciona productos para cargar líneas.</td></tr></tbody></table>');
        $('#multi-resumen-productos').text('0 productos seleccionados');
        $('#multi-total-subtotal').text('$0.00');
        $('#multi-total-descuento').text('$0.00');
        $('#multi-total-flete').text('$0.00');
        $('#multi-total-iva').text('$0.00');
        $('#multi-total-piezas').text('0');
        $('#multi-total-general').text('$0.00');
        applyGridDensity(multiGridDensity, false);
        activarModoCapturaMasiva(false);
        actualizarIndicadorProductoEnCola();
    }

    function actualizarContadorSeleccionados() {
        const total = Object.keys(seleccionProductos).length;
        $('#lbl-productos-seleccionados').text(total + ' seleccionado' + (total === 1 ? '' : 's'));
        $('#btn-capturar-seleccionados').prop('disabled', total === 0);
    }

    function actualizarWizardSteps(paso) {
        const $s1 = $('#wiz-step1-indicator');
        const $s2 = $('#wiz-step2-indicator');
        if (paso === 1) {
            $s1.removeClass('done').addClass('active');
            $s2.removeClass('active done');
        } else {
            $s1.removeClass('active').addClass('done');
            $s2.addClass('active');
        }
    }

    function actualizarIndicadorProductoEnCola() {
        const total = estadoInicial.colaProductos.length || 1;
        const indice = Math.min((estadoInicial.colaIndex || 0) + 1, total);
        $('#entrada-producto-indice').text('Producto ' + indice + ' de ' + total);
        $('#btn-producto-anterior').prop('disabled', (estadoInicial.colaIndex || 0) <= 0);
        $('#btn-producto-siguiente').prop('disabled', (estadoInicial.colaIndex || 0) >= (estadoInicial.colaProductos.length - 1));
    }

    function limpiarSeleccionProductos() {
        Object.keys(seleccionProductos).forEach(function (key) {
            delete seleccionProductos[key];
        });
        $('#chk-productos-todos').prop('checked', false);
        actualizarContadorSeleccionados();
        if ($.fn.DataTable.isDataTable('#tbl-productos-base')) {
            $('#tbl-productos-base').DataTable().rows().invalidate().draw(false);
        }
    }

    function asignarProductoActual(payload) {
        const etiqueta = (payload.prd_codigo || '-') + ' - ' + (payload.prd_nombre || '-');
        $('#entrada-producto-seleccionado').text(etiqueta);
        activarModoCapturaMasiva(false);
        mostrarPasoCaptura();
        actualizarIndicadorProductoEnCola();
        cargarProductoInicial(payload.prd_id, payload.prd_tipo);
    }

    function cargarProductoDesdeCola(index) {
        if (!estadoInicial.colaProductos.length) return;
        const nuevoIndex = Math.max(0, Math.min(index, estadoInicial.colaProductos.length - 1));
        estadoInicial.colaIndex = nuevoIndex;
        asignarProductoActual(estadoInicial.colaProductos[nuevoIndex]);
    }

    function iniciarCapturaSeleccion(productos) {
        if (!Array.isArray(productos) || productos.length === 0) {
            AppUI.showMessage('Validación', 'Selecciona al menos un producto para continuar.', 'warning');
            return;
        }
        estadoInicial.colaProductos = productos;
        estadoInicial.colaIndex = 0;
        estadoInicial.multiFiltrosAtributos = {};
        if (productos.length > 1) {
            activarModoCapturaMasiva(true);
            mostrarPasoCaptura();
            cargarProductosMasivosSeleccionados();
            return;
        }
        cargarProductoDesdeCola(0);
    }

    function actualizarUIReferenciaEntrada() {
        const tipo = String($('#inicial-tipo-entrada').val() || 'inventario_inicial');
        const $ref = $('#form-inicial [name="min_documento_referencia"]');
        const $lbl = $('#lbl-inicial-referencia');
        if (tipo === 'compra_remision') {
            $lbl.text('Referencia remisión');
            $ref.prop('required', true);
            return;
        }
        if (tipo === 'compra_factura') {
            $lbl.text('Referencia factura');
            $ref.prop('required', true);
            return;
        }
        $lbl.text('Referencia (opcional)');
        $ref.prop('required', false);
    }

    function nombreAtributoPorId(data, atrId) {
        const atributo = (data?.atributos || []).find((item) => String(item.atr_id) === String(atrId));
        return atributo?.atr_nombre || null;
    }

    function renderMatrizInicial(data, dominanteAtrId = null) {
        const lineas = data?.lineas || [];
        const attrs = data?.atributos || [];
        const shell = $('#matriz-inicial-shell');

        if (lineas.length === 0) {
            shell.html('<table class="table table-sm mb-0"><tbody><tr><td class="text-body-secondary">El producto no tiene variantes activas para captura.</td></tr></tbody></table>');
            return;
        }

        if ((data?.producto?.prd_tipo || 'simple') === 'variable' && attrs.length > 0) {
            const atrDominanteId = dominanteAtrId || $('#inicial-dominante-atr-id').val() || data?.dominante_sugerido_atr_id || attrs[0].atr_id;
            const atrDominanteNombre = nombreAtributoPorId(data, atrDominanteId) || attrs[0].atr_nombre;
            const attrsColumnas = attrs.filter((a) => a.atr_nombre !== atrDominanteNombre);

            const columnas = [];
            const mapaColumnas = {};
            const filas = {};
            const ordenFilas = [];

            lineas.forEach(function (linea) {
                const dominanteValor = (linea.atributos?.[atrDominanteNombre] || 'Sin valor').trim();
                if (!filas[dominanteValor]) {
                    filas[dominanteValor] = {};
                    ordenFilas.push(dominanteValor);
                }

                const keyCol = attrsColumnas.length
                    ? attrsColumnas.map((a) => (linea.atributos?.[a.atr_nombre] || '-')).join('||')
                    : '__base__';
                const tituloCol = attrsColumnas.length
                    ? attrsColumnas.map((a) => (linea.atributos?.[a.atr_nombre] || '-')).join(' / ')
                    : 'Existencia';

                if (!mapaColumnas[keyCol]) {
                    mapaColumnas[keyCol] = true;
                    columnas.push({ key: keyCol, titulo: tituloCol });
                }

                filas[dominanteValor][keyCol] = linea;
            });

            let indexLinea = 0;
            const rowsHtml = ordenFilas.map(function (filaValor) {
                const colsHtml = columnas.map(function (col) {
                    const celda = filas[filaValor]?.[col.key] || null;
                    if (!celda) {
                        return '<td class="text-body-secondary text-center">-</td>';
                    }

                    const html = '' +
                        '<input type="hidden" name="lineas[' + indexLinea + '][min_psk_id]" value="' + celda.min_psk_id + '">' +
                        '<input class="form-control form-control-sm js-matriz-cantidad" type="number" min="0" step="0.01" name="lineas[' + indexLinea + '][min_cantidad]" value="">';
                    indexLinea += 1;
                    return '<td>' + html + '</td>';
                }).join('');

                return '<tr><td class="fw-semibold">' + escapeHtml(filaValor) + '</td>' + colsHtml + '</tr>';
            }).join('');

            const colsHeader = columnas.map((col) => '<th>' + escapeHtml(col.titulo) + '</th>').join('');
            shell.html(
                '<table class="table table-sm mb-0">' +
                    '<thead><tr><th style="min-width:180px;">' + escapeHtml(atrDominanteNombre) + '</th>' + colsHeader + '</tr></thead>' +
                    '<tbody>' + rowsHtml + '</tbody>' +
                '</table>'
            );

            return;
        }

        const thAttrs = attrs.map((a) => '<th>' + a.atr_nombre + '</th>').join('');
        const rows = lineas.map(function (linea, idx) {
            const colsAttr = attrs.map((a) => '<td>' + (linea.atributos?.[a.atr_nombre] || '-') + '</td>').join('');
            return '' +
                '<tr>' +
                    '<td>' +
                        '<div class="fw-semibold">' + (linea.psk_codigo || '-') + '</div>' +
                        '<input type="hidden" name="lineas[' + idx + '][min_psk_id]" value="' + linea.min_psk_id + '">' +
                    '</td>' +
                    colsAttr +
                    '<td>' + (linea.combinacion || linea.psk_nombre || '-') + '</td>' +
                    '<td><input class="form-control form-control-sm js-matriz-cantidad" type="number" min="0" step="0.01" name="lineas[' + idx + '][min_cantidad]" value=""></td>' +
                '</tr>';
        }).join('');

        shell.html('' +
            '<table class="table table-sm mb-0">' +
                '<thead><tr><th>SKU</th>' + thAttrs + '<th>Variante</th><th style="width:180px;">Existencia inicial</th></tr></thead>' +
                '<tbody>' + rows + '</tbody>' +
            '</table>'
        );
    }

    function prepararDominante(data) {
        const atributos = data?.atributos || [];
        const select = $('#inicial-dominante-atr-id');
        const hint = $('#inicial-dominante-hint');
        if (atributos.length === 0) {
            $('#inicial-dominante-shell').addClass('d-none');
            $('#inicial-dominante-guardar-shell').addClass('d-none');
            return null;
        }

        const sugerido = data?.dominante_sugerido_atr_id || atributos[0].atr_id;
        const opciones = atributos.map((atr) => '<option value="' + atr.atr_id + '">' + escapeHtml(atr.atr_nombre) + '</option>').join('');
        select.html(opciones).val(String(sugerido));

        if (data?.dominante_sugerido_fuente === 'preferencia_sucursal') {
            hint.text('Sugerencia automática para esta sucursal.');
        } else if (data?.dominante_sugerido_fuente === 'preferencia_historica') {
            hint.text('Sugerencia automática basada en capturas previas.');
        } else {
            hint.text('Primera captura: el sistema recordará tu selección si dejas activada la opción.');
        }

        $('#inicial-dominante-shell').removeClass('d-none');
        $('#inicial-dominante-guardar-shell').removeClass('d-none');
        return Number(sugerido);
    }

    function cargarProductoInicial(productoId, tipoProducto = null) {
        $('#form-inicial .js-dyn-inicial').remove();

        if (!productoId) {
            resetEstadoInicial();
            return;
        }

        AppUI.showLoader();
        $.getJSON(rutas.matrizProducto(productoId), {
            min_scl_id: $('#form-inicial [name="min_scl_id"]').val() || ''
        })
            .done(function (resp) {
                const data = resp.data || {};
                const lineas = data.lineas || [];
                const tipo = data?.producto?.prd_tipo || tipoProducto || 'simple';

                estadoInicial.productoId = Number(productoId);
                estadoInicial.productoTipo = tipo;
                estadoInicial.simpleSkuId = null;
                estadoInicial.matrizData = data;
                $('#inicial-prd-id').val(String(productoId));

                if (tipo === 'variable') {
                    $('#inicial-simple-cantidad-shell').addClass('d-none');
                    $('#inicial-cantidad-simple').val('').prop('required', false);
                    $('#inicial-matriz-card').removeClass('d-none');
                    const dominante = prepararDominante(data);
                    renderMatrizInicial(data, dominante);
                    return;
                }

                const primeraLinea = lineas[0] || null;
                if (!primeraLinea?.min_psk_id) {
                    AppUI.showMessage('Validación', 'El producto simple no tiene SKU activo para carga inicial.', 'warning');
                    resetEstadoInicial();
                    return;
                }

                estadoInicial.simpleSkuId = Number(primeraLinea.min_psk_id);
                $('#inicial-simple-cantidad-shell').removeClass('d-none');
                $('#inicial-cantidad-simple').prop('required', true).focus();
                $('#inicial-dominante-shell').addClass('d-none');
                $('#inicial-dominante-guardar-shell').addClass('d-none');
                $('#inicial-matriz-card').addClass('d-none');
                $('#matriz-inicial-shell').html(htmlMatrizPlaceholder('Este producto es simple. Captura la cantidad en el campo superior.'));
            })
            .fail(function (xhr) {
                resetEstadoInicial();
                AppUI.showMessage('Error', parseError(xhr), 'error');
            })
            .always(function () {
                AppUI.hideLoader();
            });
    }

    function mostrarPasoProductoBase() {
        $('#shell-entrada-step2').addClass('d-none');
        $('#shell-entrada-step1').removeClass('d-none');
        actualizarWizardSteps(1);
    }

    function mostrarPasoCaptura() {
        $('#shell-entrada-step1').addClass('d-none');
        $('#shell-entrada-step2').removeClass('d-none');
        actualizarWizardSteps(2);
    }

    function activarModoCapturaMasiva(activo) {
        estadoInicial.modoMasivo = Boolean(activo);
        $('#header-captura-unitaria').toggleClass('d-none', estadoInicial.modoMasivo);
        $('#form-inicial').toggleClass('d-none', estadoInicial.modoMasivo);
        $('#shell-captura-masiva').toggleClass('d-none', !estadoInicial.modoMasivo);
    }

    function actualizarUIReferenciaEntradaMulti() {
        const tipo = String($('#multi-tipo-entrada').val() || 'inventario_inicial');
        const $lbl = $('#lbl-multi-referencia');
        const esCompra = tipo === 'compra_remision' || tipo === 'compra_factura';
        const esFactura = tipo === 'compra_factura';
        if (tipo === 'compra_remision') {
            $lbl.text('Referencia remisión');
        } else if (tipo === 'compra_factura') {
            $lbl.text('Referencia factura');
        } else {
            $lbl.text('Referencia (opcional)');
        }

        $('#multi-prv-id').prop('required', esFactura);
        $('#multi-fecha-emision').prop('required', esCompra);
        $('#multi-iva-porcentaje').prop('disabled', !esFactura);
        if (!esFactura) {
            $('#multi-iva-porcentaje').val('0');
        } else if (Number($('#multi-iva-porcentaje').val() || 0) <= 0) {
            $('#multi-iva-porcentaje').val('16');
        }

        aplicarPresetUbicacionPorTipoEntrada();
        actualizarReferenciaNa();
        recalcularTotalesMulti();
    }

    function seleccionarOpcionPorCoincidencia($select, terminos = []) {
        const lista = (terminos || []).map((x) => normalizarTexto(x)).filter(Boolean);
        if (!$select || !$select.length || !lista.length) return false;

        let elegido = '';
        $select.find('option').each(function () {
            const texto = normalizarTexto($(this).text());
            const valor = String($(this).val() || '');
            if (!valor || !texto) return;
            if (lista.some((term) => texto.includes(term))) {
                elegido = valor;
                return false;
            }
        });

        if (!elegido) return false;
        $select.val(elegido);
        return true;
    }

    function aplicarPresetUbicacionPorTipoEntrada() {
        const tipo = String($('#multi-tipo-entrada').val() || '');
        if (tipo !== 'compra_remision' && tipo !== 'compra_factura') return;

        const $sucursal = $('#multi-scl-id');
        const aplicadoSucursal = seleccionarOpcionPorCoincidencia($sucursal, ['matriz comitan', 'casa matriz']);
        if (aplicadoSucursal) {
            llenarAlmacenesPorSucursal('#multi-alm-id', $sucursal.val(), false);
        }

        const terminosAlmacen = tipo === 'compra_factura'
            ? ['la i. suriana', 'productos con factura', 'factura']
            : ['i. suriana', 'remision', 'no factura'];

        seleccionarOpcionPorCoincidencia($('#multi-alm-id'), terminosAlmacen);
    }

    function actualizarReferenciaNa() {
        const na = $('#multi-referencia-na').is(':checked');
        const tipo = String($('#multi-tipo-entrada').val() || '');
        const esCompra = tipo === 'compra_remision' || tipo === 'compra_factura';
        if (!esCompra) {
            $('#multi-referencia-na').prop('checked', false);
            $('#multi-referencia').prop('readonly', false);
            return;
        }
        $('#multi-referencia').prop('readonly', na);
        if (na) {
            $('#multi-referencia').val('N/A');
        } else if (String($('#multi-referencia').val() || '').trim().toUpperCase() === 'N/A') {
            $('#multi-referencia').val('');
        }
    }

    function renderProductosSeleccionadosMulti() {
        const productos = estadoInicial.colaProductos || [];
        if (!productos.length) {
            $('#multi-productos-shell').html('<table class="table table-sm table-mini mb-0"><tbody><tr><td colspan="3" class="text-body-secondary">Selecciona productos para comenzar.</td></tr></tbody></table>');
            return;
        }

        const filas = productos.map(function (p) {
            const meta = estadoInicial.multiMeta[String(p.prd_id)] || {};
            const precio = Number(meta.precio_unitario || 0);
            return '' +
                '<tr>' +
                    '<td><div class="fw-semibold">' + escapeHtml((p.prd_codigo || '-') + ' - ' + (p.prd_nombre || '-')) + '</div></td>' +
                    '<td><input type="number" class="form-control form-control-sm js-multi-precio-producto" min="0" step="0.01" data-prd-id="' + p.prd_id + '" value="' + escapeHtml(precio.toFixed(2)) + '" aria-label="Precio unitario para ' + escapeHtml(p.prd_nombre || ('producto ' + p.prd_id)) + '"></td>' +
                    '<td><button type="button" class="btn btn-sm btn-outline-danger js-multi-quitar-producto" data-prd-id="' + p.prd_id + '"><i class="ti tabler-trash"></i>Quitar</button></td>' +
                '</tr>';
        }).join('');

        const html = '' +
            '<table class="table table-sm table-mini mb-0">' +
            '<thead><tr><th style="min-width:360px;">Producto</th><th style="width:220px;">Precio unitario</th><th style="width:120px;">Acción</th></tr></thead>' +
            '<tbody>' + filas + '</tbody></table>';

        $('#multi-productos-shell').html(html);
    }

    function construirCatalogoAtributosFiltros() {
        const mapa = {};
        Object.values(estadoInicial.multiMeta).forEach(function (meta) {
            if (!meta || meta.error || meta.prd_tipo !== 'variable') return;
            (meta.data?.lineas || []).forEach(function (linea) {
                const attrs = linea?.atributos || {};
                Object.keys(attrs).forEach(function (atrNombre) {
                    const key = String(atrNombre || '').trim();
                    if (!key) return;
                    if (!mapa[key]) mapa[key] = new Set();
                    const valor = String(attrs[atrNombre] || 'Sin valor').trim();
                    mapa[key].add(valor || 'Sin valor');
                });
            });
        });

        return Object.entries(mapa)
            .sort((a, b) => a[0].localeCompare(b[0], 'es', { sensitivity: 'base' }))
            .map(([atrNombre, valores]) => ({
                atrNombre,
                valores: Array.from(valores).sort((a, b) => String(a).localeCompare(String(b), 'es', { sensitivity: 'base' }))
            }));
    }

    function renderFiltrosAtributosMulti() {
        const catalogo = construirCatalogoAtributosFiltros();
        if (!catalogo.length) {
            estadoInicial.multiFiltrosAtributos = {};
            $('#multi-atributos-shell').html('<div class="text-body-secondary small">No hay atributos variables para filtrar en los productos seleccionados.</div>');
            return;
        }

        const filtrosDepurados = {};
        const html = catalogo.map(function (item) {
            if (!estadoInicial.multiFiltrosAtributos[item.atrNombre]) {
                estadoInicial.multiFiltrosAtributos[item.atrNombre] = new Set(item.valores);
            }
            const conjunto = new Set(
                item.valores.filter((valor) => estadoInicial.multiFiltrosAtributos[item.atrNombre]?.has(valor))
            );
            if (conjunto.size === 0) {
                item.valores.forEach((valor) => conjunto.add(valor));
            }
            filtrosDepurados[item.atrNombre] = conjunto;

            const checks = item.valores.map(function (valor) {
                const id = 'flt-atr-' + btoa(unescape(encodeURIComponent(item.atrNombre + '|' + valor))).replace(/[^a-zA-Z0-9]/g, '').slice(0, 20);
                const key = escapeHtml(item.atrNombre + '||' + valor);
                const marcado = conjunto.has(valor) ? 'checked' : '';
                return '<label class="attr-chip" for="' + id + '"><input type="checkbox" class="form-check-input js-atr-filter-value" id="' + id + '" data-atr="' + escapeHtml(item.atrNombre) + '" data-val="' + escapeHtml(valor) + '" value="' + key + '" ' + marcado + '> ' + escapeHtml(valor) + '</label>';
            }).join(' ');
            return '' +
                '<div class="mb-3">' +
                    '<div class="fw-semibold mb-2">' + escapeHtml(item.atrNombre) + '</div>' +
                    '<div class="d-flex flex-wrap gap-2">' + checks + '</div>' +
                '</div>';
        }).join('');

        estadoInicial.multiFiltrosAtributos = filtrosDepurados;
        $('#multi-atributos-shell').html(html);
    }

    function sincronizarFiltrosAtributosDesdeUI() {
        const filtros = {};
        $('#multi-atributos-shell .js-atr-filter-value').each(function () {
            if (!$(this).is(':checked')) return;
            const atr = String($(this).data('atr') || '').trim();
            const val = String($(this).data('val') || '').trim();
            if (!atr || !val) return;
            if (!filtros[atr]) filtros[atr] = new Set();
            filtros[atr].add(val);
        });
        estadoInicial.multiFiltrosAtributos = filtros;
    }

    function lineaCumpleFiltros(linea) {
        const filtros = estadoInicial.multiFiltrosAtributos || {};
        const attrs = linea?.atributos || {};
        const claves = Object.keys(filtros);
        if (!claves.length) return true;
        for (let i = 0; i < claves.length; i += 1) {
            const atr = claves[i];
            const setValores = filtros[atr];
            if (!setValores || setValores.size === 0) continue;
            const valorLinea = String(attrs[atr] || 'Sin valor').trim();
            if (!setValores.has(valorLinea)) return false;
        }
        return true;
    }

    function capturarCantidadesMultiGlobal() {
        $('#multi-grid-shell .js-multi-cantidad').each(function () {
            const prdId = String($(this).data('prd-id') || '');
            const pskId = String($(this).data('min-psk-id') || '');
            if (!prdId || !pskId) return;
            const meta = estadoInicial.multiMeta[prdId];
            if (!meta) return;
            meta.cantidades = meta.cantidades || {};
            meta.cantidades[pskId] = String($(this).val() || '');
        });
    }

    function recalcularTotalesMulti() {
        const descuentoTipo = String($('#multi-descuento-tipo').val() || 'ninguno');
        const descuentoValor = Number($('#multi-descuento-valor').val() || 0);
        const fleteTotal = Number($('#multi-flete-total').val() || 0);
        const ivaPorcentaje = Number($('#multi-iva-porcentaje').val() || 0);
        const tipoEntrada = String($('#multi-tipo-entrada').val() || 'inventario_inicial');
        const aplicaIva = tipoEntrada === 'compra_factura';

        let subtotal = 0;
        let piezas = 0;
        $('#multi-grid-shell .js-multi-cantidad').each(function () {
            const cantidad = Number($(this).val() || 0);
            if (!(cantidad > 0)) return;
            const prdId = String($(this).data('prd-id') || '');
            const meta = estadoInicial.multiMeta[prdId] || {};
            const precio = Number(meta.precio_unitario || 0);
            subtotal += cantidad * precio;
            piezas += cantidad;
        });

        subtotal = Number(subtotal.toFixed(2));
        let descuentoMonto = 0;
        if (descuentoTipo === 'porcentaje') {
            descuentoMonto = subtotal * (Math.min(100, Math.max(0, descuentoValor)) / 100);
        } else if (descuentoTipo === 'importe') {
            descuentoMonto = Math.min(subtotal, Math.max(0, descuentoValor));
        }
        descuentoMonto = Number(descuentoMonto.toFixed(2));
        const base = Number(Math.max(0, subtotal - descuentoMonto + Math.max(0, fleteTotal)).toFixed(2));
        const ivaMonto = aplicaIva ? Number((base * (Math.max(0, ivaPorcentaje) / 100)).toFixed(2)) : 0;
        const total = Number((base + ivaMonto).toFixed(2));

        $('#multi-total-subtotal').text(toMoney(subtotal));
        $('#multi-total-descuento').text(toMoney(descuentoMonto));
        $('#multi-total-flete').text(toMoney(Math.max(0, fleteTotal)));
        $('#multi-total-iva').text(toMoney(ivaMonto));
        $('#multi-total-piezas').text(Number(piezas).toLocaleString('es-MX'));
        $('#multi-total-general').text(toMoney(total));

        return { subtotal, descuentoMonto, fleteTotal: Math.max(0, fleteTotal), ivaPorcentaje: Math.max(0, ivaPorcentaje), ivaMonto, total, piezas };
    }

    function actualizarUIRecibirReferencia() {
        const tipo = String($('#recibir-tipo-entrada').val() || 'compra_factura');
        const esCompra = tipo === 'compra_remision' || tipo === 'compra_factura';
        const esFactura = tipo === 'compra_factura';
        const na = $('#recibir-referencia-na').is(':checked');

        $('#recibir-referencia-na').prop('disabled', !esCompra);
        $('#recibir-referencia').prop('readonly', esCompra && na);
        if (!esCompra) {
            $('#recibir-referencia-na').prop('checked', false);
            $('#recibir-referencia').prop('readonly', false);
        } else if (na) {
            $('#recibir-referencia').val('N/A');
        } else if (String($('#recibir-referencia').val() || '').trim().toUpperCase() === 'N/A') {
            $('#recibir-referencia').val('');
        }

        $('#recibir-prv-id').prop('required', esFactura);
        $('#recibir-fecha-emision').prop('required', esCompra);
        $('#recibir-incluir-iva').prop('disabled', !esFactura);
        if (!esFactura) {
            $('#recibir-incluir-iva').prop('checked', false);
            $('#recibir-iva-porcentaje').val('0').prop('disabled', true);
        } else {
            $('#recibir-incluir-iva').prop('checked', true);
            $('#recibir-iva-porcentaje').prop('disabled', false);
            if (Number($('#recibir-iva-porcentaje').val() || 0) <= 0) {
                $('#recibir-iva-porcentaje').val('16');
            }
        }
    }

    function aplicarPresetRecibirMercancia() {
        const tipo = String($('#recibir-tipo-entrada').val() || '');
        if (tipo !== 'compra_remision' && tipo !== 'compra_factura') return;

        const $sucursal = $('#recibir-scl-id');
        const aplicadoSucursal = seleccionarOpcionPorCoincidencia($sucursal, ['matriz comitan', 'casa matriz']);
        if (aplicadoSucursal) {
            llenarAlmacenesPorSucursal('#recibir-alm-id', $sucursal.val(), false);
        }

        const terminosAlmacen = tipo === 'compra_factura'
            ? ['la i. suriana', 'productos con factura', 'factura']
            : ['i. suriana', 'remision', 'no factura'];
        seleccionarOpcionPorCoincidencia($('#recibir-alm-id'), terminosAlmacen);
    }

    function lineaCumpleFiltrosRecibir(linea) {
        const filtros = recibirState.filtrosAtributos || {};
        const attrs = linea?.atributos || {};
        const claves = Object.keys(filtros);
        if (!claves.length) return true;
        for (let i = 0; i < claves.length; i += 1) {
            const atr = claves[i];
            const setValores = filtros[atr];
            if (!setValores || setValores.size === 0) continue;
            const valorLinea = String(attrs[atr] || 'Sin valor').trim();
            if (!setValores.has(valorLinea)) return false;
        }
        return true;
    }

    function construirMatrizRecibirParaDominante(meta, atrId) {
        const lineas = meta.data?.lineas || [];
        const atributos = meta.data?.atributos || [];
        const atrDominanteNombre = nombreAtributoPorId(meta.data, atrId);
        if (!atrDominanteNombre) return { filas: {}, ordenFilas: [], columnas: [] };

        const attrsCol = atributos.filter((a) => a.atr_nombre !== atrDominanteNombre);
        const filas = {};
        const ordenFilas = [];
        const columnas = [];
        const columnasMap = {};

        lineas.forEach((linea) => {
            if (!lineaCumpleFiltrosRecibir(linea)) return;
            const filaValor = String(linea.atributos?.[atrDominanteNombre] || 'Sin valor').trim();
            if (!filas[filaValor]) {
                filas[filaValor] = {};
                ordenFilas.push(filaValor);
            }

            const pares = attrsCol
                .map((a) => ({
                    nombre: String(a.atr_nombre || 'Variable'),
                    valor: String(linea.atributos?.[a.atr_nombre] || '-').trim(),
                }))
                .filter((p) => p.valor && p.valor !== '-');

            const pivote = pares[0] || null;
            const keyCol = pares.length ? pares.map((p) => p.nombre + ':' + p.valor).join('||') : '__base__';
            const labelCol = pivote
                ? pivote.valor + (pares.length > 1 ? ' (' + pares.slice(1).map((p) => p.valor).join(' / ') + ')' : '')
                : 'Existencia';
            const groupCol = pivote ? pivote.nombre : 'Existencia';

            if (!columnasMap[keyCol]) {
                columnasMap[keyCol] = true;
                columnas.push({ key: keyCol, label: labelCol, group: groupCol });
            }
            filas[filaValor][keyCol] = linea;
        });

        return { filas, ordenFilas, columnas };
    }

    function renderProductosRecibirMercancia() {
        const productos = Object.values(recibirState.productos || {});
        if (!productos.length) {
            $('#recibir-productos-shell').html('<table class="table table-sm table-mini mb-0"><tbody><tr><td colspan="2" class="text-body-secondary">Sin productos seleccionados.</td></tr></tbody></table>');
            return;
        }
        const rows = productos.map((p) => {
            const label = (p.prd_codigo || '-') + ' - ' + (p.prd_nombre || '-');
            return '<tr>' +
                '<td class="fw-semibold">' + escapeHtml(label) + '</td>' +
                '<td><button type="button" class="btn btn-sm btn-outline-danger js-recibir-quitar-producto" data-prd-id="' + p.prd_id + '"><i class="ti tabler-trash"></i></button></td>' +
                '</tr>';
        }).join('');
        $('#recibir-productos-shell').html('<table class="table table-sm table-mini mb-0"><thead><tr><th style="min-width:360px;">Producto</th><th style="width:120px;">Acción</th></tr></thead><tbody>' + rows + '</tbody></table>');
    }

    function renderFiltrosRecibirMercancia() {
        const mapa = {};
        Object.values(recibirState.meta || {}).forEach((meta) => {
            if (!meta || meta.error || meta.prd_tipo !== 'variable') return;
            (meta.data?.lineas || []).forEach((linea) => {
                const attrs = linea?.atributos || {};
                Object.keys(attrs).forEach((atrNombre) => {
                    const key = String(atrNombre || '').trim();
                    if (!key) return;
                    if (!mapa[key]) mapa[key] = new Set();
                    mapa[key].add(String(attrs[atrNombre] || 'Sin valor').trim() || 'Sin valor');
                });
            });
        });
        const catalogo = Object.entries(mapa)
            .sort((a, b) => a[0].localeCompare(b[0], 'es', { sensitivity: 'base' }))
            .map(([atrNombre, valores]) => ({
                atrNombre,
                valores: Array.from(valores).sort((a, b) => String(a).localeCompare(String(b), 'es', { sensitivity: 'base' })),
            }));

        if (!catalogo.length) {
            recibirState.filtrosAtributos = {};
            $('#recibir-atributos-shell').html('<div class="text-body-secondary small">No hay atributos variables para filtrar en los productos seleccionados.</div>');
            return;
        }

        const filtrosDepurados = {};
        const html = catalogo.map((item) => {
            if (!recibirState.filtrosAtributos[item.atrNombre]) {
                recibirState.filtrosAtributos[item.atrNombre] = new Set(item.valores);
            }
            const conjunto = new Set(item.valores.filter((v) => recibirState.filtrosAtributos[item.atrNombre]?.has(v)));
            if (conjunto.size === 0) item.valores.forEach((v) => conjunto.add(v));
            filtrosDepurados[item.atrNombre] = conjunto;
            const chips = item.valores.map((valor) => {
                const marcado = conjunto.has(valor) ? 'checked' : '';
                return '<label class="attr-chip"><input type="checkbox" class="form-check-input js-recibir-atr-filter" data-atr="' + escapeHtml(item.atrNombre) + '" data-val="' + escapeHtml(valor) + '" ' + marcado + '> ' + escapeHtml(valor) + '</label>';
            }).join(' ');
            return '<div class="mb-3"><div class="fw-semibold mb-2">' + escapeHtml(item.atrNombre) + '</div><div class="d-flex flex-wrap gap-2">' + chips + '</div></div>';
        }).join('');

        recibirState.filtrosAtributos = filtrosDepurados;
        $('#recibir-atributos-shell').html(html);
    }

    function cargarDominantesRecibirMercancia() {
        const mapa = {};
        Object.values(recibirState.meta || {}).forEach((meta) => {
            if (!meta || meta.error || meta.prd_tipo !== 'variable') return;
            (meta.data?.atributos || []).forEach((atr) => {
                mapa[String(atr.atr_id)] = atr.atr_nombre;
            });
        });
        const options = Object.entries(mapa)
            .sort((a, b) => String(a[1]).localeCompare(String(b[1]), 'es', { sensitivity: 'base' }))
            .map(([id, nombre]) => '<option value="' + id + '">' + escapeHtml(nombre) + '</option>');
        const $sel = $('#recibir-dominante-global');
        const previo = String($sel.val() || '');
        $sel.html('<option value="">Selecciona dominante</option>' + options.join(''));
        if (previo && mapa[previo]) {
            $sel.val(previo);
        } else if (options.length) {
            $sel.val(Object.keys(mapa)[0]);
        }
    }

    function actualizarBotonRestaurarRecibir() {
        const totalExcluidas = Object.keys(recibirState.filasExcluidas || {}).length;
        const totalProductosQuitados = Object.keys(recibirState.productosQuitados || {}).length;
        const total = totalExcluidas + totalProductosQuitados;
        $('#btn-recibir-restaurar-filas').prop('disabled', total === 0);
    }

    function renderMatrizRecibirMercancia() {
        const productos = Object.values(recibirState.productos || {});
        const atrId = Number($('#recibir-dominante-global').val() || 0);
        if (!productos.length) {
            $('#recibir-grid-shell').html('<table class="table table-sm table-mini mb-0"><tbody><tr><td class="text-body-secondary">Sin productos seleccionados. Usa "Buscar artículos".</td></tr></tbody></table>');
            recalcularTotalesRecibirMercancia();
            return;
        }

        const globalColsMap = {};
        const globalCols = [];
        const filas = [];
        let nombreDominante = 'Dominante';

        productos.forEach((p) => {
            const meta = recibirState.meta[String(p.prd_id)];
            if (!meta || meta.error) return;

            if (meta.prd_tipo === 'simple') {
                const linea = (meta.data?.lineas || [])[0] || null;
                if (linea) {
                    const rowKey = String(p.prd_id) + '||Estandar';
                    if (recibirState.filasExcluidas[rowKey]) {
                        return;
                    }
                    const costoDefault = costoBaseProducto(meta.data?.producto) || costoBaseProducto(p);
                    filas.push({
                        prd_id: p.prd_id,
                        producto: (p.prd_codigo || '-') + ' - ' + (p.prd_nombre || '-'),
                        dominante: 'Estándar',
                        celdas: { '__simple__': linea },
                        tipo: 'normal',
                        row_key: rowKey,
                        default_costo: costoDefault,
                    });
                    if (!globalColsMap['__simple__']) {
                        globalColsMap['__simple__'] = true;
                        globalCols.push({ key: '__simple__', label: 'Existencia', group: 'Existencia' });
                    }
                }
                return;
            }

            if (!productoAdmiteDominante(meta, atrId)) {
                filas.push({
                    prd_id: p.prd_id,
                    producto: (p.prd_codigo || '-') + ' - ' + (p.prd_nombre || '-'),
                    dominante: '-',
                    tipo: 'error',
                    mensaje: 'No aplica al dominante seleccionado.',
                });
                return;
            }

            nombreDominante = nombreAtributoPorId(meta.data, atrId) || nombreDominante;
            const matriz = construirMatrizRecibirParaDominante(meta, atrId);
            matriz.columnas.forEach((c) => {
                if (!globalColsMap[c.key]) {
                    globalColsMap[c.key] = true;
                    globalCols.push(c);
                }
            });
            (matriz.ordenFilas || []).forEach((fv) => {
                const rowKey = String(p.prd_id) + '||' + String(fv);
                if (recibirState.filasExcluidas[rowKey]) return;
                const costoDefault = costoBaseProducto(meta.data?.producto) || costoBaseProducto(p);
                filas.push({
                    prd_id: p.prd_id,
                    producto: (p.prd_codigo || '-') + ' - ' + (p.prd_nombre || '-'),
                    dominante: fv,
                    celdas: matriz.filas[fv] || {},
                    tipo: 'normal',
                    row_key: rowKey,
                    default_costo: costoDefault,
                });
            });
        });

        if (!globalCols.length) {
            $('#recibir-grid-shell').html('<table class="table table-sm table-mini mb-0"><tbody><tr><td class="text-body-secondary">No hay columnas para el dominante seleccionado.</td></tr></tbody></table>');
            recalcularTotalesRecibirMercancia();
            return;
        }

        globalCols.sort((a, b) => {
            const ga = String(a.group || 'Variable');
            const gb = String(b.group || 'Variable');
            const cmpG = ga.localeCompare(gb, 'es', { sensitivity: 'base' });
            if (cmpG !== 0) return cmpG;
            return String(a.label || '').localeCompare(String(b.label || ''), 'es', { sensitivity: 'base' });
        });

        const gruposHeader = [];
        let actual = null;
        globalCols.forEach((c) => {
            const g = String(c.group || 'Variable');
            if (!actual || actual.group !== g) {
                actual = { group: g, count: 1 };
                gruposHeader.push(actual);
            } else {
                actual.count += 1;
            }
        });

        const mapaRowspan = {};
        filas.forEach((r) => { mapaRowspan[r.prd_id] = (mapaRowspan[r.prd_id] || 0) + 1; });
        const thGroups = gruposHeader.map((g) => '<th scope="colgroup" colspan="' + g.count + '">' + escapeHtml(g.group) + '</th>').join('');
        const thCols = globalCols.map((c) => '<th scope="col" class="multi-col-head">' + escapeHtml(c.label) + '</th>').join('');

        let html = '<table class="table table-sm table-mini mb-0 multi-grid-table" role="grid" aria-describedby="recibir-grid-help"><thead>' +
            '<tr><th scope="col" rowspan="2" style="min-width:280px;">Producto</th><th scope="col" rowspan="2" style="min-width:160px;">' + escapeHtml(nombreDominante) + '</th>' + thGroups + '<th scope="col" rowspan="2" style="width:150px;">Costo unitario</th><th scope="col" rowspan="2" style="width:120px;">Acción</th></tr>' +
            '<tr>' + thCols + '</tr></thead><tbody>';

        const rendered = {};
        let rowIndex = 0;
        filas.forEach((f) => {
            const first = !rendered[f.prd_id];
            const tdProducto = first
                ? '<th scope="rowgroup" rowspan="' + (mapaRowspan[f.prd_id] || 1) + '" class="fw-semibold align-top">' + escapeHtml(f.producto) + '</th>'
                : '';
            rendered[f.prd_id] = true;

            if (f.tipo === 'error') {
                html += '<tr>' + tdProducto + '<td class="text-body-secondary">-</td><td colspan="' + (globalCols.length + 2) + '" class="text-body-secondary">' + escapeHtml(f.mensaje || '-') + '</td></tr>';
                return;
            }

            const skuIds = Object.values(f.celdas || {})
                .map((linea) => Number(linea?.min_psk_id || 0))
                .filter((id) => id > 0);

            const celdas = globalCols.map((col, colIndex) => {
                const linea = f.celdas?.[col.key] || null;
                if (!linea) {
                    return '<td class="multi-td-na"><input class="form-control form-control-sm multi-cell-na" type="text" value="N/A" readonly tabindex="-1"></td>';
                }
                const skuId = String(linea.min_psk_id || '');
                const value = String(recibirState.cantidades[skuId] || '');
                return '<td class="multi-td-ok"><input class="form-control form-control-sm multi-cell-ok js-recibir-grid-cantidad" type="number" min="0" step="0.01" data-prd-id="' + f.prd_id + '" data-row-key="' + escapeHtml(f.row_key || '') + '" data-min-psk-id="' + skuId + '" data-grid-r="' + rowIndex + '" data-grid-c="' + colIndex + '" value="' + escapeHtml(value) + '"></td>';
            }).join('');
            const rowKeyCosto = String(f.row_key || '');
            const costoExistente = Number(recibirState.costosFila[rowKeyCosto] ?? 0);
            const fueEditado = Boolean(recibirState.costosFilaEditados[rowKeyCosto]);
            if (recibirState.costosFila[rowKeyCosto] === undefined || (!fueEditado && costoExistente <= 0 && Number(f.default_costo || 0) > 0)) {
                recibirState.costosFila[rowKeyCosto] = Number(f.default_costo || 0);
            }
            const costoFila = Number(recibirState.costosFila[rowKeyCosto] || 0);
            const inputCostoFila = '<input type="number" class="form-control form-control-sm text-end js-recibir-row-costo" min="0" step="0.01" data-row-key="' + escapeHtml(f.row_key || '') + '" value="' + escapeHtml(costoFila.toFixed(2)) + '">';
            const btnFila = '<button type="button" class="btn btn-sm btn-outline-danger js-recibir-quitar-fila" data-prd-id="' + f.prd_id + '" data-row-key="' + escapeHtml(f.row_key || '') + '" data-skus="' + escapeHtml(skuIds.join(',')) + '" title="Quitar fila"><i class="ti tabler-trash"></i></button>';
            const btnProducto = first
                ? '<button type="button" class="btn btn-sm btn-outline-secondary js-recibir-quitar-producto-bloque ms-1" data-prd-id="' + f.prd_id + '" title="Quitar producto"><i class="ti tabler-package-off"></i></button>'
                : '';
            html += '<tr>' + tdProducto + '<th scope="row" class="fw-semibold">' + escapeHtml(f.dominante) + '</th>' + celdas + '<td>' + inputCostoFila + '</td><td class="text-nowrap">' + btnFila + btnProducto + '</td></tr>';
            rowIndex += 1;
        });

        html += '</tbody></table>';
        $('#recibir-grid-shell').html(html);
        actualizarBotonRestaurarRecibir();
        recalcularTotalesRecibirMercancia();
    }

    function recalcularTotalesRecibirMercancia() {
        const descuentoTipo = String($('#recibir-descuento-tipo').val() || 'ninguno');
        const descuentoValor = Number($('#recibir-descuento-valor').val() || 0);
        const flete = Number($('#recibir-flete-total').val() || 0);
        const incluirIva = $('#recibir-incluir-iva').is(':checked');
        const ivaPorcentaje = incluirIva ? Number($('#recibir-iva-porcentaje').val() || 0) : 0;

        $('#recibir-grid-shell .js-recibir-row-costo').each(function () {
            const rowKey = String($(this).data('row-key') || '');
            if (!rowKey) return;
            recibirState.costosFila[rowKey] = Number($(this).val() || 0);
        });

        let piezas = 0;
        let subtotal = 0;
        $('#recibir-grid-shell .js-recibir-grid-cantidad').each(function () {
            const qty = Number($(this).val() || 0);
            const rowKey = String($(this).data('row-key') || '');
            const sId = String($(this).data('min-psk-id') || '');
            if (!sId) return;
            recibirState.cantidades[sId] = String($(this).val() || '');
            if (!(qty > 0)) return;
            const costo = Number(recibirState.costosFila[rowKey] || 0);
            piezas += qty;
            subtotal += qty * costo;
        });

        subtotal = Number(subtotal.toFixed(2));
        let descuento = 0;
        if (descuentoTipo === 'porcentaje') descuento = subtotal * (Math.min(100, Math.max(0, descuentoValor)) / 100);
        if (descuentoTipo === 'importe') descuento = Math.min(subtotal, Math.max(0, descuentoValor));
        descuento = Number(descuento.toFixed(2));
        const base = Number(Math.max(0, subtotal - descuento + Math.max(0, flete)).toFixed(2));
        const iva = Number((base * (Math.max(0, ivaPorcentaje) / 100)).toFixed(2));
        const total = Number((base + iva).toFixed(2));

        $('#recibir-total-articulos').text(Number(piezas).toLocaleString('es-MX'));
        $('#recibir-total-subtotal').text(toMoney(subtotal));
        $('#recibir-total-descuento').text(toMoney(descuento));
        $('#recibir-total-flete').text(toMoney(Math.max(0, flete)));
        $('#recibir-total-iva').text(toMoney(iva));
        $('#recibir-total-general').text(toMoney(total));
        return { piezas, subtotal, descuento, flete: Math.max(0, flete), ivaPorcentaje: Math.max(0, ivaPorcentaje), iva, total };
    }

    function cargarTablaBuscarRecibir() {
        if (recibirState.tablaModalInicializada) {
            $('#tbl-recibir-buscar-productos').DataTable().ajax.reload();
            return;
        }

        $('#tbl-recibir-buscar-productos').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 10,
            searching: false,
            lengthMenu: [10, 25, 50],
            order: [[2, 'asc']],
            ajax: {
                url: rutas.productosBaseData,
                data: function (d) {
                    d.prd_mrc_id = $('#recibir-buscar-marca').val();
                    d.prd_mdl_id = $('#recibir-buscar-modelo').val();
                    d.prd_lna_id = $('#recibir-buscar-linea').val();
                    d.prd_ctg_id = $('#recibir-buscar-categoria').val();
                    if ($('#recibir-buscar-texto').val()) {
                        d.search = d.search || {};
                        d.search.value = $('#recibir-buscar-texto').val();
                    }
                }
            },
            drawCallback: function () {
                $('#tbl-recibir-buscar-productos tbody input.js-recibir-modal-chk').each(function () {
                    const id = String($(this).data('prd-id') || '');
                    $(this).prop('checked', Boolean(recibirState.modalSeleccion[id]));
                });
            },
            columns: [
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function (row) {
                        const payload = encodeURIComponent(JSON.stringify({
                            prd_id: row.prd_id,
                            prd_tipo: row.prd_tipo,
                            prd_codigo: row.prd_codigo,
                            prd_nombre: row.prd_nombre,
                            prd_costo: row.prd_costo ?? 0,
                            prd_precio_base: row.prd_precio_base ?? 0
                        }));
                        return '<input type="checkbox" class="js-recibir-modal-chk" data-prd-id="' + row.prd_id + '" data-payload="' + payload + '">';
                    }
                },
                { data: 'prd_codigo' },
                { data: 'prd_nombre' },
                { data: 'prd_tipo', render: (v) => (v === 'variable' ? 'Variable' : 'Simple') },
                { data: 'marca_nombre', defaultContent: '-' },
                { data: 'linea_nombre', defaultContent: '-' },
                { data: 'skus_activos' }
            ]
        });

        recibirState.tablaModalInicializada = true;
    }

    function sincronizarFiltrosRecibirDesdeUI() {
        const filtros = {};
        $('#recibir-atributos-shell .js-recibir-atr-filter').each(function () {
            if (!$(this).is(':checked')) return;
            const atr = String($(this).data('atr') || '').trim();
            const val = String($(this).data('val') || '').trim();
            if (!atr || !val) return;
            if (!filtros[atr]) filtros[atr] = new Set();
            filtros[atr].add(val);
        });
        recibirState.filtrosAtributos = filtros;
    }

    function cargarProductosRecibirSeleccionados() {
        const productos = Object.values(recibirState.productos || {});
        const sucursalId = Number($('#recibir-scl-id').val() || 0);
        recibirState.meta = {};
        if (!productos.length) {
            renderProductosRecibirMercancia();
            renderFiltrosRecibirMercancia();
            renderMatrizRecibirMercancia();
            return Promise.resolve();
        }

        return new Promise((resolve) => {
            let pendientes = productos.length;
            AppUI.showLoader();
            productos.forEach((p) => {
                $.getJSON(rutas.matrizProducto(p.prd_id), { min_scl_id: sucursalId || '' })
                    .done((resp) => {
                        const data = resp?.data || {};
                        recibirState.meta[String(p.prd_id)] = {
                            prd_id: p.prd_id,
                            prd_tipo: data?.producto?.prd_tipo || p.prd_tipo || 'simple',
                            data,
                        };
                    })
                    .fail((xhr) => {
                        recibirState.meta[String(p.prd_id)] = {
                            prd_id: p.prd_id,
                            prd_tipo: p.prd_tipo || 'simple',
                            error: parseError(xhr),
                        };
                    })
                    .always(() => {
                        pendientes -= 1;
                        if (pendientes > 0) return;
                        AppUI.hideLoader();
                        renderProductosRecibirMercancia();
                        cargarDominantesRecibirMercancia();
                        renderFiltrosRecibirMercancia();
                        renderMatrizRecibirMercancia();
                        resolve();
                    });
            });
        });
    }

    function productoAdmiteDominante(meta, atrId) {
        if (!meta || meta.prd_tipo !== 'variable' || !atrId) return false;
        return (meta.data?.atributos || []).some((a) => String(a.atr_id) === String(atrId));
    }

    function construirMatrizProductoParaDominante(meta, atrId) {
        const lineas = meta.data?.lineas || [];
        const atributos = meta.data?.atributos || [];
        const atrDominanteNombre = nombreAtributoPorId(meta.data, atrId);
        if (!atrDominanteNombre) {
            return { filas: [], columnas: [] };
        }

        const attrsCol = atributos.filter((a) => a.atr_nombre !== atrDominanteNombre);
        const filas = {};
        const ordenFilas = [];
        const columnasMap = {};
        const columnas = [];

        lineas.forEach(function (linea) {
            if (!lineaCumpleFiltros(linea)) return;

            const filaValor = String(linea.atributos?.[atrDominanteNombre] || 'Sin valor').trim();
            if (!filas[filaValor]) {
                filas[filaValor] = {};
                ordenFilas.push(filaValor);
            }

            const pares = attrsCol
                .map((a) => ({
                    nombre: String(a.atr_nombre || 'Variable'),
                    valor: String(linea.atributos?.[a.atr_nombre] || '-').trim(),
                }))
                .filter((p) => p.valor && p.valor !== '-');

            const pivote = pares[0] || null;
            const keyCol = pares.length
                ? pares.map((p) => p.nombre + ':' + p.valor).join('||')
                : '__base__';
            const labelCol = pivote
                ? pivote.valor + (pares.length > 1 ? ' (' + pares.slice(1).map((p) => p.valor).join(' / ') + ')' : '')
                : 'Existencia';
            const groupCol = pivote ? pivote.nombre : 'Existencia';

            if (!columnasMap[keyCol]) {
                columnasMap[keyCol] = labelCol;
                columnas.push({ key: keyCol, label: labelCol, group: groupCol });
            }
            filas[filaValor][keyCol] = linea;
        });

        return { filas, ordenFilas, columnas };
    }

    function renderTablaMasivaMultiGlobal() {
        const productos = estadoInicial.colaProductos || [];
        if (!productos.length) {
            $('#multi-grid-shell').html('<table class="table table-sm table-mini mb-0"><tbody><tr><td class="text-body-secondary">Selecciona productos para cargar líneas.</td></tr></tbody></table>');
            return;
        }

        const atrId = Number($('#multi-dominante-global').val() || 0);
        const globalColumnasMap = {};
        const globalColumnas = [];
        const filasRender = [];
        let nombreDominante = 'Dominante';

        productos.forEach(function (p) {
            const meta = estadoInicial.multiMeta[String(p.prd_id)];
            if (!meta || meta.error) {
                filasRender.push({ tipo: 'error', prd_id: p.prd_id, producto: (p.prd_codigo || '-') + ' - ' + (p.prd_nombre || '-'), error: meta?.error || 'No disponible' });
                return;
            }

            if (meta.prd_tipo === 'simple') {
                const linea = (meta.data?.lineas || [])[0] || null;
                const rowId = 'Estándar';
                filasRender.push({
                    tipo: 'normal',
                    prd_id: p.prd_id,
                    producto: (p.prd_codigo || '-') + ' - ' + (p.prd_nombre || '-'),
                    dominante: rowId,
                    celdas: linea?.min_psk_id ? { '__simple__': linea } : {},
                    simple: true,
                });
                if (!globalColumnasMap['__simple__']) {
                    globalColumnasMap['__simple__'] = true;
                    globalColumnas.push({ key: '__simple__', label: 'Existencia', group: 'Existencia' });
                }
                return;
            }

            if (!productoAdmiteDominante(meta, atrId)) {
                filasRender.push({
                    tipo: 'sin_dominante',
                    prd_id: p.prd_id,
                    producto: (p.prd_codigo || '-') + ' - ' + (p.prd_nombre || '-'),
                    mensaje: 'No aplica al dominante seleccionado.'
                });
                return;
            }

            nombreDominante = nombreAtributoPorId(meta.data, atrId) || nombreDominante;
            const matriz = construirMatrizProductoParaDominante(meta, atrId);
            matriz.columnas.forEach(function (c) {
                if (!globalColumnasMap[c.key]) {
                    globalColumnasMap[c.key] = true;
                    globalColumnas.push({ key: c.key, label: c.label, group: c.group || 'Variable' });
                }
            });

            (matriz.ordenFilas || []).forEach(function (filaValor) {
                filasRender.push({
                    tipo: 'normal',
                    prd_id: p.prd_id,
                    producto: (p.prd_codigo || '-') + ' - ' + (p.prd_nombre || '-'),
                    dominante: filaValor,
                    celdas: matriz.filas[filaValor] || {},
                    simple: false,
                });
            });
        });

        if (!globalColumnas.length) {
            $('#multi-grid-shell').html('<table class="table table-sm table-mini mb-0"><tbody><tr><td class="text-body-secondary">No hay columnas para el dominante seleccionado.</td></tr></tbody></table>');
            return;
        }

        const prioridadGrupo = {
            'Talla': 1,
            'Tamanio': 1,
            'Tamaño': 1,
            'Medida': 2,
            'Ancho': 3,
            'Largo': 3,
            'Material': 4,
            'Existencia': 99,
        };
        globalColumnas.sort(function (a, b) {
            const ga = String(a.group || 'Variable');
            const gb = String(b.group || 'Variable');
            const pa = prioridadGrupo[ga] || 50;
            const pb = prioridadGrupo[gb] || 50;
            if (pa !== pb) return pa - pb;
            const cg = ga.localeCompare(gb, 'es', { sensitivity: 'base' });
            if (cg !== 0) return cg;
            return String(a.label || '').localeCompare(String(b.label || ''), 'es', { sensitivity: 'base' });
        });

        const mapaRowspan = {};
        filasRender.forEach((r) => {
            if (r.tipo === 'normal' || r.tipo === 'sin_dominante' || r.tipo === 'error') {
                mapaRowspan[r.prd_id] = (mapaRowspan[r.prd_id] || 0) + 1;
            }
        });

        const gruposHeader = [];
        let grupoActual = null;
        globalColumnas.forEach((c) => {
            const group = String(c.group || 'Variable');
            if (!grupoActual || grupoActual.group !== group) {
                grupoActual = { group, count: 1 };
                gruposHeader.push(grupoActual);
            } else {
                grupoActual.count += 1;
            }
        });

        const thGroups = gruposHeader.map((g) => '<th scope="colgroup" colspan="' + g.count + '">' + escapeHtml(g.group) + '</th>').join('');
        const conteoPorColumna = {};
        globalColumnas.forEach((c) => { conteoPorColumna[c.key] = 0; });
        filasRender.forEach(function (fila) {
            if (fila.tipo !== 'normal') return;
            globalColumnas.forEach(function (col) {
                if (fila.celdas?.[col.key]) {
                    conteoPorColumna[col.key] = (conteoPorColumna[col.key] || 0) + 1;
                }
            });
        });

        const thCols = globalColumnas.map((c, idx) => {
            const aplica = Number(conteoPorColumna[c.key] || 0);
            const clase = aplica > 0 ? 'multi-col-ok' : 'multi-col-na';
            const detalle = aplica > 0 ? (aplica + ' filas aplicables') : 'Sin aplicación';
            return '<th scope="col" class="multi-col-head ' + clase + ' col-head-' + idx + '">' + escapeHtml(c.label) + '<small>' + escapeHtml(detalle) + '</small></th>';
        }).join('');
        let html = '' +
            '<table class="table table-sm table-mini mb-0 multi-grid-table" role="grid" aria-describedby="multi-grid-help">' +
            '<caption class="visually-hidden">Matriz global de captura de entradas por producto y ' + escapeHtml(nombreDominante) + '</caption>' +
            '<thead>' +
                '<tr><th scope="col" rowspan="2" style="min-width:300px;">Producto</th><th scope="col" rowspan="2" style="min-width:180px;">' + escapeHtml(nombreDominante) + '</th>' + thGroups + '</tr>' +
                '<tr>' + thCols + '</tr>' +
            '</thead><tbody>';

        const renderedProduct = {};
        let rowIndex = 0;
        filasRender.forEach(function (fila) {
            const firstProductRow = !renderedProduct[fila.prd_id];
            const tdProducto = firstProductRow
                ? '<th scope="rowgroup" rowspan="' + (mapaRowspan[fila.prd_id] || 1) + '" class="fw-semibold align-top">' + escapeHtml(fila.producto) + '</th>'
                : '';
            renderedProduct[fila.prd_id] = true;

            if (fila.tipo === 'error' || fila.tipo === 'sin_dominante') {
                html += '<tr>' + tdProducto + '<td class="text-body-secondary">-</td><td colspan="' + globalColumnas.length + '" class="text-body-secondary">' + escapeHtml(fila.mensaje || fila.error || '-') + '</td></tr>';
                return;
            }

            const celdasHtml = globalColumnas.map(function (col, colIndex) {
                const skuLinea = fila.celdas?.[col.key] || null;
                if (!skuLinea) {
                    const ariaNa = 'No aplica. Producto ' + fila.producto + ', ' + nombreDominante + ' ' + fila.dominante + ', columna ' + col.label;
                    return '<td class="multi-td-na col-cell-' + colIndex + '"><input class="form-control form-control-sm multi-cell-na" type="text" value="N/A" readonly aria-readonly="true" tabindex="-1" title="No aplica para este producto" aria-label="' + escapeHtml(ariaNa) + '"></td>';
                }
                const pskId = String(skuLinea.min_psk_id || '');
                const meta = estadoInicial.multiMeta[String(fila.prd_id)] || {};
                const valor = String(meta.cantidades?.[pskId] || '');
                const ariaOk = 'Cantidad para producto ' + fila.producto + ', ' + nombreDominante + ' ' + fila.dominante + ', columna ' + col.label;
                return '<td class="multi-td-ok col-cell-' + colIndex + '"><input class="form-control form-control-sm js-multi-cantidad multi-cell-ok" type="number" min="0" step="0.01" data-prd-id="' + fila.prd_id + '" data-min-psk-id="' + pskId + '" data-grid-r="' + rowIndex + '" data-grid-c="' + colIndex + '" value="' + escapeHtml(valor) + '" title="Aplica para este producto" aria-label="' + escapeHtml(ariaOk) + '"></td>';
            }).join('');

            html += '<tr>' + tdProducto + '<th scope="row" class="fw-semibold">' + escapeHtml(fila.dominante) + '</th>' + celdasHtml + '</tr>';
            rowIndex += 1;
        });

        html += '</tbody></table>';
        $('#multi-grid-shell').html(html);
        applyGridDensity(multiGridDensity, false);
        const totalInputs = $('#multi-grid-shell .js-multi-cantidad').length;
        $('#multi-a11y-status').text('Matriz actualizada. ' + totalInputs + ' celdas editables disponibles.');
        recalcularTotalesMulti();
    }

    function cargarOpcionesDominanteGlobal() {
        const mapa = {};
        Object.values(estadoInicial.multiMeta).forEach(function (meta) {
            if (!meta || meta.error || meta.prd_tipo !== 'variable') return;
            (meta.data?.atributos || []).forEach(function (atr) {
                mapa[String(atr.atr_id)] = atr.atr_nombre;
            });
        });

        const options = Object.entries(mapa)
            .sort((a, b) => String(a[1]).localeCompare(String(b[1]), 'es', { sensitivity: 'base' }))
            .map(([id, nombre]) => '<option value="' + id + '">' + escapeHtml(nombre) + '</option>');

        const $sel = $('#multi-dominante-global');
        const previo = String($sel.val() || '');
        $sel.html('<option value="">Selecciona dominante</option>' + options.join(''));

        if (previo && mapa[previo]) {
            $sel.val(previo);
            return;
        }

        const primero = options.length ? Object.keys(mapa)[0] : '';
        if (primero) $sel.val(String(primero));
    }

    function cargarProductosMasivosSeleccionados() {
        const productos = estadoInicial.colaProductos || [];
        const sucursalId = $('#multi-scl-id').val() || '';
        $('#multi-resumen-productos').text(productos.length + ' productos seleccionados');
        $('#multi-grid-shell').html('<table class="table table-sm table-mini mb-0"><tbody><tr><td class="text-body-secondary">Cargando líneas...</td></tr></tbody></table>');
        estadoInicial.multiMeta = {};
        if (!productos.length) return;

        AppUI.showLoader();
        let pendientes = productos.length;
        productos.forEach(function (payload) {
            $.getJSON(rutas.matrizProducto(payload.prd_id), { min_scl_id: sucursalId })
                .done(function (resp) {
                    const data = resp.data || {};
                    const tipo = data?.producto?.prd_tipo || payload.prd_tipo || 'simple';
                    const dominanteSugerido = data?.dominante_sugerido_atr_id || (data?.atributos?.[0]?.atr_id ?? null);
                    estadoInicial.multiMeta[String(payload.prd_id)] = {
                        prd_id: payload.prd_id,
                        producto_label: (payload.prd_codigo || '-') + ' - ' + (payload.prd_nombre || '-'),
                        prd_tipo: tipo,
                        data: data,
                        dominante_atr_id: dominanteSugerido,
                        dominante_fuente: data?.dominante_sugerido_fuente || 'sin_preferencia',
                        guardar_dominante: true,
                        cantidades: {},
                        precio_unitario: Number(payload.prd_precio || 0),
                    };
                })
                .fail(function (xhr) {
                    estadoInicial.multiMeta[String(payload.prd_id)] = {
                        prd_id: payload.prd_id,
                        producto_label: (payload.prd_codigo || '-') + ' - ' + (payload.prd_nombre || '-'),
                        prd_tipo: payload.prd_tipo || 'simple',
                        error: parseError(xhr),
                        guardar_dominante: false,
                        cantidades: {},
                        precio_unitario: Number(payload.prd_precio || 0),
                    };
                })
                .always(function () {
                    pendientes -= 1;
                    if (pendientes > 0) return;

                    AppUI.hideLoader();
                    renderProductosSeleccionadosMulti();
                    cargarOpcionesDominanteGlobal();
                    renderFiltrosAtributosMulti();
                    renderTablaMasivaMultiGlobal();
                });
        });
    }

    function cargarProductosBase() {
        if ($.fn.DataTable.isDataTable('#tbl-productos-base')) {
            $('#tbl-productos-base').DataTable().ajax.reload();
            return;
        }

        $('#tbl-productos-base').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 10,
            searching: false,
            lengthMenu: [10, 25, 50],
            order: [[2, 'asc']],
            ajax: {
                url: rutas.productosBaseData,
                data: function (d) {
                    d.prd_mrc_id = $('#flt-prd-mrc').val();
                    d.prd_mdl_id = $('#flt-prd-mdl').val();
                    d.prd_lna_id = $('#flt-prd-lna').val();
                    d.prd_ctg_id = $('#flt-prd-ctg').val();
                    if ($('#flt-prd-buscar').val()) {
                        d.search = d.search || {};
                        d.search.value = $('#flt-prd-buscar').val();
                    }
                }
            },
            drawCallback: function () {
                $('#chk-productos-todos').prop('checked', false);
                $('#tbl-productos-base tbody input.js-chk-producto-base').each(function () {
                    const id = String($(this).data('prd-id') || '');
                    $(this).prop('checked', Boolean(seleccionProductos[id]));
                });
                actualizarContadorSeleccionados();
            },
            columns: [
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function (row) {
                        return '<input type="checkbox" class="js-chk-producto-base" data-prd-id="' + row.prd_id + '">';
                    }
                },
                { data: 'prd_codigo' },
                { data: 'prd_nombre' },
                { data: 'prd_tipo', render: (v) => (v === 'variable' ? 'Variable' : 'Simple') },
                { data: 'marca_nombre', defaultContent: '-' },
                { data: 'modelo_nombre', defaultContent: '-' },
                { data: 'linea_nombre', defaultContent: '-' },
                { data: 'categoria_nombre', defaultContent: '-' },
                { data: 'skus_activos' },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function (row) {
                        const payload = encodeURIComponent(JSON.stringify({
                            prd_id: row.prd_id,
                            prd_tipo: row.prd_tipo,
                            prd_codigo: row.prd_codigo,
                            prd_nombre: row.prd_nombre
                        }));
                        return '<button class="btn btn-sm btn-outline-primary" data-action="seleccionar-producto-base" data-payload="' + payload + '"><i class="ti tabler-package-import me-1"></i>Capturar</button>';
                    }
                }
            ]
        });
    }

    function seleccionarPrimerProductoTabla() {
        const $fila = $('#tbl-productos-base tbody tr').first();
        const $chk = $fila.find('input.js-chk-producto-base').first();
        if (!$chk.length) return false;
        $('#tbl-productos-base tbody tr').removeClass('table-active');
        $fila.addClass('table-active');
        if (!$chk.prop('checked')) {
            $chk.trigger('click');
        }
        return true;
    }

    function actualizarStatsExistencias(data) {
        const total = data.length;
        const conStock = data.filter((r) => Number(r.exa_existencia || 0) > 0).length;
        const bajoMin = data.filter((r) => {
            const ex = Number(r.exa_existencia || 0);
            const mi = Number(r.minimo_configurado || 0);
            return mi > 0 && ex < mi;
        }).length;
        const sinStock = data.filter((r) => Number(r.exa_existencia || 0) <= 0).length;
        $('#stat-total-skus').text(total);
        $('#stat-con-stock').text(conStock);
        $('#stat-bajo-minimo').text(bajoMin);
        $('#stat-sin-stock').text(sinStock);
    }

    function cargarExistencias() {
        AppUI.showLoader();
        $.getJSON(rutas.existencias, {
            min_scl_id: $('#flt-exa-scl').val(),
            min_alm_id: $('#flt-exa-alm').val(),
            buscar: $('#flt-exa-buscar').val()
        }).done(function (resp) {
            const data = resp.data || [];
            actualizarStatsExistencias(data);
            if ($.fn.DataTable.isDataTable('#tbl-existencias')) $('#tbl-existencias').DataTable().clear().destroy();
            $('#tbl-existencias').DataTable({
                data,
                order: [[3, 'asc'], [4, 'asc'], [1, 'asc']],
                columns: [
                    { data: 'psk_codigo', render: (v) => '<span class="inv-sku-chip">' + escapeHtml(v || '-') + '</span>' },
                    { data: 'psk_nombre' },
                    { data: 'prd_nombre' },
                    { data: 'scl_nombre' },
                    { data: 'alm_nombre' },
                    {
                        data: 'exa_existencia',
                        className: 'text-end',
                        render: function (v, _, row) {
                            const ex = Number(v || 0);
                            const mi = Number(row.minimo_configurado || 0);
                            const cls = (mi > 0 && ex < mi) ? 'stock-low' : 'stock-ok';
                            return '<span class="' + cls + '">' + ex.toFixed(2) + '</span>';
                        }
                    },
                    {
                        data: null,
                        className: 'text-end',
                        render: function (row) {
                            const mi = Number(row.minimo_configurado || 0);
                            return mi > 0 ? mi.toFixed(2) : '<span class="text-body-secondary">—</span>';
                        }
                    },
                    {
                        data: null,
                        render: function (row) {
                            const ex = Number(row.exa_existencia || 0);
                            const mi = Number(row.minimo_configurado || 0);
                            if (ex <= 0) return '<span class="ls-badge ls-badge-danger">Sin stock</span>';
                            if (mi > 0 && ex < mi) return '<span class="ls-badge ls-badge-warning">Bajo mínimo</span>';
                            return '<span class="ls-badge ls-badge-success">Normal</span>';
                        }
                    }
                ]
            });
        }).fail(function (xhr) {
            AppUI.showMessage('Error', parseError(xhr), 'error');
        }).always(function () { AppUI.hideLoader(); });
    }

    function cargarKardex() {
        AppUI.showLoader();
        $.getJSON(rutas.kardex, {
            min_scl_id: $('#flt-kar-scl').val(),
            min_alm_id: $('#flt-kar-alm').val(),
            fecha_desde: $('#flt-kar-desde').val(),
            fecha_hasta: $('#flt-kar-hasta').val()
        }).done(function (resp) {
            const data = resp.data || [];

            if ($.fn.DataTable.isDataTable('#tbl-kardex')) $('#tbl-kardex').DataTable().clear().destroy();
            $('#tbl-kardex').DataTable({
                data,
                order: [[0, 'desc']],
                columns: [
                    { data: 'min_fecha_movimiento', render: (v) => v ? v.replace('T', ' ').slice(0, 16) : '-' },
                    { data: 'min_folio', render: (v) => '<span class="inv-sku-chip">' + escapeHtml(v || '-') + '</span>' },
                    { data: 'psk_codigo', render: (v) => '<span class="inv-sku-chip">' + escapeHtml(v || '-') + '</span>' },
                    { data: null, render: (row) => renderProductoKardex(row) },
                    { data: 'scl_nombre' },
                    { data: 'alm_nombre' },
                    { data: 'tmi_nombre' },
                    {
                        data: null,
                        className: 'text-end',
                        render: function (row) {
                            const signo = Number(row.min_signo);
                            const qty = Number(row.min_cantidad).toFixed(2);
                            if (signo > 0) return '<span class="inv-qty-in"><i class="ti tabler-arrow-up"></i>+' + qty + '</span>';
                            return '<span class="inv-qty-out"><i class="ti tabler-arrow-down"></i>-' + qty + '</span>';
                        }
                    },
                    { data: 'min_existencia_antes', className: 'text-end', render: (v) => Number(v || 0).toFixed(2) },
                    { data: 'min_existencia_despues', className: 'text-end', render: (v) => Number(v || 0).toFixed(2) },
                    { data: 'usuario_nombre', defaultContent: '<span class="text-body-secondary">—</span>' },
                    { data: 'min_estatus', render: (v) => estatusBadge(v) },
                ]
            });

            if ($.fn.DataTable.isDataTable('#tbl-movimientos')) $('#tbl-movimientos').DataTable().clear().destroy();
            $('#tbl-movimientos').DataTable({
                data,
                order: [[1, 'desc']],
                columns: [
                    { data: 'min_folio', render: (v) => '<span class="inv-sku-chip">' + escapeHtml(v || '-') + '</span>' },
                    { data: 'min_fecha_movimiento', render: (v) => v ? v.replace('T', ' ').slice(0, 16) : '-' },
                    { data: 'psk_codigo', render: (v) => '<span class="inv-sku-chip">' + escapeHtml(v || '-') + '</span>' },
                    { data: null, render: (r) => escapeHtml(r.scl_nombre) + ' <span class="text-body-secondary">/</span> ' + escapeHtml(r.alm_nombre) },
                    { data: 'tmi_nombre' },
                    {
                        data: null,
                        className: 'text-end',
                        render: function (r) {
                            const signo = Number(r.min_signo);
                            const qty = Number(r.min_cantidad).toFixed(2);
                            if (signo > 0) return '<span class="inv-qty-in"><i class="ti tabler-arrow-up"></i>+' + qty + '</span>';
                            return '<span class="inv-qty-out"><i class="ti tabler-arrow-down"></i>-' + qty + '</span>';
                        }
                    },
                    { data: 'min_estatus', render: (v) => estatusBadge(v) },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function (row) {
                            if (row.min_estatus !== 'activo' || row.min_es_reversa) return '<span class="text-body-secondary small">—</span>';
                            let html = '<div class="d-flex gap-1">';
                            if (permisosUI.cancelar) {
                                html += '<button class="btn btn-sm btn-outline-danger" data-action="cancelar" data-id="' + row.min_id + '" title="Cancelar"><i class="ti tabler-ban"></i></button>';
                            }
                            if (permisosUI.corregir) {
                                html += '<button class="btn btn-sm btn-outline-primary" data-action="corregir" data-id="' + row.min_id + '" title="Corregir"><i class="ti tabler-pencil"></i></button>';
                            }
                            html += '</div>';
                            return html || '<span class="text-body-secondary small">—</span>';
                        }
                    }
                ]
            });
        }).fail(function (xhr) {
            AppUI.showMessage('Error', parseError(xhr), 'error');
        }).always(function () { AppUI.hideLoader(); });
    }

    function cargarBajoMinimo() {
        AppUI.showLoader();
        $.getJSON(rutas.bajoMinimo, {
            mni_scl_id: $('#flt-min-scl').val(),
            mni_alm_id: $('#flt-min-alm').val()
        }).done(function (resp) {
            if ($.fn.DataTable.isDataTable('#tbl-bajo-minimo')) $('#tbl-bajo-minimo').DataTable().clear().destroy();
            $('#tbl-bajo-minimo').DataTable({
                data: resp.data || [],
                order: [[2, 'asc']],
                columns: [
                    { data: 'psk_codigo', render: (v) => '<span class="inv-sku-chip">' + escapeHtml(v || '-') + '</span>' },
                    { data: 'prd_nombre' },
                    { data: 'scl_nombre' },
                    { data: 'alm_nombre' },
                    { data: 'exa_existencia', className: 'text-end', render: (v) => '<span class="stock-low fw-bold">' + Number(v).toFixed(2) + '</span>' },
                    { data: 'mni_minimo', className: 'text-end', render: (v) => Number(v).toFixed(2) },
                    {
                        data: null,
                        className: 'text-end',
                        render: (r) => '<span class="text-danger fw-semibold">-' + Number(r.mni_minimo - r.exa_existencia).toFixed(2) + '</span>'
                    }
                ]
            });
        }).fail(function (xhr) {
            AppUI.showMessage('Error', parseError(xhr), 'error');
        }).always(function () { AppUI.hideLoader(); });
    }

    function tipoEntradaEtiqueta(tipo) {
        const valor = String(tipo || '');
        if (valor === 'inventario_inicial') return 'Entrada normal (inventario inicial)';
        if (valor === 'entrada_normal') return 'Entrada normal';
        if (valor === 'compra_remision') return 'Compra con remisión';
        if (valor === 'compra_factura') return 'Compra con factura';
        return valor || '-';
    }

    function cargarReportesEntradas() {
        AppUI.showLoader();
        $.getJSON(rutas.reportesEntradasData, {
            fecha_desde: $('#flt-rep-desde').val(),
            fecha_hasta: $('#flt-rep-hasta').val()
        }).done(function (resp) {
            if ($.fn.DataTable.isDataTable('#tbl-reportes-entradas')) $('#tbl-reportes-entradas').DataTable().clear().destroy();
            $('#tbl-reportes-entradas').DataTable({
                data: resp.data || [],
                order: [[0, 'desc']],
                columns: [
                    { data: 'fecha', render: (v) => v ? String(v).replace('T', ' ').slice(0, 16) : '-' },
                    { data: 'usuario_nombre', defaultContent: 'N/D' },
                    { data: 'sucursal_nombre', defaultContent: 'N/D' },
                    { data: 'almacen_nombre', defaultContent: 'N/D' },
                    { data: 'tipo_entrada', render: (v) => tipoEntradaEtiqueta(v) },
                    { data: 'total_folios', className: 'text-end', render: (v) => Number(v || 0) },
                    { data: 'total_documento', className: 'text-end', render: (v) => toMoney(v || 0) },
                    {
                        data: 'folios_texto',
                        render: (v) => {
                            const txt = String(v || '');
                            if (!txt) return '<span class="text-body-secondary">N/D</span>';
                            return '<span title="' + escapeHtml(txt) + '">' + escapeHtml(txt.length > 120 ? (txt.slice(0, 120) + '...') : txt) + '</span>';
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function (row) {
                            return '' +
                                '<div class="d-flex gap-1">' +
                                    '<button class="btn btn-sm btn-outline-primary" data-action="ver-reporte-pdf" data-id="' + row.reporte_id + '" title="Ver PDF"><i class="ti tabler-eye"></i></button>' +
                                    '<button class="btn btn-sm btn-outline-secondary" data-action="descargar-reporte-pdf" data-id="' + row.reporte_id + '" title="Descargar PDF"><i class="ti tabler-download"></i></button>' +
                                '</div>';
                        }
                    }
                ]
            });
        }).fail(function (xhr) {
            AppUI.showMessage('Error', parseError(xhr), 'error');
        }).always(function () { AppUI.hideLoader(); });
    }

    function resetFormDateTime(selector) {
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        $(selector).val(now.toISOString().slice(0, 16));
    }

    $('#btn-filtrar-existencias').on('click', cargarExistencias);
    $('#btn-limpiar-existencias').on('click', function () {
        $('#flt-exa-scl').val('');
        llenarAlmacenesPorSucursal('#flt-exa-alm', '', true);
        $('#flt-exa-buscar').val('');
        cargarExistencias();
    });

    $('#btn-filtrar-kardex').on('click', function (e) { e.preventDefault(); cargarKardex(); });
    $('#btn-limpiar-kardex').on('click', function (e) {
        e.preventDefault();
        $('#flt-kar-scl').val('');
        llenarAlmacenesPorSucursal('#flt-kar-alm', '', true);
        $('#flt-kar-desde').val('');
        $('#flt-kar-hasta').val('');
        cargarKardex();
    });

    $('#btn-recargar-bajo-minimo').on('click', function (e) { e.preventDefault(); cargarBajoMinimo(); });
    $('#btn-filtrar-reportes').on('click', function (e) { e.preventDefault(); cargarReportesEntradas(); });
    $('#btn-limpiar-reportes').on('click', function (e) {
        e.preventDefault();
        $('#flt-rep-desde').val('');
        $('#flt-rep-hasta').val('');
        cargarReportesEntradas();
    });

    $(document).on('change', '.input-sucursal', function () {
        const suc = $(this).val();
        const cont = $(this).closest('form');
        llenarAlmacenesPorSucursal(cont.find('.input-almacen'), suc, false);

        if (cont.attr('id') === 'form-inicial' && estadoInicial.productoId) {
            cargarProductoInicial(estadoInicial.productoId, estadoInicial.productoTipo);
        }
    });

    $('#flt-exa-scl').on('change', function () {
        llenarAlmacenesPorSucursal('#flt-exa-alm', $(this).val(), true);
    });

    $('#flt-kar-scl').on('change', function () {
        llenarAlmacenesPorSucursal('#flt-kar-alm', $(this).val(), true);
    });

    $('#flt-min-scl').on('change', function () {
        llenarAlmacenesPorSucursal('#flt-min-alm', $(this).val(), true);
    });

    initRemoteSelect($('#salida-sku-id'), {
        url: rutas.skusBuscar,
        placeholder: 'Busca por código, SKU o producto',
        minInput: 2
    });

    function consultarDisponibilidadSalida() {
        const pskId = $('#salida-sku-id').val();
        const sclId = $('#salida-scl-id').val();
        const almId = $('#salida-alm-id').val();
        const $wrap = $('#inv-availability-wrap');
        const $card = $('#inv-availability-card');

        if (!pskId || !sclId || !almId) {
            $wrap.addClass('d-none');
            return;
        }

        $.getJSON(rutas.disponibilidad, { min_psk_id: pskId, min_scl_id: sclId, min_alm_id: almId })
            .done(function (resp) {
                const ex = Number(resp.data?.existencia ?? 0);
                $('#av-existencia').text(ex.toFixed(2));
                $card.removeClass('av-ok av-low av-zero');
                if (ex <= 0) {
                    $card.find('.av-icon').attr('class', 'ti tabler-box-off av-icon');
                    $card.addClass('av-zero');
                } else if (resp.data?.bajo_minimo) {
                    $card.find('.av-icon').attr('class', 'ti tabler-alert-triangle av-icon');
                    $card.addClass('av-low');
                } else {
                    $card.find('.av-icon').attr('class', 'ti tabler-box av-icon');
                    $card.addClass('av-ok');
                }
                $wrap.removeClass('d-none');
            })
            .fail(function () { $wrap.addClass('d-none'); });
    }

    $('#salida-sku-id').on('change', consultarDisponibilidadSalida);
    $('#salida-scl-id').on('change', consultarDisponibilidadSalida);
    $('#salida-alm-id').on('change', consultarDisponibilidadSalida);

    $('#btn-filtrar-productos-base').on('click', function (e) {
        e.preventDefault();
        cargarProductosBase();
    });
    $('#btn-limpiar-productos-base').on('click', function (e) {
        e.preventDefault();
        $('#flt-prd-mrc').val('');
        $('#flt-prd-mdl').val('');
        $('#flt-prd-lna').val('');
        $('#flt-prd-ctg').val('');
        $('#flt-prd-buscar').val('');
        cargarProductosBase();
    });
    $('#flt-prd-lna').on('change', function () {
        const linea = $(this).val();
        if (!linea) {
            $('#flt-prd-ctg option').show();
            return;
        }
        $('#flt-prd-ctg option').each(function () {
            if (!$(this).val()) return;
            $(this).toggle(String($(this).data('lna')) === String(linea));
        });
        if ($('#flt-prd-ctg option:selected').is(':hidden')) {
            $('#flt-prd-ctg').val('');
        }
    });
    $(document).on('click', 'button[data-action="seleccionar-producto-base"]', function () {
        const payload = JSON.parse(decodeURIComponent(String($(this).data('payload') || '%7B%7D')));
        iniciarCapturaSeleccion([payload]);
    });
    $(document).on('change', '#tbl-productos-base tbody input.js-chk-producto-base', function () {
        const id = String($(this).data('prd-id') || '');
        const tabla = $.fn.DataTable.isDataTable('#tbl-productos-base') ? $('#tbl-productos-base').DataTable() : null;
        const row = tabla ? tabla.row($(this).closest('tr')).data() : null;
        if (!id || !row) return;
        if ($(this).is(':checked')) {
            seleccionProductos[id] = {
                prd_id: row.prd_id,
                prd_tipo: row.prd_tipo,
                prd_codigo: row.prd_codigo,
                prd_nombre: row.prd_nombre
            };
        } else {
            delete seleccionProductos[id];
            $('#chk-productos-todos').prop('checked', false);
        }
        actualizarContadorSeleccionados();
    });
    $('#chk-productos-todos').on('change', function () {
        const marcado = $(this).is(':checked');
        $('#tbl-productos-base tbody input.js-chk-producto-base').each(function () {
            if ($(this).is(':checked') !== marcado) {
                $(this).trigger('click');
            }
        });
    });
    $('#btn-seleccionar-visibles').on('click', function () {
        $('#tbl-productos-base tbody input.js-chk-producto-base').each(function () {
            if (!$(this).is(':checked')) {
                $(this).trigger('click');
            }
        });
    });
    $('#btn-limpiar-seleccion-productos').on('click', function () {
        limpiarSeleccionProductos();
    });
    $('#btn-capturar-seleccionados').on('click', function () {
        const productos = Object.values(seleccionProductos);
        iniciarCapturaSeleccion(productos);
    });
    $(document).on('click', '#tbl-productos-base tbody tr', function (e) {
        if ($(e.target).closest('button,input,select,label').length) return;
        $('#tbl-productos-base tbody tr').removeClass('table-active');
        $(this).addClass('table-active');
    });
    $('#flt-prd-buscar').on('keydown', function (e) {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        if (!$.fn.DataTable.isDataTable('#tbl-productos-base')) {
            cargarProductosBase();
            return;
        }

        const dt = $('#tbl-productos-base').DataTable();
        dt.ajax.reload(function () {
            if (!seleccionarPrimerProductoTabla()) {
                AppUI.showMessage('Validación', 'No se encontraron productos con esos filtros.', 'warning');
            }
        }, true);
    });
    $(document).on('keydown', function (e) {
        if (e.key !== 'Enter') return;
        if ($('#shell-entrada-step1').hasClass('d-none')) return;
        if ($(e.target).is('textarea')) return;
        if ($(e.target).closest('#form-inicial,#form-inicial-multi,#form-salida,#form-minimo,#form-cancelar,#form-corregir').length) return;

        const $activo = $('#tbl-productos-base tbody tr.table-active input.js-chk-producto-base').first();
        const $primero = $('#tbl-productos-base tbody input.js-chk-producto-base').first();
        const $objetivo = $activo.length ? $activo : $primero;
        if ($objetivo.length) {
            e.preventDefault();
            $objetivo.trigger('click');
        }
    });
    $('#btn-cambiar-producto').on('click', function (e) {
        e.preventDefault();
        estadoInicial.colaProductos = [];
        estadoInicial.colaIndex = 0;
        $('#form-inicial')[0].reset();
        $('#form-inicial-multi')[0].reset();
        resetEstadoInicial();
        actualizarUIReferenciaEntrada();
        actualizarUIReferenciaEntradaMulti();
        resetFormDateTime('#form-inicial [name="min_fecha_movimiento"]');
        resetFormDateTime('#multi-fecha');
        resetFormDateTime('#multi-fecha-emision');
        mostrarPasoProductoBase();
    });
    $('#btn-cambiar-producto-multi').on('click', function (e) {
        e.preventDefault();
        $('#btn-cambiar-producto').trigger('click');
    });
    $('#btn-producto-anterior').on('click', function () {
        cargarProductoDesdeCola((estadoInicial.colaIndex || 0) - 1);
    });
    $('#btn-producto-siguiente').on('click', function () {
        cargarProductoDesdeCola((estadoInicial.colaIndex || 0) + 1);
    });
    $('#inicial-tipo-entrada').on('change', actualizarUIReferenciaEntrada);
    $('#multi-tipo-entrada').on('change', actualizarUIReferenciaEntradaMulti);
    $('#multi-referencia-na').on('change', actualizarReferenciaNa);
    $('#multi-scl-id').on('change', function () {
        llenarAlmacenesPorSucursal('#multi-alm-id', $(this).val(), false);
        if (estadoInicial.modoMasivo && estadoInicial.colaProductos.length) {
            cargarProductosMasivosSeleccionados();
        }
    });
    $('#multi-dominante-global').on('change', function () {
        capturarCantidadesMultiGlobal();
        renderTablaMasivaMultiGlobal();
    });
    $('.density-switch [data-density]').on('click', function () {
        const density = String($(this).data('density') || 'compact');
        applyGridDensity(density, true);
    });
    $(window).on('resize', function () {
        if (multiGridDensity !== 'auto') return;
        applyGridDensity('auto', false);
    });
    $(document).on('change', '#multi-atributos-shell .js-atr-filter-value', function () {
        capturarCantidadesMultiGlobal();
        sincronizarFiltrosAtributosDesdeUI();
        renderTablaMasivaMultiGlobal();
    });
    $(document).on('input change', '#multi-productos-shell .js-multi-precio-producto', function () {
        const prdId = String($(this).data('prd-id') || '');
        const meta = estadoInicial.multiMeta[prdId];
        if (meta) {
            meta.precio_unitario = Number($(this).val() || 0);
        }
        recalcularTotalesMulti();
    });
    $(document).on('click', '#multi-productos-shell .js-multi-quitar-producto', function () {
        const prdId = String($(this).data('prd-id') || '');
        estadoInicial.colaProductos = (estadoInicial.colaProductos || []).filter((p) => String(p.prd_id) !== prdId);
        delete estadoInicial.multiMeta[prdId];
        delete seleccionProductos[prdId];
        actualizarContadorSeleccionados();
        renderProductosSeleccionadosMulti();
        renderFiltrosAtributosMulti();
        cargarOpcionesDominanteGlobal();
        renderTablaMasivaMultiGlobal();
        if (estadoInicial.colaProductos.length === 0) {
            mostrarPasoProductoBase();
            cargarProductosBase();
        }
    });
    $(document).on('input change', '#multi-grid-shell .js-multi-cantidad,#multi-descuento-tipo,#multi-descuento-valor,#multi-flete-total,#multi-iva-porcentaje', function () {
        capturarCantidadesMultiGlobal();
        recalcularTotalesMulti();
    });
    $(document).on('keydown', '#multi-grid-shell .js-multi-cantidad', function (e) {
        const moveKeys = ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'];
        if (!moveKeys.includes(e.key)) return;

        const $actual = $(this);
        const r = Number($actual.data('grid-r'));
        const c = Number($actual.data('grid-c'));
        if (Number.isNaN(r) || Number.isNaN(c)) return;

        let nextR = r;
        let nextC = c;
        if (e.key === 'ArrowUp') nextR -= 1;
        if (e.key === 'ArrowDown') nextR += 1;
        if (e.key === 'ArrowLeft') nextC -= 1;
        if (e.key === 'ArrowRight') nextC += 1;

        const $next = $('#multi-grid-shell .js-multi-cantidad[data-grid-r="' + nextR + '"][data-grid-c="' + nextC + '"]').first();
        if (!$next.length) return;

        e.preventDefault();
        $next.trigger('focus').trigger('select');
    });
    $(document).on('focusin', '#multi-grid-shell .js-multi-cantidad', function () {
        $('#multi-grid-shell .multi-focus-cell').removeClass('multi-focus-cell');
        $('#multi-grid-shell .multi-focus-col').removeClass('multi-focus-col');
        const $input = $(this);
        const col = Number($input.data('grid-c'));
        $input.closest('td').addClass('multi-focus-cell');
        $('#multi-grid-shell .col-cell-' + col).addClass('multi-focus-col');
        $('#multi-grid-shell .col-head-' + col).addClass('multi-focus-col');
    });
    $(document).on('focusout', '#multi-grid-shell .js-multi-cantidad', function () {
        const $input = $(this);
        setTimeout(function () {
            if ($input.is(':focus')) return;
            if ($('#multi-grid-shell .js-multi-cantidad:focus').length) return;
            $('#multi-grid-shell .multi-focus-cell').removeClass('multi-focus-cell');
            $('#multi-grid-shell .multi-focus-col').removeClass('multi-focus-col');
        }, 0);
    });
    $('#inicial-dominante-atr-id').on('change', function () {
        if (estadoInicial.productoTipo !== 'variable' || !estadoInicial.matrizData) return;
        renderMatrizInicial(estadoInicial.matrizData, $(this).val());
    });

    $('#form-inicial').on('submit', function (e) {
        e.preventDefault();
        const tipo = estadoInicial.productoTipo;
        const $form = $(this);

        $form.find('.js-dyn-inicial').remove();

        if (!estadoInicial.productoId || !tipo) {
            AppUI.showMessage('Validación', 'Selecciona un producto base para cargar inventario inicial.', 'warning');
            return;
        }

        if (tipo === 'simple') {
            const cantidad = Number($('#inicial-cantidad-simple').val() || 0);
            if (!estadoInicial.simpleSkuId) {
                AppUI.showMessage('Validación', 'No fue posible identificar el SKU del producto simple seleccionado.', 'warning');
                return;
            }

            if (!(cantidad > 0)) {
                AppUI.showMessage('Validación', 'Captura una cantidad mayor a cero para el producto simple.', 'warning');
                return;
            }

            $form.append('<input type="hidden" class="js-dyn-inicial" name="lineas[0][min_psk_id]" value="' + estadoInicial.simpleSkuId + '">');
            $form.append('<input type="hidden" class="js-dyn-inicial" name="lineas[0][min_cantidad]" value="' + cantidad + '">');
        }

        postForm(rutas.inicialMasivo, $(this), 'Entrada registrada.', function () {
            cargarExistencias();
            cargarKardex();
            const actual = estadoInicial.colaProductos[estadoInicial.colaIndex] || null;
            if (actual?.prd_id) {
                delete seleccionProductos[String(actual.prd_id)];
                actualizarContadorSeleccionados();
            }

            if ((estadoInicial.colaIndex || 0) < (estadoInicial.colaProductos.length - 1)) {
                estadoInicial.colaIndex += 1;
                cargarProductoDesdeCola(estadoInicial.colaIndex);
                cargarProductosBase();
                return;
            }

            estadoInicial.colaProductos = [];
            estadoInicial.colaIndex = 0;
            $('#form-inicial')[0].reset();
            resetEstadoInicial();
            actualizarUIReferenciaEntrada();
            resetFormDateTime('#form-inicial [name="min_fecha_movimiento"]');
            mostrarPasoProductoBase();
            cargarProductosBase();
        });
    });

    $('#form-inicial-multi').on('submit', function (e) {
        e.preventDefault();
        const tipoEntrada = String($('#multi-tipo-entrada').val() || 'inventario_inicial');
        const sucursalId = Number($('#multi-scl-id').val() || 0);
        const almacenId = Number($('#multi-alm-id').val() || 0);
        const fecha = String($('#multi-fecha').val() || '');
        const fechaEmision = String($('#multi-fecha-emision').val() || '');
        const proveedorId = Number($('#multi-prv-id').val() || 0);
        const referencia = String($('#multi-referencia').val() || '');
        const motivo = String($('#multi-motivo').val() || '').trim();
        const observaciones = String($('#multi-observaciones').val() || '').trim();
        const descuentoTipo = String($('#multi-descuento-tipo').val() || 'ninguno');
        const descuentoValor = Number($('#multi-descuento-valor').val() || 0);
        const fleteTotal = Number($('#multi-flete-total').val() || 0);
        const ivaPorcentaje = Number($('#multi-iva-porcentaje').val() || 0);
        const esCompra = tipoEntrada === 'compra_remision' || tipoEntrada === 'compra_factura';
        const esFactura = tipoEntrada === 'compra_factura';

        if (!sucursalId || !almacenId || !fecha || !motivo) {
            AppUI.showMessage('Validación', 'Completa sucursal, almacén, fecha y motivo para continuar.', 'warning');
            return;
        }

        if (esCompra && !referencia.trim()) {
            AppUI.showMessage('Validación', 'La referencia es obligatoria para entradas por compra.', 'warning');
            return;
        }
        if (esCompra && !fechaEmision) {
            AppUI.showMessage('Validación', 'La fecha de emisión es obligatoria para compras con remisión o factura.', 'warning');
            return;
        }
        if (esFactura && !proveedorId) {
            AppUI.showMessage('Validación', 'Selecciona proveedor para compras con factura.', 'warning');
            return;
        }
        if (descuentoTipo === 'porcentaje' && descuentoValor > 100) {
            AppUI.showMessage('Validación', 'El descuento en porcentaje no puede ser mayor a 100.', 'warning');
            return;
        }

        const atrDominanteGlobal = Number($('#multi-dominante-global').val() || 0);
        const guardarDominanteGlobal = $('#multi-guardar-dominante-global').is(':checked');
        const resumenTotales = recalcularTotalesMulti();

        const grupos = {};
        $('#multi-grid-shell .js-multi-cantidad').each(function () {
            const cantidad = Number($(this).val() || 0);
            if (!(cantidad > 0)) return;

            const prdId = Number($(this).data('prd-id') || 0);
            const pskId = Number($(this).data('min-psk-id') || 0);
            if (!prdId || !pskId) return;

            if (!grupos[prdId]) {
                grupos[prdId] = [];
            }
            const meta = estadoInicial.multiMeta[String(prdId)] || {};
            const precioUnitario = Number(meta.precio_unitario || 0);
            grupos[prdId].push({
                min_psk_id: pskId,
                min_cantidad: cantidad,
                min_precio_unitario: Number(precioUnitario.toFixed(2))
            });
        });

        const lotes = Object.keys(grupos).map(function (prdIdRaw) {
            const prdId = Number(prdIdRaw);
            const meta = estadoInicial.multiMeta[String(prdId)] || {};
            const payload = {
                prd_id: prdId,
                min_scl_id: sucursalId,
                min_alm_id: almacenId,
                min_fecha_movimiento: fecha,
                min_fecha_emision: fechaEmision || null,
                min_documento_tipo: tipoEntrada,
                min_documento_referencia: referencia,
                min_motivo_texto: motivo,
                min_observaciones: observaciones || null,
                min_prv_id: proveedorId || null,
                min_descuento_tipo: descuentoTipo,
                min_descuento_valor: Number(descuentoValor.toFixed(2)),
                min_flete_total: Number(fleteTotal.toFixed(2)),
                min_iva_porcentaje: Number(ivaPorcentaje.toFixed(2)),
                lineas: grupos[prdId]
            };
            if (meta.prd_tipo === 'variable' && atrDominanteGlobal && productoAdmiteDominante(meta, atrDominanteGlobal)) {
                payload.dominante_atr_id = atrDominanteGlobal;
                payload.dominante_guardar_predeterminado = guardarDominanteGlobal ? 1 : 0;
            }
            return payload;
        });

        if (!lotes.length) {
            AppUI.showMessage('Validación', 'Captura al menos una existencia mayor a cero en alguno de los productos seleccionados.', 'warning');
            return;
        }

        AppUI.showLoader();
        let indice = 0;
        let exitos = 0;
        const errores = [];
        const foliosRegistrados = [];

        const enviarSiguiente = function () {
            if (indice >= lotes.length) {
                AppUI.hideLoader();
                if (exitos > 0) {
                    cargarExistencias();
                    cargarKardex();
                }

                if (errores.length === 0) {
                    AppUI.showMessage('Éxito', 'Se registraron ' + exitos + ' productos correctamente.', 'success');
                } else if (exitos === 0) {
                    AppUI.showMessage('Error', errores[0], 'error');
                } else {
                    AppUI.showMessage('Aviso', 'Se registraron ' + exitos + ' productos. Errores: ' + errores.length + '. Primer error: ' + errores[0], 'warning');
                }

                if (foliosRegistrados.length > 0) {
                    descargarReporteEntradasSeleccionadas({
                        folios: foliosRegistrados,
                        atr_dominante_id: atrDominanteGlobal || null,
                        min_scl_id: sucursalId,
                        min_alm_id: almacenId,
                        min_documento_tipo: tipoEntrada,
                        min_documento_referencia: referencia,
                        min_motivo_texto: motivo,
                        min_observaciones: observaciones || null,
                        min_fecha_movimiento: fecha,
                        min_fecha_emision: fechaEmision || null,
                        min_prv_id: proveedorId || null,
                        min_descuento_tipo: descuentoTipo,
                        min_descuento_valor: Number(descuentoValor.toFixed(2)),
                        min_flete_total: Number(fleteTotal.toFixed(2)),
                        min_iva_porcentaje: Number(ivaPorcentaje.toFixed(2)),
                        min_total_documento: resumenTotales.total,
                    });
                }

                limpiarSeleccionProductos();
                estadoInicial.colaProductos = [];
                estadoInicial.colaIndex = 0;
                $('#form-inicial-multi')[0].reset();
                actualizarUIReferenciaEntradaMulti();
                resetFormDateTime('#multi-fecha');
                resetFormDateTime('#multi-fecha-emision');
                mostrarPasoProductoBase();
                cargarProductosBase();
                return;
            }

            $.ajax({
                url: rutas.inicialMasivo,
                method: 'POST',
                data: lotes[indice],
                dataType: 'json'
            }).done(function (resp) {
                exitos += 1;
                const folios = resp?.data?.folios || [];
                if (Array.isArray(folios) && folios.length) {
                    folios.forEach((folio) => {
                        if (folio) foliosRegistrados.push(String(folio));
                    });
                }
            }).fail(function (xhr) {
                errores.push(parseError(xhr));
            }).always(function () {
                indice += 1;
                enviarSiguiente();
            });
        };

        enviarSiguiente();
    });

    $('#recibir-tipo-entrada').on('change', function () {
        actualizarUIRecibirReferencia();
        aplicarPresetRecibirMercancia();
        recalcularTotalesRecibirMercancia();
    });
    $('#recibir-referencia-na').on('change', actualizarUIRecibirReferencia);
    $('#recibir-scl-id').on('change', function () {
        llenarAlmacenesPorSucursal('#recibir-alm-id', $(this).val(), false);
        if (Object.keys(recibirState.productos || {}).length) {
            cargarProductosRecibirSeleccionados();
        }
    });
    $('#recibir-incluir-iva,#recibir-iva-porcentaje,#recibir-descuento-tipo,#recibir-descuento-valor,#recibir-flete-total').on('input change', recalcularTotalesRecibirMercancia);
    $('#recibir-dominante-global').on('change', function () {
        renderMatrizRecibirMercancia();
    });
    $(document).on('change', '#recibir-atributos-shell .js-recibir-atr-filter', function () {
        sincronizarFiltrosRecibirDesdeUI();
        renderMatrizRecibirMercancia();
    });
    $(document).on('click', '#recibir-productos-shell .js-recibir-quitar-producto', function () {
        const prdId = String($(this).data('prd-id') || '');
        if (recibirState.productos[prdId]) {
            recibirState.productosQuitados[prdId] = recibirState.productos[prdId];
        }
        delete recibirState.productos[prdId];
        delete recibirState.meta[prdId];
        Object.keys(recibirState.costosFila || {}).forEach((key) => {
            if (key.startsWith(prdId + '||')) delete recibirState.costosFila[key];
        });
        Object.keys(recibirState.filasExcluidas || {}).forEach((key) => {
            if (key.startsWith(prdId + '||')) delete recibirState.filasExcluidas[key];
        });
        cargarProductosRecibirSeleccionados();
        actualizarBotonRestaurarRecibir();
    });
    $(document).on('click', '#recibir-grid-shell .js-recibir-quitar-fila', function () {
        const rowKey = String($(this).data('row-key') || '');
        const skuRaw = String($(this).data('skus') || '');
        if (!rowKey) return;
        recibirState.filasExcluidas[rowKey] = true;
        skuRaw.split(',').map((x) => String(x).trim()).filter(Boolean).forEach((skuId) => {
            delete recibirState.cantidades[skuId];
        });
        renderMatrizRecibirMercancia();
    });
    $(document).on('click', '#recibir-grid-shell .js-recibir-quitar-producto-bloque', function () {
        const prdId = String($(this).data('prd-id') || '');
        if (!prdId) return;
        if (recibirState.productos[prdId]) {
            recibirState.productosQuitados[prdId] = recibirState.productos[prdId];
        }
        delete recibirState.productos[prdId];
        delete recibirState.meta[prdId];
        Object.keys(recibirState.costosFila || {}).forEach((key) => {
            if (key.startsWith(prdId + '||')) delete recibirState.costosFila[key];
        });
        Object.keys(recibirState.filasExcluidas || {}).forEach((key) => {
            if (key.startsWith(prdId + '||')) delete recibirState.filasExcluidas[key];
        });
        cargarProductosRecibirSeleccionados();
        actualizarBotonRestaurarRecibir();
    });
    $('#btn-recibir-restaurar-filas').on('click', function () {
        const totalExcluidas = Object.keys(recibirState.filasExcluidas || {}).length;
        const productosQuitados = Object.values(recibirState.productosQuitados || {});
        const totalProductosQuitados = productosQuitados.length;
        if (!totalExcluidas && !totalProductosQuitados) return;

        productosQuitados.forEach((p) => {
            recibirState.productos[String(p.prd_id)] = p;
        });
        recibirState.productosQuitados = {};
        recibirState.filasExcluidas = {};
        cargarProductosRecibirSeleccionados();
        AppUI.showMessage('Éxito', 'Se restauraron ' + totalExcluidas + ' fila(s) y ' + totalProductosQuitados + ' producto(s).', 'success');
    });
    $(document).on('input change', '#recibir-grid-shell .js-recibir-grid-cantidad', recalcularTotalesRecibirMercancia);
    $(document).on('input change', '#recibir-grid-shell .js-recibir-row-costo', recalcularTotalesRecibirMercancia);
    $(document).on('input change', '#recibir-grid-shell .js-recibir-row-costo', function () {
        const rowKey = String($(this).data('row-key') || '');
        if (!rowKey) return;
        recibirState.costosFilaEditados[rowKey] = true;
    });
    $(document).on('keydown', '#recibir-grid-shell .js-recibir-grid-cantidad', function (e) {
        const moveKeys = ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'];
        if (!moveKeys.includes(e.key)) return;
        const $actual = $(this);
        const r = Number($actual.data('grid-r'));
        const c = Number($actual.data('grid-c'));
        if (Number.isNaN(r) || Number.isNaN(c)) return;
        let nextR = r;
        let nextC = c;
        if (e.key === 'ArrowUp') nextR -= 1;
        if (e.key === 'ArrowDown') nextR += 1;
        if (e.key === 'ArrowLeft') nextC -= 1;
        if (e.key === 'ArrowRight') nextC += 1;
        const $next = $('#recibir-grid-shell .js-recibir-grid-cantidad[data-grid-r="' + nextR + '"][data-grid-c="' + nextC + '"]').first();
        if (!$next.length) return;
        e.preventDefault();
        $next.trigger('focus').trigger('select');
    });

    $('#btn-recibir-buscar-articulos').on('click', function () {
        cargarTablaBuscarRecibir();
        modalRecibirBuscar.show();
    });
    $('#btn-recibir-filtrar-modal').on('click', function (e) {
        e.preventDefault();
        cargarTablaBuscarRecibir();
    });
    $('#btn-recibir-limpiar-modal').on('click', function (e) {
        e.preventDefault();
        $('#recibir-buscar-texto').val('');
        $('#recibir-buscar-marca').val('');
        $('#recibir-buscar-modelo').val('');
        $('#recibir-buscar-linea').val('');
        $('#recibir-buscar-categoria').val('');
        if (recibirState.tablaModalInicializada) {
            $('#tbl-recibir-buscar-productos').DataTable().ajax.reload();
        }
    });
    $('#recibir-buscar-linea').on('change', function () {
        const linea = String($(this).val() || '');
        if (!linea) {
            $('#recibir-buscar-categoria option').show();
            return;
        }
        $('#recibir-buscar-categoria option').each(function () {
            if (!$(this).val()) return;
            $(this).toggle(String($(this).data('lna')) === linea);
        });
        if ($('#recibir-buscar-categoria option:selected').is(':hidden')) {
            $('#recibir-buscar-categoria').val('');
        }
    });
    $(document).on('change', '.js-recibir-modal-chk', function () {
        const payloadRaw = String($(this).data('payload') || '');
        let row = null;
        try {
            row = payloadRaw ? JSON.parse(decodeURIComponent(payloadRaw)) : null;
        } catch (_) {
            row = null;
        }
        if (!row || !row.prd_id) return;
        const key = String(row.prd_id);
        if ($(this).is(':checked')) {
            recibirState.modalSeleccion[key] = row;
        } else {
            delete recibirState.modalSeleccion[key];
        }
    });
    $('#btn-recibir-agregar-seleccionados').on('click', async function () {
        const seleccionados = Object.values(recibirState.modalSeleccion || {});
        if (!seleccionados.length) {
            AppUI.showMessage('Validación', 'Selecciona al menos un producto del modal.', 'warning');
            return;
        }
        seleccionados.forEach((p) => {
            const prdKey = String(p.prd_id);
            recibirState.productos[prdKey] = p;
            delete recibirState.productosQuitados[prdKey];
            Object.keys(recibirState.filasExcluidas || {}).forEach((key) => {
                if (key.startsWith(prdKey + '||')) delete recibirState.filasExcluidas[key];
            });
        });

        recibirState.modalSeleccion = {};
        if (recibirState.tablaModalInicializada) {
            $('#tbl-recibir-buscar-productos').DataTable().ajax.reload(null, false);
        }
        await cargarProductosRecibirSeleccionados();
        AppUI.showMessage('Éxito', 'Se agregaron ' + seleccionados.length + ' producto(s) a la matriz de recepción.', 'success');
        modalRecibirBuscar.hide();
    });

    $('#form-recibir-mercancia').on('submit', function (e) {
        e.preventDefault();
        const tipoEntrada = String($('#recibir-tipo-entrada').val() || 'compra_factura');
        const sucursalId = Number($('#recibir-scl-id').val() || 0);
        const almacenId = Number($('#recibir-alm-id').val() || 0);
        const fechaCaptura = String($('#recibir-fecha-captura').val() || '');
        const fechaEmision = String($('#recibir-fecha-emision').val() || '');
        const proveedorId = Number($('#recibir-prv-id').val() || 0);
        const referencia = String($('#recibir-referencia').val() || '').trim();
        const observaciones = String($('#recibir-observaciones').val() || '').trim();
        const descuentoTipo = String($('#recibir-descuento-tipo').val() || 'ninguno');
        const descuentoValor = Number($('#recibir-descuento-valor').val() || 0);
        const flete = Number($('#recibir-flete-total').val() || 0);
        const iva = $('#recibir-incluir-iva').is(':checked') ? Number($('#recibir-iva-porcentaje').val() || 0) : 0;
        const esCompra = tipoEntrada === 'compra_remision' || tipoEntrada === 'compra_factura';
        const esFactura = tipoEntrada === 'compra_factura';

        if (!sucursalId || !almacenId || !fechaCaptura) {
            AppUI.showMessage('Validación', 'Completa tipo, sucursal, almacén y fecha de captura.', 'warning');
            return;
        }
        if (esCompra && !referencia) {
            AppUI.showMessage('Validación', 'La referencia es obligatoria para compras.', 'warning');
            return;
        }
        if (esCompra && !fechaEmision) {
            AppUI.showMessage('Validación', 'La fecha de factura/remisión es obligatoria para compras.', 'warning');
            return;
        }
        if (esFactura && !proveedorId) {
            AppUI.showMessage('Validación', 'Selecciona proveedor para compras con factura.', 'warning');
            return;
        }

        const lineas = $('#recibir-grid-shell .js-recibir-grid-cantidad').toArray()
            .map((el) => {
                const $el = $(el);
                const prdId = Number($el.data('prd-id') || 0);
                const skuId = Number($el.data('min-psk-id') || 0);
                const rowKey = String($el.data('row-key') || '');
                const qty = Number($el.val() || 0);
                const costo = Number(recibirState.costosFila[rowKey] || 0);
                return {
                    prd_id: prdId,
                    min_psk_id: skuId,
                    min_cantidad: qty,
                    min_precio_unitario: costo,
                };
            })
            .filter((linea) => linea.prd_id > 0 && linea.min_psk_id > 0 && linea.min_cantidad > 0);

        if (!lineas.length) {
            AppUI.showMessage('Validación', 'Captura al menos una cantidad recibida mayor a cero.', 'warning');
            return;
        }

        const grupos = {};
        lineas.forEach((linea) => {
            if (!grupos[linea.prd_id]) grupos[linea.prd_id] = [];
            grupos[linea.prd_id].push({
                min_psk_id: linea.min_psk_id,
                min_cantidad: linea.min_cantidad,
                min_precio_unitario: linea.min_precio_unitario,
            });
        });

        const atrDominanteGlobal = Number($('#recibir-dominante-global').val() || 0);
        const lotes = Object.keys(grupos).map((prdIdRaw) => {
            const prdId = Number(prdIdRaw);
            const payload = {
                prd_id: prdId,
                min_scl_id: sucursalId,
                min_alm_id: almacenId,
                min_fecha_movimiento: fechaCaptura,
                min_fecha_emision: fechaEmision || null,
                min_documento_tipo: tipoEntrada,
                min_documento_referencia: referencia,
                min_motivo_texto: 'Recepción de mercancía manual',
                min_observaciones: observaciones || null,
                min_prv_id: proveedorId || null,
                min_descuento_tipo: descuentoTipo,
                min_descuento_valor: Number(descuentoValor.toFixed(2)),
                min_flete_total: Number(flete.toFixed(2)),
                min_iva_porcentaje: Number(iva.toFixed(2)),
                lineas: grupos[prdIdRaw],
            };
            const meta = recibirState.meta[String(prdId)] || null;
            if (meta && meta.prd_tipo === 'variable' && atrDominanteGlobal && productoAdmiteDominante(meta, atrDominanteGlobal)) {
                payload.dominante_atr_id = atrDominanteGlobal;
            }
            return payload;
        });

        const resumen = recalcularTotalesRecibirMercancia();
        AppUI.showLoader();
        let idx = 0;
        let ok = 0;
        const errores = [];
        const folios = [];

        const siguiente = function () {
            if (idx >= lotes.length) {
                AppUI.hideLoader();
                if (ok > 0) {
                    cargarExistencias();
                    cargarKardex();
                    cargarReportesEntradas();
                }

                if (!errores.length) {
                    AppUI.showMessage('Éxito', 'Recepción registrada correctamente.', 'success');
                } else if (ok > 0) {
                    AppUI.showMessage('Aviso', 'Se registró parcialmente. Primer error: ' + errores[0], 'warning');
                } else {
                    AppUI.showMessage('Error', errores[0] || 'No fue posible registrar la recepción.', 'error');
                }

                if (folios.length) {
                    const atrDominanteGlobal = Number($('#recibir-dominante-global').val() || 0);
                    descargarReporteEntradasSeleccionadas({
                        folios,
                        atr_dominante_id: atrDominanteGlobal || null,
                        min_scl_id: sucursalId,
                        min_alm_id: almacenId,
                        min_documento_tipo: tipoEntrada,
                        min_documento_referencia: referencia,
                        min_motivo_texto: 'Recepción de mercancía manual',
                        min_observaciones: observaciones || null,
                        min_fecha_movimiento: fechaCaptura,
                        min_fecha_emision: fechaEmision || null,
                        min_prv_id: proveedorId || null,
                        min_descuento_tipo: descuentoTipo,
                        min_descuento_valor: Number(descuentoValor.toFixed(2)),
                        min_flete_total: Number(flete.toFixed(2)),
                        min_iva_porcentaje: Number(iva.toFixed(2)),
                        min_total_documento: resumen.total,
                    });
                }

                if (!errores.length) {
                    recibirState.productos = {};
                    recibirState.meta = {};
                    recibirState.filtrosAtributos = {};
                    recibirState.cantidades = {};
                    recibirState.costosFila = {};
                    recibirState.filasExcluidas = {};
                    recibirState.productosQuitados = {};
                    renderProductosRecibirMercancia();
                    renderFiltrosRecibirMercancia();
                    renderMatrizRecibirMercancia();
                    actualizarBotonRestaurarRecibir();
                    $('#form-recibir-mercancia')[0].reset();
                    resetFormDateTime('#recibir-fecha-captura');
                    resetFormDateTime('#recibir-fecha-emision');
                    actualizarUIRecibirReferencia();
                    aplicarPresetRecibirMercancia();
                }
                return;
            }

            $.ajax({
                url: rutas.entradaMasiva,
                method: 'POST',
                dataType: 'json',
                data: lotes[idx],
            }).done(function (resp) {
                ok += 1;
                (resp?.data?.folios || []).forEach((f) => {
                    if (f) folios.push(String(f));
                });
            }).fail(function (xhr) {
                errores.push(parseError(xhr));
            }).always(function () {
                idx += 1;
                siguiente();
            });
        };

        siguiente();
    });

    $('#form-salida').on('submit', function (e) {
        e.preventDefault();
        postForm(rutas.salida, $(this), 'Salida registrada.', function () {
            cargarExistencias();
            cargarKardex();
            $('#form-salida')[0].reset();
            $('#salida-sku-id').val(null).trigger('change');
            $('#form-salida [name="min_documento_tipo"]').val('ajuste_manual');
            resetFormDateTime('#form-salida [name="min_fecha_movimiento"]');
            $('#inv-availability-wrap').addClass('d-none');
        });
    });

    $('#form-minimo').on('submit', function (e) {
        e.preventDefault();
        postForm(rutas.minimo, $(this), 'Mínimo guardado.', function () {
            cargarExistencias();
            cargarBajoMinimo();
        });
    });

    $(document).on('click', 'button[data-action="cancelar"]', function () {
        $('#cancelar-min-id').val($(this).data('id'));
        $('#cancelar-motivo').val('');
        modalCancelar.show();
    });

    $(document).on('click', 'button[data-action="corregir"]', function () {
        $('#corregir-min-id').val($(this).data('id'));
        $('#corregir-motivo').val('');
        $('#corregir-cantidad').val('');
        $('#corregir-referencia').val('');
        $('#corregir-motivo-nuevo').val('');
        resetFormDateTime('#corregir-fecha');
        modalCorregir.show();
    });
    $(document).on('click', 'button[data-action="ver-reporte-pdf"]', function () {
        const id = $(this).data('id');
        window.open(rutas.verReporteEntradasPdf(id), '_blank', 'noopener');
    });
    $(document).on('click', 'button[data-action="descargar-reporte-pdf"]', function () {
        const id = $(this).data('id');
        const a = document.createElement('a');
        a.href = rutas.verReporteEntradasPdf(id);
        a.target = '_blank';
        a.rel = 'noopener';
        document.body.appendChild(a);
        a.click();
        a.remove();
    });

    $('#form-cancelar').on('submit', function (e) {
        e.preventDefault();
        const id = $('#cancelar-min-id').val();
        AppUI.showLoader();
        $.ajax({
            url: rutas.cancelar(id),
            method: 'POST',
            data: { min_motivo_texto: $('#cancelar-motivo').val() },
            dataType: 'json'
        }).done(function (resp) {
            modalCancelar.hide();
            AppUI.showMessage('Éxito', resp.message || 'Movimiento cancelado.', 'success');
            cargarExistencias();
            cargarKardex();
            cargarBajoMinimo();
        }).fail(function (xhr) {
            AppUI.showMessage('Error', parseError(xhr), 'error');
        }).always(function () { AppUI.hideLoader(); });
    });

    $('#form-corregir').on('submit', function (e) {
        e.preventDefault();
        const id = $('#corregir-min-id').val();
        AppUI.showLoader();
        $.ajax({
            url: rutas.corregir(id),
            method: 'POST',
            data: {
                min_motivo_texto: $('#corregir-motivo').val(),
                'nuevo[min_cantidad]': $('#corregir-cantidad').val(),
                'nuevo[min_documento_referencia]': $('#corregir-referencia').val(),
                'nuevo[min_fecha_movimiento]': $('#corregir-fecha').val(),
                'nuevo[min_motivo_texto]': $('#corregir-motivo-nuevo').val()
            },
            dataType: 'json'
        }).done(function (resp) {
            modalCorregir.hide();
            AppUI.showMessage('Éxito', resp.message || 'Movimiento corregido.', 'success');
            cargarExistencias();
            cargarKardex();
            cargarBajoMinimo();
        }).fail(function (xhr) {
            AppUI.showMessage('Error', parseError(xhr), 'error');
        }).always(function () { AppUI.hideLoader(); });
    });

    llenarAlmacenesPorSucursal('#flt-exa-alm', '', true);
    llenarAlmacenesPorSucursal('#flt-kar-alm', '', true);
    llenarAlmacenesPorSucursal('#flt-min-alm', '', true);
    llenarAlmacenesPorSucursal('#multi-alm-id', '', false);
    llenarAlmacenesPorSucursal('#recibir-alm-id', '', false);

    try {
        multiGridDensity = normalizeDensity(localStorage.getItem(storageKeyDensity) || 'auto');
    } catch (_) {
        multiGridDensity = 'auto';
    }
    applyGridDensity(multiGridDensity, false);

    mostrarPasoProductoBase();
    resetEstadoInicial();
    actualizarContadorSeleccionados();
    actualizarUIReferenciaEntrada();
    actualizarUIReferenciaEntradaMulti();
    actualizarUIRecibirReferencia();
    resetFormDateTime('#form-inicial [name="min_fecha_movimiento"]');
    resetFormDateTime('#multi-fecha');
    resetFormDateTime('#multi-fecha-emision');
    resetFormDateTime('#recibir-fecha-captura');
    resetFormDateTime('#recibir-fecha-emision');
    resetFormDateTime('#form-salida [name="min_fecha_movimiento"]');
    aplicarPresetRecibirMercancia();
    renderProductosRecibirMercancia();
    renderFiltrosRecibirMercancia();
    renderMatrizRecibirMercancia();
    actualizarBotonRestaurarRecibir();

    cargarProductosBase();
    if (!soloEntradas) {
        cargarExistencias();
        cargarKardex();
        cargarBajoMinimo();
        cargarReportesEntradas();
    }
})();
</script>
@endpush
