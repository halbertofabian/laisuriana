<?php

namespace App\Services\Operacion;

use App\Models\ProductoSku;

class EscaneoProductoService
{
    public function sugerencias(string $consulta, int $limite = 12): array
    {
        $consulta = trim($consulta);
        $limite = max(1, min($limite, 30));

        if ($consulta === '') {
            return [];
        }

        $with = [
            'producto:prd_id,prd_codigo,prd_codigo_barras,prd_nombre,prd_descripcion,prd_precio_base,prd_costo,prd_mrc_id,prd_lna_id,prd_ctg_id',
            'producto.marca:mrc_id,mrc_nombre',
            'producto.linea:lna_id,lna_nombre',
            'producto.categoria:ctg_id,ctg_nombre',
        ];

        $query = ProductoSku::query()
            ->with($with)
            ->where('psk_estatus', 'activo')
            ->whereHas('producto', fn ($q) => $q->where('prd_estatus', 'activo'))
            ->where(function ($query) use ($consulta): void {
                $query->where('psk_codigo', 'like', "%{$consulta}%")
                    ->orWhere('psk_codigo_barras', 'like', "%{$consulta}%")
                    ->orWhere('psk_nombre', 'like', "%{$consulta}%")
                    ->orWhereHas('producto', function ($productoQuery) use ($consulta): void {
                        $productoQuery->where('prd_codigo', 'like', "%{$consulta}%")
                            ->orWhere('prd_codigo_barras', 'like', "%{$consulta}%")
                            ->orWhere('prd_nombre', 'like', "%{$consulta}%");
                    });
            })
            ->orderByRaw("
                CASE
                    WHEN psk_codigo = ? THEN 1
                    WHEN psk_codigo_barras = ? THEN 2
                    WHEN EXISTS (
                        SELECT 1 FROM tbl_productos_prd p
                        WHERE p.prd_id = tbl_producto_skus_psk.psk_prd_id
                          AND (p.prd_codigo = ? OR p.prd_codigo_barras = ?)
                    ) THEN 3
                    WHEN psk_nombre LIKE ? THEN 4
                    WHEN EXISTS (
                        SELECT 1 FROM tbl_productos_prd p2
                        WHERE p2.prd_id = tbl_producto_skus_psk.psk_prd_id
                          AND p2.prd_nombre LIKE ?
                    ) THEN 5
                    ELSE 9
                END
            ", [$consulta, $consulta, $consulta, $consulta, "{$consulta}%", "{$consulta}%"])
            ->orderBy('psk_id')
            ->limit($limite)
            ->get();

        return $query->map(fn ($sku) => $this->mapSku($sku))->values()->all();
    }

    public function buscar(string $consulta): ?array
    {
        $consulta = trim($consulta);

        if ($consulta === '') {
            return null;
        }

        $sku = $this->buscarSku($consulta);

        if (!$sku) {
            return null;
        }

        return $this->mapSku($sku);
    }

    private function buscarSku(string $consulta): ?ProductoSku
    {
        $with = [
            'producto:prd_id,prd_codigo,prd_codigo_barras,prd_nombre,prd_descripcion,prd_precio_base,prd_costo,prd_mrc_id,prd_lna_id,prd_ctg_id',
            'producto.marca:mrc_id,mrc_nombre',
            'producto.linea:lna_id,lna_nombre',
            'producto.categoria:ctg_id,ctg_nombre',
        ];

        $exacta = ProductoSku::query()
            ->with($with)
            ->where(function ($query) use ($consulta): void {
                $query->where('psk_codigo', $consulta)
                    ->orWhere('psk_codigo_barras', $consulta)
                    ->orWhereHas('producto', function ($productoQuery) use ($consulta): void {
                        $productoQuery->where('prd_codigo', $consulta)
                            ->orWhere('prd_codigo_barras', $consulta);
                    });
            })
            ->where('psk_estatus', 'activo')
            ->orderBy('psk_id')
            ->first();

        if ($exacta) {
            return $exacta;
        }

        return ProductoSku::query()
            ->with($with)
            ->where('psk_estatus', 'activo')
            ->where(function ($query) use ($consulta): void {
                $query->where('psk_codigo', 'like', "%{$consulta}%")
                    ->orWhere('psk_codigo_barras', 'like', "%{$consulta}%")
                    ->orWhere('psk_nombre', 'like', "%{$consulta}%")
                    ->orWhereHas('producto', function ($productoQuery) use ($consulta): void {
                        $productoQuery->where('prd_codigo', 'like', "%{$consulta}%")
                            ->orWhere('prd_codigo_barras', 'like', "%{$consulta}%")
                            ->orWhere('prd_nombre', 'like', "%{$consulta}%");
                    });
            })
            ->orderBy('psk_id')
            ->first();
    }

    private function mapSku(ProductoSku $sku): array
    {
        return [
            'psk_id' => $sku->psk_id,
            'psk_codigo' => $sku->psk_codigo,
            'psk_codigo_barras' => $sku->psk_codigo_barras,
            'psk_nombre' => $sku->psk_nombre,
            'psk_precio' => (float) $sku->psk_precio,
            'psk_estatus' => $sku->psk_estatus,
            'producto' => [
                'prd_id' => $sku->producto?->prd_id,
                'prd_codigo' => $sku->producto?->prd_codigo,
                'prd_codigo_barras' => $sku->producto?->prd_codigo_barras,
                'prd_nombre' => $sku->producto?->prd_nombre,
                'prd_descripcion' => $sku->producto?->prd_descripcion,
                'prd_precio_base' => (float) ($sku->producto?->prd_precio_base ?? 0),
                'prd_costo' => (float) ($sku->producto?->prd_costo ?? 0),
                'marca' => $sku->producto?->marca?->mrc_nombre,
                'linea' => $sku->producto?->linea?->lna_nombre,
                'categoria' => $sku->producto?->categoria?->ctg_nombre,
            ],
        ];
    }
}
