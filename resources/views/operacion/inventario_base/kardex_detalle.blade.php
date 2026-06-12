@extends('layouts.app')

@section('title', 'Kardex Completo')

@php
    $backParams = array_filter([
        'prd_mrc_id' => $detalle['back_filters']['prd_mrc_id'] ?? null,
        'prd_mdl_id' => $detalle['back_filters']['prd_mdl_id'] ?? null,
        'prd_lna_id' => $detalle['back_filters']['prd_lna_id'] ?? null,
        'prd_ctg_id' => $detalle['back_filters']['prd_ctg_id'] ?? null,
        'prd_id' => $detalle['back_filters']['prd_id'] ?? null,
        'prd_text' => $detalle['back_filters']['prd_text'] ?? null,
        'buscar' => $detalle['back_filters']['buscar'] ?? null,
    ], fn ($value) => $value !== null && $value !== '');
    $detailQuery = array_filter([
        'back_prd_mrc_id' => $detalle['back_filters']['prd_mrc_id'] ?? null,
        'back_prd_mdl_id' => $detalle['back_filters']['prd_mdl_id'] ?? null,
        'back_prd_lna_id' => $detalle['back_filters']['prd_lna_id'] ?? null,
        'back_prd_ctg_id' => $detalle['back_filters']['prd_ctg_id'] ?? null,
        'back_prd_id' => $detalle['back_filters']['prd_id'] ?? null,
        'back_prd_text' => $detalle['back_filters']['prd_text'] ?? null,
        'back_buscar' => $detalle['back_filters']['buscar'] ?? null,
    ], fn ($value) => $value !== null && $value !== '');
@endphp

@push('vendor-styles')
    <style>
        .kd-summary,
        .kd-filters,
        .kd-month {
            background: var(--ls-surface-1);
            border: 1px solid var(--ls-border);
            border-radius: var(--ls-radius);
        }

        .kd-summary {
            padding: 1.1rem;
        }

        .kd-summary__grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 1rem;
        }

        .kd-summary__label {
            font-size: .74rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--ls-text-muted);
            margin-bottom: .22rem;
        }

        .kd-summary__value {
            font-weight: 700;
            color: var(--ls-text-primary);
        }

        .kd-filters {
            padding: 1rem;
        }

        .kd-month {
            padding: 1rem;
            margin-top: 1rem;
        }

        .kd-month__head {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: center;
            margin-bottom: .85rem;
        }

        .kd-chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .25rem .55rem;
            border-radius: 999px;
            background: var(--ls-surface-2);
            border: 1px solid var(--ls-border);
            font-size: .8rem;
            color: var(--ls-text-secondary);
        }

        .kd-empty {
            padding: 2rem 1rem;
            text-align: center;
            color: var(--ls-text-secondary);
        }
    </style>
@endpush

@section('content')
<x-section-header
    eyebrow="Operación"
    title="Kardex Completo"
    subtitle="Detalle histórico del SKU y su variante exacta, agrupado por mes."
/>

<div class="d-flex justify-content-between align-items-center gap-2 mb-3">
    <a href="{{ route('operacion.inventario_base.existencias_matriz.index', $backParams) }}" class="btn btn-outline-secondary">
        <i class="ti tabler-arrow-left me-1"></i>Volver a existencias matriz
    </a>
    <span class="text-body-secondary small">Consulta realizada el {{ optional($detalle['fecha_consulta'])->format('d/m/Y H:i') }}</span>
</div>

<div class="kd-summary mb-3">
    <div class="kd-summary__grid">
        <div>
            <div class="kd-summary__label">Producto</div>
            <div class="kd-summary__value">{{ $detalle['producto']?->prd_codigo }} - {{ $detalle['producto']?->prd_nombre }}</div>
        </div>
        <div>
            <div class="kd-summary__label">SKU</div>
            <div class="kd-summary__value">{{ $detalle['sku']?->psk_codigo ?: 'N/D' }}</div>
        </div>
        <div>
            <div class="kd-summary__label">Talla</div>
            <div class="kd-summary__value">{{ $detalle['talla'] }}</div>
        </div>
        <div>
            <div class="kd-summary__label">Color / Variante</div>
            <div class="kd-summary__value">{{ $detalle['color'] ?: ($detalle['sku']?->psk_nombre ?: 'Base') }}</div>
        </div>
        <div>
            <div class="kd-summary__label">Existencia actual</div>
            <div class="kd-summary__value">{{ number_format((float) $detalle['existencia_actual'], 2) }} {{ $detalle['unidad'] }}</div>
        </div>
        <div>
            <div class="kd-summary__label">Ámbito</div>
            <div class="kd-summary__value">Todos los almacenes activos</div>
        </div>
        <div>
            <div class="kd-summary__label">Periodo consultado</div>
            <div class="kd-summary__value">{{ $detalle['fecha_inicio'] }} a {{ $detalle['fecha_fin'] }}</div>
        </div>
    </div>
