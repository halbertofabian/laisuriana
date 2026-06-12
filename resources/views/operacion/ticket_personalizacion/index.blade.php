@extends('layouts.app')

@section('title', 'Diseñador de ticket')

{{-- ─── CRITICAL: must be vendor-styles — that's the only CSS stack in layouts.app ─── --}}
@push('vendor-styles')
<style>
/* ═══════════════════════════════════════════════════════════════════
   TICKET PERSONALIZATION EDITOR
   Inspired by: Shopify Theme Editor · Figma Properties Panel
   All custom tokens come from --ls-* (defined in layouts/app.blade.php)
═══════════════════════════════════════════════════════════════════ */

/* ── Editor shell: fixed-height on desktop so both panels scroll ── */
.tpe-shell {
    display: grid;
    grid-template-columns: 360px minmax(0, 1fr);
    height: calc(100vh - 190px);
    min-height: 520px;
    background: var(--ls-surface);
    border: 1px solid var(--ls-border);
    border-radius: var(--ls-radius-xl);
    box-shadow: var(--ls-shadow);
    overflow: hidden;   /* clips the rounded corners */
}

/* ── Config column ────────────────────────────────────────────── */
.tpe-config {
    display: flex;
    flex-direction: column;
    border-right: 1px solid var(--ls-border);
    overflow: hidden;
}

.tpe-config__top {
    flex: 0 0 auto;
    padding: 1rem 1.25rem .8rem;
    border-bottom: 1px solid var(--ls-border);
    background: var(--ls-surface);
}
.tpe-config__heading {
    display: flex;
    align-items: center;
    gap: .5rem;
    font-size: .88rem;
    font-weight: 700;
    color: var(--ls-text-primary);
    margin: 0 0 .1rem;
}
.tpe-config__sub {
    font-size: .74rem;
    color: var(--ls-text-muted);
    margin: 0;
}

/* changes badge in config top */
.tpe-delta {
    display: inline-flex;
    align-items: center;
    gap: .28rem;
    padding: .16rem .48rem;
    border-radius: 999px;
    font-size: .67rem;
    font-weight: 700;
    background: rgba(233,155,62,.1);
    border: 1px solid rgba(233,155,62,.22);
    color: #b45309;
    opacity: 0;
    transform: scale(.86);
    transition: opacity .2s, transform .2s;
}
.tpe-delta.show { opacity: 1; transform: scale(1); }

/* form must be a flex container so .tpe-sections can constrain its height */
#tpe-form {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    min-height: 0;
    overflow: hidden;
}

.tpe-sections {
    flex: 1 1 auto;
    overflow-y: auto;
    overflow-x: hidden;
    min-height: 0;   /* required: flex items default to min-height:auto which ignores overflow */
}

.tpe-config__foot {
    flex: 0 0 auto;
    padding: .85rem 1.25rem;
    border-top: 1px solid var(--ls-border);
    background: var(--ls-surface-2);
}

/* ── Accordion ────────────────────────────────────────────────── */
.tpe-section + .tpe-section { border-top: 1px solid var(--ls-border); }

.tpe-sec-btn {
    width: 100%;
    display: flex;
    align-items: center;
    gap: .7rem;
    padding: .8rem 1.25rem;
    background: none;
    border: none;
    cursor: pointer;
    text-align: left;
    transition: background .14s;
}
.tpe-sec-btn:hover { background: var(--ls-surface-2); }

.tpe-sec-icon {
    width: 30px;
    height: 30px;
    border-radius: var(--ls-radius);
    background: var(--ls-surface-3);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: .85rem;
    color: var(--ls-text-muted);
    flex: 0 0 auto;
    transition: background .18s, color .18s;
}
.tpe-section.is-open .tpe-sec-icon {
    background: var(--ls-accent-light);
    color: var(--ls-accent);
}

.tpe-sec-meta { flex: 1; min-width: 0; }
.tpe-sec-name {
    display: block;
    font-size: .82rem;
    font-weight: 700;
    color: var(--ls-text-primary);
    line-height: 1.2;
}
.tpe-sec-desc {
    display: block;
    font-size: .71rem;
    color: var(--ls-text-muted);
    margin-top: .07rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 175px;
    transition: color .15s;
}
.tpe-section.is-open .tpe-sec-desc { color: var(--ls-accent); }

.tpe-sec-chevron {
    font-size: .82rem;
    color: var(--ls-text-muted);
    flex: 0 0 auto;
    transition: transform .24s cubic-bezier(.4,0,.2,1);
}
.tpe-section.is-open .tpe-sec-chevron { transform: rotate(180deg); }

.tpe-sec-body {
    overflow: hidden;
    max-height: 0;
    opacity: 0;
    transition: max-height .3s cubic-bezier(.4,0,.2,1), opacity .22s ease;
}
.tpe-section.is-open .tpe-sec-body { opacity: 1; }
.tpe-sec-inner { padding: .35rem 1.25rem 1.25rem; }

/* ── Logo dropzone ────────────────────────────────────────────── */
.tpe-drop {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px dashed var(--ls-border);
    border-radius: var(--ls-radius-lg);
    background: var(--ls-surface-2);
    min-height: 106px;
    cursor: pointer;
    overflow: hidden;
    transition: border-color .18s, background .18s, box-shadow .18s;
}
.tpe-drop input[type="file"] {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
    z-index: 1;
}
.tpe-drop:hover,
.tpe-drop.drag { border-color: var(--ls-accent); background: var(--ls-accent-light); box-shadow: 0 0 0 3px rgba(99,91,255,.1); }

.tpe-drop-empty {
    pointer-events: none;
    text-align: center;
    padding: 1.25rem 1rem;
    width: 100%;
}
.tpe-drop-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: var(--ls-surface-3);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: var(--ls-text-muted);
    margin-bottom: .5rem;
    transition: background .18s, color .18s;
}
.tpe-drop:hover .tpe-drop-icon,
.tpe-drop.drag .tpe-drop-icon { background: var(--ls-accent-light); color: var(--ls-accent); }
.tpe-drop-cta { display: block; font-size: .79rem; font-weight: 700; color: var(--ls-text-secondary); }
.tpe-drop-hint { display: block; font-size: .69rem; color: var(--ls-text-muted); margin-top: .18rem; }

