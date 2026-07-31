@extends('layouts.pos')

@section('title', 'Punto de Venta')

@push('pos-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/select2/select2.css') }}" />
@endpush

@section('content')
<div
    class="pos-shell"
    x-data="posApp()"
    x-init="init()"
    @keydown.window="handleKey($event)"
>
    <style>[x-cloak]{display:none !important;}</style>
    <style>
        .pos-vendedor-select-wrap .select2-container {
            width: 100% !important;
            display: block;
        }
        .pos-vendedor-select-wrap .select2-container--default .select2-selection--single {
            height: 40px;
            min-height: 40px;
            border: 1.5px solid var(--ls-border);
            border-radius: var(--ls-radius);
            background: var(--ls-surface);
            display: flex;
            align-items: center;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .pos-vendedor-select-wrap .select2-container--default.select2-container--focus .select2-selection--single,
        .pos-vendedor-select-wrap .select2-container--default.select2-container--open .select2-selection--single {
            border-color: var(--ls-accent);
            box-shadow: 0 0 0 3px var(--ls-accent-light);
        }
        .pos-vendedor-select-wrap .select2-container--default .select2-selection--single .select2-selection__rendered {
            width: 100%;
            padding-left: 2.1rem;
            padding-right: 2rem;
            color: var(--ls-text-primary);
            font-size: 0.84rem;
            line-height: 38px;
        }
        .pos-vendedor-select-wrap .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: var(--ls-text-muted);
        }
        .pos-vendedor-select-wrap .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 38px;
            right: 0.45rem;
        }
        .pos-vendedor-select-dropdown {
            border: 1px solid var(--ls-border);
            border-radius: var(--ls-radius);
            background: var(--ls-surface);
            box-shadow: var(--ls-shadow-lg);
            overflow: hidden;
        }
        .pos-vendedor-select-dropdown .select2-search--dropdown {
            padding: 0.5rem;
            background: var(--ls-surface);
        }
        .pos-vendedor-select-dropdown .select2-search__field {
            height: 34px;
            border: 1px solid var(--ls-border);
            border-radius: var(--ls-radius-sm);
            padding: 0 0.55rem;
            color: var(--ls-text-primary);
            outline: none;
        }
        .pos-vendedor-select-dropdown .select2-results__options {
            background: var(--ls-surface);
        }
        .pos-vendedor-select-dropdown .select2-results__option {
            padding: 0.45rem 0.65rem;
            font-size: 0.84rem;
            color: var(--ls-text-primary);
        }
        .pos-vendedor-select-dropdown .select2-results__option--highlighted.select2-results__option--selectable {
            background: var(--ls-accent) !important;
            color: #fff !important;
        }
        .pos-vendedor-select-dropdown .select2-results__option--selected {
            background: var(--ls-surface-3);
        }
        .pos-search-suggest {
            position: absolute;
            left: 0;
            right: 0;
            top: calc(100% + 4px);
            z-index: 50;
            background: #fff;
            border: 1px solid var(--ls-border);
            border-radius: 10px;
            box-shadow: var(--ls-shadow-lg);
            max-height: 280px;
            overflow: auto;
        }
        .pos-search-suggest__item {
            width: 100%;
            border: 0;
            padding: .5rem .65rem;
            border-bottom: 1px solid var(--ls-border);
            cursor: pointer;
            display: flex;
            flex-direction: column;
            gap: .1rem;
            background: #fff;
            text-align: left;
            appearance: none;
        }
        .pos-search-suggest__item:last-child { border-bottom: none; }
        .pos-search-suggest__item:hover,
        .pos-search-suggest__item.active {
            background: var(--ls-accent-light);
        }
        .pos-search-suggest__name { font-size: .8rem; font-weight: 700; color: var(--ls-text-primary); }
        .pos-search-suggest__meta { font-size: .72rem; color: var(--ls-text-muted); }
        .pos-search-suggest--compact {
            top: calc(100% + 2px);
            border-radius: 12px;
            box-shadow: 0 14px 30px rgba(10, 37, 64, .12);
            max-height: 190px;
        }
        .pos-search-suggest--compact .pos-search-suggest__item {
            padding: .6rem .8rem;
        }
        .pos-search-suggest--compact .pos-search-suggest__name {
            font-size: .92rem;
            font-weight: 600;
        }

        .variant-modal {
            position: fixed;
            inset: 0;
            z-index: 1250;
            background: rgba(10, 37, 64, .45);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .variant-modal__card {
            width: min(720px, 100%);
            background: #fff;
            border: 1px solid var(--ls-border);
            border-radius: 14px;
            box-shadow: var(--ls-shadow-lg);
        }
        .variant-modal__head {
            padding: .9rem 1rem;
            border-bottom: 1px solid var(--ls-border);
            font-weight: 700;
            color: var(--ls-text-primary);
        }
        .variant-modal--front {
            z-index: 1305;
        }
        /* ── MODAL DE MOVIMIENTOS DE CAJA (retiro / gasto) ─────────
           Cascarón con cabecera fija, cuerpo con scroll y barra de
           acciones fija. Comparte la hoja de conteo con el corte. ── */
        .cash-modal__card {
            width: min(860px, 100%);
            max-width: 860px !important;
            max-height: calc(100vh - 2rem);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            padding: 0;
        }

        .cash-modal__card--narrow {
            width: min(640px, 100%);
            max-width: 640px !important;
        }

        .cash-modal__head {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .75rem 1rem;
            border-bottom: 1px solid var(--ls-border);
            background: linear-gradient(180deg, var(--ls-surface) 0%, var(--ls-surface-2) 100%);
        }

        .cash-modal__head-left { display: flex; align-items: center; gap: .65rem; min-width: 0; }

        .cash-modal__icon {
            width: 36px; height: 36px;
            flex-shrink: 0;
            border-radius: var(--ls-radius);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.02rem;
            background: var(--ls-warning-bg);
            color: var(--ls-warning);
            border: 1px solid var(--ls-warning-mid);
        }

        .cash-modal__icon--gasto {
            background: var(--ls-accent-light);
            color: var(--ls-accent);
            border-color: var(--ls-accent-mid);
        }

        .cash-modal__title { font-size: .92rem; font-weight: 800; color: var(--ls-text-primary); line-height: 1.2; }
        .cash-modal__sub { font-size: .72rem; color: var(--ls-text-muted); margin-top: 2px; }

        .cash-modal__head-right { display: flex; align-items: center; gap: .8rem; flex-shrink: 0; }

        .cash-modal__close {
            width: 30px; height: 30px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: var(--ls-radius);
            border: 1px solid var(--ls-border);
            background: var(--ls-surface);
            color: var(--ls-text-muted);
            cursor: pointer;
            transition: background .15s, color .15s, border-color .15s;
        }

        .cash-modal__close:hover { background: var(--ls-surface-3); color: var(--ls-text-primary); border-color: var(--ls-border-strong); }

        .cash-modal__body {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            padding: .9rem 1rem 1rem;
            display: grid;
            align-content: start;
            gap: .8rem;
        }

        .cash-modal__foot {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .7rem 1rem;
            border-top: 1px solid var(--ls-border);
            background: var(--ls-surface);
            box-shadow: 0 -2px 10px rgba(10,37,64,.05);
        }

        .cash-modal__foot-read { min-width: 0; }
        .cash-modal__foot-label { font-size: .67rem; font-weight: 700; color: var(--ls-text-muted); line-height: 1.1; }
        .cash-modal__foot-value {
            font-size: 1.15rem;
            font-weight: 900;
            letter-spacing: -.02em;
            color: var(--ls-text-primary);
            font-variant-numeric: tabular-nums;
        }
        .cash-modal__foot-meta { font-size: .69rem; color: var(--ls-text-muted); font-weight: 600; }
        .cash-modal__foot-actions { display: flex; align-items: center; gap: .6rem; flex-shrink: 0; }

        /* Pasos */
        .cash-steps { display: flex; align-items: center; gap: .45rem; }

        .cash-step {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            font-size: .72rem;
            font-weight: 700;
            color: var(--ls-text-muted);
            white-space: nowrap;
        }

        .cash-step__num {
            width: 20px; height: 20px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 50%;
            border: 1px solid var(--ls-border-strong);
            background: var(--ls-surface);
            font-size: .66rem;
            font-weight: 800;
        }

        .cash-step--active { color: var(--ls-text-primary); }
        .cash-step--active .cash-step__num { border-color: var(--ls-accent); background: var(--ls-accent); color: #fff; }
        .cash-step--done .cash-step__num { border-color: var(--ls-success); background: var(--ls-success); color: #fff; }
        .cash-step__line { width: 26px; height: 1px; background: var(--ls-border-strong); }

        /* Indicadores */
        .cash-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: .6rem;
        }

        .cash-stat {
            border: 1px solid var(--ls-border);
            border-radius: var(--ls-radius-lg);
            background: var(--ls-surface-2);
            padding: .55rem .75rem;
            min-width: 0;
        }

        .cash-stat__label { font-size: .68rem; font-weight: 700; color: var(--ls-text-muted); }
        .cash-stat__value {
            font-size: 1rem;
            font-weight: 800;
            color: var(--ls-text-primary);
            font-variant-numeric: tabular-nums;
            margin-top: .1rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .cash-stat--warning { border-color: var(--ls-warning-mid); background: var(--ls-warning-bg); }
        .cash-stat--warning .cash-stat__value { color: #a35c05; }

        .cash-note {
            display: flex;
            align-items: flex-start;
            gap: .5rem;
            padding: .55rem .7rem;
            border-radius: var(--ls-radius);
            border: 1px solid var(--ls-warning-mid);
            background: var(--ls-warning-bg);
            font-size: .75rem;
            line-height: 1.35;
            color: #8a5200;
        }

        .cash-note i { font-size: .95rem; color: var(--ls-warning); flex-shrink: 0; margin-top: 1px; }

        .cash-note--danger { border-color: var(--ls-danger-mid); background: var(--ls-danger-bg); color: var(--ls-danger); }
        .cash-note--danger i { color: var(--ls-danger); }

        /* Bloques */
        .cash-block {
            border: 1px solid var(--ls-border);
            border-radius: var(--ls-radius-xl);
            background: var(--ls-surface);
            overflow: hidden;
        }

        .cash-block__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .6rem .8rem;
            border-bottom: 1px solid var(--ls-border);
            background: var(--ls-surface-2);
        }

        .cash-block__title {
            display: flex; align-items: center; gap: .4rem;
            font-size: .8rem; font-weight: 800; color: var(--ls-text-primary);
        }

        .cash-block__hint { font-size: .7rem; color: var(--ls-text-muted); margin-top: 1px; font-weight: 500; }
        .cash-block__body { padding: .75rem .8rem; display: grid; gap: .75rem; }
        .cash-block__error { padding: 0 .8rem .7rem; }

        /* Resumen del retiro (paso 2) */
        .cash-review {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .8rem .85rem .7rem;
        }

        .cash-review__label { font-size: .68rem; font-weight: 700; color: var(--ls-text-muted); }
        .cash-review__amount {
            font-size: 1.65rem;
            font-weight: 900;
            letter-spacing: -.03em;
            line-height: 1.05;
            color: var(--ls-text-primary);
            font-variant-numeric: tabular-nums;
        }
        .cash-review__meta { font-size: .72rem; color: var(--ls-text-muted); font-weight: 600; margin-top: .15rem; }

        .cash-chips { display: flex; flex-wrap: wrap; gap: .35rem; padding: 0 .85rem .75rem; }

        .cash-chip {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .22rem .6rem;
            border-radius: 999px;
            border: 1px solid var(--ls-border);
            background: var(--ls-surface-2);
            font-size: .72rem;
            font-weight: 600;
            color: var(--ls-text-secondary);
            font-variant-numeric: tabular-nums;
        }

        .cash-chip strong { font-weight: 800; color: var(--ls-text-primary); }

        .cash-impact {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .6rem;
            padding: .55rem .85rem;
            border-top: 1px dashed var(--ls-border);
            background: var(--ls-surface-2);
            font-size: .76rem;
            color: var(--ls-text-secondary);
        }

        .cash-impact__value { font-weight: 800; color: var(--ls-text-primary); font-variant-numeric: tabular-nums; }

        .cash-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; }
        .cash-field--full { grid-column: 1 / -1; }

        .cash-modal__body .pos-notes-textarea { min-height: 74px; }
        .cash-modal__body .pos-notes-textarea::placeholder { text-transform: none; }
        .cash-modal__body .pos-input::placeholder { text-transform: none; }

        /* Etiquetas en formato oración dentro del modal */
        .cash-modal__body .pos-field__label {
            text-transform: none;
            letter-spacing: normal;
            font-size: .74rem;
            font-weight: 700;
            color: var(--ls-text-secondary);
        }

        @media (max-width: 760px) {
            .cash-stats { grid-template-columns: minmax(0, 1fr); }
            .cash-fields { grid-template-columns: minmax(0, 1fr); }
            .cash-modal__head { flex-wrap: wrap; }
            .cash-modal__foot { flex-direction: column; align-items: stretch; }
            .cash-modal__foot-actions .pos-btn { flex: 1 1 auto; }
            .cash-review { flex-direction: column; align-items: flex-start; gap: .6rem; }
        }
        .pos-field-error {
            margin-top: .35rem;
            font-size: .78rem;
            color: #b42318;
            font-weight: 600;
        }
        .variant-modal__list { max-height: 360px; overflow: auto; }
        .variant-modal__row {
            width: 100%;
            border: 0;
            border-bottom: 1px solid var(--ls-border);
            text-align: left;
            background: #fff;
            padding: .7rem .9rem;
            cursor: pointer;
        }
        .variant-modal__row:hover { background: var(--ls-surface-2); }
        .variant-modal__name { font-size: .82rem; font-weight: 700; color: var(--ls-text-primary); display: block; }
        .variant-modal__meta { font-size: .74rem; color: var(--ls-text-muted); display: block; margin-top: .1rem; }
        .pos-inline-disc-btn {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            border: 1px solid var(--ls-border);
            background: #fff;
            color: var(--ls-text-primary);
            border-radius: 999px;
            font-size: .74rem;
            font-weight: 700;
            padding: .28rem .55rem;
            transition: .18s ease;
        }
        .pos-inline-disc-btn:hover {
            border-color: var(--ls-accent);
            color: var(--ls-accent);
            background: var(--ls-accent-light);
        }
        .pos-inline-disc-btn--active {
            border-color: rgba(220, 53, 69, .18);
            color: var(--ls-danger);
            background: rgba(220, 53, 69, .08);
        }
        .pos-discount-btn[disabled],
        .pos-inline-disc-btn[disabled] {
            opacity: .55;
            cursor: not-allowed;
        }
        .pos-discount-hint {
            margin-top: .45rem;
            font-size: .72rem;
            color: var(--ls-text-muted);
            line-height: 1.35;
        }
        .disc-modal__card {
            width: min(520px, 94vw);
            background: #fff;
            border-radius: 22px;
            border: none;
            overflow: hidden;
            box-shadow: 0 28px 72px rgba(10,37,64,.22), 0 4px 16px rgba(10,37,64,.08);
        }
        .disc-modal__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .9rem;
            padding: 1.15rem 1.4rem 1rem;
            background: #fff;
            border-bottom: 1px solid #eef0f6;
        }
        .disc-modal__title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 800;
            color: #0a2540;
            letter-spacing: -.01em;
        }
        .disc-modal__close {
            width: 38px;
            height: 38px;
            border: 0;
            border-radius: 12px;
            background: #f1f4f9;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: .18s ease;
        }
        .disc-modal__close:hover {
            background: #e2e8f0;
            color: #1e293b;
        }
        .disc-modal__body {
            padding: 1.2rem 1.4rem 1.35rem;
            background: #f8fafc;
        }
        .disc-modal__product {
            background: #fff;
            border: 1px solid #eef0f6;
            border-radius: 16px;
            padding: .95rem 1rem;
            box-shadow: 0 1px 4px rgba(10,37,64,.04);
        }
        .disc-modal__name {
            font-size: .98rem;
            font-weight: 800;
            color: #0f2744;
            line-height: 1.3;
        }
        .disc-modal__copy {
            margin-top: .25rem;
            font-size: .8rem;
            color: #74839a;
        }
        .disc-modal__field {
            margin-top: 1rem;
            background: #fff;
            border: 1px solid #eef0f6;
            border-radius: 16px;
            padding: .95rem 1rem;
        }
        .disc-modal__label {
            display: block;
            font-size: .78rem;
            font-weight: 800;
            color: #5e718f;
            letter-spacing: .05em;
            text-transform: uppercase;
            margin-bottom: .6rem;
        }
        .disc-modal__input {
            width: 100%;
            height: 48px;
            border-radius: 12px;
            border: 1.5px solid #dce5f4;
            padding: 0 .95rem;
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f2744;
            background: #fff;
            transition: .18s ease;
        }
        .disc-modal__input:focus {
            outline: none;
            border-color: #635bff;
            box-shadow: 0 0 0 3px rgba(99,91,255,.12);
        }
        .disc-modal__actions {
            display: flex;
            justify-content: flex-end;
            gap: .6rem;
            margin-top: 1rem;
        }
        .disc-modal__btn {
            height: 42px;
            border-radius: 12px;
            padding: 0 1rem;
            font-size: .86rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: .4rem;
        }
        .cash-summary__grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .65rem;
            margin-bottom: .75rem;
        }
        .cash-summary__item {
            border: 1px solid var(--ls-border);
            border-radius: 10px;
            background: #fff;
            padding: .65rem .75rem;
        }
        .cash-summary__label {
            font-size: .72rem;
            color: var(--ls-text-muted);
            text-transform: uppercase;
            letter-spacing: .04em;
            font-weight: 700;
        }
        .cash-summary__value {
            margin-top: .2rem;
            font-size: 1.05rem;
            color: var(--ls-text-primary);
            font-weight: 800;
        }
        .cash-summary__table {
            width: 100%;
            border-collapse: collapse;
            font-size: .78rem;
        }
        .cash-summary__table th,
        .cash-summary__table td {
            padding: .45rem .5rem;
            border-bottom: 1px solid var(--ls-border);
            text-align: left;
        }
        .cash-summary__table th {
            color: var(--ls-text-muted);
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .cash-summary__table td:last-child,
        .cash-summary__table th:last-child { text-align: right; }
        .cash-summary__payment-list { display: grid; gap: .18rem; min-width: 145px; }
        .cash-summary__payment { display: flex; justify-content: space-between; gap: .7rem; white-space: nowrap; }
        .cash-summary__payment strong { font-weight: 700; }
        .cash-summary__layout {
            display: grid;
            grid-template-columns: minmax(0, 1.3fr) minmax(300px, .9fr);
            gap: .9rem;
            align-items: start;
        }
        .cash-summary__stack {
            display: grid;
            gap: .9rem;
        }
        .cash-summary__section {
            border: 1px solid var(--ls-border);
            border-radius: 10px;
            background: #fff;
            overflow: hidden;
        }
        .cash-summary__section-head {
            padding: .7rem .85rem;
            border-bottom: 1px solid var(--ls-border);
            background: #f8fafc;
            font-size: .78rem;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--ls-text-muted);
        }
        .cash-summary__section-body {
            padding: .8rem .85rem;
        }
        .cash-summary__metric-list {
            display: grid;
            gap: .7rem;
        }
        .cash-summary__metric {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: .8rem;
            font-size: .84rem;
            color: var(--ls-text-secondary);
        }
        .cash-summary__metric strong {
            color: var(--ls-text-primary);
            font-size: .92rem;
            font-weight: 800;
            text-align: right;
        }
        .cash-summary__metric small {
            display: block;
            margin-top: .15rem;
            color: var(--ls-text-muted);
            font-size: .72rem;
        }
        .cash-summary__muted-note {
            margin-top: .65rem;
            font-size: .74rem;
            line-height: 1.45;
            color: var(--ls-text-muted);
        }
        .cash-summary-modal__card {
            width: min(1120px, 100%);
            max-height: min(88vh, 980px);
            display: flex;
            flex-direction: column;
        }
        .cash-summary-modal__body {
            padding: .9rem;
            overflow: auto;
        }
        @media (max-width: 900px) {
            .cash-summary__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .cash-summary__layout { grid-template-columns: 1fr; }
        }
        .pos-cambio-banner {
            margin: .8rem 0 1rem;
            padding: 1rem 1.25rem;
            border: 1px solid #f2d6a2;
            border-radius: 6px;
            background: #fffaf2;
            box-shadow: none;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            column-gap: 1.25rem;
            row-gap: .65rem;
        }
        .pos-cambio-banner__main {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: .28rem;
        }
        .pos-cambio-banner__top {
            display: flex;
            align-items: baseline;
            gap: .7rem;
            flex-wrap: wrap;
        }
        .pos-cambio-banner__title {
            font-size: .96rem;
            font-weight: 800;
            color: #8a4b15;
            letter-spacing: -.01em;
        }
        .pos-cambio-banner__hint {
            font-size: .8rem;
            color: #8b5e34;
            line-height: 1.45;
        }
        .pos-cambio-status {
            color: #c2410c;
            font-size: .78rem;
            font-weight: 800;
            line-height: 1.4;
        }
        .pos-cambio-banner__meta {
            font-size: .84rem;
            color: #8a4b15;
            line-height: 1.45;
        }
        .pos-cambio-banner__meta strong {
            color: #7c2d12;
            font-weight: 800;
        }
        .pos-cambio-banner__action {
            flex-shrink: 0;
            align-self: start;
        }
        .pos-cambio-banner__action .pos-btn {
            background: #fff;
            border-color: #d9e3f2;
            color: #526581;
            border-radius: 6px;
            padding-inline: .9rem;
            min-height: 36px;
            box-shadow: none;
        }
        @media (max-width: 900px) {
            .pos-cambio-banner {
                grid-template-columns: 1fr;
            }
            .pos-cambio-banner__action {
                align-self: flex-start;
            }
        }
        .pay-change-summary {
            margin-top: 1rem;
            margin-bottom: 1.1rem;
            padding: 1.15rem 1.2rem 1.05rem;
            border: 1px solid #f7d7b5;
            border-radius: 20px;
            background: linear-gradient(180deg, #fffdf8 0%, #fff6ea 100%);
            box-shadow: 0 10px 22px rgba(190, 124, 42, .08);
        }
        .pay-change-summary__title {
            font-size: .8rem;
            font-weight: 800;
            color: #9a3412;
            letter-spacing: .02em;
            text-transform: uppercase;
            margin-bottom: .9rem;
        }
        .pay-change-summary__rows {
            display: grid;
            gap: .7rem;
        }
        .pay-change-summary__row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 1rem;
            font-size: .84rem;
            color: #7c2d12;
        }
        .pay-change-summary__row span:first-child {
            color: #8b5e34;
        }
        .pay-change-summary__row strong {
            font-size: .95rem;
            font-weight: 800;
            color: #7c2d12;
        }
        .pay-change-summary__divider {
            margin: .85rem 0;
            border-top: 1px dashed #f7b267;
        }
        .pay-change-summary__total {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 1rem;
            font-size: 1rem;
            color: #9a3412;
        }
        .pay-change-summary__total strong {
            font-size: 1.1rem;
            font-weight: 900;
        }
        .pay-change-summary__copy {
            margin-top: .8rem;
            font-size: .76rem;
            line-height: 1.45;
            color: #8b5e34;
        }
        /* ── Pay modal shell ────────────────────────────────────── */
        .pay-modal__card {
            width: min(1060px, 94vw);
            height: min(88vh, 760px);
            max-height: 88vh;
            background: #fff;
            border-radius: 22px;
            border: none;
            overflow: hidden;
            box-shadow: 0 28px 72px rgba(10,37,64,.22), 0 4px 16px rgba(10,37,64,.08);
            display: grid;
            grid-template-rows: auto 1fr;
        }

        /* ── Header ─────────────────────────────────────────────── */
        .pay-modal__head {
            background: linear-gradient(105deg, #0a8f94 0%, #1b2d5e 100%);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .5rem 1.15rem;
            gap: 1rem;
        }
        .pay-modal__head-left { display: flex; align-items: center; gap: .7rem; }
        .pay-modal__head-icon {
            width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
            background: rgba(255,255,255,.15); border: 1.5px solid rgba(255,255,255,.25);
            display: flex; align-items: center; justify-content: center; font-size: 1.05rem;
        }
        .pay-modal__title {
            margin: 0;
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: .01em;
        }
        .pay-modal__subtitle { margin: 0; font-size: .74rem; color: rgba(255,255,255,.65); }
        /* Total anclado en el header */
        .pay-modal__head-total {
            margin-left: auto;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            line-height: 1;
            gap: .1rem;
        }
        .pay-modal__head-total-label {
            font-size: .6rem; font-weight: 800; text-transform: uppercase;
            letter-spacing: .1em; color: rgba(255,255,255,.72);
        }
        .pay-modal__head-total-amount {
            font-size: 1.75rem; font-weight: 900; color: #fff; letter-spacing: -.025em;
        }
        .pay-modal__close {
            width: 38px; height: 38px;
            border-radius: 10px; border: 0;
            background: rgba(255,255,255,.15);
            color: #fff; font-size: 1.1rem;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: background .15s;
            flex-shrink: 0;
        }
        .pay-modal__close:hover { background: rgba(255,255,255,.28); }

        /* ── Body layout ─────────────────────────────────────────── */
        .pay-modal__body {
            display: grid;
            grid-template-columns: 1fr 300px;
            min-height: 0;
        }
        .pay-main {
            padding: 1rem 1.2rem;
            overflow-y: auto;
            min-height: 0;
            background: #f8fafc;
        }
        .pay-side {
            background: #fff;
            border-left: 1px solid #eef0f6;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: .6rem;
            min-height: 0;
            overflow-y: auto;
        }

        /* ── Total display ───────────────────────────────────────── */
        .pay-total-card {
            background: #fff;
            border-radius: 16px;
            border: 1.5px solid #e2e8f0;
            padding: 1rem 1.2rem .85rem;
            text-align: center;
            margin-bottom: .9rem;
            box-shadow: 0 1px 4px rgba(10,37,64,.05);
        }
        .pay-total-label {
            font-size: .72rem;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .1em;
            margin: 0 0 .15rem;
        }
        .pay-total-amount {
            font-size: 3rem;
            font-weight: 900;
            color: #0a8f94;
            line-height: 1;
            letter-spacing: -.04em;
            margin: 0;
        }

        /* ── Payment methods ─────────────────────────────────────── */
        .pay-methods {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: .5rem;
            margin-bottom: .85rem;
        }
        .pay-methods--single {
            grid-template-columns: minmax(0, 1fr);
            max-width: 200px;
            margin-inline: auto;
            margin-bottom: .85rem;
        }
        .pay-method {
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            background: #fff;
            min-height: 60px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .25rem;
            color: #64748b;
            font-size: .8rem;
            font-weight: 700;
            cursor: pointer;
            transition: border-color .18s, background .18s, color .18s, box-shadow .18s;
            box-shadow: 0 1px 3px rgba(10,37,64,.04);
        }
        .pay-method i { font-size: 1.3rem; }
        .pay-method span { font-size: .79rem; font-weight: 700; line-height: 1.1; }
        .pay-method:hover { border-color: #0a8f94; color: #0a8f94; background: #f0fbfb; }
        .pay-method.is-active {
            border-color: #0a8f94;
            background: linear-gradient(135deg, #e6f7f8 0%, #f0fbfb 100%);
            color: #0a8f94;
            box-shadow: 0 0 0 3px rgba(10,143,148,.1);
        }

        /* ── Reference row ───────────────────────────────────────── */
        .pay-ref-row {
            display: grid;
            grid-template-columns: 160px 1fr;
            gap: .7rem;
            align-items: center;
            margin: .85rem 0;
            background: #fff;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            padding: .75rem 1rem;
            box-shadow: 0 1px 3px rgba(10,37,64,.04);
        }
        .pay-ref-row label {
            font-size: .85rem;
            font-weight: 700;
            color: #475569;
            text-align: right;
        }
        .pay-ref-row input {
            height: 44px;
            border-radius: 10px;
            border: 1.5px solid #e2e8f0;
            font-size: .95rem;
            padding: 0 .85rem;
            background: #f8fafc;
            color: #1e293b;
            transition: border-color .18s, box-shadow .18s;
            width: 100%;
        }
        .pay-ref-row input:focus {
            border-color: #0a8f94;
            box-shadow: 0 0 0 3px rgba(10,143,148,.12);
            outline: none;
            background: #fff;
        }
        .pay-ref-row--vale input {
            font-weight: 700;
            letter-spacing: .04em;
        }
        .pay-ref-row--vale input.is-loading {
            border-color: #b45309;
            box-shadow: 0 0 0 3px rgba(180,83,9,.12);
            background: #fffdf8;
        }

        /* ── Mixed payments table ────────────────────────────────── */
        .pay-lines {
            margin: .85rem 0;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            padding: .85rem;
            background: #fff;
            box-shadow: 0 1px 3px rgba(10,37,64,.04);
        }
        .pay-lines__head,
        .pay-line {
            display: grid;
            grid-template-columns: 1.2fr 1fr 1fr auto;
            gap: .55rem;
            align-items: center;
        }
        .pay-lines__head {
            font-size: .75rem;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .05em;
            margin-bottom: .5rem;
            padding-bottom: .45rem;
            border-bottom: 1px solid #f0f3f9;
        }
        .pay-line + .pay-line { margin-top: .45rem; }
        .pay-line select,
        .pay-line input {
            height: 40px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: .88rem;
            padding: 0 .7rem;
            background: #f8fafc;
            color: #1e293b;
            transition: border-color .18s;
            width: 100%;
        }
        .pay-line select:focus,
        .pay-line input:focus {
            border-color: #0a8f94;
            outline: none;
            background: #fff;
        }

        /* ── Cash section ────────────────────────────────────────── */
        .pay-cash {
            margin: .85rem 0;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            padding: .9rem 1rem;
            background: #fff;
            box-shadow: 0 1px 3px rgba(10,37,64,.04);
        }
        .pay-cash__row {
            display: grid;
            grid-template-columns: 130px 1fr 52px;
            gap: .55rem;
            align-items: center;
        }
        .pay-cash__row label {
            font-size: .85rem;
            font-weight: 700;
            color: #475569;
            text-align: right;
        }
        .pay-cash__row input {
            height: 52px;
            border: 2px solid #0a8f94;
            border-radius: 12px;
            font-size: 1.7rem;
            font-weight: 800;
            text-align: center;
            color: #0a2540;
            background: #f0fbfb;
            transition: border-color .18s, box-shadow .18s;
            width: 100%;
        }
        .pay-cash__row input:focus {
            border-color: #0a8f94;
            box-shadow: 0 0 0 3px rgba(10,143,148,.15);
            outline: none;
            background: #fff;
        }
        .pay-cash__ok {
            width: 52px; height: 52px;
            border-radius: 12px;
            border: none;
            background: linear-gradient(135deg, #0a8f94 0%, #0d7a7e 100%);
            color: #fff;
            font-size: 1.1rem;
            font-weight: 800;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 3px 10px rgba(10,143,148,.3);
            transition: opacity .18s;
        }
        .pay-cash__ok:hover { opacity: .88; }
        .pay-cash__change {
            margin-top: .75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .7rem;
            background: #f0fdf4;
            border: 1.5px solid #bbf7d0;
            border-radius: 12px;
            padding: .55rem 1rem;
        }
        .pay-cash__change-label {
            font-size: .82rem; font-weight: 700; color: #475569;
        }
        .pay-cash__change strong {
            color: #16a34a;
            font-size: 1.9rem;
            font-weight: 900;
            line-height: 1;
            letter-spacing: -.03em;
        }
        .pay-cash__quick {
            margin-top: .7rem;
            display: flex;
            justify-content: center;
            gap: .35rem;
            flex-wrap: wrap;
        }
        .pay-cash__quick button {
            border: 1.5px solid #e2e8f0;
            border-radius: 30px;
            background: #f8fafc;
            color: #1e293b;
            font-weight: 800;
            font-size: .82rem;
            padding: .3rem .75rem;
            cursor: pointer;
            transition: border-color .18s, background .18s;
        }
        .pay-cash__quick button:hover {
            border-color: #0a8f94;
            background: #f0fbfb;
            color: #0a8f94;
        }

        /* ── Summary bar ─────────────────────────────────────────── */
        .pay-summary {
            margin-top: .2rem;
            background: #fff;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            padding: .5rem;
            display: grid;
            grid-template-columns: 1fr;
            gap: .35rem;
            box-shadow: 0 1px 3px rgba(10,37,64,.04);
        }
        .pay-summary__item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: .92rem;
            font-weight: 700;
            padding: .35rem .6rem;
            border-radius: 8px;
        }
        .pay-summary__item span { color: #64748b; font-weight: 600; }
        .pay-summary__ok strong { color: #16a34a; font-size: 1.05rem; }
        .pay-summary__ok { background: #f0fdf4; }
        .pay-summary__danger strong { color: #dc2626; font-size: 1.05rem; }
        .pay-summary__danger { background: #fef2f2; }

        /* ── Side panel actions ──────────────────────────────────── */
        .pay-side-label {
            font-size: .68rem; font-weight: 800; color: #94a3b8;
            text-transform: uppercase; letter-spacing: .08em;
            margin-bottom: .1rem;
        }
        .pay-side-card {
            border-radius: 14px;
            padding: .85rem 1rem;
            display: flex;
            align-items: center;
            gap: .75rem;
            cursor: pointer;
            transition: opacity .18s, box-shadow .18s;
            border: none;
            width: 100%;
            text-align: left;
        }
        .pay-side-card:hover { opacity: .9; box-shadow: 0 4px 14px rgba(0,0,0,.12); }
        .pay-side-card__icon {
            width: 42px; height: 42px; border-radius: 11px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; font-size: 1.25rem;
        }
        .pay-side-card__k {
            font-size: 1rem; font-weight: 900; line-height: 1; margin-bottom: .1rem;
        }
        .pay-side-card__t {
            font-size: .8rem; font-weight: 700; line-height: 1.25;
        }
        /* F1 - primary action */
        .pay-side-card--f1 {
            background: linear-gradient(135deg, #0a8f94 0%, #0d7a7e 100%);
            box-shadow: 0 4px 14px rgba(10,143,148,.3);
        }
        .pay-side-card--f1 .pay-side-card__icon { background: rgba(255,255,255,.2); color: #fff; }
        .pay-side-card--f1 .pay-side-card__k,
        .pay-side-card--f1 .pay-side-card__t { color: #fff; }
        /* F2 - secondary */
        .pay-side-card--f2 {
            background: #f0fbfb;
            border: 1.5px solid #a7e9eb;
        }
        .pay-side-card--f2 .pay-side-card__icon { background: #d0f4f5; color: #0a8f94; }
        .pay-side-card--f2 .pay-side-card__k { color: #0a8f94; }
        .pay-side-card--f2 .pay-side-card__t { color: #2d6a6c; }
        /* ESC - danger */
        .pay-side-card--danger {
            background: #fff5f5;
            border: 1.5px solid #fecaca;
        }
        .pay-side-card--danger .pay-side-card__icon { background: #fee2e2; color: #dc2626; }
        .pay-side-card--danger .pay-side-card__k { color: #dc2626; }
        .pay-side-card--danger .pay-side-card__t { color: #9b2226; }
        /* F4 - muted */
        .pay-side-card--muted {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
        }
        .pay-side-card--muted .pay-side-card__icon { background: #eef0f6; color: #64748b; }
        .pay-side-card--muted .pay-side-card__k { color: #334155; }
        .pay-side-card--muted .pay-side-card__t { color: #64748b; }
        /* Registrar vale */
        .pay-side-card--vale {
            background: linear-gradient(180deg, #fffdf8 0%, #fff6ea 100%);
            border: 1.5px solid #f7d7b5;
        }
        .pay-side-card--vale .pay-side-card__icon { background: #fdead0; color: #b45309; }
        .pay-side-card--vale .pay-side-card__k { color: #9a3412; }
        .pay-side-card--vale .pay-side-card__t { color: #7c2d12; }
        /* Footer */
        .pay-side-footer {
            margin-top: auto;
            padding-top: .75rem;
            border-top: 1.5px dashed #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .pay-side-footer__label { font-size: .75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; }
        .pay-side-footer__count {
            background: #0a8f94; color: #fff;
            border-radius: 30px; padding: .15rem .65rem;
            font-size: .82rem; font-weight: 800;
        }

        @media (max-width: 1100px) {
            .pay-modal__card {
                height: min(92vh, 860px);
                max-height: 92vh;
            }
            .pay-modal__body { grid-template-columns: 1fr; }
            .pay-side { border-left: 0; border-top: 1px solid #eef0f6; flex-direction: row; flex-wrap: wrap; }
            .pay-side-card { flex: 1 1 140px; }
            .pay-summary { flex: 1 1 100%; grid-template-columns: 1fr 1fr; }
            .pay-side-footer { flex: 1 1 100%; }
            .pay-methods { grid-template-columns: repeat(3, minmax(0,1fr)); }
            .pay-ref-row { grid-template-columns: 1fr; }
            .pay-ref-row label { text-align: left; }
            .pay-cash__row { grid-template-columns: 1fr 52px; }
            .pay-cash__row label { display: none; }
        }
        .almacen-radio-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .55rem;
        }
        .almacen-radio {
            border: 1px solid #d7e0ea;
            border-radius: 12px;
            background: linear-gradient(180deg, #fff 0%, #fcfdff 100%);
            cursor: pointer;
            padding: .75rem .8rem;
            display: flex;
            align-items: flex-start;
            gap: .65rem;
            transition: border-color .15s, box-shadow .15s, background .15s, transform .08s;
        }
        .almacen-radio:hover {
            border-color: #b8c7d8;
            box-shadow: 0 6px 14px rgba(15, 35, 95, .08);
            transform: translateY(-1px);
        }
        .almacen-radio input {
            appearance: none;
            -webkit-appearance: none;
            width: 18px;
            height: 18px;
            border: 1.5px solid #9ca8ba;
            border-radius: 4px;
            margin-top: 2px;
            background: #fff;
            position: relative;
            flex-shrink: 0;
            pointer-events: none;
        }
        .almacen-radio input:checked {
            border-color: var(--ls-success);
            background: var(--ls-success);
        }
        .almacen-radio input:checked::after {
            content: '';
            position: absolute;
            left: 4px;
            top: 1px;
            width: 4px;
            height: 8px;
            border: solid #fff;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }
        .almacen-radio__txt {
            font-size: .82rem;
            font-weight: 700;
            color: var(--ls-text-primary);
            line-height: 1.2;
        }
        .almacen-radio__sub {
            font-size: .72rem;
            color: var(--ls-text-muted);
            margin-top: .12rem;
            line-height: 1.2;
        }
        .almacen-radio--active {
            border-color: rgba(26,158,109,.55);
            background: linear-gradient(180deg, rgba(26,158,109,.08) 0%, rgba(26,158,109,.03) 100%);
            box-shadow: 0 0 0 2px rgba(26,158,109,.14);
        }
        @media (max-width: 680px) {
            .almacen-radio-grid { grid-template-columns: 1fr; }
        }

        /* ── Caja Gate Modal ─────────────────────────────── */
        .caja-gate {
            position: fixed;
            inset: 0;
            background: rgba(10, 37, 64, .5);
            backdrop-filter: blur(3px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1200;
            padding: 1rem;
        }

        .caja-gate__card {
            width: min(820px, 100%);
            background: #fff;
            border-radius: 18px;
            border: 1px solid var(--ls-border);
            box-shadow: 0 24px 60px rgba(10,37,64,.18), 0 2px 8px rgba(10,37,64,.08);
            overflow: hidden;
            animation: gateIn .22s cubic-bezier(.22,.68,0,1.2);
        }

        @keyframes gateIn {
            from { opacity: 0; transform: scale(.96) translateY(10px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }

        /* Header */
        .caja-gate__head {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.25rem 1.4rem 1.1rem;
            border-bottom: 1px solid var(--ls-border);
            background: linear-gradient(160deg, #f8fbff 0%, #fff 60%);
        }

        .caja-gate__head-icon {
            width: 46px; height: 46px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--ls-success) 0%, #0d8a5e 100%);
            display: flex; align-items: center; justify-content: center;
            color: #fff;
            font-size: 1.3rem;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(26,158,109,.3);
        }

        .caja-gate__title {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--ls-text-primary);
            line-height: 1.2;
            margin: 0;
        }

        .caja-gate__sub {
            margin: .22rem 0 0;
            font-size: .79rem;
            color: var(--ls-text-muted);
            line-height: 1.45;
        }

        /* Body */
        .caja-gate__body {
            padding: 1.1rem 1.4rem 1.4rem;
        }

        .caja-gate__cols {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 0;
            align-items: stretch;
        }

        /* Divider */
        .caja-gate__sep {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0 1rem;
            gap: .4rem;
        }

        .caja-gate__sep-line {
            flex: 1;
            width: 1px;
            background: var(--ls-border);
        }

        .caja-gate__sep-label {
            font-size: .68rem;
            font-weight: 700;
            color: var(--ls-text-muted);
            background: #fff;
            border: 1px solid var(--ls-border);
            border-radius: 999px;
            width: 26px; height: 26px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        /* Choice cards */
        .caja-choice {
            border: 1.5px solid var(--ls-border);
            border-radius: 14px;
            padding: 1.1rem;
            display: flex;
            flex-direction: column;
            gap: .55rem;
            transition: border-color .15s, box-shadow .15s;
            background: #fff;
        }

        .caja-choice:hover {
            border-color: var(--ls-border-strong, #c8d0db);
            box-shadow: var(--ls-shadow-sm);
        }

        .caja-choice--accent:hover  { border-color: rgba(99,91,255,.4); }
        .caja-choice--success:hover { border-color: rgba(26,158,109,.4); }
        .caja-choice--priority {
            border-color: rgba(99,91,255,.65) !important;
            box-shadow: 0 0 0 3px rgba(99,91,255,.14);
        }

        .caja-choice__icon {
            width: 36px; height: 36px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .caja-choice__icon--accent  { background: var(--ls-accent-light); color: var(--ls-accent); }
        .caja-choice__icon--success { background: var(--ls-success-bg); color: var(--ls-success); }

        .caja-choice__title {
            font-size: .88rem;
            font-weight: 700;
            color: var(--ls-text-primary);
            margin: 0;
        }

        .caja-choice__hint {
            font-size: .74rem;
            color: var(--ls-text-muted);
            line-height: 1.45;
            margin: 0;
            min-height: 2em;
        }

        .caja-choice__empty {
            display: flex;
            align-items: center;
            gap: .45rem;
            font-size: .74rem;
            color: var(--ls-text-muted);
            background: var(--ls-surface-2);
            border: 1px dashed var(--ls-border);
            border-radius: 8px;
            padding: .55rem .7rem;
        }

        .caja-choice__select {
            width: 100%;
            height: 38px;
            padding: 0 2rem 0 .7rem;
            border: 1.5px solid var(--ls-border);
            border-radius: var(--ls-radius);
            font-size: .82rem;
            font-family: inherit;
            color: var(--ls-text-primary);
            background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%236b7c93'/%3E%3C/svg%3E") no-repeat right .65rem center;
            -webkit-appearance: none;
            appearance: none;
            outline: none;
            cursor: pointer;
            transition: border-color .15s, box-shadow .15s;
        }

        .caja-choice__select:focus {
            border-color: var(--ls-accent);
            box-shadow: 0 0 0 3px var(--ls-accent-light);
        }

        .caja-choice__select:disabled { opacity: .5; cursor: not-allowed; }

        .caja-choice__btn {
            width: 100%;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .4rem;
            font-size: .82rem;
            font-weight: 700;
            font-family: inherit;
            border-radius: var(--ls-radius);
            border: 1.5px solid transparent;
            cursor: pointer;
            transition: all .13s;
            margin-top: auto;
        }

        .caja-choice__btn:disabled { opacity: .4; cursor: not-allowed; }
        .caja-choice__btn:not(:disabled):active { transform: scale(.98); }

        .caja-choice__btn--accent {
            background: var(--ls-accent);
            color: #fff;
            border-color: var(--ls-accent);
            box-shadow: 0 2px 8px rgba(99,91,255,.25);
        }

        .caja-choice__btn--accent:not(:disabled):hover {
            background: var(--ls-accent-hover);
            box-shadow: 0 4px 12px rgba(99,91,255,.35);
        }

        .caja-choice__btn--success {
            background: var(--ls-success);
            color: #fff;
            border-color: var(--ls-success);
            box-shadow: 0 2px 8px rgba(26,158,109,.25);
        }

        .caja-choice__btn--success:not(:disabled):hover {
            background: var(--ls-success-hover);
            box-shadow: 0 4px 12px rgba(26,158,109,.35);
        }

        @media (max-width: 780px) {
            .caja-gate__cols { grid-template-columns: 1fr; }
            .caja-gate__sep { flex-direction: row; padding: .6rem 0; }
            .caja-gate__sep-line { flex: 1; width: auto; height: 1px; }
        }

        /* ── CORTE DE CAJA ─────────────────────────────────────────
           Cierre de sesión a pantalla completa: barra de contexto,
           hoja de conteo por denominación, columna de control con
           arqueo y resultado, y barra de acciones fija. ──────────── */
        .corte-caja {
            position: fixed;
            inset: 0;
            z-index: 1150;
            background: var(--ls-surface-2);
            display: flex;
            flex-direction: column;
            animation: corteFadeIn .18s ease-out;
        }

        @keyframes corteFadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }

        /* ── Barra superior ──────────────────────────────────────── */
        .corte-caja__head {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .6rem 1.5rem;
            background: var(--ls-surface);
            border-bottom: 1px solid var(--ls-border);
            box-shadow: var(--ls-shadow-sm);
        }

        .corte-caja__head-left { display: flex; align-items: center; gap: .7rem; min-width: 0; }

        .corte-caja__head-icon {
            width: 34px; height: 34px;
            border-radius: var(--ls-radius);
            background: linear-gradient(135deg, var(--ls-success) 0%, #0d8a5e 100%);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 1rem;
            box-shadow: 0 2px 6px rgba(26,158,109,.28);
            flex-shrink: 0;
        }

        .corte-caja__title {
            font-size: .95rem;
            font-weight: 800;
            color: var(--ls-text-primary);
            line-height: 1.15;
            letter-spacing: -.01em;
        }

        .corte-caja__sub { font-size: .72rem; color: var(--ls-text-muted); margin-top: 2px; }

        .corte-caja__head-right { display: flex; align-items: center; gap: .5rem; flex-shrink: 0; }

        .corte-chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            height: 28px;
            padding: 0 .65rem;
            border-radius: 999px;
            border: 1px solid var(--ls-border);
            background: var(--ls-surface-2);
            font-size: .72rem;
            font-weight: 700;
            color: var(--ls-text-secondary);
            max-width: 220px;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .corte-chip i { font-size: .8rem; color: var(--ls-text-muted); }
        .corte-chip--live { border-color: var(--ls-success-mid); background: var(--ls-success-bg); color: var(--ls-success-hover); }
        .corte-chip--live i { color: var(--ls-success); }

        /* ── Lienzo ──────────────────────────────────────────────── */
        .corte-caja__body {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .corte-caja__grid {
            max-width: 1420px;
            width: 100%;
            margin: 0 auto;
            padding: 1rem 1.5rem 1.4rem;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 358px;
            align-items: start;
            gap: 1rem;
        }

        /* ── Tarjeta base ────────────────────────────────────────── */
        .corte-card {
            background: var(--ls-surface);
            border: 1px solid var(--ls-border);
            border-radius: var(--ls-radius-xl);
            box-shadow: var(--ls-shadow-sm);
            overflow: hidden;
        }

        .corte-card__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .85rem;
            padding: .7rem .95rem;
            border-bottom: 1px solid var(--ls-border);
            background: linear-gradient(180deg, var(--ls-surface) 0%, var(--ls-surface-2) 100%);
        }

        .corte-card__head-left { display: flex; align-items: center; gap: .6rem; min-width: 0; }

        .corte-card__icon {
            width: 28px; height: 28px;
            border-radius: var(--ls-radius-sm);
            display: flex; align-items: center; justify-content: center;
            background: var(--ls-surface-3);
            color: var(--ls-text-secondary);
            font-size: .88rem;
            flex-shrink: 0;
        }

        .corte-card__icon--success { background: var(--ls-success-bg); color: var(--ls-success); }
        .corte-card__icon--accent  { background: var(--ls-accent-light); color: var(--ls-accent); }

        .corte-card__title { font-size: .82rem; font-weight: 800; color: var(--ls-text-primary); line-height: 1.2; }
        .corte-card__hint  { font-size: .7rem; color: var(--ls-text-muted); margin-top: 1px; font-weight: 500; }
        .corte-card__hint .corte-kbd { margin: 0 .1rem; vertical-align: -2px; }

        .corte-card__actions { display: flex; align-items: center; gap: .5rem; flex-shrink: 0; }

        .corte-total {
            display: inline-flex;
            align-items: baseline;
            gap: .4rem;
            padding: .3rem .7rem;
            border-radius: 999px;
            border: 1px solid var(--ls-success-mid);
            background: var(--ls-success-bg);
            white-space: nowrap;
        }

        .corte-total__label { font-size: .66rem; font-weight: 700; color: var(--ls-success-hover); letter-spacing: .01em; }
        .corte-total__value { font-size: .88rem; font-weight: 800; color: var(--ls-success-hover); font-variant-numeric: tabular-nums; }

        /* ── Hoja de conteo (compartida: corte de caja y retiros) ── */
        .count-sheet__columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        .count-sheet__col { min-width: 0; display: flex; flex-direction: column; }
        .count-sheet__col--divider { border-left: 1px solid var(--ls-border); }

        .count-sheet__colhead {
            display: grid;
            grid-template-columns: 88px minmax(0, 1fr) 108px;
            align-items: center;
            gap: .55rem;
            padding: .55rem .95rem .4rem;
            border-bottom: 1px solid var(--ls-border);
        }

        .count-sheet__coltitle {
            display: flex; align-items: center; gap: .35rem;
            font-size: .75rem; font-weight: 800; color: var(--ls-text-primary);
            grid-column: 1 / 2;
            white-space: nowrap;
        }

        .count-sheet__colmeta {
            font-size: .66rem;
            font-weight: 700;
            color: var(--ls-text-muted);
            letter-spacing: .02em;
        }

        .count-sheet__colmeta--center { text-align: center; }
        .count-sheet__colmeta--right  { text-align: right; }

        .count-sheet__rows { padding: .35rem 0; display: flex; flex-direction: column; flex: 1; }

        .count-row {
            display: grid;
            grid-template-columns: 88px minmax(0, 1fr) 108px;
            align-items: center;
            gap: .55rem;
            padding: .2rem .95rem;
            transition: background .12s;
        }

        .count-row:hover,
        .count-row:focus-within { background: var(--ls-surface-2); }

        .count-row__denom {
            display: flex; align-items: center; justify-content: center;
            height: 38px;
            border-radius: var(--ls-radius);
            border: 1px solid var(--ls-border);
            background: var(--ls-surface-2);
            color: var(--ls-text-secondary);
            font-weight: 800;
            font-size: .84rem;
            font-variant-numeric: tabular-nums;
            transition: border-color .15s, background .15s, color .15s;
        }

        .count-row.is-filled .count-row__denom {
            border-color: var(--ls-success-mid);
            background: var(--ls-success-bg);
            color: var(--ls-success-hover);
        }

        .count-row__input {
            width: 100%;
            height: 38px;
            text-align: center;
            border: 1.5px solid var(--ls-border);
            border-radius: var(--ls-radius);
            font-size: .95rem;
            font-weight: 700;
            font-family: inherit;
            color: var(--ls-text-primary);
            background: var(--ls-surface);
            font-variant-numeric: tabular-nums;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
            -moz-appearance: textfield;
        }

        .count-row__input::-webkit-outer-spin-button,
        .count-row__input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        .count-row__input::placeholder { color: var(--ls-border-strong); font-weight: 600; }

        .count-row__input:focus {
            border-color: var(--ls-success);
            box-shadow: 0 0 0 3px rgba(26,158,109,.13);
        }

        .count-row__amount {
            text-align: right;
            font-size: .86rem;
            font-weight: 700;
            color: var(--ls-border-strong);
            font-variant-numeric: tabular-nums;
            transition: color .15s;
        }

        .count-row.is-filled .count-row__amount { color: var(--ls-text-primary); }

        .count-sheet__colfoot {
            margin-top: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .6rem;
            padding: .5rem .95rem;
            border-top: 1px solid var(--ls-border);
            background: var(--ls-surface-2);
        }

        .count-sheet__colfoot-label { font-size: .72rem; font-weight: 700; color: var(--ls-text-secondary); }
        .count-sheet__colfoot-value { font-size: .86rem; font-weight: 800; color: var(--ls-text-primary); font-variant-numeric: tabular-nums; }

        /* Variante compacta: la misma hoja dentro de un modal */
        .count-sheet--compact .count-sheet__colhead,
        .count-sheet--compact .count-row { grid-template-columns: 76px minmax(0, 1fr) 92px; gap: .45rem; padding-left: .8rem; padding-right: .8rem; }
        .count-sheet--compact .count-sheet__rows { padding: .3rem 0; }
        .count-sheet--compact .count-sheet__colfoot { padding: .45rem .8rem; }
        .count-sheet--compact .count-row__denom,
        .count-sheet--compact .count-row__input { height: 34px; font-size: .82rem; }
        .count-sheet--compact .count-row__amount { font-size: .8rem; }

        /* Observaciones */
        .corte-notas { padding: .75rem .95rem .85rem; border-top: 1px solid var(--ls-border); }
        .corte-notas__label { font-size: .72rem; font-weight: 700; color: var(--ls-text-secondary); margin-bottom: .3rem; display: block; }
        .corte-notas__input {
            width: 100%;
            min-height: 72px;
            resize: vertical;
            padding: .55rem .7rem;
            border: 1.5px solid var(--ls-border);
            border-radius: var(--ls-radius);
            background: var(--ls-surface);
            color: var(--ls-text-primary);
            font-family: inherit;
            font-size: .82rem;
            line-height: 1.45;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        .corte-notas__input:focus { border-color: var(--ls-success); box-shadow: 0 0 0 3px rgba(26,158,109,.13); }
        .corte-notas__input::placeholder { color: var(--ls-text-muted); text-transform: none; font-weight: 500; }

        /* ── Columna de control ──────────────────────────────────── */
        .corte-side {
            display: grid;
            gap: .8rem;
            position: sticky;
            top: 0;
            align-content: start;
        }

        .corte-list { padding: .25rem .95rem .5rem; }

        .corte-list__row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .42rem 0;
            border-bottom: 1px solid var(--ls-border);
        }

        .corte-list__row:last-child { border-bottom: 0; }

        .corte-list__label { font-size: .76rem; color: var(--ls-text-secondary); min-width: 0; }
        .corte-list__label span { display: block; font-size: .66rem; color: var(--ls-text-muted); margin-top: 1px; }

        .corte-list__value {
            font-size: .82rem;
            font-weight: 700;
            color: var(--ls-text-primary);
            font-variant-numeric: tabular-nums;
            text-align: right;
            white-space: nowrap;
        }

        .corte-list__value--muted { color: var(--ls-text-muted); font-weight: 600; }
        .corte-list__value--neg   { color: var(--ls-danger); }

        .corte-list__row--total {
            margin-top: .35rem;
            padding-top: .6rem;
            border-top: 1.5px solid var(--ls-border-strong);
            border-bottom: 0;
        }

        .corte-list__row--total .corte-list__label { font-size: .8rem; font-weight: 800; color: var(--ls-text-primary); }
        .corte-list__row--total .corte-list__value { font-size: 1.18rem; font-weight: 900; letter-spacing: -.02em; }

        .corte-list__group {
            margin-top: .55rem;
            padding-top: .5rem;
            border-top: 1px dashed var(--ls-border);
        }

        .corte-list__group-label {
            font-size: .68rem;
            font-weight: 700;
            color: var(--ls-text-muted);
            margin-bottom: .1rem;
        }

        /* ── Barra de acciones con el resultado ──────────────────── */
        .corte-verdict__diff--ok    { color: var(--ls-success); }
        .corte-verdict__diff--sobra { color: #1d6fd8; }
        .corte-verdict__diff--falta { color: var(--ls-danger); }

        .corte-caja__footer {
            flex-shrink: 0;
            background: var(--ls-surface);
            border-top: 1px solid var(--ls-border);
            box-shadow: 0 -2px 10px rgba(10,37,64,.05);
        }

        .corte-caja__footer-inner {
            max-width: 1420px;
            width: 100%;
            margin: 0 auto;
            padding: .7rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .corte-foot-read {
            display: flex;
            align-items: center;
            gap: 1.15rem;
            min-width: 0;
            flex-wrap: wrap;
        }

        .corte-foot-read__item { min-width: 0; }

        .corte-foot-read__label {
            font-size: .67rem;
            font-weight: 700;
            color: var(--ls-text-muted);
            line-height: 1.1;
        }

        .corte-foot-read__value {
            font-size: 1rem;
            font-weight: 800;
            color: var(--ls-text-primary);
            font-variant-numeric: tabular-nums;
            line-height: 1.25;
        }

        .corte-foot-read__arrow { color: var(--ls-border-strong); font-size: 1rem; display: flex; align-items: center; }

        .corte-foot-read__item--diff {
            display: flex;
            align-items: center;
            gap: .55rem;
            padding-left: 1.15rem;
            border-left: 1px solid var(--ls-border);
        }

        .corte-foot-read__diff {
            font-size: 1.5rem;
            font-weight: 900;
            letter-spacing: -.03em;
            line-height: 1.05;
            color: var(--ls-text-primary);
            font-variant-numeric: tabular-nums;
            transition: color .15s;
        }

        .corte-foot-tag {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            font-size: .72rem;
            font-weight: 700;
            padding: .28rem .7rem;
            border-radius: 999px;
            white-space: nowrap;
        }

        .corte-foot-tag--ok    { background: var(--ls-success-bg); color: var(--ls-success-hover); }
        .corte-foot-tag--sobra { background: rgba(29,111,216,.09); color: #1d6fd8; }
        .corte-foot-tag--falta { background: var(--ls-danger-bg); color: var(--ls-danger); }

        .corte-kbd {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 19px;
            min-width: 26px;
            padding: 0 .3rem;
            border-radius: var(--ls-radius-sm);
            border: 1px solid var(--ls-border-strong);
            border-bottom-width: 2px;
            background: var(--ls-surface-2);
            font-size: .66rem;
            font-weight: 700;
            color: var(--ls-text-secondary);
        }

        .corte-caja__actions { display: flex; align-items: center; gap: .6rem; flex-shrink: 0; }
        .corte-caja__submit { height: 44px; padding: 0 1.9rem; font-size: .88rem; }

        /* ── Responsive ──────────────────────────────────────────── */
        @media (max-width: 1180px) {
            .corte-caja__grid { grid-template-columns: minmax(0, 1fr); }
            .corte-side { position: static; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 1080px) {
            .corte-foot-read { gap: .8rem; }
            .corte-foot-read__arrow { display: none; }
            .corte-foot-read__item--diff { padding-left: .8rem; }
            .corte-foot-tag { display: none; }
        }

        @media (max-width: 900px) {
            .corte-caja__grid { padding: .8rem 1rem 1.1rem; }
            .corte-side { grid-template-columns: minmax(0, 1fr); }
            .corte-caja__head { padding: .55rem 1rem; }
            .corte-caja__footer-inner { padding: .65rem 1rem; }
        }

        @media (max-width: 780px) {
            .count-sheet__columns { grid-template-columns: minmax(0, 1fr); }
            .count-sheet__col--divider { border-left: 0; border-top: 1px solid var(--ls-border); }
        }

        @media (max-width: 680px) {
            .corte-caja__head { flex-wrap: wrap; }
            .corte-caja__head-right { width: 100%; }
            .corte-caja__head-right .pos-btn { flex: 1 1 auto; }
            .corte-chip { display: none; }
            .count-row,
            .count-sheet__colhead { grid-template-columns: 72px minmax(0, 1fr) 92px; gap: .45rem; }
            .corte-caja__footer-inner { flex-direction: column; align-items: stretch; gap: .7rem; }
            .corte-foot-read { justify-content: space-between; }
            .corte-caja__actions { width: 100%; }
            .corte-caja__actions .pos-btn { flex: 1 1 auto; }
        }
    </style>
    {{-- ═══════════════════════════════════════════════════
         HEADER
    ═══════════════════════════════════════════════════ --}}
    <header class="pos-header">
        <div class="pos-header__brand">
            <div class="pos-header__logo">
                <i class="ti tabler-building-store"></i>
            </div>
            <div>
                <div class="pos-header__name">Punto de Venta</div>
                <div class="pos-header__sub">Sucursal: <strong x-text="sucursal"></strong></div>
            </div>
        </div>

        <div class="pos-header__right">
            <span class="pos-pill">
                <span class="pos-pill__dot pos-pill__dot--on"></span>
                <span class="pos-pill__cat">Caja</span>
                <span x-text="cajaNombre"></span>
            </span>
            <template x-if="ventaAlmacenNombre">
                <span class="pos-pill">
                    <span class="pos-pill__dot pos-pill__dot--on"></span>
                    <span class="pos-pill__cat">Almacén</span>
                    <span x-text="ventaAlmacenNombre"></span>
                </span>
            </template>
            <span class="pos-pill">
                <span class="pos-pill__dot pos-pill__dot--idle"></span>
                <span class="pos-pill__cat">Impresión</span>
                <span x-text="impresionEstado"></span>
            </span>
            <button class="pos-btn pos-btn--ghost" style="height:32px" @click="configurarAgenteImpresion()">
                <i class="ti tabler-printer" style="font-size:.9rem"></i>
                Agente
            </button>
            <template x-if="pedidoCargado">
                <span class="pos-pill">
                    <span class="pos-pill__dot pos-pill__dot--warn"></span>
                    <span class="pos-pill__cat">Pedido</span>
                    <span x-text="`${pedidoCargado.pdp_folio}${pedidoCargado.almacen ? ' · ' + pedidoCargado.almacen : ''}`"></span>
                </span>
            </template>
            <div class="pos-divider"></div>
            <button class="pos-btn pos-btn--ghost" style="height:32px" @click="abandonarCaja()">
                <i class="ti tabler-door-exit" style="font-size:.9rem"></i>
                Abandonar caja
            </button>
            <button class="pos-btn pos-btn--danger" style="height:32px" @click="salir()">
                <i class="ti tabler-logout" style="font-size:.9rem"></i>
                Salir
            </button>
        </div>
    </header>

    {{-- ═══════════════════════════════════════════════════
         TABS
    ═══════════════════════════════════════════════════ --}}
    <nav class="pos-tabs">
        <button class="pos-tab" :class="{ active: tab === 'ventas' }" @click="tab = 'ventas'">
            <span class="kbd">F1</span> Ventas
        </button>
        <button class="pos-tab" :class="{ active: tab === 'clientes' }" @click="tab = 'clientes'; abrirModalClientes()">
            <span class="kbd">F2</span> Clientes
        </button>
        <button class="pos-tab" :class="{ active: tab === 'cotizacion' }" @click="tab = 'cotizacion'">
            <span class="kbd">F3</span> Cotización
        </button>
        <button class="pos-tab" :class="{ active: tab === 'inventario' }" @click="tab = 'inventario'">
            <span class="kbd">F4</span> Inventario
        </button>
        <button class="pos-tab" :class="{ active: tab === 'caja' }" @click="abrirResumenCaja()">
            <span class="kbd">F5</span> Resumen caja
        </button>
        <button class="pos-tab" :class="{ active: tab === 'reimprimir' }" @click="tab = 'reimprimir'">
            <span class="kbd">F6</span> Reimprimir
        </button>
        <button class="pos-tab" :class="{ active: tab === 'vales' }" @click="tab = 'vales'; abrirModalValesCambio()">
            <span class="kbd">F9</span> Vales
        </button>
    </nav>

    {{-- ═══════════════════════════════════════════════════
         MAIN
    ═══════════════════════════════════════════════════ --}}
    <div class="pos-main">

        {{-- ── LEFT COLUMN ─────────────────────────────── --}}
        <div class="pos-left">

            {{-- Pending bar --}}
            <div class="pos-pending-bar" :class="ventasEspera.length > 0 ? 'pos-pending-bar--has-items' : ''">
                <div class="pos-pending-bar__msg-wrap">
                    <span class="pos-pending-bar__msg">
                        <i class="ti tabler-info-circle" style="font-size:.85rem;flex-shrink:0"></i>
                        Usa <kbd>F7</kbd> para listar pedidos en estado Pendiente Cobro y cargarlos al ticket.
                    </span>
                </div>

                {{-- Tab-style button, visually attached to the card below --}}
                <button class="pos-nueva-venta-tab" @click="nuevaVenta()">
                    <i class="ti tabler-plus"></i>
                    Nueva venta
                </button>
            </div>

            <div
                x-cloak
                x-show="sesionActiva && retiroCajaRecomendado"
                class="pos-cambio-banner"
                style="border-color:#f7c58b;background:#fff7ed;"
            >
                <div class="pos-cambio-banner__main">
                    <div class="pos-cambio-banner__top">
                        <span class="pos-cambio-banner__title" style="color:#9a3412;">Retiro de caja recomendado</span>
                    </div>
                    <div class="pos-cambio-banner__meta" style="color:#9a3412;">
                        Efectivo estimado <strong x-text="fmt(resumenCaja?.efectivo_disponible ?? 0)"></strong>
                        · Umbral <strong x-text="fmt(resumenCaja?.umbral_retiro ?? 0)"></strong>
                    </div>
                    <div class="pos-cambio-banner__hint" style="color:#9a3412;">
                        La caja ya alcanzó el monto configurado para retiro. Registra el retiro para mantener el efectivo controlado.
                    </div>
                </div>
                <div class="pos-cambio-banner__action" x-show="puedeRegistrarRetiroCaja">
                    <button class="pos-btn pos-btn--ghost pos-btn--sm" @click="retiroCaja()">Registrar retiro</button>
                </div>
            </div>

            {{-- Input zone --}}
            <div class="pos-input-zone">
                {{-- Producto --}}
                <div>
                    <label class="pos-field__label">Código / Producto</label>
                    <div class="pos-input-wrap">
                        <span class="input-icon"><i class="ti tabler-scan"></i></span>
                        <input
                            type="text"
                            class="pos-input pos-input--producto"
                            id="pos-producto-input"
                            x-ref="productoInput"
                            x-model="queryProducto"
                            placeholder="Nombre, SKU, código de barras o folio de pedido + Enter"
                            @keydown.enter="buscarProducto()"
                            @keydown.up.prevent="navegarSugerencia(-1)"
                            @keydown.down.prevent="navegarSugerencia(1)"
                            @keydown.escape="cerrarSugerencias()"
                            @input="onInputProducto()"
                            @focus="onInputProducto()"
                            autocomplete="off"
                        />
                        <div
                            x-cloak
                            x-show="mostrarSugerencias && sugerenciasProducto.length > 0"
                            class="pos-search-suggest"
                            x-ref="sugerenciasWrap"
                        >
                            <template x-for="(s, idx) in sugerenciasProducto" :key="s.psk_id + '-' + idx">
                                <div
                                    class="pos-search-suggest__item"
                                    :class="{ 'active': idx === sugerenciaActivaIndex }"
                                    :data-sugerencia-idx="idx"
                                    @click="seleccionarSugerencia(idx)"
                                >
                                    <span class="pos-search-suggest__name" x-text="s.psk_nombre || s.producto?.prd_nombre"></span>
                                    <span class="pos-search-suggest__meta" x-text="`${s.psk_codigo} · ${s.psk_codigo_barras || 'Sin barras'} · $${Number(s.psk_precio || 0).toFixed(2)}`"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Vendedor --}}
                <div>
                    <label class="pos-field__label">Vendedor</label>
                    <div class="pos-input-wrap pos-vendedor-select-wrap">
                        <span class="input-icon"><i class="ti tabler-user-dollar"></i></span>
                        <select
                            id="pos-vendedor-select"
                            class="pos-input pos-vendedor-select"
                            x-ref="vendedorSelect"
                            x-model="vendedorSeleccionadoId"
                        >
                            <option value=""></option>
                            <template x-for="v in vendedores" :key="v.usr_id">
                                <option :value="String(v.usr_id)" x-text="v.usr_usuario || v.usr_nombre || 'Sin usuario'"></option>
                            </template>
                        </select>
                    </div>
                </div>

                {{-- Cliente --}}
                <div>
                    <label class="pos-field__label">Cliente</label>
                    <div class="pos-input-wrap">
                        <span class="input-icon"><i class="ti tabler-users"></i></span>
                        <input
                            type="text"
                            class="pos-input"
                            x-ref="clienteInput"
                            x-model="queryCliente"
                            placeholder="3 letras para buscar o F2"
                            @input="onInputCliente()"
                            @focus="onInputCliente()"
                            @keydown.down.prevent="navegarSugerenciaCliente(1)"
                            @keydown.up.prevent="navegarSugerenciaCliente(-1)"
                            @keydown.enter.prevent="seleccionarClienteActivo()"
                            @keydown.escape="cerrarSugerenciasCliente()"
                            autocomplete="off"
                        />
                        <div
                            x-cloak
                            x-show="mostrarSugerenciasCliente && sugerenciasCliente.length > 0"
                            class="pos-search-suggest"
                        >
                            <template x-for="(c, idx) in sugerenciasCliente" :key="c.cli_id + '-' + idx">
                                <div
                                    class="pos-search-suggest__item"
                                    :class="{ 'active': idx === sugerenciaClienteActivaIndex }"
                                    @click="seleccionarCliente(idx)"
                                >
                                    <span class="pos-search-suggest__name" x-text="c.nombre"></span>
                                    <span class="pos-search-suggest__meta" x-text="`${c.telefono || 'Sin teléfono'} · ${c.rfc || 'Sin RFC'}`"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Scan hint bar --}}
            <div class="pos-scan-bar">
                <span class="pos-scan-bar__hint">
                    <i class="ti tabler-scan" style="font-size:.85rem"></i>
                    Captura rápida: escanea un código y presiona Enter.
                </span>
                <button
                    class="pos-btn pos-btn--danger-outline pos-btn--sm"
                    @click="limpiarTicket()"
                    :disabled="items.length === 0"
                    :class="items.length === 0 ? 'pos-btn--disabled' : ''"
                    style="opacity:1"
                    :style="items.length === 0 ? 'opacity:.4;cursor:not-allowed' : ''"
                >
                    <i class="ti tabler-trash" style="font-size:.8rem"></i>
                    Limpiar ticket
                </button>
            </div>

            <div x-cloak x-show="cambioActivo" class="pos-cambio-banner">
                <div class="pos-cambio-banner__main">
                    <div class="pos-cambio-banner__top">
                        <span class="pos-cambio-banner__title">Cambio en proceso</span>
                        <span x-show="cambioInvalidoMenorValor" class="pos-cambio-status">Falta completar el importe para continuar.</span>
                    </div>
                    <div class="pos-cambio-banner__meta">
                        Referencia <strong x-text="cambioActual?.psv_folio || 'N/D'"></strong>
                        · Disponible <strong x-text="fmt(creditoCambio)"></strong>
                    </div>
                    <div class="pos-cambio-banner__hint">
                        Elige tus nuevos artículos para aplicar este saldo.
                    </div>
                </div>
                <div class="pos-cambio-banner__action">
                    <button class="pos-btn pos-btn--ghost pos-btn--sm" @click="cancelarCambioActual()">Quitar cambio</button>
                </div>
            </div>

            {{-- Ticket table --}}
            <div class="pos-ticket-wrap">
                <table class="pos-ticket-table">
                    <thead>
                        <tr>
                            <th style="padding-left:0.9rem">Descripción</th>
                            <th style="min-width:130px">Vendedor</th>
                            <th style="min-width:80px">Precio</th>
                            <th style="min-width:110px">Cant.</th>
                            <th style="min-width:60px">Desc.</th>
                            <th style="min-width:90px">Importe</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="items.length === 0">
                            <tr>
                                <td colspan="7">
                                    <div class="pos-ticket__empty">
                                        <i class="ti tabler-shopping-cart pos-ticket__empty-icon"></i>
                                        <div class="pos-ticket__empty-text">
                                            Sin productos. Comienza escaneando un código.
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-for="(item, idx) in items" :key="item.sku + '-' + idx">
                            <tr class="pos-item-row">
                                <td style="padding-left:0.9rem">
                                    <div class="pos-ticket__desc" x-text="item.nombre"></div>
                                    <div class="pos-ticket__sku" x-text="item.sku"></div>
                                </td>
                                <td>
                                    <span class="pos-ticket__sku" x-text="item.vendedor || '—'"></span>
                                </td>
                                <td>
                                    <span class="pos-ticket__price" x-text="fmt(item.precio)"></span>
                                </td>
                                <td>
                                    <div class="pos-ticket__qty-wrap">
                                        <button class="pos-ticket__qty-btn" @click="decQty(idx)">
                                            <i class="ti tabler-minus" style="font-size:.65rem"></i>
                                        </button>
                                        <input
                                            type="number"
                                            class="pos-ticket__qty-input"
                                            x-model.number="item.cantidad"
                                            :min="item.permiteDecimal ? 0.01 : 1"
                                            :step="item.permiteDecimal ? 0.01 : 1"
                                            @change="item.cantidad = sanitizeQty(item.cantidad, item); recalcular()"
                                        />
                                        <button class="pos-ticket__qty-btn" @click="incQty(idx)">
                                            <i class="ti tabler-plus" style="font-size:.65rem"></i>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <button
                                        class="pos-inline-disc-btn"
                                        :class="item.descuento > 0 ? 'pos-inline-disc-btn--active' : ''"
                                        @click="abrirDescuentoItem(idx)"
                                        :disabled="descuentoGlobal > 0">
                                        <i class="ti tabler-percentage" style="font-size:.72rem"></i>
                                        <span x-text="item.descuento > 0 ? descuentoItemLabel(item) : 'Agregar'"></span>
                                    </button>
                                </td>
                                <td>
                                    <span class="pos-ticket__importe" x-text="fmt(itemImporte(item))"></span>
                                </td>
                                <td>
                                    <button class="pos-ticket__remove" @click="quitarItem(idx)" title="Quitar">
                                        <i class="ti tabler-x"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- User footer --}}
            <div class="pos-user-footer">
                <i class="ti tabler-user-circle" style="font-size:.9rem"></i>
                Usuario: <strong>{{ auth()->user()->usr_nombre ?? auth()->user()->usr_usuario ?? 'N/A' }}</strong>
                <span style="color:var(--ls-border-strong)">|</span>
                <i class="ti tabler-clock" style="font-size:.85rem"></i>
                <span x-text="fechaHora"></span>
            </div>
        </div>

        {{-- ── RIGHT PANEL ──────────────────────────────── --}}
        <aside class="pos-panel">

            {{-- Subtotal + descuento --}}
            <div class="pos-panel__block">
                <div class="pos-panel__row">
                    <span class="pos-panel__lbl">Subtotal</span>
                    <span class="pos-panel__val" x-text="fmt(subtotal)"></span>
                </div>
                <div class="pos-panel__row">
                    <span class="pos-panel__lbl">Descuento</span>
                    <span class="pos-panel__val"
                          :class="descuento > 0 ? 'pos-panel__val--danger' : 'pos-panel__val--muted'"
                          x-text="descuento > 0 ? '-' + fmt(descuento) : fmt(0)"></span>
                </div>
                <button class="pos-discount-btn" @click="aplicarDescuentoGlobal()" :disabled="tieneDescuentosPorProducto()">
                    <i class="ti tabler-percentage" style="font-size:.75rem"></i>
                    Descuento global
                </button>
                <div class="pos-discount-hint" x-show="tieneDescuentosPorProducto()" x-cloak>
                    Quita los descuentos por producto para usar uno global.
                </div>
            </div>

            {{-- Total --}}
            <div class="pos-total-block">
                <div class="pos-total-block__lbl" x-text="cambioActivo ? 'Diferencia a pagar' : 'Total'"></div>
                <div class="pos-total-block__amount">
                    <span>$</span><span x-text="fmtNum(total)"></span>
                </div>
            </div>

            <div class="pos-panel__block" x-cloak x-show="cambioActivo">
                <div class="pos-panel__row">
                    <span class="pos-panel__lbl">Nuevo carrito</span>
                    <span class="pos-panel__val" x-text="fmt(subtotal - descuento)"></span>
                </div>
                <div class="pos-panel__row">
                    <span class="pos-panel__lbl">Crédito aplicado</span>
                    <span class="pos-panel__val pos-panel__val--danger" x-text="'-' + fmt(creditoCambio)"></span>
                </div>
                <div style="font-size:.76rem;color:var(--ls-text-muted);line-height:1.35;margin-top:.45rem;">
                    Estás cobrando únicamente la diferencia entre el nuevo carrito y la mercancía devuelta.
                </div>
            </div>

            {{-- Pagado --}}
            <div class="pos-panel__block">
                <div class="pos-panel__row">
                    <span class="pos-panel__lbl">Pagado</span>
                    <span class="pos-panel__val pos-panel__val--muted" x-text="fmt(pagado)"></span>
                </div>
            </div>

            {{-- Artículos --}}
            <div class="pos-panel__block">
                <div class="pos-panel__row">
                    <span class="pos-panel__lbl">Artículos en ticket</span>
                    <span
                        class="pos-badge"
                        :class="totalArticulos > 0 ? 'pos-badge--success' : 'pos-badge--neutral'"
                        x-text="totalArticulos"
                    ></span>
                </div>
            </div>

            <div class="pos-panel__block">
                <div class="pos-panel__row">
                    <span class="pos-panel__lbl">Cliente</span>
                    <span class="pos-panel__val pos-panel__val--muted" x-text="clienteSeleccionado?.nombre || 'Público general'"></span>
                </div>
            </div>

            {{-- Notas --}}
            <div class="pos-panel__block" style="flex:1">
                <label class="pos-panel__lbl" style="display:block;margin-bottom:.4rem">
                    <i class="ti tabler-notes" style="font-size:.8rem;vertical-align:-1px"></i>
                    Notas
                </label>
                <textarea
                    class="pos-notes-textarea"
                    x-ref="notasInput"
                    x-model="notas"
                    placeholder="Observaciones de la venta"
                ></textarea>
            </div>

        </aside>
    </div>

    {{-- ═══════════════════════════════════════════════════
         BOTTOM BAR
    ═══════════════════════════════════════════════════ --}}
    <footer class="pos-bottom">

        {{-- Cash movements --}}
        <div class="pos-bottom__group">
            <button x-show="puedeRegistrarRetiroCaja" class="pos-btn pos-btn--danger-outline" @click="retiroCaja()">
                <i class="ti tabler-arrow-bar-up" style="font-size:.85rem"></i>
                Retiro caja
            </button>
            <button x-show="puedeRegistrarGastoCaja" class="pos-btn pos-btn--warning-outline" @click="gastoCaja()">
                <i class="ti tabler-receipt" style="font-size:.85rem"></i>
                Gasto caja
            </button>
        </div>

        {{-- Center actions --}}
        <div class="pos-bottom__center">
            <button class="pos-btn pos-btn--ghost" @click="enviarEspera()">
                <span class="kbd">F7</span>
                Pendiente
            </button>
            <button class="pos-btn pos-btn--ghost" @click="devolucion()">
                <span class="kbd">F8</span>
                Cambio
            </button>
            <button class="pos-btn pos-btn--ghost" @click="buscarTicket()">
                <span class="kbd">F9</span>
                Buscar Ticket
            </button>
            <button class="pos-btn pos-btn--danger-outline" @click="corteCaja()">
                <span class="kbd" style="background:var(--ls-danger-bg);border-color:var(--ls-danger-mid);color:var(--ls-danger)">F11</span>
                Corte de caja
            </button>
        </div>

        {{-- Right actions --}}
        <div class="pos-bottom__group">
            <button
                class="pos-btn pos-btn--cobrar"
                @click="cobrar()"
                :disabled="items.length === 0"
            >
                <span class="kbd">F12</span>
                COBRAR
            </button>
        </div>

    </footer>

    <div x-cloak x-show="mostrarModalPedido" class="variant-modal">
        <div class="variant-modal__card" style="max-width:560px;">
            <div class="variant-modal__head">
                Pedidos pendientes de cobro
            </div>
            <div style="padding:1rem;">
                <label style="display:block;font-size:.78rem;color:var(--ls-text-muted);margin-bottom:.35rem;">Buscar pedido</label>
                <input
                    type="text"
                    class="pos-input"
                    x-model="folioPedidoBuscar"
                    placeholder="Folio, vendedor o almacén"
                    @keydown.enter.prevent="cargarPedidosPendientes()"
                />
                <div style="margin-top:.55rem;display:flex;justify-content:flex-end;">
                    <button class="pos-btn pos-btn--ghost" @click="cargarPedidosPendientes()">Buscar</button>
                </div>
                <template x-if="pedidoMensaje">
                    <div style="margin-top:.6rem;font-size:.78rem;color:var(--ls-danger);" x-text="pedidoMensaje"></div>
                </template>
                <div style="margin-top:.7rem;max-height:280px;overflow:auto;border:1px solid var(--ls-border);border-radius:.6rem;background:#fff;">
                    <template x-if="cargandoPedidosPendientes">
                        <div style="padding:.7rem .8rem;font-size:.8rem;color:var(--ls-text-muted);">Cargando pedidos...</div>
                    </template>
                    <template x-if="!cargandoPedidosPendientes && pedidosPendientes.length === 0">
                        <div style="padding:.7rem .8rem;font-size:.8rem;color:var(--ls-text-muted);">No hay pedidos pendientes disponibles.</div>
                    </template>
                    <template x-if="!cargandoPedidosPendientes && pedidosPendientes.length > 0">
                        <div>
                            <template x-for="p in pedidosPendientes" :key="p.pdp_id">
                                <button
                                    type="button"
                                    style="display:flex;width:100%;justify-content:space-between;align-items:flex-start;text-align:left;padding:.6rem .75rem;border:0;border-bottom:1px solid var(--ls-border);background:#fff;"
                                    @click="seleccionarPedidoPendiente(p)"
                                >
                                    <span>
                                        <span style="display:block;font-weight:700;font-size:.82rem;" x-text="p.pdp_folio"></span>
                                        <span style="display:block;font-size:.75rem;color:var(--ls-text-muted);" x-text="`${p.sucursal || '-'} · ${p.almacen || '-'} · ${p.vendedor || '-'}`"></span>
                                    </span>
                                    <span style="font-weight:700;font-size:.82rem;" x-text="`$${Number(p.pdp_total || 0).toFixed(2)}`"></span>
                                </button>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
            <div style="padding:.7rem .9rem;display:flex;justify-content:flex-end;gap:.5rem;">
                <button class="pos-btn pos-btn--ghost" @click="cerrarModalPedido()">Cancelar</button>
            </div>
        </div>
    </div>

    <div x-cloak x-show="mostrarModalConfirmacionPedido" class="variant-modal">
        <div class="variant-modal__card" style="max-width:520px;">
            <div class="variant-modal__head">
                Confirmar reemplazo de ticket
            </div>
            <div style="padding:1rem;font-size:.86rem;color:var(--ls-text-secondary);">
                El ticket actual tiene productos. ¿Deseas reemplazarlo con el pedido seleccionado?
            </div>
            <div style="padding:.7rem .9rem;display:flex;justify-content:flex-end;gap:.5rem;">
                <button class="pos-btn pos-btn--ghost" @click="cancelarReemplazoPedido()">Cancelar</button>
                <button class="pos-btn pos-btn--success-outline" @click="confirmarReemplazoPedido()">Aceptar</button>
            </div>
        </div>
    </div>

    <div x-cloak x-show="mostrarModalAviso" class="variant-modal variant-modal--front" @keydown.escape.window="cerrarModalAviso()">
        <div class="variant-modal__card" style="max-width:520px;">
            <div class="variant-modal__head" style="display:flex;justify-content:space-between;align-items:center;gap:.75rem;">
                <span x-text="modalAvisoTitulo || 'Aviso'"></span>
                <button type="button" class="pos-btn pos-btn--ghost pos-btn--sm" @click="cerrarModalAviso()">Cerrar</button>
            </div>
            <div style="padding:1rem 1rem .85rem;font-size:.88rem;line-height:1.5;color:var(--ls-text-secondary);" x-text="modalAvisoMensaje"></div>
            <div style="padding:0 1rem 1rem;display:flex;justify-content:flex-end;">
                <button type="button" class="pos-btn pos-btn--success-outline" @click="cerrarModalAviso()">Entendido</button>
            </div>
        </div>
    </div>

    <div x-cloak x-show="mostrarModalTickets" class="variant-modal">
        <div class="variant-modal__card" style="max-width:760px;">
            <div class="variant-modal__head">Tickets del día</div>
            <div style="padding:1rem;">
                <div style="display:flex;gap:.5rem;margin-bottom:.7rem;">
                    <input type="text" class="pos-input" x-model="filtroTicket" @keydown.enter.prevent="cargarVentasDia()" placeholder="Buscar por folio..." />
                    <button class="pos-btn pos-btn--ghost" @click="cargarVentasDia()">Buscar</button>
                </div>
                <div style="max-height:340px;overflow:auto;border:1px solid var(--ls-border);border-radius:.6rem;background:#fff;">
                    <template x-if="cargandoTickets">
                        <div style="padding:.7rem .8rem;font-size:.8rem;color:var(--ls-text-muted);">Cargando...</div>
                    </template>
                    <template x-if="!cargandoTickets && ventasDelDia.length === 0">
                        <div style="padding:.7rem .8rem;font-size:.8rem;color:var(--ls-text-muted);">Sin ventas del día en esta sesión.</div>
                    </template>
                    <template x-for="v in ventasDelDia" :key="v.psv_id">
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:.55rem .75rem;border-bottom:1px solid var(--ls-border);">
                            <div>
                                <div style="font-weight:700;font-size:.82rem;" x-text="v.psv_folio"></div>
                                <div style="font-size:.74rem;color:var(--ls-text-muted);" x-text="`${v.psv_fecha_cobro || ''} · ${v.psv_metodo_pago || ''}`"></div>
                                <div style="font-size:.72rem;color:var(--ls-text-muted);" x-text="`${etiquetaOperacion(v.psv_tipo_operacion)} · ${etiquetaEstatus(v.psv_estatus)}`"></div>
                            </div>
                            <div style="display:flex;align-items:center;gap:.45rem;flex-wrap:wrap;justify-content:flex-end;">
                                <span style="font-weight:700;font-size:.82rem;" x-text="fmt(v.psv_total)"></span>
                                <button class="pos-btn pos-btn--ghost" @click="abrirTicketVenta(v.psv_id)">Imprimir</button>
                                <button
                                    x-show="puedeRegistrarCambio && v.psv_estatus !== 'cancelada' && v.psv_tipo_operacion === 'venta'"
                                    class="pos-btn pos-btn--ghost"
                                    @click="abrirCambioDesdeVenta(v.psv_id)"
                                >Cambio</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            <div style="padding:.7rem .9rem;display:flex;justify-content:flex-end;">
                <button class="pos-btn pos-btn--ghost" @click="mostrarModalTickets = false">Cerrar</button>
            </div>
        </div>
    </div>

    <div x-cloak x-show="mostrarModalCambio" class="variant-modal">
        <div class="variant-modal__card" style="max-width:920px;">
            <div class="variant-modal__head" style="display:flex;justify-content:space-between;align-items:center;gap:.75rem;">
                <span>Cambio / devolución sin reembolso</span>
                <button class="pos-btn pos-btn--ghost pos-btn--sm" @click="cerrarModalCambio()">Cerrar</button>
            </div>
            <div style="padding:1rem;">
                <div style="display:flex;gap:.5rem;align-items:center;margin-bottom:.85rem;">
                    <input
                        type="text"
                        class="pos-input"
                        x-model="folioCambioBuscar"
                        @keydown.enter.prevent="buscarVentaCambioPorFolio()"
                        placeholder="Captura el folio de la venta original"
                    />
                    <button class="pos-btn pos-btn--ghost" @click="buscarVentaCambioPorFolio()" :disabled="cargandoVentaCambio">Buscar</button>
                </div>
                <template x-if="mensajeCambio">
                    <div style="font-size:.78rem;color:#b42318;margin-bottom:.7rem;" x-text="mensajeCambio"></div>
                </template>
                <template x-if="ventaCambioPreview">
                    <div>
                        <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem;margin-bottom:.9rem;">
                            <div style="padding:.7rem;border:1px solid var(--ls-border);border-radius:.7rem;background:#fff;">
                                <div style="font-size:.72rem;color:var(--ls-text-muted);">Folio</div>
                                <div style="font-size:.86rem;font-weight:700;" x-text="ventaCambioPreview.psv_folio"></div>
                            </div>
                            <div style="padding:.7rem;border:1px solid var(--ls-border);border-radius:.7rem;background:#fff;">
                                <div style="font-size:.72rem;color:var(--ls-text-muted);">Cliente</div>
                                <div style="font-size:.86rem;font-weight:700;" x-text="ventaCambioPreview.cliente_nombre"></div>
                            </div>
                            <div style="padding:.7rem;border:1px solid var(--ls-border);border-radius:.7rem;background:#fff;">
                                <div style="font-size:.72rem;color:var(--ls-text-muted);">Tipo</div>
                                <div style="font-size:.86rem;font-weight:700;" x-text="etiquetaOperacion(ventaCambioPreview.psv_tipo_operacion)"></div>
                            </div>
                            <div style="padding:.7rem;border:1px solid var(--ls-border);border-radius:.7rem;background:#fff;">
                                <div style="font-size:.72rem;color:var(--ls-text-muted);">Estatus</div>
                                <div style="font-size:.86rem;font-weight:700;" x-text="etiquetaEstatus(ventaCambioPreview.psv_estatus)"></div>
                            </div>
                        </div>
                        <div style="max-height:320px;overflow:auto;border:1px solid var(--ls-border);border-radius:.75rem;background:#fff;">
                            <table class="pos-ticket-table" style="min-width:100%;">
                                <thead>
                                    <tr>
                                        <th style="padding-left:.8rem;">Producto</th>
                                        <th>Disponible</th>
                                        <th>Precio</th>
                                        <th>Devolver</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="detalle in ventaCambioPreview.detalle" :key="detalle.pvd_id">
                                        <tr>
                                            <td style="padding-left:.8rem;">
                                                <div style="font-weight:700;font-size:.78rem;" x-text="detalle.sku_nombre || detalle.psk_codigo"></div>
                                                <div style="font-size:.72rem;color:var(--ls-text-muted);" x-text="detalle.psk_codigo"></div>
                                            </td>
                                            <td x-text="fmtNum(detalle.cantidad_disponible)"></td>
                                            <td x-text="fmt(detalle.precio_unitario)"></td>
                                            <td>
                                                <div class="pos-ticket__qty-wrap" style="justify-content:center;">
                                                    <button
                                                        type="button"
                                                        class="pos-ticket__qty-btn"
                                                        @click="decCantidadCambio(detalle)"
                                                    >
                                                        <i class="ti tabler-minus"></i>
                                                    </button>
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        class="pos-ticket__qty-input"
                                                        x-model="detalle.devolver_cantidad"
                                                        @input="normalizarCantidadCambio(detalle)"
                                                        :max="detalle.cantidad_disponible"
                                                    />
                                                    <button
                                                        type="button"
                                                        class="pos-ticket__qty-btn"
                                                        @click="incCantidadCambio(detalle)"
                                                    >
                                                        <i class="ti tabler-plus"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:.75rem;margin-top:.9rem;">
                            <div style="font-size:.8rem;color:var(--ls-text-muted);">
                                Crédito calculado:
                                <strong style="color:var(--ls-text);" x-text="fmt(creditoCambioPreview())"></strong>
                            </div>
                            <button class="pos-btn pos-btn--cobrar" @click="activarCambioDesdePreview()">Generar vale de cambio</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <div x-cloak x-show="mostrarModalResumenCaja" class="variant-modal" @keydown.escape.window="mostrarModalResumenCaja = false">
        <div class="variant-modal__card cash-summary-modal__card">
            <div class="variant-modal__head" style="display:flex;justify-content:space-between;align-items:center;gap:.75rem;">
                <span>Resumen de caja</span>
                <button class="pos-btn pos-btn--ghost pos-btn--sm" @click="mostrarModalResumenCaja = false">Cerrar</button>
            </div>
            <div class="cash-summary-modal__body">
                <div class="cash-summary__grid">
                    <div class="cash-summary__item">
                        <div class="cash-summary__label">Caja</div>
                        <div class="cash-summary__value" x-text="cajaNombre || 'Sin caja activa'"></div>
                    </div>
                    <div class="cash-summary__item">
                        <div class="cash-summary__label">Sucursal</div>
                        <div class="cash-summary__value" x-text="sucursal || 'Sin sucursal'"></div>
                    </div>
                    <div class="cash-summary__item">
                        <div class="cash-summary__label">Ventas en sesión</div>
                        <div class="cash-summary__value" x-text="String(resumenCaja?.ventas_del_dia ?? (ventasDelDia || []).length)"></div>
                    </div>
                    <div class="cash-summary__item">
                        <div class="cash-summary__label">Total de sesión</div>
                        <div class="cash-summary__value" x-text="fmt(resumenCaja?.total_vendido ?? totalVentasDia)"></div>
                    </div>
                </div>

                <div class="cash-summary__layout">
                    <div class="cash-summary__section">
                        <div class="cash-summary__section-head">Movimientos de la sesión</div>
                        <div style="max-height:360px;overflow:auto;background:#fff;">
                            <table class="cash-summary__table">
                                <thead>
                                    <tr>
                                        <th>Folio</th>
                                        <th>Tipo</th>
                                        <th>Desglose de pago</th>
                                        <th>Hora</th>
                                        <th>Crédito cambio</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-if="(ventasDelDia || []).length === 0">
                                        <tr><td colspan="6" style="text-align:center;color:var(--ls-text-muted);padding:.9rem;">Sin movimientos registrados en la sesión.</td></tr>
                                    </template>
                                    <template x-for="v in ventasDelDia" :key="v.psv_id">
                                        <tr>
                                            <td x-text="v.psv_folio"></td>
                                            <td x-text="etiquetaOperacion(v.psv_tipo_operacion)"></td>
                                            <td>
                                                <div class="cash-summary__payment-list">
                                                    <template x-for="pago in (v.pagos || [])" :key="`${pago.clave}-${pago.monto}`">
                                                        <div class="cash-summary__payment">
                                                            <span x-text="etiquetaMetodoPago(pago.clave)"></span>
                                                            <strong x-text="fmt(pago.monto)"></strong>
                                                        </div>
                                                    </template>
                                                </div>
                                            </td>
                                            <td x-text="horaCorta(v.psv_fecha_cobro)"></td>
                                            <td x-text="Number(v.psv_credito_cambio || 0) > 0 ? fmt(v.psv_credito_cambio) : '—'"></td>
                                            <td x-text="fmt(v.psv_total)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="cash-summary__stack">
                        <div class="cash-summary__section">
                            <div class="cash-summary__section-head">Ventas por método de pago</div>
                            <div class="cash-summary__section-body">
                                <div class="cash-summary__metric-list">
                                    <template x-for="metodo in (resumenCaja?.ventas_por_metodo || [])" :key="metodo.clave">
                                        <div class="cash-summary__metric">
                                            <span x-text="metodo.label"></span>
                                            <strong x-text="fmt(metodo.monto)"></strong>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div class="cash-summary__section">
                            <div class="cash-summary__section-head">Crédito</div>
                            <div class="cash-summary__section-body">
                                <div class="cash-summary__metric-list">
                                    <div class="cash-summary__metric">
                                        <span>
                                            Crédito de ventas
                                            <small>Sin flujo activo identificado en este POS</small>
                                        </span>
                                        <strong x-text="fmt(resumenCaja?.credito_ventas ?? 0)"></strong>
                                    </div>
                                    <div class="cash-summary__metric">
                                        <span>
                                            Abono de crédito
                                            <small>Sin movimientos registrados en esta sesión</small>
                                        </span>
                                        <strong x-text="fmt(resumenCaja?.abono_credito ?? 0)"></strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="cash-summary__section">
                            <div class="cash-summary__section-head">Cambios</div>
                            <div class="cash-summary__section-body">
                                <div class="cash-summary__metric-list">
                                    <div class="cash-summary__metric">
                                        <span>Crédito de cambios</span>
                                        <strong x-text="fmt(resumenCaja?.credito_cambios ?? 0)"></strong>
                                    </div>
                                    <div class="cash-summary__metric">
                                        <span>Cambios realizados</span>
                                        <strong x-text="String(resumenCaja?.cantidad_cambios ?? 0)"></strong>
                                    </div>
                                    <div class="cash-summary__metric">
                                        <span>Importe cobrado por cambios</span>
                                        <strong x-text="fmt(resumenCaja?.importe_cobrado_cambios ?? 0)"></strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="cash-summary__section">
                            <div class="cash-summary__section-head">Caja</div>
                            <div class="cash-summary__section-body">
                                <div class="cash-summary__metric-list">
                                    <div class="cash-summary__metric">
                                        <span>Inicio de caja</span>
                                        <strong x-text="fmt(resumenCaja?.inicio_caja ?? 0)"></strong>
                                    </div>
                                    <div class="cash-summary__metric">
                                        <span>Efectivo por ventas</span>
                                        <strong x-text="fmt(resumenCaja?.efectivo_ventas_neto ?? 0)"></strong>
                                    </div>
                                    <div class="cash-summary__metric">
                                        <span>Efectivo disponible</span>
                                        <strong x-text="fmt(resumenCaja?.efectivo_disponible ?? 0)"></strong>
                                    </div>
                                    <div class="cash-summary__metric">
                                        <span>Umbral de retiro</span>
                                        <strong x-text="fmt(resumenCaja?.umbral_retiro ?? 0)"></strong>
                                    </div>
                                    <div class="cash-summary__metric">
                                        <span>Gastos</span>
                                        <strong x-text="fmt(resumenCaja?.gastos ?? 0)"></strong>
                                    </div>
                                    <div class="cash-summary__metric">
                                        <span>Retiros de caja</span>
                                        <strong x-text="fmt(resumenCaja?.retiros ?? 0)"></strong>
                                    </div>
                                </div>
                                <div class="cash-summary__muted-note">
                                    Todo el resumen corresponde a la sesión de caja activa, incluyendo ventas, cambios, retiros y gastos registrados.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-cloak x-show="mostrarModalValesCambio" class="variant-modal" @keydown.escape.window="cerrarModalValesCambio()">
        <div class="variant-modal__card" style="max-width:1100px;">
            <div class="variant-modal__head" style="display:flex;justify-content:space-between;align-items:center;gap:.75rem;">
                <span>Vales de cambio</span>
                <button class="pos-btn pos-btn--ghost pos-btn--sm" @click="cerrarModalValesCambio()">Cerrar</button>
            </div>
            <div style="padding:1rem;">
                <div style="display:grid;grid-template-columns:1.2fr 1fr .8fr auto;gap:.65rem;align-items:end;margin-bottom:.9rem;">
                    <div>
                        <label class="pos-field__label">Folio</label>
                        <input type="text" class="pos-input" x-model="filtrosValesCambio.folio" placeholder="CDC-001-000001">
                    </div>
                    <div>
                        <label class="pos-field__label">Cliente</label>
                        <input type="text" class="pos-input" x-model="filtrosValesCambio.cliente" placeholder="Nombre o razón social">
                    </div>
                    <div>
                        <label class="pos-field__label">Estatus</label>
                        <select class="pos-input" x-model="filtrosValesCambio.estatus">
                            <option value="">Todos</option>
                            <option value="disponible">Disponible</option>
                            <option value="parcial">Parcial</option>
                            <option value="aplicado">Aplicado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                    <div style="display:flex;gap:.45rem;">
                        <button class="pos-btn pos-btn--ghost" @click="cargarValesCambio()" :disabled="cargandoValesCambio">Buscar</button>
                        <button class="pos-btn pos-btn--danger-outline" @click="limpiarFiltrosValesCambio()">Limpiar</button>
                    </div>
                </div>

                <template x-if="mensajeValesCambio">
                    <div style="font-size:.8rem;color:#b42318;margin-bottom:.7rem;" x-text="mensajeValesCambio"></div>
                </template>

                <div style="max-height:65vh;overflow:auto;border:1px solid var(--ls-border);border-radius:16px;background:#fff;">
                    <table class="pos-ticket-table" style="min-width:920px;">
                        <thead>
                            <tr>
                                <th style="padding-left:.9rem;">Folio</th>
                                <th>Cliente</th>
                                <th>Venta origen</th>
                                <th>Almacén</th>
                                <th>Estatus</th>
                                <th>Crédito</th>
                                <th>Saldo</th>
                                <th>Fecha</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="cargandoValesCambio">
                                <tr>
                                    <td colspan="9">
                                        <div class="pos-ticket__empty">Cargando vales...</div>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="!cargandoValesCambio && valesCambio.length === 0">
                                <tr>
                                    <td colspan="9">
                                        <div class="pos-ticket__empty">No hay vales para los filtros seleccionados.</div>
                                    </td>
                                </tr>
                            </template>
                            <template x-for="vale in valesCambio" :key="vale.pcc_id">
                                <tr>
                                    <td style="padding-left:.9rem;">
                                        <div class="pos-ticket__desc" x-text="vale.pcc_folio"></div>
                                    </td>
                                    <td><span class="pos-ticket__sku" x-text="vale.cliente_nombre"></span></td>
                                    <td><span class="pos-ticket__sku" x-text="vale.venta_origen_folio || '—'"></span></td>
                                    <td><span class="pos-ticket__sku" x-text="vale.almacen || '—'"></span></td>
                                    <td><span class="pos-ticket__sku" x-text="etiquetaEstatusVale(vale.pcc_estatus)"></span></td>
                                    <td><span class="pos-ticket__price" x-text="fmt(vale.pcc_total_credito)"></span></td>
                                    <td><span class="pos-ticket__price" x-text="fmt(vale.pcc_saldo_disponible)"></span></td>
                                    <td><span class="pos-ticket__sku" x-text="vale.pcc_fecha_generado || '—'"></span></td>
                                    <td>
                                        <button class="pos-btn pos-btn--ghost pos-btn--sm" @click="abrirTicketCreditoCambio(vale.pcc_id)">Imprimir</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div x-cloak x-show="mostrarModalMovimientoCaja" class="variant-modal" @keydown.escape.window="cerrarModalMovimientoCaja()">
        <div
            class="variant-modal__card cash-modal__card"
            :class="{ 'cash-modal__card--narrow': movimientoCajaTipo !== 'retiro' || movimientoCajaPaso === 2 }"
        >
            {{-- Cabecera --}}
            <div class="cash-modal__head">
                <div class="cash-modal__head-left">
                    <div class="cash-modal__icon" :class="{ 'cash-modal__icon--gasto': movimientoCajaTipo === 'gasto' }">
                        <i class="ti" :class="movimientoCajaTipo === 'retiro' ? 'tabler-cash-banknote-off' : 'tabler-receipt-2'"></i>
                    </div>
                    <div>
                        <div class="cash-modal__title" x-text="movimientoCajaTipo === 'retiro' ? 'Registrar retiro de caja' : 'Registrar gasto de caja'"></div>
                        <div class="cash-modal__sub">
                            <span x-text="cajaNombre || 'Sin caja'"></span>
                            ·
                            <span x-text="usuarioActualNombre || 'Usuario actual'"></span>
                        </div>
                    </div>
                </div>
                <div class="cash-modal__head-right">
                    <div x-show="movimientoCajaTipo === 'retiro'" class="cash-steps" aria-label="Progreso del retiro">
                        <span class="cash-step" :class="movimientoCajaPaso === 1 ? 'cash-step--active' : 'cash-step--done'">
                            <span class="cash-step__num" x-text="movimientoCajaPaso > 1 ? '✓' : '1'"></span>
                            Efectivo
                        </span>
                        <span class="cash-step__line"></span>
                        <span class="cash-step" :class="{ 'cash-step--active': movimientoCajaPaso === 2 }">
                            <span class="cash-step__num">2</span>
                            Autorización
                        </span>
                    </div>
                    <button type="button" class="cash-modal__close" @click="cerrarModalMovimientoCaja()" aria-label="Cerrar">
                        <i class="ti tabler-x"></i>
                    </button>
                </div>
            </div>

            {{-- Cuerpo --}}
            <div class="cash-modal__body">
                <template x-if="movimientoCajaErrores.general">
                    <div class="cash-note cash-note--danger">
                        <i class="ti tabler-alert-triangle"></i>
                        <div>
                            <strong>No fue posible continuar.</strong>
                            <span x-text="movimientoCajaErrores.general"></span>
                        </div>
                    </div>
                </template>

                {{-- Indicadores de la caja --}}
                <div x-show="movimientoCajaTipo !== 'retiro' || movimientoCajaPaso === 1" class="cash-stats">
                    <div class="cash-stat">
                        <div class="cash-stat__label">Efectivo disponible</div>
                        <div class="cash-stat__value" x-text="fmt(resumenCaja?.efectivo_disponible ?? 0)"></div>
                    </div>
                    <template x-if="movimientoCajaTipo === 'retiro'">
                        <div class="cash-stat">
                            <div class="cash-stat__label">Umbral de retiro</div>
                            <div class="cash-stat__value" x-text="(resumenCaja?.umbral_retiro ?? 0) > 0 ? fmt(resumenCaja.umbral_retiro) : 'Sin umbral'"></div>
                        </div>
                    </template>
                    <template x-if="movimientoCajaTipo === 'retiro'">
                        <div class="cash-stat" :class="{ 'cash-stat--warning': (resumenCaja?.excedente_umbral ?? 0) > 0 }">
                            <div class="cash-stat__label">Excedente sobre el umbral</div>
                            <div class="cash-stat__value" x-text="fmt(resumenCaja?.excedente_umbral ?? 0)"></div>
                        </div>
                    </template>
                    <template x-if="movimientoCajaTipo === 'gasto'">
                        <div class="cash-stat">
                            <div class="cash-stat__label">Caja activa</div>
                            <div class="cash-stat__value" x-text="cajaNombre || 'Sin caja'"></div>
                        </div>
                    </template>
                    <template x-if="movimientoCajaTipo === 'gasto'">
                        <div class="cash-stat">
                            <div class="cash-stat__label">Gastos de la sesión</div>
                            <div class="cash-stat__value" x-text="fmt(resumenCaja?.gastos ?? 0)"></div>
                        </div>
                    </template>
                </div>

                <div
                    x-show="movimientoCajaTipo === 'retiro' && movimientoCajaPaso === 1 && (resumenCaja?.retiro_recomendado ?? false)"
                    class="cash-note"
                >
                    <i class="ti tabler-alert-circle"></i>
                    <div>
                        La caja superó el umbral configurado. Se recomienda retirar al menos
                        <strong x-text="fmt(resumenCaja?.excedente_umbral ?? 0)"></strong>
                        para mantener el efectivo bajo control.
                    </div>
                </div>

                {{-- Paso 1 · desglose del retiro --}}
                <div x-show="movimientoCajaTipo === 'retiro' && movimientoCajaPaso === 1" class="cash-block count-sheet count-sheet--compact">
                    <div class="cash-block__head">
                        <div>
                            <div class="cash-block__title">
                                <i class="ti tabler-cash-banknote" style="color:var(--ls-success)"></i>
                                Desglose del retiro
                            </div>
                            <div class="cash-block__hint">Captura cuántas piezas saldrán físicamente de la caja.</div>
                        </div>
                        <button
                            type="button"
                            class="pos-btn pos-btn--ghost pos-btn--sm"
                            @click="Object.keys(movimientoCajaForm.denominaciones).forEach(k => movimientoCajaForm.denominaciones[k] = ''); sincronizarMontoRetiro()"
                        >
                            <i class="ti tabler-eraser" style="font-size:.85rem"></i>
                            Limpiar
                        </button>
                    </div>

                    <div class="count-sheet__columns">
                        <div class="count-sheet__col">
                            <div class="count-sheet__colhead">
                                <div class="count-sheet__coltitle">
                                    <i class="ti tabler-cash" style="color:var(--ls-text-muted)"></i>
                                    Billetes
                                </div>
                                <div class="count-sheet__colmeta count-sheet__colmeta--center">Piezas</div>
                                <div class="count-sheet__colmeta count-sheet__colmeta--right">Importe</div>
                            </div>
                            <div class="count-sheet__rows">
                                <template x-for="denom in billetesCorte" :key="`retiro-billete-${denom}`">
                                    <div class="count-row" :class="{ 'is-filled': subtotalDenominacionRetiro(denom) > 0 }">
                                        <div class="count-row__denom" x-text="fmt(denom)"></div>
                                        <input
                                            type="number"
                                            min="0"
                                            step="1"
                                            inputmode="numeric"
                                            class="count-row__input cash-withdrawal-row__input"
                                            x-model="movimientoCajaForm.denominaciones[denom]"
                                            @input="sincronizarMontoRetiro()"
                                            @keydown.enter.prevent="focusSiguienteRetiroInput($event)"
                                            @focus="$event.target.select()"
                                            placeholder="0"
                                            :aria-label="`Cantidad de billetes de ${fmt(denom)} para retirar`"
                                        />
                                        <div class="count-row__amount" x-text="fmt(subtotalDenominacionRetiro(denom))"></div>
                                    </div>
                                </template>
                            </div>
                            <div class="count-sheet__colfoot">
                                <span class="count-sheet__colfoot-label">Subtotal billetes</span>
                                <span
                                    class="count-sheet__colfoot-value"
                                    x-text="fmt(billetesCorte.reduce((suma, d) => suma + subtotalDenominacionRetiro(d), 0))"
                                ></span>
                            </div>
                        </div>

                        <div class="count-sheet__col count-sheet__col--divider">
                            <div class="count-sheet__colhead">
                                <div class="count-sheet__coltitle">
                                    <i class="ti tabler-coins" style="color:var(--ls-text-muted)"></i>
                                    Monedas
                                </div>
                                <div class="count-sheet__colmeta count-sheet__colmeta--center">Piezas</div>
                                <div class="count-sheet__colmeta count-sheet__colmeta--right">Importe</div>
                            </div>
                            <div class="count-sheet__rows">
                                <template x-for="moneda in monedasCorte" :key="`retiro-moneda-${moneda.clave}`">
                                    <div class="count-row" :class="{ 'is-filled': subtotalDenominacionRetiro(moneda.valor, moneda.clave) > 0 }">
                                        <div class="count-row__denom" x-text="moneda.etiqueta"></div>
                                        <input
                                            type="number"
                                            min="0"
                                            step="1"
                                            inputmode="numeric"
                                            class="count-row__input cash-withdrawal-row__input"
                                            x-model="movimientoCajaForm.denominaciones[moneda.clave]"
                                            @input="sincronizarMontoRetiro()"
                                            @keydown.enter.prevent="focusSiguienteRetiroInput($event)"
                                            @focus="$event.target.select()"
                                            placeholder="0"
                                            :aria-label="`Cantidad de monedas de ${moneda.etiqueta} para retirar`"
                                        />
                                        <div class="count-row__amount" x-text="fmt(subtotalDenominacionRetiro(moneda.valor, moneda.clave))"></div>
                                    </div>
                                </template>
                            </div>
                            <div class="count-sheet__colfoot">
                                <span class="count-sheet__colfoot-label">Subtotal monedas</span>
                                <span
                                    class="count-sheet__colfoot-value"
                                    x-text="fmt(monedasCorte.reduce((suma, m) => suma + subtotalDenominacionRetiro(m.valor, m.clave), 0))"
                                ></span>
                            </div>
                        </div>
                    </div>

                    <div class="cash-block__error" x-show="movimientoCajaErrores.denominaciones || movimientoCajaErrores.monto">
                        <div class="pos-field-error" x-show="movimientoCajaErrores.denominaciones" x-text="movimientoCajaErrores.denominaciones"></div>
                        <div class="pos-field-error" x-show="movimientoCajaErrores.monto" x-text="movimientoCajaErrores.monto"></div>
                    </div>
                </div>

                {{-- Paso 2 · resumen de lo que sale de la caja --}}
                <div x-show="movimientoCajaTipo === 'retiro' && movimientoCajaPaso === 2" class="cash-block">
                    <div class="cash-review">
                        <div>
                            <div class="cash-review__label">Efectivo que saldrá de la caja</div>
                            <div class="cash-review__amount" x-text="fmt(totalDenominacionesRetiro())"></div>
                            <div class="cash-review__meta">
                                <span x-text="piezasTotalesRetiro()"></span>
                                <span x-text="piezasTotalesRetiro() === 1 ? 'pieza contada' : 'piezas contadas'"></span>
                            </div>
                        </div>
                        <button type="button" class="pos-btn pos-btn--ghost pos-btn--sm" @click="movimientoCajaPaso = 1">
                            <i class="ti tabler-pencil" style="font-size:.85rem"></i>
                            Editar efectivo
                        </button>
                    </div>
                    <div class="cash-chips">
                        <template x-for="detalle in denominacionesConPiezasRetiro()" :key="`retiro-chip-${detalle.etiqueta}`">
                            <span class="cash-chip">
                                <strong x-text="`${detalle.piezas} ×`"></strong>
                                <span x-text="detalle.etiqueta"></span>
                                <span style="color:var(--ls-text-muted)" x-text="`= ${fmt(detalle.importe)}`"></span>
                            </span>
                        </template>
                    </div>
                    <div class="cash-impact">
                        <span>Efectivo en caja después del retiro</span>
                        <span class="cash-impact__value" x-text="fmt(efectivoRestanteRetiro())"></span>
                    </div>
                </div>

                {{-- Datos del movimiento --}}
                <div x-show="movimientoCajaTipo !== 'retiro' || movimientoCajaPaso === 2" class="cash-block">
                    <div class="cash-block__head">
                        <div>
                            <div class="cash-block__title">
                                <i class="ti tabler-file-description" style="color:var(--ls-text-secondary)"></i>
                                <span x-text="movimientoCajaTipo === 'retiro' ? 'Detalles del retiro' : 'Datos del gasto'"></span>
                            </div>
                            <div
                                class="cash-block__hint"
                                x-text="movimientoCajaTipo === 'retiro' ? 'Información que acompañará al movimiento en la bitácora.' : 'Captura el importe y el concepto del gasto.'"
                            ></div>
                        </div>
                    </div>
                    <div class="cash-block__body">
                        <div class="cash-fields">
                            <div x-show="movimientoCajaTipo === 'gasto'">
                                <label class="pos-field__label">Monto</label>
                                <input type="number" min="0" step="0.01" class="pos-input" x-model="movimientoCajaForm.monto" placeholder="0.00" />
                                <div class="pos-field-error" x-show="movimientoCajaErrores.monto" x-text="movimientoCajaErrores.monto"></div>
                            </div>
                            <div x-show="movimientoCajaTipo === 'gasto'">
                                <label class="pos-field__label">Categoría o concepto</label>
                                <div style="position:relative;">
                                    <input
                                        type="text"
                                        maxlength="120"
                                        class="pos-input"
                                        x-model="movimientoCajaForm.categoria"
                                        placeholder="Ej. Papelería, limpieza, insumo operativo"
                                        @focus="mostrarSugerenciasCategoriaGasto = String(movimientoCajaForm.categoria || '').trim().length > 0 && categoriasGastoFiltradas.length > 0"
                                        @input="mostrarSugerenciasCategoriaGasto = String(movimientoCajaForm.categoria || '').trim().length > 0 && categoriasGastoFiltradas.length > 0"
                                        @keydown.escape.stop="mostrarSugerenciasCategoriaGasto = false"
                                        @click.outside="mostrarSugerenciasCategoriaGasto = false"
                                    />
                                    <div
                                        x-cloak
                                        x-show="mostrarSugerenciasCategoriaGasto && categoriasGastoFiltradas.length > 0"
                                        class="pos-search-suggest pos-search-suggest--compact"
                                    >
                                        <template x-for="categoria in categoriasGastoFiltradas" :key="`categoria-gasto-${categoria}`">
                                            <button
                                                type="button"
                                                class="pos-search-suggest__item"
                                                @mousedown.prevent="seleccionarCategoriaGasto(categoria)"
                                            >
                                                <span class="pos-search-suggest__name" x-text="categoria"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                                <div class="pos-field-error" x-show="movimientoCajaErrores.categoria" x-text="movimientoCajaErrores.categoria"></div>
                            </div>

                            <div class="cash-field--full" x-show="movimientoCajaTipo === 'retiro'">
                                <label class="pos-field__label">Referencia opcional</label>
                                <input x-ref="retiroReferencia" type="text" maxlength="180" class="pos-input" x-model="movimientoCajaForm.referencia" placeholder="Ej. Resguardo nocturno, bóveda, traslado" />
                                <div class="pos-field-error" x-show="movimientoCajaErrores.referencia" x-text="movimientoCajaErrores.referencia"></div>
                            </div>

                            <div class="cash-field--full">
                                <label class="pos-field__label" x-text="movimientoCajaTipo === 'retiro' ? 'Motivo opcional' : 'Descripción opcional'"></label>
                                <textarea
                                    class="pos-notes-textarea"
                                    x-model="movimientoCajaForm.motivo"
                                    :placeholder="movimientoCajaTipo === 'retiro' ? 'Si lo deseas, agrega una nota sobre el retiro' : 'Si lo deseas, agrega una descripción del gasto'"
                                ></textarea>
                                <div class="pos-field-error" x-show="movimientoCajaErrores.motivo" x-text="movimientoCajaErrores.motivo"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Autorización del retiro --}}
                <div x-show="movimientoCajaTipo === 'retiro' && movimientoCajaPaso === 2" class="cash-block">
                    <div class="cash-block__head">
                        <div>
                            <div class="cash-block__title">
                                <i class="ti tabler-lock" style="color:var(--ls-text-secondary)"></i>
                                Autorización del retiro
                            </div>
                            <div class="cash-block__hint">Queda registrado en la bitácora junto con tu usuario.</div>
                        </div>
                    </div>
                    <div class="cash-block__body">
                        <div class="cash-fields">
                            <div>
                                <label class="pos-field__label">Usuario autorizado</label>
                                <select class="pos-input" x-model="movimientoCajaForm.autoriza_usr_id">
                                    <option value="">Selecciona un usuario...</option>
                                    <template x-for="usuario in usuariosAutorizadosRetiro" :key="usuario.usr_id">
                                        <option :value="String(usuario.usr_id)" x-text="usuario.usr_nombre"></option>
                                    </template>
                                </select>
                                <div class="pos-field-error" x-show="movimientoCajaErrores.autoriza_usr_id" x-text="movimientoCajaErrores.autoriza_usr_id"></div>
                            </div>
                            <div>
                                <label class="pos-field__label">Contraseña</label>
                                <input
                                    type="password"
                                    class="pos-input"
                                    x-model="movimientoCajaForm.autoriza_password"
                                    placeholder="Contraseña del usuario autorizado"
                                    autocomplete="new-password"
                                    @keydown.enter.prevent="guardarMovimientoCaja()"
                                />
                                <div class="pos-field-error" x-show="movimientoCajaErrores.autoriza_password" x-text="movimientoCajaErrores.autoriza_password"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Quién registra el gasto --}}
                <div x-show="movimientoCajaTipo === 'gasto'" class="cash-note" style="border-color:var(--ls-border);background:var(--ls-surface-2);color:var(--ls-text-secondary);">
                    <i class="ti tabler-user-check" style="color:var(--ls-text-muted)"></i>
                    <div>
                        El gasto quedará ligado a
                        <strong>{{ auth()->user()->usr_nombre ?? auth()->user()->usr_usuario ?? 'tu usuario' }}</strong>
                        y a la sesión de caja activa.
                    </div>
                </div>
            </div>

            {{-- Barra de acciones --}}
            <div class="cash-modal__foot">
                <div class="cash-modal__foot-read">
                    <div class="cash-modal__foot-label" x-text="movimientoCajaTipo === 'retiro' ? 'Monto del retiro' : 'Monto del gasto'"></div>
                    <div
                        class="cash-modal__foot-value"
                        x-text="movimientoCajaTipo === 'retiro' ? fmt(totalDenominacionesRetiro()) : fmt(Number(movimientoCajaForm.monto || 0))"
                    ></div>
                    <div class="cash-modal__foot-meta" x-show="movimientoCajaTipo === 'retiro'">
                        <span x-text="piezasTotalesRetiro()"></span>
                        <span x-text="piezasTotalesRetiro() === 1 ? 'pieza' : 'piezas'"></span>
                        ·
                        <span>Quedan</span>
                        <span x-text="fmt(efectivoRestanteRetiro())"></span>
                        <span>en caja</span>
                    </div>
                </div>
                <div class="cash-modal__foot-actions">
                    <button
                        type="button"
                        class="pos-btn pos-btn--ghost"
                        @click="movimientoCajaTipo === 'retiro' && movimientoCajaPaso === 2 ? movimientoCajaPaso = 1 : cerrarModalMovimientoCaja()"
                        x-text="movimientoCajaTipo === 'retiro' && movimientoCajaPaso === 2 ? 'Atrás' : 'Cancelar'"
                    ></button>
                    <button
                        x-show="movimientoCajaTipo === 'retiro' && movimientoCajaPaso === 1"
                        type="button"
                        class="pos-btn pos-btn--cobrar"
                        @click="avanzarRetiroAConfirmacion()"
                    >
                        Continuar
                        <i class="ti tabler-arrow-right"></i>
                    </button>
                    <button
                        x-show="movimientoCajaTipo !== 'retiro' || movimientoCajaPaso === 2"
                        type="button"
                        class="pos-btn pos-btn--cobrar"
                        :disabled="guardandoMovimientoCaja"
                        @click="guardarMovimientoCaja()"
                        x-text="guardandoMovimientoCaja ? 'Guardando...' : (movimientoCajaTipo === 'retiro' ? 'Registrar retiro' : 'Registrar gasto')"
                    ></button>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════
         CORTE DE CAJA
    ═══════════════════════════════════════════════════ --}}
    <div x-cloak x-show="mostrarCorteCaja" class="corte-caja" @keydown.escape.window="mostrarCorteCaja && cerrarCorteCaja()">
        {{-- Barra de contexto --}}
        <div class="corte-caja__head">
            <div class="corte-caja__head-left">
                <div class="corte-caja__head-icon"><i class="ti tabler-clipboard-check"></i></div>
                <div>
                    <div class="corte-caja__title">Corte de caja</div>
                    <div class="corte-caja__sub">Cuenta el efectivo físico y confirma el cierre de la sesión.</div>
                </div>
            </div>
            <div class="corte-caja__head-right">
                <span class="corte-chip corte-chip--live">
                    <i class="ti tabler-cash-register"></i>
                    <span x-text="cajaNombre || 'Sin caja'"></span>
                </span>
                <span class="corte-chip">
                    <i class="ti tabler-user"></i>
                    <span x-text="usuarioActualNombre || sesionActiva?.usuario_apertura || '—'"></span>
                </span>
                <button class="pos-btn pos-btn--ghost pos-btn--sm" @click="mostrarModalResumenCaja = true">
                    <i class="ti tabler-list-details" style="font-size:.85rem"></i>
                    Ver movimientos
                </button>
                <button class="pos-btn pos-btn--ghost pos-btn--sm" @click="cerrarCorteCaja()">
                    <i class="ti tabler-x" style="font-size:.85rem"></i>
                    Cerrar
                </button>
            </div>
        </div>

        <div class="corte-caja__body">
            <div class="corte-caja__grid">

                {{-- ── Hoja de conteo ──────────────────────────── --}}
                <div class="corte-card">
                    <div class="corte-card__head">
                        <div class="corte-card__head-left">
                            <div class="corte-card__icon corte-card__icon--success"><i class="ti tabler-coin"></i></div>
                            <div>
                                <div class="corte-card__title">Conteo de efectivo</div>
                                <div class="corte-card__hint">
                                    Captura las piezas que hay en el cajón por cada denominación.
                                    <span class="corte-kbd">Enter</span> avanza al siguiente campo.
                                </div>
                            </div>
                        </div>
                        <div class="corte-card__actions">
                            <div class="corte-total">
                                <span class="corte-total__label">Total contado</span>
                                <span class="corte-total__value" x-text="fmt(corteCajeroReporta())"></span>
                            </div>
                            <button
                                type="button"
                                class="pos-btn pos-btn--ghost pos-btn--sm"
                                @click="Object.keys(corteCajaForm.denominaciones).forEach(k => corteCajaForm.denominaciones[k] = '')"
                            >
                                <i class="ti tabler-eraser" style="font-size:.85rem"></i>
                                Limpiar
                            </button>
                        </div>
                    </div>

                    <div class="count-sheet__columns">
                        {{-- Billetes --}}
                        <div class="count-sheet__col">
                            <div class="count-sheet__colhead">
                                <div class="count-sheet__coltitle">
                                    <i class="ti tabler-cash" style="color:var(--ls-text-muted)"></i>
                                    Billetes
                                </div>
                                <div class="count-sheet__colmeta count-sheet__colmeta--center">Piezas</div>
                                <div class="count-sheet__colmeta count-sheet__colmeta--right">Importe</div>
                            </div>
                            <div class="count-sheet__rows">
                                <template x-for="denom in billetesCorte" :key="`billete-${denom}`">
                                    <div class="count-row" :class="{ 'is-filled': corteSubtotal(denom) > 0 }">
                                        <div class="count-row__denom" x-text="fmt(denom)"></div>
                                        <input
                                            type="number"
                                            min="0"
                                            step="1"
                                            inputmode="numeric"
                                            class="count-row__input corte-caja__input"
                                            x-model="corteCajaForm.denominaciones[denom]"
                                            @keydown.enter.prevent="focusSiguienteCorteInput($event)"
                                            @focus="$event.target.select()"
                                            placeholder="0"
                                            :aria-label="`Cantidad de billetes de ${fmt(denom)}`"
                                        />
                                        <div class="count-row__amount" x-text="fmt(corteSubtotal(denom))"></div>
                                    </div>
                                </template>
                            </div>
                            <div class="count-sheet__colfoot">
                                <span class="count-sheet__colfoot-label">Subtotal billetes</span>
                                <span
                                    class="count-sheet__colfoot-value"
                                    x-text="fmt(billetesCorte.reduce((suma, d) => suma + corteSubtotal(d), 0))"
                                ></span>
                            </div>
                        </div>

                        {{-- Monedas --}}
                        <div class="count-sheet__col count-sheet__col--divider">
                            <div class="count-sheet__colhead">
                                <div class="count-sheet__coltitle">
                                    <i class="ti tabler-coins" style="color:var(--ls-text-muted)"></i>
                                    Monedas
                                </div>
                                <div class="count-sheet__colmeta count-sheet__colmeta--center">Piezas</div>
                                <div class="count-sheet__colmeta count-sheet__colmeta--right">Importe</div>
                            </div>
                            <div class="count-sheet__rows">
                                <template x-for="moneda in monedasCorte" :key="`moneda-${moneda.clave}`">
                                    <div class="count-row" :class="{ 'is-filled': corteSubtotal(moneda.valor, moneda.clave) > 0 }">
                                        <div class="count-row__denom" x-text="moneda.etiqueta"></div>
                                        <input
                                            type="number"
                                            min="0"
                                            step="1"
                                            inputmode="numeric"
                                            class="count-row__input corte-caja__input"
                                            x-model="corteCajaForm.denominaciones[moneda.clave]"
                                            @keydown.enter.prevent="focusSiguienteCorteInput($event)"
                                            @focus="$event.target.select()"
                                            placeholder="0"
                                            :aria-label="`Cantidad de monedas de ${moneda.etiqueta}`"
                                        />
                                        <div class="count-row__amount" x-text="fmt(corteSubtotal(moneda.valor, moneda.clave))"></div>
                                    </div>
                                </template>
                            </div>
                            <div class="count-sheet__colfoot">
                                <span class="count-sheet__colfoot-label">Subtotal monedas</span>
                                <span
                                    class="count-sheet__colfoot-value"
                                    x-text="fmt(monedasCorte.reduce((suma, m) => suma + corteSubtotal(m.valor, m.clave), 0))"
                                ></span>
                            </div>
                        </div>
                    </div>

                    <div class="corte-notas">
                        <label class="corte-notas__label" for="corte-observaciones">Observaciones del corte <span style="font-weight:500;color:var(--ls-text-muted)">(opcional)</span></label>
                        <textarea
                            id="corte-observaciones"
                            class="corte-notas__input"
                            x-model="corteCajaForm.observaciones"
                            placeholder="Anota cualquier incidencia relevante para la auditoría de esta sesión"
                        ></textarea>
                    </div>
                </div>

                {{-- ── Columna de control ──────────────────────── --}}
                <aside class="corte-side">

                    {{-- Sesión --}}
                    <div class="corte-card">
                        <div class="corte-card__head">
                            <div class="corte-card__head-left">
                                <div class="corte-card__icon"><i class="ti tabler-clock-hour-4"></i></div>
                                <div class="corte-card__title">Sesión en curso</div>
                            </div>
                        </div>
                        <div class="corte-list">
                            <div class="corte-list__row">
                                <span class="corte-list__label">Caja</span>
                                <span class="corte-list__value" x-text="cajaNombre || '—'"></span>
                            </div>
                            <div class="corte-list__row">
                                <span class="corte-list__label">Cajero</span>
                                <span class="corte-list__value" x-text="usuarioActualNombre || sesionActiva?.usuario_apertura || '—'"></span>
                            </div>
                            <div class="corte-list__row">
                                <span class="corte-list__label">Apertura</span>
                                <span class="corte-list__value" x-text="horaCorta(sesionActiva?.abierta_at)"></span>
                            </div>
                            <div class="corte-list__row">
                                <span class="corte-list__label">Duración</span>
                                <span class="corte-list__value" x-text="tiempoSesionTexto()"></span>
                            </div>
                            <div class="corte-list__row">
                                <span class="corte-list__label">Tickets cobrados</span>
                                <span class="corte-list__value" x-text="resumenCaja?.ventas_del_dia ?? 0"></span>
                            </div>
                            <div class="corte-list__row">
                                <span class="corte-list__label">Total vendido</span>
                                <span class="corte-list__value" x-text="fmt(resumenCaja?.total_vendido ?? 0)"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Arqueo --}}
                    <div class="corte-card">
                        <div class="corte-card__head">
                            <div class="corte-card__head-left">
                                <div class="corte-card__icon corte-card__icon--accent"><i class="ti tabler-report-money"></i></div>
                                <div>
                                    <div class="corte-card__title">Arqueo del sistema</div>
                                    <div class="corte-card__hint">Efectivo que debería estar en el cajón.</div>
                                </div>
                            </div>
                        </div>
                        <div class="corte-list">
                            <div class="corte-list__row">
                                <span class="corte-list__label">Fondo de apertura</span>
                                <span class="corte-list__value" x-text="fmt(resumenCaja?.inicio_caja ?? 0)"></span>
                            </div>
                            <div class="corte-list__row">
                                <span class="corte-list__label">Efectivo de ventas</span>
                                <span class="corte-list__value" x-text="fmt(resumenCaja?.efectivo_ventas_neto ?? 0)"></span>
                            </div>
                            <div class="corte-list__row">
                                <span class="corte-list__label">Retiros de caja</span>
                                <span
                                    class="corte-list__value"
                                    :class="{ 'corte-list__value--neg': (resumenCaja?.retiros ?? 0) > 0 }"
                                    x-text="(resumenCaja?.retiros ?? 0) > 0 ? `− ${fmt(resumenCaja.retiros)}` : fmt(0)"
                                ></span>
                            </div>
                            <div class="corte-list__row">
                                <span class="corte-list__label">Gastos de caja</span>
                                <span
                                    class="corte-list__value"
                                    :class="{ 'corte-list__value--neg': (resumenCaja?.gastos ?? 0) > 0 }"
                                    x-text="(resumenCaja?.gastos ?? 0) > 0 ? `− ${fmt(resumenCaja.gastos)}` : fmt(0)"
                                ></span>
                            </div>
                            <div class="corte-list__row corte-list__row--total">
                                <span class="corte-list__label">Efectivo esperado</span>
                                <span class="corte-list__value" x-text="fmt(corteEfectivoEsperado())"></span>
                            </div>

                            <div class="corte-list__group">
                                <div class="corte-list__group-label">Cobros que no afectan el efectivo</div>
                                <div class="corte-list__row">
                                    <span class="corte-list__label">Tarjeta</span>
                                    <span class="corte-list__value corte-list__value--muted" x-text="fmt(metodoMonto('tarjeta'))"></span>
                                </div>
                                <div class="corte-list__row" x-show="metodoMonto('mixto') > 0">
                                    <span class="corte-list__label">Mixto</span>
                                    <span class="corte-list__value corte-list__value--muted" x-text="fmt(metodoMonto('mixto'))"></span>
                                </div>
                                <div class="corte-list__row" x-show="metodoMonto('monedero_electronico') > 0">
                                    <span class="corte-list__label">Monedero electrónico</span>
                                    <span class="corte-list__value corte-list__value--muted" x-text="fmt(metodoMonto('monedero_electronico'))"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                </aside>
            </div>
        </div>

        {{-- Barra de acciones --}}
        <div class="corte-caja__footer">
            <div class="corte-caja__footer-inner">
                <div class="corte-foot-read">
                    <div class="corte-foot-read__item">
                        <div class="corte-foot-read__label">Efectivo esperado</div>
                        <div class="corte-foot-read__value" x-text="fmt(corteEfectivoEsperado())"></div>
                    </div>
                    <div class="corte-foot-read__arrow"><i class="ti tabler-arrow-right"></i></div>
                    <div class="corte-foot-read__item">
                        <div class="corte-foot-read__label">Cajero reporta</div>
                        <div class="corte-foot-read__value" x-text="fmt(corteCajeroReporta())"></div>
                    </div>
                    <div class="corte-foot-read__item--diff">
                        <div>
                            <div class="corte-foot-read__label">Diferencia</div>
                            <div
                                class="corte-foot-read__diff"
                                :class="{
                                    'corte-verdict__diff--ok': corteDiferenciaEstado() === 'ok',
                                    'corte-verdict__diff--sobra': corteDiferenciaEstado() === 'sobra',
                                    'corte-verdict__diff--falta': corteDiferenciaEstado() === 'falta',
                                }"
                                x-text="`${corteDiferenciaEstado() === 'falta' ? '−' : (corteDiferenciaEstado() === 'sobra' ? '+' : '')}${fmt(Math.abs(corteDiferencia()))}`"
                            ></div>
                        </div>
                        <span
                            class="corte-foot-tag"
                            :class="{
                                'corte-foot-tag--ok': corteDiferenciaEstado() === 'ok',
                                'corte-foot-tag--sobra': corteDiferenciaEstado() === 'sobra',
                                'corte-foot-tag--falta': corteDiferenciaEstado() === 'falta',
                            }"
                        >
                            <i class="ti" :class="{
                                'tabler-circle-check': corteDiferenciaEstado() === 'ok',
                                'tabler-trending-up': corteDiferenciaEstado() === 'sobra',
                                'tabler-alert-triangle': corteDiferenciaEstado() === 'falta',
                            }"></i>
                            <span x-text="corteDiferenciaEstado() === 'ok' ? 'Cuadra sin diferencias' : (corteDiferenciaEstado() === 'sobra' ? 'Sobrante en caja' : 'Faltante en caja')"></span>
                        </span>
                    </div>
                </div>
                <div class="corte-caja__actions">
                    <button type="button" class="pos-btn pos-btn--ghost" @click="cerrarCorteCaja()">
                        Cancelar
                    </button>
                    <button
                        type="button"
                        class="pos-btn pos-btn--cobrar corte-caja__submit corte-caja__input"
                        @click="abrirModalAutorizacionCorte()"
                    >
                        <i class="ti tabler-lock"></i>
                        Realizar corte
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de autorización del corte --}}
    <div x-cloak x-show="mostrarModalCorteAutorizacion" class="variant-modal" style="z-index:1260;" @keydown.escape.window="cerrarModalAutorizacionCorte()">
        <div class="variant-modal__card" style="max-width:460px;">
            <div class="variant-modal__head" style="display:flex;justify-content:space-between;align-items:center;gap:.75rem;">
                <span>Autorizar corte de caja</span>
                <button class="pos-btn pos-btn--ghost pos-btn--sm" @click="cerrarModalAutorizacionCorte()">Cerrar</button>
            </div>
            <div style="padding:1rem;display:grid;gap:.9rem;">
                <template x-if="corteAutorizaErrores.general">
                    <div style="padding:.7rem .85rem;border-radius:var(--ls-radius);background:var(--ls-danger-bg);border:1px solid var(--ls-danger-mid);">
                        <div style="font-size:.8rem;font-weight:700;color:var(--ls-danger);">No fue posible continuar</div>
                        <div style="font-size:.78rem;color:var(--ls-text-secondary);margin-top:.15rem;" x-text="corteAutorizaErrores.general"></div>
                    </div>
                </template>

                <div class="cash-summary__item" style="background:#f8fafc;">
                    <div class="cash-summary__label">Diferencia a confirmar</div>
                    <div
                        class="cash-summary__value"
                        :class="{
                            'corte-verdict__diff--ok': corteDiferenciaEstado() === 'ok',
                            'corte-verdict__diff--sobra': corteDiferenciaEstado() === 'sobra',
                            'corte-verdict__diff--falta': corteDiferenciaEstado() === 'falta',
                        }"
                        x-text="`${corteDiferenciaEstado() === 'falta' ? '-' : (corteDiferenciaEstado() === 'sobra' ? '+' : '')}${fmt(Math.abs(corteDiferencia()))}`"
                    ></div>
                </div>

                <div>
                    <label class="pos-field__label">Usuario autorizado</label>
                    <select
                        class="pos-input corte-caja__input"
                        x-model="corteAutorizaForm.usr_id"
                        @keydown.enter.prevent="focusSiguienteCorteInput($event)"
                    >
                        <option value="">Selecciona un usuario...</option>
                        <template x-for="usuario in usuariosAutorizadosCorte" :key="`corte-user-${usuario.usr_id}`">
                            <option :value="String(usuario.usr_id)" x-text="`${usuario.usr_nombre} · ${usuario.usr_usuario}`"></option>
                        </template>
                    </select>
                    <div class="pos-field-error" x-show="corteAutorizaErrores.autoriza_usr_id" x-text="corteAutorizaErrores.autoriza_usr_id"></div>
                </div>

                <div>
                    <label class="pos-field__label">Contraseña</label>
                    <input
                        type="password"
                        class="pos-input corte-caja__input"
                        x-model="corteAutorizaForm.password"
                        placeholder="Captura la contraseña del usuario autorizado"
                        autocomplete="new-password"
                        @keydown.enter.prevent="confirmarCorteCaja()"
                    />
                    <div class="pos-field-error" x-show="corteAutorizaErrores.autoriza_password" x-text="corteAutorizaErrores.autoriza_password"></div>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:.6rem;">
                    <button type="button" class="pos-btn pos-btn--ghost" @click="cerrarModalAutorizacionCorte()">Cancelar</button>
                    <button
                        type="button"
                        class="pos-btn pos-btn--cobrar"
                        :disabled="guardandoCorte"
                        @click="confirmarCorteCaja()"
                        x-text="guardandoCorte ? 'Confirmando...' : 'Confirmar'"
                    ></button>
                </div>
            </div>
        </div>
    </div>

    <div x-cloak x-show="mostrarModalPago" class="variant-modal">
        <div class="pay-modal__card">

            {{-- ── HEADER ──────────────────────────────────────────── --}}
            <div class="pay-modal__head">
                <div class="pay-modal__head-left">
                    <div class="pay-modal__head-icon"><i class="ti tabler-cash-register"></i></div>
                    <div>
                        <h3 class="pay-modal__title">Cobrar venta</h3>
                        <p class="pay-modal__subtitle">Selecciona el método de pago</p>
                    </div>
                </div>
                <div class="pay-modal__head-total">
                    <span class="pay-modal__head-total-label">Total a cobrar</span>
                    <span class="pay-modal__head-total-amount" x-text="fmt(total)"></span>
                </div>
                <button class="pay-modal__close" @click="cerrarModalPago()">
                    <i class="ti tabler-x"></i>
                </button>
            </div>

            {{-- ── BODY ─────────────────────────────────────────────── --}}
            <div class="pay-modal__body">

                {{-- ── LEFT: main content ─────────────────────────── --}}
                <div class="pay-main">

                    <div
                        x-cloak
                        style="margin-bottom:.85rem;"
                    >
                        <div class="pay-ref-row pay-ref-row--vale" x-cloak x-show="mostrarCapturaVale || creditoCambioSeleccionado" style="margin-bottom:.5rem;">
                            <label>Vale de cambio:</label>
                            <input type="text" x-ref="valeInput" style="text-align:center;" x-model="folioCreditoCambio" @input="manejarEntradaCreditoCambio($event.target.value)" @keydown.enter.prevent="buscarCreditoCambioParaCobro()" placeholder="Escanea o captura el folio" :class="{ 'is-loading': buscandoCreditoCambio }">
                        </div>
                        <div x-show="mensajeCreditoCambio" style="font-size:.8rem;color:#b42318;margin-top:-.15rem;" x-text="mensajeCreditoCambio"></div>
                        <div
                            x-cloak
                            x-show="creditoCambioSeleccionado"
                            style="border:1px solid var(--ls-border);border-radius:14px;padding:.8rem .9rem;background:#f8fafc;margin-top:.55rem;"
                        >
                            <div style="display:flex;justify-content:space-between;gap:.75rem;align-items:flex-start;">
                                <div>
                                    <div style="font-size:.82rem;font-weight:700;" x-text="creditoCambioSeleccionado?.folio || ''"></div>
                                    <div style="font-size:.76rem;color:var(--ls-text-muted);">
                                        Venta origen <strong x-text="creditoCambioSeleccionado?.venta_origen_folio || 'N/D'"></strong>
                                    </div>
                                    <div style="font-size:.76rem;color:var(--ls-text-muted);" x-text="creditoCambioSeleccionado?.cliente_nombre || 'Público general'"></div>
                                </div>
                                <button class="pos-btn pos-btn--ghost pos-btn--sm" @click="quitarCreditoCambioSeleccionado()">Quitar</button>
                            </div>
                            <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.55rem;margin-top:.7rem;">
                                <div style="padding:.65rem .75rem;border:1px dashed var(--ls-border);border-radius:12px;">
                                    <div style="font-size:.72rem;color:var(--ls-text-muted);">Saldo disponible</div>
                                    <strong x-text="fmt(creditoCambioSeleccionado?.saldo_disponible || 0)"></strong>
                                </div>
                                <div style="padding:.65rem .75rem;border:1px dashed var(--ls-border);border-radius:12px;">
                                    <div style="font-size:.72rem;color:var(--ls-text-muted);">Se aplicará</div>
                                    <strong x-text="fmt(creditoCambioSeleccionado?.monto_aplicado || 0)"></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        x-cloak
                        x-show="hayCreditoCambioAplicado"
                        class="pay-change-summary"
                    >
                        <div class="pay-change-summary__title">
                            Resumen
                        </div>
                        <div class="pay-change-summary__rows">
                            <div class="pay-change-summary__row">
                                <span>Selección</span>
                                <strong x-text="fmt(subtotal - descuento)"></strong>
                            </div>
                            <div class="pay-change-summary__row">
                                <span>Saldo a favor</span>
                                <strong x-text="'-' + fmt(creditoCambio)"></strong>
                            </div>
                        </div>
                        <div class="pay-change-summary__divider"></div>
                        <div class="pay-change-summary__total">
                            <span>Importe final</span>
                            <strong x-text="fmt(total)"></strong>
                        </div>
                        <div class="pay-change-summary__copy">
                            Se aplicó tu saldo disponible a esta selección.
                        </div>
                    </div>

                    {{-- Payment methods --}}
                    <div class="pay-methods">
                        <button class="pay-method" :class="{ 'is-active': tipoPagoSeleccionado === 'efectivo' }" @click="seleccionarTipoPago('efectivo')">
                            <i class="ti tabler-cash"></i><span>Efectivo</span>
                        </button>
                        <button class="pay-method" :class="{ 'is-active': tipoPagoSeleccionado === 'tarjeta_credito' }" @click="seleccionarTipoPago('tarjeta_credito')">
                            <i class="ti tabler-credit-card"></i><span>Tarjeta crédito</span>
                        </button>
                        <button class="pay-method" :class="{ 'is-active': tipoPagoSeleccionado === 'tarjeta_debito' }" @click="seleccionarTipoPago('tarjeta_debito')">
                            <i class="ti tabler-credit-card"></i><span>Tarjeta débito</span>
                        </button>
                        <button class="pay-method" :class="{ 'is-active': tipoPagoSeleccionado === 'transferencia' }" @click="seleccionarTipoPago('transferencia')">
                            <i class="ti tabler-building-bank"></i><span>Transferencia</span>
                        </button>
                        <button class="pay-method" :class="{ 'is-active': tipoPagoSeleccionado === 'mixto' }" @click="seleccionarTipoPago('mixto')">
                            <i class="ti tabler-wallet"></i><span>Mixto</span>
                        </button>
                    </div>

                    {{-- Reference (non-cash) --}}
                    <div class="pay-ref-row" x-show="tipoPagoSeleccionado !== 'efectivo'">
                        <label>Referencia:</label>
                        <input type="text" x-model.trim="pagoReferencia" placeholder="Folio / autorización">
                    </div>

                    {{-- Cash section --}}
                    <div class="pay-cash" x-show="tipoPagoSeleccionado === 'efectivo'">
                        <div class="pay-cash__row">
                            <label>Pagó con:</label>
                            <input type="number" step="0.01" min="0" x-ref="pagoConInput" x-model.number="pagoEfectivoRecibido" @input="aplicarPagoEfectivo()">
                            <button type="button" class="pay-cash__ok" @click="aplicarPagoEfectivo()">
                                <i class="ti tabler-check"></i>
                            </button>
                        </div>
                        <div class="pay-cash__change">
                            <span class="pay-cash__change-label">Su cambio:</span>
                            <strong x-text="fmt(cambioPago)"></strong>
                        </div>
                        <div class="pay-cash__quick">
                            <template x-for="sugerencia in sugerenciasPagoEfectivo" :key="`cash-suggestion-${sugerencia}`">
                                <button type="button" @click="seleccionarSugerenciaPago(sugerencia)" x-text="fmt(sugerencia)"></button>
                            </template>
                        </div>
                    </div>

                    {{-- Mixed payments --}}
                    <div class="pay-lines" x-show="tipoPagoSeleccionado === 'mixto'">
                        <div class="pay-lines__head">
                            <span>Método</span>
                            <span>Monto</span>
                            <span>Recibido</span>
                            <span></span>
                        </div>
                        <template x-for="(linea, idx) in pagoLineas" :key="idx">
                            <div class="pay-line">
                                <select x-model="linea.metodo">
                                    <option value="efectivo">Efectivo</option>
                                    <option value="tarjeta_credito">Tarjeta crédito</option>
                                    <option value="tarjeta_debito">Tarjeta débito</option>
                                    <option value="transferencia">Transferencia</option>
                                </select>
                                <input type="number" step="0.01" min="0" x-model.number="linea.monto">
                                <input type="number" step="0.01" min="0" x-model.number="linea.recibido">
                                <button class="pos-btn pos-btn--danger-outline" @click="quitarLineaPago(idx)">Quitar</button>
                            </div>
                        </template>
                        <div style="margin-top:.65rem;text-align:center;">
                            <button class="pos-btn pos-btn--success-outline" @click="agregarLineaPago()">+ Agregar método</button>
                        </div>
                    </div>
                </div>

                {{-- ── RIGHT: action panel ─────────────────────────── --}}
                <div class="pay-side">
                    <div class="pay-side-label">Acciones</div>

                    <button class="pay-side-card pay-side-card--vale" @click="mostrarCapturaVale = true; $nextTick(() => $refs.valeInput?.focus())">
                        <div class="pay-side-card__icon"><i class="ti tabler-ticket"></i></div>
                        <div>
                            <div class="pay-side-card__k">Vale</div>
                            <div class="pay-side-card__t">Registrar vale</div>
                        </div>
                    </button>

                    <button class="pay-side-card pay-side-card--f1" @click="confirmarCobro(true)">
                        <div class="pay-side-card__icon"><i class="ti tabler-printer"></i></div>
                        <div>
                            <div class="pay-side-card__k">F1</div>
                            <div class="pay-side-card__t">Cobrar e Imprimir</div>
                        </div>
                    </button>

                    <button class="pay-side-card pay-side-card--f2" @click="confirmarCobro(false)">
                        <div class="pay-side-card__icon"><i class="ti tabler-receipt"></i></div>
                        <div>
                            <div class="pay-side-card__k">F2</div>
                            <div class="pay-side-card__t">Solo Registrar</div>
                        </div>
                    </button>

                    <button class="pay-side-card pay-side-card--danger" @click="cerrarModalPago()">
                        <div class="pay-side-card__icon"><i class="ti tabler-x"></i></div>
                        <div>
                            <div class="pay-side-card__k">ESC</div>
                            <div class="pay-side-card__t">Cancelar Venta</div>
                        </div>
                    </button>

                    <button class="pay-side-card pay-side-card--muted" @click="$refs.notasInput?.focus()">
                        <div class="pay-side-card__icon"><i class="ti tabler-notes"></i></div>
                        <div>
                            <div class="pay-side-card__k">F4</div>
                            <div class="pay-side-card__t">Ingresar Notas</div>
                        </div>
                    </button>

                    {{-- Summary bar (Pagado / Restante) --}}
                    <div class="pay-summary">
                        <div class="pay-summary__item pay-summary__ok">
                            <span>Pagado</span>
                            <strong x-text="fmt(totalPagoCapturado)"></strong>
                        </div>
                        <div class="pay-summary__item" :class="restantePagoModal > 0 ? 'pay-summary__danger' : 'pay-summary__ok'">
                            <span>Restante</span>
                            <strong x-text="fmt(restantePagoModal)"></strong>
                        </div>
                    </div>

                    <div class="pay-side-footer">
                        <span class="pay-side-footer__label">Artículos</span>
                        <span class="pay-side-footer__count" x-text="totalArticulos"></span>
                    </div>
                </div>

            </div>{{-- /pay-modal__body --}}
        </div>
    </div>

    <div x-cloak x-show="mostrarModalClientes" class="variant-modal">
        @include('operacion.clientes.partials.modal_cliente', ['embedded' => true])
    </div>

    <div x-cloak x-show="mostrarModalAlmacenVenta" class="variant-modal">
        <div class="variant-modal__card" style="max-width:520px;">
            <div class="variant-modal__head">
                <span x-text="modalAlmacenVentaTitulo"></span>
            </div>
            <div style="padding:1rem;">
                <div style="font-size:.82rem;color:var(--ls-text-muted);margin-bottom:.55rem;" x-text="modalAlmacenVentaMensaje">
                </div>
                <div class="almacen-radio-grid">
                    <template x-for="alm in almacenesModalVenta" :key="alm.alm_id">
                        <label
                            class="almacen-radio"
                            :class="{ 'almacen-radio--active': Number(ventaAlmacenId) === Number(alm.alm_id) }"
                        >
                            <input
                                type="radio"
                                name="almacen_ticket"
                                :value="String(alm.alm_id)"
                                x-model="ventaAlmacenId"
                                @change="seleccionarAlmacenYContinuar(alm)"
                            >
                            <span>
                                <span class="almacen-radio__txt" x-text="alm.alm_nombre"></span>
                                <span class="almacen-radio__sub">Usar para esta venta</span>
                            </span>
                        </label>
                    </template>
                </div>
            </div>
            <div style="padding:.7rem .9rem;display:flex;justify-content:flex-end;gap:.5rem;">
                <button class="pos-btn pos-btn--ghost" @click="cerrarModalAlmacenVenta()">Cancelar</button>
            </div>
        </div>
    </div>

    <div x-cloak x-show="mostrarSelectorVariantes" class="variant-modal">
        <div class="variant-modal__card">
            <div class="variant-modal__head">
                Selecciona la variante a agregar
            </div>
            <div class="variant-modal__list">
                <template x-for="item in variantesPendientes" :key="item.psk_id">
                    <button type="button" class="variant-modal__row" @click="agregarDesdeBusqueda(item)">
                        <span class="variant-modal__name" x-text="item.psk_nombre || item.producto?.prd_nombre"></span>
                        <span class="variant-modal__meta" x-text="`${item.psk_codigo} · ${item.psk_codigo_barras || 'Sin barras'} · $${Number(item.psk_precio || 0).toFixed(2)}`"></span>
                    </button>
                </template>
            </div>
            <div style="padding:.7rem .9rem;display:flex;justify-content:flex-end;">
                <button class="pos-btn pos-btn--ghost" @click="cerrarSelectorVariantes()">Cancelar</button>
            </div>
        </div>
    </div>

    <div x-cloak x-show="mostrarModalDescuentoItem" class="variant-modal" @keydown.escape.window="cerrarDescuentoItem()">
        <div class="disc-modal__card">
            <div class="disc-modal__head">
                <h3 class="disc-modal__title">Descuento</h3>
                <button type="button" class="disc-modal__close" @click="cerrarDescuentoItem()" title="Cerrar">
                    <i class="ti tabler-x"></i>
                </button>
            </div>
                <div class="disc-modal__body">
                    <div class="disc-modal__product">
                        <div class="disc-modal__name" x-text="descuentoItemNombre"></div>
                        <div class="disc-modal__copy">Define si el descuento es por porcentaje o monto fijo, y para cuánta cantidad aplica.</div>
                    </div>

                    <div class="disc-modal__field">
                        <label class="disc-modal__label">Tipo</label>
                        <select class="disc-modal__input" x-model="descuentoItemTipo">
                            <option value="porcentaje">Porcentaje</option>
                            <option value="importe">Monto fijo</option>
                        </select>
                    </div>

                    <div class="disc-modal__field">
                        <label class="disc-modal__label" x-text="descuentoItemTipo === 'importe' ? 'Monto fijo' : 'Porcentaje'"></label>
                        <input
                            x-ref="descuentoItemInput"
                            type="number"
                            class="disc-modal__input"
                            min="0"
                            :max="descuentoItemTipo === 'porcentaje' ? 100 : null"
                            step="0.01"
                            x-model="descuentoItemValor"
                            @keydown.enter.prevent="guardarDescuentoItemPos()"
                        >
                    </div>

                    <div class="disc-modal__field">
                        <label class="disc-modal__label">Cantidad a descontar</label>
                        <input
                            type="number"
                            class="disc-modal__input"
                            min="0"
                            :step="descuentoItemPermiteDecimal ? 0.01 : 1"
                            x-model="descuentoItemCantidad"
                        >
                    </div>

                    <div class="disc-modal__actions">
                    <button type="button" class="btn btn-outline-secondary disc-modal__btn" @click="limpiarDescuentoItem()">
                        <i class="ti tabler-eraser"></i>
                        Quitar
                    </button>
                    <button type="button" class="btn btn-primary disc-modal__btn" @click="guardarDescuentoItemPos()">
                        <i class="ti tabler-device-floppy"></i>
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div x-cloak x-show="mostrarModalCaja" class="caja-gate">
        <div class="caja-gate__card">

            {{-- Header --}}
            <div class="caja-gate__head">
                <div class="caja-gate__head-icon">
                    <i class="ti tabler-building-store"></i>
                </div>
                <div>
                    <h3 class="caja-gate__title">Activa tu caja para empezar</h3>
                    <p class="caja-gate__sub">Únete a una sesión ya abierta por otro cajero, o abre una nueva en tu caja asignada.</p>
                </div>
            </div>

            {{-- Body --}}
            <div class="caja-gate__body">
                <div class="caja-gate__cols">

                    {{-- Opción A: Unirse a sesión activa --}}
                    <div class="caja-choice caja-choice--accent" :class="enfoqueUnirme ? 'caja-choice--priority' : ''">
                        <div class="caja-choice__icon caja-choice__icon--accent">
                            <i class="ti tabler-link"></i>
                        </div>
                        <p class="caja-choice__title">Unirme a sesión activa</p>
                        <p class="caja-choice__hint">Otra persona ya abrió la caja y tú te sumas para cobrar desde tu usuario.</p>

                        <template x-if="sesionesDisponibles.length === 0">
                            <div class="caja-choice__empty">
                                <i class="ti tabler-info-circle" style="font-size:.85rem;flex-shrink:0"></i>
                                No hay sesiones activas disponibles en tus cajas asignadas.
                            </div>
                        </template>

                        <select
                            class="caja-choice__select"
                            x-model="sesionSeleccionadaId"
                            :disabled="sesionesDisponibles.length === 0"
                        >
                            <option value="">Selecciona una sesión...</option>
                            <template x-for="s in sesionesDisponibles" :key="s.cse_id">
                                <option :value="String(s.cse_id)" x-text="`${s.caja_nombre} · abrió ${s.usuario_apertura}`"></option>
                            </template>
                        </select>

                        <button
                            class="caja-choice__btn caja-choice__btn--accent"
                            @click="tomarCajaAbierta()"
                            :disabled="!sesionSeleccionadaId"
                        >
                            <i class="ti tabler-login" style="font-size:.9rem"></i>
                            Entrar a esta sesión
                        </button>
                    </div>

                    {{-- Divisor --}}
                    <div class="caja-gate__sep">
                        <div class="caja-gate__sep-line"></div>
                        <div class="caja-gate__sep-label">O</div>
                        <div class="caja-gate__sep-line"></div>
                    </div>

                    {{-- Opción B: Abrir sesión nueva --}}
                    <div class="caja-choice caja-choice--success">
                        <div class="caja-choice__icon caja-choice__icon--success">
                            <i class="ti tabler-lock-open"></i>
                        </div>
                        <p class="caja-choice__title">Abrir sesión nueva</p>
                        <p class="caja-choice__hint">No hay sesión activa. Abre una nueva en tu caja asignada y comienza a cobrar.</p>

                        <template x-if="cajasParaAbrir.length === 0">
                            <div class="caja-choice__empty">
                                <i class="ti tabler-info-circle" style="font-size:.85rem;flex-shrink:0"></i>
                                No hay cajas disponibles para abrir en este momento.
                            </div>
                        </template>

                        <select
                            class="caja-choice__select"
                            x-model="cajaSeleccionadaId"
                            :disabled="cajasParaAbrir.length === 0"
                        >
                            <option value="">Selecciona una caja...</option>
                            <template x-for="c in cajasParaAbrir" :key="c.caj_id">
                                <option :value="String(c.caj_id)" x-text="`${c.caj_nombre} · ${c.sucursal}`"></option>
                            </template>
                        </select>

                        <button
                            class="caja-choice__btn caja-choice__btn--success"
                            @click="abrirCajaNueva()"
                            :disabled="!cajaSeleccionadaId"
                        >
                            <i class="ti tabler-circle-check" style="font-size:.9rem"></i>
                            Abrir y empezar a cobrar
                        </button>
                    </div>

                </div>

                <div class="d-flex justify-content-end mt-4 pt-2 border-top">
                    <a
                        href="{{ route('desktop.dashboard') }}"
                        class="btn btn-label-secondary"
                    >
                        <i class="ti tabler-arrow-left me-1"></i>
                        Regresar al dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('pos-scripts')
<script src="{{ asset('vendor-template/assets/vendor/libs/select2/select2.js') }}"></script>
<script>
function posApp() {
    const estadoInicial = @json($estado ?? []);
    const almacenesVentaInicial = @json($almacenesVenta ?? []);
    const vendedoresInicial = @json($vendedores ?? []);
    const usuariosAutorizadosRetiroInicial = @json($usuariosAutorizadosRetiro ?? []);
    const usuariosAutorizadosCorteInicial = @json($usuariosAutorizadosCorte ?? []);
    const categoriasGastoSugeridasInicial = @json($categoriasGastoSugeridas ?? []);
    const sucursalActivaIdInicial = {{ (int) ($sucursalActivaId ?? 0) }};
    const usuarioActualId = {{ (int) (auth()->user()->usr_id ?? 0) }};
    const usuarioActualNombre = @json((string) (auth()->user()->usr_nombre ?? auth()->user()->usr_usuario ?? 'Sin vendedor'));
    const rutaBuscarProducto = '{{ route('operacion.escaneo_productos.buscar') }}';
    const rutaPosResolverProductoAlmacen = '{{ route('pos.productos.resolver_almacen') }}';
    const rutaPosValidarProductoAlmacen = '{{ route('pos.productos.validar_almacen') }}';
    const rutaBuscarPedidoFolio = '{{ route('operacion.pedidos_piso.folio.buscar') }}';
    const rutaBuscarClientes = '{{ route('pos.clientes.buscar') }}';
    const rutaCrearCliente = '{{ route('operacion.clientes.store') }}';
    const rutaCpBuscarCliente = '{{ route('operacion.clientes.cp.buscar') }}';
    const rutaCobrarVenta = '{{ route('pos.ventas.cobrar') }}';
    const rutaCambioStore = '{{ route('pos.cambios.store') }}';
    const rutaCreditoCambioStore = '{{ route('pos.creditos_cambio.store') }}';
    const rutaCreditosCambioIndex = '{{ route('pos.creditos_cambio.index') }}';
    const rutaBuscarCreditoCambioFolio = '{{ route('pos.creditos_cambio.buscar_folio') }}';
    const rutaVentasDia = '{{ route('pos.ventas.dia') }}';
    const rutaRetiroCajaStore = '{{ route('pos.caja.retiros.store') }}';
    const rutaGastoCajaStore = '{{ route('pos.caja.gastos.store') }}';
    const rutaCorteCajaStore = '{{ route('pos.caja.cortes.store') }}';
    const rutaBuscarVentaFolio = '{{ route('pos.ventas.buscar_folio') }}';
    const rutaPedidosPendientes = '{{ route('pos.pedidos.pendientes') }}';
    const rutaTicketVentaBase = '{{ url('/pos/ventas') }}';
    const rutaTicketVentaEscposBase = '{{ url('/pos/ventas') }}';
    const rutaTicketCreditoCambioBase = '{{ url('/pos/creditos-cambio') }}';
    const rutaTicketCreditoCambioEscposBase = '{{ url('/pos/creditos-cambio') }}';
    const rutaTicketCorteCajaBase = '{{ url('/pos/caja/cortes') }}';
    const rutaTicketCorteCajaEscposBase = '{{ url('/pos/caja/cortes') }}';
    const rutaCancelarVentaBase = '{{ url('/pos/ventas') }}';
    const agenteImpresionUrlDefault = 'http://127.0.0.1:17890';
    const storageAgenteImpresionHabilitado = 'laisuriana.pos.agente_impresion.habilitado';
    const storageAgenteImpresionUrl = 'laisuriana.pos.agente_impresion.url';
    const puedeCrearCliente = @json($puedeCrearCliente ?? false);
    const puedeCancelarVenta = @json($puedeCancelarVenta ?? false);
    const puedeRegistrarCambio = @json($puedeRegistrarCambio ?? false);
    const puedeRegistrarRetiroCaja = @json($puedeRegistrarRetiroCaja ?? false);
    const puedeRegistrarGastoCaja = @json($puedeRegistrarGastoCaja ?? false);
    return {
        // ── Config ───────────────────────────────────────────────
        tab:             'ventas',
        sucursal:        '{{ $sucursal ?? "ADRIEL SABAH" }}',
        cajaNombre:      '{{ $caja ?? "Sin caja activa" }}',
        usuarioActualNombre: usuarioActualNombre,
        impresionEstado: 'Sin actividad',
        agenteImpresionHabilitado: false,
        agenteImpresionUrl: agenteImpresionUrlDefault,
        sesionActiva: estadoInicial.sesion_activa ?? null,
        mostrarModalCaja: false,
        cajasAsignadas: estadoInicial.cajas_asignadas ?? [],
        cajasParaAbrir: estadoInicial.cajas_para_abrir ?? [],
        sesionesDisponibles: estadoInicial.sesiones_disponibles ?? [],
        cajaSeleccionadaId: '',
        sesionSeleccionadaId: '',
        enfoqueUnirme: false,
        sugerenciasProducto: [],
        mostrarSugerencias: false,
        sugerenciaActivaIndex: -1,
        timerBusquedaProducto: null,
        variantesPendientes: [],
        mostrarSelectorVariantes: false,
        mostrarModalPedido: false,
        folioPedidoBuscar: '',
        pedidoPreview: null,
        pedidoMensaje: '',
        pedidoCargado: null,
        pedidosPendientes: [],
        cargandoPedidosPendientes: false,
        almacenesVenta: Array.isArray(almacenesVentaInicial) ? almacenesVentaInicial : [],
        vendedores: Array.isArray(vendedoresInicial) ? vendedoresInicial : [],
        usuariosAutorizadosRetiro: Array.isArray(usuariosAutorizadosRetiroInicial) ? usuariosAutorizadosRetiroInicial : [],
        usuariosAutorizadosCorte: Array.isArray(usuariosAutorizadosCorteInicial) ? usuariosAutorizadosCorteInicial : [],
        categoriasGastoSugeridas: Array.isArray(categoriasGastoSugeridasInicial) ? categoriasGastoSugeridasInicial : [],
        mostrarSugerenciasCategoriaGasto: false,
        vendedorSeleccionadoId: '',
        sucursalActivaId: Number(sucursalActivaIdInicial || 0),
        ventaAlmacenId: '',
        ventaAlmacenNombre: '',
        almacenesModalVenta: [],
        modalAlmacenVentaTitulo: 'Selecciona el almacén del ticket',
        modalAlmacenVentaMensaje: 'Antes de continuar, define de qué almacén se descontará este ticket.',
        modalAlmacenVentaContexto: 'ticket',
        productoPendienteAlmacen: null,
        mostrarModalAlmacenVenta: false,
        mostrarModalConfirmacionPedido: false,
        mostrarModalClientes: false,
        mostrarModalPago: false,
        mostrarModalResumenCaja: false,
        mostrarModalValesCambio: false,
        mostrarModalTickets: false,
        mostrarModalCambio: false,
        mostrarModalAviso: false,
        mostrarModalMovimientoCaja: false,
        mostrarModalDescuentoItem: false,
        mostrarCorteCaja: false,
        mostrarModalCorteAutorizacion: false,
        billetesCorte: [1000, 500, 200, 100, 50, 20],
        monedasCorte: [
            { clave: '10', valor: 10, etiqueta: '$10.00' },
            { clave: '5', valor: 5, etiqueta: '$5.00' },
            { clave: '2', valor: 2, etiqueta: '$2.00' },
            { clave: '1', valor: 1, etiqueta: '$1.00' },
            { clave: '0_50', valor: 0.5, etiqueta: '$0.50' },
        ],
        corteCajaForm: {
            denominaciones: { 1000: '', 500: '', 200: '', 100: '', 50: '', 20: '', 10: '', 5: '', 2: '', 1: '', '0_50': '' },
            observaciones: '',
        },
        corteAutorizaForm: { usr_id: '', password: '' },
        corteAutorizaErrores: {},
        guardandoCorte: false,
        puedeCrearCliente: !!puedeCrearCliente,
        puedeCancelarVenta: !!puedeCancelarVenta,
        puedeRegistrarCambio: !!puedeRegistrarCambio,
        puedeRegistrarRetiroCaja: !!puedeRegistrarRetiroCaja,
        puedeRegistrarGastoCaja: !!puedeRegistrarGastoCaja,
        guardandoClienteNuevo: false,
        cobrandoVenta: false,
        guardandoMovimientoCaja: false,
        cargandoTickets: false,
        cargandoVentaCambio: false,
        cpRowsCliente: [],
        pedidoPendienteReemplazo: null,
        ventasDelDia: [],
        resumenCaja: null,
        filtroTicket: '',
        folioCambioBuscar: '',
        ventaCambioPreview: null,
        mensajeCambio: '',
        cambioActual: null,
        folioCreditoCambio: '',
        mostrarCapturaVale: false,
        timerBusquedaCreditoCambio: null,
        buscandoCreditoCambio: false,
        mensajeCreditoCambio: '',
        creditoCambioSeleccionado: null,
        valesCambio: [],
        cargandoValesCambio: false,
        mensajeValesCambio: '',
        filtrosValesCambio: {
            folio: '',
            cliente: '',
            estatus: '',
        },
        modalAvisoTitulo: '',
        modalAvisoMensaje: '',
        movimientoCajaTipo: 'retiro',
        movimientoCajaPaso: 1,
        movimientoCajaForm: {
            monto: '',
            denominaciones: { 1000: '', 500: '', 200: '', 100: '', 50: '', 20: '', 10: '', 5: '', 2: '', 1: '', '0_50': '' },
            categoria: '',
            referencia: '',
            motivo: '',
            autoriza_usr_id: '',
            autoriza_password: '',
        },
        movimientoCajaErrores: {},
        tipoPagoSeleccionado: 'efectivo',
        pagoReferencia: '',
        pagoLineas: [],
        pagoEfectivoRecibido: 0,
        imprimirDespuesCobro: true,

        // ── Inputs ───────────────────────────────────────────────
        queryProducto: '',
        queryCliente:  '',
        clienteNuevo: {
            nombre: '',
            apellido_paterno: '',
            apellido_materno: '',
            razon_social: '',
            fecha_nacimiento: '',
            estatus: 'activo',
            descuento_default: '',
            telefono: '',
            whatsapp: '',
            email: '',
            doc_tipo: '',
            doc_valor: '',
            cp: '',
            colonia: '',
            tipo_asentamiento: '',
            municipio: '',
            estado: '',
            ciudad: '',
            calle: '',
            num_ext: '',
            num_int: '',
            referencias: '',
        },
        sugerenciasCliente: [],
        mostrarSugerenciasCliente: false,
        sugerenciaClienteActivaIndex: -1,
        timerBusquedaCliente: null,
        notas:         '',
        descuentoItemIndex: -1,
        descuentoItemValor: '',
        descuentoItemNombre: '',
        descuentoItemTipo: 'porcentaje',
        descuentoItemCantidad: '',
        descuentoItemPermiteDecimal: false,

        // ── Ticket ───────────────────────────────────────────────
        items:               [],
        ventasEspera:        [],
        clienteSeleccionado: null,

        // ── Financials ───────────────────────────────────────────
        pagado:    0,
        descuentoGlobal: 0,
        fechaHora: '',

        // ── Computed ─────────────────────────────────────────────
        get subtotal() {
            return this.items.reduce((s, i) => s + this.itemImporte(i), 0);
        },
        get descuento() {
            if (this.descuentoGlobal <= 0) return 0;
            return this.subtotal * (this.descuentoGlobal / 100);
        },
        get total() {
            return Math.max(0, this.subtotal - this.descuento - this.creditoCambio);
        },
        get cambio() {
            return Math.max(0, this.pagado - this.total);
        },
        get creditoCambio() {
            return Number(this.cambioActual?.credito_total || this.creditoCambioSeleccionado?.monto_aplicado || 0);
        },
        get cambioActivo() {
            return !!this.cambioActual?.venta_origen_id;
        },
        get hayCreditoCambioAplicado() {
            return Number(this.creditoCambio || 0) > 0;
        },
        get cambioInvalidoMenorValor() {
            return this.cambioActivo && (this.subtotal - this.descuento) < this.creditoCambio;
        },
        get totalVentasDia() {
            return (this.ventasDelDia || []).reduce((sum, v) => sum + Number(v.psv_total || 0), 0);
        },
        get categoriasGastoFiltradas() {
            const query = String(this.movimientoCajaForm?.categoria || '').trim().toLowerCase();
            const categorias = Array.isArray(this.categoriasGastoSugeridas) ? this.categoriasGastoSugeridas : [];

            if (!query) {
                return categorias.slice(0, 6);
            }

            return categorias
                .filter((categoria) => String(categoria || '').toLowerCase().includes(query))
                .slice(0, 6);
        },
        get retiroCajaRecomendado() {
            return !!this.resumenCaja?.retiro_recomendado;
        },
        get iva() {
            return 0;
        },
        get totalArticulos() {
            return this.items.reduce((s, i) => s + i.cantidad, 0);
        },
        get totalPagoCapturado() {
            if (this.tipoPagoSeleccionado === 'efectivo') {
                return Number(this.pagoEfectivoRecibido || 0);
            }
            return this.pagoLineas.reduce((sum, ln) => sum + Number(ln.recibido ?? ln.monto ?? 0), 0);
        },
        get cambioPago() {
            return Math.max(0, this.totalPagoCapturado - this.total);
        },
        get restantePagoModal() {
            return Math.max(0, this.total - this.totalPagoCapturado);
        },
        get sugerenciasPagoEfectivo() {
            return this.generarSugerenciasPagoEfectivo(this.total);
        },

        // ── Init ─────────────────────────────────────────────────
        init() {
            this.cargarConfiguracionAgenteImpresion();
            this.actualizarReloj();
            setInterval(() => this.actualizarReloj(), 1000);
            this.validarSesionCaja();
            this.aplicarAlmacenPorSesion();
            this.$nextTick(() => {
                this.initVendedorSelect2();
                this.$refs.productoInput?.focus();
            });
        },

        initVendedorSelect2() {
            const select = this.$refs.vendedorSelect;
            if (!select || !window.jQuery || typeof window.jQuery(select).select2 !== 'function') return;

            const $select = window.jQuery(select);
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }

            $select
                .select2({
                    placeholder: 'Sin seleccionar',
                    allowClear: true,
                    width: '100%',
                    dropdownCssClass: 'pos-vendedor-select-dropdown',
                })
                .on('change', () => {
                    this.vendedorSeleccionadoId = String($select.val() || '');
                });
        },

        async validarSesionCaja() {
            if (this.sesionActiva) {
                this.mostrarModalCaja = false;
                this.cajaNombre = this.sesionActiva.caja_nombre ?? this.cajaNombre;
                this.aplicarAlmacenPorSesion();
                await this.cargarVentasDia();
                return;
            }

            await this.recargarEstadoCaja();
            this.aplicarPrioridadSesionPropia();
            this.mostrarModalCaja = !this.sesionActiva;
        },

        async recargarEstadoCaja() {
            const res = await fetch('{{ route('pos.caja.estado') }}', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            const data = json.data || {};
            this.sesionActiva = data.sesion_activa ?? null;
            this.cajasAsignadas = data.cajas_asignadas ?? [];
            this.cajasParaAbrir = data.cajas_para_abrir ?? [];
            this.sesionesDisponibles = data.sesiones_disponibles ?? [];
            this.resumenCaja = json.resumen || this.resumenCaja;
            this.aplicarPrioridadSesionPropia();
            this.aplicarAlmacenPorSesion();
        },

        aplicarPrioridadSesionPropia() {
            const sesionPropia = (this.sesionesDisponibles || []).find(
                (s) => Number(s.usuario_apertura_id) === Number(usuarioActualId)
            );

            if (sesionPropia) {
                this.sesionSeleccionadaId = String(sesionPropia.cse_id);
                this.enfoqueUnirme = true;
                return;
            }

            this.enfoqueUnirme = false;
        },

        aplicarAlmacenPorSesion() {
            if (this.pedidoCargado) return;
            if (this.items.length > 0 || this.ventaAlmacenId) return;
            this.ventaAlmacenNombre = '';
        },

        normalizarFolioEscaneado(valor) {
            return String(valor || '')
                .replace(/['’`´]/g, '-')
                .replace(/\s+/g, '')
                .toUpperCase()
                .trim();
        },

        manejarEntradaCreditoCambio(valor) {
            this.folioCreditoCambio = this.normalizarFolioEscaneado(valor);
            this.mensajeCreditoCambio = '';

            if (this.timerBusquedaCreditoCambio) {
                clearTimeout(this.timerBusquedaCreditoCambio);
            }

            const folio = this.folioCreditoCambio;
            if (!folio || !folio.startsWith('CDC-') || folio.length < 10) {
                return;
            }

            this.timerBusquedaCreditoCambio = setTimeout(() => {
                this.buscarCreditoCambioParaCobro();
            }, 180);
        },

        sincronizarPagoConTotalActual() {
            const total = this.normalizarMonto(this.total || 0);

            if (this.tipoPagoSeleccionado === 'efectivo') {
                this.pagoEfectivoRecibido = total;
                this.aplicarPagoEfectivo();
                return;
            }

            if (this.tipoPagoSeleccionado === 'mixto') {
                if (!Array.isArray(this.pagoLineas) || this.pagoLineas.length === 0) {
                    this.pagoLineas = [{ metodo: 'efectivo', monto: total, recibido: total }];
                    return;
                }

                this.pagoLineas = this.pagoLineas.map((linea, idx) => ({
                    ...linea,
                    monto: idx === 0 ? total : 0,
                    recibido: idx === 0 ? total : 0,
                }));
                return;
            }

            this.pagoLineas = [{ metodo: this.tipoPagoSeleccionado, monto: total, recibido: total }];
        },

        obtenerSucursalOperativaId() {
            return Number(this.sesionActiva?.caja_scl_id || this.sucursalActivaId || 0);
        },

        abrirSelectorAlmacenVenta(opciones, titulo, mensaje, contexto = 'ticket', producto = null) {
            this.modalAlmacenVentaTitulo = titulo || 'Selecciona el almacén del ticket';
            this.modalAlmacenVentaMensaje = mensaje || 'Antes de continuar, define de qué almacén se descontará este ticket.';
            this.modalAlmacenVentaContexto = contexto || 'ticket';
            this.productoPendienteAlmacen = producto || null;
            this.almacenesModalVenta = Array.isArray(opciones) ? opciones : [];
            this.mostrarModalAlmacenVenta = true;
        },

        cerrarModalAlmacenVenta() {
            this.mostrarModalAlmacenVenta = false;
            this.modalAlmacenVentaContexto = 'ticket';
            this.productoPendienteAlmacen = null;
            this.almacenesModalVenta = [];
        },

        async resolverAlmacenParaProducto(item) {
            const sucursalId = this.obtenerSucursalOperativaId();
            if (!sucursalId) {
                this.abrirModalAviso('Sucursal no disponible', 'No fue posible identificar la sucursal activa para asignar el almacén del producto.');
                return false;
            }

            if (this.ventaAlmacenId) {
                const valido = await this.validarProductoContraAlmacen(item.psk_id, this.ventaAlmacenId, sucursalId);
                if (!valido) {
                    return false;
                }

                return {
                    ok: true,
                    almacen_id: Number(this.ventaAlmacenId),
                    almacen: this.ventaAlmacenNombre || this.obtenerNombreAlmacen(this.ventaAlmacenId),
                };
            }

            try {
                const res = await fetch(`${rutaPosResolverProductoAlmacen}?psk_id=${encodeURIComponent(item.psk_id)}&scl_id=${encodeURIComponent(sucursalId)}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const json = await res.json().catch(() => ({}));

                if (!res.ok) {
                    this.abrirModalAviso('Producto sin almacén válido', json?.message || 'No fue posible asignar un almacén para este producto.');
                    return false;
                }

                return {
                    ok: true,
                    requiereSeleccion: !!json?.data?.requiere_seleccion,
                    almacen_id: Number(json?.data?.almacen_id || 0),
                    almacen: String(json?.data?.almacen || ''),
                    almacenes: Array.isArray(json?.data?.almacenes) ? json.data.almacenes : [],
                };
            } catch (error) {
                this.abrirModalAviso('Sin conexión', 'No fue posible resolver el almacén del producto en este momento.');
                return false;
            }
        },

        async validarProductoContraAlmacen(pskId, almacenId, sucursalId = null) {
            const sclId = Number(sucursalId || this.obtenerSucursalOperativaId() || 0);
            if (!sclId || !almacenId) return false;

            try {
                const res = await fetch(`${rutaPosValidarProductoAlmacen}?psk_id=${encodeURIComponent(pskId)}&scl_id=${encodeURIComponent(sclId)}&almacen_id=${encodeURIComponent(almacenId)}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const json = await res.json().catch(() => ({}));

                if (res.ok) {
                    return true;
                }

                this.abrirModalAviso('Producto no compatible con el almacén', json?.message || 'Este producto no pertenece al almacén seleccionado para el ticket.');
                return false;
            } catch (error) {
                this.abrirModalAviso('Sin conexión', 'No fue posible validar el almacén de este producto.');
                return false;
            }
        },

        async abrirCajaNueva() {
            if (!this.cajaSeleccionadaId) return;
            const res = await fetch('{{ route('pos.caja.abrir') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ caja_id: this.cajaSeleccionadaId }),
            });

            if (!res.ok) {
                alert('No fue posible iniciar caja en este momento.');
                return;
            }

            await this.recargarEstadoCaja();
            this.cajaNombre = this.sesionActiva?.caja_nombre ?? this.cajaNombre;
            this.mostrarModalCaja = false;
            await this.cargarVentasDia();
        },

        async tomarCajaAbierta() {
            if (!this.sesionSeleccionadaId) return;
            const res = await fetch('{{ route('pos.caja.tomar') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ sesion_id: this.sesionSeleccionadaId }),
            });

            if (!res.ok) {
                alert('No fue posible unirte a esa sesión.');
                return;
            }

            await this.recargarEstadoCaja();
            this.cajaNombre = this.sesionActiva?.caja_nombre ?? this.cajaNombre;
            this.mostrarModalCaja = false;
            await this.cargarVentasDia();
        },

        actualizarReloj() {
            const now = new Date();
            this.fechaHora = now.toLocaleDateString('es-MX', {
                day: '2-digit', month: '2-digit', year: 'numeric'
            }) + ' ' + now.toLocaleTimeString('es-MX', {
                hour: '2-digit', minute: '2-digit', second: '2-digit'
            });
        },

        // ── Keyboard shortcuts ───────────────────────────────────
        handleKey(e) {
            if (this.mostrarModalPago) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    this.cerrarModalPago();
                    return;
                }
                if (e.key === 'F1') {
                    e.preventDefault();
                    this.confirmarCobro(true);
                    return;
                }
                if (e.key === 'F2') {
                    e.preventDefault();
                    this.confirmarCobro(false);
                    return;
                }
                if (e.key === 'F4') {
                    e.preventDefault();
                    this.$refs.notasInput?.focus();
                    return;
                }
            }
            const map = {
                F1:  () => { this.tab = 'ventas'; },
                F2:  () => { this.tab = 'clientes'; this.abrirModalClientes(); },
                F3:  () => { this.tab = 'cotizacion'; },
                F4:  () => { this.tab = 'inventario'; },
                F5:  () => this.abrirResumenCaja(),
                F6:  () => { this.tab = 'reimprimir'; },
                F7:  () => this.enviarEspera(),
                F8:  () => this.devolucion(),
                F9:  () => this.abrirModalValesCambio(),
                F11: () => this.corteCaja(),
                F12: () => { if (this.items.length > 0) this.cobrar(); },
            };
            if (map[e.key]) { e.preventDefault(); map[e.key](); }
        },

        // ── Product search ───────────────────────────────────────
        normalizarCodigoProducto(valor) {
            return String(valor ?? '').replaceAll("'", '-');
        },

        onInputProducto() {
            clearTimeout(this.timerBusquedaProducto);
            const normalizado = this.normalizarCodigoProducto(this.queryProducto);
            if (normalizado !== this.queryProducto) {
                this.queryProducto = normalizado;
            }

            const q = this.queryProducto.trim();
            if (q.length < 2) {
                this.sugerenciasProducto = [];
                this.mostrarSugerencias = false;
                this.sugerenciaActivaIndex = -1;
                return;
            }

            this.timerBusquedaProducto = setTimeout(async () => {
                await this.buscarSugerenciasProducto(q);
            }, 180);
        },
        abrirModalClientes() {
            const formCliente = document.getElementById('form-cliente');
            if (formCliente) formCliente.reset();
            const modalTitle = document.getElementById('modal-title');
            if (modalTitle) modalTitle.textContent = 'Nuevo cliente';
            const cliId = document.getElementById('cli_id');
            if (cliId) cliId.value = '';
            const prevNombre = document.getElementById('cliente-preview-nombre');
            if (prevNombre) prevNombre.textContent = 'Vista previa del nombre';
            const colonia = document.getElementById('cli_colonia');
            if (colonia) colonia.innerHTML = '<option value="">Selecciona</option>';
            this.cpRowsCliente = [];
            this.mostrarModalClientes = true;
            this.clienteNuevo = {
                nombre: '',
                apellido_paterno: '',
                apellido_materno: '',
                razon_social: '',
                fecha_nacimiento: '',
                estatus: 'activo',
                descuento_default: '',
                telefono: '',
                whatsapp: '',
                email: '',
                doc_tipo: '',
                doc_valor: '',
                cp: '',
                colonia: '',
                tipo_asentamiento: '',
                municipio: '',
                estado: '',
                ciudad: '',
                calle: '',
                num_ext: '',
                num_int: '',
                referencias: '',
            };
            this.$nextTick(() => {
                const formClienteNow = document.getElementById('form-cliente');
                if (formClienteNow && !formClienteNow.dataset.posBound) {
                    formClienteNow.addEventListener('submit', (e) => {
                        e.preventDefault();
                        this.guardarClienteDesdePos();
                    });
                    formClienteNow.dataset.posBound = '1';
                }

                const cpInput = document.getElementById('cli_cp');
                if (cpInput && !cpInput.dataset.posCpBound) {
                    cpInput.addEventListener('blur', () => this.buscarCpCliente(true));
                    cpInput.dataset.posCpBound = '1';
                }

                const coloniaSelect = document.getElementById('cli_colonia');
                if (coloniaSelect && !coloniaSelect.dataset.posColoniaBound) {
                    coloniaSelect.addEventListener('change', (e) => this.refrescarDependientesAsentamientoCliente(e.target.value));
                    coloniaSelect.dataset.posColoniaBound = '1';
                }
            });
            this.$nextTick(() => document.getElementById('cli_nombre')?.focus());
        },
        escHtml(v) {
            return String(v ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;');
        },
        refrescarDependientesAsentamientoCliente(colonia) {
            const found = this.cpRowsCliente.find((x) => (x.cp_asentamiento || '') === colonia);
            if (!found) return;
            const tipo = document.getElementById('cli_tipo_asentamiento');
            const municipio = document.getElementById('cli_municipio');
            const estado = document.getElementById('cli_estado');
            const ciudad = document.getElementById('cli_ciudad');
            if (tipo) tipo.value = found.cp_tipo_asentamiento || '';
            if (municipio) municipio.value = found.cp_municipio || '';
            if (estado) estado.value = found.cp_estado || '';
            if (ciudad) ciudad.value = found.cp_ciudad || '';
        },
        async buscarCpCliente(enfocarColonia = true) {
            const cp = String(document.getElementById('cli_cp')?.value || '').trim();
            if (!cp) return;
            const res = await fetch(`${rutaCpBuscarCliente}?codigo_postal=${encodeURIComponent(cp)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) return;
            this.cpRowsCliente = (await res.json()).data || [];
            const select = document.getElementById('cli_colonia');
            if (!select) return;
            select.innerHTML = '<option value="">Selecciona</option>' + this.cpRowsCliente
                .map((r) => `<option value="${this.escHtml(r.cp_asentamiento)}">${this.escHtml(r.cp_asentamiento)} (${this.escHtml(r.cp_tipo_asentamiento || 'Asentamiento')})</option>`)
                .join('');
            if (this.cpRowsCliente.length) {
                select.value = this.cpRowsCliente[0].cp_asentamiento || '';
                this.refrescarDependientesAsentamientoCliente(select.value);
                if (enfocarColonia) select.focus();
            }
        },
        cerrarModalClientes() {
            this.mostrarModalClientes = false;
        },
        onInputCliente() {
            clearTimeout(this.timerBusquedaCliente);
            const q = (this.queryCliente || '').trim();
            if (q.length < 2) {
                this.sugerenciasCliente = [];
                this.mostrarSugerenciasCliente = false;
                this.sugerenciaClienteActivaIndex = -1;
                return;
            }
            this.timerBusquedaCliente = setTimeout(async () => {
                try {
                    const res = await fetch(`${rutaBuscarClientes}?q=${encodeURIComponent(q)}`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (!res.ok) return;
                    const json = await res.json();
                    this.sugerenciasCliente = Array.isArray(json?.data) ? json.data : [];
                    this.mostrarSugerenciasCliente = this.sugerenciasCliente.length > 0;
                    this.sugerenciaClienteActivaIndex = this.sugerenciasCliente.length ? 0 : -1;
                } catch (error) {
                    this.sugerenciasCliente = [];
                    this.mostrarSugerenciasCliente = false;
                    this.sugerenciaClienteActivaIndex = -1;
                }
            }, 180);
        },
        navegarSugerenciaCliente(dir) {
            if (!this.mostrarSugerenciasCliente || this.sugerenciasCliente.length === 0) return;
            const max = this.sugerenciasCliente.length - 1;
            this.sugerenciaClienteActivaIndex = Math.min(max, Math.max(0, this.sugerenciaClienteActivaIndex + dir));
        },
        seleccionarClienteActivo() {
            if (!this.mostrarSugerenciasCliente || this.sugerenciasCliente.length === 0) return;
            const idx = this.sugerenciaClienteActivaIndex >= 0 ? this.sugerenciaClienteActivaIndex : 0;
            this.seleccionarCliente(idx);
        },
        seleccionarCliente(idx) {
            if (idx < 0 || idx >= this.sugerenciasCliente.length) return;
            const c = this.sugerenciasCliente[idx];
            this.clienteSeleccionado = c;
            this.queryCliente = c.nombre || '';
            this.aplicarDescuentoCliente(c);
            this.cerrarSugerenciasCliente();
            this.mostrarModalClientes = false;
        },
        cerrarSugerenciasCliente() {
            this.mostrarSugerenciasCliente = false;
            this.sugerenciaClienteActivaIndex = -1;
        },
        aplicarDescuentoCliente(cliente) {
            const porcentaje = Number(cliente?.descuento_default ?? 0);
            this.descuentoGlobal = porcentaje > 0 ? Math.min(100, Math.max(1, porcentaje)) : 0;
        },
        async guardarClienteDesdePos() {
            if (!this.puedeCrearCliente || this.guardandoClienteNuevo) return;
            const formCliente = document.getElementById('form-cliente');
            if (!formCliente) return;
            const fd = new FormData(formCliente);
            const cliNombre = String(fd.get('cli_nombre') || '').trim();
            if (!cliNombre) {
                alert('Captura al menos el nombre del cliente.');
                return;
            }

            const tipo = String(fd.get('doc_tipo') || '').toLowerCase();
            const valor = String(fd.get('doc_valor') || '').trim().toUpperCase();
            const body = {
                cli_nombre: cliNombre,
                cli_apellido_paterno: String(fd.get('cli_apellido_paterno') || '').trim() || null,
                cli_apellido_materno: String(fd.get('cli_apellido_materno') || '').trim() || null,
                cli_razon_social: String(fd.get('cli_razon_social') || '').trim() || null,
                cli_fecha_nacimiento: String(fd.get('cli_fecha_nacimiento') || '').trim() || null,
                cli_descuento_default: String(fd.get('cli_descuento_default') || '').trim() || null,
                cli_telefono: String(fd.get('cli_telefono') || '').trim() || null,
                cli_whatsapp: String(fd.get('cli_whatsapp') || '').trim() || null,
                cli_email: String(fd.get('cli_email') || '').trim() || null,
                cli_rfc: tipo === 'rfc' && valor ? valor : null,
                cli_curp: tipo === 'curp' && valor ? valor : null,
                cli_ine: tipo === 'ine' && valor ? valor : null,
                cli_cp: String(fd.get('cli_cp') || '').trim() || null,
                cli_colonia: String(fd.get('cli_colonia') || '').trim() || null,
                cli_tipo_asentamiento: String(fd.get('cli_tipo_asentamiento') || '').trim() || null,
                cli_municipio: String(fd.get('cli_municipio') || '').trim() || null,
                cli_estado: String(fd.get('cli_estado') || '').trim() || null,
                cli_ciudad: String(fd.get('cli_ciudad') || '').trim() || null,
                cli_calle: String(fd.get('cli_calle') || '').trim() || null,
                cli_num_ext: String(fd.get('cli_num_ext') || '').trim() || null,
                cli_num_int: String(fd.get('cli_num_int') || '').trim() || null,
                cli_referencias: String(fd.get('cli_referencias') || '').trim() || null,
                cli_estatus: String(fd.get('cli_estatus') || 'activo'),
            };

            this.guardandoClienteNuevo = true;
            try {
                const res = await fetch(rutaCrearCliente, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(body),
                });
                if (!res.ok) {
                    const err = await res.json();
                    const first = Object.values(err?.errors || {})[0];
                    alert(first ? first[0] : 'No se pudo guardar el cliente.');
                    return;
                }

                const nombreCompleto = [body.cli_nombre, body.cli_apellido_paterno, body.cli_apellido_materno].filter(Boolean).join(' ');
                this.clienteSeleccionado = {
                    cli_id: Number(json?.data?.cli_id || 0),
                    nombre: nombreCompleto,
                    telefono: body.cli_telefono || '',
                    email: body.cli_email || '',
                    rfc: body.cli_rfc || '',
                    descuento_default: body.cli_descuento_default ? Number(body.cli_descuento_default) : null,
                };
                this.aplicarDescuentoCliente(this.clienteSeleccionado);
                this.queryCliente = nombreCompleto;
                this.cerrarModalClientes();
                this.cerrarSugerenciasCliente();
            } finally {
                this.guardandoClienteNuevo = false;
            }
        },

        async buscarSugerenciasProducto(q) {
            try {
                const res = await fetch(`${rutaBuscarProducto}?modo=sugerencias&q=${encodeURIComponent(q)}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok) return;

                const json = await res.json();
                this.sugerenciasProducto = Array.isArray(json?.data) ? json.data : [];
                this.mostrarSugerencias = this.sugerenciasProducto.length > 0;
                this.sugerenciaActivaIndex = this.sugerenciasProducto.length ? 0 : -1;
            } catch (error) {
                this.sugerenciasProducto = [];
                this.mostrarSugerencias = false;
                this.sugerenciaActivaIndex = -1;
            }
        },

        async buscarProducto() {
            const normalizado = this.normalizarCodigoProducto(this.queryProducto);
            if (normalizado !== this.queryProducto) {
                this.queryProducto = normalizado;
            }

            const q = this.queryProducto.trim();
            if (!q) return;

            // Permite cargar pedido por folio directamente desde el mismo campo.
            if (/^PED-/i.test(q)) {
                await this.cargarPedidoPorFolioDirecto(q.toUpperCase());
                return;
            }

            if (this.mostrarSugerencias && this.sugerenciasProducto.length > 0) {
                const idx = this.sugerenciaActivaIndex >= 0 ? this.sugerenciaActivaIndex : 0;
                this.seleccionarSugerencia(idx);
                return;
            }

            try {
                const res = await fetch(`${rutaBuscarProducto}?modo=sugerencias&q=${encodeURIComponent(q)}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!res.ok) {
                    if (res.status === 404) {
                        alert('No encontramos productos con ese dato.');
                    } else {
                        alert('No fue posible buscar el producto en este momento.');
                    }
                    return;
                }

                const json = await res.json();
                const resultados = Array.isArray(json?.data) ? json.data : [];
                if (!resultados.length) {
                    alert('No encontramos productos con ese dato.');
                    return;
                }

                if (resultados.length === 1) {
                    await this.agregarDesdeBusqueda(resultados[0]);
                    return;
                }

                this.variantesPendientes = resultados;
                this.mostrarSelectorVariantes = true;
            } catch (error) {
                alert('Error de conexión al buscar producto.');
            }
        },

        async cargarPedidoPorFolioDirecto(folio) {
            try {
                const res = await fetch(`${rutaBuscarPedidoFolio}?folio=${encodeURIComponent(folio)}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const json = await res.json();
                if (!res.ok) {
                    alert(json?.message || 'No fue posible cargar el pedido.');
                    return;
                }

                const pedido = json?.data;
                if (!pedido) {
                    alert('No se encontró el pedido solicitado.');
                    return;
                }

                if (this.items.length > 0) {
                    this.pedidoPendienteReemplazo = {
                        pedido,
                        cerrarModalPedido: false,
                    };
                    this.mostrarModalConfirmacionPedido = true;
                    return;
                }

                this.aplicarPedidoEnTicket(pedido, false);
            } catch (error) {
                alert('Error de conexión al cargar el pedido.');
            }
        },

        async buscarTicket() {
            this.mostrarModalTickets = true;
            await this.cargarVentasDia();
        },

        async buscarVentaCambioPorFolio() {
            const folio = this.normalizarFolioEscaneado(this.folioCambioBuscar);
            this.folioCambioBuscar = folio;
            if (!folio) {
                this.mensajeCambio = 'Captura un folio para buscar la venta.';
                return;
            }

            this.cargandoVentaCambio = true;
            this.mensajeCambio = '';
            this.ventaCambioPreview = null;
            try {
                const res = await fetch(`${rutaBuscarVentaFolio}?folio=${encodeURIComponent(folio)}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const json = await res.json().catch(() => ({}));
                if (!res.ok) {
                    this.mensajeCambio = json?.message || 'No fue posible consultar la venta.';
                    return;
                }
                const venta = json?.data || null;
                if (!venta) {
                    this.mensajeCambio = 'No se encontró la venta solicitada.';
                    return;
                }
                this.ventaCambioPreview = {
                    ...venta,
                    detalle: (venta.detalle || []).map((detalle) => ({
                        ...detalle,
                        devolver_cantidad: 0,
                        condicion: 'reventa',
                    })),
                };
            } catch (error) {
                this.mensajeCambio = 'Error de conexión al consultar la venta.';
            } finally {
                this.cargandoVentaCambio = false;
            }
        },

        creditoCambioPreview() {
            if (!this.ventaCambioPreview) return 0;
            return Number((this.ventaCambioPreview.detalle || []).reduce((sum, detalle) => {
                const cantidad = Number(detalle.devolver_cantidad || 0);
                return sum + (cantidad * Number(detalle.precio_unitario || 0));
            }, 0).toFixed(2));
        },

        normalizarCantidadCambio(detalle) {
            const max = Number(detalle.cantidad_disponible || 0);
            const actual = Math.max(0, Number(detalle.devolver_cantidad || 0));
            detalle.devolver_cantidad = Number(Math.min(max, actual).toFixed(2));
        },

        stepCantidadCambio(detalle) {
            const disponible = Number(detalle?.cantidad_disponible || 0);
            return Number.isInteger(disponible) ? 1 : 0.01;
        },

        incCantidadCambio(detalle) {
            const step = this.stepCantidadCambio(detalle);
            detalle.devolver_cantidad = Number((Number(detalle.devolver_cantidad || 0) + step).toFixed(2));
            this.normalizarCantidadCambio(detalle);
        },

        decCantidadCambio(detalle) {
            const step = this.stepCantidadCambio(detalle);
            detalle.devolver_cantidad = Number((Math.max(0, Number(detalle.devolver_cantidad || 0) - step)).toFixed(2));
            this.normalizarCantidadCambio(detalle);
        },

        async activarCambioDesdePreview() {
            if (!this.ventaCambioPreview) return;
            const devoluciones = (this.ventaCambioPreview.detalle || [])
                .filter((detalle) => Number(detalle.devolver_cantidad || 0) > 0)
                .map((detalle) => ({
                    pvd_id: Number(detalle.pvd_id),
                    psk_id: Number(detalle.psk_id),
                    sku_nombre: detalle.sku_nombre || '',
                    cantidad: Number(detalle.devolver_cantidad || 0),
                    condicion: 'reventa',
                    importe_credito: Number((Number(detalle.devolver_cantidad || 0) * Number(detalle.precio_unitario || 0)).toFixed(2)),
                }));

            if (!devoluciones.length) {
                this.mensajeCambio = 'Selecciona al menos una partida para devolver.';
                return;
            }

            try {
                const res = await fetch(rutaCreditoCambioStore, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        venta_origen_id: Number(this.ventaCambioPreview.psv_id),
                        devoluciones: devoluciones.map((d) => ({
                            pvd_id: Number(d.pvd_id),
                            cantidad: Number(d.cantidad),
                            condicion: 'reventa',
                        })),
                    }),
                });

                const json = await res.json().catch(() => ({}));
                if (!res.ok) {
                    const first = Object.values(json?.errors || {})[0];
                    this.mensajeCambio = first ? first[0] : (json?.message || 'No fue posible generar el crédito de cambio.');
                    return;
                }

                this.mostrarModalCambio = false;
                this.mensajeCambio = '';
                this.ventaCambioPreview = null;
                this.folioCambioBuscar = '';
                const creditoId = Number(json?.data?.pcc_id || 0);
                const creditoFolio = String(json?.data?.pcc_folio || '');
                const creditoMonto = Number(json?.data?.pcc_total_credito || 0);
                this.abrirModalAviso(
                    'Vale de cambio generado',
                    `Se generó el folio ${creditoFolio || 'N/D'} con saldo disponible de ${this.fmt(creditoMonto)}. El cliente ya puede aplicarlo en cualquier caja de esta sucursal.`
                );
                if (creditoId > 0) {
                    this.abrirTicketCreditoCambio(creditoId);
                }
                this.$nextTick(() => this.$refs.productoInput?.focus());
            } catch (error) {
                this.mensajeCambio = 'Error de conexión al generar el crédito de cambio.';
            }
        },

        cancelarCambioActual() {
            if (!this.cambioActivo) return;
            if (!confirm('Se quitará el flujo de cambio actual del ticket. ¿Deseas continuar?')) return;
            this.cambioActual = null;
        },

        cerrarModalCambio() {
            this.mostrarModalCambio = false;
            this.mensajeCambio = '';
            this.ventaCambioPreview = null;
            this.folioCambioBuscar = '';
        },

        async abrirModalValesCambio() {
            this.mostrarModalValesCambio = true;
            await this.cargarValesCambio();
        },

        cerrarModalValesCambio() {
            this.mostrarModalValesCambio = false;
            this.mensajeValesCambio = '';
        },

        async cargarValesCambio() {
            this.cargandoValesCambio = true;
            this.mensajeValesCambio = '';
            try {
                const query = new URLSearchParams({
                    folio: this.normalizarFolioEscaneado(this.filtrosValesCambio.folio || ''),
                    cliente: String(this.filtrosValesCambio.cliente || '').trim(),
                    estatus: String(this.filtrosValesCambio.estatus || '').trim(),
                });
                const res = await fetch(`${rutaCreditosCambioIndex}?${query.toString()}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const json = await res.json().catch(() => ({}));
                if (!res.ok) {
                    this.valesCambio = [];
                    this.mensajeValesCambio = json?.message || 'No fue posible consultar los vales.';
                    return;
                }

                this.valesCambio = Array.isArray(json?.data) ? json.data : [];
            } catch (error) {
                this.valesCambio = [];
                this.mensajeValesCambio = 'Error de conexión al consultar los vales.';
            } finally {
                this.cargandoValesCambio = false;
            }
        },

        limpiarFiltrosValesCambio() {
            this.filtrosValesCambio = { folio: '', cliente: '', estatus: '' };
            this.cargarValesCambio();
        },

        etiquetaEstatusVale(estatus) {
            const valor = String(estatus || '').toLowerCase();
            if (valor === 'disponible') return 'Disponible';
            if (valor === 'parcial') return 'Parcial';
            if (valor === 'aplicado') return 'Aplicado';
            if (valor === 'cancelado') return 'Cancelado';
            return estatus || 'N/D';
        },

        async buscarCreditoCambioParaCobro() {
            const folio = this.normalizarFolioEscaneado(this.folioCreditoCambio);
            this.folioCreditoCambio = folio;
            if (!folio) {
                this.mensajeCreditoCambio = 'Captura el folio del vale para aplicarlo.';
                this.creditoCambioSeleccionado = null;
                return;
            }

            this.buscandoCreditoCambio = true;
            this.mensajeCreditoCambio = '';
            try {
                const totalSeleccion = Number((this.subtotal - this.descuento) || 0);
                const res = await fetch(`${rutaBuscarCreditoCambioFolio}?folio=${encodeURIComponent(folio)}&total=${encodeURIComponent(totalSeleccion)}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const json = await res.json().catch(() => ({}));
                if (!res.ok) {
                    this.creditoCambioSeleccionado = null;
                    this.mensajeCreditoCambio = json?.message || 'No fue posible consultar el vale de cambio.';
                    return;
                }

                const credito = json?.data || null;
                if (!credito) {
                    this.creditoCambioSeleccionado = null;
                    this.mensajeCreditoCambio = 'No se encontró el vale solicitado.';
                    return;
                }
                if (!credito.pcc_sucursal_valida) {
                    this.creditoCambioSeleccionado = null;
                    this.mensajeCreditoCambio = 'Este vale pertenece a otra sucursal y no puede aplicarse aquí.';
                    return;
                }

                this.creditoCambioSeleccionado = {
                    id: Number(credito.pcc_id || 0),
                    folio: String(credito.pcc_folio || folio),
                    cliente_nombre: String(credito.cliente_nombre || ''),
                    venta_origen_folio: String(credito.venta_origen_folio || ''),
                    saldo_disponible: Number(credito.pcc_saldo_disponible || 0),
                    monto_aplicado: Number(credito.pcc_monto_aplicable || 0),
                };
                this.folioCreditoCambio = this.creditoCambioSeleccionado.folio;
                this.sincronizarPagoConTotalActual();
                if (Number(this.creditoCambioSeleccionado.monto_aplicado || 0) <= 0) {
                    this.mensajeCreditoCambio = 'El vale no tiene saldo aplicable para esta venta.';
                }
            } catch (error) {
                this.creditoCambioSeleccionado = null;
                this.mensajeCreditoCambio = 'Error de conexión al consultar el vale.';
            } finally {
                this.buscandoCreditoCambio = false;
            }
        },

        quitarCreditoCambioSeleccionado() {
            this.creditoCambioSeleccionado = null;
            this.folioCreditoCambio = '';
            this.mensajeCreditoCambio = '';
            this.sincronizarPagoConTotalActual();
        },

        async abrirCambioDesdeVenta(ventaId) {
            this.mostrarModalTickets = false;
            this.mostrarModalCambio = true;
            this.cargandoVentaCambio = true;
            this.mensajeCambio = '';
            try {
                const res = await fetch(`${rutaTicketVentaBase}/${ventaId}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const json = await res.json().catch(() => ({}));
                if (!res.ok) {
                    this.mensajeCambio = json?.message || 'No fue posible cargar la venta.';
                    return;
                }
                const venta = json?.data || null;
                if (!venta) {
                    this.mensajeCambio = 'No se encontró la venta seleccionada.';
                    return;
                }
                this.folioCambioBuscar = venta.psv_folio || '';
                this.ventaCambioPreview = {
                    ...venta,
                    detalle: (venta.detalle || []).map((detalle) => ({
                        ...detalle,
                        devolver_cantidad: 0,
                        condicion: 'reventa',
                    })),
                };
            } catch (error) {
                this.mensajeCambio = 'Error de conexión al consultar la venta.';
            } finally {
                this.cargandoVentaCambio = false;
            }
        },

        async cancelarVentaRegistrada(venta) {
            if (!this.puedeCancelarVenta || !venta?.psv_id) return;
            const motivo = prompt(`Motivo de cancelación para ${venta.psv_folio}:`);
            if (motivo === null) return;
            if (!String(motivo).trim()) {
                alert('Debes capturar un motivo de cancelación.');
                return;
            }

            try {
                const res = await fetch(`${rutaCancelarVentaBase}/${venta.psv_id}/cancelar`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ motivo }),
                });
                const json = await res.json().catch(() => ({}));
                if (!res.ok) {
                    const first = Object.values(json?.errors || {})[0];
                    alert(first ? first[0] : (json?.message || 'No fue posible cancelar la venta.'));
                    return;
                }
                await this.cargarVentasDia();
                alert(json?.message || 'Venta cancelada correctamente.');
            } catch (error) {
                alert('Error de conexión al cancelar la venta.');
            }
        },

        async cargarPedidosPendientes() {
            this.cargandoPedidosPendientes = true;
            this.pedidoMensaje = '';
            try {
                const q = (this.folioPedidoBuscar || '').trim();
                const res = await fetch(`${rutaPedidosPendientes}?q=${encodeURIComponent(q)}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                if (!res.ok) {
                    this.pedidoMensaje = 'No fue posible consultar pedidos pendientes.';
                    this.pedidosPendientes = [];
                    return;
                }
                const json = await res.json();
                this.pedidosPendientes = Array.isArray(json?.data) ? json.data : [];
            } catch (error) {
                this.pedidoMensaje = 'Error de conexión al consultar pedidos pendientes.';
                this.pedidosPendientes = [];
            } finally {
                this.cargandoPedidosPendientes = false;
            }
        },

        async seleccionarPedidoPendiente(pedidoResumen) {
            if (!pedidoResumen?.pdp_folio) return;
            this.folioPedidoBuscar = pedidoResumen.pdp_folio;
            await this.cargarPedidoPorFolio();
        },

        async cargarVentasDia() {
            this.cargandoTickets = true;
            this.resumenCaja = null;
            try {
                const q = (this.filtroTicket || '').trim();
                const res = await fetch(`${rutaVentasDia}?q=${encodeURIComponent(q)}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok) return;
                const json = await res.json();
                this.ventasDelDia = Array.isArray(json?.data) ? json.data : [];
                this.resumenCaja = json?.resumen || null;
                if (this.sesionActiva?.caja_retiro_umbral && this.resumenCaja && this.resumenCaja.umbral_retiro == null) {
                    this.resumenCaja.umbral_retiro = Number(this.sesionActiva.caja_retiro_umbral || 0);
                }
            } finally {
                this.cargandoTickets = false;
            }
        },
        horaCorta(fecha) {
            if (!fecha) return '--:--';
            const d = new Date(String(fecha).replace(' ', 'T'));
            if (Number.isNaN(d.getTime())) return '--:--';
            return d.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
        },
        async abrirResumenCaja() {
            this.tab = 'caja';
            if (!this.sesionActiva) {
                this.mostrarModalCaja = true;
                return;
            }
            await this.cargarVentasDia();
            this.mostrarModalResumenCaja = true;
        },

        // ── Corte de caja ────────────────────────────────────────
        async abrirCorteCaja() {
            if (!this.sesionActiva) {
                this.mostrarModalCaja = true;
                return;
            }
            await this.cargarVentasDia();
            this.corteCajaForm = {
                denominaciones: { 1000: '', 500: '', 200: '', 100: '', 50: '', 20: '', 10: '', 5: '', 2: '', 1: '', '0_50': '' },
                observaciones: '',
            };
            this.mostrarCorteCaja = true;
            this.$nextTick(() => {
                const primero = document.querySelector('.corte-caja__input');
                if (primero) primero.focus();
            });
        },
        cerrarCorteCaja() {
            this.mostrarCorteCaja = false;
        },
        corteSubtotal(denominacion, clave = denominacion) {
            return Number(this.corteCajaForm.denominaciones[clave] || 0) * Number(denominacion);
        },
        corteTotalBilletesYMonedas() {
            const totalBilletes = this.billetesCorte
                .reduce((suma, denom) => suma + this.corteSubtotal(denom), 0);
            const totalMonedas = this.monedasCorte
                .reduce((suma, moneda) => suma + this.corteSubtotal(moneda.valor, moneda.clave), 0);

            return totalBilletes + totalMonedas;
        },
        corteCajeroReporta() {
            return this.corteTotalBilletesYMonedas();
        },
        corteEfectivoEsperado() {
            return Number(this.resumenCaja?.efectivo_disponible ?? 0);
        },
        corteDiferencia() {
            return this.corteCajeroReporta() - this.corteEfectivoEsperado();
        },
        corteDiferenciaEstado() {
            const diferencia = this.corteDiferencia();
            if (Math.abs(diferencia) < 0.005) return 'ok';
            return diferencia > 0 ? 'sobra' : 'falta';
        },
        metodoMonto(clave) {
            const metodo = (this.resumenCaja?.ventas_por_metodo || []).find((m) => m.clave === clave);
            return metodo ? Number(metodo.monto || 0) : 0;
        },
        tiempoSesionTexto() {
            if (!this.sesionActiva?.abierta_at) return '—';
            const inicio = new Date(String(this.sesionActiva.abierta_at).replace(' ', 'T'));
            if (Number.isNaN(inicio.getTime())) return '—';
            const minutosTotales = Math.max(0, Math.floor((Date.now() - inicio.getTime()) / 60000));
            const dias = Math.floor(minutosTotales / 1440);
            const horas = Math.floor((minutosTotales % 1440) / 60);
            const minutos = minutosTotales % 60;
            if (dias > 0) return `${dias} d ${horas} h`;
            return horas > 0 ? `${horas} h ${minutos} min` : `${minutos} min`;
        },
        focusSiguienteCorteInput(event) {
            const focusables = Array.from(document.querySelectorAll('.corte-caja__input'))
                .filter((el) => el.offsetParent !== null);
            const idx = focusables.indexOf(event.target);
            if (idx === -1) return;
            if (idx < focusables.length - 1) {
                const siguiente = focusables[idx + 1];
                siguiente.focus();
                if (typeof siguiente.select === 'function') siguiente.select();
            } else {
                event.target.blur();
            }
        },
        abrirModalAutorizacionCorte() {
            this.corteAutorizaForm = { usr_id: '', password: '' };
            this.corteAutorizaErrores = {};
            this.mostrarModalCorteAutorizacion = true;
        },
        cerrarModalAutorizacionCorte() {
            this.mostrarModalCorteAutorizacion = false;
            this.guardandoCorte = false;
            this.corteAutorizaErrores = {};
        },
        async confirmarCorteCaja() {
            if (this.guardandoCorte) return;
            this.corteAutorizaErrores = {};
            if (!this.corteAutorizaForm.usr_id) {
                this.corteAutorizaErrores.autoriza_usr_id = 'Selecciona el usuario autorizado.';
            }
            if (!this.corteAutorizaForm.password) {
                this.corteAutorizaErrores.autoriza_password = 'Captura la contraseña.';
            }
            if (Object.keys(this.corteAutorizaErrores).length) return;

            this.guardandoCorte = true;
            try {
                const payload = {
                    denominaciones: { ...this.corteCajaForm.denominaciones },
                    observaciones: this.corteCajaForm.observaciones || '',
                    autoriza_usr_id: Number(this.corteAutorizaForm.usr_id || 0),
                    autoriza_password: this.corteAutorizaForm.password || '',
                };

                const res = await fetch(rutaCorteCajaStore, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                });
                const json = await res.json().catch(() => ({}));

                if (!res.ok) {
                    const errors = json?.errors || {};
                    this.corteAutorizaErrores = {
                        autoriza_usr_id: Array.isArray(errors.autoriza_usr_id) ? errors.autoriza_usr_id[0] : undefined,
                        autoriza_password: Array.isArray(errors.autoriza_password) ? errors.autoriza_password[0] : undefined,
                        general: json?.message || (Array.isArray(errors.caja) ? errors.caja[0] : ''),
                    };
                    if (Array.isArray(errors.caja) && !this.corteAutorizaErrores.general) {
                        this.corteAutorizaErrores.general = errors.caja[0];
                    }
                    return;
                }

                this.mostrarModalCorteAutorizacion = false;
                this.mostrarCorteCaja = false;
                this.items = [];
                this.pedidoCargado = null;
                this.cambioActual = null;
                this.descuentoGlobal = 0;
                this.notas = '';
                await this.recargarEstadoCaja();
                if (this.sesionActiva) {
                    await this.cargarVentasDia();
                } else {
                    this.ventasDelDia = [];
                    this.resumenCaja = null;
                    this.mostrarModalCaja = true;
                }
                if (json?.data?.pco_id) {
                    await this.imprimirTrabajoAgente(
                        `${rutaTicketCorteCajaEscposBase}/${json.data.pco_id}/ticket-escpos`,
                        `${rutaTicketCorteCajaBase}/${json.data.pco_id}/ticket`,
                        `corte-caja-${json?.data?.pco_id || Date.now()}`
                    );
                } else if (json?.data?.ticket_url) {
                    await this.imprimirTicketDesdeUrl(
                        json.data.ticket_url,
                        `corte-caja-${json?.data?.pco_id || Date.now()}.pdf`
                    );
                }
                alert(json?.message || 'Corte de caja registrado correctamente.');
            } finally {
                this.guardandoCorte = false;
            }
        },

        async abrirTicketVenta(ventaId) {
            if (!ventaId) return;
            await this.imprimirTrabajoAgente(
                `${rutaTicketVentaEscposBase}/${ventaId}/ticket-escpos`,
                `${rutaTicketVentaBase}/${ventaId}/ticket`,
                `ticket-venta-${ventaId}`
            );
        },

        async abrirTicketCreditoCambio(creditoId) {
            if (!creditoId) return;
            await this.imprimirTrabajoAgente(
                `${rutaTicketCreditoCambioEscposBase}/${creditoId}/ticket-escpos`,
                `${rutaTicketCreditoCambioBase}/${creditoId}/ticket`,
                `ticket-credito-cambio-${creditoId}`
            );
        },

        cargarConfiguracionAgenteImpresion() {
            const habilitadoGuardado = window.localStorage.getItem(storageAgenteImpresionHabilitado);
            const urlGuardada = window.localStorage.getItem(storageAgenteImpresionUrl);
            this.agenteImpresionHabilitado = habilitadoGuardado === '1';
            this.agenteImpresionUrl = (urlGuardada || agenteImpresionUrlDefault).trim() || agenteImpresionUrlDefault;
            this.actualizarEstadoAgenteImpresion();
        },

        guardarConfiguracionAgenteImpresion() {
            window.localStorage.setItem(storageAgenteImpresionHabilitado, this.agenteImpresionHabilitado ? '1' : '0');
            window.localStorage.setItem(storageAgenteImpresionUrl, this.agenteImpresionUrl || agenteImpresionUrlDefault);
            this.actualizarEstadoAgenteImpresion();
        },

        actualizarEstadoAgenteImpresion() {
            if (!this.agenteImpresionHabilitado) {
                this.impresionEstado = 'Navegador';
                return;
            }

            this.impresionEstado = `Agente local - ${this.agenteImpresionUrl}`;
        },

        configurarAgenteImpresion() {
            const urlCapturada = window.prompt(
                'URL del agente local de impresion en esta computadora:',
                this.agenteImpresionUrl || agenteImpresionUrlDefault
            );

            if (urlCapturada === null) return;

            const urlNormalizada = String(urlCapturada || '').trim().replace(/\/+$/, '');
            if (!urlNormalizada) {
                this.agenteImpresionHabilitado = false;
                this.agenteImpresionUrl = agenteImpresionUrlDefault;
                this.guardarConfiguracionAgenteImpresion();
                alert('La impresion automatica quedo deshabilitada para esta computadora.');
                return;
            }

            this.agenteImpresionUrl = urlNormalizada;
            this.agenteImpresionHabilitado = true;
            this.guardarConfiguracionAgenteImpresion();
            alert('Agente de impresion configurado. Los siguientes tickets intentaran imprimirse automaticamente en esta computadora.');
        },

        async imprimirTicketDesdeUrl(ticketUrl, nombreArchivo = 'ticket.pdf') {
            if (!ticketUrl) return;

            const destino = String(ticketUrl);
            if (!this.agenteImpresionHabilitado) {
                window.open(destino, '_blank');
                return;
            }

            try {
                this.impresionEstado = 'Preparando ticket...';
                const res = await fetch(destino, {
                    headers: {
                        'Accept': 'application/pdf',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (!res.ok) {
                    throw new Error('No fue posible descargar el ticket PDF.');
                }

                const pdfBlob = await res.blob();
                const pdfBase64 = await this.blobToBase64(pdfBlob);

                this.impresionEstado = 'Enviando al agente...';
                const printRes = await fetch(`${this.agenteImpresionUrl.replace(/\/+$/, '')}/api/print-jobs`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        source: 'laisuriana-pos',
                        content_type: 'application/pdf',
                        document_name: nombreArchivo,
                        document_base64: pdfBase64,
                    }),
                });

                const printJson = await printRes.json().catch(() => ({}));
                if (!printRes.ok) {
                    throw new Error(printJson?.message || 'El agente local rechazo la impresion.');
                }

                this.impresionEstado = printJson?.message || 'Impreso por agente local';
            } catch (error) {
                console.error(error);
                this.impresionEstado = 'Fallback a navegador';
                window.open(destino, '_blank');
            }
        },

        async imprimirTrabajoAgente(payloadUrl, fallbackUrl, nombreBase = 'ticket') {
            if (!payloadUrl) {
                if (fallbackUrl) {
                    await this.imprimirTicketDesdeUrl(fallbackUrl, `${nombreBase}.pdf`);
                }
                return;
            }

            if (!this.agenteImpresionHabilitado) {
                if (fallbackUrl) {
                    window.open(String(fallbackUrl), '_blank');
                }
                return;
            }

            try {
                this.impresionEstado = 'Preparando ticket termico...';
                const res = await fetch(String(payloadUrl), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                const json = await res.json().catch(() => ({}));
                if (!res.ok || !json?.data?.document_base64) {
                    throw new Error(json?.message || 'No fue posible preparar el ticket termico.');
                }

                this.impresionEstado = 'Enviando RAW al agente...';
                const printRes = await fetch(`${this.agenteImpresionUrl.replace(/\/+$/, '')}/api/print-jobs`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(json.data),
                });

                const printJson = await printRes.json().catch(() => ({}));
                if (!printRes.ok) {
                    throw new Error(printJson?.message || 'El agente local rechazo la impresion RAW.');
                }

                this.impresionEstado = printJson?.message || 'Impreso por agente local';
            } catch (error) {
                console.error(error);
                this.impresionEstado = 'Fallback a navegador';
                if (fallbackUrl) {
                    await this.imprimirTicketDesdeUrl(String(fallbackUrl), `${nombreBase}.pdf`);
                }
            }
        },

        blobToBase64(blob) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onloadend = () => {
                    const raw = String(reader.result || '');
                    const base64 = raw.includes(',') ? raw.split(',', 2)[1] : raw;
                    resolve(base64);
                };
                reader.onerror = () => reject(new Error('No fue posible leer el PDF del ticket.'));
                reader.readAsDataURL(blob);
            });
        },

        etiquetaOperacion(tipo) {
            return String(tipo || 'venta') === 'cambio' ? 'Cambio' : 'Venta';
        },
        etiquetaMetodoPago(metodo) {
            const key = String(metodo || '').toLowerCase();
            if (key === 'efectivo') return 'Efectivo';
            if (key === 'tarjeta') return 'Tarjeta';
            if (key === 'mixto') return 'Mixto';
            if (key === 'monedero_electronico') return 'Monedero electrónico';
            if (key === 'sin_pago') return 'Sin pago';
            return key ? key.replace(/_/g, ' ') : 'N/A';
        },

        etiquetaEstatus(estatus) {
            return String(estatus || '') === 'cancelada' ? 'Cancelada' : 'Cobrada';
        },

        cerrarModalPedido() {
            this.mostrarModalPedido = false;
            this.pedidoMensaje = '';
            this.pedidoPreview = null;
            this.pedidosPendientes = [];
        },

        async cargarPedidoPorFolio() {
            const folio = this.normalizarFolioEscaneado(this.folioPedidoBuscar);
            this.folioPedidoBuscar = folio;
            if (!folio) {
                this.pedidoMensaje = 'Captura un folio para continuar.';
                return;
            }

            try {
                const res = await fetch(`${rutaBuscarPedidoFolio}?folio=${encodeURIComponent(folio)}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const json = await res.json();
                if (!res.ok) {
                    this.pedidoMensaje = json?.message || 'No fue posible cargar el pedido.';
                    this.pedidoPreview = null;
                    return;
                }

                const pedido = json?.data;
                this.pedidoPreview = pedido;

                if (this.items.length > 0) {
                    this.pedidoPendienteReemplazo = {
                        pedido,
                        cerrarModalPedido: true,
                    };
                    this.mostrarModalConfirmacionPedido = true;
                    return;
                }

                this.aplicarPedidoEnTicket(pedido, true);
            } catch (error) {
                this.pedidoMensaje = 'Error de conexión al buscar el pedido.';
            }
        },

        aplicarPedidoEnTicket(pedido, cerrarModalPedido = false) {
            this.cambioActual = null;
            this.items = (pedido.detalle || []).map((d) => ({
                pskId: d.ppd_psk_id,
                origen: 'pedido',
                pedidoDetalleId: Number(d.ppd_id || 0),
                usrId: d.ppd_usr_id ? Number(d.ppd_usr_id) : null,
                vendedor: d.capturista || pedido?.almacen || this.nombreFallbackVendedor(),
                nombre: d.nombre || d.sku,
                sku: d.sku,
                codigoBarras: '',
                precio: Number(d.precio || 0),
                cantidad: Number(d.cantidad || 1),
                permiteDecimal: Boolean(d.permite_decimal),
                descuentoTipo: d.descuento_tipo || 'ninguno',
                descuento: Number(d.descuento_valor || 0),
            }));
            this.notas = pedido.pdp_observaciones || '';
            this.clienteSeleccionado = pedido.cliente || null;
            this.queryCliente = pedido.cliente?.nombre || '';
            this.descuentoGlobal = 0;
            this.pedidoCargado = {
                pdp_id: pedido.pdp_id,
                pdp_folio: pedido.pdp_folio,
                pdp_alm_id: pedido.pdp_alm_id,
                almacen: pedido.almacen,
            };
            this.ventaAlmacenId = pedido?.pdp_alm_id ? String(pedido.pdp_alm_id) : '';
            this.ventaAlmacenNombre = pedido?.almacen || this.obtenerNombreAlmacen(this.ventaAlmacenId);

            if (cerrarModalPedido) {
                this.mostrarModalPedido = false;
                this.pedidoMensaje = '';
            }
            this.queryProducto = '';
            this.cerrarSugerencias();
            this.$nextTick(() => this.$refs.productoInput?.focus());
        },

        confirmarReemplazoPedido() {
            const payload = this.pedidoPendienteReemplazo;
            if (!payload?.pedido) {
                this.mostrarModalConfirmacionPedido = false;
                return;
            }

            this.aplicarPedidoEnTicket(payload.pedido, !!payload.cerrarModalPedido);
            this.pedidoPendienteReemplazo = null;
            this.mostrarModalConfirmacionPedido = false;
        },

        cancelarReemplazoPedido() {
            this.pedidoPendienteReemplazo = null;
            this.mostrarModalConfirmacionPedido = false;
        },

        navegarSugerencia(dir) {
            if (!this.mostrarSugerencias || this.sugerenciasProducto.length === 0) return;
            const max = this.sugerenciasProducto.length - 1;
            this.sugerenciaActivaIndex = Math.min(max, Math.max(0, this.sugerenciaActivaIndex + dir));
            this.$nextTick(() => this.scrollSugerenciaActivaVisible());
        },

        seleccionarSugerencia(idx) {
            if (idx < 0 || idx >= this.sugerenciasProducto.length) return;
            const elegido = this.sugerenciasProducto[idx];
            this.cerrarSugerencias();
            this.agregarDesdeBusqueda(elegido);
        },

        cerrarSugerencias() {
            this.mostrarSugerencias = false;
            this.sugerenciaActivaIndex = -1;
        },

        scrollSugerenciaActivaVisible() {
            const wrap = this.$refs.sugerenciasWrap;
            if (!wrap || this.sugerenciaActivaIndex < 0) return;

            const item = wrap.querySelector(`[data-sugerencia-idx="${this.sugerenciaActivaIndex}"]`);
            if (!item) return;

            const wrapTop = wrap.scrollTop;
            const wrapBottom = wrapTop + wrap.clientHeight;
            const itemTop = item.offsetTop;
            const itemBottom = itemTop + item.offsetHeight;

            if (itemTop < wrapTop) {
                wrap.scrollTop = itemTop;
                return;
            }

            if (itemBottom > wrapBottom) {
                wrap.scrollTop = itemBottom - wrap.clientHeight;
            }
        },

        async agregarDesdeBusqueda(item) {
            if (!item?.psk_id) return;
            const resolucion = await this.resolverAlmacenParaProducto(item);
            if (!resolucion?.ok) return;

            if (!this.ventaAlmacenId && resolucion.requiereSeleccion) {
                this.abrirSelectorAlmacenVenta(
                    resolucion.almacenes || [],
                    'Selecciona el almacén del primer producto',
                    'Este producto puede salir de varios almacenes. Elige cuál usará este ticket desde la primera marcación.',
                    'primer_producto',
                    item
                );
                return;
            }

            if (!this.ventaAlmacenId && Number(resolucion.almacen_id || 0) > 0) {
                this.ventaAlmacenId = String(resolucion.almacen_id);
                this.ventaAlmacenNombre = resolucion.almacen || this.obtenerNombreAlmacen(this.ventaAlmacenId);
            }

            const vendedor = this.resolverVendedorManual();
            this.agregarItem({
                pskId: item.psk_id,
                origen: 'manual',
                pedidoDetalleId: null,
                usrId: vendedor.usrId,
                vendedor: vendedor.nombre,
                nombre: item.psk_nombre || item.producto?.prd_nombre || item.psk_codigo,
                sku: item.psk_codigo,
                codigoBarras: item.psk_codigo_barras || item.producto?.prd_codigo_barras || '',
                precio: parseFloat(item.psk_precio || 0),
                permiteDecimal: Boolean(item.permite_decimal),
                cantidad: 1,
                descuentoTipo: 'ninguno',
                descuento: 0,
            });
            this.queryProducto = '';
            this.variantesPendientes = [];
            this.mostrarSelectorVariantes = false;
            this.$nextTick(() => this.$refs.productoInput?.focus());
        },

        resolverVendedorManual() {
            const vendedorId = Number(this.vendedorSeleccionadoId || 0);
            const vendedor = vendedorId > 0
                ? this.vendedores.find((v) => Number(v.usr_id) === vendedorId)
                : null;

            if (vendedor) {
                return {
                    usrId: Number(vendedor.usr_id),
                    nombre: vendedor.usr_usuario || vendedor.usr_nombre || 'Sin vendedor',
                };
            }

            return {
                usrId: null,
                nombre: this.nombreFallbackVendedor(),
            };
        },
        nombreFallbackVendedor() {
            return this.ventaAlmacenNombre
                || this.obtenerNombreAlmacen(this.ventaAlmacenId)
                || this.sesionActiva?.caja_almacen
                || 'Sin vendedor';
        },

        cerrarSelectorVariantes() {
            this.variantesPendientes = [];
            this.mostrarSelectorVariantes = false;
            this.$nextTick(() => this.$refs.productoInput?.focus());
        },

        agregarItem(producto) {
            const existe = this.items.find(i =>
                (i.pskId === producto.pskId || i.sku === producto.sku)
                && Number(i.usrId || 0) === Number(producto.usrId || 0)
                && String(i.origen || 'manual') === String(producto.origen || 'manual')
                && Number(i.pedidoDetalleId || 0) === Number(producto.pedidoDetalleId || 0)
                && String(i.descuentoTipo || 'ninguno') === 'ninguno'
            );
            if (existe) {
                existe.cantidad = Number((Number(existe.cantidad || 0) + (existe.permiteDecimal ? 0.01 : 1)).toFixed(2));
                this.recalcular();
            } else {
                this.items.push({ ...producto });
            }
        },
        qtyStep(item) {
            return item?.permiteDecimal ? 0.01 : 1;
        },
        sanitizeQty(val, item) {
            const num = Number(String(val ?? '').replace(',', '.'));
            if (!Number.isFinite(num)) return this.qtyStep(item);
            if (item?.permiteDecimal) return Math.max(0.01, Number(num.toFixed(2)));
            return Math.max(1, Math.round(num));
        },
        tieneDescuentosPorProducto() {
            return this.items.some((item) => Number(item.descuento || 0) > 0 && String(item.descuentoTipo || 'ninguno') !== 'ninguno');
        },
        abrirDescuentoItem(idx) {
            if (this.descuentoGlobal > 0) {
                AppUI.showMessage('Aviso', 'Quita el descuento global para usar descuento por producto.', 'warning');
                return;
            }
            const item = this.items[idx];
            if (!item) return;
            this.descuentoItemIndex = idx;
            this.descuentoItemNombre = item.nombre || item.sku || 'Producto';
            this.descuentoItemTipo = item.descuentoTipo === 'importe' ? 'importe' : 'porcentaje';
            this.descuentoItemValor = String(Number(item.descuento || 0));
            this.descuentoItemCantidad = String(Number(item.cantidad || 0));
            this.descuentoItemPermiteDecimal = Boolean(item.permiteDecimal);
            this.mostrarModalDescuentoItem = true;
            this.$nextTick(() => this.$refs.descuentoItemInput?.focus());
        },
        cerrarDescuentoItem() {
            this.mostrarModalDescuentoItem = false;
            this.descuentoItemIndex = -1;
            this.descuentoItemValor = '';
            this.descuentoItemNombre = '';
            this.descuentoItemTipo = 'porcentaje';
            this.descuentoItemCantidad = '';
            this.descuentoItemPermiteDecimal = false;
        },
        guardarDescuentoItemPos() {
            if (this.descuentoItemIndex < 0 || !this.items[this.descuentoItemIndex]) {
                this.cerrarDescuentoItem();
                return;
            }
            const item = this.items[this.descuentoItemIndex];
            const tipo = this.descuentoItemTipo === 'importe' ? 'importe' : 'porcentaje';
            const valor = Number(String(this.descuentoItemValor || 0).replace(',', '.'));
            const cantidadAplicada = this.sanitizeQty(this.descuentoItemCantidad || item.cantidad, item);
            const cantidadActual = Number(item.cantidad || 0);
            const subtotalAplicado = Number((Number(item.precio || 0) * cantidadAplicada).toFixed(2));

            if (Number.isNaN(valor) || valor < 0) {
                AppUI.showMessage('Aviso', 'Captura un descuento válido.', 'warning');
                return;
            }
            if (tipo === 'porcentaje' && valor > 100) {
                AppUI.showMessage('Aviso', 'El porcentaje no puede ser mayor a 100.', 'warning');
                return;
            }
            if (cantidadAplicada <= 0 || cantidadAplicada > cantidadActual) {
                AppUI.showMessage('Aviso', 'La cantidad a descontar no es válida.', 'warning');
                return;
            }
            if (tipo === 'importe' && valor > subtotalAplicado) {
                AppUI.showMessage('Aviso', 'El descuento fijo no puede ser mayor al subtotal aplicado.', 'warning');
                return;
            }
            if (cantidadAplicada === cantidadActual) {
                item.descuentoTipo = valor > 0 ? tipo : 'ninguno';
                item.descuento = valor > 0 ? valor : 0;
                this.recalcular();
                this.cerrarDescuentoItem();
                return;
            }

            const restante = this.sanitizeQty(cantidadActual - cantidadAplicada, item);
            if (restante <= 0 || restante >= cantidadActual) {
                AppUI.showMessage('Aviso', 'La cantidad restante no es válida.', 'warning');
                return;
            }

            const nuevaLinea = {
                ...item,
                cantidad: cantidadAplicada,
                descuentoTipo: valor > 0 ? tipo : 'ninguno',
                descuento: valor > 0 ? valor : 0,
            };

            item.cantidad = restante;
            item.descuentoTipo = 'ninguno';
            item.descuento = 0;
            this.items.splice(this.descuentoItemIndex + 1, 0, nuevaLinea);
            this.recalcular();
            this.cerrarDescuentoItem();
        },
        guardarDescuentoItem() {
            if (this.descuentoItemIndex < 0 || !this.items[this.descuentoItemIndex]) {
                this.cerrarDescuentoItem();
                return;
            }
            const valor = Math.min(100, Math.max(0, Number(this.descuentoItemValor || 0)));
            if (Number.isNaN(valor)) {
                AppUI.showMessage('Aviso', 'Captura un porcentaje válido.', 'warning');
                return;
            }
            this.items[this.descuentoItemIndex].descuentoTipo = valor > 0 ? 'porcentaje' : 'ninguno';
            this.items[this.descuentoItemIndex].descuento = valor;
            this.recalcular();
            this.cerrarDescuentoItem();
        },
        limpiarDescuentoItem() {
            if (this.descuentoItemIndex >= 0 && this.items[this.descuentoItemIndex]) {
                this.items[this.descuentoItemIndex].descuentoTipo = 'ninguno';
                this.items[this.descuentoItemIndex].descuento = 0;
                this.recalcular();
            }
            this.cerrarDescuentoItem();
        },

        quitarItem(idx)  { this.items.splice(idx, 1); },

        incQty(idx) {
            this.items[idx].cantidad = Number((Number(this.items[idx].cantidad || 0) + this.qtyStep(this.items[idx])).toFixed(2));
            this.recalcular();
        },

        decQty(idx) {
            if (Number(this.items[idx].cantidad || 0) > this.qtyStep(this.items[idx])) {
                this.items[idx].cantidad = Number((Number(this.items[idx].cantidad || 0) - this.qtyStep(this.items[idx])).toFixed(2));
                this.recalcular();
            }
        },

        itemImporte(item) {
            const base = item.precio * item.cantidad;
            if (Number(item.descuento || 0) <= 0 || String(item.descuentoTipo || 'ninguno') === 'ninguno') {
                return base;
            }
            if (String(item.descuentoTipo) === 'importe') {
                return Math.max(0, base - Number(item.descuento || 0));
            }
            return base * (1 - Number(item.descuento || 0) / 100);
        },

        recalcular() { this.items = [...this.items]; },

        limpiarTicket() {
            if (!this.items.length) return;
            if (confirm('¿Limpiar el ticket actual?')) {
                this.items = [];
                this.notas = '';
                this.pedidoCargado = null;
                this.cambioActual = null;
                this.ventaAlmacenId = '';
                this.ventaAlmacenNombre = '';
                this.clienteSeleccionado = null;
                this.queryCliente = '';
                this.descuentoGlobal = 0;
                this.folioCreditoCambio = '';
                this.creditoCambioSeleccionado = null;
                this.mensajeCreditoCambio = '';
                this.aplicarAlmacenPorSesion();
                this.$refs.productoInput?.focus();
            }
        },

        // ── Ventas en espera ─────────────────────────────────────
        nuevaVenta() {
            this.items               = [];
            this.notas               = '';
            this.clienteSeleccionado = null;
            this.descuentoGlobal     = 0;
            this.pedidoCargado       = null;
            this.cambioActual        = null;
            this.folioCreditoCambio  = '';
            this.creditoCambioSeleccionado = null;
            this.mensajeCreditoCambio = '';
            this.ventaAlmacenId      = '';
            this.ventaAlmacenNombre  = '';
            this.aplicarAlmacenPorSesion();
            this.$nextTick(() => this.$refs.productoInput?.focus());
        },

        enviarEspera() {
            this.mostrarModalPedido = true;
            this.pedidoMensaje = '';
            this.pedidoPreview = null;
            this.cargarPedidosPendientes();
        },

        restaurarVenta(idx) {
            const v = this.ventasEspera[idx];
            this.items               = v.items;
            this.notas               = v.notas;
            this.clienteSeleccionado = v.cliente;
            this.descuentoGlobal     = Number(v.descuentoGlobal || v.cliente?.descuento_default || 0);
            this.ventasEspera.splice(idx, 1);
        },

        // ── Cash / drawer ─────────────────────────────────────────
        async retiroCaja()     {
            if (!this.puedeRegistrarRetiroCaja) {
                this.abrirModalAviso('Sin permiso', 'Tu usuario no tiene permiso para registrar retiros de caja.');
                return;
            }
            if (!this.sesionActiva) {
                this.mostrarModalCaja = true;
                return;
            }
            if (!this.resumenCaja) {
                await this.cargarVentasDia();
            }
            this.movimientoCajaTipo = 'retiro';
            this.movimientoCajaPaso = 1;
            this.movimientoCajaErrores = {};
            this.movimientoCajaForm = {
                monto: '',
                denominaciones: this.denominacionesVaciasRetiro(),
                categoria: '',
                referencia: '',
                motivo: '',
                autoriza_usr_id: '',
                autoriza_password: '',
            };
            this.mostrarSugerenciasCategoriaGasto = false;
            this.mostrarModalMovimientoCaja = true;
            this.$nextTick(() => document.querySelector('.cash-withdrawal-row__input')?.focus());
        },
        async gastoCaja()      {
            if (!this.puedeRegistrarGastoCaja) {
                this.abrirModalAviso('Sin permiso', 'Tu usuario no tiene permiso para registrar gastos de caja.');
                return;
            }
            if (!this.sesionActiva) {
                this.mostrarModalCaja = true;
                return;
            }
            if (!this.resumenCaja) {
                await this.cargarVentasDia();
            }
            this.movimientoCajaTipo = 'gasto';
            this.movimientoCajaPaso = 1;
            this.movimientoCajaErrores = {};
            this.movimientoCajaForm = {
                monto: '',
                denominaciones: this.denominacionesVaciasRetiro(),
                categoria: '',
                referencia: '',
                motivo: '',
                autoriza_usr_id: '',
                autoriza_password: '',
            };
            this.mostrarSugerenciasCategoriaGasto = false;
            this.mostrarModalMovimientoCaja = true;
        },
        cerrarModalMovimientoCaja() {
            this.mostrarModalMovimientoCaja = false;
            this.movimientoCajaPaso = 1;
            this.guardandoMovimientoCaja = false;
            this.movimientoCajaErrores = {};
            this.movimientoCajaForm = {
                monto: '',
                denominaciones: this.denominacionesVaciasRetiro(),
                categoria: '',
                referencia: '',
                motivo: '',
                autoriza_usr_id: '',
                autoriza_password: '',
            };
            this.mostrarSugerenciasCategoriaGasto = false;
        },
        seleccionarCategoriaGasto(categoria) {
            this.movimientoCajaForm.categoria = String(categoria || '');
            this.mostrarSugerenciasCategoriaGasto = false;
        },
        denominacionesVaciasRetiro() {
            return { 1000: '', 500: '', 200: '', 100: '', 50: '', 20: '', 10: '', 5: '', 2: '', 1: '', '0_50': '' };
        },
        subtotalDenominacionRetiro(denominacion, clave = denominacion) {
            const cantidad = Math.max(0, Number(this.movimientoCajaForm.denominaciones?.[clave] || 0));
            return cantidad * Number(denominacion);
        },
        totalDenominacionesRetiro() {
            const totalBilletes = this.billetesCorte.reduce(
                (total, denominacion) => total + this.subtotalDenominacionRetiro(denominacion),
                0
            );
            const totalMonedas = this.monedasCorte.reduce(
                (total, moneda) => total + this.subtotalDenominacionRetiro(moneda.valor, moneda.clave),
                0
            );

            return Math.round((totalBilletes + totalMonedas) * 100) / 100;
        },
        denominacionesConPiezasRetiro() {
            const detalle = [];
            this.billetesCorte.forEach((denominacion) => {
                const piezas = Math.max(0, Number(this.movimientoCajaForm.denominaciones?.[denominacion] || 0));
                if (piezas > 0) {
                    detalle.push({ etiqueta: this.fmt(denominacion), piezas, importe: piezas * Number(denominacion) });
                }
            });
            this.monedasCorte.forEach((moneda) => {
                const piezas = Math.max(0, Number(this.movimientoCajaForm.denominaciones?.[moneda.clave] || 0));
                if (piezas > 0) {
                    detalle.push({ etiqueta: moneda.etiqueta, piezas, importe: piezas * Number(moneda.valor) });
                }
            });

            return detalle;
        },
        piezasTotalesRetiro() {
            return this.denominacionesConPiezasRetiro().reduce((total, detalle) => total + detalle.piezas, 0);
        },
        efectivoRestanteRetiro() {
            const disponible = Number(this.resumenCaja?.efectivo_disponible ?? 0);
            return Math.round((disponible - this.totalDenominacionesRetiro()) * 100) / 100;
        },
        focusSiguienteRetiroInput(event) {
            const focusables = Array.from(document.querySelectorAll('.cash-withdrawal-row__input'))
                .filter((el) => el.offsetParent !== null);
            const idx = focusables.indexOf(event.target);
            if (idx === -1) return;
            if (idx < focusables.length - 1) {
                const siguiente = focusables[idx + 1];
                siguiente.focus();
                if (typeof siguiente.select === 'function') siguiente.select();
            } else {
                event.target.blur();
            }
        },
        sincronizarMontoRetiro() {
            this.movimientoCajaForm.monto = this.totalDenominacionesRetiro();
            delete this.movimientoCajaErrores.denominaciones;
            delete this.movimientoCajaErrores.monto;
        },
        avanzarRetiroAConfirmacion() {
            const total = this.totalDenominacionesRetiro();
            const disponible = Number(this.resumenCaja?.efectivo_disponible ?? 0);

            delete this.movimientoCajaErrores.denominaciones;
            delete this.movimientoCajaErrores.monto;

            if (total <= 0) {
                this.movimientoCajaErrores.denominaciones = 'Captura al menos una pieza para continuar.';
                return;
            }
            if (total > disponible) {
                this.movimientoCajaErrores.monto = 'El retiro no puede superar el efectivo disponible en caja.';
                return;
            }

            this.sincronizarMontoRetiro();
            this.movimientoCajaPaso = 2;
            this.$nextTick(() => this.$refs.retiroReferencia?.focus());
        },
        async guardarMovimientoCaja() {
            if (this.guardandoMovimientoCaja) return;
            this.movimientoCajaErrores = {};
            const payload = {
                tipo: this.movimientoCajaTipo,
                monto: Number(this.movimientoCajaForm.monto || 0),
                denominaciones: this.movimientoCajaTipo === 'retiro'
                    ? { ...this.movimientoCajaForm.denominaciones }
                    : null,
                categoria: this.movimientoCajaTipo === 'gasto' ? (this.movimientoCajaForm.categoria || '') : null,
                referencia: this.movimientoCajaTipo === 'retiro' ? (this.movimientoCajaForm.referencia || '') : null,
                motivo: this.movimientoCajaForm.motivo || '',
                autoriza_usr_id: this.movimientoCajaTipo === 'retiro' ? Number(this.movimientoCajaForm.autoriza_usr_id || 0) : null,
                autoriza_password: this.movimientoCajaTipo === 'retiro' ? (this.movimientoCajaForm.autoriza_password || '') : null,
            };

            this.guardandoMovimientoCaja = true;
            try {
                const res = await fetch(this.movimientoCajaTipo === 'retiro' ? rutaRetiroCajaStore : rutaGastoCajaStore, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                });

                const json = await res.json().catch(() => ({}));
                if (!res.ok) {
                    const errors = json?.errors || {};
                    this.movimientoCajaErrores = Object.fromEntries(
                        Object.entries(errors).map(([key, value]) => [key, Array.isArray(value) ? value[0] : value])
                    );
                    if (!Object.keys(this.movimientoCajaErrores).length) {
                        this.movimientoCajaErrores.general = json?.message || 'Revisa la información capturada e intenta nuevamente.';
                    }
                    if (this.movimientoCajaTipo === 'retiro' && (this.movimientoCajaErrores.denominaciones || this.movimientoCajaErrores.monto)) {
                        this.movimientoCajaPaso = 1;
                    }
                    return;
                }

                const ticketUrl = json?.data?.ticket_url || '';
                const categoriaRegistrada = String(this.movimientoCajaForm.categoria || '').trim();
                if (this.movimientoCajaTipo === 'gasto' && categoriaRegistrada) {
                    const yaExiste = this.categoriasGastoSugeridas.some(
                        (categoria) => String(categoria || '').trim().toLowerCase() === categoriaRegistrada.toLowerCase()
                    );
                    if (!yaExiste) {
                        this.categoriasGastoSugeridas.unshift(categoriaRegistrada);
                    }
                }
                this.cerrarModalMovimientoCaja();
                await this.cargarVentasDia();
                if (ticketUrl) {
                    await this.imprimirTicketDesdeUrl(
                        ticketUrl,
                        `${this.movimientoCajaTipo || 'movimiento-caja'}-${Date.now()}.pdf`
                    );
                }
            } catch (error) {
                this.abrirModalAviso('Sin conexión', 'No fue posible registrar el movimiento de caja en este momento.');
            } finally {
                this.guardandoMovimientoCaja = false;
            }
        },
        devolucion()     {
            if (!this.puedeRegistrarCambio) {
                alert('Tu usuario no tiene permiso para registrar cambios.');
                return;
            }
            this.mostrarModalCambio = true;
            this.mensajeCambio = '';
            this.ventaCambioPreview = null;
            this.folioCambioBuscar = '';
        },
        corteCaja()      { this.abrirCorteCaja(); },
        async abandonarCaja()  {
            if (!this.sesionActiva) {
                window.location.href = '{{ route('desktop.dashboard') }}';
                return;
            }
            const res = await fetch('{{ route('pos.caja.abandonar') }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (!res.ok) {
                alert('No fue posible salir de la caja.');
                return;
            }
            window.location.href = '{{ route('desktop.dashboard') }}';
        },
        salir()          { window.location.href = '{{ route('desktop.dashboard') }}'; },
        abrirModalAviso(titulo, mensaje) {
            this.modalAvisoTitulo = titulo || 'Aviso';
            this.modalAvisoMensaje = mensaje || '';
            this.mostrarModalAviso = true;
        },
        cerrarModalAviso() {
            this.mostrarModalAviso = false;
            this.modalAvisoTitulo = '';
            this.modalAvisoMensaje = '';
        },
        async cobrar()         {
            if (!this.items.length) return;
            if (!this.sesionActiva) {
                this.mostrarModalCaja = true;
                return;
            }
            if (this.cambioActivo && this.cambioInvalidoMenorValor) {
                this.abrirModalAviso(
                    'Completa la selección del cambio',
                    'Para continuar, agrega artículos por un valor igual o mayor al saldo disponible de este cambio.'
                );
                return;
            }
            if (!this.ventaAlmacenId) {
                this.abrirSelectorAlmacenVenta(
                    this.almacenesVenta,
                    'Selecciona el almacén del ticket',
                    'Este ticket no tiene un almacén definido. Elige uno para continuar con el cobro.',
                    'ticket'
                );
                return;
            }
            if (this.cambioActivo && Number(this.total || 0) === 0) {
                this.confirmarCobro(true);
                return;
            }
            this.inicializarPagoModal();
            this.mostrarModalPago = true;
            this.enfocarPagoCon();
        },
        enfocarPagoCon() {
            const intentar = () => {
                const input = this.$refs.pagoConInput;
                if (!input || input.offsetParent === null) return false;
                input.focus({ preventScroll: true });
                input.select();
                return document.activeElement === input;
            };

            this.$nextTick(() => {
                if (intentar()) return;
                requestAnimationFrame(() => {
                    if (intentar()) return;
                    [40, 100, 180].forEach((ms) => {
                        setTimeout(() => { intentar(); }, ms);
                    });
                });
            });
        },
        inicializarPagoModal() {
            this.tipoPagoSeleccionado = 'efectivo';
            this.pagoReferencia = '';
            this.folioCreditoCambio = '';
            this.mostrarCapturaVale = false;
            this.creditoCambioSeleccionado = null;
            this.mensajeCreditoCambio = '';
            const total = this.normalizarMonto(this.total || 0);
            this.pagoEfectivoRecibido = total;
            this.pagoLineas = [{ metodo: 'efectivo', monto: total, recibido: total }];
        },
        cerrarModalPago() {
            this.mostrarModalPago = false;
        },
        seleccionarTipoPago(tipo) {
            this.tipoPagoSeleccionado = tipo;
            if (tipo === 'mixto') {
                if (!Array.isArray(this.pagoLineas) || this.pagoLineas.length === 0) {
                    this.pagoLineas = [
                        { metodo: 'efectivo', monto: Number(this.total || 0), recibido: Number(this.total || 0) },
                    ];
                }
                return;
            }
            if (tipo === 'efectivo') {
                this.pagoEfectivoRecibido = this.normalizarMonto(this.total || 0);
                this.enfocarPagoCon();
            }
            const total = this.normalizarMonto(this.total || 0);
            this.pagoLineas = [{ metodo: tipo, monto: total, recibido: total }];
        },
        aplicarPagoEfectivo() {
            const recibido = this.normalizarMonto(this.pagoEfectivoRecibido || 0);
            const monto = this.normalizarMonto(this.total || 0);
            this.pagoLineas = [{ metodo: 'efectivo', monto, recibido }];
        },
        seleccionarSugerenciaPago(monto) {
            this.pagoEfectivoRecibido = this.normalizarMonto(monto || 0);
            this.aplicarPagoEfectivo();
            this.enfocarPagoCon();
        },
        normalizarMonto(valor) {
            return this.centavosAMonto(this.montoACentavos(valor));
        },
        montoACentavos(valor) {
            return Math.max(0, Math.round(Number(valor || 0) * 100));
        },
        centavosAMonto(centavos) {
            return Number((Math.max(0, Number(centavos || 0)) / 100).toFixed(2));
        },
        siguienteMultiploPago(totalCentavos, multiploPesos) {
            if (multiploPesos <= 0) return 0;

            const totalPesosEnteros = Math.ceil(totalCentavos / 100);
            let sugeridoPesos = Math.ceil(totalPesosEnteros / multiploPesos) * multiploPesos;

            if ((totalCentavos % 100) === 0 && (sugeridoPesos * 100) <= totalCentavos) {
                sugeridoPesos += multiploPesos;
            }

            return sugeridoPesos * 100;
        },
        generarSugerenciasPagoEfectivo(total) {
            const totalCentavos = this.montoACentavos(total);
            if (totalCentavos <= 0) return [];

            const limite = 4;
            const denominaciones = [1, 2, 5, 10, 20, 50, 100, 200, 500, 1000];
            const sugerencias = new Set([totalCentavos]);
            const totalPesosEnteros = Math.ceil(totalCentavos / 100);

            [
                this.siguienteMultiploPago(totalCentavos, 10),
                this.siguienteMultiploPago(totalCentavos, 50),
            ].forEach((centavos) => {
                if (centavos >= totalCentavos) {
                    sugerencias.add(centavos);
                }
            });

            for (const denominacion of denominaciones) {
                if (sugerencias.size >= limite) break;
                if (denominacion < totalPesosEnteros) continue;
                sugerencias.add(denominacion * 100);
            }

            const lista = Array.from(sugerencias)
                .filter((centavos) => centavos >= totalCentavos)
                .sort((a, b) => a - b)
                .slice(0, limite);

            return lista.map((centavos) => this.centavosAMonto(centavos));
        },
        agregarLineaPago() {
            this.pagoLineas.push({ metodo: 'efectivo', monto: 0, recibido: 0 });
        },
        quitarLineaPago(idx) {
            if (this.pagoLineas.length <= 1) return;
            this.pagoLineas.splice(idx, 1);
        },
        metodoTarjeta(metodo) {
            return ['tarjeta_credito', 'tarjeta_debito', 'transferencia'].includes(String(metodo || ''));
        },
        async confirmarCobro(imprimir = true) {
            if (this.cobrandoVenta || !this.items.length) return;
            this.imprimirDespuesCobro = !!imprimir;
            if (this.tipoPagoSeleccionado === 'efectivo') {
                this.aplicarPagoEfectivo();
            }
            const lineasValidas = (this.pagoLineas || [])
                .map((ln) => ({
                    metodo: String(ln.metodo || ''),
                    monto: Number(ln.monto || 0),
                    recibido: Number(ln.recibido ?? ln.monto ?? 0),
                }))
                .filter((ln) => ln.recibido > 0);

            if (lineasValidas.length === 0 && Number(this.total || 0) > 0) {
                alert('Captura al menos un método de pago.');
                return;
            }

            if (this.totalPagoCapturado < this.total && Number(this.total || 0) > 0) {
                alert('El pago no cubre el total.');
                return;
            }

            const montoEfectivo = lineasValidas
                .filter((ln) => ln.metodo === 'efectivo')
                .reduce((s, ln) => s + ln.recibido, 0);
            const montoTarjeta = lineasValidas
                .filter((ln) => this.metodoTarjeta(ln.metodo))
                .reduce((s, ln) => s + ln.recibido, 0);

            const metodoPagoBackend = (lineasValidas.length === 0)
                ? 'sin_pago'
                : (lineasValidas.length === 1)
                ? (lineasValidas[0].metodo === 'efectivo' ? 'efectivo' : 'tarjeta')
                : 'mixto';

            const payloadBase = {
                notas: [this.notas || '', this.pagoReferencia ? `Ref: ${this.pagoReferencia}` : ''].filter(Boolean).join(' | ') || null,
                descuento_global: Number(this.descuentoGlobal || 0),
                metodo_pago: Number(this.total || 0) === 0 ? 'sin_pago' : metodoPagoBackend,
                monto_efectivo: Number(montoEfectivo || 0),
                monto_tarjeta: Number(montoTarjeta || 0),
                items: this.items.map((i) => ({
                    psk_id: Number(i.pskId),
                    origen: i.origen || 'manual',
                    pedido_detalle_id: i.pedidoDetalleId ? Number(i.pedidoDetalleId) : null,
                    usr_id: i.usrId ? Number(i.usrId) : null,
                    cantidad: Number(i.cantidad || 0),
                    precio: Number(i.precio || 0),
                    descuento_tipo: i.descuentoTipo || 'ninguno',
                    descuento_valor: Number(i.descuento || 0),
                    descuento: i.descuentoTipo === 'porcentaje' ? Number(i.descuento || 0) : 0,
                })),
            };
            const payload = this.cambioActivo
                ? {
                    ...payloadBase,
                    almacen_id: Number(this.ventaAlmacenId),
                    venta_origen_id: Number(this.cambioActual.venta_origen_id),
                    devoluciones: (this.cambioActual.devoluciones || []).map((d) => ({
                        pvd_id: Number(d.pvd_id),
                        cantidad: Number(d.cantidad),
                        condicion: 'reventa',
                    })),
                }
                : {
                    ...payloadBase,
                    almacen_id: Number(this.ventaAlmacenId),
                    cliente_id: this.clienteSeleccionado?.cli_id ? Number(this.clienteSeleccionado.cli_id) : null,
                    pedido_id: this.pedidoCargado?.pdp_id ? Number(this.pedidoCargado.pdp_id) : null,
                    credito_cambio_folio: this.creditoCambioSeleccionado?.folio || null,
                };

            this.cobrandoVenta = true;
            try {
                const res = await fetch(this.cambioActivo ? rutaCambioStore : rutaCobrarVenta, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                });

                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    const first = Object.values(err?.errors || {})[0];
                    alert(first ? first[0] : (err?.message || 'No fue posible cobrar la venta.'));
                    return;
                }
                const json = await res.json().catch(() => ({}));
                const ventaId = Number(json?.data?.psv_id || 0);
                this.mostrarModalPago = false;
                this.nuevaVenta();
                await this.cargarVentasDia();
                if (ventaId > 0 && this.imprimirDespuesCobro) await this.abrirTicketVenta(ventaId);
            } catch (error) {
                alert('Error de conexión al cobrar la venta.');
            } finally {
                this.cobrandoVenta = false;
            }
        },
        confirmarAlmacenVenta() {
            if (!this.ventaAlmacenId) return;
            this.ventaAlmacenNombre = this.obtenerNombreAlmacen(this.ventaAlmacenId);
            this.cerrarModalAlmacenVenta();
            this.cobrar();
        },
        seleccionarAlmacenYContinuar(almacen) {
            if (!almacen?.alm_id) return;
            this.ventaAlmacenId = String(almacen.alm_id);
            this.ventaAlmacenNombre = almacen.alm_nombre || this.obtenerNombreAlmacen(this.ventaAlmacenId);
            const contexto = this.modalAlmacenVentaContexto;
            const productoPendiente = this.productoPendienteAlmacen;
            this.cerrarModalAlmacenVenta();

            if (contexto === 'primer_producto' && productoPendiente?.psk_id) {
                const vendedor = this.resolverVendedorManual();
                this.agregarItem({
                    pskId: productoPendiente.psk_id,
                    origen: 'manual',
                    pedidoDetalleId: null,
                    usrId: vendedor.usrId,
                    vendedor: vendedor.nombre,
                    nombre: productoPendiente.psk_nombre || productoPendiente.producto?.prd_nombre || productoPendiente.psk_codigo,
                    sku: productoPendiente.psk_codigo,
                    codigoBarras: productoPendiente.psk_codigo_barras || productoPendiente.producto?.prd_codigo_barras || '',
                    precio: parseFloat(productoPendiente.psk_precio || 0),
                    permiteDecimal: Boolean(productoPendiente.permite_decimal),
                    cantidad: 1,
                    descuentoTipo: 'ninguno',
                    descuento: 0,
                });
                this.queryProducto = '';
                this.variantesPendientes = [];
                this.mostrarSelectorVariantes = false;
                this.$nextTick(() => this.$refs.productoInput?.focus());
                return;
            }

            this.$nextTick(() => this.cobrar());
        },
        obtenerNombreAlmacen(almacenId) {
            const encontrado = (this.almacenesVenta || []).find((a) => Number(a.alm_id) === Number(almacenId));
            return encontrado?.alm_nombre || '';
        },
        aplicarDescuentoGlobal() {
            if (this.tieneDescuentosPorProducto()) {
                AppUI.showMessage('Aviso', 'Quita primero los descuentos por producto.', 'warning');
                return;
            }
            const pct = prompt('Descuento global (%):');
            if (pct !== null && !isNaN(pct)) {
                this.descuentoGlobal = Math.min(100, Math.max(0, parseFloat(pct)));
            }
        },

        // ── Format ───────────────────────────────────────────────
        fmt(val) {
            return '$' + (val || 0).toLocaleString('es-MX', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },

        fmtNum(val) {
            return (val || 0).toLocaleString('es-MX', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },

        descuentoItemLabel(item) {
            if (String(item?.descuentoTipo || 'ninguno') === 'importe') {
                return 'Fijo ' + this.fmt(Number(item?.descuento || 0));
            }
            return '% ' + Number(item?.descuento || 0);
        },
    };
}
</script>
@endpush
