@extends('layouts.desktop')

@section('title', $pageTitle)

@push('desktop-styles')
    <style>
        .desktop-inventario-placeholder {
            display: grid;
            place-items: center;
            min-height: 360px;
            padding: 24px;
            text-align: center;
        }
        .desktop-inventario-placeholder__title {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
        }
        .desktop-inventario-placeholder__copy {
            margin: 8px 0 0;
            max-width: 460px;
            color: var(--text-2);
            line-height: 1.55;
        }
    </style>
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        @php($activeSubmenu = $activeSubmenu ?? '')
        @include('desktop.operacion.inventario._subnav')
    </div>
@endsection

@section('content')
    <section class="desktop-pane">
        <div class="desktop-inventario-placeholder">
            <div>
                <h1 class="desktop-inventario-placeholder__title">{{ $pageTitle }}</h1>
                <p class="desktop-inventario-placeholder__copy">La experiencia Desktop de este submódulo se integrará aquí en la siguiente etapa, manteniendo la misma línea visual de Inventario.</p>
            </div>
        </div>
    </section>
@endsection
