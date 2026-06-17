@extends('layouts.desktop')

@section('title', 'Atributos')

@push('desktop-vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        @php($activeSubmenu = 'atributos')
        @include('desktop.operacion.catalogo_comercial._subnav')
        @if($permisosUI['crear'])
            <span class="desktop-toolbar__divider"></span>
            <button type="button" class="desktop-btn desktop-btn--primary" id="btn-nuevo-atributo-main">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5v14"/></svg>
                Nuevo atributo
            </button>
        @endif
        <button type="button" class="desktop-btn desktop-btn--ghost" id="btn-recargar-atributos">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
            Actualizar
        </button>
    </div>
    <div class="desktop-toolbar__group">
        <select class="desktop-toolbar__select" id="atributos-estatus">
            <option value="">Todos los estatus</option>
            <option value="activo">Activos</option>
            <option value="inactivo">Inactivos</option>
        </select>
        <select class="desktop-toolbar__select" id="atributos-length">
            <option value="25">25 por página</option>
            <option value="50">50 por página</option>
            <option value="100" selected>100 por página</option>
        </select>
        <select class="desktop-toolbar__select" id="valores-atributo-filter" hidden>
            <option value="">Todos los atributos</option>
            @foreach($opciones['atributos'] as $atributo)
                <option value="{{ $atributo->atr_id }}">{{ $atributo->atr_nombre }}</option>
            @endforeach
        </select>
        <input type="search" id="atributos-search" class="desktop-toolbar__search" placeholder="Buscar atributo">
    </div>
@endsection

@section('content')
    <section class="desktop-pane">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; padding:8px 12px; border-bottom:1px solid var(--stroke); background:var(--surface-alt);">
            <div class="desktop-pivot" role="tablist" aria-label="Vista de atributos">
                <button type="button" class="desktop-btn desktop-btn--active" data-attr-view="atributos" aria-current="page">Atributos</button>
                <button type="button" class="desktop-btn" data-attr-view="valores">Valores</button>
            </div>
            <div style="font-size:.75rem; color:var(--text-2);" id="atributos-view-meta">Vista activa: Atributos</div>
        </div>

        <div class="desktop-list-wrap">
            <table id="desktop-atributos-table" class="desktop-list">
                <thead>
                    <tr id="desktop-atributos-head"></tr>
                </thead>
            </table>
        </div>

        <div class="desktop-list-foot">
            <div id="desktop-atributos-info"></div>
            <div id="desktop-atributos-pagination" class="desktop-pager"></div>
        </div>
    </section>

    <div class="desktop-modal" id="desktop-atributo-modal" aria-hidden="true">
        <div class="desktop-modal__dialog">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="desktop-atributo-modal-title">Nuevo atributo</div>
                <button type="button" class="desktop-modal__close" data-close-atributo-modal aria-label="Cerrar">&times;</button>
            </div>
            <form id="desktop-atributo-form">
                <div class="desktop-modal__body">
                    <input type="hidden" id="atr_id" name="atr_id">
                    <div class="desktop-form-grid">
                        <div class="desktop-field desktop-field--full">
                            <label>Nombre</label>
                            <input type="text" name="atr_nombre" id="atr_nombre" maxlength="120" required>
                        </div>
                        <div class="desktop-field">
                            <label>Clave</label>
                            <input type="text" name="atr_clave" id="atr_clave" maxlength="40" placeholder="Opcional">
                        </div>
                        <div class="desktop-field">
                            <label>Tipo</label>
                            <input type="text" name="atr_tipo" id="atr_tipo" maxlength="40" placeholder="Ej. talla, color, material">
                        </div>
                        <div class="desktop-field">
                            <label>Estatus</label>
                            <select name="atr_estatus" id="atr_estatus" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="desktop-modal__foot">
                    <button type="button" class="desktop-btn desktop-btn--default" data-close-atributo-modal>Cancelar</button>
                    <button type="submit" class="desktop-btn desktop-btn--primary" id="btn-guardar-atributo">Guardar atributo</button>
                </div>
            </form>
        </div>
    </div>

    <div class="desktop-modal" id="desktop-valor-modal" aria-hidden="true">
        <div class="desktop-modal__dialog">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="desktop-valor-modal-title">Nuevo valor</div>
                <button type="button" class="desktop-modal__close" data-close-valor-modal aria-label="Cerrar">&times;</button>
            </div>
            <form id="desktop-valor-form">
                <div class="desktop-modal__body">
                    <input type="hidden" id="vat_id" name="vat_id">
                    <div class="desktop-form-grid">
                        <div class="desktop-field">
                            <label>Atributo</label>
                            <select name="vat_atr_id" id="vat_atr_id" required>
                                <option value="">Selecciona un atributo</option>
                                @foreach($opciones['atributos'] as $atributo)
                                    <option value="{{ $atributo->atr_id }}">{{ $atributo->atr_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="desktop-field desktop-field--full">
                            <label>Valor</label>
                            <input type="text" name="vat_valor" id="vat_valor" maxlength="120" required>
                        </div>
                        <div class="desktop-field">
                            <label>Clave</label>
                            <input type="text" name="vat_clave" id="vat_clave" maxlength="40" placeholder="Opcional">
                        </div>
                        <div class="desktop-field">
                            <label>Estatus</label>
                            <select name="vat_estatus" id="vat_estatus" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="desktop-modal__foot">
                    <button type="button" class="desktop-btn desktop-btn--default" data-close-valor-modal>Cancelar</button>
                    <button type="submit" class="desktop-btn desktop-btn--primary" id="btn-guardar-valor">Guardar valor</button>
                </div>
            </form>
        </div>
    </div>

    <div class="desktop-modal" id="desktop-atributos-detail-modal" aria-hidden="true">
        <div class="desktop-modal__dialog">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="desktop-atributos-detail-title">Detalle</div>
                <button type="button" class="desktop-modal__close" data-close-atributos-detail-modal aria-label="Cerrar">&times;</button>
            </div>
            <div class="desktop-modal__body">
                <div class="desktop-form-grid" id="desktop-atributos-detail-grid"></div>
            </div>
            <div class="desktop-modal__foot">
                <button type="button" class="desktop-btn desktop-btn--default" data-close-atributos-detail-modal>Cerrar</button>
            </div>
        </div>
    </div>

    <div class="desktop-modal" id="desktop-atributos-confirm-modal" aria-hidden="true">
        <div class="desktop-modal__dialog" style="width:min(440px, 100%);">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title">Confirmar acción</div>
                <button type="button" class="desktop-modal__close" data-close-atributos-confirm-modal aria-label="Cerrar">&times;</button>
            </div>
            <div class="desktop-modal__body">
                <p id="desktop-atributos-confirm-copy" style="margin:0; color:var(--text-2); line-height:1.55;"></p>
            </div>
            <div class="desktop-modal__foot">
                <button type="button" class="desktop-btn desktop-btn--default" data-close-atributos-confirm-modal>Cancelar</button>
                <button type="button" class="desktop-btn desktop-btn--primary" id="desktop-atributos-confirm-accept">Continuar</button>
            </div>
        </div>
    </div>

    <div class="desktop-feedback" id="desktop-atributos-feedback"></div>
@endsection

@push('desktop-vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endpush

@push('desktop-scripts')
    <script>
        (function () {
            const $table = $('#desktop-atributos-table');
            const $atributoModal = $('#desktop-atributo-modal');
            const $valorModal = $('#desktop-valor-modal');
            const $detailModal = $('#desktop-atributos-detail-modal');
            const $confirmModal = $('#desktop-atributos-confirm-modal');
            const $atributoForm = $('#desktop-atributo-form');
            const $valorForm = $('#desktop-valor-form');
            const $feedback = $('#desktop-atributos-feedback');
            let attrTable = null;
            let currentView = 'atributos';
            let confirmAction = null;

            $.ajaxSetup({
                cache: false,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const permisosUI = @json($permisosUI);
            const rutas = {
                atributosData: '{{ route('desktop.operacion.catalogo_comercial.atributos.data') }}',
                atributoShow: function (id) { return '{{ url('/desktop/operacion/catalogo-comercial/atributos') }}/' + id; },
                atributoStore: '{{ route('desktop.operacion.catalogo_comercial.atributos.store') }}',
                atributoUpdate: function (id) { return '{{ url('/desktop/operacion/catalogo-comercial/atributos') }}/' + id; },
                atributoEstatus: function (id) { return '{{ url('/desktop/operacion/catalogo-comercial/atributos') }}/' + id + '/estatus'; },
                atributoDelete: function (id) { return '{{ url('/desktop/operacion/catalogo-comercial/atributos') }}/' + id; },
                valoresData: '{{ route('desktop.operacion.catalogo_comercial.valores.data') }}',
                valorShow: function (id) { return '{{ url('/desktop/operacion/catalogo-comercial/valores-atributo') }}/' + id; },
                valorStore: '{{ route('desktop.operacion.catalogo_comercial.valores.store') }}',
                valorUpdate: function (id) { return '{{ url('/desktop/operacion/catalogo-comercial/valores-atributo') }}/' + id; },
                valorEstatus: function (id) { return '{{ url('/desktop/operacion/catalogo-comercial/valores-atributo') }}/' + id + '/estatus'; },
                valorDelete: function (id) { return '{{ url('/desktop/operacion/catalogo-comercial/valores-atributo') }}/' + id; }
            };
            const ICONS = {
                edit: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>',
                view: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z"/><circle cx="12" cy="12" r="3"/></svg>',
                toggle: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.2-8.5"/><path d="m9 11 3 3L22 4"/></svg>',
                remove: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>',
                dots: '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="19" cy="12" r="1.8"/></svg>'
            };

            function escapeHtml(value) {
                return String(value || '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#39;');
            }

            function initials(value) {
                return String(value || '?').trim().split(/\s+/).slice(0, 2).map(function (part) {
                    return part.charAt(0);
                }).join('').toUpperCase() || '?';
            }

            function parseError(xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    const firstGroup = Object.values(xhr.responseJSON.errors)[0];
                    if (Array.isArray(firstGroup) && firstGroup[0]) return firstGroup[0];
                }

                return xhr.responseJSON?.message || 'No fue posible completar la operación.';
            }

            function showFeedback(type, message) {
                $feedback.removeClass('is-error is-success is-visible')
                    .addClass(type === 'error' ? 'is-error' : 'is-success')
                    .text(message)
                    .addClass('is-visible');

                window.clearTimeout(showFeedback._timer);
                showFeedback._timer = window.setTimeout(function () {
                    $feedback.removeClass('is-visible');
                }, 3600);
            }

            function openModal($modal) {
                $modal.addClass('is-open').attr('aria-hidden', 'false');
            }

            function closeModal($modal) {
                $modal.removeClass('is-open').attr('aria-hidden', 'true');
            }

            function renderStatus(value) {
                const active = value === 'activo';
                return '<span class="desktop-status ' + (active ? 'desktop-status--active' : 'desktop-status--inactive') + '">' + (active ? 'Activo' : 'Inactivo') + '</span>';
            }

            function syncViewUI() {
                $('[data-attr-view]').removeClass('desktop-btn--active').removeAttr('aria-current');
                $('[data-attr-view="' + currentView + '"]').addClass('desktop-btn--active').attr('aria-current', 'page');
                $('#atributos-view-meta').text('Vista activa: ' + (currentView === 'atributos' ? 'Atributos' : 'Valores de atributo'));
                $('#valores-atributo-filter').prop('hidden', currentView !== 'valores');
                $('#atributos-search').attr('placeholder', currentView === 'atributos' ? 'Buscar atributo' : 'Buscar valor');
                $('#btn-nuevo-atributo-main').html(
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5v14"/></svg>' +
                    (currentView === 'atributos' ? 'Nuevo atributo' : 'Nuevo valor')
                );
            }

            function renderActions(row) {
                const isAtributo = currentView === 'atributos';
                const id = isAtributo ? row.atr_id : row.vat_id;
                const name = isAtributo ? row.atr_nombre : row.vat_valor;
                const status = isAtributo ? row.atr_estatus : row.vat_estatus;
                const items = [];

                if (permisosUI.editar) {
                    items.push('<button type="button" class="desktop-menu__item btn-edit-record" data-id="' + id + '">' + ICONS.edit + 'Editar</button>');
                }
                items.push('<button type="button" class="desktop-menu__item btn-view-record" data-id="' + id + '">' + ICONS.view + 'Ver detalle</button>');

                if (permisosUI.inactivar) {
                    items.push('<div class="desktop-menu__divider"></div>');
                    items.push('<button type="button" class="desktop-menu__item btn-toggle-record" data-id="' + id + '" data-next="' + (status === 'activo' ? 'inactivo' : 'activo') + '">' + ICONS.toggle + (status === 'activo' ? 'Inactivar' : 'Activar') + '</button>');
                }

                if (permisosUI.eliminar) {
                    items.push('<button type="button" class="desktop-menu__item desktop-menu__item--danger btn-delete-record" data-id="' + id + '" data-name="' + escapeHtml(name) + '">' + ICONS.remove + 'Eliminar</button>');
                }

                return '<div class="desktop-rowmenu">' +
                    '<button type="button" class="desktop-overflow" data-overflow aria-haspopup="true" aria-expanded="false" aria-label="Más acciones">' + ICONS.dots + '</button>' +
                    '<div class="desktop-menu">' + items.join('') + '</div>' +
                    '</div>';
            }

            function headers() {
                return currentView === 'atributos'
                    ? ['Atributo', 'Clave', 'Tipo', 'Valores', 'Estatus', 'Acciones']
                    : ['Atributo', 'Valor', 'Clave', 'Estatus', 'Acciones'];
            }

            function columns() {
                if (currentView === 'atributos') {
                    return [
                        {
                            data: 'atr_nombre',
                            render: function (value, type, row) {
                                return '<div class="desktop-cell-primary">' +
                                    '<span class="desktop-avatar-sm">' + escapeHtml(initials(value)) + '</span>' +
                                    '<span><span class="desktop-list__name">' + escapeHtml(value) + '</span>' +
                                    '<span class="desktop-list__meta">ID ' + escapeHtml(row.atr_id) + '</span></span></div>';
                            }
                        },
                        {
                            data: 'atr_clave',
                            render: function (value) {
                                return '<span style="font-weight:600;">' + escapeHtml(value || '-') + '</span>';
                            }
                        },
                        {
                            data: 'atr_tipo',
                            render: function (value) {
                                return value
                                    ? '<span class="desktop-pill desktop-pill--neutral">' + escapeHtml(value) + '</span>'
                                    : '<span class="desktop-list__meta">Sin tipo</span>';
                            }
                        },
                        {
                            data: 'valores_total',
                            render: function (value) {
                                return '<span class="desktop-list__name">' + escapeHtml(value || 0) + ' registrados</span>';
                            }
                        },
                        { data: 'atr_estatus', render: renderStatus },
                        { data: null, orderable: false, searchable: false, render: renderActions }
                    ];
                }

                return [
                    {
                        data: 'atributo',
                        render: function (value, type, row) {
                            return '<div class="desktop-cell-primary">' +
                                '<span class="desktop-avatar-sm">' + escapeHtml(initials(value)) + '</span>' +
                                '<span><span class="desktop-list__name">' + escapeHtml(value) + '</span>' +
                                '<span class="desktop-list__meta">Atributo asociado</span></span></div>';
                        }
                    },
                    {
                        data: 'vat_valor',
                        render: function (value, type, row) {
                            return '<span class="desktop-list__name">' + escapeHtml(value) + '</span>' +
                                '<span class="desktop-list__meta">ID ' + escapeHtml(row.vat_id) + '</span>';
                        }
                    },
                    {
                        data: 'vat_clave',
                        render: function (value) {
                            return '<span style="font-weight:600;">' + escapeHtml(value || '-') + '</span>';
                        }
                    },
                    { data: 'vat_estatus', render: renderStatus },
                    { data: null, orderable: false, searchable: false, render: renderActions }
                ];
            }

            function renderFooter() {
                if (!attrTable) return;

                const info = attrTable.page.info();
                const total = info.recordsDisplay;
                const label = currentView === 'atributos' ? 'atributos' : 'valores';

                if (!total) {
                    $('#desktop-atributos-info').text('Mostrando 0 ' + label);
                    $('#desktop-atributos-pagination').empty();
                    return;
                }

                $('#desktop-atributos-info').text('Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + total + ' ' + label);

                const buttons = [];
                buttons.push({ label: '‹', page: 'previous', disabled: info.page === 0 });
                for (let i = 0; i < info.pages; i += 1) {
                    buttons.push({ label: String(i + 1), page: i, active: i === info.page });
                }
                buttons.push({ label: '›', page: 'next', disabled: info.page >= info.pages - 1 });

                $('#desktop-atributos-pagination').html(buttons.map(function (button) {
                    const classes = ['desktop-pager__btn', button.active ? 'is-active' : '', button.disabled ? 'is-disabled' : ''].filter(Boolean).join(' ');
                    return '<button type="button" class="' + classes + '" data-page="' + button.page + '"' + (button.disabled ? ' disabled' : '') + '>' + button.label + '</button>';
                }).join(''));
            }

            function rebuildTable() {
                if ($.fn.DataTable.isDataTable('#desktop-atributos-table')) {
                    $('#desktop-atributos-table').DataTable().destroy();
                }
                $('#desktop-atributos-head').html('');
                $('#desktop-atributos-table tbody').remove();

                $('#desktop-atributos-head').html(headers().map(function (header, index) {
                    const style = currentView === 'atributos'
                        ? (index === 5 ? ' style="width:56px; text-align:right;"' : (index === 4 ? ' style="width:104px;"' : ''))
                        : (index === 4 ? ' style="width:56px; text-align:right;"' : (index === 3 ? ' style="width:104px;"' : ''));
                    return '<th' + style + '>' + header + '</th>';
                }).join(''));

                attrTable = $table.DataTable({
                    ajax: {
                        url: currentView === 'atributos' ? rutas.atributosData : rutas.valoresData,
                        data: function () {
                            return {
                                buscar: $('#atributos-search').val(),
                                estatus: $('#atributos-estatus').val(),
                                vat_atr_id: currentView === 'valores' ? $('#valores-atributo-filter').val() : ''
                            };
                        },
                        dataSrc: 'data'
                    },
                    processing: true,
                    deferRender: true,
                    responsive: false,
                    autoWidth: false,
                    pageLength: Number($('#atributos-length').val() || 100),
                    lengthChange: false,
                    searching: false,
                    order: [[0, 'asc']],
                    dom: 'rt',
                    language: {
                        processing: 'Cargando...',
                        emptyTable: currentView === 'atributos' ? 'No hay atributos registrados' : 'No hay valores registrados',
                        zeroRecords: 'No se encontraron coincidencias'
                    },
                    columns: columns(),
                    initComplete: renderFooter,
                    drawCallback: renderFooter
                });
            }

            function reloadTable(resetPaging) {
                if (!attrTable) return;
                attrTable.ajax.reload(null, !resetPaging);
            }

            function resetAtributoForm() {
                $atributoForm.get(0).reset();
                $('#atr_id').val('');
                $('#atr_estatus').val('activo');
                $('#desktop-atributo-modal-title').text('Nuevo atributo');
                $('#btn-guardar-atributo').text('Guardar atributo');
            }

            function resetValorForm() {
                $valorForm.get(0).reset();
                $('#vat_id').val('');
                $('#vat_estatus').val('activo');
                $('#desktop-valor-modal-title').text('Nuevo valor');
                $('#btn-guardar-valor').text('Guardar valor');
            }

            function openCreateModal() {
                if (currentView === 'atributos') {
                    resetAtributoForm();
                    openModal($atributoModal);
                    return;
                }
                resetValorForm();
                openModal($valorModal);
            }

            function renderDetail(title, fields) {
                $('#desktop-atributos-detail-title').text(title);
                $('#desktop-atributos-detail-grid').html(fields.map(function (field) {
                    return '<div class="desktop-field ' + (field.full ? 'desktop-field--full' : '') + '">' +
                        '<label>' + escapeHtml(field.label) + '</label>' +
                        '<input type="text" readonly value="' + escapeHtml(field.value) + '">' +
                        '</div>';
                }).join(''));
                openModal($detailModal);
            }

            function openConfirm(message, callback) {
                confirmAction = callback;
                $('#desktop-atributos-confirm-copy').text(message);
                openModal($confirmModal);
            }

            function loadRecord(id, mode) {
                const request = currentView === 'atributos'
                    ? $.getJSON(rutas.atributoShow(id))
                    : $.getJSON(rutas.valorShow(id));

                request.done(function (response) {
                    const data = response.data || {};

                    if (mode === 'edit') {
                        if (currentView === 'atributos') {
                            resetAtributoForm();
                            $('#desktop-atributo-modal-title').text('Editar atributo');
                            $('#btn-guardar-atributo').text('Guardar cambios');
                            $('#atr_id').val(data.atr_id || '');
                            $('#atr_nombre').val(data.atr_nombre || '');
                            $('#atr_clave').val(data.atr_clave || '');
                            $('#atr_tipo').val(data.atr_tipo || '');
                            $('#atr_estatus').val(data.atr_estatus || 'activo');
                            openModal($atributoModal);
                        } else {
                            resetValorForm();
                            $('#desktop-valor-modal-title').text('Editar valor');
                            $('#btn-guardar-valor').text('Guardar cambios');
                            $('#vat_id').val(data.vat_id || '');
                            $('#vat_atr_id').val(String(data.vat_atr_id || ''));
                            $('#vat_valor').val(data.vat_valor || '');
                            $('#vat_clave').val(data.vat_clave || '');
                            $('#vat_estatus').val(data.vat_estatus || 'activo');
                            openModal($valorModal);
                        }
                        return;
                    }

                    if (currentView === 'atributos') {
                        renderDetail('Detalle de atributo', [
                            { label: 'Nombre', value: data.atr_nombre || '', full: true },
                            { label: 'Clave', value: data.atr_clave || '-' },
                            { label: 'Tipo', value: data.atr_tipo || '-' },
                            { label: 'Estatus', value: data.atr_estatus === 'activo' ? 'Activo' : 'Inactivo' }
                        ]);
                    } else {
                        const atributoNombre = $('#vat_atr_id option[value="' + String(data.vat_atr_id || '') + '"]').text() || '';
                        renderDetail('Detalle de valor', [
                            { label: 'Atributo', value: atributoNombre || '-', full: true },
                            { label: 'Valor', value: data.vat_valor || '', full: true },
                            { label: 'Clave', value: data.vat_clave || '-' },
                            { label: 'Estatus', value: data.vat_estatus === 'activo' ? 'Activo' : 'Inactivo' }
                        ]);
                    }
                }).fail(function (xhr) {
                    showFeedback('error', parseError(xhr));
                });
            }

            function toggleRecord(id, nextStatus) {
                const request = currentView === 'atributos'
                    ? $.ajax({ url: rutas.atributoEstatus(id), method: 'PATCH', data: { atr_estatus: nextStatus } })
                    : $.ajax({ url: rutas.valorEstatus(id), method: 'PATCH', data: { vat_estatus: nextStatus } });

                request.done(function (response) {
                    showFeedback('success', response.message || 'Estatus actualizado correctamente.');
                    reloadTable(false);
                }).fail(function (xhr) {
                    showFeedback('error', parseError(xhr));
                });
            }

            function deleteRecord(id, name) {
                openConfirm('Se eliminará "' + name + '". Esta acción no se puede deshacer.', function () {
                    const request = currentView === 'atributos'
                        ? $.ajax({ url: rutas.atributoDelete(id), method: 'DELETE' })
                        : $.ajax({ url: rutas.valorDelete(id), method: 'DELETE' });

                    request.done(function (response) {
                        showFeedback('success', response.message || 'Registro eliminado correctamente.');
                        reloadTable(true);
                    }).fail(function (xhr) {
                        showFeedback('error', parseError(xhr));
                    });
                });
            }

            syncViewUI();
            rebuildTable();

            $('[data-attr-view]').on('click', function () {
                const next = String($(this).data('attrView') || '');
                if (!next || next === currentView) return;
                currentView = next;
                syncViewUI();
                rebuildTable();
            });

            $('#btn-nuevo-atributo-main').on('click', openCreateModal);
            $('#btn-recargar-atributos').on('click', function () { reloadTable(true); });
            $('#atributos-estatus').on('change', function () { reloadTable(true); });
            $('#atributos-search').on('input', function () { reloadTable(true); });
            $('#valores-atributo-filter').on('change', function () { reloadTable(true); });
            $('#atributos-length').on('change', function () {
                if (!attrTable) return;
                attrTable.page.len(Number(this.value)).draw();
            });
            $('#desktop-atributos-pagination').on('click', '.desktop-pager__btn', function () {
                if ($(this).is(':disabled') || !attrTable) return;
                attrTable.page($(this).data('page')).draw('page');
            });

            $atributoForm.on('submit', function (event) {
                event.preventDefault();
                const id = $('#atr_id').val();
                $.ajax({
                    url: id ? rutas.atributoUpdate(id) : rutas.atributoStore,
                    method: id ? 'PUT' : 'POST',
                    data: $atributoForm.serialize()
                }).done(function (response) {
                    closeModal($atributoModal);
                    showFeedback('success', response.message || 'Atributo guardado correctamente.');
                    reloadTable(true);
                }).fail(function (xhr) {
                    showFeedback('error', parseError(xhr));
                });
            });

            $valorForm.on('submit', function (event) {
                event.preventDefault();
                const id = $('#vat_id').val();
                $.ajax({
                    url: id ? rutas.valorUpdate(id) : rutas.valorStore,
                    method: id ? 'PUT' : 'POST',
                    data: $valorForm.serialize()
                }).done(function (response) {
                    closeModal($valorModal);
                    showFeedback('success', response.message || 'Valor guardado correctamente.');
                    reloadTable(true);
                }).fail(function (xhr) {
                    showFeedback('error', parseError(xhr));
                });
            });

            $(document).on('click', '.btn-edit-record', function () { loadRecord($(this).data('id'), 'edit'); });
            $(document).on('click', '.btn-view-record', function () { loadRecord($(this).data('id'), 'view'); });
            $(document).on('click', '.btn-toggle-record', function () { toggleRecord($(this).data('id'), $(this).data('next')); });
            $(document).on('click', '.btn-delete-record', function () { deleteRecord($(this).data('id'), $(this).data('name')); });

            $('[data-close-atributo-modal]').on('click', function () { closeModal($atributoModal); });
            $('[data-close-valor-modal]').on('click', function () { closeModal($valorModal); });
            $('[data-close-atributos-detail-modal]').on('click', function () { closeModal($detailModal); });
            $('[data-close-atributos-confirm-modal]').on('click', function () { closeModal($confirmModal); });
            $('#desktop-atributos-confirm-accept').on('click', function () {
                const action = confirmAction;
                confirmAction = null;
                closeModal($confirmModal);
                if (typeof action === 'function') action();
            });

            $atributoModal.on('click', function (event) { if (event.target === this) closeModal($atributoModal); });
            $valorModal.on('click', function (event) { if (event.target === this) closeModal($valorModal); });
            $detailModal.on('click', function (event) { if (event.target === this) closeModal($detailModal); });
            $confirmModal.on('click', function (event) { if (event.target === this) closeModal($confirmModal); });
        })();
    </script>
@endpush
