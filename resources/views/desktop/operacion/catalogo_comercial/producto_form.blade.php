@extends('layouts.desktop')

@section('title', $pageTitle)

@push('desktop-vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/select2/select2.css') }}" />
@endpush

@push('desktop-styles')
    <style>
        /* La página de producto usa un único scroll: el del contenedor exterior
           (.desktop-content). Por eso el pane crece de forma natural y no genera
           un segundo scroll que pelee con el de afuera. */
        .desktop-pane--form {
            height: auto;
            min-height: 100%;
            overflow: visible;
        }
        .desktop-product-page {
            min-height: 100%;
            display: flex;
            flex-direction: column;
        }
        .desktop-product-page__head {
            position: sticky;
            top: 0;
            z-index: 4;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 18px;
            border-bottom: 1px solid var(--stroke);
            background: linear-gradient(180deg, rgba(15, 108, 189, .04) 0%, rgba(255, 255, 255, .98) 100%);
            backdrop-filter: saturate(1.2) blur(2px);
        }
        .desktop-product-page__head h2 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
        }
        .desktop-product-page__head p {
            margin: 3px 0 0;
            color: var(--text-2);
            font-size: .78rem;
        }
        .desktop-product-page__body {
            flex: 1 1 auto;
            padding: 18px 20px 22px;
        }
        .desktop-product-steps {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            margin-bottom: 16px;
            padding: 11px 13px;
            border: 1px solid var(--brand-soft-2);
            border-radius: var(--r-md);
            background: var(--brand-soft);
            color: var(--text-2);
            font-size: .76rem;
            line-height: 1.5;
        }
        .desktop-product-steps svg {
            flex: none;
            width: 16px;
            height: 16px;
            margin-top: 1px;
            color: var(--brand);
        }
        .desktop-product-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 20px;
            align-items: start;
        }
        /* Secciones agrupadas: encabezado + tarjeta limpia */
        .desktop-product-section {
            margin-bottom: 18px;
        }
        .desktop-product-section:last-child {
            margin-bottom: 0;
        }
        .desktop-product-section__head {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0 2px 10px;
        }
        .desktop-product-section__num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: none;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: var(--brand-soft);
            color: var(--brand);
            font-size: .72rem;
            font-weight: 700;
        }
        .desktop-product-section__head h3 {
            margin: 0;
            font-size: .85rem;
            font-weight: 600;
        }
        .desktop-product-section__head p {
            margin: 1px 0 0;
            color: var(--text-2);
            font-size: .72rem;
        }
        .desktop-product-card {
            padding: 16px;
            border: 1px solid var(--stroke);
            border-radius: var(--r-lg);
            background: var(--surface);
            box-shadow: var(--shadow-1, 0 1px 2px rgba(16, 24, 40, .04));
        }
        .desktop-product-form-grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 12px 16px;
        }
        .desktop-product-form-grid .span-12 { grid-column: span 12; }
        .desktop-product-form-grid .span-8 { grid-column: span 8; }
        .desktop-product-form-grid .span-6 { grid-column: span 6; }
        .desktop-product-form-grid .span-4 { grid-column: span 4; }
        .desktop-product-form-grid .span-3 { grid-column: span 3; }
        .desktop-product-form-grid .span-2 { grid-column: span 2; }
        .desktop-product-field input[readonly],
        .desktop-product-field textarea[readonly] {
            background: var(--surface-sunken);
        }
        .desktop-product-field.is-muted {
            opacity: .62;
        }
        .desktop-product-checkgrid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }
        .desktop-product-check {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            min-height: 42px;
            padding: 8px 10px;
            border: 1px solid var(--stroke);
            border-radius: var(--r-md);
            background: var(--surface-alt);
            transition: border-color .12s ease, background .12s ease;
        }
        .desktop-product-check.is-selected {
            border-color: var(--brand-soft-2);
            background: var(--brand-soft);
        }
        .desktop-product-check small {
            display: block;
            margin-top: 2px;
            color: var(--text-3);
            font-size: .7rem;
        }
        .desktop-product-type {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }
        .desktop-product-type__option {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px;
            border: 1px solid var(--stroke);
            border-radius: var(--r-md);
            background: var(--surface-alt);
            transition: border-color .12s ease, background .12s ease;
        }
        .desktop-product-type__option.is-active {
            border-color: var(--brand-soft-2);
            background: var(--brand-soft);
        }
        .desktop-product-type__option strong {
            display: block;
            font-size: .8rem;
        }
        .desktop-product-type__option span {
            display: block;
            margin-top: 3px;
            color: var(--text-2);
            font-size: .72rem;
            line-height: 1.45;
        }
        .desktop-product-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
            margin-top: 12px;
        }
        .desktop-product-summary:empty {
            display: none;
        }
        .desktop-product-summary__card {
            padding: 10px 12px;
            border: 1px solid var(--stroke);
            border-radius: var(--r-md);
            background: var(--surface-alt);
        }
        .desktop-product-summary__card small {
            display: block;
            color: var(--text-2);
            font-size: .7rem;
        }
        .desktop-product-summary__card strong {
            display: block;
            margin-top: 3px;
            font-size: .9rem;
        }
        .desktop-product-variable {
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px solid var(--divider);
        }
        .desktop-product-variable[hidden] {
            display: none;
        }
        .desktop-product-blockcard {
            padding: 14px;
            border: 1px solid var(--stroke);
            border-radius: var(--r-md);
            background: var(--surface-alt);
        }
        .desktop-product-blockcard__head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }
        .desktop-product-blockcard__head h3 {
            margin: 0;
            font-size: .9rem;
            font-weight: 600;
        }
        .desktop-product-blockcard__head p {
            margin: 2px 0 0;
            color: var(--text-2);
            font-size: .74rem;
        }
        .desktop-product-badge {
            display: inline-flex;
            align-items: center;
            height: 20px;
            padding: 0 8px;
            border-radius: var(--r-sm);
            font-size: .7rem;
            font-weight: 600;
            background: var(--brand-soft);
            color: var(--brand);
        }
        .desktop-product-value-card {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--divider);
        }
        .desktop-product-value-card:first-child {
            margin-top: 0;
            padding-top: 0;
            border-top: 0;
        }
        .desktop-product-value-card h4 {
            margin: 0;
            font-size: .8rem;
            font-weight: 600;
        }
        .desktop-product-value-card__empty {
            margin-top: 8px;
            color: var(--text-3);
            font-size: .73rem;
        }
        .desktop-product-corridas {
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px solid var(--divider);
        }
        .desktop-product-corridas__toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
        .desktop-product-corridas__toolbar p {
            margin: 2px 0 0;
            color: var(--text-2);
            font-size: .73rem;
        }
        .desktop-product-corridas__controls {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .desktop-product-corrida {
            margin-top: 8px;
            padding: 12px;
            border: 1px solid var(--stroke);
            border-radius: var(--r-md);
            background: var(--surface);
        }
        .desktop-product-corrida__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
        }
        .desktop-product-corrida__head strong {
            font-size: .82rem;
        }
        .desktop-product-corrida__grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 8px 12px;
        }
        .desktop-product-corrida__grid .span-6 { grid-column: span 6; }
        .desktop-product-corrida__grid .span-4 { grid-column: span 4; }
        .desktop-product-corrida__grid .span-3 { grid-column: span 3; }
        .desktop-product-sidecard {
            position: sticky;
            top: 78px;
            padding: 14px;
            border: 1px solid var(--stroke);
            border-radius: var(--r-lg);
            background: var(--surface-alt);
            height: fit-content;
        }
        @media (max-width: 1180px) {
            .desktop-product-sidecard {
                position: static;
                top: auto;
            }
        }
        .desktop-product-sidecard h3 {
            margin: 0 0 8px;
            font-size: .86rem;
            font-weight: 600;
        }
        .desktop-product-sidecard p {
            margin: 0 0 12px;
            color: var(--text-2);
            font-size: .75rem;
            line-height: 1.5;
        }
        .desktop-product-preview {
            width: 100%;
            aspect-ratio: 1;
            border: 1px dashed var(--stroke-strong);
            border-radius: var(--r-lg);
            background: var(--surface);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            color: var(--text-3);
            font-size: .74rem;
            text-align: center;
            padding: 10px;
        }
        .desktop-product-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
        }
        .desktop-product-preview.has-image img {
            display: block;
        }
        .desktop-product-preview.has-image span {
            display: none;
        }
        .desktop-product-methods {
            display: grid;
            gap: 8px;
            margin-top: 12px;
        }
        .desktop-product-method {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            padding: 10px;
            border: 1px solid var(--stroke);
            border-radius: var(--r-md);
            background: var(--surface);
            transition: border-color .12s ease, background .12s ease;
        }
        .desktop-product-method.is-active {
            border-color: var(--brand-soft-2);
            background: var(--brand-soft);
        }
        .desktop-product-method strong {
            display: block;
            font-size: .78rem;
        }
        .desktop-product-method span {
            display: block;
            margin-top: 2px;
            color: var(--text-2);
            font-size: .72rem;
            line-height: 1.4;
        }
        .desktop-product-block {
            display: none;
            margin-top: 12px;
        }
        .desktop-product-block.is-active {
            display: block;
        }
        .desktop-product-qr {
            padding: 12px;
            border: 1px dashed var(--stroke-strong);
            border-radius: var(--r-md);
            background: var(--surface);
            text-align: center;
        }
        .desktop-product-qr img {
            width: 100%;
            max-width: 220px;
            border-radius: var(--r-md);
            background: #fff;
            display: none;
        }
        .desktop-product-qr img.is-visible {
            display: inline-block;
        }
        .desktop-product-qr__link {
            display: none;
            margin-top: 8px;
            color: var(--brand);
            font-size: .73rem;
            word-break: break-all;
        }
        .desktop-product-qr__link.is-visible {
            display: block;
        }
        .desktop-product-qr__status {
            margin-top: 8px;
            color: var(--text-2);
            font-size: .73rem;
            line-height: 1.45;
        }
        .desktop-product-feedback {
            margin-bottom: 12px;
        }
        .desktop-select2 + .select2-container {
            width: 100% !important;
        }
        .desktop-select2 + .select2-container .select2-selection--multiple {
            min-height: 34px;
            border: 1px solid var(--stroke-strong);
            border-radius: var(--r-md);
            background: var(--surface);
            padding: 2px 6px;
        }
        .desktop-select2 + .select2-container .select2-selection--multiple .select2-selection__choice {
            background: var(--brand-soft);
            border: 1px solid var(--brand-soft-2);
            color: var(--brand);
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 600;
            padding: 2px 8px;
            margin-top: 3px;
            margin-right: 4px;
        }
        .desktop-select2 + .select2-container .select2-selection--multiple .select2-selection__choice__remove {
            color: var(--brand);
            margin-right: 4px;
        }
        .select2-container--open .select2-dropdown,
        .select2-dropdown {
            background: var(--surface, #fff);
            border: 1px solid var(--stroke-strong);
            border-radius: var(--r-md);
            overflow: hidden;
            box-shadow: var(--shadow-2);
            z-index: 9000;
        }
        .select2-results {
            background: var(--surface, #fff);
        }
        .select2-results__option {
            background: var(--surface, #fff);
            color: var(--text);
        }
        .select2-search--dropdown .select2-search__field {
            border: 1px solid var(--stroke-strong);
            border-radius: var(--r-sm);
        }
        .select2-results__option--highlighted {
            background: var(--brand) !important;
        }
        @media (max-width: 1180px) {
            .desktop-product-layout {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 900px) {
            .desktop-product-form-grid,
            .desktop-product-corrida__grid {
                grid-template-columns: 1fr;
            }
            .desktop-product-form-grid .span-12,
            .desktop-product-form-grid .span-8,
            .desktop-product-form-grid .span-6,
            .desktop-product-form-grid .span-4,
            .desktop-product-form-grid .span-3,
            .desktop-product-form-grid .span-2,
            .desktop-product-corrida__grid .span-6,
            .desktop-product-corrida__grid .span-4,
            .desktop-product-corrida__grid .span-3 {
                grid-column: span 1;
            }
            .desktop-product-checkgrid,
            .desktop-product-summary {
                grid-template-columns: 1fr;
            }
        }

        /* ====================================================================
           REDISEÑO Azure / M365 — App shell: cabecera + nav interna + footer
           fijos; solo scrollea el panel central. Reutiliza tokens y componentes.
           ==================================================================== */
        .desktop-pane--form {
            height: 100%;
            min-height: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .desktop-product-page {
            height: 100%;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }

        /* Cabecera fija */
        .desktop-product-head {
            flex: none;
            position: static;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 11px 18px;
            border-bottom: 1px solid var(--stroke);
            background: var(--surface);
        }
        .desktop-product-head h2 { margin: 0; font-size: .98rem; font-weight: 600; letter-spacing: -.01em; }
        .desktop-product-head p { margin: 2px 0 0; color: var(--text-2); font-size: .76rem; }
        .desktop-product-head__badge {
            display: inline-flex; align-items: center; gap: 6px;
            height: 24px; padding: 0 10px; border-radius: 999px;
            background: var(--brand-soft); color: var(--brand);
            font-size: .72rem; font-weight: 600;
        }

        /* Shell: nav interna + scroll */
        .desktop-product-shell {
            flex: 1 1 auto;
            min-height: 0;
            display: grid;
            grid-template-columns: 216px minmax(0, 1fr);
        }

        /* Navegación lateral interna (estilo Azure) */
        .desktop-product-nav {
            border-right: 1px solid var(--stroke);
            background: var(--surface-alt);
            display: flex;
            flex-direction: column;
            min-height: 0;
            overflow: auto;
        }
        .desktop-product-nav__list { display: flex; flex-direction: column; gap: 1px; padding: 10px 8px; }
        .desktop-product-nav__item {
            display: flex; align-items: center; gap: 9px;
            height: 32px; padding: 0 10px;
            border-radius: var(--r-md);
            color: var(--text-2); font-size: .8rem; font-weight: 500;
            text-decoration: none; cursor: pointer; position: relative;
            transition: background .12s ease, color .12s ease;
        }
        .desktop-product-nav__item:hover { background: rgba(15, 108, 189, .07); color: var(--text); }
        .desktop-product-nav__item.is-active { background: var(--brand-soft); color: var(--brand); font-weight: 600; }
        .desktop-product-nav__item.is-active::before {
            content: ""; position: absolute; left: 0; top: 6px; bottom: 6px;
            width: 3px; border-radius: 0 3px 3px 0; background: var(--brand);
        }
        .desktop-product-nav__num {
            flex: none;
            display: inline-flex; align-items: center; justify-content: center;
            width: 19px; height: 19px; border-radius: 50%;
            background: var(--surface-sunken); color: var(--text-2);
            font-size: .68rem; font-weight: 700;
        }
        .desktop-product-nav__item.is-active .desktop-product-nav__num { background: var(--brand); color: #fff; }
        .desktop-product-nav__item:hover .desktop-product-nav__num { background: var(--stroke-strong); color: var(--text); }
        .desktop-product-nav__item.is-active:hover .desktop-product-nav__num { background: var(--brand); color: #fff; }

        /* Resumen dinámico siempre visible */
        .desktop-product-nav__summary { margin-top: auto; padding: 12px; border-top: 1px solid var(--stroke); }
        .desktop-product-nav__summary-title {
            display: block; margin-bottom: 8px;
            font-size: .68rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase;
            color: var(--text-3);
        }
        .desktop-product-nav__summary .desktop-product-summary {
            grid-template-columns: 1fr; gap: 4px; margin-top: 0;
        }
        .desktop-product-nav__summary .desktop-product-summary__card {
            display: flex; align-items: baseline; justify-content: space-between; gap: 8px;
            padding: 6px 9px; border: 0; border-radius: var(--r-sm); background: var(--surface);
        }
        .desktop-product-nav__summary .desktop-product-summary__card small { font-size: .73rem; color: var(--text-2); }
        .desktop-product-nav__summary .desktop-product-summary__card strong { margin-top: 0; font-size: .82rem; font-weight: 600; }

        /* Único panel con scroll */
        .desktop-product-scroll {
            position: relative;
            min-height: 0;
            overflow: auto;
            overscroll-behavior: contain;
            scroll-behavior: smooth;
            padding: 16px 22px 28px;
        }

        /* Footer fijo */
        .desktop-product-foot {
            flex: none;
            display: flex; align-items: center; gap: 8px;
            padding: 10px 18px;
            border-top: 1px solid var(--stroke);
            background: var(--surface);
            box-shadow: 0 -1px 2px rgba(16, 24, 40, .04);
        }
        .desktop-product-foot__spacer { flex: 1 1 auto; }
        .desktop-product-foot__hint { font-size: .74rem; color: var(--text-3); }

        /* Secciones ligeras: menos cajas, menos bordes, más densidad */
        .desktop-product-section {
            scroll-margin-top: 8px;
            margin: 0 0 18px;
            padding: 0 0 18px;
            border-bottom: 1px solid var(--divider);
        }
        .desktop-product-section:last-child { margin-bottom: 0; padding-bottom: 4px; border-bottom: 0; }
        .desktop-product-section__head { margin: 0 0 12px; }
        .desktop-product-section__head h3 { font-size: .9rem; }
        .desktop-product-section__num {
            width: 21px; height: 21px; font-size: .7rem;
        }
        .desktop-product-card { padding: 0; border: 0; background: transparent; box-shadow: none; }
        .desktop-product-form-grid { gap: 12px 16px; }

        /* Tipo de producto → control segmentado Fluent */
        .desktop-product-type {
            display: inline-flex; gap: 2px; padding: 2px;
            background: var(--surface-sunken); border-radius: var(--r-md);
        }
        .desktop-product-type__option {
            display: inline-flex; align-items: center; gap: 7px;
            height: 30px; padding: 0 18px; min-height: 0;
            border: 0; border-radius: var(--r-sm); background: transparent;
            color: var(--text-2); font-size: .82rem; font-weight: 600;
            cursor: pointer; transition: background .12s ease, color .12s ease, box-shadow .12s ease;
        }
        .desktop-product-type__option input { position: absolute; opacity: 0; width: 0; height: 0; }
        .desktop-product-type__option > span { display: inline; margin: 0; font-size: inherit; color: inherit; }
        .desktop-product-type__option:hover { color: var(--text); }
        .desktop-product-type__option.is-active { background: var(--surface); color: var(--brand); box-shadow: var(--shadow-2); }
        .desktop-product-hint { margin: 8px 2px 0; font-size: .76rem; color: var(--text-2); }

        /* Disponibilidad → lista de checkboxes densa multicolumna */
        .desktop-product-checkgrid {
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 1px 16px;
        }
        .desktop-product-check {
            min-height: 0; padding: 6px 8px; gap: 9px; align-items: center;
            border: 0; border-radius: var(--r-sm); background: transparent;
        }
        .desktop-product-check:hover { background: var(--surface-sunken); }
        .desktop-product-check.is-selected { border: 0; background: var(--brand-soft); }
        .desktop-product-check input { margin: 0; accent-color: var(--brand); }
        .desktop-product-check small { margin-top: 0; }

        /* Corridas (núcleo): agrupación ligera, bloques compactos */
        .desktop-product-variable { margin-top: 14px; padding-top: 14px; }
        .desktop-product-blockcard { padding: 0; border: 0; background: transparent; }
        .desktop-product-corrida {
            margin-top: 6px; padding: 10px 12px;
            border: 1px solid var(--stroke); border-radius: var(--r-md);
            background: var(--surface);
        }
        .desktop-product-corrida__head { margin-bottom: 8px; }
        .desktop-product-value-card { margin-top: 10px; padding-top: 10px; }
        .desktop-product-corridas { margin-top: 14px; padding-top: 14px; }

        /* Imagen al final: preview acotado + métodos al lado */
        .desktop-product-imagewrap {
            display: grid; grid-template-columns: 196px minmax(0, 1fr); gap: 20px; align-items: start;
        }
        .desktop-product-imagewrap .desktop-product-preview { max-width: 196px; margin: 0; }
        .desktop-product-methods { margin-top: 0; }

        @media (max-width: 1100px) {
            .desktop-product-shell { grid-template-columns: 1fr; }
            .desktop-product-nav {
                flex-direction: row; align-items: center; gap: 4px;
                border-right: 0; border-bottom: 1px solid var(--stroke);
                overflow-x: auto; overflow-y: hidden;
            }
            .desktop-product-nav__list { flex-direction: row; padding: 6px 8px; }
            .desktop-product-nav__item { height: 28px; white-space: nowrap; }
            .desktop-product-nav__item.is-active::before { display: none; }
            .desktop-product-nav__summary { display: none; }
        }
        @media (max-width: 760px) {
            .desktop-product-imagewrap { grid-template-columns: 1fr; }
            .desktop-product-imagewrap .desktop-product-preview { max-width: 220px; }
        }
    </style>
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        @php($activeSubmenu = 'productos')
        @include('desktop.operacion.catalogo_comercial._subnav')
        <span class="desktop-toolbar__divider"></span>
        <a href="{{ route('desktop.operacion.catalogo_comercial.productos.index') }}" class="desktop-btn desktop-btn--ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Volver a productos
        </a>
    </div>
    <div class="desktop-toolbar__group">
        <button type="button" class="desktop-btn desktop-btn--default" id="btn-cancelar-producto-top">
            Descartar cambios
        </button>
        <button type="submit" form="desktop-producto-form" class="desktop-btn desktop-btn--primary" id="btn-submit-producto-top">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8"/><path d="M7 3v5h8"/></svg>
            Guardar y continuar
        </button>
    </div>
@endsection

@section('content')
    <section class="desktop-pane desktop-pane--form">
        <form id="desktop-producto-form" class="desktop-product-page" enctype="multipart/form-data">
            <header class="desktop-product-head">
                <div>
                    <h2>{{ $pageTitle }}</h2>
                    <p>Captura organizada por secciones. Usa la navegación lateral para moverte.</p>
                </div>
                <span class="desktop-product-head__badge">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    Códigos y SKU automáticos
                </span>
            </header>

            <input type="hidden" id="prd_id" name="prd_id" value="{{ $isEditing ? $initialProductoData['prd_id'] : '' }}">
            <input type="hidden" id="prd_imagen_temp_token" name="prd_imagen_temp_token" value="">
            <input type="hidden" id="prd_imagen_reset" name="prd_imagen_reset" value="0">

            <div class="desktop-product-shell">
                <aside class="desktop-product-nav" id="producto-nav">
                    <div class="desktop-product-nav__list">
                        <a class="desktop-product-nav__item is-active" data-target="sec-general"><span class="desktop-product-nav__num">1</span> Información general</a>
                        <a class="desktop-product-nav__item" data-target="sec-clasificacion"><span class="desktop-product-nav__num">2</span> Clasificación</a>
                        <a class="desktop-product-nav__item" data-target="sec-disponibilidad"><span class="desktop-product-nav__num">3</span> Disponibilidad</a>
                        <a class="desktop-product-nav__item" data-target="sec-tipo"><span class="desktop-product-nav__num">4</span> Tipo de producto</a>
                        <a class="desktop-product-nav__item" data-target="sec-corridas"><span class="desktop-product-nav__num">5</span> Corridas</a>
                        <a class="desktop-product-nav__item" data-target="sec-precios"><span class="desktop-product-nav__num">6</span> Precios e inventario</a>
                        <a class="desktop-product-nav__item" data-target="sec-imagen"><span class="desktop-product-nav__num">7</span> Imagen principal</a>
                    </div>
                    <div class="desktop-product-nav__summary">
                        <span class="desktop-product-nav__summary-title">Resumen</span>
                        <div class="desktop-product-summary" id="producto-resumen-general"></div>
                    </div>
                </aside>

                <div class="desktop-product-scroll" id="producto-scroll">
                    <div class="desktop-feedback desktop-product-feedback" id="desktop-productos-feedback"></div>

                        <section class="desktop-product-section" id="sec-general">
                            <div class="desktop-product-section__head">
                                <span class="desktop-product-section__num">1</span>
                                <div>
                                    <h3>Información general</h3>
                                    <p>Identifica el producto y su origen.</p>
                                </div>
                            </div>
                            <div class="desktop-product-card">
                                <div class="desktop-product-form-grid">
                                    <div class="desktop-field span-8">
                                        <label>Nombre del producto</label>
                                        <input type="text" name="prd_nombre" id="prd_nombre" maxlength="180" required>
                                    </div>
                                    <div class="desktop-field span-4">
                                        <label>Código interno</label>
                                        <input type="text" name="prd_codigo" id="prd_codigo" maxlength="40" readonly placeholder="Se crea automáticamente al guardar">
                                    </div>

                                    <div class="desktop-field span-4">
                                        <label>Marca</label>
                                        <select name="prd_mrc_id" id="prd_mrc_id" required>
                                            <option value="">Selecciona una marca</option>
                                            @foreach($opciones['marcas'] as $item)
                                                <option value="{{ $item->mrc_id }}">{{ $item->mrc_nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="desktop-field span-4" id="prd-modelo-wrap" style="display:none;">
                                        <label>Modelo</label>
                                        <select name="prd_mdl_id" id="prd_mdl_id">
                                            <option value="">Sin modelo</option>
                                        </select>
                                    </div>
                                    <div class="desktop-field span-4">
                                        <label>Proveedor principal</label>
                                        <select name="prd_prv_id" id="prd_prv_id">
                                            <option value="">Sin proveedor asignado</option>
                                            @foreach($opciones['proveedores'] as $item)
                                                <option value="{{ $item->prv_id }}">{{ $item->prv_nombre_empresa }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="desktop-field span-12">
                                        <label>Descripción para referencia</label>
                                        <textarea name="prd_descripcion" id="prd_descripcion" rows="3" maxlength="2000" placeholder="Ejemplo: producto de temporada, material, detalles importantes..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="desktop-product-section" id="sec-clasificacion">
                            <div class="desktop-product-section__head">
                                <span class="desktop-product-section__num">2</span>
                                <div>
                                    <h3>Clasificación y unidad</h3>
                                    <p>Cómo se ordena y se vende el producto.</p>
                                </div>
                            </div>
                            <div class="desktop-product-card">
                                <div class="desktop-product-form-grid">
                                    <div class="desktop-field span-4">
                                        <label>Línea</label>
                                        <select name="prd_lna_id" id="prd_lna_id" required>
                                            <option value="">Selecciona una línea</option>
                                            @foreach($opciones['lineas'] as $item)
                                                <option value="{{ $item->lna_id }}">{{ $item->lna_nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="desktop-field span-4">
                                        <label>Concepto</label>
                                        <select name="prd_ctg_id" id="prd_ctg_id">
                                            <option value="">Selecciona una línea primero</option>
                                        </select>
                                    </div>
                                    <div class="desktop-field span-4">
                                        <label>Unidad de venta</label>
                                        <select name="prd_umd_id" id="prd_umd_id" required>
                                            <option value="">Selecciona una unidad</option>
                                            @foreach($opciones['unidades'] as $item)
                                                <option value="{{ $item->umd_id }}"{{ $item->umd_es_predeterminada ? ' data-predeterminada=1' : '' }}>{{ $item->umd_nombre }} ({{ $item->umd_codigo }}){{ $item->umd_es_predeterminada ? ' ★' : '' }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="desktop-field span-6">
                                        <label>Código de barras</label>
                                        <input type="text" name="prd_codigo_barras" id="prd_codigo_barras" maxlength="80" placeholder="Opcional">
                                    </div>
                                    <div class="desktop-field span-6">
                                        <label>Clave fiscal (SAT)</label>
                                        <input type="text" name="prd_clave_sat" id="prd_clave_sat" maxlength="20" placeholder="Opcional">
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="desktop-product-section" id="sec-disponibilidad">
                            <div class="desktop-product-section__head">
                                <span class="desktop-product-section__num">3</span>
                                <div>
                                    <h3>Disponibilidad en almacenes</h3>
                                    <p>Marca dónde podrá manejarse este producto.</p>
                                </div>
                            </div>
                            <div class="desktop-product-card">
                                <div class="desktop-product-checkgrid" id="prd-almacenes-checklist">
                                    @foreach($opciones['almacenes'] as $item)
                                        <label class="desktop-product-check">
                                            <input type="checkbox" name="almacen_ids[]" value="{{ $item->alm_id }}">
                                            <span>
                                                <strong>{{ $item->alm_nombre }}</strong>
                                                <small>{{ $item->sucursal?->scl_nombre }}</small>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </section>

                        <section class="desktop-product-section" id="sec-tipo">
                            <div class="desktop-product-section__head">
                                <span class="desktop-product-section__num">4</span>
                                <div>
                                    <h3>Tipo de producto</h3>
                                    <p>Define si tendrá corridas (tallas, colores, etc.) o no.</p>
                                </div>
                            </div>
                            <div class="desktop-product-card">
                                <div class="desktop-product-type">
                                    <label class="desktop-product-type__option" data-product-type="variable">
                                        <input type="radio" name="prd_tipo" id="prd_tipo_variable" value="variable">
                                        <span>Variable</span>
                                    </label>
                                    <label class="desktop-product-type__option" data-product-type="simple">
                                        <input type="radio" name="prd_tipo" id="prd_tipo_simple" value="simple">
                                        <span>Producto simple</span>
                                    </label>
                                </div>
                                <p class="desktop-product-hint">El producto <strong>variable</strong> maneja corridas (tallas, colores, etc.) con su propio precio y stock. El <strong>simple</strong> es una sola pieza.</p>
                            </div>
                        </section>

                        <section class="desktop-product-section" id="sec-corridas">
                            <div class="desktop-product-section__head">
                                <span class="desktop-product-section__num">5</span>
                                <div>
                                    <h3>Configuración de corridas</h3>
                                    <p>Elige los atributos, marca sus valores y crea las corridas. Solo aplica a productos variables.</p>
                                </div>
                            </div>
                            <div class="desktop-product-card">
                                <div class="desktop-product-variable" id="producto-variable-shell" hidden>
                                    <div class="desktop-product-blockcard">
                                        <div class="desktop-field">
                                            <label>Atributos para corridas</label>
                                            <select id="producto-atributos-select" class="desktop-select2" name="atributo_ids[]" multiple></select>
                                        </div>

                                        <div id="producto-valores-config"></div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="desktop-product-section" id="sec-precios">
                            <div class="desktop-product-section__head">
                                <span class="desktop-product-section__num">6</span>
                                <div>
                                    <h3>Precios e inventario</h3>
                                    <p>En productos variables estos valores se definen por corrida.</p>
                                </div>
                            </div>
                            <div class="desktop-product-card">
                                <div class="desktop-product-form-grid">
                                    <div class="desktop-field span-4 desktop-product-field" data-base-field="precio">
                                        <label>Precio de venta</label>
                                        <input type="number" name="prd_precio_base" id="prd_precio_base" min="0" step="0.01" value="0.00" required>
                                    </div>
                                    <div class="desktop-field span-4 desktop-product-field" data-base-field="costo">
                                        <label>Costo</label>
                                        <input type="number" name="prd_costo" id="prd_costo" min="0" step="0.01" value="0.00">
                                    </div>
                                    <div class="desktop-field span-4">
                                        <label>Estado</label>
                                        <select name="prd_estatus" id="prd_estatus">
                                            <option value="activo">Activo</option>
                                            <option value="inactivo">Inactivo</option>
                                        </select>
                                    </div>
                                    <div class="desktop-field span-6 desktop-product-field" data-base-field="stock-min">
                                        <label>Stock mínimo base</label>
                                        <input type="number" name="prd_stock_minimo" id="prd_stock_minimo" min="0" step="1" value="0" required>
                                    </div>
                                    <div class="desktop-field span-6 desktop-product-field" data-base-field="stock-max">
                                        <label>Stock máximo base</label>
                                        <input type="number" name="prd_stock_maximo" id="prd_stock_maximo" min="0" step="1" value="0" required>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="desktop-product-section" id="sec-imagen">
                            <div class="desktop-product-section__head">
                                <span class="desktop-product-section__num">7</span>
                                <div>
                                    <h3>Imagen principal</h3>
                                    <p>Opcional. Súbela desde este equipo, por enlace o con el celular vía QR.</p>
                                </div>
                            </div>
                            <div class="desktop-product-card">
                                <div class="desktop-product-imagewrap">
                                    <div class="desktop-product-preview" id="producto-imagen-preview-wrap">
                                        <span>Aquí verás la foto principal del producto.</span>
                                        <img id="producto-imagen-preview" alt="Vista previa del producto">
                                    </div>

                                    <div>
                                        <div class="desktop-product-methods">
                                            <label class="desktop-product-method" data-image-method-label="archivo">
                                                <input type="radio" name="prd_imagen_metodo" value="archivo">
                                                <span>
                                                    <strong>Subir desde esta computadora</strong>
                                                    <span>Elige una imagen guardada en este equipo.</span>
                                                </span>
                                            </label>
                                            <label class="desktop-product-method" data-image-method-label="url">
                                                <input type="radio" name="prd_imagen_metodo" value="url">
                                                <span>
                                                    <strong>Usar enlace de imagen</strong>
                                                    <span>Pega el enlace público de la imagen.</span>
                                                </span>
                                            </label>
                                            <label class="desktop-product-method" data-image-method-label="qr">
                                                <input type="radio" name="prd_imagen_metodo" value="qr">
                                                <span>
                                                    <strong>Subir desde celular con QR</strong>
                                                    <span>Escanea el código y carga la foto desde tu teléfono.</span>
                                                </span>
                                            </label>
                                        </div>

                                        <div class="desktop-product-block" data-image-method="archivo">
                                            <div class="desktop-field">
                                                <label>Archivo de imagen</label>
                                                <input type="file" name="prd_imagen_archivo" id="prd_imagen_archivo" accept="image/*">
                                                <small>JPG, PNG o WEBP. Máximo 5 MB.</small>
                                            </div>
                                        </div>

                                        <div class="desktop-product-block" data-image-method="url">
                                            <div class="desktop-field">
                                                <label>URL externa</label>
                                                <input type="url" name="prd_imagen_url" id="prd_imagen_url" maxlength="500" placeholder="https://...">
                                            </div>
                                        </div>

                                        <div class="desktop-product-block" data-image-method="qr">
                                            <div class="desktop-product-qr">
                                                <img id="producto-imagen-qr" alt="QR de carga móvil">
                                                <div id="producto-imagen-qr-placeholder" class="desktop-product-qr__status">Generando sesión de carga móvil...</div>
                                                <a href="#" target="_blank" rel="noopener" class="desktop-product-qr__link" id="producto-imagen-mobile-link"></a>
                                                <div class="desktop-product-qr__status" id="producto-imagen-mobile-status">Aún no se ha cargado una imagen desde el celular.</div>
                                                <div class="desktop-product-qr__status">Tip: usa una dirección de red local o dominio accesible desde tu teléfono.</div>
                                            </div>
                                        </div>

                                        <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:12px;">
                                            <button type="button" class="desktop-btn desktop-btn--default" id="btn-regenerar-qr-producto">Generar nuevo QR</button>
                                            <button type="button" class="desktop-btn desktop-btn--default" id="btn-quitar-imagen-producto">Quitar foto</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                </div>
            </div>

            <footer class="desktop-product-foot">
                <button type="button" class="desktop-btn desktop-btn--default" id="btn-cancelar-producto-bottom">Cancelar</button>
                <span class="desktop-product-foot__hint">Los códigos internos y los SKU se generan automáticamente al guardar.</span>
                <div class="desktop-product-foot__spacer"></div>
                <button type="submit" class="desktop-btn desktop-btn--primary" id="btn-submit-producto-bottom">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8"/><path d="M7 3v5h8"/></svg>
                    Guardar y continuar
                </button>
            </footer>
        </form>
    </section>
@endsection

@push('desktop-vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/select2/select2.js') }}"></script>
@endpush

@push('desktop-scripts')
    <script>
        (function () {
            const $form = $('#desktop-producto-form');
            const $feedback = $('#desktop-productos-feedback');
            const modelosCatalogo = @json($modelosCatalogo);
            const categoriasCatalogo = @json($categoriasCatalogo);
            const initialData = @json($initialProductoData);
            const isEditing = @json($isEditing);
            const rutas = {
                index: '{{ route('desktop.operacion.catalogo_comercial.productos.index') }}',
                store: '{{ route('desktop.operacion.catalogo_comercial.productos.store') }}',
                update: function (id) { return '{{ url('/desktop/operacion/catalogo-comercial/productos') }}/' + id; },
                imageTempStart: '{{ route('desktop.operacion.catalogo_comercial.productos.imagen_temporal.iniciar') }}',
                imageTempState: function (token) { return '{{ url('/desktop/operacion/catalogo-comercial/productos/imagen-temporal') }}/' + token + '/estado'; }
            };
            const catalogoState = {
                atributos: @json($atributosCatalogo),
                valores: @json($valoresCatalogo),
                unidades: @json($unidadesCatalogo),
                productoAtributosSeleccionados: [],
                productoValoresSeleccionados: {},
                productoCorridas: [],
                productoCorridaAtributoObjetivo: null,
                productoImagenSesion: null,
                productoImagenPollId: null,
            };

            $.ajaxSetup({
                cache: false,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            function escapeHtml(value) {
                return String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#39;');
            }

            function parseError(xhr) {
                if (xhr.responseJSON?.errors) {
                    return Object.values(xhr.responseJSON.errors).flat().join('\n');
                }
                return xhr.responseJSON?.message || 'No fue posible completar la operación.';
            }

            function showFeedback(type, message) {
                $feedback.removeClass('is-error is-success is-visible')
                    .addClass(type === 'error' ? 'is-error' : 'is-success')
                    .text(message)
                    .addClass('is-visible');
                window.clearTimeout(showFeedback._timer);
                showFeedback._timer = window.setTimeout(function () {
                    $feedback.removeClass('is-visible');
                }, 3800);
            }

            function toggleCheckState($input) {
                $input.closest('.desktop-product-check').toggleClass('is-selected', $input.is(':checked'));
            }

            function updateTypeCards() {
                const type = $('input[name="prd_tipo"]:checked').val() || 'variable';
                $('[data-product-type]').each(function () {
                    $(this).toggleClass('is-active', $(this).data('productType') === type);
                });
            }

            function updateImageMethodCards() {
                const method = $('input[name="prd_imagen_metodo"]:checked').val() || '';
                $('[data-image-method-label]').each(function () {
                    $(this).toggleClass('is-active', $(this).data('imageMethodLabel') === method);
                });
            }

            function clearImagePolling() {
                if (catalogoState.productoImagenPollId) {
                    clearInterval(catalogoState.productoImagenPollId);
                    catalogoState.productoImagenPollId = null;
                }
            }

            function setImagePreview(url) {
                const wrap = document.getElementById('producto-imagen-preview-wrap');
                const img = document.getElementById('producto-imagen-preview');
                if (!wrap || !img) return;

                if (url) {
                    wrap.classList.add('has-image');
                    img.src = url;
                } else {
                    wrap.classList.remove('has-image');
                    img.removeAttribute('src');
                }
            }

            function applyQrSession(session) {
                catalogoState.productoImagenSesion = session || null;
                const qrUrl = session?.qr_url || '';
                const mobileUrl = session?.mobile_url || '';
                const estado = session?.estado || {};

                $('#prd_imagen_temp_token').val(session?.token || '');
                $('#producto-imagen-qr').attr('src', qrUrl).toggleClass('is-visible', !!qrUrl);
                $('#producto-imagen-qr-placeholder').toggle(!qrUrl);
                $('#producto-imagen-mobile-link')
                    .attr('href', mobileUrl)
                    .text(mobileUrl)
                    .toggleClass('is-visible', !!mobileUrl);

                if (estado?.preview_url) {
                    setImagePreview(estado.preview_url);
                    $('#producto-imagen-mobile-status').text('Imagen recibida desde celular' + (estado.original_name ? ': ' + estado.original_name : '.'));
                } else {
                    $('#producto-imagen-mobile-status').text('Aún no se ha cargado una imagen desde el celular.');
                }
            }

            function pollQrState(token, showErrors) {
                if (!token) return;
                $.getJSON(rutas.imageTempState(token))
                    .done(function (resp) {
                        if ((resp.data || {}).preview_url) {
                            applyQrSession({
                                ...(catalogoState.productoImagenSesion || {}),
                                estado: resp.data || {},
                            });
                        }
                    })
                    .fail(function (xhr) {
                        if (showErrors) showFeedback('error', parseError(xhr));
                    });
            }

            function startQrSession() {
                $.post(rutas.imageTempStart, { public_base_url: window.location.origin })
                    .done(function (resp) {
                        applyQrSession(resp.data || null);
                        clearImagePolling();
                        if ((resp.data || {}).token) {
                            catalogoState.productoImagenPollId = setInterval(function () {
                                pollQrState((resp.data || {}).token, false);
                            }, 5000);
                        }
                    })
                    .fail(function (xhr) {
                        showFeedback('error', parseError(xhr));
                    });
            }

            function updateImageMethodUI() {
                const method = $('input[name="prd_imagen_metodo"]:checked').val() || '';
                updateImageMethodCards();
                $('.desktop-product-block').removeClass('is-active');
                if (method) {
                    $('.desktop-product-block[data-image-method="' + method + '"]').addClass('is-active');
                }

                if (method !== 'qr') {
                    clearImagePolling();
                    catalogoState.productoImagenSesion = null;
                    $('#prd_imagen_temp_token').val('');
                }

                if (method === 'qr' && !catalogoState.productoImagenSesion) {
                    startQrSession();
                }
            }

            function getSelectedAttributeIds() {
                return catalogoState.productoAtributosSeleccionados.map(Number);
            }

            function normalizeValuesMap(map) {
                const normalized = {};
                Object.entries(map || {}).forEach(function ([attrId, ids]) {
                    normalized[Number(attrId)] = [...new Set((ids || []).map(Number).filter((id) => id > 0))];
                });
                return normalized;
            }

            function isTallaAttribute(name) {
                const txt = String(name || '')
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .toLowerCase()
                    .trim();
                return ['talla', 'tallas', 'tamano', 'tamanio', 'medida'].includes(txt);
            }

            function naturalSort(a, b, numericOnly) {
                const aa = String(a || '').trim();
                const bb = String(b || '').trim();
                const rx = /^-?\d+(\.\d+)?$/;
                const na = rx.test(aa) ? Number(aa) : null;
                const nb = rx.test(bb) ? Number(bb) : null;
                if (na !== null && nb !== null) return na - nb;
                if (numericOnly) {
                    if (na !== null) return -1;
                    if (nb !== null) return 1;
                }
                return aa.localeCompare(bb, 'es', { numeric: true, sensitivity: 'base' });
            }

            function getAvailableValuesForAttribute(attributeId) {
                const attrId = Number(attributeId || 0);
                const selected = (catalogoState.productoValoresSeleccionados[attrId] || []).map(Number).filter((id) => id > 0);
                if (selected.length) return [...new Set(selected)];

                const atributo = catalogoState.atributos.find((item) => Number(item.atr_id) === attrId);
                const numeric = isTallaAttribute(atributo?.atr_nombre || '');
                return catalogoState.valores
                    .filter((item) => Number(item.vat_atr_id) === attrId)
                    .slice()
                    .sort(function (a, b) { return naturalSort(a?.vat_valor, b?.vat_valor, numeric); })
                    .map((item) => Number(item.vat_id))
                    .filter((id) => id > 0);
            }

            function countSkus() {
                if (($('input[name="prd_tipo"]:checked').val() || 'variable') !== 'variable') return 1;
                const attrs = getSelectedAttributeIds();
                if (!attrs.length) return 0;
                let total = 1;
                for (const attrId of attrs) {
                    const values = catalogoState.productoValoresSeleccionados[attrId] || [];
                    if (!values.length) return 0;
                    total *= values.length;
                }
                return total;
            }

            function renderSummary() {
                const type = $('input[name="prd_tipo"]:checked').val() || 'variable';
                const attrCount = getSelectedAttributeIds().length;
                const valueCount = Object.values(catalogoState.productoValoresSeleccionados).reduce(function (sum, values) {
                    return sum + (values?.length || 0);
                }, 0);
                const skuCount = countSkus();

                $('#producto-resumen-general').html([
                    ['Tipo actual', type === 'variable' ? 'Variable' : 'Simple'],
                    ['Atributos en corrida', type === 'variable' ? String(attrCount) : '0'],
                    ['Valores seleccionados', type === 'variable' ? String(valueCount) : '0'],
                    ['SKU a generar', String(skuCount)]
                ].map(function (item) {
                    return '<div class="desktop-product-summary__card"><small>' + item[0] + '</small><strong>' + item[1] + '</strong></div>';
                }).join(''));
            }

            function updateBaseFieldsState() {
                const isVariable = ($('input[name="prd_tipo"]:checked').val() || 'variable') === 'variable';
                const map = {
                    precio: '#prd_precio_base',
                    costo: '#prd_costo',
                    'stock-min': '#prd_stock_minimo',
                    'stock-max': '#prd_stock_maximo'
                };
                Object.entries(map).forEach(function ([key, selector]) {
                    $(selector).prop('readonly', isVariable);
                    $('[data-base-field="' + key + '"]').toggleClass('is-muted', isVariable);
                });
            }

            function renderAttributeSelect() {
                const html = catalogoState.atributos.map(function (atributo) {
                    const selected = getSelectedAttributeIds().includes(Number(atributo.atr_id)) ? ' selected' : '';
                    return '<option value="' + atributo.atr_id + '"' + selected + '>' + escapeHtml(atributo.atr_nombre) + '</option>';
                }).join('');
                $('#producto-atributos-select').html(html);
                if ($('#producto-atributos-select').hasClass('select2-hidden-accessible')) {
                    $('#producto-atributos-select').select2('destroy');
                }
                $('#producto-atributos-select').select2({
                    width: '100%',
                    placeholder: 'Selecciona atributos',
                    closeOnSelect: false,
                    allowClear: true
                });
                $('#producto-atributos-select').val(getSelectedAttributeIds().map(String)).trigger('change.select2');
            }

            function sanitizeCorridas() {
                const allowedAttrs = new Set(getSelectedAttributeIds().map(Number));
                const cleaned = [];
                (catalogoState.productoCorridas || []).forEach(function (corrida) {
                    const attrId = Number(corrida?.atr_id || 0);
                    if (!allowedAttrs.has(attrId)) return;
                    const allowedValues = new Set(getAvailableValuesForAttribute(attrId));
                    const valueIds = [...new Set((corrida?.valor_ids || []).map(Number).filter((id) => allowedValues.has(id)))];
                    cleaned.push({
                        uid: String(corrida?.uid || (Date.now() + '-' + Math.random().toString(36).slice(2))),
                        nombre: String(corrida?.nombre || '').trim(),
                        atr_id: attrId,
                        valor_ids: valueIds,
                        precio_base: Number(corrida?.precio_base || 0),
                        costo_base: Number(corrida?.costo_base || 0),
                        stock_minimo: Number(corrida?.stock_minimo || 0),
                        stock_maximo: Number(corrida?.stock_maximo || 0)
                    });
                });
                catalogoState.productoCorridas = cleaned;
            }

            function getUsedCorridaValueIds(attrId, excludeUid) {
                const used = new Set();
                (catalogoState.productoCorridas || []).forEach(function (corrida) {
                    if (Number(corrida?.atr_id || 0) !== Number(attrId)) return;
                    if (excludeUid && String(corrida?.uid || '') === String(excludeUid)) return;
                    (corrida.valor_ids || []).forEach(function (valueId) {
                        used.add(Number(valueId));
                    });
                });

                return used;
            }

            function getUnusedCorridaValueIds(attrId, excludeUid) {
                const used = getUsedCorridaValueIds(attrId, excludeUid);
                return getAvailableValuesForAttribute(attrId).filter(function (valueId) {
                    return !used.has(Number(valueId));
                });
            }

            function validateCorridas() {
                const corridas = catalogoState.productoCorridas || [];
                if (!corridas.length) {
                    return 'Debes agregar al menos una corrida para producto variable.';
                }

                const usedByAttr = {};

                for (let idx = 0; idx < corridas.length; idx += 1) {
                    const corrida = corridas[idx] || {};
                    const corridaLabel = 'La corrida ' + (idx + 1);
                    const attrId = Number(corrida.atr_id || 0);
                    const valueIds = [...new Set((corrida.valor_ids || []).map(Number).filter(function (id) { return id > 0; }))];
                    const allowedValues = new Set(getAvailableValuesForAttribute(attrId));
                    const stockMin = Number(corrida.stock_minimo || 0);
                    const stockMax = Number(corrida.stock_maximo || 0);

                    if (!attrId) return corridaLabel + ' debe indicar un atributo objetivo válido.';
                    if (!String(corrida.nombre || '').trim()) return corridaLabel + ' debe tener nombre.';
                    if (!valueIds.length) return corridaLabel + ' debe incluir al menos un valor.';
                    if (stockMax < stockMin) return corridaLabel + ' debe tener stock máximo mayor o igual al stock mínimo.';

                    usedByAttr[attrId] = usedByAttr[attrId] || {};

                    for (const valueId of valueIds) {
                        if (!allowedValues.has(valueId)) {
                            return corridaLabel + ' contiene valores que ya no pertenecen al atributo seleccionado.';
                        }

                        if (usedByAttr[attrId][valueId]) {
                            return 'Un mismo valor de atributo no puede repetirse en más de una corrida.';
                        }

                        usedByAttr[attrId][valueId] = true;
                    }
                }

                return null;
            }

            function corridaSuggestedTitle(attrId) {
                const atributo = catalogoState.atributos.find((item) => Number(item.atr_id) === Number(attrId));
                return (atributo?.atr_nombre || 'Grupo') + ' ' + ((catalogoState.productoCorridas || []).length + 1);
            }

            function renderCorridasConfig() {
                const attrs = getSelectedAttributeIds();
                if (!attrs.length) {
                    return '<div class="desktop-product-value-card__empty">Selecciona atributos y valores para crear corridas.</div>';
                }

                const rows = (catalogoState.productoCorridas || []).map(function (corrida, idx) {
                    const attrId = Number(corrida.atr_id || 0);
                    const attr = catalogoState.atributos.find((item) => Number(item.atr_id) === attrId);
                    const used = {};
                    (catalogoState.productoCorridas || []).forEach(function (other, otherIdx) {
                        if (otherIdx === idx) return;
                        if (Number(other.atr_id) !== attrId) return;
                        (other.valor_ids || []).forEach(function (valueId) { used[Number(valueId)] = true; });
                    });

                    const available = getAvailableValuesForAttribute(attrId)
                        .map(function (valueId) {
                            return catalogoState.valores.find(function (value) { return Number(value.vat_id) === Number(valueId); });
                        })
                        .filter(Boolean);

                    const options = available.map(function (value) {
                        const valueId = Number(value.vat_id);
                        const selected = (corrida.valor_ids || []).includes(valueId);
                        const disabled = !selected && used[valueId];
                        return '<option value="' + valueId + '"' + (selected ? ' selected' : '') + (disabled ? ' disabled' : '') + '>' + escapeHtml(value.vat_valor) + '</option>';
                    }).join('');

                    return '' +
                        '<div class="desktop-product-corrida" data-corrida-uid="' + escapeHtml(corrida.uid) + '" data-corrida-attr="' + attrId + '">' +
                            '<div class="desktop-product-corrida__head">' +
                                '<strong>Corrida ' + (idx + 1) + '</strong>' +
                                '<button type="button" class="desktop-btn desktop-btn--default js-prd-corrida-remove" data-corrida-uid="' + escapeHtml(corrida.uid) + '">Quitar</button>' +
                            '</div>' +
                            '<div class="desktop-product-corrida__grid">' +
                                '<div class="desktop-field span-3"><label>Nombre</label><input type="text" class="js-prd-corrida-nombre" data-idx="' + idx + '" maxlength="120" value="' + escapeHtml(corrida.nombre || '') + '"></div>' +
                                '<div class="desktop-field span-3"><label>Atributo objetivo</label><input type="text" readonly value="' + escapeHtml(attr?.atr_nombre || ('Atributo ' + attrId)) + '"></div>' +
                                '<div class="desktop-field span-6"><label>Valores incluidos</label><select class="desktop-select2 js-prd-corrida-valores" data-idx="' + idx + '" multiple>' + options + '</select></div>' +
                                '<div class="desktop-field span-3"><label>Costo</label><input type="number" min="0" step="0.01" class="js-prd-corrida-costo" data-idx="' + idx + '" value="' + escapeHtml(Number(corrida.costo_base || 0).toFixed(2)) + '"></div>' +
                                '<div class="desktop-field span-3"><label>Precio venta</label><input type="number" min="0" step="0.01" class="js-prd-corrida-precio" data-idx="' + idx + '" value="' + escapeHtml(Number(corrida.precio_base || 0).toFixed(2)) + '"></div>' +
                                '<div class="desktop-field span-3"><label>Stock mínimo base</label><input type="number" min="0" step="1" class="js-prd-corrida-stock-min" data-idx="' + idx + '" value="' + escapeHtml(Number(corrida.stock_minimo || 0)) + '"></div>' +
                                '<div class="desktop-field span-3"><label>Stock máximo base</label><input type="number" min="0" step="1" class="js-prd-corrida-stock-max" data-idx="' + idx + '" value="' + escapeHtml(Number(corrida.stock_maximo || 0)) + '"></div>' +
                            '</div>' +
                        '</div>';
                }).join('');

                return '' +
                    '<div class="desktop-product-corridas">' +
                        '<div class="desktop-product-corridas__toolbar">' +
                            '<div><strong>Agrupaciones de corridas</strong><p>Cada corrida define precio, costo y stock por grupo de valores.</p></div>' +
                            '<div class="desktop-product-corridas__controls">' +
                                '<select id="prd-corrida-atr-objetivo">' +
                                    attrs.map(function (attrId) {
                                        const attr = catalogoState.atributos.find(function (item) { return Number(item.atr_id) === Number(attrId); });
                                        const selected = Number(catalogoState.productoCorridaAtributoObjetivo || attrs[0]) === Number(attrId) ? ' selected' : '';
                                        const disabled = !getUnusedCorridaValueIds(attrId).length ? ' disabled' : '';
                                        return '<option value="' + attrId + '"' + selected + disabled + '>' + escapeHtml(attr?.atr_nombre || ('Atributo ' + attrId)) + '</option>';
                                    }).join('') +
                                '</select>' +
                                '<button type="button" class="desktop-btn desktop-btn--default" id="btn-prd-corrida-add">+ Agregar corrida</button>' +
                            '</div>' +
                        '</div>' +
                        '<div id="producto-corridas-list">' + (rows || '<div class="desktop-product-value-card__empty">Aún no hay corridas. Agrega al menos una.</div>') + '</div>' +
                    '</div>';
            }

            function renderVariableConfig() {
                renderAttributeSelect();

                if (($('input[name="prd_tipo"]:checked').val() || 'variable') !== 'variable') {
                    $('#producto-valores-config').html('');
                    renderSummary();
                    return;
                }

                const attrs = getSelectedAttributeIds();
                if (!attrs.length) {
                    $('#producto-valores-config').html('<div class="desktop-product-value-card__empty">Selecciona al menos un atributo para configurar la corrida.</div>');
                    renderSummary();
                    return;
                }

                const valuesHtml = attrs.map(function (attrId) {
                    const attr = catalogoState.atributos.find(function (item) { return Number(item.atr_id) === Number(attrId); });
                    const numeric = isTallaAttribute(attr?.atr_nombre || '');
                    const values = catalogoState.valores
                        .filter(function (item) { return Number(item.vat_atr_id) === Number(attrId); })
                        .slice()
                        .sort(function (a, b) { return naturalSort(a?.vat_valor, b?.vat_valor, numeric); });
                    const selected = (catalogoState.productoValoresSeleccionados[attrId] || []).map(Number);

                    const options = values.map(function (value) {
                        return '<option value="' + value.vat_id + '"' + (selected.includes(Number(value.vat_id)) ? ' selected' : '') + '>' + escapeHtml(value.vat_valor) + '</option>';
                    }).join('');

                    return '' +
                        '<div class="desktop-product-value-card">' +
                            '<h4>' + escapeHtml(attr?.atr_nombre || 'Atributo') + '</h4>' +
                            (values.length
                                ? '<select class="desktop-select2 producto-valores-select" name="atributo_valores[' + attrId + '][]" data-atributo-id="' + attrId + '" multiple>' + options + '</select>'
                                : '<div class="desktop-product-value-card__empty">Este atributo no tiene valores activos.</div>') +
                        '</div>';
                }).join('');

                sanitizeCorridas();
                $('#producto-valores-config').html(
                    '<div id="producto-valores-list">' + valuesHtml + '</div>' +
                    '<div id="producto-corridas-wrap">' + renderCorridasConfig() + '</div>'
                );

                $('#producto-valores-list .producto-valores-select').each(function () {
                    if ($(this).hasClass('select2-hidden-accessible')) $(this).select2('destroy');
                    $(this).select2({
                        width: '100%',
                        placeholder: 'Selecciona valores',
                        closeOnSelect: false,
                        allowClear: true
                    });
                });

                initCorridaValueSelects();
                renderSummary();
            }

            function initCorridaValueSelects() {
                $('#producto-corridas-list .js-prd-corrida-valores').each(function () {
                    if ($(this).hasClass('select2-hidden-accessible')) $(this).select2('destroy');
                    $(this).select2({
                        width: '100%',
                        placeholder: 'Selecciona valores',
                        closeOnSelect: false,
                        allowClear: true
                    });
                });
            }

            // Re-dibuja SOLO las corridas, sin tocar los selects de valores que el
            // usuario está manipulando (evita el destroy/re-init que causa la trabazón).
            function renderCorridasOnly() {
                const isVariable = ($('input[name="prd_tipo"]:checked').val() || 'variable') === 'variable';
                if (!isVariable || !getSelectedAttributeIds().length) {
                    renderSummary();
                    return;
                }
                sanitizeCorridas();
                $('#producto-corridas-wrap').html(renderCorridasConfig());
                initCorridaValueSelects();
                renderSummary();
            }

            function updateProductTypeUI() {
                const isVariable = ($('input[name="prd_tipo"]:checked').val() || 'variable') === 'variable';
                updateTypeCards();
                $('#producto-variable-shell').prop('hidden', !isVariable);
                if (!isVariable) {
                    catalogoState.productoAtributosSeleccionados = [];
                    catalogoState.productoValoresSeleccionados = {};
                    catalogoState.productoCorridas = [];
                }
                updateBaseFieldsState();
                renderVariableConfig();
            }

            function updateCategories(lineaId, selectedId) {
                if (!lineaId) {
                    $('#prd_ctg_id').html('<option value="">Selecciona una línea primero</option>').val('');
                    return;
                }

                const filtered = categoriasCatalogo.filter(function (item) {
                    return String(item.ctg_lna_id || '') === String(lineaId);
                });

                if (!filtered.length) {
                    $('#prd_ctg_id').html('<option value="">Sin conceptos para esta línea</option>').val('');
                    return;
                }

                const html = ['<option value="">Selecciona</option>'];
                filtered.forEach(function (item) {
                    html.push('<option value="' + item.ctg_id + '">' + escapeHtml(item.ctg_nombre) + '</option>');
                });
                $('#prd_ctg_id').html(html.join(''));
                if (selectedId) $('#prd_ctg_id').val(String(selectedId));
            }

            function updateModelOptions(selectedMarcaId, selectedModeloId) {
                const marcaId = Number(selectedMarcaId || 0);
                if (!marcaId) {
                    $('#prd_mdl_id').html('<option value="">Sin modelo</option>').val('');
                    $('#prd-modelo-wrap').hide();
                    return;
                }

                const modelos = modelosCatalogo.filter(function (modelo) {
                    return Array.isArray(modelo.marca_ids) && modelo.marca_ids.map(Number).includes(marcaId);
                });

                if (!modelos.length) {
                    $('#prd_mdl_id').html('<option value="">Sin modelo</option>').val('');
                    $('#prd-modelo-wrap').hide();
                    return;
                }

                const html = ['<option value="">Sin modelo</option>'];
                modelos.forEach(function (modelo) {
                    html.push('<option value="' + modelo.mdl_id + '">' + escapeHtml(modelo.mdl_nombre) + '</option>');
                });
                $('#prd_mdl_id').html(html.join('')).val(selectedModeloId ? String(selectedModeloId) : '');
                $('#prd-modelo-wrap').show();
            }

            function resetForm() {
                $form.get(0).reset();
                $('#prd_id').val('');
                $('#prd_codigo').val('');
                $('#prd_codigo_barras').val('');
                $('#prd_clave_sat').val('');
                $('#prd_prv_id').val('');
                $('#prd_precio_base').val('0.00');
                $('#prd_costo').val('0.00');
                $('#prd_stock_minimo').val('0');
                $('#prd_stock_maximo').val('0');
                $('#prd_imagen_temp_token').val('');
                $('#prd_imagen_reset').val('0');
                $('#prd_imagen_archivo').val('');
                $('#prd_imagen_url').val('');
                $('#prd-almacenes-checklist input[type="checkbox"]').prop('checked', false).each(function () { toggleCheckState($(this)); });
                $('#prd_tipo_variable').prop('checked', true);
                $('#prd_estatus').val('activo');
                $('input[name="prd_imagen_metodo"]').prop('checked', false);
                $('#prd_mdl_id').html('<option value="">Sin modelo</option>').val('');
                $('#prd-modelo-wrap').hide();
                $('#prd_ctg_id').html('<option value="">Selecciona una línea primero</option>').val('');

                const defaultUnit = (catalogoState.unidades || []).find(function (item) { return item.es_predeterminada; });
                if (defaultUnit) {
                    $('#prd_umd_id').val(String(defaultUnit.id));
                }

                catalogoState.productoAtributosSeleccionados = [];
                catalogoState.productoValoresSeleccionados = {};
                catalogoState.productoCorridas = [];
                catalogoState.productoCorridaAtributoObjetivo = null;
                catalogoState.productoImagenSesion = null;
                clearImagePolling();
                setImagePreview(null);
                $('#producto-imagen-qr').removeClass('is-visible').attr('src', '');
                $('#producto-imagen-qr-placeholder').show().text('Generando sesión de carga móvil...');
                $('#producto-imagen-mobile-link').removeClass('is-visible').text('').attr('href', '#');
                $('#producto-imagen-mobile-status').text('Aún no se ha cargado una imagen desde el celular.');

                updateProductTypeUI();
                updateImageMethodUI();
                startQrSession();
            }

            function normalizeCorridas(corridas) {
                return (corridas || [])
                    .filter(function (item) { return item && typeof item === 'object'; })
                    .map(function (item) {
                        const attrId = Number(item.crc_atr_id ?? item.prc_atr_id ?? 0);
                        const rawValueIds = item.crc_valor_ids ?? item.prc_valor_ids ?? [];
                        return {
                            uid: String(item.uid ?? item.crc_uid ?? item.prc_uid ?? (Date.now() + '-' + Math.random().toString(36).slice(2))),
                            nombre: String(item.crc_nombre ?? item.prc_nombre ?? '').trim(),
                            atr_id: attrId,
                            valor_ids: [...new Set((rawValueIds || []).map(Number).filter((id) => id > 0))],
                            precio_base: Number(item.crc_precio_base ?? item.prc_precio_base ?? 0),
                            costo_base: Number(item.crc_costo_base ?? item.prc_costo_base ?? 0),
                            stock_minimo: Number(item.crc_stock_minimo ?? item.prc_stock_minimo ?? 0),
                            stock_maximo: Number(item.crc_stock_maximo ?? item.prc_stock_maximo ?? 0)
                        };
                    })
                    .filter(function (item) { return item.atr_id > 0; });
            }

            function fillForm(data) {
                resetForm();
                $('#prd_id').val(data.prd_id || '');
                $('#prd_codigo').val(data.prd_codigo || '');
                $('#prd_codigo_barras').val(data.prd_codigo_barras || '');
                $('#prd_clave_sat').val(data.prd_clave_sat || '');
                $('#prd_nombre').val(data.prd_nombre || '');
                $('#prd_descripcion').val(data.prd_descripcion || '');
                $('#prd_precio_base').val(data.prd_precio_base ?? '0.00');
                $('#prd_costo').val(data.prd_costo ?? '0.00');
                $('#prd_stock_minimo').val(data.prd_stock_minimo ?? '0');
                $('#prd_stock_maximo').val(data.prd_stock_maximo ?? '0');
                $('#prd_mrc_id').val(String(data.prd_mrc_id || ''));
                $('#prd_prv_id').val(String(data.prd_prv_id || ''));
                $('#prd_lna_id').val(String(data.prd_lna_id || ''));
                updateCategories(data.prd_lna_id, data.prd_ctg_id);
                $('#prd_umd_id').val(String(data.prd_umd_id || ''));
                $('#prd_estatus').val(data.prd_estatus || 'activo');
                $('#prd-almacenes-checklist input[type="checkbox"]').each(function () {
                    const checked = (data.almacen_ids || []).includes(Number(this.value));
                    $(this).prop('checked', checked);
                    toggleCheckState($(this));
                });

                if ((data.prd_tipo || 'simple') === 'variable') {
                    $('#prd_tipo_variable').prop('checked', true);
                } else {
                    $('#prd_tipo_simple').prop('checked', true);
                }

                updateModelOptions(data.prd_mrc_id, data.prd_mdl_id);

                $('#prd_imagen_reset').val('0');
                $('#prd_imagen_archivo').val('');
                $('#prd_imagen_url').val(data.prd_imagen_tipo === 'url' ? (data.prd_imagen_url || '') : '');
                setImagePreview(data.prd_imagen_preview_url || null);
                if (data.prd_imagen_tipo === 'url') {
                    $('input[name="prd_imagen_metodo"][value="url"]').prop('checked', true);
                } else if (data.prd_imagen_tipo === 'archivo') {
                    $('input[name="prd_imagen_metodo"][value="archivo"]').prop('checked', true);
                } else {
                    $('input[name="prd_imagen_metodo"][value="archivo"]').prop('checked', true);
                }

                catalogoState.productoAtributosSeleccionados = (data.atributo_ids || []).map(Number);
                catalogoState.productoValoresSeleccionados = normalizeValuesMap(data.atributo_valores || {});
                catalogoState.productoCorridas = normalizeCorridas(data.corridas || []);
                catalogoState.productoCorridaAtributoObjetivo = Number((data.corridas && data.corridas[0] && (data.corridas[0].prc_atr_id || data.corridas[0].crc_atr_id)) || catalogoState.productoAtributosSeleccionados[0] || 0);

                updateProductTypeUI();
                updateImageMethodUI();
                startQrSession();
            }

            function syncCorridasFromUi() {
                const corridas = [];
                $('#producto-corridas-list .desktop-product-corrida').each(function () {
                    const $card = $(this);
                    corridas.push({
                        uid: String($card.data('corridaUid') || (Date.now() + '-' + Math.random().toString(36).slice(2))),
                        nombre: String($card.find('.js-prd-corrida-nombre').val() || '').trim(),
                        atr_id: Number($card.data('corridaAttr') || 0),
                        valor_ids: ($card.find('.js-prd-corrida-valores').val() || []).map(Number).filter((id) => id > 0),
                        precio_base: Number($card.find('.js-prd-corrida-precio').val() || 0),
                        costo_base: Number($card.find('.js-prd-corrida-costo').val() || 0),
                        stock_minimo: Number($card.find('.js-prd-corrida-stock-min').val() || 0),
                        stock_maximo: Number($card.find('.js-prd-corrida-stock-max').val() || 0)
                    });
                });
                catalogoState.productoCorridas = corridas;
            }

            function redirectToList(message) {
                const url = new URL(rutas.index, window.location.origin);
                url.searchParams.set('guardado', '1');
                url.searchParams.set('mensaje', message);
                window.location.href = url.toString();
            }

            resetForm();
            if (initialData) {
                fillForm(initialData);
            }

            $('#prd_lna_id').on('change', function () {
                updateCategories($(this).val(), '');
            });

            $('#prd_mrc_id').on('change', function () {
                $('#prd_mdl_id').val('');
                updateModelOptions($(this).val(), '');
            });

            $(document).on('change', '#prd-almacenes-checklist input[type="checkbox"]', function () {
                toggleCheckState($(this));
            });

            $('input[name="prd_tipo"]').on('change', updateProductTypeUI);

            $(document).on('change', '#producto-atributos-select', function () {
                catalogoState.productoAtributosSeleccionados = [...new Set(($(this).val() || []).map(Number))];
                Object.keys(catalogoState.productoValoresSeleccionados).forEach(function (attrId) {
                    if (!catalogoState.productoAtributosSeleccionados.includes(Number(attrId))) {
                        delete catalogoState.productoValoresSeleccionados[attrId];
                    }
                });
                renderVariableConfig();
                setTimeout(function () {
                    const $select = $('#producto-atributos-select');
                    if ($select.length && $select.hasClass('select2-hidden-accessible')) $select.select2('open');
                }, 0);
            });

            $(document).on('change', '.producto-valores-select', function () {
                const attrId = Number($(this).data('atributoId'));
                catalogoState.productoValoresSeleccionados[attrId] = [...new Set(($(this).val() || []).map(Number))];
                // Solo re-dibuja las corridas; el select que estás usando queda
                // intacto y el dropdown sigue abierto (closeOnSelect:false).
                renderCorridasOnly();
            });

            $(document).on('click', '#btn-prd-corrida-add', function () {
                syncCorridasFromUi();
                const attrId = Number($('#prd-corrida-atr-objetivo').val() || catalogoState.productoCorridaAtributoObjetivo || getSelectedAttributeIds()[0] || 0);
                catalogoState.productoCorridaAtributoObjetivo = attrId;
                if (!attrId) {
                    showFeedback('error', 'Selecciona el atributo objetivo de la corrida.');
                    return;
                }
                const availableValues = getAvailableValuesForAttribute(attrId);
                if (!availableValues.length) {
                    showFeedback('error', 'Primero selecciona valores para ese atributo.');
                    return;
                }
                const unusedValues = getUnusedCorridaValueIds(attrId);
                if (!unusedValues.length) {
                    showFeedback('error', 'Todos los valores seleccionados para ese atributo ya están asignados a una corrida.');
                    return;
                }
                const firstAvailable = unusedValues[0];
                catalogoState.productoCorridas.push({
                    uid: Date.now() + '-' + Math.random().toString(36).slice(2),
                    nombre: corridaSuggestedTitle(attrId),
                    atr_id: attrId,
                    valor_ids: firstAvailable ? [Number(firstAvailable)] : [],
                    precio_base: Number($('#prd_precio_base').val() || 0),
                    costo_base: Number($('#prd_costo').val() || 0),
                    stock_minimo: Number($('#prd_stock_minimo').val() || 0),
                    stock_maximo: Number($('#prd_stock_maximo').val() || 0)
                });
                renderVariableConfig();
            });

            $(document).on('change', '#prd-corrida-atr-objetivo', function () {
                catalogoState.productoCorridaAtributoObjetivo = Number($(this).val() || 0);
            });

            $(document).on('click', '.js-prd-corrida-remove', function () {
                const uid = String($(this).data('corridaUid') || '');
                if (!uid) return;
                syncCorridasFromUi();
                catalogoState.productoCorridas = (catalogoState.productoCorridas || []).filter(function (corrida) {
                    return String(corrida.uid || '') !== uid;
                });
                renderVariableConfig();
            });

            $(document).on('change', '.js-prd-corrida-valores', function () {
                const idx = Number($(this).data('idx') || -1);
                if (idx < 0 || !catalogoState.productoCorridas[idx]) return;
                catalogoState.productoCorridas[idx].valor_ids = [...new Set(($(this).val() || []).map(Number).filter((id) => id > 0))];
            });

            $(document).on('input', '.js-prd-corrida-nombre', function () {
                const idx = Number($(this).data('idx') || -1);
                if (idx < 0 || !catalogoState.productoCorridas[idx]) return;
                catalogoState.productoCorridas[idx].nombre = String($(this).val() || '').trim();
            });

            $(document).on('input', '.js-prd-corrida-precio,.js-prd-corrida-costo,.js-prd-corrida-stock-min,.js-prd-corrida-stock-max', function () {
                const idx = Number($(this).data('idx') || -1);
                if (idx < 0 || !catalogoState.productoCorridas[idx]) return;
                const corrida = catalogoState.productoCorridas[idx];
                if ($(this).hasClass('js-prd-corrida-precio')) corrida.precio_base = Number($(this).val() || 0);
                if ($(this).hasClass('js-prd-corrida-costo')) corrida.costo_base = Number($(this).val() || 0);
                if ($(this).hasClass('js-prd-corrida-stock-min')) corrida.stock_minimo = Number($(this).val() || 0);
                if ($(this).hasClass('js-prd-corrida-stock-max')) corrida.stock_maximo = Number($(this).val() || 0);
            });

            $('input[name="prd_imagen_metodo"]').on('change', function () {
                $('#prd_imagen_reset').val('0');
                updateImageMethodUI();
            });

            $('#prd_imagen_archivo').on('change', function (event) {
                $('#prd_imagen_reset').val('0');
                clearImagePolling();
                catalogoState.productoImagenSesion = null;
                $('#prd_imagen_temp_token').val('');
                const file = event.target.files?.[0];
                if (!file) return;
                setImagePreview(URL.createObjectURL(file));
            });

            $('#prd_imagen_url').on('input', function () {
                $('#prd_imagen_reset').val('0');
                clearImagePolling();
                catalogoState.productoImagenSesion = null;
                $('#prd_imagen_temp_token').val('');
                setImagePreview((this.value || '').trim() || null);
            });

            $('#btn-regenerar-qr-producto').on('click', function () {
                $('#prd_imagen_reset').val('0');
                startQrSession();
            });

            $('#btn-quitar-imagen-producto').on('click', function () {
                $('#prd_imagen_reset').val('1');
                clearImagePolling();
                catalogoState.productoImagenSesion = null;
                $('input[name="prd_imagen_metodo"]').prop('checked', false);
                updateImageMethodUI();
                $('#prd_imagen_archivo').val('');
                $('#prd_imagen_url').val('');
                $('#prd_imagen_temp_token').val('');
                setImagePreview(null);
                $('#producto-imagen-mobile-status').text('La imagen se quitará al guardar el producto.');
            });

            $('#btn-cancelar-producto-top, #btn-cancelar-producto-bottom').on('click', function () {
                window.location.href = rutas.index;
            });

            $form.on('submit', function (event) {
                event.preventDefault();
                const id = $('#prd_id').val();
                const formData = new FormData($form.get(0));
                const isVariable = ($('input[name="prd_tipo"]:checked').val() || 'variable') === 'variable';

                if (isVariable) {
                    syncCorridasFromUi();
                    sanitizeCorridas();
                    const corridasError = validateCorridas();
                    if (corridasError) {
                        showFeedback('error', corridasError);
                        return;
                    }

                    formData.delete('corridas');
                    (catalogoState.productoCorridas || []).forEach(function (corrida, idx) {
                        formData.append('corridas[' + idx + '][crc_nombre]', String(corrida.nombre || '').trim());
                        formData.append('corridas[' + idx + '][crc_atr_id]', String(Number(corrida.atr_id || 0)));
                        (corrida.valor_ids || []).forEach(function (valueId) {
                            formData.append('corridas[' + idx + '][crc_valor_ids][]', String(Number(valueId || 0)));
                        });
                        formData.append('corridas[' + idx + '][crc_precio_base]', String(Number(corrida.precio_base || 0).toFixed(2)));
                        formData.append('corridas[' + idx + '][crc_costo_base]', String(Number(corrida.costo_base || 0).toFixed(2)));
                        formData.append('corridas[' + idx + '][crc_stock_minimo]', String(Math.max(0, Math.trunc(Number(corrida.stock_minimo || 0)))));
                        formData.append('corridas[' + idx + '][crc_stock_maximo]', String(Math.max(0, Math.trunc(Number(corrida.stock_maximo || 0)))));
                    });
                    const first = catalogoState.productoCorridas[0];
                    formData.set('prd_precio_base', String(Number(first?.precio_base || 0).toFixed(2)));
                    formData.set('prd_costo', String(Number(first?.costo_base || 0).toFixed(2)));
                    formData.set('prd_stock_minimo', String(Math.max(0, Math.trunc(Number(first?.stock_minimo || 0)))));
                    formData.set('prd_stock_maximo', String(Math.max(0, Math.trunc(Number(first?.stock_maximo || 0)))));
                }

                if (id) formData.append('_method', 'PUT');

                $('#btn-submit-producto-top, #btn-submit-producto-bottom').prop('disabled', true);

                $.ajax({
                    url: id ? rutas.update(id) : rutas.store,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false
                }).done(function (response) {
                    redirectToList(response.message || 'Producto guardado correctamente.');
                }).fail(function (xhr) {
                    $('#btn-submit-producto-top, #btn-submit-producto-bottom').prop('disabled', false);
                    showFeedback('error', parseError(xhr));
                });
            });
        })();
    </script>

    {{-- Navegación lateral interna (scrollspy + clic). Solo UI, sin lógica de negocio. --}}
    <script>
        (function () {
            const scroller = document.getElementById('producto-scroll');
            const items = Array.prototype.slice.call(document.querySelectorAll('.desktop-product-nav__item'));
            if (!scroller || !items.length) return;

            const sections = items.map(function (item) {
                return document.getElementById(item.getAttribute('data-target'));
            });

            items.forEach(function (item) {
                item.addEventListener('click', function (event) {
                    event.preventDefault();
                    const target = document.getElementById(item.getAttribute('data-target'));
                    if (!target) return;
                    scroller.scrollTo({ top: Math.max(0, target.offsetTop - 8), behavior: 'smooth' });
                });
            });

            let ticking = false;
            function setActive() {
                ticking = false;
                const pos = scroller.scrollTop + 28;
                const atBottom = scroller.scrollTop + scroller.clientHeight >= scroller.scrollHeight - 4;
                let index = 0;
                for (let i = 0; i < sections.length; i++) {
                    if (sections[i] && sections[i].offsetTop <= pos) index = i;
                }
                if (atBottom) index = sections.length - 1;
                items.forEach(function (item, i) {
                    item.classList.toggle('is-active', i === index);
                });
            }

            scroller.addEventListener('scroll', function () {
                if (ticking) return;
                ticking = true;
                window.requestAnimationFrame(setActive);
            }, { passive: true });

            setActive();
        })();
    </script>
@endpush
