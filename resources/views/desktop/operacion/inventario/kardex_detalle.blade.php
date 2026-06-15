@extends('layouts.desktop')

@section('title', 'Kardex completo')

@php
    $backParams = array_filter([
        'min_scl_id' => $detalle['back_filters']['min_scl_id'] ?? null,
        'min_alm_id' => $detalle['back_filters']['min_alm_id'] ?? null,
        'prd_mrc_id' => $detalle['back_filters']['prd_mrc_id'] ?? null,
        'prd_mdl_id' => $detalle['back_filters']['prd_mdl_id'] ?? null,
        'prd_lna_id' => $detalle['back_filters']['prd_lna_id'] ?? null,
        'prd_ctg_id' => $detalle['back_filters']['prd_ctg_id'] ?? null,
        'prd_id' => $detalle['back_filters']['prd_id'] ?? null,
        'prd_text' => $detalle['back_filters']['prd_text'] ?? null,
        'buscar' => $detalle['back_filters']['buscar'] ?? null,
    ], fn ($value) => $value !== null && $value !== '');
    $backHidden = [
        'back_min_scl_id' => $detalle['back_filters']['min_scl_id'] ?? null,
        'back_min_alm_id' => $detalle['back_filters']['min_alm_id'] ?? null,
        'back_prd_mrc_id' => $detalle['back_filters']['prd_mrc_id'] ?? null,
        'back_prd_mdl_id' => $detalle['back_filters']['prd_mdl_id'] ?? null,
        'back_prd_lna_id' => $detalle['back_filters']['prd_lna_id'] ?? null,
        'back_prd_ctg_id' => $detalle['back_filters']['prd_ctg_id'] ?? null,
        'back_prd_id' => $detalle['back_filters']['prd_id'] ?? null,
        'back_prd_text' => $detalle['back_filters']['prd_text'] ?? null,
        'back_buscar' => $detalle['back_filters']['buscar'] ?? null,
    ];
    $backHidden = array_filter($backHidden, fn ($value) => $value !== null && $value !== '');

    $existencia = (float) $detalle['existencia_actual'];
    $existEstado = $existencia > 0 ? 'pos' : ($existencia < 0 ? 'neg' : 'zero');

    $periodoActual = $detalle['periodo'];
    $periodos = [
        'hoy' => 'Hoy',
        'ayer' => 'Ayer',
        'esta_semana' => 'Semana',
        'este_mes' => 'Mes',
        'ultimos_3_meses' => '3 meses',
        'ultimos_6_meses' => '6 meses',
        'este_anio' => 'Año',
    ];
    $periodoLabels = [
        'hoy' => 'Hoy', 'ayer' => 'Ayer', 'esta_semana' => 'Esta semana', 'este_mes' => 'Este mes',
        'ultimos_3_meses' => 'Últimos 3 meses', 'ultimos_6_meses' => 'Últimos 6 meses',
        'este_anio' => 'Este año', 'ultimos_3_anios' => 'Últimos 3 años', 'rango' => 'Rango personalizado',
    ];
    $consultaLabel = $periodoActual === 'rango'
        ? (($detalle['fecha_inicio'] ?? '') . ' a ' . ($detalle['fecha_fin'] ?? ''))
        : ($periodoLabels[$periodoActual] ?? 'Este mes');

    $tl = collect($detalle['timeline']);
    $totalEntradas = round((float) $tl->sum('entradas'), 2);
    $totalSalidas = round((float) $tl->sum('salidas'), 2);
    $ajustesCount = (int) $tl->sum(fn ($m) => collect($m['movimientos'])->filter(fn ($x) => $x->tmi_clase === 'ajuste')->count());
    $totalMovs = (int) ($detalle['movimientos_total'] ?? $tl->sum('total_movimientos'));
    $unidad = $detalle['unidad'];

    $claseLabels = ['entrada' => 'Entrada', 'salida' => 'Salida', 'ajuste' => 'Ajuste', 'traspaso' => 'Traspaso'];