.tpe-drop-file {
    pointer-events: none;
    display: flex;
    align-items: center;
    gap: .8rem;
    padding: .75rem 1rem;
    width: 100%;
}
.tpe-drop-thumb {
    width: 50px;
    height: 50px;
    border-radius: var(--ls-radius);
    object-fit: contain;
    border: 1px solid var(--ls-border);
    background: #fff;
    flex: 0 0 auto;
    box-shadow: var(--ls-shadow-sm);
}
.tpe-drop-fname { font-size: .79rem; font-weight: 700; color: var(--ls-text-primary); display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px; }
.tpe-drop-replace { font-size: .69rem; color: var(--ls-text-muted); display: block; margin-top: .1rem; }

/* ── Char counter overlay ─────────────────────────────────────── */
.tpe-ta-wrap { position: relative; }
.tpe-ta-wrap textarea { padding-bottom: 1.65rem !important; }
.tpe-cnt {
    position: absolute;
    bottom: .48rem;
    right: .6rem;
    font-size: .65rem;
    font-weight: 600;
    color: var(--ls-text-muted);
    pointer-events: none;
    background: var(--ls-surface);
    padding: 1px .22rem;
    border-radius: 3px;
    line-height: 1.4;
    transition: color .14s;
}
.tpe-cnt.warn { color: var(--ls-warning); }
.tpe-cnt.over { color: var(--ls-danger); }

/* ── Saved flash ──────────────────────────────────────────────── */
.tpe-saved {
    display: inline-flex;
    align-items: center;
    gap: .38rem;
    font-size: .79rem;
    font-weight: 600;
    color: var(--ls-success);
    opacity: 0;
    transform: translateY(4px);
    transition: opacity .28s, transform .28s;
    pointer-events: none;
}
.tpe-saved.show { opacity: 1; transform: translateY(0); }

/* ── Preview column ───────────────────────────────────────────── */
.tpe-preview {
    display: flex;
    flex-direction: column;
    overflow: hidden;

    /* dot-grid stage */
    background-color: #ebeff9;
    background-image:
        radial-gradient(ellipse at 15% 8%,  rgba(99,91,255,.11) 0%, transparent 48%),
        radial-gradient(ellipse at 85% 92%,  rgba(26,158,109,.08) 0%, transparent 48%),
        radial-gradient(circle, rgba(99,91,255,.055) 1px, transparent 1px);
    background-size: auto, auto, 22px 22px;
}

