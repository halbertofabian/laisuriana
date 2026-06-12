<?php

namespace App\Services\Operacion;

use App\Models\Almacen;
use App\Models\Producto;
use App\Models\PedidoPiso;
use App\Models\PedidoPisoDetalle;
use App\Models\ProductoSku;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        return $this->guardarPedido($request, $datos);
    }

    public function actualizar(Request $request, int $pedidoId, array $datos): PedidoPiso
    {
        return $this->guardarPedido($request, $datos, $pedidoId);
    }

    public function eliminar(Request $request, int $pedidoId): void
    {
        DB::transaction(function () use ($request, $pedidoId): void {
            $pedido = PedidoPiso::query()->findOrFail($pedidoId);

            if ((string) $pedido->pdp_estatus !== 'pendiente_cobro') {
                throw ValidationException::withMessages([
                    'pedido' => ['Solo se pueden eliminar pedidos pendientes de cobro.'],
                ]);
            }

            DB::table('tbl_pedido_piso_detalle_ppd')
                ->where('ppd_pdp_id', $pedido->pdp_id)
                ->where('ppd_deleted', false)
                ->whereNull('ppd_deleted_at')
                ->update([
                    'ppd_deleted' => true,
                    'ppd_deleted_at' => now(),
                    'ppd_updated_at' => now(),
                    'ppd_updated_by_usr_id' => optional($request->user())->usr_id,
                ]);

            $pedido->forceFill([
                'pdp_estatus' => 'cancelado',
                'pdp_updated_by_usr_id' => optional($request->user())->usr_id,
            ])->save();

            $pedido->marcarComoEliminado();

            $this->auditoriaService->registrarAccion(
                $request,
                'pedido_piso.eliminar',
                'tbl_pedidos_piso_pdp',
                (string) $pedido->pdp_id,
                [
                    'pdp_folio' => $pedido->pdp_folio,
                    'pdp_estatus' => $pedido->pdp_estatus,
                ]
            );
        });
    }

    public function obtenerPorId(int $pedidoId): PedidoPiso
    {
        return PedidoPiso::query()
            ->with([
                'sucursal:scl_id,scl_nombre',
                'almacen:alm_id,alm_nombre',
                'usuario:usr_id,usr_nombre,usr_usuario',
                'detalle.sku:psk_id,psk_prd_id,psk_codigo,psk_codigo_barras,psk_nombre,psk_precio',
                'detalle.capturista:usr_id,usr_nombre,usr_usuario',
                'detalle.sku.producto:prd_id,prd_umd_id,prd_nombre',
                'detalle.sku.producto.unidad:umd_id,umd_codigo,umd_nombre',
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

    public function validarSkuParaAlmacen(int $skuId, int $sucursalId, int $almacenId): array
    {
        $almacen = Almacen::query()
            ->where('alm_id', $almacenId)
            ->where('alm_scl_id', $sucursalId)
            ->where('alm_estatus', 'activo')
            ->first();

        if (!$almacen) {
            return [
                'valido' => false,
                'message' => 'El almacén seleccionado no pertenece a la sucursal indicada.',
            ];
        }

        $productoId = (int) (ProductoSku::query()->where('psk_id', $skuId)->value('psk_prd_id') ?? 0);
        if ($productoId <= 0) {
            return [
                'valido' => false,
                'message' => 'No fue posible identificar el producto base del SKU seleccionado.',
            ];
        }

        $configurados = DB::table('tbl_producto_almacenes_pra')
            ->where('pra_prd_id', $productoId)
            ->where('pra_deleted', false)
            ->whereNull('pra_deleted_at')
            ->pluck('pra_alm_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($configurados->isNotEmpty() && !$configurados->contains($almacenId)) {
            $productoNombre = (string) (Producto::query()->where('prd_id', $productoId)->value('prd_nombre') ?? 'Este producto');

            return [
                'valido' => false,
                'message' => $productoNombre . ' no pertenece al almacén seleccionado.',
            ];
        }

        return [
            'valido' => true,
            'message' => 'Producto permitido para este almacén.',
        ];
    }

    public function resolverSkuAlmacen(int $skuId, int $sucursalId): array
    {
        $sku = ProductoSku::query()
            ->with('producto.unidad:umd_id,umd_nombre,umd_codigo')
            ->find($skuId);

        if (!$sku || !$sku->producto) {
            return [
                'valido' => false,
                'message' => 'No fue posible identificar el producto base del SKU seleccionado.',
            ];
        }

        $configurados = DB::table('tbl_producto_almacenes_pra as pra')
            ->join('tbl_almacenes_alm as alm', 'alm.alm_id', '=', 'pra.pra_alm_id')
            ->where('pra.pra_prd_id', (int) $sku->producto->prd_id)
            ->where('pra.pra_deleted', false)
            ->whereNull('pra.pra_deleted_at')
            ->where('alm.alm_estatus', 'activo')
            ->where('alm.alm_scl_id', $sucursalId)
            ->orderBy('pra.pra_id')
            ->orderBy('alm.alm_nombre')
            ->get(['alm.alm_id', 'alm.alm_nombre']);

        $almacenesDisponibles = $configurados->isNotEmpty()
            ? $configurados
            : Almacen::query()
                ->where('alm_scl_id', $sucursalId)
                ->where('alm_estatus', 'activo')
                ->orderBy('alm_nombre')
                ->get(['alm_id', 'alm_nombre']);

        if ($almacenesDisponibles->isEmpty()) {
            return [
                'valido' => false,
                'message' => 'No hay un almacén disponible para este producto en la sucursal seleccionada.',
            ];
        }

        if ($almacenesDisponibles->count() > 1) {
            return [
                'valido' => true,
                'requiere_seleccion' => true,
                'message' => 'Selecciona el almacén desde el que tomarás este producto.',
                'prd_id' => (int) $sku->producto->prd_id,
                'prd_nombre' => (string) $sku->producto->prd_nombre,
                'permite_decimal' => $this->skuPermiteDecimales($sku),
                'almacenes' => $almacenesDisponibles->map(fn ($almacen) => [
                    'alm_id' => (int) $almacen->alm_id,
                    'alm_nombre' => (string) $almacen->alm_nombre,
                ])->values()->all(),
                'almacenes_configurados_total' => (int) $almacenesDisponibles->count(),
            ];
        }

        $almacen = $almacenesDisponibles->first();

        return [
            'valido' => true,
            'requiere_seleccion' => false,
            'message' => 'Producto asignado automáticamente al almacén correspondiente.',
            'prd_id' => (int) $sku->producto->prd_id,
            'prd_nombre' => (string) $sku->producto->prd_nombre,
            'permite_decimal' => $this->skuPermiteDecimales($sku),
            'pdp_alm_id' => (int) $almacen->alm_id,
            'almacen' => (string) $almacen->alm_nombre,
            'almacenes' => [[
                'alm_id' => (int) $almacen->alm_id,
                'alm_nombre' => (string) $almacen->alm_nombre,
            ]],
            'almacenes_configurados_total' => (int) $almacenesDisponibles->count(),
        ];
    }

    private function guardarPedido(Request $request, array $datos, ?int $pedidoId = null): PedidoPiso
    {
        return DB::transaction(function () use ($request, $datos, $pedidoId): PedidoPiso {
            $partidasInput = collect($datos['partidas']);
            $skuIds = $partidasInput->pluck('ppd_psk_id')->map(fn ($v) => (int) $v)->unique()->values();
            $skus = ProductoSku::query()
                ->with('producto.unidad:umd_id,umd_nombre,umd_codigo')
                ->whereIn('psk_id', $skuIds)
                ->get()
                ->keyBy('psk_id');
            $almacenId = (int) $datos['pdp_alm_id'];
            $sucursalId = (int) $datos['pdp_scl_id'];
            $pedido = null;

            if ($pedidoId !== null) {
                $pedido = PedidoPiso::query()->lockForUpdate()->findOrFail($pedidoId);

                if ((string) $pedido->pdp_estatus !== 'pendiente_cobro') {
                    throw ValidationException::withMessages([
                        'pedido' => ['Solo se pueden editar pedidos pendientes de cobro.'],
                    ]);
                }

                if ((int) $pedido->pdp_scl_id !== $sucursalId || (int) $pedido->pdp_alm_id !== $almacenId) {
                    throw ValidationException::withMessages([
                        'pedido' => ['El pedido solo puede actualizarse dentro de su misma sucursal y almacén.'],
                    ]);
                }
            }

            foreach ($partidasInput as $item) {
                $skuId = (int) $item['ppd_psk_id'];
                $cantidad = (float) $item['ppd_cantidad'];
                $validacion = $this->validarSkuParaAlmacen($skuId, $sucursalId, $almacenId);
                if (!$validacion['valido']) {
                    throw ValidationException::withMessages([
                        'partidas' => [$validacion['message']],
                    ]);
                }

                $sku = $skus->get($skuId);
                $validacionCantidad = $this->validarCantidadParaSku($sku, $cantidad);
                if ($validacionCantidad !== null) {
                    throw ValidationException::withMessages([
                        'partidas' => [$validacionCantidad],
                    ]);
                }
            }

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
                    'ppd_usr_id' => (int) ($item['ppd_usr_id'] ?? optional($request->user())->usr_id ?? 0),
                ];
            });

            if ($pedido) {
                $pedido->update([
                    'pdp_subtotal' => round($subtotal, 2),
                    'pdp_total' => round($subtotal, 2),
                    'pdp_observaciones' => $datos['pdp_observaciones'] ?? null,
                    'pdp_updated_by_usr_id' => optional($request->user())->usr_id,
                ]);

                DB::table('tbl_pedido_piso_detalle_ppd')
                    ->where('ppd_pdp_id', $pedido->pdp_id)
                    ->where('ppd_deleted', false)
                    ->update([
                        'ppd_deleted' => true,
                        'ppd_deleted_at' => now(),
                        'ppd_updated_at' => now(),
                        'ppd_updated_by_usr_id' => optional($request->user())->usr_id,
                    ]);
            } else {
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
            }

            foreach ($partidas as $partida) {
                PedidoPisoDetalle::query()->create([
                    'ppd_pdp_id' => $pedido->pdp_id,
                    'ppd_psk_id' => $partida['ppd_psk_id'],
                    'ppd_cantidad' => $partida['ppd_cantidad'],
                    'ppd_precio_unitario' => $partida['ppd_precio_unitario'],
                    'ppd_importe' => $partida['ppd_importe'],
                    'ppd_usr_id' => $partida['ppd_usr_id'] > 0 ? $partida['ppd_usr_id'] : optional($request->user())->usr_id,
                    'ppd_created_by_usr_id' => optional($request->user())->usr_id,
                    'ppd_updated_by_usr_id' => optional($request->user())->usr_id,
                ]);
            }

            $this->auditoriaService->registrarAccion(
                $request,
                $pedidoId ? 'pedido_piso.actualizar' : 'pedido_piso.crear',
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

    private function skuPermiteDecimales(?ProductoSku $sku): bool
    {
        $codigoUnidad = strtoupper(trim((string) ($sku?->producto?->unidad?->umd_codigo ?? '')));
        return $codigoUnidad === 'M';
    }

    private function validarCantidadParaSku(?ProductoSku $sku, float $cantidad): ?string
    {
        if ($cantidad <= 0) {
            return 'La cantidad capturada debe ser mayor a cero.';
        }

        if ($this->skuPermiteDecimales($sku)) {
            return null;
        }

        if (abs($cantidad - round($cantidad)) > 0.000001) {
            $productoNombre = (string) ($sku?->producto?->prd_nombre ?? $sku?->psk_nombre ?? 'Este producto');
            return $productoNombre . ' solo permite cantidades enteras.';
        }

        return null;
    }

    private function crearFolio(int $almacenId): string
    {
        $prefix = 'PED-' . str_pad((string) $almacenId, 3, '0', STR_PAD_LEFT) . '-';
        $last = PedidoPiso::query()
            ->withDeleted()
            ->where('pdp_folio', 'like', $prefix . '%')
            ->orderByDesc('pdp_id')
            ->value('pdp_folio');

        $next = 1;
        if ($last && str_starts_with($last, $prefix)) {
            $num = (int) substr($last, strlen($prefix));
            $next = $num + 1;
        }

        do {
            $folio = $prefix . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $exists = PedidoPiso::query()
                ->withDeleted()
                ->where('pdp_folio', $folio)
                ->exists();
            $next++;
        } while ($exists);

        return $folio;
    }
}
