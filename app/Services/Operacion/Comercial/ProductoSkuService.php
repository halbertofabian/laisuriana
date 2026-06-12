<?php

namespace App\Services\Operacion\Comercial;

use App\Models\ProductoAtributo;
use App\Models\Producto;
use App\Models\ProductoSku;
use App\Models\SkuValorAtributo;
use App\Models\ValorAtributo;
use App\Services\AuditoriaService;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductoSkuService
{
    public function __construct(private readonly AuditoriaService $auditoriaService)
    {
    }

    public function listar(array $filtros = [])
    {
        $query = ProductoSku::query()
            ->with([
                'producto:prd_id,prd_codigo,prd_nombre',
                'valoresAtributo:vat_id,vat_atr_id,vat_valor,vat_clave',
                'valoresAtributo.atributo:atr_id,atr_nombre,atr_clave',
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
            ->orderBy('psk_codigo');

        foreach ($this->normalizarFiltrosAtributo($filtros['atributo_filtros'] ?? []) as $valorId) {
            $query->whereHas('valoresAtributo', fn ($sub) => $sub->where('tbl_valores_atributo_vat.vat_id', $valorId));
        }

        $skus = $query->get();

        if (!empty($filtros['psk_prd_id']) && $this->coleccionTieneAtributoTalla($skus)) {
            return $this->ordenarSkusPorTalla($skus);
        }

        return $skus;
    }

    public function listarAgrupado(array $filtros = []): array
    {
        $query = ProductoSku::query()
            ->with([
                'producto:prd_id,prd_codigo,prd_nombre,prd_mrc_id,prd_mdl_id,prd_lna_id,prd_ctg_id',
                'producto.marca:mrc_id,mrc_nombre',
                'producto.modelo:mdl_id,mdl_nombre',
                'producto.linea:lna_id,lna_nombre',
                'producto.categoria:ctg_id,ctg_nombre',
                'valoresAtributo:vat_id,vat_atr_id,vat_valor,vat_clave',
                'valoresAtributo.atributo:atr_id,atr_nombre,atr_clave',
            ])
            ->orderBy('psk_codigo');

        if (!empty($filtros['psk_prd_id'])) {
            $query->where('psk_prd_id', (int) $filtros['psk_prd_id']);
        }

        if (!empty($filtros['prd_mrc_id'])) {
            $query->whereHas('producto', fn ($q) => $q->where('prd_mrc_id', (int) $filtros['prd_mrc_id']));
        }

        if (!empty($filtros['prd_mdl_id'])) {
            $query->whereHas('producto', fn ($q) => $q->where('prd_mdl_id', (int) $filtros['prd_mdl_id']));
        }

        if (!empty($filtros['prd_lna_id'])) {
            $query->whereHas('producto', fn ($q) => $q->where('prd_lna_id', (int) $filtros['prd_lna_id']));
        }

        if (!empty($filtros['prd_ctg_id'])) {
            $query->whereHas('producto', fn ($q) => $q->where('prd_ctg_id', (int) $filtros['prd_ctg_id']));
        }

        if (!empty($filtros['buscar'])) {
            $buscar = trim((string) $filtros['buscar']);
            $query->where(function ($sub) use ($buscar): void {
                $sub->where('psk_codigo', 'like', "%{$buscar}%")
                    ->orWhere('psk_nombre', 'like', "%{$buscar}%")
                    ->orWhereHas('producto', function ($q) use ($buscar): void {
                        $q->where('prd_nombre', 'like', "%{$buscar}%")
                          ->orWhere('prd_codigo', 'like', "%{$buscar}%");
                    });
            });
        }

        // Filtros de atributos: [{atr_id => vat_id}, ...]
        if (!empty($filtros['atributo_filtros']) && is_array($filtros['atributo_filtros'])) {
            foreach ($filtros['atributo_filtros'] as $vatId) {
                $vatId = (int) $vatId;
                if ($vatId > 0) {
                    $query->whereHas('valoresAtributo', fn ($q) => $q->where('vat_id', $vatId));
                }
            }
        }

        $skus = $query->get();

        $grupos = [];

        foreach ($skus as $sku) {
            $producto = $sku->producto;
            if (!$producto) {
                continue;
            }

            $colorVal = $sku->valoresAtributo->first(
                fn ($v) => $this->esAtributoColor($v->atributo?->atr_nombre ?? '', $v->atributo?->atr_clave ?? '')
            );

            $colorId = $colorVal ? (int) $colorVal->vat_id : 0;
            $colorNombre = $colorVal ? (string) $colorVal->vat_valor : 'Sin color';
            $rowKey = $producto->prd_id . ':' . $colorId;

            if (!isset($grupos[$rowKey])) {
                $grupos[$rowKey] = [
                    'prd_id' => $producto->prd_id,
                    'producto_codigo' => (string) $producto->prd_codigo,
                    'producto_nombre' => (string) $producto->prd_nombre,
                    'marca_nombre' => (string) ($producto->marca?->mrc_nombre ?? ''),
                    'modelo_nombre' => (string) ($producto->modelo?->mdl_nombre ?? ''),
                    'linea_nombre' => (string) ($producto->linea?->lna_nombre ?? ''),
                    'concepto_nombre' => (string) ($producto->categoria?->ctg_nombre ?? ''),
                    'color_nombre' => $colorNombre,
                    'color_vat_id' => $colorId > 0 ? $colorId : null,
                    'variantes' => [],
                    '_sort_marca' => strtolower($producto->marca?->mrc_nombre ?? ''),
                    '_sort_linea' => strtolower($producto->linea?->lna_nombre ?? ''),
                    '_sort_concepto' => strtolower($producto->categoria?->ctg_nombre ?? ''),
                    '_sort_producto' => strtolower($producto->prd_nombre ?? ''),
                    '_sort_color' => strtolower($colorNombre),
                ];
            }

            $labelPartes = $sku->valoresAtributo
                ->filter(fn ($v) => !$this->esAtributoColor($v->atributo?->atr_nombre ?? '', $v->atributo?->atr_clave ?? ''))
                ->sortBy(fn ($v) => $this->esAtributoTalla($v->atributo?->atr_nombre ?? '', $v->atributo?->atr_clave ?? '') ? 0 : 1)
                ->map(fn ($v) => (string) $v->vat_valor)
                ->filter()
                ->values();

            $grupos[$rowKey]['variantes'][] = [
                'psk_id' => $sku->psk_id,
                'psk_codigo' => (string) $sku->psk_codigo,
                'psk_estatus' => (string) $sku->psk_estatus,
                'label' => $labelPartes->isNotEmpty() ? $labelPartes->implode(' / ') : (string) $sku->psk_codigo,
                '_sort_label' => $labelPartes->first() ?? $sku->psk_codigo,
            ];
        }

        usort($grupos, function (array $a, array $b): int {
            return $a['_sort_marca'] <=> $b['_sort_marca']
                ?: $a['_sort_linea'] <=> $b['_sort_linea']
                ?: $a['_sort_concepto'] <=> $b['_sort_concepto']
                ?: $a['_sort_producto'] <=> $b['_sort_producto']
                ?: $a['_sort_color'] <=> $b['_sort_color'];
        });

        foreach ($grupos as &$grupo) {
            usort($grupo['variantes'], function (array $a, array $b): int {
                return $this->compararValoresTalla($a['_sort_label'], null, $b['_sort_label'], null);
            });

            $grupo['variantes'] = array_map(function (array $v): array {
                unset($v['_sort_label']);
                return $v;
            }, $grupo['variantes']);

            unset($grupo['_sort_marca'], $grupo['_sort_linea'], $grupo['_sort_concepto'], $grupo['_sort_producto'], $grupo['_sort_color']);
        }

        return array_values($grupos);
    }

    public function obtenerFiltrosProducto(int $productoId): array
    {
        $skus = ProductoSku::query()
            ->with([
                'valoresAtributo:vat_id,vat_atr_id,vat_valor,vat_clave',
                'valoresAtributo.atributo:atr_id,atr_nombre,atr_clave',
            ])
            ->where('psk_prd_id', $productoId)
            ->orderBy('psk_codigo')
            ->get();

        $atributos = [];

        foreach ($skus as $sku) {
            foreach ($sku->valoresAtributo as $valor) {
                $atributo = $valor->atributo;

                if (!$atributo) {
                    continue;
                }

                $atributoId = (int) $atributo->atr_id;
                if (!isset($atributos[$atributoId])) {
                    $atributos[$atributoId] = [
                        'atr_id' => $atributoId,
                        'atr_nombre' => $atributo->atr_nombre,
                        'atr_clave' => $atributo->atr_clave,
                        'es_talla' => $this->esAtributoTalla($atributo->atr_nombre, $atributo->atr_clave),
                        'valores' => [],
                    ];
                }

                $atributos[$atributoId]['valores'][(int) $valor->vat_id] = [
                    'vat_id' => (int) $valor->vat_id,
                    'vat_valor' => $valor->vat_valor,
                    'vat_clave' => $valor->vat_clave,
                ];
            }
        }

        $atributos = collect($atributos)
            ->map(function (array $atributo): array {
                $valores = collect($atributo['valores'])->values();

                if ($atributo['es_talla']) {
                    $valores = $valores->sort(function (array $a, array $b): int {
                        return $this->compararValoresTalla($a['vat_valor'] ?? '', $a['vat_clave'] ?? null, $b['vat_valor'] ?? '', $b['vat_clave'] ?? null);
                    })->values();
                } else {
                    $valores = $valores->sortBy(fn ($item) => Str::lower((string) ($item['vat_valor'] ?? '')), SORT_NATURAL)->values();
                }

                $atributo['valores'] = $valores->all();

                return $atributo;
            })
            ->sort(function (array $a, array $b): int {
                if ($a['es_talla'] !== $b['es_talla']) {
                    return $a['es_talla'] ? -1 : 1;
                }

                return strnatcasecmp((string) $a['atr_nombre'], (string) $b['atr_nombre']);
            })
            ->values();

        return [
            'atributos' => $atributos->all(),
            'ordenamiento' => [
                'usa_talla' => $atributos->contains(fn ($item) => !empty($item['es_talla'])),
                'criterio' => $atributos->contains(fn ($item) => !empty($item['es_talla']))
                    ? 'Sin campo explícito de orden en valores; se aplica orden natural reforzado para tallas y valores numéricos usando nombre/clave.'
                    : 'Sin orden especial por talla para este producto.',
            ],
        ];
    }

    public function obtenerMatrizProducto(int $productoId, array $filtrosAtributo = []): array
    {
        $skus = ProductoSku::query()
            ->with([
                'producto:prd_id,prd_codigo,prd_nombre,prd_tipo',
                'valoresAtributo:vat_id,vat_atr_id,vat_valor,vat_clave',
                'valoresAtributo.atributo:atr_id,atr_nombre,atr_clave',
            ])
            ->where('psk_prd_id', $productoId)
            ->orderBy('psk_codigo')
            ->get();

        foreach ($this->normalizarFiltrosAtributo($filtrosAtributo) as $valorId) {
            $skus = $skus->filter(function (ProductoSku $sku) use ($valorId): bool {
                return $sku->valoresAtributo->contains(fn ($valor) => (int) $valor->vat_id === (int) $valorId);
            })->values();
        }

        $producto = $skus->first()?->producto;
        $atributosUsados = $this->obtenerAtributosUsadosEnSkus($skus);
        $atributoColor = $atributosUsados->first(fn ($atributo) => $this->esAtributoColor($atributo['atr_nombre'] ?? null, $atributo['atr_clave'] ?? null));

        if (!$atributoColor) {
            return [
                'disponible' => false,
                'motivo' => 'El producto no tiene una variación de color para construir la matriz.',
                'producto' => [
                    'prd_id' => $productoId,
                    'prd_codigo' => $producto?->prd_codigo,
                    'prd_nombre' => $producto?->prd_nombre,
                ],
                'atributos' => $atributosUsados->values()->all(),
            ];
        }

        $rowAtrId = (int) $atributoColor['atr_id'];
        $columnAttrs = $atributosUsados
            ->reject(fn ($atributo) => (int) $atributo['atr_id'] === $rowAtrId)
            ->sort(function (array $a, array $b): int {
                $aEsTalla = !empty($a['es_talla']);
                $bEsTalla = !empty($b['es_talla']);

                if ($aEsTalla !== $bEsTalla) {
                    return $aEsTalla ? -1 : 1;
                }

                return strnatcasecmp((string) $a['atr_nombre'], (string) $b['atr_nombre']);
            })
            ->values();

        $filas = [];
        $columnas = [];
        $columnasMap = [];

        foreach ($skus as $sku) {
            $valoresPorAtributo = $this->mapearValoresPorAtributo($sku);
            $filaValor = $valoresPorAtributo[$rowAtrId] ?? null;

            if (!$filaValor) {
                continue;
            }

            $filaKey = (string) $filaValor['vat_id'];
            if (!isset($filas[$filaKey])) {
                $filas[$filaKey] = [
                    'key' => $filaKey,
                    'valor' => $filaValor['vat_valor'],
                    'valor_clave' => $filaValor['vat_clave'],
                    'cells' => [],
                ];
            }

            [$columnKey, $columnData] = $this->construirColumnaMatriz($columnAttrs, $valoresPorAtributo);
            if (!isset($columnasMap[$columnKey])) {
                $columnasMap[$columnKey] = true;
                $columnas[] = $columnData;
            }

            $filas[$filaKey]['cells'][$columnKey] = [
                'psk_id' => (int) $sku->psk_id,
                'psk_codigo' => $sku->psk_codigo,
                'psk_codigo_barras' => $sku->psk_codigo_barras,
                'psk_nombre' => $sku->psk_nombre,
                'psk_precio' => $sku->psk_precio,
                'psk_costo' => $sku->psk_costo,
                'psk_stock_minimo' => $sku->psk_stock_minimo,
                'psk_stock_maximo' => $sku->psk_stock_maximo,
                'psk_estatus' => $sku->psk_estatus,
            ];
        }

        $columnas = collect($columnas)->sort(function (array $a, array $b): int {
            $partesA = $a['parts'] ?? [];
            $partesB = $b['parts'] ?? [];
            $longitud = max(count($partesA), count($partesB));

            for ($i = 0; $i < $longitud; $i++) {
                $parteA = $partesA[$i] ?? null;
                $parteB = $partesB[$i] ?? null;
                if ($parteA === null && $parteB === null) {
                    continue;
                }
                if ($parteA === null) {
                    return -1;
                }
                if ($parteB === null) {
                    return 1;
                }

                $comparacion = $this->compararParteColumnaMatriz($parteA, $parteB);
                if ($comparacion !== 0) {
                    return $comparacion;
                }
            }

            return strnatcasecmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? ''));
        })->values()->map(function (array $columna): array {
            unset($columna['parts']);

            return $columna;
        });

        $filas = collect($filas)->sort(function (array $a, array $b): int {
            return $this->compararValorNaturalGenerico($a['valor'] ?? '', $a['valor_clave'] ?? null, $b['valor'] ?? '', $b['valor_clave'] ?? null, false);
        })->values()->all();

        return [
            'disponible' => true,
            'producto' => [
                'prd_id' => $productoId,
                'prd_codigo' => $producto?->prd_codigo,
                'prd_nombre' => $producto?->prd_nombre,
            ],
            'row_attribute' => [
                'atr_id' => $atributoColor['atr_id'],
                'atr_nombre' => $atributoColor['atr_nombre'],
                'atr_clave' => $atributoColor['atr_clave'],
            ],
            'column_attributes' => $columnAttrs->map(fn ($atributo) => [
                'atr_id' => $atributo['atr_id'],
                'atr_nombre' => $atributo['atr_nombre'],
                'atr_clave' => $atributo['atr_clave'],
                'es_talla' => $atributo['es_talla'],
            ])->values()->all(),
            'columnas' => $columnas->all(),
            'filas' => $filas,
            'usa_talla' => $columnAttrs->contains(fn ($atributo) => !empty($atributo['es_talla'])),
            'criterio_orden_talla' => $columnAttrs->contains(fn ($atributo) => !empty($atributo['es_talla']))
                ? 'Sin campo explícito de orden en valores; se usa orden natural reforzado por clave/nombre para tallas y numéricos.'
                : null,
        ];
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
                'psk_precio' => array_key_exists('psk_precio', $datos) ? (float) $datos['psk_precio'] : $producto->prd_precio_base,
                'psk_costo' => array_key_exists('psk_costo', $datos) ? (float) $datos['psk_costo'] : $producto->prd_costo,
                'psk_stock_minimo' => $datos['psk_stock_minimo'] ?? $producto->prd_stock_minimo,
                'psk_stock_maximo' => $datos['psk_stock_maximo'] ?? $producto->prd_stock_maximo,
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
            $sku = ProductoSku::query()->with('valoresAtributo:vat_id')->findOrFail($id);
            $valorIds = $sku->valoresAtributo->pluck('vat_id')->map(fn ($v) => (int) $v)->values()->all();

            if (empty($valorIds)) {
                $valorIds = $this->normalizarValorIds((array) ($datos['valor_atributo_ids'] ?? []));
            }

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
                'psk_precio' => array_key_exists('psk_precio', $datos) ? (float) $datos['psk_precio'] : $sku->psk_precio,
                'psk_costo' => array_key_exists('psk_costo', $datos) ? (float) $datos['psk_costo'] : $sku->psk_costo,
                'psk_stock_minimo' => $datos['psk_stock_minimo'] ?? $sku->psk_stock_minimo,
                'psk_stock_maximo' => $datos['psk_stock_maximo'] ?? $sku->psk_stock_maximo,
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

    private function normalizarFiltrosAtributo(array $filtros): array
    {
        return collect($filtros)
            ->map(fn ($valor) => (int) $valor)
            ->filter(fn ($valor) => $valor > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function coleccionTieneAtributoTalla(Collection $skus): bool
    {
        foreach ($skus as $sku) {
            foreach ($sku->valoresAtributo as $valor) {
                if ($this->esAtributoTalla($valor->atributo?->atr_nombre, $valor->atributo?->atr_clave)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function ordenarSkusPorTalla(Collection $skus): Collection
    {
        return $skus->sort(function (ProductoSku $a, ProductoSku $b): int {
            [$tallaA, $claveA] = $this->extraerValorTalla($a);
            [$tallaB, $claveB] = $this->extraerValorTalla($b);

            $comparacion = $this->compararValoresTalla($tallaA, $claveA, $tallaB, $claveB);
            if ($comparacion !== 0) {
                return $comparacion;
            }

            return strnatcasecmp((string) $a->psk_codigo, (string) $b->psk_codigo);
        })->values();
    }

    private function extraerValorTalla(ProductoSku $sku): array
    {
        foreach ($sku->valoresAtributo as $valor) {
            if ($this->esAtributoTalla($valor->atributo?->atr_nombre, $valor->atributo?->atr_clave)) {
                return [(string) $valor->vat_valor, (string) ($valor->vat_clave ?? '')];
            }
        }

        return ['', ''];
    }

    private function esAtributoTalla(?string $nombre, ?string $clave = null): bool
    {
        $claveNormalizada = $this->normalizarTexto($clave);
        if ($claveNormalizada !== '' && str_contains($claveNormalizada, 'talla')) {
            return true;
        }

        $nombreNormalizado = $this->normalizarTexto($nombre);

        return in_array($nombreNormalizado, ['talla', 'tallas', 'tamano', 'tamanio', 'medida'], true);
    }

    private function esAtributoColor(?string $nombre, ?string $clave = null): bool
    {
        $claveNormalizada = $this->normalizarTexto($clave);
        if ($claveNormalizada !== '' && str_contains($claveNormalizada, 'color')) {
            return true;
        }

        $nombreNormalizado = $this->normalizarTexto($nombre);

        return in_array($nombreNormalizado, ['color', 'colores'], true);
    }

    private function compararValoresTalla(string $valorA, ?string $claveA, string $valorB, ?string $claveB): int
    {
        [$grupoA, $pesoA, $textoA] = $this->obtenerPesoTalla($valorA, $claveA);
        [$grupoB, $pesoB, $textoB] = $this->obtenerPesoTalla($valorB, $claveB);

        if ($grupoA !== $grupoB) {
            return $grupoA <=> $grupoB;
        }

        if ($pesoA !== $pesoB) {
            return $pesoA <=> $pesoB;
        }

        return strnatcasecmp($textoA, $textoB);
    }

    private function compararValorNaturalGenerico(string $valorA, ?string $claveA, string $valorB, ?string $claveB, bool $esTalla): int
    {
        if ($esTalla) {
            return $this->compararValoresTalla($valorA, $claveA, $valorB, $claveB);
        }

        return strnatcasecmp($valorA, $valorB);
    }

    private function obtenerPesoTalla(string $valor, ?string $clave = null): array
    {
        $texto = trim($valor);
        $normalizado = $this->normalizarTexto($valor);
        $claveNormalizada = $this->normalizarTexto($clave);
        $compuesto = trim($normalizado . ' ' . $claveNormalizada);

        if ($numero = $this->extraerNumeroTalla($texto)) {
            return [0, $numero, $texto];
        }

        // Fallback semántico cuando el catálogo no guarda un orden explícito para tallas.
        $mapa = [
            'xxxs' => 10,
            '3xs' => 10,
            'xxs' => 20,
            '2xs' => 20,
            'xs' => 30,
            'extra chico' => 30,
            'extra small' => 30,
            's' => 40,
            'sm' => 40,
            'small' => 40,
            'ch' => 40,
            'chica' => 40,
            'chico' => 40,
            'm' => 50,
            'med' => 50,
            'medium' => 50,
            'mediana' => 50,
            'g' => 60,
            'lg' => 60,
            'large' => 60,
            'grande' => 60,
            'l' => 60,
            'xl' => 70,
            'xg' => 70,
            'extra grande' => 70,
            'extra large' => 70,
            'xxl' => 80,
            '2xl' => 80,
            '2xg' => 80,
            'xxxl' => 90,
            '3xl' => 90,
            '3xg' => 90,
            'unitalla' => 200,
            'unica' => 200,
            'unico' => 200,
            'std' => 210,
            'estandar' => 210,
        ];

        foreach ($mapa as $patron => $peso) {
            if ($normalizado === $patron || str_contains($compuesto, $patron)) {
                return [1, $peso, $texto];
            }
        }

        return [2, 9999, $texto];
    }

    private function extraerNumeroTalla(string $valor): ?float
    {
        if (preg_match('/\d+(?:\.\d+)?/', $valor, $matches) !== 1) {
            return null;
        }

        return (float) $matches[0];
    }

    private function normalizarTexto(?string $texto): string
    {
        $texto = Str::of((string) $texto)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->trim()
            ->value();

        return preg_replace('/\s+/', ' ', $texto) ?: '';
    }

    private function obtenerAtributosUsadosEnSkus(Collection $skus): Collection
    {
        $atributos = [];

        foreach ($skus as $sku) {
            foreach ($sku->valoresAtributo as $valor) {
                $atributo = $valor->atributo;
                if (!$atributo) {
                    continue;
                }

                $atributoId = (int) $atributo->atr_id;
                if (isset($atributos[$atributoId])) {
                    continue;
                }

                $atributos[$atributoId] = [
                    'atr_id' => $atributoId,
                    'atr_nombre' => $atributo->atr_nombre,
                    'atr_clave' => $atributo->atr_clave,
                    'es_talla' => $this->esAtributoTalla($atributo->atr_nombre, $atributo->atr_clave),
                    'es_color' => $this->esAtributoColor($atributo->atr_nombre, $atributo->atr_clave),
                ];
            }
        }

        return collect($atributos);
    }

    private function mapearValoresPorAtributo(ProductoSku $sku): array
    {
        $mapa = [];

        foreach ($sku->valoresAtributo as $valor) {
            $mapa[(int) $valor->vat_atr_id] = [
                'vat_id' => (int) $valor->vat_id,
                'vat_valor' => (string) $valor->vat_valor,
                'vat_clave' => (string) ($valor->vat_clave ?? ''),
            ];
        }

        return $mapa;
    }

    private function construirColumnaMatriz(Collection $columnAttrs, array $valoresPorAtributo): array
    {
        if ($columnAttrs->isEmpty()) {
            return [
                '__base__',
                [
                    'key' => '__base__',
                    'label' => 'Base',
                    'detail' => null,
                    'parts' => [],
                ],
            ];
        }

        $partes = [];
        $tokens = [];

        foreach ($columnAttrs as $atributo) {
            $valor = $valoresPorAtributo[(int) $atributo['atr_id']] ?? null;
            $textoValor = (string) ($valor['vat_valor'] ?? 'Sin valor');
            $textoClave = (string) ($valor['vat_clave'] ?? '');
            $tokens[] = (int) $atributo['atr_id'] . ':' . (int) ($valor['vat_id'] ?? 0);
            $partes[] = [
                'atr_id' => (int) $atributo['atr_id'],
                'atr_nombre' => (string) $atributo['atr_nombre'],
                'atr_clave' => (string) ($atributo['atr_clave'] ?? ''),
                'es_talla' => (bool) ($atributo['es_talla'] ?? false),
                'vat_valor' => $textoValor,
                'vat_clave' => $textoClave,
            ];
        }

        $label = count($partes) === 1
            ? $partes[0]['vat_valor']
            : implode(' / ', array_map(fn ($parte) => $parte['vat_valor'], $partes));

        $detail = count($partes) > 1
            ? implode(' / ', array_map(fn ($parte) => $parte['atr_nombre'] . ': ' . $parte['vat_valor'], $partes))
            : $partes[0]['atr_nombre'];

        return [
            implode('||', $tokens),
            [
                'key' => implode('||', $tokens),
                'label' => $label,
                'detail' => $detail,
                'parts' => $partes,
            ],
        ];
    }

    private function compararParteColumnaMatriz(array $parteA, array $parteB): int
    {
        $esTalla = !empty($parteA['es_talla']) || !empty($parteB['es_talla']);

        if ((int) ($parteA['atr_id'] ?? 0) !== (int) ($parteB['atr_id'] ?? 0)) {
            return strnatcasecmp((string) ($parteA['atr_nombre'] ?? ''), (string) ($parteB['atr_nombre'] ?? ''));
        }

        return $this->compararValorNaturalGenerico(
            (string) ($parteA['vat_valor'] ?? ''),
            (string) ($parteA['vat_clave'] ?? ''),
            (string) ($parteB['vat_valor'] ?? ''),
            (string) ($parteB['vat_clave'] ?? ''),
            $esTalla
        );
    }

    private function firmaCombinacion(array $valorIds): string
    {
        sort($valorIds);

        return implode('-', $valorIds);
    }
}
