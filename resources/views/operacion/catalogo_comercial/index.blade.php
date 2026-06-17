@extends('layouts.app')

@section('title', 'Catálogo Comercial')

@push('vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/select2/select2.css') }}" />
    <style>
        .cc-table td,
        .cc-table th {
            vertical-align: middle;
        }

        .cc-onboarding {
            border: 1px solid var(--ls-border);
            border-radius: 0.8rem;
            background: linear-gradient(180deg, #f5f7ff 0%, var(--ls-accent-light) 100%);
            padding: 0.9rem 1rem;
        }

        .cc-onboarding .cc-step {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--ls-accent);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-right: 0.9rem;
        }

        .cc-onboarding .cc-step .dot {
            width: 0.46rem;
            height: 0.46rem;
            border-radius: 999px;
            background: var(--ls-accent);
        }

        .cc-toolbar {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.8rem;
            flex-wrap: wrap;
            margin-bottom: 0.9rem;
        }

        .cc-toolbar-left {
            min-width: 17rem;
            flex: 1 1 auto;
        }

        .cc-catalog-buttons {
            display: flex;
            gap: 0.45rem;
            flex-wrap: wrap;
            margin-bottom: 0.52rem;
        }

        .cc-catalog-buttons .btn {
            min-width: 7.5rem;
        }

        .cc-active-hint {
            font-size: 0.88rem;
            color: var(--ls-text-secondary);
        }

        .cc-base-layout {
            display: grid;
            grid-template-columns: 220px minmax(0, 1fr);
            gap: 1rem;
            align-items: start;
        }

        .cc-base-sidebar {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .cc-base-content {
            min-width: 0;
        }

        .cc-catalog-buttons--vertical {
            flex-direction: column;
            margin-bottom: 0;
        }

        .cc-catalog-buttons--vertical .btn {
            width: 100%;
            min-width: 0;
            justify-content: center;
        }

        @media (max-width: 991.98px) {
            .cc-base-layout {
                grid-template-columns: 1fr;
            }

            .cc-catalog-buttons--vertical {
                flex-direction: row;
                flex-wrap: wrap;
            }

            .cc-catalog-buttons--vertical .btn {
                width: auto;
                min-width: 7.5rem;
            }
        }

        .cc-product-shell {
            padding: 0;
            background: transparent;
            border: 0;
            border-radius: 0;
        }

        .cc-product-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 340px;
            gap: 1rem;
            align-items: start;
        }

        .cc-product-sidebar {
            border-left: 1px solid var(--ls-border);
            padding-left: 1rem;
        }

        .cc-side-panel {
            border: 1px solid var(--ls-border);
            border-radius: 0.85rem;
            background: linear-gradient(180deg, #ffffff 0%, #fafbff 100%);
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .cc-side-panel h6 {
            margin-bottom: 0.35rem;
        }

        .cc-side-panel p {
            color: var(--ls-text-secondary);
            margin-bottom: 0.85rem;
        }

        .cc-image-preview {
            border: 1px dashed #cfd8ea;
            border-radius: 0.85rem;
            background: #f8faff;
            min-height: 240px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-bottom: 0.85rem;
        }

        .cc-image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
        }

        .cc-image-preview.has-image img {
            display: block;
        }

        .cc-image-preview.has-image .cc-image-placeholder {
            display: none;
        }

        .cc-image-placeholder {
            padding: 1rem;
            text-align: center;
            color: var(--ls-text-secondary);
        }

        .cc-method-grid {
            display: grid;
            gap: 0.55rem;
            margin-bottom: 0.85rem;
        }

        .cc-method-option {
            display: flex;
            gap: 0.7rem;
            align-items: flex-start;
            border: 1px solid var(--ls-border);
            border-radius: 0.8rem;
            background: #fff;
            padding: 0.75rem 0.85rem;
            cursor: pointer;
        }

        .cc-method-option.is-active {
            border-color: var(--ls-accent);
            background: rgba(115, 103, 240, 0.06);
        }

        .cc-method-option input {
            margin-top: 0.2rem;
        }

        .cc-image-block {
            display: none;
        }

        .cc-image-block.is-active {
            display: block;
        }

        .cc-qr-shell {
            border: 1px dashed #d9e2f0;
            border-radius: 0.85rem;
            background: #fff;
            padding: 0.85rem;
            text-align: center;
        }

        .cc-qr-shell img {
            max-width: 220px;
            width: 100%;
            height: auto;
            border-radius: 0.5rem;
            background: #fff;
        }

        .cc-qr-link {
            display: block;
            font-size: 0.82rem;
            margin-top: 0.7rem;
            word-break: break-all;
        }

        .cc-image-status {
            font-size: 0.82rem;
            color: var(--ls-text-secondary);
            margin-top: 0.55rem;
        }

        @media (max-width: 991.98px) {
            .cc-product-layout {
                grid-template-columns: 1fr;
            }

            .cc-product-sidebar {
                border-left: 0;
                border-top: 1px solid var(--ls-border);
                padding-left: 0;
                padding-top: 1rem;
            }
        }

        .cc-product-type {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .cc-product-type .form-check {
            margin: 0;
            padding: 0;
        }

        .cc-product-type .form-check-label {
            display: block;
            min-width: 10rem;
            padding: 0.8rem 0.9rem;
            border: 1px solid var(--ls-border);
            border-radius: 0.8rem;
            cursor: pointer;
            background: #fff;
        }

        .cc-product-type .form-check-input:checked + .form-check-label {
            border-color: var(--ls-accent);
            background: rgba(115, 103, 240, 0.08);
            box-shadow: inset 0 0 0 1px rgba(115, 103, 240, 0.12);
        }

        .cc-attr-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 0.75rem;
        }

        .cc-attr-option {
            border: 1px solid var(--ls-border);
            border-radius: 0.8rem;
            padding: 0.7rem 0.8rem;
            background: #fff;
        }

        .cc-attr-option.is-selected {
            border-color: var(--ls-accent);
            background: rgba(115, 103, 240, 0.06);
        }

        .cc-value-card {
            border: 0;
            border-top: 1px solid var(--ls-border);
            border-radius: 0;
            background: transparent;
            padding: 0.9rem 0 0;
        }

        .cc-value-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.6rem;
        }

        .cc-value-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.45rem 0.65rem;
            border: 1px solid var(--ls-border);
            border-radius: 999px;
            background: #fff;
        }

        .cc-value-pill input {
            margin: 0;
        }

        .cc-select2-tags + .select2-container {
            width: 100% !important;
        }

        .cc-select2-tags + .select2-container .select2-selection--multiple {
            min-height: 2.45rem;
            border: 1px solid var(--ls-border);
            border-radius: var(--ls-radius);
            background: var(--ls-surface);
            box-shadow: 0 1px 2px rgba(10,37,64,.04);
            padding: 0.2rem 0.35rem;
        }

        .cc-select2-tags + .select2-container .select2-selection--multiple .select2-selection__choice {
            background: rgba(115, 103, 240, 0.1);
            border: 1px solid rgba(115, 103, 240, 0.22);
            color: var(--ls-accent);
            border-radius: 999px;
            font-size: 0.74rem;
            font-weight: 600;
            padding: 0.2rem 0.58rem;
            margin-top: 0.2rem;
            margin-right: 0.35rem;
        }

        .cc-select2-tags + .select2-container .select2-selection--multiple .select2-selection__choice__remove {
            color: var(--ls-accent);
            margin-right: 0.25rem;
        }

        .cc-select2-tags + .select2-container .select2-search__field {
            margin-top: 0.25rem;
        }

        .select2-dropdown {
            border: 1px solid var(--ls-border);
        }

        .cc-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.35rem 0.75rem;
        }

        .cc-summary-card {
            border: 0;
            border-left: 3px solid #d9e1ea;
            border-radius: 0;
            background: transparent;
            padding: 0.2rem 0 0.2rem 0.75rem;
        }

        .cc-summary-card small {
            display: block;
            color: var(--ls-text-muted);
            margin-bottom: 0.15rem;
        }

        .cc-summary-card strong {
            font-size: 1.1rem;
        }

        .cc-view-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.3rem;
            border: 1px solid var(--ls-border);
            border-radius: 999px;
            background: #fff;
        }

        .cc-view-toggle .btn {
            border-radius: 999px;
            min-width: 7.5rem;
        }

        .cc-products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
        }

        .cc-product-card {
            position: relative;
            border: 1px solid var(--ls-border);
            border-radius: var(--ls-radius-lg);
            background: var(--ls-surface);
            padding: 1rem;
            box-shadow: var(--ls-shadow-sm);
            transition: box-shadow .15s, border-color .15s;
        }
        .cc-product-card:hover {
            box-shadow: var(--ls-shadow);
            border-color: #c8d3de;
        }

        .cc-product-card__top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.85rem;
        }

        .cc-product-card__code {
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--ls-text-muted);
            margin-bottom: 0.25rem;
        }

        .cc-product-card__title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--ls-text-primary);
            margin-bottom: 0;
        }

        .cc-product-card__meta {
            color: var(--ls-text-secondary);
            font-size: 0.92rem;
            margin-bottom: 0.85rem;
            min-height: 2.6rem;
        }

        .cc-product-card__chips {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
            margin-bottom: 0.9rem;
        }

        .cc-product-card__chips .badge {
            padding: 0.45rem 0.6rem;
            font-weight: 600;
        }

        .cc-product-card__stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.7rem;
            margin-bottom: 0.95rem;
        }

        .cc-product-card__stat {
            border: 1px solid #eceffa;
            border-radius: 0.85rem;
            background: rgba(255, 255, 255, 0.9);
            padding: 0.7rem 0.8rem;
        }

        .cc-product-card__stat small {
            display: block;
            color: #7c7895;
            margin-bottom: 0.15rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .cc-product-card__stat strong {
            font-size: 1rem;
            color: #322f47;
        }

        .cc-product-card__attrs {
            border-top: 1px solid #eceffa;
            padding-top: 0.85rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
        }

        .cc-product-card__attrs .badge {
            padding: 0.45rem 0.7rem;
        }

        .cc-product-card__empty {
            border: 1px dashed #dfe4f8;
            border-radius: 1rem;
            background: linear-gradient(180deg, #fbfcff 0%, #f5f7ff 100%);
            padding: 2rem 1.5rem;
            text-align: center;
            color: #76718f;
        }
        .cc-products-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            border: 1px solid #e5e9f7;
            border-radius: 1rem;
            background: linear-gradient(135deg, #f9faff 0%, #f3f6ff 100%);
            padding: 1rem 1.1rem;
            margin-bottom: 1rem;
        }
        .cc-products-hero h5 { margin: 0 0 0.25rem; font-size: 1.05rem; }
        .cc-products-hero p { margin: 0; color: var(--ls-text-secondary); font-size: 0.9rem; }
        .cc-products-kpis {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 0.65rem;
            margin-bottom: 0.95rem;
        }
        .cc-products-kpi {
            border: 1px solid #e7ebfa;
            border-radius: 0.85rem;
            background: #fff;
            padding: 0.75rem 0.85rem;
        }
        .cc-products-kpi small {
            display: block;
            color: #7f7a9a;
            font-size: 0.76rem;
            margin-bottom: 0.1rem;
        }
        .cc-products-kpi strong { font-size: 1.05rem; color: #2f2b45; }
        .cc-products-toolbar {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 0.6rem;
            align-items: center;
            margin-bottom: 0.95rem;
        }

        .cc-sku-matrix-empty {
            border: 1px dashed #d7dfef;
            border-radius: 1rem;
            background: linear-gradient(180deg, #fbfcff 0%, #f5f8ff 100%);
            padding: 1.4rem;
            text-align: center;
        }

        .cc-sku-matrix-empty strong {
            display: block;
            color: var(--ls-text-primary);
            font-size: 1rem;
            margin-bottom: 0.35rem;
        }

        .cc-sku-matrix-header {
            display: grid;
            gap: 0.65rem;
            margin-bottom: 1rem;
            padding: 0.9rem 1rem;
            border: 1px solid #e7ebf5;
            border-radius: 1rem;
            background: linear-gradient(135deg, #fafbff 0%, #f4f7ff 100%);
        }

        .cc-sku-matrix-header small {
            display: block;
            color: #7e88a3;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-weight: 700;
            margin-bottom: 0.2rem;
        }

        .cc-sku-matrix-header h6 {
            margin: 0;
            color: #253858;
            font-size: 1.05rem;
        }

        .cc-sku-matrix-meta {
            display: flex;
            gap: 0.45rem;
            flex-wrap: wrap;
        }

        .cc-sku-matrix-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.45rem 0.65rem;
            border: 1px solid #dde5f4;
            border-radius: 999px;
            background: #fff;
            color: #54627d;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .cc-sku-matrix-table {
            --bs-table-bg: transparent;
            min-width: 860px;
        }

        .cc-sku-matrix-table thead th {
            vertical-align: bottom;
            background: #f3f6fc;
            border-color: #dbe3f1;
            color: #2c3d5a;
            font-weight: 700;
        }

        .cc-sku-matrix-rowhead {
            position: sticky;
            left: 0;
            z-index: 2;
            min-width: 180px;
            background: #f9fbff;
            border-right: 1px solid #dbe3f1;
        }

        .cc-sku-matrix-rowhead small {
            display: block;
            color: #7f8ba3;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-size: 0.7rem;
            margin-bottom: 0.2rem;
        }

        .cc-sku-matrix-colhead {
            min-width: 110px;
        }

        .cc-sku-matrix-colhead strong {
            display: block;
            font-size: 0.9rem;
            line-height: 1.2;
            margin-bottom: 0.12rem;
        }

        .cc-sku-matrix-colhead span {
            display: block;
            color: #7a859d;
            font-size: 0.72rem;
            line-height: 1.25;
            font-weight: 500;
        }

        .cc-sku-matrix-cell {
            min-width: 110px;
            padding: 0.3rem;
            background: #fff;
        }

        .cc-sku-variant-pill {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: .1rem;
            width: 100%;
            border: 1px solid #dbe3f1;
            border-radius: .4rem;
            padding: .28rem .45rem;
            background: #fff;
            cursor: pointer;
            transition: background .12s, border-color .12s, box-shadow .12s;
            font-family: inherit;
            text-align: left;
        }

        .cc-sku-variant-pill:hover {
            background: #f0f4ff;
            border-color: #6366f1;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, .12);
        }

        .cc-sku-variant-pill:active {
            background: #e8edff;
        }

        .cc-sku-variant-pill__code {
            font-size: .78rem;
            font-weight: 700;
            color: #253858;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }

        .cc-sku-variant-pill__price {
            font-size: .68rem;
            color: #3d5a80;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
            line-height: 1.2;
        }

        .cc-sku-variant-pill--inactivo {
            opacity: .55;
            border-style: dashed;
            background: #f9fafb;
        }

        .cc-sku-variant-pill--inactivo .cc-sku-variant-pill__code {
            color: #7a859d;
        }

        .cc-sku-matrix-cell--empty {
            min-width: 110px;
            padding: 0.3rem;
            background: #fbfcfe;
        }

        .cc-sku-matrix-na {
            min-height: 34px;
            border: 1px dashed #dde4f0;
            border-radius: .35rem;
            background: #f9fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #b0baca;
            font-size: .68rem;
            font-weight: 600;
        }
        @media (max-width: 991.98px) {
            .cc-products-toolbar { grid-template-columns: 1fr; }
        }

        /* ---- Filtro + tabla agrupada en vista matriz SKUs ---- */
        .cc-mtz-filter-bar {
            background: var(--ls-surface-2, #f8f9fc);
            border: 1px solid var(--ls-border, #e3e8f4);
            border-radius: .6rem;
            padding: .85rem 1rem;
            margin-bottom: 1rem;
        }

        .cc-mtz-filter-bar .form-label {
            font-size: .75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--ls-text-muted, #8a94a8);
            margin-bottom: .3rem;
        }

        .cc-mtz-table td,
        .cc-mtz-table th {
            vertical-align: middle;
        }

        .cc-mtz-meta {
            display: flex;
            flex-direction: column;
            gap: .12rem;
        }

        .cc-mtz-meta__title {
            font-weight: 700;
            color: var(--ls-text-primary, #253858);
            line-height: 1.2;
        }

        .cc-mtz-meta__sub {
            font-size: .75rem;
            color: var(--ls-text-secondary, #637594);
            line-height: 1.3;
        }

        .cc-mtz-variant-strip {
            display: flex;
            flex-wrap: wrap;
            gap: .22rem .28rem;
            align-items: center;
            min-width: 180px;
        }

        .cc-mtz-pill {
            display: inline-flex;
            align-items: baseline;
            gap: .18rem;
            border: 1px solid #dbe3f1;
            border-radius: .3rem;
            padding: .08rem .38rem;
            font-size: .74rem;
            line-height: 1.6;
            white-space: nowrap;
            background: #fff;
            cursor: pointer;
            font-family: inherit;
            transition: background .1s, border-color .1s, box-shadow .1s;
        }

        .cc-mtz-pill:hover {
            background: #f0f4ff;
            border-color: #6366f1;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, .12);
        }

        .cc-mtz-pill__label {
            font-weight: 700;
            font-size: .72rem;
            letter-spacing: .02em;
            color: #3a4d6b;
        }

        .cc-mtz-pill--inactivo {
            opacity: .55;
            border-style: dashed;
            background: #f9fafb;
        }

        .cc-mtz-pill--inactivo .cc-mtz-pill__label {
            color: #8a94a8;
        }

        .cc-editor-shell {
            border: 0;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
            overflow: hidden;
        }
        .cc-form-actions {
            position: sticky;
            bottom: 0;
            z-index: 10;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 0.6rem;
            flex-wrap: wrap;
            margin-top: 1rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--ls-border);
            background: linear-gradient(to top, #fff 70%, rgba(255, 255, 255, 0));
        }
        .cc-form-actions .btn {
            min-width: 10.5rem;
        }

        .cc-editor-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            padding: 0.95rem 1.25rem;
            border-bottom: 1px solid #edf0fa;
            background: linear-gradient(90deg, rgba(226, 237, 246, 0.45) 0%, rgba(255, 255, 255, 0.9) 100%);
        }

        .cc-editor-head h5 {
            margin-bottom: 0.2rem;
        }

        .cc-editor-head p {
            margin-bottom: 0;
            color: var(--ls-text-secondary);
        }

        .cc-product-form-body {
            padding: 1rem 1.25rem 1.1rem;
        }

        .cc-product-form-grid {
            --bs-gutter-x: 0.9rem;
            --bs-gutter-y: 0.9rem;
        }

        #panel-producto-form {
            margin: -1.25rem;
        }
    </style>
@endpush

@section('content')
@php($vistaActiva = $vistaActiva ?? 'base')
<x-section-header
    eyebrow="Comercial"
    icon="tabler-box-multiple"
    title="Catálogo Comercial"
    subtitle="Administra catálogos base, atributos dinámicos, productos y variantes SKU."
/>

<div class="card app-tabs-shell mb-4">
    <div class="app-tabs-shell__header d-none">
        <ul class="nav nav-tabs app-tabs-shell__tabs" role="tablist">
            <li class="nav-item" role="presentation"><button class="nav-link {{ $vistaActiva === 'base' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#panel-base" type="button">Catálogos Base</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link {{ $vistaActiva === 'atributos' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#panel-atributos" type="button">Atributos</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link {{ $vistaActiva === 'productos' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#panel-productos" type="button">Productos</button></li>
            <li class="nav-item d-none" id="nav-producto-form-item" role="presentation"><button class="nav-link" id="tab-producto-form-btn" data-bs-toggle="tab" data-bs-target="#panel-producto-form" type="button"><span id="tab-producto-form-label">Nuevo producto</span></button></li>
            <li class="nav-item" role="presentation"><button class="nav-link {{ $vistaActiva === 'skus' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#panel-skus" type="button">SKU / Variantes</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link {{ $vistaActiva === 'proveedores' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#panel-proveedores" type="button">Proveedores</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link {{ $vistaActiva === 'etiquetado' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#panel-etiquetado" type="button">Etiquetado</button></li>
        </ul>
    </div>
    <div class="app-tabs-shell__body">
        <div class="tab-content">
            <div class="tab-pane fade {{ $vistaActiva === 'base' ? 'show active' : '' }}" id="panel-base">
                <div class="cc-onboarding mb-3">
                    <span class="cc-step"><span class="dot"></span>Paso 1: elige catálogo</span>
                    <span class="cc-step"><span class="dot"></span>Paso 2: crea el registro</span>
                    <div class="mt-2 text-body-secondary">
                        Desde aquí das de alta <strong>Marca</strong>, <strong>Línea</strong>, <strong>Concepto</strong>, <strong>Unidad</strong> y <strong>Motivo</strong>.
                    </div>
                </div>

                <div class="cc-base-layout">
                    <aside class="cc-base-sidebar">
                        <div class="cc-catalog-buttons cc-catalog-buttons--vertical">
                            <button type="button" class="btn btn-outline-primary btn-catalogo-target" data-catalogo-target="marcas">Marca</button>
                            <button type="button" class="btn btn-outline-primary btn-catalogo-target" data-catalogo-target="modelos">Modelo</button>
                            <button type="button" class="btn btn-outline-primary btn-catalogo-target" data-catalogo-target="lineas">Línea</button>
                            <button type="button" class="btn btn-outline-primary btn-catalogo-target" data-catalogo-target="categorias">Concepto</button>
                            <button type="button" class="btn btn-outline-primary btn-catalogo-target" data-catalogo-target="unidades">Unidad</button>
                            <button type="button" class="btn btn-outline-primary btn-catalogo-target" data-catalogo-target="motivos">Motivo</button>
                        </div>
                        <div class="cc-active-hint">
                            Catálogo activo: <strong id="lbl-catalogo-activo-inline">Marcas</strong>
                        </div>
                    </aside>
                    <div class="cc-base-content">
                        <div class="d-flex align-items-start justify-content-end gap-2 mb-3">
                            @if($permisosUI['crear'])
                                <button class="btn btn-primary" id="btn-nuevo-catalogo">Nuevo registro</button>
                            @endif
                        </div>
                <select class="d-none" id="catalogo-tipo">
                    <option value="marcas">Marcas</option>
                    <option value="modelos">Modelos</option>
                    <option value="lineas">Líneas</option>
                    <option value="categorias">Conceptos</option>
                    <option value="unidades">Unidades de Medida</option>
                    <option value="motivos">Motivos</option>
                </select>

                        <div id="catalogo-wrap">
                            <div class="card">
                                <div class="card-header">
                                    Listado de <span id="lbl-catalogo-activo" class="fw-semibold">Marcas</span>
                                </div>
                                <div class="card-datatable table-responsive">
                                    <table id="catalogo-table" class="table table-bordered cc-table">
                                        <thead>
                                            <tr>
                                                <th>Nombre</th>
                                                <th>Código</th>
                                                <th>Clave</th>
                                                <th>Estatus</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div id="modelos-wrap" style="display:none;">
                            <div class="card">
                                <div class="card-header">Listado de Modelos</div>
                                <div class="card-datatable table-responsive">
                                    <table id="modelos-table" class="table table-bordered cc-table">
                                        <thead>
                                            <tr>
                                                <th>Nombre</th>
                                                <th>Clave</th>
                                                <th>Marcas</th>
                                                <th>Estatus</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade {{ $vistaActiva === 'atributos' ? 'show active' : '' }}" id="panel-atributos">
                <div class="row g-3 mb-3">
                    <div class="col-md-6 d-flex justify-content-end">
                        @if($permisosUI['crear'])
                            <button class="btn btn-primary" id="btn-nuevo-atributo">Nuevo atributo</button>
                        @endif
                    </div>
                    <div class="col-md-6 d-flex justify-content-end">
                        @if($permisosUI['crear'])
                            <button class="btn btn-outline-primary" id="btn-nuevo-valor">Nuevo valor de atributo</button>
                        @endif
                    </div>
                </div>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">Atributos</div>
                            <div class="card-datatable table-responsive">
                                <table id="atributos-table" class="table table-bordered cc-table">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Clave</th>
                                            <th>Tipo</th>
                                            <th>Estatus</th>
                                            <th>Valores</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">Valores de Atributo</div>
                            <div class="card-datatable table-responsive">
                                <table id="valores-table" class="table table-bordered cc-table">
                                    <thead>
                                        <tr>
                                            <th>Atributo</th>
                                            <th>Valor</th>
                                            <th>Clave</th>
                                            <th>Estatus</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade {{ $vistaActiva === 'productos' ? 'show active' : '' }}" id="panel-productos">
                <div class="cc-products-hero">
                    <div>
                        <h5>Productos de tu catálogo</h5>
                        <p>Consulta, organiza y actualiza tus productos en un solo lugar.</p>
                    </div>
                </div>
                <div class="cc-products-kpis">
                    <div class="cc-products-kpi"><small>Total de productos</small><strong id="kpi-productos-total">0</strong></div>
                    <div class="cc-products-kpi"><small>Activos</small><strong id="kpi-productos-activos">0</strong></div>
                    <div class="cc-products-kpi"><small>Con corridas</small><strong id="kpi-productos-variantes">0</strong></div>
                    <div class="cc-products-kpi"><small>Sin proveedor</small><strong id="kpi-productos-sin-proveedor">0</strong></div>
                </div>
                <div class="cc-products-toolbar">
                    <div class="input-group">
                        <span class="input-group-text"><i class="ti tabler-search"></i></span>
                        <input id="productos-busqueda-rapida" type="text" class="form-control" placeholder="Buscar por nombre, código, marca o proveedor">
                    </div>
                    <div class="cc-view-toggle" role="group" aria-label="Vista de productos">
                        <button type="button" class="btn btn-primary btn-sm" id="btn-productos-vista-grid">Tarjetas</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btn-productos-vista-list">Tabla</button>
                    </div>
                    <div class="d-flex justify-content-end">
                        @if($permisosUI['crear'])
                            <button class="btn btn-primary" id="btn-nuevo-producto">Agregar producto</button>
                        @endif
                    </div>
                </div>
                <div class="mb-3" id="productos-grid-wrap">
                    <div id="productos-grid" class="cc-products-grid"></div>
                </div>
                <div class="card d-none" id="productos-list-wrap">
                    <div class="card-datatable table-responsive">
                        <table id="productos-table" class="table table-bordered cc-table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th>Detalles</th>
                                    <th>Categoría</th>
                                    <th>Corridas</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="panel-producto-form">
                <div class="cc-editor-shell">
                    <div class="cc-editor-head">
                        <div>
                            <h5 id="producto-form-heading">Agregar producto</h5>
                            <p>Llena los datos principales, elige su categoría y configura sus corridas si las necesitas.</p>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-cancelar-producto-tab">Regresar al listado</button>
                    </div>
                    <div class="cc-product-form-body">
                        <form id="form-producto" enctype="multipart/form-data">
                            <input type="hidden" id="prd_id">
                            <input type="hidden" name="prd_imagen_temp_token" id="prd_imagen_temp_token">
                            <input type="hidden" name="prd_imagen_reset" id="prd_imagen_reset" value="0">
                            <div class="cc-product-shell">
                                <div class="cc-product-layout">
                                    <div>
                                        <div class="alert alert-primary mb-3" role="alert">
                                            <strong>Guía rápida:</strong> Primero captura la información general y después, si aplica, configura las corridas.
                                        </div>
                                        <div class="row cc-product-form-grid">
                                            <div class="col-md-8">
                                                <label class="form-label">Nombre del producto <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="prd_nombre" id="prd_nombre" required maxlength="180">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Código interno</label>
                                                <input type="text" class="form-control" name="prd_codigo" id="prd_codigo" maxlength="40" readonly placeholder="Se crea automáticamente al guardar">
                                            </div>
                                            <div class="col-md-4"><label class="form-label">Marca <span class="text-danger">*</span></label><select class="form-select" name="prd_mrc_id" id="prd_mrc_id" required><option value="">Selecciona una marca</option>@foreach($opciones['marcas'] as $item)<option value="{{ $item->mrc_id }}">{{ $item->mrc_nombre }}</option>@endforeach</select></div>
                                            <div class="col-md-4" id="prd-modelo-wrap" style="display:none;">
                                                <label class="form-label">Modelo <small class="text-body-secondary">(opcional)</small></label>
                                                <select class="form-select" name="prd_mdl_id" id="prd_mdl_id">
                                                    <option value="">Sin modelo</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4"><label class="form-label">Proveedor principal</label><select class="form-select" name="prd_prv_id" id="prd_prv_id"><option value="">Sin proveedor asignado</option>@foreach($opciones['proveedores'] as $item)<option value="{{ $item->prv_id }}">{{ $item->prv_nombre_empresa }}</option>@endforeach</select></div>
                                            <div class="col-md-4"><label class="form-label">Línea <span class="text-danger">*</span></label><select class="form-select" name="prd_lna_id" id="prd_lna_id" required><option value="">Selecciona una línea</option>@foreach($opciones['lineas'] as $item)<option value="{{ $item->lna_id }}">{{ $item->lna_nombre }}</option>@endforeach</select></div>
                                            <div class="col-md-4"><label class="form-label">Concepto</label><select class="form-select" name="prd_ctg_id" id="prd_ctg_id"><option value="">Selecciona un concepto</option>@foreach($opciones['categorias'] as $item)<option value="{{ $item->ctg_id }}">{{ $item->ctg_nombre }}</option>@endforeach</select></div>
                                            <div class="col-md-4"><label class="form-label">Descripción</label><select class="form-select" name="prd_dsc_id" id="prd_dsc_id"><option value="">Selecciona una descripción</option>@foreach($opciones['descripciones'] as $item)<option value="{{ $item->dsc_id }}">{{ $item->dsc_nombre }}</option>@endforeach</select></div>
                                            <div class="col-md-4"><label class="form-label">Unidad de venta <span class="text-danger">*</span></label><select class="form-select" name="prd_umd_id" id="prd_umd_id" required><option value="">Selecciona una unidad</option>@foreach($opciones['unidades'] as $item)<option value="{{ $item->umd_id }}"{{ $item->umd_es_predeterminada ? ' data-predeterminada="1"' : '' }}>{{ $item->umd_nombre }} ({{ $item->umd_codigo }}){{ $item->umd_es_predeterminada ? ' ★' : '' }}</option>@endforeach</select></div>
                                            <div class="col-12">
                                                <label class="form-label">Almacenes permitidos <span class="text-danger">*</span></label>
                                                <div id="prd-almacenes-checklist" class="cc-attr-grid">
                                                    @foreach($opciones['almacenes'] as $item)
                                                        <label class="cc-attr-option">
                                                            <input class="form-check-input me-2" type="checkbox" name="almacen_ids[]" value="{{ $item->alm_id }}">
                                                            <span class="fw-semibold">{{ $item->alm_nombre }}</span>
                                                            <small class="d-block text-body-secondary">{{ $item->sucursal?->scl_nombre }}</small>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div class="col-md-2"><label class="form-label">Código de barras</label><input type="text" class="form-control" name="prd_codigo_barras" id="prd_codigo_barras" maxlength="80" placeholder="Opcional"></div>
                                            <div class="col-md-2"><label class="form-label">Clave fiscal (SAT)</label><input type="text" class="form-control" name="prd_clave_sat" id="prd_clave_sat" maxlength="20" placeholder="Opcional"></div>
                                            <div class="col-12">
                                                <label class="form-label d-block">¿Cómo quieres manejar este producto?</label>
                                                <div class="cc-product-type">
                                                    <div class="form-check">
                                                        <input class="form-check-input d-none" type="radio" name="prd_tipo" id="prd_tipo_variable" value="variable" checked>
                                                        <label class="form-check-label" for="prd_tipo_variable">
                                                            <span class="fw-semibold d-block">Variable</span>
                                                            <small class="text-body-secondary">Ideal cuando el producto cambia por talla, color u otra característica.</small>
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input d-none" type="radio" name="prd_tipo" id="prd_tipo_simple" value="simple">
                                                        <label class="form-check-label" for="prd_tipo_simple">
                                                            <span class="fw-semibold d-block">Producto simple</span>
                                                            <small class="text-body-secondary">Se guarda como un solo producto, sin combinaciones.</small>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="cc-summary-grid" id="producto-resumen-general"></div>
                                            </div>
                                            <div class="col-md-4"><label class="form-label">Precio de venta <span class="text-danger">*</span></label><input type="number" class="form-control" name="prd_precio_base" id="prd_precio_base" min="0" step="0.01" value="0.00" required></div>
                                            <div class="col-md-4"><label class="form-label">Costo</label><input type="number" class="form-control" name="prd_costo" id="prd_costo" min="0" step="0.01" value="0.00"></div>
                                            <div class="col-md-4"><label class="form-label">Estado</label><select class="form-select" name="prd_estatus" id="prd_estatus"><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select></div>
                                            <div class="col-md-4"><label class="form-label">Stock mínimo base <span class="text-danger">*</span></label><input type="number" class="form-control" name="prd_stock_minimo" id="prd_stock_minimo" min="0" step="1" value="0" required></div>
                                            <div class="col-md-4"><label class="form-label">Stock máximo base <span class="text-danger">*</span></label><input type="number" class="form-control" name="prd_stock_maximo" id="prd_stock_maximo" min="0" step="1" value="0" required></div>
                                            <div class="col-12"><label class="form-label">Descripción para referencia</label><textarea class="form-control" name="prd_descripcion" id="prd_descripcion" rows="3" placeholder="Ejemplo: producto de temporada, material, detalles importantes..."></textarea></div>
                                            <div class="col-12" id="producto-variable-shell" style="display:none;">
                                                <div class="cc-value-card">
                                                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                                                        <div>
                                                            <h6 class="mb-1">Configuración de corridas</h6>
                                                            <p class="mb-0 text-body-secondary">Elige los atributos y luego marca los valores para crear las corridas.</p>
                                                        </div>
                                                        <span class="badge bg-label-info">Códigos automáticos</span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Atributos para corridas</label>
                                                        <div id="producto-atributos-checklist" class="cc-attr-grid"></div>
                                                    </div>
                                                    <div id="producto-valores-config"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <aside class="cc-product-sidebar">
                                        <div class="cc-side-panel">
                                            <h6>Foto principal</h6>
                                            <p>Agrega la imagen principal del producto. Puedes subirla aquí o desde tu celular.</p>
                                            <div class="cc-image-preview" id="producto-imagen-preview-wrap">
                                                <div class="cc-image-placeholder">Aquí verás la foto principal del producto.</div>
                                                <img id="producto-imagen-preview" alt="Vista previa del producto">
                                            </div>
                                            <div class="cc-method-grid">
                                                <label class="cc-method-option">
                                                        <input type="radio" name="prd_imagen_metodo" value="archivo">
                                                        <div>
                                                        <strong>Subir desde esta computadora</strong>
                                                        <div class="small text-body-secondary">Elige una imagen guardada en este equipo.</div>
                                                        </div>
                                                </label>
                                                <label class="cc-method-option">
                                                        <input type="radio" name="prd_imagen_metodo" value="url">
                                                        <div>
                                                        <strong>Usar enlace de imagen</strong>
                                                        <div class="small text-body-secondary">Pega el enlace público de la imagen.</div>
                                                        </div>
                                                </label>
                                                <label class="cc-method-option">
                                                        <input type="radio" name="prd_imagen_metodo" value="qr">
                                                        <div>
                                                        <strong>Subir desde celular con QR</strong>
                                                        <div class="small text-body-secondary">Escanea el código y carga la foto desde tu teléfono.</div>
                                                        </div>
                                                </label>
                                            </div>
                                            <div class="cc-image-block is-active" data-image-method="archivo">
                                                <label class="form-label">Archivo de imagen</label>
                                                <input type="file" class="form-control" name="prd_imagen_archivo" id="prd_imagen_archivo" accept="image/*">
                                                <div class="form-text">Formatos sugeridos: JPG, PNG o WEBP. Tamaño máximo: 5 MB.</div>
                                            </div>
                                            <div class="cc-image-block" data-image-method="url">
                                                <label class="form-label">URL externa</label>
                                                <input type="url" class="form-control" name="prd_imagen_url" id="prd_imagen_url" maxlength="500" placeholder="https://...">
                                            </div>
                                            <div class="cc-image-block" data-image-method="qr">
                                                <div class="cc-qr-shell">
                                                    <img id="producto-imagen-qr" alt="QR de carga móvil" style="display:none;">
                                                    <div id="producto-imagen-qr-placeholder" class="text-body-secondary">Generando sesión de carga móvil...</div>
                                                    <a href="#" target="_blank" rel="noopener" class="cc-qr-link" id="producto-imagen-mobile-link" style="display:none;"></a>
                                                    <div class="cc-image-status" id="producto-imagen-mobile-status">Aún no se ha cargado una imagen desde el celular.</div>
                                                    <div class="cc-image-status">Tip: usa una dirección de red local o dominio accesible desde tu teléfono.</div>
                                                </div>
                                            </div>
                                            <div class="d-grid gap-2 mt-3">
                                                <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-regenerar-qr-producto">Generar nuevo QR</button>
                                                <button type="button" class="btn btn-outline-danger btn-sm" id="btn-quitar-imagen-producto">Quitar foto</button>
                                            </div>
                                        </div>
                                    </aside>
                                </div>
                            </div>
                            <div class="cc-form-actions">
                                <button type="button" class="btn btn-outline-secondary btn-cancelar-producto-tab">Descartar cambios</button>
                                <button type="submit" class="btn btn-primary">Guardar y continuar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade {{ $vistaActiva === 'skus' ? 'show active' : '' }}" id="panel-skus">
                {{-- Selector de producto oculto en DOM para compatibilidad con JS existente --}}
                <select class="d-none" id="flt-sku-producto">
                    <option value="">Todos los productos</option>
                    @foreach($opciones['productos'] as $producto)
                        <option value="{{ $producto->prd_id }}">{{ $producto->prd_codigo }} - {{ $producto->prd_nombre }}</option>
                    @endforeach
                </select>
                <div id="sku-filtros-dinamicos-shell" class="d-none">
                    <div id="sku-filtros-dinamicos"></div>
                    <div class="d-none" id="sku-filtros-orden-note"></div>
                </div>
                <div class="d-none" id="skus-table-shell">
                    <table id="skus-table" class="table table-bordered cc-table">
                        <thead><tr>
                            <th>Producto</th><th>SKU</th><th>Barcode SKU</th>
                            <th>Nombre</th><th>Combinación</th><th>Precio</th>
                            <th>Stock</th><th>Estatus</th><th>Acciones</th>
                        </tr></thead>
                    </table>
                </div>
                <div id="skus-matriz-shell">
                    <div class="cc-mtz-filter-bar mb-3">
                        {{-- Fila 1: Producto + Buscar + Marca --}}
                        <div class="row g-2 mb-2">
                            <div class="col-md-5">
                                <label class="form-label" for="mtz-flt-producto">Producto</label>
                                <select id="mtz-flt-producto" class="form-select form-select-sm">
                                    <option value="">Todos los productos</option>
                                    @foreach($opciones['productos'] as $producto)
                                        <option value="{{ $producto->prd_id }}">{{ $producto->prd_codigo }} - {{ $producto->prd_nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="mtz-flt-buscar">Buscar</label>
                                <input id="mtz-flt-buscar" type="text" class="form-control form-control-sm" placeholder="Código, SKU o nombre">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="mtz-flt-mrc">Marca</label>
                                <select id="mtz-flt-mrc" class="form-select form-select-sm">
                                    <option value="">Todas</option>
                                    @foreach($opciones['marcas'] as $marca)
                                        <option value="{{ $marca->mrc_id }}">{{ $marca->mrc_nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        {{-- Fila 2: Modelo + Línea + Concepto + Botones --}}
                        <div class="row g-2 mb-2">
                            <div class="col-md-3">
                                <label class="form-label" for="mtz-flt-mdl">Modelo</label>
                                <select id="mtz-flt-mdl" class="form-select form-select-sm">
                                    <option value="">Todos</option>
                                    @foreach($opciones['modelos'] as $modelo)
                                        <option value="{{ $modelo->mdl_id }}">{{ $modelo->mdl_nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="mtz-flt-lna">Línea</label>
                                <select id="mtz-flt-lna" class="form-select form-select-sm">
                                    <option value="">Todas</option>
                                    @foreach($opciones['lineas'] as $linea)
                                        <option value="{{ $linea->lna_id }}">{{ $linea->lna_nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="mtz-flt-ctg">Concepto</label>
                                <select id="mtz-flt-ctg" class="form-select form-select-sm">
                                    <option value="">Todos</option>
                                    @foreach($opciones['categorias'] as $categoria)
                                        <option value="{{ $categoria->ctg_id }}">{{ $categoria->ctg_nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-mtz-limpiar" title="Limpiar filtros">
                                    <i class="ti tabler-eraser me-1"></i>Limpiar
                                </button>
                            </div>
                        </div>
                        {{-- Fila 3: Filtros dinámicos de atributos (Talla, Color, etc.) — aparece al seleccionar producto --}}
                        <div class="row g-2 d-none" id="mtz-atributos-wrap"></div>
                    </div>
                    <div class="card">
                        <div class="card-body p-0">
                            <div class="table-responsive" id="skus-matriz-wrap">
                                <div class="p-3 text-body-secondary">Cargando...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade {{ $vistaActiva === 'proveedores' ? 'show active' : '' }}" id="panel-proveedores">
                <div class="d-flex justify-content-end mb-3">
                    @if($permisosUI['crear'])
                        <button class="btn btn-primary" id="btn-nuevo-proveedor">Nuevo proveedor</button>
                    @endif
                </div>
                <div class="card">
                    <div class="card-datatable table-responsive">
                        <table id="proveedores-table" class="table table-bordered cc-table">
                            <thead>
                                <tr>
                                    <th>Empresa</th>
                                    <th>Asesor de ventas</th>
                                    <th>Concepto</th>
                                    <th>Contactos</th>
                                    <th>Razón social</th>
                                    <th>RFC</th>
                                    <th>Correo</th>
                                    <th>Condiciones de pago</th>
                                    <th>Tiempo de respuesta</th>
                                    <th>Estatus</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade {{ $vistaActiva === 'etiquetado' ? 'show active' : '' }}" id="panel-etiquetado">
                <div class="alert alert-info mb-3" role="alert">
                    Genera una etiqueta PDF estándar para impresión térmica Zebra. Incluye código de barras del SKU, nombre del producto y precio.
                </div>
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="fw-semibold">Configuración Zebra (preparación sin impresora)</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-restaurar-zebra">Restaurar valores sugeridos</button>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="etq-usar-config-manual" value="1">
                            <label class="form-check-label" for="etq-usar-config-manual">
                                Ajustar manualmente tamaño físico y barcode
                            </label>
                        </div>
                        <div class="row g-3 d-none" id="etq-config-manual-wrap">
                            <div class="col-md-2">
                                <label class="form-label">Ancho (mm)</label>
                                <input type="number" step="0.1" min="20" max="120" class="form-control" id="etq-width-mm">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Alto (mm)</label>
                                <input type="number" step="0.1" min="10" max="120" class="form-control" id="etq-height-mm">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Margen izq (mm)</label>
                                <input type="number" step="0.1" min="0" max="10" class="form-control" id="etq-margin-left-mm">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Margen der (mm)</label>
                                <input type="number" step="0.1" min="0" max="10" class="form-control" id="etq-margin-right-mm">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Margen sup (mm)</label>
                                <input type="number" step="0.1" min="0" max="10" class="form-control" id="etq-margin-top-mm">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Margen inf (mm)</label>
                                <input type="number" step="0.1" min="0" max="10" class="form-control" id="etq-margin-bottom-mm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Alto barcode (mm)</label>
                                <input type="number" step="0.1" min="4" max="25" class="form-control" id="etq-barcode-height-mm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Grosor barra (xres)</label>
                                <input type="number" step="0.01" min="0.2" max="0.8" class="form-control" id="etq-barcode-xres">
                            </div>
                        </div>
                        <div class="form-text text-body-secondary mt-2">
                            Recomendado Zebra: imprimir al 100% de escala y sin “Ajustar a página”.
                        </div>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-5">
                        <label class="form-label">Filtrar por producto</label>
                        <select class="form-select" id="flt-etq-producto">
                            <option value="">Todos los productos</option>
                            @foreach($opciones['productos'] as $producto)
                                <option value="{{ $producto->prd_id }}">{{ $producto->prd_codigo }} - {{ $producto->prd_nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Copias por etiqueta</label>
                        <input type="number" class="form-control" id="etq-copias" min="1" max="50" value="1">
                    </div>
                </div>
                <div class="card">
                    <div class="card-datatable table-responsive">
                        <table id="etiquetado-table" class="table table-bordered cc-table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>SKU</th>
                                    <th>Nombre etiqueta</th>
                                    <th>Precio</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-catalogo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="form-catalogo">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-catalogo-title">Nuevo registro de Marca</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="cat_id" />
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre</label>
                            <input type="text" class="form-control" name="nombre" id="cat_nombre" maxlength="120" required>
                        </div>
                        <div class="col-md-3" id="cat-codigo-wrap">
                            <label class="form-label">Código</label>
                            <input type="text" class="form-control" name="codigo" id="cat_codigo" maxlength="20">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estatus</label>
                            <select class="form-select" name="estatus" id="cat_estatus" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        {{-- Campo exclusivo para Conceptos (antes Categorías): línea a la que pertenece --}}
                        <div id="cat-categoria-linea-wrap" style="display:none;" class="col-md-5">
                            <label class="form-label">Línea <span class="text-danger">*</span></label>
                            <select class="form-select" name="lna_id" id="cat_lna_id">
                                <option value="">Selecciona una línea</option>
                                @foreach($opciones['lineas'] as $linea)
                                    <option value="{{ $linea->lna_id }}">{{ $linea->lna_nombre }}</option>
                                @endforeach
                            </select>
                            <div class="form-text text-body-secondary">El mismo nombre de concepto puede usarse en distintas líneas.</div>
                        </div>

                        {{-- Campos exclusivos para Unidades de Medida --}}
                        <div id="cat-unidad-extra" style="display:none;" class="col-12">
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label class="form-label">Tipo de cantidad</label>
                                    <select class="form-select" name="tipo_cantidad" id="cat_tipo_cantidad">
                                        <option value="entero">Entero (ej. piezas, cajas)</option>
                                        <option value="decimal">Decimal (ej. metros, litros)</option>
                                    </select>
                                    <div class="form-text text-body-secondary">Define si la cantidad admite decimales al usar esta unidad.</div>
                                </div>
                                <div class="col-md-7 d-flex align-items-center pt-3">
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" name="es_predeterminada" id="cat_es_predeterminada" value="1">
                                        <label class="form-check-label" for="cat_es_predeterminada">
                                            <span class="fw-semibold">Unidad predeterminada</span>
                                            <span class="d-block text-body-secondary" style="font-size:0.8rem;">Se seleccionará automáticamente al registrar un nuevo producto.</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-modelo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="form-modelo">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-modelo-title">Nuevo Modelo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="mdl_id" />
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre</label>
                            <input type="text" class="form-control" name="nombre" id="mdl_nombre" maxlength="120" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Clave <small class="text-body-secondary">(opcional)</small></label>
                            <input type="text" class="form-control" name="clave" id="mdl_clave" maxlength="40">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Estatus</label>
                            <select class="form-select" name="estatus" id="mdl_estatus" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Marcas asociadas</label>
                            <div class="form-text text-body-secondary mb-2">Selecciona las marcas a las que pertenece este modelo. Al elegir una marca en el producto, solo aparecerán los modelos asociados a ella.</div>
                            <div id="mdl-marcas-checklist" class="cc-attr-grid">
                                @foreach($opciones['marcas'] as $marca)
                                    <label class="cc-attr-option">
                                        <input class="form-check-input me-2" type="checkbox" name="marca_ids[]" value="{{ $marca->mrc_id }}">
                                        <span class="fw-semibold">{{ $marca->mrc_nombre }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @if($opciones['marcas']->isEmpty())
                                <div class="text-body-secondary">No hay marcas activas. Da de alta una marca primero.</div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-atributo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="form-atributo">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-atributo-title">Atributo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="atr_id">
                    <div class="row g-3">
                        <div class="col-md-5"><label class="form-label">Nombre</label><input type="text" class="form-control" name="atr_nombre" id="atr_nombre" required maxlength="120"></div>
                        <div class="col-md-7"><label class="form-label">Tipo</label><input type="text" class="form-control" name="atr_tipo" id="atr_tipo" maxlength="40"></div>
                        <div class="col-md-4"><label class="form-label">Estatus</label><select class="form-select" name="atr_estatus" id="atr_estatus"><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-valor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="form-valor">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-valor-title">Valor de atributo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="vat_id">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Atributo</label>
                            <select class="form-select" name="vat_atr_id" id="vat_atr_id" required>
                                <option value="">Selecciona</option>
                                @foreach($opciones['atributos'] as $atributo)
                                    <option value="{{ $atributo->atr_id }}">{{ $atributo->atr_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-8"><label class="form-label">Valor</label><input type="text" class="form-control" name="vat_valor" id="vat_valor" required maxlength="120"></div>
                        <div class="col-md-4"><label class="form-label">Estatus</label><select class="form-select" name="vat_estatus" id="vat_estatus"><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-sku" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <form id="form-sku">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-sku-title">SKU / Variante</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="psk_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Producto</label>
                            <select class="form-select" name="psk_prd_id" id="psk_prd_id" required>
                                <option value="">Selecciona</option>
                                @foreach($opciones['productos'] as $producto)
                                    <option value="{{ $producto->prd_id }}">{{ $producto->prd_codigo }} - {{ $producto->prd_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3"><label class="form-label">Código SKU</label><input type="text" class="form-control" name="psk_codigo" id="psk_codigo" required maxlength="60"></div>
                        <div class="col-md-3"><label class="form-label">Código de barras SKU</label><input type="text" class="form-control" name="psk_codigo_barras" id="psk_codigo_barras" maxlength="80"></div>
                        <div class="col-md-6"><label class="form-label">Nombre SKU</label><input type="text" class="form-control" name="psk_nombre" id="psk_nombre" maxlength="180"></div>
                        <div class="col-md-3"><label class="form-label">Costo</label><input type="number" class="form-control" name="psk_costo" id="psk_costo" min="0" step="0.01"></div>
                        <div class="col-md-3"><label class="form-label">Precio venta</label><input type="number" class="form-control" name="psk_precio" id="psk_precio" min="0" step="0.01"></div>
                        <div class="col-md-3"><label class="form-label">Stock mínimo variante</label><input type="number" class="form-control" name="psk_stock_minimo" id="psk_stock_minimo" min="0" step="1"></div>
                        <div class="col-md-3"><label class="form-label">Stock máximo variante</label><input type="number" class="form-control" name="psk_stock_maximo" id="psk_stock_maximo" min="0" step="1"></div>
                        <div class="col-md-8" id="psk-valores-wrap">
                            <label class="form-label">Valores de atributo de la variante</label>
                            <select class="form-select" name="valor_atributo_ids[]" id="psk_valor_atributo_ids" multiple size="8" required>
                                @foreach($opciones['valores'] as $valor)
                                    <option value="{{ $valor->vat_id }}">{{ $valor->atributo?->atr_nombre }}: {{ $valor->vat_valor }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4"><label class="form-label">Estatus</label><select class="form-select" name="psk_estatus" id="psk_estatus"><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-proveedor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <form id="form-proveedor">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-proveedor-title">Nuevo proveedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="prv_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre de la empresa</label>
                            <input type="text" class="form-control" name="prv_nombre_empresa" id="prv_nombre_empresa" maxlength="180" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nombre del asesor de ventas</label>
                            <input type="text" class="form-control" name="prv_nombre_asesor_ventas" id="prv_nombre_asesor_ventas" maxlength="180">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Categoría</label>
                            <input type="text" class="form-control" name="prv_categoria" id="prv_categoria" maxlength="120">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Razón social</label>
                            <input type="text" class="form-control" name="prv_razon_social" id="prv_razon_social" maxlength="180">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">RFC</label>
                            <input type="text" class="form-control text-uppercase" name="prv_rfc" id="prv_rfc" maxlength="13">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Correo</label>
                            <input type="email" class="form-control" name="prv_correo" id="prv_correo" maxlength="160">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tiempo de respuesta</label>
                            <input type="text" class="form-control" name="prv_tiempo_respuesta" id="prv_tiempo_respuesta" maxlength="120">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Estatus</label>
                            <select class="form-select" name="prv_estatus" id="prv_estatus">
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Condiciones de pago</label>
                            <input type="text" class="form-control" name="prv_condiciones_pago" id="prv_condiciones_pago" maxlength="220">
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">Números de contacto</label>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-agregar-contacto-proveedor">Agregar número</button>
                            </div>
                            <div id="proveedor-contactos-list" class="d-flex flex-column gap-2"></div>
                            <div class="form-text text-body-secondary">Captura uno o varios números de contacto.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-confirmar-eliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Confirmar eliminación</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><p id="confirmar-eliminar-mensaje" class="mb-0"></p></div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-danger" id="btn-confirmar-eliminar">Eliminar</button></div>
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
(() => {
    const permisos = @json($permisosUI);
    const rutas = {
        baseData: (tipo) => '{{ url('/operacion/catalogo-comercial/catalogos') }}/' + tipo + '/data',
        baseShow: (tipo, id) => '{{ url('/operacion/catalogo-comercial/catalogos') }}/' + tipo + '/' + id,
        baseStore: (tipo) => '{{ url('/operacion/catalogo-comercial/catalogos') }}/' + tipo,
        baseUpdate: (tipo, id) => '{{ url('/operacion/catalogo-comercial/catalogos') }}/' + tipo + '/' + id,
        baseEstatus: (tipo, id) => '{{ url('/operacion/catalogo-comercial/catalogos') }}/' + tipo + '/' + id + '/estatus',
        baseDelete: (tipo, id) => '{{ url('/operacion/catalogo-comercial/catalogos') }}/' + tipo + '/' + id,

        modelosData: '{{ route('operacion.catalogo_comercial.modelos.data') }}',
        modeloShow: (id) => '{{ url('/operacion/catalogo-comercial/modelos') }}/' + id,
        modeloStore: '{{ route('operacion.catalogo_comercial.modelos.store') }}',
        modeloUpdate: (id) => '{{ url('/operacion/catalogo-comercial/modelos') }}/' + id,
        modeloEstatus: (id) => '{{ url('/operacion/catalogo-comercial/modelos') }}/' + id + '/estatus',
        modeloDelete: (id) => '{{ url('/operacion/catalogo-comercial/modelos') }}/' + id,
        modelosPorMarca: (marcaId) => '{{ url('/operacion/catalogo-comercial/modelos-por-marca') }}/' + marcaId,

        atributosData: '{{ route('operacion.catalogo_comercial.atributos.data') }}',
        atributoShow: (id) => '{{ url('/operacion/catalogo-comercial/atributos') }}/' + id,
        atributoStore: '{{ route('operacion.catalogo_comercial.atributos.store') }}',
        atributoUpdate: (id) => '{{ url('/operacion/catalogo-comercial/atributos') }}/' + id,
        atributoEstatus: (id) => '{{ url('/operacion/catalogo-comercial/atributos') }}/' + id + '/estatus',
        atributoDelete: (id) => '{{ url('/operacion/catalogo-comercial/atributos') }}/' + id,

        valoresData: '{{ route('operacion.catalogo_comercial.valores.data') }}',
        valorShow: (id) => '{{ url('/operacion/catalogo-comercial/valores-atributo') }}/' + id,
        valorStore: '{{ route('operacion.catalogo_comercial.valores.store') }}',
        valorUpdate: (id) => '{{ url('/operacion/catalogo-comercial/valores-atributo') }}/' + id,
        valorEstatus: (id) => '{{ url('/operacion/catalogo-comercial/valores-atributo') }}/' + id + '/estatus',
        valorDelete: (id) => '{{ url('/operacion/catalogo-comercial/valores-atributo') }}/' + id,

        productosData: '{{ route('operacion.catalogo_comercial.productos.data') }}',
        productoShow: (id) => '{{ url('/operacion/catalogo-comercial/productos') }}/' + id,
        productoStore: '{{ route('operacion.catalogo_comercial.productos.store') }}',
        productoUpdate: (id) => '{{ url('/operacion/catalogo-comercial/productos') }}/' + id,
        productoEstatus: (id) => '{{ url('/operacion/catalogo-comercial/productos') }}/' + id + '/estatus',
        productoDelete: (id) => '{{ url('/operacion/catalogo-comercial/productos') }}/' + id,
        productoImagenTempStart: '{{ route('operacion.catalogo_comercial.productos.imagen_temporal.iniciar') }}',
        productoImagenTempState: (token) => '{{ url('/operacion/catalogo-comercial/productos/imagen-temporal') }}/' + token + '/estado',

        skusData: '{{ route('operacion.catalogo_comercial.skus.data') }}',
        skusFiltros: '{{ route('operacion.catalogo_comercial.skus.filtros') }}',
        skusMatriz: '{{ route('operacion.catalogo_comercial.skus.matriz') }}',
        skusAgrupados: '{{ route('operacion.catalogo_comercial.skus.agrupados') }}',
        skuShow: (id) => '{{ url('/operacion/catalogo-comercial/skus') }}/' + id,
        skuStore: '{{ route('operacion.catalogo_comercial.skus.store') }}',
        skuUpdate: (id) => '{{ url('/operacion/catalogo-comercial/skus') }}/' + id,
        skuEstatus: (id) => '{{ url('/operacion/catalogo-comercial/skus') }}/' + id + '/estatus',
        skuDelete: (id) => '{{ url('/operacion/catalogo-comercial/skus') }}/' + id,
        skuEtiqueta: (id) => '{{ url('/operacion/catalogo-comercial/skus') }}/' + id + '/etiqueta-pdf',

        proveedoresData: '{{ route('operacion.catalogo_comercial.proveedores.data') }}',
        proveedorShow: (id) => '{{ url('/operacion/catalogo-comercial/proveedores') }}/' + id,
        proveedorStore: '{{ route('operacion.catalogo_comercial.proveedores.store') }}',
        proveedorUpdate: (id) => '{{ url('/operacion/catalogo-comercial/proveedores') }}/' + id,
        proveedorEstatus: (id) => '{{ url('/operacion/catalogo-comercial/proveedores') }}/' + id + '/estatus',
        proveedorDelete: (id) => '{{ url('/operacion/catalogo-comercial/proveedores') }}/' + id,
    };
    const zebraDefaults = @json(config('etiquetado.formatos.zebra_50x30', []));

    const modalCatalogo = new bootstrap.Modal(document.getElementById('modal-catalogo'));
    const modalModelo = new bootstrap.Modal(document.getElementById('modal-modelo'));
    const modalAtributo = new bootstrap.Modal(document.getElementById('modal-atributo'));
    const modalValor = new bootstrap.Modal(document.getElementById('modal-valor'));
    const modalSku = new bootstrap.Modal(document.getElementById('modal-sku'));
    const modalProveedor = new bootstrap.Modal(document.getElementById('modal-proveedor'));
    const modalConfirm = new bootstrap.Modal(document.getElementById('modal-confirmar-eliminar'));
    const tabProductoFormButton = document.getElementById('tab-producto-form-btn');
    const tabProductosButton = document.querySelector('[data-bs-target="#panel-productos"]');
    let deleteAction = null;
    let productosCache = [];
    let tablaProductos = null;
    let productosViewMode = 'cuadricula';
    const catalogoLabels = {
        marcas: 'Marca',
        modelos: 'Modelo',
        lineas: 'Línea',
        categorias: 'Concepto',
        unidades: 'Unidad',
        conceptos: 'Concepto',
        motivos: 'Motivo',
    };
    const catalogoListadoLabels = {
        marcas: 'Marcas',
        modelos: 'Modelos',
        lineas: 'Líneas',
        categorias: 'Conceptos',
        unidades: 'Unidades',
        conceptos: 'Conceptos',
        motivos: 'Motivos',
    };
    const catalogoState = {
        atributos: [],
        valores: [],
        unidades: [],
        categorias: [],
        proveedores: [],
        productoAtributosSeleccionados: [],
        productoValoresSeleccionados: {},
        productoCorridas: [],
        productoCorridaAtributoObjetivo: null,
        productoImagenActual: null,
        productoImagenSesion: null,
        productoImagenPollId: null,
        skuFiltrosMeta: [],
        skuViewMode: 'matriz',
    };

    const estatusBadge = (estatus) => estatus === 'activo'
        ? '<span class="ls-badge ls-badge-success">Activo</span>'
        : '<span class="ls-badge ls-badge-danger">Inactivo</span>';

    function parseErrorMessage(xhr) {
        if (xhr.responseJSON?.message) return xhr.responseJSON.message;
        if (xhr.responseJSON?.errors) {
            return Object.values(xhr.responseJSON.errors).flat().join('\n');
        }
        return 'No fue posible completar la operación.';
    }

    function escapeAttr(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function escapeHtml(value) {
        return escapeAttr(value);
    }

    function limpiarPollingImagenProducto() {
        if (catalogoState.productoImagenPollId) {
            clearInterval(catalogoState.productoImagenPollId);
            catalogoState.productoImagenPollId = null;
        }
    }

    function establecerPreviewImagenProducto(url) {
        const $wrap = $('#producto-imagen-preview-wrap');
        const $img = $('#producto-imagen-preview');

        if (url) {
            $img.attr('src', url);
            $wrap.addClass('has-image');
            catalogoState.productoImagenActual = url;
            return;
        }

        $img.attr('src', '');
        $wrap.removeClass('has-image');
        catalogoState.productoImagenActual = null;
    }

    function actualizarUiMetodoImagen() {
        const metodo = $('input[name="prd_imagen_metodo"]:checked').val() || '';

        $('.cc-method-option').removeClass('is-active');
        $('input[name="prd_imagen_metodo"]').each(function () {
            $(this).closest('.cc-method-option').toggleClass('is-active', $(this).is(':checked'));
        });

        $('.cc-image-block').removeClass('is-active');
        if (metodo) {
            $('.cc-image-block[data-image-method="' + metodo + '"]').addClass('is-active');
        }

        if (metodo !== 'qr') {
            limpiarPollingImagenProducto();
            catalogoState.productoImagenSesion = null;
            $('#prd_imagen_temp_token').val('');
        }

        if (metodo === 'qr' && !catalogoState.productoImagenSesion) {
            iniciarSesionImagenProducto();
        }
    }

    function aplicarEstadoSesionImagenProducto(sesion) {
        catalogoState.productoImagenSesion = sesion || null;

        const qrUrl = sesion?.qr_url || '';
        const mobileUrl = sesion?.mobile_url || '';
        const estado = sesion?.estado || {};

        $('#prd_imagen_temp_token').val(sesion?.token || '');
        $('#producto-imagen-qr').attr('src', qrUrl).toggle(!!qrUrl);
        $('#producto-imagen-qr-placeholder').toggle(!qrUrl);
        $('#producto-imagen-mobile-link').attr('href', mobileUrl).text(mobileUrl).toggle(!!mobileUrl);

        if (estado?.preview_url) {
            establecerPreviewImagenProducto(estado.preview_url);
            $('#producto-imagen-mobile-status').text('Imagen recibida desde celular' + (estado.original_name ? ': ' + estado.original_name : '.') );
        } else {
            $('#producto-imagen-mobile-status').text('Todavía no se ha cargado una imagen desde el celular.');
        }
    }

    function iniciarSesionImagenProducto() {
        $.post(rutas.productoImagenTempStart, {
            public_base_url: window.location.origin,
        })
            .done(function (resp) {
                aplicarEstadoSesionImagenProducto(resp.data || null);
                limpiarPollingImagenProducto();
                if ((resp.data || {}).token) {
                    catalogoState.productoImagenPollId = setInterval(function () {
                        consultarEstadoImagenProducto((resp.data || {}).token, false);
                    }, 5000);
                }
            })
            .fail((xhr) => AppUI.showMessage('Error', parseErrorMessage(xhr), 'error'));
    }

    function consultarEstadoImagenProducto(token, mostrarErrores = true) {
        if (!token) {
            return;
        }

        $.getJSON(rutas.productoImagenTempState(token))
            .done(function (resp) {
                if ((resp.data || {}).preview_url) {
                    aplicarEstadoSesionImagenProducto({
                        ...(catalogoState.productoImagenSesion || {}),
                        estado: resp.data || {},
                    });
                }
            })
            .fail(function (xhr) {
                if (mostrarErrores) {
                    AppUI.showMessage('Error', parseErrorMessage(xhr), 'error');
                }
            });
    }

    function buildActions(section, id, name, isActive) {
        if (!permisos.editar && !permisos.inactivar && !permisos.eliminar) {
            return '<span class="text-body-secondary">Sin acciones</span>';
        }

        let html = '<div class="dropdown"><button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">Acciones</button><ul class="dropdown-menu dropdown-menu-end">';
        if (permisos.editar) html += '<li><button type="button" class="dropdown-item" data-action="edit-' + section + '" data-id="' + id + '">Editar</button></li>';
        if (permisos.inactivar) html += '<li><button type="button" class="dropdown-item" data-action="toggle-' + section + '" data-id="' + id + '" data-next="' + (isActive ? 'inactivo' : 'activo') + '">' + (isActive ? 'Inactivar' : 'Activar') + '</button></li>';
        if (permisos.eliminar) html += '<li><hr class="dropdown-divider"></li><li><button type="button" class="dropdown-item text-danger" data-action="delete-' + section + '" data-id="' + id + '" data-name="' + (name || '') + '">Eliminar</button></li>';
        html += '</ul></div>';
        return html;
    }

    function abrirTabProducto(titulo, etiquetaTab) {
        $('#nav-producto-form-item').removeClass('d-none');
        $('#producto-form-heading').text(titulo);
        $('#tab-producto-form-label').text(etiquetaTab || titulo);

        bootstrap.Tab.getOrCreateInstance(tabProductoFormButton).show();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function cerrarTabProducto(resetearFormulario = true) {
        if (resetearFormulario) {
            prepararNuevoProducto();
        }

        $('#nav-producto-form-item').addClass('d-none');
        bootstrap.Tab.getOrCreateInstance(tabProductosButton).show();
    }

    function obtenerVistaProductos() {
        return productosViewMode;
    }

    function aplicarVistaProductos(vista) {
        const esLista = vista === 'lista';
        productosViewMode = esLista ? 'lista' : 'cuadricula';

        $('#productos-grid-wrap').toggleClass('d-none', esLista);
        $('#productos-list-wrap').toggleClass('d-none', !esLista);

        $('#btn-productos-vista-grid')
            .toggleClass('btn-primary', !esLista)
            .toggleClass('btn-outline-primary', esLista);

        $('#btn-productos-vista-list')
            .toggleClass('btn-primary', esLista)
            .toggleClass('btn-outline-primary', !esLista);

        if (esLista && $.fn.DataTable.isDataTable('#productos-table')) {
            setTimeout(function () {
                $('#productos-table').DataTable().columns.adjust().draw(false);
            }, 50);
        }
    }

    function confirmDelete(message, onConfirm) {
        deleteAction = onConfirm;
        $('#confirmar-eliminar-mensaje').text(message);
        modalConfirm.show();
    }

    function construirFilaContactoProveedor(valor = '') {
        return `
            <div class="input-group proveedor-contacto-item">
                <input
                    type="text"
                    class="form-control"
                    name="numeros_contacto[]"
                    maxlength="30"
                    value="${escapeAttr(valor || '')}"
                    placeholder="Ej. 55 1234 5678"
                >
                <button type="button" class="btn btn-outline-danger btn-remover-contacto-proveedor">Quitar</button>
            </div>
        `;
    }

    function agregarContactoProveedor(valor = '') {
        $('#proveedor-contactos-list').append(construirFilaContactoProveedor(valor));
    }

    function limpiarContactosProveedor(contactos = []) {
        $('#proveedor-contactos-list').html('');

        if (!contactos.length) {
            agregarContactoProveedor('');
            return;
        }

        contactos.forEach(function (contacto) {
            agregarContactoProveedor(contacto);
        });
    }

    function prepararNuevoProveedor() {
        $('#form-proveedor')[0].reset();
        $('#prv_id').val('');
        $('#prv_estatus').val('activo');
        $('#modal-proveedor-title').text('Nuevo proveedor');
        limpiarContactosProveedor([]);
    }

    function obtenerTipoCatalogoActual() {
        return $('#catalogo-tipo').val();
    }

    function sincronizarUIcatalogo(tipo) {
        const label = catalogoLabels[tipo] || 'Catálogo';
        const labelListado = catalogoListadoLabels[tipo] || label;
        $('#lbl-catalogo-activo').text(labelListado);
        $('#lbl-catalogo-activo-inline').text(labelListado);
        $('#modal-catalogo-title').text('Nuevo registro de ' + label);
        $('#cat-codigo-wrap').toggle(tipo === 'unidades');
        $('#cat-unidad-extra').toggle(tipo === 'unidades');
        $('#cat-categoria-linea-wrap').toggle(tipo === 'categorias');

        $('.btn-catalogo-target').removeClass('btn-primary').addClass('btn-outline-primary');
        $('.btn-catalogo-target[data-catalogo-target=\"' + tipo + '\"]').removeClass('btn-outline-primary').addClass('btn-primary');
    }

    function recargarModelos() {
        AppUI.showLoader();
        $.getJSON(rutas.modelosData).done(function (resp) {
            if ($.fn.DataTable.isDataTable('#modelos-table')) $('#modelos-table').DataTable().clear().destroy();
            $('#modelos-table').DataTable({
                data: resp.data || [],
                order: [[0, 'asc']],
                columns: [
                    { data: 'mdl_nombre' },
                    { data: 'mdl_clave', defaultContent: '-' },
                    { data: 'marcas_texto', defaultContent: '-' },
                    { data: 'mdl_estatus', render: (v) => estatusBadge(v) },
                    { data: null, orderable: false, searchable: false, render: (row) => buildActions('modelo', row.mdl_id, row.mdl_nombre, row.mdl_estatus === 'activo') },
                ],
            });
        }).fail((xhr) => AppUI.showMessage('Error', parseErrorMessage(xhr), 'error')).always(() => AppUI.hideLoader());
    }

    function actualizarSelectModelo(marcaId) {
        if (!marcaId) {
            $('#prd_mdl_id').html('<option value=\"\">Sin modelo</option>').val('');
            $('#prd-modelo-wrap').hide();
            return;
        }
        $.getJSON(rutas.modelosPorMarca(marcaId)).done(function (resp) {
            const modelos = resp.data || [];
            if (modelos.length === 0) {
                $('#prd_mdl_id').html('<option value=\"\">Sin modelo</option>').val('');
                $('#prd-modelo-wrap').hide();
            } else {
                const html = ['<option value=\"\">Sin modelo</option>'];
                modelos.forEach(function (m) {
                    html.push('<option value=\"' + m.id + '\">' + m.nombre + '</option>');
                });
                $('#prd_mdl_id').html(html.join(''));
                $('#prd-modelo-wrap').show();
            }
        });
    }

    function recargarMarcasModalModelo() {
        $.getJSON(rutas.baseData('marcas')).done(function (resp) {
            const marcas = (resp.data || []).filter(x => x.estatus === 'activo');
            const html = marcas.map(function (m) {
                return '<label class=\"cc-attr-option\"><input class=\"form-check-input me-2\" type=\"checkbox\" name=\"marca_ids[]\" value=\"' + m.id + '\"><span class=\"fw-semibold\">' + m.nombre + '</span></label>';
            }).join('');
            $('#mdl-marcas-checklist').html(html || '<div class=\"text-body-secondary\">No hay marcas activas.</div>');
        });
    }

    function obtenerTipoProductoActual() {
        return $('input[name="prd_tipo"]:checked').val() || 'simple';
    }

    function esAtributoTalla(nombre) {
        const txt = String(nombre || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
        return txt === 'talla' || txt === 'tallas' || txt === 'tamano' || txt === 'tamanio' || txt === 'medida';
    }

    function compararValorNaturalCatalogo(a, b, forzarNumerico = false) {
        const aa = String(a || '').trim();
        const bb = String(b || '').trim();
        if (!aa && !bb) return 0;
        if (!aa) return 1;
        if (!bb) return -1;

        const rx = /^-?\d+(\.\d+)?$/;
        const na = rx.test(aa) ? Number(aa) : null;
        const nb = rx.test(bb) ? Number(bb) : null;

        if (na !== null && nb !== null) return na - nb;
        if (forzarNumerico) {
            if (na !== null) return -1;
            if (nb !== null) return 1;
        }

        return aa.localeCompare(bb, 'es', { numeric: true, sensitivity: 'base' });
    }

    function obtenerIndiceAtributo(atrId) {
        return catalogoState.productoAtributosSeleccionados.findIndex((id) => Number(id) === Number(atrId));
    }

    function normalizarCorridasProducto(corridas = []) {
        return (corridas || [])
            .filter((item) => item && typeof item === 'object')
            .map((item) => {
                const atrId = Number(item.crc_atr_id ?? item.prc_atr_id ?? 0);
                const valorIdsRaw = item.crc_valor_ids ?? item.prc_valor_ids ?? [];
                const valorIds = [...new Set((valorIdsRaw || []).map((x) => Number(x)).filter((x) => x > 0))];
                return {
                    uid: String(item.uid ?? item.crc_uid ?? item.prc_uid ?? (Date.now() + '-' + Math.random().toString(36).slice(2))),
                    nombre: String(item.crc_nombre ?? item.prc_nombre ?? '').trim(),
                    atr_id: atrId,
                    valor_ids: valorIds,
                    precio_base: Number(item.crc_precio_base ?? item.prc_precio_base ?? 0),
                    costo_base: Number(item.crc_costo_base ?? item.prc_costo_base ?? 0),
                    stock_minimo: Number(item.crc_stock_minimo ?? item.prc_stock_minimo ?? 0),
                    stock_maximo: Number(item.crc_stock_maximo ?? item.prc_stock_maximo ?? 0),
                };
            })
            .filter((item) => item.atr_id > 0 && item.valor_ids.length > 0);
    }

    function obtenerValoresAtributoDisponibles(atributoId) {
        const atrId = Number(atributoId || 0);
        const seleccionados = (catalogoState.productoValoresSeleccionados[atrId] || [])
            .map((id) => Number(id))
            .filter((id) => id > 0);

        if (seleccionados.length) {
            return [...new Set(seleccionados)];
        }

        const atributo = catalogoState.atributos.find((a) => Number(a.atr_id) === atrId);
        const ordenarNumerico = esAtributoTalla(atributo?.atr_nombre || '');

        return catalogoState.valores
            .filter((item) => Number(item.vat_atr_id) === atrId)
            .slice()
            .sort((a, b) => compararValorNaturalCatalogo(a?.vat_valor, b?.vat_valor, ordenarNumerico))
            .map((item) => Number(item.vat_id))
            .filter((id) => id > 0);
    }

    function sanitizarCorridasProducto() {
        const attrsPermitidos = new Set(catalogoState.productoAtributosSeleccionados.map((id) => Number(id)));
        const limpio = [];
        (catalogoState.productoCorridas || []).forEach((corrida) => {
            const atrId = Number(corrida?.atr_id || 0);
            if (!attrsPermitidos.has(atrId)) return;
            const permitidos = new Set(obtenerValoresAtributoDisponibles(atrId));
            const valorIds = [...new Set((corrida?.valor_ids || []).map((id) => Number(id)).filter((id) => permitidos.has(id)))];
            limpio.push({
                nombre: String(corrida?.nombre || '').trim(),
                atr_id: atrId,
                valor_ids: valorIds,
                precio_base: Number(corrida?.precio_base || 0),
                costo_base: Number(corrida?.costo_base || 0),
                stock_minimo: Number(corrida?.stock_minimo || 0),
                stock_maximo: Number(corrida?.stock_maximo || 0),
            });
        });
        catalogoState.productoCorridas = limpio;
    }

    function obtenerValoresCorridaUsados(atributoId, excluirUid = null) {
        const usados = new Set();
        (catalogoState.productoCorridas || []).forEach((corrida) => {
            if (Number(corrida?.atr_id || 0) !== Number(atributoId)) return;
            if (excluirUid && String(corrida?.uid || '') === String(excluirUid)) return;
            (corrida.valor_ids || []).forEach((valorId) => {
                usados.add(Number(valorId));
            });
        });

        return usados;
    }

    function obtenerValoresCorridaDisponibles(atributoId, excluirUid = null) {
        const usados = obtenerValoresCorridaUsados(atributoId, excluirUid);
        return obtenerValoresAtributoDisponibles(atributoId).filter((valorId) => !usados.has(Number(valorId)));
    }

    function validarCorridasProducto() {
        const corridas = catalogoState.productoCorridas || [];
        if (!corridas.length) {
            return 'Debes agregar al menos una corrida para producto variable.';
        }

        const usadosPorAtributo = {};

        for (let idx = 0; idx < corridas.length; idx += 1) {
            const corrida = corridas[idx] || {};
            const etiqueta = 'La corrida ' + (idx + 1);
            const atrId = Number(corrida.atr_id || 0);
            const valorIds = [...new Set((corrida.valor_ids || []).map((id) => Number(id)).filter((id) => id > 0))];
            const permitidos = new Set(obtenerValoresAtributoDisponibles(atrId));
            const stockMin = Number(corrida.stock_minimo || 0);
            const stockMax = Number(corrida.stock_maximo || 0);

            if (!atrId) return etiqueta + ' debe indicar un atributo objetivo válido.';
            if (!String(corrida.nombre || '').trim()) return etiqueta + ' debe tener nombre.';
            if (!valorIds.length) return etiqueta + ' debe incluir al menos un valor.';
            if (stockMax < stockMin) return etiqueta + ' debe tener stock máximo mayor o igual al stock mínimo.';

            usadosPorAtributo[atrId] = usadosPorAtributo[atrId] || {};

            for (const valorId of valorIds) {
                if (!permitidos.has(valorId)) {
                    return etiqueta + ' contiene valores que ya no pertenecen al atributo seleccionado.';
                }

                if (usadosPorAtributo[atrId][valorId]) {
                    return 'Un mismo valor de atributo no puede repetirse en más de una corrida.';
                }

                usadosPorAtributo[atrId][valorId] = true;
            }
        }

        return null;
    }

    function sincronizarCorridasDesdeUI() {
        const corridas = [];
        $('#producto-corridas-list .card').each(function (idx) {
            const $card = $(this);
            const atrId = Number($card.data('atrId') || 0);
            const valorIds = ($card.find('.js-prd-corrida-valores').val() || []).map((v) => Number(v)).filter((v) => v > 0);
            corridas.push({
                uid: String($card.data('corridaUid') || (Date.now() + '-' + Math.random().toString(36).slice(2))),
                nombre: String($card.find('.js-prd-corrida-nombre').val() || '').trim(),
                atr_id: atrId,
                valor_ids: [...new Set(valorIds)],
                precio_base: Number($card.find('.js-prd-corrida-precio').val() || 0),
                costo_base: Number($card.find('.js-prd-corrida-costo').val() || 0),
                stock_minimo: Number($card.find('.js-prd-corrida-stock-min').val() || 0),
                stock_maximo: Number($card.find('.js-prd-corrida-stock-max').val() || 0),
            });
        });
        catalogoState.productoCorridas = corridas;
    }

    function sincronizarCorridaPorIndice(idx) {
        const $card = $('#producto-corridas-list .card').eq(idx);
        if (!$card.length || !catalogoState.productoCorridas[idx]) return;
        const atrId = Number($card.data('atrId') || 0);
        const valorIds = ($card.find('.js-prd-corrida-valores').val() || []).map((v) => Number(v)).filter((v) => v > 0);
        catalogoState.productoCorridas[idx] = {
            ...catalogoState.productoCorridas[idx],
            uid: String($card.data('corridaUid') || catalogoState.productoCorridas[idx].uid || (Date.now() + '-' + Math.random().toString(36).slice(2))),
            nombre: String($card.find('.js-prd-corrida-nombre').val() || '').trim(),
            atr_id: atrId,
            valor_ids: [...new Set(valorIds)],
            precio_base: Number($card.find('.js-prd-corrida-precio').val() || 0),
            costo_base: Number($card.find('.js-prd-corrida-costo').val() || 0),
            stock_minimo: Number($card.find('.js-prd-corrida-stock-min').val() || 0),
            stock_maximo: Number($card.find('.js-prd-corrida-stock-max').val() || 0),
        };
    }

    function reconstruirValoresCorridaEnSitio(idx) {
        const corrida = catalogoState.productoCorridas[idx];
        if (!corrida) return;

        const atrId = Number(corrida.atr_id || 0);
        const $card = $('#producto-corridas-list .card').eq(idx);
        if (!$card.length) return;

        const usados = {};
        (catalogoState.productoCorridas || []).forEach((otra, oIdx) => {
            if (oIdx === idx) return;
            if (Number(otra?.atr_id || 0) !== atrId) return;
            (otra?.valor_ids || []).forEach((v) => { usados[Number(v)] = true; });
        });

        const disponibles = obtenerValoresAtributoDisponibles(atrId)
            .map((valorId) => catalogoState.valores.find((v) => Number(v.vat_id) === Number(valorId)))
            .filter(Boolean);

        const $select = $card.find('.js-prd-corrida-valores');
        if (!$select.length) return;

        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }

        const optionsHtml = disponibles.map((v) => {
            const valId = Number(v.vat_id);
            const bloqueado = usados[valId] ? ' disabled' : '';
            return '<option value="' + valId + '"' + bloqueado + '>' + escapeAttr(v.vat_valor || '') + '</option>';
        }).join('');

        $select.html(optionsHtml).val([]);
        $select.select2({
            width: '100%',
            placeholder: 'Selecciona valores',
            closeOnSelect: false,
            allowClear: true,
        });
    }

    function tituloCorridaSugerido(atrId) {
        const atributo = catalogoState.atributos.find((a) => Number(a.atr_id) === Number(atrId));
        const nombre = atributo?.atr_nombre || 'Grupo';
        const idx = (catalogoState.productoCorridas || []).length + 1;
        return nombre + ' ' + idx;
    }

    function renderConfiguracionCorridasProducto() {
        const attrs = catalogoState.productoAtributosSeleccionados || [];
        if (!attrs.length) {
            return '<div class="text-body-secondary small mt-2">Selecciona atributos y valores para crear corridas.</div>';
        }

        const rows = (catalogoState.productoCorridas || []).map((corrida, idx) => {
            const atrId = Number(corrida.atr_id || 0);
            const atrActual = catalogoState.atributos.find((a) => Number(a.atr_id) === atrId);

            const usados = {};
            (catalogoState.productoCorridas || []).forEach((otra, oIdx) => {
                if (oIdx === idx) return;
                if (Number(otra.atr_id) !== atrId) return;
                (otra.valor_ids || []).forEach((v) => { usados[Number(v)] = true; });
            });
            const disponibles = obtenerValoresAtributoDisponibles(atrId)
                .map((valorId) => catalogoState.valores.find((v) => Number(v.vat_id) === Number(valorId)))
                .filter(Boolean);
            const optionsVals = disponibles.map((v) => {
                const valId = Number(v.vat_id);
                const sel = (corrida.valor_ids || []).includes(valId);
                const bloqueado = !sel && usados[valId];
                return '<option value="' + valId + '" ' + (sel ? 'selected' : '') + (bloqueado ? ' disabled' : '') + '>' + v.vat_valor + '</option>';
            }).join('');

            return '' +
                '<div class="card mb-2" data-atr-id="' + atrId + '" data-corrida-uid="' + escapeAttr(corrida.uid || ('uid-' + idx)) + '">' +
                    '<div class="card-body">' +
                        '<div class="d-flex justify-content-between align-items-center mb-2">' +
                            '<strong>Corrida ' + (idx + 1) + '</strong>' +
                            '<button type="button" class="btn btn-sm btn-outline-danger js-prd-corrida-remove" data-corrida-uid="' + escapeAttr(corrida.uid || ('uid-' + idx)) + '">Quitar</button>' +
                        '</div>' +
                        '<div class="row g-2">' +
                            '<div class="col-md-3"><label class="form-label">Nombre</label><input type="text" class="form-control form-control-sm js-prd-corrida-nombre" data-idx="' + idx + '" value="' + escapeAttr(corrida.nombre || '') + '" maxlength="120"></div>' +
                            '<div class="col-md-3"><label class="form-label">Atributo objetivo</label><input type="text" class="form-control form-control-sm bg-light" value="' + escapeAttr(atrActual?.atr_nombre || ('Atributo ' + atrId)) + '" readonly></div>' +
                            '<div class="col-md-6"><label class="form-label">Valores incluidos</label><select class="form-select form-select-sm cc-select2-tags js-prd-corrida-valores" data-idx="' + idx + '" multiple>' + optionsVals + '</select></div>' +
                            '<div class="col-md-3"><label class="form-label">Costo</label><input type="number" class="form-control form-control-sm js-prd-corrida-costo" data-idx="' + idx + '" min="0" step="0.01" value="' + escapeAttr(Number(corrida.costo_base || 0).toFixed(2)) + '"></div>' +
                            '<div class="col-md-3"><label class="form-label">Precio venta</label><input type="number" class="form-control form-control-sm js-prd-corrida-precio" data-idx="' + idx + '" min="0" step="0.01" value="' + escapeAttr(Number(corrida.precio_base || 0).toFixed(2)) + '"></div>' +
                            '<div class="col-md-3"><label class="form-label">Stock mínimo base</label><input type="number" class="form-control form-control-sm js-prd-corrida-stock-min" data-idx="' + idx + '" min="0" step="1" value="' + escapeAttr(Number(corrida.stock_minimo || 0)) + '"></div>' +
                            '<div class="col-md-3"><label class="form-label">Stock máximo base</label><input type="number" class="form-control form-control-sm js-prd-corrida-stock-max" data-idx="' + idx + '" min="0" step="1" value="' + escapeAttr(Number(corrida.stock_maximo || 0)) + '"></div>' +
                        '</div>' +
                    '</div>' +
                '</div>';
        }).join('');

        return '' +
            '<div class="mt-3 border-top pt-3">' +
                '<div class="d-flex justify-content-between align-items-center mb-2">' +
                    '<h6 class="mb-0">Agrupaciones de corridas</h6>' +
                    '<div class="d-flex align-items-center gap-2">' +
                        '<select class="form-select form-select-sm" id="prd-corrida-atr-objetivo" style="min-width:240px;">' +
                            attrs.map((id) => {
                                const atr = catalogoState.atributos.find((a) => Number(a.atr_id) === Number(id));
                                const selected = Number(catalogoState.productoCorridaAtributoObjetivo || attrs[0]) === Number(id) ? ' selected' : '';
                                const disabled = !obtenerValoresCorridaDisponibles(id).length ? ' disabled' : '';
                                return '<option value="' + id + '"' + selected + disabled + '>' + (atr?.atr_nombre || ('Atributo ' + id)) + '</option>';
                            }).join('') +
                        '</select>' +
                        '<button type="button" class="btn btn-sm btn-outline-primary" id="btn-prd-corrida-add">+ Agregar corrida</button>' +
                    '</div>' +
                '</div>' +
                '<div class="small text-body-secondary mb-2">Cada corrida define precio, costo y stock por grupo de valores.</div>' +
                '<div id="producto-corridas-list">' + (rows || '<div class="text-body-secondary small">Aún no hay corridas. Agrega al menos una.</div>') + '</div>' +
            '</div>';
    }

    function actualizarUiCamposBaseProducto() {
        const esVariable = obtenerTipoProductoActual() === 'variable';
        const $precio = $('#prd_precio_base');
        const $costo = $('#prd_costo');
        const $min = $('#prd_stock_minimo');
        const $max = $('#prd_stock_maximo');
        const $labels = $precio.closest('.col-md-4').add($costo.closest('.col-md-4')).add($min.closest('.col-md-4')).add($max.closest('.col-md-4'));
        $labels.toggleClass('opacity-50', esVariable);
        if (esVariable) {
            $precio.prop('readonly', true);
            $costo.prop('readonly', true);
            $min.prop('readonly', true);
            $max.prop('readonly', true);
        } else {
            $precio.prop('readonly', false);
            $costo.prop('readonly', false);
            $min.prop('readonly', false);
            $max.prop('readonly', false);
        }
    }

    function actualizarUiTipoProducto() {
        const esVariable = obtenerTipoProductoActual() === 'variable';
        $('#producto-variable-shell').toggle(esVariable);

        if (!esVariable) {
            catalogoState.productoAtributosSeleccionados = [];
            catalogoState.productoValoresSeleccionados = {};
            catalogoState.productoCorridas = [];
        }

        actualizarUiCamposBaseProducto();
        renderConfiguracionVariableProducto();
    }

    function renderChecklistAtributosProducto() {
        if (!catalogoState.atributos.length) {
            $('#producto-atributos-checklist').html('<div class="text-body-secondary">No hay atributos activos disponibles.</div>');
            return;
        }

        const options = catalogoState.atributos.map(function (atributo) {
            const selected = catalogoState.productoAtributosSeleccionados.includes(Number(atributo.atr_id)) ? 'selected' : '';
            return `<option value="${atributo.atr_id}" ${selected}>${atributo.atr_nombre}</option>`;
        }).join('');

        const html = `
            <select id="producto-atributos-select" class="form-select cc-select2-tags" name="atributo_ids[]" multiple>
                ${options}
            </select>
        `;

        $('#producto-atributos-checklist').html(html);
    }

    function inicializarSelectTagsProducto() {
        const $atributos = $('#producto-atributos-select');
        if ($atributos.length) {
            if ($atributos.hasClass('select2-hidden-accessible')) {
                $atributos.select2('destroy');
            }
            $atributos.select2({
                width: '100%',
                placeholder: 'Selecciona atributos',
                closeOnSelect: false,
                allowClear: true,
            });
        }

        $('.producto-valores-select').each(function () {
            const $select = $(this);
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }
            $select.select2({
                width: '100%',
                placeholder: 'Selecciona valores',
                closeOnSelect: false,
                allowClear: true,
            });
        });
    }

    function contarCorridasProducto() {
        if (obtenerTipoProductoActual() !== 'variable') {
            return 1;
        }

        if (catalogoState.productoAtributosSeleccionados.length === 0) {
            return 0;
        }

        let total = 1;

        for (const atributoId of catalogoState.productoAtributosSeleccionados) {
            const valores = catalogoState.productoValoresSeleccionados[atributoId] || [];
            if (!valores.length) {
                return 0;
            }

            total *= valores.length;
        }

        return total;
    }

    function renderResumenProducto() {
        const tipo = obtenerTipoProductoActual();
        const totalAtributos = catalogoState.productoAtributosSeleccionados.length;
        const totalValores = Object.values(catalogoState.productoValoresSeleccionados).reduce(function (acumulado, valores) {
            return acumulado + (valores?.length || 0);
        }, 0);
        const totalCorridas = contarCorridasProducto();

        const html = `
            <div class="cc-summary-card">
                <small>Tipo actual</small>
                <strong>${tipo === 'variable' ? 'Variable' : 'Simple'}</strong>
            </div>
            <div class="cc-summary-card">
                <small>Atributos en corrida</small>
                <strong>${tipo === 'variable' ? totalAtributos : 0}</strong>
            </div>
            <div class="cc-summary-card">
                <small>Valores seleccionados</small>
                <strong>${tipo === 'variable' ? totalValores : 0}</strong>
            </div>
            <div class="cc-summary-card">
                <small>SKU a generar</small>
                <strong>${totalCorridas}</strong>
            </div>
        `;

        $('#producto-resumen-general').html(html);
    }

    function renderConfiguracionVariableProducto() {
        renderChecklistAtributosProducto();
        inicializarSelectTagsProducto();

        if (obtenerTipoProductoActual() !== 'variable') {
            $('#producto-valores-config').html('');
            renderResumenProducto();
            return;
        }

        if (catalogoState.productoAtributosSeleccionados.length === 0) {
            $('#producto-valores-config').html('<div class="text-body-secondary">Selecciona al menos un atributo para configurar la corrida.</div>');
            renderResumenProducto();
            return;
        }

        const htmlValores = catalogoState.productoAtributosSeleccionados.map(function (atributoId) {
            const atributo = catalogoState.atributos.find((item) => Number(item.atr_id) === Number(atributoId));
            const ordenarNumerico = esAtributoTalla(atributo?.atr_nombre || '');
            const valores = catalogoState.valores
                .filter((item) => Number(item.vat_atr_id) === Number(atributoId))
                .slice()
                .sort((a, b) => compararValorNaturalCatalogo(a?.vat_valor, b?.vat_valor, ordenarNumerico));
            const seleccionados = (catalogoState.productoValoresSeleccionados[atributoId] || []).map(Number);

            const valoresHtml = valores.map(function (valor) {
                const selected = seleccionados.includes(Number(valor.vat_id)) ? 'selected' : '';
                return `<option value="${valor.vat_id}" ${selected}>${valor.vat_valor}</option>`;
            }).join('');

            return `
                <div class="cc-value-card mb-3">
                    <div class="fw-semibold">${atributo ? atributo.atr_nombre : 'Atributo'}</div>
                    ${valores.length
                        ? `<select class="form-select cc-select2-tags producto-valores-select mt-2" name="atributo_valores[${atributoId}][]" data-atributo-id="${atributoId}" multiple>${valoresHtml}</select>`
                        : '<span class="text-body-secondary d-block mt-2">Este atributo no tiene valores activos.</span>'
                    }
                </div>
            `;
        }).join('');

        sanitizarCorridasProducto();
        const html = htmlValores + renderConfiguracionCorridasProducto();
        $('#producto-valores-config').html(html);
        inicializarSelectTagsProducto();
        $('#producto-valores-config .js-prd-corrida-valores').each(function () {
            if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).select2('destroy');
            }
            $(this).select2({
                width: '100%',
                placeholder: 'Selecciona valores',
                closeOnSelect: false,
                allowClear: true,
            });
        });
        renderResumenProducto();
    }

    function normalizarMapaValoresProducto(mapa) {
        const normalizado = {};

        Object.entries(mapa || {}).forEach(function ([atributoId, valorIds]) {
            normalizado[Number(atributoId)] = [...new Set((valorIds || []).map(Number))];
        });

        return normalizado;
    }

    function sanitizarEstadoProductoVariable() {
        const atributosDisponibles = catalogoState.atributos.map((item) => Number(item.atr_id));
        const valoresDisponibles = catalogoState.valores.map((item) => Number(item.vat_id));

        catalogoState.productoAtributosSeleccionados = catalogoState.productoAtributosSeleccionados
            .map(Number)
            .filter((atributoId, index, array) => atributosDisponibles.includes(atributoId) && array.indexOf(atributoId) === index);

        const mapaNormalizado = {};

        Object.entries(catalogoState.productoValoresSeleccionados || {}).forEach(function ([atributoId, valorIds]) {
            const atributoIdInt = Number(atributoId);

            if (!catalogoState.productoAtributosSeleccionados.includes(atributoIdInt)) {
                return;
            }

            mapaNormalizado[atributoIdInt] = [...new Set((valorIds || []).map(Number))]
                .filter((valorId) => valoresDisponibles.includes(valorId));
        });

        catalogoState.productoValoresSeleccionados = mapaNormalizado;
    }

    function prepararNuevoProducto() {
        $('#form-producto')[0].reset();
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
        $('#prd-almacenes-checklist input[type="checkbox"]').prop('checked', false).closest('.cc-attr-option').removeClass('is-selected');
        $('#prd_tipo_variable').prop('checked', true);
        $('#prd_tipo_simple').prop('checked', false);
        $('#prd_estatus').val('activo');
        $('input[name="prd_imagen_metodo"]').prop('checked', false);
        $('#producto-form-heading').text('Alta de producto base');
        $('#tab-producto-form-label').text('Nuevo producto');

        // Limpiar modelo y conceptos dependientes
        $('#prd_mdl_id').html('<option value=\"\">Sin modelo</option>').val('');
        $('#prd-modelo-wrap').hide();
        $('#prd_ctg_id').html('<option value="">Selecciona una línea primero</option>').val('');

        // Pre-seleccionar la unidad predeterminada si existe
        const unidadDefault = (catalogoState.unidades || []).find(u => u.es_predeterminada);
        if (unidadDefault) {
            $('#prd_umd_id').val(String(unidadDefault.id));
        }

        catalogoState.productoAtributosSeleccionados = [];
        catalogoState.productoValoresSeleccionados = {};
        catalogoState.productoCorridas = [];
        catalogoState.productoImagenSesion = null;
        limpiarPollingImagenProducto();
        establecerPreviewImagenProducto(null);
        $('#producto-imagen-qr').hide();
        $('#producto-imagen-qr-placeholder').show().text('Generando sesión de carga móvil...');
        $('#producto-imagen-mobile-link').hide().text('');
        $('#producto-imagen-mobile-status').text('Todavía no se ha cargado una imagen desde el celular.');

        actualizarUiTipoProducto();
        actualizarUiMetodoImagen();
        iniciarSesionImagenProducto();
    }

    $('#btn-confirmar-eliminar').on('click', function () {
        if (typeof deleteAction === 'function') {
            modalConfirm.hide();
            deleteAction();
        }
    });

    function recargarCatalogo() {
        const tipo = $('#catalogo-tipo').val();
        if (tipo === 'modelos') {
            $('#catalogo-wrap').hide();
            $('#modelos-wrap').show();
            recargarModelos();
            return;
        }
        $('#modelos-wrap').hide();
        $('#catalogo-wrap').show();
        AppUI.showLoader();
        $.getJSON(rutas.baseData(tipo)).done(function (resp) {
            if ($.fn.DataTable.isDataTable('#catalogo-table')) $('#catalogo-table').DataTable().clear().destroy();

            let columns;
            if (tipo === 'categorias') {
                $('#catalogo-table thead tr').html('<th>Nombre</th><th>Línea</th><th>Clave</th><th>Estatus</th><th>Acciones</th>');
                columns = [
                    { data: 'nombre' },
                    { data: 'linea', defaultContent: '<span class="text-body-secondary">Sin línea</span>' },
                    { data: 'clave', defaultContent: '-' },
                    { data: 'estatus', render: (v) => estatusBadge(v) },
                    { data: null, orderable: false, searchable: false, render: (row) => buildActions('catalogo', row.id, row.nombre, row.estatus === 'activo') },
                ];
            } else {
                $('#catalogo-table thead tr').html('<th>Nombre</th><th>Código</th><th>Clave</th><th>Estatus</th><th>Acciones</th>');
                columns = [
                    { data: 'nombre' },
                    { data: 'codigo', defaultContent: '-' },
                    { data: 'clave', defaultContent: '-' },
                    { data: 'estatus', render: (v) => estatusBadge(v) },
                    { data: null, orderable: false, searchable: false, render: (row) => buildActions('catalogo', row.id, row.nombre, row.estatus === 'activo') },
                ];
            }

            $('#catalogo-table').DataTable({
                data: resp.data || [],
                order: [[0, 'asc']],
                columns,
            });
        }).fail((xhr) => AppUI.showMessage('Error', parseErrorMessage(xhr), 'error')).always(() => AppUI.hideLoader());
    }

    function recargarAtributos() {
        $.getJSON(rutas.atributosData).done(function (resp) {
            if ($.fn.DataTable.isDataTable('#atributos-table')) $('#atributos-table').DataTable().clear().destroy();
            $('#atributos-table').DataTable({
                data: resp.data || [],
                order: [[0, 'asc']],
                columns: [
                    { data: 'atr_nombre' }, { data: 'atr_clave', defaultContent: '-' }, { data: 'atr_tipo', defaultContent: '-' },
                    { data: 'atr_estatus', render: (v) => estatusBadge(v) }, { data: 'valores_total' },
                    { data: null, orderable: false, searchable: false, render: (row) => buildActions('atributo', row.atr_id, row.atr_nombre, row.atr_estatus === 'activo') },
                ],
            });
        });
    }

    function recargarValores() {
        $.getJSON(rutas.valoresData).done(function (resp) {
            if ($.fn.DataTable.isDataTable('#valores-table')) $('#valores-table').DataTable().clear().destroy();
            $('#valores-table').DataTable({
                data: resp.data || [],
                order: [[0, 'asc'], [1, 'asc']],
                columns: [
                    { data: 'atributo' }, { data: 'vat_valor' }, { data: 'vat_clave', defaultContent: '-' },
                    { data: 'vat_estatus', render: (v) => estatusBadge(v) },
                    { data: null, orderable: false, searchable: false, render: (row) => buildActions('valor', row.vat_id, row.vat_valor, row.vat_estatus === 'activo') },
                ],
            });
        });
    }

    function renderProductosGrid(data) {
        const html = (data || []).map(function (producto) {
            const atributos = (producto.atributos || []).length
                ? (producto.atributos || []).map((atributo) => '<span class="badge bg-label-primary">' + atributo + '</span>').join('')
                : '<span class="badge bg-label-secondary">Sin atributos</span>';

            const clasificacion = [producto.marca, producto.linea, producto.categoria, producto.unidad]
                .filter(Boolean)
                .join(' / ') || 'Sin clasificación';
            const barcode = producto.prd_codigo_barras ? 'Código de barras: ' + producto.prd_codigo_barras : 'Código de barras: no capturado';
            const claveSat = producto.prd_clave_sat ? 'Clave fiscal (SAT): ' + producto.prd_clave_sat : 'Clave fiscal (SAT): no capturada';
            const proveedor = producto.proveedor ? '<br><small>Proveedor: ' + producto.proveedor + '</small>' : '';

            return `
                <article class="cc-product-card">
                    <div class="cc-product-card__top">
                        <div>
                            <div class="cc-product-card__code">${producto.prd_codigo || '-'}</div>
                            <h5 class="cc-product-card__title">${producto.prd_nombre || 'Producto'}</h5>
                        </div>
                        ${buildActions('producto', producto.prd_id, producto.prd_nombre, producto.prd_estatus === 'activo')}
                    </div>
                    <div class="cc-product-card__meta">${clasificacion}<br><small>${barcode}</small><br><small>${claveSat}</small>${proveedor}</div>
                    <div class="cc-product-card__chips">
                        <span class="badge bg-label-${producto.prd_tipo === 'variable' ? 'info' : 'primary'}">${producto.prd_tipo === 'variable' ? 'Variable' : 'Simple'}</span>
                        ${estatusBadge(producto.prd_estatus)}
                    </div>
                    <div class="cc-product-card__stats">
                        <div class="cc-product-card__stat">
                            <small>Precio base</small>
                            <strong>$${Number(producto.prd_precio_base || 0).toFixed(2)}</strong>
                        </div>
                        <div class="cc-product-card__stat">
                            <small>Costo base</small>
                            <strong>$${Number(producto.prd_costo || 0).toFixed(2)}</strong>
                        </div>
                        <div class="cc-product-card__stat">
                            <small>Corridas</small>
                            <strong>${Number(producto.skus_total || 0)}</strong>
                        </div>
                        <div class="cc-product-card__stat">
                            <small>Stock min / max</small>
                            <strong>${producto.prd_stock_minimo ?? 0} / ${producto.prd_stock_maximo ?? 0}</strong>
                        </div>
                    </div>
                    <div class="cc-product-card__attrs">${atributos}</div>
                </article>
            `;
        }).join('');

        $('#productos-grid').html(html || '<div class="cc-product-card__empty">Aún no hay productos para mostrar con esta búsqueda. Prueba con otro texto o agrega un producto nuevo.</div>');
    }

    function actualizarResumenProductos(productos) {
        const total = productos.length;
        const activos = productos.filter((p) => p.prd_estatus === 'activo').length;
        const variantes = productos.filter((p) => Number(p.skus_total || 0) > 0).length;
        const sinProveedor = productos.filter((p) => !p.proveedor).length;
        $('#kpi-productos-total').text(total);
        $('#kpi-productos-activos').text(activos);
        $('#kpi-productos-variantes').text(variantes);
        $('#kpi-productos-sin-proveedor').text(sinProveedor);
    }

    function aplicarFiltroProductosLocal() {
        const term = String($('#productos-busqueda-rapida').val() || '').trim().toLowerCase();
        const filtrados = term
            ? productosCache.filter((p) => {
                const bolsa = [
                    p.prd_codigo,
                    p.prd_nombre,
                    p.marca,
                    p.linea,
                    p.categoria,
                    p.proveedor,
                    p.unidad,
                ].filter(Boolean).join(' ').toLowerCase();
                return bolsa.includes(term);
            })
            : productosCache;

        actualizarResumenProductos(filtrados);
        renderProductosGrid(filtrados);

        if (tablaProductos) {
            tablaProductos.clear().rows.add(filtrados).draw();
        }
    }

    function recargarProductos() {
        $.getJSON(rutas.productosData).done(function (resp) {
            productosCache = resp.data || [];

            if ($.fn.DataTable.isDataTable('#productos-table')) $('#productos-table').DataTable().clear().destroy();
            tablaProductos = $('#productos-table').DataTable({
                data: productosCache,
                order: [[1, 'asc']],
                columns: [
                    { data: 'prd_codigo' },
                    { data: 'prd_nombre' },
                    { data: null, render: (r) => '<span class="badge bg-label-' + (r.prd_tipo === 'variable' ? 'info' : 'primary') + ' mb-1">' + (r.prd_tipo === 'variable' ? 'Con corridas' : 'Sencillo') + '</span><div class="small text-body-secondary">Precio: $' + Number(r.prd_precio_base || 0).toFixed(2) + ' | Costo: $' + Number(r.prd_costo || 0).toFixed(2) + '</div><div class="small text-body-secondary">Stock mínimo/máximo: ' + (r.prd_stock_minimo ?? 0) + ' / ' + (r.prd_stock_maximo ?? 0) + '</div><div class="small text-body-secondary">Código de barras: ' + (r.prd_codigo_barras || '-') + '</div><div class="small text-body-secondary">Clave fiscal (SAT): ' + (r.prd_clave_sat || '-') + '</div>' },
                    { data: null, render: (r) => {
                        const principal = [r.marca, r.linea, r.categoria, r.unidad].filter(Boolean).join(' / ') || '-';
                        const proveedor = r.proveedor ? '<div class="small text-body-secondary">Proveedor: ' + r.proveedor + '</div>' : '';
                        return principal + proveedor;
                    }},
                    { data: null, render: (r) => {
                        const atributos = (r.atributos || []).length ? (r.atributos || []).join(', ') : 'Sin atributos';
                        const total = Number(r.skus_total || 0);
                        const activos = Number(r.skus_activos || 0);
                        return '<div class="fw-semibold">' + total + ' corridas</div><div class="small text-body-secondary">Activas: ' + activos + '</div><div class="small text-body-secondary">' + atributos + '</div>';
                    }},
                    { data: 'prd_estatus', render: (v) => estatusBadge(v) },
                    { data: null, orderable: false, searchable: false, render: (row) => buildActions('producto', row.prd_id, row.prd_nombre, row.prd_estatus === 'activo') },
                ],
            });

            aplicarFiltroProductosLocal();
            aplicarVistaProductos(obtenerVistaProductos());
        });
    }

    function recargarSkus() {
        $.getJSON(rutas.skusData, {
            psk_prd_id: $('#flt-sku-producto').val(),
            atributo_filtros: obtenerFiltrosSkuSeleccionados(),
        }).done(function (resp) {
            if ($.fn.DataTable.isDataTable('#skus-table')) $('#skus-table').DataTable().clear().destroy();
            $('#skus-table').DataTable({
                data: resp.data || [],
                order: [[1, 'asc']],
                columns: [
                    { data: null, render: (r) => [r.producto_codigo, r.producto].filter(Boolean).join(' - ') },
                    { data: 'psk_codigo' },
                    { data: 'psk_codigo_barras', defaultContent: '-' },
                    { data: 'psk_nombre', defaultContent: '-' },
                    { data: 'combinacion', render: (arr) => (arr || []).join(' | ') },
                    { data: 'psk_precio', render: (v) => '$' + Number(v || 0).toFixed(2) },
                    { data: null, render: (r) => 'Min: ' + (r.psk_stock_minimo ?? 0) + '<br>Max: ' + (r.psk_stock_maximo ?? 0) },
                    { data: 'psk_estatus', render: (v) => estatusBadge(v) },
                    { data: null, orderable: false, searchable: false, render: (row) => buildActions('sku', row.psk_id, row.psk_codigo, row.psk_estatus === 'activo') },
                ],
            });
        });
    }

    function aplicarVistaSkus(vista) {
        const modo = vista === 'matriz' ? 'matriz' : 'tabla';

        catalogoState.skuViewMode = modo;

        $('#btn-sku-vista-tabla')
            .toggleClass('btn-primary', modo === 'tabla')
            .toggleClass('btn-outline-primary', modo !== 'tabla');

        $('#btn-sku-vista-matriz')
            .toggleClass('btn-primary', modo === 'matriz')
            .toggleClass('btn-outline-primary', modo !== 'matriz')
            .prop('disabled', false);

        // En modo Matriz el selector de producto y los filtros de atributos quedan
        // reemplazados por la barra de filtros propia de la matriz.
        const esMatriz = modo === 'matriz';
        $('#sku-producto-selector-wrap').toggleClass('d-none', esMatriz);

        if (esMatriz) {
            $('#sku-filtros-dinamicos-shell').addClass('d-none');
        } else {
            // Restaurar visibilidad solo si hay filtros cargados
            const tieneFiltros = $('#sku-filtros-dinamicos').children().length > 0;
            $('#sku-filtros-dinamicos-shell').toggleClass('d-none', !tieneFiltros);
        }

        $('#skus-table-shell').toggleClass('d-none', esMatriz);
        $('#skus-matriz-shell').toggleClass('d-none', !esMatriz);
    }

    function recargarVistaSkus() {
        if (catalogoState.skuViewMode === 'matriz') {
            recargarMatrizSkus();
            return;
        }

        recargarSkus();
    }

    function limpiarFiltrosSkuDinamicos() {
        catalogoState.skuFiltrosMeta = [];
        $('#sku-filtros-dinamicos').html('');
        $('#sku-filtros-dinamicos-shell').addClass('d-none');
        $('#sku-filtros-orden-note').addClass('d-none').text('');
    }

    function obtenerFiltrosSkuSeleccionados() {
        const filtros = {};

        $('#sku-filtros-dinamicos').find('[data-sku-atributo-id]').each(function () {
            const atrId = String($(this).data('skuAtributoId') || '').trim();
            const valorId = String($(this).val() || '').trim();

            if (atrId && valorId) {
                filtros[atrId] = valorId;
            }
        });

        return filtros;
    }

    function renderFiltrosSkuDinamicos(meta, seleccionados = {}) {
        const atributos = meta?.atributos || [];
        catalogoState.skuFiltrosMeta = atributos;

        if (!atributos.length) {
            limpiarFiltrosSkuDinamicos();
            return;
        }

        const html = atributos.map(function (atributo) {
            const opciones = ['<option value="">Todos</option>'].concat((atributo.valores || []).map(function (valor) {
                const selected = String(seleccionados?.[atributo.atr_id] || '') === String(valor.vat_id) ? ' selected' : '';
                return '<option value="' + escapeAttr(valor.vat_id) + '"' + selected + '>' + escapeAttr(valor.vat_valor || '') + '</option>';
            }));

            return '' +
                '<div class="col-md-4">' +
                    '<label class="form-label">' + escapeAttr(atributo.atr_nombre || 'Atributo') + '</label>' +
                    '<select class="form-select js-sku-filtro-atributo" data-sku-atributo-id="' + escapeAttr(atributo.atr_id) + '">' +
                        opciones.join('') +
                    '</select>' +
                '</div>';
        }).join('');

        $('#sku-filtros-dinamicos').html(html);
        $('#sku-filtros-dinamicos-shell').removeClass('d-none');

        const notaOrden = String(meta?.ordenamiento?.criterio || '').trim();
        if (notaOrden && meta?.ordenamiento?.usa_talla) {
            $('#sku-filtros-orden-note').removeClass('d-none').text(notaOrden);
        } else {
            $('#sku-filtros-orden-note').addClass('d-none').text('');
        }
    }

    function cargarFiltrosSkuProducto(productoId, recargar = true, seleccionados = {}) {
        // Los filtros de atributos solo aplican a la vista Tabla.
        // En modo Matriz estos están ocultos y la barra propia de la matriz filtra.
        if (catalogoState.skuViewMode === 'matriz') {
            return;
        }

        if (!productoId) {
            limpiarFiltrosSkuDinamicos();
            aplicarVistaSkus(catalogoState.skuViewMode);
            if (recargar) recargarSkus();
            return;
        }

        $.getJSON(rutas.skusFiltros, { psk_prd_id: productoId })
            .done(function (resp) {
                renderFiltrosSkuDinamicos(resp.data || {}, seleccionados);
                aplicarVistaSkus(catalogoState.skuViewMode);
                if (recargar) recargarVistaSkus();
            })
            .fail(function () {
                limpiarFiltrosSkuDinamicos();
                aplicarVistaSkus(catalogoState.skuViewMode);
                if (recargar) recargarVistaSkus();
            });
    }

    function renderMatrizSkusVacia(mensaje) {
        $('#skus-matriz-wrap').html(
            '<div class="p-4 text-center">' +
                '<div class="text-body-secondary">' + escapeHtml(mensaje || 'No hay datos para mostrar.') + '</div>' +
            '</div>'
        );
    }

    function renderMatrizSkus(filas) {
        if (!Array.isArray(filas) || !filas.length) {
            renderMatrizSkusVacia('No se encontraron registros con los filtros aplicados.');
            return;
        }

        const headHtml =
            '<thead>' +
                '<tr>' +
                    '<th>Marca</th>' +
                    '<th>Modelo</th>' +
                    '<th>Línea</th>' +
                    '<th>Concepto</th>' +
                    '<th>Color</th>' +
                    '<th style="min-width:240px">Variantes</th>' +
                '</tr>' +
            '</thead>';

        const bodyHtml = '<tbody>' + filas.map(function (fila) {
            const meta = function (title, sub) {
                return '<div class="cc-mtz-meta"><span class="cc-mtz-meta__title">' + escapeHtml(title || '-') + '</span>' +
                    (sub ? '<span class="cc-mtz-meta__sub">' + escapeHtml(sub) + '</span>' : '') + '</div>';
            };

            const pills = (fila.variantes || []).map(function (v) {
                const mod = v.psk_estatus === 'inactivo' ? ' cc-mtz-pill--inactivo' : '';
                const tooltip = 'Editar SKU / Variante · ' + (v.psk_codigo || '-') + (v.psk_estatus === 'inactivo' ? ' · Inactivo' : '');
                return '<button type="button" class="cc-mtz-pill' + mod + '" ' +
                    'data-action="edit-sku" data-id="' + escapeAttr(v.psk_id) + '" ' +
                    'title="' + escapeAttr(tooltip) + '">' +
                    '<span class="cc-mtz-pill__label">' + escapeHtml(v.label || v.psk_codigo || '-') + '</span>' +
                '</button>';
            }).join('');

            const concepto = fila.concepto_nombre || '-';
            const subConcepto = fila.producto_codigo ? (fila.producto_codigo + ' · ' + (fila.producto_nombre || '')) : '';

            return '<tr>' +
                '<td>' + meta(fila.marca_nombre) + '</td>' +
                '<td>' + meta(fila.modelo_nombre) + '</td>' +
                '<td>' + meta(fila.linea_nombre) + '</td>' +
                '<td>' + meta(concepto, subConcepto) + '</td>' +
                '<td>' + meta(fila.color_nombre) + '</td>' +
                '<td><div class="cc-mtz-variant-strip">' + (pills || '<span class="text-body-secondary" style="font-size:.75rem">Sin variantes</span>') + '</div></td>' +
            '</tr>';
        }).join('') + '</tbody>';

        $('#skus-matriz-wrap').html(
            '<table class="table table-bordered table-sm cc-mtz-table">' + headHtml + bodyHtml + '</table>'
        );
    }

    function cargarFiltrosMatrizProducto(productoId) {
        $('#mtz-atributos-wrap').addClass('d-none').html('');

        if (!productoId) {
            recargarMatrizSkus();
            return;
        }

        $.getJSON(rutas.skusFiltros, { psk_prd_id: productoId })
            .done(function (resp) {
                const atributos = resp.data?.atributos || [];

                if (atributos.length) {
                    const html = atributos.map(function (attr) {
                        const opts = ['<option value="">Todos</option>'].concat(
                            (attr.valores || []).map(function (v) {
                                return '<option value="' + escapeAttr(v.vat_id) + '">' + escapeHtml(v.vat_valor || '') + '</option>';
                            })
                        );
                        return '<div class="col-md-3">' +
                            '<label class="form-label">' + escapeHtml(attr.atr_nombre || 'Atributo') + '</label>' +
                            '<select class="form-select form-select-sm js-mtz-atributo" data-atr-id="' + escapeAttr(attr.atr_id) + '">' +
                                opts.join('') +
                            '</select>' +
                        '</div>';
                    }).join('');

                    $('#mtz-atributos-wrap').html(html).removeClass('d-none');
                }

                recargarMatrizSkus();
            })
            .fail(function () {
                recargarMatrizSkus();
            });
    }

    function recargarMatrizSkus() {
        $('#skus-matriz-wrap').html('<div class="p-3 text-body-secondary">Cargando...</div>');

        const params = {
            psk_prd_id: $('#mtz-flt-producto').val() || '',
            prd_mrc_id: $('#mtz-flt-mrc').val() || '',
            prd_mdl_id: $('#mtz-flt-mdl').val() || '',
            prd_lna_id: $('#mtz-flt-lna').val() || '',
            prd_ctg_id: $('#mtz-flt-ctg').val() || '',
            buscar: $('#mtz-flt-buscar').val() || '',
        };

        // Solo enviar atributo_filtros si hay valores seleccionados
        $('#mtz-atributos-wrap').find('.js-mtz-atributo').each(function () {
            const atrId = String($(this).data('atrId') || '').trim();
            const val = String($(this).val() || '').trim();
            if (atrId && val) {
                params['atributo_filtros[' + atrId + ']'] = val;
            }
        });

        $.getJSON(rutas.skusAgrupados, params).done(function (resp) {
            renderMatrizSkus(resp.data || []);
        }).fail(function (xhr) {
            renderMatrizSkusVacia(parseErrorMessage(xhr) || 'Error al cargar la matriz.');
        });
    }

    function recargarEtiquetado() {
        $.getJSON(rutas.skusData, { psk_prd_id: $('#flt-etq-producto').val() }).done(function (resp) {
            if ($.fn.DataTable.isDataTable('#etiquetado-table')) $('#etiquetado-table').DataTable().clear().destroy();
            $('#etiquetado-table').DataTable({
                data: resp.data || [],
                order: [[1, 'asc']],
                columns: [
                    { data: null, render: (r) => [r.producto_codigo, r.producto].filter(Boolean).join(' - ') },
                    { data: 'psk_codigo' },
                    { data: 'psk_nombre', defaultContent: '<span class="text-body-secondary">Usa nombre del producto base</span>' },
                    { data: 'psk_precio', render: (v) => '$' + Number(v || 0).toFixed(2) },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: (row) => '<button type="button" class="btn btn-sm btn-primary" data-action="generar-etiqueta" data-id="' + row.psk_id + '">Generar PDF</button>',
                    },
                ],
            });
        });
    }

    function cargarConfiguracionZebraBase() {
        $('#etq-width-mm').val(zebraDefaults.width_mm ?? 50);
        $('#etq-height-mm').val(zebraDefaults.height_mm ?? 30);
        $('#etq-margin-left-mm').val(zebraDefaults.margin_left_mm ?? 2);
        $('#etq-margin-right-mm').val(zebraDefaults.margin_right_mm ?? 2);
        $('#etq-margin-top-mm').val(zebraDefaults.margin_top_mm ?? 1.8);
        $('#etq-margin-bottom-mm').val(zebraDefaults.margin_bottom_mm ?? 1.8);
        $('#etq-barcode-height-mm').val(zebraDefaults.barcode_height_mm ?? 9.5);
        $('#etq-barcode-xres').val(zebraDefaults.barcode_xres ?? 0.33);
    }

    function obtenerConfiguracionZebraManual() {
        if (!$('#etq-usar-config-manual').is(':checked')) {
            return { usar_configuracion_manual: 0 };
        }

        const campos = [
            { key: 'width_mm', selector: '#etq-width-mm', min: 20, max: 120, label: 'Ancho' },
            { key: 'height_mm', selector: '#etq-height-mm', min: 10, max: 120, label: 'Alto' },
            { key: 'margin_left_mm', selector: '#etq-margin-left-mm', min: 0, max: 10, label: 'Margen izquierdo' },
            { key: 'margin_right_mm', selector: '#etq-margin-right-mm', min: 0, max: 10, label: 'Margen derecho' },
            { key: 'margin_top_mm', selector: '#etq-margin-top-mm', min: 0, max: 10, label: 'Margen superior' },
            { key: 'margin_bottom_mm', selector: '#etq-margin-bottom-mm', min: 0, max: 10, label: 'Margen inferior' },
            { key: 'barcode_height_mm', selector: '#etq-barcode-height-mm', min: 4, max: 25, label: 'Alto de barcode' },
            { key: 'barcode_xres', selector: '#etq-barcode-xres', min: 0.2, max: 0.8, label: 'Grosor de barra' },
        ];

        const config = { usar_configuracion_manual: 1 };

        for (const campo of campos) {
            const valor = Number($(campo.selector).val());
            if (!Number.isFinite(valor) || valor < campo.min || valor > campo.max) {
                AppUI.showMessage('Validación', `${campo.label} fuera de rango (${campo.min} - ${campo.max}).`, 'warning');
                return null;
            }
            config[campo.key] = valor;
        }

        return config;
    }

    function recargarProveedores() {
        $.getJSON(rutas.proveedoresData).done(function (resp) {
            if ($.fn.DataTable.isDataTable('#proveedores-table')) $('#proveedores-table').DataTable().clear().destroy();
            $('#proveedores-table').DataTable({
                data: resp.data || [],
                order: [[0, 'asc']],
                columns: [
                    { data: 'prv_nombre_empresa' },
                    { data: 'prv_nombre_asesor_ventas' },
                    { data: 'prv_categoria', defaultContent: '-' },
                    { data: 'numeros_contacto_texto', defaultContent: '-' },
                    { data: 'prv_razon_social', defaultContent: '-' },
                    { data: 'prv_rfc' },
                    { data: 'prv_correo', defaultContent: '-' },
                    { data: 'prv_condiciones_pago', defaultContent: '-' },
                    { data: 'prv_tiempo_respuesta', defaultContent: '-' },
                    { data: 'prv_estatus', render: (v) => estatusBadge(v) },
                    { data: null, orderable: false, searchable: false, render: (row) => buildActions('proveedor', row.prv_id, row.prv_nombre_empresa, row.prv_estatus === 'activo') },
                ],
            });
        });
    }

    function llenarSelectSimple(selector, items, valueKey, textBuilder, firstLabel = 'Selecciona') {
        const html = ['<option value=\"\">' + firstLabel + '</option>'];
        (items || []).forEach(function (item) {
            html.push('<option value=\"' + item[valueKey] + '\">' + textBuilder(item) + '</option>');
        });
        $(selector).html(html.join(''));
    }

    function llenarSelectMultiple(selector, items, valueKey, textBuilder) {
        const html = [];
        (items || []).forEach(function (item) {
            html.push('<option value=\"' + item[valueKey] + '\">' + textBuilder(item) + '</option>');
        });
        $(selector).html(html.join(''));
    }

    function llenarSelectUnidades(selector, unidades) {
        const html = ['<option value=\"\">Selecciona</option>'];
        (unidades || []).forEach(function (u) {
            const label = u.nombre + (u.codigo ? ' (' + u.codigo + ')' : '') + (u.es_predeterminada ? ' ★' : '');
            html.push('<option value=\"' + u.id + '\"' + (u.es_predeterminada ? ' data-predeterminada=\"1\"' : '') + '>' + label + '</option>');
        });
        $(selector).html(html.join(''));
    }

    function recargarOpcionesDependientes() {
        const productoSkuSeleccionado = String($('#flt-sku-producto').val() || '');
        const productoEtiquetaSeleccionado = String($('#flt-etq-producto').val() || '');
        const productoModalSeleccionado = String($('#psk_prd_id').val() || '');
        const filtrosSkuSeleccionados = obtenerFiltrosSkuSeleccionados();

        $.when(
            $.getJSON(rutas.baseData('marcas')),
            $.getJSON(rutas.baseData('lineas')),
            $.getJSON(rutas.baseData('categorias')),
            $.getJSON(rutas.baseData('unidades')),
            $.getJSON(rutas.proveedoresData),
            $.getJSON(rutas.atributosData),
            $.getJSON(rutas.valoresData),
            $.getJSON(rutas.productosData)
        ).done(function (marcasResp, lineasResp, categoriasResp, unidadesResp, proveedoresResp, atributosResp, valoresResp, productosResp) {
            const marcas = (marcasResp[0]?.data || []).filter(x => x.estatus === 'activo');
            const lineas = (lineasResp[0]?.data || []).filter(x => x.estatus === 'activo');
            const categorias = (categoriasResp[0]?.data || []).filter(x => x.estatus === 'activo');
            const unidades = (unidadesResp[0]?.data || []).filter(x => x.estatus === 'activo');
            const proveedores = (proveedoresResp[0]?.data || []).filter(x => x.prv_estatus === 'activo');
            const atributos = (atributosResp[0]?.data || []).filter(x => x.atr_estatus === 'activo');
            const valores = (valoresResp[0]?.data || []).filter(x => x.vat_estatus === 'activo');
            const productos = (productosResp[0]?.data || []).filter(x => x.prd_estatus === 'activo');

            llenarSelectSimple('#prd_mrc_id', marcas, 'id', (x) => x.nombre, 'Selecciona');
            llenarSelectSimple('#prd_lna_id', lineas, 'id', (x) => x.nombre, 'Selecciona');
            llenarSelectSimple('#prd_prv_id', proveedores, 'prv_id', (x) => x.prv_nombre_empresa, 'Sin proveedor asignado');
            catalogoState.categorias = categorias;
            // El select de conceptos arranca vacío; se llena al elegir una línea
            $('#prd_ctg_id').html('<option value="">Selecciona una línea primero</option>');
            llenarSelectUnidades('#prd_umd_id', unidades);
            catalogoState.unidades = unidades;
            catalogoState.proveedores = proveedores;

            llenarSelectSimple('#vat_atr_id', atributos, 'atr_id', (x) => x.atr_nombre, 'Selecciona');

            llenarSelectSimple('#psk_prd_id', productos, 'prd_id', (x) => x.prd_codigo + ' - ' + x.prd_nombre, 'Selecciona');
            llenarSelectSimple('#flt-sku-producto', productos, 'prd_id', (x) => x.prd_codigo + ' - ' + x.prd_nombre, 'Todos los productos');
            llenarSelectSimple('#mtz-flt-producto', productos, 'prd_id', (x) => x.prd_codigo + ' - ' + x.prd_nombre, 'Todos los productos');
            llenarSelectSimple('#flt-etq-producto', productos, 'prd_id', (x) => x.prd_codigo + ' - ' + x.prd_nombre, 'Todos los productos');

            if (productoModalSeleccionado && productos.some((x) => String(x.prd_id) === productoModalSeleccionado)) {
                $('#psk_prd_id').val(productoModalSeleccionado);
            }
            if (productoEtiquetaSeleccionado && productos.some((x) => String(x.prd_id) === productoEtiquetaSeleccionado)) {
                $('#flt-etq-producto').val(productoEtiquetaSeleccionado);
            }

            llenarSelectMultiple('#psk_valor_atributo_ids', valores, 'vat_id', (x) => (x.atributo || 'Atributo') + ': ' + x.vat_valor);

            catalogoState.atributos = atributos;
            catalogoState.valores = valores;
            sanitizarEstadoProductoVariable();
            renderConfiguracionVariableProducto();
            recargarEtiquetado();
        });
    }

    $('#catalogo-tipo').on('change', function () {
        sincronizarUIcatalogo(obtenerTipoCatalogoActual());
        recargarCatalogo();
    });
    $('.btn-catalogo-target').on('click', function () {
        const tipo = $(this).data('catalogo-target');
        $('#catalogo-tipo').val(tipo);
        sincronizarUIcatalogo(tipo);
        recargarCatalogo();
    });
    $('#flt-sku-producto').on('change', function () {
        cargarFiltrosSkuProducto($(this).val(), true);
    });
    $(document).on('change', '.js-sku-filtro-atributo', recargarVistaSkus);
    $(document).on('click', '[data-sku-view]', function () {
        const vista = String($(this).data('skuView') || 'tabla');
        aplicarVistaSkus(vista);
        recargarVistaSkus();
    });

    $('#mtz-flt-producto').on('change', function () {
        cargarFiltrosMatrizProducto($(this).val());
    });
    $('#mtz-flt-mrc, #mtz-flt-mdl, #mtz-flt-lna, #mtz-flt-ctg').on('change', recargarMatrizSkus);
    $('#btn-mtz-limpiar').on('click', function () {
        $('#mtz-flt-producto, #mtz-flt-mrc, #mtz-flt-mdl, #mtz-flt-lna, #mtz-flt-ctg').val('');
        $('#mtz-flt-buscar').val('');
        $('#mtz-atributos-wrap').addClass('d-none').html('');
        recargarMatrizSkus();
    });
    $('#mtz-flt-buscar').on('keydown', function (e) {
        if (e.key === 'Enter') recargarMatrizSkus();
    }).on('blur', recargarMatrizSkus);
    $(document).on('change', '.js-mtz-atributo', recargarMatrizSkus);
    $('#flt-etq-producto').on('change', recargarEtiquetado);
    $('#etq-usar-config-manual').on('change', function () {
        $('#etq-config-manual-wrap').toggleClass('d-none', !$(this).is(':checked'));
    });
    $('#btn-restaurar-zebra').on('click', function () {
        cargarConfiguracionZebraBase();
    });
    $('#btn-productos-vista-grid').on('click', function () { aplicarVistaProductos('cuadricula'); });
    $('#btn-productos-vista-list').on('click', function () { aplicarVistaProductos('lista'); });
    $('#productos-busqueda-rapida').on('input', aplicarFiltroProductosLocal);

    $('#btn-nuevo-catalogo').on('click', function () {
        const tipo = obtenerTipoCatalogoActual();
        if (tipo === 'modelos') {
            $('#form-modelo')[0].reset();
            $('#mdl_id').val('');
            $('#modal-modelo-title').text('Nuevo Modelo');
            recargarMarcasModalModelo();
            modalModelo.show();
        } else {
            $('#form-catalogo')[0].reset();
            $('#cat_id').val('');
            sincronizarUIcatalogo(tipo);
            modalCatalogo.show();
        }
    });
    $('#btn-nuevo-atributo').on('click', function () { $('#form-atributo')[0].reset(); $('#atr_id').val(''); modalAtributo.show(); });
    $('#btn-nuevo-valor').on('click', function () { $('#form-valor')[0].reset(); $('#vat_id').val(''); modalValor.show(); });
    $('#btn-nuevo-producto').on('click', function () {
        prepararNuevoProducto();
        abrirTabProducto('Alta de producto base', 'Nuevo producto');
    });
    $(document).on('click', '.btn-cancelar-producto-tab', function () {
        cerrarTabProducto(true);
    });
    $('#btn-nuevo-proveedor').on('click', function () {
        prepararNuevoProveedor();
        modalProveedor.show();
    });
    $('#btn-agregar-contacto-proveedor').on('click', function () {
        agregarContactoProveedor('');
    });
    $(document).on('click', '.btn-remover-contacto-proveedor', function () {
        const total = $('#proveedor-contactos-list .proveedor-contacto-item').length;
        if (total <= 1) {
            $(this).closest('.proveedor-contacto-item').find('input').val('');
            return;
        }
        $(this).closest('.proveedor-contacto-item').remove();
    });
    $('input[name="prd_tipo"]').on('change', actualizarUiTipoProducto);

    $(document).on('change', '#producto-atributos-select', function () {
        const seleccionados = ($(this).val() || []).map(Number);
        catalogoState.productoAtributosSeleccionados = [...new Set(seleccionados)];

        Object.keys(catalogoState.productoValoresSeleccionados || {}).forEach(function (atributoId) {
            if (!catalogoState.productoAtributosSeleccionados.includes(Number(atributoId))) {
                delete catalogoState.productoValoresSeleccionados[atributoId];
            }
        });

        renderConfiguracionVariableProducto();
        setTimeout(function () {
            const $select = $('#producto-atributos-select');
            if ($select.length && $select.hasClass('select2-hidden-accessible')) {
                $select.select2('open');
            }
        }, 0);
    });

    $(document).on('change', '.producto-valores-select', function () {
        const atributoId = Number($(this).data('atributo-id'));
        const seleccionados = ($(this).val() || []).map(Number);
        catalogoState.productoValoresSeleccionados[atributoId] = [...new Set(seleccionados)];
        renderResumenProducto();
        renderConfiguracionVariableProducto();
        const selector = '.producto-valores-select[data-atributo-id="' + atributoId + '"]';
        setTimeout(function () {
            const $select = $(selector);
            if ($select.length && $select.hasClass('select2-hidden-accessible')) {
                $select.select2('open');
            }
        }, 0);
    });

    $(document).on('click', '#btn-prd-corrida-add', function () {
        sincronizarCorridasDesdeUI();
        const atrRapido = Number($('#prd-corrida-atr-objetivo').val() || catalogoState.productoCorridaAtributoObjetivo || catalogoState.productoAtributosSeleccionados[0] || 0);
        catalogoState.productoCorridaAtributoObjetivo = atrRapido;
        if (!atrRapido) {
            AppUI.showMessage('Validación', 'Selecciona el atributo objetivo de la corrida.', 'warning');
            return;
        }
        const valoresDisponibles = obtenerValoresAtributoDisponibles(atrRapido);
        if (!valoresDisponibles.length) {
            AppUI.showMessage('Validación', 'Primero selecciona valores para ese atributo.', 'warning');
            return;
        }
        const valoresLibres = obtenerValoresCorridaDisponibles(atrRapido);
        if (!valoresLibres.length) {
            AppUI.showMessage('Validación', 'Todos los valores seleccionados para ese atributo ya están asignados a una corrida.', 'warning');
            return;
        }
        const primerDisponible = valoresLibres[0];
        catalogoState.productoCorridas.push({
            uid: Date.now() + '-' + Math.random().toString(36).slice(2),
            nombre: tituloCorridaSugerido(atrRapido),
            atr_id: atrRapido,
            valor_ids: primerDisponible ? [Number(primerDisponible)] : [],
            precio_base: Number($('#prd_precio_base').val() || 0),
            costo_base: Number($('#prd_costo').val() || 0),
            stock_minimo: Number($('#prd_stock_minimo').val() || 0),
            stock_maximo: Number($('#prd_stock_maximo').val() || 0),
        });
        renderConfiguracionVariableProducto();
    });

    $(document).on('change', '#prd-corrida-atr-objetivo', function () {
        catalogoState.productoCorridaAtributoObjetivo = Number($(this).val() || 0);
    });

    $(document).on('click', '.js-prd-corrida-remove', function () {
        const uid = String($(this).data('corridaUid') || '');
        if (!uid) return;
        if ((catalogoState.productoCorridas || []).length <= 1) {
            catalogoState.productoCorridas = [];
            renderConfiguracionVariableProducto();
            return;
        }
        sincronizarCorridasDesdeUI();
        catalogoState.productoCorridas = (catalogoState.productoCorridas || []).filter((corrida) => String(corrida.uid || '') !== uid);
        renderConfiguracionVariableProducto();
    });


    $(document).on('change', '.js-prd-corrida-valores', function () {
        const idx = Number($(this).data('idx') || -1);
        if (idx < 0 || !catalogoState.productoCorridas[idx]) return;
        const seleccion = ($(this).val() || []).map((v) => Number(v)).filter((v) => v > 0);
        catalogoState.productoCorridas[idx].valor_ids = [...new Set(seleccion)];
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

    $('#form-catalogo').on('submit', function (e) {
        e.preventDefault();
        const tipo = $('#catalogo-tipo').val();
        const id = $('#cat_id').val();
        $.ajax({ url: id ? rutas.baseUpdate(tipo, id) : rutas.baseStore(tipo), method: id ? 'PUT' : 'POST', data: $(this).serialize(), dataType: 'json' })
            .done((resp) => { modalCatalogo.hide(); recargarCatalogo(); recargarOpcionesDependientes(); AppUI.showMessage('Éxito', resp.message || 'Guardado correctamente.', 'success'); })
            .fail((xhr) => AppUI.showMessage('Error', parseErrorMessage(xhr), 'error'));
    });

    $('#form-modelo').on('submit', function (e) {
        e.preventDefault();
        const id = $('#mdl_id').val();
        $.ajax({ url: id ? rutas.modeloUpdate(id) : rutas.modeloStore, method: id ? 'PUT' : 'POST', data: $(this).serialize(), dataType: 'json' })
            .done((resp) => { modalModelo.hide(); recargarModelos(); recargarOpcionesDependientes(); AppUI.showMessage('Éxito', resp.message || 'Guardado correctamente.', 'success'); })
            .fail((xhr) => AppUI.showMessage('Error', parseErrorMessage(xhr), 'error'));
    });

    $('#form-atributo').on('submit', function (e) {
        e.preventDefault();
        const id = $('#atr_id').val();
        $.ajax({ url: id ? rutas.atributoUpdate(id) : rutas.atributoStore, method: id ? 'PUT' : 'POST', data: $(this).serialize(), dataType: 'json' })
            .done((resp) => { modalAtributo.hide(); recargarAtributos(); recargarOpcionesDependientes(); AppUI.showMessage('Éxito', resp.message || 'Guardado correctamente.', 'success'); })
            .fail((xhr) => AppUI.showMessage('Error', parseErrorMessage(xhr), 'error'));
    });

    $('#form-valor').on('submit', function (e) {
        e.preventDefault();
        const id = $('#vat_id').val();
        $.ajax({ url: id ? rutas.valorUpdate(id) : rutas.valorStore, method: id ? 'PUT' : 'POST', data: $(this).serialize(), dataType: 'json' })
            .done((resp) => { modalValor.hide(); recargarValores(); recargarAtributos(); recargarOpcionesDependientes(); AppUI.showMessage('Éxito', resp.message || 'Guardado correctamente.', 'success'); })
            .fail((xhr) => AppUI.showMessage('Error', parseErrorMessage(xhr), 'error'));
    });

    $('#form-producto').on('submit', function (e) {
        e.preventDefault();
        const id = $('#prd_id').val();
        const formData = new FormData(this);
        const esVariable = obtenerTipoProductoActual() === 'variable';
        if (esVariable) {
            sincronizarCorridasDesdeUI();
            sanitizarCorridasProducto();
            const errorCorridas = validarCorridasProducto();
            if (errorCorridas) {
                AppUI.showMessage('Validación', errorCorridas, 'warning');
                return;
            }
            formData.delete('corridas');
            (catalogoState.productoCorridas || []).forEach((corrida, idx) => {
                formData.append('corridas[' + idx + '][crc_nombre]', String(corrida.nombre || '').trim());
                formData.append('corridas[' + idx + '][crc_atr_id]', String(Number(corrida.atr_id || 0)));
                (corrida.valor_ids || []).forEach((valorId) => {
                    formData.append('corridas[' + idx + '][crc_valor_ids][]', String(Number(valorId || 0)));
                });
                formData.append('corridas[' + idx + '][crc_precio_base]', String(Number(corrida.precio_base || 0).toFixed(2)));
                formData.append('corridas[' + idx + '][crc_costo_base]', String(Number(corrida.costo_base || 0).toFixed(2)));
                formData.append('corridas[' + idx + '][crc_stock_minimo]', String(Math.max(0, Math.trunc(Number(corrida.stock_minimo || 0)))));
                formData.append('corridas[' + idx + '][crc_stock_maximo]', String(Math.max(0, Math.trunc(Number(corrida.stock_maximo || 0)))));
            });
            const primera = catalogoState.productoCorridas[0];
            formData.set('prd_precio_base', String(Number(primera?.precio_base || 0).toFixed(2)));
            formData.set('prd_costo', String(Number(primera?.costo_base || 0).toFixed(2)));
            formData.set('prd_stock_minimo', String(Math.max(0, Math.trunc(Number(primera?.stock_minimo || 0)))));
            formData.set('prd_stock_maximo', String(Math.max(0, Math.trunc(Number(primera?.stock_maximo || 0)))));
        }
        if (id) {
            formData.append('_method', 'PUT');
        }
        $.ajax({
            url: id ? rutas.productoUpdate(id) : rutas.productoStore,
            method: 'POST',
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false,
        })
            .done((resp) => { cerrarTabProducto(true); recargarProductos(); recargarVistaSkus(); recargarOpcionesDependientes(); AppUI.showMessage('Éxito', resp.message || 'Guardado correctamente.', 'success'); })
            .fail((xhr) => AppUI.showMessage('Error', parseErrorMessage(xhr), 'error'));
    });

    $('#form-sku').on('submit', function (e) {
        e.preventDefault();
        const id = $('#psk_id').val();
        $.ajax({ url: id ? rutas.skuUpdate(id) : rutas.skuStore, method: id ? 'PUT' : 'POST', data: $(this).serialize(), dataType: 'json' })
            .done((resp) => { modalSku.hide(); recargarVistaSkus(); recargarProductos(); recargarOpcionesDependientes(); AppUI.showMessage('Éxito', resp.message || 'Guardado correctamente.', 'success'); })
            .fail((xhr) => AppUI.showMessage('Error', parseErrorMessage(xhr), 'error'));
    });

    $('#form-proveedor').on('submit', function (e) {
        e.preventDefault();
        const id = $('#prv_id').val();
        $.ajax({ url: id ? rutas.proveedorUpdate(id) : rutas.proveedorStore, method: id ? 'PUT' : 'POST', data: $(this).serialize(), dataType: 'json' })
            .done((resp) => { modalProveedor.hide(); recargarProveedores(); AppUI.showMessage('Éxito', resp.message || 'Guardado correctamente.', 'success'); })
            .fail((xhr) => AppUI.showMessage('Error', parseErrorMessage(xhr), 'error'));
    });

    $(document).on('click', '[data-action="edit-catalogo"]', function () {
        const tipo = $('#catalogo-tipo').val();
        const id = $(this).data('id');
        $.getJSON(rutas.baseShow(tipo, id)).done(function (resp) {
            const d = resp.data || {};
            $('#cat_id').val(d.id || '');
            $('#cat_nombre').val(d.nombre || '');
            $('#cat_codigo').val(d.codigo || '');
            $('#cat_estatus').val(d.estatus || 'activo');
            if (tipo === 'categorias') {
                $('#cat_lna_id').val(String(d.lna_id || ''));
            }
            if (tipo === 'unidades') {
                $('#cat_tipo_cantidad').val(d.tipo_cantidad || 'entero');
                $('#cat_es_predeterminada').prop('checked', !!d.es_predeterminada);
            }
            sincronizarUIcatalogo(tipo);
            $('#modal-catalogo-title').text('Editar ' + (catalogoLabels[tipo] || 'Catálogo'));
            modalCatalogo.show();
        });
    });

    function actualizarSelectCategoria(lnaId, seleccionarId) {
        if (!lnaId) {
            $('#prd_ctg_id').html('<option value="">Selecciona una línea primero</option>').val('');
            return;
        }
        const filtradas = (catalogoState.categorias || []).filter(function (c) {
            return String(c.lna_id) === String(lnaId);
        });
        if (filtradas.length === 0) {
            $('#prd_ctg_id').html('<option value="">Sin conceptos para esta línea</option>').val('');
        } else {
            const html = ['<option value="">Selecciona</option>'];
            filtradas.forEach(function (c) {
                html.push('<option value="' + c.id + '">' + c.nombre + '</option>');
            });
            $('#prd_ctg_id').html(html.join(''));
            if (seleccionarId) { $('#prd_ctg_id').val(String(seleccionarId)); }
        }
    }

    // Cuando cambia la línea en el form de producto → filtrar conceptos
    $('#prd_lna_id').on('change', function () {
        $('#prd_ctg_id').val('');
        actualizarSelectCategoria($(this).val());
    });

    // Cuando cambia la marca en el form de producto → filtrar modelos
    $('#prd_mrc_id').on('change', function () {
        $('#prd_mdl_id').val('');
        actualizarSelectModelo($(this).val());
    });

    $(document).on('change', 'input[name="prd_imagen_metodo"]', function () {
        $('#prd_imagen_reset').val('0');
        actualizarUiMetodoImagen();
    });

    $('#prd_imagen_archivo').on('change', function (event) {
        $('#prd_imagen_reset').val('0');
        limpiarPollingImagenProducto();
        catalogoState.productoImagenSesion = null;
        $('#prd_imagen_temp_token').val('');
        const file = event.target.files && event.target.files[0];
        if (!file) {
            return;
        }

        establecerPreviewImagenProducto(URL.createObjectURL(file));
    });

    $('#prd_imagen_url').on('input', function () {
        $('#prd_imagen_reset').val('0');
        limpiarPollingImagenProducto();
        catalogoState.productoImagenSesion = null;
        $('#prd_imagen_temp_token').val('');
        const url = ($(this).val() || '').trim();
        establecerPreviewImagenProducto(url || null);
    });

    $('#btn-regenerar-qr-producto').on('click', function () {
        $('#prd_imagen_reset').val('0');
        iniciarSesionImagenProducto();
    });

    $('#btn-quitar-imagen-producto').on('click', function () {
        $('#prd_imagen_reset').val('1');
        limpiarPollingImagenProducto();
        catalogoState.productoImagenSesion = null;
        $('input[name="prd_imagen_metodo"]').prop('checked', false);
        actualizarUiMetodoImagen();
        $('#prd_imagen_archivo').val('');
        $('#prd_imagen_url').val('');
        $('#prd_imagen_temp_token').val('');
        establecerPreviewImagenProducto(null);
        $('#producto-imagen-mobile-status').text('La imagen se quitará al guardar el producto.');
    });

    $(document).on('click', '[data-action="edit-modelo"]', function () {
        const id = $(this).data('id');
        $.getJSON(rutas.modeloShow(id)).done(function (resp) {
            const d = resp.data || {};
            $('#mdl_id').val(d.mdl_id || '');
            $('#mdl_nombre').val(d.mdl_nombre || '');
            $('#mdl_clave').val(d.mdl_clave || '');
            $('#mdl_estatus').val(d.mdl_estatus || 'activo');
            // Recargar marcas y marcar las seleccionadas
            recargarMarcasModalModelo();
            // Pequeño delay para que se rendericen los checkboxes
            setTimeout(function () {
                $('#mdl-marcas-checklist input[type="checkbox"]').each(function () {
                    const marcaId = parseInt($(this).val(), 10);
                    const checked = (d.marca_ids || []).includes(marcaId);
                    $(this).prop('checked', checked);
                    $(this).closest('.cc-attr-option').toggleClass('is-selected', checked);
                });
            }, 80);
            $('#modal-modelo-title').text('Editar Modelo');
            modalModelo.show();
        });
    });

    $(document).on('click', '[data-action="toggle-modelo"]', function () {
        $.ajax({ url: rutas.modeloEstatus($(this).data('id')), method: 'PATCH', data: { mdl_estatus: $(this).data('next') }, dataType: 'json' })
            .done((resp) => { recargarModelos(); recargarOpcionesDependientes(); AppUI.showMessage('Éxito', resp.message || 'Estatus actualizado.', 'success'); })
            .fail((xhr) => AppUI.showMessage('Error', parseErrorMessage(xhr), 'error'));
    });

    $(document).on('click', '[data-action="delete-modelo"]', function () {
        const id = $(this).data('id');
        confirmDelete('¿Deseas eliminar el modelo seleccionado?', function () {
            $.ajax({ url: rutas.modeloDelete(id), method: 'DELETE', dataType: 'json' })
                .done((resp) => { recargarModelos(); recargarOpcionesDependientes(); AppUI.showMessage('Éxito', resp.message || 'Eliminado correctamente.', 'success'); })
                .fail((xhr) => AppUI.showMessage('Error', parseErrorMessage(xhr), 'error'));
        });
    });

    // Estilizar checkboxes del modal de modelo
    $(document).on('change', '#mdl-marcas-checklist input[type="checkbox"]', function () {
        $(this).closest('.cc-attr-option').toggleClass('is-selected', $(this).is(':checked'));
    });

    $(document).on('change', '#prd-almacenes-checklist input[type="checkbox"]', function () {
        $(this).closest('.cc-attr-option').toggleClass('is-selected', $(this).is(':checked'));
    });

    $(document).on('click', '[data-action="edit-atributo"]', function () {
        const id = $(this).data('id');
        $.getJSON(rutas.atributoShow(id)).done(function (resp) {
            const d = resp.data || {};
            $('#atr_id').val(d.atr_id || '');
            $('#atr_nombre').val(d.atr_nombre || '');
            $('#atr_tipo').val(d.atr_tipo || '');
            $('#atr_estatus').val(d.atr_estatus || 'activo');
            modalAtributo.show();
        });
    });

    $(document).on('click', '[data-action="edit-valor"]', function () {
        const id = $(this).data('id');
        $.getJSON(rutas.valorShow(id)).done(function (resp) {
            const d = resp.data || {};
            $('#vat_id').val(d.vat_id || '');
            $('#vat_atr_id').val(String(d.vat_atr_id || ''));
            $('#vat_valor').val(d.vat_valor || '');
            $('#vat_estatus').val(d.vat_estatus || 'activo');
            modalValor.show();
        });
    });

    $(document).on('click', '[data-action="edit-producto"]', function () {
        const id = $(this).data('id');
        $.getJSON(rutas.productoShow(id)).done(function (resp) {
            const d = resp.data || {};
            $('#prd_id').val(d.prd_id || '');
            $('#prd_codigo').val(d.prd_codigo || '');
            $('#prd_codigo_barras').val(d.prd_codigo_barras || '');
            $('#prd_clave_sat').val(d.prd_clave_sat || '');
            $('#prd_nombre').val(d.prd_nombre || '');
            $('#prd_descripcion').val(d.prd_descripcion || '');
            $('#prd_precio_base').val(d.prd_precio_base ?? '0.00');
            $('#prd_costo').val(d.prd_costo ?? '0.00');
            $('#prd_stock_minimo').val(d.prd_stock_minimo ?? 0);
            $('#prd_stock_maximo').val(d.prd_stock_maximo ?? 0);
            $('#prd_mrc_id').val(String(d.prd_mrc_id || ''));
            $('#prd_prv_id').val(String(d.prd_prv_id || ''));
            $('#prd_imagen_reset').val('0');
            $('#prd_imagen_archivo').val('');
            $('#prd_imagen_url').val(d.prd_imagen_tipo === 'url' ? (d.prd_imagen_url || '') : '');
            establecerPreviewImagenProducto(d.prd_imagen_preview_url || null);
            if (d.prd_imagen_tipo === 'url') {
                $('input[name="prd_imagen_metodo"][value="url"]').prop('checked', true);
            } else if (d.prd_imagen_tipo === 'archivo') {
                $('input[name="prd_imagen_metodo"][value="archivo"]').prop('checked', true);
            } else {
                $('input[name="prd_imagen_metodo"][value="archivo"]').prop('checked', true);
            }
            iniciarSesionImagenProducto();
            // Cargar modelos de la marca y seleccionar el modelo del producto
            if (d.prd_mrc_id) {
                $.getJSON(rutas.modelosPorMarca(d.prd_mrc_id)).done(function (mResp) {
                    const modelos = mResp.data || [];
                    if (modelos.length > 0) {
                        const html = ['<option value=\"\">Sin modelo</option>'];
                        modelos.forEach(function (m) { html.push('<option value=\"' + m.id + '\">' + m.nombre + '</option>'); });
                        $('#prd_mdl_id').html(html.join(''));
                        $('#prd-modelo-wrap').show();
                        if (d.prd_mdl_id) { $('#prd_mdl_id').val(String(d.prd_mdl_id)); }
                    } else {
                        $('#prd_mdl_id').html('<option value=\"\">Sin modelo</option>').val('');
                        $('#prd-modelo-wrap').hide();
                    }
                });
            } else {
                $('#prd_mdl_id').html('<option value=\"\">Sin modelo</option>').val('');
                $('#prd-modelo-wrap').hide();
            }
            $('#prd_lna_id').val(String(d.prd_lna_id || ''));
            actualizarSelectCategoria(d.prd_lna_id, d.prd_ctg_id);
            $('#prd_dsc_id').val(String(d.prd_dsc_id || ''));
            $('#prd_umd_id').val(String(d.prd_umd_id || ''));
            $('#prd-almacenes-checklist input[type="checkbox"]').each(function () {
                const almacenId = parseInt($(this).val(), 10);
                const checked = (d.almacen_ids || []).includes(almacenId);
                $(this).prop('checked', checked);
                $(this).closest('.cc-attr-option').toggleClass('is-selected', checked);
            });
            $('#prd_estatus').val(d.prd_estatus || 'activo');
            if ((d.prd_tipo || 'simple') === 'variable') {
                $('#prd_tipo_variable').prop('checked', true);
            } else {
                $('#prd_tipo_simple').prop('checked', true);
            }
            catalogoState.productoAtributosSeleccionados = (d.atributo_ids || []).map(Number);
            catalogoState.productoValoresSeleccionados = normalizarMapaValoresProducto(d.atributo_valores || {});
            catalogoState.productoCorridas = normalizarCorridasProducto(d.corridas || []);
            $('#producto-form-heading').text('Editar producto base');
            $('#tab-producto-form-label').text('Editar producto');
            actualizarUiTipoProducto();
            actualizarUiMetodoImagen();
            abrirTabProducto('Editar producto base', 'Editar producto');
        });
    });

    $(document).on('click', '[data-action="edit-sku"]', function () {
        const id = $(this).data('id');
        $.getJSON(rutas.skuShow(id)).done(function (resp) {
            const d = resp.data || {};
            $('#psk_id').val(d.psk_id || '');
            $('#psk_prd_id').val(String(d.psk_prd_id || ''));
            $('#psk_codigo').val(d.psk_codigo || '');
            $('#psk_codigo_barras').val(d.psk_codigo_barras || '');
            $('#psk_nombre').val(d.psk_nombre || '');
            $('#psk_costo').val(Number(d.psk_costo ?? 0).toFixed(2));
            $('#psk_precio').val(Number(d.psk_precio ?? 0).toFixed(2));
            $('#psk_stock_minimo').val(d.psk_stock_minimo ?? 0);
            $('#psk_stock_maximo').val(d.psk_stock_maximo ?? 0);
            $('#psk_estatus').val(d.psk_estatus || 'activo');
            $('#psk_valor_atributo_ids').val((d.valor_atributo_ids || []).map(String)).prop('disabled', true).prop('required', false);
            $('#psk-valores-wrap').hide();
            $('#modal-sku-title').text('Editar SKU / Variante');
            modalSku.show();
        });
    });

    $(document).on('click', '[data-action="edit-proveedor"]', function () {
        const id = $(this).data('id');
        $.getJSON(rutas.proveedorShow(id)).done(function (resp) {
            const d = resp.data || {};
            $('#prv_id').val(d.prv_id || '');
            $('#prv_nombre_empresa').val(d.prv_nombre_empresa || '');
            $('#prv_nombre_asesor_ventas').val(d.prv_nombre_asesor_ventas || '');
            $('#prv_categoria').val(d.prv_categoria || '');
            $('#prv_razon_social').val(d.prv_razon_social || '');
            $('#prv_rfc').val(d.prv_rfc || '');
            $('#prv_correo').val(d.prv_correo || '');
            $('#prv_condiciones_pago').val(d.prv_condiciones_pago || '');
            $('#prv_tiempo_respuesta').val(d.prv_tiempo_respuesta || '');
            $('#prv_estatus').val(d.prv_estatus || 'activo');
            limpiarContactosProveedor(d.numeros_contacto || []);
            $('#modal-proveedor-title').text('Editar proveedor');
            modalProveedor.show();
        });
    });

    $(document).on('click', '[data-action="generar-etiqueta"]', function () {
        const skuId = $(this).data('id');
        const copias = Number($('#etq-copias').val() || 1);

        if (!Number.isInteger(copias) || copias < 1 || copias > 50) {
            AppUI.showMessage('Validación', 'La cantidad de copias debe estar entre 1 y 50.', 'warning');
            return;
        }

        const configManual = obtenerConfiguracionZebraManual();
        if (configManual === null) {
            return;
        }

        const params = new URLSearchParams({
            formato: 'zebra_50x30',
            copias: String(copias),
        });

        Object.entries(configManual).forEach(([key, value]) => {
            params.set(key, String(value));
        });

        const query = '?' + params.toString();
        AppUI.showLoader();
        window.open(rutas.skuEtiqueta(skuId) + query, '_blank', 'noopener');
        setTimeout(() => AppUI.hideLoader(), 700);
    });

    document.getElementById('modal-proveedor').addEventListener('hidden.bs.modal', function () {
        prepararNuevoProveedor();
    });

    document.getElementById('modal-sku').addEventListener('hidden.bs.modal', function () {
        $('#form-sku')[0].reset();
        $('#psk_id').val('');
        $('#psk_valor_atributo_ids').val([]).prop('disabled', false).prop('required', true);
        $('#psk-valores-wrap').show();
        $('#psk_stock_minimo').val('');
        $('#psk_stock_maximo').val('');
        $('#psk_costo').val('');
        $('#psk_precio').val('');
        $('#modal-sku-title').text('SKU / Variante');
    });

    $(document).on('click', '[data-action="toggle-catalogo"]', function () {
        const tipo = $('#catalogo-tipo').val();
        $.ajax({ url: rutas.baseEstatus(tipo, $(this).data('id')), method: 'PATCH', data: { estatus: $(this).data('next') }, dataType: 'json' })
            .done((resp) => { recargarCatalogo(); recargarOpcionesDependientes(); AppUI.showMessage('Éxito', resp.message || 'Estatus actualizado.', 'success'); })
            .fail((xhr) => AppUI.showMessage('Error', parseErrorMessage(xhr), 'error'));
    });

    $(document).on('click', '[data-action="toggle-atributo"]', function () {
        $.ajax({ url: rutas.atributoEstatus($(this).data('id')), method: 'PATCH', data: { atr_estatus: $(this).data('next') }, dataType: 'json' })
            .done((resp) => { recargarAtributos(); recargarOpcionesDependientes(); AppUI.showMessage('Éxito', resp.message || 'Estatus actualizado.', 'success'); })
            .fail((xhr) => AppUI.showMessage('Error', parseErrorMessage(xhr), 'error'));
    });

    $(document).on('click', '[data-action="toggle-valor"]', function () {
        $.ajax({ url: rutas.valorEstatus($(this).data('id')), method: 'PATCH', data: { vat_estatus: $(this).data('next') }, dataType: 'json' })
            .done((resp) => { recargarValores(); recargarOpcionesDependientes(); AppUI.showMessage('Éxito', resp.message || 'Estatus actualizado.', 'success'); })
            .fail((xhr) => AppUI.showMessage('Error', parseErrorMessage(xhr), 'error'));
    });

    $(document).on('click', '[data-action="toggle-producto"]', function () {
        $.ajax({ url: rutas.productoEstatus($(this).data('id')), method: 'PATCH', data: { prd_estatus: $(this).data('next') }, dataType: 'json' })
            .done((resp) => { recargarProductos(); recargarOpcionesDependientes(); AppUI.showMessage('Éxito', resp.message || 'Estatus actualizado.', 'success'); })
            .fail((xhr) => AppUI.showMessage('Error', parseErrorMessage(xhr), 'error'));
    });

    $(document).on('click', '[data-action="toggle-sku"]', function () {
        $.ajax({ url: rutas.skuEstatus($(this).data('id')), method: 'PATCH', data: { psk_estatus: $(this).data('next') }, dataType: 'json' })
            .done((resp) => { recargarVistaSkus(); recargarOpcionesDependientes(); AppUI.showMessage('Éxito', resp.message || 'Estatus actualizado.', 'success'); })
            .fail((xhr) => AppUI.showMessage('Error', parseErrorMessage(xhr), 'error'));
    });

    $(document).on('click', '[data-action="toggle-proveedor"]', function () {
        $.ajax({ url: rutas.proveedorEstatus($(this).data('id')), method: 'PATCH', data: { prv_estatus: $(this).data('next') }, dataType: 'json' })
            .done((resp) => { recargarProveedores(); AppUI.showMessage('Éxito', resp.message || 'Estatus actualizado.', 'success'); })
            .fail((xhr) => AppUI.showMessage('Error', parseErrorMessage(xhr), 'error'));
    });

    $(document).on('click', '[data-action="delete-catalogo"]', function () {
        const tipo = $('#catalogo-tipo').val();
        const id = $(this).data('id');
        confirmDelete('¿Deseas eliminar el registro seleccionado?', function () {
            $.ajax({ url: rutas.baseDelete(tipo, id), method: 'DELETE', dataType: 'json' })
                .done((resp) => { recargarCatalogo(); recargarOpcionesDependientes(); AppUI.showMessage('Éxito', resp.message || 'Eliminado correctamente.', 'success'); })
                .fail((xhr) => AppUI.showMessage('Error', parseErrorMessage(xhr), 'error'));
        });
    });

    $(document).on('click', '[data-action="delete-atributo"]', function () {
        const id = $(this).data('id');
        confirmDelete('¿Deseas eliminar el atributo seleccionado?', function () {
            $.ajax({ url: rutas.atributoDelete(id), method: 'DELETE', dataType: 'json' })
                .done((resp) => { recargarAtributos(); recargarOpcionesDependientes(); AppUI.showMessage('Éxito', resp.message || 'Eliminado correctamente.', 'success'); })
                .fail((xhr) => AppUI.showMessage('Error', parseErrorMessage(xhr), 'error'));
        });
    });

    $(document).on('click', '[data-action="delete-valor"]', function () {
        const id = $(this).data('id');
        confirmDelete('¿Deseas eliminar el valor seleccionado?', function () {
            $.ajax({ url: rutas.valorDelete(id), method: 'DELETE', dataType: 'json' })
                .done((resp) => { recargarValores(); recargarOpcionesDependientes(); AppUI.showMessage('Éxito', resp.message || 'Eliminado correctamente.', 'success'); })
                .fail((xhr) => AppUI.showMessage('Error', parseErrorMessage(xhr), 'error'));
        });
    });

    $(document).on('click', '[data-action="delete-producto"]', function () {
        const id = $(this).data('id');
        confirmDelete('¿Deseas eliminar el producto seleccionado?', function () {
            $.ajax({ url: rutas.productoDelete(id), method: 'DELETE', dataType: 'json' })
                .done((resp) => { recargarProductos(); recargarOpcionesDependientes(); AppUI.showMessage('Éxito', resp.message || 'Eliminado correctamente.', 'success'); })
                .fail((xhr) => AppUI.showMessage('Error', parseErrorMessage(xhr), 'error'));
        });
    });

    $(document).on('click', '[data-action="delete-sku"]', function () {
        const id = $(this).data('id');
        confirmDelete('¿Deseas eliminar el SKU seleccionado?', function () {
            $.ajax({ url: rutas.skuDelete(id), method: 'DELETE', dataType: 'json' })
                .done((resp) => { recargarVistaSkus(); recargarOpcionesDependientes(); AppUI.showMessage('Éxito', resp.message || 'Eliminado correctamente.', 'success'); })
                .fail((xhr) => AppUI.showMessage('Error', parseErrorMessage(xhr), 'error'));
        });
    });

    $(document).on('click', '[data-action="delete-proveedor"]', function () {
        const id = $(this).data('id');
        confirmDelete('¿Deseas eliminar el proveedor seleccionado?', function () {
            $.ajax({ url: rutas.proveedorDelete(id), method: 'DELETE', dataType: 'json' })
                .done((resp) => { recargarProveedores(); AppUI.showMessage('Éxito', resp.message || 'Eliminado correctamente.', 'success'); })
                .fail((xhr) => AppUI.showMessage('Error', parseErrorMessage(xhr), 'error'));
        });
    });

    sincronizarUIcatalogo(obtenerTipoCatalogoActual());
    prepararNuevoProducto();
    prepararNuevoProveedor();
    cargarConfiguracionZebraBase();
    $('#etq-config-manual-wrap').addClass('d-none');
    aplicarVistaProductos('cuadricula');
    const vistaActiva = @json($vistaActiva ?? 'base');
    if (vistaActiva === 'base') {
        recargarCatalogo();
    } else if (vistaActiva === 'atributos') {
        recargarAtributos();
        recargarValores();
    } else if (vistaActiva === 'productos') {
        recargarProductos();
    } else if (vistaActiva === 'skus') {
        recargarMatrizSkus();
    } else if (vistaActiva === 'proveedores') {
        recargarProveedores();
    } else if (vistaActiva === 'etiquetado') {
        recargarEtiquetado();
    }
    recargarOpcionesDependientes();
})();
</script>
@endpush
