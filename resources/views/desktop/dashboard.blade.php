@extends('layouts.desktop')

@section('title', 'Dashboard')

@push('desktop-vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/chartjs/chartjs.css') }}" />
@endpush

@push('desktop-styles')
    <style>
        .dash { display: flex; flex-direction: column; gap: 16px; }

        .dash__header {
            display: flex; align-items: center; justify-content: space-between; gap: 16px;
            padding: 16px 20px;
            background: linear-gradient(135deg, #0f6cbd 0%, #1452a3 100%);
            border-radius: var(--r-lg);
            color: #fff;
            box-shadow: var(--shadow-4);
        }
        .dash__header h1 { margin: 0; font-size: 1.25rem; font-weight: 600; letter-spacing: -.02em; }
        .dash__header p { margin: 4px 0 0; font-size: .85rem; opacity: .85; }
        .dash__header-meta { display: flex; align-items: center; gap: 16px; }
        .dash__header-stat { text-align: center; }
        .dash__header-stat strong { display: block; font-size: 1.4rem; font-weight: 700; }
        .dash__header-stat span { font-size: .72rem; opacity: .8; text-transform: uppercase; letter-spacing: .03em; }

        .dash__section-title {
            font-size: .82rem; font-weight: 600; color: var(--text-2);
            text-transform: uppercase; letter-spacing: .04em;
            margin: 4px 2px 0;
        }

        .kpi-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
        .kpi {
            display: flex; flex-direction: column; gap: 6px;
            padding: 16px 18px;
            background: var(--surface);
            border: 1px solid var(--stroke);
            border-radius: var(--r-lg);
            box-shadow: var(--shadow-2);
            transition: box-shadow .15s ease, border-color .15s ease;
        }
        .kpi:hover { box-shadow: var(--shadow-4); border-color: var(--stroke-strong); }
        .kpi__top { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .kpi__icon {
            width: 36px; height: 36px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: var(--r-md);
        }
        .kpi__icon svg { width: 20px; height: 20px; }
        .kpi__icon--blue { color: #0f6cbd; background: rgba(15, 108, 189, .1); }
        .kpi__icon--green { color: #107c41; background: rgba(16, 124, 65, .1); }
        .kpi__icon--orange { color: #c27803; background: rgba(194, 120, 3, .1); }
        .kpi__icon--purple { color: #7c3aed; background: rgba(124, 58, 237, .1); }
        .kpi__label { font-size: .78rem; color: var(--text-2); font-weight: 500; }
        .kpi__value { font-size: 1.5rem; font-weight: 700; letter-spacing: -.02em; line-height: 1.1; color: var(--text); }
        .kpi__delta { font-size: .74rem; display: flex; align-items: center; gap: 4px; }
        .kpi__delta--up { color: #107c41; }
        .kpi__delta--down { color: #d13438; }
        .kpi__delta--neutral { color: var(--text-3); }

        .panel-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 12px; }
        .panel-grid--equal { grid-template-columns: 1fr 1fr; }
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
        .panel__head h3 { margin: 0; font-size: .92rem; font-weight: 600; }
        .panel__head-sub { font-size: .75rem; color: var(--text-2); }
        .panel__body { padding: 16px 18px; }
        .panel__body--compact { padding: 8px; }

        .chart-wrap { position: relative; height: 220px; }

        .top-list { display: flex; flex-direction: column; gap: 2px; }
        .top-item {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 12px; border-radius: var(--r-md);
            transition: background .12s ease;
        }
        .top-item:hover { background: var(--surface-sunken); }
        .top-item__rank {
            width: 24px; height: 24px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 50%;
            background: var(--brand-soft); color: var(--brand);
            font-size: .72rem; font-weight: 700;
        }
        .top-item__info { flex: 1; min-width: 0; }
        .top-item__name { font-size: .84rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .top-item__meta { font-size: .72rem; color: var(--text-2); }
        .top-item__value { font-size: .9rem; font-weight: 700; color: var(--text); }

        .stat-row { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--divider); }
        .stat-row:last-child { border-bottom: none; }
        .stat-row__label { font-size: .84rem; color: var(--text); }
        .stat-row__value { font-size: .9rem; font-weight: 600; }

        .stock-list { display: flex; flex-direction: column; gap: 4px; }
        .stock-item {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 10px; border-radius: var(--r-md);
            background: var(--surface-sunken);
        }
        .stock-item__code { font-size: .72rem; font-weight: 600; color: var(--text-2); min-width: 60px; }
        .stock-item__name { flex: 1; font-size: .82rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .stock-item__qty { font-size: .78rem; font-weight: 600; padding: 2px 8px; border-radius: var(--r-sm); }
        .stock-item__qty--low { background: rgba(209, 52, 56, .12); color: #d13438; }
        .stock-item__qty--ok { background: rgba(16, 124, 65, .12); color: #107c41; }

        .empty-state { text-align: center; padding: 24px; color: var(--text-2); font-size: .84rem; }

        @media (max-width: 1200px) { .kpi-grid { grid-template-columns: repeat(2, 1fr); } .panel-grid, .panel-grid--equal { grid-template-columns: 1fr; } }
        @media (max-width: 560px) { .kpi-grid { grid-template-columns: 1fr; } .dash__header { flex-direction: column; text-align: center; } .dash__header-meta { justify-content: center; } }
    </style>
@endpush

@section('desktop-toolbar')
    <div class="page-head">
        <span class="page-head__title">Dashboard Financiero y Operativo</span>
        <span class="page-head__sub">Resumen general del negocio · {{ now()->format('d M Y') }}</span>
    </div>
    <div class="desktop-toolbar__group">
        <button type="button" class="desktop-btn desktop-btn--ghost" onclick="location.reload()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
            Actualizar
        </button>
        <a href="{{ route('desktop.ventas') }}" class="desktop-btn desktop-btn--primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 3v18h18"/><path d="m7 14 3-3 3 3 5-6"/></svg>
            Ver ventas
        </a>
    </div>
@endsection

@section('content')
    <div class="dash">
        <div class="dash__header">
            <div>
                <h1>La Suriana Retail</h1>
                <p>Panel de control financiero y operativo en tiempo real</p>
            </div>
            <div class="dash__header-meta">
                <div class="dash__header-stat">
                    <strong>${{ number_format($metricas['ventas_mes']['ingresos'], 0) }}</strong>
                    <span>Ingresos del mes</span>
                </div>
                <div class="dash__header-stat">
                    <strong>{{ number_format($metricas['ventas_mes']['tickets']) }}</strong>
                    <span>Tickets del mes</span>
                </div>
            </div>
        </div>

        <div>
            <div class="dash__section-title">Indicadores financieros</div>
            <div class="kpi-grid" style="margin-top: 8px;">
                <div class="kpi">
                    <div class="kpi__top">
                        <span class="kpi__icon kpi__icon--blue">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        </span>
                        <span class="kpi__label">Ventas hoy</span>
                    </div>
                    <div class="kpi__value">${{ number_format($metricas['ventas_hoy']['ingresos'], 2) }}</div>
                    <div class="kpi__delta {{ $metricas['variacion_hoy_vs_ayer'] > 0 ? 'kpi__delta--up' : ($metricas['variacion_hoy_vs_ayer'] < 0 ? 'kpi__delta--down' : 'kpi__delta--neutral') }}">
                        @if($metricas['variacion_hoy_vs_ayer'] !== null)
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;">
                                <path d="{{ $metricas['variacion_hoy_vs_ayer'] >= 0 ? 'M12 19V5M5 12l7-7 7 7' : 'M12 5v14M5 12l7 7 7-7' }}" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            {{ abs($metricas['variacion_hoy_vs_ayer']) }}% vs ayer
                        @else
                            Sin datos ayer
                        @endif
                    </div>
                </div>

                <div class="kpi">
                    <div class="kpi__top">
                        <span class="kpi__icon kpi__icon--green">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                        </span>
                        <span class="kpi__label">Tickets hoy</span>
                    </div>
                    <div class="kpi__value">{{ number_format($metricas['ventas_hoy']['tickets']) }}</div>
                    <div class="kpi__delta kpi__delta--neutral">
                        {{ number_format($metricas['ventas_semana']['tickets']) }} esta semana
                    </div>
                </div>

                <div class="kpi">
                    <div class="kpi__top">
                        <span class="kpi__icon kpi__icon--orange">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m7 14 3-3 3 3 5-6"/></svg>
                        </span>
                        <span class="kpi__label">Ingresos semana</span>
                    </div>
                    <div class="kpi__value">${{ number_format($metricas['ventas_semana']['ingresos'], 0) }}</div>
                    <div class="kpi__delta kpi__delta--neutral">
                        {{ number_format($metricas['ventas_semana']['tickets']) }} tickets
                    </div>
                </div>

                <div class="kpi">
                    <div class="kpi__top">
                        <span class="kpi__icon kpi__icon--purple">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        </span>
                        <span class="kpi__label">Ticket promedio</span>
                    </div>
                    <div class="kpi__value">${{ number_format($metricas['ventas_hoy']['ticket_promedio'], 2) }}</div>
                    <div class="kpi__delta kpi__delta--neutral">
                        Hoy
                    </div>
                </div>
            </div>
        </div>

        <div class="panel-grid">
            <div class="panel">
                <div class="panel__head">
                    <div>
                        <h3>Ventas últimos 7 días</h3>
                        <span class="panel__head-sub">Ingresos diarios</span>
                    </div>
                </div>
                <div class="panel__body">
                    <div class="chart-wrap">
                        <canvas id="chart-ventas-7dias"></canvas>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel__head">
                    <div>
                        <h3>Top vendedores</h3>
                        <span class="panel__head-sub">Este mes</span>
                    </div>
                </div>
                <div class="panel__body--compact">
                    @if($top_vendedores->count() > 0)
                        <div class="top-list">
                            @foreach($top_vendedores as $i => $vendedor)
                                <div class="top-item">
                                    <span class="top-item__rank">{{ $i + 1 }}</span>
                                    <div class="top-item__info">
                                        <div class="top-item__name">{{ $vendedor->vendedor }}</div>
                                        <div class="top-item__meta">{{ number_format($vendedor->tickets) }} tickets</div>
                                    </div>
                                    <div class="top-item__value">${{ number_format($vendedor->total_ventas, 0) }}</div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state">Sin ventas este mes</div>
                    @endif
                </div>
            </div>
        </div>

        <div>
            <div class="dash__section-title">Indicadores operativos</div>
            <div class="kpi-grid" style="margin-top: 8px;">
                <div class="kpi">
                    <div class="kpi__top">
                        <span class="kpi__icon kpi__icon--blue">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                        </span>
                        <span class="kpi__label">Productos activos</span>
                    </div>
                    <div class="kpi__value">{{ number_format($operativo['total_productos']) }}</div>
                    <div class="kpi__delta kpi__delta--neutral">En catálogo</div>
                </div>

                <div class="kpi">
                    <div class="kpi__top">
                        <span class="kpi__icon kpi__icon--green">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                        </span>
                        <span class="kpi__label">SKUs activos</span>
                    </div>
                    <div class="kpi__value">{{ number_format($operativo['total_skus']) }}</div>
                    <div class="kpi__delta kpi__delta--neutral">Variantes</div>
                </div>

                <div class="kpi">
                    <div class="kpi__top">
                        <span class="kpi__icon kpi__icon--orange">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
                        </span>
                        <span class="kpi__label">Pedidos pendientes</span>
                    </div>
                    <div class="kpi__value">{{ number_format($operativo['pedidos_pendientes']) }}</div>
                    <div class="kpi__delta kpi__delta--neutral">Por procesar</div>
                </div>

                <div class="kpi">
                    <div class="kpi__top">
                        <span class="kpi__icon kpi__icon--purple">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                        </span>
                        <span class="kpi__label">Cajas abiertas</span>
                    </div>
                    <div class="kpi__value">{{ number_format($operativo['cajas_abiertas']) }}</div>
                    <div class="kpi__delta kpi__delta--neutral">Sesiones activas</div>
                </div>
            </div>
        </div>

        <div class="panel-grid--equal panel-grid">
            <div class="panel">
                <div class="panel__head">
                    <div>
                        <h3>Métodos de pago</h3>
                        <span class="panel__head-sub">Distribución del mes</span>
                    </div>
                </div>
                <div class="panel__body">
                    @if($ventas_por_metodo_pago->count() > 0)
                        @php $totalPagos = $ventas_por_metodo_pago->sum('total'); @endphp
                        @foreach($ventas_por_metodo_pago as $pago)
                            <div class="stat-row">
                                <span class="stat-row__label">{{ ucfirst(str_replace('_', ' ', $pago->metodo)) }}</span>
                                <span class="stat-row__value">${{ number_format($pago->total, 0) }} <small style="color:var(--text-2);font-weight:400;">({{ $totalPagos > 0 ? round(($pago->total / $totalPagos) * 100) : 0 }}%)</small></span>
                            </div>
                        @endforeach
                    @else
                        <div class="empty-state">Sin datos</div>
                    @endif
                </div>
            </div>

            <div class="panel">
                <div class="panel__head">
                    <div>
                        <h3>Stock bajo</h3>
                        <span class="panel__head-sub">Productos por reabastecer</span>
                    </div>
                    <a href="{{ route('desktop.operacion.inventario.existencias_negativas.index') }}" class="desktop-btn desktop-btn--ghost" style="font-size:.75rem;">Ver todo</a>
                </div>
                <div class="panel__body--compact">
                    @if($productos_stock_bajo->count() > 0)
                        <div class="stock-list">
                            @foreach($productos_stock_bajo as $item)
                                <div class="stock-item">
                                    <span class="stock-item__code">{{ $item->psk_codigo }}</span>
                                    <span class="stock-item__name">{{ $item->nombre }}</span>
                                    <span class="stock-item__qty {{ $item->stock_actual <= 0 ? 'stock-item__qty--low' : 'stock-item__qty--low' }}">
                                        {{ number_format($item->stock_actual) }} / {{ $item->psk_stock_minimo }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state">Todos los productos tienen stock suficiente</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('desktop-vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/chartjs/chartjs.js') }}"></script>
@endpush

@push('desktop-scripts')
    <script>
        (function () {
            const ventasData = @json($ventas_ultimos_7_dias);
            const labels = ventasData.map(d => {
                const fecha = new Date(d.fecha + 'T00:00:00');
                return fecha.toLocaleDateString('es-MX', { weekday: 'short', day: 'numeric' });
            });
            const values = ventasData.map(d => parseFloat(d.total) || 0);

            const ctx = document.getElementById('chart-ventas-7dias');
            if (ctx && typeof Chart !== 'undefined') {
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Ingresos',
                            data: values,
                            backgroundColor: 'rgba(15, 108, 189, 0.8)',
                            borderColor: '#0f6cbd',
                            borderWidth: 1,
                            borderRadius: 6,
                            borderSkipped: false,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return '$' + context.parsed.y.toLocaleString('es-MX', { minimumFractionDigits: 2 });
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { font: { size: 11 } }
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(0,0,0,0.05)' },
                                ticks: {
                                    font: { size: 11 },
                                    callback: function(value) {
                                        return '$' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
            }
        })();
    </script>
@endpush
