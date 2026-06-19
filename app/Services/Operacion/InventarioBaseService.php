<?php

namespace App\Services\Operacion;

use App\Models\Almacen;
use App\Models\ExistenciaAlmacen;
use App\Models\ExistenciaSucursal;
use App\Models\MinimoInventario;
use App\Models\MovimientoInventario;
use App\Models\PreferenciaMatrizProducto;
use App\Models\Producto;
use App\Models\ProductoSku;
use App\Models\RecepcionMercancia;
use App\Models\RecepcionMercanciaDetalle;
use App\Models\Sucursal;
use App\Models\TipoMovimientoInventario;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use TCPDF;

class InventarioBaseService
{
    public function __construct(private readonly AuditoriaService $auditoriaService)
    {
    }

    public function opcionesBase(): array
    {
        return [
            'sucursales' => Sucursal::query()
                ->where('scl_estatus', 'activo')
                ->orderBy('scl_nombre')
                ->get(['scl_id', 'scl_nombre']),
            'almacenes' => Almacen::query()
                ->with('sucursal:scl_id,scl_nombre')
                ->where('alm_estatus', 'activo')
                ->orderBy('alm_scl_id')
                ->orderBy('alm_nombre')
                ->get(['alm_id', 'alm_scl_id', 'alm_nombre']),
            'skus' => ProductoSku::query()
                ->with('producto:prd_id,prd_nombre')
                ->where('psk_estatus', 'activo')
                ->orderBy('psk_nombre')
                ->limit(500)
                ->get(['psk_id', 'psk_prd_id', 'psk_codigo', 'psk_nombre']),
            'productos_base' => collect(),
            'marcas' => DB::table('tbl_marcas_mrc')
                ->where('mrc_deleted', false)
                ->whereNull('mrc_deleted_at')
                ->where('mrc_estatus', 'activo')
                ->orderBy('mrc_nombre')
                ->get(['mrc_id', 'mrc_nombre']),
            'modelos' => DB::table('tbl_modelos_mdl')
                ->where('mdl_deleted', false)
                ->whereNull('mdl_deleted_at')
                ->where('mdl_estatus', 'activo')
                ->orderBy('mdl_nombre')
                ->get(['mdl_id', 'mdl_nombre']),
            'lineas' => DB::table('tbl_lineas_lna')
                ->where('lna_deleted', false)
                ->whereNull('lna_deleted_at')
                ->where('lna_estatus', 'activo')
                ->orderBy('lna_nombre')
                ->get(['lna_id', 'lna_nombre']),
            'categorias' => DB::table('tbl_categorias_ctg')
                ->where('ctg_deleted', false)
                ->whereNull('ctg_deleted_at')
                ->where('ctg_estatus', 'activo')
                ->orderBy('ctg_nombre')
                ->get(['ctg_id', 'ctg_lna_id', 'ctg_nombre']),
            'descripciones' => DB::table('tbl_descripciones_dsc')
                ->where('dsc_deleted', false)
                ->whereNull('dsc_deleted_at')
                ->where('dsc_estatus', 'activo')
                ->orderBy('dsc_nombre')
                ->get(['dsc_id', 'dsc_nombre']),
            'motivos' => DB::table('tbl_motivos_mtv')
                ->where('mtv_deleted', false)
                ->whereNull('mtv_deleted_at')
                ->where('mtv_estatus', 'activo')
                ->orderBy('mtv_nombre')
                ->get(['mtv_id', 'mtv_nombre']),
            'proveedores' => DB::table('tbl_proveedores_prv')
                ->where('prv_deleted', false)
                ->whereNull('prv_deleted_at')
                ->where('prv_estatus', 'activo')
                ->orderBy('prv_nombre_empresa')
                ->get(['prv_id', 'prv_nombre_empresa']),
        ];
    }

    public function opcionesSesionesCaja(int $limite = 300): Collection
    {
        return DB::table('tbl_caja_sesiones_cse as cse')
            ->join('tbl_cajas_caj as caj', 'caj.caj_id', '=', 'cse.cse_caj_id')
            ->join('tbl_sucursales_scl as scl', 'scl.scl_id', '=', 'cse.cse_scl_id')
            ->leftJoin('tbl_usuarios_usr as usr', 'usr.usr_id', '=', 'cse.cse_usr_apertura_id')
            ->orderByDesc('cse.cse_abierta_at')
            ->limit(max(50, min($limite, 1000)))
            ->get([
                'cse.cse_id',
                'cse.cse_abierta_at',
                'cse.cse_estatus',
                'caj.caj_nombre',
                'scl.scl_nombre',
                'usr.usr_nombre as usuario_apertura',
            ]);
    }

    public function buscarProductosBase(string $termino = '', int $pagina = 1, int $porPagina = 20, array $filtros = []): array
    {
        $termino = trim($termino);
        $pagina = max(1, $pagina);
        $porPagina = max(10, min($porPagina, 50));

        $query = Producto::query()
            ->with([
                'marca:mrc_id,mrc_nombre',
                'modelo:mdl_id,mdl_nombre',
                'categoria:ctg_id,ctg_nombre',
                'descripcionCatalogo:dsc_id,dsc_nombre',
            ])
            ->withCount([
                'skus as skus_activos' => fn ($q) => $q
                    ->where('psk_deleted', false)
                    ->whereNull('psk_deleted_at')
                    ->where('psk_estatus', 'activo'),
            ])
            ->where('prd_deleted', false)
            ->whereNull('prd_deleted_at')
            ->where('prd_estatus', 'activo')
            ->when(!empty($filtros['prd_mrc_id']), fn ($q) => $q->where('prd_mrc_id', (int) $filtros['prd_mrc_id']))
            ->when(!empty($filtros['prd_mdl_id']), fn ($q) => $q->where('prd_mdl_id', (int) $filtros['prd_mdl_id']))
            ->when(!empty($filtros['prd_lna_id']), fn ($q) => $q->where('prd_lna_id', (int) $filtros['prd_lna_id']))
            ->when(!empty($filtros['prd_ctg_id']), fn ($q) => $q->where('prd_ctg_id', (int) $filtros['prd_ctg_id']))
            ->when(!empty($filtros['prd_dsc_id']), fn ($q) => $q->where('prd_dsc_id', (int) $filtros['prd_dsc_id']))
            ->when($termino !== '', function ($q) use ($termino): void {
                $q->where(function ($sub) use ($termino): void {
                    $sub->where('prd_codigo', 'like', "%{$termino}%")
                        ->orWhere('prd_nombre', 'like', "%{$termino}%");
                });
            });

        $total = (clone $query)->count();
        $filas = $query
            ->orderBy('prd_nombre')
            ->forPage($pagina, $porPagina)
            ->get(['prd_id', 'prd_codigo', 'prd_nombre', 'prd_tipo', 'prd_mrc_id', 'prd_costo', 'prd_precio_base']);

        $resultados = $filas->map(fn ($producto) => [
            'id' => $producto->prd_id,
            'text' => sprintf(
                '%s - %s (%s, %d SKU)',
                $producto->prd_codigo,
                $producto->prd_nombre,
                $producto->prd_tipo === 'variable' ? 'Variable' : 'Simple',
                (int) $producto->skus_activos
            ),
            'prd_tipo' => $producto->prd_tipo,
            'skus_activos' => (int) $producto->skus_activos,
            'prd_codigo' => $producto->prd_codigo,
            'prd_nombre' => $producto->prd_nombre,
            'prd_costo' => (float) ($producto->prd_costo ?? 0),
            'prd_precio_base' => (float) ($producto->prd_precio_base ?? 0),
            'marca_nombre' => (string) ($producto->marca?->mrc_nombre ?? ''),
            'modelo_nombre' => (string) ($producto->modelo?->mdl_nombre ?? ''),
            'concepto_nombre' => (string) ($producto->categoria?->ctg_nombre ?? ''),
            'descripcion_nombre' => (string) ($producto->descripcionCatalogo?->dsc_nombre ?? ''),
        ])->values()->all();

        return [
            'results' => $resultados,
            'pagination' => [
                'more' => ($pagina * $porPagina) < $total,
            ],
        ];
    }

    public function listarProductosBase(array $filtros = []): Collection
    {
        $limite = (int) ($filtros['limite'] ?? 200);
        $limite = max(50, min($limite, 500));

        return $this->queryProductosBase($filtros, true)
            ->orderBy('prd.prd_nombre')
            ->limit($limite)
            ->get($this->selectProductosBase());
    }

    public function paginarProductosBaseDataTable(
        array $filtros = [],
        int $start = 0,
        int $length = 10,
        int $orderColumn = 1,
        string $orderDir = 'asc'
    ): array {
        $start = max(0, $start);
        $length = max(1, min(100, $length));
        $orderDir = strtolower($orderDir) === 'desc' ? 'desc' : 'asc';

        $columnasOrden = [
            0 => 'prd.prd_codigo',
            1 => 'prd.prd_codigo',
            2 => 'prd.prd_nombre',
            3 => 'prd.prd_tipo',
            4 => 'mrc.mrc_nombre',
            5 => 'mdl.mdl_nombre',
            6 => 'lna.lna_nombre',
            7 => 'ctg.ctg_nombre',
            8 => 'skc.skus_activos',
        ];
        $orderBy = $columnasOrden[$orderColumn] ?? 'prd.prd_nombre';

        $queryBase = $this->queryProductosBase($filtros, false);
        $queryFiltrado = $this->queryProductosBase($filtros, true);

        $total = (clone $queryBase)->count('prd.prd_id');
        $filtrado = (clone $queryFiltrado)->count('prd.prd_id');
        $rows = $queryFiltrado
            ->orderBy($orderBy, $orderDir)
            ->orderBy('prd.prd_id', 'asc')
            ->offset($start)
            ->limit($length)
            ->get($this->selectProductosBase());

        return [
            'recordsTotal' => $total,
            'recordsFiltered' => $filtrado,
            'data' => $rows,
        ];
    }