@endphp

@push('desktop-styles')
    <style>
        /* ===== Breadcrumb ===== */
        .desktop-kdx-bread {
            display: flex; align-items: center; gap: 6px;
            padding: 8px 12px;
            font-size: .8rem; color: var(--text-3);
            border-bottom: 1px solid var(--stroke);
            background: var(--surface);
            flex-wrap: wrap;
        }
        .desktop-kdx-bread a { color: var(--text-2); text-decoration: none; font-weight: 600; }
        .desktop-kdx-bread a:hover { color: var(--brand); }
        .desktop-kdx-bread__sep { color: var(--text-3); }
        .desktop-kdx-bread__current { color: var(--text); font-weight: 600; }
        .desktop-kdx-bread__time { margin-left: auto; color: var(--text-3); font-weight: 400; }

        /* ===== Ficha resumida + existencia destacada ===== */
        .desktop-kdx-ficha {
            display: flex; align-items: center; justify-content: space-between;
            gap: 16px; flex-wrap: wrap;
            padding: 12px;
            border-bottom: 1px solid var(--stroke);
            background: var(--surface-alt);
        }
        .desktop-kdx-ficha__main { min-width: 240px; }
        .desktop-kdx-ficha__title { margin: 0; font-size: 1.05rem; font-weight: 700; letter-spacing: -.01em; }
        .desktop-kdx-ficha__sku { margin-top: 2px; font-size: .8rem; color: var(--text-2); font-variant-numeric: tabular-nums; }
        .desktop-kdx-ficha__chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
        .desktop-kdx-tag {
            display: inline-flex; align-items: center; gap: 5px;
            min-height: 22px; padding: 0 9px;
            border: 1px solid var(--stroke); border-radius: 999px;
            background: var(--surface); font-size: .73rem; color: var(--text-2);
        }
        .desktop-kdx-tag b { color: var(--text); font-weight: 600; }

        .desktop-kdx-exist {
            display: flex; flex-direction: column; align-items: flex-end;
            padding: 6px 16px;
            border-radius: var(--r-md);
            border: 1px solid var(--stroke);
            background: var(--surface);
            border-left: 4px solid var(--exist, var(--text-3));
            min-width: 150px;
        }
        .desktop-kdx-exist__label { font-size: .68rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--text-3); }
        .desktop-kdx-exist__value { font-size: 1.7rem; font-weight: 800; line-height: 1.1; color: var(--exist, var(--text)); font-variant-numeric: tabular-nums; }
        .desktop-kdx-exist__unit { font-size: .82rem; font-weight: 600; color: var(--text-2); }
        .desktop-kdx-exist--pos  { --exist: var(--success); }
        .desktop-kdx-exist--zero { --exist: #b57c00; }
        .desktop-kdx-exist--neg  { --exist: var(--danger); }

        /* ===== Filtros rápidos de periodo ===== */
        .desktop-kdx-periodbar {
            display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
            padding: 8px 12px;
            border-bottom: 1px solid var(--stroke);
            background: var(--surface);
        }
        .desktop-kdx-periodbar__label { font-size: .68rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: var(--text-3); }
        .desktop-kdx-periods { display: inline-flex; flex-wrap: wrap; gap: 4px; }
        .desktop-kdx-pchip {
            height: 28px; padding: 0 12px;
            border: 1px solid var(--stroke-strong); border-radius: 999px;
            background: var(--surface); color: var(--text-2);
            font: inherit; font-size: .78rem; font-weight: 600;
            cursor: pointer; white-space: nowrap;
            transition: background .12s ease, color .12s ease, border-color .12s ease;
        }
        .desktop-kdx-pchip:hover { background: var(--surface-sunken); color: var(--text); }
        .desktop-kdx-pchip.is-active { background: var(--brand); border-color: var(--brand); color: var(--on-brand); }
        .desktop-kdx-range { display: none; align-items: center; gap: 6px; }
        .desktop-kdx-range.is-open { display: inline-flex; }
        .desktop-kdx-range input[type="date"] {
            height: 28px; padding: 0 8px;
            border: 1px solid var(--stroke-strong); border-radius: var(--r-md);
            font: inherit; font-size: .78rem; color: var(--text); background: var(--surface);
        }
        .desktop-kdx-err { margin-left: 8px; color: var(--danger); font-size: .76rem; }

        /* ===== Resumen rápido (métricas ERP compactas) ===== */
        .desktop-kdx-metrics {
            display: flex; gap: 18px; flex-wrap: wrap;
            padding: 8px 12px;
            border-bottom: 1px solid var(--stroke);
            background: var(--surface-alt);
        }
        .desktop-kdx-metric { display: flex; flex-direction: column; line-height: 1.15; }
        .desktop-kdx-metric__value { font-size: 1.05rem; font-weight: 800; font-variant-numeric: tabular-nums; color: var(--text); }
        .desktop-kdx-metric__label { font-size: .68rem; font-weight: 600; letter-spacing: .02em; text-transform: uppercase; color: var(--text-3); }
        .desktop-kdx-metric--in .desktop-kdx-metric__value { color: var(--success); }
        .desktop-kdx-metric--out .desktop-kdx-metric__value { color: var(--danger); }
        .desktop-kdx-metric--adj .desktop-kdx-metric__value { color: var(--brand); }
        .desktop-kdx-metric--bal-pos .desktop-kdx-metric__value { color: var(--success); }
        .desktop-kdx-metric--bal-zero .desktop-kdx-metric__value { color: #b57c00; }
        .desktop-kdx-metric--bal-neg .desktop-kdx-metric__value { color: var(--danger); }

        /* ===== Búsqueda interna ===== */
        .desktop-kdx-tools {
            display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
            padding: 8px 12px;
            border-bottom: 1px solid var(--stroke);
            background: var(--surface);
        }
        .desktop-kdx-tools__search { position: relative; flex: 1 1 280px; max-width: 420px; display: flex; align-items: center; }
        .desktop-kdx-tools__search svg { position: absolute; left: 9px; width: 15px; height: 15px; color: var(--text-3); pointer-events: none; }
        .desktop-kdx-tools__search input {
            width: 100%; height: 30px; padding: 0 10px 0 30px;
            border: 1px solid var(--stroke-strong); border-radius: var(--r-md);
            background: var(--surface); color: var(--text); font: inherit; font-size: .82rem;
        }
        .desktop-kdx-tools__search input:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 1px var(--brand); }
        .desktop-kdx-tools select {
            height: 30px; padding: 0 30px 0 10px;
            border: 1px solid var(--stroke-strong); border-radius: var(--r-md);
            background: var(--surface); color: var(--text); font: inherit; font-size: .8rem;
            appearance: none; cursor: pointer;
            background-image: linear-gradient(45deg, transparent 50%, var(--text-3) 50%), linear-gradient(135deg, var(--text-3) 50%, transparent 50%);
            background-position: calc(100% - 15px) 13px, calc(100% - 10px) 13px; background-size: 5px 5px; background-repeat: no-repeat;
        }

        /* ===== Timeline ===== */
        .desktop-kdx-timeline { flex: 1 1 auto; min-height: 0; padding: 0 0 12px; overflow: auto; }
        .desktop-kdx-monthgroup { border-bottom: 1px solid var(--divider); }
        .desktop-kdx-monthhead {
            position: sticky; top: 0; z-index: 2;
            display: flex; align-items: baseline; gap: 12px; flex-wrap: wrap;
            padding: 8px 12px;
            background: var(--surface-sunken);
            border-bottom: 1px solid var(--divider);
        }
        .desktop-kdx-monthhead__title { margin: 0; font-size: .9rem; font-weight: 700; }
        .desktop-kdx-monthhead__stats { display: inline-flex; gap: 12px; font-size: .74rem; color: var(--text-2); }
        .desktop-kdx-monthhead__stats b { font-variant-numeric: tabular-nums; }
        .desktop-kdx-monthhead .pos { color: var(--success); }
        .desktop-kdx-monthhead .neg { color: var(--danger); }

        .desktop-kdx-events { list-style: none; margin: 0; padding: 4px 12px 8px 12px; }
        .desktop-kdx-event {
            position: relative;
            display: flex; gap: 12px;
            padding: 6px 0 6px 0;
        }
        .desktop-kdx-event__marker {
            position: relative; flex: 0 0 auto; width: 10px; margin-top: 5px;
        }
        .desktop-kdx-event__marker::before {
            content: ""; position: absolute; left: 3px; top: 9px; bottom: -16px; width: 2px;
            background: var(--divider);
        }
        .desktop-kdx-event:last-child .desktop-kdx-event__marker::before { display: none; }
        .desktop-kdx-event__marker::after {
            content: ""; position: absolute; left: 0; top: 1px;
            width: 8px; height: 8px; border-radius: 50%;
            background: var(--evt, var(--text-3));
            box-shadow: 0 0 0 3px var(--evt-soft, rgba(0,0,0,.05));
        }
        .desktop-kdx-event__card { flex: 1 1 auto; min-width: 0; }
        .desktop-kdx-event__top { display: flex; align-items: baseline; gap: 10px; flex-wrap: wrap; }
        .desktop-kdx-event__type {
            font-size: .82rem; font-weight: 700; color: var(--evt, var(--text));
        }
        .desktop-kdx-event__qty { font-size: .86rem; font-weight: 800; font-variant-numeric: tabular-nums; color: var(--evt, var(--text)); }
        .desktop-kdx-saldo {
            display: inline-flex; align-items: baseline; gap: 4px;
            padding: 1px 8px; border-radius: 999px;
            border: 1px solid var(--stroke);
            font-size: .74rem; font-weight: 700; font-variant-numeric: tabular-nums;
            background: var(--surface);
        }
        .desktop-kdx-saldo span { font-size: .64rem; font-weight: 600; color: var(--text-3); text-transform: uppercase; letter-spacing: .02em; }
        .desktop-kdx-saldo--pos { color: var(--success); border-color: rgba(17,121,80,.3); }
        .desktop-kdx-saldo--zero { color: #8a5a00; border-color: rgba(181,124,0,.3); }
        .desktop-kdx-saldo--neg { color: var(--danger); border-color: rgba(193,42,46,.3); }
        .desktop-kdx-event__when { margin-left: auto; font-size: .74rem; color: var(--text-3); white-space: nowrap; font-variant-numeric: tabular-nums; }
        .desktop-kdx-event__detail { display: flex; flex-wrap: wrap; gap: 4px 16px; margin-top: 2px; font-size: .76rem; color: var(--text-2); }
        .desktop-kdx-event__detail b { color: var(--text-3); font-weight: 600; }
        .desktop-kdx-event__detail .ref { font-variant-numeric: tabular-nums; }

        /* Colores por clase de movimiento */
        .desktop-kdx-event--entrada  { --evt: var(--success); --evt-soft: rgba(17,121,80,.14); }
        .desktop-kdx-event--salida   { --evt: var(--danger);  --evt-soft: rgba(193,42,46,.14); }
        .desktop-kdx-event--ajuste   { --evt: var(--brand);   --evt-soft: rgba(47,111,237,.14); }
        .desktop-kdx-event--traspaso { --evt: #7c3aed;        --evt-soft: rgba(124,58,237,.14); }

        .desktop-kdx-empty { padding: 48px 16px; text-align: center; color: var(--text-2); font-size: .82rem; }
        .desktop-kdx-empty[hidden] { display: none; }
        .desktop-kdx-monthgroup[hidden], .desktop-kdx-event[hidden] { display: none; }

        @media (max-width: 760px) {
            .desktop-kdx-event__when { margin-left: 0; width: 100%; }
        }
    </style>
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        @php $activeSubmenu = 'existencias'; @endphp
        @include('desktop.operacion.inventario._subnav')
    </div>
    <div class="desktop-toolbar__group">
        <a href="{{ route('desktop.operacion.inventario.existencias.index', $backParams) }}" class="desktop-btn desktop-btn--default">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Volver a existencias
        </a>
    </div>
@endsection

@section('content')
    <section class="desktop-pane">
        {{-- Breadcrumb --}}
        <nav class="desktop-kdx-bread" aria-label="Ruta">
            <a href="{{ route('desktop.operacion.inventario.existencias.index') }}">Inventario</a>
            <span class="desktop-kdx-bread__sep">/</span>
            <a href="{{ route('desktop.operacion.inventario.existencias.index', $backParams) }}">Existencias</a>
            <span class="desktop-kdx-bread__sep">/</span>
            <span class="desktop-kdx-bread__current">Kardex</span>
            <span class="desktop-kdx-bread__time">Consulta: {{ optional($detalle['fecha_consulta'])->format('d/m/Y H:i') }}</span>
        </nav>

        {{-- Ficha resumida + existencia destacada --}}
        <div class="desktop-kdx-ficha">
            <div class="desktop-kdx-ficha__main">
                <h2 class="desktop-kdx-ficha__title">{{ $detalle['producto']?->prd_nombre ?: 'Producto' }}</h2>
                <div class="desktop-kdx-ficha__sku">SKU: {{ $detalle['sku']?->psk_codigo ?: 'N/D' }}</div>
                <div class="desktop-kdx-ficha__chips">
                    <span class="desktop-kdx-tag"><b>{{ $detalle['color'] ?: ($detalle['sku']?->psk_nombre ?: 'Base') }}</b> · {{ $detalle['talla'] }}</span>
                    <span class="desktop-kdx-tag">Consulta: <b>{{ $consultaLabel }}</b></span>
                    <span class="desktop-kdx-tag">Ámbito: <b>Todos los almacenes activos</b></span>
                </div>
            </div>
            <div class="desktop-kdx-exist desktop-kdx-exist--{{ $existEstado }}">
                <span class="desktop-kdx-exist__label">Existencia actual</span>
                <span class="desktop-kdx-exist__value">{{ number_format($existencia, 2) }} <span class="desktop-kdx-exist__unit">{{ $unidad }}</span></span>
            </div>
        </div>

        {{-- Filtros rápidos de periodo (auto-aplican) --}}
        <form method="GET" class="desktop-kdx-periodbar" id="kd-periodform">
            @foreach($backHidden as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <span class="desktop-kdx-periodbar__label">Periodo</span>
            <div class="desktop-kdx-periods">
                @foreach($periodos as $value => $label)
                    <button type="submit" name="periodo" value="{{ $value }}"
                            class="desktop-kdx-pchip {{ $periodoActual === $value ? 'is-active' : '' }}">{{ $label }}</button>
                @endforeach
                <button type="button" id="kd-custom-toggle"
                        class="desktop-kdx-pchip {{ $periodoActual === 'rango' ? 'is-active' : '' }}">Personalizado</button>
            </div>
            <div class="desktop-kdx-range {{ $periodoActual === 'rango' ? 'is-open' : '' }}" id="kd-range">
                <input type="date" name="fecha_inicio" id="kd-fi" value="{{ request('fecha_inicio', $periodoActual === 'rango' ? $detalle['fecha_inicio'] : '') }}">
                <span style="color:var(--text-3);">→</span>
                <input type="date" name="fecha_fin" id="kd-ff" value="{{ request('fecha_fin', $periodoActual === 'rango' ? $detalle['fecha_fin'] : '') }}">
                <input type="hidden" name="periodo" id="kd-range-flag" value="rango" disabled>
            </div>
            @if ($errors->any())
                <span class="desktop-kdx-err">{{ $errors->first() }}</span>
            @endif
        </form>

        {{-- Resumen rápido (métricas compactas tipo ERP) --}}
        <div class="desktop-kdx-metrics">
            <div class="desktop-kdx-metric desktop-kdx-metric--in">
                <span class="desktop-kdx-metric__value">+{{ number_format($totalEntradas, 2) }}</span>
                <span class="desktop-kdx-metric__label">Entradas</span>
            </div>
            <div class="desktop-kdx-metric desktop-kdx-metric--out">
                <span class="desktop-kdx-metric__value">-{{ number_format($totalSalidas, 2) }}</span>
                <span class="desktop-kdx-metric__label">Salidas</span>
            </div>
            <div class="desktop-kdx-metric desktop-kdx-metric--adj">
                <span class="desktop-kdx-metric__value">{{ $ajustesCount }}</span>
                <span class="desktop-kdx-metric__label">Ajustes</span>
            </div>
            <div class="desktop-kdx-metric">
                <span class="desktop-kdx-metric__value">{{ $totalMovs }}</span>
                <span class="desktop-kdx-metric__label">Movimientos</span>
            </div>
            <div class="desktop-kdx-metric desktop-kdx-metric--bal-{{ $existEstado }}">
                <span class="desktop-kdx-metric__value">{{ number_format($existencia, 2) }}</span>
                <span class="desktop-kdx-metric__label">Saldo actual</span>
            </div>
        </div>

        {{-- Búsqueda interna --}}
        <div class="desktop-kdx-tools">
            <div class="desktop-kdx-tools__search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="search" id="kd-search" placeholder="Buscar por referencia, usuario, observación...">
            </div>
            <select id="kd-type">
                <option value="">Todos los tipos</option>
                <option value="entrada">Entradas</option>
                <option value="salida">Salidas</option>
                <option value="ajuste">Ajustes</option>
                <option value="traspaso">Traspasos</option>
            </select>
        </div>

        <div class="desktop-kdx-timeline" id="kd-timeline">
            @if($tl->isEmpty())
                <div class="desktop-kdx-empty">No se encontraron movimientos para el periodo seleccionado.</div>
            @else
                <div class="desktop-kdx-empty" id="kd-noresults" hidden>Ningún movimiento coincide con la búsqueda.</div>
                @foreach($tl as $mes)
                    @php $neto = round((float) $mes['entradas'] - (float) $mes['salidas'], 2); @endphp
                    <section class="desktop-kdx-monthgroup" data-month>
                        <div class="desktop-kdx-monthhead">
                            <h3 class="desktop-kdx-monthhead__title">{{ $mes['mes_label'] }}</h3>
                            <span class="desktop-kdx-monthhead__stats">
                                <span>Entradas: <b class="pos">+{{ number_format((float) $mes['entradas'], 2) }}</b></span>
                                <span>Salidas: <b class="neg">-{{ number_format((float) $mes['salidas'], 2) }}</b></span>
                                <span>Saldo neto: <b class="{{ $neto >= 0 ? 'pos' : 'neg' }}">{{ $neto >= 0 ? '+' : '' }}{{ number_format($neto, 2) }}</b></span>
                            </span>
                        </div>
                        <ol class="desktop-kdx-events">
                            @foreach($mes['movimientos'] as $mov)
                                @php
                                    $clase = $mov->tmi_clase ?: ((float) $mov->min_signo >= 0 ? 'entrada' : 'salida');
                                    $tipoLabel = $mov->tmi_nombre ?: ($claseLabels[$clase] ?? 'Movimiento');
                                    $signo = (float) $mov->min_signo;
                                    $cantidad = (float) $mov->min_cantidad;
                                    $qtyStr = ($signo >= 0 ? '+' : '-') . number_format($cantidad, 2);
                                    $saldo = (float) $mov->min_existencia_despues;
                                    $saldoEstado = $saldo > 0 ? 'pos' : ($saldo < 0 ? 'neg' : 'zero');
                                    $ref = $mov->min_documento_referencia ?: $mov->min_folio;
                                    $usuario = $mov->usuario_nombre ?: '—';
                                    $obs = $mov->min_motivo_texto ?: '';
                                    $searchText = mb_strtolower(trim(($ref ?: '') . ' ' . $usuario . ' ' . $obs . ' ' . $tipoLabel));
                                @endphp
                                <li class="desktop-kdx-event desktop-kdx-event--{{ $clase }}" data-clase="{{ $clase }}" data-text="{{ $searchText }}">
                                    <span class="desktop-kdx-event__marker"></span>
                                    <div class="desktop-kdx-event__card">
                                        <div class="desktop-kdx-event__top">
                                            <span class="desktop-kdx-event__type">{{ $tipoLabel }}</span>
                                            <span class="desktop-kdx-event__qty">{{ $qtyStr }}</span>
                                            <span class="desktop-kdx-saldo desktop-kdx-saldo--{{ $saldoEstado }}"><span>Saldo</span> {{ number_format($saldo, 2) }}</span>
                                            <span class="desktop-kdx-event__when">{{ \Illuminate\Support\Carbon::parse($mov->min_fecha_movimiento)->translatedFormat('d M Y · H:i') }}</span>
                                        </div>
                                        <div class="desktop-kdx-event__detail">
                                            <span class="ref"><b>Ref:</b> {{ $ref ?: '—' }}</span>
                                            <span><b>Usuario:</b> {{ $usuario }}</span>
                                            @if($obs !== '')<span><b>Obs:</b> {{ $obs }}</span>@endif
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endforeach
            @endif
        </div>
    </section>
@endsection

@push('desktop-scripts')
    <script>
        (function () {
            // Periodo personalizado: revelar fechas y auto-aplicar al completar el rango
            const form = document.getElementById('kd-periodform');
            const toggle = document.getElementById('kd-custom-toggle');
            const range = document.getElementById('kd-range');
            const fi = document.getElementById('kd-fi');
            const ff = document.getElementById('kd-ff');
            const rangeFlag = document.getElementById('kd-range-flag');

            if (toggle) {
                toggle.addEventListener('click', function () {
                    range.classList.toggle('is-open');
                    if (range.classList.contains('is-open')) (fi.value ? ff : fi).focus();
                });
            }
            function maybeSubmitRange() {
                if (fi.value && ff.value) {
                    rangeFlag.disabled = false; // envía periodo=rango
                    form.submit();
                }
            }
            if (fi && ff) {
                fi.addEventListener('change', maybeSubmitRange);
                ff.addEventListener('change', maybeSubmitRange);
            }

            // Búsqueda interna + filtro por tipo (client-side sobre la línea de tiempo)
            const search = document.getElementById('kd-search');
            const typeSel = document.getElementById('kd-type');
            const noResults = document.getElementById('kd-noresults');
            const events = Array.from(document.querySelectorAll('.desktop-kdx-event'));
            const months = Array.from(document.querySelectorAll('.desktop-kdx-monthgroup'));

            function applyFilter() {
                const q = (search?.value || '').trim().toLowerCase();
                const type = typeSel?.value || '';
                let visibles = 0;

                events.forEach(function (el) {
                    const matchText = !q || (el.dataset.text || '').indexOf(q) !== -1;
                    const matchType = !type || el.dataset.clase === type;
                    const show = matchText && matchType;
                    el.hidden = !show;
                    if (show) visibles += 1;
                });

                months.forEach(function (m) {
                    const any = m.querySelectorAll('.desktop-kdx-event:not([hidden])').length > 0;
                    m.hidden = !any;
                });

                if (noResults) noResults.hidden = visibles !== 0;
            }

            if (search) search.addEventListener('input', applyFilter);
            if (typeSel) typeSel.addEventListener('change', applyFilter);
        })();
    </script>
@endpush
