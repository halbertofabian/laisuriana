<?php

namespace App\Services\Operacion;

use App\Models\Almacen;
use App\Models\PedidoPiso;
use App\Models\PedidoPisoDetalle;
use App\Models\ProductoSku;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PedidoPisoService
{
    public function __construct(private readonly AuditoriaService $auditoriaService)
    {
    }

    public function listar(array $filtros = [])
    {
        return PedidoPiso::query()
            ->with([
                'sucursal:scl_id,scl_nombre',
                'almacen:alm_id,alm_nombre',
                'usuario:usr_id,usr_nombre,usr_usuario',
            ])
            ->when(!empty($filtros['buscar']), function ($q) use ($filtros): void {
                $buscar = trim((string) $filtros['buscar']);
                $q->where(function ($sub) use ($buscar): void {
                    $sub->where('pdp_folio', 'like', "%{$buscar}%")
                        ->orWhere('pdp_observaciones', 'like', "%{$buscar}%");
                });
            })
            ->when(!empty($filtros['pdp_estatus']), fn ($q) => $q->where('pdp_estatus', $filtros['pdp_estatus']))
            ->when(!empty($filtros['pdp_scl_id']), fn ($q) => $q->where('pdp_scl_id', (int) $filtros['pdp_scl_id']))
            ->orderByDesc('pdp_id')
            ->get();
    }

    public function opcionesBase(): array
    {
        return [
            'sucursales' => DB::table('tbl_sucursales_scl')
                ->where('scl_deleted', false)
                ->whereNull('scl_deleted_at')
                ->where('scl_estatus', 'activo')
                ->orderBy('scl_nombre')
                ->get(['scl_id', 'scl_nombre']),
            'almacenes' => Almacen::query()
                ->where('alm_estatus', 'activo')
                ->orderBy('alm_scl_id')
                ->orderBy('alm_nombre')
                ->get(['alm_id', 'alm_scl_id', 'alm_nombre']),
        ];
    }

    public function crear(Request $request, array $datos): PedidoPiso
    {
        return DB::transaction(function () use ($request, $datos): PedidoPiso {
            $skuIds = collect($datos['partidas'])->pluck('ppd_psk_id')->map(fn ($v) => (int) $v)->unique()->values();
            $skus = ProductoSku::query()->whereIn('psk_id', $skuIds)->get()->keyBy('psk_id');

            $subtotal = 0.0;
            $partidas = collect($datos['partidas'])->map(function ($item) use (&$subtotal, $skus) {
                $cantidad = (float) $item['ppd_cantidad'];
                $sku = $skus->get((int) $item['ppd_psk_id']);
                $precio = (float) ($sku?->psk_precio ?? 0);
                $importe = round($cantidad * $precio, 2);
                $subtotal += $importe;

                return [
                    'ppd_psk_id' => (int) $item['ppd_psk_id'],
                    'ppd_cantidad' => $cantidad,
                    'ppd_precio_unitario' => $precio,
                    'ppd_importe' => $importe,
                ];
            });

            $pedido = PedidoPiso::query()->create([
                'pdp_folio' => $this->crearFolio((int) $datos['pdp_alm_id']),
                'pdp_scl_id' => (int) $datos['pdp_scl_id'],
                'pdp_alm_id' => (int) $datos['pdp_alm_id'],
                'pdp_usr_id' => (int) optional($request->user())->usr_id,
                'pdp_estatus' => 'pendiente_cobro',
                'pdp_subtotal' => round($subtotal, 2),
                'pdp_total' => round($subtotal, 2),
                'pdp_observaciones' => $datos['pdp_observaciones'] ?? null,
                'pdp_fecha' => now(),
                'pdp_created_by_usr_id' => optional($request->user())->usr_id,
                'pdp_updated_by_usr_id' => optional($request->user())->usr_id,
            ]);

            foreach ($partidas as $partida) {
                PedidoPisoDetalle::query()->create([
                    'ppd_pdp_id' => $pedido->pdp_id,
                    'ppd_psk_id' => $partida['ppd_psk_id'],
                    'ppd_cantidad' => $partida['ppd_cantidad'],
                    'ppd_precio_unitario' => $partida['ppd_precio_unitario'],
                    'ppd_importe' => $partida['ppd_importe'],
                    'ppd_created_by_usr_id' => optional($request->user())->usr_id,
                    'ppd_updated_by_usr_id' => optional($request->user())->usr_id,
                ]);
            }

            $this->auditoriaService->registrarAccion(
                $request,
                'pedido_piso.crear',
                'tbl_pedidos_piso_pdp',
                (string) $pedido->pdp_id,
                [
                    'pdp_folio' => $pedido->pdp_folio,
                    'pdp_alm_id' => $pedido->pdp_alm_id,
                    'partidas' => $partidas->count(),
                    'total' => $pedido->pdp_total,
                ]
            );

            return $pedido;
        });
    }

    public function obtenerPorId(int $pedidoId): PedidoPiso
    {
        return PedidoPiso::query()
            ->with([
                'sucursal:scl_id,scl_nombre',
                'almacen:alm_id,alm_nombre',
                'usuario:usr_id,usr_nombre,usr_usuario',
                'detalle.sku:psk_id,psk_codigo,psk_codigo_barras,psk_nombre,psk_precio',
            ])
            ->findOrFail($pedidoId);
    }

    public function obtenerPorFolio(string $folio): ?PedidoPiso
    {
        $folio = trim($folio);
        if ($folio === '') {
            return null;
        }

        return PedidoPiso::query()
            ->with([
                'sucursal:scl_id,scl_nombre',
                'almacen:alm_id,alm_nombre',
                'usuario:usr_id,usr_nombre,usr_usuario',
                'detalle.sku:psk_id,psk_codigo,psk_codigo_barras,psk_nombre,psk_precio',
            ])
            ->where('pdp_folio', $folio)
            ->first();
    }

    private function crearFolio(int $almacenId): string
    {
        $prefix = 'PED-' . str_pad((string) $almacenId, 3, '0', STR_PAD_LEFT) . '-';
        $last = PedidoPiso::query()
            ->where('pdp_folio', 'like', $prefix . '%')
            ->orderByDesc('pdp_id')
            ->value('pdp_folio');

        $next = 1;
        if ($last && str_starts_with($last, $prefix)) {
            $num = (int) substr($last, strlen($prefix));
            $next = $num + 1;
        }

        return $prefix . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
