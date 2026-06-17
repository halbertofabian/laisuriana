<?php

namespace App\Services\Operacion;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExistenciaMatrizService
{
    public function paginarDataTable(
        array $filtros = [],
        int $start = 0,
        int $length = 10,
        int $orderColumn = 0,
        string $orderDir = 'asc',
        bool $soloNegativos = false,
    ): array {
        $start = max(0, $start);
        $length = max(1, min(100, $length));
        $orderDir = strtolower($orderDir) === 'desc' ? 'desc' : 'asc';

        $columnasOrden = [
            0 => 'marca_nombre',
            1 => 'modelo_nombre',
            2 => 'linea_nombre',
            3 => 'concepto_nombre',
            4 => 'color_nombre',
            6 => 'total_articulos',
            7 => 'precio_unitario',
            8 => 'costo_unitario',
            9 => 'total_importe_precio',
        ];

        $orderBy = $columnasOrden[$orderColumn] ?? 'marca_nombre';

        $queryBase = $this->queryFilasBase($filtros, false, $soloNegativos);
        $queryFiltrado = $this->queryFilasBase($filtros, true, $soloNegativos);

        $total = DB::query()->fromSub($queryBase, 'base_rows')->count();
        $filtrado = DB::query()->fromSub($queryFiltrado, 'filtered_rows')->count();

        $filas = DB::query()
            ->fromSub($queryFiltrado, 'rows')
            ->orderBy($orderBy, $orderDir)
            ->orderBy('producto_nombre')
            ->orderBy('color_nombre')
            ->offset($start)
            ->limit($length)
            ->get();

        return [
            'recordsTotal' => $total,
            'recordsFiltered' => $filtrado,
            'data' => $this->hidratarTallas($filas, $filtros),
        ];
    }

    public function exportarTodos(
        array $filtros = [],
        string $orderBy = 'marca_nombre',
        string $orderDir = 'asc',
        bool $soloNegativos = false,
    ): array {
        $columnasValidas = [
            'marca_nombre', 'modelo_nombre', 'linea_nombre', 'concepto_nombre',
            'color_nombre', 'total_articulos', 'precio_unitario', 'costo_unitario', 'total_importe_precio',
        ];
        $orderBy  = in_array($orderBy, $columnasValidas, true) ? $orderBy : 'marca_nombre';
        $orderDir = strtolower($orderDir) === 'desc' ? 'desc' : 'asc';

        $filas = DB::query()
            ->fromSub($this->queryFilasBase($filtros, true, $soloNegativos), 'rows')
            ->orderBy($orderBy, $orderDir)
            ->orderBy('producto_nombre')
            ->orderBy('color_nombre')
            ->get();

        return $this->hidratarTallas($filas, $filtros);
    }

