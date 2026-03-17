@extends('layouts.app')

@section('title', 'Permisos')

@push('vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
@endpush

@section('content')
<x-section-header
    variant="compact"
    eyebrow="Catálogo base"
    icon="tabler-shield-lock"
    title="Permisos"
    subtitle="Catálogo de permisos por acción de negocio."
/>

<div class="card">
    <div class="card-datatable table-responsive">
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
@endsection

@push('vendor-scripts')
    <script src="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endpush

@push('page-scripts')
<script>
(function () {
    AppUI.showLoader();

    $.getJSON('{{ route('seguridad.permisos.data') }}').done(function (response) {
        $('#permisos-table').DataTable({
            data: response.data || [],
            order: [[0, 'asc']],
            columns: [
                { data: 'prm_clave' },
                { data: 'prm_descripcion' },
                { data: 'prm_modulo' },
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
    }).fail(function () {
        AppUI.showMessage('Error', 'No fue posible cargar el catalogo de permisos.');
    }).always(function () {
        AppUI.hideLoader();
    });
})();
</script>
@endpush
