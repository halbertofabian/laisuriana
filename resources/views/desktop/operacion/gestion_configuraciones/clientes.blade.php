@extends('layouts.desktop')

@section('title', 'Clientes')

@push('desktop-vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
@endpush

@push('desktop-styles')
    <style>
        /* El formulario debe participar en el layout flexible del diálogo para que el cuerpo tenga una altura limitada y pueda desplazarse. */
        #desktop-cliente-modal { padding: 32px 20px; overflow-y: auto; }
        #desktop-cliente-modal .desktop-modal__dialog { max-height: calc(100vh - 64px); }
        #desktop-cliente-form { display: flex; flex: 1 1 auto; flex-direction: column; min-height: 0; }
        #desktop-cliente-modal .desktop-modal__body { flex: 1 1 auto; min-height: 0; overflow-y: auto; padding-bottom: 16px; }

        .desktop-cliente-tabs {
            display: flex; gap: 4px; padding: 0 18px; border-bottom: 1px solid var(--divider);
            overflow-x: auto; flex: none;
        }
        .desktop-cliente-tab {
            appearance: none; border: 0; border-bottom: 2px solid transparent; background: transparent;
            color: var(--text-2); cursor: pointer; font: inherit; font-size: .84rem; font-weight: 600;
            padding: 10px 12px 9px; white-space: nowrap;
        }
        .desktop-cliente-tab:hover { color: var(--text); }
        .desktop-cliente-tab.is-active { color: var(--brand); border-bottom-color: var(--brand); }
        .desktop-cliente-panel { display: none; }
        .desktop-cliente-panel.is-active { display: block; }
    </style>
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        @php($activeSubmenu = 'clientes')
        @include('desktop.operacion.gestion_configuraciones._subnav')
        @if($permisosUI['cliente_crear'])
            <span class="desktop-toolbar__divider"></span>
            <button type="button" class="desktop-btn desktop-btn--primary" id="btn-nuevo-cliente">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5v14"/></svg>
                Nuevo cliente
            </button>
        @endif
        <button type="button" class="desktop-btn desktop-btn--ghost" id="btn-recargar-clientes">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
            Actualizar
        </button>
    </div>
    <div class="desktop-toolbar__group">
        <select class="desktop-toolbar__select" id="clientes-estatus">
            <option value="">Todos los estatus</option>
            <option value="activo">Activos</option>
            <option value="inactivo">Inactivos</option>
        </select>
        <select class="desktop-toolbar__select" id="clientes-length">
            <option value="10">10 por página</option>
            <option value="25">25 por página</option>
            <option value="50">50 por página</option>
        </select>
        <input type="search" id="clientes-search" class="desktop-toolbar__search" placeholder="Buscar cliente">
    </div>
@endsection

@section('content')
    <section class="desktop-pane">
        <div class="desktop-list-wrap">
            <table id="desktop-clientes-table" class="desktop-list">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Descuento</th>
                        <th>Contacto</th>
                        <th>Documentos</th>
                        <th>Dirección</th>
                        <th style="width:104px;">Estatus</th>
                        <th style="width:56px; text-align:right;">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
        <div class="desktop-list-foot">
            <div id="desktop-clientes-info"></div>
            <div id="desktop-clientes-pagination" class="desktop-pager"></div>
        </div>
    </section>

    <div class="desktop-modal" id="desktop-cliente-modal" aria-hidden="true">
        <div class="desktop-modal__dialog" style="width:min(1040px,100%);">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="desktop-cliente-modal-title">Nuevo cliente</div>
                <button type="button" class="desktop-modal__close" data-close-cliente-modal aria-label="Cerrar">&times;</button>
            </div>

            <div class="desktop-cliente-tabs" role="tablist" aria-label="Secciones del cliente">
                <button type="button" class="desktop-cliente-tab is-active" role="tab" aria-selected="true" aria-controls="cliente-tab-persona" id="cliente-tab-persona-button" data-cliente-tab="cliente-tab-persona">Datos generales</button>
                <button type="button" class="desktop-cliente-tab" role="tab" aria-selected="false" aria-controls="cliente-tab-contacto" id="cliente-tab-contacto-button" data-cliente-tab="cliente-tab-contacto">Contacto</button>
                <button type="button" class="desktop-cliente-tab" role="tab" aria-selected="false" aria-controls="cliente-tab-documentos" id="cliente-tab-documentos-button" data-cliente-tab="cliente-tab-documentos">Documentos</button>
                <button type="button" class="desktop-cliente-tab" role="tab" aria-selected="false" aria-controls="cliente-tab-direccion" id="cliente-tab-direccion-button" data-cliente-tab="cliente-tab-direccion">Dirección</button>
            </div>

            <form id="desktop-cliente-form" data-ls-autocomplete="admin">
                <div class="desktop-modal__body">
                    <input type="hidden" name="cli_id" id="cli_id">
                    <input type="hidden" name="cli_rfc" id="cli_rfc">
                    <input type="hidden" name="cli_curp" id="cli_curp">
                    <input type="hidden" name="cli_ine" id="cli_ine">

                    <section class="desktop-cliente-panel is-active" id="cliente-tab-persona" role="tabpanel" aria-labelledby="cliente-tab-persona-button">
                    <div class="desktop-form-grid">
                        <div class="desktop-field">
                            <label>Nombre</label>
                            <input type="text" name="cli_nombre" id="cli_nombre" maxlength="120" required>
                        </div>
                        <div class="desktop-field">
                            <label>Apellido paterno</label>
                            <input type="text" name="cli_apellido_paterno" id="cli_apellido_paterno" maxlength="120">
                        </div>
                        <div class="desktop-field">
                            <label>Apellido materno</label>
                            <input type="text" name="cli_apellido_materno" id="cli_apellido_materno" maxlength="120">
                        </div>
                        <div class="desktop-field">
                            <label>Estatus</label>
                            <select name="cli_estatus" id="cli_estatus" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="desktop-field desktop-field--full">
                            <label>Razón social</label>
                            <input type="text" name="cli_razon_social" id="cli_razon_social" maxlength="180">
                        </div>
                        <div class="desktop-field">
                            <label>Fecha de nacimiento</label>
                            <input type="date" name="cli_fecha_nacimiento" id="cli_fecha_nacimiento">
                        </div>
                        <div class="desktop-field">
                            <label>Descuento %</label>
                            <input type="number" name="cli_descuento_default" id="cli_descuento_default" min="1" max="100" step="1" placeholder="1 a 100">
                            <small>Porcentaje predeterminado para las ventas de este cliente.</small>
                        </div>
                    </div>
                    </section>

                    <section class="desktop-cliente-panel" id="cliente-tab-contacto" role="tabpanel" aria-labelledby="cliente-tab-contacto-button">
                    <div class="desktop-field-section" style="margin-top:0; padding-top:0; border-top:0;">
                        <div class="desktop-field-section__title">Contacto</div>
                        <div class="desktop-field-section__hint">Información para comunicarse con el cliente.</div>
                    </div>

                    <div class="desktop-form-grid" style="margin-top:14px;">
                        <div class="desktop-field">
                            <label>Teléfono</label>
                            <input type="text" name="cli_telefono" id="cli_telefono" maxlength="25">
                        </div>
                        <div class="desktop-field">
                            <label>WhatsApp</label>
                            <input type="text" name="cli_whatsapp" id="cli_whatsapp" maxlength="25">
                        </div>
                        <div class="desktop-field">
                            <label>Correo electrónico</label>
                            <input type="email" name="cli_email" id="cli_email" maxlength="140">
                        </div>
                    </div>
                    </section>

                    <section class="desktop-cliente-panel" id="cliente-tab-documentos" role="tabpanel" aria-labelledby="cliente-tab-documentos-button">
                    <div class="desktop-field-section" style="margin-top:0; padding-top:0; border-top:0;">
                        <div class="desktop-field-section__title">Documentos</div>
                        <div class="desktop-field-section__hint">Registra el documento principal del cliente.</div>
                    </div>

                    <div class="desktop-form-grid" style="margin-top:14px;">
                        <div class="desktop-field">
                            <label>Tipo de documento</label>
                            <select id="doc_tipo">
                                <option value="">Selecciona</option>
                                <option value="rfc">RFC</option>
                                <option value="curp">CURP</option>
                                <option value="ine">INE</option>
                            </select>
                        </div>
                        <div class="desktop-field">
                            <label>Documento</label>
                            <input type="text" id="doc_valor" maxlength="30">
                        </div>
                    </div>
                    </section>

                    <section class="desktop-cliente-panel" id="cliente-tab-direccion" role="tabpanel" aria-labelledby="cliente-tab-direccion-button">
                    <div class="desktop-field-section" style="margin-top:0; padding-top:0; border-top:0;">
                        <div class="desktop-field-section__title">Dirección</div>
                        <div class="desktop-field-section__hint">Consulta el código postal y completa la dirección fiscal o de contacto.</div>
                    </div>

                    <div class="desktop-form-grid" style="margin-top:14px;">
                        <div class="desktop-field">
                            <label>Código postal</label>
                            <input type="text" name="cli_cp" id="cli_cp" maxlength="10">
                        </div>
                        <div class="desktop-field">
                            <label>Colonia</label>
                            <select name="cli_colonia" id="cli_colonia">
                                <option value="">Selecciona</option>
                            </select>
                        </div>
                        <div class="desktop-field">
                            <label>Tipo de asentamiento</label>
                            <input type="text" name="cli_tipo_asentamiento" id="cli_tipo_asentamiento" maxlength="80">
                        </div>
                        <div class="desktop-field">
                            <label>Municipio</label>
                            <input type="text" name="cli_municipio" id="cli_municipio" maxlength="120">
                        </div>
                        <div class="desktop-field">
                            <label>Estado</label>
                            <input type="text" name="cli_estado" id="cli_estado" maxlength="120">
                        </div>
                        <div class="desktop-field">
                            <label>Ciudad</label>
                            <input type="text" name="cli_ciudad" id="cli_ciudad" maxlength="120">
                        </div>
                        <div class="desktop-field desktop-field--full">
                            <label>Calle</label>
                            <input type="text" name="cli_calle" id="cli_calle" maxlength="180">
                        </div>
                        <div class="desktop-field">
                            <label>Número exterior</label>
                            <input type="text" name="cli_num_ext" id="cli_num_ext" maxlength="30">
                        </div>
                        <div class="desktop-field">
                            <label>Número interior</label>
                            <input type="text" name="cli_num_int" id="cli_num_int" maxlength="30">
                        </div>
                        <div class="desktop-field desktop-field--full">
                            <label>Referencias</label>
                            <textarea name="cli_referencias" id="cli_referencias" rows="3"></textarea>
                        </div>
                    </div>
                    </section>
                </div>

                <div class="desktop-modal__foot">
                    <button type="button" class="desktop-btn desktop-btn--default" data-close-cliente-modal>Cancelar</button>
                    <button type="submit" class="desktop-btn desktop-btn--primary" id="btn-guardar-cliente">Guardar cliente</button>
                </div>
            </form>
        </div>
    </div>

    <div class="desktop-feedback" id="desktop-clientes-feedback"></div>
@endsection

@push('desktop-vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endpush

@push('desktop-scripts')
    <script>
        (function () {
            const $table = $('#desktop-clientes-table');
            const $modal = $('#desktop-cliente-modal');
            const $form = $('#desktop-cliente-form');
            const $feedback = $('#desktop-clientes-feedback');
            let clientesTable = null;
            let cpRows = [];

            $.ajaxSetup({
                cache: false,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const permisosUI = @json($permisosUI);
            const rutas = {
                data: '{{ route('desktop.operacion.gestion_configuraciones.clientes.data') }}',
                show: function (id) { return '{{ url('/desktop/operacion/gestion-configuraciones/clientes') }}/' + id; },
                store: '{{ route('desktop.operacion.gestion_configuraciones.clientes.store') }}',
                update: function (id) { return '{{ url('/desktop/operacion/gestion-configuraciones/clientes') }}/' + id; },
                estatus: function (id) { return '{{ url('/desktop/operacion/gestion-configuraciones/clientes') }}/' + id + '/estatus'; },
                cpBuscar: '{{ route('desktop.operacion.gestion_configuraciones.clientes.cp.buscar') }}'
            };

            const ICONS = {
                edit: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>',
                toggle: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.2-8.5"/><path d="m9 11 3 3L22 4"/></svg>',
                dots: '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="19" cy="12" r="1.8"/></svg>'
            };

            function escapeHtml(v) {
                return String(v || '')
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

            function parseError(xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    const firstGroup = Object.values(xhr.responseJSON.errors)[0];
                    if (Array.isArray(firstGroup) && firstGroup[0]) return firstGroup[0];
                }

                return xhr.responseJSON?.message || 'No fue posible completar la operación.';
            }

            function openModal() {
                $modal.addClass('is-open').attr('aria-hidden', 'false');
            }

            function closeModal() {
                $modal.removeClass('is-open').attr('aria-hidden', 'true');
            }

            function renderStatus(value) {
                const className = value === 'activo' ? 'desktop-status--active' : 'desktop-status--inactive';
                const label = value === 'activo' ? 'Activo' : 'Inactivo';
                return '<span class="desktop-status ' + className + '">' + label + '</span>';
            }

            function activarTabCliente(tabId) {
                $('.desktop-cliente-tab').each(function () {
                    const active = $(this).data('cliente-tab') === tabId;
                    $(this).toggleClass('is-active', active).attr('aria-selected', active ? 'true' : 'false');
                });
                $('.desktop-cliente-panel').each(function () {
                    $(this).toggleClass('is-active', this.id === tabId);
                });
                $('#desktop-cliente-modal .desktop-modal__body').scrollTop(0);
            }

            function renderActions(row) {
                const toggleTo = row.cli_estatus === 'activo' ? 'inactivo' : 'activo';
                const toggleText = row.cli_estatus === 'activo' ? 'Inactivar' : 'Activar';

                return '' +
                    '<div class="desktop-rowmenu">' +
                        '<button type="button" class="desktop-overflow" data-overflow aria-haspopup="true" aria-expanded="false" aria-label="Más acciones">' + ICONS.dots + '</button>' +
                        '<div class="desktop-menu">' +
                            (permisosUI.cliente_editar
                                ? '<button type="button" class="desktop-menu__item btn-editar-cliente" data-id="' + row.cli_id + '">' + ICONS.edit + 'Editar</button>'
                                : '') +
                            (permisosUI.cliente_inactivar
                                ? '<div class="desktop-menu__divider"></div><button type="button" class="desktop-menu__item btn-toggle-cliente" data-id="' + row.cli_id + '" data-estatus="' + toggleTo + '">' + ICONS.toggle + toggleText + '</button>'
                                : '') +
                        '</div>' +
                    '</div>';
            }

            function sincronizarDocumento() {
                const tipo = ($('#doc_tipo').val() || '').toLowerCase();
                const valor = ($('#doc_valor').val() || '').trim().toUpperCase();
                $('#cli_rfc').val('');
                $('#cli_curp').val('');
                $('#cli_ine').val('');
                if (!tipo || !valor) return;
                if (tipo === 'rfc') $('#cli_rfc').val(valor);
                if (tipo === 'curp') $('#cli_curp').val(valor);
                if (tipo === 'ine') $('#cli_ine').val(valor);
            }

            function refrescarDependientesAsentamiento(colonia) {
                const found = cpRows.find(function (x) {
                    return (x.cp_asentamiento || '') === colonia;
                });
                if (!found) return;
                $('#cli_tipo_asentamiento').val(found.cp_tipo_asentamiento || '');
                $('#cli_municipio').val(found.cp_municipio || '');
                $('#cli_estado').val(found.cp_estado || '');
                $('#cli_ciudad').val(found.cp_ciudad || '');
            }

            async function buscarCP(enfocarColonia) {
                const cp = ($('#cli_cp').val() || '').trim();
                if (cp.length < 5) return;

                const response = await $.getJSON(rutas.cpBuscar, { codigo_postal: cp });
                cpRows = response.data || [];

                const options = ['<option value="">Selecciona</option>'].concat(
                    cpRows.map(function (row) {
                        return '<option value="' + escapeHtml(row.cp_asentamiento) + '">' +
                            escapeHtml(row.cp_asentamiento) + ' (' + escapeHtml(row.cp_tipo_asentamiento || 'Asentamiento') + ')' +
                        '</option>';
                    })
                );

                $('#cli_colonia').html(options.join(''));
                if (cpRows.length && enfocarColonia !== false) {
                    $('#cli_colonia').focus();
                }
            }

            function renderCustomFooter() {
                if (!clientesTable) return;

                const info = clientesTable.page.info();
                const total = info.recordsDisplay;

                if (!total) {
                    $('#desktop-clientes-info').text('Mostrando 0 clientes');
                    $('#desktop-clientes-pagination').empty();
                    return;
                }

                $('#desktop-clientes-info').text(
                    'Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + total + ' clientes'
                );

                const buttons = [];
                const current = info.page;
                const totalPages = info.pages;

                buttons.push({ label: '‹', page: 'previous', disabled: current === 0 });
                for (let i = 0; i < totalPages; i += 1) {
                    buttons.push({ label: String(i + 1), page: i, active: i === current });
                }
                buttons.push({ label: '›', page: 'next', disabled: current >= totalPages - 1 });

                const html = buttons.map(function (button) {
                    const classes = [
                        'desktop-pager__btn',
                        button.active ? 'is-active' : '',
                        button.disabled ? 'is-disabled' : ''
                    ].filter(Boolean).join(' ');

                    return '<button type="button" class="' + classes + '" data-page="' + button.page + '"' +
                        (button.disabled ? ' disabled' : '') + '>' + button.label + '</button>';
                }).join('');

                $('#desktop-clientes-pagination').html(html);
            }

            function limpiarFormulario() {
                $form.get(0).reset();
                $('#cli_id').val('');
                $('#cli_estatus').val('activo');
                $('#doc_tipo').val('');
                $('#doc_valor').val('');
                $('#cli_rfc').val('');
                $('#cli_curp').val('');
                $('#cli_ine').val('');
                cpRows = [];
                $('#cli_colonia').html('<option value="">Selecciona</option>');
                $('#desktop-cliente-modal-title').text('Nuevo cliente');
                $('#btn-guardar-cliente').text('Guardar cliente');
                activarTabCliente('cliente-tab-persona');
            }

            function initTable() {
                clientesTable = $table.DataTable({
                    ajax: {
                        url: rutas.data,
                        data: function () {
                            return {
                                buscar: $('#clientes-search').val(),
                                estatus: $('#clientes-estatus').val()
                            };
                        },
                        dataSrc: 'data'
                    },
                    processing: true,
                    deferRender: true,
                    responsive: false,
                    autoWidth: false,
                    pageLength: 10,
                    lengthChange: false,
                    searching: false,
                    order: [[0, 'asc']],
                    dom: 'rt',
                    language: {
                        processing: 'Cargando...',
                        emptyTable: 'No hay clientes registrados',
                        zeroRecords: 'No se encontraron clientes'
                    },
                    columns: [
                        {
                            data: 'nombre_completo',
                            render: function (value, type, row) {
                                return '' +
                                    '<div class="desktop-cell-primary">' +
                                        '<span class="desktop-avatar-sm">' + escapeHtml(initials(value)) + '</span>' +
                                        '<span>' +
                                            '<span class="desktop-list__name">' + escapeHtml(value) + '</span>' +
                                            '<span class="desktop-list__meta">' + escapeHtml(row.cli_razon_social || 'Sin razón social') + '</span>' +
                                        '</span>' +
                                    '</div>';
                            }
                        },
                        {
                            data: 'cli_descuento_default',
                            render: function (value) {
                                return value ? '<span style="font-weight:600;">' + escapeHtml(value) + '%</span>' : '<span class="desktop-list__meta">—</span>';
                            }
                        },
                        {
                            data: null,
                            render: function (row) {
                                return '<span class="desktop-list__name">' + escapeHtml(row.cli_telefono || 'Sin teléfono') + '</span>' +
                                    '<span class="desktop-list__meta">' + escapeHtml(row.cli_email || row.cli_whatsapp || 'Sin contacto adicional') + '</span>';
                            }
                        },
                        {
                            data: null,
                            render: function (row) {
                                return '<span class="desktop-list__name">RFC: ' + escapeHtml(row.cli_rfc || '—') + '</span>' +
                                    '<span class="desktop-list__meta">CURP: ' + escapeHtml(row.cli_curp || '—') + '</span>';
                            }
                        },
                        {
                            data: 'direccion',
                            render: function (value) {
                                return value ? escapeHtml(value) : '<span class="desktop-list__meta">Sin dirección</span>';
                            }
                        },
                        { data: 'cli_estatus', render: renderStatus },
                        { data: null, orderable: false, searchable: false, render: renderActions }
                    ],
                    initComplete: renderCustomFooter,
                    drawCallback: renderCustomFooter
                });
            }

            function reloadTable(resetPaging) {
                if (!clientesTable) return;
                clientesTable.ajax.reload(null, !resetPaging);
            }

            async function cargarCliente(id) {
                try {
                    const response = await $.getJSON(rutas.show(id));
                    const row = response.data || {};
                    limpiarFormulario();
                    $('#desktop-cliente-modal-title').text('Editar cliente');
                    $('#btn-guardar-cliente').text('Guardar cambios');

                    Object.keys(row).forEach(function (key) {
                        const $field = $('#' + key);
                        if ($field.length && row[key] !== null) {
                            $field.val(row[key]);
                        }
                    });

                    if (row.cli_rfc) {
                        $('#doc_tipo').val('rfc');
                        $('#doc_valor').val(row.cli_rfc);
                    } else if (row.cli_curp) {
                        $('#doc_tipo').val('curp');
                        $('#doc_valor').val(row.cli_curp);
                    } else if (row.cli_ine) {
                        $('#doc_tipo').val('ine');
                        $('#doc_valor').val(row.cli_ine);
                    }

                    if (row.cli_cp) {
                        await buscarCP(false);
                        if (row.cli_colonia) {
                            $('#cli_colonia').val(row.cli_colonia);
                        }
                    }

                    openModal();
                } catch (xhr) {
                    showFeedback('error', parseError(xhr));
                }
            }

            function cambiarEstatus(id, estatus) {
                $.ajax({
                    url: rutas.estatus(id),
                    method: 'PATCH',
                    data: { cli_estatus: estatus }
                }).done(function (response) {
                    showFeedback('success', response.message || 'Estatus actualizado correctamente.');
                    reloadTable(false);
                }).fail(function (xhr) {
                    showFeedback('error', parseError(xhr));
                });
            }

            initTable();

            $('#btn-nuevo-cliente').on('click', function () {
                limpiarFormulario();
                openModal();
            });

            $('#btn-recargar-clientes').on('click', function () {
                reloadTable(true);
            });

            $('#clientes-estatus').on('change', function () {
                reloadTable(true);
            });

            $('#clientes-length').on('change', function () {
                if (!clientesTable) return;
                clientesTable.page.len(Number(this.value)).draw();
            });

            $('#clientes-search').on('input', function () {
                reloadTable(true);
            });

            $('#cli_cp').on('blur', function () {
                buscarCP(true).catch(function () {});
            });

            $('#cli_colonia').on('change', function () {
                refrescarDependientesAsentamiento(this.value);
            });

            $('#desktop-clientes-pagination').on('click', '.desktop-pager__btn', function () {
                if ($(this).is(':disabled') || !clientesTable) return;
                clientesTable.page($(this).data('page')).draw('page');
            });

            $(document).on('click', '[data-close-cliente-modal]', function () {
                closeModal();
            });

            $modal.on('click', function (event) {
                if (event.target === this) closeModal();
            });

            $table.on('click', '.btn-editar-cliente', function () {
                cargarCliente($(this).data('id'));
            });

            $table.on('click', '.btn-toggle-cliente', function () {
                const id = $(this).data('id');
                const estatus = $(this).data('estatus');
                const promptText = estatus === 'activo'
                    ? '¿Deseas activar este cliente?'
                    : '¿Deseas inactivar este cliente?';

                DesktopUI.confirm({ title: 'Confirmar', message: promptText }).then(function (ok) {
                    if (!ok) return;
                    cambiarEstatus(id, estatus);
                });
            });

            $('.desktop-cliente-tab').on('click', function () {
                activarTabCliente($(this).data('cliente-tab'));
            });

            $form.on('submit', function (event) {
                event.preventDefault();
                sincronizarDocumento();

                const id = $('#cli_id').val();
                const url = id ? rutas.update(id) : rutas.store;

                $.ajax({
                    url: url,
                    method: id ? 'PUT' : 'POST',
                    data: $form.serialize()
                }).done(function (response) {
                    closeModal();
                    showFeedback('success', response.message || 'Cliente guardado correctamente.');
                    reloadTable(false);
                }).fail(function (xhr) {
                    showFeedback('error', parseError(xhr));
                });
            });
        })();
    </script>
@endpush
