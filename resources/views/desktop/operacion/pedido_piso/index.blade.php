@extends('layouts.desktop')

@section('title', 'Pedido de piso')

@push('desktop-styles')
    <style>
        .pp-app { height: 100%; display: flex; flex-direction: column; min-height: 0; background: var(--bg); }

        /* Barra de contexto operativa (compacta) */
        .pp-head { flex: 0 0 auto; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; padding: 6px 14px; min-height: 48px; background: var(--surface); border-bottom: 1px solid var(--stroke); }
        .pp-ctx { min-width: 0; line-height: 1.25; }
        .pp-ctx__line1 { display: flex; align-items: center; gap: 7px; font-size: .82rem; }
        .pp-ctx__dot { color: var(--text-3); }
        .pp-ctx__scl { font-weight: 700; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .pp-ctx__line2 { font-size: .78rem; color: var(--text-2); font-variant-numeric: tabular-nums; }
        .pp-badge { font-size: .66rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: var(--brand); background: var(--brand-soft); border-radius: 999px; padding: 2px 9px; }
        .pp-head__actions { display: flex; align-items: center; gap: 8px; }
        .pp-scl-wrap { position: relative; display: inline-flex; }
        .pp-scl-overlay { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; border: 0; }
        #pp-cancelar { width: 34px; padding: 0; }

        .pp-body { flex: 1 1 auto; min-height: 0; display: flex; }
        .pp-main { flex: 1 1 auto; min-width: 0; min-height: 0; display: flex; flex-direction: column; }

        /* Buscador grande */
        .pp-search { flex: 0 0 auto; position: relative; padding: 12px 14px; }
        .pp-search svg { position: absolute; left: 26px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: var(--text-3); pointer-events: none; }
        .pp-search input { width: 100%; height: 48px; padding: 0 14px 0 44px; border: 1px solid var(--stroke-strong); border-radius: var(--r-lg); background: var(--surface); font: inherit; font-size: 1rem; color: var(--text); }
        .pp-search input:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 2px var(--brand-soft); }

        .pp-results { flex: 1 1 auto; min-height: 0; overflow: auto; padding: 0 14px 14px; display: flex; flex-direction: column; gap: 8px; }
        .pp-empty { padding: 40px 16px; text-align: center; color: var(--text-2); font-size: .9rem; }

        /* Tarjeta de resultado */
        .pp-card { display: flex; align-items: center; gap: 12px; padding: 11px 13px; border: 1px solid var(--stroke); border-radius: var(--r-lg); background: var(--surface); cursor: pointer; transition: border-color .12s ease, box-shadow .12s ease; text-align: left; width: 100%; }
        .pp-card:hover { border-color: var(--brand); box-shadow: var(--shadow-2); }
        .pp-card__main { flex: 1 1 auto; min-width: 0; }
        .pp-card__name { font-weight: 600; font-size: .92rem; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .pp-card__meta { font-size: .76rem; color: var(--text-2); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-top: 1px; }
        .pp-card__price { font-size: 1rem; font-weight: 800; color: var(--brand); white-space: nowrap; font-variant-numeric: tabular-nums; }
        .pp-card__add { flex: 0 0 auto; width: 34px; height: 34px; border-radius: 50%; background: var(--brand-soft); color: var(--brand); display: inline-flex; align-items: center; justify-content: center; }

        /* Carrito (columna derecha en desktop · bottom sheet en móvil) */
        .pp-cart { flex: 0 0 380px; min-height: 0; display: flex; flex-direction: column; border-left: 1px solid var(--stroke); background: var(--surface); }
        .pp-cart__head { flex: 0 0 auto; display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; border-bottom: 1px solid var(--divider); font-size: .92rem; font-weight: 700; }
        .pp-cart__close { display: none; border: 0; background: transparent; font-size: 1.4rem; line-height: 1; color: var(--text-2); cursor: pointer; }
        .pp-cart__scroll { flex: 1 1 auto; min-height: 0; overflow: auto; padding: 12px 14px; }
        .pp-cart__foot { flex: 0 0 auto; padding: 12px 14px; border-top: 1px solid var(--stroke); display: flex; flex-direction: column; gap: 8px; }
        .pp-cart__totrow { display: flex; align-items: baseline; justify-content: space-between; }
        .pp-cart__totrow span { font-size: .8rem; font-weight: 700; text-transform: uppercase; color: var(--text-3); }
        .pp-cart__totrow b { font-size: 1.4rem; font-weight: 800; color: var(--brand); font-variant-numeric: tabular-nums; }
        .pp-cart__foot .desktop-btn { width: 100%; justify-content: center; height: 44px; font-size: .92rem; }
        .pp-client { margin-bottom: 12px; position: relative; }
        .pp-client__input { width: 100%; height: 40px; border: 1px solid var(--stroke-strong); border-radius: var(--r-md); padding: 0 42px 0 12px; background: var(--surface); font: inherit; font-size: .85rem; }
        .pp-client__input:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 2px var(--brand-soft); }
        .pp-client__clear { position: absolute; right: 8px; top: 31px; width: 26px; height: 26px; border: 0; border-radius: 999px; background: transparent; color: var(--text-3); cursor: pointer; }
        .pp-client__clear:hover { background: var(--surface-alt); color: var(--text); }
        .pp-client__current { margin-top: 7px; display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 8px 10px; border: 1px solid var(--stroke); border-radius: var(--r-md); background: var(--surface-alt); }
        .pp-client__current[hidden] { display: none; }
        .pp-client__name { font-size: .83rem; font-weight: 700; color: var(--text); }
        .pp-client__meta { font-size: .72rem; color: var(--text-2); }
        .pp-client__suggest { margin-top: 6px; border: 1px solid var(--stroke); border-radius: var(--r-md); background: var(--surface); overflow: hidden; box-shadow: var(--shadow-2); }
        .pp-client__suggest[hidden] { display: none; }
        .pp-client__option { width: 100%; border: 0; background: transparent; text-align: left; padding: 9px 10px; cursor: pointer; }
        .pp-client__option + .pp-client__option { border-top: 1px solid var(--divider); }
        .pp-client__option:hover { background: var(--surface-alt); }
        .pp-client__option-name { display: block; font-size: .82rem; font-weight: 700; color: var(--text); }
        .pp-client__option-meta { display: block; font-size: .72rem; color: var(--text-2); margin-top: 1px; }

        .pp-note { font-size: .78rem; color: var(--text-2); background: var(--surface-alt); border: 1px solid var(--stroke); border-radius: var(--r-md); padding: 8px 10px; margin-bottom: 12px; }
        .pp-note b { color: var(--text); }

        /* Grupo de almacén (= un pedido) */
        .pp-group { border: 1px solid var(--stroke); border-radius: var(--r-lg); margin-bottom: 10px; overflow: hidden; }
        .pp-group__head { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 8px 12px; background: var(--surface-alt); border-bottom: 1px solid var(--divider); }
        .pp-group__name { font-weight: 700; font-size: .85rem; display: flex; align-items: center; gap: 6px; }
        .pp-group__name svg { width: 15px; height: 15px; color: var(--text-3); }
        .pp-group__meta { font-size: .72rem; color: var(--text-2); }
        .pp-line { display: flex; align-items: flex-start; gap: 10px; padding: 8px 12px; border-bottom: 1px solid var(--divider); }
        .pp-line:last-child { border-bottom: 0; }
        .pp-line__info { flex: 1 1 auto; min-width: 0; }
        .pp-line__name { font-weight: 600; font-size: .84rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .pp-line__sku { font-size: .73rem; color: var(--text-2); }
        .pp-line__money { font-size: .71rem; color: var(--text-2); margin-top: 3px; }
        .pp-line__aside { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; }
        .pp-qty { display: inline-flex; align-items: center; gap: 0; }
        .pp-qty__btn { width: 30px; height: 30px; border: 1px solid var(--stroke-strong); background: var(--surface); color: var(--text); font-size: 1.1rem; line-height: 1; cursor: pointer; }
        .pp-qty__btn:first-child { border-radius: var(--r-sm) 0 0 var(--r-sm); }
        .pp-qty__btn:last-child { border-radius: 0 var(--r-sm) var(--r-sm) 0; }
        .pp-qty__inp { width: 52px; height: 30px; border: 1px solid var(--stroke-strong); border-left: 0; border-right: 0; text-align: center; font: inherit; font-size: .84rem; font-variant-numeric: tabular-nums; background: var(--surface); }
        .pp-line__del { width: 28px; height: 28px; border: 0; border-radius: var(--r-sm); background: transparent; color: var(--text-3); cursor: pointer; font-size: 1rem; }
        .pp-line__del:hover { background: var(--danger-soft); color: var(--danger); }
        .pp-disc { display: flex; align-items: center; gap: 6px; margin-top: 7px; }
        .pp-disc__type, .pp-disc__val { height: 30px; border: 1px solid var(--stroke-strong); border-radius: var(--r-sm); background: var(--surface); font: inherit; font-size: .75rem; color: var(--text); }
        .pp-disc__type { width: 90px; padding: 0 8px; }
        .pp-disc__val { width: 96px; padding: 0 8px; }
        .pp-disc__val:disabled, .pp-disc__type:disabled { opacity: .6; cursor: not-allowed; background: var(--surface-alt); }

        /* Barra inferior fija (móvil) */
        .pp-bar { display: none; position: sticky; bottom: 0; width: 100%; height: 54px; border: 0; background: var(--brand); color: #fff; font: inherit; font-size: .95rem; font-weight: 700; align-items: center; justify-content: center; gap: 8px; cursor: pointer; box-shadow: 0 -4px 16px rgba(0,0,0,.12); }
        .pp-bar b { font-weight: 800; }
        .pp-bar[hidden] { display: none !important; }

        /* Bottom sheet (agregar producto) */
        .pp-sheet { position: fixed; inset: 0; z-index: var(--z-modal); display: flex; align-items: flex-end; justify-content: center; }
        .pp-sheet[hidden] { display: none; }
        .pp-sheet__scrim { position: absolute; inset: 0; background: rgba(16,24,40,.34); }
        .pp-sheet__panel { position: relative; width: 100%; max-width: 560px; max-height: 88vh; display: flex; flex-direction: column; background: var(--surface); border-radius: var(--r-xl) var(--r-xl) 0 0; box-shadow: var(--shadow-16); animation: ppsheet .2s ease; }
        @keyframes ppsheet { from { transform: translateY(24px); opacity: .6; } to { transform: none; opacity: 1; } }
        .pp-sheet__head { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; padding: 16px 16px 8px; }
        .pp-sheet__title { font-size: 1rem; font-weight: 700; }
        .pp-sheet__sub { font-size: .78rem; color: var(--text-2); margin-top: 2px; }
        .pp-sheet__close { border: 0; background: transparent; font-size: 1.4rem; line-height: 1; color: var(--text-2); cursor: pointer; }
        .pp-sheet__body { flex: 1 1 auto; overflow: auto; padding: 6px 16px 12px; }
        .pp-sheet__foot { flex: 0 0 auto; padding: 12px 16px; border-top: 1px solid var(--stroke); display: flex; gap: 8px; }
        .pp-sheet__foot .desktop-btn { flex: 1 1 auto; justify-content: center; height: 46px; font-size: .92rem; }
        .pp-sheet__foot .desktop-btn:disabled { opacity: .55; cursor: not-allowed; box-shadow: none; }
        .pp-sheet__row { padding: 10px 0; border-top: 1px solid var(--divider); }
        .pp-sheet__row:first-child { border-top: 0; }
        .pp-sheet__lbl { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: var(--text-3); margin-bottom: 6px; }
        .pp-alm-opts { display: flex; flex-wrap: wrap; gap: 8px; }
        .pp-alm-opt { height: 38px; padding: 0 14px; border: 1px solid var(--stroke-strong); border-radius: 999px; background: var(--surface); font: inherit; font-size: .85rem; cursor: pointer; }
        .pp-alm-opt.is-active { background: var(--brand); border-color: var(--brand); color: #fff; font-weight: 600; }
        .pp-bigqty { display: flex; align-items: center; justify-content: center; gap: 0; }
        .pp-bigqty__btn { width: 48px; height: 48px; border: 1px solid var(--stroke-strong); background: var(--surface); font-size: 1.4rem; line-height: 1; cursor: pointer; }
        .pp-bigqty__btn:first-child { border-radius: var(--r-md) 0 0 var(--r-md); }
        .pp-bigqty__btn:last-child { border-radius: 0 var(--r-md) var(--r-md) 0; }
        .pp-bigqty__inp { width: 96px; height: 48px; border: 1px solid var(--stroke-strong); border-left: 0; border-right: 0; text-align: center; font: inherit; font-size: 1.3rem; font-weight: 700; font-variant-numeric: tabular-nums; background: var(--surface); }
        .pp-sheet__price { font-size: 1.5rem; font-weight: 800; color: var(--brand); font-variant-numeric: tabular-nums; }

        /* Tarjetas de pedido (sheet Ver pedidos) */
        .pp-ped-card { display: flex; align-items: center; gap: 10px; padding: 11px 12px; border: 1px solid var(--stroke); border-radius: var(--r-md); margin-bottom: 8px; }
        .pp-ped-card__main { flex: 1 1 auto; min-width: 0; }
        .pp-ped-card__folio { font-weight: 700; font-size: .88rem; }
        .pp-ped-card__meta { font-size: .75rem; color: var(--text-2); }
        .pp-ped-card__total { font-weight: 800; color: var(--brand); font-variant-numeric: tabular-nums; white-space: nowrap; }
        .pp-ped-state { display: inline-flex; align-items: center; height: 20px; padding: 0 8px; border-radius: 999px; font-size: .68rem; font-weight: 700; background: rgba(181,124,0,.14); color: #8a5a00; white-space: nowrap; }

        /* ===== Mobile (<1024): carrito como bottom sheet, barra inferior visible ===== */
        @media (max-width: 1024px) {
            .pp-head__scl { margin-left: 0; }
            .pp-cart {
                position: fixed; inset: 0; z-index: var(--z-drawer);
                flex: none; width: 100%; height: 100%;
                transform: translateY(100%); transition: transform .22s ease;
                border-left: 0;
            }
            .pp-cart.is-open { transform: none; }
            .pp-cart__close { display: inline-flex; }
            .pp-bar { display: flex; }
        }
    </style>
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        <span style="font-size:.9rem;font-weight:700;">Pedido de piso</span>
    </div>
@endsection

@section('content')
    <section class="pp-app">
        <header class="pp-head">
            <div class="pp-ctx">
                <div class="pp-ctx__line1">
                    <span class="pp-badge">Nuevo pedido</span>
                    <span class="pp-ctx__dot">·</span>
                    <span class="pp-ctx__scl" id="pp-ctx-scl">—</span>
                </div>
                <div class="pp-ctx__line2" id="pp-ctx-tot">0 artículos · $0.00</div>
            </div>
            <div class="pp-head__actions">
                <button type="button" class="desktop-btn desktop-btn--primary" id="pp-ver-pedidos">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01"/><path d="M3 12h.01"/><path d="M3 18h.01"/></svg>
                    Ver pedidos
                </button>
                <div class="pp-scl-wrap">
                    <button type="button" class="desktop-btn desktop-btn--default" id="pp-cambiar-scl">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9 12 3l9 6v11a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z"/></svg>
                        Sucursal
                    </button>
                    <select id="pp-scl" class="pp-scl-overlay" aria-label="Cambiar sucursal">
                        @foreach($opciones['sucursales'] as $s)
                            <option value="{{ $s->scl_id }}" @selected((int) $s->scl_id === (int) ($defaultSucursalId ?? 0))>{{ $s->scl_nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" class="desktop-btn desktop-btn--ghost" id="pp-cancelar" title="Cancelar pedido" aria-label="Cancelar pedido">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>
        </header>

        <div class="pp-body">
            <main class="pp-main">
                <div class="pp-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    <input id="pp-q" type="search" placeholder="Buscar producto, SKU o código…" autocomplete="off">
                </div>
                <div id="pp-results" class="pp-results">
                    <div class="pp-empty">Busca un producto para comenzar el pedido.</div>
                </div>
            </main>

            <aside class="pp-cart" id="pp-cart">
                <div class="pp-cart__head">
                    <span>Pedido</span>
                    <button type="button" class="pp-cart__close" id="pp-cart-close" aria-label="Cerrar">&times;</button>
                </div>
                <div class="pp-cart__scroll">
                    <div class="pp-client">
                        <div class="pp-sheet__lbl">Cliente</div>
                        <input id="pp-cli-q" type="text" class="pp-client__input" placeholder="Buscar cliente o dejar público general" autocomplete="off">
                        <button type="button" class="pp-client__clear" id="pp-cli-clear" title="Limpiar cliente" aria-label="Limpiar cliente">&times;</button>
                        <div class="pp-client__current" id="pp-cli-current" hidden>
                            <div>
                                <div class="pp-client__name" id="pp-cli-name">Público general</div>
                                <div class="pp-client__meta" id="pp-cli-meta">Cliente seleccionado</div>
                            </div>
                            <button type="button" class="desktop-btn desktop-btn--ghost" id="pp-cli-change" style="height:30px;padding:0 10px;">Cambiar</button>
                        </div>
                        <div class="pp-client__suggest" id="pp-cli-suggest" hidden></div>
                    </div>
                    <div id="pp-groups"></div>
                    <div style="margin-top:6px;">
                        <div class="pp-sheet__lbl">Observaciones</div>
                        <textarea id="pp-obs" rows="2" maxlength="1000" placeholder="Notas del pedido (opcional)" style="width:100%;border:1px solid var(--stroke-strong);border-radius:var(--r-md);padding:8px 10px;font:inherit;font-size:.85rem;"></textarea>
                    </div>
                </div>
                <div class="pp-cart__foot">
                    <div class="pp-cart__totrow"><span>Total</span><b id="pp-total">$0.00</b></div>
                    <button type="button" class="desktop-btn desktop-btn--primary" id="pp-confirm">Confirmar pedido</button>
                </div>
            </aside>
        </div>

        <button type="button" class="pp-bar" id="pp-bar" hidden>
            Ver pedido · <b id="pp-bar-count">0</b> artículos · <b id="pp-bar-total">$0.00</b>
        </button>
    </section>

    {{-- Bottom sheet: agregar producto --}}
    <div class="pp-sheet" id="pp-sheet" hidden aria-hidden="true">
        <div class="pp-sheet__scrim" data-pp-sheet-close></div>
        <div class="pp-sheet__panel">
            <div class="pp-sheet__head">
                <div>
                    <div class="pp-sheet__title" id="pp-sheet-name">Producto</div>
                    <div class="pp-sheet__sub" id="pp-sheet-sku"></div>
                </div>
                <button type="button" class="pp-sheet__close" data-pp-sheet-close aria-label="Cerrar">&times;</button>
            </div>
            <div class="pp-sheet__body">
                <div class="pp-sheet__row" style="display:flex; align-items:center; justify-content:space-between;">
                    <span class="pp-sheet__lbl" style="margin:0;">Precio</span>
                    <span class="pp-sheet__price" id="pp-sheet-price">$0.00</span>
                </div>
                <div class="pp-sheet__row" id="pp-sheet-alm-row" hidden>
                    <div class="pp-sheet__lbl">Almacén</div>
                    <div class="pp-alm-opts" id="pp-sheet-alm"></div>
                </div>
                <div class="pp-sheet__row">
                    <div class="pp-sheet__lbl">Cantidad</div>
                    <div class="pp-bigqty">
                        <button type="button" class="pp-bigqty__btn" id="pp-qty-dec">−</button>
                        <input type="number" class="pp-bigqty__inp" id="pp-qty-inp" value="1" min="1" step="1">
                        <button type="button" class="pp-bigqty__btn" id="pp-qty-inc">+</button>
                    </div>
                </div>
            </div>
            <div class="pp-sheet__foot">
                <button type="button" class="desktop-btn desktop-btn--default" data-pp-sheet-close>Cancelar</button>
                <button type="button" class="desktop-btn desktop-btn--primary" id="pp-sheet-add">Agregar al pedido</button>
            </div>
        </div>
    </div>

    {{-- Sheet: ver pedidos existentes (pendientes de cobro) --}}
    <div class="pp-sheet" id="pp-ped-sheet" hidden aria-hidden="true">
        <div class="pp-sheet__scrim" data-pp-ped-close></div>
        <div class="pp-sheet__panel">
            <div class="pp-sheet__head">
                <div>
                    <div class="pp-sheet__title">Pedidos</div>
                    <div class="pp-sheet__sub">Pendientes de cobro</div>
                </div>
                <button type="button" class="pp-sheet__close" data-pp-ped-close aria-label="Cerrar">&times;</button>
            </div>
            <div class="pp-sheet__body" id="pp-ped-list"><div class="pp-empty">Cargando…</div></div>
        </div>
    </div>
@endsection

@push('desktop-scripts')
    <script>
        (function () {
            const rutas = {
                buscar: @json(route('operacion.pedidos_piso.productos.buscar')),
                buscarClientes: @json(route('pos.clientes.buscar')),
                resolver: @json(route('operacion.pedidos_piso.productos.resolver')),
                store: @json(route('operacion.pedidos_piso.store')),
                data: @json(route('operacion.pedidos_piso.data')),
                ticket: @json(route('operacion.pedidos_piso.ticket', ['pedido' => '__ID__'])),
            };
            const csrf = @json(csrf_token());
            const usrId = @json($usuarioActual['usr_id']);

            let partidas = [];
            let sheetCtx = null;
            let buscarTimer = null;
            let clienteTimer = null;
            let clienteSeleccionado = null;
            let lineSeq = 0;

            function esc(t) { return $('<div>').text(t ?? '').html(); }
            function money(v) { return '$ ' + Number(v || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
            function notify(t, m, type) { if (window.DesktopUI) DesktopUI.message(t, m, type); }
            function roundMoney(v) { return Math.round((Number(v || 0) + Number.EPSILON) * 100) / 100; }
            function discountType(v) { return v === 'porcentaje' || v === 'importe' ? v : 'ninguno'; }
            function nextItemKey() { lineSeq += 1; return 'pp-line-' + lineSeq; }
            function lineStep(item) { return item?.permite_decimal ? 0.01 : 1; }
            function lineQtyValue(item, raw) {
                const parsed = Number(String(raw ?? '').replace(',', '.'));
                if (!Number.isFinite(parsed)) return null;
                if (item?.permite_decimal) {
                    return Number(parsed.toFixed(2));
                }
                return Math.round(parsed);
            }
            function formatQty(item, qty) {
                const num = Number(qty || 0);
                return item?.permite_decimal ? num.toFixed(2) : String(Math.round(num));
            }
            function qtyEquals(item, a, b) {
                return Math.abs(Number(a || 0) - Number(b || 0)) < (item?.permite_decimal ? 0.001 : 0.5);
            }
            function clearLineDiscount(item) {
                item.ppd_descuento_tipo = 'ninguno';
                item.ppd_descuento_valor = 0;
                item.ppd_descuento_cantidad = 0;
                return item;
            }
            function lineSubtotal(item) { return roundMoney(Number(item.cantidad || 0) * Number(item.precio || 0)); }
            function lineDiscount(item) {
                const subtotal = lineSubtotal(item);
                const tipo = discountType(item.ppd_descuento_tipo);
                const valor = roundMoney(item.ppd_descuento_valor || 0);
                if (subtotal <= 0 || tipo === 'ninguno' || valor <= 0) return 0;
                if (tipo === 'porcentaje') return roundMoney(subtotal * (Math.min(100, Math.max(0, valor)) / 100));
                return roundMoney(Math.min(subtotal, Math.max(0, valor)));
            }
            function lineTotal(item) { return roundMoney(Math.max(0, lineSubtotal(item) - lineDiscount(item))); }
            function normalizeItemDiscount(item) {
                item.ppd_descuento_tipo = discountType(item.ppd_descuento_tipo);
                if (item.ppd_descuento_tipo === 'ninguno') {
                    item.ppd_descuento_valor = 0;
                    item.ppd_descuento_cantidad = 0;
                    return item;
                }
                const subtotal = lineSubtotal(item);
                let valor = roundMoney(item.ppd_descuento_valor || 0);
                valor = Math.max(0, valor);
                if (item.ppd_descuento_tipo === 'porcentaje') valor = Math.min(100, valor);
                if (item.ppd_descuento_tipo === 'importe') valor = Math.min(subtotal, valor);
                item.ppd_descuento_valor = valor;
                item.ppd_descuento_cantidad = Number(item.ppd_descuento_cantidad || item.cantidad || 0);
                if (item.ppd_descuento_cantidad <= 0 || item.ppd_descuento_cantidad > item.cantidad) item.ppd_descuento_cantidad = Number(item.cantidad || 0);
                return item;
            }
            function renderClienteSeleccionado() {
                $('#pp-cli-name').text(clienteSeleccionado?.nombre || 'Público general');
                $('#pp-cli-meta').text(
                    clienteSeleccionado
                        ? [clienteSeleccionado.telefono || 'Sin teléfono', clienteSeleccionado.rfc || 'Sin RFC'].join(' · ')
                        : 'Venta sin cliente específico'
                );
                $('#pp-cli-current').prop('hidden', false);
                $('#pp-cli-clear').prop('hidden', !clienteSeleccionado && !String($('#pp-cli-q').val() || '').trim());
            }
            function cerrarSugerenciasCliente() { $('#pp-cli-suggest').prop('hidden', true).empty().removeData('rows'); }
            function limpiarCliente() {
                clienteSeleccionado = null;
                $('#pp-cli-q').val('');
                cerrarSugerenciasCliente();
                renderClienteSeleccionado();
            }
            function setCliente(cliente) {
                clienteSeleccionado = cliente || null;
                $('#pp-cli-q').val(clienteSeleccionado?.nombre || '');
                cerrarSugerenciasCliente();
                renderClienteSeleccionado();
            }
            function renderSugerenciasCliente(rows) {
                if (!rows.length) {
                    cerrarSugerenciasCliente();
                    return;
                }
                $('#pp-cli-suggest').data('rows', rows).html(rows.map((c, idx) =>
                    '<button type="button" class="pp-client__option" data-cli="' + idx + '">' +
                        '<span class="pp-client__option-name">' + esc(c.nombre || 'Cliente') + '</span>' +
                        '<span class="pp-client__option-meta">' + esc((c.telefono || 'Sin teléfono') + ' · ' + (c.rfc || 'Sin RFC')) + '</span>' +
                    '</button>'
                ).join('')).prop('hidden', false);
            }
            function resetPedidoCaptura() {
                partidas = [];
                $('#pp-obs').val('');
                limpiarCliente();
                renderCarrito();
            }
            async function pedirCantidadDescuento(item) {
                if (Number(item.cantidad || 0) <= lineStep(item)) {
                    return Number(item.cantidad || 0);
                }
                const respuesta = await DesktopUI.prompt({
                    title: 'Cantidad con descuento',
                    message: '¿Para cuánta cantidad aplica el descuento en este renglón?',
                    value: formatQty(item, item.ppd_descuento_cantidad || item.cantidad),
                    placeholder: formatQty(item, item.cantidad),
                    okText: 'Aplicar',
                });
                if (respuesta === null) {
                    return null;
                }
                const cantidad = lineQtyValue(item, respuesta);
                if (cantidad === null || cantidad <= 0) {
                    notify('Cantidad inválida', 'Captura una cantidad válida para aplicar el descuento.', 'error');
                    return false;
                }
                if (cantidad > Number(item.cantidad || 0)) {
                    notify('Cantidad inválida', 'La cantidad con descuento no puede ser mayor a la cantidad de la partida.', 'error');
                    return false;
                }
                return cantidad;
            }
            function insertarPartidaDespues(itemKey, nuevaPartida) {
                const idx = partidas.findIndex(x => x.itemKey === itemKey);
                if (idx < 0) partidas.push(nuevaPartida);
                else partidas.splice(idx + 1, 0, nuevaPartida);
            }

            // ===== Búsqueda =====
            $('#pp-q').on('input', function () {
                const q = String(this.value || '').trim();
                clearTimeout(buscarTimer);
                if (q.length < 2) { renderResultados([]); return; }
                buscarTimer = setTimeout(() => buscar(q), 250);
            });
            function buscar(q) {
                $('#pp-results').html('<div class="pp-empty">Buscando…</div>');
                $.getJSON(rutas.buscar, { q })
                    .done(resp => renderResultados(resp.data || []))
                    .fail(() => $('#pp-results').html('<div class="pp-empty">No fue posible buscar productos.</div>'));
            }
            function renderResultados(items) {
                const q = String($('#pp-q').val() || '').trim();
                if (!items.length) {
                    $('#pp-results').html('<div class="pp-empty">' + (q.length < 2 ? 'Busca un producto para comenzar el pedido.' : 'No encontramos productos con esa búsqueda.') + '</div>');
                    return;
                }
                $('#pp-results').html(items.map(it => {
                    const nombre = it.psk_nombre || it.producto?.prd_nombre || 'Producto';
                    const meta = [it.psk_codigo, it.producto?.marca, it.producto?.linea].filter(Boolean).join(' · ');
                    return '<button type="button" class="pp-card" data-psk=\'' + esc(JSON.stringify({ psk_id: it.psk_id, nombre, sku: it.psk_codigo || '', precio: Number(it.psk_precio || 0) })) + '\'>' +
                        '<div class="pp-card__main"><div class="pp-card__name">' + esc(nombre) + '</div><div class="pp-card__meta">' + esc(meta || '—') + '</div></div>' +
                        '<span class="pp-card__price">' + money(it.psk_precio) + '</span>' +
                        '<span class="pp-card__add"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M12 5v14M5 12h14"/></svg></span>' +
                    '</button>';
                }).join(''));
            }

            $('#pp-cli-q').on('input', function () {
                const q = String(this.value || '').trim();
                renderClienteSeleccionado();
                clearTimeout(clienteTimer);
                if (clienteSeleccionado && q === String(clienteSeleccionado.nombre || '').trim()) {
                    cerrarSugerenciasCliente();
                    return;
                }
                if (q.length < 2) {
                    cerrarSugerenciasCliente();
                    return;
                }
                clienteTimer = setTimeout(() => {
                    $.getJSON(rutas.buscarClientes, { q })
                        .done(resp => renderSugerenciasCliente(resp.data || []))
                        .fail(() => cerrarSugerenciasCliente());
                }, 250);
            });
            $('#pp-cli-clear').on('click', limpiarCliente);
            $('#pp-cli-change').on('click', function () {
                $('#pp-cli-q').val('');
                clienteSeleccionado = null;
                renderClienteSeleccionado();
                $('#pp-cli-q').trigger('focus');
            });
            $('#pp-cli-suggest').on('click', '.pp-client__option', function () {
                const rows = $('#pp-cli-suggest').data('rows') || [];
                const cliente = rows[Number($(this).data('cli'))] || null;
                if (cliente) setCliente(cliente);
            });
            $(document).on('click', function (e) {
                if (!$(e.target).closest('.pp-client').length) cerrarSugerenciasCliente();
            });

            // ===== Bottom sheet de producto =====
            function abrirSheet(prod) {
                const scl = $('#pp-scl').val();
                if (!scl) { notify('Selecciona sucursal', 'Elige la sucursal antes de agregar productos.', 'error'); return; }
                sheetCtx = { psk_id: prod.psk_id, nombre: prod.nombre, sku: prod.sku, precio: prod.precio, permite_decimal: false, pdp_alm_id: null, almacen: null, almacenes: [] };
                $('#pp-sheet-name').text(prod.nombre);
                $('#pp-sheet-sku').text(prod.sku || '');
                $('#pp-sheet-price').text(money(prod.precio));
                $('#pp-sheet-alm-row').prop('hidden', true);
                $('#pp-sheet-alm').empty();
                $('#pp-qty-inp').attr('step', '1').attr('min', '1').val(1);
                $('#pp-sheet-add').prop('disabled', true);
                $('#pp-sheet').prop('hidden', false).attr('aria-hidden', 'false');

                $.getJSON(rutas.resolver, { psk_id: prod.psk_id, pdp_scl_id: scl })
                    .done(function (resp) {
                        const d = resp.data || {};
                        sheetCtx.permite_decimal = !!d.permite_decimal;
                        const step = d.permite_decimal ? '0.01' : '1';
                        $('#pp-qty-inp').attr('step', step).attr('min', step).val(d.permite_decimal ? '1' : '1');
                        if (d.requiere_seleccion) {
                            sheetCtx.almacenes = d.almacenes || [];
                            $('#pp-sheet-alm-row').prop('hidden', false);
                            $('#pp-sheet-alm').html((d.almacenes || []).map(a =>
                                '<button type="button" class="pp-alm-opt" data-alm="' + a.alm_id + '" data-nombre="' + esc(a.alm_nombre) + '">' + esc(a.alm_nombre) + '</button>'
                            ).join(''));
                            const primerAlmacen = sheetCtx.almacenes[0] || null;
                            if (primerAlmacen) {
                                sheetCtx.pdp_alm_id = Number(primerAlmacen.alm_id);
                                sheetCtx.almacen = primerAlmacen.alm_nombre;
                                $('#pp-sheet-alm .pp-alm-opt').first().addClass('is-active');
                                $('#pp-sheet-add').prop('disabled', false);
                            }
                            // no almacén elegido aún
                        } else {
                            sheetCtx.pdp_alm_id = Number(d.pdp_alm_id);
                            sheetCtx.almacen = d.almacen || 'Almacén';
                            $('#pp-sheet-alm-row').prop('hidden', false);
                            $('#pp-sheet-alm').html('<button type="button" class="pp-alm-opt is-active" disabled>' + esc(sheetCtx.almacen) + '</button>');
                            $('#pp-sheet-add').prop('disabled', false);
                        }
                    })
                    .fail(function (xhr) {
                        notify('No disponible', xhr.responseJSON?.message || 'No hay almacén disponible para este producto en la sucursal.', 'error');
                        cerrarSheet();
                    });
            }
            function cerrarSheet() { $('#pp-sheet').prop('hidden', true).attr('aria-hidden', 'true'); sheetCtx = null; }

            $('#pp-results').on('click', '.pp-card', function () { abrirSheet(JSON.parse($(this).attr('data-psk'))); });
            $(document).on('click', '[data-pp-sheet-close]', cerrarSheet);

            $('#pp-sheet-alm').on('click', '.pp-alm-opt[data-alm]', function () {
                $('#pp-sheet-alm .pp-alm-opt').removeClass('is-active');
                $(this).addClass('is-active');
                sheetCtx.pdp_alm_id = Number($(this).data('alm'));
                sheetCtx.almacen = $(this).data('nombre');
                $('#pp-sheet-add').prop('disabled', false);
            });

            function qtyStep() { return sheetCtx?.permite_decimal ? 0.01 : 1; }
            $('#pp-qty-dec').on('click', function () { const v = Math.max(qtyStep(), Number($('#pp-qty-inp').val() || 0) - qtyStep()); $('#pp-qty-inp').val(sheetCtx?.permite_decimal ? v.toFixed(2) : Math.round(v)); });
            $('#pp-qty-inc').on('click', function () { const v = Number($('#pp-qty-inp').val() || 0) + qtyStep(); $('#pp-qty-inp').val(sheetCtx?.permite_decimal ? v.toFixed(2) : Math.round(v)); });

            $('#pp-sheet-add').on('click', function () {
                if (!sheetCtx || !sheetCtx.pdp_alm_id) { notify('Falta almacén', 'Selecciona el almacén del producto.', 'error'); return; }
                let cant = Number($('#pp-qty-inp').val() || 0);
                if (!sheetCtx.permite_decimal) cant = Math.round(cant);
                if (cant <= 0) { notify('Cantidad inválida', 'La cantidad debe ser mayor a cero.', 'error'); return; }
                agregarPartida(sheetCtx, cant);
                cerrarSheet();
            });

            function agregarPartida(ctx, cant) {
                const existente = partidas.find(p =>
                    p.pdp_alm_id === ctx.pdp_alm_id
                    && p.ppd_psk_id === Number(ctx.psk_id)
                    && Number(p.ppd_usr_id || 0) === Number(usrId || 0)
                    && Number(p.precio || 0) === Number(ctx.precio || 0)
                    && discountType(p.ppd_descuento_tipo) === 'ninguno'
                );
                if (existente) { existente.cantidad = Number((existente.cantidad + cant).toFixed(2)); }
                else {
                    partidas.push({
                        itemKey: nextItemKey(), ppd_psk_id: Number(ctx.psk_id), pdp_alm_id: ctx.pdp_alm_id, almacen: ctx.almacen,
                        ppd_usr_id: usrId || null, sku: ctx.sku, nombre: ctx.nombre,
                        cantidad: Number(cant.toFixed(2)), precio: Number(ctx.precio || 0), permite_decimal: ctx.permite_decimal,
                        ppd_descuento_tipo: 'ninguno', ppd_descuento_valor: 0, ppd_descuento_cantidad: 0,
                    });
                }
                renderCarrito();
                notify('Agregado', ctx.nombre + ' agregado al pedido.', 'success');
            }

            // ===== Carrito agrupado por almacén =====
            function grupos() {
                const map = new Map();
                partidas.forEach(p => {
                    if (!map.has(String(p.pdp_alm_id))) map.set(String(p.pdp_alm_id), { pdp_alm_id: p.pdp_alm_id, almacen: p.almacen, items: [] });
                    normalizeItemDiscount(p);
                    map.get(String(p.pdp_alm_id)).items.push(p);
                });
                return Array.from(map.values()).map(g => ({
                    ...g,
                    subtotal: roundMoney(g.items.reduce((s, i) => s + lineSubtotal(i), 0)),
                    descuento: roundMoney(g.items.reduce((s, i) => s + lineDiscount(i), 0)),
                    total: roundMoney(g.items.reduce((s, i) => s + lineTotal(i), 0)),
                    piezas: g.items.reduce((s, i) => s + i.cantidad, 0),
                }));
            }

            function renderCarrito() {
                const gs = grupos();
                const totalArt = partidas.reduce((s, i) => s + i.cantidad, 0);
                const totalDesc = roundMoney(gs.reduce((s, g) => s + g.descuento, 0));
                const totalGen = roundMoney(gs.reduce((s, g) => s + g.total, 0));

                $('#pp-total').text(money(totalGen));
                $('#pp-bar-count').text(totalArt.toLocaleString('es-MX'));
                $('#pp-bar-total').text(money(totalGen));
                $('#pp-ctx-tot').text(totalArt.toLocaleString('es-MX') + ' artículos · ' + money(totalGen));
                $('#pp-bar').prop('hidden', partidas.length === 0);
                $('#pp-confirm').prop('disabled', partidas.length === 0);
                renderClienteSeleccionado();

                if (!partidas.length) {
                    $('#pp-groups').html('<div class="pp-empty">Agrega productos para armar el pedido.</div>');
                    return;
                }

                const aviso = gs.length > 1
                    ? '<div class="pp-note">Este pedido se generará en <b>' + gs.length + ' almacenes</b> → se crearán <b>' + gs.length + ' pedidos separados</b>.</div>'
                    : '';

                $('#pp-groups').html(aviso + gs.map(g =>
                    '<div class="pp-group">' +
                        '<div class="pp-group__head">' +
                            '<span class="pp-group__name"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9 12 3l9 6v11a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z"/></svg>' + esc(g.almacen) + '</span>' +
                            '<span class="pp-group__meta">' + g.items.length + ' producto(s) · ' + money(g.subtotal) + '</span>' +
                        '</div>' +
                        g.items.map(i =>
                            '<div class="pp-line">' +
                                '<div class="pp-line__info"><div class="pp-line__name">' + esc(i.nombre) + '</div><div class="pp-line__sku">' + esc(i.sku || '') + ' · ' + money(i.precio) + '</div></div>' +
                                '<div class="pp-qty"><button type="button" class="pp-qty__btn" data-dec="' + esc(i.itemKey) + '">−</button>' +
                                    '<input type="number" class="pp-qty__inp" data-key="' + esc(i.itemKey) + '" value="' + i.cantidad + '" min="' + (i.permite_decimal ? '0.01' : '1') + '" step="' + (i.permite_decimal ? '0.01' : '1') + '">' +
                                    '<button type="button" class="pp-qty__btn" data-inc="' + esc(i.itemKey) + '">+</button></div>' +
                                '<button type="button" class="pp-line__del" data-del="' + esc(i.itemKey) + '" title="Quitar">&times;</button>' +
                            '</div>'
                        ).join('') +
                    '</div>'
                ).join(''));
                $('#pp-groups .pp-group').each(function (groupIndex) {
                    const grupo = gs[groupIndex];
                    if (!grupo) return;
                    $(this).find('.pp-group__meta').text(
                        grupo.items.length + ' producto(s) · ' + money(grupo.total) + (grupo.descuento > 0 ? ' · desc. ' + money(grupo.descuento) : '')
                    );
                });
                $('#pp-groups .pp-line').each(function () {
                    const key = String($(this).find('.pp-qty__inp').data('key') || '');
                    const item = partidas.find(x => x.itemKey === key);
                    if (!item) return;
                    $(this).find('.pp-line__info').append(
                        '<div class="pp-disc">' +
                            '<select class="pp-disc__type" data-disc-type="' + esc(item.itemKey) + '">' +
                                '<option value="ninguno"' + (discountType(item.ppd_descuento_tipo) === 'ninguno' ? ' selected' : '') + '>Sin desc.</option>' +
                                '<option value="porcentaje"' + (discountType(item.ppd_descuento_tipo) === 'porcentaje' ? ' selected' : '') + '>%</option>' +
                                '<option value="importe"' + (discountType(item.ppd_descuento_tipo) === 'importe' ? ' selected' : '') + '>$ fijo</option>' +
                            '</select>' +
                            '<input type="text" inputmode="decimal" class="pp-disc__val" data-disc-val="' + esc(item.itemKey) + '" value="' + Number(item.ppd_descuento_valor || 0) + '" ' + (discountType(item.ppd_descuento_tipo) === 'ninguno' ? 'disabled' : '') + '>' +
                        '</div>' +
                        '<div class="pp-line__money">Subtotal ' + money(lineSubtotal(item)) + (lineDiscount(item) > 0 ? ' · Desc. ' + money(lineDiscount(item)) + ' · Total ' + money(lineTotal(item)) : '') + '</div>'
                    );
                    $(this).append('<div class="pp-line__aside"><strong style="font-size:.84rem;color:var(--brand);">' + money(lineTotal(item)) + '</strong></div>');
                });
            }

            function setCant(key, cant) {
                const p = partidas.find(x => x.itemKey === key);
                if (!p) return;
                let v = Number(cant || 0);
                if (!p.permite_decimal) v = Math.round(v);
                if (v <= 0) { partidas = partidas.filter(x => x.itemKey !== key); }
                else {
                    const previa = Number(p.cantidad || 0);
                    p.cantidad = Number(v.toFixed(2));
                    if (discountType(p.ppd_descuento_tipo) !== 'ninguno') {
                        if (qtyEquals(p, Number(p.ppd_descuento_cantidad || 0), previa) || Number(p.ppd_descuento_cantidad || 0) <= 0) {
                            p.ppd_descuento_cantidad = p.cantidad;
                        } else if (Number(p.ppd_descuento_cantidad || 0) > p.cantidad) {
                            p.ppd_descuento_cantidad = p.cantidad;
                        }
                    } else {
                        p.ppd_descuento_cantidad = 0;
                    }
                    normalizeItemDiscount(p);
                }
                renderCarrito();
            }
            async function setDiscount(key, tipo, valor, forcePrompt = false) {
                const p = partidas.find(x => x.itemKey === key);
                if (!p) return;
                const nuevoTipo = discountType(tipo);
                if (nuevoTipo === 'ninguno') {
                    clearLineDiscount(p);
                    renderCarrito();
                    return;
                }
                const valorNormalizado = Number(String(valor ?? 0).replace(',', '.'));
                if (!Number.isFinite(valorNormalizado) || valorNormalizado < 0) {
                    notify('Descuento inválido', 'Captura un valor de descuento válido.', 'error');
                    return;
                }

                let cantidadAplicada = Number(p.ppd_descuento_cantidad || 0);
                if (forcePrompt || cantidadAplicada <= 0) {
                    const respuestaCantidad = await pedirCantidadDescuento(p);
                    if (respuestaCantidad === null) {
                        renderCarrito();
                        return;
                    }
                    if (respuestaCantidad === false) {
                        return;
                    }
                    cantidadAplicada = respuestaCantidad;
                }

                if (cantidadAplicada <= 0 || cantidadAplicada > Number(p.cantidad || 0)) {
                    notify('Cantidad inválida', 'La cantidad con descuento no es válida para esta partida.', 'error');
                    return;
                }

                if (qtyEquals(p, cantidadAplicada, p.cantidad)) {
                    p.ppd_descuento_tipo = nuevoTipo;
                    p.ppd_descuento_valor = valorNormalizado;
                    p.ppd_descuento_cantidad = p.cantidad;
                    normalizeItemDiscount(p);
                    renderCarrito();
                    return;
                }

                const restante = lineQtyValue(p, Number(p.cantidad || 0) - cantidadAplicada);
                if (restante === null || restante <= 0) {
                    notify('Cantidad inválida', 'No es posible generar una partida restante con cantidad cero.', 'error');
                    return;
                }

                const nuevaPartida = {
                    ...p,
                    itemKey: nextItemKey(),
                    cantidad: cantidadAplicada,
                    ppd_descuento_tipo: nuevoTipo,
                    ppd_descuento_valor: valorNormalizado,
                    ppd_descuento_cantidad: cantidadAplicada,
                };

                p.cantidad = restante;
                clearLineDiscount(p);
                normalizeItemDiscount(nuevaPartida);
                insertarPartidaDespues(p.itemKey, nuevaPartida);
                normalizeItemDiscount(p);
                renderCarrito();
            }
            function setDiscountDraft(key, tipo, valor) {
                const p = partidas.find(x => x.itemKey === key);
                if (!p) return;
                p.ppd_descuento_tipo = discountType(tipo);
                p.ppd_descuento_valor = valor;
            }
            $('#pp-groups').on('click', '[data-dec]', function () { const p = partidas.find(x => x.itemKey === $(this).data('dec')); if (p) setCant(p.itemKey, p.cantidad - (p.permite_decimal ? 0.01 : 1)); });
            $('#pp-groups').on('click', '[data-inc]', function () { const p = partidas.find(x => x.itemKey === $(this).data('inc')); if (p) setCant(p.itemKey, p.cantidad + (p.permite_decimal ? 0.01 : 1)); });
            $('#pp-groups').on('input', '.pp-qty__inp', function () { setCant(String($(this).data('key')), this.value); });
            $('#pp-groups').on('change', '[data-disc-type]', async function () {
                const key = String($(this).data('disc-type'));
                const item = partidas.find(x => x.itemKey === key);
                const pedirCantidad = $(this).val() !== 'ninguno' && discountType(item?.ppd_descuento_tipo) === 'ninguno';
                await setDiscount(key, $(this).val(), $(this).val() === 'ninguno' ? 0 : $('[data-disc-val="' + key + '"]').val(), pedirCantidad);
            });
            $('#pp-groups').on('input', '.pp-disc__val', function () {
                const key = String($(this).data('disc-val'));
                setDiscountDraft(key, $('[data-disc-type="' + key + '"]').val(), this.value);
            });
            $('#pp-groups').on('blur', '.pp-disc__val', async function () {
                const key = String($(this).data('disc-val'));
                await setDiscount(key, $('[data-disc-type="' + key + '"]').val(), this.value);
            });
            $('#pp-groups').on('click', '[data-del]', function () { partidas = partidas.filter(x => x.itemKey !== $(this).data('del')); renderCarrito(); });

            // ===== Carrito móvil (bottom sheet) =====
            $('#pp-bar').on('click', () => $('#pp-cart').addClass('is-open'));
            $('#pp-cart-close').on('click', () => $('#pp-cart').removeClass('is-open'));

            // ===== Cancelar =====
            $('#pp-cancelar').on('click', async function () {
                if (!partidas.length) return;
                const ok = await DesktopUI.confirm({ title: 'Cancelar pedido', message: 'Se descartarán los productos capturados. ¿Continuar?', okText: 'Descartar', danger: true });
                if (!ok) return;
                resetPedidoCaptura(); $('#pp-cart').removeClass('is-open');
            });

            // ===== Confirmar (un pedido por almacén) =====
            function postGrupo(grupo, obs) {
                return $.ajax({
                    url: rutas.store, method: 'POST', headers: { 'X-CSRF-TOKEN': csrf },
                    data: {
                        pdp_scl_id: $('#pp-scl').val(),
                        pdp_alm_id: grupo.pdp_alm_id,
                        pdp_cli_id: clienteSeleccionado?.cli_id || null,
                        pdp_observaciones: obs || null,
                        partidas: grupo.items.map(i => ({
                            ppd_psk_id: i.ppd_psk_id,
                            ppd_cantidad: i.cantidad,
                            ppd_usr_id: i.ppd_usr_id,
                            ppd_descuento_tipo: discountType(i.ppd_descuento_tipo),
                            ppd_descuento_valor: Number(i.ppd_descuento_valor || 0),
                            ppd_descuento_cantidad: discountType(i.ppd_descuento_tipo) === 'ninguno' ? 0 : Number(i.ppd_descuento_cantidad || i.cantidad || 0),
                        })),
                    },
                });
            }
            $('#pp-confirm').on('click', async function () {
                if (!partidas.length) { notify('Pedido vacío', 'Agrega productos antes de confirmar.', 'error'); return; }
                if (!$('#pp-scl').val()) { notify('Falta sucursal', 'Selecciona la sucursal del pedido.', 'error'); return; }
                const gs = grupos();
                const ok = await DesktopUI.confirm({
                    title: 'Confirmar pedido',
                    message: gs.length > 1
                        ? ('Se generarán ' + gs.length + ' pedidos (uno por almacén). ¿Confirmar?')
                        : 'Se generará el pedido. ¿Confirmar?',
                    okText: 'Confirmar pedido',
                });
                if (!ok) return;

                const obs = $('#pp-obs').val().trim();
                const $btn = $('#pp-confirm').prop('disabled', true);
                const folios = [];
                try {
                    for (const g of gs) {
                        const resp = await postGrupo(g, obs);
                        folios.push((g.almacen || 'Almacén') + ': ' + (resp.data?.pdp_folio || ''));
                    }
                    notify('Pedidos generados', folios.join(' · '), 'success');
                    resetPedidoCaptura(); $('#pp-q').val(''); renderResultados([]); $('#pp-cart').removeClass('is-open');
                } catch (xhr) {
                    notify('Error', xhr.responseJSON?.message || 'No fue posible generar el pedido. Revisa lo capturado.', 'error');
                } finally {
                    $btn.prop('disabled', false);
                }
            });

            // ===== Contexto: sucursal actual =====
            function actualizarCtx() { $('#pp-ctx-scl').text($('#pp-scl option:selected').text() || '—'); }
            let sclPrevio = $('#pp-scl').val();
            $('#pp-scl').on('change', async function () {
                if (partidas.length) {
                    const ok = await DesktopUI.confirm({
                        title: 'Cambiar sucursal',
                        message: 'Los productos se resuelven por almacén de la sucursal actual. Cambiar de sucursal vaciará el pedido en curso. ¿Continuar?',
                        okText: 'Cambiar y vaciar', danger: true,
                    });
                    if (!ok) { $('#pp-scl').val(sclPrevio); return; }
                    resetPedidoCaptura();
                }
                sclPrevio = $('#pp-scl').val();
                actualizarCtx();
            });

            // ===== Ver pedidos (sheet) =====
            function estadoBadge(e) { return '<span class="pp-ped-state">' + esc(e || 'pendiente') + '</span>'; }
            $('#pp-ver-pedidos').on('click', function () {
                $('#pp-ped-list').html('<div class="pp-empty">Cargando…</div>');
                $('#pp-ped-sheet').prop('hidden', false).attr('aria-hidden', 'false');
                $.getJSON(rutas.data, { pdp_scl_id: $('#pp-scl').val() })
                    .done(function (resp) {
                        const rows = resp.data || [];
                        if (!rows.length) { $('#pp-ped-list').html('<div class="pp-empty">Aún no hay pedidos pendientes de cobro.</div>'); return; }
                        $('#pp-ped-list').html(rows.map(r =>
                            '<div class="pp-ped-card">' +
                                '<div class="pp-ped-card__main">' +
                                    '<div class="pp-ped-card__folio">' + esc(r.pdp_folio) + '</div>' +
                                    '<div class="pp-ped-card__meta">' + esc(r.almacen || r.sucursal || '—') + ' · ' + esc(r.vendedor || '') + '</div>' +
                                '</div>' +
                                estadoBadge(r.pdp_estatus) +
                                '<span class="pp-ped-card__total">' + money(r.pdp_total) + '</span>' +
                                '<a class="desktop-btn desktop-btn--ghost" style="height:30px;padding:0 8px;" href="' + rutas.ticket.replace('__ID__', r.pdp_id) + '" target="_blank" title="Ver ticket">' +
                                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M4 4h16v16l-2-1-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M8 8h8M8 12h8"/></svg></a>' +
                            '</div>'
                        ).join(''));
                    })
                    .fail(() => $('#pp-ped-list').html('<div class="pp-empty">No fue posible cargar los pedidos.</div>'));
            });
            $(document).on('click', '[data-pp-ped-close]', () => $('#pp-ped-sheet').prop('hidden', true).attr('aria-hidden', 'true'));

            // Init
            actualizarCtx();
            renderCarrito();
            $('#pp-q').trigger('focus');
        })();
    </script>
@endpush
