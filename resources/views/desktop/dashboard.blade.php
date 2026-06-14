@extends('layouts.desktop')

@section('title', 'Inicio')

@push('desktop-styles')
    <style>
        .dash { display: flex; flex-direction: column; gap: 12px; }
        .dash__hero {
            display: flex; align-items: center; justify-content: space-between; gap: 20px;
            padding: 16px 18px;
            background: linear-gradient(120deg, #0f6cbd 0%, #1452a3 100%);
            border-radius: var(--r-md);
            color: #fff;
            box-shadow: var(--shadow-2);
        }
        .dash__hero h1 { margin: 0; font-size: 1.15rem; font-weight: 600; letter-spacing: -.01em; }
        .dash__hero p { margin: 4px 0 0; font-size: .84rem; opacity: .85; max-width: 560px; line-height: 1.45; }
        .dash__hero .desktop-btn { background: rgba(255,255,255,.16); color: #fff; border-color: rgba(255,255,255,.25); }
        .dash__hero .desktop-btn:hover { background: rgba(255,255,255,.26); }

        .dash__section-title { font-size: .88rem; font-weight: 600; margin: 2px 2px; }

        .kpi-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
        .kpi {
            display: flex; flex-direction: column; gap: 8px;
            padding: 13px 14px;
            background: var(--surface);
            border: 1px solid var(--stroke);
            border-radius: var(--r-md);
            box-shadow: var(--shadow-2);
        }
        .kpi__top { display: flex; align-items: center; justify-content: space-between; }
        .kpi__icon {
            width: 32px; height: 32px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: var(--r-md); color: var(--brand); background: var(--brand-soft);
        }
        .kpi__icon svg { width: 18px; height: 18px; }
        .kpi__label { font-size: .76rem; color: var(--text-2); font-weight: 500; }
        .kpi__value { font-size: 1.3rem; font-weight: 600; letter-spacing: -.02em; line-height: 1; }
        .kpi__delta { font-size: .74rem; color: var(--text-3); }

        .panel-grid { display: grid; grid-template-columns: 1.4fr 1fr; gap: 12px; }
        .panel {
            background: var(--surface);
            border: 1px solid var(--stroke);
            border-radius: var(--r-lg);
            box-shadow: var(--shadow-2);
            overflow: hidden;
        }
        .panel__head {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 18px; border-bottom: 1px solid var(--divider);
        }
        .panel__head h3 { margin: 0; font-size: .9rem; font-weight: 600; }
        .panel__body { padding: 8px; }

        .quick { display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px; }
        .quick a {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 12px; border-radius: var(--r-md);
            transition: background .12s ease;
        }
        .quick a:hover { background: var(--surface-sunken); }
        .quick__icon {
            width: 36px; height: 36px; flex: none;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: var(--r-md); color: var(--brand); background: var(--brand-soft);
        }
        .quick__icon svg { width: 18px; height: 18px; }
        .quick__txt strong { display: block; font-size: .84rem; font-weight: 600; }
        .quick__txt span { font-size: .75rem; color: var(--text-2); }

        .feed-item { display: flex; gap: 12px; padding: 11px 12px; border-radius: var(--r-md); }
        .feed-item:hover { background: var(--surface-sunken); }
        .feed-dot { width: 8px; height: 8px; margin-top: 6px; flex: none; border-radius: 50%; background: var(--brand); }
        .feed-item p { margin: 0; font-size: .82rem; }
        .feed-item span { font-size: .72rem; color: var(--text-3); }

        @media (max-width: 1100px) { .kpi-grid { grid-template-columns: repeat(2, 1fr); } .panel-grid { grid-template-columns: 1fr; } }
        @media (max-width: 560px) { .kpi-grid { grid-template-columns: 1fr; } .quick { grid-template-columns: 1fr; } }
    </style>
@endpush

@section('desktop-toolbar')
    <div class="page-head">
        <span class="page-head__title">Inicio</span>
        <span class="page-head__sub">Resumen general del sistema</span>
    </div>
    <div class="desktop-toolbar__group">
        <button type="button" class="desktop-btn desktop-btn--ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
            Actualizar
        </button>
        <a href="{{ route('desktop.usuarios') }}" class="desktop-btn desktop-btn--primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5v14"/></svg>
            Ir a usuarios
        </a>
    </div>
@endsection

@section('content')
    <div class="dash">
        <div class="dash__hero">
            <div>
                <h1>Bienvenido a La Suriana Retail</h1>
                <p>Administra usuarios, roles y la operación de tu negocio desde un solo lugar. Selecciona un módulo para comenzar.</p>
            </div>
            <a href="{{ route('desktop.usuarios') }}" class="desktop-btn">Administrar seguridad</a>
        </div>

        <div>
            <div class="dash__section-title">Accesos rápidos</div>
            <div class="kpi-grid">
                <div class="kpi">
                    <div class="kpi__top">
                        <span class="kpi__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></span>
                    </div>
                    <div>
                        <div class="kpi__label">Usuarios</div>
                        <div class="kpi__value">Gestión</div>
                    </div>
                    <div class="kpi__delta">Altas, edición y estatus de cuentas</div>
                </div>
                <div class="kpi">
                    <div class="kpi__top">
                        <span class="kpi__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
                    </div>
                    <div>
                        <div class="kpi__label">Roles y permisos</div>
                        <div class="kpi__value">Control</div>
                    </div>
                    <div class="kpi__delta">Define accesos por módulo</div>
                </div>
                <div class="kpi">
                    <div class="kpi__top">
                        <span class="kpi__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg></span>
                    </div>
                    <div>
                        <div class="kpi__label">Inventario</div>
                        <div class="kpi__value">Próximamente</div>
                    </div>
                    <div class="kpi__delta">Existencias y movimientos</div>
                </div>
                <div class="kpi">
                    <div class="kpi__top">
                        <span class="kpi__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m7 14 3-3 3 3 5-6"/></svg></span>
                    </div>
                    <div>
                        <div class="kpi__label">Reportes</div>
                        <div class="kpi__value">Próximamente</div>
                    </div>
                    <div class="kpi__delta">Indicadores del negocio</div>
                </div>
            </div>
        </div>

        <div class="panel-grid">
            <div class="panel">
                <div class="panel__head"><h3>Tareas frecuentes</h3></div>
                <div class="panel__body">
                    <div class="quick">
                        <a href="{{ route('desktop.usuarios') }}">
                            <span class="quick__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg></span>
                            <span class="quick__txt"><strong>Nuevo usuario</strong><span>Crear una cuenta de acceso</span></span>
                        </a>
                        <a href="{{ route('desktop.roles') }}">
                            <span class="quick__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg></span>
                            <span class="quick__txt"><strong>Gestionar roles</strong><span>Permisos por módulo</span></span>
                        </a>
                        <a href="{{ route('desktop.usuarios') }}">
                            <span class="quick__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg></span>
                            <span class="quick__txt"><strong>Buscar usuario</strong><span>Consultar cuentas existentes</span></span>
                        </a>
                        <a href="{{ route('desktop.dashboard') }}">
                            <span class="quick__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.2 4.2l2.9 2.9M16.9 16.9l2.9 2.9M1 12h4M19 12h4M4.2 19.8l2.9-2.9M16.9 7.1l2.9-2.9"/></svg></span>
                            <span class="quick__txt"><strong>Preferencias</strong><span>Configuración del sistema</span></span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel__head"><h3>Comenzar</h3></div>
                <div class="panel__body">
                    <div class="feed-item"><span class="feed-dot"></span><div><p>Revisa los usuarios con acceso al sistema</p><span>Módulo de seguridad</span></div></div>
                    <div class="feed-item"><span class="feed-dot" style="background:#107c41"></span><div><p>Configura los roles y sus permisos</p><span>Roles y permisos</span></div></div>
                    <div class="feed-item"><span class="feed-dot" style="background:#8a5a00"></span><div><p>Los módulos de operación llegarán pronto</p><span>Inventario · Catálogo · Ventas</span></div></div>
                </div>
            </div>
        </div>
    </div>
@endsection