    private function queryProductosBase(array $filtros = [], bool $conBuscar = true)
    {
        $subSkus = DB::table('tbl_producto_skus_psk')
            ->selectRaw('psk_prd_id, COUNT(*) as skus_activos')
            ->where('psk_deleted', false)
            ->whereNull('psk_deleted_at')
            ->where('psk_estatus', 'activo')
            ->groupBy('psk_prd_id');

        return DB::table('tbl_productos_prd as prd')
            ->leftJoin('tbl_marcas_mrc as mrc', 'mrc.mrc_id', '=', 'prd.prd_mrc_id')
            ->leftJoin('tbl_modelos_mdl as mdl', 'mdl.mdl_id', '=', 'prd.prd_mdl_id')
            ->leftJoin('tbl_lineas_lna as lna', 'lna.lna_id', '=', 'prd.prd_lna_id')
            ->leftJoin('tbl_categorias_ctg as ctg', 'ctg.ctg_id', '=', 'prd.prd_ctg_id')
            ->leftJoin('tbl_descripciones_dsc as dsc', 'dsc.dsc_id', '=', 'prd.prd_dsc_id')
            ->leftJoinSub($subSkus, 'skc', function ($join): void {
                $join->on('skc.psk_prd_id', '=', 'prd.prd_id');
            })
            ->where('prd.prd_deleted', false)
            ->whereNull('prd.prd_deleted_at')
            ->where('prd.prd_estatus', 'activo')
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
                        ->orWhere('dsc.dsc_nombre', 'like', "%{$buscar}%");
                });
            });
    }

    private function selectProductosBase(): array
    {
        return [
            'prd.prd_id',
            'prd.prd_codigo',
            'prd.prd_nombre',
            'prd.prd_tipo',
            'prd.prd_costo',
            'prd.prd_precio_base',
            'prd.prd_mrc_id',
            'prd.prd_mdl_id',
            'prd.prd_lna_id',
            'prd.prd_ctg_id',
            'prd.prd_dsc_id',
            'mrc.mrc_nombre as marca_nombre',
            'mdl.mdl_nombre as modelo_nombre',
            'lna.lna_nombre as linea_nombre',
            'ctg.ctg_nombre as categoria_nombre',
            'dsc.dsc_nombre as descripcion_nombre',
            DB::raw('COALESCE(skc.skus_activos, 0) as skus_activos'),
        ];
    }

    public function buscarSkusActivos(string $termino = '', int $pagina = 1, int $porPagina = 20): array
    {
        $termino = trim($termino);
        $pagina = max(1, $pagina);
        $porPagina = max(10, min($porPagina, 50));

        $query = ProductoSku::query()
            ->join('tbl_productos_prd as prd', 'prd.prd_id', '=', 'tbl_producto_skus_psk.psk_prd_id')
            ->where('tbl_producto_skus_psk.psk_deleted', false)
            ->whereNull('tbl_producto_skus_psk.psk_deleted_at')
            ->where('tbl_producto_skus_psk.psk_estatus', 'activo')
            ->where('prd.prd_deleted', false)
            ->whereNull('prd.prd_deleted_at')
            ->where('prd.prd_estatus', 'activo')
            ->when($termino !== '', function ($q) use ($termino): void {
                $q->where(function ($sub) use ($termino): void {
                    $sub->where('tbl_producto_skus_psk.psk_codigo', 'like', "%{$termino}%")
                        ->orWhere('tbl_producto_skus_psk.psk_nombre', 'like', "%{$termino}%")
                        ->orWhere('prd.prd_nombre', 'like', "%{$termino}%");
                });
            });

        $total = (clone $query)->count();
        $filas = $query
            ->orderBy('tbl_producto_skus_psk.psk_nombre')
            ->forPage($pagina, $porPagina)
            ->get([
                'tbl_producto_skus_psk.psk_id',
                'tbl_producto_skus_psk.psk_prd_id',
                'tbl_producto_skus_psk.psk_codigo',
                'tbl_producto_skus_psk.psk_nombre',
                'prd.prd_nombre',
            ]);

        $resultados = $filas->map(fn ($sku) => [
            'id' => $sku->psk_id,
            'text' => sprintf('%s - %s (%s)', $sku->psk_codigo, $sku->psk_nombre, $sku->prd_nombre),
            'prd_id' => (int) $sku->psk_prd_id,
            'sku_codigo' => (string) $sku->psk_codigo,
            'sku_nombre' => (string) $sku->psk_nombre,
            'producto_nombre' => (string) $sku->prd_nombre,
        ])->values()->all();

        return [
            'results' => $resultados,
            'pagination' => [
                'more' => ($pagina * $porPagina) < $total,
            ],
        ];
    }

    public function matrizCargaInicialProducto(int $productoId, ?int $sucursalId = null): array
    {
        $producto = Producto::query()
            ->with([
                'marca:mrc_id,mrc_nombre',
                'modelo:mdl_id,mdl_nombre',
                'categoria:ctg_id,ctg_nombre',
                'descripcionCatalogo:dsc_id,dsc_nombre',
                'atributos:atr_id,atr_nombre',
                'skus' => fn ($query) => $query
                    ->where('psk_deleted', false)
                    ->whereNull('psk_deleted_at')
                    ->where('psk_estatus', 'activo')
                    ->with([
                        'valoresAtributo:vat_id,vat_atr_id,vat_valor',
                        'valoresAtributo.atributo:atr_id,atr_nombre',
                    ])
                    ->orderBy('psk_nombre'),
            ])
            ->where('prd_deleted', false)
            ->whereNull('prd_deleted_at')
            ->where('prd_estatus', 'activo')
            ->findOrFail($productoId);

        $preferenciaQuery = PreferenciaMatrizProducto::query()
            ->where('pmp_prd_id', $producto->prd_id)
            ->where('pmp_estatus', 'activo');

        $preferencia = null;
        $fuentePreferencia = 'sin_preferencia';
        if (!empty($sucursalId)) {
            $preferencia = (clone $preferenciaQuery)
                ->where('pmp_scl_id', (int) $sucursalId)
                ->orderByDesc('pmp_updated_at')
                ->first();
            if ($preferencia) {
                $fuentePreferencia = 'preferencia_sucursal';
            }
        }

        if (!$preferencia) {
            $preferencia = (clone $preferenciaQuery)
                ->orderByDesc('pmp_updated_at')
                ->first();
            if ($preferencia) {
                $fuentePreferencia = 'preferencia_historica';
            }
        }

        $atributos = $producto->atributos
            ->sortBy('atr_nombre')
            ->values()
            ->map(fn ($item) => [
                'atr_id' => $item->atr_id,
                'atr_nombre' => $item->atr_nombre,
            ])
            ->all();

        $lineas = $producto->skus->map(function ($sku) {
            $mapa = [];
            foreach ($sku->valoresAtributo as $valor) {
                $nombreAtributo = (string) ($valor->atributo?->atr_nombre ?? 'Atributo');
                $mapa[$nombreAtributo] = $valor->vat_valor;
            }

            return [
                'min_psk_id' => $sku->psk_id,
                'psk_codigo' => $sku->psk_codigo,
                'psk_nombre' => $sku->psk_nombre,
                'psk_costo' => (float) ($sku->psk_costo ?? 0),
                'psk_precio' => (float) ($sku->psk_precio ?? 0),
                'atributos' => $mapa,
                'combinacion' => !empty($mapa) ? implode(' / ', array_values($mapa)) : 'Estándar',
            ];
        })->values()->all();

        return [
            'producto' => [
                'prd_id' => $producto->prd_id,
                'prd_codigo' => $producto->prd_codigo,
                'prd_nombre' => $producto->prd_nombre,
                'prd_tipo' => $producto->prd_tipo,
                'marca_nombre' => (string) ($producto->marca?->mrc_nombre ?? ''),
                'modelo_nombre' => (string) ($producto->modelo?->mdl_nombre ?? ''),
                'concepto_nombre' => (string) ($producto->categoria?->ctg_nombre ?? ''),
                'descripcion_nombre' => (string) ($producto->descripcionCatalogo?->dsc_nombre ?? ''),
                'prd_costo' => (float) ($producto->prd_costo ?? 0),
                'prd_precio_base' => (float) ($producto->prd_precio_base ?? 0),
            ],
            'atributos' => $atributos,
            'lineas' => $lineas,
            'dominante_sugerido_atr_id' => $preferencia?->pmp_atr_dominante_id,
            'dominante_sugerido_fuente' => $fuentePreferencia,
        ];
    }

    public function listarExistencias(array $filtros = []): Collection
    {
        return DB::table('tbl_existencias_almacen_exa as exa')
            ->join('tbl_producto_skus_psk as psk', 'psk.psk_id', '=', 'exa.exa_psk_id')
            ->join('tbl_productos_prd as prd', 'prd.prd_id', '=', 'psk.psk_prd_id')
            ->join('tbl_sucursales_scl as scl', 'scl.scl_id', '=', 'exa.exa_scl_id')
            ->join('tbl_almacenes_alm as alm', 'alm.alm_id', '=', 'exa.exa_alm_id')
            ->leftJoin('tbl_minimos_inventario_mni as mni', function ($join): void {
                $join->on('mni.mni_psk_id', '=', 'exa.exa_psk_id')
                    ->on('mni.mni_scl_id', '=', 'exa.exa_scl_id')
                    ->on('mni.mni_alm_id', '=', 'exa.exa_alm_id')
                    ->where('mni.mni_deleted', false)
                    ->whereNull('mni.mni_deleted_at')
                    ->where('mni.mni_estatus', 'activo');
            })
            ->where('exa.exa_deleted', false)
            ->whereNull('exa.exa_deleted_at')
            ->where('exa.exa_estatus', 'activo')
            ->where('psk.psk_deleted', false)
            ->whereNull('psk.psk_deleted_at')
            ->where('prd.prd_deleted', false)
            ->whereNull('prd.prd_deleted_at')
            ->where('scl.scl_deleted', false)
            ->whereNull('scl.scl_deleted_at')
            ->where('alm.alm_deleted', false)
            ->whereNull('alm.alm_deleted_at')
            ->when(!empty($filtros['min_scl_id']), fn ($q) => $q->where('exa.exa_scl_id', (int) $filtros['min_scl_id']))
            ->when(!empty($filtros['min_alm_id']), fn ($q) => $q->where('exa.exa_alm_id', (int) $filtros['min_alm_id']))
            ->when(!empty($filtros['min_psk_id']), fn ($q) => $q->where('exa.exa_psk_id', (int) $filtros['min_psk_id']))
            ->when(!empty($filtros['solo_negativas']), fn ($q) => $q->where('exa.exa_existencia', '<', 0))
            ->when(!empty($filtros['buscar']), function ($q) use ($filtros): void {
                $buscar = trim((string) $filtros['buscar']);
                $q->where(function ($sub) use ($buscar): void {
                    $sub->where('psk.psk_codigo', 'like', "%{$buscar}%")
                        ->orWhere('psk.psk_nombre', 'like', "%{$buscar}%")
                        ->orWhere('prd.prd_nombre', 'like', "%{$buscar}%");
                });
            })
            ->orderBy('scl.scl_nombre')
            ->orderBy('alm.alm_nombre')
            ->orderBy('psk.psk_nombre')
            ->get([
                'exa.exa_id',
                'exa.exa_psk_id',
                'exa.exa_scl_id',
                'exa.exa_alm_id',
                'exa.exa_existencia',
                'psk.psk_codigo',
                'psk.psk_nombre',
                'prd.prd_nombre',
                'scl.scl_nombre',
                'alm.alm_nombre',
                DB::raw('COALESCE(mni.mni_minimo, 0) as minimo_configurado'),
            ]);
    }

    private function queryKardexBase(array $filtros = [])
    {
        return DB::table('tbl_movimientos_inventario_min as min')
            ->join('tbl_tipos_movimiento_inventario_tmi as tmi', 'tmi.tmi_id', '=', 'min.min_tmi_id')
            ->join('tbl_producto_skus_psk as psk', 'psk.psk_id', '=', 'min.min_psk_id')
            ->join('tbl_productos_prd as prd', 'prd.prd_id', '=', 'psk.psk_prd_id')
            ->join('tbl_sucursales_scl as scl', 'scl.scl_id', '=', 'min.min_scl_id')
            ->join('tbl_almacenes_alm as alm', 'alm.alm_id', '=', 'min.min_alm_id')
            ->leftJoin('tbl_usuarios_usr as usr', 'usr.usr_id', '=', 'min.min_created_by_usr_id')
            ->where('min.min_deleted', false)
            ->whereNull('min.min_deleted_at')
            ->when(!empty($filtros['min_scl_id']), fn ($q) => $q->where('min.min_scl_id', (int) $filtros['min_scl_id']))
            ->when(!empty($filtros['min_alm_id']), fn ($q) => $q->where('min.min_alm_id', (int) $filtros['min_alm_id']))
            ->when(!empty($filtros['min_psk_id']), fn ($q) => $q->where('min.min_psk_id', (int) $filtros['min_psk_id']))
            ->when(!empty($filtros['fecha_desde']), fn ($q) => $q->whereDate('min.min_fecha_movimiento', '>=', $filtros['fecha_desde']))
            ->when(!empty($filtros['fecha_hasta']), fn ($q) => $q->whereDate('min.min_fecha_movimiento', '<=', $filtros['fecha_hasta']))
            ->when(!empty($filtros['buscar']), function ($q) use ($filtros): void {
                $buscar = trim((string) $filtros['buscar']);
                $q->where(function ($sub) use ($buscar): void {
                    $sub->where('min.min_folio', 'like', "%{$buscar}%")
                        ->orWhere('min.min_documento_referencia', 'like', "%{$buscar}%")
                        ->orWhere('psk.psk_codigo', 'like', "%{$buscar}%")
                        ->orWhere('psk.psk_nombre', 'like', "%{$buscar}%")
                        ->orWhere('prd.prd_nombre', 'like', "%{$buscar}%");
                });
            });
    }

    private function selectKardex(): array
    {
        return [
            'min.min_id', 'min.min_folio', 'min.min_psk_id', 'min.min_scl_id', 'min.min_alm_id',
            'min.min_documento_tipo', 'min.min_documento_referencia', 'min.min_cantidad', 'min.min_signo',
            'min.min_existencia_antes', 'min.min_existencia_despues', 'min.min_motivo_texto', 'min.min_estatus',
            'min.min_es_reversa', 'min.min_origen_min_id', 'min.min_reversa_de_min_id', 'min.min_fecha_movimiento',
            'psk.psk_codigo', 'psk.psk_nombre', 'prd.prd_nombre', 'prd.prd_tipo',
            'scl.scl_nombre', 'alm.alm_nombre', 'tmi.tmi_nombre', 'tmi.tmi_clase', 'usr.usr_nombre as usuario_nombre',
        ];
    }

    public function listarKardex(array $filtros = []): Collection
    {
        return $this->queryKardexBase($filtros)
            ->orderByDesc('min.min_fecha_movimiento')
            ->orderByDesc('min.min_id')
            ->limit(1000)
            ->get($this->selectKardex());
    }

    public function paginarKardex(
        array $filtros = [],
        int $start = 0,
        int $length = 50,
        int $orderColumn = 0,
        string $orderDir = 'desc',
    ): array {
        $start = max(0, $start);
        $length = max(1, min(250, $length));
        $orderDir = strtolower($orderDir) === 'asc' ? 'asc' : 'desc';

        $columnasOrden = [
            0 => 'min.min_fecha_movimiento',
            1 => 'min.min_folio',
            2 => 'psk.psk_codigo',
            3 => 'prd.prd_nombre',
            4 => 'scl.scl_nombre',
            5 => 'alm.alm_nombre',
            6 => 'tmi.tmi_nombre',
            7 => 'min.min_cantidad',
            9 => 'min.min_existencia_despues',
            10 => 'usr.usr_nombre',
            11 => 'min.min_estatus',
        ];
        $orderBy = $columnasOrden[$orderColumn] ?? 'min.min_fecha_movimiento';

        $total = $this->queryKardexBase($filtros)->count();

        $data = $this->queryKardexBase($filtros)
            ->orderBy($orderBy, $orderDir)
            ->orderByDesc('min.min_id')
            ->offset($start)
            ->limit($length)
            ->get($this->selectKardex());

        return [
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $data,
        ];
    }

    /**
     * Listado paginado de salidas (movimientos de ajuste manual / merma con signo negativo).
     */
    public function paginarSalidas(
        array $filtros = [],
        int $start = 0,
        int $length = 50,
        int $orderColumn = 0,
        string $orderDir = 'desc',
    ): array {
        $start = max(0, $start);
        $length = max(1, min(250, $length));
        $orderDir = strtolower($orderDir) === 'asc' ? 'asc' : 'desc';

        $base = fn () => $this->queryKardexBase($filtros)
            ->whereIn('min.min_documento_tipo', ['ajuste_manual', 'merma'])
            ->where('min.min_signo', '<', 0)
            ->when(!empty($filtros['tipo']), fn ($q) => $q->where('min.min_documento_tipo', (string) $filtros['tipo']));

        $columnasOrden = [
            0 => 'min.min_fecha_movimiento',
            1 => 'min.min_folio',
            2 => 'psk.psk_codigo',
            3 => 'prd.prd_nombre',
            4 => 'scl.scl_nombre',
            5 => 'alm.alm_nombre',
            6 => 'min.min_documento_tipo',
            7 => 'min.min_cantidad',
            8 => 'min.min_existencia_despues',
            9 => 'usr.usr_nombre',
            10 => 'min.min_estatus',
        ];
        $orderBy = $columnasOrden[$orderColumn] ?? 'min.min_fecha_movimiento';

        $total = $base()->count();

        $data = $base()
            ->orderBy($orderBy, $orderDir)
            ->orderByDesc('min.min_id')
            ->offset($start)
            ->limit($length)
            ->get($this->selectKardex());

        return [
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $data,
        ];
    }

    public function obtenerKardexDetalleSku(int $skuId, array $filtros = []): array
    {
        $sku = ProductoSku::query()
            ->with([
                'producto:prd_id,prd_codigo,prd_nombre,prd_umd_id',
                'producto.unidad:umd_id,umd_nombre,umd_codigo',
                'valoresAtributo' => fn ($query) => $query
                    ->where('vat_deleted', false)
                    ->whereNull('vat_deleted_at')
                    ->where('vat_estatus', 'activo')
                    ->with(['atributo:atr_id,atr_nombre,atr_clave']),
            ])
            ->where('psk_deleted', false)
            ->whereNull('psk_deleted_at')
            ->where('psk_estatus', 'activo')
            ->findOrFail($skuId);

        $periodo = (string) ($filtros['periodo'] ?? 'este_mes');
        [$fechaInicio, $fechaFin] = $this->resolverPeriodoKardexDetalle($periodo, $filtros);

        $movimientos = $this->listarKardex([
            'min_psk_id' => $skuId,
            'fecha_desde' => $fechaInicio->toDateString(),
            'fecha_hasta' => $fechaFin->toDateString(),
        ]);

        $existenciaActual = round((float) ExistenciaAlmacen::query()
            ->where('exa_psk_id', $skuId)
            ->where('exa_deleted', false)
            ->whereNull('exa_deleted_at')
            ->where('exa_estatus', 'activo')
            ->sum('exa_existencia'), 2);

        $atributos = collect($sku->valoresAtributo ?? []);
        $talla = (string) optional($atributos->first(fn ($valor) => $this->esAtributoTalla(
            (string) ($valor->atributo?->atr_nombre ?? ''),
            (string) ($valor->atributo?->atr_clave ?? '')
        )))->vat_valor ?: 'Base';
        $color = (string) optional($atributos->first(fn ($valor) => $this->esAtributoColor(
            (string) ($valor->atributo?->atr_nombre ?? ''),
            (string) ($valor->atributo?->atr_clave ?? '')
        )))->vat_valor;

        $timeline = $movimientos
            ->groupBy(fn ($movimiento) => Carbon::parse($movimiento->min_fecha_movimiento)->format('Y-m'))
            ->map(function (Collection $items, string $mesKey): array {
                $mes = Carbon::createFromFormat('Y-m', $mesKey);

                return [
                    'mes_key' => $mesKey,
                    'mes_label' => Str::title($mes->translatedFormat('F Y')),
                    'total_movimientos' => $items->count(),
                    'entradas' => round((float) $items->filter(fn ($movimiento) => (float) $movimiento->min_signo > 0)->sum('min_cantidad'), 2),
                    'salidas' => round((float) $items->filter(fn ($movimiento) => (float) $movimiento->min_signo < 0)->sum('min_cantidad'), 2),
                    'movimientos' => $items->values(),
                ];
            })
            ->values();

        return [
            'sku' => $sku,
            'producto' => $sku->producto,
            'periodo' => $periodo,
            'fecha_inicio' => $fechaInicio->toDateString(),
            'fecha_fin' => $fechaFin->toDateString(),
            'existencia_actual' => $existenciaActual,
            'fecha_consulta' => now(),
            'talla' => $talla,
            'color' => $color,
            'unidad' => (string) ($sku->producto?->unidad?->umd_codigo ?? $sku->producto?->unidad?->umd_nombre ?? 'PZA'),
            'timeline' => $timeline,
            'movimientos_total' => $movimientos->count(),
            'back_filters' => [
                'prd_mrc_id' => $filtros['back_prd_mrc_id'] ?? null,
                'prd_mdl_id' => $filtros['back_prd_mdl_id'] ?? null,
                'prd_lna_id' => $filtros['back_prd_lna_id'] ?? null,
                'prd_ctg_id' => $filtros['back_prd_ctg_id'] ?? null,
                'prd_dsc_id' => $filtros['back_prd_dsc_id'] ?? null,
                'prd_id' => $filtros['back_prd_id'] ?? null,
                'prd_text' => $filtros['back_prd_text'] ?? null,
                'buscar' => $filtros['back_buscar'] ?? null,
            ],
        ];
    }

    private function queryNegativosPorSesionCajaBase(array $filtros = [])
    {
        return DB::table('tbl_movimientos_inventario_min as min')
            ->join('tbl_pos_ventas_psv as psv', 'psv.psv_folio', '=', 'min.min_documento_referencia')
            ->join('tbl_caja_sesiones_cse as cse', 'cse.cse_id', '=', 'psv.psv_cse_id')
            ->join('tbl_cajas_caj as caj', 'caj.caj_id', '=', 'psv.psv_caj_id')
            ->join('tbl_producto_skus_psk as psk', 'psk.psk_id', '=', 'min.min_psk_id')
            ->join('tbl_productos_prd as prd', 'prd.prd_id', '=', 'psk.psk_prd_id')
            ->join('tbl_sucursales_scl as scl', 'scl.scl_id', '=', 'min.min_scl_id')
            ->join('tbl_almacenes_alm as alm', 'alm.alm_id', '=', 'min.min_alm_id')
            ->leftJoin('tbl_usuarios_usr as usr_ap', 'usr_ap.usr_id', '=', 'cse.cse_usr_apertura_id')
            ->leftJoin('tbl_usuarios_usr as usr_vta', 'usr_vta.usr_id', '=', 'psv.psv_usr_id')
            ->where('min.min_deleted', false)
            ->whereNull('min.min_deleted_at')
            ->where('min.min_estatus', 'activo')
            ->where('min.min_documento_tipo', 'venta_pos')
            ->where('min.min_signo', -1)
            ->where('min.min_existencia_despues', '<', 0)
            ->when(!empty($filtros['cse_id']), fn ($q) => $q->where('cse.cse_id', (int) $filtros['cse_id']))
            ->when(!empty($filtros['min_scl_id']), fn ($q) => $q->where('min.min_scl_id', (int) $filtros['min_scl_id']))
            ->when(!empty($filtros['min_alm_id']), fn ($q) => $q->where('min.min_alm_id', (int) $filtros['min_alm_id']))
            ->when(!empty($filtros['prd_id']), fn ($q) => $q->where('prd.prd_id', (int) $filtros['prd_id']))
            ->when(!empty($filtros['prd_mrc_id']), fn ($q) => $q->where('prd.prd_mrc_id', (int) $filtros['prd_mrc_id']))
            ->when(!empty($filtros['prd_mdl_id']), fn ($q) => $q->where('prd.prd_mdl_id', (int) $filtros['prd_mdl_id']))
            ->when(!empty($filtros['prd_lna_id']), fn ($q) => $q->where('prd.prd_lna_id', (int) $filtros['prd_lna_id']))
            ->when(!empty($filtros['prd_ctg_id']), fn ($q) => $q->where('prd.prd_ctg_id', (int) $filtros['prd_ctg_id']))
            ->when(!empty($filtros['prd_dsc_id']), fn ($q) => $q->where('prd.prd_dsc_id', (int) $filtros['prd_dsc_id']))
            ->when(!empty($filtros['fecha_desde']), fn ($q) => $q->whereDate('min.min_fecha_movimiento', '>=', $filtros['fecha_desde']))
            ->when(!empty($filtros['fecha_hasta']), fn ($q) => $q->whereDate('min.min_fecha_movimiento', '<=', $filtros['fecha_hasta']))
            ->when(!empty($filtros['buscar']), function ($q) use ($filtros): void {
                $buscar = trim((string) $filtros['buscar']);
                $q->where(function ($sub) use ($buscar): void {
                    $sub->where('psv.psv_folio', 'like', "%{$buscar}%")
                        ->orWhere('psk.psk_codigo', 'like', "%{$buscar}%")
                        ->orWhere('psk.psk_nombre', 'like', "%{$buscar}%")
                        ->orWhere('prd.prd_nombre', 'like', "%{$buscar}%")
                        ->orWhere('caj.caj_nombre', 'like', "%{$buscar}%")
                        ->orWhere('usr_vta.usr_nombre', 'like', "%{$buscar}%");
                });
            });
    }

    private function selectNegativosPorSesionCaja(): array
    {
        return [
            'min.min_id',
            'min.min_folio',
            'min.min_fecha_movimiento',
            'min.min_documento_referencia',
            'min.min_cantidad',
            'min.min_existencia_antes',
            'min.min_existencia_despues',
            'cse.cse_id',
            'cse.cse_abierta_at',
            'cse.cse_cerrada_at',
            'cse.cse_estatus',
            'caj.caj_nombre',
            'scl.scl_nombre',
            'alm.alm_nombre',
            'psk.psk_codigo',
            'psk.psk_nombre',
            'prd.prd_nombre',
            'usr_ap.usr_nombre as usuario_apertura',
            'usr_vta.usr_nombre as usuario_venta',
            'psv.psv_folio',
        ];
    }

    public function listarNegativosPorSesionCaja(array $filtros = []): Collection
    {
        return $this->queryNegativosPorSesionCajaBase($filtros)
            ->orderByDesc('min.min_fecha_movimiento')
            ->orderByDesc('min.min_id')
            ->limit(1500)
            ->get($this->selectNegativosPorSesionCaja());
    }

    /**
     * Paginación server-side (LIMIT/OFFSET + COUNT) para el reporte de negativos
     * por sesión de caja. Mantiene los mismos filtros que el listado completo.
     */
    public function paginarNegativosPorSesionCaja(
        array $filtros = [],
        int $start = 0,
        int $length = 50,
        int $orderColumn = 0,
        string $orderDir = 'desc',
    ): array {
        $start = max(0, $start);
        $length = max(1, min(250, $length));
        $orderDir = strtolower($orderDir) === 'asc' ? 'asc' : 'desc';

        $columnasOrden = [
            0 => 'min.min_fecha_movimiento',
            1 => 'cse.cse_id',
            2 => 'caj.caj_nombre',
            3 => 'scl.scl_nombre',
            4 => 'alm.alm_nombre',
            5 => 'psv.psv_folio',
            6 => 'psk.psk_codigo',
            7 => 'psk.psk_nombre',
            8 => 'min.min_cantidad',
            9 => 'min.min_existencia_antes',
            10 => 'min.min_existencia_despues',
            11 => 'usr_vta.usr_nombre',
        ];
        $orderBy = $columnasOrden[$orderColumn] ?? 'min.min_fecha_movimiento';

        $total = $this->queryNegativosPorSesionCajaBase($filtros)->count();

        $data = $this->queryNegativosPorSesionCajaBase($filtros)
            ->orderBy($orderBy, $orderDir)
            ->orderByDesc('min.min_id')
            ->offset($start)
            ->limit($length)
            ->get($this->selectNegativosPorSesionCaja());

        return [
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $data,
        ];
    }

    private function queryBajoMinimoBase(array $filtros = [])
    {
        return DB::table('tbl_minimos_inventario_mni as mni')
            ->join('tbl_existencias_almacen_exa as exa', function ($join): void {
                $join->on('exa.exa_psk_id', '=', 'mni.mni_psk_id')
                    ->on('exa.exa_scl_id', '=', 'mni.mni_scl_id')
                    ->on('exa.exa_alm_id', '=', 'mni.mni_alm_id')
                    ->where('exa.exa_deleted', false)
                    ->whereNull('exa.exa_deleted_at')
                    ->where('exa.exa_estatus', 'activo');
            })
            ->join('tbl_producto_skus_psk as psk', 'psk.psk_id', '=', 'mni.mni_psk_id')
            ->join('tbl_productos_prd as prd', 'prd.prd_id', '=', 'psk.psk_prd_id')
            ->join('tbl_sucursales_scl as scl', 'scl.scl_id', '=', 'mni.mni_scl_id')
            ->join('tbl_almacenes_alm as alm', 'alm.alm_id', '=', 'mni.mni_alm_id')
            ->where('mni.mni_deleted', false)
            ->whereNull('mni.mni_deleted_at')
            ->where('mni.mni_estatus', 'activo')
            ->whereRaw('exa.exa_existencia < mni.mni_minimo')
            ->when(!empty($filtros['mni_scl_id']), fn ($q) => $q->where('mni.mni_scl_id', (int) $filtros['mni_scl_id']))
            ->when(!empty($filtros['mni_alm_id']), fn ($q) => $q->where('mni.mni_alm_id', (int) $filtros['mni_alm_id']))
            ->when(!empty($filtros['buscar']), function ($q) use ($filtros): void {
                $buscar = trim((string) $filtros['buscar']);
                $q->where(function ($sub) use ($buscar): void {
                    $sub->where('psk.psk_codigo', 'like', "%{$buscar}%")
                        ->orWhere('psk.psk_nombre', 'like', "%{$buscar}%")
                        ->orWhere('prd.prd_nombre', 'like', "%{$buscar}%");
                });
            });
    }

    private function selectBajoMinimo(): array
    {
        return [
            'mni.mni_id', 'mni.mni_minimo', 'exa.exa_existencia',
            'psk.psk_id', 'psk.psk_codigo', 'psk.psk_nombre', 'prd.prd_nombre',
            'scl.scl_nombre', 'alm.alm_nombre',
        ];
    }

    public function listarBajoMinimo(array $filtros = []): Collection
    {
        return $this->queryBajoMinimoBase($filtros)
            ->orderBy('scl.scl_nombre')
            ->orderBy('alm.alm_nombre')
            ->orderBy('psk.psk_nombre')
            ->get($this->selectBajoMinimo());
    }

    public function paginarBajoMinimo(
        array $filtros = [],
        int $start = 0,
        int $length = 50,
        int $orderColumn = 0,
        string $orderDir = 'asc',
    ): array {
        $start = max(0, $start);
        $length = max(1, min(250, $length));
        $orderDir = strtolower($orderDir) === 'desc' ? 'desc' : 'asc';

        $columnasOrden = [
            0 => 'psk.psk_codigo',
            1 => 'prd.prd_nombre',
            2 => 'scl.scl_nombre',
            3 => 'alm.alm_nombre',
            4 => 'exa.exa_existencia',
            5 => 'mni.mni_minimo',
        ];
        $orderBy = $columnasOrden[$orderColumn] ?? 'psk.psk_codigo';

        $total = $this->queryBajoMinimoBase($filtros)->count();

        $data = $this->queryBajoMinimoBase($filtros)
            ->orderBy($orderBy, $orderDir)
            ->orderBy('psk.psk_nombre')
            ->offset($start)
            ->limit($length)
            ->get($this->selectBajoMinimo());

        return [
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $data,
        ];
    }

    public function listarReportesEntradasPdf(array $filtros = []): Collection
    {
        $registros = DB::table('tbl_bitacora_acciones_bac as bac')
            ->leftJoin('tbl_usuarios_usr as usr', 'usr.usr_id', '=', 'bac.bac_usr_id')
            ->where('bac.bac_accion', 'inventario_base.reporte_entradas_pdf')
            ->when(!empty($filtros['fecha_desde']), fn ($q) => $q->whereDate('bac.bac_created_at', '>=', $filtros['fecha_desde']))
            ->when(!empty($filtros['fecha_hasta']), fn ($q) => $q->whereDate('bac.bac_created_at', '<=', $filtros['fecha_hasta']))
            ->orderByDesc('bac.bac_created_at')
            ->limit(300)
            ->get([
                'bac.bac_id',
                'bac.bac_scl_id',
                'bac.bac_accion',
                'bac.bac_entidad',
                'bac.bac_entidad_id',
                'bac.bac_payload',
                'bac.bac_created_at',
                'usr.usr_nombre as usuario_nombre',
            ]);

        $sucursalIds = [];
        $almacenIds = [];
        foreach ($registros as $registro) {
            $payload = json_decode((string) ($registro->bac_payload ?? '{}'), true);
            if (is_array($payload)) {
                if (!empty($payload['sucursal_id'])) {
                    $sucursalIds[] = (int) $payload['sucursal_id'];
                }
                if (!empty($payload['almacen_id'])) {
                    $almacenIds[] = (int) $payload['almacen_id'];
                }
            }
        }

        $sucursales = Sucursal::query()
            ->whereIn('scl_id', array_values(array_unique($sucursalIds)))
            ->pluck('scl_nombre', 'scl_id');
        $almacenes = Almacen::query()
            ->whereIn('alm_id', array_values(array_unique($almacenIds)))
            ->pluck('alm_nombre', 'alm_id');

        return $registros->map(function ($registro) use ($sucursales, $almacenes) {
            $payload = json_decode((string) ($registro->bac_payload ?? '{}'), true);
            if (!is_array($payload)) {
                $payload = [];
            }

            $folios = collect($payload['folios'] ?? [])->map(fn ($f) => trim((string) $f))->filter()->values();
            $sucursalId = (int) ($payload['sucursal_id'] ?? 0);
            $almacenId = (int) ($payload['almacen_id'] ?? 0);

            return [
                'reporte_id' => (int) $registro->bac_id,
                'fecha' => (string) $registro->bac_created_at,
                'usuario_nombre' => (string) ($registro->usuario_nombre ?? 'N/D'),
                'sucursal_nombre' => (string) ($sucursales[$sucursalId] ?? 'N/D'),
                'almacen_nombre' => (string) ($almacenes[$almacenId] ?? 'N/D'),
                'tipo_entrada' => (string) ($payload['tipo_entrada'] ?? 'entrada_normal'),
                'total_folios' => (int) ($payload['total_folios'] ?? $folios->count()),
                'folios_texto' => $folios->implode(', '),
                'total_documento' => (float) ($payload['total_documento'] ?? 0),
            ];
        })->values();
    }

    public function listarRecepcionesMercancia(array $filtros = []): Collection
    {
        $detalleSub = DB::table('tbl_recepcion_mercancia_detalle_rmd as rmd')
            ->selectRaw('rmd_rme_id, COUNT(*) as total_lineas, SUM(rmd_cantidad) as total_articulos, SUM(COALESCE(rmd_cantidad, 0) * COALESCE(rmd_precio_unitario, 0)) as total_importe')
            ->where('rmd_deleted', false)
            ->whereNull('rmd_deleted_at')
            ->groupBy('rmd_rme_id');

        return DB::table('tbl_recepciones_mercancia_rme as rme')
            ->leftJoinSub($detalleSub, 'det', fn ($join) => $join->on('det.rmd_rme_id', '=', 'rme.rme_id'))
            ->leftJoin('tbl_sucursales_scl as scl', 'scl.scl_id', '=', 'rme.rme_scl_id')
            ->leftJoin('tbl_almacenes_alm as alm', 'alm.alm_id', '=', 'rme.rme_alm_id')
            ->leftJoin('tbl_proveedores_prv as prv', 'prv.prv_id', '=', 'rme.rme_prv_id')
            ->leftJoin('tbl_usuarios_usr as usr_creo', 'usr_creo.usr_id', '=', 'rme.rme_created_by_usr_id')
            ->leftJoin('tbl_usuarios_usr as usr_conf', 'usr_conf.usr_id', '=', 'rme.rme_confirmado_by_usr_id')
            ->where('rme.rme_deleted', false)
            ->whereNull('rme.rme_deleted_at')
            ->when(!empty($filtros['estado']), fn ($q) => $q->where('rme.rme_estado', (string) $filtros['estado']))
            ->when(!empty($filtros['fecha_desde']), fn ($q) => $q->whereDate('rme.rme_fecha_captura', '>=', $filtros['fecha_desde']))
            ->when(!empty($filtros['fecha_hasta']), fn ($q) => $q->whereDate('rme.rme_fecha_captura', '<=', $filtros['fecha_hasta']))
            ->orderByDesc('rme.rme_created_at')
            ->limit(300)
            ->get([
                'rme.rme_id',
                'rme.rme_folio',
                'rme.rme_estado',
                'rme.rme_documento_tipo',
                'rme.rme_documento_referencia',
                'rme.rme_fecha_captura',
                'rme.rme_confirmado_at',
                'rme.rme_cancelado_at',
                'rme.rme_cancelacion_motivo',
                'scl.scl_nombre as sucursal_nombre',
                'alm.alm_nombre as almacen_nombre',
                'prv.prv_nombre_empresa as proveedor_nombre',
                'usr_creo.usr_nombre as usuario_creo',
                'usr_conf.usr_nombre as usuario_confirmo',
                DB::raw('COALESCE(det.total_lineas, 0) as total_lineas'),
                DB::raw('COALESCE(det.total_articulos, 0) as total_articulos'),
                DB::raw('COALESCE(det.total_importe, 0) as total_importe'),
            ]);
    }

    /**
     * Paginación server-side (LIMIT/OFFSET + COUNT) del listado de recepciones.
     */
    public function paginarRecepcionesMercancia(
        array $filtros = [],
        int $start = 0,
        int $length = 50,
        int $orderColumn = 0,
        string $orderDir = 'desc',
    ): array {
        $start = max(0, $start);
        $length = max(1, min(250, $length));
        $orderDir = strtolower($orderDir) === 'asc' ? 'asc' : 'desc';

        $base = fn () => DB::table('tbl_recepciones_mercancia_rme as rme')
            ->where('rme.rme_deleted', false)
            ->whereNull('rme.rme_deleted_at')
            ->when(!empty($filtros['estado']), fn ($q) => $q->where('rme.rme_estado', (string) $filtros['estado']))
            ->when(!empty($filtros['fecha_desde']), fn ($q) => $q->whereDate('rme.rme_fecha_captura', '>=', $filtros['fecha_desde']))
            ->when(!empty($filtros['fecha_hasta']), fn ($q) => $q->whereDate('rme.rme_fecha_captura', '<=', $filtros['fecha_hasta']))
            ->when(!empty($filtros['buscar']), function ($q) use ($filtros): void {
                $buscar = trim((string) $filtros['buscar']);
                $q->where(function ($sub) use ($buscar): void {
                    $sub->where('rme.rme_folio', 'like', "%{$buscar}%")
                        ->orWhere('rme.rme_documento_referencia', 'like', "%{$buscar}%")
                        ->orWhereExists(function ($exists) use ($buscar): void {
                            $exists->selectRaw('1')
                                ->from('tbl_proveedores_prv as prvx')
                                ->whereColumn('prvx.prv_id', 'rme.rme_prv_id')
                                ->where('prvx.prv_nombre_empresa', 'like', "%{$buscar}%");
                        });
                });
            });

        $columnasOrden = [
            0 => 'rme.rme_fecha_captura',
            1 => 'rme.rme_folio',
            2 => 'rme.rme_estado',
        ];
        $orderBy = $columnasOrden[$orderColumn] ?? 'rme.rme_fecha_captura';

        $total = $base()->count();

        $detalleSub = DB::table('tbl_recepcion_mercancia_detalle_rmd as rmd')
            ->selectRaw('rmd_rme_id, COUNT(*) as total_lineas, SUM(rmd_cantidad) as total_articulos, SUM(COALESCE(rmd_cantidad, 0) * COALESCE(rmd_precio_unitario, 0)) as total_importe')
            ->where('rmd_deleted', false)
            ->whereNull('rmd_deleted_at')
            ->groupBy('rmd_rme_id');

        $data = $base()
            ->leftJoinSub($detalleSub, 'det', fn ($join) => $join->on('det.rmd_rme_id', '=', 'rme.rme_id'))
            ->leftJoin('tbl_sucursales_scl as scl', 'scl.scl_id', '=', 'rme.rme_scl_id')
            ->leftJoin('tbl_almacenes_alm as alm', 'alm.alm_id', '=', 'rme.rme_alm_id')
            ->leftJoin('tbl_proveedores_prv as prv', 'prv.prv_id', '=', 'rme.rme_prv_id')
            ->leftJoin('tbl_usuarios_usr as usr_creo', 'usr_creo.usr_id', '=', 'rme.rme_created_by_usr_id')
            ->leftJoin('tbl_usuarios_usr as usr_conf', 'usr_conf.usr_id', '=', 'rme.rme_confirmado_by_usr_id')
            ->orderBy($orderBy, $orderDir)
            ->orderByDesc('rme.rme_id')
            ->offset($start)
            ->limit($length)
            ->get([
                'rme.rme_id',
                'rme.rme_folio',
                'rme.rme_estado',
                'rme.rme_documento_tipo',
                'rme.rme_documento_referencia',
                'rme.rme_fecha_captura',
                'rme.rme_confirmado_at',
                'rme.rme_cancelado_at',
                'rme.rme_cancelacion_motivo',
                'scl.scl_nombre as sucursal_nombre',
                'alm.alm_nombre as almacen_nombre',
                'prv.prv_nombre_empresa as proveedor_nombre',
                'usr_creo.usr_nombre as usuario_creo',
                'usr_conf.usr_nombre as usuario_confirmo',
                DB::raw('COALESCE(det.total_lineas, 0) as total_lineas'),
                DB::raw('COALESCE(det.total_articulos, 0) as total_articulos'),
                DB::raw('COALESCE(det.total_importe, 0) as total_importe'),
            ]);

        return [
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $data,
        ];
    }

    public function obtenerRecepcionMercancia(int $recepcionId): array
    {
        $recepcion = RecepcionMercancia::query()
            ->with([
                'sucursal:scl_id,scl_nombre',
                'almacen:alm_id,alm_nombre',
                'proveedor:prv_id,prv_nombre_empresa',
                'detalle' => fn ($query) => $query
                    ->with([
                        'sku:psk_id,psk_prd_id,psk_codigo,psk_nombre',
                        'sku.producto:prd_id,prd_codigo,prd_nombre',
                    ])
                    ->orderBy('rmd_id'),
            ])
            ->findOrFail($recepcionId);

        $payload = $recepcion->rme_payload;
        if (!is_array($payload)) {
            $payload = [];
        }

        return [
            'rme_id' => (int) $recepcion->rme_id,
            'rme_folio' => (string) $recepcion->rme_folio,
            'rme_estado' => (string) $recepcion->rme_estado,
            'min_scl_id' => $recepcion->rme_scl_id ? (int) $recepcion->rme_scl_id : null,
            'min_alm_id' => $recepcion->rme_alm_id ? (int) $recepcion->rme_alm_id : null,
            'min_prv_id' => $recepcion->rme_prv_id ? (int) $recepcion->rme_prv_id : null,
            'dominante_atr_id' => $recepcion->rme_dominante_atr_id ? (int) $recepcion->rme_dominante_atr_id : null,
            'min_documento_tipo' => $recepcion->rme_documento_tipo,
            'min_documento_referencia' => $recepcion->rme_documento_referencia,
            'min_fecha_movimiento' => optional($recepcion->rme_fecha_captura)?->format('Y-m-d\TH:i'),
            'min_fecha_emision' => optional($recepcion->rme_fecha_emision)?->format('Y-m-d\TH:i'),
            'min_motivo_texto' => $recepcion->rme_motivo_texto,
            'min_observaciones' => $recepcion->rme_observaciones,
            'min_descuento_tipo' => $recepcion->rme_descuento_tipo ?: 'ninguno',
            'min_descuento_valor' => (float) ($recepcion->rme_descuento_valor ?? 0),
            'min_flete_total' => (float) ($recepcion->rme_flete_total ?? 0),
            'min_iva_porcentaje' => (float) ($recepcion->rme_iva_porcentaje ?? 0),
            'payload' => $payload,
            'lineas' => $recepcion->detalle->map(function (RecepcionMercanciaDetalle $detalle) {
                return [
                    'prd_id' => $detalle->rmd_prd_id ? (int) $detalle->rmd_prd_id : (int) ($detalle->sku?->psk_prd_id ?? 0),
                    'min_psk_id' => (int) $detalle->rmd_psk_id,
                    'min_cantidad' => (float) $detalle->rmd_cantidad,
                    'min_precio_unitario' => (float) ($detalle->rmd_precio_unitario ?? 0),
                    'sku_codigo' => (string) ($detalle->sku?->psk_codigo ?? ''),
                    'sku_nombre' => (string) ($detalle->sku?->psk_nombre ?? ''),
                    'producto_nombre' => (string) ($detalle->sku?->producto?->prd_nombre ?? ''),
                ];
            })->values()->all(),
            'resumen' => [
                'total_lineas' => (int) $recepcion->detalle->count(),
                'total_articulos' => (float) $recepcion->detalle->sum('rmd_cantidad'),
                'total_importe' => (float) $recepcion->detalle->sum(fn (RecepcionMercanciaDetalle $detalle) => ((float) $detalle->rmd_cantidad) * ((float) ($detalle->rmd_precio_unitario ?? 0))),
                'sucursal_nombre' => (string) ($recepcion->sucursal?->scl_nombre ?? 'N/D'),
                'almacen_nombre' => (string) ($recepcion->almacen?->alm_nombre ?? 'N/D'),
                'proveedor_nombre' => (string) ($recepcion->proveedor?->prv_nombre_empresa ?? 'N/D'),
            ],
        ];
    }

    public function descargarReporteRecepcionMercanciaPdf(Request $request, int $recepcionId): array
    {
        $recepcion = RecepcionMercancia::query()->findOrFail($recepcionId);

        $folios = MovimientoInventario::query()
            ->where('min_rme_id', $recepcionId)
            ->where('min_deleted', false)
            ->whereNull('min_deleted_at')
            ->where('min_estatus', 'activo')
            ->where('min_signo', '>', 0)
            ->orderBy('min_id')
            ->pluck('min_folio')
            ->map(fn ($folio) => trim((string) $folio))
            ->filter()
            ->values()
            ->all();

        if (empty($folios)) {
            throw ValidationException::withMessages([
                'recepcion' => 'La recepción no tiene movimientos definitivos para generar reporte.',
            ]);
        }

        return $this->generarReporteEntradasSeleccionadasPdf($request, [
            'folios' => $folios,
            'atr_dominante_id' => (int) ($recepcion->rme_dominante_atr_id ?? 0),
            'min_scl_id' => (int) ($recepcion->rme_scl_id ?? 0),
            'min_alm_id' => (int) ($recepcion->rme_alm_id ?? 0),
            'min_documento_tipo' => (string) ($recepcion->rme_documento_tipo ?? 'entrada_normal'),
            'min_documento_referencia' => (string) ($recepcion->rme_documento_referencia ?? ''),
            'min_motivo_texto' => (string) ($recepcion->rme_motivo_texto ?? ''),
            'min_observaciones' => (string) ($recepcion->rme_observaciones ?? ''),
            'min_fecha_movimiento' => (string) optional($recepcion->rme_fecha_captura)?->toDateTimeString(),
            'min_fecha_emision' => (string) optional($recepcion->rme_fecha_emision)?->toDateTimeString(),
            'min_prv_id' => (int) ($recepcion->rme_prv_id ?? 0),
            'min_descuento_tipo' => (string) ($recepcion->rme_descuento_tipo ?? 'ninguno'),
            'min_descuento_valor' => (float) ($recepcion->rme_descuento_valor ?? 0),
            'min_flete_total' => (float) ($recepcion->rme_flete_total ?? 0),
            'min_iva_porcentaje' => (float) ($recepcion->rme_iva_porcentaje ?? 0),
        ], false);
    }

    public function descargarReporteRecepcionMercanciaTermicoPdf(Request $request, int $recepcionId): array
    {
        $recepcion = RecepcionMercancia::query()
            ->with(['sucursal:scl_id,scl_nombre', 'almacen:alm_id,alm_nombre', 'proveedor:prv_id,prv_nombre_empresa'])
            ->findOrFail($recepcionId);

        $movimientos = MovimientoInventario::query()
            ->with([
                'sku:psk_id,psk_prd_id,psk_codigo,psk_nombre,psk_precio,psk_costo',
                'sku.producto:prd_id,prd_codigo,prd_nombre,prd_precio_base,prd_costo',
                'sku.valoresAtributo' => fn ($q) => $q
                    ->where('vat_deleted', false)
                    ->whereNull('vat_deleted_at')
                    ->where('vat_estatus', 'activo')
                    ->with(['atributo:atr_id,atr_nombre'])
                    ->orderBy('vat_valor'),
            ])
            ->where('min_rme_id', $recepcionId)
            ->where('min_deleted', false)
            ->whereNull('min_deleted_at')
            ->where('min_estatus', 'activo')
            ->where('min_signo', '>', 0)
            ->orderBy('min_id')
            ->get();

        if ($movimientos->isEmpty()) {
            throw ValidationException::withMessages([
                'recepcion' => 'La recepcion no tiene movimientos definitivos para generar reporte termico.',
            ]);
        }

        $tipoEntrada = (string) ($recepcion->rme_documento_tipo ?? $movimientos->first()->min_documento_tipo ?? 'entrada_normal');
        $tipoEntradaLabel = match ($tipoEntrada) {
            'inventario_inicial' => 'Entrada normal (inventario inicial)',
            'entrada_normal' => 'Entrada normal',
            'compra_remision' => 'Compra con remision',
            'compra_factura' => 'Compra con factura',
            default => Str::headline(str_replace('_', ' ', $tipoEntrada)),
        };

        $subtotalMonetario = round((float) $movimientos->sum('min_subtotal_linea'), 2);
        $descuentoTipo = (string) ($recepcion->rme_descuento_tipo ?? $movimientos->first()->min_descuento_tipo ?? 'ninguno');
        $descuentoValor = round((float) ($recepcion->rme_descuento_valor ?? $movimientos->first()->min_descuento_valor ?? 0), 2);
        $descuentoMonetario = round((float) $movimientos->sum('min_descuento_linea'), 2);
        if ($descuentoMonetario <= 0 && $descuentoValor > 0) {
            $descuentoMonetario = $descuentoTipo === 'porcentaje'
                ? round($subtotalMonetario * ($descuentoValor / 100), 2)
                : min($subtotalMonetario, $descuentoValor);
        }
        $fleteTotal = round((float) ($recepcion->rme_flete_total ?? $movimientos->first()->min_flete_total ?? 0), 2);
        $ivaPorcentaje = round((float) ($recepcion->rme_iva_porcentaje ?? $movimientos->first()->min_iva_porcentaje ?? 0), 2);
        $ivaMonetario = round((float) $movimientos->sum('min_iva_linea'), 2);
        $totalMonetario = round((float) $movimientos->sum('min_total_linea'), 2);
        if ($totalMonetario <= 0) {
            $baseMonetaria = max(0, round($subtotalMonetario - $descuentoMonetario + $fleteTotal, 2));
            $ivaMonetario = $tipoEntrada === 'compra_factura'
                ? round($baseMonetaria * ($ivaPorcentaje / 100), 2)
                : 0.0;
            $totalMonetario = round($baseMonetaria + $ivaMonetario, 2);
        }

        $folioRecepcion = (string) ($recepcion->rme_folio ?? ('RME-' . $recepcionId));
        $fechaCaptura = optional($recepcion->rme_fecha_captura ?? $movimientos->first()->min_fecha_movimiento)->format('d/m/Y H:i') ?? 'N/D';
        $referencia = trim((string) ($recepcion->rme_documento_referencia ?? $movimientos->first()->min_documento_referencia ?? ''));
        $observaciones = trim((string) ($recepcion->rme_observaciones ?? $movimientos->first()->min_observaciones ?? ''));
        $totalArticulos = (float) $movimientos->sum('min_cantidad');

        $columnasMap = [];
        $filasMap = [];
        foreach ($movimientos as $movimiento) {
            $sku = $movimiento->sku;
            $producto = $sku?->producto;
            if (!$sku || !$producto) {
                continue;
            }

            $color = 'GENERAL';
            $talla = (string) ($sku->psk_codigo ?? 'SKU');
            foreach ($sku->valoresAtributo as $valor) {
                $atrNombre = (string) ($valor->atributo?->atr_nombre ?? '');
                $atrValor = (string) ($valor->vat_valor ?? '-');
                if ($this->esAtributoColor($atrNombre)) {
                    $color = $atrValor;
                    continue;
                }
                if ($this->esAtributoTalla($atrNombre)) {
                    $talla = $atrValor;
                }
            }

            $colKey = $talla !== '' ? $talla : (string) ($sku->psk_codigo ?? 'SKU');
            $columnasMap[$colKey] = $colKey;

            $rowKey = (int) $producto->prd_id . '|' . $color;
            if (!isset($filasMap[$rowKey])) {
                $filasMap[$rowKey] = [
                    'producto' => (string) ($producto->prd_nombre ?? $sku->psk_nombre ?? 'Producto'),
                    'codigo' => (string) ($producto->prd_codigo ?? $sku->psk_codigo ?? ''),
                    'color' => $color,
                    'cells' => [],
                    'total' => 0.0,
                ];
            }

            $cantidad = (float) $movimiento->min_cantidad;
            $filasMap[$rowKey]['cells'][$colKey] = (float) ($filasMap[$rowKey]['cells'][$colKey] ?? 0) + $cantidad;
            $filasMap[$rowKey]['total'] += $cantidad;
        }

        uasort($columnasMap, fn ($a, $b) => strnatcasecmp((string) $a, (string) $b));
        uasort($filasMap, function (array $a, array $b): int {
            $cmp = strnatcasecmp((string) $a['producto'], (string) $b['producto']);
            return $cmp !== 0 ? $cmp : strnatcasecmp((string) $a['color'], (string) $b['color']);
        });

        $filas = array_values($filasMap);
        $columnas = array_values($columnasMap);
        $rowsPerStrip = 6;
        $chunks = array_chunk($filas, $rowsPerStrip);
        $totalPages = max(1, count($chunks));
        $productoWidth = 56;
        $colorWidth = 24;
        $totalWidth = 16;
        $colWidth = 10;
        $pageWidth = max(240, 6 + $productoWidth + $colorWidth + $totalWidth + (count($columnas) * $colWidth));

        $pdf = new TCPDF('L', 'mm', [$pageWidth, 80], true, 'UTF-8', false, false);
        $pdf->SetCreator(config('app.name', 'La Suriana Retail'));
        $pdf->SetAuthor((string) ($request->user()?->usr_nombre ?? config('app.name', 'La Suriana Retail')));
        $pdf->SetTitle('Reporte termico de recepcion');
        $pdf->SetSubject($folioRecepcion);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(3, 3, 3);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.12);
        $pdf->SetTextColor(0, 0, 0);

        $text = function (float $x, float $y, float $w, float $h, string $value, int|float $size = 6.2, string $style = '', string $align = 'L') use ($pdf): void {
            $pdf->SetFont('helvetica', $style, $size);
            $pdf->MultiCell($w, $h, $value, 0, $align, false, 1, $x, $y, true, 0, false, true, $h, 'T', true);
        };

        $cell = function (float $x, float $y, float $w, float $h, string $value, int|float $size = 6.2, string $style = '', string $align = 'L') use ($pdf): void {
            $pdf->Rect($x, $y, $w, $h);
            $pdf->SetFont('helvetica', $style, $size);
            $pdf->MultiCell($w - 1.2, $h, $value, 0, $align, false, 1, $x + 0.6, $y + 0.25, true, 0, false, true, $h - 0.5, 'M', true);
        };

        foreach ($chunks as $pageIndex => $chunk) {
            $pdf->AddPage();

            $text(4, 4, 50, 10, "Consulta Entrada de\nMercancia", 10, 'B');
            $text(4, 18, 72, 4, 'No. Entrada: ' . $folioRecepcion, 6.2, 'B');
            $text(60, 4, 58, 14, "Fecha: {$fechaCaptura}\nTienda: " . (string) ($recepcion->sucursal?->scl_nombre ?? 'N/D') . "\nAlmacen: " . (string) ($recepcion->almacen?->alm_nombre ?? 'N/D'), 6.4, 'B');
            $text(124, 4, 70, 14, "Proveedor: " . (string) ($recepcion->proveedor?->prv_nombre_empresa ?? 'N/D') . "\nReferencia: " . ($referencia !== '' ? $referencia : '-') . "\nComentario: " . ($observaciones !== '' ? $observaciones : '-'), 6.2, 'B');
            $text(200, 4, 50, 10, "Linea: TODAS LAS LINEAS\nMarca: TODAS LAS MARCAS", 6.4, 'B');
            $text($pageWidth - 42, 4, 38, 6, 'Pagina ' . ($pageIndex + 1) . ' de ' . $totalPages, 6.8, 'B', 'R');

            $x = 4;
            $y = 24;
            $headerH = 5;
            $rowH = 7.2;
            $cell($x, $y, $productoWidth, $headerH, 'Producto', 6.3, 'B');
            $x += $productoWidth;
            $cell($x, $y, $colorWidth, $headerH, 'Color', 6.3, 'B');
            $x += $colorWidth;
            foreach ($columnas as $col) {
                $cell($x, $y, $colWidth, $headerH, (string) $col, 6.1, 'B', 'C');
                $x += $colWidth;
            }
            $cell($x, $y, $totalWidth, $headerH, 'Total', 6.2, 'B', 'C');

            $y += $headerH;
            foreach ($chunk as $row) {
                $x = 4;
                $productoTexto = trim((string) $row['producto'] . "\n" . (string) $row['codigo']);
                $cell($x, $y, $productoWidth, $rowH, $productoTexto, 6.2, 'B');
                $x += $productoWidth;
                $cell($x, $y, $colorWidth, $rowH, (string) $row['color'], 6.2);
                $x += $colorWidth;
                foreach ($columnas as $col) {
                    $valor = (float) ($row['cells'][$col] ?? 0);
                    $cell($x, $y, $colWidth, $rowH, $valor > 0 ? number_format($valor, 0, '.', ',') : '', 6.4, '', 'C');
                    $x += $colWidth;
                }
                $cell($x, $y, $totalWidth, $rowH, number_format((float) $row['total'], 0, '.', ','), 6.5, 'B', 'R');
                $y += $rowH;
            }

            if ($pageIndex === $totalPages - 1) {
                $x = 4;
                $summaryW = $productoWidth + $colorWidth + (count($columnas) * $colWidth);
                $cell($x, $y, $summaryW, 5.5, 'Total articulos', 6.4, 'B', 'R');
                $cell($x + $summaryW, $y, $totalWidth, 5.5, number_format($totalArticulos, 0, '.', ','), 6.6, 'B', 'R');
                $text(4, 70, 42, 5, 'TIENDAS L. SURIANA', 6.8, 'B');
                $text(60, 70, 55, 5, 'Subtotal: $ ' . number_format($subtotalMonetario, 2, '.', ',') . '   Desc: $ ' . number_format($descuentoMonetario, 2, '.', ','), 6.4);
                $text(126, 70, 58, 5, 'Flete: $ ' . number_format($fleteTotal, 2, '.', ',') . '   IVA: $ ' . number_format($ivaMonetario, 2, '.', ','), 6.4);
                $text($pageWidth - 52, 70, 48, 5, 'Total: $ ' . number_format($totalMonetario, 2, '.', ','), 7.0, 'B', 'R');
            } else {
                $text(4, 70, 42, 5, 'TIENDAS L. SURIANA', 6.8, 'B');
            }
        }

        $safeFolio = Str::slug($folioRecepcion, '-') ?: ('recepcion-' . $recepcionId);

        return [
            'content' => $pdf->Output('', 'S'),
            'file_name' => 'recepcion-termica-' . $safeFolio . '-' . now()->format('Ymd-His') . '.pdf',
        ];
    }

    public function obtenerReporteCompactoRecepcion(int $recepcionId): array
    {
        $recepcion = RecepcionMercancia::query()
            ->with(['sucursal:scl_id,scl_nombre', 'almacen:alm_id,alm_nombre', 'proveedor:prv_id,prv_nombre_empresa'])
            ->findOrFail($recepcionId);

        $movimientos = MovimientoInventario::query()
            ->with([
                'sku:psk_id,psk_prd_id,psk_codigo,psk_nombre',
                'sku.producto:prd_id,prd_codigo,prd_nombre',
                'sku.valoresAtributo' => fn ($q) => $q
                    ->where('vat_deleted', false)
                    ->whereNull('vat_deleted_at')
                    ->where('vat_estatus', 'activo')
                    ->with(['atributo:atr_id,atr_nombre'])
                    ->orderBy('vat_valor'),
            ])
            ->where('min_rme_id', $recepcionId)
            ->where('min_deleted', false)
            ->whereNull('min_deleted_at')
            ->where('min_estatus', 'activo')
            ->where('min_signo', '>', 0)
            ->orderBy('min_id')
            ->get();

        if ($movimientos->isEmpty()) {
            throw ValidationException::withMessages([
                'recepcion' => 'La recepcion no tiene movimientos definitivos para generar el reporte.',
            ]);
        }

        $grupos = [];
        foreach ($movimientos as $mov) {
            $sku = $mov->sku;
            $producto = $sku?->producto;
            if (!$sku || !$producto) {
                continue;
            }

            $color = 'GENERAL';
            $talla = (string) ($sku->psk_codigo ?? 'SKU');
            foreach ($sku->valoresAtributo as $valor) {
                $atrNombre = (string) ($valor->atributo?->atr_nombre ?? '');
                $atrValor = trim((string) ($valor->vat_valor ?? ''));
                if ($this->esAtributoColor($atrNombre)) {
                    $color = $atrValor !== '' ? $atrValor : 'GENERAL';
                } elseif ($this->esAtributoTalla($atrNombre)) {
                    $talla = $atrValor !== '' ? $atrValor : $talla;
                }
            }

            $gKey = (string) $producto->prd_id;
            if (!isset($grupos[$gKey])) {
                $grupos[$gKey] = [
                    'codigo' => (string) ($producto->prd_codigo ?? ''),
                    'nombre' => (string) ($producto->prd_nombre ?? $sku->psk_nombre ?? 'Producto'),
                    'total' => 0.0,
                    'colores' => [],
                ];
            }

            $cKey = Str::lower(Str::ascii($color));
            if (!isset($grupos[$gKey]['colores'][$cKey])) {
                $grupos[$gKey]['colores'][$cKey] = [
                    'nombre' => $color,
                    'total' => 0.0,
                    'tallas' => [],
                ];
            }

            $cantidad = (float) $mov->min_cantidad;
            $grupos[$gKey]['colores'][$cKey]['tallas'][$talla] = ($grupos[$gKey]['colores'][$cKey]['tallas'][$talla] ?? 0.0) + $cantidad;
            $grupos[$gKey]['colores'][$cKey]['total'] += $cantidad;
            $grupos[$gKey]['total'] += $cantidad;
        }

        uasort($grupos, fn (array $a, array $b): int => strnatcasecmp($a['nombre'], $b['nombre']));

        foreach ($grupos as &$grupo) {
            uasort($grupo['colores'], fn (array $a, array $b): int => strnatcasecmp($a['nombre'], $b['nombre']));
            foreach ($grupo['colores'] as &$colorData) {
                uksort($colorData['tallas'], 'strnatcasecmp');
            }
            unset($colorData);
        }
        unset($grupo);

        $subtotal = round((float) $movimientos->sum('min_subtotal_linea'), 2);
        $descuento = round((float) $movimientos->sum('min_descuento_linea'), 2);
        $flete = round((float) ($recepcion->rme_flete_total ?? 0), 2);
        $iva = round((float) $movimientos->sum('min_iva_linea'), 2);
        $total = round((float) $movimientos->sum('min_total_linea'), 2);
        $articulos = (float) $movimientos->sum('min_cantidad');

        if ($total <= 0 && ($subtotal > 0 || $flete > 0)) {
            $ivaPct = round((float) ($recepcion->rme_iva_porcentaje ?? 0), 2);
            $tipoDoc = (string) ($recepcion->rme_documento_tipo ?? '');
            $base = max(0.0, round($subtotal - $descuento + $flete, 2));
            $iva = $tipoDoc === 'compra_factura' ? round($base * $ivaPct / 100, 2) : 0.0;
            $total = round($base + $iva, 2);
        }

        return [
            'folio'         => (string) ($recepcion->rme_folio ?? 'RME-' . $recepcionId),
            'fecha'         => optional($recepcion->rme_fecha_captura)->format('d/m/Y H:i') ?? 'N/D',
            'sucursal'      => (string) ($recepcion->sucursal?->scl_nombre ?? 'N/D'),
            'almacen'       => (string) ($recepcion->almacen?->alm_nombre ?? 'N/D'),
            'proveedor'     => (string) ($recepcion->proveedor?->prv_nombre_empresa ?? 'N/D'),
            'referencia'    => trim((string) ($recepcion->rme_documento_referencia ?? '')),
            'observaciones' => trim((string) ($recepcion->rme_observaciones ?? '')),
            'grupos'        => array_values($grupos),
            'totales'       => [
                'articulos' => $articulos,
                'subtotal'  => $subtotal,
                'descuento' => $descuento,
                'flete'     => $flete,
                'iva'       => $iva,
                'total'     => $total,
            ],
        ];
    }

    public function obtenerReporteRecepcionMercanciaTermicoHtml(int $recepcionId): array
    {
        $recepcion = RecepcionMercancia::query()
            ->with(['sucursal:scl_id,scl_nombre', 'almacen:alm_id,alm_nombre', 'proveedor:prv_id,prv_nombre_empresa'])
            ->findOrFail($recepcionId);

        $movimientos = MovimientoInventario::query()
            ->with([
                'sku:psk_id,psk_prd_id,psk_codigo,psk_nombre',
                'sku.producto:prd_id,prd_codigo,prd_nombre',
                'sku.valoresAtributo' => fn ($q) => $q
                    ->where('vat_deleted', false)
                    ->whereNull('vat_deleted_at')
                    ->where('vat_estatus', 'activo')
                    ->with(['atributo:atr_id,atr_nombre'])
                    ->orderBy('vat_valor'),
            ])
            ->where('min_rme_id', $recepcionId)
            ->where('min_deleted', false)
            ->whereNull('min_deleted_at')
            ->where('min_estatus', 'activo')
            ->where('min_signo', '>', 0)
            ->orderBy('min_id')
            ->get();

        if ($movimientos->isEmpty()) {
            throw ValidationException::withMessages([
                'recepcion' => 'La recepcion no tiene movimientos definitivos para generar el reporte termico.',
            ]);
        }

        $gruposMap = [];

        foreach ($movimientos as $movimiento) {
            $sku = $movimiento->sku;
            $producto = $sku?->producto;
            if (!$sku || !$producto) {
                continue;
            }

            $color = 'GENERAL';
            $talla = (string) ($sku->psk_codigo ?? 'SKU');
            foreach ($sku->valoresAtributo as $valor) {
                $atrNombre = (string) ($valor->atributo?->atr_nombre ?? '');
                $atrValor = trim((string) ($valor->vat_valor ?? '-'));
                if ($this->esAtributoColor($atrNombre)) {
                    $color = $atrValor !== '' ? $atrValor : 'GENERAL';
                    continue;
                }
                if ($this->esAtributoTalla($atrNombre)) {
                    $talla = $atrValor !== '' ? $atrValor : $talla;
                }
            }

            $groupKey = (string) $producto->prd_id;
            if (!isset($gruposMap[$groupKey])) {
                $gruposMap[$groupKey] = [
                    'producto_id' => (int) $producto->prd_id,
                    'producto' => (string) ($producto->prd_nombre ?? $sku->psk_nombre ?? 'Producto'),
                    'codigo' => (string) ($producto->prd_codigo ?? $sku->psk_codigo ?? ''),
                    'tallas' => [],
                    'rows' => [],
                    'total' => 0.0,
                ];
            }

            $gruposMap[$groupKey]['tallas'][$talla] = $talla;

            $rowKey = Str::lower(Str::ascii($color));
            if (!isset($gruposMap[$groupKey]['rows'][$rowKey])) {
                $gruposMap[$groupKey]['rows'][$rowKey] = [
                    'color' => $color,
                    'cells' => [],
                    'total' => 0.0,
                ];
            }

            $cantidad = (float) $movimiento->min_cantidad;
            $gruposMap[$groupKey]['rows'][$rowKey]['cells'][$talla] = (float) ($gruposMap[$groupKey]['rows'][$rowKey]['cells'][$talla] ?? 0) + $cantidad;
            $gruposMap[$groupKey]['rows'][$rowKey]['total'] += $cantidad;
            $gruposMap[$groupKey]['total'] += $cantidad;
        }

        uasort($gruposMap, function (array $a, array $b): int {
            $cmp = strnatcasecmp((string) $a['producto'], (string) $b['producto']);
            return $cmp !== 0 ? $cmp : strnatcasecmp((string) $a['codigo'], (string) $b['codigo']);
        });

        $layout = [
            'table_width' => 1180,
            'group_col_width' => 250,
            'color_col_width' => 144,
            'total_col_width' => 88,
            'size_col_min_width' => 54,
            'size_col_padding' => 18,
        ];

        $bloques = [];
        $indiceBloqueGlobal = 1;

        foreach ($gruposMap as $grupo) {
            $tallas = array_values($grupo['tallas']);
            usort($tallas, fn ($a, $b) => strnatcasecmp((string) $a, (string) $b));

            $rows = array_values($grupo['rows']);
            usort($rows, fn ($a, $b) => strnatcasecmp((string) $a['color'], (string) $b['color']));

            $columnas = array_map(function (string $talla) use ($layout): array {
                return [
                    'key' => $talla,
                    'label' => $talla,
                    'width' => $this->anchoColumnaTallaTermica($talla, $layout['size_col_min_width'], $layout['size_col_padding']),
                ];
            }, $tallas);

            $segmentos = $this->segmentarColumnasTermicasHtml(
                $columnas,
                $layout['table_width'] - $layout['group_col_width'] - $layout['color_col_width'] - $layout['total_col_width']
            );

            foreach ($segmentos as $segmentIndex => $segmento) {
                $segmentColumns = $segmento['columns'];
                $tableWidth = $layout['group_col_width'] + $layout['color_col_width'] + $layout['total_col_width'] + array_sum(array_column($segmentColumns, 'width'));
                $filas = [];

                foreach ($rows as $rowIndex => $row) {
                    $segmentTotal = 0.0;
                    $cells = [];
                    foreach ($segmentColumns as $col) {
                        $value = (float) ($row['cells'][$col['key']] ?? 0);
                        $segmentTotal += $value;
                        $cells[] = [
                            'key' => $col['key'],
                            'value' => $value,
                        ];
                    }

                    $filas[] = [
                        'show_group_cell' => $rowIndex === 0,
                        'group_rowspan' => count($rows),
                        'group_label' => trim(($grupo['codigo'] !== '' ? $grupo['codigo'] . ' · ' : '') . $grupo['producto']),
                        'color' => $row['color'],
                        'cells' => $cells,
                        'segment_total' => $segmentTotal,
                        'row_total' => (float) $row['total'],
                    ];
                }

                $bloques[] = [
                    'index' => $indiceBloqueGlobal++,
                    'group_index' => $segmentIndex + 1,
                    'group_total_segments' => count($segmentos),
                    'is_group_continuation' => $segmentIndex > 0,
                    'producto' => $grupo['producto'],
                    'codigo' => $grupo['codigo'],
                    'group_total' => (float) $grupo['total'],
                    'columns' => $segmentColumns,
                    'rows' => $filas,
                    'table_width' => $tableWidth,
                    'show_receipt_header' => empty($bloques),
                ];
            }
        }

        $subtotalMonetario = round((float) $movimientos->sum('min_subtotal_linea'), 2);
        $descuentoMonetario = round((float) $movimientos->sum('min_descuento_linea'), 2);
        $fleteTotal = round((float) ($recepcion->rme_flete_total ?? $movimientos->first()->min_flete_total ?? 0), 2);
        $ivaMonetario = round((float) $movimientos->sum('min_iva_linea'), 2);
        $totalMonetario = round((float) $movimientos->sum('min_total_linea'), 2);
        $totalArticulos = (float) $movimientos->sum('min_cantidad');

        return [
            'folio' => (string) ($recepcion->rme_folio ?? ('RME-' . $recepcionId)),
            'fecha' => optional($recepcion->rme_fecha_captura ?? $movimientos->first()->min_fecha_movimiento)->format('d/m/Y H:i') ?? 'N/D',
            'sucursal' => (string) ($recepcion->sucursal?->scl_nombre ?? 'N/D'),
            'almacen' => (string) ($recepcion->almacen?->alm_nombre ?? 'N/D'),
            'proveedor' => (string) ($recepcion->proveedor?->prv_nombre_empresa ?? 'N/D'),
            'referencia' => trim((string) ($recepcion->rme_documento_referencia ?? $movimientos->first()->min_documento_referencia ?? '')),
            'observaciones' => trim((string) ($recepcion->rme_observaciones ?? $movimientos->first()->min_observaciones ?? '')),
            'blocks' => $bloques,
            'receipt_totals' => [
                'articulos' => $totalArticulos,
                'subtotal' => $subtotalMonetario,
                'descuento' => $descuentoMonetario,
                'flete' => $fleteTotal,
                'iva' => $ivaMonetario,
                'total' => $totalMonetario,
            ],
        ];
    }

    public function imprimirRecepcionMercanciaTermicoDirecto(Request $request, int $recepcionId): array
    {
        $printerName = (string) env('THERMAL_PRINTER_NAME', 'POS-80');
        $payload = $this->construirTicketEscposTextoRecepcion($recepcionId);
        $this->enviarPayloadRawWindows($printerName, $payload);

        return [
            'message' => 'Ticket enviado a ' . $printerName . '.',
            'printer' => $printerName,
            'recepcion' => $recepcionId,
        ];
    }

    private function construirTicketEscposTextoRecepcion(int $recepcionId): string
    {
        $recepcion = RecepcionMercancia::query()
            ->with(['sucursal:scl_id,scl_nombre', 'almacen:alm_id,alm_nombre', 'proveedor:prv_id,prv_nombre_empresa'])
            ->findOrFail($recepcionId);

        $movimientos = MovimientoInventario::query()
            ->with([
                'sku:psk_id,psk_prd_id,psk_codigo,psk_nombre',
                'sku.producto:prd_id,prd_codigo,prd_nombre',
                'sku.valoresAtributo' => fn ($q) => $q
                    ->where('vat_deleted', false)
                    ->whereNull('vat_deleted_at')
                    ->where('vat_estatus', 'activo')
                    ->with(['atributo:atr_id,atr_nombre'])
                    ->orderBy('vat_valor'),
            ])
            ->where('min_rme_id', $recepcionId)
            ->where('min_deleted', false)
            ->whereNull('min_deleted_at')
            ->where('min_estatus', 'activo')
            ->where('min_signo', '>', 0)
            ->orderBy('min_id')
            ->get();

        if ($movimientos->isEmpty()) {
            throw ValidationException::withMessages([
                'recepcion' => 'La recepcion no tiene movimientos definitivos para imprimir.',
            ]);
        }

        $width = max(32, min(64, (int) env('THERMAL_CHARS_WIDTH', 42)));

        // ── Agrupar por producto → color → talla ─────────────────────────
        $grupos = [];
        foreach ($movimientos as $mov) {
            $sku = $mov->sku;
            $producto = $sku?->producto;
            if (!$sku || !$producto) {
                continue;
            }

            $color = 'GENERAL';
            $talla = $this->thermalAscii((string) ($sku->psk_codigo ?? 'SKU'));
            foreach ($sku->valoresAtributo as $valor) {
                $atrNombre = (string) ($valor->atributo?->atr_nombre ?? '');
                $atrValor = trim((string) ($valor->vat_valor ?? ''));
                if ($this->esAtributoColor($atrNombre)) {
                    $color = $atrValor !== '' ? $atrValor : 'GENERAL';
                } elseif ($this->esAtributoTalla($atrNombre)) {
                    $talla = $atrValor !== '' ? $this->thermalAscii($atrValor) : $talla;
                }
            }

            $gKey = (string) $producto->prd_id;
            if (!isset($grupos[$gKey])) {
                $grupos[$gKey] = [
                    'codigo' => $this->thermalAscii((string) ($producto->prd_codigo ?? '')),
                    'nombre' => $this->thermalAscii((string) ($producto->prd_nombre ?? $sku->psk_nombre ?? 'Producto')),
                    'total' => 0.0,
                    'colores' => [],
                ];
            }

            $cKey = Str::lower(Str::ascii($color));
            if (!isset($grupos[$gKey]['colores'][$cKey])) {
                $grupos[$gKey]['colores'][$cKey] = [
                    'nombre' => $this->thermalAscii($color),
                    'total' => 0.0,
                    'tallas' => [],
                ];
            }

            $cantidad = (float) $mov->min_cantidad;
            $grupos[$gKey]['colores'][$cKey]['tallas'][$talla] = ($grupos[$gKey]['colores'][$cKey]['tallas'][$talla] ?? 0.0) + $cantidad;
            $grupos[$gKey]['colores'][$cKey]['total'] += $cantidad;
            $grupos[$gKey]['total'] += $cantidad;
        }

        uasort($grupos, fn (array $a, array $b): int => strnatcasecmp($a['nombre'], $b['nombre']));

        // ── Calcular totales monetarios ───────────────────────────────────
        $subtotal = round((float) $movimientos->sum('min_subtotal_linea'), 2);
        $descuento = round((float) $movimientos->sum('min_descuento_linea'), 2);
        $flete = round((float) ($recepcion->rme_flete_total ?? 0), 2);
        $iva = round((float) $movimientos->sum('min_iva_linea'), 2);
        $total = round((float) $movimientos->sum('min_total_linea'), 2);
        $articulos = (float) $movimientos->sum('min_cantidad');

        if ($total <= 0 && ($subtotal > 0 || $flete > 0)) {
            $ivaPct = round((float) ($recepcion->rme_iva_porcentaje ?? 0), 2);
            $tipoDoc = (string) ($recepcion->rme_documento_tipo ?? '');
            $base = max(0.0, round($subtotal - $descuento + $flete, 2));
            $iva = $tipoDoc === 'compra_factura' ? round($base * $ivaPct / 100, 2) : 0.0;
            $total = round($base + $iva, 2);
        }

        // ── Constantes ESC/POS ────────────────────────────────────────────
        $ESC = "\x1B";
        $GS  = "\x1D";
        $LF  = "\n";
        $INIT     = $ESC . '@';
        $LEFT     = $ESC . 'a' . "\x00";
        $CENTER   = $ESC . 'a' . "\x01";
        $BOLD_ON  = $ESC . 'E' . "\x01";
        $BOLD_OFF = $ESC . 'E' . "\x00";
        $CUT      = "\n\n\n\n" . $GS . 'V' . "\x00";
        $sep      = str_repeat('-', $width);
        $sepD     = str_repeat('=', $width);

        // ── Construir ticket ──────────────────────────────────────────────
        $p = $INIT;

        // Encabezado
        $p .= $CENTER . $BOLD_ON . 'ENTRADA DE MERCANCIA' . $LF . $BOLD_OFF;
        $p .= $CENTER . 'Comprobante de recepcion' . $LF;
        $p .= $LEFT . $sep . $LF;

        // Metadatos
        $folio    = $this->thermalAscii((string) ($recepcion->rme_folio ?? 'RME-' . $recepcionId));
        $fecha    = optional($recepcion->rme_fecha_captura)->format('d/m/Y H:i') ?? 'N/D';
        $sucursal = $this->thermalAscii((string) ($recepcion->sucursal?->scl_nombre ?? 'N/D'));
        $almacen  = $this->thermalAscii((string) ($recepcion->almacen?->alm_nombre ?? 'N/D'));
        $proveedor = $this->thermalAscii((string) ($recepcion->proveedor?->prv_nombre_empresa ?? 'N/D'));
        $ref = trim($this->thermalAscii((string) ($recepcion->rme_documento_referencia ?? '')));
        $obs = trim($this->thermalAscii((string) ($recepcion->rme_observaciones ?? '')));

        $p .= $BOLD_ON . $this->escposTcRow('Folio: ' . $folio, $fecha, $width) . $LF . $BOLD_OFF;
        $p .= $this->escposTcRow('Sucursal', substr($sucursal, 0, $width - 10), $width) . $LF;
        $p .= $this->escposTcRow('Almacen', substr($almacen, 0, $width - 9), $width) . $LF;

        foreach (str_split($proveedor, $width - 12) as $idx => $chunk) {
            $p .= ($idx === 0 ? 'Proveedor: ' : '           ') . $chunk . $LF;
        }

        if ($ref !== '') {
            $p .= 'Ref: ' . $ref . $LF;
        }
        if ($obs !== '') {
            foreach (str_split($obs, $width - 5) as $idx => $chunk) {
                $p .= ($idx === 0 ? 'Obs: ' : '     ') . $chunk . $LF;
            }
        }

        $p .= $sep . $LF;

        // Productos
        foreach ($grupos as $grupo) {
            $prodLabel = $grupo['codigo'] !== '' ? $grupo['codigo'] . ' ' . $grupo['nombre'] : $grupo['nombre'];
            $totalStr = number_format($grupo['total'], 0, '.', ',') . 'pz';
            $maxProdLen = $width - strlen($totalStr) - 1;
            $p .= $BOLD_ON . $this->escposTcRow(
                substr($prodLabel, 0, $maxProdLen),
                $totalStr,
                $width
            ) . $LF . $BOLD_OFF;

            foreach ($grupo['colores'] as $colorData) {
                $colorNombre = $colorData['nombre'];
                $colorTotal = number_format($colorData['total'], 0, '.', ',');
                $indent = '  ';
                $p .= $indent . $this->escposTcRow(
                    substr($colorNombre, 0, $width - strlen($indent) - strlen($colorTotal) - 1),
                    $colorTotal,
                    $width - strlen($indent)
                ) . $LF;

                // Tallas compactas en líneas que respetan el ancho
                uksort($colorData['tallas'], 'strnatcasecmp');
                $tallaIndent = '    ';
                $lineWidth = $width - strlen($tallaIndent);
                $lineBuffer = '';
                foreach ($colorData['tallas'] as $talla => $qty) {
                    if ((int) $qty <= 0) {
                        continue;
                    }
                    $chip = $talla . ':' . number_format($qty, 0);
                    $needs = $lineBuffer !== '' ? strlen($lineBuffer) + 2 + strlen($chip) : strlen($chip);
                    if ($lineBuffer !== '' && $needs > $lineWidth) {
                        $p .= $tallaIndent . $lineBuffer . $LF;
                        $lineBuffer = $chip;
                    } else {
                        $lineBuffer .= ($lineBuffer !== '' ? '  ' : '') . $chip;
                    }
                }
                if ($lineBuffer !== '') {
                    $p .= $tallaIndent . $lineBuffer . $LF;
                }
            }

            $p .= $sep . $LF;
        }

        // Totales
        $p .= $BOLD_ON . 'TOTALES' . $LF . $BOLD_OFF;
        $p .= $this->escposTcRow('Articulos', number_format($articulos, 0) . ' pzas', $width) . $LF;

        if ($subtotal > 0) {
            $p .= $this->escposTcRow('Subtotal', '$ ' . number_format($subtotal, 2, '.', ','), $width) . $LF;
        }
        if ($descuento > 0) {
            $p .= $this->escposTcRow('Descuento', '-$ ' . number_format($descuento, 2, '.', ','), $width) . $LF;
        }
        if ($flete > 0) {
            $p .= $this->escposTcRow('Flete', '$ ' . number_format($flete, 2, '.', ','), $width) . $LF;
        }
        if ($iva > 0) {
            $p .= $this->escposTcRow('IVA', '$ ' . number_format($iva, 2, '.', ','), $width) . $LF;
        }

        if ($total > 0) {
            $p .= $sep . $LF;
            $p .= $BOLD_ON . $this->escposTcRow('TOTAL', '$ ' . number_format($total, 2, '.', ','), $width) . $LF . $BOLD_OFF;
        }

        $p .= $LF . $CENTER . now()->format('d/m/Y H:i') . $LF;
        $p .= $CUT;

        return $p;
    }

    private function escposTcRow(string $left, string $right, int $width): string
    {
        $rightLen = strlen($right);
        $maxLeft = $width - $rightLen - 1;
        if (strlen($left) > $maxLeft) {
            $left = substr($left, 0, $maxLeft);
        }
        $spaces = $width - strlen($left) - $rightLen;
        return $left . str_repeat(' ', max(1, $spaces)) . $right;
    }

    public function imprimirRecepcionMercanciaTermicoHtmlDirecto(Request $request, int $recepcionId, string $printUrl): array
    {
        $reporte = $this->obtenerReporteRecepcionMercanciaTermicoHtml($recepcionId);
        $browserPath = $this->resolverNavegadorImpresionSilenciosa();
        $printerName = (string) env('THERMAL_PRINTER_NAME', 'POS-80');

        $this->lanzarImpresionSilenciosaNavegador($browserPath, $printUrl);

        return [
            'message' => 'Impresion termica enviada a ' . $printerName . ' usando impresion silenciosa.',
            'printer' => $printerName,
            'recepcion' => $reporte['folio'],
            'url' => $printUrl,
        ];
    }

    public function guardarRecepcionMercanciaBorrador(Request $request, array $datos): RecepcionMercancia
    {
        return DB::transaction(function () use ($request, $datos): RecepcionMercancia {
            $datos['min_fecha_movimiento'] = (string) ($datos['min_fecha_movimiento'] ?? $this->fechaActualSistema()->toDateTimeString());
            $recepcion = $this->resolverRecepcionEditable((int) ($datos['rme_id'] ?? 0));

            if ($recepcion) {
                $this->validarRecepcionEditable($recepcion);
            } else {
                $recepcion = new RecepcionMercancia();
                $recepcion->rme_folio = $this->crearFolioRecepcion();
                $recepcion->rme_created_by_usr_id = optional($request->user())->usr_id;
            }

            $recepcion->fill($this->mapearDatosRecepcion($datos));
            $recepcion->rme_estado = RecepcionMercancia::ESTADO_BORRADOR;
            $recepcion->rme_updated_by_usr_id = optional($request->user())->usr_id;
            $recepcion->save();

            $this->sincronizarDetalleRecepcion($recepcion, (array) ($datos['lineas'] ?? []), optional($request->user())->usr_id);

            $this->auditoriaService->registrarAccion(
                $request,
                'inventario_base.recepcion.borrador',
                'tbl_recepciones_mercancia_rme',
                (string) $recepcion->rme_id,
                [
                    'rme_folio' => $recepcion->rme_folio,
                    'rme_estado' => $recepcion->rme_estado,
                ]
            );

            return $recepcion->fresh();
        });
    }

    public function confirmarRecepcionMercancia(Request $request, array $datos): array
    {
        return DB::transaction(function () use ($request, $datos): array {
            $datos['min_fecha_movimiento'] = $this->fechaActualSistema()->toDateTimeString();
            $recepcion = $this->resolverRecepcionEditable((int) ($datos['rme_id'] ?? 0));

            if ($recepcion) {
                $this->validarRecepcionEditable($recepcion);
            } else {
                $recepcion = new RecepcionMercancia();
                $recepcion->rme_folio = $this->crearFolioRecepcion();
                $recepcion->rme_created_by_usr_id = optional($request->user())->usr_id;
            }

            $recepcion->fill($this->mapearDatosRecepcion($datos));
            $recepcion->rme_estado = RecepcionMercancia::ESTADO_BORRADOR;
            $recepcion->rme_updated_by_usr_id = optional($request->user())->usr_id;
            $recepcion->save();

            $lineas = $this->normalizarLineasRecepcion((array) ($datos['lineas'] ?? []), true);
            $this->sincronizarDetalleRecepcion($recepcion, $lineas->all(), optional($request->user())->usr_id);

            $folios = [];
            foreach ($this->agruparLineasRecepcionPorProducto($lineas, $datos) as $lote) {
                $resultado = $this->registrarEntradaProductoLote(
                    request: $request,
                    datos: $lote,
                    recepcionId: (int) $recepcion->rme_id,
                    registrarAuditoria: true,
                );
                $folios = array_merge($folios, $resultado['folios']);
            }

            $recepcion->forceFill([
                'rme_estado' => RecepcionMercancia::ESTADO_FINALIZADO,
                'rme_confirmado_at' => $this->fechaActualSistema(),
                'rme_confirmado_by_usr_id' => optional($request->user())->usr_id,
                'rme_updated_by_usr_id' => optional($request->user())->usr_id,
            ])->save();

            $this->auditoriaService->registrarAccion(
                $request,
                'inventario_base.recepcion.finalizar',
                'tbl_recepciones_mercancia_rme',
                (string) $recepcion->rme_id,
                [
                    'rme_folio' => $recepcion->rme_folio,
                    'folios_movimiento' => $folios,
                ]
            );

            return [
                'recepcion' => $recepcion->fresh(),
                'folios' => array_values(array_unique($folios)),
            ];
        });
    }

    public function cancelarRecepcionMercancia(Request $request, int $recepcionId, string $motivo = ''): RecepcionMercancia
    {
        return DB::transaction(function () use ($request, $recepcionId, $motivo): RecepcionMercancia {
            $recepcion = RecepcionMercancia::query()->lockForUpdate()->findOrFail($recepcionId);
            $this->validarRecepcionEditable($recepcion);

            $recepcion->forceFill([
                'rme_estado' => RecepcionMercancia::ESTADO_CANCELADO,
                'rme_cancelado_at' => now(),
                'rme_cancelado_by_usr_id' => optional($request->user())->usr_id,
                'rme_cancelacion_motivo' => trim($motivo) !== '' ? trim($motivo) : 'Cancelado por usuario.',
                'rme_updated_by_usr_id' => optional($request->user())->usr_id,
            ])->save();

            $this->auditoriaService->registrarAccion(
                $request,
                'inventario_base.recepcion.cancelar',
                'tbl_recepciones_mercancia_rme',
                (string) $recepcion->rme_id,
                [
                    'rme_folio' => $recepcion->rme_folio,
                    'motivo' => $recepcion->rme_cancelacion_motivo,
                ]
            );

            return $recepcion->fresh();
        });
    }

    public function descargarReporteEntradasPdfDesdeBitacora(Request $request, int $reporteId): array
    {
        $registro = DB::table('tbl_bitacora_acciones_bac')
            ->where('bac_id', $reporteId)
            ->where('bac_accion', 'inventario_base.reporte_entradas_pdf')
            ->first(['bac_id', 'bac_payload']);

        if (!$registro) {
            throw ValidationException::withMessages([
                'reporte' => 'No se encontró el reporte solicitado.',
            ]);
        }

        $payload = json_decode((string) ($registro->bac_payload ?? '{}'), true);
        if (!is_array($payload) || empty($payload['folios'])) {
            throw ValidationException::withMessages([
                'reporte' => 'El reporte no contiene folios válidos para regenerar PDF.',
            ]);
        }

        $datos = [
            'folios' => (array) $payload['folios'],
            'atr_dominante_id' => (int) ($payload['dominante_atr_id'] ?? 0),
            'min_scl_id' => (int) ($payload['sucursal_id'] ?? 0),
            'min_alm_id' => (int) ($payload['almacen_id'] ?? 0),
            'min_documento_tipo' => (string) ($payload['tipo_entrada'] ?? 'entrada_normal'),
            'min_documento_referencia' => (string) ($payload['referencia'] ?? ''),
            'min_motivo_texto' => (string) ($payload['motivo'] ?? ''),
            'min_observaciones' => (string) ($payload['observaciones'] ?? ''),
            'min_fecha_movimiento' => (string) ($payload['fecha_captura'] ?? now()->toDateTimeString()),
            'min_fecha_emision' => (string) ($payload['fecha_emision'] ?? ''),
            'min_prv_id' => (int) ($payload['proveedor_id'] ?? 0),
            'min_descuento_tipo' => (string) ($payload['descuento_tipo'] ?? 'ninguno'),
            'min_descuento_valor' => (float) ($payload['descuento_valor'] ?? 0),
            'min_flete_total' => (float) ($payload['flete_total'] ?? 0),
            'min_iva_porcentaje' => (float) ($payload['iva_porcentaje'] ?? 16),
        ];

        return $this->generarReporteEntradasSeleccionadasPdf($request, $datos, false);
    }

    public function disponibilidad(int $skuId, int $sucursalId, int $almacenId): array
    {
        $existencia = ExistenciaAlmacen::query()
            ->where('exa_psk_id', $skuId)
            ->where('exa_scl_id', $sucursalId)
            ->where('exa_alm_id', $almacenId)
            ->first();

        return [
            'existencia' => (float) ($existencia?->exa_existencia ?? 0),
        ];
    }

    public function registrarInventarioInicial(Request $request, array $datos): MovimientoInventario
    {
        return DB::transaction(function () use ($request, $datos): MovimientoInventario {
            $movimiento = $this->registrarMovimientoInterno(
                request: $request,
                datos: $datos,
                tmiClave: 'inventario.entrada',
                documentoTipo: 'inventario_inicial',
                signo: 1,
                movimientoOrigenId: null,
                reversaDeId: null,
                esReversa: false,
            );

            $this->auditoriaService->registrarAccion(
                $request,
                'inventario_base.inicial',
                'tbl_movimientos_inventario_min',
                (string) $movimiento->min_id,
                [
                    'min_folio' => $movimiento->min_folio,
                    'min_psk_id' => $movimiento->min_psk_id,
                    'min_scl_id' => $movimiento->min_scl_id,
                    'min_alm_id' => $movimiento->min_alm_id,
                    'min_cantidad' => $movimiento->min_cantidad,
                ]
            );

            return $movimiento;
        });
    }

    public function registrarInventarioInicialMasivo(Request $request, array $datos): array
    {
        return DB::transaction(fn () => $this->registrarEntradaProductoLote($request, $datos));
    }

    public function generarReporteEntradasSeleccionadasPdf(Request $request, array $datos, bool $registrarAuditoria = true): array
    {
        $folios = collect($datos['folios'] ?? [])
            ->map(fn ($folio) => trim((string) $folio))
            ->filter()
            ->unique()
            ->values();

        if ($folios->isEmpty()) {
            throw ValidationException::withMessages([
                'folios' => 'Debes enviar al menos un folio para generar el reporte.',
            ]);
        }

        $movimientos = MovimientoInventario::query()
            ->whereIn('min_folio', $folios->all())
            ->where('min_deleted', false)
            ->whereNull('min_deleted_at')
            ->where('min_estatus', 'activo')
            ->where('min_signo', '>', 0)
            ->orderBy('min_fecha_movimiento')
            ->orderBy('min_id')
            ->get([
                'min_id',
                'min_folio',
                'min_psk_id',
                'min_scl_id',
                'min_alm_id',
                'min_prv_id',
                'min_cantidad',
                'min_fecha_movimiento',
                'min_fecha_emision',
                'min_documento_tipo',
                'min_documento_referencia',
                'min_descuento_tipo',
                'min_descuento_valor',
                'min_flete_total',
                'min_precio_unitario',
                'min_subtotal_linea',
                'min_descuento_linea',
                'min_flete_linea',
                'min_iva_porcentaje',
                'min_iva_linea',
                'min_total_linea',
                'min_motivo_texto',
                'min_observaciones',
                'min_created_by_usr_id',
            ]);

        if ($movimientos->isEmpty()) {
            throw ValidationException::withMessages([
                'folios' => 'No se encontraron movimientos de entrada activos para los folios enviados.',
            ]);
        }

        $skuIds = $movimientos->pluck('min_psk_id')->unique()->values();
        $skus = ProductoSku::query()
            ->with([
                'producto:prd_id,prd_codigo,prd_nombre,prd_tipo,prd_precio_base,prd_costo,prd_mrc_id,prd_mdl_id,prd_ctg_id,prd_dsc_id',
                'producto.marca:mrc_id,mrc_nombre',
                'producto.modelo:mdl_id,mdl_nombre',
                'producto.categoria:ctg_id,ctg_nombre',
                'producto.descripcionCatalogo:dsc_id,dsc_nombre',
                'valoresAtributo' => fn ($q) => $q
                    ->where('vat_deleted', false)
                    ->whereNull('vat_deleted_at')
                    ->where('vat_estatus', 'activo')
                    ->with(['atributo:atr_id,atr_nombre'])
                    ->orderBy('vat_valor'),
            ])
            ->whereIn('psk_id', $skuIds->all())
            ->get(['psk_id', 'psk_prd_id', 'psk_codigo', 'psk_nombre', 'psk_precio', 'psk_costo'])
            ->keyBy('psk_id');

        $dominanteAtrId = (int) ($datos['atr_dominante_id'] ?? 0);
        $columnasMap = [];
        $productColumnasMap = [];
        $filasMap = [];
        $rowSort = [];
        $dominanteNombre = 'Dominante';
        $tipoEntrada = (string) ($datos['min_documento_tipo'] ?? 'entrada_normal');
        $descuentoTipo = (string) ($datos['min_descuento_tipo'] ?? ($movimientos->first()->min_descuento_tipo ?? 'ninguno'));
        $descuentoValor = round((float) ($datos['min_descuento_valor'] ?? ($movimientos->first()->min_descuento_valor ?? 0)), 2);
        $fleteTotal = round((float) ($datos['min_flete_total'] ?? ($movimientos->first()->min_flete_total ?? 0)), 2);
        $ivaPorcentaje = round((float) ($datos['min_iva_porcentaje'] ?? ($movimientos->first()->min_iva_porcentaje ?? 16)), 2);
        $economicos = $this->calcularEconomicosRecepcion(
            $movimientos,
            $descuentoTipo,
            $descuentoValor,
            $fleteTotal,
            $ivaPorcentaje,
        );

        foreach ($movimientos as $movimiento) {
            /** @var ProductoSku|null $sku */
            $sku = $skus->get((int) $movimiento->min_psk_id);
            if (!$sku || !$sku->producto) {
                continue;
            }

            $producto = $sku->producto;
            $productoId = (int) $producto->prd_id;
            $productoMeta = implode(' · ', [
                (string) ($producto->marca?->mrc_nombre ?? 'S/M'),
                (string) ($producto->modelo?->mdl_nombre ?? 'S/Mo'),
                (string) ($producto->categoria?->ctg_nombre ?? 'S/C'),
                (string) ($producto->descripcionCatalogo?->dsc_nombre ?? 'S/D'),
                (string) ($producto->prd_codigo ?? 'S/CI'),
            ]);
            $productoLabel = '<span style="font-weight:bold;">' . $this->esc((string) $producto->prd_nombre) . '</span><br>' .
                '<span style="font-size:7px;color:#64748b;">' . $this->esc($productoMeta) . '</span>';
            $tipoProducto = (string) ($producto->prd_tipo ?? 'simple');

            $atributosById = [];
            $atributosByNombre = [];
            foreach ($sku->valoresAtributo as $valor) {
                $atrId = (int) ($valor->vat_atr_id ?? 0);
                $atrNombre = (string) ($valor->atributo?->atr_nombre ?? 'Atributo');
                $atrValor = (string) ($valor->vat_valor ?? '-');
                if ($atrId <= 0) {
                    continue;
                }
                $atributosById[$atrId] = ['nombre' => $atrNombre, 'valor' => $atrValor];
                $atributosByNombre[$atrNombre] = $atrValor;
            }

            $dominanteValor = 'Estándar';
            $colKey = '__simple__';
            $colLabel = 'Existencia';
            $compatibleDominante = false;

            if ($tipoProducto === 'variable') {
                $colorAtrId = null;
                $tallaAtrId = null;

                foreach ($atributosById as $atrId => $item) {
                    $nombreAttr = (string) ($item['nombre'] ?? '');
                    if ($colorAtrId === null && $this->esAtributoColor($nombreAttr)) {
                        $colorAtrId = (int) $atrId;
                    }
                    if ($tallaAtrId === null && $this->esAtributoTalla($nombreAttr)) {
                        $tallaAtrId = (int) $atrId;
                    }
                }

                $rowAtrId = $colorAtrId ?? ($dominanteAtrId > 0 && isset($atributosById[$dominanteAtrId]) ? $dominanteAtrId : null);
                $colAtrId = $tallaAtrId;

                if ($rowAtrId !== null && isset($atributosById[$rowAtrId])) {
                    $compatibleDominante = true;
                    $dominanteNombre = (string) $atributosById[$rowAtrId]['nombre'];
                    $dominanteValor = (string) $atributosById[$rowAtrId]['valor'];

                    $restoFila = collect($atributosById)
                        ->reject(fn ($item, $id) => (int) $id === (int) $rowAtrId || (int) $id === (int) $colAtrId)
                        ->map(fn ($item) => (string) $item['valor'])
                        ->filter()
                        ->values()
                        ->all();

                    if (!empty($restoFila)) {
                        $dominanteValor .= ' / ' . implode(' / ', $restoFila);
                    }
                }

                if ($colAtrId !== null && isset($atributosById[$colAtrId])) {
                    $colLabel = (string) $atributosById[$colAtrId]['valor'];
                    $colKey = $colLabel !== '' ? $colLabel : '__base__';
                } elseif ($rowAtrId !== null) {
                    $restoCol = collect($atributosById)
                        ->reject(fn ($item, $id) => (int) $id === (int) $rowAtrId)
                        ->map(fn ($item) => (string) $item['valor'])
                        ->filter()
                        ->values()
                        ->all();

                    $colLabel = !empty($restoCol) ? implode(' / ', $restoCol) : 'Existencia';
                    $colKey = !empty($restoCol) ? implode('||', $restoCol) : '__base__';
                }
            }

            if (!isset($columnasMap[$colKey])) {
                $columnasMap[$colKey] = $colLabel;
            }
            if (!isset($productColumnasMap[$productoId][$colKey])) {
                $productColumnasMap[$productoId][$colKey] = $colLabel;
            }

            $rowId = $productoId . '|' . $dominanteValor;
            if (!isset($filasMap[$rowId])) {
                $filasMap[$rowId] = [
                    'producto_id' => $productoId,
                    'producto' => $productoLabel,
                    'dominante' => $dominanteValor,
                    'cells' => [],
                    'total_articulos' => 0.0,
                    'precio_acumulado' => 0.0,
                    'costo_acumulado' => 0.0,
                    'total_monetario' => 0.0,
                    'compatible_dominante' => $compatibleDominante || $tipoProducto === 'simple',
                ];
                $rowSort[$rowId] = [
                    'producto' => mb_strtolower(trim(strip_tags($productoLabel))),
                    'dominante' => mb_strtolower($dominanteValor),
                ];
            }

            $cantidad = (float) $movimiento->min_cantidad;
            $filasMap[$rowId]['cells'][$colKey] = (float) ($filasMap[$rowId]['cells'][$colKey] ?? 0) + $cantidad;
            $filasMap[$rowId]['total_articulos'] += $cantidad;

            $precioReferencia = (float) ($sku->psk_precio ?? $producto->prd_precio_base ?? 0);
            $costoReferencia = (float) ($movimiento->min_precio_unitario ?? 0);
            if ($costoReferencia <= 0) {
                $costoReferencia = (float) ($sku->psk_costo ?? $producto->prd_costo ?? 0);
            }
            $totalLineaMonetario = (float) ($economicos['lineas'][(int) $movimiento->min_id]['subtotal'] ?? 0);

            $filasMap[$rowId]['precio_acumulado'] += ($precioReferencia * $cantidad);
            $filasMap[$rowId]['costo_acumulado'] += ($costoReferencia * $cantidad);
            $filasMap[$rowId]['total_monetario'] += $totalLineaMonetario;
        }

        if (empty($filasMap)) {
            throw ValidationException::withMessages([
                'folios' => 'No se pudo construir el reporte con los movimientos seleccionados.',
            ]);
        }

        uasort($columnasMap, fn ($a, $b) => strnatcasecmp((string) $a, (string) $b));
        foreach ($productColumnasMap as &$pColMap) {
            uasort($pColMap, fn ($a, $b) => strnatcasecmp((string) $a, (string) $b));
        }
        unset($pColMap);
        uasort($filasMap, function (array $a, array $b): int {
            $cmpProducto = strcmp((string) mb_strtolower($a['producto']), (string) mb_strtolower($b['producto']));
            if ($cmpProducto !== 0) {
                return $cmpProducto;
            }

            return strcmp((string) mb_strtolower($a['dominante']), (string) mb_strtolower($b['dominante']));
        });

        $sucursalId = (int) ($datos['min_scl_id'] ?? (int) $movimientos->first()->min_scl_id);
        $almacenId = (int) ($datos['min_alm_id'] ?? (int) $movimientos->first()->min_alm_id);
        $proveedorId = (int) ($datos['min_prv_id'] ?? (int) ($movimientos->first()->min_prv_id ?? 0));
        $sucursalNombre = (string) (Sucursal::query()->where('scl_id', $sucursalId)->value('scl_nombre') ?? 'N/D');
        $almacenNombre = (string) (Almacen::query()->where('alm_id', $almacenId)->value('alm_nombre') ?? 'N/D');
        $proveedorNombre = $proveedorId > 0
            ? (string) (DB::table('tbl_proveedores_prv')->where('prv_id', $proveedorId)->value('prv_nombre_empresa') ?? 'N/D')
            : 'N/D';

        $tipoEntradaLabel = match ($tipoEntrada) {
            'inventario_inicial' => 'Entrada normal (inventario inicial)',
            'entrada_normal' => 'Entrada normal',
            'compra_remision' => 'Compra con remisión',
            'compra_factura' => 'Compra con factura',
            default => Str::headline(str_replace('_', ' ', $tipoEntrada)),
        };

        $referencia = trim((string) ($datos['min_documento_referencia'] ?? ($datos['referencia'] ?? '')));
        $motivo = trim((string) ($datos['min_motivo_texto'] ?? ($datos['motivo'] ?? '')));
        $observaciones = trim((string) ($datos['min_observaciones'] ?? ($datos['observaciones'] ?? ($movimientos->first()->min_observaciones ?? ''))));
        $fechaCaptura = (string) ($datos['min_fecha_movimiento'] ?? now()->toDateTimeString());
        $fechaEmision = (string) ($datos['min_fecha_emision'] ?? ($movimientos->first()->min_fecha_emision ?? ''));
        $subtotalMonetario = (float) $economicos['subtotal'];
        $descuentoMonetario = (float) $economicos['descuento'];
        $ivaMonetario = (float) $economicos['iva'];
        $totalMonetario = (float) $economicos['total'];

        // ── Totales globales por columna ────────────────────────────────────
        $totalesPorColumna = [];
        $granTotal = 0.0;
        foreach ($filasMap as $row) {
            foreach ($columnasMap as $colKey => $colLabel) {
                $totalesPorColumna[$colKey] = (float) ($totalesPorColumna[$colKey] ?? 0) + (float) ($row['cells'][$colKey] ?? 0);
            }
            $granTotal += (float) $row['total_articulos'];
        }

        $totalFilas   = count($filasMap);
        $totalProductos = count(array_unique(array_column(array_values($filasMap), 'producto_id')));

        // ── Colores brand ───────────────────────────────────────────────────
        $colorPrimario  = '#0a2540';   // navy oscuro
        $colorAccent    = '#635bff';   // violeta La Suriana
        $colorHeaderBg  = '#1a3a5c';   // azul encabezado tabla
        $colorHeaderTxt = '#ffffff';
        $colorSubHeader = '#e8edf3';   // gris claro subencabezado
        $colorBorderTbl = '#c8d3e0';
        $colorOkBg      = '#e4f7ed';   // verde muy claro
        $colorOkTxt     = '#166534';
        $colorNaBg      = '#f1f4f8';
        $colorNaTxt     = '#94a3b8';
        $colorTotalBg   = '#dde6f0';
        $colorAltRow    = '#f7f9fc';   // zebra alternado

        $css = '
            body        { font-family: helvetica, arial, sans-serif; font-size: 9px; color: ' . $colorPrimario . '; }
            table       { width: 100%; border-collapse: collapse; }
            th, td      { border: 1px solid ' . $colorBorderTbl . '; padding: 4px 5px; vertical-align: middle; }
            .text-right  { text-align: right; }
            .text-center { text-align: center; }
            .text-left   { text-align: left; }
            .bold        { font-weight: bold; }
            .ok          { background-color: ' . $colorOkBg . '; color: ' . $colorOkTxt . '; }
            .na          { background-color: ' . $colorNaBg . '; color: ' . $colorNaTxt . '; }
            .total-row   { background-color: ' . $colorTotalBg . '; font-weight: bold; }
            .alt-row     { background-color: ' . $colorAltRow . '; }
        ';

        // ── Encabezado de página ────────────────────────────────────────────
        $html = '<style>' . $css . '</style>';

        // Barra de título
        $html .= '
        <table style="border-collapse:collapse;margin-bottom:2px;">
            <tr>
                <td style="border:none;padding:5px 10px;background-color:' . $colorPrimario . ';width:75%;">
                    <div style="font-size:13px;font-weight:bold;color:#ffffff;letter-spacing:0.4px;">
                        Reporte de Entradas Registradas
                    </div>
                    <div style="font-size:7.5px;color:#9eb4cc;margin-top:1px;letter-spacing:0.3px;">
                        LA I. SURIANA &nbsp;&bull;&nbsp; INVENTARIO BASE
                    </div>
                </td>
                <td style="border:none;padding:5px 10px;background-color:' . $colorAccent . ';width:25%;text-align:right;vertical-align:middle;">
                    <div style="font-size:7px;color:#d0ccff;">Generado</div>
                    <div style="font-size:10px;font-weight:bold;color:#ffffff;">' . $this->esc(now()->format('d/M/Y')) . '</div>
                    <div style="font-size:7px;color:#d0ccff;">' . $this->esc(now()->format('H:i:s')) . '</div>
                </td>
            </tr>
        </table>';

        // Banda de metadatos (2 columnas)
        $fechaCapturaFmt = $fechaCaptura !== ''
            ? date('d/m/Y H:i', strtotime($fechaCaptura))
            : 'N/D';

        $html .= '
        <table style="border-collapse:collapse;margin-bottom:3px;">
            <tr>
                <td style="border:none;border-bottom:3px solid ' . $colorAccent . ';background-color:#f0f4f9;padding:3px 8px;width:50%;vertical-align:top;">
                    <table style="border-collapse:collapse;width:100%;">
                        <tr>
                            <td style="border:none;padding:1px 3px;font-size:8px;color:#64748b;width:38%;">Sucursal</td>
                            <td style="border:none;padding:1px 3px;font-size:9px;font-weight:bold;color:' . $colorPrimario . ';">' . $this->esc($sucursalNombre) . '</td>
                        </tr>
                        <tr>
                            <td style="border:none;padding:1px 3px;font-size:8px;color:#64748b;">Almacén</td>
                            <td style="border:none;padding:1px 3px;font-size:9px;font-weight:bold;color:' . $colorPrimario . ';">' . $this->esc($almacenNombre) . '</td>
                        </tr>
                        <tr>
                            <td style="border:none;padding:1px 3px;font-size:8px;color:#64748b;">Tipo de entrada</td>
                            <td style="border:none;padding:1px 3px;font-size:9px;color:' . $colorPrimario . ';">' . $this->esc($tipoEntradaLabel) . '</td>
                        </tr>
                        <tr>
                            <td style="border:none;padding:1px 3px;font-size:8px;color:#64748b;">Proveedor</td>
                            <td style="border:none;padding:1px 3px;font-size:9px;color:' . $colorPrimario . ';">' . $this->esc($proveedorNombre) . '</td>
                        </tr>
                        <tr>
                            <td style="border:none;padding:1px 3px;font-size:8px;color:#64748b;">Fecha captura</td>
                            <td style="border:none;padding:1px 3px;font-size:9px;color:' . $colorPrimario . ';">' . $this->esc($fechaCapturaFmt) . '</td>
                        </tr>
                        <tr>
                            <td style="border:none;padding:1px 3px;font-size:8px;color:#64748b;">Fecha emisión</td>
                            <td style="border:none;padding:1px 3px;font-size:9px;color:' . $colorPrimario . ';">' . $this->esc($fechaEmision !== '' ? date('d/m/Y H:i', strtotime($fechaEmision)) : 'N/D') . '</td>
                        </tr>
                    </table>
                </td>
                <td style="border:none;border-bottom:3px solid ' . $colorAccent . ';background-color:#f0f4f9;padding:3px 8px;width:50%;vertical-align:top;">
                    <table style="border-collapse:collapse;width:100%;">
                        <tr>
                            <td style="border:none;padding:1px 3px;font-size:8px;color:#64748b;width:32%;">Dominante</td>
                            <td style="border:none;padding:1px 3px;font-size:9px;color:' . $colorPrimario . ';">' . $this->esc($dominanteAtrId > 0 ? $dominanteNombre : 'No definida') . '</td>
                        </tr>
                        <tr>
                            <td style="border:none;padding:1px 3px;font-size:8px;color:#64748b;">Referencia</td>
                            <td style="border:none;padding:1px 3px;font-size:9px;color:' . $colorPrimario . ';">' . $this->esc($referencia !== '' ? $referencia : '—') . '</td>
                        </tr>
                        <tr>
                            <td style="border:none;padding:1px 3px;font-size:8px;color:#64748b;">Motivo</td>
                            <td style="border:none;padding:1px 3px;font-size:9px;color:' . $colorPrimario . ';">' . $this->esc($motivo !== '' ? $motivo : '—') . '</td>
                        </tr>
                        <tr>
                            <td style="border:none;padding:1px 3px;font-size:8px;color:#64748b;">Observaciones</td>
                            <td style="border:none;padding:1px 3px;font-size:9px;color:' . $colorPrimario . ';">' . $this->esc($observaciones !== '' ? $observaciones : '—') . '</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>';

        // ── Anchos fijos en mm (A4 landscape = 297mm, márgenes 7mm c/u → 283mm útiles) ──
        $mmProducto  = 62;   // columna producto
        $mmDominante = 24;   // columna dominante (color, talla, etc.)
        $mmTotalArt  = 14;   // total articulo
        $mmPrecio    = 17;   // precio
        $mmCosto     = 17;   // costo
        $mmTotal     = 20;   // total monetario
        $mmDisponible = 283 - $mmProducto - $mmDominante - $mmTotalArt - $mmPrecio - $mmCosto - $mmTotal;
        $numColumnas  = max(1, count($columnasMap));
        $mmCol        = max(8, (int) floor($mmDisponible / $numColumnas));

        $baseMonetaria = max(0, round($subtotalMonetario - $descuentoMonetario + $fleteTotal, 2));
        $moneyRow = function (string $label, float $value, bool $grand = false) use ($colorBorderTbl, $colorPrimario, $colorAccent): string {
            $rowStyle = 'padding:3px 0;font-size:' . ($grand ? '11px' : '9px') . ';color:' . ($grand ? $colorPrimario : '#475569') . ';';
            if ($grand) {
                $rowStyle .= 'border-top:1px solid ' . $colorBorderTbl . ';padding-top:7px;';
            }

            return '<tr>' .
                '<td style="' . $rowStyle . ' text-align:left;">' . $this->esc($label) . '</td>' .
                '<td style="' . $rowStyle . ' text-align:right;font-weight:' . ($grand ? '800' : '600') . ';color:' . ($grand ? $colorAccent : $colorPrimario) . ';">$ ' . number_format($value, 2, '.', ',') . '</td>' .
            '</tr>';
        };

        $moneyRows = '';
        $moneyRows .= $moneyRow('Subtotal', $subtotalMonetario);
        $moneyRows .= $moneyRow('Descuento', $descuentoMonetario);
        $moneyRows .= $moneyRow('Flete', $fleteTotal);
        $moneyRows .= $moneyRow('IVA (' . number_format($ivaPorcentaje, 2, '.', ',') . '%)', $ivaMonetario);
        $moneyRows .= $moneyRow('Total', $totalMonetario, true);

        $summaryHtml = '
        <table style="border-collapse:collapse;margin-top:4px;margin-bottom:3px;width:283mm;">
            <tr>
                <td style="border:none;border-top:1px solid ' . $colorBorderTbl . ';padding:4px 8px 3px 0;width:110mm;vertical-align:middle;">
                    <span style="font-size:8px;color:#64748b;">Líneas:</span>
                    <span style="font-size:9px;font-weight:800;color:' . $colorPrimario . ';">&nbsp;' . number_format($totalFilas, 0, '.', ',') . '</span>
                    <span style="font-size:8px;color:#c8d3e0;">&nbsp;&nbsp;|&nbsp;&nbsp;</span>
                    <span style="font-size:8px;color:#64748b;">Artículos:</span>
                    <span style="font-size:9px;font-weight:800;color:' . $colorPrimario . ';">&nbsp;' . number_format($granTotal, 0, '.', ',') . '</span>
                </td>
                <td style="border:none;border-top:1px solid ' . $colorBorderTbl . ';padding:3px 0 3px 12px;width:173mm;vertical-align:top;">
                    <table style="border-collapse:collapse;width:100%;">' . $moneyRows . '</table>
                </td>
            </tr>
        </table>';

        // ── Tabla de datos con anchos en mm ──────────────────────────────
        // Cada tabla de producto usa anchos explícitos en todas sus columnas.
        // Las columnas de tallas reciben el espacio sobrante dividido con round()
        // para minimizar la diferencia de posición de las columnas finales.
        $p2  = '2px 3px';   // padding compacto celdas de datos
        $p2h = '2px 4px';   // padding encabezados
        $stProducto  = 'width:' . $mmProducto  . 'mm;border:1px solid ' . $colorBorderTbl . ';padding:' . $p2 . ';vertical-align:middle;';
        $stDominante = 'width:' . $mmDominante . 'mm;border:1px solid ' . $colorBorderTbl . ';padding:' . $p2 . ';text-align:center;vertical-align:middle;';
        // border-left marcado para separar visualmente la zona de tallas de la zona monetaria
        $stTotalArt  = 'width:' . $mmTotalArt  . 'mm;border:1px solid ' . $colorBorderTbl . ';border-left:2px solid ' . $colorPrimario . ';padding:' . $p2 . ';text-align:center;vertical-align:middle;';
        $stPrecio    = 'width:' . $mmPrecio    . 'mm;border:1px solid ' . $colorBorderTbl . ';padding:' . $p2 . ';text-align:right;vertical-align:middle;';
        $stCosto     = 'width:' . $mmCosto     . 'mm;border:1px solid ' . $colorBorderTbl . ';padding:' . $p2 . ';text-align:right;vertical-align:middle;';
        $stTotal     = 'width:' . $mmTotal     . 'mm;border:1px solid ' . $colorBorderTbl . ';padding:' . $p2 . ';text-align:right;vertical-align:middle;';

        // ── Encabezado global (se muestra UNA SOLA VEZ sobre todos los bloques) ──
        // La zona de tallas se representa con un placeholder; cada producto
        // mostrará sus propias tallas en su sub-encabezado.
        $mmEspaciador = $mmDisponible;
        // Celda fantasma para columnas monetarias en sub-encabezados por producto:
        // mantiene la estructura de columnas sin repetir el label.
        $stMoneyGhost = 'border:none;border-bottom:1px solid ' . $colorBorderTbl . ';padding:0;vertical-align:middle;';

        $html .= '<table style="border-collapse:collapse;font-size:8px;width:283mm;margin-bottom:0;">';
        $html .= '<tr>';
        $html .= '<th style="' . $stProducto . 'background-color:' . $colorHeaderBg . ';color:' . $colorHeaderTxt . ';font-weight:bold;text-align:left;padding:' . $p2h . ';">Producto</th>';
        $html .= '<th style="' . $stDominante . 'background-color:' . $colorHeaderBg . ';color:' . $colorHeaderTxt . ';font-weight:bold;padding:' . $p2h . ';">' . $this->esc($dominanteNombre) . '</th>';
        $html .= '<th style="width:' . $mmEspaciador . 'mm;border:1px solid ' . $colorBorderTbl . ';background-color:' . $colorHeaderBg . ';color:#9eb4cc;font-weight:normal;font-style:italic;text-align:center;padding:' . $p2h . ';font-size:7.5px;">tallas por producto</th>';
        $html .= '<th style="' . $stTotalArt . 'background-color:' . $colorAccent . ';color:#ffffff;font-weight:bold;padding:' . $p2h . ';">Total</th>';
        $html .= '<th style="' . $stCosto . 'background-color:' . $colorHeaderBg . ';color:#ffffff;font-weight:bold;padding:' . $p2h . ';">Costo</th>';
        $html .= '<th style="' . $stPrecio . 'background-color:' . $colorHeaderBg . ';color:#ffffff;font-weight:bold;padding:' . $p2h . ';">Precio</th>';
        $html .= '<th style="' . $stTotal . 'background-color:' . $colorAccent . ';color:#ffffff;font-weight:bold;padding:' . $p2h . ';">Total $</th>';
        $html .= '</tr>';
        $html .= '</table>';

        // ── Tabla de datos: una tabla por producto ────────────────────────
        // Agrupar filas por producto_id preservando el orden de $filasMap
        $filasPorProducto = [];
        foreach ($filasMap as $row) {
            if ((float) ($row['total_articulos'] ?? 0) <= 0) {
                continue;
            }
            $filasPorProducto[$row['producto_id']][] = $row;
        }

        foreach ($filasPorProducto as $productoId => $filas) {
            $colsProducto  = $productColumnasMap[$productoId] ?? [];
            $numColsProd   = max(1, count($colsProducto));
            $mmColProd     = max(8, round($mmDisponible / $numColsProd, 1));
            $stColProd     = 'width:' . $mmColProd . 'mm;border:1px solid ' . $colorBorderTbl . ';padding:' . $p2 . ';text-align:center;vertical-align:middle;';
            $esTablaSimple = ($numColsProd === 1 && isset($colsProducto['__simple__']));
            $firstRow      = $filas[0];

            $html .= '<table style="border-collapse:collapse;font-size:8px;width:283mm;margin-bottom:3px;">';
            $html .= '<tbody>';

            if ($esTablaSimple) {
                // Sub-encabezado: nombre producto + celda dominante vacía + celdas fantasma monetarias
                $html .= '<tr>';
                $html .= '<td style="' . $stProducto . 'background-color:' . $colorHeaderBg . ';color:#ffffff;font-weight:bold;">' . (string) $firstRow['producto'] . '</td>';
                $html .= '<td style="' . $stDominante . 'background-color:' . $colorSubHeader . ';"></td>';
                $html .= '<td style="width:' . $mmTotalArt . 'mm;border-left:2px solid ' . $colorPrimario . ';' . $stMoneyGhost . '"></td>';
                $html .= '<td style="width:' . $mmCosto . 'mm;' . $stMoneyGhost . '"></td>';
                $html .= '<td style="width:' . $mmPrecio . 'mm;' . $stMoneyGhost . '"></td>';
                $html .= '<td style="width:' . $mmTotal . 'mm;' . $stMoneyGhost . '"></td>';
                $html .= '</tr>';

                foreach ($filas as $idx => $row) {
                    $bgFila = ($idx % 2 === 1) ? $colorAltRow : '#ffffff';
                    $totalArticulosFila = (float) ($row['total_articulos'] ?? 0);
                    $precioPromedioFila = $totalArticulosFila > 0 ? ((float) $row['precio_acumulado'] / $totalArticulosFila) : 0;
                    $costoPromedioFila  = $totalArticulosFila > 0 ? ((float) $row['costo_acumulado'] / $totalArticulosFila) : 0;
                    $totalMonetarioFila = (float) ($row['total_monetario'] ?? 0);

                    $html .= '<tr>';
                    $html .= '<td style="' . $stProducto . 'background-color:' . $bgFila . ';border-top:1px dashed ' . $colorBorderTbl . ';">&nbsp;</td>';
                    $html .= '<td style="' . $stDominante . 'background-color:' . $colorSubHeader . ';font-weight:bold;">' . $this->esc((string) $row['dominante']) . '</td>';
                    $html .= '<td style="' . $stTotalArt . 'background-color:' . $colorTotalBg . ';font-weight:bold;">' . number_format($totalArticulosFila, 0, '.', ',') . '</td>';
                    $html .= '<td style="' . $stCosto . 'background-color:' . $bgFila . ';font-weight:bold;">' . number_format($costoPromedioFila, 2, '.', ',') . '</td>';
                    $html .= '<td style="' . $stPrecio . 'background-color:' . $bgFila . ';font-weight:bold;">' . number_format($precioPromedioFila, 2, '.', ',') . '</td>';
                    $html .= '<td style="' . $stTotal . 'background-color:' . $colorTotalBg . ';font-weight:bold;">' . number_format($totalMonetarioFila, 2, '.', ',') . '</td>';
                    $html .= '</tr>';
                }
            } else {
                // Sub-encabezado: nombre producto + celda dominante vacía + tallas + celdas fantasma monetarias
                $html .= '<tr>';
                $html .= '<td style="' . $stProducto . 'background-color:' . $colorHeaderBg . ';color:#ffffff;font-weight:bold;">' . (string) $firstRow['producto'] . '</td>';
                $html .= '<td style="' . $stDominante . 'background-color:' . $colorSubHeader . ';"></td>';
                foreach ($colsProducto as $colLabel) {
                    $html .= '<th style="' . $stColProd . 'background-color:' . $colorHeaderBg . ';color:' . $colorHeaderTxt . ';font-weight:bold;padding:' . $p2h . ';">' . $this->esc((string) $colLabel) . '</th>';
                }
                $html .= '<td style="width:' . $mmTotalArt . 'mm;border-left:2px solid ' . $colorPrimario . ';' . $stMoneyGhost . '"></td>';
                $html .= '<td style="width:' . $mmCosto . 'mm;' . $stMoneyGhost . '"></td>';
                $html .= '<td style="width:' . $mmPrecio . 'mm;' . $stMoneyGhost . '"></td>';
                $html .= '<td style="width:' . $mmTotal . 'mm;' . $stMoneyGhost . '"></td>';
                $html .= '</tr>';

                foreach ($filas as $idx => $row) {
                    $bgFila = ($idx % 2 === 1) ? $colorAltRow : '#ffffff';
                    $totalArticulosFila = (float) ($row['total_articulos'] ?? 0);
                    $precioPromedioFila = $totalArticulosFila > 0 ? ((float) $row['precio_acumulado'] / $totalArticulosFila) : 0;
                    $costoPromedioFila  = $totalArticulosFila > 0 ? ((float) $row['costo_acumulado'] / $totalArticulosFila) : 0;
                    $totalMonetarioFila = (float) ($row['total_monetario'] ?? 0);

                    $html .= '<tr>';
                    $html .= '<td style="' . $stProducto . 'background-color:' . $bgFila . ';border-top:1px dashed ' . $colorBorderTbl . ';">&nbsp;</td>';
                    $html .= '<td style="' . $stDominante . 'background-color:' . $colorSubHeader . ';font-weight:bold;">' . $this->esc((string) $row['dominante']) . '</td>';
                    foreach ($colsProducto as $colKey => $colLabel) {
                        $valor = (float) ($row['cells'][$colKey] ?? 0);
                        if ($valor > 0) {
                            $html .= '<td style="' . $stColProd . 'background-color:' . $colorOkBg . ';color:' . $colorOkTxt . ';font-weight:bold;">' . number_format($valor, 0, '.', ',') . '</td>';
                        } else {
                            $html .= '<td style="' . $stColProd . 'background-color:' . $colorNaBg . ';color:' . $colorNaTxt . ';">—</td>';
                        }
                    }
                    $html .= '<td style="' . $stTotalArt . 'background-color:' . $colorTotalBg . ';font-weight:bold;">' . number_format($totalArticulosFila, 0, '.', ',') . '</td>';
                    $html .= '<td style="' . $stCosto . 'background-color:' . $bgFila . ';font-weight:bold;">' . number_format($costoPromedioFila, 2, '.', ',') . '</td>';
                    $html .= '<td style="' . $stPrecio . 'background-color:' . $bgFila . ';font-weight:bold;">' . number_format($precioPromedioFila, 2, '.', ',') . '</td>';
                    $html .= '<td style="' . $stTotal . 'background-color:' . $colorTotalBg . ';font-weight:bold;">' . number_format($totalMonetarioFila, 2, '.', ',') . '</td>';
                    $html .= '</tr>';
                }
            }

            $html .= '</tbody></table>';
        }

        $html .= $summaryHtml;

        // ── Pie de página ────────────────────────────────────────────────
        $html .= '
        <table style="border-collapse:collapse;margin-top:3px;">
            <tr>
                <td style="border:none;border-top:1px solid ' . $colorBorderTbl . ';padding:3px 0;font-size:7px;color:#94a3b8;">
                    Generado por el sistema de inventario de La I. Suriana &nbsp;&bull;&nbsp; ' . $this->esc(now()->format('d/m/Y H:i:s')) . ' &nbsp;&bull;&nbsp; Documento de uso interno.
                </td>
            </tr>
        </table>';

        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false, false);
        $pdf->SetCreator(config('app.name', 'La Suriana Retail'));
        $pdf->SetAuthor((string) ($request->user()?->usr_nombre ?? config('app.name', 'La Suriana Retail')));
        $pdf->SetTitle('Reporte de Entradas Registradas');
        $pdf->SetSubject($sucursalNombre . ' — ' . $almacenNombre);
        $pdf->SetKeywords('entradas, inventario, La Suriana');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(7, 7, 7);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');

        $fileName = 'reporte-entradas-' . now()->format('Ymd-His') . '.pdf';

        if ($registrarAuditoria) {
            $this->auditoriaService->registrarAccion(
                $request,
                'inventario_base.reporte_entradas_pdf',
                'tbl_movimientos_inventario_min',
                (string) ($folios->first() ?? 'SIN-FOLIO'),
                [
                    'folios' => $folios->all(),
                    'total_folios' => $folios->count(),
                    'sucursal_id' => $sucursalId,
                    'almacen_id' => $almacenId,
                    'tipo_entrada' => $tipoEntrada,
                    'referencia' => $referencia,
                    'motivo' => $motivo,
                    'observaciones' => $observaciones,
                    'fecha_captura' => $fechaCaptura,
                    'fecha_emision' => $fechaEmision,
                    'proveedor_id' => $proveedorId,
                    'subtotal' => $subtotalMonetario,
                    'descuento_tipo' => $descuentoTipo,
                    'descuento_valor' => $descuentoValor,
                    'descuento_monto' => $descuentoMonetario,
                    'flete_total' => $fleteTotal,
                    'iva_porcentaje' => $ivaPorcentaje,
                    'iva_total' => $ivaMonetario,
                    'total_documento' => $totalMonetario,
                    'dominante_atr_id' => $dominanteAtrId,
                ]
            );
        }

        return [
            'content' => $pdf->Output('', 'S'),
            'file_name' => $fileName,
        ];
    }

    private function guardarPreferenciaDominante(Request $request, int $productoId, int $sucursalId, int $atributoId): void
    {
        $existeAtributoProducto = DB::table('tbl_producto_atributos_pat')
            ->where('pat_prd_id', $productoId)
            ->where('pat_atr_id', $atributoId)
            ->where('pat_deleted', false)
            ->whereNull('pat_deleted_at')
            ->where('pat_estatus', 'activo')
            ->exists();

        if (!$existeAtributoProducto) {
            throw ValidationException::withMessages([
                'dominante_atr_id' => 'La variable dominante seleccionada no pertenece al producto.',
            ]);
        }

        $registro = PreferenciaMatrizProducto::query()
            ->where('pmp_prd_id', $productoId)
            ->where('pmp_scl_id', $sucursalId)
            ->lockForUpdate()
            ->first();

        if ($registro) {
            $registro->forceFill([
                'pmp_atr_dominante_id' => $atributoId,
                'pmp_estatus' => 'activo',
                'pmp_updated_by_usr_id' => $request->user()?->usr_id,
            ])->save();
            return;
        }

        PreferenciaMatrizProducto::query()->create([
            'pmp_prd_id' => $productoId,
            'pmp_scl_id' => $sucursalId,
            'pmp_atr_dominante_id' => $atributoId,
            'pmp_estatus' => 'activo',
            'pmp_created_by_usr_id' => $request->user()?->usr_id,
            'pmp_updated_by_usr_id' => $request->user()?->usr_id,
        ]);
    }

    public function registrarEntrada(Request $request, array $datos): MovimientoInventario
    {
        return DB::transaction(function () use ($request, $datos): MovimientoInventario {
            $movimiento = $this->registrarMovimientoInterno(
                request: $request,
                datos: $datos,
                tmiClave: 'inventario.entrada',
                documentoTipo: 'remision',
                signo: 1,
                movimientoOrigenId: null,
                reversaDeId: null,
                esReversa: false,
            );

            $this->auditoriaService->registrarAccion(
                $request,
                'inventario_base.entrada',
                'tbl_movimientos_inventario_min',
                (string) $movimiento->min_id,
                [
                    'min_folio' => $movimiento->min_folio,
                    'min_psk_id' => $movimiento->min_psk_id,
                    'min_scl_id' => $movimiento->min_scl_id,
                    'min_alm_id' => $movimiento->min_alm_id,
                    'min_cantidad' => $movimiento->min_cantidad,
                    'min_documento_referencia' => $movimiento->min_documento_referencia,
                ]
            );

            return $movimiento;
        });
    }

    public function registrarSalida(Request $request, array $datos): MovimientoInventario
    {
        return DB::transaction(fn () => $this->registrarSalidaMovimiento($request, $datos));
    }

    public function registrarSalidaLote(Request $request, array $datos): array
    {
        return DB::transaction(function () use ($request, $datos): array {
            $movimientos = [];

            foreach ($this->normalizarLineasSalida($datos) as $linea) {
                $movimientos[] = $this->registrarSalidaMovimiento($request, array_merge($datos, $linea));
            }

            return ['movimientos' => $movimientos];
        });
    }

    public function cancelarMovimiento(Request $request, int $movimientoId, string $motivo): array
    {
        return DB::transaction(function () use ($request, $movimientoId, $motivo): array {
            $original = MovimientoInventario::query()->lockForUpdate()->findOrFail($movimientoId);

            $this->validarMovimientoCancelableOCorregible($original);

            $this->validarNoRevertido($original->min_id);

            $reversa = $this->registrarMovimientoInterno(
                request: $request,
                datos: [
                    'min_psk_id' => $original->min_psk_id,
                    'min_scl_id' => $original->min_scl_id,
                    'min_alm_id' => $original->min_alm_id,
                    'min_mtv_id' => $original->min_mtv_id,
                    'min_cantidad' => $original->min_cantidad,
                    'min_fecha_movimiento' => now()->toDateTimeString(),
                    'min_documento_referencia' => 'REVERSA-' . $original->min_folio,
                    'min_motivo_texto' => 'Reversa por cancelación: ' . $motivo,
                ],
                tmiClave: ((int) $original->min_signo) > 0 ? 'inventario.salida' : 'inventario.entrada',
                documentoTipo: 'cancelacion',
                signo: ((int) $original->min_signo) * -1,
                movimientoOrigenId: $original->min_id,
                reversaDeId: $original->min_id,
                esReversa: true,
            );

            $original->update([
                'min_estatus' => 'cancelado',
                'min_cancelado_at' => now(),
                'min_cancelado_by_usr_id' => optional($request->user())->usr_id,
                'min_cancelacion_motivo' => $motivo,
                'min_updated_by_usr_id' => optional($request->user())->usr_id,
            ]);

            $this->auditoriaService->registrarAccion(
                $request,
                'inventario_base.cancelar',
                'tbl_movimientos_inventario_min',
                (string) $original->min_id,
                [
                    'min_folio' => $original->min_folio,
                    'reversa_folio' => $reversa->min_folio,
                    'motivo' => $motivo,
                ]
            );

            return ['original' => $original->fresh(), 'reversa' => $reversa];
        });
    }

    public function corregirMovimiento(Request $request, int $movimientoId, string $motivoCorreccion, array $nuevo): array
    {
        return DB::transaction(function () use ($request, $movimientoId, $motivoCorreccion, $nuevo): array {
            $original = MovimientoInventario::query()->lockForUpdate()->findOrFail($movimientoId);
            $original->loadMissing('tipoMovimiento:tmi_id,tmi_clave');

            $this->validarMovimientoCancelableOCorregible($original);
            $this->validarNoRevertido($original->min_id);

            $reversa = $this->registrarMovimientoInterno(
                request: $request,
                datos: [
                    'min_psk_id' => $original->min_psk_id,
                    'min_scl_id' => $original->min_scl_id,
                    'min_alm_id' => $original->min_alm_id,
                    'min_mtv_id' => $original->min_mtv_id,
                    'min_cantidad' => $original->min_cantidad,
                    'min_fecha_movimiento' => now()->toDateTimeString(),
                    'min_documento_referencia' => 'REVERSA-CORR-' . $original->min_folio,
                    'min_motivo_texto' => 'Reversa por corrección: ' . $motivoCorreccion,
                ],
                tmiClave: ((int) $original->min_signo) > 0 ? 'inventario.salida' : 'inventario.entrada',
                documentoTipo: 'correccion',
                signo: ((int) $original->min_signo) * -1,
                movimientoOrigenId: $original->min_id,
                reversaDeId: $original->min_id,
                esReversa: true,
            );

            $original->update([
                'min_estatus' => 'corregido',
                'min_cancelado_at' => now(),
                'min_cancelado_by_usr_id' => optional($request->user())->usr_id,
                'min_cancelacion_motivo' => $motivoCorreccion,
                'min_updated_by_usr_id' => optional($request->user())->usr_id,
            ]);

            $nuevoMovimiento = $this->registrarMovimientoInterno(
                request: $request,
                datos: [
                    'min_psk_id' => $original->min_psk_id,
                    'min_scl_id' => $original->min_scl_id,
                    'min_alm_id' => $original->min_alm_id,
                    'min_mtv_id' => $original->min_mtv_id,
                    'min_cantidad' => $nuevo['min_cantidad'],
                    'min_fecha_movimiento' => $nuevo['min_fecha_movimiento'],
                    'min_documento_referencia' => $nuevo['min_documento_referencia'] ?? null,
                    'min_motivo_texto' => $nuevo['min_motivo_texto'],
                ],
                tmiClave: $original->tipoMovimiento?->tmi_clave ?? (((int) $original->min_signo) > 0 ? 'inventario.entrada' : 'inventario.salida'),
                documentoTipo: $original->min_documento_tipo,
                signo: (int) $original->min_signo,
                movimientoOrigenId: $original->min_id,
                reversaDeId: null,
                esReversa: false,
            );

            $this->auditoriaService->registrarAccion(
                $request,
                'inventario_base.corregir',
                'tbl_movimientos_inventario_min',
                (string) $original->min_id,
                [
                    'min_folio_original' => $original->min_folio,
                    'min_folio_reversa' => $reversa->min_folio,
                    'min_folio_nuevo' => $nuevoMovimiento->min_folio,
                    'motivo' => $motivoCorreccion,
                ]
            );

            return [
                'original' => $original->fresh(),
                'reversa' => $reversa,
                'corregido' => $nuevoMovimiento,
            ];
        });
    }

    public function guardarMinimo(Request $request, array $datos): MinimoInventario
    {
        return DB::transaction(function () use ($request, $datos): MinimoInventario {
            $this->validarAlmacenPerteneceSucursal((int) $datos['mni_scl_id'], (int) $datos['mni_alm_id']);

            $registro = MinimoInventario::query()
                ->lockForUpdate()
                ->where('mni_psk_id', (int) $datos['mni_psk_id'])
                ->where('mni_scl_id', (int) $datos['mni_scl_id'])
                ->where('mni_alm_id', (int) $datos['mni_alm_id'])
                ->first();

            if ($registro) {
                $registro->update([
                    'mni_minimo' => $datos['mni_minimo'],
                    'mni_estatus' => 'activo',
                    'mni_updated_by_usr_id' => optional($request->user())->usr_id,
                ]);
            } else {
                $registro = MinimoInventario::query()->create([
                    'mni_psk_id' => $datos['mni_psk_id'],
                    'mni_scl_id' => $datos['mni_scl_id'],
                    'mni_alm_id' => $datos['mni_alm_id'],
                    'mni_minimo' => $datos['mni_minimo'],
                    'mni_estatus' => 'activo',
                    'mni_created_by_usr_id' => optional($request->user())->usr_id,
                    'mni_updated_by_usr_id' => optional($request->user())->usr_id,
                ]);
            }

            $this->auditoriaService->registrarAccion(
                $request,
                'inventario_base.minimos',
                'tbl_minimos_inventario_mni',
                (string) $registro->mni_id,
                [
                    'mni_psk_id' => $registro->mni_psk_id,
                    'mni_scl_id' => $registro->mni_scl_id,
                    'mni_alm_id' => $registro->mni_alm_id,
                    'mni_minimo' => $registro->mni_minimo,
                ]
            );

            return $registro;
        });
    }

    private function registrarMovimientoInterno(
        Request $request,
        array $datos,
        string $tmiClave,
        string $documentoTipo,
        int $signo,
        ?int $movimientoOrigenId,
        ?int $reversaDeId,
        bool $esReversa,
    ): MovimientoInventario {
        $skuId = (int) $datos['min_psk_id'];
        $sucursalId = (int) $datos['min_scl_id'];
        $almacenId = (int) $datos['min_alm_id'];
        $cantidad = round((float) $datos['min_cantidad'], 2);

        $this->validarAlmacenPerteneceSucursal($sucursalId, $almacenId);

        $tipoMovimiento = $this->obtenerTipoMovimientoPorClave($tmiClave);
        $existencia = $this->obtenerExistenciaBloqueada($skuId, $sucursalId, $almacenId, optional($request->user())->usr_id);

        $antes = (float) $existencia->exa_existencia;
        $despues = round($antes + ($cantidad * $signo), 2);
        $permitirNegativo = (bool) ($datos['min_permitir_negativo'] ?? false);

        if ($despues < 0 && !$permitirNegativo) {
            throw ValidationException::withMessages([
                'min_cantidad' => 'La operación deja inventario negativo y no está permitido.',
            ]);
        }

        $movimiento = MovimientoInventario::query()->create([
            'min_folio' => $this->crearFolio(),
            'min_tmi_id' => $tipoMovimiento->tmi_id,
            'min_psk_id' => $skuId,
            'min_scl_id' => $sucursalId,
            'min_alm_id' => $almacenId,
            'min_prv_id' => $datos['min_prv_id'] ?? null,
            'min_rme_id' => $datos['min_rme_id'] ?? null,
            'min_mtv_id' => $datos['min_mtv_id'] ?? null,
            'min_origen_min_id' => $movimientoOrigenId,
            'min_reversa_de_min_id' => $reversaDeId,
            'min_documento_tipo' => $documentoTipo,
            'min_documento_referencia' => $datos['min_documento_referencia'] ?? null,
            'min_descuento_tipo' => $datos['min_descuento_tipo'] ?? null,
            'min_descuento_valor' => $datos['min_descuento_valor'] ?? null,
            'min_flete_total' => $datos['min_flete_total'] ?? null,
            'min_cantidad' => $cantidad,
            'min_precio_unitario' => $datos['min_precio_unitario'] ?? null,
            'min_subtotal_linea' => $datos['min_subtotal_linea'] ?? null,
            'min_descuento_linea' => $datos['min_descuento_linea'] ?? null,
            'min_flete_linea' => $datos['min_flete_linea'] ?? null,
            'min_iva_porcentaje' => $datos['min_iva_porcentaje'] ?? null,
            'min_iva_linea' => $datos['min_iva_linea'] ?? null,
            'min_total_linea' => $datos['min_total_linea'] ?? null,
            'min_signo' => $signo,
            'min_existencia_antes' => $antes,
            'min_existencia_despues' => $despues,
            'min_motivo_texto' => $datos['min_motivo_texto'] ?? null,
            'min_observaciones' => $datos['min_observaciones'] ?? null,
            'min_estatus' => 'activo',
            'min_es_reversa' => $esReversa,
            'min_fecha_movimiento' => $datos['min_fecha_movimiento'] ?? now(),
            'min_fecha_emision' => $datos['min_fecha_emision'] ?? null,
            'min_created_by_usr_id' => optional($request->user())->usr_id,
            'min_updated_by_usr_id' => optional($request->user())->usr_id,
        ]);

        $existencia->update([
            'exa_existencia' => $despues,
            'exa_estatus' => 'activo',
            'exa_updated_by_usr_id' => optional($request->user())->usr_id,
        ]);

        $this->sincronizarExistenciaSucursal($skuId, $sucursalId, optional($request->user())->usr_id);

        return $movimiento;
    }

    private function registrarSalidaMovimiento(Request $request, array $datos): MovimientoInventario
    {
        $tipoSalida = (string) ($datos['min_documento_tipo'] ?? 'ajuste_manual');
        $tmiClave = $tipoSalida === 'ajuste_manual' ? 'inventario.ajuste' : 'inventario.salida';

        $movimiento = $this->registrarMovimientoInterno(
            request: $request,
            datos: $datos,
            tmiClave: $tmiClave,
            documentoTipo: $tipoSalida,
            signo: -1,
            movimientoOrigenId: null,
            reversaDeId: null,
            esReversa: false,
        );

        $this->auditoriaService->registrarAccion(
            $request,
            'inventario_base.salida',
            'tbl_movimientos_inventario_min',
            (string) $movimiento->min_id,
            [
                'min_folio' => $movimiento->min_folio,
                'min_psk_id' => $movimiento->min_psk_id,
                'min_scl_id' => $movimiento->min_scl_id,
                'min_alm_id' => $movimiento->min_alm_id,
                'min_cantidad' => $movimiento->min_cantidad,
                'min_documento_tipo' => $movimiento->min_documento_tipo,
            ]
        );

        return $movimiento;
    }

    private function normalizarLineasSalida(array $datos): array
    {
        $lineas = collect($datos['lineas'] ?? [])
            ->filter(fn ($linea) => is_array($linea) && ((int) ($linea['min_psk_id'] ?? 0) > 0))
            ->groupBy(fn ($linea) => (int) $linea['min_psk_id'])
            ->map(function (Collection $grupo, int|string $skuId): array {
                return [
                    'min_psk_id' => (int) $skuId,
                    'min_cantidad' => (int) $grupo->sum(fn ($linea) => (int) ($linea['min_cantidad'] ?? 0)),
                ];
            })
            ->filter(fn ($linea) => (int) ($linea['min_cantidad'] ?? 0) > 0)
            ->values()
            ->all();

        if (empty($lineas)) {
            throw ValidationException::withMessages([
                'lineas' => 'Debes agregar al menos un producto para registrar la salida.',
            ]);
        }

        return $lineas;
    }

    private function validarAlmacenPerteneceSucursal(int $sucursalId, int $almacenId): void
    {
        $valido = Almacen::query()
            ->where('alm_id', $almacenId)
            ->where('alm_scl_id', $sucursalId)
            ->where('alm_estatus', 'activo')
            ->exists();

        if (!$valido) {
            throw ValidationException::withMessages([
                'min_alm_id' => 'El almacén no pertenece a la sucursal indicada o no está activo.',
            ]);
        }
    }

    private function obtenerTipoMovimientoPorClave(string $clave): TipoMovimientoInventario
    {
        $tipo = TipoMovimientoInventario::query()
            ->where('tmi_clave', $clave)
            ->where('tmi_estatus', 'activo')
            ->first();

        if (!$tipo) {
            throw ValidationException::withMessages([
                'tipo_movimiento' => 'No existe configuración activa para el tipo de movimiento solicitado.',
            ]);
        }

        return $tipo;
    }

    private function obtenerExistenciaBloqueada(int $skuId, int $sucursalId, int $almacenId, ?int $usuarioId): ExistenciaAlmacen
    {
        $existencia = ExistenciaAlmacen::query()
            ->where('exa_psk_id', $skuId)
            ->where('exa_scl_id', $sucursalId)
            ->where('exa_alm_id', $almacenId)
            ->lockForUpdate()
            ->first();

        if ($existencia) {
            return $existencia;
        }

        try {
            ExistenciaAlmacen::query()->create([
                'exa_psk_id' => $skuId,
                'exa_scl_id' => $sucursalId,
                'exa_alm_id' => $almacenId,
                'exa_existencia' => 0,
                'exa_estatus' => 'activo',
                'exa_created_by_usr_id' => $usuarioId,
                'exa_updated_by_usr_id' => $usuarioId,
            ]);
        } catch (\Throwable) {
            // Si hubo carrera por índice único, se vuelve a leer con bloqueo.
        }

        return ExistenciaAlmacen::query()
            ->where('exa_psk_id', $skuId)
            ->where('exa_scl_id', $sucursalId)
            ->where('exa_alm_id', $almacenId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function validarMovimientoCancelableOCorregible(MovimientoInventario $movimiento): void
    {
        if ($movimiento->min_estatus !== 'activo') {
            throw ValidationException::withMessages([
                'movimiento' => 'El movimiento ya fue cancelado o corregido.',
            ]);
        }

        if ((bool) $movimiento->min_es_reversa) {
            throw ValidationException::withMessages([
                'movimiento' => 'No se puede cancelar ni corregir un movimiento de reversa.',
            ]);
        }
    }

    private function validarNoRevertido(int $movimientoId): void
    {
        $existeReversa = MovimientoInventario::query()
            ->where('min_reversa_de_min_id', $movimientoId)
            ->exists();

        if ($existeReversa) {
            throw ValidationException::withMessages([
                'movimiento' => 'El movimiento ya tiene una reversa registrada.',
            ]);
        }
    }

    private function crearFolio(): string
    {
        return 'INV-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6));
    }

    private function crearFolioRecepcion(): string
    {
        return 'RCM-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6));
    }

    private function fechaActualSistema(): \Illuminate\Support\Carbon
    {
        return now(config('app.timezone', 'America/Mexico_City'));
    }

    private function resolverRecepcionEditable(int $recepcionId): ?RecepcionMercancia
    {
        if ($recepcionId <= 0) {
            return null;
        }

        return RecepcionMercancia::query()->lockForUpdate()->findOrFail($recepcionId);
    }

    private function validarRecepcionEditable(RecepcionMercancia $recepcion): void
    {
        if ($recepcion->rme_estado === RecepcionMercancia::ESTADO_FINALIZADO) {
            throw ValidationException::withMessages([
                'recepcion' => 'La recepción ya fue finalizada y solo puede consultarse.',
            ]);
        }

        if ($recepcion->rme_estado === RecepcionMercancia::ESTADO_CANCELADO) {
            throw ValidationException::withMessages([
                'recepcion' => 'La recepción ya fue cancelada y no puede editarse.',
            ]);
        }
    }

    private function mapearDatosRecepcion(array $datos): array
    {
        return [
            'rme_scl_id' => !empty($datos['min_scl_id']) ? (int) $datos['min_scl_id'] : null,
            'rme_alm_id' => !empty($datos['min_alm_id']) ? (int) $datos['min_alm_id'] : null,
            'rme_prv_id' => !empty($datos['min_prv_id']) ? (int) $datos['min_prv_id'] : null,
            'rme_dominante_atr_id' => !empty($datos['dominante_atr_id']) ? (int) $datos['dominante_atr_id'] : null,
            'rme_documento_tipo' => $datos['min_documento_tipo'] ?? null,
            'rme_documento_referencia' => $datos['min_documento_referencia'] ?? null,
            'rme_descuento_tipo' => $datos['min_descuento_tipo'] ?? 'ninguno',
            'rme_descuento_valor' => isset($datos['min_descuento_valor']) ? round((float) $datos['min_descuento_valor'], 2) : 0,
            'rme_flete_total' => isset($datos['min_flete_total']) ? round((float) $datos['min_flete_total'], 2) : 0,
            'rme_iva_porcentaje' => isset($datos['min_iva_porcentaje']) ? round((float) $datos['min_iva_porcentaje'], 2) : 0,
            'rme_fecha_captura' => $datos['min_fecha_movimiento'] ?? $this->fechaActualSistema(),
            'rme_fecha_emision' => $datos['min_fecha_emision'] ?? null,
            'rme_motivo_texto' => $datos['min_motivo_texto'] ?? null,
            'rme_observaciones' => $datos['min_observaciones'] ?? null,
            'rme_payload' => is_array($datos['payload'] ?? null) ? $datos['payload'] : null,
        ];
    }

    private function sincronizarDetalleRecepcion(RecepcionMercancia $recepcion, array $lineas, ?int $usuarioId): void
    {
        DB::table('tbl_recepcion_mercancia_detalle_rmd')
            ->where('rmd_rme_id', $recepcion->rme_id)
            ->delete();

        $normalizadas = $this->normalizarLineasRecepcion($lineas, false);
        foreach ($normalizadas as $linea) {
            RecepcionMercanciaDetalle::query()->create([
                'rmd_rme_id' => $recepcion->rme_id,
                'rmd_prd_id' => $linea['prd_id'] ?: null,
                'rmd_psk_id' => $linea['min_psk_id'],
                'rmd_cantidad' => $linea['min_cantidad'],
                'rmd_precio_unitario' => $linea['min_precio_unitario'],
                'rmd_payload' => $linea['payload'] ?? null,
                'rmd_created_by_usr_id' => $usuarioId,
                'rmd_updated_by_usr_id' => $usuarioId,
            ]);
        }
    }

    private function normalizarLineasRecepcion(array $lineas, bool $soloPositivas): Collection
    {
        return collect($lineas)
            ->map(function ($linea) {
                return [
                    'prd_id' => (int) ($linea['prd_id'] ?? 0),
                    'min_psk_id' => (int) ($linea['min_psk_id'] ?? 0),
                    'min_cantidad' => round((float) ($linea['min_cantidad'] ?? 0), 2),
                    'min_precio_unitario' => round((float) ($linea['min_precio_unitario'] ?? 0), 2),
                    'payload' => is_array($linea['payload'] ?? null) ? $linea['payload'] : null,
                ];
            })
            ->filter(fn ($linea) => $linea['prd_id'] > 0 && $linea['min_psk_id'] > 0)
            ->when($soloPositivas, fn ($items) => $items->filter(fn ($linea) => $linea['min_cantidad'] > 0))
            ->values();
    }

    private function agruparLineasRecepcionPorProducto(Collection $lineas, array $datos): array
    {
        $subtotalGlobal = round((float) $lineas->sum(fn ($linea) => ((float) $linea['min_cantidad']) * ((float) $linea['min_precio_unitario'])), 2);
        $totalPiezas = max(0.01, (float) $lineas->sum(fn ($linea) => (float) $linea['min_cantidad']));
        $descuentoTipo = (string) ($datos['min_descuento_tipo'] ?? 'ninguno');
        $descuentoValor = round((float) ($datos['min_descuento_valor'] ?? 0), 2);
        $descuentoImporte = $descuentoTipo === 'importe' ? min($subtotalGlobal, $descuentoValor) : 0.0;
        $fleteTotal = round((float) ($datos['min_flete_total'] ?? 0), 2);
        $descuentoAsignado = 0.0;
        $fleteAsignado = 0.0;
        $grupos = $lineas->groupBy('prd_id')->values();
        $ultimoGrupo = max(0, $grupos->count() - 1);

        return $grupos
            ->map(function (Collection $lineasProducto, int $idx) use (
                $datos,
                $subtotalGlobal,
                $totalPiezas,
                $descuentoTipo,
                $descuentoValor,
                $descuentoImporte,
                $fleteTotal,
                $ultimoGrupo,
                &$descuentoAsignado,
                &$fleteAsignado,
            ) {
                $productoId = (int) ($lineasProducto->first()['prd_id'] ?? 0);
                $subtotalProducto = round((float) $lineasProducto->sum(fn ($linea) => ((float) $linea['min_cantidad']) * ((float) $linea['min_precio_unitario'])), 2);
                $cantidadProducto = (float) $lineasProducto->sum(fn ($linea) => (float) $linea['min_cantidad']);
                $proporcion = $subtotalGlobal > 0
                    ? ($subtotalProducto / max(0.01, $subtotalGlobal))
                    : ($cantidadProducto / $totalPiezas);
                $descuentoGrupo = $descuentoTipo === 'importe'
                    ? ($idx === $ultimoGrupo ? round($descuentoImporte - $descuentoAsignado, 2) : round($descuentoImporte * $proporcion, 2))
                    : $descuentoValor;
                $fleteGrupo = $idx === $ultimoGrupo
                    ? round($fleteTotal - $fleteAsignado, 2)
                    : round($fleteTotal * $proporcion, 2);
                if ($descuentoTipo === 'importe') {
                    $descuentoAsignado += $descuentoGrupo;
                }
                $fleteAsignado += $fleteGrupo;

                return [
                    'prd_id' => (int) $productoId,
                    'min_scl_id' => (int) $datos['min_scl_id'],
                    'min_alm_id' => (int) $datos['min_alm_id'],
                    'min_fecha_movimiento' => $datos['min_fecha_movimiento'],
                    'min_fecha_emision' => $datos['min_fecha_emision'] ?? null,
                    'min_documento_tipo' => $datos['min_documento_tipo'] ?? 'entrada_normal',
                    'min_documento_referencia' => $datos['min_documento_referencia'] ?? null,
                    'min_motivo_texto' => $datos['min_motivo_texto'] ?? 'Recepción de mercancía manual',
                    'min_observaciones' => $datos['min_observaciones'] ?? null,
                    'min_prv_id' => $datos['min_prv_id'] ?? null,
                    'min_descuento_tipo' => $descuentoTipo,
                    'min_descuento_valor' => $descuentoGrupo,
                    'min_flete_total' => $fleteGrupo,
                    'min_iva_porcentaje' => $datos['min_iva_porcentaje'] ?? 0,
                    'dominante_atr_id' => $datos['dominante_atr_id'] ?? null,
                    'lineas' => $lineasProducto->map(fn ($linea) => [
                        'min_psk_id' => $linea['min_psk_id'],
                        'min_cantidad' => $linea['min_cantidad'],
                        'min_precio_unitario' => $linea['min_precio_unitario'],
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function calcularEconomicosRecepcion(
        Collection $movimientos,
        string $descuentoTipo,
        float $descuentoValor,
        float $fleteTotal,
        float $ivaPorcentaje,
    ): array {
        $subtotal = round((float) $movimientos->sum(function (MovimientoInventario $movimiento): float {
            $subtotalLinea = (float) ($movimiento->min_subtotal_linea ?? 0);
            if ($subtotalLinea > 0) {
                return $subtotalLinea;
            }

            return ((float) $movimiento->min_cantidad) * ((float) ($movimiento->min_precio_unitario ?? 0));
        }), 2);
        $descuento = 0.0;
        if ($descuentoTipo === 'porcentaje') {
            $descuento = round($subtotal * ($descuentoValor / 100), 2);
        } elseif ($descuentoTipo === 'importe') {
            $descuento = min($subtotal, $descuentoValor);
        }

        $targetBase = max(0, round($subtotal - $descuento + $fleteTotal, 2));
        $targetIva = round($targetBase * (max(0, $ivaPorcentaje) / 100), 2);

        $lineas = [];
        foreach ($movimientos as $movimiento) {
            $subtotalLinea = round((float) ($movimiento->min_subtotal_linea ?? 0), 2);
            if ($subtotalLinea <= 0) {
                $subtotalLinea = round(((float) $movimiento->min_cantidad) * ((float) ($movimiento->min_precio_unitario ?? 0)), 2);
            }

            $lineas[(int) $movimiento->min_id] = [
                'subtotal' => $subtotalLinea,
                'descuento' => 0.0,
                'flete' => 0.0,
                'iva' => 0.0,
                'total' => $subtotalLinea,
            ];
        }

        return [
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'flete' => $fleteTotal,
            'iva' => $targetIva,
            'total' => round($targetBase + $targetIva, 2),
            'lineas' => $lineas,
        ];
    }

    private function registrarEntradaProductoLote(
        Request $request,
        array $datos,
        ?int $recepcionId = null,
        bool $registrarAuditoria = true,
    ): array {
        $producto = Producto::query()
            ->with([
                'atributos' => fn ($q) => $q
                    ->where('atr_deleted', false)
                    ->whereNull('atr_deleted_at')
                    ->where('atr_estatus', 'activo')
                    ->orderBy('atr_nombre'),
            ])
            ->where('prd_deleted', false)
            ->whereNull('prd_deleted_at')
            ->where('prd_estatus', 'activo')
            ->findOrFail((int) $datos['prd_id']);

        $lineas = collect($datos['lineas'] ?? [])
            ->map(fn ($linea) => [
                'min_psk_id' => (int) ($linea['min_psk_id'] ?? 0),
                'min_cantidad' => round((float) ($linea['min_cantidad'] ?? 0), 2),
                'min_precio_unitario' => round((float) ($linea['min_precio_unitario'] ?? 0), 2),
            ])
            ->filter(fn ($linea) => $linea['min_cantidad'] > 0)
            ->values();

        $skuIdsPermitidos = ProductoSku::query()
            ->where('psk_prd_id', $producto->prd_id)
            ->where('psk_deleted', false)
            ->whereNull('psk_deleted_at')
            ->where('psk_estatus', 'activo')
            ->pluck('psk_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $noPermitidos = $lineas
            ->pluck('min_psk_id')
            ->reject(fn ($skuId) => in_array((int) $skuId, $skuIdsPermitidos, true))
            ->values()
            ->all();

        if (!empty($noPermitidos)) {
            throw ValidationException::withMessages([
                'lineas' => 'Se detectaron variantes que no pertenecen al producto seleccionado.',
            ]);
        }

        $dominanteAtrId = Arr::has($datos, 'dominante_atr_id') ? (int) $datos['dominante_atr_id'] : null;
        $guardarPredeterminado = filter_var($datos['dominante_guardar_predeterminado'] ?? false, FILTER_VALIDATE_BOOL);
        if ($guardarPredeterminado && $producto->prd_tipo === 'variable') {
            if (empty($dominanteAtrId)) {
                $dominanteAtrId = (int) ($producto->atributos->first()?->atr_id ?? 0);
            }

            if ($dominanteAtrId > 0) {
                $this->guardarPreferenciaDominante($request, (int) $producto->prd_id, (int) $datos['min_scl_id'], $dominanteAtrId);
            }
        }

        $tipoEntrada = (string) ($datos['min_documento_tipo'] ?? 'inventario_inicial');
        $documentoTipo = $this->resolverDocumentoTipoEntrada($tipoEntrada);

        $movimientos = [];
        $subtotal = round((float) $lineas->sum(fn ($linea) => ((float) $linea['min_cantidad']) * ((float) $linea['min_precio_unitario'])), 2);
        $descuentoTipo = (string) ($datos['min_descuento_tipo'] ?? 'ninguno');
        $descuentoValor = round((float) ($datos['min_descuento_valor'] ?? 0), 2);
        $fleteTotal = round((float) ($datos['min_flete_total'] ?? 0), 2);
        $ivaPorcentaje = max(0, round((float) ($datos['min_iva_porcentaje'] ?? 16), 2));

        $descuentoMonto = 0.0;
        if ($descuentoTipo === 'porcentaje') {
            $descuentoMonto = round($subtotal * ($descuentoValor / 100), 2);
        } elseif ($descuentoTipo === 'importe') {
            $descuentoMonto = min($subtotal, $descuentoValor);
        }
        $baseSinIva = max(0, round($subtotal - $descuentoMonto + $fleteTotal, 2));
        $ivaTotal = $tipoEntrada === 'compra_factura'
            ? round($baseSinIva * ($ivaPorcentaje / 100), 2)
            : 0.0;
        $totalDocumento = round($baseSinIva + $ivaTotal, 2);

        $totalPiezas = max(0.01, (float) $lineas->sum(fn ($linea) => (float) $linea['min_cantidad']));
        foreach ($lineas as $linea) {
            $subtotalLinea = round(((float) $linea['min_cantidad']) * ((float) $linea['min_precio_unitario']), 2);
            $proporcion = $subtotal > 0
                ? ($subtotalLinea / max(0.01, $subtotal))
                : (((float) $linea['min_cantidad']) / $totalPiezas);
            $descuentoLinea = round($descuentoMonto * $proporcion, 2);
            $fleteLinea = round($fleteTotal * $proporcion, 2);
            $baseLinea = round(max(0, $subtotalLinea - $descuentoLinea + $fleteLinea), 2);
            $ivaLinea = $tipoEntrada === 'compra_factura'
                ? round($baseLinea * ($ivaPorcentaje / 100), 2)
                : 0.0;
            $totalLinea = round($baseLinea + $ivaLinea, 2);

            $movimientos[] = $this->registrarMovimientoInterno(
                request: $request,
                datos: [
                    'min_psk_id' => $linea['min_psk_id'],
                    'min_scl_id' => $datos['min_scl_id'],
                    'min_alm_id' => $datos['min_alm_id'],
                    'min_cantidad' => $linea['min_cantidad'],
                    'min_fecha_movimiento' => $datos['min_fecha_movimiento'],
                    'min_fecha_emision' => $datos['min_fecha_emision'] ?? null,
                    'min_documento_referencia' => $datos['min_documento_referencia'] ?? null,
                    'min_descuento_tipo' => $descuentoTipo,
                    'min_descuento_valor' => $descuentoValor,
                    'min_flete_total' => $fleteTotal,
                    'min_motivo_texto' => $datos['min_motivo_texto'],
                    'min_observaciones' => $datos['min_observaciones'] ?? null,
                    'min_prv_id' => $datos['min_prv_id'] ?? null,
                    'min_rme_id' => $recepcionId,
                    'min_precio_unitario' => $linea['min_precio_unitario'],
                    'min_subtotal_linea' => $subtotalLinea,
                    'min_descuento_linea' => $descuentoLinea,
                    'min_flete_linea' => $fleteLinea,
                    'min_iva_porcentaje' => $ivaPorcentaje,
                    'min_iva_linea' => $ivaLinea,
                    'min_total_linea' => $totalLinea,
                ],
                tmiClave: 'inventario.entrada',
                documentoTipo: $documentoTipo,
                signo: 1,
                movimientoOrigenId: null,
                reversaDeId: null,
                esReversa: false,
            );
        }

        if ($registrarAuditoria) {
            $eventoAuditoria = $tipoEntrada === 'inventario_inicial'
                ? 'inventario_base.inicial.masivo'
                : 'inventario_base.entrada.masivo';

            $this->auditoriaService->registrarAccion(
                $request,
                $eventoAuditoria,
                'tbl_movimientos_inventario_min',
                (string) $producto->prd_id,
                [
                    'producto' => $producto->prd_codigo,
                    'lineas' => count($movimientos),
                    'sucursal' => (int) $datos['min_scl_id'],
                    'almacen' => (int) $datos['min_alm_id'],
                    'tipo_entrada' => $tipoEntrada,
                    'proveedor_id' => (int) ($datos['min_prv_id'] ?? 0),
                    'fecha_emision' => (string) ($datos['min_fecha_emision'] ?? ''),
                    'subtotal' => $subtotal,
                    'descuento_tipo' => $descuentoTipo,
                    'descuento_valor' => $descuentoValor,
                    'descuento_monto' => $descuentoMonto,
                    'flete_total' => $fleteTotal,
                    'iva_porcentaje' => $ivaPorcentaje,
                    'iva_total' => $ivaTotal,
                    'total_documento' => $totalDocumento,
                    'recepcion_id' => $recepcionId,
                ]
            );
        }

        return [
            'total' => count($movimientos),
            'folios' => collect($movimientos)->map(fn ($mov) => $mov->min_folio)->values()->all(),
        ];
    }

    private function resolverDocumentoTipoEntrada(string $tipoEntrada): string
    {
        return match ($tipoEntrada) {
            'compra_remision' => 'remision',
            'compra_factura' => 'factura',
            'entrada_normal' => 'entrada_normal',
            default => 'inventario_inicial',
        };
    }

    private function sincronizarExistenciaSucursal(int $skuId, int $sucursalId, ?int $usuarioId): void
    {
        $total = (float) ExistenciaAlmacen::query()
            ->where('exa_psk_id', $skuId)
            ->where('exa_scl_id', $sucursalId)
            ->sum('exa_existencia');

        $existenciaSucursal = ExistenciaSucursal::query()
            ->where('exs_psk_id', $skuId)
            ->where('exs_scl_id', $sucursalId)
            ->lockForUpdate()
            ->first();

        if ($existenciaSucursal) {
            $existenciaSucursal->update([
                'exs_existencia' => $total,
                'exs_estatus' => 'activo',
                'exs_updated_by_usr_id' => $usuarioId,
            ]);

            return;
        }

        ExistenciaSucursal::query()->create([
            'exs_psk_id' => $skuId,
            'exs_scl_id' => $sucursalId,
            'exs_existencia' => $total,
            'exs_estatus' => 'activo',
            'exs_created_by_usr_id' => $usuarioId,
            'exs_updated_by_usr_id' => $usuarioId,
        ]);
    }

    private function resolverPeriodoKardexDetalle(string $periodo, array $filtros = []): array
    {
        $hoy = now();

        if ($periodo === 'rango') {
            $fechaInicio = trim((string) ($filtros['fecha_inicio'] ?? ''));
            $fechaFin = trim((string) ($filtros['fecha_fin'] ?? ''));

            if ($fechaInicio === '' || $fechaFin === '') {
                throw ValidationException::withMessages([
                    'fecha_inicio' => 'Debes capturar fecha inicial y final para el rango.',
                ]);
            }

            $inicio = Carbon::parse($fechaInicio)->startOfDay();
            $fin = Carbon::parse($fechaFin)->endOfDay();

            if ($inicio->gt($fin)) {
                throw ValidationException::withMessages([
                    'fecha_inicio' => 'La fecha inicial no puede ser mayor a la fecha final.',
                ]);
            }

            return [$inicio, $fin];
        }

        return match ($periodo) {
            'hoy' => [$hoy->copy()->startOfDay(), $hoy->copy()->endOfDay()],
            'ayer' => [$hoy->copy()->subDay()->startOfDay(), $hoy->copy()->subDay()->endOfDay()],
            'esta_semana' => [$hoy->copy()->startOfWeek(), $hoy->copy()->endOfDay()],
            'ultimos_3_meses' => [$hoy->copy()->subMonthsNoOverflow(2)->startOfMonth(), $hoy->copy()->endOfDay()],
            'ultimos_6_meses' => [$hoy->copy()->subMonthsNoOverflow(5)->startOfMonth(), $hoy->copy()->endOfDay()],
            'este_anio' => [$hoy->copy()->startOfYear(), $hoy->copy()->endOfDay()],
            'ultimos_3_anios' => [$hoy->copy()->subYearsNoOverflow(2)->startOfYear(), $hoy->copy()->endOfDay()],
            default => [$hoy->copy()->startOfMonth(), $hoy->copy()->endOfDay()],
        };
    }

    private function construirDatosRecepcionTermicaDirecta(int $recepcionId): array
    {
        $recepcion = RecepcionMercancia::query()
            ->with(['sucursal:scl_id,scl_nombre', 'almacen:alm_id,alm_nombre', 'proveedor:prv_id,prv_nombre_empresa'])
            ->findOrFail($recepcionId);

        $movimientos = MovimientoInventario::query()
            ->with([
                'sku:psk_id,psk_prd_id,psk_codigo,psk_nombre',
                'sku.producto:prd_id,prd_codigo,prd_nombre',
                'sku.valoresAtributo' => fn ($q) => $q
                    ->where('vat_deleted', false)
                    ->whereNull('vat_deleted_at')
                    ->where('vat_estatus', 'activo')
                    ->with(['atributo:atr_id,atr_nombre'])
                    ->orderBy('vat_valor'),
            ])
            ->where('min_rme_id', $recepcionId)
            ->where('min_deleted', false)
            ->whereNull('min_deleted_at')
            ->where('min_estatus', 'activo')
            ->where('min_signo', '>', 0)
            ->orderBy('min_id')
            ->get();

        if ($movimientos->isEmpty()) {
            throw ValidationException::withMessages([
                'recepcion' => 'La recepcion no tiene movimientos definitivos para impresion directa.',
            ]);
        }

        $columnasMap = [];
        $filasMap = [];
        foreach ($movimientos as $movimiento) {
            $sku = $movimiento->sku;
            $producto = $sku?->producto;
            if (!$sku || !$producto) {
                continue;
            }

            $color = 'GENERAL';
            $talla = (string) ($sku->psk_codigo ?? 'SKU');
            foreach ($sku->valoresAtributo as $valor) {
                $atrNombre = (string) ($valor->atributo?->atr_nombre ?? '');
                $atrValor = (string) ($valor->vat_valor ?? '-');
                if ($this->esAtributoColor($atrNombre)) {
                    $color = $atrValor;
                    continue;
                }
                if ($this->esAtributoTalla($atrNombre)) {
                    $talla = $atrValor;
                }
            }

            $colKey = $talla !== '' ? $talla : (string) ($sku->psk_codigo ?? 'SKU');
            $columnasMap[$colKey] = $colKey;

            $rowKey = (int) $producto->prd_id . '|' . $color;
            if (!isset($filasMap[$rowKey])) {
                $filasMap[$rowKey] = [
                    'producto' => (string) ($producto->prd_nombre ?? $sku->psk_nombre ?? 'Producto'),
                    'codigo' => (string) ($producto->prd_codigo ?? $sku->psk_codigo ?? ''),
                    'color' => $color,
                    'cells' => [],
                    'total' => 0.0,
                ];
            }

            $cantidad = (float) $movimiento->min_cantidad;
            $filasMap[$rowKey]['cells'][$colKey] = (float) ($filasMap[$rowKey]['cells'][$colKey] ?? 0) + $cantidad;
            $filasMap[$rowKey]['total'] += $cantidad;
        }

        uasort($columnasMap, fn ($a, $b) => strnatcasecmp((string) $a, (string) $b));
        uasort($filasMap, function (array $a, array $b): int {
            $cmp = strnatcasecmp((string) $a['producto'], (string) $b['producto']);
            return $cmp !== 0 ? $cmp : strnatcasecmp((string) $a['color'], (string) $b['color']);
        });

        $subtotalMonetario = round((float) $movimientos->sum('min_subtotal_linea'), 2);
        $descuentoMonetario = round((float) $movimientos->sum('min_descuento_linea'), 2);
        $fleteTotal = round((float) ($recepcion->rme_flete_total ?? $movimientos->first()->min_flete_total ?? 0), 2);
        $ivaMonetario = round((float) $movimientos->sum('min_iva_linea'), 2);
        $totalMonetario = round((float) $movimientos->sum('min_total_linea'), 2);
        $totalArticulos = (float) $movimientos->sum('min_cantidad');
        $tipoEntrada = (string) ($recepcion->rme_documento_tipo ?? $movimientos->first()->min_documento_tipo ?? 'entrada_normal');
        $tipoEntradaLabel = match ($tipoEntrada) {
            'inventario_inicial' => 'Entrada normal',
            'entrada_normal' => 'Entrada normal',
            'compra_remision' => 'Compra remision',
            'compra_factura' => 'Compra factura',
            default => Str::headline(str_replace('_', ' ', $tipoEntrada)),
        };

        return [
            'folio' => (string) ($recepcion->rme_folio ?? ('RME-' . $recepcionId)),
            'fecha' => optional($recepcion->rme_fecha_captura ?? $movimientos->first()->min_fecha_movimiento)->format('d/m/Y H:i') ?? 'N/D',
            'sucursal' => (string) ($recepcion->sucursal?->scl_nombre ?? 'N/D'),
            'almacen' => (string) ($recepcion->almacen?->alm_nombre ?? 'N/D'),
            'proveedor' => (string) ($recepcion->proveedor?->prv_nombre_empresa ?? 'N/D'),
            'referencia' => trim((string) ($recepcion->rme_documento_referencia ?? $movimientos->first()->min_documento_referencia ?? '')),
            'observaciones' => trim((string) ($recepcion->rme_observaciones ?? $movimientos->first()->min_observaciones ?? '')),
            'tipo' => $tipoEntradaLabel,
            'filas' => array_values($filasMap),
            'column_groups' => array_chunk(array_values($columnasMap), 4),
            'total_pages' => max(1, count(array_chunk(array_values($columnasMap), 4))),
            'subtotal' => $subtotalMonetario,
            'descuento' => $descuentoMonetario,
            'flete' => $fleteTotal,
            'iva' => $ivaMonetario,
            'total' => $totalMonetario,
            'total_articulos' => $totalArticulos,
        ];
    }

    private function construirPayloadEscPosRecepcion(array $datos): string
    {
        $payload = '';
        $pages = $datos['column_groups'];
        $totalPages = (int) $datos['total_pages'];

        foreach ($pages as $pageIndex => $cols) {
            $image = $this->renderRecepcionStripImage($datos, $cols, $pageIndex + 1, $totalPages);
            try {
                $payload .= "\x1B@\x1Ba\x00" . $this->escposRasterFromImage($image) . "\n\n";
            } finally {
                imagedestroy($image);
            }
        }

        $payload .= "\n\n\n\n\x1DV\x00";

        return $payload;
    }

    private function enviarPayloadRawWindows(string $printerName, string $payload): void
    {
        $dataPath = tempnam(sys_get_temp_dir(), 'rme-raw-');
        $scriptBase = tempnam(sys_get_temp_dir(), 'rme-ps-');
        if ($dataPath === false || $scriptBase === false) {
            throw ValidationException::withMessages([
                'printer' => 'No fue posible preparar la impresion termica.',
            ]);
        }

        $scriptPath = $scriptBase . '.ps1';
        file_put_contents($dataPath, $payload);
        file_put_contents($scriptPath, <<<'PS1'
param([string]$PrinterName,[string]$Path)
$signature = @"
using System;
using System.Runtime.InteropServices;
public class RawPrinterHelper {
  [StructLayout(LayoutKind.Sequential, CharSet=CharSet.Unicode)]
  public class DOCINFOA {
    [MarshalAs(UnmanagedType.LPWStr)] public string pDocName;
    [MarshalAs(UnmanagedType.LPWStr)] public string pOutputFile;
    [MarshalAs(UnmanagedType.LPWStr)] public string pDataType;
  }
  [DllImport("winspool.drv", EntryPoint="OpenPrinterW", SetLastError=true, CharSet=CharSet.Unicode)]
  public static extern bool OpenPrinter(string pPrinterName, out IntPtr phPrinter, IntPtr pDefault);
  [DllImport("winspool.drv", SetLastError=true)] public static extern bool ClosePrinter(IntPtr hPrinter);
  [DllImport("winspool.drv", SetLastError=true, CharSet=CharSet.Unicode)]
  public static extern bool StartDocPrinter(IntPtr hPrinter, Int32 Level, [In, MarshalAs(UnmanagedType.LPStruct)] DOCINFOA pDocInfo);
  [DllImport("winspool.drv", SetLastError=true)] public static extern bool EndDocPrinter(IntPtr hPrinter);
  [DllImport("winspool.drv", SetLastError=true)] public static extern bool StartPagePrinter(IntPtr hPrinter);
  [DllImport("winspool.drv", SetLastError=true)] public static extern bool EndPagePrinter(IntPtr hPrinter);
  [DllImport("winspool.drv", SetLastError=true)] public static extern bool WritePrinter(IntPtr hPrinter, byte[] pBytes, Int32 dwCount, out Int32 dwWritten);
}
"@
Add-Type -TypeDefinition $signature -Language CSharp
$bytes = [System.IO.File]::ReadAllBytes($Path)
$hPrinter = [IntPtr]::Zero
$docInfo = New-Object RawPrinterHelper+DOCINFOA
$docInfo.pDocName = "Recepcion termica"
$docInfo.pDataType = "RAW"
if (-not [RawPrinterHelper]::OpenPrinter($PrinterName, [ref]$hPrinter, [IntPtr]::Zero)) { throw "No se pudo abrir la impresora '$PrinterName'." }
try {
  if (-not [RawPrinterHelper]::StartDocPrinter($hPrinter, 1, $docInfo)) { throw "No se pudo iniciar el documento." }
  try {
    if (-not [RawPrinterHelper]::StartPagePrinter($hPrinter)) { throw "No se pudo iniciar la pagina." }
    try {
      $written = 0
      if (-not [RawPrinterHelper]::WritePrinter($hPrinter, $bytes, $bytes.Length, [ref]$written)) { throw "No se pudieron enviar los datos." }
      if ($written -ne $bytes.Length) { throw "Se enviaron $written de $($bytes.Length) bytes." }
    } finally { [void][RawPrinterHelper]::EndPagePrinter($hPrinter) }
  } finally { [void][RawPrinterHelper]::EndDocPrinter($hPrinter) }
} finally { [void][RawPrinterHelper]::ClosePrinter($hPrinter) }
PS1);

        $command = 'powershell -NoProfile -ExecutionPolicy Bypass -File '
            . escapeshellarg($scriptPath)
            . ' -PrinterName '
            . escapeshellarg($printerName)
            . ' -Path '
            . escapeshellarg($dataPath)
            . ' 2>&1';
        exec($command, $output, $exitCode);
        @unlink($dataPath);
        @unlink($scriptPath);
        @unlink($scriptBase);

        if ($exitCode !== 0) {
            throw ValidationException::withMessages([
                'printer' => trim(implode("\n", $output)) ?: 'No fue posible imprimir directamente en la termica.',
            ]);
        }
    }

    private function thermalColumnsHeader(array $cols): string
    {
        $line = '';
        foreach ($cols as $col) {
            $line .= $this->thermalPad((string) $col, 4, STR_PAD_LEFT);
        }
        return $line;
    }

    private function thermalPad(string $value, int $width, int $padType = STR_PAD_RIGHT): string
    {
        $clean = $this->thermalSlice($value, $width, 0);
        $len = strlen($clean);
        return $len >= $width ? $clean : str_pad($clean, $width, ' ', $padType);
    }

    private function thermalSlice(string $value, int $width, int $offset = 0): string
    {
        return substr($this->thermalAscii($value), $offset, $width);
    }

    private function thermalAscii(string $value): string
    {
        $ascii = Str::ascii($value);
        return preg_replace('/[^\x20-\x7E]/', ' ', $ascii) ?? '';
    }

    private function escposText(string $value): string
    {
        return $value;
    }

    private function escposAlign(string $align): string
    {
        return match (strtoupper($align)) {
            'C' => "\x1Ba\x01",
            'R' => "\x1Ba\x02",
            default => "\x1Ba\x00",
        };
    }

    private function escposEmphasis(bool $enabled): string
    {
        return $enabled ? "\x1BE\x01" : "\x1BE\x00";
    }

    private function renderRecepcionStripImage(array $datos, array $cols, int $pageNo, int $totalPages)
    {
        $fontRegular = $this->thermalFontPath(false);
        $fontBold = $this->thermalFontPath(true);
        $isFirstPage = $pageNo === 1;
        $isLastPage = $pageNo === $totalPages;
        $scaleX = 1.18;
        $scaleY = 1.04;
        $fontScale = 1.12;
        $sx = static fn (int $value): int => (int) round($value * $scaleX);
        $sy = static fn (int $value): int => (int) round($value * $scaleY);
        $fs = static fn (int $value): int => max(8, (int) round($value * $fontScale));

        $margin = $sx(18);
        $topGap = $isFirstPage ? $sy(14) : $sy(8);
        $headerH = $isFirstPage ? $sy(110) : $topGap;
        $tableHeaderH = $sy(26);
        $rowH = $sy(38);
        $footerH = $isLastPage ? $sy(46) : $sy(10);
        $productW = $sx(330);
        $colorW = $sx(150);
        $colW = $sx(74);
        $totalW = $sx(84);
        $pageW = $margin * 2 + $productW + $colorW + (count($cols) * $colW) + $totalW;
        $summaryH = $isLastPage ? $sy(26) : 0;
        $rowsH = count($datos['filas']) * $rowH;
        $pageH = $headerH + $tableHeaderH + $rowsH + $summaryH + $footerH + $sy(14);

        $img = \imagecreatetruecolor($pageW, $pageH);
        $white = \imagecolorallocate($img, 255, 255, 255);
        $black = \imagecolorallocate($img, 0, 0, 0);
        $gray = \imagecolorallocate($img, 90, 90, 90);
        \imagefilledrectangle($img, 0, 0, $pageW, $pageH, $white);

        $text = function (string $text, int $x, int $y, int $size = 11, bool $bold = false, int $color = null) use ($img, $fontRegular, $fontBold, $black, $fs): void {
            \imagettftext($img, $fs($size), 0, $x, $y, $color ?? $black, $bold ? $fontBold : $fontRegular, $text);
        };

        if ($isFirstPage) {
            $text('Consulta Entrada de', $margin, $sy(28), 16, true);
            $text('Mercancia', $margin, $sy(48), 16, true);
            $text('Fecha: ' . $this->thermalAscii($datos['fecha']), $sx(285), $sy(28), 10, true);
            $text('Tienda: ' . $this->thermalAscii($datos['sucursal']), $sx(285), $sy(47), 10, true);
            $text('Almacen: ' . $this->thermalAscii($datos['almacen']), $sx(285), $sy(66), 10, true);
            $text('Proveedor: ' . $this->thermalAscii($datos['proveedor']), $sx(565), $sy(28), 10, true);
            $text('Referencia: ' . $this->thermalAscii($datos['referencia'] !== '' ? $datos['referencia'] : '-'), $sx(565), $sy(47), 10, true);
            $text('Comentario: ' . $this->thermalAscii($datos['observaciones'] !== '' ? $datos['observaciones'] : '-'), $sx(565), $sy(66), 10, true);
            $text('Linea: TODAS LAS LINEAS', $sx(875), $sy(28), 10, true);
            $text('Marca: TODAS LAS MARCAS', $sx(875), $sy(47), 10, true);
            $pageLabel = 'Pagina ' . $pageNo . ' de ' . $totalPages;
            $bbox = imagettfbbox($fs(10), 0, $fontBold, $pageLabel);
            $pageLabelWidth = abs($bbox[2] - $bbox[0]);
            $text($pageLabel, $pageW - $margin - $pageLabelWidth, $sy(28), 10, true);
            $text('No. Entrada: ' . $this->thermalAscii($datos['folio']), $margin, $sy(96), 10, true);
        }

        $x = $margin;
        $y = $headerH;
        \imagerectangle($img, $x, $y, $x + $productW, $y + $tableHeaderH, $black);
        $text('Producto', $x + $sx(10), $y + $sy(18), 10, true);
        $x += $productW;
        \imagerectangle($img, $x, $y, $x + $colorW, $y + $tableHeaderH, $black);
        $text('Color', $x + $sx(10), $y + $sy(18), 10, true);
        $x += $colorW;
        foreach ($cols as $col) {
            \imagerectangle($img, $x, $y, $x + $colW, $y + $tableHeaderH, $black);
            $colText = $this->thermalWrapLabel((string) $col, 8);
            $lines = explode("\n", $colText);
            $lineY = $y + $sy(12);
            foreach ($lines as $line) {
                $bbox = imagettfbbox($fs(8), 0, $fontBold, $line);
                $lw = abs($bbox[2] - $bbox[0]);
                $text($line, (int) ($x + (($colW - $lw) / 2)), $lineY, 8, true);
                $lineY += $sy(10);
            }
            $x += $colW;
        }
        \imagerectangle($img, $x, $y, $x + $totalW, $y + $tableHeaderH, $black);
        $text('Total', $x + $sx(14), $y + $sy(18), 10, true);

        $bodyY = $headerH + $tableHeaderH;
        foreach ($datos['filas'] as $fila) {
            $x = $margin;
            \imagerectangle($img, $x, $bodyY, $x + $productW, $bodyY + $rowH, $black);
            $prod1 = $this->thermalSlice($fila['producto'], 28, 0);
            $prod2 = trim($this->thermalSlice($fila['producto'], 28, 28) . ' ' . $fila['codigo']);
            $text($prod1, $x + $sx(8), $bodyY + $sy(16), 10, true);
            if ($prod2 !== '') {
                $text($prod2, $x + $sx(8), $bodyY + $sy(31), 9, true, $gray);
            }
            $x += $productW;
            \imagerectangle($img, $x, $bodyY, $x + $colorW, $bodyY + $rowH, $black);
            $text($this->thermalSlice($fila['color'], 14, 0), $x + $sx(8), $bodyY + $sy(24), 10, false);
            $x += $colorW;
            foreach ($cols as $col) {
                \imagerectangle($img, $x, $bodyY, $x + $colW, $bodyY + $rowH, $black);
                $valor = (float) ($fila['cells'][$col] ?? 0);
                if ($valor > 0) {
                    $v = number_format($valor, 0, '.', ',');
                    $bbox = imagettfbbox($fs(10), 0, $fontRegular, $v);
                    $vw = abs($bbox[2] - $bbox[0]);
                    $text($v, (int) ($x + (($colW - $vw) / 2)), $bodyY + $sy(24), 10, false);
                }
                $x += $colW;
            }
            \imagerectangle($img, $x, $bodyY, $x + $totalW, $bodyY + $rowH, $black);
            $totalText = number_format((float) $fila['total'], 0, '.', ',');
            $bbox = imagettfbbox($fs(10), 0, $fontBold, $totalText);
            $tw = abs($bbox[2] - $bbox[0]);
            $text($totalText, $x + $totalW - $tw - $sx(8), $bodyY + $sy(24), 10, true);
            $bodyY += $rowH;
        }

        if ($isLastPage) {
            $sumW = $productW + $colorW + (count($cols) * $colW);
            \imagerectangle($img, $margin, $bodyY, $margin + $sumW, $bodyY + $sy(24), $black);
            $sumText = 'Total articulos';
            $bbox = imagettfbbox($fs(10), 0, $fontBold, $sumText);
            $sw = abs($bbox[2] - $bbox[0]);
            $text($sumText, $margin + $sumW - $sw - $sx(8), $bodyY + $sy(17), 10, true);
            \imagerectangle($img, $margin + $sumW, $bodyY, $margin + $sumW + $totalW, $bodyY + $sy(24), $black);
            $artText = number_format((float) $datos['total_articulos'], 0, '.', ',');
            $bbox = imagettfbbox($fs(10), 0, $fontBold, $artText);
            $aw = abs($bbox[2] - $bbox[0]);
            $text($artText, $margin + $sumW + $totalW - $aw - $sx(8), $bodyY + $sy(17), 10, true);
            $bodyY += $sy(26);
        }

        $footerY = $pageH - $footerH;
        if ($isLastPage) {
            $text('TIENDAS L. SURIANA', $margin, $footerY + $sy(18), 12, true);
            $text('Subtotal: $ ' . number_format((float) $datos['subtotal'], 2, '.', ',') . '   Desc: $ ' . number_format((float) $datos['descuento'], 2, '.', ','), $sx(285), $footerY + $sy(18), 11, false);
            $text('Flete: $ ' . number_format((float) $datos['flete'], 2, '.', ',') . '   IVA: $ ' . number_format((float) $datos['iva'], 2, '.', ','), $sx(655), $footerY + $sy(18), 11, false);
            $totalLabel = 'Total: $ ' . number_format((float) $datos['total'], 2, '.', ',');
            $bbox = imagettfbbox($fs(12), 0, $fontBold, $totalLabel);
            $tw = abs($bbox[2] - $bbox[0]);
            $text($totalLabel, $pageW - $margin - $tw, $footerY + $sy(18), 12, true);
        }

        $rotated = \imagerotate($img, -90, $white);
        \imagedestroy($img);
        return $rotated;
    }

    private function escposRasterFromImage($image): string
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $widthBytes = (int) ceil($width / 8);
        $data = '';

        for ($y = 0; $y < $height; $y++) {
            for ($xb = 0; $xb < $widthBytes; $xb++) {
                $byte = 0;
                for ($bit = 0; $bit < 8; $bit++) {
                    $x = ($xb * 8) + $bit;
                    if ($x >= $width) {
                        continue;
                    }
                    $rgb = imagecolorat($image, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    $luma = (0.299 * $r) + (0.587 * $g) + (0.114 * $b);
                    if ($luma < 200) {
                        $byte |= (1 << (7 - $bit));
                    }
                }
                $data .= chr($byte);
            }
        }

        return "\x1D\x76\x30\x00"
            . chr($widthBytes & 0xFF)
            . chr(($widthBytes >> 8) & 0xFF)
            . chr($height & 0xFF)
            . chr(($height >> 8) & 0xFF)
            . $data;
    }

    private function thermalFontPath(bool $bold = false): string
    {
        $paths = $bold
            ? ['C:\\Windows\\Fonts\\arialbd.ttf', 'C:\\Windows\\Fonts\\calibrib.ttf']
            : ['C:\\Windows\\Fonts\\arial.ttf', 'C:\\Windows\\Fonts\\calibri.ttf'];

        foreach ($paths as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        throw ValidationException::withMessages([
            'printer' => 'No se encontro una fuente TrueType de Windows para imprimir el ticket raster.',
        ]);
    }

    private function thermalWrapLabel(string $value, int $segment = 8): string
    {
        $clean = $this->thermalAscii($value);
        return trim(chunk_split($clean, $segment, "\n"));
    }

    private function construirPayloadEscPosRecepcionDinamica(array $reporte): string
    {
        $payload = '';
        $blocks = array_values($reporte['blocks'] ?? []);
        $lastIndex = count($blocks) - 1;

        foreach ($blocks as $index => $block) {
            $image = $this->renderRecepcionDynamicBlockImage($reporte, $block, $index === 0, $index === $lastIndex);
            try {
                $payload .= "\x1B@\x1Ba\x00" . $this->escposRasterFromImage($image) . "\n\n";
            } finally {
                imagedestroy($image);
            }
        }

        $payload .= "\n\n\n\n\x1DV\x00";

        return $payload;
    }

    private function renderRecepcionDynamicBlockImage(array $reporte, array $block, bool $isFirstBlock, bool $isLastBlock)
    {
        $fontRegular = $this->thermalFontPath(false);
        $fontBold = $this->thermalFontPath(true);
        $margin = 16;
        $headerH = $isFirstBlock ? 106 : 14;
        $groupBandH = 34;
        $tableHeaderH = 24;
        $rowH = 27;
        $footerH = $isLastBlock ? 62 : 10;
        $groupW = 250;
        $colorW = 130;
        $totalW = 76;
        $rows = $block['rows'] ?? [];
        $columnWidths = array_map(
            fn (array $column): int => max(48, min(88, (int) round(((int) ($column['width'] ?? 54)) * 0.92))),
            $block['columns'] ?? []
        );
        $matrixW = array_sum($columnWidths);
        $pageW = $margin * 2 + $groupW + $colorW + $matrixW + $totalW;
        $summaryH = $isLastBlock ? 24 : 0;
        $pageH = $headerH + $groupBandH + $tableHeaderH + (count($rows) * $rowH) + $summaryH + $footerH + 12;

        $img = \imagecreatetruecolor($pageW, $pageH);
        $white = \imagecolorallocate($img, 255, 255, 255);
        $black = \imagecolorallocate($img, 0, 0, 0);
        $gray = \imagecolorallocate($img, 90, 90, 90);
        $fill = \imagecolorallocate($img, 236, 239, 244);
        \imagefilledrectangle($img, 0, 0, $pageW, $pageH, $white);

        $text = function (string $text, int $x, int $y, int $size = 10, bool $bold = false, int $color = null) use ($img, $fontRegular, $fontBold, $black): void {
            \imagettftext($img, $size, 0, $x, $y, $color ?? $black, $bold ? $fontBold : $fontRegular, $text);
        };
        $fitText = function (string $content, int $x, int $y, int $width, int $size = 10, bool $bold = false, string $align = 'L') use ($fontRegular, $fontBold, $text): void {
            $font = $bold ? $fontBold : $fontRegular;
            $printSize = $size;
            $safe = $content;
            do {
                $bbox = imagettfbbox($printSize, 0, $font, $safe);
                $textWidth = abs($bbox[2] - $bbox[0]);
                if ($textWidth <= $width || $printSize <= 7) {
                    break;
                }
                $printSize--;
            } while (true);

            $drawX = $x;
            if ($align === 'C') {
                $drawX = (int) ($x + (($width - $textWidth) / 2));
            } elseif ($align === 'R') {
                $drawX = $x + $width - $textWidth;
            }

            $text($safe, $drawX, $y, $printSize, $bold);
        };

        if ($isFirstBlock) {
            \imagerectangle($img, $margin, 6, $pageW - $margin, $headerH - 10, $black);
            $text('Consulta Entrada de Mercancia', $margin + 12, 32, 18, true);
            $text('Recepcion termica horizontal', $margin + 12, 52, 10, false, $gray);

            $metaLeftX = $margin + 290;
            $metaMidX = $margin + 545;
            $metaRightX = $margin + 800;

            $text('No. Entrada', $metaLeftX, 20, 8, true);
            $text($this->thermalAscii((string) $reporte['folio']), $metaLeftX, 35, 11, true);
            $text('Sucursal', $metaLeftX, 54, 8, true);
            $text($this->thermalAscii((string) $reporte['sucursal']), $metaLeftX, 69, 10, true);
            $text('Almacen', $metaLeftX, 86, 8, true);
            $text($this->thermalAscii((string) $reporte['almacen']), $metaLeftX, 101, 10, true);

            $text('Fecha', $metaMidX, 20, 8, true);
            $text($this->thermalAscii((string) $reporte['fecha']), $metaMidX, 35, 11, true);
            $text('Proveedor', $metaMidX, 54, 8, true);
            $fitText($this->thermalAscii((string) $reporte['proveedor']), $metaMidX, 69, 220, 10, true);
            $text('Referencia', $metaMidX, 86, 8, true);
            $fitText($this->thermalAscii((string) (($reporte['referencia'] ?? '') !== '' ? $reporte['referencia'] : '-')), $metaMidX, 101, 220, 10, true);

            $text('Bloques', $metaRightX, 20, 8, true);
            $text('1 de ' . count($reporte['blocks']), $metaRightX, 35, 11, true);
            if (($reporte['observaciones'] ?? '') !== '') {
                $text('Observaciones', $metaRightX, 54, 8, true);
                $fitText($this->thermalAscii((string) $reporte['observaciones']), $metaRightX, 69, 220, 9, false);
            }
        }

        $bandY = $headerH;
        \imagefilledrectangle($img, $margin, $bandY, $pageW - $margin, $bandY + $groupBandH, $fill);
        \imagerectangle($img, $margin, $bandY, $pageW - $margin, $bandY + $groupBandH, $black);
        $groupLabel = trim((($block['codigo'] ?? '') !== '' ? $block['codigo'] . ' · ' : '') . ($block['producto'] ?? 'Producto'));
        $fitText($this->thermalAscii($groupLabel), $margin + 10, $bandY + 22, 520, 13, true);
        $tableInfo = 'Tabla ' . ((int) ($block['group_index'] ?? 1)) . ' de ' . ((int) ($block['group_total_segments'] ?? 1));
        if (!empty($block['is_group_continuation'])) {
            $tableInfo .= ' CONT.';
        }
        $fitText($tableInfo, $pageW - $margin - 170, $bandY + 18, 160, 10, true, 'R');
        $fitText('Total grupo: ' . number_format((float) ($block['group_total'] ?? 0), 0, '.', ','), $pageW - $margin - 170, $bandY + 31, 160, 10, true, 'R');

        $x = $margin;
        $y = $bandY + $groupBandH;
        \imagerectangle($img, $x, $y, $x + $groupW, $y + $tableHeaderH, $black);
        $text('Producto', $x + 10, $y + 17, 10, true);
        $x += $groupW;
        \imagerectangle($img, $x, $y, $x + $colorW, $y + $tableHeaderH, $black);
        $text('Color', $x + 10, $y + 17, 10, true);
        $x += $colorW;

        foreach (($block['columns'] ?? []) as $idx => $column) {
            $colW = $columnWidths[$idx];
            \imagerectangle($img, $x, $y, $x + $colW, $y + $tableHeaderH, $black);
            $fitText($this->thermalAscii((string) ($column['label'] ?? '')), $x + 2, $y + 17, $colW - 4, 9, true, 'C');
            $x += $colW;
        }

        \imagerectangle($img, $x, $y, $x + $totalW, $y + $tableHeaderH, $black);
        $fitText('Total', $x + 2, $y + 17, $totalW - 4, 10, true, 'C');

        $bodyY = $y + $tableHeaderH;
        foreach ($rows as $row) {
            $x = $margin;
            if (!empty($row['show_group_cell'])) {
                $rowspan = max(1, (int) ($row['group_rowspan'] ?? 1));
                \imagerectangle($img, $x, $bodyY, $x + $groupW, $bodyY + ($rowH * $rowspan), $black);
                $fitText($this->thermalAscii((string) ($row['group_label'] ?? $groupLabel)), $x + 8, $bodyY + 20, $groupW - 16, 11, true);
            }
            $x += $groupW;
            \imagerectangle($img, $x, $bodyY, $x + $colorW, $bodyY + $rowH, $black);
            $fitText($this->thermalAscii((string) ($row['color'] ?? '')), $x + 6, $bodyY + 18, $colorW - 12, 10, false);
            $x += $colorW;

            foreach (($row['cells'] ?? []) as $cellIndex => $cell) {
                $colW = $columnWidths[$cellIndex];
                \imagerectangle($img, $x, $bodyY, $x + $colW, $bodyY + $rowH, $black);
                $value = (float) ($cell['value'] ?? 0);
                if ($value > 0) {
                    $fitText(number_format($value, 0, '.', ','), $x + 2, $bodyY + 18, $colW - 4, 10, false, 'C');
                }
                $x += $colW;
            }

            \imagerectangle($img, $x, $bodyY, $x + $totalW, $bodyY + $rowH, $black);
            $fitText(number_format((float) ($row['row_total'] ?? 0), 0, '.', ','), $x + 2, $bodyY + 18, $totalW - 6, 10, true, 'R');
            $bodyY += $rowH;
        }

        if ($isLastBlock) {
            $sumW = $groupW + $colorW + $matrixW;
            \imagerectangle($img, $margin, $bodyY, $margin + $sumW, $bodyY + 24, $black);
            $fitText('Total articulos', $margin + 4, $bodyY + 17, $sumW - 8, 10, true, 'R');
            \imagerectangle($img, $margin + $sumW, $bodyY, $margin + $sumW + $totalW, $bodyY + 24, $black);
            $fitText(number_format((float) ($reporte['receipt_totals']['articulos'] ?? 0), 0, '.', ','), $margin + $sumW + 2, $bodyY + 17, $totalW - 6, 10, true, 'R');

            $footerY = $pageH - $footerH + 18;
            $text('Subtotal: $ ' . number_format((float) ($reporte['receipt_totals']['subtotal'] ?? 0), 2, '.', ','), $margin, $footerY, 10, false);
            $text('Descuento: $ ' . number_format((float) ($reporte['receipt_totals']['descuento'] ?? 0), 2, '.', ','), $margin + 250, $footerY, 10, false);
            $text('Flete: $ ' . number_format((float) ($reporte['receipt_totals']['flete'] ?? 0), 2, '.', ','), $margin + 500, $footerY, 10, false);
            $text('IVA: $ ' . number_format((float) ($reporte['receipt_totals']['iva'] ?? 0), 2, '.', ','), $margin + 700, $footerY, 10, false);
            $fitText('Total: $ ' . number_format((float) ($reporte['receipt_totals']['total'] ?? 0), 2, '.', ','), $pageW - $margin - 240, $footerY, 230, 12, true, 'R');
        }

        $rotated = \imagerotate($img, -90, $white);
        \imagedestroy($img);
        return $rotated;
    }

    private function resolverNavegadorImpresionSilenciosa(): string
    {
        $paths = [
            'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
        ];

        foreach ($paths as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        throw ValidationException::withMessages([
            'printer' => 'No se encontro Edge o Chrome para lanzar la impresion silenciosa.',
        ]);
    }

    private function lanzarImpresionSilenciosaNavegador(string $browserPath, string $url): void
    {
        $scriptBase = tempnam(sys_get_temp_dir(), 'rme-print-');
        if ($scriptBase === false) {
            throw ValidationException::withMessages([
                'printer' => 'No fue posible preparar la impresion directa.',
            ]);
        }

        $scriptPath = $scriptBase . '.ps1';
        file_put_contents($scriptPath, <<<'PS1'
param([string]$BrowserPath,[string]$Url)
Start-Process -FilePath $BrowserPath -ArgumentList @('--new-window', '--kiosk-printing', $Url) -WindowStyle Hidden
PS1);

        $command = 'powershell -NoProfile -ExecutionPolicy Bypass -File '
            . escapeshellarg($scriptPath)
            . ' -BrowserPath '
            . escapeshellarg($browserPath)
            . ' -Url '
            . escapeshellarg($url)
            . ' 2>&1';

        exec($command, $output, $exitCode);
        @unlink($scriptPath);
        @unlink($scriptBase);

        if ($exitCode !== 0) {
            throw ValidationException::withMessages([
                'printer' => trim(implode("\n", $output)) ?: 'No fue posible lanzar la impresion silenciosa.',
            ]);
        }
    }

    private function anchoColumnaTallaTermica(string $label, int $minWidth, int $padding): int
    {
        $length = max(1, strlen($this->thermalAscii($label)));
        return max($minWidth, ($length * 9) + $padding);
    }

    private function segmentarColumnasTermicasHtml(array $columnas, int $availableWidth): array
    {
        if (empty($columnas)) {
            return [[
                'columns' => [],
            ]];
        }

        $segmentos = [];
        $actual = [];
        $anchoActual = 0;

        foreach ($columnas as $columna) {
            $colWidth = (int) ($columna['width'] ?? 0);
            if (!empty($actual) && ($anchoActual + $colWidth) > $availableWidth) {
                $segmentos[] = ['columns' => $actual];
                $actual = [];
                $anchoActual = 0;
            }

            $actual[] = $columna;
            $anchoActual += $colWidth;
        }

        if (!empty($actual)) {
            $segmentos[] = ['columns' => $actual];
        }

        return $segmentos;
    }

    private function esAtributoTalla(string $nombre, string $clave = ''): bool
    {
        $texto = Str::lower(Str::ascii(trim($nombre . ' ' . $clave)));
        return str_contains($texto, 'talla') || str_contains($texto, 'tamano') || str_contains($texto, 'medida');
    }

    private function esAtributoColor(string $nombre, string $clave = ''): bool
    {
        $texto = Str::lower(Str::ascii(trim($nombre . ' ' . $clave)));
        return str_contains($texto, 'color');
    }

    private function esc(string $valor): string
    {
        return htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
