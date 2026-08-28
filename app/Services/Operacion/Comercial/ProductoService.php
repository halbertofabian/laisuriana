<?php

namespace App\Services\Operacion\Comercial;

use App\Models\Atributo;
use App\Models\Almacen;
use App\Models\Producto;
use App\Models\ProductoAtributo;
use App\Models\ProductoAlmacen;
use App\Models\ProductoCorrida;
use App\Models\ProductoCorridaValor;
use App\Models\ProductoSku;
use App\Models\SkuValorAtributo;
use App\Models\ValorAtributo;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductoService
{
    public function __construct(
        private readonly AuditoriaService $auditoriaService,
        private readonly ProductoImagenTemporalService $productoImagenTemporalService,
    ) {
    }

    public function listar(array $filtros = [])
    {
        return Producto::query()
            ->with([
                'marca:mrc_id,mrc_nombre',
                'modelo:mdl_id,mdl_nombre',
                'linea:lna_id,lna_nombre',
                'proveedor:prv_id,prv_nombre_empresa',
                'categoria:ctg_id,ctg_nombre',
                'descripcionCatalogo:dsc_id,dsc_nombre',
                'unidad:umd_id,umd_nombre,umd_codigo',
                'atributos:atr_id,atr_nombre',
                'almacenesPermitidos:alm_id,alm_scl_id,alm_nombre',
            ])
            ->withCount([
                'skus as skus_total' => fn ($query) => $query->where('psk_deleted', false)->whereNull('psk_deleted_at'),
                'skus as skus_activos' => fn ($query) => $query->where('psk_deleted', false)->whereNull('psk_deleted_at')->where('psk_estatus', 'activo'),
            ])
            ->select('tbl_productos_prd.*')
            ->selectSub(
                ProductoSku::query()
                    ->selectRaw('MIN(psk_costo)')
                    ->whereColumn('psk_prd_id', 'tbl_productos_prd.prd_id')
                    ->where('psk_deleted', false)
                    ->whereNull('psk_deleted_at'),
                'costo_minimo_sku'
            )
            ->selectSub(
                ProductoSku::query()
                    ->selectRaw('MAX(psk_costo)')
                    ->whereColumn('psk_prd_id', 'tbl_productos_prd.prd_id')
                    ->where('psk_deleted', false)
                    ->whereNull('psk_deleted_at'),
                'costo_maximo_sku'
            )
            ->when(!empty($filtros['buscar']), function ($query) use ($filtros): void {
                $buscar = trim((string) $filtros['buscar']);
                $query->where(function ($sub) use ($buscar): void {
                    $sub->where('prd_codigo', 'like', "%{$buscar}%")
                        ->orWhere('prd_codigo_barras', 'like', "%{$buscar}%")
                        ->orWhere('prd_clave_sat', 'like', "%{$buscar}%")
                        ->orWhere('prd_nombre', 'like', "%{$buscar}%");
                });
            })
            ->when(!empty($filtros['estatus']), fn ($query) => $query->where('prd_estatus', $filtros['estatus']))
            ->when(!empty($filtros['almacen_id']), fn ($query, $almacenId) => $query->whereHas('almacenesPermitidos', fn ($q) => $q->where('tbl_almacenes_alm.alm_id', (int) $almacenId)))
            ->orderBy('prd_nombre')
            ->get();
    }

    public function obtenerPorId(int $id): Producto
    {
        $producto = Producto::query()
            ->with([
                'atributos:atr_id,atr_nombre',
                'almacenesPermitidos:alm_id,alm_scl_id,alm_nombre',
                'corridas' => fn ($query) => $query
                    ->where('prc_deleted', false)
                    ->whereNull('prc_deleted_at')
                    ->with([
                        'atributo:atr_id,atr_nombre',
                        'valores:vat_id,vat_atr_id,vat_valor',
                    ])
                    ->orderBy('prc_orden'),
                'skus' => fn ($query) => $query
                    ->where('psk_deleted', false)
                    ->whereNull('psk_deleted_at')
                    ->with('valoresAtributo:vat_id,vat_atr_id,vat_valor'),
            ])
            ->findOrFail($id);

        $producto->setAttribute('atributo_valores', $this->obtenerMapaValoresSeleccionados($producto));

        return $producto;
    }

    public function crear(Request $request, array $datos): Producto
    {
        return DB::transaction(function () use ($request, $datos): Producto {
            $configuracion = $this->normalizarConfiguracionProducto(
                $datos['prd_tipo'],
                $datos['atributo_ids'] ?? [],
                $datos['atributo_valores'] ?? [],
                $datos['corridas'] ?? []
            );

            $codigoSolicitado = trim((string) Arr::get($datos, 'prd_codigo', ''));
            $imagen = $this->resolverImagenProducto($request, $datos, null);

            $producto = Producto::query()->create([
                'prd_codigo' => $codigoSolicitado !== '' ? $codigoSolicitado : $this->generarCodigoTemporal(),
                'prd_codigo_barras' => Arr::get($datos, 'prd_codigo_barras'),
                'prd_clave_sat' => Arr::get($datos, 'prd_clave_sat'),
                'prd_nombre' => $datos['prd_nombre'],
                'prd_descripcion' => $datos['prd_descripcion'] ?? null,
                'prd_imagen_tipo' => $imagen['tipo'],
                'prd_imagen_path' => $imagen['path'],
                'prd_imagen_url' => $imagen['url'],
                'prd_precio_base' => $datos['prd_precio_base'],
                'prd_costo' => Arr::get($datos, 'prd_costo') ?? 0,
                'prd_stock_minimo' => $datos['prd_stock_minimo'],
                'prd_stock_maximo' => $datos['prd_stock_maximo'],
                'prd_mrc_id' => $datos['prd_mrc_id'],
                'prd_mdl_id' => $datos['prd_mdl_id'] ?? null,
                'prd_prv_id' => $datos['prd_prv_id'] ?? null,
                'prd_lna_id' => $datos['prd_lna_id'],
                'prd_ctg_id' => Arr::get($datos, 'prd_ctg_id'),
                'prd_dsc_id' => Arr::get($datos, 'prd_dsc_id'),
                'prd_umd_id' => $datos['prd_umd_id'],
                'prd_tipo' => $datos['prd_tipo'],
                'prd_estatus' => $datos['prd_estatus'],
                'prd_created_by_usr_id' => optional($request->user())->usr_id,
                'prd_updated_by_usr_id' => optional($request->user())->usr_id,
            ]);

            if ($codigoSolicitado === '') {
                $producto->update([
                    'prd_codigo' => $this->generarCodigoProducto($producto),
                    'prd_updated_by_usr_id' => optional($request->user())->usr_id,
                ]);
            }

            $this->sincronizarAtributosProducto($request, $producto->prd_id, $configuracion['atributo_ids']);
            $this->sincronizarAlmacenesProducto($request, $producto->prd_id, $datos['almacen_ids'] ?? []);
            $this->sincronizarCorridasProducto($request, $producto->prd_id, $configuracion['corridas']);
            $resumenSkus = $this->sincronizarSkusGenerados($request, $producto, $configuracion);

            $this->auditoriaService->registrarAccion(
                $request,
                'catalogo_comercial.producto.crear',
                'tbl_productos_prd',
                (string) $producto->prd_id,
                [
                    'prd_codigo' => $producto->prd_codigo,
                    'prd_tipo' => $producto->prd_tipo,
                    'skus_creados' => count($resumenSkus['creados']),
                ]
            );

            $this->registrarResumenSku($request, $producto, 'catalogo_comercial.producto.generar_skus', $resumenSkus);

            return $producto->fresh();
        });
    }

    public function actualizar(Request $request, int $id, array $datos): Producto
    {
        return DB::transaction(function () use ($request, $id, $datos): Producto {
            $producto = Producto::query()->findOrFail($id);

            $configuracion = $this->normalizarConfiguracionProducto(
                $datos['prd_tipo'],
                $datos['atributo_ids'] ?? [],
                $datos['atributo_valores'] ?? [],
                $datos['corridas'] ?? []
            );

            $codigoSolicitado = trim((string) Arr::get($datos, 'prd_codigo', ''));
            $codigoFinal = $codigoSolicitado !== '' ? $codigoSolicitado : ($producto->prd_codigo ?: $this->generarCodigoProducto($producto, $datos['prd_nombre']));
            $imagen = $this->resolverImagenProducto($request, $datos, $producto);

            $producto->update([
                'prd_codigo' => $codigoFinal,
                'prd_codigo_barras' => Arr::get($datos, 'prd_codigo_barras'),
                'prd_clave_sat' => Arr::get($datos, 'prd_clave_sat'),
                'prd_nombre' => $datos['prd_nombre'],
                'prd_descripcion' => $datos['prd_descripcion'] ?? null,
                'prd_imagen_tipo' => $imagen['tipo'],
                'prd_imagen_path' => $imagen['path'],
                'prd_imagen_url' => $imagen['url'],
                'prd_precio_base' => $datos['prd_precio_base'],
                'prd_costo' => Arr::get($datos, 'prd_costo') ?? 0,
                'prd_stock_minimo' => $datos['prd_stock_minimo'],
                'prd_stock_maximo' => $datos['prd_stock_maximo'],
                'prd_mrc_id' => $datos['prd_mrc_id'],
                'prd_mdl_id' => $datos['prd_mdl_id'] ?? null,
                'prd_prv_id' => $datos['prd_prv_id'] ?? null,
                'prd_lna_id' => $datos['prd_lna_id'],
                'prd_ctg_id' => Arr::get($datos, 'prd_ctg_id'),
                'prd_dsc_id' => Arr::get($datos, 'prd_dsc_id'),
                'prd_umd_id' => $datos['prd_umd_id'],
                'prd_tipo' => $datos['prd_tipo'],
                'prd_estatus' => $datos['prd_estatus'],
                'prd_updated_by_usr_id' => optional($request->user())->usr_id,
            ]);

            $this->sincronizarAtributosProducto($request, $producto->prd_id, $configuracion['atributo_ids']);
            $this->sincronizarAlmacenesProducto($request, $producto->prd_id, $datos['almacen_ids'] ?? []);
            $this->sincronizarCorridasProducto($request, $producto->prd_id, $configuracion['corridas']);
            $resumenSkus = $this->sincronizarSkusGenerados($request, $producto->fresh(), $configuracion);
            $this->sincronizarEstatusSkusPorProducto($request, $producto->prd_id, $producto->prd_estatus);

            $this->auditoriaService->registrarAccion(
                $request,
                'catalogo_comercial.producto.editar',
                'tbl_productos_prd',
                (string) $producto->prd_id,
                [
                    'prd_codigo' => $producto->prd_codigo,
                    'prd_tipo' => $producto->prd_tipo,
                    'prd_estatus' => $producto->prd_estatus,
                ]
            );

            $this->registrarResumenSku($request, $producto, 'catalogo_comercial.producto.regenerar_skus', $resumenSkus);

            return $producto->fresh();
        });
    }

    public function cambiarEstatus(Request $request, int $id, string $estatus): Producto
    {
        $producto = Producto::query()->findOrFail($id);

        $producto->update([
            'prd_estatus' => $estatus,
            'prd_updated_by_usr_id' => optional($request->user())->usr_id,
        ]);

        $this->sincronizarEstatusSkusPorProducto($request, $producto->prd_id, $estatus);

        $this->auditoriaService->registrarAccion(
            $request,
            $estatus === 'activo' ? 'catalogo_comercial.producto.activar' : 'catalogo_comercial.producto.inactivar',
            'tbl_productos_prd',
            (string) $producto->prd_id,
            ['prd_estatus' => $estatus]
        );

        return $producto;
    }

    public function eliminar(Request $request, int $id): void
    {
        DB::transaction(function () use ($request, $id): void {
            $producto = Producto::query()->findOrFail($id);
            $this->productoImagenTemporalService->eliminarImagenPersistida($producto->prd_imagen_path);

            $this->eliminarSkusDelProducto($request, $producto->prd_id);

            ProductoAtributo::query()
                ->where('pat_prd_id', $producto->prd_id)
                ->where('pat_deleted', false)
                ->whereNull('pat_deleted_at')
                ->update([
                    'pat_deleted' => true,
                    'pat_deleted_at' => now(),
                    'pat_estatus' => 'inactivo',
                    'pat_updated_by_usr_id' => optional($request->user())->usr_id,
                    'pat_updated_at' => now(),
                ]);

            $this->eliminarCorridasDelProducto($request, $producto->prd_id);

            $producto->forceFill([
                'prd_estatus' => 'inactivo',
                'prd_updated_by_usr_id' => optional($request->user())->usr_id,
            ])->save();

            $producto->marcarComoEliminado();

            $this->auditoriaService->registrarAccion(
                $request,
                'catalogo_comercial.producto.eliminar',
                'tbl_productos_prd',
                (string) $producto->prd_id,
                ['prd_codigo' => $producto->prd_codigo]
            );
        });
    }

    public function opcionesParaFormulario(): array
    {
        return [
            'marcas' => \App\Models\Marca::query()->where('mrc_estatus', 'activo')->orderBy('mrc_nombre')->get(['mrc_id', 'mrc_nombre']),
            'lineas' => \App\Models\Linea::query()->where('lna_estatus', 'activo')->orderBy('lna_nombre')->get(['lna_id', 'lna_nombre']),
            'categorias' => \App\Models\Categoria::query()->where('ctg_estatus', 'activo')->orderBy('ctg_nombre')->get(['ctg_id', 'ctg_nombre']),
            'descripciones' => \App\Models\Descripcion::query()->where('dsc_estatus', 'activo')->orderBy('dsc_nombre')->get(['dsc_id', 'dsc_nombre']),
            'unidades' => \App\Models\UnidadMedida::query()->where('umd_estatus', 'activo')->orderByDesc('umd_es_predeterminada')->orderBy('umd_nombre')->get(['umd_id', 'umd_nombre', 'umd_codigo', 'umd_es_predeterminada']),
            'almacenes' => Almacen::query()->with('sucursal:scl_id,scl_nombre')->where('alm_estatus', 'activo')->orderBy('alm_scl_id')->orderBy('alm_nombre')->get(['alm_id', 'alm_scl_id', 'alm_nombre']),
            'proveedores' => \App\Models\Proveedor::query()->where('prv_estatus', 'activo')->orderBy('prv_nombre_empresa')->get(['prv_id', 'prv_nombre_empresa', 'prv_razon_social']),
            'atributos' => \App\Models\Atributo::query()->where('atr_estatus', 'activo')->orderBy('atr_nombre')->get(['atr_id', 'atr_nombre']),
            'productos' => Producto::query()->where('prd_estatus', 'activo')->orderBy('prd_nombre')->get(['prd_id', 'prd_nombre', 'prd_codigo']),
        ];
    }

    private function sincronizarAlmacenesProducto(Request $request, int $productoId, array $almacenIds): void
    {
        $almacenIds = collect($almacenIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $existentes = Almacen::query()
            ->whereIn('alm_id', $almacenIds)
            ->where('alm_estatus', 'activo')
            ->pluck('alm_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($existentes->count() !== $almacenIds->count()) {
            throw ValidationException::withMessages([
                'almacen_ids' => 'Uno o más almacenes seleccionados no están disponibles.',
            ]);
        }

        ProductoAlmacen::query()
            ->where('pra_prd_id', $productoId)
            ->whereNotIn('pra_alm_id', $existentes->all())
            ->where('pra_deleted', false)
            ->whereNull('pra_deleted_at')
            ->update([
                'pra_deleted' => true,
                'pra_deleted_at' => now(),
                'pra_updated_by_usr_id' => optional($request->user())->usr_id,
                'pra_updated_at' => now(),
            ]);

        foreach ($existentes as $almacenId) {
            $registro = ProductoAlmacen::query()
                ->where('pra_prd_id', $productoId)
                ->where('pra_alm_id', (int) $almacenId)
                ->first();

            if ($registro) {
                $registro->forceFill([
                    'pra_deleted' => false,
                    'pra_deleted_at' => null,
                    'pra_updated_by_usr_id' => optional($request->user())->usr_id,
                ])->save();
                continue;
            }

            ProductoAlmacen::query()->create([
                'pra_prd_id' => $productoId,
                'pra_alm_id' => (int) $almacenId,
                'pra_created_by_usr_id' => optional($request->user())->usr_id,
                'pra_updated_by_usr_id' => optional($request->user())->usr_id,
            ]);
        }
    }

    public function atributosPermitidosProducto(int $productoId)
    {
        return ProductoAtributo::query()
            ->join('tbl_atributos_atr as atr', 'atr.atr_id', '=', 'tbl_producto_atributos_pat.pat_atr_id')
            ->where('tbl_producto_atributos_pat.pat_prd_id', $productoId)
            ->where('tbl_producto_atributos_pat.pat_deleted', false)
            ->whereNull('tbl_producto_atributos_pat.pat_deleted_at')
            ->where('tbl_producto_atributos_pat.pat_estatus', 'activo')
            ->where('atr.atr_estatus', 'activo')
            ->orderBy('atr.atr_nombre')
            ->get(['atr.atr_id', 'atr.atr_nombre']);
    }

    private function resolverImagenProducto(Request $request, array $datos, ?Producto $producto): array
    {
        if (Arr::get($datos, 'prd_imagen_reset')) {
            if ($producto) {
                $this->productoImagenTemporalService->eliminarImagenPersistida($producto->prd_imagen_path);
            }

            return ['tipo' => null, 'path' => null, 'url' => null];
        }

        $metodo = Arr::get($datos, 'prd_imagen_metodo');

        if ($metodo === 'archivo' && $request->hasFile('prd_imagen_archivo')) {
            $imagen = $this->productoImagenTemporalService->guardarArchivoFinal($request->file('prd_imagen_archivo'));

            if ($producto) {
                $this->productoImagenTemporalService->eliminarImagenPersistida($producto->prd_imagen_path);
            }

            return ['tipo' => $imagen['tipo'], 'path' => $imagen['path'], 'url' => $imagen['url']];
        }

        if ($metodo === 'url' && filled(Arr::get($datos, 'prd_imagen_url'))) {
            if ($producto) {
                $this->productoImagenTemporalService->eliminarImagenPersistida($producto->prd_imagen_path);
            }

            return ['tipo' => 'url', 'path' => null, 'url' => trim((string) Arr::get($datos, 'prd_imagen_url'))];
        }

        if ($metodo === 'qr' && filled(Arr::get($datos, 'prd_imagen_temp_token'))) {
            $imagen = $this->productoImagenTemporalService->moverATemporalFinal((string) Arr::get($datos, 'prd_imagen_temp_token'));

            if ($imagen) {
                if ($producto) {
                    $this->productoImagenTemporalService->eliminarImagenPersistida($producto->prd_imagen_path);
                }

                return ['tipo' => $imagen['tipo'], 'path' => $imagen['path'], 'url' => $imagen['url']];
            }

            if (!$producto) {
                throw ValidationException::withMessages([
                    'prd_imagen_temp_token' => 'Todavía no se ha recibido la imagen desde el celular.',
                ]);
            }
        }

        if (!$producto && filled($metodo)) {
            throw ValidationException::withMessages([
                'prd_imagen_metodo' => 'Debes completar la carga de la imagen general antes de guardar.',
            ]);
        }

        return [
            'tipo' => $producto?->prd_imagen_tipo,
            'path' => $producto?->prd_imagen_path,
            'url' => $producto?->prd_imagen_url,
        ];
    }

    private function normalizarConfiguracionProducto(string $tipo, array $atributoIds, array $atributoValores, array $corridasRaw = []): array
    {
        if ($tipo !== 'variable') {
            return [
                'tipo' => 'simple',
                'atributo_ids' => [],
                'atributo_valores' => [],
                'corridas' => [],
            ];
        }

        $atributoIds = array_values(array_unique(array_map('intval', array_filter($atributoIds))));

        if (empty($atributoIds)) {
            throw ValidationException::withMessages([
                'atributo_ids' => 'Debes seleccionar al menos un atributo para un producto variable.',
            ]);
        }

        $atributosActivos = Atributo::query()
            ->whereIn('atr_id', $atributoIds)
            ->where('atr_estatus', 'activo')
            ->pluck('atr_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if (count($atributosActivos) !== count($atributoIds)) {
            throw ValidationException::withMessages([
                'atributo_ids' => 'Uno o más atributos seleccionados no existen o están inactivos.',
            ]);
        }

        $mapaValores = [];
        $todosLosValores = [];

        foreach ($atributoIds as $atributoId) {
            $valores = Arr::get($atributoValores, (string) $atributoId, Arr::get($atributoValores, $atributoId, []));
            $valorIds = array_values(array_unique(array_map('intval', array_filter((array) $valores))));

            if (empty($valorIds)) {
                throw ValidationException::withMessages([
                    'atributo_valores' => 'Cada atributo seleccionado debe tener al menos un valor para generar corridas.',
                ]);
            }

            $mapaValores[$atributoId] = $valorIds;
            $todosLosValores = array_merge($todosLosValores, $valorIds);
        }

        $valoresActivos = ValorAtributo::query()
            ->whereIn('vat_id', $todosLosValores)
            ->where('vat_estatus', 'activo')
            ->where('vat_deleted', false)
            ->whereNull('vat_deleted_at')
            ->get(['vat_id', 'vat_atr_id'])
            ->keyBy('vat_id');

        if ($valoresActivos->count() !== count(array_unique($todosLosValores))) {
            throw ValidationException::withMessages([
                'atributo_valores' => 'Uno o más valores seleccionados no existen o están inactivos.',
            ]);
        }

        foreach ($mapaValores as $atributoId => $valorIds) {
            foreach ($valorIds as $valorId) {
                $valor = $valoresActivos->get($valorId);

                if (!$valor || (int) $valor->vat_atr_id !== (int) $atributoId) {
                    throw ValidationException::withMessages([
                        'atributo_valores' => 'Se detectaron valores que no pertenecen al atributo seleccionado.',
                    ]);
                }
            }
        }

        $corridas = $this->normalizarCorridas($corridasRaw, $atributoIds, $mapaValores, $valoresActivos);
        if (empty($corridas)) {
            throw ValidationException::withMessages([
                'corridas' => 'Debes configurar al menos una corrida para producto variable.',
            ]);
        }

        return [
            'tipo' => 'variable',
            'atributo_ids' => $atributoIds,
            'atributo_valores' => $mapaValores,
            'corridas' => $corridas,
        ];
    }

    private function normalizarCorridas(array $corridasRaw, array $atributoIds, array $mapaValores, Collection $valoresActivos): array
    {
        $corridas = collect($corridasRaw)
            ->filter(fn ($item) => is_array($item))
            ->values()
            ->map(function ($item) {
                return [
                    'atr_id' => (int) ($item['crc_atr_id'] ?? 0),
                    'nombre' => trim((string) ($item['crc_nombre'] ?? '')),
                    'valor_ids' => array_values(array_unique(array_map('intval', array_filter((array) ($item['crc_valor_ids'] ?? []))))),
                    'precio_base' => (float) ($item['crc_precio_base'] ?? 0),
                    'costo_base' => (float) ($item['crc_costo_base'] ?? 0),
                    'stock_minimo' => (int) ($item['crc_stock_minimo'] ?? 0),
                    'stock_maximo' => (int) ($item['crc_stock_maximo'] ?? 0),
                ];
            })
            ->filter(fn ($item) => $item['atr_id'] > 0 && !empty($item['valor_ids']))
            ->values();

        if ($corridas->isEmpty()) {
            return [];
        }

        $atributosPermitidos = array_flip($atributoIds);
        $usadosPorAtributo = [];

        foreach ($corridas as $idx => $corrida) {
            if (!isset($atributosPermitidos[$corrida['atr_id']])) {
                throw ValidationException::withMessages([
                    "corridas.{$idx}.crc_atr_id" => 'El atributo de la corrida no está habilitado en el producto.',
                ]);
            }

            if ($corrida['nombre'] === '') {
                throw ValidationException::withMessages([
                    "corridas.{$idx}.crc_nombre" => 'Cada corrida debe tener nombre.',
                ]);
            }

            if ($corrida['stock_maximo'] < $corrida['stock_minimo']) {
                throw ValidationException::withMessages([
                    "corridas.{$idx}.crc_stock_maximo" => 'El stock máximo de la corrida debe ser mayor o igual al stock mínimo.',
                ]);
            }

            $valoresPermitidos = $mapaValores[$corrida['atr_id']] ?? [];
            $setPermitidos = array_flip($valoresPermitidos);

            foreach ($corrida['valor_ids'] as $valorId) {
                if (!isset($setPermitidos[$valorId]) || !$valoresActivos->has($valorId)) {
                    throw ValidationException::withMessages([
                        "corridas.{$idx}.crc_valor_ids" => 'La corrida contiene valores inválidos para el atributo seleccionado.',
                    ]);
                }

                if (isset($usadosPorAtributo[$corrida['atr_id']][$valorId])) {
                    throw ValidationException::withMessages([
                        "corridas.{$idx}.crc_valor_ids" => 'Un mismo valor de atributo no puede repetirse en más de una corrida.',
                    ]);
                }

                $usadosPorAtributo[$corrida['atr_id']][$valorId] = true;
            }
        }

        return $corridas
            ->values()
            ->map(fn ($corrida, $idx) => array_merge($corrida, ['orden' => $idx + 1]))
            ->all();
    }

    private function sincronizarAtributosProducto(Request $request, int $productoId, array $atributoIds): void
    {
        $atributoIds = array_values(array_unique(array_map('intval', $atributoIds)));

        ProductoAtributo::query()
            ->where('pat_prd_id', $productoId)
            ->where('pat_deleted', false)
            ->whereNull('pat_deleted_at')
            ->whereNotIn('pat_atr_id', $atributoIds)
            ->update([
                'pat_deleted' => true,
                'pat_deleted_at' => now(),
                'pat_estatus' => 'inactivo',
                'pat_updated_by_usr_id' => optional($request->user())->usr_id,
                'pat_updated_at' => now(),
            ]);

        foreach ($atributoIds as $atributoId) {
            $registro = ProductoAtributo::query()
                ->withDeleted()
                ->where('pat_prd_id', $productoId)
                ->where('pat_atr_id', $atributoId)
                ->first();

            $datos = [
                'pat_estatus' => 'activo',
                'pat_deleted' => false,
                'pat_deleted_at' => null,
                'pat_updated_by_usr_id' => optional($request->user())->usr_id,
            ];

            if ($registro) {
                $registro->update($datos);
                continue;
            }

            ProductoAtributo::query()->create(array_merge($datos, [
                'pat_prd_id' => $productoId,
                'pat_atr_id' => $atributoId,
                'pat_created_by_usr_id' => optional($request->user())->usr_id,
            ]));
        }
    }

    private function sincronizarCorridasProducto(Request $request, int $productoId, array $corridas): void
    {
        $corridasActivas = ProductoCorrida::query()
            ->where('prc_prd_id', $productoId)
            ->where('prc_deleted', false)
            ->whereNull('prc_deleted_at')
            ->get(['prc_id']);

        $corridaIds = $corridasActivas->pluck('prc_id')->map(fn ($id) => (int) $id)->all();
        if (!empty($corridaIds)) {
            ProductoCorridaValor::query()
                ->whereIn('pcv_prc_id', $corridaIds)
                ->where('pcv_deleted', false)
                ->whereNull('pcv_deleted_at')
                ->update([
                    'pcv_deleted' => true,
                    'pcv_deleted_at' => now(),
                    'pcv_estatus' => 'inactivo',
                    'pcv_updated_by_usr_id' => optional($request->user())->usr_id,
                    'pcv_updated_at' => now(),
                ]);
        }

        ProductoCorrida::query()
            ->where('prc_prd_id', $productoId)
            ->where('prc_deleted', false)
            ->whereNull('prc_deleted_at')
            ->update([
                'prc_deleted' => true,
                'prc_deleted_at' => now(),
                'prc_estatus' => 'inactivo',
                'prc_updated_by_usr_id' => optional($request->user())->usr_id,
                'prc_updated_at' => now(),
            ]);

        foreach ($corridas as $corrida) {
            $registro = ProductoCorrida::query()->create([
                'prc_prd_id' => $productoId,
                'prc_atr_id' => $corrida['atr_id'],
                'prc_nombre' => $corrida['nombre'],
                'prc_orden' => $corrida['orden'],
                'prc_precio_base' => $corrida['precio_base'],
                'prc_costo_base' => $corrida['costo_base'],
                'prc_stock_minimo' => $corrida['stock_minimo'],
                'prc_stock_maximo' => $corrida['stock_maximo'],
                'prc_estatus' => 'activo',
                'prc_created_by_usr_id' => optional($request->user())->usr_id,
                'prc_updated_by_usr_id' => optional($request->user())->usr_id,
            ]);

            foreach ($corrida['valor_ids'] as $valorId) {
                ProductoCorridaValor::query()->create([
                    'pcv_prc_id' => $registro->prc_id,
                    'pcv_vat_id' => $valorId,
                    'pcv_estatus' => 'activo',
                    'pcv_created_by_usr_id' => optional($request->user())->usr_id,
                    'pcv_updated_by_usr_id' => optional($request->user())->usr_id,
                ]);
            }
        }
    }

    private function sincronizarSkusGenerados(Request $request, Producto $producto, array $configuracion): array
    {
        $combinacionesDeseadas = $configuracion['tipo'] === 'simple'
            ? [[]]
            : $this->generarCombinaciones($configuracion['atributo_ids'], $configuracion['atributo_valores']);

        $skusActivos = ProductoSku::query()
            ->with([
                'valoresAtributo' => fn ($query) => $query->select('tbl_valores_atributo_vat.vat_id', 'tbl_valores_atributo_vat.vat_atr_id', 'tbl_valores_atributo_vat.vat_valor'),
            ])
            ->where('psk_prd_id', $producto->prd_id)
            ->where('psk_deleted', false)
            ->whereNull('psk_deleted_at')
            ->get();

        $skusPorFirma = [];
        foreach ($skusActivos as $sku) {
            $firma = $this->firmaCombinacion($sku->valoresAtributo->pluck('vat_id')->map(fn ($id) => (int) $id)->all());
            $skusPorFirma[$firma] = $sku;
        }

        $usados = [];
        $keepIds = [];
        $resumen = [
            'creados' => [],
            'actualizados' => [],
            'eliminados' => [],
        ];
        $mapaCorridas = $this->construirMapaCorridasPorValor($configuracion['corridas'] ?? []);

        foreach ($combinacionesDeseadas as $combinacion) {
            $firma = $this->firmaCombinacion($combinacion);
            $valores = $this->obtenerValoresOrdenados($combinacion, $configuracion['atributo_ids']);
            $codigoSku = $this->generarCodigoSku($producto, $valores, $usados);
            $nombreSku = $this->generarNombreSku($producto, $valores);
            $reglasCorrida = $this->resolverReglasCorridaPorCombinacion($combinacion, $mapaCorridas);
            $precioSku = $reglasCorrida['precio_base'] ?? (float) $producto->prd_precio_base;
            $costoSku = $reglasCorrida['costo_base'] ?? (float) $producto->prd_costo;
            $stockMinSku = $reglasCorrida['stock_minimo'] ?? (int) $producto->prd_stock_minimo;
            $stockMaxSku = $reglasCorrida['stock_maximo'] ?? (int) $producto->prd_stock_maximo;

            if (isset($skusPorFirma[$firma])) {
                $sku = $skusPorFirma[$firma];
                $keepIds[] = $sku->psk_id;
                $usados[] = $codigoSku;
                $barcodeActual = (string) ($sku->psk_codigo_barras ?? '');
                $barcodeSugerido = ($barcodeActual === '' || $barcodeActual === (string) $sku->psk_codigo)
                    ? $codigoSku
                    : $barcodeActual;

                $sku->update([
                    'psk_codigo' => $codigoSku,
                    'psk_codigo_barras' => $barcodeSugerido,
                    'psk_nombre' => $nombreSku,
                    'psk_precio' => $precioSku,
                    'psk_costo' => $costoSku,
                    'psk_stock_minimo' => $stockMinSku,
                    'psk_stock_maximo' => $stockMaxSku,
                    'psk_estatus' => $producto->prd_estatus,
                    'psk_updated_by_usr_id' => optional($request->user())->usr_id,
                ]);

                $this->sincronizarValoresSku($request, $sku->psk_id, $combinacion);
                $resumen['actualizados'][] = $codigoSku;
                continue;
            }

            $sku = ProductoSku::query()->create([
                'psk_prd_id' => $producto->prd_id,
                'psk_codigo' => $codigoSku,
                'psk_codigo_barras' => $codigoSku,
                'psk_nombre' => $nombreSku,
                'psk_precio' => $precioSku,
                'psk_costo' => $costoSku,
                'psk_stock_minimo' => $stockMinSku,
                'psk_stock_maximo' => $stockMaxSku,
                'psk_estatus' => $producto->prd_estatus,
                'psk_created_by_usr_id' => optional($request->user())->usr_id,
                'psk_updated_by_usr_id' => optional($request->user())->usr_id,
            ]);

            $this->sincronizarValoresSku($request, $sku->psk_id, $combinacion);
            $keepIds[] = $sku->psk_id;
            $usados[] = $codigoSku;
            $resumen['creados'][] = $codigoSku;
        }

        $skusAEliminar = ProductoSku::query()
            ->where('psk_prd_id', $producto->prd_id)
            ->where('psk_deleted', false)
            ->whereNull('psk_deleted_at')
            ->when(!empty($keepIds), fn ($query) => $query->whereNotIn('psk_id', $keepIds))
            ->get();

        foreach ($skusAEliminar as $sku) {
            $resumen['eliminados'][] = $sku->psk_codigo;
            $this->eliminarSkuConRelaciones($request, $sku);
        }

        return $resumen;
    }

    private function construirMapaCorridasPorValor(array $corridas): array
    {
        $mapa = [];

        foreach ($corridas as $corrida) {
            foreach ($corrida['valor_ids'] as $valorId) {
                $mapa[(int) $valorId] = [
                    'nombre' => $corrida['nombre'],
                    'precio_base' => (float) $corrida['precio_base'],
                    'costo_base' => (float) $corrida['costo_base'],
                    'stock_minimo' => (int) $corrida['stock_minimo'],
                    'stock_maximo' => (int) $corrida['stock_maximo'],
                ];
            }
        }

        return $mapa;
    }

    private function resolverReglasCorridaPorCombinacion(array $combinacion, array $mapaCorridas): ?array
    {
        foreach ($combinacion as $valorId) {
            $valorId = (int) $valorId;
            if (isset($mapaCorridas[$valorId])) {
                return $mapaCorridas[$valorId];
            }
        }

        return null;
    }

    private function eliminarCorridasDelProducto(Request $request, int $productoId): void
    {
        $corridasActivas = ProductoCorrida::query()
            ->where('prc_prd_id', $productoId)
            ->where('prc_deleted', false)
            ->whereNull('prc_deleted_at')
            ->get(['prc_id']);

        $corridaIds = $corridasActivas->pluck('prc_id')->map(fn ($id) => (int) $id)->all();
        if (!empty($corridaIds)) {
            ProductoCorridaValor::query()
                ->whereIn('pcv_prc_id', $corridaIds)
                ->where('pcv_deleted', false)
                ->whereNull('pcv_deleted_at')
                ->update([
                    'pcv_deleted' => true,
                    'pcv_deleted_at' => now(),
                    'pcv_estatus' => 'inactivo',
                    'pcv_updated_by_usr_id' => optional($request->user())->usr_id,
                    'pcv_updated_at' => now(),
                ]);
        }

        ProductoCorrida::query()
            ->where('prc_prd_id', $productoId)
            ->where('prc_deleted', false)
            ->whereNull('prc_deleted_at')
            ->update([
                'prc_deleted' => true,
                'prc_deleted_at' => now(),
                'prc_estatus' => 'inactivo',
                'prc_updated_by_usr_id' => optional($request->user())->usr_id,
                'prc_updated_at' => now(),
            ]);
    }

    private function sincronizarValoresSku(Request $request, int $skuId, array $valorIds): void
    {
        $valorIds = array_values(array_unique(array_map('intval', $valorIds)));

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

    private function sincronizarEstatusSkusPorProducto(Request $request, int $productoId, string $estatus): void
    {
        ProductoSku::query()
            ->where('psk_prd_id', $productoId)
            ->where('psk_deleted', false)
            ->whereNull('psk_deleted_at')
            ->update([
                'psk_estatus' => $estatus,
                'psk_updated_by_usr_id' => optional($request->user())->usr_id,
                'psk_updated_at' => now(),
            ]);
    }

    private function eliminarSkusDelProducto(Request $request, int $productoId): void
    {
        $skus = ProductoSku::query()
            ->where('psk_prd_id', $productoId)
            ->where('psk_deleted', false)
            ->whereNull('psk_deleted_at')
            ->get();

        foreach ($skus as $sku) {
            $this->eliminarSkuConRelaciones($request, $sku);
        }
    }

    private function eliminarSkuConRelaciones(Request $request, ProductoSku $sku): void
    {
        SkuValorAtributo::query()
            ->where('sva_psk_id', $sku->psk_id)
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
    }

    private function generarCombinaciones(array $atributoIds, array $atributoValores): array
    {
        $acumulado = [[]];

        foreach ($atributoIds as $atributoId) {
            $siguiente = [];

            foreach ($acumulado as $base) {
                foreach ($atributoValores[$atributoId] as $valorId) {
                    $siguiente[] = array_merge($base, [(int) $valorId]);
                }
            }

            $acumulado = $siguiente;
        }

        return $acumulado;
    }

    private function obtenerValoresOrdenados(array $valorIds, array $atributoIds): Collection
    {
        $valores = ValorAtributo::query()
            ->whereIn('vat_id', $valorIds)
            ->get(['vat_id', 'vat_atr_id', 'vat_valor', 'vat_clave'])
            ->keyBy('vat_id');

        $ordenAtributos = array_flip($atributoIds);

        return collect($valorIds)
            ->map(fn ($valorId) => $valores->get($valorId))
            ->filter()
            ->sortBy(fn ($valor) => $ordenAtributos[(int) $valor->vat_atr_id] ?? 999)
            ->values();
    }

    private function generarCodigoTemporal(): string
    {
        return 'TMP-' . Str::upper(Str::random(10));
    }

    private function generarCodigoProducto(Producto $producto, ?string $nombre = null): string
    {
        $segmento = $this->generarSegmentoProducto($nombre ?: $producto->prd_nombre);
        $base = $segmento . '-' . str_pad((string) $producto->prd_id, 3, '0', STR_PAD_LEFT);
        $candidato = $base;
        $consecutivo = 2;

        while (Producto::query()
            ->withDeleted()
            ->where('prd_codigo', $candidato)
            ->where('prd_id', '!=', $producto->prd_id)
            ->exists()) {
            $sufijo = '-' . $consecutivo;
            $candidato = Str::substr($base, 0, 40 - strlen($sufijo)) . $sufijo;
            $consecutivo++;
        }

        return $candidato;
    }

    private function generarSegmentoProducto(string $nombre): string
    {
        $tokens = collect(preg_split('/[^A-Za-z0-9]+/', Str::upper(Str::ascii($nombre)) ?: ''))
            ->filter()
            ->reject(fn ($token) => in_array($token, [
                'DE', 'DEL', 'LA', 'LAS', 'EL', 'LOS', 'Y', 'CON', 'PARA',
                'PRODUCTO', 'ARTICULO', 'ARTICULO', 'PLAYERA', 'CAMISA', 'BLUSA',
                'PANTALON', 'PANTALONES', 'TELA', 'PIEZA', 'PAR',
            ], true))
            ->values();

        if ($tokens->count() >= 2) {
            $segmento = Str::substr($tokens[0], 0, 3) . Str::substr($tokens[1], 0, 1);
        } elseif ($tokens->count() === 1) {
            $segmento = Str::substr($tokens[0], 0, 4);
        } else {
            $segmento = 'PRD';
        }

        return Str::padRight(Str::upper($segmento), 4, 'X');
    }

    private function generarCodigoSku(Producto $producto, Collection $valores, array &$usados): string
    {
        $base = $producto->prd_codigo ?: $this->generarCodigoProducto($producto);
        $partes = $valores->isEmpty()
            ? ['STD']
            : $valores->map(fn ($valor) => $this->abreviarValorParaSku((string) $valor->vat_valor))->all();

        $candidato = Str::substr($base . '-' . implode('-', $partes), 0, 60);
        $consecutivo = 2;

        while (in_array($candidato, $usados, true)) {
            $sufijo = '-' . $consecutivo;
            $candidato = Str::substr($base . '-' . implode('-', $partes), 0, 60 - strlen($sufijo)) . $sufijo;
            $consecutivo++;
        }

        return $candidato;
    }

    private function abreviarValorParaSku(string $valor): string
    {
        $tokens = collect(preg_split('/[^A-Za-z0-9]+/', Str::upper(Str::ascii($valor)) ?: ''))
            ->filter()
            ->values();

        $numericos = $tokens
            ->filter(fn ($token) => preg_match('/^[0-9]+$/', $token) === 1)
            ->implode('');

        if ($numericos !== '') {
            return Str::substr($numericos, 0, 3);
        }

        $palabras = $tokens
            ->reject(fn ($token) => preg_match('/^[0-9]+$/', $token) === 1)
            ->values();

        if ($palabras->count() >= 2) {
            return Str::substr($palabras[0], 0, 2) . Str::substr($palabras[1], 0, 1);
        }

        if ($palabras->count() === 1) {
            return Str::substr($palabras[0], 0, 3);
        }

        return 'VAL';
    }

    private function generarNombreSku(Producto $producto, Collection $valores): string
    {
        if ($valores->isEmpty()) {
            return $producto->prd_nombre . ' / Estándar';
        }

        return $producto->prd_nombre . ' / ' . $valores->pluck('vat_valor')->implode(' / ');
    }

    private function firmaCombinacion(array $valorIds): string
    {
        sort($valorIds);

        return implode('-', $valorIds);
    }

    private function obtenerMapaValoresSeleccionados(Producto $producto): array
    {
        $mapa = [];

        foreach ($producto->skus as $sku) {
            foreach ($sku->valoresAtributo as $valor) {
                $atributoId = (int) $valor->vat_atr_id;
                $mapa[$atributoId] = $mapa[$atributoId] ?? [];
                $mapa[$atributoId][] = (int) $valor->vat_id;
            }
        }

        foreach ($mapa as $atributoId => $valores) {
            $mapa[$atributoId] = array_values(array_unique($valores));
        }

        return $mapa;
    }

    public function obtenerCorridasProducto(Producto $producto): array
    {
        return $producto->corridas
            ->filter(fn (ProductoCorrida $corrida) => !$corrida->prc_deleted && $corrida->prc_deleted_at === null && $corrida->prc_estatus === 'activo')
            ->map(function (ProductoCorrida $corrida): array {
                return [
                    'prc_id' => $corrida->prc_id,
                    'prc_nombre' => $corrida->prc_nombre,
                    'prc_orden' => $corrida->prc_orden,
                    'prc_atr_id' => $corrida->prc_atr_id,
                    'prc_atr_nombre' => $corrida->atributo?->atr_nombre,
                    'prc_precio_base' => $corrida->prc_precio_base,
                    'prc_costo_base' => $corrida->prc_costo_base,
                    'prc_stock_minimo' => $corrida->prc_stock_minimo,
                    'prc_stock_maximo' => $corrida->prc_stock_maximo,
                    'prc_valor_ids' => $corrida->valores->pluck('vat_id')->map(fn ($id) => (int) $id)->values(),
                ];
            })
            ->values()
            ->all();
    }

    private function registrarResumenSku(Request $request, Producto $producto, string $accion, array $resumen): void
    {
        if (empty($resumen['creados']) && empty($resumen['actualizados']) && empty($resumen['eliminados'])) {
            return;
        }

        $this->auditoriaService->registrarAccion(
            $request,
            $accion,
            'tbl_producto_skus_psk',
            (string) $producto->prd_id,
            [
                'producto' => $producto->prd_codigo,
                'creados' => $resumen['creados'],
                'actualizados' => $resumen['actualizados'],
                'eliminados' => $resumen['eliminados'],
            ]
        );
    }
}
