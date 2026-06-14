@extends('layouts.desktop')

@section('title', $pageTitle)

@push('desktop-styles')
    <style>
        .desktop-ops-placeholder {
            display: grid;
            place-items: center;
            min-height: 360px;
            padding: 28px;
            text-align: center;
        }
        .desktop-ops-placeholder__card {
            width: min(560px, 100%);
            padding: 28px;
            border: 1px solid var(--stroke);
            border-radius: var(--r-xl);
            background: linear-gradient(180deg, var(--surface) 0%, var(--surface-alt) 100%);
            box-shadow: var(--shadow-2);
        }
        .desktop-ops-placeholder__icon {
            width: 52px;
            height: 52px;
            margin: 0 auto 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            color: var(--brand);
            background: var(--brand-soft);
        }
        .desktop-ops-placeholder__icon svg { width: 24px; height: 24px; }
        .desktop-ops-placeholder__title { margin: 0; font-size: 1.08rem; font-weight: 600; }
        .desktop-ops-placeholder__copy { margin: 10px 0 0; color: var(--text-2); line-height: 1.6; }
    </style>
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        <div class="page-head">
            <span class="page-head__title">{{ $pageTitle }}</span>
            <span class="page-head__sub">Gestión y configuraciones de operación</span>
        </div>
        <span class="desktop-toolbar__divider"></span>
        @php($activeSubmenu = $activeSubmenu ?? '')
        @include('desktop.operacion.gestion_configuraciones._subnav')
    </div>
@endsection

@section('content')
    <section class="desktop-pane">
        <div class="desktop-ops-placeholder">
            <div class="desktop-ops-placeholder__card">
                <div class="desktop-ops-placeholder__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="M8 11 12 15l4-4"/><path d="M4 21h16"/></svg>
                </div>
                <h1 class="desktop-ops-placeholder__title">{{ $pageTitle }}</h1>
                <p class="desktop-ops-placeholder__copy">{{ $pageDescription }}</p>
            </div>
        </div>
    </section>
@endsection
