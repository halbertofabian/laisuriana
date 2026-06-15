@extends('layouts.desktop')

@section('title', 'Salidas')

@push('desktop-vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/select2/select2.css') }}" />
@endpush

@push('desktop-styles')
    <style>
        .desktop-sal-scroll { flex: 1 1 auto; min-height: 0; overflow: auto; padding: 12px; display: flex; flex-direction: column; gap: 12px; }
        .desktop-sal-card { border: 1px solid var(--stroke); border-radius: var(--r-md); background: var(--surface); box-shadow: var(--shadow-2); }
        .desktop-sal-card__head { display: flex; align-items: center; gap: 10px; padding: 9px 12px; border-bottom: 1px solid var(--divider); }
        .desktop-sal-card__title { font-size: .88rem; font-weight: 700; }
        .desktop-sal-card__hint { margin-left: auto; font-size: .73rem; color: var(--text-3); }
        .desktop-sal-card__body { padding: 12px; }
        .desktop-sal-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px 12px; }
        .desktop-field--full { grid-column: 1 / -1; }

        /* Escaneo */
        .desktop-sal-scanrow { display: grid; grid-template-columns: 1fr minmax(220px, 1.2fr) auto; gap: 10px; align-items: end; }
        .desktop-sal-scan input {
            width: 100%; height: 40px; padding: 0 12px;
            border: 2px solid var(--brand); border-radius: var(--r-md);
            font: inherit; font-size: 1rem; background: var(--surface); color: var(--text);
        }
        .desktop-sal-scan input:focus { outline: none; box-shadow: 0 0 0 2px var(--brand-soft); }
        .desktop-sal-scan__hint { font-size: .72rem; color: var(--text-3); margin-top: 4px; }

        /* Tarjeta de disponibilidad */
        .desktop-sal-avail { display: none; align-items: center; gap: 12px; margin-top: 10px; padding: 10px 14px; border: 1px solid var(--stroke); border-left: 4px solid var(--text-3); border-radius: var(--r-md); background: var(--surface-alt); }
        .desktop-sal-avail.is-on { display: flex; }
        .desktop-sal-avail__val { font-size: 1.5rem; font-weight: 800; font-variant-numeric: tabular-nums; line-height: 1; }
        .desktop-sal-avail__lbl { font-size: .72rem; color: var(--text-2); }
        .desktop-sal-avail__last { font-size: .74rem; color: var(--text-3); margin-top: 2px; }
        .desktop-sal-avail--ok { border-left-color: var(--success); }
        .desktop-sal-avail--ok .desktop-sal-avail__val { color: var(--success); }
        .desktop-sal-avail--low { border-left-color: #b57c00; }
        .desktop-sal-avail--low .desktop-sal-avail__val { color: #8a5a00; }
        .desktop-sal-avail--zero { border-left-color: var(--danger); }
        .desktop-sal-avail--zero .desktop-sal-avail__val { color: var(--danger); }

        /* Tabla de líneas */
        .desktop-sal-lines { width: 100%; border-collapse: collapse; font-size: .82rem; }
        .desktop-sal-lines th { text-align: left; padding: 7px 10px; border-bottom: 1px solid var(--stroke); font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .02em; color: var(--text-3); white-space: nowrap; }
        .desktop-sal-lines td { padding: 5px 10px; border-bottom: 1px solid var(--divider); vertical-align: middle; }
        .desktop-sal-lines td.num { text-align: right; font-variant-numeric: tabular-nums; }
        .desktop-sal-lines__name { font-weight: 600; color: var(--text); }
        .desktop-sal-lines__sub { font-size: .73rem; color: var(--text-2); }
        .desktop-sal-lines input { width: 84px; height: 30px; padding: 0 8px; text-align: right; border: 1px solid var(--stroke-strong); border-radius: var(--r-sm); font: inherit; font-size: .82rem; font-variant-numeric: tabular-nums; background: var(--surface); }
        .desktop-sal-lines input:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 1px var(--brand); }
        .desktop-sal-lines__del { width: 28px; height: 28px; border: 0; border-radius: var(--r-sm); background: transparent; color: var(--text-3); cursor: pointer; font-size: 1.1rem; line-height: 1; }
        .desktop-sal-lines__del:hover { background: var(--danger-soft); color: var(--danger); }
        .desktop-sal-empty { padding: 24px; text-align: center; color: var(--text-2); font-size: .82rem; }

        /* Pie */
        .desktop-sal-foot { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; padding: 10px 12px; border-top: 1px solid var(--stroke); background: var(--surface); }
        .desktop-sal-resumen { font-size: .85rem; color: var(--text-2); font-weight: 600; }
        .desktop-sal-resumen b { color: var(--text); }

        .select2-container { width: 100% !important; }
        .select2-container--default .select2-selection--single { min-height: 36px; border: 1px solid var(--stroke-strong); border-radius: var(--r-md); }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 34px; font-size: .84rem; padding-left: 10px; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 34px; }
        /* Dropdown sólido (se adjunta al body): fondo, borde, sombra y z-index correctos */
        .select2-container--open .select2-dropdown,
        .select2-dropdown {
            background: var(--surface);
            border: 1px solid var(--stroke);
            border-radius: var(--r-md);
            box-shadow: var(--shadow-16);
            overflow: hidden;
            z-index: var(--z-dropdown);
        }
        .select2-dropdown .select2-results__options { background: var(--surface); }
        .select2-dropdown .select2-results__option { font-size: .84rem; color: var(--text); padding: 7px 12px; }
        .select2-dropdown .select2-results__option--highlighted { background: var(--brand) !important; color: var(--on-brand) !important; }
        .select2-search--dropdown { padding: 8px; }
        .select2-search--dropdown .select2-search__field { border: 1px solid var(--stroke-strong); border-radius: var(--r-sm); padding: 6px 8px; }
    </style>
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        @php $activeSubmenu = 'salidas'; @endphp
        @include('desktop.operacion.inventario._subnav')
        <span class="desktop-toolbar__divider"></span>
        <a href="{{ route('desktop.operacion.inventario.salidas.index') }}" class="desktop-btn desktop-btn--default">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Salidas
        </a>
    </div>
