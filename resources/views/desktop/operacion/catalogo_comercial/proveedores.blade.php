@extends('layouts.desktop')

@section('title', 'Proveedores')

@push('desktop-vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
@endpush

@push('desktop-styles')
    <style>
        .desktop-proveedor-contacts {
            display: grid;
            gap: 8px;
        }
        .desktop-proveedor-contact {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 8px;
            align-items: center;
        }
        .desktop-proveedor-contact button {
            min-width: 38px;
            padding-inline: 0;
        }
        .desktop-proveedor-card {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .desktop-proveedor-card__avatar {
            width: 42px;
            height: 42px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: .82rem;
            color: #fff;
            background: linear-gradient(180deg, #5f8ff6 0%, #3b73e0 100%);
            flex: 0 0 auto;
        }
    </style>
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        @php($activeSubmenu = 'proveedores')
        @include('desktop.operacion.catalogo_comercial._subnav')
        @if($permisosUI['crear'])
            <span class="desktop-toolbar__divider"></span>
            <button type="button" class="desktop-btn desktop-btn--primary" id="btn-nuevo-proveedor">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5v14"/></svg>
                Nuevo proveedor
            </button>
        @endif
        <button type="button" class="desktop-btn desktop-btn--ghost" id="btn-recargar-proveedores">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
            Actualizar
        </button>
    </div>
    <div class="desktop-toolbar__group">
        <select class="desktop-toolbar__select" id="proveedores-estatus">
            <option value="">Todos los estatus</option>
            <option value="activo">Activos</option>
            <option value="inactivo">Inactivos</option>
        </select>
        <select class="desktop-toolbar__select" id="proveedores-length">
            <option value="25">25 por página</option>
            <option value="50">50 por página</option>
            <option value="100" selected>100 por página</option>
        </select>
        <input type="search" id="proveedores-search" class="desktop-toolbar__search" placeholder="Buscar proveedor">
    </div>
@endsection

@section('content')
    <section class="desktop-pane">
        <div class="desktop-list-wrap">
            <table id="desktop-proveedores-table" class="desktop-list">
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th>Asesor de ventas</th>
                        <th>Concepto</th>
                        <th>Contactos</th>
                        <th>RFC</th>
                        <th>Correo</th>
                        <th>Respuesta</th>
                        <th style="width:104px;">Estatus</th>
                        <th style="width:56px; text-align:right;">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
        <div class="desktop-list-foot">
            <div id="desktop-proveedores-info"></div>
            <div id="desktop-proveedores-pagination" class="desktop-pager"></div>
        </div>
    </section>

    <div class="desktop-modal" id="desktop-proveedor-modal" aria-hidden="true">
        <div class="desktop-modal__dialog" style="width:min(860px, 100%);">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="desktop-proveedor-modal-title">Nuevo proveedor</div>
                <button type="button" class="desktop-modal__close" data-close-proveedor-modal aria-label="Cerrar">&times;</button>
            </div>
            <form id="desktop-proveedor-form" data-ls-autocomplete="admin">
                <div class="desktop-modal__body">
                    <input type="hidden" id="prv_id" name="prv_id">
                    <div class="desktop-form-grid">
                        <div class="desktop-field desktop-field--full">
                            <label>Empresa</label>
                            <input type="text" id="prv_nombre_empresa" name="prv_nombre_empresa" maxlength="180" required>
                        </div>
                        <div class="desktop-field">
                            <label>Asesor de ventas</label>
                            <input type="text" id="prv_nombre_asesor_ventas" name="prv_nombre_asesor_ventas" maxlength="180">
                        </div>
                        <div class="desktop-field">
                            <label>Concepto</label>
                            <input type="text" id="prv_categoria" name="prv_categoria" maxlength="120" placeholder="Ej. calzado, uniformes, accesorios">
                        </div>
                        <div class="desktop-field">
                            <label>Razón social</label>
                            <input type="text" id="prv_razon_social" name="prv_razon_social" maxlength="180">
                        </div>
                        <div class="desktop-field">
                            <label>RFC</label>
                            <input type="text" id="prv_rfc" name="prv_rfc" maxlength="13" style="text-transform:uppercase;">
                        </div>
                        <div class="desktop-field">
                            <label>Correo</label>
                            <input type="email" id="prv_correo" name="prv_correo" maxlength="160" placeholder="asesor@proveedor.com">
                        </div>
                        <div class="desktop-field">
                            <label>Condiciones de pago</label>
                            <input type="text" id="prv_condiciones_pago" name="prv_condiciones_pago" maxlength="220" placeholder="Ej. Crédito 30 días">
                        </div>
                        <div class="desktop-field">
                            <label>Tiempo de respuesta</label>
                            <input type="text" id="prv_tiempo_respuesta" name="prv_tiempo_respuesta" maxlength="120" placeholder="Ej. 24 a 48 horas">
                        </div>
                        <div class="desktop-field">
                            <label>Estatus</label>
                            <select id="prv_estatus" name="prv_estatus">
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="desktop-field desktop-field--full">
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:8px;">
                                <label style="margin:0;">Números de contacto</label>
                                <button type="button" class="desktop-btn desktop-btn--default" id="btn-agregar-contacto">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5v14"/></svg>
                                    Agregar número
                                </button>
                            </div>
                            <div class="desktop-proveedor-contacts" id="proveedor-contactos-list"></div>
                            <small>Puedes registrar uno o varios teléfonos del proveedor.</small>
                        </div>
                    </div>
                </div>
                <div class="desktop-modal__foot">
                    <button type="button" class="desktop-btn desktop-btn--default" data-close-proveedor-modal>Cancelar</button>
                    <button type="submit" class="desktop-btn desktop-btn--primary" id="btn-guardar-proveedor">Guardar proveedor</button>
                </div>
            </form>
        </div>
    </div>

    <div class="desktop-modal" id="desktop-proveedor-detail-modal" aria-hidden="true">
        <div class="desktop-modal__dialog">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="desktop-proveedor-detail-title">Detalle del proveedor</div>
                <button type="button" class="desktop-modal__close" data-close-proveedor-detail-modal aria-label="Cerrar">&times;</button>
            </div>
            <div class="desktop-modal__body">
                <div class="desktop-form-grid" id="desktop-proveedor-detail-grid"></div>
            </div>
            <div class="desktop-modal__foot">
                <button type="button" class="desktop-btn desktop-btn--default" data-close-proveedor-detail-modal>Cerrar</button>
            </div>
        </div>
    </div>

    <div class="desktop-modal" id="desktop-proveedor-confirm-modal" aria-hidden="true">
        <div class="desktop-modal__dialog" style="width:min(440px, 100%);">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title">Confirmar acción</div>
                <button type="button" class="desktop-modal__close" data-close-proveedor-confirm-modal aria-label="Cerrar">&times;</button>
            </div>
            <div class="desktop-modal__body">
                <p id="desktop-proveedor-confirm-copy" style="margin:0; color:var(--text-2); line-height:1.55;"></p>
            </div>
            <div class="desktop-modal__foot">
                <button type="button" class="desktop-btn desktop-btn--default" data-close-proveedor-confirm-modal>Cancelar</button>
                <button type="button" class="desktop-btn desktop-btn--primary" id="desktop-proveedor-confirm-accept">Continuar</button>
            </div>
        </div>
    </div>

    <div class="desktop-feedback" id="desktop-proveedores-feedback"></div>
@endsection

@push('desktop-vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endpush

@push('desktop-scripts')
    <script>
        (function () {
            const $table = $('#desktop-proveedores-table');
            const $modal = $('#desktop-proveedor-modal');
            const $detailModal = $('#desktop-proveedor-detail-modal');
            const $confirmModal = $('#desktop-proveedor-confirm-modal');
            const $form = $('#desktop-proveedor-form');
            const $feedback = $('#desktop-proveedores-feedback');
            const $contactList = $('#proveedor-contactos-list');
            let providersTable = null;
            let confirmAction = null;

            $.ajaxSetup({
                cache: false,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const permisosUI = @json($permisosUI);
            const rutas = {
                data: '{{ route('desktop.operacion.catalogo_comercial.proveedores.data') }}',
                show: function (id) { return '{{ url('/desktop/operacion/catalogo-comercial/proveedores') }}/' + id; },
                store: '{{ route('desktop.operacion.catalogo_comercial.proveedores.store') }}',
                update: function (id) { return '{{ url('/desktop/operacion/catalogo-comercial/proveedores') }}/' + id; },
                estatus: function (id) { return '{{ url('/desktop/operacion/catalogo-comercial/proveedores') }}/' + id + '/estatus'; },
                destroy: function (id) { return '{{ url('/desktop/operacion/catalogo-comercial/proveedores') }}/' + id; }
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

            function openModal($target) {
                $target.addClass('is-open').attr('aria-hidden', 'false');
            }

            function closeModal($target) {
                $target.removeClass('is-open').attr('aria-hidden', 'true');
            }

            function renderStatus(value) {
                const active = value === 'activo';
                return '<span class="desktop-status ' + (active ? 'desktop-status--active' : 'desktop-status--inactive') + '">' + (active ? 'Activo' : 'Inactivo') + '</span>';
            }

            function appendContact(value) {
                const item = document.createElement('div');
                item.className = 'desktop-proveedor-contact';
                item.innerHTML = '' +
                    '<input type="text" name="numeros_contacto[]" maxlength="30" placeholder="Ej. +52 55 1234 5678" value="' + escapeHtml(value || '') + '">' +
                    '<button type="button" class="desktop-btn desktop-btn--default btn-remove-contact" aria-label="Quitar número">&times;</button>';
                $contactList.append(item);
            }

            function fillContacts(values) {
                $contactList.empty();
                const items = Array.isArray(values) ? values.filter(Boolean) : [];
                if (!items.length) {
                    appendContact('');
                    return;
                }
                items.forEach(function (value) {
                    appendContact(value);
                });
            }

            function serializeForm() {
                const payload = {
                    prv_nombre_empresa: $('#prv_nombre_empresa').val().trim(),
                    prv_nombre_asesor_ventas: $('#prv_nombre_asesor_ventas').val().trim(),
                    prv_categoria: $('#prv_categoria').val().trim(),
                    prv_razon_social: $('#prv_razon_social').val().trim(),
                    prv_rfc: $('#prv_rfc').val().trim().toUpperCase(),
                    prv_correo: $('#prv_correo').val().trim(),
                    prv_condiciones_pago: $('#prv_condiciones_pago').val().trim(),
                    prv_tiempo_respuesta: $('#prv_tiempo_respuesta').val().trim(),
                    prv_estatus: $('#prv_estatus').val(),
                    numeros_contacto: []
                };

                $contactList.find('input[name="numeros_contacto[]"]').each(function () {
                    const value = $(this).val().trim();
                    if (value) payload.numeros_contacto.push(value);
                });

                return payload;
            }

            function resetForm() {
                $form.get(0).reset();
                $('#prv_id').val('');
                $('#desktop-proveedor-modal-title').text('Nuevo proveedor');
                $('#btn-guardar-proveedor').text('Guardar proveedor');
                fillContacts([]);
            }

            function openCreateModal() {
                resetForm();
                openModal($modal);
            }

            function populateForm(data) {
                $('#prv_id').val(data.prv_id || '');
                $('#prv_nombre_empresa').val(data.prv_nombre_empresa || '');
                $('#prv_nombre_asesor_ventas').val(data.prv_nombre_asesor_ventas || '');
                $('#prv_categoria').val(data.prv_categoria || '');
                $('#prv_razon_social').val(data.prv_razon_social || '');
                $('#prv_rfc').val(data.prv_rfc || '');
                $('#prv_correo').val(data.prv_correo || '');
                $('#prv_condiciones_pago').val(data.prv_condiciones_pago || '');
                $('#prv_tiempo_respuesta').val(data.prv_tiempo_respuesta || '');
                $('#prv_estatus').val(data.prv_estatus || 'activo');
                fillContacts(data.numeros_contacto || []);
            }

            function renderActions(row) {
                const items = [];

                if (permisosUI.editar) {
                    items.push('<button type="button" class="desktop-menu__item btn-edit-provider" data-id="' + row.prv_id + '">' + ICONS.edit + 'Editar proveedor</button>');
                }

                items.push('<button type="button" class="desktop-menu__item btn-view-provider" data-id="' + row.prv_id + '">' + ICONS.view + 'Ver detalle</button>');

                if (permisosUI.inactivar) {
                    items.push('<div class="desktop-menu__divider"></div>');
                    items.push('<button type="button" class="desktop-menu__item btn-toggle-provider" data-id="' + row.prv_id + '" data-next="' + (row.prv_estatus === 'activo' ? 'inactivo' : 'activo') + '">' + ICONS.toggle + (row.prv_estatus === 'activo' ? 'Inactivar' : 'Activar') + '</button>');
                }

                if (permisosUI.eliminar) {
                    items.push('<button type="button" class="desktop-menu__item desktop-menu__item--danger btn-delete-provider" data-id="' + row.prv_id + '" data-name="' + escapeHtml(row.prv_nombre_empresa) + '">' + ICONS.remove + 'Eliminar</button>');
                }

                return '<div class="desktop-rowmenu">' +
                    '<button type="button" class="desktop-overflow" data-overflow aria-haspopup="true" aria-expanded="false" aria-label="Más acciones">' + ICONS.dots + '</button>' +
                    '<div class="desktop-menu">' + items.join('') + '</div>' +
                '</div>';
            }

            function providerColumns() {
                return [
                    {
                        data: 'prv_nombre_empresa',
                        render: function (value, type, row) {
                            if (type !== 'display') return value;
                            return '<div class="desktop-proveedor-card">' +
                                '<span class="desktop-proveedor-card__avatar">' + escapeHtml(initials(value)) + '</span>' +
                                '<span><span class="desktop-list__name">' + escapeHtml(value) + '</span>' +
                                '<span class="desktop-list__meta">' + escapeHtml(row.prv_clave || ('ID ' + row.prv_id)) + '</span></span></div>';
                        }
                    },
                    {
                        data: 'prv_nombre_asesor_ventas',
                        render: function (value) {
                            return value ? '<span class="desktop-list__name">' + escapeHtml(value) + '</span>' : '<span class="desktop-list__meta">Sin asesor asignado</span>';
                        }
                    },
                    {
                        data: 'prv_categoria',
                        render: function (value) {
                            return value ? '<span class="desktop-list__name">' + escapeHtml(value) + '</span>' : '<span class="desktop-list__meta">Sin concepto</span>';
                        }
                    },
                    {
                        data: null,
                        render: function (row) {
                            const contactos = Array.isArray(row.numeros_contacto) ? row.numeros_contacto : [];
                            if (!contactos.length) return '<span class="desktop-list__meta">Sin contactos</span>';
                            return '<span class="desktop-list__name">' + escapeHtml(contactos[0]) + '</span>' +
                                '<span class="desktop-list__meta">' + (contactos.length > 1 ? escapeHtml((contactos.length - 1) + ' adicional(es)') : '1 registrado') + '</span>';
                        }
                    },
                    {
                        data: 'prv_rfc',
                        render: function (value) {
                            return value ? '<span style="font-weight:600;">' + escapeHtml(value) + '</span>' : '<span class="desktop-list__meta">Sin RFC</span>';
                        }
                    },
                    {
                        data: 'prv_correo',
                        render: function (value) {
                            return value ? escapeHtml(value) : '<span class="desktop-list__meta">Sin correo</span>';
                        }
                    },
                    {
                        data: 'prv_tiempo_respuesta',
                        render: function (value, type, row) {
                            if (!value && !row.prv_condiciones_pago) return '<span class="desktop-list__meta">Sin datos</span>';
                            return '<span class="desktop-list__name">' + escapeHtml(value || 'Sin tiempo definido') + '</span>' +
                                '<span class="desktop-list__meta">' + escapeHtml(row.prv_condiciones_pago || 'Sin condiciones') + '</span>';
                        }
                    },
                    {
                        data: 'prv_estatus',
                        render: function (value) {
                            return renderStatus(value);
                        }
                    },
                    {
                        data: null,
                        className: 'text-end',
                        orderable: false,
                        searchable: false,
                        render: function (row) {
                            return renderActions(row);
                        }
                    }
                ];
            }

            function renderCustomFooter() {
                const tableInstance = providersTable || ($.fn.DataTable.isDataTable($table) ? $table.DataTable() : null);
                if (!tableInstance) return;

                const info = tableInstance.page.info();
                const total = info.recordsDisplay;

                if (!total) {
                    $('#desktop-proveedores-info').text('Mostrando 0 proveedores');
                    $('#desktop-proveedores-pagination').empty();
                    return;
                }

                $('#desktop-proveedores-info').text(
                    'Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + total + ' proveedores'
                );

                const buttons = [];
                buttons.push({ label: '‹', page: 'previous', disabled: info.page === 0 });
                for (let i = 0; i < info.pages; i += 1) {
                    buttons.push({ label: String(i + 1), page: i, active: i === info.page });
                }
                buttons.push({ label: '›', page: 'next', disabled: info.page >= info.pages - 1 });

                $('#desktop-proveedores-pagination').html(buttons.map(function (button) {
                    const classes = ['desktop-pager__btn', button.active ? 'is-active' : '', button.disabled ? 'is-disabled' : ''].filter(Boolean).join(' ');
                    return '<button type="button" class="' + classes + '" data-page="' + button.page + '"' + (button.disabled ? ' disabled' : '') + '>' + button.label + '</button>';
                }).join(''));
            }

            function buildTable() {
                providersTable = $table.DataTable({
                    ajax: {
                        url: rutas.data,
                        data: function (d) {
                            d.buscar = $('#proveedores-search').val();
                            d.estatus = $('#proveedores-estatus').val();
                        },
                        dataSrc: 'data'
                    },
                    columns: providerColumns(),
                    pageLength: Number($('#proveedores-length').val() || 100),
                    lengthChange: false,
                    searching: false,
                    ordering: false,
                    info: true,
                    pagingType: 'simple_numbers',
                    autoWidth: false,
                    dom: 'rt<"bottom">',
                    language: {
                        processing: 'Cargando...',
                        emptyTable: 'No hay proveedores registrados.',
                        zeroRecords: 'No se encontraron proveedores',
                        info: 'Mostrando _START_ a _END_ de _TOTAL_ proveedores',
                        infoEmpty: 'Mostrando 0 a 0 de 0 proveedores',
                        paginate: {
                            previous: '&lsaquo;',
                            next: '&rsaquo;'
                        }
                    },
                    processing: true,
                    deferRender: true,
                    responsive: false,
                    initComplete: renderCustomFooter,
                    drawCallback: renderCustomFooter
                });
            }

            function reloadTable(keepPage) {
                if (!providersTable) return;
                providersTable.ajax.reload(null, !keepPage);
            }

            function openDetail(data) {
                const fields = [
                    ['Empresa', data.prv_nombre_empresa || '—'],
                    ['Clave', data.prv_clave || ('ID ' + (data.prv_id || '—'))],
                    ['Asesor de ventas', data.prv_nombre_asesor_ventas || 'Sin asesor asignado'],
                    ['Concepto', data.prv_categoria || 'Sin concepto'],
                    ['Razón social', data.prv_razon_social || 'Sin razón social'],
                    ['RFC', data.prv_rfc || 'Sin RFC'],
                    ['Correo', data.prv_correo || 'Sin correo'],
                    ['Condiciones de pago', data.prv_condiciones_pago || 'Sin condiciones registradas'],
                    ['Tiempo de respuesta', data.prv_tiempo_respuesta || 'Sin tiempo definido'],
                    ['Contactos', Array.isArray(data.numeros_contacto) && data.numeros_contacto.length ? data.numeros_contacto.join(', ') : 'Sin contactos'],
                    ['Estatus', data.prv_estatus === 'activo' ? 'Activo' : 'Inactivo']
                ];
                $('#desktop-proveedor-detail-grid').html(fields.map(function (field) {
                    return '<div class="desktop-field"><label>' + escapeHtml(field[0]) + '</label><div>' + escapeHtml(field[1]) + '</div></div>';
                }).join(''));
                $('#desktop-proveedor-detail-title').text(data.prv_nombre_empresa || 'Detalle del proveedor');
                openModal($detailModal);
            }

            function requestConfirm(message, callback, danger) {
                confirmAction = callback;
                $('#desktop-proveedor-confirm-copy').text(message);
                $('#desktop-proveedor-confirm-accept')
                    .toggleClass('desktop-btn--danger', !!danger)
                    .toggleClass('desktop-btn--primary', !danger);
                openModal($confirmModal);
            }

            function fetchProvider(id, callback) {
                $.get(rutas.show(id))
                    .done(function (response) {
                        callback(response.data);
                    })
                    .fail(function (xhr) {
                        showFeedback('error', parseError(xhr));
                    });
            }

            $('#btn-nuevo-proveedor').on('click', openCreateModal);
            $('#btn-recargar-proveedores').on('click', function () {
                reloadTable(true);
            });
            $('#proveedores-search').on('input', function () {
                reloadTable(false);
            });
            $('#proveedores-estatus').on('change', function () {
                reloadTable(false);
            });
            $('#proveedores-length').on('change', function () {
                if (providersTable) {
                    providersTable.page.len(Number(this.value || 100)).draw();
                }
            });
            $('#desktop-proveedores-pagination').on('click', '.desktop-pager__btn', function () {
                if (!providersTable || this.disabled) return;
                const page = $(this).data('page');
                providersTable.page(page === 'previous' || page === 'next' ? page : Number(page)).draw('page');
            });

            $('#btn-agregar-contacto').on('click', function () {
                appendContact('');
            });

            $contactList.on('click', '.btn-remove-contact', function () {
                if ($contactList.children().length === 1) {
                    $(this).closest('.desktop-proveedor-contact').find('input').val('').trigger('focus');
                    return;
                }
                $(this).closest('.desktop-proveedor-contact').remove();
            });

            $form.on('submit', function (event) {
                event.preventDefault();
                const id = $('#prv_id').val();
                const isEdit = Boolean(id);
                const payload = serializeForm();

                $.ajax({
                    url: isEdit ? rutas.update(id) : rutas.store,
                    method: isEdit ? 'PUT' : 'POST',
                    data: payload,
                }).done(function (response) {
                    closeModal($modal);
                    reloadTable(true);
                    showFeedback('success', response.message || (isEdit ? 'Proveedor actualizado correctamente.' : 'Proveedor creado correctamente.'));
                }).fail(function (xhr) {
                    showFeedback('error', parseError(xhr));
                });
            });

            $table.on('click', '.btn-edit-provider', function () {
                const id = $(this).data('id');
                fetchProvider(id, function (data) {
                    resetForm();
                    populateForm(data);
                    $('#desktop-proveedor-modal-title').text('Editar proveedor');
                    $('#btn-guardar-proveedor').text('Guardar cambios');
                    openModal($modal);
                });
            });

            $table.on('click', '.btn-view-provider', function () {
                fetchProvider($(this).data('id'), openDetail);
            });

            $table.on('click', '.btn-toggle-provider', function () {
                const id = $(this).data('id');
                const next = $(this).data('next');
                requestConfirm(
                    (next === 'activo' ? 'Se activará' : 'Se inactivará') + ' el proveedor seleccionado.',
                    function () {
                        $.ajax({
                            url: rutas.estatus(id),
                            method: 'PATCH',
                            data: { prv_estatus: next }
                        }).done(function (response) {
                            closeModal($confirmModal);
                            reloadTable(true);
                            showFeedback('success', response.message || 'Estatus actualizado correctamente.');
                        }).fail(function (xhr) {
                            closeModal($confirmModal);
                            showFeedback('error', parseError(xhr));
                        });
                    }
                );
            });

            $table.on('click', '.btn-delete-provider', function () {
                const id = $(this).data('id');
                const name = $(this).data('name');
                requestConfirm(
                    'Se eliminará el proveedor "' + name + '". Esta acción no se puede deshacer.',
                    function () {
                        $.ajax({
                            url: rutas.destroy(id),
                            method: 'DELETE'
                        }).done(function (response) {
                            closeModal($confirmModal);
                            reloadTable(false);
                            showFeedback('success', response.message || 'Proveedor eliminado correctamente.');
                        }).fail(function (xhr) {
                            closeModal($confirmModal);
                            showFeedback('error', parseError(xhr));
                        });
                    },
                    true
                );
            });

            $('[data-close-proveedor-modal]').on('click', function () {
                closeModal($modal);
            });
            $('[data-close-proveedor-detail-modal]').on('click', function () {
                closeModal($detailModal);
            });
            $('[data-close-proveedor-confirm-modal]').on('click', function () {
                closeModal($confirmModal);
            });

            $('#desktop-proveedor-confirm-accept').on('click', function () {
                if (typeof confirmAction === 'function') {
                    confirmAction();
                }
            });

            $modal.on('click', function (event) {
                if (event.target === this) closeModal($modal);
            });
            $detailModal.on('click', function (event) {
                if (event.target === this) closeModal($detailModal);
            });
            $confirmModal.on('click', function (event) {
                if (event.target === this) closeModal($confirmModal);
            });

            buildTable();
            resetForm();
        })();
    </script>
@endpush
