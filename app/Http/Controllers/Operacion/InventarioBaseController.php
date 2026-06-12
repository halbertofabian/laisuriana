<?php

namespace App\Http\Controllers\Operacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operacion\Inventario\CancelarMovimientoInventarioRequest;
use App\Http\Requests\Operacion\Inventario\ConfirmRecepcionMercanciaRequest;
use App\Http\Requests\Operacion\Inventario\CorregirMovimientoInventarioRequest;
use App\Http\Requests\Operacion\Inventario\ListExistenciaMatrizRequest;
use App\Http\Requests\Operacion\Inventario\ShowKardexDetalleRequest;
use App\Http\Requests\Operacion\Inventario\StoreEntradaInventarioRequest;
use App\Http\Requests\Operacion\Inventario\StoreInventarioInicialRequest;
use App\Http\Requests\Operacion\Inventario\StoreInventarioInicialMasivoRequest;
use App\Http\Requests\Operacion\Inventario\StoreMinimoInventarioRequest;
use App\Http\Requests\Operacion\Inventario\StoreRecepcionMercanciaDraftRequest;
use App\Http\Requests\Operacion\Inventario\StoreSalidaInventarioRequest;
use App\Models\ProductoSku;
use App\Services\Operacion\EscaneoProductoService;
use App\Services\Operacion\ExistenciaMatrizService;
use App\Services\Operacion\InventarioBaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InventarioBaseController extends Controller
{
    public function __construct(
        private readonly InventarioBaseService $inventarioService,
        private readonly ExistenciaMatrizService $existenciaMatrizService,
        private readonly EscaneoProductoService $escaneoProductoService,
    ) {
    }

    public function index()
    {
        return redirect()->route('operacion.inventario_base.existencias.index');
    }

    public function existencias()
    {
        return $this->renderInventarioBase(false, 'existencias');
    }

    public function existenciasNegativas()
    {
        return view('operacion.inventario_base.existencias_negativas', [
            'opciones' => $this->inventarioService->opcionesBase(),
        ]);
    }

    public function dataExistenciasNegativas(ListExistenciaMatrizRequest $request): JsonResponse
    {
        $filtros = $request->only([
            'prd_id',
            'prd_mrc_id',
            'prd_mdl_id',
            'prd_lna_id',
            'prd_ctg_id',
            'buscar',
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
            soloNegativos: true,
        );

        return response()->json([
            'draw' => (int) $request->integer('draw', 1),
            'recordsTotal' => (int) $resultado['recordsTotal'],
            'recordsFiltered' => (int) $resultado['recordsFiltered'],
            'data' => $resultado['data'],
        ]);
    }

    public function existenciasMatriz()
    {
        $opciones = $this->inventarioService->opcionesBase();
        $usuario = auth()->user();
        $defaultSucursalId = $usuario?->sucursales()
            ->orderBy('tbl_sucursales_scl.scl_nombre')
            ->value('tbl_sucursales_scl.scl_id');

        if (!$defaultSucursalId && $opciones['sucursales']->count() === 1) {
            $defaultSucursalId = (int) $opciones['sucursales']->first()->scl_id;
        }

        return view('operacion.inventario_base.existencias_matriz', [
            'opciones' => $opciones,
            'defaultSucursalId' => $defaultSucursalId ? (int) $defaultSucursalId : null,
        ]);
    }

    public function exportarExcelExistenciasMatriz(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filtros = $request->only(['prd_mrc_id', 'prd_mdl_id', 'prd_lna_id', 'prd_ctg_id', 'prd_id', 'buscar', 'min_scl_id', 'min_alm_id']);
        $filas   = $this->existenciaMatrizService->exportarTodos($filtros);

        $fileName = 'existencias-matriz-' . now()->format('Ymd-His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->streamDownload(function () use ($filas): void {
            $handle = fopen('php://output', 'w');
            // BOM UTF-8 para compatibilidad con Excel en Windows
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Marca', 'Modelo', 'Línea', 'Concepto', 'Producto', 'Color', 'Tallas', 'Total Art.', 'Precio Unit.', 'Costo Unit.', 'Total Importe']);

            foreach ($filas as $fila) {
                $tallasStr = collect($fila['tallas'] ?? [])
                    ->map(fn ($t) => ($t['talla'] ?? 'Base') . ':' . ($t['existencia'] === null ? 'N/D' : number_format((float) $t['existencia'], 0, '.', '')))
                    ->implode(' / ');

                fputcsv($handle, [
                    $fila['marca_nombre']          ?? '',
                    $fila['modelo_nombre']          ?? '',
                    $fila['linea_nombre']           ?? '',
                    $fila['concepto_nombre']        ?? '',
                    ($fila['producto_codigo'] ?? '') . ' - ' . ($fila['producto_nombre'] ?? ''),
                    $fila['color_nombre']           ?? '',
                    $tallasStr,
                    $fila['total_articulos']        ?? 0,
                    $fila['precio_unitario']        ?? 0,
                    $fila['costo_unitario']         ?? 0,
                    $fila['total_importe_precio']   ?? 0,
                ]);
            }

            fclose($handle);
        }, $fileName, $headers);
    }

    public function exportarPdfExistenciasMatriz(Request $request): \Illuminate\Http\Response
    {
        $filtros = $request->only(['prd_mrc_id', 'prd_mdl_id', 'prd_lna_id', 'prd_ctg_id', 'prd_id', 'buscar', 'min_scl_id', 'min_alm_id']);
        $filas   = $this->existenciaMatrizService->exportarTodos($filtros);

        $filas = collect($filas);
        $total_art  = $filas->sum(fn ($f) => (float) ($f['total_articulos'] ?? 0));
        $total_imp  = $filas->sum(fn ($f) => (float) ($f['total_importe_precio'] ?? 0));

        $filas = $filas->all();

        $rowsHtml = '';
        $rowIdx   = 0;
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

        $fechaGen  = now()->format('d/m/Y H:i:s');
        $totalRows = count($filas);

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
        <h2>Existencias Matriz</h2>
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
                    <td style="text-align:right"><b>' . number_format($total_art, 2) . '</b></td>
                    <td colspan="2"></td>
                    <td style="text-align:right"><b>$ ' . number_format($total_imp, 2) . '</b></td>
                </tr>
            </tfoot>
        </table>
        <div class="footer">La I. Suriana &bull; Documento de uso interno &bull; ' . $fechaGen . '</div>';

        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false, false);
        $pdf->SetCreator(config('app.name', 'La Suriana Retail'));
        $pdf->SetAuthor((string) ($request->user()?->name ?? config('app.name')));
        $pdf->SetTitle('Existencias Matriz');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(7, 7, 7);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');

        $fileName = 'existencias-matriz-' . now()->format('Ymd-His') . '.pdf';
        $content  = $pdf->Output('', 'S');

        return response($content, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Content-Length'      => strlen($content),
        ]);
    }

    public function entradas()
    {
        return $this->renderInventarioBase(false, 'entradas');
    }

    public function recibir()
    {
        return $this->renderInventarioBase(false, 'recibir');
    }

    public function recepciones()
    {
        return $this->renderInventarioBase(false, 'recepciones');
    }

    public function salidas()
    {
        return $this->renderInventarioBase(false, 'salidas');
    }

    public function kardex()
    {
        return $this->renderInventarioBase(false, 'kardex');
    }

    public function kardexDetalle(ShowKardexDetalleRequest $request, int $sku)
    {
        return view('operacion.inventario_base.kardex_detalle', [
            'detalle' => $this->inventarioService->obtenerKardexDetalleSku($sku, $request->validated()),
        ]);
    }

    public function negativosPorSesion()
    {
        return view('operacion.inventario_base.negativos_sesion', [
            'opciones' => $this->inventarioService->opcionesBase(),
            'sesionesCaja' => $this->inventarioService->opcionesSesionesCaja(),
        ]);
    }

    public function minimos()
    {
        return $this->renderInventarioBase(false, 'minimos');
    }

    public function reportes()
    {
        return $this->renderInventarioBase(false, 'reportes');
    }

    public function wizardEntradas()
    {
        return $this->renderInventarioBase(true, 'entradas');
    }

    private function renderInventarioBase(bool $soloEntradas, string $vistaActiva)
    {
        return view('operacion.inventario_base.index', [
            'opciones' => $this->inventarioService->opcionesBase(),
            'soloEntradas' => $soloEntradas,
            'vistaActiva' => $vistaActiva,
            'permisosUI' => [
                'ver' => auth()->user()?->tienePermiso('inventario_base.ver') ?? false,
                'inicial' => auth()->user()?->tienePermiso('inventario_base.inicial') ?? false,
                'entrada' => auth()->user()?->tienePermiso('inventario_base.entrada') ?? false,
                'salida' => auth()->user()?->tienePermiso('inventario_base.salida') ?? false,
                'ajustar' => auth()->user()?->tienePermiso('inventario_base.ajustar') ?? false,
                'cancelar' => auth()->user()?->tienePermiso('inventario_base.cancelar') ?? false,
                'corregir' => auth()->user()?->tienePermiso('inventario_base.corregir') ?? false,
                'minimos' => auth()->user()?->tienePermiso('inventario_base.minimos') ?? false,
            ],
        ]);
    }

    public function dataExistencias(Request $request): JsonResponse
    {
        $filtros = $request->only([
            'min_scl_id',
            'min_alm_id',
            'min_psk_id',
            'buscar',
        ]);

        if ($request->boolean('solo_negativas')) {
            $filtros['solo_negativas'] = true;
        }

        $registros = $this->inventarioService->listarExistencias($filtros);

        return response()->json(['data' => $registros]);
    }

    public function dataExistenciasMatriz(ListExistenciaMatrizRequest $request): JsonResponse
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
        );

        return response()->json([
            'draw' => (int) $request->integer('draw', 1),
            'recordsTotal' => (int) $resultado['recordsTotal'],
            'recordsFiltered' => (int) $resultado['recordsFiltered'],
            'data' => $resultado['data'],
        ]);
    }

    public function dataProductosBase(Request $request): JsonResponse
    {
        $filtros = $request->only([
            'prd_mrc_id',
            'prd_mdl_id',
            'prd_lna_id',
            'prd_ctg_id',
            'buscar',
            'limite',
        ]);

        if ($request->has('draw')) {
            $buscarDatatable = trim((string) $request->input('search.value', ''));
            if ($buscarDatatable !== '') {
                $filtros['buscar'] = $buscarDatatable;
            }

            $resultado = $this->inventarioService->paginarProductosBaseDataTable(
                filtros: $filtros,
                start: (int) $request->integer('start', 0),
                length: (int) $request->integer('length', 10),
                orderColumn: (int) $request->input('order.0.column', 1),
                orderDir: (string) $request->input('order.0.dir', 'asc'),
            );

            return response()->json([
                'draw' => (int) $request->integer('draw', 1),
                'recordsTotal' => (int) $resultado['recordsTotal'],
                'recordsFiltered' => (int) $resultado['recordsFiltered'],
                'data' => $resultado['data'],
            ]);
        }

        $registros = $this->inventarioService->listarProductosBase($filtros);
        return response()->json(['data' => $registros]);
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

    public function buscarSkus(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $resultado = $this->inventarioService->buscarSkusActivos(
            (string) ($datos['q'] ?? ''),
            (int) ($datos['page'] ?? 1)
        );

        return response()->json($resultado);
    }

    public function dataKardex(Request $request): JsonResponse
    {
        $registros = $this->inventarioService->listarKardex($request->only([
            'min_scl_id',
            'min_alm_id',
            'min_psk_id',
            'fecha_desde',
            'fecha_hasta',
        ]));

        return response()->json(['data' => $registros]);
    }

    public function dataNegativosPorSesion(Request $request): JsonResponse
    {
        $registros = $this->inventarioService->listarNegativosPorSesionCaja($request->only([
            'cse_id',
            'min_scl_id',
            'min_alm_id',
            'fecha_desde',
            'fecha_hasta',
            'buscar',
        ]));

        return response()->json(['data' => $registros]);
    }

    public function dataBajoMinimo(Request $request): JsonResponse
    {
        $registros = $this->inventarioService->listarBajoMinimo($request->only([
            'mni_scl_id',
            'mni_alm_id',
        ]));

        return response()->json(['data' => $registros]);
    }

    public function dataReportesEntradas(Request $request): JsonResponse
    {
        $registros = $this->inventarioService->listarReportesEntradasPdf($request->only([
            'fecha_desde',
            'fecha_hasta',
        ]));

        return response()->json(['data' => $registros]);
    }

    public function dataRecepcionesMercancia(Request $request): JsonResponse
    {
        $registros = $this->inventarioService->listarRecepcionesMercancia($request->only([
            'estado',
            'fecha_desde',
            'fecha_hasta',
        ]));

        return response()->json(['data' => $registros]);
    }

    public function showRecepcionMercancia(int $recepcion): JsonResponse
    {
        return response()->json([
            'data' => $this->inventarioService->obtenerRecepcionMercancia($recepcion),
        ]);
    }

    public function disponibilidad(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'min_psk_id' => ['required', 'integer'],
            'min_scl_id' => ['required', 'integer'],
            'min_alm_id' => ['required', 'integer'],
        ], [
            'min_psk_id.required' => 'El SKU es obligatorio.',
            'min_scl_id.required' => 'La sucursal es obligatoria.',
            'min_alm_id.required' => 'El almacén es obligatorio.',
        ]);

        $resultado = $this->inventarioService->disponibilidad(
            (int) $datos['min_psk_id'],
            (int) $datos['min_scl_id'],
            (int) $datos['min_alm_id'],
        );

        return response()->json([
            'message' => 'Disponibilidad consultada correctamente.',
            'data' => $resultado,
        ]);
    }

    public function resolverSalida(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'min_psk_id' => [
                'nullable',
                'integer',
                Rule::exists('tbl_producto_skus_psk', 'psk_id')->where(fn ($query) => $query
                    ->where('psk_deleted', false)
                    ->whereNull('psk_deleted_at')
                    ->where('psk_estatus', 'activo')),
            ],
            'min_scl_id' => ['required', 'integer'],
            'min_alm_id' => ['required', 'integer'],
        ]);

        $consulta = trim((string) ($datos['q'] ?? ''));
        $skuId = (int) ($datos['min_psk_id'] ?? 0);

        if ($consulta === '' && $skuId <= 0) {
            throw ValidationException::withMessages([
                'q' => 'Debes capturar un código o seleccionar un SKU.',
            ]);
        }

        if ($skuId > 0) {
            $sku = ProductoSku::query()
                ->with([
                    'producto:prd_id,prd_codigo,prd_codigo_barras,prd_nombre,prd_umd_id',
                    'producto.unidad:umd_id,umd_nombre,umd_codigo',
                ])
                ->where('psk_id', $skuId)
                ->where('psk_estatus', 'activo')
                ->first();

            if (!$sku) {
                return response()->json([
                    'message' => 'El SKU seleccionado ya no está disponible.',
                ], 404);
            }

            $resultado = [
                'psk_id' => (int) $sku->psk_id,
                'psk_codigo' => (string) $sku->psk_codigo,
                'psk_codigo_barras' => (string) ($sku->psk_codigo_barras ?? ''),
                'psk_nombre' => (string) ($sku->psk_nombre ?? ''),
                'producto' => [
                    'prd_id' => (int) ($sku->producto?->prd_id ?? 0),
                    'prd_codigo' => (string) ($sku->producto?->prd_codigo ?? ''),
                    'prd_nombre' => (string) ($sku->producto?->prd_nombre ?? ''),
                ],
                'unidad' => [
                    'umd_nombre' => (string) ($sku->producto?->unidad?->umd_nombre ?? ''),
                    'umd_codigo' => (string) ($sku->producto?->unidad?->umd_codigo ?? ''),
                ],
            ];
        } else {
            $resultado = $this->escaneoProductoService->buscar($consulta);

            if (!$resultado) {
                return response()->json([
                    'message' => 'No encontramos coincidencias para el código enviado.',
                ], 404);
            }

            $sku = ProductoSku::query()
                ->with(['producto.unidad:umd_id,umd_nombre,umd_codigo'])
                ->find($resultado['psk_id']);

            $resultado['unidad'] = [
                'umd_nombre' => (string) ($sku?->producto?->unidad?->umd_nombre ?? ''),
                'umd_codigo' => (string) ($sku?->producto?->unidad?->umd_codigo ?? ''),
            ];
        }

        $resultado['existencia'] = (float) ($this->inventarioService->disponibilidad(
            (int) $resultado['psk_id'],
            (int) $datos['min_scl_id'],
            (int) $datos['min_alm_id'],
        )['existencia'] ?? 0);

        return response()->json([
            'message' => 'Producto localizado correctamente.',
            'data' => $resultado,
        ]);
    }

    public function matrizProducto(int $producto): JsonResponse
    {
        $sucursalId = request()->integer('min_scl_id') ?: null;
        $resultado = $this->inventarioService->matrizCargaInicialProducto($producto, $sucursalId);

        return response()->json([
            'message' => 'Matriz de variantes cargada correctamente.',
            'data' => $resultado,
        ]);
    }

    public function storeInventarioInicial(StoreInventarioInicialRequest $request): JsonResponse
    {
        $movimiento = $this->inventarioService->registrarInventarioInicial($request, $request->validated());

        return response()->json([
            'message' => 'Inventario inicial registrado correctamente.',
            'data' => ['min_id' => $movimiento->min_id, 'min_folio' => $movimiento->min_folio],
        ]);
    }

    public function storeInventarioInicialMasivo(StoreInventarioInicialMasivoRequest $request): JsonResponse
    {
        $resultado = $this->inventarioService->registrarInventarioInicialMasivo($request, $request->validated());

        return response()->json([
            'message' => 'Carga inicial registrada correctamente para las variantes capturadas.',
            'data' => $resultado,
        ]);
    }

    public function storeEntradaMasiva(StoreInventarioInicialMasivoRequest $request): JsonResponse
    {
        $resultado = $this->inventarioService->registrarInventarioInicialMasivo($request, $request->validated());

        return response()->json([
            'message' => 'Entrada registrada correctamente para las variantes capturadas.',
            'data' => $resultado,
        ]);
    }

    public function storeRecepcionMercanciaDraft(StoreRecepcionMercanciaDraftRequest $request): JsonResponse
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

    public function confirmRecepcionMercancia(ConfirmRecepcionMercanciaRequest $request): JsonResponse
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

    public function cancelarRecepcionMercancia(Request $request, int $recepcion): JsonResponse
    {
        $datos = $request->validate([
            'motivo' => ['nullable', 'string', 'max:500'],
        ]);

        $registro = $this->inventarioService->cancelarRecepcionMercancia($request, $recepcion, (string) ($datos['motivo'] ?? ''));

        return response()->json([
            'message' => 'Recepción cancelada correctamente.',
            'data' => [
                'rme_id' => $registro->rme_id,
                'rme_estado' => $registro->rme_estado,
            ],
        ]);
    }

    public function storeEntrada(StoreEntradaInventarioRequest $request): JsonResponse
    {
        $movimiento = $this->inventarioService->registrarEntrada($request, $request->validated());

        return response()->json([
            'message' => 'Entrada de inventario registrada correctamente.',
            'data' => ['min_id' => $movimiento->min_id, 'min_folio' => $movimiento->min_folio],
        ]);
    }

    public function storeSalida(StoreSalidaInventarioRequest $request): JsonResponse
    {
        $datos = $request->validated();

        if (!empty($datos['lineas'])) {
            $resultado = $this->inventarioService->registrarSalidaLote($request, $datos);

            return response()->json([
                'message' => 'Salida de inventario registrada correctamente.',
                'data' => [
                    'total_lineas' => count($resultado['movimientos']),
                    'min_ids' => collect($resultado['movimientos'])->pluck('min_id')->values()->all(),
                    'folios' => collect($resultado['movimientos'])->pluck('min_folio')->values()->all(),
                ],
            ]);
        }

        $movimiento = $this->inventarioService->registrarSalida($request, $datos);

        return response()->json([
            'message' => 'Salida de inventario registrada correctamente.',
            'data' => ['min_id' => $movimiento->min_id, 'min_folio' => $movimiento->min_folio],
        ]);
    }

    public function cancelar(CancelarMovimientoInventarioRequest $request, int $movimiento): JsonResponse
    {
        $result = $this->inventarioService->cancelarMovimiento($request, $movimiento, (string) $request->input('min_motivo_texto'));

        return response()->json([
            'message' => 'Movimiento cancelado con reversa correctamente.',
            'data' => [
                'original' => $result['original']->min_id,
                'reversa' => $result['reversa']->min_id,
            ],
        ]);
    }

    public function corregir(CorregirMovimientoInventarioRequest $request, int $movimiento): JsonResponse
    {
        $datos = $request->validated();
        $result = $this->inventarioService->corregirMovimiento(
            $request,
            $movimiento,
            (string) $datos['min_motivo_texto'],
            $datos['nuevo'],
        );

        return response()->json([
            'message' => 'Movimiento corregido correctamente con trazabilidad completa.',
            'data' => [
                'original' => $result['original']->min_id,
                'reversa' => $result['reversa']->min_id,
                'corregido' => $result['corregido']->min_id,
                'folio_corregido' => $result['corregido']->min_folio,
            ],
        ]);
    }

    public function storeMinimo(StoreMinimoInventarioRequest $request): JsonResponse
    {
        $registro = $this->inventarioService->guardarMinimo($request, $request->validated());

        return response()->json([
            'message' => 'Stock mínimo guardado correctamente.',
            'data' => ['mni_id' => $registro->mni_id],
        ]);
    }

    public function reporteEntradasSeleccionadasPdf(Request $request)
    {
        $datos = $request->validate([
            'folios' => ['required', 'array', 'min:1'],
            'folios.*' => ['required', 'string', 'max:80'],
            'atr_dominante_id' => ['nullable', 'integer'],
            'min_scl_id' => ['nullable', 'integer'],
            'min_alm_id' => ['nullable', 'integer'],
            'min_documento_tipo' => ['nullable', 'string', 'max:40'],
            'min_documento_referencia' => ['nullable', 'string', 'max:120'],
            'min_motivo_texto' => ['nullable', 'string', 'max:500'],
            'min_observaciones' => ['nullable', 'string', 'max:1500'],
            'min_fecha_movimiento' => ['nullable', 'date'],
            'min_fecha_emision' => ['nullable', 'date'],
            'min_prv_id' => ['nullable', 'integer'],
            'min_descuento_tipo' => ['nullable', 'string', 'max:20'],
            'min_descuento_valor' => ['nullable', 'numeric'],
            'min_flete_total' => ['nullable', 'numeric'],
            'min_iva_porcentaje' => ['nullable', 'numeric'],
        ]);

        $pdf = $this->inventarioService->generarReporteEntradasSeleccionadasPdf($request, $datos);

        return response($pdf['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $pdf['file_name'] . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function verReporteEntradasPdf(int $reporte)
    {
        $pdf = $this->inventarioService->descargarReporteEntradasPdfDesdeBitacora(request(), $reporte);

        return response($pdf['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdf['file_name'] . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function verReporteRecepcionMercanciaPdf(int $recepcion)
    {
        $pdf = $this->inventarioService->descargarReporteRecepcionMercanciaPdf(request(), $recepcion);

        return response($pdf['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdf['file_name'] . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