@endsection

@section('content')
    <section class="desktop-pane">
        <div class="desktop-sal-scroll">
            {{-- Datos de la salida --}}
            <div class="desktop-sal-card">
                <div class="desktop-sal-card__head"><span class="desktop-sal-card__title">Datos de la salida</span></div>
                <div class="desktop-sal-card__body">
                    <div class="desktop-sal-grid">
                        <div class="desktop-field">
                            <label for="sal-scl">Sucursal *</label>
                            <select id="sal-scl">
                                <option value="">Selecciona…</option>
                                @foreach($opciones['sucursales'] as $s)
                                    <option value="{{ $s->scl_id }}" @selected((int) $s->scl_id === (int) ($defaultSucursalId ?? 0))>{{ $s->scl_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="desktop-field">
                            <label for="sal-alm">Almacén *</label>
                            <select id="sal-alm">
                                <option value="">Selecciona…</option>
                                @foreach($opciones['almacenes'] as $a)
                                    <option value="{{ $a->alm_id }}" data-scl="{{ $a->alm_scl_id }}">{{ $a->alm_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="desktop-field">
                            <label for="sal-tipo">Tipo de salida *</label>
                            <select id="sal-tipo">
                                <option value="ajuste_manual">Ajuste manual</option>
                                <option value="merma">Merma</option>
                            </select>
                        </div>
                        <div class="desktop-field">
                            <label for="sal-fecha">Fecha *</label>
                            <input type="datetime-local" id="sal-fecha">
                        </div>
                        <div class="desktop-field">
                            <label for="sal-mtv">Motivo (catálogo)</label>
                            <select id="sal-mtv">
                                <option value="">Selecciona…</option>
                                @foreach($opciones['motivos'] as $m)
                                    <option value="{{ $m->mtv_id }}">{{ $m->mtv_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="desktop-field">
                            <label for="sal-ref">Referencia (opcional)</label>
                            <input type="text" id="sal-ref" maxlength="120">
                        </div>
                        <div class="desktop-field desktop-field--full">
                            <label for="sal-motivo">Motivo *</label>
                            <input type="text" id="sal-motivo" maxlength="500" placeholder="Describe la razón de la salida">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Escaneo / agregar --}}
            <div class="desktop-sal-card">
                <div class="desktop-sal-card__head">
                    <span class="desktop-sal-card__title">Escanear / agregar productos</span>
                    <span class="desktop-sal-card__hint">Enter agrega · pensado para lector de código de barras</span>
                </div>
                <div class="desktop-sal-card__body">
                    <div class="desktop-sal-scanrow">
                        <div class="desktop-field desktop-sal-scan">
                            <label for="sal-scan">Escaneo principal</label>
                            <input type="text" id="sal-scan" placeholder="Escanea el código y presiona Enter" autocomplete="off" inputmode="search">
                        </div>
                        <div class="desktop-field">
                            <label for="sal-sku">Agregar manualmente</label>
                            <select id="sal-sku"></select>
                        </div>
                        <button type="button" class="desktop-btn desktop-btn--default" id="sal-add" style="height:38px;">Agregar</button>
                    </div>
                    <div class="desktop-sal-scan__hint">El foco vuelve al escáner automáticamente tras cada lectura.</div>
                    <div class="desktop-sal-avail" id="sal-avail">
                        <div>
                            <div class="desktop-sal-avail__val" id="sal-avail-val">0</div>
                            <div class="desktop-sal-avail__lbl">Existencia disponible en almacén</div>
                            <div class="desktop-sal-avail__last" id="sal-avail-last">Sin lecturas todavía.</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Líneas --}}
            <div class="desktop-sal-card">
                <div class="desktop-sal-card__head"><span class="desktop-sal-card__title">Productos a dar salida</span></div>
                <div class="desktop-sal-card__body" style="padding:0;">
                    <table class="desktop-sal-lines">
                        <thead>
                            <tr>
                                <th>Código / SKU</th>
                                <th>Producto</th>
                                <th>Variante</th>
                                <th>Unidad</th>
                                <th style="text-align:right;">Disponible</th>
                                <th style="text-align:right;">Cantidad</th>
                                <th style="width:44px;"></th>
                            </tr>
                        </thead>
                        <tbody id="sal-lines-body">
                            <tr><td colspan="7" class="desktop-sal-empty">Escanea o agrega productos para armar la salida.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="desktop-sal-foot">
            <div class="desktop-sal-resumen" id="sal-resumen"><b>0</b> productos · <b>0</b> piezas</div>
            <button type="button" class="desktop-btn desktop-btn--danger" id="sal-confirmar">Confirmar salida</button>
        </div>
    </section>
@endsection

@push('desktop-vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/select2/select2.js') }}"></script>
@endpush

