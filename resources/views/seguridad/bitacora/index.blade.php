@extends('layouts.app')

@section('title', 'Bitácora')

@push('vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <style>
        .datatable-toolbar .dataTables_filter label,
        .datatable-toolbar .dataTables_length label { font-size:.82rem;font-weight:500;color:var(--ls-text-muted); }
        .datatable-toolbar .dataTables_filter input,
        .datatable-toolbar .dataTables_length select { min-height:2.1rem;border-radius:var(--ls-radius);border:1px solid var(--ls-border);font-size:.84rem; }
        .dataTables_paginate .paginate_button { border-radius:var(--ls-radius-sm)!important;font-size:.8rem!important; }
        .dataTables_paginate .paginate_button.current,
        .dataTables_paginate .paginate_button.current:hover { background:var(--ls-accent)!important;color:#fff!important;border-color:var(--ls-accent)!important; }
        .tag-chip { display:inline-flex;align-items:center;border-radius:.35rem;padding:.18rem .55rem;font-size:.73rem;font-weight:600;line-height:1.4;border:1px solid transparent; }
        .tag-chip.branch { background:rgba(26,158,109,.08);color:#1a9e6d;border-color:rgba(26,158,109,.2); }
        .tag-chip.permission { background:var(--ls-surface-3);color:var(--ls-text-muted);border-color:var(--ls-border); }
        .tag-list { display:flex;flex-wrap:wrap;gap:.3rem; }
        .bitacora-filters { background:var(--ls-surface);border:1px solid var(--ls-border);border-radius:var(--ls-radius-lg);padding:1rem 1.25rem;margin-bottom:1.25rem; }
    </style>
@endpush

@section('content')
<x-section-header
    eyebrow="Seguridad"
    icon="tabler-clipboard-list"
    title="Bitácora"
    subtitle="Registro de accesos y acciones realizadas en el sistema."
/>

{{-- Filtros --}}
<div class="bitacora-filters mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Usuario</label>
            <select id="flt-bitacora-usuario" class="form-select">
                <option value="">Todos</option>
            </select>
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
        <div class="col-md-2">
            <label class="form-label">Evento</label>
            <select id="flt-bitacora-accion" class="form-select">
                <option value="">Todos</option>
            </select>
        </div>
        <div class="col-md-1 d-flex gap-2">
            <button type="button" class="btn btn-primary btn-sm w-100" id="btn-aplicar-filtros">
                <i class="ti tabler-filter me-1"></i>Filtrar
            </button>
        </div>
    </div>
    <div class="mt-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-limpiar-filtros">
            <i class="ti tabler-refresh me-1"></i>Limpiar filtros
        </button>
    </div>
</div>

{{-- Sub-tabs --}}
<div class="card">
    <div class="card-header" style="padding-bottom:0;">
        <ul class="nav nav-tabs" id="bitacoraTabs" role="tablist" style="margin-bottom:0;border-bottom:none;">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-accesos" data-bs-toggle="tab" data-bs-target="#panel-accesos"
                    type="button" role="tab" aria-selected="true">
                    <i class="ti tabler-login me-1"></i>Accesos
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-acciones" data-bs-toggle="tab" data-bs-target="#panel-acciones"
                    type="button" role="tab" aria-selected="false">
                    <i class="ti tabler-activity me-1"></i>Acciones
                </button>
            </li>
        </ul>
    </div>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="panel-accesos" role="tabpanel" tabindex="0">
            <div class="table-responsive">
                <table id="bitacora-accesos-table" class="table mb-0">
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
        </div>
        <div class="tab-pane fade" id="panel-acciones" role="tabpanel" tabindex="0">
            <div class="table-responsive">
                <table id="bitacora-acciones-table" class="table mb-0">
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
    </div>
</div>
@endsection

@push('vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endpush

@push('page-scripts')
<script>
(function () {
    const datatableLanguage = {
        lengthMenu: '_MENU_ registros por página',
        zeroRecords: 'No se encontraron registros',
        info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
        infoEmpty: 'Mostrando 0 a 0 de 0 registros',
        infoFiltered: '(filtrado de _MAX_ registros totales)',
        search: 'Buscar:', paginate: { first: '«', last: '»', next: '›', previous: '‹' }
    };

    const bitacoraOpciones = { usuarios: new Map(), eventos: new Map() };

    function escapeHtml(v) {
        return String(v || '').replaceAll('&','&amp;').replaceAll('<','&lt;')
            .replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#39;');
    }

    function normalizarOpcionTexto(v) { return String(v || '').trim(); }

    function registrarOpcionMapa(mapa, valor, etiqueta) {
        const key = normalizarOpcionTexto(valor);
        if (!key) return;
        const label = normalizarOpcionTexto(etiqueta) || key;
        if (!mapa.has(key)) mapa.set(key, label);
    }

    function poblarSelectBitacora(selector, mapaOpciones) {
        const $s = $(selector);
        const valorActual = String($s.val() || '');
        const unicos = Array.from((mapaOpciones || new Map()).entries())
            .map(function (e) { return { value: normalizarOpcionTexto(e[0]), label: normalizarOpcionTexto(e[1]) }; })
            .filter(function (i) { return i.value !== ''; })
            .sort(function (a, b) { return a.label.localeCompare(b.label, 'es', { sensitivity: 'base' }); });
        const opciones = ['<option value="">Todos</option>'].concat(
            unicos.map(function (i) { return '<option value="' + escapeHtml(i.value) + '">' + escapeHtml(i.label) + '</option>'; })
        );
        $s.html(opciones.join(''));
        if (valorActual && unicos.some(function (i) { return i.value === valorActual; })) {
            $s.val(valorActual);
        }
    }

    function establecerFechasUltimaSemana() {
        const hoy   = new Date();
        const desde = new Date(hoy);
        desde.setDate(hoy.getDate() - 6);
        const fmt = function (d) {
            return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
        };
        $('#flt-bitacora-desde').val(fmt(desde));
        $('#flt-bitacora-hasta').val(fmt(hoy));
    }

    function filtros() {
        return {
            usuario: $('#flt-bitacora-usuario').val(),
            fecha_desde: $('#flt-bitacora-desde').val(),
            fecha_hasta: $('#flt-bitacora-hasta').val(),
            resultado: $('#flt-bitacora-resultado').val(),
            accion: $('#flt-bitacora-accion').val()
        };
    }

    function cargarAccesos() {
        AppUI.showLoader();
        $.getJSON('{{ route('seguridad.bitacora.accesos') }}', filtros()).done(function (response) {
            (response.data || []).forEach(function (item) {
                const uc = normalizarOpcionTexto(item.usuario_intentado);
                const ui = normalizarOpcionTexto(item.usuario_registrado);
                if (uc) registrarOpcionMapa(bitacoraOpciones.usuarios, uc, uc);
                if (ui) registrarOpcionMapa(bitacoraOpciones.usuarios, ui, ui);
            });
            poblarSelectBitacora('#flt-bitacora-usuario', bitacoraOpciones.usuarios);

            if ($.fn.DataTable.isDataTable('#bitacora-accesos-table')) {
                $('#bitacora-accesos-table').DataTable().clear().destroy();
            }
            $('#bitacora-accesos-table').DataTable({
                data: response.data || [],
                order: [[0, 'desc']],
                responsive: true, autoWidth: false, pageLength: 10, lengthMenu: [10, 25, 50],
                language: datatableLanguage,
                initComplete: function () { $('#bitacora-accesos-table_wrapper').addClass('datatable-toolbar'); },
                columns: [
                    { data: 'fecha' },
                    { data: 'usuario_intentado', render: function (v) { return '<span class="fw-semibold">' + escapeHtml(v || '-') + '</span>'; } },
                    { data: null, render: function (row) { return escapeHtml(row.nombre_registrado || row.usuario_registrado || '-'); } },
                    { data: 'resultado', render: function (v) {
                        return v === 'exitoso'
                            ? '<span class="ls-badge ls-badge-success">Acceso permitido</span>'
                            : '<span class="ls-badge ls-badge-danger">Acceso denegado</span>';
                    }},
                    { data: 'motivo', defaultContent: '-', render: function (v) { return '<small style="text-transform:none;">' + escapeHtml(v || '-') + '</small>'; } },
                    { data: 'ip', defaultContent: '-', render: function (v) { return escapeHtml(v || '-'); } }
                ]
            });
        }).fail(function () { AppUI.showMessage('Error', 'No fue posible cargar la bitácora de accesos.'); })
        .always(function () { AppUI.hideLoader(); });
    }

    function cargarAcciones() {
        AppUI.showLoader();
        $.getJSON('{{ route('seguridad.bitacora.acciones') }}', filtros()).done(function (response) {
            (response.data || []).forEach(function (item) {
                const ul = normalizarOpcionTexto(item.usuario_login);
                const ua = normalizarOpcionTexto(item.usuario);
                const ek = normalizarOpcionTexto(item.accion);
                const el = normalizarOpcionTexto(item.evento);
                if (ul || ua) registrarOpcionMapa(bitacoraOpciones.usuarios, ul || ua, ua || ul);
                if (ek || el) registrarOpcionMapa(bitacoraOpciones.eventos, ek || el, el || ek);
            });
            poblarSelectBitacora('#flt-bitacora-usuario', bitacoraOpciones.usuarios);
            poblarSelectBitacora('#flt-bitacora-accion', bitacoraOpciones.eventos);

            if ($.fn.DataTable.isDataTable('#bitacora-acciones-table')) {
                $('#bitacora-acciones-table').DataTable().clear().destroy();
            }
            $('#bitacora-acciones-table').DataTable({
                data: response.data || [],
                order: [[0, 'desc']],
                responsive: true, autoWidth: false, pageLength: 10, lengthMenu: [10, 25, 50],
                language: datatableLanguage,
                initComplete: function () { $('#bitacora-acciones-table_wrapper').addClass('datatable-toolbar'); },
                columns: [
                    { data: 'fecha' },
                    { data: 'evento', render: function (v) { return '<span class="tag-chip permission" style="text-transform:none;">' + escapeHtml(v || '-') + '</span>'; } },
                    { data: 'detalle', render: function (v) { return '<span class="fw-semibold">' + escapeHtml(v || '-') + '</span>'; } },
                    { data: 'usuario', defaultContent: '-', render: function (v) { return escapeHtml(v || '-'); } },
                    { data: 'sucursal', defaultContent: '-', render: function (v) {
                        return v ? '<span class="tag-chip branch" style="text-transform:none;">' + escapeHtml(v) + '</span>' : '-';
                    }},
                    { data: 'ip', defaultContent: '-', render: function (v) { return escapeHtml(v || '-'); } },
                    { data: 'payload', render: function (v) {
                        if (!v || v === 'Sin detalle adicional') return '<span class="text-body-secondary" style="text-transform:none;">Sin detalle</span>';
                        return '<small title="' + escapeHtml(v) + '" style="text-transform:none;">' + escapeHtml(v) + '</small>';
                    }}
                ]
            });
        }).fail(function () { AppUI.showMessage('Error', 'No fue posible cargar la bitácora de acciones.'); })
        .always(function () { AppUI.hideLoader(); });
    }

    $('#btn-aplicar-filtros').on('click', function () { cargarAccesos(); cargarAcciones(); });

    $('#btn-limpiar-filtros').on('click', function () {
        $('#flt-bitacora-usuario, #flt-bitacora-resultado, #flt-bitacora-accion').val('');
        establecerFechasUltimaSemana();
        cargarAccesos(); cargarAcciones();
    });

    document.querySelectorAll('#bitacoraTabs button[data-bs-toggle="tab"]').forEach(function (btn) {
        btn.addEventListener('shown.bs.tab', function () {
            if ($.fn.DataTable.isDataTable('#bitacora-accesos-table')) {
                $('#bitacora-accesos-table').DataTable().columns.adjust().responsive.recalc();
            }
            if ($.fn.DataTable.isDataTable('#bitacora-acciones-table')) {
                $('#bitacora-acciones-table').DataTable().columns.adjust().responsive.recalc();
            }
        });
    });

    establecerFechasUltimaSemana();
    cargarAccesos();
    cargarAcciones();
})();
</script>
@endpush
