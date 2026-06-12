@extends('layouts.app')

@section('title', 'Pedidos de Piso')

@push('vendor-styles')
<style>
/* ══════════════════════════════════════════════════════════════
   PEDIDOS DE PISO — Mobile First
   ══════════════════════════════════════════════════════════════ */

/* ─── Segment tabs ────────────────────────────────────────────── */
.pp-tabs {
    display: flex;
    background: var(--ls-surface-3);
    border-radius: 0.8rem;
    padding: 3px;
    gap: 3px;
    margin-bottom: 1.25rem;
}
.pp-tab-btn {
    flex: 1;
    padding: 0.55rem 0.75rem;
    border: 0;
    border-radius: 0.6rem;
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--ls-text-muted);
    background: transparent;
    cursor: pointer;
    transition: background 0.15s, color 0.15s, box-shadow 0.15s;
    white-space: nowrap;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    -webkit-tap-highlight-color: transparent;
}
.pp-tab-btn.active {
    background: #fff;
    color: var(--ls-text-primary);
    box-shadow: 0 1px 4px rgba(10,37,64,0.1);
}
.pp-tab-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.3rem;
    height: 1.3rem;
    padding: 0 0.3rem;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 800;
    background: var(--ls-accent-light);
    color: var(--ls-accent);
    line-height: 1;
}
.pp-tab-btn.active .pp-tab-badge {
    background: var(--ls-accent);
    color: #fff;
}

/* ─── Tab panels ──────────────────────────────────────────────── */
.pp-panel { display: none; }
.pp-panel.active { display: block; }


/* ─── Page header ─────────────────────────────────────────────── */
.pp-top {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-bottom: 1.1rem;
}
.pp-top__title {
    font-size: 1.2rem;
    font-weight: 800;
    color: var(--ls-text-primary);
    margin: 0;
    line-height: 1.2;
}
.pp-sucursal-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    background: var(--ls-accent-light);
    color: var(--ls-accent);
    border: 1px solid rgba(99,91,255,0.18);
    border-radius: 999px;
    padding: 0.28rem 0.75rem;
    font-size: 0.78rem;
    font-weight: 700;
    white-space: nowrap;
}

/* ─── Hero search ─────────────────────────────────────────────── */
.pp-hero {
    display: flex;
    align-items: center;
    background: #fff;
    border: 1.5px solid var(--ls-border);
    border-radius: 1rem;
    position: relative;
    margin-bottom: 0.85rem;
    box-shadow: var(--ls-shadow);
    transition: border-color 0.15s, box-shadow 0.15s;
}
.pp-hero:focus-within {
    border-color: var(--ls-border-focus);
    box-shadow: 0 0 0 3px rgba(99,91,255,0.13);
}
.pp-hero__icon {
    flex-shrink: 0;
    color: var(--ls-text-muted);
    font-size: 1.25rem;
    padding-left: 1rem;
}
.pp-hero__input {
    flex: 1;
    height: 3.4rem;
    border: 0;
    outline: none;
    font-size: 1.05rem;
    color: var(--ls-text-primary);
    background: transparent;
    padding: 0 1rem;
}
.pp-hero__input::placeholder { color: var(--ls-text-muted); }

#sugerencias {
    position: absolute;
    left: 0; right: 0;
    top: calc(100% + 6px);
    z-index: 30;
    background: #fff;
    border: 1px solid var(--ls-border);
    border-radius: 0.85rem;
    box-shadow: var(--ls-shadow-lg);
    max-height: 260px;
    overflow-y: auto;
}
#sugerencias .list-group-item {
    border-left: 0; border-right: 0;
    border-radius: 0;
    padding: 0.75rem 1rem;
    text-transform: none;
    letter-spacing: 0;
}
#sugerencias .list-group-item:first-child { border-top: 0; border-radius: 0.85rem 0.85rem 0 0; }
#sugerencias .list-group-item:last-child  { border-bottom: 0; border-radius: 0 0 0.85rem 0.85rem; }
#sugerencias .list-group-item.active { background: var(--ls-accent-light); border-color: transparent; color: inherit; }
#sugerencias .list-group-item:hover  { background: var(--ls-surface-3); }

/* ─── Summary pill ────────────────────────────────────────────── */
.pp-summary-pill {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.88rem;
    font-weight: 600;
    color: var(--ls-text-secondary);
    padding: 0.2rem 0.05rem 0.8rem;
    text-transform: none;
    letter-spacing: 0;
}
.pp-summary-sep {
    width: 3px; height: 3px;
    border-radius: 999px;
    background: var(--ls-text-muted);
    opacity: 0.45;
    flex-shrink: 0;
    display: inline-block;
}
.pp-summary-total {
    margin-left: auto;
    font-weight: 800;
    font-size: 0.95rem;
    color: var(--ls-text-primary);
}

/* ─── Accordion (carrito) ─────────────────────────────────────── */
.pp-accord {
    background: #fff;
    border: 1px solid var(--ls-border);
    border-radius: 0.9rem;
    margin-bottom: 0.6rem;
    overflow: hidden;
    transition: border-color 0.15s;
}
.pp-accord[open] { border-color: rgba(99,91,255,0.28); }

.pp-accord > summary {
    list-style: none;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.85rem 1rem;
    cursor: pointer;
    user-select: none;
    -webkit-tap-highlight-color: transparent;
}
.pp-accord > summary::-webkit-details-marker { display: none; }
.pp-accord > summary::marker               { display: none; }

.pp-accord__info { flex: 1; min-width: 0; }
.pp-accord__name {
    font-weight: 800;
    font-size: 0.92rem;
    color: var(--ls-text-primary);
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    text-transform: none;
    letter-spacing: 0;
}
.pp-accord__meta {
    font-size: 0.76rem;
    color: var(--ls-text-muted);
    display: block;
    margin-top: 0.1rem;
    text-transform: none;
    letter-spacing: 0;
}
.pp-accord__right {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    flex-shrink: 0;
}
.pp-accord__sub {
    font-weight: 800;
    font-size: 0.98rem;
    color: var(--ls-text-primary);
    text-transform: none;
    letter-spacing: 0;
}
.pp-accord__chevron {
    font-size: 1rem;
    color: var(--ls-text-muted);
    transition: transform 0.2s ease;
    flex-shrink: 0;
}
.pp-accord[open] .pp-accord__chevron { transform: rotate(180deg); }
.pp-accord__body { border-top: 1px solid var(--ls-border); }

