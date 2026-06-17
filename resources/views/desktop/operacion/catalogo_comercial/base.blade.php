@extends('layouts.desktop')

@section('title', 'Catálogos base')

@push('desktop-vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
@endpush

@push('desktop-styles')
    <style>
        .desktop-catalogo-pane__bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            padding: 8px 12px;
            border-bottom: 1px solid var(--stroke);
            background: var(--surface-alt);
        }
        .desktop-catalogo-pane__meta {
            font-size: .75rem;
            color: var(--text-2);
        }
        .desktop-catalogo-checklist {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }
        .desktop-catalogo-check {
            display: flex;
            align-items: center;
            gap: 8px;
            min-height: 34px;
            padding: 0 10px;
            border: 1px solid var(--stroke);
            border-radius: var(--r-md);
            background: var(--surface-alt);
        }
        .desktop-catalogo-check input {
            margin: 0;
        }
        .desktop-catalogo-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px 16px;
        }
        .desktop-catalogo-detail-grid .desktop-field--full {
            grid-column: 1 / -1;
        }
        @media (max-width: 860px) {
            .desktop-catalogo-checklist,
            .desktop-catalogo-detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        @php($activeSubmenu = 'base')
        @include('desktop.operacion.catalogo_comercial._subnav')
        @if($permisosUI['crear'])
            <span class="desktop-toolbar__divider"></span>
            <button type="button" class="desktop-btn desktop-btn--primary" id="btn-nuevo-base">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5v14"/></svg>
                Nuevo registro
            </button>
        @endif
        <button type="button" class="desktop-btn desktop-btn--ghost" id="btn-recargar-base">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
            Actualizar
        </button>
    </div>
    <div class="desktop-toolbar__group">
        <select class="desktop-toolbar__select" id="base-estatus">
            <option value="">Todos los estatus</option>
            <option value="activo">Activos</option>
            <option value="inactivo">Inactivos</option>
        </select>
        <select class="desktop-toolbar__select" id="base-length">
            <option value="25">25 por página</option>
            <option value="50">50 por página</option>
            <option value="100" selected>100 por página</option>
        </select>
        <input type="search" id="base-search" class="desktop-toolbar__search" placeholder="Buscar registro">
    </div>
@endsection

@section('content')
    <section class="desktop-pane">
        <div class="desktop-catalogo-pane__bar">
            <div class="desktop-pivot" role="tablist" aria-label="Tipos de catálogo base">
                @foreach($catalogTypes as $catalogType)
                    <button
                        type="button"
                        class="desktop-btn {{ $loop->first ? 'desktop-btn--active' : '' }}"
                        data-catalog-type="{{ $catalogType['key'] }}"
                        @if($loop->first) aria-current="page" @endif
                    >
                        {{ $catalogType['label'] }}
                    </button>
                @endforeach
            </div>
            <div class="desktop-catalogo-pane__meta" id="catalogo-base-meta">Catálogo activo: Marcas</div>
        </div>

        <div class="desktop-list-wrap">
            <table id="desktop-catalogos-base-table" class="desktop-list">
                <thead>
                    <tr id="desktop-catalogos-base-head"></tr>
                </thead>
            </table>
        </div>

        <div class="desktop-list-foot">
            <div id="desktop-catalogos-base-info"></div>
            <div id="desktop-catalogos-base-pagination" class="desktop-pager"></div>
        </div>
    </section>

    <div class="desktop-modal" id="desktop-catalogo-modal" aria-hidden="true">
        <div class="desktop-modal__dialog">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="desktop-catalogo-modal-title">Nuevo registro</div>
                <button type="button" class="desktop-modal__close" data-close-catalogo-modal aria-label="Cerrar">&times;</button>
            </div>

            <form id="desktop-catalogo-form">
                <div class="desktop-modal__body">
                    <input type="hidden" id="cat_id" name="cat_id">

                    <div class="desktop-form-grid">
                        <div class="desktop-field desktop-field--full">
                            <label>Nombre</label>
                            <input type="text" name="nombre" id="cat_nombre" maxlength="120" required>
                        </div>

                        <div class="desktop-field" id="cat-codigo-wrap" hidden>
                            <label>Código</label>
                            <input type="text" name="codigo" id="cat_codigo" maxlength="20">
                        </div>

                        <div class="desktop-field">
                            <label>Estatus</label>
                            <select name="estatus" id="cat_estatus" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>

                        <div class="desktop-field desktop-field--full" id="cat-categoria-linea-wrap" hidden>
                            <label>Línea</label>
                            <select name="lna_id" id="cat_lna_id">
                                <option value="">Selecciona una línea</option>
                                @foreach($opciones['lineas'] as $linea)
                                    <option value="{{ $linea->lna_id }}">{{ $linea->lna_nombre }}</option>
                                @endforeach
                            </select>
                            <small>El mismo concepto puede existir en distintas líneas.</small>
                        </div>

                        <div class="desktop-field desktop-field--full" id="cat-unidad-extra" hidden>
                            <div class="desktop-form-grid">
                                <div class="desktop-field">
                                    <label>Tipo de cantidad</label>
                                    <select name="tipo_cantidad" id="cat_tipo_cantidad">
                                        <option value="entero">Entero</option>
                                        <option value="decimal">Decimal</option>
                                    </select>
                                    <small>Define si la unidad admite cantidades decimales.</small>
                                </div>
                                <div class="desktop-field" style="justify-content:flex-end;">
                                    <label class="desktop-check">
                                        <input type="checkbox" name="es_predeterminada" id="cat_es_predeterminada" value="1">
                                        <span class="desktop-check__box">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                        </span>
                                        <span class="desktop-check__label">Usar como unidad predeterminada</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="desktop-modal__foot">
                    <button type="button" class="desktop-btn desktop-btn--default" data-close-catalogo-modal>Cancelar</button>
                    <button type="submit" class="desktop-btn desktop-btn--primary" id="btn-guardar-catalogo">Guardar registro</button>
                </div>
            </form>
        </div>
    </div>

    <div class="desktop-modal" id="desktop-modelo-modal" aria-hidden="true">
        <div class="desktop-modal__dialog">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="desktop-modelo-modal-title">Nuevo modelo</div>
                <button type="button" class="desktop-modal__close" data-close-modelo-modal aria-label="Cerrar">&times;</button>
            </div>

            <form id="desktop-modelo-form">
                <div class="desktop-modal__body">
                    <input type="hidden" id="mdl_id" name="mdl_id">

                    <div class="desktop-form-grid">
                        <div class="desktop-field desktop-field--full">
                            <label>Nombre</label>
                            <input type="text" name="nombre" id="mdl_nombre" maxlength="120" required>
                        </div>
                        <div class="desktop-field">
                            <label>Clave</label>
                            <input type="text" name="clave" id="mdl_clave" maxlength="40" placeholder="Opcional">
                        </div>
                        <div class="desktop-field">
                            <label>Estatus</label>
                            <select name="estatus" id="mdl_estatus" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="desktop-field desktop-field--full">
                            <label>Marcas asociadas</label>
                            <div class="desktop-catalogo-checklist">
                                @foreach($opciones['marcas'] as $marca)
                                    <label class="desktop-catalogo-check">
                                        <input type="checkbox" name="marca_ids[]" value="{{ $marca->mrc_id }}">
                                        <span>{{ $marca->mrc_nombre }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @if($opciones['marcas']->isEmpty())
                                <small>No hay marcas activas disponibles.</small>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="desktop-modal__foot">
                    <button type="button" class="desktop-btn desktop-btn--default" data-close-modelo-modal>Cancelar</button>
                    <button type="submit" class="desktop-btn desktop-btn--primary" id="btn-guardar-modelo">Guardar modelo</button>
                </div>
            </form>
        </div>
    </div>

    <div class="desktop-modal" id="desktop-detail-modal" aria-hidden="true">
        <div class="desktop-modal__dialog">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="desktop-detail-title">Detalle</div>
                <button type="button" class="desktop-modal__close" data-close-detail-modal aria-label="Cerrar">&times;</button>
            </div>
            <div class="desktop-modal__body">
                <div class="desktop-catalogo-detail-grid" id="desktop-detail-grid"></div>
            </div>
            <div class="desktop-modal__foot">
                <button type="button" class="desktop-btn desktop-btn--default" data-close-detail-modal>Cerrar</button>
            </div>
        </div>
    </div>

    <div class="desktop-modal" id="desktop-confirm-modal" aria-hidden="true">
        <div class="desktop-modal__dialog" style="width:min(440px, 100%);">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="desktop-confirm-title">Confirmar acción</div>
                <button type="button" class="desktop-modal__close" data-close-confirm-modal aria-label="Cerrar">&times;</button>
            </div>
            <div class="desktop-modal__body">
                <p id="desktop-confirm-copy" style="margin:0; color:var(--text-2); line-height:1.55;"></p>
            </div>
            <div class="desktop-modal__foot">
                <button type="button" class="desktop-btn desktop-btn--default" data-close-confirm-modal>Cancelar</button>
                <button type="button" class="desktop-btn desktop-btn--primary" id="desktop-confirm-accept">Continuar</button>
            </div>
        </div>
    </div>

    <div class="desktop-feedback" id="desktop-catalogo-feedback"></div>
@endsection

@push('desktop-vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endpush

@push('desktop-scripts')
    <script>
        (function () {
            const $table = $('#desktop-catalogos-base-table');
            const $catalogModal = $('#desktop-catalogo-modal');
            const $catalogForm = $('#desktop-catalogo-form');
            const $modelModal = $('#desktop-modelo-modal');
            const $modelForm = $('#desktop-modelo-form');
            const $detailModal = $('#desktop-detail-modal');
            const $confirmModal = $('#desktop-confirm-modal');
            const $feedback = $('#desktop-catalogo-feedback');
            let baseTable = null;
            let currentType = 'marcas';
            let confirmAction = null;

            $.ajaxSetup({
                cache: false,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const permisosUI = @json($permisosUI);
            const labels = {
                marcas: 'Marca',
                modelos: 'Modelo',
                lineas: 'Línea',
                categorias: 'Concepto',
                unidades: 'Unidad',
                motivos: 'Motivo'
            };
            const createLabels = {
                marcas: 'Nueva marca',
                modelos: 'Nuevo modelo',
                lineas: 'Nueva línea',
                categorias: 'Nuevo concepto',
                unidades: 'Nueva unidad',
                motivos: 'Nuevo motivo'
            };
            const listLabels = {
                marcas: 'Marcas',
                modelos: 'Modelos',
                lineas: 'Líneas',
                categorias: 'Conceptos',
                unidades: 'Unidades',
                motivos: 'Motivos'
            };
            const rutas = {
                baseData: function (tipo) { return '{{ url('/desktop/operacion/catalogo-comercial/catalogos') }}/' + tipo + '/data'; },
                baseShow: function (tipo, id) { return '{{ url('/desktop/operacion/catalogo-comercial/catalogos') }}/' + tipo + '/' + id; },
                baseStore: function (tipo) { return '{{ url('/desktop/operacion/catalogo-comercial/catalogos') }}/' + tipo; },
                baseUpdate: function (tipo, id) { return '{{ url('/desktop/operacion/catalogo-comercial/catalogos') }}/' + tipo + '/' + id; },
                baseEstatus: function (tipo, id) { return '{{ url('/desktop/operacion/catalogo-comercial/catalogos') }}/' + tipo + '/' + id + '/estatus'; },
                baseDelete: function (tipo, id) { return '{{ url('/desktop/operacion/catalogo-comercial/catalogos') }}/' + tipo + '/' + id; },
                modelosData: '{{ route('desktop.operacion.catalogo_comercial.modelos.data') }}',
                modeloShow: function (id) { return '{{ url('/desktop/operacion/catalogo-comercial/modelos') }}/' + id; },
                modeloStore: '{{ route('desktop.operacion.catalogo_comercial.modelos.store') }}',
                modeloUpdate: function (id) { return '{{ url('/desktop/operacion/catalogo-comercial/modelos') }}/' + id; },
                modeloEstatus: function (id) { return '{{ url('/desktop/operacion/catalogo-comercial/modelos') }}/' + id + '/estatus'; },
                modeloDelete: function (id) { return '{{ url('/desktop/operacion/catalogo-comercial/modelos') }}/' + id; }
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
                return '<span class="desktop-status ' + (active ? 'desktop-status--active' : 'desktop-status--inactive') + '">' +
                    (active ? 'Activo' : 'Inactivo') + '</span>';
            }

            function syncTypeUI() {
                $('[data-catalog-type]').removeClass('desktop-btn--active').removeAttr('aria-current');
                $('[data-catalog-type="' + currentType + '"]').addClass('desktop-btn--active').attr('aria-current', 'page');
                $('#catalogo-base-meta').text('Catálogo activo: ' + (listLabels[currentType] || 'Catálogos'));
                $('#btn-nuevo-base').html(
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5v14"/></svg>' +
                    (createLabels[currentType] || 'Nuevo registro')
                );
            }

            function syncCatalogFormVisibility() {
                const isUnidad = currentType === 'unidades';
                const isConcepto = currentType === 'categorias';

                $('#cat-codigo-wrap').prop('hidden', !isUnidad);
                $('#cat-unidad-extra').prop('hidden', !isUnidad);
                $('#cat-categoria-linea-wrap').prop('hidden', !isConcepto);
                $('#cat_codigo').prop('required', isUnidad);
                $('#cat_lna_id').prop('required', isConcepto);
            }

            function renderActions(row) {
                const isModel = currentType === 'modelos';
                const id = isModel ? row.mdl_id : row.id;
                const name = isModel ? row.mdl_nombre : row.nombre;
                const status = isModel ? row.mdl_estatus : row.estatus;
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

            function tableHeaders() {
                if (currentType === 'modelos') {
                    return ['Modelo', 'Clave', 'Marcas', 'Estatus', 'Acciones'];
                }

                if (currentType === 'categorias') {
                    return ['Concepto', 'Línea', 'Clave', 'Estatus', 'Acciones'];
                }

                return ['Registro', 'Código', 'Clave', 'Estatus', 'Acciones'];
            }

            function tableColumns() {
                if (currentType === 'modelos') {
                    return [
                        {
                            data: 'mdl_nombre',
                            render: function (value, type, row) {
                                return '<div class="desktop-cell-primary">' +
                                    '<span class="desktop-avatar-sm">' + escapeHtml(initials(value)) + '</span>' +
                                    '<span><span class="desktop-list__name">' + escapeHtml(value) + '</span>' +
                                    '<span class="desktop-list__meta">ID ' + escapeHtml(row.mdl_id) + '</span></span></div>';
                            }
                        },
                        {
                            data: 'mdl_clave',
                            render: function (value) {
                                return '<span style="font-weight:600;">' + escapeHtml(value || '-') + '</span>';
                            }
                        },
                        {
                            data: 'marcas',
                            render: function (value, type, row) {
                                const brands = Array.isArray(value) ? value : [];
                                if (!brands.length) return '<span class="desktop-list__meta">Sin marcas asociadas</span>';

                                const pills = brands.slice(0, 2).map(function (brand) {
                                    return '<span class="desktop-pill desktop-pill--brand">' + escapeHtml(brand.nombre) + '</span>';
                                });

                                if (brands.length > 2) {
                                    pills.push('<span class="desktop-pill desktop-pill--more">+' + (brands.length - 2) + '</span>');
                                }

                                return '<div class="desktop-pill-list">' + pills.join('') + '</div>' +
                                    '<span class="desktop-list__meta">' + escapeHtml(row.marcas_texto) + '</span>';
                            }
                        },
                        { data: 'mdl_estatus', render: renderStatus },
                        { data: null, orderable: false, searchable: false, render: renderActions }
                    ];
                }

                if (currentType === 'categorias') {
                    return [
                        {
                            data: 'nombre',
                            render: function (value, type, row) {
                                return '<div class="desktop-cell-primary">' +
                                    '<span class="desktop-avatar-sm">' + escapeHtml(initials(value)) + '</span>' +
                                    '<span><span class="desktop-list__name">' + escapeHtml(value) + '</span>' +
                                    '<span class="desktop-list__meta">ID ' + escapeHtml(row.id) + '</span></span></div>';
                            }
                        },
                        {
                            data: 'linea',
                            render: function (value) {
                                return value
                                    ? '<span class="desktop-list__name">' + escapeHtml(value) + '</span>'
                                    : '<span class="desktop-list__meta">Sin línea</span>';
                            }
                        },
                        {
                            data: 'clave',
                            render: function (value) {
                                return '<span style="font-weight:600;">' + escapeHtml(value || '-') + '</span>';
                            }
                        },
                        { data: 'estatus', render: renderStatus },
                        { data: null, orderable: false, searchable: false, render: renderActions }
                    ];
                }

                return [
                    {
                        data: 'nombre',
                        render: function (value, type, row) {
                            let meta = 'ID ' + escapeHtml(row.id);

                            if (currentType === 'unidades') {
                                meta = escapeHtml((row.tipo_cantidad || 'entero').charAt(0).toUpperCase() + (row.tipo_cantidad || 'entero').slice(1)) +
                                    (row.es_predeterminada ? ' · Predeterminada' : '');
                            }

                            return '<div class="desktop-cell-primary">' +
                                '<span class="desktop-avatar-sm">' + escapeHtml(initials(value)) + '</span>' +
                                '<span><span class="desktop-list__name">' + escapeHtml(value) + '</span>' +
                                '<span class="desktop-list__meta">' + meta + '</span></span></div>';
                        }
                    },
                    {
                        data: 'codigo',
                        render: function (value) {
                            return value
                                ? '<span class="desktop-list__name">' + escapeHtml(value) + '</span>'
                                : '<span class="desktop-list__meta">-</span>';
                        }
                    },
                    {
                        data: 'clave',
                        render: function (value) {
                            return '<span style="font-weight:600;">' + escapeHtml(value || '-') + '</span>';
                        }
                    },
                    { data: 'estatus', render: renderStatus },
                    { data: null, orderable: false, searchable: false, render: renderActions }
                ];
            }

            function renderCustomFooter() {
                if (!baseTable) return;

                const info = baseTable.page.info();
                const total = info.recordsDisplay;
                const label = listLabels[currentType] || 'registros';

                if (!total) {
                    $('#desktop-catalogos-base-info').text('Mostrando 0 ' + label.toLowerCase());
                    $('#desktop-catalogos-base-pagination').empty();
                    return;
                }

                $('#desktop-catalogos-base-info').text(
                    'Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + total + ' ' + label.toLowerCase()
                );

                const buttons = [];
                buttons.push({ label: '‹', page: 'previous', disabled: info.page === 0 });
                for (let i = 0; i < info.pages; i += 1) {
                    buttons.push({ label: String(i + 1), page: i, active: i === info.page });
                }
                buttons.push({ label: '›', page: 'next', disabled: info.page >= info.pages - 1 });

                $('#desktop-catalogos-base-pagination').html(buttons.map(function (button) {
                    const classes = [
                        'desktop-pager__btn',
                        button.active ? 'is-active' : '',
                        button.disabled ? 'is-disabled' : ''
                    ].filter(Boolean).join(' ');

                    return '<button type="button" class="' + classes + '" data-page="' + button.page + '"' +
                        (button.disabled ? ' disabled' : '') + '>' + button.label + '</button>';
                }).join(''));
            }

            function destroyTable() {
                if ($.fn.DataTable.isDataTable('#desktop-catalogos-base-table')) {
                    $('#desktop-catalogos-base-table').DataTable().destroy();
                }
                $('#desktop-catalogos-base-head').html('');
                $('#desktop-catalogos-base-table tbody').remove();
                baseTable = null;
            }

            function buildTable() {
                destroyTable();

                $('#desktop-catalogos-base-head').html(tableHeaders().map(function (header, index) {
                    const style = index === 4 ? ' style="width:56px; text-align:right;"' : (index === 3 ? ' style="width:104px;"' : '');
                    return '<th' + style + '>' + header + '</th>';
                }).join(''));

                baseTable = $table.DataTable({
                    ajax: {
                        url: currentType === 'modelos' ? rutas.modelosData : rutas.baseData(currentType),
                        data: function () {
                            return {
                                buscar: $('#base-search').val(),
                                estatus: $('#base-estatus').val()
                            };
                        },
                        dataSrc: 'data'
                    },
                    processing: true,
                    deferRender: true,
                    responsive: false,
                    autoWidth: false,
                    pageLength: Number($('#base-length').val() || 100),
                    lengthChange: false,
                    searching: false,
                    order: [[0, 'asc']],
                    dom: 'rt',
                    language: {
                        processing: 'Cargando...',
                        emptyTable: 'No hay registros disponibles',
                        zeroRecords: 'No se encontraron coincidencias'
                    },
                    columns: tableColumns(),
                    initComplete: renderCustomFooter,
                    drawCallback: renderCustomFooter
                });
            }

            function reloadTable(resetPaging) {
                if (!baseTable) return;
                baseTable.ajax.reload(null, !resetPaging);
            }

            function resetCatalogForm() {
                $catalogForm.get(0).reset();
                $('#cat_id').val('');
                $('#cat_estatus').val('activo');
                $('#cat_tipo_cantidad').val('entero');
                $('#desktop-catalogo-modal-title').text(createLabels[currentType] || 'Nuevo registro');
                $('#btn-guardar-catalogo').text('Guardar registro');
                syncCatalogFormVisibility();
            }

            function resetModelForm() {
                $modelForm.get(0).reset();
                $('#mdl_id').val('');
                $('#mdl_estatus').val('activo');
                $('#desktop-modelo-modal-title').text('Nuevo modelo');
                $('#btn-guardar-modelo').text('Guardar modelo');
            }

            function openNewForm() {
                if (currentType === 'modelos') {
                    resetModelForm();
                    openModal($modelModal);
                    return;
                }

                resetCatalogForm();
                openModal($catalogModal);
            }

            function fillCatalogForm(data) {
                resetCatalogForm();
                $('#desktop-catalogo-modal-title').text('Editar ' + (labels[currentType] || 'registro'));
                $('#btn-guardar-catalogo').text('Guardar cambios');
                $('#cat_id').val(data.id || '');
                $('#cat_nombre').val(data.nombre || '');
                $('#cat_codigo').val(data.codigo || '');
                $('#cat_estatus').val(data.estatus || 'activo');
                $('#cat_lna_id').val(data.lna_id || '');
                $('#cat_tipo_cantidad').val(data.tipo_cantidad || 'entero');
                $('#cat_es_predeterminada').prop('checked', Boolean(data.es_predeterminada));
            }

            function fillModelForm(data) {
                resetModelForm();
                $('#desktop-modelo-modal-title').text('Editar modelo');
                $('#btn-guardar-modelo').text('Guardar cambios');
                $('#mdl_id').val(data.mdl_id || '');
                $('#mdl_nombre').val(data.mdl_nombre || '');
                $('#mdl_clave').val(data.mdl_clave || '');
                $('#mdl_estatus').val(data.mdl_estatus || 'activo');
                const selected = Array.isArray(data.marca_ids) ? data.marca_ids.map(Number) : [];
                $('#desktop-modelo-form input[name="marca_ids[]"]').each(function () {
                    $(this).prop('checked', selected.includes(Number(this.value)));
                });
            }

            function renderDetail(title, fields) {
                $('#desktop-detail-title').text(title);
                $('#desktop-detail-grid').html(fields.map(function (field) {
                    return '<div class="desktop-field ' + (field.full ? 'desktop-field--full' : '') + '">' +
                        '<label>' + escapeHtml(field.label) + '</label>' +
                        '<input type="text" readonly value="' + escapeHtml(field.value) + '">' +
                        '</div>';
                }).join(''));
                openModal($detailModal);
            }

            function openConfirm(message, callback) {
                confirmAction = callback;
                $('#desktop-confirm-copy').text(message);
                openModal($confirmModal);
            }

            function loadRecord(id, mode) {
                const request = currentType === 'modelos'
                    ? $.getJSON(rutas.modeloShow(id))
                    : $.getJSON(rutas.baseShow(currentType, id));

                request.done(function (response) {
                    const data = response.data || {};

                    if (mode === 'edit') {
                        if (currentType === 'modelos') {
                            fillModelForm(data);
                            openModal($modelModal);
                        } else {
                            fillCatalogForm(data);
                            openModal($catalogModal);
                        }
                        return;
                    }

                    if (currentType === 'modelos') {
                        renderDetail('Detalle de modelo', [
                            { label: 'Modelo', value: data.mdl_nombre || '', full: true },
                            { label: 'Clave', value: data.mdl_clave || '-' },
                            { label: 'Estatus', value: data.mdl_estatus === 'activo' ? 'Activo' : 'Inactivo' },
                            { label: 'Marcas asociadas', value: data.marcas_texto || '-', full: true }
                        ]);
                        return;
                    }

                    const fields = [
                        { label: labels[currentType] || 'Registro', value: data.nombre || '', full: true },
                        { label: 'Clave', value: data.clave || '-' },
                        { label: 'Estatus', value: data.estatus === 'activo' ? 'Activo' : 'Inactivo' }
                    ];

                    if (currentType === 'categorias') {
                        fields.splice(1, 0, { label: 'Línea', value: data.linea || '-' });
                    }

                    if (currentType === 'unidades') {
                        fields.splice(1, 0, { label: 'Código', value: data.codigo || '-' });
                        fields.push({ label: 'Tipo de cantidad', value: data.tipo_cantidad === 'decimal' ? 'Decimal' : 'Entero' });
                        fields.push({ label: 'Predeterminada', value: data.es_predeterminada ? 'Sí' : 'No' });
                    }

                    renderDetail('Detalle de ' + (labels[currentType] || 'registro'), fields);
                }).fail(function (xhr) {
                    showFeedback('error', parseError(xhr));
                });
            }

            function toggleRecord(id, nextStatus) {
                const request = currentType === 'modelos'
                    ? $.ajax({ url: rutas.modeloEstatus(id), method: 'PATCH', data: { mdl_estatus: nextStatus } })
                    : $.ajax({ url: rutas.baseEstatus(currentType, id), method: 'PATCH', data: { estatus: nextStatus } });

                request.done(function (response) {
                    showFeedback('success', response.message || 'Estatus actualizado correctamente.');
                    reloadTable(false);
                }).fail(function (xhr) {
                    showFeedback('error', parseError(xhr));
                });
            }

            function deleteRecord(id, name) {
                openConfirm('Se eliminará "' + name + '". Esta acción no se puede deshacer.', function () {
                    const request = currentType === 'modelos'
                        ? $.ajax({ url: rutas.modeloDelete(id), method: 'DELETE' })
                        : $.ajax({ url: rutas.baseDelete(currentType, id), method: 'DELETE' });

                    request.done(function (response) {
                        showFeedback('success', response.message || 'Registro eliminado correctamente.');
                        reloadTable(true);
                    }).fail(function (xhr) {
                        showFeedback('error', parseError(xhr));
                    });
                });
            }

            buildTable();
            syncTypeUI();

            $('[data-catalog-type]').on('click', function () {
                const next = String($(this).data('catalogType') || '');
                if (!next || next === currentType) return;
                currentType = next;
                syncTypeUI();
                buildTable();
            });

            $('#btn-nuevo-base').on('click', openNewForm);
            $('#btn-recargar-base').on('click', function () { reloadTable(true); });
            $('#base-estatus').on('change', function () { reloadTable(true); });
            $('#base-search').on('input', function () { reloadTable(true); });
            $('#base-length').on('change', function () {
                if (!baseTable) return;
                baseTable.page.len(Number(this.value)).draw();
            });
            $('#desktop-catalogos-base-pagination').on('click', '.desktop-pager__btn', function () {
                if ($(this).is(':disabled') || !baseTable) return;
                baseTable.page($(this).data('page')).draw('page');
            });

            $catalogForm.on('submit', function (event) {
                event.preventDefault();
                const recordId = $('#cat_id').val();
                const request = $.ajax({
                    url: recordId ? rutas.baseUpdate(currentType, recordId) : rutas.baseStore(currentType),
                    method: recordId ? 'PUT' : 'POST',
                    data: $catalogForm.serialize()
                });

                request.done(function (response) {
                    closeModal($catalogModal);
                    showFeedback('success', response.message || 'Registro guardado correctamente.');
                    reloadTable(true);
                }).fail(function (xhr) {
                    showFeedback('error', parseError(xhr));
                });
            });

            $modelForm.on('submit', function (event) {
                event.preventDefault();
                const recordId = $('#mdl_id').val();
                const request = $.ajax({
                    url: recordId ? rutas.modeloUpdate(recordId) : rutas.modeloStore,
                    method: recordId ? 'PUT' : 'POST',
                    data: $modelForm.serialize()
                });

                request.done(function (response) {
                    closeModal($modelModal);
                    showFeedback('success', response.message || 'Modelo guardado correctamente.');
                    reloadTable(true);
                }).fail(function (xhr) {
                    showFeedback('error', parseError(xhr));
                });
            });

            $(document).on('click', '.btn-edit-record', function () {
                loadRecord($(this).data('id'), 'edit');
            });

            $(document).on('click', '.btn-view-record', function () {
                loadRecord($(this).data('id'), 'view');
            });

            $(document).on('click', '.btn-toggle-record', function () {
                toggleRecord($(this).data('id'), $(this).data('next'));
            });

            $(document).on('click', '.btn-delete-record', function () {
                deleteRecord($(this).data('id'), $(this).data('name'));
            });

            $('[data-close-catalogo-modal]').on('click', function () { closeModal($catalogModal); });
            $('[data-close-modelo-modal]').on('click', function () { closeModal($modelModal); });
            $('[data-close-detail-modal]').on('click', function () { closeModal($detailModal); });
            $('[data-close-confirm-modal]').on('click', function () { closeModal($confirmModal); });
            $('#desktop-confirm-accept').on('click', function () {
                const action = confirmAction;
                confirmAction = null;
                closeModal($confirmModal);
                if (typeof action === 'function') action();
            });

            $catalogModal.on('click', function (event) { if (event.target === this) closeModal($catalogModal); });
            $modelModal.on('click', function (event) { if (event.target === this) closeModal($modelModal); });
            $detailModal.on('click', function (event) { if (event.target === this) closeModal($detailModal); });
            $confirmModal.on('click', function (event) { if (event.target === this) closeModal($confirmModal); });
        })();
    </script>
@endpush
