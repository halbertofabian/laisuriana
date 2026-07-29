<?php

namespace App\Services\Operacion;

use App\Models\Almacen;
use App\Models\Producto;
use App\Models\ProductoSku;
use Illuminate\Support\Facades\DB;

class ProductoAlmacenResolverService
{
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
                'message' => $productoNombre . ' no pertenece al almacén seleccionado para este ticket.',
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
            'almacen_id' => (int) $almacen->alm_id,
            'almacen' => (string) $almacen->alm_nombre,
            'almacenes' => [[
                'alm_id' => (int) $almacen->alm_id,
                'alm_nombre' => (string) $almacen->alm_nombre,
            ]],
            'almacenes_configurados_total' => (int) $almacenesDisponibles->count(),
        ];
    }

    private function skuPermiteDecimales(ProductoSku $sku): bool
    {
        return strtoupper(trim((string) ($sku->producto?->unidad?->umd_codigo ?? ''))) === 'M';
    }
}
