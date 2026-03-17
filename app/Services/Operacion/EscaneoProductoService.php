<?php

namespace App\Services\Operacion;

use App\Models\ProductoSku;

class EscaneoProductoService
{
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
}