    private function hidratarTallas(Collection $filas, array $filtros = []): array
    {
        if ($filas->isEmpty()) {
            return [];
        }

        $soloDisponibles = !empty($filtros['solo_disponibles']);

        $productoIds = $filas->pluck('prd_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $tallasPorProducto = $this->resolverTallasPorProducto($productoIds);
        $existenciasPorFila = $this->resolverExistenciasPorFila($productoIds, $filtros);

        return $filas->map(function ($fila) use ($tallasPorProducto, $existenciasPorFila, $soloDisponibles): ?array {
            $productoId = (int) $fila->prd_id;
            $colorId = (int) ($fila->color_vat_id ?? 0);
            $rowKey = $this->rowKey($productoId, $colorId);
            $tallas = $tallasPorProducto[$productoId] ?? [['key' => '__base__', 'label' => 'Base']];
            $celdas = $existenciasPorFila[$rowKey] ?? [];

            $tallasTransformadas = collect($tallas)->map(function (array $talla) use ($celdas, $colorId): array {
                $celda = $celdas[$talla['key']] ?? null;
                $existencia = $celda['existencia'] ?? null;
                $existenciaFloat = (float) $existencia;
                $tieneHistorial = (bool) ($celda['tiene_historial'] ?? false);
                $estado = $celda === null
                    ? 'sin_sku'
                    : (!$tieneHistorial
                        ? 'sin_historial'
                        : ($existenciaFloat < 0
                            ? 'negativo'
                            : ($existenciaFloat > 0 ? 'con_existencia' : 'cero')));

                return [
                    'talla' => $talla['label'],
                    'existencia' => $existencia !== null ? round((float) $existencia, 2) : null,
                    'estado' => $estado,
                    'psk_id' => $celda['psk_id'] ?? null,
                    'talla_key' => $talla['key'],
                    'color_vat_id' => $celda['color_vat_id'] ?? ($colorId > 0 ? $colorId : null),
                    'psk_precio' => isset($celda['psk_precio']) ? round((float) $celda['psk_precio'], 2) : 0.0,
                    'psk_costo' => isset($celda['psk_costo']) ? round((float) $celda['psk_costo'], 2) : 0.0,
                ];
            });

            if ($soloDisponibles) {
                $tallasTransformadas = $tallasTransformadas
                    ->filter(fn (array $talla) => $talla['estado'] === 'con_existencia')
                    ->values();
            } else {
                $tallasTransformadas = $tallasTransformadas->values();
            }

            if ($soloDisponibles && $tallasTransformadas->isEmpty()) {
                return null;
            }

            $totalArticulos = round((float) ($fila->total_articulos ?? 0), 2);
            $precioUnitario = round((float) ($fila->precio_unitario ?? 0), 2);
            $costoUnitario = round((float) ($fila->costo_unitario ?? 0), 2);
            $totalImportePrecio = round((float) ($fila->total_importe_precio ?? 0), 2);
            $totalImporteCosto = round((float) ($fila->total_importe_costo ?? 0), 2);
            $skuTotal = (int) $fila->sku_total;

            if ($soloDisponibles) {
                $skuTotal = $tallasTransformadas
                    ->filter(fn (array $talla) => !empty($talla['psk_id']))
                    ->count();

                $totalArticulos = round((float) $tallasTransformadas->sum(fn (array $talla) => (float) ($talla['existencia'] ?? 0)), 2);
                $totalImportePrecio = round((float) $tallasTransformadas->sum(fn (array $talla) => (float) ($talla['existencia'] ?? 0) * (float) ($talla['psk_precio'] ?? 0)), 2);
                $totalImporteCosto = round((float) $tallasTransformadas->sum(fn (array $talla) => (float) ($talla['existencia'] ?? 0) * (float) ($talla['psk_costo'] ?? 0)), 2);
                $precioUnitario = $totalArticulos > 0 ? round($totalImportePrecio / $totalArticulos, 2) : 0.0;
                $costoUnitario = $totalArticulos > 0 ? round($totalImporteCosto / $totalArticulos, 2) : 0.0;
            }

            return [
                'prd_id' => $productoId,
                'producto_codigo' => (string) $fila->producto_codigo,
                'producto_nombre' => (string) $fila->producto_nombre,
                'marca_nombre' => (string) $fila->marca_nombre,
                'modelo_nombre' => (string) ($fila->modelo_nombre ?? ''),
                'linea_nombre' => (string) ($fila->linea_nombre ?? ''),
                'concepto_nombre' => (string) ($fila->concepto_nombre ?? ''),
                'color_nombre' => (string) ($fila->color_nombre ?? 'Sin color'),
                'color_vat_id' => $colorId > 0 ? $colorId : null,
                'sku_total' => $skuTotal,
                'total_articulos' => $totalArticulos,
                'precio_unitario' => $precioUnitario,
                'costo_unitario' => $costoUnitario,
                'total_importe_precio' => $totalImportePrecio,
                'total_importe_costo' => $totalImporteCosto,
                'tallas' => $tallasTransformadas
                    ->map(fn (array $talla) => collect($talla)->except(['psk_precio', 'psk_costo'])->all())
                    ->values()
                    ->all(),
            ];
        })->filter()->values()->all();
    }

    private function resolverTallasPorProducto(array $productoIds): array
    {
        if (empty($productoIds)) {
            return [];
        }

        $tallas = [];

        $corridas = DB::table('tbl_producto_corridas_prc as prc')
            ->join('tbl_atributos_atr as atr', 'atr.atr_id', '=', 'prc.prc_atr_id')
            ->join('tbl_producto_corrida_valores_pcv as pcv', function ($join): void {
                $join->on('pcv.pcv_prc_id', '=', 'prc.prc_id')
                    ->where('pcv.pcv_deleted', false)
                    ->whereNull('pcv.pcv_deleted_at')
                    ->where('pcv.pcv_estatus', 'activo');
            })
            ->join('tbl_valores_atributo_vat as vat', function ($join): void {
                $join->on('vat.vat_id', '=', 'pcv.pcv_vat_id')
                    ->where('vat.vat_deleted', false)
                    ->whereNull('vat.vat_deleted_at')
                    ->where('vat.vat_estatus', 'activo');
            })
            ->whereIn('prc.prc_prd_id', $productoIds)
            ->where('prc.prc_deleted', false)
            ->whereNull('prc.prc_deleted_at')
            ->where('prc.prc_estatus', 'activo')
            ->where(function ($query): void {
                $query->whereIn(DB::raw('LOWER(atr.atr_nombre)'), ['talla', 'tallas', 'tamano', 'tamaño', 'tamanio', 'medida'])
                    ->orWhereRaw("LOWER(COALESCE(atr.atr_clave, '')) like ?", ['%talla%']);
            })
            ->orderBy('prc.prc_orden')
            ->get([
                'prc.prc_prd_id',
                'prc.prc_orden',
                'vat.vat_id',
                'vat.vat_valor',
                'vat.vat_clave',
            ]);

        foreach ($corridas as $item) {
            $productoId = (int) $item->prc_prd_id;
            $key = (string) $item->vat_id;
            $tallas[$productoId] = $tallas[$productoId] ?? [];
            $tallas[$productoId][$key] = [
                'key' => $key,
                'label' => (string) $item->vat_valor,
                'clave' => (string) ($item->vat_clave ?? ''),
                'orden' => (int) ($item->prc_orden ?? 9999),
            ];
        }

        $tallasSku = DB::table('tbl_producto_skus_psk as psk')
            ->leftJoinSub($this->subqueryValorAtributoPorSku('talla'), 'talla', function ($join): void {
                $join->on('talla.psk_id', '=', 'psk.psk_id');
            })
            ->whereIn('psk.psk_prd_id', $productoIds)
            ->where('psk.psk_deleted', false)
            ->whereNull('psk.psk_deleted_at')
            ->where('psk.psk_estatus', 'activo')
            ->whereNotNull('talla.vat_id')
            ->groupBy('psk.psk_prd_id', 'talla.vat_id', 'talla.vat_valor', 'talla.vat_clave')
            ->get([
                'psk.psk_prd_id',
                'talla.vat_id',
                'talla.vat_valor',
                'talla.vat_clave',
            ]);

        foreach ($tallasSku as $item) {
            $productoId = (int) $item->psk_prd_id;
            $key = (string) $item->vat_id;
            $tallas[$productoId] = $tallas[$productoId] ?? [];
            $tallas[$productoId][$key] = $tallas[$productoId][$key] ?? [
                'key' => $key,
                'label' => (string) $item->vat_valor,
                'clave' => (string) ($item->vat_clave ?? ''),
                'orden' => null,
            ];
        }

        foreach ($productoIds as $productoId) {
            if (empty($tallas[$productoId])) {
                $tallas[$productoId] = [['key' => '__base__', 'label' => 'Base', 'clave' => '', 'orden' => null]];
                continue;
            }

            $tallas[$productoId] = collect($tallas[$productoId])
                ->sort(function (array $a, array $b): int {
                    $ordenA = $a['orden'];
                    $ordenB = $b['orden'];

                    if ($ordenA !== null && $ordenB !== null && $ordenA !== $ordenB) {
                        return $ordenA <=> $ordenB;
                    }

                    if ($ordenA !== null && $ordenB === null) {
                        return -1;
                    }

                    if ($ordenA === null && $ordenB !== null) {
                        return 1;
                    }

                    return $this->compararValoresTalla(
                        (string) ($a['label'] ?? ''),
                        (string) ($a['clave'] ?? ''),
                        (string) ($b['label'] ?? ''),
                        (string) ($b['clave'] ?? ''),
                    );
                })
                ->map(fn (array $item) => [
                    'key' => $item['key'],
                    'label' => $item['label'],
                ])
                ->values()
                ->all();
        }

        return $tallas;
    }

    private function resolverExistenciasPorFila(array $productoIds, array $filtros = []): array
    {
        $registros = DB::table('tbl_producto_skus_psk as psk')
            ->leftJoinSub($this->subqueryValorAtributoPorSku('color'), 'color', function ($join): void {
                $join->on('color.psk_id', '=', 'psk.psk_id');
            })
            ->leftJoinSub($this->subqueryValorAtributoPorSku('talla'), 'talla', function ($join): void {
                $join->on('talla.psk_id', '=', 'psk.psk_id');
            })
            ->leftJoinSub($this->subqueryExistenciaGlobalPorSku($filtros), 'inv', function ($join): void {
                $join->on('inv.psk_id', '=', 'psk.psk_id');
            })
            ->leftJoinSub($this->subqueryHistorialExistenciaPorSku(), 'hist', function ($join): void {
                $join->on('hist.psk_id', '=', 'psk.psk_id');
            })
            ->whereIn('psk.psk_prd_id', $productoIds)
            ->where('psk.psk_deleted', false)
            ->whereNull('psk.psk_deleted_at')
            ->where('psk.psk_estatus', 'activo')
            ->groupBy(
                'psk.psk_id',
                'psk.psk_prd_id',
                'color.vat_id',
                'talla.vat_id',
                'talla.vat_valor',
                'talla.vat_clave'
            )
            ->get([
                'psk.psk_id',
                'psk.psk_prd_id',
                DB::raw('MAX(psk.psk_precio) as psk_precio'),
                DB::raw('MAX(psk.psk_costo) as psk_costo'),
                DB::raw('COALESCE(color.vat_id, 0) as color_vat_id'),
                'talla.vat_id as talla_vat_id',
                'talla.vat_valor as talla_valor',
                'talla.vat_clave as talla_clave',
                DB::raw('ROUND(SUM(COALESCE(inv.existencia_total, 0)), 2) as existencia_total'),
                DB::raw('COALESCE(MAX(hist.tiene_historial), 0) as tiene_historial'),
            ]);

        $mapa = [];
        foreach ($registros as $item) {
            $rowKey = $this->rowKey((int) $item->psk_prd_id, (int) ($item->color_vat_id ?? 0));
            $sizeKey = $item->talla_vat_id ? (string) $item->talla_vat_id : '__base__';
            $mapa[$rowKey] = $mapa[$rowKey] ?? [];
            $mapa[$rowKey][$sizeKey] = [
                'psk_id' => (int) $item->psk_id,
                'existencia' => (float) ($item->existencia_total ?? 0),
                'label' => $item->talla_valor ? (string) $item->talla_valor : 'Base',
                'color_vat_id' => (int) ($item->color_vat_id ?? 0) ?: null,
                'tiene_historial' => (int) ($item->tiene_historial ?? 0) === 1,
                'psk_precio' => (float) ($item->psk_precio ?? 0),
                'psk_costo' => (float) ($item->psk_costo ?? 0),
            ];
        }

        return $mapa;
    }

    private function queryFilasBase(array $filtros = [], bool $conBuscar = true, bool $soloNegativos = false)
    {
        $query = DB::table('tbl_producto_skus_psk as psk')
            ->join('tbl_productos_prd as prd', 'prd.prd_id', '=', 'psk.psk_prd_id')
            ->leftJoin('tbl_marcas_mrc as mrc', 'mrc.mrc_id', '=', 'prd.prd_mrc_id')
            ->leftJoin('tbl_modelos_mdl as mdl', 'mdl.mdl_id', '=', 'prd.prd_mdl_id')
            ->leftJoin('tbl_lineas_lna as lna', 'lna.lna_id', '=', 'prd.prd_lna_id')
            ->leftJoin('tbl_categorias_ctg as ctg', 'ctg.ctg_id', '=', 'prd.prd_ctg_id')
            ->leftJoin('tbl_descripciones_dsc as dsc', 'dsc.dsc_id', '=', 'prd.prd_dsc_id')
            ->leftJoinSub($this->subqueryValorAtributoPorSku('color'), 'color', function ($join): void {
                $join->on('color.psk_id', '=', 'psk.psk_id');
            })
            ->leftJoinSub($this->subqueryExistenciaGlobalPorSku($filtros), 'inv', function ($join): void {
                $join->on('inv.psk_id', '=', 'psk.psk_id');
            })
            ->where('psk.psk_deleted', false)
            ->whereNull('psk.psk_deleted_at')
            ->where('psk.psk_estatus', 'activo')
            ->where('prd.prd_deleted', false)
            ->whereNull('prd.prd_deleted_at')
            ->where('prd.prd_estatus', 'activo')
            ->when(!empty($filtros['prd_id']), fn ($q) => $q->where('prd.prd_id', (int) $filtros['prd_id']))
            ->when(!empty($filtros['prd_mrc_id']), fn ($q) => $q->where('prd.prd_mrc_id', (int) $filtros['prd_mrc_id']))
            ->when(!empty($filtros['prd_mdl_id']), fn ($q) => $q->where('prd.prd_mdl_id', (int) $filtros['prd_mdl_id']))
            ->when(!empty($filtros['prd_lna_id']), fn ($q) => $q->where('prd.prd_lna_id', (int) $filtros['prd_lna_id']))
            ->when(!empty($filtros['prd_ctg_id']), fn ($q) => $q->where('prd.prd_ctg_id', (int) $filtros['prd_ctg_id']))
            ->when(!empty($filtros['prd_dsc_id']), fn ($q) => $q->where('prd.prd_dsc_id', (int) $filtros['prd_dsc_id']))
            ->when($conBuscar && !empty($filtros['buscar']), function ($q) use ($filtros): void {
                $buscar = trim((string) $filtros['buscar']);
                $q->where(function ($sub) use ($buscar): void {
                    $sub->where('prd.prd_codigo', 'like', "%{$buscar}%")
                        ->orWhere('prd.prd_nombre', 'like', "%{$buscar}%")
                        ->orWhere('mrc.mrc_nombre', 'like', "%{$buscar}%")
                        ->orWhere('mdl.mdl_nombre', 'like', "%{$buscar}%")
                        ->orWhere('lna.lna_nombre', 'like', "%{$buscar}%")
                        ->orWhere('ctg.ctg_nombre', 'like', "%{$buscar}%")
                        ->orWhere('dsc.dsc_nombre', 'like', "%{$buscar}%")
                        ->orWhere('color.vat_valor', 'like', "%{$buscar}%");
                });
            })
            ->groupBy(
                'prd.prd_id',
                'prd.prd_codigo',
                'prd.prd_nombre',
                'mrc.mrc_nombre',
                'mdl.mdl_nombre',
                'lna.lna_nombre',
                'ctg.ctg_nombre',
                'color.vat_id',
                'color.vat_valor'
            )
            ->when(!empty($filtros['solo_disponibles']), fn ($q) => $q->havingRaw('MAX(CASE WHEN COALESCE(inv.existencia_total, 0) > 0 THEN 1 ELSE 0 END) = 1'))
            ->when($soloNegativos, fn ($q) => $q->havingRaw('MIN(inv.existencia_total) < 0'));

        return $query->select([
            'prd.prd_id',
            'prd.prd_codigo as producto_codigo',
            'prd.prd_nombre as producto_nombre',
            DB::raw("COALESCE(mrc.mrc_nombre, '') as marca_nombre"),
            DB::raw("COALESCE(mdl.mdl_nombre, '') as modelo_nombre"),
            DB::raw("COALESCE(lna.lna_nombre, '') as linea_nombre"),
            DB::raw("COALESCE(ctg.ctg_nombre, '') as concepto_nombre"),
            DB::raw('COALESCE(color.vat_id, 0) as color_vat_id'),
            DB::raw("COALESCE(color.vat_valor, 'Sin color') as color_nombre"),
            DB::raw('COUNT(psk.psk_id) as sku_total'),
            DB::raw('ROUND(SUM(COALESCE(inv.existencia_total, 0)), 2) as total_articulos'),
            DB::raw('ROUND(CASE WHEN SUM(COALESCE(inv.existencia_total, 0)) > 0 THEN SUM(COALESCE(inv.existencia_total, 0) * COALESCE(psk.psk_precio, 0)) / SUM(COALESCE(inv.existencia_total, 0)) ELSE AVG(COALESCE(psk.psk_precio, 0)) END, 2) as precio_unitario'),
            DB::raw('ROUND(CASE WHEN SUM(COALESCE(inv.existencia_total, 0)) > 0 THEN SUM(COALESCE(inv.existencia_total, 0) * COALESCE(psk.psk_costo, 0)) / SUM(COALESCE(inv.existencia_total, 0)) ELSE AVG(COALESCE(psk.psk_costo, 0)) END, 2) as costo_unitario'),
            DB::raw('ROUND(SUM(COALESCE(inv.existencia_total, 0) * COALESCE(psk.psk_precio, 0)), 2) as total_importe_precio'),
            DB::raw('ROUND(SUM(COALESCE(inv.existencia_total, 0) * COALESCE(psk.psk_costo, 0)), 2) as total_importe_costo'),
        ]);
    }

    private function subqueryExistenciaGlobalPorSku(array $filtros = [])
    {
        return DB::table('tbl_existencias_almacen_exa as exa')
            ->select([
                'exa.exa_psk_id as psk_id',
                DB::raw('ROUND(SUM(exa.exa_existencia), 2) as existencia_total'),
            ])
            ->where('exa.exa_deleted', false)
            ->whereNull('exa.exa_deleted_at')
            ->where('exa.exa_estatus', 'activo')
            ->when(!empty($filtros['min_scl_id']), fn ($q) => $q->where('exa.exa_scl_id', (int) $filtros['min_scl_id']))
            ->when(!empty($filtros['min_alm_id']), fn ($q) => $q->where('exa.exa_alm_id', (int) $filtros['min_alm_id']))
            ->groupBy('exa.exa_psk_id');
    }

    private function subqueryHistorialExistenciaPorSku()
    {
        return DB::table('tbl_existencias_almacen_exa as exa')
            ->select([
                'exa.exa_psk_id as psk_id',
                DB::raw('1 as tiene_historial'),
            ])
            ->where('exa.exa_deleted', false)
            ->whereNull('exa.exa_deleted_at')
            ->where('exa.exa_estatus', 'activo')
            ->groupBy('exa.exa_psk_id');
    }

    private function subqueryValorAtributoPorSku(string $tipo)
    {
        $nombres = $tipo === 'color'
            ? ['color', 'colores']
            : ['talla', 'tallas', 'tamano', 'tamaño', 'tamanio', 'medida'];
        $like = $tipo === 'color' ? '%color%' : '%talla%';

        return DB::table('tbl_sku_valores_atributo_sva as sva')
            ->join('tbl_valores_atributo_vat as vat', function ($join): void {
                $join->on('vat.vat_id', '=', 'sva.sva_vat_id')
                    ->where('vat.vat_deleted', false)
                    ->whereNull('vat.vat_deleted_at')
                    ->where('vat.vat_estatus', 'activo');
            })
            ->join('tbl_atributos_atr as atr', function ($join): void {
                $join->on('atr.atr_id', '=', 'vat.vat_atr_id')
                    ->where('atr.atr_deleted', false)
                    ->whereNull('atr.atr_deleted_at')
                    ->where('atr.atr_estatus', 'activo');
            })
            ->where('sva.sva_deleted', false)
            ->whereNull('sva.sva_deleted_at')
            ->where('sva.sva_estatus', 'activo')
            ->where(function ($query) use ($nombres, $like): void {
                $query->whereIn(DB::raw('LOWER(atr.atr_nombre)'), $nombres)
                    ->orWhereRaw("LOWER(COALESCE(atr.atr_clave, '')) like ?", [$like]);
            })
            ->groupBy('sva.sva_psk_id')
            ->select([
                'sva.sva_psk_id as psk_id',
                DB::raw('MIN(vat.vat_id) as vat_id'),
                DB::raw('MIN(vat.vat_valor) as vat_valor'),
                DB::raw('MIN(COALESCE(vat.vat_clave, \'\')) as vat_clave'),
            ]);
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

    private function obtenerPesoTalla(string $valor, ?string $clave = null): array
    {
        $texto = trim($valor);
        $normalizado = $this->normalizarTexto($valor);
        $claveNormalizada = $this->normalizarTexto($clave);
        $compuesto = trim($normalizado . ' ' . $claveNormalizada);
        $numero = $this->extraerNumeroTalla($texto);

        if ($numero !== null) {
            return [0, $numero, $texto];
        }

        $mapa = [
            'xxxs' => 10,
            '3xs' => 10,
            'xxs' => 20,
            '2xs' => 20,
            'xs' => 30,
            's' => 40,
            'small' => 40,
            'ch' => 40,
            'm' => 50,
            'medium' => 50,
            'mediana' => 50,
            'g' => 60,
            'l' => 60,
            'large' => 60,
            'xl' => 70,
            'xg' => 70,
            'xxl' => 80,
            '2xl' => 80,
            'xxxl' => 90,
            '3xl' => 90,
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
        return (string) Str::of((string) $texto)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->trim()
            ->replaceMatches('/\s+/', ' ');
    }

    private function rowKey(int $productoId, int $colorId): string
    {
        return $productoId . ':' . $colorId;
    }
}