/* frosted-glass top bar */
.tpe-prev-bar {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: .75rem 1.35rem;
    background: rgba(255,255,255,.5);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(255,255,255,.55);
}
.tpe-prev-title { font-size: .82rem; font-weight: 700; color: var(--ls-text-primary); }
.tpe-prev-sub   { font-size: .71rem; color: var(--ls-text-muted); margin-top: .05rem; }

.tpe-live-pill {
    display: inline-flex;
    align-items: center;
    gap: .36rem;
    padding: .2rem .52rem;
    border-radius: 999px;
    background: rgba(255,255,255,.85);
    border: 1px solid rgba(26,158,109,.22);
    font-size: .65rem;
    font-weight: 800;
    color: var(--ls-success);
    letter-spacing: .04em;
    text-transform: uppercase;
}
.tpe-live-dot {
    width: 5px;
    height: 5px;
    border-radius: 999px;
    background: var(--ls-success);
    animation: tpePulse 1.9s ease-in-out infinite;
}
@keyframes tpePulse {
    0%,100% { opacity:1; transform:scale(1); }
    50%      { opacity:.4; transform:scale(.65); }
}

/* scrollable stage area */
.tpe-stage {
    flex: 1 1 auto;
    overflow-y: auto;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 2rem 1.5rem 3rem;
}

/* ── Ticket paper ─────────────────────────────────────────────── */
.tpe-paper-wrap {
    width: 256px;
    flex: 0 0 auto;
    animation: tpeIn .38s cubic-bezier(.2,.9,.25,1) both;
}
@keyframes tpeIn {
    from { opacity:0; transform:translateY(10px) scale(.98); }
    to   { opacity:1; transform:translateY(0)    scale(1);   }
}

/* The receipt — slightly warm thermal-paper white */
.tpe-paper {
    background: #fffef8;
    border-radius: 4px 4px 0 0;
    padding: 1rem .88rem .9rem;
    font-family: "SFMono-Regular","Menlo","Consolas",monospace;
    color: #1a1f2e;
    /* layered shadows for depth */
    filter:
        drop-shadow(0 2px 4px rgba(10,37,64,.07))
        drop-shadow(0 8px 20px rgba(10,37,64,.1))
        drop-shadow(0 28px 56px rgba(10,37,64,.13));
}