</div>

<form method="GET" class="kd-filters mb-3">
    @foreach($backParams as $key => $value)
        <input type="hidden" name="back_{{ $key }}" value="{{ $value }}">
    @endforeach

    <div class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Periodo</label>
            <select name="periodo" id="kd-periodo" class="form-select">
                <option value="este_mes" {{ $detalle['periodo'] === 'este_mes' ? 'selected' : '' }}>Este mes</option>
                <option value="ultimos_3_meses" {{ $detalle['periodo'] === 'ultimos_3_meses' ? 'selected' : '' }}>Últimos 3 meses</option>
                <option value="este_anio" {{ $detalle['periodo'] === 'este_anio' ? 'selected' : '' }}>Este año</option>
                <option value="ultimos_3_anios" {{ $detalle['periodo'] === 'ultimos_3_anios' ? 'selected' : '' }}>Últimos 3 años</option>
                <option value="rango" {{ $detalle['periodo'] === 'rango' ? 'selected' : '' }}>Rango</option>
            </select>
        </div>
        <div class="col-md-3 kd-rango {{ $detalle['periodo'] === 'rango' ? '' : 'd-none' }}">
            <label class="form-label">Fecha inicio</label>
            <input type="date" name="fecha_inicio" class="form-control" value="{{ request('fecha_inicio', $detalle['periodo'] === 'rango' ? $detalle['fecha_inicio'] : '') }}">
        </div>
        <div class="col-md-3 kd-rango {{ $detalle['periodo'] === 'rango' ? '' : 'd-none' }}">
            <label class="form-label">Fecha fin</label>
            <input type="date" name="fecha_fin" class="form-control" value="{{ request('fecha_fin', $detalle['periodo'] === 'rango' ? $detalle['fecha_fin'] : '') }}">
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100">
                <i class="ti tabler-filter me-1"></i>Aplicar
            </button>
            <a href="{{ route('operacion.inventario_base.kardex.detalle', array_merge(['sku' => $detalle['sku']->psk_id], $detailQuery)) }}" class="btn btn-outline-secondary">
                <i class="ti tabler-refresh"></i>
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mt-3 mb-0">{{ $errors->first() }}</div>
    @endif
</form>

@if(collect($detalle['timeline'])->isEmpty())
    <div class="kd-month">
        <div class="kd-empty">No se encontraron movimientos para el periodo seleccionado.</div>
    </div>
@else
    @foreach($detalle['timeline'] as $mes)
        <section class="kd-month">
            <div class="kd-month__head">
                <div>
                    <h5 class="mb-1">{{ $mes['mes_label'] }}</h5>
                    <div class="text-body-secondary small">{{ $mes['total_movimientos'] }} movimiento(s)</div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="kd-chip">Entradas: {{ number_format((float) $mes['entradas'], 2) }}</span>
                    <span class="kd-chip">Salidas: {{ number_format((float) $mes['salidas'], 2) }}</span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Movimiento</th>
                            <th class="text-end">Entrada</th>
                            <th class="text-end">Salida</th>
                            <th class="text-end">Saldo</th>
                            <th>Referencia</th>
                            <th>Usuario</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($mes['movimientos'] as $movimiento)
                            @php
                                $cantidad = (float) $movimiento->min_cantidad;
                                $esEntrada = (float) $movimiento->min_signo > 0;
                            @endphp
                            <tr>
                                <td>{{ optional(\Illuminate\Support\Carbon::parse($movimiento->min_fecha_movimiento))->format('d/m/Y H:i') }}</td>
                                <td>{{ $movimiento->tmi_nombre ?: 'Movimiento' }}</td>
                                <td class="text-end">{{ $esEntrada ? number_format($cantidad, 2) : '—' }}</td>
                                <td class="text-end">{{ !$esEntrada ? number_format($cantidad, 2) : '—' }}</td>
                                <td class="text-end">{{ number_format((float) $movimiento->min_existencia_despues, 2) }}</td>
                                <td>
                                    <div>{{ $movimiento->min_documento_referencia ?: '—' }}</div>
                                    <div class="small text-body-secondary">{{ $movimiento->min_folio }}</div>
                                </td>
                                <td>{{ $movimiento->usuario_nombre ?: '—' }}</td>
                                <td>{{ $movimiento->min_motivo_texto ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endforeach
@endif
@endsection

@push('page-scripts')
    <script>
        (() => {
            const periodo = document.getElementById('kd-periodo');
            const rangoFields = Array.from(document.querySelectorAll('.kd-rango'));

            function syncRango() {
                const isRango = periodo?.value === 'rango';
                rangoFields.forEach((item) => {
                    item.classList.toggle('d-none', !isRango);
                    item.querySelectorAll('input').forEach((input) => {
                        input.disabled = !isRango;
                    });
                });
            }

            periodo?.addEventListener('change', syncRango);
            syncRango();
        })();
    </script>
@endpush
