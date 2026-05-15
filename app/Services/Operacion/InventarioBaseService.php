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
use App\Models\Sucursal;
use App\Models\TipoMovimientoInventario;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
            ->with('marca:mrc_id,mrc_nombre')
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
            ->when($conBuscar && !empty($filtros['buscar']), function ($q) use ($filtros): void {
                $buscar = trim((string) $filtros['buscar']);
                $q->where(function ($sub) use ($buscar): void {
                    $sub->where('prd.prd_codigo', 'like', "%{$buscar}%")
                        ->orWhere('prd.prd_nombre', 'like', "%{$buscar}%")
                        ->orWhere('mrc.mrc_nombre', 'like', "%{$buscar}%")
                        ->orWhere('mdl.mdl_nombre', 'like', "%{$buscar}%")
                        ->orWhere('lna.lna_nombre', 'like', "%{$buscar}%")
                        ->orWhere('ctg.ctg_nombre', 'like', "%{$buscar}%");
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
            'mrc.mrc_nombre as marca_nombre',
            'mdl.mdl_nombre as modelo_nombre',
            'lna.lna_nombre as linea_nombre',
            'ctg.ctg_nombre as categoria_nombre',
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
                'tbl_producto_skus_psk.psk_codigo',
                'tbl_producto_skus_psk.psk_nombre',
                'prd.prd_nombre',
            ]);

        $resultados = $filas->map(fn ($sku) => [
            'id' => $sku->psk_id,
            'text' => sprintf('%s - %s (%s)', $sku->psk_codigo, $sku->psk_nombre, $sku->prd_nombre),
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

    public function listarKardex(array $filtros = []): Collection
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
            ->orderByDesc('min.min_fecha_movimiento')
            ->orderByDesc('min.min_id')
            ->limit(1000)
            ->get([
                'min.min_id',
                'min.min_folio',
                'min.min_psk_id',
                'min.min_scl_id',
                'min.min_alm_id',
                'min.min_documento_tipo',
                'min.min_documento_referencia',
                'min.min_cantidad',
                'min.min_signo',
                'min.min_existencia_antes',
                'min.min_existencia_despues',
                'min.min_motivo_texto',
                'min.min_estatus',
                'min.min_es_reversa',
                'min.min_origen_min_id',
                'min.min_reversa_de_min_id',
                'min.min_fecha_movimiento',
                'psk.psk_codigo',
                'psk.psk_nombre',
                'prd.prd_nombre',
                'prd.prd_tipo',
                'scl.scl_nombre',
                'alm.alm_nombre',
                'tmi.tmi_nombre',
                'tmi.tmi_clase',
                'usr.usr_nombre as usuario_nombre',
            ]);
    }

    public function listarNegativosPorSesionCaja(array $filtros = []): Collection
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
            })
            ->orderByDesc('min.min_fecha_movimiento')
            ->orderByDesc('min.min_id')
            ->limit(1500)
            ->get([
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
            ]);
    }

    public function listarBajoMinimo(array $filtros = []): Collection
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
            ->orderBy('scl.scl_nombre')
            ->orderBy('alm.alm_nombre')
            ->orderBy('psk.psk_nombre')
            ->get([
                'mni.mni_id',
                'mni.mni_minimo',
                'exa.exa_existencia',
                'psk.psk_id',
                'psk.psk_codigo',
                'psk.psk_nombre',
                'prd.prd_nombre',
                'scl.scl_nombre',
                'alm.alm_nombre',
            ]);
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
        return DB::transaction(function () use ($request, $datos): array {
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
            $documentoTipo = match ($tipoEntrada) {
                'compra_remision' => 'remision',
                'compra_factura' => 'factura',
                'entrada_normal' => 'entrada_normal',
                default => 'inventario_inicial',
            };

            $movimientos = [];
            $subtotal = round((float) $lineas->sum(fn ($linea) => ((float) $linea['min_cantidad']) * ((float) $linea['min_precio_unitario'])), 2);
            $descuentoTipo = (string) ($datos['min_descuento_tipo'] ?? 'ninguno');
            $descuentoValor = round((float) ($datos['min_descuento_valor'] ?? 0), 2);
            $fleteTotal = round((float) ($datos['min_flete_total'] ?? 0), 2);
            $ivaPorcentaje = round((float) ($datos['min_iva_porcentaje'] ?? 16), 2);
            if ($ivaPorcentaje < 0) {
                $ivaPorcentaje = 0;
            }

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
                ]
            );

            return [
                'total' => count($movimientos),
                'folios' => collect($movimientos)->map(fn ($mov) => $mov->min_folio)->values()->all(),
            ];
        });
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
                'producto:prd_id,prd_codigo,prd_nombre,prd_tipo',
                'valoresAtributo' => fn ($q) => $q
                    ->where('vat_deleted', false)
                    ->whereNull('vat_deleted_at')
                    ->where('vat_estatus', 'activo')
                    ->with(['atributo:atr_id,atr_nombre'])
                    ->orderBy('vat_valor'),
            ])
            ->whereIn('psk_id', $skuIds->all())
            ->get(['psk_id', 'psk_prd_id', 'psk_codigo', 'psk_nombre'])
            ->keyBy('psk_id');

        $dominanteAtrId = (int) ($datos['atr_dominante_id'] ?? 0);
        $columnasMap = [];
        $filasMap = [];
        $rowSort = [];
        $dominanteNombre = 'Dominante';

        foreach ($movimientos as $movimiento) {
            /** @var ProductoSku|null $sku */
            $sku = $skus->get((int) $movimiento->min_psk_id);
            if (!$sku || !$sku->producto) {
                continue;
            }

            $producto = $sku->producto;
            $productoId = (int) $producto->prd_id;
            $productoLabel = trim((string) $producto->prd_codigo . ' - ' . (string) $producto->prd_nombre);
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

            if ($tipoProducto === 'variable' && $dominanteAtrId > 0 && isset($atributosById[$dominanteAtrId])) {
                $compatibleDominante = true;
                $dominanteNombre = (string) $atributosById[$dominanteAtrId]['nombre'];
                $dominanteValor = (string) $atributosById[$dominanteAtrId]['valor'];

                $resto = collect($atributosById)
                    ->reject(fn ($item, $id) => (int) $id === $dominanteAtrId)
                    ->map(fn ($item) => (string) $item['valor'])
                    ->values()
                    ->all();

                $colLabel = !empty($resto) ? implode(' / ', $resto) : 'Existencia';
                $colKey = !empty($resto) ? implode('||', $resto) : '__base__';
            }

            if (!isset($columnasMap[$colKey])) {
                $columnasMap[$colKey] = $colLabel;
            }

            $rowId = $productoId . '|' . $dominanteValor;
            if (!isset($filasMap[$rowId])) {
                $filasMap[$rowId] = [
                    'producto_id' => $productoId,
                    'producto' => $productoLabel,
                    'dominante' => $dominanteValor,
                    'cells' => [],
                    'total' => 0.0,
                    'compatible_dominante' => $compatibleDominante || $tipoProducto === 'simple',
                ];
                $rowSort[$rowId] = [
                    'producto' => mb_strtolower($productoLabel),
                    'dominante' => mb_strtolower($dominanteValor),
                ];
            }

            $cantidad = (float) $movimiento->min_cantidad;
            $filasMap[$rowId]['cells'][$colKey] = (float) ($filasMap[$rowId]['cells'][$colKey] ?? 0) + $cantidad;
            $filasMap[$rowId]['total'] += $cantidad;
        }

        if (empty($filasMap)) {
            throw ValidationException::withMessages([
                'folios' => 'No se pudo construir el reporte con los movimientos seleccionados.',
            ]);
        }

        asort($columnasMap);
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

        $tipoEntrada = (string) ($datos['min_documento_tipo'] ?? 'entrada_normal');
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
        $descuentoTipo = (string) ($datos['min_descuento_tipo'] ?? ($movimientos->first()->min_descuento_tipo ?? 'ninguno'));
        $descuentoValor = round((float) ($datos['min_descuento_valor'] ?? ($movimientos->first()->min_descuento_valor ?? 0)), 2);
        $fleteTotal = round((float) ($datos['min_flete_total'] ?? ($movimientos->first()->min_flete_total ?? 0)), 2);
        $ivaPorcentaje = round((float) ($datos['min_iva_porcentaje'] ?? ($movimientos->first()->min_iva_porcentaje ?? 16)), 2);
        $subtotalMonetario = round((float) $movimientos->sum('min_subtotal_linea'), 2);
        $descuentoMonetario = round((float) $movimientos->sum('min_descuento_linea'), 2);
        if ($descuentoMonetario <= 0 && $descuentoValor > 0) {
            $descuentoMonetario = $descuentoTipo === 'porcentaje'
                ? round($subtotalMonetario * ($descuentoValor / 100), 2)
                : min($subtotalMonetario, $descuentoValor);
        }
        $ivaMonetario = round((float) $movimientos->sum('min_iva_linea'), 2);
        $totalMonetario = round((float) $movimientos->sum('min_total_linea'), 2);
        if ($totalMonetario <= 0) {
            $baseMonetaria = max(0, round($subtotalMonetario - $descuentoMonetario + $fleteTotal, 2));
            $ivaMonetario = $tipoEntrada === 'compra_factura'
                ? round($baseMonetaria * ($ivaPorcentaje / 100), 2)
                : 0.0;
            $totalMonetario = round($baseMonetaria + $ivaMonetario, 2);
        }

        // ── Totales globales por columna ────────────────────────────────────
        $totalesPorColumna = [];
        $granTotal = 0.0;
        foreach ($filasMap as $row) {
            foreach ($columnasMap as $colKey => $colLabel) {
                $totalesPorColumna[$colKey] = (float) ($totalesPorColumna[$colKey] ?? 0) + (float) ($row['cells'][$colKey] ?? 0);
            }
            $granTotal += (float) $row['total'];
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
                <td style="border:none;padding:10px 14px;background-color:' . $colorPrimario . ';width:75%;">
                    <div style="font-size:16px;font-weight:bold;color:#ffffff;letter-spacing:0.5px;">
                        Reporte de Entradas Registradas
                    </div>
                    <div style="font-size:8px;color:#9eb4cc;margin-top:2px;letter-spacing:0.3px;">
                        LA I. SURIANA &nbsp;&bull;&nbsp; INVENTARIO BASE
                    </div>
                </td>
                <td style="border:none;padding:10px 14px;background-color:' . $colorAccent . ';width:25%;text-align:right;vertical-align:middle;">
                    <div style="font-size:8px;color:#d0ccff;">Generado</div>
                    <div style="font-size:11px;font-weight:bold;color:#ffffff;">' . $this->esc(now()->format('d/M/Y')) . '</div>
                    <div style="font-size:8px;color:#d0ccff;">' . $this->esc(now()->format('H:i:s')) . '</div>
                </td>
            </tr>
        </table>';

        // Banda de metadatos (2 columnas)
        $fechaCapturaFmt = $fechaCaptura !== ''
            ? date('d/m/Y H:i', strtotime($fechaCaptura))
            : 'N/D';

        $html .= '
        <table style="border-collapse:collapse;margin-bottom:6px;">
            <tr>
                <td style="border:none;border-bottom:3px solid ' . $colorAccent . ';background-color:#f0f4f9;padding:6px 10px;width:50%;vertical-align:top;">
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
                <td style="border:none;border-bottom:3px solid ' . $colorAccent . ';background-color:#f0f4f9;padding:6px 10px;width:50%;vertical-align:top;">
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
        $mmProducto  = 70;   // columna producto
        $mmDominante = 26;   // columna dominante (color, talla, etc.)
        $mmTotal     = 20;   // columna total fila
        $mmDisponible = 283 - $mmProducto - $mmDominante - $mmTotal;
        $numColumnas  = max(1, count($columnasMap));
        $mmCol        = max(10, (int) floor($mmDisponible / $numColumnas));

        // ── Resumen rápido (3 pastillas en mm fijos) ──────────────────────
        $mmTercio = (int) floor(283 / 3);
        $html .= '
        <table style="border-collapse:collapse;margin-bottom:8px;width:283mm;">
            <tr>
                <td style="border:1px solid ' . $colorBorderTbl . ';padding:5px 10px;background-color:#ffffff;width:' . $mmTercio . 'mm;text-align:center;">
                    <div style="font-size:7px;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Productos</div>
                    <div style="font-size:14px;font-weight:bold;color:' . $colorPrimario . ';">' . $totalProductos . '</div>
                </td>
                <td style="border:1px solid ' . $colorBorderTbl . ';padding:5px 10px;background-color:#ffffff;width:' . $mmTercio . 'mm;text-align:center;">
                    <div style="font-size:7px;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Variantes (filas)</div>
                    <div style="font-size:14px;font-weight:bold;color:' . $colorPrimario . ';">' . $totalFilas . '</div>
                </td>
                <td style="border:1px solid ' . $colorBorderTbl . ';padding:5px 10px;background-color:' . $colorOkBg . ';width:' . $mmTercio . 'mm;text-align:center;">
                    <div style="font-size:7px;color:#166534;text-transform:uppercase;letter-spacing:0.5px;">Total piezas</div>
                    <div style="font-size:14px;font-weight:bold;color:#166534;">' . number_format($granTotal, 0, '.', ',') . '</div>
                </td>
            </tr>
        </table>';

        $baseMonetaria = max(0, round($subtotalMonetario - $descuentoMonetario + $fleteTotal, 2));
        $html .= '
        <table style="border-collapse:collapse;margin-bottom:8px;width:283mm;">
            <tr>
                <td style="border:1px solid ' . $colorBorderTbl . ';padding:5px 8px;background:#ffffff;width:47mm;"><strong>Subtotal</strong><br>' . number_format($subtotalMonetario, 2, '.', ',') . '</td>
                <td style="border:1px solid ' . $colorBorderTbl . ';padding:5px 8px;background:#ffffff;width:47mm;"><strong>Descuento</strong><br>' . number_format($descuentoMonetario, 2, '.', ',') . '</td>
                <td style="border:1px solid ' . $colorBorderTbl . ';padding:5px 8px;background:#ffffff;width:47mm;"><strong>Flete</strong><br>' . number_format($fleteTotal, 2, '.', ',') . '</td>';

        if ($tipoEntrada === 'compra_factura') {
            $html .= '<td style="border:1px solid ' . $colorBorderTbl . ';padding:5px 8px;background:#ffffff;width:47mm;"><strong>Base</strong><br>' . number_format($baseMonetaria, 2, '.', ',') . '</td>';
            $html .= '<td style="border:1px solid ' . $colorBorderTbl . ';padding:5px 8px;background:#ffffff;width:47mm;"><strong>IVA (' . number_format($ivaPorcentaje, 2, '.', ',') . '%)</strong><br>' . number_format($ivaMonetario, 2, '.', ',') . '</td>';
        } else {
            $html .= '<td style="border:1px solid ' . $colorBorderTbl . ';padding:5px 8px;background:#ffffff;width:94mm;" colspan="2"><strong>Base</strong><br>' . number_format($baseMonetaria, 2, '.', ',') . '</td>';
        }

        $html .= '<td style="border:1px solid ' . $colorAccent . ';padding:5px 8px;background:' . $colorAccent . ';color:#ffffff;width:48mm;"><strong>Total</strong><br>' . number_format($totalMonetario, 2, '.', ',') . '</td>
            </tr>
        </table>';

        // ── Tabla de datos con anchos en mm (sin rowspan) ─────────────────
        $stProducto  = 'width:' . $mmProducto  . 'mm;border:1px solid ' . $colorBorderTbl . ';padding:4px 5px;vertical-align:middle;';
        $stDominante = 'width:' . $mmDominante . 'mm;border:1px solid ' . $colorBorderTbl . ';padding:4px 5px;text-align:center;vertical-align:middle;';
        $stCol       = 'width:' . $mmCol       . 'mm;border:1px solid ' . $colorBorderTbl . ';padding:4px 4px;text-align:center;vertical-align:middle;';
        $stTotal     = 'width:' . $mmTotal     . 'mm;border:1px solid ' . $colorBorderTbl . ';padding:4px 5px;text-align:center;vertical-align:middle;';

        $html .= '<table style="border-collapse:collapse;font-size:8.5px;width:283mm;">';
        $html .= '<thead><tr>';
        $html .= '<th style="' . $stProducto  . 'background-color:' . $colorHeaderBg . ';color:' . $colorHeaderTxt . ';font-weight:bold;text-align:left;">Producto</th>';
        $html .= '<th style="' . $stDominante . 'background-color:' . $colorHeaderBg . ';color:' . $colorHeaderTxt . ';font-weight:bold;">' . $this->esc($dominanteNombre) . '</th>';

        foreach ($columnasMap as $colLabel) {
            $html .= '<th style="' . $stCol . 'background-color:' . $colorHeaderBg . ';color:' . $colorHeaderTxt . ';font-weight:bold;">'
                   . $this->esc((string) $colLabel) . '</th>';
        }

        $html .= '<th style="' . $stTotal . 'background-color:' . $colorAccent . ';color:#ffffff;font-weight:bold;">Total</th>';
        $html .= '</tr></thead><tbody>';

        // ── Filas de datos (sin rowspan, bordeado superior para separar productos) ──
        $filaIndex       = 0;
        $productoAnterior = null;
        foreach ($filasMap as $row) {
            $esNuevoProducto = ($row['producto_id'] !== $productoAnterior);
            $esAlt           = ($filaIndex % 2 === 1);
            $bgFila          = $esAlt ? $colorAltRow : '#ffffff';

            // Borde superior más grueso al cambiar de producto
            $borderTopProducto = $esNuevoProducto
                ? 'border-top:2px solid ' . $colorHeaderBg . ';'
                : '';

            $html .= '<tr>';

            // Producto: se muestra en cada fila pero visualmente agrupado por borde
            if ($esNuevoProducto) {
                $html .= '<td style="' . $stProducto . 'background-color:' . $bgFila . ';font-weight:bold;' . $borderTopProducto . '">'
                       . $this->esc((string) $row['producto']) . '</td>';
            } else {
                $html .= '<td style="' . $stProducto . 'background-color:' . $bgFila . ';border-top:1px dashed ' . $colorBorderTbl . ';">&nbsp;</td>';
            }

            // Dominante
            $html .= '<td style="' . $stDominante . 'background-color:' . $colorSubHeader . ';font-weight:bold;' . $borderTopProducto . '">'
                   . $this->esc((string) $row['dominante']) . '</td>';

            // Celdas de valores por columna
            foreach ($columnasMap as $colKey => $colLabel) {
                $valor = (float) ($row['cells'][$colKey] ?? 0);
                if ($valor > 0) {
                    $html .= '<td style="' . $stCol . 'background-color:' . $colorOkBg . ';color:' . $colorOkTxt . ';font-weight:bold;' . $borderTopProducto . '">'
                           . number_format($valor, 0, '.', ',') . '</td>';
                } else {
                    $html .= '<td style="' . $stCol . 'background-color:' . $colorNaBg . ';color:' . $colorNaTxt . ';' . $borderTopProducto . '">—</td>';
                }
            }

            // Total de fila
            $html .= '<td style="' . $stTotal . 'background-color:' . $colorTotalBg . ';font-weight:bold;' . $borderTopProducto . '">'
                   . number_format((float) $row['total'], 0, '.', ',') . '</td>';
            $html .= '</tr>';

            $productoAnterior = $row['producto_id'];
            $filaIndex++;
        }

        // ── Fila de totales globales ──────────────────────────────────────
        $html .= '<tr>';
        $html .= '<td colspan="2" style="width:' . ($mmProducto + $mmDominante) . 'mm;border:1px solid ' . $colorBorderTbl . ';border-top:2px solid ' . $colorHeaderBg . ';padding:5px 6px;background-color:' . $colorHeaderBg . ';color:#ffffff;font-weight:bold;text-align:right;font-size:9px;">TOTAL GENERAL</td>';

        foreach ($columnasMap as $colKey => $colLabel) {
            $tot = (float) ($totalesPorColumna[$colKey] ?? 0);
            $html .= '<td style="' . $stCol . 'background-color:' . $colorHeaderBg . ';color:#ffffff;font-weight:bold;border-top:2px solid ' . $colorHeaderBg . ';font-size:9px;">'
                   . ($tot > 0 ? number_format($tot, 0, '.', ',') : '—') . '</td>';
        }

        $html .= '<td style="' . $stTotal . 'background-color:' . $colorAccent . ';color:#ffffff;font-weight:bold;border-top:2px solid ' . $colorHeaderBg . ';font-size:10px;">'
               . number_format($granTotal, 0, '.', ',') . '</td>';
        $html .= '</tr>';

        $html .= '</tbody></table>';

        // ── Pie de página ────────────────────────────────────────────────
        $html .= '
        <br/>
        <table style="border-collapse:collapse;margin-top:4px;">
            <tr>
                <td style="border:none;border-top:1px solid ' . $colorBorderTbl . ';padding:4px 0;font-size:7px;color:#94a3b8;">
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
        return DB::transaction(function () use ($request, $datos): MovimientoInventario {
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

    private function esc(string $valor): string
    {
        return htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
