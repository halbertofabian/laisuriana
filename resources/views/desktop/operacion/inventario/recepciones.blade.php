@extends('layouts.desktop')

@section('title', 'Recepciones capturadas')

@push('desktop-vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
@endpush

@push('desktop-styles')
    <style>
        .desktop-rme-bar {
            display: flex; align-items: center; gap: 8px;
            padding: 6px 12px;
            border-bottom: 1px solid var(--stroke);
            background: var(--surface-alt);
        }
        .desktop-rme-bar__search { position: relative; flex: 1 1 320px; max-width: 420px; display: flex; align-items: center; }
        .desktop-rme-bar__search svg { position: absolute; left: 9px; width: 15px; height: 15px; color: var(--text-3); pointer-events: none; }
        .desktop-rme-bar__search input {
            width: 100%; height: 32px; padding: 0 10px 0 30px;
            border: 1px solid var(--stroke-strong); border-radius: var(--r-md);
            background: var(--surface); color: var(--text); font: inherit; font-size: .82rem;
        }
        .desktop-rme-bar__search input:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 1px var(--brand); }
        .desktop-rme-bar__field { display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; }
        .desktop-rme-bar__cap { font-size: .68rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: var(--text-3); }
        .desktop-rme-bar__field .desktop-toolbar__select,
        .desktop-rme-bar__field input { height: 32px; max-width: 170px; }
        .desktop-rme-bar .desktop-btn { height: 32px; }
        .desktop-rme-clear {
            border: 0;
            background: transparent;
            color: var(--brand);
            font: inherit;
            font-size: .78rem;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            padding: 0 4px;
        }
        .desktop-rme-clear:hover { text-decoration: underline; }
        .desktop-rme-clear[hidden] { display: none; }
        .desktop-rme-filterbtn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 32px;
            padding: 0 11px;
            border: 1px solid var(--stroke-strong);
            border-radius: var(--r-md);
            background: var(--surface);
            color: var(--text);
            font: inherit;
            font-size: .8rem;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
        }
        .desktop-rme-filterbtn svg { width: 15px; height: 15px; }
        .desktop-rme-filterbtn:hover { background: var(--surface-sunken); }
        .desktop-rme-filterbtn.is-active { border-color: var(--brand); color: var(--brand); }
        .desktop-rme-filterbtn__badge {
            display: none;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 999px;
            background: var(--brand);
            color: var(--on-brand);
            font-size: .68rem;
            font-weight: 700;
            line-height: 1;
        }
        .desktop-rme-filterbtn__badge.is-visible { display: inline-flex; }
        .desktop-rme-period-range[hidden] { display: none; }
        .desktop-rme-period-range { display: grid; gap: 12px; }
        .desktop-rme-bar__spacer { flex: 1 1 auto; }
        .desktop-rme-bar__divider { width: 1px; height: 22px; background: var(--stroke); }
        .desktop-rme-drawer { position: fixed; inset: 0; z-index: var(--z-drawer); display: none; }
        .desktop-rme-drawer.is-open { display: block; }
        .desktop-rme-drawer__scrim { position: absolute; inset: 0; background: rgba(15, 23, 42, .16); backdrop-filter: blur(2px); }
        .desktop-rme-drawer__panel {
            position: absolute; top: 0; right: 0; height: 100%; width: min(420px, 100%);
            display: flex; flex-direction: column; background: var(--surface); border-left: 1px solid var(--stroke);
            box-shadow: var(--shadow-16); animation: desktoprmedrawer .18s ease;
        }
        @keyframes desktoprmedrawer { from { transform: translateX(20px); opacity: .5; } to { transform: none; opacity: 1; } }
        .desktop-rme-drawer__head {
            display: flex; align-items: center; justify-content: space-between; gap: 8px;
            padding: 14px 16px; border-bottom: 1px solid var(--stroke);
        }
        .desktop-rme-drawer__title { font-size: .95rem; font-weight: 600; letter-spacing: -.01em; }
        .desktop-rme-drawer__close {
            display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px;
            border: 0; border-radius: var(--r-md); background: transparent; color: var(--text-2);
            font-size: 1.2rem; line-height: 1; cursor: pointer;
        }
        .desktop-rme-drawer__close:hover { background: var(--surface-sunken); color: var(--text); }
        .desktop-rme-drawer__body {
            flex: 1 1 auto; overflow: auto; padding: 14px 16px; display: grid; gap: 12px; align-content: start;
        }
        .desktop-rme-drawer__body .desktop-field input,
        .desktop-rme-drawer__body .desktop-field select { min-height: 34px; }
        .desktop-rme-drawer__foot {
            display: flex; align-items: center; justify-content: space-between; gap: 8px;
            padding: 12px 16px; border-top: 1px solid var(--stroke);
        }

        .desktop-rme-table tbody td { padding-top: 5px !important; padding-bottom: 5px !important; vertical-align: middle; }
        .desktop-rme-table tbody tr.is-selected td { background: #eaf1fd; }
        .desktop-rme-meta { display: flex; flex-direction: column; gap: 1px; line-height: 1.2; min-width: 0; }
        .desktop-rme-meta__title { font-weight: 600; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .desktop-rme-meta__sub { font-size: .74rem; color: var(--text-2); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .desktop-rme-meta--wrap .desktop-rme-meta__title,
        .desktop-rme-meta--wrap .desktop-rme-meta__sub { white-space: normal; overflow: visible; text-overflow: clip; }
        .desktop-rme-num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; font-weight: 600; }

        .desktop-rme-state {
            display: inline-flex; align-items: center; gap: 6px;
            height: 22px; padding: 0 10px; border-radius: 999px;
            font-size: .74rem; font-weight: 700; white-space: nowrap;
        }
        .desktop-rme-state::before { content: ""; width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
        .desktop-rme-state--finalizado { background: rgba(17,121,80,.12); color: var(--success); }
        .desktop-rme-state--borrador { background: rgba(181,124,0,.14); color: #8a5a00; }
        .desktop-rme-state--cancelado { background: rgba(193,42,46,.12); color: var(--danger); }

        /* Detalle (modal) */
        .desktop-rme-detail__grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-bottom: 14px; }
        .desktop-rme-detail__cell label { display: block; font-size: .67rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: var(--text-3); margin-bottom: 2px; }
        .desktop-rme-detail__cell div { font-size: .84rem; font-weight: 600; color: var(--text); }
        .desktop-rme-lines { width: 100%; border-collapse: collapse; font-size: .82rem; }
        .desktop-rme-lines th { text-align: left; padding: 6px 8px; border-bottom: 1px solid var(--stroke); font-size: .7rem; font-weight: 700; text-transform: uppercase; color: var(--text-3); }
        .desktop-rme-lines td { padding: 6px 8px; border-bottom: 1px solid var(--divider); }
        .desktop-rme-lines td.num { text-align: right; font-variant-numeric: tabular-nums; }
        .desktop-rme-detail__obs { margin-bottom: 14px; }
        .desktop-rme-detail__obs label { display: block; font-size: .67rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: var(--text-3); margin-bottom: 3px; }
        .desktop-rme-detail__obs div { font-size: .82rem; color: var(--text); background: var(--surface-alt); border: 1px solid var(--stroke); border-radius: var(--r-md); padding: 8px 10px; white-space: pre-wrap; }
        .desktop-rme-detail__foot { display: flex; justify-content: space-between; gap: 24px; flex-wrap: wrap; margin-top: 12px; padding-top: 10px; border-top: 1px solid var(--stroke); }
        .desktop-rme-detail__totals { display: flex; gap: 20px; flex-wrap: wrap; align-content: flex-start; }
        .desktop-rme-detail__total b { font-size: 1rem; font-variant-numeric: tabular-nums; }
        .desktop-rme-detail__total span { display: block; font-size: .68rem; font-weight: 700; text-transform: uppercase; color: var(--text-3); }
        .desktop-rme-money { min-width: 220px; margin-left: auto; }
        .desktop-rme-money__row { display: flex; align-items: baseline; justify-content: space-between; gap: 24px; padding: 3px 0; font-size: .82rem; color: var(--text-2); }
        .desktop-rme-money__row b { color: var(--text); font-variant-numeric: tabular-nums; font-weight: 600; }
        .desktop-rme-money__row.is-grand { border-top: 1px solid var(--stroke); margin-top: 4px; padding-top: 6px; font-size: .92rem; }
        .desktop-rme-money__row.is-grand span { font-weight: 700; color: var(--text); }
        .desktop-rme-money__row.is-grand b { color: var(--brand); font-weight: 800; font-size: 1.05rem; }

        @media (max-width: 1100px) {
            .desktop-rme-bar { flex-wrap: wrap; }
            .desktop-rme-bar__search { flex: 1 1 100%; max-width: none; order: -1; }
            .desktop-rme-bar__spacer { display: none; }
        }
        @media (max-width: 860px) {
            .desktop-rme-drawer__panel { width: 100%; }
        }
    </style>
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        @php $activeSubmenu = 'recepciones'; @endphp
        @include('desktop.operacion.inventario._subnav')
        <span class="desktop-toolbar__divider"></span>
        <a href="{{ route('desktop.operacion.inventario.recibir.index') }}" class="desktop-btn desktop-btn--primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            Recibir mercancía
        </a>
        <button type="button" class="desktop-btn desktop-btn--ghost" id="btn-recargar-rme">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
            Actualizar
        </button>
    </div>
@endsection

@section('content')
    <section class="desktop-pane">
        <div class="desktop-rme-bar">
            <div class="desktop-rme-bar__search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <input id="flt-buscar" type="search" placeholder="Buscar folio, referencia, proveedor o marca">
            </div>
            <div class="desktop-rme-bar__field">
                <select class="desktop-toolbar__select" id="flt-length">
                    <option value="25">25 por página</option>
                    <option value="50">50 por página</option>
                    <option value="100" selected>100 por página</option>
                    <option value="250">250 por página</option>
                </select>
            </div>

            <span class="desktop-rme-bar__spacer"></span>
            <button type="button" class="desktop-rme-clear" id="btn-limpiar" hidden>Limpiar</button>
            <button type="button" class="desktop-rme-filterbtn" id="btn-rme-filtros" aria-haspopup="dialog" aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                Filtros
                <span class="desktop-rme-filterbtn__badge" id="rme-filtros-badge"></span>
            </button>
            <span class="desktop-rme-bar__divider"></span>
            <button type="button" class="desktop-btn desktop-btn--primary" id="btn-filtrar">Aplicar</button>
        </div>

        <div class="desktop-list-wrap">
            <table class="desktop-list desktop-rme-table" id="tbl-recepciones">
                <thead>
                    <tr>
                        <th>Fecha captura</th>
                        <th>Folio</th>
                        <th>Estado</th>
                        <th>Marcas</th>
                        <th>Sucursal / Almacén</th>
                        <th>Proveedor</th>
                        <th style="text-align:right;">Líneas</th>
                        <th style="text-align:right;">Artículos</th>
                        <th style="text-align:right;">Importe</th>
                        <th>Capturó</th>
                        <th style="width:56px; text-align:right;">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>

        <div class="desktop-list-foot">
            <div id="desktop-rme-info"></div>
            <div id="desktop-rme-pagination" class="desktop-pager"></div>
        </div>
    </section>

    <aside class="desktop-rme-drawer" id="desktop-rme-drawer" aria-hidden="true" role="dialog" aria-label="Filtros avanzados">
        <div class="desktop-rme-drawer__scrim" data-close-rme-drawer></div>
        <div class="desktop-rme-drawer__panel">
            <div class="desktop-rme-drawer__head">
                <div class="desktop-rme-drawer__title">Filtros avanzados</div>
                <button type="button" class="desktop-rme-drawer__close" data-close-rme-drawer aria-label="Cerrar">&times;</button>
            </div>
            <div class="desktop-rme-drawer__body">
                <div class="desktop-field">
                    <label for="flt-periodo">Periodo</label>
                    <select id="flt-periodo">
                        <option value="hoy">Hoy</option>
                        <option value="ayer">Ayer</option>
                        <option value="antier">Antier</option>
                        <option value="semana_en_curso" selected>Semana en curso</option>
                        <option value="mes_en_curso">Mes en curso</option>
                        <option value="mes_anterior">Mes anterior</option>
                        <option value="ultimos_3_meses">Últimos 3 meses</option>
                        <option value="este_ano">Este año</option>
                        <option value="rango_personalizado">Rango personalizado</option>
                    </select>
                </div>
                <div class="desktop-rme-period-range" id="flt-periodo-rango" hidden>
                    <div class="desktop-field">
                        <label for="flt-desde">Desde</label>
                        <input type="date" id="flt-desde">
                    </div>
                    <div class="desktop-field">
                        <label for="flt-hasta">Hasta</label>
                        <input type="date" id="flt-hasta">
                    </div>
                </div>
                <div class="desktop-field">
                    <label for="flt-estado">Estado</label>
                    <select id="flt-estado">
                        <option value="">Todos</option>
                        <option value="finalizado">Finalizado</option>
                        <option value="borrador">Borrador</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                </div>
                <div class="desktop-field">
                    <label for="flt-marca">Marca</label>
                    <select id="flt-marca">
                        <option value="">Todas</option>
                        @foreach(($opciones['marcas'] ?? []) as $marca)
                            <option value="{{ $marca->mrc_id }}">{{ $marca->mrc_nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="desktop-rme-drawer__foot">
                <button type="button" class="desktop-btn desktop-btn--default" id="btn-limpiar-drawer">Limpiar</button>
                <button type="button" class="desktop-btn desktop-btn--primary" id="btn-aplicar-drawer">Aplicar filtros</button>
            </div>
        </div>
    </aside>

    <div class="desktop-modal" id="rme-modal" aria-hidden="true">
        <div class="desktop-modal__dialog" style="max-width:720px;">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="rme-modal-title">Detalle de recepción</div>
                <button type="button" class="desktop-modal__close" data-close-rme aria-label="Cerrar">&times;</button>
            </div>
            <div class="desktop-modal__body" id="rme-modal-body">
                <div style="padding:24px; text-align:center; color:var(--text-2);">Cargando…</div>
            </div>
            <div class="desktop-modal__foot">
                <button type="button" class="desktop-btn desktop-btn--default" data-close-rme>Cerrar</button>
                <a href="#" target="_blank" class="desktop-btn desktop-btn--primary" id="rme-modal-pdf">Ver PDF</a>
                <a href="#" target="_blank" class="desktop-btn desktop-btn--default" id="rme-modal-termico">Reporte termico</a>
                <button type="button" class="desktop-btn desktop-btn--default" id="rme-modal-imprimir-directo">Imprimir compacto</button>
                <button type="button" class="desktop-btn desktop-btn--default" id="rme-modal-imprimir-horizontal">Imprimir horizontal</button>
            </div>
        </div>
    </div>
@endsection

@push('desktop-vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endpush

@push('desktop-scripts')
    <script>
        (function () {
            const rutas = {
                data: @json(route('desktop.operacion.inventario.recepciones.data')),
                show: @json(url('/desktop/operacion/inventario/recepciones/__ID__')),
                pdf: @json(url('/desktop/operacion/inventario/recepciones/__ID__/reporte-pdf')),
                termico: @json(url('/desktop/operacion/inventario/recepciones/__ID__/reporte-termico')),
                imprimirDirecto:     @json(url('/desktop/operacion/inventario/recepciones/__ID__/imprimir-termico-directo')),
                imprimirHorizontal: @json(url('/desktop/operacion/inventario/recepciones/__ID__/imprimir-horizontal')),
                cancelar: @json(url('/desktop/operacion/inventario/recepciones/__ID__/cancelar')),
                recibir: @json(route('desktop.operacion.inventario.recibir.index')),
            };
            const csrf = @json(csrf_token());
            const ICONS = { dots: '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>' };

            const $table = $('#tbl-recepciones');
            let tabla = null;

            function escapeHtml(text) { return $('<div>').text(text ?? '').html(); }
            function money(v) { return Number(v || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
            function num(v) { return Number(v || 0).toLocaleString('es-MX'); }
            function fecha(v) { return v ? String(v).replace('T', ' ').slice(0, 16) : '—'; }
            function urlFor(tpl, id) { return tpl.replace('__ID__', encodeURIComponent(String(id))); }

            function renderMeta(title, subtitle) {
                return '<div class="desktop-rme-meta"><span class="desktop-rme-meta__title">' + escapeHtml(title || '—') + '</span>' +
                    (subtitle ? '<span class="desktop-rme-meta__sub">' + escapeHtml(subtitle) + '</span>' : '') + '</div>';
            }
            function renderMetaWrap(title, subtitle) {
                return '<div class="desktop-rme-meta desktop-rme-meta--wrap"><span class="desktop-rme-meta__title">' + escapeHtml(title || '—') + '</span>' +
                    (subtitle ? '<span class="desktop-rme-meta__sub">' + escapeHtml(subtitle) + '</span>' : '') + '</div>';
            }
            function formatDateValue(date) {
                const y = date.getFullYear();
                const m = String(date.getMonth() + 1).padStart(2, '0');
                const d = String(date.getDate()).padStart(2, '0');
                return y + '-' + m + '-' + d;
            }
            function startOfDay(date) {
                const d = new Date(date);
                d.setHours(0, 0, 0, 0);
                return d;
            }
            function addDays(date, days) {
                const d = new Date(date);
                d.setDate(d.getDate() + days);
                return d;
            }
            function addMonths(date, months) {
                const d = new Date(date);
                d.setMonth(d.getMonth() + months);
                return d;
            }
            function resolverPeriodo(periodo) {
                const today = startOfDay(new Date());
                switch (periodo) {
                    case 'hoy':
                        return { desde: formatDateValue(today), hasta: formatDateValue(today) };
                    case 'ayer': {
                        const d = addDays(today, -1);
                        return { desde: formatDateValue(d), hasta: formatDateValue(d) };
                    }
                    case 'antier': {
                        const d = addDays(today, -2);
                        return { desde: formatDateValue(d), hasta: formatDateValue(d) };
                    }
                    case 'semana_en_curso': {
                        const day = today.getDay();
                        const diff = day === 0 ? -6 : 1 - day;
                        const desde = addDays(today, diff);
                        return { desde: formatDateValue(desde), hasta: formatDateValue(addDays(desde, 6)) };
                    }
                    case 'mes_en_curso': {
                        const desde = new Date(today.getFullYear(), today.getMonth(), 1);
                        const hasta = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                        return { desde: formatDateValue(desde), hasta: formatDateValue(hasta) };
                    }
                    case 'mes_anterior': {
                        const desde = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                        const hasta = new Date(today.getFullYear(), today.getMonth(), 0);
                        return { desde: formatDateValue(desde), hasta: formatDateValue(hasta) };
                    }
                    case 'ultimos_3_meses':
                        return { desde: formatDateValue(addMonths(today, -3)), hasta: formatDateValue(today) };
                    case 'este_ano':
                        return { desde: formatDateValue(new Date(today.getFullYear(), 0, 1)), hasta: formatDateValue(today) };
                    default:
                        return { desde: $('#flt-desde').val(), hasta: $('#flt-hasta').val() };
                }
            }
            function syncPeriodoUI() {
                $('#flt-periodo-rango').prop('hidden', $('#flt-periodo').val() !== 'rango_personalizado');
            }
            function getFechasFiltro() {
                if ($('#flt-periodo').val() === 'rango_personalizado') {
                    return { desde: $('#flt-desde').val(), hasta: $('#flt-hasta').val() };
                }
                return resolverPeriodo($('#flt-periodo').val());
            }
            function contarFiltrosAvanzados() {
                let count = 0;
                if ($('#flt-estado').val()) count += 1;
                if ($('#flt-marca').val()) count += 1;
                if ($('#flt-periodo').val() !== 'semana_en_curso') count += 1;
                if ($('#flt-periodo').val() === 'rango_personalizado' && ($('#flt-desde').val() || $('#flt-hasta').val())) count += 1;
                return count;
            }
            function hayFiltroActivo() {
                return contarFiltrosAvanzados() > 0 || !!$('#flt-buscar').val().trim();
            }
            function syncBadge() {
                const count = contarFiltrosAvanzados();
                $('#rme-filtros-badge').text(count ? String(count) : '').toggleClass('is-visible', count > 0);
                $('#btn-rme-filtros').toggleClass('is-active', count > 0);
                $('#btn-limpiar').prop('hidden', !hayFiltroActivo());
            }
            function renderEstado(v) {
                const estado = String(v || '').toLowerCase();
                const label = estado ? estado.charAt(0).toUpperCase() + estado.slice(1) : '—';
                return '<span class="desktop-rme-state desktop-rme-state--' + escapeHtml(estado) + '">' + escapeHtml(label) + '</span>';
            }
            function renderAcciones(row) {
                const id = row.rme_id;
                const estado = String(row.rme_estado || '').toLowerCase();
                let items;
                if (estado === 'borrador') {
                    // Un borrador se retoma o se descarta (aún no tiene movimientos)
                    items = '<button type="button" class="desktop-menu__item" data-act="continuar" data-id="' + id + '">Continuar captura</button>' +
                        '<div class="desktop-menu__divider"></div>' +
                        '<button type="button" class="desktop-menu__item desktop-menu__item--danger" data-act="cancelar" data-id="' + id + '">Descartar borrador</button>';
                } else {
                    items = '<button type="button" class="desktop-menu__item" data-act="ver" data-id="' + id + '">Ver detalle</button>' +
                        '<div class="desktop-menu__divider"></div>' +
                        '<button type="button" class="desktop-menu__item" data-act="imprimir-directo" data-id="' + id + '">Imprimir compacto</button>' +
                        '<button type="button" class="desktop-menu__item" data-act="imprimir-horizontal" data-id="' + id + '">Imprimir horizontal</button>' +
                        '<div class="desktop-menu__divider"></div>' +
                        '<a class="desktop-menu__item" href="' + urlFor(rutas.pdf, id) + '" target="_blank">Ver PDF A4</a>' +
                        '<a class="desktop-menu__item" href="' + urlFor(rutas.termico, id) + '" target="_blank">Reporte termico 80 mm</a>';
                }
                return '<div class="desktop-rowmenu">' +
                    '<button type="button" class="desktop-overflow" data-overflow aria-haspopup="true" aria-expanded="false" aria-label="Más acciones">' + ICONS.dots + '</button>' +
                    '<div class="desktop-menu" role="menu">' + items + '</div>' +
                '</div>';
            }

            function renderFooter() {
                if (!tabla) return;
                const info = tabla.page.info();
                const total = info.recordsDisplay;
                if (!total) {
                    $('#desktop-rme-info').text('Mostrando 0 registros');
                    $('#desktop-rme-pagination').empty();
                    return;
                }
                $('#desktop-rme-info').text('Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + total + ' registros');
                const buttons = [];
                const current = info.page, totalPages = info.pages;
                buttons.push({ label: '‹', page: 'previous', disabled: current === 0 });
                for (let i = 0; i < totalPages; i += 1) buttons.push({ label: String(i + 1), page: i, active: i === current });
                buttons.push({ label: '›', page: 'next', disabled: current >= totalPages - 1 });
                $('#desktop-rme-pagination').html(buttons.map(function (b) {
                    const cls = ['desktop-pager__btn', b.active ? 'is-active' : '', b.disabled ? 'is-disabled' : ''].filter(Boolean).join(' ');
                    return '<button type="button" class="' + cls + '" data-page="' + b.page + '"' + (b.disabled ? ' disabled' : '') + '>' + b.label + '</button>';
                }).join(''));
            }

            function buildTabla() {
                tabla = $table.DataTable({
                    processing: true,
                    serverSide: true,
                    pageLength: Number($('#flt-length').val() || 100),
                    lengthChange: false,
                    searching: false,
                    ordering: true,
                    info: false,
                    autoWidth: false,
                    responsive: false,
                    order: [[0, 'desc']],
                    dom: 'rt',
                    language: { processing: 'Cargando...', emptyTable: 'No hay recepciones capturadas', zeroRecords: 'No se encontraron coincidencias' },
                    ajax: {
                        url: rutas.data,
                        data: function (d) {
                            const fechas = getFechasFiltro();
                            d.estado = $('#flt-estado').val();
                            d.marca_id = $('#flt-marca').val();
                            d.fecha_desde = fechas.desde;
                            d.fecha_hasta = fechas.hasta;
                            d.buscar = $('#flt-buscar').val().trim();
                        }
                    },
                    columns: [
                        { data: 'rme_fecha_captura', render: function (v, _, row) { return renderMeta(fecha(v), row.rme_documento_referencia || ''); } },
                        { data: 'rme_folio', render: function (v) { return renderMeta(v || '—'); } },
                        { data: 'rme_estado', render: renderEstado },
                        { data: 'marcas_nombres', orderable: false, render: function (v) { return renderMetaWrap(v || '—'); } },
                        { data: null, orderable: false, render: function (_, __, row) { return renderMeta(row.sucursal_nombre || '—', row.almacen_nombre || ''); } },
                        { data: 'proveedor_nombre', orderable: false, render: function (v) { return renderMeta(v || '—'); } },
                        { data: 'total_lineas', orderable: false, className: 'desktop-rme-num', render: num },
                        { data: 'total_articulos', orderable: false, className: 'desktop-rme-num', render: function (v) { return money(v); } },
                        { data: 'total_importe', orderable: false, className: 'desktop-rme-num', render: function (v) { return '$ ' + money(v); } },
                        { data: 'usuario_creo', orderable: false, render: function (v) { return renderMeta(v || '—'); } },
                        { data: null, orderable: false, className: 'dt-actions', render: function (_, __, row) { return renderAcciones(row); } },
                    ],
                    initComplete: renderFooter,
                    drawCallback: renderFooter
                });
            }

            function recargar(reset) {
                if (!tabla) { buildTabla(); return; }
                tabla.ajax.reload(null, !!reset);
            }
            function limpiarFiltros() {
                $('#flt-estado').val('');
                $('#flt-marca').val('');
                $('#flt-periodo').val('semana_en_curso');
                $('#flt-desde').val('');
                $('#flt-hasta').val('');
                syncPeriodoUI();
                $('#flt-buscar').val('');
                syncBadge();
                recargar(true);
            }
            function abrirDrawer() {
                $('#desktop-rme-drawer').addClass('is-open').attr('aria-hidden', 'false');
                $('#btn-rme-filtros').attr('aria-expanded', 'true');
            }
            function cerrarDrawer() {
                $('#desktop-rme-drawer').removeClass('is-open').attr('aria-hidden', 'true');
                $('#btn-rme-filtros').attr('aria-expanded', 'false');
            }

            // ===== Modal de detalle =====
            function abrirModal() { $('#rme-modal').addClass('is-open').attr('aria-hidden', 'false'); }
            function cerrarModal() { $('#rme-modal').removeClass('is-open').attr('aria-hidden', 'true'); }
            let recepcionActualId = 0;

            function verDetalle(id) {
                abrirModal();
                recepcionActualId = Number(id || 0);
                $('#rme-modal-pdf').attr('href', urlFor(rutas.pdf, id));
                $('#rme-modal-termico').attr('href', urlFor(rutas.termico, id));
                $('#rme-modal-body').html('<div style="padding:24px; text-align:center; color:var(--text-2);">Cargando…</div>');
                $.getJSON(urlFor(rutas.show, id)).done(function (resp) {
                    const d = resp.data || {};
                    const r = d.resumen || {};
                    $('#rme-modal-title').text('Recepción ' + (d.rme_folio || ''));
                    const tipo = String(d.min_documento_tipo || '');
                    const esFactura = tipo === 'compra_factura';
                    const esRemision = tipo === 'compra_remision';
                    const obs = String(d.min_observaciones || '').trim();
                    const cab = '<div class="desktop-rme-detail__grid">' +
                        '<div class="desktop-rme-detail__cell"><label>Estado</label><div>' + renderEstado(d.rme_estado) + '</div></div>' +
                        '<div class="desktop-rme-detail__cell"><label>Sucursal</label><div>' + escapeHtml(r.sucursal_nombre || '—') + '</div></div>' +
                        '<div class="desktop-rme-detail__cell"><label>Almacén</label><div>' + escapeHtml(r.almacen_nombre || '—') + '</div></div>' +
                        '<div class="desktop-rme-detail__cell"><label>Proveedor</label><div>' + escapeHtml(r.proveedor_nombre || '—') + '</div></div>' +
                        '<div class="desktop-rme-detail__cell"><label>Referencia</label><div>' + escapeHtml(d.min_documento_referencia || '—') + '</div></div>' +
                        '<div class="desktop-rme-detail__cell"><label>Fecha</label><div>' + escapeHtml(fecha(d.min_fecha_movimiento)) + '</div></div>' +
                    '</div>' +
                    (obs ? '<div class="desktop-rme-detail__obs"><label>Observaciones</label><div>' + escapeHtml(obs) + '</div></div>' : '');
                    const filas = (d.lineas || []).map(function (l) {
                        return '<tr><td>' + escapeHtml(l.sku_codigo || '—') + '</td>' +
                            '<td>' + escapeHtml(l.producto_nombre || l.sku_nombre || '—') + '</td>' +
                            '<td class="num">' + money(l.min_cantidad) + '</td>' +
                            '<td class="num">$ ' + money(l.min_precio_unitario) + '</td>' +
                            '<td class="num">$ ' + money((l.min_cantidad || 0) * (l.min_precio_unitario || 0)) + '</td></tr>';
                    }).join('') || '<tr><td colspan="5" style="text-align:center; color:var(--text-2); padding:16px;">Sin líneas</td></tr>';
                    const tabla = '<table class="desktop-rme-lines"><thead><tr><th>SKU</th><th>Producto</th><th style="text-align:right;">Cant.</th><th style="text-align:right;">Precio</th><th style="text-align:right;">Importe</th></tr></thead><tbody>' + filas + '</tbody></table>';

                    // Desglose monetario por tipo
                    const subtotal = Number(r.total_importe || 0);
                    const dt = d.min_descuento_tipo || 'ninguno', dv = Number(d.min_descuento_valor || 0);
                    const desc = dt === 'importe' ? dv : (dt === 'porcentaje' ? subtotal * Math.min(dv, 100) / 100 : 0);
                    const flete = Number(d.min_flete_total || 0);
                    const base = Math.max(0, subtotal - desc) + flete;
                    const ivaPct = Number(d.min_iva_porcentaje || 0);
                    const iva = esFactura ? base * ivaPct / 100 : 0;
                    const total = base + iva;
                    const linea = (lbl, val, grand) => '<div class="desktop-rme-money__row' + (grand ? ' is-grand' : '') + '"><span>' + lbl + '</span><b>$ ' + money(val) + '</b></div>';
                    let money_rows = '';
                    if (esRemision) {
                        money_rows = linea('Descuento', desc) + linea('Flete', flete) + linea('Total', total, true);
                    } else {
                        money_rows = linea('Subtotal', subtotal) + linea('Descuento', desc) + linea('Flete', flete) +
                            (esFactura ? linea('IVA (' + ivaPct + '%)', iva) : '') + linea('Total', total, true);
                    }
                    const totales = '<div class="desktop-rme-detail__foot">' +
                        '<div class="desktop-rme-detail__totals">' +
                            '<div class="desktop-rme-detail__total"><span>Líneas</span><b>' + num(r.total_lineas) + '</b></div>' +
                            '<div class="desktop-rme-detail__total"><span>Artículos</span><b>' + money(r.total_articulos) + '</b></div>' +
                        '</div>' +
                        '<div class="desktop-rme-money">' + money_rows + '</div>' +
                    '</div>';
                    $('#rme-modal-body').html(cab + tabla + totales);
                }).fail(function () {
                    $('#rme-modal-body').html('<div style="padding:24px; text-align:center; color:var(--danger);">No fue posible cargar el detalle.</div>');
                });
            }

            function notify(title, msg, type) {
                if (window.DesktopUI) DesktopUI.message(title, msg, type);
                else if (window.AppUI?.showMessage) window.AppUI.showMessage(title, msg, type);
            }

            function continuar(id) {
                window.location.href = rutas.recibir + '?rme_id=' + encodeURIComponent(String(id));
            }

            function imprimirDirecto(id) {
                $.ajax({
                    url: urlFor(rutas.imprimirDirecto, id),
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf },
                }).done(function (resp) {
                    notify('Impresion enviada', resp.message || 'Se envio el ticket compacto a la impresora termica.', 'success');
                }).fail(function (xhr) {
                    notify('Error', xhr.responseJSON?.message || 'No fue posible imprimir directamente.', 'error');
                });
            }

            function imprimirHorizontal(id) {
                $.ajax({
                    url: urlFor(rutas.imprimirHorizontal, id),
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf },
                }).done(function (resp) {
                    notify('Impresion enviada', resp.message || 'Se envio el ticket horizontal a la impresora termica.', 'success');
                }).fail(function (xhr) {
                    notify('Error', xhr.responseJSON?.message || 'No fue posible imprimir el ticket horizontal.', 'error');
                });
            }

            async function cancelar(id) {
                const ok = await DesktopUI.confirm({
                    title: 'Descartar borrador',
                    message: 'Se descartará el borrador seleccionado. Esta acción no se puede deshacer.',
                    okText: 'Descartar borrador',
                    danger: true,
                });
                if (!ok) return;
                $.ajax({
                    url: urlFor(rutas.cancelar, id),
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf },
                    data: { motivo: '' }
                }).done(function (resp) {
                    notify('Listo', resp.message || 'Borrador descartado.', 'success');
                    recargar(false);
                }).fail(function (xhr) {
                    notify('Error', xhr.responseJSON?.message || 'No fue posible descartar el borrador.', 'error');
                });
            }

            $('#btn-filtrar, #btn-recargar-rme').on('click', function () { recargar(true); });
            $('#flt-periodo').on('change', function () {
                syncPeriodoUI();
                syncBadge();
            });
            $('#flt-estado, #flt-desde, #flt-hasta').on('change', syncBadge);
            $('#flt-buscar').on('input', syncBadge);
            $('#flt-buscar').on('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); recargar(true); } });
            $('#flt-length').on('change', function () { if (tabla) tabla.page.len(Number(this.value || 100)).draw(false); });
            $('#btn-rme-filtros').on('click', abrirDrawer);
            $(document).on('click', '[data-close-rme-drawer]', cerrarDrawer);
            $('#btn-limpiar, #btn-limpiar-drawer').on('click', function () {
                limpiarFiltros();
            });
            $('#btn-aplicar-drawer').on('click', function () {
                syncBadge();
                cerrarDrawer();
                recargar(true);
            });

            $('#desktop-rme-pagination').on('click', '.desktop-pager__btn', function () {
                if (!tabla || this.disabled) return;
                const page = $(this).data('page');
                tabla.page(page === 'previous' || page === 'next' ? page : Number(page)).draw('page');
            });

            $table.on('click', '[data-act="ver"]', function () { verDetalle($(this).data('id')); });
            $table.on('click', '[data-act="imprimir-directo"]', function () { imprimirDirecto($(this).data('id')); });
            $table.on('click', '[data-act="imprimir-horizontal"]', function () { imprimirHorizontal($(this).data('id')); });
            $table.on('click', '[data-act="continuar"]', function () { continuar($(this).data('id')); });
            $table.on('click', '[data-act="cancelar"]', function () { cancelar($(this).data('id')); });
            $('#rme-modal-imprimir-directo').on('click', function () { if (recepcionActualId > 0) imprimirDirecto(recepcionActualId); });
            $('#rme-modal-imprimir-horizontal').on('click', function () { if (recepcionActualId > 0) imprimirHorizontal(recepcionActualId); });
            $(document).on('click', '[data-close-rme]', cerrarModal);
            $('#rme-modal').on('click', function (e) { if (e.target === this) cerrarModal(); });
            $(document).on('keydown', function (e) { if (e.key === 'Escape') cerrarModal(); });
            syncPeriodoUI();
            syncBadge();
            buildTabla();
        })();
    </script>
@endpush
