@php($templateAssetBase = asset('vendor-template/assets'))
<!doctype html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="layout-navbar-fixed layout-compact"
    dir="ltr"
    data-skin="default"
    data-bs-theme="light"
    data-assets-path="{{ $templateAssetBase }}/"
    data-template="vertical-menu-template"
>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'Inicio') | {{ config('app.name', 'La Suriana Retail') }}</title>

    <link rel="icon" type="image/x-icon" href="{{ $templateAssetBase }}/img/favicon/favicon.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

    <link rel="stylesheet" href="{{ $templateAssetBase }}/vendor/fonts/iconify-icons.css" />
    <link rel="stylesheet" href="{{ $templateAssetBase }}/vendor/libs/node-waves/node-waves.css" />
    <link rel="stylesheet" href="{{ $templateAssetBase }}/vendor/libs/pickr/pickr-themes.css" />
    <link rel="stylesheet" href="{{ $templateAssetBase }}/vendor/css/core.css" />
    <link rel="stylesheet" href="{{ $templateAssetBase }}/css/demo.css" />
    <link rel="stylesheet" href="{{ $templateAssetBase }}/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    @stack('vendor-styles')

    <script src="{{ $templateAssetBase }}/vendor/js/helpers.js"></script>
    <script src="{{ $templateAssetBase }}/js/config.js"></script>
    <style>
        /* ============================================================
           LA SURIANA — DESIGN SYSTEM (Stripe-inspired)
           ============================================================ */

        :root {
            --ls-accent:       #635bff;
            --ls-accent-hover: #4f46e5;
            --ls-accent-light: rgba(99, 91, 255, 0.08);
            --ls-accent-mid:   rgba(99, 91, 255, 0.16);

            --ls-success:      #1a9e6d;
            --ls-success-bg:   rgba(26, 158, 109, 0.09);
            --ls-danger:       #df1b41;
            --ls-danger-bg:    rgba(223, 27, 65, 0.09);
            --ls-warning:      #e99b3e;
            --ls-warning-bg:   rgba(233, 155, 62, 0.1);
            --ls-info:         #0c73c7;
            --ls-info-bg:      rgba(12, 115, 199, 0.09);

            --ls-text-primary:   #0a2540;
            --ls-text-secondary: #425466;
            --ls-text-muted:     #6b7c93;

            --ls-border:       #e6ebf1;
            --ls-border-focus: #635bff;

            --ls-surface:      #ffffff;
            --ls-surface-2:    #f6f9fc;
            --ls-surface-3:    #f0f4f8;

            --ls-radius-sm: 0.375rem;
            --ls-radius:    0.5rem;
            --ls-radius-lg: 0.75rem;
            --ls-radius-xl: 1rem;

            --ls-shadow-sm: 0 1px 3px rgba(10,37,64,.06), 0 1px 2px rgba(10,37,64,.04);
            --ls-shadow:    0 2px 8px rgba(10,37,64,.08), 0 0 1px rgba(10,37,64,.06);
            --ls-shadow-lg: 0 8px 24px rgba(10,37,64,.10), 0 1px 3px rgba(10,37,64,.06);
        }

        /* --- Base body --- */
        body { background: var(--ls-surface-2); color: var(--ls-text-primary); }

        /* Vista general en mayúsculas (excepto módulo de usuarios) */
        body.ls-uppercase-ui .layout-wrapper {
            text-transform: uppercase;
        }

        /* --- Loader --- */
        #global-loader {
            background: rgba(10, 37, 64, 0.28);
            backdrop-filter: blur(3px);
        }

        /* ============================================================
           SECTION HEADER  (page title bar)
           ============================================================ */
        .app-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.25rem;
            margin-bottom: 1.25rem;
            background: var(--ls-surface);
            border: 1px solid var(--ls-border);
            border-radius: var(--ls-radius-lg);
            box-shadow: var(--ls-shadow-sm);
            animation: ls-fade-up 0.22s ease-out;
        }

        .app-section-header__glow { display: none; }

        .app-section-header__content {
            display: contents; /* let parent flex handle alignment */
        }

        .app-section-header__main {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 0;
        }

        .app-section-header__icon {
            flex: 0 0 auto;
            width: 2.25rem;
            height: 2.25rem;
            border-radius: var(--ls-radius);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: var(--ls-accent);
            background: var(--ls-accent-light);
        }

        .app-section-header__eyebrow {
            margin: 0 0 0.1rem;
            font-size: 0.65rem;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            font-weight: 600;
            color: var(--ls-accent);
        }

        .app-section-header__title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            line-height: 1.2;
            color: var(--ls-text-primary);
        }

        .app-section-header__subtitle {
            margin: 0.15rem 0 0;
            font-size: 0.84rem;
            line-height: 1.4;
            color: var(--ls-text-muted);
        }

        .app-section-header__actions {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* compact variant */
        .app-section-header--compact {
            padding: 0.65rem 1rem;
            margin-bottom: 1rem;
        }
        .app-section-header--compact .app-section-header__icon {
            width: 1.8rem;
            height: 1.8rem;
            font-size: 0.9rem;
        }
        .app-section-header--compact .app-section-header__title { font-size: 1.05rem; }
        .app-section-header--compact .app-section-header__subtitle { font-size: 0.79rem; }

        @media (max-width: 767.98px) {
            .app-section-header { flex-direction: column; align-items: flex-start; }
            .app-section-header__actions { width: 100%; }
            .app-section-header__actions > .btn { flex: 1; }
        }

        /* ============================================================
           CARDS  — cleaner, Stripe-style
           ============================================================ */
        .card {
            border: 1px solid var(--ls-border) !important;
            border-radius: var(--ls-radius-lg) !important;
            box-shadow: var(--ls-shadow-sm) !important;
            background: var(--ls-surface) !important;
        }

        .card-header {
            padding: 1rem 1.25rem 0.75rem;
            border-bottom: 1px solid var(--ls-border);
            background: transparent;
        }

        .card-body { padding: 1.25rem; }

        /* ============================================================
           TABS  (app-tabs-shell)
           ============================================================ */
        .app-tabs-shell {
            border: 1px solid var(--ls-border);
            border-radius: var(--ls-radius-lg);
            box-shadow: var(--ls-shadow-sm);
            background: var(--ls-surface);
            overflow: hidden;
        }

        .app-tabs-shell .app-tabs-shell__header {
            padding: 0 1.25rem;
            border-bottom: 1px solid var(--ls-border);
            background: var(--ls-surface);
        }

        .app-tabs-shell .app-tabs-shell__tabs {
            margin-bottom: 0;
            gap: 0;
            border-bottom: none;
        }

        .app-tabs-shell .app-tabs-shell__tabs .nav-link {
            padding: 0.8rem 1rem;
            font-size: 0.84rem;
            font-weight: 600;
            color: var(--ls-text-muted);
            border: none;
            border-bottom: 2px solid transparent;
            border-radius: 0;
            transition: color 0.15s, border-color 0.15s;
        }

        .app-tabs-shell .app-tabs-shell__tabs .nav-link:hover { color: var(--ls-text-primary); }

        .app-tabs-shell .app-tabs-shell__tabs .nav-link.active {
            color: var(--ls-accent);
            border-bottom-color: var(--ls-accent);
            background: transparent;
        }

        .app-tabs-shell .app-tabs-shell__body { padding: 1.25rem; }

        /* ============================================================
           TABLES  — Stripe-like minimal
           ============================================================ */
        .table {
            --bs-table-border-color: var(--ls-border);
            font-size: 0.875rem;
            color: var(--ls-text-primary);
        }

        .table thead th {
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--ls-text-muted);
            padding: 0.65rem 1rem;
            background: var(--ls-surface-2);
            border-bottom: 1px solid var(--ls-border);
            white-space: nowrap;
        }

        .table tbody td {
            padding: 0.75rem 1rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--ls-border);
            color: var(--ls-text-secondary);
        }

        .table tbody tr:last-child td { border-bottom: none; }
        .table tbody tr:hover td { background: var(--ls-surface-2); }

        /* ============================================================
           BADGES  (status pills)
           ============================================================ */
        .ls-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            white-space: nowrap;
        }

        .ls-badge::before {
            content: '';
            width: 0.38rem;
            height: 0.38rem;
            border-radius: 999px;
            background: currentColor;
            opacity: 0.85;
        }

        .ls-badge-success { background: var(--ls-success-bg); color: var(--ls-success); }
        .ls-badge-danger  { background: var(--ls-danger-bg);  color: var(--ls-danger);  }
        .ls-badge-warning { background: var(--ls-warning-bg); color: var(--ls-warning); }
        .ls-badge-info    { background: var(--ls-info-bg);    color: var(--ls-info);    }
        .ls-badge-neutral { background: var(--ls-surface-3);  color: var(--ls-text-muted); }

        /* ============================================================
           TAGS / CHIPS  (roles, sucursales, permisos)
           ============================================================ */
        .ls-tag {
            display: inline-flex;
            align-items: center;
            padding: 0.18rem 0.55rem;
            border-radius: var(--ls-radius-sm);
            font-size: 0.73rem;
            font-weight: 600;
            line-height: 1.4;
            border: 1px solid transparent;
        }

        .ls-tag-role    { background: rgba(99,91,255,.08); color: #4f46e5; border-color: rgba(99,91,255,.2); }
        .ls-tag-branch  { background: rgba(26,158,109,.08); color: #1a9e6d; border-color: rgba(26,158,109,.2); }
        .ls-tag-perm    { background: var(--ls-surface-3); color: var(--ls-text-muted); border-color: var(--ls-border); }
        .ls-tag-more    { background: rgba(233,155,62,.1); color: #c97b1a; border-color: rgba(233,155,62,.25); }

        .ls-tag-list { display: flex; flex-wrap: wrap; gap: 0.3rem; }

        /* ============================================================
           BUTTONS  — Stripe pill style
           ============================================================ */
        .btn {
            font-weight: 600;
            font-size: 0.84rem;
            border-radius: var(--ls-radius);
            transition: all 0.15s;
        }

        .btn-primary {
            background: var(--ls-accent);
            border-color: var(--ls-accent);
            color: #fff;
        }
        .btn-primary:hover, .btn-primary:focus {
            background: var(--ls-accent-hover);
            border-color: var(--ls-accent-hover);
            color: #fff;
        }

        .btn-sm { font-size: 0.78rem; padding: 0.3rem 0.7rem; }

        /* ghost / outline */
        .btn-outline-secondary {
            border-color: var(--ls-border);
            color: var(--ls-text-secondary);
            background: var(--ls-surface);
        }
        .btn-outline-secondary:hover {
            background: var(--ls-surface-2);
            border-color: #c8d3de;
            color: var(--ls-text-primary);
        }

        .btn-ghost-action {
            background: transparent;
            border: 1px solid var(--ls-border);
            color: var(--ls-text-muted);
            padding: 0.28rem 0.55rem;
            border-radius: var(--ls-radius-sm);
            line-height: 1;
        }
        .btn-ghost-action:hover {
            background: var(--ls-surface-2);
            color: var(--ls-text-primary);
            border-color: #c8d3de;
        }
        .btn-ghost-action.danger:hover {
            background: var(--ls-danger-bg);
            color: var(--ls-danger);
            border-color: rgba(223,27,65,.25);
        }
        .btn-ghost-action.success:hover {
            background: var(--ls-success-bg);
            color: var(--ls-success);
            border-color: rgba(26,158,109,.25);
        }

        /* ============================================================
           FORMS  — Stripe-like inputs
           ============================================================ */
        .form-control, .form-select {
            font-size: 0.875rem;
            color: var(--ls-text-primary);
            border: 1px solid var(--ls-border);
            border-radius: var(--ls-radius);
            background: var(--ls-surface);
            box-shadow: 0 1px 2px rgba(10,37,64,.04);
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--ls-accent);
            box-shadow: 0 0 0 3px rgba(99,91,255,.12);
            outline: none;
        }

        .form-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--ls-text-secondary);
            margin-bottom: 0.35rem;
        }

        /* ============================================================
           MODALS  — refined
           ============================================================ */
        .modal-content {
            border: 1px solid var(--ls-border) !important;
            border-radius: var(--ls-radius-xl) !important;
            box-shadow: var(--ls-shadow-lg) !important;
        }

        .modal-header {
            padding: 1.1rem 1.4rem 0.9rem;
            border-bottom: 1px solid var(--ls-border);
        }

        .modal-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--ls-text-primary);
        }

        .modal-body { padding: 1.25rem 1.4rem; }

        .modal-footer {
            padding: 1.25rem 1.4rem;
            border-top: 1px solid var(--ls-border);
            background: var(--ls-surface-2);
            border-radius: 0 0 var(--ls-radius-xl) var(--ls-radius-xl);
            gap: 0.5rem;
            align-items: center;
            flex-wrap: nowrap;
        }
        /* Bootstrap añade margin-top negativo a los hijos del footer — lo anulamos */
        .modal-footer > * {
            margin-top: 0 !important;
        }

        /* ============================================================
           GLOBAL MESSAGE MODAL
           ============================================================ */
        #globalMessageModal .modal-content {
            border: 0 !important;
            border-radius: var(--ls-radius-xl) !important;
            overflow: hidden;
            box-shadow: var(--ls-shadow-lg) !important;
        }

        #globalMessageModal.fade .modal-dialog {
            transform: translateY(12px) scale(0.99);
            opacity: 0;
            transition: transform 0.22s cubic-bezier(0.2, 0.9, 0.25, 1), opacity 0.2s ease;
        }

        #globalMessageModal.show .modal-dialog {
            transform: translateY(0) scale(1);
            opacity: 1;
        }

        .app-message-shell {
            border-top: 3px solid transparent;
            padding: 1.1rem 1.3rem 1rem;
            background: var(--ls-surface);
            animation: ls-fade-up 0.22s ease-out;
        }

        .app-message-shell.is-success { border-top-color: var(--ls-success); }
        .app-message-shell.is-error   { border-top-color: var(--ls-danger); }
        .app-message-shell.is-warning { border-top-color: var(--ls-warning); }
        .app-message-shell.is-info    { border-top-color: var(--ls-info); }

        .app-message-head {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 0.55rem;
            padding-right: 2rem;
        }

        .app-message-icon {
            width: 2rem;
            height: 2rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .app-message-shell.is-success .app-message-icon { background: var(--ls-success-bg); color: var(--ls-success); }
        .app-message-shell.is-error   .app-message-icon { background: var(--ls-danger-bg);  color: var(--ls-danger);  }
        .app-message-shell.is-warning .app-message-icon { background: var(--ls-warning-bg); color: var(--ls-warning); }
        .app-message-shell.is-info    .app-message-icon { background: var(--ls-info-bg);    color: var(--ls-info);    }

        .app-message-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: var(--ls-text-primary);
        }

        .app-message-body {
            font-size: 0.9rem;
            line-height: 1.55;
            color: var(--ls-text-secondary);
            margin-bottom: 0.9rem;
            white-space: pre-line;
        }

        .app-message-actions { display: flex; justify-content: flex-end; }
        .app-message-actions .btn { min-width: 6.5rem; font-weight: 600; }

        .app-message-close {
            position: absolute;
            top: 0.6rem;
            right: 0.6rem;
            z-index: 2;
            transform: none !important;
            padding: 0.4rem !important;
            border-radius: var(--ls-radius-sm);
            background-color: transparent !important;
            box-shadow: none !important;
            opacity: 0.6;
        }
        .app-message-close:hover { background-color: var(--ls-surface-3) !important; opacity: 1; }

        /* ============================================================
           EMPTY STATE
           ============================================================ */
        .ls-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 1.5rem;
            text-align: center;
        }

        .ls-empty__icon {
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 999px;
            background: var(--ls-surface-3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--ls-text-muted);
            margin-bottom: 1rem;
        }

        .ls-empty__title {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--ls-text-primary);
            margin: 0 0 0.3rem;
        }

        .ls-empty__sub {
            font-size: 0.84rem;
            color: var(--ls-text-muted);
            margin: 0;
        }

        /* ============================================================
           KEYFRAMES
           ============================================================ */
        @keyframes ls-fade-up {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @media (prefers-reduced-motion: reduce) {
            .app-section-header, .app-message-shell,
            #globalMessageModal.fade .modal-dialog { animation: none; transition: none; }
        }

        @media (max-width: 575.98px) {
            .app-message-actions { justify-content: stretch; }
            .app-message-actions .btn { width: 100%; }
        }
    </style>
</head>
<body class="{{ request()->routeIs('seguridad.usuarios.*') ? '' : 'ls-uppercase-ui' }}">
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">

            <!-- ── Sidebar vertical ─────────────────────────────────────────── -->
            <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">

                <!-- Brand -->
                <div class="app-brand demo">
                    <a href="{{ route('dashboard') }}" class="app-brand-link">
                        <span class="app-brand-logo demo text-primary">
                            <i class="icon-base ti tabler-building-store icon-lg"></i>
                        </span>
                        <span class="app-brand-text demo menu-text fw-bold text-heading ms-2">La I. Suriana</span>
                    </a>
                    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-xl-none">
                        <i class="icon-base ti tabler-x icon-sm d-flex align-items-center justify-content-center"></i>
                    </a>
                </div>

                <div class="menu-inner-shadow"></div>

                <ul class="menu-inner py-1">

                    <li class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <a href="{{ route('dashboard') }}" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-layout-dashboard"></i>
                            <div>Dashboard</div>
                        </a>
                    </li>

                    @if(auth()->user()?->tienePermiso('usuario.ver') || auth()->user()?->tienePermiso('rol.ver') || auth()->user()?->tienePermiso('permiso.ver') || auth()->user()?->tienePermiso('seguridad.ver_auditoria'))
                        <li class="menu-item {{ request()->routeIs('seguridad.usuarios.*') || request()->routeIs('seguridad.roles.*') || request()->routeIs('seguridad.permisos.*') || request()->routeIs('seguridad.bitacora.*') ? 'active' : '' }}">
                            <a href="{{ route('seguridad.usuarios.index') }}" class="menu-link">
                                <i class="menu-icon icon-base ti tabler-users"></i>
                                <div>Usuarios</div>
                            </a>
                        </li>
                    @endif

                    @if(auth()->user()?->tienePermiso('sucursal.ver') && auth()->user()?->tienePermiso('almacen.ver') && auth()->user()?->tienePermiso('tipo_almacen.ver'))
                        <li class="menu-item {{ request()->routeIs('operacion.sucursales_almacenes.*') ? 'active' : '' }}">
                            <a href="{{ route('operacion.sucursales_almacenes.index') }}" class="menu-link">
                                <i class="menu-icon icon-base ti tabler-building-warehouse"></i>
                                <div>Sucursales y almacenes</div>
                            </a>
                        </li>
                    @endif

                    @if(auth()->user()?->tienePermiso('catalogo_comercial.ver'))
                        <li class="menu-item {{ request()->routeIs('operacion.catalogo_comercial.*') ? 'active' : '' }}">
                            <a href="{{ route('operacion.catalogo_comercial.index') }}" class="menu-link">
                                <i class="menu-icon icon-base ti tabler-box-multiple"></i>
                                <div>Catálogo comercial</div>
                            </a>
                        </li>
                    @endif

                    @if(auth()->user()?->tienePermiso('escaneo_producto.ver'))
                        <li class="menu-item {{ request()->routeIs('operacion.escaneo_productos.*') ? 'active' : '' }}">
                            <a href="{{ route('operacion.escaneo_productos.index') }}" class="menu-link">
                                <i class="menu-icon icon-base ti tabler-scan"></i>
                                <div>Escaneo productos</div>
                            </a>
                        </li>
                    @endif

                    @if(auth()->user()?->tienePermiso('inventario_base.ver'))
                        <li class="menu-item {{ request()->routeIs('operacion.inventario_base.*') ? 'active' : '' }}">
                            <a href="{{ route('operacion.inventario_base.index') }}" class="menu-link">
                                <i class="menu-icon icon-base ti tabler-packages"></i>
                                <div>Inventario</div>
                            </a>
                        </li>
                    @endif

                    @if(auth()->user()?->tienePermiso('checklist_entregables.ver'))
                        <li class="menu-item {{ request()->routeIs('operacion.checklist_entregables.*') ? 'active' : '' }}">
                            <a href="{{ route('operacion.checklist_entregables.index') }}" class="menu-link">
                                <i class="menu-icon icon-base ti tabler-checklist"></i>
                                <div>Checklist entregables</div>
                            </a>
                        </li>
                    @endif

                </ul>
            </aside>
            <!-- / Sidebar vertical -->

            <!-- Layout page -->
            <div class="layout-page">

                <!-- Navbar superior (solo usuario + toggle mobile) -->
                <nav class="layout-navbar navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
                    <div class="container-xxl">

                        <!-- Mobile toggle -->
                        <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
                            <a class="nav-item nav-link px-0" href="javascript:void(0)">
                                <i class="icon-base ti tabler-menu-2 icon-md"></i>
                            </a>
                        </div>

                        <!-- Spacer -->
                        <div class="navbar-nav-right d-flex align-items-center justify-content-end w-100" id="navbar-collapse">
                            <div class="d-flex align-items-center gap-3">
                                <div class="text-end">
                                    <div class="fw-semibold" style="font-size:.88rem;">{{ auth()->user()?->usr_nombre }}</div>
                                    <small class="text-body-secondary">{{ auth()->user()?->usr_usuario }}</small>
                                </div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1">
                                        <i class="icon-base ti tabler-logout"></i>
                                        <span class="d-none d-md-inline">Salir</span>
                                    </button>
                                </form>
                            </div>
                        </div>

                    </div>
                </nav>
                <!-- / Navbar superior -->

                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        @yield('content')
                    </div>

                    <footer class="content-footer footer bg-footer-theme">
                        <div class="container-xxl">
                            <div class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
                                <div class="text-body">
                                    {{ now()->year }} &mdash; La I. Suriana Retail
                                </div>
                                <div class="d-none d-lg-inline-block text-body-secondary" style="font-size:.8rem;">
                                    Laravel + Bootstrap 5 + AJAX + DataTables
                                </div>
                            </div>
                        </div>
                    </footer>

                    <div class="content-backdrop fade"></div>
                </div>
                <!-- / Content wrapper -->

            </div>
            <!-- / Layout page -->

        </div>
        <div class="layout-overlay layout-menu-toggle"></div>
        <div class="drag-target"></div>
    </div>

    <div id="global-loader" class="position-fixed top-0 start-0 w-100 h-100 d-none align-items-center justify-content-center" style="z-index: 1080;">
        <div class="text-center text-white">
            <div class="spinner-border mb-2" role="status" aria-hidden="true"></div>
            <p class="mb-0 fw-semibold">Procesando...</p>
        </div>
    </div>

    <div class="modal fade" id="globalMessageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <button type="button" class="btn-close app-message-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                <div class="app-message-shell is-info" id="globalMessageContent">
                    <div class="app-message-head">
                        <span class="app-message-icon">
                            <i class="icon-base ti tabler-info-circle" id="globalMessageIcon" aria-hidden="true"></i>
                        </span>
                        <h5 class="app-message-title" id="globalMessageTitle">Mensaje</h5>
                    </div>
                    <div class="app-message-body" id="globalMessageBody"></div>
                    <div class="app-message-actions">
                        <button type="button" class="btn btn-primary" id="globalMessageButton" data-bs-dismiss="modal">Aceptar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ $templateAssetBase }}/vendor/libs/jquery/jquery.js"></script>
    <script src="{{ $templateAssetBase }}/vendor/libs/popper/popper.js"></script>
    <script src="{{ $templateAssetBase }}/vendor/js/bootstrap.js"></script>
    <script src="{{ $templateAssetBase }}/vendor/libs/node-waves/node-waves.js"></script>
    <script src="{{ $templateAssetBase }}/vendor/libs/pickr/pickr.js"></script>
    <script src="{{ $templateAssetBase }}/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="{{ $templateAssetBase }}/vendor/libs/hammer/hammer.js"></script>
    <script src="{{ $templateAssetBase }}/vendor/js/menu.js"></script>
    @stack('vendor-scripts')
    <script src="{{ $templateAssetBase }}/js/main.js"></script>
    <script>
        window.AppUI = {
            showLoader() {
                document.getElementById('global-loader').classList.remove('d-none');
                document.getElementById('global-loader').classList.add('d-flex');
            },
            hideLoader() {
                document.getElementById('global-loader').classList.add('d-none');
                document.getElementById('global-loader').classList.remove('d-flex');
            },
            showMessage(title, body, type = null) {
                const normalizedTitle = (title || '')
                    .toString()
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '');
                const normalizedType = (type || '').toString().toLowerCase();

                const resolvedType = normalizedType || (
                    normalizedTitle.includes('error') || normalizedTitle.includes('denegado')
                        ? 'error'
                        : normalizedTitle.includes('validacion') || normalizedTitle.includes('warning') || normalizedTitle.includes('advertencia')
                            ? 'warning'
                            : normalizedTitle.includes('exito') || normalizedTitle.includes('correcto')
                                ? 'success'
                                : 'info'
                );

                const variants = {
                    success: { shell: 'is-success', icon: 'tabler-circle-check', button: 'btn-success' },
                    error: { shell: 'is-error', icon: 'tabler-alert-circle', button: 'btn-danger' },
                    warning: { shell: 'is-warning', icon: 'tabler-alert-triangle', button: 'btn-warning' },
                    info: { shell: 'is-info', icon: 'tabler-info-circle', button: 'btn-primary' }
                };

                const variant = variants[resolvedType] || variants.info;
                const content = document.getElementById('globalMessageContent');
                const button = document.getElementById('globalMessageButton');

                content.classList.remove('is-success', 'is-error', 'is-warning', 'is-info');
                content.classList.add(variant.shell);
                button.classList.remove('btn-primary', 'btn-success', 'btn-danger', 'btn-warning');
                button.classList.add(variant.button);

                document.getElementById('globalMessageIcon').className = 'icon-base ti ' + variant.icon;
                document.getElementById('globalMessageTitle').textContent = title || 'Mensaje';
                document.getElementById('globalMessageBody').textContent = body || '';
                const modal = new bootstrap.Modal(document.getElementById('globalMessageModal'));
                modal.show();
            }
        };

        $.ajaxSetup({
            cache: false,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
    </script>
    @stack('page-scripts')
</body>
</html>
