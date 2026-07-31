@extends('layouts.desktop')

@section('title', $reporte['titulo'] ?? 'Reportes')

@php
    /* Categoría activa: la del reporte abierto o la primera del catálogo. */
    $categoriaActiva = $reporte['categoria'] ?? array_key_first($catalogo);
    $grupoActivo = $catalogo[$categoriaActiva] ?? null;
    $mostrarPeriodo = $reporte && $reporte['categoria'] !== 'inventario';
    $mostrarCaja = $reporte && in_array($reporte['categoria'], ['ventas', 'caja'], true);
    $mostrarAlmacen = $reporte && in_array($reporte['categoria'], ['inventario', 'compras', 'ventas'], true);
    $mostrarVendedor = $reporte && in_array($reporte['slug'], ['ventas-vendedor', 'ventas-descuentos'], true);
@endphp

@push('desktop-styles')
    <style>
        /* Navegación secundaria: reportes de la categoría activa. */
        .desktop-rep-tabs {
            display: flex; align-items: center; gap: 8px;
            padding: 6px 12px;
            border-bottom: 1px solid var(--stroke);
            background: var(--surface-alt);
            overflow-x: auto;
        }
        .desktop-rep-tabs { overflow: visible; }
        .desktop-rep-tabs__scroll { flex: 1 1 auto; min-width: 0; overflow-x: auto; }
        .desktop-rep-tabs__scroll .desktop-pivot { width: max-content; }

        /* Botón "Filtros" con contador de filtros activos (único control en el pane). */
        .desktop-rep-filterbtn {
            display: inline-flex; align-items: center; gap: 6px; flex: 0 0 auto;
            height: 30px; padding: 0 11px;
            border: 1px solid var(--stroke-strong); border-radius: var(--r-md);
            background: var(--surface); color: var(--text);
            font: inherit; font-size: .8rem; font-weight: 600;
            cursor: pointer; white-space: nowrap;
        }
        .desktop-rep-filterbtn svg { width: 15px; height: 15px; }
        .desktop-rep-filterbtn:hover { background: var(--surface-sunken); }
        .desktop-rep-filterbtn.is-active { border-color: var(--brand); color: var(--brand); }
        .desktop-rep-filterbtn__badge {
            display: none; align-items: center; justify-content: center;
            min-width: 18px; height: 18px; padding: 0 5px;
            border-radius: 999px;
            background: var(--brand); color: var(--on-brand);
            font-size: .68rem; font-weight: 700; line-height: 1;
        }
        .desktop-rep-filterbtn__badge.is-visible { display: inline-flex; }

        /* Offcanvas de filtros. */
        .desktop-rep-drawer { position: fixed; inset: 0; z-index: var(--z-drawer); display: none; }
        .desktop-rep-drawer.is-open { display: block; }
        .desktop-rep-drawer__scrim { position: absolute; inset: 0; background: rgba(15, 23, 42, .16); backdrop-filter: blur(2px); }
        .desktop-rep-drawer__panel {
            position: absolute; top: 0; right: 0; height: 100%; width: min(420px, 100%);
            display: flex; flex-direction: column;
            background: var(--surface); border-left: 1px solid var(--stroke);
            box-shadow: var(--shadow-16); animation: desktopRepDrawer .18s ease;
        }
        @keyframes desktopRepDrawer { from { transform: translateX(20px); opacity: .5; } to { transform: none; opacity: 1; } }
        .desktop-rep-drawer__head {
            display: flex; align-items: center; justify-content: space-between; gap: 8px;
            padding: 14px 16px; border-bottom: 1px solid var(--stroke);
        }
        .desktop-rep-drawer__title { font-size: .95rem; font-weight: 600; letter-spacing: -.01em; }
        .desktop-rep-drawer__sub { font-size: .76rem; color: var(--text-2); margin-top: 2px; }
        .desktop-rep-drawer__close {
            display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px;
            border: 0; border-radius: var(--r-md); background: transparent; color: var(--text-2);
            font-size: 1.2rem; line-height: 1; cursor: pointer;
        }
        .desktop-rep-drawer__close:hover { background: var(--surface-sunken); color: var(--text); }
        .desktop-rep-drawer__body { flex: 1 1 auto; overflow: auto; padding: 14px 16px; display: grid; gap: 12px; align-content: start; }
        .desktop-rep-drawer__foot {
            display: flex; align-items: center; justify-content: space-between; gap: 8px;
            padding: 12px 16px; border-top: 1px solid var(--stroke);
        }
        .desktop-rep-range { display: grid; gap: 12px; }
        .desktop-rep-range[hidden] { display: none; }

        /* Indicadores compactos: una sola fila, altura mínima.
           flex:0 0 auto es obligatorio: al ser contenedores con scroll, la
           barra de pestañas y la de KPIs pueden encogerse por debajo de su
           contenido cuando la tabla es larga, y el texto queda cortado. */
        .desktop-pane > .desktop-rep-tabs,
        .desktop-pane > .desktop-rep-kpis,
        .desktop-pane > .desktop-list-foot { flex: 0 0 auto; }

        .desktop-rep-kpis {
            display: flex; align-items: stretch;
            border-bottom: 1px solid var(--stroke);
            background: var(--surface-alt);
            overflow-x: auto;
        }
        .desktop-rep-kpis[hidden] { display: none; }
        .desktop-rep-kpi {
            display: flex; flex-direction: column; justify-content: center; gap: 2px;
            min-width: 146px; padding: 7px 14px;
            border-right: 1px solid var(--divider);
            white-space: nowrap;
        }
        .desktop-rep-kpi:last-child { border-right: 0; }
        .desktop-rep-kpi__label {
            font-size: .68rem; font-weight: 700; letter-spacing: .03em;
            text-transform: uppercase; color: var(--text-3);
        }
        .desktop-rep-kpi__value {
            font-size: .95rem; font-weight: 700; letter-spacing: -.01em;
            color: var(--text); font-variant-numeric: tabular-nums;
        }

        /* Tabla: mismo Details List del resto del ERP. */
        table.desktop-rep-table tbody td { white-space: nowrap; }
        table.desktop-rep-table th.is-num, table.desktop-rep-table td.is-num { text-align: right; font-variant-numeric: tabular-nums; }
        table.desktop-rep-table td.is-money { font-weight: 600; }
        table.desktop-rep-table td.is-negative { color: var(--danger); }

        /* Estados (carga, vacío, error, sin reporte seleccionado). */
        .desktop-rep-state { display: grid; place-items: center; padding: 46px 16px; text-align: center; }
        .desktop-rep-state[hidden] { display: none; }
        .desktop-rep-state svg { width: 26px; height: 26px; margin: 0 auto 9px; color: var(--text-3); }
        .desktop-rep-state strong { display: block; font-size: .86rem; font-weight: 600; color: var(--text); }
        .desktop-rep-state span { display: block; margin-top: 3px; font-size: .76rem; color: var(--text-3); }
        .desktop-rep-spin {
            width: 24px; height: 24px; margin: 0 auto 10px;
            border: 2.5px solid var(--stroke); border-top-color: var(--brand);
            border-radius: 50%; animation: desktopRepSpin .7s linear infinite;
        }
        @keyframes desktopRepSpin { to { transform: rotate(360deg); } }

        /* Contexto del reporte en la command bar. */
        .desktop-rep-meta { display: inline-flex; flex-direction: column; line-height: 1.25; text-align: right; font-size: .72rem; color: var(--text-3); }
        .desktop-rep-meta strong { font-size: .75rem; font-weight: 600; color: var(--text-2); }

        .desktop-rep-menu { position: relative; display: inline-flex; }

        .desktop-rep-print { display: none; }

        @media (max-width: 860px) {
            .desktop-rep-drawer__panel { width: 100%; }
            .desktop-rep-kpi { min-width: 132px; padding: 6px 12px; }
        }

        @media print {
            .app__header, .app__nav, .desktop-toolbar,
            .desktop-rep-tabs, .desktop-list-foot { display: none !important; }
            .desktop-content { padding: 0 !important; overflow: visible !important; }
            .desktop-pane { height: auto !important; border: 0 !important; box-shadow: none !important; }
            .desktop-list-wrap { overflow: visible !important; }
            .desktop-rep-print { display: block; padding: 0 0 10px; }
            .desktop-rep-print h1 { margin: 0 0 2px; font-size: 1rem; }
            .desktop-rep-print p { margin: 0; font-size: .76rem; color: var(--text-2); }
            table.desktop-rep-table { font-size: .7rem; }
            table.desktop-rep-table tbody td { white-space: normal; }
        }
    </style>
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        <div class="desktop-pivot" role="tablist" aria-label="Áreas de reportes">
            @foreach($catalogo as $key => $grupo)
                <a
                    href="{{ route('reportes.show', $grupo['reportes'][0]['slug']) }}"
                    class="desktop-btn {{ $categoriaActiva === $key ? 'desktop-btn--active' : '' }}"
                    @if($categoriaActiva === $key) aria-current="page" @endif
                >{{ $grupo['titulo'] }}</a>
            @endforeach
        </div>

        @if($reporte)
            <span class="desktop-toolbar__divider"></span>
            <button type="button" class="desktop-btn desktop-btn--primary" id="rep-consultar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                Consultar
            </button>
            <button type="button" class="desktop-btn desktop-btn--ghost" id="rep-actualizar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
                Actualizar
            </button>
            <span class="desktop-toolbar__divider"></span>
            <div class="desktop-rep-menu">
                <button type="button" class="desktop-btn desktop-btn--ghost" data-overflow aria-haspopup="true" aria-expanded="false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
                    Exportar
                </button>
                <div class="desktop-menu">
                    <button type="button" class="desktop-menu__item rep-export" data-format="xlsx">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="m9.5 12.5 5 5"/><path d="m14.5 12.5-5 5"/></svg>
                        Exportar Excel
                    </button>
                    <button type="button" class="desktop-menu__item rep-export" data-format="pdf">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M9 17v-4h1.5a1.5 1.5 0 0 1 0 3H9"/></svg>
                        Exportar PDF
                    </button>
                    <button type="button" class="desktop-menu__item rep-export" data-format="csv">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M8 13h8"/><path d="M8 17h5"/></svg>
                        Exportar CSV
                    </button>
                </div>
            </div>
            <button type="button" class="desktop-btn desktop-btn--ghost" id="rep-imprimir">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V3h12v6"/><path d="M6 18H4a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v7H6z"/></svg>
                Imprimir
            </button>
        @endif
    </div>

    @if($reporte)
        <div class="desktop-toolbar__group">
            <span class="desktop-rep-meta" id="rep-meta">
                <strong>{{ $reporte['titulo'] }}</strong>
                <span id="rep-meta-rango">Sin consultar</span>
            </span>
        </div>
    @endif
