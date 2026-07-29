@extends('layouts.desktop')

@section('title', 'Cajas')

@push('desktop-vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
@endpush

@section('desktop-toolbar')
    <div class="desktop-toolbar__group">
        @php($activeSubmenu = 'cajas')
        @include('desktop.operacion.gestion_configuraciones._subnav')
        @if($permisosUI['caja_crear'])
            <span class="desktop-toolbar__divider"></span>
            <button type="button" class="desktop-btn desktop-btn--primary" id="btn-nueva-caja">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5v14"/></svg>
                Nueva caja
            </button>
        @endif
        <button type="button" class="desktop-btn desktop-btn--ghost" id="btn-recargar-cajas">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
            Actualizar
        </button>
    </div>
    <div class="desktop-toolbar__group">
        <select class="desktop-toolbar__select" id="cajas-sucursal">
            <option value="">Todas las sucursales</option>
            @foreach($opciones['sucursales'] as $sucursal)
                <option value="{{ $sucursal->scl_id }}">{{ $sucursal->scl_nombre }}</option>
            @endforeach
        </select>
        <select class="desktop-toolbar__select" id="cajas-estatus">
            <option value="">Todos los estatus</option>
            <option value="activo">Activas</option>
            <option value="inactivo">Inactivas</option>
        </select>
        <select class="desktop-toolbar__select" id="cajas-length">
            <option value="10">10 por página</option>
            <option value="25">25 por página</option>
            <option value="50">50 por página</option>
        </select>
        <input type="search" id="cajas-search" class="desktop-toolbar__search" placeholder="Buscar caja">
    </div>
@endsection

@section('content')
    <section class="desktop-pane">
        <div class="desktop-list-wrap">
            <table id="desktop-cajas-table" class="desktop-list">
                <thead>
                    <tr>
                        <th>Caja</th>
                        <th>Sucursal</th>
                        <th>Almacén</th>
                        <th>Clave</th>
                        <th>Usuarios</th>
                        <th style="width:104px;">Estatus</th>
                        <th style="width:56px; text-align:right;">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
        <div class="desktop-list-foot">
            <div id="desktop-cajas-info"></div>
            <div id="desktop-cajas-pagination" class="desktop-pager"></div>
        </div>
    </section>

    <div class="desktop-modal" id="desktop-caja-modal" aria-hidden="true">
        <div class="desktop-modal__dialog">
            <div class="desktop-modal__head">
                <div class="desktop-modal__title" id="desktop-caja-modal-title">Nueva caja</div>
                <button type="button" class="desktop-modal__close" data-close-caja-modal aria-label="Cerrar">&times;</button>
            </div>

            <form id="desktop-caja-form" data-ls-autocomplete="admin">
                <div class="desktop-modal__body">
                    <input type="hidden" name="caj_id" id="caj_id">
                    <div class="desktop-form-grid">
                        <div class="desktop-field">
                            <label>Sucursal</label>
                            <select name="caj_scl_id" id="caj_scl_id" required>
                                <option value="">Selecciona</option>
                                @foreach($opciones['sucursales'] as $sucursal)
                                    <option value="{{ $sucursal->scl_id }}">{{ $sucursal->scl_nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="desktop-field">
                            <label>Estatus</label>
                            <select name="caj_estatus" id="caj_estatus" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="desktop-field desktop-field--full">
                            <label>Almacén asociado</label>
                            <select name="caj_alm_id" id="caj_alm_id">
                                <option value="">Sin almacén asociado</option>
                            </select>
                        </div>
                        <div class="desktop-field desktop-field--full">
                            <label>Nombre de caja</label>
                            <input type="text" name="caj_nombre" id="caj_nombre" maxlength="120" placeholder="Ej. Caja principal" required>
                        </div>
                        <div class="desktop-field desktop-field--full">
                            <label>Usuarios asignados</label>
                            <div id="caja-usuarios-picker" class="desktop-tag-picker"></div>
                            <select class="d-none" name="usuarios[]" id="usuarios" multiple>
                                @foreach($opciones['usuarios'] as $usuario)
                                    <option value="{{ $usuario->usr_id }}">{{ $usuario->usr_nombre }} ({{ $usuario->usr_usuario }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="desktop-field desktop-field--full">
                            <label>Clave</label>
                            <input type="text" id="caj_clave_preview" value="Se generará automáticamente al guardar." readonly>
                        </div>
                    </div>
                </div>

                <div class="desktop-modal__foot">
                    <button type="button" class="desktop-btn desktop-btn--default" data-close-caja-modal>Cancelar</button>
                    <button type="submit" class="desktop-btn desktop-btn--primary" id="btn-guardar-caja">Guardar caja</button>
                </div>
            </form>
        </div>
    </div>

    <div class="desktop-feedback" id="desktop-cajas-feedback"></div>
@endsection

@push('desktop-vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endpush

@push('desktop-scripts')
    <script>
        (function () {
            const $table = $('#desktop-cajas-table');
            const $modal = $('#desktop-caja-modal');
            const $form = $('#desktop-caja-form');
            const $feedback = $('#desktop-cajas-feedback');
            const tagPickers = {};
            const almacenesBase = @json($opciones['almacenes_js'] ?? []);
            let cajasTable = null;

            $.ajaxSetup({
                cache: false,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const permisosUI = @json($permisosUI);
            const rutas = {
                data: '{{ route('desktop.operacion.gestion_configuraciones.cajas.data') }}',
                show: function (id) { return '{{ url('/desktop/operacion/gestion-configuraciones/cajas') }}/' + id; },
                store: '{{ route('desktop.operacion.gestion_configuraciones.cajas.store') }}',
                update: function (id) { return '{{ url('/desktop/operacion/gestion-configuraciones/cajas') }}/' + id; },
                estatus: function (id) { return '{{ url('/desktop/operacion/gestion-configuraciones/cajas') }}/' + id + '/estatus'; }
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

            function renderPills(values, variant, maxItems) {
                const items = values || [];
                const visible = items.slice(0, maxItems || items.length);

                if (!visible.length) {
                    return '<span class="desktop-list__meta">Sin usuarios</span>';
                }

                const chips = visible.map(function (value) {
                    return '<span class="desktop-pill desktop-pill--' + variant + '">' + escapeHtml(value) + '</span>';
                });

                if (items.length > visible.length) {
                    chips.push('<span class="desktop-pill desktop-pill--more">+' + (items.length - visible.length) + '</span>');
                }

                return '<div class="desktop-pill-list">' + chips.join('') + '</div>';
            }

            function renderStatus(value) {
                const className = value === 'activo' ? 'desktop-status--active' : 'desktop-status--inactive';
                const label = value === 'activo' ? 'Activo' : 'Inactivo';
                return '<span class="desktop-status ' + className + '">' + label + '</span>';
            }

            function renderActions(row) {
                const toggleTo = row.caj_estatus === 'activo' ? 'inactivo' : 'activo';
                const toggleText = row.caj_estatus === 'activo' ? 'Inactivar' : 'Activar';

                return '' +
                    '<div class="desktop-rowmenu">' +
                        '<button type="button" class="desktop-overflow" data-overflow aria-haspopup="true" aria-expanded="false" aria-label="Más acciones">' + ICONS.dots + '</button>' +
                        '<div class="desktop-menu">' +
                            (permisosUI.caja_editar
                                ? '<button type="button" class="desktop-menu__item btn-editar-caja" data-id="' + row.caj_id + '">' + ICONS.edit + 'Editar</button>'
                                : '') +
                            (permisosUI.caja_inactivar
                                ? '<div class="desktop-menu__divider"></div><button type="button" class="desktop-menu__item btn-toggle-caja" data-id="' + row.caj_id + '" data-estatus="' + toggleTo + '">' + ICONS.toggle + toggleText + '</button>'
                                : '') +
                        '</div>' +
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
                        if (selected.has(item.value)) return false;
                        return term === '' || item.label.toLowerCase().includes(term);
                    });
                }

                function render(searchTerm, keepFocus) {
                    const tagsHtml = Array.from(selected).map(function (value) {
                        const option = options.find(function (item) { return item.value === value; });
                        if (!option) return '';

                        return '' +
                            '<span class="desktop-tag-picker__chip" data-value="' + escapeHtml(value) + '">' +
                                escapeHtml(option.label) +
                                '<button type="button" aria-label="Quitar">&times;</button>' +
                            '</span>';
                    }).join('');

                    const dropdownOptions = availableOptions(searchTerm).slice(0, 20);
                    const dropdownHtml = dropdownOptions.length
                        ? dropdownOptions.map(function (item) {
                            return '<div class="desktop-tag-picker__option" data-value="' + escapeHtml(item.value) + '">' + escapeHtml(item.label) + '</div>';
                        }).join('')
                        : '<div class="desktop-tag-picker__option" style="color:var(--text-3);cursor:default;">Sin coincidencias</div>';

                    $container.html(
                        '<div class="desktop-tag-picker__tags">' + tagsHtml +
                            '<input type="text" class="desktop-tag-picker__input" placeholder="' + escapeHtml(config.placeholder || 'Escribe para buscar') + '">' +
                        '</div>' +
                        '<div class="desktop-tag-picker__dropdown">' + dropdownHtml + '</div>'
                    );

                    const $input = $container.find('.desktop-tag-picker__input');
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
                    $container.find('.desktop-tag-picker__input').trigger('focus');
                });

                $container.on('focus', '.desktop-tag-picker__input', function () {
                    $container.addClass('is-focused');
                    $container.find('.desktop-tag-picker__dropdown').show();
                });

                $container.on('blur', '.desktop-tag-picker__input', function () {
                    window.setTimeout(function () {
                        $container.removeClass('is-focused');
                        $container.find('.desktop-tag-picker__dropdown').hide();
                    }, 150);
                });

                $container.on('input', '.desktop-tag-picker__input', function () {
                    render($(this).val(), true);
                    $container.find('.desktop-tag-picker__dropdown').show();
                });

                $container.on('keydown', '.desktop-tag-picker__input', function (e) {
                    if (e.key !== 'Enter') return;
                    e.preventDefault();

                    const first = $container.find('.desktop-tag-picker__option[data-value]').first();
                    if (first.length) first.trigger('click');
                });

                $container.on('click', '.desktop-tag-picker__option[data-value]', function () {
                    selected.add(String($(this).data('value')));
                    render('', true);
                    syncSelect();
                });

                $container.on('click', '.desktop-tag-picker__chip button', function (e) {
                    e.stopPropagation();
                    selected.delete(String($(this).closest('.desktop-tag-picker__chip').data('value')));
                    render('', true);
                    syncSelect();
                });

                tagPickers[config.name] = { setValues };
            }

            function llenarAlmacenesPorSucursal(sucursalId, seleccionado) {
                const current = String(seleccionado || '');
                const opciones = almacenesBase.filter(function (item) {
                    return String(item.alm_scl_id) === String(sucursalId || '');
                });

                const html = ['<option value="">Sin almacén asociado</option>'];
                opciones.forEach(function (item) {
                    html.push('<option value="' + item.alm_id + '">' + escapeHtml(item.alm_nombre) + '</option>');
                });

                $('#caj_alm_id').html(html.join(''));
                if (current) {
                    $('#caj_alm_id').val(current);
                }
            }

            function renderCustomFooter() {
                if (!cajasTable) return;

                const info = cajasTable.page.info();
                const total = info.recordsDisplay;

                if (!total) {
                    $('#desktop-cajas-info').text('Mostrando 0 cajas');
                    $('#desktop-cajas-pagination').empty();
                    return;
                }

                $('#desktop-cajas-info').text(
                    'Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + total + ' cajas'
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

                $('#desktop-cajas-pagination').html(html);
            }

            function limpiarFormulario() {
                $form.get(0).reset();
                $('#caj_id').val('');
                $('#caj_estatus').val('activo');
                $('#caj_clave_preview').val('Se generará automáticamente al guardar.');
                $('#desktop-caja-modal-title').text('Nueva caja');
                $('#btn-guardar-caja').text('Guardar caja');
                if (tagPickers.usuarios) tagPickers.usuarios.setValues([]);
                llenarAlmacenesPorSucursal('', '');
            }

            function initTable() {
                cajasTable = $table.DataTable({
                    ajax: {
                        url: rutas.data,
                        data: function () {
                            return {
                                buscar: $('#cajas-search').val(),
                                estatus: $('#cajas-estatus').val(),
                                caj_scl_id: $('#cajas-sucursal').val()
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
                    order: [[1, 'asc'], [0, 'asc']],
                    dom: 'rt',
                    language: {
                        processing: 'Cargando...',
                        emptyTable: 'No hay cajas registradas',
                        zeroRecords: 'No se encontraron cajas'
                    },
                    columns: [
                        {
                            data: 'caj_nombre',
                            render: function (value, type, row) {
                                return '' +
                                    '<div class="desktop-cell-primary">' +
                                        '<span class="desktop-avatar-sm">' + escapeHtml(initials(value)) + '</span>' +
                                        '<span>' +
                                            '<span class="desktop-list__name">' + escapeHtml(value) + '</span>' +
                                            '<span class="desktop-list__meta">ID ' + escapeHtml(row.caj_id) + '</span>' +
                                        '</span>' +
                                    '</div>';
                            }
                        },
                        {
                            data: 'sucursal',
                            render: function (value) {
                                return value ? '<span style="font-weight:600;">' + escapeHtml(value) + '</span>' : '<span class="desktop-list__meta">Sin sucursal</span>';
                            }
                        },
                        {
                            data: 'almacen',
                            render: function (value) {
                                return value ? escapeHtml(value) : '<span class="desktop-list__meta">Sin almacén</span>';
                            }
                        },
                        {
                            data: 'caj_clave',
                            render: function (value) {
                                return '<span style="font-weight:600;">' + escapeHtml(value) + '</span>';
                            }
                        },
                        {
                            data: 'usuarios',
                            render: function (values) {
                                const labels = (values || []).map(function (item) { return item.usr_nombre; });
                                return renderPills(labels, 'brand', 3);
                            }
                        },
                        { data: 'caj_estatus', render: renderStatus },
                        { data: null, orderable: false, searchable: false, render: renderActions }
                    ],
                    initComplete: renderCustomFooter,
                    drawCallback: renderCustomFooter
                });
            }

            function reloadTable(resetPaging) {
                if (!cajasTable) return;
                cajasTable.ajax.reload(null, !resetPaging);
            }

            function cargarCaja(id) {
                $.getJSON(rutas.show(id))
                    .done(function (response) {
                        const data = response.data || {};
                        limpiarFormulario();
                        $('#desktop-caja-modal-title').text('Editar caja');
                        $('#btn-guardar-caja').text('Guardar cambios');
                        $('#caj_id').val(data.caj_id || '');
                        $('#caj_scl_id').val(data.caj_scl_id || '');
                        $('#caj_nombre').val(data.caj_nombre || '');
                        $('#caj_estatus').val(data.caj_estatus || 'activo');
                        $('#caj_clave_preview').val(data.caj_clave || 'Se generará automáticamente al guardar.');
                        llenarAlmacenesPorSucursal(data.caj_scl_id || '', data.caj_alm_id || '');
                        if (tagPickers.usuarios) tagPickers.usuarios.setValues((data.usuarios || []).map(String));
                        openModal();
                    })
                    .fail(function (xhr) {
                        showFeedback('error', parseError(xhr));
                    });
            }

            function cambiarEstatus(id, estatus) {
                $.ajax({
                    url: rutas.estatus(id),
                    method: 'PATCH',
                    data: { caj_estatus: estatus }
                }).done(function (response) {
                    showFeedback('success', response.message || 'Estatus actualizado correctamente.');
                    reloadTable(false);
                }).fail(function (xhr) {
                    showFeedback('error', parseError(xhr));
                });
            }

            initTagPicker({ name: 'usuarios', selectId: '#usuarios', containerId: '#caja-usuarios-picker', placeholder: 'Agregar usuario' });
            initTable();
            llenarAlmacenesPorSucursal('', '');

            $('#btn-nueva-caja').on('click', function () {
                limpiarFormulario();
                openModal();
            });

            $('#btn-recargar-cajas').on('click', function () {
                reloadTable(true);
            });

            $('#cajas-sucursal, #cajas-estatus').on('change', function () {
                reloadTable(true);
            });

            $('#cajas-length').on('change', function () {
                if (!cajasTable) return;
                cajasTable.page.len(Number(this.value)).draw();
            });

            $('#cajas-search').on('input', function () {
                reloadTable(true);
            });

            $('#caj_scl_id').on('change', function () {
                llenarAlmacenesPorSucursal(this.value, '');
            });

            $('#desktop-cajas-pagination').on('click', '.desktop-pager__btn', function () {
                if ($(this).is(':disabled') || !cajasTable) return;
                cajasTable.page($(this).data('page')).draw('page');
            });

            $(document).on('click', '[data-close-caja-modal]', function () {
                closeModal();
            });

            $modal.on('click', function (event) {
                if (event.target === this) closeModal();
            });

            $table.on('click', '.btn-editar-caja', function () {
                cargarCaja($(this).data('id'));
            });

            $table.on('click', '.btn-toggle-caja', function () {
                const id = $(this).data('id');
                const estatus = $(this).data('estatus');
                const promptText = estatus === 'activo'
                    ? '¿Deseas activar esta caja?'
                    : '¿Deseas inactivar esta caja?';

                DesktopUI.confirm({ title: 'Confirmar', message: promptText }).then(function (ok) {
                    if (!ok) return;
                    cambiarEstatus(id, estatus);
                });
            });

            $form.on('submit', function (event) {
                event.preventDefault();

                const usuariosSeleccionados = $('#usuarios').val() || [];
                if (usuariosSeleccionados.length === 0) {
                    showFeedback('error', 'Debes asignar al menos un usuario a la caja.');
                    return;
                }

                const id = $('#caj_id').val();
                const url = id ? rutas.update(id) : rutas.store;

                $.ajax({
                    url: url,
                    method: id ? 'PUT' : 'POST',
                    data: $form.serialize()
                }).done(function (response) {
                    closeModal();
                    showFeedback('success', response.message || 'Caja guardada correctamente.');
                    reloadTable(false);
                }).fail(function (xhr) {
                    showFeedback('error', parseError(xhr));
                });
            });
        })();
    </script>
@endpush
