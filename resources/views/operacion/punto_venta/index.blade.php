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
            padding: .5rem .65rem;
            border-bottom: 1px solid var(--ls-border);
            cursor: pointer;
            display: flex;
            flex-direction: column;
            gap: .1rem;
        }
        .pos-search-suggest__item:last-child { border-bottom: none; }
        .pos-search-suggest__item:hover,
        .pos-search-suggest__item.active {
            background: var(--ls-accent-light);
        }
        .pos-search-suggest__name { font-size: .8rem; font-weight: 700; color: var(--ls-text-primary); }
        .pos-search-suggest__meta { font-size: .72rem; color: var(--ls-text-muted); }

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
        @media (max-width: 900px) {
            .cash-summary__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        /* ── Pay modal shell ────────────────────────────────────── */
        .pay-modal__card {
            width: min(1060px, 94vw);
            min-height: min(600px, 88vh);
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
            padding: 1rem 1.5rem;
            gap: 1rem;
        }
        .pay-modal__head-left { display: flex; align-items: center; gap: .85rem; }
        .pay-modal__head-icon {
            width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
            background: rgba(255,255,255,.15); border: 1.5px solid rgba(255,255,255,.25);
            display: flex; align-items: center; justify-content: center; font-size: 1.25rem;
        }
        .pay-modal__title {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 800;
            letter-spacing: .01em;
        }
        .pay-modal__subtitle { margin: 0; font-size: .78rem; color: rgba(255,255,255,.65); }
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
            grid-template-columns: 1fr 268px;
            min-height: 0;
        }
        .pay-main {
            padding: 1.2rem 1.4rem;
            overflow-y: auto;
            background: #f8fafc;
        }
        .pay-side {
            background: #fff;
            border-left: 1px solid #eef0f6;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: .6rem;
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
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .55rem;
            margin-bottom: .55rem;
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
            min-height: 68px;
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
            margin-top: .85rem;
            background: #fff;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            padding: .75rem 1rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .5rem;
            box-shadow: 0 1px 3px rgba(10,37,64,.04);
        }
        .pay-summary__item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: .92rem;
            font-weight: 700;
            padding: .3rem .5rem;
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
            .pay-modal__body { grid-template-columns: 1fr; }
            .pay-side { border-left: 0; border-top: 1px solid #eef0f6; flex-direction: row; flex-wrap: wrap; }
            .pay-side-card { flex: 1 1 140px; }
            .pay-methods { grid-template-columns: repeat(2, minmax(0,1fr)); }
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
                <div class="pos-total-block__lbl">Total</div>
                <div class="pos-total-block__amount">
                    <span>$</span><span x-text="fmtNum(total)"></span>
                </div>
            </div>

            {{-- Pagado --}}
            <div class="pos-panel__block">
                <div class="pos-panel__row">
                    <span class="pos-panel__lbl">Pagado</span>
                    <span class="pos-panel__val pos-panel__val--muted" x-text="fmt(pagado)"></span>
                </div>
            </div>

            {{-- Cambio + IVA --}}
            <div class="pos-panel__block">
                <div class="pos-panel__row">
                    <span class="pos-panel__lbl">Cambio</span>
                    <span class="pos-panel__val"
                          :class="cambio > 0 ? 'pos-panel__val--danger' : ''"
                          x-text="fmt(cambio)"></span>
                </div>
                <div class="pos-panel__row">
                    <span class="pos-panel__lbl">IVA</span>
                    <span class="pos-panel__val pos-panel__val--muted" x-text="fmt(iva)"></span>
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
            <button class="pos-btn pos-btn--success-outline" @click="ingresoCaja()">
                <i class="ti tabler-arrow-bar-to-down" style="font-size:.85rem"></i>
                Ingreso caja
            </button>
            <button class="pos-btn pos-btn--danger-outline" @click="retiroCaja()">
                <i class="ti tabler-arrow-bar-up" style="font-size:.85rem"></i>
                Retiro caja
            </button>
            <button class="pos-btn pos-btn--warning-outline" @click="gastoCaja()">
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
                Devolución
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
                            </div>
                            <div style="display:flex;align-items:center;gap:.55rem;">
                                <span style="font-weight:700;font-size:.82rem;" x-text="fmt(v.psv_total)"></span>
                                <button class="pos-btn pos-btn--ghost" @click="abrirTicketVenta(v.psv_id)">Imprimir</button>
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

    <div x-cloak x-show="mostrarModalResumenCaja" class="variant-modal" @keydown.escape.window="mostrarModalResumenCaja = false">
        <div class="variant-modal__card" style="max-width:920px;">
            <div class="variant-modal__head" style="display:flex;justify-content:space-between;align-items:center;gap:.75rem;">
                <span>Resumen de caja</span>
                <button class="pos-btn pos-btn--ghost pos-btn--sm" @click="mostrarModalResumenCaja = false">Cerrar</button>
            </div>
            <div style="padding:.9rem;">
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
                        <div class="cash-summary__label">Ventas del día</div>
                        <div class="cash-summary__value" x-text="String((ventasDelDia || []).length)"></div>
                    </div>
                    <div class="cash-summary__item">
                        <div class="cash-summary__label">Total vendido</div>
                        <div class="cash-summary__value" x-text="fmt(totalVentasDia)"></div>
                    </div>
                </div>

                <div style="max-height:300px;overflow:auto;border:1px solid var(--ls-border);border-radius:10px;background:#fff;">
                    <table class="cash-summary__table">
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Método</th>
                                <th>Hora</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="(ventasDelDia || []).length === 0">
                                <tr><td colspan="4" style="text-align:center;color:var(--ls-text-muted);padding:.9rem;">Sin ventas del día.</td></tr>
                            </template>
                            <template x-for="v in ventasDelDia" :key="v.psv_id">
                                <tr>
                                    <td x-text="v.psv_folio"></td>
                                    <td x-text="v.psv_metodo_pago || 'N/A'"></td>
                                    <td x-text="horaCorta(v.psv_fecha_cobro)"></td>
                                    <td x-text="fmt(v.psv_total)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
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
                <button class="pay-modal__close" @click="cerrarModalPago()">
                    <i class="ti tabler-x"></i>
                </button>
            </div>

            {{-- ── BODY ─────────────────────────────────────────────── --}}
            <div class="pay-modal__body">

                {{-- ── LEFT: main content ─────────────────────────── --}}
                <div class="pay-main">

                    {{-- Total card --}}
                    <div class="pay-total-card">
                        <p class="pay-total-label">Total a cobrar</p>
                        <div class="pay-total-amount" x-text="fmt(total)"></div>
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
                    </div>
                    <div class="pay-methods pay-methods--single">
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

                    {{-- Summary bar --}}
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
                </div>

                {{-- ── RIGHT: action panel ─────────────────────────── --}}
                <div class="pay-side">
                    <div class="pay-side-label">Acciones</div>

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
                Selecciona el almacén del ticket
            </div>
            <div style="padding:1rem;">
                <div style="font-size:.82rem;color:var(--ls-text-muted);margin-bottom:.55rem;">
                    Antes de cobrar, define de qué almacén se descontará este ticket.
                </div>
                <div class="almacen-radio-grid">
                    <template x-for="alm in almacenesVenta" :key="alm.alm_id">
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
                <button class="pos-btn pos-btn--ghost" @click="mostrarModalAlmacenVenta = false">Cancelar</button>
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
    const usuarioActualId = {{ (int) (auth()->user()->usr_id ?? 0) }};
    const usuarioActualNombre = @json((string) (auth()->user()->usr_nombre ?? auth()->user()->usr_usuario ?? 'Sin vendedor'));
    const rutaBuscarProducto = '{{ route('operacion.escaneo_productos.buscar') }}';
    const rutaBuscarPedidoFolio = '{{ route('operacion.pedidos_piso.folio.buscar') }}';
    const rutaBuscarClientes = '{{ route('pos.clientes.buscar') }}';
    const rutaCrearCliente = '{{ route('operacion.clientes.store') }}';
    const rutaCpBuscarCliente = '{{ route('operacion.clientes.cp.buscar') }}';
    const rutaCobrarVenta = '{{ route('pos.ventas.cobrar') }}';
    const rutaVentasDia = '{{ route('pos.ventas.dia') }}';
    const rutaPedidosPendientes = '{{ route('pos.pedidos.pendientes') }}';
    const rutaTicketVentaBase = '{{ url('/pos/ventas') }}';
    const puedeCrearCliente = @json($puedeCrearCliente ?? false);
    return {
        // ── Config ───────────────────────────────────────────────
        tab:             'ventas',
        sucursal:        '{{ $sucursal ?? "ADRIEL SABAH" }}',
        cajaNombre:      '{{ $caja ?? "Sin caja activa" }}',
        impresionEstado: 'Sin actividad',
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
        vendedorSeleccionadoId: '',
        ventaAlmacenId: '',
        ventaAlmacenNombre: '',
        mostrarModalAlmacenVenta: false,
        mostrarModalConfirmacionPedido: false,
        mostrarModalClientes: false,
        mostrarModalPago: false,
        mostrarModalResumenCaja: false,
        mostrarModalTickets: false,
        mostrarModalDescuentoItem: false,
        puedeCrearCliente: !!puedeCrearCliente,
        guardandoClienteNuevo: false,
        cobrandoVenta: false,
        cargandoTickets: false,
        cpRowsCliente: [],
        pedidoPendienteReemplazo: null,
        ventasDelDia: [],
        filtroTicket: '',
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
            return Math.max(0, this.subtotal - this.descuento);
        },
        get cambio() {
            return Math.max(0, this.pagado - this.total);
        },
        get totalVentasDia() {
            return (this.ventasDelDia || []).reduce((sum, v) => sum + Number(v.psv_total || 0), 0);
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
            const almId = this.sesionActiva?.caja_alm_id ? String(this.sesionActiva.caja_alm_id) : '';
            const almNombre = this.sesionActiva?.caja_almacen || '';
            if (!almId) return;
            this.ventaAlmacenId = almId;
            this.ventaAlmacenNombre = almNombre || this.obtenerNombreAlmacen(almId);
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
                F9:  () => this.buscarTicket(),
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
                    this.agregarDesdeBusqueda(resultados[0]);
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
            try {
                const q = (this.filtroTicket || '').trim();
                const res = await fetch(`${rutaVentasDia}?q=${encodeURIComponent(q)}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok) return;
                const json = await res.json();
                this.ventasDelDia = Array.isArray(json?.data) ? json.data : [];
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

        abrirTicketVenta(ventaId) {
            if (!ventaId) return;
            window.open(`${rutaTicketVentaBase}/${ventaId}/ticket`, '_blank');
        },

        cerrarModalPedido() {
            this.mostrarModalPedido = false;
            this.pedidoMensaje = '';
            this.pedidoPreview = null;
            this.pedidosPendientes = [];
        },

        async cargarPedidoPorFolio() {
            const folio = (this.folioPedidoBuscar || '').trim();
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

        agregarDesdeBusqueda(item) {
            if (!item?.psk_id) return;
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
                this.ventaAlmacenId = '';
                this.ventaAlmacenNombre = '';
                this.clienteSeleccionado = null;
                this.queryCliente = '';
                this.descuentoGlobal = 0;
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
        ingresoCaja()    { alert('Ingreso a caja — próximamente'); },
        retiroCaja()     { alert('Retiro de caja — próximamente'); },
        gastoCaja()      { alert('Gasto de caja — próximamente'); },
        devolucion()     { alert('Devolución — próximamente'); },
        corteCaja()      { alert('Corte de caja — próximamente'); },
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
        async cobrar()         {
            if (!this.items.length) return;
            if (!this.sesionActiva) {
                this.mostrarModalCaja = true;
                return;
            }
            if (!this.ventaAlmacenId) {
                this.mostrarModalAlmacenVenta = true;
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
            if (lineasValidas.length === 0) {
                alert('Captura al menos un método de pago.');
                return;
            }

            if (this.totalPagoCapturado < this.total) {
                alert('El pago no cubre el total.');
                return;
            }

            const montoEfectivo = lineasValidas
                .filter((ln) => ln.metodo === 'efectivo')
                .reduce((s, ln) => s + ln.recibido, 0);
            const montoTarjeta = lineasValidas
                .filter((ln) => this.metodoTarjeta(ln.metodo))
                .reduce((s, ln) => s + ln.recibido, 0);

            const metodoPagoBackend = (lineasValidas.length === 1)
                ? (lineasValidas[0].metodo === 'efectivo' ? 'efectivo' : 'tarjeta')
                : 'mixto';

            const payload = {
                almacen_id: Number(this.ventaAlmacenId),
                cliente_id: this.clienteSeleccionado?.cli_id ? Number(this.clienteSeleccionado.cli_id) : null,
                pedido_id: this.pedidoCargado?.pdp_id ? Number(this.pedidoCargado.pdp_id) : null,
                notas: [this.notas || '', this.pagoReferencia ? `Ref: ${this.pagoReferencia}` : ''].filter(Boolean).join(' | ') || null,
                descuento_global: Number(this.descuentoGlobal || 0),
                metodo_pago: metodoPagoBackend,
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

            this.cobrandoVenta = true;
            try {
                const res = await fetch(rutaCobrarVenta, {
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
                if (ventaId > 0 && this.imprimirDespuesCobro) this.abrirTicketVenta(ventaId);
            } catch (error) {
                alert('Error de conexión al cobrar la venta.');
            } finally {
                this.cobrandoVenta = false;
            }
        },
        confirmarAlmacenVenta() {
            if (!this.ventaAlmacenId) return;
            this.ventaAlmacenNombre = this.obtenerNombreAlmacen(this.ventaAlmacenId);
            this.mostrarModalAlmacenVenta = false;
            this.cobrar();
        },
        seleccionarAlmacenYContinuar(almacen) {
            if (!almacen?.alm_id) return;
            this.ventaAlmacenId = String(almacen.alm_id);
            this.ventaAlmacenNombre = almacen.alm_nombre || this.obtenerNombreAlmacen(this.ventaAlmacenId);
            this.mostrarModalAlmacenVenta = false;
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
