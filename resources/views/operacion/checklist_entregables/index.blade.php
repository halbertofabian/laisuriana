@extends('layouts.app')

@section('title', 'Checklist de Entregables')

@push('vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <style>
        .chk-resumen {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
        }

        .chk-counter {
            border: 1px solid var(--ls-border);
            border-radius: 999px;
            padding: 0.22rem 0.62rem;
            font-size: 0.74rem;
            font-weight: 600;
            color: var(--ls-text-secondary);
            background: var(--ls-surface);
        }

        .chk-section-card {
            border: 1px solid var(--ls-border);
            border-radius: var(--ls-radius-lg);
            background: var(--ls-surface);
            box-shadow: var(--ls-shadow-sm);
        }

        .chk-section-card + .chk-section-card {
            margin-top: 0.9rem;
        }

        .chk-section-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.7rem;
            padding: 0.9rem 1rem;
            border-bottom: 1px solid var(--ls-border);
            background: var(--ls-surface-2);
        }

        .chk-section-title {
            margin: 0;
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--ls-text-primary);
        }

        .chk-section-desc {
            margin: 0.15rem 0 0;
            font-size: 0.8rem;
            color: var(--ls-text-muted);
        }

        .chk-item-table td,
        .chk-item-table th {
            vertical-align: top;
        }

        .chk-item-title {
            margin: 0;
            font-size: 0.84rem;
            font-weight: 700;
            color: var(--ls-text-primary);
        }

        .chk-item-meta {
            margin: 0.18rem 0 0;
            font-size: 0.76rem;
            color: var(--ls-text-muted);
            line-height: 1.4;
        }

        .chk-empty {
            border: 1px dashed var(--ls-border);
            border-radius: var(--ls-radius-lg);
            background: var(--ls-surface-2);
            padding: 1.1rem;
            color: var(--ls-text-muted);
            text-align: center;
            font-size: 0.84rem;
        }

        .chk-list-badge {
            margin-left: 0.45rem;
            font-size: 0.68rem;
            padding: 0.18rem 0.4rem;
        }
    </style>
@endpush

@section('content')
<x-section-header
    eyebrow="Operación"
    icon="tabler-checklist"
    title="Checklist de Entregables"
    subtitle="Registra y revisa avances por módulo para juntas con cliente."
/>

