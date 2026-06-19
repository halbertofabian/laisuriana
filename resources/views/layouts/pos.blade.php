@php($templateAssetBase = asset('vendor-template/assets'))
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'Punto de Venta') | {{ config('app.name', 'La Suriana Retail') }}</title>

    <link rel="icon" type="image/x-icon" href="{{ $templateAssetBase }}/img/favicon/favicon.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet" />

    <link rel="stylesheet" href="{{ $templateAssetBase }}/vendor/fonts/iconify-icons.css" />

    @stack('pos-styles')

    <style>
        /* ============================================================
           LA SURIANA — DESIGN SYSTEM TOKENS
           ============================================================ */
        :root {
            --ls-accent:        #635bff;
            --ls-accent-hover:  #4f46e5;
            --ls-accent-light:  rgba(99, 91, 255, 0.08);
            --ls-accent-mid:    rgba(99, 91, 255, 0.15);

            --ls-success:       #1a9e6d;
            --ls-success-hover: #148056;
            --ls-success-bg:    rgba(26, 158, 109, 0.09);
            --ls-success-mid:   rgba(26, 158, 109, 0.18);

            --ls-danger:        #df1b41;
            --ls-danger-hover:  #b8162f;
            --ls-danger-bg:     rgba(223, 27, 65, 0.08);
            --ls-danger-mid:    rgba(223, 27, 65, 0.18);

            --ls-warning:       #d97706;
            --ls-warning-bg:    rgba(217, 119, 6, 0.09);
            --ls-warning-mid:   rgba(217, 119, 6, 0.18);

            --ls-text-primary:   #0a2540;
            --ls-text-secondary: #425466;
            --ls-text-muted:     #6b7c93;

            --ls-border:        #e3e8ef;
            --ls-border-strong: #c8d0db;

            --ls-surface:       #ffffff;
            --ls-surface-2:     #f6f9fc;
            --ls-surface-3:     #eef2f7;

            --ls-radius-sm:  0.3rem;
            --ls-radius:     0.5rem;
            --ls-radius-lg:  0.75rem;
            --ls-radius-xl:  1rem;

            --ls-shadow-sm:  0 1px 3px rgba(10,37,64,.07), 0 1px 2px rgba(10,37,64,.04);
            --ls-shadow:     0 2px 8px rgba(10,37,64,.09), 0 0 1px rgba(10,37,64,.05);
            --ls-shadow-lg:  0 8px 24px rgba(10,37,64,.12), 0 1px 3px rgba(10,37,64,.06);

            /* POS layout */
            --pos-header-h:  54px;
            --pos-tabs-h:    42px;
            --pos-bottom-h:  60px;
            --pos-panel-w:   290px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            height: 100%;
            overflow: hidden;
            font-family: 'Public Sans', ui-sans-serif, system-ui, sans-serif;
            font-size: 14px;
            color: var(--ls-text-primary);
            background: var(--ls-surface-2);
            -webkit-font-smoothing: antialiased;
        }

        /* ============================================================
           POS SHELL
           ============================================================ */
        .pos-shell {
            display: grid;
            grid-template-rows: var(--pos-header-h) var(--pos-tabs-h) 1fr var(--pos-bottom-h);
            height: 100vh;
            width: 100vw;
            overflow: hidden;
        }

        /* ── HEADER ─────────────────────────────────────────────── */
        .pos-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.1rem;
            background: var(--ls-surface);
            border-bottom: 1px solid var(--ls-border);
            gap: 1rem;
            box-shadow: 0 1px 0 var(--ls-border), var(--ls-shadow-sm);
            z-index: 20;
        }

        .pos-header__brand {
            display: flex;
            align-items: center;
            gap: 0.7rem;
        }

        .pos-header__logo {
            width: 34px;
            height: 34px;
            border-radius: var(--ls-radius);
            background: linear-gradient(135deg, var(--ls-success) 0%, #0d8a5e 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1rem;
            box-shadow: 0 2px 6px rgba(26,158,109,.35);
            flex-shrink: 0;
        }

        .pos-header__name {
            font-size: 0.92rem;
            font-weight: 800;
            color: var(--ls-text-primary);
            line-height: 1.2;
            letter-spacing: -0.01em;
        }

        .pos-header__sub {
            font-size: 0.7rem;
            color: var(--ls-text-muted);
            font-weight: 500;
            margin-top: 1px;
        }

        .pos-header__sub strong { color: var(--ls-text-secondary); font-weight: 600; }

        .pos-header__right {
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }

        .pos-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.28rem 0.7rem 0.28rem 0.55rem;
            border-radius: 999px;
            border: 1px solid var(--ls-border);
            background: var(--ls-surface-2);
            font-size: 0.73rem;
            font-weight: 500;
            color: var(--ls-text-secondary);
            cursor: default;
            user-select: none;
        }

        .pos-pill__dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .pos-pill__dot--on   { background: var(--ls-success); box-shadow: 0 0 0 2px var(--ls-success-bg); }
        .pos-pill__dot--idle { background: var(--ls-text-muted); }
        .pos-pill__dot--warn { background: var(--ls-warning); box-shadow: 0 0 0 2px var(--ls-warning-bg); }

        .pos-pill__cat {
            font-size: 0.63rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--ls-text-muted);
        }

        .pos-divider { width: 1px; height: 22px; background: var(--ls-border); flex-shrink: 0; }

        /* ── TABS ───────────────────────────────────────────────── */
        .pos-tabs {
            display: flex;
            align-items: stretch;
            padding: 0 1.1rem;
            background: var(--ls-surface);
            border-bottom: 1px solid var(--ls-border);
            gap: 0;
        }

        .pos-tab {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0 0.9rem;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--ls-text-muted);
            border: none;
            border-bottom: 2.5px solid transparent;
            background: transparent;
            cursor: pointer;
            transition: color 0.12s, border-color 0.12s;
            white-space: nowrap;
            letter-spacing: 0.01em;
        }

        .pos-tab:hover { color: var(--ls-text-primary); }

        .pos-tab.active {
            color: var(--ls-success);
            border-bottom-color: var(--ls-success);
        }

        .pos-tab .kbd {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 17px;
            min-width: 24px;
            padding: 0 4px;
            border-radius: 4px;
            background: var(--ls-surface-3);
            border: 1px solid var(--ls-border-strong);
            border-bottom-width: 2px;
            font-size: 0.6rem;
            font-weight: 700;
            font-family: monospace;
            color: var(--ls-text-muted);
            box-shadow: 0 1px 0 var(--ls-border-strong);
        }

        .pos-tab.active .kbd {
            background: var(--ls-success-bg);
            border-color: var(--ls-success-mid);
            border-bottom-color: var(--ls-success);
            color: var(--ls-success);
        }

        /* ── MAIN AREA ──────────────────────────────────────────── */
        .pos-main {
            display: grid;
            grid-template-columns: 1fr var(--pos-panel-w);
            overflow: hidden;
            background: var(--ls-surface-2);
        }

        /* ── LEFT COLUMN ─────────────────────────────────────────── */
        .pos-left {
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border-right: 1px solid var(--ls-border);
            background: var(--ls-surface);
        }

        /* Pending bar */
        .pos-pending-bar {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            padding: 0 1.1rem 0 1.1rem;
            min-height: 38px;
            gap: 0.75rem;
            border-bottom: 1px solid var(--ls-border);
            background: var(--ls-surface-2);
        }

        .pos-pending-bar__msg-wrap {
            display: flex;
            align-items: center;
            flex: 1;
            min-width: 0;
            padding-bottom: 0.5rem;
        }

        /* Tab-style "Nueva venta" button — sits flush at the bottom of the bar
           and visually connects to the white card below */
        .pos-nueva-venta-tab {
            display: inline-flex;
            align-items: center;
            gap: 0.38rem;
            padding: 0.3rem 0.85rem;
            font-size: 0.77rem;
            font-weight: 700;
            font-family: inherit;
            color: var(--ls-success);
            background: var(--ls-surface);
            border: 1px solid var(--ls-border);
            border-bottom: none;
            border-radius: var(--ls-radius) var(--ls-radius) 0 0;
            cursor: pointer;
            transition: background 0.13s, color 0.13s;
            letter-spacing: 0.01em;
            box-shadow: 0 -2px 6px rgba(10,37,64,.05);
            position: relative;
            bottom: -1px;
        }

        .pos-nueva-venta-tab:hover {
            background: var(--ls-success-bg);
            color: var(--ls-success-hover);
        }

        .pos-nueva-venta-tab i { font-size: 0.82rem; }

        .pos-pending-bar--has-items {
            background: linear-gradient(90deg, rgba(26,158,109,.04) 0%, transparent 100%);
        }

        .pos-pending-bar__msg {
            font-size: 0.75rem;
            color: var(--ls-text-muted);
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .pos-pending-bar__msg kbd {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 15px;
            min-width: 22px;
            padding: 0 3px;
            border-radius: 3px;
            background: var(--ls-surface);
            border: 1px solid var(--ls-border-strong);
            border-bottom-width: 2px;
            font-size: 0.6rem;
            font-weight: 700;
            font-family: monospace;
            color: var(--ls-text-secondary);
        }

        /* Input zone */
        .pos-input-zone {
            display: grid;
            grid-template-columns: 3fr 1.5fr 2fr;
            gap: 0.65rem;
            padding: 0.7rem 1.1rem;
            background: var(--ls-surface);
            border-bottom: 1px solid var(--ls-border);
        }

        @media (max-width: 920px) {
            .pos-input-zone { grid-template-columns: 1fr; }
        }

        .pos-field__label {
            display: block;
            font-size: 0.64rem;
            font-weight: 700;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: var(--ls-text-muted);
            margin-bottom: 0.28rem;
        }

        .pos-input-wrap {
            position: relative;
        }

        .pos-input-wrap .input-icon {
            position: absolute;
            left: 0.6rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--ls-text-muted);
            font-size: 1rem;
            pointer-events: none;
            display: flex;
            align-items: center;
        }

        .pos-input {
            width: 100%;
            height: 40px;
            padding: 0 0.7rem 0 2.1rem;
            border: 1.5px solid var(--ls-border);
            border-radius: var(--ls-radius);
            font-size: 0.84rem;
            font-family: inherit;
            color: var(--ls-text-primary);
            background: var(--ls-surface);
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .pos-input:focus {
            border-color: var(--ls-success);
            box-shadow: 0 0 0 3px rgba(26, 158, 109, 0.13);
        }

        .pos-input--producto:focus {
            border-color: var(--ls-accent);
            box-shadow: 0 0 0 3px var(--ls-accent-light);
        }

        .pos-input--producto:focus ~ .input-icon,
        .pos-input-wrap:focus-within .input-icon { color: var(--ls-accent); }

        .pos-input--producto:focus { border-color: var(--ls-accent); }

        .pos-input::placeholder { color: var(--ls-text-muted); font-size: 0.8rem; }

        /* Scan bar */
        .pos-scan-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.3rem 1.1rem;
            background: var(--ls-surface-2);
            border-bottom: 1px solid var(--ls-border);
            gap: 0.75rem;
        }

        .pos-scan-bar__hint {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.73rem;
            color: var(--ls-text-muted);
        }

        /* Ticket table */
        .pos-ticket-wrap {
            flex: 1;
            overflow-y: auto;
        }

        .pos-ticket-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
        }

        .pos-ticket-table thead {
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .pos-ticket-table thead th {
            padding: 0.42rem 0.8rem;
            font-size: 0.64rem;
            font-weight: 700;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: var(--ls-text-muted);
            background: var(--ls-surface-2);
            border-bottom: 1.5px solid var(--ls-border);
            white-space: nowrap;
        }

        .pos-ticket-table thead th:not(:first-child) { text-align: right; }
        .pos-ticket-table thead th:last-child { text-align: center; width: 38px; }

        .pos-ticket-table tbody tr {
            border-bottom: 1px solid var(--ls-border);
            transition: background 0.1s;
        }

        .pos-ticket-table tbody tr:hover { background: var(--ls-surface-2); }

        .pos-ticket-table tbody td {
            padding: 0.55rem 0.8rem;
            vertical-align: middle;
        }

        .pos-ticket-table tbody td:not(:first-child):not(:last-child) { text-align: right; }
        .pos-ticket-table tbody td:last-child { text-align: center; }

        .pos-ticket__empty {
            padding: 3rem 1rem;
            text-align: center;
            color: var(--ls-text-muted);
        }

        .pos-ticket__empty-icon {
            font-size: 2rem;
            display: block;
            margin-bottom: 0.5rem;
            color: var(--ls-border-strong);
        }

        .pos-ticket__empty-text {
            font-size: 0.82rem;
            color: var(--ls-text-muted);
        }

        .pos-ticket__desc {
            font-weight: 600;
            color: var(--ls-text-primary);
            font-size: 0.83rem;
        }

        .pos-ticket__sku {
            font-size: 0.69rem;
            color: var(--ls-text-muted);
            margin-top: 2px;
            font-family: monospace;
            letter-spacing: 0.02em;
        }

        .pos-ticket__price {
            color: var(--ls-text-secondary);
            font-weight: 500;
        }

        .pos-ticket__importe {
            font-weight: 700;
            color: var(--ls-text-primary);
        }

        .pos-ticket__qty-wrap {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.3rem;
        }

        .pos-ticket__qty-btn {
            width: 22px; height: 22px;
            border: 1px solid var(--ls-border);
            background: var(--ls-surface-2);
            border-radius: var(--ls-radius-sm);
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.7rem;
            color: var(--ls-text-muted);
            transition: all 0.1s;
            padding: 0;
        }

        .pos-ticket__qty-btn:hover {
            background: var(--ls-surface-3);
            border-color: var(--ls-border-strong);
            color: var(--ls-text-primary);
        }

        .pos-ticket__qty-input {
            width: 48px;
            height: 26px;
            text-align: center;
            border: 1px solid var(--ls-border);
            border-radius: var(--ls-radius-sm);
            font-size: 0.82rem;
            font-family: inherit;
            font-weight: 600;
            color: var(--ls-text-primary);
            background: var(--ls-surface);
            outline: none;
            transition: border-color 0.12s, box-shadow 0.12s;
        }

        .pos-ticket__qty-input:focus {
            border-color: var(--ls-success);
            box-shadow: 0 0 0 2px rgba(26,158,109,.12);
        }

        .pos-ticket__remove {
            background: none;
            border: none;
            color: var(--ls-text-muted);
            cursor: pointer;
            width: 26px; height: 26px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: var(--ls-radius-sm);
            font-size: 0.8rem;
            transition: color 0.1s, background 0.1s;
        }

        .pos-ticket__remove:hover {
            color: var(--ls-danger);
            background: var(--ls-danger-bg);
        }

        .pos-ticket__desc-badge {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 3px;
            background: var(--ls-success-bg);
            color: var(--ls-success);
            font-size: 0.65rem;
            font-weight: 700;
            vertical-align: middle;
            margin-left: 4px;
        }

        /* User footer */
        .pos-user-footer {
            padding: 0.3rem 1.1rem;
            background: var(--ls-surface-2);
            border-top: 1px solid var(--ls-border);
            font-size: 0.7rem;
            color: var(--ls-text-muted);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .pos-user-footer strong { color: var(--ls-text-secondary); font-weight: 600; }

        /* ── RIGHT PANEL ──────────────────────────────────────────── */
        .pos-panel {
            display: flex;
            flex-direction: column;
            background: var(--ls-surface);
            overflow-y: auto;
        }

        .pos-panel::-webkit-scrollbar { width: 4px; }
        .pos-panel::-webkit-scrollbar-track { background: transparent; }
        .pos-panel::-webkit-scrollbar-thumb { background: var(--ls-border); border-radius: 999px; }

        .pos-panel__block {
            padding: 0.7rem 0.85rem;
            border-bottom: 1px solid var(--ls-border);
        }

        .pos-panel__row {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 0.5rem;
            margin-bottom: 0.35rem;
        }

        .pos-panel__row:last-child { margin-bottom: 0; }

        .pos-panel__lbl {
            font-size: 0.67rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--ls-text-muted);
        }

        .pos-panel__val {
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--ls-text-primary);
        }

        .pos-panel__val--muted {
            color: var(--ls-text-muted);
            font-weight: 400;
        }

        .pos-panel__val--danger {
            color: var(--ls-danger);
        }

        /* Total big block */
        .pos-total-block {
            padding: 1rem 0.85rem 0.95rem;
            border-bottom: 1px solid var(--ls-border);
            background: linear-gradient(135deg, rgba(26,158,109,.04) 0%, transparent 80%);
            text-align: center;
        }

        .pos-total-block__lbl {
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--ls-success);
            margin-bottom: 0.3rem;
        }

        .pos-total-block__amount {
            font-size: 2.95rem;
            font-weight: 800;
            color: var(--ls-success);
            line-height: .95;
            letter-spacing: -0.05em;
        }

        .pos-total-block__amount span { font-size: 1.35rem; font-weight: 700; vertical-align: top; margin-top: 0.35rem; display: inline-block; }

        .pos-discount-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            background: var(--ls-accent-light);
            color: var(--ls-accent);
            font-size: 0.7rem;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid var(--ls-accent-mid);
            transition: background 0.12s;
            margin-top: 0.35rem;
        }

        .pos-discount-btn:hover { background: rgba(99,91,255,.14); }

        .pos-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 22px;
            height: 22px;
            padding: 0 6px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
        }

        .pos-badge--neutral { background: var(--ls-surface-3); color: var(--ls-text-muted); }
        .pos-badge--success { background: var(--ls-success-bg); color: var(--ls-success); }

        .pos-notes-textarea {
            width: 100%;
            min-height: 64px;
            padding: 0.5rem 0.6rem;
            border: 1.5px solid var(--ls-border);
            border-radius: var(--ls-radius);
            font-size: 0.78rem;
            font-family: inherit;
            color: var(--ls-text-primary);
            background: var(--ls-surface);
            resize: none;
            outline: none;
            transition: border-color 0.15s;
        }

        .pos-notes-textarea:focus { border-color: var(--ls-border-focus, var(--ls-accent)); }
        .pos-notes-textarea::placeholder { color: var(--ls-text-muted); font-size: 0.76rem; }

        /* ── BOTTOM BAR ───────────────────────────────────────────── */
        .pos-bottom {
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            padding: 0 0.85rem;
            background: var(--ls-surface);
            border-top: 1.5px solid var(--ls-border);
            gap: 0.5rem;
            box-shadow: 0 -2px 10px rgba(10,37,64,.06);
            z-index: 20;
        }

        .pos-bottom__group {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        .pos-bottom__center {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
        }

        /* ── BUTTONS ─────────────────────────────────────────────── */
        .pos-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            padding: 0 0.8rem;
            height: 36px;
            border-radius: var(--ls-radius);
            font-size: 0.76rem;
            font-weight: 600;
            font-family: inherit;
            border: 1.5px solid transparent;
            cursor: pointer;
            transition: all 0.13s;
            white-space: nowrap;
            outline: none;
            letter-spacing: 0.01em;
        }

        .pos-btn:active { transform: scale(0.97); }

        .pos-btn--sm {
            height: 28px;
            padding: 0 0.65rem;
            font-size: 0.74rem;
        }

        .pos-btn--success {
            background: var(--ls-success);
            color: #fff;
            border-color: var(--ls-success);
            box-shadow: 0 1px 3px rgba(26,158,109,.3);
        }

        .pos-btn--success:hover {
            background: var(--ls-success-hover);
            border-color: var(--ls-success-hover);
        }

        .pos-btn--success-outline {
            background: transparent;
            color: var(--ls-success);
            border-color: var(--ls-success-mid);
        }

        .pos-btn--success-outline:hover {
            background: var(--ls-success-bg);
            border-color: var(--ls-success);
        }

        .pos-btn--danger {
            background: var(--ls-danger);
            color: #fff;
            border-color: var(--ls-danger);
        }

        .pos-btn--danger:hover {
            background: var(--ls-danger-hover);
            border-color: var(--ls-danger-hover);
        }

        .pos-btn--danger-outline {
            background: transparent;
            color: var(--ls-danger);
            border-color: var(--ls-danger-mid);
        }

        .pos-btn--danger-outline:hover {
            background: var(--ls-danger-bg);
            border-color: var(--ls-danger);
        }

        .pos-btn--warning-outline {
            background: transparent;
            color: var(--ls-warning);
            border-color: var(--ls-warning-mid);
        }

        .pos-btn--warning-outline:hover {
            background: var(--ls-warning-bg);
            border-color: var(--ls-warning);
        }

        .pos-btn--ghost {
            background: transparent;
            color: var(--ls-text-secondary);
            border-color: var(--ls-border);
        }

        .pos-btn--ghost:hover {
            background: var(--ls-surface-2);
            border-color: var(--ls-border-strong);
        }

        .pos-btn--cobrar {
            height: 46px;
            padding: 0 1.75rem;
            font-size: 0.92rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            background: linear-gradient(135deg, var(--ls-success) 0%, #0d8a5e 100%);
            color: #fff;
            border-color: var(--ls-success);
            border-radius: var(--ls-radius);
            box-shadow: 0 3px 10px rgba(26, 158, 109, 0.35);
        }

        .pos-btn--cobrar:hover {
            background: linear-gradient(135deg, var(--ls-success-hover) 0%, #0a7050 100%);
            box-shadow: 0 5px 15px rgba(26, 158, 109, 0.45);
            transform: translateY(-1px);
        }

        .pos-btn--cobrar:disabled {
            opacity: 0.45;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .pos-btn .kbd {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 16px;
            min-width: 22px;
            padding: 0 3px;
            border-radius: 3px;
            background: rgba(255,255,255,0.18);
            border: 1px solid rgba(255,255,255,0.3);
            font-size: 0.58rem;
            font-weight: 700;
            font-family: monospace;
        }

        .pos-btn--ghost .kbd,
        .pos-btn--danger-outline .kbd,
        .pos-btn--warning-outline .kbd,
        .pos-btn--success-outline .kbd {
            background: var(--ls-surface-3);
            border-color: var(--ls-border-strong);
            color: var(--ls-text-muted);
        }

        /* ── TICKET SCROLLBAR ──────────────────────────────────── */
        .pos-ticket-wrap::-webkit-scrollbar { width: 5px; }
        .pos-ticket-wrap::-webkit-scrollbar-track { background: transparent; }
        .pos-ticket-wrap::-webkit-scrollbar-thumb { background: var(--ls-border); border-radius: 999px; }
        .pos-ticket-wrap::-webkit-scrollbar-thumb:hover { background: var(--ls-border-strong); }

        /* ── ANIMATIONS ─────────────────────────────────────────── */
        @keyframes slideInRow {
            from { opacity: 0; transform: translateX(-6px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        .pos-item-row { animation: slideInRow 0.18s ease-out; }

        /* ── FOCUS TRAP ──────────────────────────────────────────── */
        [data-focus-trap] { outline: none; }
    </style>
</head>
<body>
    <div id="pos-root">
        @yield('content')
    </div>

    <script src="{{ $templateAssetBase }}/vendor/libs/jquery/jquery.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @stack('pos-scripts')
</body>
</html>
