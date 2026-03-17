@extends('layouts.app')

@section('title', 'Demo DataTables')

@push('vendor-styles')
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('vendor-template/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endpush

@section('content')
    <x-section-header
        variant="compact"
        eyebrow="Laboratorio"
        icon="tabler-table"
        title="Demo AJAX + DataTables"
        subtitle="Visualización de inventario por sucursal con carga dinámica."
    />

    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">Inventario por sucursal (demo)</h5>
        </div>
        <div class="card-datatable table-responsive">
            <table id="inventory-table" class="table table-bordered">
                <thead>
                    <tr>
                        <th>Sucursal</th>
                        <th>Almacen</th>
                        <th>Producto</th>
                        <th>Existencia</th>
                        <th>Estado</th>
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
            const tableId = '#inventory-table';

            function loadTableData() {
                AppUI.showLoader();

                $.ajax({
                    url: '{{ route('demo.datatables.data') }}',
                    method: 'GET',
                    dataType: 'json'
                }).done(function (response) {
                    if ($.fn.DataTable.isDataTable(tableId)) {
                        $(tableId).DataTable().clear().destroy();
                    }

                    $(tableId).DataTable({
                        data: response.data || [],
                        responsive: true,
                        pageLength: 10,
                        order: [[0, 'asc']],
                        columns: [
                            { data: 'sucursal' },
                            { data: 'almacen' },
                            { data: 'producto' },
                            { data: 'existencia' },
                            {
                                data: 'estado',
                                render: function (value) {
                                    if (value === 'Disponible') {
                                        return '<span class="badge bg-label-success">' + value + '</span>';
                                    }
                                    if (value === 'Bajo stock') {
                                        return '<span class="badge bg-label-warning">' + value + '</span>';
                                    }

                                    return '<span class="badge bg-label-danger">' + value + '</span>';
                                }
                            }
                        ]
                    });
                }).fail(function () {
                    AppUI.showMessage('Error', 'No fue posible cargar los datos de la tabla.');
                }).always(function () {
                    AppUI.hideLoader();
                });
            }

            loadTableData();
        })();
    </script>
@endpush