@push('desktop-scripts')
    <script>
        (function () {
            const rutas = {
                resolver: @json(route('operacion.inventario_base.salidas.resolver')),
                store: @json(route('operacion.inventario_base.salidas.store')),
                skus: @json(route('operacion.inventario_base.skus.buscar')),
                listado: @json(route('desktop.operacion.inventario.salidas.index')),
            };
            const csrf = @json(csrf_token());
            const lineas = {}; // psk_id -> { min_psk_id, codigo, productoNombre, productoCodigo, variante, unidad, existencia, cantidad }
            let enviando = false;

            function esc(t) { return $('<div>').text(t ?? '').html(); }
            function notify(title, msg, type) { if (window.DesktopUI) DesktopUI.message(title, msg, type); }
            function foco(ms) { setTimeout(() => $('#sal-scan').trigger('focus'), ms || 20); }

            function syncAlmacenes() {
                const scl = String($('#sal-scl').val() || '');
                const actual = String($('#sal-alm').val() || '');
                $('#sal-alm option').each(function () {
                    if (!this.value) return;
                    $(this).prop('hidden', scl && String($(this).data('scl')) !== scl);
                });
                if (actual && $('#sal-alm option[value="' + actual + '"]:not([hidden])').length === 0) $('#sal-alm').val('');
            }

            function contextoOk() {
                if (!$('#sal-scl').val() || !$('#sal-alm').val()) {
                    notify('Faltan datos', 'Selecciona sucursal y almacén antes de escanear.', 'error');
                    foco();
                    return false;
                }
                return true;
            }

            function actualizarResumen() {
                const arr = Object.values(lineas);
                const piezas = arr.reduce((a, l) => a + Number(l.cantidad || 0), 0);
                $('#sal-resumen').html('<b>' + arr.length + '</b> productos · <b>' + piezas.toLocaleString('es-MX') + '</b> piezas');
                $('#sal-confirmar').prop('disabled', enviando || arr.length === 0);
            }

            function renderLineas() {
                const arr = Object.values(lineas).sort((a, b) => String(a.codigo || '').localeCompare(String(b.codigo || ''), 'es', { numeric: true }));
                const $b = $('#sal-lines-body');
                if (!arr.length) {
                    $b.html('<tr><td colspan="7" class="desktop-sal-empty">Escanea o agrega productos para armar la salida.</td></tr>');
                    actualizarResumen();
                    return;
                }
                $b.html(arr.map(l =>
                    '<tr data-key="' + esc(l.key) + '">' +
                        '<td><div class="desktop-sal-lines__name">' + esc(l.codigo) + '</div>' + (l.codigoBarras ? '<div class="desktop-sal-lines__sub">' + esc(l.codigoBarras) + '</div>' : '') + '</td>' +
                        '<td><div class="desktop-sal-lines__name">' + esc(l.productoCodigo || '') + '</div><div class="desktop-sal-lines__sub">' + esc(l.productoNombre || '') + '</div></td>' +
                        '<td>' + esc(l.variante || 'Base') + '</td>' +
                        '<td>' + esc(l.unidad || 'PZA') + '</td>' +
                        '<td class="num">' + Number(l.existencia || 0).toFixed(2) + '</td>' +
                        '<td class="num"><input type="number" min="1" step="1" class="js-sal-cant" data-key="' + esc(l.key) + '" value="' + esc(l.cantidad) + '"></td>' +
                        '<td class="num"><button type="button" class="desktop-sal-lines__del" data-del="' + esc(l.key) + '" title="Quitar">&times;</button></td>' +
                    '</tr>'
                ).join(''));
                actualizarResumen();
            }

            function actualizarAvail(l) {
                const ex = Number(l?.existencia ?? 0);
                $('#sal-avail-val').text(ex.toFixed(2));
                $('#sal-avail-last').text((l?.codigo || '-') + ' · ' + (l?.productoNombre || 'Producto'));
                const $a = $('#sal-avail').addClass('is-on').removeClass('desktop-sal-avail--ok desktop-sal-avail--low desktop-sal-avail--zero');
                $a.addClass(ex <= 0 ? 'desktop-sal-avail--zero' : ex <= 3 ? 'desktop-sal-avail--low' : 'desktop-sal-avail--ok');
            }

            function registrarLinea(data, inc) {
                const key = String(data.psk_id);
                const existencia = Number(data.existencia || 0);
                const meta = {
                    key, min_psk_id: Number(data.psk_id),
                    codigo: data.psk_codigo || data.producto?.prd_codigo || ('SKU-' + data.psk_id),
                    codigoBarras: data.psk_codigo_barras || data.producto?.prd_codigo_barras || '',
                    productoCodigo: data.producto?.prd_codigo || '',
                    productoNombre: data.producto?.prd_nombre || data.psk_nombre || 'Producto',
                    variante: data.psk_nombre || '',
                    unidad: data.unidad?.umd_codigo || data.unidad?.umd_nombre || 'PZA',
                    existencia,
                };
                if (existencia <= 0) { actualizarAvail(meta); notify('Sin existencia', 'El producto no tiene existencia disponible en este almacén.', 'error'); return; }
                const actual = lineas[key];
                const nueva = Math.min(existencia, Number(actual?.cantidad || 0) + (inc || 1));
                if (actual && nueva === Number(actual.cantidad)) { actualizarAvail(actual); notify('Tope alcanzado', 'La cantidad no puede superar la existencia disponible.', 'error'); return; }
                lineas[key] = Object.assign(meta, { cantidad: nueva });
                actualizarAvail(lineas[key]);
                renderLineas();
            }

            function resolver(params) {
                if (!contextoOk()) return $.Deferred().reject().promise();
                return $.getJSON(rutas.resolver, Object.assign({ min_scl_id: $('#sal-scl').val(), min_alm_id: $('#sal-alm').val() }, params));
            }
            function parseErr(xhr) {
                const errs = xhr.responseJSON?.errors;
                let m = xhr.responseJSON?.message || 'No fue posible procesar la solicitud.';
                if (errs) { const f = Object.values(errs)[0]; if (f && f[0]) m = f[0]; }
                return m;
            }

            // ===== Eventos =====
            $('#sal-scl').on('change', syncAlmacenes);

            $('#sal-scan').on('keydown', function (e) {
                if (e.key !== 'Enter') return;
                e.preventDefault();
                const codigo = String(this.value || '').trim();
                if (!codigo) { foco(10); return; }
                resolver({ q: codigo })
                    .done(resp => registrarLinea(resp.data, 1))
                    .fail(xhr => { if (xhr?.status) notify('Error', parseErr(xhr), 'error'); })
                    .always(() => { $('#sal-scan').val(''); foco(); });
            });

            $('#sal-sku').select2({
                placeholder: 'Busca por código, SKU o producto',
                minimumInputLength: 2,
                ajax: { url: rutas.skus, dataType: 'json', delay: 250, data: p => ({ q: p.term || '', page: p.page || 1 }), processResults: d => d }
            });
            function agregarManual() {
                const id = Number($('#sal-sku').val() || 0);
                if (!id) { notify('Faltan datos', 'Selecciona un SKU para agregar.', 'error'); return; }
                resolver({ min_psk_id: id })
                    .done(resp => { registrarLinea(resp.data, 1); $('#sal-sku').val(null).trigger('change'); })
                    .fail(xhr => { if (xhr?.status) notify('Error', parseErr(xhr), 'error'); })
                    .always(() => foco());
            }
            $('#sal-add').on('click', agregarManual);

            $('#sal-lines-body').on('input', '.js-sal-cant', function () {
                const l = lineas[String($(this).data('key'))];
                if (!l) return;
                let v = Math.floor(Number(this.value || 0));
                if (v < 1) v = 1;
                if (v > Number(l.existencia)) { v = Number(l.existencia); this.value = v; notify('Tope alcanzado', 'La cantidad no puede superar la existencia disponible.', 'error'); }
                l.cantidad = v;
                actualizarResumen();
            });
            $('#sal-lines-body').on('click', '[data-del]', function () {
                delete lineas[String($(this).data('del'))];
                renderLineas();
            });

            function construirPayload() {
                return {
                    min_scl_id: $('#sal-scl').val(),
                    min_alm_id: $('#sal-alm').val(),
                    min_documento_tipo: $('#sal-tipo').val(),
                    min_fecha_movimiento: $('#sal-fecha').val(),
                    min_mtv_id: $('#sal-mtv').val() || null,
                    min_documento_referencia: $('#sal-ref').val().trim() || null,
                    min_motivo_texto: $('#sal-motivo').val().trim(),
                    lineas: Object.values(lineas).map(l => ({ min_psk_id: l.min_psk_id, min_cantidad: Number(l.cantidad || 0) })),
                };
            }

            $('#sal-confirmar').on('click', async function () {
                if (!Object.keys(lineas).length) { notify('Faltan datos', 'Agrega al menos un producto.', 'error'); return; }
                if (!$('#sal-motivo').val().trim()) { notify('Faltan datos', 'El motivo es obligatorio.', 'error'); $('#sal-motivo').trigger('focus'); return; }
                const ok = await DesktopUI.confirm({
                    title: 'Confirmar salida',
                    message: 'Se descontará el inventario de los productos capturados. ¿Confirmar la salida?',
                    okText: 'Confirmar salida', danger: true,
                });
                if (!ok) return;
                enviando = true; actualizarResumen();
                $.ajax({ url: rutas.store, method: 'POST', headers: { 'X-CSRF-TOKEN': csrf }, data: construirPayload() })
                    .done(function (resp) {
                        notify('Salida registrada', resp.message || 'Salida registrada correctamente.', 'success');
                        window.location.href = rutas.listado;
                    })
                    .fail(xhr => { notify('Error', parseErr(xhr), 'error'); enviando = false; actualizarResumen(); });
            });

            // Init
            const ahora = new Date();
            $('#sal-fecha').val(new Date(ahora.getTime() - ahora.getTimezoneOffset() * 60000).toISOString().slice(0, 16));
            syncAlmacenes();
            renderLineas();
            foco(120);
        })();
    </script>
@endpush