<div class="card">
    <div class="card-header pb-0">
        <ul class="nav nav-tabs card-header-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="chk-tab-listado-btn" data-bs-toggle="tab" data-bs-target="#chk-tab-listado" type="button" role="tab" aria-controls="chk-tab-listado" aria-selected="true">Listado</button>
            </li>
            <li class="nav-item d-none" id="chk-tab-resumen-item" role="presentation">
                <button class="nav-link" id="chk-tab-resumen-btn" data-bs-toggle="tab" data-bs-target="#chk-tab-resumen" type="button" role="tab" aria-controls="chk-tab-resumen" aria-selected="false">Resumen</button>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">
            <div class="tab-pane fade show active" id="chk-tab-listado" role="tabpanel" aria-labelledby="chk-tab-listado-btn">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <span class="fw-semibold">Checklists</span>
                    @if($permisosUI['crear'])
                        <button type="button" class="btn btn-sm btn-primary" id="btn-nuevo-checklist">
                            <i class="icon-base ti tabler-plus"></i> Nuevo
                        </button>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table" id="checklists-table">
                        <thead>
                            <tr>
                                <th>Checklist</th>
                                <th>Fecha</th>
                                <th>Estatus</th>
                                <th>Resumen</th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="chk-tab-resumen" role="tabpanel" aria-labelledby="chk-tab-resumen-btn">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <span class="fw-semibold">Detalle de revisión</span>
                    @if($permisosUI['crear'])
                        <button type="button" class="btn btn-sm btn-outline-primary d-none" id="btn-nueva-seccion">
                            <i class="icon-base ti tabler-layout-list"></i> Nueva sección
                        </button>
                    @endif
                </div>

                <form id="form-checklist" class="d-none">
                    <input type="hidden" id="chk_id" />
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="chk_nombre" name="chk_nombre" maxlength="180" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cliente / referencia</label>
                            <input type="text" class="form-control" id="chk_referencia" name="chk_referencia" maxlength="180">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fecha</label>
                            <input type="date" class="form-control" id="chk_fecha" name="chk_fecha" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Estatus general</label>
                            <select class="form-select" id="chk_estatus_general" name="chk_estatus_general">
                                <option value="pendiente">Pendiente</option>
                                <option value="en_revision">En revisión</option>
                                <option value="aprobado">Aprobado</option>
                                <option value="observado">Observado</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Observaciones generales</label>
                            <textarea class="form-control" id="chk_observaciones" name="chk_observaciones" rows="2" maxlength="5000"></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
                        <div class="chk-resumen" id="chk-resumen"></div>
                        @if($permisosUI['editar'])
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="icon-base ti tabler-device-floppy"></i> Guardar cabecera
                            </button>
                        @endif
                    </div>
                </form>

                <div id="chk-empty" class="chk-empty">
                    Selecciona un checklist para comenzar la captura o crear uno nuevo.
                </div>

                <div id="chk-secciones" class="d-none"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-checklist" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="form-nuevo-checklist">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo checklist</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label">Nombre</label>
                            <input type="text" class="form-control" name="chk_nombre" maxlength="180" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Fecha</label>
                            <input type="date" class="form-control" name="chk_fecha" value="{{ now()->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Cliente / referencia</label>
                            <input type="text" class="form-control" name="chk_referencia" maxlength="180">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Observaciones generales</label>
                            <textarea class="form-control" name="chk_observaciones" rows="3" maxlength="5000"></textarea>
                        </div>
                        <div class="col-md-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="usar_plantilla_base" name="usar_plantilla_base" checked>
                                <label class="form-check-label" for="usar_plantilla_base">
                                    Precargar estructura base de entregables
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear checklist</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-seccion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="form-seccion">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva sección</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Título</label>
                            <input type="text" class="form-control" name="chs_titulo" maxlength="160" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Orden</label>
                            <input type="number" class="form-control" name="chs_orden" min="1" step="1">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="chs_descripcion" rows="2" maxlength="5000"></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Observación de sección</label>
                            <textarea class="form-control" name="chs_observacion" rows="2" maxlength="5000"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar sección</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-item" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="form-item">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo ítem</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="item-chs-id" />
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Título</label>
                            <input type="text" class="form-control" name="chi_titulo" maxlength="180" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Orden</label>
                            <input type="number" class="form-control" name="chi_orden" min="1" step="1">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Descripción / criterio</label>
                            <textarea class="form-control" name="chi_descripcion" rows="2" maxlength="5000"></textarea>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Referencia funcional</label>
                            <input type="text" class="form-control" name="chi_referencia_funcional" maxlength="220">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estatus inicial</label>
                            <select class="form-select" name="chi_estatus">
                                <option value="pendiente">Pendiente</option>
                                <option value="aprobado">Aprobado</option>
                                <option value="observado">Observado</option>
                                <option value="no_aplica">No aplica</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar ítem</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endpush

