@extends('layouts.desktop')
@section('title', 'Ventas históricas')

@push('desktop-styles')
<style>
    .sales-history{max-width:1160px;margin:0 auto;display:grid;gap:20px;color:var(--text)}
    .history-heading{display:flex;align-items:center;justify-content:space-between;gap:16px}.history-heading h1{font-size:1.65rem;letter-spacing:-.035em;margin:6px 0 8px}.history-heading p{color:var(--text-2);margin:0;max-width:650px;line-height:1.6;font-size:.88rem}.history-eyebrow{font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.13em;color:var(--brand)}
    .history-context{display:flex;align-items:center;gap:9px;color:var(--text-2);font-size:.78rem}.history-context span{background:var(--surface);border:1px solid var(--stroke);border-radius:999px;padding:6px 12px}
    .history-layout{display:grid;grid-template-columns:minmax(0,1fr) 310px;align-items:start;gap:20px}.history-card{background:var(--surface);border:1px solid var(--stroke);border-radius:16px;box-shadow:var(--shadow-2);overflow:hidden}.history-card__head{padding:20px 24px;border-bottom:1px solid var(--divider);display:flex;justify-content:space-between;gap:12px;align-items:center}.history-card__head h2{font-size:1rem;margin:0}.history-card__head p{color:var(--text-2);font-size:.79rem;margin:5px 0 0}.history-badge{font-size:.68rem;font-weight:700;padding:5px 9px;white-space:nowrap;color:var(--brand);background:var(--brand-soft);border-radius:7px}
    .history-section{border:0;padding:22px 24px;margin:0;min-width:0}.history-section+.history-section{border-top:1px solid var(--divider)}.history-section legend{float:left;display:flex;align-items:center;gap:9px;width:100%;font-size:.86rem;font-weight:750;margin-bottom:17px}.history-section legend span{display:grid;place-items:center;width:23px;height:23px;border-radius:7px;background:var(--surface-sunken);color:var(--text-2);font-size:.73rem}.history-fields{clear:both;display:grid;grid-template-columns:1fr 1fr;gap:17px}.history-full{grid-column:1/-1}.history-fields .desktop-field{margin:0}.history-fields label{font-size:.8rem}.history-fields input,.history-fields select,.history-fields textarea{width:100%;min-height:42px;border:1px solid var(--stroke-strong);background:var(--surface);color:var(--text);border-radius:9px;padding:10px 12px;font:inherit;font-size:.86rem}.history-fields textarea{resize:vertical;min-height:82px}.history-fields input:focus-visible,.history-fields select:focus-visible,.history-fields textarea:focus-visible{outline:2px solid var(--brand);outline-offset:2px}.history-fields small{display:block;margin-top:7px;font-size:.74rem;line-height:1.5;color:var(--text-2)}.history-fields [aria-invalid=true]{border-color:#bb3b3b}.history-fields .history-error{color:#a12c2c}.history-optional{font-weight:400;color:var(--text-3);font-size:.72rem}.history-money{position:relative}.history-money>span{position:absolute;left:13px;top:11px;color:var(--text-2)}.history-money input{padding-left:29px;font-variant-numeric:tabular-nums}
    .history-footer{padding:17px 24px;border-top:1px solid var(--divider);background:var(--surface-sunken);display:flex;justify-content:flex-end;align-items:center;gap:10px}.history-aside{display:grid;gap:16px;position:sticky;top:20px}.history-preview{padding:23px;background:linear-gradient(160deg,var(--surface),var(--brand-soft))}.history-preview h2{margin:0 0 20px;font-size:.92rem}.history-preview dl{margin:0;display:grid;gap:14px}.history-preview dl div{display:flex;justify-content:space-between;gap:10px;font-size:.79rem}.history-preview dt{font-weight:400;color:var(--text-2)}.history-preview dd{margin:0;font-variant-numeric:tabular-nums;font-weight:650}.history-preview__total{margin-top:18px;padding-top:18px;border-top:1px solid var(--stroke)}.history-preview__total span{display:block;font-size:.73rem;color:var(--text-2)}.history-preview__total output{display:block;font-size:1.9rem;font-weight:800;letter-spacing:-.04em;margin:6px 0;color:var(--brand);overflow-wrap:anywhere}.history-preview__total p{font-size:.76rem;line-height:1.6;color:var(--text-2);margin:0}.history-guide{padding:20px 22px}.history-guide h3{font-size:.84rem;margin:0 0 12px}.history-guide p{font-size:.77rem;line-height:1.65;color:var(--text-2);margin:0 0 12px}.history-guide p:last-child{margin-bottom:0}.history-guide strong{color:var(--text)}.history-feedback{border-radius:12px;padding:14px 18px;font-size:.83rem;line-height:1.5}.history-feedback--success{background:#eaf7ef;color:#256044;border:1px solid #b7dfc5}.history-feedback--error{background:#fff0ef;color:#922d27;border:1px solid #efc1bd}.history-feedback ul{margin:6px 0 0;padding-left:20px}
    .history-filter{display:flex;align-items:end;gap:8px}.history-filter label{display:block;font-size:.7rem;color:var(--text-2);margin-bottom:4px}.history-filter input{min-height:35px;border:1px solid var(--stroke);border-radius:7px;background:var(--surface);color:var(--text);padding:5px 8px}.history-table-wrap{overflow-x:auto}.history-table{width:100%;border-collapse:collapse;font-size:.8rem;min-width:710px}.history-table th{background:var(--surface-sunken);color:var(--text-2);padding:12px 20px;font-size:.68rem;letter-spacing:.04em;text-transform:uppercase;text-align:left}.history-table td{padding:15px 20px;border-top:1px solid var(--divider)}.history-table .history-number{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}.history-table small{display:block;color:var(--text-2);margin-top:4px;font-size:.71rem}.history-table a{color:var(--brand);font-weight:650}.history-table tfoot td{background:var(--surface-sunken);font-weight:750}.history-empty{text-align:center;padding:32px 20px;color:var(--text-2);font-size:.84rem;line-height:1.7}.history-empty strong{display:block;color:var(--text);font-size:.94rem;margin-bottom:3px}.history-dot{width:6px;height:6px;display:inline-block;background:var(--brand);border-radius:50%;margin-right:6px}
    @media(max-width:950px){.history-layout{grid-template-columns:minmax(0,1fr) 270px}.history-card__head,.history-section{padding:18px}.history-heading h1{font-size:1.4rem}}
    @media(max-width:740px){.history-layout{grid-template-columns:1fr}.history-aside{position:static}.history-heading{align-items:start;flex-direction:column}.history-fields{grid-template-columns:1fr}.history-card__head{flex-wrap:wrap}.history-filter{width:100%}.history-footer{padding:16px 18px}.history-context{flex-wrap:wrap}}
</style>
@endpush

@section('desktop-toolbar')
<div class="page-head"><span class="page-head__title">Metas y comisiones</span><span class="page-head__sub">Ventas históricas</span></div>
@endsection

@section('content')
@php
    $periodoCaptura = old('periodo', $edicion ? substr($edicion->chv_periodo, 0, 7) : $mes);
    $ventasIniciales = old('ventas_netas', $edicion?->chv_ventas_netas);
    $autoInicial = old('autoservicio', $edicion?->chv_autoservicio ?? '0.00');
    $baseInicial = max(0, (float)$ventasIniciales - (float)$autoInicial);
@endphp
<div class="sales-history">
    <header class="history-heading">
        <div><span class="history-eyebrow">Referencia para tus metas</span><h1>Ventas históricas</h1><p>Recupera las ventas de meses anteriores y úsalas como punto de partida para las metas del próximo año.</p></div>
        <a class="desktop-btn desktop-btn--ghost" href="{{ route('desktop.operacion.gestion_configuraciones.comisiones.index') }}">← Volver a comisiones</a>
    </header>
    <div class="history-context"><span><i class="history-dot" aria-hidden="true"></i>{{ $sucursalNombre }}</span>Una captura por mes, almacén y línea</div>

    @if(session('success'))<div class="history-feedback history-feedback--success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="history-feedback history-feedback--error" role="alert"><strong>No se guardó la captura.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <form action="{{ route('desktop.operacion.gestion_configuraciones.comisiones.historico.store') }}" method="POST" data-ls-autocomplete="admin" id="history-form">
        @csrf
        @if($edicion)<input type="hidden" name="id" value="{{ $edicion->chv_id }}"><input type="hidden" name="version" value="{{ old('version', $edicion->chv_version) }}">@endif
        <div class="history-layout">
            <section class="history-card">
                <header class="history-card__head"><div><h2>{{ $edicion ? 'Corregir captura' : 'Nueva captura mensual' }}</h2><p>Ten a la mano el reporte de ventas del mes completo.</p></div><span class="history-badge">{{ $edicion ? 'Corrección' : 'Captura manual' }}</span></header>
                <fieldset class="history-section"><legend><span>1</span> Identifica las ventas</legend><div class="history-fields">
                    <div class="desktop-field"><label for="history-period">Mes y año de las ventas</label><input id="history-period" type="month" name="periodo" required max="{{ now()->subMonthNoOverflow()->format('Y-m') }}" value="{{ $periodoCaptura }}" aria-invalid="{{ $errors->has('periodo') ? 'true' : 'false' }}">@error('periodo')<small class="history-error">{{ $message }}</small>@enderror</div>
                    <div class="desktop-field"><label for="history-warehouse">Almacén</label><select id="history-warehouse" name="almacen_id" required aria-invalid="{{ $errors->has('almacen_id') ? 'true' : 'false' }}"><option value="">Selecciona un almacén</option>@foreach($almacenes as $almacen)<option value="{{ $almacen->alm_id }}" @selected((string)old('almacen_id', $edicion?->chv_alm_id) === (string)$almacen->alm_id)>{{ $almacen->alm_nombre }}</option>@endforeach</select>@error('almacen_id')<small class="history-error">{{ $message }}</small>@enderror</div>
                    <div class="desktop-field history-full"><label for="history-line">Línea de producto</label><select id="history-line" name="linea_id" required aria-describedby="history-line-help" aria-invalid="{{ $errors->has('linea_id') ? 'true' : 'false' }}"><option value="">Selecciona una línea</option>@foreach($lineas as $linea)<option value="{{ $linea->lna_id }}" @selected((string)old('linea_id', $edicion?->chv_lna_id) === (string)$linea->lna_id)>{{ $linea->lna_nombre }}</option>@endforeach</select><small id="history-line-help">El departamento se determina con las líneas que elijas al configurar las metas.</small>@error('linea_id')<small class="history-error">{{ $message }}</small>@enderror</div>
                </div></fieldset>
                <fieldset class="history-section"><legend><span>2</span> Captura los importes</legend><div class="history-fields">
                    <div class="desktop-field"><label for="history-sales">Ventas netas totales</label><div class="history-money"><span aria-hidden="true">$</span><input id="history-sales" name="ventas_netas" type="number" min="0" max="999999999999.99" step="0.01" inputmode="decimal" placeholder="0.00" value="{{ $ventasIniciales }}" required aria-describedby="history-sales-help" aria-invalid="{{ $errors->has('ventas_netas') ? 'true' : 'false' }}"></div><small id="history-sales-help">Incluye el autoservicio. Usa el total después de descuentos y devoluciones, sin ventas canceladas.</small>@error('ventas_netas')<small class="history-error">{{ $message }}</small>@enderror</div>
                    <div class="desktop-field"><label for="history-self-service">De ese total, autoservicio</label><div class="history-money"><span aria-hidden="true">$</span><input id="history-self-service" name="autoservicio" type="number" min="0" step="0.01" inputmode="decimal" value="{{ $autoInicial }}" required aria-describedby="history-auto-help" aria-invalid="{{ $errors->has('autoservicio') ? 'true' : 'false' }}"></div><small id="history-auto-help">Ventas netas sin atención de un vendedor. Se descontarán una sola vez.</small>@error('autoservicio')<small class="history-error">{{ $message }}</small>@enderror</div>
                </div></fieldset>
                <fieldset class="history-section"><legend><span>3</span> Deja una referencia</legend><div class="history-fields">
                    <div class="desktop-field history-full"><label for="history-reference">Reporte o documento de origen</label><input id="history-reference" name="referencia" maxlength="250" value="{{ old('referencia', $edicion?->chv_referencia) }}" placeholder="Ej. Reporte mensual del sistema anterior · agosto 2025" required><small>Una referencia para localizar y revisar estas cifras después.</small></div>
                    <div class="desktop-field history-full"><label for="history-notes">Observaciones <span class="history-optional">Opcional</span></label><textarea id="history-notes" name="observaciones" maxlength="1000" rows="2" placeholder="Anota cualquier aclaración sobre este reporte.">{{ old('observaciones', $edicion?->chv_observaciones) }}</textarea></div>
                    @if($edicion)<div class="desktop-field history-full"><label for="history-reason">Motivo de la corrección</label><textarea id="history-reason" name="motivo" maxlength="500" rows="2" required placeholder="Explica qué cambió y por qué.">{{ old('motivo') }}</textarea></div>@endif
                </div></fieldset>
                <footer class="history-footer"><a class="desktop-btn desktop-btn--ghost" href="{{ route('desktop.operacion.gestion_configuraciones.comisiones.historico.index', ['mes' => $mes]) }}">{{ $edicion ? 'Cancelar' : 'Limpiar' }}</a><button type="submit" class="desktop-btn desktop-btn--primary">{{ $edicion ? 'Guardar corrección' : 'Guardar histórico' }}</button></footer>
            </section>
            <aside class="history-aside">
                <section class="history-card history-preview" aria-label="Resumen de la captura">
                    <h2>Revisa tu captura</h2><dl><div><dt>Ventas netas</dt><dd id="history-total">${{ number_format((float)$ventasIniciales, 2) }}</dd></div><div><dt>Menos autoservicio</dt><dd id="history-excluded">− ${{ number_format((float)$autoInicial, 2) }}</dd></div></dl>
                    <div class="history-preview__total" aria-live="polite"><span>Base para calcular metas</span><output id="history-base" for="history-sales history-self-service">${{ number_format($baseInicial, 2) }}</output><p id="history-preview-message">Este importe se dividirá entre el equipo del mes que configures y se aplicará el incremento definido.</p></div>
                </section>
                <section class="history-card history-guide"><h3>¿Cómo se usará?</h3><p id="history-target">La captura servirá como referencia para el mismo mes del año siguiente.</p><p>Captura el <strong>total completo del mes</strong> para esta línea y almacén. Será la referencia preferente, aunque ya existan ventas de esa combinación en el sistema.</p><p>Después, guarda la configuración de comisiones y revisa la <strong>meta sugerida</strong>. Las metas ya aprobadas requieren una corrección con motivo; los periodos cerrados conservan sus resultados.</p></section>
            </aside>
        </div>
    </form>

    <section class="history-card" aria-label="Histórico capturado">
        <header class="history-card__head"><div><h2>Capturas guardadas</h2><p>{{ $registros->count() }} {{ $registros->count() === 1 ? 'registro' : 'registros' }} en el mes seleccionado</p></div><form method="GET" class="history-filter"><div><label for="history-filter-month">Consultar mes</label><input type="month" id="history-filter-month" name="mes" value="{{ $mes }}" required></div><button type="submit" class="desktop-btn desktop-btn--ghost">Consultar</button></form></header>
        @if($registros->isEmpty())<div class="history-empty"><strong>Aún no hay capturas en este mes</strong>Agrega la primera con el formulario. Puedes registrar una línea a la vez.</div>
        @else<div class="history-table-wrap"><table class="history-table"><thead><tr><th>Almacén / línea</th><th class="history-number">Ventas netas</th><th class="history-number">Autoservicio</th><th class="history-number">Base para metas</th><th><span class="visually-hidden">Acciones</span></th></tr></thead><tbody>
            @foreach($registros as $registro)<tr><td><strong>{{ $registro->lna_nombre }}</strong><small>{{ $registro->alm_nombre }}</small><small>{{ $registro->chv_referencia }}</small></td><td class="history-number">${{ number_format($registro->chv_ventas_netas, 2) }}</td><td class="history-number">${{ number_format($registro->chv_autoservicio, 2) }}</td><td class="history-number"><strong>${{ number_format($registro->chv_ventas_netas - $registro->chv_autoservicio, 2) }}</strong></td><td><a href="{{ route('desktop.operacion.gestion_configuraciones.comisiones.historico.index', ['mes' => $mes, 'editar' => $registro->chv_id]) }}" aria-label="Corregir {{ $registro->lna_nombre }} en {{ $registro->alm_nombre }}">Corregir</a></td></tr>@endforeach
        </tbody><tfoot><tr><td>Total capturado</td><td class="history-number">${{ number_format($registros->sum('chv_ventas_netas'), 2) }}</td><td class="history-number">${{ number_format($registros->sum('chv_autoservicio'), 2) }}</td><td class="history-number">${{ number_format($registros->sum('chv_ventas_netas') - $registros->sum('chv_autoservicio'), 2) }}</td><td></td></tr></tfoot></table></div>@endif
    </section>
</div>
@endsection

@push('desktop-scripts')
<script>
(() => {
    const sales = document.getElementById('history-sales');
    const selfService = document.getElementById('history-self-service');
    const period = document.getElementById('history-period');
    const money = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });
    const update = () => {
        const total = Number(sales.value || 0);
        const excluded = Number(selfService.value || 0);
        const invalid = excluded > total;
        selfService.setCustomValidity(invalid ? 'El autoservicio no puede superar las ventas netas totales.' : '');
        document.getElementById('history-total').textContent = money.format(total);
        document.getElementById('history-excluded').textContent = '− ' + money.format(excluded);
        document.getElementById('history-base').textContent = invalid ? 'Revisa los importes' : money.format(Math.max(0, total - excluded));
        document.getElementById('history-preview-message').textContent = invalid
            ? 'El autoservicio debe estar incluido en el total de ventas netas.'
            : 'Este importe se dividirá entre el equipo del mes que configures y se aplicará el incremento definido.';
        if (/^\d{4}-\d{2}$/.test(period.value)) {
            const [year, month] = period.value.split('-').map(Number);
            const target = new Intl.DateTimeFormat('es-MX', { month: 'long', year: 'numeric' }).format(new Date(year + 1, month - 1, 1));
            document.getElementById('history-target').textContent = 'Estas ventas serán la referencia para las metas de ' + target + '.';
        }
    };
    [sales, selfService, period].forEach(field => field.addEventListener('input', update));
    update();
})();
</script>
@endpush