/* ─── Product rows ────────────────────────────────────────────── */
.pp-prod-row {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.7rem 1rem;
    border-bottom: 1px solid var(--ls-surface-3);
}
.pp-prod-row:last-of-type { border-bottom: 0; }
.pp-prod-row__info { flex: 1; min-width: 0; }
.pp-prod-row__name {
    font-weight: 600;
    font-size: 0.86rem;
    color: var(--ls-text-primary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    text-transform: none;
    letter-spacing: 0;
}
.pp-prod-row__sku {
    font-size: 0.73rem;
    color: var(--ls-text-muted);
    margin-top: 0.1rem;
    text-transform: none;
    letter-spacing: 0;
}
.pp-prod-row__capturista {
    font-size: 0.7rem;
    color: var(--ls-accent);
    font-weight: 700;
    margin-top: 0.16rem;
    text-transform: none;
    letter-spacing: 0;
}
.pp-prod-row__right {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    flex-shrink: 0;
}
.pp-prod-row__price {
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--ls-text-secondary);
    min-width: 3.2rem;
    text-align: right;
    text-transform: none;
    letter-spacing: 0;
}

/* ─── Qty control ─────────────────────────────────────────────── */
.pp-qty-ctrl {
    display: inline-flex;
    align-items: center;
    border: 1px solid var(--ls-border);
    border-radius: 0.6rem;
    overflow: hidden;
    height: 42px;
    background: #fff;
}
.pp-qty-btn {
    width: 42px; height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    font-weight: 700;
    border: 0;
    background: var(--ls-surface-3);
    color: var(--ls-text-secondary);
    cursor: pointer;
    flex-shrink: 0;
    line-height: 1;
    transition: background 0.1s;
    -webkit-tap-highlight-color: transparent;
}
.pp-qty-btn:active { background: var(--ls-border); }
.pp-qty-input {
    border: 0;
    border-left: 1px solid var(--ls-border);
    border-right: 1px solid var(--ls-border);
    border-radius: 0;
    text-align: center;
    width: 58px; height: 42px;
    font-size: 1rem;
    font-weight: 700;
    color: var(--ls-text-primary);
    padding: 0;
    background: #fff;
    box-shadow: none !important;
    -moz-appearance: textfield;
}
.pp-qty-input::-webkit-inner-spin-button,
.pp-qty-input::-webkit-outer-spin-button { -webkit-appearance: none; }

.pp-del-btn {
    width: 26px; height: 26px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 0;
    background: transparent;
    color: var(--ls-text-muted);
    border-radius: 0.4rem;
    font-size: 0.75rem;
    cursor: pointer;
    transition: color 0.12s, background 0.12s;
    -webkit-tap-highlight-color: transparent;
}
.pp-del-btn:hover, .pp-del-btn:active { color: var(--ls-danger); background: var(--ls-danger-bg); }

/* ─── Accordion footer ────────────────────────────────────────── */
.pp-accord__foot {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 0.65rem 1rem;
    border-top: 1px solid var(--ls-surface-3);
    background: var(--ls-surface-2);
}

/* ─── Empty state ─────────────────────────────────────────────── */
.pp-empty {
    text-align: center;
    padding: 1.75rem 1rem;
    color: var(--ls-text-muted);
    border: 1px dashed var(--ls-border);
    border-radius: 0.9rem;
    background: var(--ls-surface-2);
    margin-bottom: 0.75rem;
}
.pp-empty i { font-size: 1.75rem; opacity: 0.28; display: block; margin-bottom: 0.4rem; }
.pp-empty__text { font-size: 0.84rem; text-transform: none; letter-spacing: 0; }

/* ─── Notes toggle ────────────────────────────────────────────── */
.pp-notes-toggle { margin-bottom: 1rem; }
.pp-notes-btn {
    background: none;
    border: 1px dashed var(--ls-border);
    border-radius: 0.65rem;
    color: var(--ls-text-muted);
    font-size: 0.84rem;
    font-weight: 600;
    padding: 0.5rem 0.9rem;
    width: 100%;
    text-align: left;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    transition: border-color 0.15s, color 0.15s;
    -webkit-tap-highlight-color: transparent;
}
.pp-notes-btn:hover { border-color: var(--ls-accent); color: var(--ls-accent); }
.pp-notes-area {
    margin-top: 0.5rem;
    display: none;
}
.pp-notes-area textarea { border-radius: 0.65rem; min-height: 76px; resize: vertical; }