/* zigzag torn edge */
.tpe-paper-edge {
    height: 18px;
    background-image:
        linear-gradient(135deg, #fffef8 33.33%, transparent 33.33%),
        linear-gradient(-135deg, #fffef8 33.33%, transparent 33.33%);
    background-size: 9px 18px;
    background-repeat: repeat-x;
    background-position: 0 0, 4.5px 0;
    filter:
        drop-shadow(0 2px 4px rgba(10,37,64,.07))
        drop-shadow(0 8px 20px rgba(10,37,64,.1));
}

/* ticket internals */
.tpk-logo {
    width: 60px; height: 60px;
    object-fit: contain; border-radius: 10px;
    border: 1px solid rgba(0,0,0,.06); background: #fff;
    display: block; margin: 0 auto .5rem;
    transition: opacity .18s;
}
.tpk-brand { text-align:center; font-weight:800; font-size:.88rem; color:#0a1628; margin-bottom:.18rem; }
.tpk-hdr {
    font-size:.63rem; line-height:1.55; text-align:center; color:#475569;
    white-space:pre-line; min-height:.8em; transition:all .12s;
}
.tpk-rule { border:none; border-top:1px dashed #c8d6e5; margin:.52rem 0; }
.tpk-date { text-align:center; font-size:.57rem; color:#94a3b8; margin-bottom:.38rem; }

.tpk-meta, .tpk-totals { width:100%; font-size:.62rem; }
.tpk-meta td, .tpk-totals td { padding:.07rem 0; vertical-align:top; }
.tpk-meta td:first-child  { color:#64748b; width:46%; }
.tpk-meta td:last-child   { font-weight:600; text-align:right; }
.tpk-totals td:first-child { color:#64748b; }
.tpk-totals td:last-child  { text-align:right; font-weight:600; }
.tpk-total-row td { font-weight:800; font-size:.7rem; color:#0a1628; }

.tpk-items { width:100%; font-size:.59rem; }
.tpk-items th { color:#94a3b8; font-size:.55rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; padding-bottom:.22rem; }
.tpk-items td { padding:.14rem 0; vertical-align:top; color:#1e293b; }

.tpk-ftr {
    font-size:.63rem; line-height:1.55; text-align:center; color:#475569;
    white-space:pre-line; min-height:.8em; transition:all .12s;
}
.tpk-barcode {
    height:36px; border-radius:3px; margin-top:.52rem;
    background: repeating-linear-gradient(
        90deg,
        #1a1f2e 0 2px,  #fffef8 2px 4px,
        #1a1f2e 4px 5px, #fffef8 5px 8px,
        #1a1f2e 8px 10px, #fffef8 10px 13px,
        #1a1f2e 13px 14px, #fffef8 14px 17px,
        #1a1f2e 17px 18px, #fffef8 18px 21px
    );
}
.tpk-folio { text-align:center; margin-top:.28rem; font-size:.59rem; letter-spacing:.1em; color:#94a3b8; }

/* width ruler below ticket */
.tpe-ruler {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .4rem;
    margin-top: .9rem;
    font-size: .67rem;
    font-weight: 600;
    color: rgba(100,116,139,.65);
}
.tpe-ruler::before, .tpe-ruler::after {
    content: '';
    flex: 1;
    height: 1px;
    background: rgba(100,116,139,.2);
}

/* ── Mobile: preview first, config stacks below ───────────────── */
@media (max-width: 1100px) {
    .tpe-shell { grid-template-columns: 300px minmax(0,1fr); }
}
@media (max-width: 991.98px) {
    .tpe-shell {
        display: flex;
        flex-direction: column;
        height: auto;
        min-height: 0;
    }
    .tpe-preview {
        order: 1;
        min-height: 380px;
        border-radius: 0;
    }
    .tpe-config {
        order: 2;
        border-right: none;
        border-top: 1px solid var(--ls-border);
        overflow: visible; /* let content breathe on mobile */
    }
    .tpe-sections { overflow: visible; }
    .tpe-paper-wrap { width: 230px; }
    .tpe-stage { padding: 1.5rem 1rem 2rem; }
}
@media (max-width: 575.98px) {
    .tpe-preview  { min-height: 330px; }
    .tpe-paper-wrap { width: 210px; }
}
</style>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    {{-- ── Page header ──────────────────────────────────────────── --}}
    <div class="app-section-header mb-3">
        <div class="app-section-header__content">
            <div class="app-section-header__main">
                <span class="app-section-header__icon">
                    <i class="ti tabler-receipt-2"></i>
                </span>
                <div>
                    <p class="app-section-header__eyebrow">Red y gestión</p>
                    <h1 class="app-section-header__title">Diseñador de ticket</h1>
                    <p class="app-section-header__subtitle">Los cambios se reflejan en tiempo real</p>
                </div>
            </div>
            <div class="app-section-header__actions">
                <span class="tpe-delta" id="tpe-delta">
                    <i class="ti tabler-pencil" style="font-size:.78em;"></i>
                    <span id="tpe-delta-n">0</span>&nbsp;cambio(s) sin guardar
                </span>
                <span class="tpe-saved" id="tpe-saved">
                    <i class="ti tabler-circle-check fs-5"></i> Guardado
                </span>
            </div>
        </div>
    </div>

    {{-- ── Editor shell ──────────────────────────────────────────── --}}
    <div class="tpe-shell">

        {{-- ─── Config column (left, 360px) ─────────────────────── --}}
        <div class="tpe-config">

            {{-- Panel header --}}
            <div class="tpe-config__top">
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <div class="tpe-config__heading">
                        <i class="ti tabler-adjustments-horizontal" style="color:var(--ls-text-muted);"></i>
                        Configuración
                    </div>
                </div>
                <p class="tpe-config__sub">Ajusta cada sección y ve el resultado en vivo</p>
            </div>

            {{-- Form --}}
            <form id="tpe-form"
                  method="POST"
                  action="{{ route('operacion.ticket_personalizacion.update') }}"
                  enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="tpe-sections">

                    {{-- ── Section 1: Apariencia (open by default) ── --}}
                    <div class="tpe-section is-open" id="sec-apariencia">
                        <button type="button" class="tpe-sec-btn" data-toggle>
                            <span class="tpe-sec-icon"><i class="ti tabler-photo"></i></span>
                            <span class="tpe-sec-meta">
                                <span class="tpe-sec-name">Apariencia</span>
                                <span class="tpe-sec-desc" id="desc-logo">
                                    {{ $logoUrl ? 'Logo configurado' : 'Sin logo' }}
                                </span>
                            </span>
                            <i class="ti tabler-chevron-down tpe-sec-chevron"></i>
                        </button>
                        <div class="tpe-sec-body">
                            <div class="tpe-sec-inner">

                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <label class="form-label mb-0" style="font-size:.78rem;">
                                        Logo del ticket
                                        <i class="ti tabler-info-circle ms-1"
                                           style="font-size:.8em;color:var(--ls-text-muted);cursor:help;vertical-align:middle;"
                                           title="Imagen cuadrada — PNG, JPG o WebP — máximo 3 MB"></i>
                                    </label>
                                    <button type="button" id="tpe-logo-clear"
                                            class="btn btn-ghost-action danger {{ $logoUrl ? '' : 'd-none' }}"
                                            style="font-size:.72rem;padding:.22rem .5rem;">
                                        <i class="ti tabler-trash me-1"></i>Quitar
                                    </button>
                                </div>

                                <label class="tpe-drop" id="tpe-drop" for="logo">
                                    <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp">

                                    {{-- empty state --}}
                                    <div class="tpe-drop-empty" id="tpe-drop-empty" {{ $logoUrl ? 'style=display:none' : '' }}>
                                        <div class="tpe-drop-icon"><i class="ti tabler-cloud-upload"></i></div>
                                        <span class="tpe-drop-cta">Arrastra tu logo aquí</span>
                                        <span class="tpe-drop-hint">o haz clic · PNG, JPG, WebP · 3 MB máx.</span>
                                    </div>

                                    {{-- file preview --}}
                                    <div class="tpe-drop-file" id="tpe-drop-file" {{ $logoUrl ? '' : 'style=display:none' }}>
                                        <img class="tpe-drop-thumb" id="tpe-drop-thumb"
                                             src="{{ $logoUrl ?? '' }}" alt="Logo">
                                        <div>
                                            <span class="tpe-drop-fname" id="tpe-drop-fname">Logo configurado</span>
                                            <span class="tpe-drop-replace">Haz clic para cambiar</span>
                                        </div>
                                    </div>
                                </label>

                                @error('logo')
                                    <p class="mb-0 mt-1" style="font-size:.75rem;color:var(--ls-danger);">
                                        <i class="ti tabler-alert-circle me-1"></i>{{ $message }}
                                    </p>
                                @enderror

                            </div>
                        </div>
                    </div>

                    {{-- ── Section 2: Encabezado ─────────────────── --}}
                    <div class="tpe-section" id="sec-encabezado">
                        <button type="button" class="tpe-sec-btn" data-toggle>
                            <span class="tpe-sec-icon"><i class="ti tabler-building-store"></i></span>
                            <span class="tpe-sec-meta">
                                <span class="tpe-sec-name">Encabezado</span>
                                <span class="tpe-sec-desc" id="desc-header">
                                    {{ $config->ptc_texto_encabezado
                                        ? Str::limit($config->ptc_texto_encabezado, 30)
                                        : 'Sin mensaje de encabezado' }}
                                </span>
                            </span>
                            <i class="ti tabler-chevron-down tpe-sec-chevron"></i>
                        </button>
                        <div class="tpe-sec-body">
                            <div class="tpe-sec-inner">
                                <label for="texto_encabezado" class="form-label" style="font-size:.78rem;">
                                    Texto del encabezado
                                </label>
                                <div class="tpe-ta-wrap">
                                    <textarea
                                        class="form-control @error('texto_encabezado') is-invalid @enderror"
                                        id="texto_encabezado"
                                        name="texto_encabezado"
                                        rows="4"
                                        maxlength="4000"
                                        placeholder="Ej: ¡Gracias por visitarnos!&#10;Cambios únicamente con ticket."
                                        style="resize:vertical;"
                                    >{{ old('texto_encabezado', $config->ptc_texto_encabezado) }}</textarea>
                                    <span class="tpe-cnt" id="cnt-hdr">0</span>
                                </div>
                                <p class="mb-0 mt-1" style="font-size:.73rem;color:var(--ls-text-muted);">
                                    Aparece entre el logo y el detalle de venta.
                                </p>
                                @error('texto_encabezado')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- ── Section 3: Pie ────────────────────────── --}}
                    <div class="tpe-section" id="sec-pie">
                        <button type="button" class="tpe-sec-btn" data-toggle>
                            <span class="tpe-sec-icon"><i class="ti tabler-align-bottom"></i></span>
                            <span class="tpe-sec-meta">
                                <span class="tpe-sec-name">Pie de ticket</span>
                                <span class="tpe-sec-desc" id="desc-footer">
                                    {{ $config->ptc_texto_pie
                                        ? Str::limit($config->ptc_texto_pie, 30)
                                        : 'Sin mensaje de cierre' }}
                                </span>
                            </span>
                            <i class="ti tabler-chevron-down tpe-sec-chevron"></i>
                        </button>
                        <div class="tpe-sec-body">
                            <div class="tpe-sec-inner">
                                <label for="texto_pie" class="form-label" style="font-size:.78rem;">
                                    Mensaje de cierre
                                </label>
                                <div class="tpe-ta-wrap">
                                    <textarea
                                        class="form-control @error('texto_pie') is-invalid @enderror"
                                        id="texto_pie"
                                        name="texto_pie"
                                        rows="4"
                                        maxlength="4000"
                                        placeholder="Ej: Gracias por su compra.&#10;¡Vuelva pronto!"
                                        style="resize:vertical;"
                                    >{{ old('texto_pie', $config->ptc_texto_pie) }}</textarea>
                                    <span class="tpe-cnt" id="cnt-ftr">0</span>
                                </div>
                                <p class="mb-0 mt-1" style="font-size:.73rem;color:var(--ls-text-muted);">
                                    Aparece antes del código de barras.
                                </p>
                                @error('texto_pie')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                </div>{{-- /tpe-sections --}}

                {{-- Footer: save --}}
                <div class="tpe-config__foot">
                    <button type="submit" class="btn btn-primary w-100" id="tpe-save-btn">
                        <i class="ti tabler-device-floppy me-1"></i>
                        <span id="tpe-save-lbl">Guardar cambios</span>
                    </button>
                </div>

            </form>
        </div>

        {{-- ─── Preview column (right, fills remaining) ──────────── --}}
        <div class="tpe-preview">

            {{-- Glass bar --}}
            <div class="tpe-prev-bar">
                <div>
                    <div class="tpe-prev-title">Vista previa</div>
                    <div class="tpe-prev-sub">Exactamente como se imprimirá</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="tpe-live-pill">
                        <span class="tpe-live-dot"></span>En vivo
                    </span>
                    <span class="ls-badge ls-badge-neutral" style="font-size:.68rem;">
                        <i class="ti tabler-ruler-measure me-1"></i>80 mm
                    </span>
                </div>
            </div>

            {{-- Scrollable stage --}}
            <div class="tpe-stage">
                <div>
                    <div class="tpe-paper-wrap">

                        {{-- Receipt paper --}}
                        <div class="tpe-paper">

                            <img id="prev-logo"
                                 class="tpk-logo"
                                 src="{{ $logoUrl ?? '' }}"
                                 alt="Logo"
                                 style="{{ $logoUrl ? '' : 'display:none;' }}">

                            <div class="tpk-brand">{{ $preview['empresa'] }}</div>
                            <div id="prev-hdr" class="tpk-hdr">{{ $config->ptc_texto_encabezado }}</div>

                            <div class="tpk-rule"></div>
                            <div class="tpk-date">{{ $preview['fecha'] }}</div>

                            <table class="tpk-meta">
                                <tr><td>Almacén</td>   <td>{{ $preview['almacen'] }}</td></tr>
                                <tr><td>Cliente</td>   <td>{{ $preview['cliente'] }}</td></tr>
                                <tr><td>Artículos</td> <td>{{ $preview['articulos'] }}</td></tr>
                                <tr>
                                    <td>Vendedores</td>
                                    <td style="word-break:break-word;max-width:7rem;">{{ $preview['vendedores'] }}</td>
                                </tr>
                            </table>

                            <div class="tpk-rule"></div>

                            <table class="tpk-items">
                                <thead>
                                    <tr>
                                        <th align="left">Producto</th>
                                        <th align="right">Cant.</th>
                                        <th align="right">Imp.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($preview['items'] as $item)
                                        <tr>
                                            <td>{{ $item['nombre'] }}</td>
                                            <td align="right">{{ $item['cantidad'] }}</td>
                                            <td align="right">{{ $item['importe'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <div class="tpk-rule"></div>

                            <table class="tpk-totals">
                                <tr><td>Subtotal</td><td>$1,500.00</td></tr>
                                <tr><td>Descuento</td><td>$0.00</td></tr>
                                <tr class="tpk-total-row"><td>TOTAL</td><td>$1,500.00</td></tr>
                            </table>

                            <div class="tpk-rule"></div>

                            <div id="prev-ftr" class="tpk-ftr">{{ $config->ptc_texto_pie ?: 'Gracias por su compra' }}</div>
                            <div class="tpk-barcode"></div>
                            <div class="tpk-folio">{{ $preview['folio'] }}</div>

                        </div>
                        {{-- zigzag torn edge --}}
                        <div class="tpe-paper-edge"></div>

                    </div>

                    {{-- Width ruler --}}
                    <div class="tpe-ruler">80 mm</div>
                </div>
            </div>

        </div>

    </div>
</div>
@endsection

@push('page-scripts')
<script>
(() => {
    'use strict';

    /* ── DOM ─────────────────────────────────────────────── */
    const drop       = document.getElementById('tpe-drop');
    const logoInput  = document.getElementById('logo');
    const dropEmpty  = document.getElementById('tpe-drop-empty');
    const dropFile   = document.getElementById('tpe-drop-file');
    const dropThumb  = document.getElementById('tpe-drop-thumb');
    const dropFname  = document.getElementById('tpe-drop-fname');
    const logoClear  = document.getElementById('tpe-logo-clear');

    const headerTa   = document.getElementById('texto_encabezado');
    const footerTa   = document.getElementById('texto_pie');

    const prevLogo   = document.getElementById('prev-logo');
    const prevHdr    = document.getElementById('prev-hdr');
    const prevFtr    = document.getElementById('prev-ftr');

    const cntHdr     = document.getElementById('cnt-hdr');
    const cntFtr     = document.getElementById('cnt-ftr');

    const descLogo   = document.getElementById('desc-logo');
    const descHeader = document.getElementById('desc-header');
    const descFooter = document.getElementById('desc-footer');

    const delta      = document.getElementById('tpe-delta');
    const deltaN     = document.getElementById('tpe-delta-n');
    const saved      = document.getElementById('tpe-saved');
    const form       = document.getElementById('tpe-form');
    const saveBtn    = document.getElementById('tpe-save-btn');
    const saveLbl    = document.getElementById('tpe-save-lbl');

    /* ── State ───────────────────────────────────────────── */
    let changes = 0;
    let objUrl  = null;

    /* ── Accordion init & toggle ─────────────────────────── */
    function initAccordion() {
        document.querySelectorAll('.tpe-section').forEach((sec) => {
            const body = sec.querySelector('.tpe-sec-body');
            const inner = sec.querySelector('.tpe-sec-inner');
            if (sec.classList.contains('is-open')) {
                /* open on load: skip transition */
                body.style.transition = 'none';
                body.style.maxHeight  = inner.offsetHeight + 'px';
                body.style.opacity    = '1';
                requestAnimationFrame(() => { body.style.transition = ''; });
            } else {
                body.style.maxHeight = '0';
                body.style.opacity   = '0';
            }
        });
    }

    function toggleSection(btn) {
        const sec   = btn.closest('.tpe-section');
        const body  = sec.querySelector('.tpe-sec-body');
        const inner = sec.querySelector('.tpe-sec-inner');
        const open  = sec.classList.contains('is-open');
        if (open) {
            body.style.maxHeight = body.scrollHeight + 'px';
            sec.classList.remove('is-open');
            requestAnimationFrame(() => { body.style.maxHeight = '0'; body.style.opacity = '0'; });
        } else {
            sec.classList.add('is-open');
            body.style.maxHeight = inner.offsetHeight + 'px';
            body.style.opacity = '1';
            body.addEventListener('transitionend', () => {
                if (sec.classList.contains('is-open')) body.style.maxHeight = 'none';
            }, { once: true });
        }
    }

    document.querySelectorAll('[data-toggle]').forEach((btn) => {
        btn.addEventListener('click', () => toggleSection(btn));
    });
    initAccordion();

    /* ── Changes counter ─────────────────────────────────── */
    function bump() {
        changes++;
        deltaN.textContent = changes;
        delta.classList.add('show');
        saved.classList.remove('show');
    }
    function clearChanges() {
        changes = 0;
        delta.classList.remove('show');
    }

    /* ── Char counter ────────────────────────────────────── */
    function updateCount(ta, el) {
        const n = ta.value.length;
        el.textContent = n;
        el.classList.toggle('warn', n >= 3500 && n < 4000);
        el.classList.toggle('over', n >= 4000);
    }

    /* ── Compact text for section desc ──────────────────── */
    function cap(str, max = 30) {
        const s = str.trim();
        return s ? (s.length > max ? s.slice(0, max) + '…' : s) : null;
    }

    /* ── Text sync ───────────────────────────────────────── */
    function syncHeader() {
        prevHdr.textContent = headerTa.value.trim();
        updateCount(headerTa, cntHdr);
        descHeader.textContent = cap(headerTa.value) ?? 'Sin mensaje de encabezado';
    }
    function syncFooter() {
        prevFtr.textContent = footerTa.value.trim() || 'Gracias por su compra';
        updateCount(footerTa, cntFtr);
        descFooter.textContent = cap(footerTa.value) ?? 'Sin mensaje de cierre';
    }

    headerTa.addEventListener('input', () => { syncHeader(); bump(); });
    footerTa.addEventListener('input', () => { syncFooter(); bump(); });
    syncHeader();
    syncFooter();

    /* ── Logo ────────────────────────────────────────────── */
    function setLogo(url, name) {
        dropThumb.src = url;
        dropFname.textContent = name || 'Logo configurado';
        dropEmpty.style.display = 'none';
        dropFile.style.display  = '';
        logoClear.classList.remove('d-none');
        prevLogo.src = url;
        prevLogo.style.display = '';
        descLogo.textContent = 'Logo configurado';
    }

    function clearLogo() {
        if (objUrl) { URL.revokeObjectURL(objUrl); objUrl = null; }
        dropThumb.src = '';
        dropEmpty.style.display = '';
        dropFile.style.display  = 'none';
        logoClear.classList.add('d-none');
        prevLogo.src = '';
        prevLogo.style.display = 'none';
        logoInput.value = '';
        descLogo.textContent = 'Sin logo';
    }

    function handleFile(file) {
        if (!file?.type.startsWith('image/')) return;
        if (objUrl) URL.revokeObjectURL(objUrl);
        objUrl = URL.createObjectURL(file);
        setLogo(objUrl, file.name);
        bump();
    }

    logoInput.addEventListener('change', (e) => handleFile(e.target.files?.[0]));

    logoClear.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        clearLogo();
        bump();
    });

    drop.addEventListener('dragover',  (e) => { e.preventDefault(); drop.classList.add('drag'); });
    drop.addEventListener('dragleave', ()  => drop.classList.remove('drag'));
    drop.addEventListener('dragend',   ()  => drop.classList.remove('drag'));
    drop.addEventListener('drop', (e) => {
        e.preventDefault();
        drop.classList.remove('drag');
        const file = e.dataTransfer?.files?.[0];
        if (!file) return;
        const dt = new DataTransfer();
        dt.items.add(file);
        logoInput.files = dt.files;
        handleFile(file);
    });

    /* ── Form submit ─────────────────────────────────────── */
    form.addEventListener('submit', () => {
        saveBtn.disabled = true;
        saveLbl.textContent = 'Guardando…';
        const spin = document.createElement('span');
        spin.className = 'spinner-border spinner-border-sm me-1';
        spin.setAttribute('role', 'status');
        spin.setAttribute('aria-hidden', 'true');
        saveBtn.prepend(spin);
        changes = 0;
    });

    /* ── Page-load success flash ─────────────────────────── */
    @if(session('success'))
    requestAnimationFrame(() => {
        clearChanges();
        saved.classList.add('show');
        setTimeout(() => saved.classList.remove('show'), 3400);
    });
    @endif

    /* ── Unsaved navigation guard ────────────────────────── */
    window.addEventListener('beforeunload', (e) => {
        if (changes > 0) { e.preventDefault(); e.returnValue = ''; }
    });

})();
</script>
@endpush