@push('page-scripts')
<script>
(function () {
    const permisos = @json($permisosUI);
    const rutas = {
        data: '{{ route('operacion.checklist_entregables.data') }}',
        detalle: (id) => '{{ url('/operacion/checklist-entregables') }}/' + id + '/detalle',
        store: '{{ route('operacion.checklist_entregables.store') }}',
        update: (id) => '{{ url('/operacion/checklist-entregables') }}/' + id,
        storeSeccion: (id) => '{{ url('/operacion/checklist-entregables') }}/' + id + '/secciones',
        storeItem: (seccionId) => '{{ url('/operacion/checklist-entregables/secciones') }}/' + seccionId + '/items',
        revisarItem: (itemId) => '{{ url('/operacion/checklist-entregables/items') }}/' + itemId + '/revision'
    };

    const modalChecklist = new bootstrap.Modal(document.getElementById('modal-checklist'));
    const modalSeccion = new bootstrap.Modal(document.getElementById('modal-seccion'));
    const modalItem = new bootstrap.Modal(document.getElementById('modal-item'));
    const tabListadoButton = document.getElementById('chk-tab-listado-btn');
    const tabResumenItem = document.getElementById('chk-tab-resumen-item');
    const tabResumenButton = document.getElementById('chk-tab-resumen-btn');

    let checklistActualId = null;

    function parseErrorMessage(xhr) {
        if (xhr.responseJSON) {
            if (xhr.responseJSON.message) {
                return xhr.responseJSON.message;
            }

            if (xhr.responseJSON.errors) {
                const parts = [];
                Object.values(xhr.responseJSON.errors).forEach(function (messages) {
                    (messages || []).forEach(function (message) {
                        parts.push(message);
                    });
                });

                if (parts.length > 0) {
                    return parts.join('\n');
                }
            }
        }

        return 'No fue posible completar la operación.';
    }

    function estatusBadge(estatus) {
        const mapa = {
            pendiente: 'ls-badge-neutral',
            en_revision: 'ls-badge-info',
            aprobado: 'ls-badge-success',
            observado: 'ls-badge-warning',
            no_aplica: 'ls-badge-neutral'
        };

        const texto = {
            pendiente: 'Pendiente',
            en_revision: 'En revisión',
            aprobado: 'Aprobado',
            observado: 'Observado',
            no_aplica: 'No aplica'
        };

        return '<span class="ls-badge ' + (mapa[estatus] || 'ls-badge-neutral') + '">' + (texto[estatus] || estatus || 'Pendiente') + '</span>';
    }

    function escapeHtml(valor) {
        return $('<div>').text(valor || '').html();
    }

    function renderResumen(resumen) {
        const html = [
            '<span class="chk-counter">Total: <strong>' + Number(resumen.total || 0) + '</strong></span>',
            '<span class="chk-counter">Pendiente: <strong>' + Number(resumen.pendiente || 0) + '</strong></span>',
            '<span class="chk-counter">Aprobado: <strong>' + Number(resumen.aprobado || 0) + '</strong></span>',
            '<span class="chk-counter">Observado: <strong>' + Number(resumen.observado || 0) + '</strong></span>',
            '<span class="chk-counter">No aplica: <strong>' + Number(resumen.no_aplica || 0) + '</strong></span>'
        ];

        $('#chk-resumen').html(html.join(''));
    }

    function renderSecciones(secciones) {
        if (!Array.isArray(secciones) || secciones.length === 0) {
            $('#chk-secciones').html('<div class="chk-empty">Este checklist todavía no tiene secciones.</div>');
            return;
        }

        const html = secciones.map(function (seccion) {
            const filas = (seccion.items || []).map(function (item) {
                if (!permisos.editar) {
                    return '<tr>' +
                        '<td>' + Number(item.chi_orden || 0) + '</td>' +
                        '<td>' +
                            '<p class="chk-item-title">' + escapeHtml(item.chi_titulo) + '</p>' +
                            (item.chi_descripcion ? '<p class="chk-item-meta">' + escapeHtml(item.chi_descripcion) + '</p>' : '') +
                            (item.chi_referencia_funcional ? '<p class="chk-item-meta"><strong>Ref:</strong> ' + escapeHtml(item.chi_referencia_funcional) + '</p>' : '') +
                        '</td>' +
                        '<td>' + estatusBadge(item.chi_estatus) + '</td>' +
                        '<td><small class="text-body-secondary">' + escapeHtml(item.chi_observacion || '-') + '</small></td>' +
                        '<td><span class="text-body-secondary">-</span></td>' +
                    '</tr>';
                }

                return '<tr data-item-row="' + item.chi_id + '">' +
                    '<td>' + Number(item.chi_orden || 0) + '</td>' +
                    '<td>' +
                        '<p class="chk-item-title">' + escapeHtml(item.chi_titulo) + '</p>' +
                        (item.chi_descripcion ? '<p class="chk-item-meta">' + escapeHtml(item.chi_descripcion) + '</p>' : '') +
                        (item.chi_referencia_funcional ? '<p class="chk-item-meta"><strong>Ref:</strong> ' + escapeHtml(item.chi_referencia_funcional) + '</p>' : '') +
                    '</td>' +
                    '<td>' +
                        '<select class="form-select form-select-sm item-estatus" data-item-id="' + item.chi_id + '">' +
                            '<option value="pendiente" ' + (item.chi_estatus === 'pendiente' ? 'selected' : '') + '>Pendiente</option>' +
                            '<option value="aprobado" ' + (item.chi_estatus === 'aprobado' ? 'selected' : '') + '>Aprobado</option>' +
                            '<option value="observado" ' + (item.chi_estatus === 'observado' ? 'selected' : '') + '>Observado</option>' +
                            '<option value="no_aplica" ' + (item.chi_estatus === 'no_aplica' ? 'selected' : '') + '>No aplica</option>' +
                        '</select>' +
                    '</td>' +
                    '<td><textarea class="form-control form-control-sm item-observacion" rows="2" data-item-id="' + item.chi_id + '">' + escapeHtml(item.chi_observacion || '') + '</textarea></td>' +
                    '<td><button type="button" class="btn btn-sm btn-primary btn-guardar-item" data-item-id="' + item.chi_id + '">Guardar</button></td>' +
                '</tr>';
            }).join('');

            return '<div class="chk-section-card">' +
                '<div class="chk-section-head">' +
                    '<div>' +
                        '<p class="chk-section-title">' + Number(seccion.chs_orden || 0) + '. ' + escapeHtml(seccion.chs_titulo) + '</p>' +
                        (seccion.chs_descripcion ? '<p class="chk-section-desc">' + escapeHtml(seccion.chs_descripcion) + '</p>' : '') +
                    '</div>' +
                    (permisos.crear ? '<button type="button" class="btn btn-sm btn-outline-primary btn-nuevo-item" data-seccion-id="' + seccion.chs_id + '">Nuevo ítem</button>' : '') +
                '</div>' +
                '<div class="p-3">' +
                    (seccion.chs_observacion ? '<p class="small text-body-secondary mb-3"><strong>Observación:</strong> ' + escapeHtml(seccion.chs_observacion) + '</p>' : '') +
                    '<div class="table-responsive">' +
                        '<table class="table table-sm chk-item-table">' +
                            '<thead>' +
                                '<tr><th>Orden</th><th>Ítem</th><th>Estatus</th><th>Observación</th><th></th></tr>' +
                            '</thead>' +
                            '<tbody>' + (filas || '<tr><td colspan="5" class="text-center text-body-secondary">Sin ítems en esta sección.</td></tr>') + '</tbody>' +
                        '</table>' +
                    '</div>' +
                '</div>' +
            '</div>';
        });

        $('#chk-secciones').html(html.join(''));
    }

    function abrirTabResumen() {
        if (tabResumenItem) {
            tabResumenItem.classList.remove('d-none');
        }
        bootstrap.Tab.getOrCreateInstance(tabResumenButton).show();
    }

    function ocultarTabResumen() {
        if (tabResumenItem) {
            tabResumenItem.classList.add('d-none');
        }

        if (tabListadoButton) {
            bootstrap.Tab.getOrCreateInstance(tabListadoButton).show();
        }
    }

    function cargarDetalle(checklistId) {
        AppUI.showLoader();
        $.getJSON(rutas.detalle(checklistId)).done(function (response) {
            const data = response.data || {};
            checklistActualId = data.chk_id;

            $('#chk-empty').addClass('d-none');
            $('#form-checklist').removeClass('d-none');
            $('#chk-secciones').removeClass('d-none');

            $('#chk_id').val(data.chk_id || '');
            $('#chk_nombre').val(data.chk_nombre || '');
            $('#chk_referencia').val(data.chk_referencia || '');
            $('#chk_fecha').val(data.chk_fecha || '');
            $('#chk_estatus_general').val(data.chk_estatus_general || 'pendiente');
            $('#chk_observaciones').val(data.chk_observaciones || '');

            if (permisos.crear) {
                $('#btn-nueva-seccion').removeClass('d-none');
            }

            renderResumen(data.resumen || {});
            renderSecciones(data.secciones || []);
        }).fail(function (xhr) {
            AppUI.showMessage('Error', parseErrorMessage(xhr), 'error');
        }).always(function () {
            AppUI.hideLoader();
        });
    }

    function cargarChecklists(preseleccionarId = null) {
        AppUI.showLoader();

        $.getJSON(rutas.data).done(function (response) {
            const data = response.data || [];

            if ($.fn.DataTable.isDataTable('#checklists-table')) {
                $('#checklists-table').DataTable().clear().destroy();
            }

            $('#checklists-table').DataTable({
                data,
                order: [[1, 'desc']],
                pageLength: 8,
                columns: [
                    {
                        data: 'chk_nombre',
                        render: function (valor, type, row) {
                            let html = '<span class="fw-semibold">' + escapeHtml(valor || 'Checklist') + '</span>';
                            if (row.chk_es_plantilla) {
                                html += '<span class="badge bg-label-info chk-list-badge">Plantilla</span>';
                            }
                            if (row.chk_referencia) {
                                html += '<div class="small text-body-secondary">' + escapeHtml(row.chk_referencia) + '</div>';
                            }
                            return html;
                        }
                    },
                    { data: 'chk_fecha', defaultContent: '-' },
                    { data: 'chk_estatus_general', render: (valor) => estatusBadge(valor) },
                    {
                        data: null,
                        orderable: false,
                        render: function (row) {
                            return '<small class="text-body-secondary">P: ' + Number(row.items_pendiente || 0) +
                                ' | A: ' + Number(row.items_aprobado || 0) +
                                ' | O: ' + Number(row.items_observado || 0) +
                                ' | NA: ' + Number(row.items_no_aplica || 0) + '</small>';
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function (row) {
                            return '<button type="button" class="btn btn-sm btn-outline-primary btn-abrir-checklist" data-id="' + row.chk_id + '">Abrir</button>';
                        }
                    }
                ]
            });

            const idObjetivo = preseleccionarId || null;
            if (idObjetivo) {
                cargarDetalle(idObjetivo);
            } else {
                checklistActualId = null;
                ocultarTabResumen();
                $('#btn-nueva-seccion').addClass('d-none');
                $('#chk-empty').removeClass('d-none');
                $('#form-checklist').addClass('d-none');
                $('#chk-secciones').addClass('d-none').html('');
            }
        }).fail(function (xhr) {
            AppUI.showMessage('Error', parseErrorMessage(xhr), 'error');
        }).always(function () {
            AppUI.hideLoader();
        });
    }

    $('#checklists-table').on('click', '.btn-abrir-checklist', function () {
        const id = Number($(this).data('id'));
        if (id) {
            cargarDetalle(id);
            abrirTabResumen();
        }
    });

    $('#btn-nuevo-checklist').on('click', function () {
        $('#form-nuevo-checklist')[0].reset();
        $('input[name="chk_fecha"]', '#form-nuevo-checklist').val('{{ now()->format('Y-m-d') }}');
        $('#usar_plantilla_base').prop('checked', true);
        modalChecklist.show();
    });

    $('#form-nuevo-checklist').on('submit', function (event) {
        event.preventDefault();

        AppUI.showLoader();
        $.ajax({
            url: rutas.store,
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json'
        }).done(function (response) {
            modalChecklist.hide();
            cargarChecklists();
            AppUI.showMessage('Éxito', response.message || 'Checklist creado correctamente.', 'success');
        }).fail(function (xhr) {
            AppUI.showMessage('Error', parseErrorMessage(xhr), 'error');
        }).always(function () {
            AppUI.hideLoader();
        });
    });

    $('#form-checklist').on('submit', function (event) {
        event.preventDefault();

        if (!permisos.editar) {
            return;
        }

        const id = Number($('#chk_id').val());
        if (!id) {
            return;
        }

        AppUI.showLoader();
        $.ajax({
            url: rutas.update(id),
            method: 'PUT',
            data: $(this).serialize(),
            dataType: 'json'
        }).done(function (response) {
            cargarChecklists(id);
            AppUI.showMessage('Éxito', response.message || 'Checklist actualizado.', 'success');
        }).fail(function (xhr) {
            AppUI.showMessage('Error', parseErrorMessage(xhr), 'error');
        }).always(function () {
            AppUI.hideLoader();
        });
    });

    $('#btn-nueva-seccion').on('click', function () {
        if (!checklistActualId) {
            return;
        }

        $('#form-seccion')[0].reset();
        modalSeccion.show();
    });

    $('#form-seccion').on('submit', function (event) {
        event.preventDefault();

        if (!checklistActualId) {
            return;
        }

        AppUI.showLoader();
        $.ajax({
            url: rutas.storeSeccion(checklistActualId),
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json'
        }).done(function (response) {
            modalSeccion.hide();
            cargarDetalle(checklistActualId);
            cargarChecklists(checklistActualId);
            AppUI.showMessage('Éxito', response.message || 'Sección creada correctamente.', 'success');
        }).fail(function (xhr) {
            AppUI.showMessage('Error', parseErrorMessage(xhr), 'error');
        }).always(function () {
            AppUI.hideLoader();
        });
    });

    $('#chk-secciones').on('click', '.btn-nuevo-item', function () {
        const seccionId = Number($(this).data('seccion-id'));
        if (!seccionId) {
            return;
        }

        $('#form-item')[0].reset();
        $('#item-chs-id').val(String(seccionId));
        modalItem.show();
    });

    $('#form-item').on('submit', function (event) {
        event.preventDefault();

        const seccionId = Number($('#item-chs-id').val());
        if (!seccionId) {
            return;
        }

        AppUI.showLoader();
        $.ajax({
            url: rutas.storeItem(seccionId),
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json'
        }).done(function (response) {
            modalItem.hide();
            if (checklistActualId) {
                cargarDetalle(checklistActualId);
                cargarChecklists(checklistActualId);
            }
            AppUI.showMessage('Éxito', response.message || 'Ítem creado correctamente.', 'success');
        }).fail(function (xhr) {
            AppUI.showMessage('Error', parseErrorMessage(xhr), 'error');
        }).always(function () {
            AppUI.hideLoader();
        });
    });

    $('#chk-secciones').on('click', '.btn-guardar-item', function () {
        const itemId = Number($(this).data('item-id'));
        if (!itemId) {
            return;
        }

        const estatus = $('.item-estatus[data-item-id="' + itemId + '"]').val();
        const observacion = $('.item-observacion[data-item-id="' + itemId + '"]').val();

        AppUI.showLoader();
        $.ajax({
            url: rutas.revisarItem(itemId),
            method: 'PATCH',
            data: {
                chi_estatus: estatus,
                chi_observacion: observacion
            },
            dataType: 'json'
        }).done(function (response) {
            if (checklistActualId) {
                cargarDetalle(checklistActualId);
                cargarChecklists(checklistActualId);
            }
            AppUI.showMessage('Éxito', response.message || 'Revisión actualizada.', 'success');
        }).fail(function (xhr) {
            AppUI.showMessage('Error', parseErrorMessage(xhr), 'error');
        }).always(function () {
            AppUI.hideLoader();
        });
    });

    if (tabListadoButton) {
        tabListadoButton.addEventListener('shown.bs.tab', function () {
            if ($.fn.DataTable.isDataTable('#checklists-table')) {
                $('#checklists-table').DataTable().columns.adjust();
            }
        });
    }

    cargarChecklists();
})();
</script>
@endpush