@endsection

@section('content')
    <section class="desktop-pane">
        {{-- Navegación de reportes de la categoría --}}
        <div class="desktop-rep-tabs">
            <div class="desktop-rep-tabs__scroll">
                <div class="desktop-pivot" role="tablist" aria-label="Reportes de {{ $grupoActivo['titulo'] ?? 'la categoría' }}">
                    @foreach(($grupoActivo['reportes'] ?? []) as $item)
                        <a
                            href="{{ route('reportes.show', $item['slug']) }}"
                            class="desktop-btn {{ ($reporte['slug'] ?? null) === $item['slug'] ? 'desktop-btn--active' : '' }}"
                            @if(($reporte['slug'] ?? null) === $item['slug']) aria-current="page" @endif
                        >{{ $item['titulo'] }}</a>
                    @endforeach
                </div>
            </div>

            @if($reporte)
                <button type="button" class="desktop-rep-filterbtn" id="rep-abrir-filtros" aria-haspopup="dialog" aria-expanded="false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                    Filtros
                    <span class="desktop-rep-filterbtn__badge" id="rep-filtros-badge"></span>
                </button>
            @endif
        </div>

        @if($reporte)
            {{-- Indicadores del reporte --}}
            <div class="desktop-rep-kpis" id="rep-kpis" hidden></div>
        @endif

        <div class="desktop-list-wrap">
            <div class="desktop-rep-print">
                <h1>{{ $reporte['titulo'] ?? 'Reportes' }}</h1>
                <p id="rep-print-meta"></p>
            </div>

            @if($reporte)
                <table class="desktop-list desktop-rep-table" id="rep-table" hidden>
                    <thead id="rep-headings"></thead>
                    <tbody id="rep-rows"></tbody>
                </table>
                <div class="desktop-rep-state" id="rep-state">
                    <div>
                        <div class="desktop-rep-spin"></div>
                        <strong>Consultando información</strong>
                        <span>Preparando el reporte de la sucursal activa…</span>
                    </div>
                </div>
            @else
                <div class="desktop-rep-state">
                    <div>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19V5a2 2 0 0 1 2-2h9l5 5v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/><path d="M14 3v5h5"/><path d="M8 13h6"/><path d="M8 17h4"/></svg>
                        <strong>Selecciona un reporte</strong>
                        <span>Elige un área en la barra superior y después el reporte que necesitas consultar.</span>
                    </div>
                </div>
            @endif
        </div>

        <div class="desktop-list-foot">
            <div id="rep-info">@if(!$reporte) Sin reporte seleccionado @endif</div>
            <div id="rep-pager" class="desktop-pager"></div>
        </div>
    </section>

    @if($reporte)
        {{-- Offcanvas de filtros --}}
        <aside class="desktop-rep-drawer" id="rep-drawer" aria-hidden="true" role="dialog" aria-label="Filtros del reporte">
            <div class="desktop-rep-drawer__scrim" data-close-rep-drawer></div>
            <div class="desktop-rep-drawer__panel">
                <div class="desktop-rep-drawer__head">
                    <div>
                        <div class="desktop-rep-drawer__title">Filtros</div>
                        <div class="desktop-rep-drawer__sub">{{ $reporte['titulo'] }}</div>
                    </div>
                    <button type="button" class="desktop-rep-drawer__close" data-close-rep-drawer aria-label="Cerrar">&times;</button>
                </div>

                <div class="desktop-rep-drawer__body">
                    <div class="desktop-field">
                        <label for="filter-search">Buscar en el resultado</label>
                        <input type="search" id="filter-search" placeholder="Texto en cualquier columna">
                        <small>Filtra las filas ya consultadas, sin volver a consultar.</small>
                    </div>

                    @if($mostrarPeriodo)
                        <div class="desktop-field">
                            <label for="filter-period">Periodo</label>
                            <select id="filter-period">
                                <option value="today">Hoy</option>
                                <option value="yesterday">Ayer</option>
                                <option value="before-yesterday">Antier</option>
                                <option value="week">Semana en curso</option>
                                <option value="month">Mes en curso</option>
                                <option value="year">Año en curso</option>
                                <option value="range">Rango personalizado</option>
                            </select>
                        </div>
                        <div class="desktop-rep-range" id="filter-range" hidden>
                            <div class="desktop-field">
                                <label for="filter-from">Desde</label>
                                <input type="date" id="filter-from" value="{{ now()->toDateString() }}">
                            </div>
                            <div class="desktop-field">
                                <label for="filter-to">Hasta</label>
                                <input type="date" id="filter-to" value="{{ now()->toDateString() }}">
                            </div>
                        </div>
                    @else
                        <div class="desktop-field">
                            <label>Corte</label>
                            <small>Este reporte muestra la existencia actual, no depende de un periodo.</small>
                        </div>
                    @endif

                    @if($mostrarCaja)
                        <div class="desktop-field">
                            <label for="filter-cash">Caja</label>
                            <select id="filter-cash">
                                <option value="">Todas</option>
                                @foreach($cajas as $caja)
                                    <option value="{{ $caja->caj_id }}">{{ $caja->caj_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if($mostrarAlmacen)
                        <div class="desktop-field">
                            <label for="filter-warehouse">Almacén</label>
                            <select id="filter-warehouse">
                                <option value="">Todos</option>
                                @foreach($almacenes as $almacen)
                                    <option value="{{ $almacen->alm_id }}">{{ $almacen->alm_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if($mostrarVendedor)
                        <div class="desktop-field">
                            <label for="filter-user">Vendedor</label>
                            <select id="filter-user">
                                <option value="">Todos</option>
                                @foreach($usuarios as $usuario)
                                    <option value="{{ $usuario->usr_id }}">{{ $usuario->usr_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="desktop-field-section">
                        <div class="desktop-field">
                            <label for="rep-length">Registros por página</label>
                            <select id="rep-length">
                                <option value="50">50</option>
                                <option value="100" selected>100</option>
                                <option value="250">250</option>
                                <option value="500">500</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="desktop-rep-drawer__foot">
                    <button type="button" class="desktop-btn desktop-btn--default" id="rep-limpiar-drawer">Limpiar</button>
                    <button type="button" class="desktop-btn desktop-btn--primary" id="rep-aplicar-drawer">Aplicar filtros</button>
                </div>
            </div>
        </aside>
    @endif
@endsection

@push('desktop-scripts')
    @if($reporte)
        <script>
            (function () {
                const dataUrl = @json(route('reportes.data', $reporte['slug']));
                const exportBase = @json(url('/desktop/reportes/'.$reporte['slug'].'/exportar'));
                const usaBusquedaServidor = @json($reporte['slug'] === 'ventas-cliente');

                const $state = document.getElementById('rep-state');
                const $table = document.getElementById('rep-table');
                const $headings = document.getElementById('rep-headings');
                const $body = document.getElementById('rep-rows');
                const $kpis = document.getElementById('rep-kpis');
                const $info = document.getElementById('rep-info');
                const $pager = document.getElementById('rep-pager');
                const $metaRango = document.getElementById('rep-meta-rango');
                const $printMeta = document.getElementById('rep-print-meta');

                let headers = [];
                let rows = [];
                let visibles = [];
                let page = 1;
                let size = 100;

                const esc = (valor) => String(valor ?? '—').replace(/[&<>"']/g, (c) => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
                })[c]);

                const esPorcentaje = (encabezado) => /%|participaci[oó]n|porcentaje/i.test(encabezado);
                /* Conteos que contienen palabras monetarias ("Con diferencia", "En revisión"). */
                const esConteo = (encabezado) => /^(con|en)\s/i.test(String(encabezado).trim());
                const esMoneda = (encabezado) => !esPorcentaje(encabezado) && !esConteo(encabezado)
                    && /(venta|total|importe|descuento|promedio|monto|esperado|reportado|diferencia|costo|valor|subtotal|iva|gastos|retiros|cr[eé]dito|efectivo|cobrado)/i.test(encabezado);
                const esNumero = (encabezado) => esPorcentaje(encabezado) || esConteo(encabezado)
                    || /(piezas|tickets|recepciones|documentos|existencia|m[ií]nimo|cantidad|saldo|clientes|movimientos|cortes|skus|proveedores|devoluciones|operaciones|m[eé]todos|entradas|salidas|cajas)/i.test(encabezado);

                const num = (valor, decimales) => Number(valor).toLocaleString('es-MX', {
                    minimumFractionDigits: decimales, maximumFractionDigits: decimales
                });

                function formatear(valor, encabezado) {
                    if (valor === null || valor === undefined || valor === '') return '—';
                    const numerico = typeof valor === 'number'
                        || (typeof valor === 'string' && valor.trim() !== '' && !isNaN(Number(valor)));
                    if (!numerico) return esc(valor);

                    const numero = Number(valor);
                    if (esMoneda(encabezado)) return (numero < 0 ? '-' : '') + '$' + num(Math.abs(numero), 2);
                    if (esPorcentaje(encabezado)) return num(numero, 2) + ' %';
                    if (esNumero(encabezado)) return num(numero, Number.isInteger(numero) ? 0 : 2);
                    return esc(valor);
                }

                /* El backend entrega encabezados y filas por separado; en algunos reportes
                   el orden de las columnas calculadas no coincide con el de los encabezados.
                   Cuando cada encabezado corresponde a una llave de la fila, se respeta el
                   orden de los encabezados; si no, se usa el orden natural de la fila. */
                const normalizar = (texto) => String(texto ?? '')
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                    .toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');

                let llavesPorEncabezado = null;

                function mapearColumnas(fila) {
                    llavesPorEncabezado = null;
                    if (!fila) return;
                    const llaves = Object.keys(fila);
                    if (llaves.length !== headers.length) return;
                    const indice = new Map(llaves.map((llave) => [normalizar(llave), llave]));
                    const mapeo = headers.map((encabezado) => indice.get(normalizar(encabezado)));
                    if (mapeo.every(Boolean) && new Set(mapeo).size === mapeo.length) llavesPorEncabezado = mapeo;
                }

                const valoresDe = (fila) => (llavesPorEncabezado
                    ? llavesPorEncabezado.map((llave) => fila[llave])
                    : Object.values(fila));

                function iso(fecha) {
                    const y = fecha.getFullYear();
                    const m = String(fecha.getMonth() + 1).padStart(2, '0');
                    const d = String(fecha.getDate()).padStart(2, '0');
                    return `${y}-${m}-${d}`;
                }

                function periodo() {
                    const el = document.getElementById('filter-period');
                    if (!el) return null;
                    const hoy = new Date();
                    let desde = new Date(hoy);
                    let hasta = new Date(hoy);
                    if (el.value === 'yesterday') { desde.setDate(desde.getDate() - 1); hasta = new Date(desde); }
                    else if (el.value === 'before-yesterday') { desde.setDate(desde.getDate() - 2); hasta = new Date(desde); }
                    else if (el.value === 'week') { desde.setDate(desde.getDate() - ((desde.getDay() + 6) % 7)); }
                    else if (el.value === 'month') { desde.setDate(1); }
                    else if (el.value === 'year') { desde.setMonth(0, 1); }
                    else if (el.value === 'range') {
                        return { desde: document.getElementById('filter-from').value, hasta: document.getElementById('filter-to').value };
                    }
                    return { desde: iso(desde), hasta: iso(hasta) };
                }

                function parametros() {
                    const params = periodo() || {};
                    [['caja_id', 'filter-cash'], ['almacen_id', 'filter-warehouse'], ['usuario_id', 'filter-user']].forEach(([clave, id]) => {
                        const el = document.getElementById(id);
                        if (el && el.value) params[clave] = el.value;
                    });
                    const busqueda = document.getElementById('filter-search');
                    if (usaBusquedaServidor && busqueda && busqueda.value.trim()) params.q = busqueda.value.trim();
                    return params;
                }

                function mostrarEstado(html) {
                    $state.innerHTML = '<div>' + html + '</div>';
                    $state.hidden = false;
                    $table.hidden = true;
                }

                function aplicarBusqueda() {
                    const el = document.getElementById('filter-search');
                    const termino = (el ? el.value : '').trim().toLowerCase();
                    visibles = !termino
                        ? rows
                        : rows.filter((fila) => Object.values(fila).some((valor) => String(valor ?? '').toLowerCase().includes(termino)));
                    page = 1;
                    pintar();
                }

                function pintar() {
                    const total = visibles.length;
                    const paginas = Math.max(1, Math.ceil(total / size));
                    page = Math.min(page, paginas);
                    const inicio = (page - 1) * size;
                    const bloque = visibles.slice(inicio, inicio + size);

                    if (!total) {
                        mostrarEstado('<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg><strong>Sin registros</strong><span>Ajusta los filtros o el periodo para obtener resultados.</span>');
                        $info.textContent = 'Mostrando 0 registros';
                        $pager.innerHTML = '';
                        return;
                    }

                    $state.hidden = true;
                    $table.hidden = false;

                    $headings.innerHTML = '<tr>' + headers.map((encabezado) => {
                        const clase = esMoneda(encabezado) || esNumero(encabezado) ? ' class="is-num"' : '';
                        return '<th' + clase + '>' + esc(encabezado) + '</th>';
                    }).join('') + '</tr>';

                    $body.innerHTML = bloque.map((fila) => '<tr>' + valoresDe(fila).map((valor, i) => {
                        const encabezado = headers[i] || '';
                        const clases = [];
                        if (esMoneda(encabezado)) clases.push('is-num', 'is-money');
                        else if (esNumero(encabezado)) clases.push('is-num');
                        if (Number(valor) < 0) clases.push('is-negative');
                        const clase = clases.length ? ' class="' + clases.join(' ') + '"' : '';
                        return '<td' + clase + '>' + formatear(valor, encabezado) + '</td>';
                    }).join('') + '</tr>').join('');

                    $info.textContent = 'Mostrando ' + (inicio + 1) + ' a ' + Math.min(inicio + size, total) + ' de ' + total + ' registros';

                    const botones = [{ label: '‹', page: page - 1, disabled: page === 1 }];
                    const primera = Math.max(1, Math.min(page - 2, paginas - 4));
                    for (let i = 0; i < Math.min(5, paginas); i += 1) {
                        const n = primera + i;
                        if (n <= paginas) botones.push({ label: String(n), page: n, active: n === page });
                    }
                    botones.push({ label: '›', page: page + 1, disabled: page === paginas });

                    $pager.innerHTML = botones.map((boton) => {
                        const clases = ['desktop-pager__btn', boton.active ? 'is-active' : '', boton.disabled ? 'is-disabled' : ''].filter(Boolean).join(' ');
                        return '<button type="button" class="' + clases + '" data-page="' + boton.page + '"' + (boton.disabled ? ' disabled' : '') + '>' + boton.label + '</button>';
                    }).join('');
                }

                async function consultar() {
                    mostrarEstado('<div class="desktop-rep-spin"></div><strong>Consultando información</strong><span>Preparando el reporte de la sucursal activa…</span>');
                    $kpis.hidden = true;
                    $info.textContent = '';
                    $pager.innerHTML = '';

                    try {
                        const respuesta = await fetch(dataUrl + '?' + new URLSearchParams(parametros()), { headers: { Accept: 'application/json' } });
                        const json = await respuesta.json();
                        if (!respuesta.ok) throw new Error(json.message || 'No fue posible consultar el reporte.');

                        headers = json.encabezados || [];
                        rows = json.rows || [];
                        mapearColumnas(rows[0]);

                        const kpis = Object.entries(json.kpis || {});
                        $kpis.innerHTML = kpis.map(([etiqueta, valor]) => (
                            '<div class="desktop-rep-kpi"><span class="desktop-rep-kpi__label">' + esc(etiqueta) + '</span>' +
                            '<span class="desktop-rep-kpi__value">' + formatear(valor, etiqueta) + '</span></div>'
                        )).join('');
                        $kpis.hidden = kpis.length === 0;

                        const rango = esc(json.desde) + ' al ' + esc(json.hasta);
                        if ($metaRango) $metaRango.innerHTML = esc(json.sucursal) + ' · ' + rango;
                        if ($printMeta) $printMeta.textContent = json.sucursal + ' · ' + json.desde + ' al ' + json.hasta + ' · Generado por ' + json.generado_por;

                        aplicarBusqueda();
                    } catch (error) {
                        $kpis.hidden = true;
                        mostrarEstado('<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v5"/><path d="M12 16h.01"/></svg><strong>No fue posible cargar el reporte</strong><span>' + esc(error.message) + '</span>');
                        $info.textContent = '';
                    }
                }

                const periodoEl = document.getElementById('filter-period');
                const $drawer = document.getElementById('rep-drawer');
                const $abrirFiltros = document.getElementById('rep-abrir-filtros');
                const $badge = document.getElementById('rep-filtros-badge');

                function sincronizarRango() {
                    const rango = document.getElementById('filter-range');
                    if (rango && periodoEl) rango.hidden = periodoEl.value !== 'range';
                }

                /* Filtros activos = los del offcanvas que no están en su valor por defecto. */
                function filtrosActivos() {
                    let total = 0;
                    if (periodoEl && periodoEl.value !== 'today') total += 1;
                    ['filter-cash', 'filter-warehouse', 'filter-user', 'filter-search'].forEach((id) => {
                        const el = document.getElementById(id);
                        if (el && String(el.value || '').trim() !== '') total += 1;
                    });
                    return total;
                }

                function sincronizarIndicadores() {
                    const activos = filtrosActivos();
                    if ($badge) {
                        $badge.textContent = activos ? String(activos) : '';
                        $badge.classList.toggle('is-visible', activos > 0);
                    }
                    if ($abrirFiltros) $abrirFiltros.classList.toggle('is-active', activos > 0);
                }

                function abrirDrawer() {
                    if (!$drawer) return;
                    $drawer.classList.add('is-open');
                    $drawer.setAttribute('aria-hidden', 'false');
                    if ($abrirFiltros) $abrirFiltros.setAttribute('aria-expanded', 'true');
                    const primero = $drawer.querySelector('select, input');
                    if (primero) primero.focus();
                }

                function cerrarDrawer() {
                    if (!$drawer) return;
                    $drawer.classList.remove('is-open');
                    $drawer.setAttribute('aria-hidden', 'true');
                    if ($abrirFiltros) {
                        $abrirFiltros.setAttribute('aria-expanded', 'false');
                        $abrirFiltros.focus();
                    }
                }

                if (periodoEl) {
                    periodoEl.addEventListener('change', function () {
                        sincronizarRango();
                        sincronizarIndicadores();
                    });
                }

                ['filter-cash', 'filter-warehouse', 'filter-user', 'filter-from', 'filter-to'].forEach((id) => {
                    const el = document.getElementById(id);
                    if (el) el.addEventListener('change', sincronizarIndicadores);
                });

                if ($abrirFiltros) $abrirFiltros.addEventListener('click', abrirDrawer);
                document.querySelectorAll('[data-close-rep-drawer]').forEach((el) => el.addEventListener('click', cerrarDrawer));
                document.addEventListener('keydown', function (evento) {
                    if (evento.key === 'Escape' && $drawer && $drawer.classList.contains('is-open')) cerrarDrawer();
                });

                const $aplicarDrawer = document.getElementById('rep-aplicar-drawer');
                if ($aplicarDrawer) {
                    $aplicarDrawer.addEventListener('click', function () {
                        sincronizarIndicadores();
                        cerrarDrawer();
                        consultar();
                    });
                }

                function limpiarFiltros() {
                    ['filter-cash', 'filter-warehouse', 'filter-user', 'filter-search'].forEach((id) => {
                        const el = document.getElementById(id);
                        if (el) el.value = '';
                    });
                    if (periodoEl) periodoEl.value = 'today';
                    sincronizarRango();
                    sincronizarIndicadores();
                    consultar();
                }

                const $limpiarDrawer = document.getElementById('rep-limpiar-drawer');
                if ($limpiarDrawer) $limpiarDrawer.addEventListener('click', limpiarFiltros);

                const busqueda = document.getElementById('filter-search');
                if (busqueda) busqueda.addEventListener('input', function () { aplicarBusqueda(); sincronizarIndicadores(); });

                const length = document.getElementById('rep-length');
                if (length) length.addEventListener('change', function () { size = Number(this.value) || 100; page = 1; pintar(); });

                document.getElementById('rep-consultar').addEventListener('click', consultar);
                document.getElementById('rep-actualizar').addEventListener('click', consultar);
                document.getElementById('rep-imprimir').addEventListener('click', function () { window.print(); });

                $pager.addEventListener('click', function (evento) {
                    const boton = evento.target.closest('[data-page]');
                    if (!boton || boton.disabled) return;
                    page = Number(boton.dataset.page);
                    pintar();
                });

                document.querySelectorAll('.rep-export').forEach((boton) => {
                    boton.addEventListener('click', function () {
                        window.location.href = exportBase + '/' + this.dataset.format + '?' + new URLSearchParams(parametros());
                    });
                });

                sincronizarRango();
                sincronizarIndicadores();

                size = Number(length ? length.value : 100) || 100;
                consultar();
            })();
        </script>
    @endif
@endpush
