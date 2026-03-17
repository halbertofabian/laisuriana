@extends('layouts.app')

@section('title', 'Usuarios y Seguridad')

@push('vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <style>
        /* ---- DataTables toolbar ---- */
        .datatable-toolbar .dataTables_filter label,
        .datatable-toolbar .dataTables_length label {
            font-size: .82rem;
            font-weight: 500;
            color: var(--ls-text-muted);
        }
        .datatable-toolbar .dataTables_filter input,
        .datatable-toolbar .dataTables_length select {
            min-height: 2.1rem;
            border-radius: var(--ls-radius);
            border: 1px solid var(--ls-border);
            font-size: .84rem;
        }
        .dataTables_paginate .paginate_button {
            border-radius: var(--ls-radius-sm) !important;
            font-size: .8rem !important;
        }
        .dataTables_paginate .paginate_button.current,
        .dataTables_paginate .paginate_button.current:hover {
            background: var(--ls-accent) !important;
            color: #fff !important;
            border-color: var(--ls-accent) !important;
        }

        /* ---- Table cell helpers ---- */
        .cell-title { font-weight: 600; color: var(--ls-text-primary); }

        /* ---- Tag chips (legacy aliases → new system) ---- */
        .tag-list  { display: flex; flex-wrap: wrap; gap: .3rem; }
        .tag-chip  { display: inline-flex; align-items: center; border-radius: .35rem; padding: .18rem .55rem;
                     font-size: .73rem; font-weight: 600; line-height: 1.4; border: 1px solid transparent; }
        .tag-chip.role       { background: rgba(99,91,255,.08); color: #4f46e5; border-color: rgba(99,91,255,.2); }
        .tag-chip.branch     { background: rgba(26,158,109,.08); color: #1a9e6d; border-color: rgba(26,158,109,.2); }
        .tag-chip.permission { background: var(--ls-surface-3); color: var(--ls-text-muted); border-color: var(--ls-border); }
        .tag-chip.more       { background: rgba(233,155,62,.1); color: #c97b1a; border-color: rgba(233,155,62,.25); }

        /* ---- Row actions dropdown ---- */
        .table-row-actions .dropdown-menu {
            min-width: 12rem;
            border-radius: var(--ls-radius-lg);
            border: 1px solid var(--ls-border);
            box-shadow: var(--ls-shadow-lg);
            padding: .35rem;
        }
        .table-row-actions .dropdown-toggle {
            font-size: .78rem;
            font-weight: 600;
            padding: .28rem .65rem;
            border-radius: var(--ls-radius-sm);
        }
        .table-row-actions .dropdown-item {
            display: flex; align-items: center; gap: .45rem;
            font-size: .82rem; font-weight: 500;
            border-radius: var(--ls-radius-sm);
            padding: .45rem .65rem;
        }
        .table-row-actions .dropdown-item:hover { background: var(--ls-surface-2); }
        .table-row-actions .dropdown-item i { font-size: .95rem; }

        /* ---- Permission modules (flat style, no cards) ---- */
        .perm-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: .75rem;
        }
        .perm-group { min-width: 0; }
        .perm-group-head {
            padding-bottom: .35rem;
            border-bottom: 1px solid var(--ls-border);
            font-size: .72rem;
            letter-spacing: .05em;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--ls-text-muted);
        }
        .perm-group-body {
            padding-top: .5rem;
            max-height: 220px;
            overflow: auto;
        }
        .perm-group .form-check { margin-bottom: .45rem; }
        .perm-group .form-check:last-child { margin-bottom: 0; }
        .perm-group .form-check-input:checked { background-color: var(--ls-accent); border-color: var(--ls-accent); }
        .perm-group .form-check-label { font-size: .82rem; color: var(--ls-text-secondary); }

        /* ---- Tag picker (multi-select input) ---- */
        .tag-picker {
            border: 1px solid var(--ls-border);
            border-radius: var(--ls-radius);
            min-height: 42px;
            padding: .35rem;
            background: var(--ls-surface);
            position: relative;
            box-shadow: 0 1px 2px rgba(10,37,64,.04);
            transition: border-color .15s, box-shadow .15s;
        }
        .tag-picker.is-focused {
            border-color: var(--ls-accent);
            box-shadow: 0 0 0 3px rgba(99,91,255,.12);
        }
        .tag-picker-tags { display: flex; flex-wrap: wrap; gap: .3rem; margin-bottom: .3rem; }
        .tag-picker-chip {
            display: inline-flex; align-items: center; gap: .3rem;
            border-radius: var(--ls-radius-sm);
            background: var(--ls-accent); color: #fff;
            padding: .22rem .5rem; font-weight: 600; font-size: .8rem; line-height: 1.3;
        }
        .tag-picker-chip button {
            border: 0; background: rgba(0,0,0,.2); color: #fff;
            border-radius: 999px; width: .95rem; height: .95rem;
            padding: 0; line-height: .95rem; font-size: .68rem; cursor: pointer;
        }
        .tag-picker-input {
            width: 100%; border: 0; outline: none;
            padding: .12rem .2rem; font-size: .875rem;
            background: transparent; color: var(--ls-text-primary);
        }
        .tag-picker-dropdown {
            position: absolute; left: 0; right: 0; top: calc(100% + .2rem);
            border: 1px solid var(--ls-border); border-radius: var(--ls-radius-lg);
            background: var(--ls-surface); box-shadow: var(--ls-shadow-lg);
            z-index: 1080; max-height: 180px; overflow: auto; display: none;
        }
        .tag-picker-option { padding: .48rem .75rem; cursor: pointer; font-size: .875rem; color: var(--ls-text-secondary); }
        .tag-picker-option:hover { background: var(--ls-surface-2); color: var(--ls-text-primary); }
    </style>
@endpush

@section('content')
<x-section-header
    eyebrow="Control de acceso"
    icon="tabler-users-group"
    title="Usuarios y Seguridad"
    subtitle="Administra usuarios, roles y permisos del sistema."
/>

<div class="app-tabs-shell">
<div class="app-tabs-shell__header">
<ul class="nav nav-tabs app-tabs-shell__tabs" id="seguridadTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-usuarios" data-bs-toggle="tab" data-bs-target="#panel-usuarios" type="button" role="tab" aria-controls="panel-usuarios" aria-selected="true">
            Listar usuarios
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-roles" data-bs-toggle="tab" data-bs-target="#panel-roles" type="button" role="tab" aria-controls="panel-roles" aria-selected="false">
            Roles
        </button>
    </li>
    <li class="nav-item d-none" role="presentation">
        <button class="nav-link" id="tab-rol-form" data-bs-toggle="tab" data-bs-target="#panel-rol-form" type="button" role="tab" aria-controls="panel-rol-form" aria-selected="false">
            Formulario rol
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-permisos" data-bs-toggle="tab" data-bs-target="#panel-permisos" type="button" role="tab" aria-controls="panel-permisos" aria-selected="false">
            Permisos
        </button>
    </li>
    @if(auth()->user()?->tienePermiso('seguridad.ver_auditoria'))
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-bitacora" data-bs-toggle="tab" data-bs-target="#panel-bitacora" type="button" role="tab" aria-controls="panel-bitacora" aria-selected="false">
                Bitácora
            </button>
        </li>
    @endif
</ul>
</div>

<div class="app-tabs-shell__body">
<div class="tab-content">
    <div class="tab-pane fade show active" id="panel-usuarios" role="tabpanel" aria-labelledby="tab-usuarios" tabindex="0">
        <div class="d-flex justify-content-end">
            <button class="btn btn-primary btn-sm" id="btn-nuevo-usuario">
                <i class="ti tabler-plus me-1" style="font-size:.85rem;"></i>Nuevo usuario
            </button>
        </div>
        <div class="table-responsive">
            <table id="usuarios-table" class="table">
                <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Correo</th>
                    <th>Roles</th>
                    <th>Sucursales</th>
                    <th>Estatus</th>
                    <th style="width:1%;">Acciones</th>
                </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="tab-pane fade" id="panel-roles" role="tabpanel" aria-labelledby="tab-roles" tabindex="0">
        <div class="d-flex justify-content-end">
            <button class="btn btn-primary btn-sm" id="btn-nuevo-rol">
                <i class="ti tabler-plus me-1" style="font-size:.85rem;"></i>Nuevo rol
            </button>
        </div>
        <div class="table-responsive">
            <table id="roles-table" class="table">
                <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Permisos</th>
                    <th>Estatus</th>
                    <th style="width:1%;">Acciones</th>
                </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="tab-pane fade" id="panel-rol-form" role="tabpanel" aria-labelledby="tab-rol-form" tabindex="0">
        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
            <h6 id="rolFormTitle">Nuevo rol</h6>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-cancelar-rol-form">
                <i class="ti tabler-arrow-left me-1"></i>Volver a roles
            </button>
        </div>
        <form id="rol-form" class="pt-3">
            <input type="hidden" name="rol_id" id="rol_id">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="rol_nombre" placeholder="Ej. Supervisor de ventas" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Estatus <span class="text-danger">*</span></label>
                    <select class="form-select" name="rol_estatus" required>
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Descripción</label>
                    <input type="text" class="form-control" name="rol_descripcion" placeholder="Ej. Puede ver reportes y autorizar cancelaciones">
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 py-3">
                <span style="font-size:.78rem;font-weight:700;color:var(--ls-text-primary);text-transform:uppercase;letter-spacing:.05em;">Permisos</span>
                <span style="font-size:.76rem;color:var(--ls-text-muted);">Selecciona las acciones permitidas por módulo</span>
            </div>

            @php($permisosAgrupados = $permisos->groupBy('prm_modulo'))
            <div class="perm-grid">
                @foreach($permisosAgrupados as $modulo => $permisosDelModulo)
                    <div class="perm-group">
                        <div class="perm-group-head">{{ ucfirst(str_replace('_', ' ', $modulo)) }}</div>
                        <div class="perm-group-body">
                            @foreach($permisosDelModulo as $permiso)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permisos[]" id="permiso_{{ $permiso->prm_id }}" value="{{ $permiso->prm_id }}">
                                    <label class="form-check-label" for="permiso_{{ $permiso->prm_id }}" title="{{ $permiso->prm_clave }}">
                                        {{ $permiso->prm_descripcion }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="d-flex justify-content-end gap-2 pt-3">
                <button type="button" class="btn btn-outline-secondary" id="btn-cancelar-rol-form-footer">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btn-guardar-rol-form">Guardar rol</button>
            </div>
        </form>
    </div>

    <div class="tab-pane fade" id="panel-permisos" role="tabpanel" aria-labelledby="tab-permisos" tabindex="0">
        <div class="table-responsive">
            <table id="permisos-table" class="table">
                <thead>
                <tr>
                    <th>Clave</th>
                    <th>Descripción</th>
                    <th>Módulo</th>
                    <th>Estatus</th>
                </tr>
                </thead>
            </table>
        </div>
    </div>

    @if(auth()->user()?->tienePermiso('seguridad.ver_auditoria'))
        <div class="tab-pane fade" id="panel-bitacora" role="tabpanel" aria-labelledby="tab-bitacora" tabindex="0">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Usuario</label>
                    <input type="text" id="flt-bitacora-usuario" class="form-control" placeholder="Usuario o nombre">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Desde</label>
                    <input type="date" id="flt-bitacora-desde" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Hasta</label>
                    <input type="date" id="flt-bitacora-hasta" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Resultado acceso</label>
                    <select id="flt-bitacora-resultado" class="form-select">
                        <option value="">Todos</option>
                        <option value="exitoso">Exitoso</option>
                        <option value="fallido">Fallido</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Evento (contiene)</label>
                    <input type="text" id="flt-bitacora-accion" class="form-control" placeholder="Ej. edición de usuario">
                </div>
            </div>
            <div class="d-flex gap-2 py-3">
                <button type="button" class="btn btn-primary btn-sm" id="btn-aplicar-filtros-bitacora">Aplicar filtros</button>
                <button type="button" class="btn btn-label-secondary btn-sm" id="btn-limpiar-filtros-bitacora">Limpiar</button>
            </div>

            <h6 class="py-2">Bitácora de accesos</h6>
            <div class="table-responsive">
                <table id="bitacora-accesos-table" class="table table-bordered security-table">
                    <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Usuario capturado</th>
                        <th>Usuario identificado</th>
                        <th>Estado</th>
                        <th>Detalle</th>
                        <th>IP</th>
                    </tr>
                    </thead>
                </table>
            </div>

            <h6 class="py-2">Bitácora de acciones</h6>
            <div class="table-responsive">
                <table id="bitacora-acciones-table" class="table table-bordered security-table">
                    <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Evento</th>
                        <th>¿Qué ocurrió?</th>
                        <th>Usuario</th>
                        <th>Sucursal</th>
                        <th>IP</th>
                        <th>Detalle</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    @endif
</div>
</div>
</div>

{{-- ====== MODAL: USUARIO ====== --}}
<div class="modal fade" id="usuarioModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="usuario-form">
                <div class="modal-header">
                    <h5 class="modal-title" id="usuarioModalTitle">Nuevo usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="usr_id" id="usr_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre completo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="usr_nombre" placeholder="Ej. Ana García" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Usuario <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="usr_usuario" placeholder="Ej. ana.garcia" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Correo electrónico</label>
                            <input type="email" class="form-control" name="usr_email" placeholder="correo@empresa.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contraseña <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="usr_password" id="usr_password" placeholder="••••••••">
                            <p style="font-size:.76rem;color:var(--ls-text-muted);margin:0;">En edición, dejar vacío para conservar la actual.</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estatus <span class="text-danger">*</span></label>
                            <select class="form-select" name="usr_estatus" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sucursal predeterminada</label>
                            <select class="form-select" name="usc_scl_predeterminada" id="usc_scl_predeterminada"></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Roles <span class="text-danger">*</span></label>
                            <div id="roles-picker" class="tag-picker"></div>
                            <select class="form-select d-none" name="roles[]" id="roles" multiple>
                                @foreach($opciones['roles'] as $rol)
                                    <option value="{{ $rol->rol_id }}">{{ $rol->rol_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sucursales <span class="text-danger">*</span></label>
                            <div id="sucursales-picker" class="tag-picker"></div>
                            <select class="form-select d-none" name="sucursales[]" id="sucursales" multiple>
                                @foreach($opciones['sucursales'] as $sucursal)
                                    <option value="{{ $sucursal->scl_id }}">{{ $sucursal->scl_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar usuario</button>
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
    const usuarioModal = new bootstrap.Modal(document.getElementById('usuarioModal'));
    const datatableLanguage = {
        lengthMenu: '_MENU_ registros por página',
        zeroRecords: 'No se encontraron registros',
        info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
        infoEmpty: 'Mostrando 0 a 0 de 0 registros',
        infoFiltered: '(filtrado de _MAX_ registros totales)',
        search: 'Buscar:',
        paginate: {
            first: '«',
            last: '»',
            next: '›',
            previous: '‹'
        }
    };

    let usuariosCargados = false;
    let rolesCargados = false;
    let permisosCargados = false;
    let bitacoraAccesosCargada = false;
    let bitacoraAccionesCargada = false;
    const tagPickers = {};

    function escapeHtml(value) {
        return String(value || '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');
    }

    function renderTags(list, type, maxItems) {
        const values = list || [];
        const visibles = values.slice(0, maxItems || values.length);
        const chips = visibles.map(function (value) {
            return '<span class="tag-chip ' + type + '">' + escapeHtml(value) + '</span>';
        });

        if (values.length > visibles.length) {
            chips.push('<span class="tag-chip more">+' + (values.length - visibles.length) + ' más</span>');
        }

        return '<div class="tag-list">' + chips.join('') + '</div>';
    }

    function renderPermisosCompactos(permisos) {
        const items = permisos || [];
        if (items.length === 0) {
            return '<span class="text-body-secondary">Sin permisos</span>';
        }

        const visibles = items.slice(0, 4).map(function (permiso) {
            const descripcion = escapeHtml(permiso.descripcion || '');
            return '<span class="tag-chip permission" title="' + descripcion + '">' + descripcion + '</span>';
        });

        if (items.length > 4) {
            visibles.push('<span class="tag-chip more">+' + (items.length - 4) + ' más</span>');
        }

        return '<div class="tag-list">' + visibles.join('') + '</div>';
    }

    function renderActionsDropdown(items) {
        const options = items.map(function (item) {
            const extraData = Object.entries(item.data || {}).map(function (entry) {
                return ' data-' + entry[0] + '="' + escapeHtml(entry[1]) + '"';
            }).join('');

            return '<li><button type="button" class="dropdown-item ' + item.className + '"' + extraData + '>' +
                '<i class="icon-base ti ' + item.icon + '"></i>' + escapeHtml(item.label) +
                '</button></li>';
        }).join('');

        return '<div class="dropdown table-row-actions">' +
            '<button class="btn btn-sm btn-label-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">' +
            '<i class="icon-base ti tabler-dots-vertical me-1"></i>Acciones</button>' +
            '<ul class="dropdown-menu dropdown-menu-end">' + options + '</ul>' +
            '</div>';
    }

    function initTagPicker(config) {
        const $select = $(config.selectId);
        const $container = $(config.containerId);
        const options = $select.find('option').map(function () {
            return { value: String($(this).val()), label: $(this).text() };
        }).get();

        let selected = new Set(($select.val() || []).map(String));

        function syncSelect() {
            $select.val(Array.from(selected));
            $select.trigger('change');
        }

        function availableOptions(search) {
            const term = (search || '').trim().toLowerCase();
            return options.filter(function (item) {
                if (selected.has(item.value)) {
                    return false;
                }
                return term === '' || item.label.toLowerCase().includes(term);
            });
        }

        function render(searchTerm, keepFocus) {
            const tagsHtml = Array.from(selected).map(function (value) {
                const option = options.find(function (item) { return item.value === value; });
                if (!option) {
                    return '';
                }

                return '<span class="tag-picker-chip" data-value="' + escapeHtml(value) + '">' +
                    escapeHtml(option.label) +
                    '<button type="button" aria-label="Quitar">×</button>' +
                    '</span>';
            }).join('');

            const dropdownOptions = availableOptions(searchTerm).slice(0, 20);
            const dropdownHtml = dropdownOptions.length
                ? dropdownOptions.map(function (item) {
                    return '<div class="tag-picker-option" data-value="' + escapeHtml(item.value) + '">' + escapeHtml(item.label) + '</div>';
                }).join('')
                : '<div class="tag-picker-option text-body-secondary">Sin coincidencias</div>';

            $container.html(
                '<div class="tag-picker-tags">' + tagsHtml + '</div>' +
                '<input type="text" class="tag-picker-input" placeholder="' + escapeHtml(config.placeholder || 'Escribe para buscar') + '">' +
                '<div class="tag-picker-dropdown">' + dropdownHtml + '</div>'
            );

            const $input = $container.find('.tag-picker-input');
            $input.val(searchTerm || '');

            if (keepFocus) {
                const inputEl = $input.get(0);
                if (inputEl) {
                    inputEl.focus();
                    const pos = (searchTerm || '').length;
                    inputEl.setSelectionRange(pos, pos);
                }
            }
        }

        function setValues(values) {
            selected = new Set((values || []).map(String));
            render('', false);
            syncSelect();
        }

        render('', false);
        syncSelect();

        $container.on('click', function () {
            $container.find('.tag-picker-input').trigger('focus');
        });

        $container.on('focus', '.tag-picker-input', function () {
            $container.addClass('is-focused');
            $container.find('.tag-picker-dropdown').show();
        });

        $container.on('blur', '.tag-picker-input', function () {
            window.setTimeout(function () {
                $container.removeClass('is-focused');
                $container.find('.tag-picker-dropdown').hide();
            }, 150);
        });

        $container.on('input', '.tag-picker-input', function () {
            render($(this).val(), true);
            $container.find('.tag-picker-dropdown').show();
        });

        $container.on('keydown', '.tag-picker-input', function (event) {
            if (event.key !== 'Enter') {
                return;
            }

            event.preventDefault();
            const firstOption = $container.find('.tag-picker-option[data-value]').first();
            if (firstOption.length) {
                firstOption.trigger('click');
            }
        });

        $container.on('click', '.tag-picker-option[data-value]', function () {
            const value = String($(this).data('value'));
            selected.add(value);
            render('', true);
            syncSelect();
        });

        $container.on('click', '.tag-picker-chip button', function (event) {
            event.stopPropagation();
            const value = String($(this).closest('.tag-picker-chip').data('value'));
            selected.delete(value);
            render('', true);
            syncSelect();
        });

        tagPickers[config.name] = { setValues };
    }

    function actualizarOpcionesPredeterminada() {
        const selected = $('#sucursales').val() || [];
        const $pred = $('#usc_scl_predeterminada');
        $pred.empty();

        selected.forEach(function (value) {
            const text = $('#sucursales option[value="' + value + '"]').text();
            $pred.append('<option value="' + value + '">' + text + '</option>');
        });
    }

    function cargarUsuariosTabla() {
        AppUI.showLoader();

        $.getJSON('{{ route('seguridad.usuarios.data') }}').done(function (response) {
            if ($.fn.DataTable.isDataTable('#usuarios-table')) {
                $('#usuarios-table').DataTable().clear().destroy();
            }

            $('#usuarios-table').DataTable({
                data: response.data || [],
                order: [[0, 'asc']],
                responsive: true,
                autoWidth: false,
                pageLength: 10,
                lengthMenu: [10, 25, 50],
                language: datatableLanguage,
                initComplete: function () {
                    $('#usuarios-table_wrapper').addClass('datatable-toolbar');
                },
                columns: [
                    {
                        data: 'usr_nombre',
                        render: function (v) {
                            return '<div class="cell-title">' + escapeHtml(v) + '</div>';
                        }
                    },
                    {
                        data: 'usr_usuario',
                        render: function (v) {
                            return '<span class="fw-semibold text-primary">' + escapeHtml(v) + '</span>';
                        }
                    },
                    { data: 'usr_email', defaultContent: '-', render: function (v) { return escapeHtml(v || '-'); } },
                    { data: 'roles', render: function (v) { return renderTags(v, 'role', 2); } },
                    { data: 'sucursales', render: function (v) { return renderTags(v, 'branch', 2); } },
                    {
                        data: 'usr_estatus',
                        render: function (v) {
                            return v === 'activo'
                                ? '<span class="ls-badge ls-badge-success">Activo</span>'
                                : '<span class="ls-badge ls-badge-danger">Inactivo</span>';
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function (row) {
                            const toggleTo = row.usr_estatus === 'activo' ? 'inactivo' : 'activo';
                            const toggleText = row.usr_estatus === 'activo' ? 'Inactivar' : 'Activar';
                            const toggleIcon = toggleTo === 'activo' ? 'tabler-user-check' : 'tabler-user-off';

                            return renderActionsDropdown([
                                {
                                    className: 'btn-editar-usuario',
                                    label: 'Editar',
                                    icon: 'tabler-edit',
                                    data: { id: row.usr_id }
                                },
                                {
                                    className: 'btn-toggle-usuario',
                                    label: toggleText,
                                    icon: toggleIcon,
                                    data: { id: row.usr_id, estatus: toggleTo }
                                }
                            ]);
                        }
                    }
                ]
            });

            usuariosCargados = true;
        }).fail(function () {
            AppUI.showMessage('Error', 'No fue posible cargar los usuarios.');
        }).always(function () {
            AppUI.hideLoader();
        });
    }

    function cargarRolesTabla() {
        AppUI.showLoader();

        $.getJSON('{{ route('seguridad.roles.data') }}').done(function (response) {
            if ($.fn.DataTable.isDataTable('#roles-table')) {
                $('#roles-table').DataTable().clear().destroy();
            }

            $('#roles-table').DataTable({
                data: response.data || [],
                order: [[0, 'asc']],
                responsive: true,
                autoWidth: false,
                pageLength: 10,
                lengthMenu: [10, 25, 50],
                language: datatableLanguage,
                initComplete: function () {
                    $('#roles-table_wrapper').addClass('datatable-toolbar');
                },
                columns: [
                    { data: 'rol_nombre', render: function (v) { return '<div class="cell-title">' + escapeHtml(v) + '</div>'; } },
                    { data: 'rol_descripcion', defaultContent: '-', render: function (v) { return escapeHtml(v || '-'); } },
                    {
                        data: 'permisos',
                        render: function (v) { return renderPermisosCompactos(v); }
                    },
                    {
                        data: 'rol_estatus',
                        render: function (v) {
                            return v === 'activo'
                                ? '<span class="ls-badge ls-badge-success">Activo</span>'
                                : '<span class="ls-badge ls-badge-danger">Inactivo</span>';
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function (row) {
                            const toggleTo = row.rol_estatus === 'activo' ? 'inactivo' : 'activo';
                            const toggleText = row.rol_estatus === 'activo' ? 'Inactivar' : 'Activar';
                            const toggleIcon = toggleTo === 'activo' ? 'tabler-lock-open' : 'tabler-lock-off';

                            return renderActionsDropdown([
                                {
                                    className: 'btn-editar-rol',
                                    label: 'Editar',
                                    icon: 'tabler-edit',
                                    data: { id: row.rol_id }
                                },
                                {
                                    className: 'btn-toggle-rol',
                                    label: toggleText,
                                    icon: toggleIcon,
                                    data: { id: row.rol_id, estatus: toggleTo }
                                }
                            ]);
                        }
                    }
                ]
            });

            rolesCargados = true;
        }).fail(function () {
            AppUI.showMessage('Error', 'No fue posible cargar los roles.');
        }).always(function () {
            AppUI.hideLoader();
        });
    }

    function cargarPermisosTabla() {
        AppUI.showLoader();

        $.getJSON('{{ route('seguridad.permisos.data') }}').done(function (response) {
            if ($.fn.DataTable.isDataTable('#permisos-table')) {
                $('#permisos-table').DataTable().clear().destroy();
            }

            $('#permisos-table').DataTable({
                data: response.data || [],
                order: [[0, 'asc']],
                responsive: true,
                autoWidth: false,
                pageLength: 10,
                lengthMenu: [10, 25, 50],
                language: datatableLanguage,
                initComplete: function () {
                    $('#permisos-table_wrapper').addClass('datatable-toolbar');
                },
                columns: [
                    { data: 'prm_clave', render: function (v) { return '<span class="fw-semibold">' + escapeHtml(v) + '</span>'; } },
                    { data: 'prm_descripcion', render: function (v) { return escapeHtml(v); } },
                    {
                        data: 'prm_modulo',
                        render: function (v) {
                            return '<span class="tag-chip permission">' + escapeHtml(v) + '</span>';
                        }
                    },
                    {
                        data: 'prm_estatus',
                        render: function (v) {
                            return v === 'activo'
                                ? '<span class="ls-badge ls-badge-success">Activo</span>'
                                : '<span class="ls-badge ls-badge-danger">Inactivo</span>';
                        }
                    }
                ]
            });

            permisosCargados = true;
        }).fail(function () {
            AppUI.showMessage('Error', 'No fue posible cargar el catálogo de permisos.');
        }).always(function () {
            AppUI.hideLoader();
        });
    }

    function filtrosBitacora() {
        return {
            usuario: $('#flt-bitacora-usuario').val(),
            fecha_desde: $('#flt-bitacora-desde').val(),
            fecha_hasta: $('#flt-bitacora-hasta').val(),
            resultado: $('#flt-bitacora-resultado').val(),
            accion: $('#flt-bitacora-accion').val()
        };
    }

    function cargarBitacoraAccesosTabla() {
        AppUI.showLoader();
        const filtros = filtrosBitacora();

        $.getJSON('{{ route('seguridad.bitacora.accesos') }}', filtros).done(function (response) {
            if ($.fn.DataTable.isDataTable('#bitacora-accesos-table')) {
                $('#bitacora-accesos-table').DataTable().clear().destroy();
            }

            $('#bitacora-accesos-table').DataTable({
                data: response.data || [],
                order: [[0, 'desc']],
                responsive: true,
                autoWidth: false,
                pageLength: 10,
                lengthMenu: [10, 25, 50],
                language: datatableLanguage,
                initComplete: function () {
                    $('#bitacora-accesos-table_wrapper').addClass('datatable-toolbar');
                },
                columns: [
                    { data: 'fecha' },
                    { data: 'usuario_intentado', render: function (v) { return '<span class="fw-semibold">' + escapeHtml(v || '-') + '</span>'; } },
                    {
                        data: null,
                        render: function (row) {
                            const texto = row.nombre_registrado || row.usuario_registrado || '-';
                            return escapeHtml(texto);
                        }
                    },
                    {
                        data: 'resultado',
                        render: function (v) {
                            return v === 'exitoso'
                                ? '<span class="ls-badge ls-badge-success">Acceso permitido</span>'
                                : '<span class="ls-badge ls-badge-danger">Acceso denegado</span>';
                        }
                    },
                    { data: 'motivo', defaultContent: '-', render: function (v) { return '<small>' + escapeHtml(v || '-') + '</small>'; } },
                    { data: 'ip', defaultContent: '-', render: function (v) { return escapeHtml(v || '-'); } }
                ]
            });

            bitacoraAccesosCargada = true;
        }).fail(function () {
            AppUI.showMessage('Error', 'No fue posible cargar la bitácora de accesos.');
        }).always(function () {
            AppUI.hideLoader();
        });
    }

    function cargarBitacoraAccionesTabla() {
        AppUI.showLoader();
        const filtros = filtrosBitacora();

        $.getJSON('{{ route('seguridad.bitacora.acciones') }}', filtros).done(function (response) {
            if ($.fn.DataTable.isDataTable('#bitacora-acciones-table')) {
                $('#bitacora-acciones-table').DataTable().clear().destroy();
            }

            $('#bitacora-acciones-table').DataTable({
                data: response.data || [],
                order: [[0, 'desc']],
                responsive: true,
                autoWidth: false,
                pageLength: 10,
                lengthMenu: [10, 25, 50],
                language: datatableLanguage,
                initComplete: function () {
                    $('#bitacora-acciones-table_wrapper').addClass('datatable-toolbar');
                },
                columns: [
                    { data: 'fecha' },
                    { data: 'evento', render: function (v) { return '<span class="tag-chip permission">' + escapeHtml(v || '-') + '</span>'; } },
                    {
                        data: 'detalle',
                        render: function (row) {
                            return '<span class="fw-semibold">' + escapeHtml(row || '-') + '</span>';
                        }
                    },
                    { data: 'usuario', defaultContent: '-', render: function (v) { return escapeHtml(v || '-'); } },
                    { data: 'sucursal', defaultContent: '-', render: function (v) { return renderTags(v ? [v] : [], 'branch', 1); } },
                    { data: 'ip', defaultContent: '-', render: function (v) { return escapeHtml(v || '-'); } },
                    {
                        data: 'payload',
                        render: function (v) {
                            if (!v || v === 'Sin detalle adicional') {
                                return '<span class="text-body-secondary">Sin detalle</span>';
                            }
                            return '<small title="' + escapeHtml(v) + '">' + escapeHtml(v) + '</small>';
                        }
                    }
                ]
            });

            bitacoraAccionesCargada = true;
        }).fail(function () {
            AppUI.showMessage('Error', 'No fue posible cargar la bitácora de acciones.');
        }).always(function () {
            AppUI.hideLoader();
        });
    }

    function limpiarFormularioUsuario() {
        $('#usuario-form')[0].reset();
        $('#usr_id').val('');
        if (tagPickers.roles) {
            tagPickers.roles.setValues([]);
        }
        if (tagPickers.sucursales) {
            tagPickers.sucursales.setValues([]);
        }
        $('#usc_scl_predeterminada').empty();
        $('#usuarioModalTitle').text('Nuevo usuario');
        $('#usr_password').attr('required', 'required');
    }

    function limpiarFormularioRol() {
        $('#rol-form')[0].reset();
        $('#rol_id').val('');
        $('input[name="permisos[]"]').prop('checked', false);
        $('#rolFormTitle').text('Nuevo rol');
        $('#btn-guardar-rol-form').text('Guardar rol');
    }

    function abrirTabFormularioRol() {
        const tabBtn = document.querySelector('#tab-rol-form');
        if (!tabBtn) {
            return;
        }

        bootstrap.Tab.getOrCreateInstance(tabBtn).show();
    }

    function volverATabRoles() {
        const tabBtn = document.querySelector('#tab-roles');
        if (!tabBtn) {
            return;
        }

        bootstrap.Tab.getOrCreateInstance(tabBtn).show();
    }

    $('#btn-nuevo-usuario').on('click', function () {
        limpiarFormularioUsuario();
        usuarioModal.show();
    });

    $('#btn-nuevo-rol').on('click', function () {
        limpiarFormularioRol();
        abrirTabFormularioRol();
    });

    $('#btn-cancelar-rol-form, #btn-cancelar-rol-form-footer').on('click', function () {
        limpiarFormularioRol();
        volverATabRoles();
    });

    $('#sucursales').on('change', actualizarOpcionesPredeterminada);

    initTagPicker({
        name: 'roles',
        selectId: '#roles',
        containerId: '#roles-picker',
        placeholder: 'Agregar rol'
    });

    initTagPicker({
        name: 'sucursales',
        selectId: '#sucursales',
        containerId: '#sucursales-picker',
        placeholder: 'Agregar sucursal'
    });

    $('#usuarios-table').on('click', '.btn-editar-usuario', function () {
        const id = $(this).data('id');
        AppUI.showLoader();

        $.getJSON('{{ url('/seguridad/usuarios') }}/' + id).done(function (response) {
            const d = response.data;
            limpiarFormularioUsuario();
            $('#usuarioModalTitle').text('Editar usuario');
            $('#usr_id').val(d.usr_id);
            $('[name="usr_nombre"]').val(d.usr_nombre);
            $('[name="usr_usuario"]').val(d.usr_usuario);
            $('[name="usr_email"]').val(d.usr_email);
            $('[name="usr_estatus"]').val(d.usr_estatus);
            if (tagPickers.roles) {
                tagPickers.roles.setValues((d.roles || []).map(String));
            }
            if (tagPickers.sucursales) {
                tagPickers.sucursales.setValues((d.sucursales || []).map(String));
            }
            actualizarOpcionesPredeterminada();
            if (d.usc_scl_predeterminada) {
                $('#usc_scl_predeterminada').val(String(d.usc_scl_predeterminada));
            } else if (d.sucursales.length > 0) {
                $('#usc_scl_predeterminada').val(String(d.sucursales[0]));
            }
            $('#usr_password').removeAttr('required').val('');
            usuarioModal.show();
        }).fail(function () {
            AppUI.showMessage('Error', 'No fue posible cargar la información del usuario.');
        }).always(function () {
            AppUI.hideLoader();
        });
    });

    $('#usuarios-table').on('click', '.btn-toggle-usuario', function () {
        const id = $(this).data('id');
        const estatus = $(this).data('estatus');

        AppUI.showLoader();
        $.ajax({
            url: '{{ url('/seguridad/usuarios') }}/' + id + '/estatus',
            method: 'PATCH',
            data: { usr_estatus: estatus }
        }).done(function (response) {
            AppUI.showMessage('Éxito', response.message);
            cargarUsuariosTabla();
        }).fail(function (xhr) {
            AppUI.showMessage('Error', xhr.responseJSON?.message || 'No se pudo cambiar el estatus.');
        }).always(function () {
            AppUI.hideLoader();
        });
    });

    $('#usuario-form').on('submit', function (event) {
        event.preventDefault();

        const rolesSeleccionados = $('#roles').val() || [];
        const sucursalesSeleccionadas = $('#sucursales').val() || [];

        if (rolesSeleccionados.length === 0) {
            AppUI.showMessage('Validación', 'Debes seleccionar al menos un rol.');
            return;
        }

        if (sucursalesSeleccionadas.length === 0) {
            AppUI.showMessage('Validación', 'Debes seleccionar al menos una sucursal.');
            return;
        }

        const id = $('#usr_id').val();
        const url = id ? '{{ url('/seguridad/usuarios') }}/' + id : '{{ route('seguridad.usuarios.store') }}';
        const method = id ? 'PUT' : 'POST';

        AppUI.showLoader();
        $.ajax({
            url,
            method,
            data: $(this).serialize()
        }).done(function (response) {
            usuarioModal.hide();
            AppUI.showMessage('Éxito', response.message);
            cargarUsuariosTabla();
        }).fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                const firstError = Object.values(xhr.responseJSON.errors)[0][0];
                AppUI.showMessage('Validación', firstError);
                return;
            }
            AppUI.showMessage('Error', xhr.responseJSON?.message || 'No fue posible guardar el usuario.');
        }).always(function () {
            AppUI.hideLoader();
        });
    });

    $('#roles-table').on('click', '.btn-editar-rol', function () {
        const id = $(this).data('id');
        AppUI.showLoader();

        $.getJSON('{{ url('/seguridad/roles') }}/' + id).done(function (response) {
            const d = response.data;
            limpiarFormularioRol();
            $('#rolFormTitle').text('Editar rol');
            $('#btn-guardar-rol-form').text('Guardar cambios');
            $('#rol_id').val(d.rol_id);
            $('[name="rol_nombre"]').val(d.rol_nombre);
            $('[name="rol_descripcion"]').val(d.rol_descripcion);
            $('[name="rol_estatus"]').val(d.rol_estatus);
            (d.permisos || []).forEach(function (idPermiso) {
                $('#permiso_' + idPermiso).prop('checked', true);
            });
            abrirTabFormularioRol();
        }).fail(function () {
            AppUI.showMessage('Error', 'No fue posible cargar el rol.');
        }).always(function () {
            AppUI.hideLoader();
        });
    });

    $('#roles-table').on('click', '.btn-toggle-rol', function () {
        const id = $(this).data('id');
        const estatus = $(this).data('estatus');

        AppUI.showLoader();
        $.ajax({
            url: '{{ url('/seguridad/roles') }}/' + id + '/estatus',
            method: 'PATCH',
            data: { rol_estatus: estatus }
        }).done(function (response) {
            AppUI.showMessage('Éxito', response.message);
            cargarRolesTabla();
        }).fail(function (xhr) {
            AppUI.showMessage('Error', xhr.responseJSON?.message || 'No fue posible cambiar el estatus.');
        }).always(function () {
            AppUI.hideLoader();
        });
    });

    $('#rol-form').on('submit', function (event) {
        event.preventDefault();

        const id = $('#rol_id').val();
        const url = id ? '{{ url('/seguridad/roles') }}/' + id : '{{ route('seguridad.roles.store') }}';
        const method = id ? 'PUT' : 'POST';

        AppUI.showLoader();
        $.ajax({
            url,
            method,
            data: $(this).serialize()
        }).done(function (response) {
            AppUI.showMessage('Éxito', response.message);
            limpiarFormularioRol();
            volverATabRoles();
            cargarRolesTabla();
        }).fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                const firstError = Object.values(xhr.responseJSON.errors)[0][0];
                AppUI.showMessage('Validación', firstError);
                return;
            }
            AppUI.showMessage('Error', xhr.responseJSON?.message || 'No fue posible guardar el rol.');
        }).always(function () {
            AppUI.hideLoader();
        });
    });

    $('#btn-aplicar-filtros-bitacora').on('click', function () {
        cargarBitacoraAccesosTabla();
        cargarBitacoraAccionesTabla();
    });

    $('#btn-limpiar-filtros-bitacora').on('click', function () {
        $('#flt-bitacora-usuario').val('');
        $('#flt-bitacora-desde').val('');
        $('#flt-bitacora-hasta').val('');
        $('#flt-bitacora-resultado').val('');
        $('#flt-bitacora-accion').val('');
        cargarBitacoraAccesosTabla();
        cargarBitacoraAccionesTabla();
    });

    document.querySelectorAll('#seguridadTabs button[data-bs-toggle="tab"]').forEach(function (button) {
        button.addEventListener('shown.bs.tab', function (event) {
            const target = event.target.getAttribute('data-bs-target');

            if (target === '#panel-usuarios' && !usuariosCargados) {
                cargarUsuariosTabla();
            }

            if (target === '#panel-roles' && !rolesCargados) {
                cargarRolesTabla();
            }

            if (target === '#panel-permisos' && !permisosCargados) {
                cargarPermisosTabla();
            }

            if (target === '#panel-bitacora' && (!bitacoraAccesosCargada || !bitacoraAccionesCargada)) {
                cargarBitacoraAccesosTabla();
                cargarBitacoraAccionesTabla();
            }
        });
    });

    function activarTabInicial() {
        const tab = new URLSearchParams(window.location.search).get('tab');

        if (tab === 'roles') {
            const btn = document.querySelector('#tab-roles');
            bootstrap.Tab.getOrCreateInstance(btn).show();
            if (!rolesCargados) {
                cargarRolesTabla();
            }
            return;
        }

        if (tab === 'permisos') {
            const btn = document.querySelector('#tab-permisos');
            bootstrap.Tab.getOrCreateInstance(btn).show();
            if (!permisosCargados) {
                cargarPermisosTabla();
            }
            return;
        }

        if (tab === 'bitacora') {
            const btn = document.querySelector('#tab-bitacora');
            if (btn) {
                bootstrap.Tab.getOrCreateInstance(btn).show();
                if (!bitacoraAccesosCargada || !bitacoraAccionesCargada) {
                    cargarBitacoraAccesosTabla();
                    cargarBitacoraAccionesTabla();
                }
                return;
            }
        }

        if (!usuariosCargados) {
            cargarUsuariosTabla();
        }
    }

    activarTabInicial();
})();
</script>
@endpush
