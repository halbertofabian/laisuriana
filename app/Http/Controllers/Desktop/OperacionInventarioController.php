<?php

namespace App\Http\Controllers\Desktop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operacion\Inventario\ConfirmRecepcionMercanciaRequest;
use App\Http\Requests\Operacion\Inventario\ListExistenciaMatrizRequest;
use App\Http\Requests\Operacion\Inventario\ShowKardexDetalleRequest;
use App\Http\Requests\Operacion\Inventario\StoreRecepcionMercanciaDraftRequest;
use App\Services\Operacion\ExistenciaMatrizService;
use App\Services\Operacion\InventarioBaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OperacionInventarioController extends Controller
{
    public function __construct(
        private readonly InventarioBaseService $inventarioService,
        private readonly ExistenciaMatrizService $existenciaMatrizService,
    ) {
    }

    public function index()
    {
        return redirect()->route('desktop.operacion.inventario.existencias.index');
    }

    public function existencias()
    {
        return $this->renderExistenciasView();
    }

    public function kardexDetalle(ShowKardexDetalleRequest $request, int $sku)
    {
        return view('desktop.operacion.inventario.kardex_detalle', [
            'submenus' => $this->submenus(),
            'detalle' => $this->inventarioService->obtenerKardexDetalleSku($sku, $request->validated()),
        ]);
    }

    public function existenciasNegativas()
    {
        return $this->renderExistenciasView(true);
    }

    public function negativosSesion()
    {
        $opciones = $this->inventarioService->opcionesBase();
        $usuario = auth()->user();
        $defaultSucursalId = $usuario?->sucursales()
            ->orderBy('tbl_sucursales_scl.scl_nombre')
            ->value('tbl_sucursales_scl.scl_id');

        if (!$defaultSucursalId && $opciones['sucursales']->count() === 1) {
            $defaultSucursalId = (int) $opciones['sucursales']->first()->scl_id;
        }

        return view('desktop.operacion.inventario.negativos_sesion', [
            'submenus' => $this->submenus(),
            'opciones' => $opciones,
            'sesionesCaja' => $this->inventarioService->opcionesSesionesCaja(),
            'defaultSucursalId' => $defaultSucursalId ? (int) $defaultSucursalId : null,
        ]);
    }

    public function recibir()
    {
        $usuario = auth()->user();
        $defaultSucursalId = $usuario?->sucursales()
            ->orderBy('tbl_sucursales_scl.scl_nombre')
            ->value('tbl_sucursales_scl.scl_id');

        $proveedores = DB::table('tbl_proveedores_prv')
            ->where('prv_deleted', false)
            ->whereNull('prv_deleted_at')
            ->where('prv_estatus', 'activo')
            ->orderBy('prv_nombre_empresa')
            ->get(['prv_id', 'prv_nombre_empresa']);

        $borrador = null;
        $rmeId = (int) request()->integer('rme_id', 0);
        if ($rmeId > 0) {
            try {
                $borrador = $this->inventarioService->obtenerRecepcionMercancia($rmeId);
            } catch (\Throwable $e) {
                $borrador = null;
            }
        }

        return view('desktop.operacion.inventario.recibir', [
            'submenus' => $this->submenus(),
            'opciones' => $this->inventarioService->opcionesBase(),
            'proveedores' => $proveedores,
            'defaultSucursalId' => $defaultSucursalId ? (int) $defaultSucursalId : null,
            'borrador' => $borrador,
        ]);
    }

    public function buscarProductosRecibir(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        return response()->json(
            $this->inventarioService->buscarProductosBase((string) ($datos['q'] ?? ''), (int) ($datos['page'] ?? 1), 20)
        );
    }

    public function matrizRecibir(int $producto): JsonResponse
    {
        $sucursalId = request()->integer('min_scl_id') ?: null;

        return response()->json([
            'data' => $this->inventarioService->matrizCargaInicialProducto($producto, $sucursalId),
        ]);
    }

    public function storeRecibirBorrador(StoreRecepcionMercanciaDraftRequest $request): JsonResponse
    {
        $recepcion = $this->inventarioService->guardarRecepcionMercanciaBorrador($request, $request->validated());

        return response()->json([
            'message' => 'Borrador guardado correctamente.',
            'data' => [
                'rme_id' => $recepcion->rme_id,
                'rme_folio' => $recepcion->rme_folio,
                'rme_estado' => $recepcion->rme_estado,
            ],
        ]);
    }

    public function confirmarRecibir(ConfirmRecepcionMercanciaRequest $request): JsonResponse
    {
        $resultado = $this->inventarioService->confirmarRecepcionMercancia($request, $request->validated());
        $recepcion = $resultado['recepcion'];

        return response()->json([
            'message' => 'Recepción registrada correctamente.',
            'data' => [
                'rme_id' => $recepcion->rme_id,
                'rme_folio' => $recepcion->rme_folio,
                'rme_estado' => $recepcion->rme_estado,
                'folios' => $resultado['folios'],
            ],
        ]);
    }

    public function recepciones()
    {
        return view('desktop.operacion.inventario.recepciones', [
            'submenus' => $this->submenus(),
            'opciones' => $this->inventarioService->opcionesBase(),
        ]);
    }

    public function dataRecepciones(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'estado' => ['nullable', 'string', 'max:30'],
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date'],
            'buscar' => ['nullable', 'string', 'max:120'],
        ]);

        $buscarDatatable = trim((string) $request->input('search.value', ''));
        if ($buscarDatatable !== '') {
            $datos['buscar'] = $buscarDatatable;
        }

        $resultado = $this->inventarioService->paginarRecepcionesMercancia(
            filtros: $datos,
            start: (int) $request->integer('start', 0),
            length: (int) $request->integer('length', 50),
            orderColumn: (int) $request->input('order.0.column', 0),
            orderDir: (string) $request->input('order.0.dir', 'desc'),
        );

        return response()->json([
            'draw' => (int) $request->integer('draw', 1),
            'recordsTotal' => (int) $resultado['recordsTotal'],
            'recordsFiltered' => (int) $resultado['recordsFiltered'],
            'data' => $resultado['data'],
        ]);
    }

    public function showRecepcion(int $recepcion): JsonResponse
    {
        return response()->json([
            'data' => $this->inventarioService->obtenerRecepcionMercancia($recepcion),
        ]);
    }

    public function cancelarRecepcion(Request $request, int $recepcion): JsonResponse
    {
        $datos = $request->validate([
            'motivo' => ['nullable', 'string', 'max:500'],
        ]);

        $registro = $this->inventarioService->cancelarRecepcionMercancia($request, $recepcion, (string) ($datos['motivo'] ?? ''));

        return response()->json([
            'message' => 'Recepción cancelada correctamente.',
            'data' => ['rme_id' => $registro->rme_id, 'rme_estado' => $registro->rme_estado],
        ]);
    }

    public function reportePdfRecepcion(int $recepcion): \Illuminate\Http\Response
    {
        $pdf = $this->inventarioService->descargarReporteRecepcionMercanciaPdf(request(), $recepcion);

        return response($pdf['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdf['file_name'] . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function salidas()
    {
        return view('desktop.operacion.inventario.salidas', [
            'submenus' => $this->submenus(),
            'opciones' => $this->inventarioService->opcionesBase(),
        ]);
    }

    public function salidasRegistrar()
    {
        $usuario = auth()->user();
        $defaultSucursalId = $usuario?->sucursales()
            ->orderBy('tbl_sucursales_scl.scl_nombre')
            ->value('tbl_sucursales_scl.scl_id');

        return view('desktop.operacion.inventario.salidas_form', [
            'submenus' => $this->submenus(),
            'opciones' => $this->inventarioService->opcionesBase(),
            'defaultSucursalId' => $defaultSucursalId ? (int) $defaultSucursalId : null,
        ]);
    }

    public function dataSalidas(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'min_scl_id' => ['nullable', 'integer'],
            'min_alm_id' => ['nullable', 'integer'],
            'tipo' => ['nullable', 'string', 'max:30'],
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date'],
            'buscar' => ['nullable', 'string', 'max:120'],
        ]);

        $buscarDatatable = trim((string) $request->input('search.value', ''));
        if ($buscarDatatable !== '') {
            $datos['buscar'] = $buscarDatatable;
        }

        $resultado = $this->inventarioService->paginarSalidas(
            filtros: $datos,
            start: (int) $request->integer('start', 0),
            length: (int) $request->integer('length', 50),
            orderColumn: (int) $request->input('order.0.column', 0),
            orderDir: (string) $request->input('order.0.dir', 'desc'),
        );

        return response()->json([
            'draw' => (int) $request->integer('draw', 1),
            'recordsTotal' => (int) $resultado['recordsTotal'],
            'recordsFiltered' => (int) $resultado['recordsFiltered'],
            'data' => $resultado['data'],
        ]);
    }

    public function kardex()
    {
        return view('desktop.operacion.inventario.kardex', [
            'submenus' => $this->submenus(),
            'opciones' => $this->inventarioService->opcionesBase(),
        ]);
    }

    public function dataKardex(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'min_scl_id' => ['nullable', 'integer'],
            'min_alm_id' => ['nullable', 'integer'],
            'min_psk_id' => ['nullable', 'integer'],
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date'],
            'buscar' => ['nullable', 'string', 'max:120'],
        ]);

        $buscarDatatable = trim((string) $request->input('search.value', ''));
        if ($buscarDatatable !== '') {
            $datos['buscar'] = $buscarDatatable;
        }

        $resultado = $this->inventarioService->paginarKardex(
            filtros: $datos,
            start: (int) $request->integer('start', 0),
            length: (int) $request->integer('length', 50),
            orderColumn: (int) $request->input('order.0.column', 0),
            orderDir: (string) $request->input('order.0.dir', 'desc'),
        );

        return response()->json([
            'draw' => (int) $request->integer('draw', 1),
            'recordsTotal' => (int) $resultado['recordsTotal'],
            'recordsFiltered' => (int) $resultado['recordsFiltered'],
            'data' => $resultado['data'],
        ]);
    }

    public function minimos()
    {
        return view('desktop.operacion.inventario.minimos', [
            'submenus' => $this->submenus(),
            'opciones' => $this->inventarioService->opcionesBase(),
        ]);
    }

    public function dataBajoMinimo(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'mni_scl_id' => ['nullable', 'integer'],
            'mni_alm_id' => ['nullable', 'integer'],
            'buscar' => ['nullable', 'string', 'max:120'],
        ]);

        $buscarDatatable = trim((string) $request->input('search.value', ''));
        if ($buscarDatatable !== '') {
            $datos['buscar'] = $buscarDatatable;
        }

        $resultado = $this->inventarioService->paginarBajoMinimo(
            filtros: $datos,
            start: (int) $request->integer('start', 0),
            length: (int) $request->integer('length', 50),
            orderColumn: (int) $request->input('order.0.column', 0),
            orderDir: (string) $request->input('order.0.dir', 'asc'),
        );

        return response()->json([
            'draw' => (int) $request->integer('draw', 1),
            'recordsTotal' => (int) $resultado['recordsTotal'],
            'recordsFiltered' => (int) $resultado['recordsFiltered'],
            'data' => $resultado['data'],
        ]);
    }

    public function dataExistenciasMatriz(ListExistenciaMatrizRequest $request): JsonResponse
    {
        return $this->dataExistenciasResponse($request);
    }

    public function dataExistenciasNegativas(ListExistenciaMatrizRequest $request): JsonResponse
    {
        return $this->dataExistenciasResponse($request, true);
    }

    public function buscarProductosBase(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $resultado = $this->inventarioService->buscarProductosBase(
            (string) ($datos['q'] ?? ''),
            (int) ($datos['page'] ?? 1),
            20,
            $request->only(['prd_mrc_id', 'prd_mdl_id', 'prd_lna_id', 'prd_ctg_id'])
        );

        return response()->json($resultado);
    }

    public function dataNegativosSesion(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'cse_id' => ['nullable', 'integer'],
            'min_scl_id' => ['nullable', 'integer'],
            'min_alm_id' => ['nullable', 'integer'],
            'prd_id' => ['nullable', 'integer'],
            'prd_mrc_id' => ['nullable', 'integer'],
            'prd_mdl_id' => ['nullable', 'integer'],
            'prd_lna_id' => ['nullable', 'integer'],
            'prd_ctg_id' => ['nullable', 'integer'],
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date'],
            'buscar' => ['nullable', 'string', 'max:120'],
        ]);

        $buscarDatatable = trim((string) $request->input('search.value', ''));
        if ($buscarDatatable !== '') {
            $datos['buscar'] = $buscarDatatable;
        }

        $resultado = $this->inventarioService->paginarNegativosPorSesionCaja(
            filtros: $datos,
            start: (int) $request->integer('start', 0),
            length: (int) $request->integer('length', 50),
            orderColumn: (int) $request->input('order.0.column', 0),
            orderDir: (string) $request->input('order.0.dir', 'desc'),
        );

        return response()->json([
            'draw' => (int) $request->integer('draw', 1),
            'recordsTotal' => (int) $resultado['recordsTotal'],
            'recordsFiltered' => (int) $resultado['recordsFiltered'],
            'data' => $resultado['data'],
        ]);
    }

    public function exportarExcelNegativosSesion(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filtros = $request->only([
            'cse_id',
            'min_scl_id',
            'min_alm_id',
            'prd_id',
            'prd_mrc_id',
            'prd_mdl_id',
            'prd_lna_id',
            'prd_ctg_id',
            'fecha_desde',
            'fecha_hasta',
            'buscar',
        ]);

        $filas = $this->inventarioService->listarNegativosPorSesionCaja($filtros);
        $fileName = 'negativos-por-sesion-' . now()->format('Ymd-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->streamDownload(function () use ($filas): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Fecha', 'Sesion', 'Caja', 'Sucursal', 'Almacen', 'Venta', 'SKU', 'Producto', 'Cantidad', 'Antes', 'Despues', 'Usuario venta', 'Usuario apertura']);

            foreach ($filas as $fila) {
                fputcsv($handle, [
                    $fila->min_fecha_movimiento ?? '',
                    $fila->cse_id ?? '',
                    $fila->caj_nombre ?? '',
                    $fila->scl_nombre ?? '',
                    $fila->alm_nombre ?? '',
                    $fila->psv_folio ?? '',
                    $fila->psk_codigo ?? '',
                    $fila->psk_nombre ?? $fila->prd_nombre ?? '',
                    $fila->min_cantidad ?? 0,
                    $fila->min_existencia_antes ?? 0,
                    $fila->min_existencia_despues ?? 0,
                    $fila->usuario_venta ?? '',
                    $fila->usuario_apertura ?? '',
                ]);
            }

            fclose($handle);
        }, $fileName, $headers);
    }

    public function exportarPdfNegativosSesion(Request $request): \Illuminate\Http\Response
    {
        $filtros = $request->only([
            'cse_id',
            'min_scl_id',
            'min_alm_id',
            'prd_id',
            'prd_mrc_id',
            'prd_mdl_id',
            'prd_lna_id',
            'prd_ctg_id',
            'fecha_desde',
            'fecha_hasta',
            'buscar',
        ]);

        $filas = $this->inventarioService->listarNegativosPorSesionCaja($filtros);
        $filas = collect($filas);
        $fechaGen = now()->format('d/m/Y H:i:s');

        $rowsHtml = '';
        $rowIdx = 0;
        foreach ($filas as $fila) {
            $bg = ($rowIdx % 2 === 0) ? '#f8fafc' : '#ffffff';
            $rowIdx++;

            $rowsHtml .= '<tr style="background:' . $bg . '">'
                . '<td>' . htmlspecialchars((string) ($fila->min_fecha_movimiento ?? '-')) . '</td>'
                . '<td>' . htmlspecialchars('#' . (string) ($fila->cse_id ?? '-')) . '</td>'
                . '<td>' . htmlspecialchars((string) ($fila->caj_nombre ?? '-')) . '</td>'
                . '<td>' . htmlspecialchars((string) ($fila->scl_nombre ?? '-')) . '</td>'
                . '<td>' . htmlspecialchars((string) ($fila->alm_nombre ?? '-')) . '</td>'
                . '<td>' . htmlspecialchars((string) ($fila->psv_folio ?? '-')) . '</td>'
                . '<td>' . htmlspecialchars((string) ($fila->psk_codigo ?? '-')) . '</td>'
                . '<td>' . htmlspecialchars((string) ($fila->psk_nombre ?? $fila->prd_nombre ?? '-')) . '</td>'
                . '<td style="text-align:right">' . number_format((float) ($fila->min_cantidad ?? 0), 2) . '</td>'
                . '<td style="text-align:right">' . number_format((float) ($fila->min_existencia_antes ?? 0), 2) . '</td>'
                . '<td style="text-align:right">' . number_format((float) ($fila->min_existencia_despues ?? 0), 2) . '</td>'
                . '<td>' . htmlspecialchars((string) ($fila->usuario_venta ?? '-')) . '</td>'
                . '</tr>';
        }

        $html = '
        <style>
            body { font-family: helvetica; font-size: 8px; color: #1e293b; }
            h2 { font-size: 12px; color: #1e3a5f; margin: 0 0 2px 0; }
            .meta { font-size: 7px; color: #64748b; margin-bottom: 6px; }
            table { border-collapse: collapse; width: 100%; }
            th { background: #1e3a5f; color: #fff; font-size: 7.5px; padding: 4px 3px; text-align: left; }
            td { font-size: 7.2px; padding: 3px; border-bottom: 1px solid #e2e8f0; }
        </style>
        <h2>Negativos por sesion</h2>
        <div class="meta">' . $filas->count() . ' registros &bull; Generado: ' . $fechaGen . '</div>
        <table>
            <thead>
                <tr>
                    <th>Fecha</th><th>Sesion</th><th>Caja</th><th>Sucursal</th><th>Almacen</th>
                    <th>Venta</th><th>SKU</th><th>Producto</th>
                    <th style="text-align:right">Cantidad</th>
                    <th style="text-align:right">Antes</th>
                    <th style="text-align:right">Despues</th>
                    <th>Usuario venta</th>
                </tr>
            </thead>
            <tbody>' . $rowsHtml . '</tbody>
        </table>';

        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false, false);
        $pdf->SetCreator(config('app.name', 'La Suriana Retail'));
        $pdf->SetAuthor((string) (request()->user()?->name ?? config('app.name')));
        $pdf->SetTitle('Negativos por sesion');
        $pdf->SetMargins(8, 8, 8);
        $pdf->SetAutoPageBreak(true, 8);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');

        return response($pdf->Output('negativos-por-sesion.pdf', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="negativos-por-sesion.pdf"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function exportarExcelExistenciasMatriz(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return $this->exportarExcelExistencias($request);
    }

    public function exportarExcelExistenciasNegativas(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return $this->exportarExcelExistencias($request, true);
    }

    public function exportarPdfExistenciasMatriz(Request $request): \Illuminate\Http\Response
    {
        return $this->exportarPdfExistencias($request);
    }

    public function exportarPdfExistenciasNegativas(Request $request): \Illuminate\Http\Response
    {
        return $this->exportarPdfExistencias($request, true);
    }

    private function renderExistenciasView(bool $soloNegativas = false)
    {
        $opciones = $this->inventarioService->opcionesBase();
        $usuario = auth()->user();
        $defaultSucursalId = $usuario?->sucursales()
            ->orderBy('tbl_sucursales_scl.scl_nombre')
            ->value('tbl_sucursales_scl.scl_id');

        if (!$defaultSucursalId && $opciones['sucursales']->count() === 1) {
            $defaultSucursalId = (int) $opciones['sucursales']->first()->scl_id;
        }

        return view('desktop.operacion.inventario.existencias', [
            'submenus' => $this->submenus(),
            'opciones' => $opciones,
            'defaultSucursalId' => $defaultSucursalId ? (int) $defaultSucursalId : null,
            'soloNegativas' => $soloNegativas,
            'activeSubmenu' => $soloNegativas ? 'existencias_negativas' : 'existencias',
            'pageTitle' => $soloNegativas ? 'Existencias negativas' : 'Existencias',
            'dataRoute' => $soloNegativas
                ? route('desktop.operacion.inventario.existencias_negativas.data')
                : route('desktop.operacion.inventario.existencias.data'),
            'exportExcelRoute' => $soloNegativas
                ? route('desktop.operacion.inventario.existencias_negativas.exportar.excel')
                : route('desktop.operacion.inventario.existencias.exportar.excel'),
            'exportPdfRoute' => $soloNegativas
                ? route('desktop.operacion.inventario.existencias_negativas.exportar.pdf')
                : route('desktop.operacion.inventario.existencias.exportar.pdf'),
        ]);
    }

    private function dataExistenciasResponse(ListExistenciaMatrizRequest $request, bool $soloNegativas = false): JsonResponse
    {
        $filtros = $request->only([
            'prd_id',
            'prd_mrc_id',
            'prd_mdl_id',
            'prd_lna_id',
            'prd_ctg_id',
            'buscar',
            'min_scl_id',
            'min_alm_id',
            'solo_disponibles',
        ]);

        $buscarDatatable = trim((string) $request->input('search.value', ''));
        if ($buscarDatatable !== '') {
            $filtros['buscar'] = $buscarDatatable;
        }

        $resultado = $this->existenciaMatrizService->paginarDataTable(
            filtros: $filtros,
            start: (int) $request->integer('start', 0),
            length: (int) $request->integer('length', 10),
            orderColumn: (int) $request->input('order.0.column', 0),
            orderDir: (string) $request->input('order.0.dir', 'asc'),
            soloNegativos: $soloNegativas,
        );

        return response()->json([
            'draw' => (int) $request->integer('draw', 1),
            'recordsTotal' => (int) $resultado['recordsTotal'],
            'recordsFiltered' => (int) $resultado['recordsFiltered'],
            'data' => $resultado['data'],
        ]);
    }

    private function exportarExcelExistencias(Request $request, bool $soloNegativas = false): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filtros = $request->only(['prd_mrc_id', 'prd_mdl_id', 'prd_lna_id', 'prd_ctg_id', 'prd_id', 'buscar', 'min_scl_id', 'min_alm_id', 'solo_disponibles']);
        $filas = $this->existenciaMatrizService->exportarTodos($filtros, soloNegativos: $soloNegativas);
        $baseName = $soloNegativas ? 'existencias-negativas' : 'existencias-matriz';
        $fileName = $baseName . '-' . now()->format('Ymd-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->streamDownload(function () use ($filas): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Marca', 'Modelo', 'Línea', 'Concepto', 'Producto', 'Color', 'Tallas', 'Total Art.', 'Precio Unit.', 'Costo Unit.', 'Total Importe']);

            foreach ($filas as $fila) {
                $tallasStr = collect($fila['tallas'] ?? [])
                    ->map(fn ($t) => ($t['talla'] ?? 'Base') . ':' . ($t['existencia'] === null ? 'N/D' : number_format((float) $t['existencia'], 0, '.', '')))
                    ->implode(' / ');

                fputcsv($handle, [
                    $fila['marca_nombre'] ?? '',
                    $fila['modelo_nombre'] ?? '',
                    $fila['linea_nombre'] ?? '',
                    $fila['concepto_nombre'] ?? '',
                    ($fila['producto_codigo'] ?? '') . ' - ' . ($fila['producto_nombre'] ?? ''),
                    $fila['color_nombre'] ?? '',
                    $tallasStr,
                    $fila['total_articulos'] ?? 0,
                    $fila['precio_unitario'] ?? 0,
                    $fila['costo_unitario'] ?? 0,
                    $fila['total_importe_precio'] ?? 0,
                ]);
            }

            fclose($handle);
        }, $fileName, $headers);
    }

    private function exportarPdfExistencias(Request $request, bool $soloNegativas = false): \Illuminate\Http\Response
    {
        $filtros = $request->only(['prd_mrc_id', 'prd_mdl_id', 'prd_lna_id', 'prd_ctg_id', 'prd_id', 'buscar', 'min_scl_id', 'min_alm_id']);
        $filas = $this->existenciaMatrizService->exportarTodos($filtros, soloNegativos: $soloNegativas);

        $filas = collect($filas);
        $totalArt = $filas->sum(fn ($f) => (float) ($f['total_articulos'] ?? 0));
        $totalImp = $filas->sum(fn ($f) => (float) ($f['total_importe_precio'] ?? 0));
        $filas = $filas->all();

        $rowsHtml = '';
        $rowIdx = 0;
        foreach ($filas as $fila) {
            $tallasStr = collect($fila['tallas'] ?? [])
                ->map(fn ($t) => '<b>' . htmlspecialchars($t['talla'] ?? 'Base') . '</b>:' . ($t['existencia'] === null ? '<i>N/D</i>' : number_format((float) $t['existencia'], 0, '.', '')))
                ->implode(' &nbsp;');

            $bg = ($rowIdx % 2 === 0) ? '#f8fafc' : '#ffffff';
            $rowIdx++;

            $rowsHtml .= '<tr style="background:' . $bg . '">'
                . '<td>' . htmlspecialchars($fila['marca_nombre'] ?? '-') . '</td>'
                . '<td>' . htmlspecialchars($fila['modelo_nombre'] ?? '-') . '</td>'
                . '<td>' . htmlspecialchars($fila['linea_nombre'] ?? '-') . '</td>'
                . '<td>' . htmlspecialchars($fila['concepto_nombre'] ?? '-') . '</td>'
                . '<td>' . htmlspecialchars(($fila['producto_codigo'] ?? '') . ' ' . ($fila['producto_nombre'] ?? '')) . '</td>'
                . '<td>' . htmlspecialchars($fila['color_nombre'] ?? '-') . '</td>'
                . '<td style="font-size:7px">' . $tallasStr . '</td>'
                . '<td style="text-align:right">' . number_format((float) ($fila['total_articulos'] ?? 0), 2) . '</td>'
                . '<td style="text-align:right">$ ' . number_format((float) ($fila['precio_unitario'] ?? 0), 2) . '</td>'
                . '<td style="text-align:right">$ ' . number_format((float) ($fila['costo_unitario'] ?? 0), 2) . '</td>'
                . '<td style="text-align:right">$ ' . number_format((float) ($fila['total_importe_precio'] ?? 0), 2) . '</td>'
                . '</tr>';
        }

        $fechaGen = now()->format('d/m/Y H:i:s');
        $totalRows = count($filas);
        $titulo = $soloNegativas ? 'Existencias Negativas' : 'Existencias Matriz';

        $html = '
        <style>
            body { font-family: helvetica; font-size: 8px; color: #1e293b; }
            h2 { font-size: 12px; color: #1e3a5f; margin: 0 0 2px 0; }
            .meta { font-size: 7px; color: #64748b; margin-bottom: 6px; }
            table { border-collapse: collapse; width: 100%; }
            th { background: #1e3a5f; color: #fff; font-size: 7.5px; padding: 4px 3px; text-align: left; }
            td { font-size: 7.5px; padding: 3px; border-bottom: 1px solid #e2e8f0; }
            .footer { font-size: 6.5px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 3px; margin-top: 6px; }
        </style>
        <h2>' . $titulo . '</h2>
        <div class="meta">' . $totalRows . ' registros &bull; Generado: ' . $fechaGen . '</div>
        <table>
            <thead>
                <tr>
                    <th>Marca</th><th>Modelo</th><th>Línea</th><th>Concepto</th>
                    <th>Producto</th><th>Color</th><th>Tallas</th>
                    <th style="text-align:right">Total Art.</th>
                    <th style="text-align:right">Precio</th>
                    <th style="text-align:right">Costo</th>
                    <th style="text-align:right">Total $</th>
                </tr>
            </thead>
            <tbody>' . $rowsHtml . '</tbody>
            <tfoot>
                <tr style="background:#f1f5f9">
                    <td colspan="7"><b>TOTAL</b></td>
                    <td style="text-align:right"><b>' . number_format($totalArt, 2) . '</b></td>
                    <td colspan="2"></td>
                    <td style="text-align:right"><b>$ ' . number_format($totalImp, 2) . '</b></td>
                </tr>
            </tfoot>
        </table>
        <div class="footer">La I. Suriana &bull; Documento de uso interno &bull; ' . $fechaGen . '</div>';

        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false, false);
        $pdf->SetCreator(config('app.name', 'La Suriana Retail'));
        $pdf->SetAuthor((string) (request()->user()?->name ?? config('app.name')));
        $pdf->SetTitle($titulo);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(7, 7, 7);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');

        $baseName = $soloNegativas ? 'existencias-negativas' : 'existencias-matriz';
        $fileName = $baseName . '-' . now()->format('Ymd-His') . '.pdf';
        $content = $pdf->Output('', 'S');

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Content-Length' => strlen($content),
        ]);
    }

    private function submenus(): array
    {
        return [
            ['key' => 'existencias', 'label' => 'Existencias', 'route' => route('desktop.operacion.inventario.existencias.index')],
            ['key' => 'existencias_negativas', 'label' => 'Existencias negativas', 'route' => route('desktop.operacion.inventario.existencias_negativas.index')],
            ['key' => 'negativos_sesion', 'label' => 'Negativos por sesión', 'route' => route('desktop.operacion.inventario.negativos_sesion.index')],
            ['key' => 'recepciones', 'label' => 'Recepciones', 'route' => route('desktop.operacion.inventario.recepciones.index')],
            ['key' => 'salidas', 'label' => 'Salidas', 'route' => route('desktop.operacion.inventario.salidas.index')],
            ['key' => 'kardex', 'label' => 'Kardex', 'route' => route('desktop.operacion.inventario.kardex.index')],
            ['key' => 'minimos', 'label' => 'Bajo mínimo', 'route' => route('desktop.operacion.inventario.minimos.index')],
        ];
    }

    private function renderPlaceholder(string $activeSubmenu, string $pageTitle)
    {
        return view('desktop.operacion.inventario.placeholder', [
            'activeSubmenu' => $activeSubmenu,
            'pageTitle' => $pageTitle,
            'submenus' => $this->submenus(),
        ]);
    }
}