/* ─── Desktop generate button ─────────────────────────────────── */
.pp-btn-gen-desktop {
    height: 2.85rem;
    font-size: 0.95rem;
    font-weight: 700;
    border-radius: 0.75rem;
    width: 100%;
    display: none;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

/* ─── Orders section ──────────────────────────────────────────── */
.pp-orders-hdr {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.85rem;
    flex-wrap: wrap;
}
.pp-orders-hdr__title {
    font-size: 0.98rem;
    font-weight: 800;
    color: var(--ls-text-primary);
    margin: 0;
}
.pp-search-folio {
    border-radius: 999px;
    font-size: 0.84rem;
    max-width: 175px;
    height: 2rem;
}

/* ─── Order cards ─────────────────────────────────────────────── */
.pp-order-card {
    background: #fff;
    border: 1px solid var(--ls-border);
    border-radius: 0.85rem;
    padding: 0.75rem 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
    transition: border-color 0.12s;
}
.pp-order-card:hover { border-color: rgba(99,91,255,0.28); }
.pp-order-card__folio {
    font-family: monospace;
    font-weight: 800;
    font-size: 0.88rem;
    color: var(--ls-accent);
    display: block;
    text-transform: none;
    letter-spacing: 0;
}
.pp-order-card__where {
    font-size: 0.76rem;
    color: var(--ls-text-muted);
    display: block;
    margin-top: 0.1rem;
    text-transform: none;
    letter-spacing: 0;
}
.pp-order-card__right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.28rem;
    flex-shrink: 0;
}
.pp-order-card__actions {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    flex-wrap: wrap;
    justify-content: flex-end;
}
.pp-order-card__action {
    width: 2rem;
    height: 2rem;
    padding: 0;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    font-weight: 700;
}
.pp-order-card__action i {
    pointer-events: none;
}
.pp-order-card__total {
    font-weight: 800;
    font-size: 0.92rem;
    color: var(--ls-text-primary);
    text-transform: none;
    letter-spacing: 0;
}
.pp-edit-banner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.75rem 0.9rem;
    margin-bottom: 0.85rem;
    border: 1px solid rgba(99,91,255,0.22);
    border-radius: 0.9rem;
    background: linear-gradient(180deg, rgba(99,91,255,0.08) 0%, rgba(99,91,255,0.03) 100%);
}
.pp-edit-banner__eyebrow {
    display: block;
    font-size: 0.72rem;
    font-weight: 800;
    color: var(--ls-accent);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.pp-edit-banner__title {
    display: block;
    margin-top: 0.1rem;
    font-size: 0.92rem;
    font-weight: 800;
    color: var(--ls-text-primary);
    text-transform: none;
    letter-spacing: 0;
}
.pp-edit-banner__meta {
    display: block;
    margin-top: 0.1rem;
    font-size: 0.78rem;
    color: var(--ls-text-secondary);
    text-transform: none;
    letter-spacing: 0;
}
.pp-edit-banner__cancel {
    flex-shrink: 0;
    border-radius: 999px;
    font-weight: 700;
}
.pp-orders-empty {
    text-align: center;
    padding: 2rem 1rem;
    color: var(--ls-text-muted);
    font-size: 0.84rem;
    border: 1px dashed var(--ls-border);
    border-radius: 0.85rem;
    text-transform: none;
    letter-spacing: 0;
}

/* ─── Status badges ───────────────────────────────────────────── */
.pp-badge {
    border-radius: 999px;
    padding: 0.18rem 0.58rem;
    font-size: 0.73rem;
    font-weight: 700;
    white-space: nowrap;
    display: inline-flex;
    text-transform: none;
    letter-spacing: 0;
}
.pp-badge-pendiente { background: var(--ls-warning-bg); color: #a96c00; }
.pp-badge-cobrado   { background: var(--ls-success-bg); color: var(--ls-success); }
.pp-badge-cancelado { background: var(--ls-danger-bg);  color: var(--ls-danger); }
.pp-badge-default   { background: var(--ls-surface-3);  color: var(--ls-text-muted); }

/* ─── Modal ───────────────────────────────────────────────────── */
.pp-modal-options { display: grid; gap: 0.6rem; }
.pp-modal-option {
    border: 1px solid var(--ls-border);
    border-radius: 0.9rem;
    background: #fff;
    padding: 0.85rem 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    cursor: pointer;
    transition: border-color 0.15s, box-shadow 0.15s;
    width: 100%;
    text-align: left;
}
.pp-modal-option:hover {
    border-color: rgba(99,91,255,0.45);
    box-shadow: 0 8px 20px rgba(99,91,255,0.12);
}
.pp-modal-option__title {
    display: block;
    font-weight: 800;
    font-size: 0.94rem;
    color: var(--ls-text-primary);
    text-transform: none;
    letter-spacing: 0;
}
.pp-modal-option__meta {
    display: block;
    font-size: 0.78rem;
    color: var(--ls-text-muted);
    margin-top: 0.1rem;
    text-transform: none;
    letter-spacing: 0;
}
.pp-modal-option__icon {
    width: 2.1rem; height: 2.1rem;
    border-radius: 999px;
    background: var(--ls-accent-light);
    color: var(--ls-accent);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 0.9rem;
}

/* ─── Sticky bar (mobile) ─────────────────────────────────────── */
.pp-sticky-bar {
    position: fixed;
    bottom: 0; left: 0; right: 0;
    padding: 0.7rem 1rem;
    padding-bottom: calc(0.7rem + env(safe-area-inset-bottom));
    background: rgba(255,255,255,0.96);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-top: 1px solid var(--ls-border);
    z-index: 200;
    transform: translateY(100%);
    transition: transform 0.25s ease;
}
.pp-sticky-bar.visible { transform: translateY(0); }
.pp-sticky-btn {
    width: 100%;
    height: 3.25rem;
    font-size: 1rem;
    font-weight: 800;
    border-radius: 0.85rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
}
.pp-sticky-btn__total {
    margin-left: auto;
    font-size: 0.9rem;
    opacity: 0.85;
}

/* ─── Responsive ──────────────────────────────────────────────── */
@media (max-width: 767.98px) {
    .pp-btn-gen-desktop { display: none !important; }
    /* Espacio para la barra sticky */
    #pp-panel-captura   { padding-bottom: 5rem; }
}
@media (min-width: 768px) {
    .pp-sticky-bar      { display: none !important; }
    .pp-btn-gen-desktop { display: flex; }
}
</style>
@endpush

@section('content')

<input type="hidden" id="pdp_scl_id" value="{{ (int)($defaultSucursalId ?? 0) }}">

{{-- Encabezado compacto --}}
<div class="pp-top">
    <h4 class="pp-top__title">Pedidos de piso</h4>
    <span class="pp-sucursal-pill">
        <i class="tabler-building-store"></i>
        {{ optional($opciones['sucursales']->firstWhere('scl_id', $defaultSucursalId))->scl_nombre ?? 'Sin sucursal configurada' }}
    </span>
</div>

{{-- Tabs de segmento --}}
<div class="pp-tabs" role="tablist">
    <button class="pp-tab-btn active" data-target="pp-panel-captura" role="tab">
        <i class="tabler-clipboard-plus"></i>Nuevo pedido
    </button>
    <button class="pp-tab-btn" data-target="pp-panel-listado" role="tab">
        <i class="tabler-list-details"></i>Pedidos del día
        <span class="pp-tab-badge" id="pp-tab-badge-listado">0</span>
    </button>
</div>

{{-- ── PANEL 1: CAPTURA ──────────────────────────────────── --}}
<div id="pp-panel-captura" class="pp-panel active">

    <div id="pp-edit-banner" class="pp-edit-banner d-none">
        <div>
            <span class="pp-edit-banner__eyebrow">Modo edición</span>
            <span class="pp-edit-banner__title" id="pp-edit-title">Editando pedido</span>
            <span class="pp-edit-banner__meta" id="pp-edit-meta">Puedes seguir agregando productos a este pedido pendiente.</span>
        </div>
        <button type="button" class="btn btn-outline-secondary btn-sm pp-edit-banner__cancel" id="btn-cancelar-edicion">Cancelar</button>
    </div>

    {{-- Buscador hero --}}
    <div class="pp-hero position-relative">
        <i class="tabler-scan pp-hero__icon"></i>
        <input
            class="pp-hero__input"
            id="buscar_producto"
            type="text"
            placeholder="Escanea o busca un producto…"
            autocomplete="off"
        />
        <div id="sugerencias" class="list-group d-none"></div>
    </div>

    {{-- Resumen en línea (oculto cuando el carrito está vacío) --}}
    <div id="pp-pill-summary" class="pp-summary-pill d-none">
        <span id="pp-resumen-productos">0</span>&thinsp;productos
        <span class="pp-summary-sep"></span>
        <span id="pp-resumen-almacenes">0</span>&thinsp;almacenes
        <span class="pp-summary-total" id="pp-resumen-total">$0.00</span>
    </div>

    {{-- Grupos del carrito --}}
    <div id="pp-grupos" class="mb-1"></div>

    {{-- Notas (colapsadas por defecto) --}}
    <div class="pp-notes-toggle">
        <button type="button" class="pp-notes-btn" id="btn-toggle-notas">
            <i class="tabler-notes"></i>Agregar notas al pedido
        </button>
        <div class="pp-notes-area" id="pp-notas-area">
            <textarea class="form-control" id="pdp_observaciones" rows="3" placeholder="Instrucciones especiales, referencias…"></textarea>
        </div>
    </div>

    {{-- Botón generar (desktop) --}}
    <button
        type="button"
        class="btn btn-primary pp-btn-gen-desktop"
        id="btn-generar-todos"
        @if(!$permisosUI['crear']) disabled @endif
    >
        <i class="tabler-device-floppy"></i><span id="pp-btn-desktop-label">Generar todos los pedidos</span>
    </button>

</div>

{{-- ── PANEL 2: LISTADO ──────────────────────────────────── --}}
<div id="pp-panel-listado" class="pp-panel">
    <div class="pp-orders-hdr">
        <h5 class="pp-orders-hdr__title">Pedidos del día</h5>
        <input class="form-control pp-search-folio" id="flt-buscar" placeholder="Buscar folio…" />
    </div>
    <div id="pp-pedidos-lista"></div>
</div>

{{-- Barra sticky (solo móvil) --}}
<div class="pp-sticky-bar" id="pp-sticky-bar">
    <button
        type="button"
        class="btn btn-primary pp-sticky-btn"
        id="btn-generar-todos-mobile"
        @if(!$permisosUI['crear']) disabled @endif
    >
        <i class="tabler-device-floppy"></i>
        <span id="pp-btn-mobile-label">Generar pedidos</span>
        <span class="pp-sticky-btn__total" id="pp-sticky-total">$0.00</span>
    </button>
</div>

{{-- Modal: elegir almacén --}}
<div class="modal fade" id="modal-seleccionar-almacen" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-1">
                <div>
                    <h5 class="modal-title">¿De dónde tomamos el producto?</h5>
                    <div class="text-body-secondary small mt-1" id="pp-modal-almacen-subtitle"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-1">
                <div id="pp-modal-almacen-options" class="pp-modal-options"></div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-eliminar-pedido" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title">Eliminar pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-body-secondary" id="pp-eliminar-texto">¿Seguro que deseas eliminar este pedido?</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btn-confirmar-eliminar-pedido">Eliminar</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('page-scripts')
<script>
(() => {
    /* ─── Rutas ──────────────────────────────────────────────── */
    const rutas = {
        data:             '{{ route('operacion.pedidos_piso.data') }}',
        store:            '{{ route('operacion.pedidos_piso.store') }}',
        update:           id => `{{ url('/operacion/pedidos-piso') }}/${id}`,
        show:             id => `{{ url('/operacion/pedidos-piso') }}/${id}`,
        destroy:          id => `{{ url('/operacion/pedidos-piso') }}/${id}`,
        ticket:           id => `{{ url('/operacion/pedidos-piso') }}/${id}/ticket`,
        buscarProductos:  '{{ route('operacion.pedidos_piso.productos.buscar') }}',
        resolverProducto: '{{ route('operacion.pedidos_piso.productos.resolver') }}',
    };
    const puedeCrear = {{ $permisosUI['crear'] ? 'true' : 'false' }};
    const usuarioActual = @json($usuarioActual);

    /* ─── Referencias DOM ────────────────────────────────────── */
    const gruposWrap     = document.getElementById('pp-grupos');
    const listaPedidos   = document.getElementById('pp-pedidos-lista');
    const inpBuscar      = document.getElementById('buscar_producto');
    const boxSugerencias = document.getElementById('sugerencias');
    const inpFiltro      = document.getElementById('flt-buscar');
    const selectSucursal = document.getElementById('pdp_scl_id');
    const notasInput     = document.getElementById('pdp_observaciones');
    const btnGenDesktop  = document.getElementById('btn-generar-todos');
    const btnGenMobile   = document.getElementById('btn-generar-todos-mobile');
    const stickyBar      = document.getElementById('pp-sticky-bar');
    const stickyTotal    = document.getElementById('pp-sticky-total');
    const pillSummary    = document.getElementById('pp-pill-summary');
    const editBanner     = document.getElementById('pp-edit-banner');
    const editTitle      = document.getElementById('pp-edit-title');
    const editMeta       = document.getElementById('pp-edit-meta');
    const btnCancelarEdicion = document.getElementById('btn-cancelar-edicion');
    const btnDesktopLabel = document.getElementById('pp-btn-desktop-label');
    const btnMobileLabel  = document.getElementById('pp-btn-mobile-label');
    const puedeEliminar = {{ $permisosUI['eliminar'] ? 'true' : 'false' }};
    const modalEl        = document.getElementById('modal-seleccionar-almacen');
    const modal          = new bootstrap.Modal(modalEl);
    const modalOptions   = document.getElementById('pp-modal-almacen-options');
    const modalSubtitle  = document.getElementById('pp-modal-almacen-subtitle');
    const modalEliminarPedidoEl = document.getElementById('modal-eliminar-pedido');
    const modalEliminarPedido = new bootstrap.Modal(modalEliminarPedidoEl);
    const eliminarTexto = document.getElementById('pp-eliminar-texto');
    const btnConfirmarEliminarPedido = document.getElementById('btn-confirmar-eliminar-pedido');

    /* ─── Estado ─────────────────────────────────────────────── */
    let timer               = null;
    let partidas            = [];
    let sugerenciasActuales = [];
    let sugerenciaActiva    = -1;
    let guardando           = false;
    let resolverAlmacen     = null;
    let pedidoEditando      = null;
    let pedidoPendienteEliminar = null;

    /* ─── Tabs ───────────────────────────────────────────────── */
    const tabBtns   = document.querySelectorAll('.pp-tab-btn');
    const tabPanels = document.querySelectorAll('.pp-panel');

    function activarTab(target) {
        tabBtns.forEach(b => b.classList.toggle('active', b.dataset.target === target));
        tabPanels.forEach(p => p.classList.toggle('active', p.id === target));
        // La barra sticky solo aplica en el panel de captura
        if (target !== 'pp-panel-captura') {
            stickyBar.classList.remove('visible');
        } else {
            stickyBar.classList.toggle('visible', partidas.length > 0);
        }
    }

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => activarTab(btn.dataset.target));
    });

    /* ─── Toggle notas ───────────────────────────────────────── */
    document.getElementById('btn-toggle-notas').addEventListener('click', () => {
        const area = document.getElementById('pp-notas-area');
        area.style.display = area.style.display === 'block' ? 'none' : 'block';
    });

    /* ─── Helpers ────────────────────────────────────────────── */
    function money(v) {
        return '$' + Number(v || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function qtyStep(item) {
        return item?.permite_decimal ? '0.01' : '1';
    }

    function qtyMin(item) {
        return item?.permite_decimal ? '0.01' : '1';
    }

    function sanitizeCantidad(value, item) {
        const parsed = Number.parseFloat(value);
        if (!Number.isFinite(parsed) || parsed <= 0) {
            return item?.permite_decimal ? 0.01 : 1;
        }

        if (item?.permite_decimal) {
            return Math.max(0.01, Math.round(parsed * 100) / 100);
        }

        return Math.max(1, Math.round(parsed));
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function syncEditModeUi() {
        const editing = Boolean(pedidoEditando);
        editBanner.classList.toggle('d-none', !editing);
        btnDesktopLabel.textContent = editing ? 'Guardar cambios del pedido' : 'Generar todos los pedidos';
        btnMobileLabel.textContent = editing ? 'Guardar cambios' : 'Generar pedidos';

        if (editing) {
            editTitle.textContent = `Editando ${pedidoEditando.pdp_folio}`;
            editMeta.textContent = `Pedido pendiente en ${pedidoEditando.almacen}. Puedes seguir agregando productos de ese mismo almacén.`;
        }
    }

    function resetEditorState() {
        partidas = [];
        pedidoEditando = null;
        notasInput.value = '';
        syncEditModeUi();
        renderPartidas();
        inpBuscar.value = '';
        cerrarSugerencias();
    }

    function estatusBadge(estatus) {
        const e = (estatus || '').toLowerCase().replace(/_/g, ' ');
        const label = e.replace(/\b\w/g, c => c.toUpperCase());
        if (e.includes('pendiente')) return `<span class="pp-badge pp-badge-pendiente">${label}</span>`;
        if (e.includes('cobrado'))   return `<span class="pp-badge pp-badge-cobrado">${label}</span>`;
        if (e.includes('cancelado')) return `<span class="pp-badge pp-badge-cancelado">${label}</span>`;
        return `<span class="pp-badge pp-badge-default">${label}</span>`;
    }

    function abrirTicketPedido(pedidoId) {
        if (!pedidoId) return;
        window.open(rutas.ticket(pedidoId), '_blank');
    }

    /* ─── Datos del carrito ──────────────────────────────────── */
    function groupedPartidas() {
        const map = new Map();
        partidas.forEach(item => {
            const key = String(item.pdp_alm_id);
            if (!map.has(key)) map.set(key, { pdp_alm_id: item.pdp_alm_id, almacen: item.almacen, items: [] });
            map.get(key).items.push(item);
        });
        return [...map.values()].map(g => ({
            ...g,
            subtotal: g.items.reduce((s, i) => s + Number(i.cantidad) * Number(i.precio), 0),
        }));
    }

    function findItemByKey(key) {
        return partidas.find(i => i.itemKey === key);
    }

    /* ─── Resumen y estado de botones ────────────────────────── */
    function updateSummary(grupos) {
        const totalPiezas  = partidas.reduce((s, i) => s + Number(i.cantidad), 0);
        const totalGeneral = grupos.reduce((s, g) => s + g.subtotal, 0);
        const hasItems     = partidas.length > 0;

        document.getElementById('pp-resumen-almacenes').textContent = grupos.length;
        document.getElementById('pp-resumen-productos').textContent = totalPiezas;
        document.getElementById('pp-resumen-total').textContent     = money(totalGeneral);

        pillSummary.classList.toggle('d-none', !hasItems);

        stickyTotal.textContent = money(totalGeneral);
        stickyBar.classList.toggle('visible', hasItems);

        const disabled = guardando || !hasItems || !puedeCrear;
        btnGenDesktop.disabled = disabled;
        btnGenMobile.disabled  = disabled;
    }

    /* ─── Render carrito ─────────────────────────────────────── */
    function renderPartidas() {
        /* Preservar estado abierto/cerrado antes de re-renderizar */
        const cerrados = new Set();
        document.querySelectorAll('.pp-accord[data-alm-id]').forEach(el => {
            if (!el.open) cerrados.add(el.dataset.almId);
        });

        const grupos = groupedPartidas();
        updateSummary(grupos);

        if (!partidas.length) {
            gruposWrap.innerHTML = `
                <div class="pp-empty">
                    <i class="tabler-shopping-cart-off"></i>
                    <div class="pp-empty__text">Escanea un producto para comenzar</div>
                </div>`;
            return;
        }

        gruposWrap.innerHTML = grupos.map(grupo => {
            const isOpen   = !cerrados.has(String(grupo.pdp_alm_id));
            const piezas   = grupo.items.reduce((s, i) => s + Number(i.cantidad), 0);
            const disabled = guardando || !puedeCrear;
            return `
            <details class="pp-accord" data-alm-id="${grupo.pdp_alm_id}" ${isOpen ? 'open' : ''}>
                <summary>
                    <div class="pp-accord__info">
                        <span class="pp-accord__name">${escapeHtml(grupo.almacen)}</span>
                        <span class="pp-accord__meta">${grupo.items.length} producto(s) · ${piezas} pieza(s)</span>
                    </div>
                    <div class="pp-accord__right">
                        <span class="pp-accord__sub">${money(grupo.subtotal)}</span>
                        <i class="tabler-chevron-down pp-accord__chevron"></i>
                    </div>
                </summary>
                <div class="pp-accord__body">
                    ${grupo.items.map(item => `
                        <div class="pp-prod-row">
                            <div class="pp-prod-row__info">
                                <div class="pp-prod-row__name">${escapeHtml(item.nombre)}</div>
                                <div class="pp-prod-row__sku">${escapeHtml(item.sku)}</div>
                                <div class="pp-prod-row__capturista">Capturó: ${escapeHtml(item.capturista || 'Sin vendedor')}</div>
                            </div>
                            <div class="pp-prod-row__right">
                                <span class="pp-prod-row__price">${money(item.precio)}</span>
                                <div class="pp-qty-ctrl">
                                    <button type="button" class="pp-qty-btn" data-k="dec" data-key="${item.itemKey}">−</button>
                                    <input type="number" min="${qtyMin(item)}" step="${qtyStep(item)}" class="pp-qty-input" data-k="cant" data-key="${item.itemKey}" value="${item.cantidad}">
                                    <button type="button" class="pp-qty-btn" data-k="inc" data-key="${item.itemKey}">+</button>
                                </div>
                                <button type="button" class="pp-del-btn" data-k="del" data-key="${item.itemKey}" title="Quitar">✕</button>
                            </div>
                        </div>`).join('')}
                    <div class="pp-accord__foot">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-action="limpiar-grupo" data-alm="${grupo.pdp_alm_id}" ${guardando ? 'disabled' : ''}>Vaciar</button>
                        <button type="button" class="btn btn-sm btn-primary" data-action="generar-grupo" data-alm="${grupo.pdp_alm_id}" ${disabled ? 'disabled' : ''}>Generar pedido</button>
                    </div>
                </div>
            </details>`;
        }).join('');
    }

    /* ─── Render pedidos generados ───────────────────────────── */
    async function cargarPedidos() {
        const q   = inpFiltro.value.trim();
        const res = await fetch(`${rutas.data}?buscar=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' } });
        const json = await res.json();
        const rows = json.data || [];

        // Actualizar badge en el tab
        const badge = document.getElementById('pp-tab-badge-listado');
        if (badge) badge.textContent = rows.length || '0';

        listaPedidos.innerHTML = rows.length
            ? rows.map(r => `
                <div class="pp-order-card">
                    <div>
                        <span class="pp-order-card__folio">${escapeHtml(r.pdp_folio)}</span>
                        <span class="pp-order-card__where">${escapeHtml(r.almacen || r.sucursal || '—')}</span>
                    </div>
                    <div class="pp-order-card__right">
                        <div class="pp-order-card__actions">
                            ${estatusBadge(r.pdp_estatus)}
                            <button type="button" class="btn btn-outline-secondary btn-sm pp-order-card__action" data-action="ticket-pedido" data-id="${r.pdp_id}" title="Reimprimir ticket" aria-label="Reimprimir ticket"><i class="ti tabler-printer"></i></button>
                            ${puedeCrear ? `<button type="button" class="btn btn-outline-primary btn-sm pp-order-card__action" data-action="editar-pedido" data-id="${r.pdp_id}" title="Editar pedido" aria-label="Editar pedido"><i class="ti tabler-pencil"></i></button>` : ''}
                            ${puedeEliminar ? `<button type="button" class="btn btn-outline-danger btn-sm pp-order-card__action" data-action="eliminar-pedido" data-id="${r.pdp_id}" data-folio="${escapeHtml(r.pdp_folio)}" title="Eliminar pedido" aria-label="Eliminar pedido"><i class="ti tabler-trash"></i></button>` : ''}
                        </div>
                        <span class="pp-order-card__total">${money(r.pdp_total)}</span>
                    </div>
                </div>`).join('')
            : `<div class="pp-orders-empty">Sin pedidos registrados</div>`;
    }

    async function cargarPedidoParaEdicion(pedidoId) {
        if (partidas.length && (!pedidoEditando || Number(pedidoEditando.pdp_id) !== Number(pedidoId))) {
            AppUI.showMessage('Validación', 'Primero guarda o cancela la captura actual antes de editar otro pedido.', 'warning');
            return;
        }

        const res = await fetch(rutas.show(pedidoId), { headers: { Accept: 'application/json' } });
        const json = await res.json().catch(() => ({}));
        if (!res.ok) {
            AppUI.showMessage('Error', json.message || 'No fue posible cargar el pedido para edición.', 'error');
            return;
        }

        const pedido = json.data || {};
        pedidoEditando = {
            pdp_id: Number(pedido.pdp_id),
            pdp_folio: pedido.pdp_folio,
            pdp_alm_id: Number(pedido.pdp_alm_id),
            pdp_scl_id: Number(pedido.pdp_scl_id),
            almacen: pedido.almacen || 'Almacén',
        };

        selectSucursal.value = String(pedidoEditando.pdp_scl_id || selectSucursal.value || '');
        notasInput.value = pedido.pdp_observaciones || '';
        partidas = Array.isArray(pedido.detalle) ? pedido.detalle.map(item => ({
            itemKey: `${pedidoEditando.pdp_alm_id}:${item.ppd_psk_id}:${Number(item.ppd_usr_id || 0)}`,
            ppd_psk_id: Number(item.ppd_psk_id),
            prd_id: null,
            pdp_alm_id: pedidoEditando.pdp_alm_id,
            almacen: pedidoEditando.almacen,
            ppd_usr_id: Number(item.ppd_usr_id || 0),
            capturista: item.capturista || 'Sin vendedor',
            permite_decimal: Boolean(item.permite_decimal),
            sku: item.sku,
            nombre: item.nombre || item.sku,
            cantidad: Number(item.cantidad || 0),
            precio: Number(item.precio || 0),
        })) : [];

        syncEditModeUi();
        renderPartidas();
        activarTab('pp-panel-captura');
        inpBuscar.focus();
    }

    function prepararEliminacionPedido(pedidoId, folio) {
        if (pedidoEditando && Number(pedidoEditando.pdp_id) === Number(pedidoId)) {
            AppUI.showMessage('Validación', 'No puedes eliminar un pedido mientras lo estás editando. Cancela la edición primero.', 'warning');
            return;
        }

        pedidoPendienteEliminar = Number(pedidoId);
        eliminarTexto.textContent = `¿Seguro que deseas eliminar el pedido ${folio}? Esta acción lo ocultará de la lista activa.`;
        modalEliminarPedido.show();
    }

    async function eliminarPedidoConfirmado() {
        if (!pedidoPendienteEliminar) return;

        const pedidoId = Number(pedidoPendienteEliminar);
        const res = await fetch(rutas.destroy(pedidoId), {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
        });

        const json = await res.json().catch(() => ({}));
        if (!res.ok) {
            const first = Object.values(json.errors || {})[0];
            throw new Error(first ? first[0] : (json.message || 'No se pudo eliminar el pedido.'));
        }

        pedidoPendienteEliminar = null;
        modalEliminarPedido.hide();
        await cargarPedidos();
        AppUI.showMessage('Listo', json.message || 'Pedido eliminado correctamente.', 'success');
    }

    /* ─── Búsqueda de productos ──────────────────────────────── */
    async function buscarProductos(q) {
        const res  = await fetch(`${rutas.buscarProductos}?q=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' } });
        if (!res.ok) { cerrarSugerencias(); return; }
        const json = await res.json();
        const data = json.data || [];
        if (!data.length) { cerrarSugerencias(); return; }

        sugerenciasActuales = data;
        sugerenciaActiva    = 0;
        boxSugerencias.innerHTML = data.map((d, idx) => `
            <button type="button" class="list-group-item list-group-item-action" data-idx="${idx}" data-psk='${JSON.stringify(d).replace(/'/g, '&#39;')}'>
                <div class="fw-semibold" style="font-size:.9rem;text-transform:none;letter-spacing:0">${escapeHtml(d.psk_nombre || d.producto?.prd_nombre || d.psk_codigo)}</div>
                <small class="text-body-secondary" style="text-transform:none;letter-spacing:0">${escapeHtml(d.psk_codigo)} · ${escapeHtml(d.psk_codigo_barras || 'Sin código de barras')} · ${money(d.psk_precio || 0)}</small>
            </button>`).join('');
        marcarSugerenciaActiva();
        boxSugerencias.classList.remove('d-none');
    }

    function marcarSugerenciaActiva() {
        [...boxSugerencias.querySelectorAll('[data-idx]')].forEach((el, i) => {
            el.classList.toggle('active', i === sugerenciaActiva);
            if (i === sugerenciaActiva) el.scrollIntoView({ block: 'nearest' });
        });
    }

    function cerrarSugerencias() {
        boxSugerencias.classList.add('d-none');
        boxSugerencias.innerHTML = '';
        sugerenciasActuales = [];
        sugerenciaActiva    = -1;
    }

    async function seleccionarActiva() {
        if (!sugerenciasActuales.length) return;
        const item = sugerenciasActuales[sugerenciaActiva >= 0 ? sugerenciaActiva : 0];
        if (!item) return;
        await addPartida(item);
        inpBuscar.value = '';
        cerrarSugerencias();
        inpBuscar.focus();
    }

    /* ─── Resolución de producto/almacén ─────────────────────── */
    async function resolverProductoAlmacen(item) {
        const sucursalId = selectSucursal.value;
        if (!sucursalId) {
            AppUI.showMessage('Validación', 'No hay una sucursal predeterminada disponible.', 'warning');
            return false;
        }
        const res = await fetch(
            `${rutas.resolverProducto}?psk_id=${encodeURIComponent(item.psk_id)}&pdp_scl_id=${encodeURIComponent(sucursalId)}`,
            { headers: { Accept: 'application/json' } }
        );
        if (res.ok) return (await res.json().catch(() => ({}))).data || null;
        const json = await res.json().catch(() => ({}));
        AppUI.showMessage('Validación', json.message || 'No fue posible asignar este producto a un almacén.', 'warning');
        return null;
    }

    function pedirAlmacenParaProducto(resolucion, item) {
        return new Promise(resolve => {
            resolverAlmacen = resolve;
            const nombre = item.psk_nombre || item.producto?.prd_nombre || resolucion.prd_nombre || item.psk_codigo;
            modalSubtitle.textContent = `"${nombre}" está disponible en varios almacenes. Elige desde cuál lo tomas.`;
            modalOptions.innerHTML = (resolucion.almacenes || []).map((a, i) => `
                <button type="button" class="pp-modal-option" data-alm-id="${a.alm_id}" data-alm-nombre="${escapeHtml(a.alm_nombre)}">
                    <div>
                        <span class="pp-modal-option__title">${escapeHtml(a.alm_nombre)}</span>
                        <span class="pp-modal-option__meta">Opción ${i + 1}</span>
                    </div>
                    <span class="pp-modal-option__icon"><i class="tabler-arrow-right"></i></span>
                </button>`).join('');
            modal.show();
        });
    }

    async function addPartida(item) {
        let res = await resolverProductoAlmacen(item);
        if (!res) return;

        if (res.requiere_seleccion) {
            const seleccion = await pedirAlmacenParaProducto(res, item);
            if (!seleccion) return;
            res = { ...res, requiere_seleccion: false, pdp_alm_id: Number(seleccion.alm_id), almacen: seleccion.alm_nombre };
        }

        if (pedidoEditando && Number(res.pdp_alm_id) !== Number(pedidoEditando.pdp_alm_id)) {
            AppUI.showMessage(
                'Validación',
                `Estás editando ${pedidoEditando.pdp_folio}. Solo puedes agregar productos del almacén ${pedidoEditando.almacen}.`,
                'warning'
            );
            return;
        }

        const capturistaId = Number(usuarioActual?.usr_id || 0);
        const capturistaNombre = usuarioActual?.usr_nombre || 'Sin vendedor';
        const key = `${res.pdp_alm_id}:${item.psk_id}:${capturistaId}`;
        const idx = partidas.findIndex(p => p.itemKey === key);
        if (idx >= 0) {
            partidas[idx].cantidad = sanitizeCantidad(
                Number(partidas[idx].cantidad) + (partidas[idx].permite_decimal ? 0.01 : 1),
                partidas[idx]
            );
        } else {
            partidas.push({
                itemKey:    key,
                ppd_psk_id: item.psk_id,
                prd_id:     res.prd_id,
                pdp_alm_id: res.pdp_alm_id,
                almacen:    res.almacen,
                ppd_usr_id: capturistaId,
                capturista: capturistaNombre,
                permite_decimal: Boolean(res.permite_decimal),
                sku:        item.psk_codigo,
                nombre:     item.psk_nombre || item.producto?.prd_nombre || item.psk_codigo,
                cantidad:   res.permite_decimal ? 0.01 : 1,
                precio:     Number(item.psk_precio || 0),
            });
        }
        renderPartidas();
    }

    /* ─── Generación de pedidos ──────────────────────────────── */
    async function savePedido(payload) {
        const editing = Boolean(pedidoEditando?.pdp_id);
        const res = await fetch(editing ? rutas.update(pedidoEditando.pdp_id) : rutas.store, {
            method: editing ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify(payload),
        });
        const json = await res.json().catch(() => ({}));
        if (!res.ok) {
            const first = Object.values(json.errors || {})[0];
            throw new Error(first ? first[0] : (json.message || 'No se pudo guardar el pedido.'));
        }
        return json;
    }

    async function generarPedidoGrupo(almacenId) {
        const grupo = groupedPartidas().find(g => Number(g.pdp_alm_id) === Number(almacenId));
        if (!grupo) return;

        guardando = true;
        renderPartidas();
        try {
            const json = await savePedido({
                pdp_scl_id:        selectSucursal.value,
                pdp_alm_id:        grupo.pdp_alm_id,
                pdp_observaciones: notasInput.value,
                partidas: grupo.items.map(i => ({ ppd_psk_id: i.ppd_psk_id, ppd_cantidad: i.cantidad, ppd_usr_id: i.ppd_usr_id })),
            });
            const mensaje = pedidoEditando
                ? `Pedido ${json.data.pdp_folio} actualizado correctamente.`
                : `Pedido ${json.data.pdp_folio} generado para ${grupo.almacen}.`;
            resetEditorState();
            await cargarPedidos();
            abrirTicketPedido(Number(json.data.pdp_id || 0));
            AppUI.showMessage('Listo', mensaje, 'success');
        } catch (err) {
            AppUI.showMessage('Error', err.message || 'No se pudo generar el pedido.', 'error');
        } finally {
            guardando = false;
            renderPartidas();
        }
    }

    async function generarTodos() {
        const grupos = groupedPartidas();
        if (!grupos.length) {
            AppUI.showMessage('Validación', 'Agrega al menos un producto al carrito.', 'warning');
            return;
        }
        guardando = true;
        renderPartidas();
        const folios = [];
        try {
            if (pedidoEditando) {
                const grupo = grupos[0];
                const json = await savePedido({
                    pdp_scl_id:        selectSucursal.value,
                    pdp_alm_id:        grupo.pdp_alm_id,
                    pdp_observaciones: notasInput.value,
                    partidas: grupo.items.map(i => ({ ppd_psk_id: i.ppd_psk_id, ppd_cantidad: i.cantidad, ppd_usr_id: i.ppd_usr_id })),
                });
                resetEditorState();
                await cargarPedidos();
                abrirTicketPedido(Number(json.data.pdp_id || 0));
                AppUI.showMessage('Listo', `Pedido ${json.data.pdp_folio} actualizado correctamente.`, 'success');
                return;
            }

            for (const grupo of grupos) {
                const json = await savePedido({
                    pdp_scl_id:        selectSucursal.value,
                    pdp_alm_id:        grupo.pdp_alm_id,
                    pdp_observaciones: notasInput.value,
                    partidas: grupo.items.map(i => ({ ppd_psk_id: i.ppd_psk_id, ppd_cantidad: i.cantidad, ppd_usr_id: i.ppd_usr_id })),
                });
                folios.push(`${grupo.almacen}: ${json.data.pdp_folio}`);
                abrirTicketPedido(Number(json.data.pdp_id || 0));
            }
            resetEditorState();
            await cargarPedidos();
            AppUI.showMessage('Listo', `${folios.length} pedido(s) generado(s).\n${folios.join('\n')}`, 'success');
        } catch (err) {
            AppUI.showMessage('Error', err.message || 'No se pudieron generar todos los pedidos.', 'error');
        } finally {
            guardando = false;
            renderPartidas();
        }
    }

    /* ─── Eventos: búsqueda ──────────────────────────────────── */
    inpBuscar.addEventListener('input', () => {
        clearTimeout(timer);
        const q = inpBuscar.value.trim();
        if (q.length < 2) { cerrarSugerencias(); return; }
        timer = setTimeout(() => buscarProductos(q), 200);
    });

    inpBuscar.addEventListener('keydown', e => {
        if (e.key === 'Escape') { e.preventDefault(); cerrarSugerencias(); return; }
        if (e.key === 'ArrowDown' && sugerenciasActuales.length) {
            e.preventDefault();
            sugerenciaActiva = Math.min(sugerenciasActuales.length - 1, sugerenciaActiva < 0 ? 0 : sugerenciaActiva + 1);
            marcarSugerenciaActiva();
        } else if (e.key === 'ArrowUp' && sugerenciasActuales.length) {
            e.preventDefault();
            sugerenciaActiva = Math.max(0, sugerenciaActiva < 0 ? 0 : sugerenciaActiva - 1);
            marcarSugerenciaActiva();
        } else if (e.key === 'Enter' && sugerenciasActuales.length) {
            e.preventDefault();
            seleccionarActiva();
        }
    });

    document.addEventListener('click', e => {
        if (!e.target.closest('#buscar_producto') && !e.target.closest('#sugerencias')) cerrarSugerencias();
    });

    boxSugerencias.addEventListener('click', async e => {
        const btn = e.target.closest('[data-psk]');
        if (!btn) return;
        await addPartida(JSON.parse(btn.getAttribute('data-psk')));
        inpBuscar.value = '';
        cerrarSugerencias();
        inpBuscar.focus();
    });

    listaPedidos.addEventListener('click', async e => {
        const ticketBtn = e.target.closest('[data-action="ticket-pedido"]');
        if (ticketBtn) {
            abrirTicketPedido(Number(ticketBtn.dataset.id));
            return;
        }

        const editBtn = e.target.closest('[data-action="editar-pedido"]');
        if (editBtn) {
            await cargarPedidoParaEdicion(Number(editBtn.dataset.id));
            return;
        }

        const deleteBtn = e.target.closest('[data-action="eliminar-pedido"]');
        if (deleteBtn) {
            prepararEliminacionPedido(Number(deleteBtn.dataset.id), deleteBtn.dataset.folio || 'este pedido');
        }
    });

    /* ─── Eventos: carrito ───────────────────────────────────── */
    gruposWrap.addEventListener('input', e => {
        const el = e.target.closest('[data-k]');
        if (!el || el.dataset.k !== 'cant') return;
        const item = findItemByKey(el.dataset.key);
        if (!item) return;
        // Actualiza el dato en memoria y los totales, sin re-renderizar el DOM
        // para que el usuario pueda escribir libremente sin perder el foco.
        const val = parseFloat(el.value);
        if (!isNaN(val) && val > 0) {
            item.cantidad = item.permite_decimal ? val : sanitizeCantidad(val, item);
            updateSummary(groupedPartidas());
        }
    });

    gruposWrap.addEventListener('blur', e => {
        const el = e.target.closest('[data-k]');
        if (!el || el.dataset.k !== 'cant') return;
        const item = findItemByKey(el.dataset.key);
        if (!item) return;
        // Al salir del campo, normaliza el valor y re-renderiza
        item.cantidad = sanitizeCantidad(el.value, item);
        renderPartidas();
    }, true); // useCapture para capturar blur (no burbujea)

    gruposWrap.addEventListener('click', async e => {
        const el = e.target.closest('[data-k], [data-action]');
        if (!el || guardando) return;

        if (el.dataset.action === 'limpiar-grupo') {
            partidas = partidas.filter(i => Number(i.pdp_alm_id) !== Number(el.dataset.alm));
            renderPartidas();
            return;
        }
        if (el.dataset.action === 'generar-grupo') {
            await generarPedidoGrupo(Number(el.dataset.alm));
            return;
        }

        if (el.dataset.k === 'cant') return; // el input lo maneja el evento blur, no el click

        const item = findItemByKey(el.dataset.key);
        if (!item) return;
        if      (el.dataset.k === 'inc') item.cantidad = sanitizeCantidad(Number(item.cantidad) + (item.permite_decimal ? 0.01 : 1), item);
        else if (el.dataset.k === 'dec') item.cantidad = sanitizeCantidad(Number(item.cantidad) - (item.permite_decimal ? 0.01 : 1), item);
        else if (el.dataset.k === 'del') partidas = partidas.filter(r => r.itemKey !== item.itemKey);
        renderPartidas();
    });

    /* ─── Eventos: modal almacén ─────────────────────────────── */
    modalOptions.addEventListener('click', e => {
        const btn = e.target.closest('[data-alm-id]');
        if (!btn || !resolverAlmacen) return;
        const resolve = resolverAlmacen;
        resolverAlmacen = null;
        modal.hide();
        resolve({ alm_id: Number(btn.dataset.almId), alm_nombre: btn.dataset.almNombre });
    });

    modalEl.addEventListener('hidden.bs.modal', () => {
        if (resolverAlmacen) { const r = resolverAlmacen; resolverAlmacen = null; r(null); }
        modalOptions.innerHTML = '';
        inpBuscar.focus();
    });

    /* ─── Eventos: generar ───────────────────────────────────── */
    btnGenDesktop.addEventListener('click', generarTodos);
    btnGenMobile.addEventListener('click',  generarTodos);
    btnCancelarEdicion.addEventListener('click', () => {
        resetEditorState();
        AppUI.showMessage('Listo', 'La edición del pedido fue cancelada.', 'success');
    });
    btnConfirmarEliminarPedido.addEventListener('click', async () => {
        btnConfirmarEliminarPedido.disabled = true;
        try {
            await eliminarPedidoConfirmado();
        } catch (error) {
            AppUI.showMessage('Error', error.message || 'No se pudo eliminar el pedido.', 'error');
        } finally {
            btnConfirmarEliminarPedido.disabled = false;
        }
    });
    modalEliminarPedidoEl.addEventListener('hidden.bs.modal', () => {
        pedidoPendienteEliminar = null;
    });

    /* ─── Eventos: filtro pedidos ────────────────────────────── */
    inpFiltro.addEventListener('input', () => cargarPedidos());

    /* ─── Inicio ─────────────────────────────────────────────── */
    syncEditModeUi();
    renderPartidas();
    cargarPedidos();
})();
</script>
@endpush
