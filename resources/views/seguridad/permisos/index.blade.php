@extends('layouts.app')

@section('title', 'Permisos')

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
        .tag-chip.permission { background:var(--ls-surface-3);color:var(--ls-text-muted);border-color:var(--ls-border); }
    </style>
@endpush

@section('content')
<x-section-header
    eyebrow="Seguridad"
    icon="tabler-key"
    title="Permisos"
    subtitle="Catálogo de permisos disponibles por módulo del sistema."
/>

<div class="card">
    <div class="card-header d-flex align-items-center gap-3 flex-wrap" id="permisos-toolbar-wrapper">
        <span></span>
    </div>
    <div class="table-responsive">
        <table id="permisos-table" class="table mb-0">
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

    function escapeHtml(v) {
        return String(v || '').replaceAll('&','&amp;').replaceAll('<','&lt;')
            .replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#39;');
    }

    AppUI.showLoader();
    $.getJSON('{{ route('seguridad.permisos.data') }}').done(function (response) {
        $('#permisos-table').DataTable({
            data: response.data || [],
            order: [[0, 'asc']],
            responsive: true, autoWidth: false, pageLength: 25, lengthMenu: [10, 25, 50],
            language: datatableLanguage,
            initComplete: function () {
                const wrapper = $('#permisos-table_wrapper');
                wrapper.addClass('datatable-toolbar');
                const filter = wrapper.find('.dataTables_filter').detach();
                const length = wrapper.find('.dataTables_length').detach();
                $('#permisos-toolbar-wrapper').html(
                    '<div class="d-flex align-items-center gap-3 w-100 flex-wrap">' +
                    (length.length ? length[0].outerHTML : '') + (filter.length ? filter[0].outerHTML : '') + '</div>'
                );
            },
            columns: [
                { data: 'prm_clave', render: function (v) { return '<span class="fw-semibold">' + escapeHtml(v) + '</span>'; } },
                { data: 'prm_descripcion', render: function (v) { return escapeHtml(v); } },
                { data: 'prm_modulo', render: function (v) {
                    return '<span class="tag-chip permission" style="text-transform:none;">' + escapeHtml(v) + '</span>';
                }},
                { data: 'prm_estatus', render: function (v) {
                    return v === 'activo'
                        ? '<span class="ls-badge ls-badge-success">Activo</span>'
                        : '<span class="ls-badge ls-badge-danger">Inactivo</span>';
                }}
            ]
        });
    }).fail(function () {
        AppUI.showMessage('Error', 'No fue posible cargar el catálogo de permisos.');
    }).always(function () { AppUI.hideLoader(); });
})();
</script>
@endpush
