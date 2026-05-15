<?php

namespace App\Http\Controllers\Operacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operacion\Inventario\CancelarMovimientoInventarioRequest;
use App\Http\Requests\Operacion\Inventario\CorregirMovimientoInventarioRequest;
use App\Http\Requests\Operacion\Inventario\StoreEntradaInventarioRequest;
use App\Http\Requests\Operacion\Inventario\StoreInventarioInicialRequest;
use App\Http\Requests\Operacion\Inventario\StoreInventarioInicialMasivoRequest;
use App\Http\Requests\Operacion\Inventario\StoreMinimoInventarioRequest;
use App\Http\Requests\Operacion\Inventario\StoreSalidaInventarioRequest;
use App\Services\Operacion\InventarioBaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventarioBaseController extends Controller
{
    public function __construct(private readonly InventarioBaseService $inventarioService)
    {
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
        return $this->renderInventarioBase(false, 'existencias_negativas');
    }

    public function entradas()
    {
        return $this->renderInventarioBase(false, 'entradas');
    }

    public function recibir()
    {
        return $this->renderInventarioBase(false, 'recibir');
    }

    public function salidas()
    {
        return $this->renderInventarioBase(false, 'salidas');
    }

    public function kardex()
    {
        return $this->renderInventarioBase(false, 'kardex');
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
        $movimiento = $this->inventarioService->registrarSalida($request, $request->validated());

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
}
