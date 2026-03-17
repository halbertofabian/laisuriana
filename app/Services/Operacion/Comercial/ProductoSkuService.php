<?php

namespace App\Services\Operacion\Comercial;

use App\Models\ProductoAtributo;
use App\Models\Producto;
use App\Models\ProductoSku;
use App\Models\SkuValorAtributo;
use App\Models\ValorAtributo;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductoSkuService
{
    public function __construct(private readonly AuditoriaService $auditoriaService)
    {
    }

    public function listar(array $filtros = [])
    {
        return ProductoSku::query()
            ->with([
                'producto:prd_id,prd_codigo,prd_nombre',
                'valoresAtributo:vat_id,vat_atr_id,vat_valor',
                'valoresAtributo.atributo:atr_id,atr_nombre',
            ])
            ->when(!empty($filtros['psk_prd_id']), fn ($q) => $q->where('psk_prd_id', (int) $filtros['psk_prd_id']))
            ->when(!empty($filtros['buscar']), function ($query) use ($filtros): void {
                $buscar = trim((string) $filtros['buscar']);
                $query->where(function ($sub) use ($buscar): void {
                    $sub->where('psk_codigo', 'like', "%{$buscar}%")
                        ->orWhere('psk_codigo_barras', 'like', "%{$buscar}%")
                        ->orWhere('psk_nombre', 'like', "%{$buscar}%");
                });
            })
            ->when(!empty($filtros['estatus']), fn ($q) => $q->where('psk_estatus', $filtros['estatus']))
            ->orderBy('psk_codigo')
            ->get();
    }

    public function obtenerPorId(int $id): ProductoSku
    {
        return ProductoSku::query()->with('valoresAtributo:vat_id,vat_atr_id,vat_valor')->findOrFail($id);
    }

    public function obtenerParaEtiqueta(int $id): ProductoSku
    {
        return ProductoSku::query()
            ->with('producto:prd_id,prd_codigo,prd_nombre,prd_tipo')
            ->findOrFail($id);
    }

    public function crear(Request $request, array $datos): ProductoSku
    {
        return DB::transaction(function () use ($request, $datos): ProductoSku {
            $producto = Producto::query()->findOrFail((int) $datos['psk_prd_id']);
            $valorIds = $this->normalizarValorIds($datos['valor_atributo_ids']);
            $this->validarConsistenciaValores((int) $datos['psk_prd_id'], $valorIds);
            $this->validarSkuCodigoUnico((int) $datos['psk_prd_id'], $datos['psk_codigo']);
            $this->validarCombinacionUnica((int) $datos['psk_prd_id'], $valorIds);

            $sku = ProductoSku::query()->create([
                'psk_prd_id' => $datos['psk_prd_id'],
                'psk_codigo' => $datos['psk_codigo'],
                'psk_codigo_barras' => ($datos['psk_codigo_barras'] ?? null) ?: $datos['psk_codigo'],
                'psk_nombre' => $datos['psk_nombre'] ?? null,
                'psk_precio' => $producto->prd_precio_base,
                'psk_stock_minimo' => $producto->prd_stock_minimo,
                'psk_stock_maximo' => $producto->prd_stock_maximo,
                'psk_estatus' => $datos['psk_estatus'],
                'psk_created_by_usr_id' => optional($request->user())->usr_id,
                'psk_updated_by_usr_id' => optional($request->user())->usr_id,
            ]);

            $this->sincronizarValores($request, $sku->psk_id, $valorIds);

            $this->auditoriaService->registrarAccion(
                $request,
                'catalogo_comercial.sku.crear',
                'tbl_producto_skus_psk',
                (string) $sku->psk_id,
                ['psk_codigo' => $sku->psk_codigo]
            );

            return $sku;
        });
    }

    public function actualizar(Request $request, int $id, array $datos): ProductoSku
    {
        return DB::transaction(function () use ($request, $id, $datos): ProductoSku {
            $sku = ProductoSku::query()->findOrFail($id);
            $valorIds = $this->normalizarValorIds($datos['valor_atributo_ids']);

            if ($datos['psk_estatus'] === 'inactivo') {
                $this->validarInactivacionSku($id);
            }

            $this->validarConsistenciaValores((int) $datos['psk_prd_id'], $valorIds);
            $this->validarSkuCodigoUnico((int) $datos['psk_prd_id'], $datos['psk_codigo'], $id);
            $this->validarCombinacionUnica((int) $datos['psk_prd_id'], $valorIds, $id);

            $sku->update([
                'psk_prd_id' => $datos['psk_prd_id'],
                'psk_codigo' => $datos['psk_codigo'],
                'psk_codigo_barras' => ($datos['psk_codigo_barras'] ?? null) ?: $datos['psk_codigo'],
                'psk_nombre' => $datos['psk_nombre'] ?? null,
                'psk_estatus' => $datos['psk_estatus'],
                'psk_updated_by_usr_id' => optional($request->user())->usr_id,
            ]);

            $this->sincronizarValores($request, $sku->psk_id, $valorIds);

            $this->auditoriaService->registrarAccion(
                $request,
                'catalogo_comercial.sku.editar',
                'tbl_producto_skus_psk',
                (string) $sku->psk_id,
                ['psk_estatus' => $sku->psk_estatus]
            );

            return $sku;
        });
    }

    public function cambiarEstatus(Request $request, int $id, string $estatus): ProductoSku
    {
        $sku = ProductoSku::query()->findOrFail($id);

        if ($estatus === 'inactivo') {
            $this->validarInactivacionSku($id);
        }

        $sku->update([
            'psk_estatus' => $estatus,
            'psk_updated_by_usr_id' => optional($request->user())->usr_id,
        ]);

        $this->auditoriaService->registrarAccion(
            $request,
            $estatus === 'activo' ? 'catalogo_comercial.sku.activar' : 'catalogo_comercial.sku.inactivar',
            'tbl_producto_skus_psk',
            (string) $sku->psk_id,
            ['psk_estatus' => $estatus]
        );

        return $sku;
    }

    public function eliminar(Request $request, int $id): void
    {
        DB::transaction(function () use ($request, $id): void {
            $sku = ProductoSku::query()->findOrFail($id);
            $this->validarInactivacionSku($id);

            SkuValorAtributo::query()
                ->where('sva_psk_id', $id)
                ->where('sva_deleted', false)
                ->whereNull('sva_deleted_at')
                ->update([
                    'sva_deleted' => true,
                    'sva_deleted_at' => now(),
                    'sva_estatus' => 'inactivo',
                    'sva_updated_by_usr_id' => optional($request->user())->usr_id,
                    'sva_updated_at' => now(),
                ]);

            $sku->forceFill([
                'psk_estatus' => 'inactivo',
                'psk_updated_by_usr_id' => optional($request->user())->usr_id,
            ])->save();

            $sku->marcarComoEliminado();

            $this->auditoriaService->registrarAccion(
                $request,
                'catalogo_comercial.sku.eliminar',
                'tbl_producto_skus_psk',
                (string) $sku->psk_id,
                ['psk_codigo' => $sku->psk_codigo]
            );
        });
    }

    private function validarInactivacionSku(int $skuId): void
    {
        // Reservado para futuras validaciones cuando ventas/inventario usen SKU.
    }

    private function sincronizarValores(Request $request, int $skuId, array $valorIds): void
    {
        SkuValorAtributo::query()
            ->where('sva_psk_id', $skuId)
            ->where('sva_deleted', false)
            ->whereNull('sva_deleted_at')
            ->whereNotIn('sva_vat_id', $valorIds)
            ->update([
                'sva_deleted' => true,
                'sva_deleted_at' => now(),
                'sva_estatus' => 'inactivo',
                'sva_updated_by_usr_id' => optional($request->user())->usr_id,
                'sva_updated_at' => now(),
            ]);

        foreach ($valorIds as $valorId) {
            $registro = SkuValorAtributo::query()
                ->withDeleted()
                ->where('sva_psk_id', $skuId)
                ->where('sva_vat_id', $valorId)
                ->first();

            $datos = [
                'sva_estatus' => 'activo',
                'sva_deleted' => false,
                'sva_deleted_at' => null,
                'sva_updated_by_usr_id' => optional($request->user())->usr_id,
            ];

            if ($registro) {
                $registro->update($datos);
                continue;
            }

            SkuValorAtributo::query()->create(array_merge($datos, [
                'sva_psk_id' => $skuId,
                'sva_vat_id' => $valorId,
                'sva_created_by_usr_id' => optional($request->user())->usr_id,
            ]));
        }
    }

    private function validarConsistenciaValores(int $productoId, array $valorIds): void
    {
        $valores = ValorAtributo::query()
            ->whereIn('vat_id', $valorIds)
            ->where('vat_deleted', false)
            ->whereNull('vat_deleted_at')
            ->where('vat_estatus', 'activo')
            ->get(['vat_id', 'vat_atr_id']);

        if ($valores->count() !== count($valorIds)) {
            throw ValidationException::withMessages([
                'valor_atributo_ids' => 'Uno o más valores de atributo no existen o están inactivos.',
            ]);
        }

        $atributoIds = $valores->pluck('vat_atr_id')->map(fn ($id) => (int) $id)->values();
        if ($atributoIds->unique()->count() !== $atributoIds->count()) {
            throw ValidationException::withMessages([
                'valor_atributo_ids' => 'No puedes repetir el mismo atributo en la combinación del SKU.',
            ]);
        }

        $permitidos = ProductoAtributo::query()
            ->where('pat_prd_id', $productoId)
            ->where('pat_deleted', false)
            ->whereNull('pat_deleted_at')
            ->where('pat_estatus', 'activo')
            ->pluck('pat_atr_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $noPermitidos = $atributoIds->diff($permitidos);
        if ($noPermitidos->isNotEmpty()) {
            throw ValidationException::withMessages([
                'valor_atributo_ids' => 'La combinación incluye atributos no habilitados para el producto seleccionado.',
            ]);
        }
    }

    private function validarSkuCodigoUnico(int $productoId, string $codigo, ?int $idIgnorar = null): void
    {
        $existe = ProductoSku::query()
            ->withDeleted()
            ->where('psk_prd_id', $productoId)
            ->where('psk_codigo', $codigo)
            ->where('psk_deleted', false)
            ->when($idIgnorar, fn ($q) => $q->where('psk_id', '!=', $idIgnorar))
            ->exists();

        if ($existe) {
            throw ValidationException::withMessages([
                'psk_codigo' => 'Ya existe ese código SKU para el producto seleccionado.',
            ]);
        }
    }

    private function validarCombinacionUnica(int $productoId, array $valorIds, ?int $skuIgnorar = null): void
    {
        $firmaObjetivo = $this->firmaCombinacion($valorIds);

        $skus = ProductoSku::query()
            ->where('psk_prd_id', $productoId)
            ->where('psk_deleted', false)
            ->whereNull('psk_deleted_at')
            ->when($skuIgnorar, fn ($q) => $q->where('psk_id', '!=', $skuIgnorar))
            ->get(['psk_id']);

        foreach ($skus as $sku) {
            $actualIds = SkuValorAtributo::query()
                ->where('sva_psk_id', $sku->psk_id)
                ->where('sva_deleted', false)
                ->whereNull('sva_deleted_at')
                ->pluck('sva_vat_id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            if ($this->firmaCombinacion($actualIds) === $firmaObjetivo) {
                throw ValidationException::withMessages([
                    'valor_atributo_ids' => 'Ya existe un SKU con la misma combinación de valores de atributos.',
                ]);
            }
        }
    }

    private function normalizarValorIds(array $valorIds): array
    {
        return array_values(array_unique(array_map('intval', $valorIds)));
    }

    private function firmaCombinacion(array $valorIds): string
    {
        sort($valorIds);

        return implode('-', $valorIds);
    }
}
