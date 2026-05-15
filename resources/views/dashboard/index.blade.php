@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<x-section-header
    eyebrow="Panel principal"
    icon="tabler-home-bolt"
    title="Inicio del sistema"
    subtitle="Módulo base activo. Accede a Seguridad y Operación desde el menú superior."
/>

<div class="row g-4">

    {{-- Stats row --}}
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="ls-stat-icon" style="background:rgba(99,91,255,.09);color:#635bff;">
                    <i class="ti tabler-users icon-base"></i>
                </div>
                <div>
                    <div class="ls-stat-label">Usuarios activos</div>
                    <div class="ls-stat-value" id="stat-usuarios">—</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="ls-stat-icon" style="background:rgba(26,158,109,.09);color:#1a9e6d;">
                    <i class="ti tabler-lock-cog icon-base"></i>
                </div>
                <div>
                    <div class="ls-stat-label">Roles configurados</div>
                    <div class="ls-stat-value" id="stat-roles">—</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="ls-stat-icon" style="background:rgba(12,115,199,.09);color:#0c73c7;">
                    <i class="ti tabler-building-warehouse icon-base"></i>
                </div>
                <div>
                    <div class="ls-stat-label">Sucursales activas</div>
                    <div class="ls-stat-value" id="stat-sucursales">—</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="ls-stat-icon" style="background:rgba(233,155,62,.1);color:#c97b1a;">
                    <i class="ti tabler-box-multiple icon-base"></i>
                </div>
                <div>
                    <div class="ls-stat-label">Productos en catálogo</div>
                    <div class="ls-stat-value" id="stat-productos">—</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Feature cards --}}
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="ls-feature-icon" style="background:rgba(99,91,255,.09);color:#635bff;">
                        <i class="ti tabler-shield-check"></i>
                    </span>
                    <span class="fw-700" style="font-size:.9rem;color:var(--ls-text-primary);font-weight:700;">Autenticación segura</span>
                </div>
                <p class="mb-0" style="font-size:.84rem;color:var(--ls-text-muted);line-height:1.55;">
                    Inicio de sesión con usuario + contraseña. Sin dependencia de correo electrónico como credencial de acceso.
                </p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="ls-feature-icon" style="background:rgba(26,158,109,.09);color:#1a9e6d;">
                        <i class="ti tabler-adjustments"></i>
                    </span>
                    <span class="fw-700" style="font-size:.9rem;color:var(--ls-text-primary);font-weight:700;">Seguridad configurable</span>
                </div>
                <p class="mb-0" style="font-size:.84rem;color:var(--ls-text-muted);line-height:1.55;">
                    Roles y permisos por acción de negocio. Sin roles fijos — cada permiso se asigna de forma granular.
                </p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="ls-feature-icon" style="background:rgba(12,115,199,.09);color:#0c73c7;">
                        <i class="ti tabler-activity"></i>
                    </span>
                    <span class="fw-700" style="font-size:.9rem;color:var(--ls-text-primary);font-weight:700;">Trazabilidad de acciones</span>
                </div>
                <p class="mb-0" style="font-size:.84rem;color:var(--ls-text-muted);line-height:1.55;">
                    Bitácoras de accesos y acciones sensibles. Auditoría mínima incluida en cada operación del sistema.
                </p>
            </div>
        </div>
    </div>

    {{-- Quick access --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span style="font-size:.84rem;font-weight:700;color:var(--ls-text-primary);">Accesos rápidos</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @if(auth()->user()?->tienePermiso('usuario.ver'))
                    <div class="col-6 col-md-3">
                        <a href="{{ route('seguridad.usuarios.index') }}" class="ls-quick-link">
                            <i class="ti tabler-users"></i>
                            <span>Usuarios</span>
                        </a>
                    </div>
                    @endif
                    @if(auth()->user()?->tienePermiso('rol.ver'))
                    <div class="col-6 col-md-3">
                        <a href="{{ route('seguridad.roles.index') }}" class="ls-quick-link">
                            <i class="ti tabler-lock-cog"></i>
                            <span>Roles</span>
                        </a>
                    </div>
                    @endif
                    @if(auth()->user()?->tienePermiso('sucursal.ver'))
                    <div class="col-6 col-md-3">
                        <a href="{{ route('operacion.sucursales_almacenes.sucursales.index') }}" class="ls-quick-link">
                            <i class="ti tabler-building-warehouse"></i>
                            <span>Sucursales</span>
                        </a>
                    </div>
                    @endif
                    @if(auth()->user()?->tienePermiso('catalogo_comercial.ver'))
                    <div class="col-6 col-md-3">
                        <a href="{{ route('operacion.catalogo_comercial.base.index') }}" class="ls-quick-link">
                            <i class="ti tabler-box-multiple"></i>
                            <span>Catálogo</span>
                        </a>
                    </div>
                    @endif
                    @if(auth()->user()?->tienePermiso('checklist_entregables.ver'))
                    <div class="col-6 col-md-3">
                        <a href="{{ route('operacion.checklist_entregables.index') }}" class="ls-quick-link">
                            <i class="ti tabler-checklist"></i>
                            <span>Checklist entregables</span>
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>

<style>
    .ls-stat-icon {
        width: 2.6rem;
        height: 2.6rem;
        border-radius: .6rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }
    .ls-stat-label {
        font-size: .74rem;
        font-weight: 600;
        color: var(--ls-text-muted);
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: .1rem;
    }
    .ls-stat-value {
        font-size: 1.55rem;
        font-weight: 700;
        color: var(--ls-text-primary);
        line-height: 1.1;
    }
    .ls-feature-icon {
        width: 2rem;
        height: 2rem;
        border-radius: .45rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }
    .ls-quick-link {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        padding: 1.1rem .75rem;
        border: 1px solid var(--ls-border);
        border-radius: var(--ls-radius-lg);
        color: var(--ls-text-secondary);
        font-size: .8rem;
        font-weight: 600;
        text-decoration: none;
        background: var(--ls-surface);
        transition: all .15s;
    }
    .ls-quick-link i { font-size: 1.35rem; color: var(--ls-accent); }
    .ls-quick-link:hover {
        border-color: var(--ls-accent);
        background: var(--ls-accent-light);
        color: var(--ls-accent);
    }
</style>
@endsection
