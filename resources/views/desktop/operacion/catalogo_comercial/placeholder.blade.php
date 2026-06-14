@extends('layouts.desktop')

@section('title', $pageTitle)

@push('desktop-styles')
    <style>
        .desktop-catalogo-placeholder {
            display: grid;
            place-items: center;
            min-height: 360px;
            padding: 24px;
            text-align: center;
        }
        .desktop-catalogo-placeholder__title {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
        }
        .desktop-catalogo-placeholder__copy {
            margin: 8px 0 0;
            max-width: 420px;
            color: var(--text-2);
            line-height: 1.55;
        }
    </style>
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        @php($activeSubmenu = $activeSubmenu ?? '')
        @include('desktop.operacion.catalogo_comercial._subnav')
    </div>
@endsection

@section('content')
    <section class="desktop-pane">
        <div class="desktop-catalogo-placeholder">
            <div>
                <h1 class="desktop-catalogo-placeholder__title">{{ $pageTitle }}</h1>
                <p class="desktop-catalogo-placeholder__copy">{{ $pageDescription }}</p>
            </div>
        </div>
    </section>
@endsection
