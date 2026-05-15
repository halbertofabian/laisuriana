<?php

namespace App\Http\Controllers\Operacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operacion\StorePedidoPisoRequest;
use App\Services\Operacion\EscaneoProductoService;
use App\Services\Operacion\PedidoPisoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PedidoPisoController extends Controller
{
    public function __construct(
        private readonly PedidoPisoService $pedidoPisoService,
        private readonly EscaneoProductoService $escaneoProductoService,
    ) {
    }

    public function index()
    {
        return view('operacion.pedidos_piso.index', [
            'opciones' => $this->pedidoPisoService->opcionesBase(),
            'permisosUI' => [
                'crear' => auth()->user()?->tienePermiso('pedido_piso.crear') ?? false,
                'ver' => auth()->user()?->tienePermiso('pedido_piso.ver') ?? false,
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filtros = $request->only(['buscar', 'pdp_scl_id']);
        $filtros['pdp_estatus'] = 'pendiente_cobro';

        $rows = $this->pedidoPisoService->listar($filtros);

        $data = $rows->map(fn ($r) => [
            'pdp_id' => $r->pdp_id,
            'pdp_folio' => $r->pdp_folio,
            'pdp_estatus' => $r->pdp_estatus,
            'pdp_total' => (float) $r->pdp_total,
            'pdp_fecha' => optional($r->pdp_fecha)->format('Y-m-d H:i:s'),
            'sucursal' => $r->sucursal?->scl_nombre,
            'almacen' => $r->almacen?->alm_nombre,
            'vendedor' => $r->usuario?->usr_nombre,
        ])->values();

        return response()->json(['data' => $data]);
    }

    public function show(int $pedido): JsonResponse
    {
        $r = $this->pedidoPisoService->obtenerPorId($pedido);

        return response()->json([
            'data' => [
                'pdp_id' => $r->pdp_id,
                'pdp_folio' => $r->pdp_folio,
                'pdp_estatus' => $r->pdp_estatus,
                'pdp_total' => (float) $r->pdp_total,
                'pdp_observaciones' => $r->pdp_observaciones,
                'sucursal' => $r->sucursal?->scl_nombre,
                'almacen' => $r->almacen?->alm_nombre,
                'vendedor' => $r->usuario?->usr_nombre,
                'detalle' => $r->detalle->map(fn ($d) => [
                    'ppd_psk_id' => $d->ppd_psk_id,
                    'sku' => $d->sku?->psk_codigo,
                    'nombre' => $d->sku?->psk_nombre,
                    'cantidad' => (float) $d->ppd_cantidad,
                    'precio' => (float) $d->ppd_precio_unitario,
                    'importe' => (float) $d->ppd_importe,
                ])->values(),
            ],
        ]);
    }

    public function store(StorePedidoPisoRequest $request): JsonResponse
    {
        $pedido = $this->pedidoPisoService->crear($request, $request->validated());

        return response()->json([
            'message' => 'Pedido creado correctamente.',
            'data' => [
                'pdp_id' => $pedido->pdp_id,
                'pdp_folio' => $pedido->pdp_folio,
            ],
        ]);
    }

    public function buscarProductos(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'q' => ['required', 'string', 'max:120'],
        ]);

        return response()->json([
            'data' => $this->escaneoProductoService->sugerencias((string) $datos['q'], 15),
        ]);
    }

    public function buscarPorFolio(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'folio' => ['required', 'string', 'max:50'],
        ], [
            'folio.required' => 'Captura el folio del pedido.',
        ]);

        $pedido = $this->pedidoPisoService->obtenerPorFolio((string) $datos['folio']);
        if (!$pedido) {
            return response()->json([
                'message' => 'No encontramos el pedido solicitado.',
            ], 404);
        }

        if ($pedido->pdp_estatus !== 'pendiente_cobro') {
            return response()->json([
                'message' => 'El pedido no está disponible para carga en caja.',
                'data' => ['pdp_estatus' => $pedido->pdp_estatus],
            ], 422);
        }

        return response()->json([
            'message' => 'Pedido localizado correctamente.',
            'data' => [
                'pdp_id' => $pedido->pdp_id,
                'pdp_folio' => $pedido->pdp_folio,
                'pdp_estatus' => $pedido->pdp_estatus,
                'pdp_total' => (float) $pedido->pdp_total,
                'pdp_observaciones' => $pedido->pdp_observaciones,
                'pdp_alm_id' => $pedido->pdp_alm_id,
                'almacen' => $pedido->almacen?->alm_nombre,
                'sucursal' => $pedido->sucursal?->scl_nombre,
                'vendedor' => $pedido->usuario?->usr_nombre,
                'detalle' => $pedido->detalle->map(fn ($d) => [
                    'ppd_psk_id' => $d->ppd_psk_id,
                    'sku' => $d->sku?->psk_codigo,
                    'nombre' => $d->sku?->psk_nombre,
                    'cantidad' => (float) $d->ppd_cantidad,
                    'precio' => (float) $d->ppd_precio_unitario,
                    'importe' => (float) $d->ppd_importe,
                ])->values(),
            ],
        ]);
    }
}
